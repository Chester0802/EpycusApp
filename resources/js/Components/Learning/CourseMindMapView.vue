<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import {
    ZoomIn,
    ZoomOut,
    Compass,
    Layers,
    Maximize2,
    Minimize2,
    ChevronDown,
    ChevronRight,
    Eye
} from '@lucide/vue';

const props = defineProps({
    course: { type: Object, required: true },
    chunks: { type: Array, default: () => [] },
});

const emit = defineEmits(['studyChunk', 'generateAi']);

// Estado de Contenedor & Fullscreen Nativo
const containerRef = ref(null);
const isFullscreen = ref(false);
const zoom = ref(1);
const pan = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Estado de Expansión Progresiva (Drill-Down)
// Por defecto, ramas abiertas para que el usuario las explore o colapse
const expandedBranches = ref(new Set());
const areAllExpanded = ref(true);

// Paleta Armónica por Rama
const BRANCH_PALETTE = [
    '#6366f1', // Índigo
    '#0ea5e9', // Cian
    '#10b981', // Esmeralda
    '#f59e0b', // Ámbar
    '#8b5cf6', // Púrpura
    '#ec4899', // Rosa
];

// Agrupar Chunks por Categoría / Rama Temática
const mindMapTree = computed(() => {
    if (!props.course) return null;

    const courseName = props.course.name || 'Asignatura';
    const categoriesMap = {};

    props.chunks.forEach(chunk => {
        const cat = chunk.category || 'Conceptos Centrales';
        if (!categoriesMap[cat]) {
            categoriesMap[cat] = [];
        }
        categoriesMap[cat].push(chunk);
    });

    const categoryKeys = Object.keys(categoriesMap);
    const branches = categoryKeys.map((catName, idx) => {
        return {
            id: `branch_${idx}`,
            name: catName,
            chunks: categoriesMap[catName],
            color: BRANCH_PALETTE[idx % BRANCH_PALETTE.length],
            side: idx % 2 === 0 ? 'right' : 'left',
        };
    });

    return {
        root: {
            label: courseName,
            color: props.course.color || '#4f46e5',
        },
        branches: branches,
    };
});

// Inicializar todas las ramas expandidas cuando cambie el curso
watch(
    () => props.course?.id,
    () => {
        if (mindMapTree.value) {
            expandedBranches.value = new Set(mindMapTree.value.branches.map(b => b.id));
            areAllExpanded.value = true;
        }
    },
    { immediate: true }
);

function toggleBranch(branchId) {
    if (expandedBranches.value.has(branchId)) {
        expandedBranches.value.delete(branchId);
    } else {
        expandedBranches.value.add(branchId);
    }
    areAllExpanded.value = expandedBranches.value.size === (mindMapTree.value?.branches.length || 0);
}

function toggleExpandAll() {
    if (areAllExpanded.value) {
        expandedBranches.value.clear();
        areAllExpanded.value = false;
    } else {
        expandedBranches.value = new Set(mindMapTree.value?.branches.map(b => b.id) || []);
        areAllExpanded.value = true;
    }
}

