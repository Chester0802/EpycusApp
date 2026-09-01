<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import UsageTipBanner from '@/Components/ui/UsageTipBanner.vue';
import AppIcon from '@/Components/AppIcon.vue';
import StudentIdCard from '@/Components/ui/StudentIdCard.vue';
import CharacterSheetCard from '@/Components/ui/CharacterSheetCard.vue';
import ActivityHeatmap from '@/Components/ui/ActivityHeatmap.vue';
import CourseDistributionChart from '@/Components/ui/CourseDistributionChart.vue';
import PeakHoursChart from '@/Components/ui/PeakHoursChart.vue';
import WellbeingTrendChart from '@/Components/ui/WellbeingTrendChart.vue';
import VillainDamageHistoryChart from '@/Components/ui/VillainDamageHistoryChart.vue';
import { NotebookText } from '@lucide/vue';
import DonutChart from '@/Components/ui/DonutChart.vue';
import RadialProgressRing from '@/Components/ui/RadialProgressRing.vue';

const props = defineProps({
    userName: { type: String, default: 'Estudiante' },
    userCareer: { type: String, default: null },
    avatarStyle: { type: String, default: null },
    avatarGender: { type: String, default: null },
    avatarOptions: { type: Object, default: () => ({}) },
    characterStats: { type: Object, default: () => ({}) },
    analytics: { type: Object, default: () => ({}) },
    progress: { type: Object, required: true },
    activity: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    wellbeing: { type: Object, default: () => ({}) },
    villain: { type: Object, default: null },
    motivationalQuote: { type: Object, default: null },
});

const activeTab = ref('focus'); // 'focus' | 'habits'

