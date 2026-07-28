<script setup>
/*
 * Íconos de la navegación principal (docs/12-HISTORIAL.md, sesión de
 * rediseño de nav). stroke="currentColor" a propósito: heredan el color de
 * texto activo/inactivo del Link/span que los envuelve, sin hardcodear un
 * color propio (evita la Trampa de colores literales de la skill epycus-ui).
 */
defineProps({
    name: { type: String, required: true },
});
</script>

<template>
    <svg
        class="h-5 w-5 shrink-0"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="name === 'home'">
            <path d="M3 11.5 12 4l9 7.5" />
            <path d="M5.5 10v8.5a1 1 0 0 0 1 1H10v-5.5h4V19.5h3.5a1 1 0 0 0 1-1V10" />
        </template>
        <template v-else-if="name === 'habits'">
            <circle cx="12" cy="12" r="8.5" />
            <path d="m8.25 12.5 2.5 2.5 5-5.5" />
        </template>
        <template v-else-if="name === 'pomodoro'">
            <circle cx="12" cy="13.5" r="7.5" />
            <path d="M12 9.5v4l3 2M9.5 2.5h5" />
        </template>
        <template v-else-if="name === 'missions'">
            <circle cx="12" cy="12" r="8" />
            <circle cx="12" cy="12" r="4.5" />
            <circle cx="12" cy="12" r="0.75" fill="currentColor" stroke="none" />
        </template>
        <template v-else-if="name === 'user'">
            <circle cx="12" cy="8.5" r="3.25" />
            <path d="M5.25 19.5c1.2-3.6 3.8-5.5 6.75-5.5s5.55 1.9 6.75 5.5" />
        </template>
        <template v-else-if="name === 'settings'">
            <path d="M4 7h10M18 7h2" />
            <circle cx="16" cy="7" r="2" />
            <path d="M4 12h4M12 12h8" />
            <circle cx="10" cy="12" r="2" />
            <path d="M4 17h10M18 17h2" />
            <circle cx="16" cy="17" r="2" />
        </template>
    </svg>
</template>
