<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import axios from 'axios';
import {
    Sparkles,
    Search,
    RotateCcw,
    ZoomIn,
    ZoomOut,
    Maximize2,
    Minimize2,
    X,
    BookOpen,
    Network,
    Loader2,
    Compass,
    Brain,
    Layers,
    Activity,
    Box,
    ExternalLink
} from '@lucide/vue';
import ForceGraph3D from '3d-force-graph';
import * as THREE from 'three';

const props = defineProps({
    show: { type: Boolean, default: false },
    courses: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'openNote', 'studyChunk', 'recallChunk']);

// ── Paleta Científica Calibrada ─────────────────────────────────────────────
const SCIENTIFIC_PALETTE = [
    '#818cf8', '#34d399', '#fbbf24', '#38bdf8', '#f472b6',
    '#a78bfa', '#fb923c', '#2dd4bf', '#e879f9', '#60a5fa'
];

function resolveColor(c, fallbackIndex = 0) {
    if (!c || typeof c !== 'string') return SCIENTIFIC_PALETTE[fallbackIndex % SCIENTIFIC_PALETTE.length];
    const trimmed = c.trim();
    if (trimmed.startsWith('#') || trimmed.startsWith('rgb') || trimmed.startsWith('hsl')) return trimmed;
    return SCIENTIFIC_PALETTE[fallbackIndex % SCIENTIFIC_PALETTE.length];
}

function hexToRgba(hex, alpha = 0.3) {
    const c = resolveColor(hex);
    if (c.startsWith('#')) {
        let cleanHex = c.replace('#', '');
        if (cleanHex.length === 3) cleanHex = cleanHex.split('').map(x => x + x).join('');
        const num = parseInt(cleanHex, 16);
        return `rgba(${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}, ${alpha})`;
    }
    return c;
}

// ── Estado General ──────────────────────────────────────────────────────────
const loading = ref(false);
const errorMessage = ref('');
const graphData = ref({
    has_graph: false,
    nodes: [],
    edges: [],
    stats: { total_concepts: 0, total_connections: 0, courses_count: 0 },
    courses: [],
});

const selectedCourseId = ref(null);
const searchQuery = ref('');
const isFullscreen = ref(false);
const livePulseActive = ref(true);
const isMobileDevice = ref(false);

// ── Three.js 3D Variables ───────────────────────────────────────────────────
const graph3DContainer = ref(null);
const containerRef = ref(null);
let graph3DInstance = null;
let cosmicOrbsGroup = null;
let starDustParticles = null;
let resizeObserver3D = null;
let hoveredNode = null;

// ── Control de Teclado Cinemático 3D (W, A, S, D, +, -) ─────────────────────
function handleKeyDown(e) {
    if (!props.show || !graph3DInstance) return;
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;

    const key = e.key.toLowerCase();
    const camera = graph3DInstance.camera();
    const controls = graph3DInstance.controls();
    if (!camera) return;

    const target = controls?.target ? controls.target.clone() : new THREE.Vector3(0, 0, 0);
    const offset = new THREE.Vector3().subVectors(camera.position, target);
    const spherical = new THREE.Spherical().setFromVector3(offset);

    let changed = false;

    // A / D: Rotación orbital azimutal alrededor del cerebro
    if (key === 'a' || e.key === 'ArrowLeft') {
        e.preventDefault();
        spherical.theta += 0.12;
        changed = true;
    } else if (key === 'd' || e.key === 'ArrowRight') {
        e.preventDefault();
        spherical.theta -= 0.12;
        changed = true;
    } 
    // W / S: Elevación polar / inclinación
    else if (key === 'w' || e.key === 'ArrowUp') {
        e.preventDefault();
        spherical.phi = Math.max(0.1, Math.min(Math.PI - 0.1, spherical.phi - 0.08));
        changed = true;
    } else if (key === 's' || e.key === 'ArrowDown') {
        e.preventDefault();
        spherical.phi = Math.max(0.1, Math.min(Math.PI - 0.1, spherical.phi + 0.08));
        changed = true;
    } 
    // + / -: Zoom in / Zoom out cinemático
    else if (key === '+' || key === '=' || e.code === 'NumpadAdd') {
        e.preventDefault();
        spherical.radius = Math.max(80, spherical.radius * 0.85);
        changed = true;
    } else if (key === '-' || key === '_' || e.code === 'NumpadSubtract') {
        e.preventDefault();
        spherical.radius = Math.min(1200, spherical.radius * 1.18);
        changed = true;
    }

    if (changed) {
        offset.setFromSpherical(spherical);
        const newPos = new THREE.Vector3().addVectors(target, offset);
        graph3DInstance.cameraPosition(
            { x: newPos.x, y: newPos.y, z: newPos.z },
            target,
            80
        );
    }
}

