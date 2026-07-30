<script setup>
import { computed } from 'vue'

const props = defineProps({
    value: { type: Number, required: true },
    max: { type: Number, required: true },
    color: { type: String, default: 'bg-primary-strong' },
    size: { type: String, default: 'h-4' },
    showLabel: { type: Boolean, default: false },
})

const percentage = computed(() => Math.min(100, Math.max(0, (props.value / props.max) * 100)))
</script>

<template>
    <div class="w-full" role="progressbar" :aria-valuenow="value" :aria-valuemax="max" :aria-valuemin="0">
        <div class="w-full bg-bg rounded-full overflow-hidden" :class="size">
            <div
                class="h-full transition-all duration-500 ease-out rounded-full"
                :class="color"
                :style="{ width: percentage + '%' }"
            />
        </div>
        <p v-if="showLabel" class="text-xs text-content-secondary mt-1">
            {{ value }} / {{ max }}
        </p>
    </div>
</template>
