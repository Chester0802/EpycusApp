<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton from '@/Components/ui/BaseButton.vue'
import BaseCard from '@/Components/ui/BaseCard.vue'
import BaseInput from '@/Components/ui/BaseInput.vue'
import BaseModal from '@/Components/ui/BaseModal.vue'
import BaseSelect from '@/Components/ui/BaseSelect.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'

import { useTelemetry } from '@/Composables/useTelemetry'
const { track } = useTelemetry()

const flash = computed(() => usePage().props.flash)
const errors = computed(() => usePage().props.errors)

defineProps({
    sessions: { type: Object, required: true },
    activeSessions: { type: Array, default: () => [] },
})

const showCreateModal = ref(false)
const newSession = ref({ name: '', max_seats: 5, focus_minutes: 25, break_minutes: 5, cycles: 4 })

function createSession() {
    router.post(route('study-groups.store'), newSession.value, {
        onSuccess: () => {
            showCreateModal.value = false
            newSession.value = { name: '', max_seats: 5, focus_minutes: 25, break_minutes: 5, cycles: 4 }
        },
    })
}

function joinSession(id) {
    track('group_session.joined', 'study_groups', { session_id: id })
    router.post(route('study-groups.join', id))
}

function leaveSession(id) {
    track('group_session.left', 'study_groups', { session_id: id })
    router.post(route('study-groups.leave', id))
}

function goToSession(id) {
    router.get(route('study-groups.show', id))
}

function phaseLabel(session) {
    if (session.state === 'completed') return 'Completado'
    if (session.state === 'closed') return 'Cerrado'
    if (session.phase === 'focus') return `Enfoque ${session.current_cycle}/${session.cycles}`
    if (session.phase === 'break') return `Descanso ${session.current_cycle}/${session.cycles}`
    if (session.phase === 'idle') return 'Esperando inicio'
    return ''
}
</script>

<template>
    <AppLayout title="Sesiones de estudio">
        <div class="mx-auto max-w-4xl space-y-8">
            <BaseCard class="p-6">
                <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="font-display text-3xl font-bold tracking-tight text-content-primary">Sesiones de estudio</h1>
                        <p class="mt-1 text-sm text-content-secondary">
                            Estudia acompañado en tiempo real con hasta 4 compañeros más.
                        </p>
                    </div>
                    <BaseButton variant="primary" @click="showCreateModal = true">
                        + Crear sesión
                    </BaseButton>
                </header>
            </BaseCard>

            <BaseCard
                v-if="flash?.success"
                class="p-3 text-sm text-success-text"
            >
                {{ flash.success }}
            </BaseCard>
            <BaseCard
                v-if="flash?.error || errors?.error"
                class="p-3 text-sm text-danger-text"
            >
                {{ flash?.error || errors?.error }}
            </BaseCard>

            <!-- Caso 1: No hay ninguna sesión (ni activa del usuario ni abierta) -->
            <BaseCard
                v-if="activeSessions.length === 0 && (!sessions.open || sessions.open.length === 0)"
                class="p-6"
            >
                <EmptyState
                    title="No hay sesiones de estudio"
                    description="Crea una sesión de estudio e invita a tus compañeros para estudiar juntos."
                >
                    <template #action>
                        <BaseButton @click="showCreateModal = true">Crear primera sesión</BaseButton>
                    </template>
                </EmptyState>
            </BaseCard>

            <!-- Caso 2: Hay al menos una sesión activa o alguna sesión abierta -->
            <template v-else>
                <!-- Tus sesiones activas -->
                <div v-if="activeSessions.length > 0" class="space-y-3">
                    <h2 class="text-lg font-semibold text-content-primary">Tus sesiones activas</h2>
                    <BaseCard v-for="as in activeSessions" :key="as.id" class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-lg font-semibold text-content-primary">{{ as.name }}</p>
                                <p class="text-xs text-content-muted">
                                    {{ phaseLabel(as) }} · {{ as.participant_count }} participante{{ as.participant_count !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <BaseButton @click="goToSession(as.id)">Entrar</BaseButton>
                                <BaseButton variant="danger" @click="leaveSession(as.id)">Salir</BaseButton>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Sesiones abiertas disponibles para unirse -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-content-primary">Otras sesiones abiertas</h2>
                    <div v-if="sessions.open && sessions.open.length > 0" class="grid gap-4 sm:grid-cols-2">
                        <BaseCard v-for="session in sessions.open" :key="session.id" class="p-4">
                            <div class="flex items-start justify-between">
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-base font-semibold text-content-primary">{{ session.name }}</h3>
                                    <p class="mt-1 text-sm text-content-secondary">
                                        {{ session.participant_count }} / {{ session.max_seats }} participantes
                                    </p>
                                    <p class="text-xs text-content-muted">{{ phaseLabel(session) }}</p>
                                </div>
                                <BaseButton
                                    :disabled="session.participant_count >= session.max_seats"
                                    @click="joinSession(session.id)"
                                >
                                    {{ session.participant_count >= session.max_seats ? 'Completa' : 'Unirse' }}
                                </BaseButton>
                            </div>
                        </BaseCard>
                    </div>

                    <BaseCard v-else class="p-6 text-center">
                        <p class="text-sm text-content-secondary">
                            No hay más sesiones abiertas disponibles en este momento. Puedes crear una nueva con el botón superior.
                        </p>
                    </BaseCard>
                </div>
            </template>

            <BaseModal :show="showCreateModal" @close="showCreateModal = false">
                <template #title>Crear sesión de estudio</template>
                <form class="space-y-4" @submit.prevent="createSession">
                    <BaseInput
                        v-model="newSession.name"
                        label="Nombre de la sesión"
                        placeholder="Semana de parciales"
                        maxlength="80"
                        required
                    />
                    <BaseSelect
                        v-model.number="newSession.max_seats"
                        label="Participantes (máximo)"
                        :options="[
                            { value: 2, label: 'Tú + 1' },
                            { value: 3, label: 'Tú + 2' },
                            { value: 4, label: 'Tú + 3' },
                            { value: 5, label: 'Tú + 4' },
                        ]"
                    />
                    <div class="grid grid-cols-3 gap-3">
                        <BaseInput
                            v-model.number="newSession.focus_minutes"
                            label="Foco (min)"
                            type="number"
                            min="5"
                            max="120"
                        />
                        <BaseInput
                            v-model.number="newSession.break_minutes"
                            label="Descanso (min)"
                            type="number"
                            min="1"
                            max="30"
                        />
                        <BaseInput
                            v-model.number="newSession.cycles"
                            label="Ciclos"
                            type="number"
                            min="1"
                            max="20"
                        />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <BaseButton type="button" variant="ghost" @click="showCreateModal = false">Cancelar</BaseButton>
                        <BaseButton type="submit">Crear sesión</BaseButton>
                    </div>
                </form>
            </BaseModal>
        </div>
    </AppLayout>
</template>