// ── Nodos Filtrados ─────────────────────────────────────────────────────────
const simulationNodes = computed(() => {
    let list = graphData.value.nodes || [];
    if (selectedCourseId.value !== null) {
        list = list.filter(n => n.course_id === selectedCourseId.value || n.id === `course_${selectedCourseId.value}`);
    }
    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(n =>
            (n.label && n.label.toLowerCase().includes(q)) ||
            (n.course_name && n.course_name.toLowerCase().includes(q))
        );
    }
    return list;
});

const simulationEdges = computed(() => {
    const nodeIds = new Set(simulationNodes.value.map(n => n.id));
    return (graphData.value.edges || []).filter(e => {
        const s = typeof e.source === 'object' ? e.source.id : e.source;
        const t = typeof e.target === 'object' ? e.target.id : e.target;
        return nodeIds.has(s) && nodeIds.has(t);
    });
});

// ── Inicialización de la Escena 3D ──────────────────────────────────────────
async function loadGraph() {
    loading.value = true;
    errorMessage.value = '';
    try {
        const url = typeof route !== 'undefined' && route().has('calendar.knowledge-graph.show')
            ? route('calendar.knowledge-graph.show')
            : '/api/calendar/knowledge-graph';
        const res = await axios.get(url);
        if (res.data.success) {
            graphData.value = res.data.data;
            nextTick(() => {
                init3DGraph();
            });
        }
    } catch (e) {
        console.error('Error cargando grafo 3D:', e);
        errorMessage.value = 'No se pudo cargar el grafo 3D.';
    } finally {
        loading.value = false;
    }
}

function init3DGraph() {
    if (!graph3DContainer.value) return;
    destroy3DGraph();

    const elem = graph3DContainer.value;

    const gData = {
        nodes: simulationNodes.value.map(n => ({ ...n })),
        links: simulationEdges.value.map(e => ({ ...e }))
    };

    graph3DInstance = ForceGraph3D()(elem)
        .backgroundColor('#020307')
        .showNavInfo(false)
        .nodeLabel(n => `${n.is_parent ? '🎓 ' : '🧩 '}${n.label}`)
        .nodeColor(n => n.color || '#818cf8')
        .nodeResolution(16)
        .graphData(gData)
        // Conexiones / Aristas
        .linkColor(link => {
            const sourceColor = typeof link.source === 'object' ? link.source.color : '#4f46e5';
            return hexToRgba(sourceColor, link.type === 'hierarchy' ? 0.6 : 0.35);
        })
        .linkOpacity(0.5)
        .linkWidth(l => l.type === 'hierarchy' ? 1.5 : 0.8)
        .linkDirectionalParticles(livePulseActive.value ? 2 : 0)
        .linkDirectionalParticleSpeed(0.012)
        .linkDirectionalParticleWidth(1.8)
        .linkDirectionalParticleColor(link => {
            return typeof link.source === 'object' ? link.source.color : '#c084fc';
        })
        .onNodeHover(node => {
            hoveredNode = node;
            elem.style.cursor = node ? 'pointer' : 'grab';
        })
        .onNodeClick(node => {
            if (!node) return;
            // Vuelo cinemático al nodo
            const distance = isMobileDevice.value ? 120 : 160;
            const distRatio = 1 + distance / Math.hypot(node.x || 1, node.y || 1, node.z || 0.1);
            graph3DInstance.cameraPosition(
                { x: (node.x || 0) * distRatio, y: (node.y || 0) * distRatio, z: (node.z || 0) * distRatio + 40 },
                node,
                1400
            );
        });

    // Renderizar Nodos 3D Personalizados (Padres Cursos vs Hijos Chunks)
    graph3DInstance.nodeThreeObject(node => {
        const group = new THREE.Group();
        const isParent = Boolean(node.is_parent);
        const nodeColor = new THREE.Color(resolveColor(node.color));
        const nodeRadius = isParent ? 14 : 7.5;

        // Esfera principal
        const sphereMat = new THREE.MeshLambertMaterial({
            color: nodeColor,
            emissive: nodeColor,
            emissiveIntensity: isParent ? 0.45 : 0.25,
            transparent: true,
            opacity: 0.92,
        });
        const sphere = new THREE.Mesh(new THREE.SphereGeometry(nodeRadius, 20, 20), sphereMat);
        group.add(sphere);

        // Halo holográfico para nodos padre
        if (isParent) {
            const haloMat = new THREE.MeshBasicMaterial({
                color: nodeColor,
                wireframe: true,
                transparent: true,
                opacity: 0.3,
                blending: THREE.AdditiveBlending,
            });
            const halo = new THREE.Mesh(new THREE.IcosahedronGeometry(nodeRadius * 1.5, 2), haloMat);
            group.add(halo);
        }

        // Sprite de Texto flotante
        const textSprite = createTextSprite3D(node.label, '#ffffff');
        textSprite.position.set(0, nodeRadius + 12, 0);
        group.add(textSprite);

        return group;
    });

    // Agregar Orbe Cósmico Central de Cerebro Artificial
    addCosmicOrbTo3DScene();

    // Resize Observer
    resizeObserver3D = new ResizeObserver(() => {
        if (graph3DInstance && containerRef.value) {
            const rect = containerRef.value.getBoundingClientRect();
            graph3DInstance.width(rect.width);
            graph3DInstance.height(rect.height);
        }
    });
    resizeObserver3D.observe(elem);
}

