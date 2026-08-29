<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { triggerConfetti, playSuccessChime } from '@/utils/celebration';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

const currentStep = ref(0); // 0 a 7
const isSubmitting = ref(false);
const errorMessage = ref('');
const showResults = ref(false);
const isCompletedLocal = ref(false);

const items = [
    {
        key: 'item_2',
        num: 2,
        title: 'Preparación para exámenes',
        text: 'Generalmente me preparo por adelantado para los exámenes.',
    },
    {
        key: 'item_5',
        num: 5,
        title: 'Búsqueda de orientación',
        text: 'Cuando tengo problemas para entender algo, inmediatamente trato de buscar ayuda.',
    },
    {
        key: 'item_7',
        num: 7,
        title: 'Cumplimiento oportuno',
        text: 'Trato de completar el trabajo asignado lo más pronto posible.',
    },
    {
        key: 'item_10',
        num: 10,
        title: 'Mejora continua',
        text: 'Constantemente intento mejorar mis hábitos de estudio.',
    },
    {
        key: 'item_11',
        num: 11,
        title: 'Perseverancia académica',
        text: 'Invierto el tiempo necesario en estudiar aun cuando el tema sea aburrido.',
    },
    {
        key: 'item_12',
        num: 12,
        title: 'Automotivación',
        text: 'Trato de motivarme para mantener mi ritmo de estudio.',
    },
    {
        key: 'item_13',
        num: 13,
        title: 'Gestión del tiempo',
        text: 'Trato de terminar mis trabajos importantes con tiempo de sobra.',
    },
    {
        key: 'item_14',
        num: 14,
        title: 'Revisión final',
        text: 'Me tomo el tiempo de revisar mis tareas antes de entregarlas.',
    },
];

const options = [
    { value: 1, label: 'Nunca' },
    { value: 2, label: 'A veces' },
    { value: 3, label: 'Casi siempre' },
    { value: 4, label: 'Siempre' },
];

const answers = ref({
    item_2: null,
    item_5: null,
    item_7: null,
    item_10: null,
    item_11: null,
    item_12: null,
    item_13: null,
    item_14: null,
});

const currentItem = computed(() => {
    const idx = Math.max(0, Math.min(currentStep.value, items.length - 1));
    return items[idx] || items[0];
});

const progressPercent = computed(() => {
    const step = Math.max(0, Math.min(currentStep.value, items.length - 1));
    return Math.round(((step + 1) / items.length) * 100);
});

function isOptionSelected(val) {
    if (!currentItem.value) return false;
    return answers.value[currentItem.value.key] === val;
}

const isCurrentAnswered = computed(() => {
    if (!currentItem.value) return false;
    return answers.value[currentItem.value.key] !== null;
});

const isAllAnswered = computed(() => {
    return items.every((item) => answers.value[item.key] !== null);
});

const calculatedScore = computed(() => {
    return Object.values(answers.value).reduce((acc, val) => acc + (Number(val) || 0), 0);
});

