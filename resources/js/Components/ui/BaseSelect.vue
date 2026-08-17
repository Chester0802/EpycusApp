<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
    options: {
        type: Array,
        required: true,
        // cada opción: { value, label }
    },
    placeholder: { type: String, default: 'Selecciona una opción' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    // Variante compacta (Pomodoro/Index.vue): fila de label+select angosta,
    // pensada para 3+ selects lado a lado sin ocupar toda la pantalla. El
    // área táctil se mantiene en 44px (mínimo de la skill epycus-ui §11) —
    // "compacto" reduce texto y relleno horizontal, nunca la altura del
    // control, que es lo que garantiza que siga siendo tocable con el dedo.
    compact: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="id"
            class="mb-1.5 block font-semibold text-content-secondary"
            :class="compact ? 'text-xs' : 'text-sm'"
        >
            {{ label }}
            <span v-if="required" class="text-danger-text" aria-hidden="true">*</span>
        </label>
        <select
            :id="id"
            class="panel-sunken min-h-[44px] w-full rounded text-content-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
            :class="compact ? 'px-2.5 py-1.5 text-sm' : 'px-4 py-2.5 text-base'"
            :value="modelValue"
            :required="required"
            :aria-invalid="!!error"
            :aria-describedby="error ? `${id}-error` : undefined"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option value="" disabled>{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-danger-text">
            {{ error }}
        </p>
    </div>
</template>
