<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import AppIcon from '@/Components/AppIcon.vue';
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
    courses: { type: Array, default: () => [] },
    readings: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
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
    skipBreak,
    abandon,
    syncWithInertiaProps,
} = usePomodoroState();

const onBreak = computed(() => mode.value === 'break');
const onBreakIsLong = computed(() => breakInfo.value.isLong);

// Context selection & focus targets
const selectedContextType = ref('free');
const selectedMissionId = ref(null);
const selectedCourseId = ref(null);
const selectedReadingId = ref(null);
const selectedSkillId = ref(null);

const missionFilterTab = ref('all'); // 'all' | 'academic' | 'work' | 'personal'
const expandedMissionId = ref(null);
const subtaskBusy = ref({});

const CONTEXT_OPTIONS = [
    { value: 'free', label: '⚡ Enfoque Libre' },
    { value: 'mission', label: '🎯 Misiones / Entregables' },
    { value: 'course', label: '🎓 Cursos / Asignaturas' },
    { value: 'reading', label: '📚 Lecturas / Biblioteca' },
    { value: 'skill', label: '⚡ Habilidades / Destrezas' },
    { value: 'work', label: '💼 Trabajo / Laboral' },
    { value: 'personal', label: '🌱 Hábitos / Desarrollo Personal' }
];

function selectMission(id) {
    if (selectedMissionId.value === id) {
        selectedMissionId.value = null;
        expandedMissionId.value = null;
    } else {
        selectedMissionId.value = id;
        expandedMissionId.value = id;
        // Reset others
        selectedCourseId.value = null;
        selectedReadingId.value = null;
        selectedSkillId.value = null;
    }
}

function selectCourse(id) {
    selectedCourseId.value = selectedCourseId.value === id ? null : id;
    selectedMissionId.value = null;
    selectedReadingId.value = null;
    selectedSkillId.value = null;
}

function selectReading(id) {
    selectedReadingId.value = selectedReadingId.value === id ? null : id;
    selectedMissionId.value = null;
    selectedCourseId.value = null;
    selectedSkillId.value = null;
}

function selectSkill(id) {
    selectedSkillId.value = selectedSkillId.value === id ? null : id;
    selectedMissionId.value = null;
    selectedCourseId.value = null;
    selectedReadingId.value = null;
}

function clearTarget() {
    selectedMissionId.value = null;
    selectedCourseId.value = null;
    selectedReadingId.value = null;
    selectedSkillId.value = null;
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
    { value: 420, label: '7 horas' },
    { value: 480, label: '8 horas' },
];
const goalMinutesInput = ref(0);
const hasGoal = computed(() => goalMinutesInput.value > 0);
const goalJustCompleted = ref(false);

const todayIso = new Date().toISOString().slice(0, 10);
const goalStorageKey = computed(
    () => `epycus:pomodoro:goal:${page.props.auth?.user?.id ?? 'anon'}:${todayIso}`,
);

const filteredMissions = computed(() => {
    if (selectedContextType.value === 'work') {
        return props.missions.filter(m => m.mission_type === 'work');
    }
    if (selectedContextType.value === 'personal') {
        return props.missions.filter(m => m.mission_type === 'personal');
    }
    if (selectedContextType.value === 'mission') {
        if (missionFilterTab.value === 'all') return props.missions;
        return props.missions.filter(m => m.mission_type === missionFilterTab.value);
    }
    return props.missions;
});

