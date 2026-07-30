<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard from '@/Components/ui/BaseCard.vue'
import ProgressBar from '@/Components/ui/ProgressBar.vue'
import UsageTipBanner from '@/Components/ui/UsageTipBanner.vue'

const props = defineProps({
    summary: { type: Object, required: true },
    achievements: { type: Array, default: () => [] },
    avatarImage: { type: String, default: null },
})

const activeCategory = ref('all')

const categories = [
    { id: 'all', label: 'Todos' },
    { id: 'constancia', label: '🔥 Constancia' },
    { id: 'volumen', label: '⏱️ Volumen' },
    { id: 'progresion', label: '🥋 Progresión' },
    { id: 'villanos', label: '⚔️ Villanos' },
    { id: 'bienestar', label: '🧘 Bienestar' },
    { id: 'puntualidad', label: '🎯 Puntualidad' },
]

const filteredAchievements = computed(() => {
    if (activeCategory.value === 'all') return props.achievements
    return props.achievements.filter(a => a.category === activeCategory.value)
})
</script>

<template>
    <Head title="Logros e Insignias — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header Banner -->
            <BaseCard class="p-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            v-if="avatarImage"
                            class="flex h-24 w-16 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-1 border border-border-interactive shadow-sm"
                        >
                            <img :src="avatarImage" alt="Avatar" class="h-full w-full object-contain" />
                        </div>
                        <div class="space-y-1">
                            <h1 class="font-display text-2xl font-bold tracking-tight text-content-primary">
                                Logros e Insignias 🏆
                            </h1>
                            <p class="text-sm text-content-secondary">
                                Reconocimiento a tu constancia, hábitos y progreso real de estudio.
                            </p>
                        </div>
                    </div>

                    <!-- Progress Badge Counter -->
                    <div class="flex flex-col items-end shrink-0 min-w-[180px] space-y-1.5">
                        <div class="flex items-center justify-between w-full text-xs font-semibold text-content-primary">
                            <span>Desbloqueados:</span>
                            <span class="font-bold text-primary-strong">{{ summary.unlocked }} / {{ summary.total }}</span>
                        </div>
                        <ProgressBar
                            :value="summary.unlocked"
                            :max="summary.total"
                            color="bg-primary-strong"
                            size="h-3"
                            class="w-full"
                        />
                        <span class="text-[11px] text-content-muted font-medium">{{ summary.percent }}% Completado</span>
                    </div>
                </div>
            </BaseCard>

            <UsageTipBanner module="achievements" />

            <!-- Category Filter Tabs -->
            <div class="flex gap-2 overflow-x-auto border-b border-border-interactive/50 pb-2 no-scrollbar">
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    class="whitespace-nowrap px-4 py-2 text-xs font-semibold rounded-xl transition-all"
                    :class="[
                        activeCategory === cat.id
                            ? 'bg-primary-strong text-white shadow-sm'
                            : 'bg-surface-raised/60 text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    ]"
                    @click="activeCategory = cat.id"
                >
                    {{ cat.label }}
                </button>
            </div>

            <!-- Achievements Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <BaseCard
                    v-for="ach in filteredAchievements"
                    :key="ach.id"
                    class="p-4 flex flex-col justify-between transition-all"
                    :class="[
                        ach.is_unlocked
                            ? 'border-primary-strong/40 bg-surface-raised/80 shadow-md ring-1 ring-primary-strong/20'
                            : 'opacity-60 bg-surface-raised/30 grayscale'
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl shadow-sm border"
                            :class="ach.is_unlocked ? 'bg-primary-strong/10 border-primary-strong/30' : 'bg-surface border-border-interactive'"
                        >
                            {{ ach.icon }}
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-sm text-content-primary">{{ ach.name }}</h3>
                            </div>
                            <p class="text-xs text-content-secondary leading-relaxed">{{ ach.description }}</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-border-interactive/30 flex items-center justify-between text-xs">
                        <span
                            class="rounded-full px-2.5 py-0.5 font-bold text-[11px]"
                            :class="ach.is_unlocked ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-surface text-content-muted border border-border-interactive'"
                        >
                            +{{ ach.xp_reward }} XP
                        </span>

                        <span v-if="ach.is_unlocked" class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1">
                            ✓ Desbloqueado ({{ ach.unlocked_at }})
                        </span>
                        <span v-else class="text-[11px] text-content-muted italic">
                            🔒 Bloqueado
                        </span>
                    </div>
                </BaseCard>
            </div>
        </div>
    </AppLayout>
</template>
