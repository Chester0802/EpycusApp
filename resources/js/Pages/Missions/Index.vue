<script setup>
import { computed, ref, reactive, onMounted } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import UsageTipBanner from '@/Components/ui/UsageTipBanner.vue';
import { useTelemetry } from '@/Composables/useTelemetry';

const props = defineProps({
    missions: { type: Array, default: () => [] },
    completedMissions: { type: Array, default: () => [] },
    todayDate: { type: String, required: true },
    sortBy: { type: String, default: 'default' },
    avatarStyle: { type: String, default: 'base' },
    avatarGender: { type: String, default: 'm' },
    progress: { type: Object, default: () => ({ phase: 1 }) },
});

const { track } = useTelemetry();

const viewMode = ref('matrix'); // 'matrix' | 'kanban' | 'list'
const showGuide = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showCompleted = ref(false);
const editingMission = ref(null);
const editingSubtaskId = ref(null);
const editingSubtaskTitle = ref('');
const addSubtaskTitles = reactive({});
const dragSubtaskId = ref(null);

onMounted(() => {
    const saved = localStorage.getItem('epycus_missions_view');
    if (saved === 'list' || saved === 'matrix' || saved === 'kanban') {
        viewMode.value = saved;
    }
});

function setViewMode(mode) {
    const oldMode = viewMode.value;
    viewMode.value = mode;
    localStorage.setItem('epycus_missions_view', mode);
    track('missions.view_switched', 'missions', { from_view: oldMode, to_view: mode });
}

const difficultyOptions = [
    { value: 'easy', label: 'Fácil' },
    { value: 'medium', label: 'Media' },
    { value: 'hard', label: 'Difícil' },
];

const priorityOptions = [
    { value: 'baja', label: 'Baja' },
    { value: 'normal', label: 'Normal' },
    { value: 'alta', label: 'Alta' },
];

const difficultyConfig = {
    easy: { label: 'Fácil', class: 'bg-success/20 text-success' },
    medium: { label: 'Media', class: 'bg-accent/20 text-accent' },
    hard: { label: 'Difícil', class: 'bg-danger/20 text-danger' },
};

const priorityConfig = {
    baja: { label: '↓ Baja', class: 'text-content-muted' },
    normal: { label: '— Normal', class: 'text-content-secondary' },
    alta: { label: '↑ Alta', class: 'text-danger-text' },
};

const eisenhowerConfig = {
    q1: {
        id: 'q1',
        title: 'Q1: Hacer YA',
        subtitle: 'Urgente e Importante (Crisis)',
        badge: 'Hacer YA',
        icon: 'flame',
        color: 'text-danger',
        bgBadge: 'bg-danger/15 text-danger border border-danger/30',
        bgHeader: 'bg-danger/10 border-danger/30 text-danger',
        borderCard: 'border-l-4 border-l-danger',
        postitClass: 'bg-rose-100 dark:bg-rose-950/90 border-rose-300 dark:border-rose-700 text-rose-950 dark:text-rose-100 shadow-md',
        postitTape: 'bg-rose-300/80 dark:bg-rose-400/40 border-rose-400/50',
        description: 'Exámenes próximos, tareas que vencen hoy y entregas críticas.',
        tip: 'Apaga estos incendios primero para liberar tu mente.',
    },
    q2: {
        id: 'q2',
        title: 'Q2: Planificar',
        subtitle: 'No Urgente pero Importante (Estratégico)',
        badge: 'Estratégico',
        icon: 'target',
        color: 'text-success',
        bgBadge: 'bg-success/20 text-success font-black border border-success/40',
        bgHeader: 'bg-success/15 border-success/40 text-success',
        borderCard: 'border-l-4 border-l-success ring-1 ring-success/30',
        postitClass: 'bg-emerald-100 dark:bg-emerald-950/90 border-emerald-300 dark:border-emerald-700 text-emerald-950 dark:text-emerald-100 shadow-md ring-1 ring-emerald-500/30',
        postitTape: 'bg-emerald-300/80 dark:bg-emerald-400/40 border-emerald-400/50',
        description: 'Estudio espaciado, lecturas, repasos y tesis. ¡El núcleo anti-procrastinación!',
        tip: 'Dedica el 70% de tu tiempo aquí para evitar que las tareas se vuelvan crisis en Q1.',
    },
    q3: {
        id: 'q3',
        title: 'Q3: Minimizar',
        subtitle: 'Urgente pero No Importante (Operativo)',
        badge: 'Minimizar',
        icon: 'clock',
        color: 'text-warning',
        bgBadge: 'bg-warning/20 text-warning font-black border border-warning/40',
        bgHeader: 'bg-warning/15 border-warning/40 text-warning',
        borderCard: 'border-l-4 border-l-warning',
        postitClass: 'bg-amber-100 dark:bg-amber-950/90 border-amber-300 dark:border-amber-700 text-amber-950 dark:text-amber-100 shadow-md',
        postitTape: 'bg-amber-300/80 dark:bg-amber-400/40 border-amber-400/50',
        description: 'Trámites, consultas rápidas, correos y tareas secundarias.',
        tip: 'Agrúpalas y resuélvelas en bloques cortos de 15 minutos.',
    },
    q4: {
        id: 'q4',
        title: 'Q4: Descartar',
        subtitle: 'Ni Urgente ni Importante (Distracción)',
        badge: 'Descartar',
        icon: 'archive',
        color: 'text-content-muted',
        bgBadge: 'bg-surface-raised text-content-muted border border-border-interactive',
        bgHeader: 'bg-surface-raised border-border-interactive text-content-secondary',
        borderCard: 'border-l-4 border-l-border-interactive opacity-85',
        postitClass: 'bg-slate-100 dark:bg-slate-900/90 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 shadow-md',
        postitTape: 'bg-slate-300/80 dark:bg-slate-500/40 border-slate-400/50',
        description: 'Actividades de bajo valor o pendientes prescindibles.',
        tip: 'Si no aporta a tu aprendizaje o bienestar, considera eliminarla.',
    },
};

const overdueCount = computed(() => props.missions.filter((m) => m.is_overdue).length);
const activeCount = computed(() => props.missions.length);

// Cronograma / Lista Computeds
const todayMissions = computed(() =>
    props.missions.filter((m) => m.due_date === props.todayDate && !m.is_overdue),
);

const dueSoon = computed(() => {
    const today = new Date(props.todayDate);
    const weekEnd = new Date(today);
    weekEnd.setDate(weekEnd.getDate() + 7);
    return props.missions.filter(
        (m) =>
            m.due_date &&
            !m.is_overdue &&
            m.due_date > props.todayDate &&
            new Date(m.due_date) <= weekEnd,
    );
});

const restMissions = computed(() => {
    const today = new Date(props.todayDate);
    const weekEnd = new Date(today);
    weekEnd.setDate(weekEnd.getDate() + 7);
    return props.missions
        .filter((m) => !m.due_date || new Date(m.due_date) > weekEnd)
        .filter((m) => !m.is_overdue);
});

const overdueMissions = computed(() => props.missions.filter((m) => m.is_overdue));

// Eisenhower Quadrants Computeds
const q1Missions = computed(() => props.missions.filter((m) => m.eisenhower_quadrant === 'q1'));
const q2Missions = computed(() =>
    props.missions.filter((m) => m.eisenhower_quadrant === 'q2' || !m.eisenhower_quadrant),
);
const q3Missions = computed(() => props.missions.filter((m) => m.eisenhower_quadrant === 'q3'));
const q4Missions = computed(() => props.missions.filter((m) => m.eisenhower_quadrant === 'q4'));

// Kanban 4 Columns Computeds:
// 1. Por Hacer: sin subtareas iniciadas y no en revisión
const kanbanBacklog = computed(() =>
    props.missions.filter(
        (m) => m.subtask_done === 0 && (m.subtask_count === 0 || m.state === 'pending'),
    ),
);

// 2. En Proceso: al menos 1 subtarea hecha, pero no todas
const kanbanInProgress = computed(() =>
    props.missions.filter(
        (m) =>
            (m.subtask_done > 0 && m.subtask_done < m.subtask_count) ||
            (m.subtask_count === 0 && (m.state === 'in_progress' || m.is_overdue)),
    ),
);

// 3. En Revisión: todas las subtareas hechas (listo para reclamar XP)
const kanbanInReview = computed(() =>
    props.missions.filter((m) => m.subtask_count > 0 && m.subtask_done === m.subtask_count),
);

