let heartbeatInterval = null;
let activeField = null;
let lastField = null;

const knownDocs = new Set();
const knownShadowRoots = new Set();
const iframeDocMap = new WeakMap();
const remoteFocuses = new Map();
const ckObservedEditables = new WeakSet();

// Shared across fieldFromEvent() (resolves focus events inside widgets) and
// applyHighlight() (picks a visible target when the underlying field is hidden).
// Keep this as the single source of truth so both paths stay consistent.
const WIDGET_WRAPPER_SELECTOR = [
    '.t3js-formengine-field-item',
    'typo3-rte-ckeditor-ckeditor5',
    'typo3-formengine-element-datetime',
    'typo3-formengine-element-text',
    'typo3-formengine-element-color',
    'typo3-formengine-element-link',
    'typo3-formengine-element-password',
    'typo3-formengine-element-folder',
    'typo3-formengine-element-category',
    'typo3-formengine-element-json',
].join(',');

const source = new EventSource(TYPO3.settings.ajaxUrls.collaboration_example);

source.addEventListener('open', () => {
    if (activeField) {
        sendFocus(activeField, false);
    }
    requestPresenceSync();
});

source.addEventListener('stream_focus', (e) => {
    try {
        const data = JSON.parse(e.data).eventData;
        const key = remoteKey(data);
        remoteFocuses.set(key, { data, ts: Date.now() });
        applyHighlight(data, true);
    } catch {
        return
    }
});

source.addEventListener('stream_blur', (e) => {
    try {
        const data = JSON.parse(e.data).eventData;
        remoteFocuses.delete(remoteKey(data));
        applyHighlight(data, false);
    } catch {
        return
    }
});

function remoteKey(d) {
    return `${d.table}-${d.uid}-${d.field}`;
}

function applyHighlight(data, on) {
    const el = findFieldElement(data);
    if (!el) return;

    // CKEditor: render the remote indicator with a dedicated class + inline
    // box-shadow. We deliberately do NOT toggle `ck-focused` here, because:
    //   (a) our own MutationObserver on .ck-editor__editable would see the
    //       class change and broadcast a phantom local focus for this user,
    //       causing a feedback loop;
    //   (b) if the local user is actually focused in the same editor, removing
    //       `ck-focused` on remote blur would strip CKEditor's own focus state
    //       from under them.
    const ckWrapper = el.closest?.('typo3-rte-ckeditor-ckeditor5');
    const ckEditable = ckWrapper?.querySelector?.('.ck-editor__editable');
    if (ckEditable) {
        if (on) {
            ckEditable.classList.add('collab-remote-focus');
            ckEditable.style.boxShadow = '0 0 0 2px #3c7fdd';
        } else {
            ckEditable.classList.remove('collab-remote-focus');
            ckEditable.style.boxShadow = '';
        }
        return;
    }

    let target = el;
    if (el.type === 'hidden' || (el.getBoundingClientRect && (el.getBoundingClientRect().width === 0 || el.getBoundingClientRect().height === 0))) {
        const wrapper = el.closest?.(WIDGET_WRAPPER_SELECTOR);
        if (wrapper) target = wrapper;
    }
    target.style.outline = on ? '1px solid #3c7fdd' : '';
    target.style.outlineOffset = '0px';
}

function reapplyAllHighlights() {
    for (const { data } of remoteFocuses.values()) {
        applyHighlight(data, true);
    }
}

function requestPresenceSync() {
    fetch(TYPO3.settings.ajaxUrls.collaboration_focus, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sync: true }),
        keepalive: true,
    }).catch(() => {});
}

function attachDoc(doc) {
    if (!doc || knownDocs.has(doc)) return;
    knownDocs.add(doc);
    doc.addEventListener('focusin', onFocusIn, true);
    doc.addEventListener('focusout', onFocusOut, true);
    deepWalk(doc);
    reapplyAllHighlights();
    if (activeField) sendFocus(activeField, false);
    requestPresenceSync();
}

function attachShadowRoot(root) {
    if (!root || knownShadowRoots.has(root)) return;
    knownShadowRoots.add(root);
    root.addEventListener('focusin', onFocusIn, true);
    root.addEventListener('focusout', onFocusOut, true);
    deepWalk(root);
}