const diagnosticInfo = computed(() => {
    const score = calculatedScore.value;
    if (score >= 25) {
        return {
            level: 'Alta Autorregulación Académica',
            procrastination: 'Baja o Mínima Procrastinación (25-32 pts)',
            badgeClass: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
            barClass: 'from-emerald-500 to-teal-400',
            icon: '🌟',
            description:
                '¡Excelente nivel de autorregulación! Demuestras una notable constancia, preparación anticipada para tus evaluaciones y alta disciplina para sostener el ritmo de estudio.',
            tips: [
                'Usa los bloques Pomodoro para no sobrecargarte y balancear tu descanso.',
                'Organiza tus misiones por prioridad para avanzar en proyectos de alta complejidad.',
            ],
        };
    }
    if (score >= 17) {
        return {
            level: 'Autorregulación Académica Moderada',
            procrastination: 'Procrastinación Ocasional (17-24 pts)',
            badgeClass: 'bg-amber-500/15 text-amber-400 border-amber-500/30',
            barClass: 'from-amber-500 to-orange-400',
            icon: '⚡',
            description:
                'Cuentas con buen cumplimiento, pero en ocasiones postergas el inicio de tareas clave o repasos anticipados, acumulando picos de estrés de último minuto.',
            tips: [
                'Inicia tus sesiones de estudio con Pomodoro de 25 min y música Lo-Fi.',
                'Divide las tareas grandes en subtareas sencillas para evitar la resistencia inicial.',
            ],
        };
    }
    return {
        level: 'Baja Autorregulación Académica',
        procrastination: 'Alta Frecuencia de Procrastinación (8-16 pts)',
        badgeClass: 'bg-rose-500/15 text-rose-400 border-rose-500/30',
        barClass: 'from-rose-500 to-pink-500',
        icon: '🌱',
        description:
            'Presentas una marcada tendencia a postergar tus actividades académicas y dificultades para sostener rutinas continuas. ¡Estás en el lugar ideal para transformar este hábito!',
        tips: [
            'Aplica la regla de los 5 minutos: inicia una misión sin exigirte terminar todo de inmediato.',
            'Registra tus hábitos diarios y derrota al Villano de la Semana ganando experiencia.',
        ],
    };
});

let advanceTimeout = null;

function selectOption(val) {
    if (!currentItem.value) return;
    answers.value[currentItem.value.key] = val;
    if (advanceTimeout) clearTimeout(advanceTimeout);
    if (currentStep.value < items.length - 1) {
        advanceTimeout = setTimeout(() => {
            if (currentStep.value < items.length - 1) {
                currentStep.value++;
            }
        }, 180);
    }
}

function prevStep() {
    if (advanceTimeout) clearTimeout(advanceTimeout);
    if (currentStep.value > 0) {
        currentStep.value--;
    }
}

function nextStep() {
    if (advanceTimeout) clearTimeout(advanceTimeout);
    if (currentStep.value < items.length - 1 && isCurrentAnswered.value) {
        currentStep.value++;
    }
}

const isModalVisible = computed(() => {
    return props.show && !isCompletedLocal.value;
});

function markAsCompleted() {
    isCompletedLocal.value = true;
    const userId = page.props.auth?.user?.id;
    if (typeof window !== 'undefined' && window.localStorage) {
        try {
            if (userId) {
                localStorage.setItem(`epycus_epa_completed_${userId}`, '1');
            }
            localStorage.setItem('epycus_epa_completed_latest', '1');
        } catch {
            // Silencioso
        }
    }
}

function submitSurvey() {
    if (!isAllAnswered.value || isSubmitting.value) return;

    isSubmitting.value = true;
    errorMessage.value = '';

    router.post(route('epa.pretest.store'), answers.value, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            triggerConfetti();
            playSuccessChime();
            showResults.value = true;

            if (typeof window !== 'undefined') {
                window.dispatchEvent(
                    new CustomEvent('epycus-toast', {
                        detail: {
                            message: '¡Diagnóstico inicial completado! Has ganado +50 XP 🎉',
                            type: 'success',
                        },
                    }),
                );
            }
        },
        onError: (errors) => {
            isSubmitting.value = false;
            if (errors && typeof errors === 'object') {
                const firstVal = Object.values(errors)[0];
                const msg = typeof firstVal === 'string' ? firstVal : '';
                if (msg.toLowerCase().includes('completado') || msg.toLowerCase().includes('ya has')) {
                    markAsCompleted();
                    return;
                }
                errorMessage.value = msg || 'Error guardando el cuestionario. Por favor intenta de nuevo.';
            } else if (typeof errors === 'string' && (errors.includes('completado') || errors.includes('ya has'))) {
                markAsCompleted();
            } else {
                errorMessage.value =
                    errors?.message ||
                    'Error guardando el cuestionario. Por favor intenta de nuevo.';
            }
        },
    });
}

