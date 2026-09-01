<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
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
    Network,
} from '@lucide/vue';

const props = defineProps({
    missionsByDate: { type: Object, default: () => ({}) },
    eventsByDate:   { type: Object, default: () => ({}) },
    personalEvents: { type: Array,  default: () => [] },
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

const [ty, tm, td] = (props.todayDate || '').split('-').map(Number);
const currentMonth = tm || (new Date().getMonth() + 1);
const currentYear  = ty || new Date().getFullYear();

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
            personalEvents: dateStr && props.eventsByDate[dateStr] ? props.eventsByDate[dateStr] : [],
            sessions:    daySessions,
            isToday:     dateStr === props.todayDate,
            isPast:      dateStr !== null && dateStr < props.todayDate,
            hasActivity: dateStr && (
                (props.missionsByDate[dateStr]?.length ?? 0) > 0
                || (props.eventsByDate[dateStr]?.length ?? 0) > 0
                || holiday
                || daySessions.length > 0
            ),
        });
    }
    return days;
});

const layerFilters = ref({
    classes: true,
    missions: true,
    personal: true,
});

const monthSummaryStats = computed(() => {
    let classes = 0;
    let missions = 0;
    let personal = 0;
    for (const d of calendarDays.value) {
        if (d.day) {
            classes += d.sessions?.length || 0;
            missions += d.missions?.length || 0;
            personal += d.personalEvents?.length || 0;
        }
    }
    return { classes, missions, personal };
});

function openAddEventOnDate(dateStr) {
    if (!dateStr) return;
    eventForm.event_date = dateStr;
    showPersonalEventModal.value = true;
}

const showPersonalEventModal = ref(false);
const eventForm = useForm({
    title: '',
    description: '',
    type: 'birthday',
    event_date: props.todayDate,
    start_time: '',
    end_time: '',
    color: 'primary',
});

const eventTypeIcons = {
    birthday: '🎂',
    appointment: '🩺',
    meeting: '👥',
    work: '💼',
    social: '🥂',
    reminder: '⏰',
    other: '📌',
};

async function createPersonalEvent() {
    if (!eventForm.title || !eventForm.event_date) return;
    try {
        await axios.post(route('calendar.personal-events.store'), eventForm.data());
        showPersonalEventModal.value = false;
        eventForm.reset();
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('Error al crear evento: ' + (e.response?.data?.message || e.message));
    }
}

function goToMonth(m, y) {
    router.get(
        route('calendar.index', {
            month: m,
            year: y,
            date: selectedDay.value,
            view: calendarViewMode.value,
        }),
        { preserveScroll: true, preserveState: true }
    );
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
    selectDay(props.todayDate);
    goToMonth(currentMonth, currentYear);
}

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
    easy:   'bg-success/15 text-content-primary border border-success/40',
    medium: 'bg-primary/15 text-content-primary border border-primary/40',
    hard:   'bg-danger/15 text-danger-text border border-danger/40',
};

const scheduleColorStyles = {
    primary:   'bg-primary/10 text-content-primary border-primary/40',
    accent:    'bg-secondary/15 text-content-primary border-secondary/40',
    success:   'bg-success/15 text-content-primary border-success/40',
    warning:   'bg-warning/15 text-content-primary border-warning/40',
    secondary: 'bg-surface-raised text-content-secondary border-border',
};

// ── Cursos ─────────────────────────────────────────────────────────────────
const showCourseModal   = ref(false);
const showCourseForm    = ref(false);
const editingCourseId   = ref(null);

const courseForm = useForm({
    name:      '',
    professor: '',
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
    courseForm.professor = '';
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
    courseForm.professor = course.professor || '';
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

// ── Apuntes Oficiales ───────────────────────────────────────────────────────
function openNote(courseId) {
    router.visit(route('notes.index', { course_id: courseId }));
}

// ── Selección de Día & Modo de Vista ────────────────────────────────────────
const getInitialViewMode = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const viewFromUrl = urlParams.get('view');
        if (viewFromUrl === 'grid' || viewFromUrl === 'timeblocking') {
            return viewFromUrl;
        }
        const savedView = localStorage.getItem('epycus_calendar_view');
        if (savedView === 'grid' || savedView === 'timeblocking') {
            return savedView;
        }
    }
    return 'timeblocking';
};

