<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
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
const isProcessing = ref(false);

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
    <Head title="Fitness & Salud Estudiantil — Epycus" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Pestañas de Navegación del Hub de Salud & Bienestar -->
            <div class="flex items-center gap-2 border-b border-border/70 pb-2 overflow-x-auto">
                <Link
                    :href="route('habits.index')"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all text-content-secondary hover:bg-surface-raised hover:text-content-primary flex items-center gap-2 shrink-0"
                >
                    <span>🌿</span> Hábitos Diarios
                </Link>
                <Link
                    :href="route('wellbeing.index')"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all text-content-secondary hover:bg-surface-raised hover:text-content-primary flex items-center gap-2 shrink-0"
                >
                    <span>🧘</span> Diario & Ánimo
                </Link>
                <Link
                    :href="route('fitness.index')"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all bg-primary-strong text-on-primary-strong shadow-sm flex items-center gap-2 shrink-0"
                >
                    <span>💪</span> Fitness & Hidratación
                </Link>
            </div>

            <!-- Header Principal -->
            <div class="p-6 rounded-3xl bg-surface-raised border border-border shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-2xl bg-danger/15 text-danger-text text-2xl font-bold">💪</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="font-display text-2xl sm:text-3xl font-bold text-content-primary">Fitness & Salud Estudiantil</h1>
                            </div>
                            <p class="text-xs sm:text-sm text-content-secondary mt-0.5">
                                Rutinas anti-sedentarismo de escritorio, calistenia en casa, marcador de hidratación y recarga de energía.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <BaseButton variant="primary" size="sm" @click="openCustomWorkoutModal">
                        <Plus :size="14" /> Registrar Entrenamiento
                    </BaseButton>
                </div>
            </div>

            <!-- Panel de Métricas de la Semana & Marcador de Hidratación (2 Columnas) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                <!-- Columna Izquierda: Marcador Interactivo de Hidratación (8 Vasos = 2 Litros) (6 cols) -->
                <BaseCard class="lg:col-span-6 p-6 flex flex-col justify-between space-y-5">
                    <div class="flex items-center justify-between border-b border-border/70 pb-3">
                        <div class="flex items-center gap-2">
                            <Droplets :size="20" class="text-primary-strong" />
                            <div>
                                <h2 class="font-display font-bold text-base text-content-primary">Hidratación Diaria</h2>
                                <p class="text-xs text-content-secondary">Meta: 8 vasos (2.0 Litros) para optimizar memoria y enfoque</p>
                            </div>
                        </div>
                        <span class="font-display text-sm font-bold text-primary-strong">
                            {{ overview.hydration.total_ml }} / 2000 ml
                        </span>
                    </div>

                    <!-- Visualización de los 8 Vasos -->
                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 py-2">
                        <div
                            v-for="v in 8"
                            :key="v"
                            :class="[
                                'p-3 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1',
                                v <= overview.hydration.glasses
                                    ? 'bg-primary/20 border-primary-strong text-primary-strong shadow-sm scale-105'
                                    : 'bg-surface border-border text-content-muted opacity-60'
                            ]"
                        >
                            <span class="text-2xl">{{ v <= overview.hydration.glasses ? '💧' : '🥛' }}</span>
                            <span class="text-[10px] font-bold">Vaso {{ v }}</span>
                        </div>
                    </div>

                    <!-- Barra y Controles -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-xs text-content-secondary">
                            <span>Progreso: {{ overview.hydration.glasses }} de 8 vasos</span>
                            <span class="font-bold text-primary-strong">{{ overview.hydration.percentage }}%</span>
                        </div>
                        <div class="w-full bg-surface-sunken rounded-full h-2.5 overflow-hidden border border-border">
                            <div class="bg-primary-strong h-2.5 rounded-full transition-all duration-500" :style="{ width: `${overview.hydration.percentage}%` }"></div>
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-2">
                            <button
                                type="button"
                                :disabled="overview.hydration.glasses === 0 || isProcessing"
                                class="px-3 py-1.5 rounded-xl border border-border bg-surface text-content-secondary hover:bg-surface-raised text-xs font-bold transition-all disabled:opacity-30 flex items-center gap-1"
                                @click="updateHydration(-1)"
                            >
                                <Minus :size="13" /> Quitar
                            </button>

                            <button
                                type="button"
                                :disabled="isProcessing"
                                class="px-5 py-2 rounded-xl bg-primary-strong text-on-primary-strong font-bold text-xs shadow-md shadow-primary-strong/20 hover:opacity-90 active:scale-95 transition-all flex items-center gap-1.5"
                                @click="updateHydration(1)"
                            >
                                <Plus :size="14" /> +1 Vaso de Agua (250 ml)
                            </button>
                        </div>
                    </div>
                </BaseCard>

                <!-- Columna Derecha: Métricas Semanales de Actividad (6 cols) -->
                <BaseCard class="lg:col-span-6 p-6 flex flex-col justify-between space-y-4">
                    <div class="flex items-center justify-between border-b border-border/70 pb-3">
                        <div class="flex items-center gap-2">
                            <HeartPulse :size="20" class="text-danger-text" />
                            <div>
                                <h2 class="font-display font-bold text-base text-content-primary">Rendimiento Semanal</h2>
                                <p class="text-xs text-content-secondary">Actividad física acumulada esta semana</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 my-auto">
                        <div class="p-4 rounded-2xl bg-surface border border-border text-center space-y-1">
                            <span class="text-xs text-content-muted block">Sesiones</span>
                            <div class="font-display text-2xl font-black text-content-primary">
                                {{ overview.weekly_stats.sessions_count }}
                            </div>
                            <span class="text-[10px] text-content-muted">entrenamientos</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-surface border border-border text-center space-y-1">
                            <span class="text-xs text-content-muted block">Minutos</span>
                            <div class="font-display text-2xl font-black text-primary-strong">
                                {{ overview.weekly_stats.total_minutes }}'
                            </div>
                            <span class="text-[10px] text-content-muted">de ejercicio</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-surface border border-border text-center space-y-1">
                            <span class="text-xs text-content-muted block">Calorías</span>
                            <div class="font-display text-2xl font-black text-danger-text">
                                {{ overview.weekly_stats.total_calories }}
                            </div>
                            <span class="text-[10px] text-content-muted">kcal quemadas</span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-surface-raised border border-border text-xs text-content-secondary flex items-center gap-2.5">
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
                        class="p-5 rounded-3xl bg-surface border border-border hover:border-primary/50 transition-all shadow-sm flex flex-col justify-between gap-4 group"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl p-2 rounded-xl bg-surface-raised border border-border">
                                    {{ routine.icon }}
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary/15 text-primary-strong">
                                    {{ routine.difficulty }}
                                </span>
                            </div>

                            <h3 class="font-display font-bold text-base text-content-primary">
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
                                @click="openRoutineGuide(routine)"
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
                        <p class="text-xs text-content-secondary mt-0.5">Ejercicios sencillos sin pesas con instrucciones de ejecución</p>
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
                                'px-3 py-1 rounded-lg text-xs font-bold transition-all',
                                activeCategory === cat.id ? 'bg-primary-strong text-on-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'
                            ]"
                            @click="activeCategory = cat.id"
                        >
                            {{ cat.label }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="exercise in filteredExercises"
                        :key="exercise.id"
                        class="p-4 rounded-2xl bg-surface border border-border/80 hover:border-primary/40 transition-all flex flex-col justify-between gap-3"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ exercise.icon }}</span>
                                <h3 class="font-bold text-xs text-content-primary leading-tight">
                                    {{ exercise.name }}
                                </h3>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-surface-raised text-content-muted font-bold block w-fit">
                                {{ exercise.target_muscles }}
                            </span>
                            <p class="text-[11px] text-content-secondary leading-normal">
                                {{ exercise.instructions }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-border/50 flex items-center justify-between text-[10px] text-content-muted font-semibold">
                            <span>⏱️ {{ exercise.default_duration_seconds }}s por serie</span>
                            <span class="capitalize">{{ exercise.difficulty }}</span>
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
                        class="flex items-center justify-between p-3.5 rounded-2xl bg-surface border border-border/80 text-xs"
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

        <!-- Modal: Guía de Rutina -->
        <BaseModal
            :show="showRoutineGuideModal"
            :title="selectedRoutine?.name || 'Guía de Rutina'"
            @close="showRoutineGuideModal = false"
        >
            <div v-if="selectedRoutine" class="space-y-4">
                <p class="text-xs text-content-secondary">
                    {{ selectedRoutine.description }}
                </p>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-content-secondary">Ejercicios incluidos:</label>
                    <div class="space-y-2">
                        <div
                            v-for="(exName, idx) in selectedRoutine.exercises"
                            :key="idx"
                            class="p-3 rounded-xl bg-surface-raised border border-border flex items-center gap-2 text-xs font-bold text-content-primary"
                        >
                            <span class="w-5 h-5 rounded-full bg-primary-strong text-on-primary-strong flex items-center justify-center text-[10px]">
                                {{ idx + 1 }}
                            </span>
                            <span>{{ exName }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" @click="showRoutineGuideModal = false">Cerrar</BaseButton>
                    <BaseButton variant="primary" @click="openWorkoutModalFromRoutine(selectedRoutine)">
                        Iniciar y Registrar Sesión
                    </BaseButton>
                </div>
            </div>
        </BaseModal>

        <!-- Modal: Registrar Entrenamiento -->
        <BaseModal
            :show="showWorkoutModal"
            title="💪 Registrar Sesión de Ejercicio"
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
                        <label class="block text-xs font-bold text-content-secondary mb-1">Calorías Estimadas</label>
                        <BaseInput v-model="workoutForm.calories_burned" type="number" min="0" max="2000" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-secondary mb-1">Notas / Sensaciones (opcional)</label>
                    <BaseInput v-model="workoutForm.notes" placeholder="Ej. Alivió la tensión lumbar" />
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-border">
                    <BaseButton variant="secondary" type="button" @click="showWorkoutModal = false">Cancelar</BaseButton>
                    <BaseButton variant="primary" type="submit" :disabled="isProcessing">
                        Guardar Entrenamiento (+25 XP)
                    </BaseButton>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>
