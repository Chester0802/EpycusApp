<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-display text-content-primary">
                Actualizar contraseña
            </h2>
            <p class="mt-1 text-sm text-content-secondary">
                Asegúrate de usar una contraseña larga y aleatoria para mantener tu cuenta segura.
            </p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="updatePassword">
            <BaseInput
                id="current_password"
                ref="currentPasswordInput"
                label="Contraseña actual"
                type="password"
                :model-value="form.current_password"
                :error="form.errors.current_password"
                autocomplete="current-password"
                @update:model-value="form.current_password = $event"
            />

            <BaseInput
                id="password"
                ref="passwordInput"
                label="Nueva contraseña"
                type="password"
                :model-value="form.password"
                :error="form.errors.password"
                autocomplete="new-password"
                @update:model-value="form.password = $event"
            />

            <BaseInput
                id="password_confirmation"
                label="Confirmar nueva contraseña"
                type="password"
                :model-value="form.password_confirmation"
                :error="form.errors.password_confirmation"
                autocomplete="new-password"
                @update:model-value="form.password_confirmation = $event"
            />

            <div class="flex items-center gap-4">
                <BaseButton type="submit" variant="primary" :disabled="form.processing">
                    Guardar
                </BaseButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-content-secondary"
                    >
                        Guardado.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
