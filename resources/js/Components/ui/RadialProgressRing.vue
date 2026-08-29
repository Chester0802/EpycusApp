<script setup>
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, default: 0 },
    size: { type: Number, default: 140 },
    strokeWidth: { type: Number, default: 12 },
    gradientFrom: { type: String, default: '#0284c7' },
    gradientTo: { type: String, default: '#10b981' },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});

const CIRCUMFERENCE = 2 * Math.PI * 40; // R = 40

const clampedPercent = computed(() => Math.min(100, Math.max(0, props.percentage)));

const dashOffset = computed(() => {
    return CIRCUMFERENCE - (clampedPercent.value / 100) * CIRCUMFERENCE;
});
</script>

<template>
    <div class="flex flex-col items-center text-center">
        <div class="relative flex items-center justify-center">
            <svg :width="size" :height="size" viewBox="0 0 100 100" class="-rotate-90 transform">
                <defs>
                    <linearGradient id="radialGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" :stop-color="gradientFrom" />
                        <stop offset="100%" :stop-color="gradientTo" />
                    </linearGradient>
                </defs>

                <!-- Anillo Base Fondo con alto contraste -->
                <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    class="stroke-slate-200 dark:stroke-slate-700/60 transition-colors"
                    :stroke-width="strokeWidth"
                />

                <!-- Anillo de Progreso Animado con Degradado -->
                <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke="url(#radialGradient)"
                    :stroke-width="strokeWidth"
                    :stroke-dasharray="CIRCUMFERENCE"
                    :stroke-dashoffset="dashOffset"
                    stroke-linecap="round"
                    class="transition-all duration-1000 ease-out"
                />
            </svg>

            <!-- Contenido Central -->
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="font-display text-2xl font-black text-content-primary">
                    {{ clampedPercent }}%
                </span>
                <span
                    v-if="subtitle"
                    class="text-[10px] font-bold text-content-muted uppercase tracking-wider"
                >
                    {{ subtitle }}
                </span>
            </div>
        </div>

        <p v-if="title" class="mt-2 text-xs font-semibold text-content-secondary">
            {{ title }}
        </p>
    </div>
</template>
