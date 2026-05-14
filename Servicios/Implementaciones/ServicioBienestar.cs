using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioBienestar : IServicioBienestar
    {
        private readonly ContextoAplicacion _contexto;

        public ServicioBienestar(ContextoAplicacion contexto)
        {
            _contexto = contexto;
        }

        public async Task<List<AlertaBienestar>> ObtenerAlertasActivas(int usuarioId)
        {
            var alertas = new List<AlertaBienestar>();

            var alertaPomodoro = await VerificarUsoExcesivoPomodoro(usuarioId);
            if (alertaPomodoro != null)
            {
                alertas.Add(alertaPomodoro);
            }

            var alertaAnimo = await VerificarAnimoNegativoConsecutivo(usuarioId);
            if (alertaAnimo != null)
            {
                alertas.Add(alertaAnimo);
            }

            var alertaSueno = await VerificarHabitoSueno(usuarioId);
            if (alertaSueno != null)
            {
                alertas.Add(alertaSueno);
            }

            var alertaMisiones = await VerificarSobrecargaMisiones(usuarioId);
            if (alertaMisiones != null)
            {
                alertas.Add(alertaMisiones);
            }

            return alertas;
        }

        public async Task<AlertaBienestar?> VerificarUsoExcesivoPomodoro(int usuarioId)
        {
            var hoy = DateTime.Today;
            var ciclosHoy = await _contexto.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId
                         && s.FechaInicio.Date == hoy
                         && s.FueCompletada)
                .SumAsync(s => s.CiclosCompletados);

            if (ciclosHoy > 8)
            {
                return new AlertaBienestar
                {
                    Tipo = "Sobrecarga",
                    Mensaje = "Llevas más de 8 ciclos Pomodoro hoy. Es momento de una pausa real. Tu cerebro lo necesita.",
                    Icono = "bi-exclamation-triangle",
                    EsCritica = true
                };
            }

            return null;
        }

        public async Task<AlertaBienestar?> VerificarAnimoNegativoConsecutivo(int usuarioId)
        {
            var ultimosTresEstados = await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .Take(3)
                .Select(e => e.Estado)
                .ToListAsync();

            bool tresNegativosConsecutivos = ultimosTresEstados.Count == 3
                && ultimosTresEstados.All(e => e == "Cansado" || e == "Estresado");

            if (tresNegativosConsecutivos)
            {
                return new AlertaBienestar
                {
                    Tipo = "Estres",
                    Mensaje = "Has registrado estrés o cansancio 3 días seguidos. Considera reducir la carga de hábitos por hoy y priorizar el descanso.",
                    Icono = "bi-heart-pulse",
                    EsCritica = true
                };
            }

            return null;
        }

        private async Task<AlertaBienestar?> VerificarHabitoSueno(int usuarioId)
        {
            var hace3dias = DateOnly.FromDateTime(DateTime.Today.AddDays(-3));

            var tieneHabitoSueno = await _contexto.Habitos
                .AnyAsync(h => h.UsuarioId == usuarioId
                            && h.EstaActivo
                            && h.Categoria.Nombre == "Sueño");

            if (!tieneHabitoSueno)
            {
                return null;
            }

            var suenoCumplido = await _contexto.RegistrosHabito
                .AnyAsync(r => r.Habito.UsuarioId == usuarioId
                            && r.Habito.Categoria.Nombre == "Sueño"
                            && r.Fecha >= hace3dias
                            && r.Estado == "Completado");

            if (!suenoCumplido)
            {
                return new AlertaBienestar
                {
                    Tipo = "Descanso",
                    Mensaje = "No has registrado tu hábito de sueño en 3 días. El descanso es fundamental para tu rendimiento académico.",
                    Icono = "bi-moon-stars",
                    EsCritica = false
                };
            }

            return null;
        }

        private async Task<AlertaBienestar?> VerificarSobrecargaMisiones(int usuarioId)
        {
            var en2dias = DateOnly.FromDateTime(DateTime.Today.AddDays(2));
            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var misionesUrgentes = await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId
                              && m.FechaLimite >= hoy
                              && m.FechaLimite <= en2dias
                              && (m.Estado == "Pendiente" || m.Estado == "EnProgreso"));

            if (misionesUrgentes >= 3)
            {
                return new AlertaBienestar
                {
                    Tipo = "Sobrecarga",
                    Mensaje = $"Tienes {misionesUrgentes} misiones que vencen en los próximos 2 días. Prioriza y divide el trabajo en sesiones Pomodoro.",
                    Icono = "bi-lightning",
                    EsCritica = true
                };
            }

            return null;
        }

        public string? RecomendacionPausaActiva(int ciclosCompletados)
        {
            return ciclosCompletados switch
            {
                2 => "Estira los dedos y mueve las muñecas. 30 segundos.",
                4 => "Párate, camina y mira por la ventana. 5 minutos.",
                6 => "Come algo ligero y toma agua. Tu cerebro necesita glucosa.",
                _ => null
            };
        }
    }
}
