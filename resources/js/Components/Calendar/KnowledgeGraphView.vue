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
    Info,
    ArrowRight,
    Loader2,
    Compass,
    Eye,
    Brain,
    Layers,
    Zap,
    ZapOff,
    HelpCircle,
    CheckCircle2,
    ArrowUpRight,
    Activity,
    Box,
    Copy,
    Check,
    ExternalLink
} from '@lucide/vue';
import ForceGraph3D from '3d-force-graph';
import * as THREE from 'three';

const props = defineProps({
    show: { type: Boolean, default: false },
    courses: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'openNote']);

// ── Paleta de Colores Científica Calibrada para Descanso Visual (Dark Mode) ──
const SCIENTIFIC_PALETTE = [
    '#818cf8', // Índigo Cósmico (Lógica & Redes)
    '#34d399', // Esmeralda Suave (Práctica & Aplicación)
    '#fbbf24', // Ámbar Cálido (Atención & Análisis)
    '#38bdf8', // Cian Celeste (Tecnología & Sistemas)
    '#f472b6', // Orquídea Suave (Humanidades & Idiomas)
    '#a78bfa', // Lavanda Profundo (Metodología & Tesis)
    '#fb923c', // Coral Cálido (Creatividad)
    '#2dd4bf', // Menta Bio (Procesos)
    '#e879f9', // Magenta Neón Suave
    '#60a5fa', // Azul Brisa
];

const NAMED_COLOR_MAP = {
    primary: '#818cf8',
    accent: '#a78bfa',
    success: '#34d399',
    warning: '#fbbf24',
    danger: '#f87171',
    secondary: '#94a3b8',
};

function resolveColor(c, fallbackIndex = 0) {
    if (!c) return SCIENTIFIC_PALETTE[fallbackIndex % SCIENTIFIC_PALETTE.length];
    if (typeof c !== 'string') return SCIENTIFIC_PALETTE[fallbackIndex % SCIENTIFIC_PALETTE.length];
    const trimmed = c.trim();
    if (trimmed.startsWith('#') || trimmed.startsWith('rgb') || trimmed.startsWith('hsl')) {
        return trimmed;
    }
    return NAMED_COLOR_MAP[trimmed.toLowerCase()] || SCIENTIFIC_PALETTE[fallbackIndex % SCIENTIFIC_PALETTE.length];
}

function hexToRgba(hex, alpha = 0.3) {
    const c = resolveColor(hex);
    if (c.startsWith('#')) {
        let cleanHex = c.replace('#', '');
        if (cleanHex.length === 3) {
            cleanHex = cleanHex.split('').map(x => x + x).join('');
        }
        const num = parseInt(cleanHex, 16);
        const r = (num >> 16) & 255;
        const g = (num >> 8) & 255;
        const b = num & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    return c;
}

// ── Estado General ──────────────────────────────────────────────────────────
const loading = ref(false);
const generating = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const graphData = ref({
    has_graph: false,
    nodes: [],
    edges: [],
    stats: { total_concepts: 0, total_connections: 0, courses_count: 0, global_insight: '' },
    last_generated_at: null,
    quota: { used_count: 0, max_quota: 5, remaining: 5, is_exhausted: false },
    courses: [],
});

const selectedCourseId = ref(null);
const searchQuery = ref('');
const selectedNode = ref(null);
const isFullscreen = ref(false);
const showQuizAnswer = ref(false);
const livePulseActive = ref(true); // Modo Sinapsis Viva / Pulso Neural
const viewMode = ref('2D'); // '2D' o '3D' (Por defecto 2D para carga inmediata)
const isMobileDevice = ref(false);

// ── Control de Teclado WASD y +/- ───────────────────────────────────────────
function handleKeyDown(e) {
    if (!props.show) return;
    // Ignorar si el foco está en un input de texto
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
        return;
    }

    const key = e.key.toLowerCase();
    
    // Controles de Navegación WASD y +/-
    if (key === 'w' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (viewMode.value === '2D') {
            camera.value.y += 45;
            reheatSimulation(0.2);
        } else if (graph3DInstance) {
            const cp = graph3DInstance.cameraPosition();
            graph3DInstance.cameraPosition({ y: cp.y + 40 }, undefined, 100);
        }
    } else if (key === 's' || e.key === 'ArrowDown') {
        e.preventDefault();
        if (viewMode.value === '2D') {
            camera.value.y -= 45;
            reheatSimulation(0.2);
        } else if (graph3DInstance) {
            const cp = graph3DInstance.cameraPosition();
            graph3DInstance.cameraPosition({ y: cp.y - 40 }, undefined, 100);
        }
    } else if (key === 'a' || e.key === 'ArrowLeft') {
        e.preventDefault();
        if (viewMode.value === '2D') {
            camera.value.x += 45;
            reheatSimulation(0.2);
        } else if (graph3DInstance) {
            const cp = graph3DInstance.cameraPosition();
            graph3DInstance.cameraPosition({ x: cp.x - 40 }, undefined, 100);
        }
    } else if (key === 'd' || e.key === 'ArrowRight') {
        e.preventDefault();
        if (viewMode.value === '2D') {
            camera.value.x -= 45;
            reheatSimulation(0.2);
        } else if (graph3DInstance) {
            const cp = graph3DInstance.cameraPosition();
            graph3DInstance.cameraPosition({ x: cp.x + 40 }, undefined, 100);
        }
    } else if (key === '+' || key === '=' || e.code === 'NumpadAdd') {
        e.preventDefault();
        if (viewMode.value === '2D') {
            camera.value.zoom = Math.min(2.8, camera.value.zoom * 1.15);
            reheatSimulation(0.2);
        } else if (graph3DInstance) {
            const cp = graph3DInstance.cameraPosition();
            graph3DInstance.cameraPosition({ x: cp.x * 0.85, y: cp.y * 0.85, z: cp.z * 0.85 }, undefined, 100);
        }
    } else if (key === '-' || key === '_' || e.code === 'NumpadSubtract') {
        e.preventDefault();
        if (viewMode.value === '2D') {
            camera.value.zoom = Math.max(0.35, camera.value.zoom / 1.15);
            reheatSimulation(0.2);
        } else if (graph3DInstance) {
            const cp = graph3DInstance.cameraPosition();
            graph3DInstance.cameraPosition({ x: cp.x * 1.18, y: cp.y * 1.18, z: cp.z * 1.18 }, undefined, 100);
        }
    }
}

// ── Variables 2D / 3D Compartidas ───────────────────────────────────────────
const simulationNodes = ref([]);
const simulationEdges = ref([]);

// ── Canvas 2D Variables ─────────────────────────────────────────────────────
const canvasRef = ref(null);
const containerRef = ref(null);
let animationFrameId = null;

let simAlpha = 1.0;
const SIM_MIN_ALPHA = 0.002;
const SIM_DECAY = 0.94;

const camera = ref({ x: 0, y: 0, zoom: 1 });
let isPanning = false;
let panStart = { x: 0, y: 0 };
let draggedNode = null;
let hoveredNode = null;
let pointerDownPos = { x: 0, y: 0 };
let hasDragged = false;
let savePositionsTimeout = null;
let synapsisTime = 0;

// ── Three.js 3D Variables ───────────────────────────────────────────────────
const graph3DContainer = ref(null);
let graph3DInstance = null;
let cosmicOrbsGroup = null;
let starDustParticles = null; // Nuevo Polvo Estelar
const sharedSphereGeo = new THREE.SphereGeometry(1, 24, 24); // Shared Geometry para evitar alto consumo de RAM
let resizeObserver3D = null;

// ── Conteo de Conceptos por Curso ───────────────────────────────────────────
const courseConceptCounts = computed(() => {
    const counts = {};
    for (const node of graphData.value.nodes || []) {
        if (node.course_id) {
            counts[node.course_id] = (counts[node.course_id] || 0) + 1;
        }
    }
    return counts;
});

// ── Inicialización y Carga ──────────────────────────────────────────────────
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            document.body.style.overflow = 'hidden'; // Evitar scrollbar extra en PC
            isMobileDevice.value = window.innerWidth < 768;
            window.addEventListener('keydown', handleKeyDown);
            loadGraph();
            nextTick(() => {
                setTimeout(() => {
                    setupSimulationData();
                    if (viewMode.value === '2D') {
                        initCanvas();
                        startSimulation();
                    } else {
                        init3DGraph();
                    }
                }, 60);
            });
        } else {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', handleKeyDown);
            stopSimulation();
            destroy3DGraph();
        }
    },
    { immediate: true }
);

watch(viewMode, (newMode) => {
    if (newMode === '3D') {
        stopSimulation();
        nextTick(() => {
            init3DGraph();
        });
    } else {
        destroy3DGraph();
        nextTick(() => {
            initCanvas();
            autoFitCamera();
            startSimulation();
        });
    }
});