const createForm = useForm({
    title: '',
    description: '',
    difficulty: 'medium',
    priority: 'normal',
    eisenhower_quadrant: 'q2',
    due_date: '',
    subtasks: [''],
});

const editForm = useForm({
    title: '',
    description: '',
    difficulty: 'medium',
    priority: 'normal',
    eisenhower_quadrant: 'q2',
    due_date: '',
});

function addSubtaskField() {
    if (createForm.subtasks.length < 20) {
        createForm.subtasks.push('');
    }
}

function removeSubtaskField(index) {
    if (createForm.subtasks.length > 1) {
        createForm.subtasks.splice(index, 1);
    }
}

function openCreateModal(initialQuadrant = 'q2') {
    createForm.reset();
    createForm.eisenhower_quadrant = initialQuadrant;
    createForm.subtasks = [''];
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createForm.reset();
}

function submitCreate() {
    const subtasks = createForm.subtasks.filter((s) => s.trim().length > 0);
    createForm.subtasks = subtasks;
    createForm.post(route('missions.store'), {
        onSuccess: () => {
            track('missions.created', 'missions', {
                difficulty: createForm.difficulty,
                priority: createForm.priority,
                quadrant: createForm.eisenhower_quadrant,
                has_due_date: Boolean(createForm.due_date),
                subtasks_count: subtasks.length,
            });
            closeCreateModal();
        },
    });
}

function openEditModal(mission) {
    editingMission.value = mission;
    editForm.title = mission.title;
    editForm.description = mission.description || '';
    editForm.difficulty = mission.difficulty;
    editForm.priority = mission.priority;
    editForm.eisenhower_quadrant = mission.eisenhower_quadrant || 'q2';
    editForm.due_date = mission.due_date || '';
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingMission.value = null;
    editForm.reset();
}

function submitEdit() {
    editForm.patch(route('missions.update', { id: editingMission.value.id }), {
        onSuccess: () => closeEditModal(),
    });
}

function quickChangeQuadrant(missionId, newQuadrant) {
    const mission = props.missions.find((m) => m.id === missionId);
    const oldQuadrant = mission?.eisenhower_quadrant || 'q2';
    if (mission) {
        mission.eisenhower_quadrant = newQuadrant;
    }
    track('missions.quadrant_changed', 'missions', {
        mission_id: missionId,
        from_quadrant: oldQuadrant,
        to_quadrant: newQuadrant,
    });
    router.post(
        route('missions.quadrant', { id: missionId }),
        { quadrant: newQuadrant },
        { preserveScroll: true, preserveState: true },
    );
}

function completeMission(missionId) {
    track('missions.completed_triggered', 'missions', { mission_id: missionId });
    router.post(route('missions.complete', { id: missionId }), {}, { preserveScroll: true });
}

function uncompleteMission(missionId) {
    track('missions.uncompleted_triggered', 'missions', { mission_id: missionId });
    router.post(route('missions.uncomplete', { id: missionId }), {}, { preserveScroll: true });
}

