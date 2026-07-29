<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTheme } from '@/composables/useTheme';

/*
 * Layout para pantallas públicas (Login, Register).
 * Reemplaza el GuestLayout de Breeze (fondo gris bg-gray-100 + tarjeta
 * blanca plana sin tokens). Ahora usa los tokens del sistema de diseño de
 * Epycus: --color-bg, panel-raised, tipografía Quicksand/Nunito.
 *
 * No usa AppLayout (que requiere auth). No tiene sidebar ni barra inferior.
 * Diseño: centrado vertical en escritorio, full-height en móvil (docs/10 §4).
 *
 * El slot "logo" es opcional — se puede reemplazar con otro contenido arriba
 * del panel. El slot default es el contenido de la pantalla.
 *
 * `heroImage` (opcional, usado por Login): en escritorio arma dos columnas
 * (imagen a la izquierda, formulario a la derecha); en móvil el slot decide
 * su propio recorte, este layout solo lo apila arriba. Register no lo usa
 * y se ve exactamente igual que antes (columna única centrada).
 *
 * ThemeToggle: el usuario pidió poder cambiar de tema desde el login, no
 * solo ya adentro de la app — el composable usa localStorage, no depende
 * de sesión, así que funciona igual sin estar autenticado.
 */
const { surface } = useTheme();
</script>

<template>
    <div class="min-h-screen bg-bg px-4 py-12">
        <ThemeToggle v-if="surface !== 'glass'" class="fixed right-4 top-4 z-10" />

        <div
            class="mx-auto flex w-full max-w-5xl flex-col gap-8"
            :class="{ 'lg:flex-row lg:items-stretch lg:gap-10': $slots.heroImage }"
        >
            <div v-if="$slots.heroImage" class="overflow-hidden rounded-2xl lg:w-1/2">
                <slot name="heroImage" />
            </div>

            <div class="flex flex-1 flex-col items-center justify-center">
                <!-- Logo + nombre del producto -->
                <div class="mb-8 flex flex-col items-center gap-3">
                    <Link
                        :href="route('login')"
                        class="flex items-center gap-3 rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong focus-visible:ring-offset-2 focus-visible:ring-offset-bg"
                    >
                        <ApplicationLogo class="h-10 w-auto fill-current text-primary-strong" />
                        <span class="font-display text-2xl font-semibold text-content-primary">Epycus</span>
                    </Link>
                    <!-- Tagline — slot opcional para personalizar por pantalla -->
                    <slot name="tagline" />
                </div>

                <!-- Panel principal con el formulario -->
                <div class="panel-raised w-full max-w-md rounded-2xl px-8 py-8 sm:px-10">
                    <slot />
                </div>

                <!-- Footer mínimo -->
                <p class="mt-8 text-center text-xs text-content-muted">
                    © {{ new Date().getFullYear() }} Epycus — Proyecto de investigación
                </p>
            </div>
        </div>
    </div>
</template>
