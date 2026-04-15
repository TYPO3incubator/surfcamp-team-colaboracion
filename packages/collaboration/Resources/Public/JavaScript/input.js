let heartbeatInterval = null;
let activeField = null;
let lastField = null;

const knownDocs = new Set();
const knownShadowRoots = new Set();
const iframeDocMap = new WeakMap();
const remoteFocuses = new Map();

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
    } catch {
        return
    }
    const key = remoteKey(data);
    remoteFocuses.set(key, { data, ts: Date.now() });
    applyHighlight(data, true);
});

source.addEventListener('stream_blur', (e) => {
    try {
        const data = JSON.parse(e.data).eventData;
    } catch {
        return
    }
    remoteFocuses.delete(remoteKey(data));
    applyHighlight(data, false);
});

function remoteKey(d) {
    return `${d.table}-${d.uid}-${d.field}`;
}

function applyHighlight(data, on) {
    const el = findFieldElement(data);
    if (!el) return;
    el.style.outline = on ? '1px solid #3c7fdd' : '';
    el.style.outlineOffset = '0px';
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
    }
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
    const path = typeof e.composedPath === 'function' ? e.composedPath() : [];
    for (const node of path) {
        if (!(node instanceof Element)) continue;
        const hit = node.closest?.('input, textarea, select, [data-formengine-input-name]');
        if (hit) return hit;
    }
    return e.target?.closest?.('input, textarea, select, [data-formengine-input-name]') || null;
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
    const name = field.dataset?.formengineInputName || field.name;
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
