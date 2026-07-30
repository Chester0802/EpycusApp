<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard from '@/Components/ui/BaseCard.vue'
import BaseBadge from '@/Components/ui/BaseBadge.vue'
import { useTelemetry } from '@/Composables/useTelemetry'

const props = defineProps({
    ranking: { type: Array, default: () => [] },
    ownPosition: { type: Object, required: true },
    avatarImage: { type: String, default: null },
})

const { track } = useTelemetry()

// Telemetría obligatoria por docs/01-MODULOS.md §9.3
track('ranking.viewed', 'ranking', {
    own_rank: props.ownPosition.rank,
    total_participants: props.ownPosition.total_participants,
})

const topThree = computed(() => props.ranking.slice(0, 3))
</script>

<template>
    <Head title="Tabla de Posiciones — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header Card -->
            <BaseCard class="p-6">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            v-if="avatarImage"
                            class="flex h-32 w-20 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-1 border border-border-interactive shadow-sm"
                        >
                            <img :src="avatarImage" alt="Avatar" class="h-full w-full object-contain" />
                        </div>
                        <div>
                            <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">
                                Tabla de Posiciones 🏆
                            </h1>
                            <p class="mt-1 text-sm text-content-secondary">
                                Revisa el progreso del grupo y celebra la constancia colectiva.
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold text-content-primary">
                                <span class="rounded-full bg-primary-strong/10 px-3 py-1 text-primary-strong border border-primary-strong/20">
                                    Tu posición: #{{ ownPosition.rank }} de {{ ownPosition.total_participants }}
                                </span>
                                <span class="rounded-full bg-surface-raised px-3 py-1 border border-border-interactive">
                                    Nivel {{ ownPosition.level }}
                                </span>
                                <span class="rounded-full bg-surface-raised px-3 py-1 border border-border-interactive">
                                    🔥 {{ ownPosition.current_streak }} días
                                </span>
                            </div>
                        </div>
                    </div>
                </header>
            </BaseCard>

            <!-- Nota pedagógica (docs/01-MODULOS.md §9.1) -->
            <BaseCard class="border-l-4 border-l-primary p-4 text-sm text-content-secondary">
                💡 <strong class="text-content-primary">Enfoque personal:</strong> El ranking es solo una referencia colectiva. Tu propio progreso diario frente a ti mismo es lo que realmente importa.
            </BaseCard>

            <!-- Podio de los 3 Primeros Lugares -->
            <div v-if="topThree.length >= 3" class="grid grid-cols-3 gap-3 sm:gap-4 items-end pt-2">
                <!-- 2do Lugar (Izquierda) -->
                <BaseCard class="p-4 text-center border-t-4 border-t-slate-400 order-1 space-y-2">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-400/20 text-slate-300 font-bold text-lg">
                        🥈
                    </div>
                    <p class="font-bold text-sm text-content-primary truncate">{{ topThree[1].name }}</p>
                    <p class="text-xs text-content-muted">Nivel {{ topThree[1].level }}</p>
                    <p class="font-display font-bold text-primary-strong text-base">{{ topThree[1].total_xp }} XP</p>
                </BaseCard>

                <!-- 1er Lugar (Centro, más alto) -->
                <BaseCard class="p-5 text-center border-t-4 border-t-amber-400 order-2 space-y-2 -mt-4 shadow-lg">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-amber-400/20 text-amber-300 font-bold text-2xl">
                        🥇
                    </div>
                    <p class="font-bold text-base text-content-primary truncate">{{ topThree[0].name }}</p>
                    <p class="text-xs text-content-muted">Nivel {{ topThree[0].level }} · Fase {{ topThree[0].phase }}</p>
                    <p class="font-display font-bold text-amber-400 text-lg">{{ topThree[0].total_xp }} XP</p>
                </BaseCard>

                <!-- 3er Lugar (Derecha) -->
                <BaseCard class="p-4 text-center border-t-4 border-t-amber-700 order-3 space-y-2">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-700/20 text-amber-600 font-bold text-lg">
                        🥉
                    </div>
                    <p class="font-bold text-sm text-content-primary truncate">{{ topThree[2].name }}</p>
                    <p class="text-xs text-content-muted">Nivel {{ topThree[2].level }}</p>
                    <p class="font-display font-bold text-primary-strong text-base">{{ topThree[2].total_xp }} XP</p>
                </BaseCard>
            </div>

            <!-- Tabla General de Posiciones -->
            <BaseCard class="overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-content-secondary">
                        <thead class="bg-surface-raised text-xs uppercase tracking-wider text-content-muted border-b border-border-interactive">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-center w-16">#</th>
                                <th scope="col" class="px-4 py-3">Estudiante</th>
                                <th scope="col" class="px-4 py-3 text-center">Nivel</th>
                                <th scope="col" class="px-4 py-3 text-center">Racha</th>
                                <th scope="col" class="px-4 py-3 text-right">XP Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-interactive">
                            <tr
                                v-for="user in ranking"
                                :key="user.user_id"
                                class="transition-colors"
                                :class="user.user_id === ownPosition.user_id ? 'bg-primary-strong/10 font-semibold text-content-primary border-l-4 border-l-primary-strong' : 'hover:bg-surface-raised/50'"
                            >
                                <td class="px-4 py-3 text-center font-bold">
                                    <span v-if="user.rank === 1">🥇</span>
                                    <span v-else-if="user.rank === 2">🥈</span>
                                    <span v-else-if="user.rank === 3">🥉</span>
                                    <span v-else>#{{ user.rank }}</span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span>{{ user.name }}</span>
                                        <BaseBadge v-if="user.user_id === ownPosition.user_id" variant="primary" class="text-[10px]">
                                            Tú
                                        </BaseBadge>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="rounded bg-surface-raised px-2 py-0.5 text-xs font-semibold text-content-primary">
                                        Nivel {{ user.level }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 text-xs">
                                        🔥 {{ user.current_streak }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right font-display font-bold text-content-primary">
                                    {{ user.total_xp }} XP
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>
        </div>
    </AppLayout>
</template>
