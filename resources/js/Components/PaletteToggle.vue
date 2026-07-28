<script setup>
import { useTheme } from '@/composables/useTheme';

/*
 * Eje C: paleta de color (docs/04-DISENO-VISUAL.md §3). Device-only por
 * ahora, igual que el tema claro/oscuro — ver la nota en useTheme.js.
 */
const { palette, setPalette } = useTheme();

const options = [
    { value: 'kawaii', label: 'Kawaii' },
    { value: 'bosque', label: 'Bosque' },
    { value: 'oceano', label: 'Océano' },
    { value: 'nube', label: 'Nube' },
];
</script>

<template>
    <div class="flex items-center gap-2" role="radiogroup" aria-label="Paleta de colores">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="palette === option.value"
            class="min-h-[44px] rounded px-3 text-sm font-semibold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
            :class="
                palette === option.value
                    ? 'bg-primary text-on-primary'
                    : 'border border-border-interactive text-content-secondary hover:text-content-primary'
            "
            @click="setPalette(option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
