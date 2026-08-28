<script setup>
import { ref, computed, watch } from 'vue';
import {
    RotateCcw,
    X,
    CheckCircle2,
    Trophy,
    ArrowRight
} from '@lucide/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    chunks: { type: Array, default: () => [] },
    initialIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'evaluated', 'openStudy']);

const currentIndex = ref(0);
const isFlipped = ref(false);
const isCompleted = ref(false);
const evaluatedCount = ref(0);
const scoreHistory = ref([]);

watch(
    () => props.show,
    (val) => {
        if (val) {
            currentIndex.value = Math.max(0, Math.min(props.initialIndex, (props.chunks.length || 1) - 1));
            isFlipped.value = false;
            isCompleted.value = false;
            evaluatedCount.value = 0;
            scoreHistory.value = [];
        }
    }
);

const currentChunk = computed(() => {
    if (!props.chunks || props.chunks.length === 0) return null;
    return props.chunks[currentIndex.value] || props.chunks[0];
});

function toggleFlip() {
    isFlipped.value = !isFlipped.value;
}

function handleGrade(delta) {
    if (!currentChunk.value) return;

    emit('evaluated', {
        chunkId: currentChunk.value.id,
        delta: delta,
    });

    evaluatedCount.value++;
    scoreHistory.value.push(delta);

    // Verificar si es la última pregunta
    if (currentIndex.value >= props.chunks.length - 1) {
        setTimeout(() => {
            isCompleted.value = true;
        }, 400);
    } else {
        setTimeout(() => {
            isFlipped.value = false;
            currentIndex.value++;
        }, 400);
    }
}

function restartSession() {
    currentIndex.value = 0;
    isFlipped.value = false;
    isCompleted.value = false;
    evaluatedCount.value = 0;
    scoreHistory.value = [];
}
</script>

