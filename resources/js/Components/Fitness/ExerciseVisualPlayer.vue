<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Play, Pause, RotateCcw, Clock, Dumbbell, Sparkles, Flame } from '@lucide/vue';

const props = defineProps({
    exercise: {
        type: Object,
        required: true,
    },
    autoPlay: {
        type: Boolean,
        default: true,
    },
    showTimer: {
        type: Boolean,
        default: true,
    },
});

const currentFrameIndex = ref(0); // 0, 1, 2
const isPlaying = ref(props.autoPlay);
const direction = ref(1); // 1 = forward, -1 = reverse for smooth loop
let loopInterval = null;

// Timer de ejercicio
const timerDuration = computed(() => props.exercise?.default_duration_seconds || 45);
const timerRemaining = ref(timerDuration.value);
const isTimerActive = ref(false);
let timerInterval = null;

const frames = computed(() => {
    if (props.exercise?.frames && Array.isArray(props.exercise.frames) && props.exercise.frames.length > 0) {
        return props.exercise.frames;
    }
    const slug = props.exercise?.image_slug || props.exercise?.slug || 'push-up';
    return [
        `/assets/exercises/${slug}/frame-1.png`,
        `/assets/exercises/${slug}/frame-2.png`,
        `/assets/exercises/${slug}/frame-3.png`,
    ];
});

const currentFrame = computed(() => {
    return frames.value[currentFrameIndex.value] || frames.value[0];
});

function startLoop() {
    stopLoop();
    if (!isPlaying.value) return;

    loopInterval = setInterval(() => {
        if (frames.value.length <= 1) return;

        let next = currentFrameIndex.value + direction.value;
        if (next >= frames.value.length) {
            direction.value = -1;
            next = frames.value.length - 2;
        } else if (next < 0) {
            direction.value = 1;
            next = 1;
        }
        currentFrameIndex.value = Math.max(0, Math.min(frames.value.length - 1, next));
    }, 600);
}

function stopLoop() {
    if (loopInterval) {
        clearInterval(loopInterval);
        loopInterval = null;
    }
}

function togglePlay() {
    isPlaying.value = !isPlaying.value;
    if (isPlaying.value) {
        startLoop();
    } else {
        stopLoop();
    }
}

function selectFrame(idx) {
    isPlaying.value = false;
    stopLoop();
    currentFrameIndex.value = idx;
}

// Control del temporizador de serie
function toggleTimer() {
    if (isTimerActive.value) {
        stopTimer();
    } else {
        startTimer();
    }
}

function startTimer() {
    isTimerActive.value = true;
    if (timerRemaining.value <= 0) {
        timerRemaining.value = timerDuration.value;
    }

    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (timerRemaining.value > 0) {
            timerRemaining.value -= 1;
        } else {
            stopTimer();
            playTimerEndBeep();
        }
    }, 1000);
}

function stopTimer() {
    isTimerActive.value = false;
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

function resetTimer() {
    stopTimer();
    timerRemaining.value = timerDuration.value;
}

function playTimerEndBeep() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 880; // La5
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.5);
    } catch {
        // Fallback
    }
}

watch(() => props.exercise, () => {
    currentFrameIndex.value = 0;
    resetTimer();
    if (isPlaying.value) startLoop();
});

onMounted(() => {
    if (isPlaying.value) startLoop();
});

