const CACHE_NAME = "nehiloth-static-v1";
const STATIC_ASSETS = ["/css/style.css", "/logo/logo.jpg"];

self.addEventListener("install", event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener("activate", event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
    );
    self.clients.claim();
});

self.addEventListener("fetch", event => {
    const requestUrl = new URL(event.request.url);
    if (event.request.method !== "GET" || requestUrl.origin !== self.location.origin ||
        !STATIC_ASSETS.includes(requestUrl.pathname)) {
        return;
    }
    event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request)));
});
