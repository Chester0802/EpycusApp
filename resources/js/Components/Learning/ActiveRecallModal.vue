<script setup>
import { ref, computed, watch } from 'vue';
import {
    RotateCcw,
    X,
    CheckCircle2,
    Trophy,
    HelpCircle,
    ArrowRight
} from '@lucide/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    chunks: { type: Array, default: () => [] },
    initialIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'evaluated']);

const currentIndex = ref(0);
const isFlipped = ref(false);
const isCompleted = ref(false);
const evaluatedCount = ref(0);

watch(
    () => props.show,
    (val) => {
        if (val) {
            currentIndex.value = Math.max(0, Math.min(props.initialIndex, (props.chunks.length || 1) - 1));
            isFlipped.value = false;
            isCompleted.value = false;
            evaluatedCount.value = 0;
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

    // Si es la última tarjeta, completar sesión
    if (currentIndex.value >= props.chunks.length - 1) {
        setTimeout(() => {
            isCompleted.value = true;
        }, 350);
    } else {
        setTimeout(() => {
            isFlipped.value = false;
            currentIndex.value++;
        }, 350);
    }
}

function restartSession() {
    currentIndex.value = 0;
    isFlipped.value = false;
    isCompleted.value = false;
    evaluatedCount.value = 0;
}
</script>

<template>
    <div
        v-if="show && (currentChunk || isCompleted)"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md animate-fade-in select-none"
        @click.self="emit('close')"
    >
        <div class="relative w-full max-w-lg flex flex-col items-center">
            
            <!-- ── PANTALLA: SESIÓN COMPLETADA ──────────────────────────────── -->
            <div
                v-if="isCompleted"
                class="w-full bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-2xl text-center space-y-6 animate-fade-in"
            >
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto shadow-inner">
                    <Trophy class="w-8 h-8" />
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-2xl font-black text-slate-900">
                        ¡Sesión de Recall Completada!
                    </h3>
                    <p class="text-sm text-slate-600">
                        Has puesto a prueba tu recuerdo con <strong>{{ evaluatedCount }}</strong> tarjetas. Tu dominio global ha sido actualizado en vivo.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <button
                        type="button"
                        class="py-3 px-4 rounded-2xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition-all active:scale-95"
                        @click="restartSession"
                    >
                        Practicar de nuevo
                    </button>
                    <button
                        type="button"
                        class="py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition-all active:scale-95"
                        @click="emit('close')"
                    >
                        Volver al Deck
                    </button>
                </div>
            </div>

            <!-- ── FLASHCARD INTERACTIVA (CUADRO BLANCO SÓLIDO TOTAL) ───────── -->
            <div v-else-if="currentChunk" class="w-full space-y-3">
                
                <!-- Barra Superior de Navegación -->
                <div class="w-full flex items-center justify-between text-white text-xs font-bold px-2">
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-sm">
                            {{ currentChunk.course_name }}
                        </span>
                        <span class="text-slate-300 font-semibold text-xs">
                            {{ currentIndex + 1 }} de {{ chunks.length }}
                        </span>
                    </div>

                    <button
                        type="button"
                        class="p-1.5 rounded-xl text-slate-300 hover:text-white hover:bg-white/10 transition-colors"
                        @click="emit('close')"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- ── CONTENEDOR 3D FLIP CARD CON ALTURA DEFINIDA ──────────────── -->
                <div class="perspective-1000 w-full h-[440px] sm:h-[470px] cursor-pointer relative" @click="toggleFlip">
                    <div
                        class="w-full h-full duration-500 transform-style-3d transition-transform relative"
                        :class="isFlipped ? 'rotate-y-180' : ''"
                    >
                        
                        <!-- ── CARA FRONTAL (ANVERSO: PREGUNTA) ────────────────── -->
                        <div class="absolute inset-0 w-full h-full backface-hidden bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl overflow-hidden">
                            <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100 shrink-0">
                                <span class="text-[11px] font-black uppercase tracking-wider text-indigo-600 flex items-center gap-1.5">
                                    <HelpCircle class="w-4 h-4" />
                                    Active Recall Flashcard
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[11px]">
                                    Dominio: {{ currentChunk.mastery || 70 }}%
                                </span>
                            </div>

                            <!-- Pregunta Central Destacada (Texto Negro de Alto Contraste) -->
                            <div class="my-auto text-center space-y-3 py-4 overflow-y-auto max-h-[220px]">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block">
                                    {{ currentChunk.label }}
                                </span>
                                <h3 class="text-xl sm:text-2xl font-black text-slate-900 leading-snug px-2">
                                    {{ currentChunk.quiz_question || `¿Cuál es el concepto o propósito central de ${currentChunk.label}?` }}
                                </h3>
                            </div>

                            <!-- Botón para Voltear -->
                            <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-indigo-600 text-xs font-bold shrink-0">
                                <RotateCcw class="w-4 h-4" />
                                <span>Toca para voltear y ver la respuesta</span>
                            </div>
                        </div>

                        <!-- ── CARA POSTERIOR (REVERSO: RESPUESTA & CALIFICACIÓN) ── -->
                        <div
                            class="absolute inset-0 w-full h-full backface-hidden rotate-y-180 bg-white border border-indigo-200 rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl overflow-hidden"
                            @click.stop
                        >
                            <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100 shrink-0">
                                <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 flex items-center gap-1.5">
                                    <CheckCircle2 class="w-4 h-4" />
                                    Respuesta Clave Explicada
                                </span>
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-slate-800 flex items-center gap-1 text-[11px] font-semibold"
                                    @click="toggleFlip"
                                >
                                    <RotateCcw class="w-3.5 h-3.5" />
                                    <span>Ver pregunta</span>
                                </button>
                            </div>

                            <!-- Respuesta Central (Texto Nítido y Claro) -->
                            <div class="my-auto text-center space-y-2 py-3 overflow-y-auto max-h-[190px]">
                                <p class="text-sm sm:text-base font-semibold text-slate-800 leading-relaxed px-2">
                                    {{ currentChunk.quiz_answer || currentChunk.summary }}
                                </p>
                            </div>

                            <!-- 3 Botones de Calificación Claros -->
                            <div class="space-y-2 pt-3 border-t border-slate-100 shrink-0">
                                <p class="text-center text-xs font-bold text-slate-500">
                                    ¿Cómo fue tu retención mental?
                                </p>
                                <div class="grid grid-cols-3 gap-2.5">
                                    <button
                                        type="button"
                                        class="py-3 px-2 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-black shadow-sm active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                        @click="handleGrade(-10)"
                                    >
                                        <span>Difícil</span>
                                        <span class="text-[10px] font-normal opacity-80">-10%</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="py-3 px-2 rounded-2xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-black shadow-sm active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                        @click="handleGrade(5)"
                                    >
                                        <span>Regular</span>
                                        <span class="text-[10px] font-normal opacity-80">+5%</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="py-3 px-2 rounded-2xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-black shadow-sm active:scale-95 transition-all flex flex-col items-center justify-center gap-0.5"
                                        @click="handleGrade(15)"
                                    >
                                        <span>Fácil</span>
                                        <span class="text-[10px] font-normal opacity-80">+15%</span>
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
