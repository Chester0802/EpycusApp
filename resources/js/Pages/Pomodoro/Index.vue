<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';

const props = defineProps({
    activeSession: { type: Object, default: null },
    autoCompletedFocusMinutes: { type: Number, default: null },
    autoCompletedXp: { type: Number, default: null },
    todaySessions: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
    avatarImage: { type: String, default: null },
});

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

const session = ref(props.activeSession);
const remainingSeconds = ref(0);
const busy = ref(false);
const toast = ref(
    props.autoCompletedFocusMinutes !== null
        ? `Tu Pomodoro anterior se completó mientras no estabas: ${props.autoCompletedFocusMinutes} min de foco, +${props.autoCompletedXp ?? 0} XP.`
        : null,
);
const plannedMinutesInput = ref(25);
const durationOptions = [15, 20, 25, 30, 40, 50].map((n) => ({ value: n, label: `${n} min` }));

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
const breakMinutesInput = ref(5);
const breakDurationOptions = [3, 5, 10, 15, 20].map((n) => ({ value: n, label: `${n} min` }));
const onBreak = ref(false);
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

function startBreak() {
    onBreak.value = true;
    breakRemainingSeconds.value = breakMinutesInput.value * 60;
    playChime();
    stopBreakTicker();
    breakTimer = setInterval(() => {
        breakRemainingSeconds.value -= 1;
        if (breakRemainingSeconds.value <= 0) {
            stopBreakTicker();
            onBreak.value = false;
            playChime();
            startSession(); // "y otra vez a enfoque" — el ciclo sigue solo
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
    const referenceEndMs = s.status === 'paused' && s.paused_at ? new Date(s.paused_at).getTime() : correctedNowMs();
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
    return { completed: 'completada', abandoned: 'abandonada', running: 'en curso', paused: 'en pausa' }[status] ?? status;
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
    const total = breakMinutesInput.value * 60;
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
    busy.value = true;
    toast.value = null;
    try {
        const res = await fetch(route('pomodoro.start'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
            body: JSON.stringify({ planned_minutes: plannedMinutesInput.value }),
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
            session.value = null;
            router.reload({ only: ['todaySessions', 'stats'] });
            if (action === 'complete') {
                toast.value = `¡Foco completado! +${body.xp_awarded ?? 0} XP. Empieza el descanso.`;
                startBreak();
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

onMounted(() => {
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
        <header class="mb-8 flex items-center gap-4">
            <div v-if="avatarImage" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-1">
                <img :src="avatarImage" alt="" class="h-full w-full object-contain" />
            </div>
            <div>
                <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">Pomodoro</h1>
                <p class="mt-1 text-sm text-content-secondary">Sesiones de enfoque cronometradas.</p>
            </div>
        </header>

        <div
            v-if="toast"
            class="mb-6 rounded-lg bg-accent px-4 py-3 text-sm font-semibold text-on-accent"
            role="status"
        >
            {{ toast }}
        </div>

        <BaseCard class="mb-6 text-center">
            <template v-if="session || onBreak">
                <!--
                    Tamaño por estilo inline, no `h-36 w-36`: esas clases no
                    estaban llegando al CSS servido por Vite en dev (probado
                    con getComputedStyle — el círculo salía de 891px en vez
                    de 144px). Con estilo inline no depende de que Tailwind
                    haya generado esa utilidad en particular.
                -->
                <div class="relative mx-auto" style="width: 144px; height: 144px">
                    <svg viewBox="0 0 200 200" class="h-full w-full -rotate-90">
                        <!--
                            fill/stroke por clase de Tailwind (stroke-primary-strong,
                            etc.) no se aplicaba sobre los tokens de color propios del
                            proyecto (probado en navegador — el círculo salía relleno
                            de negro sólido). Mismo arreglo que ya funciona en
                            NavIcon.vue: fill="none" como atributo SVG literal, color
                            heredado con stroke="currentColor" + una clase text-*.
                        -->
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
                            {{ onBreak ? 'Descanso' : session.status === 'paused' ? 'En pausa' : 'En foco' }}
                        </p>
                    </div>
                </div>

                <template v-if="onBreak">
                    <p class="mt-4 text-sm text-content-secondary">Estirate, tomá agua. Ya volvés al foco.</p>
                    <div class="mt-6 flex justify-center gap-3">
                        <BaseButton variant="ghost" :disabled="busy" @click="skipBreak"> Saltar descanso </BaseButton>
                    </div>
                </template>
                <template v-else>
                    <p class="mt-4 text-sm text-content-secondary">{{ session.planned_minutes }} min planificados</p>
                    <div class="mt-6 flex justify-center gap-3">
                        <BaseButton v-if="session.status === 'running'" variant="ghost" :disabled="busy" @click="callAction('pause')">
                            Pausar
                        </BaseButton>
                        <BaseButton v-else :disabled="busy" @click="callAction('resume')"> Reanudar </BaseButton>
                        <BaseButton variant="danger" :disabled="busy" @click="callAction('abandon')"> Abandonar </BaseButton>
                    </div>
                </template>
            </template>

            <template v-else>
                <p class="mb-4 text-content-secondary">Elegí cuánto tiempo de foco y de descanso.</p>
                <div class="mx-auto flex max-w-xs flex-col gap-4">
                    <BaseSelect
                        id="planned_minutes"
                        v-model.number="plannedMinutesInput"
                        label="Foco"
                        :options="durationOptions"
                    />
                    <BaseSelect
                        id="break_minutes"
                        v-model.number="breakMinutesInput"
                        label="Descanso"
                        :options="breakDurationOptions"
                    />
                </div>
                <BaseButton class="mt-4" :disabled="busy" @click="startSession"> Iniciar Pomodoro </BaseButton>
            </template>
        </BaseCard>

        <BaseCard class="mb-6">
            <h2 class="mb-4 font-display text-lg text-content-primary">Estadísticas (últimos 7 días)</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-content-muted">Completadas</p>
                    <p class="font-display text-2xl text-content-primary">{{ stats.sessionsCompleted }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-content-muted">Tasa de finalización</p>
                    <p class="font-display text-2xl text-content-primary">{{ stats.completionRate }}%</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-content-muted">Minutos de foco</p>
                    <p class="font-display text-2xl text-content-primary">{{ stats.focusMinutesTotal }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-content-muted">Iniciadas</p>
                    <p class="font-display text-2xl text-content-primary">{{ stats.sessionsStarted }}</p>
                </div>
            </div>
        </BaseCard>

        <BaseCard>
            <h2 class="mb-4 font-display text-lg text-content-primary">Hoy</h2>
            <p v-if="todaySessions.length === 0" class="text-sm text-content-secondary">Todavía no hiciste ningún Pomodoro hoy.</p>
            <ul v-else class="divide-y divide-border">
                <li v-for="s in todaySessions" :key="s.id" class="flex items-center justify-between py-2 text-sm">
                    <span class="text-content-secondary">{{ new Date(s.started_at).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }) }}</span>
                    <span class="text-content-primary">{{ s.focus_minutes ?? s.planned_minutes }} min</span>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="s.status === 'completed' ? 'bg-success text-on-accent' : 'bg-surface-sunken text-content-muted'"
                    >
                        {{ statusLabel(s.status) }}
                    </span>
                </li>
            </ul>
        </BaseCard>
    </AppLayout>
</template>
