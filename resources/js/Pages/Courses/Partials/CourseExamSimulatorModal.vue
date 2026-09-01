<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import { triggerConfetti } from '@/utils/celebration';
import {
    Sparkles,
    Loader2,
    Clock,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Brain,
    Trophy,
    BookOpen,
    Send,
    RotateCcw,
} from '@lucide/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    course: { type: Object, required: true },
});

const emit = defineEmits(['close', 'flashcardsCreated']);

const loading = ref(false);
const evaluating = ref(false);
const exam = ref(null);
const userAnswers = ref({});
const evaluationResult = ref(null);
const error = ref(null);

const timeRemaining = ref(1200); // 20 min en segundos
let timerInterval = null;

const allQuestions = computed(() => {
    if (!exam.value) return [];
    const mc = (exam.value.multiple_choice || []).map(q => ({ ...q, type: 'mc' }));
    const open = (exam.value.open_questions || []).map(q => ({ ...q, type: 'open' }));
    return [...mc, ...open];
});

const answeredCount = computed(() => {
    return Object.keys(userAnswers.value).filter(k => {
        const val = userAnswers.value[k];
        return val !== undefined && val !== null && String(val).trim() !== '';
    }).length;
});

const formattedTime = computed(() => {
    const min = Math.floor(timeRemaining.value / 60);
    const sec = timeRemaining.value % 60;
    return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
});

async function startExam() {
    loading.value = true;
    error.value = null;
    exam.value = null;
    evaluationResult.value = null;
    userAnswers.value = {};
    timeRemaining.value = 1200;

    try {
        const res = await axios.post(route('courses.mock-exam.generate', { course: props.course.id }));
        if (res.data.exam) {
            exam.value = res.data.exam;
            timeRemaining.value = (res.data.exam.time_limit_minutes || 20) * 60;
            startTimer();
        } else {
            error.value = 'No se pudo estructurar el examen.';
        }
    } catch (e) {
        error.value = e.response?.data?.error || 'Los servidores están en mantenimiento. Disculpe.';
    } finally {
        loading.value = false;
    }
}

function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--;
        } else {
            clearInterval(timerInterval);
            submitExam();
        }
    }, 1000);
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

async function submitExam() {
    if (evaluating.value) return;
    stopTimer();
    evaluating.value = true;
    error.value = null;

    try {
        const res = await axios.post(route('courses.mock-exam.evaluate', { course: props.course.id }), {
            exam: exam.value,
            user_answers: userAnswers.value,
        });

        evaluationResult.value = res.data.evaluation;
        if (res.data.evaluation?.final_grade >= 14) {
            triggerConfetti();
        }
        if (res.data.autocreated_flashcards_count > 0) {
            emit('flashcardsCreated');
        }
    } catch (e) {
        error.value = e.response?.data?.error || 'Los servidores están en mantenimiento. Disculpe.';
    } finally {
        evaluating.value = false;
    }
}

