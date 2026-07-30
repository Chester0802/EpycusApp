<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

/*
 * Pantalla de inicio de sesión — Epycus (Fase 1).
 * Layout visual: Imagen Hero (`login-hero.webp`) + Logo (`logo.webp`) + Saludo "Bienvenido a Epycus." y frase motivadora.
 * Acciones: Correo, Contraseña, "¿Olvidaste tu contraseña?", "Mantener sesión iniciada", Botón Ingresar y Continuar con Google.
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
            <!-- Subtítulo motivador -->
            <p class="text-sm font-medium text-content-secondary">
                Transforma tu rutina y domina tus metas de estudio.
            </p>
        </template>

        <!--
            Hero de dos personajes. En móvil se apila arriba del formulario:
            antes se veía cortado a la mitad de las caras/piernas porque
            `object-cover` con poca altura (h-32/h-40) recorta el centro de
            una imagen compuesta para verse completa — ahora usa más alto
            (h-64) y `object-position: top` para no perder las caras.
            En escritorio (columna izquierda de GuestLayout, `lg:w-1/2`) usa
            el alto completo del panel, sin recortar nada.
        -->
        <template #heroImage>
            <img
                src="/assets/images/login-hero.webp"
                alt="Epycus Hero"
                class="h-64 w-full object-cover object-top lg:h-full"
                onerror="this.style.display='none'"
            />
        </template>

        <!-- Mensaje de estado (p. ej. "Enlace de restablecimiento enviado") -->
        <div
            v-if="status"
            class="mb-5 rounded-lg bg-accent px-4 py-3 text-sm font-semibold text-on-accent"
            role="alert"
        >
            {{ status }}
        </div>

        <h1 class="mb-2 font-display text-2xl font-bold text-content-primary">
            Bienvenido a Epycus.
        </h1>
        <p class="mb-6 text-sm text-content-secondary">
            Ingresa a tu cuenta para continuar con tu progreso.
        </p>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <BaseInput
                id="email"
                v-model="form.email"
                label="Correo electrónico"
                type="email"
                autocomplete="username"
                :error="form.errors.email"
                required
            />

            <BaseInput
                id="password"
                v-model="form.password"
                label="Contraseña"
                type="password"
                autocomplete="current-password"
                :error="form.errors.password"
                required
            />

            <!-- Recordar sesión ("Mantener sesión iniciada") + recuperar contraseña -->
            <div class="flex items-center justify-between gap-4">
                <label class="flex min-h-[44px] cursor-pointer items-center gap-2 text-sm text-content-secondary">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-border-interactive text-primary focus-visible:ring-2 focus-visible:ring-primary-strong"
                    />
                    Mantener sesión iniciada
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <BaseButton type="submit" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Ingresando…' : 'Ingresar' }}
            </BaseButton>
        </form>

        <!-- Separador para OAuth -->
        <div class="relative my-6 flex items-center gap-3">
            <div class="h-px flex-1 bg-border" aria-hidden="true" />
            <span class="text-xs text-content-muted">o continúa con</span>
            <div class="h-px flex-1 bg-border" aria-hidden="true" />
        </div>

        <!-- GOOGLE OAUTH -->
        <a
            :href="route('auth.google')"
            class="flex min-h-[44px] w-full items-center justify-center gap-3 rounded-xl border border-border-interactive bg-surface-raised px-4 text-sm font-semibold text-content-primary shadow-sm hover:bg-surface hover:border-primary-strong transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
        >
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continuar con Google
        </a>

        <!-- Acceso Administrador / Investigador -->
        <div class="mt-4 text-center">
            <button
                type="button"
                class="text-xs text-content-muted hover:text-primary-strong transition-colors underline-offset-2 hover:underline"
                @click="form.email = 'admin@epycus.es'; form.password = 'admin1234'"
            >
                🛡️ Acceso Administrador / Investigación
            </button>
        </div>

        <!-- Enlace a registro -->
        <p class="mt-6 text-center text-sm text-content-secondary">
            ¿No tienes cuenta?
            <Link
                :href="route('register')"
                class="font-semibold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
            >
                Regístrate
            </Link>
        </p>
    </GuestLayout>
</template>
