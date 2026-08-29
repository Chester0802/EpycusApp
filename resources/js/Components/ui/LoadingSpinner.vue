<script setup>
defineProps({
    label: { type: String, default: 'Cargando…' },
});
</script>

<template>
    <div class="flex items-center justify-center gap-3 py-8" role="status">
        <svg
            class="h-6 w-6 animate-spin text-primary-strong motion-reduce:animate-none"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
        </svg>
        <span class="text-sm text-content-secondary">{{ label }}</span>
    </div>
</template>
