<script setup>
import { computed, ref, watch } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

/*
 * Pantalla de registro — Epycus (Fase 1 Mejorada).
 * Experiencia optimizada para estudiantes:
 *   1. Imagen Hero completa y limpia a la izquierda (en PC) o superior (en móvil).
 *   2. Registro prioritario en 1 clic con Google ("Regístrate con Google").
 *   3. Propuesta de valor clara (hábitos, gamificación y ranking) visible en móvil y PC.
 *   4. Aceptación de Términos y Condiciones.
 *   5. Registro manual colapsable con medidor de fuerza de contraseña en tiempo real,
 *      indicador de coincidencia y sugerencia de alias.
 */

const form = useForm({
    name: '',
    alias: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms_accepted: true,
});

const showManualRegister = ref(false);
const userModifiedAlias = ref(false);

// Auto-expandir formulario si hay errores en campos manuales
watch(
    () => form.hasErrors,
    (hasErr) => {
        if (hasErr) {
            showManualRegister.value = true;
        }
    },
    { immediate: true },
);

// Generador y sugerencia inteligente de alias
const handleNameInput = () => {
    if (!userModifiedAlias.value && form.name) {
        const firstWord = form.name
            .trim()
            .split(' ')[0]
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '');
        const base = firstWord.length >= 2 ? firstWord : 'estudiante';
        const randomCode = Math.floor(1000 + Math.random() * 9000);
        form.alias = `${base}-${randomCode}`;
    }
};

const generateAlias = () => {
    userModifiedAlias.value = false;
    if (form.name) {
        handleNameInput();
    } else {
        const randomCode = Math.floor(1000 + Math.random() * 9000);
        form.alias = `estudiante-${randomCode}`;
    }
};

// Medidor de fuerza de contraseña en tiempo real
const passwordRules = computed(() => [
    {
        id: 'length',
        label: 'Mínimo 8 caracteres',
        met: (form.password || '').length >= 8,
    },
    {
        id: 'case',
        label: 'Mayúsculas y minúsculas',
        met: /[A-Z]/.test(form.password || '') && /[a-z]/.test(form.password || ''),
    },
    {
        id: 'number',
        label: 'Al menos un número',
        met: /[0-9]/.test(form.password || ''),
    },
    {
        id: 'symbol',
        label: 'Un símbolo o carácter especial',
        met: /[^A-Za-z0-9]/.test(form.password || ''),
    },
]);

const passwordScore = computed(() => {
    const pwd = form.password || '';
    if (!pwd) return 0;
    return passwordRules.value.filter((r) => r.met).length;
});

const passwordStrength = computed(() => {
    const score = passwordScore.value;
    const pwd = form.password || '';
    if (!pwd) {
        return { label: 'Ingresa una contraseña', color: 'bg-border', textColor: 'text-content-muted', percent: 0 };
    }
    if (score <= 1) {
        return { label: 'Muy débil', color: 'bg-danger-text', textColor: 'text-danger-text', percent: 25 };
    }
    if (score === 2) {
        return { label: 'Regular', color: 'bg-warning', textColor: 'text-warning-text', percent: 50 };
    }
    if (score === 3) {
        return { label: 'Fuerte', color: 'bg-primary-strong', textColor: 'text-primary-strong', percent: 75 };
    }
    return { label: 'Excelente y segura', color: 'bg-success', textColor: 'text-success', percent: 100 };
});

