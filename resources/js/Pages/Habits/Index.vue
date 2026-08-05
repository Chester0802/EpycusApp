<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
import UsageTipBanner from '@/Components/ui/UsageTipBanner.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useTelemetry } from '@/Composables/useTelemetry';

const props = defineProps({
    habits: { type: Array, default: () => [] },
    archivedHabits: { type: Array, default: () => [] },
    todayDate: { type: String, required: true },
    avatarImage: { type: String, default: null },
    stats: {
        type: Object,
        default: () => ({ total_weekly: 0, max_streak: 0, active_habits: 0 }),
    },
});

const { track } = useTelemetry();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showArchived = ref(false);
const editingHabit = ref(null);
const xpNotification = ref(null);

const freqTypeOptions = [
    { value: 'daily', label: 'Diario' },
    { value: 'weekly_times', label: 'X veces por semana' },
    { value: 'weekly_days', label: 'Días específicos' },
];

const dayNames = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];

/*
 * iconOptions: el valor 'icon' es ahora el nombre de AppIcon (Lucide).
 * Se persiste este nombre en la base de datos en vez del emoji.
 * Retrocompatibilidad: habit.icon con valor antiguo (emoji) mostrará
 * el emoji como texto si no encuentra el nombre en AppIcon (fallo silencioso).
 */
const iconOptions = [
    { icon: 'zap', label: 'Energía' }, { icon: 'book-open', label: 'Estudio' },
    { icon: 'dumbbell', label: 'Ejercicio' }, { icon: 'brain', label: 'Mente' },
    { icon: 'footprints', label: 'Correr' }, { icon: 'target', label: 'Meta' },
    { icon: 'pencil', label: 'Escritura' }, { icon: 'palette', label: 'Creatividad' },
    { icon: 'leaf', label: 'Meditación' }, { icon: 'bed', label: 'Descanso' },
    { icon: 'utensils', label: 'Alimentación' }, { icon: 'music', label: 'Música' },
];

const categoryOptions = [
    { value: 'estudio', label: '📖 Estudio' },
    { value: 'sueno', label: '😴 Sueño' },
    { value: 'ejercicio', label: '🏃 Ejercicio' },
    { value: 'alimentacion', label: '🥗 Alimentación' },
    { value: 'otro', label: '✨ Otro' },
];

const categoryMap = {
    estudio: { icon: 'book-open', label: 'Estudio' },
    sueno: { icon: 'bed', label: 'Sueño' },
    ejercicio: { icon: 'footprints', label: 'Ejercicio' },
    alimentacion: { icon: 'utensils', label: 'Alimentación' },
    otro: { icon: 'sparkles', label: 'Otro' },
};

const createForm = useForm({
    title: '',
    category: 'estudio',
    frequency: { type: 'daily' },
    icon: 'zap',
});

const editForm = useForm({
    title: '',
    category: 'estudio',
    frequency: { type: 'daily' },
    icon: 'zap',
});

const freqType = ref('daily');
const freqTimesPerWeek = ref(3);
const freqDays = ref([1, 2, 3, 4, 5]);

function buildFrequency(type, timesPerWeek, days) {
    if (type === 'daily') return { type: 'daily' };
    if (type === 'weekly_times') return { type: 'weekly', times_per_week: timesPerWeek };
    return { type: 'weekly', days };
}

function freqLabel(freq) {
    if (!freq || freq.type === 'daily') return 'Diario';
    if (freq.type === 'weekly' && freq.times_per_week) return `${freq.times_per_week}x/sem`;
    if (freq.type === 'weekly' && freq.days) return freq.days.map(d => dayNames[d - 1]).join('-');
    return 'Diario';
}

function initFreqForm(freq) {
    if (!freq || freq.type === 'daily') {
        freqType.value = 'daily';
        return;
    }
    if (freq.times_per_week) {
        freqType.value = 'weekly_times';
        freqTimesPerWeek.value = freq.times_per_week;
    } else if (freq.days) {
        freqType.value = 'weekly_days';
        freqDays.value = [...freq.days];
    }
}

function syncFreqToForm(form) {
    form.frequency = buildFrequency(freqType.value, freqTimesPerWeek.value, freqDays.value);
}

