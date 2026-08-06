<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    autocomplete: { type: String, default: null },
});

defineEmits(['update:modelValue']);

const isPassword = computed(() => props.type === 'password');
const showPassword = ref(false);
const inputType = computed(() => (isPassword.value && showPassword.value ? 'text' : props.type));
</script>

<template>
    <div>
        <label :for="id" class="mb-1.5 block text-sm font-semibold text-content-secondary">
            {{ label }}
            <span v-if="required" class="text-danger-text" aria-hidden="true">*</span>
        </label>
        <div class="relative">
            <input
                :id="id"
                class="panel-sunken min-h-[44px] w-full rounded px-4 py-2.5 text-base text-content-primary placeholder:text-content-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                :class="isPassword ? 'pr-12' : ''"
                :type="inputType"
                :value="modelValue"
                :required="required"
                :autocomplete="autocomplete"
                :aria-invalid="!!error"
                :aria-describedby="error ? `${id}-error` : undefined"
                @input="$emit('update:modelValue', $event.target.value)"
            />
            <button
                v-if="isPassword"
                type="button"
                class="absolute right-1 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition-colors hover:text-primary-strong focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                @click="showPassword = !showPassword"
            >
                <svg
                    v-if="!showPassword"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                <svg
                    v-else
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                    <path
                        d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"
                    />
                    <path
                        d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"
                    />
                    <path d="m2 2 20 20" />
                </svg>
            </button>
        </div>
        <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-danger-text">
            {{ error }}
        </p>
    </div>
</template>
