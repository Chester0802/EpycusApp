<script setup>
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import { Head, useForm } from '@inertiajs/vue3';

/*
 * Pantalla de completar perfil — Fase 1 (crítica).
 * ProfileController::edit() ya intentaba renderizar 'Identity/CompleteProfile'
 * pero la vista no existía, causando un error 500 en cada intento de acceder
 * a /profile/complete. Esta pantalla la crea por primera vez.
 *
 * Datos que recibe del backend (via Inertia props):
 *   - careers: { health: [...], business: [...], ... }  ← Career::groupedByStyle()
 *   - cycles: [1, 2, ..., 10]
 *   - institutionTypes: ['universidad', 'instituto']
 *
 * Los campos avatar_style y avatar_gender NO se muestran como selects sueltos:
 * el estilo se deriva automáticamente de la carrera elegida (Career::avatarStyle()),
 * y el género del avatar se elige como dos botones visuales — no como un
 * <select> con "m/f" que sería confuso y técnicamente incorrecto.
 *
 * Regla de validación del backend (CompleteProfileRequest):
 *   career: in (lista cerrada) | avatar_style: in health/business/technical/systems/law
 *   avatar_gender: in m/f | cycle: int 1-10 | institution_type: in universidad/instituto
 */

const props = defineProps({
    careers: { type: Object, required: true },   // { health: [...], ... }
    cycles: { type: Array, required: true },       // [1, ..., 10]
    institutionTypes: { type: Array, required: true }, // ['universidad', 'instituto']
});

// Mapa de estilo de avatar por nombre de carrera (derivado del mismo
// Career::groupedByStyle() que ya tiene el backend — no duplicar la lógica).
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
        list.map((name) => ({ value: name, label: name, group: style }))
    )
);

// Opciones de ciclo
const cycleOptions = computed(() =>
    props.cycles.map((c) => ({ value: String(c), label: `Ciclo ${c}` }))
);

// Opciones de tipo de institución
const institutionOptions = computed(() =>
    props.institutionTypes.map((t) => ({
        value: t,
        label: t.charAt(0).toUpperCase() + t.slice(1),
    }))
);

const form = useForm({
    career: '',
    avatar_style: '',   // se actualiza automáticamente al elegir carrera
    avatar_gender: '',
    cycle: '',
    institution_type: '',
});

// Sincronizar avatar_style con la carrera seleccionada
watch(
    () => form.career,
    (newCareer) => {
        form.avatar_style = styleByCareer.value[newCareer] ?? '';
    }
);

const submit = () => {
    form.patch(route('profile.complete'), {
        preserveScroll: true,
    });
};

// Etiquetas de género de avatar — legibles, no los valores internos 'm/f'
const genderOptions = [
    { value: 'm', label: 'Masculino', emoji: '🧑' },
    { value: 'f', label: 'Femenino', emoji: '👩' },
];
</script>

<template>
    <Head title="Completar perfil" />

    <AppLayout>
        <div class="mx-auto max-w-lg">
            <h1 class="mb-2 font-display text-3xl text-content-primary">
                Completa tu perfil
            </h1>
            <p class="mb-8 text-content-secondary">
                Esta información es necesaria para el estudio. Solo la verá el equipo investigador.
            </p>

            <form class="space-y-6" novalidate @submit.prevent="submit">
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
                    label="Carrera"
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

                <!-- Género del avatar — botones visuales, no select "m/f" -->
                <div>
                    <p class="mb-1.5 text-sm font-semibold text-content-secondary">
                        Género del avatar
                        <span class="text-danger-text" aria-hidden="true">*</span>
                    </p>
                    <p class="mb-3 text-xs text-content-muted">
                        Solo afecta el aspecto de tu personaje Funko Pop, no tiene ningún otro significado en el estudio.
                    </p>
                    <div class="flex gap-3" role="radiogroup" aria-label="Género del avatar">
                        <button
                            v-for="opt in genderOptions"
                            :key="opt.value"
                            type="button"
                            role="radio"
                            :aria-checked="form.avatar_gender === opt.value"
                            class="flex min-h-[60px] flex-1 flex-col items-center justify-center gap-1 rounded-xl border transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                            :class="
                                form.avatar_gender === opt.value
                                    ? 'bg-primary border-primary-strong text-on-primary'
                                    : 'border-border-interactive text-content-secondary hover:bg-surface-raised'
                            "
                            @click="form.avatar_gender = opt.value"
                        >
                            <span class="text-2xl" aria-hidden="true">{{ opt.emoji }}</span>
                            <span class="text-sm font-semibold">{{ opt.label }}</span>
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

                <!-- Campo oculto — avatar_style se deriva de career en el watch() -->
                <input type="hidden" name="avatar_style" :value="form.avatar_style" />

                <!-- Errores globales del formulario -->
                <div
                    v-if="form.errors.avatar_style"
                    class="rounded-lg bg-danger/20 px-4 py-3 text-sm text-danger-text"
                    role="alert"
                >
                    {{ form.errors.avatar_style }}
                </div>

                <BaseButton type="submit" class="w-full" :disabled="form.processing">
                    {{ form.processing ? 'Guardando…' : 'Guardar y continuar' }}
                </BaseButton>
            </form>
        </div>
    </AppLayout>
</template>
