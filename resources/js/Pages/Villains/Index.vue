<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import ProgressBar from '@/Components/ui/ProgressBar.vue'

defineProps({
    villain: { type: Object, default: null },
})
</script>

<template>
    <AppLayout title="Villano semanal">
        <div class="max-w-2xl mx-auto space-y-6">
            <div v-if="!villain" class="text-center py-12">
                <div class="text-6xl mb-4">🛡️</div>
                <h2 class="text-xl font-display font-bold text-content-primary mb-2">
                    No hay villano activo
                </h2>
                <p class="text-content-secondary">
                    Esta semana no tienes ningún villano que enfrentar. ¡Disfruta el descanso!
                </p>
            </div>

            <div v-else class="space-y-6">
                <div class="text-center">
                    <h1 class="text-2xl font-display font-bold text-content-primary">
                        Villano de la semana
                    </h1>
                    <p class="text-sm text-content-secondary">
                        Semana {{ villain.week_number }}
                    </p>
                </div>

                <div class="panel-raised p-6 space-y-4">
                    <div class="flex flex-col items-center gap-4">
                        <img
                            :src="villain.image_url"
                            :alt="villain.name"
                            class="w-40 h-40 object-contain rounded-xl"
                        />
                        <h2 class="text-xl font-display font-bold text-content-primary">
                            {{ villain.name }}
                        </h2>
                        <p class="text-content-secondary text-center max-w-md">
                            {{ villain.description }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between text-sm text-content-secondary">
                            <span>HP del villano</span>
                            <span>{{ villain.remaining_hp }} / {{ villain.total_hp }}</span>
                        </div>
                        <ProgressBar
                            :value="villain.remaining_hp"
                            :max="villain.total_hp"
                            :color="villain.status === 'defeated' ? 'bg-success' : 'bg-danger'"
                            size="h-5"
                        />
                    </div>

                    <div v-if="villain.status === 'defeated'" class="text-center py-2">
                        <span class="inline-flex items-center gap-2 text-success font-bold text-lg">
                            <span>🏆</span> ¡Derrotado!
                        </span>
                    </div>

                    <div class="p-4 bg-bg rounded-lg">
                        <h3 class="font-semibold text-content-primary mb-1">Debilidad</h3>
                        <p class="text-content-secondary text-sm">
                            {{ villain.weakness_description }}
                        </p>
                    </div>

                    <div class="text-xs text-content-secondary text-center">
                        Asignado: {{ villain.assigned_at }} · Expira: {{ villain.expires_at }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