function toggleDay(day) {
    const idx = freqDays.value.indexOf(day);
    if (idx >= 0) freqDays.value.splice(idx, 1);
    else freqDays.value.push(day);
    freqDays.value.sort((a, b) => a - b);
}

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const currentMonthDays = computed(() => {
    const today = new Date(props.todayDate);
    const year = today.getFullYear();
    const month = today.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days = [];
    for (let day = 1; day <= daysInMonth; day++) {
        const d = new Date(year, month, day);
        days.push({
            date: d.toISOString().slice(0, 10),
            day,
            dayOfWeek: (d.getDay() + 6) % 7,
        });
    }
    return days;
});

const currentMonthLabel = computed(() => {
    const today = new Date(props.todayDate);
    return `${monthNames[today.getMonth()]} ${today.getFullYear()}`;
});

const monthGrid = computed(() => {
    const today = new Date(props.todayDate);
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const pad = (firstDay.getDay() + 6) % 7;
    const cells = [];
    for (let i = 0; i < pad; i++) {
        cells.push(null);
    }
    for (const d of currentMonthDays.value) {
        cells.push(d);
    }
    while (cells.length % 7 !== 0) {
        cells.push(null);
    }
    const weeks = [];
    for (let i = 0; i < cells.length; i += 7) {
        weeks.push(cells.slice(i, i + 7));
    }
    return weeks;
});

function isCompleted(habit, dateStr) {
    return habit.completed_dates?.includes(dateStr);
}

function isBeforeCreation(habit, dateStr) {
    return !habit.created_at || dateStr < habit.created_at;
}

function heatmapColor(habit, dateStr) {
    if (isCompleted(habit, dateStr)) return 'bg-primary';
    if (isBeforeCreation(habit, dateStr)) return 'bg-surface opacity-30';
    const d = new Date(dateStr);
    const today = new Date(props.todayDate);
    if (d > today) return 'bg-surface';
    return 'bg-surface-sunken';
}

function completionRate(habit) {
    const today = new Date(props.todayDate);
    const existed = currentMonthDays.value.filter(
        d => new Date(d.date) <= today && !isBeforeCreation(habit, d.date)
    ).length;
    if (existed === 0) return 0;
    const done = currentMonthDays.value.filter(d => isCompleted(habit, d.date)).length;
    return Math.round((done / existed) * 100);
}

const openCreateModal = () => {
    freqType.value = 'daily';
    createForm.reset();
    showCreateModal.value = true;
    track('open_create_habit_modal', 'habits');
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    syncFreqToForm(createForm);
    createForm.post(route('habits.store'), {
        onSuccess: () => {
            track('create_habit_success', 'habits', { category: createForm.category });
            closeCreateModal();
        },
    });
};

const openEditModal = (habit) => {
    editingHabit.value = habit;
    editForm.title = habit.title;
    editForm.category = habit.category;
                editForm.icon = habit.icon || 'zap';
    initFreqForm(habit.frequency);
    showEditModal.value = true;
    track('open_edit_habit_modal', 'habits', { habit_id: habit.id });
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingHabit.value = null;
    editForm.reset();
};

const submitEdit = () => {
    syncFreqToForm(editForm);
    editForm.patch(route('habits.update', { id: editingHabit.value.id }), {
        onSuccess: () => {
            track('edit_habit_success', 'habits', { habit_id: editingHabit.value.id });
            closeEditModal();
        },
    });
};

const toggleHabit = (habit) => {
    const originalState = habit.is_completed_today;
    habit.is_completed_today = !originalState;

    router.post(
        route('habits.toggle', { id: habit.id }),
        { date: props.todayDate },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const xp = page?.props?.xp_awarded ?? (habit.is_completed_today ? 10 : 0);
                track('toggle_habit_completion', 'habits', {
                    habit_id: habit.id,
                    completed: habit.is_completed_today,
                    xp_awarded: xp,
                });
                if (habit.is_completed_today && xp > 0) {
                    triggerXpToast(`+${xp} XP ¡Excelente hábito!`);
                }
            },
            onError: () => {
                habit.is_completed_today = originalState;
            },
        },
    );
};

