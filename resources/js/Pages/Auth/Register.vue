<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseSelect from '@/Components/ui/BaseSelect.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

/*
 * Pantalla de registro — Epycus (Fase 1).
 * Formulario: Nombre completo, correo, contraseña, fecha de nacimiento, género, carrera.
 * Regla Género → Personaje/Avatar:
 *   - Masculino ('m')
 *   - Femenino ('f')
 *   - Prefiero no decir ('unspecified') → Asigna por defecto el avatar masculino ('m').
 * Términos y condiciones: Checkbox de aceptación y enlace a /terms.
 *
 * NOTA DE ARQUITECTURA (Cuestionario Pre-Uso Futuro):
 * Posterior al registro/completar perfil, en el futuro se intercalará el cuestionario
 * pre-uso del sistema (antes de acceder al Dashboard principal). Se deja estructurado
 * para integrarse fluidamente manteniendo una experiencia limpia ("todo bonito").
 */

const props = defineProps({
    careers: {
        type: Array,
        default: () => [
            'Ingeniería de Sistemas',
            'Ingeniería Civil',
            'Ingeniería Industrial',
            'Ingeniería de Minas',
            'Arquitectura',
            'Administración de Empresas',
            'Contabilidad',
            'Medicina',
            'Enfermería',
            'Obstetricia',
            'Derecho',
        ],
    },
});

const careerOptions = computed(() =>
    props.careers.map((c) => ({ value: c, label: c }))
);

const genderOptions = [
    { value: 'm', label: 'Masculino' },
    { value: 'f', label: 'Femenino' },
    { value: 'unspecified', label: 'Prefiero no decir' },
];

const form = useForm({
    name: '',
    alias: '',
    email: '',
    password: '',
    password_confirmation: '',
    birthdate: '',
    gender: 'm',
    career: '',
    terms_accepted: false,
});

const submit = () => {
    // Si selecciona "Prefiero no decir", se mapea avatar de género masculino por defecto
    const avatarGender = form.gender === 'unspecified' ? 'm' : form.gender;

    form.transform((data) => ({
        ...data,
        avatar_gender: avatarGender,
    })).post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Crear cuenta" />

    <GuestLayout>
        <template #tagline>
            <p class="text-sm font-medium text-content-secondary">
                Comienza tu progreso
            </p>
        </template>

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

            <!-- Alias público: visible en el ranking -->
            <BaseInput
                id="alias"
                v-model="form.alias"
                label="Alias (visible en el ranking)"
                type="text"
                autocomplete="username"
                :error="form.errors.alias"
                required
            />

            <BaseInput
                id="email"
                v-model="form.email"
                label="Correo electrónico"
                type="email"
                autocomplete="email"
                :error="form.errors.email"
                required
            />

            <!-- Fecha de nacimiento -->
            <BaseInput
                id="birthdate"
                v-model="form.birthdate"
                label="Fecha de nacimiento"
                type="date"
                :error="form.errors.birthdate"
                required
            />

            <!-- Género -->
            <BaseSelect
                id="gender"
                v-model="form.gender"
                label="Género"
                :options="genderOptions"
                placeholder="Selecciona tu género"
                :error="form.errors.gender"
                required
            />

            <!-- Carrera -->
            <BaseSelect
                id="career"
                v-model="form.career"
                label="Carrera profesional"
                :options="careerOptions"
                placeholder="Selecciona tu carrera"
                :error="form.errors.career"
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
                <label class="flex min-h-[44px] cursor-pointer items-start gap-3 text-sm text-content-secondary">
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
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
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