const missionDonutSegments = computed(() => [
    { label: 'Completadas', value: props.stats?.completedMissions || 0, hexColor: '#10b981' },
    { label: 'Pendientes', value: props.stats?.pendingMissions || 0, hexColor: '#0284c7' },
    { label: 'Vencidas', value: props.stats?.overdueMissions || 0, hexColor: '#f43f5e' },
]);

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
            <!-- Ficha de Personaje RPG y Pentágono de Atributos -->
            <CharacterSheetCard
                v-if="characterStats && characterStats.attributes"
                :user-name="userName"
                :user-career="userCareer"
                :avatar-style="avatarStyle"
                :avatar-gender="avatarGender ?? 'm'"
                :avatar-options="avatarOptions"
                :progress="progress"
                :character-stats="characterStats"
            />
            <StudentIdCard
                v-else
                :user-name="userName"
                :user-career="userCareer"
                :avatar-style="avatarStyle"
                :avatar-gender="avatarGender ?? 'm'"
                :avatar-options="avatarOptions"
                :progress="progress"
            />

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

            <!-- Banner Calendario & Time-Blocking -->
            <div class="p-4 rounded-2xl bg-surface-raised border border-primary/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="text-2xl p-2 rounded-xl bg-primary/15 text-primary-strong">⏱️</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-display font-bold text-sm text-content-primary">Calendario & Time-Blocking</h3>
                        </div>
                        <p class="text-xs text-content-secondary mt-0.5">
                            Visualiza tus 24h, clases, misiones y checklist diario con 3 estados (Hecho, No Hecho, Postergar).
                        </p>
                    </div>
                </div>
                <Link
                    :href="route('calendar.index', { view: 'timeblocking' })"
                    class="px-4 py-2 rounded-xl bg-primary-strong hover:opacity-90 text-on-primary-strong font-bold text-xs shadow-md shadow-primary-strong/20 transition-all text-center self-start sm:self-center shrink-0"
                >
                    Ver Time-Blocking del Día 🚀
                </Link>
            </div>

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
                    <AppIcon name="zap" :size="28" class="mx-auto text-amber-500" />
                    <p class="font-display text-2xl font-bold text-content-primary">
                        {{ stats.todayHabitsDone }}
                    </p>
                    <p class="text-xs text-content-secondary">Hábitos de hoy</p>
                </BaseCard>

                <BaseCard class="p-4 text-center space-y-1">
                    <AppIcon name="clipboard" :size="28" class="mx-auto text-primary-strong" />
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

            <!-- Acceso Rápido a Bloc de Apuntes -->
            <BaseCard class="p-5 flex items-center justify-between bg-gradient-to-r from-surface-raised to-primary/5 border border-primary/20 relative overflow-hidden">
                <div class="relative z-10 flex items-center gap-4">
                    <div class="bg-primary/20 p-3 rounded-full text-primary-strong">
                        <NotebookText :size="24" />
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-content-primary">Bloc de Apuntes Centralizado</h2>
                        <p class="text-sm text-content-secondary">Accede rápidamente a tus apuntes de clase organizados por curso, con soporte para pantalla dividida.</p>
                    </div>
                </div>
                <Link :href="route('notes.index')" class="relative z-10">
                    <BaseButton variant="primary" class="font-semibold shadow-lg shadow-primary/25 hover:shadow-primary/40 transition-shadow">
                        Abrir Apuntes
                    </BaseButton>
                </Link>
                <!-- Decorative background elements -->
                <div class="absolute right-0 top-0 bottom-0 w-1/3 bg-gradient-to-l from-primary/10 to-transparent pointer-events-none"></div>
            </BaseCard>

            <!-- Gráfico 1: Mapa de Calor de Consistencia Diaria (60 Días) -->
            <ActivityHeatmap
                v-if="analytics && analytics.heatmap"
                :data="analytics.heatmap"
            />

            <!-- Sección de Gráficos Circulares de Datos y Bienestar -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Card 1: Gráfico de Donut de Misiones -->
                <BaseCard class="p-6 space-y-4">
                    <div>
                        <h2
                            class="font-display text-base font-bold text-content-primary flex items-center gap-2"
                        >
                            <AppIcon name="clipboard" :size="18" class="text-primary-strong" />
                            Estado de Misiones
                        </h2>
                        <p class="text-xs text-content-secondary">Distribución por estado actual</p>
                    </div>

                    <DonutChart
                        :segments="missionDonutSegments"
                        :center-title="stats.totalMissions || 0"
                        center-subtitle="Total"
                        :size="130"
                    />
                </BaseCard>

                <!-- Card 2: Anillo Radial de Cumplimiento de Hábitos -->
                <BaseCard class="p-6 space-y-4 flex flex-col justify-between">
                    <div>
                        <h2
                            class="font-display text-base font-bold text-content-primary flex items-center gap-2"
                        >
                            <AppIcon name="zap" :size="18" class="text-amber-500" />
                            Adherencia a Hábitos Hoy
                        </h2>
                        <p class="text-xs text-content-secondary">Porcentaje del objetivo diario</p>
                    </div>

                    <div class="my-auto py-2">
                        <RadialProgressRing
                            :percentage="stats.habitAdherencePercent || 0"
                            subtitle="Cumplido"
                        />
                    </div>

                    <div
                        class="text-center text-xs font-semibold text-content-secondary bg-surface-sunken rounded-lg py-1.5 border border-border-interactive"
                    >
                        {{ stats.todayHabitsDone }} de {{ stats.totalActiveHabits || 0 }} hábitos
                        completados hoy
                    </div>
                </BaseCard>

                <!-- Card 3: Bienestar Emocional Semanal con Enlace Directo -->
                <BaseCard
                    class="p-6 space-y-4 flex flex-col justify-between border-l-4 border-l-danger"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <h2
                                class="font-display text-base font-bold text-content-primary flex items-center gap-2"
                            >
                                <AppIcon name="heart" :size="18" class="text-danger" />
                                Bienestar Emocional
                            </h2>
                            <span class="text-[11px] font-bold text-content-muted">Últimos 7d</span>
                        </div>
                        <p class="text-xs text-content-secondary">
                            Promedio registrado en tu diario
                        </p>
                    </div>

                    <div v-if="wellbeing && wellbeing.totalEntries > 0" class="space-y-3">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-content-secondary flex items-center gap-1.5">
                                <AppIcon name="smile" :size="15" class="text-primary-strong" />
                                Estado de Ánimo
                            </span>
                            <span class="font-bold text-content-primary">{{
                                wellbeing.avgMood ? `${wellbeing.avgMood} / 5.0` : 'No disponible'
                            }}</span>
                        </div>
                        <ProgressBar
                            :value="wellbeing.avgMood || 0"
                            :max="5"
                            color="bg-primary-strong"
                            size="h-2"
                        />

                        <div class="flex items-center justify-between text-xs font-semibold pt-1">
                            <span class="text-content-secondary flex items-center gap-1.5">
                                <AppIcon name="zap" :size="15" class="text-warning" />
                                Nivel de Energía
                            </span>
                            <span class="font-bold text-content-primary">{{
                                wellbeing.avgEnergy
                                    ? `${wellbeing.avgEnergy} / 5.0`
                                    : 'No disponible'
                            }}</span>
                        </div>
                        <ProgressBar
                            :value="wellbeing.avgEnergy || 0"
                            :max="5"
                            color="bg-warning"
                            size="h-2"
                        />

                        <div class="flex items-center justify-between text-xs font-semibold pt-1">
                            <span class="text-content-secondary flex items-center gap-1.5">
                                <AppIcon name="shield" :size="15" class="text-success" />
                                Control de Estrés
                            </span>
                            <span class="font-bold text-content-primary">{{
                                wellbeing.avgStress
                                    ? `${wellbeing.avgStress} / 5.0`
                                    : 'No disponible'
                            }}</span>
                        </div>
                        <ProgressBar
                            :value="wellbeing.avgStress || 0"
                            :max="5"
                            color="bg-success"
                            size="h-2"
                        />
                    </div>

                    <div v-else class="py-4 text-center text-xs text-content-muted space-y-1">
                        <AppIcon name="heart" :size="24" class="mx-auto text-content-muted block" />
                        <p>No has registrado tu diario en los últimos 7 días.</p>
                    </div>

                    <BaseButton
                        :href="route('wellbeing.index')"
                        class="w-full flex items-center justify-center gap-1.5"
                        variant="ghost"
                        size="sm"
                    >
                        <AppIcon name="heart" :size="14" class="text-danger" />
                        Ir al módulo Bienestar
                        <AppIcon name="arrow-right" :size="14" />
                    </BaseButton>
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

                    <BaseButton
                        :href="route('villains.index')"
                        class="w-full flex items-center justify-center gap-1"
                        variant="ghost"
                        size="sm"
                    >
                        Ver Villano Completo <AppIcon name="arrow-right" :size="14" />
                    </BaseButton>
                </BaseCard>
            </div>

            <!-- Centro de Comando y Analíticas del Héroe (4 Gráficos Adicionales) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-lg font-bold text-content-primary flex items-center gap-2">
                            <span>📊</span> Analíticas y Rendimiento del Héroe
                        </h2>
                        <p class="text-xs text-content-secondary">
                            Métricas avanzadas de tiempo, concentración circadiana y combate.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Gráfico 2: Distribución por Asignatura -->
                    <CourseDistributionChart
                        v-if="analytics && analytics.courseDistribution"
                        :courses="analytics.courseDistribution"
                    />

                    <!-- Gráfico 3: Horas Pico de Rendimiento -->
                    <PeakHoursChart
                        v-if="analytics && analytics.peakHours"
                        :peak-data="analytics.peakHours"
                    />

                    <!-- Gráfico 4: Curva de Bienestar: Energía vs. Estrés -->
                    <WellbeingTrendChart
                        v-if="analytics && analytics.wellbeingTrend"
                        :trend-data="analytics.wellbeingTrend"
                    />

                    <!-- Gráfico 5: Historial de Asalto a Villanos -->
                    <VillainDamageHistoryChart
                        v-if="analytics && analytics.villainHistory"
                        :history="analytics.villainHistory"
                    />
                </div>
            </div>

            <!-- Accesos Rápidos a Módulos Clave -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <Link
                    :href="route('ranking.index')"
                    class="block text-left w-full cursor-pointer transition-transform hover:scale-[1.02] focus:outline-none"
                >
                    <BaseCard
                        class="p-4 flex items-center gap-3 h-full"
                    >
                        <AppIcon name="trophy" :size="24" class="text-warning shrink-0" />
                        <div>
                            <p class="font-semibold text-sm text-content-primary">Ranking</p>
                            <p class="text-xs text-content-muted">Tabla de posiciones</p>
                        </div>
                    </BaseCard>
                </Link>

                <Link
                    :href="route('calendar.index')"
                    class="block text-left w-full cursor-pointer transition-transform hover:scale-[1.02] focus:outline-none"
                >
                    <BaseCard
                        class="p-4 flex items-center gap-3 h-full"
                    >
                        <AppIcon name="calendar" :size="24" class="text-primary-strong shrink-0" />
                        <div>
                            <p class="font-semibold text-sm text-content-primary">Calendario</p>
                            <p class="text-xs text-content-muted">Feriados y exámenes</p>
                        </div>
                    </BaseCard>
                </Link>

                <Link
                    :href="route('wellbeing.index')"
                    class="block text-left w-full cursor-pointer transition-transform hover:scale-[1.02] focus:outline-none"
                >
                    <BaseCard
                        class="p-4 flex items-center gap-3 h-full"
                    >
                        <AppIcon name="heart" :size="24" class="text-danger shrink-0" />
                        <div>
                            <p class="font-semibold text-sm text-content-primary">Bienestar</p>
                            <p class="text-xs text-content-muted">Diario y ánimo</p>
                        </div>
                    </BaseCard>
                </Link>

                <Link
                    :href="route('study-groups.index')"
                    class="block text-left w-full cursor-pointer transition-transform hover:scale-[1.02] focus:outline-none"
                >
                    <BaseCard
                        class="p-4 flex items-center gap-3 h-full"
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