const deleteHabit = (habitId) => {
    if (confirm('¿Deseas eliminar permanentemente este hábito? Su historial se conservará.')) {
        track('delete_habit', 'habits', { habit_id: habitId });
        router.delete(route('habits.destroy', { id: habitId }));
    }
};

const archiveHabit = (habitId) => {
    track('archive_habit', 'habits', { habit_id: habitId });
    router.patch(route('habits.archive', { id: habitId }), {}, { preserveScroll: true });
};

const unarchiveHabit = (habitId) => {
    track('unarchive_habit', 'habits', { habit_id: habitId });
    router.patch(route('habits.unarchive', { id: habitId }), {}, { preserveScroll: true });
};

const triggerXpToast = (msg) => {
    xpNotification.value = msg;
    setTimeout(() => { xpNotification.value = null; }, 3000);
};
</script>

<template>
    <Head title="Hábitos — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <UsageTipBanner module="habits" />
            <BaseCard class="mb-8">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
v-if="avatarImage"
                            class="flex h-32 w-20 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-1">
                            <img :src="avatarImage" alt="" class="h-full w-full object-contain" />
                        </div>
                        <div>
                            <h1 class="font-display text-3xl text-content-primary">Hábitos Diarios</h1>
                            <p class="mt-1 text-sm text-content-secondary">Construye consistencia día a día en tus metas.</p>
                        </div>
                    </div>
                    <BaseButton variant="primary" @click="openCreateModal">+ Nuevo Hábito</BaseButton>
                </header>
            </BaseCard>

            <div v-if="habits.length > 0" class="mb-6 grid grid-cols-4 gap-4">
                <BaseCard class="p-4 text-center">
                    <p class="font-display text-2xl text-content-primary">{{ stats.active_habits }}</p>
                    <p class="text-xs text-content-muted">Activos</p>
                </BaseCard>
                <BaseCard class="p-4 text-center">
                    <p class="font-display text-2xl text-content-primary">{{ stats.total_weekly }}</p>
                    <p class="text-xs text-content-muted">Esta semana</p>
                </BaseCard>
                <BaseCard class="p-4 text-center">
                    <p class="font-display text-2xl text-content-primary">{{ stats.max_streak }}</p>
                    <p class="text-xs text-content-muted flex items-center gap-1">Mejor racha <AppIcon name="flame" :size="11" class="text-danger" /></p>
                </BaseCard>
                <BaseCard class="p-4 text-center">
                    <p class="font-display text-2xl text-content-primary">
                        {{ habits.length > 0 ? Math.round(habits.reduce((s, h) => s + completionRate(h), 0) / habits.length) : 0 }}%
                    </p>
                    <p class="text-xs text-content-muted">Adherencia del mes</p>
                </BaseCard>
            </div>

            <template v-if="habits.length > 0">
                <div class="space-y-4">
                    <BaseCard v-for="habit in habits" :key="habit.id" class="p-3 transition-all hover:scale-[1.01]">
                        <div class="group flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <button
type="button"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border text-lg transition-all"
                                    :class="habit.is_completed_today
                                        ? 'scale-105 border-primary bg-primary text-on-primary shadow-md'
                                        : 'border-border-interactive bg-bg text-content-muted hover:border-primary'"
                                    @click="toggleHabit(habit)">
                                    <AppIcon v-if="habit.is_completed_today" name="check" :size="18" />
                                    <AppIcon v-else :name="habit.icon || 'zap'" :size="18" />
                                </button>
                                <div class="truncate">
                                    <div class="flex items-center gap-2">
                                        <h3
class="truncate font-semibold text-content-primary"
                                            :class="{ 'line-through opacity-60': habit.is_completed_today }">
                                            {{ habit.title }}
                                        </h3>
                                        <span v-if="habit.streak > 0" class="shrink-0 text-xs text-accent inline-flex items-center gap-0.5" title="Racha"><AppIcon name="flame" :size="11" class="text-danger" />{{ habit.streak }}</span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <BaseBadge class="flex items-center gap-1">
                                            <AppIcon :name="categoryMap[habit.category]?.icon || 'sparkles'" :size="10" />
                                            {{ categoryMap[habit.category]?.label || habit.category }}
                                        </BaseBadge>
                                        <span class="text-xs text-content-muted">{{ freqLabel(habit.frequency) }}</span>
                                        <span class="text-xs text-content-muted">{{ completionRate(habit) }}%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button
