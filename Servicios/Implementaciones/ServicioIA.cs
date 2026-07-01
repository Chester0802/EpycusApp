using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Ia;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioIA : IServicioIA
    {
        private const int MaxMensajesHistorial = 20;
        private const int MaxMensajesPorDia = 50;
        private const int XpPorMensaje = 1;

        private readonly ContextoAplicacion _contexto;
        private readonly ConstructorContextoIA _constructorContexto;
        private readonly IProveedorDeepSeek _proveedorDeepSeek;
        private readonly IServicioGamificacion _gamificacion;

        public ServicioIA(
            ContextoAplicacion contexto,
            ConstructorContextoIA constructorContexto,
            IProveedorDeepSeek proveedorDeepSeek,
            IServicioGamificacion gamificacion)
        {
            _contexto = contexto;
            _constructorContexto = constructorContexto;
            _proveedorDeepSeek = proveedorDeepSeek;
            _gamificacion = gamificacion;
        }

        public string NuevaConversacionId() => Guid.NewGuid().ToString();

        public async Task<List<MensajeIA>> ObtenerHistorialAsync(int usuarioId, string conversacionId)
        {
            return await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId)
                .OrderBy(m => m.FechaHora)
                .ToListAsync();
        }

        public async Task<List<ConversacionResumen>> ObtenerConversacionesAsync(int usuarioId)
        {
            var conversaciones = await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId)
                .GroupBy(m => m.ConversacionId)
                .Select(g => new ConversacionResumen
                {
                    ConversacionId = g.Key,
                    UltimoMensaje = g.Max(m => m.FechaHora),
                    CantidadMensajes = g.Count(),
                    Titulo = g.OrderBy(m => m.FechaHora).Select(m => m.Contenido).FirstOrDefault()
                })
                .OrderByDescending(c => c.UltimoMensaje)
                .Take(20)
                .ToListAsync();

            foreach (var c in conversaciones)
            {
                if (!string.IsNullOrEmpty(c.Titulo) && c.Titulo.Length > 60)
                    c.Titulo = c.Titulo[..60] + "...";
            }

            return conversaciones;
        }

        public async Task<List<string>> ObtenerSugerenciasPersonalizadasAsync(int usuarioId)
        {
            var sugerencias = new List<string>();

            var misionesUrgentes = await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId && m.Estado != "Completado"
                    && m.FechaLimite.DayNumber - DateOnly.FromDateTime(DateTime.Today).DayNumber <= 2);
            if (misionesUrgentes > 0)
                sugerencias.Add($"Tengo {misionesUrgentes} mision(es) urgente(s)");

            var habitosHoy = await _contexto.RegistrosHabito
                .CountAsync(r => r.Habito.UsuarioId == usuarioId && r.Fecha == DateOnly.FromDateTime(DateTime.Today));
            if (habitosHoy == 0)
                sugerencias.Add("Recordarme mis habitos de hoy");

            var racha = await _contexto.ProgresosUsuario
                .Where(p => p.UsuarioId == usuarioId)
                .Select(p => p.RachaActual)
                .FirstOrDefaultAsync();
            if (racha > 0)
                sugerencias.Add($"Como mejorar mi racha de {racha} dias");

            sugerencias.Add("Como van mis habitos");
            sugerencias.Add("Dame un consejo de productividad");
            sugerencias.Add("Me siento desmotivado");

            return sugerencias.Distinct().Take(6).ToList();
        }

        public async Task<BienestarContextoIA?> ObtenerBienestarContextoAsync(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);
            var ultimosAnimos = await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .Take(3)
                .Select(e => e.Estado)
                .ToListAsync();

            var animosNegativos = ultimosAnimos.Count(a => a is "Triste" or "Enojado" or "Estrés");

            var pomodoroHoy = await _contexto.SesionesPomodoro
                .CountAsync(s => s.UsuarioId == usuarioId && s.FechaInicio >= DateTime.Today);

            var misionesPendientes = await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId && m.Estado != "Completado");

            var alertas = await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId && m.Rol == "alerta_bienestar"
                    && m.FechaHora >= DateTime.UtcNow.AddDays(-1))
                .AnyAsync();

            return new BienestarContextoIA
            {
                TieneAlertasActivas = alertas || animosNegativos >= 2,
                DiasAnimoNegativo = animosNegativos,
                PomodoroExcesivo = pomodoroHoy > 6,
                SobrecargaMisiones = misionesPendientes > 8,
                UltimoEstadoAnimo = ultimosAnimos.FirstOrDefault()
            };
        }

        public async Task RegistrarFeedbackAsync(int usuarioId, int mensajeId, bool util)
        {
            var mensaje = await _contexto.MensajesIA
                .FirstOrDefaultAsync(m => m.Id == mensajeId && m.UsuarioId == usuarioId);
            if (mensaje != null)
            {
                mensaje.FeedbackRecibido = true;
                mensaje.FeedbackUtil = util;
                await _contexto.SaveChangesAsync();
            }
        }

        public async Task<int> ObtenerMensajesHoyAsync(int usuarioId)
        {
            var hoy = DateTime.UtcNow.Date;
            return await _contexto.MensajesIA
                .CountAsync(m => m.UsuarioId == usuarioId && m.FechaHora >= hoy);
        }

        public async Task<string> ChatAsync(int usuarioId, string mensaje, string conversacionId)
        {
            var primerMensaje = await _contexto.MensajesIA
                .Where(m => m.ConversacionId == conversacionId)
                .Select(m => (int?)m.UsuarioId)
                .FirstOrDefaultAsync();

            if (primerMensaje.HasValue && primerMensaje.Value != usuarioId)
                throw new UnauthorizedAccessException("La conversacion no pertenece al usuario.");

            var mensajesHoy = await ObtenerMensajesHoyAsync(usuarioId);
            if (mensajesHoy >= MaxMensajesPorDia)
                throw new InvalidOperationException($"Has alcanzado el limite diario de {MaxMensajesPorDia} mensajes. Vuelve manana.");

            var ctxUsuario = await _constructorContexto.ConstruirAsync(usuarioId);

            using var transaction = await _contexto.Database.BeginTransactionAsync();

            try
            {
                var msgUsuario = new MensajeIA
                {
                    ConversacionId = conversacionId,
                    UsuarioId = usuarioId,
                    Rol = "user",
                    Contenido = mensaje,
                    FechaHora = DateTime.UtcNow
                };
                _contexto.MensajesIA.Add(msgUsuario);
                await _contexto.SaveChangesAsync();

                var historial = (await _contexto.MensajesIA
                    .Where(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId)
                    .OrderByDescending(m => m.FechaHora)
                    .ThenByDescending(m => m.Id)
                    .Take(MaxMensajesHistorial)
                    .ToListAsync())
                    .OrderBy(m => m.FechaHora)
                    .ThenBy(m => m.Id)
                    .ToList();

                var resumen = await GenerarResumenSiNecesarioAsync(usuarioId, conversacionId, historial);

                var respuestaTexto = await _proveedorDeepSeek.LlamarAsync(ctxUsuario, historial, resumen);

                _contexto.MensajesIA.Add(new MensajeIA
                {
                    ConversacionId = conversacionId,
                    UsuarioId = usuarioId,
                    Rol = "model",
                    Contenido = respuestaTexto,
                    FechaHora = DateTime.UtcNow
                });
                await _contexto.SaveChangesAsync();

                await transaction.CommitAsync();

                await _gamificacion.SumarXP(usuarioId, XpPorMensaje);

                return respuestaTexto;
            }
            catch
            {
                await transaction.RollbackAsync();
                throw;
            }
        }

        private async Task<string?> GenerarResumenSiNecesarioAsync(int usuarioId, string conversacionId, List<MensajeIA> historial)
        {
            var totalMensajes = await _contexto.MensajesIA
                .CountAsync(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId);

            if (totalMensajes > MaxMensajesHistorial && historial.Count >= MaxMensajesHistorial)
            {
                var msgAntiguos = historial.Take(5).ToList();
                var resumenPartes = msgAntiguos.Select(m => $"[{m.Rol}]: {m.Contenido[..Math.Min(m.Contenido.Length, 100)]}");
                return "Resumen de la conversacion anterior: " + string.Join(" | ", resumenPartes);
            }

            return null;
        }
    }
}
