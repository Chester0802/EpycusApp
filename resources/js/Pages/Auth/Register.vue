<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

/*
 * Pantalla de registro — Epycus (Fase 1).
 * Formulario esencial de inicio de cuenta:
 *   - Nombre completo, Alias público (con sugerencia automática), Correo, Contraseña y Términos.
 *
 * Flujo de Arquitectura (docs/01-MODULOS.md):
 *   Paso 1: Registro de credenciales e identidad inicial (/register).
 *   Paso 2: Completar perfil de estudiante (/profile/complete) → Institución, Carrera, Ciclo y Género de Avatar.
 */

const form = useForm({
    name: '',
    alias: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
});

const generateAlias = () => {
    const firstWord = form.name
        ? form.name
              .trim()
              .split(' ')[0]
              .toLowerCase()
              .replace(/[^a-z0-9]/g, '')
        : 'estudiante';
    const base = firstWord.length >= 2 ? firstWord : 'estudiante';
    const randomCode = Math.floor(1000 + Math.random() * 9000);
    form.alias = `${base}-${randomCode}`;
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Crear cuenta" />

    <GuestLayout>
        <template #tagline>
            <p class="text-sm font-medium text-content-secondary">Comienza tu progreso</p>
        </template>

        <!-- Alertas flash de error y advertencia -->
        <div
            v-if="$page.props.flash?.error"
            class="mb-5 rounded-lg bg-danger/10 border border-danger/20 px-4 py-3 text-sm font-semibold text-danger-text"
            role="alert"
        >
            {{ $page.props.flash.error }}
        </div>

        <div
            v-if="$page.props.flash?.warning"
            class="mb-5 rounded-lg bg-warning/10 border border-warning/20 px-4 py-3 text-sm font-semibold text-warning-text"
            role="alert"
        >
            {{ $page.props.flash.warning }}
        </div>

        <h1 class="mb-2 font-display text-2xl font-bold text-content-primary">Crear cuenta</h1>
        <p class="mb-6 text-sm text-content-secondary">
            Regístrate para formar parte de una comunidad que quiere progresar.
        </p>

        <form class="space-y-4" novalidate @submit.prevent="submit">
            <!-- Nombre completo -->
            <BaseInput
                id="name"
                v-model="form.name"
                label="Nombre completo"
                type="text"
                autocomplete="name"
                :error="form.errors.name"
                required
            />

            <!-- Alias público con botón interactivo de generación -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="alias" class="block text-sm font-semibold text-content-secondary">
                        Alias público (visible en el ranking)
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
                    label=""
                    type="text"
                    autocomplete="username"
                    placeholder="Ej. estudiante-8492"
                    :error="form.errors.alias"
                    required
                />
            </div>

            <!-- Correo electrónico -->
            <BaseInput
                id="email"
                v-model="form.email"
                label="Correo electrónico"
                type="email"
                autocomplete="email"
                :error="form.errors.email"
                required
            />

            <!-- Contraseña -->
            <BaseInput
                id="password"
                v-model="form.password"
                label="Contraseña"
                type="password"
                autocomplete="new-password"
                :error="form.errors.password"
                required
            />

            <!-- Confirmar contraseña -->
            <BaseInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                label="Confirmar contraseña"
                type="password"
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
                required
            />

            <!-- Aceptación de Términos y Condiciones -->
            <div class="pt-2">
                <label
                    class="flex min-h-[44px] cursor-pointer items-start gap-3 text-sm text-content-secondary"
                >
                    <input
                        v-model="form.terms_accepted"
                        type="checkbox"
                        class="mt-1 h-4 w-4 rounded border-border-interactive text-primary focus-visible:ring-2 focus-visible:ring-primary-strong"
                        required
                    />
                    <span>
                        Acepto los
                        <Link
                            :href="route('terms')"
                            target="_blank"
                            class="font-semibold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
                        >
                            Términos y Condiciones
                        </Link>
                        del Proyecto Epycus.
                    </span>
                </label>
                <p v-if="form.errors.terms_accepted" class="mt-1 text-sm text-danger-text">
                    {{ form.errors.terms_accepted }}
                </p>
            </div>

            <BaseButton
                type="submit"
                class="w-full mt-4"
                :disabled="form.processing || !form.terms_accepted"
            >
                {{ form.processing ? 'Creando cuenta…' : 'Crear cuenta' }}
            </BaseButton>
        </form>

        <!-- Separador para OAuth -->
        <div class="relative my-6 flex items-center gap-3">
            <div class="h-px flex-1 bg-border" aria-hidden="true" />
            <span class="text-xs text-content-muted">o regístrate con</span>
            <div class="h-px flex-1 bg-border" aria-hidden="true" />
        </div>

        <!-- GOOGLE OAUTH -->
        <a
            :href="route('auth.google')"
            class="flex min-h-[44px] w-full items-center justify-center gap-3 rounded-xl border border-border-interactive bg-surface-raised px-4 text-sm font-semibold text-content-primary shadow-sm hover:bg-surface hover:border-primary-strong transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
        >
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                <path
                    fill="#4285F4"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                />
                <path
                    fill="#34A853"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                />
                <path
                    fill="#FBBC05"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                />
                <path
                    fill="#EA4335"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                />
            </svg>
            Continuar con Google
        </a>

        <!-- Enlace a login -->
        <p class="mt-6 text-center text-sm text-content-secondary">
            ¿Ya tienes cuenta?
            <Link
                :href="route('login')"
                class="font-semibold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
            >
                Inicia sesión
            </Link>
        </p>
    </GuestLayout>
</template>