type="button" class="rounded p-1.5 text-sm text-content-muted opacity-0 transition hover:text-content-primary group-hover:opacity-100 focus:opacity-100"
                                    title="Editar hábito" @click="openEditModal(habit)"><AppIcon name="pencil" :size="14" /></button>
                                <button
type="button" class="rounded p-1.5 text-sm text-content-muted opacity-0 transition hover:text-accent group-hover:opacity-100 focus:opacity-100"
                                    title="Archivar hábito" @click="archiveHabit(habit.id)"><AppIcon name="lock" :size="14" /></button>
                                <button
type="button" class="rounded p-1.5 text-sm text-content-muted opacity-0 transition hover:text-danger-text group-hover:opacity-100 focus:opacity-100"
                                    title="Eliminar hábito" @click="deleteHabit(habit.id)"><AppIcon name="trash" :size="14" /></button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <p class="mb-0.5 text-[10px] font-semibold text-content-secondary">{{ currentMonthLabel }}</p>
                            <div class="grid grid-cols-7 gap-[2px]">
                                <div
v-for="(name, idx) in dayNames" :key="'h'+idx"
                                    class="text-center text-[8px] font-semibold text-content-muted">
                                    {{ name.charAt(0) }}
                                </div>
                                <template v-for="(week, wi) in monthGrid" :key="'w'+wi">
                                    <div