watch(selectedNode, (newNode) => {
    showQuizAnswer.value = false;
    if (newNode) {
        if (viewMode.value === '2D') {
            centerCameraOnNode(newNode);
        } else if (graph3DInstance) {
            // Vuelo Cinemático 3D (Fly-To)
            const distance = isMobileDevice.value ? 120 : 150;
            const distRatio = 1 + distance / Math.hypot(newNode.x, newNode.y, newNode.z || 0.1);
            graph3DInstance.cameraPosition(
                { x: newNode.x * distRatio, y: newNode.y * distRatio, z: (newNode.z || 0) * distRatio + 40 }, 
                newNode, // lookAt
                1600     // duración
            );
        }
    }
});

watch(livePulseActive, (active) => {
    if (viewMode.value === '3D' && graph3DInstance) {
        graph3DInstance.linkDirectionalParticles(active ? (d => (d.strength || 1) + 1) : 0);
    }
});

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
            setupSimulationData();
            if (viewMode.value === '3D' && graph3DInstance) {
                update3DData();
            }
        }
    } catch (e) {
        console.error('Error cargando grafo:', e);
        errorMessage.value = 'No se pudo cargar el grafo de conocimiento.';
    } finally {
        loading.value = false;
    }
}

async function handleGenerateGraph() {
    if (generating.value) return;
    generating.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const url = typeof route !== 'undefined' && route().has('calendar.knowledge-graph.generate')
            ? route('calendar.knowledge-graph.generate')
            : '/api/calendar/knowledge-graph/generate';
        const res = await axios.post(url);
        if (res.data.success) {
            graphData.value = res.data.data;
            successMessage.value = res.data.message || '¡Grafo de conocimiento ampliado con IA!';
            setupSimulationData();
            if (viewMode.value === '3D' && graph3DInstance) {
                update3DData();
            }
            setTimeout(() => {
                successMessage.value = '';
            }, 4000);
        }
    } catch (e) {
        console.error('Error generando grafo:', e);
        errorMessage.value = e.response?.data?.message || 'Error al generar el grafo con IA.';
    } finally {
        generating.value = false;
    }
}

// ── Datos Base (Usados por 2D y 3D) ─────────────────────────────────────────
function setupSimulationData() {
    const rawNodes = graphData.value.nodes || [];
    const rawEdges = graphData.value.edges || [];

    if (rawNodes.length === 0) {
        simulationNodes.value = [];
        simulationEdges.value = [];
        return;
    }

    const dpr = window.devicePixelRatio || 1;
    const width = 800;
    const height = 600;

    const courseColorMap = {};
    (graphData.value.courses || []).forEach((c, idx) => {
        courseColorMap[c.id] = resolveColor(c.color, idx);
    });

    const baseRadius = Math.min(width, height) * 0.32;
    const count = rawNodes.length;

    simulationNodes.value = rawNodes.map((n, i) => {
        const hasSavedPos = isFinite(n.x) && isFinite(n.y) && n.x !== 0 && n.y !== 0;
        const angle = (i / count) * 2 * Math.PI + 0.15;
        const dist = baseRadius * (0.65 + ((i % 3) * 0.2));
        const assignedColor = courseColorMap[n.course_id] || resolveColor(n.color, i);
        const importance = Number(n.importance) || 3;

        return {
            ...n,
            color: assignedColor,
            x: hasSavedPos ? Number(n.x) : (width / 2) + Math.cos(angle) * dist,
            y: hasSavedPos ? Number(n.y) : (height / 2) + Math.sin(angle) * dist,
            // Agregar componente Z aleatorio para el volumen 3D
            z: Math.random() * 120 - 60,
            vx: 0,
            vy: 0,
            vz: 0,
            radius: Math.max(13, importance * 2.2 + 7),
        };
    });

    simulationEdges.value = rawEdges.map(e => ({ ...e }));

    if (viewMode.value === '2D') {
        autoFitCamera();
        reheatSimulation(0.8);
    }
}

// ────────────────────────────────────────────────────────────────────────────
// ── MOTOR 3D HOLOGRÁFICO (Three.js & 3d-force-graph) ────────────────────────
// ────────────────────────────────────────────────────────────────────────────

function init3DGraph() {
    if (!graph3DContainer.value || simulationNodes.value.length === 0) return;
    
    // Destruir instancia previa de forma segura
    destroy3DGraph();

    const elem = graph3DContainer.value;
    
    // Inicializar ForceGraph3D (sin fijar width/height, usaremos ResizeObserver)
    graph3DInstance = ForceGraph3D()(elem)
        .backgroundColor('#020307')
        .showNavInfo(false)
        .nodeLabel('')
        .nodeColor('color')
        .nodeResolution(16) // Reducido de 32 a 16 (optimización RAM)
        .nodeRelSize(3)
        // Conexiones / Aristas
        .linkColor(link => {
            const sourceColor = typeof link.source === 'object' ? link.source.color : '#4f46e5';
            return hexToRgba(sourceColor, 0.45);
        })
        .linkOpacity(0.45)
        .linkWidth(0.8)
        .linkDirectionalParticles(livePulseActive.value ? (d => (d.strength || 1) + 1) : 0)
        .linkDirectionalParticleSpeed(0.012)
        .linkDirectionalParticleWidth(2.0) // Más brillantes
        .linkDirectionalParticleColor(link => {
            return typeof link.source === 'object' ? link.source.color : '#c084fc';
        })
        // Interactividad
        .onNodeClick(node => {
            selectedNode.value = node;
        })
        .onNodeHover(node => {
            hoveredNode = node;
            elem.style.cursor = node ? 'pointer' : 'grab';
        })
        .onBackgroundClick(() => {
            selectedNode.value = null;
        });

    // Material de los Nodos (Bolas de Energía)
    graph3DInstance.nodeThreeObject(node => {
        const group = new THREE.Group();
        
        // Esfera principal interactiva (Reusamos sharedSphereGeo)
        const material = new THREE.MeshPhongMaterial({
            color: node.color,
            emissive: node.color,
            emissiveIntensity: 0.6, // Mayor brillo
            shininess: 100,
            transparent: true,
            opacity: 0.95
        });
        const sphere = new THREE.Mesh(sharedSphereGeo, material);
        const s = node.radius * 0.4;
        sphere.scale.set(s, s, s); // Escalar geometría base en lugar de recrearla
        group.add(sphere);

        // Halo Exterior Brillante Cuántico (Additive Blending)
        const haloMaterial = new THREE.MeshBasicMaterial({
            color: node.color,
            transparent: true,
            opacity: 0.2, // Más visible
            blending: THREE.AdditiveBlending,
            depthWrite: false // Evitar bloqueos de oclusión Z
        });
        const halo = new THREE.Mesh(sharedSphereGeo, haloMaterial);
        const hs = node.radius * 0.65;
        halo.scale.set(hs, hs, hs); // Escalar geometría base
        group.add(halo);

        // Etiqueta Flotante 3D (Sprite en Alta Resolución/Retina)
        const isHoveredOrSelected = (selectedNode.value?.id === node.id) || (hoveredNode?.id === node.id);
        const isSearchMatch = searchQuery.value.trim() !== '' && node.label.toLowerCase().includes(searchQuery.value.trim().toLowerCase());
        
        if (simulationNodes.value.length < 25 || isHoveredOrSelected || isSearchMatch) {
            const sprite = createTextSprite3D(node.label, isHoveredOrSelected ? '#ffffff' : '#cbd5e1', node.color);
            sprite.position.y = -(node.radius * 0.4 + 4);
            group.add(sprite);
        }

        return group;
    });

    // Añadir El Gran Orbe Cerebral al fondo y Polvo Estelar
    addCosmicOrbTo3DScene();

    // Iluminación y Efectos (Niebla)
    const scene = graph3DInstance.scene();
    scene.fog = new THREE.FogExp2(0x020307, 0.0009); // Niebla cuántica reducida a la mitad

    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(100, 200, 300);
    scene.add(directionalLight);

    // Ajustar física básica
    graph3DInstance.d3Force('charge').strength(-350); // Un poco más de repulsión
    graph3DInstance.d3Force('link').distance(90);

    // Campo de Fuerza Magnético: Damping Radial (Rebote Suave)
    const maxRadius = 205; 
    graph3DInstance.d3Force('magneticBoundary', () => {
        const currentData = graph3DInstance.graphData();
        if (!currentData || !currentData.nodes) return;
        for (const n of currentData.nodes) {
            const dist = Math.hypot(n.x || 0, n.y || 0, n.z || 0);
            if (dist > maxRadius) {
                // Ley de Hooke invertida (Resorte magnético de contención)
                const excess = dist - maxRadius;
                const pushForce = excess * 0.02; // Damping factor
                
                n.vx -= (n.x / dist) * pushForce;
                n.vy -= (n.y / dist) * pushForce;
                n.vz -= (n.z / dist) * pushForce;
            }
        }
    });

    // Resize Observer Dinámico para prevenir cortes en pantalla completa o paneles
    resizeObserver3D = new ResizeObserver(entries => {
        for (let entry of entries) {
            if (graph3DInstance) {
                graph3DInstance.width(entry.contentRect.width);
                graph3DInstance.height(entry.contentRect.height);
            }
        }
    });
    resizeObserver3D.observe(elem);

    update3DData();
    update3DVisibility(); // Aplicar filtros vigentes
}

