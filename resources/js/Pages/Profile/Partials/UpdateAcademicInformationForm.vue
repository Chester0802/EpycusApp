<script setup>
import { computed } from 'vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    careers: { type: Object, default: () => ({}) },
    cycles: { type: Array, default: () => [] },
    institutionTypes: { type: Array, default: () => [] },
});

const user = usePage().props.auth.user;

const form = useForm({
    institution_type: user.institution_type ?? '',
    career: user.career ?? '',
    cycle: user.cycle ? String(user.cycle) : '',
});

// Opciones planas para el selector de carrera
const careerOptions = computed(() => {
    if (!props.careers) return [];
    if (Array.isArray(props.careers)) {
        return props.careers.map((c) => ({ value: c, label: c }));
    }
    if (typeof props.careers === 'object') {
        return Object.entries(props.careers).flatMap(([style, list]) =>
            Array.isArray(list) ? list.map((name) => ({ value: name, label: name })) : []
        );
    }
    return [];
});

// Opciones de ciclo académico (1 a 10)
const cycleOptions = computed(() => {
    const list = Array.isArray(props.cycles) && props.cycles.length > 0
        ? props.cycles
        : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    return list.map((c) => ({ value: String(c), label: `Ciclo ${c}` }));
});

// Opciones de tipo de institución (Universidad / Instituto)
const institutionOptions = computed(() => {
    const list = Array.isArray(props.institutionTypes) && props.institutionTypes.length > 0
        ? props.institutionTypes
        : ['universidad', 'instituto'];
    return list.map((t) => ({
        value: String(t),
        label: String(t).charAt(0).toUpperCase() + String(t).slice(1),
    }));
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="font-display text-xl font-bold text-content-primary">
                Información Académica
            </h2>
            <p class="mt-1 text-sm text-content-secondary">
                Actualiza tu institución, carrera profesional y el ciclo académico en el que te
                encuentras.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <!-- Tipo de Institución -->
            <BaseSelect
                id="institution_type"
                v-model="form.institution_type"
                label="Tipo de institución"
                :options="institutionOptions"
                placeholder="Selecciona tu tipo de institución"
                :error="form.errors.institution_type"
            />

            <!-- Carrera -->
            <BaseSelect
                id="career"
                v-model="form.career"
                label="Carrera profesional"
                :options="careerOptions"
                placeholder="Selecciona tu carrera"
                :error="form.errors.career"
            />

            <!-- Ciclo -->
            <BaseSelect
                id="cycle"
                v-model="form.cycle"
                label="Ciclo académico actual"
                :options="cycleOptions"
                placeholder="Selecciona tu ciclo"
                :error="form.errors.cycle"
            />

            <div class="flex items-center gap-4">
                <BaseButton type="submit" variant="primary" :disabled="form.processing">
                    {{ form.processing ? 'Guardando…' : 'Guardar Datos Académicos' }}
                </BaseButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-semibold text-success-text"
                    >
                        Guardado correctamente.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
