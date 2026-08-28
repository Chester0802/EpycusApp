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
    <AppLayout title="Metaverso de Estudio">
        <div class="mx-auto max-w-5xl space-y-8 px-4 py-8">
            <header class="text-center mb-12">
                <h1 class="font-display text-4xl sm:text-5xl font-black tracking-tight text-content-primary mb-4">
                    El Metaverso de Estudio
                </h1>
                <p class="text-base sm:text-lg text-content-secondary max-w-2xl mx-auto">
                    Únete a una sala virtual, arrastra tu silla donde quieras y estudia enfocado junto a otros compañeros. Tu reloj Pomodoro es 100% independiente.
                </p>
            </header>

            <BaseCard v-if="flash?.error || errors?.error" class="p-4 text-sm text-white bg-danger mb-6 rounded-xl font-bold">
                {{ flash?.error || errors?.error }}
            </BaseCard>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Tarjetas de las Salas Maestras -->
                <div 
                    v-for="session in sessions" 
                    :key="session.id" 
                    class="relative rounded-3xl overflow-hidden group border border-border-interactive shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300"
                >
                    <!-- Imagen de Fondo -->
                    <img :src="session.image" :alt="session.name" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                    
                    <!-- Gradiente Oscuro -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                    
                    <!-- Contenido -->
                    <div class="relative p-6 sm:p-8 h-80 flex flex-col justify-end">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-3xl font-black text-white drop-shadow-md">{{ session.name }}</h2>
                            <div class="flex items-center gap-1.5 px-3 py-1 bg-black/40 backdrop-blur-md rounded-full border border-white/10 text-white font-medium text-sm shadow-inner">
                                👥 {{ session.participants_count }} / {{ session.max_seats }}
                            </div>
                        </div>
                        <p class="text-sm text-white/80 mb-6 drop-shadow-sm font-medium">
                            {{ session.id === 1 ? 'El murmullo de una cafetería, ideal para trabajo creativo.' : 'Silencio absoluto, ideal para estudio profundo.' }}
                        </p>
                        
                        <div class="flex items-center gap-3">
                            <BaseButton 
                                class="w-full justify-center !bg-white !text-black hover:!bg-white/90 shadow-xl"
                                @click="joinSession(session.id)"
                            >
                                Entrar a la Sala
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
