<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

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
 */
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-12">
        <!-- Logo + nombre del producto -->
        <div class="mb-8 flex flex-col items-center gap-3">
            <Link :href="route('login')" class="flex items-center gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong focus-visible:ring-offset-2 focus-visible:ring-offset-bg rounded">
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
        <p class="mt-8 text-xs text-content-muted">
            © {{ new Date().getFullYear() }} Epycus — Proyecto de investigación
        </p>
    </div>
</template>
