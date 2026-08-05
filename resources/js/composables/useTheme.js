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
const palette = ref(document.documentElement.getAttribute('data-palette') || 'kawaii');

function applyTheme(value) {
    theme.value = value;
    document.documentElement.setAttribute('data-theme', value);
    localStorage.setItem('epycus.theme', value);
}

function applySurface(value) {
    surface.value = value;
    document.documentElement.setAttribute('data-surface', value);
    localStorage.setItem('epycus.surface', value);

    /*
     * Vidrio no tiene claro/oscuro propios — el fondo de pantalla es una
     * foto fija (docs/04-DISENO-VISUAL.md §11) y una foto de atardecer con
     * cromo claro, o una de noche con cromo claro, se ve mal (feedback
     * directo del usuario tras ver capturas reales). Por eso vidrio fuerza
     * oscuro y el selector de tema se oculta en la interfaz (ThemeToggle,
     * ver AppLayout.vue) — no tiene sentido ofrecer un control que solo
     * empeora el resultado.
     */
    if (value === 'glass' && theme.value !== 'dark') {
        applyTheme('dark');
    }
}

// Corrige un estado inconsistente guardado antes de esta regla (ej.
// localStorage con surface=glass y theme=light de una sesión anterior).
if (surface.value === 'glass' && theme.value !== 'dark') {
    applyTheme('dark');
}

function applyPalette(value) {
    palette.value = value;
    document.documentElement.setAttribute('data-palette', value);
    localStorage.setItem('epycus.palette', value);
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
    atardecer: 'atardecer.avif',
    chica_anime: 'chica_anime.jpg',
    claro_bts: 'claro_bts.jpg',
    dragon_ball: 'dragon_ball.png',
    anime_morado: 'anime_morado.jpg',
    lofi_naturaleza: 'lofi_naturaleza.jpg',
    gris_pinguino: 'gris_pinguino.jpeg',
    verde_cactus: 'verde_cactus.jpg',
    lofi_gato: 'lofi_gato.jpg',
};

function applyWallpaper(key) {
    const file = wallpaperFileMap[key] || 'atardecer.avif';
    document.documentElement.style.setProperty('--user-wallpaper', `url('/assets/wallpapers/full/${file}')`);
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
