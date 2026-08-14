<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { KeyRound, ArrowLeft, Send, CheckCircle2 } from '@lucide/vue';

defineProps({
    status: {
        type: String,
        default: '',
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña — Epycus" />

        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <KeyRound :size="24" />
            </div>
            <h1 class="font-display text-2xl font-bold text-content-primary">¿Olvidaste tu contraseña?</h1>
            <p class="mt-2 text-sm text-content-secondary">
                No hay problema. Ingresa tu correo electrónico registrado y te enviaremos un enlace seguro para restablecer tu contraseña.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-6 flex items-center gap-2 rounded-xl border border-success/30 bg-success/10 p-4 text-sm font-medium text-success"
        >
            <CheckCircle2 :size="18" class="shrink-0" />
            <span>{{ status }}</span>
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
                    placeholder="tu_correo@ejemplo.com"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div class="pt-2">
                <BaseButton
                    type="submit"
                    variant="primary"
                    class="w-full justify-center"
                    :disabled="form.processing"
                >
                    <Send :size="16" />
                    {{ form.processing ? 'Enviando enlace…' : 'Enviar enlace de recuperación' }}
                </BaseButton>
            </div>

            <div class="text-center pt-3">
                <Link
                    :href="route('login')"
                    class="inline-flex items-center gap-1.5 text-xs text-content-secondary hover:text-content-primary transition"
                >
                    <ArrowLeft :size="14" />
                    Volver a iniciar sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
