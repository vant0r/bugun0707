const VPY_CACHE = 'vpy-yaypan-v1';
const VPY_OFFLINE_URLS = [
    '/',
    '/manifest.json',
    '/assets/images/favicon.svg'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(VPY_CACHE).then(function(cache) {
            return cache.addAll(VPY_OFFLINE_URLS).catch(function() {});
        }).then(function() {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(keys.map(function(k) {
                if (k !== VPY_CACHE) return caches.delete(k);
            }));
        }).then(function() {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function(event) {
    var req = event.request;
    if (req.method !== 'GET') return;
    var url = new URL(req.url);
    if (url.origin !== location.origin) return;
    if (url.pathname.indexOf('/admin/') === 0 || url.pathname.indexOf('/api/') === 0) return;

    if (req.headers.get('accept') && req.headers.get('accept').indexOf('text/html') !== -1) {
        event.respondWith(
            fetch(req).then(function(resp) {
                var clone = resp.clone();
                caches.open(VPY_CACHE).then(function(c) { c.put(req, clone); });
                return resp;
            }).catch(function() {
                return caches.match(req).then(function(cached) {
                    return cached || caches.match('/');
                });
            })
        );
        return;
    }

    event.respondWith(
        caches.match(req).then(function(cached) {
            if (cached) {
                fetch(req).then(function(resp) {
                    if (resp && resp.status === 200) {
                        var clone = resp.clone();
                        caches.open(VPY_CACHE).then(function(c) { c.put(req, clone); });
                    }
                }).catch(function() {});
                return cached;
            }
            return fetch(req).then(function(resp) {
                if (resp && resp.status === 200 && resp.type === 'basic') {
                    var clone = resp.clone();
                    caches.open(VPY_CACHE).then(function(c) { c.put(req, clone); });
                }
                return resp;
            }).catch(function() {});
        })
    );
});
