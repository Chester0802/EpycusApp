<script setup>
import { ref, computed } from 'vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
});

const hoveredDay = ref(null);

// Agrupar en semanas (columnas de 7 días: Lu a Do)
const weeks = computed(() => {
    if (!props.data || props.data.length === 0) return [];
    const chunks = [];
    let currentWeek = [];

    props.data.forEach((day, index) => {
        currentWeek.push(day);
        if (currentWeek.length === 7 || index === props.data.length - 1) {
            chunks.push(currentWeek);
            currentWeek = [];
        }
    });

    return chunks;
});

const totalActiveDays = computed(() => {
    return props.data.filter((d) => d.count > 0).length;
});

const totalActivities = computed(() => {
    return props.data.reduce((sum, d) => sum + (d.count || 0), 0);
});

function getLevelClass(level) {
    switch (level) {
        case 4:
            return 'bg-emerald-600 dark:bg-emerald-400 shadow-sm shadow-emerald-500/50 ring-1 ring-emerald-300';
        case 3:
            return 'bg-emerald-500 dark:bg-emerald-600';
        case 2:
            return 'bg-emerald-400 dark:bg-emerald-700';
        case 1:
            return 'bg-emerald-200 dark:bg-emerald-900/60';
        default:
            return 'bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/50';
    }
}
</script>

<template>
    <BaseCard class="p-5 border-border-interactive">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <span class="text-base">🔥</span>
                    <h3 class="font-bold text-sm text-content-primary">
                        Mapa de Consistencia Diaria (60 Días)
                    </h3>
                </div>
                <p class="text-xs text-content-secondary">
                    Registros continuos de Pomodoros, Hábitos y Misiones.
                </p>
            </div>

            <!-- Contadores de Consistencia -->
            <div class="flex items-center gap-3 text-xs">
                <div class="bg-surface-raised px-2.5 py-1 rounded-lg border border-border-interactive">
                    <span class="text-content-muted">Días Activos: </span>
                    <strong class="text-primary-strong">{{ totalActiveDays }} / {{ data.length }}</strong>
                </div>
                <div class="bg-surface-raised px-2.5 py-1 rounded-lg border border-border-interactive">
                    <span class="text-content-muted">Total Acciones: </span>
                    <strong class="text-emerald-800 dark:text-emerald-300">{{ totalActivities }}</strong>
                </div>
            </div>
        </div>

        <!-- Matriz de Cuadrículas del Heatmap -->
        <div class="overflow-x-auto pb-2">
            <div class="inline-flex gap-1.5 min-w-full justify-start sm:justify-center p-1">
                <div
                    v-for="(week, wIdx) in weeks"
                    :key="'week-' + wIdx"
                    class="flex flex-col gap-1.5"
                >
                    <div
                        v-for="day in week"
                        :key="'day-' + day.date"
                        class="h-4 w-4 rounded-sm cursor-pointer transition-all duration-200 hover:scale-125 hover:z-10 relative"
                        :class="getLevelClass(day.level)"
                        @mouseenter="hoveredDay = day"
                        @mouseleave="hoveredDay = null"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Leyenda y Detalle de Hover -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-3 pt-3 border-t border-border-interactive/40 text-xs">
            <!-- Detalle flotante al pasar cursor -->
            <div class="text-content-secondary font-medium min-h-[20px]">
                <span v-if="hoveredDay" class="flex items-center gap-1.5 text-content-primary">
                    <strong class="text-primary-strong">{{ hoveredDay.date }}:</strong>
                    <span>{{ hoveredDay.count }} acciones</span>
                    <span class="text-content-muted text-[11px]">
                        ({{ hoveredDay.focusMinutes }} min foco, {{ hoveredDay.habitsDone }} hábitos, {{ hoveredDay.missionsDone }} misiones)
                    </span>
                </span>
                <span v-else class="text-content-muted italic">
                    Pasa el cursor sobre un día para ver detalles
                </span>
            </div>

            <!-- Leyenda de colores -->
            <div class="flex items-center gap-1.5 text-[11px] text-content-muted">
                <span>Menos</span>
                <div class="h-3 w-3 rounded-sm bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/50"></div>
                <div class="h-3 w-3 rounded-sm bg-emerald-200 dark:bg-emerald-900/60"></div>
                <div class="h-3 w-3 rounded-sm bg-emerald-400 dark:bg-emerald-700"></div>
                <div class="h-3 w-3 rounded-sm bg-emerald-500 dark:bg-emerald-600"></div>
                <div class="h-3 w-3 rounded-sm bg-emerald-600 dark:bg-emerald-400 shadow-sm"></div>
                <span>Más</span>
            </div>
        </div>
    </BaseCard>
</template>
