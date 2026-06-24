const CACHE_NAME = 'epycus-v1';
const STATIC_ASSETS = [
  '/',
  '/lib/bootstrap.min.css',
  '/lib/bootstrap-icons.min.css',
  '/lib/bootstrap.bundle.min.js',
  '/css/variables.css',
  '/css/epycus-modern.css',
  '/css/dashboard.css',
  '/css/site.css',
  '/css/notificaciones.css',
  '/css/temas/tema-noche-epica.css',
  '/js/theme-manager.js',
  '/js/site.js',
  '/js/notificaciones.js',
  '/img/logo.webp',
  '/favicon.ico',
  '/manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;

      return fetch(event.request).then(response => {
        if (response && response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, clone);
          });
        }
        return response;
      }).catch(() => {
        if (event.request.mode === 'navigate') {
          return caches.match('/');
        }
        return new Response('Offline', { status: 503 });
      });
    })
  );
});
