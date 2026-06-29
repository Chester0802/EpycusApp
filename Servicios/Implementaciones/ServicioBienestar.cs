using EpycusApp.Datos;
using EpycusApp.DTOs;
using EpycusApp.Hubs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.SignalR;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioBienestar : IServicioBienestar
    {
        private readonly ContextoAplicacion _contexto;
        private readonly IHubContext<NotificacionesHub> _hubContext;
        private readonly ILogger<ServicioBienestar> _logger;

        public ServicioBienestar(ContextoAplicacion contexto, IHubContext<NotificacionesHub> hubContext, ILogger<ServicioBienestar> logger)
        {
            _contexto = contexto;
            _hubContext = hubContext;
            _logger = logger;
        }

        public async Task<List<AlertaBienestar>> ObtenerAlertasActivas(int usuarioId)
        {
            var alertas = new List<AlertaBienestar>();

            var alertaPomodoro = await VerificarUsoExcesivoPomodoro(usuarioId);
            if (alertaPomodoro != null)
                alertas.Add(alertaPomodoro);

            var alertaAnimo = await VerificarAnimoNegativoConsecutivo(usuarioId);
            if (alertaAnimo != null)
                alertas.Add(alertaAnimo);

            var alertaSueno = await VerificarHabitoSueno(usuarioId);
            if (alertaSueno != null)
                alertas.Add(alertaSueno);

            var alertaMisiones = await VerificarSobrecargaMisiones(usuarioId);
            if (alertaMisiones != null)
                alertas.Add(alertaMisiones);

            foreach (var alerta in alertas.Where(a => a.EsCritica))
            {
                await EnviarAlertaSignalR(usuarioId, alerta);
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
                return null;

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

        public RecomendacionPausaDto? RecomendacionPausaActiva(int ciclosCompletados)
        {
            return ciclosCompletados switch
            {
                2 => new RecomendacionPausaDto
                {
                    Tipo = "Micro-pausa",
                    DuracionSegundos = 30,
                    Descripcion = "Estira los dedos y mueve las muñecas.",
                    Icono = "bi-hand-index-thumb"
                },
                4 => new RecomendacionPausaDto
                {
                    Tipo = "Pausa activa",
                    DuracionSegundos = 300,
                    Descripcion = "Párate, camina y mira por la ventana.",
                    Icono = "bi-person-walking"
                },
                6 => new RecomendacionPausaDto
                {
                    Tipo = "Recarga",
                    DuracionSegundos = 600,
                    Descripcion = "Come algo ligero y toma agua. Tu cerebro necesita glucosa.",
                    Icono = "bi-cup-straw"
                },
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

            var random = new Random();
            var indiceAleatorio = random.Next(frasesActivas.Count);
            return frasesActivas[indiceAleatorio];
        }

        public async Task<EstadoAnimo?> ObtenerEstadoHoy(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);
            return await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId && e.Fecha >= hoy)
                .OrderByDescending(e => e.Fecha)
                .FirstOrDefaultAsync();
        }

        public async Task<List<EstadoAnimo>> ObtenerHistorialAnimo(int usuarioId, int dias)
        {
            var inicio = DateOnly.FromDateTime(DateTime.Today.AddDays(-dias + 1));
            return await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId && e.Fecha >= inicio)
                .OrderByDescending(e => e.Fecha)
                .ToListAsync();
        }

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
            var alerta = await VerificarAnimoNegativoConsecutivo(usuarioId);
            if (alerta != null)
            {
                await EnviarAlertaSignalR(usuarioId, alerta);
            }
            return alerta;
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
                .Where(h => h.UsuarioId == usuarioId && h.EstaActivo)
                .CountAsync(h => !h.Registros.Any(r => r.Fecha == hoy && r.Estado == "Completado"));
        }

        public async Task<int> ObtenerMisionesPendientesAsync(int usuarioId)
        {
            return await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId && (m.Estado == "Pendiente" || m.Estado == "EnProgreso"));
        }

        private async Task EnviarAlertaSignalR(int usuarioId, AlertaBienestar alerta)
        {
            try
            {
                await _hubContext.Clients.Group($"usuario_{usuarioId}").SendAsync("RecibirAlerta", new
                {
                    alerta.Tipo,
                    alerta.Mensaje,
                    alerta.Icono,
                    alerta.EsCritica,
                    Fecha = DateTime.UtcNow
                });
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Error al enviar alerta SignalR para usuario {UsuarioId}", usuarioId);
            }
        }
    }
}