function addCosmicOrbTo3DScene() {
    if (!graph3DInstance) return;
    const scene = graph3DInstance.scene();
    
    cosmicOrbsGroup = new THREE.Group();
    
    // Matriz Holográfica Externa (Cian Neón)
    const brainMat = new THREE.MeshBasicMaterial({
        color: 0x38bdf8,
        wireframe: true,
        transparent: true,
        opacity: 0.08,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });
    const brainOuter = new THREE.Mesh(new THREE.IcosahedronGeometry(250, 3), brainMat);
    cosmicOrbsGroup.add(brainOuter);

    // Núcleo Sináptico Interior (Índigo)
    const coreMat = new THREE.MeshBasicMaterial({
        color: 0x818cf8,
        wireframe: true,
        transparent: true,
        opacity: 0.12,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });
    const brainCore = new THREE.Mesh(new THREE.IcosahedronGeometry(150, 2), coreMat);
    cosmicOrbsGroup.add(brainCore);

    // Partículas de Polvo Estelar
    const particleCount = 140;
    const particleGeometry = new THREE.BufferGeometry();
    const particlePositions = new Float32Array(particleCount * 3);
    const particleColors = new Float32Array(particleCount * 3);
    const color1 = new THREE.Color(0x38bdf8);
    const color2 = new THREE.Color(0x818cf8);

    for (let i = 0; i < particleCount; i++) {
        const radius = Math.random() * 550;
        const theta = Math.random() * 2 * Math.PI;
        const phi = Math.acos((Math.random() * 2) - 1);
        particlePositions[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
        particlePositions[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
        particlePositions[i * 3 + 2] = radius * Math.cos(phi);

        const mixedColor = color1.clone().lerp(color2, Math.random());
        particleColors[i * 3] = mixedColor.r;
        particleColors[i * 3 + 1] = mixedColor.g;
        particleColors[i * 3 + 2] = mixedColor.b;
    }
    
    particleGeometry.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
    particleGeometry.setAttribute('color', new THREE.BufferAttribute(particleColors, 3));

    const particleMaterial = new THREE.PointsMaterial({
        size: isMobileDevice.value ? 0.9 : 1.3,
        vertexColors: true,
        transparent: true,
        opacity: 0.6,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });

    starDustParticles = new THREE.Points(particleGeometry, particleMaterial);
    cosmicOrbsGroup.add(starDustParticles);
    scene.add(cosmicOrbsGroup);

    const animateCosmic = () => {
        if (cosmicOrbsGroup && livePulseActive.value) {
            brainOuter.rotation.y += 0.0006;
            brainOuter.rotation.x += 0.0003;
            brainCore.rotation.y -= 0.0008;
            if (starDustParticles) {
                starDustParticles.rotation.y += 0.0002;
            }
        }
        if (graph3DInstance) {
            requestAnimationFrame(animateCosmic);
        }
    };
    animateCosmic();
}

function createTextSprite3D(message, color) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    canvas.width = 512 * dpr;
    canvas.height = 96 * dpr;
    ctx.scale(dpr, dpr);
    ctx.font = 'bold 32px Inter, sans-serif';
    ctx.fillStyle = color;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.shadowColor = '#000000';
    ctx.shadowBlur = 8;
    ctx.fillText(message, 256, 48);

    const texture = new THREE.CanvasTexture(canvas);
    const spriteMaterial = new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false });
    const sprite = new THREE.Sprite(spriteMaterial);
    sprite.scale.set(55, 12, 1);
    return sprite;
}

function destroy3DGraph() {
    if (graph3DInstance) {
        if (resizeObserver3D && graph3DContainer.value) {
            resizeObserver3D.disconnect();
            resizeObserver3D = null;
        }
        const scene = graph3DInstance.scene();
        if (scene && cosmicOrbsGroup) {
            scene.remove(cosmicOrbsGroup);
        }
        graph3DInstance._destructor();
        graph3DInstance = null;
        cosmicOrbsGroup = null;
    }
}

