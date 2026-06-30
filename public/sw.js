/* AnimTrack service worker — ringan & aman untuk Livewire.
   Strategi: network-first; hanya aset statis (build/icon/css/js) yang di-cache. */
const CACHE = 'animtrack-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Biarkan POST/PUT/dll (mis. update Livewire) langsung ke jaringan.
    if (req.method !== 'GET') {
        return;
    }

    const isStatic = /\/build\/|\.(?:css|js|svg|png|jpg|jpeg|webp|woff2?)(?:\?|$)/.test(req.url);

    event.respondWith(
        fetch(req)
            .then((res) => {
                if (isStatic && res && res.status === 200) {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copy));
                }
                return res;
            })
            .catch(() => caches.match(req))
    );
});
