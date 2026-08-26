<script setup>
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            concentration: 50,
            discipline: 50,
            resilience: 50,
            serenity: 50,
            attack: 50,
        }),
    },
    size: {
        type: Number,
        default: 180,
    },
});

// 5 ejes en orden de pentágono comenzando arriba (0 = Foco, 1 = Disciplina, 2 = Resistencia, 3 = Serenidad, 4 = Ataque)
const axes = [
    { key: 'concentration', label: 'Foco 🧠', color: '#0284c7' },
    { key: 'discipline', label: 'Disciplina 🎯', color: '#8b5cf6' },
    { key: 'resilience', label: 'Resistencia 🌱', color: '#10b981' },
    { key: 'serenity', label: 'Serenidad 🧘', color: '#0d9488' },
    { key: 'attack', label: 'Ataque ⚔️', color: '#ef4444' },
];

// Coordenadas fijas optimizadas para el centro del SVG (320x200)
const centerX = 160;
const centerY = 95;
const radius = 52; // Radio máximo del pentágono (100%)

// Generar puntos para un polígono de porcentaje dado
function getPolygonPoints(percent) {
    const r = (radius * percent) / 100;
    const points = [];
    for (let i = 0; i < 5; i++) {
        const angle = (i * 2 * Math.PI) / 5 - Math.PI / 2;
        const x = centerX + r * Math.cos(angle);
        const y = centerY + r * Math.sin(angle);
        points.push(`${x.toFixed(1)},${y.toFixed(1)}`);
    }
    return points.join(' ');
}

// Polígono del usuario según sus estadísticas actuales (mínimo 15% para visibilidad)
const userPolygonPoints = computed(() => {
    const points = [];
    axes.forEach((axis, i) => {
        const rawVal = props.stats[axis.key] ?? 50;
        const val = Math.max(15, Math.min(100, rawVal));
        const r = (radius * val) / 100;
        const angle = (i * 2 * Math.PI) / 5 - Math.PI / 2;
        const x = centerX + r * Math.cos(angle);
        const y = centerY + r * Math.sin(angle);
        points.push(`${x.toFixed(1)},${y.toFixed(1)}`);
    });
    return points.join(' ');
});

// Coordenadas individuales para cada vértice
const vertexPositions = computed(() => {
    return axes.map((axis, i) => {
        const rawVal = props.stats[axis.key] ?? 50;
        const val = Math.max(15, Math.min(100, rawVal));
        const r = (radius * val) / 100;
        const angle = (i * 2 * Math.PI) / 5 - Math.PI / 2;
        return {
            key: axis.key,
            label: axis.label,
            value: rawVal,
            color: axis.color,
            x: centerX + r * Math.cos(angle),
            y: centerY + r * Math.sin(angle),
        };
    });
});

// Coordenadas de las etiquetas de texto (con suficiente espacio en el viewBox 320x200)
const labelPositions = computed(() => {
    const labelRadius = radius + 20;
    return axes.map((axis, i) => {
        const angle = (i * 2 * Math.PI) / 5 - Math.PI / 2;
        const x = centerX + labelRadius * Math.cos(angle);
        const y = centerY + labelRadius * Math.sin(angle);

        let textAnchor = 'middle';
        let offsetX = 0;
        let offsetY = 4;

        if (i === 0) {
            // Arriba (Foco)
            textAnchor = 'middle';
            offsetY = -4;
        } else if (i === 1) {
            // Derecha arriba (Disciplina)
            textAnchor = 'start';
            offsetX = 3;
            offsetY = 3;
        } else if (i === 2) {
            // Derecha abajo (Resistencia)
            textAnchor = 'start';
            offsetX = 2;
            offsetY = 11;
        } else if (i === 3) {
            // Izquierda abajo (Serenidad)
            textAnchor = 'end';
            offsetX = -2;
            offsetY = 11;
        } else if (i === 4) {
            // Izquierda arriba (Ataque)
            textAnchor = 'end';
            offsetX = -3;
            offsetY = 3;
        }

        return {
            key: axis.key,
            label: axis.label,
            value: props.stats[axis.key] ?? 50,
            x: x + offsetX,
            y: y + offsetY,
            textAnchor,
        };
    });
});
</script>

<template>
    <div class="relative flex flex-col items-center justify-center w-full max-w-[320px] mx-auto">
        <svg
            viewBox="0 0 320 200"
            class="w-full h-auto select-none drop-shadow-sm"
        >
            <defs>
                <!-- Gradiente de relleno del radar del héroe -->
                <linearGradient id="heroRadarFill" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#6366f1" stop-opacity="0.45" />
                    <stop offset="50%" stop-color="#38bdf8" stop-opacity="0.30" />
                    <stop offset="100%" stop-color="#10b981" stop-opacity="0.45" />
                </linearGradient>
                <filter id="radarGlowEffect" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="2.5" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>

            <!-- Rejillas concéntricas del pentágono (20%, 40%, 60%, 80%, 100%) -->
            <polygon
                v-for="p in [100, 80, 60, 40, 20]"
                :key="'grid-' + p"
                :points="getPolygonPoints(p)"
                fill="none"
                class="stroke-slate-300 dark:stroke-slate-700 transition-colors duration-300"
                :stroke-width="p === 100 ? 1.5 : 1"
                :stroke-dasharray="p < 100 ? '3 3' : 'none'"
            />

            <!-- Ejes radiales desde el centro a cada vértice -->
            <line
                v-for="(axis, i) in axes"
                :key="'axis-' + i"
                :x1="centerX"
                :y1="centerY"
                :x2="centerX + radius * Math.cos((i * 2 * Math.PI) / 5 - Math.PI / 2)"
                :y2="centerY + radius * Math.sin((i * 2 * Math.PI) / 5 - Math.PI / 2)"
                class="stroke-slate-300 dark:stroke-slate-700"
                stroke-width="1"
            />

            <!-- Polígono de Atributos del Jugador (Animado con Glow y Gradiente) -->
            <polygon
                :points="userPolygonPoints"
                fill="url(#heroRadarFill)"
                stroke="#6366f1"
                stroke-width="2.2"
                filter="url(#radarGlowEffect)"
                class="transition-all duration-700 ease-out"
            />

            <!-- Vértices con puntos luminosos y bordes nítidos -->
            <circle
                v-for="v in vertexPositions"
                :key="'vertex-' + v.key"
                :cx="v.x"
                :cy="v.y"
                r="4"
                :fill="v.color"
                class="stroke-white dark:stroke-slate-900 transition-all duration-700 ease-out cursor-pointer hover:r-5.5"
                stroke-width="1.5"
            >
                <title>{{ v.label }}: {{ v.value }}/100</title>
            </circle>

            <!-- Etiquetas de texto en cada eje con alto contraste -->
            <text
                v-for="lbl in labelPositions"
                :key="'lbl-' + lbl.key"
                :x="lbl.x"
                :y="lbl.y"
                :text-anchor="lbl.textAnchor"
                class="text-[10px] font-bold fill-slate-800 dark:fill-slate-100 select-none"
            >
                {{ lbl.label }}
                <tspan class="text-[9.5px] font-extrabold fill-indigo-600 dark:fill-indigo-400">({{ lbl.value }})</tspan>
            </text>
        </svg>
    </div>
</template>
