<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseBadge from '@/Components/ui/BaseBadge.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import { triggerConfetti, triggerHapticVibration } from '@/utils/celebration';

const props = defineProps({
    plan: { type: Object, required: true },
    currentDate: { type: String, required: true },
    avatarStyle: { type: String, default: 'base' },
    avatarGender: { type: String, default: 'm' },
    progress: { type: Object, default: () => ({ total_xp: 0, phase: 1, streak: 0 }) },
    xp_awarded: { type: Number, default: 0 },
});

// Modales
const showAddModal = ref(false);
const showSkipModal = ref(false);
const showPostponeModal = ref(false);
const showRoutinesModal = ref(false);
const selectedItem = ref(null);

// Formularios
const newItemForm = ref({
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
    title: '',
    category: 'salud',
    time_block: 'morning',
    scheduled_time: '07:30',
    estimated_minutes: 15,
    days_of_week: [1, 2, 3, 4, 5, 6, 7],
});

const isProcessing = ref(false);

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

function updateStatus(item, status, skipReasonVal = null, postponeToBlockVal = null) {
    isProcessing.value = true;
    router.patch(
        route('day-planner.items.status', { id: item.id }),
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
    updateStatus(item, 'done');
}

function openSkipModal(item) {
    selectedItem.value = item;
    skipReason.value = 'cansancio';
    showSkipModal.value = true;
}

function confirmSkip() {
    if (!selectedItem.value) return;
    updateStatus(selectedItem.value, 'skipped', skipReason.value);
    showSkipModal.value = false;
    selectedItem.value = null;
}

function openPostponeModal(item) {
    selectedItem.value = item;
    postponeBlock.value = item.time_block === 'morning' ? 'afternoon' : 'night';
    showPostponeModal.value = true;
}

function confirmPostpone() {
    if (!selectedItem.value) return;
    updateStatus(selectedItem.value, 'postponed', null, postponeBlock.value);
    showPostponeModal.value = false;
    selectedItem.value = null;
}

function openAddModal(block = 'morning') {
    newItemForm.value = {
        title: '',
        category: 'estudio',
        time_block: block,
        scheduled_time: '',
        estimated_minutes: 15,
        notes: '',
    };
    showAddModal.value = true;
}

function submitNewItem() {
    isProcessing.value = true;
    router.post(
        route('day-planner.items.store'),
        {
            ...newItemForm.value,
            plan_date: props.currentDate,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showAddModal.value = false;
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function deleteItem(item) {
    if (!confirm('¿Deseas eliminar esta actividad del plan de hoy?')) return;
    router.delete(route('day-planner.items.destroy', { id: item.id }), {
        preserveScroll: true,
    });
}

function submitNewRoutine() {
    isProcessing.value = true;
    router.post(
        route('day-planner.routines.store'),
        routineForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                routineForm.value.title = '';
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function deleteRoutine(routineId) {
    if (!confirm('¿Eliminar esta plantilla de rutina? Ya no se generará en días futuros.')) return;
    router.delete(route('day-planner.routines.destroy', { id: routineId }), {
        preserveScroll: true,
    });
}

function applyMyRoutines() {
    isProcessing.value = true;
    router.post(
        route('day-planner.routines.apply'),
        { plan_date: props.currentDate },
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
        route('day-planner.starter-template'),
        { plan_date: props.currentDate },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            },
        }
    );
}

function changeDate(deltaDays) {
    const d = new Date(props.currentDate);
    d.setDate(d.getDate() + deltaDays);
    const newDateStr = d.toISOString().split('T')[0];
    router.visit(route('day-planner.index', { date: newDateStr }));
}

function goToPomodoro(item) {
    router.visit(route('pomodoro.index', { task: item.title }));
}

const formattedDate = computed(() => {
    try {
        const parts = props.currentDate.split('-');
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        return d.toLocaleDateString('es-PE', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        });
    } catch {
        return props.currentDate;
    }
});

const isToday = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.currentDate === today;
});
</script>

<template>
    <AppLayout title="Day Planner — Rutinas del Día">
        <Head title="Day Planner — Mi Plan del Día" />

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Encabezado Principal & Selector de Fecha -->
            <BaseCard class="p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-primary/15 text-primary-strong font-bold text-lg">📅</span>
                            <div>
                                <h1 class="font-display text-2xl font-bold tracking-tight text-content-primary capitalize">
                                    {{ formattedDate }}
                                </h1>
                                <p class="text-xs text-content-secondary mt-0.5">
                                    Lista de verificación de rutinas y actividades con 3 estados rápidos
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl border border-border bg-surface text-content-primary hover:bg-surface-raised text-xs font-semibold transition-all"
                            @click="changeDate(-1)"
                        >
                            ◀ Ayer
                        </button>
                        <button
                            type="button"
                            :class="[
                                'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all',
                                isToday ? 'bg-primary-strong text-on-primary-strong shadow-md shadow-primary-strong/20' : 'border border-border bg-surface text-content-primary hover:bg-surface-raised'
                            ]"
                            @click="router.visit(route('day-planner.index'))"
                        >
                            Hoy
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl border border-border bg-surface text-content-primary hover:bg-surface-raised text-xs font-semibold transition-all"
                            @click="changeDate(1)"
                        >
                            Mañana ▶
                        </button>

                        <button
                            type="button"
                            class="ml-2 px-3.5 py-1.5 rounded-xl bg-surface-raised border border-border text-content-secondary hover:text-content-primary text-xs font-semibold transition-all flex items-center gap-1.5"
                            @click="showRoutinesModal = true"
                        >
                            ⚙️ Plantillas de Rutina
                        </button>
                    </div>
                </div>
            </BaseCard>

            <!-- Barra de Métricas y Progreso del Día -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <BaseCard class="p-4 flex flex-col justify-between">
                    <span class="text-xs font-semibold text-content-muted">Total del Día</span>
                    <div class="font-display text-2xl font-bold text-content-primary mt-1">{{ plan.stats.total }}</div>
                    <span class="text-[11px] text-content-muted">actividades</span>
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
                    <span class="text-[11px] text-danger-text/80">con motivo</span>
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

            <!-- Bloques del Día: 3 Secciones (Mañana, Tarde, Noche) -->
            <div class="space-y-6">
                <!-- 1. Rutina Matutina -->
                <BaseCard class="p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">🌅</span>
                            <div>
                                <h3 class="font-display font-bold text-lg text-content-primary">Rutina Matutina</h3>
                                <p class="text-xs text-content-secondary">Despertar, aseo, desayuno y arranque del día (06:00 - 09:00)</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                            @click="openAddModal('morning')"
                        >
                            ➕ Añadir a la Mañana
                        </button>
                    </div>

                    <div v-if="plan.blocks.morning.length === 0" class="p-6 text-center text-content-muted text-sm border border-dashed border-border rounded-2xl">
                        No hay actividades matutinas para hoy. Haz clic en añadir o revisa tus plantillas.
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
                            <div class="flex items-start gap-3">
                                <span class="text-lg mt-0.5">
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

                            <!-- 3 Botones de Acción -->
                            <div class="flex items-center gap-1.5 self-end sm:self-center">
                                <button
                                    v-if="item.status !== 'done'"
                                    type="button"
                                    class="px-3 py-1.5 rounded-xl bg-success hover:opacity-90 text-on-accent text-xs font-bold transition-all active:scale-95 flex items-center gap-1"
                                    title="Marcar como hecho (+15 XP)"
                                    @click="handleMarkDone(item)"
                                >
                                    ✅ Hecho
                                </button>
                                <button
                                    v-if="item.status !== 'skipped'"
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-xl bg-surface hover:bg-danger/15 border border-border text-danger-text text-xs font-semibold transition-all"
                                    title="Marcar como no hecho / saltar con motivo"
                                    @click="openSkipModal(item)"
                                >
                                    ❌ No hecho
                                </button>
                                <button
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-xl bg-surface hover:bg-warning/15 border border-border text-warning text-xs font-semibold transition-all"
                                    title="Postergar al siguiente bloque"
                                    @click="openPostponeModal(item)"
                                >
                                    ⏳ Postergar
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-content-muted hover:text-danger-text text-xs transition-colors"
                                    title="Eliminar"
                                    @click="deleteItem(item)"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                </BaseCard>

                <!-- 2. Bloque de Foco / Tarde -->
                <BaseCard class="p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">☀️</span>
                            <div>
                                <h3 class="font-display font-bold text-lg text-content-primary">Bloque de Foco y Tarde</h3>
                                <p class="text-xs text-content-secondary">Estudio, sesiones de foco, misiones académicas y clases (09:00 - 18:00)</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                            @click="openAddModal('afternoon')"
                        >
                            ➕ Añadir a la Tarde
                        </button>
                    </div>

                    <div v-if="plan.blocks.afternoon.length === 0" class="p-6 text-center text-content-muted text-sm border border-dashed border-border rounded-2xl">
                        No hay actividades para la tarde. Haz clic en añadir.
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
                            <div class="flex items-start gap-3">
                                <span class="text-lg mt-0.5">
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
                                    class="px-3 py-1.5 rounded-xl bg-success hover:opacity-90 text-on-accent text-xs font-bold transition-all active:scale-95 flex items-center gap-1"
                                    @click="handleMarkDone(item)"
                                >
                                    ✅ Hecho
                                </button>
                                <button
                                    v-if="item.status !== 'skipped'"
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-xl bg-surface hover:bg-danger/15 border border-border text-danger-text text-xs font-semibold transition-all"
                                    @click="openSkipModal(item)"
                                >
                                    ❌ No hecho
                                </button>
                                <button
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-xl bg-surface hover:bg-warning/15 border border-border text-warning text-xs font-semibold transition-all"
                                    @click="openPostponeModal(item)"
                                >
                                    ⏳ Postergar
                                </button>
                                <button
                                    v-if="item.category === 'estudio'"
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-xl bg-primary/15 hover:bg-primary/25 text-primary-strong border border-primary/30 text-xs font-semibold"
                                    title="Abrir Pomodoro"
                                    @click="goToPomodoro(item)"
                                >
                                    🍅
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-content-muted hover:text-danger-text text-xs transition-colors"
                                    @click="deleteItem(item)"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                </BaseCard>

                <!-- 3. Rutina Nocturna -->
                <BaseCard class="p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">🌙</span>
                            <div>
                                <h3 class="font-display font-bold text-lg text-content-primary">Rutina Nocturna y Cierre</h3>
                                <p class="text-xs text-content-secondary">Cena, Daily Shutdown, reflexión en diario e higiene de sueño (19:00 - 23:00)</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-bold text-primary-strong hover:underline flex items-center gap-1"
                            @click="openAddModal('night')"
                        >
                            ➕ Añadir a la Noche
                        </button>
                    </div>

                    <div v-if="plan.blocks.night.length === 0" class="p-6 text-center text-content-muted text-sm border border-dashed border-border rounded-2xl">
                        No hay actividades nocturnas para hoy. Haz clic en añadir.
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
                            <div class="flex items-start gap-3">
                                <span class="text-lg mt-0.5">
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
                                    class="px-3 py-1.5 rounded-xl bg-success hover:opacity-90 text-on-accent text-xs font-bold transition-all active:scale-95 flex items-center gap-1"
                                    @click="handleMarkDone(item)"
                                >
                                    ✅ Hecho
                                </button>
                                <button
                                    v-if="item.status !== 'skipped'"
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-xl bg-surface hover:bg-danger/15 border border-border text-danger-text text-xs font-semibold transition-all"
                                    @click="openSkipModal(item)"
                                >
                                    ❌ No hecho
                                </button>
                                <button
                                    type="button"
                                    class="px-2.5 py-1.5 rounded-xl bg-surface hover:bg-warning/15 border border-border text-warning text-xs font-semibold transition-all"
                                    @click="openPostponeModal(item)"
                                >
                                    ⏳ Postergar
                                </button>
                                <button
                                    type="button"
                                    class="p-1.5 text-content-muted hover:text-danger-text text-xs transition-colors"
                                    @click="deleteItem(item)"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                </BaseCard>
            </div>
        </div>

        <!-- Modal: Marcar como No Hecho (Saltar con motivo) -->
        <BaseModal :show="showSkipModal" title="❌ Registrar Motivo de Salto" @close="showSkipModal = false">
            <div class="space-y-4">
                <p class="text-sm text-content-secondary">
                    No te preocupes por saltar una actividad hoy. Selecciona el motivo para entender mejor tus patrones de energía y bienestar:
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

        <!-- Modal: Añadir Actividad Rápida -->
        <BaseModal :show="showAddModal" title="➕ Añadir Actividad al Plan" @close="showAddModal = false">
            <form class="space-y-4" @submit.prevent="submitNewItem">
                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Nombre de la actividad</label>
                    <BaseInput v-model="newItemForm.title" placeholder="Ej. Repasar 15 min de inglés" required />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Bloque del día</label>
                        <BaseSelect v-model="newItemForm.time_block" :options="timeBlocks" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Categoría</label>
                        <BaseSelect v-model="newItemForm.category" :options="categories" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Hora estimada (opcional)</label>
                        <BaseInput v-model="newItemForm.scheduled_time" placeholder="08:30" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Duración (minutos)</label>
                        <BaseInput v-model="newItemForm.estimated_minutes" type="number" min="1" max="480" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <BaseButton variant="secondary" type="button" @click="showAddModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">Añadir al Día</BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- Modal: Plantillas de Rutina Recurrentes -->
        <BaseModal :show="showRoutinesModal" title="⚙️ Plantillas de Rutinas Recurrentes" size="lg" @close="showRoutinesModal = false">
            <div class="space-y-6">
                <p class="text-xs text-content-secondary">
                    Estas son las actividades que se generan automáticamente cada día en tu plan según tu horario habitual.
                </p>

                <!-- Formulario para agregar nueva plantilla -->
                <form class="bg-surface-raised p-4 rounded-2xl border border-border space-y-3" @submit.prevent="submitNewRoutine">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-primary-strong">➕ Nueva Plantilla de Rutina</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <BaseInput v-model="routineForm.title" placeholder="Ej. Ejercicio / Estiramiento" required />
                        <BaseSelect v-model="routineForm.time_block" :options="timeBlocks" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <BaseSelect v-model="routineForm.category" :options="categories" />
                        <BaseInput v-model="routineForm.scheduled_time" placeholder="Hora: 07:00" />
                    </div>
                    <div class="flex justify-end">
                        <BaseButton variant="primary" size="sm" type="submit" :disabled="isProcessing">
                            Guardar Plantilla
                        </BaseButton>
                    </div>
                </form>

                <!-- Lista de plantillas activas -->
                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    <div
                        v-for="routine in plan.routines"
                        :key="routine.id"
                        class="flex items-center justify-between p-3 rounded-xl bg-surface border border-border/70 text-sm"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-base">{{ routine.time_block === 'morning' ? '🌅' : routine.time_block === 'afternoon' ? '☀️' : '🌙' }}</span>
                            <div>
                                <span class="font-bold text-content-primary">{{ routine.title }}</span>
                                <span class="text-xs text-content-secondary block">{{ routine.scheduled_time || 'Sin hora fija' }} • ~{{ routine.estimated_minutes }} min</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-xs text-danger-text hover:opacity-80 font-bold p-1"
                            @click="deleteRoutine(routine.id)"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </BaseModal>
    </AppLayout>
</template>
