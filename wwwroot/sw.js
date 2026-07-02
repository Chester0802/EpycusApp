const CACHE_NAME = 'epycus-v3';
const CACHE_FIRST_ASSETS = [
  '/',
  '/lib/bootstrap.min.css',
  '/lib/bootstrap-icons.min.css',
  '/lib/bootstrap.bundle.min.js',
  '/img/logo.webp',
  '/favicon.ico',
  '/manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(CACHE_FIRST_ASSETS);
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

  const url = new URL(event.request.url);

  // Navegaciones de página completa (HTML): network-first. Antes eran cache-first, así
  // que recargar la página no volvía a pedirle nada al servidor -> el HTML (incluido el
  // <script> inline de páginas como Pomodoro) y el estado de sesión que mostraba podían
  // quedarse desactualizados indefinidamente, incluso después de desplegar un fix o de que
  // la sesión real hubiera expirado en el servidor. Solo cae a cache si no hay red.
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          if (response && response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              try { cache.put(event.request, clone); } catch (e) {}
            });
          }
          return response;
        })
        .catch(() => caches.match(event.request).then(cached => cached || caches.match('/')))
    );
    return;
  }

  // CSS/JS: network-first (always get latest, fall back to cache offline)
  if (url.pathname.match(/\.(css|js)(\?.*)?$/)) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          if (response && response.status === 200 && url.origin === self.location.origin) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              try { cache.put(event.request, clone); } catch(e) {}
            });
          }
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Everything else: cache-first for speed, update in background
  event.respondWith(
    caches.match(event.request).then(cached => {
      const fetchPromise = fetch(event.request).then(response => {
        if (response && response.status === 200 && url.origin === self.location.origin) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            try { cache.put(event.request, clone); } catch(e) {}
          });
        }
        return response;
      }).catch(() => {});
      return cached || fetchPromise;
    }).catch(() => {
      if (event.request.mode === 'navigate') {
        return caches.match('/');
      }
      return new Response('Offline', { status: 503 });
    })
  );
});
