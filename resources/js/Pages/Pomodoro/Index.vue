<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { usePomodoroState } from '@/Composables/usePomodoroState';

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

const page = usePage();

const {
    mode,
    session,
    isPaused,
    busy,
    remainingSeconds,
    formattedTime,
    progressPercent,
    breakInfo,
    breakJustFinished,
    startFocus,
    pause,
    resume,
    completeFocus,
    startBreak,
    skipBreak,
    abandon,
    syncWithInertiaProps,
} = usePomodoroState();

const onBreak = computed(() => mode.value === 'break');
const onBreakIsLong = computed(() => breakInfo.value.isLong);

const selectedMissionId = ref(null);
const expandedMissionId = ref(null);
const subtaskBusy = ref({});

function selectMission(id) {
    selectedMissionId.value = selectedMissionId.value === id ? null : id;
}

function toggleExpandMission(id) {
    expandedMissionId.value = expandedMissionId.value === id ? null : id;
}

function csrfHeader() {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
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

const toast = ref(
    props.autoCompletedFocusMinutes !== null
        ? `Tu Pomodoro anterior se completó mientras no estabas: ${props.autoCompletedFocusMinutes} min de foco, +${props.autoCompletedXp ?? 0} XP.`
        : null,
);

const FOCUS_OPTIONS = [15, 20, 25, 30, 40, 50];
const BREAK_MINUTES_ALL = [3, 5, 10, 15, 20];
const BREAK_RATIO_MAX = 0.4;
const LONG_BREAK_EVERY = 4;

function maxBreakForFocus(focusMinutes) {
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

// ── Meta de Estudio Diaria ──────────────────────────────────────────────────
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

const liveFocusMinutes = computed(() => {
    if (!session.value || onBreak.value) return 0;
    const total = (session.value.planned_minutes || 25) * 60;
    return Math.max(0, Math.floor((total - remainingSeconds.value) / 60));
});
const totalFocusMinutesToday = computed(() => todayFocusMinutes.value + liveFocusMinutes.value);
const goalProgressPercent = computed(() =>
    hasGoal.value
        ? Math.min(100, Math.round((totalFocusMinutesToday.value / goalMinutesInput.value) * 100))
        : 0,
);

const projectedSessionsForGoal = computed(() =>
    hasGoal.value ? Math.ceil(goalMinutesInput.value / plannedMinutesInput.value) : 0,
);
const exceedsDailyXpCap = computed(
    () => hasGoal.value && todayCompletedCount.value + projectedSessionsForGoal.value > 8,
);

function formatTime(totalSec) {
    const m = Math.floor(totalSec / 60)
        .toString()
        .padStart(2, '0');
    const s = Math.floor(totalSec % 60)
        .toString()
        .padStart(2, '0');
    return `${m}:${s}`;
}

const timeLabel = computed(() => formattedTime.value);

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

// ── Círculo SVG de Progreso ────────────────────────────────────────────────
const CIRCLE_RADIUS = 90;
const CIRCLE_CIRCUMFERENCE = 2 * Math.PI * CIRCLE_RADIUS;
const dashOffset = computed(() => CIRCLE_CIRCUMFERENCE * (progressPercent.value / 100));
const breakDashOffset = computed(() => CIRCLE_CIRCUMFERENCE * (progressPercent.value / 100));

async function startSession() {
    goalJustCompleted.value = false;
    toast.value = null;
    try {
        const selectedMission = props.missions.find((m) => m.id === selectedMissionId.value);
        await startFocus(
            plannedMinutesInput.value,
            selectedMissionId.value,
            selectedMission ? selectedMission.title : null,
        );
    } catch (e) {
        toast.value = e.message || 'No se pudo iniciar la sesión.';
    }
}

async function callAction(action) {
    if (action === 'pause') {
        await pause();
    } else if (action === 'resume') {
        await resume();
    } else if (action === 'abandon') {
        await abandon();
    } else if (action === 'complete') {
        await completeFocus();
    }
}

function continueStudying() {
    goalJustCompleted.value = false;
    startSession();
}

function chooseAnotherGoal() {
    goalJustCompleted.value = false;
    goalMinutesInput.value = 0;
}

// ── Música de Fondo (YouTube Lo-Fi) ─────────────────────────────────────────
const DEFAULT_EMBED_URL = 'https://www.youtube-nocookie.com/embed/videoseries?list=PLfP6i5T0-DkIMLNRwmJpRBs4PJvxfgwBg';
const musicVisible = ref(false);
const activeEmbedUrl = ref(DEFAULT_EMBED_URL);
const customPlaylistInput = ref('');
const musicError = ref('');
const musicPlaylistStorageKey = computed(
    () => `epycus:pomodoro:music-playlist:${page.props.auth?.user?.id ?? 'anon'}`,
);
const playlistEmbedUrl = computed(() => activeEmbedUrl.value);
const isCustomPlaylist = computed(() => activeEmbedUrl.value !== DEFAULT_EMBED_URL);

function parseYouTubeUrl(input) {
    if (!input) return null;
    const trimmed = input.trim();
    if (!trimmed) return null;

    const iframeSrcMatch = trimmed.match(/src=["'](https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/embed\/[^"']+)["']/i);
    if (iframeSrcMatch) return iframeSrcMatch[1];

    try {
        const urlStr = /^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`;
        const url = new URL(urlStr);
        const hostname = url.hostname.toLowerCase().replace(/^www\./, '').replace(/^m\./, '').replace(/^music\./, '');

        if (hostname === 'youtube.com' || hostname === 'youtube-nocookie.com' || hostname === 'youtu.be') {
            const listId = url.searchParams.get('list');
            if (hostname === 'youtu.be') {
                const videoId = url.pathname.slice(1).split('/')[0].split('?')[0];
                if (listId && videoId) return `https://www.youtube-nocookie.com/embed/${videoId}?list=${listId}`;
                if (videoId) return `https://www.youtube-nocookie.com/embed/${videoId}`;
                if (listId) return `https://www.youtube-nocookie.com/embed/videoseries?list=${listId}`;
            }

            const videoId = url.searchParams.get('v');
            if (url.pathname.includes('/watch') && videoId) {
                if (listId) return `https://www.youtube-nocookie.com/embed/${videoId}?list=${listId}`;
                return `https://www.youtube-nocookie.com/embed/${videoId}`;
            }

            if (url.pathname.includes('/playlist') && listId) {
                return `https://www.youtube-nocookie.com/embed/videoseries?list=${listId}`;
            }

            if (url.pathname.includes('/embed/')) {
                const pathParts = url.pathname.split('/embed/')[1]?.split('?')[0];
                if (pathParts) {
                    if (listId) return `https://www.youtube-nocookie.com/embed/${pathParts}?list=${listId}`;
                    return `https://www.youtube-nocookie.com/embed/${pathParts}`;
                }
            }

            if (url.pathname.includes('/shorts/')) {
                const shortId = url.pathname.split('/shorts/')[1]?.split('?')[0];
                if (shortId) return `https://www.youtube-nocookie.com/embed/${shortId}`;
            }

            if (listId) return `https://www.youtube-nocookie.com/embed/videoseries?list=${listId}`;
            if (videoId) return `https://www.youtube-nocookie.com/embed/${videoId}`;
        }
    } catch {
        // Ignorar
    }

    if (/^(PL|OLAK|RD|UU|FL|LL)[a-zA-Z0-9_-]+$/i.test(trimmed)) {
        return `https://www.youtube-nocookie.com/embed/videoseries?list=${trimmed}`;
    }

    if (/^[a-zA-Z0-9_-]{11}$/.test(trimmed)) {
        return `https://www.youtube-nocookie.com/embed/${trimmed}`;
    }

    const listMatch = trimmed.match(/[?&]list=([a-zA-Z0-9_-]+)/);
    if (listMatch) return `https://www.youtube-nocookie.com/embed/videoseries?list=${listMatch[1]}`;

    const vMatch = trimmed.match(/[?&]v=([a-zA-Z0-9_-]{11})/);
    if (vMatch) return `https://www.youtube-nocookie.com/embed/${vMatch[1]}`;

    return null;
}

