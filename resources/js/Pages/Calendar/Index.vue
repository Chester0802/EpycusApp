<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import NoteEditorModal from '@/Components/Calendar/NoteEditorModal.vue';
import {
    CalendarDays,
    BookOpen,
    Target,
    Trash2,
    NotebookText,
    Plus,
    Pencil,
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';

// Opciones para BaseSelect
const colorOptions = [
    { value: 'primary',   label: 'Primario (Rosa/Cyan)' },
    { value: 'accent',    label: 'Acento (Púrpura)' },
    { value: 'success',   label: 'Éxito (Verde)' },
    { value: 'warning',   label: 'Alerta (Ámbar)' },
    { value: 'secondary', label: 'Secundario (Gris)' },
];
const dayOptions = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 7, label: 'Domingo' },
];

const props = defineProps({
    missionsByDate: { type: Object, default: () => ({}) },
    holidays:       { type: Object, default: () => ({}) },
    examDates:      { type: Object, default: () => ({}) },
    courses:        { type: Array,  default: () => [] },
    month:          { type: Number, required: true },
    year:           { type: Number, required: true },
    todayDate:      { type: String, required: true },
    academicCycle:  { type: String, default: '2026-2' },
});

const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const dayNames   = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

// ── Formato de hora en 12h (a.m. / p.m.) ───────────────────────────────────
function formatTime12h(timeStr) {
    if (!timeStr) return '';
    const parts = timeStr.split(':');
    const h = parseInt(parts[0], 10);
    const m = parts[1] ? parts[1].slice(0, 2) : '00';
    if (isNaN(h)) return timeStr;
    const period = h >= 12 ? 'p.m.' : 'a.m.';
    const h12 = h % 12 || 12;
    return `${String(h12).padStart(2, '0')}:${m} ${period}`;
}

const today        = new Date(props.todayDate);
const currentMonth = today.getMonth() + 1;
const currentYear  = today.getFullYear();

const daysInMonth    = computed(() => new Date(props.year, props.month, 0).getDate());
const firstDayOfWeek = computed(() => {
    const d = new Date(props.year, props.month - 1, 1).getDay();
    return d === 0 ? 6 : d - 1;
});

const hasEvents = computed(() =>
    Object.keys(props.missionsByDate).length > 0
    || Object.keys(props.holidays).length > 0
    || props.courses.length > 0,
);

// Aplanar cursos → sessions para el calendario
const flatSessions = computed(() => {
    const result = [];
    for (const course of props.courses) {
        for (const session of (course.sessions ?? [])) {
            result.push({
                ...session,
                course_id: course.id,
                course_name: course.name,
                color: course.color,
                starts_at: course.starts_at,
                ends_at: course.ends_at,
            });
        }
    }
    return result;
});

