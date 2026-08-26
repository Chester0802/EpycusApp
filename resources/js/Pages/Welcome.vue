<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppIcon from '@/Components/AppIcon.vue';
import { triggerHapticVibration } from '@/utils/celebration';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

// Modo Claro / Modo Oscuro
const isDark = ref(true);

function toggleTheme() {
    isDark.value = !isDark.value;
    const theme = isDark.value ? 'dark' : 'light';
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('data-theme', theme);
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        try {
            localStorage.setItem('epycus.theme', theme);
        } catch {
            // Ignorar errores de almacenamiento local
        }
    }
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('epycus.theme');
        if (saved) {
            isDark.value = saved === 'dark';
        } else {
            isDark.value = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? true;
        }
        const theme = isDark.value ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        window.addEventListener('keydown', handleKeyDown);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleKeyDown);
    }
});

// Buzón de la Comunidad
const feedbackType = ref('suggestion');
const feedbackName = ref('');
const feedbackEmail = ref('');
const feedbackMessage = ref('');
const feedbackImage = ref(null);
const feedbackImagePreview = ref(null);
const feedbackImageInput = ref(null);
const feedbackSubmitting = ref(false);
const feedbackSuccess = ref(false);
const feedbackError = ref('');

const feedbackCategories = [
    { id: 'suggestion', label: 'Sugerencia de Mejora', icon: 'zap' },
    { id: 'idea', label: 'Nueva Idea / Función', icon: 'sparkles' },
    { id: 'bug', label: 'Reporte de Error', icon: 'shield' },
    { id: 'gratitude', label: 'Agradecimiento', icon: 'heart' },
];

const isDraggingFeedback = ref(false);

function processFeedbackImageFile(file) {
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        feedbackError.value = 'Por favor selecciona o pega un archivo de imagen válido (PNG, JPG, WEBP).';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        feedbackError.value = 'La imagen no debe superar los 5MB de tamaño.';
        return;
    }

    feedbackError.value = '';
    const fileName = file.name && file.name !== 'image.png' ? file.name : `captura_${new Date().toISOString().slice(0, 10)}.png`;
    const namedFile = new File([file], fileName, { type: file.type || 'image/png' });
    feedbackImage.value = namedFile;

    const reader = new FileReader();
    reader.onload = (event) => {
        feedbackImagePreview.value = event.target?.result;
    };
    reader.readAsDataURL(file);
    triggerHapticVibration([30, 40]);
}

function onImageChange(e) {
    const file = e.target.files?.[0];
    if (file) {
        processFeedbackImageFile(file);
    }
}

function handleFeedbackPaste(e) {
    const items = e.clipboardData?.items;
    if (!items) return;

    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        if (item.type.indexOf('image') !== -1) {
            const file = item.getAsFile();
            if (file) {
                e.preventDefault();
                processFeedbackImageFile(file);
                break;
            }
        }
    }
}

function onFeedbackDragOver(e) {
    e.preventDefault();
    isDraggingFeedback.value = true;
}

function onFeedbackDragLeave(e) {
    e.preventDefault();
    isDraggingFeedback.value = false;
}

function onFeedbackDrop(e) {
    e.preventDefault();
    isDraggingFeedback.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) {
        processFeedbackImageFile(file);
    }
}

function removeImage() {
    feedbackImage.value = null;
    feedbackImagePreview.value = null;
    isDraggingFeedback.value = false;
    if (feedbackImageInput.value) {
        feedbackImageInput.value.value = '';
    }
}

