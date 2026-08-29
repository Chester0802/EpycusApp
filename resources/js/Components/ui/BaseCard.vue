<script setup>
import { computed } from 'vue';

const props = defineProps({
    elevation: {
        type: String,
        default: 'raised',
        validator: (v) => ['flat', 'raised'].includes(v),
    },
});

const elevationClass = computed(() => (props.elevation === 'raised' ? 'panel-raised' : 'panel-flat'));
</script>

<template>
    <div class="rounded-lg p-6" :class="elevationClass">
        <slot />
    </div>
</template>
