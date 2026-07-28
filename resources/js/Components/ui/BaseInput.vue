<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    autocomplete: { type: String, default: null },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label :for="id" class="mb-1.5 block text-sm font-semibold text-content-secondary">
            {{ label }}
            <span v-if="required" class="text-danger-text" aria-hidden="true">*</span>
        </label>
        <input
            :id="id"
            class="panel-sunken min-h-[44px] w-full rounded px-4 py-2.5 text-base text-content-primary placeholder:text-content-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
            :type="type"
            :value="modelValue"
            :required="required"
            :autocomplete="autocomplete"
            :aria-invalid="!!error"
            :aria-describedby="error ? `${id}-error` : undefined"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-danger-text">
            {{ error }}
        </p>
    </div>
</template>
