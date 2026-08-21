<script setup>
import { computed, ref, onMounted } from 'vue';
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
import { triggerConfetti, playSuccessChime, triggerHapticVibration } from '@/utils/celebration';

const props = defineProps({
    habits: { type: Array, default: () => [] },
    archivedHabits: { type: Array, default: () => [] },
    todayDate: { type: String, required: true },
    avatarStyle: { type: String, default: 'base' },
    avatarGender: { type: String, default: 'm' },
    progress: { type: Object, default: () => ({ phase: 1 }) },
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

const viewMode = ref('weekly'); // 'weekly' | 'monthly'
const activeTimeOfDay = ref('all'); // 'all' | 'morning' | 'afternoon' | 'night'

onMounted(() => {
    const savedView = localStorage.getItem('epycus_habits_view');
    if (savedView === 'weekly' || savedView === 'monthly') {
        viewMode.value = savedView;
    }
});

function setViewMode(mode) {
    const oldMode = viewMode.value;
    viewMode.value = mode;
    localStorage.setItem('epycus_habits_view', mode);
    track('habits.view_switched', 'habits', { from_view: oldMode, to_view: mode });
}

function setTimeOfDayFilter(time) {
    activeTimeOfDay.value = time;
    track('habits.time_filtered', 'habits', { time_of_day: time });
}

const filteredHabits = computed(() => {
    if (activeTimeOfDay.value === 'all') return props.habits;
    return props.habits.filter(
        (h) => (h.time_of_day || 'anytime') === activeTimeOfDay.value || (h.time_of_day || 'anytime') === 'anytime',
    );
});

const timeOfDayOptions = [
    { value: 'anytime', label: '⚡ En cualquier momento' },
    { value: 'morning', label: '🌅 Por la mañana' },
    { value: 'afternoon', label: '☀️ Por la tarde' },
    { value: 'night', label: '🌙 Por la noche' },
];

const timeOfDayBadges = {
    morning: { label: 'Mañana', icon: 'sun', class: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30' },
    afternoon: { label: 'Tarde', icon: 'sun-medium', class: 'bg-orange-500/15 text-orange-600 dark:text-orange-400 border border-orange-500/30' },
    night: { label: 'Noche', icon: 'moon', class: 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/30' },
    anytime: { label: 'Flexible', icon: 'zap', class: 'bg-surface-raised text-content-muted border border-border-interactive' },
};

const atomicTemplates = [
    {
        title: 'Repaso activo de 20 min',
        category: 'estudio',
        icon: 'book-open',
        time_of_day: 'afternoon',
        cue_trigger: 'Al sentarme en mi escritorio después de almorzar',
        description: 'Refuerza lo aprendido antes de olvidar el 80% de la clase.',
    },
    {
        title: 'Revisar notas del día',
        category: 'estudio',
        icon: 'pencil',
        time_of_day: 'night',
        cue_trigger: 'Antes de cenar o cerrar la laptop',
        description: 'Lectura de 5 min para consolidar la memoria a largo plazo.',
    },
    {
        title: 'Dejar pantallas 30 min antes de dormir',
        category: 'sueno',
        icon: 'bed',
        time_of_day: 'night',
        cue_trigger: 'Al ponerme el pijama',
        description: 'Protege tu ritmo circadiano y mejora la calidad del sueño profundo.',
    },
    {
        title: 'Beber 1 vaso de agua al despertar',
        category: 'alimentacion',
        icon: 'utensils',
        time_of_day: 'morning',
        cue_trigger: 'Inmediatamente al salir de la cama',
        description: 'Rehidrata el cerebro tras 7-8 horas de ayuno nocturno.',
    },
    {
        title: 'Pausa activa / Caminata de 15 min',
        category: 'ejercicio',
        icon: 'footprints',
        time_of_day: 'afternoon',
        cue_trigger: 'Al terminar mi bloque principal de estudio',
        description: 'Oxigena la mente y reduce la fatiga mental.',
    },
    {
        title: 'Escribir en el Diario de Bienestar',
        category: 'otro',
        icon: 'brain',
        time_of_day: 'night',
        cue_trigger: 'Justo antes de acostarme',
        description: 'Descarga mental y regulación emocional diaria.',
    },
];

const freqTypeOptions = [
    { value: 'daily', label: 'Diario' },
    { value: 'weekly_times', label: 'X veces por semana' },
    { value: 'weekly_days', label: 'Días específicos' },
];

const dayNames = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];

const iconOptions = [
    { icon: 'zap', label: 'Energía' },
    { icon: 'book-open', label: 'Estudio' },
    { icon: 'dumbbell', label: 'Ejercicio' },
    { icon: 'brain', label: 'Mente' },
    { icon: 'footprints', label: 'Correr' },
    { icon: 'target', label: 'Meta' },
    { icon: 'pencil', label: 'Escritura' },
    { icon: 'palette', label: 'Creatividad' },
    { icon: 'leaf', label: 'Meditación' },
    { icon: 'bed', label: 'Descanso' },
    { icon: 'utensils', label: 'Alimentación' },
    { icon: 'music', label: 'Música' },
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
    time_of_day: 'anytime',
    cue_trigger: '',
});

const editForm = useForm({
    title: '',
    category: 'estudio',
    frequency: { type: 'daily' },
    icon: 'zap',
    time_of_day: 'anytime',
    cue_trigger: '',
});

const freqType = ref('daily');
const freqTimesPerWeek = ref(3);
const freqDays = ref([1, 2, 3, 4, 5]);

function applyTemplate(tpl) {
    createForm.title = tpl.title;
    createForm.category = tpl.category;
    createForm.icon = tpl.icon;
    createForm.time_of_day = tpl.time_of_day;
    createForm.cue_trigger = tpl.cue_trigger;
    freqType.value = 'daily';
    track('habits.template_selected', 'habits', { title: tpl.title, category: tpl.category });
}

function buildFrequency(type, timesPerWeek, days) {
    if (type === 'daily') return { type: 'daily' };
    if (type === 'weekly_times') return { type: 'weekly', times_per_week: timesPerWeek };
    return { type: 'weekly', days };
}

function freqLabel(freq) {
    if (!freq || freq.type === 'daily') return 'Diario';
    if (freq.type === 'weekly' && freq.times_per_week) return `${freq.times_per_week}x/sem`;
    if (freq.type === 'weekly' && freq.days) return freq.days.map((d) => dayNames[d - 1]).join('-');
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

// 7 días de la semana actual
const currentWeekDays = computed(() => {
    const today = new Date(props.todayDate);
    const dayOfWeek = (today.getDay() + 6) % 7; // 0 = Lunes, 6 = Domingo
    const monday = new Date(today);
    monday.setDate(today.getDate() - dayOfWeek);

    const days = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        const dateStr = d.toISOString().slice(0, 10);
        days.push({
            date: dateStr,
            day: d.getDate(),
            dayName: dayNames[i],
            isToday: dateStr === props.todayDate,
            isFuture: dateStr > props.todayDate,
        });
    }
    return days;
});

const monthNames = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

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

function isFutureDate(dateStr) {
    return dateStr > props.todayDate;
}

function heatmapColor(habit, dateStr) {
    const isTargetToday = dateStr === props.todayDate;
    if (isCompleted(habit, dateStr)) {
        return 'bg-emerald-500 text-white font-bold shadow-xs ring-1 ring-emerald-400/50 hover:brightness-110';
    }
    if (isBeforeCreation(habit, dateStr)) {
        return 'bg-surface-sunken/40 text-content-muted/40 border border-border-interactive/20 cursor-default';
    }
    const d = new Date(dateStr);
    const today = new Date(props.todayDate);
    if (d > today) {
        return 'bg-surface-raised/40 text-content-muted/60 border border-dashed border-border-interactive/30 cursor-default';
    }
    if (isTargetToday) {
        return 'bg-primary-strong/20 text-primary-strong border-2 border-primary-strong font-bold hover:bg-primary-strong/30';
    }
    return 'bg-surface-raised/80 text-content-secondary border border-border-interactive/50 hover:border-primary-strong hover:bg-surface-raised';
}

function completionRate(habit) {
    const today = new Date(props.todayDate);
    const existed = currentMonthDays.value.filter(
        (d) => new Date(d.date) <= today && !isBeforeCreation(habit, d.date),
    ).length;
    if (existed === 0) return 0;
    const done = currentMonthDays.value.filter((d) => isCompleted(habit, d.date)).length;
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
            track('create_habit_success', 'habits', {
                category: createForm.category,
                time_of_day: createForm.time_of_day,
                has_cue: Boolean(createForm.cue_trigger),
            });
            closeCreateModal();
        },
    });
};

