<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import UpdateAcademicInformationForm from './Partials/UpdateAcademicInformationForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import AvatarCustomizer from '@/Components/AvatarCustomizer.vue';
import StudentIdCard from '@/Components/ui/StudentIdCard.vue';
import HerosPathMap from '@/Components/ui/HerosPathMap.vue';
import { Head } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';

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
    characterStats: {
        type: Object,
        default: () => ({}),
    },
    herosJourneyPhases: {
        type: Array,
        default: () => [],
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

const activeTab = ref('hero-path'); // 'hero-path' | 'avatar' | 'settings'
</script>

<template>
    <Head title="Perfil — Epycus" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Encabezado de Perfil & Credencial Holográfica -->
            <StudentIdCard
                :user-name="profileData.alias || 'Estudiante'"
                :user-career="profileData.career"
                :avatar-style="avatarStyle"
                :avatar-gender="avatarGender ?? 'm'"
                :avatar-options="avatarOptions"
                :progress="progress"
            />

            <!-- Pestañas de Navegación del Perfil RPG -->
            <div class="flex items-center gap-2 border-b border-border-interactive/60 pb-2 overflow-x-auto">
                <button
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 cursor-pointer whitespace-nowrap"
                    :class="
                        activeTab === 'hero-path'
                            ? 'bg-primary-strong text-on-accent shadow-sm'
                            : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    "
                    @click="activeTab = 'hero-path'"
                >
                    <span>🗺️</span> El Camino del Héroe
                </button>

                <button
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 cursor-pointer whitespace-nowrap"
                    :class="
                        activeTab === 'avatar'
                            ? 'bg-primary-strong text-on-accent shadow-sm'
                            : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    "
                    @click="activeTab = 'avatar'"
                >
                    <span>🎨</span> Personalizar Avatar
                </button>

                <button
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 cursor-pointer whitespace-nowrap"
                    :class="
                        activeTab === 'settings'
                            ? 'bg-primary-strong text-on-accent shadow-sm'
                            : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    "
                    @click="activeTab = 'settings'"
                >
                    <span>⚙️</span> Ajustes de Cuenta
                </button>
            </div>

            <!-- Contenido Pestaña 1: El Camino del Héroe (Fases 1 a 10) -->
            <div v-if="activeTab === 'hero-path'">
                <HerosPathMap
                    :phases="herosJourneyPhases"
                    :current-level="progress.level"
                    :current-phase="progress.phase"
                    :total-xp="progress.totalXp"
                />
            </div>

            <!-- Contenido Pestaña 2: Personalizar Avatar -->
            <div v-else-if="activeTab === 'avatar'" class="space-y-6">
                <!-- Barra de Progreso de Nivel (XP) -->
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
            </div>

            <!-- Contenido Pestaña 3: Ajustes de Cuenta & Información Académica -->
            <div v-else-if="activeTab === 'settings'" class="space-y-6">
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

                <!-- Seguridad / Contraseña (Solo para usuarios con registro manual, no Google) -->
                <BaseCard v-if="!$page.props.auth.user?.google_id" class="p-6">
                    <UpdatePasswordForm class="max-w-xl" />
                </BaseCard>
                <BaseCard v-else class="p-6">
                    <div class="flex items-center gap-3 text-sm text-content-secondary">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <ShieldCheck :size="20" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-content-primary">Seguridad de la Cuenta</h3>
                            <p class="text-xs text-content-muted mt-0.5">
                                Tu cuenta utiliza la autenticación de <strong>Google</strong>. Las credenciales y contraseñas son administradas de forma segura directamente por Google.
                            </p>
                        </div>
                    </div>
                </BaseCard>

                <BaseCard class="p-6">
                    <DeleteUserForm class="max-w-xl" />
                </BaseCard>
            </div>
        </div>
    </AppLayout>
</template>

