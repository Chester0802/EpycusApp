import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { emitSync, SYNC_EVENTS, reloadInertiaProps } from './useAppSync';

// ── Estado Reactivo Global (Singleton) ──────────────────────────────────────
const STORAGE_KEY = 'epycus_pomodoro_state';

const mode = ref('idle'); // 'idle' | 'focus' | 'break'
const session = ref(null);
const targetEndMs = ref(null);
const totalSeconds = ref(0);
const remainingSeconds = ref(0);
const isPaused = ref(false);
const pausedAtMs = ref(null);
const breakInfo = ref({
    durationMinutes: 5,
    isLong: false,
});
const busy = ref(false);
const hasCompletedFocusModal = ref(false);
const completedSessionInfo = ref(null);
const breakJustFinished = ref(false);
const isWidgetHidden = ref(false); // Flag para ocultar el widget en módulos específicos (como salas)

let ticker = null;
let isInitialized = false;

function csrfHeader() {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

// ── Sonidos y Notificaciones ────────────────────────────────────────────────
function playFocusEndMelody() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const notes = [523.25, 659.25, 783.99, 1046.5]; // Arpegio Do Mayor
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
        // Fallback
    }
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
        navigator.vibrate([300, 150, 300, 150, 500]);
    }
}

function playBreakEndChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.25, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.7);
        osc.start();
        osc.stop(ctx.currentTime + 0.7);
    } catch {
        // Fallback
    }
    if (typeof navigator !== 'undefined' && navigator.vibrate) {
        navigator.vibrate([200, 100, 200]);
    }
}

function sendNotification(title, body) {
    try {
        if (typeof window !== 'undefined' && 'Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification(title, { body, icon: '/favicon.ico' });
            } else if (Notification.permission === 'default') {
                Notification.requestPermission().then((perm) => {
                    if (perm === 'granted') {
                        new Notification(title, { body, icon: '/favicon.ico' });
                    }
                });
            }
        }
    } catch {
        // Ignorar
    }
}

