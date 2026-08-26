<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import {
    Calendar,
    Clock,
    Flame,
    Target,
    Archive,
    CheckCircle2,
    RotateCcw,
    Trash2,
    Plus,
    Timer,
    Sparkles,
    Check,
    Pencil,
    ArrowLeft,
    AlertTriangle,
    Edit3,
    CheckCircle,
} from '@lucide/vue';

const props = defineProps({
    mission: { type: Object, required: true },
    pomodoroSessions: { type: Array, default: () => [] },
});

// Estado de UI
const showEditModal = ref(false);
const newSubtaskTitle = ref('');
const editingSubtaskId = ref(null);
const editingSubtaskTitle = ref('');

// Total de minutos de pomodoro
const totalPomodoroMinutes = computed(() =>
    props.pomodoroSessions.reduce((sum, s) => sum + (s.focus_minutes ?? 0), 0),
);

// Formulario de edición
const editForm = useForm({
    title: props.mission.title,
    description: props.mission.description || '',
    difficulty: props.mission.difficulty,
    priority: props.mission.priority,
    eisenhower_quadrant: props.mission.eisenhower_quadrant || 'q2',
    due_date: props.mission.due_date || '',
});

function openEditModal() {
    editForm.title = props.mission.title;
    editForm.description = props.mission.description || '';
    editForm.difficulty = props.mission.difficulty;
    editForm.priority = props.mission.priority;
    editForm.eisenhower_quadrant = props.mission.eisenhower_quadrant || 'q2';
    editForm.due_date = props.mission.due_date || '';
    showEditModal.value = true;
}

function submitEdit() {
    editForm.patch(route('missions.update', { id: props.mission.id }), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
}

function deleteMission() {
    if (confirm('¿Estás seguro de que deseas eliminar esta misión?')) {
        router.delete(route('missions.destroy', { id: props.mission.id }), {
            onSuccess: () => router.visit(route('missions.index')),
        });
    }
}

// Eisenhower Quadrants
const eisenhowerConfig = {
    q1: {
        id: 'q1',
        title: 'Q1: Hacer YA',
        subtitle: 'Urgente e Importante (Crisis)',
        icon: Flame,
        badgeClass: 'bg-danger/15 text-red-700 dark:text-danger border border-danger/30 font-bold',
        description: 'Exámenes próximos, entregas que vencen pronto y tareas críticas.',
    },
    q2: {
        id: 'q2',
        title: 'Q2: Planificar',
        subtitle: 'Importante pero No Urgente (Estratégico)',
        icon: Target,
        badgeClass: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30 font-bold',
        description: 'Estudio espaciado, lecturas, repasos y tesis. ¡El núcleo anti-procrastinación!',
    },
    q3: {
        id: 'q3',
        title: 'Q3: Minimizar',
        subtitle: 'Urgente pero No Importante (Operativo)',
        icon: Clock,
        badgeClass: 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 font-bold',
        description: 'Trámites, consultas rápidas, correos y tareas secundarias.',
    },
    q4: {
        id: 'q4',
        title: 'Q4: Descartar',
        subtitle: 'Ni Urgente ni Importante (Distracción)',
        icon: Archive,
        badgeClass: 'bg-surface-raised text-content-secondary border border-border-interactive font-medium',
        description: 'Actividades de bajo valor o pendientes prescindibles.',
    },
};

const difficultyConfig = {
    easy: {
        label: 'Fácil',
        xp: 20,
        badgeClass: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30 font-semibold',
    },
    medium: {
        label: 'Media',
        xp: 35,
        badgeClass: 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 font-semibold',
    },
    hard: {
        label: 'Difícil',
        xp: 50,
        badgeClass: 'bg-danger/15 text-red-700 dark:text-danger border border-danger/30 font-semibold',
    },
};

const priorityConfig = {
    baja: {
        label: 'Baja',
        icon: '↓',
        badgeClass: 'bg-surface-raised text-content-secondary border border-border-interactive font-medium',
    },
    normal: {
        label: 'Normal',
        icon: '—',
        badgeClass: 'bg-surface-raised text-content-primary border border-border-interactive font-medium',
    },
    alta: {
        label: 'Alta',
        icon: '↑',
        badgeClass: 'bg-danger/15 text-red-700 dark:text-danger border border-danger/30 font-bold',
    },
};

const difficultyOptions = [
    { value: 'easy', label: 'Fácil (+20 XP)' },
    { value: 'medium', label: 'Media (+35 XP)' },
    { value: 'hard', label: 'Difícil (+50 XP)' },
];

const priorityOptions = [
    { value: 'baja', label: 'Baja' },
    { value: 'normal', label: 'Normal' },
    { value: 'alta', label: 'Alta' },
];

const quadrantOptions = [
    { value: 'q1', label: 'Q1: Hacer YA (Urgente e Importante)' },
    { value: 'q2', label: 'Q2: Planificar (Importante, No Urgente)' },
    { value: 'q3', label: 'Q3: Minimizar (Urgente, No Importante)' },
    { value: 'q4', label: 'Q4: Descartar (Ni Urgente ni Importante)' },
];

function quickChangeQuadrant(newQuadrant) {
    router.post(
        route('missions.quadrant', { id: props.mission.id }),
        { quadrant: newQuadrant },
        { preserveScroll: true, preserveState: true },
    );
}

// Subtareas
function toggleSubtask(subtaskId) {
    router.post(
        route('missions.subtasks.toggle', { id: props.mission.id, subtaskId }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

function handleAddSubtask() {
    const title = newSubtaskTitle.value.trim();
    if (!title) return;
    router.post(
        route('missions.subtasks.store', { id: props.mission.id }),
        { title },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                newSubtaskTitle.value = '';
            },
        },
    );
}

function startEditSubtask(subtask) {
    editingSubtaskId.value = subtask.id;
    editingSubtaskTitle.value = subtask.title;
}

function saveEditSubtask(subtaskId) {
    const title = editingSubtaskTitle.value.trim();
    if (!title) {
        editingSubtaskId.value = null;
        return;
    }
    router.patch(
        route('missions.subtasks.update', { id: props.mission.id, subtaskId }),
        { title },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                editingSubtaskId.value = null;
            },
        },
    );
}

