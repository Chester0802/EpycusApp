<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard from '@/Components/ui/BaseCard.vue'
import BaseBadge from '@/Components/ui/BaseBadge.vue'
import {
    ArrowLeft,
    User,
    Timer,
    Target,
    Smile,
    MessageSquare,
    Trophy,
    Flame
} from '@lucide/vue'

const props = defineProps({
    user: Object,
    participant: Object,
    progress: Object,
    pomodoros: Array,
    missions: Array,
    journal: Array,
    aiQueries: Array,
})

function formatDate(dateString) {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(date)
}
</script>

<template>
    <Head :title="`Expediente: ${user.name}`" />

    <AppLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Encabezado -->
            <div class="flex items-center gap-4">
                <Link :href="route('admin.index')" class="p-2 hover:bg-surface-elevated rounded-full transition-colors text-content-tertiary hover:text-content-primary">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-content-primary flex items-center gap-2">
                        <User class="w-6 h-6 text-brand-primary" />
                        {{ user.name }}
                        <BaseBadge v-if="user.role === 'admin'" variant="danger">Admin</BaseBadge>
                    </h1>
                    <p class="text-content-secondary text-sm">
                        {{ user.email }} | Alias: {{ user.alias || 'No definido' }}
                    </p>
                </div>
            </div>

            <!-- Tarjetas Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <BaseCard class="p-4 flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-content-secondary">
                        <Trophy class="w-5 h-5 text-yellow-500" />
                        <span class="font-medium">Nivel y XP</span>
                    </div>
                    <p class="text-2xl font-bold text-content-primary">Lvl {{ progress?.level || 1 }}</p>
                    <p class="text-sm text-content-tertiary">{{ progress?.total_xp || 0 }} XP Totales</p>
                </BaseCard>

                <BaseCard class="p-4 flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-content-secondary">
                        <Flame class="w-5 h-5 text-orange-500" />
                        <span class="font-medium">Racha Actual</span>
                    </div>
                    <p class="text-2xl font-bold text-content-primary">{{ progress?.current_streak || 0 }} días</p>
                    <p class="text-sm text-content-tertiary">Max: {{ progress?.highest_streak || 0 }} días</p>
                </BaseCard>

                <BaseCard class="p-4 flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-content-secondary">
                        <Timer class="w-5 h-5 text-blue-500" />
                        <span class="font-medium">Pomodoros</span>
                    </div>
                    <p class="text-2xl font-bold text-content-primary">{{ pomodoros.length }}</p>
                    <p class="text-sm text-content-tertiary">Completados (histórico)</p>
                </BaseCard>

                <BaseCard class="p-4 flex flex-col gap-2">
                    <div class="flex items-center gap-2 text-content-secondary">
                        <Target class="w-5 h-5 text-purple-500" />
                        <span class="font-medium">Misiones</span>
                    </div>
                    <p class="text-2xl font-bold text-content-primary">{{ missions.length }}</p>
                    <p class="text-sm text-content-tertiary">Total creadas</p>
                </BaseCard>
            </div>

            <!-- Grillas de Detalles -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Pomodoros -->
                <BaseCard class="p-6 h-96 overflow-y-auto">
                    <h3 class="text-lg font-bold text-content-primary flex items-center gap-2 mb-4">
                        <Timer class="w-5 h-5" />
                        Últimos Pomodoros
                    </h3>
                    <div v-if="pomodoros.length === 0" class="text-content-secondary text-sm">No hay pomodoros registrados.</div>
                    <ul v-else class="space-y-3">
                        <li v-for="pom in pomodoros" :key="pom.id" class="p-3 bg-surface-elevated rounded-lg flex justify-between items-center">
                            <div>
                                <p class="font-medium text-content-primary">{{ pom.course_name || 'Sin curso' }}</p>
                                <p class="text-xs text-content-tertiary">{{ formatDate(pom.created_at) }}</p>
                            </div>
                            <BaseBadge :variant="pom.status === 'completed' ? 'success' : 'danger'">
                                {{ pom.planned_minutes }} min
                            </BaseBadge>
                        </li>
                    </ul>
                </BaseCard>

                <!-- Misiones -->
                <BaseCard class="p-6 h-96 overflow-y-auto">
                    <h3 class="text-lg font-bold text-content-primary flex items-center gap-2 mb-4">
                        <Target class="w-5 h-5" />
                        Últimas Misiones
                    </h3>
                    <div v-if="missions.length === 0" class="text-content-secondary text-sm">No hay misiones registradas.</div>
                    <ul v-else class="space-y-3">
                        <li v-for="mission in missions" :key="mission.id" class="p-3 bg-surface-elevated rounded-lg">
                            <div class="flex justify-between items-start mb-1">
                                <p class="font-medium text-content-primary">{{ mission.title }}</p>
                                <BaseBadge v-if="mission.completed_at" variant="success">Completada</BaseBadge>
                                <BaseBadge v-else variant="warning">Pendiente</BaseBadge>
                            </div>
                            <p class="text-xs text-content-tertiary">Curso: {{ mission.course_name || 'General' }} | Creada: {{ formatDate(mission.created_at) }}</p>
                        </li>
                    </ul>
                </BaseCard>

                <!-- Consultas IA -->
                <BaseCard class="p-6 h-96 overflow-y-auto lg:col-span-2">
                    <h3 class="text-lg font-bold text-content-primary flex items-center gap-2 mb-4">
                        <MessageSquare class="w-5 h-5" />
                        Consultas a Edy (IA)
                    </h3>
                    <div v-if="aiQueries.length === 0" class="text-content-secondary text-sm">El usuario no ha usado a Edy aún.</div>
                    <ul v-else class="space-y-4">
                        <li v-for="query in aiQueries" :key="query.id" class="p-4 bg-surface-elevated rounded-lg border border-border-subtle">
                            <p class="font-medium text-content-primary mb-2">"{{ query.content }}"</p>
                            <div class="flex gap-4 text-xs text-content-tertiary">
                                <span>Conv: {{ query.title }}</span>
                                <span>{{ formatDate(query.created_at) }}</span>
                            </div>
                        </li>
                    </ul>
                </BaseCard>
            </div>
        </div>
    </AppLayout>
</template>
