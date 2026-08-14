<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';

const props = defineProps({
    activeSession: { type: Object, default: null },
    autoCompletedFocusMinutes: { type: Number, default: null },
    autoCompletedXp: { type: Number, default: null },
    todaySessions: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
    avatarStyle: { type: String, default: 'base' },
    avatarGender: { type: String, default: 'm' },
    progress: { type: Object, default: () => ({ phase: 1 }) },
    missions: { type: Array, default: () => [] },
});

const selectedMissionId = ref(null);
const expandedMissionId = ref(null);
const subtaskBusy = ref({});

function selectMission(id) {
    selectedMissionId.value = selectedMissionId.value === id ? null : id;
}

function toggleExpandMission(id) {
    expandedMissionId.value = expandedMissionId.value === id ? null : id;
}

async function toggleSubtask(missionId, subtask) {
    subtaskBusy.value[subtask.id] = true;
    try {
        const res = await fetch(
            route('missions.subtasks.toggle', { id: missionId, subtaskId: subtask.id }),
            {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': csrfHeader() },
            },
        );
        if (res.ok) {
            subtask.is_completed = !subtask.is_completed;
        }
    } finally {
        subtaskBusy.value[subtask.id] = false;
    }
}

/*
 * Temporizador en el CLIENTE, sincronizado con el servidor solo en cada
 * transición (iniciar/pausar/reanudar/completar/abandonar) — decisión ya
 * tomada en docs/01-MODULOS.md §3: con 1 núcleo de CPU, un timer del lado
 * del servidor obligaría a pollear a todos los usuarios a la vez.
 *
 * "Cerré el navegador con un Pomodoro corriendo" (el caso que pidió el
 * usuario que se manejara bien) se resuelve en DOS lugares distintos:
 * 1. Si cerró la pestaña/navegador entero: al volver a `/pomodoro`, el
 *    backend (`ResolveStaleSessionUseCase`, ver PomodoroController) ya
 *    completó la sesión vieja antes de que esta página cargue — acá solo
 *    se muestra el aviso (`autoCompletedFocusMinutes`).
 * 2. Si la pestaña se quedó abierta pero en segundo plano (cambió de app,
 *    volvió después): el intervalo de abajo sigue corriendo (o se
 *    congela y se retoma al volver — cualquiera de los dos casos se
 *    autocorrige, porque el cálculo siempre parte de marcas de tiempo
 *    absolutas, nunca de un contador que se incrementa a ciegas) y llama
 *    a completar solo cuando el tiempo restante real llega a 0.
 */

const page = usePage();

const session = ref(props.activeSession);
const remainingSeconds = ref(0);
const busy = ref(false);
const toast = ref(
    props.autoCompletedFocusMinutes !== null
        ? `Tu Pomodoro anterior se completó mientras no estabas: ${props.autoCompletedFocusMinutes} min de foco, +${props.autoCompletedXp ?? 0} XP.`
        : null,
);

/*
 * Reglas de foco/descanso — investigadas explícitamente a pedido del
 * usuario ("investiga acerca el pomodoro"), no inventadas:
 * - Cirillo (técnica clásica): 25 min foco / 5 min descanso → ratio 20%.
 * - Variantes documentadas y usadas en la práctica: 50/10 (20%),
 *   52/17 (~33%, estudio DeskTime 2014), 90/20 (~22%).
 * - Ninguna variante conocida supera ~35% de descanso respecto al foco.
 * Por eso el tope duro es 40%: cubre todas las variantes reales con
 * margen y bloquea combinaciones sin sentido como "10 min de foco, 20 de
 * descanso" (200%) — el caso que el usuario pidió explícitamente que no
 * se pudiera elegir. `docs/01-MODULOS.md §3` documenta esto también.
 */
const FOCUS_OPTIONS = [15, 20, 25, 30, 40, 50];
const BREAK_MINUTES_ALL = [3, 5, 10, 15, 20];
const BREAK_RATIO_MAX = 0.4;
const LONG_BREAK_EVERY = 4; // técnica clásica: descanso largo cada 4 ciclos de foco seguidos

function maxBreakForFocus(focusMinutes) {
    // Redondeado, con piso de 3 (la opción más chica) para que la lista
    // nunca quede vacía — con foco mínimo (15) da 6, que ya deja pasar 3 y 5.
    return Math.max(3, Math.round(focusMinutes * BREAK_RATIO_MAX));
}

const plannedMinutesInput = ref(25);
const durationOptions = FOCUS_OPTIONS.map((n) => ({ value: n, label: `${n} min` }));