const openEditModal = (habit) => {
    editingHabit.value = habit;
    editForm.title = habit.title;
    editForm.category = habit.category;
    editForm.icon = habit.icon || 'zap';
    editForm.time_of_day = habit.time_of_day || 'anytime';
    editForm.cue_trigger = habit.cue_trigger || '';
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

const toggleHabitForDate = (habit, targetDate) => {
    const isTargetToday = targetDate === props.todayDate;
    const wasCompleted = isCompleted(habit, targetDate);

    // Optimistic UI update
    if (wasCompleted) {
        habit.completed_dates = habit.completed_dates.filter((d) => d !== targetDate);
        if (isTargetToday) habit.is_completed_today = false;
    } else {
        if (!habit.completed_dates) habit.completed_dates = [];
        habit.completed_dates.push(targetDate);
        if (isTargetToday) habit.is_completed_today = true;
    }

    router.post(
        route('habits.toggle', { id: habit.id }),
        { date: targetDate },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const xp = page?.props?.xp_awarded ?? (!wasCompleted ? 10 : 0);
                track('toggle_habit_completion', 'habits', {
                    habit_id: habit.id,
                    date: targetDate,
                    completed: !wasCompleted,
                    xp_awarded: xp,
                });
                if (!wasCompleted) {
                    triggerConfetti();
                    playSuccessChime();
                    if (xp > 0) {
                        triggerXpToast(`+${xp} XP ¡Hábito cumplido! 🎉`);
                    } else {
                        triggerXpToast('¡Hábito registrado!');
                    }
                }
            },
        },
    );
};