function addCosmicOrbTo3DScene() {
    if (!graph3DInstance) return;
    const scene = graph3DInstance.scene();
    
    cosmicOrbsGroup = new THREE.Group();
    
    // 🧠 1. Estructura Neuronal de Cerebro Artificial (Matriz Holográfica Externa)
    const brainMat = new THREE.MeshBasicMaterial({
        color: 0x38bdf8, // Cian Neón Suave
        wireframe: true,
        transparent: true,
        opacity: 0.09,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });
    const brainOuter = new THREE.Mesh(new THREE.IcosahedronGeometry(240, 3), brainMat);
    cosmicOrbsGroup.add(brainOuter);

    // 🧠 2. Núcleo Sináptico Interior (Cerebro Central Pulsante)
    const coreMat = new THREE.MeshBasicMaterial({
        color: 0x818cf8, // Índigo Neural
        wireframe: true,
        transparent: true,
        opacity: 0.14,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });
    const brainCore = new THREE.Mesh(new THREE.IcosahedronGeometry(150, 2), coreMat);
    cosmicOrbsGroup.add(brainCore);

    // ✨ 3. Sistema de Sinapsis y Polvo Estelar ✨
    const particleCount = 140;
    const particleGeometry = new THREE.BufferGeometry();
    const particlePositions = new Float32Array(particleCount * 3);
    const particleColors = new Float32Array(particleCount * 3);

    const color1 = new THREE.Color(0x38bdf8); // Cian
    const color2 = new THREE.Color(0x818cf8); // Índigo

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
        opacity: 0.65,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });

    starDustParticles = new THREE.Points(particleGeometry, particleMaterial);
    cosmicOrbsGroup.add(starDustParticles);

    scene.add(cosmicOrbsGroup);

    // Animar matrices neuronales en el loop
    const animateCosmic = () => {
        if (cosmicOrbsGroup && livePulseActive.value) {
            brainOuter.rotation.y += 0.0006;
            brainOuter.rotation.x += 0.0003;
            brainCore.rotation.y -= 0.0008;
            brainCore.rotation.z += 0.0004;
            if (starDustParticles) {
                starDustParticles.rotation.y += 0.0002;
                starDustParticles.rotation.x += 0.0001;
            }
        }
        if (graph3DInstance) {
            requestAnimationFrame(animateCosmic);
        }
    };
    animateCosmic();
}

function update3DData() {
    if (graph3DInstance) {
        const gData = {
            nodes: simulationNodes.value.map(n => ({...n})),
            links: simulationEdges.value.map(e => ({...e}))
        };
        graph3DInstance.graphData(gData);
        graph3DInstance.cameraPosition({ z: 450 });
    }
}

function handle3DResize() {
    isMobileDevice.value = window.innerWidth < 768;
}

// Memory Management & Garbage Collection
function disposeThreeObject(obj) {
    if (!obj) return;
    if (obj.geometry) {
        obj.geometry.dispose();
    }
    if (obj.material) {
        if (Array.isArray(obj.material)) {
            obj.material.forEach(mat => disposeMaterial(mat));
        } else {
            disposeMaterial(obj.material);
        }
    }
    if (obj.children && obj.children.length > 0) {
        obj.children.forEach(child => disposeThreeObject(child));
    }
}

function disposeMaterial(mat) {
    if (!mat) return;
    if (mat.map) mat.map.dispose();
    if (mat.lightMap) mat.lightMap.dispose();
    if (mat.bumpMap) mat.bumpMap.dispose();
    if (mat.normalMap) mat.normalMap.dispose();
    if (mat.specularMap) mat.specularMap.dispose();
    if (mat.envMap) mat.envMap.dispose();
    mat.dispose();
}

function destroy3DGraph() {
    if (graph3DInstance) {
        window.removeEventListener('resize', handle3DResize);
        
        if (resizeObserver3D && graph3DContainer.value) {
            resizeObserver3D.unobserve(graph3DContainer.value);
            resizeObserver3D.disconnect();
            resizeObserver3D = null;
        }

        // Recolección de Basura: Eliminar toda la escena manualmente para evitar Memory Leaks
        const scene = graph3DInstance.scene();
        if (scene) {
            disposeThreeObject(scene);
        }

        if (cosmicOrbsGroup) {
            disposeThreeObject(cosmicOrbsGroup);
        }

        graph3DInstance._destructor();
        graph3DInstance = null;
        cosmicOrbsGroup = null;
        starDustParticles = null;
    }
}

function createTextSprite3D(message, color, glowColor) {
    const safeMessage = "  " + message + "  "; // Padding seguro para primera/última letra
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // Soporte HiDPI (Retina)
    const dpr = window.devicePixelRatio || 1;
    const baseWidth = 640; // Ensanchado para texto largo y evitar corte
    const baseHeight = 128;
    
    // Multiplicar dimensiones internas por DPR para nitidez
    canvas.width = baseWidth * dpr;
    canvas.height = baseHeight * dpr;
    
    ctx.scale(dpr, dpr);
    
    ctx.font = 'bold 36px Inter, sans-serif'; // Tamaño relativo al scale
    ctx.fillStyle = color;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Halo oscuro para legibilidad (Crispness)
    ctx.shadowColor = '#000000';
    ctx.shadowBlur = 8;
    ctx.fillText(safeMessage, baseWidth / 2, baseHeight / 2);
    ctx.fillText(safeMessage, baseWidth / 2, baseHeight / 2); // Doble trazo (Crisp text)

    const texture = new THREE.CanvasTexture(canvas);
    texture.minFilter = THREE.LinearFilter;
    
    const spriteMaterial = new THREE.SpriteMaterial({ 
        map: texture, 
        transparent: true,
        depthTest: false // Siempre visible delante de otras mallas si se sobreponen un poco
    });
    
    const sprite = new THREE.Sprite(spriteMaterial);
    sprite.scale.set(60, 15, 1); // Escalar al tamaño visual correcto
    return sprite;
}


// ────────────────────────────────────────────────────────────────────────────
// ── MOTOR 2D CLÁSICO ────────────────────────────────────────────────────────
// ────────────────────────────────────────────────────────────────────────────

// ── Auto-Encuadre Inteligente (Fit View) ─────────────────────────────────────
function autoFitCamera() {
    if (viewMode.value !== '2D') return;
    const nodes = simulationNodes.value;
    const canvas = canvasRef.value;
    if (nodes.length === 0 || !canvas) {
        camera.value = { x: 0, y: 0, zoom: 1 };
        return;
    }

    const dpr = window.devicePixelRatio || 1;
    const width = Math.max(300, canvas.width / dpr);
    const height = Math.max(200, canvas.height / dpr);

    let minX = Infinity, maxX = -Infinity;
    let minY = Infinity, maxY = -Infinity;

    for (const n of nodes) {
        if (isFinite(n.x) && isFinite(n.y)) {
            minX = Math.min(minX, n.x - n.radius - 40);
            maxX = Math.max(maxX, n.x + n.radius + 40);
            minY = Math.min(minY, n.y - n.radius - 25);
            maxY = Math.max(maxY, n.y + n.radius + 35);
        }
    }

    const graphW = maxX - minX || 1;
    const graphH = maxY - minY || 1;
    const graphCenterX = (minX + maxX) / 2;
    const graphCenterY = (minY + maxY) / 2;

    const isMobile = window.innerWidth < 768;
    const scaleX = (width * (isMobile ? 0.85 : 0.78)) / graphW;
    const scaleY = (height * (isMobile ? 0.70 : 0.76)) / graphH;
    const targetZoom = Math.max(0.60, Math.min(1.25, Math.min(scaleX, scaleY)));

    const offsetX = (selectedNode.value && !isMobile) ? -120 : 0;
    const offsetY = (selectedNode.value && isMobile) ? -80 : 0;

    camera.value = {
        x: (width / 2 + offsetX) - graphCenterX * targetZoom,
        y: (height / 2 + offsetY) - graphCenterY * targetZoom,
        zoom: targetZoom,
    };
}

