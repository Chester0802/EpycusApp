<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import { Play, Pause, ExternalLink, X, CheckCircle, GripVertical } from '@lucide/vue';

const page = usePage();
const activePomodoro = computed(() => page.props.activePomodoro);

const remainingSeconds = ref(0);
const totalSeconds = ref(0);
const isPaused = ref(false);
const isCompletedModalOpen = ref(false);
const completedSessionInfo = ref(null);
let ticker = null;
let clockOffsetMs = 0;

// Arrastre libre de la píldora
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

function csrfHeader() {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

// Síntesis de sonido melódico con Web Audio API (C5 - E5 - G5 - C6)
function playPomodoroEndMelody() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const notes = [523.25, 659.25, 783.99, 1046.50]; // Arpegio Do Mayor
        notes.forEach((freq, idx) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.value = freq;
            const start = ctx.currentTime + idx * 0.12;
            const end = start + 0.7;
            gain.gain.setValueAtTime(0, start);
            gain.gain.linearRampToValueAtTime(0.35, start + 0.04);
            gain.gain.exponentialRampToValueAtTime(0.001, end);
            osc.start(start);
            osc.stop(end);
        });
    } catch {
        // Fallback en caso de bloqueo de autoplay
    }
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
        navigator.vibrate([300, 150, 300, 150, 500]);
    }
}

function sendDesktopNotification(title, body) {
    try {
        if (typeof window !== 'undefined' && 'Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification(title, {
                    body,
                    icon: '/favicon.ico',
                });
            } else if (Notification.permission === 'default') {
                Notification.requestPermission().then((perm) => {
                    if (perm === 'granted') {
                        new Notification(title, { body, icon: '/favicon.ico' });
                    }
                });
            }
        }
    } catch {
        // Ignorar errores de notificación
    }
}

function requestNotificationPermission() {
    if (typeof window !== 'undefined' && 'Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

function syncFromSession(sess) {
    if (!sess) {
        stopTicker();
        remainingSeconds.value = 0;
        return;
    }

    if (sess.server_now) {
        clockOffsetMs = Date.now() - new Date(sess.server_now).getTime();
    }

    totalSeconds.value = (sess.planned_minutes || 25) * 60;
    isPaused.value = sess.status === 'paused';

    const startedMs = new Date(sess.started_at).getTime();
    const pausedMs = sess.paused_at ? new Date(sess.paused_at).getTime() : null;
    const nowMs = Date.now() - clockOffsetMs;
    const effectiveNowMs = isPaused.value && pausedMs ? pausedMs : nowMs;

    const elapsedSeconds = Math.max(0, Math.floor((effectiveNowMs - startedMs) / 1000) - (sess.total_paused_seconds || 0));
    remainingSeconds.value = Math.max(0, totalSeconds.value - elapsedSeconds);

    if (sess.status === 'running') {
        startTicker();
    } else {
        stopTicker();
    }
}

function startTicker() {
    stopTicker();
    ticker = setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value -= 1;
        } else {
            handleSessionCompleted();
        }
    }, 1000);
}

function stopTicker() {
    if (ticker) {
        clearInterval(ticker);
        ticker = null;
    }
}

async function handleSessionCompleted() {
    stopTicker();
    const sess = activePomodoro.value;
    if (!sess) return;

    playPomodoroEndMelody();
    sendDesktopNotification(
        '🍅 ¡Bloque Pomodoro Finalizado!',
        `¡Excelente trabajo! Completaste ${sess.planned_minutes} min de enfoque. Es hora de tomar tu descanso.`
    );

    completedSessionInfo.value = {
        minutes: sess.planned_minutes,
    };
    isCompletedModalOpen.value = true;

    // Completar en el backend para acreditar XP
    try {
        await fetch(route('pomodoro.complete', { id: sess.id }), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
        });
        router.reload({ only: ['activePomodoro', 'auth', 'flash'] });
    } catch {
        // Fallback
    }
}

async function togglePause() {
    const sess = activePomodoro.value;
    if (!sess) return;
    const action = isPaused.value ? 'resume' : 'pause';
    try {
        const res = await fetch(route(`pomodoro.${action}`, { id: sess.id }), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
        });
        if (res.ok) {
            router.reload({ only: ['activePomodoro'] });
        }
    } catch {
        // Error handling
    }
}

