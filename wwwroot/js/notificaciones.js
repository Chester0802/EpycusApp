/**
 * Sistema de notificaciones toast para EPYCUS
 * Reemplaza los alert() nativos con notificaciones estilizadas
 */

const Notificaciones = {
    /**
     * Muestra una notificación de éxito
     * @param {string} mensaje - Mensaje a mostrar
     */
    mostrarExito: function(mensaje) {
        this._mostrarToast(mensaje, 'exito');
    },

    /**
     * Muestra una notificación de error
     * @param {string} mensaje - Mensaje a mostrar
     */
    mostrarError: function(mensaje) {
        this._mostrarToast(mensaje, 'error');
    },

    /**
     * Muestra una notificación de información
     * @param {string} mensaje - Mensaje a mostrar
     */
    mostrarInfo: function(mensaje) {
        this._mostrarToast(mensaje, 'info');
    },

    /**
     * Muestra una notificación de advertencia
     * @param {string} mensaje - Mensaje a mostrar
     */
    mostrarAdvertencia: function(mensaje) {
        this._mostrarToast(mensaje, 'advertencia');
    },

    /**
     * Método interno para crear y mostrar el toast
     * @private
     */
    _mostrarToast: function(mensaje, tipo) {
        const contenedor = this._obtenerContenedor();
        const toast = this._crearToast(mensaje, tipo);

        contenedor.appendChild(toast);

        // Trigger animation
        setTimeout(() => toast.classList.add('ep-toast-show'), 10);

        // Auto remove after 4 seconds
        setTimeout(() => {
            toast.classList.remove('ep-toast-show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    /**
     * Obtiene o crea el contenedor de toasts
     * @private
     */
    _obtenerContenedor: function() {
        let contenedor = document.getElementById('ep-toast-container');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'ep-toast-container';
            contenedor.className = 'ep-toast-container';
            document.body.appendChild(contenedor);
        }
        return contenedor;
    },

    /**
     * Crea el elemento toast con el estilo apropiado
     * @private
     */
    _crearToast: function(mensaje, tipo) {
        const toast = document.createElement('div');
        toast.className = `ep-toast ep-toast-${tipo}`;

        const icono = this._obtenerIcono(tipo);

        toast.innerHTML = `
            <i class="bi ${icono} me-2"></i>
            <span>${mensaje}</span>
            <button type="button" class="ep-toast-close" aria-label="Cerrar notificación">
                <i class="bi bi-x"></i>
            </button>
        `;

        // Add close button functionality
        toast.querySelector('.ep-toast-close').addEventListener('click', () => {
            toast.classList.remove('ep-toast-show');
            setTimeout(() => toast.remove(), 300);
        });

        return toast;
    },

    /**
     * Obtiene el icono Bootstrap apropiado según el tipo
     * @private
     */
    _obtenerIcono: function(tipo) {
        const iconos = {
            'exito': 'bi-check-circle-fill',
            'error': 'bi-x-circle-fill',
            'info': 'bi-info-circle-fill',
            'advertencia': 'bi-exclamation-triangle-fill'
        };
        return iconos[tipo] || 'bi-info-circle-fill';
    }
};

// Hacer disponible globalmente
window.Notificaciones = Notificaciones;

/**
 * Sistema de sonidos y vibración háptica gamificada
 */
const EpycusSonidos = {
    _audioCtx: null,

    _obtenerContexto: function() {
        if (!this._audioCtx) {
            this._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        return this._audioCtx;
    },

    _reproducirTono: function(frecuencia, duracion, tipoOnda, volumen) {
        try {
            var ctx = this._obtenerContexto();
            if (ctx.state === 'suspended') ctx.resume();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = tipoOnda || 'sine';
            osc.frequency.value = frecuencia;
            gain.gain.setValueAtTime(volumen || 0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duracion);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + duracion);
        } catch(e) {}
    },

    completarHabito: function() {
        this._reproducirTono(523, 0.1, 'sine', 0.12);
        this._reproducirTono(659, 0.1, 'sine', 0.12);
        this._reproducirTono(784, 0.15, 'sine', 0.12);
        this.vibrar([30, 50, 30]);
    },

    subirNivel: function() {
        var notas = [523, 659, 784, 1047];
        for (var i = 0; i < notas.length; i++) {
            (function(n, d) {
                setTimeout(function() { EpycusSonidos._reproducirTono(n, 0.2, 'sine', 0.15); }, d);
            })(notas[i], i * 100);
        }
        this.vibrar([50, 80, 50, 80, 100]);
    },

    ganarLogro: function() {
        this._reproducirTono(784, 0.15, 'triangle', 0.1);
        this._reproducirTono(988, 0.15, 'triangle', 0.1);
        this._reproducirTono(1175, 0.3, 'triangle', 0.1);
        this.vibrar([40, 60, 40, 60]);
    },

    recibirXP: function() {
        this._reproducirTono(440, 0.08, 'sine', 0.08);
        this._reproducirTono(554, 0.08, 'sine', 0.08);
        this.vibrar([20, 30]);
    },

    error: function() {
        this._reproducirTono(200, 0.3, 'sawtooth', 0.08);
        this.vibrar([100]);
    },

    vibrar: function(patron) {
        if (navigator.vibrate) {
            try { navigator.vibrate(patron); } catch(e) {}
        }
    }
};

window.EpycusSonidos = EpycusSonidos;

// Extender Notificaciones para incluir sonidos
var _originalMostrar = Notificaciones._mostrarToast;
Notificaciones._mostrarToast = function(mensaje, tipo) {
    if (tipo === 'exito' && EpycusSonidos) EpycusSonidos.recibirXP();
    if (tipo === 'error' && EpycusSonidos) EpycusSonidos.error();
    _originalMostrar.call(this, mensaje, tipo);
};
