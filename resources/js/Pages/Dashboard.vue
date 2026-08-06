<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import UsageTipBanner from '@/Components/ui/UsageTipBanner.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ProceduralAvatar from '@/Components/ProceduralAvatar.vue';

const props = defineProps({
    userName: { type: String, default: 'Estudiante' },
    userCareer: { type: String, default: null },
    avatarStyle: { type: String, default: null },
    avatarGender: { type: String, default: null },
    progress: { type: Object, required: true },
    activity: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    villain: { type: Object, default: null },
    motivationalQuote: { type: Object, default: null },
    avatarImage: { type: String, default: null },
});

const activeTab = ref('focus'); // 'focus' | 'habits'

const totalWeeklyFocusMinutes = computed(() => {
    return props.activity.reduce((sum, d) => sum + (d.focusMinutes || 0), 0);
});

const totalWeeklyHabits = computed(() => {
    return props.activity.reduce((sum, d) => sum + (d.habitsDone || 0), 0);
});

const maxFocusMinutes = computed(() => {
    const max = Math.max(...props.activity.map((d) => d.focusMinutes || 0), 60);
    return max === 0 ? 60 : max;
});

const maxHabitsDone = computed(() => {
    const max = Math.max(...props.activity.map((d) => d.habitsDone || 0), 5);
    return max === 0 ? 5 : max;
});

function focusBarHeight(minutes) {
    if (!minutes) return 4;
    return Math.max(8, Math.min(100, Math.round((minutes / maxFocusMinutes.value) * 100)));
}

function habitsBarHeight(count) {
    if (!count) return 4;
    return Math.max(8, Math.min(100, Math.round((count / maxHabitsDone.value) * 100)));
}
</script>