<template>
    <div
        v-if="show && (currentChunk || isCompleted)"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md animate-fade-in select-none"
        @click.self="emit('close')"
    >
        <div class="relative w-full max-w-lg flex flex-col items-center">
            
            <!-- ── PANTALLA DE SESIÓN COMPLETADA ────────────────────────────── -->
            <div
                v-if="isCompleted"
                class="w-full bg-white dark:bg-surface rounded-3xl p-8 border border-border shadow-2xl text-center space-y-6 animate-fade-in"
            >
                <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 flex items-center justify-center mx-auto shadow-inner">
                    <Trophy class="w-8 h-8" />
                </div>

                <div class="space-y-1">
                    <h3 class="text-2xl font-black text-content-primary">
                        ¡Sesión Completada!
                    </h3>
                    <p class="text-xs text-content-secondary">
                        Has puesto a prueba tu memoria con {{ evaluatedCount }} tarjetas. Tu retención neuronal se ha actualizado.
                    </p>
                </div>

                <!-- Botones de Acción Final -->
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <button
                        type="button"
                        class="py-2.5 px-4 rounded-2xl bg-surface-raised hover:bg-surface-sunken border border-border text-xs font-bold text-content-primary transition-all"
                        @click="restartSession"
                    >
                        Practicar de nuevo
                    </button>
                    <button
                        type="button"
                        class="py-2.5 px-4 rounded-2xl bg-primary-strong hover:bg-primary-strong/90 text-white text-xs font-bold shadow-md transition-all"
                        @click="emit('close')"
                    >
                        Volver al Deck
                    </button>
                </div>
            </div>

            <!-- ── FLASHCARD INTERACTIVA EN PROCESO ────────────────────────── -->
            <div v-else-if="currentChunk" class="w-full space-y-3">
                
                <!-- Barra Superior de Progreso -->
                <div class="w-full flex items-center justify-between text-white/90 text-xs font-semibold px-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/10 border border-white/20">
                            {{ currentChunk.course_name }}
                        </span>
                        <span class="text-slate-400 text-[11px]">
                            Tarjeta {{ currentIndex + 1 }} de {{ chunks.length }}
                        </span>
                    </div>

                    <button
                        type="button"
                        class="p-1 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors"
                        @click="emit('close')"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Barra de Progreso Superior -->
                <div class="w-full h-1 bg-white/10 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-rose-500 transition-all duration-300"
                        :style="{ width: `${((currentIndex + 1) / chunks.length) * 100}%` }"
                    />
                </div>

                <!-- ── CONTENEDOR 3D FLIP CARD ──────────────────────────────────── -->
                <div class="perspective-1000 w-full min-h-[360px] sm:min-h-[400px] cursor-pointer" @click="toggleFlip">
                    <div
                        class="relative w-full h-full duration-500 transform-style-3d transition-transform rounded-3xl"
                        :class="isFlipped ? 'rotate-y-180' : ''"
                    >
                        
                        <!-- ── CARA FRONTAL (ANVERSO: LA PREGUNTA) ─────────────── -->
                        <div class="absolute inset-0 backface-hidden bg-white dark:bg-surface border border-border/80 rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl">
                            <div class="flex items-center justify-between text-xs text-content-secondary">
                                <span class="text-xs font-bold text-rose-600 dark:text-rose-400">
                                    Pregunta de Retención
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full bg-surface-raised text-content-primary font-bold text-[10px] border border-border">
                                    Dominio actual: {{ currentChunk.mastery || 70 }}%
                                </span>
                            </div>

                            <!-- Pregunta Central -->
                            <div class="my-auto text-center space-y-3 py-4">
                                <span class="text-xs font-semibold text-content-secondary uppercase tracking-wider block">
                                    {{ currentChunk.label }}
                                </span>
                                <h3 class="text-lg sm:text-xl font-bold text-content-primary leading-snug px-2">
                                    {{ currentChunk.quiz_question || `¿Cuál es el rol o principio central de ${currentChunk.label}?` }}
                                </h3>
                            </div>

                            <!-- Indicador de Giro -->
                            <div class="pt-4 border-t border-border flex items-center justify-center gap-2 text-rose-600 dark:text-rose-400 text-xs font-bold">
                                <RotateCcw class="w-4 h-4" />
                                <span>Toca para ver la respuesta</span>
                            </div>
                        </div>

                        <!-- ── CARA POSTERIOR (REVERSO: LA RESPUESTA) ──────────── -->
                        <div
                            class="absolute inset-0 backface-hidden rotate-y-180 bg-white dark:bg-surface border border-indigo-200 dark:border-indigo-800/60 rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl"
                            @click.stop
                        >
                            <div class="flex items-center justify-between text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                <span class="flex items-center gap-1.5">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-500" />
                                    Respuesta Clave
                                </span>
                                <button
                                    type="button"
                                    class="text-content-secondary hover:text-content-primary flex items-center gap-1 text-[11px]"
                                    @click="toggleFlip"
                                >
                                    <RotateCcw class="w-3.5 h-3.5" />
                                    <span>Volver</span>
                                </button>
                            </div>

                            <!-- Respuesta Central -->
                            <div class="my-auto text-center space-y-2 py-4">
                                <p class="text-sm sm:text-base font-semibold text-content-primary leading-relaxed px-2">
                                    {{ currentChunk.quiz_answer || currentChunk.summary }}
                                </p>
                            </div>

                            <!-- 3 Botones de Calificación -->
                            <div class="space-y-2 pt-4 border-t border-border">
                                <p class="text-center text-xs font-bold text-content-secondary">
                                    ¿Cómo fue tu recuerdo?
                                </p>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        class="py-2.5 px-2 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                        @click="handleGrade(-10)"
                                    >
                                        <span>Difícil</span>
                                        <span class="text-[10px] font-normal opacity-90">-10%</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="py-2.5 px-2 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                        @click="handleGrade(5)"
                                    >
                                        <span>Regular</span>
                                        <span class="text-[10px] font-normal opacity-90">+5%</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="py-2.5 px-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                        @click="handleGrade(15)"
                                    >
                                        <span>Fácil</span>
                                        <span class="text-[10px] font-normal opacity-90">+15%</span>
                                    </button>
                                </div>
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