function finishAndClose() {
    markAsCompleted();
    router.reload({ only: ['auth', 'progress'] });
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isModalVisible"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/75 backdrop-blur-md overflow-y-auto"
        >
            <div
                class="relative w-full max-w-lg my-auto rounded-3xl bg-surface-raised border border-border/80 shadow-2xl p-5 sm:p-6 text-content-primary"
            >
                <!-- ── Vista 1: Cuestionario EPA Obligatorio ── -->
                <div v-if="!showResults" class="space-y-4">
                    <!-- Header -->
                    <div class="text-center space-y-1">
                        <div
                            class="inline-flex items-center gap-1.5 rounded-full bg-primary-strong/15 px-3 py-1 text-xs font-bold text-primary-strong"
                        >
                            📋 Evaluación Inicial · Escala EPA
                        </div>
                        <h2 class="font-display text-xl sm:text-2xl font-extrabold text-content-primary">
                            Diagnóstico de Hábitos de Estudio
                        </h2>
                        <p class="text-xs sm:text-sm text-content-secondary leading-relaxed">
                            Responde las 8 preguntas para calibrar tu perfil. Al finalizar ganarás <strong class="font-bold text-accent">+50 XP</strong>.
                        </p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs font-semibold text-content-secondary">
                            <span>Pregunta {{ currentStep + 1 }} de {{ items.length }}</span>
                            <span class="text-primary-strong font-bold">{{ progressPercent }}% completado</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-sunken">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary to-accent transition-all duration-300 ease-out"
                                :style="{ width: `${progressPercent}%` }"
                            />
                        </div>
                    </div>

                    <!-- Question Box -->
                    <div class="rounded-2xl border border-border/80 bg-surface p-4 sm:p-5 space-y-3 shadow-inner">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-primary-strong">
                            {{ currentItem.title }}
                        </div>
                        <p class="text-base sm:text-lg font-bold leading-snug text-content-primary">
                            "{{ currentItem.text }}"
                        </p>

                        <!-- Options List -->
                        <div class="space-y-2 pt-1">
                            <button
                                v-for="opt in options"
                                :key="opt.value"
                                type="button"
                                class="flex min-h-[44px] w-full items-center justify-between rounded-xl border px-4 py-2.5 text-left transition-all duration-150 cursor-pointer"
                                :class="[
                                    isOptionSelected(opt.value)
                                        ? 'bg-primary-strong text-on-primary-strong border-primary-strong shadow-md font-bold scale-[1.01]'
                                        : 'bg-surface-raised border-border text-content-primary hover:border-primary/50 hover:bg-surface-sunken',
                                ]"
                                @click="selectOption(opt.value)"
                            >
                                <span class="text-sm">
                                    <span class="mr-2 font-bold opacity-80">{{ opt.value }}.</span>
                                    {{ opt.label }}
                                </span>
                                <div
                                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                    :class="
                                        isOptionSelected(opt.value)
                                            ? 'border-white bg-white/30'
                                            : 'border-content-secondary/40'
                                    "
                                >
                                    <span
                                        v-if="isOptionSelected(opt.value)"
                                        class="h-1.5 w-1.5 rounded-full bg-white"
                                    />
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div
                        v-if="errorMessage"
                        class="rounded-xl bg-danger-text/10 p-3 text-center text-xs font-semibold text-danger-text border border-danger-text/20"
                    >
                        {{ errorMessage }}
                    </div>

                    <!-- Actions / Nav Buttons -->
                    <div class="flex items-center justify-between pt-1">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-content-secondary hover:text-content-primary transition-all disabled:opacity-40 disabled:pointer-events-none"
                            :disabled="currentStep === 0 || isSubmitting"
                            @click="prevStep"
                        >
                            ← Anterior
                        </button>

                        <div class="flex gap-2">
                            <button
                                v-if="currentStep < items.length - 1"
                                type="button"
                                class="px-5 py-2.5 rounded-xl bg-primary-strong text-on-primary-strong text-xs font-bold shadow-md hover:opacity-90 transition-all disabled:opacity-40 disabled:pointer-events-none"
                                :disabled="!isCurrentAnswered"
                                @click="nextStep"
                            >
                                Siguiente →
                            </button>

                            <button
                                v-else
                                type="button"
                                class="px-6 py-2.5 rounded-xl bg-primary-strong text-on-primary-strong text-sm font-bold shadow-lg hover:opacity-90 transition-all disabled:opacity-40 disabled:pointer-events-none flex items-center gap-1.5"
                                :disabled="!isAllAnswered || isSubmitting"
                                @click="submitSurvey"
                            >
                                <span>{{ isSubmitting ? 'Guardando…' : 'Finalizar y ver resultados 🎉' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Vista 2: Pantalla de Resultados y Diagnóstico ── -->
                <div v-else class="text-center space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-3xl bg-success/15 text-success flex items-center justify-center text-3xl shadow-inner animate-bounce">
                        🎉
                    </div>

                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-primary/15 border border-primary/30 px-3 py-0.5 text-xs font-bold text-primary">
                            🏁 Evaluación Completada
                        </div>
                        <h2 class="font-display text-2xl font-black text-content-primary">
                            ¡Tu Diagnóstico EPA está listo!
                        </h2>
                        <p class="text-xs sm:text-sm text-content-secondary">
                            Has completado la evaluación inicial y ganaste <strong class="text-accent font-bold">+50 XP</strong>.
                        </p>
                    </div>

                    <!-- Score Card -->
                    <div class="rounded-2xl border border-border/80 bg-surface p-4 sm:p-5 text-left space-y-3 shadow-inner">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-border/60 pb-3">
                            <div>
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-content-muted">Puntuación Total</span>
                                <div class="flex items-baseline gap-1.5 mt-0.5">
                                    <span class="font-display text-3xl font-extrabold text-content-primary">{{ calculatedScore }}</span>
                                    <span class="text-xs font-semibold text-content-secondary">/ 32 puntos</span>
                                </div>
                            </div>

                            <div class="flex flex-col sm:items-end">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-content-muted">Nivel Diagnosticado</span>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold mt-0.5"
                                    :class="diagnosticInfo.badgeClass"
                                >
                                    <span>{{ diagnosticInfo.icon }}</span>
                                    <span>{{ diagnosticInfo.level }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-[11px] text-content-secondary">
                                <span>8 pts (Baja regulación)</span>
                                <span class="font-bold text-primary">{{ Math.round((calculatedScore / 32) * 100) }}%</span>
                                <span>32 pts (Máxima regulación)</span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-surface-sunken overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r transition-all duration-700 ease-out"
                                    :class="diagnosticInfo.barClass"
                                    :style="{ width: `${(calculatedScore / 32) * 100}%` }"
                                />
                            </div>
                        </div>

                        <!-- Description & Tips -->
                        <div class="rounded-xl bg-surface-raised p-3.5 border border-border/60 text-xs sm:text-sm space-y-2">
                            <p class="font-semibold text-content-primary leading-relaxed">
                                {{ diagnosticInfo.description }}
                            </p>
                            <ul class="space-y-1 text-xs text-content-secondary">
                                <li v-for="(tip, idx) in diagnosticInfo.tips" :key="idx" class="flex items-start gap-2">
                                    <span class="text-primary font-bold">✓</span>
                                    <span>{{ tip }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Final Button -->
                    <div class="pt-2">
                        <button
                            type="button"
                            class="w-full sm:w-auto px-8 py-3 rounded-2xl bg-primary-strong text-on-primary-strong font-bold text-sm shadow-xl hover:opacity-90 transition-all cursor-pointer"
                            @click="finishAndClose"
                        >
                            🚀 Comenzar mi Aventura en Epycus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