function toggleSubtask(missionId, subtaskId) {
    const mission = props.missions.find((m) => m.id === missionId);
    if (mission) {
        const subtask = mission.subtasks.find((s) => s.id === subtaskId);
        if (subtask) {
            subtask.is_completed = !subtask.is_completed;
            mission.subtask_done = mission.subtasks.filter((s) => s.is_completed).length;
            mission.state =
                mission.subtask_done > 0
                    ? 'in_progress'
                    : mission.is_overdue
                      ? 'overdue'
                      : 'pending';
        }
    }
    router.post(
        route('missions.subtasks.toggle', { id: missionId, subtaskId }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

function startEditSubtask(subtask) {
    editingSubtaskId.value = subtask.id;
    editingSubtaskTitle.value = subtask.title;
}

function saveEditSubtask(missionId, subtaskId) {
    const title = editingSubtaskTitle.value.trim();
    if (!title) {
        editingSubtaskId.value = null;
        return;
    }
    router.patch(
        route('missions.subtasks.update', { id: missionId, subtaskId }),
        { title },
        { preserveScroll: true, preserveState: true },
    );
    editingSubtaskId.value = null;
}

function cancelEditSubtask() {
    editingSubtaskId.value = null;
}

function addSubtask(missionId) {
    const title = (addSubtaskTitles[missionId] || '').trim();
    if (!title) return;
    router.post(
        route('missions.subtasks.store', { id: missionId }),
        { title },
        { preserveScroll: true, preserveState: true },
    );
    addSubtaskTitles[missionId] = '';
}

function onDragStart(subtaskId) {
    dragSubtaskId.value = subtaskId;
}

function onDragOver(e) {
    e.preventDefault();
}

function onDrop(missionId, targetSubtaskId) {
    if (dragSubtaskId.value === null || dragSubtaskId.value === targetSubtaskId) {
        dragSubtaskId.value = null;
        return;
    }
    const mission = props.missions.find((m) => m.id === missionId);
    if (!mission) return;
    const ids = mission.subtasks.map((s) => s.id);
    const fromIdx = ids.indexOf(dragSubtaskId.value);
    const toIdx = ids.indexOf(targetSubtaskId);
    if (fromIdx === -1 || toIdx === -1) return;
    ids.splice(fromIdx, 1);
    ids.splice(toIdx, 0, dragSubtaskId.value);
    dragSubtaskId.value = null;
    router.post(
        route('missions.subtasks.reorder', { id: missionId }),
        { ordered_ids: ids },
        { preserveScroll: true, preserveState: true },
    );
}

function changeSort(sort) {
    router.get(route('missions.index', { sort_by: sort === 'default' ? undefined : sort }), {
        preserveScroll: true,
        preserveState: true,
    });
}

function deleteMission(missionId) {
    if (confirm('¿Eliminar esta misión? Se conservará su historial.')) {
        track('missions.deleted', 'missions', { mission_id: missionId });
        router.delete(route('missions.destroy', { id: missionId }));
    }
}

function startPomodoro(missionId) {
    track('missions.pomodoro_started', 'missions', { mission_id: missionId });
    router.visit(route('pomodoro.index'), {
        data: { mission_id: missionId },
    });
}

function stateClass(state) {
    const classes = {
        overdue: 'border-l-danger',
        pending: 'border-l-border-interactive',
        in_progress: 'border-l-primary-strong',
        completed: 'border-l-success',
    };
    return classes[state] || 'border-l-border-interactive';
}
</script>

<template>
    <Head title="Misiones — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Tip de uso dinámico -->
            <UsageTipBanner module="missions" />

            <!-- Header Card -->
            <BaseCard class="p-6">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-interactive bg-surface-raised/40 p-2 shadow-sm"
                        >
                            <img
                                src="/assets/gifs/missions.gif"
                                alt="Misiones y Objetivos"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">
                                Misiones
                            </h1>
                            <p class="mt-1 text-sm text-content-secondary">
                                Prioriza con impacto, organiza tu flujo y divide tus retos en pasos alcanzables.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Selector de 3 Vistas: Matriz / Kanban / Cronograma -->
                        <div class="inline-flex rounded-xl bg-surface-raised p-1 border border-border-interactive shadow-xs">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                                :class="
                                    viewMode === 'matrix'
                                        ? 'bg-primary-strong text-on-accent shadow-sm'
                                        : 'text-content-secondary hover:text-content-primary'
                                "
                                title="Matriz de Urgencia vs Importancia"
                                @click="setViewMode('matrix')"
                            >
                                <AppIcon name="layout-grid" :size="14" /> Matriz
                            </button>
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                                :class="
                                    viewMode === 'kanban'
                                        ? 'bg-primary-strong text-on-accent shadow-sm'
                                        : 'text-content-secondary hover:text-content-primary'
                                "
                                title="Tablero de flujo con Post-its"
                                @click="setViewMode('kanban')"
                            >
                                <AppIcon name="columns" :size="14" /> Kanban
                            </button>
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                                :class="
                                    viewMode === 'list'
                                        ? 'bg-primary-strong text-on-accent shadow-sm'
                                        : 'text-content-secondary hover:text-content-primary'
                                "
                                title="Lista ordenada por fechas"
                                @click="setViewMode('list')"
                            >
                                <AppIcon name="list" :size="14" /> Cronograma
                            </button>
                        </div>

                        <select
                            v-if="viewMode === 'list'"
                            class="rounded-xl border border-border-interactive bg-surface px-3 py-1.5 text-xs font-medium text-content-secondary outline-none shadow-xs"
                            :value="sortBy"
                            @change="changeSort($event.target.value)"
                        >
                            <option value="default">Orden por defecto</option>
                            <option value="priority">Por prioridad</option>
                            <option value="difficulty">Por dificultad</option>
                            <option value="created_at">Por creación</option>
                        </select>

                        <BaseButton variant="primary" @click="openCreateModal('q2')">
                            + Nueva Misión
                        </BaseButton>
                    </div>
                </header>
            </BaseCard>

            <!-- Banner Pedagógico de la Matriz de Eisenhower y Flujo Kanban -->
            <BaseCard class="border-l-4 border-l-primary-strong p-4 text-sm text-content-secondary">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2">
                        <AppIcon name="lightbulb" :size="18" class="text-warning shrink-0 mt-0.5" />
                        <div>
                            <p class="font-semibold text-content-primary">
                                Método Eisenhower & Kanban contra la Procrastinación
                            </p>
                            <p class="mt-0.5 text-xs">
                                Usa la <strong class="text-success">Matriz</strong> para priorizar (<span class="text-success">Q2 es la clave preventiva</span>) y el <strong class="text-primary-strong">Tablero Kanban</strong> para avanzar paso a paso: <em>Por Hacer ➔ En Proceso ➔ En Revisión ➔ Terminado</em>.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="text-xs font-semibold text-primary-strong hover:underline shrink-0 cursor-pointer"
                        @click="showGuide = !showGuide"
                    >
                        {{ showGuide ? 'Ocultar guía' : '¿Cómo funciona?' }}
                    </button>
                </div>

                <!-- Detalle Colapsable -->
                <div v-if="showGuide" class="mt-4 pt-3 border-t border-border-interactive grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="p-2.5 rounded-xl bg-danger/5 border border-danger/20">
                        <p class="font-bold text-danger flex items-center gap-1">🔴 Q1: Hacer YA</p>
                        <p class="text-content-secondary mt-1">Urgente + Importante. Entregas inminentes y exámenes mañana. Resuélvelas de inmediato.</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-success/5 border border-success/20">
                        <p class="font-bold text-success flex items-center gap-1">🟢 Q2: Planificar (Clave)</p>
                        <p class="text-content-secondary mt-1">Importante + No Urgente. Repasos, tesis y hábitos. ¡El 70% de tu éxito vive aquí!</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-warning/5 border border-warning/20">
                        <p class="font-bold text-warning flex items-center gap-1">🟡 Q3: Minimizar</p>
                        <p class="text-content-secondary mt-1">Urgente + No Importante. Trámites y consultas rápidas. Resuélvelas en bloques de 15 min.</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-raised border border-border-interactive">
                        <p class="font-bold text-content-muted flex items-center gap-1">⚪ Q4: Descartar</p>
                        <p class="text-content-secondary mt-1">Ni urgente ni importante. Tareas de bajo valor o distracciones. Evalúa si vale la pena hacerlas.</p>
                    </div>
                </div>
            </BaseCard>

            <!-- Resumen de Métricas -->
            <div v-if="missions.length > 0 || completedMissions.length > 0" class="grid grid-cols-3 gap-4">
                <BaseCard class="p-4 text-center">
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ activeCount }}
                    </p>
                    <p class="text-xs text-content-muted font-medium">Activas</p>
                </BaseCard>
                <BaseCard class="p-4 text-center">
                    <p class="font-display text-2xl font-bold text-danger">
                        {{ overdueCount }}
                    </p>
                    <p class="text-xs text-content-muted font-medium">Vencidas</p>
                </BaseCard>
                <BaseCard class="p-4 text-center">
                    <p class="font-display text-2xl font-bold text-success">
                        {{ completedMissions.length }}
                    </p>
                    <p class="text-xs text-content-muted font-medium">Completadas</p>
                </BaseCard>
            </div>

            <!-- VISTA 1: MATRIZ DE EISENHOWER (4 CUADRANTES) -->
            <div v-if="viewMode === 'matrix' && (missions.length > 0 || completedMissions.length > 0)" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- CUADRANTE 1: Urgente + Importante (Hacer YA) -->
                    <BaseCard class="p-5 flex flex-col justify-between border-t-4 border-t-danger bg-surface/80">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-border-interactive">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-danger/10 text-danger font-bold text-sm">
                                        Q1
                                    </div>
                                    <div>
                                        <h2 class="font-bold text-sm text-content-primary flex items-center gap-1.5">
                                            Hacer YA <span class="text-xs font-normal text-content-muted">(Urgente e Importante)</span>
                                        </h2>
                                        <p class="text-[11px] text-content-secondary">Crisis, entregas inmediatas y exámenes próximos.</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-danger/15 px-2.5 py-0.5 text-xs font-bold text-danger">
                                    {{ q1Missions.length }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3 min-h-[140px]">
                                <div v-if="q1Missions.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-xs text-content-muted">
                                    <AppIcon name="check-circle" :size="24" class="text-success mb-1" />
                                    <span>¡Sin crisis urgentes! Mantén este cuadrante despejado.</span>
                                </div>
                                <template v-else>
                                    <div
                                        v-for="m in q1Missions"
                                        :key="m.id"
                                        class="rounded-xl border border-border-interactive bg-surface-raised/60 p-3.5 shadow-xs transition hover:border-danger/40"
                                        :class="stateClass(m.state)"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <Link
                                                        :href="route('missions.show', { id: m.id })"
                                                        class="font-semibold text-sm text-content-primary hover:text-primary-strong truncate"
                                                    >
                                                        {{ m.title }}
                                                    </Link>
                                                    <span v-if="m.is_overdue" class="rounded bg-danger/20 px-1.5 py-0.2 text-[10px] font-bold text-danger">
                                                        Vencida
                                                    </span>
                                                </div>
                                                <p v-if="m.description" class="mt-0.5 text-xs text-content-secondary line-clamp-2">
                                                    {{ m.description }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                                    <span :class="difficultyConfig[m.difficulty]?.class" class="rounded px-1.5 py-0.2 font-medium">
                                                        {{ difficultyConfig[m.difficulty]?.label }}
                                                    </span>
                                                    <span v-if="m.due_date" class="text-danger font-medium">
                                                        📅 {{ m.due_date }}
                                                    </span>
                                                    <span v-if="m.subtask_count > 0" class="text-content-muted">
                                                        ✓ {{ m.subtask_done }}/{{ m.subtask_count }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1 shrink-0">
                                                <select
                                                    class="rounded-lg border border-border-interactive bg-surface px-1.5 py-1 text-[11px] font-medium text-content-secondary outline-none cursor-pointer"
                                                    :value="m.eisenhower_quadrant || 'q1'"
                                                    title="Mover de cuadrante"
                                                    @change="quickChangeQuadrant(m.id, $event.target.value)"
                                                >
                                                    <option value="q1">Q1 (YA)</option>
                                                    <option value="q2">Q2 (Plan)</option>
                                                    <option value="q3">Q3 (Min)</option>
                                                    <option value="q4">Q4 (Desc)</option>
                                                </select>
                                                <button
                                                    type="button"
                                                    class="rounded p-1 text-content-muted hover:text-primary-strong cursor-pointer"
                                                    title="Iniciar Pomodoro"
                                                    @click="startPomodoro(m.id)"
                                                >
                                                    <AppIcon name="timer" :size="14" />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded p-1 text-content-muted hover:text-success cursor-pointer"
                                                    title="Completar"
                                                    @click="completeMission(m.id)"
                                                >
                                                    <AppIcon name="check" :size="14" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-border-interactive flex justify-between items-center text-xs">
                            <span class="text-content-muted text-[11px]">⚡ Atender primero</span>
                            <button
                                type="button"
                                class="font-semibold text-danger hover:underline flex items-center gap-1 cursor-pointer"
                                @click="openCreateModal('q1')"
                            >
                                + Agregar a Q1
                            </button>
                        </div>
                    </BaseCard>

                    <!-- CUADRANTE 2: No Urgente pero Importante (Planificar / Estratégico) -->
                    <BaseCard class="p-5 flex flex-col justify-between border-t-4 border-t-success bg-surface/80 ring-1 ring-success/20 shadow-sm">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-border-interactive">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-success/10 text-success font-bold text-sm">
                                        Q2
                                    </div>
                                    <div>
                                        <h2 class="font-bold text-sm text-content-primary flex items-center gap-1.5">
                                            Planificar <span class="rounded bg-success/20 px-1.5 py-0.2 text-[10px] font-bold text-success">Zona Anti-Procrastinación</span>
                                        </h2>
                                        <p class="text-[11px] text-content-secondary">Importante pero no urgente: estudio espaciado y proyectos.</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-bold text-success">
                                    {{ q2Missions.length }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3 min-h-[140px]">
                                <div v-if="q2Missions.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-xs text-content-muted">
                                    <AppIcon name="target" :size="24" class="text-success mb-1" />
                                    <span>¡Añade misiones aquí para estudiar con calma y sin prisas!</span>
                                </div>
                                <template v-else>
                                    <div
                                        v-for="m in q2Missions"
                                        :key="m.id"
                                        class="rounded-xl border border-border-interactive bg-surface-raised/60 p-3.5 shadow-xs transition hover:border-success/40"
                                        :class="stateClass(m.state)"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <Link
                                                        :href="route('missions.show', { id: m.id })"
                                                        class="font-semibold text-sm text-content-primary hover:text-primary-strong truncate"
                                                    >
                                                        {{ m.title }}
                                                    </Link>
                                                    <span
                                                        v-if="m.state === 'in_progress'"
                                                        class="rounded bg-primary-strong/20 px-1.5 py-0.2 text-[10px] font-bold text-primary-strong"
                                                    >
                                                        En Progreso
                                                    </span>
                                                </div>
                                                <p v-if="m.description" class="mt-0.5 text-xs text-content-secondary line-clamp-2">
                                                    {{ m.description }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                                    <span :class="difficultyConfig[m.difficulty]?.class" class="rounded px-1.5 py-0.2 font-medium">
                                                        {{ difficultyConfig[m.difficulty]?.label }}
                                                    </span>
                                                    <span v-if="m.due_date" class="text-content-secondary font-medium">
                                                        📅 {{ m.due_date }}
                                                    </span>
                                                    <span v-if="m.subtask_count > 0" class="text-content-muted">
                                                        ✓ {{ m.subtask_done }}/{{ m.subtask_count }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1 shrink-0">
                                                <select
                                                    class="rounded-lg border border-border-interactive bg-surface px-1.5 py-1 text-[11px] font-medium text-content-secondary outline-none cursor-pointer"
                                                    :value="m.eisenhower_quadrant || 'q2'"
                                                    title="Mover de cuadrante"
                                                    @change="quickChangeQuadrant(m.id, $event.target.value)"
                                                >
                                                    <option value="q1">Q1 (YA)</option>
                                                    <option value="q2">Q2 (Plan)</option>
                                                    <option value="q3">Q3 (Min)</option>
                                                    <option value="q4">Q4 (Desc)</option>
                                                </select>
                                                <button
                                                    type="button"
                                                    class="rounded p-1 text-content-muted hover:text-primary-strong cursor-pointer"
                                                    title="Iniciar Pomodoro"
                                                    @click="startPomodoro(m.id)"
                                                >
                                                    <AppIcon name="timer" :size="14" />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded p-1 text-content-muted hover:text-success cursor-pointer"
                                                    title="Completar"
                                                    @click="completeMission(m.id)"
                                                >
                                                    <AppIcon name="check" :size="14" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-border-interactive flex justify-between items-center text-xs">
                            <span class="text-success font-medium text-[11px]">🧠 Mayor retorno de aprendizaje</span>
                            <button
                                type="button"
                                class="font-semibold text-success hover:underline flex items-center gap-1 cursor-pointer"
                                @click="openCreateModal('q2')"
                            >
                                + Agregar a Q2
                            </button>
                        </div>
                    </BaseCard>

                    <!-- CUADRANTE 3: Urgente pero No Importante (Minimizar / Operativo) -->
                    <BaseCard class="p-5 flex flex-col justify-between border-t-4 border-t-warning bg-surface/80">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-border-interactive">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-warning/10 text-warning font-bold text-sm">
                                        Q3
                                    </div>
                                    <div>
                                        <h2 class="font-bold text-sm text-content-primary flex items-center gap-1.5">
                                            Minimizar <span class="text-xs font-normal text-content-muted">(Urgente, no importante)</span>
                                        </h2>
                                        <p class="text-[11px] text-content-secondary">Trámites rápidos, consultas operativas y coordinaciones.</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-warning/15 px-2.5 py-0.5 text-xs font-bold text-warning">
                                    {{ q3Missions.length }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3 min-h-[140px]">
                                <div v-if="q3Missions.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-xs text-content-muted">
                                    <span>Sin tareas operativas secundarias.</span>
                                </div>
                                <template v-else>
                                    <div
                                        v-for="m in q3Missions"
                                        :key="m.id"
                                        class="rounded-xl border border-border-interactive bg-surface-raised/60 p-3.5 shadow-xs transition hover:border-warning/40"
                                        :class="stateClass(m.state)"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <Link
                                                    :href="route('missions.show', { id: m.id })"
                                                    class="font-semibold text-sm text-content-primary hover:text-primary-strong truncate block"
                                                >
                                                    {{ m.title }}
                                                </Link>
                                                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                                    <span :class="difficultyConfig[m.difficulty]?.class" class="rounded px-1.5 py-0.2 font-medium">
                                                        {{ difficultyConfig[m.difficulty]?.label }}
                                                    </span>
                                                    <span v-if="m.due_date" class="text-content-secondary font-medium">
                                                        📅 {{ m.due_date }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1 shrink-0">
                                                <select
                                                    class="rounded-lg border border-border-interactive bg-surface px-1.5 py-1 text-[11px] font-medium text-content-secondary outline-none cursor-pointer"
                                                    :value="m.eisenhower_quadrant || 'q3'"
                                                    title="Mover de cuadrante"
                                                    @change="quickChangeQuadrant(m.id, $event.target.value)"
                                                >
                                                    <option value="q1">Q1 (YA)</option>
                                                    <option value="q2">Q2 (Plan)</option>
                                                    <option value="q3">Q3 (Min)</option>
                                                    <option value="q4">Q4 (Desc)</option>
                                                </select>
                                                <button
                                                    type="button"
                                                    class="rounded p-1 text-content-muted hover:text-success cursor-pointer"
                                                    title="Completar"
                                                    @click="completeMission(m.id)"
                                                >
                                                    <AppIcon name="check" :size="14" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-border-interactive flex justify-between items-center text-xs">
                            <span class="text-content-muted text-[11px]">⚡ Agrupar en bloques cortos</span>
                            <button
                                type="button"
                                class="font-semibold text-warning hover:underline flex items-center gap-1 cursor-pointer"
                                @click="openCreateModal('q3')"
                            >
                                + Agregar a Q3
                            </button>
                        </div>
                    </BaseCard>

                    <!-- CUADRANTE 4: Ni Urgente ni Importante (Descartar / Evaluar) -->
                    <BaseCard class="p-5 flex flex-col justify-between border-t-4 border-t-slate-400 bg-surface/80 opacity-90">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-border-interactive">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-raised text-content-muted font-bold text-sm">
                                        Q4
                                    </div>
                                    <div>
                                        <h2 class="font-bold text-sm text-content-primary flex items-center gap-1.5">
                                            Descartar <span class="text-xs font-normal text-content-muted">(Ni urgente ni importante)</span>
                                        </h2>
                                        <p class="text-[11px] text-content-secondary">Pendientes de bajo valor o tareas prescindibles.</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-surface-raised px-2.5 py-0.5 text-xs font-bold text-content-muted">
                                    {{ q4Missions.length }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3 min-h-[140px]">
                                <div v-if="q4Missions.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-xs text-content-muted">
                                    <span>Sin tareas descartables o de relleno.</span>
                                </div>
                                <template v-else>
                                    <div
                                        v-for="m in q4Missions"
                                        :key="m.id"
                                        class="rounded-xl border border-border-interactive bg-surface-raised/40 p-3.5 shadow-xs transition hover:border-border"
                                        :class="stateClass(m.state)"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <Link
                                                    :href="route('missions.show', { id: m.id })"
                                                    class="font-medium text-sm text-content-secondary hover:text-content-primary truncate block"
                                                >
                                                    {{ m.title }}
                                                </Link>
                                                <p v-if="m.description" class="mt-0.5 text-xs text-content-muted line-clamp-1">
                                                    {{ m.description }}
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-1 shrink-0">
                                                <select
                                                    class="rounded-lg border border-border-interactive bg-surface px-1.5 py-1 text-[11px] font-medium text-content-secondary outline-none cursor-pointer"
                                                    :value="m.eisenhower_quadrant || 'q4'"
                                                    title="Mover de cuadrante"
                                                    @change="quickChangeQuadrant(m.id, $event.target.value)"
                                                >
                                                    <option value="q1">Q1 (YA)</option>
                                                    <option value="q2">Q2 (Plan)</option>
                                                    <option value="q3">Q3 (Min)</option>
                                                    <option value="q4">Q4 (Desc)</option>
                                                </select>
                                                <button
                                                    type="button"
                                                    class="rounded p-1 text-content-muted hover:text-danger-text cursor-pointer"
                                                    title="Eliminar"
                                                    @click="deleteMission(m.id)"
                                                >
                                                    <AppIcon name="trash" :size="14" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-border-interactive flex justify-between items-center text-xs">
                            <span class="text-content-muted text-[11px]">🧹 Eliminar si no aporta valor</span>
                            <button
                                type="button"
                                class="font-semibold text-content-secondary hover:underline flex items-center gap-1 cursor-pointer"
                                @click="openCreateModal('q4')"
                            >
                                + Agregar a Q4
                            </button>
                        </div>
                    </BaseCard>
                </div>
            </div>

            <!-- VISTA 2: TABLERO KANBAN CON 4 COLUMNAS Y ESTÉTICA DE POST-ITS -->
            <div v-else-if="viewMode === 'kanban' && (missions.length > 0 || completedMissions.length > 0)" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
                    <!-- Columna 1: LISTA DE MISIONES (Por Hacer / Backlog) -->
                    <div class="rounded-2xl bg-surface-raised/90 backdrop-blur-md border border-border-interactive/90 p-3.5 flex flex-col min-h-[420px] shadow-sm">
                        <div class="flex items-center justify-between pb-2.5 border-b border-border-interactive mb-3">
                            <h2 class="font-bold text-xs text-content-primary flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span> 📋 Lista de Misiones
                            </h2>
                            <span class="rounded-full bg-surface px-2 py-0.5 text-[11px] font-bold text-content-secondary border border-border-interactive">
                                {{ kanbanBacklog.length }}
                            </span>
                        </div>

                        <div class="space-y-3 flex-1">
                            <div v-if="kanbanBacklog.length === 0" class="py-12 text-center text-xs text-content-muted">
                                ¡Sin misiones pendientes! Crea una nueva.
                            </div>
                            <div
                                v-for="(m, idx) in kanbanBacklog"
                                :key="m.id"
                                class="group relative rounded-xl border p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:scale-[1.01]"
                                :class="[
                                    eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.postitClass,
                                    idx % 2 === 0 ? 'rotate-[-0.8deg] hover:rotate-0' : 'rotate-[0.8deg] hover:rotate-0',
                                ]"
                            >
                                <!-- Cinta decorativa del Post-it -->
                                <div
                                    class="absolute -top-2 left-1/2 -translate-x-1/2 h-3.5 w-12 rounded-xs backdrop-blur-xs border shadow-xs select-none pointer-events-none rotate-[-1.5deg]"
                                    :class="eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.postitTape"
                                ></div>

                                <div class="flex items-start justify-between gap-1.5 mt-0.5">
                                    <Link
                                        :href="route('missions.show', { id: m.id })"
                                        class="font-bold text-xs hover:underline flex-1 line-clamp-2 leading-tight"
                                    >
                                        {{ m.title }}
                                    </Link>
                                    <span
                                        class="rounded px-1.5 py-0.2 text-[9px] font-black shrink-0"
                                        :class="eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.bgBadge"
                                    >
                                        {{ (m.eisenhower_quadrant || 'q2').toUpperCase() }}
                                    </span>
                                </div>

                                <p v-if="m.description" class="mt-1 text-[11px] opacity-80 line-clamp-2">
                                    {{ m.description }}
                                </p>

                                <!-- Subtareas interactivas -->
                                <div class="mt-2.5 pt-2 border-t border-current/15 space-y-1.5">
                                    <div class="flex items-center justify-between text-[10px] font-semibold opacity-80">
                                        <span>Subtareas ({{ m.subtask_done }}/{{ m.subtask_count }})</span>
                                        <span v-if="m.due_date">📅 {{ m.due_date }}</span>
                                    </div>
                                    <div v-if="m.subtasks && m.subtasks.length > 0" class="space-y-1">
                                        <div
                                            v-for="s in m.subtasks"
                                            :key="s.id"
                                            class="flex items-center gap-1.5 text-xs"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="s.is_completed"
                                                class="h-3.5 w-3.5 shrink-0 accent-primary cursor-pointer"
                                                @change="toggleSubtask(m.id, s.id)"
                                            />
                                            <span :class="s.is_completed ? 'line-through opacity-60' : 'font-medium'" class="truncate">
                                                {{ s.title }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Input rápido para agregar subtarea -->
                                    <div class="flex items-center gap-1 pt-1">
                                        <input
                                            v-model="addSubtaskTitles[m.id]"
                                            class="min-w-0 flex-1 rounded border border-current/20 bg-surface/70 px-1.5 py-0.5 text-[11px] outline-none placeholder:text-content-muted focus:border-primary"
                                            placeholder="+ Añadir paso..."
                                            @keyup.enter="addSubtask(m.id)"
                                        />
                                    </div>
                                </div>

                                <div class="mt-2.5 pt-2 border-t border-current/15 flex items-center justify-between text-xs">
                                    <span class="text-[10px] font-semibold opacity-70">
                                        {{ difficultyConfig[m.difficulty]?.label }}
                                    </span>

                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="rounded p-1 text-current opacity-75 hover:opacity-100 hover:scale-110 transition cursor-pointer"
                                            title="Enfocarme con Pomodoro"
                                            @click="startPomodoro(m.id)"
                                        >
                                            <AppIcon name="timer" :size="14" />
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded p-1 text-current opacity-75 hover:opacity-100 hover:scale-110 transition cursor-pointer"
                                            title="Editar"
                                            @click="openEditModal(m)"
                                        >
                                            <AppIcon name="pencil" :size="14" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="mt-3 w-full py-1.5 rounded-xl border border-dashed border-border-interactive text-xs font-semibold text-content-secondary hover:text-content-primary hover:border-primary transition cursor-pointer"
                            @click="openCreateModal('q2')"
                        >
                            + Añadir Post-it
                        </button>
                    </div>

                    <!-- Columna 2: EN PROCESO (En Foco) -->
                    <div class="rounded-2xl bg-primary-strong/15 dark:bg-primary-strong/20 backdrop-blur-md border border-primary-strong/35 p-3.5 flex flex-col min-h-[420px] shadow-sm">
                        <div class="flex items-center justify-between pb-2.5 border-b border-primary-strong/20 mb-3">
                            <h2 class="font-bold text-xs text-primary-strong flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-primary-strong animate-pulse"></span> ⚡ En Proceso
                            </h2>
                            <span class="rounded-full bg-primary-strong/20 px-2 py-0.5 text-[11px] font-bold text-primary-strong border border-primary-strong/35">
                                {{ kanbanInProgress.length }}
                            </span>
                        </div>

                        <div class="space-y-3 flex-1">
                            <div v-if="kanbanInProgress.length === 0" class="py-12 text-center text-xs text-content-muted">
                                Sin tareas en foco. ¡Marca una subtarea o inicia un Pomodoro!
                            </div>
                            <div
                                v-for="(m, idx) in kanbanInProgress"
                                :key="m.id"
                                class="group relative rounded-xl border p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:scale-[1.01]"
                                :class="[
                                    eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.postitClass,
                                    idx % 2 === 0 ? 'rotate-[0.9deg] hover:rotate-0' : 'rotate-[-0.9deg] hover:rotate-0',
                                ]"
                            >
                                <div
                                    class="absolute -top-2 left-1/2 -translate-x-1/2 h-3.5 w-12 rounded-xs backdrop-blur-xs border shadow-xs select-none pointer-events-none rotate-[1.5deg]"
                                    :class="eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.postitTape"
                                ></div>

                                <div class="flex items-start justify-between gap-1.5 mt-0.5">
                                    <Link
                                        :href="route('missions.show', { id: m.id })"
                                        class="font-bold text-xs hover:underline flex-1 line-clamp-2 leading-tight"
                                    >
                                        {{ m.title }}
                                    </Link>
                                    <span
                                        class="rounded px-1.5 py-0.2 text-[9px] font-black shrink-0"
                                        :class="eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.bgBadge"
                                    >
                                        {{ (m.eisenhower_quadrant || 'q2').toUpperCase() }}
                                    </span>
                                </div>

                                <!-- Barra de progreso de subtareas -->
                                <div class="mt-2 space-y-1">
                                    <div class="flex justify-between text-[10px] font-bold">
                                        <span>Avance</span>
                                        <span>{{ m.subtask_done }}/{{ m.subtask_count }} pasos</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-current/15 overflow-hidden">
                                        <div
                                            class="h-full bg-primary-strong transition-all duration-300"
                                            :style="{ width: `${m.subtask_count > 0 ? (m.subtask_done / m.subtask_count) * 100 : 0}%` }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Subtareas interactivas -->
                                <div v-if="m.subtasks && m.subtasks.length > 0" class="mt-2.5 space-y-1">
                                    <div
                                        v-for="s in m.subtasks"
                                        :key="s.id"
                                        class="flex items-center gap-1.5 text-xs"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="s.is_completed"
                                            class="h-3.5 w-3.5 shrink-0 accent-primary cursor-pointer"
                                            @change="toggleSubtask(m.id, s.id)"
                                        />
                                        <span :class="s.is_completed ? 'line-through opacity-60' : 'font-medium'" class="truncate">
                                            {{ s.title }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1 pt-1">
                                        <input
                                            v-model="addSubtaskTitles[m.id]"
                                            class="min-w-0 flex-1 rounded border border-current/20 bg-surface/70 px-1.5 py-0.5 text-[11px] outline-none placeholder:text-content-muted focus:border-primary"
                                            placeholder="+ Añadir paso..."
                                            @keyup.enter="addSubtask(m.id)"
                                        />
                                    </div>
                                </div>

                                <div class="mt-2.5 pt-2 border-t border-current/15 flex items-center justify-between text-xs">
                                    <span v-if="m.due_date" class="text-[10px] font-semibold opacity-80">
                                        📅 {{ m.due_date }}
                                    </span>
                                    <span v-else class="text-[10px] opacity-70">
                                        {{ difficultyConfig[m.difficulty]?.label }}
                                    </span>

                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="rounded p-1 text-current opacity-75 hover:opacity-100 hover:scale-110 transition cursor-pointer"
                                            title="Enfocarme con Pomodoro"
                                            @click="startPomodoro(m.id)"
                                        >
                                            <AppIcon name="timer" :size="14" />
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded p-1 text-current opacity-75 hover:opacity-100 hover:scale-110 transition cursor-pointer"
                                            title="Completar misión"
                                            @click="completeMission(m.id)"
                                        >
                                            <AppIcon name="check" :size="14" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 3: EN REVISIÓN (Listo para cerrar) -->
                    <div class="rounded-2xl bg-amber-500/15 dark:bg-amber-500/20 backdrop-blur-md border border-amber-500/35 p-3.5 flex flex-col min-h-[420px] shadow-sm">
                        <div class="flex items-center justify-between pb-2.5 border-b border-amber-500/20 mb-3">
                            <h2 class="font-bold text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> 🔍 En Revisión
                            </h2>
                            <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[11px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/35">
                                {{ kanbanInReview.length }}
                            </span>
                        </div>

                        <div class="space-y-3 flex-1">
                            <div v-if="kanbanInReview.length === 0" class="py-12 text-center text-xs text-content-muted">
                                Las tareas con todos sus pasos completados aparecerán aquí para tu revisión final.
                            </div>
                            <div
                                v-for="(m, idx) in kanbanInReview"
                                :key="m.id"
                                class="group relative rounded-xl border p-3.5 shadow-sm transition-all duration-200 hover:shadow-md hover:scale-[1.01]"
                                :class="[
                                    eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.postitClass,
                                    idx % 2 === 0 ? 'rotate-[-0.7deg] hover:rotate-0' : 'rotate-[0.7deg] hover:rotate-0',
                                ]"
                            >
                                <div
                                    class="absolute -top-2 left-1/2 -translate-x-1/2 h-3.5 w-12 rounded-xs backdrop-blur-xs border shadow-xs select-none pointer-events-none rotate-[-1deg]"
                                    :class="eisenhowerConfig[m.eisenhower_quadrant || 'q2']?.postitTape"
                                ></div>

                                <div class="flex items-start justify-between gap-1.5 mt-0.5">
                                    <Link
                                        :href="route('missions.show', { id: m.id })"
                                        class="font-bold text-xs hover:underline flex-1 line-clamp-2 leading-tight"
                                    >
                                        {{ m.title }}
                                    </Link>
                                    <span class="rounded bg-success/20 px-1.5 py-0.2 text-[9px] font-black text-success">
                                        100% LISTO
                                    </span>
                                </div>

                                <p class="mt-1 text-[11px] opacity-80">
                                    ✓ Todos los pasos completados ({{ m.subtask_done }}/{{ m.subtask_count }}).
                                </p>

                                <div class="mt-3 pt-2.5 border-t border-current/15 flex flex-col gap-2">
                                    <button
                                        type="button"
                                        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-success text-on-accent text-xs font-bold shadow-sm hover:brightness-110 transition cursor-pointer"
                                        @click="completeMission(m.id)"
                                    >
                                        <AppIcon name="check-circle" :size="14" />
                                        <span>Finalizar Misión</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 4: TERMINADO (Completadas) -->
                    <div class="rounded-2xl bg-success/15 dark:bg-success/20 backdrop-blur-md border border-success/35 p-3.5 flex flex-col min-h-[420px] shadow-sm">
                        <div class="flex items-center justify-between pb-2.5 border-b border-success/20 mb-3">
                            <h2 class="font-bold text-xs text-success flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-success"></span> ✅ Terminado
                            </h2>
                            <span class="rounded-full bg-success/20 px-2 py-0.5 text-[11px] font-bold text-success border border-success/35">
                                {{ completedMissions.length }}
                            </span>
                        </div>

                        <div class="space-y-3 flex-1">
                            <div v-if="completedMissions.length === 0" class="py-12 text-center text-xs text-content-muted">
                                ¡Completa tu primera misión para ganar XP y derrotar villanos!
                            </div>
                            <div
                                v-for="(m, idx) in completedMissions.slice(0, 10)"
                                :key="m.id"
                                class="group relative rounded-xl border border-success/40 bg-surface-raised/95 p-3.5 shadow-sm transition-all duration-200 hover:scale-[1.01]"
                                :class="idx % 2 === 0 ? 'rotate-[-0.6deg]' : 'rotate-[0.6deg]'"
                            >
                                <div class="flex items-start justify-between gap-1.5">
                                    <Link
                                        :href="route('missions.show', { id: m.id })"
                                        class="font-semibold text-xs line-through text-content-secondary hover:text-content-primary flex-1 line-clamp-2 leading-tight"
                                    >
                                        {{ m.title }}
                                    </Link>
                                    <span v-if="m.xp_awarded > 0" class="rounded bg-accent/20 px-1.5 py-0.2 text-[9px] font-black text-accent shrink-0">
                                        +{{ m.xp_awarded }} XP
                                    </span>
                                </div>

                                <div class="mt-2 pt-2 border-t border-border-interactive flex items-center justify-between text-[11px] text-content-muted">
                                    <span>📅 {{ m.completed_at }}</span>
                                    <button
                                        type="button"
                                        class="font-semibold text-content-secondary hover:text-content-primary hover:underline cursor-pointer"
                                        @click="uncompleteMission(m.id)"
                                    >
                                        Desarchivar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISTA 3: CRONOGRAMA / LISTA CON FILTROS -->
            <div v-else-if="viewMode === 'list' && (missions.length > 0 || completedMissions.length > 0)" class="space-y-6">
                <!-- Vencidas -->
                <div v-if="overdueMissions.length > 0" class="mb-4">
                    <h2 class="mb-2 text-sm font-semibold text-danger flex items-center gap-1.5">
                        <AppIcon name="alert-triangle" :size="16" /> Vencidas ({{ overdueMissions.length }})
                    </h2>
                    <div class="space-y-2">
                        <BaseCard
                            v-for="m in overdueMissions"
                            :key="m.id"
                            class="border-l-4 p-4"
                            :class="stateClass(m.state)"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Link
                                            :href="route('missions.show', { id: m.id })"
                                            class="font-semibold text-content-primary hover:text-primary-strong"
                                        >
                                            {{ m.title }}
                                        </Link>
                                        <span
                                            v-if="m.eisenhower_quadrant"
                                            class="rounded px-1.5 py-0.2 text-[10px] font-bold"
                                            :class="eisenhowerConfig[m.eisenhower_quadrant]?.bgBadge"
                                        >
                                            {{ eisenhowerConfig[m.eisenhower_quadrant]?.badge }}
                                        </span>
                                    </div>
                                    <p v-if="m.description" class="mt-0.5 text-sm text-content-secondary truncate">
                                        {{ m.description }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                        <span :class="difficultyConfig[m.difficulty]?.class">{{ difficultyConfig[m.difficulty]?.label }}</span>
                                        <span :class="priorityConfig[m.priority]?.class">{{ priorityConfig[m.priority]?.label }}</span>
                                        <span class="text-danger font-medium">Vencía {{ m.due_date }}</span>
                                        <span v-if="m.subtask_count > 0" class="text-content-muted">
                                            ✓ {{ m.subtask_done }}/{{ m.subtask_count }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-start gap-1">
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-primary-strong cursor-pointer"
                                        title="Enfocarme con Pomodoro"
                                        @click="startPomodoro(m.id)"
                                    >
                                        <AppIcon name="timer" :size="16" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-success cursor-pointer"
                                        title="Completar"
                                        @click="completeMission(m.id)"
                                    >
                                        <AppIcon name="check-circle" :size="16" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-content-primary cursor-pointer"
                                        title="Editar"
                                        @click="openEditModal(m)"
                                    >
                                        <AppIcon name="pencil" :size="16" />
                                    </button>
                                </div>
                            </div>
                        </BaseCard>
                    </div>
                </div>

                <!-- Hoy -->
                <div v-if="todayMissions.length > 0" class="mb-4">
                    <h2 class="mb-2 text-sm font-semibold text-content-primary flex items-center gap-1.5">
                        <AppIcon name="calendar" :size="16" class="text-primary-strong" /> Para Hoy ({{ todayMissions.length }})
                    </h2>
                    <div class="space-y-2">
                        <BaseCard
                            v-for="m in todayMissions"
                            :key="m.id"
                            class="border-l-4 p-4"
                            :class="stateClass(m.state)"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Link
                                            :href="route('missions.show', { id: m.id })"
                                            class="font-semibold text-content-primary hover:text-primary-strong"
                                        >
                                            {{ m.title }}
                                        </Link>
                                        <span
                                            v-if="m.eisenhower_quadrant"
                                            class="rounded px-1.5 py-0.2 text-[10px] font-bold"
                                            :class="eisenhowerConfig[m.eisenhower_quadrant]?.bgBadge"
                                        >
                                            {{ eisenhowerConfig[m.eisenhower_quadrant]?.badge }}
                                        </span>
                                    </div>
                                    <p v-if="m.description" class="mt-0.5 text-sm text-content-secondary truncate">
                                        {{ m.description }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                        <span :class="difficultyConfig[m.difficulty]?.class">{{ difficultyConfig[m.difficulty]?.label }}</span>
                                        <span :class="priorityConfig[m.priority]?.class">{{ priorityConfig[m.priority]?.label }}</span>
                                        <span v-if="m.subtask_count > 0" class="text-content-muted">
                                            ✓ {{ m.subtask_done }}/{{ m.subtask_count }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-start gap-1">
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-primary-strong cursor-pointer"
                                        title="Enfocarme con Pomodoro"
                                        @click="startPomodoro(m.id)"
                                    >
                                        <AppIcon name="timer" :size="16" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-success cursor-pointer"
                                        title="Completar"
                                        @click="completeMission(m.id)"
                                    >
                                        <AppIcon name="check-circle" :size="16" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-content-primary cursor-pointer"
                                        title="Editar"
                                        @click="openEditModal(m)"
                                    >
                                        <AppIcon name="pencil" :size="16" />
                                    </button>
                                </div>
                            </div>
                        </BaseCard>
                    </div>
                </div>

                <!-- Esta Semana y Resto -->
                <div v-if="dueSoon.length > 0 || restMissions.length > 0" class="mb-4">
                    <h2 class="mb-2 text-sm font-semibold text-content-secondary">
                        Próximas y sin fecha
                    </h2>
                    <div class="space-y-2">
                        <BaseCard
                            v-for="m in [...dueSoon, ...restMissions]"
                            :key="m.id"
                            class="border-l-4 p-4"
                            :class="stateClass(m.state)"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <Link
                                            :href="route('missions.show', { id: m.id })"
                                            class="font-semibold text-content-primary hover:text-primary-strong"
                                        >
                                            {{ m.title }}
                                        </Link>
                                        <span
                                            v-if="m.eisenhower_quadrant"
                                            class="rounded px-1.5 py-0.2 text-[10px] font-bold"
                                            :class="eisenhowerConfig[m.eisenhower_quadrant]?.bgBadge"
                                        >
                                            {{ eisenhowerConfig[m.eisenhower_quadrant]?.badge }}
                                        </span>
                                    </div>
                                    <p v-if="m.description" class="mt-0.5 text-sm text-content-secondary truncate">
                                        {{ m.description }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                        <span :class="difficultyConfig[m.difficulty]?.class">{{ difficultyConfig[m.difficulty]?.label }}</span>
                                        <span :class="priorityConfig[m.priority]?.class">{{ priorityConfig[m.priority]?.label }}</span>
                                        <span v-if="m.due_date" class="text-content-secondary">📅 {{ m.due_date }}</span>
                                        <span v-if="m.subtask_count > 0" class="text-content-muted">
                                            ✓ {{ m.subtask_done }}/{{ m.subtask_count }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-start gap-1">
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-primary-strong cursor-pointer"
                                        title="Enfocarme con Pomodoro"
                                        @click="startPomodoro(m.id)"
                                    >
                                        <AppIcon name="timer" :size="16" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-success cursor-pointer"
                                        title="Completar"
                                        @click="completeMission(m.id)"
                                    >
                                        <AppIcon name="check-circle" :size="16" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-content-muted hover:text-content-primary cursor-pointer"
                                        title="Editar"
                                        @click="openEditModal(m)"
                                    >
                                        <AppIcon name="pencil" :size="16" />
                                    </button>
                                </div>
                            </div>
                        </BaseCard>
                    </div>
                </div>
            </div>

            <!-- Misiones Completadas (Historial) -->
            <div v-if="completedMissions.length > 0 && viewMode !== 'kanban'" class="pt-4 border-t border-border-interactive">
                <button
                    type="button"
                    class="flex items-center gap-2 text-sm font-semibold text-content-secondary hover:text-content-primary transition cursor-pointer"
                    @click="showCompleted = !showCompleted"
                >
                    <AppIcon name="archive" :size="16" />
                    <span>Misiones completadas ({{ completedMissions.length }})</span>
                    <span class="text-xs text-content-muted">[{{ showCompleted ? 'Ocultar' : 'Mostrar' }}]</span>
                </button>

                <div v-if="showCompleted" class="mt-3 space-y-2">
                    <BaseCard
                        v-for="m in completedMissions"
                        :key="m.id"
                        class="border-l-4 border-l-success p-3.5 opacity-80"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="route('missions.show', { id: m.id })"
                                    class="font-semibold text-content-primary line-through hover:text-primary-strong text-sm"
                                >
                                    {{ m.title }}
                                </Link>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-content-muted">
                                    <span class="text-success font-medium">✓ Completada {{ m.completed_at }}</span>
                                    <span v-if="m.xp_awarded > 0" class="text-accent font-semibold">+{{ m.xp_awarded }} XP</span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="flex items-center gap-1 rounded bg-surface-raised px-2.5 py-1 text-xs font-medium text-content-secondary hover:text-content-primary cursor-pointer"
                                @click="uncompleteMission(m.id)"
                            >
                                <AppIcon name="rotate-ccw" :size="12" /> Desarchivar
                            </button>
                        </div>
                    </BaseCard>
                </div>
            </div>

            <!-- Estado Vacío -->
            <BaseCard
                v-if="missions.length === 0 && completedMissions.length === 0"
                class="flex flex-col items-center p-12 text-center"
            >
                <AppIcon name="target" :size="48" class="mb-3 text-content-muted" />
                <h2 class="text-lg font-semibold text-content-primary">No tienes misiones creadas</h2>
                <p class="mt-1 max-w-sm text-sm text-content-secondary">
                    Crea tu primera misión, clasifícala en la matriz o visualízala en tu tablero Kanban de post-its.
                </p>
                <BaseButton class="mt-6" variant="primary" @click="openCreateModal('q2')">
                    Crear primera misión
                </BaseButton>
            </BaseCard>
        </div>

        <!-- MODAL: CREAR MISIÓN -->
        <BaseModal :show="showCreateModal" title="Nueva Misión" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <BaseInput
                    id="create-title"
                    v-model="createForm.title"
                    label="Título de la Misión"
                    placeholder="Ej. Terminar práctica de Cálculo o Avance de Tesis"
                    :error="createForm.errors.title"
                    required
                />

                <BaseInput
                    id="create-desc"
                    v-model="createForm.description"
                    label="Descripción (opcional)"
                    placeholder="Detalles clave para resolverla..."
                />

                <!-- Selector Visual de Cuadrante Eisenhower -->
                <div>
                    <label class="block text-xs font-semibold text-content-secondary mb-2">
                        Clasificación en Matriz de Eisenhower
                    </label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            class="flex flex-col text-left p-2.5 rounded-xl border transition cursor-pointer"
                            :class="
                                createForm.eisenhower_quadrant === 'q1'
                                    ? 'bg-danger/15 border-danger text-danger ring-1 ring-danger'
                                    : 'bg-surface border-border-interactive text-content-secondary hover:border-danger/40'
                            "
                            @click="createForm.eisenhower_quadrant = 'q1'"
                        >
                            <span class="font-bold text-xs flex items-center gap-1">🔴 Q1: Hacer YA</span>
                            <span class="text-[11px] opacity-80 mt-0.5">Urgente + Importante (Crisis)</span>
                        </button>

                        <button
                            type="button"
                            class="flex flex-col text-left p-2.5 rounded-xl border transition cursor-pointer"
                            :class="
                                createForm.eisenhower_quadrant === 'q2'
                                    ? 'bg-success/15 border-success text-success ring-1 ring-success'
                                    : 'bg-surface border-border-interactive text-content-secondary hover:border-success/40'
                            "
                            @click="createForm.eisenhower_quadrant = 'q2'"
                        >
                            <span class="font-bold text-xs flex items-center gap-1">🟢 Q2: Planificar (Recomendado)</span>
                            <span class="text-[11px] opacity-80 mt-0.5">Importante + No Urgente (Estratégico)</span>
                        </button>

                        <button
                            type="button"
                            class="flex flex-col text-left p-2.5 rounded-xl border transition cursor-pointer"
                            :class="
                                createForm.eisenhower_quadrant === 'q3'
                                    ? 'bg-warning/15 border-warning text-warning ring-1 ring-warning'
                                    : 'bg-surface border-border-interactive text-content-secondary hover:border-warning/40'
                            "
                            @click="createForm.eisenhower_quadrant = 'q3'"
                        >
                            <span class="font-bold text-xs flex items-center gap-1">🟡 Q3: Minimizar</span>
                            <span class="text-[11px] opacity-80 mt-0.5">Urgente + No Importante (Operativo)</span>
                        </button>

                        <button
                            type="button"
                            class="flex flex-col text-left p-2.5 rounded-xl border transition cursor-pointer"
                            :class="
                                createForm.eisenhower_quadrant === 'q4'
                                    ? 'bg-surface-raised border-content-muted text-content-primary ring-1 ring-content-muted'
                                    : 'bg-surface border-border-interactive text-content-secondary hover:border-content-muted'
                            "
                            @click="createForm.eisenhower_quadrant = 'q4'"
                        >
                            <span class="font-bold text-xs flex items-center gap-1">⚪ Q4: Descartar</span>
                            <span class="text-[11px] opacity-80 mt-0.5">Ni urgente ni importante</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <BaseSelect
                        id="create-difficulty"
                        v-model="createForm.difficulty"
                        label="Dificultad"
                        :options="difficultyOptions"
                    />
                    <BaseSelect
                        id="create-priority"
                        v-model="createForm.priority"
                        label="Prioridad"
                        :options="priorityOptions"
                    />
                </div>

                <BaseInput
                    id="create-due"
                    v-model="createForm.due_date"
                    label="Fecha límite (opcional)"
                    type="date"
                />

                <fieldset>
                    <legend class="mb-2 text-xs font-semibold text-content-secondary">
                        Subtareas <span class="text-content-muted font-normal">(0–20, opcional)</span>
                    </legend>
                    <div class="space-y-2">
                        <div
                            v-for="(_, i) in createForm.subtasks"
                            :key="'st-' + i"
                            class="flex items-center gap-2"
                        >
                            <BaseInput
                                :id="'subtask-' + i"
                                v-model="createForm.subtasks[i]"
                                :placeholder="`Paso ${i + 1}`"
                                class="flex-1"
                            />
                            <button
                                v-if="createForm.subtasks.length > 1"
                                type="button"
                                class="shrink-0 rounded p-1.5 text-sm text-content-muted hover:text-danger-text cursor-pointer"
                                @click="removeSubtaskField(i)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                    <button
                        v-if="createForm.subtasks.length < 20"
                        type="button"
                        class="mt-2 text-xs font-semibold text-primary-strong hover:underline cursor-pointer"
                        @click="addSubtaskField"
                    >
                        + Agregar paso
                    </button>
                </fieldset>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton variant="ghost" type="button" @click="closeCreateModal">Cancelar</BaseButton>
                    <BaseButton type="submit" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Guardando…' : 'Crear Misión' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- MODAL: EDITAR MISIÓN -->
        <BaseModal
            :show="showEditModal"
            :title="`Editar Misión: ${editingMission?.title || ''}`"
            @close="closeEditModal"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <BaseInput
                    id="edit-title"
                    v-model="editForm.title"
                    label="Título"
                    :error="editForm.errors.title"
                    required
                />
                <BaseInput id="edit-desc" v-model="editForm.description" label="Descripción" />

                <!-- Selector de Cuadrante en Edición -->
                <div>
                    <label class="block text-xs font-semibold text-content-secondary mb-2">
                        Cuadrante de Eisenhower
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="p-2 rounded-lg border text-xs font-bold text-left transition cursor-pointer"
                            :class="
                                editForm.eisenhower_quadrant === 'q1'
                                    ? 'bg-danger/20 border-danger text-danger'
                                    : 'bg-surface border-border-interactive text-content-secondary'
                            "
                            @click="editForm.eisenhower_quadrant = 'q1'"
                        >
                            🔴 Q1: Hacer YA
                        </button>
                        <button
                            type="button"
                            class="p-2 rounded-lg border text-xs font-bold text-left transition cursor-pointer"
                            :class="
                                editForm.eisenhower_quadrant === 'q2'
                                    ? 'bg-success/20 border-success text-success'
                                    : 'bg-surface border-border-interactive text-content-secondary'
                            "
                            @click="editForm.eisenhower_quadrant = 'q2'"
                        >
                            🟢 Q2: Planificar
                        </button>
                        <button
                            type="button"
                            class="p-2 rounded-lg border text-xs font-bold text-left transition cursor-pointer"
                            :class="
                                editForm.eisenhower_quadrant === 'q3'
                                    ? 'bg-warning/20 border-warning text-warning'
                                    : 'bg-surface border-border-interactive text-content-secondary'
                            "
                            @click="editForm.eisenhower_quadrant = 'q3'"
                        >
                            🟡 Q3: Minimizar
                        </button>
                        <button
                            type="button"
                            class="p-2 rounded-lg border text-xs font-bold text-left transition cursor-pointer"
                            :class="
                                editForm.eisenhower_quadrant === 'q4'
                                    ? 'bg-surface-raised border-content-muted text-content-primary'
                                    : 'bg-surface border-border-interactive text-content-secondary'
                            "
                            @click="editForm.eisenhower_quadrant = 'q4'"
                        >
                            ⚪ Q4: Descartar
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <BaseSelect
                        id="edit-difficulty"
                        v-model="editForm.difficulty"
                        label="Dificultad"
                        :options="difficultyOptions"
                    />
                    <BaseSelect
                        id="edit-priority"
                        v-model="editForm.priority"
                        label="Prioridad"
                        :options="priorityOptions"
                    />
                </div>

                <BaseInput
                    id="edit-due"
                    v-model="editForm.due_date"
                    label="Fecha de vencimiento"
                    type="date"
                />

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton variant="ghost" type="button" @click="closeEditModal">Cancelar</BaseButton>
                    <BaseButton type="submit" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Guardando…' : 'Guardar cambios' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
