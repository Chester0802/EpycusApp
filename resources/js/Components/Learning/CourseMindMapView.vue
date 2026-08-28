<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
    ZoomIn,
    ZoomOut,
    Compass,
    BookOpen,
    HelpCircle,
    Layers,
    Sparkles,
    CheckCircle2
} from '@lucide/vue';

const props = defineProps({
    course: { type: Object, required: true },
    chunks: { type: Array, default: () => [] },
});

const emit = defineEmits(['studyChunk', 'recallChunk', 'generateAi']);

// Estado Canvas SVG
const svgContainer = ref(null);
const zoom = ref(1);
const pan = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });

// Agrupar Chunks por Categoría / Rama Temática
const mindMapTree = computed(() => {
    if (!props.course) return null;

    const courseName = props.course.name || 'Asignatura';
    const categoriesMap = {};

    props.chunks.forEach(chunk => {
        const cat = chunk.category || 'General';
        if (!categoriesMap[cat]) {
            categoriesMap[cat] = [];
        }
        categoriesMap[cat].push(chunk);
    });

    const branches = Object.keys(categoriesMap).map((catName, branchIdx) => {
        return {
            name: catName,
            chunks: categoriesMap[catName],
            index: branchIdx,
        };
    });

    return {
        root: {
            label: courseName,
            color: props.course.color || '#6366f1',
        },
        branches: branches,
    };
});

// Layout Radial / Árbol Calculado
const layoutElements = computed(() => {
    if (!mindMapTree.value) return { nodes: [], links: [] };

    const nodes = [];
    const links = [];

    const rootX = 450;
    const rootY = 320;

    // Nodo Raíz (Curso)
    nodes.push({
        id: 'root',
        type: 'root',
        x: rootX,
        y: rootY,
        label: mindMapTree.value.root.label,
        color: mindMapTree.value.root.color,
    });

    const branches = mindMapTree.value.branches;
    const totalBranches = branches.length || 1;

    branches.forEach((branch, bIdx) => {
        // Distribuir ramas en abanico
        const angle = (bIdx / totalBranches) * 2 * Math.PI - Math.PI / 2;
        const branchDist = 180;
        const branchX = rootX + Math.cos(angle) * branchDist;
        const branchY = rootY + Math.sin(angle) * branchDist;
        const branchId = `branch_${bIdx}`;

        nodes.push({
            id: branchId,
            type: 'branch',
            x: branchX,
            y: branchY,
            label: branch.name,
            color: mindMapTree.value.root.color,
            chunksCount: branch.chunks.length,
        });

        links.push({
            id: `link_root_${branchId}`,
            x1: rootX,
            y1: rootY,
            x2: branchX,
            y2: branchY,
            color: mindMapTree.value.root.color,
        });

        // Nodos Hijos (Chunks)
        const totalChunks = branch.chunks.length;
        branch.chunks.forEach((chunk, cIdx) => {
            const spread = 0.5;
            const subAngle = angle + (cIdx - (totalChunks - 1) / 2) * spread;
            const chunkDist = 140;
            const chunkX = branchX + Math.cos(subAngle) * chunkDist;
            const chunkY = branchY + Math.sin(subAngle) * chunkDist;
            const chunkId = `chunk_${chunk.id}`;

            nodes.push({
                id: chunkId,
                type: 'chunk',
                x: chunkX,
                y: chunkY,
                label: chunk.label,
                color: chunk.color || mindMapTree.value.root.color,
                mastery: chunk.mastery || 70,
                rawChunk: chunk,
            });

            links.push({
                id: `link_${branchId}_${chunkId}`,
                x1: branchX,
                y1: branchY,
                x2: chunkX,
                y2: chunkY,
                color: chunk.color || mindMapTree.value.root.color,
            });
        });
    });

    return { nodes, links };
});

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
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    zoom.value = Math.max(0.4, Math.min(2.5, zoom.value * delta));
}

