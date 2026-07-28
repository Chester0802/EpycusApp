import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        // Sin esto Vite anuncia su dev server en [::1] (IPv6). Con eso el
        // navegador local funciona, pero cualquier entorno sin loopback
        // IPv6 configurado (sandboxes, algunos contenedores) no puede
        // resolverlo y todos los assets fallan en silencio.
        host: '127.0.0.1',
    },
});
