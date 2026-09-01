<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import KnowledgeGraphView from '@/Components/Calendar/KnowledgeGraphView.vue';
import CourseMindMapView from '@/Components/Learning/CourseMindMapView.vue';
import StudySummaryModal from '@/Components/Learning/StudySummaryModal.vue';
import ActiveRecallModal from '@/Components/Learning/ActiveRecallModal.vue';
import CourseFlashcardsTab from './CourseFlashcardsTab.vue';
import {
    Brain,
    Layers,
    BookOpen,
    HelpCircle,
    Loader2
} from '@lucide/vue';

const props = defineProps({
    course: { type: Object, required: true },
    graphData: { type: Object, required: false, default: () => ({}) },
    learningStats: { type: Object, required: false, default: () => ({}) },
});

// ── Estado de Vistas ─────────────────────────────────────────────────────────
const getSavedViewMode = () => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem(`course_${props.course.id}_learning_view`);
        if (saved && ['flashcards', 'chunks', 'mindmap', 'graph'].includes(saved)) {
            return saved;
        }
    }
    return 'flashcards';
};

const viewMode = ref(getSavedViewMode()); // 'flashcards' | 'chunks' | 'mindmap' | 'graph'

if (typeof window !== 'undefined') {
    // Guardar cambio de subvista
    const updateViewMode = (mode) => {
        viewMode.value = mode;
        localStorage.setItem(`course_${props.course.id}_learning_view`, mode);
    };
}
const generatingAI = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

// Modales Especializados
const showStudyModal = ref(false);
const showRecallModal = ref(false);
const activeChunk = ref(null);
const selectedRecallIndex = ref(0);

// Grafo reactivo local (específico del curso)
const localGraphData = ref({ ...props.graphData });
const allNodes = computed(() => localGraphData.value.nodes || []);
const allEdges = computed(() => localGraphData.value.edges || []);

// Filtrar solo los chunks
const localChunks = computed(() => allNodes.value.filter(n => !n.is_parent));

// ── Cuota de IA Restante ───────────────────────────────────────────────────
const quotaRemaining = computed(() => {
    if (localGraphData.value.quota?.remaining !== undefined) {
        return localGraphData.value.quota.remaining;
    }
    const used = localGraphData.value.quota?.used_count || 0;
    return Math.max(0, 5 - used);
});

// ── Métricas Reales Reactivas en Vivo ───────────────────────────────────────
const realGlobalMastery = computed(() => {
    const list = localChunks.value;
    if (list.length === 0) return 0;
    const sum = list.reduce((acc, c) => acc + (Number(c.mastery) || 0), 0);
    return Math.round(sum / list.length);
});

const weakChunksCount = computed(() => {
    return localChunks.value.filter(c => (Number(c.mastery) || 0) < 60 && (Number(c.mastery) || 0) > 0).length;
});

// Conexiones relacionadas para el chunk activo
const activeChunkRelations = computed(() => {
    if (!activeChunk.value) return [];
    const chunkId = activeChunk.value.id;
    const related = [];

    allEdges.value.forEach(e => {
        if (e.source === chunkId || e.source?.id === chunkId) {
            const targetId = e.target?.id || e.target;
            const targetNode = localChunks.value.find(n => n.id === targetId);
            if (targetNode) {
                related.push({ node: targetNode, label: e.label || 'se relaciona con' });
            }
        } else if (e.target === chunkId || e.target?.id === chunkId) {
            const sourceId = e.source?.id || e.source;
            const sourceNode = localChunks.value.find(n => n.id === sourceId);
            if (sourceNode) {
                related.push({ node: sourceNode, label: e.label || 'conecta con' });
            }
        }
    });

    return related;
});

// ── Acciones de Estudio y Recall ────────────────────────────────────────────
function openStudy(chunk) {
    activeChunk.value = chunk;
    showStudyModal.value = true;
}

function openRecall(chunk) {
    activeChunk.value = chunk;
    const idx = localChunks.value.findIndex(c => c.id === chunk.id);
    selectedRecallIndex.value = idx >= 0 ? idx : 0;
    showRecallModal.value = true;
}

function handleRecallEvaluation({ chunkId, delta }) {
    const chunk = localChunks.value.find(c => c.id === chunkId);
    if (!chunk) return;

    const current = Number(chunk.mastery) || 0;
    const updated = Math.max(0, Math.min(100, current + delta));
    chunk.mastery = updated;

    axios.post(route('courses.learning.chunk.mastery', { course: props.course.id }), {
        node_id: chunkId,
        delta: delta,
    }).catch(err => {
        console.error('Error guardando mastery:', err);
    });

    triggerToast(`Dominio actualizado a ${updated}%`, delta > 0 ? 'success' : 'info');
}

