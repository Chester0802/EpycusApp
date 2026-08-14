<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});

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

const activeFaq = ref(null);
function toggleFaq(index) {
    activeFaq.value = activeFaq.value === index ? null : index;
}

const villains = [
    { name: 'La Postergación', image: '/assets/villains/Villano_postergación.png', desc: 'Retrasa el inicio de tareas clave hasta el último momento.' },
    { name: 'La Distracción', image: '/assets/villains/Villano_distracción.png', desc: 'Interrupciones constantes por celular y redes sociales.' },
    { name: 'La Ansiedad', image: '/assets/villains/Villano_ansiedad.png', desc: 'Parálisis por sobrepensamiento antes de empezar.' },
    { name: 'El Desorden', image: '/assets/villains/Villano_desorden.png', desc: 'Falta de planificación y pérdida de prioridades.' },
    { name: 'El Cansancio', image: '/assets/villains/Villano_cansancio.png', desc: 'Agotamiento mental por falta de descansos programados.' },
];

const faqs = [
    {
        q: '¿Cómo garantiza Epycus la privacidad de mis datos personales?',
        a: 'En cumplimiento estricto de la Ley N.º 29733 (Ley de Protección de Datos Personales de Perú) y su reglamento DS 016-2024-JUS, toda tu identidad real (nombre, correo) se almacena por separado y se reemplaza por un código de participante seudonimizado (ej. EPY-X9A2). Ningún dato personal es compartido en la telemetría ni en las publicaciones científicas.',
    },
    {
        q: '¿Qué es la Escala EPA y por qué debo completarla al registrarme?',
        a: 'La Escala de Procrastinación Académica (EPA) consta de 8 ítems psicométricos diseñados para medir tus niveles de autorregulación académica y postergación de actividades. Al completarla al inicio, recibes +50 XP de bienvenida y permites al sistema personalizar tu plan de intervención de 40 días.',
    },
    {
        q: '¿Puedo ingresar usando mi cuenta institucional de Google?',
        a: 'Sí, la plataforma cuenta con autenticación segura con Google OAuth de 1-clic. Al ingresar con tu correo institucional o personal, se genera tu credencial seudonimizada de forma automática.',
    },
    {
        q: '¿Cómo gano experiencia (XP), niveles y monedas dentro de la plataforma?',
        a: 'Ganarás XP cumpliendo hábitos diarios (+10 XP), completando sesiones de enfoque Pomodoro (+15 XP), superando misiones académicas y venciendo a los 5 Villanos Académicos (+100 XP). Al subir de nivel acumularás monedas (🪙) para desbloquear fondos de pantalla HD y marcos holográficos.',
    },
];

const features = [
    {
        gif: '/assets/gifs/habits.gif',
        icon: '🎯',
        title: 'Hábitos & Micro-rutinas',
        description: 'Fija y cumple objetivos diarios con topes anti-trampa y protección de racha diaria.',
    },
    {
        gif: '/assets/gifs/pomodoro.gif',
        icon: '⏱️',
        title: 'Pomodoro Sincronizado',
        description: 'Temporizador con validación de enfoque al 95% en servidor y reproductor de música Lo-Fi opcional.',
    },
    {
        gif: '/assets/gifs/missions.gif',
        icon: '🎯',
        title: 'Misiones y Objetivos',
        description: 'Descompón tus tareas complejas en metas manejables y gana experiencia acumulativa.',
    },
    {
        gif: '/assets/gifs/benny-typing-v2.gif',
        icon: '🤖',
        title: 'Asistente de IA con Guardarrieles',
        description: 'Tutor de autorregulación con cuota diaria de consultas y protocolo de ayuda.',
    },
    {
        gif: '/assets/gifs/achievements.gif',
        icon: '🏆',
        title: 'Recompensas e Insignias',
        description: 'Desbloquea logros, sube de nivel y colecciona reconocimientos por tu consistencia académica.',
    },
    {
        gif: '/assets/villains/Villano_postergación.png',
        icon: '⚔️',
        title: 'Batallas contra Villanos',
        description: 'Enfrenta a los 5 monstruos de la procrastinación basados en evidencia universitaria real.',
    },
];
</script>

