<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';
import { 
    Swords, Brain, Lightbulb, Heart, Shield, 
    Award, TrendingUp, Sparkles, Star,
    User, Settings, Compass
} from '@lucide/vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const props = defineProps({
    progress: {
        type: Object,
        required: true,
    },
    skills: {
        type: Array,
        required: true,
    }
});

function getIconForSkill(key) {
    switch (key?.toLowerCase()) {
        case 'intelecto':
        case 'intellect':
            return Brain;
        case 'creatividad':
        case 'creativity':
            return Lightbulb;
        case 'disciplina':
        case 'discipline':
            return Shield;
        case 'vitalidad':
        case 'vitality':
            return Heart;
        default:
            return Award;
    }
}

function getEmojiForSkill(key) {
    switch (key?.toLowerCase()) {
        case 'intelecto': return '🧠';
        case 'creatividad': return '💡';
        case 'disciplina': return '🛡️';
        case 'vitalidad': return '❤️';
        default: return '';
    }
}

const heroTitles = [
    'Novato del Conocimiento',
    'Escudero del Hábito',
    'Guerrero de la Disciplina',
    'Erudito Estratégico',
    'Maestro del Enfoque',
    'Campeón Legendario',
];

const currentHeroTitle = computed(() => {
    const idx = Math.min(heroTitles.length - 1, Math.max(0, (props.progress.current_level || 1) - 1));
    return heroTitles[idx];
});
</script>