const breakMinutesInput = ref(5);
const availableBreakMinutes = computed(() =>
    BREAK_MINUTES_ALL.filter((n) => n <= maxBreakForFocus(plannedMinutesInput.value)),
);
const breakDurationOptions = computed(() =>
    availableBreakMinutes.value.map((n) => ({ value: n, label: `${n} min` })),
);
const breakHint = computed(() => {
    const max = maxBreakForFocus(plannedMinutesInput.value);
    return `Descanso corto: máx. ${max} min para ${plannedMinutesInput.value} min de foco (regla anti-abuso: nunca más del 40% del foco). Cada ${LONG_BREAK_EVERY}.º ciclo el descanso se alarga solo.`;
});

// Si el foco cambia y el descanso elegido deja de ser válido, se ajusta al
// mayor valor todavía permitido — nunca se deja un estado imposible en pantalla.
watch(plannedMinutesInput, () => {
    if (!availableBreakMinutes.value.includes(breakMinutesInput.value)) {
        breakMinutesInput.value = availableBreakMinutes.value.at(-1);
    }
});

const longBreakMinutes = computed(() => Math.min(30, Math.max(15, breakMinutesInput.value * 3)));

function formatMinutesLabel(totalMinutes) {
    const min = Math.max(0, Math.round(totalMinutes));
    if (min < 60) return `${min} min`;
    const h = Math.floor(min / 60);
    const m = min % 60;
    return m === 0 ? `${h} h` : `${h} h ${m} min`;
}

/*
 * Meta de estudio del día (pedido explícito: "quiero estudiar 4 horas,
 * elijo foco y descanso"). Es puramente de orientación en el cliente — no
 * crea ninguna sesión propia ni se manda al servidor, se compara siempre
 * contra `todaySessions` (verdad del servidor) para saber cuánto foco real
 * ya se hizo hoy. `0` es el valor centinela de "Sin meta" (un <select>
 * nativo no maneja bien `null` como value, y 0 minutos de meta no tiene
 * sentido de todas formas).
 */
const GOAL_OPTIONS = [
    { value: 0, label: 'Sin meta' },
    { value: 60, label: '1 hora' },
    { value: 90, label: '1.5 horas' },
    { value: 120, label: '2 horas' },
    { value: 180, label: '3 horas' },
    { value: 240, label: '4 horas' },
    { value: 300, label: '5 horas' },
    { value: 360, label: '6 horas' },
];
const goalMinutesInput = ref(0);
const hasGoal = computed(() => goalMinutesInput.value > 0);
const goalJustCompleted = ref(false);

// Clave por usuario + día: la meta de "hoy" no debería seguir apareciendo
// mañana como si fuera la de hoy. Se guarda en localStorage porque no es
// dato de investigación ni necesita sobrevivir a un cambio de navegador —
// si se pierde, el usuario la vuelve a elegir en dos clics.
const todayIso = new Date().toISOString().slice(0, 10);
const goalStorageKey = computed(
    () => `epycus:pomodoro:goal:${page.props.auth?.user?.id ?? 'anon'}:${todayIso}`,
);

watch(goalMinutesInput, (value) => {
    if (value > 0) localStorage.setItem(goalStorageKey.value, String(value));
    else localStorage.removeItem(goalStorageKey.value);
});

const todayCompletedSessions = computed(() =>
    props.todaySessions.filter((s) => s.status === 'completed'),
);
const todayCompletedCount = computed(() => todayCompletedSessions.value.length);
const todayFocusMinutes = computed(() =>
    todayCompletedSessions.value.reduce((sum, s) => sum + (s.focus_minutes ?? 0), 0),
);

// Minutos de la sesión EN CURSO (todavía no está en `todaySessions`, que solo
// se actualiza tras completar). Deriva de `remainingSeconds`, que ya se
// recalcula cada segundo — así el progreso de la meta se ve avanzar en vivo,
// no solo al terminar cada ciclo.
const liveFocusMinutes = computed(() => {
    if (!session.value || onBreak.value) return 0;
    const total = session.value.planned_minutes * 60;
    return Math.max(0, Math.floor((total - remainingSeconds.value) / 60));
});
const totalFocusMinutesToday = computed(() => todayFocusMinutes.value + liveFocusMinutes.value);
const goalProgressPercent = computed(() =>
    hasGoal.value
        ? Math.min(100, Math.round((totalFocusMinutesToday.value / goalMinutesInput.value) * 100))
        : 0,
);

