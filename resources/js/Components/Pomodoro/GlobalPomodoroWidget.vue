<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Play, Pause, ExternalLink, X, CheckCircle, GripVertical, Coffee, FastForward } from '@lucide/vue';
import { usePomodoroState } from '@/Composables/usePomodoroState';

const page = usePage();
const activePomodoroProp = computed(() => page.props.activePomodoro);

const {
    mode,
    session,
    isPaused,
    formattedTime,
    progressPercent,
    remainingSeconds,
    totalSeconds,
    breakInfo,
    hasCompletedFocusModal,
    completedSessionInfo,
    pause,
    resume,
    skipBreak,
    syncWithInertiaProps,
    requestNotificationPermission,
    isWidgetHidden,
} = usePomodoroState();

// ── Arrastre Libre de la Píldora ───────────────────────────────────────────
const pillEl = ref(null);
const isDragging = ref(false);
const pillPos = ref({ x: null, y: null });
let dragStartPointer = { x: 0, y: 0 };
let dragStartPos = { x: 0, y: 0 };

const isPomodoroPage = computed(() => {
    try {
        if (typeof route === 'function' && route().current) {
            return route().current('pomodoro.index');
        }
    } catch {
        // Fallback
    }
    return page.url.startsWith('/pomodoro');
});

const isVisible = computed(() => {
    return (mode.value === 'focus' || mode.value === 'break') && !isPomodoroPage.value && !isWidgetHidden.value;
});

function togglePlayPause() {
    if (mode.value === 'focus') {
        if (isPaused.value) {
            resume();
        } else {
            pause();
        }
    }
}

function goToPomodoro() {
    if (isDragging.value) return;
    hasCompletedFocusModal.value = false;
    router.visit(route('pomodoro.index'));
}

// ── Control de Arrastre (Drag and Drop) ─────────────────────────────────────
function onPointerDown(e) {
    if (e.button !== undefined && e.button !== 0) return;
    if (e.target.closest('button')) return;

    isDragging.value = true;
    dragStartPointer = { x: e.clientX, y: e.clientY };

    const el = pillEl.value;
    if (el) {
        const rect = el.getBoundingClientRect();
        dragStartPos = { x: rect.left, y: rect.top };
        pillPos.value = { x: rect.left, y: rect.top };
    }

    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onPointerUp);
}

function onPointerMove(e) {
    if (!isDragging.value) return;
    const dx = e.clientX - dragStartPointer.x;
    const dy = e.clientY - dragStartPointer.y;

    const el = pillEl.value;
    const width = el ? el.offsetWidth : 210;
    const height = el ? el.offsetHeight : 54;

    const maxX = typeof window !== 'undefined' ? window.innerWidth - width - 8 : 1000;
    const maxY = typeof window !== 'undefined' ? window.innerHeight - height - 8 : 1000;

    const newX = Math.max(8, Math.min(maxX, dragStartPos.x + dx));
    const newY = Math.max(8, Math.min(maxY, dragStartPos.y + dy));

    pillPos.value = { x: newX, y: newY };
}

function onPointerUp() {
    if (!isDragging.value) return;
    setTimeout(() => {
        isDragging.value = false;
    }, 50);
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);

    try {
        if (pillPos.value.x !== null) {
            localStorage.setItem('epycus_pomodoro_pill_pos', JSON.stringify(pillPos.value));
        }
    } catch {
        // Silencioso
    }
}

watch(
    activePomodoroProp,
    (newSess) => {
        syncWithInertiaProps(newSess);
        if (newSess && newSess.status === 'running') {
            requestNotificationPermission();
        }
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    syncWithInertiaProps(activePomodoroProp.value);

    // Cargar posición guardada del usuario
    try {
        if (typeof window !== 'undefined' && window.localStorage) {
            const saved = localStorage.getItem('epycus_pomodoro_pill_pos');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (parsed && typeof parsed.x === 'number' && typeof parsed.y === 'number') {
                    const maxX = window.innerWidth - 220;
                    const maxY = window.innerHeight - 60;
                    pillPos.value = {
                        x: Math.max(8, Math.min(maxX, parsed.x)),
                        y: Math.max(8, Math.min(maxY, parsed.y)),
                    };
                }
            }
        }
    } catch {
        // Silencioso
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
    }
});
</script>