onUnmounted(() => {
    stopLoop();
    stopTimer();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Visualizador de Fotogramas SVG/PNG con Contraste Adaptativo Perfecto -->
        <div class="relative w-full rounded-3xl bg-slate-100/90 dark:bg-slate-950/70 border border-slate-200 dark:border-white/10 p-4 flex flex-col items-center justify-center overflow-hidden shadow-inner">
            <!-- Badges Superiores -->
            <div class="absolute top-3 left-3 flex items-center gap-1.5 z-10">
                <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full bg-primary/15 text-primary-strong border border-primary/20 backdrop-blur-md shadow-sm">
                    {{ exercise.category || 'Fitness' }}
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-surface-raised border border-border text-content-secondary shadow-sm">
                    {{ exercise.difficulty || 'Fácil' }}
                </span>
            </div>

            <!-- Botón Play/Pause Flotante -->
            <button
                type="button"
                class="absolute top-3 right-3 p-2 rounded-xl bg-surface-raised/95 border border-border text-content-primary shadow-md hover:bg-surface transition-all active:scale-95 z-10 cursor-pointer"
                :title="isPlaying ? 'Pausar animación' : 'Reproducir animación'"
                @click="togglePlay"
            >
                <Pause v-if="isPlaying" :size="15" class="text-primary-strong fill-primary-strong" />
                <Play v-else :size="15" class="text-content-primary fill-content-primary" />
            </button>

            <!-- Ilustración en Fotograma Activo con Visibilidad Garantizada (Negro en claro, Blanco en oscuro) -->
            <div class="w-full max-w-[280px] h-[210px] sm:h-[240px] flex items-center justify-center relative my-2">
                <img
                    :src="currentFrame"
                    :alt="`${exercise.name} - Paso ${currentFrameIndex + 1}`"
                    class="max-h-full max-w-full object-contain exercise-figure select-none transition-transform duration-200"
                    loading="lazy"
                />
            </div>

            <!-- Selector de Pasos -->
            <div class="w-full flex items-center justify-center gap-2 pt-2 border-t border-border/60">
                <button
                    v-for="(f, idx) in frames"
                    :key="idx"
                    type="button"
                    class="px-3 py-1 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1"
                    :class="[
                        currentFrameIndex === idx
                            ? 'bg-primary-strong text-on-primary-strong shadow-md scale-105'
                            : 'bg-surface-raised border border-border text-content-secondary hover:text-content-primary hover:bg-surface'
                    ]"
                    @click="selectFrame(idx)"
                >
                    <span>Paso {{ idx + 1 }}</span>
                    <span v-if="idx === 0" class="text-[9px] opacity-75">(Inicio)</span>
                    <span v-else-if="idx === 1" class="text-[9px] opacity-75">(Foco)</span>
                    <span v-else class="text-[9px] opacity-75">(Retorno)</span>
                </button>
            </div>
        </div>

        <!-- Detalles del Ejercicio e Instrucciones -->
        <div class="space-y-3">
            <div>
                <h3 class="font-display font-black text-lg text-content-primary leading-tight">
                    {{ exercise.name }}
                </h3>
                <div class="flex items-center gap-2 text-xs text-content-secondary mt-1">
                    <span class="font-bold text-primary-strong">Músculos activos:</span>
                    <span>{{ exercise.target_muscles }}</span>
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-surface border border-border/80 text-xs leading-relaxed text-content-secondary shadow-sm">
                <p class="font-bold text-content-primary mb-1 flex items-center gap-1.5">
                    <span>📋</span> Instrucciones de ejecución:
                </p>
                <p>{{ exercise.instructions }}</p>
            </div>

            <!-- Temporizador de Serie Guiada -->
            <div v-if="showTimer" class="p-3 rounded-2xl bg-surface-raised border border-border flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-primary/15 text-primary-strong flex items-center justify-center font-bold">
                        <Clock :size="18" />
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-extrabold tracking-wider text-content-muted block">Temporizador de Serie</span>
                        <span class="font-mono font-black text-lg text-content-primary">
                            {{ String(Math.floor(timerRemaining / 60)).padStart(2, '0') }}:{{ String(timerRemaining % 60).padStart(2, '0') }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="p-2.5 rounded-xl border border-border bg-surface text-content-secondary hover:text-content-primary transition-all active:scale-95 cursor-pointer shadow-sm"
                        title="Reiniciar temporizador"
                        @click="resetTimer"
                    >
                        <RotateCcw :size="14" />
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                        :class="[
                            isTimerActive
                                ? 'bg-warning text-on-warning hover:opacity-90'
                                : 'bg-primary-strong text-on-primary-strong hover:opacity-90'
                        ]"
                        @click="toggleTimer"
                    >
                        <Pause v-if="isTimerActive" :size="14" />
                        <Play v-else :size="14" />
                        <span>{{ isTimerActive ? 'Pausar' : 'Comenzar Serie' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* En Modo Claro: la figura blanca original se vuelve silueta negra pura */
.exercise-figure {
    filter: brightness(0) opacity(0.88);
}

/* En Modo Oscuro: la figura blanca original se mantiene blanca pura */
:global(.dark) .exercise-figure,
:global([data-theme='dark']) .exercise-figure {
    filter: brightness(1) opacity(1) drop-shadow(0 2px 8px rgba(255, 255, 255, 0.2));
}
</style>