const toggleHabit = (habit) => {
    toggleHabitForDate(habit, props.todayDate);
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

const startPomodoroFromHabit = (habit) => {
    track('habits.pomodoro_started', 'habits', { habit_id: habit.id, title: habit.title });
    router.visit(route('pomodoro.index'));
};

const triggerXpToast = (msg) => {
    triggerHapticVibration([50, 40, 60]);
    xpNotification.value = msg;
    setTimeout(() => {
        xpNotification.value = null;
    }, 3000);
};
</script>

<template>
    <Head title="Hábitos — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Tip dinámico del módulo -->
            <UsageTipBanner module="habits" />

            <!-- Header Card Principal -->
            <BaseCard class="p-6 border-border-interactive">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border-interactive bg-surface-raised/40 p-2 shadow-sm"
                        >
                            <img
                                src="/assets/gifs/habits.gif"
                                alt="Hábitos Diarios"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">
                                Hábitos Diarios
                            </h1>
                            <p class="mt-1 text-sm text-content-secondary">
                                Construye consistencia con micro-hábitos y rutinas científicas.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <BaseButton variant="primary" @click="openCreateModal">
                            + Nuevo Hábito
                        </BaseButton>
                    </div>
                </header>
            </BaseCard>

            <!-- Banner Científico Anti-Frustración: Regla de "Nunca fallar 2 veces" -->
            <BaseCard class="p-4 border-l-4 border-l-success bg-success/5 text-xs text-content-secondary flex items-start gap-3">
                <span class="text-xl shrink-0">🌱</span>
                <div>
                    <p class="font-bold text-content-primary">
                        Psicología del Hábito: La Regla de "Nunca fallar dos veces"
                    </p>
                    <p class="mt-0.5 leading-relaxed">
                        Fallar un día por imprevistos es completamente normal. Lo que consolida tu identidad académica es <strong class="text-success">no fallar dos días consecutivos</strong>. ¡Un pequeño paso hoy mantiene tu racha viva!
                    </p>
                </div>
            </BaseCard>

            <!-- Métricas Resumen -->
            <div v-if="habits.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                <BaseCard class="p-3.5 sm:p-4 text-center">
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ stats.active_habits }}
                    </p>
                    <p class="text-xs text-content-muted leading-tight font-medium">Activos</p>
                </BaseCard>
                <BaseCard class="p-3.5 sm:p-4 text-center">
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ stats.total_weekly }}
                    </p>
                    <p class="text-xs text-content-muted leading-tight font-medium">Esta semana</p>
                </BaseCard>
                <BaseCard class="p-3.5 sm:p-4 text-center">
                    <p class="font-display text-2xl font-bold text-danger flex items-center justify-center gap-1">
                        {{ stats.max_streak }} <AppIcon name="flame" :size="16" />
                    </p>
                    <p class="text-xs text-content-muted leading-tight font-medium">Mejor racha</p>
                </BaseCard>
                <BaseCard class="p-3.5 sm:p-4 text-center">
                    <p class="font-display text-2xl font-bold text-primary-strong">
                        {{
                            habits.length > 0
                                ? Math.round(
                                      habits.reduce((s, h) => s + completionRate(h), 0) /
                                          habits.length,
                                  )
                                : 0
                        }}%
                    </p>
                    <p class="text-xs text-content-muted leading-tight font-medium">Adherencia del mes</p>
                </BaseCard>
            </div>

            <!-- Controles: Filtro por Momento del Día + Selector de Vista (Semanal / Heatmap) -->
            <div v-if="habits.length > 0" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                <!-- Filtros por Momento del Día -->
                <div class="flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl transition cursor-pointer border shrink-0"
                        :class="
                            activeTimeOfDay === 'all'
                                ? 'bg-primary-strong text-white border-primary-strong shadow-xs'
                                : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'
                        "
                        @click="setTimeOfDayFilter('all')"
                    >
                        ⚡ Todos ({{ props.habits.length }})
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl transition cursor-pointer border shrink-0 flex items-center gap-1"
                        :class="
                            activeTimeOfDay === 'morning'
                                ? 'bg-amber-500 text-white border-amber-500 shadow-xs'
                                : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'
                        "
                        @click="setTimeOfDayFilter('morning')"
                    >
                        <span>🌅 Mañana</span>
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl transition cursor-pointer border shrink-0 flex items-center gap-1"
                        :class="
                            activeTimeOfDay === 'afternoon'
                                ? 'bg-orange-500 text-white border-orange-500 shadow-xs'
                                : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'
                        "
                        @click="setTimeOfDayFilter('afternoon')"
                    >
                        <span>☀️ Tarde</span>
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl transition cursor-pointer border shrink-0 flex items-center gap-1"
                        :class="
                            activeTimeOfDay === 'night'
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                                : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'
                        "
                        @click="setTimeOfDayFilter('night')"
                    >
                        <span>🌙 Noche</span>
                    </button>
                </div>

                <!-- Selector de Vista (Semanal vs Heatmap Mensual) -->
                <div class="inline-flex rounded-xl bg-surface-raised p-1 border border-border-interactive shadow-xs shrink-0 self-start sm:self-auto">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-lg transition cursor-pointer"
                        :class="viewMode === 'weekly' ? 'bg-primary-strong text-white shadow-xs' : 'text-content-secondary hover:text-content-primary'"
                        @click="setViewMode('weekly')"
                    >
                        <AppIcon name="calendar-days" :size="13" /> Vista Semanal
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-lg transition cursor-pointer"
                        :class="viewMode === 'monthly' ? 'bg-primary-strong text-white shadow-xs' : 'text-content-secondary hover:text-content-primary'"
                        @click="setViewMode('monthly')"
                    >
                        <AppIcon name="layout-grid" :size="13" /> Heatmap Mensual
                    </button>
                </div>
            </div>

            <!-- Lista de Hábitos -->
            <template v-if="filteredHabits.length > 0">
                <div class="space-y-4">
                    <BaseCard
                        v-for="habit in filteredHabits"
                        :key="habit.id"
                        class="p-4 transition-all duration-200 hover:border-primary-strong/30"
                        :class="habit.is_completed_today ? 'bg-surface-raised/70' : 'bg-surface'"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3.5 min-w-0 flex-1">
                                <!-- Botón de Check rápido hoy -->
                                <button
                                    type="button"
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border text-xl transition-all cursor-pointer shadow-xs"
                                    :class="
                                        habit.is_completed_today
                                            ? 'scale-105 border-success bg-success text-on-accent shadow-sm'
                                            : 'border-border-interactive bg-surface-raised text-content-muted hover:border-primary hover:text-primary-strong'
                                    "
                                    title="Marcar completado hoy"
                                    @click="toggleHabit(habit)"
                                >
                                    <AppIcon
                                        v-if="habit.is_completed_today"
                                        name="check"
                                        :size="20"
                                    />
                                    <AppIcon v-else :name="habit.icon || 'zap'" :size="20" />
                                </button>

                                <div class="truncate flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3
                                            class="font-bold text-sm text-content-primary truncate"
                                            :class="{ 'line-through opacity-70': habit.is_completed_today }"
                                        >
                                            {{ habit.title }}
                                        </h3>
                                        <span
                                            v-if="habit.streak > 0"
                                            class="shrink-0 text-xs text-danger font-bold inline-flex items-center gap-0.5 bg-danger/10 px-2 py-0.5 rounded-full border border-danger/20"
                                            title="Racha activa"
                                        >
                                            <AppIcon name="flame" :size="12" /> {{ habit.streak }} días
                                        </span>
                                    </div>

                                    <!-- Disparador de Anclaje (Habit Stacking) -->
                                    <p
                                        v-if="habit.cue_trigger"
                                        class="text-xs text-primary-strong font-medium mt-0.5 flex items-center gap-1"
                                    >
                                        <span>🔗</span>
                                        <span>Después de: <em>{{ habit.cue_trigger }}</em></span>
                                    </p>

                                    <!-- Badges & Metadatos -->
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                        <BaseBadge class="flex items-center gap-1">
                                            <AppIcon
                                                :name="categoryMap[habit.category]?.icon || 'sparkles'"
                                                :size="11"
                                            />
                                            {{ categoryMap[habit.category]?.label || habit.category }}
                                        </BaseBadge>

                                        <span
                                            v-if="habit.time_of_day && habit.time_of_day !== 'anytime'"
                                            class="rounded px-1.5 py-0.2 text-[10px] font-bold"
                                            :class="timeOfDayBadges[habit.time_of_day]?.class"
                                        >
                                            {{ timeOfDayBadges[habit.time_of_day]?.label }}
                                        </span>

                                        <span class="text-xs text-content-muted font-medium">
                                            {{ freqLabel(habit.frequency) }}
                                        </span>

                                        <span class="text-xs text-success font-semibold">
                                            {{ completionRate(habit) }}% adherencia
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Acciones rápidas -->
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    v-if="habit.category === 'estudio'"
                                    type="button"
                                    class="rounded-lg p-1.5 text-xs text-primary-strong hover:bg-primary-strong/10 transition cursor-pointer flex items-center gap-1 font-semibold"
                                    title="Iniciar Pomodoro para este hábito"
                                    @click="startPomodoroFromHabit(habit)"
                                >
                                    <AppIcon name="timer" :size="14" />
                                    <span class="hidden sm:inline">Pomodoro</span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 text-content-muted hover:text-content-primary hover:bg-surface-raised transition cursor-pointer"
                                    title="Editar hábito"
                                    @click="openEditModal(habit)"
                                >
                                    <AppIcon name="pencil" :size="14" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 text-content-muted hover:text-accent hover:bg-surface-raised transition cursor-pointer"
                                    title="Archivar hábito"
                                    @click="archiveHabit(habit.id)"
                                >
                                    <AppIcon name="archive" :size="14" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg p-1.5 text-content-muted hover:text-danger-text hover:bg-surface-raised transition cursor-pointer"
                                    title="Eliminar hábito"
                                    @click="deleteHabit(habit.id)"
                                >
                                    <AppIcon name="trash" :size="14" />
                                </button>
                            </div>
                        </div>

                        <!-- VISTA 1: Tira Semanal Compacta (7 Días) -->
                        <div v-if="viewMode === 'weekly'" class="mt-3.5 pt-3 border-t border-border-interactive/40">
                            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                                <div
                                    v-for="d in currentWeekDays"
                                    :key="'week-' + habit.id + '-' + d.date"
                                    class="flex flex-col items-center justify-center p-1.5 rounded-xl border transition-all cursor-pointer select-none"
                                    :class="[
                                        isCompleted(habit, d.date)
                                            ? 'bg-success/15 border-success text-success font-bold'
                                            : d.isToday
                                              ? 'bg-primary-strong/10 border-primary-strong text-primary-strong'
                                              : d.isFuture
                                                ? 'bg-surface-raised/40 border-border-interactive text-content-muted opacity-60 pointer-events-none'
                                                : isBeforeCreation(habit, d.date)
                                                  ? 'bg-surface-raised/20 border-border-interactive/40 text-content-muted opacity-40'
                                                  : 'bg-surface-raised/60 border-border-interactive text-content-secondary hover:border-primary-strong',
                                    ]"
                                    :title="`${d.date}: ${isCompleted(habit, d.date) ? 'Completado' : 'Pendiente'}`"
                                    @click="!d.isFuture && toggleHabitForDate(habit, d.date)"
                                >
                                    <span class="text-[10px] font-semibold uppercase">{{ d.dayName }}</span>
                                    <span class="text-xs font-bold mt-0.5">{{ d.day }}</span>
                                    <span class="text-xs mt-0.5">
                                        <template v-if="isCompleted(habit, d.date)">✓</template>
                                        <template v-else-if="d.isToday">⚡</template>
                                        <template v-else-if="d.isFuture">·</template>
                                        <template v-else>✕</template>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- VISTA 2: Heatmap Mensual Completo -->
                        <div v-else class="mt-3.5 pt-3 border-t border-border-interactive/40">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-bold text-content-primary flex items-center gap-1.5">
                                    <AppIcon name="calendar-days" :size="13" class="text-primary-strong" />
                                    <span>{{ currentMonthLabel }}</span>
                                </p>
                                <div class="flex items-center gap-3 text-[11px] text-content-secondary font-medium">
                                    <span class="flex items-center gap-1">
                                        <span class="inline-block w-2.5 h-2.5 rounded-xs bg-emerald-500 shadow-2xs"></span>
                                        Cumplido
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="inline-block w-2.5 h-2.5 rounded-xs bg-surface-raised border border-border-interactive/60"></span>
                                        Pendiente
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-surface-sunken/80 border border-border-interactive/60 p-3 sm:p-4 backdrop-blur-md">
                                <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
                                    <div
                                        v-for="(name, idx) in dayNames"
                                        :key="'h' + idx"
                                        class="text-center text-[10px] font-bold text-content-primary py-1 uppercase tracking-wider"
                                    >
                                        {{ name }}
                                    </div>
                                    <template v-for="(week, wi) in monthGrid" :key="'w' + wi">
                                        <div
                                            v-for="(cell, ci) in week"
                                            :key="'d' + wi + '-' + ci"
                                            class="flex h-7 sm:h-8 items-center justify-center rounded-lg text-xs font-semibold leading-none transition-all select-none cursor-pointer shadow-2xs"
                                            :class="cell ? heatmapColor(habit, cell.date) : 'opacity-0 pointer-events-none'"
                                            :title="
                                                cell
                                                    ? `${cell.date}: ${isCompleted(habit, cell.date) ? '✅ Completado' : isBeforeCreation(habit, cell.date) ? '— No existía' : '❌ Pendiente'}`
                                                    : ''
                                            "
                                            @click="cell && !isFutureDate(cell.date) && toggleHabitForDate(habit, cell.date)"
                                        >
                                            <span v-if="cell && isCompleted(habit, cell.date)" class="text-sm font-bold">✓</span>
                                            <span v-else-if="cell && isBeforeCreation(habit, cell.date)" class="opacity-40">·</span>
                                            <span v-else-if="cell">{{ cell.day }}</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Hábitos Archivados -->
                <div v-if="archivedHabits.length > 0" class="mt-6">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-xl bg-surface px-4 py-2.5 text-sm font-semibold text-content-secondary transition hover:bg-surface-raised border border-border-interactive cursor-pointer"
                        @click="showArchived = !showArchived"
                    >
                        <span class="flex items-center gap-1.5">
                            <AppIcon name="archive" :size="14" /> Hábitos Archivados ({{ archivedHabits.length }})
                        </span>
                        <AppIcon
                            name="chevron-down"
                            :size="14"
                            class="transition"
                            :class="showArchived ? 'rotate-180' : ''"
                        />
                    </button>
                    <div v-if="showArchived" class="mt-2 space-y-2">
                        <BaseCard
                            v-for="habit in archivedHabits"
                            :key="'arch-' + habit.id"
                            class="flex items-center justify-between p-3.5 opacity-80"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <AppIcon
                                    :name="habit.icon || 'zap'"
                                    :size="20"
                                    class="shrink-0 text-content-secondary"
                                />
                                <div class="truncate">
                                    <p class="truncate text-sm font-medium text-content-primary">
                                        {{ habit.title }}
                                    </p>
                                    <p class="text-xs text-content-muted">
                                        {{ categoryMap[habit.category]?.label || habit.category }} ·
                                        {{ freqLabel(habit.frequency) }}
                                    </p>
                                </div>
                            </div>
                            <BaseButton
                                variant="ghost"
                                class="shrink-0 text-xs"
                                @click="unarchiveHabit(habit.id)"
                            >
                                Restaurar
                            </BaseButton>
                        </BaseCard>
                    </div>
                </div>
            </template>

            <!-- Estado Vacío -->
            <BaseCard
                v-if="habits.length === 0"
                class="flex flex-col items-center p-12 text-center"
            >
                <AppIcon name="leaf" :size="48" class="mb-3 text-success" />
                <h2 class="text-lg font-semibold text-content-primary">
                    No tienes hábitos registrados
                </h2>
                <p class="mt-1 max-w-sm text-sm text-content-secondary">
                    Crea tu primer hábito diario o elige una de nuestras plantillas atómicas para acumular racha y experiencia.
                </p>
                <BaseButton class="mt-6" variant="primary" @click="openCreateModal">
                    Crear mi primer hábito
                </BaseButton>
            </BaseCard>
        </div>

        <!-- MODAL: CREAR HÁBITO CON PLANTILLAS ATÓMICAS -->
        <BaseModal :show="showCreateModal" title="Nuevo Hábito" @close="closeCreateModal">
            <div class="space-y-4">
                <!-- Selector Rápido de Plantillas Atómicas -->
                <div class="rounded-xl bg-surface-raised p-3 border border-border-interactive">
                    <p class="text-xs font-bold text-content-primary mb-2 flex items-center gap-1.5">
                        <span>💡</span> Plantillas Atómicas Recomendadas (1-Clic)
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button
                            v-for="(tpl, idx) in atomicTemplates"
                            :key="'tpl-' + idx"
                            type="button"
                            class="text-left p-2 rounded-lg bg-surface border border-border-interactive hover:border-primary transition cursor-pointer text-xs group"
                            @click="applyTemplate(tpl)"
                        >
                            <p class="font-semibold text-content-primary group-hover:text-primary-strong truncate">
                                {{ tpl.title }}
                            </p>
                            <p class="text-[10px] text-content-muted truncate mt-0.5">
                                {{ tpl.cue_trigger }}
                            </p>
                        </button>
                    </div>
                </div>

                <form class="space-y-4" @submit.prevent="submitCreate">
                    <BaseInput
                        id="create-title"
                        v-model="createForm.title"
                        label="Título del hábito"
                        placeholder="Ej. Repaso activo de 20 min"
                        :error="createForm.errors.title"
                        required
                    />

                    <!-- Disparador de Anclaje (Habit Stacking) -->
                    <BaseInput
                        id="create-cue"
                        v-model="createForm.cue_trigger"
                        label="Disparador o Rutina Ancla (Opcional - Habit Stacking)"
                        placeholder="Ej. Al sentarme en mi escritorio después de almorzar..."
                        :error="createForm.errors.cue_trigger"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <BaseSelect
                            id="create-category"
                            v-model="createForm.category"
                            label="Categoría"
                            :options="categoryOptions"
                            :error="createForm.errors.category"
                            required
                        />

                        <BaseSelect
                            id="create-time-of-day"
                            v-model="createForm.time_of_day"
                            label="Momento del día"
                            :options="timeOfDayOptions"
                            :error="createForm.errors.time_of_day"
                        />
                    </div>

                    <fieldset>
                        <legend class="mb-2 text-xs font-semibold text-content-secondary">
                            Frecuencia
                        </legend>
                        <BaseSelect
                            id="create-freq-type"
                            v-model="freqType"
                            :options="freqTypeOptions"
                        />

                        <div v-if="freqType === 'weekly_times'" class="mt-3">
                            <BaseInput
                                id="create-freq-times"
                                v-model="freqTimesPerWeek"
                                label="Veces por semana"
                                type="number"
                                min="1"
                                max="7"
                            />
                        </div>

                        <div v-if="freqType === 'weekly_days'" class="mt-3 flex gap-2">
                            <button
                                v-for="(name, i) in dayNames"
                                :key="i"
                                type="button"
                                class="h-8 w-8 rounded-lg text-xs font-bold transition cursor-pointer border"
                                :class="
                                    freqDays.includes(i + 1)
                                        ? 'bg-primary-strong text-white border-primary-strong'
                                        : 'bg-surface text-content-secondary border-border-interactive'
                                "
                                @click="toggleDay(i + 1)"
                            >
                                {{ name }}
                            </button>
                        </div>
                    </fieldset>

                    <!-- Selector de Iconos -->
                    <div>
                        <label class="block text-xs font-semibold text-content-secondary mb-2">
                            Icono del Hábito
                        </label>
                        <div class="grid grid-cols-6 gap-2">
                            <button
                                v-for="opt in iconOptions"
                                :key="opt.icon"
                                type="button"
                                class="flex h-10 items-center justify-center rounded-xl border transition cursor-pointer"
                                :class="
                                    createForm.icon === opt.icon
                                        ? 'bg-primary-strong/15 border-primary-strong text-primary-strong ring-1 ring-primary-strong'
                                        : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'
                                "
                                :title="opt.label"
                                @click="createForm.icon = opt.icon"
                            >
                                <AppIcon :name="opt.icon" :size="18" />
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <BaseButton variant="ghost" type="button" @click="closeCreateModal">Cancelar</BaseButton>
                        <BaseButton type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Guardando…' : 'Crear Hábito' }}
                        </BaseButton>
                    </div>
                </form>
            </div>
        </BaseModal>

        <!-- MODAL: EDITAR HÁBITO -->
        <BaseModal
            :show="showEditModal"
            :title="`Editar Hábito: ${editingHabit?.title || ''}`"
            @close="closeEditModal"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <BaseInput
                    id="edit-title"
                    v-model="editForm.title"
                    label="Título del hábito"
                    :error="editForm.errors.title"
                    required
                />

                <BaseInput
                    id="edit-cue"
                    v-model="editForm.cue_trigger"
                    label="Disparador o Rutina Ancla (Habit Stacking)"
                    placeholder="Ej. Al sentarme en mi escritorio después de almorzar..."
                />

                <div class="grid grid-cols-2 gap-4">
                    <BaseSelect
                        id="edit-category"
                        v-model="editForm.category"
                        label="Categoría"
                        :options="categoryOptions"
                        :error="editForm.errors.category"
                        required
                    />

                    <BaseSelect
                        id="edit-time-of-day"
                        v-model="editForm.time_of_day"
                        label="Momento del día"
                        :options="timeOfDayOptions"
                    />
                </div>

                <fieldset>
                    <legend class="mb-2 text-xs font-semibold text-content-secondary">
                        Frecuencia
                    </legend>
                    <BaseSelect
                        id="edit-freq-type"
                        v-model="freqType"
                        :options="freqTypeOptions"
                    />

                    <div v-if="freqType === 'weekly_times'" class="mt-3">
                        <BaseInput
                            id="edit-freq-times"
                            v-model="freqTimesPerWeek"
                            label="Veces por semana"
                            type="number"
                            min="1"
                            max="7"
                        />
                    </div>

                    <div v-if="freqType === 'weekly_days'" class="mt-3 flex gap-2">
                        <button
                            v-for="(name, i) in dayNames"
                            :key="i"
                            type="button"
                            class="h-8 w-8 rounded-lg text-xs font-bold transition cursor-pointer border"
                            :class="
                                freqDays.includes(i + 1)
                                    ? 'bg-primary-strong text-white border-primary-strong'
                                    : 'bg-surface text-content-secondary border-border-interactive'
                            "
                            @click="toggleDay(i + 1)"
                        >
                            {{ name }}
                        </button>
                    </div>
                </fieldset>

                <div>
                    <label class="block text-xs font-semibold text-content-secondary mb-2">
                        Icono
                    </label>
                    <div class="grid grid-cols-6 gap-2">
                        <button
                            v-for="opt in iconOptions"
                            :key="opt.icon"
                            type="button"
                            class="flex h-10 items-center justify-center rounded-xl border transition cursor-pointer"
                            :class="
                                editForm.icon === opt.icon
                                    ? 'bg-primary-strong/15 border-primary-strong text-primary-strong ring-1 ring-primary-strong'
                                    : 'bg-surface border-border-interactive text-content-secondary hover:bg-surface-raised'
                            "
                            @click="editForm.icon = opt.icon"
                        >
                            <AppIcon :name="opt.icon" :size="18" />
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton variant="ghost" type="button" @click="closeEditModal">Cancelar</BaseButton>
                    <BaseButton type="submit" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Guardando…' : 'Guardar Cambios' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