// Layout Progresivo Bi-Direccional (Calcula posiciones según ramas abiertas)
const layoutElements = computed(() => {
    if (!mindMapTree.value) return { nodes: [], links: [] };

    const nodes = [];
    const links = [];

    const rootX = 550;
    const rootY = 360;

    // 1. Nodo Raíz Central (El Curso)
    nodes.push({
        id: 'root',
        type: 'root',
        x: rootX,
        y: rootY,
        label: mindMapTree.value.root.label,
        color: mindMapTree.value.root.color,
    });

    const branches = mindMapTree.value.branches;
    const rightBranches = branches.filter(b => b.side === 'right');
    const leftBranches = branches.filter(b => b.side === 'left');

    function layoutSide(sideBranches, isRight) {
        const total = sideBranches.length;
        const spacingY = 180;
        const startY = rootY - ((total - 1) * spacingY) / 2;

        sideBranches.forEach((branch, bIdx) => {
            const branchX = isRight ? rootX + 230 : rootX - 230;
            const branchY = startY + bIdx * spacingY;
            const isExpanded = expandedBranches.value.has(branch.id);

            // Nodo de Rama
            nodes.push({
                id: branch.id,
                type: 'branch',
                x: branchX,
                y: branchY,
                label: branch.name,
                color: branch.color,
                isRight: isRight,
                isExpanded: isExpanded,
                chunksCount: branch.chunks.length,
            });

            // Enlace desde la Raíz
            links.push({
                id: `link_root_${branch.id}`,
                d: generateBezierCurve(rootX, rootY, branchX, branchY, isRight),
                color: branch.color,
                width: 2.5,
            });

            // Si está expandida, renderizar los chunks hijos
            if (isExpanded) {
                const totalChunks = branch.chunks.length;
                const chunkSpacingY = 52;
                const chunkStartY = branchY - ((totalChunks - 1) * chunkSpacingY) / 2;
                const chunkX = isRight ? branchX + 210 : branchX - 210;

                branch.chunks.forEach((chunk, cIdx) => {
                    const chunkY = chunkStartY + cIdx * chunkSpacingY;
                    const chunkNodeId = `chunk_${chunk.id}`;

                    nodes.push({
                        id: chunkNodeId,
                        type: 'chunk',
                        x: chunkX,
                        y: chunkY,
                        label: chunk.label,
                        color: branch.color,
                        rawChunk: chunk,
                        isRight: isRight,
                    });

                    // Enlace hacia el Chunk
                    links.push({
                        id: `link_${branch.id}_${chunkNodeId}`,
                        d: generateBezierCurve(branchX, branchY, chunkX, chunkY, isRight),
                        color: branch.color,
                        width: 1.5,
                    });
                });
            }
        });
    }

    layoutSide(rightBranches, true);
    layoutSide(leftBranches, false);

    return { nodes, links };
});

function generateBezierCurve(x1, y1, x2, y2, isRight) {
    const dx = Math.abs(x2 - x1) * 0.55;
    const cp1x = isRight ? x1 + dx : x1 - dx;
    const cp1y = y1;
    const cp2x = isRight ? x2 - dx : x2 + dx;
    const cp2y = y2;
    return `M ${x1} ${y1} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${x2} ${y2}`;
}

// Pan & Zoom
function handleMouseDown(e) {
    if (e.target.closest('.interactive-node')) return;
    isDragging.value = true;
    dragStart.value = { x: e.clientX - pan.value.x, y: e.clientY - pan.value.y };
}

function handleMouseMove(e) {
    if (!isDragging.value) return;
    pan.value = {
        x: e.clientX - dragStart.value.x,
        y: e.clientY - dragStart.value.y,
    };
}

function handleMouseUp() {
    isDragging.value = false;
}

function handleWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.92 : 1.08;
    zoom.value = Math.max(0.35, Math.min(2.2, zoom.value * delta));
}

function resetView() {
    zoom.value = 1;
    pan.value = { x: 0, y: 0 };
}

// Fullscreen Nativo del Navegador (100% Pantalla Real)
function toggleFullscreen() {
    if (!containerRef.value) return;

    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch(err => {
            console.error('Error al entrar a pantalla completa:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
        }).catch(err => {
            console.error('Error al salir de pantalla completa:', err);
        });
    }
}

function onFullscreenChange() {
    isFullscreen.value = !!document.fullscreenElement;
}

onMounted(() => {
    document.addEventListener('fullscreenchange', onFullscreenChange);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', onFullscreenChange);
});
</script>

