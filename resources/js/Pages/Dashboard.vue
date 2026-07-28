<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl text-content-primary">Inicio</h1>

        <!--
            Placeholder hasta que exista contenido real: el orden de bloques
            del dashboard (saludo, avatar, racha, villano, hábitos de hoy,
            pomodoro, misiones, ánimo) está en docs/04-DISENO-VISUAL.md §9 y
            se arma módulo por módulo según docs/13-ROADMAP.md — no antes.
        -->
        <BaseCard>
            <p class="text-content-secondary">Sesión iniciada correctamente.</p>
        </BaseCard>
    </AppLayout>
</template>
