/*
 * Composable global de Telemetría (Fase 2).
 * Conforme a docs/02-TELEMETRIA.md:
 * 1. Acumula eventos en buffer en memoria.
 * 2. Envía lotes automáticamente cada 30 segundos, cuando alcanza 20 eventos o al cerrar la pestaña.
 * 3. La telemetría nunca interrumpe la experiencia del usuario (errores atrapados y logueados en silencio).
 */

const BUFFER_MAX_SIZE = 20;
const FLUSH_INTERVAL_MS = 30000;

const buffer = [];
let flushTimer = null;
let sessionUuid = null;

function getSessionUuid() {
    if (!sessionUuid) {
        sessionUuid = crypto.randomUUID ? crypto.randomUUID() : 'session-' + Date.now();
    }
    return sessionUuid;
}

export function useTelemetry() {
    /**
     * Registra un evento de interacción en el buffer de telemetría.
     *
     * @param {string} eventName Nombre del evento (ej: 'view_dashboard', 'start_pomodoro')
     * @param {string} eventCategory Categoría (ej: 'navigation', 'habits', 'pomodoro')
     * @param {Object|null} payload Datos adicionales del evento (sin PII)
     */
    const track = (eventName, eventCategory, payload = null) => {
        try {
            buffer.push({
                event_name: eventName,
                event_category: eventCategory,
                payload: payload,
                session_uuid: getSessionUuid(),
                occurred_at: new Date().toISOString(),
            });

            if (buffer.length >= BUFFER_MAX_SIZE) {
                flush();
            } else if (!flushTimer) {
                flushTimer = setTimeout(flush, FLUSH_INTERVAL_MS);
            }
        } catch {
            // Silencioso para no romper UX
        }
    };

    /**
     * Envía el lote acumulado al endpoint POST /api/v1/telemetry/batch.
     */
    function getCsrfToken() {
        if (typeof document === 'undefined') return '';
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        if (match) {
            return decodeURIComponent(match[1]);
        }
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    const flush = async () => {
        if (flushTimer) {
            clearTimeout(flushTimer);
            flushTimer = null;
        }

        if (buffer.length === 0) return;

        const eventsToSend = buffer.splice(0, buffer.length);

        try {
            const token = getCsrfToken();

            await fetch('/api/v1/telemetry/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-XSRF-TOKEN': token,
                },
                body: JSON.stringify({ events: eventsToSend }),
            });
        } catch {
            // Re-inserta eventos fallidos si es posible sin exceder límite
            if (buffer.length + eventsToSend.length <= BUFFER_MAX_SIZE * 2) {
                buffer.unshift(...eventsToSend);
            }
        }
    };

    /**
     * Envía el buffer con sendBeacon — a diferencia de fetch(), sobrevive al
     * cierre/descarga de la pestaña (docs/02-TELEMETRIA.md §6: "esencial", y
     * compuerta §10: "sendBeacon funciona al cerrar la pestaña de golpe").
     * Un fetch() disparado en beforeunload puede cancelarse a medio camino
     * cuando el documento se descarga; sendBeacon está diseñado para eso.
     * Sin cabecera CSRF a propósito: `api/v1/telemetry/batch` ya está
     * excluido en bootstrap/app.php, y sendBeacon no permite headers
     * personalizados de todas formas.
     */
    const flushWithBeacon = () => {
        if (flushTimer) {
            clearTimeout(flushTimer);
            flushTimer = null;
        }

        if (buffer.length === 0) return;

        const eventsToSend = buffer.splice(0, buffer.length);
        const blob = new Blob([JSON.stringify({ events: eventsToSend })], {
            type: 'application/json',
        });
        const accepted = navigator.sendBeacon('/api/v1/telemetry/batch', blob);

        if (!accepted) {
            buffer.unshift(...eventsToSend);
        }
    };

    // beforeunload no es fiable en Safari móvil (no siempre se dispara al
    // cambiar de app) — visibilitychange + pagehide cubren ese caso, igual
    // que pide la sección 6 del documento de telemetría.
    if (typeof window !== 'undefined') {
        window.addEventListener('beforeunload', flushWithBeacon);
        window.addEventListener('pagehide', flushWithBeacon);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') flushWithBeacon();
        });
    }

    return {
        track,
        flush,
    };
}