function centerCameraOnNode(node) {
    if (!node || !canvasRef.value) return;
    const dpr = window.devicePixelRatio || 1;
    const width = (canvasRef.value?.width || 800 * dpr) / dpr;
    const height = (canvasRef.value?.height || 600 * dpr) / dpr;

    const isMobile = window.innerWidth < 768;
    const offsetX = isMobile ? 0 : -130;
    const offsetY = isMobile ? -90 : 0;

    camera.value.zoom = Math.max(1.05, camera.value.zoom);
    camera.value.x = (width / 2 + offsetX) - node.x * camera.value.zoom;
    camera.value.y = (height / 2 + offsetY) - node.y * camera.value.zoom;
    reheatSimulation(0.3);
}

// ── Loop de Física & Renderizado Visual Continuo (60 FPS) ───────────────────
function initCanvas() {
    if (viewMode.value !== '2D') return;
    if (!canvasRef.value || !containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    if (rect.width > 0 && rect.height > 0) {
        const dpr = window.devicePixelRatio || 1;
        canvasRef.value.width = rect.width * dpr;
        canvasRef.value.height = rect.height * dpr;
    }
}

function reheatSimulation(targetAlpha = 0.5) {
    if (viewMode.value !== '2D') return;
    simAlpha = Math.max(simAlpha, targetAlpha);
    if (!animationFrameId) {
        startSimulation();
    }
}

function startSimulation() {
    if (viewMode.value !== '2D') return;
    if (animationFrameId) cancelAnimationFrame(animationFrameId);

    function loop() {
        synapsisTime += 0.018;

        const needsPhysics = simAlpha > SIM_MIN_ALPHA || draggedNode !== null;

        if (needsPhysics) {
            updatePhysics();
            if (draggedNode === null) {
                simAlpha *= SIM_DECAY;
                if (simAlpha <= SIM_MIN_ALPHA) {
                    simAlpha = 0;
                    for (const node of simulationNodes.value) {
                        node.vx = 0;
                        node.vy = 0;
                    }
                }
            }
        }

        renderCanvas();

        if (props.show && viewMode.value === '2D') {
            animationFrameId = requestAnimationFrame(loop);
        } else {
            animationFrameId = null;
        }
    }

    animationFrameId = requestAnimationFrame(loop);
}

function stopSimulation() {
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }
}

function updatePhysics() {
    const nodes = simulationNodes.value;
    const edges = simulationEdges.value;
    if (nodes.length === 0) return;

    const dpr = window.devicePixelRatio || 1;
    const canvas = canvasRef.value;
    const width = Math.max(500, (canvas?.width || 900 * dpr) / dpr);
    const height = Math.max(400, (canvas?.height || 650 * dpr) / dpr);
    const centerX = width / 2;
    const centerY = height / 2;

    const alpha = Math.max(0.015, simAlpha);

    // 1. Enlaces elásticos
    const targetDistance = 145;
    const linkStrength = 0.22;

    for (const edge of edges) {
        const n1 = nodes.find(n => n.id === edge.source);
        const n2 = nodes.find(n => n.id === edge.target);
        if (!n1 || !n2) continue;

        let dx = n2.x - n1.x;
        let dy = n2.y - n1.y;
        let dist = Math.sqrt(dx * dx + dy * dy) || 1;

        let diff = (dist - targetDistance) / dist;
        let force = diff * linkStrength * alpha * (edge.strength || 1);

        let fx = dx * force;
        let fy = dy * force;

        if (n1 !== draggedNode) {
            n1.vx += fx;
            n1.vy += fy;
        }
        if (n2 !== draggedNode) {
            n2.vx -= fx;
            n2.vy -= fy;
        }
    }

    // 2. Colisión suave
    const padding = 34;
    for (let i = 0; i < nodes.length; i++) {
        const n1 = nodes[i];
        for (let j = i + 1; j < nodes.length; j++) {
            const n2 = nodes[j];

            let dx = n2.x - n1.x;
            let dy = n2.y - n1.y;
            let dist = Math.sqrt(dx * dx + dy * dy) || 1;
            let minDist = n1.radius + n2.radius + padding;

            if (dist < minDist) {
                let overlap = ((minDist - dist) / dist) * 0.45 * alpha;
                let ox = dx * overlap;
                let oy = dy * overlap;

                if (n1 !== draggedNode) {
                    n1.vx -= ox;
                    n1.vy -= oy;
                }
                if (n2 !== draggedNode) {
                    n2.vx += ox;
                    n2.vy += oy;
                }
            }
        }
    }

    // 3. Gravedad al centro & amortiguación
    const centerStrength = 0.012 * alpha;
    for (const node of nodes) {
        if (node === draggedNode) continue;

        node.vx += (centerX - node.x) * centerStrength;
        node.vy += (centerY - node.y) * centerStrength;

        node.vx *= 0.68;
        node.vy *= 0.68;

        if (Math.abs(node.vx) < 0.02) node.vx = 0;
        if (Math.abs(node.vy) < 0.02) node.vy = 0;

        node.x += node.vx;
        node.y += node.vy;
    }
}

