let inputDoc = null
let heartbeatInterval = null;
let activeField = null;
let lastField = null

const source = new EventSource(TYPO3.settings.ajaxUrls.collaboration_example);
source.addEventListener('stream_focus', (e) => {
    const data = JSON.parse(e.data).eventData

    console.log('el focused')
    const el = findFieldElement(data)
    console.log('el found')
    if (el){
        el.style.border = '3px solid red'
    }
})

source.addEventListener('stream_blur', (e) => {
    const data = JSON.parse(e.data).eventData

    const el = findFieldElement(data)
    if (el){
        el.style.border = ''
    }
})

function attachToIframes() {
    document.querySelectorAll('iframe').forEach((iframe) => {

        try {
            console.log('attach')
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            if (iframeDoc) {
                iframeDoc.addEventListener('focusin', onFocusIn);
                iframeDoc.addEventListener('focusout', onFocusOut);
                inputDoc = iframeDoc
            }
        } catch (e) {
        }
    });
}

function findFieldElement({ table, uid, field }) {
    const inputName = `data[${table}][${uid}][${field}]`;

    const selector = `
        [data-formengine-input-name="${inputName}"],
        [name="${inputName}"]
    `;

    return inputDoc.querySelector(selector);
}

function onFocusOut(e) {
    const field = e.target.closest('input, textarea, select, [data-formengine-input-name]');
    if (!field) return;
    activeField = null;
    lastField = null;

    setTimeout(() => {
        if (!document.activeElement || document.activeElement === document.body) {
            stopHeartbeat();

            console.log('blur → stop heartbeat');
        }
    }, 50);
}

function onFocusIn(e) {
    console.log('focus')
    const field = e.target.closest('input, textarea, select, [data-formengine-input-name]');
    if (!field) return;

    const name =
        field.dataset?.formengineInputName ||
        field.name;

    if (!name) return;

    const parsed = parseInputName(name);
    if (!parsed) return;

    const key = `${parsed.table}-${parsed.uid}-${parsed.field}`;
    if (lastField === key) return;
    lastField = key;
    activeField = parsed;

    sendFocus(parsed, false)
    startHeartbeat()
}

function sendFocus(data, heartbeat) {
    console.log('sent focus heartbeat?', heartbeat)
    fetch(TYPO3.settings.ajaxUrls.collaboration_focus, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            ...data,
            heartbeat: heartbeat,
        }),
    });
}

function startHeartbeat() {
    stopHeartbeat();

    heartbeatInterval = setInterval(() => {
        if (!activeField) return;

        sendFocus(activeField, true)
    }, 250)
}

function stopHeartbeat() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval)
        heartbeatInterval = null
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

attachToIframes();

const observer = new MutationObserver(() => attachToIframes());
observer.observe(document.body, { childList: true, subtree: true });

