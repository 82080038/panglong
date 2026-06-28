const CACHE_NAME = 'panglong-erp-v2';
const STATIC_ASSETS = [
  '/panglong/frontend/',
  '/panglong/frontend/login.php',
  '/panglong/frontend/assets/css/bootstrap.min.css',
  '/panglong/frontend/assets/css/bootstrap-icons.css',
  '/panglong/frontend/assets/js/jquery-3.6.0.min.js',
  '/panglong/frontend/assets/js/bootstrap.bundle.min.js',
  '/panglong/frontend/assets/js/chart.umd.min.js',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    caches.match(event.request).then((cached) => {
      return cached || fetch(event.request).then((response) => {
        if (response.status === 200 && event.request.url.startsWith('http')) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => cached);
    })
  );
});
