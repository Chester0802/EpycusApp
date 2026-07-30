<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    missions: { type: Array, default: () => [] },
    completedMissions: { type: Array, default: () => [] },
    todayDate: { type: String, required: true },
    sortBy: { type: String, default: 'default' },
    avatarImage: { type: String, default: null },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showCompleted = ref(false);
const editingMission = ref(null);
const editingSubtaskId = ref(null);
const editingSubtaskTitle = ref('');
const addSubtaskTitles = reactive({});
const dragSubtaskId = ref(null);

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

const overdueCount = computed(() => props.missions.filter(m => m.is_overdue).length);
const activeCount = computed(() => props.missions.length);

const todayMissions = computed(() =>
    props.missions.filter(m => m.due_date === props.todayDate && !m.is_overdue)
);

const dueSoon = computed(() => {
    const today = new Date(props.todayDate);
    const weekEnd = new Date(today);
    weekEnd.setDate(weekEnd.getDate() + 7);
    return props.missions.filter(m =>
        m.due_date && !m.is_overdue &&
        m.due_date > props.todayDate &&
        new Date(m.due_date) <= weekEnd
    );
});

const restMissions = computed(() => {
    const today = new Date(props.todayDate);
    const weekEnd = new Date(today);
    weekEnd.setDate(weekEnd.getDate() + 7);
    return props.missions.filter(m =>
        !m.due_date || new Date(m.due_date) > weekEnd
    ).filter(m => !m.is_overdue);
});

const overdueMissions = computed(() => props.missions.filter(m => m.is_overdue));

const createForm = useForm({
    title: '',
    description: '',
    difficulty: 'medium',
    priority: 'normal',
    due_date: '',
    subtasks: [''],
});

