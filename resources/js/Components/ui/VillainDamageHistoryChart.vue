<script setup>
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';

defineProps({
    history: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <BaseCard class="p-5 border-border-interactive flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚔️</span>
                    <h3 class="font-bold text-sm text-content-primary">
                        Historial de Combate vs. Villanos
                    </h3>
                </div>
                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                    Últimas 4 Semanas
                </span>
            </div>
            <p class="text-xs text-content-secondary mb-4">
                Daño infligido a los bosses semanales mediante tus actividades académicas.
            </p>

            <div v-if="history && history.length > 0" class="space-y-3">
                <div
                    v-for="(item, idx) in history"
                    :key="'villain-hist-' + idx"
                    class="p-3 rounded-xl bg-surface-raised border border-border-interactive/60 space-y-2"
                >
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-content-primary">Semana {{ item.week }}:</span>
                            <span class="font-medium text-content-secondary">{{ item.villainName }}</span>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                            :class="
                                item.defeated
                                    ? 'bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30'
                                    : 'bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/30'
                            "
                        >
                            {{ item.defeated ? '✓ Derrotado' : 'En Batalla' }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-content-muted">Daño Infligido:</span>
                            <strong class="text-rose-800 dark:text-rose-300">-{{ item.damageDealt }} / {{ item.totalHp }} HP</strong>
                        </div>
                        <ProgressBar
                            :value="item.damageDealt"
                            :max="item.totalHp"
                            :color="item.defeated ? 'bg-emerald-500' : 'bg-rose-500'"
                            size="h-2"
                        />
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-6 text-xs text-content-muted italic">
                El combate semanal contra el villano se registrará aquí.
            </div>
        </div>

        <p class="text-[11px] text-content-muted mt-3 pt-2 border-t border-border-interactive/40">
            Completar tareas, hábitos y Pomodoros debilita la resistencia de los villanos del semestre.
        </p>
    </BaseCard>
</template>
