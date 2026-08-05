<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';

const props = defineProps({
    missionsByDate: { type: Object, default: () => ({}) },
    holidays: { type: Object, default: () => ({}) },
    examDates: { type: Object, default: () => ({}) },
    schedules: { type: Array, default: () => [] },
    month: { type: Number, required: true },
    year: { type: Number, required: true },
    todayDate: { type: String, required: true },
    academicCycle: { type: String, default: '2026-2' },
});

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

const today = new Date(props.todayDate);
const currentMonth = today.getMonth() + 1;
const currentYear = today.getFullYear();

const daysInMonth = computed(() => new Date(props.year, props.month, 0).getDate());
const firstDayOfWeek = computed(() => {
    const d = new Date(props.year, props.month - 1, 1).getDay();
    return d === 0 ? 6 : d - 1;
});

const hasEvents = computed(() => {
    return Object.keys(props.missionsByDate).length > 0
        || Object.keys(props.holidays).length > 0
        || Object.keys(props.examDates).length > 0
        || props.schedules.length > 0;
});

const calendarDays = computed(() => {
    const days = [];
    const totalCells = Math.ceil((firstDayOfWeek.value + daysInMonth.value) / 7) * 7;
    for (let i = 0; i < totalCells; i++) {
        const dayNum = i - firstDayOfWeek.value + 1;
        const dateStr = dayNum >= 1 && dayNum <= daysInMonth.value
            ? `${props.year}-${String(props.month).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`
            : null;
        const holiday = dateStr ? props.holidays[dateStr] : null;

        // Day of week: 1=Lunes, ..., 7=Domingo
        const dayOfWeekIndex = (i % 7) + 1;
        const daySchedules = dateStr ? props.schedules.filter(s => Number(s.day_of_week) === dayOfWeekIndex) : [];

        days.push({
            day: dayNum >= 1 && dayNum <= daysInMonth.value ? dayNum : null,
            date: dateStr,
            holiday,
            isExam: dateStr ? !!props.examDates[dateStr] : false,
            missions: dateStr && props.missionsByDate[dateStr] ? props.missionsByDate[dateStr] : [],
            schedules: daySchedules,
            isToday: dateStr === props.todayDate,
            isPast: dateStr !== null && dateStr < props.todayDate,
            hasActivity: dateStr && ((props.missionsByDate[dateStr]?.length ?? 0) > 0 || holiday || props.examDates[dateStr] || daySchedules.length > 0),
        });
    }
    return days;
});

function goToMonth(m, y) {
    router.get(route('calendar.index', { month: m, year: y }), { preserveScroll: true });
}

function prevMonth() {
    const m = props.month === 1 ? 12 : props.month - 1;
    const y = props.month === 1 ? props.year - 1 : props.year;
    goToMonth(m, y);
}

function nextMonth() {
    const m = props.month === 12 ? 1 : props.month + 1;
    const y = props.month === 12 ? props.year + 1 : props.year;
    goToMonth(m, y);
}

function goToToday() {
    goToMonth(currentMonth, currentYear);
}

const showMonthPicker = ref(false);
const pickerMonth = ref(props.month);
const pickerYear = ref(props.year);

function openMonthPicker() {
    pickerMonth.value = props.month;
    pickerYear.value = props.year;
    showMonthPicker.value = true;
}

function applyMonthPicker() {
    goToMonth(pickerMonth.value, pickerYear.value);
    showMonthPicker.value = false;
}

const yearOptions = Array.from({ length: 5 }, (_, i) => 2026 + i);

const difficultyStyles = {
    easy: 'bg-success/20 text-success',
    medium: 'bg-accent/20 text-accent',
    hard: 'bg-danger/20 text-danger',
};

const scheduleColorStyles = {
    primary: 'bg-primary/10 text-primary-strong border-primary/30',
    accent: 'bg-accent/20 text-accent border-accent/30',
    success: 'bg-success/20 text-success border-success/30',
    warning: 'bg-warning/20 text-warning border-warning/30',
    secondary: 'bg-surface-raised text-content-secondary border-border',
};