// ── Watchers y Lifecycle ────────────────────────────────────────────────────
watch(
    () => props.show,
    (show) => {
        if (show) {
            window.addEventListener('keydown', handleKeyDown);
            loadGraph();
        } else {
            window.removeEventListener('keydown', handleKeyDown);
            destroy3DGraph();
        }
    },
    { immediate: true }
);

watch(selectedCourseId, () => {
    if (graph3DInstance) {
        const gData = {
            nodes: simulationNodes.value.map(n => ({ ...n })),
            links: simulationEdges.value.map(e => ({ ...e }))
        };
        graph3DInstance.graphData(gData);
    }
});

function toggleFullscreen() {
    if (!containerRef.value) return;

    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen().then(() => {
            isFullscreen.value = true;
            resize3D();
        }).catch(err => {
            console.error('Error fullscreen:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
            resize3D();
        }).catch(err => {
            console.error('Error exit fullscreen:', err);
        });
    }
}

function resize3D() {
    nextTick(() => {
        setTimeout(() => {
            if (graph3DInstance && containerRef.value) {
                const rect = containerRef.value.getBoundingClientRect();
                graph3DInstance.width(rect.width);
                graph3DInstance.height(rect.height);
            }
        }, 100);
    });
}

function onFullscreenChange() {
    isFullscreen.value = !!document.fullscreenElement;
    resize3D();
}

onMounted(() => {
    document.addEventListener('fullscreenchange', onFullscreenChange);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    destroy3DGraph();
});
</script>

<template>
    <div
        v-if="show"
        ref="containerRef"
        class="relative w-full h-[78vh] bg-slate-950 rounded-3xl border border-slate-800 select-none flex flex-col shadow-2xl animate-fade-in overflow-hidden"
    >
        <!-- Barra Superior Header 3D -->
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 bg-slate-900/90 backdrop-blur-md border-b border-slate-800/80 z-20 shrink-0">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 text-indigo-400">
                    <Brain class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-white leading-none">
                        Segundo Cerebro 3D
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Tú Segundo cerebro • Constelaciones de Nodos Padre (Cursos) y Chunks
                    </p>
                </div>
            </div>

            <!-- Filtro de Asignaturas & Acciones -->
            <div class="flex items-center gap-2">
                <!-- Selector de Cursos -->
                <div class="hidden sm:flex items-center gap-1 bg-slate-800/80 p-0.5 rounded-xl border border-slate-700/60 overflow-x-auto max-w-xs">
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all shrink-0"
                        :class="selectedCourseId === null ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                        @click="selectedCourseId = null"
                    >
                        Todos
                    </button>
                    <button
                        v-for="c in courses"
                        :key="c.id"
                        type="button"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all shrink-0 flex items-center gap-1.5"
                        :class="selectedCourseId === c.id ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                        @click="selectedCourseId = c.id"
                    >
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: c.color || '#6366f1' }" />
                        <span class="truncate max-w-[80px]">{{ c.name }}</span>
                    </button>
                </div>

                <!-- Botón Sinapsis Viva -->
                <button
                    type="button"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all"
                    :class="livePulseActive ? 'bg-indigo-600/20 border-indigo-500/50 text-indigo-300' : 'bg-slate-800 border-slate-700 text-slate-400'"
                    @click="livePulseActive = !livePulseActive"
                >
                    <Activity class="w-3.5 h-3.5" :class="livePulseActive ? 'animate-pulse text-indigo-400' : ''" />
                    <span class="hidden md:inline">{{ livePulseActive ? 'Sinapsis Activa' : 'Pausada' }}</span>
                </button>

                <!-- Botón Pantalla Completa -->
                <button
                    type="button"
                    class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors border border-slate-700/60"
                    :title="isFullscreen ? 'Salir de Pantalla Completa' : 'Pantalla Completa'"
                    @click="toggleFullscreen"
                >
                    <Minimize2 v-if="isFullscreen" class="w-4 h-4 text-indigo-400" />
                    <Maximize2 v-else class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Canvas 3D ForceGraph Container -->
        <div ref="graph3DContainer" class="w-full h-full relative" />

        <!-- Leyenda Inferior -->
        <div class="absolute bottom-4 right-4 z-20 hidden sm:flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-900/80 backdrop-blur-md border border-slate-800 text-xs text-slate-400 shadow-xl">
            <span>💡 <strong>W, A, S, D</strong> orbitar • <strong>+ / -</strong> zoom • <strong>Arrastra</strong> explorar 3D</span>
            <span class="text-slate-700">|</span>
            <span class="text-emerald-400 font-bold">{{ simulationNodes.length }} Nodos en órbita</span>
        </div>
    </div>
</template>
