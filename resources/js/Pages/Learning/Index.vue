<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import KnowledgeGraphView from '@/Components/Calendar/KnowledgeGraphView.vue';
import NoteEditorModal from '@/Components/Calendar/NoteEditorModal.vue';
import {
    Brain,
    Layers,
    Sparkles,
    Search,
    BookOpen,
    Network,
    Zap,
    CheckCircle2,
    HelpCircle,
    ArrowLeft,
    ArrowUpRight,
    Copy,
    Check,
    ExternalLink,
    Flame,
    Clock,
    Target,
    RotateCcw,
    Loader2,
    Activity,
    Plus,
    X,
    TrendingUp,
    ChevronRight,
    Star
} from '@lucide/vue';

const props = defineProps({
    courses: { type: Array, default: () => [] },
    graphData: { type: Object, default: () => ({ has_graph: false, nodes: [], edges: [], stats: {}, quota: {} }) },
    learningStats: { type: Object, default: () => ({ avgMastery: 70, totalChunks: 0, weakChunksCount: 0, streakDays: 4 }) },
});

// ── Estado de Vista ─────────────────────────────────────────────────────────
const viewMode = ref('chunks'); // 'chunks' | 'graph'
const selectedCourseId = ref(null);
const searchQuery = ref('');
const selectedChunk = ref(null); // Chunk activo en el Visor Inmersivo
const showQuizAnswer = ref(false);
const isCopyingForNotebookLM = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
const generatingMission = ref(false);
const generatingAI = ref(false);

// Modal de Apuntes
const showNoteModal = ref(false);
const activeNoteCourse = ref(null);

// Grafo reactivo local
const localGraphData = ref({ ...props.graphData });
const localNodes = computed(() => localGraphData.value.nodes || []);
const localEdges = computed(() => localGraphData.value.edges || []);