// Horario Modal Logic
const showScheduleModal = ref(false);
const showAddScheduleForm = ref(false);
const selectedDayTab = ref(1); // 1 = Lunes

const scheduleForm = useForm({
    course_name: '',
    day_of_week: 1,
    start_time: '08:00',
    end_time: '10:00',
    classroom: '',
    color: 'primary',
});

function openAddFormForDay(dayNumber) {
    scheduleForm.day_of_week = dayNumber;
    showAddScheduleForm.value = true;
}

function submitSchedule() {
    scheduleForm.post(route('calendar.schedules.store'), {
        preserveScroll: true,
        onSuccess: () => {
            scheduleForm.reset('course_name', 'classroom');
            showAddScheduleForm.value = false;
        },
    });
}

function deleteSchedule(id) {
    if (confirm('¿Deseas eliminar este horario de clase?')) {
        router.delete(route('calendar.schedules.destroy', { id }), {
            preserveScroll: true,
        });
    }
}

const filteredSchedules = computed(() => {
    return props.schedules.filter(s => Number(s.day_of_week) === selectedDayTab.value);
});
</script>

<template>
    <Head title="Calendario — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-5xl">
            <BaseCard class="mb-6">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="font-display text-3xl text-content-primary">Calendario</h1>
                        <p class="mt-1 text-sm text-content-secondary">Ciclo {{ academicCycle }} — Feriados, exámenes, horario de clases y misiones</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <BaseButton variant="secondary" @click="showScheduleModal = true">
                            📚 Horario de clases
                        </BaseButton>
                        <a :href="route('missions.index')" class="text-sm text-content-secondary hover:text-content-primary">← Ir a misiones</a>
                    </div>
                </header>
            </BaseCard>

            <BaseCard class="p-4">
                <div class="mb-4 flex items-center justify-between">
                    <button type="button" class="rounded-lg px-3 py-1.5 text-sm text-content-secondary hover:bg-surface-raised" @click="prevMonth">← {{ month === 1 ? monthNames[11] : monthNames[month - 2] }}</button>
                    <button type="button" class="font-display text-xl text-content-primary hover:text-primary-strong" @click="openMonthPicker">{{ monthNames[month - 1] }} {{ year }}</button>
                    <div class="flex items-center gap-2">
                        <button v-if="month !== currentMonth || year !== currentYear" type="button" class="rounded-lg px-2 py-1 text-xs text-content-muted hover:bg-surface-raised hover:text-content-primary" @click="goToToday">Hoy</button>
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm text-content-secondary hover:bg-surface-raised" @click="nextMonth">{{ month === 12 ? monthNames[0] : monthNames[month] }} →</button>
                    </div>
                </div>

                <div v-if="showMonthPicker" class="mb-4 flex items-center gap-3 rounded-lg bg-surface-raised p-3">
                    <select v-model="pickerMonth" class="rounded-lg border-border bg-surface px-3 py-1.5 text-sm outline-none">
                        <option v-for="(name, i) in monthNames" :key="i" :value="i + 1">{{ name }}</option>
                    </select>
                    <select v-model="pickerYear" class="rounded-lg border-border bg-surface px-3 py-1.5 text-sm outline-none">
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <BaseButton variant="primary" @click="applyMonthPicker">Ir</BaseButton>
                    <button type="button" class="text-sm text-content-muted hover:text-content-primary" @click="showMonthPicker = false">Cancelar</button>
                </div>

                <div class="grid grid-cols-7 gap-px rounded-lg overflow-hidden">
                    <div v-for="d in ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do']" :key="d" class="bg-surface-sunken p-2 text-center text-xs font-semibold text-content-muted">{{ d }}</div>
                    <div
                        v-for="(cell, i) in calendarDays" :key="i"
                        class="min-h-[110px] bg-surface p-2 transition"
                        :class="{
                            'bg-primary/10 ring-2 ring-primary': cell.isToday,
                            'opacity-40': cell.isPast && !cell.hasActivity,
                            'hover:bg-surface-raised': cell.hasActivity,
                            'cursor-default': !cell.hasActivity,
                        }">
                        <div class="flex items-center gap-1">
                            <span
                                v-if="cell.day" class="text-xs font-semibold"
                                :class="cell.isToday ? 'text-primary' : (cell.isPast ? 'text-content-muted' : 'text-content-secondary')">{{ cell.day }}</span>
                            <span v-if="cell.holiday" class="rounded bg-danger/10 px-1 text-[9px] text-danger" title="Feriado">🏖</span>
                            <span v-if="cell.isExam" class="rounded bg-warning/20 px-1 text-[9px] text-warning" title="Semana de exámenes">📝</span>
                        </div>
                        <div v-if="cell.holiday" class="mt-0.5 text-[9px] leading-tight text-danger truncate" :title="cell.holiday.name">{{ cell.holiday.name }}</div>

                        <!-- Clases del día -->
                        <div v-if="cell.schedules.length > 0" class="mt-1 space-y-0.5">
                            <div
                                v-for="s in cell.schedules"
                                :key="s.id"
                                class="truncate rounded border px-1 py-0.5 text-[9px] font-medium leading-tight"
                                :class="scheduleColorStyles[s.color] || scheduleColorStyles.primary"
                                :title="`${s.course_name} (${s.start_time} - ${s.end_time})${s.classroom ? ' [' + s.classroom + ']' : ''}`">
                                📖 {{ s.start_time }} {{ s.course_name }}
                            </div>
                        </div>

                        <!-- Misiones del día -->
                        <div class="mt-1 space-y-0.5">
                            <a
                                v-for="m in cell.missions" :key="m.id"
                                :href="route('missions.show', { id: m.id })"
                                class="block truncate rounded px-1 py-0.5 text-[10px] leading-tight transition hover:opacity-80"
                                :class="m.is_completed ? 'text-content-muted line-through' : (difficultyStyles[m.difficulty] || 'text-content-muted')"
                                :title="m.title">
                                🎯 {{ m.title }}
                            </a>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-content-muted">
                <span class="flex items-center gap-1"><span class="rounded bg-danger/10 px-1 text-[9px] text-danger">🏖</span> Feriado</span>
                <span class="flex items-center gap-1"><span class="rounded bg-warning/20 px-1 text-[9px] text-warning">📝</span> Semana de exámenes</span>
                <span class="flex items-center gap-1"><span class="rounded border bg-primary/10 px-1 text-[9px] text-primary-strong">📖</span> Horario de clase</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-success"></span> Misión pendiente</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-content-muted"></span> Misión completada</span>
            </div>

            <BaseCard v-if="!hasEvents" class="mt-4 flex flex-col items-center p-8 text-center">
                <span class="mb-3 text-3xl">📅</span>
                <h2 class="text-sm font-semibold text-content-primary">Sin eventos este mes</h2>
                <p class="mt-1 max-w-sm text-sm text-content-secondary">
                    No hay feriados, semanas de examen, horarios de clases ni misiones registradas.
                </p>
                <div class="mt-4 flex gap-3">
                    <BaseButton variant="primary" @click="showScheduleModal = true">Agregar horario de clases</BaseButton>
                    <a :href="route('missions.index')" class="inline-flex items-center rounded-lg border border-border px-3 py-1.5 text-sm text-content-secondary hover:text-content-primary">Crear una misión →</a>
                </div>
            </BaseCard>
        </div>

        <!-- Modal de Horario de Clases -->
        <BaseModal :show="showScheduleModal" title="Horario de Clases Semanal" @close="showScheduleModal = false">
            <div class="space-y-4">
                <!-- Pestañas por día de la semana -->
                <div class="flex overflow-x-auto border-b border-border pb-2 gap-1 scrollbar-none">
                    <button
                        v-for="(dName, i) in dayNames"
                        :key="i + 1"
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold whitespace-nowrap transition"
                        :class="selectedDayTab === (i + 1) ? 'bg-primary text-on-primary' : 'text-content-secondary hover:bg-surface-raised'"
                        @click="selectedDayTab = (i + 1); showAddScheduleForm = false">
                        {{ dName }}
                        <span class="ml-1 rounded-full bg-surface-sunken/40 px-1.5 py-0.5 text-[10px]">
                            {{ schedules.filter(s => Number(s.day_of_week) === (i + 1)).length }}
                        </span>
                    </button>
                </div>

                <!-- Botón para mostrar formulario de agregar -->
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-content-primary">
                        Clases del día {{ dayNames[selectedDayTab - 1] }}
                    </h3>
                    <BaseButton v-if="!showAddScheduleForm" variant="primary" class="text-xs py-1 px-3" @click="openAddFormForDay(selectedDayTab)">
                        + Agregar Clase
                    </BaseButton>
                </div>

                <!-- Formulario de Agregar Clase -->
                <form v-if="showAddScheduleForm" class="rounded-xl border border-border bg-surface-raised p-4 space-y-3" @submit.prevent="submitSchedule">
                    <h4 class="text-xs font-semibold text-content-primary uppercase tracking-wider">Nueva asignatura para {{ dayNames[scheduleForm.day_of_week - 1] }}</h4>

                    <div>
                        <label class="block text-xs font-medium text-content-secondary mb-1">Nombre del curso *</label>
                        <BaseInput v-model="scheduleForm.course_name" placeholder="Ej. Cálculo I, Química Orgánica" required />
                        <span v-if="scheduleForm.errors.course_name" class="text-xs text-danger">{{ scheduleForm.errors.course_name }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-content-secondary mb-1">Hora de entrada *</label>
                            <BaseInput v-model="scheduleForm.start_time" type="time" required />
                            <span v-if="scheduleForm.errors.start_time" class="text-xs text-danger">{{ scheduleForm.errors.start_time }}</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-content-secondary mb-1">Hora de salida *</label>
                            <BaseInput v-model="scheduleForm.end_time" type="time" required />
                            <span v-if="scheduleForm.errors.end_time" class="text-xs text-danger">{{ scheduleForm.errors.end_time }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-content-secondary mb-1">Aula / Salón (opcional)</label>
                            <BaseInput v-model="scheduleForm.classroom" placeholder="Ej. Aula 204" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-content-secondary mb-1">Color distintivo</label>
                            <BaseSelect v-model="scheduleForm.color">
                                <option value="primary">Primario (Rosa/Cyan)</option>
                                <option value="accent">Acento (Púrpura)</option>
                                <option value="success">Éxito (Verde)</option>
                                <option value="warning">Alerta (Ámbar)</option>
                                <option value="secondary">Secundario (Gris)</option>
                            </BaseSelect>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-1.5 text-xs text-content-muted hover:text-content-primary" @click="showAddScheduleForm = false">
                            Cancelar
                        </button>
                        <BaseButton variant="primary" type="submit" :disabled="scheduleForm.processing">
                            Guardar Horario
                        </BaseButton>
                    </div>
                </form>

                <!-- Lista de Clases del Día Seleccionado -->
                <div v-if="filteredSchedules.length > 0" class="space-y-2">
                    <div
                        v-for="s in filteredSchedules"
                        :key="s.id"
                        class="flex items-center justify-between rounded-xl border p-3 transition"
                        :class="scheduleColorStyles[s.color] || scheduleColorStyles.primary">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-sm">{{ s.course_name }}</span>
                                <span v-if="s.classroom" class="rounded bg-surface/50 px-1.5 py-0.5 text-[10px]">📍 {{ s.classroom }}</span>
                            </div>
                            <div class="mt-0.5 text-xs opacity-80">
                                ⏰ {{ s.start_time }} — {{ s.end_time }}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded p-1 text-content-muted hover:bg-danger/20 hover:text-danger transition"
                            title="Eliminar clase"
                            @click="deleteSchedule(s.id)">
                            🗑️
                        </button>
                    </div>
                </div>

                <div v-else-if="!showAddScheduleForm" class="py-6 text-center text-content-muted text-xs">
                    No tienes clases registradas para el día {{ dayNames[selectedDayTab - 1] }}.
                </div>
            </div>
        </BaseModal>
    </AppLayout>
</template>