// ── RENDERIZADO CANVAS 2D CLÁSICO ──────────────────────────────────────────
function renderCanvas() {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const dpr = window.devicePixelRatio || 1;
    const width = Math.max(300, canvas.width / dpr);
    const height = Math.max(200, canvas.height / dpr);

    ctx.save();
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, width, height);

    // Fondo Cósmico
    const bgGradRadius = Math.max(300, Math.max(width, height));
    const bgGradient = ctx.createRadialGradient(
        Math.round(width / 2),
        Math.round(height / 2),
        20,
        Math.round(width / 2),
        Math.round(height / 2),
        Math.round(bgGradRadius)
    );
    bgGradient.addColorStop(0, '#0a0f1d');
    bgGradient.addColorStop(0.65, '#050711');
    bgGradient.addColorStop(1, '#020307');
    ctx.fillStyle = bgGradient;
    ctx.fillRect(0, 0, width, height);

    drawGrid(ctx, width, height);

    ctx.translate(camera.value.x, camera.value.y);
    ctx.scale(camera.value.zoom, camera.value.zoom);

    const nodes = simulationNodes.value;
    const edges = simulationEdges.value;

    // Gran Orbe Cerebral 2D
    drawCosmicBrainOrb(ctx, nodes, width, height);

    const activeCourseId = selectedCourseId.value;
    const activeSearch = searchQuery.value.trim().toLowerCase();

    // DIBUJAR ENLACES 2D
    for (let i = 0; i < edges.length; i++) {
        const edge = edges[i];
        const n1 = nodes.find(n => n.id === edge.source);
        const n2 = nodes.find(n => n.id === edge.target);
        if (!n1 || !n2) continue;

        const n1x = isFinite(n1.x) ? n1.x : width / 2;
        const n1y = isFinite(n1.y) ? n1.y : height / 2;
        const n2x = isFinite(n2.x) ? n2.x : width / 2;
        const n2y = isFinite(n2.y) ? n2.y : height / 2;

        const midX = (n1x + n2x) / 2;
        const midY = (n1y + n2y) / 2;
        const dx = n2x - n1x;
        const dy = n2y - n1y;
        const curveOffset = (i % 2 === 0 ? 1 : -1) * Math.min(18, Math.hypot(dx, dy) * 0.08);
        const cpX = midX - (dy / Math.hypot(dx, dy) || 0) * curveOffset;
        const cpY = midY + (dx / Math.hypot(dx, dy) || 0) * curveOffset;

        const isHighlighted =
            (selectedNode.value && (selectedNode.value.id === n1.id || selectedNode.value.id === n2.id)) ||
            (hoveredNode && (hoveredNode.id === n1.id || hoveredNode.id === n2.id));

        const matchesCourse = (!activeCourseId || n1.course_id === activeCourseId || n2.course_id === activeCourseId);
        const isDimmed = !matchesCourse || ((selectedNode.value || hoveredNode) && !isHighlighted);

        ctx.beginPath();
        ctx.moveTo(n1x, n1y);
        ctx.quadraticCurveTo(cpX, cpY, n2x, n2y);

        if (isHighlighted) {
            ctx.strokeStyle = '#c084fc';
            ctx.lineWidth = 2.4;
            ctx.shadowColor = '#a855f7';
            ctx.shadowBlur = 10;
        } else if (isDimmed) {
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.035)';
            ctx.lineWidth = 1;
            ctx.shadowBlur = 0;
        } else {
            const edgeGrad = ctx.createLinearGradient(n1x, n1y, n2x, n2y);
            edgeGrad.addColorStop(0, hexToRgba(n1.color, 0.35));
            edgeGrad.addColorStop(1, hexToRgba(n2.color, 0.35));
            ctx.strokeStyle = edgeGrad;
            ctx.lineWidth = 1.3;
            ctx.shadowBlur = 0;
        }
        ctx.stroke();
        ctx.shadowBlur = 0;

        // Pulso Sináptico de Energía 2D
        if (livePulseActive.value || isHighlighted) {
            const speed = isHighlighted ? 1.0 : 0.45;
            const t = (synapsisTime * speed + (i * 0.22)) % 1;
            const px = (1 - t) * (1 - t) * n1x + 2 * (1 - t) * t * cpX + t * t * n2x;
            const py = (1 - t) * (1 - t) * n1y + 2 * (1 - t) * t * cpY + t * t * n2y;

            ctx.beginPath();
            ctx.arc(px, py, isHighlighted ? 2.8 : 1.8, 0, Math.PI * 2);
            ctx.fillStyle = isHighlighted ? '#ffffff' : hexToRgba(n1.color, 0.85);
            ctx.shadowColor = isHighlighted ? '#d8b4fe' : n1.color;
            ctx.shadowBlur = isHighlighted ? 8 : 4;
            ctx.fill();
            ctx.shadowBlur = 0;
        }
    }

    // DIBUJAR NODOS 2D
    for (const node of nodes) {
        const isSelected = selectedNode.value?.id === node.id;
        const isHovered = hoveredNode?.id === node.id;
        const matchesCourse = !activeCourseId || node.course_id === activeCourseId;
        const matchesSearch = !activeSearch || node.label.toLowerCase().includes(activeSearch);

        const isDimmed = (!matchesCourse || !matchesSearch) || ((selectedNode.value || hoveredNode) && !isSelected && !isHovered && !isConnectedTo(node, selectedNode.value || hoveredNode));

        const nodeColor = resolveColor(node.color);
        const nx = isFinite(node.x) ? node.x : width / 2;
        const ny = isFinite(node.y) ? node.y : height / 2;
        const r = Math.max(12, isFinite(node.radius) ? node.radius + (isSelected || isHovered ? 4 : 0) : 15);

        ctx.save();
        ctx.globalAlpha = isDimmed ? 0.12 : 1;

        // A. Halo de Luz Ambiental / Pulso Neural
        const pulse = livePulseActive.value ? Math.sin(synapsisTime * 2 + (node.id.charCodeAt(node.id.length - 1) || 0)) * 2 : 0;
        const glowRadius = isSelected || isHovered ? r + 13 : r + 4 + Math.max(0, pulse);
        const glowGrad = ctx.createRadialGradient(nx, ny, r * 0.35, nx, ny, glowRadius);
        glowGrad.addColorStop(0, hexToRgba(nodeColor, isSelected || isHovered ? 0.6 : 0.25));
        glowGrad.addColorStop(1, hexToRgba(nodeColor, 0));

        ctx.beginPath();
        ctx.arc(nx, ny, glowRadius, 0, Math.PI * 2);
        ctx.fillStyle = glowGrad;
        ctx.fill();

        // B. Cuerpo de Cristal Oscuro
        const bodyGrad = ctx.createRadialGradient(nx - r * 0.25, ny - r * 0.25, r * 0.1, nx, ny, r);
        bodyGrad.addColorStop(0, '#1e293b');
        bodyGrad.addColorStop(0.7, '#0f172a');
        bodyGrad.addColorStop(1, '#020617');

        ctx.beginPath();
        ctx.arc(nx, ny, r, 0, Math.PI * 2);
        ctx.fillStyle = bodyGrad;
        ctx.fill();

        // C. Borde Luminoso
        ctx.strokeStyle = isSelected || isHovered ? '#ffffff' : nodeColor;
        ctx.lineWidth = isSelected || isHovered ? 2.8 : 1.8;
        if (isSelected || isHovered) {
            ctx.shadowColor = nodeColor;
            ctx.shadowBlur = 10;
        }
        ctx.stroke();
        ctx.shadowBlur = 0;

        // D. Núcleo Interior Radiante
        const dotRadius = Math.max(3, r * 0.3);
        ctx.beginPath();
        ctx.arc(nx, ny, dotRadius, 0, Math.PI * 2);
        ctx.fillStyle = nodeColor;
        ctx.fill();

        // E. TÍTULO FLOTANTE 2D
        ctx.font = `${isSelected || isHovered ? 'bold 11.5px' : '500 10.5px'} Inter, sans-serif`;
        ctx.fillStyle = isSelected || isHovered ? '#ffffff' : '#cbd5e1';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';

        ctx.shadowColor = '#000000';
        ctx.shadowBlur = 5;
        ctx.fillText(node.label, nx, ny + r + 6);

        ctx.restore();
    }

    ctx.restore();
}

function drawCosmicBrainOrb(ctx, nodes, width, height) {
    if (nodes.length === 0) return;

    let avgX = 0, avgY = 0;
    let maxDist = 0;
    for (const n of nodes) {
        avgX += n.x;
        avgY += n.y;
    }
    avgX /= nodes.length;
    avgY /= nodes.length;

    for (const n of nodes) {
        const d = Math.hypot(n.x - avgX, n.y - avgY) + n.radius;
        if (d > maxDist) maxDist = d;
    }

    const orbRadius = Math.max(220, maxDist + 75);

    const orbGrad = ctx.createRadialGradient(avgX, avgY, 10, avgX, avgY, orbRadius);
    orbGrad.addColorStop(0, 'rgba(99, 102, 241, 0.10)');
    orbGrad.addColorStop(0.5, 'rgba(168, 85, 247, 0.045)');
    orbGrad.addColorStop(0.85, 'rgba(56, 189, 248, 0.02)');
    orbGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');

    ctx.beginPath();
    ctx.arc(avgX, avgY, orbRadius, 0, Math.PI * 2);
    ctx.fillStyle = orbGrad;
    ctx.fill();

    ctx.save();
    ctx.translate(avgX, avgY);

    const rotAngle = livePulseActive.value ? synapsisTime * 0.025 : 0;
    ctx.rotate(rotAngle);

    ctx.setLineDash([8, 12]);
    ctx.strokeStyle = 'rgba(168, 85, 247, 0.35)';
    ctx.lineWidth = 1.6;
    ctx.shadowColor = 'rgba(168, 85, 247, 0.3)';
    ctx.shadowBlur = 6;
    ctx.beginPath();
    ctx.arc(0, 0, orbRadius, 0, Math.PI * 2);
    ctx.stroke();

    for (let p = 0; p < 4; p++) {
        const pAngle = (synapsisTime * 0.15 + (p * Math.PI / 2)) % (Math.PI * 2);
        const px = Math.cos(pAngle) * orbRadius;
        const py = Math.sin(pAngle) * orbRadius;
        ctx.beginPath();
        ctx.arc(px, py, 2.2, 0, Math.PI * 2);
        ctx.fillStyle = '#e9d5ff';
        ctx.shadowColor = '#c084fc';
        ctx.shadowBlur = 8;
        ctx.fill();
    }

    ctx.rotate(-rotAngle * 2);
    ctx.setLineDash([5, 10]);
    ctx.strokeStyle = 'rgba(99, 102, 241, 0.32)';
    ctx.lineWidth = 1.3;
    ctx.shadowColor = 'rgba(99, 102, 241, 0.25)';
    ctx.shadowBlur = 4;
    ctx.beginPath();
    ctx.arc(0, 0, orbRadius - 18, 0, Math.PI * 2);
    ctx.stroke();

    ctx.restore();
}

function drawGrid(ctx, width, height) {
    const gridSize = 48 * camera.value.zoom;
    const offsetX = camera.value.x % gridSize;
    const offsetY = camera.value.y % gridSize;

    ctx.strokeStyle = 'rgba(255, 255, 255, 0.02)';
    ctx.lineWidth = 1;

    for (let x = offsetX; x < width; x += gridSize) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, height);
        ctx.stroke();
    }
    for (let y = offsetY; y < height; y += gridSize) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(width, y);
        ctx.stroke();
    }
}

// ── Interacciones 2D ────────────────────────────────────────────────────────
function screenToWorld(clientX, clientY) {
    const rect = canvasRef.value.getBoundingClientRect();
    const x = (clientX - rect.left - camera.value.x) / camera.value.zoom;
    const y = (clientY - rect.top - camera.value.y) / camera.value.zoom;
    return { x, y };
}

