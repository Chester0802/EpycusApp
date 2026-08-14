<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    villain: { type: Object, default: null },
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    // Reemplaza espacio por 'T' si viene en formato "YYYY-MM-DD HH:mm:ss"
    const formatted = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T');
    const d = new Date(formatted);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('es-PE', { day: 'numeric', month: 'long' });
}

const dateRangeLabel = computed(() => {
    if (!props.villain) return '';
    const start = formatDate(props.villain.assigned_at);
    const end = formatDate(props.villain.expires_at);
    return `Semana: Del ${start} al ${end}`;
});
</script>

<template>
    <AppLayout title="Villano semanal">
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="text-center">
                <h1 class="font-display text-2xl font-bold text-content-primary">
                    Villano de la semana
                </h1>
                <p v-if="villain" class="mt-1 text-sm text-content-secondary">
                    Semana {{ villain.week_number }}
                </p>
                <p v-else class="mt-1 text-sm text-content-secondary">
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

            <BaseCard v-else class="space-y-4 p-6">
                <div class="flex flex-col items-center gap-4">
                    <img
                        :src="villain.image_url"
                        :alt="villain.name"
                        class="villain-idle h-40 w-40 rounded-xl object-contain"
                    />
                    <h2 class="font-display text-xl font-bold text-content-primary">
                        {{ villain.name }}
                    </h2>
                    <p class="max-w-md text-center text-content-secondary">
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

                <div v-if="villain.status === 'defeated'" class="py-2 text-center">
                    <span class="inline-flex items-center gap-2 text-lg font-bold text-success">
                        <AppIcon name="trophy" :size="20" class="text-warning" /> ¡Derrotado!
                    </span>
                </div>

                <div class="rounded-lg border border-border-interactive p-4">
                    <h3 class="mb-1 font-semibold text-content-primary">Debilidad</h3>
                    <p class="text-sm text-content-secondary">
                        {{ villain.weakness_description }}
                    </p>
                </div>

                <div class="text-center text-xs font-medium text-content-secondary">
                    {{ dateRangeLabel }}
                </div>
            </BaseCard>
        </div>
    </AppLayout>
</template>