function useCustomPlaylist() {
    const embedUrl = parseYouTubeUrl(customPlaylistInput.value);
    if (!embedUrl) {
        musicError.value = 'Ingresa un enlace válido de YouTube (video individual, Shorts o playlist) o su ID.';
        return;
    }
    musicError.value = '';
    activeEmbedUrl.value = embedUrl;
    localStorage.setItem(musicPlaylistStorageKey.value, customPlaylistInput.value.trim());
}

function resetToDefaultPlaylist() {
    musicError.value = '';
    customPlaylistInput.value = '';
    activeEmbedUrl.value = DEFAULT_EMBED_URL;
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
    } else {
        const taskParam = params.get('task');
        if (taskParam && Array.isArray(props.missions)) {
            const found = props.missions.find((m) =>
                m.title?.toLowerCase().includes(taskParam.toLowerCase()),
            );
            if (found) {
                selectedMissionId.value = found.id;
                expandedMissionId.value = found.id;
            }
        }
    }

    const savedGoal = Number(localStorage.getItem(goalStorageKey.value));
    if (savedGoal > 0 && GOAL_OPTIONS.some((o) => o.value === savedGoal)) {
        goalMinutesInput.value = savedGoal;
    }

    const savedPlaylist = localStorage.getItem(musicPlaylistStorageKey.value);
    if (savedPlaylist) {
        const parsed = parseYouTubeUrl(savedPlaylist);
        if (parsed) {
            activeEmbedUrl.value = parsed;
            customPlaylistInput.value = savedPlaylist;
        }
    }

    syncWithInertiaProps(props.activeSession);
});