const editForm = useForm({
    title: '',
    description: '',
    difficulty: 'medium',
    priority: 'normal',
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

function openCreateModal() {
    createForm.reset();
    createForm.subtasks = [''];
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createForm.reset();
}

function submitCreate() {
    const subtasks = createForm.subtasks.filter(s => s.trim().length > 0);
    createForm.subtasks = subtasks;
    createForm.post(route('missions.store'), {
        onSuccess: () => closeCreateModal(),
    });
}

function openEditModal(mission) {
    editingMission.value = mission;
    editForm.title = mission.title;
    editForm.description = mission.description || '';
    editForm.difficulty = mission.difficulty;
    editForm.priority = mission.priority;
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

function completeMission(missionId) {
    router.post(route('missions.complete', { id: missionId }), {}, { preserveScroll: true });
}

function toggleSubtask(missionId, subtaskId) {
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
    const mission = props.missions.find(m => m.id === missionId);
    if (!mission) return;
    const ids = mission.subtasks.map(s => s.id);
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
    router.get(route('missions.index', { sort_by: sort === 'default' ? undefined : sort }), { preserveScroll: true, preserveState: true });
}

function deleteMission(missionId) {
    if (confirm('¿Eliminar esta misión? Se conservará su historial.')) {
        router.delete(route('missions.destroy', { id: missionId }));
    }
}

function startPomodoro(missionId) {
    router.visit(route('pomodoro.index'), {
        data: { mission_id: missionId },
    });
}

function stateClass(state) {
    const classes = {
        overdue: 'border-l-danger',
        pending: 'border-l-border',
        in_progress: 'border-l-primary',
        completed: 'border-l-success',
    };
    return classes[state] || 'border-l-border';
}
</script>

<template>
    <Head title="Misiones — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-4xl">
            <BaseCard class="mb-8">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div v-if="avatarImage" class="flex h-32 w-20 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-1">
                            <img :src="avatarImage" alt="" class="h-full w-full object-contain" />
                        </div>
                        <div>
                            <h1 class="font-display text-3xl text-content-primary">Misiones</h1>
                            <p class="mt-1 text-sm text-content-secondary">Descompón tus tareas grandes en pasos manejables.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select
                            class="rounded-lg border-border bg-surface px-3 py-1.5 text-sm text-content-secondary outline-none"
                            :value="sortBy" @change="changeSort(($event.target).value)">
                            <option value="default">Orden por defecto</option>
                            <option value="priority">Por prioridad</option>
                            <option value="difficulty">Por dificultad</option>
                            <option value="created_at">Por creación</option>
                        </select>
                        <BaseButton variant="primary" @click="openCreateModal">+ Nueva Misión</BaseButton>
                    </div>
                </header>
            </BaseCard>

            <div v-if="missions.length > 0 || completedMissions.length > 0" class="space-y-6">
                <div v-if="missions.length > 0">
                    <div class="mb-4 grid grid-cols-3 gap-4">
                        <BaseCard class="p-4 text-center">
                            <p class="font-display text-2xl text-content-primary">{{ activeCount }}</p>
                            <p class="text-xs text-content-muted">Activas</p>
                        </BaseCard>
                        <BaseCard class="p-4 text-center">
                            <p class="font-display text-2xl text-content-primary">{{ overdueCount }}</p>
                            <p class="text-xs text-content-muted">Vencidas</p>
                        </BaseCard>
                        <BaseCard class="p-4 text-center">
                            <p class="font-display text-2xl text-content-secondary">{{ completedMissions.length }}</p>
                            <p class="text-xs text-content-muted">Completadas</p>
                        </BaseCard>
                    </div>

                    <div v-if="overdueMissions.length > 0" class="mb-4">
                        <h2 class="mb-2 text-sm font-semibold text-danger">Vencidas ({{ overdueMissions.length }})</h2>
                        <div class="space-y-2">
                            <BaseCard
v-for="m in overdueMissions" :key="m.id"
                                class="border-l-4 p-4" :class="stateClass(m.state)">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <a :href="route('missions.show', { id: m.id })" class="font-semibold text-content-primary hover:text-primary-strong">{{ m.title }}</a>
                                        <p v-if="m.description" class="mt-0.5 text-sm text-content-secondary truncate">{{ m.description }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                            <span :class="difficultyConfig[m.difficulty]?.class">{{ difficultyConfig[m.difficulty]?.label }}</span>
                                            <span :class="priorityConfig[m.priority]?.class">{{ priorityConfig[m.priority]?.label }}</span>
                                            <span v-if="m.due_date" class="text-danger-text">Vencía {{ m.due_date }}</span>
                                            <span v-if="m.subtask_count > 0" class="text-content-muted">
                                                {{ m.subtask_done }}/{{ m.subtask_count }}
                                            </span>
                                        </div>
                                        <div class="mt-2 space-y-1">
                                            <div
v-for="s in m.subtasks" :key="s.id"
                                                class="flex items-center gap-1.5 rounded px-1 text-sm"
                                                :class="{ 'line-through text-content-muted': s.is_completed, 'bg-surface-raised': dragSubtaskId === s.id }"
                                                draggable="true"
                                                @dragstart="onDragStart(s.id)"
                                                @dragover="onDragOver"
                                                @drop="onDrop(m.id, s.id)">
                                                <span class="cursor-grab text-content-muted select-none">⠿</span>
                                                <input
type="checkbox" :checked="s.is_completed"
                                                    class="h-4 w-4 shrink-0 accent-primary"
                                                    @change="toggleSubtask(m.id, s.id)" />
                                                <template v-if="editingSubtaskId === s.id">
                                                    <input
ref="editInput"
                                                        v-model="editingSubtaskTitle"
                                                        class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-sm outline-none focus:border-primary"
                                                        @keyup.enter="saveEditSubtask(m.id, s.id)"
                                                        @keyup.escape="cancelEditSubtask"
                                                        @blur="saveEditSubtask(m.id, s.id)" />
                                                </template>
                                                <template v-else>
                                                    <span
class="min-w-0 flex-1 cursor-pointer truncate"
                                                        @click="startEditSubtask(s)">{{ s.title }}</span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-1.5 pl-5">
                                                <input
v-model="addSubtaskTitles[m.id]"
                                                    class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-xs outline-none placeholder:text-content-muted focus:border-primary"
                                                    placeholder="+ Nueva subtarea…"
                                                    @keyup.enter="addSubtask(m.id)" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-start gap-1">
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Completar misión" @click="completeMission(m.id)">✅</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-primary-strong"
                                            title="Enfocarme" @click="startPomodoro(m.id)">⏱</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Editar" @click="openEditModal(m)">✏️</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-danger-text"
                                            title="Eliminar" @click="deleteMission(m.id)">🗑️</button>
                                    </div>
                                </div>
                            </BaseCard>
                        </div>
                    </div>

                    <div v-if="todayMissions.length > 0" class="mb-4">
                        <h2 class="mb-2 text-sm font-semibold text-accent">Vence hoy</h2>
                        <div class="space-y-2">
                            <BaseCard
v-for="m in todayMissions" :key="m.id"
                                class="border-l-4 p-4" :class="stateClass(m.state)">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-semibold text-content-primary">{{ m.title }}</h3>
                                        <p v-if="m.description" class="mt-0.5 text-sm text-content-secondary truncate">{{ m.description }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                            <span :class="difficultyConfig[m.difficulty]?.class">{{ difficultyConfig[m.difficulty]?.label }}</span>
                                            <span :class="priorityConfig[m.priority]?.class">{{ priorityConfig[m.priority]?.label }}</span>
                                            <span v-if="m.subtask_count > 0" class="text-content-muted">
                                                {{ m.subtask_done }}/{{ m.subtask_count }}
                                            </span>
                                        </div>
                                        <div class="mt-2 space-y-1">
                                            <div
v-for="s in m.subtasks" :key="s.id"
                                                class="flex items-center gap-1.5 rounded px-1 text-sm"
                                                :class="{ 'line-through text-content-muted': s.is_completed, 'bg-surface-raised': dragSubtaskId === s.id }"
                                                draggable="true"
                                                @dragstart="onDragStart(s.id)"
                                                @dragover="onDragOver"
                                                @drop="onDrop(m.id, s.id)">
                                                <span class="cursor-grab text-content-muted select-none">⠿</span>
                                                <input
type="checkbox" :checked="s.is_completed"
                                                    class="h-4 w-4 shrink-0 accent-primary"
                                                    @change="toggleSubtask(m.id, s.id)" />
                                                <template v-if="editingSubtaskId === s.id">
                                                    <input
ref="editInput"
                                                        v-model="editingSubtaskTitle"
                                                        class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-sm outline-none focus:border-primary"
                                                        @keyup.enter="saveEditSubtask(m.id, s.id)"
                                                        @keyup.escape="cancelEditSubtask"
                                                        @blur="saveEditSubtask(m.id, s.id)" />
                                                </template>
                                                <template v-else>
                                                    <span
class="min-w-0 flex-1 cursor-pointer truncate"
                                                        @click="startEditSubtask(s)">{{ s.title }}</span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-1.5 pl-5">
                                                <input
v-model="addSubtaskTitles[m.id]"
                                                    class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-xs outline-none placeholder:text-content-muted focus:border-primary"
                                                    placeholder="+ Nueva subtarea…"
                                                    @keyup.enter="addSubtask(m.id)" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-start gap-1">
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Completar misión" @click="completeMission(m.id)">✅</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-primary-strong"
                                            title="Enfocarme" @click="startPomodoro(m.id)">⏱</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Editar" @click="openEditModal(m)">✏️</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-danger-text"
                                            title="Eliminar" @click="deleteMission(m.id)">🗑️</button>
                                    </div>
                                </div>
                            </BaseCard>
                        </div>
                    </div>

                    <div v-if="dueSoon.length > 0" class="mb-4">
                        <h2 class="mb-2 text-sm font-semibold text-content-secondary">Vence esta semana</h2>
                        <div class="space-y-2">
                            <BaseCard
v-for="m in dueSoon" :key="m.id"
                                class="border-l-4 p-4" :class="stateClass(m.state)">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <a :href="route('missions.show', { id: m.id })" class="font-semibold text-content-primary hover:text-primary-strong">{{ m.title }}</a>
                                        <p v-if="m.description" class="mt-0.5 text-sm text-content-secondary truncate">{{ m.description }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                            <span :class="difficultyConfig[m.difficulty]?.class">{{ difficultyConfig[m.difficulty]?.label }}</span>
                                            <span :class="priorityConfig[m.priority]?.class">{{ priorityConfig[m.priority]?.label }}</span>
                                            <span v-if="m.due_date" class="text-content-muted">{{ m.due_date }}</span>
                                            <span v-if="m.subtask_count > 0" class="text-content-muted">
                                                {{ m.subtask_done }}/{{ m.subtask_count }}
                                            </span>
                                        </div>
                                        <div class="mt-2 space-y-1">
                                            <div
v-for="s in m.subtasks" :key="s.id"
                                                class="flex items-center gap-1.5 rounded px-1 text-sm"
                                                :class="{ 'line-through text-content-muted': s.is_completed, 'bg-surface-raised': dragSubtaskId === s.id }"
                                                draggable="true"
                                                @dragstart="onDragStart(s.id)"
                                                @dragover="onDragOver"
                                                @drop="onDrop(m.id, s.id)">
                                                <span class="cursor-grab text-content-muted select-none">⠿</span>
                                                <input
type="checkbox" :checked="s.is_completed"
                                                    class="h-4 w-4 shrink-0 accent-primary"
                                                    @change="toggleSubtask(m.id, s.id)" />
                                                <template v-if="editingSubtaskId === s.id">
                                                    <input
ref="editInput"
                                                        v-model="editingSubtaskTitle"
                                                        class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-sm outline-none focus:border-primary"
                                                        @keyup.enter="saveEditSubtask(m.id, s.id)"
                                                        @keyup.escape="cancelEditSubtask"
                                                        @blur="saveEditSubtask(m.id, s.id)" />
                                                </template>
                                                <template v-else>
                                                    <span
class="min-w-0 flex-1 cursor-pointer truncate"
                                                        @click="startEditSubtask(s)">{{ s.title }}</span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-1.5 pl-5">
                                                <input
v-model="addSubtaskTitles[m.id]"
                                                    class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-xs outline-none placeholder:text-content-muted focus:border-primary"
                                                    placeholder="+ Nueva subtarea…"
                                                    @keyup.enter="addSubtask(m.id)" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-start gap-1">
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Completar misión" @click="completeMission(m.id)">✅</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-primary-strong"
                                            title="Enfocarme" @click="startPomodoro(m.id)">⏱</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Editar" @click="openEditModal(m)">✏️</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-danger-text"
                                            title="Eliminar" @click="deleteMission(m.id)">🗑️</button>
                                    </div>
                                </div>
                            </BaseCard>
                        </div>
                    </div>

                    <div v-if="restMissions.length > 0" class="mb-4">
                        <h2 class="mb-2 text-sm font-semibold text-content-muted">Otras misiones</h2>
                        <div class="space-y-2">
                            <BaseCard
v-for="m in restMissions" :key="m.id"
                                class="border-l-4 p-4" :class="stateClass(m.state)">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <a :href="route('missions.show', { id: m.id })" class="font-semibold text-content-primary hover:text-primary-strong">{{ m.title }}</a>
                                        <p v-if="m.description" class="mt-0.5 text-sm text-content-secondary truncate">{{ m.description }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                            <span :class="difficultyConfig[m.difficulty]?.class">{{ difficultyConfig[m.difficulty]?.label }}</span>
                                            <span :class="priorityConfig[m.priority]?.class">{{ priorityConfig[m.priority]?.label }}</span>
                                            <span v-if="m.due_date" class="text-content-muted">{{ m.due_date }}</span>
                                            <span v-if="m.subtask_count > 0" class="text-content-muted">
                                                {{ m.subtask_done }}/{{ m.subtask_count }}
                                            </span>
                                        </div>
                                        <div class="mt-2 space-y-1">
                                            <div
v-for="s in m.subtasks" :key="s.id"
                                                class="flex items-center gap-1.5 rounded px-1 text-sm"
                                                :class="{ 'line-through text-content-muted': s.is_completed, 'bg-surface-raised': dragSubtaskId === s.id }"
                                                draggable="true"
                                                @dragstart="onDragStart(s.id)"
                                                @dragover="onDragOver"
                                                @drop="onDrop(m.id, s.id)">
                                                <span class="cursor-grab text-content-muted select-none">⠿</span>
                                                <input
type="checkbox" :checked="s.is_completed"
                                                    class="h-4 w-4 shrink-0 accent-primary"
                                                    @change="toggleSubtask(m.id, s.id)" />
                                                <template v-if="editingSubtaskId === s.id">
                                                    <input
ref="editInput"
                                                        v-model="editingSubtaskTitle"
                                                        class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-sm outline-none focus:border-primary"
                                                        @keyup.enter="saveEditSubtask(m.id, s.id)"
                                                        @keyup.escape="cancelEditSubtask"
                                                        @blur="saveEditSubtask(m.id, s.id)" />
                                                </template>
                                                <template v-else>
                                                    <span
class="min-w-0 flex-1 cursor-pointer truncate"
                                                        @click="startEditSubtask(s)">{{ s.title }}</span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-1.5 pl-5">
                                                <input
v-model="addSubtaskTitles[m.id]"
                                                    class="min-w-0 flex-1 rounded border-border bg-surface px-1.5 py-0.5 text-xs outline-none placeholder:text-content-muted focus:border-primary"
                                                    placeholder="+ Nueva subtarea…"
                                                    @keyup.enter="addSubtask(m.id)" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-start gap-1">
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Completar misión" @click="completeMission(m.id)">✅</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-primary-strong"
                                            title="Enfocarme" @click="startPomodoro(m.id)">⏱</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-content-primary"
                                            title="Editar" @click="openEditModal(m)">✏️</button>
                                        <button
type="button"
                                            class="rounded p-1 text-sm text-content-muted hover:text-danger-text"
                                            title="Eliminar" @click="deleteMission(m.id)">🗑️</button>
                                    </div>
                                </div>
                            </BaseCard>
                        </div>
                    </div>
                </div>

                <div v-if="completedMissions.length > 0">
                    <button
type="button"
                        class="flex w-full items-center justify-between rounded-xl bg-surface px-4 py-2 text-sm font-semibold text-content-secondary transition hover:bg-surface-raised"
                        @click="showCompleted = !showCompleted">
                        <span>✅ Completadas ({{ completedMissions.length }})</span>
                        <span class="transition" :class="showCompleted ? 'rotate-180' : ''">▼</span>
                    </button>
                    <div v-if="showCompleted" class="mt-2 space-y-2">
                        <BaseCard v-for="m in completedMissions" :key="m.id" class="border-l-4 border-l-success p-4 opacity-70">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-content-primary line-through">{{ m.title }}</h3>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                        <span class="text-success">Completada {{ m.completed_at }}</span>
                                        <span v-if="m.xp_awarded > 0" class="text-accent">+{{ m.xp_awarded }} XP</span>
                                        <span v-if="m.days_early_or_late !== null" class="text-content-muted">
                                            {{ m.days_early_or_late < 0 ? `${Math.abs(m.days_early_or_late)} días antes` : `${m.days_early_or_late} días tarde` }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </BaseCard>
                    </div>
                </div>
            </div>

            <BaseCard
v-if="missions.length === 0 && completedMissions.length === 0"
                class="flex flex-col items-center p-12 text-center">
                <span class="mb-3 text-4xl">🎯</span>
                <h2 class="text-lg font-semibold text-content-primary">No tienes misiones</h2>
                <p class="mt-1 max-w-sm text-sm text-content-secondary">
                    Crea tu primera misión y divide las tareas grandes en pasos pequeños.
                </p>
                <BaseButton class="mt-6" variant="primary" @click="openCreateModal">Crear mi primera misión</BaseButton>
            </BaseCard>
        </div>

        <BaseModal :show="showCreateModal" title="Nueva Misión" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <BaseInput
id="create-title" v-model="createForm.title" label="Título"
                    placeholder="Ej. Terminar práctica de Cálculo" :error="createForm.errors.title" required />

                <BaseInput
id="create-desc" v-model="createForm.description" label="Descripción (opcional)"
                    placeholder="Detalles de la tarea..." />

                <div class="grid grid-cols-2 gap-4">
                    <BaseSelect
id="create-difficulty" v-model="createForm.difficulty" label="Dificultad"
                        :options="difficultyOptions" />
                    <BaseSelect
id="create-priority" v-model="createForm.priority" label="Prioridad"
                        :options="priorityOptions" />
                </div>

                <BaseInput
id="create-due" v-model="createForm.due_date" label="Fecha de vencimiento (opcional)"
                    type="date" />

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-content-secondary">
                        Subtareas
                        <span class="text-content-muted font-normal">(0–20, opcional)</span>
                    </legend>
                    <div class="space-y-2">
                        <div v-for="(_, i) in createForm.subtasks" :key="'st-'+i" class="flex items-center gap-2">
                            <BaseInput
:id="'subtask-'+i" v-model="createForm.subtasks[i]"
                                :placeholder="`Paso ${i+1}`" class="flex-1" />
                            <button
v-if="createForm.subtasks.length > 1" type="button"
                                class="shrink-0 rounded p-1.5 text-sm text-content-muted hover:text-danger-text"
                                @click="removeSubtaskField(i)">✕</button>
                        </div>
                    </div>
                    <button
v-if="createForm.subtasks.length < 20" type="button"
                        class="mt-2 text-sm text-content-secondary hover:text-content-primary"
                        @click="addSubtaskField">+ Agregar paso</button>
                </fieldset>

                <div
v-if="createForm.difficulty === 'hard' && createForm.subtasks.filter(s => s.trim()).length === 0"
                    class="rounded-lg bg-primary/20 px-3 py-2 text-sm text-content-secondary">
                    💡 Las tareas grandes se sienten menos pesadas por partes. ¿Quieres dividirla?
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton variant="ghost" type="button" @click="closeCreateModal">Cancelar</BaseButton>
                    <BaseButton type="submit" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Guardando…' : 'Crear Misión' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <BaseModal :show="showEditModal" :title="`Editar: ${editingMission?.title || ''}`" @close="closeEditModal">
            <form class="space-y-4" @submit.prevent="submitEdit">
                <BaseInput id="edit-title" v-model="editForm.title" label="Título" :error="editForm.errors.title" required />
                <BaseInput id="edit-desc" v-model="editForm.description" label="Descripción" />

                <div class="grid grid-cols-2 gap-4">
                    <BaseSelect
id="edit-difficulty" v-model="editForm.difficulty" label="Dificultad"
                        :options="difficultyOptions" />
                    <BaseSelect
id="edit-priority" v-model="editForm.priority" label="Prioridad"
                        :options="priorityOptions" />
                </div>

                <BaseInput id="edit-due" v-model="editForm.due_date" label="Fecha de vencimiento" type="date" />

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