const calendarDays = computed(() => {
    const days       = [];
    const totalCells = Math.ceil((firstDayOfWeek.value + daysInMonth.value) / 7) * 7;
    for (let i = 0; i < totalCells; i++) {
        const dayNum  = i - firstDayOfWeek.value + 1;
        const dateStr = dayNum >= 1 && dayNum <= daysInMonth.value
            ? `${props.year}-${String(props.month).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`
            : null;
        const holiday = dateStr ? props.holidays[dateStr] : null;

        const dayOfWeekIndex = (i % 7) + 1;
        const daySessions    = dateStr ? flatSessions.value.filter(s => {
            if (Number(s.day_of_week) !== dayOfWeekIndex) return false;
            if (s.starts_at && dateStr < s.starts_at) return false;
            if (s.ends_at && dateStr > s.ends_at) return false;
            return true;
        }) : [];

        days.push({
            day:         dayNum >= 1 && dayNum <= daysInMonth.value ? dayNum : null,
            date:        dateStr,
            holiday,
            missions:    dateStr && props.missionsByDate[dateStr] ? props.missionsByDate[dateStr] : [],
            sessions:    daySessions,
            isToday:     dateStr === props.todayDate,
            isPast:      dateStr !== null && dateStr < props.todayDate,
            hasActivity: dateStr && (
                (props.missionsByDate[dateStr]?.length ?? 0) > 0
                || holiday
                || daySessions.length > 0
            ),
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
function goToToday() { goToMonth(currentMonth, currentYear); }

const showMonthPicker = ref(false);
const pickerMonth     = ref(props.month);
const pickerYear      = ref(props.year);

function openMonthPicker() {
    pickerMonth.value = props.month;
    pickerYear.value  = props.year;
    showMonthPicker.value = true;
}
function applyMonthPicker() {
    goToMonth(pickerMonth.value, pickerYear.value);
    showMonthPicker.value = false;
}

const yearOptions = Array.from({ length: 5 }, (_, i) => 2026 + i);

const difficultyStyles = {
    easy:   'bg-success/20 text-success',
    medium: 'bg-accent/20 text-accent',
    hard:   'bg-danger/20 text-danger',
};

const scheduleColorStyles = {
    primary:   'bg-primary/10 text-primary-strong border-primary/30',
    accent:    'bg-accent/20 text-accent border-accent/30',
    success:   'bg-success/20 text-success border-success/30',
    warning:   'bg-warning/20 text-warning border-warning/30',
    secondary: 'bg-surface-raised text-content-secondary border-border',
};

// ── Modal de Gestión de Cursos ─────────────────────────────────────────────
const showCourseModal   = ref(false);
const showCourseForm    = ref(false);
const editingCourseId   = ref(null);

// Formulario multi-sesión
const courseForm = useForm({
    name:      '',
    color:     'primary',
    starts_at: '',
    ends_at:   '',
    sessions:  [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }],
});

function openCreateCourseForm() {
    editingCourseId.value = null;
    courseForm.reset();
    courseForm.clearErrors();
    courseForm.name = '';
    courseForm.color = 'primary';
    courseForm.starts_at = '';
    courseForm.ends_at = '';
    courseForm.sessions = [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }];
    showCourseForm.value = true;
}

function openEditCourseForm(course) {
    editingCourseId.value = course.id;
    courseForm.clearErrors();
    courseForm.name = course.name;
    courseForm.color = course.color || 'primary';
    courseForm.starts_at = course.starts_at || '';
    courseForm.ends_at = course.ends_at || '';
    courseForm.sessions = (course.sessions && course.sessions.length > 0)
        ? course.sessions.map(s => ({
            day_of_week: Number(s.day_of_week),
            start_time:  s.start_time,
            end_time:    s.end_time,
            classroom:   s.classroom || '',
        }))
        : [{ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' }];
    showCourseForm.value = true;
}

function addSession() {
    courseForm.sessions.push({ day_of_week: 1, start_time: '08:00', end_time: '10:00', classroom: '' });
}
function removeSession(index) {
    if (courseForm.sessions.length > 1) courseForm.sessions.splice(index, 1);
}
function setSessionDay(idx, val) {
    courseForm.sessions[idx].day_of_week = Number(val);
}

function submitCourse() {
    if (editingCourseId.value) {
        courseForm.put(route('calendar.courses.update', { id: editingCourseId.value }), {
            preserveScroll: true,
            onSuccess: () => {
                showCourseForm.value = false;
                editingCourseId.value = null;
            },
        });
    } else {
        courseForm.post(route('calendar.courses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showCourseForm.value = false;
                courseForm.reset();
            },
        });
    }
}

function deleteCourse(id) {
    if (confirm('¿Deseas eliminar este curso y todos sus horarios y apuntes?')) {
        router.delete(route('calendar.courses.destroy', { id }), { preserveScroll: true });
    }
}

// ── Modal de Apuntes ────────────────────────────────────────────────────────
const showNoteModal  = ref(false);
const selectedCourse = ref(null);

function openNote(courseId) {
    const course = props.courses.find(c => c.id === courseId);
    if (!course) return;
    selectedCourse.value = course;
    showNoteModal.value  = true;
}

// ── Selección de Día para Vista Detallada (Especialmente en Móvil) ─────────
const selectedDay = ref(props.todayDate);

function selectDay(dateStr) {
    if (dateStr) {
        selectedDay.value = dateStr;
    }
}

const selectedDayData = computed(() => {
    if (!selectedDay.value) return null;
    return calendarDays.value.find(d => d.date === selectedDay.value) || null;
});

function formatSelectedDayHeader(dateStr) {
    if (!dateStr) return '';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const dayIdx = dateObj.getDay() === 0 ? 6 : dateObj.getDay() - 1;
        const dayName = dayNames[dayIdx];
        const monthName = monthNames[m - 1];
        return `${dayName}, ${d} de ${monthName} de ${y}`;
    } catch {
        return dateStr;
    }
}
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
                        <BaseButton variant="secondary" @click="showCourseModal = true">
                            <BookOpen :size="16" />
                            Mis Cursos
                        </BaseButton>
                        <a :href="route('missions.index')" class="text-sm text-content-secondary hover:text-content-primary">
                            <ChevronLeft :size="14" class="inline" /> Ir a misiones
                        </a>
                    </div>
                </header>
            </BaseCard>

            <BaseCard class="p-4">
                <div class="mb-4 flex items-center justify-between">
                    <button type="button" class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm text-content-secondary hover:bg-surface-raised" @click="prevMonth">
                        <ChevronLeft :size="16" />
                        {{ month === 1 ? monthNames[11] : monthNames[month - 2] }}
                    </button>
                    <button type="button" class="font-display text-xl text-content-primary hover:text-primary-strong" @click="openMonthPicker">
                        {{ monthNames[month - 1] }} {{ year }}
                    </button>
                    <div class="flex items-center gap-2">
                        <button v-if="month !== currentMonth || year !== currentYear" type="button" class="rounded-lg px-2 py-1 text-xs text-content-muted hover:bg-surface-raised hover:text-content-primary" @click="goToToday">Hoy</button>
                        <button type="button" class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm text-content-secondary hover:bg-surface-raised" @click="nextMonth">
                            {{ month === 12 ? monthNames[0] : monthNames[month] }}
                            <ChevronRight :size="16" />
                        </button>
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

                <div class="grid grid-cols-7 gap-1 overflow-hidden rounded-xl bg-surface-sunken/40 p-1 sm:gap-px sm:bg-transparent sm:p-0">
                    <div v-for="d in ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do']" :key="d" class="bg-surface-sunken p-1.5 text-center text-[11px] font-bold text-content-muted sm:p-2 sm:text-xs">{{ d }}</div>
                    <div
                        v-for="(cell, i) in calendarDays" :key="i"
                        class="relative flex flex-col justify-between rounded-lg bg-surface p-1 transition cursor-pointer sm:min-h-[110px] sm:rounded-none sm:p-2"
                        :class="{
                            'ring-2 ring-primary bg-primary/10': cell.date && cell.date === selectedDay,
                            'opacity-35': cell.isPast && !cell.hasActivity && cell.date !== selectedDay,
                            'hover:bg-surface-raised': cell.date,
                            'pointer-events-none opacity-20': !cell.date,
                        }"
                        @click="selectDay(cell.date)"
                    >
                        <div class="flex items-center justify-between sm:justify-start sm:gap-1">
                            <span
                                v-if="cell.day" class="text-xs font-bold leading-none sm:text-sm"
                                :class="cell.isToday ? 'text-primary' : (cell.date === selectedDay ? 'text-primary font-extrabold' : (cell.isPast ? 'text-content-muted' : 'text-content-secondary'))"
                            >{{ cell.day }}</span>
                            <span v-if="cell.holiday" class="rounded bg-danger/15 px-1 py-0.2 text-[8px] font-bold text-danger sm:text-[9px]" title="Feriado Oficial">F</span>
                        </div>

                        <!-- Indicadores compactos para Móvil (< sm) -->
                        <div class="mt-1 flex items-center justify-center gap-1 sm:hidden">
                            <span v-if="cell.holiday" class="h-1.5 w-1.5 rounded-full bg-danger" title="Feriado"></span>
                            <span
                                v-for="s in cell.sessions.slice(0, 2)"
                                :key="s.id"
                                class="h-1.5 w-1.5 rounded-full"
                                :class="s.color === 'accent' ? 'bg-accent' : (s.color === 'success' ? 'bg-success' : (s.color === 'warning' ? 'bg-warning' : 'bg-primary'))"
                            ></span>
                            <span v-if="cell.sessions.length > 2" class="text-[7px] font-bold text-content-muted">+{{ cell.sessions.length - 2 }}</span>
                            <span v-if="cell.missions.length > 0" class="h-1.5 w-1.5 rounded-full bg-success/80"></span>
                        </div>

                        <!-- Vista completa para Escritorio (>= sm) -->
                        <div class="hidden sm:block">
                            <div v-if="cell.holiday" class="mt-0.5 truncate text-[9px] font-medium leading-tight text-danger" :title="cell.holiday.name">{{ cell.holiday.name }}</div>

                            <!-- Clases del día — clic para abrir apunte -->
                            <div v-if="cell.sessions.length > 0" class="mt-1 space-y-0.5">
                                <button
                                    v-for="s in cell.sessions"
                                    :key="s.id"
                                    type="button"
                                    class="flex w-full items-center gap-1 truncate rounded border px-1 py-0.5 text-left text-[9px] font-medium leading-tight transition hover:opacity-80"
                                    :class="scheduleColorStyles[s.color] || scheduleColorStyles.primary"
                                    :title="`${s.course_name} (${formatTime12h(s.start_time)} - ${formatTime12h(s.end_time)})${s.classroom ? ' [' + s.classroom + ']' : ''} — Clic para ver apunte`"
                                    @click.stop="openNote(s.course_id)"
                                >
                                    <BookOpen :size="9" class="shrink-0" />
                                    <span class="truncate">{{ formatTime12h(s.start_time) }} {{ s.course_name }}</span>
                                </button>
                            </div>

                            <!-- Misiones del día -->
                            <div v-if="cell.missions.length > 0" class="mt-1 space-y-0.5">
                                <a
                                    v-for="m in cell.missions" :key="m.id"
                                    :href="route('missions.show', { id: m.id })"
                                    class="flex items-center gap-0.5 truncate rounded px-1 py-0.5 text-[10px] leading-tight transition hover:opacity-80"
                                    :class="m.is_completed ? 'text-content-muted line-through' : (difficultyStyles[m.difficulty] || 'text-content-muted')"
                                    :title="m.title"
                                    @click.stop
                                >
                                    <Target :size="9" class="shrink-0" />
                                    <span class="truncate">{{ m.title }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <!-- ── Panel de Detalle / Agenda del Día Seleccionado ──────────── -->
            <BaseCard v-if="selectedDayData" class="mt-4 p-4 sm:p-5">
                <div class="flex items-center justify-between border-b border-border/60 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-display text-base font-bold capitalize text-content-primary sm:text-lg">
                                {{ formatSelectedDayHeader(selectedDay) }}
                            </h2>
                            <span v-if="selectedDayData.isToday" class="rounded-full bg-primary/15 px-2 py-0.5 text-[10px] font-bold text-primary">
                                Hoy
                            </span>
                        </div>
                        <p class="text-xs text-content-secondary">
                            {{ selectedDayData.sessions.length }} {{ selectedDayData.sessions.length === 1 ? 'clase programada' : 'clases programadas' }}
                            • {{ selectedDayData.missions.length }} {{ selectedDayData.missions.length === 1 ? 'misión' : 'misiones' }}
                        </p>
                    </div>
                    <BaseButton variant="secondary" class="text-xs" @click="showCourseModal = true; openCreateCourseForm()">
                        <Plus :size="13" />
                        <span class="hidden sm:inline">Agregar</span> Horario
                    </BaseButton>
                </div>

                <!-- Banner de Feriado si aplica -->
                <div v-if="selectedDayData.holiday" class="mt-3 flex items-center gap-2.5 rounded-xl border border-danger/25 bg-danger/10 p-3 text-danger">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-danger/20 font-bold text-xs">F</span>
                    <div>
                        <p class="text-xs font-bold">{{ selectedDayData.holiday.name }}</p>
                        <p class="text-[11px] opacity-80">Feriado Oficial de Perú</p>
                    </div>
                </div>

                <!-- Lista de Cursos / Clases del Día -->
                <div class="mt-4">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-content-muted">
                        Horario de Clases
                    </h3>

                    <div v-if="selectedDayData.sessions.length > 0" class="space-y-2">
                        <div
                            v-for="s in selectedDayData.sessions"
                            :key="s.id"
                            class="flex flex-col justify-between gap-2 rounded-xl border border-border bg-surface-raised/60 p-3 transition hover:border-primary/40 sm:flex-row sm:items-center"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="mt-1 h-3 w-3 shrink-0 rounded-full"
                                    :class="s.color === 'accent' ? 'bg-accent' : (s.color === 'success' ? 'bg-success' : (s.color === 'warning' ? 'bg-warning' : 'bg-primary'))"
                                ></span>
                                <div>
                                    <h4 class="font-semibold text-content-primary text-sm">{{ s.course_name }}</h4>
                                    <p class="text-xs text-content-secondary mt-0.5">
                                        <span class="font-medium text-primary">{{ formatTime12h(s.start_time) }} – {{ formatTime12h(s.end_time) }}</span>
                                        <span v-if="s.classroom" class="ml-2 rounded bg-surface-sunken px-1.5 py-0.5 text-[10px] text-content-muted">
                                            Aula: {{ s.classroom }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <BaseButton
                                variant="secondary"
                                class="self-start text-xs sm:self-center"
                                @click="openNote(s.course_id)"
                            >
                                <NotebookText :size="14" />
                                <span>Ver / Editar Apuntes</span>
                            </BaseButton>
                        </div>
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-border/70 p-4 text-center text-xs text-content-muted">
                        No hay clases universitarias programadas para este día.
                    </div>
                </div>

                <!-- Lista de Misiones del Día -->
                <div class="mt-4">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-content-muted">
                        Misiones y Tareas del Día
                    </h3>

                    <div v-if="selectedDayData.missions.length > 0" class="space-y-1.5">
                        <a
                            v-for="m in selectedDayData.missions"
                            :key="m.id"
                            :href="route('missions.show', { id: m.id })"
                            class="flex items-center justify-between rounded-lg border border-border/60 bg-surface-raised/40 p-2.5 transition hover:bg-surface-raised"
                        >
                            <div class="flex items-center gap-2">
                                <Target :size="14" class="text-primary" />
                                <span class="text-xs font-medium text-content-primary" :class="{ 'line-through text-content-muted': m.is_completed }">
                                    {{ m.title }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="m.difficulty"
                                    class="rounded px-1.5 py-0.5 text-[10px] font-medium capitalize"
                                    :class="difficultyStyles[m.difficulty] || 'bg-surface text-content-muted'"
                                >
                                    {{ m.difficulty }}
                                </span>
                                <span class="text-[10px] font-bold text-warning">+{{ m.xp_reward || 10 }} XP</span>
                            </div>
                        </a>
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-border/70 p-4 text-center text-xs text-content-muted">
                        No hay misiones programadas para esta fecha.
                    </div>
                </div>
            </BaseCard>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-content-muted">
                <span class="flex items-center gap-1"><span class="rounded bg-danger/10 px-1 text-[9px] text-danger font-bold">F</span> Feriado Oficial</span>
                <span class="flex items-center gap-1"><BookOpen :size="10" class="text-primary" /> Clase (toca el día para ver detalle)</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-success"></span> Misión</span>
            </div>

            <BaseCard v-if="!hasEvents" class="mt-4 flex flex-col items-center p-8 text-center">
                <CalendarDays :size="40" class="mb-3 text-content-muted" />
                <h2 class="text-sm font-semibold text-content-primary">Sin eventos este mes</h2>
                <p class="mt-1 max-w-sm text-sm text-content-secondary">
                    No hay feriados, cursos ni misiones registradas.
                </p>
                <div class="mt-4 flex gap-3">
                    <BaseButton variant="primary" @click="showCourseModal = true; openCreateCourseForm()">Agregar mis cursos</BaseButton>
                    <a :href="route('missions.index')" class="inline-flex items-center rounded-lg border border-border px-3 py-1.5 text-sm text-content-secondary hover:text-content-primary">
                        Crear una misión <ChevronRight :size="14" />
                    </a>
                </div>
            </BaseCard>
        </div>

        <!-- ── Modal de Gestión de Cursos ─────────────────────────────────── -->
        <BaseModal :show="showCourseModal" title="Mis Cursos" @close="showCourseModal = false">
            <div class="space-y-4">

                <!-- Botón para agregar nuevo curso -->
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-content-primary">
                        {{ showCourseForm ? (editingCourseId ? 'Editar Curso' : 'Nuevo Curso') : 'Cursos registrados' }}
                    </h3>
                    <BaseButton v-if="!showCourseForm" variant="primary" class="px-3 py-1 text-xs" @click="openCreateCourseForm">
                        <Plus :size="14" />
                        Nuevo Curso
                    </BaseButton>
                </div>

                <!-- ── Formulario multi-sesión (Crear / Editar) ─────────────── -->
                <form v-if="showCourseForm" class="space-y-4 rounded-xl border border-border bg-surface-raised p-4" @submit.prevent="submitCourse">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-content-primary">
                            {{ editingCourseId ? 'Modificar datos del curso' : 'Detalles del nuevo curso' }}
                        </h4>
                        <span v-if="editingCourseId" class="rounded bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">Modo Edición</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-content-secondary">Nombre del curso *</label>
                            <BaseInput v-model="courseForm.name" placeholder="Ej. Inglés, Cálculo I" required />
                            <span v-if="courseForm.errors.name" class="text-xs text-danger">{{ courseForm.errors.name }}</span>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="mb-1 block text-xs font-medium text-content-secondary">Color distintivo</label>
                            <BaseSelect
                                id="course-color"
                                label=""
                                :options="colorOptions"
                                :model-value="courseForm.color"
                                @update:model-value="courseForm.color = $event"
                            />
                        </div>
                        <div class="col-span-2 grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-content-secondary">Fecha inicio (opcional)</label>
                                <BaseInput v-model="courseForm.starts_at" type="date" />
                                <span v-if="courseForm.errors.starts_at" class="text-xs text-danger">{{ courseForm.errors.starts_at }}</span>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-content-secondary">Fecha fin (opcional)</label>
                                <BaseInput v-model="courseForm.ends_at" type="date" />
                                <span v-if="courseForm.errors.ends_at" class="text-xs text-danger">{{ courseForm.errors.ends_at }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sesiones (días/horarios) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-content-secondary">Días y horarios</span>
                            <button type="button" class="flex items-center gap-1 text-xs text-primary hover:underline" @click="addSession">
                                <Plus :size="12" /> Agregar día
                            </button>
                        </div>

                        <div
                            v-for="(session, idx) in courseForm.sessions"
                            :key="idx"
                            class="space-y-2 rounded-lg border border-border/50 bg-surface p-3"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-content-muted">Sesión {{ idx + 1 }}</span>
                                <button
                                    v-if="courseForm.sessions.length > 1"
                                    type="button"
                                    class="flex items-center gap-1 text-[10px] text-danger hover:underline"
                                    @click="removeSession(idx)"
                                >
                                    <Trash2 :size="11" /> Eliminar sesión
                                </button>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="mb-1 block text-[10px] text-content-muted">Día *</label>
                                    <BaseSelect
                                        :id="`session-day-${idx}`"
                                        label=""
                                        :options="dayOptions"
                                        :model-value="session.day_of_week"
                                        compact
                                        @update:model-value="setSessionDay(idx, $event)"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] text-content-muted">Entrada (24h) *</label>
                                    <BaseInput v-model="session.start_time" type="time" required />
                                    <span class="mt-0.5 block text-[9px] text-content-muted">{{ formatTime12h(session.start_time) }}</span>
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] text-content-muted">Salida (24h) *</label>
                                    <BaseInput v-model="session.end_time" type="time" required />
                                    <span class="mt-0.5 block text-[9px] text-content-muted">{{ formatTime12h(session.end_time) }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] text-content-muted">Aula / Salón (opcional)</label>
                                <BaseInput v-model="session.classroom" placeholder="Ej. Aula 204, Laboratorio B" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="px-3 py-1.5 text-xs text-content-muted hover:text-content-primary" @click="showCourseForm = false; editingCourseId = null">
                            Cancelar
                        </button>
                        <BaseButton variant="primary" type="submit" :disabled="courseForm.processing">
                            {{ editingCourseId ? 'Guardar Cambios' : 'Guardar Curso' }}
                        </BaseButton>
                    </div>
                </form>

                <!-- ── Lista de todos los cursos ───────────────────────────── -->
                <div v-if="courses.length > 0 && !showCourseForm" class="space-y-2">
                    <div
                        v-for="c in courses"
                        :key="c.id"
                        class="rounded-xl border p-3 transition"
                        :class="scheduleColorStyles[c.color] || scheduleColorStyles.primary"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <span class="font-semibold text-sm">{{ c.name }}</span>
                                <!-- Todas las sessions del curso con formato 12h (a.m. / p.m.) -->
                                <div class="mt-1 space-y-0.5">
                                    <div
                                        v-for="s in c.sessions"
                                        :key="s.id"
                                        class="text-xs opacity-85"
                                    >
                                        <strong>{{ dayNames[s.day_of_week - 1] }}:</strong> {{ formatTime12h(s.start_time) }} — {{ formatTime12h(s.end_time) }}
                                        <span v-if="s.classroom" class="ml-1 opacity-75">· 📍 {{ s.classroom }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    class="rounded p-1.5 text-content-muted transition hover:bg-surface/50 hover:text-content-primary"
                                    title="Modificar curso y horarios"
                                    @click="openEditCourseForm(c)"
                                >
                                    <Pencil :size="15" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded p-1.5 text-content-muted transition hover:bg-surface/50 hover:text-content-primary"
                                    title="Ver apunte de este curso"
                                    @click="showCourseModal = false; openNote(c.id)"
                                >
                                    <NotebookText :size="15" />
                                </button>
                                <button
                                    type="button"
                                    class="rounded p-1.5 text-content-muted transition hover:bg-danger/20 hover:text-danger"
                                    title="Eliminar curso"
                                    @click="deleteCourse(c.id)"
                                >
                                    <Trash2 :size="15" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="courses.length === 0 && !showCourseForm" class="py-6 text-center text-xs text-content-muted">
                    No tienes cursos registrados todavía.
                </div>
            </div>
        </BaseModal>

        <!-- ── Editor de Apuntes ──────────────────────────────────────────── -->
        <NoteEditorModal
            :show="showNoteModal"
            :course="selectedCourse"
            @close="showNoteModal = false"
        />
    </AppLayout>
</template>