// Cuántas sesiones de foco haría falta iniciar para llegar a la meta, con
// el foco elegido ahora mismo — informativo, no bloquea nada. Se compara
// contra el tope de 8 sesiones con XP/día (docs/01-MODULOS.md §3) para
// avisar sin sorpresas, no para impedir que el usuario estudie más.
const projectedSessionsForGoal = computed(() =>
    hasGoal.value ? Math.ceil(goalMinutesInput.value / plannedMinutesInput.value) : 0,
);
const exceedsDailyXpCap = computed(
    () => hasGoal.value && todayCompletedCount.value + projectedSessionsForGoal.value > 8,
);

function isLongBreakCycle(cycleNumber) {
    return cycleNumber % LONG_BREAK_EVERY === 0;
}

/*
 * La Técnica Pomodoro de verdad (corrección del usuario: lo anterior era
 * solo un temporizador de una sola vez, no el ciclo foco→descanso→foco).
 * El descanso es puramente del lado del cliente — no crea otra
 * `pomodoro_sessions` ni otorga XP, porque docs/01-MODULOS.md §3 no define
 * ninguna regla de dominio para el descanso (solo aparece como el segundo
 * número de la pareja "25 min foco / 5 min descanso"). Consecuencia
 * aceptada a propósito: si se cierra el navegador *durante* un descanso,
 * se pierde ese descanso en particular al volver (no el progreso de foco,
 * que sigue siendo 100% del servidor) — aceptable porque un descanso
 * perdido no tiene ningún costo real, a diferencia de un foco perdido.
 */
const onBreak = ref(false);
const onBreakIsLong = ref(false);
const breakTotalSeconds = ref(0);
const breakRemainingSeconds = ref(0);
let breakTimer = null;

function playChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.25, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.7);
        osc.start();
        osc.stop(ctx.currentTime + 0.7);
    } catch {
        // Algunos navegadores exigen un gesto del usuario antes de crear
        // un AudioContext — si falla, no rompe el ciclo, solo no suena.
    }
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
}

function stopBreakTicker() {
    if (breakTimer) {
        clearInterval(breakTimer);
        breakTimer = null;
    }
}

function startBreak(isLong) {
    onBreak.value = true;
    onBreakIsLong.value = isLong;
    breakTotalSeconds.value = (isLong ? longBreakMinutes.value : breakMinutesInput.value) * 60;
    breakRemainingSeconds.value = breakTotalSeconds.value;
    playChime();
    stopBreakTicker();
    breakTimer = setInterval(() => {
        breakRemainingSeconds.value -= 1;
        if (breakRemainingSeconds.value <= 0) {
            stopBreakTicker();
            onBreak.value = false;
            playChime();
            // Si hay meta y ya se llegó (con el foco YA acreditado por el
            // servidor tras el `router.reload` disparado al completar), no
            // se arranca otro foco solo — se corta el ciclo automático y se
            // muestra el estado de "meta cumplida". Sin meta, sigue como
            // siempre: foco → descanso → foco, sin que el usuario toque nada.
            if (hasGoal.value && todayFocusMinutes.value >= goalMinutesInput.value) {
                goalJustCompleted.value = true;
            } else {
                startSession(); // "y otra vez a enfoque" — el ciclo sigue solo
            }
        }
    }, 1000);
}

function skipBreak() {
    stopBreakTicker();
    onBreak.value = false;
    startSession();
}

let clockOffsetMs = 0;
let tickTimer = null;
let completing = false;

function syncClock(serverNowIso) {
    clockOffsetMs = new Date(serverNowIso).getTime() - Date.now();
}

function correctedNowMs() {
    return Date.now() + clockOffsetMs;
}

function elapsedActiveSeconds(s) {
    if (!s) return 0;
    const startedAtMs = new Date(s.started_at).getTime();
    const referenceEndMs =
        s.status === 'paused' && s.paused_at ? new Date(s.paused_at).getTime() : correctedNowMs();
    const rawSeconds = Math.floor((referenceEndMs - startedAtMs) / 1000);
    return Math.max(0, rawSeconds - s.total_paused_seconds);
}

function recomputeRemaining() {
    if (!session.value) {
        remainingSeconds.value = 0;
        return;
    }
    const total = session.value.planned_minutes * 60;
    remainingSeconds.value = Math.max(0, total - elapsedActiveSeconds(session.value));

    if (remainingSeconds.value === 0 && session.value.status === 'running' && !completing) {
        completeSession();
    }
}

