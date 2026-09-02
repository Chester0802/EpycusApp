const CACHE_NAME = 'epycus-cache-v1';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/assets/images/favicon.ico',
    '/assets/images/logo.webp',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[Epycus SW] Error precaching static assets:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    // Solo cachear peticiones GET
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Estrategia Cache-First para fuentes e imágenes estáticas
    if (url.pathname.startsWith('/fonts/') || url.pathname.startsWith('/assets/images/')) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => caches.match(event.request));
            })
        );
        return;
    }

    // Estrategia Network-First para el resto de navegación
    event.respondWith(
        fetch(event.request).catch(async () => {
            const cached = await caches.match(event.request);
            if (cached) {
                return cached;
            }
            if (event.request.mode === 'navigate') {
                const fallback = await caches.match('/');
                if (fallback) {
                    return fallback;
                }
            }
            return new Response('Offline o error de red', {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'text/plain; charset=utf-8' },
            });
        })
    );
});

// Manejo de eventos de Notificación (Click en la notificación)
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// Manejo de eventos Push en segundo plano
self.addEventListener('push', (event) => {
    let data = {
        title: 'Epycus',
        body: 'Tienes una actualización importante.',
        icon: '/assets/images/favicon.ico',
        url: '/dashboard',
    };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/assets/images/favicon.ico',
        badge: '/assets/images/favicon.ico',
        data: { url: data.url || '/dashboard' },
        vibrate: [100, 50, 100],
    };

    event.waitUntil(self.registration.showNotification(data.title || 'Epycus', options));
});
