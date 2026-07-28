<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    progress: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl text-content-primary">Inicio</h1>

        <!--
            Tarjeta de progreso real (Fase 4, Gamification) — a propósito
            sin imagen de avatar: los 400 assets Funko Pop de
            docs/03-GAMIFICACION.md §2 no existen todavía (es un encargo de
            arte, no de código). El resto del layout del dashboard (saludo,
            avatar grande, villano, hábitos de hoy, pomodoro, misiones,
            ánimo) sigue en docs/04-DISENO-VISUAL.md §9 y se arma módulo por
            módulo según docs/13-ROADMAP.md — no antes.
        -->
        <BaseCard class="mb-6">
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
        </BaseCard>

        <BaseCard>
            <p class="text-content-secondary">Sesión iniciada correctamente.</p>
        </BaseCard>
    </AppLayout>
</template>
