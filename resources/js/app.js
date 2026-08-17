import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
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

if (isFramed) {
    if (typeof document !== 'undefined' && document.documentElement) {
        document.documentElement.style.display = 'none';
        document.documentElement.innerHTML = '';
    }
    throw new Error('Execution prevented inside iframe preview.');
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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

/**
 * Fix para bfcache en móvil:
 * Cuando el navegador restaura la página desde el Back-Forward Cache
 * (evento pageshow con persisted=true), Vue y el runtime de Inertia
 * ya no están activos. Si el usuario navega, el servidor devuelve JSON
 * de Inertia pero no hay nada que lo consuma → se muestra como texto plano.
 * Solución: forzar un reload completo al detectar restauración desde bfcache.
 */
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});
