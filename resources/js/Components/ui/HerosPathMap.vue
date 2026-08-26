<script setup>
import { ref, computed } from 'vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';

const props = defineProps({
    phases: {
        type: Array,
        default: () => [],
    },
    currentLevel: {
        type: Number,
        default: 1,
    },
    currentPhase: {
        type: Number,
        default: 1,
    },
    totalXp: {
        type: Number,
        default: 0,
    },
});

const selectedPhaseNumber = ref(props.currentPhase);

const selectedPhase = computed(() => {
    return props.phases.find((p) => p.phase === selectedPhaseNumber.value) || props.phases[0] || null;
});

function selectPhase(phaseNumber) {
    selectedPhaseNumber.value = phaseNumber;
}

function getPhaseStatus(phase) {
    if (props.currentLevel > phase.maxLevel) return 'completed';
    if (props.currentLevel >= phase.minLevel) return 'active';
    return 'locked';
}

function getPhaseProgress(phase) {
    if (props.currentLevel > phase.maxLevel) return 100;
    if (props.currentLevel < phase.minLevel) return 0;
    const range = phase.maxLevel - phase.minLevel + 1;
    const completed = props.currentLevel - phase.minLevel;
    return Math.min(100, Math.round((completed / range) * 100));
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header del Camino del Héroe -->
        <BaseCard class="p-6 border-border-interactive relative overflow-hidden">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-primary-strong/15 px-2.5 py-0.5 text-xs font-bold text-primary-strong border border-primary-strong/30">
                            Árbol de Evolución (50 Niveles)
                        </span>
                        <span class="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:text-amber-300 border border-amber-500/30">
                            Fase {{ currentPhase }} Activa
                        </span>
                    </div>
                    <h2 class="font-display text-2xl font-bold tracking-tight text-content-primary">
                        El Camino del Héroe 🗺️
                    </h2>
                    <p class="text-xs text-content-secondary max-w-xl">
                        Avanza a través de las 10 fases evolutivas de Epycus. Cada fase desbloquea nuevas recompensas, auras cósmicas, marcos de avatar y multiplicadores académicos.
                    </p>
                </div>

                <!-- Resumen de Progreso Global -->
                <div class="bg-surface-raised/60 p-3 rounded-2xl border border-border-interactive/60 flex items-center gap-4 shrink-0">
                    <div class="text-center px-2">
                        <p class="text-[10px] text-content-muted font-bold uppercase">Nivel Actual</p>
                        <p class="text-2xl font-black text-primary-strong">{{ currentLevel }}</p>
                    </div>
                    <div class="h-8 w-px bg-border-interactive"></div>
                    <div class="text-center px-2">
                        <p class="text-[10px] text-content-muted font-bold uppercase">XP Acumulada</p>
                        <p class="text-xl font-bold text-emerald-800 dark:text-emerald-300">{{ totalXp }}</p>
                    </div>
                </div>
            </div>
        </BaseCard>

        <!-- Grilla / Constelación de las 10 Fases -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <button
                v-for="phase in phases"
                :key="'phase-node-' + phase.phase"
                type="button"
                class="p-4 rounded-2xl border text-left transition-all duration-300 relative overflow-hidden group cursor-pointer flex flex-col justify-between"
                :class="[
                    selectedPhaseNumber === phase.phase
                        ? 'ring-2 ring-primary-strong shadow-md border-primary-strong bg-surface-raised'
                        : 'border-border-interactive bg-surface hover:border-primary-strong/60 hover:bg-surface-raised/60',
                    getPhaseStatus(phase) === 'completed'
                        ? 'border-emerald-500/40 bg-emerald-500/5'
                        : getPhaseStatus(phase) === 'active'
                          ? 'border-primary-strong bg-primary-strong/5'
                          : 'opacity-70 grayscale-[30%]',
                ]"
                @click="selectPhase(phase.phase)"
            >
                <!-- Indicador de Fase & Icono -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase"
                            :class="
                                getPhaseStatus(phase) === 'completed'
                                    ? 'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300'
                                    : getPhaseStatus(phase) === 'active'
                                      ? 'bg-primary-strong text-on-accent animate-pulse'
                                      : 'bg-surface-sunken text-content-muted'
                            "
                        >
                            {{ getPhaseStatus(phase) === 'completed' ? '✓ Fase ' + phase.phase : 'Fase ' + phase.phase }}
                        </span>

                        <span v-if="getPhaseStatus(phase) === 'completed'" class="text-xs text-emerald-800 dark:text-emerald-300 font-bold">
                            🏆
                        </span>
                        <span v-else-if="getPhaseStatus(phase) === 'locked'" class="text-xs text-content-muted">
                            🔒
                        </span>
                    </div>

                    <h3 class="font-bold text-xs text-content-primary leading-tight line-clamp-1">
                        {{ phase.name }}
                    </h3>

                    <p class="text-[11px] font-medium text-content-secondary">
                        {{ phase.levelRange }}
                    </p>
                </div>

                <!-- Barra de Progreso de la Fase -->
                <div class="mt-3 space-y-1">
                    <ProgressBar
                        :value="getPhaseProgress(phase)"
                        :max="100"
                        :color="getPhaseStatus(phase) === 'completed' ? 'bg-emerald-500' : 'bg-primary-strong'"
                        size="h-1.5"
                    />
                    <p class="text-[9px] text-content-muted text-right">
                        {{ getPhaseProgress(phase) }}%
                    </p>
                </div>
            </button>
        </div>

        <!-- Detalle Cinemático de la Fase Seleccionada -->
        <BaseCard v-if="selectedPhase" class="p-6 border-2 border-primary-strong/40 bg-surface-raised relative overflow-hidden">
            <div class="flex flex-col md:flex-row gap-6 justify-between items-start">
                <div class="space-y-3 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full px-3 py-0.5 text-xs font-black"
                            :class="
                                getPhaseStatus(selectedPhase) === 'completed'
                                    ? 'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30'
                                    : getPhaseStatus(selectedPhase) === 'active'
                                      ? 'bg-primary-strong text-on-accent'
                                      : 'bg-surface-sunken text-content-muted border border-border-interactive'
                            "
                        >
                            Fase {{ selectedPhase.phase }}: {{ selectedPhase.levelRange }}
                        </span>

                        <span class="text-xs text-content-secondary font-medium italic">
                            "{{ selectedPhase.tagline }}"
                        </span>
                    </div>

                    <h3 class="font-display text-2xl font-bold text-content-primary">
                        {{ selectedPhase.name }}
                    </h3>

                    <p class="text-sm text-content-secondary leading-relaxed bg-surface/60 p-3.5 rounded-xl border border-border-interactive/50">
                        {{ selectedPhase.lore }}
                    </p>

                    <!-- Lista de Recompensas y Desbloqueos de la Fase -->
                    <div class="space-y-2 pt-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-content-primary flex items-center gap-1.5">
                            <span>🎁</span> Recompensas & Desbloqueos de esta Fase:
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <div
                                v-for="(reward, idx) in selectedPhase.rewards"
                                :key="'reward-' + idx"
                                class="p-2.5 rounded-xl bg-surface border border-border-interactive text-xs flex items-start gap-2"
                            >
                                <span class="text-base text-primary-strong shrink-0">✨</span>
                                <span class="text-content-primary font-medium">{{ reward }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Lateral de Estado de la Fase -->
                <div class="w-full md:w-64 bg-surface p-4 rounded-2xl border border-border-interactive space-y-3 shrink-0 text-center">
                    <p class="text-xs font-bold text-content-muted uppercase">Estado en tu Aventura</p>

                    <div v-if="getPhaseStatus(selectedPhase) === 'completed'" class="space-y-2">
                        <div class="h-14 w-14 rounded-full bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 mx-auto flex items-center justify-center text-2xl shadow-inner border border-emerald-500/30">
                            ✓
                        </div>
                        <p class="font-bold text-sm text-emerald-800 dark:text-emerald-300">¡Fase Conquistada!</p>
                        <p class="text-[11px] text-content-secondary">
                            Superaste los {{ selectedPhase.levelRange }} y desbloqueaste todos sus honores.
                        </p>
                    </div>

                    <div v-else-if="getPhaseStatus(selectedPhase) === 'active'" class="space-y-2">
                        <div class="h-14 w-14 rounded-full bg-primary-strong/15 text-primary-strong mx-auto flex items-center justify-center text-2xl animate-pulse shadow-inner border border-primary-strong/30">
                            ⚡
                        </div>
                        <p class="font-bold text-sm text-primary-strong">Fase en Progreso</p>
                        <p class="text-[11px] text-content-secondary">
                            Estás en el nivel {{ currentLevel }}. ¡Completa misiones y hábitos para ascender!
                        </p>
                    </div>

                    <div v-else class="space-y-2">
                        <div class="h-14 w-14 rounded-full bg-surface-sunken text-content-muted mx-auto flex items-center justify-center text-2xl border border-border-interactive">
                            🔒
                        </div>
                        <p class="font-bold text-sm text-content-muted">Fase Bloqueada</p>
                        <p class="text-[11px] text-content-muted">
                            Alcanza el nivel {{ selectedPhase.minLevel }} para comenzar esta etapa.
                        </p>
                    </div>
                </div>
            </div>
        </BaseCard>
    </div>
</template>