v-for="(cell, ci) in week" :key="'d'+wi+'-'+ci"
                                        class="flex h-4 items-center justify-center rounded-[2px] text-[9px] font-medium leading-none transition-colors"
                                        :class="cell ? heatmapColor(habit, cell.date) : 'opacity-0'"
                                        :title="cell ? `${cell.date}: ${isCompleted(habit, cell.date) ? '✅ Completado' : isBeforeCreation(habit, cell.date) ? '— No existía' : '❌ Pendiente'}` : ''">
                                        <span v-if="cell && isCompleted(habit, cell.date)" class="text-on-primary">✓</span>
                                        <span v-else-if="cell && isBeforeCreation(habit, cell.date)" class="text-content-muted">·</span>
                                        <span v-else-if="cell" class="text-content-primary">{{ cell.day }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <div v-if="archivedHabits.length > 0" class="mt-6">
                    <button
type="button"
                        class="flex w-full items-center justify-between rounded-xl bg-surface px-4 py-2 text-sm font-semibold text-content-secondary transition hover:bg-surface-raised"
                        @click="showArchived = !showArchived">
                        <span class="flex items-center gap-1"><AppIcon name="lock" :size="13" /> Archivados ({{ archivedHabits.length }})</span>
                        <AppIcon name="chevron-down" :size="13" class="transition" :class="showArchived ? 'rotate-180' : ''" />
                    </button>
                    <div v-if="showArchived" class="mt-2 space-y-2">
                        <BaseCard v-for="habit in archivedHabits" :key="'arch-'+habit.id" class="flex items-center justify-between p-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <AppIcon :name="habit.icon || 'zap'" :size="20" class="shrink-0 text-content-secondary" />
                                <div class="truncate">
                                    <p class="truncate text-sm font-medium text-content-primary">{{ habit.title }}</p>
                                    <p class="text-xs text-content-muted">{{ categoryMap[habit.category]?.label || habit.category }} · {{ freqLabel(habit.frequency) }}</p>
                                </div>
                            </div>
                            <BaseButton variant="ghost" class="shrink-0 text-xs" @click="unarchiveHabit(habit.id)">Restaurar</BaseButton>
                        </BaseCard>
                    </div>
                </div>
            </template>

            <BaseCard v-if="habits.length === 0" class="flex flex-col items-center p-12 text-center">
                <AppIcon name="leaf" :size="48" class="mb-3 text-success" />
                <h2 class="text-lg font-semibold text-content-primary">No tienes hábitos registrados</h2>
                <p class="mt-1 max-w-sm text-sm text-content-secondary">Crea tu primer hábito diario para comenzar a acumular racha y ganar experiencia.</p>
                <BaseButton class="mt-6" variant="primary" @click="openCreateModal">Crear mi primer hábito</BaseButton>
            </BaseCard>
        </div>

        <BaseModal :show="showCreateModal" title="Nuevo Hábito" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <BaseInput
id="create-title" v-model="createForm.title" label="Título del hábito"
                    placeholder="Ej. Estudiar 45 min de Algoritmos" :error="createForm.errors.title" required />

                <BaseSelect
id="create-category" v-model="createForm.category" label="Categoría"
                    :options="categoryOptions" :error="createForm.errors.category" required />

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-content-secondary">Frecuencia</legend>
                    <BaseSelect id="create-freq-type" v-model="freqType" :options="freqTypeOptions" />

                    <div v-if="freqType === 'weekly_times'" class="mt-3">
                        <BaseInput
id="create-freq-times" v-model="freqTimesPerWeek" label="Veces por semana"
                            type="number" min="1" max="7" />
                    </div>

                    <div v-if="freqType === 'weekly_days'" class="mt-3 flex gap-2">
                        <button
v-for="(name, i) in dayNames" :key="i" type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border text-xs font-semibold transition-all"
                            :class="freqDays.includes(i + 1)
                                ? 'border-primary bg-primary text-on-primary'
                                : 'border-border-interactive text-content-muted hover:border-primary'"
                            @click="toggleDay(i + 1)">
                            {{ name }}
                        </button>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-content-secondary">Icono</legend>
                    <div class="flex flex-wrap gap-2">
                        <button
v-for="opt in iconOptions" :key="opt.icon" type="button" :title="opt.label"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border transition-all"
                            :class="createForm.icon === opt.icon
                                ? 'border-primary bg-primary text-on-primary'
                                : 'border-border-interactive text-content-muted hover:border-primary'"
                            @click="createForm.icon = opt.icon">
                            <AppIcon :name="opt.icon" :size="16" />
                        </button>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton variant="ghost" type="button" @click="closeCreateModal">Cancelar</BaseButton>
                    <BaseButton type="submit" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Guardando…' : 'Crear Hábito' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <BaseModal :show="showEditModal" :title="`Editar: ${editingHabit?.title || ''}`" @close="closeEditModal">
            <form class="space-y-4" @submit.prevent="submitEdit">
                <BaseInput
id="edit-title" v-model="editForm.title" label="Título del hábito"
                    :error="editForm.errors.title" required />

                <BaseSelect
id="edit-category" v-model="editForm.category" label="Categoría"
                    :options="categoryOptions" :error="editForm.errors.category" required />

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-content-secondary">Frecuencia</legend>
                    <BaseSelect id="edit-freq-type" v-model="freqType" :options="freqTypeOptions" />

                    <div v-if="freqType === 'weekly_times'" class="mt-3">
                        <BaseInput
id="edit-freq-times" v-model="freqTimesPerWeek" label="Veces por semana"
                            type="number" min="1" max="7" />
                    </div>

                    <div v-if="freqType === 'weekly_days'" class="mt-3 flex gap-2">
                        <button
v-for="(name, i) in dayNames" :key="i" type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border text-xs font-semibold transition-all"
                            :class="freqDays.includes(i + 1)
                                ? 'border-primary bg-primary text-on-primary'
                                : 'border-border-interactive text-content-muted hover:border-primary'"
                            @click="toggleDay(i + 1)">
                            {{ name }}
                        </button>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-content-secondary">Icono</legend>
                    <div class="flex flex-wrap gap-2">
                        <button
v-for="opt in iconOptions" :key="opt.icon" type="button" :title="opt.label"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border transition-all"
                            :class="editForm.icon === opt.icon
                                ? 'border-primary bg-primary text-on-primary'
                                : 'border-border-interactive text-content-muted hover:border-primary'"
                            @click="editForm.icon = opt.icon">
                            <AppIcon :name="opt.icon" :size="16" />
                        </button>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton variant="ghost" type="button" @click="closeEditModal">Cancelar</BaseButton>
                    <BaseButton type="submit" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Guardando…' : 'Guardar cambios' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <Transition
enter-active-class="transition duration-300 ease-out" enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
            <div
v-if="xpNotification"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-on-primary shadow-lg">
                <AppIcon name="star" :size="20" /><span>{{ xpNotification }}</span>
            </div>
        </Transition>
    </AppLayout>
</template>
