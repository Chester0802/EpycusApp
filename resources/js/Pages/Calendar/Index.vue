<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    missionsByDate: { type: Object, default: () => ({}) },
    holidays: { type: Object, default: () => ({}) },
    examDates: { type: Object, default: () => ({}) },
    month: { type: Number, required: true },
    year: { type: Number, required: true },
    todayDate: { type: String, required: true },
    academicCycle: { type: String, default: '2026-2' },
});

const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

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
        || Object.keys(props.examDates).length > 0;
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
        days.push({
            day: dayNum >= 1 && dayNum <= daysInMonth.value ? dayNum : null,
            date: dateStr,
            holiday,
            isExam: dateStr ? !!props.examDates[dateStr] : false,
            missions: dateStr && props.missionsByDate[dateStr] ? props.missionsByDate[dateStr] : [],
            isToday: dateStr === props.todayDate,
            isPast: dateStr !== null && dateStr < props.todayDate,
            hasActivity: dateStr && ((props.missionsByDate[dateStr]?.length ?? 0) > 0 || holiday || props.examDates[dateStr]),
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
</script>

<template>
    <Head title="Calendario — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-5xl">
            <BaseCard class="mb-6">
                <header class="flex items-center justify-between">
                    <div>
                        <h1 class="font-display text-3xl text-content-primary">Calendario</h1>
                        <p class="mt-1 text-sm text-content-secondary">Ciclo {{ academicCycle }} — Feriados, exámenes y misiones</p>
                    </div>
                    <div class="flex items-center gap-3">
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
                        <div class="mt-1 space-y-0.5">
                            <a
v-for="m in cell.missions" :key="m.id"
                                :href="route('missions.show', { id: m.id })"
                                class="block truncate rounded px-1 py-0.5 text-[10px] leading-tight transition hover:opacity-80"
                                :class="m.is_completed ? 'text-content-muted line-through' : (difficultyStyles[m.difficulty] || 'text-content-muted')"
                                :title="m.title">
                                {{ m.title }}
                            </a>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-content-muted">
                <span class="flex items-center gap-1"><span class="rounded bg-danger/10 px-1 text-[9px] text-danger">🏖</span> Feriado</span>
                <span class="flex items-center gap-1"><span class="rounded bg-warning/20 px-1 text-[9px] text-warning">📝</span> Semana de exámenes</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-success"></span> Misión pendiente</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-content-muted"></span> Misión completada</span>
            </div>

            <BaseCard v-if="!hasEvents" class="mt-4 flex flex-col items-center p-8 text-center">
                <span class="mb-3 text-3xl">📅</span>
                <h2 class="text-sm font-semibold text-content-primary">Sin eventos este mes</h2>
                <p class="mt-1 max-w-sm text-sm text-content-secondary">
                    No hay feriados, semanas de examen ni misiones con fecha en {{ monthNames[month - 1].toLowerCase() }}.
                </p>
                <a :href="route('missions.index')" class="mt-4 text-sm text-primary hover:text-primary-strong">Crear una misión →</a>
            </BaseCard>
        </div>
    </AppLayout>
</template>
