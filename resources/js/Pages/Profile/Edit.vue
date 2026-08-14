<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import UpdateAcademicInformationForm from './Partials/UpdateAcademicInformationForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import AvatarCustomizer from '@/Components/AvatarCustomizer.vue';
import StudentIdCard from '@/Components/ui/StudentIdCard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
    avatarStyle: {
        type: String,
        default: 'base',
    },
    avatarGender: {
        type: String,
        default: 'm',
    },
    avatarOptions: {
        type: Object,
        default: () => ({}),
    },
    progress: {
        type: Object,
        default: () => ({
            phase: 1,
            level: 1,
            currentStreak: 0,
            totalXp: 0,
            coins: 0,
            currentLevelXp: 0,
            nextLevelXpNeeded: 100,
            levelProgressPercent: 0,
        }),
    },
    participantCode: {
        type: String,
        default: null,
    },
    profileData: {
        type: Object,
        default: () => ({}),
    },
    careers: {
        type: Object,
        default: () => ({}),
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    institutionTypes: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Perfil — Epycus" />

    <AppLayout>
        <h1 class="mb-6 font-display text-3xl font-bold text-content-primary">Perfil</h1>

        <div class="space-y-6">
            <!-- Credencial de Estudiante Digital Holográfica -->
            <StudentIdCard
                :user-name="profileData.alias || 'Estudiante'"
                :user-career="profileData.career"
                :avatar-style="avatarStyle"
                :avatar-gender="avatarGender ?? 'm'"
                :avatar-options="avatarOptions"
                :progress="progress"
            />

            <!-- Barra de Progreso de Nivel (XP) y Monedas -->
            <BaseCard class="p-6">
                <div
                    class="flex items-center justify-between text-xs font-semibold text-content-secondary mb-1.5"
                >
                    <span>Progreso hacia Nivel {{ progress.level + 1 }}</span>
                    <span
                        >{{ progress.currentLevelXp }} / {{ progress.nextLevelXpNeeded }} XP ({{
                            progress.levelProgressPercent
                        }}%)</span
                    >
                </div>
                <ProgressBar
                    :value="progress.currentLevelXp"
                    :max="progress.nextLevelXpNeeded"
                    color="bg-primary-strong"
                    size="h-3"
                />
            </BaseCard>

            <!-- Editor / Creador de Avatar Personalizable -->
            <AvatarCustomizer :initial-options="avatarOptions" :gender="avatarGender" />

            <!-- Formulario de Información de Cuenta -->
            <BaseCard class="p-6">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </BaseCard>

            <!-- Formulario de Información Académica -->
            <BaseCard class="p-6">
                <UpdateAcademicInformationForm
                    :careers="careers"
                    :cycles="cycles"
                    :institution-types="institutionTypes"
                    class="max-w-xl"
                />
            </BaseCard>

            <!-- Seguridad / Contraseña -->
            <BaseCard class="p-6">
                <UpdatePasswordForm class="max-w-xl" />
            </BaseCard>

            <BaseCard class="p-6">
                <DeleteUserForm class="max-w-xl" />
            </BaseCard>
        </div>
    </AppLayout>
</template>
