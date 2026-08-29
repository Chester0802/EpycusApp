<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'ghost', 'danger'].includes(v),
    },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    href: { type: String, default: null },
});

defineEmits(['click']);

const variantClasses = computed(() => ({
    primary:
        'bg-primary text-on-primary hover:bg-primary-strong hover:text-on-primary-strong',
    secondary: 'panel-raised text-content-primary hover:border-border-strong',
    ghost: 'bg-transparent text-content-primary border border-border-interactive hover:bg-surface',
    danger: 'bg-danger text-on-accent hover:brightness-95',
}[props.variant]));
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href ?? undefined"
        :type="href ? undefined : type"
        :disabled="!href && disabled"
        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-2 rounded px-4 py-2.5 font-sans text-base font-semibold transition-colors duration-150 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong focus-visible:ring-offset-2 focus-visible:ring-offset-bg disabled:cursor-not-allowed disabled:opacity-50"
        :class="variantClasses"
        @click="$emit('click', $event)"
    >
        <slot />
    </component>
</template>
