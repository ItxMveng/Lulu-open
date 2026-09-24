/* Service worker LULU-OPEN : coque hors ligne + cache des ressources statiques.
   Les pages HTML ne sont jamais mises en cache (comptes, messages, paiements). */
const VERSION = 'lulu-pwa-v2';
const STATIC_CACHE = VERSION + '-static';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/assets/pwa/icon-192.png', '/assets/css/app.css'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

const CDN_HOSTS = ['cdn.jsdelivr.net', 'fonts.googleapis.com', 'fonts.gstatic.com'];

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  const isStatic = (url.origin === self.location.origin && url.pathname.startsWith('/assets/')) || CDN_HOSTS.includes(url.hostname);
  if (!isStatic) return;

  event.respondWith(
    caches.open(STATIC_CACHE).then((cache) =>
      cache.match(req).then((cached) => {
        const network = fetch(req).then((res) => {
          if (res && (res.ok || res.type === 'opaque')) cache.put(req, res.clone());
          return res;
        }).catch(() => cached);
        return cached || network;
      })
    )
  );
});
