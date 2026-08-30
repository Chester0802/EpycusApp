<script setup>
import { ref, computed } from 'vue';
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
import { Head, usePage } from '@inertiajs/vue3';
import { ShieldCheck, Trophy, Sparkles, CheckCircle2, Lock } from '@lucide/vue';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isGoogleAccount = computed(() => Boolean(currentUser.value?.google_id));

const props = defineProps({
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
    achievementsData: {
        type: Object,
        default: () => ({
            total_count: 0,
            unlocked_count: 0,
            progress_percent: 0,
            total_xp_earned: 0,
            achievements: [],
        }),
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

const urlParams = new URLSearchParams(window.location.search);
const initialTab = urlParams.get('tab') === 'achievements' ? 'achievements' : 'hero-path';
const activeTab = ref(initialTab); // 'hero-path' | 'avatar' | 'achievements' | 'settings'

// ── Lógica de Logros Integrados ────────────────────────────────────────────
const activeCategory = ref('all');
const searchQuery = ref('');
const filterStatus = ref('all'); // 'all' | 'unlocked' | 'locked'

const rawCategories = [
    { id: 'all', label: 'Todos', icon: '🏆' },
    { id: 'constancia', label: 'Constancia', icon: '🔥' },
    { id: 'volumen', label: 'Pomodoro', icon: '⏱️' },
    { id: 'misiones', label: 'Misiones', icon: '🎯' },
    { id: 'habitos', label: 'Hábitos', icon: '🌱' },
    { id: 'estudio_grupal', label: 'Grupos', icon: '👥' },
    { id: 'villanos', label: 'Villanos', icon: '⚔️' },
    { id: 'bienestar', label: 'Diario', icon: '🧘' },
    { id: 'progresion', label: 'Nivel', icon: '🥋' },
];

const achievementsList = computed(() => props.achievementsData?.achievements || []);

const filteredAchievements = computed(() => {
    return achievementsList.value.filter((ach) => {
        const matchesCat = activeCategory.value === 'all' || ach.category === activeCategory.value;
        const matchesStatus =
            filterStatus.value === 'all' ||
            (filterStatus.value === 'unlocked' && ach.is_unlocked) ||
            (filterStatus.value === 'locked' && !ach.is_unlocked);
        const matchesSearch =
            !searchQuery.value.trim() ||
            ach.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            ach.description.toLowerCase().includes(searchQuery.value.toLowerCase());

        return matchesCat && matchesStatus && matchesSearch;
    });
});
</script>

<template>
    <Head title="Perfil del Estudiante — Epycus" />

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

            <!-- Pestañas de Navegación del Perfil Unificado -->
            <div class="flex items-center gap-2 border-b border-border/70 pb-2 overflow-x-auto">
                <button
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 cursor-pointer whitespace-nowrap"
                    :class="
                        activeTab === 'hero-path'
                            ? 'bg-primary-strong text-on-primary-strong shadow-sm'
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
                            ? 'bg-primary-strong text-on-primary-strong shadow-sm'
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
                        activeTab === 'achievements'
                            ? 'bg-primary-strong text-on-primary-strong shadow-sm'
                            : 'text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                    "
                    @click="activeTab = 'achievements'"
                >
                    <span>🏆</span> Mis Logros & Medallas
                </button>

                <button
                    type="button"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 cursor-pointer whitespace-nowrap"
                    :class="
                        activeTab === 'settings'
                            ? 'bg-primary-strong text-on-primary-strong shadow-sm'
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
                <BaseCard class="p-6">
                    <div class="flex items-center justify-between text-xs font-semibold text-content-secondary mb-1.5">
                        <span>Progreso hacia Nivel {{ progress.level + 1 }}</span>
                        <span>{{ progress.currentLevelXp }} / {{ progress.nextLevelXpNeeded }} XP ({{ progress.levelProgressPercent }}%)</span>
                    </div>
                    <ProgressBar
                        :current="progress.currentLevelXp"
                        :max="progress.nextLevelXpNeeded"
                        height="h-3"
                        color="bg-primary-strong"
                    />
                </BaseCard>

                <BaseCard class="p-6">
                    <AvatarCustomizer
                        :user-id="profileData.id"
                        :user-phase="progress.phase"
                        :initial-style="avatarStyle"
                        :initial-gender="avatarGender ?? 'm'"
                        :initial-options="avatarOptions"
                    />
                </BaseCard>
            </div>

            <!-- Contenido Pestaña 3: Mis Logros e Insignias Integradas -->
            <div v-else-if="activeTab === 'achievements'" class="space-y-6">
                <!-- Resumen de Logros -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <BaseCard class="p-4 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-content-muted">Total Desbloqueados</span>
                        <div class="font-display text-2xl font-black text-content-primary mt-1">
                            {{ achievementsData.unlocked_count }} / {{ achievementsData.total_count }}
                        </div>
                        <span class="text-[11px] text-content-muted">{{ achievementsData.progress_percent }}% de la colección</span>
                    </BaseCard>

                    <BaseCard class="p-4 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-content-muted">XP Total Ganado</span>
                        <div class="font-display text-2xl font-black text-primary-strong mt-1">
                            +{{ achievementsData.total_xp_earned }}
                        </div>
                        <span class="text-[11px] text-content-muted">en medallas e hitos</span>
                    </BaseCard>

                    <BaseCard class="col-span-2 p-4 flex flex-col justify-between">
                        <span class="text-xs font-semibold text-content-muted">Progreso de Colección</span>
                        <div class="w-full bg-surface-sunken rounded-full h-2.5 mt-2 overflow-hidden border border-border">
                            <div class="bg-primary-strong h-2.5 rounded-full transition-all duration-500" :style="{ width: `${achievementsData.progress_percent}%` }"></div>
                        </div>
                        <span class="text-[11px] text-content-muted mt-1">Completa hábitos, misiones y rutinas para desbloquear más insignias</span>
                    </BaseCard>
                </div>

                <!-- Filtros y Categorías -->
                <BaseCard class="p-4 space-y-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            v-for="cat in rawCategories"
                            :key="cat.id"
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                                activeCategory === cat.id
                                    ? 'bg-primary-strong text-on-primary-strong shadow-sm'
                                    : 'bg-surface border border-border text-content-secondary hover:bg-surface-raised hover:text-content-primary'
                            ]"
                            @click="activeCategory = cat.id"
                        >
                            {{ cat.icon }} {{ cat.label }}
                        </button>
                    </div>
                </BaseCard>

                <!-- Grid de Logros -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="ach in filteredAchievements"
                        :key="ach.id"
                        :class="[
                            'p-4 rounded-3xl border transition-all duration-300 flex items-start gap-3.5',
                            ach.is_unlocked
                                ? 'bg-surface border-border hover:border-primary/50 shadow-sm'
                                : 'bg-surface/50 border-border/40 opacity-75'
                        ]"
                    >
                        <div
                            class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shrink-0 border"
                            :class="ach.is_unlocked ? 'bg-primary/15 border-primary/30 text-primary-strong' : 'bg-surface-sunken border-border text-content-muted'"
                        >
                            <span v-if="ach.is_unlocked">{{ ach.icon || '🏆' }}</span>
                            <Lock v-else :size="18" class="text-content-muted" />
                        </div>

                        <div class="space-y-1 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <h4 class="font-bold text-xs sm:text-sm text-content-primary leading-tight">
                                    {{ ach.name }}
                                </h4>
                                <span class="text-[10px] font-bold text-warning shrink-0">
                                    ⚡ +{{ ach.xp_reward }} XP
                                </span>
                            </div>
                            <p class="text-[11px] text-content-secondary leading-normal">
                                {{ ach.description }}
                            </p>

                            <!-- Barra de progreso si está bloqueado -->
                            <div v-if="!ach.is_unlocked && ach.progress_percent > 0" class="pt-1.5 space-y-1">
                                <div class="w-full bg-surface-sunken rounded-full h-1.5 overflow-hidden border border-border">
                                    <div class="bg-primary-strong h-1.5 rounded-full" :style="{ width: `${ach.progress_percent}%` }"></div>
                                </div>
                                <span class="text-[10px] text-content-muted block">{{ ach.progress_percent }}% completado</span>
                            </div>
                            <span v-else-if="ach.is_unlocked" class="text-[10px] text-success font-bold flex items-center gap-1 pt-1">
                                <CheckCircle2 :size="11" /> Desbloqueado
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido Pestaña 4: Ajustes de Cuenta -->
            <div v-else-if="activeTab === 'settings'" class="space-y-6">
                <!-- Información Académica (Siempre visible) -->
                <BaseCard class="p-6">
                    <UpdateAcademicInformationForm
                        :initial-career="profileData.career"
                        :initial-cycle="profileData.cycle"
                        :initial-institution-type="profileData.institutionType"
                        :careers="careers"
                        :cycles="cycles"
                        :institution-types="institutionTypes"
                    />
                </BaseCard>

                <!-- USUARIOS CON CUENTA GOOGLE: Panel Informativo de Seguridad -->
                <BaseCard v-if="isGoogleAccount" class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-sunken border border-border">
                                <svg class="h-5 w-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-content-primary">Cuenta vinculada con Google</h3>
                                <p class="text-xs text-content-secondary">Tu autenticación y seguridad están gestionadas por Google.</p>
                            </div>
                        </div>

                        <div class="rounded-xl bg-surface-sunken p-4 border border-border text-xs text-content-secondary space-y-1.5">
                            <p class="text-content-primary font-semibold">
                                Correo: <span class="font-normal text-content-secondary">{{ currentUser?.email }}</span>
                            </p>
                            <p class="text-content-muted leading-relaxed">
                                Tu nombre, correo y contraseña están protegidos por tu cuenta de Google. No necesitas administrar contraseñas en Epycus.
                            </p>
                        </div>
                    </div>
                </BaseCard>

                <!-- USUARIOS CON CUENTA MANUAL: Vincular con Google + Actualizar Información + Actualizar Contraseña -->
                <template v-else>
                    <!-- Tarjeta para vincular con Google -->
                    <BaseCard class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="space-y-1">
                                <h3 class="text-base font-bold text-content-primary">
                                    Vincular cuenta con Google
                                </h3>
                                <p class="text-xs text-content-secondary">
                                    Conecta tu cuenta para iniciar sesión en 1 solo clic.
                                </p>
                            </div>
                            <a
                                :href="route('auth.google')"
                                class="inline-flex min-h-[44px] items-center justify-center gap-2.5 rounded-xl border border-border-interactive bg-surface-raised px-4 py-2 text-xs font-bold text-content-primary shadow-sm hover:bg-surface hover:border-primary-strong transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong cursor-pointer whitespace-nowrap"
                            >
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span>Vincular con Google</span>
                            </a>
                        </div>
                    </BaseCard>

                    <!-- Información Personal y Alias -->
                    <BaseCard class="p-6">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </BaseCard>

                    <!-- Seguridad y Contraseña -->
                    <BaseCard class="p-6">
                        <UpdatePasswordForm />
                    </BaseCard>
                </template>

                <!-- Eliminar Cuenta (Siempre visible) -->
                <BaseCard class="p-6 border-danger/40">
                    <DeleteUserForm />
                </BaseCard>
            </div>
        </div>
    </AppLayout>
</template>