<template>
    <div
        ref="containerRef"
        class="relative w-full h-[76vh] bg-slate-50 select-none flex flex-col rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden"
    >
        <!-- Header Flotante del Mapa Mental -->
        <div class="absolute top-4 left-4 z-20 flex items-center gap-3 bg-white/95 backdrop-blur-md px-4 py-2 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-3.5 h-3.5 rounded-full" :style="{ backgroundColor: course.color || '#4f46e5' }" />
            <div>
                <h3 class="text-sm font-bold text-slate-900 leading-tight">
                    Mapa Mental: {{ course.name }}
                </h3>
                <span class="text-[11px] text-slate-500 font-medium">
                    {{ chunks.length }} Conceptos estructurados en ramas interactivas
                </span>
            </div>
        </div>

        <!-- Controles Flotantes: Expandir Todo, Zoom y Pantalla Completa Real -->
        <div class="absolute top-4 right-4 z-20 flex items-center gap-1.5 bg-white/95 backdrop-blur-md p-1.5 rounded-2xl border border-slate-200 shadow-sm">
            <!-- Botón Expandir / Contraer Todo -->
            <button
                type="button"
                class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors flex items-center gap-1"
                @click="toggleExpandAll"
            >
                <Eye class="w-3.5 h-3.5 text-indigo-600" />
                <span>{{ areAllExpanded ? 'Contraer Todo' : 'Ver Mapa Completo' }}</span>
            </button>

            <span class="h-4 w-px bg-slate-200 mx-0.5" />

            <!-- Controles Zoom -->
            <button
                type="button"
                class="p-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                title="Acercar"
                @click="zoom = Math.min(2.2, zoom * 1.15)"
            >
                <ZoomIn class="w-4 h-4" />
            </button>
            <span class="text-[11px] font-bold text-slate-600 px-1 select-none">
                {{ Math.round(zoom * 100) }}%
            </span>
            <button
                type="button"
                class="p-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                title="Alejar"
                @click="zoom = Math.max(0.35, zoom / 1.15)"
            >
                <ZoomOut class="w-4 h-4" />
            </button>
            <button
                type="button"
                class="p-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                title="Centrar Vista"
                @click="resetView"
            >
                <Compass class="w-4 h-4 text-indigo-600" />
            </button>

            <span class="h-4 w-px bg-slate-200 mx-0.5" />

            <!-- Pantalla Completa Nativa -->
            <button
                type="button"
                class="p-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                :title="isFullscreen ? 'Salir de Pantalla Completa' : 'Pantalla Completa Real'"
                @click="toggleFullscreen"
            >
                <Minimize2 v-if="isFullscreen" class="w-4 h-4 text-indigo-600" />
                <Maximize2 v-else class="w-4 h-4" />
            </button>
        </div>

        <!-- Estado Vacío -->
        <div
            v-if="chunks.length === 0"
            class="m-auto text-center p-8 space-y-4 max-w-md z-10"
        >
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto shadow-inner">
                <Layers class="w-6 h-6" />
            </div>
            <h4 class="text-base font-bold text-slate-800">Sin apuntes en este curso</h4>
            <p class="text-xs text-slate-500">
                Escribe tus notas en <strong>{{ course.name }}</strong> antes de conectar con IA para construir tu mapa conceptual.
            </p>
            <button
                type="button"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all"
                @click="emit('generateAi')"
            >
                Generar con IA
            </button>
        </div>

        <!-- ── LIENZO SVG DEL MAPA MENTAL EXPANDIBLE ───────────────────────── -->
        <svg
            v-else
            class="w-full h-full cursor-grab active:cursor-grabbing bg-slate-50/70"
            @mousedown="handleMouseDown"
            @mousemove="handleMouseMove"
            @mouseup="handleMouseUp"
            @mouseleave="handleMouseUp"
            @wheel="handleWheel"
        >
            <!-- Patrón de fondo sutil -->
            <defs>
                <pattern id="grid-dots" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1" fill="#cbd5e1" opacity="0.45" />
                </pattern>
            </defs>

            <rect width="100%" height="100%" fill="url(#grid-dots)" />

            <g :transform="`translate(${pan.x}, ${pan.y}) scale(${zoom})`">
                
                <!-- 1. Enlaces Bézier Orgánicos -->
                <g class="links">
                    <path
                        v-for="link in layoutElements.links"
                        :key="link.id"
                        :d="link.d"
                        fill="none"
                        :stroke="link.color"
                        :stroke-width="link.width"
                        stroke-linecap="round"
                        stroke-opacity="0.4"
                    />
                </g>

                <!-- 2. Nodos del Árbol -->
                <g class="nodes">
                    
                    <!-- 2.1. Nodo Raíz Central (Curso) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'root')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                        class="interactive-node cursor-pointer"
                        @click="toggleExpandAll"
                    >
                        <rect
                            x="-90"
                            y="-24"
                            width="180"
                            height="48"
                            rx="24"
                            fill="#ffffff"
                            :stroke="node.color"
                            stroke-width="3"
                            class="shadow-lg hover:scale-105 transition-transform"
                        />
                        <text
                            text-anchor="middle"
                            dy="5"
                            :fill="node.color"
                            font-size="13"
                            font-weight="900"
                            class="select-none font-sans"
                        >
                            {{ node.label.length > 20 ? node.label.substring(0, 18) + '...' : node.label }}
                        </text>
                    </g>

                    <!-- 2.2. Nodos de Rama Temática (Clic para desplegar sus Chunks) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'branch')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                        class="interactive-node cursor-pointer group"
                        @click="toggleBranch(node.id)"
                    >
                        <rect
                            x="-75"
                            y="-18"
                            width="150"
                            height="36"
                            rx="18"
                            fill="#ffffff"
                            :stroke="node.color"
                            stroke-width="2"
                            class="group-hover:shadow-md transition-all group-hover:scale-105"
                        />
                        
                        <!-- Indicador de Expansión (+ / -) -->
                        <circle
                            :cx="node.isRight ? 60 : -60"
                            cy="0"
                            r="8"
                            :fill="node.color"
                        />
                        <text
                            :x="node.isRight ? 60 : -60"
                            dy="3.5"
                            text-anchor="middle"
                            fill="#ffffff"
                            font-size="10"
                            font-weight="bold"
                            class="select-none"
                        >
                            {{ node.isExpanded ? '−' : '+' }}
                        </text>

                        <!-- Nombre del Eje Temático -->
                        <text
                            :x="node.isRight ? -8 : 8"
                            text-anchor="middle"
                            dy="4"
                            fill="#1e293b"
                            font-size="10.5"
                            font-weight="700"
                            class="select-none font-sans"
                        >
                            {{ node.label.length > 16 ? node.label.substring(0, 14) + '...' : node.label }}
                        </text>
                    </g>

                    <!-- 2.3. Nodos Hijos (Conceptos / Chunks Clave - Clic para Estudiar) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'chunk')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                        class="interactive-node cursor-pointer group"
                        @click="emit('studyChunk', node.rawChunk)"
                    >
                        <rect
                            x="-85"
                            y="-16"
                            width="170"
                            height="32"
                            rx="10"
                            fill="#ffffff"
                            stroke="#e2e8f0"
                            stroke-width="1.2"
                            class="group-hover:stroke-indigo-500 group-hover:shadow-md transition-all"
                        />
                        
                        <!-- Barra de Acento Izquierda -->
                        <rect
                            :x="-85"
                            y="-16"
                            width="4"
                            height="32"
                            rx="2"
                            :fill="node.color"
                        />

                        <!-- Título del Concepto (Limpio, Sin Porcentajes) -->
                        <text
                            :x="-72"
                            dy="4"
                            fill="#0f172a"
                            font-size="10.5"
                            font-weight="600"
                            class="select-none font-sans group-hover:fill-indigo-600 transition-colors"
                        >
                            {{ node.label.length > 20 ? node.label.substring(0, 18) + '...' : node.label }}
                        </text>
                    </g>
                </g>
            </g>
        </svg>

        <!-- Leyenda Inferior -->
        <div class="absolute bottom-4 left-4 z-20 hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200 text-xs text-slate-500 shadow-sm">
            <span>Toca una rama (+ / −) para abrir sus conceptos • Clic en un concepto para estudiarlo</span>
        </div>
    </div>
</template>
