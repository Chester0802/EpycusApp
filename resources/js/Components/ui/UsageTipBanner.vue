<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    module: { type: String, required: true },
})

const tip = ref(null)
const isVisible = ref(false)

onMounted(async () => {
    try {
        const response = await axios.get(route('motivation.tip', props.module))
        if (response.data.success && response.data.data) {
            tip.value = response.data.data
            isVisible.value = true
        }
    } catch (err) {
        // Silencioso si no hay tip disponible
    }
})

async function dismiss() {
    if (!tip.value) return
    isVisible.value = false
    try {
        await axios.post(route('motivation.dismiss-tip'), {
            tip_id: tip.value.id,
        })
    } catch (err) {
        console.error('Error al descartar tip:', err)
    }
}
</script>

<template>
    <div
        v-if="isVisible && tip"
        class="mb-4 rounded-xl border border-primary-strong/30 bg-primary-strong/10 p-3 text-xs text-content-primary flex items-start justify-between gap-3 shadow-sm transition-all"
    >
        <div class="flex items-start gap-2.5">
            <span class="text-base shrink-0">💡</span>
            <div>
                <strong class="font-semibold text-primary-strong">Consejo práctico:</strong>
                <p class="mt-0.5 text-content-secondary leading-relaxed">{{ tip.content }}</p>
            </div>
        </div>
        <button
            type="button"
            class="text-content-muted hover:text-content-primary text-sm font-bold px-1.5 py-0.5 rounded hover:bg-surface-raised transition-colors shrink-0"
            title="Descartar consejo"
            @click="dismiss"
        >
            ✕
        </button>
    </div>
</template>
