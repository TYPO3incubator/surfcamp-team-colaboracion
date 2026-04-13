const source = new EventSource(TYPO3.settings.ajaxUrls.collaboration_example);

source.addEventListener('customEvent', (e) => {
    console.log(e);
});

source.onerror = () => {
    // Browser reconnectet automatisch
};