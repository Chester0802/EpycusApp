<script setup>
import { computed } from 'vue';

const props = defineProps({
    phase: {
        type: Number,
        default: 1,
    },
    streak: {
        type: Number,
        default: 0,
    },
    sizeClass: {
        type: String,
        default: 'h-36 w-24',
    },
    showBadge: {
        type: Boolean,
        default: true,
    },
});

const currentPhase = computed(() => Math.min(Math.max(Number(props.phase) || 1, 1), 10));

// Estilos de marco metálico evolutivo por fase
const frameStyle = computed(() => {
    const p = currentPhase.value;
    if (p <= 2) {
        // Bronce
        return 'border-2 border-amber-800/70 bg-amber-900/10 shadow-sm ring-1 ring-amber-700/30';
    } else if (p <= 4) {
        // Plata
        return 'border-2 border-slate-300/80 bg-slate-400/10 shadow-md ring-2 ring-slate-300/40';
    } else if (p <= 6) {
        // Oro
        return 'border-2 border-amber-400 bg-amber-400/15 shadow-lg ring-2 ring-amber-400/50';
    } else if (p <= 8) {
        // Esmeralda Neón
        return 'border-2 border-emerald-400 bg-emerald-400/20 shadow-xl ring-2 ring-emerald-300/60 shadow-emerald-400/20';
    } else {
        // Diamante Mítico Estelar
        return 'border-2 border-cyan-300 bg-cyan-400/20 shadow-2xl ring-4 ring-cyan-300/70 shadow-cyan-400/40 animate-pulse';
    }
});

// Estilo del badge de fase
const badgeStyle = computed(() => {
    const p = currentPhase.value;
    if (p <= 2) return 'bg-amber-800 text-amber-100 border-amber-700';
    if (p <= 4) return 'bg-slate-300 text-slate-900 border-slate-200 font-bold';
    if (p <= 6) return 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 border-amber-300 font-extrabold';
    if (p <= 8) return 'bg-gradient-to-r from-emerald-400 to-teal-500 text-slate-950 border-emerald-300 font-extrabold';
    return 'bg-gradient-to-r from-cyan-400 via-sky-400 to-indigo-500 text-slate-950 border-cyan-200 font-extrabold shadow-lg shadow-cyan-400/50';
});
</script>

<template>
    <div
        class="relative flex shrink-0 items-center justify-center rounded-2xl p-2 transition-all duration-300"
        :class="[frameStyle, sizeClass]"
    >
        <!-- Anillo de aura por racha de días (si racha >= 3) -->
        <div
            v-if="streak >= 3"
            class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-400 opacity-75 blur-sm animate-pulse -z-10"
        />

        <slot />

        <!-- Badge de Fase en la esquina -->
        <span
            v-if="showBadge"
            class="absolute -bottom-2 -right-2 rounded-full border px-2 py-0.5 text-[10px] uppercase tracking-wider shadow"
            :class="badgeStyle"
        >
            Fase {{ currentPhase }}
        </span>
    </div>
</template>
