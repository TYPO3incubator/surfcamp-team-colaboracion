const source = new EventSource('https://surfcamp-team2.ddev.site/typo3/module/web/example');

source.addEventListener('customEvent', (e) => {
    console.log(e);
});

source.onerror = () => {
    // Browser reconnectet automatisch
};