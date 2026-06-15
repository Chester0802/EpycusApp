using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioBienestar : IServicioBienestar
    {
        private readonly ContextoAplicacion _contexto;
        private readonly ILogger<ServicioBienestar> _logger;

        public ServicioBienestar(ContextoAplicacion contexto, ILogger<ServicioBienestar> logger)
        {
            _contexto = contexto;
            _logger = logger;
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
                    Mensaje = "Llevas mÃ¡s de 8 ciclos Pomodoro hoy. Es momento de una pausa real. Tu cerebro lo necesita.",
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
                    Mensaje = "Has registrado estrÃ©s o cansancio 3 dÃ­as seguidos. Considera reducir la carga de hÃ¡bitos por hoy y priorizar el descanso.",
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
                            && h.Categoria.Nombre == "SueÃ±o");

            if (!tieneHabitoSueno)
            {
                return null;
            }

            var suenoCumplido = await _contexto.RegistrosHabito
                .AnyAsync(r => r.Habito.UsuarioId == usuarioId
                            && r.Habito.Categoria.Nombre == "SueÃ±o"
                            && r.Fecha >= hace3dias
                            && r.Estado == "Completado");

            if (!suenoCumplido)
            {
                return new AlertaBienestar
                {
                    Tipo = "Descanso",
                    Mensaje = "No has registrado tu hÃ¡bito de sueÃ±o en 3 dÃ­as. El descanso es fundamental para tu rendimiento acadÃ©mico.",
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
                    Mensaje = $"Tienes {misionesUrgentes} misiones que vencen en los prÃ³ximos 2 dÃ­as. Prioriza y divide el trabajo en sesiones Pomodoro.",
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
                2 => "Estira los dedos y mueve las muÃ±ecas. 30 segundos.",
                4 => "PÃ¡rate, camina y mira por la ventana. 5 minutos.",
                6 => "Come algo ligero y toma agua. Tu cerebro necesita glucosa.",
                _ => null
            };
        }

        public async Task<FraseMotivacional?> ObtenerFraseMotivacionalAleatoria()
        {
            var frasesActivas = await _contexto.FrasesMotivacionales
                .Where(f => f.EstaActiva)
                .ToListAsync();

            if (!frasesActivas.Any())
                return null;

            // Seleccionar una frase aleatoria
            var random = new Random();
            var indiceAleatorio = random.Next(frasesActivas.Count);
            return frasesActivas[indiceAleatorio];
        }

        // Devuelve el estado de Ã¡nimo del usuario para hoy (si existe)
        public async Task<EstadoAnimo?> ObtenerEstadoHoy(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);
            return await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId && e.Fecha >= hoy)
                .OrderByDescending(e => e.Fecha)
                .FirstOrDefaultAsync();
        }

        // Devuelve el historial de estados de Ã¡nimo de los Ãºltimos `dias` dÃ­as
        public async Task<List<EstadoAnimo>> ObtenerHistorialAnimo(int usuarioId, int dias)
        {
            var inicio = DateOnly.FromDateTime(DateTime.Today.AddDays(-dias + 1));
            return await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId && e.Fecha >= inicio)
                .OrderByDescending(e => e.Fecha)
                .ToListAsync();
        }

        // Registra un estado de Ã¡nimo para el usuario
        public async Task<AlertaBienestar?> RegistrarEstadoAnimo(int usuarioId, string estado, string? nota)
        {
            _contexto.EstadosAnimo.Add(new EstadoAnimo
            {
                UsuarioId = usuarioId,
                Estado = estado,
                Nota = nota,
                Fecha = DateOnly.FromDateTime(DateTime.Today),
                FechaRegistro = DateTime.UtcNow
            });

            await _contexto.SaveChangesAsync();
            return await VerificarAnimoNegativoConsecutivo(usuarioId);
        }

        public async Task<List<EstadoAnimo>> ObtenerHistorialAnimoCompletoAsync(int usuarioId)
        {
            return await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .ToListAsync();
        }

        public async Task<int> ObtenerHabitosPendientesAsync(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);
            return await _contexto.Habitos
                .Include(h => h.Registros)
                .Where(h => h.UsuarioId == usuarioId && h.EstaActivo)
                .CountAsync(h => !h.Registros.Any(r => r.Fecha == hoy && r.Estado == "Completado"));
        }

        public async Task<int> ObtenerMisionesPendientesAsync(int usuarioId)
        {
            return await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId && (m.Estado == "Pendiente" || m.Estado == "EnProgreso"));
        }
    }
}