function handleClose() {
    stopTimer();
    emit('close');
}

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <BaseModal :show="show" title="Simulacro de Examen con IA" size="xl" @close="handleClose">
        <!-- Estado Inicial / Carga -->
        <div v-if="loading" class="py-16 text-center space-y-4">
            <div class="inline-flex p-4 rounded-3xl bg-primary/10 text-primary-strong animate-bounce">
                <Brain :size="40" />
            </div>
            <h3 class="text-lg font-bold text-content-primary">Generando examen personalizado con IA...</h3>
            <p class="text-xs text-content-secondary max-w-md mx-auto">
                Analizando apuntes y contenidos de <strong>{{ course.name }}</strong> para estructurar 6 preguntas de opción múltiple y 4 casos de desarrollo.
            </p>
            <Loader2 :size="24" class="animate-spin text-primary-strong mx-auto mt-2" />
        </div>

        <!-- Error -->
        <div v-else-if="error" class="py-8 text-center space-y-4">
            <div class="inline-flex p-3 rounded-full bg-danger/10 text-danger">
                <AlertCircle :size="32" />
            </div>
            <p class="text-sm font-semibold text-danger">{{ error }}</p>
            <BaseButton variant="primary" size="sm" @click="startExam">
                Reintentar generación
            </BaseButton>
        </div>

        <!-- Pantalla de Bienvenida al Simulacro -->
        <div v-else-if="!exam && !evaluationResult" class="py-8 text-center space-y-6">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-primary to-accent text-white flex items-center justify-center mx-auto shadow-lg shadow-primary/25">
                <Sparkles :size="32" />
            </div>
            <div class="space-y-2">
                <h3 class="text-xl font-bold text-content-primary">Simulacro Parcial: {{ course.name }}</h3>
                <p class="text-sm text-content-secondary max-w-lg mx-auto">
                    Pondrás a prueba tus conocimientos con 10 preguntas universitarias en 20 minutos. Al terminar, la IA corregirá tus respuestas y <strong>convertirá tus errores automáticamente en Flashcards</strong> para tu repaso diario.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 max-w-md mx-auto text-left">
                <div class="p-3 rounded-xl bg-surface-raised border border-border">
                    <p class="text-[11px] text-content-muted">Preguntas</p>
                    <p class="font-bold text-sm text-content-primary">10 Reactivos</p>
                </div>
                <div class="p-3 rounded-xl bg-surface-raised border border-border">
                    <p class="text-[11px] text-content-muted">Tiempo</p>
                    <p class="font-bold text-sm text-content-primary">20 Minutos</p>
                </div>
                <div class="p-3 rounded-xl bg-surface-raised border border-border">
                    <p class="text-[11px] text-content-muted">Recompensa</p>
                    <p class="font-bold text-sm text-primary-strong">+30 XP</p>
                </div>
            </div>

            <div class="pt-2">
                <BaseButton variant="primary" size="lg" class="shadow-md shadow-primary/20" @click="startExam">
                    <Sparkles :size="18" class="mr-2" /> Iniciar Simulacro Ahora
                </BaseButton>
            </div>
        </div>

        <!-- Pantalla del Examen en Curso -->
        <div v-else-if="exam && !evaluationResult" class="space-y-6">
            <!-- Barra Superior del Examen -->
            <div class="sticky top-0 z-20 -mt-2 -mx-2 px-4 py-3 bg-surface/95 backdrop-blur border-b border-border flex items-center justify-between gap-4 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-raised border border-border text-xs font-bold text-content-primary">
                        <Clock :size="14" class="text-amber-500 animate-pulse" />
                        <span>{{ formattedTime }}</span>
                    </div>
                    <span class="text-xs text-content-secondary hidden sm:inline">
                        Respondidas: <strong>{{ answeredCount }}</strong> / {{ allQuestions.length }}
                    </span>
                </div>
                <BaseButton
                    variant="primary"
                    size="sm"
                    :disabled="evaluating"
                    @click="submitExam"
                >
                    <Loader2 v-if="evaluating" :size="14" class="animate-spin mr-1" />
                    <Send v-else :size="14" class="mr-1" />
                    Entregar Examen
                </BaseButton>
            </div>

            <!-- Lista de Preguntas -->
            <div class="space-y-6 pb-6">
                <div
                    v-for="(q, idx) in allQuestions"
                    :key="q.id"
                    class="p-5 rounded-2xl bg-surface border border-border space-y-3 transition-colors"
                    :class="{ 'border-primary/50 bg-primary/5': userAnswers[q.id] !== undefined }"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-md bg-surface-raised text-content-secondary border border-border">
                            Pregunta {{ idx + 1 }} · {{ q.type === 'mc' ? 'Opción Múltiple' : 'Desarrollo' }}
                        </span>
                    </div>

                    <p class="text-sm font-semibold text-content-primary leading-relaxed">
                        {{ q.question }}
                    </p>

                    <!-- Opciones Múltiples -->
                    <div v-if="q.type === 'mc'" class="space-y-2 pt-1">
                        <label
                            v-for="(opt, optIdx) in q.options"
                            :key="optIdx"
                            class="flex items-center gap-3 p-3 rounded-xl border border-border cursor-pointer transition-all hover:bg-surface-raised text-xs font-medium text-content-primary"
                            :class="{
                                'border-primary bg-primary/10 font-bold text-primary-strong shadow-sm': userAnswers[q.id] === optIdx
                            }"
                        >
                            <input
                                type="radio"
                                :name="`question_${q.id}`"
                                :value="optIdx"
                                v-model="userAnswers[q.id]"
                                class="w-4 h-4 text-primary focus:ring-primary"
                            />
                            <span>{{ ['A', 'B', 'C', 'D'][optIdx] }}) {{ opt }}</span>
                        </label>
                    </div>

                    <!-- Pregunta de Desarrollo / Respuesta Libre -->
                    <div v-else class="pt-1">
                        <textarea
                            v-model="userAnswers[q.id]"
                            rows="3"
                            placeholder="Escribe tu respuesta y fundamentación conceptual aquí..."
                            class="w-full text-xs rounded-xl border border-border bg-surface-sunken p-3 text-content-primary focus:border-primary focus:ring-1 focus:ring-primary outline-none transition"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pantalla de Resultados y Retroalimentación -->
        <div v-else-if="evaluationResult" class="space-y-6 py-2">
            <div class="p-6 rounded-2xl bg-gradient-to-br from-surface-raised to-surface border border-border text-center space-y-4">
                <div class="inline-flex p-3 rounded-2xl bg-primary/15 text-primary-strong">
                    <Trophy :size="36" />
                </div>
                <div>
                    <span class="text-xs uppercase font-bold tracking-wider text-content-muted">Calificación Final</span>
                    <div class="text-4xl font-black font-display mt-1" :class="evaluationResult.final_grade >= 11 ? 'text-success' : 'text-danger'">
                        {{ evaluationResult.final_grade }} <span class="text-lg text-content-muted font-normal">/ 20.0</span>
                    </div>
                </div>
                <p class="text-sm text-content-secondary max-w-xl mx-auto italic">
                    "{{ evaluationResult.feedback_summary }}"
                </p>
                <div v-if="evaluationResult.failed_concepts?.length" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-300 font-semibold text-xs border border-amber-500/30">
                    <Sparkles :size="13" />
                    {{ evaluationResult.failed_concepts.length }} conceptos convertidos a Flashcards en Caja 1
                </div>
            </div>

            <!-- Revisión de Preguntas -->
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-content-primary flex items-center gap-2">
                    <BookOpen :size="16" /> Desglose Pregunta por Pregunta
                </h4>

                <div
                    v-for="(rev, i) in evaluationResult.questions_review || []"
                    :key="i"
                    class="p-4 rounded-xl border border-border bg-surface text-xs space-y-1.5"
                >
                    <div class="flex items-center justify-between font-bold">
                        <span class="flex items-center gap-1.5" :class="rev.is_correct ? 'text-success' : 'text-danger'">
                            <CheckCircle2 v-if="rev.is_correct" :size="15" />
                            <XCircle v-else :size="15" />
                            Pregunta #{{ rev.id }}
                        </span>
                        <span class="text-content-muted">{{ rev.score }} / {{ rev.max_score }} pts</span>
                    </div>
                    <p class="text-content-secondary leading-relaxed">{{ rev.comment }}</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <BaseButton variant="secondary" @click="handleClose">
                    Cerrar y Ver Flashcards
                </BaseButton>
                <BaseButton variant="primary" @click="startExam">
                    <RotateCcw :size="14" class="mr-1.5" /> Intentar Otro Simulacro
                </BaseButton>
            </div>
        </div>
    </BaseModal>
</template>
