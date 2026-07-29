<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    progress: {
        type: Object,
        required: true,
    },
    // Ya viene barajado desde el servidor (DashboardController) — una
    // recarga completa es una petición nueva, así que el orden cambia solo
    // con recargar, sin lógica extra acá (docs/04-DISENO-VISUAL.md, bloque
    // 1 de avatares).
    avatarImages: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl text-content-primary">Inicio</h1>

        <!--
            Bloque 1 de assets de avatar ya conectado (2026-07-28): Base
            (fase 1, todas las carreras) + Medicina/Tecnico desde fase 2.
            Faltan estilos (business/systems/law) y fases 5-10 — cuando
            lleguen, esta tarjeta no necesita cambios: AvatarAssetResolver
            ya hace fallback a lo que exista.
        -->
        <BaseCard class="mb-6">
            <div class="flex flex-col gap-6 sm:flex-row">
                <div
                    v-if="avatarImages.length > 0"
                    class="flex h-40 w-40 shrink-0 items-center justify-center self-center rounded-2xl bg-surface-raised p-2 sm:self-start"
                >
                    <img :src="avatarImages[0]" alt="Tu avatar" class="h-full w-full object-contain" />
                </div>

                <div class="flex-1">
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

                    <div v-if="avatarImages.length > 1" class="mt-4 flex gap-2">
                        <div
                            v-for="(img, i) in avatarImages.slice(1)"
                            :key="i"
                            class="flex h-14 w-14 items-center justify-center rounded-xl bg-surface-raised p-1"
                        >
                            <img :src="img" alt="" class="h-full w-full object-contain" />
                        </div>
                    </div>
                </div>
            </div>
        </BaseCard>

        <BaseCard>
            <p class="text-content-secondary">Sesión iniciada correctamente.</p>
        </BaseCard>
    </AppLayout>
</template>