async function handleGenerateAI() {
    generatingAI.value = true;
    try {
        const res = await axios.post(route('calendar.knowledge-graph.generate'), {});
        if (res.data.success) {
            localGraphData.value.nodes = res.data.nodes || [];
            localGraphData.value.edges = res.data.edges || [];
            localGraphData.value.stats = res.data.stats || {};
            if (res.data.quota) {
                localGraphData.value.quota = res.data.quota;
            }
            triggerToast('¡Chunks y Grafo generados exitosamente con IA! ✨', 'success');
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'Error al conectar con IA';
        triggerToast(msg, 'error');
    } finally {
        generatingAI.value = false;
    }
}

// ── Filtros y Chunks ────────────────────────────────────────────────────────
const filteredChunks = computed(() => {
    let list = localNodes.value;
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

const courseChunkCounts = computed(() => {
    const counts = {};
    localNodes.value.forEach(n => {
        if (n.course_id) {
            counts[n.course_id] = (counts[n.course_id] || 0) + 1;
        }
    });
    return counts;
});

// Chunks críticos para la alerta de gamificación
const weakChunks = computed(() => {
    return localNodes.value.filter(n => (n.mastery || 70) < 60);
});

// Conexiones del chunk seleccionado
const selectedChunkRelations = computed(() => {
    if (!selectedChunk.value) return [];
    const chunkId = selectedChunk.value.id;
    const related = [];

    localEdges.value.forEach(e => {
        if (e.source === chunkId || e.source?.id === chunkId) {
            const targetId = e.target?.id || e.target;
            const targetNode = localNodes.value.find(n => n.id === targetId);
            if (targetNode) {
                related.push({ node: targetNode, label: e.label || 'se relaciona con', type: e.type || 'aplicacion' });
            }
        } else if (e.target === chunkId || e.target?.id === chunkId) {
            const sourceId = e.source?.id || e.source;
            const sourceNode = localNodes.value.find(n => n.id === sourceId);
            if (sourceNode) {
                related.push({ node: sourceNode, label: e.label || 'conecta con', type: e.type || 'requisito' });
            }
        }
    });

    return related;
});

// ── Métodos ─────────────────────────────────────────────────────────────────
function openInmersiveChunk(chunk, focusOnQuiz = false) {
    selectedChunk.value = chunk;
    showQuizAnswer.value = false;
    if (focusOnQuiz) {
        // Auto-scroll a la pregunta de Active Recall si se solicitó
        setTimeout(() => {
            const el = document.getElementById('active-recall-section');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        }, 100);
    }
}

function closeInmersiveChunk() {
    selectedChunk.value = null;
    showQuizAnswer.value = false;
}

function viewInGraph(chunk) {
    selectedChunk.value = null;
    viewMode.value = 'graph';
    // Se activa el modo grafo y el componente KnowledgeGraphView seleccionará el nodo
}

function openNoteForCourse(courseId) {
    const course = props.courses.find(c => c.id === courseId);
    if (course) {
        activeNoteCourse.value = course;
        showNoteModal.value = true;
    }
}

// Actualizar dominio activo (Active Recall)
async function evaluateMastery(delta) {
    if (!selectedChunk.value) return;
    const chunkId = selectedChunk.value.id;

    try {
        const res = await axios.post(route('learning.chunk.mastery'), {
            node_id: chunkId,
            delta: delta
        });

        if (res.data.success) {
            const newMastery = res.data.mastery;
            selectedChunk.value.mastery = newMastery;
            selectedChunk.value.last_reviewed_at = 'Hoy';

            // Actualizar en el estado local de nodos
            const target = localGraphData.value.nodes.find(n => n.id === chunkId);
            if (target) {
                target.mastery = newMastery;
                target.last_reviewed_at = 'Hoy';
            }

            triggerToast(delta > 0 ? `¡Dominio aumentado a ${newMastery}%! 🧠` : `Ajustado a ${newMastery}% para reforzar`, 'success');
        }
    } catch (e) {
        console.error('Error al actualizar dominio:', e);
    }
}

// Auto-generar misión de refuerzo con Pomodoro
async function generateSmartMission(chunk = null) {
    const target = chunk || weakChunks.value[0] || selectedChunk.value;
    if (!target) return;

    generatingMission.value = true;
    try {
        const res = await axios.post(route('learning.generate-mission'), {
            node_label: target.label,
            course_id: target.course_id,
            summary: target.summary
        });

        if (res.data.success) {
            triggerToast(res.data.message, 'success');
            // Opcional: Navegar a misiones o iniciar pomodoro
            setTimeout(() => {
                router.visit(route('missions.index'));
            }, 1200);
        }
    } catch (e) {
        triggerToast('No se pudo crear la misión automáticamente', 'error');
    } finally {
        generatingMission.value = false;
    }
}

// Copiar Markdown formateado para Google NotebookLM
function copyForNotebookLM(courseId = null) {
    const targetCourseId = courseId || selectedCourseId.value;
    let text = '';

    if (targetCourseId) {
        const course = props.courses.find(c => c.id === targetCourseId);
        const courseName = course?.name || 'Curso';
        const courseNodes = localNodes.value.filter(n => n.course_id === targetCourseId);

        text += `# 🧠 Apuntes & Chunks de Aprendizaje — ${courseName}\n`;
        text += `> Fuente estructurada para Google NotebookLM generada desde Epycus 2.0\n\n`;

        if (course?.notes_content) {
            text += `## 📝 Apuntes Oficiales\n${course.notes_content}\n\n`;
        }

        if (courseNodes.length > 0) {
            text += `## 🧩 Conceptos Clave & Chunks (Segundo Cerebro)\n\n`;
            courseNodes.forEach((n, idx) => {
                text += `### ${idx + 1}. ${n.label}\n`;
                text += `- **Idea Clave:** ${n.summary || 'Sin resumen'}\n`;
                if (n.why_it_matters) text += `- **Aplicación Profesional:** ${n.why_it_matters}\n`;
                if (n.quiz_question) {
                    text += `- **Active Recall:** ${n.quiz_question}\n`;
                    text += `  - *Respuesta:* ${n.quiz_answer || n.summary}\n`;
                }
                text += `\n`;
            });
        }
    } else {
        text += `# 🧠 Ecosistema de Aprendizaje & Segundo Cerebro — Epycus\n`;
        text += `> Resumen global de todas las asignaturas para Google NotebookLM\n\n`;
        props.courses.forEach(c => {
            text += `## 📚 ${c.name}\n`;
            const courseNodes = localNodes.value.filter(n => n.course_id === c.id);
            if (courseNodes.length > 0) {
                courseNodes.forEach(n => {
                    text += `- **${n.label}:** ${n.summary}\n`;
                    if (n.why_it_matters) text += `  - *Aplicación:* ${n.why_it_matters}\n`;
                });
            }
            text += `\n`;
        });
    }

    navigator.clipboard.writeText(text).then(() => {
        isCopyingForNotebookLM.value = true;
        triggerToast('¡Chunks copiados en Markdown listos para pegar en NotebookLM!', 'success');
        setTimeout(() => { isCopyingForNotebookLM.value = false; }, 3500);
    }).catch(() => {
        triggerToast('Error al copiar al portapapeles', 'error');
    });
}

function triggerToast(msg, type = 'success') {
    toastMessage.value = msg;
    toastType.value = type;
    setTimeout(() => { toastMessage.value = ''; }, 4000);
}

// Helpers de Color & Dominio
function getMasteryColor(mastery) {
    if (mastery >= 75) return 'bg-emerald-500 text-emerald-700';
    if (mastery >= 50) return 'bg-amber-500 text-amber-700';
    return 'bg-rose-500 text-rose-700';
}

function getMasteryBadgeClass(mastery) {
    if (mastery >= 75) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    if (mastery >= 50) return 'bg-amber-50 text-amber-700 border-amber-200';
    return 'bg-rose-50 text-rose-700 border-rose-200';
}
</script>

<template>
    <AppLayout>
        <Head title="Zona de Aprendizaje & Segundo Cerebro — Epycus" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 animate-fade-in">
            
            <!-- ── 1. HEADER DE LA ZONA DE APRENDIZAJE ───────────────────────── -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface rounded-2xl p-5 border border-border/80 shadow-sm">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-600 shadow-md">
                        <Brain class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-content-primary tracking-tight">
                            Zona de Aprendizaje
                        </h1>
                        <p class="text-xs text-content-secondary mt-0.5">
                            Asimila tus cursos universitarios mediante micro-cápsulas (*Chunking*), autoevaluación activa y mapa conceptual.
                        </p>
                    </div>
                </div>

                <!-- Selector de Modo de Aprendizaje & Acciones IA -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Toggle Modos -->
                    <div class="inline-flex p-1 rounded-xl bg-surface-raised border border-border shadow-inner" role="tablist">
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="viewMode === 'chunks'"
                            class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="viewMode === 'chunks'
                                ? 'bg-primary-strong text-white shadow-md'
                                : 'text-content-secondary hover:text-content-primary'"
                            @click="viewMode = 'chunks'; selectedChunk = null"
                        >
                            <Layers class="w-3.5 h-3.5" />
                            <span>Deck de Chunks</span>
                        </button>
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="viewMode === 'graph'"
                            class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="viewMode === 'graph'
                                ? 'bg-purple-600 text-white shadow-md'
                                : 'text-content-secondary hover:text-content-primary'"
                            @click="viewMode = 'graph'"
                        >
                            <Network class="w-3.5 h-3.5" />
                            <span>Segundo Cerebro (Grafo)</span>
                        </button>
                    </div>

                    <!-- Botón Conectar con IA (Genera Chunks y Grafo) -->
                    <button
                        type="button"
                        :disabled="generatingAI || (graphData.quota && graphData.quota.is_exhausted)"
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-md select-none disabled:opacity-50"
                        :class="graphData.quota && graphData.quota.is_exhausted
                            ? 'bg-slate-800 border border-slate-700 text-slate-400 cursor-not-allowed'
                            : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-indigo-500/20 hover:scale-105 active:scale-95'"
                        @click="handleGenerateAI"
                    >
                        <Loader2 v-if="generatingAI" class="w-3.5 h-3.5 animate-spin" />
                        <Sparkles v-else class="w-3.5 h-3.5 text-yellow-300" />
                        <span>{{ generatingAI ? 'Generando...' : 'Conectar con IA' }}</span>
                        <span v-if="graphData.quota && graphData.quota.remaining !== undefined" class="px-1.5 py-0.5 text-[9px] font-black rounded bg-black/30 text-indigo-100">
                            {{ graphData.quota.remaining }}/{{ graphData.quota.max_quota || 5 }}
                        </span>
                    </button>

                    <!-- Botón NotebookLM Quick Link -->
                    <a
                        href="https://notebooklm.google.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 shadow-sm transition-all"
                        title="Abrir Google NotebookLM"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="#E8F0FE"/>
                            <path d="M7 6.5h10a1.5 1.5 0 0 1 1.5 1.5v8a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 16V8A1.5 1.5 0 0 1 7 6.5z" fill="#1A73E8"/>
                            <path d="M8.5 9.5h7M8.5 12h5M8.5 14.5h4" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                            <circle cx="16.5" cy="14.5" r="1" fill="#34A853"/>
                        </svg>
                        <span>NotebookLM</span>
                        <ExternalLink class="w-3 h-3 text-blue-500" />
                    </a>
                </div>
            </div>

            <!-- ── 2. BARRA DE MÉTRICAS PEDAGÓGICAS (ANTI-FATIGA) ─────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                <!-- 1. Dominio Global -->
                <div class="bg-surface p-4 rounded-2xl border border-border/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-content-secondary font-semibold mb-2">
                        <span>Dominio Global</span>
                        <Brain class="w-4 h-4 text-indigo-500" />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-content-primary">{{ learningStats.avgMastery || 70 }}%</span>
                        <span class="text-[10px] font-bold text-emerald-600">Promedio</span>
                    </div>
                    <!-- Barra de progreso -->
                    <div class="w-full h-1.5 bg-surface-sunken rounded-full overflow-hidden mt-2">
                        <div
                            class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full"
                            :style="{ width: `${learningStats.avgMastery || 70}%` }"
                        />
                    </div>
                </div>

                <!-- 2. Chunks Atómicos -->
                <div class="bg-surface p-4 rounded-2xl border border-border/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-content-secondary font-semibold mb-2">
                        <span>Cápsulas / Chunks</span>
                        <Layers class="w-4 h-4 text-emerald-500" />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-content-primary">{{ localNodes.length }}</span>
                        <span class="text-[10px] font-bold text-content-muted">Conceptos</span>
                    </div>
                    <p class="text-[10px] text-content-muted mt-2">Extraídos de tus apuntes</p>
                </div>

                <!-- 3. Conceptos a Reforzar -->
                <div class="bg-surface p-4 rounded-2xl border border-border/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-content-secondary font-semibold mb-2">
                        <span>Por Reforzar</span>
                        <Flame class="w-4 h-4 text-rose-500" />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-rose-600">{{ weakChunks.length }}</span>
                        <span class="text-[10px] font-bold text-rose-500">&lt;60% Dominio</span>
                    </div>
                    <p class="text-[10px] text-content-muted mt-2">Requieren Active Recall</p>
                </div>

                <!-- 4. Sinergia NotebookLM -->
                <div class="bg-surface p-4 rounded-2xl border border-border/80 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-content-secondary font-semibold mb-2">
                        <span>Exportar a Fuentes</span>
                        <Copy class="w-4 h-4 text-blue-500" />
                    </div>
                    <button
                        type="button"
                        class="w-full py-1.5 px-2 rounded-xl bg-surface-raised hover:bg-surface-sunken border border-border text-xs font-bold text-content-primary transition-all flex items-center justify-center gap-1.5"
                        @click="copyForNotebookLM()"
                    >
                        <Check v-if="isCopyingForNotebookLM" class="w-3.5 h-3.5 text-emerald-600" />
                        <Copy v-else class="w-3.5 h-3.5 text-content-muted" />
                        <span>{{ isCopyingForNotebookLM ? '¡Copiado!' : 'Copiar Chunks' }}</span>
                    </button>
                    <p class="text-[10px] text-content-muted mt-1 text-center">Formato Markdown limpio</p>
                </div>
            </div>

            <!-- ── 3. BANNER DE ALERTA GAMIFICADA: GENERAR MISIÓN DE REFUERZO ─── -->
            <div
                v-if="weakChunks.length > 0 && !selectedChunk && viewMode === 'chunks'"
                class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-indigo-500/10 border border-amber-200/80 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-amber-500/20 text-amber-700 shrink-0">
                        <Target class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-content-primary">
                            🎯 Refuerzo Sugerido: Detectamos {{ weakChunks.length }} conceptos con dominio bajo
                        </h4>
                        <p class="text-[11px] text-content-secondary">
                            Concepto prioritario: <strong>«{{ weakChunks[0]?.label }}»</strong> en <em>{{ weakChunks[0]?.course_name }}</em>.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    :disabled="generatingMission"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-rose-600 hover:from-amber-500 hover:to-rose-500 text-white text-xs font-bold shadow-md hover:scale-105 active:scale-95 transition-all flex items-center gap-1.5 shrink-0 disabled:opacity-50"
                    @click="generateSmartMission(weakChunks[0])"
                >
                    <Loader2 v-if="generatingMission" class="w-3.5 h-3.5 animate-spin" />
                    <Sparkles v-else class="w-3.5 h-3.5" />
                    <span>Crear Misión Pomodoro (15 min) 🚀</span>
                </button>
            </div>

            <!-- Toast Flotante -->
            <div v-if="toastMessage" class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50 px-4 py-2 rounded-xl text-xs font-bold shadow-2xl backdrop-blur-md animate-fade-in flex items-center gap-2"
                :class="toastType === 'error' ? 'bg-rose-500 text-white' : 'bg-slate-900 text-white border border-slate-700'"
            >
                <span>{{ toastMessage }}</span>
            </div>

            <!-- ── 4. VISTA: DECK VISUAL DE CHUNKS ────────────────────────────── -->
            <div v-if="viewMode === 'chunks'" class="space-y-6">

                <!-- ── VISOR INMERSIVO DEL CHUNK (CUANDO SE ENTRA A ESTUDIAR) ─── -->
                <div
                    v-if="selectedChunk"
                    class="bg-surface rounded-3xl border border-border shadow-xl p-5 sm:p-8 animate-fade-in space-y-6 max-w-4xl mx-auto"
                >
                    <!-- Barra Superior del Visor -->
                    <div class="flex items-center justify-between pb-4 border-b border-border/80 gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-raised hover:bg-surface-sunken border border-border text-xs font-bold text-content-secondary hover:text-content-primary transition-all"
                            @click="closeInmersiveChunk"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            <span>Volver a Chunks</span>
                        </button>

                        <div class="flex items-center gap-2">
                            <span class="text-xs text-content-muted font-semibold">Dominio:</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black border" :class="getMasteryBadgeClass(selectedChunk.mastery || 70)">
                                {{ selectedChunk.mastery || 70 }}%
                            </span>
                        </div>
                    </div>

                    <!-- Encabezado del Chunk -->
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ selectedChunk.course_name }}
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-content-primary mt-2">
                            🧩 {{ selectedChunk.label }}
                        </h2>
                    </div>

                    <!-- 💡 IDEA CLAVE -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                            Idea Clave
                        </span>
                        <p class="text-sm text-slate-800 leading-relaxed font-medium">
                            {{ selectedChunk.summary || 'Concepto fundamental extraído de tus apuntes oficiales del curso.' }}
                        </p>
                    </div>

                    <!-- ❓ COMPRUEBA TU RECUERDO (ACTIVE RECALL) -->
                    <div id="active-recall-section" class="p-5 sm:p-6 rounded-2xl bg-rose-50/70 border border-rose-200/90 space-y-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-rose-700 flex items-center gap-1.5">
                                <HelpCircle class="w-4 h-4 text-rose-600" />
                                Comprueba tu Recuerdo (Active Recall)
                            </span>
                        </div>

                        <p class="text-base sm:text-lg font-bold text-rose-950 leading-snug">
                            {{ selectedChunk.quiz_question || `¿Cuál es el rol o definición central de ${selectedChunk.label}?` }}
                        </p>

                        <!-- Respuesta Oculta / Revelada -->
                        <div v-if="showQuizAnswer" class="space-y-4 animate-fade-in">
                            <div class="p-4 rounded-xl bg-white border border-rose-200 text-sm text-rose-950 font-normal leading-relaxed shadow-sm">
                                <strong class="text-rose-700 font-bold">Respuesta Clave:</strong> {{ selectedChunk.quiz_answer || selectedChunk.summary }}
                            </div>

                            <!-- Autoevaluación de Dominio -->
                            <div class="space-y-2">
                                <p class="text-xs font-bold text-rose-900">¿Cómo fue tu retención?</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        class="py-2.5 px-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-sm active:scale-95"
                                        @click="evaluateMastery(-10)"
                                    >
                                        🔴 Difícil (-10%)
                                    </button>
                                    <button
                                        type="button"
                                        class="py-2.5 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-all shadow-sm active:scale-95"
                                        @click="evaluateMastery(5)"
                                    >
                                        🟡 Regular (+5%)
                                    </button>
                                    <button
                                        type="button"
                                        class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-sm active:scale-95"
                                        @click="evaluateMastery(15)"
                                    >
                                        🟢 Fácil (+15%)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            v-else
                            type="button"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-md hover:scale-105 active:scale-95 flex items-center gap-2"
                            @click="showQuizAnswer = true"
                        >
                            <span>Mostrar Respuesta</span>
                        </button>
                    </div>

                    <!-- 🔗 SE RELACIONA CON (Navegables con 1 clic) -->
                    <div v-if="selectedChunkRelations.length > 0" class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-content-muted flex items-center gap-1.5">
                            <Network class="w-4 h-4 text-indigo-500" />
                            Conceptos Relacionados
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(rel, idx) in selectedChunkRelations"
                                :key="idx"
                                type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-raised hover:bg-indigo-50 hover:border-indigo-300 border border-border text-xs font-bold text-content-primary hover:text-indigo-700 transition-all shadow-sm group"
                                @click="openInmersiveChunk(rel.node)"
                            >
                                <span class="text-[10px] text-content-muted font-normal">[{{ rel.label }}]</span>
                                <span>{{ rel.node.label }}</span>
                                <ArrowUpRight class="w-3.5 h-3.5 text-content-muted group-hover:text-indigo-600 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                            </button>
                        </div>
                    </div>

                    <!-- 🎯 ACCIONES FINALES -->
                    <div class="pt-4 border-t border-border/80 flex flex-wrap items-center justify-between gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl bg-surface-raised hover:bg-surface-sunken border border-border text-xs font-bold text-content-primary transition-all flex items-center gap-1.5"
                            @click="openNoteForCourse(selectedChunk.course_id)"
                        >
                            <BookOpen class="w-3.5 h-3.5 text-indigo-500" />
                            <span>Abrir Apuntes del Curso</span>
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl bg-surface-raised hover:bg-surface-sunken border border-border text-xs font-bold text-content-primary transition-all flex items-center gap-1.5"
                            @click="copyForNotebookLM(selectedChunk.course_id)"
                        >
                            <Copy class="w-3.5 h-3.5 text-blue-500" />
                            <span>Copiar para NotebookLM</span>
                        </button>
                    </div>
                </div>

                <!-- ── CUADRÍCULA DE TARJETAS DE CHUNK (DECK PRINCIPAL) ───────── -->
                <div v-else class="space-y-4">
                    
                    <!-- Barra de Filtro por Cursos & Buscador -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-surface p-3.5 rounded-2xl border border-border/80">
                        <!-- Filtros Cursos -->
                        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0"
                                :class="selectedCourseId === null
                                    ? 'bg-primary-strong text-white shadow-sm'
                                    : 'bg-surface-raised text-content-secondary hover:text-content-primary border border-border'"
                                @click="selectedCourseId = null"
                            >
                                Todos ({{ localNodes.length }})
                            </button>

                            <button
                                v-for="c in courses"
                                :key="c.id"
                                type="button"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border"
                                :class="selectedCourseId === c.id
                                    ? 'bg-surface-raised border-primary-strong text-primary-strong shadow-sm'
                                    : 'bg-surface-raised/60 border-border text-content-secondary hover:text-content-primary'"
                                @click="selectedCourseId = c.id"
                            >
                                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: c.color || '#6366f1' }" />
                                <span>{{ c.name }}</span>
                                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-surface-sunken text-content-muted">
                                    {{ courseChunkCounts[c.id] || 0 }}
                                </span>
                            </button>
                        </div>

                        <!-- Buscador -->
                        <div class="relative w-full sm:w-64 shrink-0">
                            <Search class="w-3.5 h-3.5 absolute left-3 top-3 text-content-muted" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar cápsula de estudio..."
                                class="w-full pl-9 pr-3 py-1.5 bg-surface-sunken border border-border rounded-xl text-xs text-content-primary placeholder-content-muted focus:outline-none focus:border-primary-strong"
                            />
                        </div>
                    </div>

                    <!-- Estado Vacío -->
                    <div
                        v-if="filteredChunks.length === 0"
                        class="p-12 text-center rounded-3xl bg-surface border border-dashed border-border space-y-3"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto">
                            <Layers class="w-6 h-6" />
                        </div>
                        <h3 class="text-base font-bold text-content-primary">No hay Chunks registrados</h3>
                        <p class="text-xs text-content-secondary max-w-md mx-auto">
                            Conecta tus apuntes universitarios con IA en el Segundo Cerebro para generar automáticamente tus tarjetas de estudio.
                        </p>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl bg-primary-strong text-white text-xs font-bold shadow-md hover:scale-105 transition-all"
                            @click="viewMode = 'graph'"
                        >
                            Ir al Grafo de Conocimiento ✨
                        </button>
                    </div>

                    <!-- Cuadrícula de Chunks con Peso & Métricas -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="chunk in filteredChunks"
                            :key="chunk.id"
                            class="bg-surface rounded-2xl border border-border/80 hover:border-primary-strong/50 shadow-sm hover:shadow-md transition-all p-5 flex flex-col justify-between space-y-4 group"
                        >
                            <!-- Header de la Tarjeta -->
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-surface-raised border border-border text-content-secondary truncate max-w-[140px]">
                                        {{ chunk.course_name }}
                                    </span>
                                    <div class="flex items-center gap-0.5 text-amber-500 text-xs">
                                        <Star v-for="s in (chunk.importance || 4)" :key="s" class="w-3 h-3 fill-amber-400 text-amber-400" />
                                    </div>
                                </div>

                                <h3 class="text-base font-bold text-content-primary group-hover:text-primary-strong transition-colors line-clamp-1">
                                    🧩 {{ chunk.label }}
                                </h3>

                                <p class="text-xs text-content-secondary mt-1.5 line-clamp-2 leading-relaxed font-normal">
                                    {{ chunk.summary || 'Concepto clave registrado en tus notas.' }}
                                </p>
                            </div>

                            <!-- Métricas & Peso del Chunk -->
                            <div class="space-y-2 pt-2 border-t border-border/60">
                                <!-- Barra de Dominio -->
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-[11px] font-bold text-content-secondary">Dominio</span>
                                    <span class="text-xs font-black" :class="getMasteryColor(chunk.mastery || 70)">
                                        {{ chunk.mastery || 70 }}%
                                    </span>
                                </div>
                                <div class="w-full h-1.5 bg-surface-sunken rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="getMasteryColor(chunk.mastery || 70)"
                                        :style="{ width: `${chunk.mastery || 70}%` }"
                                    />
                                </div>

                                <!-- Metadatos (Conexiones, Preguntas, Último Estudio) -->
                                <div class="flex items-center justify-between text-[10px] text-content-muted pt-1">
                                    <span class="flex items-center gap-1">
                                        <Network class="w-3 h-3 text-indigo-400" />
                                        {{ chunk.connections_count || 3 }} conexiones
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <HelpCircle class="w-3 h-3 text-rose-400" />
                                        1 pregunta
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Clock class="w-3 h-3 text-slate-400" />
                                        {{ chunk.last_reviewed_at || 'Hace 2d' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="grid grid-cols-2 gap-2 pt-2">
                                <button
                                    type="button"
                                    class="py-2 px-3 rounded-xl bg-primary-strong hover:bg-primary-strong/90 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95"
                                    @click="openInmersiveChunk(chunk)"
                                >
                                    <BookOpen class="w-3.5 h-3.5" />
                                    <span>Estudiar</span>
                                </button>
                                <button
                                    type="button"
                                    class="py-2 px-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-all flex items-center justify-center gap-1.5 active:scale-95"
                                    @click="openInmersiveChunk(chunk, true)"
                                >
                                    <HelpCircle class="w-3.5 h-3.5" />
                                    <span>Recall</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── 5. VISTA: SEGUNDO CEREBRO (GRAFO DE CONOCIMIENTO EMBEBIDO) ── -->
            <div v-else-if="viewMode === 'graph'" class="h-[78vh] rounded-3xl overflow-hidden shadow-xl border border-border relative animate-fade-in">
                <KnowledgeGraphView
                    :show="true"
                    :courses="courses"
                    @close="viewMode = 'chunks'"
                    @openNote="openNoteForCourse"
                />
            </div>

            <!-- Modal de Apuntes Oficiales -->
            <NoteEditorModal
                :show="showNoteModal"
                :course="activeNoteCourse"
                @close="showNoteModal = false"
                @openKnowledgeGraph="viewMode = 'graph'; showNoteModal = false"
            />
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.25);
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.45);
}
</style>
