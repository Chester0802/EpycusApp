<script setup>
import { computed } from 'vue'

const props = defineProps({
    value: { type: Number, default: undefined },
    current: { type: Number, default: undefined },
    max: { type: Number, default: 100 },
    color: { type: String, default: 'bg-primary-strong' },
    size: { type: String, default: undefined },
    height: { type: String, default: undefined },
    showLabel: { type: Boolean, default: false },
})

const actualValue = computed(() => props.value ?? props.current ?? 0)
const actualSize = computed(() => props.height ?? props.size ?? 'h-2.5')
const percentage = computed(() => {
    if (!props.max || props.max <= 0) return 0
    return Math.min(100, Math.max(0, (actualValue.value / props.max) * 100))
})
</script>

<template>
    <div class="w-full" role="progressbar" :aria-valuenow="actualValue" :aria-valuemax="max" :aria-valuemin="0">
        <div class="w-full bg-surface-sunken border border-border/50 rounded-full overflow-hidden" :class="actualSize">
            <div
                class="h-full transition-all duration-500 ease-out rounded-full"
                :class="color"
                :style="{ width: percentage + '%' }"
            />
        </div>
        <p v-if="showLabel" class="text-xs text-content-secondary mt-1">
            {{ actualValue }} / {{ max }}
        </p>
    </div>
</template>