// ── Generación de IA por Curso ─────────────────────────────────────────────
async function handleGenerateAI() {
    if (quotaRemaining.value <= 0) {
        triggerToast('Has alcanzado el límite de 5 intentos diarios de IA', 'error');
        return;
    }

    generatingAI.value = true;
    try {
        const res = await axios.post(route('courses.learning.generate-graph', { course: props.course.id }));
        if (res.data.success) {
            triggerToast('Conocimiento actualizado con IA exitosamente. Recargando...', 'success');
            setTimeout(() => { window.location.reload(); }, 1500);
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Error al conectar con IA';
        triggerToast(msg, 'error');
    } finally {
        generatingAI.value = false;
    }
}

function triggerToast(msg, type = 'success') {
    if (typeof window !== 'undefined') {
        const event = new CustomEvent('epycus-toast', { detail: { message: msg, type: type } });
        window.dispatchEvent(event);
    }
}

function getMasteryColor(mastery) {
    const m = Number(mastery) || 0;
    if (m === 0) return 'text-slate-400 bg-slate-200 dark:bg-slate-700';
    if (m >= 80) return 'text-emerald-600 bg-emerald-500';
    if (m >= 60) return 'text-amber-600 bg-amber-500';
    return 'text-rose-600 bg-rose-500';
}
</script>

<template>
    <div class="space-y-6">
        <!-- 1. HEADER PRINCIPAL: MÉTRICAS & SELECTOR DE VISTAS -->
        <div class="bg-surface rounded-3xl border border-border p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-indigo-500/10 text-primary-strong border border-indigo-500/20">
                        <Brain class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-xl sm:text-2xl font-black text-content-primary tracking-tight">
                            Zona de Aprendizaje
                        </h3>
                        <p class="text-xs sm:text-sm text-content-secondary mt-0.5">
                            Asimilación cognitiva para {{ course.name }}
                        </p>
                    </div>
                </div>

                <!-- Métricas Reactivas -->
                <div class="flex items-center gap-3 mt-4 text-xs">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-raised border border-border">
                        <span class="text-content-muted font-medium">Dominio:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                            {{ realGlobalMastery }}%
                        </span>
                        <div class="w-16 h-1.5 bg-surface-sunken rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all duration-500" :style="{ width: `${realGlobalMastery}%` }" />
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all"
                        :class="weakChunksCount > 0
                            ? 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 text-rose-700 dark:text-rose-400'
                            : 'bg-surface-raised border-border text-content-secondary'"
                    >
                        <span><strong>{{ weakChunksCount }}</strong> por reforzar (&lt;60%)</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="flex items-center bg-surface-sunken p-1 rounded-2xl border border-border flex-wrap gap-1">
                    <button
                        type="button"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        :class="viewMode === 'flashcards' ? 'bg-surface text-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                        @click="viewMode = 'flashcards'; updateViewMode('flashcards')"
                    >
                        <span>🃏 Flashcards</span>
                    </button>
                    <button
                        type="button"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        :class="viewMode === 'chunks' ? 'bg-surface text-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                        @click="viewMode = 'chunks'; updateViewMode('chunks')"
                    >
                        <span>🧩 Chunks</span>
                    </button>
                    <button
                        type="button"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        :class="viewMode === 'mindmap' ? 'bg-surface text-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                        @click="viewMode = 'mindmap'; updateViewMode('mindmap')"
                    >
                        <span>🗺️ Mapa Mental</span>
                    </button>
                    <button
                        type="button"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                        :class="viewMode === 'graph' ? 'bg-surface text-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                        @click="viewMode = 'graph'; updateViewMode('graph')"
                    >
                        <span>🧠 Grafo 3D</span>
                    </button>
                </div>
                
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary-strong hover:bg-primary-strong/90 text-white text-xs font-bold shadow-sm transition-all disabled:opacity-50"
                    :disabled="generatingAI || quotaRemaining <= 0"
                    @click="handleGenerateAI"
                >
                    <Loader2 v-if="generatingAI" class="w-3.5 h-3.5 animate-spin" />
                    <Brain v-else class="w-3.5 h-3.5" />
                    <span>Sintetizar Notas con IA ({{ quotaRemaining }}/5)</span>
                </button>
            </div>
        </div>

        <!-- Banner de Procesamiento -->
        <div
            v-if="generatingAI"
            class="bg-indigo-50/95 dark:bg-indigo-950/50 border border-indigo-200 rounded-3xl p-4 flex items-center gap-3.5 shadow-sm animate-fade-in"
        >
            <div class="p-2.5 rounded-2xl bg-indigo-600 text-white shadow-md shrink-0">
                <Loader2 class="w-5 h-5 animate-spin" />
            </div>
            <div class="space-y-0.5">
                <h4 class="text-sm font-black text-slate-900 dark:text-content-primary">
                    Sintetizando apuntes con IA...
                </h4>
                <p class="text-xs text-slate-600 dark:text-content-secondary">
                    Esto puede tardar entre 30s y 2 minutos dependiendo de la longitud de tus notas.
                </p>
            </div>
        </div>

        <!-- 0. VISTA: FLASHCARDS (LEITNER & SIMULACRO) -->
        <div v-if="viewMode === 'flashcards'" class="animate-fade-in">
            <CourseFlashcardsTab :course="course" />
        </div>

        <!-- 1. VISTA: DECK DE CHUNKS -->
        <div v-else-if="viewMode === 'chunks'" class="animate-fade-in">
            <div v-if="localChunks.length === 0" class="p-12 text-center rounded-3xl bg-surface border border-dashed border-border space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-primary-strong flex items-center justify-center mx-auto">
                    <Layers class="w-6 h-6" />
                </div>
                <h3 class="text-base font-bold text-content-primary">
                    No hay Chunks registrados
                </h3>
                <p class="text-xs text-content-secondary max-w-md mx-auto">
                    Añade apuntes en la pestaña "Apuntes" y luego aplica la IA para generar flashcards y mapas mentales.
                </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="chunk in localChunks"
                    :key="chunk.id"
                    class="bg-surface rounded-2xl border border-border/80 hover:border-primary-strong/50 shadow-sm transition-all p-5 flex flex-col justify-between space-y-4 group"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span v-if="chunk.category" class="text-[10px] text-content-muted font-medium bg-surface-raised px-2 py-0.5 rounded-md border border-border">
                                {{ chunk.category }}
                            </span>
                        </div>
                        <h3 class="text-base font-bold text-content-primary group-hover:text-primary-strong transition-colors line-clamp-1">
                            {{ chunk.label }}
                        </h3>
                        <p class="text-xs text-content-secondary mt-1.5 line-clamp-2 leading-relaxed font-normal">
                            {{ chunk.summary || 'Concepto clave registrado en tus notas.' }}
                        </p>
                    </div>

                    <!-- Barra de Dominio -->
                    <div class="space-y-2 pt-2 border-t border-border/60">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[11px] font-medium text-content-secondary">Dominio</span>
                            <span class="text-xs font-bold" :class="getMasteryColor(chunk.mastery || 70).split(' ')[0]">
                                {{ chunk.mastery || 70 }}%
                            </span>
                        </div>
                        <div class="w-full h-1.5 bg-surface-sunken rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="getMasteryColor(chunk.mastery || 70).split(' ')[1]"
                                :style="{ width: `${chunk.mastery || 70}%` }"
                            />
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button
                            type="button"
                            class="py-2 px-3 rounded-xl bg-primary-strong hover:bg-primary-strong/90 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95"
                            @click="openStudy(chunk)"
                        >
                            <BookOpen class="w-3.5 h-3.5" />
                            <span>Estudiar</span>
                        </button>
                        <button
                            type="button"
                            class="py-2 px-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-all flex items-center justify-center gap-1.5 active:scale-95"
                            @click="openRecall(chunk)"
                        >
                            <HelpCircle class="w-3.5 h-3.5" />
                            <span>Recall</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. VISTA: MAPA MENTAL -->
        <div v-else-if="viewMode === 'mindmap'" class="animate-fade-in">
            <CourseMindMapView
                :course="course"
                :chunks="localChunks"
                @studyChunk="openStudy"
                @generateAi="handleGenerateAI"
            />
        </div>

        <!-- 4. VISTA: GRAFO 3D -->
        <div v-else-if="viewMode === 'graph'" class="animate-fade-in">
            <KnowledgeGraphView
                :show="true"
                :courses="[course]"
                @close="viewMode = 'chunks'"
            />
        </div>

        <!-- Modales -->
        <StudySummaryModal
            :show="showStudyModal"
            :chunk="activeChunk"
            :relatedChunks="activeChunkRelations"
            @close="showStudyModal = false"
            @startRecall="(c) => { showStudyModal = false; openRecall(c); }"
            @openRelated="(node) => { activeChunk = node; }"
        />

        <ActiveRecallModal
            :show="showRecallModal"
            :chunks="localChunks"
            :initial-index="selectedRecallIndex"
            @close="showRecallModal = false"
            @evaluated="handleRecallEvaluation"
        />
    </div>
</template>
