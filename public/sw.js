/* Rooted — Service Worker v2 */

var CACHE_NAME = 'rooted-v2';
var OFFLINE_URL = '/offline';

var SHELL_ASSETS = [
    '/assets/css/app.css',
    '/assets/css/pwa.css',
    '/assets/js/app.js',
    '/assets/js/pwa.js',
    '/assets/js/gps.js',
    '/assets/images/icon-192.png',
    '/assets/images/icon-512.png',
    '/assets/images/apple-touch-icon.png',
    '/manifest.json',
];

// Install: cache shell assets + offline page
self.addEventListener('install', function (event) {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            // Cache offline page first (must succeed), then shell assets best-effort
            return cache.add(OFFLINE_URL).then(function () {
                return cache.addAll(SHELL_ASSETS).catch(function (err) {
                    console.warn('[SW] Failed to cache some assets:', err);
                });
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
    if (event.request.method !== 'GET') return;

    // API routes: network first, no cache fallback
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

    // Static assets + manifest: cache first, network fallback, update cache
    if (
        url.pathname.startsWith('/assets/') ||
        url.pathname === '/manifest.json'
    ) {
        event.respondWith(
            caches.match(event.request).then(function (cached) {
                var networkFetch = fetch(event.request).then(function (resp) {
                    if (resp && resp.status === 200) {
                        var clone = resp.clone();
                        caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, clone); });
                    }
                    return resp;
                });
                return cached || networkFetch;
            })
        );
        return;
    }

    // HTML pages: network first, cache fallback, offline page last resort
    event.respondWith(
        fetch(event.request).then(function (resp) {
            if (resp && resp.status === 200) {
                var clone = resp.clone();
                caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, clone); });
            }
            return resp;
        }).catch(function () {
            return caches.match(event.request).then(function (cached) {
                return cached || caches.match(OFFLINE_URL);
            });
        })
    );
});