<template>
    <Head title="Epycus — Plataforma de Autorregulación y Enfoque Académico" />

    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-purple-500 selection:text-white relative overflow-hidden">
        <!-- Fondos con gradientes dinámicos de luz -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-purple-600/30 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-cyan-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-pink-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Encabezado / Navegación -->
        <header class="sticky top-0 z-50 backdrop-blur-xl bg-slate-950/80 border-b border-slate-800/60 transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="/assets/images/logo.webp" alt="Epycus Logo" class="w-10 h-10 object-contain drop-shadow-md" />
                    <div>
                        <span class="font-extrabold text-xl tracking-tight bg-gradient-to-r from-purple-300 via-pink-200 to-cyan-300 bg-clip-text text-transparent">
                            Epycus
                        </span>
                        <span class="hidden sm:inline-block ml-2 text-[10px] font-semibold uppercase tracking-widest px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-300 border border-purple-500/20">
                            UPN Research
                        </span>
                    </div>
                </div>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="#caracteristicas" class="hover:text-purple-300 transition-colors">Características</a>
                    <a href="#villanos" class="hover:text-purple-300 transition-colors">Villanos</a>
                    <a href="#investigacion" class="hover:text-purple-300 transition-colors">Investigación</a>
                    <a href="#faq" class="hover:text-purple-300 transition-colors">FAQ</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a
                        :href="appLoginUrl"
                        class="px-4 py-2 text-sm font-semibold text-slate-300 hover:text-white rounded-lg hover:bg-slate-800/60 transition-all"
                    >
                        Iniciar Sesión
                    </a>
                    <a
                        :href="appRegisterUrl"
                        class="px-5 py-2.5 text-sm font-bold text-white rounded-xl bg-gradient-to-r from-purple-600 via-pink-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-600/30 hover:shadow-purple-600/50 transition-all hover:scale-[1.02] active:scale-[0.98]"
                    >
                        Registrarme 🚀
                    </a>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative pt-12 pb-16 lg:pt-16 lg:pb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Columna Izquierda: Texto principal -->
                <div class="lg:col-span-7 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-500/10 border border-purple-500/30 text-purple-300 text-xs font-semibold mb-6 backdrop-blur-md animate-pulse">
                        <span>🔬 Estudio de Campo Activo</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                        <span>Intervención Gamificada de 40 Días</span>
                    </div>

                    <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-tight">
                        Supera la <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-cyan-400 bg-clip-text text-transparent">Procrastinación Académica</span> con Autorregulación e IA
                    </h1>

                    <p class="mt-6 text-lg text-slate-300 leading-relaxed">
                        Una plataforma científica diseñada para estudiantes universitarios peruanos. Transforma tu hábito de estudio mediante técnicas Pomodoro sincrónicas, seguimiento de rutinas, batallas de villanos y tutoría inteligente.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a
                            :href="appRegisterUrl"
                            class="w-full sm:w-auto px-8 py-4 text-base font-bold text-white rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-xl shadow-purple-600/40 hover:shadow-purple-600/60 transition-all hover:scale-105 text-center"
                        >
                            Comenzar Ahora (+50 XP Gratis) 🚀
                        </a>
                        <a
                            href="#investigacion"
                            class="w-full sm:w-auto px-8 py-4 text-base font-semibold text-slate-300 hover:text-white rounded-2xl bg-slate-900/80 hover:bg-slate-800 border border-slate-800 transition-all text-center"
                        >
                            Conocer la Investigación 📊
                        </a>
                    </div>
                </div>

                <!-- Columna Derecha: Ilustración Hero + Edy Mascot -->
                <div class="lg:col-span-5 relative flex justify-center items-center">
                    <div class="relative w-full max-w-md">
                        <!-- Hero Image Frame -->
                        <div class="relative rounded-3xl overflow-hidden border border-purple-500/30 shadow-2xl shadow-purple-900/40 bg-slate-900/80">
                            <img src="/assets/images/login-hero.webp" alt="Epycus Interface Hero" class="w-full h-auto object-cover rounded-3xl opacity-90 hover:opacity-100 transition-opacity" />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                        </div>

                        <!-- Edy Mascot Badge Float -->
                        <div class="absolute -bottom-6 -left-6 rounded-2xl bg-slate-900/90 border border-purple-500/40 p-3 shadow-xl backdrop-blur-xl flex items-center gap-3 animate-bounce duration-[3000ms]">
                            <img src="/assets/Edy.png" alt="Mascota Edy IA" class="w-14 h-14 object-contain" />
                            <div>
                                <div class="text-xs font-bold text-white">Edy — Tutor IA</div>
                                <div class="text-[11px] text-purple-300 font-mono">"¡Te ayudo a enfocar tu tiempo!"</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Card / Mockup interactivo -->
            <div class="mt-20 relative max-w-5xl mx-auto">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-4 sm:p-6 backdrop-blur-2xl shadow-2xl shadow-purple-950/50">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-4 mb-6">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                            <span class="ml-2 font-mono text-xs text-slate-400">app.epycus.es — Panel de Enfoque Académico</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-mono text-slate-400">
                            <span>🪙 Monedas: 150</span>
                            <span class="text-purple-400 font-bold">Nivel 12</span>
                        </div>
                    </div>

                    <!-- Layout interno simulado con los GIFs reales -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-2xl bg-slate-950/90 p-5 border border-purple-500/20 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-purple-400 uppercase tracking-wider">⏱️ Pomodoro Enfoque</span>
                                    <img src="/assets/gifs/pomodoro.gif" alt="Pomodoro GIF" class="w-8 h-8 object-contain rounded-lg" />
                                </div>
                                <div class="text-3xl font-extrabold font-mono text-white mb-1">24:50</div>
                                <p class="text-xs text-slate-400">Sesión 3 de 8 diarias | +15 XP</p>
                            </div>
                            <div class="mt-4 w-full bg-slate-800 rounded-full h-2">
                                <div class="bg-gradient-to-r from-purple-500 to-cyan-400 h-2 rounded-full w-[85%]"></div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-950/90 p-5 border border-pink-500/20 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-pink-400 uppercase tracking-wider">🎯 Hábitos Cumplidos</span>
                                    <img src="/assets/gifs/habits.gif" alt="Hábitos GIF" class="w-8 h-8 object-contain rounded-lg" />
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-900 text-slate-200 border border-slate-800">
                                        <span>✔ Repasar lecturas</span>
                                        <span class="text-emerald-400 font-bold">+10 XP</span>
                                    </div>
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-900 text-slate-200 border border-slate-800">
                                        <span>✔ Resolver ejercicios</span>
                                        <span class="text-emerald-400 font-bold">+10 XP</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-950/90 p-5 border border-cyan-500/20 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-cyan-400 uppercase tracking-wider">⚔️ Jefe de la Semana</span>
                                    <img src="/assets/villains/Villano_postergación.png" alt="Villano" class="w-8 h-8 object-contain rounded-lg" />
                                </div>
                                <div class="flex items-center gap-3">
                                    <img src="/assets/villains/Villano_postergación.png" alt="Postergación" class="w-12 h-12 object-contain" />
                                    <div>
                                        <h4 class="font-bold text-sm text-white">La Postergación</h4>
                                        <p class="text-[11px] text-slate-400">Derrotable con 4 Pomodoros (+100 XP)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Barra de Estadísticas de la Investigación -->
        <section class="py-12 bg-slate-900/50 border-y border-slate-800/60 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold text-purple-400 font-mono">N=384</div>
                        <div class="mt-1 text-xs sm:text-sm text-slate-400 font-medium">Muestra Diagnosticada</div>
                    </div>
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold text-pink-400 font-mono">63.3%</div>
                        <div class="mt-1 text-xs sm:text-sm text-slate-400 font-medium">Acumulación de Obstáculos</div>
                    </div>
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold text-cyan-400 font-mono">40 Días</div>
                        <div class="mt-1 text-xs sm:text-sm text-slate-400 font-medium">Intervención Gamificada</div>
                    </div>
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold text-emerald-400 font-mono">100%</div>
                        <div class="mt-1 text-xs sm:text-sm text-slate-400 font-medium">Seudonimizado (Ley 29733)</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección: Los 5 Villanos Académicos -->
        <section id="villanos" class="py-20 lg:py-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/30 text-red-300 text-xs font-semibold mb-4">
                    <span>⚔️ Diagrama de Pareto Académico</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                    Enfrenta a los <span class="bg-gradient-to-r from-red-400 via-pink-400 to-purple-400 bg-clip-text text-transparent">5 Villanos de la Procrastinación</span>
                </h2>
                <p class="mt-4 text-slate-400 text-base">
                    Basados en el estudio empírico realizado a estudiantes universitarios. Vence a los jefes completando tus objetivos de estudio.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                <div
                    v-for="(v, index) in villains"
                    :key="index"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 flex flex-col items-center text-center hover:border-purple-500/40 hover:bg-slate-900/90 transition-all duration-300 group hover:-translate-y-1"
                >
                    <div class="w-24 h-24 mb-4 rounded-2xl bg-slate-950/80 p-2 border border-slate-800 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <img :src="v.image" :alt="v.name" class="w-full h-full object-contain" />
                    </div>
                    <h3 class="font-bold text-white text-base mb-2 group-hover:text-purple-300 transition-colors">{{ v.name }}</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">{{ v.desc }}</p>
                </div>
            </div>
        </section>

        <!-- Módulos / Características principales con GIFs -->
        <section id="caracteristicas" class="py-20 bg-slate-900/30 border-t border-slate-800/60 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                        Un Ecosistema Diseñado para el <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">Rendimiento Académico</span>
                    </h2>
                    <p class="mt-4 text-slate-400 text-base">
                        Combina herramientas probadas de autorregulación psicológica con dinámicas de juegos para vencer la postergación de tareas.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    <div
                        v-for="(item, index) in features"
                        :key="index"
                        class="rounded-3xl border border-slate-800/80 bg-slate-900/60 p-6 sm:p-8 hover:border-purple-500/40 hover:bg-slate-900/90 transition-all duration-300 hover:-translate-y-1 group"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                                {{ item.icon }}
                            </div>
                            <img :src="item.gif" :alt="item.title" class="w-12 h-12 object-contain rounded-xl border border-slate-800 bg-slate-950/80 p-1" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3 group-hover:text-purple-300 transition-colors">
                            {{ item.title }}
                        </h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            {{ item.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Investigación y Metodología -->
        <section id="investigacion" class="py-20 border-t border-slate-800/60 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-xs font-mono font-bold uppercase tracking-widest text-purple-400">Marco Metodológico</span>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-extrabold text-white leading-tight">
                        Basado en la Escala EPA y Evidencia Empírica Peruana
                    </h2>
                    <p class="mt-4 text-slate-300 text-sm sm:text-base leading-relaxed">
                        Epycus integra los 8 ítems psicométricos validados de la Escala de Procrastinación Académica (EPA) para medir los cambios de autorregulación pretest y postest durante 40 días de intervención.
                    </p>

                    <div class="mt-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="text-emerald-400 font-bold">✓</span>
                            <p class="text-xs sm:text-sm text-slate-300">
                                <strong>Diagnóstico Exploratorio Inicial:</strong> Estudio previo ($N=98$ y $N=31$) que determinó los principales desencadenantes de distracción.
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-emerald-400 font-bold">✓</span>
                            <p class="text-xs sm:text-sm text-slate-300">
                                <strong>Diagrama de Pareto de Obstáculos:</strong> Los 5 Villanos Académicos corresponden exactamente al 63.3% de causas acumuladas.
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-emerald-400 font-bold">✓</span>
                            <p class="text-xs sm:text-sm text-slate-300">
                                <strong>Protección Ley 29733:</strong> Ningún dato de salud mental ni PII sale del sistema; todo análisis se efectúa en Tidy Data seudonimizada con Python.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-800 bg-slate-950 p-6 sm:p-8 relative">
                    <h3 class="font-bold text-lg text-white mb-4">Escala EPA — 8 Ítems Estandarizados</h3>
                    <div class="space-y-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-300">
                            <strong>Item 2:</strong> Cuando tengo problemas para hacer las tareas, me pongo a hacer otras cosas.
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-300">
                            <strong>Item 5:</strong> Dejo la preparación de mis exámenes para el último momento.
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-300">
                            <strong>Item 10:</strong> Dejo para mañana lo que puedo hacer hoy.
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-300">
                            <strong>Item 14:</strong> Postergo los trabajos difíciles de mis asignaturas.
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <span class="text-[11px] text-purple-400 font-mono">Puntuación Likert 1–5 | Total acumulado de 8 a 40 puntos</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Preguntas Frecuentes (FAQ Accordion) -->
        <section id="faq" class="py-20 lg:py-32 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Preguntas Frecuentes</h2>
                <p class="mt-4 text-slate-400 text-sm">Resuelve tus dudas sobre la participación en el estudio y la plataforma.</p>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(faq, index) in faqs"
                    :key="index"
                    class="rounded-2xl border border-slate-800 bg-slate-900/60 overflow-hidden transition"
                >
                    <button
                        type="button"
                        class="w-full p-5 text-left font-semibold text-slate-200 flex items-center justify-between gap-4 hover:text-purple-300 transition-colors"
                        @click="toggleFaq(index)"
                    >
                        <span>{{ faq.q }}</span>
                        <span class="text-xl font-mono text-purple-400">{{ activeFaq === index ? '−' : '+' }}</span>
                    </button>
                    <div v-if="activeFaq === index" class="px-5 pb-5 text-sm text-slate-400 leading-relaxed border-t border-slate-800/40 pt-4">
                        {{ faq.a }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Banner CTA Final -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto text-center">
            <div class="rounded-3xl bg-gradient-to-r from-purple-900/60 via-indigo-900/60 to-slate-900 p-8 sm:p-12 border border-purple-500/30 backdrop-blur-xl shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-8 text-left">
                <div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                        ¿Listo para dominar tu tiempo y enfoque?
                    </h2>
                    <p class="mt-3 text-slate-300 max-w-xl text-sm sm:text-base">
                        Únete a los estudiantes universitarios en el estudio de autorregulación y transforma tu experiencia académica hoy mismo.
                    </p>
                    <div class="mt-6">
                        <a
                            :href="appRegisterUrl"
                            class="inline-block px-8 py-4 text-base font-bold text-white rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-xl shadow-purple-600/40 hover:scale-105 transition-all"
                        >
                            Crear Cuenta Gratuita (+50 XP) 🚀
                        </a>
                    </div>
                </div>
                <img src="/assets/Edy.png" alt="Edy Mascot" class="w-36 h-36 object-contain flex-shrink-0" />
            </div>
        </section>

        <!-- Pie de página (Footer) -->
        <footer class="border-t border-slate-800/80 py-12 bg-slate-950 text-xs text-slate-400 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <img src="/assets/images/logo.webp" alt="Epycus Logo" class="w-6 h-6 object-contain" />
                    <div>
                        <span class="font-bold text-slate-200">Epycus System</span> — Plataforma de Autorregulación Académica
                        <p class="mt-0.5 text-slate-400">Protección de Datos Personales conforme a la Ley N.º 29733 (DS 016-2024-JUS)</p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <a href="/terms" class="hover:text-purple-300 transition-colors">Términos & Consentimiento</a>
                    <a :href="appLoginUrl" class="hover:text-purple-300 transition-colors">Acceso Estudiantes</a>
                    <span class="text-slate-400">Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})</span>
                </div>
            </div>
        </footer>
    </div>
</template>
