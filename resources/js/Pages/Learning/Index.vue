<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import KnowledgeGraphView from '@/Components/Calendar/KnowledgeGraphView.vue';
import NoteEditorModal from '@/Components/Calendar/NoteEditorModal.vue';
import CourseMindMapView from '@/Components/Learning/CourseMindMapView.vue';
import StudySummaryModal from '@/Components/Learning/StudySummaryModal.vue';
import ActiveRecallModal from '@/Components/Learning/ActiveRecallModal.vue';
import {
    Brain,
    Layers,
    Search,
    BookOpen,
    HelpCircle,
    Loader2,
    Sparkles
} from '@lucide/vue';

const props = defineProps({
    courses: { type: Array, default: () => [] },
    graphData: { type: Object, default: () => ({ has_graph: false, nodes: [], edges: [], stats: {}, quota: { used_count: 0, max_quota: 5, remaining: 5 } }) },
    learningStats: { type: Object, default: () => ({ avgMastery: 70, totalChunks: 0, weakChunksCount: 0, streakDays: 4 }) },
});

// ── Estado de Vistas (Tríada de Aprendizaje) ─────────────────────────────────
const viewMode = ref('chunks'); // 'chunks' | 'mindmap' | 'graph'
const selectedCourseId = ref(null);
const searchQuery = ref('');
const toastMessage = ref('');
const toastType = ref('success');
const generatingAI = ref(false);

// Modales Especializados
const showStudyModal = ref(false);
const showRecallModal = ref(false);
const activeChunk = ref(null);
const selectedRecallIndex = ref(0);
const showNoteModal = ref(false);
const activeNoteCourse = ref(null);

// Grafo reactivo local
const localGraphData = ref({ ...props.graphData });
const allNodes = computed(() => localGraphData.value.nodes || []);
const allEdges = computed(() => localGraphData.value.edges || []);

// Filtrar solo los chunks (excluyendo nodos padre de curso en el deck)
const localChunks = computed(() => {
    return allNodes.value.filter(n => !n.is_parent);
});

// ── Cuota de IA Restante (5 globales por usuario) ───────────────────────────
const quotaRemaining = computed(() => {
    if (localGraphData.value.quota?.remaining !== undefined) {
        return localGraphData.value.quota.remaining;
    }
    const used = localGraphData.value.quota?.used_count || 0;
    return Math.max(0, 5 - used);
});

// ── Métricas Reales Reactivas en Vivo ────────────────────────────────────────
const realGlobalMastery = computed(() => {
    const list = localChunks.value;
    if (list.length === 0) return 70;
    const sum = list.reduce((acc, c) => acc + (c.mastery !== undefined ? c.mastery : 70), 0);
    return Math.round(sum / list.length);
});

const weakChunksCount = computed(() => {
    return localChunks.value.filter(c => (c.mastery !== undefined ? c.mastery : 70) < 60).length;
});

// Chunks filtrados por buscador y curso seleccionado
const filteredChunks = computed(() => {
    let list = localChunks.value;
    if (selectedCourseId.value !== null) {
        list = list.filter(n => n.course_id === selectedCourseId.value);
    }
    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(n =>
            (n.label && n.label.toLowerCase().includes(q)) ||
            (n.summary && n.summary.toLowerCase().includes(q)) ||
            (n.course_name && n.course_name.toLowerCase().includes(q))
        );
    }
    return list;
});

const activeCourse = computed(() => {
    if (selectedCourseId.value === null) {
        return props.courses[0] || { id: 1, name: 'Asignatura', color: '#6366f1' };
    }
    return props.courses.find(c => c.id === selectedCourseId.value) || props.courses[0];
});

