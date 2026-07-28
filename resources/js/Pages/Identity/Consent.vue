<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import { Head, useForm } from '@inertiajs/vue3';

/*
 * Pantalla de consentimiento informado — Fase 1.
 * Conecta a POST /consent → RecordConsentUseCase → ConsentGranted event.
 * Luego redirige a /profile/complete (flujo de onboarding).
 *
 * El texto del consentimiento debe reflejar el documento real aprobado por
 * el comité de ética/institución. El que aparece aquí es un placeholder
 * estructuralmente correcto para revisar con el equipo investigador antes
 * del día de inicio de la intervención (07/09/2026).
 *
 * Regla: el formulario no se puede enviar si el usuario no ha marcado la
 * casilla de aceptación — validación en cliente, la guarda de idempotencia
 * está en RecordConsentUseCase (no se puede otorgar dos veces).
 */

const form = useForm({
    accepted: false,
});

const submit = () => {
    if (!form.accepted) return;
    form.post(route('consent.store'));
};
</script>

<template>
    <Head title="Consentimiento informado" />

    <AppLayout>
        <div class="mx-auto max-w-2xl">
            <h1 class="mb-2 font-display text-3xl text-content-primary">
                Consentimiento informado
            </h1>
            <p class="mb-8 text-content-secondary">
                Lee con atención antes de continuar. Tu participación es completamente voluntaria.
            </p>

            <!-- Documento de consentimiento -->
            <BaseCard class="mb-6">
                <div class="space-y-4 text-sm leading-relaxed text-content-secondary">
                    <h2 class="font-display text-lg text-content-primary">
                        Proyecto de investigación: Epycus
                    </h2>

                    <section aria-labelledby="consent-purpose">
                        <h3 id="consent-purpose" class="mb-1 font-semibold text-content-primary">
                            Propósito del estudio
                        </h3>
                        <p>
                            Esta investigación estudia el efecto de una aplicación gamificada en los hábitos de
                            estudio de estudiantes universitarios durante una intervención de 66 días
                            (7 de septiembre al 11 de noviembre de 2026). Los resultados serán usados
                            exclusivamente con fines académicos.
                        </p>
                    </section>

                    <section aria-labelledby="consent-data">
                        <h3 id="consent-data" class="mb-1 font-semibold text-content-primary">
                            Datos que se recopilan
                        </h3>
                        <ul class="list-inside list-disc space-y-1">
                            <li>Información de perfil académico (carrera, ciclo, institución)</li>
                            <li>Registro de hábitos de estudio completados</li>
                            <li>Tiempos de sesiones Pomodoro</li>
                            <li>Niveles y puntos de experiencia dentro de la aplicación</li>
                            <li>Respuestas al diario de bienestar (cifradas)</li>
                        </ul>
                    </section>

                    <section aria-labelledby="consent-privacy">
                        <h3 id="consent-privacy" class="mb-1 font-semibold text-content-primary">
                            Privacidad y confidencialidad
                        </h3>
                        <p>
                            Tus datos se almacenan bajo un código seudónimo — nunca tu nombre real.
                            Cumplimos la Ley N.° 29733 (Ley de Protección de Datos Personales del Perú)
                            y su reglamento. Tienes derecho a acceder, rectificar o retirar tu consentimiento
                            en cualquier momento escribiendo al equipo investigador.
                        </p>
                    </section>

                    <section aria-labelledby="consent-voluntary">
                        <h3 id="consent-voluntary" class="mb-1 font-semibold text-content-primary">
                            Participación voluntaria
                        </h3>
                        <p>
                            Puedes retirarte del estudio en cualquier momento sin consecuencia alguna.
                            Si te retiras, tus datos serán anonimizados y no se incluirán en el análisis final.
                        </p>
                    </section>

                    <section aria-labelledby="consent-contact">
                        <h3 id="consent-contact" class="mb-1 font-semibold text-content-primary">
                            Contacto
                        </h3>
                        <p>
                            Para consultas sobre el estudio o tus derechos como participante,
                            escríbenos a
                            <!-- Reemplazar con el correo real del equipo investigador antes del 07/09/2026 -->
                            <strong class="text-content-primary">investigacion@epycus.es</strong>.
                        </p>
                    </section>
                </div>
            </BaseCard>

            <!-- Formulario de aceptación -->
            <form novalidate @submit.prevent="submit">
                <label
                    class="mb-6 flex min-h-[48px] cursor-pointer items-start gap-3 rounded-xl border border-border-interactive p-4 transition-colors duration-150 hover:bg-surface-raised"
                    :class="{ 'bg-primary/20 border-primary-strong': form.accepted }"
                >
                    <input
                        v-model="form.accepted"
                        type="checkbox"
                        class="mt-0.5 h-5 w-5 shrink-0 rounded border-border-interactive focus-visible:ring-2 focus-visible:ring-primary-strong"
                        aria-required="true"
                    />
                    <span class="text-sm text-content-primary">
                        He leído y entendido la información anterior. Acepto participar voluntariamente
                        en este estudio y autorizo el uso de mis datos de la forma descrita.
                    </span>
                </label>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <BaseButton
                        type="submit"
                        class="flex-1"
                        :disabled="!form.accepted || form.processing"
                        :aria-disabled="!form.accepted"
                    >
                        {{ form.processing ? 'Registrando…' : 'Acepto y continúo' }}
                    </BaseButton>

                    <BaseButton
                        variant="ghost"
                        :href="route('logout')"
                        as="a"
                        class="flex-1 sm:flex-none"
                    >
                        No acepto, salir
                    </BaseButton>
                </div>

                <!-- Error de servidor (p. ej. consentimiento ya otorgado) -->
                <p
                    v-if="form.errors.general"
                    class="mt-4 text-sm text-danger-text"
                    role="alert"
                >
                    {{ form.errors.general }}
                </p>
            </form>
        </div>
    </AppLayout>
</template>