function findNodeAt(worldX, worldY) {
    let closestNode = null;
    let minDistance = Infinity;
    
    // Aumentar padding táctil en móviles para mejorar la accesibilidad
    const touchPadding = isMobileDevice.value ? 35 : 20;

    for (let i = simulationNodes.value.length - 1; i >= 0; i--) {
        const n = simulationNodes.value[i];
        const dx = n.x - worldX;
        const dy = n.y - worldY;
        const distToCircle = Math.sqrt(dx * dx + dy * dy);

        if (distToCircle <= n.radius + touchPadding) {
            if (distToCircle < minDistance) {
                minDistance = distToCircle;
                closestNode = n;
            }
        }

        const textHalfWidth = Math.max(50, (n.label ? n.label.length * 4.4 : 50)) + touchPadding / 2;
        if (Math.abs(dx) <= textHalfWidth && dy >= -touchPadding && dy <= n.radius + 25 + touchPadding) {
            const distToText = Math.abs(dx) + Math.abs(dy);
            if (distToText < minDistance) {
                minDistance = distToText;
                closestNode = n;
            }
        }
    }
    return closestNode;
}

function handlePointerDown(e) {
    if (viewMode.value !== '2D') return;
    const { x, y } = screenToWorld(e.clientX, e.clientY);
    const hitNode = findNodeAt(x, y);

    pointerDownPos = { x: e.clientX, y: e.clientY };
    hasDragged = false;

    if (hitNode) {
        draggedNode = hitNode;
        reheatSimulation(0.6);
    } else {
        isPanning = true;
        panStart = { x: e.clientX - camera.value.x, y: e.clientY - camera.value.y };
    }

    try {
        e.currentTarget.setPointerCapture(e.pointerId);
    } catch (_) {}
}

function handlePointerMove(e) {
    if (viewMode.value !== '2D') return;
    const dx = e.clientX - pointerDownPos.x;
    const dy = e.clientY - pointerDownPos.y;
    if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
        hasDragged = true;
    }

    const { x, y } = screenToWorld(e.clientX, e.clientY);

    if (draggedNode) {
        draggedNode.x = x;
        draggedNode.y = y;
        draggedNode.vx = 0;
        draggedNode.vy = 0;
        reheatSimulation(0.4);
    } else if (isPanning) {
        camera.value.x = e.clientX - panStart.x;
        camera.value.y = e.clientY - panStart.y;
    } else {
        hoveredNode = findNodeAt(x, y);
        if (canvasRef.value) {
            canvasRef.value.style.cursor = hoveredNode ? 'pointer' : (isPanning ? 'grabbing' : 'grab');
        }
    }
}

function handlePointerUp(e) {
    if (viewMode.value !== '2D') return;
    const { x, y } = screenToWorld(e.clientX, e.clientY);
    const hitNode = findNodeAt(x, y);

    if (!hasDragged) {
        const targetNode = draggedNode || hitNode;
        if (targetNode) {
            selectedNode.value = targetNode;
        } else {
            selectedNode.value = null;
        }
    } else if (draggedNode) {
        triggerSavePositions();
    }

    draggedNode = null;
    isPanning = false;
    reheatSimulation(0.25);

    try {
        if (e.currentTarget?.releasePointerCapture) {
            e.currentTarget.releasePointerCapture(e.pointerId);
        }
    } catch (_) {}
}

function handleWheel(e) {
    if (viewMode.value !== '2D') return;
    e.preventDefault();
    const zoomFactor = e.deltaY < 0 ? 1.12 : 0.88;
    const newZoom = Math.max(0.35, Math.min(2.8, camera.value.zoom * zoomFactor));

    const rect = canvasRef.value.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;

    camera.value.x = mouseX - (mouseX - camera.value.x) * (newZoom / camera.value.zoom);
    camera.value.y = mouseY - (mouseY - camera.value.y) * (newZoom / camera.value.zoom);
    camera.value.zoom = newZoom;
}

function triggerSavePositions() {
    clearTimeout(savePositionsTimeout);
    savePositionsTimeout = setTimeout(async () => {
        try {
            const positions = simulationNodes.value.map(n => ({
                id: n.id,
                x: Math.round(n.x * 10) / 10,
                y: Math.round(n.y * 10) / 10,
            }));
            await axios.post('/api/calendar/knowledge-graph/positions', { positions });
        } catch (e) {
            console.error('Error guardando posiciones:', e);
        }
    }, 1200);
}

// ── Helpers Globales ────────────────────────────────────────────────────────
function zoomIn() {
    if (viewMode.value === '2D') {
        camera.value.zoom = Math.min(2.8, camera.value.zoom * 1.25);
    } else if (graph3DInstance) {
        const p = graph3DInstance.cameraPosition();
        graph3DInstance.cameraPosition({ x: p.x * 0.8, y: p.y * 0.8, z: p.z * 0.8 }, null, 600);
    }
}

function zoomOut() {
    if (viewMode.value === '2D') {
        camera.value.zoom = Math.max(0.35, camera.value.zoom * 0.8);
    } else if (graph3DInstance) {
        const p = graph3DInstance.cameraPosition();
        graph3DInstance.cameraPosition({ x: p.x * 1.25, y: p.y * 1.25, z: p.z * 1.25 }, null, 600);
    }
}

function selectCourseAndFilter(courseId) {
    if (selectedCourseId.value === courseId) {
        selectedCourseId.value = null;
        if (viewMode.value === '2D') {
            autoFitCamera();
        } else if (graph3DInstance) {
            graph3DInstance.cameraPosition({ z: 450 }, { x: 0, y: 0, z: 0 }, 1500);
        }
        return;
    }
    selectedCourseId.value = courseId;
    selectedNode.value = null;

    const courseNodes = simulationNodes.value.filter(n => n.course_id === courseId);
    if (courseNodes.length > 0) {
        let avgX = 0, avgY = 0, avgZ = 0;
        for (const cn of courseNodes) {
            avgX += cn.x; avgY += cn.y; avgZ += (cn.z || 0);
        }
        avgX /= courseNodes.length; avgY /= courseNodes.length; avgZ /= courseNodes.length;

        if (viewMode.value === '2D') {
            const dpr = window.devicePixelRatio || 1;
            const width = (canvasRef.value?.width || 800 * dpr) / dpr;
            const height = (canvasRef.value?.height || 600 * dpr) / dpr;
            camera.value.zoom = 1.15;
            camera.value.x = width / 2 - avgX * camera.value.zoom;
            camera.value.y = height / 2 - avgY * camera.value.zoom;
        } else if (graph3DInstance) {
            graph3DInstance.cameraPosition({ x: avgX * 1.5, y: avgY * 1.5, z: avgZ * 1.5 + 200 }, { x: avgX, y: avgY, z: avgZ }, 1500);
        }
    }
}

function update3DVisibility() {
    if (viewMode.value === '3D' && graph3DInstance) {
        const activeCourse = selectedCourseId.value;
        const activeSearch = searchQuery.value.trim().toLowerCase();
        
        graph3DInstance.nodeVisibility(node => {
            if (activeCourse && node.course_id !== activeCourse) return false;
            if (activeSearch && !node.label.toLowerCase().includes(activeSearch)) return false;
            return true;
        });

        graph3DInstance.linkVisibility(link => {
            let sNode = typeof link.source === 'object' ? link.source : simulationNodes.value.find(n => n.id === link.source);
            let tNode = typeof link.target === 'object' ? link.target : simulationNodes.value.find(n => n.id === link.target);
            if (!sNode || !tNode) return true;
            if (activeCourse && (sNode.course_id !== activeCourse && tNode.course_id !== activeCourse)) return false;
            if (activeSearch && (!sNode.label.toLowerCase().includes(activeSearch) && !tNode.label.toLowerCase().includes(activeSearch))) return false;
            return true;
        });
    }
}

watch([selectedCourseId, searchQuery], () => {
    update3DVisibility();
    if (viewMode.value === '2D') {
        reheatSimulation(0.3); // Despertar simulación para que se repinte suave
    }
});

function selectNodeAndCenter(node) {
    if (!node) return;
    selectedNode.value = node; // El watcher ya maneja el Fly-To / auto-encuadre
}

// ── Conexiones Clasificadas Pedagógicamente ──────────────────────────────────
const classifiedConnections = computed(() => {
    if (!selectedNode.value) return { prerequisites: [], applications: [], crossDisciplinary: [] };
    const id = selectedNode.value.id;

    const all = simulationEdges.value
        .filter(e => e.source === id || e.target === id || (e.source?.id === id) || (e.target?.id === id))
        .map(e => {
            const isSource = e.source === id || e.source?.id === id;
            const otherId = isSource ? (e.target?.id || e.target) : (e.source?.id || e.source);
            const otherNode = simulationNodes.value.find(n => n.id === otherId);
            return {
                edge: e,
                direction: isSource ? 'saliente' : 'entrante',
                node: otherNode,
                isCross: otherNode && otherNode.course_id !== selectedNode.value.course_id,
            };
        })
        .filter(c => c.node !== undefined);

    return {
        prerequisites: all.filter(c => !c.isCross && (c.edge.type === 'requisito' || c.direction === 'entrante')),
        applications: all.filter(c => !c.isCross && (c.edge.type === 'aplicacion' || c.direction === 'saliente')),
        crossDisciplinary: all.filter(c => c.isCross || c.edge.type === 'interdisciplinario'),
    };
});