// Coincidencia de contraseñas
const passwordMatchStatus = computed(() => {
    if (!form.password_confirmation) return null;
    if (form.password === form.password_confirmation) {
        return { match: true, message: 'Las contraseñas coinciden ✓' };
    }
    return { match: false, message: 'Las contraseñas aún no coinciden' };
});

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
            <p class="text-sm font-medium text-content-secondary">Tu viaje universitario al siguiente nivel</p>
        </template>

        <!-- IMAGEN HERO COMPLETA Y LIMPIA (SIN TEXTO ENCIMA) -->
        <template #heroImage>
            <img
                src="/assets/images/login-hero.webp"
                alt="Epycus Hero"
                class="h-64 w-full object-cover object-top lg:h-auto lg:max-h-[720px] rounded-3xl"
                onerror="this.style.display = 'none'"
            />
        </template>

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
        <div class="mb-4 text-center sm:text-left">
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-content-primary tracking-tight">
                Crear tu cuenta
            </h1>
            <p class="mt-1 text-sm text-content-secondary">
                Regístrate en segundos y comienza a dominar tu ciclo académico.
            </p>
        </div>

        <!-- PROPUESTA DE VALOR (VISIBLE EN MÓVIL Y PC) -->
        <div class="mb-5 rounded-2xl bg-surface-raised border border-border/70 p-3.5 space-y-2 text-xs shadow-sm">
            <div class="flex items-center gap-2.5 font-semibold text-content-primary">
                <span class="text-sm">📅</span>
                <span>Hábitos diarios, Pomodoro y Gamificación</span>
            </div>
            <div class="flex items-center gap-2.5 font-semibold text-content-primary">
                <span class="text-sm">🎮</span>
                <span>Gana XP y compite en el ranking universitario</span>
            </div>
        </div>

        <!-- SECCIÓN 1: REGISTRO CON GOOGLE (PRIMER PLANO) -->
        <div class="space-y-2">
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
                <span>Regístrate con Google</span>
            </a>

            <!-- Nota informativa para Google -->
            <p class="text-[11px] text-center text-content-muted pt-1">
                Al registrarte con Google, aceptas los
                <Link
                    :href="route('terms')"
                    target="_blank"
                    class="font-semibold text-primary-strong underline-offset-2 hover:underline"
                >
                    Términos y Condiciones
                </Link>.
            </p>
        </div>

        <!-- SEPARADOR CON BOTÓN PARA DESPLEGAR REGISTRO MANUAL -->
        <div class="my-5 flex flex-col items-center">
            <div class="relative w-full flex items-center justify-center">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-border" />
                </div>
                <button
                    type="button"
                    class="relative z-10 inline-flex items-center gap-2 rounded-full border border-border bg-surface-raised px-4 py-1.5 text-xs font-bold text-content-secondary hover:text-content-primary hover:border-primary-strong hover:bg-surface transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong cursor-pointer shadow-sm"
                    @click="showManualRegister = !showManualRegister"
                >
                    <span>{{ showManualRegister ? 'Ocultar registro manual' : 'O regístrate con tu correo' }}</span>
                    <svg
                        class="h-3.5 w-3.5 transition-transform duration-200"
                        :class="{ 'rotate-180': showManualRegister }"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- SECCIÓN 2: FORMULARIO DE REGISTRO MANUAL (COLAPSABLE) -->
        <div v-show="showManualRegister" class="transition-all duration-300 ease-in-out">
            <form class="space-y-4" novalidate @submit.prevent="submit">
                <!-- Nombre completo -->
                <div>
                    <BaseInput
                        id="name"
                        v-model="form.name"
                        label="Nombre completo"
                        type="text"
                        autocomplete="name"
                        placeholder="Ej. Marco Antonio"
                        :error="form.errors.name"
                        required
                        @input="handleNameInput"
                    />
                </div>

                <!-- Alias público con generador interactivo -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="alias" class="block text-xs font-bold uppercase tracking-wider text-content-secondary">
                            Alias público (Ranking)
                            <span class="text-danger-text">*</span>
                        </label>
                        <button
                            type="button"
                            class="text-xs font-bold text-primary-strong hover:underline focus-visible:outline-none cursor-pointer flex items-center gap-1.5"
                            @click="generateAlias"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                            </svg>
                            <span>Generar alias</span>
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
                        @input="userModifiedAlias = true"
                    />
                    <p class="mt-1 text-[11px] text-content-muted">
                        Este nombre será visible en las tablas de clasificación y misiones.
                    </p>
                </div>

                <!-- Correo electrónico -->
                <div>
                    <BaseInput
                        id="email"
                        v-model="form.email"
                        label="Correo electrónico"
                        type="email"
                        autocomplete="email"
                        placeholder="tu.correo@universidad.edu.pe"
                        :error="form.errors.email"
                        required
                    />
                </div>

                <!-- Contraseña con Medidor de Fuerza en Tiempo Real -->
                <div>
                    <BaseInput
                        id="password"
                        v-model="form.password"
                        label="Contraseña"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Crea una contraseña segura"
                        :error="form.errors.password"
                        required
                    />

                    <!-- Barra del Medidor de Fuerza -->
                    <div v-if="form.password" class="mt-2.5 space-y-2 rounded-xl bg-surface p-3 border border-border/80">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-content-secondary">Seguridad:</span>
                            <span :class="passwordStrength.textColor">{{ passwordStrength.label }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-sunken">
                            <div
                                class="h-full rounded-full transition-all duration-300 ease-out"
                                :class="passwordStrength.color"
                                :style="{ width: `${passwordStrength.percent}%` }"
                            />
                        </div>

                        <!-- Checklist de Criterios de Seguridad -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pt-1 text-[11px]">
                            <div
                                v-for="rule in passwordRules"
                                :key="rule.id"
                                class="flex items-center gap-1.5 transition-colors"
                                :class="rule.met ? 'text-success font-semibold' : 'text-content-muted'"
                            >
                                <span class="text-xs">{{ rule.met ? '✓' : '○' }}</span>
                                <span>{{ rule.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmar contraseña con indicador de coincidencia -->
                <div>
                    <BaseInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        label="Confirmar contraseña"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Repite tu contraseña"
                        :error="form.errors.password_confirmation"
                        required
                    />
                    <!-- Feedback de coincidencia en tiempo real -->
                    <p
                        v-if="passwordMatchStatus"
                        class="mt-1 text-xs font-semibold"
                        :class="passwordMatchStatus.match ? 'text-success' : 'text-warning-text'"
                    >
                        {{ passwordMatchStatus.message }}
                    </p>
                </div>

                <!-- ACEPTACIÓN DE TÉRMINOS Y CONDICIONES (DENTRO DEL FORMULARIO) -->
                <div class="pt-1">
                    <label
                        class="flex items-start gap-3 p-3 rounded-2xl border transition-all cursor-pointer select-none"
                        :class="form.terms_accepted ? 'bg-primary/5 border-primary-strong/40' : 'bg-surface border-border hover:bg-surface-raised'"
                    >
                        <input
                            v-model="form.terms_accepted"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-border-interactive text-primary focus-visible:ring-2 focus-visible:ring-primary-strong cursor-pointer shrink-0"
                            required
                        />
                        <span class="text-xs text-content-secondary leading-relaxed">
                            Acepto los
                            <Link
                                :href="route('terms')"
                                target="_blank"
                                class="font-bold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
                                @click.stop
                            >
                                Términos y Condiciones
                            </Link>
                            del Proyecto Epycus y su Política de Privacidad.
                        </span>
                    </label>
                    <p v-if="form.errors.terms_accepted" class="mt-1.5 text-xs text-danger-text font-semibold pl-1">
                        {{ form.errors.terms_accepted }}
                    </p>
                </div>

                <!-- Botón de Envío -->
                <BaseButton
                    type="submit"
                    class="w-full min-h-[48px] mt-2 font-bold text-sm shadow-md"
                    :disabled="form.processing || !form.terms_accepted"
                >
                    {{ form.processing ? 'Creando cuenta…' : 'Crear cuenta con correo' }}
                </BaseButton>
            </form>
        </div>

        <!-- Enlace a login -->
        <p class="mt-6 text-center text-sm text-content-secondary">
            ¿Ya tienes cuenta?
            <Link
                :href="route('login')"
                class="font-bold text-primary-strong underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong rounded"
            >
                Inicia sesión aquí
            </Link>
        </p>
    </GuestLayout>
</template>
