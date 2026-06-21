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

            if (ciclosCompletados <= 0 || ciclosCompletados <= sesion.CiclosCompletados)
                return (0, false, null);

            sesion.CiclosCompletados = ciclosCompletados;

            int xpGanado = ConstantesGamificacion.XP_BASE_POMODORO;
            sesion.XpOtorgado += xpGanado;

            var config = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == sesion.UsuarioId);
            if (config is null)
            {
                config = new ConfiguracionPomodoro { UsuarioId = sesion.UsuarioId };
            }

            bool sugerir = config.CiclosAntesDescansoLargo > 0 && (ciclosCompletados % config.CiclosAntesDescansoLargo == 0);
            var pausa = sugerir ? "larga" : "corta";

            var pausaActiva = _servicioBienestar.RecomendacionPausaActiva(ciclosCompletados);

            await _context.SaveChangesAsync();

            await _servicioGamificacion.SumarXP(sesion.UsuarioId, xpGanado);

            return (xpGanado, sugerir, pausaActiva?.Descripcion ?? pausa);
        }

        public async Task FinalizarSesion(int sesionId, int ciclosCompletados)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null) return;

            sesion.CiclosCompletados = ciclosCompletados;
            sesion.FechaFin = DateTime.UtcNow;
            sesion.FueCompletada = true;

            await _context.SaveChangesAsync();
        }

        public async Task CancelarSesion(int sesionId)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null) return;

            sesion.FechaFin = DateTime.UtcNow;
            sesion.FueCompletada = false;

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
                    SonidoSeleccionado = dto.SonidoSeleccionado,
                    Volumen = dto.Volumen,
                    AutoIniciarDescanso = dto.AutoIniciarDescanso,
                    AutoIniciarEnfoque = dto.AutoIniciarEnfoque,
                    TicTacActivo = dto.TicTacActivo,
                    MetaDiariaCiclos = dto.MetaDiariaCiclos,
                    ModoPersonalizadoMinutos = dto.ModoPersonalizadoMinutos,
                    VibracionActiva = dto.VibracionActiva,
                    NotificacionDesktop = dto.NotificacionDesktop,
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
                existente.SonidoSeleccionado = dto.SonidoSeleccionado;
                existente.Volumen = dto.Volumen;
                existente.AutoIniciarDescanso = dto.AutoIniciarDescanso;
                existente.AutoIniciarEnfoque = dto.AutoIniciarEnfoque;
                existente.TicTacActivo = dto.TicTacActivo;
                existente.MetaDiariaCiclos = dto.MetaDiariaCiclos;
                existente.ModoPersonalizadoMinutos = dto.ModoPersonalizadoMinutos;
                existente.VibracionActiva = dto.VibracionActiva;
                existente.NotificacionDesktop = dto.NotificacionDesktop;
                existente.FechaActualizacion = DateTime.UtcNow;
            }
            await _context.SaveChangesAsync();
        }

        public async Task<string> ObtenerTipAleatorio()
        {
            var tip = await _context.TipsPomodoro
                .Where(t => t.EstaActivo)
                .OrderBy(t => EF.Functions.Random())
                .Select(t => t.Tip)
                .FirstOrDefaultAsync();
            return tip ?? string.Empty;
        }

        public async Task<SesionPomodoro?> ObtenerSesion(int sesionId)
        {
            return await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
        }

        public async Task<List<SesionPomodoro>> ObtenerSesionesHoyAsync(int usuarioId)
        {
            var hoy = DateTime.UtcNow.Date;
            return await _context.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= hoy)
                .OrderByDescending(s => s.FechaInicio)
                .ToListAsync();
        }

        public async Task<List<SesionPomodoro>> ObtenerHistorialAsync(int usuarioId, DateTime desde, DateTime hasta, int pagina = 1, int tamano = 20)
        {
            return await _context.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= desde && s.FechaInicio <= hasta)
                .OrderByDescending(s => s.FechaInicio)
                .Skip((pagina - 1) * tamano)
                .Take(tamano)
                .ToListAsync();
        }

        public async Task<int> ObtenerRachaActualAsync(int usuarioId)
        {
            var sesiones = await _context.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId && s.FueCompletada)
                .OrderByDescending(s => s.FechaInicio)
                .Select(s => s.FechaInicio.Date)
                .Distinct()
                .ToListAsync();

            if (sesiones.Count == 0) return 0;

            var hoy = DateTime.UtcNow.Date;
            var ayer = hoy.AddDays(-1);

            if (sesiones[0] != hoy && sesiones[0] != ayer)
                return 0;

            int racha = 1;
            for (int i = 1; i < sesiones.Count; i++)
            {
                if ((sesiones[i - 1] - sesiones[i]).Days == 1)
                    racha++;
                else
                    break;
            }
            return racha;
        }

        public async Task<EstadisticasPomodoroPeriodo> ObtenerEstadisticasPeriodoAsync(int usuarioId, DateTime desde, DateTime hasta)
        {
            var sesiones = await _context.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= desde && s.FechaInicio <= hasta)
                .ToListAsync();

            return new EstadisticasPomodoroPeriodo
            {
                Fecha = desde.ToString("yyyy-MM-dd"),
                Ciclos = sesiones.Sum(s => s.CiclosCompletados),
                Minutos = sesiones.Where(s => s.FueCompletada).Sum(s => (int)((s.FechaFin ?? s.FechaInicio) - s.FechaInicio).TotalMinutes),
                Xp = sesiones.Sum(s => s.XpOtorgado)
            };
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
                    CategoriaNombre = h.Categoria != null ? h.Categoria.Nombre : "Sin categoría",
                    Tipo = "Habito"
                }).ToListAsync();

            var misiones = await _context.Misiones
                .Include(m => m.Categoria)
                .Where(m => m.UsuarioId == usuarioId && m.Estado != "Completado" && m.ConPomodoro)
                .Select(m => new TareaPomodoro
                {
                    Id = m.Id,
                    Nombre = m.Nombre,
                    CategoriaNombre = m.Categoria != null ? m.Categoria.Nombre : "Sin categoría",
                    Tipo = "Mision"
                }).ToListAsync();

            var tareas = new List<TareaPomodoro>();
            tareas.AddRange(habitos);
            tareas.AddRange(misiones);
            return tareas;
        }
    }
}