function isConnectedTo(nodeA, nodeB) {
    if (!nodeA || !nodeB) return false;
    return simulationEdges.value.some(
        e => ((e.source?.id || e.source) === nodeA.id && (e.target?.id || e.target) === nodeB.id) ||
             ((e.source?.id || e.source) === nodeB.id && (e.target?.id || e.target) === nodeA.id)
    );
}

function handleOpenNote(courseId) {
    emit('openNote', courseId);
    emit('close');
}

const isCopyingForNotebookLM = ref(false);

function copyForNotebookLM(targetCourseId = null) {
    const courseId = targetCourseId || selectedNode.value?.course_id || selectedCourseId.value;
    let text = '';

    if (courseId) {
        const course = graphData.value.courses?.find(c => c.id === courseId);
        const courseName = course?.name || selectedNode.value?.course_name || 'Curso';
        const courseNodes = graphData.value.nodes?.filter(n => n.course_id === courseId) || [];

        text += `# 🧠 Apuntes & Chunks de Aprendizaje — ${courseName}\n`;
        text += `> Fuente estructurada para Google NotebookLM generada desde Epycus 2.0\n\n`;

        if (course?.notes_content && course.notes_content !== 'Sin apuntes registrados aún por el estudiante.') {
            text += `## 📝 Apuntes Oficiales\n${course.notes_content}\n\n`;
        }

        if (courseNodes.length > 0) {
            text += `## 🧩 Conceptos Clave & Chunks (Segundo Cerebro)\n\n`;
            courseNodes.forEach((n, idx) => {
                text += `### ${idx + 1}. ${n.label}\n`;
                text += `- **Concepto Clave:** ${n.summary || 'Sin resumen registrado.'}\n`;
                if (n.why_it_matters) {
                    text += `- **Aplicación Profesional:** ${n.why_it_matters}\n`;
                }
                if (n.quiz_question) {
                    text += `- **Active Recall (Autoevaluación):** ${n.quiz_question}\n`;
                    text += `  - *Respuesta Clave:* ${n.quiz_answer || n.summary}\n`;
                }
                text += `\n`;
            });
        }
    } else {
        text += `# 🧠 Ecosistema de Aprendizaje & Segundo Cerebro — Epycus\n`;
        text += `> Resumen de todas las asignaturas y conceptos clave para Google NotebookLM\n\n`;
        (graphData.value.courses || []).forEach(c => {
            text += `## 📚 ${c.name}\n`;
            const courseNodes = graphData.value.nodes?.filter(n => n.course_id === c.id) || [];
            if (courseNodes.length > 0) {
                courseNodes.forEach(n => {
                    text += `- **${n.label}:** ${n.summary}\n`;
                    if (n.why_it_matters) text += `  - *Aplicación:* ${n.why_it_matters}\n`;
                });
            } else {
                text += `- *Sin conceptos generados aún.*\n`;
            }
            text += `\n`;
        });
    }

    navigator.clipboard.writeText(text).then(() => {
        isCopyingForNotebookLM.value = true;
        successMessage.value = '¡Chunks copiados en Markdown listos para pegar en NotebookLM!';
        setTimeout(() => {
            isCopyingForNotebookLM.value = false;
            successMessage.value = '';
        }, 4000);
    }).catch(() => {
        errorMessage.value = 'No se pudo copiar automáticamente al portapapeles.';
        setTimeout(() => { errorMessage.value = ''; }, 3000);
    });
}

function handleKeydown(e) {
    if (!props.show) return;
    
    // Ignorar si el foco está en un input
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

    if (e.key === '+' || e.key === '=') {
        zoomIn();
    } else if (e.key === '-') {
        zoomOut();
    }
}

onMounted(() => {
    window.addEventListener('resize', () => {
        if (viewMode.value === '2D') {
            initCanvas();
            autoFitCamera();
        } else {
            handle3DResize();
        }
    });
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.body.style.overflow = '';
    window.removeEventListener('resize', initCanvas);
    window.removeEventListener('resize', handle3DResize);
    window.removeEventListener('keydown', handleKeydown);
    clearTimeout(savePositionsTimeout);
    stopSimulation();
    destroy3DGraph();
});
</script>

