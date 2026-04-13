const source = new EventSource(TYPO3.settings.ajaxUrls.collaboration_example);

source.addEventListener('clearCacheEvent', (e) => {
    const data = JSON.parse(e.data);
    TYPO3.Notification.warning(data.eventData.data);
});

source.addEventListener('ping', (e) => console.log(JSON.parse(e.data)));
source.onerror = (e) => console.error('SSE error', e);
