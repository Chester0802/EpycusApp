<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

/*
 * Pantalla de inicio de sesión — Epycus (Fase 1 Mejorada).
 * Estructura de alta conversión:
 *   1. Botón prioritario de Google en primer plano ("Continuar con Google").
 *   2. Separador visual limpio para acceso con credenciales tradicionales.
 *   3. Formulario de ingreso (correo/alias, contraseña, recordar sesión y recuperación).
 */
defineProps({
    canResetPassword: { type: Boolean, default: false },
    status: { type: String, default: '' },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <GuestLayout>
        <template #tagline>
            <p class="text-sm font-medium text-content-secondary">
                Transforma tu rutina y domina tus metas de estudio.
            </p>
        </template>

        <!-- IMAGEN HERO COMPLETA Y LIMPIA -->
        <template #heroImage>
            <img
                src="/assets/images/login-hero.webp"
                alt="Epycus Hero"
                class="h-64 w-full object-cover object-top lg:h-auto lg:max-h-[720px] rounded-3xl"
                onerror="this.style.display = 'none'"
            />
        </template>

        <!-- Mensajes de estado (p. ej. "Enlace de restablecimiento enviado") -->
        <div
            v-if="status"
            class="mb-4 rounded-xl bg-accent/20 border border-accent/30 px-4 py-3 text-sm font-semibold text-content-primary"
            role="alert"
        >
            {{ status }}
        </div>

        <!-- Alertas flash de error y advertencia -->
        <div
            v-if="$page.props.flash?.error"
            class="mb-4 rounded-xl bg-danger/10 border border-danger/20 px-4 py-3 text-sm font-semibold text-danger-text"
            role="alert"
        >
            {{ $page.props.flash.error }}
        </div>

        <div
            v-if="$page.props.flash?.warning"
            class="mb-4 rounded-xl bg-warning/10 border border-warning/20 px-4 py-3 text-sm font-semibold text-warning-text"
            role="alert"
        >
            {{ $page.props.flash.warning }}
        </div>

        <!-- Encabezado Principal -->
        <div class="mb-5 text-center sm:text-left">
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-content-primary tracking-tight">
                Bienvenido a Epycus
            </h1>
            <p class="mt-1 text-sm text-content-secondary">
                Ingresa a tu cuenta para continuar con tu progreso.
            </p>
        </div>

        <!-- SECCIÓN 1: INGRESO PRIORITARIO CON GOOGLE (EN PRIMER PLANO) -->
        <div class="space-y-4">
            <a
                :href="route('auth.google')"
                class="flex min-h-[50px] w-full items-center justify-center gap-3 rounded-2xl border-2 border-primary-strong/30 bg-surface-raised px-4 py-3 text-sm font-bold text-content-primary shadow-md hover:bg-surface hover:border-primary-strong hover:shadow-lg transition-all active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong cursor-pointer"
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
                <span>Continuar con Google</span>
            </a>

            <!-- Separador -->
            <div class="relative my-5 flex items-center gap-3">
                <div class="h-px flex-1 bg-border" aria-hidden="true" />
                <span class="text-xs font-semibold text-content-muted">o ingresa con tu correo o alias</span>
                <div class="h-px flex-1 bg-border" aria-hidden="true" />
            </div>
        </div>

        <!-- SECCIÓN 2: FORMULARIO DE INGRESO MANUAL -->
        <form class="space-y-4" novalidate @submit.prevent="submit">
            <BaseInput
                id="email"
                v-model="form.email"
                label="Correo electrónico o Alias"
                type="text"
                autocomplete="username"
                placeholder="tu.correo@universidad.edu.pe o tu alias"
                :error="form.errors.email"
                required
            />

            <BaseInput
                id="password"
                v-model="form.password"
                label="Contraseña"
                type="password"
                autocomplete="current-password"
                placeholder="Ingresa tu contraseña"
                :error="form.errors.password"
                required
            />

            <!-- Recordar sesión + recuperar contraseña -->
            <div class="flex items-center justify-between gap-4 pt-1">
                <label
                    class="flex min-h-[44px] cursor-pointer items-center gap-2 text-xs text-content-secondary"
                >
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-border-interactive text-primary focus-visible:ring-2 focus-visible:ring-primary-strong"
                    />
                    <span>Mantener sesión</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs font-semibold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <BaseButton
                type="submit"
                class="w-full min-h-[48px] font-bold text-sm shadow-md"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Ingresando…' : 'Ingresar' }}
            </BaseButton>
        </form>

        <!-- Enlace a registro -->
        <p class="mt-6 text-center text-sm text-content-secondary">
            ¿No tienes cuenta?
            <Link
                :href="route('register')"
                class="font-bold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
            >
                Regístrate aquí
            </Link>
        </p>
    </GuestLayout>
</template>
