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
        private readonly IServicioHabitos _servicioHabitos;
        private readonly IServicioMisiones _servicioMisiones;
        private readonly ILogger<ServicioPomodoro> _logger;

        public ServicioPomodoro(
            ContextoAplicacion context,
            IServicioGamificacion servicioGamificacion,
            IServicioBienestar servicioBienestar,
            IServicioHabitos servicioHabitos,
            IServicioMisiones servicioMisiones,
            ILogger<ServicioPomodoro> logger)
        {
            _context = context;
            _servicioGamificacion = servicioGamificacion;
            _servicioBienestar = servicioBienestar;
            _servicioHabitos = servicioHabitos;
            _servicioMisiones = servicioMisiones;
            _logger = logger;
        }

        public async Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId)
        {
            if (habitoId.HasValue)
            {
                var habito = await _context.Habitos.FirstOrDefaultAsync(h => h.Id == habitoId && h.UsuarioId == usuarioId);
                if (habito is null)
                    throw new InvalidOperationException("El hábito especificado no existe o no te pertenece.");
            }

            if (misionId.HasValue)
            {
                var mision = await _context.Misiones.FirstOrDefaultAsync(m => m.Id == misionId && m.UsuarioId == usuarioId);
                if (mision is null)
                    throw new InvalidOperationException("La misión especificada no existe o no te pertenece.");
            }

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

        public async Task<(bool Exito, SesionPomodoro? Sesion, string? Error)> IniciarSesionSiNoActiva(int usuarioId, int? habitoId, int? misionId)
        {
            var sesionesHoy = await ObtenerSesionesHoyAsync(usuarioId);
            var activa = sesionesHoy.FirstOrDefault(s => !s.FechaFin.HasValue);
            if (activa != null)
                return (false, null, "Ya tienes una sesión activa. Finalízala o cancélala antes de iniciar una nueva.");

            try
            {
                var sesion = await IniciarSesion(usuarioId, habitoId, misionId);
                return (true, sesion, null);
            }
            catch (InvalidOperationException ex)
            {
                return (false, null, ex.Message);
            }
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
            await _servicioGamificacion.VerificarYOtorgarLogros(sesion.UsuarioId);

            return (xpGanado, sugerir, pausaActiva?.Descripcion ?? pausa);
        }

        public async Task<(int XpTotal, int XpBonus)> FinalizarSesion(int sesionId, int ciclosCompletados)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null) return (0, 0);

            sesion.CiclosCompletados = ciclosCompletados;
            sesion.FechaFin = DateTime.UtcNow;
            sesion.FueCompletada = true;

            int xpBonus = ciclosCompletados * 5 + 10;
            sesion.XpOtorgado += xpBonus;

            await _context.SaveChangesAsync();

            if (xpBonus > 0)
            {
                var (_, _, _) = await _servicioGamificacion.SumarXP(sesion.UsuarioId, xpBonus);
                await _servicioGamificacion.VerificarYOtorgarLogros(sesion.UsuarioId);
            }

            return (sesion.XpOtorgado, xpBonus);
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

            var config = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == usuarioId);
            var tiempoEstudio = config?.TiempoEstudioMin ?? 25;

            return new EstadisticasPomodoroPeriodo
            {
                Fecha = desde.ToString("yyyy-MM-dd"),
                Ciclos = sesiones.Sum(s => s.CiclosCompletados),
                Minutos = sesiones.Sum(s => s.CiclosCompletados * tiempoEstudio),
                Xp = sesiones.Sum(s => s.XpOtorgado)
            };
        }

        public async Task<List<EstadisticasPomodoroPeriodo>> ObtenerEstadisticasSemanalesAsync(int usuarioId)
        {
            var config = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == usuarioId);
            var tiempoEstudio = config?.TiempoEstudioMin ?? 25;

            var hoy = DateTime.UtcNow.Date;
            var hace7 = hoy.AddDays(-6);

            var sesiones = await _context.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= hace7)
                .ToListAsync();

            var resultado = new List<EstadisticasPomodoroPeriodo>();
            for (int i = 0; i < 7; i++)
            {
                var dia = hace7.AddDays(i);
                var diaSiguiente = dia.AddDays(1);
                var delDia = sesiones
                    .Where(s => s.FechaInicio >= dia && s.FechaInicio < diaSiguiente)
                    .ToList();

                resultado.Add(new EstadisticasPomodoroPeriodo
                {
                    Fecha = dia.ToString("ddd"),
                    Ciclos = delDia.Sum(s => s.CiclosCompletados),
                    Minutos = delDia.Sum(s => s.CiclosCompletados * tiempoEstudio),
                    Xp = delDia.Sum(s => s.XpOtorgado)
                });
            }

            return resultado;
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
            return tareas.DistinctBy(t => new { t.Id, t.Tipo }).ToList();
        }
    }
}
