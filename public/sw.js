/* Rooted — Service Worker v1 */

var CACHE_NAME = 'rooted-v1';
var OFFLINE_URL = '/dashboard';

var SHELL_ASSETS = [
    '/assets/css/app.css',
    '/assets/css/pwa.css',
    '/assets/js/app.js',
    '/assets/js/pwa.js',
    '/assets/js/items.js',
    '/assets/js/dashboard.js',
    '/assets/js/sync.js',
];

// Install: cache shell assets
self.addEventListener('install', function (event) {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(SHELL_ASSETS).catch(function (err) {
                console.warn('[SW] Failed to cache some assets:', err);
            });
        })
    );
});

// Activate: remove old caches
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// Fetch strategy
self.addEventListener('fetch', function (event) {
    var url = new URL(event.request.url);

    // Skip cross-origin and non-GET for caching
    if (url.origin !== self.location.origin) return;

    // API routes: network first, no cache
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(event.request).catch(function () {
                return new Response(JSON.stringify({ success: false, message: 'Offline' }), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // Static assets: cache first
    if (
        url.pathname.startsWith('/assets/') ||
        url.pathname === '/manifest.json'
    ) {
        event.respondWith(
            caches.match(event.request).then(function (cached) {
                return cached || fetch(event.request).then(function (resp) {
                    var clone = resp.clone();
                    caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, clone); });
                    return resp;
                });
            })
        );
        return;
    }

    // Everything else: network first, fallback to cache
    event.respondWith(
        fetch(event.request).then(function (resp) {
            var clone = resp.clone();
            caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, clone); });
            return resp;
        }).catch(function () {
            return caches.match(event.request).then(function (cached) {
                return cached || caches.match(OFFLINE_URL);
            });
        })
    );
});