export function requestNotificationPermission() {
    if (typeof window !== 'undefined' && 'Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// ── Persistencia en LocalStorage ────────────────────────────────────────────
function saveStateToStorage() {
    if (typeof window === 'undefined' || !window.localStorage) return;
    try {
        const snapshot = {
            mode: mode.value,
            session: session.value,
            targetEndMs: targetEndMs.value,
            totalSeconds: totalSeconds.value,
            remainingSeconds: remainingSeconds.value,
            isPaused: isPaused.value,
            pausedAtMs: pausedAtMs.value,
            breakInfo: breakInfo.value,
            savedAt: Date.now(),
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(snapshot));
    } catch {
        // Fallback
    }
}

function loadStateFromStorage() {
    if (typeof window === 'undefined' || !window.localStorage) return null;
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function clearStorage() {
    if (typeof window !== 'undefined' && window.localStorage) {
        localStorage.removeItem(STORAGE_KEY);
    }
}

// ── Temporizador Unificado de Alta Precisión ────────────────────────────────
function tick() {
    if (mode.value === 'idle') {
        stopTicker();
        return;
    }

    const now = Date.now();

    if (isPaused.value) {
        // En pausa, los segundos restantes no se decrementan
        return;
    }

    if (!targetEndMs.value) {
        return;
    }

    const diffSeconds = Math.max(0, Math.round((targetEndMs.value - now) / 1000));
    remainingSeconds.value = diffSeconds;

    if (diffSeconds <= 0) {
        if (mode.value === 'focus') {
            handleFocusFinished();
        } else if (mode.value === 'break') {
            handleBreakFinished();
        }
    } else {
        saveStateToStorage();
    }
}

function startTicker() {
    stopTicker();
    tick();
    ticker = setInterval(tick, 1000);
}

function stopTicker() {
    if (ticker) {
        clearInterval(ticker);
        ticker = null;
    }
}

// ── Finalización de Foco y Transición a Descanso ────────────────────────────
async function handleFocusFinished() {
    stopTicker();
    const currentSess = session.value;
    const minutes = currentSess?.planned_minutes || 25;

    playFocusEndMelody();
    sendNotification(
        '🍅 ¡Bloque Pomodoro Finalizado!',
        `¡Excelente trabajo! Completaste ${minutes} min de enfoque. Es hora de tu descanso.`
    );

    completedSessionInfo.value = {
        minutes,
        sessionId: currentSess?.id,
    };
    hasCompletedFocusModal.value = true;

    // Completar en el backend
    if (currentSess?.id) {
        try {
            await fetch(route('pomodoro.complete', { id: currentSess.id }), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfHeader(),
                },
            });
        } catch {
            // Silencioso
        }
    }

    emitSync(SYNC_EVENTS.POMODORO_COMPLETED, { sessionId: currentSess?.id, minutes });
    reloadInertiaProps(['activePomodoro', 'auth', 'flash', 'stats', 'todaySessions']);

    // Pasar automáticamente a descanso corto (o largo según ciclo)
    startBreak(5, false);
}

function handleBreakFinished() {
    stopTicker();
    mode.value = 'idle';
    targetEndMs.value = null;
    session.value = null;
    breakJustFinished.value = true;
    remainingSeconds.value = 0;
    clearStorage();

    playBreakEndChime();
    sendNotification(
        '☕ ¡Descanso Finalizado!',
        'Tu tiempo de recarga ha terminado. ¡Listo para tu próximo bloque de enfoque!'
    );

    emitSync(SYNC_EVENTS.BREAK_COMPLETED);

    if (typeof window !== 'undefined') {
        window.dispatchEvent(
            new CustomEvent('epycus-toast', {
                detail: {
                    message: '☕ ¡Descanso finalizado! Puedes iniciar el siguiente bloque cuando estés listo.',
                    type: 'info',
                },
            }),
        );
    }
}

// ── Acciones de Usuario ─────────────────────────────────────────────────────

export async function startFocus(plannedMinutes, missionId = null, missionTitle = null, studyGroupSessionId = null, contextType = null, contextId = null) {
    if (busy.value) return;
    busy.value = true;
    try {
        const res = await fetch(route('pomodoro.start'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
            body: JSON.stringify({
                planned_minutes: plannedMinutes,
                mission_id: missionId,
                study_group_session_id: studyGroupSessionId,
                context_type: contextType,
                context_id: contextId,
            }),
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'No se pudo iniciar el Pomodoro');
        }

        const data = await res.json();
        session.value = {
            ...data,
            mission_title: missionTitle || data.mission_title,
        };
        mode.value = 'focus';
        isPaused.value = false;
        pausedAtMs.value = null;
        totalSeconds.value = plannedMinutes * 60;
        remainingSeconds.value = plannedMinutes * 60;
        targetEndMs.value = Date.now() + plannedMinutes * 60 * 1000;
        breakJustFinished.value = false;
        hasCompletedFocusModal.value = false;

        saveStateToStorage();
        startTicker();
        requestNotificationPermission();

        emitSync(SYNC_EVENTS.POMODORO_STARTED, { session: session.value });
        reloadInertiaProps(['activePomodoro']);

        return data;
    } finally {
        busy.value = false;
    }
}

export async function pause() {
    if (!session.value?.id || isPaused.value || busy.value) return;
    busy.value = true;
    try {
        const res = await fetch(route('pomodoro.pause', { id: session.value.id }), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
        });

        if (res.ok) {
            isPaused.value = true;
            pausedAtMs.value = Date.now();
            saveStateToStorage();
            emitSync(SYNC_EVENTS.POMODORO_PAUSED);
        }
    } finally {
        busy.value = false;
    }
}

export async function resume() {
    if (!session.value?.id || !isPaused.value || busy.value) return;
    busy.value = true;
    try {
        const res = await fetch(route('pomodoro.resume', { id: session.value.id }), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfHeader(),
            },
        });

        if (res.ok) {
            const pauseDurationMs = pausedAtMs.value ? Date.now() - pausedAtMs.value : 0;
            if (targetEndMs.value) {
                targetEndMs.value += pauseDurationMs;
            }
            isPaused.value = false;
            pausedAtMs.value = null;
            saveStateToStorage();
            startTicker();
            emitSync(SYNC_EVENTS.POMODORO_RESUMED);
        }
    } finally {
        busy.value = false;
    }
}

export async function abandon() {
    if (!session.value?.id && mode.value !== 'focus') {
        skipBreak();
        return;
    }
    busy.value = true;
    try {
        if (session.value?.id) {
            await fetch(route('pomodoro.abandon', { id: session.value.id }), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': csrfHeader(),
                },
            });
        }
    } catch {
        // Silencioso
    } finally {
        mode.value = 'idle';
        session.value = null;
        targetEndMs.value = null;
        remainingSeconds.value = 0;
        isPaused.value = false;
        pausedAtMs.value = null;
        stopTicker();
        clearStorage();
        busy.value = false;
        emitSync(SYNC_EVENTS.POMODORO_ABANDONED);
        reloadInertiaProps(['activePomodoro', 'stats', 'todaySessions']);
    }
}