<template>
    <Head title="Inicio" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header Card: Saludo + Avatar + Acciones Rápidas -->
            <BaseCard class="p-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            v-if="avatarImage || avatarStyle"
                            class="relative flex h-36 w-24 shrink-0 items-center justify-center rounded-2xl p-2 border border-border-interactive shadow-sm"
                        >
                            <ProceduralAvatar
                                v-if="avatarStyle"
                                :career="avatarStyle"
                                :gender="avatarGender ?? 'm'"
                                :phase="progress.phase"
                            />
                            <img
                                v-else
                                :src="avatarImage"
                                alt="Tu avatar"
                                class="h-full w-full object-contain"
                            />
                            <span
                                class="absolute -bottom-2 -right-2 rounded-full bg-primary-strong px-2 py-0.5 text-[10px] font-bold text-on-accent shadow"
                            >
                                Fase {{ progress.phase }}
                            </span>
                        </div>
                        <div>
                            <h1
                                class="font-display text-3xl font-bold tracking-tight text-content-primary"
                            >
                                ¡Hola, {{ userName }}!
                            </h1>
                            <p
                                v-if="userCareer"
                                class="mt-1 text-sm font-medium text-primary-strong"
                            >
                                {{ userCareer }}
                            </p>
                            <p class="mt-1 text-sm text-content-secondary">
                                Revisa tu progreso académico y mantén tu racha activa hoy.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-surface-raised px-3 py-1 font-semibold text-content-primary border border-border-interactive"
                                >
                                    <AppIcon name="trophy" :size="14" class="text-warning" /> Nivel
                                    {{ progress.level }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-surface-raised px-3 py-1 font-semibold text-content-primary border border-border-interactive"
                                >
                                    <AppIcon name="flame" :size="14" class="text-danger" />
                                    {{ progress.currentStreak }} días de racha
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-surface-raised px-3 py-1 font-semibold text-content-primary border border-border-interactive"
                                >
                                    <AppIcon name="coins" :size="14" class="text-warning" />
                                    {{ progress.coins }} monedas
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 sm:flex-col sm:items-end">
                        <Link :href="route('pomodoro.index')">
                            <BaseButton variant="primary" size="sm">
                                <AppIcon name="timer" :size="14" class="mr-1" /> Iniciar Pomodoro
                            </BaseButton>
                        </Link>
                        <Link :href="route('habits.index')">
                            <BaseButton variant="secondary" size="sm">
                                <AppIcon name="zap" :size="14" class="mr-1" /> Ver Hábitos
                            </BaseButton>
                        </Link>
                    </div>
                </div>

                <!-- Barra de Progreso de Nivel (XP) -->
                <div class="mt-6 border-t border-border-interactive pt-4">
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
                </div>
            </BaseCard>

            <!-- Usage Tip Banner descartable -->
            <UsageTipBanner module="dashboard" />

            <!-- Frase Motivacional de Inicio de Sesión -->
            <BaseCard v-if="motivationalQuote" class="p-5 border-l-4 border-l-primary-strong">
                <div class="flex items-start gap-3">
                    <span class="text-2xl shrink-0">📜</span>
                    <div class="space-y-1">
                        <blockquote
                            class="italic text-sm text-content-primary font-medium leading-relaxed"
                        >
                            "{{ motivationalQuote.text }}"
                        </blockquote>
                        <p class="text-xs text-content-secondary font-semibold">
                            — {{ motivationalQuote.author }}
                            <span
                                v-if="!motivationalQuote.is_verified"
                                class="text-[11px] text-content-muted font-normal ml-1"
                                title="Frase popular atribuida"
                            >
                                (Atribuida)
                            </span>
                        </p>
                    </div>
                </div>
            </BaseCard>

            <!-- Cuadrícula de Métricas Rápidas de Hoy -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <BaseCard class="p-4 text-center space-y-1">
                    <AppIcon name="timer" :size="28" class="mx-auto text-primary-strong" />
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ stats.todayFocusMinutes }}
                        <span class="text-xs font-normal text-content-muted">min</span>
                    </p>
                    <p class="text-xs text-content-secondary">Foco de hoy</p>
                </BaseCard>

                <BaseCard class="p-4 text-center space-y-1">
                    <AppIcon name="zap" :size="28" class="mx-auto text-warning" />
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ stats.todayHabitsDone }}
                    </p>
                    <p class="text-xs text-content-secondary">Hábitos de hoy</p>
                </BaseCard>

                <BaseCard class="p-4 text-center space-y-1">
                    <AppIcon name="clipboard" :size="28" class="mx-auto text-accent" />
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ stats.pendingMissions }}
                    </p>
                    <p class="text-xs text-content-secondary">Misiones pendientes</p>
                </BaseCard>

                <BaseCard class="p-4 text-center space-y-1">
                    <AppIcon name="check-circle" :size="28" class="mx-auto text-success" />
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ stats.completedMissions }}
                    </p>
                    <p class="text-xs text-content-secondary">Misiones completadas</p>
                </BaseCard>
            </div>

            <!-- Gráfica de Actividad Semanal + Desafío Villano -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Gráfica Visual de Actividad -->
                <BaseCard class="p-6 lg:col-span-2 space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2
                                class="font-display text-lg font-bold text-content-primary flex items-center gap-2"
                            >
                                <AppIcon name="bar-chart" :size="20" class="text-primary-strong" />
                                Actividad Semanal
                            </h2>
                            <p class="text-xs text-content-secondary">
                                Resumen de rendimiento en los últimos 7 días
                            </p>
                        </div>
                        <div
                            class="flex items-center gap-1 rounded-xl bg-surface-sunken p-1 border border-border-interactive text-xs font-semibold"
                        >
                            <button
                                type="button"
                                class="flex items-center gap-1 rounded-lg px-3 py-1 transition-all"
                                :class="
                                    activeTab === 'focus'
                                        ? 'bg-primary-strong text-on-accent shadow-sm'
                                        : 'text-content-secondary hover:text-content-primary'
                                "
                                @click="activeTab = 'focus'"
                            >
                                <AppIcon name="timer" :size="12" /> Foco ({{
                                    totalWeeklyFocusMinutes
                                }}
                                min)
                            </button>
                            <button
                                type="button"
                                class="flex items-center gap-1 rounded-lg px-3 py-1 transition-all"
                                :class="
                                    activeTab === 'habits'
                                        ? 'bg-primary-strong text-on-accent shadow-sm'
                                        : 'text-content-secondary hover:text-content-primary'
                                "
                                @click="activeTab = 'habits'"
                            >
                                <AppIcon name="zap" :size="12" /> Hábitos ({{ totalWeeklyHabits }})
                            </button>
                        </div>
                    </div>

                    <!-- Gráfica de Barras Interactiva en SVG/HTML -->
                    <div class="pt-4">
                        <div
                            class="flex h-48 items-end gap-3 px-2 border-b border-border-interactive pb-2"
                        >
                            <div
                                v-for="day in activity"
                                :key="day.date"
                                class="group relative flex flex-1 flex-col items-center h-full justify-end"
                            >
                                <!-- Tooltip flotante al hacer hover -->
                                <div
                                    class="absolute -top-8 z-10 hidden rounded-md bg-surface-raised px-2 py-1 text-[11px] font-bold text-content-primary shadow-md border border-border-interactive group-hover:block whitespace-nowrap"
                                >
                                    <template v-if="activeTab === 'focus'">
                                        {{ day.focusMinutes }} min
                                    </template>
                                    <template v-else>
                                        {{ day.habitsDone }} hábito{{
                                            day.habitsDone !== 1 ? 's' : ''
                                        }}
                                    </template>
                                </div>

                                <!-- Columna de barra visual -->
                                <div class="w-full flex-1 flex items-end justify-center">
                                    <div
                                        class="w-full max-w-[36px] rounded-t-lg transition-all duration-500 group-hover:brightness-110"
                                        :class="
                                            activeTab === 'focus'
                                                ? 'bg-primary-strong'
                                                : 'bg-success'
                                        "
                                        :style="{
                                            height:
                                                (activeTab === 'focus'
                                                    ? focusBarHeight(day.focusMinutes)
                                                    : habitsBarHeight(day.habitsDone)) + '%',
                                        }"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Labels de días de la semana -->
                        <div
                            class="flex justify-between px-2 pt-2 text-xs font-semibold text-content-secondary"
                        >
                            <div
                                v-for="day in activity"
                                :key="'lbl-' + day.date"
                                class="flex-1 text-center"
                            >
                                {{ day.label }}
                            </div>
                        </div>
                    </div>
                </BaseCard>

                <!-- Desafío Semanal: Villano Activo -->
                <BaseCard class="p-6 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h2
                                class="font-display text-lg font-bold text-content-primary flex items-center gap-2"
                            >
                                <AppIcon name="shield" :size="20" class="text-danger" /> Villano
                                Semanal
                            </h2>
                            <span
                                v-if="villain"
                                class="text-xs font-semibold text-content-secondary"
                            >
                                Sem. {{ villain.week_number }}
                            </span>
                        </div>

                        <div v-if="villain" class="space-y-3 text-center">
                            <img
                                :src="villain.image_url"
                                :alt="villain.name"
                                class="villain-idle mx-auto h-28 w-28 object-contain rounded-xl"
                            />
                            <div>
                                <h3 class="font-display font-bold text-content-primary text-base">
                                    {{ villain.name }}
                                </h3>
                                <p class="text-xs text-content-secondary line-clamp-2 mt-1">
                                    {{ villain.description }}
                                </p>
                            </div>

                            <div class="space-y-1 text-left">
                                <div
                                    class="flex justify-between text-xs font-semibold text-content-secondary"
                                >
                                    <span>HP</span>
                                    <span>{{ villain.remaining_hp }} / {{ villain.total_hp }}</span>
                                </div>
                                <ProgressBar
                                    :value="villain.remaining_hp"
                                    :max="villain.total_hp"
                                    :color="
                                        villain.status === 'defeated' ? 'bg-success' : 'bg-danger'
                                    "
                                    size="h-3"
                                />
                            </div>
                        </div>

                        <div v-else class="py-8 text-center text-sm text-content-secondary">
                            <AppIcon
                                name="shield"
                                :size="40"
                                class="mx-auto mb-2 text-content-muted"
                            />
                            No hay villano activo esta semana.
                        </div>
                    </div>

                    <Link :href="route('villains.index')" class="w-full">
                        <BaseButton
                            class="w-full flex items-center justify-center gap-1"
                            variant="ghost"
                            size="sm"
                        >
                            Ver Villano Completo <AppIcon name="arrow-right" :size="14" />
                        </BaseButton>
                    </Link>
                </BaseCard>
            </div>

            <!-- Accesos Rápidos a Módulos Clave -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <Link :href="route('ranking.index')">
                    <BaseCard
                        class="p-4 flex items-center gap-3 transition-transform hover:scale-[1.02]"
                    >
                        <AppIcon name="trophy" :size="24" class="text-warning shrink-0" />
                        <div>
                            <p class="font-semibold text-sm text-content-primary">Ranking</p>
                            <p class="text-xs text-content-muted">Tabla de posiciones</p>
                        </div>
                    </BaseCard>
                </Link>

                <Link :href="route('calendar.index')">
                    <BaseCard
                        class="p-4 flex items-center gap-3 transition-transform hover:scale-[1.02]"
                    >
                        <AppIcon name="calendar" :size="24" class="text-primary-strong shrink-0" />
                        <div>
                            <p class="font-semibold text-sm text-content-primary">Calendario</p>
                            <p class="text-xs text-content-muted">Feriados y exámenes</p>
                        </div>
                    </BaseCard>
                </Link>

                <Link :href="route('wellbeing.index')">
                    <BaseCard
                        class="p-4 flex items-center gap-3 transition-transform hover:scale-[1.02]"
                    >
                        <AppIcon name="heart" :size="24" class="text-danger shrink-0" />
                        <div>
                            <p class="font-semibold text-sm text-content-primary">Bienestar</p>
                            <p class="text-xs text-content-muted">Diario y ánimo</p>
                        </div>
                    </BaseCard>
                </Link>

                <Link :href="route('study-groups.index')">
                    <BaseCard
                        class="p-4 flex items-center gap-3 transition-transform hover:scale-[1.02]"
                    >
                        <AppIcon name="users" :size="24" class="text-secondary shrink-0" />
                        <div>
                            <p class="font-semibold text-sm text-content-primary">Grupos</p>
                            <p class="text-xs text-content-muted">Salas de estudio</p>
                        </div>
                    </BaseCard>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
