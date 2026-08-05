<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseCard from '@/Components/ui/BaseCard.vue'
import ProgressBar from '@/Components/ui/ProgressBar.vue'
import EmptyState from '@/Components/ui/EmptyState.vue'
import AppIcon from '@/Components/AppIcon.vue'

defineProps({
    villain: { type: Object, default: null },
})
</script>

<template>
    <AppLayout title="Villano semanal">
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="text-center">
                <h1 class="text-2xl font-display font-bold text-content-primary">
                    Villano de la semana
                </h1>
                <p v-if="villain" class="text-sm text-content-secondary mt-1">
                    Semana {{ villain.week_number }}
                </p>
                <p v-else class="text-sm text-content-secondary mt-1">
                    Desafío semanal
                </p>
            </div>

            <BaseCard v-if="!villain" class="p-6">
                <EmptyState
                    title="No hay villano activo"
                    description="Esta semana no tienes ningún villano asignado que enfrentar. Cumple tus hábitos y mantente listo para el siguiente desafío."
                >
                    <template #icon>
                        <AppIcon name="shield" :size="56" class="text-content-muted" />
                    </template>
                </EmptyState>
            </BaseCard>

            <BaseCard v-else class="p-6 space-y-4">
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
                        <AppIcon name="trophy" :size="20" class="text-warning" /> ¡Derrotado!
                    </span>
                </div>

                <div class="p-4 rounded-lg border border-border-interactive">
                    <h3 class="font-semibold text-content-primary mb-1">Debilidad</h3>
                    <p class="text-content-secondary text-sm">
                        {{ villain.weakness_description }}
                    </p>
                </div>

                <div class="text-xs text-content-secondary text-center">
                    Asignado: {{ villain.assigned_at }} · Expira: {{ villain.expires_at }}
                </div>
            </BaseCard>
        </div>
    </AppLayout>
</template>
