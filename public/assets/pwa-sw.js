'use strict';

const cacheName = 'hypotenuse-pwa-' + (self.PWA_CACHE_VERSION || '3');
const offlinePage = '/offline.html';

self.addEventListener('install', event => {
    event.waitUntil(caches.open(cacheName).then(cache =>
        cache.add(new Request(new URL(offlinePage, self.location.origin), { credentials: 'omit' }))
    ));
});

self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(
        keys.filter(key => key.startsWith('hypotenuse-pwa-') && key !== cacheName)
            .map(key => caches.delete(key))
    )).then(() => self.clients.claim()));
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    // HTML and user-specific routes are always fetched from the server.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(offlinePage)));
        return;
    }

    // Cache only static assets; uploaded questions, PDFs and Livewire are excluded.
    if (!/^\/(assets|backend\/assets|icons)\//.test(url.pathname)
        || !/\.(css|js|png|jpe?g|webp|gif|ico|woff2?|ttf|svg)$/i.test(url.pathname)) return;

    event.respondWith((async () => {
        let cache;
        let cached;
        try {
            cache = await caches.open(cacheName);
            cached = await cache.match(request);
        } catch (error) {
            // Storage may be unavailable; online assets must still load.
        }
        if (cached) return cached;
        const response = await fetch(request);
        if (cache && response.ok && !response.redirected
            && !response.headers.get('content-type')?.includes('text/html')
            && !/private|no-store/i.test(response.headers.get('cache-control') || '')) {
            try {
                await cache.put(request, response.clone());
            } catch (error) {
                // Full/disabled cache must not turn a successful request into an error.
            }
        }
        return response;
    })());
});
