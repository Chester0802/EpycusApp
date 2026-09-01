<script setup>
import { computed } from 'vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const user = computed(() => usePage().props.auth.user);

const form = useForm({
    name: user.value?.name ?? '',
    alias: user.value?.alias ?? '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-display font-bold text-content-primary">
                Identidad y Nombre de Héroe
            </h2>
            <p class="mt-1 text-sm text-content-secondary">
                Personaliza cómo te verán tus compañeros y profesores en la plataforma.
            </p>
        </header>

        <form
            class="mt-6 space-y-5"
            @submit.prevent="form.patch(route('profile.update'))"
        >
            <BaseInput
                id="name"
                label="Nombre Completo"
                :model-value="form.name"
                :error="form.errors.name"
                placeholder="Tu nombre y apellido"
                required
                autocomplete="name"
                @update:model-value="form.name = $event"
            />

            <div>
                <BaseInput
                    id="alias"
                    label="Alias / Nombre de Héroe"
                    :model-value="form.alias"
                    :error="form.errors.alias"
                    placeholder="Ej. CyberKnight, AlexPro, etc."
                    autocomplete="username"
                    @update:model-value="form.alias = $event"
                />
                <p class="mt-1.5 text-xs text-content-muted">
                    Este alias se mostrará en los Rankings, Salas de Estudio y tu Tarjeta de Estudiante.
                </p>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <BaseButton type="submit" variant="primary" :disabled="form.processing">
                    Guardar Cambios
                </BaseButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-bold text-success"
                    >
                        ✓ Guardado correctamente.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
