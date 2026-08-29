import { onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export const SYNC_EVENTS = {
    POMODORO_STARTED: 'epycus:pomodoro-started',
    POMODORO_PAUSED: 'epycus:pomodoro-paused',
    POMODORO_RESUMED: 'epycus:pomodoro-resumed',
    POMODORO_COMPLETED: 'epycus:pomodoro-completed',
    POMODORO_ABANDONED: 'epycus:pomodoro-abandoned',
    BREAK_STARTED: 'epycus:break-started',
    BREAK_COMPLETED: 'epycus:break-completed',
    HABIT_TOGGLED: 'epycus:habit-toggled',
    MISSION_UPDATED: 'epycus:mission-updated',
    PLAN_UPDATED: 'epycus:plan-updated',
};

/**
 * Emite un evento global en la ventana para sincronizar componentes en tiempo real.
 */
export function emitSync(eventName, detail = {}) {
    if (typeof window === 'undefined') return;
    window.dispatchEvent(
        new CustomEvent(eventName, {
            detail: {
                ...detail,
                timestamp: Date.now(),
            },
        }),
    );
}

/**
 * Escucha un evento de sincronización y limpia automáticamente el listener
 * cuando el componente que lo invocó se desmonta.
 */
export function onSync(eventName, callback) {
    if (typeof window === 'undefined') return () => {};

    const handler = (event) => {
        callback(event.detail);
    };

    window.addEventListener(eventName, handler);

    try {
        onUnmounted(() => {
            window.removeEventListener(eventName, handler);
        });
    } catch {
        // En caso de llamarse fuera del ciclo setup de un componente
    }

    return () => {
        window.removeEventListener(eventName, handler);
    };
}

/**
 * Recarga props específicas de Inertia en segundo plano sin parpadeo ni pérdida de scroll.
 */
export function reloadInertiaProps(onlyProps = [], onFinish = null) {
    const options = {
        preserveScroll: true,
        preserveState: true,
    };
    if (Array.isArray(onlyProps) && onlyProps.length > 0) {
        options.only = onlyProps;
    }
    if (typeof onFinish === 'function') {
        options.onFinish = onFinish;
    }
    router.reload(options);
}

export function useAppSync() {
    return {
        SYNC_EVENTS,
        emitSync,
        onSync,
        reloadInertiaProps,
    };
}
