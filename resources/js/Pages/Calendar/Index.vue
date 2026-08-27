<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import NoteEditorModal from '@/Components/Calendar/NoteEditorModal.vue';
import { triggerConfetti, triggerHapticVibration } from '@/utils/celebration';
import {
    BookOpen,
    Target,
    Trash2,
    NotebookText,
    Plus,
    Pencil,
    ChevronLeft,
    ChevronRight,
} from '@lucide/vue';

const props = defineProps({
    missionsByDate: { type: Object, default: () => ({}) },
    holidays:       { type: Object, default: () => ({}) },
    examDates:      { type: Object, default: () => ({}) },
    courses:        { type: Array,  default: () => [] },
    plan:           { type: Object, default: () => ({ blocks: { morning: [], afternoon: [], night: [], anytime: [] }, stats: { total: 0, done: 0, skipped: 0, postponed: 0, completion_rate: 0 }, routines: [] }) },
    month:          { type: Number, required: true },
    year:           { type: Number, required: true },
    todayDate:      { type: String, required: true },
    selectedDate:   { type: String, default: null },
    academicCycle:  { type: String, default: '2026-2' },
    avatarStyle:    { type: String, default: 'base' },
    avatarGender:   { type: String, default: 'm' },
    progress:       { type: Object, default: () => ({ total_xp: 0, phase: 1, streak: 0 }) },
    xp_awarded:     { type: Number, default: 0 },
});

const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const dayNames   = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

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
    router.get(route('calendar.index', { month: m, year: y, date: selectedDay.value }), { preserveScroll: true });
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

// ── Cursos ─────────────────────────────────────────────────────────────────
const showCourseModal   = ref(false);
const showCourseForm    = ref(false);
const editingCourseId   = ref(null);

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

// ── Apuntes ────────────────────────────────────────────────────────────────
const showNoteModal  = ref(false);
const selectedCourse = ref(null);

function openNote(courseId) {
    const course = props.courses.find(c => c.id === courseId);
    if (!course) return;
    selectedCourse.value = course;
    showNoteModal.value  = true;

    // Sincronizar parámetro en la URL para que persista ante recargas (F5)
    if (typeof window !== 'undefined' && window.history) {
        const url = new URL(window.location.href);
        url.searchParams.set('note', String(courseId));
        window.history.replaceState({}, '', url.toString());
    }
}

function closeNote() {
    showNoteModal.value = false;
    selectedCourse.value = null;

    // Remover parámetro de la URL
    if (typeof window !== 'undefined' && window.history) {
        const url = new URL(window.location.href);
        url.searchParams.delete('note');
        url.searchParams.delete('course_id');
        window.history.replaceState({}, '', url.toString());
    }
}

// ── Selección de Día & Modo de Vista ────────────────────────────────────────
const selectedDay = ref(props.selectedDate || props.todayDate);
const calendarViewMode = ref('timeblocking'); // 'grid' | 'timeblocking'

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('view') === 'grid') {
        calendarViewMode.value = 'grid';
    } else if (urlParams.get('view') === 'timeblocking') {
        calendarViewMode.value = 'timeblocking';
    }

    // Auto-abrir apunte si viene en la URL (?note=ID o ?course_id=ID)
    const noteParam = urlParams.get('note') || urlParams.get('course_id');
    if (noteParam) {
        const targetId = Number(noteParam);
        const course = props.courses.find(c => c.id === targetId);
        if (course) {
            selectedCourse.value = course;
            showNoteModal.value = true;
        }
    }
});

