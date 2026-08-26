<script setup>
import { computed, ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    month: { type: Number, required: true },
    year: { type: Number, required: true },
    todayDate: { type: String, required: true },
    days: { type: Object, default: () => ({}) },
    holidays: { type: Object, default: () => ({}) },
    examDates: { type: Object, default: () => ({}) },
    moodScale: { type: Array, default: () => [] },
    entryTags: { type: Array, default: () => [] },
    healthTip: { type: String, default: null },
    physicalActivityTypes: { type: Array, default: () => [] },
    epaDiagnostic: { type: Object, default: null },
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

const calendarDays = computed(() => {
    const cells = [];
    const totalCells = Math.ceil((firstDayOfWeek.value + daysInMonth.value) / 7) * 7;
    for (let i = 0; i < totalCells; i++) {
        const dayNum = i - firstDayOfWeek.value + 1;
        const dateStr = dayNum >= 1 && dayNum <= daysInMonth.value
            ? `${props.year}-${String(props.month).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`
            : null;
        const dayData = dateStr ? props.days[dateStr] : null;
        const moodScore = dayData?.avg_score ?? null;
        const moodInfo = moodScore ? props.moodScale.find(m => m.value === moodScore) : null;
        cells.push({
            day: dayNum >= 1 && dayNum <= daysInMonth.value ? dayNum : null,
            date: dateStr,
            moodScore,
            moodEmoji: moodInfo?.emoji ?? null,
            moodLabel: moodInfo?.label ?? null,
            holiday: dateStr ? props.holidays[dateStr] : null,
            isExam: dateStr ? !!props.examDates[dateStr] : false,
            isToday: dateStr === props.todayDate,
            isPast: dateStr !== null && dateStr < props.todayDate,
            hasEntry: !!dayData,
            entryCount: dayData?.entry_count ?? 0,
        });
    }
    return cells;
});

function goToMonth(m, y) {
    router.get(route('wellbeing.index', { month: m, year: y }), { preserveScroll: true });
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
function openDay(dateStr) {
    router.get(route('wellbeing.day', { date: dateStr }), { preserveScroll: true });
}

const showMonthPicker = ref(false);
const pickerMonth = ref(props.month);
const pickerYear = ref(props.year);
function applyMonthPicker() {
    goToMonth(pickerMonth.value, pickerYear.value);
    showMonthPicker.value = false;
}
const yearOptions = Array.from({ length: 5 }, (_, i) => 2026 + i);
</script>

<template>
    <Head title="Bienestar — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Pestañas de Navegación del Hub de Salud & Bienestar -->
            <div class="flex items-center gap-2 border-b border-border/70 pb-2 overflow-x-auto">
                <Link
                    :href="route('habits.index')"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all text-content-secondary hover:bg-surface-raised hover:text-content-primary flex items-center gap-2 shrink-0"
                >
                    <span>🌿</span> Hábitos Diarios
                </Link>
                <Link
                    :href="route('wellbeing.index')"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all bg-primary-strong text-on-primary-strong shadow-sm flex items-center gap-2 shrink-0"
                >
                    <span>🧘</span> Diario & Ánimo
                </Link>
                <Link
                    :href="route('fitness.index')"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all text-content-secondary hover:bg-surface-raised hover:text-content-primary flex items-center gap-2 shrink-0"
                >
                    <span>💪</span> Fitness & Hidratación
                </Link>
            </div>

            <BaseCard class="mb-6">
                <header class="flex items-center justify-between">
                    <div>
                        <h1 class="font-display text-3xl text-content-primary">Bienestar</h1>
                        <p class="mt-1 text-sm text-content-secondary">Registrá tu ánimo cada día. Opcional, solo para vos.</p>
                    </div>
                </header>
            </BaseCard>

            <BaseCard class="p-4">
                <div class="mb-4 flex items-center justify-between">
                    <button type="button" class="rounded-lg px-3 py-1.5 text-sm text-content-secondary hover:bg-surface-raised" @click="prevMonth">← {{ month === 1 ? monthNames[11] : monthNames[month - 2] }}</button>
                    <button type="button" class="font-display text-xl text-content-primary hover:text-primary-strong" @click="showMonthPicker = !showMonthPicker">{{ monthNames[month - 1] }} {{ year }}</button>
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
                    <button type="button" class="rounded-lg bg-primary-strong px-3 py-1.5 text-sm text-on-accent hover:opacity-90" @click="applyMonthPicker">Ir</button>
                    <button type="button" class="text-sm text-content-muted hover:text-content-primary" @click="showMonthPicker = false">Cancelar</button>
                </div>

                <div class="grid grid-cols-7 gap-px rounded-lg overflow-hidden">
                    <div v-for="d in ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do']" :key="d" class="bg-surface-sunken p-2 text-center text-xs font-semibold text-content-muted">{{ d }}</div>
                    <div
                        v-for="(cell, i) in calendarDays" :key="i"
                        class="min-h-[90px] bg-surface p-2 transition cursor-pointer hover:bg-surface-raised"
                        :class="{
                            'bg-primary/10 ring-2 ring-primary': cell.isToday,
                            'opacity-40': !cell.hasEntry && cell.isPast && !cell.holiday,
                        }"
                        @click="cell.date ? openDay(cell.date) : null"
                    >
                        <div class="flex items-center gap-1">
                            <span
                                v-if="cell.day" class="text-xs font-semibold"
                                :class="cell.isToday ? 'text-primary' : 'text-content-secondary'"
                            >{{ cell.day }}</span>
                            <span v-if="cell.holiday" class="rounded bg-danger/10 px-1 text-[9px] text-danger inline-flex items-center" title="Feriado"><AppIcon name="leaf" :size="9" /></span>
                            <span v-if="cell.isExam" class="rounded bg-warning/20 px-1 text-[9px] text-warning inline-flex items-center" title="Exámenes"><AppIcon name="book-open" :size="9" /></span>
                        </div>
                        <div v-if="cell.moodEmoji" class="mt-2 text-center">
                            <span class="text-2xl" :title="`${cell.moodLabel} (${cell.moodScore}/5)`">{{ cell.moodEmoji }}</span>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-content-muted">
                <span class="flex items-center gap-1"><span class="rounded bg-danger/10 px-1 text-[9px] text-danger inline-flex items-center"><AppIcon name="leaf" :size="9" /></span> Feriado</span>
                <span class="flex items-center gap-1"><span class="rounded bg-warning/20 px-1 text-[9px] text-warning inline-flex items-center"><AppIcon name="book-open" :size="9" /></span> Semana de exámenes</span>
            </div>

            <BaseCard v-if="healthTip" class="mt-6 border-l-4 border-l-primary-strong p-4">
                <div class="flex items-start gap-3">
                    <AppIcon name="heart" :size="20" class="mt-0.5 shrink-0 text-danger" />
                    <div>
                        <p class="text-sm font-medium text-content-primary">Consejo del día</p>
                        <p class="mt-1 text-sm text-content-secondary">{{ healthTip }}</p>
                    </div>
                </div>
            </BaseCard>

            <BaseCard class="mt-6 flex flex-col items-center p-8 text-center">
                <AppIcon name="message-square" :size="32" class="mb-2 text-content-muted" />
                <h2 class="text-sm font-semibold text-content-primary">¿Cómo te sentís hoy?</h2>
                <p class="mt-1 text-sm text-content-secondary">Hacé clic en el día de hoy para registrar tu ánimo.</p>
            </BaseCard>

            <!-- ── Diagnóstico Inicial EPA (Día 1) ── -->
            <BaseCard v-if="epaDiagnostic" class="mt-6 overflow-hidden border border-primary/25 bg-surface-raised shadow-sm">
                <div class="border-b border-border/70 bg-primary/5 px-5 py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/20 text-primary text-base">📊</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-display text-base font-bold text-content-primary">
                                    Diagnóstico Científico · Escala EPA
                                </h3>
                                <span class="rounded-full bg-primary/15 border border-primary/30 px-2.5 py-0.5 text-[11px] font-extrabold text-primary">
                                    {{ epaDiagnostic.day_label }}
                                </span>
                            </div>
                            <p class="text-xs text-content-secondary">
                                {{ epaDiagnostic.milestone_title }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 text-xs text-content-muted self-start sm:self-auto">
                        <span>🗓️</span>
                        <span>Evaluado el {{ epaDiagnostic.completed_at_formatted }}</span>
                    </div>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-center">
                        <!-- Score & Gauge -->
                        <div class="rounded-xl bg-surface-sunken p-4 border border-border/60">
                            <span class="text-xs font-semibold uppercase tracking-wider text-content-muted">Puntaje Obtenido</span>
                            <div class="flex items-baseline gap-1.5 mt-1">
                                <span class="font-display text-3xl font-black text-content-primary">{{ epaDiagnostic.total_score }}</span>
                                <span class="text-xs font-semibold text-content-secondary">/ {{ epaDiagnostic.max_score }} pts</span>
                                <span class="ml-auto text-xs font-bold text-primary">{{ epaDiagnostic.percentage }}%</span>
                            </div>

                            <div class="mt-2.5 h-2.5 w-full rounded-full bg-surface overflow-hidden border border-border/40">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r transition-all duration-700 ease-out"
                                    :class="[
                                        epaDiagnostic.level === 'high' ? 'from-emerald-500 to-teal-400' :
                                        epaDiagnostic.level === 'moderate' ? 'from-amber-500 to-orange-400' :
                                        'from-rose-500 to-pink-500'
                                    ]"
                                    :style="{ width: `${epaDiagnostic.percentage}%` }"
                                />
                            </div>

                            <div class="mt-2.5 flex items-center justify-between text-[11px]">
                                <span class="text-content-muted">Nivel:</span>
                                <span
                                    class="font-bold"
                                    :class="[
                                        epaDiagnostic.level === 'high' ? 'text-emerald-400' :
                                        epaDiagnostic.level === 'moderate' ? 'text-amber-400' :
                                        'text-rose-400'
                                    ]"
                                >
                                    {{ epaDiagnostic.level_label }}
                                </span>
                            </div>
                        </div>

                        <!-- Interpretative explanation -->
                        <div class="md:col-span-2 space-y-3">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-primary">Interpretación de tus hábitos</h4>
                                <p class="mt-1 text-sm text-content-primary leading-relaxed font-medium">
                                    {{ epaDiagnostic.description }}
                                </p>
                            </div>

                            <div v-if="epaDiagnostic.recommendations?.length" class="space-y-1.5 pt-1">
                                <span class="text-xs font-semibold text-content-muted">Recomendaciones para potenciar tu aventura:</span>
                                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-content-secondary">
                                    <li v-for="(rec, idx) in epaDiagnostic.recommendations" :key="idx" class="flex items-start gap-1.5 rounded-lg bg-surface-sunken p-2 border border-border/40">
                                        <span class="text-primary font-bold shrink-0">✦</span>
                                        <span>{{ rec }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </div>
    </AppLayout>
</template>