watch(
    () => props.activeSession,
    (newVal) => {
        syncWithInertiaProps(newVal);
    },
    { deep: true },
);
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
                                    {{ onBreak ? formattedTime : timeLabel }}
                                </p>
                                <p class="mt-1 text-xs text-content-secondary">
                                    {{
                                        onBreak
                                            ? onBreakIsLong
                                                ? 'Descanso largo'
                                                : 'Descanso'
                                            : isPaused
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
                                        ? 'Descanso largo: llevas 4 ciclos seguidos, tomate el tiempo completo.'
                                        : 'Estírate, toma agua. Ya vuelves al foco.'
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
                                {{ session?.planned_minutes || 25 }} min planificados
                            </p>
                            <div class="mt-6 flex justify-center gap-3">
                                <BaseButton
                                    v-if="!isPaused"
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
                        <div class="py-2">
                            <p class="font-display text-2xl font-bold text-content-primary">¡Meta cumplida! 🎉</p>
                            <p class="mt-2 text-sm text-content-secondary max-w-md mx-auto">
                                Completaste {{ formatMinutesLabel(totalFocusMinutesToday) }} de foco en
                                {{ todayCompletedCount }} ciclos hoy.
                            </p>
                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                <BaseButton :disabled="busy" @click="continueStudying">
                                    Seguir estudiando (+{{ plannedMinutesInput }} min)
                                </BaseButton>
                                <BaseButton variant="ghost" :disabled="busy" @click="chooseAnotherGoal">
                                    Aumentar meta diaria
                                </BaseButton>
                            </div>
                        </div>
                    </template>

                    <template v-else-if="breakJustFinished">
                        <div class="py-2">
                            <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-success/15 px-3.5 py-1 text-xs font-bold text-success shadow-sm">
                                ☕ Descanso finalizado
                            </div>
                            <p class="font-display text-2xl font-bold text-content-primary">
                                ¿Listo para tu siguiente bloque de foco?
                            </p>
                            <p class="mt-2 text-sm text-content-secondary max-w-md mx-auto leading-relaxed">
                                Llevas {{ formatMinutesLabel(totalFocusMinutesToday) }} de foco completados hoy (Ciclo {{ todayCompletedCount }} listo). Inicia el siguiente ciclo cuando estés preparado o ajusta tus metas.
                            </p>
                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                <BaseButton :disabled="busy" @click="startSession">
                                    ▶ Iniciar Siguiente Bloque ({{ plannedMinutesInput }} min)
                                </BaseButton>
                                <BaseButton variant="ghost" :disabled="busy" @click="breakJustFinished = false">
                                    ⚙️ Ajustar tiempos o meta
                                </BaseButton>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <p class="mb-4 text-content-secondary">
                            Elige cuánto tiempo de foco y de descanso.
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
                                Video o playlist de YouTube. Totalmente opcional.
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
                            Esto carga el reproductor de YouTube dentro de la página. Puedes usar cualquier video individual, directo, Shorts o playlist personalizada.
                        </p>
                        <div class="aspect-video w-full overflow-hidden rounded-lg">
                            <iframe
                                :src="playlistEmbedUrl"
                                class="h-full w-full"
                                title="Reproductor de música de YouTube"
                                allow="
                                    accelerometer;
                                    autoplay;
                                    clipboard-write;
                                    encrypted-media;
                                    gyroscope;
                                    picture-in-picture;
                                "
                                allowfullscreen
                                referrerpolicy="strict-origin-when-cross-origin"
                            ></iframe>
                        </div>

                        <div class="mt-3 flex flex-wrap items-end gap-2">
                            <div class="min-w-[240px] flex-1">
                                <BaseInput
                                    id="custom_playlist"
                                    v-model="customPlaylistInput"
                                    label="¿Deseas tu propio video o playlist de YouTube?"
                                    placeholder="Pega el enlace de un video o playlist de YouTube"
                                    :error="musicError"
                                />
                            </div>
                            <BaseButton variant="ghost" @click="useCustomPlaylist">
                                Usar este enlace
                            </BaseButton>
                            <BaseButton
                                v-if="isCustomPlaylist"
                                variant="ghost"
                                @click="resetToDefaultPlaylist"
                            >
                                Restaurar por defecto
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
                            <div class="flex items-center justify-between p-3">
                                <button
                                    type="button"
                                    class="min-w-0 flex-1 text-left text-sm font-semibold text-content-primary"
                                    @click="selectMission(m.id)"
                                >
                                    {{ m.title }}
                                </button>
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="m.subtask_count > 0"
                                        class="text-xs text-content-muted"
                                    >
                                        {{ m.subtask_done }}/{{ m.subtask_count }}
                                    </span>
                                    <button
                                        v-if="m.subtask_count > 0"
                                        type="button"
                                        class="text-xs text-content-secondary hover:text-content-primary"
                                        @click="toggleExpandMission(m.id)"
                                    >
                                        {{ expandedMissionId === m.id ? '▲' : '▼' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Subtareas desplegables -->
                            <div
                                v-if="expandedMissionId === m.id && m.subtasks.length > 0"
                                class="border-t border-border bg-surface-sunken px-3 py-2"
                            >
                                <ul class="space-y-1.5">
                                    <li
                                        v-for="sub in m.subtasks"
                                        :key="sub.id"
                                        class="flex items-center gap-2 text-xs"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="sub.is_completed"
                                            :disabled="subtaskBusy[sub.id]"
                                            class="h-3.5 w-3.5 rounded border-border text-primary-strong focus:ring-primary-strong"
                                            @change="toggleSubtask(m.id, sub)"
                                        />
                                        <span
                                            :class="
                                                sub.is_completed
                                                    ? 'text-content-muted line-through'
                                                    : 'text-content-primary'
                                            "
                                        >
                                            {{ sub.title }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </BaseCard>
            </div>
        </div>
    </AppLayout>
</template>