function formatTime(totalSeconds) {
    const m = Math.floor(totalSeconds / 60)
        .toString()
        .padStart(2, '0');
    const s = Math.floor(totalSeconds % 60)
        .toString()
        .padStart(2, '0');
    return `${m}:${s}`;
}

const timeLabel = computed(() => formatTime(remainingSeconds.value));

function statusLabel(status) {
    return (
        {
            completed: 'completada',
            abandoned: 'abandonada',
            running: 'en curso',
            paused: 'en pausa',
        }[status] ?? status
    );
}
const progressPercent = computed(() => {
    if (!session.value) return 0;
    const total = session.value.planned_minutes * 60;
    return Math.min(100, Math.round((elapsedActiveSeconds(session.value) / total) * 100));
});

// Círculo que se va "cerrando" a medida que pasa el tiempo (pedido del
// usuario): el trazo completo (circunferencia) se dibuja entero al
// empezar, y `stroke-dashoffset` va creciendo con el % transcurrido, así
// que la parte visible se va achicando en vez de llenando.
const CIRCLE_RADIUS = 90;
const CIRCLE_CIRCUMFERENCE = 2 * Math.PI * CIRCLE_RADIUS;
const dashOffset = computed(() => CIRCLE_CIRCUMFERENCE * (progressPercent.value / 100));
const breakDashOffset = computed(() => {
    const total = breakTotalSeconds.value || 1;
    const elapsed = total - breakRemainingSeconds.value;
    return CIRCLE_CIRCUMFERENCE * Math.min(1, Math.max(0, elapsed / total));
});

function applySession(data) {
    session.value = data;
    syncClock(data.server_now);
    recomputeRemaining();
}

function startTicker() {
    if (tickTimer) return;
    tickTimer = setInterval(recomputeRemaining, 1000);
}

function stopTicker() {
    if (tickTimer) {
        clearInterval(tickTimer);
        tickTimer = null;
    }
}

/*
 * Rutas de Pomodoro SÍ exigen CSRF (a diferencia de la de Telemetría, que
 * está excluida en bootstrap/app.php) — sin esta cabecera, cada fetch()
 * daba "CSRF token mismatch" (probado en navegador, no asumido). Mismo
 * mecanismo que useTelemetry.js: se lee la cookie XSRF-TOKEN tal cual
 * (todavía cifrada) y se manda de vuelta como X-XSRF-TOKEN; Laravel la
 * descifra del lado del servidor.
 */
function csrfHeader() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function startSession() {
    goalJustCompleted.value = false;
    busy.value = true;
    toast.value = null;
    try {
        const payload = { planned_minutes: plannedMinutesInput.value };
        if (selectedMissionId.value) {
            payload.mission_id = selectedMissionId.value;
        }
        const res = await fetch(route('pomodoro.start'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            toast.value = body.message || 'No se pudo iniciar la sesión.';
            return;
        }
        applySession(await res.json());
        startTicker();
    } finally {
        busy.value = false;
    }
}

async function callAction(action) {
    if (!session.value) return;
    busy.value = true;
    try {
        const res = await fetch(route(`pomodoro.${action}`, { id: session.value.id }), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': csrfHeader() },
        });
        const body = await res.json().catch(() => ({}));
        if (!res.ok) {
            toast.value = body.message || 'No se pudo completar la acción.';
            return;
        }
        if (action === 'complete' || action === 'abandon') {
            stopTicker();
            // El ciclo que se acaba de completar, calculado ANTES de pedir el
            // reload — `todayCompletedCount` todavía refleja el estado previo
            // acá (el reload es async), así que "+1" es exactamente el número
            // de ciclo que corresponde al descanso que está por arrancar.
            const cycleJustCompleted = action === 'complete' ? todayCompletedCount.value + 1 : null;
            session.value = null;
            router.reload({ only: ['todaySessions', 'stats'] });
            if (action === 'complete') {
                toast.value = `¡Foco completado! +${body.xp_awarded ?? 0} XP. Empieza el descanso.`;
                startBreak(isLongBreakCycle(cycleJustCompleted));
            }
        } else {
            applySession(body);
        }
    } finally {
        busy.value = false;
    }
}

async function completeSession() {
    if (completing) return;
    completing = true;
    await callAction('complete');
    completing = false;
}

function continueStudying() {
    goalJustCompleted.value = false;
    startSession();
}

function chooseAnotherGoal() {
    goalJustCompleted.value = false;
    goalMinutesInput.value = 0;
}

