const source = new EventSource(TYPO3.settings.ajaxUrls.collaboration_example);

source.addEventListener('customEvent', (e) => {
    console.log(e);
});

source.addEventListener('ping', (e) => console.log(JSON.parse(e.data)));
source.onerror = (e) => console.error('SSE error', e);