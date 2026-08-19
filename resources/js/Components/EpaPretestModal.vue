<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import { triggerConfetti, playSuccessChime } from '@/utils/celebration';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

const currentStep = ref(0); // 0-indexed (0 to 7)
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

const isCurrentAnswered = computed(() => {
    return currentItem.value ? answers.value[currentItem.value.key] !== null : false;
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
    // Auto-advance if not on last step
    if (currentStep.value < items.length - 1) {
        advanceTimeout = setTimeout(() => {
            if (currentStep.value < items.length - 1) {
                currentStep.value++;
            }
        }, 220);
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
        } catch (e) {
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
    <BaseModal :show="isModalVisible" :closeable="false">
        <div class="p-2 sm:p-4">
            <!-- ── Vista 1: Cuestionario EPA ── -->
            <div v-if="!showResults">
                <!-- Header con colores y gradientes enriquecidos -->
                <div class="mb-6 text-center">
                    <div
                        class="mb-3 inline-flex items-center gap-2 rounded-full bg-primary-strong/15 px-3.5 py-1 text-xs font-bold text-primary-strong shadow-sm"
                    >
                        📋 Evaluación Inicial · Escala EPA
                    </div>
                    <h2 class="font-display text-2xl font-extrabold text-content-primary sm:text-3xl">
                        Diagnóstico de procrastinación actual
                    </h2>
                    <p class="mt-2 text-sm text-content-secondary">
                        Responde con honestidad sobre tus hábitos de estudio actuales. Al completar
                        recibirás <strong class="font-bold text-accent">+50 XP</strong>.
                    </p>
                </div>

                <!-- Progress Bar animada -->
                <div class="mb-6">
                    <div
                        class="mb-1.5 flex items-center justify-between text-xs font-semibold text-content-secondary"
                    >
                        <span>Pregunta {{ currentStep + 1 }} de {{ items.length }}</span>
                        <span class="text-primary-strong">{{ progressPercent }}% completado</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-surface-sunken">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-primary to-accent transition-all duration-300 ease-out shadow-sm"
                            :style="{ width: `${progressPercent}%` }"
                        />
                    </div>
                </div>

                <!-- Question Card -->
                <div
                    class="panel-sunken mb-6 rounded-2xl border border-border/80 p-5 transition-all duration-200"
                >
                    <div class="mb-2 text-xs font-bold uppercase tracking-wider text-primary-strong">
                        {{ currentItem.title }}
                    </div>
                    <p class="text-lg font-bold leading-snug text-content-primary">
                        "{{ currentItem.text }}"
                    </p>

                    <!-- Options -->
                    <div class="mt-5 space-y-2.5">
                        <button
                            v-for="opt in options"
                            :key="opt.value"
                            type="button"
                            class="flex min-h-[48px] w-full items-center justify-between rounded-xl border px-4 py-3 text-left transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-strong"
                            :class="[
                                answers[currentItem.key] === opt.value
                                    ? 'bg-primary text-on-primary border-primary-strong shadow-md font-bold scale-[1.01]'
                                    : 'bg-surface-raised border-border-interactive text-content-primary hover:border-primary-strong hover:bg-surface-raised/80',
                            ]"
                            @click="selectOption(opt.value)"
                        >
                            <span class="text-sm">
                                <span class="mr-2.5 font-bold opacity-80">{{ opt.value }}.</span>
                                {{ opt.label }}
                            </span>
                            <div
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                :class="
                                    answers[currentItem.key] === opt.value
                                        ? 'border-white bg-white/30'
                                        : 'border-content-secondary/40'
                                "
                            >
                                <span
                                    v-if="answers[currentItem.key] === opt.value"
                                    class="h-2 w-2 rounded-full bg-white"
                                />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Error Message -->
                <div
                    v-if="errorMessage"
                    class="mb-4 rounded-xl bg-danger/10 p-3 text-center text-xs font-semibold text-danger-text border border-danger/20"
                >
                    {{ errorMessage }}
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-2">
                    <BaseButton
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="currentStep === 0 || isSubmitting"
                        @click="prevStep"
                    >
                        ← Anterior
                    </BaseButton>

                    <div class="flex gap-2">
                        <BaseButton
                            v-if="currentStep < items.length - 1"
                            type="button"
                            variant="primary"
                            size="sm"
                            :disabled="!isCurrentAnswered"
                            @click="nextStep"
                        >
                            Siguiente →
                        </BaseButton>

                        <BaseButton
                            v-else
                            type="button"
                            variant="accent"
                            size="md"
                            :disabled="!isAllAnswered || isSubmitting"
                            @click="submitSurvey"
                        >
                            {{ isSubmitting ? 'Guardando…' : 'Finalizar y ver resultados 🎉' }}
                        </BaseButton>
                    </div>
                </div>
            </div>

            <!-- ── Vista 2: Pantalla de Resultados y Diagnóstico ── -->
            <div v-else class="text-center py-2 animate-fadeIn">
                <!-- Badge Día 1 -->
                <div class="inline-flex items-center gap-2 rounded-full bg-primary/15 border border-primary/30 px-3.5 py-1 text-xs font-bold text-primary mb-3">
                    🏁 Día 1 · Punto de Partida
                </div>

                <h2 class="font-display text-2xl font-black text-content-primary sm:text-3xl">
                    ¡Tu Diagnóstico Inicial EPA está listo!
                </h2>
                <p class="mt-1 text-sm text-content-secondary">
                    Has completado la evaluación científica de entrada y ganaste <strong class="text-accent font-bold">+50 XP</strong>.
                </p>

                <!-- Score Card -->
                <div class="mt-5 rounded-2xl border border-border/80 bg-surface-raised p-5 text-left shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-border/60 pb-4">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-content-muted">Puntuación Total</span>
                            <div class="flex items-baseline gap-1.5 mt-0.5">
                                <span class="font-display text-3xl font-extrabold text-content-primary">{{ calculatedScore }}</span>
                                <span class="text-sm font-semibold text-content-secondary">/ 32 puntos</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:items-end">
                            <span class="text-xs font-semibold uppercase tracking-wider text-content-muted">Nivel Diagnosticado</span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold mt-0.5"
                                :class="diagnosticInfo.badgeClass"
                            >
                                <span>{{ diagnosticInfo.icon }}</span>
                                <span>{{ diagnosticInfo.level }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Progress Gauge Bar -->
                    <div class="my-4">
                        <div class="flex justify-between text-xs text-content-secondary mb-1">
                            <span>8 pts (Alta Procrastinación)</span>
                            <span class="font-bold text-primary">{{ Math.round((calculatedScore / 32) * 100) }}%</span>
                            <span>32 pts (Máxima Autorregulación)</span>
                        </div>
                        <div class="h-3 w-full rounded-full bg-surface-sunken overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r transition-all duration-700 ease-out"
                                :class="diagnosticInfo.barClass"
                                :style="{ width: `${(calculatedScore / 32) * 100}%` }"
                            />
                        </div>
                    </div>

                    <!-- Explicación del diagnóstico -->
                    <div class="rounded-xl bg-surface-sunken p-4 border border-border/60 text-sm">
                        <p class="font-semibold text-content-primary leading-relaxed">
                            {{ diagnosticInfo.description }}
                        </p>
                        <ul class="mt-3 space-y-1.5 text-xs text-content-secondary">
                            <li v-for="(tip, idx) in diagnosticInfo.tips" :key="idx" class="flex items-start gap-2">
                                <span class="text-primary font-bold">✓</span>
                                <span>{{ tip }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-3.5 flex items-center justify-between text-[11px] text-content-muted">
                        <span>🗓️ Registrado hoy · Día 1</span>
                        <span class="font-semibold text-accent">📍 Visible siempre en Bienestar</span>
                    </div>
                </div>

                <!-- Botón de acción para cerrar y comenzar -->
                <div class="mt-6 flex justify-center">
                    <BaseButton
                        type="button"
                        variant="primary"
                        size="lg"
                        class="w-full sm:w-auto px-8 shadow-lg font-bold"
                        @click="finishAndClose"
                    >
                        Comenzar mi Aventura (Día 1) 🚀
                    </BaseButton>
                </div>
            </div>
        </div>
    </BaseModal>
</template>