function selectDay(dateStr) {
    if (dateStr) {
        selectedDay.value = dateStr;
        router.visit(route('calendar.index', { date: dateStr, month: props.month, year: props.year }), {
            preserveScroll: true,
            preserveState: true,
        });
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

function changePlanDate(deltaDays) {
    const d = new Date(selectedDay.value);
    d.setDate(d.getDate() + deltaDays);
    const newDateStr = d.toISOString().split('T')[0];
    selectDay(newDateStr);
}

// ── Time-Blocking 24h Helpers ──────────────────────────────────────────────
const timelineHours = [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23];

function getHourSlotData(hour) {
    const sessions = selectedDayData.value?.sessions || [];
    const hourStr = String(hour).padStart(2, '0');

    // Clases en esta hora
    const sessionsInHour = sessions.filter(s => {
        const startH = parseInt(s.start_time.split(':')[0], 10);
        const endH = parseInt(s.end_time.split(':')[0], 10);
        return hour >= startH && hour < endH;
    });

    // Actividades del plan diario programadas a esta hora
    const allPlanItems = [
        ...(props.plan?.blocks?.morning || []),
        ...(props.plan?.blocks?.afternoon || []),
        ...(props.plan?.blocks?.night || []),
        ...(props.plan?.blocks?.anytime || []),
    ];

    const planItemsInHour = allPlanItems.filter(item => {
        if (!item.scheduled_time) return false;
        const itemHour = parseInt(item.scheduled_time.split(':')[0], 10);
        return itemHour === hour;
    });

    return {
        sessions: sessionsInHour,
        planItems: planItemsInHour,
        isFree: sessionsInHour.length === 0 && planItemsInHour.length === 0,
    };
}

// ── Day Planner / Rutinas: Acciones y Modales ───────────────────────────────
const showAddPlanModal = ref(false);
const showEditPlanModal = ref(false);
const showSkipModal = ref(false);
const showPostponeModal = ref(false);
const showRoutinesModal = ref(false);
const selectedPlanItem = ref(null);
const isProcessing = ref(false);

const planItemForm = ref({
    id: null,
    title: '',
    category: 'estudio',
    time_block: 'morning',
    scheduled_time: '',
    estimated_minutes: 15,
    notes: '',
});

const skipReason = ref('cansancio');
const postponeBlock = ref('afternoon');

const routineForm = ref({
    id: null,
    title: '',
    category: 'salud',
    time_block: 'morning',
    scheduled_time: '07:30',
    estimated_minutes: 15,
    days_of_week: [1, 2, 3, 4, 5, 6, 7],
});

const categories = [
    { value: 'salud', label: '🌿 Salud y Bienestar' },
    { value: 'estudio', label: '📚 Estudio y Clases' },
    { value: 'personal', label: '✨ Cuidado Personal' },
    { value: 'trabajo', label: '💼 Trabajo y Proyectos' },
    { value: 'general', label: '📌 General' },
];

const timeBlocks = [
    { value: 'morning', label: '🌅 Rutina Matutina (Mañana)' },
    { value: 'afternoon', label: '☀️ Bloque de Tarde (Foco)' },
    { value: 'night', label: '🌙 Rutina Nocturna (Cierre)' },
    { value: 'anytime', label: '⚡ Cualquier Momento' },
];

const skipReasons = [
    { value: 'cansancio', label: '😴 Cansancio físico o mental' },
    { value: 'sin_tiempo', label: '⏰ Me faltó tiempo / imprevisto' },
    { value: 'desanimo', label: '🌧️ Falta de motivación' },
    { value: 'salud', label: '🩹 Malestar de salud' },
    { value: 'otro', label: '📝 Otro motivo' },
];

function updatePlanItemStatus(item, status, skipReasonVal = null, postponeToBlockVal = null) {
    isProcessing.value = true;
    router.patch(
        route('calendar.planner.items.status', { id: item.id }),
        {
            status,
            skip_reason: skipReasonVal,
            postpone_to_block: postponeToBlockVal,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                if (status === 'done') {
                    triggerConfetti();
                    triggerHapticVibration([40, 30, 80]);
                }
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function handleMarkDone(item) {
    updatePlanItemStatus(item, 'done');
}

function openSkipModal(item) {
    selectedPlanItem.value = item;
    skipReason.value = 'cansancio';
    showSkipModal.value = true;
}

function confirmSkip() {
    if (!selectedPlanItem.value) return;
    updatePlanItemStatus(selectedPlanItem.value, 'skipped', skipReason.value);
    showSkipModal.value = false;
    selectedPlanItem.value = null;
}

function openPostponeModal(item) {
    selectedPlanItem.value = item;
    postponeBlock.value = item.time_block === 'morning' ? 'afternoon' : 'night';
    showPostponeModal.value = true;
}

function confirmPostpone() {
    if (!selectedPlanItem.value) return;
    updatePlanItemStatus(selectedPlanItem.value, 'postponed', null, postponeBlock.value);
    showPostponeModal.value = false;
    selectedPlanItem.value = null;
}

function openAddPlanModal(block = 'morning') {
    planItemForm.value = {
        id: null,
        title: '',
        category: 'estudio',
        time_block: block,
        scheduled_time: '',
        estimated_minutes: 15,
        notes: '',
    };
    showAddPlanModal.value = true;
}

function openEditPlanModal(item) {
    planItemForm.value = {
        id: item.id,
        title: item.title,
        category: item.category,
        time_block: item.time_block,
        scheduled_time: item.scheduled_time || '',
        estimated_minutes: item.estimated_minutes || 15,
        notes: item.notes || '',
    };
    showEditPlanModal.value = true;
}

function submitPlanItem() {
    isProcessing.value = true;
    if (planItemForm.value.id) {
        // Actualizar actividad existente
        router.put(
            route('calendar.planner.items.update', { id: planItemForm.value.id }),
            planItemForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    isProcessing.value = false;
                    showEditPlanModal.value = false;
                },
                onError: () => {
                    isProcessing.value = false;
                },
            }
        );
    } else {
        // Crear nueva actividad
        router.post(
            route('calendar.planner.items.store'),
            {
                ...planItemForm.value,
                plan_date: selectedDay.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isProcessing.value = false;
                    showAddPlanModal.value = false;
                },
                onError: () => {
                    isProcessing.value = false;
                },
            }
        );
    }
}

function deletePlanItem(item) {
    if (!confirm('¿Deseas eliminar esta actividad del plan de hoy?')) return;
    router.delete(route('calendar.planner.items.destroy', { id: item.id }), {
        preserveScroll: true,
    });
}

function openEditRoutine(routine) {
    routineForm.value = {
        id: routine.id,
        title: routine.title,
        category: routine.category,
        time_block: routine.time_block,
        scheduled_time: routine.scheduled_time || '',
        estimated_minutes: routine.estimated_minutes || 15,
        days_of_week: routine.days_of_week || [1, 2, 3, 4, 5, 6, 7],
    };
}

function resetRoutineForm() {
    routineForm.value = {
        id: null,
        title: '',
        category: 'salud',
        time_block: 'morning',
        scheduled_time: '07:30',
        estimated_minutes: 15,
        days_of_week: [1, 2, 3, 4, 5, 6, 7],
    };
}

function submitRoutine() {
    isProcessing.value = true;
    if (routineForm.value.id) {
        router.put(
            route('calendar.planner.routines.update', { id: routineForm.value.id }),
            routineForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    isProcessing.value = false;
                    resetRoutineForm();
                },
                onError: () => {
                    isProcessing.value = false;
                },
            }
        );
    } else {
        router.post(
            route('calendar.planner.routines.store'),
            routineForm.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    isProcessing.value = false;
                    resetRoutineForm();
                },
                onError: () => {
                    isProcessing.value = false;
                },
            }
        );
    }
}

function deleteRoutine(routineId) {
    if (!confirm('¿Eliminar esta plantilla de rutina? Ya no se generará en días futuros.')) return;
    router.delete(route('calendar.planner.routines.destroy', { id: routineId }), {
        preserveScroll: true,
    });
}

function applyMyRoutines() {
    isProcessing.value = true;
    router.post(
        route('calendar.planner.routines.apply'),
        { plan_date: selectedDay.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            },
        }
    );
}

function loadRecommendedTemplates() {
    isProcessing.value = true;
    router.post(
        route('calendar.planner.starter-template'),
        { plan_date: selectedDay.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            },
        }
    );
}

function goToPomodoro(item) {
    router.visit(route('pomodoro.index', { task: item.title }));
}
</script>

