<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import BaseCard from '@/Components/ui/BaseCard.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseModal from '@/Components/ui/BaseModal.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import CourseExamSimulatorModal from './CourseExamSimulatorModal.vue';
import { triggerConfetti } from '@/utils/celebration';
import {
    Sparkles,
    Plus,
    Loader2,
    RotateCw,
    Check,
    Trash2,
    Pencil,
    Brain,
    HelpCircle,
    Trophy,
    Layers,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Flame,
    FileText,
} from '@lucide/vue';

const props = defineProps({
    course: { type: Object, required: true },
});

const loading = ref(false);
const generatingAi = ref(false);
const flashcards = ref([]);
const dueCards = ref([]);
const stats = ref({
    total: 0,
    due_today: 0,
    mastered: 0,
    box_counts: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
});

// Vistas: 'overview' | 'study'
const viewMode = ref('overview');
const selectedBoxFilter = ref('all'); // 'all' | 1 | 2 | 3 | 4 | 5 | 'due'

// Estudio
const studyDeck = ref([]);
const currentStudyIndex = ref(0);
const isCardFlipped = ref(false);
const reviewing = ref(false);
const sessionCompleted = ref(false);

// Modales
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showExamModal = ref(false);
const selectedCard = ref(null);

const form = ref({
    question: '',
    answer: '',
});

const filteredCards = computed(() => {
    if (selectedBoxFilter.value === 'all') return flashcards.value;
    if (selectedBoxFilter.value === 'due') return dueCards.value;
    return flashcards.value.filter(c => c.leitner_box === Number(selectedBoxFilter.value));
});

const currentStudyCard = computed(() => {
    return studyDeck.value[currentStudyIndex.value] || null;
});

async function loadFlashcards() {
    loading.value = true;
    try {
        const res = await axios.get(route('courses.flashcards.index', { course: props.course.id }));
        flashcards.value = res.data.flashcards || [];
        dueCards.value = res.data.due_flashcards || [];
        stats.value = res.data.stats || { total: 0, due_today: 0, mastered: 0, box_counts: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 } };
    } catch (e) {
        console.error('Error cargando flashcards:', e);
    } finally {
        loading.value = false;
    }
}

function startStudy(onlyDue = false) {
    const deck = onlyDue ? [...dueCards.value] : [...flashcards.value];
    if (deck.length === 0) return;
    studyDeck.value = deck;
    currentStudyIndex.value = 0;
    isCardFlipped.value = false;
    sessionCompleted.value = false;
    viewMode.value = 'study';
}

function flipCard() {
    isCardFlipped.value = !isCardFlipped.value;
}

async function rateCard(rating) {
    if (reviewing.value || !currentStudyCard.value) return;
    reviewing.value = true;

    try {
        const cardId = currentStudyCard.value.id;
        const res = await axios.post(route('flashcards.review', { id: cardId }), { rating });

        // Actualizar tarjeta local
        const updated = res.data.flashcard;
        const idx = flashcards.value.findIndex(c => c.id === cardId);
        if (idx !== -1) flashcards.value[idx] = updated;

        // Avanzar al siguiente
        if (currentStudyIndex.value < studyDeck.value.length - 1) {
            currentStudyIndex.value++;
            isCardFlipped.value = false;
        } else {
            sessionCompleted.value = true;
            triggerConfetti();
        }
        await loadFlashcards();
    } catch (e) {
        console.error('Error al calificar tarjeta:', e);
    } finally {
        reviewing.value = false;
    }
}

async function createFlashcard() {
    if (!form.value.question || !form.value.answer) return;
    try {
        await axios.post(route('courses.flashcards.store', { course: props.course.id }), form.value);
        form.value = { question: '', answer: '' };
        showCreateModal.value = false;
        await loadFlashcards();
    } catch (e) {
        alert('Error al crear flashcard: ' + (e.response?.data?.message || e.message));
    }
}

function openEditModal(card) {
    selectedCard.value = card;
    form.value = { question: card.question, answer: card.answer };
    showEditModal.value = true;
}

async function updateFlashcard() {
    if (!selectedCard.value) return;
    try {
        await axios.put(route('flashcards.update', { id: selectedCard.value.id }), form.value);
        showEditModal.value = false;
        selectedCard.value = null;
        await loadFlashcards();
    } catch (e) {
        alert('Error al actualizar: ' + (e.response?.data?.message || e.message));
    }
}

