import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

/**
 * Tres ejes independientes — nunca acoplados entre sí:
 * - Eje A (superficie): docs/04-DISENO-VISUAL.md §1 — neumorfismo | vidrio
 * - Eje B (tema): claro | oscuro
 * - Eje C (paleta): docs/04-DISENO-VISUAL.md §3 — kawaii | bosque
 *
 * `surface` es el único de los tres que hoy vive en el backend
 * (`user_preferences.surface_mode`, ver docs/05-BASE-DATOS.md). `theme` y
 * `palette` todavía no tienen columna propia — el usuario nunca confirmó
 * que deban ser preferencia de cuenta y no de dispositivo, así que por
 * ahora solo se persisten en localStorage. Si eso cambia, agregar la
 * columna y sincronizarla igual que surfaceMode aquí abajo — no antes.
 */
const theme = ref(document.documentElement.getAttribute('data-theme') || 'light');
const surface = ref(document.documentElement.getAttribute('data-surface') || 'neumorphism');
const palette = ref(document.documentElement.getAttribute('data-palette') || 'nube');

function safeSet(key, value) {
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            window.localStorage.setItem(key, value);
        }
    } catch (e) {}
}

function applyTheme(value) {
    theme.value = value;
    document.documentElement.setAttribute('data-theme', value);
    safeSet('epycus.theme', value);
}

function applySurface(value) {
    surface.value = value;
    document.documentElement.setAttribute('data-surface', value);
    safeSet('epycus.surface', value);
}

function applyPalette(value) {
    palette.value = value;
    document.documentElement.setAttribute('data-palette', value);
    safeSet('epycus.palette', value);
}


/**
 * Degradación en gama baja — skill epycus-ui §6. Un dispositivo de gama
 * baja no debería pagar el costo de backdrop-filter en modo vidrio.
 */
function detectLowEndDevice() {
    const lowEnd =
        (navigator.hardwareConcurrency ?? 8) <= 4 ||
        (navigator.deviceMemory ?? 8) <= 4 ||
        !(window.CSS && CSS.supports('backdrop-filter', 'blur(1px)'));

    if (lowEnd) {
        document.documentElement.setAttribute('data-glass-fallback', 'true');
    }
}

detectLowEndDevice();

const wallpaperFileMap = {
    Fondo_1: 'Fondo_1.avif',
    atardecer: 'Fondo_1.avif',
    Fondo_2: 'Fondo_2.jpg',
    Fondo_3: 'Fondo_3.jpg',
    Fondo_4: 'Fondo_4.png',
    Fondo_5: 'Fondo_5.jpg',
    Fondo_6: 'Fondo_6.jpg',
    Fondo_7: 'Fondo_7.jpeg',
    Fondo_8: 'Fondo_8.jpg',
    Fondo_9: 'Fondo_9.jpg',
};

function applyWallpaper(key) {
    if (!key) return;
    let file = wallpaperFileMap[key];
    if (!file) {
        try {
            const page = usePage();
            const catalog = page.props?.wallpapers || [];
            const found = catalog.find((w) => w.key === key);
            if (found) {
                file = found.file;
            }
        } catch (e) {
            // Silencioso en SSR / fuera de contexto
        }
    }
    if (!file) {
        file = `${key}.jpg`;
    }
    document.documentElement.style.setProperty(
        '--user-wallpaper',
        `url('/assets/wallpapers/full/${file}')`,
    );
}

let syncedFromServer = false;

export function useTheme() {
    const page = usePage();

    if (page.props.preferences) {
        if (!syncedFromServer) {
            syncedFromServer = true;
            applySurface(page.props.preferences.surfaceMode);
        }
        if (page.props.preferences.wallpaperKey) {
            applyWallpaper(page.props.preferences.wallpaperKey);
        }
    }

    function setTheme(value) {
        applyTheme(value);
    }

    function setSurface(value) {
        applySurface(value);

        if (page.props.auth?.user) {
            router.patch(
                '/preferences',
                { surface_mode: value },
                { preserveScroll: true, preserveState: true, only: ['preferences'] },
            );
        }
    }

    function setPalette(value) {
        applyPalette(value);
    }

    function setWallpaper(key) {
        applyWallpaper(key);
    }

    return { theme, surface, palette, setTheme, setSurface, setPalette, setWallpaper };
}
