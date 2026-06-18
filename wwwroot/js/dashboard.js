// Script del dashboard principal - Gestión de hábitos y gráficos
(function() {
    'use strict';

    // Función para completar el hábito desde el Dashboard
    window.completarHabitoDashboard = async function(id, btn) {
        btn.disabled = true;
        const originalText = btn.innerText;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const res = await fetch(`/api/apihabitos/${id}/completar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin'
            });

            if (!res.ok) {
                const json = await res.json().catch(() => null);
                if (typeof Notificaciones !== 'undefined') {
                    Notificaciones.mostrarError(json?.mensaje ?? 'No se pudo completar el hábito');
                } else {
                    alert(json?.mensaje ?? 'No se pudo completar el hábito');
                }
                btn.innerText = originalText;
                return;
            }

            const data = await res.json();

            // Mostrar éxito visual
            btn.className = 'btn btn-sm btn-success rounded-pill px-3';
            btn.innerHTML = '<i class="bi bi-check2"></i> XP +' + data.datos.xpGanado;

            // Actualizar fila racha
            const fila = document.querySelector(`.ep-habit-card[data-habito-id="${id}"]`);
            if (fila) {
                const rachaCell = fila.querySelector('.racha-cell');
                if (rachaCell) {
                    const texto = rachaCell.innerText;
                    const num = parseInt(texto.replace(/[^0-9]/g, '')) || 0;
                    rachaCell.innerHTML = `<i class="bi bi-fire me-1"></i>${num + 1} días`;
                }
            }
        } catch (e) {
            if (typeof Notificaciones !== 'undefined') {
                Notificaciones.mostrarError('Error de red al completar el hábito');
            } else {
                alert('Error de red al completar el hábito');
            }
            btn.innerText = originalText;
        } finally {
            setTimeout(() => { btn.disabled = false; }, 1000);
        }
    };

    // Renderizar gráfico de Chart.js
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('graficoCategorias');
        if (!ctx) return;

        // Ocultar skeleton y mostrar canvas
        var skeleton = document.getElementById('skeletonChart');
        if (skeleton) skeleton.style.display = 'none';
        ctx.style.display = 'block';

        // Obtener datos del atributo data-* del canvas
        var categorias = JSON.parse(ctx.dataset.categorias || '[]');
        var valores = JSON.parse(ctx.dataset.valores || '[]');

        if (categorias.length === 0 || valores.length === 0) {
            console.warn('No hay datos para el gráfico');
            return;
        }

        var esModoOscuro = localStorage.getItem('epycus_theme') !== 'claro';
        var colorTexto = esModoOscuro ? '#E2E8F0' : '#1E293B';

        // Colores vibrantes y modernos
        var coloresBg = [
            'rgba(59, 130, 246, 0.8)',   // Azul
            'rgba(16, 185, 129, 0.8)',   // Verde
            'rgba(245, 158, 11, 0.8)',   // Naranja
            'rgba(167, 139, 250, 0.8)',  // Púrpura
            'rgba(236, 72, 153, 0.8)',   // Rosa
            'rgba(14, 165, 233, 0.8)'    // Cyan
        ];

        var myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categorias,
                datasets: [{
                    data: valores,
                    backgroundColor: coloresBg,
                    borderWidth: 2,
                    borderColor: esModoOscuro ? '#1E293B' : '#FFFFFF',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: colorTexto,
                            padding: 20,
                            font: {
                                family: "'Inter', sans-serif",
                                size: 13
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: esModoOscuro ? 'rgba(15, 23, 42, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                        titleColor: esModoOscuro ? '#fff' : '#000',
                        bodyColor: esModoOscuro ? '#CBD5E1' : '#334155',
                        padding: 12,
                        borderColor: 'rgba(128, 128, 128, 0.2)',
                        borderWidth: 1
                    }
                },
                cutout: '70%'
            }
        });

        // Re-render en cambio de tema
        window.addEventListener('themeChanged', function(e) {
            var isDark = e.detail.theme === 'oscuro';
            myChart.options.plugins.legend.labels.color = isDark ? '#E2E8F0' : '#1E293B';
            myChart.data.datasets[0].borderColor = isDark ? '#1E293B' : '#FFFFFF';
            myChart.options.plugins.tooltip.backgroundColor = isDark ? 'rgba(15, 23, 42, 0.9)' : 'rgba(255, 255, 255, 0.9)';
            myChart.options.plugins.tooltip.titleColor = isDark ? '#fff' : '#000';
            myChart.options.plugins.tooltip.bodyColor = isDark ? '#CBD5E1' : '#334155';
            myChart.update();
        });
    });
})();
