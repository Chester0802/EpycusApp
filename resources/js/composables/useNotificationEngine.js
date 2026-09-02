import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const permissionStatus = ref(
    typeof window !== 'undefined' && 'Notification' in window
        ? Notification.permission
        : 'denied'
);

const defaultSettings = {
    pomodoro: true,
    calendar: true,
    habits_streak: true,
    daily_plan: true,
    hydration: false,
    sound_enabled: true,
};

/**
 * Generador de sonido de notificación cristalino, suave y moderno (Estilo Crystal Bell)
 */
function playNotificationChime() {
    try {
        if (typeof window === 'undefined') return;
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;

        const ctx = new AudioCtx();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }

        const now = ctx.currentTime;

        // Acorde suave en dos tonos cristalinos (C6 y G6 con armónico suave)
        const notes = [
            { freq: 1046.50, time: 0, duration: 0.25, vol: 0.10 },
            { freq: 1567.98, time: 0.07, duration: 0.32, vol: 0.08 },
        ];

        notes.forEach(({ freq, time, duration, vol }) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, now + time);

            gain.gain.setValueAtTime(0.0001, now + time);
            gain.gain.linearRampToValueAtTime(vol, now + time + 0.012);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + time + duration);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now + time);
            osc.stop(now + time + duration + 0.02);
        });
    } catch (e) {
        // Silencioso si el navegador bloquea audio sin interacción previa
    }
}

export function useNotificationEngine() {
    const page = usePage();

    const isSupported = computed(() => {
        return typeof window !== 'undefined' && 'Notification' in window;
    });

    const isGranted = computed(() => permissionStatus.value === 'granted');
    const isDenied = computed(() => permissionStatus.value === 'denied');
    const isPrompt = computed(() => permissionStatus.value === 'default');

    const masterEnabled = computed(() => {
        return Boolean(page.props.preferences?.notificationsEnabled);
    });

    const settings = computed(() => {
        const userSettings = page.props.preferences?.notificationSettings;
        return {
            ...defaultSettings,
            ...(userSettings || {}),
        };
    });

    async function requestPermission() {
        if (!isSupported.value) return false;

        try {
            const result = await Notification.requestPermission();
            permissionStatus.value = result;

            if (result === 'granted') {
                // Si concede permiso y el switch maestro estaba en false, activarlo en la BD
                if (!masterEnabled.value) {
                    router.patch(route('preferences.update'), {
                        notifications_enabled: true,
                    }, {
                        preserveScroll: true,
                        preserveState: true,
                    });
                }
                return true;
            }
            return false;
        } catch (e) {
            console.warn('[Epycus Notifications] Error al solicitar permisos:', e);
            return false;
        }
    }

    function isChannelEnabled(channelKey) {
        if (!masterEnabled.value) return false;
        if (!channelKey) return true;
        return Boolean(settings.value[channelKey]);
    }

    async function sendNotification(title, { body = '', icon = '/assets/images/favicon.ico', url = '/dashboard', channel = null } = {}) {
        if (!isSupported.value) return false;
        if (permissionStatus.value !== 'granted') return false;

        // Verificar si el canal específico está habilitado
        if (channel && !isChannelEnabled(channel)) {
            return false;
        }

        // Tono de audio si está habilitado
        if (settings.value.sound_enabled) {
            playNotificationChime();
        }

        // Intentar a través de Service Worker si está registrado
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            try {
                const reg = await navigator.serviceWorker.ready;
                reg.showNotification(title, {
                    body,
                    icon,
                    badge: icon,
                    data: { url },
                    vibrate: [100, 50, 100],
                });
                return true;
            } catch (e) {
                // Fallback a Notification API tradicional
            }
        }

        try {
            const notif = new Notification(title, {
                body,
                icon,
                data: { url },
            });

            notif.onclick = () => {
                window.focus();
                if (url && typeof window !== 'undefined') {
                    router.visit(url);
                }
                notif.close();
            };
            return true;
        } catch (e) {
            console.warn('[Epycus Notifications] No se pudo enviar notificación:', e);
            return false;
        }
    }

    async function testNotification(channel = null) {
        if (permissionStatus.value !== 'granted') {
            const granted = await requestPermission();
            if (!granted) return false;
        }

        const channelNames = {
            pomodoro: 'Pomodoro y Enfoque',
            calendar: 'Calendario y Clases',
            habits_streak: 'Racha de Hábitos',
            daily_plan: 'Plan Diario',
            hydration: 'Pausa Activa e Hidratación',
        };

        const channelLabel = channelNames[channel] || 'Sistema Epycus';

        return sendNotification(`⚡ Notificación de Prueba — ${channelLabel}`, {
            body: `¡Excelente! Las alertas de Epycus están configuradas y funcionando en tu dispositivo.`,
            icon: '/assets/images/favicon.ico',
            url: '/settings',
            channel: null, // Forzar para la prueba
        });
    }

    /**
     * Revisa si hay clases próximas en los próximos 10 a 15 minutos y dispara aviso
     */
    function checkUpcomingClasses(schedules = []) {
        if (!isChannelEnabled('calendar')) return;
        if (!isGranted.value) return;
        if (!Array.isArray(schedules) || schedules.length === 0) return;

        const now = new Date();
        const jsDay = now.getDay(); // 0=Domingo, 1=Lunes, ... 6=Sábado
        const dayOfWeek = jsDay === 0 ? 7 : jsDay; // Convertir a 1=Lunes .. 7=Domingo
        const todayDateStr = now.toISOString().slice(0, 10);
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        schedules.forEach((item) => {
            if (Number(item.day_of_week) !== dayOfWeek) return;
            if (!item.start_time) return;

            const [h, m] = item.start_time.split(':').map(Number);
            const classMinutes = h * 60 + m;
            const diffMinutes = classMinutes - currentMinutes;

            // Si faltan entre 1 y 15 minutos para que empiece la clase
            if (diffMinutes > 0 && diffMinutes <= 15) {
                const alertKey = `epycus_alert_class_${item.id || item.course_name}_${todayDateStr}_${item.start_time}`;
                if (typeof window !== 'undefined' && sessionStorage.getItem(alertKey)) {
                    return; // Ya fue alertado hoy
                }

                const classroomText = item.classroom ? ` en el aula ${item.classroom}` : '';
                sendNotification(`🔔 Tu clase comienza en ${diffMinutes} min`, {
                    body: `${item.course_name || 'Clase'}${classroomText} inicia a las ${item.start_time.slice(0, 5)}.`,
                    url: '/calendar',
                    channel: 'calendar',
                });

                if (typeof window !== 'undefined') {
                    sessionStorage.setItem(alertKey, '1');
                }
            }
        });
    }

    return {
        isSupported,
        isGranted,
        isDenied,
        isPrompt,
        permissionStatus,
        masterEnabled,
        settings,
        requestPermission,
        sendNotification,
        testNotification,
        checkUpcomingClasses,
    };
}
