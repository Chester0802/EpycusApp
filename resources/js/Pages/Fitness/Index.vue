<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import ExerciseVisualPlayer from '@/Components/Fitness/ExerciseVisualPlayer.vue';
import { triggerConfetti, triggerHapticVibration } from '@/utils/celebration';
import {
    Dumbbell,
    Flame,
    Clock,
    Droplets,
    Plus,
    Minus,
    Play,
    CheckCircle,
    HeartPulse,
    Sparkles,
    Eye,
} from '@lucide/vue';

const props = defineProps({
    overview: {
        type: Object,
        required: true,
    },
});

const activeCategory = ref('todos');
const showWorkoutModal = ref(false);
const showRoutineGuideModal = ref(false);
const selectedRoutine = ref(null);
const routineActiveExerciseIndex = ref(0);

const showExerciseModal = ref(false);
const selectedExerciseForModal = ref(null);
const isProcessing = ref(false);

function openExerciseModal(exercise) {
    selectedExerciseForModal.value = exercise;
    showExerciseModal.value = true;
}

const workoutForm = ref({
    routine_name: 'Anti-Sedentarismo de Escritorio',
    duration_minutes: 15,
    calories_burned: 70,
    notes: '',
});

const filteredExercises = computed(() => {
    if (activeCategory.value === 'todos') {
        return props.overview.exercises;
    }
    return props.overview.exercises.filter(e => e.category === activeCategory.value);
});

const routineExercisesList = computed(() => {
    if (!selectedRoutine.value) return [];
    return (selectedRoutine.value.exercises || []).map(exName => {
        const match = props.overview.exercises.find(e => e.name === exName || exName.includes(e.name) || e.name.includes(exName));
        return match || {
            name: exName,
            slug: 'push-up',
            image_slug: 'push-up',
            category: 'escritorio',
            difficulty: 'facil',
            target_muscles: 'Cuerpo completo',
            instructions: 'Realiza el ejercicio con técnica controlada y respiración constante.',
            default_duration_seconds: 45,
            frames: [
                '/assets/exercises/push-up/frame-1.png',
                '/assets/exercises/push-up/frame-2.png',
                '/assets/exercises/push-up/frame-3.png',
            ],
        };
    });
});

const routineActiveExercise = computed(() => {
    return routineExercisesList.value[routineActiveExerciseIndex.value] || routineExercisesList.value[0];
});

function updateHydration(delta) {
    isProcessing.value = true;
    router.post(
        route('fitness.hydration.update'),
        { delta, date: props.overview.todayDate },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                if (delta > 0) {
                    triggerConfetti();
                    triggerHapticVibration([30, 30, 60]);
                }
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}

function openRoutineGuide(routine) {
    selectedRoutine.value = routine;
    routineActiveExerciseIndex.value = 0;
    showRoutineGuideModal.value = true;
}

function openWorkoutModalFromRoutine(routine) {
    workoutForm.value = {
        routine_name: routine.name,
        duration_minutes: routine.duration_minutes,
        calories_burned: routine.calories,
        notes: '',
    };
    showRoutineGuideModal.value = false;
    showWorkoutModal.value = true;
}

function openCustomWorkoutModal() {
    workoutForm.value = {
        routine_name: 'Sesión Libre de Ejercicio',
        duration_minutes: 20,
        calories_burned: 100,
        notes: '',
    };
    showWorkoutModal.value = true;
}

function submitWorkout() {
    isProcessing.value = true;
    router.post(
        route('fitness.workouts.store'),
        workoutForm.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showWorkoutModal.value = false;
                triggerConfetti();
                triggerHapticVibration([50, 40, 100]);
            },
            onError: () => {
                isProcessing.value = false;
            },
        }
    );
}
</script>

