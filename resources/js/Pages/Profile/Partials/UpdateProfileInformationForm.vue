<script setup>
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

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

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    alias: user.alias ?? '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-display text-content-primary">
                Información de la cuenta
            </h2>
            <p class="mt-1 text-sm text-content-secondary">
                Actualiza tu nombre, correo y alias.
            </p>
        </header>

        <form
            class="mt-6 space-y-6"
            @submit.prevent="form.patch(route('profile.update'))"
        >
            <BaseInput
                id="name"
                label="Nombre"
                :model-value="form.name"
                :error="form.errors.name"
                required
                autocomplete="name"
                @update:model-value="form.name = $event"
            />

            <BaseInput
                id="alias"
                label="Alias"
                :model-value="form.alias"
                :error="form.errors.alias"
                autocomplete="username"
                @update:model-value="form.alias = $event"
            />

            <BaseInput
                id="email"
                label="Correo electrónico"
                type="email"
                :model-value="form.email"
                :error="form.errors.email"
                required
                autocomplete="username"
                @update:model-value="form.email = $event"
            />

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm text-content-secondary">
                    Tu correo no está verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-accent underline hover:text-accent-strong focus:outline-none focus:ring-2 focus:ring-primary-strong focus:ring-offset-2 focus:ring-offset-bg"
                    >
                        Reenviar verificación.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-success-text"
                >
                    Se ha enviado un nuevo enlace de verificación a tu correo.
                </div>
            </div>

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
