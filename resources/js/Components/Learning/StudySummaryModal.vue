<script setup>
import {
    BookOpen,
    X,
    CheckCircle2,
    List,
    Network,
    ArrowUpRight,
    Copy,
    Check,
    Flame,
    Zap,
    ExternalLink
} from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    chunk: { type: Object, default: null },
    relatedChunks: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'openRelated', 'openNote', 'startRecall', 'copyNotebookLM']);

const copied = ref(false);

function handleCopy() {
    if (!props.chunk) return;
    const text = `# 🧩 ${props.chunk.label} (${props.chunk.course_name})\n\n` +
        `> **Idea Clave:** ${props.chunk.summary}\n\n` +
        (props.chunk.key_points?.length ? `## 📑 Puntos Clave:\n${props.chunk.key_points.map(p => `- ${p}`).join('\n')}\n\n` : '') +
        (props.chunk.why_it_matters ? `## ⚡ Aplicación Profesional:\n${props.chunk.why_it_matters}\n\n` : '');

    navigator.clipboard.writeText(text).then(() => {
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 3000);
    });
}
</script>

<template>
    <div
        v-if="show && chunk"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md animate-fade-in"
        @click.self="emit('close')"
    >
        <div class="relative w-full max-w-2xl bg-white dark:bg-surface rounded-3xl border border-slate-200/90 dark:border-border shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Barra Superior -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-border/80 bg-slate-50/70 dark:bg-surface-raised">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: chunk.color || '#6366f1' }" />
                    <span class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-content-secondary truncate">
                        {{ chunk.course_name }}
                    </span>
                    <span v-if="chunk.category" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200/80 dark:bg-surface-sunken text-slate-700 dark:text-content-muted">
                        {{ chunk.category }}
                    </span>
                </div>
                <button
                    type="button"
                    class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-surface-sunken transition-colors"
                    @click="emit('close')"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Contenido Scrollable -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1 custom-scrollbar">
                
                <!-- Título del Concepto -->
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-content-primary tracking-tight">
                        🧩 {{ chunk.label }}
                    </h2>
                </div>

                <!-- 💡 Idea Clave (Resumen Atómico) -->
                <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/40 space-y-1.5">
                    <span class="text-[11px] font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-400 flex items-center gap-1.5">
                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                        Idea Clave
                    </span>
                    <p class="text-sm sm:text-base text-emerald-950 dark:text-emerald-100 font-medium leading-relaxed">
                        {{ chunk.summary || 'Concepto fundamental registrado en tus notas.' }}
                    </p>
                </div>

                <!-- 📑 Desglose en Puntos Clave (Chunking) -->
                <div v-if="chunk.key_points && chunk.key_points.length > 0" class="p-4 rounded-2xl bg-slate-50 dark:bg-surface-raised border border-slate-200/70 dark:border-border/60 space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-content-muted flex items-center gap-1.5">
                        <List class="w-4 h-4 text-indigo-500" />
                        Desglose Esencial
                    </span>
                    <ul class="space-y-2">
                        <li
                            v-for="(point, idx) in chunk.key_points"
                            :key="idx"
                            class="text-xs sm:text-sm text-slate-700 dark:text-content-secondary flex items-start gap-2.5 font-normal"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0" />
                            <span>{{ point }}</span>
                        </li>
                    </ul>
                </div>

                <!-- ⚡ Aplicación Profesional -->
                <div v-if="chunk.why_it_matters" class="p-3.5 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/70 dark:border-amber-800/40 space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-400 flex items-center gap-1.5">
                        <Zap class="w-3.5 h-3.5 text-amber-600" />
                        Aplicación Profesional
                    </span>
                    <p class="text-xs text-amber-950 dark:text-amber-200 leading-relaxed">
                        {{ chunk.why_it_matters }}
                    </p>
                </div>

                <!-- 🔗 Conceptos Relacionados -->
                <div v-if="relatedChunks.length > 0" class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-content-muted flex items-center gap-1.5">
                        <Network class="w-4 h-4 text-indigo-500" />
                        Conceptos Relacionados (Navegables)
                    </span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="(rel, idx) in relatedChunks"
                            :key="idx"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-surface-raised hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:border-indigo-300 border border-slate-200 dark:border-border text-xs font-bold text-slate-800 dark:text-content-primary hover:text-indigo-700 transition-all shadow-sm group"
                            @click="emit('openRelated', rel.node)"
                        >
                            <span class="text-[10px] text-slate-400 font-normal">[{{ rel.label || 'conecta con' }}]</span>
                            <span>{{ rel.node.label }}</span>
                            <ArrowUpRight class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Barra Inferior de Acciones -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-border/80 bg-slate-50/70 dark:bg-surface-raised flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="px-3.5 py-2 rounded-xl bg-white dark:bg-surface hover:bg-slate-100 border border-slate-200 dark:border-border text-xs font-bold text-slate-700 dark:text-content-primary transition-all flex items-center gap-1.5 shadow-sm"
                        @click="emit('openNote', chunk.course_id)"
                    >
                        <BookOpen class="w-3.5 h-3.5 text-indigo-500" />
                        <span>Abrir Apuntes del Curso</span>
                    </button>
                    <button
                        type="button"
                        class="px-3.5 py-2 rounded-xl bg-white dark:bg-surface hover:bg-slate-100 border border-slate-200 dark:border-border text-xs font-bold text-slate-700 dark:text-content-primary transition-all flex items-center gap-1.5 shadow-sm"
                        @click="handleCopy"
                    >
                        <Check v-if="copied" class="w-3.5 h-3.5 text-emerald-600" />
                        <Copy v-else class="w-3.5 h-3.5 text-blue-500" />
                        <span>{{ copied ? '¡Copiado!' : 'Copiar Markdown' }}</span>
                    </button>
                </div>

                <button
                    type="button"
                    class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white text-xs font-bold shadow-md hover:scale-105 active:scale-95 transition-all flex items-center gap-1.5"
                    @click="emit('startRecall', chunk)"
                >
                    <span>Practicar Active Recall ➔</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.3);
    border-radius: 4px;
}
</style>
