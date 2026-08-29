<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col items-center gap-3 px-6 py-12 text-center">
        <div v-if="$slots.icon" class="text-content-muted" aria-hidden="true">
            <slot name="icon" />
        </div>
        <h3 class="text-lg text-content-primary">{{ title }}</h3>
        <p v-if="description" class="max-w-sm text-sm text-content-secondary">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-2">
            <slot name="action" />
        </div>
    </div>
</template>
