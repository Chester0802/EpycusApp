<script setup>
import { computed } from 'vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    courses: {
        type: Array,
        default: () => [],
    },
});

const totalMinutes = computed(() => {
    return props.courses.reduce((sum, c) => sum + (c.minutes || 0), 0);
});

function formatHours(mins) {
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    if (h === 0) return `${m} min`;
    return m > 0 ? `${h}h ${m}m` : `${h}h`;
}
</script>

<template>
    <BaseCard class="p-5 border-border-interactive flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-base">📚</span>
                    <h3 class="font-bold text-sm text-content-primary">
                        Distribución por Asignatura
                    </h3>
                </div>
                <span class="text-xs font-bold text-primary-strong bg-primary-strong/10 px-2 py-0.5 rounded-full border border-primary-strong/20">
                    {{ formatHours(totalMinutes) }} totales
                </span>
            </div>

            <div v-if="courses && courses.length > 0" class="space-y-3 mt-4">
                <div
                    v-for="(course, idx) in courses"
                    :key="'course-dist-' + idx"
                    class="space-y-1.5"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-content-primary flex items-center gap-1.5 truncate max-w-[200px]">
                            <span
                                class="h-2.5 w-2.5 rounded-full shrink-0"
                                :style="{ backgroundColor: course.color || '#6366f1' }"
                            ></span>
                            {{ course.name }}
                        </span>
                        <div class="flex items-center gap-2 text-content-secondary shrink-0">
                            <span class="font-bold text-content-primary">{{ formatHours(course.minutes) }}</span>
                            <span class="text-[10px] text-content-muted">({{ course.percentage }}%)</span>
                        </div>
                    </div>

                    <div class="h-2 w-full bg-surface-raised rounded-full overflow-hidden border border-border-interactive/30">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="{
                                width: `${course.percentage}%`,
                                backgroundColor: course.color || '#6366f1',
                            }"
                        ></div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-6 text-xs text-content-muted italic">
                Aún no has registrado sesiones con materias asignadas.
            </div>
        </div>

        <p class="text-[11px] text-content-muted mt-4 pt-2 border-t border-border-interactive/40">
            Vincula tus misiones y Pomodoros a tus cursos para un balance académico óptimo.
        </p>
    </BaseCard>
</template>
