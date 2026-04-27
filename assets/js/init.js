// Check if the browser supports service workers
const initScriptUrl = document.currentScript ? document.currentScript.src : '';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        const appRoot = initScriptUrl
            ? new URL('../..', initScriptUrl)
            : new URL('./', window.location.href);
        const serviceWorkerUrl = new URL('sw.js', appRoot);

        navigator.serviceWorker
            .register(serviceWorkerUrl.pathname)
            .then(function (registration) {
                console.log('Service Worker registered:', registration.scope);
            })
            .catch(function (error) {
                console.error('Service Worker registration failed:', error);
            });
    });
}
