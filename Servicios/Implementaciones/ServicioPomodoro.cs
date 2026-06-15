using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioPomodoro : IServicioPomodoro
    {
        private readonly ContextoAplicacion _context;
        private readonly IServicioGamificacion _servicioGamificacion;
        private readonly IServicioBienestar _servicioBienestar;
        private readonly ILogger<ServicioPomodoro> _logger;

        public ServicioPomodoro(ContextoAplicacion context, IServicioGamificacion servicioGamificacion, IServicioBienestar servicioBienestar, ILogger<ServicioPomodoro> logger)
        {
            _context = context;
            _servicioGamificacion = servicioGamificacion;
            _servicioBienestar = servicioBienestar;
            _logger = logger;
        }

        public async Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId)
        {
            var sesion = new SesionPomodoro
            {
                FechaInicio = DateTime.UtcNow,
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

            int xpGanado = ciclosCompletados * ConstantesGamificacion.XP_BASE_POMODORO;
            sesion.XpOtorgado = xpGanado;

            var config = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == sesion.UsuarioId);
            if (config is null)
            {
                config = new ConfiguracionPomodoro { UsuarioId = sesion.UsuarioId };
            }

            bool sugerir = config.CiclosAntesDescansoLargo > 0 && (ciclosCompletados % config.CiclosAntesDescansoLargo == 0);
            var pausa = sugerir ? "larga" : "corta";

            var pausaActiva = _servicioBienestar is ServicioBienestar bienestar
                ? bienestar.RecomendacionPausaActiva(ciclosCompletados)
                : null;

            _context.SesionesPomodoro.Update(sesion);
            await _context.SaveChangesAsync();

            await _servicioGamificacion.SumarXP(sesion.UsuarioId, ConstantesGamificacion.XP_BASE_POMODORO);

            return (xpGanado, sugerir, pausaActiva ?? pausa);
        }

        public async Task FinalizarSesion(int sesionId, int ciclosCompletados)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null) return;

            sesion.CiclosCompletados = ciclosCompletados;
            sesion.FechaFin = DateTime.UtcNow;
            sesion.FueCompletada = true;

            _context.SesionesPomodoro.Update(sesion);
            await _context.SaveChangesAsync();
        }

        public async Task CancelarSesion(int sesionId)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null) return;

            sesion.FechaFin = DateTime.UtcNow;
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

        public async Task ActualizarConfiguracion(int usuarioId, ActualizarConfiguracionPomodoroDto dto)
        {
            var existente = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == usuarioId);
            if (existente is null)
            {
                _context.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro
                {
                    UsuarioId = usuarioId,
                    TiempoEstudioMin = dto.TiempoEstudioMin,
                    TiempoDescansoMin = dto.TiempoDescansoMin,
                    TiempoDescansoLargoMin = dto.TiempoDescansoLargoMin,
                    CiclosAntesDescansoLargo = dto.CiclosAntesDescansoLargo,
                    SonidoActivo = dto.SonidoActivo,
                    FechaActualizacion = DateTime.UtcNow
                });
            }
            else
            {
                existente.TiempoEstudioMin = dto.TiempoEstudioMin;
                existente.TiempoDescansoMin = dto.TiempoDescansoMin;
                existente.TiempoDescansoLargoMin = dto.TiempoDescansoLargoMin;
                existente.CiclosAntesDescansoLargo = dto.CiclosAntesDescansoLargo;
                existente.SonidoActivo = dto.SonidoActivo;
                existente.FechaActualizacion = DateTime.UtcNow;
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

        public async Task<List<SesionPomodoro>> ObtenerSesionesHoyAsync(int usuarioId)
        {
            var hoy = DateTime.Today;
            return await _context.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= hoy)
                .OrderByDescending(s => s.FechaInicio)
                .ToListAsync();
        }

        public async Task<int> ObtenerMisionesCompletadasHoyAsync(int usuarioId)
        {
            var hoy = DateTime.Today;
            return await _context.Misiones
                .Where(m => m.UsuarioId == usuarioId
                    && m.Estado == "Completado"
                    && m.FechaCompletado != null
                    && m.FechaCompletado.Value.Date == hoy)
                .CountAsync();
        }

        public async Task<List<TareaPomodoro>> ObtenerTareasEnfoqueAsync(int usuarioId)
        {
            var habitos = await _context.Habitos
                .Include(h => h.Categoria)
                .Where(h => h.UsuarioId == usuarioId && h.EstaActivo && h.ConPomodoro)
                .Select(h => new TareaPomodoro
                {
                    Id = h.Id,
                    Nombre = h.Nombre,
                    CategoriaNombre = h.Categoria != null ? h.Categoria.Nombre : "Sin categorÃ­a",
                    Tipo = "Habito"
                }).ToListAsync();

            var misiones = await _context.Misiones
                .Include(m => m.Categoria)
                .Where(m => m.UsuarioId == usuarioId && m.Estado != "Completado" && m.ConPomodoro)
                .Select(m => new TareaPomodoro
                {
                    Id = m.Id,
                    Nombre = m.Nombre,
                    CategoriaNombre = m.Categoria != null ? m.Categoria.Nombre : "Sin categorÃ­a",
                    Tipo = "Mision"
                }).ToListAsync();

            var tareas = new List<TareaPomodoro>();
            tareas.AddRange(habitos);
            tareas.AddRange(misiones);
            return tareas;
        }
    }
}