<template>
    <Head title="Perfil del Héroe, Avatar & Gamificación - Epycus" />

    <AppLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- HEADER CON ENLACE DIRECTO AL PERFIL ACADÉMICO / CUENTA -->
            <div class="flex items-center justify-between flex-wrap gap-4 pb-2 border-b border-border/60">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-surface-raised border border-primary/30 flex items-center justify-center overflow-hidden shadow-xs shrink-0 p-1">
                        <ProceduralAvatar
                            :career="authUser?.avatar_style || authUser?.career || 'base'"
                            :gender="authUser?.avatar_gender || 'm'"
                            :avatar-options="authUser?.avatar_options"
                            :phase="progress.current_phase || 1"
                            :size="52"
                        />
                    </div>
                    <div>
                        <h1 class="font-display text-2xl sm:text-3xl font-black text-content-primary tracking-tight">
                            Perfil del Héroe & Progreso
                        </h1>
                        <p class="text-xs sm:text-sm text-content-secondary mt-0.5">
                            Tu identidad RPG, nivel de aventurero y atributos clave.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="route('profile.edit')">
                        <BaseButton variant="secondary" size="sm" class="shadow-sm">
                            <Settings :size="15" class="mr-1.5" />
                            Datos de Cuenta & Universidad
                        </BaseButton>
                    </Link>
                </div>
            </div>

            <!-- HEADER / GLOBAL STATS & AVATAR CANVAS -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <!-- AVATAR & LEVEL OVERVIEW -->
                <div class="md:col-span-5 lg:col-span-4">
                    <BaseCard class="h-full relative overflow-hidden bg-gradient-to-br from-surface to-surface-raised border border-border/60 p-6 flex flex-col items-center text-center justify-between">
                        <!-- Glow effect behind avatar -->
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-64 h-64 bg-primary/20 rounded-full blur-3xl opacity-50"></div>
                        
                        <div class="relative z-10 w-full flex flex-col items-center">
                            <!-- Avatar Procedural Frame -->
                            <div class="relative mb-4">
                                <div class="w-36 h-36 rounded-3xl border-4 border-primary/40 bg-surface-sunken flex items-center justify-center overflow-hidden shadow-2xl relative z-10 p-2">
                                    <ProceduralAvatar
                                        :career="authUser?.avatar_style || authUser?.career || 'base'"
                                        :gender="authUser?.avatar_gender || 'm'"
                                        :avatar-options="authUser?.avatar_options"
                                        :phase="progress.current_phase || 1"
                                        :size="180"
                                    />
                                </div>
                                <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 bg-primary-strong text-white font-black px-4 py-1 rounded-full border-2 border-surface shadow-lg text-xs z-20 flex items-center gap-1 whitespace-nowrap">
                                    <Star :size="12" class="fill-current" />
                                    NIVEL {{ progress.current_level }}
                                </div>
                            </div>
                            
                            <h2 class="text-xl font-black text-content-primary mt-2 flex items-center gap-2">
                                {{ currentHeroTitle }}
                            </h2>
                            <p class="text-content-secondary font-medium text-xs mb-4">
                                Fase del Viaje del Héroe: {{ progress.current_phase }}
                            </p>
                        </div>
                        
                        <div class="w-full space-y-2 relative z-10 mt-2">
                            <div class="flex justify-between text-xs font-bold text-content-secondary">
                                <span>Experiencia Total</span>
                                <span class="text-primary-strong">{{ progress.total_xp }} / {{ progress.next_level_xp }} XP</span>
                            </div>
                            <div class="h-3 w-full bg-surface-sunken rounded-full overflow-hidden shadow-inner relative">
                                <div 
                                    class="absolute top-0 left-0 h-full bg-gradient-to-r from-primary to-primary-strong transition-all duration-1000 ease-out"
                                    :style="{ width: `${progress.progress_percent}%` }"
                                ></div>
                            </div>
                            <div class="text-right text-[10px] text-content-muted font-bold">
                                {{ progress.progress_percent }}% para el siguiente nivel
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- QUICK STATS & REWARDS SUMMARY -->
                <div class="md:col-span-7 lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <BaseCard class="p-6 flex flex-col justify-between bg-gradient-to-br from-surface to-surface-raised border border-border/50">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-500 flex items-center justify-center shadow-sm">
                                <Award :size="24" />
                            </div>
                            <div>
                                <h3 class="font-bold text-content-secondary text-xs uppercase tracking-wider">Monedas de Recompensa</h3>
                                <div class="text-3xl font-black text-content-primary mt-0.5">
                                    {{ progress.coins }} <span class="text-amber-500 text-xl">🪙</span>
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-border/60 flex items-center justify-between">
                            <p class="text-xs text-content-muted font-medium">Canjeables en la Tienda</p>
                            <Link :href="route('shop.index')" class="text-xs font-bold text-primary-strong hover:underline">
                                Ir a la Tienda →
                            </Link>
                        </div>
                    </BaseCard>

                    <BaseCard class="p-6 flex flex-col justify-between bg-gradient-to-br from-surface to-surface-raised border border-border/50">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center shadow-sm">
                                <TrendingUp :size="24" />
                            </div>
                            <div>
                                <h3 class="font-bold text-content-secondary text-xs uppercase tracking-wider">Racha Actual de Hábitos</h3>
                                <div class="text-3xl font-black text-content-primary mt-0.5">
                                    {{ progress.current_streak }} <span class="text-emerald-500 text-xl">🔥</span>
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-border/60 flex items-center justify-between">
                            <p class="text-xs text-content-muted font-medium">Récord: {{ progress.longest_streak }} días</p>
                            <Link :href="route('habits.index')" class="text-xs font-bold text-primary-strong hover:underline">
                                Ver Hábitos →
                            </Link>
                        </div>
                    </BaseCard>

                    <BaseCard class="p-6 flex flex-col justify-between bg-gradient-to-br from-surface to-surface-raised border border-border/50">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 text-indigo-500 flex items-center justify-center shadow-sm">
                                <Compass :size="24" />
                            </div>
                            <div>
                                <h3 class="font-bold text-content-secondary text-xs uppercase tracking-wider">Viaje del Héroe</h3>
                                <div class="text-2xl font-black text-content-primary mt-0.5">
                                    Fase {{ progress.current_phase }} / 10
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-border/60">
                            <p class="text-xs text-content-muted font-medium">Desbloqueas nuevos rangos al avanzar cada 5 niveles.</p>
                        </div>
                    </BaseCard>

                    <BaseCard class="p-6 flex flex-col justify-between bg-gradient-to-br from-surface to-surface-raised border border-border/50">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-purple-500/20 text-purple-500 flex items-center justify-center shadow-sm">
                                <User :size="24" />
                            </div>
                            <div>
                                <h3 class="font-bold text-content-secondary text-xs uppercase tracking-wider">Perfil Académico</h3>
                                <div class="text-lg font-bold text-content-primary truncate mt-0.5">
                                    Configuración de Alumno
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-border/60 flex items-center justify-between">
                            <p class="text-xs text-content-muted font-medium">Ciclo, carrera y credenciales</p>
                            <Link :href="route('profile.edit')" class="text-xs font-bold text-primary-strong hover:underline">
                                Editar Perfil →
                            </Link>
                        </div>
                    </BaseCard>
                </div>
            </div>

            <!-- SKILLS / ATRIBUTOS RPG ACTUALIZADOS CON SÍMBOLOS E ÍCONOS -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-black text-content-primary flex items-center gap-2">
                        <Swords :size="24" class="text-primary-strong" />
                        Atributos del Aventurero
                    </h3>
                    <span class="text-xs text-content-muted font-bold">
                        {{ skills.length }} Atributos RPG
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseCard 
                        v-for="skill in skills" 
                        :key="skill.id" 
                        class="p-5 border border-border/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-primary/40 group relative overflow-hidden"
                    >
                        <!-- Top color strip -->
                        <div 
                            class="absolute top-0 left-0 w-full h-1 opacity-70"
                            :style="{ backgroundColor: skill.color || '#3b82f6' }"
                        ></div>

                        <div class="flex items-start gap-4">
                            <div 
                                class="w-14 h-14 rounded-2xl flex items-center justify-center text-white flex-shrink-0 shadow-md transition-transform group-hover:scale-105 relative"
                                :style="{ backgroundColor: skill.color || '#3b82f6' }"
                            >
                                <component :is="getIconForSkill(skill.key)" :size="26" />
                                <span v-if="getEmojiForSkill(skill.key)" class="absolute -top-1.5 -right-1.5 text-xs">
                                    {{ getEmojiForSkill(skill.key) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <h4 class="font-black text-base text-content-primary flex items-center gap-1.5">
                                            <span>{{ skill.name }}</span>
                                            <span v-if="getEmojiForSkill(skill.key)" class="text-xs">{{ getEmojiForSkill(skill.key) }}</span>
                                        </h4>
                                        <p class="text-xs text-content-secondary line-clamp-1 mt-0.5">{{ skill.description }}</p>
                                    </div>
                                    <div class="bg-surface-raised px-2.5 py-1 rounded-xl border border-border font-black text-xs text-content-primary shadow-xs">
                                        Nivel {{ skill.level }}
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <div class="flex justify-between text-[10px] font-bold text-content-muted mb-1.5 uppercase tracking-wider">
                                        <span>Progreso</span>
                                        <span class="text-primary-strong">{{ skill.xp }} / {{ skill.next_level_xp }} XP</span>
                                    </div>
                                    <div class="h-2.5 w-full bg-surface-sunken rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-1000 ease-out"
                                            :style="{ width: `${skill.progress_percent}%`, backgroundColor: skill.color || '#3b82f6' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </BaseCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
