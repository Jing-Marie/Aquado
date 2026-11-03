// =====================================================
// AQUADO DRIVER APP - SERVICE WORKER
// Makes the Operations app installable and shows an offline
// screen when there is no connection. Pages and order data are
// never cached, so drivers always see live information.
// =====================================================

const CACHE_NAME = "aquado-driver-v1";

const OFFLINE_URL = "offline.html";

const PRECACHE = [
    OFFLINE_URL,
    "assets/logo.svg",
    "assets/icons/icon-192.png",
    "assets/icons/icon-512.png"
];


self.addEventListener("install", function(event) {

    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(PRECACHE);
        })
    );

    self.skipWaiting();

});


self.addEventListener("activate", function(event) {

    // Remove caches from older versions of the app.
    event.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(
                keys
                    .filter(function(key) { return key !== CACHE_NAME; })
                    .map(function(key) { return caches.delete(key); })
            );
        })
    );

    self.clients.claim();

});


self.addEventListener("fetch", function(event) {

    const request = event.request;

    // Pages: always from the network; offline screen when there is no connection.
    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request).catch(function() {
                return caches.match(OFFLINE_URL);
            })
        );
        return;
    }

    // App icons and logo: from the cache when available.
    if (request.method === "GET" && new URL(request.url).origin === self.location.origin) {
        event.respondWith(
            caches.match(request).then(function(cached) {
                return cached || fetch(request);
            })
        );
    }

});