const selectedDay = ref(props.selectedDate || props.todayDate);
const calendarViewMode = ref(getInitialViewMode()); // 'grid' | 'timeblocking'

function setCalendarViewMode(mode) {
    calendarViewMode.value = mode;
    if (typeof window !== 'undefined') {
        try {
            localStorage.setItem('epycus_calendar_view', mode);
            const url = new URL(window.location.href);
            url.searchParams.set('view', mode);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }
}

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    if (viewParam === 'grid' || viewParam === 'timeblocking') {
        calendarViewMode.value = viewParam;
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
        router.visit(
            route('calendar.index', {
                date: dateStr,
                month: props.month,
                year: props.year,
                view: calendarViewMode.value,
            }),
            {
                preserveScroll: true,
                preserveState: true,
            }
        );
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
    if (!selectedDay.value) return;
    const [y, m, d] = selectedDay.value.split('-').map(Number);
    const dateObj = new Date(y, m - 1, d + deltaDays);
    const ny = dateObj.getFullYear();
    const nm = String(dateObj.getMonth() + 1).padStart(2, '0');
    const nd = String(dateObj.getDate()).padStart(2, '0');
    selectDay(`${ny}-${nm}-${nd}`);
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
    { value: 'personal', label: 'Cuidado Personal' },
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
                        <BaseButton
                            variant="secondary"
                            size="sm"
                            @click="showPersonalEventModal = true"
                        >
                            <span>🎂</span> + Evento Personal
                        </BaseButton>

                        <!-- Toggle de Vistas: Mensual vs Time-Blocking -->
                        <div class="flex rounded-xl bg-surface-sunken p-1 border border-border">
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="calendarViewMode === 'timeblocking' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                                @click="setCalendarViewMode('timeblocking')"
                            >
                                ⏱️ Time-Blocking (24h)
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="calendarViewMode === 'grid' ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                                @click="setCalendarViewMode('grid')"
                            >
                                📅 Vista Mensual
                            </button>
                        </div>
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
                                Ayer
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
                                Mañana
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
                        <span class="text-xs font-semibold text-content-primary">✅ Hechas</span>
                        <div class="font-display text-2xl font-bold text-content-primary mt-1">{{ plan.stats.done }}</div>
                        <span class="text-[11px] text-content-secondary">+{{ plan.stats.done * 15 }} XP ganados</span>
                    </div>

                    <div class="bg-warning/15 border border-warning/30 p-4 rounded-2xl flex flex-col justify-between">
                        <span class="text-xs font-semibold text-content-primary">⏳ Postergadas</span>
                        <div class="font-display text-2xl font-bold text-content-primary mt-1">{{ plan.stats.postponed }}</div>
                        <span class="text-[11px] text-content-secondary">movidas de bloque</span>
                    </div>

                    <div class="bg-danger/15 border border-danger/30 p-4 rounded-2xl flex flex-col justify-between">
                        <span class="text-xs font-semibold text-danger-text">❌ Saltadas</span>
                        <div class="font-display text-2xl font-bold text-danger-text mt-1">{{ plan.stats.skipped }}</div>
                        <span class="text-[11px] text-content-secondary">con registro</span>
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
                                class="px-4 py-2 rounded-xl bg-surface-raised border border-border text-primary-strong hover:bg-surface text-xs font-bold transition-all flex items-center gap-1.5"
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
                                        <span class="opacity-60">Libre</span>
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
            <!-- VISTA 2: CUADRÍCULA MENSUAL MEJORADA (GLASS & NEU CARDS)         -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div v-else class="space-y-6">
                <!-- Barra de Navegación del Mes & Filtros -->
                <BaseCard class="p-5">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Navegación de Meses -->
                        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                            <div class="flex items-center rounded-xl bg-surface-sunken p-1 border border-border">
                                <button
                                    type="button"
                                    class="p-2 rounded-lg text-content-secondary hover:text-content-primary hover:bg-surface transition-all active:scale-95"
                                    title="Mes anterior"
                                    @click="prevMonth"
                                >
                                    <ChevronLeft :size="18" />
                                </button>
                                <button
                                    type="button"
                                    class="px-3.5 py-1.5 rounded-lg font-display text-base sm:text-lg font-bold text-content-primary hover:text-primary-strong transition-colors flex items-center gap-1.5"
                                    @click="openMonthPicker"
                                >
                                    <span>📅</span>
                                    <span>{{ monthNames[month - 1] }} {{ year }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="p-2 rounded-lg text-content-secondary hover:text-content-primary hover:bg-surface transition-all active:scale-95"
                                    title="Mes siguiente"
                                    @click="nextMonth"
                                >
                                    <ChevronRight :size="18" />
                                </button>
                            </div>

                            <button
                                v-if="month !== currentMonth || year !== currentYear"
                                type="button"
                                class="px-3 py-1.5 rounded-xl border border-primary-strong/40 bg-primary/10 text-primary-strong text-xs font-bold hover:bg-primary/20 transition-all flex items-center gap-1.5 shadow-sm"
                                @click="goToToday"
                            >
                                <span class="h-2 w-2 rounded-full bg-primary-strong animate-pulse"></span>
                                Ir a Hoy
                            </button>
                        </div>

                        <!-- Filtros de Capas Rápidos -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-semibold text-content-muted hidden sm:inline">Ver:</span>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border"
                                :class="layerFilters.classes ? 'bg-primary/15 border-primary-strong/40 text-primary-strong shadow-xs' : 'bg-surface border-border text-content-muted hover:text-content-secondary'"
                                @click="layerFilters.classes = !layerFilters.classes"
                            >
                                <span>🎒</span> Clases
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border"
                                :class="layerFilters.missions ? 'bg-success/15 border-success/40 text-success shadow-xs' : 'bg-surface border-border text-content-muted hover:text-content-secondary'"
                                @click="layerFilters.missions = !layerFilters.missions"
                            >
                                <span>🎯</span> Misiones
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border"
                                :class="layerFilters.personal ? 'bg-primary/15 border-primary-strong/40 text-primary-strong shadow-xs' : 'bg-surface border-border text-content-muted hover:text-content-secondary'"
                                @click="layerFilters.personal = !layerFilters.personal"
                            >
                                <span>🎂</span> Eventos
                            </button>

                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-xl bg-primary-strong text-on-primary-strong text-xs font-bold shadow-sm hover:opacity-90 transition-all flex items-center gap-1 ml-auto lg:ml-0"
                                @click="openAddEventOnDate(selectedDay || todayDate)"
                            >
                                <span>➕</span> Evento
                            </button>
                        </div>
                    </div>

                    <!-- Selector emergente de Mes / Año -->
                    <div v-if="showMonthPicker" class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl bg-surface-sunken p-4 border border-border">
                        <span class="text-xs font-bold text-content-primary">Seleccionar período:</span>
                        <select v-model="pickerMonth" class="rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-semibold text-content-primary outline-none focus:ring-2 focus:ring-primary-strong">
                            <option v-for="(name, i) in monthNames" :key="i" :value="i + 1">{{ name }}</option>
                        </select>
                        <select v-model="pickerYear" class="rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-semibold text-content-primary outline-none focus:ring-2 focus:ring-primary-strong">
                            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                        </select>
                        <BaseButton variant="primary" size="sm" @click="applyMonthPicker">Aplicar</BaseButton>
                        <button type="button" class="text-xs text-content-muted hover:text-content-primary transition-colors" @click="showMonthPicker = false">Cancelar</button>
                    </div>

                    <!-- Mini resumen de actividades del mes -->
                    <div class="mt-4 pt-3 border-t border-border flex items-center gap-3 sm:gap-6 flex-wrap text-xs text-content-secondary">
                        <div class="flex items-center gap-1.5">
                            <span class="p-1 rounded-md bg-primary/15 text-primary-strong font-bold">🎒</span>
                            <span><strong>{{ monthSummaryStats.classes }}</strong> clases programadas</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="p-1 rounded-md bg-success/15 text-content-primary font-bold">🎯</span>
                            <span><strong>{{ monthSummaryStats.missions }}</strong> misiones / entregables</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="p-1 rounded-md bg-primary/15 text-primary-strong font-bold">🎂</span>
                            <span><strong>{{ monthSummaryStats.personal }}</strong> eventos personales</span>
                        </div>
                    </div>
                </BaseCard>

                <!-- Cuadrícula Mensual Principal -->
                <BaseCard class="p-3 sm:p-5 overflow-hidden">
                    <!-- Cabecera de Días de la Semana -->
                    <div class="grid grid-cols-7 gap-1.5 sm:gap-2 mb-2">
                        <div
                            v-for="(d, idx) in ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB', 'DOM']"
                            :key="d"
                            class="py-2 text-center text-[11px] sm:text-xs font-extrabold tracking-wider rounded-xl transition-colors"
                            :class="idx >= 5 ? 'bg-primary/10 text-primary-strong' : 'bg-surface-sunken/80 text-content-secondary'"
                        >
                            {{ d }}
                        </div>
                    </div>

                    <!-- Grilla de Celdas de Días -->
                    <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
                        <div
                            v-for="(cell, i) in calendarDays"
                            :key="i"
                            class="group relative flex flex-col justify-between rounded-xl sm:rounded-2xl border p-1.5 sm:p-2.5 transition-all duration-200 cursor-pointer min-h-[85px] sm:min-h-[135px]"
                            :class="[
                                cell.date ? 'bg-surface hover:bg-surface-raised hover:shadow-md hover:border-primary-strong/40' : 'bg-surface-sunken/20 border-transparent pointer-events-none opacity-20',
                                cell.date && cell.date === selectedDay ? 'ring-2 ring-primary-strong bg-primary/5 border-primary-strong/50 shadow-sm' : 'border-border/70',
                                cell.isPast && !cell.hasActivity && cell.date !== selectedDay ? 'opacity-40 hover:opacity-100' : ''
                            ]"
                            @click="selectDay(cell.date)"
                        >
                            <!-- Cabecera de la celda: Número de día + Feriado + Botón rápido "+" -->
                            <div class="flex items-center justify-between gap-1">
                                <div class="flex items-center gap-1.5">
                                    <!-- Número del Día -->
                                    <div
                                        v-if="cell.day"
                                        class="flex items-center justify-center text-xs sm:text-sm font-bold transition-all"
                                        :class="[
                                            cell.isToday
                                                ? 'h-6 w-6 sm:h-7 sm:w-7 rounded-full bg-primary-strong text-on-primary-strong font-black shadow-md ring-2 ring-primary/40'
                                                : (cell.date === selectedDay ? 'text-primary-strong font-black text-sm sm:text-base' : (cell.isPast ? 'text-content-muted' : 'text-content-primary'))
                                        ]"
                                    >
                                        {{ cell.day }}
                                    </div>

                                    <!-- Tag Feriado Oficial -->
                                    <span
                                        v-if="cell.holiday"
                                        class="rounded-lg bg-danger/15 border border-danger/30 px-1.5 py-0.5 text-[9px] font-extrabold text-danger-text truncate max-w-[70px] sm:max-w-[100px]"
                                        :title="cell.holiday.name"
                                    >
                                        🎉 {{ cell.holiday.name }}
                                    </span>
                                </div>

                                <!-- Botón "+" al hacer hover en escritorio -->
                                <button
                                    v-if="cell.date"
                                    type="button"
                                    class="hidden sm:flex h-5 w-5 items-center justify-center rounded-md bg-surface-sunken text-content-muted hover:text-primary-strong hover:bg-primary/20 opacity-0 group-hover:opacity-100 transition-all text-xs font-bold"
                                    title="Añadir evento a este día"
                                    @click.stop="openAddEventOnDate(cell.date)"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Indicadores Compactos para Móviles -->
                            <div class="mt-1 flex items-center justify-center gap-1 sm:hidden">
                                <span v-if="cell.holiday" class="h-1.5 w-1.5 rounded-full bg-danger"></span>
                                <span
                                    v-for="s in cell.sessions.slice(0, 2)"
                                    :key="'ms-' + s.id"
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="s.color === 'accent' ? 'bg-secondary' : (s.color === 'success' ? 'bg-success' : (s.color === 'warning' ? 'bg-warning' : 'bg-primary-strong'))"
                                ></span>
                                <span v-if="cell.missions.length > 0" class="h-1.5 w-1.5 rounded-full bg-success"></span>
                                <span v-if="cell.personalEvents.length > 0" class="h-1.5 w-1.5 rounded-full bg-primary-strong"></span>
                            </div>

                            <!-- Contenido Completo para Pantallas Medianas / Grandes -->
                            <div class="hidden sm:flex flex-col gap-1 mt-1.5 overflow-hidden">
                                <!-- Clases Universitarias -->
                                <template v-if="layerFilters.classes && cell.sessions.length > 0">
                                    <button
                                        v-for="s in cell.sessions.slice(0, 2)"
                                        :key="'cs-' + s.id"
                                        type="button"
                                        class="flex w-full items-center gap-1.5 truncate rounded-lg border px-1.5 py-1 text-left text-[10px] font-semibold leading-tight transition-all hover:brightness-105 shadow-2xs"
                                        :class="scheduleColorStyles[s.color] || scheduleColorStyles.primary"
                                        :title="`${s.course_name} (${formatTime12h(s.start_time)} - ${formatTime12h(s.end_time)})`"
                                        @click.stop="openNote(s.course_id)"
                                    >
                                        <BookOpen :size="10" class="shrink-0" />
                                        <span class="truncate">{{ formatTime12h(s.start_time) }} {{ s.course_name }}</span>
                                    </button>
                                </template>

                                <!-- Misiones / Entregables -->
                                <template v-if="layerFilters.missions && cell.missions.length > 0">
                                    <a
                                        v-for="m in cell.missions.slice(0, 2)"
                                        :key="'cm-' + m.id"
                                        :href="route('missions.show', { id: m.id })"
                                        class="flex items-center gap-1 truncate rounded-lg px-1.5 py-1 text-[10px] font-medium leading-tight transition-all hover:opacity-85 border border-border bg-surface-raised"
                                        :class="m.is_completed ? 'text-content-muted line-through opacity-60' : 'text-content-primary'"
                                        :title="m.title"
                                        @click.stop
                                    >
                                        <Target :size="10" class="shrink-0 text-success" />
                                        <span class="truncate">{{ m.title }}</span>
                                    </a>
                                </template>

                                <!-- Eventos Personales -->
                                <template v-if="layerFilters.personal && cell.personalEvents.length > 0">
                                    <div
                                        v-for="e in cell.personalEvents.slice(0, 1)"
                                        :key="'pe-' + e.id"
                                        class="flex items-center gap-1 truncate rounded-lg border border-border bg-surface-raised px-1.5 py-1 text-left text-[10px] font-bold text-content-primary truncate"
                                        :title="`${e.title} (${e.start_time ? formatTime12h(e.start_time) : 'Todo el día'})`"
                                    >
                                        <span>{{ eventTypeIcons[e.type] || '📌' }}</span>
                                        <span class="truncate">{{ e.title }}</span>
                                    </div>
                                </template>

                                <!-- Contador de elementos adicionales (+X más) -->
                                <div
                                    v-if="(cell.sessions.length + cell.missions.length + cell.personalEvents.length) > 3"
                                    class="text-[9px] font-bold text-content-muted px-1 py-0.5 rounded-md bg-surface-sunken self-start"
                                >
                                    +{{ (cell.sessions.length + cell.missions.length + cell.personalEvents.length) - 3 }} más
                                </div>
                            </div>
                        </div>
                    </div>
                </BaseCard>

                <!-- Panel Inspector del Día Seleccionado (Al hacer clic en cualquier día) -->
                <BaseCard v-if="selectedDayData" class="p-5 border-l-4 border-l-primary-strong">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-border">
                        <div class="flex items-center gap-3">
                            <span class="p-2.5 rounded-2xl bg-primary/15 text-primary-strong text-xl font-bold">📌</span>
                            <div>
                                <h3 class="font-display text-lg sm:text-xl font-bold text-content-primary capitalize">
                                    {{ formatSelectedDayHeader(selectedDay) }}
                                </h3>
                                <p class="text-xs text-content-secondary mt-0.5">
                                    Resumen de clases, tareas y eventos programados para este día
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <button
                                type="button"
                                class="px-4 py-2 rounded-xl bg-primary-strong text-on-primary-strong text-xs font-bold shadow-sm hover:opacity-90 transition-all flex items-center gap-1.5"
                                @click="setCalendarViewMode('timeblocking')"
                            >
                                <span>⏱️</span> Ver Time-Blocking 24h
                            </button>
                            <button
                                type="button"
                                class="px-3.5 py-2 rounded-xl bg-surface-raised border border-border text-content-primary hover:bg-surface text-xs font-bold transition-all flex items-center gap-1.5"
                                @click="openAddEventOnDate(selectedDay)"
                            >
                                <span>🎂</span> + Evento
                            </button>
                        </div>
                    </div>

                    <!-- Columnas de Detalle del Día -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4">
                        <!-- Clases del Día -->
                        <div class="p-3.5 rounded-2xl bg-surface-raised border border-border">
                            <h4 class="font-bold text-xs text-content-primary flex items-center gap-1.5 mb-2.5">
                                <span>🎒</span> Clases Universitarias ({{ selectedDayData.sessions.length }})
                            </h4>
                            <div v-if="selectedDayData.sessions.length > 0" class="space-y-2">
                                <div
                                    v-for="s in selectedDayData.sessions"
                                    :key="'ds-' + s.id"
                                    class="p-2.5 rounded-xl border bg-surface flex flex-col gap-1 shadow-2xs"
                                    :class="scheduleColorStyles[s.color] || scheduleColorStyles.primary"
                                >
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span>{{ s.course_name }}</span>
                                        <span class="text-[11px] font-mono">{{ formatTime12h(s.start_time) }} - {{ formatTime12h(s.end_time) }}</span>
                                    </div>
                                    <span v-if="s.classroom" class="text-[11px] text-content-secondary">📍 Aula / Sala: {{ s.classroom }}</span>
                                </div>
                            </div>
                            <p v-else class="text-xs text-content-muted italic">Sin clases programadas este día.</p>
                        </div>

                        <!-- Misiones del Día -->
                        <div class="p-3.5 rounded-2xl bg-surface-raised border border-border">
                            <h4 class="font-bold text-xs text-content-primary flex items-center gap-1.5 mb-2.5">
                                <span>🎯</span> Misiones / Tareas ({{ selectedDayData.missions.length }})
                            </h4>
                            <div v-if="selectedDayData.missions.length > 0" class="space-y-2">
                                <a
                                    v-for="m in selectedDayData.missions"
                                    :key="'dm-' + m.id"
                                    :href="route('missions.show', { id: m.id })"
                                    class="p-2.5 rounded-xl border border-border bg-surface flex items-center justify-between text-xs hover:border-primary-strong transition-all shadow-2xs block"
                                >
                                    <span :class="m.is_completed ? 'line-through text-content-muted' : 'font-semibold text-content-primary'">{{ m.title }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-md" :class="difficultyStyles[m.difficulty] || 'bg-surface-sunken text-content-secondary'">
                                        {{ m.difficulty }}
                                    </span>
                                </a>
                            </div>
                            <p v-else class="text-xs text-content-muted italic">No hay entregas pendientes para este día.</p>
                        </div>

                        <!-- Eventos Personales del Día -->
                        <div class="p-3.5 rounded-2xl bg-surface-raised border border-border">
                            <h4 class="font-bold text-xs text-content-primary flex items-center gap-1.5 mb-2.5">
                                <span>🎂</span> Eventos Personales ({{ selectedDayData.personalEvents.length }})
                            </h4>
                            <div v-if="selectedDayData.personalEvents.length > 0" class="space-y-2">
                                <div
                                    v-for="e in selectedDayData.personalEvents"
                                    :key="'de-' + e.id"
                                    class="p-2.5 rounded-xl border border-border bg-surface flex flex-col gap-0.5 shadow-2xs"
                                >
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-content-primary">
                                        <span>{{ eventTypeIcons[e.type] || '📌' }}</span>
                                        <span>{{ e.title }}</span>
                                    </div>
                                    <span v-if="e.start_time" class="text-[11px] text-content-secondary font-mono">
                                        ⏰ {{ formatTime12h(e.start_time) }} {{ e.end_time ? '- ' + formatTime12h(e.end_time) : '' }}
                                    </span>
                                </div>
                            </div>
                            <p v-else class="text-xs text-content-muted italic">Sin eventos personales agendados.</p>
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
                        <label class="block text-xs font-bold text-content-secondary mb-1">Profesor / Docente (Opcional)</label>
                        <BaseInput v-model="courseForm.professor" placeholder="Ej. Juan Pérez" />
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

        <!-- Modal de Evento Personal (Fase 4) -->
        <BaseModal :show="showPersonalEventModal" title="Nuevo Evento Personal o Cita" @close="showPersonalEventModal = false">
            <form class="space-y-4" @submit.prevent="createPersonalEvent">
                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">Título del Evento</label>
                    <BaseInput v-model="eventForm.title" placeholder="Ej: Cumpleaños de Ana / Cita Médica / Entrevista laboral" required />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-primary mb-1">Tipo de Evento</label>
                        <select v-model="eventForm.type" class="w-full text-xs rounded-xl border border-border bg-surface px-3 py-2 text-content-primary outline-none">
                            <option value="birthday">🎂 Cumpleaños</option>
                            <option value="appointment">🩺 Cita Médica / Trámite</option>
                            <option value="meeting">👥 Reunión / Grupo</option>
                            <option value="work">💼 Trabajo / Freelance</option>
                            <option value="social">🥂 Salida / Evento Social</option>
                            <option value="reminder">⏰ Recordatorio</option>
                            <option value="other">📌 Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-primary mb-1">Fecha</label>
                        <input type="date" v-model="eventForm.event_date" required class="w-full text-xs rounded-xl border border-border bg-surface px-3 py-2 text-content-primary outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-primary mb-1">Hora Inicio (Opcional)</label>
                        <input type="time" v-model="eventForm.start_time" class="w-full text-xs rounded-xl border border-border bg-surface px-3 py-2 text-content-primary outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-primary mb-1">Hora Fin (Opcional)</label>
                        <input type="time" v-model="eventForm.end_time" class="w-full text-xs rounded-xl border border-border bg-surface px-3 py-2 text-content-primary outline-none" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">Descripción / Notas (Opcional)</label>
                    <textarea v-model="eventForm.description" rows="2" placeholder="Detalles, lugar, enlace..." class="w-full text-xs rounded-xl border border-border bg-surface-sunken p-3 text-content-primary outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showPersonalEventModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit">Guardar Evento</BaseButton>
                </div>
            </form>
        </BaseModal>

    </AppLayout>
</template>
