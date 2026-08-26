<script setup>
import { computed } from 'vue';
import BaseCard from '@/Components/ui/BaseCard.vue';

const props = defineProps({
    trendData: {
        type: Array,
        default: () => [],
    },
});

const hasData = computed(() => {
    return props.trendData.some((d) => d.energy !== null || d.stress !== null);
});

// Puntos para la curva de energía (escala 1 a 5)
const energyPoints = computed(() => {
    if (!props.trendData.length) return '';
    const step = 280 / (props.trendData.length - 1 || 1);
    return props.trendData
        .map((d, i) => {
            const val = d.energy !== null ? d.energy : 3;
            const x = i * step;
            const y = 90 - (val - 1) * 20; // escala 1-5 -> y: 90 to 10
            return `${x},${y}`;
        })
        .join(' ');
});

// Puntos para la curva de estrés (escala 1 a 5)
const stressPoints = computed(() => {
    if (!props.trendData.length) return '';
    const step = 280 / (props.trendData.length - 1 || 1);
    return props.trendData
        .map((d, i) => {
            const val = d.stress !== null ? d.stress : 2;
            const x = i * step;
            const y = 90 - (val - 1) * 20;
            return `${x},${y}`;
        })
        .join(' ');
});
</script>

<template>
    <BaseCard class="p-5 border-border-interactive flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">📈</span>
                    <h3 class="font-bold text-sm text-content-primary">
                        Curva de Bienestar: Energía vs. Estrés
                    </h3>
                </div>
                <div class="flex items-center gap-3 text-[11px]">
                    <span class="flex items-center gap-1 font-semibold text-emerald-800 dark:text-emerald-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Energía
                    </span>
                    <span class="flex items-center gap-1 font-semibold text-rose-800 dark:text-rose-300">
                        <span class="h-2 w-2 rounded-full bg-rose-500"></span> Estrés
                    </span>
                </div>
            </div>
            <p class="text-xs text-content-secondary mb-3">
                Tendencia de los últimos 14 días basada en tu Diario Emocional.
            </p>

            <!-- Gráfico de Líneas SVG -->
            <div v-if="hasData" class="relative pt-2">
                <svg
                    viewBox="0 0 280 100"
                    class="w-full h-28 overflow-visible select-none drop-shadow-sm"
                >
                    <!-- Líneas guía horizontales -->
                    <line x1="0" y1="10" x2="280" y2="10" class="stroke-slate-200 dark:stroke-slate-700" stroke-dasharray="2 2" stroke-width="1" />
                    <line x1="0" y1="50" x2="280" y2="50" class="stroke-slate-200 dark:stroke-slate-700" stroke-dasharray="2 2" stroke-width="1" />
                    <line x1="0" y1="90" x2="280" y2="90" class="stroke-slate-300 dark:stroke-slate-700" stroke-width="1" />

                    <!-- Curva de Energía (Verde) -->
                    <polyline
                        :points="energyPoints"
                        fill="none"
                        stroke="#10b981"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="transition-all duration-500"
                    />

                    <!-- Curva de Estrés (Rosa/Rojo) -->
                    <polyline
                        :points="stressPoints"
                        fill="none"
                        stroke="#f43f5e"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="transition-all duration-500"
                    />
                </svg>

                <!-- Etiquetas de fechas (primer y último día) -->
                <div class="flex justify-between text-[10px] text-content-muted mt-1">
                    <span>{{ trendData[0]?.label }}</span>
                    <span>{{ trendData[Math.floor(trendData.length / 2)]?.label }}</span>
                    <span>{{ trendData[trendData.length - 1]?.label }}</span>
                </div>
            </div>

            <div v-else class="text-center py-6 text-xs text-content-muted italic">
                Registra tus emociones en el Diario de Bienestar para ver tu evolución.
            </div>
        </div>

        <p class="text-[11px] text-content-muted mt-3 pt-2 border-t border-border-interactive/40">
            Mantener la energía alta y el estrés controlado optimiza tu memoria a largo plazo.
        </p>
    </BaseCard>
</template>