function goToPomodoro() {
    if (isDragging.value) return;
    isCompletedModalOpen.value = false;
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

const formattedTime = computed(() => {
    const s = Math.max(0, remainingSeconds.value);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
});

const progressPercent = computed(() => {
    if (totalSeconds.value <= 0) return 100;
    const done = totalSeconds.value - remainingSeconds.value;
    return Math.min(100, Math.max(0, Math.round((done / totalSeconds.value) * 100)));
});

watch(activePomodoro, (newSess) => {
    syncFromSession(newSess);
    if (newSess && newSess.status === 'running') {
        requestNotificationPermission();
    }
}, { immediate: true, deep: true });

onMounted(() => {
    syncFromSession(activePomodoro.value);

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
    stopTicker();
    if (typeof window !== 'undefined') {
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
    }
});
</script>

<template>
    <!-- 1. Floating Pill Widget Totalmente Arrastrable (Visible en cualquier módulo excepto /pomodoro) -->
    <div
        v-if="activePomodoro && !isPomodoroPage"
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
            class="flex items-center gap-2.5 px-3.5 py-2 rounded-2xl border shadow-2xl backdrop-blur-xl bg-surface-raised/95 border-primary/40 text-content-primary hover:border-primary/70 active:border-primary"
        >
            <!-- Grip handle para arrastrar -->
            <div class="text-content-muted hover:text-content-secondary cursor-grab active:cursor-grabbing p-0.5">
                <GripVertical :size="14" />
            </div>

            <!-- Icono y Luz pulsante -->
            <div class="relative flex items-center justify-center cursor-pointer" @click="goToPomodoro">
                <span class="text-lg">🍅</span>
                <span
                    v-if="!isPaused"
                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-success animate-ping"
                ></span>
                <span
                    v-if="!isPaused"
                    class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-success"
                ></span>
            </div>

            <!-- Tiempo y Estado -->
            <div class="flex flex-col cursor-pointer min-w-[70px]" @click="goToPomodoro">
                <div class="flex items-center gap-1.5">
                    <span class="font-mono font-black text-sm tracking-tight text-primary-strong">
                        {{ formattedTime }}
                    </span>
                    <span
                        v-if="isPaused"
                        class="text-[9px] uppercase font-extrabold px-1 rounded bg-warning/20 text-warning"
                    >
                        Pausa
                    </span>
                </div>
                <span class="text-[9px] text-content-secondary -mt-0.5 leading-tight truncate">
                    {{ isPaused ? 'En pausa' : 'Enfoque' }} • {{ progressPercent }}%
                </span>
            </div>

            <!-- Botón Pausa / Reanudar -->
            <button
                type="button"
                class="p-1 rounded-xl bg-surface hover:bg-surface-sunken border border-border text-content-primary transition-all active:scale-95 cursor-pointer"
                :title="isPaused ? 'Reanudar' : 'Pausar'"
                @click.stop="togglePause"
            >
                <Play v-if="isPaused" :size="13" class="text-success fill-success" />
                <Pause v-else :size="13" class="text-warning fill-warning" />
            </button>

            <!-- Botón Ir a Pomodoro -->
            <button
                type="button"
                class="p-1 rounded-xl bg-primary-strong text-on-primary-strong hover:opacity-90 transition-all active:scale-95 cursor-pointer"
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
            v-if="isCompletedModalOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
        >
            <div
                class="relative w-full max-w-md p-6 overflow-hidden rounded-3xl bg-surface-raised border-2 border-primary shadow-2xl text-center space-y-4"
            >
                <button
                    type="button"
                    class="absolute top-4 right-4 p-1 rounded-full text-content-muted hover:text-content-primary transition-all cursor-pointer"
                    @click="isCompletedModalOpen = false"
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
                        ¡Felicitaciones! Has completado tu tiempo de estudio. Se han acreditado tus minutos de foco y experiencia (XP).
                    </p>
                </div>

                <div class="p-3 rounded-2xl bg-surface border border-border text-xs text-content-secondary flex items-center justify-center gap-2">
                    <CheckCircle :size="16" class="text-success" />
                    <span>Tu progreso se guardó automáticamente. Es hora de tu descanso.</span>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <button
                        type="button"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-primary-strong text-on-primary-strong font-bold text-sm shadow-md hover:opacity-90 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        @click="goToPomodoro"
                    >
                        ☕ Iniciar Descanso en Pomodoro
                    </button>
                    <button
                        type="button"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-surface-raised border border-border text-content-secondary hover:text-content-primary font-semibold text-sm transition-all cursor-pointer"
                        @click="isCompletedModalOpen = false"
                    >
                        Continuar aquí
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
