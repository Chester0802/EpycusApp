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
        <template v-else-if="name === 'planner' || name === 'day-planner'">
            <path d="M9 11l3 3L22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
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
        <template v-else-if="name === 'wellbeing'">
            <path d="M20.84 5.27a4.5 4.5 0 0 0-6.36 0L12 7.75l-2.48-2.48a4.5 4.5 0 0 0-6.36 6.36L12 20.47l8.84-8.84a4.5 4.5 0 0 0 0-6.36Z" />
        </template>
        <template v-else-if="name === 'calendar'">
            <rect x="3" y="4.5" width="18" height="17" rx="2" />
            <path d="M3 9.5h18M8.5 2.5V6M15.5 2.5V6" />
            <path d="M8.5 13.5h.01M12 13.5h.01M15.5 13.5h.01M8.5 16.5h.01M12 16.5h.01M15.5 16.5h.01" stroke-width="1.5" />
        </template>
        <template v-else-if="name === 'book' || name === 'courses' || name === 'course' || name === 'book-open'">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
        </template>
        <template v-else-if="name === 'brain' || name === 'learning' || name === 'aprendizaje'">
            <path d="M12 18V5" />
            <path d="M15 13a4.17 4.17 0 0 1-3-4 4.17 4.17 0 0 1-3 4" />
            <path d="M17.6 6.5A3 3 0 1 0 12 5a3 3 0 1 0-5.6 1.5" />
            <path d="M18 18a4 4 0 0 0 2-7.5" />
            <path d="M20 17.5A4 4 0 1 1 12 18a4 4 0 1 1-8-.5" />
            <path d="M6 18a4 4 0 0 1-2-7.5" />
        </template>
        <template v-else-if="name === 'villains'">
            <path d="M8.5 3.5 6 7.5l.5 2.5-2 3.5 1 5.5h13l1-5.5-2-3.5.5-2.5-2.5-4" />
            <path d="M9.5 12a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" />
            <path d="M14.5 12a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" />
            <path d="M9 9.5c1-.5 5-.5 6 0" stroke-width="1.5" />
        </template>
        <template v-else-if="name === 'groups'">
            <circle cx="12" cy="6.5" r="2.25" />
            <circle cx="19" cy="8.5" r="2" />
            <circle cx="5" cy="8.5" r="2" />
            <path d="M7.5 17.5c0-2.5 2-4.5 4.5-4.5s4.5 2 4.5 4.5" />
            <path d="M2.5 19c.8-1.8 2.5-3 4.8-3" />
            <path d="M21.5 19c-.8-1.8-2.5-3-4.8-3" />
        </template>
        <template v-else-if="name === 'ai' || name === 'bot' || name === 'robot' || name === 'edy-ai'">
            <rect width="18" height="12" x="3" y="6" rx="2" />
            <path d="M12 2v4" />
            <path d="M2 14h2" />
            <path d="M20 14h2" />
            <path d="M15 13v2" />
            <path d="M9 13v2" />
        </template>
        <template v-else-if="name === 'ranking'">
            <path d="M6 9v11M12 4v16M18 13v7" />
        </template>
        <template v-else-if="name === 'achievements'">
            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
            <path d="M4 22h16" />
            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
            <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
        </template>
        <template v-else-if="name === 'shop' || name === 'store'">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
            <path d="M3 6h18" />
            <path d="M16 10a4 4 0 0 1-8 0" />
        </template>
        <template v-else-if="name === 'finance' || name === 'money'">
            <line x1="12" x2="12" y1="2" y2="22" />
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
        </template>
        <template v-else-if="name === 'fitness' || name === 'workout' || name === 'exercise'">
            <path d="m6.5 6.5 11 11" />
            <path d="m21 21-1-1" />
            <path d="m3 3 1 1" />
            <path d="m18 22 4-4" />
            <path d="m2 6 4-4" />
            <path d="m3 10 7-7" />
            <path d="m14 21 7-7" />
        </template>
        <template v-else-if="name === 'readings' || name === 'library'">
            <path d="m16 6 4 14" />
            <path d="M12 6v14" />
            <path d="M8 8v12" />
            <path d="M4 4v16" />
        </template>
        <template v-else-if="name === 'skills' || name === 'destrezas'">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
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