/*
 * Música de fondo — YouTube, opcional, 100% a pedido del usuario ("boton
 * que diga al usuario... si no quiere, no presiona"). Nunca se carga sola:
 * el <iframe> ni siquiera existe en el DOM hasta que `musicVisible` es
 * true, y eso solo pasa con un clic explícito.
 *
 * Dominio `youtube-nocookie.com` (modo "sin cookies" de YouTube) en vez de
 * `youtube.com`, y advertencia visible antes del reproductor: este
 * proyecto ya tiene precedente documentado (docs/06-SEGURIDAD.md §7) de
 * evitar que servicios de Google registren la IP de los participantes sin
 * que esté declarado en el consentimiento informado — activar la música
 * SÍ genera ese tráfico hacia Google mientras está encendida, así que se
 * avisa en la propia pantalla en vez de ocultarlo. No se recuerda el
 * "encendido" entre visitas (si se recordara, se cargaría solo en la
 * próxima visita sin un clic nuevo, que es justo lo que se quiere evitar);
 * solo se recuerda qué playlist eligió, para no tener que pegarla de nuevo.
 */
const DEFAULT_PLAYLIST_ID = 'PLfP6i5T0-DkIMLNRwmJpRBs4PJvxfgwBg'; // "Lofi Music (No Copyright)" — canal BreakingCopyright, verificado por oEmbed el 2026-07-29
const musicVisible = ref(false);
const activePlaylistId = ref(DEFAULT_PLAYLIST_ID);
const customPlaylistInput = ref('');
const musicError = ref('');
const musicPlaylistStorageKey = computed(
    () => `epycus:pomodoro:music-playlist:${page.props.auth?.user?.id ?? 'anon'}`,
);
const playlistEmbedUrl = computed(
    () => `https://www.youtube-nocookie.com/embed/videoseries?list=${activePlaylistId.value}`,
);
const isCustomPlaylist = computed(() => activePlaylistId.value !== DEFAULT_PLAYLIST_ID);

function extractPlaylistId(urlOrId) {
    const trimmed = urlOrId.trim();
    if (!trimmed) return null;
    const fromUrl = trimmed.match(/[?&]list=([a-zA-Z0-9_-]+)/);
    if (fromUrl) return fromUrl[1];
    if (/^[a-zA-Z0-9_-]{10,}$/.test(trimmed)) return trimmed; // ya es un ID de playlist, pegado directo
    return null;
}

function useCustomPlaylist() {
    const id = extractPlaylistId(customPlaylistInput.value);
    if (!id) {
        musicError.value =
            'Pegá el link completo de una playlist de YouTube (o su ID) — no se reconoció el formato.';
        return;
    }
    musicError.value = '';
    activePlaylistId.value = id;
    localStorage.setItem(musicPlaylistStorageKey.value, id);
}

function resetToDefaultPlaylist() {
    musicError.value = '';
    customPlaylistInput.value = '';
    activePlaylistId.value = DEFAULT_PLAYLIST_ID;
    localStorage.removeItem(musicPlaylistStorageKey.value);
}

function stopMusic() {
    musicVisible.value = false;
}

watch(selectedMissionId, (val) => {
    const url = new URL(window.location.href);
    if (val) {
        url.searchParams.set('mission_id', String(val));
    } else {
        url.searchParams.delete('mission_id');
    }
    history.replaceState({}, '', url);
});

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const missionId = params.get('mission_id');
    if (missionId) {
        selectedMissionId.value = Number(missionId);
        expandedMissionId.value = Number(missionId);
    }

    const savedGoal = Number(localStorage.getItem(goalStorageKey.value));
    if (savedGoal > 0 && GOAL_OPTIONS.some((o) => o.value === savedGoal)) {
        goalMinutesInput.value = savedGoal;
    }
    const savedPlaylist = localStorage.getItem(musicPlaylistStorageKey.value);
    if (savedPlaylist) activePlaylistId.value = savedPlaylist;

    if (session.value) {
        syncClock(session.value.server_now);
        recomputeRemaining();
        startTicker();
    }
});

onUnmounted(() => {
    stopTicker();
    stopBreakTicker();
});
</script>