// Recursively walk a root: catch all iframes and open shadow roots inside.
function deepWalk(root) {
    let nodes;
    try {
        nodes = root.querySelectorAll('*');
    } catch (e) {
        return;
    }
    for (const node of nodes) {
        if (node.tagName === 'IFRAME') {
            attachIframe(node);
        }
        if (node.shadowRoot) {
            attachShadowRoot(node.shadowRoot);
        }
        if (node.classList?.contains('ck-editor__editable')) {
            attachCkEditable(node);
        }
    }
}

// CKEditor manages its own focus state via the `ck-focused` class on
// .ck-editor__editable. DOM focusin/focusout on contenteditables is unreliable
// when CKEditor intercepts pointer events — so we observe class changes and
// drive focus/blur from that.
function attachCkEditable(editable) {
    if (!editable || ckObservedEditables.has(editable)) return;
    ckObservedEditables.add(editable);

    const resolveField = () => {
        const wrapper = editable.closest?.('typo3-rte-ckeditor-ckeditor5');
        if (!wrapper) return null;
        const inner = wrapper.querySelector?.('textarea[name^="data["], input[name^="data["], [data-formengine-input-name]');
        if (!inner) return null;
        return parsedFromField(inner);
    };

    // Per-editable state so we always know if *we* sent focus for this editor,
    // independent of the global `lastField` (which another field may have claimed
    // by the time `ck-focused` is removed).
    let wasFocused = false;

    const onToggle = () => {
        const parsed = resolveField();
        if (!parsed) return;
        const key = `${parsed.table}-${parsed.uid}-${parsed.field}`;
        const focused = editable.classList.contains('ck-focused');
        if (focused && !wasFocused) {
            wasFocused = true;
            if (activeField && lastField !== key) sendBlur(activeField);
            activeField = parsed;
            lastField = key;
            sendFocus(parsed, false);
            startHeartbeat();
        } else if (!focused && wasFocused) {
            wasFocused = false;
            // Only clear the global focus/blur the backend row if nothing else
            // has claimed ownership in the meantime — otherwise the new focus
            // holder (e.g. the header input) would be wiped by our blur.
            if (lastField === key) {
                sendBlur(parsed);
                activeField = null;
                lastField = null;
                stopHeartbeat();
            }
        }
    };

    const observer = new MutationObserver(onToggle);
    observer.observe(editable, { attributes: true, attributeFilter: ['class'] });
    if (editable.classList.contains('ck-focused')) onToggle();
}

function attachIframe(iframe) {
    const tryAttach = () => {
        let doc = null;
        try {
            doc = iframe.contentDocument || iframe.contentWindow?.document;
        } catch (e) {
            return;
        }
        if (!doc) return;
        if (iframeDocMap.get(iframe) === doc) return;
        iframeDocMap.set(iframe, doc);
        attachDoc(doc);
    };

    tryAttach();
    iframe.addEventListener('load', tryAttach, { once: true });
}

// Deep search: walks all known docs + their nested iframes + open shadow roots.
function findFieldElement({ table, uid, field }) {
    const inputName = `data[${table}][${uid}][${field}]`;
    const selector = `[data-formengine-input-name="${inputName}"],[name="${inputName}"]`;

    for (const doc of knownDocs) {
        const el = deepQuery(doc, selector);
        if (el) return el;
    }
    for (const root of knownShadowRoots) {
        const el = deepQuery(root, selector);
        if (el) return el;
    }
    return null;
}

function deepQuery(root, selector) {
    try {
        const direct = root.querySelector(selector);
        if (direct && direct.isConnected) return direct;
        const all = root.querySelectorAll('*');
        for (const node of all) {
            if (node.shadowRoot) {
                const hit = deepQuery(node.shadowRoot, selector);
                if (hit) return hit;
            }
            if (node.tagName === 'IFRAME') {
                try {
                    const innerDoc = node.contentDocument || node.contentWindow?.document;
                    if (innerDoc) {
                        const hit = deepQuery(innerDoc, selector);
                        if (hit) return hit;
                    }
                } catch (e) {
                    // cross-origin — skip
                }
            }
        }
    } catch (e) {
        // detached — skip
    }
    return null;
}