<template>
    <Head title="Calendario & Planificador Diario — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Principal con Switcher de Vistas -->
            <BaseCard class="p-5">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="font-display text-2xl sm:text-3xl font-bold text-content-primary">Calendario & Plan Diario</h1>
                        </div>
                        <p class="mt-1 text-xs sm:text-sm text-content-secondary">
                            Ciclo {{ academicCycle }} — Vista mensual de clases, Time-Blocking de 24h y lista de rutinas diarias
                        </p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Toggle de Vistas: Mensual vs Time-Blocking -->
                        <div class="flex rounded-xl bg-surface-sunken p-1 border border-border">
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="calendarViewMode === 'timeblocking' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                                @click="calendarViewMode = 'timeblocking'"
                            >
                                ⏱️ Time-Blocking (24h)
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="calendarViewMode === 'grid' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                                @click="calendarViewMode = 'grid'"
                            >
                                📅 Vista Mensual
                            </button>
                        </div>

                        <BaseButton variant="secondary" size="sm" @click="showCourseModal = true">
                            <BookOpen :size="15" />
                            Mis Cursos
                        </BaseButton>
                    </div>
                </header>
            </BaseCard>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- VISTA 1: TIME-BLOCKING Y PLANIFICADOR DEL DÍA UNIFICADO (24H)   -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div v-if="calendarViewMode === 'timeblocking'" class="space-y-6">
                <!-- Selector de Día & Botones de Acción -->
                <BaseCard class="p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-primary/15 text-primary-strong font-bold text-lg">📅</span>
                            <div>
                                <h2 class="font-display text-xl sm:text-2xl font-bold tracking-tight text-content-primary capitalize">
                                    {{ formatSelectedDayHeader(selectedDay) }}
                                </h2>
                                <p class="text-xs text-content-secondary mt-0.5">
                                    Plan del día integrado con Time-Blocking y tus clases universitarias
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-xl border border-border bg-surface text-content-primary hover:bg-surface-raised text-xs font-semibold transition-all"
                                @click="changePlanDate(-1)"
                            >
                                ◀ Ayer
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all',
                                    selectedDay === todayDate ? 'bg-primary-strong text-on-primary-strong shadow-md shadow-primary-strong/20' : 'border border-border bg-surface text-content-primary hover:bg-surface-raised'
                                ]"
                                @click="selectDay(todayDate)"
                            >
                                Hoy
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-xl border border-border bg-surface text-content-primary hover:bg-surface-raised text-xs font-semibold transition-all"
                                @click="changePlanDate(1)"
                            >
                                Mañana ▶
                            </button>

                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-xl bg-surface-raised border border-border text-content-secondary hover:text-content-primary text-xs font-semibold transition-all flex items-center gap-1.5"
                                @click="showRoutinesModal = true"
                            >
                                ⚙️ Plantillas de Rutinas
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-xl bg-primary-strong text-on-primary-strong text-xs font-bold shadow-sm hover:opacity-90 transition-all flex items-center gap-1"
                                @click="openAddPlanModal('morning')"
                            >
                                ➕ Añadir Actividad
                            </button>
                        </div>
                    </div>
                </BaseCard>

                <!-- Barra de Métricas y Progreso del Día -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <BaseCard class="p-4 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-content-muted">Total Actividades</span>
                        <div class="font-display text-2xl font-bold text-content-primary mt-1">{{ plan.stats.total }}</div>
                        <span class="text-[11px] text-content-muted">en el checklist</span>
                    </BaseCard>

                    <div class="bg-success/15 border border-success/30 p-4 rounded-2xl flex flex-col justify-between">
                        <span class="text-xs font-semibold text-success">✅ Hechas</span>
                        <div class="font-display text-2xl font-bold text-success mt-1">{{ plan.stats.done }}</div>
                        <span class="text-[11px] text-success/80">+{{ plan.stats.done * 15 }} XP ganados</span>
                    </div>

                    <div class="bg-warning/15 border border-warning/30 p-4 rounded-2xl flex flex-col justify-between">
                        <span class="text-xs font-semibold text-warning">⏳ Postergadas</span>
                        <div class="font-display text-2xl font-bold text-warning mt-1">{{ plan.stats.postponed }}</div>
                        <span class="text-[11px] text-warning/80">movidas de bloque</span>
                    </div>

                    <div class="bg-danger/15 border border-danger/30 p-4 rounded-2xl flex flex-col justify-between">
                        <span class="text-xs font-semibold text-danger-text">❌ Saltadas</span>
                        <div class="font-display text-2xl font-bold text-danger-text mt-1">{{ plan.stats.skipped }}</div>
                        <span class="text-[11px] text-danger-text/80">con registro</span>
                    </div>

                    <BaseCard class="col-span-2 md:col-span-1 p-4 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-content-muted">Efectividad</span>
                        <div class="flex items-baseline gap-1 mt-1">
                            <span class="font-display text-2xl font-bold text-primary-strong">{{ plan.stats.completion_rate }}%</span>
                        </div>
                        <div class="w-full bg-surface-sunken rounded-full h-1.5 mt-2 overflow-hidden border border-border">
                            <div class="bg-primary-strong h-1.5 rounded-full transition-all duration-500" :style="{ width: `${plan.stats.completion_rate}%` }"></div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Banner cuando el día está limpio (0 actividades) -->
                <BaseCard v-if="plan.stats.total === 0" class="p-6 text-center border-dashed border-border/80">
                    <div class="max-w-md mx-auto space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary-strong flex items-center justify-center text-2xl mx-auto shadow-sm">
                            ✨
                        </div>
                        <h3 class="font-display font-bold text-lg text-content-primary">
                            Tu día está libre y limpio
                        </h3>
                        <p class="text-xs text-content-secondary leading-relaxed">
                            No hay actividades registradas para esta fecha. Puedes estructurar tu día manualmente o cargar tus plantillas habituales de rutina cuando gustes.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-2.5 pt-2">
                            <button
                                type="button"
                                class="px-4 py-2 rounded-xl bg-primary-strong text-on-primary-strong text-xs font-bold shadow-sm hover:opacity-90 transition-all flex items-center gap-1.5"
                                @click="openAddPlanModal('morning')"
                            >
                                ➕ Añadir Actividad Manual
                            </button>
                            <button
                                v-if="plan.routines.length > 0"
                                type="button"
                                class="px-4 py-2 rounded-xl bg-surface-raised border border-border text-primary-strong hover:bg-surface text-xs font-bold transition-all flex items-center gap-1.5"
                                :disabled="isProcessing"
                                @click="applyMyRoutines"
                            >
                                📋 Cargar Mis Plantillas ({{ plan.routines.length }})
                            </button>
                            <button
                                v-else
                                type="button"
                                class="px-4 py-2 rounded-xl bg-surface-raised border border-border text-accent hover:bg-surface text-xs font-bold transition-all flex items-center gap-1.5"
                                :disabled="isProcessing"
                                @click="loadRecommendedTemplates"
                            >
                                💡 Cargar Plantilla Recomendada
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-2 rounded-xl border border-border bg-surface text-content-secondary hover:text-content-primary text-xs font-semibold transition-all"
                                @click="showRoutinesModal = true"
                            >
                                ⚙️ Plantillas
                            </button>
                        </div>
                    </div>
                </BaseCard>

                <!-- Card Modo Enfoque: Siguiente Acción Ahora -->
                <div v-if="plan.next_action" class="relative overflow-hidden bg-surface-raised border-2 border-primary/40 p-6 rounded-3xl shadow-md">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="space-y-1.5">
                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-primary/20 text-primary-strong text-xs font-bold uppercase tracking-wider">
                                <span class="w-2 h-2 rounded-full bg-primary-strong animate-pulse"></span>
                                Tu Siguiente Acción Ahora
                            </div>
                            <h2 class="font-display text-xl md:text-2xl font-bold text-content-primary">
                                {{ plan.next_action.title }}
                            </h2>
                            <div class="flex items-center gap-3 text-xs text-content-secondary">
                                <span v-if="plan.next_action.scheduled_time" class="font-semibold text-primary-strong">
                                    🕒 {{ plan.next_action.scheduled_time }}
                                </span>
                                <span>⏱️ ~{{ plan.next_action.estimated_minutes }} min</span>
                                <span class="capitalize">🏷️ {{ plan.next_action.category }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 flex-wrap">
                            <button
                                type="button"
                                class="px-5 py-2.5 rounded-2xl bg-success hover:opacity-90 text-on-accent font-bold text-sm shadow-md active:scale-95 transition-all flex items-center gap-2"
                                @click="handleMarkDone(plan.next_action)"
                            >
                                ✅ Marcar Hecho (+15 XP)
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2.5 rounded-2xl bg-surface hover:bg-surface-raised border border-border text-content-primary font-bold text-sm transition-all"
                                @click="openPostponeModal(plan.next_action)"
                            >
                                ⏳ Postergar
                            </button>
                            <button
                                v-if="plan.next_action.category === 'estudio'"
                                type="button"
                                class="px-4 py-2.5 rounded-2xl bg-primary/15 hover:bg-primary/25 text-primary-strong border border-primary/30 font-bold text-sm transition-all"
                                @click="goToPomodoro(plan.next_action)"
                            >
                                🍅 Pomodoro
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Layout Principal: 2 Columnas (Time-Blocking 24h + Checklist de Rutinas) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Columna Izquierda: Time-Blocking Visual 24 Horas (5 columnas) -->
                    <BaseCard class="lg:col-span-5 p-5 space-y-4">
                        <div class="border-b border-border/70 pb-3">
                            <h3 class="font-display font-bold text-base text-content-primary flex items-center gap-2">
                                <span>⏱️</span> Línea de Tiempo (24h)
                            </h3>
                            <p class="text-xs text-content-secondary mt-0.5">Clases y actividades programadas por hora</p>
                        </div>

                        <div class="space-y-2 max-h-[750px] overflow-y-auto pr-1">
                            <div
                                v-for="hour in timelineHours"
                                :key="hour"
                                class="flex items-stretch gap-2.5 group"
                            >
                                <div class="w-12 shrink-0 text-right text-xs font-mono font-bold text-content-muted pt-1.5 border-r border-border/80 pr-2">
                                    {{ String(hour).padStart(2, '0') }}:00
                                </div>

                                <div
                                    class="flex-1 min-h-[46px] rounded-xl border p-2 transition-all flex flex-col justify-center gap-1"
                                    :class="[
                                        getHourSlotData(hour).sessions.length > 0
                                            ? 'bg-primary/10 border-primary/30 shadow-sm'
                                            : getHourSlotData(hour).planItems.length > 0
                                            ? 'bg-surface-raised border-border-strong'
                                            : 'bg-surface/60 border-border/50 hover:bg-surface-raised hover:border-primary/20'
                                    ]"
                                >
                                    <!-- Clases en esta hora -->
                                    <div
                                        v-for="s in getHourSlotData(hour).sessions"
                                        :key="s.id"
                                        class="flex items-center justify-between gap-1 text-xs"
                                    >
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-2 h-2 rounded-full bg-primary-strong shrink-0"></span>
                                            <span class="font-bold text-content-primary truncate">{{ s.course_name }}</span>
                                            <span class="text-[10px] text-primary-strong shrink-0">({{ formatTime12h(s.start_time) }})</span>
                                        </div>
                                        <button
                                            type="button"
                                            class="text-[10px] text-primary-strong hover:underline shrink-0"
                                            @click="openNote(s.course_id)"
                                        >
                                            📝
                                        </button>
                                    </div>

                                    <!-- Actividades del checklist en esta hora -->
                                    <div
                                        v-for="item in getHourSlotData(hour).planItems"
                                        :key="item.id"
                                        class="flex items-center justify-between gap-1 text-xs"
                                    >
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="text-xs">{{ item.status === 'done' ? '✅' : '⚪' }}</span>
                                            <span :class="['text-xs text-content-primary truncate', item.status === 'done' && 'line-through text-content-muted']">
                                                {{ item.title }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-content-muted shrink-0">{{ item.scheduled_time }}</span>
                                    </div>

                                    <!-- Espacio libre -->
                                    <div
                                        v-if="getHourSlotData(hour).isFree"
                                        class="flex items-center justify-between text-[11px] text-content-muted"
                                    >
                                        <span class="opacity-60">✨ Libre</span>
                                        <a
                                            :href="route('pomodoro.index')"
                                            class="text-[10px] px-1.5 py-0.5 rounded bg-surface-raised border border-border text-primary-strong opacity-0 group-hover:opacity-100 transition-opacity font-bold"
                                        >
                                            🍅 Pomodoro
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </BaseCard>

                    <!-- Columna Derecha: Checklist de Rutinas con 3 Estados (7 columnas) -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- 1. Rutina Matutina -->
                        <BaseCard class="p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-border/70 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🌅</span>
                                    <div>
                                        <h3 class="font-display font-bold text-base text-content-primary">Rutina Matutina</h3>
                                        <p class="text-xs text-content-secondary">Despertar, aseo, desayuno y arranque del día (06:00 - 09:00)</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                                    @click="openAddPlanModal('morning')"
                                >
                                    ➕ Añadir
                                </button>
                            </div>

                            <div v-if="plan.blocks.morning.length === 0" class="p-5 text-center text-content-muted text-xs border border-dashed border-border rounded-2xl">
                                No hay actividades matutinas. Haz clic en <strong>➕ Añadir</strong> para registrar una.
                            </div>

                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="item in plan.blocks.morning"
                                    :key="item.id"
                                    :class="[
                                        'p-3.5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3',
                                        item.status === 'done' ? 'bg-success/10 border-success/30' :
                                        item.status === 'skipped' ? 'bg-danger/10 border-danger/30 opacity-70' :
                                        item.status === 'postponed' ? 'bg-warning/10 border-warning/30' :
                                        'bg-surface border-border hover:border-primary/40'
                                    ]"
                                >
                                    <div class="flex items-start gap-2.5">
                                        <span class="text-base mt-0.5">
                                            {{ item.status === 'done' ? '✅' : item.status === 'skipped' ? '❌' : item.status === 'postponed' ? '⏳' : '⚪' }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span :class="['font-bold text-sm text-content-primary', item.status === 'done' && 'line-through text-content-muted']">
                                                    {{ item.title }}
                                                </span>
                                                <span v-if="item.scheduled_time" class="text-[11px] px-2 py-0.5 rounded-md bg-surface-raised border border-border text-primary-strong font-bold">
                                                    {{ item.scheduled_time }}
                                                </span>
                                                <span v-if="item.postponed_count > 0" class="text-[10px] px-2 py-0.5 rounded-md bg-warning/20 text-warning font-bold">
                                                    Postergada {{ item.postponed_count }}x
                                                </span>
                                                <span v-if="item.skip_reason" class="text-[10px] px-2 py-0.5 rounded-md bg-danger/20 text-danger-text">
                                                    Motivo: {{ item.skip_reason }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-content-secondary mt-0.5">~{{ item.estimated_minutes }} min • {{ item.category }}</p>
                                        </div>
                                    </div>

                                    <!-- Botones de Acción -->
                                    <div class="flex items-center gap-1.5 self-end sm:self-center">
                                        <button
                                            v-if="item.status !== 'done'"
                                            type="button"
                                            class="px-2.5 py-1 rounded-xl bg-success hover:opacity-90 text-on-accent text-xs font-bold transition-all active:scale-95"
                                            @click="handleMarkDone(item)"
                                        >
                                            ✅ Hecho
                                        </button>
                                        <button
                                            v-if="item.status !== 'skipped'"
                                            type="button"
                                            class="px-2 py-1 rounded-xl bg-surface hover:bg-danger/15 border border-border text-danger-text text-xs font-semibold transition-all"
                                            @click="openSkipModal(item)"
                                        >
                                            ❌
                                        </button>
                                        <button
                                            type="button"
                                            class="px-2 py-1 rounded-xl bg-surface hover:bg-warning/15 border border-border text-warning text-xs font-semibold transition-all"
                                            @click="openPostponeModal(item)"
                                        >
                                            ⏳
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 text-content-muted hover:text-primary-strong text-xs"
                                            title="Editar actividad"
                                            @click="openEditPlanModal(item)"
                                        >
                                            <Pencil :size="13" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 text-content-muted hover:text-danger-text text-xs"
                                            title="Eliminar"
                                            @click="deletePlanItem(item)"
                                        >
                                            <Trash2 :size="13" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </BaseCard>

                        <!-- 2. Bloque de Tarde / Foco -->
                        <BaseCard class="p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-border/70 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">☀️</span>
                                    <div>
                                        <h3 class="font-display font-bold text-base text-content-primary">Bloque de Foco y Tarde</h3>
                                        <p class="text-xs text-content-secondary">Estudio, sesiones de foco, misiones académicas y clases (09:00 - 18:00)</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                                    @click="openAddPlanModal('afternoon')"
                                >
                                    ➕ Añadir
                                </button>
                            </div>

                            <div v-if="plan.blocks.afternoon.length === 0" class="p-5 text-center text-content-muted text-xs border border-dashed border-border rounded-2xl">
                                No hay actividades para la tarde. Haz clic en <strong>➕ Añadir</strong> para registrar una.
                            </div>

                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="item in plan.blocks.afternoon"
                                    :key="item.id"
                                    :class="[
                                        'p-3.5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3',
                                        item.status === 'done' ? 'bg-success/10 border-success/30' :
                                        item.status === 'skipped' ? 'bg-danger/10 border-danger/30 opacity-70' :
                                        item.status === 'postponed' ? 'bg-warning/10 border-warning/30' :
                                        'bg-surface border-border hover:border-primary/40'
                                    ]"
                                >
                                    <div class="flex items-start gap-2.5">
                                        <span class="text-base mt-0.5">
                                            {{ item.status === 'done' ? '✅' : item.status === 'skipped' ? '❌' : item.status === 'postponed' ? '⏳' : '⚪' }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span :class="['font-bold text-sm text-content-primary', item.status === 'done' && 'line-through text-content-muted']">
                                                    {{ item.title }}
                                                </span>
                                                <span v-if="item.scheduled_time" class="text-[11px] px-2 py-0.5 rounded-md bg-surface-raised border border-border text-primary-strong font-bold">
                                                    {{ item.scheduled_time }}
                                                </span>
                                                <span v-if="item.postponed_count > 0" class="text-[10px] px-2 py-0.5 rounded-md bg-warning/20 text-warning font-bold">
                                                    Postergada {{ item.postponed_count }}x
                                                </span>
                                                <span v-if="item.skip_reason" class="text-[10px] px-2 py-0.5 rounded-md bg-danger/20 text-danger-text">
                                                    Motivo: {{ item.skip_reason }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-content-secondary mt-0.5">~{{ item.estimated_minutes }} min • {{ item.category }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 self-end sm:self-center">
                                        <button
                                            v-if="item.status !== 'done'"
                                            type="button"
                                            class="px-2.5 py-1 rounded-xl bg-success hover:opacity-90 text-on-accent text-xs font-bold transition-all active:scale-95"
                                            @click="handleMarkDone(item)"
                                        >
                                            ✅ Hecho
                                        </button>
                                        <button
                                            v-if="item.status !== 'skipped'"
                                            type="button"
                                            class="px-2 py-1 rounded-xl bg-surface hover:bg-danger/15 border border-border text-danger-text text-xs font-semibold transition-all"
                                            @click="openSkipModal(item)"
                                        >
                                            ❌
                                        </button>
                                        <button
                                            type="button"
                                            class="px-2 py-1 rounded-xl bg-surface hover:bg-warning/15 border border-border text-warning text-xs font-semibold transition-all"
                                            @click="openPostponeModal(item)"
                                        >
                                            ⏳
                                        </button>
                                        <button
                                            v-if="item.category === 'estudio'"
                                            type="button"
                                            class="px-2 py-1 rounded-xl bg-primary/15 hover:bg-primary/25 text-primary-strong border border-primary/30 text-xs font-bold"
                                            title="Abrir Pomodoro"
                                            @click="goToPomodoro(item)"
                                        >
                                            🍅
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 text-content-muted hover:text-primary-strong text-xs"
                                            title="Editar actividad"
                                            @click="openEditPlanModal(item)"
                                        >
                                            <Pencil :size="13" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 text-content-muted hover:text-danger-text text-xs"
                                            title="Eliminar"
                                            @click="deletePlanItem(item)"
                                        >
                                            <Trash2 :size="13" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </BaseCard>

                        <!-- 3. Rutina Nocturna -->
                        <BaseCard class="p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-border/70 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🌙</span>
                                    <div>
                                        <h3 class="font-display font-bold text-base text-content-primary">Rutina Nocturna y Cierre</h3>
                                        <p class="text-xs text-content-secondary">Cena, Daily Shutdown, reflexión en diario e higiene de sueño (19:00 - 23:00)</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                                    @click="openAddPlanModal('night')"
                                >
                                    ➕ Añadir
                                </button>
                            </div>

                            <div v-if="plan.blocks.night.length === 0" class="p-5 text-center text-content-muted text-xs border border-dashed border-border rounded-2xl">
                                No hay actividades nocturnas para hoy. Haz clic en <strong>➕ Añadir</strong> para registrar una.
                            </div>

                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="item in plan.blocks.night"
                                    :key="item.id"
                                    :class="[
                                        'p-3.5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3',
                                        item.status === 'done' ? 'bg-success/10 border-success/30' :
                                        item.status === 'skipped' ? 'bg-danger/10 border-danger/30 opacity-70' :
                                        item.status === 'postponed' ? 'bg-warning/10 border-warning/30' :
                                        'bg-surface border-border hover:border-primary/40'
                                    ]"
                                >
                                    <div class="flex items-start gap-2.5">
                                        <span class="text-base mt-0.5">
                                            {{ item.status === 'done' ? '✅' : item.status === 'skipped' ? '❌' : item.status === 'postponed' ? '⏳' : '⚪' }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span :class="['font-bold text-sm text-content-primary', item.status === 'done' && 'line-through text-content-muted']">
                                                    {{ item.title }}
                                                </span>
                                                <span v-if="item.scheduled_time" class="text-[11px] px-2 py-0.5 rounded-md bg-surface-raised border border-border text-primary-strong font-bold">
                                                    {{ item.scheduled_time }}
                                                </span>
                                                <span v-if="item.postponed_count > 0" class="text-[10px] px-2 py-0.5 rounded-md bg-warning/20 text-warning font-bold">
                                                    Postergada {{ item.postponed_count }}x
                                                </span>
                                                <span v-if="item.skip_reason" class="text-[10px] px-2 py-0.5 rounded-md bg-danger/20 text-danger-text">
                                                    Motivo: {{ item.skip_reason }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-content-secondary mt-0.5">~{{ item.estimated_minutes }} min • {{ item.category }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 self-end sm:self-center">
                                        <button
                                            v-if="item.status !== 'done'"
                                            type="button"
                                            class="px-2.5 py-1 rounded-xl bg-success hover:opacity-90 text-on-accent text-xs font-bold transition-all active:scale-95"
                                            @click="handleMarkDone(item)"
                                        >
                                            ✅ Hecho
                                        </button>
                                        <button
                                            v-if="item.status !== 'skipped'"
                                            type="button"
                                            class="px-2 py-1 rounded-xl bg-surface hover:bg-danger/15 border border-border text-danger-text text-xs font-semibold transition-all"
                                            @click="openSkipModal(item)"
                                        >
                                            ❌
                                        </button>
                                        <button
                                            type="button"
                                            class="px-2 py-1 rounded-xl bg-surface hover:bg-warning/15 border border-border text-warning text-xs font-semibold transition-all"
                                            @click="openPostponeModal(item)"
                                        >
                                            ⏳
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 text-content-muted hover:text-primary-strong text-xs"
                                            title="Editar actividad"
                                            @click="openEditPlanModal(item)"
                                        >
                                            <Pencil :size="13" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 text-content-muted hover:text-danger-text text-xs"
                                            title="Eliminar"
                                            @click="deletePlanItem(item)"
                                        >
                                            <Trash2 :size="13" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </BaseCard>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- VISTA 2: CUADRÍCULA MENSUAL TRADICIONAL                         -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div v-else class="space-y-6">
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

                            <!-- Indicadores Móvil -->
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

                            <!-- Escritorio -->
                            <div class="hidden sm:block">
                                <div v-if="cell.holiday" class="mt-0.5 truncate text-[9px] font-medium leading-tight text-danger" :title="cell.holiday.name">{{ cell.holiday.name }}</div>

                                <div v-if="cell.sessions.length > 0" class="mt-1 space-y-0.5">
                                    <button
                                        v-for="s in cell.sessions"
                                        :key="s.id"
                                        type="button"
                                        class="flex w-full items-center gap-1 truncate rounded border px-1 py-0.5 text-left text-[9px] font-medium leading-tight transition hover:opacity-80"
                                        :class="scheduleColorStyles[s.color] || scheduleColorStyles.primary"
                                        :title="`${s.course_name} (${formatTime12h(s.start_time)} - ${formatTime12h(s.end_time)})`"
                                        @click.stop="openNote(s.course_id)"
                                    >
                                        <BookOpen :size="9" class="shrink-0" />
                                        <span class="truncate">{{ formatTime12h(s.start_time) }} {{ s.course_name }}</span>
                                    </button>
                                </div>

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
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- MODALES DE EDICIÓN Y GESTIÓN                                      -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->

        <!-- Modal: Añadir o Editar Actividad del Día -->
        <BaseModal
            :show="showAddPlanModal || showEditPlanModal"
            :title="planItemForm.id ? '✏️ Editar Actividad del Día' : '➕ Añadir Actividad al Plan'"
            @close="showAddPlanModal = false; showEditPlanModal = false"
        >
            <form class="space-y-4" @submit.prevent="submitPlanItem">
                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Nombre / Título de la actividad</label>
                    <BaseInput v-model="planItemForm.title" placeholder="Ej. Ejercicio matutino 20 min" required />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Bloque del día</label>
                        <BaseSelect v-model="planItemForm.time_block" :options="timeBlocks" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Categoría</label>
                        <BaseSelect v-model="planItemForm.category" :options="categories" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Hora estimada (ej. 07:30)</label>
                        <BaseInput v-model="planItemForm.scheduled_time" placeholder="07:30" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Duración (minutos)</label>
                        <BaseInput v-model="planItemForm.estimated_minutes" type="number" min="1" max="480" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <BaseButton variant="secondary" type="button" @click="showAddPlanModal = false; showEditPlanModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">
                        {{ planItemForm.id ? 'Guardar Cambios' : 'Añadir al Día' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- Modal: Plantillas de Rutinas (Crear y Editar) -->
        <BaseModal :show="showRoutinesModal" title="⚙️ Plantillas de Rutinas Recurrentes" size="lg" @close="showRoutinesModal = false">
            <div class="space-y-6">
                <p class="text-xs text-content-secondary">
                    Configura las actividades automáticas que se generan cada mañana según tu horario habitual. Puedes editar el título, la hora, el bloque y los días.
                </p>

                <!-- Formulario de Plantilla (Crear / Editar) -->
                <form class="bg-surface-raised p-4 rounded-2xl border border-border space-y-3" @submit.prevent="submitRoutine">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-primary-strong">
                            {{ routineForm.id ? '✏️ Modificar Plantilla' : '➕ Nueva Plantilla de Rutina' }}
                        </h4>
                        <button
                            v-if="routineForm.id"
                            type="button"
                            class="text-xs text-content-muted hover:text-content-primary underline"
                            @click="resetRoutineForm"
                        >
                            Cancelar edición
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-content-secondary mb-0.5">Título de la rutina</label>
                            <BaseInput v-model="routineForm.title" placeholder="Ej. Aseo y cepillado dental" required />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-content-secondary mb-0.5">Bloque</label>
                            <BaseSelect v-model="routineForm.time_block" :options="timeBlocks" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-content-secondary mb-0.5">Categoría</label>
                            <BaseSelect v-model="routineForm.category" :options="categories" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-content-secondary mb-0.5">Hora (ej. 07:00)</label>
                            <BaseInput v-model="routineForm.scheduled_time" placeholder="07:00" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-content-secondary mb-0.5">Minutos</label>
                            <BaseInput v-model="routineForm.estimated_minutes" type="number" min="1" max="480" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <BaseButton variant="primary" size="sm" type="submit" :disabled="isProcessing">
                            {{ routineForm.id ? 'Actualizar Plantilla' : 'Guardar Plantilla' }}
                        </BaseButton>
                    </div>
                </form>

                <!-- Lista de Plantillas Existentes -->
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    <div
                        v-for="routine in plan.routines"
                        :key="routine.id"
                        class="flex items-center justify-between p-3 rounded-xl bg-surface border border-border/70 text-sm hover:border-primary/40 transition-all"
                    >
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">{{ routine.time_block === 'morning' ? '🌅' : routine.time_block === 'afternoon' ? '☀️' : '🌙' }}</span>
                            <div>
                                <span class="font-bold text-content-primary">{{ routine.title }}</span>
                                <span class="text-xs text-content-secondary block">
                                    {{ routine.scheduled_time ? '🕒 ' + routine.scheduled_time : 'Sin hora fija' }} • ~{{ routine.estimated_minutes }} min • {{ routine.category }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg bg-surface-raised border border-border text-xs font-bold text-primary-strong hover:bg-surface"
                                @click="openEditRoutine(routine)"
                            >
                                ✏️ Editar
                            </button>
                            <button
                                type="button"
                                class="text-xs text-danger-text hover:opacity-80 font-bold p-1"
                                @click="deleteRoutine(routine.id)"
                            >
                                <Trash2 :size="14" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </BaseModal>

        <!-- Modal: Marcar como No Hecho (Saltar con motivo) -->
        <BaseModal :show="showSkipModal" title="❌ Registrar Motivo de Salto" @close="showSkipModal = false">
            <div class="space-y-4">
                <p class="text-sm text-content-secondary">
                    Selecciona el motivo para entender mejor tus patrones de energía y bienestar:
                </p>
                <div class="space-y-2">
                    <label
                        v-for="reason in skipReasons"
                        :key="reason.value"
                        :class="[
                            'flex items-center gap-3 p-3 rounded-2xl border cursor-pointer transition-all',
                            skipReason === reason.value ? 'bg-primary/15 border-primary text-primary-strong font-bold' : 'bg-surface border-border text-content-primary hover:bg-surface-raised'
                        ]"
                    >
                        <input
                            v-model="skipReason"
                            type="radio"
                            :value="reason.value"
                            class="text-primary-strong focus:ring-primary h-4 w-4"
                        />
                        <span class="text-sm">{{ reason.label }}</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <BaseButton variant="secondary" @click="showSkipModal = false">Cancelar</BaseButton>
                    <BaseButton variant="danger" :disabled="isProcessing" @click="confirmSkip">Guardar Registro</BaseButton>
                </div>
            </div>
        </BaseModal>

        <!-- Modal: Postergar Actividad -->
        <BaseModal :show="showPostponeModal" title="⏳ Postergar Actividad" @close="showPostponeModal = false">
            <div class="space-y-4">
                <p class="text-sm text-content-secondary">
                    ¿A qué bloque del día prefieres mover esta actividad?
                </p>
                <div class="space-y-2">
                    <label
                        v-for="block in timeBlocks"
                        :key="block.value"
                        :class="[
                            'flex items-center gap-3 p-3 rounded-2xl border cursor-pointer transition-all',
                            postponeBlock === block.value ? 'bg-warning/20 border-warning text-warning font-bold' : 'bg-surface border-border text-content-primary hover:bg-surface-raised'
                        ]"
                    >
                        <input
                            v-model="postponeBlock"
                            type="radio"
                            :value="block.value"
                            class="text-warning focus:ring-warning h-4 w-4"
                        />
                        <span class="text-sm">{{ block.label }}</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <BaseButton variant="secondary" @click="showPostponeModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" :disabled="isProcessing" @click="confirmPostpone">Postergar Ahora</BaseButton>
                </div>
            </div>
        </BaseModal>

        <!-- Modal de Gestión de Cursos -->
        <BaseModal :show="showCourseModal" title="Gestión de Cursos y Horarios" size="lg" @close="showCourseModal = false">
            <div v-if="!showCourseForm" class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-content-secondary">Cursos registrados para el ciclo actual con sus sesiones semanales.</p>
                    <BaseButton variant="primary" size="sm" @click="openCreateCourseForm">
                        <Plus :size="14" /> Agregar Curso
                    </BaseButton>
                </div>

                <div v-if="courses.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-xs text-content-muted">
                    No tienes cursos registrados. Agrega tus asignaturas universitarias para visualizarlas en el Time-Blocking.
                </div>

                <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                    <div
                        v-for="c in courses"
                        :key="c.id"
                        class="flex items-center justify-between p-3.5 rounded-xl bg-surface border border-border/80"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="scheduleColorStyles[c.color] || scheduleColorStyles.primary"></span>
                                <h4 class="font-bold text-sm text-content-primary">{{ c.name }}</h4>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-content-secondary mt-1 flex-wrap">
                                <span v-for="s in c.sessions" :key="s.id" class="rounded bg-surface-sunken px-1.5 py-0.5 text-[11px]">
                                    {{ dayNames[s.day_of_week - 1] }} {{ formatTime12h(s.start_time) }}-{{ formatTime12h(s.end_time) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="px-2.5 py-1 rounded-lg bg-surface-raised border border-border text-xs font-semibold text-content-secondary hover:text-primary-strong flex items-center gap-1 transition-all"
                                title="Ver y editar apuntes del curso"
                                @click="openNote(c.id)"
                            >
                                <NotebookText :size="13" />
                                <span>Apuntes</span>
                            </button>
                            <button
                                type="button"
                                class="p-1.5 text-content-muted hover:text-primary-strong rounded-lg hover:bg-surface-raised transition-all"
                                title="Editar curso"
                                @click="openEditCourseForm(c)"
                            >
                                <Pencil :size="14" />
                            </button>
                            <button
                                type="button"
                                class="p-1.5 text-content-muted hover:text-danger-text rounded-lg hover:bg-surface-raised transition-all"
                                title="Eliminar curso"
                                @click="deleteCourse(c.id)"
                            >
                                <Trash2 :size="14" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Curso -->
            <form v-else class="space-y-4" @submit.prevent="submitCourse">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Nombre del Curso</label>
                        <BaseInput v-model="courseForm.name" placeholder="Ej. Cálculo Multivariable" required />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Color de identificación</label>
                        <BaseSelect v-model="courseForm.color" :options="[
                            { value: 'primary', label: 'Primario' },
                            { value: 'accent', label: 'Acento' },
                            { value: 'success', label: 'Éxito' },
                            { value: 'warning', label: 'Alerta' },
                            { value: 'secondary', label: 'Secundario' }
                        ]" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-content-secondary">Sesiones semanales</label>
                        <button type="button" class="text-xs text-primary-strong font-bold hover:underline" @click="addSession">
                            + Añadir otra sesión
                        </button>
                    </div>

                    <div
                        v-for="(session, idx) in courseForm.sessions"
                        :key="idx"
                        class="grid grid-cols-12 gap-2 p-2.5 rounded-xl bg-surface border border-border items-center"
                    >
                        <div class="col-span-4">
                            <BaseSelect
                                :model-value="session.day_of_week"
                                :options="dayNames.map((d, i) => ({ value: i + 1, label: d }))"
                                @update:model-value="val => setSessionDay(idx, val)"
                            />
                        </div>
                        <div class="col-span-3">
                            <BaseInput v-model="session.start_time" placeholder="08:00" />
                        </div>
                        <div class="col-span-3">
                            <BaseInput v-model="session.end_time" placeholder="10:00" />
                        </div>
                        <div class="col-span-2 text-right">
                            <button
                                v-if="courseForm.sessions.length > 1"
                                type="button"
                                class="text-xs text-danger-text hover:opacity-80 p-1"
                                @click="removeSession(idx)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showCourseForm = false">Volver</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="courseForm.processing">
                        {{ editingCourseId ? 'Guardar Curso' : 'Registrar Curso' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- Modal de Apuntes -->
        <NoteEditorModal
            :show="showNoteModal"
            :course="selectedCourse"
            @close="closeNote"
        />
    </AppLayout>
</template>
