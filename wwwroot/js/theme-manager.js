// Gestor de temas - Maneja el cambio entre tema claro y oscuro
(function() {
    'use strict';

    function aplicarTema(tema) {
        var esOscuro = tema === 'oscuro';
        var themeLink = document.getElementById('hoja-tema');
        if (themeLink) {
            themeLink.href = esOscuro
                ? '/css/temas/tema-noche-epica.css'
                : '/css/temas/tema-sakura.css';
        }
        document.documentElement.setAttribute('data-theme', esOscuro ? 'dark' : 'light');
    }

    // Cargar el tema guardado antes de que la página renderice para evitar FOUC
    var savedTheme = localStorage.getItem('epycus_theme') || 'oscuro';
    aplicarTema(savedTheme);

    // Inicializar el toggle de tema cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        var themeSwitch = document.getElementById('switch-tema');
        var btnTema = document.getElementById('btn-toggle-tema');
        var themeLink = document.getElementById('hoja-tema');

        if (!themeSwitch || !btnTema || !themeLink) {
            return;
        }

        var savedTheme = localStorage.getItem('epycus_theme') || 'oscuro';

        if (savedTheme === 'oscuro') {
            themeSwitch.checked = true;
            btnTema.querySelector('span').innerHTML = '<i class="bi bi-moon-stars"></i> Modo Oscuro';
        } else {
            themeSwitch.checked = false;
            btnTema.querySelector('span').innerHTML = '<i class="bi bi-sun"></i> Modo Claro';
        }

        themeSwitch.addEventListener('change', toggleTheme);
        btnTema.addEventListener('click', function(e) {
            if(e.target !== themeSwitch) {
                themeSwitch.checked = !themeSwitch.checked;
                toggleTheme();
            }
        });

        function toggleTheme() {
            var tema = themeSwitch.checked ? 'oscuro' : 'claro';
            localStorage.setItem('epycus_theme', tema);
            aplicarTema(tema);
            btnTema.querySelector('span').innerHTML = tema === 'oscuro'
                ? '<i class="bi bi-moon-stars"></i> Modo Oscuro'
                : '<i class="bi bi-sun"></i> Modo Claro';
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: tema } }));
        }
    });
})();