async function deleteCard(id) {
    if (!confirm('¿Deseas eliminar esta Flashcard?')) return;
    try {
        await axios.delete(route('flashcards.destroy', { id }));
        await loadFlashcards();
    } catch (e) {
        alert('Error al eliminar: ' + (e.response?.data?.message || e.message));
    }
}

async function generateWithAi() {
    generatingAi.value = true;
    try {
        const res = await axios.post(route('courses.flashcards.generate-ai', { course: props.course.id }));
        alert(res.data.message || 'Flashcards generadas con éxito.');
        await loadFlashcards();
    } catch (e) {
        alert(e.response?.data?.error || 'Los servidores están en mantenimiento. Disculpe.');
    } finally {
        generatingAi.value = false;
    }
}

function getBoxLabel(box) {
    const labels = {
        1: 'Caja 1 (Diario)',
        2: 'Caja 2 (Cada 3 días)',
        3: 'Caja 3 (Cada 7 días)',
        4: 'Caja 4 (Cada 14 días)',
        5: 'Caja 5 (Dominada · 30 días)',
    };
    return labels[box] || `Caja ${box}`;
}

function formatReviewDate(dateStr) {
    if (!dateStr) return 'Hoy';
    const cleanStr = String(dateStr).substring(0, 10);
    const parts = cleanStr.split('-');
    if (parts.length !== 3) return 'Hoy';
    const [y, m, d] = parts;

    const now = new Date();
    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

    if (cleanStr <= todayStr) {
        return 'Hoy (Pendiente)';
    }
    return `Repaso: ${d}/${m}/${y}`;
}

onMounted(() => {
    loadFlashcards();
});
</script>

