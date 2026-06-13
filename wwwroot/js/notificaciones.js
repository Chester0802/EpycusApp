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