function resetView() {
    zoom.value = 1;
    pan.value = { x: 0, y: 0 };
}
</script>

<template>
    <div class="relative w-full h-[75vh] bg-slate-950 rounded-3xl border border-slate-800 overflow-hidden select-none flex flex-col shadow-2xl">
        
        <!-- Header del Mapa Mental -->
        <div class="absolute top-4 left-4 z-20 flex items-center gap-3 bg-slate-900/80 backdrop-blur-md px-4 py-2 rounded-2xl border border-slate-800 shadow-xl">
            <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: course.color || '#6366f1' }" />
            <div>
                <h3 class="text-sm font-black text-white leading-none">
                    Mapa Mental: {{ course.name }}
                </h3>
                <span class="text-[10px] text-slate-400 font-semibold">
                    {{ chunks.length }} Conceptos Clave organizados por ramas temáticas
                </span>
            </div>
        </div>

        <!-- Controles Flotantes Pan/Zoom -->
        <div class="absolute top-4 right-4 z-20 flex items-center gap-1.5 bg-slate-900/80 backdrop-blur-md p-1 rounded-2xl border border-slate-800 shadow-xl">
            <button
                type="button"
                class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
                title="Acercar"
                @click="zoom = Math.min(2.5, zoom * 1.15)"
            >
                <ZoomIn class="w-4 h-4" />
            </button>
            <button
                type="button"
                class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
                title="Alejar"
                @click="zoom = Math.max(0.4, zoom / 1.15)"
            >
                <ZoomOut class="w-4 h-4" />
            </button>
            <button
                type="button"
                class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
                title="Centrar Vista"
                @click="resetView"
            >
                <Compass class="w-4 h-4 text-indigo-400" />
            </button>
        </div>

        <!-- Estado Vacío -->
        <div
            v-if="chunks.length === 0"
            class="m-auto text-center p-8 space-y-4 max-w-md z-10"
        >
            <div class="w-14 h-14 rounded-2xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center mx-auto border border-indigo-500/30 shadow-lg shadow-indigo-500/10">
                <Layers class="w-7 h-7" />
            </div>
            <h4 class="text-base font-bold text-white">Mapa Mental No Generado</h4>
            <p class="text-xs text-slate-400">
                Conecta los apuntes de <strong>{{ course.name }}</strong> con IA para estructurar el mapa conceptual automático.
            </p>
            <button
                type="button"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-bold shadow-lg shadow-indigo-500/20 hover:scale-105 active:scale-95 transition-all"
                @click="emit('generateAi')"
            >
                ✨ Generar con IA
            </button>
        </div>

        <!-- Canvas SVG Interactivo -->
        <svg
            v-else
            ref="svgContainer"
            class="w-full h-full cursor-grab active:cursor-grabbing"
            @mousedown="handleMouseDown"
            @mousemove="handleMouseMove"
            @mouseup="handleMouseUp"
            @mouseleave="handleMouseUp"
            @wheel="handleWheel"
        >
            <defs>
                <!-- Filtro de Resplandor Neón -->
                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>

            <g :transform="`translate(${pan.x}, ${pan.y}) scale(${zoom})`">
                
                <!-- Enlaces Curvos Orgánicos (Ramas) -->
                <g class="links">
                    <path
                        v-for="link in layoutElements.links"
                        :key="link.id"
                        :d="`M ${link.x1} ${link.y1} C ${(link.x1 + link.x2) / 2} ${link.y1}, ${(link.x1 + link.x2) / 2} ${link.y2}, ${link.x2} ${link.y2}`"
                        fill="none"
                        :stroke="link.color"
                        stroke-width="2"
                        stroke-opacity="0.35"
                        stroke-dasharray="3 3"
                    />
                </g>

                <!-- Nodos -->
                <g class="nodes">
                    
                    <!-- 1. Nodo Raíz (Curso Central) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'root')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                    >
                        <circle
                            r="44"
                            :fill="node.color"
                            fill-opacity="0.25"
                            :stroke="node.color"
                            stroke-width="3"
                            filter="url(#glow)"
                        />
                        <circle r="36" fill="#020617" :stroke="node.color" stroke-width="2" />
                        <text
                            text-anchor="middle"
                            dy="4"
                            fill="#FFFFFF"
                            font-size="12"
                            font-weight="900"
                            class="select-none"
                        >
                            {{ node.label.substring(0, 14) }}
                        </text>
                    </g>

                    <!-- 2. Nodos de Rama Temática (Módulos / Ejes) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'branch')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                    >
                        <rect
                            x="-60"
                            y="-18"
                            width="120"
                            height="36"
                            rx="18"
                            fill="#0f172a"
                            :stroke="node.color"
                            stroke-width="2"
                            stroke-opacity="0.8"
                            filter="url(#glow)"
                        />
                        <text
                            text-anchor="middle"
                            dy="4"
                            fill="#e2e8f0"
                            font-size="11"
                            font-weight="bold"
                            class="select-none"
                        >
                            {{ node.label.substring(0, 16) }}
                        </text>
                    </g>

                    <!-- 3. Nodos Hijos (Chunks / Conceptos Interactivos) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'chunk')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                        class="interactive-node cursor-pointer group"
                    >
                        <rect
                            x="-65"
                            y="-22"
                            width="130"
                            height="44"
                            rx="12"
                            fill="#1e293b"
                            class="group-hover:fill-slate-800 transition-colors shadow-lg"
                            :stroke="node.color"
                            stroke-width="1.5"
                        />
                        
                        <!-- Barra de Dominio en el Nodo -->
                        <rect
                            x="-65"
                            y="18"
                            :width="(node.mastery / 100) * 130"
                            height="4"
                            rx="2"
                            :fill="node.mastery >= 75 ? '#10b981' : node.mastery >= 50 ? '#f59e0b' : '#f43f5e'"
                        />

                        <!-- Título del Chunk -->
                        <text
                            text-anchor="middle"
                            dy="-3"
                            fill="#f8fafc"
                            font-size="10"
                            font-weight="bold"
                            class="select-none"
                        >
                            {{ node.label.substring(0, 18) }}
                        </text>

                        <!-- Botones Rápidos en el Nodo SVG -->
                        <g transform="translate(0, 9)" class="opacity-90 group-hover:opacity-100 transition-opacity">
                            <!-- Botón Estudiar -->
                            <g
                                transform="translate(-25, 0)"
                                class="hover:scale-110 transition-transform"
                                @click.stop="emit('studyChunk', node.rawChunk)"
                            >
                                <rect x="-16" y="-7" width="32" height="14" rx="7" fill="#4f46e5" />
                                <text text-anchor="middle" dy="3.5" fill="#ffffff" font-size="8" font-weight="900">📖 Ver</text>
                            </g>

                            <!-- Botón Recall -->
                            <g
                                transform="translate(25, 0)"
                                class="hover:scale-110 transition-transform"
                                @click.stop="emit('recallChunk', node.rawChunk)"
                            >
                                <rect x="-18" y="-7" width="36" height="14" rx="7" fill="#e11d48" />
                                <text text-anchor="middle" dy="3.5" fill="#ffffff" font-size="8" font-weight="900">❓ Quiz</text>
                            </g>
                        </g>
                    </g>
                </g>
            </g>
        </svg>

        <!-- Leyenda Inferior -->
        <div class="absolute bottom-4 left-4 z-20 hidden sm:flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-900/80 backdrop-blur-md border border-slate-800 text-xs text-slate-400">
            <span>💡 <strong>Arrastra el canvas</strong> para mover • <strong>Rueda</strong> para zoom • Clic en <strong>Ver</strong> o <strong>Quiz</strong></span>
        </div>
    </div>
</template>
