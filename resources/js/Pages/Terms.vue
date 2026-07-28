<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

/*
 * Página de Términos y Condiciones del Servicio y del Experimento — Epycus.
 * Requisito legal y ético para la participación de estudiantes universitarios
 * e institutos en el estudio de 66 días.
 */
</script>

<template>
    <Head title="Términos y Condiciones" />

    <GuestLayout>
        <template #tagline>
            <p class="text-sm text-content-secondary">Términos y Condiciones del Servicio</p>
        </template>

        <div class="space-y-4 text-sm leading-relaxed text-content-secondary max-h-[60vh] overflow-y-auto pr-2">
            <h1 class="font-display text-xl font-bold text-content-primary mb-2">
                Términos y Condiciones de Uso — Epycus
            </h1>

            <section>
                <h2 class="font-semibold text-content-primary mb-1">1. Aceptación de los Términos</h2>
                <p>
                    Al registrarte y utilizar la plataforma Epycus, aceptas cumplir con los presentes
                    términos y condiciones. Esta aplicación forma parte de un proyecto de investigación
                    orientado a la mejora de hábitos de estudio en estudiantes de educación superior.
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-content-primary mb-1">2. Alcance Participativo</h2>
                <p>
                    El servicio está habilitado para estudiantes de universidades e institutos superiores.
                    Los datos recopilados durante la intervención de 66 días serán tratados bajo estricto
                    anonimato y seudonimización conforme a la legislación de protección de datos personales.
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-content-primary mb-1">3. Cuentas y Seguridad</h2>
                <p>
                    El usuario es responsable de mantener la confidencialidad de sus credenciales de acceso.
                    Los correos de notificaciones transaccionales y soporte se emitirán mediante el dominio
                    oficial autorizado (<strong class="text-content-primary">@soltecto.com</strong>).
                </p>
            </section>

            <section>
                <h2 class="font-semibold text-content-primary mb-1">4. Retiro Voluntario</h2>
                <p>
                    El participante puede solicitar el retiro de su información o descontinuar su uso en cualquier
                    momento sin ningún perjuicio académico ni personal.
                </p>
            </section>
        </div>

        <div class="mt-6 border-t border-border pt-4 text-center">
            <Link
                :href="route('register')"
                class="inline-block font-semibold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
            >
                ← Volver al Registro
            </Link>
        </div>
    </GuestLayout>
</template>
