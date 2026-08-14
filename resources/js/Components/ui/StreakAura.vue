<script setup>
import { computed } from 'vue';

const props = defineProps({
    streak: {
        type: Number,
        default: 0,
    },
});

const isHot = computed(() => Number(props.streak) >= 3);
</script>

<template>
    <div v-if="isHot" class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-amber-500/20 via-orange-500/20 to-yellow-500/20 border border-orange-500/40 px-3 py-1 text-xs font-extrabold text-orange-400 shadow-sm shadow-orange-500/20 animate-pulse">
        <span class="text-sm">🔥</span>
        <span>Racha de {{ streak }} Días</span>
    </div>
</template>