const activeTargetInfo = computed(() => {
    if (selectedMissionId.value) {
        const m = props.missions.find(x => x.id === selectedMissionId.value);
        if (m) return { type: 'mission', label: m.title, subtext: `${m.subtask_done}/${m.subtask_count} subtareas`, icon: 'target' };
    }
    if (selectedCourseId.value) {
        const c = props.courses.find(x => x.id === selectedCourseId.value);
        if (c) return { type: 'course', label: c.name, subtext: c.professor ? `Prof. ${c.professor}` : 'Asignatura', icon: 'graduation-cap' };
    }
    if (selectedReadingId.value) {
        const r = props.readings.find(x => x.id === selectedReadingId.value);
        if (r) return { type: 'reading', label: r.title, subtext: `Pág. ${r.current_page}/${r.total_pages}`, icon: 'book-open' };
    }
    if (selectedSkillId.value) {
        const s = props.skills.find(x => x.id === selectedSkillId.value);
        if (s) return { type: 'skill', label: s.name, subtext: `Nivel ${s.current_level} (${s.category})`, icon: 'zap' };
    }
    return null;
});

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
        let missionIdParam = null;
        let missionTitleParam = null;
        let contextTypeParam = selectedContextType.value !== 'free' ? selectedContextType.value : null;
        let contextIdParam = null;

        if (selectedContextType.value === 'mission' || selectedContextType.value === 'work' || selectedContextType.value === 'personal') {
            if (selectedMissionId.value) {
                const m = props.missions.find(x => x.id === selectedMissionId.value);
                missionIdParam = selectedMissionId.value;
                missionTitleParam = m ? m.title : null;
                contextTypeParam = m ? (m.mission_type === 'academic' ? 'mission' : m.mission_type) : 'mission';
                contextIdParam = selectedMissionId.value;
            }
        } else if (selectedContextType.value === 'course') {
            if (selectedCourseId.value) {
                const c = props.courses.find(x => x.id === selectedCourseId.value);
                missionTitleParam = c ? `Curso: ${c.name}` : null;
                contextTypeParam = 'course';
                contextIdParam = selectedCourseId.value;
            }
        } else if (selectedContextType.value === 'reading') {
            if (selectedReadingId.value) {
                const r = props.readings.find(x => x.id === selectedReadingId.value);
                missionTitleParam = r ? `Lectura: ${r.title}` : null;
                contextTypeParam = 'reading';
                contextIdParam = selectedReadingId.value;
            }
        } else if (selectedContextType.value === 'skill') {
            if (selectedSkillId.value) {
                const s = props.skills.find(x => x.id === selectedSkillId.value);
                missionTitleParam = s ? `Habilidad: ${s.name}` : null;
                contextTypeParam = 'skill';
                contextIdParam = selectedSkillId.value;
            }
        }

        await startFocus(
            plannedMinutesInput.value,
            missionIdParam,
            missionTitleParam,
            null,
            contextTypeParam,
            contextIdParam
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

function parseYouTubeUrl(input) {
    if (!input) return null;
    const trimmed = input.trim();
    if (!trimmed) return null;

    try {
        if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
            const url = new URL(trimmed);
            const videoId = url.searchParams.get('v');
            const listId = url.searchParams.get('list');

            if (url.hostname.includes('youtu.be')) {
                const id = url.pathname.slice(1);
                if (id) {
                    if (listId) return `https://www.youtube-nocookie.com/embed/${id}?list=${listId}`;
                    return `https://www.youtube-nocookie.com/embed/${id}`;
                }
            }

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

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const missionId = params.get('mission_id') || params.get('mission');
    const courseId = params.get('course_id') || params.get('course');
    const readingId = params.get('reading_id') || params.get('reading');
    const skillId = params.get('skill_id') || params.get('skill');

    if (missionId) {
        selectedContextType.value = 'mission';
        selectMission(Number(missionId));
    } else if (courseId) {
        selectedContextType.value = 'course';
        selectCourse(Number(courseId));
    } else if (readingId) {
        selectedContextType.value = 'reading';
        selectReading(Number(readingId));
    } else if (skillId) {
        selectedContextType.value = 'skill';
        selectSkill(Number(skillId));
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

    fetchMonthlyReport();
    syncWithInertiaProps(props.activeSession);
});

const monthlyReport = ref(null);
async function fetchMonthlyReport() {
    try {
        const res = await fetch(route('pomodoro.report.index'), {
            headers: { Accept: 'application/json' }
        });
        if (res.ok) {
            monthlyReport.value = await res.json();
        }
    } catch {
        // Silencioso
    }
}

watch(
    () => props.activeSession,
    (newVal) => {
        syncWithInertiaProps(newVal);
    },
    { deep: true },
);
</script>

<template>
    <Head title="Pomodoro & Enfoque" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-xl text-content-primary leading-tight flex items-center gap-2">
                        <span>🍅</span> Pomodoro & Enfoque
                    </h2>
                </div>
                <div v-if="activeTargetInfo" class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-xs font-semibold text-primary-strong">
                    <AppIcon :name="activeTargetInfo.icon" :size="14" />
                    <span>{{ activeTargetInfo.label }}</span>
                    <button type="button" @click="clearTarget" class="ml-1 text-content-muted hover:text-danger cursor-pointer" title="Quitar selección">✕</button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Header Card Principal con GIF Animado -->
                <BaseCard class="p-6 border-border-interactive mb-6">
                    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-20 w-20 sm:h-24 sm:w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-interactive bg-surface-raised/40 p-2 shadow-sm"
                            >
                                <img
                                    src="/assets/gifs/pomodoro.gif"
                                    alt="Pomodoro & Enfoque"
                                    class="h-full w-full object-contain"
                                />
                            </div>
                            <div>
                                <h1 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-content-primary flex items-center gap-2">
                                    Pomodoro & Enfoque 🍅
                                </h1>
                                <p class="mt-1 text-sm text-content-secondary max-w-xl">
                                    Estructura tus bloques de concentración profunda, vincula tus cursos, lecturas y misiones, y mantén tu racha activa.
                                </p>
                            </div>
                        </div>

                        <!-- Métricas Rápidas en Header -->
                        <div class="flex items-center gap-3 bg-surface-raised/40 p-3 rounded-xl border border-border-interactive/50 shrink-0">
                            <div class="text-center px-3 border-r border-border">
                                <p class="text-[10px] text-content-muted font-bold uppercase tracking-wider">Hoy</p>
                                <p class="text-lg font-black text-primary-strong">{{ formatMinutesLabel(totalFocusMinutesToday) }}</p>
                            </div>
                            <div class="text-center px-3">
                                <p class="text-[10px] text-content-muted font-bold uppercase tracking-wider">Ciclos</p>
                                <p class="text-lg font-black text-success">{{ todayCompletedCount }}</p>
                            </div>
                        </div>
                    </header>
                </BaseCard>

                <div v-if="toast" class="mb-4 rounded-xl bg-primary/10 p-4 border border-primary/20 text-sm text-primary-strong flex items-center justify-between">
                    <span>{{ toast }}</span>
                    <button type="button" @click="toast = null" class="font-bold text-xs">✕</button>
                </div>

                <div class="flex flex-col lg:flex-row gap-6">
                    
                    <!-- Columna Principal: Timer y Métricas -->
                    <div class="flex-1 min-w-0">
                        
                        <!-- Banner de Objetivo Seleccionado -->
                        <div v-if="activeTargetInfo" class="mb-4 p-3.5 rounded-2xl bg-gradient-to-r from-primary/15 via-primary/5 to-transparent border border-primary/25 flex items-center justify-between gap-3 shadow-sm">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-primary-strong text-white flex items-center justify-center shrink-0 shadow">
                                    <AppIcon :name="activeTargetInfo.icon" :size="18" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-primary-strong">
                                            {{ activeTargetInfo.type === 'mission' ? 'Misión Activa' : (activeTargetInfo.type === 'course' ? 'Curso Activo' : (activeTargetInfo.type === 'reading' ? 'Lectura Activa' : 'Destreza Activa')) }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-bold text-content-primary truncate">{{ activeTargetInfo.label }}</h4>
                                    <p class="text-xs text-content-secondary font-medium">{{ activeTargetInfo.subtext }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="clearTarget"
                                class="shrink-0 px-2.5 py-1 rounded-lg text-xs font-semibold text-content-muted hover:text-danger hover:bg-danger/10 transition cursor-pointer"
                            >
                                Cambiar
                            </button>
                        </div>

                        <!-- Card de Meta Diaria -->
                        <BaseCard v-if="hasGoal" class="mb-6">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-content-primary">
                                    Meta de hoy: {{ formatMinutesLabel(totalFocusMinutesToday) }} / {{ formatMinutesLabel(goalMinutesInput) }}
                                </span>
                                <span class="text-xs text-content-secondary font-bold">
                                    Ciclo {{ todayCompletedCount + 1 }}
                                </span>
                            </div>
                            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-surface-sunken">
                                <div
                                    class="h-full rounded-full bg-primary-strong transition-[width] duration-500"
                                    :style="{ width: goalProgressPercent + '%' }"
                                />
                            </div>
                            <p v-if="exceedsDailyXpCap" class="mt-2 text-xs text-content-muted">
                                Con {{ plannedMinutesInput }} min de foco vas a necesitar ~{{ projectedSessionsForGoal }} sesiones para llegar a la meta.
                            </p>
                        </BaseCard>

                        <!-- Card del Temporizador -->
                        <BaseCard class="mb-6 text-center py-6">
                            <template v-if="session || onBreak">
                                <div class="relative mx-auto my-4" style="width: 170px; height: 170px">
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
                                        <p class="font-display text-3xl font-black tabular-nums text-content-primary">
                                            {{ onBreak ? formattedTime : timeLabel }}
                                        </p>
                                        <p class="mt-1 text-xs font-bold uppercase tracking-wider" :class="onBreak ? 'text-success' : 'text-primary-strong'">
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
                                    <p class="mt-2 text-sm text-content-secondary font-medium">
                                        {{ session?.planned_minutes || 25 }} min planificados
                                        <span v-if="activeTargetInfo" class="font-bold text-content-primary"> · {{ activeTargetInfo.label }}</span>
                                    </p>
                                    <div class="mt-6 flex justify-center gap-3 flex-wrap">
                                        <BaseButton
                                            v-if="!isPaused"
                                            variant="ghost"
                                            :disabled="busy"
                                            @click="callAction('pause')"
                                        >
                                            ⏸ Pausar
                                        </BaseButton>
                                        <BaseButton v-else :disabled="busy" @click="callAction('resume')">
                                            ▶ Reanudar
                                        </BaseButton>
                                        <BaseButton
                                            variant="ghost"
                                            class="text-danger hover:bg-danger/10"
                                            :disabled="busy"
                                            @click="callAction('abandon')"
                                        >
                                            ✕ Abandonar
                                        </BaseButton>
                                        <BaseButton
                                            variant="primary"
                                            :disabled="busy"
                                            @click="callAction('complete')"
                                        >
                                            ✓ Terminar Bloque
                                        </BaseButton>
                                    </div>
                                </template>
                            </template>

                            <template v-else-if="goalJustCompleted">
                                <div class="py-4">
                                    <div class="text-4xl mb-2">🎉</div>
                                    <h3 class="font-display text-2xl font-black text-content-primary">
                                        ¡Meta diaria cumplida!
                                    </h3>
                                    <p class="mt-2 text-sm text-content-secondary max-w-md mx-auto">
                                        Completaste {{ formatMinutesLabel(totalFocusMinutesToday) }} de foco en {{ todayCompletedCount }} ciclos hoy.
                                    </p>
                                    <div class="mt-6 flex justify-center gap-3">
                                        <BaseButton :disabled="busy" @click="continueStudying">
                                            Seguir estudiando (+{{ plannedMinutesInput }} min)
                                        </BaseButton>
                                        <BaseButton variant="ghost" :disabled="busy" @click="chooseAnotherGoal">
                                            Aumentar meta
                                        </BaseButton>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="breakJustFinished">
                                <div class="py-4">
                                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-success/15 px-3.5 py-1 text-xs font-bold text-success">
                                        ☕ Descanso finalizado
                                    </div>
                                    <h3 class="font-display text-2xl font-bold text-content-primary">
                                        ¿Listo para tu siguiente bloque?
                                    </h3>
                                    <p class="mt-2 text-sm text-content-secondary max-w-md mx-auto">
                                        Llevas {{ formatMinutesLabel(totalFocusMinutesToday) }} de foco completados hoy.
                                    </p>
                                    <div class="mt-6 flex justify-center gap-3">
                                        <BaseButton :disabled="busy" @click="startSession">
                                            ▶ Iniciar Siguiente Bloque ({{ plannedMinutesInput }} min)
                                        </BaseButton>
                                        <BaseButton variant="ghost" :disabled="busy" @click="breakJustFinished = false">
                                            ⚙️ Ajustar tiempos
                                        </BaseButton>
                                    </div>
                                </div>
                            </template>

                            <template v-else>
                                <p class="mb-4 text-sm font-semibold text-content-secondary">
                                    Configura los minutos de foco y descanso para tu sesión:
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
                                
                                <div class="mx-auto grid max-w-md mt-3">
                                    <BaseSelect
                                        id="context_type"
                                        v-model="selectedContextType"
                                        label="Categoría / Módulo a enfocar"
                                        compact
                                        :options="CONTEXT_OPTIONS"
                                    />
                                </div>

                                <p class="mx-auto mt-3 max-w-md text-xs text-content-muted">
                                    {{ breakHint }}
                                </p>

                                <div class="mt-6">
                                    <BaseButton size="lg" class="shadow-lg shadow-primary/25 px-8" :disabled="busy" @click="startSession">
                                        🍅 Iniciar Pomodoro
                                    </BaseButton>
                                </div>
                            </template>
                        </BaseCard>

                        <!-- Música Lo-Fi -->
                        <BaseCard class="mb-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-sm text-content-primary">
                                        🎵 Música para concentrarte
                                    </h3>
                                    <p class="text-xs text-content-secondary">
                                        Reproductor YouTube integrado para música ambiental o Lo-Fi.
                                    </p>
                                </div>
                                <BaseButton
                                    v-if="!musicVisible"
                                    variant="secondary"
                                    size="sm"
                                    @click="musicVisible = true"
                                >
                                    🎵 Activar música
                                </BaseButton>
                                <BaseButton v-else variant="ghost" size="sm" @click="stopMusic">
                                    Detener música
                                </BaseButton>
                            </div>

                            <div v-if="musicVisible" class="mt-4">
                                <div class="aspect-video w-full overflow-hidden rounded-xl bg-surface-sunken">
                                    <iframe
                                        :src="playlistEmbedUrl"
                                        class="h-full w-full"
                                        title="Reproductor de música"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                    ></iframe>
                                </div>

                                <div class="mt-3 flex flex-wrap items-end gap-2">
                                    <div class="min-w-[200px] flex-1">
                                        <BaseInput
                                            id="custom_playlist"
                                            v-model="customPlaylistInput"
                                            placeholder="Pega link o ID de YouTube..."
                                            compact
                                        />
                                    </div>
                                    <BaseButton size="sm" @click="useCustomPlaylist">Guardar</BaseButton>
                                    <BaseButton size="sm" variant="ghost" @click="resetToDefaultPlaylist">Restaurar</BaseButton>
                                </div>
                                <p v-if="musicError" class="text-xs text-danger mt-1">{{ musicError }}</p>
                            </div>
                        </BaseCard>

                        <!-- Estadísticas y Reporte -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                            <BaseCard class="p-4 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-content-muted">Completadas</p>
                                <p class="font-display text-2xl font-black text-content-primary mt-1">{{ stats.sessionsCompleted }}</p>
                            </BaseCard>
                            <BaseCard class="p-4 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-content-muted">Efectividad</p>
                                <p class="font-display text-2xl font-black text-primary-strong mt-1">{{ stats.completionRate }}%</p>
                            </BaseCard>
                            <BaseCard class="p-4 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-content-muted">Minutos Foco</p>
                                <p class="font-display text-2xl font-black text-content-primary mt-1">{{ stats.focusMinutesTotal }}</p>
                            </BaseCard>
                            <BaseCard class="p-4 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-content-muted">Iniciadas</p>
                                <p class="font-display text-2xl font-black text-content-secondary mt-1">{{ stats.sessionsStarted }}</p>
                            </BaseCard>
                        </div>
                    </div>

                    <!-- Columna Lateral: Cuadros de Contexto (Misiones, Cursos, Lecturas, Habilidades) -->
                    <div class="w-full lg:w-96 shrink-0 space-y-6">
                        
                        <!-- 🎯 CUADRO DE MISIONES -->
                        <BaseCard v-if="['mission', 'work', 'personal', 'free'].includes(selectedContextType)">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                                    <span>🎯</span> Misiones Activas ({{ missions.length }})
                                </h3>
                                <Link :href="route('missions.index')" class="text-xs font-bold text-primary-strong hover:underline">
                                    Ver todas →
                                </Link>
                            </div>

                            <!-- Pestañas de filtrado de misiones -->
                            <div class="flex items-center gap-1 mb-3 overflow-x-auto pb-1 text-xs">
                                <button
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                    :class="missionFilterTab === 'all' ? 'bg-primary-strong text-white' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                                    @click="missionFilterTab = 'all'"
                                >
                                    Todas ({{ missions.length }})
                                </button>
                                <button
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                    :class="missionFilterTab === 'academic' ? 'bg-primary-strong text-white' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                                    @click="missionFilterTab = 'academic'"
                                >
                                    Académicas ({{ missions.filter(m => m.mission_type === 'academic').length }})
                                </button>
                                <button
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                    :class="missionFilterTab === 'work' ? 'bg-primary-strong text-white' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                                    @click="missionFilterTab = 'work'"
                                >
                                    Trabajo ({{ missions.filter(m => m.mission_type === 'work').length }})
                                </button>
                                <button
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer"
                                    :class="missionFilterTab === 'personal' ? 'bg-primary-strong text-white' : 'bg-surface-raised text-content-secondary hover:text-content-primary'"
                                    @click="missionFilterTab = 'personal'"
                                >
                                    Personal ({{ missions.filter(m => m.mission_type === 'personal').length }})
                                </button>
                            </div>

                            <div v-if="filteredMissions.length === 0" class="text-center py-6 text-xs text-content-muted">
                                No hay misiones registradas en esta categoría.
                            </div>

                            <div v-else class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                                <div
                                    v-for="m in filteredMissions"
                                    :key="m.id"
                                    class="rounded-xl border transition-all cursor-pointer overflow-hidden"
                                    :class="
                                        selectedMissionId === m.id
                                            ? 'border-primary-strong bg-primary/5 ring-1 ring-primary-strong'
                                            : 'border-border bg-surface hover:border-primary/50'
                                    "
                                    @click="selectMission(m.id)"
                                >
                                    <div class="p-3 flex items-start gap-2.5">
                                        <div 
                                            class="w-4 h-4 rounded-full border-2 mt-0.5 flex items-center justify-center shrink-0 transition"
                                            :class="selectedMissionId === m.id ? 'border-primary-strong bg-primary-strong' : 'border-content-muted'"
                                        >
                                            <div v-if="selectedMissionId === m.id" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="text-xs font-bold text-content-primary truncate" :class="selectedMissionId === m.id ? 'text-primary-strong' : ''">
                                                    {{ m.title }}
                                                </span>
                                                <span v-if="m.subtask_count > 0" class="text-[10px] font-bold text-content-muted shrink-0">
                                                    {{ m.subtask_done }}/{{ m.subtask_count }}
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                                <span v-if="m.priority === 'high'" class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-danger/15 text-danger">
                                                    🔴 Alta
                                                </span>
                                                <span v-if="m.eisenhower_quadrant" class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-surface-raised text-content-secondary uppercase">
                                                    {{ m.eisenhower_quadrant }}
                                                </span>
                                                <span v-if="m.due_date" class="px-1.5 py-0.2 text-[9px] font-medium rounded text-content-muted">
                                                    📅 {{ m.due_date }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Subtareas interactivas -->
                                    <div
                                        v-if="expandedMissionId === m.id && m.subtasks && m.subtasks.length > 0"
                                        class="border-t border-border bg-surface-sunken px-3 py-2 space-y-1.5"
                                        @click.stop
                                    >
                                        <div
                                            v-for="sub in m.subtasks"
                                            :key="sub.id"
                                            class="flex items-center gap-2 text-xs"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="sub.is_completed"
                                                :disabled="subtaskBusy[sub.id]"
                                                class="h-3.5 w-3.5 rounded border-border text-primary-strong focus:ring-primary-strong cursor-pointer"
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </BaseCard>

                        <!-- 🎓 CUADRO DE CURSOS -->
                        <BaseCard v-if="['course', 'free'].includes(selectedContextType)">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                                    <span>🎓</span> Cursos & Asignaturas ({{ courses.length }})
                                </h3>
                                <Link :href="route('courses.index')" class="text-xs font-bold text-primary-strong hover:underline">
                                    Hub Cursos →
                                </Link>
                            </div>

                            <div v-if="courses.length === 0" class="text-center py-6 text-xs text-content-muted">
                                No tienes cursos registrados aún.
                            </div>

                            <div v-else class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                                <div
                                    v-for="c in courses"
                                    :key="c.id"
                                    class="p-3 rounded-xl border transition-all cursor-pointer flex items-center gap-3"
                                    :class="
                                        selectedCourseId === c.id
                                            ? 'border-primary-strong bg-primary/5 ring-1 ring-primary-strong'
                                            : 'border-border bg-surface hover:border-primary/50'
                                    "
                                    @click="selectCourse(c.id)"
                                >
                                    <div 
                                        class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition"
                                        :class="selectedCourseId === c.id ? 'border-primary-strong bg-primary-strong' : 'border-content-muted'"
                                    >
                                        <div v-if="selectedCourseId === c.id" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                    </div>

                                    <div 
                                        class="w-7 h-7 rounded-lg text-white font-bold text-[10px] flex items-center justify-center shrink-0 shadow-sm"
                                        :style="{ backgroundColor: c.color || '#3b82f6' }"
                                    >
                                        {{ c.name.substring(0, 2).toUpperCase() }}
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-content-primary truncate">{{ c.name }}</h4>
                                        <p class="text-[10px] text-content-secondary truncate">
                                            {{ c.professor ? `👨‍🏫 ${c.professor}` : 'Sin profesor' }}
                                        </p>
                                    </div>

                                    <span v-if="c.credits" class="text-[10px] font-bold text-primary-strong shrink-0 px-2 py-0.5 rounded bg-primary/10">
                                        {{ c.credits }} cr.
                                    </span>
                                </div>
                            </div>
                        </BaseCard>

                        <!-- 📚 CUADRO DE LECTURAS -->
                        <BaseCard v-if="['reading', 'free'].includes(selectedContextType)">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                                    <span>📚</span> Lecturas en Curso ({{ readings.length }})
                                </h3>
                                <Link :href="route('readings.index')" class="text-xs font-bold text-primary-strong hover:underline">
                                    Biblioteca →
                                </Link>
                            </div>

                            <div v-if="readings.length === 0" class="text-center py-6 text-xs text-content-muted">
                                No tienes libros en curso.
                            </div>

                            <div v-else class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                                <div
                                    v-for="r in readings"
                                    :key="r.id"
                                    class="p-3 rounded-xl border transition-all cursor-pointer flex items-center gap-3"
                                    :class="
                                        selectedReadingId === r.id
                                            ? 'border-primary-strong bg-primary/5 ring-1 ring-primary-strong'
                                            : 'border-border bg-surface hover:border-primary/50'
                                    "
                                    @click="selectReading(r.id)"
                                >
                                    <div 
                                        class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition"
                                        :class="selectedReadingId === r.id ? 'border-primary-strong bg-primary-strong' : 'border-content-muted'"
                                    >
                                        <div v-if="selectedReadingId === r.id" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-content-primary truncate">{{ r.title }}</h4>
                                        <p class="text-[10px] text-content-secondary truncate">{{ r.author || 'Autor desconocido' }}</p>
                                        
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <div class="flex-1 h-1.5 bg-surface-sunken rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full bg-indigo-500 rounded-full"
                                                    :style="{ width: Math.min(100, Math.round((r.current_page / (r.total_pages || 1)) * 100)) + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-[10px] font-semibold text-content-muted shrink-0">
                                                {{ r.current_page }}/{{ r.total_pages }} pág.
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </BaseCard>

                        <!-- ⚡ CUADRO DE HABILIDADES -->
                        <BaseCard v-if="['skill', 'free'].includes(selectedContextType)">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-sm text-content-primary flex items-center gap-2">
                                    <span>⚡</span> Destrezas & Habilidades ({{ skills.length }})
                                </h3>
                                <Link :href="route('skills.index')" class="text-xs font-bold text-primary-strong hover:underline">
                                    Árbol →
                                </Link>
                            </div>

                            <div v-if="skills.length === 0" class="text-center py-6 text-xs text-content-muted">
                                No has creado habilidades aún.
                            </div>

                            <div v-else class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                                <div
                                    v-for="s in skills"
                                    :key="s.id"
                                    class="p-3 rounded-xl border transition-all cursor-pointer flex items-center gap-3"
                                    :class="
                                        selectedSkillId === s.id
                                            ? 'border-primary-strong bg-primary/5 ring-1 ring-primary-strong'
                                            : 'border-border bg-surface hover:border-primary/50'
                                    "
                                    @click="selectSkill(s.id)"
                                >
                                    <div 
                                        class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition"
                                        :class="selectedSkillId === s.id ? 'border-primary-strong bg-primary-strong' : 'border-content-muted'"
                                    >
                                        <div v-if="selectedSkillId === s.id" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <h4 class="text-xs font-bold text-content-primary truncate">{{ s.name }}</h4>
                                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-500/15 text-amber-600 shrink-0">
                                                Niv. {{ s.current_level }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-content-secondary mt-0.5 capitalize">{{ s.category }}</p>
                                    </div>
                                </div>
                            </div>
                        </BaseCard>

                        <!-- Historial de Pomodoros de Hoy -->
                        <BaseCard>
                            <h3 class="font-bold text-sm text-content-primary mb-3 flex items-center gap-2">
                                <span>📋</span> Ciclos Completados Hoy
                            </h3>
                            <p v-if="todaySessions.length === 0" class="text-xs text-content-secondary">
                                Todavía no has realizado ninguna sesión hoy.
                            </p>
                            <ul v-else class="divide-y divide-border text-xs">
                                <li
                                    v-for="s in todaySessions"
                                    :key="s.id"
                                    class="flex items-center justify-between py-2"
                                >
                                    <span class="text-content-secondary">
                                        {{ new Date(s.started_at).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                    <span class="text-content-primary font-semibold">
                                        {{ s.focus_minutes ?? s.planned_minutes }} min
                                    </span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :class="s.status === 'completed' ? 'bg-success/15 text-success' : 'bg-surface-sunken text-content-muted'"
                                    >
                                        {{ statusLabel(s.status) }}
                                    </span>
                                </li>
                            </ul>
                        </BaseCard>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