<template>
    <div
        v-if="show"
        class="fixed top-0 left-0 z-[9999] flex bg-black/85 transition-all duration-300"
        :class="isFullscreen ? 'w-screen h-screen p-0 backdrop-blur-none' : 'inset-0 items-center justify-center p-0 sm:p-3 md:p-5 backdrop-blur-md'"
        tabindex="0"
    >
        <div
            ref="containerRef"
            class="relative flex flex-col bg-slate-950/98 border-slate-800 shadow-2xl overflow-hidden glass-panel transition-all"
            :class="isFullscreen ? 'w-screen h-screen max-w-none rounded-none border-0' : 'w-full h-full sm:h-[94vh] max-w-7xl border-0 sm:border rounded-none sm:rounded-3xl'"
        >
            <!-- ── BARRA SUPERIOR HEADER ────────────────────────────────────── -->
            <div class="flex items-center justify-between px-3.5 sm:px-5 py-3 bg-slate-900/90 border-b border-slate-800/80 z-20 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="p-2 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 text-indigo-400 shrink-0 shadow-lg shadow-indigo-500/10">
                        <Brain class="w-5 h-5" />
                    </div>
                    <div class="truncate">
                        <h2 class="text-sm sm:text-base md:text-lg font-black text-white truncate">
                            Grafo de Conocimiento
                        </h2>
                        <p class="hidden sm:block text-xs text-slate-400">
                            Tú Segundo cerebro
                        </p>
                    </div>
                </div>

                <!-- Botones de Acción Header -->
                <div class="flex items-center gap-2 shrink-0">
                    
                    <!-- Toggle 2D / 3D -->
                    <div class="flex bg-slate-800/80 p-0.5 rounded-xl border border-slate-700/50" role="group" aria-label="Alternar modo visual">
                        <button
                            type="button"
                            aria-pressed="viewMode === '2D'"
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg text-[10px] sm:text-xs font-bold transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="viewMode === '2D' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                            @click="viewMode = '2D'"
                        >
                            2D
                        </button>
                        <button
                            type="button"
                            aria-pressed="viewMode === '3D'"
                            class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg text-[10px] sm:text-xs font-bold transition-all flex items-center gap-1 sm:gap-1.5 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            :class="viewMode === '3D' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                            @click="viewMode = '3D'"
                        >
                            <Box class="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                            3D
                        </button>
                    </div>

                    <!-- Botón Modo Sinapsis Viva / Pulso Neural -->
                    <button
                        type="button"
                        aria-label="Alternar animación de sinapsis"
                        class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border select-none focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :class="livePulseActive
                            ? 'bg-indigo-600/20 border-indigo-500/50 text-indigo-300 shadow-sm shadow-indigo-500/20'
                            : 'bg-slate-800/60 border-slate-700/50 text-slate-400 hover:text-white'"
                        @click="livePulseActive = !livePulseActive"
                        :title="livePulseActive ? 'Pausar animación de sinapsis' : 'Activar animación de sinapsis'"
                    >
                        <Activity class="w-3.5 h-3.5" :class="livePulseActive ? 'animate-pulse text-indigo-400' : ''" />
                        <span class="hidden md:inline">{{ livePulseActive ? 'Sinapsis Activa' : 'Sinapsis Pausada' }}</span>
                    </button>

                    <!-- Botón NotebookLM Quick Link -->
                    <a
                        href="https://notebooklm.google.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 shadow-sm transition-all select-none"
                        title="Abrir Google NotebookLM en nueva pestaña"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="#E8F0FE"/>
                            <path d="M7 6.5h10a1.5 1.5 0 0 1 1.5 1.5v8a1.5 1.5 0 0 1-1.5 1.5H7A1.5 1.5 0 0 1 5.5 16V8A1.5 1.5 0 0 1 7 6.5z" fill="#1A73E8"/>
                            <path d="M8.5 9.5h7M8.5 12h5M8.5 14.5h4" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                            <circle cx="16.5" cy="14.5" r="1" fill="#34A853"/>
                        </svg>
                        <span>NotebookLM</span>
                        <ExternalLink class="w-3 h-3 text-slate-400" />
                    </a>

                    <!-- Maximizar Pantalla (Desktop) -->
                    <button
                        type="button"
                        aria-label="Alternar pantalla completa"
                        class="hidden sm:flex p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                        @click="isFullscreen = !isFullscreen"
                        title="Pantalla completa"
                    >
                        <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
                        <Maximize2 v-else class="w-4 h-4" />
                    </button>

                    <!-- Cerrar Modal -->
                    <button
                        type="button"
                        aria-label="Cerrar modal del grafo"
                        class="p-2 rounded-xl bg-slate-800/80 hover:bg-rose-500/20 hover:text-rose-400 text-slate-300 transition-colors"
                        @click="emit('close')"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- ── BARRA DE CURSOS CON COLORES VIVOS Y FILTROS ───────────────── -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 px-3.5 sm:px-5 py-2.5 bg-slate-900/60 border-b border-slate-800/60 z-20 shrink-0 text-xs">
                <!-- Filtro por Cursos con Badges de Color -->
                <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar py-0.5 max-w-full">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold transition-all shrink-0 text-xs"
                        :class="selectedCourseId === null
                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30'
                            : 'bg-slate-800/70 text-slate-400 hover:bg-slate-800 hover:text-slate-200 border border-slate-700/50'"
                        @click="selectCourseAndFilter(null)"
                    >
                        <Network class="w-3 h-3 text-indigo-300" />
                        <span>Todos</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-black/30 text-[10px] font-black">
                            {{ graphData.stats.total_concepts }}
                        </span>
                    </button>

                    <div
                        v-for="(course, idx) in graphData.courses"
                        :key="course.id"
                        class="inline-flex items-center rounded-xl border transition-all shrink-0 text-xs overflow-hidden group/course"
                        :class="selectedCourseId === course.id
                            ? 'bg-slate-800 text-white shadow-lg border-white/40'
                            : 'bg-slate-800/40 text-slate-300 hover:bg-slate-800 hover:text-white border-slate-700/50'"
                    >
                        <button
                            type="button"
                            class="flex items-center gap-2 pl-3 pr-2 py-1.5 font-bold focus:outline-none"
                            @click="selectCourseAndFilter(course.id)"
                        >
                            <span
                                class="w-2.5 h-2.5 rounded-full shadow-md shrink-0"
                                :style="{ backgroundColor: resolveColor(course.color, idx) }"
                            />
                            <span class="truncate max-w-[120px]">{{ course.name }}</span>
                            <span
                                v-if="courseConceptCounts[course.id]"
                                class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                                :style="{
                                    backgroundColor: hexToRgba(resolveColor(course.color, idx), 0.25),
                                    color: resolveColor(course.color, idx)
                                }"
                            >
                                {{ courseConceptCounts[course.id] }}
                            </span>
                        </button>
                        
                        <!-- Botón directo para abrir apuntes de este curso -->
                        <button
                            type="button"
                            class="px-2 py-1.5 border-l border-slate-700/60 hover:bg-indigo-600/30 text-slate-400 hover:text-indigo-200 transition-colors focus:outline-none"
                            :title="`Abrir apuntes de ${course.name}`"
                            @click.stop="handleOpenNote(course.id)"
                        >
                            <BookOpen class="w-3.5 h-3.5 text-indigo-400 group-hover/course:text-indigo-300" />
                        </button>
                    </div>
                </div>

                <!-- Buscador de Conceptos -->
                <div class="relative w-full sm:w-60 shrink-0">
                    <Search class="absolute left-3 top-2.5 w-3.5 h-3.5 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Buscar concepto..."
                        class="w-full pl-9 pr-3 py-1.5 bg-slate-950/80 border border-slate-700/60 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors shadow-inner"
                    />
                    <button
                        v-if="searchQuery"
                        class="absolute right-2.5 top-2 text-slate-400 hover:text-white"
                        @click="searchQuery = ''"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <!-- Notificaciones / Mensajes -->
            <div v-if="successMessage" class="absolute top-24 left-1/2 transform -translate-x-1/2 z-30 px-4 py-2 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs font-semibold shadow-xl backdrop-blur-md animate-fade-in flex items-center gap-2">
                <Sparkles class="w-4 h-4 text-emerald-400 shrink-0" />
                <span>{{ successMessage }}</span>
            </div>
            <div v-if="errorMessage" class="absolute top-24 left-1/2 transform -translate-x-1/2 z-30 px-4 py-2 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold shadow-xl backdrop-blur-md animate-fade-in flex items-center gap-2">
                <Info class="w-4 h-4 text-rose-400 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>

            <!-- ── ÁREA PRINCIPAL DEL CANVAS DEL GRAFO ───────────────────────── -->
            <div class="relative flex-1 w-full h-full overflow-hidden touch-none select-none">
                
                <!-- CANVAS 2D -->
                <canvas
                    v-show="viewMode === '2D'"
                    ref="canvasRef"
                    class="absolute inset-0 w-full h-full cursor-grab active:cursor-grabbing block"
                    @pointerdown="handlePointerDown"
                    @pointermove="handlePointerMove"
                    @pointerup="handlePointerUp"
                    @pointercancel="handlePointerUp"
                    @wheel="handleWheel"
                />

                <!-- CANVAS 3D Holográfico -->
                <div 
                    v-show="viewMode === '3D'"
                    ref="graph3DContainer" 
                    class="absolute inset-0 w-full h-full cursor-grab active:cursor-grabbing block"
                />

                <!-- Estado Vacío (Sin Grafo Generado) -->
                <div
                    v-if="!loading && !graphData.has_graph"
                    class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-slate-950/85 backdrop-blur-sm z-10"
                >
                    <div class="p-4 rounded-3xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 mb-4 animate-bounce shadow-xl shadow-indigo-500/10">
                        <Brain class="w-12 h-12" />
                    </div>
                    <h3 class="text-xl font-black text-white mb-2">Construye tu Segundo Cerebro</h3>
                    <p class="text-xs sm:text-sm text-slate-400 max-w-md mb-6 leading-relaxed">
                        Nuestra IA analizará tus materias y notas para descubrir relaciones pedagógicas, interconexiones y preguntas de autoevaluación.
                    </p>
                    <button
                        type="button"
                        :disabled="generating || graphData.quota.is_exhausted"
                        class="flex items-center gap-2 px-6 py-3 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 hover:from-indigo-500 hover:to-purple-500 shadow-xl shadow-indigo-600/30 transition-all border border-indigo-400/40 hover:scale-105 active:scale-95 disabled:opacity-50"
                        @click="handleGenerateGraph"
                    >
                        <Loader2 v-if="generating" class="w-4 h-4 animate-spin" />
                        <Sparkles v-else class="w-4 h-4 text-yellow-300" />
                        <span>{{ generating ? 'Construyendo...' : '✨ Conectar Apuntes con IA' }}</span>
                    </button>
                </div>

                <!-- Controles Flotantes de Zoom & Auto-Encuadre -->
                <div class="absolute bottom-4 left-4 z-20 flex flex-col gap-1 bg-slate-900/85 backdrop-blur-md border border-slate-800 p-1 rounded-2xl shadow-xl glass-panel-sm">
                    <button
                        type="button"
                        class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
                        @click="zoomIn"
                        title="Acercar"
                    >
                        <ZoomIn class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
                        @click="zoomOut"
                        title="Alejar"
                    >
                        <ZoomOut class="w-4 h-4" />
                    </button>
                    <button
                        v-if="viewMode === '2D'"
                        type="button"
                        class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
                        @click="autoFitCamera"
                        title="Encuadre perfecto (Auto-Fit)"
                    >
                        <Compass class="w-4 h-4 text-indigo-400" />
                    </button>
                </div>

                <!-- Leyenda de Ayuda e Interacción -->
                <div class="absolute bottom-4 right-4 z-20 hidden md:flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-900/80 backdrop-blur-md border border-slate-800 text-xs text-slate-300 shadow-xl glass-panel-sm">
                    <span class="text-slate-400">💡 <strong>W, A, S, D</strong> mover • <strong>+ / -</strong> zoom • <strong>Arrastra</strong> explorar</span>
                    <span class="text-slate-700">|</span>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400" />
                        <span><strong>{{ graphData.stats.total_concepts }}</strong> conceptos</span>
                        <span class="text-slate-600">•</span>
                        <span class="w-2 h-2 rounded-full bg-indigo-400" />
                        <span><strong>{{ graphData.stats.total_connections }}</strong> conexiones</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
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

/* Glassmorphism balanceado */
.glass-panel {
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.03), 0 4px 16px 0 rgba(0, 0, 0, 0.3);
}
.glass-panel-sm {
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05), 0 2px 8px 0 rgba(0, 0, 0, 0.2);
}
</style>
