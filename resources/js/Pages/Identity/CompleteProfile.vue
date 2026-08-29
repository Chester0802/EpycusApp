<script setup>
import { computed, watch } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    careers: { type: Object, required: true }, // { health: [...], ... }
    cycles: { type: Array, required: true }, // [1, ..., 10]
    institutionTypes: { type: Array, required: true }, // ['universidad', 'instituto']
    userAlias: { type: String, default: '' },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// Mapa de estilo de avatar por nombre de carrera
const styleByCareer = computed(() => {
    const map = {};
    for (const [style, list] of Object.entries(props.careers)) {
        for (const name of list) {
            map[name] = style;
        }
    }
    return map;
});

// Opciones planas para el BaseSelect de carrera
const careerOptions = computed(() =>
    Object.entries(props.careers).flatMap(([style, list]) =>
        list.map((name) => ({ value: name, label: name, group: style })),
    ),
);

// Opciones de ciclo
const cycleOptions = computed(() =>
    props.cycles.map((c) => ({ value: String(c), label: `Ciclo ${c}` })),
);

// Opciones de tipo de institución
const institutionOptions = computed(() =>
    props.institutionTypes.map((t) => ({
        value: t,
        label: t.charAt(0).toUpperCase() + t.slice(1),
    })),
);

const form = useForm({
    alias: props.userAlias || currentUser.value?.alias || '',
    career: '',
    avatar_style: '', // se actualiza automáticamente al elegir carrera
    avatar_gender: 'm',
    cycle: '',
    institution_type: '',
});

const generateAlias = () => {
    const userName = currentUser.value?.name || '';
    const firstWord = userName
        ? userName
              .trim()
              .split(' ')[0]
              .toLowerCase()
              .replace(/[^a-z0-9]/g, '')
        : 'estudiante';
    const base = firstWord.length >= 2 ? firstWord : 'estudiante';
    const randomCode = Math.floor(1000 + Math.random() * 9000);
    form.alias = `${base}-${randomCode}`;
};

// Sincronizar avatar_style con la carrera seleccionada
watch(
    () => form.career,
    (newCareer) => {
        form.avatar_style = styleByCareer.value[newCareer] ?? '';
    },
);

const submit = () => {
    form.patch(route('profile.complete'), {
        preserveScroll: true,
    });
};

const genderOptions = [
    { value: 'm', label: 'Masculino', emoji: '🧑' },
    { value: 'f', label: 'Femenino', emoji: '👩' },
];
</script>

<template>
    <Head title="Completar perfil" />

    <GuestLayout>
        <template #tagline>
            <p class="text-sm font-medium text-content-secondary">Último paso antes de empezar</p>
        </template>

        <h1 class="mb-2 font-display text-2xl font-bold text-content-primary">
            Completa tu perfil
        </h1>
        <p class="mb-6 text-sm text-content-secondary">
            Personaliza tus datos de estudiante para configurar tu carnet y ranking.
        </p>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <!-- Alias público / Apodo con generador interactivo -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="alias" class="block text-sm font-semibold text-content-secondary">
                        Alias público / Apodo (visible en el ranking)
                        <span class="text-danger-text">*</span>
                    </label>
                    <button
                        type="button"
                        class="text-xs font-semibold text-primary-strong hover:underline focus-visible:outline-none"
                        @click="generateAlias"
                    >
                        ⚡ Generar alias
                    </button>
                </div>
                <BaseInput
                    id="alias"
                    v-model="form.alias"
                    type="text"
                    placeholder="Ej. marco-9482"
                    :error="form.errors.alias"
                    required
                />
            </div>

            <!-- Institución -->
            <BaseSelect
                id="institution_type"
                v-model="form.institution_type"
                label="Tipo de institución"
                :options="institutionOptions"
                placeholder="¿Estudias en una…?"
                :error="form.errors.institution_type"
                required
            />

            <!-- Carrera -->
            <BaseSelect
                id="career"
                v-model="form.career"
                label="Carrera profesional"
                :options="careerOptions"
                placeholder="Selecciona tu carrera"
                :error="form.errors.career"
                required
            />

            <!-- Ciclo -->
            <BaseSelect
                id="cycle"
                v-model="form.cycle"
                label="Ciclo académico actual"
                :options="cycleOptions"
                placeholder="¿En qué ciclo estás?"
                :error="form.errors.cycle"
                required
            />

            <!-- Género del avatar -->
            <div>
                <p class="mb-1 text-sm font-semibold text-content-secondary">
                    Género del avatar
                    <span class="text-danger-text" aria-hidden="true">*</span>
                </p>
                <p class="mb-2.5 text-xs text-content-muted">
                    Aspecto visual de tu personaje en el carnet.
                </p>
                <div class="flex gap-3" role="radiogroup" aria-label="Género del avatar">
                    <button
                        v-for="opt in genderOptions"
                        :key="opt.value"
                        type="button"
                        role="radio"
                        :aria-checked="form.avatar_gender === opt.value"
                        class="flex min-h-[52px] flex-1 flex-col items-center justify-center gap-1 rounded-xl border transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                        :class="
                            form.avatar_gender === opt.value
                                ? 'bg-primary border-primary-strong text-on-primary'
                                : 'border-border-interactive text-content-secondary hover:bg-surface-raised'
                        "
                        @click="form.avatar_gender = opt.value"
                    >
                        <span class="text-xl" aria-hidden="true">{{ opt.emoji }}</span>
                        <span class="text-xs font-semibold">{{ opt.label }}</span>
                    </button>
                </div>
                <p
                    v-if="form.errors.avatar_gender"
                    id="avatar_gender-error"
                    class="mt-1.5 text-sm text-danger-text"
                >
                    {{ form.errors.avatar_gender }}
                </p>
            </div>

            <!-- Campo oculto — avatar_style -->
            <input type="hidden" name="avatar_style" :value="form.avatar_style" />

            <!-- Errores globales -->
            <div
                v-if="form.errors.avatar_style"
                class="rounded-lg bg-danger/20 px-4 py-3 text-sm text-danger-text"
                role="alert"
            >
                {{ form.errors.avatar_style }}
            </div>

            <BaseButton type="submit" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Guardando…' : 'Guardar y comenzar 🚀' }}
            </BaseButton>
        </form>
    </GuestLayout>
</template>
