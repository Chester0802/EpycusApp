using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.Datos;
using Microsoft.EntityFrameworkCore;
using System.Threading.Tasks;
using System;
using System.Linq;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioPomodoro : IServicioPomodoro
    {
        private readonly ContextoAplicacion _context;
        private readonly IServicioGamificacion _servicioGamificacion;
        private readonly IServicioBienestar _servicioBienestar;

        public ServicioPomodoro(ContextoAplicacion context, IServicioGamificacion servicioGamificacion, IServicioBienestar servicioBienestar)
        {
            _context = context;
            _servicioGamificacion = servicioGamificacion;
            _servicioBienestar = servicioBienestar;
        }

        public async Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId)
        {
            var sesion = new SesionPomodoro
            {
                FechaInicio = DateTime.Now,
                UsuarioId = usuarioId,
                HabitoId = habitoId,
                MisionId = misionId,
                CiclosCompletados = 0,
                XpOtorgado = 0,
                FueCompletada = false
            };

            _context.SesionesPomodoro.Add(sesion);
            await _context.SaveChangesAsync();
            return sesion;
        }

        public async Task<(int XpGanado, bool SugerirDescanso, string? PausaActiva)> RegistrarCiclo(int sesionId, int ciclosCompletados)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null)
                return (0, false, null);

            sesion.CiclosCompletados = ciclosCompletados;

            // Calcular XP según la especificación: 15 XP por ciclo
            int xpGanado = ciclosCompletados * 15;
            sesion.XpOtorgado = xpGanado;

            // Obtener configuración del usuario para sugerir descanso largo
            var config = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == sesion.UsuarioId);
            if (config is null)
            {
                config = new ConfiguracionPomodoro { UsuarioId = sesion.UsuarioId };
            }

            bool sugerir = config.CiclosAntesDescansoLargo > 0 && (ciclosCompletados % config.CiclosAntesDescansoLargo == 0);
            var pausa = sugerir ? "larga" : "corta";

            var pausaActiva = _servicioBienestar is Servicios.Implementaciones.ServicioBienestar bienestar
                ? bienestar.RecomendacionPausaActiva(ciclosCompletados)
                : null;

            _context.SesionesPomodoro.Update(sesion);
            await _context.SaveChangesAsync();

            await _servicioGamificacion.SumarXP(sesion.UsuarioId, 15);

            return (xpGanado, sugerir, pausaActiva ?? pausa);
        }

        public async Task FinalizarSesion(int sesionId, int ciclosCompletados)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null) return;

            sesion.CiclosCompletados = ciclosCompletados;
            sesion.FechaFin = DateTime.Now;
            sesion.FueCompletada = true;

            _context.SesionesPomodoro.Update(sesion);
            await _context.SaveChangesAsync();
        }

        public async Task CancelarSesion(int sesionId)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null) return;

            sesion.FechaFin = DateTime.Now;
            sesion.FueCompletada = false;

            _context.SesionesPomodoro.Update(sesion);
            await _context.SaveChangesAsync();
        }

        public async Task<ConfiguracionPomodoro> ObtenerConfiguracion(int usuarioId)
        {
            var cfg = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == usuarioId);
            if (cfg is null)
            {
                cfg = new ConfiguracionPomodoro { UsuarioId = usuarioId };
                _context.ConfiguracionesPomodoro.Add(cfg);
                await _context.SaveChangesAsync();
            }
            return cfg;
        }

        public async Task ActualizarConfiguracion(int usuarioId, ConfiguracionPomodoro config)
        {
            var existente = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == usuarioId);
            if (existente is null)
            {
                config.UsuarioId = usuarioId;
                _context.ConfiguracionesPomodoro.Add(config);
            }
            else
            {
                existente.TiempoEstudioMin = config.TiempoEstudioMin;
                existente.TiempoDescansoMin = config.TiempoDescansoMin;
                existente.TiempoDescansoLargoMin = config.TiempoDescansoLargoMin;
                existente.CiclosAntesDescansoLargo = config.CiclosAntesDescansoLargo;
                existente.SonidoActivo = config.SonidoActivo;
                existente.FechaActualizacion = DateTime.Now;
                _context.ConfiguracionesPomodoro.Update(existente);
            }
            await _context.SaveChangesAsync();
        }

        public async Task<string> ObtenerTipAleatorio()
        {
            var tip = await _context.TipsPomodoro.Where(t => t.EstaActivo).OrderBy(t => Guid.NewGuid()).Select(t => t.Tip).FirstOrDefaultAsync();
            return tip ?? string.Empty;
        }

        public async Task<SesionPomodoro?> ObtenerSesion(int sesionId)
        {
            return await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
        }
    }
}
