<script setup>
import { computed } from 'vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import AvatarFrame from '@/Components/ui/AvatarFrame.vue';
import StreakAura from '@/Components/ui/StreakAura.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';
import { getCareerRankTitle } from '@/constants/careerRanks';

const props = defineProps({
    userName: { type: String, default: 'Estudiante' },
    userCareer: { type: String, default: null },
    avatarStyle: { type: String, default: 'base' },
    avatarGender: { type: String, default: 'm' },
    avatarOptions: { type: Object, default: () => ({}) },
    progress: {
        type: Object,
        default: () => ({ phase: 1, level: 1, currentStreak: 0, totalXp: 0 }),
    },
});

const currentPhase = computed(() => Number(props.progress?.phase) || 1);
const currentStreak = computed(() => Number(props.progress?.currentStreak) || 0);

// Título evolutivo específico para su carrera exacta
const rankTitle = computed(() => getCareerRankTitle(props.userCareer, currentPhase.value));

// Estilo del sello metálico de verificación
const sealBadgeStyle = computed(() => {
    const p = currentPhase.value;
    if (p <= 2) return 'from-amber-700 to-amber-900 text-amber-200 border-amber-600';
    if (p <= 4) return 'from-slate-300 to-slate-500 text-slate-950 border-slate-200 font-bold';
    if (p <= 6) return 'from-amber-300 via-amber-400 to-yellow-600 text-slate-950 border-amber-300 font-black';
    if (p <= 8) return 'from-emerald-300 via-teal-400 to-emerald-600 text-slate-950 border-emerald-300 font-black';
    return 'from-cyan-300 via-sky-400 to-indigo-600 text-slate-950 border-cyan-200 font-black shadow-lg shadow-cyan-400/40';
});
</script>

<template>
    <BaseCard class="relative overflow-hidden p-6 shadow-xl transition-all duration-300 hover:shadow-2xl">
        <!-- Textura holográfica de fondo / marcas de agua -->
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gradient-to-br from-primary-strong/10 via-primary-strong/5 to-transparent blur-2xl" />

        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <!-- Marco de Avatar Evolutivo con Aura -->
                <AvatarFrame
                    :phase="currentPhase"
                    :streak="currentStreak"
                    size-class="h-36 w-28 shrink-0"
                >
                    <ProceduralAvatar
                        :career="avatarStyle"
                        :gender="avatarGender"
                        :avatar-options="avatarOptions"
                        :phase="currentPhase"
                        :size="256"
                    />
                </AvatarFrame>

                <!-- Información del Carnet Estudiantil -->
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Sello / Rango Metálico de Credencial -->
                        <span class="inline-flex items-center gap-1 rounded-full border bg-gradient-to-r px-2.5 py-0.5 text-[10px] uppercase tracking-wider shadow-sm" :class="sealBadgeStyle">
                            🛡️ {{ rankTitle }}
                        </span>

                        <!-- Badge de Racha si está activa -->
                        <StreakAura :streak="currentStreak" />
                    </div>

                    <div>
                        <h1 class="font-display text-2xl font-bold tracking-tight text-content-primary sm:text-3xl">
                            ¡Hola, {{ userName }}!
                        </h1>
                        <p class="text-sm font-semibold text-primary-strong">
                            {{ userCareer || 'Estudiante Epycus' }}
                        </p>
                    </div>

                    <!-- Nivel y Progreso de Fase -->
                    <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-content-secondary pt-1">
                        <span class="rounded-lg bg-surface-raised px-2.5 py-1 border border-border-interactive">
                            🏅 Nivel <strong class="text-content-primary">{{ progress.level || 1 }}</strong>
                        </span>
                        <span class="rounded-lg bg-surface-raised px-2.5 py-1 border border-border-interactive">
                            ⚡ <strong class="text-content-primary">{{ progress.totalXp || 0 }}</strong> XP Acumulados
                        </span>
                        <span class="rounded-lg bg-surface-raised px-2.5 py-1 border border-border-interactive">
                            🪙 <strong class="text-content-primary">{{ progress.coins || 0 }}</strong> Monedas
                        </span>
                    </div>
                </div>
            </div>

            <!-- Ranura para botones o acciones rápidas -->
            <div v-if="$slots.actions" class="shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </BaseCard>
</template>
