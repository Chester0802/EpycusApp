// Gestor de temas - Maneja el cambio entre tema claro y oscuro
(function() {
    'use strict';

    // Cargar el tema guardado antes de que la página renderice para evitar FOUC (Flash of Unstyled Content)
    var savedTheme = localStorage.getItem('epycus_theme') || 'oscuro';
    var themeLink = document.getElementById('hoja-tema');

    if (themeLink) {
        if (savedTheme === 'claro') {
            themeLink.href = '/css/temas/tema-sakura.css';
        } else {
            themeLink.href = '/css/temas/tema-noche-epica.css';
        }
    }

    // Inicializar el toggle de tema cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        var themeSwitch = document.getElementById('switch-tema');
        var btnTema = document.getElementById('btn-toggle-tema');
        var themeLink = document.getElementById('hoja-tema');

        if (!themeSwitch || !btnTema || !themeLink) {
            return; // Elementos no encontrados, salir
        }

        var savedTheme = localStorage.getItem('epycus_theme') || 'oscuro';

        // Sincronizar el UI inicial
        if (savedTheme === 'oscuro') {
            themeSwitch.checked = true;
            btnTema.querySelector('span').innerHTML = '<i class="bi bi-moon-stars"></i> Modo Oscuro';
        } else {
            themeSwitch.checked = false;
            btnTema.querySelector('span').innerHTML = '<i class="bi bi-sun"></i> Modo Claro';
        }

        // Event listeners
        themeSwitch.addEventListener('change', toggleTheme);
        btnTema.addEventListener('click', function(e) {
            if(e.target !== themeSwitch) {
                themeSwitch.checked = !themeSwitch.checked;
                toggleTheme();
            }
        });

        function toggleTheme() {
            if (themeSwitch.checked) {
                localStorage.setItem('epycus_theme', 'oscuro');
                themeLink.href = '/css/temas/tema-noche-epica.css';
                btnTema.querySelector('span').innerHTML = '<i class="bi bi-moon-stars"></i> Modo Oscuro';
                // Disparar evento personalizado para otros componentes
                window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: 'oscuro' } }));
            } else {
                localStorage.setItem('epycus_theme', 'claro');
                themeLink.href = '/css/temas/tema-sakura.css';
                btnTema.querySelector('span').innerHTML = '<i class="bi bi-sun"></i> Modo Claro';
                // Disparar evento personalizado para otros componentes
                window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: 'claro' } }));
            }
        }
    });
})();
