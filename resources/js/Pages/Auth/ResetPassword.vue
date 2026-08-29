<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Lock, ShieldCheck, Check } from '@lucide/vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Restablecer contraseña — Epycus" />

        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <ShieldCheck :size="24" />
            </div>
            <h1 class="font-display text-2xl font-bold text-content-primary">Restablecer contraseña</h1>
            <p class="mt-2 text-sm text-content-secondary">
                Crea una nueva contraseña segura para tu cuenta de Epycus.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-content-secondary">
                    Correo electrónico *
                </label>
                <BaseInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-content-secondary">
                    Nueva contraseña *
                </label>
                <BaseInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    placeholder="Mínimo 8 caracteres"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-content-secondary">
                    Confirmar nueva contraseña *
                </label>
                <BaseInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    placeholder="Repite la contraseña"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
            </div>

            <div class="pt-2">
                <BaseButton
                    type="submit"
                    variant="primary"
                    class="w-full justify-center"
                    :disabled="form.processing"
                >
                    <Check :size="16" />
                    {{ form.processing ? 'Guardando contraseña…' : 'Restablecer contraseña' }}
                </BaseButton>
            </div>
        </form>
    </GuestLayout>
</template>
