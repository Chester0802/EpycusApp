<script setup>
import { useTheme } from '@/composables/useTheme';

/*
 * Control mínimo para el Eje A (docs/04-DISENO-VISUAL.md §1). La pantalla
 * de Ajustes completa (con el resto de UserPreferences) es de la Fase 1
 * de docs/13-ROADMAP.md — esto es lo mínimo para que el sistema de
 * superficie sea end-to-end verificable desde ya.
 */
const { surface, setSurface } = useTheme();

const options = [
    { value: 'neumorphism', label: 'Neumorfismo' },
    { value: 'glass', label: 'Vidrio' },
];
</script>

<template>
    <div class="flex items-center gap-2" role="radiogroup" aria-label="Modo de superficie">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="surface === option.value"
            class="min-h-[44px] rounded px-3 text-sm font-semibold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
            :class="
                surface === option.value
                    ? 'bg-primary text-on-primary'
                    : 'border border-border-interactive text-content-secondary hover:text-content-primary'
            "
            @click="setSurface(option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
