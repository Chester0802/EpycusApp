<script setup>
import { useTheme } from '@/composables/useTheme';

const { theme, setTheme } = useTheme();

function toggle() {
    setTheme(theme.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <button
        type="button"
        class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-content-secondary hover:text-content-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
        :aria-label="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        @click="toggle"
    >
        <!-- Sol: visible en oscuro (ofrece cambiar a claro) -->
        <svg v-if="theme === 'dark'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
        </svg>
        <!-- Luna: visible en claro (ofrece cambiar a oscuro) -->
        <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
        </svg>
    </button>
</template>
