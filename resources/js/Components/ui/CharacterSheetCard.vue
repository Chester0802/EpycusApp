<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';
import CharacterRadarChart from '@/Components/ui/CharacterRadarChart.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    userName: {
        type: String,
        default: 'Estudiante',
    },
    userCareer: {
        type: String,
        default: null,
    },
    avatarStyle: {
        type: String,
        default: 'base',
    },
    avatarGender: {
        type: String,
        default: 'm',
    },
    avatarOptions: {
        type: Object,
        default: () => ({}),
    },
    progress: {
        type: Object,
        required: true,
    },
    characterStats: {
        type: Object,
        required: true,
    },
});

// Estilo del marco del avatar según nivel
const avatarFrameClass = computed(() => {
    const lvl = props.progress?.level ?? 1;
    if (lvl >= 40) return 'border-pink-500 shadow-pink-500/30 ring-2 ring-pink-400';
    if (lvl >= 30) return 'border-amber-400 shadow-amber-400/30 ring-2 ring-amber-300';
    if (lvl >= 20) return 'border-indigo-400 shadow-indigo-400/30 ring-2 ring-indigo-300';
    if (lvl >= 10) return 'border-sky-400 shadow-sky-400/30 ring-1 ring-sky-300';
    return 'border-border-interactive shadow-sm';
});

// Aura de racha activa
const streakAuraClass = computed(() => {
    const streak = props.progress?.currentStreak ?? 0;
    if (streak >= 7) return 'animate-pulse ring-4 ring-amber-400/60 shadow-lg shadow-amber-500/40';
    if (streak >= 3) return 'ring-2 ring-emerald-400/60 shadow-md shadow-emerald-500/30';
    return '';
});
</script>

<template>
    <BaseCard class="p-6 overflow-hidden relative border-border-interactive">
        <!-- Fondo decorativo con sutil gradiente cósmico / RPG -->
        <div
            class="absolute top-0 right-0 -mt-8 -mr-8 w-72 h-72 bg-primary-strong/10 rounded-full blur-3xl pointer-events-none"
        ></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <!-- Lado Izquierdo: Identidad del Héroe, Nivel y Título de Clase (7 columnas) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                    <!-- Avatar con Marco de Nivel y Aura de Racha -->
                    <div class="relative shrink-0">
                        <div
                            class="h-22 w-22 rounded-2xl overflow-hidden bg-surface-raised/80 p-1 border-2 shadow-md transition-all duration-300 flex items-center justify-center"
                            :class="[avatarFrameClass, streakAuraClass]"
                        >
                            <ProceduralAvatar
                                :options="avatarOptions"
                                :gender="avatarGender"
                                :size="80"
                                class="h-full w-full object-contain"
                            />
                        </div>

                        <!-- Badge de Nivel flotante -->
                        <div
                            class="absolute -bottom-2 -right-2 rounded-full bg-primary-strong px-2 py-0.5 text-[11px] font-black text-on-accent border-2 border-surface shadow-md"
                        >
                            Nv. {{ progress.level }}
                        </div>
                    </div>

                    <!-- Nombre, Título de Clase y Carrera -->
                    <div class="text-center sm:text-left space-y-1 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            <span
                                class="rounded-full bg-primary-strong/15 px-2.5 py-0.5 text-[11px] font-bold text-primary-strong border border-primary-strong/30"
                            >
                                Fase {{ progress.phase }} de 10
                            </span>

                            <span
                                v-if="progress.currentStreak > 0"
                                class="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-[11px] font-bold text-amber-800 dark:text-amber-300 border border-amber-500/30 flex items-center gap-1"
                            >
                                <span>🔥</span> {{ progress.currentStreak }} días de racha
                            </span>

                            <span
                                class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 dark:text-emerald-300 border border-emerald-500/30 flex items-center gap-1"
                            >
                                <span>🪙</span> {{ progress.coins }}
                            </span>
                        </div>

                        <h2 class="font-display text-2xl font-bold tracking-tight text-content-primary">
                            {{ userName }}
                        </h2>

                        <!-- Título de Clase RPG Dinámico -->
                        <p class="text-sm font-bold text-primary-strong flex items-center justify-center sm:justify-start gap-1.5">
                            <span>✨</span> {{ characterStats.classTitle }}
                        </p>

                        <p class="text-xs text-content-secondary leading-relaxed line-clamp-2">
                            {{ characterStats.classDescription }}
                        </p>
                    </div>
                </div>

                <!-- Barra de Progreso de Nivel (XP) - Compacta y Elegante -->
                <div class="max-w-md space-y-1 bg-surface-raised/50 p-2.5 rounded-xl border border-border-interactive/60">
                    <div class="flex justify-between items-center text-[11px] font-semibold text-content-secondary">
                        <span>Progreso hacia Nivel {{ progress.level + 1 }}</span>
                        <span class="text-content-primary font-bold">
                            {{ progress.currentLevelXp }} / {{ progress.nextLevelXpNeeded }} XP ({{ progress.levelProgressPercent }}%)
                        </span>
                    </div>
                    <ProgressBar
                        :value="progress.currentLevelXp"
                        :max="progress.nextLevelXpNeeded"
                        color="bg-primary-strong"
                        size="h-1.5"
                    />
                </div>

                <!-- Enlace al Camino del Héroe -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <div class="text-xs text-content-muted">
                        Poder de Combate: <strong class="text-primary-strong text-sm">{{ characterStats.totalPowerScore }} pts</strong>
                    </div>

                    <Link
                        :href="route('profile.edit')"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-primary-strong hover:text-primary-strong/80 hover:underline transition cursor-pointer"
                    >
                        <span>🗺️ Ver mi Camino del Héroe (Fases 1–10)</span>
                        <AppIcon name="chevron-right" :size="14" />
                    </Link>
                </div>
            </div>

            <!-- Lado Derecho: Radar Pentagonal de Atributos RPG (5 columnas, centrado) -->
            <div class="lg:col-span-5 flex flex-col items-center justify-center pt-4 lg:pt-0 border-t lg:border-t-0 lg:border-l border-border-interactive/60 lg:px-4">
                <p class="text-xs font-bold uppercase tracking-wider text-content-muted mb-1 text-center">
                    Pentágono de Atributos RPG
                </p>
                <CharacterRadarChart :stats="characterStats" :size="180" />
            </div>
        </div>
    </BaseCard>
</template>
