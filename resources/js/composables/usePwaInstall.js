import { ref, onMounted } from 'vue';

const deferredPrompt = ref(null);
const isInstallable = ref(false);
const isInstalled = ref(false);

export function usePwaInstall() {
    onMounted(() => {
        if (typeof window === 'undefined') return;

        // Detectar si ya se está ejecutando como PWA instalada
        if (
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true
        ) {
            isInstalled.value = true;
            isInstallable.value = false;
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt.value = e;
            isInstallable.value = true;
        });

        window.addEventListener('appinstalled', () => {
            isInstalled.value = true;
            isInstallable.value = false;
            deferredPrompt.value = null;
        });
    });

    async function promptInstall() {
        if (!deferredPrompt.value) {
            // Si el navegador no soporta el evento o es iOS
            return false;
        }

        try {
            deferredPrompt.value.prompt();
            const { outcome } = await deferredPrompt.value.userChoice;
            if (outcome === 'accepted') {
                isInstalled.value = true;
                isInstallable.value = false;
                deferredPrompt.value = null;
                return true;
            }
        } catch (e) {
            console.warn('[Epycus PWA] Error durante prompt de instalación:', e);
        }
        return false;
    }

    return {
        isInstallable,
        isInstalled,
        promptInstall,
    };
}
