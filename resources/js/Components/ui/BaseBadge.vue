<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (v) => ['neutral', 'success', 'warning', 'danger', 'accent'].includes(v),
    },
});

const variantClasses = computed(() => ({
    neutral: 'bg-surface-raised text-content-secondary',
    success: 'bg-success text-on-accent',
    warning: 'bg-warning text-on-accent',
    danger: 'bg-danger text-on-accent',
    accent: 'bg-accent text-on-accent',
}[props.variant]));
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-sm px-2.5 py-1 text-xs font-semibold"
        :class="variantClasses"
    >
        <slot />
    </span>
</template>
