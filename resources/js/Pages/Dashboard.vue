<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    progress: {
        type: Object,
        required: true,
    },
    // Un solo personaje (orden=1 para Dashboard), fase al azar — ya
    // resuelto en el servidor por AvatarAssetResolver. Corrección del
    // usuario: la primera versión mostraba 4 imágenes juntas y no le
    // gustó; ahora es una sola, distinta cada vez que se recarga la
    // página (docs/04-DISENO-VISUAL.md, bloque 1 de avatares).
    avatarImage: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl text-content-primary">Inicio</h1>

        <BaseCard class="mb-6">
            <div class="flex flex-col items-center gap-6 sm:flex-row">
                <div
                    v-if="avatarImage"
                    class="flex h-44 w-32 shrink-0 items-center justify-center rounded-2xl bg-surface-raised p-2"
                >
                    <img :src="avatarImage" alt="Tu avatar" class="h-full w-full object-contain" />
                </div>

                <div class="w-full flex-1">
                    <h2 class="mb-4 font-display text-lg text-content-primary">Tu progreso</h2>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">Nivel</p>
                            <p class="font-display text-2xl text-content-primary">{{ progress.level }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">Fase</p>
                            <p class="font-display text-2xl text-content-primary">{{ progress.phase }}/10</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">XP total</p>
                            <p class="font-display text-2xl text-content-primary">{{ progress.totalXp }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-content-muted">Racha</p>
                            <p class="font-display text-2xl text-content-primary">{{ progress.currentStreak }} 🔥</p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-content-secondary">{{ progress.coins }} monedas acumuladas.</p>
                </div>
            </div>
        </BaseCard>

        <BaseCard>
            <p class="text-content-secondary">Sesión iniciada correctamente.</p>
        </BaseCard>
    </AppLayout>
</template>
