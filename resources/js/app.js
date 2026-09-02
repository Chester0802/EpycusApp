import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// Bloqueo total para iframes o ventanas de previsualización (about:srcdoc / widgets)
let isFramed = false;
try {
    isFramed = typeof window !== 'undefined' && window.self !== window.top;
} catch {
    isFramed = true;
}

if (!isFramed) {
    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

    router.on('invalid', (event) => {
        event.preventDefault();
        const response = event.detail.response;
        const targetUrl = response?.config?.url || window.location.href;
        console.warn('[Epycus SPA] Respuesta no válida interceptada. Redirigiendo limpiamente a:', targetUrl);
        window.location.href = targetUrl;
    });

    router.on('exception', (event) => {
        event.preventDefault();
        console.warn('[Epycus SPA] Excepción de navegación interceptada:', event.detail.error);
    });

    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue'),
            ),
        setup({ el, App, props, plugin }) {
            return createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .mount(el);
        },
        progress: {
            color: '#4B5563',
        },
    });
}

/**
 * Fix para bfcache en móvil:
 * Cuando el navegador restaura la página desde el Back-Forward Cache
 * (evento pageshow con persisted=true), forzar un reload limpio.
 */
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});

/**
 * Registro de Service Worker para capacidades PWA y Notificaciones
 */
if (typeof window !== 'undefined' && 'serviceWorker' in navigator && !isFramed) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('[Epycus PWA] No se pudo registrar el Service Worker:', err);
        });
    });
}
