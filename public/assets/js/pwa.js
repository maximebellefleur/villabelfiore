/* Rooted — PWA / Service Worker registration */
(function () {

    // Register service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register((window.APP_BASE || '') + '/sw.js').then(function (reg) {
                console.log('[Rooted] SW registered, scope:', reg.scope);
            }).catch(function (err) {
                console.warn('[Rooted] SW registration failed:', err);
            });
        });
    }

    // Online / offline status
    var $body = document.body;

    function updateStatus() {
        if (navigator.onLine) {
            $body.classList.remove('offline');
            // Attempt to process queued sync items
            fetch((window.APP_BASE || '') + '/sync/status').then(function (r) { return r.json(); }).then(function (data) {
                if (data.data && data.data.pending > 0) {
                    document.getElementById('syncStatus') &&
                        (document.getElementById('syncStatus').style.display = 'block');
                }
            }).catch(function () {});
        } else {
            $body.classList.add('offline');
        }
    }

    window.addEventListener('online',  updateStatus);
    window.addEventListener('offline', updateStatus);
    updateStatus();

}());