function fieldFromEvent(e) {
    const directSelector = 'input, textarea, select, [data-formengine-input-name], [data-formengine-datepicker-real-input-name]';
    const path = typeof e.composedPath === 'function' ? e.composedPath() : [];

    // 1) Direct hit: a real input/textarea/select with a parseable data[...] name.
    for (const node of path) {
        if (!(node instanceof Element)) continue;
        const hit = node.closest?.(directSelector);
        if (hit && parsedFromField(hit)) return hit;
    }
    const direct = e.target?.closest?.(directSelector);
    if (direct && parsedFromField(direct)) return direct;

    // 2) Widget fallback: focus landed inside a formengine widget wrapper
    //    (CKEditor contenteditable, datepicker popup, color picker, etc.) —
    //    resolve the wrapper's underlying named field.
    const innerSelector =
        '[data-formengine-input-name],' +
        '[data-formengine-datepicker-real-input-name],' +
        'textarea[name^="data["],' +
        'input[name^="data["],' +
        'select[name^="data["]';

    const candidates = path.length ? path : [e.target];
    for (const node of candidates) {
        if (!(node instanceof Element)) continue;
        const wrapper = node.closest?.(WIDGET_WRAPPER_SELECTOR);
        if (!wrapper) continue;
        const inner = wrapper.querySelector?.(innerSelector);
        if (inner && parsedFromField(inner)) return inner;
    }
    return null;
}

function onFocusOut(e) {
    const field = fieldFromEvent(e);
    if (!field) return;

    const parsed = parsedFromField(field);
    if (parsed) sendBlur(parsed);

    activeField = null;
    lastField = null;
    stopHeartbeat();
}

function onFocusIn(e) {
    const field = fieldFromEvent(e);
    if (!field) return;

    const parsed = parsedFromField(field);
    if (!parsed) return;

    const key = `${parsed.table}-${parsed.uid}-${parsed.field}`;
    activeField = parsed;

    if (lastField !== key) {
        lastField = key;
        sendFocus(parsed, false);
    }
    startHeartbeat();
}

function parsedFromField(field) {
    const name = field.dataset?.formengineInputName
        || field.dataset?.formengineDatepickerRealInputName
        || field.name;
    if (!name) return null;
    return parseInputName(name);
}

function sendFocus(data, heartbeat) {
    fetch(TYPO3.settings.ajaxUrls.collaboration_focus, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...data, heartbeat }),
        keepalive: true,
    });
}

function sendBlur(data) {
    const body = JSON.stringify({ ...data, blur: true });
    if (navigator.sendBeacon) {
        const url = TYPO3.settings.ajaxUrls.collaboration_focus;
        navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
        return;
    }
    fetch(TYPO3.settings.ajaxUrls.collaboration_focus, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body,
        keepalive: true,
    });
}

function startHeartbeat() {
    stopHeartbeat();
    heartbeatInterval = setInterval(() => {
        if (!activeField) return;
        sendFocus(activeField, true);
    }, 1000);
}

function stopHeartbeat() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
}

function parseInputName(name) {
    const match = name.match(/^data\[(.*?)]\[(\d+)]\[(.*?)]$/);
    if (!match) return null;
    return {
        table: match[1],
        uid: parseInt(match[2], 10),
        field: match[3],
    };
}

setInterval(() => {
    const now = Date.now();
    for (const [key, { ts }] of remoteFocuses) {
        if (now - ts > 1500) {
            const { data } = remoteFocuses.get(key);
            remoteFocuses.delete(key);
            applyHighlight(data, false);
        }
    }
    reapplyAllHighlights();
}, 1000);

window.addEventListener('beforeunload', () => {
    if (activeField) sendBlur(activeField);
});

attachDoc(document);

const observer = new MutationObserver((mutations) => {
    for (const m of mutations) {
        m.addedNodes.forEach((node) => {
            if (node.nodeType !== 1) return;
            if (node.tagName === 'IFRAME') {
                attachIframe(node);
            }
            if (node.shadowRoot) {
                attachShadowRoot(node.shadowRoot);
            }
            if (node.querySelectorAll) {
                deepWalk(node);
            }
        });
    }
    reapplyAllHighlights();
});
observer.observe(document.documentElement, { childList: true, subtree: true });

// Periodic re-walk of all known docs to catch nested iframes / shadow roots
// that appear when sidebar↔fullscreen toggles re-mount the editor.
setInterval(() => {
    for (const doc of knownDocs) {
        try {
            deepWalk(doc);
        } catch (e) {
            // detached — ignore
        }
    }
}, 1000);
