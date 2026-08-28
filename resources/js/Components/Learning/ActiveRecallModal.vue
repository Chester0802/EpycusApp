<script setup>
import { ref, watch } from 'vue';
import {
    HelpCircle,
    RotateCcw,
    X,
    Sparkles,
    CheckCircle2,
    Flame,
    ArrowRight
} from '@lucide/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    chunk: { type: Object, default: null },
    totalChunksCount: { type: Number, default: 1 },
    currentIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'evaluated', 'next']);

const isFlipped = ref(false);
const evaluating = ref(false);
const showConfetti = ref(false);

watch(
    () => props.chunk,
    () => {
        isFlipped.value = false;
        evaluating.value = false;
        showConfetti.value = false;
    }
);

function toggleFlip() {
    isFlipped.value = !isFlipped.value;
}

function handleGrade(delta) {
    if (evaluating.value || !props.chunk) return;
    evaluating.value = true;
    showConfetti.value = delta > 0;

    emit('evaluated', {
        chunkId: props.chunk.id,
        delta: delta
    });

    // Pequeño delay de satisfacción antes de pasar al siguiente chunk si existe
    setTimeout(() => {
        emit('next');
        isFlipped.value = false;
        evaluating.value = false;
        showConfetti.value = false;
    }, 600);
}
</script>

<template>
    <div
        v-if="show && chunk"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/85 backdrop-blur-lg animate-fade-in select-none"
        @click.self="emit('close')"
    >
        <div class="relative w-full max-w-xl flex flex-col items-center">
            
            <!-- Barra Superior de Control de la Sesión de Recall -->
            <div class="w-full flex items-center justify-between pb-3 text-white/80 text-xs font-bold">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30">
                        Active Recall Flashcard
                    </span>
                    <span class="text-slate-400">
                        {{ chunk.course_name }}
                    </span>
                </div>
                <button
                    type="button"
                    class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors"
                    @click="emit('close')"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- ── CONTENEDOR 3D FLIP CARD ──────────────────────────────────── -->
            <div class="perspective-1000 w-full min-h-[380px] sm:min-h-[420px] cursor-pointer" @click="toggleFlip">
                <div
                    class="relative w-full h-full duration-500 transform-style-3d transition-transform rounded-3xl"
                    :class="isFlipped ? 'rotate-y-180' : ''"
                >
                    
                    <!-- ── 1. CARA FRONTAL (ANVERSO: EL RETO / PREGUNTA) ──────── -->
                    <div class="absolute inset-0 backface-hidden bg-gradient-to-br from-slate-900 via-slate-900 to-rose-950/40 border border-slate-700/80 rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span class="flex items-center gap-1.5 text-rose-400 font-bold">
                                <HelpCircle class="w-4 h-4" />
                                Pregunta de Retención
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 font-black text-[10px]">
                                Dominio: {{ chunk.mastery || 70 }}%
                            </span>
                        </div>

                        <!-- Pregunta Central -->
                        <div class="my-auto text-center space-y-4 py-4">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest block">
                                🧩 {{ chunk.label }}
                            </span>
                            <h3 class="text-lg sm:text-2xl font-black text-white leading-relaxed tracking-tight px-2">
                                {{ chunk.quiz_question || `¿Cuál es la función, definición y propósito central de ${chunk.label}?` }}
                            </h3>
                        </div>

                        <!-- Indicador de Giro -->
                        <div class="pt-4 border-t border-slate-800/80 flex items-center justify-center gap-2 text-rose-400 text-xs font-bold animate-pulse">
                            <RotateCcw class="w-4 h-4" />
                            <span>Toca para voltear la tarjeta y ver la respuesta</span>
                        </div>
                    </div>

                    <!-- ── 2. CARA POSTERIOR (REVERSO: LA RESPUESTA & EVALUACIÓN) ── -->
                    <div
                        class="absolute inset-0 backface-hidden rotate-y-180 bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/50 border border-indigo-500/40 rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl"
                        @click.stop
                    >
                        <div class="flex items-center justify-between text-xs text-indigo-400 font-bold">
                            <span class="flex items-center gap-1.5">
                                <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                                Respuesta Clave Explicada
                            </span>
                            <button
                                type="button"
                                class="text-slate-400 hover:text-white flex items-center gap-1 text-[11px]"
                                @click="toggleFlip"
                            >
                                <RotateCcw class="w-3 h-3" />
                                <span>Volver a la pregunta</span>
                            </button>
                        </div>

                        <!-- Respuesta Central -->
                        <div class="my-auto text-center space-y-3 py-4">
                            <p class="text-base sm:text-xl font-bold text-slate-100 leading-relaxed px-2">
                                {{ chunk.quiz_answer || chunk.summary }}
                            </p>
                        </div>

                        <!-- 3 Botones de Calificación de Dominio -->
                        <div class="space-y-2 pt-4 border-t border-slate-800">
                            <p class="text-center text-xs font-bold text-slate-300">
                                ¿Cómo fue tu recuerdo mental?
                            </p>
                            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                                <button
                                    type="button"
                                    class="py-3 px-2 rounded-2xl bg-rose-600/90 hover:bg-rose-600 text-white text-xs font-black shadow-lg shadow-rose-600/20 active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                    @click="handleGrade(-10)"
                                >
                                    <span>🔴 Difícil</span>
                                    <span class="text-[10px] font-normal opacity-80">-10% dominio</span>
                                </button>
                                <button
                                    type="button"
                                    class="py-3 px-2 rounded-2xl bg-amber-600/90 hover:bg-amber-600 text-white text-xs font-black shadow-lg shadow-amber-600/20 active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                    @click="handleGrade(5)"
                                >
                                    <span>🟡 Regular</span>
                                    <span class="text-[10px] font-normal opacity-80">+5% dominio</span>
                                </button>
                                <button
                                    type="button"
                                    class="py-3 px-2 rounded-2xl bg-emerald-600/90 hover:bg-emerald-600 text-white text-xs font-black shadow-lg shadow-emerald-600/20 active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                    @click="handleGrade(15)"
                                >
                                    <span>🟢 Fácil</span>
                                    <span class="text-[10px] font-normal opacity-80">+15% dominio</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}
.transform-style-3d {
    transform-style: preserve-3d;
}
.backface-hidden {
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}
.rotate-y-180 {
    transform: rotateY(180deg);
}
</style>
