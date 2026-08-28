<script setup>
import { ref, computed } from 'vue';
import {
    ZoomIn,
    ZoomOut,
    Compass,
    Layers,
    Sparkles
} from '@lucide/vue';

const props = defineProps({
    course: { type: Object, required: true },
    chunks: { type: Array, default: () => [] },
});

const emit = defineEmits(['studyChunk', 'generateAi']);

// Estado Canvas SVG
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
            color: props.course.color || '#4f46e5',
        },
        branches: branches,
    };
});

// Layout Radial / Árbol Limpio
const layoutElements = computed(() => {
    if (!mindMapTree.value) return { nodes: [], links: [] };

    const nodes = [];
    const links = [];

    const rootX = 450;
    const rootY = 320;

    // 1. Nodo Raíz (Curso)
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
        const angle = (bIdx / totalBranches) * 2 * Math.PI - Math.PI / 2;
        const branchDist = 180;
        const branchX = rootX + Math.cos(angle) * branchDist;
        const branchY = rootY + Math.sin(angle) * branchDist;
        const branchId = `branch_${bIdx}`;

        // 2. Nodo Rama Temática
        nodes.push({
            id: branchId,
            type: 'branch',
            x: branchX,
            y: branchY,
            label: branch.name,
            color: mindMapTree.value.root.color,
        });

        links.push({
            id: `link_root_${branchId}`,
            x1: rootX,
            y1: rootY,
            x2: branchX,
            y2: branchY,
            color: mindMapTree.value.root.color,
        });

        // 3. Nodos Conceptuales (Chunks)
        const totalChunks = branch.chunks.length;
        branch.chunks.forEach((chunk, cIdx) => {
            const spread = 0.45;
            const subAngle = angle + (cIdx - (totalChunks - 1) / 2) * spread;
            const chunkDist = 150;
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
    <div class="relative w-full h-[75vh] bg-white rounded-3xl border border-slate-200/90 overflow-hidden select-none flex flex-col shadow-sm">
        
        <!-- Header del Mapa Mental -->
        <div class="absolute top-4 left-4 z-20 flex items-center gap-3 bg-white/95 backdrop-blur-md px-4 py-2 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: course.color || '#4f46e5' }" />
            <div>
                <h3 class="text-sm font-bold text-slate-800 leading-none">
                    Mapa Mental: {{ course.name }}
                </h3>
                <span class="text-[11px] text-slate-500 font-medium">
                    {{ chunks.length }} Conceptos estructurados por ramas
                </span>
            </div>
        </div>

        <!-- Controles Flotantes Pan/Zoom -->
        <div class="absolute top-4 right-4 z-20 flex items-center gap-1 bg-white/95 backdrop-blur-md p-1 rounded-2xl border border-slate-200 shadow-sm">
            <button
                type="button"
                class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                title="Acercar"
                @click="zoom = Math.min(2.5, zoom * 1.15)"
            >
                <ZoomIn class="w-4 h-4" />
            </button>
            <button
                type="button"
                class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                title="Alejar"
                @click="zoom = Math.max(0.4, zoom / 1.15)"
            >
                <ZoomOut class="w-4 h-4" />
            </button>
            <button
                type="button"
                class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                title="Centrar Vista"
                @click="resetView"
            >
                <Compass class="w-4 h-4 text-indigo-600" />
            </button>
        </div>

        <!-- Estado Vacío -->
        <div
            v-if="chunks.length === 0"
            class="m-auto text-center p-8 space-y-4 max-w-md z-10"
        >
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto">
                <Layers class="w-6 h-6" />
            </div>
            <h4 class="text-base font-bold text-slate-800">Mapa Mental No Generado</h4>
            <p class="text-xs text-slate-500">
                Conecta los apuntes de <strong>{{ course.name }}</strong> con IA para estructurar el mapa conceptual automático.
            </p>
            <button
                type="button"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all"
                @click="emit('generateAi')"
            >
                Generar con IA
            </button>
        </div>

        <!-- Canvas SVG Interactivo Limpio (Fondo Blanco) -->
        <svg
            v-else
            class="w-full h-full cursor-grab active:cursor-grabbing bg-slate-50/50"
            @mousedown="handleMouseDown"
            @mousemove="handleMouseMove"
            @mouseup="handleMouseUp"
            @mouseleave="handleMouseUp"
            @wheel="handleWheel"
        >
            <g :transform="`translate(${pan.x}, ${pan.y}) scale(${zoom})`">
                
                <!-- Enlaces Curvos Orgánicos -->
                <g class="links">
                    <path
                        v-for="link in layoutElements.links"
                        :key="link.id"
                        :d="`M ${link.x1} ${link.y1} C ${(link.x1 + link.x2) / 2} ${link.y1}, ${(link.x1 + link.x2) / 2} ${link.y2}, ${link.x2} ${link.y2}`"
                        fill="none"
                        :stroke="link.color"
                        stroke-width="2"
                        stroke-opacity="0.3"
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
                            r="46"
                            fill="#ffffff"
                            :stroke="node.color"
                            stroke-width="3"
                            class="shadow-sm"
                        />
                        <text
                            text-anchor="middle"
                            dy="4"
                            :fill="node.color"
                            font-size="12"
                            font-weight="bold"
                            class="select-none"
                        >
                            {{ node.label.substring(0, 15) }}
                        </text>
                    </g>

                    <!-- 2. Nodos de Rama Temática (Módulos / Ejes) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'branch')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                    >
                        <rect
                            x="-55"
                            y="-16"
                            width="110"
                            height="32"
                            rx="16"
                            fill="#ffffff"
                            :stroke="node.color"
                            stroke-width="1.8"
                        />
                        <text
                            text-anchor="middle"
                            dy="4"
                            fill="#334155"
                            font-size="11"
                            font-weight="bold"
                            class="select-none"
                        >
                            {{ node.label.substring(0, 16) }}
                        </text>
                    </g>

                    <!-- 3. Nodos Hijos (Chunks / Conceptos Limpios sin botones) -->
                    <g
                        v-for="node in layoutElements.nodes.filter(n => n.type === 'chunk')"
                        :key="node.id"
                        :transform="`translate(${node.x}, ${node.y})`"
                        class="interactive-node cursor-pointer group"
                        @click="emit('studyChunk', node.rawChunk)"
                    >
                        <rect
                            x="-65"
                            y="-18"
                            width="130"
                            height="36"
                            rx="10"
                            fill="#ffffff"
                            stroke="#cbd5e1"
                            stroke-width="1.2"
                            class="group-hover:stroke-indigo-500 group-hover:shadow-md transition-all"
                        />
                        
                        <!-- Barra de Dominio en el borde inferior -->
                        <rect
                            x="-65"
                            y="14"
                            :width="(node.mastery / 100) * 130"
                            height="4"
                            rx="2"
                            :fill="node.mastery >= 75 ? '#10b981' : node.mastery >= 50 ? '#f59e0b' : '#f43f5e'"
                        />

                        <!-- Título del Concepto -->
                        <text
                            text-anchor="middle"
                            dy="4"
                            fill="#0f172a"
                            font-size="10.5"
                            font-weight="600"
                            class="select-none group-hover:fill-indigo-600 transition-colors"
                        >
                            {{ node.label.substring(0, 18) }}
                        </text>
                    </g>
                </g>
            </g>
        </svg>

        <!-- Leyenda Inferior Limpia -->
        <div class="absolute bottom-4 left-4 z-20 hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200 text-xs text-slate-500 shadow-sm">
            <span>Arrastra para mover • Rueda para zoom • Clic en cualquier concepto para estudiarlo</span>
        </div>
    </div>
</template>