<template>
    <AppLayout title="Fitness & Hábitos Saludables">
        <Head title="Fitness & Hábitos Saludables — Epycus" />

        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8 p-4 sm:p-6 pb-24">
            <!-- Header con Navegación del Hub de Salud -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-border/80 pb-5">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-2xl bg-danger/15 text-danger-text">
                            <HeartPulse :size="24" />
                        </div>
                        <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-content-primary tracking-tight">
                            Fitness & Hábitos Saludables
                        </h1>
                    </div>
                    <p class="text-xs sm:text-sm text-content-secondary mt-1">
                        Pausas activas de escritorio, calistenia estudiantil y control de hidratación diaria
                    </p>
                </div>

                <!-- Botón Registrar Entrenamiento -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="px-4 py-2.5 rounded-2xl bg-primary-strong text-on-primary-strong font-bold text-xs sm:text-sm shadow-md hover:opacity-90 active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
                        @click="openCustomWorkoutModal"
                    >
                        <Plus :size="16" /> Registrar Ejercicio
                    </button>
                </div>
            </div>

            <!-- Subnavegador del Hub de Salud -->
            <div class="flex rounded-2xl bg-surface-sunken p-1.5 border border-border overflow-x-auto gap-1">
                <Link
                    :href="route('habits.index')"
                    class="flex-1 min-w-[120px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all text-content-secondary hover:text-content-primary hover:bg-surface flex items-center justify-center gap-1.5"
                >
                    <span>🌿</span> Hábitos Diarios
                </Link>
                <Link
                    :href="route('wellbeing.index')"
                    class="flex-1 min-w-[120px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all text-content-secondary hover:text-content-primary hover:bg-surface flex items-center justify-center gap-1.5"
                >
                    <span>🧘</span> Diario & Ánimo
                </Link>
                <Link
                    :href="route('fitness.index')"
                    class="flex-1 min-w-[120px] py-2 px-3 rounded-xl text-xs font-bold text-center transition-all bg-primary-strong text-on-primary-strong shadow-md flex items-center justify-center gap-1.5"
                >
                    <span>💪</span> Fitness & Hidratación
                </Link>
            </div>

            <!-- Panel Superior: Hidratación & Resumen Semanal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- 1. Marcador de Hidratación con 8 Vasitos Reales -->
                <BaseCard class="p-5 sm:p-6 flex flex-col justify-between space-y-4 lg:col-span-1 bg-gradient-to-br from-surface to-primary/5 border-primary/20 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2.5 rounded-2xl bg-primary/15 text-primary-strong">
                                <Droplets :size="20" />
                            </div>
                            <div>
                                <h2 class="font-display font-bold text-base text-content-primary">Hidratación Diaria</h2>
                                <p class="text-xs text-content-secondary">Meta: 8 vasos (2.0 Litros)</p>
                            </div>
                        </div>

                        <span
                            v-if="overview.hydration.is_completed"
                            class="px-2.5 py-1 rounded-full bg-success/20 text-success text-[11px] font-black flex items-center gap-1 shadow-sm animate-pulse"
                        >
                            <CheckCircle :size="12" /> ¡Meta Cumplida!
                        </span>
                    </div>

                    <!-- Progreso Visual de Vasos -->
                    <div class="space-y-3">
                        <div class="flex items-end justify-between">
                            <div>
                                <span class="font-display text-3xl sm:text-4xl font-extrabold text-primary-strong">
                                    {{ overview.hydration.glasses }}
                                </span>
                                <span class="text-sm font-semibold text-content-secondary"> / 8 vasos</span>
                            </div>
                            <span class="text-xs font-bold text-primary-strong">
                                {{ overview.hydration.total_ml }} ml ({{ overview.hydration.percentage }}%)
                            </span>
                        </div>

                        <!-- Barra de Progreso -->
                        <div class="h-2.5 w-full rounded-full bg-surface-sunken overflow-hidden p-0.5 border border-border">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary to-accent transition-all duration-500"
                                :style="{ width: `${overview.hydration.percentage}%` }"
                            />
                        </div>

                        <!-- 8 Vasitos de Agua Ilustrados e Interactivos (Responsivos: 4x2 en móvil, 8x1 en desktop) -->
                        <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 pt-1.5">
                            <button
                                v-for="i in 8"
                                :key="i"
                                type="button"
                                class="flex flex-col items-center justify-center p-1.5 sm:p-1 rounded-2xl transition-all duration-300 cursor-pointer active:scale-95 group relative"
                                :class="[
                                    i <= overview.hydration.glasses
                                        ? 'bg-primary/20 border-2 border-primary-strong shadow-md scale-[1.03]'
                                        : 'bg-surface-sunken/80 border border-border/80 hover:border-primary/40'
                                ]"
                                :title="i <= overview.hydration.glasses ? `Vaso ${i} tomado (clic para restar)` : `Tomar vaso ${i}`"
                                @click="i <= overview.hydration.glasses ? updateHydration(-1) : updateHydration(1)"
                            >
                                <!-- Vaso SVG de Cristal con Agua Azul -->
                                <svg viewBox="0 0 24 30" class="w-6 h-8 sm:w-5 sm:h-7 transition-transform duration-300 group-hover:scale-110">
                                    <!-- Contorno del Vaso de Cristal -->
                                    <path
                                        d="M3 3 L5.5 25 C5.7 26.5 7 27.5 8.5 27.5 L15.5 27.5 C17 27.5 18.3 26.5 18.5 25 L21 3 Z"
                                        fill="none"
                                        class="stroke-current"
                                        :class="i <= overview.hydration.glasses ? 'text-primary-strong' : 'text-content-muted/50'"
                                        stroke-width="1.8"
                                        stroke-linejoin="round"
                                    />
                                    <!-- Agua dentro del vaso (Lleno) -->
                                    <path
                                        v-if="i <= overview.hydration.glasses"
                                        d="M4.6 8 L6 24 C6.2 25.2 7.1 26 8.3 26 L15.7 26 C16.9 26 17.8 25.2 18 24 L19.4 8 Z"
                                        class="fill-primary-strong"
                                    />
                                    <!-- Reflejo de luz en el cristal -->
                                    <path
                                        d="M6.5 6 L7.5 22"
                                        stroke="white"
                                        stroke-width="1"
                                        stroke-linecap="round"
                                        opacity="0.6"
                                    />
                                    <!-- Burbujitas de agua si está lleno -->
                                    <circle v-if="i <= overview.hydration.glasses" cx="13" cy="16" r="0.9" fill="white" opacity="0.8" />
                                    <circle v-if="i <= overview.hydration.glasses" cx="11" cy="20" r="0.7" fill="white" opacity="0.6" />
                                </svg>
                                <span
                                    class="text-[9px] font-black mt-0.5"
                                    :class="i <= overview.hydration.glasses ? 'text-primary-strong' : 'text-content-muted'"
                                >
                                    {{ i }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Controles de Agua -->
                    <div class="flex items-center gap-2 pt-2">
                        <button
                            type="button"
                            class="flex-1 py-2.5 rounded-xl border border-border bg-surface text-content-secondary hover:text-content-primary hover:bg-surface-raised font-bold text-xs transition-all active:scale-95 disabled:opacity-40 disabled:pointer-events-none flex items-center justify-center gap-1 cursor-pointer"
                            :disabled="overview.hydration.glasses <= 0 || isProcessing"
                            @click="updateHydration(-1)"
                        >
                            <Minus :size="14" /> Restar Vaso
                        </button>
                        <button
                            type="button"
                            class="flex-1 py-2.5 rounded-xl bg-primary-strong text-on-primary-strong font-bold text-xs shadow-md hover:opacity-90 transition-all active:scale-95 disabled:opacity-40 disabled:pointer-events-none flex items-center justify-center gap-1 cursor-pointer"
                            :disabled="isProcessing"
                            @click="updateHydration(1)"
                        >
                            <Plus :size="14" /> Beber Vaso (+250ml)
                        </button>
                    </div>
                </BaseCard>

                <!-- 2. Resumen Semanal de Ejercicio -->
                <BaseCard class="p-5 sm:p-6 flex flex-col justify-between space-y-4 lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-border/70 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2.5 rounded-2xl bg-danger/15 text-danger-text">
                                <Flame :size="20" />
                            </div>
                            <div>
                                <h2 class="font-display font-bold text-base text-content-primary">Rendimiento Semanal</h2>
                                <p class="text-xs text-content-secondary">Actividad física acumulada esta semana</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 my-auto">
                        <div class="p-4 rounded-2xl bg-surface border border-border text-center space-y-1 shadow-sm">
                            <span class="text-xs text-content-muted block font-semibold">Sesiones</span>
                            <div class="font-display text-2xl font-black text-content-primary">
                                {{ overview.weekly_stats.sessions_count }}
                            </div>
                            <span class="text-[10px] text-content-muted">entrenamientos</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-surface border border-border text-center space-y-1 shadow-sm">
                            <span class="text-xs text-content-muted block font-semibold">Minutos</span>
                            <div class="font-display text-2xl font-black text-primary-strong">
                                {{ overview.weekly_stats.total_minutes }}'
                            </div>
                            <span class="text-[10px] text-content-muted">de ejercicio</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-surface border border-border text-center space-y-1 shadow-sm">
                            <span class="text-xs text-content-muted block font-semibold">Calorías</span>
                            <div class="font-display text-2xl font-black text-danger-text">
                                {{ overview.weekly_stats.total_calories }}
                            </div>
                            <span class="text-[10px] text-content-muted">kcal quemadas</span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-surface-raised border border-border text-xs text-content-secondary flex items-center gap-2.5 shadow-sm">
                        <span class="text-lg">💡</span>
                        <span>
                            Hacer al menos <strong>15 minutos diarios</strong> de movilidad reduce el dolor cervical y aumenta la oxigenación cerebral para rendir mejor en tus exámenes.
                        </span>
                    </div>
                </BaseCard>
            </div>

            <!-- Rutinas Express Recomendadas para Estudiantes -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-display text-lg font-bold text-content-primary flex items-center gap-2">
                            <span>⚡</span> Rutinas Express Prearmadas
                        </h2>
                        <p class="text-xs text-content-secondary">Diseñadas para hacer en tu habitación o frente al escritorio en 10-15 minutos</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="routine in overview.routines"
                        :key="routine.id"
                        class="p-5 rounded-3xl bg-surface border border-border hover:border-primary/50 transition-all shadow-sm flex flex-col justify-between gap-4 group cursor-pointer"
                        @click="openRoutineGuide(routine)"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl p-2 rounded-2xl bg-surface-raised border border-border shadow-sm">
                                    {{ routine.icon }}
                                </span>
                                <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full bg-primary/15 text-primary-strong">
                                    {{ routine.difficulty }}
                                </span>
                            </div>

                            <h3 class="font-display font-bold text-base text-content-primary group-hover:text-primary-strong transition-colors">
                                {{ routine.name }}
                            </h3>
                            <p class="text-xs text-content-secondary leading-relaxed">
                                {{ routine.description }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-border/70 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs text-content-muted">
                                <span class="flex items-center gap-1 font-semibold text-content-primary">
                                    <Clock :size="12" /> {{ routine.duration_minutes }} min
                                </span>
                                <span>•</span>
                                <span class="flex items-center gap-1 text-danger-text font-semibold">
                                    <Flame :size="12" /> ~{{ routine.calories }} kcal
                                </span>
                            </div>

                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-xl bg-primary-strong text-on-primary-strong font-bold text-xs shadow-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-1"
                                @click.stop="openRoutineGuide(routine)"
                            >
                                <Play :size="12" /> Ver Guía
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catálogo de Ejercicios Básicos Estudiantiles -->
            <BaseCard class="p-5 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border/70 pb-3">
                    <div>
                        <h2 class="font-display font-bold text-base text-content-primary flex items-center gap-2">
                            <span>📚</span> Catálogo de Ejercicios Básicos
                        </h2>
                        <p class="text-xs text-content-secondary mt-0.5">Ejercicios sencillos sin pesas con animaciones técnicas paso a paso</p>
                    </div>

                    <!-- Filtros por Categoría -->
                    <div class="flex rounded-xl bg-surface-sunken p-1 border border-border flex-wrap gap-1">
                        <button
                            v-for="cat in [
                                { id: 'todos', label: 'Todos' },
                                { id: 'escritorio', label: '🪑 Escritorio' },
                                { id: 'fuerza', label: '💪 Fuerza' },
                                { id: 'cardio', label: '🏃 Cardio' },
                                { id: 'flexibilidad', label: '🧘 Flexibilidad' },
                            ]"
                            :key="cat.id"
                            type="button"
                            :class="[
                                'px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer',
                                activeCategory === cat.id ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'
                            ]"
                            @click="activeCategory = cat.id"
                        >
                            {{ cat.label }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="exercise in filteredExercises"
                        :key="exercise.id"
                        class="p-4 rounded-3xl bg-surface border border-border/80 hover:border-primary/50 transition-all flex flex-col justify-between gap-3 group shadow-sm hover:shadow-md cursor-pointer"
                        @click="openExerciseModal(exercise)"
                    >
                        <!-- Ilustración del Ejercicio con Contraste Garantizado (Negro en claro, Blanco en oscuro) -->
                        <div class="w-full h-36 rounded-2xl bg-slate-100/90 dark:bg-slate-950/70 border border-slate-200 dark:border-white/10 p-2 flex items-center justify-center relative overflow-hidden group-hover:bg-slate-200/80 dark:group-hover:bg-slate-900 transition-colors shadow-inner">
                            <img
                                :src="exercise.frames ? exercise.frames[0] : `/assets/exercises/${exercise.image_slug || exercise.slug || 'push-up'}/frame-1.png`"
                                :alt="exercise.name"
                                class="max-h-full max-w-full object-contain exercise-card-fig select-none transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div class="absolute bottom-2 right-2 px-2 py-0.5 rounded-lg bg-surface-raised/95 border border-border text-[10px] font-bold text-primary-strong flex items-center gap-1 shadow-sm opacity-90 group-hover:opacity-100 transition-opacity">
                                <Eye :size="11" /> Ver Técnica
                            </div>
                        </div>

                        <div class="space-y-1.5 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <h3 class="font-display font-bold text-sm text-content-primary group-hover:text-primary-strong transition-colors leading-snug">
                                    {{ exercise.name }}
                                </h3>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-primary/10 text-primary-strong shrink-0">
                                    {{ exercise.difficulty }}
                                </span>
                            </div>
                            <span class="text-[10px] font-semibold text-content-muted block truncate">
                                🎯 {{ exercise.target_muscles }}
                            </span>
                            <p class="text-[11px] text-content-secondary line-clamp-2 leading-relaxed">
                                {{ exercise.instructions }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-border/50 flex items-center justify-between text-[11px] text-content-muted font-semibold">
                            <span class="flex items-center gap-1">
                                <Clock :size="12" /> {{ exercise.default_duration_seconds }}s / serie
                            </span>
                            <span class="text-primary-strong font-bold group-hover:underline text-[10px]">
                                Guía Animada →
                            </span>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <!-- Historial de Entrenamientos Recientes -->
            <BaseCard class="p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border/70 pb-3">
                    <div>
                        <h2 class="font-display font-bold text-base text-content-primary flex items-center gap-2">
                            <span>📜</span> Historial de Entrenamientos
                        </h2>
                        <p class="text-xs text-content-secondary mt-0.5">Registro de tus sesiones de ejercicio y XP obtenido</p>
                    </div>
                </div>

                <div v-if="overview.history.length === 0" class="p-6 text-center text-xs text-content-muted">
                    No has registrado entrenamientos recientemente. ¡Completa una rutina para ganar +25 XP!
                </div>

                <div v-else class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    <div
                        v-for="log in overview.history"
                        :key="log.id"
                        class="flex items-center justify-between p-3.5 rounded-2xl bg-surface border border-border/80 text-xs shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">💪</span>
                            <div>
                                <h3 class="font-bold text-sm text-content-primary">{{ log.routine_name }}</h3>
                                <span class="text-[11px] text-content-muted block">
                                    {{ log.performed_at }} • {{ log.duration_minutes }} minutos • ⚡ +25 XP
                                </span>
                            </div>
                        </div>

                        <span class="font-display font-bold text-xs px-2.5 py-1 rounded-xl bg-danger/15 text-danger-text flex items-center gap-1">
                            <Flame :size="12" /> {{ log.calories_burned }} kcal
                        </span>
                    </div>
                </div>
            </BaseCard>
        </div>

        <!-- Modal 1: Guía Visual Animada Individual del Ejercicio -->
        <BaseModal
            :show="showExerciseModal"
            :title="selectedExerciseForModal?.name || 'Técnica de Ejercicio'"
            maxWidth="md"
            @close="showExerciseModal = false"
        >
            <div v-if="selectedExerciseForModal" class="p-1">
                <ExerciseVisualPlayer
                    :exercise="selectedExerciseForModal"
                    :autoPlay="true"
                    :showTimer="true"
                />
                <div class="flex justify-end pt-3 mt-3 border-t border-border">
                    <BaseButton variant="primary" @click="showExerciseModal = false">
                        Listo, Entendido 👍
                    </BaseButton>
                </div>
            </div>
        </BaseModal>

        <!-- Modal 2: Guía Interactiva Integrada Paso a Paso de la Rutina (Sin modales superpuestos) -->
        <BaseModal
            :show="showRoutineGuideModal"
            :title="selectedRoutine?.name || 'Guía de Rutina Express'"
            maxWidth="xl"
            @close="showRoutineGuideModal = false"
        >
            <div v-if="selectedRoutine && routineActiveExercise" class="space-y-4">
                <!-- Info Rutina -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3.5 rounded-2xl bg-surface-sunken border border-border/80 text-xs shadow-inner">
                    <p class="text-content-secondary flex-1 leading-relaxed">
                        {{ selectedRoutine.description }}
                    </p>
                    <div class="flex items-center gap-2 font-bold shrink-0">
                        <span class="px-2.5 py-1 rounded-xl bg-surface-raised border border-border text-content-primary flex items-center gap-1">
                            <Clock :size="12" /> {{ selectedRoutine.duration_minutes }} min
                        </span>
                        <span class="px-2.5 py-1 rounded-xl bg-danger/15 text-danger-text flex items-center gap-1">
                            <Flame :size="12" /> ~{{ selectedRoutine.calories }} kcal
                        </span>
                    </div>
                </div>

                <!-- Barra de Navegación de Ejercicios en la Rutina (Stepper) -->
                <div class="space-y-1.5">
                    <span class="text-[11px] font-bold text-content-muted uppercase tracking-wider block">
                        Ejercicios incluidos (Paso {{ routineActiveExerciseIndex + 1 }} de {{ routineExercisesList.length }}):
                    </span>
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                        <button
                            v-for="(ex, idx) in routineExercisesList"
                            :key="idx"
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                            :class="[
                                routineActiveExerciseIndex === idx
                                    ? 'bg-primary-strong text-on-primary-strong shadow-md scale-[1.02]'
                                    : 'bg-surface-raised border border-border text-content-secondary hover:text-content-primary hover:bg-surface'
                            ]"
                            @click="routineActiveExerciseIndex = idx"
                        >
                            <span
                                class="w-4 h-4 rounded-full text-[10px] flex items-center justify-center font-black"
                                :class="routineActiveExerciseIndex === idx ? 'bg-white/30 text-white' : 'bg-surface-sunken text-content-muted'"
                            >
                                {{ idx + 1 }}
                            </span>
                            <span>{{ ex.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Reproductor Visual Interactivo del Ejercicio Activo -->
                <div class="p-1 rounded-2xl bg-surface/50 border border-border/40">
                    <ExerciseVisualPlayer
                        :key="routineActiveExercise.slug || routineActiveExercise.name"
                        :exercise="routineActiveExercise"
                        :autoPlay="true"
                        :showTimer="true"
                    />
                </div>

                <!-- Footer con Navegación de Pasos y Registro de Sesión -->
                <div class="flex items-center justify-between pt-3 border-t border-border gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-bold border border-border text-content-secondary hover:text-content-primary transition-all disabled:opacity-30 disabled:pointer-events-none cursor-pointer"
                        :disabled="routineActiveExerciseIndex === 0"
                        @click="routineActiveExerciseIndex--"
                    >
                        ← Anterior
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="routineActiveExerciseIndex < routineExercisesList.length - 1"
                            type="button"
                            class="px-4 py-2 rounded-xl bg-surface-raised border border-border/80 text-primary-strong text-xs font-bold hover:bg-surface transition-all cursor-pointer shadow-sm"
                            @click="routineActiveExerciseIndex++"
                        >
                            Siguiente Ejercicio →
                        </button>

                        <BaseButton
                            variant="primary"
                            @click="openWorkoutModalFromRoutine(selectedRoutine)"
                        >
                            🚀 Registrar Sesión (+25 XP)
                        </BaseButton>
                    </div>
                </div>
            </div>
        </BaseModal>

        <!-- Modal 3: Registrar Entrenamiento -->
        <BaseModal
            :show="showWorkoutModal"
            title="💪 Registrar Sesión de Ejercicio"
            maxWidth="md"
            @close="showWorkoutModal = false"
        >
            <form class="space-y-4" @submit.prevent="submitWorkout">
                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Nombre de la Rutina / Ejercicio</label>
                    <BaseInput v-model="workoutForm.routine_name" placeholder="Ej. Anti-Sedentarismo de Escritorio" required />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Duración (minutos)</label>
                        <BaseInput v-model="workoutForm.duration_minutes" type="number" min="1" max="300" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-content-secondary mb-1">Calorías Quemadas (~kcal)</label>
                        <BaseInput v-model="workoutForm.calories_burned" type="number" min="0" max="2000" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Notas / Sensaciones (Opcional)</label>
                    <BaseInput v-model="workoutForm.notes" placeholder="Ej. Me sentí con más energía para estudiar matemáticas..." />
                </div>

                <div class="p-3 rounded-xl bg-success/10 border border-success/20 text-xs text-success font-semibold flex items-center gap-2">
                    <Sparkles :size="16" />
                    <span>¡Ganarás +25 XP para tu nivel de héroe al registrar tu entrenamiento!</span>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showWorkoutModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Guardando...' : 'Completar y Ganar XP' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>

<style scoped>
/* En Modo Claro: la figura blanca original se vuelve silueta negra pura */
.exercise-card-fig {
    filter: brightness(0) opacity(0.88);
}

/* En Modo Oscuro: la figura blanca original se mantiene blanca pura */
:global(.dark) .exercise-card-fig,
:global([data-theme='dark']) .exercise-card-fig {
    filter: brightness(1) opacity(1) drop-shadow(0 2px 8px rgba(255, 255, 255, 0.2));
}
</style>