const courseChunkCounts = computed(() => {
    const counts = {};
    localChunks.value.forEach(n => {
        if (n.course_id) {
            counts[n.course_id] = (counts[n.course_id] || 0) + 1;
        }
    });
    return counts;
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
    const idx = filteredChunks.value.findIndex(c => c.id === chunk.id);
    selectedRecallIndex.value = idx >= 0 ? idx : 0;
    showRecallModal.value = true;
}

function handleRecallEvaluation({ chunkId, delta }) {
    const chunk = localChunks.value.find(c => c.id === chunkId);
    if (!chunk) return;

    // Actualizar dominio reactivamente en vivo
    const current = chunk.mastery !== undefined ? chunk.mastery : 70;
    const updated = Math.max(10, Math.min(100, current + delta));
    chunk.mastery = updated;

    // Persistir en Backend
    axios.post('/api/learning/chunk/mastery', {
        chunk_id: chunkId,
        mastery: updated,
    }).catch(err => {
        console.error('Error guardando mastery:', err);
    });

    triggerToast(`Dominio actualizado a ${updated}%`, delta > 0 ? 'success' : 'info');
}

// ── Generación de IA por Curso o Global ─────────────────────────────────────
async function handleGenerateAI(courseId = null) {
    if (quotaRemaining.value <= 0) {
        triggerToast('Has alcanzado el límite de 5 intentos diarios de IA', 'error');
        return;
    }

    generatingAI.value = true;
    try {
        const targetId = courseId || selectedCourseId.value;
        const res = await axios.post(route('calendar.knowledge-graph.generate'), {
            course_id: targetId,
        });
        if (res.data.success) {
            localGraphData.value.nodes = res.data.nodes || [];
            localGraphData.value.edges = res.data.edges || [];
            localGraphData.value.stats = res.data.stats || {};
            if (res.data.quota) {
                localGraphData.value.quota = res.data.quota;
            }
            triggerToast('Conocimiento actualizado con IA exitosamente', 'success');
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'Error al conectar con IA';
        triggerToast(msg, 'error');
    } finally {
        generatingAI.value = false;
    }
}

function openNoteForCourse(courseId) {
    const found = props.courses.find(c => c.id === courseId);
    if (found) {
        activeNoteCourse.value = found;
        showNoteModal.value = true;
    }
}

function triggerToast(msg, type = 'success') {
    toastMessage.value = msg;
    toastType.value = type;
    setTimeout(() => { toastMessage.value = ''; }, 3500);
}

function getMasteryColor(mastery) {
    if (mastery >= 80) return 'text-emerald-600 bg-emerald-500';
    if (mastery >= 60) return 'text-amber-600 bg-amber-500';
    return 'text-rose-600 bg-rose-500';
}
</script>

<template>
    <AppLayout title="Zona de Aprendizaje">
        <Head title="Zona de Aprendizaje • Segundo Cerebro" />

        <!-- Toast Flotante -->
        <div
            v-if="toastMessage"
            class="fixed top-5 right-5 z-50 px-4 py-2.5 rounded-2xl shadow-xl text-xs font-bold transition-all"
            :class="toastType === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
        >
            {{ toastMessage }}
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            
            <!-- ── 1. HEADER PRINCIPAL: MÉTRICAS & SELECTOR DE VISTAS ─────────── -->
            <div class="bg-surface rounded-3xl border border-border p-6 sm:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-2xl bg-indigo-500/10 text-primary-strong border border-indigo-500/20">
                            <Brain class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-black text-content-primary tracking-tight">
                                Zona de Aprendizaje
                            </h1>
                            <p class="text-xs sm:text-sm text-content-secondary mt-0.5">
                                Asimilación cognitiva profunda • Chunking, Active Recall y Segundo Cerebro
                            </p>
                        </div>
                    </div>

                    <!-- Métricas Reactivas en Vivo -->
                    <div class="flex items-center gap-3 mt-4 text-xs">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-raised border border-border">
                            <span class="text-content-muted font-medium">Dominio Global:</span>
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

                <!-- Selector de Modos de Aprendizaje (Sin Símbolos, Solo Títulos) & Conectar con IA -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="flex items-center bg-surface-sunken p-1 rounded-2xl border border-border">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
                            :class="viewMode === 'chunks' ? 'bg-surface text-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                            @click="viewMode = 'chunks'"
                        >
                            Deck Chunks
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
                            :class="viewMode === 'mindmap' ? 'bg-surface text-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                            @click="viewMode = 'mindmap'"
                        >
                            Mapa Mental
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
                            :class="viewMode === 'graph' ? 'bg-surface text-primary-strong shadow-sm' : 'text-content-secondary hover:text-content-primary'"
                            @click="viewMode = 'graph'"
                        >
                            Segundo Cerebro 3D
                        </button>
                    </div>
                </div>
            </div>

            <!-- Banner Informativo de Procesamiento de IA -->
            <div
                v-if="generatingAI"
                class="bg-indigo-50/95 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/80 rounded-3xl p-4 sm:p-5 flex items-center gap-3.5 shadow-sm animate-fade-in"
            >
                <div class="p-2.5 rounded-2xl bg-indigo-600 text-white shadow-md shrink-0">
                    <Loader2 class="w-5 h-5 animate-spin" />
                </div>
                <div class="space-y-0.5">
                    <h4 class="text-sm font-black text-slate-900 dark:text-content-primary">
                        Sintetizando tu Segundo Cerebro con IA...
                    </h4>
                    <p class="text-xs text-slate-600 dark:text-content-secondary">
                        La IA está procesando tus apuntes oficiales para construir el mapa mental y flashcards. <strong>Esto puede tardar entre 30s y 2 minutos</strong>. Ten paciencia o continúa navegando en tus asignaturas mientras el motor neuronal termina.
                    </p>
                </div>
            </div>

            <!-- ── 2. VISTA 1: DECK VISUAL DE CHUNKS ────────────────────────────── -->
            <div v-if="viewMode === 'chunks'" class="space-y-4">
                
                <!-- Barra de Filtros por Asignatura & Buscador (Diseño Responsivo en 2 Filas sin Desbordes) -->
                <div class="bg-surface p-3 sm:p-4 rounded-2xl border border-border/80 space-y-3 shadow-sm">
                    <!-- Fila 1: Pestañas de Cursos con scroll horizontal suave -->
                    <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pb-1 scroll-smooth">
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
                            :class="selectedCourseId === null ? 'bg-primary-strong text-white shadow-sm' : 'bg-surface-raised text-content-secondary hover:text-content-primary border border-border'"
                            @click="selectedCourseId = null"
                        >
                            Todos ({{ localChunks.length }})
                        </button>
                        <button
                            v-for="c in courses"
                            :key="c.id"
                            type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border cursor-pointer"
                            :class="selectedCourseId === c.id ? 'bg-surface-raised border-primary-strong text-primary-strong shadow-sm' : 'bg-surface-raised/60 border-border text-content-secondary hover:text-content-primary'"
                            @click="selectedCourseId = c.id"
                        >
                            <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: c.color || '#6366f1' }" />
                            <span class="whitespace-nowrap">{{ c.name }}</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-surface-sunken text-content-muted">
                                {{ courseChunkCounts[c.id] || 0 }}
                            </span>
                        </button>
                    </div>

                    <!-- Fila 2: Buscador y Acciones (NotebookLM & Aplicar IA) -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 pt-2 border-t border-border/50">
                        <!-- Buscador Flexible -->
                        <div class="relative flex-1 min-w-0">
                            <Search class="w-3.5 h-3.5 absolute left-3 top-2.5 text-content-muted" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar cápsula..."
                                class="w-full pl-9 pr-3 py-1.5 bg-surface-sunken border border-border rounded-xl text-xs text-content-primary placeholder-content-muted focus:outline-none focus:border-primary-strong"
                            />
                        </div>

                        <!-- Botones de Acción (Notebook & Aplicar IA) -->
                        <div class="flex items-center gap-2 shrink-0 justify-end">
                            <!-- Botón Oficial Google Notebook -->
                            <a
                                href="https://notebooklm.google.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-raised hover:bg-surface-sunken border border-border text-xs font-semibold text-content-secondary hover:text-primary-strong shadow-sm transition-all shrink-0"
                                title="Abrir Google Notebook"
                            >
                                <svg class="w-4 h-4 shrink-0 text-primary-strong" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.0014 3.50928c6.6271 0.00006 11.9997 5.37254 11.9997 11.99972l-0.0035 4.4977c0 0.4376 -0.3548 0.7922 -0.7923 0.7922h-1.1621c-0.4374 0 -0.792 -0.3546 -0.7921 -0.7922l0.0036 -4.4977c0 -5.1104 -4.143 -9.25336 -9.2533 -9.25342 -0.1954 0 -0.3895 0.00605 -0.5819 0.01799 4.4483 0.81634 7.8193 4.71323 7.8193 9.39753v0.0018l-0.0013 4.3338c0 0.4376 -0.3546 0.7922 -0.7922 0.7922h-1.1618c-0.4375 0 -0.7917 -0.3546 -0.7917 -0.7922V15.583c-0.0471 -3.7196 -3.0772 -6.72041 -6.80786 -6.72047 -0.34884 0 -0.6922 0.02551 -1.02803 0.07468 0.02195 0.00479 0.04387 0.00962 0.06575 0.01461 3.25564 0.74232 5.69664 3.62518 5.76414 7.08968h0.0029v3.9652c0 0.4376 -0.3548 0.7922 -0.7923 0.7922h-1.162c-0.4375 0 -0.7922 -0.3546 -0.7922 -0.7922l-0.0012 -3.8178c-0.0001 -2.5422 -2.02879 -4.6109 -4.55565 -4.6749 -0.04008 -0.001 -0.08034 -0.0015 -0.12067 -0.0015 -0.04193 0 -0.0836 0.0005 -0.12501 0.0014 -1.28352 0.0281 -2.313 0.4882 -3.01944 1.1911 -0.67318 0.6699 -1.11836 1.6251 -1.16792 2.8172 -0.00147 0.0355 -0.00261 0.0712 -0.00338 0.107l0.00595 4.3776c-0.00004 0.4375 -0.35471 0.7923 -0.79223 0.7923H0.800818c-0.437489 -0.0001 -0.79218757 -0.3548 -0.79223241 -0.7923L0.00155074 15.846l-0.00002309 -0.0019c-0.001345827 -0.1115 -0.0026994 -0.2237 0.00015837 -0.3351C0.00168602 8.88179 5.37421 3.50928 12.0014 3.50928"/>
                                </svg>
                                <span>Notebook</span>
                            </a>

                            <!-- Botón Aplicar IA: EXCLUSIVO cuando se selecciona un curso específico -->
                            <button
                                v-if="selectedCourseId !== null"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-primary-strong hover:bg-primary-strong/90 text-white text-xs font-bold shadow-sm transition-all shrink-0 disabled:opacity-50 cursor-pointer"
                                :disabled="generatingAI || quotaRemaining <= 0"
                                @click="handleGenerateAI(selectedCourseId)"
                                :title="activeCourse ? `Aplicar IA para ${activeCourse.name}` : 'Aplicar IA'"
                            >
                                <Loader2 v-if="generatingAI" class="w-3.5 h-3.5 animate-spin" />
                                <Brain v-else class="w-3.5 h-3.5" />
                                <span>Aplicar IA ({{ quotaRemaining }}/5)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estado Vacío -->
                <div
                    v-if="filteredChunks.length === 0"
                    class="p-8 sm:p-12 text-center rounded-3xl bg-surface border border-dashed border-border space-y-3"
                >
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-primary-strong flex items-center justify-center mx-auto">
                        <Layers class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-bold text-content-primary">
                        {{ selectedCourseId === null ? 'Selecciona una asignatura' : 'No hay Chunks registrados' }}
                    </h3>
                    <p class="text-xs text-content-secondary max-w-md mx-auto">
                        {{ selectedCourseId === null 
                            ? 'Elige una de tus asignaturas arriba para ver sus cápsulas conceptuales o generar nuevo conocimiento con IA.' 
                            : `Genera con IA las tarjetas de estudio y mapa mental para ${activeCourse?.name || 'esta asignatura'}.` }}
                    </p>
                    <button
                        v-if="selectedCourseId !== null"
                        type="button"
                        class="px-4 py-2 rounded-xl bg-primary-strong text-white text-xs font-bold shadow-md hover:scale-105 transition-all cursor-pointer disabled:opacity-50"
                        :disabled="generatingAI || quotaRemaining <= 0"
                        @click="handleGenerateAI(selectedCourseId)"
                    >
                        <Loader2 v-if="generatingAI" class="w-3.5 h-3.5 animate-spin inline mr-1" />
                        <span>Aplicar IA ({{ quotaRemaining }}/5)</span>
                    </button>
                </div>

                <!-- Grid de Chunks Limpio (Sin Emojis ni Estrellas saturadas) -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="chunk in filteredChunks"
                        :key="chunk.id"
                        class="bg-surface rounded-2xl border border-border/80 hover:border-primary-strong/50 shadow-sm hover:shadow-md transition-all p-5 flex flex-col justify-between space-y-4 group"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-surface-raised border border-border text-content-secondary truncate max-w-[140px]">
                                    {{ chunk.course_name }}
                                </span>
                                <span v-if="chunk.category" class="text-[10px] text-content-muted font-medium">
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

                        <!-- 2 Botones Limpios: Estudiar vs Recall -->
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

            <!-- ── 3. VISTA 2: MAPA MENTAL POR CURSO (Fondo Blanco) ─────────────── -->
            <div v-else-if="viewMode === 'mindmap'" class="space-y-4 animate-fade-in">
                <!-- Selector de Curso para el Mapa Mental & Botón de IA del Curso -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-surface p-3.5 rounded-2xl border border-border/80">
                    <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
                        <button
                            v-for="c in courses"
                            :key="c.id"
                            type="button"
                            class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border"
                            :class="activeCourse.id === c.id ? 'bg-surface-raised border-primary-strong text-primary-strong shadow-sm' : 'bg-surface border-border text-content-secondary hover:text-content-primary'"
                            @click="selectedCourseId = c.id"
                        >
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: c.color || '#6366f1' }" />
                            <span>{{ c.name }}</span>
                        </button>
                    </div>

                    <!-- Botón Aplicar IA dedicado para este curso -->
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all shrink-0 disabled:opacity-50"
                        :disabled="generatingAI || quotaRemaining <= 0"
                        @click="handleGenerateAI(activeCourse.id)"
                    >
                        <Loader2 v-if="generatingAI" class="w-3.5 h-3.5 animate-spin" />
                        <Brain v-else class="w-3.5 h-3.5" />
                        <span>Aplicar IA ({{ quotaRemaining }}/5)</span>
                    </button>
                </div>

                <CourseMindMapView
                    :course="activeCourse"
                    :chunks="filteredChunks"
                    @studyChunk="openStudy"
                    @generateAi="handleGenerateAI(activeCourse.id)"
                />
            </div>

            <!-- ── 4. VISTA 3: SEGUNDO CEREBRO 3D (GALAXIA HOLOGRÁFICA) ─────────── -->
            <div v-else-if="viewMode === 'graph'" class="animate-fade-in">
                <KnowledgeGraphView
                    :show="true"
                    :courses="courses"
                    @close="viewMode = 'chunks'"
                    @openNote="openNoteForCourse"
                />
            </div>

            <!-- Modales -->
            <StudySummaryModal
                :show="showStudyModal"
                :chunk="activeChunk"
                :relatedChunks="activeChunkRelations"
                @close="showStudyModal = false"
                @openNote="openNoteForCourse"
                @startRecall="(c) => { showStudyModal = false; openRecall(c); }"
                @openRelated="(node) => { activeChunk = node; }"
            />

            <ActiveRecallModal
                :show="showRecallModal"
                :chunks="filteredChunks"
                :initial-index="selectedRecallIndex"
                @close="showRecallModal = false"
                @evaluated="handleRecallEvaluation"
            />

            <NoteEditorModal
                :show="showNoteModal"
                :course="activeNoteCourse"
                @close="showNoteModal = false"
                @openKnowledgeGraph="viewMode = 'graph'; showNoteModal = false"
            />
        </div>
    </AppLayout>
</template>
