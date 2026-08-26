<script setup>
import { computed } from 'vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    peakData: {
        type: Object,
        default: () => ({
            labels: [],
            data: [],
            peakWindow: 'Mañana (09:00 - 12:00)',
            peakMinutes: 0,
        }),
    },
});

const maxVal = computed(() => {
    const max = Math.max(...(props.peakData.data || [0]), 30);
    return max === 0 ? 30 : max;
});

function getBarHeightPercent(val) {
    if (!val) return 4;
    return Math.max(6, Math.min(100, Math.round((val / maxVal.value) * 100)));
}
</script>

<template>
    <BaseCard class="p-5 border-border-interactive flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">⏰</span>
                    <h3 class="font-bold text-sm text-content-primary">
                        Horas Pico de Rendimiento
                    </h3>
                </div>
                <span class="text-[11px] font-bold text-amber-800 dark:text-amber-300 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20 flex items-center gap-1">
                    <span>⚡</span> Modo Flow: {{ peakData.peakWindow }}
                </span>
            </div>
            <p class="text-xs text-content-secondary mb-4">
                Distribución de tus minutos de Pomodoro por franja horaria.
            </p>

            <!-- Gráfico de Barras Verticales Horarias -->
            <div class="h-32 flex items-end justify-between gap-2 pt-4 px-2">
                <div
                    v-for="(val, idx) in peakData.data"
                    :key="'hour-bar-' + idx"
                    class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end group cursor-pointer"
                >
                    <!-- Tooltip de valor en hover -->
                    <span class="text-[10px] font-bold text-primary-strong opacity-0 group-hover:opacity-100 transition-opacity">
                        {{ val }}m
                    </span>

                    <!-- Barra con gradiente -->
                    <div class="w-full max-w-[28px] bg-surface-raised rounded-t-md overflow-hidden flex items-end h-full">
                        <div
                            class="w-full rounded-t-md transition-all duration-500"
                            :class="
                                val === maxVal && val > 0
                                    ? 'bg-gradient-to-t from-primary-strong to-amber-400 shadow-sm shadow-amber-400/40'
                                    : 'bg-primary-strong/70 group-hover:bg-primary-strong'
                            "
                            :style="{ height: `${getBarHeightPercent(val)}%` }"
                        ></div>
                    </div>

                    <!-- Etiqueta de hora -->
                    <span class="text-[9px] font-semibold text-content-muted text-center truncate max-w-[42px]">
                        {{ (peakData.labels[idx] || '').split(' - ')[0] }}
                    </span>
                </div>
            </div>
        </div>

        <p class="text-[11px] text-content-muted mt-3 pt-2 border-t border-border-interactive/40">
            Aprovecha tu pico de energía para estudiar los temas de mayor dificultad.
        </p>
    </BaseCard>
</template>