<template>
    <div class="space-y-6">
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- CABECERA: SISTEMA DE CAJAS LEITNER & ACCIONES PRINCIPALES       -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <BaseCard class="p-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Información y Mazo -->
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-primary/15 text-primary-strong">🃏</span>
                        <h3 class="font-display text-xl font-bold text-content-primary">
                            Active Recall & Cajas de Leitner
                        </h3>
                    </div>
                    <p class="text-xs text-content-secondary max-w-xl leading-relaxed">
                        Repetición espaciada neurocientífica. Las tarjetas avanzan de caja (1 → 5) cuando las recuerdas con facilidad y retroceden cuando fallas, optimizando tu memoria a largo plazo.
                    </p>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <BaseButton
                        variant="secondary"
                        size="sm"
                        :disabled="generatingAi"
                        @click="generateWithAi"
                    >
                        <Loader2 v-if="generatingAi" :size="14" class="animate-spin mr-1.5" />
                        <Sparkles v-else :size="14" class="text-primary-strong mr-1.5" />
                        Generar con IA
                    </BaseButton>

                    <BaseButton
                        variant="secondary"
                        size="sm"
                        @click="showExamModal = true"
                    >
                        <FileText :size="14" class="text-amber-500 mr-1.5" />
                        Simulacro IA
                    </BaseButton>

                    <BaseButton
                        variant="primary"
                        size="sm"
                        @click="showCreateModal = true"
                    >
                        <Plus :size="14" class="mr-1" />
                        Nueva Tarjeta
                    </BaseButton>

                    <BaseButton
                        v-if="dueCards.length > 0 && viewMode === 'overview'"
                        variant="primary"
                        size="sm"
                        class="bg-gradient-to-r from-primary-strong to-accent shadow-md shadow-primary/20"
                        @click="startStudy(true)"
                    >
                        <Flame :size="14" class="mr-1.5" />
                        Repasar Hoy ({{ dueCards.length }})
                    </BaseButton>
                </div>
            </div>

            <!-- Indicadores de Cajas de Leitner -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-6 pt-6 border-t border-border">
                <div
                    v-for="b in [1, 2, 3, 4, 5]"
                    :key="b"
                    class="p-3 rounded-xl border transition-all cursor-pointer"
                    :class="[
                        selectedBoxFilter === b
                            ? 'border-primary bg-primary/10 shadow-sm'
                            : 'border-border bg-surface-sunken hover:bg-surface-raised'
                    ]"
                    @click="selectedBoxFilter = selectedBoxFilter === b ? 'all' : b; viewMode = 'overview'"
                >
                    <div class="flex items-center justify-between text-[11px] font-bold text-content-muted">
                        <span>Caja {{ b }}</span>
                        <span class="text-[10px] font-medium opacity-75">
                            {{ b === 1 ? '1d' : b === 2 ? '3d' : b === 3 ? '7d' : b === 4 ? '14d' : '30d' }}
                        </span>
                    </div>
                    <div class="font-display text-xl font-black text-content-primary mt-1">
                        {{ stats.box_counts[b] || 0 }}
                    </div>
                    <div class="w-full bg-border rounded-full h-1.5 mt-2 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="b === 5 ? 'bg-success' : b >= 3 ? 'bg-primary' : 'bg-warning'"
                            :style="{ width: `${stats.total > 0 ? ((stats.box_counts[b] || 0) / stats.total) * 100 : 0}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </BaseCard>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- VISTA 1: MODO ESTUDIO INTERACTIVO (3D FLIP CARD)                -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div v-if="viewMode === 'study'" class="space-y-6">
            <div class="flex items-center justify-between">
                <BaseButton variant="secondary" size="sm" @click="viewMode = 'overview'">
                    <ChevronLeft :size="15" class="mr-1" /> Salir del Modo Estudio
                </BaseButton>

                <span class="text-xs font-bold text-content-secondary">
                    Tarjeta {{ currentStudyIndex + 1 }} de {{ studyDeck.length }}
                </span>
            </div>

            <!-- Pantalla de Sesión Completada -->
            <div v-if="sessionCompleted" class="py-12 text-center space-y-4">
                <div class="w-20 h-20 rounded-3xl bg-success/15 text-success flex items-center justify-center mx-auto animate-bounce">
                    <Trophy :size="40" />
                </div>
                <h3 class="text-2xl font-bold font-display text-content-primary">¡Sesión de Repaso Completada!</h3>
                <p class="text-sm text-content-secondary max-w-md mx-auto">
                    Has fortalecido tus conexiones neuronales mediante Active Recall. Tus tarjetas han sido reprogramadas en sus respectivas cajas de Leitner.
                </p>
                <div class="pt-4 flex justify-center gap-3">
                    <BaseButton variant="secondary" @click="viewMode = 'overview'">
                        Ver todas las tarjetas
                    </BaseButton>
                    <BaseButton variant="primary" @click="startStudy(false)">
                        Repasar mazo completo
                    </BaseButton>
                </div>
            </div>

            <!-- Flip Card Activa -->
            <div v-else-if="currentStudyCard" class="max-w-2xl mx-auto space-y-6">
                <!-- Tarjeta Interactiva -->
                <div
                    class="flip-card cursor-pointer select-none"
                    @click="flipCard"
                >
                    <div class="flip-card-inner" :class="{ 'is-flipped': isCardFlipped }">
                        <!-- Frente (Pregunta) -->
                        <div class="flip-card-front p-8 rounded-3xl bg-surface border border-border shadow-xl flex flex-col justify-between min-h-[320px]">
                            <div class="flex items-center justify-between text-xs text-content-muted">
                                <span class="px-2.5 py-1 rounded-lg bg-surface-raised border border-border font-bold">
                                    {{ getBoxLabel(currentStudyCard.leitner_box) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <HelpCircle :size="14" /> Haz clic para ver respuesta
                                </span>
                            </div>

                            <div class="py-6 text-center">
                                <span class="text-xs uppercase font-bold tracking-wider text-primary-strong mb-2 block">
                                    Pregunta de Active Recall
                                </span>
                                <p class="text-lg sm:text-xl font-bold text-content-primary leading-relaxed">
                                    {{ currentStudyCard.question }}
                                </p>
                            </div>

                            <div class="text-center text-xs text-content-muted font-medium">
                                Pulsa Espacio o toca para voltear 🔄
                            </div>
                        </div>

                        <!-- Reverso (Respuesta) -->
                        <div class="flip-card-back p-8 rounded-3xl bg-surface-raised border border-primary/40 shadow-xl flex flex-col justify-between min-h-[320px]">
                            <div class="flex items-center justify-between text-xs text-content-muted">
                                <span class="px-2.5 py-1 rounded-lg bg-primary/10 text-primary-strong font-bold">
                                    Respuesta Correcta
                                </span>
                                <span class="text-xs text-content-muted">
                                    {{ currentStudyCard.source === 'ai' ? 'Generada con IA' : 'Manual' }}
                                </span>
                            </div>

                            <div class="py-4 text-center">
                                <p class="text-base sm:text-lg font-medium text-content-primary leading-relaxed whitespace-pre-line">
                                    {{ currentStudyCard.answer }}
                                </p>
                            </div>

                            <div class="text-center text-[11px] text-content-muted">
                                ¿Qué tan fácil fue recordarlo?
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Calificación Leitner -->
                <div v-if="isCardFlipped" class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                    <button
                        type="button"
                        class="p-3.5 rounded-2xl bg-danger/10 hover:bg-danger/20 border border-danger/30 text-danger text-xs font-bold transition-all text-center flex flex-col items-center gap-1 shadow-sm"
                        :disabled="reviewing"
                        @click="rateCard('fail')"
                    >
                        <span class="text-base">🔴</span>
                        <span>Falla / Olvidé</span>
                        <span class="text-[10px] font-normal opacity-80">Vuelve a Caja 1</span>
                    </button>

                    <button
                        type="button"
                        class="p-3.5 rounded-2xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs font-bold transition-all text-center flex flex-col items-center gap-1 shadow-sm"
                        :disabled="reviewing"
                        @click="rateCard('hard')"
                    >
                        <span class="text-base">🟡</span>
                        <span>Difícil / Casi</span>
                        <span class="text-[10px] font-normal opacity-80">Repite mañana</span>
                    </button>

                    <button
                        type="button"
                        class="p-3.5 rounded-2xl bg-primary/10 hover:bg-primary/20 border border-primary/30 text-primary-strong text-xs font-bold transition-all text-center flex flex-col items-center gap-1 shadow-sm"
                        :disabled="reviewing"
                        @click="rateCard('good')"
                    >
                        <span class="text-base">🟢</span>
                        <span>Bien / Recordé</span>
                        <span class="text-[10px] font-normal opacity-80">+1 Caja Leitner</span>
                    </button>

                    <button
                        type="button"
                        class="p-3.5 rounded-2xl bg-success/10 hover:bg-success/20 border border-success/30 text-success text-xs font-bold transition-all text-center flex flex-col items-center gap-1 shadow-sm"
                        :disabled="reviewing"
                        @click="rateCard('easy')"
                    >
                        <span class="text-base">💎</span>
                        <span>Muy Fácil</span>
                        <span class="text-[10px] font-normal opacity-80">+1 Caja Leitner</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- VISTA 2: LISTA GENERAL & GESTIÓN DE FLASHCARDS                  -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div v-else class="space-y-4">
            <!-- Barra de Filtros -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-content-secondary">Filtrar por:</span>
                    <select
                        v-model="selectedBoxFilter"
                        class="text-xs rounded-xl border border-border bg-surface px-3 py-1.5 text-content-primary font-medium outline-none"
                    >
                        <option value="all">Todas las cajas ({{ stats.total }})</option>
                        <option value="due">Pendientes para hoy ({{ stats.due_today }})</option>
                        <option :value="1">Caja 1 — Diario ({{ stats.box_counts[1] || 0 }})</option>
                        <option :value="2">Caja 2 — 3 días ({{ stats.box_counts[2] || 0 }})</option>
                        <option :value="3">Caja 3 — 7 días ({{ stats.box_counts[3] || 0 }})</option>
                        <option :value="4">Caja 4 — 14 días ({{ stats.box_counts[4] || 0 }})</option>
                        <option :value="5">Caja 5 — Dominadas 30d ({{ stats.box_counts[5] || 0 }})</option>
                    </select>
                </div>

                <div v-if="filteredCards.length > 0">
                    <BaseButton variant="secondary" size="sm" @click="startStudy(false)">
                        <Layers :size="14" class="mr-1.5" /> Estudiar Mazo Seleccionado ({{ filteredCards.length }})
                    </BaseButton>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="py-12 text-center text-content-muted">
                <Loader2 :size="24" class="animate-spin mx-auto mb-2" />
                Cargando Flashcards...
            </div>

            <!-- Estado Vacío -->
            <div v-else-if="filteredCards.length === 0" class="py-12 text-center p-8 rounded-3xl bg-surface border border-dashed border-border space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary-strong flex items-center justify-center mx-auto">
                    <Brain :size="28" />
                </div>
                <div class="space-y-1">
                    <h4 class="font-bold text-base text-content-primary">No hay Flashcards en esta sección</h4>
                    <p class="text-xs text-content-secondary max-w-sm mx-auto">
                        Crea tus tarjetas manuales o utiliza el generador con IA a partir de tus apuntes.
                    </p>
                </div>
                <div class="flex justify-center gap-2 pt-2">
                    <BaseButton variant="secondary" size="sm" @click="generateWithAi">
                        <Sparkles :size="14" class="mr-1 text-primary-strong" /> Generar con IA
                    </BaseButton>
                    <BaseButton variant="primary" size="sm" @click="showCreateModal = true">
                        <Plus :size="14" class="mr-1" /> Nueva Tarjeta
                    </BaseButton>
                </div>
            </div>

            <!-- Grilla de Tarjetas -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="card in filteredCards"
                    :key="card.id"
                    class="p-5 rounded-2xl bg-surface border border-border flex flex-col justify-between space-y-4 hover:shadow-md hover:border-primary/40 transition-all group"
                >
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold px-2 py-0.5 rounded-md bg-surface-raised text-content-secondary border border-border">
                                {{ getBoxLabel(card.leitner_box) }}
                            </span>
                            <span class="text-content-muted flex items-center gap-1 text-[10px]">
                                <Calendar :size="12" />
                                {{ formatReviewDate(card.next_review_at) }}
                            </span>
                        </div>

                        <h4 class="font-bold text-sm text-content-primary leading-snug">
                            {{ card.question }}
                        </h4>

                        <p class="text-xs text-content-secondary line-clamp-3 leading-relaxed">
                            {{ card.answer }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-border flex items-center justify-between text-xs text-content-muted">
                        <span class="text-[10px]">
                            Repasos: <strong>{{ card.review_count }}</strong>
                        </span>

                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                type="button"
                                class="p-1.5 rounded-lg hover:bg-surface-raised text-content-secondary hover:text-content-primary"
                                title="Editar tarjeta"
                                @click="openEditModal(card)"
                            >
                                <Pencil :size="13" />
                            </button>
                            <button
                                type="button"
                                class="p-1.5 rounded-lg hover:bg-danger/10 text-content-secondary hover:text-danger"
                                title="Eliminar tarjeta"
                                @click="deleteCard(card.id)"
                            >
                                <Trash2 :size="13" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MODAL: CREAR / EDITAR FLASHCARD                                 -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <BaseModal
            :show="showCreateModal || showEditModal"
            :title="showEditModal ? 'Editar Flashcard' : 'Nueva Flashcard'"
            @close="showCreateModal = false; showEditModal = false"
        >
            <form @submit.prevent="showEditModal ? updateFlashcard() : createFlashcard()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">
                        Pregunta o Concepto Frontal (Active Recall)
                    </label>
                    <textarea
                        v-model="form.question"
                        rows="3"
                        required
                        placeholder="Ej: ¿Cuál es la diferencia entre un proceso y un hilo?"
                        class="w-full text-xs rounded-xl border border-border bg-surface-sunken p-3 text-content-primary focus:border-primary outline-none"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-content-primary mb-1">
                        Respuesta o Explicación Posterior
                    </label>
                    <textarea
                        v-model="form.answer"
                        rows="4"
                        required
                        placeholder="Respuesta concisa, precisa y comprensible..."
                        class="w-full text-xs rounded-xl border border-border bg-surface-sunken p-3 text-content-primary focus:border-primary outline-none"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <BaseButton variant="secondary" type="button" @click="showCreateModal = false; showEditModal = false">
                        Cancelar
                    </BaseButton>
                    <BaseButton variant="primary" type="submit">
                        {{ showEditModal ? 'Guardar Cambios' : 'Crear Tarjeta' }}
                    </BaseButton>
                </div>
            </form>
        </BaseModal>

        <!-- MODAL: SIMULACRO DE EXAMEN CON IA -->
        <CourseExamSimulatorModal
            :show="showExamModal"
            :course="course"
            @close="showExamModal = false"
            @flashcardsCreated="loadFlashcards"
        />
    </div>
</template>

<style scoped>
/* 3D Flip Card Effect */
.flip-card {
    perspective: 1000px;
}

.flip-card-inner {
    position: relative;
    width: 100%;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d;
}

.flip-card-inner.is-flipped {
    transform: rotateY(180deg);
}

.flip-card-front,
.flip-card-back {
    width: 100%;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.flip-card-back {
    position: absolute;
    top: 0;
    left: 0;
    transform: rotateY(180deg);
}
</style>
