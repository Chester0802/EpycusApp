<script setup>
import { computed } from 'vue';

const props = defineProps({
    segments: {
        type: Array,
        required: true,
        // Array de { label, value, colorClass, hexColor }
    },
    centerTitle: { type: [String, Number], default: '' },
    centerSubtitle: { type: String, default: '' },
    size: { type: Number, default: 160 },
    strokeWidth: { type: Number, default: 14 },
});

const CIRCUMFERENCE = 2 * Math.PI * 40; // R = 40

const totalValue = computed(() => {
    return props.segments.reduce((acc, seg) => acc + (Number(seg.value) || 0), 0);
});

const formattedSegments = computed(() => {
    if (totalValue.value <= 0) {
        return [
            {
                label: 'Sin datos',
                value: 1,
                percentage: 100,
                strokeDasharray: `${CIRCUMFERENCE} ${CIRCUMFERENCE}`,
                strokeDashoffset: 0,
                color: '#64748b',
            },
        ];
    }

    let cumulativePercentage = 0;
    return props.segments.map((seg) => {
        const val = Number(seg.value) || 0;
        const percentage = (val / totalValue.value) * 100;
        const dashLength = (percentage / 100) * CIRCUMFERENCE;
        const dashOffset = -((cumulativePercentage / 100) * CIRCUMFERENCE);
        cumulativePercentage += percentage;

        return {
            ...seg,
            percentage: Math.round(percentage),
            strokeDasharray: `${dashLength} ${CIRCUMFERENCE - dashLength}`,
            strokeDashoffset: dashOffset,
        };
    });
});
</script>

<template>
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center sm:justify-between">
        <!-- SVG Donut Canvas -->
        <div class="relative flex items-center justify-center shrink-0">
            <svg
                :width="size"
                :height="size"
                viewBox="0 0 100 100"
                class="-rotate-90 transform transition-all duration-500"
            >
                <!-- Anillo Trasero Base -->
                <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    class="stroke-surface-raised"
                    :stroke-width="strokeWidth"
                />

                <!-- Segmentos del Donut -->
                <circle
                    v-for="(seg, idx) in formattedSegments"
                    :key="idx"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    :stroke="seg.hexColor || 'currentColor'"
                    :class="seg.colorClass"
                    :stroke-width="strokeWidth"
                    :stroke-dasharray="seg.strokeDasharray"
                    :stroke-dashoffset="seg.strokeDashoffset"
                    stroke-linecap="round"
                    class="transition-all duration-700 ease-out"
                />
            </svg>

            <!-- Texto del Centro -->
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="font-display text-2xl font-black text-content-primary">
                    {{ centerTitle !== '' ? centerTitle : totalValue }}
                </span>
                <span
                    v-if="centerSubtitle"
                    class="text-[10px] font-bold text-content-muted uppercase tracking-wider"
                >
                    {{ centerSubtitle }}
                </span>
            </div>
        </div>

        <!-- Leyenda Lateral con Badges -->
        <div class="w-full space-y-2 text-xs font-semibold sm:w-auto flex-1">
            <div
                v-for="(seg, idx) in segments"
                :key="'leg-' + idx"
                class="flex items-center justify-between rounded-lg bg-surface-raised/50 px-3 py-1.5 border border-border-interactive transition-colors hover:bg-surface-raised"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full shadow-sm"
                        :style="{ backgroundColor: seg.hexColor }"
                        :class="seg.colorClass"
                    />
                    <span class="text-content-secondary">{{ seg.label }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="font-bold text-content-primary">{{ seg.value }}</span>
                    <span class="text-[11px] text-content-muted font-normal">
                        ({{ totalValue > 0 ? Math.round((seg.value / totalValue) * 100) : 0 }}%)
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
