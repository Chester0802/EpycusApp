<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const currentStep = ref(0); // 0-indexed (0 to 7)
const isSubmitting = ref(false);
const errorMessage = ref('');

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
    { value: 1, label: 'Nunca / Casi nunca' },
    { value: 2, label: 'Raras veces' },
    { value: 3, label: 'A veces' },
    { value: 4, label: 'Casi siempre' },
    { value: 5, label: 'Siempre / Casi siempre' },
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

const isCompletedLocal = ref(false);

import { usePage } from '@inertiajs/vue3';

const page = usePage();

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

import { triggerConfetti, playSuccessChime } from '@/utils/celebration';

function submitSurvey() {
    if (!isAllAnswered.value || isSubmitting.value) return;

    isSubmitting.value = true;
    errorMessage.value = '';

    router.post(route('epa.pretest.store'), answers.value, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            markAsCompleted();
            triggerConfetti();
            playSuccessChime();
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
            router.reload({ only: ['auth', 'progress'] });
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
</script>

<template>
    <BaseModal :show="isModalVisible" :closeable="false">
        <div class="p-2 sm:p-4">
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
                        {{ isSubmitting ? 'Guardando…' : 'Finalizar y ganar +50 XP 🎉' }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </BaseModal>
</template>