export function startBreak(minutes = 5, isLong = false) {
    stopTicker();
    mode.value = 'break';
    isPaused.value = false;
    pausedAtMs.value = null;
    breakInfo.value = {
        durationMinutes: minutes,
        isLong,
    };
    totalSeconds.value = minutes * 60;
    remainingSeconds.value = minutes * 60;
    targetEndMs.value = Date.now() + minutes * 60 * 1000;
    breakJustFinished.value = false;

    saveStateToStorage();
    startTicker();
    emitSync(SYNC_EVENTS.BREAK_STARTED, { minutes, isLong });
}

export function skipBreak() {
    stopTicker();
    mode.value = 'idle';
    session.value = null;
    targetEndMs.value = null;
    remainingSeconds.value = 0;
    isPaused.value = false;
    pausedAtMs.value = null;
    breakJustFinished.value = false;
    clearStorage();
    emitSync(SYNC_EVENTS.BREAK_COMPLETED);
}

// ── Sincronización Inteligente con Props de Inertia ─────────────────────────
export function syncWithInertiaProps(activePomodoroProp) {
    if (!isInitialized) {
        isInitialized = true;
        const saved = loadStateFromStorage();
        if (saved && saved.mode === 'break' && saved.targetEndMs && saved.targetEndMs > Date.now()) {
            // Restaurar descanso activo
            mode.value = 'break';
            breakInfo.value = saved.breakInfo || { durationMinutes: 5, isLong: false };
            totalSeconds.value = saved.totalSeconds || 300;
            targetEndMs.value = saved.targetEndMs;
            startTicker();
            return;
        }
    }

    if (activePomodoroProp) {
        const serverSess = activePomodoroProp;
        const isCurrentSession = session.value?.id === serverSess.id;

        // Si ya tenemos este foco corriendo con un targetEndMs válido, NO resetear bruscamente el tiempo
        if (isCurrentSession && mode.value === 'focus' && targetEndMs.value) {
            isPaused.value = serverSess.status === 'paused';
            session.value = {
                ...session.value,
                ...serverSess,
            };
            return;
        }

        // Si es una nueva sesión o primer inicio
        session.value = serverSess;
        mode.value = 'focus';
        isPaused.value = serverSess.status === 'paused';
        totalSeconds.value = (serverSess.planned_minutes || 25) * 60;

        // Calcular remaining basado en tiempo transcurrido seguro
        const startedMs = new Date(serverSess.started_at).getTime();
        const nowMs = Date.now();
        const rawElapsed = Math.max(0, Math.floor((nowMs - startedMs) / 1000) - (serverSess.total_paused_seconds || 0));
        const rem = Math.max(0, totalSeconds.value - rawElapsed);

        remainingSeconds.value = rem;
        targetEndMs.value = nowMs + rem * 1000;

        if (serverSess.status === 'running') {
            startTicker();
        } else {
            stopTicker();
        }
        saveStateToStorage();
    } else {
        // El servidor no tiene sesión de foco activa
        if (mode.value === 'focus') {
            // La sesión fue cerrada externamente o ya terminó
            mode.value = 'idle';
            session.value = null;
            targetEndMs.value = null;
            remainingSeconds.value = 0;
            stopTicker();
            clearStorage();
        }
        // Si estábamos en modo descanso ('break'), se mantiene el descanso corriendo
    }
}

// ── Getters y Formateadores ─────────────────────────────────────────────────
const formattedTime = computed(() => {
    const s = Math.max(0, remainingSeconds.value);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
});

const progressPercent = computed(() => {
    if (totalSeconds.value <= 0) return 0;
    const elapsed = totalSeconds.value - remainingSeconds.value;
    return Math.min(100, Math.max(0, Math.round((elapsed / totalSeconds.value) * 100)));
});

export function usePomodoroState() {
    return {
        // Estado
        mode,
        session,
        isPaused,
        busy,
        remainingSeconds,
        totalSeconds,
        formattedTime,
        progressPercent,
        breakInfo,
        hasCompletedFocusModal,
        completedSessionInfo,
        breakJustFinished,
        isWidgetHidden,

        // Acciones
        startFocus,
        pause,
        resume,
        completeFocus: handleFocusFinished,
        startBreak,
        skipBreak,
        abandon,
        syncWithInertiaProps,
        requestNotificationPermission,
    };
}