function cancelEditSubtask() {
    editingSubtaskId.value = null;
}

// Acciones principales
function completeMission() {
    router.post(route('missions.complete', { id: props.mission.id }), {}, { preserveScroll: true });
}

function uncompleteMission() {
    router.post(route('missions.uncomplete', { id: props.mission.id }), {}, { preserveScroll: true });
}

function startPomodoro() {
    router.visit(route('pomodoro.index'), {
        data: { mission_id: props.mission.id },
    });
}

function formatSessionTime(isoString) {
    try {
        return new Date(isoString).toLocaleString('es-PE', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return isoString;
    }
}

function formatDueDate(dateStr) {
    if (!dateStr) return '';
    try {
        const [year, month, day] = dateStr.split('-');
        const date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
        return date.toLocaleDateString('es-PE', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
}

function formatRelativeDue(dateStr, isOverdue, isCompleted, daysEarlyOrLate) {
    if (isCompleted && daysEarlyOrLate !== null) {
        if (daysEarlyOrLate < 0) return `${Math.abs(daysEarlyOrLate)} días antes de tiempo`;
        if (daysEarlyOrLate === 0) return 'El mismo día del plazo';
        return `${daysEarlyOrLate} días después del plazo`;
    }
    if (!dateStr) return null;
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const [y, m, d] = dateStr.split('-');
    const target = new Date(parseInt(y), parseInt(m) - 1, parseInt(d));
    const diffTime = target - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Vence hoy';
    if (diffDays === 1) return 'Vence mañana';
    if (diffDays > 1) return `Vence en ${diffDays} días`;
    if (diffDays === -1) return 'Venció ayer';
    return `Venció hace ${Math.abs(diffDays)} días`;
}
</script>

<template>
    <Head :title="`${mission.title} — Misiones — Epycus`" />

    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6 pb-12">

            <!-- Navegación Superior (Breadcrumbs & Acciones) -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-content-secondary hover:text-content-primary transition cursor-pointer"
                    @click="router.visit(route('missions.index'))"
                >
                    <ArrowLeft :size="16" />
                    <span>Volver a Misiones</span>
                </button>

                <div class="flex items-center gap-2">
                    <BaseButton
                        variant="secondary"
                        size="sm"
                        class="flex items-center gap-1.5"
                        @click="openEditModal"
                    >
                        <Edit3 :size="14" />
                        <span>Editar</span>
                    </BaseButton>
                    <BaseButton
                        variant="ghost"
                        size="sm"
                        class="flex items-center gap-1.5 text-danger-text hover:bg-danger/10"
                        @click="deleteMission"
                    >
                        <Trash2 :size="14" />
                        <span>Eliminar</span>
                    </BaseButton>
                </div>
            </div>

            <!-- Tarjeta Principal del Detalle de la Misión -->
            <BaseCard
                class="p-6 lg:p-8"
                :class="
                    mission.is_completed
                        ? 'border-l-4 border-l-success'
                        : mission.is_overdue
                          ? 'border-l-4 border-l-danger'
                          : 'border-l-4 border-l-primary-strong'
                "
            >
                <div class="space-y-6">

                    <!-- Cabecera de Estado y Título -->
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="space-y-2 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Badge de Estado -->
                                <span
                                    v-if="mission.is_completed"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30"
                                >
                                    <CheckCircle :size="13" /> Completada
                                </span>
                                <span
                                    v-else-if="mission.is_overdue"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold bg-danger/20 text-red-700 dark:text-danger border border-danger/30"
                                >
                                    <AlertTriangle :size="13" /> Vencida
                                </span>
                                <span
                                    v-else-if="mission.subtask_done > 0"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold bg-primary-strong/20 text-primary-strong border border-primary-strong/30"
                                >
                                    <Clock :size="13" /> En Progreso
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-surface-raised text-content-secondary border border-border-interactive"
                                >
                                    <Target :size="13" /> Pendiente
                                </span>

                                <!-- Cuadrante de Eisenhower -->
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs"
                                    :class="eisenhowerConfig[mission.eisenhower_quadrant || 'q2']?.badgeClass"
                                >
                                    <component
                                        :is="eisenhowerConfig[mission.eisenhower_quadrant || 'q2']?.icon"
                                        :size="13"
                                    />
                                    <span>{{ eisenhowerConfig[mission.eisenhower_quadrant || 'q2']?.title }}</span>
                                </span>
                            </div>

                            <h1
                                class="font-display text-2xl lg:text-3xl font-bold tracking-tight text-content-primary break-words"
                                :class="{ 'line-through opacity-70': mission.is_completed }"
                            >
                                {{ mission.title }}
                            </h1>

                            <p
                                v-if="mission.description"
                                class="text-content-secondary text-sm lg:text-base leading-relaxed whitespace-pre-line pt-1"
                            >
                                {{ mission.description }}
                            </p>
                            <p v-else class="text-content-muted text-sm italic pt-1">
                                Sin descripción detallada.
                            </p>
                        </div>

                        <!-- Botones de Acción Primaria -->
                        <div class="flex flex-row sm:flex-col gap-2.5 shrink-0">
                            <template v-if="!mission.is_completed">
                                <BaseButton
                                    variant="primary"
                                    class="flex items-center justify-center gap-2 shadow-sm font-bold"
                                    @click="completeMission"
                                >
                                    <CheckCircle2 :size="16" />
                                    <span>Completar</span>
                                </BaseButton>
                                <BaseButton
                                    variant="secondary"
                                    class="flex items-center justify-center gap-2"
                                    @click="startPomodoro"
                                >
                                    <Timer :size="16" class="text-primary-strong" />
                                    <span>Enfocarme</span>
                                </BaseButton>
                            </template>
                            <template v-else>
                                <BaseButton
                                    variant="secondary"
                                    class="flex items-center justify-center gap-2"
                                    @click="uncompleteMission"
                                >
                                    <RotateCcw :size="16" />
                                    <span>Reactivar Misión</span>
                                </BaseButton>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-border-interactive/60 pt-4"></div>

                    <!-- Fila de Atributos y Recompensas (Chips informativos) -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">

                        <!-- Dificultad -->
                        <div class="rounded-xl border border-border-interactive/60 bg-surface-raised/40 p-3 space-y-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">Dificultad</span>
                            <div class="flex items-center gap-1.5 pt-0.5">
                                <span
                                    class="rounded px-2 py-0.5 text-xs font-semibold"
                                    :class="difficultyConfig[mission.difficulty]?.badgeClass"
                                >
                                    {{ difficultyConfig[mission.difficulty]?.label }}
                                </span>
                            </div>
                        </div>

                        <!-- Prioridad -->
                        <div class="rounded-xl border border-border-interactive/60 bg-surface-raised/40 p-3 space-y-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">Prioridad</span>
                            <div class="flex items-center gap-1.5 pt-0.5">
                                <span
                                    class="rounded px-2 py-0.5 text-xs"
                                    :class="priorityConfig[mission.priority]?.badgeClass"
                                >
                                    {{ priorityConfig[mission.priority]?.icon }} {{ priorityConfig[mission.priority]?.label }}
                                </span>
                            </div>
                        </div>

                        <!-- Vencimiento -->
                        <div class="rounded-xl border border-border-interactive/60 bg-surface-raised/40 p-3 space-y-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">Plazo / Vencimiento</span>
                            <div class="pt-0.5">
                                <div
                                    v-if="mission.due_date"
                                    class="font-semibold text-content-primary flex items-center gap-1 cursor-default"
                                    :title="formatDueDate(mission.due_date)"
                                >
                                    <Calendar :size="13" class="text-content-muted shrink-0" />
                                    <span>{{ mission.due_date }}</span>
                                </div>
                                <span v-else class="text-content-muted italic">Sin fecha límite</span>
                                <p
                                    v-if="mission.due_date"
                                    class="text-[10px] mt-0.5"
                                    :class="mission.is_overdue && !mission.is_completed ? 'text-danger-text dark:text-danger font-bold' : 'text-content-muted'"
                                >
                                    {{ formatRelativeDue(mission.due_date, mission.is_overdue, mission.is_completed, mission.days_early_or_late) }}
                                </p>
                            </div>
                        </div>

                        <!-- Recompensa XP -->
                        <div class="rounded-xl border border-border-interactive/60 bg-surface-raised/40 p-3 space-y-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-content-muted">Recompensa XP</span>
                            <div class="flex items-center gap-1 pt-0.5">
                                <Sparkles :size="14" class="text-amber-500" />
                                <span class="font-bold text-sm text-content-primary">
                                    +{{ mission.xp_awarded > 0 ? mission.xp_awarded : difficultyConfig[mission.difficulty]?.xp }} XP
                                </span>
                                <span v-if="mission.is_completed" class="text-[10px] font-bold text-emerald-800 dark:text-emerald-300 ml-1">
                                    (Ganada)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Selector rápido de Cuadrante de Eisenhower -->
                    <div class="rounded-xl bg-surface-sunken/60 border border-border-interactive p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <component
                                :is="eisenhowerConfig[mission.eisenhower_quadrant || 'q2']?.icon"
                                :size="16"
                                class="text-primary-strong shrink-0"
                            />
                            <div>
                                <span class="font-semibold text-content-primary">
                                    {{ eisenhowerConfig[mission.eisenhower_quadrant || 'q2']?.title }}
                                </span>
                                <span class="text-content-secondary ml-1 hidden sm:inline">
                                    — {{ eisenhowerConfig[mission.eisenhower_quadrant || 'q2']?.description }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <label class="text-[11px] font-medium text-content-muted">Mover a:</label>
                            <select
                                class="rounded-lg border border-border-interactive bg-surface px-2.5 py-1 text-xs font-semibold text-content-primary outline-none cursor-pointer shadow-xs"
                                :value="mission.eisenhower_quadrant || 'q2'"
                                @change="quickChangeQuadrant($event.target.value)"
                            >
                                <option value="q1">Q1: Hacer YA</option>
                                <option value="q2">Q2: Planificar</option>
                                <option value="q3">Q3: Minimizar</option>
                                <option value="q4">Q4: Descartar</option>
                            </select>
                        </div>
                    </div>

                </div>
            </BaseCard>

            <!-- Sección de Subtareas con Progreso y Checklist -->
            <BaseCard class="p-6 lg:p-7">
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="font-display text-lg font-bold text-content-primary flex items-center gap-2">
                                <span>Subtareas</span>
                                <span class="text-xs font-semibold rounded-full px-2 py-0.5 bg-surface-raised border border-border-interactive text-content-secondary">
                                    {{ mission.subtask_done }} de {{ mission.subtask_count }}
                                </span>
                            </h2>
                            <p class="text-xs text-content-secondary mt-0.5">
                                Divide tu objetivo en pasos concretos y alcanzables.
                            </p>
                        </div>

                        <div v-if="mission.subtask_count > 0" class="w-full sm:w-48 space-y-1">
                            <ProgressBar
                                :value="mission.subtask_done"
                                :max="mission.subtask_count"
                                color="bg-primary-strong"
                                size="h-2.5"
                            />
                            <p class="text-[11px] font-semibold text-right text-content-muted">
                                {{ Math.round((mission.subtask_done / mission.subtask_count) * 100) }}% completado
                            </p>
                        </div>
                    </div>

                    <!-- Input rápido para agregar subtarea -->
                    <form
                        class="flex items-center gap-2 pt-2"
                        @submit.prevent="handleAddSubtask"
                    >
                        <input
                            v-model="newSubtaskTitle"
                            type="text"
                            placeholder="Añadir nueva subtarea (ej. Leer capítulo 2, resolver 5 ejercicios)…"
                            class="flex-1 rounded-xl border border-border-interactive bg-surface px-3.5 py-2 text-sm text-content-primary placeholder:text-content-muted outline-none focus:ring-2 focus:ring-primary-strong/40"
                        />
                        <BaseButton
                            type="submit"
                            variant="secondary"
                            size="sm"
                            :disabled="!newSubtaskTitle.trim()"
                            class="flex items-center gap-1 shrink-0"
                        >
                            <Plus :size="15" />
                            <span>Añadir</span>
                        </BaseButton>
                    </form>

                    <!-- Lista de Subtareas -->
                    <div v-if="mission.subtasks.length > 0" class="space-y-2.5 pt-2">
                        <div
                            v-for="s in mission.subtasks"
                            :key="s.id"
                            class="group flex items-center justify-between gap-3 rounded-xl border border-border-interactive/60 p-3 transition"
                            :class="
                                s.is_completed
                                    ? 'bg-surface-sunken/60 opacity-80'
                                    : 'bg-surface-raised/40 hover:bg-surface-raised'
                            "
                        >
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <input
                                    type="checkbox"
                                    :checked="s.is_completed"
                                    :disabled="mission.is_completed"
                                    class="h-4 w-4 rounded border-border-interactive text-primary-strong focus:ring-primary-strong cursor-pointer"
                                    @change="toggleSubtask(s.id)"
                                />

                                <!-- Modo edición inline -->
                                <div v-if="editingSubtaskId === s.id" class="flex items-center gap-2 flex-1">
                                    <input
                                        v-model="editingSubtaskTitle"
                                        type="text"
                                        class="flex-1 rounded-lg border border-border-interactive bg-surface px-2.5 py-1 text-sm text-content-primary outline-none"
                                        @keydown.enter.prevent="saveEditSubtask(s.id)"
                                        @keydown.esc="cancelEditSubtask"
                                    />
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-success hover:underline"
                                        @click="saveEditSubtask(s.id)"
                                    >
                                        Guardar
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs text-content-muted hover:underline"
                                        @click="cancelEditSubtask"
                                    >
                                        Cancelar
                                    </button>
                                </div>

                                <!-- Vista normal -->
                                <span
                                    v-else
                                    class="text-sm font-medium text-content-primary break-words flex-1 cursor-pointer select-none"
                                    :class="{ 'line-through text-content-muted': s.is_completed }"
                                    @click="!mission.is_completed && toggleSubtask(s.id)"
                                >
                                    {{ s.title }}
                                </span>
                            </div>

                            <!-- Botón de edición rápida -->
                            <button
                                v-if="editingSubtaskId !== s.id && !mission.is_completed"
                                type="button"
                                class="opacity-0 group-hover:opacity-100 p-1 text-content-muted hover:text-content-primary rounded transition"
                                title="Editar título"
                                @click="startEditSubtask(s)"
                            >
                                <Pencil :size="13" />
                            </button>
                        </div>
                    </div>

                    <!-- Estado sin subtareas -->
                    <div
                        v-else
                        class="rounded-xl border border-dashed border-border-interactive/80 p-6 text-center text-xs text-content-muted space-y-1"
                    >
                        <p class="font-medium text-content-secondary">
                            Aún no has agregado subtareas a esta misión.
                        </p>
                        <p>
                            Escribe una acción concreta arriba para empezar a dividir tu meta.
                        </p>
                    </div>
                </div>
            </BaseCard>

            <!-- Historial y Métricas de Pomodoro para esta Misión -->
            <BaseCard class="p-6 lg:p-7">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="font-display text-lg font-bold text-content-primary flex items-center gap-2">
                                <Timer :size="18" class="text-primary-strong" />
                                <span>Sesiones Pomodoro de esta Misión</span>
                            </h2>
                            <p class="text-xs text-content-secondary mt-0.5">
                                Registro de bloques de concentración vinculados a esta tarea.
                            </p>
                        </div>

                        <div v-if="pomodoroSessions.length > 0" class="text-right">
                            <span class="font-display text-xl font-bold text-primary-strong">
                                {{ totalPomodoroMinutes }} min
                            </span>
                            <p class="text-[10px] text-content-muted uppercase font-semibold tracking-wider">
                                {{ pomodoroSessions.length }} sesión{{ pomodoroSessions.length !== 1 ? 'es' : '' }}
                            </p>
                        </div>
                    </div>

                    <!-- Lista de sesiones -->
                    <div v-if="pomodoroSessions.length > 0" class="space-y-3 pt-2">
                        <div
                            v-for="s in pomodoroSessions"
                            :key="s.id"
                            class="rounded-xl border border-border-interactive/60 bg-surface-raised/40 p-3.5 text-sm transition"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 font-medium text-content-primary">
                                    <Clock :size="14" class="text-content-muted" />
                                    <span>{{ formatSessionTime(s.started_at) }}</span>
                                </div>
                                <span class="rounded-full bg-primary-strong/20 px-2.5 py-0.5 text-xs font-bold text-primary-strong">
                                    {{ s.focus_minutes }} min de foco
                                </span>
                            </div>

                            <!-- Subtareas completadas durante esta sesión -->
                            <div v-if="s.subtasks && s.subtasks.length > 0" class="mt-2 pt-2 border-t border-border-interactive/40 space-y-1">
                                <p class="text-xs font-semibold text-content-muted flex items-center gap-1">
                                    <Check :size="12" class="text-success" />
                                    <span>Subtareas completadas en esta sesión:</span>
                                </p>
                                <ul class="list-inside list-disc text-xs text-content-secondary pl-1 space-y-0.5">
                                    <li v-for="st in s.subtasks" :key="st.id">
                                        {{ st.title }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Estado sin sesiones -->
                    <div
                        v-else
                        class="rounded-xl border border-dashed border-border-interactive/80 p-6 text-center space-y-3"
                    >
                        <p class="text-xs text-content-secondary">
                            Aún no has registrado sesiones de Pomodoro para esta misión.
                        </p>
                        <BaseButton
                            variant="secondary"
                            size="sm"
                            class="inline-flex items-center gap-2"
                            @click="startPomodoro"
                        >
                            <Timer :size="14" />
                            <span>Iniciar sesión de foco (25 min)</span>
                        </BaseButton>
                    </div>
                </div>
            </BaseCard>

        </div>

        <!-- Modal para Editar Misión -->
        <BaseModal
            :show="showEditModal"
            title="Editar Misión"
            @close="showEditModal = false"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <BaseInput
                    v-model="editForm.title"
                    label="Título de la Misión"
                    placeholder="Ej. Estudiar para el examen de Cálculo"
                    :error="editForm.errors.title"
                    required
                />

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-content-secondary mb-1">
                        Descripción
                    </label>
                    <textarea
                        v-model="editForm.description"
                        rows="3"
                        placeholder="Detalla de qué trata esta misión, notas o instrucciones…"
                        class="w-full rounded-xl border border-border-interactive bg-surface px-3.5 py-2 text-sm text-content-primary placeholder:text-content-muted outline-none focus:ring-2 focus:ring-primary-strong/40"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <BaseSelect
                        v-model="editForm.difficulty"
                        label="Dificultad"
                        :options="difficultyOptions"
                        :error="editForm.errors.difficulty"
                        required
                    />

                    <BaseSelect
                        v-model="editForm.priority"
                        label="Prioridad"
                        :options="priorityOptions"
                        :error="editForm.errors.priority"
                        required
                    />
                </div>

                <BaseSelect
                    v-model="editForm.eisenhower_quadrant"
                    label="Cuadrante de Eisenhower"
                    :options="quadrantOptions"
                    :error="editForm.errors.eisenhower_quadrant"
                />

                <BaseInput
                    v-model="editForm.due_date"
                    type="date"
                    label="Fecha Límite (Opcional)"
                    :error="editForm.errors.due_date"
                />

                <div class="mt-6 flex justify-end gap-2 pt-2 border-t border-border-interactive/40">
                    <BaseButton
                        type="button"
                        variant="secondary"
                        @click="showEditModal = false"
                    >
                        Cancelar
                    </BaseButton>
                    <BaseButton
                        type="submit"
                        variant="primary"
                        :disabled="editForm.processing"
                    >
                        {{ editForm.processing ? 'Guardando…' : 'Guardar Cambios' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