async function sendFeedback() {
    if (!feedbackMessage.value.trim() || feedbackSubmitting.value) return;
    feedbackSubmitting.value = true;
    feedbackError.value = '';
    feedbackSuccess.value = false;

    try {
        const formData = new FormData();
        formData.append('type', feedbackType.value);
        formData.append('name', feedbackName.value);
        formData.append('email', feedbackEmail.value);
        formData.append('message', feedbackMessage.value);
        if (feedbackImage.value) {
            formData.append('image', feedbackImage.value);
        }

        await axios.post('/feedback', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        feedbackSuccess.value = true;
        feedbackMessage.value = '';
        feedbackName.value = '';
        feedbackEmail.value = '';
        removeImage();
        triggerHapticVibration([40, 60, 40]);
        setTimeout(() => {
            feedbackSuccess.value = false;
        }, 10000);
    } catch {
        feedbackError.value = 'No se pudo enviar el mensaje en este momento. Puedes escribirnos directamente a contacto@soltectos.com';
    } finally {
        feedbackSubmitting.value = false;
    }
}

const appLoginUrl = computed(() => {
    if (typeof window !== 'undefined' && window.location.hostname === 'epycus.es') {
        return 'https://app.epycus.es/login';
    }
    return route('login');
});

const appRegisterUrl = computed(() => {
    if (typeof window !== 'undefined' && window.location.hostname === 'epycus.es') {
        return 'https://app.epycus.es/register';
    }
    return route('register');
});

// Pestaña activa en el Bento Grid
const activeTab = ref('pomodoro');

function selectTab(tabId) {
    activeTab.value = tabId;
}

// Acordeón de preguntas frecuentes
const activeFaq = ref(null);
function toggleFaq(index) {
    activeFaq.value = activeFaq.value === index ? null : index;
}

// Acordeón de conceptos clave / glosario
const activeConcept = ref(0);
function toggleConcept(index) {
    activeConcept.value = activeConcept.value === index ? null : index;
}

// Catálogo de los 5 Villanos Académicos (Imágenes ASCII web-safe)
const villains = [
    {
        name: 'La Postergación',
        image: '/assets/villains/villano-postergacion.png',
        tagline: '«Lo hago más tarde, todavía hay tiempo»',
        effect: 'Te hace aplazar el inicio de trabajos decisivos hasta la madrugada previa a la entrega.',
        weakness: 'Misiones con Subtareas',
        icon: 'clipboard',
        border: 'hover:border-amber-500/50',
        badge: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
    },
    {
        name: 'La Distracción',
        image: '/assets/villains/villano-distraccion.png',
        tagline: '«Solo un video corto y me pongo a estudiar»',
        effect: 'Roba tu concentración con notificaciones continuas, redes sociales y dispersión digital.',
        weakness: 'Bloques Pomodoro de 25 min',
        icon: 'timer',
        border: 'hover:border-indigo-500/50',
        badge: 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
    },
    {
        name: 'La Ansiedad',
        image: '/assets/villains/villano-ansiedad.png',
        tagline: '«¿Y si no me alcanza el tiempo para todo?»',
        effect: 'Bloqueo mental y parálisis por sobrepensar la magnitud de los exámenes y entregas.',
        weakness: 'Diario de Bienestar & Hábitos',
        icon: 'leaf',
        border: 'hover:border-rose-500/50',
        badge: 'bg-rose-500/15 text-rose-300 border-rose-500/30',
    },
    {
        name: 'El Desorden',
        image: '/assets/villains/villano-desorden.png',
        tagline: '«¿Para cuándo era esta práctica calificada?»',
        effect: 'Apuntes dispersos, cruces de horarios y pérdida de prioridades a mitad del semestre.',
        weakness: 'Gestor de Cursos & Horarios',
        icon: 'calendar',
        border: 'hover:border-cyan-500/50',
        badge: 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30',
    },
    {
        name: 'El Cansancio',
        image: '/assets/villains/villano-cansancio.png',
        tagline: '«No doy más, mejor estudio mañana temprano»',
        effect: 'Agotamiento físico y mental por largas jornadas de estudio sin pausas estructuradas.',
        weakness: 'Pausas Activas & Rutinas de Sueño',
        icon: 'heart',
        border: 'hover:border-emerald-500/50',
        badge: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
    },
];

// Conceptos Clave y Definiciones Académicas
const concepts = [
    {
        title: '¿Qué es la Escala EPA y por qué es tu punto de partida?',
        icon: 'clipboard',
        summary: 'Diagnóstico científico de procrastinación académica al registrarte.',
        content: 'La Escala de Procrastinación Académica (EPA) es un instrumento psicométrico validado para medir hábitos de estudio, autorregulación y gestión del tiempo en universitarios. En Epycus, completas esta breve evaluación inicial al ingresar, recibes tu diagnóstico de partida y ganas automáticamente tus primeros +50 XP para comenzar tu aventura académica.',
    },
    {
        title: '¿Qué es la Procrastinación Académica?',
        icon: 'brain',
        summary: 'Un patrón conductual involuntario, no un defecto de carácter.',
        content: 'En psicología educativa, la procrastinación académica se define como la postergación sistemática e injustificada del inicio o culminación de tareas de estudio planificadas, a pesar de anticipar consecuencias desfavorables. Se origina por parálisis ante tareas intimidantes, fatiga por sobrecarga y búsqueda de alivio emocional inmediato. Epycus la neutraliza reduciendo la fricción inicial mediante micro-subtareas de 20 minutos.',
    },
    {
        title: '¿Qué es la Técnica Pomodoro y por qué funciona?',
        icon: 'timer',
        summary: 'Gestión del tiempo por intervalos óptimos de atención.',
        content: 'Desarrollada por Francesco Cirillo, consiste en dividir las sesiones de estudio en bloques de 25 minutos de concentración ininterrumpida seguidos de 5 minutos de descanso activo. Mantiene alta la agudeza cognitiva, previene el agotamiento y elimina la ansiedad del "tiempo infinito". En Epycus, el Pomodoro se sincroniza con música universal de YouTube y valida el tiempo real en servidor.',
    },
    {
        title: '¿Cómo funciona la Economía de XP, Monedas y Rachas?',
        icon: 'coins',
        summary: 'Recompensas inmediatas: 1 Moneda por cada 10 XP ganada.',
        content: 'Cada actividad de estudio (pomodoros, hábitos diarios, misiones y reflexiones) otorga puntos de experiencia (XP). Por cada 10 XP recibes automáticamente 1 Moneda para personalizar tu avatar y desbloquear marcos. Además, mantener tu racha diaria consecutiva te otorga un multiplicador de hasta +50% de XP extra en todo lo que hagas.',
    },
    {
        title: '¿Por qué la Gamificación Cognitiva vence la postergación?',
        icon: 'swords',
        summary: 'Recompensas inmediatas para balancear el esfuerzo a largo plazo.',
        content: 'El cerebro humano prefiere gratificaciones inmediatas sobre metas lejanas como un examen en dos meses. Al transformar tareas y hábitos en puntos de experiencia (XP), niveles, monedas y batallas contra 10 villanos representativos, Epycus convierte el avance académico diario en una experiencia gratificante y visible.',
    },
    {
        title: '¿Qué es la Ficha de Personaje y el Pentágono de 5 Atributos?',
        icon: 'bar-chart',
        summary: 'Evaluación multidimensional de tu perfil como estudiante.',
        content: 'Tu Dashboard incluye un radar pentagonal que mide en tiempo real tus 5 pilares de rendimiento: Disciplina (hábitos), Enfoque (horas Pomodoro), Constancia (racha), Equilibrio (diario de bienestar) y Sabiduría (misiones y apuntes), calculando tu clase y rango académico con rigor científico.',
    },
    {
        title: '¿Qué es el Camino del Héroe Universitario?',
        icon: 'trophy',
        summary: 'Tu mapa de progresión RPG en 5 etapas evolutivas.',
        content: 'Desde El Despertar del Iniciado hasta La Maestría del Titán, desbloqueas misiones de maestría por fase, títulos de honor acordes a tu carrera universitaria y perks permanentes (multiplicadores de XP, días de gracia para proteger tu racha y salas de estudio VIP).',
    },
];

// Comparativa Objetiva de Características Técnicas con Apps Populares
const comparisonFeatures = [
    { name: 'Diagnóstico Científico EPA al Iniciar (+50 XP)', epycus: true, notion: false, habitica: false, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Ficha de Personaje RPG con Pentágono Radar (5 Atributos)', epycus: true, notion: false, habitica: false, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Calendario & Time-Blocking 24h con Rutinas del Día', epycus: true, notion: false, habitica: false, forest: false, ticktick: true, focustodo: false, todoist: false },
    { name: 'Temporizador Pomodoro con Música YouTube / Lo-Fi', epycus: true, notion: false, habitica: false, forest: true, ticktick: false, focustodo: true, todoist: false },
    { name: 'Gestión de Cursos y Horarios Universitarios con Apuntes & Fotos', epycus: true, notion: false, habitica: false, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Misiones Vinculadas a Cursos con Tablero Kanban & Matriz Q2', epycus: true, notion: true, habitica: false, forest: false, ticktick: true, focustodo: false, todoist: true },
    { name: 'Finanzas Estudiantiles (Presupuesto Universitario & Metas con XP)', epycus: true, notion: false, habitica: false, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Hub de Salud: Fitness Anti-Sedentarismo & Marcador de 8 Vasos', epycus: true, notion: false, habitica: false, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Tienda de Recompensas de la Vida Real (Canje por Autocuidado)', epycus: true, notion: false, habitica: true, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Tutor IA Edy con Contexto 360° (Misiones, Finanzas, Calendario)', epycus: true, notion: false, habitica: false, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Avatar 100% Personalizable (Open Peeps con 10 Fases)', epycus: true, notion: false, habitica: true, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Camino del Héroe & Catálogo Completo de Logros', epycus: true, notion: false, habitica: true, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: 'Batallas Semanales contra 10 Villanos de la Procrastinación', epycus: true, notion: false, habitica: true, forest: false, ticktick: false, focustodo: false, todoist: false },
    { name: '100% Gratuito y Libre de Anuncios Invasivos', epycus: true, notion: false, habitica: false, forest: false, ticktick: false, focustodo: false, todoist: false },
];

// Carreras Universitarias soportadas con títulos de progresión
const careers = [
    { name: 'Ingeniería de Sistemas / Software', icon: 'monitor', ranks: 'Practicante → Arquitecto de Software' },
    { name: 'Medicina Humana & Salud', icon: 'heart', ranks: 'Interno → Especialista Clínico' },
    { name: 'Derecho & Ciencias Políticas', icon: 'shield', ranks: 'Secigrista → Jurista Supremo' },
    { name: 'Administración & Negocios', icon: 'trending-up', ranks: 'Asistente → Director Ejecutivo (CEO)' },
    { name: 'Psicología', icon: 'brain', ranks: 'Orientador → Terapeuta Máster' },
    { name: 'Arquitectura & Diseño', icon: 'palette', ranks: 'Proyectista → Diseñador Principal' },
];

// Preguntas Frecuentes
const faqs = [
    {
        q: '¿Qué es Epycus y cómo me ayuda a reducir la procrastinación en la universidad?',
        a: 'Epycus es un sistema integral de productividad diseñado para estudiantes de educación superior. Combina diagnóstico científico EPA, ficha de personaje RPG con radar de atributos, temporizadores Pomodoro con música Lo-Fi universal de YouTube, gestión de cursos vinculada a misiones, hábitos con sonido de recompensa y dinámicas de gamificación con monedas y niveles.',
    },
    {
        q: '¿Cómo funciona la Evaluación Inicial EPA y qué gano al completarla?',
        a: 'Al ingresar por primera vez, realizas un breve cuestionario diagnóstico de 8 preguntas sobre tus hábitos de estudio (Escala EPA). Al completarlo, recibes automáticamente +50 XP y tus primeras 5 Monedas en tu cuenta, además de activar tu perfil personalizado.',
    },
    {
        q: '¿Cómo se ganan monedas y experiencia (XP)?',
        a: 'Ganas XP estudiando con Pomodoro (+15 XP), completando hábitos (+10 XP), resolviendo misiones (+20 a +40 XP) y escribiendo en tu diario (+10 XP). Por cada 10 XP ganas automáticamente 1 Moneda para desbloquear accesorios y marcos en tu credencial. ¡Si mantienes tu racha de días consecutivos recibes hasta +50% de XP extra!',
    },
    {
        q: '¿Puedo elegir mi carrera universitaria al registrarme?',
        a: 'Sí. Al configurar tu perfil puedes seleccionar tu carrera universitaria o técnica (Ingeniería, Medicina, Derecho, Psicología, Arquitectura, Administración, etc.). A medida que subas de nivel, tu título académico evolucionará desde practicante hasta el rango máximo de tu especialidad.',
    },
    {
        q: '¿Puedo escuchar mi propia música o playlists de YouTube en el Pomodoro?',
        a: 'Sí. El reproductor universal es compatible con cualquier enlace de YouTube: playlists completas, transmisiones en vivo, mezclas Lo-Fi o pistas instrumentales, guardando tu enlace favorito para tus próximas sesiones.',
    },
    {
        q: '¿Tiene algún costo o requiere tarjeta de crédito?',
        a: 'No. El acceso a todas las herramientas de productividad de Epycus es 100% gratuito para los estudiantes universitarios y de institutos.',
    },
];

// ── Catálogo de Módulos del Sistema y Capturas de Pantalla ──────────────────
const activeModuleFilter = ref('all');

const moduleCategories = [
    { id: 'all', label: 'Todos los Módulos', count: 16, icon: 'layout-grid' },
    { id: 'productivity', label: 'Productividad', count: 5, icon: 'zap' },
    { id: 'organization', label: 'Organización', count: 2, icon: 'calendar' },
    { id: 'gamification', label: 'Gamificación', count: 5, icon: 'trophy' },
    { id: 'wellbeing', label: 'Bienestar', count: 2, icon: 'heart' },
    { id: 'social', label: 'Social & Ajustes', count: 2, icon: 'users' },
];

const systemModules = [
    {
        id: 'dashboard',
        name: 'Dashboard & Ficha de Personaje',
        category: 'productivity',
        categoryLabel: 'Productividad',
        badgeColor: 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30',
        image: '/assets/screenshots/pc_dashboard.png',
        icon: 'bar-chart',
        screenReaderTitle: 'Captura de pantalla del módulo Dashboard Principal de Epycus',
        screenReaderDesc: 'Panel central que muestra el nivel del estudiante, racha de estudio diaria, monedas acumuladas, barra de experiencia XP, ficha de personaje RPG con pentágono de 5 atributos y 5 gráficos analíticos.',
        tagline: 'Centro de Mando & Ficha RPG del Estudiante',
        description: 'Visualiza tus métricas clave de un vistazo: Ficha de personaje con Pentágono Radar de 5 Atributos (Disciplina, Enfoque, Constancia, Equilibrio, Sabiduría) y 5 Gráficos de Analítica Avanzada (Heatmap 60 días, Distribución por Asignatura, Horas Pico, Curva de Bienestar e Incursiones).',
        features: ['Ficha RPG con Pentágono Radar', '5 Gráficos de Analítica Avanzada', 'Racha, Monedas y Nivel en Vivo'],
    },
    {
        id: 'pomodoro',
        name: 'Temporizador Pomodoro & YouTube',
        category: 'productivity',
        categoryLabel: 'Productividad',
        badgeColor: 'bg-rose-500/15 text-rose-400 border-rose-500/30',
        image: '/assets/screenshots/pc_pomodoro.png',
        icon: 'timer',
        screenReaderTitle: 'Captura de pantalla del módulo Temporizador Pomodoro de Epycus',
        screenReaderDesc: 'Interfaz del temporizador de estudio con tiempos de trabajo y descanso, reproductor embebido de música Lo-Fi de YouTube y ganancia de 15 XP por sesión completada.',
        tagline: 'Sesiones de Foco Ininterrumpido',
        description: 'Combina intervalos de concentración de 25 minutos con tus pistas de música Lo-Fi favoritas de YouTube. Vincula misiones para acumular horas exactas de estudio por asignatura en tu Dashboard.',
        features: ['Música Lo-Fi de YouTube', 'Cálculo Exacto por Asignatura', '+15 XP por Bloque'],
    },
    {
        id: 'misiones_kanban',
        name: 'Misiones en Tablero Kanban',
        category: 'productivity',
        categoryLabel: 'Productividad',
        badgeColor: 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
        image: '/assets/screenshots/pc_misiones_kanban.png',
        icon: 'clipboard',
        screenReaderTitle: 'Captura de pantalla del Tablero Kanban de Misiones de Epycus',
        screenReaderDesc: 'Vista visual de gestión de tareas dividida en cuatro columnas: Lista, En Progreso, En Revisión y Terminado, con tarjetas de tareas, insignias de curso y subtareas interactivas.',
        tagline: 'Flujo Visual de Tareas y Entregas',
        description: 'Organiza tus proyectos y trabajos académicos con columnas de estado dinámicas. Vincula tareas a tus cursos de calendario y descompón trabajos grandes en micro-pasos de 20 minutos.',
        features: ['4 Columnas Kanban con Post-its', 'Vinculación a Cursos Universitarios', 'Subtareas de 20 minutos'],
    },
    {
        id: 'misiones_eisenhower',
        name: 'Matriz Eisenhower de Prioridades',
        category: 'productivity',
        categoryLabel: 'Productividad',
        badgeColor: 'bg-amber-500/15 text-amber-400 border-amber-500/30',
        image: '/assets/screenshots/pc_misiones_eisenhower.png',
        icon: 'target',
        screenReaderTitle: 'Captura de pantalla de la Matriz Eisenhower de Priorización de Epycus',
        screenReaderDesc: 'Cuadrante de cuatro zonas para clasificar tareas: Hacer YA (Q1), Planificar (Q2), Minimizar (Q3) y Descartar (Q4), con badges de asignaturas.',
        tagline: 'Priorización Estratégica en 4 Cuadrantes',
        description: 'Distingue de inmediato lo urgente de lo verdaderamente importante en tu ciclo. Evita el estrés de última hora clasificando tus exámenes y lecturas con criterio y filtrando por asignatura.',
        features: ['4 Cuadrantes de Impacto (Q1-Q4)', 'Filtro por Asignatura', 'Visión Antiestrés'],
    },
    {
        id: 'edy_ai',
        name: 'Tutor IA Edy — Asistente de Estudio',
        category: 'productivity',
        categoryLabel: 'Productividad',
        badgeColor: 'bg-purple-500/15 text-purple-400 border-purple-500/30',
        image: '/assets/screenshots/pc_edyAi.png',
        icon: 'brain',
        screenReaderTitle: 'Captura de pantalla del Asistente Virtual Inteligente Edy IA de Epycus',
        screenReaderDesc: 'Ventana de conversación con el tutor inteligente Edy, ofreciendo recomendaciones académicas personalizadas, técnicas de estudio y apoyo contra la procrastinación.',
        tagline: 'Inteligencia Artificial Adaptada al Universitario',
        description: 'Tu compañero de estudio 24/7. Pídele ideas para iniciar una monografía, técnicas de memorización para exámenes difíciles o pautas para organizar tu semana de entregas.',
        features: ['5 Consultas Diarias Sin Costo', 'Guía Pedagógica', 'Especializado en Universitarios'],
    },
    {
        id: 'calendario',
        name: 'Calendario Académico & Horarios',
        category: 'organization',
        categoryLabel: 'Organización',
        badgeColor: 'bg-blue-500/15 text-blue-400 border-blue-500/30',
        image: '/assets/screenshots/pc_calendario.png',
        icon: 'calendar',
        screenReaderTitle: 'Captura de pantalla del Calendario Académico y Horario Semanal de Epycus',
        screenReaderDesc: 'Agenda de horarios de clase semanales de lunes a domingo con código de colores por asignatura, aulas asignadas y feriados oficiales peruanos.',
        tagline: 'Planificación Semanal de Asignaturas',
        description: 'Controla tu horario semanal de clases de Lunes a Domingo con aulas y docentes asignados, además de los 16 feriados oficiales por ley de Perú (2025–2028).',
        features: ['Horarios Lunes a Domingo', 'Feriados Oficiales Perú', 'Aulas y Bloques de Clase'],
    },
    {
        id: 'apuntes',
        name: 'Cursos & Bloc de Apuntes',
        category: 'organization',
        categoryLabel: 'Organización',
        badgeColor: 'bg-teal-500/15 text-teal-400 border-teal-500/30',
        image: '/assets/screenshots/pc_apuntes.png',
        icon: 'book-open',
        screenReaderTitle: 'Captura de pantalla del Bloc de Apuntes y Cursos de Epycus',
        screenReaderDesc: 'Editor de notas de clase con listado de cursos, apuntes organizados por fecha y soporte para adjuntar imágenes de pizarras y diapositivas.',
        tagline: 'Notas Rápidas y Fotos de Pizarras',
        description: 'Lleva tus apuntes de clase organizados por asignatura. Adjunta capturas de diapositivas o pizarras para repasar antes de tus exámenes parciales y finales.',
        features: ['Organización por Asignatura', 'Adjuntos de Imágenes y Fotos', 'Búsqueda Rápida de Temas'],
    },
    {
        id: 'habitos',
        name: 'Hábitos Diarios & Rachas',
        category: 'gamification',
        categoryLabel: 'Gamificación',
        badgeColor: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
        image: '/assets/screenshots/pc_habitos.png',
        icon: 'flame',
        screenReaderTitle: 'Captura de pantalla del Módulo de Hábitos Diarios y Rachas de Epycus',
        screenReaderDesc: 'Lista de hábitos de estudio y autocuidado con casillas interactivas, contador de racha de días consecutivos y activación de confeti sonoro.',
        tagline: 'Consistencia y Días de Gracia',
        description: 'Construye rutinas de estudio duraderas con el reto científico de los 66 días. Incluye días de gracia automáticos para proteger tu racha y multiplicador de hasta +50% XP.',
        features: ['Feedback Sonoro y Confeti', 'Días de Gracia de Racha', 'Multiplicador de Experiencia'],
    },
    {
        id: 'villanos',
        name: 'Batallas Semanales vs 10 Villanos',
        category: 'gamification',
        categoryLabel: 'Gamificación',
        badgeColor: 'bg-rose-500/15 text-rose-400 border-rose-500/30',
        image: '/assets/screenshots/pc_villanos.png',
        icon: 'swords',
        screenReaderTitle: 'Captura de pantalla de la Batalla contra Villanos de la Procrastinación en Epycus',
        screenReaderDesc: 'Arena de combate semanal mostrando al villano activo (como La Postergación, La Distracción o El Burnout), Bestiario con 10 jefes, Playbook Estratégico y Battle Log.',
        tagline: 'Gamificación Cognitiva contra 10 Bosses Académicos',
        description: 'Enfrenta a 10 jefes temáticos semanales con Bestiario, Sala de Trofeos, Playbook Estratégico científico y botones de Ataque Directo por Pomodoros, hábitos y misiones.',
        features: ['10 Jefes Académicos & Bestiario', 'Botones de Ataque Directo', 'Playbook Estratégico & Battle Log'],
    },
    {
        id: 'ranking',
        name: 'Ranking & Clasificación',
        category: 'gamification',
        categoryLabel: 'Gamificación',
        badgeColor: 'bg-amber-500/15 text-amber-400 border-amber-500/30',
        image: '/assets/screenshots/pc_ranking.png',
        icon: 'trophy',
        screenReaderTitle: 'Captura de pantalla del Ranking y Tabla de Posiciones Académicas de Epycus',
        screenReaderDesc: 'Tabla de clasificación académica que muestra a los estudiantes universitarios con mayor puntuación de experiencia, nivel y constancia del ciclo.',
        tagline: 'Motivación Competitiva y Reconocimiento',
        description: 'Compite sanamente en la tabla de clasificación semanal y general. Destaca tu constancia de estudio y posiciónate entre los mejores de tu universidad.',
        features: ['Top Semanal y Global', 'Medallas de Posición', 'Filtrado por Rango'],
    },
    {
        id: 'logros',
        name: 'Logros & Medallas Desbloqueables',
        category: 'gamification',
        categoryLabel: 'Gamificación',
        badgeColor: 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30',
        image: '/assets/screenshots/pc_logros.png',
        icon: 'award',
        screenReaderTitle: 'Captura de pantalla de la Galería de Logros y Medallas de Epycus',
        screenReaderDesc: 'Muro de trofeos e insignias desbloqueables por metas cumplidas como racha de 30 días, 50 pomodoros o villanos vencidos.',
        tagline: 'Hitos y Trofeos de Aprendizaje',
        description: 'Desbloquea medallas por tus logros académicos: acumular horas de foco, mantener rachas consecutivas, derrotar jefes o explorar todas las herramientas.',
        features: ['Insignias Progresivas', 'Recompensas de Monedas', 'Registro de Hitos Históricos'],
    },
    {
        id: 'editaravatar',
        name: 'Camino del Héroe & Avatar',
        category: 'gamification',
        categoryLabel: 'Gamificación',
        badgeColor: 'bg-purple-500/15 text-purple-400 border-purple-500/30',
        image: '/assets/screenshots/pc_editaravatar.png',
        icon: 'palette',
        screenReaderTitle: 'Captura de pantalla del Editor de Avatar, Camino del Héroe y Credencial Digital en Epycus',
        screenReaderDesc: 'Mapa del Camino del Héroe en 5 fases evolutivas con perks permanentes, personalizador de personaje Open Peeps, marcos holográficos y credencial estudiantil.',
        tagline: 'Camino del Héroe & Identidad Universitaria',
        description: 'Recorre el Camino del Héroe en 5 fases evolutivas con misiones de maestría y perks permanentes. Personaliza tu avatar Open Peeps, desbloquea 10 marcos y exhibe tu credencial con títulos según tu carrera.',
        features: ['Camino del Héroe en 5 Fases', 'Perks y Bonificaciones de Maestría', 'Títulos Académicos por Carrera'],
    },
    {
        id: 'diario',
        name: 'Diario Emocional & Bienestar',
        category: 'wellbeing',
        categoryLabel: 'Bienestar',
        badgeColor: 'bg-rose-500/15 text-rose-400 border-rose-500/30',
        image: '/assets/screenshots/pc_diarioEmocional.png',
        icon: 'heart',
        screenReaderTitle: 'Captura de pantalla del Diario Emocional y de Bienestar en Epycus',
        screenReaderDesc: 'Historial de notas reflexivas privadas del estudiante, registro de autoconocimiento y seguimiento de salud mental durante el semestre universitario.',
        tagline: 'Bitácora Privada de Autorregulación',
        description: 'Espacio íntimo y cifrado para desahogarte, reflexionar sobre la carga académica del ciclo y mantener un balance saludable entre estudio y bienestar personal.',
        features: ['100% Privado y Seguro', 'Reduce el Estrés del Semestre', 'Daña al Villano de la Ansiedad'],
    },
    {
        id: 'emocion',
        name: 'Registro de Estado de Ánimo',
        category: 'wellbeing',
        categoryLabel: 'Bienestar',
        badgeColor: 'bg-pink-500/15 text-pink-400 border-pink-500/30',
        image: '/assets/screenshots/pc_registrar_emocion.png',
        icon: 'smile',
        screenReaderTitle: 'Captura de pantalla del Registro Diario de Estado de Ánimo en Epycus',
        screenReaderDesc: 'Modal interactivo con escala visual de emociones para seleccionar cómo se siente el estudiante (motivado, tranquilo, cansado, ansioso) antes de estudiar.',
        tagline: 'Check-in Emocional en 10 Segundos',
        description: 'Monitorea cómo fluctúa tu motivación y energía a lo largo de las semanas de clases para detectar a tiempo momentos de sobrecarga o fatiga mental.',
        features: ['Check-in Rápido con 1 Clic', 'Seguimiento de Tendencias', 'Consejos Pedagógicos'],
    },
    {
        id: 'grupos',
        name: 'Salas de Estudio Grupal',
        category: 'social',
        categoryLabel: 'Social & Ajustes',
        badgeColor: 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
        image: '/assets/screenshots/pc_grupoestudio.png',
        icon: 'users',
        screenReaderTitle: 'Captura de pantalla de las Salas de Estudio Grupal en Epycus',
        screenReaderDesc: 'Espacio colaborativo donde varios compañeros universitarios se unen a una sala virtual con temporizador Pomodoro compartido y chat en directo.',
        tagline: 'Estudia Acompañado en Tiempo Real',
        description: 'Crea salas de estudio con tus compañeros de clase. Compartan un temporizador Pomodoro sincronizado para motivarse mutuamente y evitar distracciones.',
        features: ['Pomodoro Grupal Sincronizado', 'Chat de Sala en Vivo', 'Estudio Colaborativo'],
    },
    {
        id: 'ajustes',
        name: 'Configuración & Preferencias',
        category: 'social',
        categoryLabel: 'Social & Ajustes',
        badgeColor: 'bg-slate-500/15 text-slate-400 border-slate-500/30',
        image: '/assets/screenshots/pc_ajustes.png',
        icon: 'settings',
        screenReaderTitle: 'Captura de pantalla del Panel de Ajustes y Configuración de Epycus',
        screenReaderDesc: 'Pantalla de configuración del sistema donde se administran opciones de notificaciones, sonidos de recompensa, carrera universitaria y privacidad.',
        tagline: 'Personalización Completa de tu Entorno',
        description: 'Configura tus preferencias de sonido, alertas, carrera universitaria, políticas de privacidad y gestiona tu cuenta de manera sencilla.',
        features: ['Control de Audio y Efectos', 'Cambio de Especialidad', 'Privacidad y Seguridad Ley 29733'],
    },
];

const filteredModules = computed(() => {
    if (activeModuleFilter.value === 'all') {
        return systemModules;
    }
    return systemModules.filter((m) => m.category === activeModuleFilter.value);
});

// Modal / Lightbox de Capturas en Pantalla Completa
const selectedScreenshot = ref(null);

function openScreenshotModal(mod) {
    selectedScreenshot.value = mod;
    triggerHapticVibration([20, 30]);
}

function closeScreenshotModal() {
    selectedScreenshot.value = null;
}

function nextScreenshot() {
    if (!selectedScreenshot.value) return;
    const currentList = filteredModules.value;
    const currentIndex = currentList.findIndex((m) => m.id === selectedScreenshot.value.id);
    if (currentIndex === -1) return;
    const nextIndex = (currentIndex + 1) % currentList.length;
    selectedScreenshot.value = currentList[nextIndex];
}

function prevScreenshot() {
    if (!selectedScreenshot.value) return;
    const currentList = filteredModules.value;
    const currentIndex = currentList.findIndex((m) => m.id === selectedScreenshot.value.id);
    if (currentIndex === -1) return;
    const prevIndex = (currentIndex - 1 + currentList.length) % currentList.length;
    selectedScreenshot.value = currentList[prevIndex];
}

function handleKeyDown(e) {
    if (!selectedScreenshot.value) return;
    if (e.key === 'Escape') {
        closeScreenshotModal();
    } else if (e.key === 'ArrowRight') {
        nextScreenshot();
    } else if (e.key === 'ArrowLeft') {
        prevScreenshot();
    }
}
</script>

<template>
    <Head>
        <title>Epycus — Aplicación que Reduce la Procrastinación Académica | App para Universitarios</title>
        <meta name="description" content="Epycus es la mejor aplicación para universitarios que reduce la procrastinación académica, ayuda a vencer los malos hábitos de estudio y mejora la organización académica. Pomodoro con música Lo-Fi, misiones con subtareas, diario emocional y asistente de IA integrado. El sistema gamificado de autorregulación académica diseñado para estudiantes de educación superior." />
        <meta name="keywords" content="aplicación que reduce la procrastinación, app para universitarios, reducir la procrastinación académica, hábitos de estudio, organización académica, diario emocional, asistente IA para estudiantes, app de estudio Perú, gamificación académica, Epycus" />
    </Head>

    <div
        class="min-h-screen font-sans selection:bg-indigo-500 selection:text-white relative overflow-x-hidden transition-colors duration-300"
        :class="isDark ? 'bg-[#070A12] text-slate-100' : 'bg-slate-50 text-slate-800'"
    >
        
        <!-- Iluminación ambiental suave de fondo -->
        <div v-if="isDark" class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[600px] bg-gradient-to-b from-indigo-900/15 via-cyan-900/5 to-transparent blur-3xl pointer-events-none -z-10"></div>
        <div v-if="isDark" class="absolute top-1/3 left-0 w-96 h-96 bg-purple-900/10 rounded-full blur-[140px] pointer-events-none -z-10"></div>
        <div v-if="isDark" class="absolute top-2/3 right-0 w-96 h-96 bg-cyan-900/10 rounded-full blur-[140px] pointer-events-none -z-10"></div>

        <div v-else class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[500px] bg-gradient-to-b from-indigo-100/60 via-cyan-50/40 to-transparent blur-3xl pointer-events-none -z-10"></div>

        <!-- ── Barra de Navegación ────────────────────────────────────────── -->
        <header
            class="sticky top-0 z-50 backdrop-blur-2xl border-b transition-all"
            :class="isDark ? 'bg-[#070A12]/85 border-white/[0.08]' : 'bg-white/90 border-slate-200 shadow-sm'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Marca -->
                <a href="/" class="flex items-center gap-3.5 group">
                    <div class="relative">
                        <img src="/assets/images/logo.webp" alt="Epycus Logo" class="w-10 h-10 object-contain drop-shadow-md group-hover:scale-105 transition-transform" />
                        <span class="absolute -bottom-1 -right-1 flex h-2.5 w-2.5">
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-cyan-400"></span>
                        </span>
                    </div>
                    <div>
                        <span
                            class="font-black text-xl tracking-tight transition-colors"
                            :class="isDark ? 'text-white group-hover:text-cyan-200' : 'text-slate-900 group-hover:text-indigo-600'"
                        >
                            Epycus
                        </span>
                        <span
                            class="hidden sm:inline-block ml-2 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md border"
                            :class="isDark ? 'bg-white/[0.06] text-slate-300 border-white/10' : 'bg-indigo-50 text-indigo-700 border-indigo-200'"
                        >
                            Academic Lab
                        </span>
                    </div>
                </a>

                <!-- Navegación -->
                <nav
                    class="hidden md:flex items-center gap-6 text-sm font-medium"
                    :class="isDark ? 'text-slate-300' : 'text-slate-600'"
                >
                    <a href="#modulos" class="hover:text-indigo-500 transition-colors">Módulos</a>
                    <a href="#comparativa" class="hover:text-indigo-500 transition-colors">Comparativa</a>
                    <a href="#buzon" class="hover:text-indigo-500 transition-colors flex items-center gap-1 text-cyan-400 font-semibold">
                        <AppIcon name="mail" :size="14" />
                        <span>Buzón</span>
                    </a>
                </nav>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Botón Toggle Modo Claro / Oscuro -->
                    <button
                        type="button"
                        class="p-2 sm:p-2.5 rounded-xl border transition-all flex items-center justify-center cursor-pointer"
                        :class="isDark ? 'bg-white/[0.05] border-white/10 text-amber-300 hover:bg-white/10' : 'bg-slate-100 border-slate-200 text-slate-700 hover:bg-slate-200'"
                        :title="isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                        @click="toggleTheme"
                    >
                        <AppIcon :name="isDark ? 'sun' : 'moon'" :size="18" />
                    </button>

                    <a
                        :href="appLoginUrl"
                        class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl transition-all"
                        :class="isDark ? 'text-slate-300 hover:text-white hover:bg-white/[0.05]' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100'"
                    >
                        Iniciar Sesión
                    </a>
                    <a
                        :href="appRegisterUrl"
                        class="px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-bold text-white rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center gap-1.5 sm:gap-2"
                    >
                        <span>Registrarme</span>
                        <AppIcon name="arrow-right" :size="15" />
                    </a>
                </div>
            </div>
        </header>

        <main>
            <!-- ── Hero Section ───────────────────────────────────────────── -->
            <section class="relative pt-12 pb-20 lg:pt-20 lg:pb-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    
                    <!-- Columna Izquierda: Texto Principal -->
                    <div class="lg:col-span-7 text-center lg:text-left">
                        <div
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold mb-6 backdrop-blur-md border"
                            :class="isDark ? 'bg-white/[0.05] border-white/10 text-slate-300' : 'bg-indigo-50 border-indigo-100 text-indigo-700'"
                        >
                            <AppIcon name="sparkles" :size="14" class="text-cyan-400" />
                            <span>Para estudiantes de universidades e institutos del Perú</span>
                        </div>

                        <h1
                            class="text-4xl sm:text-6xl font-black tracking-tight leading-[1.12]"
                            :class="isDark ? 'text-white' : 'text-slate-900'"
                        >
                            Salva tu ciclo universitario. <br class="hidden sm:inline" />
                            <span class="bg-gradient-to-r from-indigo-400 via-cyan-400 to-teal-400 bg-clip-text text-transparent">
                                Aniquila la procrastinación.
                            </span>
                        </h1>

                        <p
                            class="mt-6 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto lg:mx-0"
                            :class="isDark ? 'text-slate-300' : 'text-slate-600'"
                        >
                            Organiza tus cursos, mantén el foco con sesiones Pomodoro con música Lo-Fi, divide tus trabajos complejos en subtareas sencillas y sube de nivel mientras derrotas a los villanos del semestre.
                        </p>

                        <p
                            class="mt-4 text-[13px] sm:text-sm leading-relaxed max-w-2xl mx-auto lg:mx-0"
                            :class="isDark ? 'text-slate-400' : 'text-slate-500'"
                        >
                            Epycus es la aplicación que reduce la procrastinación académica en estudiantes universitarios: combate los malos hábitos de estudio, mejora la organización académica con un diario emocional privado y un asistente de IA integrado. Actualmente es uno de los mejores sistemas de autorregulación y productividad para estudiantes de educación superior.
                        </p>

                        <!-- Botones de Acción -->
                        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            <a
                                :href="appRegisterUrl"
                                class="w-full sm:w-auto px-8 py-4 text-base font-bold text-white rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-xl shadow-indigo-600/30 hover:shadow-cyan-600/40 transition-all hover:scale-105 text-center flex items-center justify-center gap-2.5"
                            >
                                <AppIcon name="zap" :size="18" class="text-amber-300" />
                                <span>Comenzar Gratis (+50 XP)</span>
                            </a>
                            <a
                                href="#ecosistema"
                                class="w-full sm:w-auto px-8 py-4 text-base font-semibold rounded-2xl border transition-all text-center flex items-center justify-center gap-2"
                                :class="isDark ? 'text-slate-300 hover:text-white bg-white/[0.04] hover:bg-white/[0.08] border-white/10' : 'text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-100 border-slate-200 shadow-sm'"
                            >
                                <span>Explorar Herramientas</span>
                                <AppIcon name="chevron-down" :size="16" />
                            </a>
                        </div>

                        <!-- Indicadores de Confianza -->
                        <div
                            class="mt-10 grid grid-cols-3 gap-4 border-t pt-6 max-w-xl mx-auto lg:mx-0 text-left"
                            :class="isDark ? 'border-white/[0.08]' : 'border-slate-200'"
                        >
                            <div class="flex items-start gap-2.5">
                                <AppIcon name="check-circle" :size="18" class="text-emerald-500 mt-0.5" />
                                <div>
                                    <div class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">100% Gratuito</div>
                                    <div class="text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Sin pagos ni anuncios</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <AppIcon name="calendar" :size="18" class="text-cyan-500 mt-0.5" />
                                <div>
                                    <div class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Feriados Peruanos</div>
                                    <div class="text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Oficiales 2025–2028</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <AppIcon name="shield" :size="18" class="text-indigo-500 mt-0.5" />
                                <div>
                                    <div class="text-sm font-bold" :class="isDark ? 'text-white' : 'text-slate-900'">Google 1-Clic</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Ilustración Hero + Edy Mascot -->
                    <div class="lg:col-span-5 relative flex justify-center items-center">
                        <div class="relative w-full max-w-md">
                            <!-- Marco Principal de la Imagen -->
                            <div
                                class="relative rounded-3xl overflow-hidden border shadow-2xl"
                                :class="isDark ? 'border-white/10 shadow-indigo-950/60 bg-[#0E1322]' : 'border-slate-200 shadow-xl bg-white'"
                            >
                                <img src="/assets/images/login-hero.webp" alt="Epycus Interface Hero" class="w-full h-auto object-cover rounded-3xl opacity-95 hover:opacity-100 transition-opacity" />
                                <div class="absolute inset-0" :class="isDark ? 'bg-gradient-to-t from-[#070A12] via-transparent to-transparent' : 'bg-gradient-to-t from-slate-900/30 via-transparent to-transparent'"></div>
                            </div>

                            <!-- Badge Flotante del Tutor IA Edy -->
                            <div
                                class="absolute -bottom-6 -left-6 rounded-2xl border p-3.5 shadow-2xl backdrop-blur-xl flex items-center gap-3.5 animate-smooth-float"
                                :class="isDark ? 'bg-[#0E1322]/95 border-white/10' : 'bg-white/95 border-slate-200 text-slate-800'"
                            >
                                <img src="/assets/Edy.png" alt="Mascota Edy IA" class="w-14 h-14 object-contain" />
                                <div>
                                    <div class="text-xs font-black flex items-center gap-1.5" :class="isDark ? 'text-white' : 'text-slate-900'">
                                        <span>Edy — Tutor IA</span>
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                    </div>
                                    <div class="text-[11px] font-mono" :class="isDark ? 'text-cyan-300' : 'text-indigo-600 font-semibold'">"¡Te ayudo a mantener el foco!"</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Bento Showcase Interactivo ────────────────────────── -->
                <div id="ecosistema" class="mt-20 sm:mt-28 max-w-6xl mx-auto">
                    <!-- Selector de Pestañas del Bento -->
                    <div class="flex items-center justify-center gap-2 mb-8 overflow-x-auto pb-2">
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="activeTab === 'pomodoro' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : (isDark ? 'bg-white/[0.04] text-slate-400 hover:text-white border border-white/10' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 shadow-sm')"
                            @click="selectTab('pomodoro')"
                        >
                            <AppIcon name="timer" :size="15" />
                            <span>Pomodoro & YouTube</span>
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="activeTab === 'courses' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : (isDark ? 'bg-white/[0.04] text-slate-400 hover:text-white border border-white/10' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 shadow-sm')"
                            @click="selectTab('courses')"
                        >
                            <AppIcon name="calendar" :size="15" />
                            <span>Calendario & Time-Blocking</span>
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="activeTab === 'missions' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : (isDark ? 'bg-white/[0.04] text-slate-400 hover:text-white border border-white/10' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 shadow-sm')"
                            @click="selectTab('missions')"
                        >
                            <AppIcon name="clipboard" :size="15" />
                            <span>Misiones & Hábitos</span>
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="activeTab === 'health' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : (isDark ? 'bg-white/[0.04] text-slate-400 hover:text-white border border-white/10' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 shadow-sm')"
                            @click="selectTab('health')"
                        >
                            <AppIcon name="heart" :size="15" />
                            <span>Fitness & Hidratación</span>
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="activeTab === 'finance' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : (isDark ? 'bg-white/[0.04] text-slate-400 hover:text-white border border-white/10' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 shadow-sm')"
                            @click="selectTab('finance')"
                        >
                            <AppIcon name="coins" :size="15" />
                            <span>Finanzas & Tienda</span>
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="activeTab === 'avatar' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : (isDark ? 'bg-white/[0.04] text-slate-400 hover:text-white border border-white/10' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 shadow-sm')"
                            @click="selectTab('avatar')"
                        >
                            <AppIcon name="trophy" :size="15" />
                            <span>Avatares & Logros</span>
                        </button>
                    </div>

                    <!-- Bento Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                        
                        <!-- Tarjeta Principal (8 Columnas) -->
                        <div
                            class="md:col-span-8 rounded-3xl border p-6 sm:p-8 flex flex-col justify-between shadow-2xl relative overflow-hidden group"
                            :class="isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-lg'"
                        >
                            <div class="absolute -top-24 -right-24 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/20 transition-all"></div>
                            
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-xs font-black uppercase tracking-wider px-3 py-1 rounded-lg bg-indigo-500/15 text-indigo-400 border border-indigo-500/30 flex items-center gap-1.5">
                                        <AppIcon :name="activeTab === 'pomodoro' ? 'timer' : (activeTab === 'courses' ? 'calendar' : (activeTab === 'missions' ? 'clipboard' : (activeTab === 'health' ? 'heart' : (activeTab === 'finance' ? 'coins' : 'trophy'))))" :size="14" />
                                        <span>{{ activeTab === 'pomodoro' ? 'Estudio Ininterrumpido' : (activeTab === 'courses' ? 'Planificador Visual 24h' : (activeTab === 'missions' ? 'Tareas Descompuestas' : (activeTab === 'health' ? 'Hub de Salud & Bienestar' : (activeTab === 'finance' ? 'Presupuesto Universitario' : 'Progresión de Jugador')))) }}</span>
                                    </span>
                                    <span class="text-xs font-mono" :class="isDark ? 'text-slate-400' : 'text-slate-500'">app.epycus.es</span>
                                </div>

                                <!-- Vista Tab 1: Pomodoro -->
                                <div v-if="activeTab === 'pomodoro'" class="space-y-4">
                                    <h3 class="text-2xl sm:text-3xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">Temporizador Sincronizado + Música Universal</h3>
                                    <p class="text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                        Pega cualquier playlist de YouTube, directo o video Lo-Fi. El temporizador valida tus minutos reales en el servidor y sincroniza tus pausas aunque cambies de pestaña o recargues el navegador.
                                    </p>
                                    <div
                                        class="mt-4 p-4 rounded-2xl border flex items-center justify-between"
                                        :class="isDark ? 'bg-[#070A12] border-white/[0.08]' : 'bg-slate-50 border-slate-200'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <img src="/assets/gifs/pomodoro.gif" alt="Pomodoro" class="w-10 h-10 object-contain rounded-xl" />
                                            <div>
                                                <div class="font-mono text-2xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">25:00</div>
                                                <div class="text-[11px] text-emerald-500 font-medium flex items-center gap-1">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>Sesión de Foco Activa (+15 XP)</span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-xs px-3 py-1.5 rounded-xl bg-indigo-500/15 text-indigo-400 border border-indigo-500/30 font-semibold flex items-center gap-1.5">
                                            <AppIcon name="music" :size="14" />
                                            <span>Lo-Fi Beats Activo</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Vista Tab 2: Calendario & Time-Blocking -->
                                <div v-else-if="activeTab === 'courses'" class="space-y-4">
                                    <h3 class="text-2xl sm:text-3xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">Time-Blocking Visual 24h & Bloc de Apuntes</h3>
                                    <p class="text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                        Combina tus clases universitarias con un checklist de 3 bloques diarios (Mañana, Tarde, Noche). Sube fotos de pizarras en tus apuntes y sincroniza con los 16 feriados peruanos oficiales.
                                    </p>
                                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-indigo-500/30' : 'bg-indigo-50/50 border-indigo-200'">
                                            <div class="font-bold text-indigo-500 flex items-center justify-between">
                                                <span>🕒 Time-Blocking 08:00 - 10:00</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-400">Clase</span>
                                            </div>
                                            <p class="text-[11px] mt-1 font-bold text-content-primary">Cálculo Integral [Aula B-302]</p>
                                            <p class="text-[10px] text-content-muted mt-0.5">📝 Bloc con fotos de fórmulas adjuntas</p>
                                        </div>
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-emerald-500/30' : 'bg-emerald-50/50 border-emerald-200'">
                                            <div class="font-bold text-emerald-500 flex items-center justify-between">
                                                <span>☀️ Rutina de Mañana</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400">Hecho</span>
                                            </div>
                                            <p class="text-[11px] mt-1 font-bold text-content-primary">Repaso activo 25 min + Café</p>
                                            <p class="text-[10px] text-success mt-0.5">+15 XP acumulados</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vista Tab 3: Misiones -->
                                <div v-else-if="activeTab === 'missions'" class="space-y-4">
                                    <h3 class="text-2xl sm:text-3xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">Matriz Eisenhower, Tablero Kanban & Subtareas</h3>
                                    <p class="text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                        Prioriza con el cuadrante de 4 zonas (Urgente vs Importante) o gestiona tu flujo de trabajo en el tablero Kanban (Pendiente, En Progreso, Completada). Descompón entregas complejas en micro-pasos de 20 minutos con avance automático.
                                    </p>
                                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-indigo-500/30' : 'bg-indigo-50/50 border-indigo-200'">
                                            <div class="font-bold text-indigo-400 flex items-center justify-between">
                                                <span>📋 Pendientes</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-500/20">2</span>
                                            </div>
                                            <p class="text-[11px] mt-2 text-content-secondary line-clamp-1">Ensayo de Bioética</p>
                                        </div>
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-cyan-500/30' : 'bg-cyan-50/50 border-cyan-200'">
                                            <div class="font-bold text-cyan-400 flex items-center justify-between">
                                                <span>⚡ En Progreso</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-cyan-500/20">1</span>
                                            </div>
                                            <p class="text-[11px] mt-2 text-content-secondary line-clamp-1">Informe Lab 4 (2/3 subtareas)</p>
                                        </div>
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-emerald-500/30' : 'bg-emerald-50/50 border-emerald-200'">
                                            <div class="font-bold text-emerald-400 flex items-center justify-between">
                                                <span>✅ Completadas</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/20">+40 XP</span>
                                            </div>
                                            <p class="text-[11px] mt-2 text-content-secondary line-clamp-1">Práctica Calificada 1</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vista Tab 4: Health (Fitness & Hidratación) -->
                                <div v-else-if="activeTab === 'health'" class="space-y-4">
                                    <h3 class="text-2xl sm:text-3xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">Fitness Anti-Sedentarismo & 8 Vasos de Agua</h3>
                                    <p class="text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                        Rutinas express de escritorio, estiramientos y calistenia en casa adaptadas al estudiante. Rastrea tus 8 vasos de agua al día para maximizar tu memoria y agudeza mental (+25 XP por sesión).
                                    </p>
                                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-cyan-500/30' : 'bg-cyan-50/50 border-cyan-200'">
                                            <div class="font-bold text-cyan-400 flex items-center justify-between">
                                                <span>💧 Hidratación Diaria</span>
                                                <span class="font-mono text-cyan-300">6 / 8 vasos</span>
                                            </div>
                                            <p class="text-[11px] text-content-secondary mt-1">1,500 ml consumidos hoy (75%)</p>
                                        </div>
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-rose-500/30' : 'bg-rose-50/50 border-rose-200'">
                                            <div class="font-bold text-rose-400 flex items-center justify-between">
                                                <span>💪 Rutina Express</span>
                                                <span class="text-rose-300">+25 XP</span>
                                            </div>
                                            <p class="text-[11px] text-content-secondary mt-1">Estiramiento cervical & lumbar (10 min)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vista Tab 5: Finance & Tienda -->
                                <div v-else-if="activeTab === 'finance'" class="space-y-4">
                                    <h3 class="text-2xl sm:text-3xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">Control de Gastos & Tienda de Autocuidado</h3>
                                    <p class="text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                        Control presupuestario de transporte, comidas y materiales de estudio. Canjea tus monedas ganadas por disciplina por premios de la vida real (café especial, tarde de cine, noche de videojuegos).
                                    </p>
                                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-emerald-500/30' : 'bg-emerald-50/50 border-emerald-200'">
                                            <div class="font-bold text-emerald-400 flex items-center justify-between">
                                                <span>💰 Presupuesto Mensual</span>
                                                <span class="text-emerald-300">Superávit S/ 140.00</span>
                                            </div>
                                            <p class="text-[11px] text-content-secondary mt-1">S/ 320 gastados de S/ 450 presupuestados</p>
                                        </div>
                                        <div class="p-3 rounded-xl border" :class="isDark ? 'bg-[#070A12] border-amber-500/30' : 'bg-amber-50/50 border-amber-200'">
                                            <div class="font-bold text-amber-400 flex items-center justify-between">
                                                <span>🎁 Recompensa Canjeada</span>
                                                <span class="text-amber-300 font-mono">🪙 50</span>
                                            </div>
                                            <p class="text-[11px] text-content-secondary mt-1">☕ Café especial de fin de semana</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vista Tab 6: Avatar & Logros -->
                                <div v-else class="space-y-4">
                                    <h3 class="text-2xl sm:text-3xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">Credencial Holográfica, Logros & 50 Niveles</h3>
                                    <p class="text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                        Personaliza tu avatar con el estilo Open Peeps, desbloquea insignias de maestría en tu perfil, sube de nivel con tu carrera y disfruta de perks permanentes de estudio.
                                    </p>
                                    <div
                                        class="mt-4 p-4 rounded-2xl border flex items-center justify-between"
                                        :class="isDark ? 'bg-[#070A12] border-amber-500/30' : 'bg-amber-50/50 border-amber-200'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-amber-500 to-indigo-600 p-0.5">
                                                <div class="w-full h-full rounded-[10px] flex items-center justify-center font-bold text-amber-400 text-xs" :class="isDark ? 'bg-[#070A12]' : 'bg-white'">
                                                    VIP
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm" :class="isDark ? 'text-white' : 'text-slate-900'">Credencial Estudiantil Digital</div>
                                                <div class="text-xs text-amber-500 font-mono flex items-center gap-1 mt-0.5">
                                                    <AppIcon name="coins" :size="13" />
                                                    <span>2,000 Monedas • Nivel 14 • 18 Logros Desbloqueados</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta Lateral (4 Columnas): Tutor IA Edy -->
                        <div
                            class="md:col-span-4 rounded-3xl border p-6 sm:p-8 flex flex-col justify-between shadow-2xl relative overflow-hidden"
                            :class="isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-lg'"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <AppIcon name="brain" :size="14" />
                                    <span>Tutor Inteligente</span>
                                </span>
                                <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                            </div>
                            <div class="text-center my-auto py-4">
                                <img src="/assets/Edy.png" alt="Edy Mascota" class="w-24 h-24 object-contain mx-auto mb-4 drop-shadow-xl" />
                                <h4 class="font-bold text-lg" :class="isDark ? 'text-white' : 'text-slate-900'">Edy — Tutor IA</h4>
                                <p class="text-xs mt-2 leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                    "¿Te cuesta arrancar con esa monografía? Divide el primer capítulo en 15 minutos de lluvia de ideas."
                                </p>
                            </div>
                            <div
                                class="p-3 rounded-xl border text-[11px] text-center font-mono flex items-center justify-center gap-1.5"
                                :class="isDark ? 'bg-[#070A12] border-white/[0.08] text-slate-400' : 'bg-slate-50 border-slate-200 text-slate-600'"
                            >
                                <AppIcon name="zap" :size="13" class="text-amber-400" />
                                <span>5 consultas diarias sin costo</span>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- ── Sección: Elección de Carrera Universitaria ───────────── -->
            <section
                id="carreras"
                class="py-20 border-t px-4 sm:px-6 lg:px-8 transition-colors"
                :class="isDark ? 'bg-[#0A0E17] border-white/[0.08]' : 'bg-slate-100/70 border-slate-200'"
            >
                <div class="max-w-7xl mx-auto">
                    <div class="text-center max-w-2xl mx-auto mb-14">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-4">
                            <AppIcon name="award" :size="14" />
                            <span>Progresión por Especialidad</span>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">
                            Rangos Adaptados a tu Carrera
                        </h2>
                        <p class="mt-4 text-sm sm:text-base" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                            Selecciona tu carrera universitaria al registrarte. A medida que sumas XP con Pomodoro y hábitos, tu título académico evoluciona en tu perfil público y credencial.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div
                            v-for="(car, cIdx) in careers"
                            :key="cIdx"
                            class="p-5 rounded-2xl border transition-all flex items-start gap-4"
                            :class="isDark ? 'bg-[#0E1322] border-white/[0.08] hover:border-indigo-500/40' : 'bg-white border-slate-200 hover:border-indigo-400 shadow-sm'"
                        >
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0 mt-0.5">
                                <AppIcon :name="car.icon" :size="20" />
                            </div>
                            <div>
                                <h3 class="font-bold text-sm sm:text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ car.name }}</h3>
                                <p class="text-xs text-cyan-500 font-mono mt-1 flex items-center gap-1">
                                    <AppIcon name="trending-up" :size="12" />
                                    <span>{{ car.ranks }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Sección: El Reto Científico de los 66 Días & Evaluación EPA ─ -->
            <section
                id="metodologia-66-dias"
                class="py-20 lg:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t"
                :class="isDark ? 'border-white/[0.08]' : 'border-slate-200'"
            >
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold mb-4">
                        <AppIcon name="brain" :size="14" />
                        <span>Fundamento Científico · University College London</span>
                    </div>
                    <h2 class="text-3xl sm:text-5xl font-black tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                        El Ciclo de los <span class="bg-gradient-to-r from-cyan-400 via-indigo-400 to-teal-400 bg-clip-text text-transparent">66 Días</span> y la Escala EPA
                    </h2>
                    <p class="mt-4 text-base sm:text-lg leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                        La ciencia demostró que se requieren en promedio <strong>66 días consecutivos</strong> para que un comportamiento de estudio se convierta en un hábito automático. Epycus mide tu evolución real de inicio a fin.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
                    <!-- Paso 1 -->
                    <div
                        class="p-7 rounded-3xl border flex flex-col justify-between relative overflow-hidden transition-all hover:-translate-y-1 shadow-xl"
                        :class="isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-sm'"
                    >
                        <div class="absolute top-0 right-0 px-4 py-2 rounded-bl-2xl bg-indigo-500/20 text-indigo-400 font-mono text-xs font-bold">
                            Día 1
                        </div>
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 text-indigo-400 flex items-center justify-center mb-5">
                                <AppIcon name="clipboard" :size="22" />
                            </div>
                            <h3 class="text-xl font-black mb-2" :class="isDark ? 'text-white' : 'text-slate-900'">1. Diagnóstico Inicial (Pre-test EPA)</h3>
                            <p class="text-xs sm:text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                Respondes la Escala de Procrastinación Académica (8 preguntas) para identificar tu nivel de autorregulación y gestión del tiempo. Recibes <strong>+50 XP inmediatos</strong> y tus primeras monedas.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t text-[11px] font-semibold text-indigo-400 flex items-center gap-1.5" :class="isDark ? 'border-white/[0.06]' : 'border-slate-100'">
                            <AppIcon name="check-circle" :size="14" />
                            <span>Punto de partida calibrado</span>
                        </div>
                    </div>

                    <!-- Paso 2 -->
                    <div
                        class="p-7 rounded-3xl border flex flex-col justify-between relative overflow-hidden transition-all hover:-translate-y-1 shadow-xl"
                        :class="isDark ? 'bg-[#0E1322] border-cyan-500/30' : 'bg-cyan-50/30 border-cyan-200 shadow-sm'"
                    >
                        <div class="absolute top-0 right-0 px-4 py-2 rounded-bl-2xl bg-cyan-500/20 text-cyan-400 font-mono text-xs font-bold">
                            Días 2 – 65
                        </div>
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-cyan-500/15 border border-cyan-500/30 text-cyan-400 flex items-center justify-center mb-5">
                                <AppIcon name="zap" :size="22" />
                            </div>
                            <h3 class="text-xl font-black mb-2" :class="isDark ? 'text-white' : 'text-slate-900'">2. Entrenamiento y Racha</h3>
                            <p class="text-xs sm:text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                Estudias con Pomodoro, marcas tus hábitos protegidos con Días de Gracia, ordenas tus misiones en la matriz Kanban y aplicas daño a los 5 villanos del semestre. Multiplicador de hasta <strong>+50% XP extra</strong>.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t text-[11px] font-semibold text-cyan-400 flex items-center gap-1.5" :class="isDark ? 'border-white/[0.06]' : 'border-slate-100'">
                            <AppIcon name="flame" :size="14" />
                            <span>Construcción de consistencia</span>
                        </div>
                    </div>

                    <!-- Paso 3 -->
                    <div
                        class="p-7 rounded-3xl border flex flex-col justify-between relative overflow-hidden transition-all hover:-translate-y-1 shadow-xl"
                        :class="isDark ? 'bg-[#0E1322] border-emerald-500/30' : 'bg-emerald-50/30 border-emerald-200 shadow-sm'"
                    >
                        <div class="absolute top-0 right-0 px-4 py-2 rounded-bl-2xl bg-emerald-500/20 text-emerald-400 font-mono text-xs font-bold">
                            Día 66
                        </div>
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 flex items-center justify-center mb-5">
                                <AppIcon name="trophy" :size="22" />
                            </div>
                            <h3 class="text-xl font-black mb-2" :class="isDark ? 'text-white' : 'text-slate-900'">3. Evaluación Final (Post-test EPA)</h3>
                            <p class="text-xs sm:text-sm leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                Al cumplir los 66 días se desbloquea el Post-test para comparar de forma tangible la reducción en tus índices de procrastinación, evaluar tu crecimiento y celebrar tu nuevo nivel de maestría.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t text-[11px] font-semibold text-emerald-400 flex items-center gap-1.5" :class="isDark ? 'border-white/[0.06]' : 'border-slate-100'">
                            <AppIcon name="sparkles" :size="14" />
                            <span>Transformación y hábito consolidado</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Sección: Los 5 Villanos Académicos ───────────────────── -->
            <section
                id="villanos"
                class="py-20 lg:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t"
                :class="isDark ? 'border-white/[0.08]' : 'border-slate-200'"
            >
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold mb-4">
                        <AppIcon name="swords" :size="14" />
                        <span>Gamificación Semanal</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">
                        Vence a los <span class="bg-gradient-to-r from-rose-400 via-amber-400 to-indigo-400 bg-clip-text text-transparent">5 Villanos de la Procrastinación</span>
                    </h2>
                    <p class="mt-4 text-sm sm:text-base" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                        Cada semana enfrentarás a un jefe automático. Aplícale daño real estudiando con Pomodoro, marcando hábitos y registrando tu bienestar emocional.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                    <article
                        v-for="(v, index) in villains"
                        :key="index"
                        class="rounded-3xl border p-6 flex flex-col items-center text-center transition-all duration-300 group hover:-translate-y-1.5 shadow-xl"
                        :class="[v.border, isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-sm']"
                    >
                        <div
                            class="w-24 h-24 mb-4 rounded-2xl p-2 border flex items-center justify-center group-hover:scale-105 transition-transform"
                            :class="isDark ? 'bg-[#070A12] border-white/[0.08]' : 'bg-slate-50 border-slate-200'"
                        >
                            <img :src="v.image" :alt="v.name" class="w-full h-full object-contain" />
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border mb-2 flex items-center gap-1" :class="v.badge">
                            <AppIcon :name="v.icon" :size="12" />
                            <span>{{ v.weakness }}</span>
                        </span>
                        <h3 class="font-bold text-base mb-1" :class="isDark ? 'text-white' : 'text-slate-900'">{{ v.name }}</h3>
                        <p class="text-[11px] italic mb-3 font-serif" :class="isDark ? 'text-amber-300/80' : 'text-amber-700'">{{ v.tagline }}</p>
                        <p class="text-xs leading-relaxed mt-auto" :class="isDark ? 'text-slate-300' : 'text-slate-600'">{{ v.effect }}</p>
                    </article>
                </div>
            </section>

            <!-- ── Sección: Módulos y Herramientas del Sistema ────────────── -->
            <section
                id="modulos"
                class="py-20 lg:py-28 border-t px-4 sm:px-6 lg:px-8 transition-colors scroll-mt-20"
                :class="isDark ? 'bg-[#0A0E17] border-white/[0.08]' : 'bg-slate-100/70 border-slate-200'"
                aria-labelledby="modules-main-heading"
            >
                <div class="max-w-7xl mx-auto">
                    <!-- Encabezado de la Sección -->
                    <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-4 backdrop-blur-md">
                            <AppIcon name="layout-grid" :size="14" />
                            <span>Módulos del Sistema Epycus</span>
                        </div>

                        <!-- Título visual principal y Título accesible para personas ciegas / lectores de pantalla -->
                        <h2
                            id="modules-main-heading"
                            class="text-3xl sm:text-5xl font-black tracking-tight"
                            :class="isDark ? 'text-white' : 'text-slate-900'"
                        >
                            Explora cada <span class="bg-gradient-to-r from-indigo-400 via-cyan-400 to-teal-400 bg-clip-text text-transparent">Módulo y Espacio</span>
                        </h2>

                        <!-- Título explícito y descripción accesible para personas con discapacidad visual y lectores de pantalla (NVDA, JAWS, VoiceOver) -->
                        <div class="sr-only">
                            <h2>Título para ciegos y lectores de pantalla: Catálogo de las 16 herramientas y capturas del sistema Epycus</h2>
                            <p>
                                A continuación se detallan los 16 módulos de la plataforma académica Epycus. Cada módulo cuenta con su título descriptivo, captura de pantalla ilustrativa con texto alternativo detallado, lista de funcionalidades y la opción de abrir una vista previa ampliada accesible mediante teclado.
                            </p>
                        </div>

                        <p class="mt-4 text-sm sm:text-base leading-relaxed" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                            Descubre cómo funciona cada rincón de Epycus: desde la gestión de cursos y pomodoros hasta las batallas contra villanos y el diario emocional.
                        </p>
                    </div>

                    <!-- Filtros por Categoría de Módulos (Scrollable en móviles) -->
                    <div
                        role="tablist"
                        aria-label="Filtrar módulos del sistema por categoría"
                        class="flex items-center justify-start sm:justify-center gap-2 mb-10 overflow-x-auto pb-3 custom-scrollbar px-1"
                    >
                        <button
                            v-for="cat in moduleCategories"
                            :key="cat.id"
                            type="button"
                            role="tab"
                            :aria-selected="activeModuleFilter === cat.id"
                            :aria-label="`Filtrar por ${cat.label} (${cat.count} módulos)`"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 border cursor-pointer select-none"
                            :class="activeModuleFilter === cat.id
                                ? 'bg-gradient-to-r from-indigo-600 to-cyan-600 text-white border-transparent shadow-lg shadow-indigo-600/25 scale-[1.02]'
                                : (isDark ? 'bg-[#0E1322] border-white/10 text-slate-300 hover:text-white hover:bg-white/[0.06]' : 'bg-white border-slate-200 text-slate-700 hover:text-slate-900 hover:bg-slate-50 shadow-sm')"
                            @click="activeModuleFilter = cat.id; triggerHapticVibration([15]);"
                        >
                            <AppIcon :name="cat.icon" :size="14" />
                            <span>{{ cat.label }}</span>
                            <span
                                class="px-1.5 py-0.5 rounded-full text-[10px] font-mono"
                                :class="activeModuleFilter === cat.id ? 'bg-white/20 text-white' : (isDark ? 'bg-white/[0.08] text-slate-400' : 'bg-slate-100 text-slate-600')"
                            >
                                {{ cat.count }}
                            </span>
                        </button>
                    </div>

                    <!-- Mensaje accesible para lectores de pantalla sobre el filtro activo -->
                    <div class="sr-only" aria-live="polite">
                        Mostrando {{ filteredModules.length }} módulos en la categoría seleccionada.
                    </div>

                    <!-- Grid Responsivo de Módulos (1 Col en móvil, 2 en tablet, 3 en laptop, 4 en pantallas grandes) -->
                    <div
                        id="modules-grid"
                        role="region"
                        aria-label="Listado de módulos de Epycus"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                    >
                        <article
                            v-for="mod in filteredModules"
                            :key="mod.id"
                            class="rounded-3xl border flex flex-col justify-between overflow-hidden transition-all duration-300 hover:-translate-y-1.5 group shadow-xl"
                            :class="isDark ? 'bg-[#0E1322] border-white/[0.08] hover:border-indigo-500/50 hover:shadow-indigo-950/50' : 'bg-white border-slate-200 hover:border-indigo-400 hover:shadow-indigo-100 shadow-sm'"
                        >
                            <!-- Contenedor de Captura de Pantalla con Overlay Interactivo -->
                            <div class="relative overflow-hidden bg-slate-950 border-b aspect-[16/10]" :class="isDark ? 'border-white/[0.08]' : 'border-slate-100'">
                                <img
                                    :src="mod.image"
                                    :alt="mod.screenReaderTitle"
                                    :title="`Captura del módulo: ${mod.name}`"
                                    loading="lazy"
                                    class="w-full h-full object-cover object-top transition-transform duration-500 group-hover:scale-105 cursor-pointer"
                                    @click="openScreenshotModal(mod)"
                                />

                                <!-- Badge de Categoría sobre la imagen -->
                                <div class="absolute top-3 left-3 z-10">
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg border backdrop-blur-md flex items-center gap-1.5 shadow-md" :class="mod.badgeColor">
                                        <AppIcon :name="mod.icon" :size="12" />
                                        <span>{{ mod.categoryLabel }}</span>
                                    </span>
                                </div>

                                <!-- Overlay Hover para abrir modal -->
                                <button
                                    type="button"
                                    class="absolute inset-0 bg-gradient-to-t from-[#070A12]/90 via-[#070A12]/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-end p-4 text-white text-xs font-bold cursor-pointer"
                                    :aria-label="`Abrir visor ampliado de captura para ${mod.name}`"
                                    :title="`Clic para ver la captura de ${mod.name} en tamaño completo`"
                                    @click="openScreenshotModal(mod)"
                                >
                                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/20 backdrop-blur-md border border-white/30 shadow-lg">
                                        <AppIcon name="maximize" :size="13" />
                                        <span>Ampliar Captura</span>
                                    </div>
                                </button>
                            </div>

                            <!-- Información y Detalles del Módulo -->
                            <div class="p-5 sm:p-6 flex flex-col flex-grow justify-between">
                                <div>
                                    <!-- Encabezado del Módulo -->
                                    <div class="flex items-start justify-between gap-2 mb-1.5">
                                        <h3
                                            class="font-black text-base sm:text-lg leading-tight group-hover:text-indigo-400 transition-colors"
                                            :class="isDark ? 'text-white' : 'text-slate-900'"
                                            :title="mod.name"
                                        >
                                            {{ mod.name }}
                                        </h3>
                                    </div>

                                    <!-- Título y descripción específica para lectores de pantalla y ciegos -->
                                    <div class="sr-only">
                                        <h4>Detalle accesible para personas ciegas: {{ mod.name }}</h4>
                                        <p>{{ mod.screenReaderDesc }}</p>
                                    </div>

                                    <p class="text-xs font-semibold mb-3 flex items-center gap-1.5" :class="isDark ? 'text-cyan-400' : 'text-indigo-600'">
                                        <span>{{ mod.tagline }}</span>
                                    </p>

                                    <p class="text-xs leading-relaxed mb-4 line-clamp-3" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                                        {{ mod.description }}
                                    </p>
                                </div>

                                <div>
                                    <!-- Lista de Puntos Clave / Características -->
                                    <ul class="space-y-1.5 pt-3 border-t text-[11px] mb-4" :class="isDark ? 'border-white/[0.06] text-slate-300' : 'border-slate-100 text-slate-600'">
                                        <li v-for="(feat, fIdx) in mod.features" :key="fIdx" class="flex items-center gap-2">
                                            <AppIcon name="check-circle" :size="13" class="text-emerald-400 shrink-0" />
                                            <span class="truncate">{{ feat }}</span>
                                        </li>
                                    </ul>

                                    <!-- Botón de Acción para Ver Captura -->
                                    <button
                                        type="button"
                                        class="w-full py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 border cursor-pointer"
                                        :class="isDark
                                            ? 'bg-white/[0.04] hover:bg-indigo-600 hover:text-white hover:border-indigo-500 border-white/10 text-slate-200 shadow-sm'
                                            : 'bg-slate-50 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 border-slate-200 text-slate-700 shadow-sm'"
                                        :title="`Ver captura de pantalla completa de ${mod.name}`"
                                        :aria-label="`Ver captura de pantalla completa de ${mod.name}. ${mod.screenReaderTitle}`"
                                        @click="openScreenshotModal(mod)"
                                    >
                                        <AppIcon name="eye" :size="14" />
                                        <span>Ver Captura de Pantalla</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- ── Modal / Lightbox Accesible de Captura en Pantalla Completa ── -->
                <div
                    v-if="selectedScreenshot"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="'screenshot-modal-title-' + selectedScreenshot.id"
                    :aria-describedby="'screenshot-modal-desc-' + selectedScreenshot.id"
                    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/90 backdrop-blur-2xl animate-fade-in"
                    @click.self="closeScreenshotModal"
                >
                    <div
                        class="relative w-full max-w-5xl max-h-[92vh] rounded-3xl border overflow-hidden flex flex-col shadow-2xl transition-all"
                        :class="isDark ? 'bg-[#0E1322] border-white/15 text-white' : 'bg-white border-slate-300 text-slate-900'"
                    >
                        <!-- Cabecera del Modal -->
                        <div
                            class="px-5 py-4 border-b flex items-center justify-between gap-4 shrink-0"
                            :class="isDark ? 'bg-[#070A12] border-white/10' : 'bg-slate-50 border-slate-200'"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-xs font-black uppercase tracking-wider px-2.5 py-1 rounded-lg border flex items-center gap-1.5 shrink-0" :class="selectedScreenshot.badgeColor">
                                    <AppIcon :name="selectedScreenshot.icon" :size="13" />
                                    <span>{{ selectedScreenshot.categoryLabel }}</span>
                                </span>
                                <div class="min-w-0">
                                    <h3
                                        :id="'screenshot-modal-title-' + selectedScreenshot.id"
                                        class="font-black text-sm sm:text-lg truncate"
                                    >
                                        {{ selectedScreenshot.name }}
                                    </h3>
                                    <p class="text-xs text-cyan-400 font-medium truncate hidden sm:block">
                                        {{ selectedScreenshot.tagline }}
                                    </p>
                                </div>
                            </div>

                            <!-- Botones de Navegación y Cerrar -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    class="p-2 rounded-xl border transition-colors cursor-pointer"
                                    :class="isDark ? 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/15' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100'"
                                    aria-label="Módulo anterior (o tecla flecha izquierda)"
                                    title="Módulo anterior (←)"
                                    @click="prevScreenshot"
                                >
                                    <AppIcon name="arrow-left" :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="p-2 rounded-xl border transition-colors cursor-pointer"
                                    :class="isDark ? 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/15' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100'"
                                    aria-label="Módulo siguiente (o tecla flecha derecha)"
                                    title="Módulo siguiente (→)"
                                    @click="nextScreenshot"
                                >
                                    <AppIcon name="arrow-right" :size="16" />
                                </button>
                                <button
                                    type="button"
                                    class="p-2 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 hover:bg-rose-500/25 transition-colors cursor-pointer ml-1"
                                    aria-label="Cerrar visor de captura (o tecla Escape)"
                                    title="Cerrar visor (Esc)"
                                    @click="closeScreenshotModal"
                                >
                                    <AppIcon name="x" :size="18" />
                                </button>
                            </div>
                        </div>

                        <!-- Contenedor Principal de la Imagen con Scroll si excede -->
                        <div class="p-3 sm:p-5 overflow-y-auto flex-grow flex flex-col items-center justify-center bg-slate-950/60">
                            <div class="w-full relative rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-black flex items-center justify-center">
                                <img
                                    :src="selectedScreenshot.image"
                                    :alt="selectedScreenshot.screenReaderTitle"
                                    :title="selectedScreenshot.name"
                                    class="w-full h-auto max-h-[60vh] object-contain rounded-2xl"
                                />
                            </div>
                        </div>

                        <!-- Pie del Modal con Accesibilidad y Descripción -->
                        <div
                            class="px-5 py-4 border-t shrink-0 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs"
                            :class="isDark ? 'bg-[#070A12] border-white/10 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-700'"
                        >
                            <div :id="'screenshot-modal-desc-' + selectedScreenshot.id" class="max-w-3xl leading-relaxed">
                                <span class="font-bold text-indigo-400 block sm:inline mr-1">Descripción Accesible:</span>
                                <span>{{ selectedScreenshot.screenReaderDesc }}</span>
                            </div>

                            <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400 shrink-0 self-end sm:self-center">
                                <span class="px-2 py-1 rounded bg-white/10 font-bold">Esc</span>
                                <span>para cerrar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Sección: Tabla Comparativa Panorámica Completa ──────── -->
            <section
                id="comparativa"
                class="py-20 lg:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t"
                :class="isDark ? 'border-white/[0.08]' : 'border-slate-200'"
            >
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-500 text-xs font-semibold mb-4">
                        <AppIcon name="bar-chart" :size="14" />
                        <span>Comparativa de Productividad</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">
                        ¿Por qué Epycus para Estudiantes Universitarios?
                    </h2>
                    <p class="mt-4 text-sm sm:text-base" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                        Panorama completo y objetivo frente a las principales aplicaciones de organización y estudio.
                    </p>
                </div>

                <!-- Indicador de desplazamiento para móviles -->
                <div class="flex items-center justify-between text-xs mb-3 px-1" :class="isDark ? 'text-slate-400' : 'text-slate-500'">
                    <span class="flex items-center gap-1.5 font-medium">
                        <AppIcon name="bar-chart" :size="13" class="text-cyan-500" />
                        <span>Comparativa de las 7 plataformas</span>
                    </span>
                    <span class="text-[11px] font-bold text-indigo-500 flex items-center gap-1 animate-pulse">
                        <span>Desliza a la derecha</span>
                        <AppIcon name="arrow-right" :size="12" />
                    </span>
                </div>

                <!-- Matriz Completa y Fluida con Barra de Desplazamiento Visible -->
                <div
                    class="rounded-3xl border overflow-hidden shadow-2xl relative"
                    :class="isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-md'"
                >
                    <div class="overflow-x-auto custom-scrollbar pb-2">
                        <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[480px] sm:min-w-[760px]">
                            <thead>
                                <tr class="border-b" :class="isDark ? 'border-white/[0.08] bg-[#070A12]' : 'border-slate-200 bg-slate-100'">
                                    <!-- Columna Fija Sticky con Nombre de Característica -->
                                    <th
                                        class="p-2.5 sm:p-5 font-bold sticky left-0 z-20 border-r w-[110px] min-w-[110px] max-w-[125px] sm:w-[220px] sm:min-w-[220px] text-[10.5px] sm:text-xs leading-tight"
                                        :class="isDark ? 'bg-[#070A12] text-slate-300 border-white/[0.08]' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                    >
                                        Característica
                                    </th>
                                    <th
                                        class="p-2 sm:p-5 font-black text-center border-r w-[48px] min-w-[48px] sm:w-[95px] sm:min-w-[95px] text-[10px] sm:text-xs"
                                        :class="isDark ? 'bg-indigo-600/20 text-cyan-300 border-indigo-500/30' : 'bg-indigo-50 text-indigo-700 border-indigo-200'"
                                    >
                                        Epycus
                                    </th>
                                    <th class="p-2 sm:p-5 font-medium text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px] text-[10px] sm:text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">Notion</th>
                                    <th class="p-2 sm:p-5 font-medium text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px] text-[10px] sm:text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">Habitica</th>
                                    <th class="p-2 sm:p-5 font-medium text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px] text-[10px] sm:text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">Forest</th>
                                    <th class="p-2 sm:p-5 font-medium text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px] text-[10px] sm:text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">TickTick</th>
                                    <th class="p-2 sm:p-5 font-medium text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px] text-[10px] sm:text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">Focus</th>
                                    <th class="p-2 sm:p-5 font-medium text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px] text-[10px] sm:text-xs" :class="isDark ? 'text-slate-400' : 'text-slate-600'">Todoist</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="isDark ? 'divide-white/[0.04]' : 'divide-slate-100'">
                                <tr
                                    v-for="(row, rIdx) in comparisonFeatures"
                                    :key="rIdx"
                                    class="transition-colors"
                                    :class="isDark ? 'hover:bg-white/[0.02]' : 'hover:bg-slate-50'"
                                >
                                    <!-- Celda Fija Sticky -->
                                    <td
                                        class="p-2.5 sm:p-5 font-medium sticky left-0 z-10 border-r w-[110px] min-w-[110px] max-w-[125px] sm:w-[220px] sm:min-w-[220px] text-[10.5px] sm:text-xs leading-tight"
                                        :class="isDark ? 'bg-[#0E1322] text-slate-200 border-white/[0.08]' : 'bg-white text-slate-800 border-slate-200'"
                                    >
                                        {{ row.name }}
                                    </td>
                                    <td
                                        class="p-2 sm:p-5 text-center border-r w-[48px] min-w-[48px] sm:w-[95px] sm:min-w-[95px]"
                                        :class="isDark ? 'bg-indigo-600/10 border-indigo-500/20' : 'bg-indigo-50/50 border-indigo-100'"
                                    >
                                        <AppIcon v-if="row.epycus" name="check-circle" :size="16" class="text-emerald-500 mx-auto" />
                                        <span v-else class="text-slate-400 font-mono">—</span>
                                    </td>
                                    <td class="p-2 sm:p-5 text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px]">
                                        <AppIcon v-if="row.notion" name="check-circle" :size="14" class="text-slate-400 mx-auto" />
                                        <span v-else class="text-slate-400 font-mono">—</span>
                                    </td>
                                    <td class="p-2 sm:p-5 text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px]">
                                        <AppIcon v-if="row.habitica" name="check-circle" :size="14" class="text-slate-400 mx-auto" />
                                        <span v-else class="text-slate-400 font-mono">—</span>
                                    </td>
                                    <td class="p-2 sm:p-5 text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px]">
                                        <AppIcon v-if="row.forest" name="check-circle" :size="14" class="text-slate-400 mx-auto" />
                                        <span v-else class="text-slate-400 font-mono">—</span>
                                    </td>
                                    <td class="p-2 sm:p-5 text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px]">
                                        <AppIcon v-if="row.ticktick" name="check-circle" :size="14" class="text-slate-400 mx-auto" />
                                        <span v-else class="text-slate-400 font-mono">—</span>
                                    </td>
                                    <td class="p-2 sm:p-5 text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px]">
                                        <AppIcon v-if="row.focustodo" name="check-circle" :size="14" class="text-slate-400 mx-auto" />
                                        <span v-else class="text-slate-400 font-mono">—</span>
                                    </td>
                                    <td class="p-2 sm:p-5 text-center w-[46px] min-w-[46px] sm:w-[85px] sm:min-w-[85px]">
                                        <AppIcon v-if="row.todoist" name="check-circle" :size="14" class="text-slate-400 mx-auto" />
                                        <span v-else class="text-slate-400 font-mono">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ── Sección: Glosario Académico & Conceptos Clave ─────────── -->
            <section
                id="glosario"
                class="py-20 border-t px-4 sm:px-6 lg:px-8 transition-colors"
                :class="isDark ? 'bg-[#0A0E17] border-white/[0.08]' : 'bg-slate-100/70 border-slate-200'"
            >
                <div class="max-w-4xl mx-auto">
                    <div class="text-center max-w-2xl mx-auto mb-14">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-4">
                            <AppIcon name="book-text" :size="14" />
                            <span>Glosario & Fundamentos</span>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">
                            Conceptos Clave de Enfoque y Autorregulación
                        </h2>
                        <p class="mt-4 text-sm sm:text-base" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                            Comprende la ciencia detrás del hábito de estudio para transformar tu rendimiento académico.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <article
                            v-for="(c, cIdx) in concepts"
                            :key="cIdx"
                            class="rounded-2xl border overflow-hidden transition"
                            :class="isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-sm'"
                        >
                            <button
                                type="button"
                                class="w-full p-5 text-left font-semibold flex items-center justify-between gap-4 transition-colors cursor-pointer"
                                :class="isDark ? 'text-slate-200 hover:text-cyan-300' : 'text-slate-800 hover:text-indigo-600'"
                                @click="toggleConcept(cIdx)"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                                        <AppIcon :name="c.icon" :size="16" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-sm sm:text-base" :class="isDark ? 'text-white' : 'text-slate-900'">{{ c.title }}</div>
                                        <div class="text-xs font-normal mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-500'">{{ c.summary }}</div>
                                    </div>
                                </div>
                                <AppIcon :name="activeConcept === cIdx ? 'chevron-down' : 'chevron-right'" :size="18" class="text-cyan-500 shrink-0" />
                            </button>
                            <div
                                v-if="activeConcept === cIdx"
                                class="px-5 pb-5 text-sm leading-relaxed border-t pt-4"
                                :class="isDark ? 'text-slate-300 border-white/[0.06]' : 'text-slate-600 border-slate-100'"
                            >
                                {{ c.content }}
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- ── Sección: Preguntas Frecuentes ───────────────────────── -->
            <section
                id="faq"
                class="py-20 lg:py-28 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto border-t"
                :class="isDark ? 'border-white/[0.08]' : 'border-slate-200'"
            >
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">Preguntas Frecuentes</h2>
                    <p class="mt-4 text-sm" :class="isDark ? 'text-slate-300' : 'text-slate-600'">Todo lo que necesitas saber antes de comenzar a usar Epycus.</p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="rounded-2xl border overflow-hidden transition"
                        :class="isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-sm'"
                    >
                        <button
                            type="button"
                            class="w-full p-5 text-left font-semibold flex items-center justify-between gap-4 transition-colors cursor-pointer"
                            :class="isDark ? 'text-slate-200 hover:text-cyan-300' : 'text-slate-800 hover:text-indigo-600'"
                            @click="toggleFaq(index)"
                        >
                            <span>{{ faq.q }}</span>
                            <AppIcon :name="activeFaq === index ? 'chevron-down' : 'chevron-right'" :size="18" class="text-cyan-500 shrink-0" />
                        </button>
                        <div
                            v-if="activeFaq === index"
                            class="px-5 pb-5 text-sm leading-relaxed border-t pt-4"
                            :class="isDark ? 'text-slate-300 border-white/[0.06]' : 'text-slate-600 border-slate-100'"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Sección: Buzón de la Comunidad Universitaria ─────────── -->
            <section
                id="buzon"
                class="py-20 border-t px-4 sm:px-6 lg:px-8 transition-colors"
                :class="isDark ? 'bg-[#0A0E17] border-white/[0.08]' : 'bg-slate-100/70 border-slate-200'"
            >
                <div class="max-w-4xl mx-auto">
                    <div class="text-center max-w-2xl mx-auto mb-12">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold mb-4">
                            <AppIcon name="mail" :size="14" />
                            <span>Buzón Abierto de la Comunidad</span>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-black" :class="isDark ? 'text-white' : 'text-slate-900'">
                            Tu Opinión Construye <span class="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">Epycus</span>
                        </h2>
                        <p class="mt-4 text-sm sm:text-base" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                            ¿Tienes una idea para una nueva función, encontraste un error, deseas sugerir una mejora o simplemente dejar un mensaje de agradecimiento? ¡Te leemos directamente!
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border p-6 sm:p-10 shadow-2xl relative overflow-hidden transition-all"
                        :class="isDark ? 'bg-[#0E1322] border-white/[0.08]' : 'bg-white border-slate-200 shadow-xl'"
                    >
                        <!-- Selector de Categoría -->
                        <div class="mb-6">
                            <label class="block text-xs font-bold uppercase tracking-wider mb-3" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                                1. Selecciona el Tipo de Mensaje
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <button
                                    v-for="cat in feedbackCategories"
                                    :key="cat.id"
                                    type="button"
                                    class="p-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 border cursor-pointer"
                                    :class="feedbackType === cat.id
                                        ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30'
                                        : (isDark ? 'bg-[#070A12] border-white/[0.08] text-slate-400 hover:text-white hover:bg-white/[0.04]' : 'bg-slate-50 border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100')"
                                    @click="feedbackType = cat.id"
                                >
                                    <AppIcon :name="cat.icon" :size="14" />
                                    <span>{{ cat.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Formulario de Envío -->
                        <form class="space-y-4" @submit.prevent="sendFeedback" @paste="handleFeedbackPaste">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold mb-1.5" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                                        Tu Nombre o Alias <span class="text-slate-400 font-normal">(Opcional)</span>
                                    </label>
                                    <input
                                        v-model="feedbackName"
                                        type="text"
                                        placeholder="Ej. Juan de San Marcos"
                                        maxlength="100"
                                        class="w-full px-4 py-3 rounded-xl text-xs sm:text-sm border transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        :class="isDark ? 'bg-[#070A12] border-white/10 text-white placeholder:text-slate-500' : 'bg-slate-50 border-slate-200 text-slate-800 placeholder:text-slate-400'"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1.5" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                                        Correo Electrónico <span class="text-slate-400 font-normal">(Opcional, si deseas respuesta)</span>
                                    </label>
                                    <input
                                        v-model="feedbackEmail"
                                        type="email"
                                        placeholder="tu.correo@universidad.edu.pe"
                                        maxlength="150"
                                        class="w-full px-4 py-3 rounded-xl text-xs sm:text-sm border transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        :class="isDark ? 'bg-[#070A12] border-white/10 text-white placeholder:text-slate-500' : 'bg-slate-50 border-slate-200 text-slate-800 placeholder:text-slate-400'"
                                    />
                                </div>
                            </div>

                            <!-- Callout Dinámico para Reporte de Error -->
                            <div
                                v-if="feedbackType === 'bug'"
                                class="p-4 rounded-2xl border flex items-start gap-3 bg-amber-500/10 border-amber-500/30 text-amber-300 animate-fade-in"
                            >
                                <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 shrink-0 mt-0.5">
                                    <AppIcon name="shield" :size="18" />
                                </div>
                                <div class="text-xs leading-relaxed">
                                    <div class="font-bold text-amber-300 text-sm">¿Encontraste un fallo o error en pantalla?</div>
                                    <p class="mt-0.5" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                                        Por favor, <strong>adjunta una captura de pantalla</strong> abajo (o pégala directo con <kbd class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-[11px]">Ctrl + V</kbd>). Nos ayuda enormemente a reproducirlo y resolverlo en minutos.
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold mb-1.5" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                                    Tu Mensaje, Idea o Reporte <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    v-model="feedbackMessage"
                                    required
                                    rows="4"
                                    maxlength="2000"
                                    :placeholder="feedbackType === 'bug' ? 'Describe qué estabas haciendo, en qué dispositivo/navegador y qué error o comportamiento inesperado ocurrió... (puedes pegar capturas con Ctrl + V)' : 'Escribe aquí tu aporte con todos los detalles que consideres necesarios...'"
                                    class="w-full px-4 py-3 rounded-xl text-xs sm:text-sm border transition focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"
                                    :class="isDark ? 'bg-[#070A12] border-white/10 text-white placeholder:text-slate-500' : 'bg-slate-50 border-slate-200 text-slate-800 placeholder:text-slate-400'"
                                    @paste="handleFeedbackPaste"
                                    @dragover.prevent="onFeedbackDragOver"
                                    @dragleave.prevent="onFeedbackDragLeave"
                                    @drop.prevent="onFeedbackDrop"
                                ></textarea>
                                <div class="flex items-center justify-between mt-1 text-[11px]" :class="isDark ? 'text-slate-400' : 'text-slate-500'">
                                    <span>Llega directo al equipo técnico y pedagógico de Epycus</span>
                                    <span>{{ feedbackMessage.length }} / 2000 caracteres</span>
                                </div>
                            </div>

                            <!-- Subida de Captura de Pantalla / Imagen Opcional -->
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 flex items-center justify-between" :class="isDark ? 'text-slate-300' : 'text-slate-700'">
                                    <span>Captura de Pantalla o Imagen <span class="text-slate-400 font-normal">(Opcional)</span></span>
                                    <span class="text-[11px] font-mono text-cyan-400 flex items-center gap-1">
                                        <AppIcon name="clipboard" :size="12" />
                                        <span>Pega con Ctrl + V</span>
                                    </span>
                                </label>

                                <!-- Si no hay imagen seleccionada -->
                                <div
                                    v-if="!feedbackImagePreview"
                                    class="border-2 border-dashed rounded-2xl p-4 sm:p-5 text-center transition-all cursor-pointer relative"
                                    :class="[
                                        isDraggingFeedback
                                            ? 'border-cyan-400 bg-cyan-500/15 scale-[1.01] ring-2 ring-cyan-400/50'
                                            : (isDark ? 'border-white/15 bg-white/[0.02] hover:border-indigo-500/50 hover:bg-white/[0.04]' : 'border-slate-300 bg-slate-50 hover:border-indigo-400 hover:bg-indigo-50/30')
                                    ]"
                                    @click="feedbackImageInput?.click()"
                                    @dragover.prevent="onFeedbackDragOver"
                                    @dragenter.prevent="onFeedbackDragOver"
                                    @dragleave.prevent="onFeedbackDragLeave"
                                    @drop.prevent="onFeedbackDrop"
                                >
                                    <input
                                        ref="feedbackImageInput"
                                        type="file"
                                        accept="image/png, image/jpeg, image/jpg, image/webp"
                                        class="hidden"
                                        @change="onImageChange"
                                    />
                                    <div class="flex flex-col items-center justify-center gap-2 pointer-events-none">
                                        <div class="p-2.5 rounded-xl" :class="isDark ? 'bg-indigo-500/15 text-indigo-400' : 'bg-indigo-100 text-indigo-600'">
                                            <AppIcon name="camera" :size="20" />
                                        </div>
                                        <div class="text-xs font-semibold" :class="isDark ? 'text-slate-200' : 'text-slate-700'">
                                            <span>Haz clic para adjuntar, arrastra una imagen o pega con <kbd class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-[10px]">Ctrl + V</kbd></span>
                                        </div>
                                        <div class="text-[11px]" :class="isDark ? 'text-slate-400' : 'text-slate-500'">
                                            Formatos soportados: PNG, JPG, JPEG o WEBP (hasta 5 MB)
                                        </div>
                                    </div>
                                </div>

                                <!-- Previsualización de Imagen Cargada -->
                                <div
                                    v-else
                                    class="p-3.5 rounded-2xl border flex items-center justify-between gap-4"
                                    :class="isDark ? 'bg-[#070A12] border-indigo-500/40' : 'bg-indigo-50/50 border-indigo-200'"
                                >
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <img
                                            :src="feedbackImagePreview"
                                            alt="Preview Captura"
                                            class="w-14 h-14 object-cover rounded-xl border border-white/10 shrink-0 shadow-md"
                                        />
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold truncate" :class="isDark ? 'text-white' : 'text-slate-900'">
                                                {{ feedbackImage?.name }}
                                            </div>
                                            <div class="text-[11px] text-emerald-400 font-mono mt-0.5 flex items-center gap-1">
                                                <AppIcon name="check" :size="12" />
                                                <span>Captura lista para enviar ({{ (feedbackImage?.size / 1024 / 1024).toFixed(2) }} MB)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="p-2 rounded-xl text-rose-400 hover:text-rose-300 hover:bg-rose-500/15 border border-rose-500/30 transition-colors shrink-0 cursor-pointer"
                                        title="Eliminar captura"
                                        @click="removeImage"
                                    >
                                        <AppIcon name="trash-2" :size="16" />
                                    </button>
                                </div>
                            </div>

                            <!-- Banner de Alerta de Éxito -->
                            <div
                                v-if="feedbackSuccess"
                                class="p-4 rounded-xl border flex items-center gap-3 bg-emerald-500/15 border-emerald-500/30 text-emerald-400 text-xs sm:text-sm font-medium animate-fade-in"
                            >
                                <AppIcon name="check-circle" :size="20" class="shrink-0 text-emerald-400" />
                                <div>
                                    <div class="font-bold">¡Mensaje recibido con éxito!</div>
                                    <div class="text-xs text-emerald-300/90">Muchas gracias por ayudarnos a hacer de Epycus una plataforma cada día mejor.</div>
                                </div>
                            </div>

                            <!-- Banner de Error -->
                            <div
                                v-if="feedbackError"
                                class="p-4 rounded-xl border flex items-center gap-3 bg-rose-500/15 border-rose-500/30 text-rose-400 text-xs sm:text-sm font-medium"
                            >
                                <AppIcon name="alert-triangle" :size="18" class="shrink-0 text-rose-400" />
                                <span>{{ feedbackError }}</span>
                            </div>

                            <!-- Botón de Envío y Contacto Directo -->
                            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <button
                                    type="submit"
                                    :disabled="feedbackSubmitting || !feedbackMessage.trim()"
                                    class="w-full sm:w-auto px-7 py-3.5 rounded-xl font-bold text-white bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-lg shadow-indigo-600/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer"
                                >
                                    <AppIcon v-if="feedbackSubmitting" name="refresh-cw" :size="16" class="animate-spin" />
                                    <AppIcon v-else name="send" :size="16" />
                                    <span>{{ feedbackSubmitting ? 'Enviando al buzón...' : 'Enviar al Buzón de Epycus' }}</span>
                                </button>

                                <div class="text-xs text-center sm:text-right" :class="isDark ? 'text-slate-400' : 'text-slate-500'">
                                    <span>O escríbenos directamente a:</span>
                                    <a
                                        href="mailto:contacto@soltectos.com"
                                        class="block font-mono font-bold text-cyan-400 hover:underline mt-0.5"
                                    >
                                        contacto@soltectos.com
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- ── CTA Final de Registro ──────────────────────────────── -->
            <section class="py-20 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto text-center">
                <div
                    class="rounded-3xl p-8 sm:p-12 border backdrop-blur-xl shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-8 text-left"
                    :class="isDark ? 'bg-gradient-to-r from-indigo-950/90 via-[#0E1322] to-[#0A0E17] border-indigo-500/30' : 'bg-gradient-to-r from-indigo-50 via-white to-cyan-50 border-indigo-200 shadow-xl'"
                >
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-black tracking-tight" :class="isDark ? 'text-white' : 'text-slate-900'">
                            ¿Listo para dominar tu ciclo universitario?
                        </h2>
                        <p class="mt-3 max-w-xl text-sm sm:text-base" :class="isDark ? 'text-slate-300' : 'text-slate-600'">
                            Únete gratis a Epycus, configura tus horarios y empieza a estudiar con foco y consistencia desde hoy.
                        </p>
                        <div class="mt-6">
                            <a
                                :href="appRegisterUrl"
                                class="inline-flex items-center gap-2 px-8 py-4 text-base font-bold text-white rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-xl shadow-indigo-600/30 hover:scale-105 transition-all"
                            >
                                <AppIcon name="zap" :size="18" class="text-amber-300" />
                                <span>Crear Cuenta Gratuita (+50 XP)</span>
                            </a>
                        </div>
                    </div>
                    <img src="/assets/Edy.png" alt="Edy Mascot" class="w-36 h-36 object-contain flex-shrink-0" />
                </div>
            </section>
        </main>

        <!-- ── Pie de Página (Footer) ────────────────────────────────── -->
        <footer
            class="border-t py-12 text-xs px-4 sm:px-6 lg:px-8 transition-colors"
            :class="isDark ? 'bg-[#05080E] border-white/[0.08] text-slate-400' : 'bg-slate-100 border-slate-200 text-slate-600'"
        >
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <img src="/assets/images/logo.webp" alt="Epycus Logo" class="w-6 h-6 object-contain" />
                    <div>
                        <span class="font-bold" :class="isDark ? 'text-slate-200' : 'text-slate-800'">Epycus</span> — Sistema de Productividad y Enfoque Universitario
                        <p class="mt-0.5" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Protección de Datos Personales conforme a la Ley N.º 29733 (DS 016-2024-JUS)</p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <a href="/terms" class="hover:text-indigo-500 transition-colors">Términos de Uso</a>
                    <a :href="appLoginUrl" class="hover:text-indigo-500 transition-colors">Acceso de Estudiantes</a>
                    <span :class="isDark ? 'text-slate-400' : 'text-slate-500'">Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes smoothFloat {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-6px);
    }
}
.animate-smooth-float {
    animation: smoothFloat 8s ease-in-out infinite;
}

/* Barra de desplazamiento personalizada visible para móvil y escritorio */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(99, 102, 241, 0.6) rgba(255, 255, 255, 0.08);
}
.custom-scrollbar::-webkit-scrollbar {
    height: 8px;
    display: block;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(100, 116, 139, 0.15);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(90deg, #6366f1, #06b6d4);
    border-radius: 9999px;
}
</style>
