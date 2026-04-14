import DeferredAction from "@typo3/backend/action-button/deferred-action.js";

const source = new EventSource(TYPO3.settings.ajaxUrls.collaboration_example);

source.addEventListener('lockedRecordEvent', (e) => {
    const data = JSON.parse(e.data);
    TYPO3.Notification.warning(data.eventData.data);
});

source.addEventListener('clearCacheEvent', (e) => {
    const data = JSON.parse(e.data);
    const deferredActionCallback = new DeferredAction(function () {
        return Promise.resolve(window.location.reload());
    });
    const actions = [{label: 'Reload Backend', action: deferredActionCallback}];
    TYPO3.Notification.warning(data.eventData.data, '', 5, actions);
});

source.addEventListener('ping', (e) => console.log(JSON.parse(e.data)));
source.onerror = (e) => console.error('SSE error', e);