<template>
    <Head title="Pomodoro" />

    <AppLayout>
        <div class="lg:flex lg:gap-6">
            <div class="min-w-0 flex-1">
                <BaseCard class="mb-8">
                    <header class="flex items-center gap-4">
                        <div
                            class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-interactive bg-surface-raised/40 p-2 shadow-sm"
                        >
                            <img
                                src="/assets/gifs/pomodoro.gif"
                                alt="Temporizador Pomodoro"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <h1
                                class="font-display text-3xl font-bold tracking-tight text-content-primary"
                            >
                                Pomodoro
                            </h1>
                            <p class="mt-1 text-sm text-content-secondary">
                                Sesiones de enfoque cronometradas.
                            </p>
                        </div>
                    </header>
                </BaseCard>

                <div
                    v-if="toast"
                    class="mb-6 rounded-lg bg-accent px-4 py-3 text-sm font-semibold text-on-accent"
                    role="status"
                >
                    {{ toast }}
                </div>

                <!-- Barra de meta del día -->
                <BaseCard v-if="hasGoal" class="mb-6">
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="font-semibold text-content-primary">
                            Meta de hoy: {{ formatMinutesLabel(totalFocusMinutesToday) }} /
                            {{ formatMinutesLabel(goalMinutesInput) }}
                        </span>
                        <span class="text-xs text-content-secondary"
                            >Ciclo {{ todayCompletedCount + 1 }}</span
                        >
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-surface-sunken">
                        <div
                            class="h-full rounded-full bg-primary-strong transition-[width] duration-500"
                            :style="{ width: goalProgressPercent + '%' }"
                        />
                    </div>
                    <p v-if="exceedsDailyXpCap" class="mt-2 text-xs text-content-muted">
                        Con {{ plannedMinutesInput }} min de foco vas a necesitar ~{{
                            projectedSessionsForGoal
                        }}
                        sesiones para llegar a la meta — el sistema solo da XP a las primeras 8 del
                        día, el resto sigue contando para tu progreso pero sin XP extra.
                    </p>
                </BaseCard>

                <BaseCard class="mb-6 text-center">
                    <template v-if="session || onBreak">
                        <div class="relative mx-auto" style="width: 144px; height: 144px">
                            <svg viewBox="0 0 200 200" class="h-full w-full -rotate-90">
                                <circle
                                    cx="100"
                                    cy="100"
                                    :r="CIRCLE_RADIUS"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="14"
                                    class="text-surface-sunken"
                                />
                                <circle
                                    cx="100"
                                    cy="100"
                                    :r="CIRCLE_RADIUS"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="14"
                                    stroke-linecap="round"
                                    class="transition-[stroke-dashoffset] duration-1000 ease-linear"
                                    :class="onBreak ? 'text-success' : 'text-primary-strong'"
                                    :stroke-dasharray="CIRCLE_CIRCUMFERENCE"
                                    :stroke-dashoffset="onBreak ? breakDashOffset : dashOffset"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <p class="font-display text-2xl tabular-nums text-content-primary">
                                    {{ onBreak ? formatTime(breakRemainingSeconds) : timeLabel }}
                                </p>
                                <p class="mt-1 text-xs text-content-secondary">
                                    {{
                                        onBreak
                                            ? onBreakIsLong
                                                ? 'Descanso largo'
                                                : 'Descanso'
                                            : session.status === 'paused'
                                              ? 'En pausa'
                                              : 'En foco'
                                    }}
                                </p>
                            </div>
                        </div>

                        <template v-if="onBreak">
                            <p class="mt-4 text-sm text-content-secondary">
                                {{
                                    onBreakIsLong
                                        ? 'Descanso largo: llevás 4 ciclos seguidos, tomate el tiempo completo.'
                                        : 'Estirate, tomá agua. Ya volvés al foco.'
                                }}
                            </p>
                            <div class="mt-6 flex justify-center gap-3">
                                <BaseButton variant="ghost" :disabled="busy" @click="skipBreak">
                                    Saltar descanso
                                </BaseButton>
                            </div>
                        </template>
                        <template v-else>
                            <p class="mt-4 text-sm text-content-secondary">
                                {{ session.planned_minutes }} min planificados
                            </p>
                            <div class="mt-6 flex justify-center gap-3">
                                <BaseButton
                                    v-if="session.status === 'running'"
                                    variant="ghost"
                                    :disabled="busy"
                                    @click="callAction('pause')"
                                >
                                    Pausar
                                </BaseButton>
                                <BaseButton v-else :disabled="busy" @click="callAction('resume')">
                                    Reanudar
                                </BaseButton>
                                <BaseButton
                                    variant="danger"
                                    :disabled="busy"
                                    @click="callAction('abandon')"
                                >
                                    Abandonar
                                </BaseButton>
                            </div>
                        </template>
                    </template>

                    <template v-else-if="goalJustCompleted">
                        <p class="font-display text-xl text-content-primary">¡Meta cumplida! 🎉</p>
                        <p class="mt-2 text-sm text-content-secondary">
                            Completaste {{ formatMinutesLabel(totalFocusMinutesToday) }} de foco en
                            {{ todayCompletedCount }} ciclos hoy.
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <BaseButton :disabled="busy" @click="continueStudying">
                                Seguir estudiando
                            </BaseButton>
                            <BaseButton variant="ghost" :disabled="busy" @click="chooseAnotherGoal">
                                Elegir otra meta
                            </BaseButton>
                        </div>
                    </template>

                    <template v-else>
                        <p class="mb-4 text-content-secondary">
                            Elegí cuánto tiempo de foco y de descanso.
                        </p>
                        <div class="mx-auto grid max-w-md grid-cols-3 gap-2 sm:gap-3">
                            <BaseSelect
                                id="goal_minutes"
                                v-model.number="goalMinutesInput"
                                label="Meta"
                                compact
                                :options="GOAL_OPTIONS"
                            />
                            <BaseSelect
                                id="planned_minutes"
                                v-model.number="plannedMinutesInput"
                                label="Foco"
                                compact
                                :options="durationOptions"
                            />
                            <BaseSelect
                                id="break_minutes"
                                v-model.number="breakMinutesInput"
                                label="Descanso"
                                compact
                                :options="breakDurationOptions"
                            />
                        </div>
                        <p class="mx-auto mt-2 max-w-md text-xs text-content-muted">
                            {{ breakHint }}
                        </p>
                        <BaseButton class="mt-4" :disabled="busy" @click="startSession">
                            Iniciar Pomodoro
                        </BaseButton>
                    </template>
                </BaseCard>

                <BaseCard class="mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-display text-lg text-content-primary">
                                Música para enfocarte
                            </h2>
                            <p class="text-xs text-content-secondary">
                                Playlist de YouTube, gratis. Totalmente opcional.
                            </p>
                        </div>
                        <BaseButton
                            v-if="!musicVisible"
                            variant="secondary"
                            @click="musicVisible = true"
                        >
                            🎵 Activar música
                        </BaseButton>
                        <BaseButton v-else variant="ghost" @click="stopMusic">
                            Detener música
                        </BaseButton>
                    </div>

                    <div v-if="musicVisible" class="mt-4">
                        <p class="mb-2 text-xs text-content-muted">
                            Esto carga un video de YouTube (Google) dentro de la página. Mientras
                            esté activo, Google recibe tu IP como con cualquier video de YouTube que
                            mires — es opcional y podés detenerlo cuando quieras con el botón de
                            arriba.
                        </p>
                        <div class="aspect-video w-full overflow-hidden rounded-lg">
                            <iframe
                                :src="playlistEmbedUrl"
                                class="h-full w-full"
                                title="Reproductor de música de YouTube"
                                allow="
                                    accelerometer;
                                    autoplay;
                                    encrypted-media;
                                    gyroscope;
                                    picture-in-picture;
                                "
                                allowfullscreen
                                referrerpolicy="strict-origin-when-cross-origin"
                            ></iframe>
                        </div>

                        <div class="mt-3 flex flex-wrap items-end gap-2">
                            <div class="min-w-[220px] flex-1">
                                <BaseInput
                                    id="custom_playlist"
                                    v-model="customPlaylistInput"
                                    label="¿Preferís tu propia playlist de YouTube?"
                                    placeholder="Pegá el link de una playlist de YouTube"
                                    :error="musicError"
                                />
                            </div>
                            <BaseButton variant="ghost" @click="useCustomPlaylist">
                                Usar esta
                            </BaseButton>
                            <BaseButton
                                v-if="isCustomPlaylist"
                                variant="ghost"
                                @click="resetToDefaultPlaylist"
                            >
                                Volver a la de por defecto
                            </BaseButton>
                        </div>
                    </div>
                </BaseCard>

                <BaseCard class="mb-6">
                    <h2 class="mb-4 font-display text-lg text-content-primary">
                        Estadísticas (últimos 7 días)
                    </h2>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">
                                Completadas
                            </p>
                            <p class="font-display text-2xl text-content-primary">
                                {{ stats.sessionsCompleted }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">
                                Tasa de finalización
                            </p>
                            <p class="font-display text-2xl text-content-primary">
                                {{ stats.completionRate }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">
                                Minutos de foco
                            </p>
                            <p class="font-display text-2xl text-content-primary">
                                {{ stats.focusMinutesTotal }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">
                                Iniciadas
                            </p>
                            <p class="font-display text-2xl text-content-primary">
                                {{ stats.sessionsStarted }}
                            </p>
                        </div>
                    </div>
                </BaseCard>

                <BaseCard>
                    <h2 class="mb-4 font-display text-lg text-content-primary">Hoy</h2>
                    <p v-if="todaySessions.length === 0" class="text-sm text-content-secondary">
                        Todavía no hiciste ningún Pomodoro hoy.
                    </p>
                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="s in todaySessions"
                            :key="s.id"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <span class="text-content-secondary">{{
                                new Date(s.started_at).toLocaleTimeString('es-PE', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                })
                            }}</span>
                            <span class="text-content-primary"
                                >{{ s.focus_minutes ?? s.planned_minutes }} min</span
                            >
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                :class="
                                    s.status === 'completed'
                                        ? 'bg-success text-on-accent'
                                        : 'bg-surface-sunken text-content-muted'
                                "
                            >
                                {{ statusLabel(s.status) }}
                            </span>
                        </li>
                    </ul>
                </BaseCard>
            </div>

            <!-- Panel lateral de misiones activas -->
            <div class="mt-6 shrink-0 lg:mt-0 lg:w-80">
                <BaseCard>
                    <h2 class="mb-3 font-display text-lg text-content-primary">Misiones activas</h2>
                    <p v-if="missions.length === 0" class="text-sm text-content-secondary">
                        No hay misiones activas.
                    </p>
                    <div v-else class="space-y-2">
                        <div
                            v-for="m in missions"
                            :key="m.id"
                            class="rounded-lg border"
                            :class="
                                selectedMissionId === m.id
                                    ? 'border-primary-strong'
                                    : 'border-border'
                            "
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm"
                                @click="toggleExpandMission(m.id)"
                            >
                                <span
                                    class="flex items-center gap-2 truncate font-medium text-content-primary"
                                >
                                    <span
                                        class="inline-block h-2 w-2 shrink-0 rounded-full"
                                        :class="
                                            m.is_overdue
                                                ? 'bg-danger-text'
                                                : m.subtask_done > 0
                                                  ? 'bg-primary-strong'
                                                  : 'bg-content-muted'
                                        "
                                    ></span>
                                    <span class="truncate">{{ m.title }}</span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <span class="text-xs text-content-muted"
                                        >{{ m.subtask_done }}/{{ m.subtask_count }}</span
                                    >
                                    <svg
                                        class="h-4 w-4 text-content-muted transition-transform"
                                        :class="expandedMissionId === m.id ? 'rotate-180' : ''"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </span>
                            </button>

                            <div
                                v-if="expandedMissionId === m.id"
                                class="border-t border-border px-3 py-2"
                            >
                                <ul v-if="m.subtasks.length > 0" class="space-y-1">
                                    <li
                                        v-for="st in m.subtasks"
                                        :key="st.id"
                                        class="flex items-center gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                                            :class="
                                                st.is_completed
                                                    ? 'border-primary-strong bg-primary-strong text-on-accent'
                                                    : 'border-border'
                                            "
                                            :disabled="subtaskBusy[st.id]"
                                            @click="toggleSubtask(m.id, st)"
                                        >
                                            <svg
                                                v-if="st.is_completed"
                                                class="h-3 w-3"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="3"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                        </button>
                                        <span
                                            class="text-sm"
                                            :class="
                                                st.is_completed
                                                    ? 'text-content-muted line-through'
                                                    : 'text-content-primary'
                                            "
                                            >{{ st.title }}</span
                                        >
                                    </li>
                                </ul>
                                <p v-else class="text-xs text-content-muted">Sin subtareas</p>
                            </div>

                            <div class="border-t border-border px-3 py-1.5">
                                <button
                                    type="button"
                                    class="w-full rounded px-2 py-1 text-xs font-semibold transition-colors"
                                    :class="
                                        selectedMissionId === m.id
                                            ? 'bg-primary-strong text-on-accent'
                                            : 'bg-surface-sunken text-content-secondary hover:bg-primary-strong hover:text-on-accent'
                                    "
                                    @click="selectMission(m.id)"
                                >
                                    {{
                                        selectedMissionId === m.id
                                            ? '✓ Asociada al Pomodoro'
                                            : 'Enfocarme en esta misión'
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </BaseCard>
            </div>
        </div>
    </AppLayout>
</template>