<template>
    <!-- 1. Floating Pill Widget Totalmente Arrastrable -->
    <div
        v-if="isVisible"
        ref="pillEl"
        class="fixed z-[85] select-none touch-none transition-shadow"
        :style="
            pillPos.x !== null
                ? { left: `${pillPos.x}px`, top: `${pillPos.y}px`, right: 'auto', bottom: 'auto' }
                : { right: '1.5rem', bottom: '1.5rem' }
        "
        :class="isDragging ? 'cursor-grabbing shadow-2xl scale-[1.03]' : 'cursor-grab'"
        @pointerdown="onPointerDown"
    >
        <div
            class="flex items-center gap-2.5 px-3.5 py-2 rounded-2xl border shadow-2xl backdrop-blur-xl bg-surface-raised/95 text-content-primary transition-all duration-300"
            :class="[
                mode === 'break'
                    ? 'border-emerald-500/50 shadow-emerald-500/10 hover:border-emerald-500/80'
                    : 'border-primary/40 shadow-primary/10 hover:border-primary/70'
            ]"
        >
            <!-- Grip handle para arrastrar -->
            <div class="text-content-muted hover:text-content-secondary cursor-grab active:cursor-grabbing p-0.5">
                <GripVertical :size="14" />
            </div>

            <!-- Icono y Luz pulsante -->
            <div class="relative flex items-center justify-center cursor-pointer" @click="goToPomodoro">
                <span class="text-lg">{{ mode === 'break' ? '☕' : '🍅' }}</span>
                <span
                    v-if="mode === 'focus' && !isPaused"
                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-success animate-ping"
                ></span>
                <span
                    v-if="mode === 'focus' && !isPaused"
                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-success"
                ></span>
                <span
                    v-if="mode === 'break'"
                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-400 animate-pulse"
                ></span>
            </div>

            <!-- Tiempo y Estado -->
            <div class="flex flex-col cursor-pointer min-w-[76px]" @click="goToPomodoro">
                <div class="flex items-center gap-1.5">
                    <span
                        class="font-mono font-black text-sm tracking-tight"
                        :class="mode === 'break' ? 'text-emerald-400' : 'text-primary-strong'"
                    >
                        {{ formattedTime }}
                    </span>
                    <span
                        v-if="mode === 'focus' && isPaused"
                        class="text-[9px] uppercase font-extrabold px-1 rounded bg-warning/20 text-warning"
                    >
                        Pausa
                    </span>
                    <span
                        v-else-if="mode === 'break'"
                        class="text-[9px] uppercase font-extrabold px-1 rounded bg-emerald-500/20 text-emerald-300"
                    >
                        {{ breakInfo.isLong ? 'Descanso+' : 'Descanso' }}
                    </span>
                </div>
                <span class="text-[9px] text-content-secondary -mt-0.5 leading-tight truncate max-w-[130px]">
                    <template v-if="mode === 'break'">
                        Recargando • {{ progressPercent }}%
                    </template>
                    <template v-else-if="session?.mission_title">
                        {{ session.mission_title }}
                    </template>
                    <template v-else>
                        {{ isPaused ? 'En pausa' : 'Enfoque' }} • {{ progressPercent }}%
                    </template>
                </span>
            </div>

            <!-- Botones de Control Rápido -->
            <template v-if="mode === 'focus'">
                <button
                    type="button"
                    class="p-1 rounded-xl bg-surface hover:bg-surface-sunken border border-border text-content-primary transition-all active:scale-95 cursor-pointer"
                    :title="isPaused ? 'Reanudar' : 'Pausar'"
                    @click.stop="togglePlayPause"
                >
                    <Play v-if="isPaused" :size="13" class="text-success fill-success" />
                    <Pause v-else :size="13" class="text-warning fill-warning" />
                </button>
            </template>
            <template v-else-if="mode === 'break'">
                <button
                    type="button"
                    class="p-1 rounded-xl bg-surface hover:bg-surface-sunken border border-border text-content-secondary hover:text-content-primary transition-all active:scale-95 cursor-pointer"
                    title="Saltar descanso"
                    @click.stop="skipBreak"
                >
                    <FastForward :size="13" />
                </button>
            </template>

            <!-- Botón Ir a Pomodoro -->
            <button
                type="button"
                class="p-1 rounded-xl transition-all active:scale-95 cursor-pointer"
                :class="[
                    mode === 'break'
                        ? 'bg-emerald-600 text-white hover:bg-emerald-500'
                        : 'bg-primary-strong text-on-primary-strong hover:opacity-90'
                ]"
                title="Abrir Pomodoro completo"
                @click.stop="goToPomodoro"
            >
                <ExternalLink :size="13" />
            </button>
        </div>
    </div>

    <!-- 2. Modal Global de Notificación de Pomodoro Finalizado -->
    <Teleport to="body">
        <div
            v-if="hasCompletedFocusModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
        >
            <div
                class="relative w-full max-w-md p-6 overflow-hidden rounded-3xl bg-surface-raised border-2 border-primary shadow-2xl text-center space-y-4"
            >
                <button
                    type="button"
                    class="absolute top-4 right-4 p-1 rounded-full text-content-muted hover:text-content-primary transition-all cursor-pointer"
                    @click="hasCompletedFocusModal = false"
                >
                    <X :size="18" />
                </button>

                <div class="w-16 h-16 mx-auto rounded-3xl bg-success/15 text-success flex items-center justify-center text-3xl shadow-inner animate-bounce">
                    🎉
                </div>

                <div>
                    <h3 class="font-display font-bold text-2xl text-content-primary">
                        ¡Bloque de Enfoque Terminado!
                    </h3>
                    <p class="mt-2 text-sm text-content-secondary leading-relaxed">
                        ¡Felicitaciones! Has completado tu tiempo de estudio ({{ completedSessionInfo?.minutes || 25 }} min). Se han acreditado tus minutos de foco y experiencia (+XP).
                    </p>
                </div>

                <div class="p-3 rounded-2xl bg-surface border border-border text-xs text-content-secondary flex items-center justify-center gap-2">
                    <CheckCircle :size="16" class="text-success" />
                    <span>Tu descanso activo de 5 min ya ha comenzado automáticamente.</span>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <button
                        type="button"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-primary-strong text-on-primary-strong font-bold text-sm shadow-md hover:opacity-90 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        @click="goToPomodoro"
                    >
                        ☕ Ver Descanso en Pomodoro
                    </button>
                    <button
                        type="button"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-surface-raised border border-border text-content-secondary hover:text-content-primary font-semibold text-sm transition-all cursor-pointer"
                        @click="hasCompletedFocusModal = false"
                    >
                        Continuar aquí
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
