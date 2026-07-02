using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.DTOs;
using EpycusApp.Hubs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.SignalR;
using Microsoft.EntityFrameworkCore;
using MySqlConnector;
using System.Globalization;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioPomodoro : IServicioPomodoro
    {
        private readonly ContextoAplicacion _context;
        private readonly IServicioGamificacion _servicioGamificacion;
        private readonly IServicioBienestar _servicioBienestar;
        private readonly IServicioHabitos _servicioHabitos;
        private readonly IServicioMisiones _servicioMisiones;
        private readonly IHubContext<NotificacionesHub> _hubContext;
        private readonly ILogger<ServicioPomodoro> _logger;

        private async Task<TimeZoneInfo> ObtenerZonaHorariaUsuario(int usuarioId)
        {
            var usuario = await _context.Usuarios
                .Where(u => u.Id == usuarioId)
                .Select(u => u.ZonaHoraria)
                .FirstOrDefaultAsync();
            try
            {
                return TimeZoneInfo.FindSystemTimeZoneById(usuario ?? "Europe/Madrid");
            }
            catch
            {
                return TimeZoneInfo.FindSystemTimeZoneById("Europe/Madrid");
            }
        }

        private DateTime ConvertirAUsuarioTimeZone(int usuarioId, DateTime utcDateTime)
        {
            var tz = _cacheZonaHoraria.GetValueOrDefault(usuarioId);
            if (tz == null)
            {
                var usuario = _context.Usuarios
                    .Where(u => u.Id == usuarioId)
                    .Select(u => u.ZonaHoraria)
                    .FirstOrDefault();
                tz = TimeZoneInfo.FindSystemTimeZoneById(usuario ?? "Europe/Madrid");
                _cacheZonaHoraria[usuarioId] = tz;
            }
            return TimeZoneInfo.ConvertTimeFromUtc(utcDateTime, tz);
        }

        private readonly Dictionary<int, TimeZoneInfo> _cacheZonaHoraria = new();

        public ServicioPomodoro(
            ContextoAplicacion context,
            IServicioGamificacion servicioGamificacion,
            IServicioBienestar servicioBienestar,
            IServicioHabitos servicioHabitos,
            IServicioMisiones servicioMisiones,
            IHubContext<NotificacionesHub> hubContext,
            ILogger<ServicioPomodoro> logger)
        {
            _context = context;
            _servicioGamificacion = servicioGamificacion;
            _servicioBienestar = servicioBienestar;
            _servicioHabitos = servicioHabitos;
            _servicioMisiones = servicioMisiones;
            _hubContext = hubContext;
            _logger = logger;
        }

        public async Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId, int? subTareaId = null)
        {
            if (habitoId.HasValue && habitoId.Value > 0)
            {
                var habito = await _context.Habitos.FirstOrDefaultAsync(h => h.Id == habitoId && h.UsuarioId == usuarioId);
                if (habito is null)
                    throw new InvalidOperationException("El h\u00e1bito especificado no existe o no te pertenece.");
            }

            if (misionId.HasValue && misionId.Value > 0)
            {
                var mision = await _context.Misiones.FirstOrDefaultAsync(m => m.Id == misionId && m.UsuarioId == usuarioId);
                if (mision is null)
                    throw new InvalidOperationException("La misi\u00f3n especificada no existe o no te pertenece.");
            }

            if (subTareaId.HasValue && subTareaId.Value > 0)
            {
                var subTarea = await _context.SubTareas
                    .Include(st => st.Mision)
                    .FirstOrDefaultAsync(st => st.Id == subTareaId && st.Mision.UsuarioId == usuarioId);
                if (subTarea is null)
                    throw new InvalidOperationException("La sub-tarea especificada no existe o no te pertenece.");
            }

            var sesion = new SesionPomodoro
            {
                FechaInicio = DateTime.UtcNow,
                UsuarioId = usuarioId,
                HabitoId = habitoId,
                MisionId = misionId,
                SubTareaId = subTareaId,
                CiclosCompletados = 0,
                XpOtorgado = 0,
                FueCompletada = false,
                Tipo = "Enfoque"
            };

            _context.SesionesPomodoro.Add(sesion);
            await _context.SaveChangesAsync();
            return sesion;
        }

        private const string MensajeSesionActiva = "Ya tienes una sesi\u00f3n activa. Final\u00edzala o canc\u00e9lala antes de iniciar una nueva.";

        public async Task<(bool Exito, SesionPomodoro? Sesion, string? Error)> IniciarSesionSiNoActiva(int usuarioId, int? habitoId, int? misionId, int? subTareaId = null)
        {
            var sesionesHoy = await ObtenerSesionesHoyAsync(usuarioId);
            var activa = sesionesHoy.FirstOrDefault(s => !s.FechaFin.HasValue);
            if (activa != null)
                return (false, null, MensajeSesionActiva);

            try
            {
                var sesion = await IniciarSesion(usuarioId, habitoId, misionId, subTareaId);
                return (true, sesion, null);
            }
            catch (InvalidOperationException ex)
            {
                return (false, null, ex.Message);
            }
            catch (DbUpdateException ex) when (EsViolacionDeSesionUnica(ex))
            {
                // El check de arriba no es atomico (check-then-insert): dos peticiones casi
                // simultaneas (doble-tap, dos pestanas, dos dispositivos) pueden pasar el
                // check antes de que cualquiera termine de insertar. El indice unico sobre
                // SesionAbiertaMarcador (ver ContextoAplicacion.OnModelCreating) es la
                // garantia real a nivel de BD; si la insercion choca con el, se traduce al
                // mismo mensaje amigable en vez de dejar pasar un 500 sin manejar.
                return (false, null, MensajeSesionActiva);
            }
        }

        private static bool EsViolacionDeSesionUnica(DbUpdateException ex)
        {
            return ex.InnerException is MySqlException mysqlEx
                && mysqlEx.ErrorCode == MySqlErrorCode.DuplicateKeyEntry;
        }

        public async Task<(int XpGanado, bool SugerirDescanso, string? PausaActiva)> RegistrarCiclo(int sesionId, int ciclosCompletados, int usuarioId)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null || sesion.UsuarioId != usuarioId)
                return (0, false, null);

            if (ciclosCompletados <= 0 || ciclosCompletados <= sesion.CiclosCompletados)
                return (0, false, null);

            int nuevosCiclos = ciclosCompletados - sesion.CiclosCompletados;
            sesion.CiclosCompletados = ciclosCompletados;

            int xpGanado = ConstantesGamificacion.XpBasePomodoro * nuevosCiclos;
            sesion.XpOtorgado += xpGanado;

            var config = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == sesion.UsuarioId);
            if (config is null)
            {
                config = new ConfiguracionPomodoro { UsuarioId = sesion.UsuarioId };
            }

            if (sesion.SubTareaId.HasValue)
            {
                var subTarea = await _context.SubTareas.FirstOrDefaultAsync(st => st.Id == sesion.SubTareaId);
                if (subTarea != null)
                {
                    subTarea.TiempoEnfoqueSegundos += config.TiempoEstudioMin * 60;
                }
            }

            bool sugerir = config.CiclosAntesDescansoLargo > 0 && (ciclosCompletados % config.CiclosAntesDescansoLargo == 0);

            var pausaActiva = _servicioBienestar.RecomendacionPausaActiva(ciclosCompletados);

            await _context.SaveChangesAsync();

            await _servicioGamificacion.SumarXP(sesion.UsuarioId, xpGanado);
            await _servicioGamificacion.VerificarYOtorgarLogros(sesion.UsuarioId);
            await _servicioGamificacion.ActualizarRacha(sesion.UsuarioId);

            try
            {
                await _hubContext.Clients.Group($"usuario_{sesion.UsuarioId}")
                    .SendAsync("PomodoroCicloCompletado", new
                    {
                        XpGanado = xpGanado,
                        SugerirDescanso = sugerir,
                        PausaActiva = pausaActiva?.Descripcion
                    });
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Error al enviar notificacion SignalR de ciclo pomodoro completado para usuario {UsuarioId}", sesion.UsuarioId);
            }

            return (xpGanado, sugerir, pausaActiva?.Descripcion);
        }

        public async Task<(int XpTotal, int XpBonus)> FinalizarSesion(int sesionId, int ciclosCompletados, int usuarioId)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null || sesion.UsuarioId != usuarioId) return (0, 0);

            if (ciclosCompletados < sesion.CiclosCompletados)
                ciclosCompletados = sesion.CiclosCompletados;

            sesion.CiclosCompletados = ciclosCompletados;
            sesion.FechaFin = DateTime.UtcNow;
            sesion.FueCompletada = true;

            int xpBonus = ciclosCompletados > 0 ? ciclosCompletados * 5 + 10 : 0;
            sesion.XpOtorgado += xpBonus;

            if (sesion.SubTareaId.HasValue)
            {
                var subTarea = await _context.SubTareas.FirstOrDefaultAsync(st => st.Id == sesion.SubTareaId);
                if (subTarea != null)
                {
                    var config = await _context.ConfiguracionesPomodoro.FirstOrDefaultAsync(c => c.UsuarioId == sesion.UsuarioId);
                    var tiempoEstudio = config?.TiempoEstudioMin ?? 25;
                    var segundosReales = (int)(sesion.FechaFin.Value - sesion.FechaInicio).TotalSeconds;
                    var segundosYaRegistrados = sesion.CiclosCompletados * tiempoEstudio * 60;
                    if (segundosReales > segundosYaRegistrados)
                    {
                        subTarea.TiempoEnfoqueSegundos += segundosReales - segundosYaRegistrados;
                    }
                }
            }

            await _context.SaveChangesAsync();

            if (xpBonus > 0)
            {
                await _servicioGamificacion.SumarXP(sesion.UsuarioId, xpBonus);
                await _servicioGamificacion.VerificarYOtorgarLogros(sesion.UsuarioId);
            }

            await _servicioGamificacion.ActualizarRacha(sesion.UsuarioId);

            try
            {
                await _hubContext.Clients.Group($"usuario_{sesion.UsuarioId}")
                    .SendAsync("PomodoroFinalizado", new { XpTotal = sesion.XpOtorgado, SesionGuardada = true });
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Error al enviar notificacion SignalR de pomodoro finalizado para usuario {UsuarioId}", sesion.UsuarioId);
            }

            return (sesion.XpOtorgado, xpBonus);
        }

        public async Task<SesionPomodoro> CrearSesionDescanso(int usuarioId, string tipoDescanso, int segundos)
        {
            var sesion = new SesionPomodoro
            {
                FechaInicio = DateTime.UtcNow.AddSeconds(-segundos),
                FechaFin = DateTime.UtcNow,
                UsuarioId = usuarioId,
                CiclosCompletados = 0,
                XpOtorgado = 0,
                FueCompletada = true,
                Tipo = tipoDescanso
            };

            _context.SesionesPomodoro.Add(sesion);
            await _context.SaveChangesAsync();
            return sesion;
        }

        public async Task CancelarSesion(int sesionId, int usuarioId)
        {
            var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
            if (sesion is null || sesion.UsuarioId != usuarioId) return;

            sesion.FechaFin = DateTime.UtcNow;
            sesion.FueCompletada = false;

            await _context.SaveChangesAsync();
        }

        public async Task<ConfiguracionPomodoro> ObtenerConfiguracion(int usuarioId)
        {
            var cfg = await _context.ConfiguracionesPomodoro.AsNoTracking().FirstOrDefaultAsync(c => c.UsuarioId == usuarioId);
            if (cfg is null)
            {
                cfg = new ConfiguracionPomodoro { UsuarioId = usuarioId };
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
                    ModoPersonalizadoMin = dto.ModoPersonalizadoMin,
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
                existente.ModoPersonalizadoMin = dto.ModoPersonalizadoMin;
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
            return await _context.SesionesPomodoro.AsNoTracking().FirstOrDefaultAsync(s => s.Id == sesionId);
        }

        public async Task<List<SesionPomodoro>> ObtenerSesionesHoyAsync(int usuarioId)
        {
            // "Hoy" debe ser el dia calendario del usuario, no el de UTC: para cualquier
            // usuario al oeste de UTC (toda Latinoamerica) la medianoche UTC cae varias
            // horas antes que su medianoche real, asi que sesiones hechas por la tarde/noche
            // ya contaban como "de manana" -> desaparecian de "Resumen de hoy"/"Historial de
            // hoy" aunque siguieran apareciendo correctamente en el grafico semanal (que si
            // ya usaba la zona horaria del usuario).
            var tz = await ObtenerZonaHorariaUsuario(usuarioId);
            var hoyLocal = TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, tz).Date;
            var hoyUtc = TimeZoneInfo.ConvertTimeToUtc(hoyLocal, tz);
            return await _context.SesionesPomodoro
                .AsNoTracking()
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= hoyUtc)
                .OrderByDescending(s => s.FechaInicio)
                .ToListAsync();
        }

        public async Task<List<SesionPomodoro>> ObtenerHistorialAsync(int usuarioId, DateTime desde, DateTime hasta, int pagina = 1, int tamano = 20, bool? completada = null, bool? conXp = null)
        {
            // "Historial" son sesiones ya terminadas: una sesion todavia en curso (FechaFin
            // null) no pertenece aqui (para eso existe /sesion-activa) -> sin este filtro
            // aparecia como una entrada mas con "0 ciclos, 0 min", ruido confuso en el
            // dialogo de Historial de Android (que consume este endpoint directamente).
            var query = _context.SesionesPomodoro
                .AsNoTracking()
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= desde && s.FechaInicio <= hasta && s.FechaFin != null);

            if (completada.HasValue)
                query = query.Where(s => s.FueCompletada == completada.Value);

            if (conXp.HasValue)
                query = conXp.Value
                    ? query.Where(s => s.XpOtorgado > 0)
                    : query.Where(s => s.XpOtorgado == 0);

            return await query
                .OrderByDescending(s => s.FechaInicio)
                .Skip((pagina - 1) * tamano)
                .Take(tamano)
                .ToListAsync();
        }

        public async Task<int> ObtenerRachaActualAsync(int usuarioId)
        {
            // Igual que en ObtenerSesionesHoyAsync: agrupar por dia calendario del usuario,
            // no por dia UTC, para que la racha no se rompa/adelante artificialmente para
            // usuarios al oeste de UTC.
            var tz = await ObtenerZonaHorariaUsuario(usuarioId);
            var hoy = TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, tz).Date;
            var hace30 = hoy.AddDays(-30);
            var hace30Utc = TimeZoneInfo.ConvertTimeToUtc(hace30, tz);

            // Días (en memoria) con al menos una sesión completada en los últimos 30 días.
            // Se calcula en cliente: el conjunto está acotado (<=30 fechas) y evita la SQL
            // cruda anterior, que mapeaba a un ValueTuple y pasaba los parámetros como
            // objeto anónimo (no se enlazaban @usuarioId/@hace30) -> provocaba un 500.
            var fechasDesc = (await _context.SesionesPomodoro
                    .Where(s => s.UsuarioId == usuarioId
                             && s.FueCompletada
                             && s.FechaInicio >= hace30Utc)
                    .Select(s => s.FechaInicio)
                    .ToListAsync())
                .Select(f => TimeZoneInfo.ConvertTimeFromUtc(f, tz).Date)
                .Distinct()
                .OrderByDescending(f => f)
                .ToList();

            if (fechasDesc.Count == 0) return 0;

            var primera = fechasDesc[0];
            if (primera != hoy && primera != hoy.AddDays(-1))
                return 0;

            int racha = 1;
            for (int i = 1; i < fechasDesc.Count; i++)
            {
                var dias = (fechasDesc[i - 1] - fechasDesc[i]).Days;
                if (dias == 1)
                    racha++;
                else
                    break;
            }
            return racha;
        }

        public async Task<EstadisticasPomodoroPeriodo> ObtenerEstadisticasPeriodoAsync(int usuarioId, DateTime desde, DateTime hasta)
        {
            var sesiones = await _context.SesionesPomodoro
                .AsNoTracking()
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= desde && s.FechaInicio <= hasta)
                .ToListAsync();

            var desdeLocal = ConvertirAUsuarioTimeZone(usuarioId, desde);

            return new EstadisticasPomodoroPeriodo
            {
                Fecha = desdeLocal.ToString("yyyy-MM-dd"),
                Ciclos = sesiones.Sum(s => s.CiclosCompletados),
                // Solo cuenta minutos de sesiones con al menos 1 ciclo real completado: una
                // sesion que se quedo abierta horas y se cancelo/finalizo sin completar nada
                // (bug de cliente, doble pestana, o cancelacion manual) no debe inflar
                // "minutos enfocados" mientras ciclos/XP se quedan en 0 -> esa discrepancia
                // es justo el sintoma reportado por el usuario probando el Pomodoro en vivo.
                Minutos = sesiones.Where(s => s.CiclosCompletados > 0).Sum(s => s.FechaFin.HasValue
                    ? (int)(s.FechaFin.Value - s.FechaInicio).TotalMinutes
                    : 0),
                Xp = sesiones.Sum(s => s.XpOtorgado)
            };
        }

        public async Task<List<EstadisticasPomodoroPeriodo>> ObtenerEstadisticasSemanalesAsync(int usuarioId)
        {
            var tz = await ObtenerZonaHorariaUsuario(usuarioId);
            var hoy = TimeZoneInfo.ConvertTimeFromUtc(DateTime.UtcNow, tz).Date;
            var hace7 = hoy.AddDays(-6);

            var sesiones = await _context.SesionesPomodoro
                .AsNoTracking()
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= TimeZoneInfo.ConvertTimeToUtc(hace7, tz))
                .ToListAsync();

            var resultado = new List<EstadisticasPomodoroPeriodo>();
            for (int i = 0; i < 7; i++)
            {
                var dia = hace7.AddDays(i);
                var diaSiguiente = dia.AddDays(1);
                var delDia = sesiones
                    .Where(s => s.FechaInicio >= TimeZoneInfo.ConvertTimeToUtc(dia, tz) && s.FechaInicio < TimeZoneInfo.ConvertTimeToUtc(diaSiguiente, tz))
                    .ToList();

                resultado.Add(new EstadisticasPomodoroPeriodo
                {
                    Fecha = dia.ToString("ddd", CultureInfo.CreateSpecificCulture("es-ES")),
                    Ciclos = delDia.Sum(s => s.CiclosCompletados),
                    Minutos = delDia.Where(s => s.CiclosCompletados > 0).Sum(s => s.FechaFin.HasValue
                        ? (int)(s.FechaFin.Value - s.FechaInicio).TotalMinutes
                        : 0),
                    Xp = delDia.Sum(s => s.XpOtorgado)
                });
            }

            return resultado;
        }

        public async Task<PomodoroEstadisticasAvanzadasResponse> ObtenerEstadisticasAvanzadasAsync(int usuarioId, DateTime desde, DateTime hasta)
        {
            var tz = await ObtenerZonaHorariaUsuario(usuarioId);
            var desdeUtc = TimeZoneInfo.ConvertTimeToUtc(TimeZoneInfo.ConvertTimeFromUtc(desde, tz), tz);
            var hastaUtc = TimeZoneInfo.ConvertTimeToUtc(TimeZoneInfo.ConvertTimeFromUtc(hasta, tz), tz);

            var sesiones = await _context.SesionesPomodoro
                .AsNoTracking()
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= desdeUtc && s.FechaInicio <= hastaUtc)
                .ToListAsync();

            var diasEnRango = Math.Max(1, (hasta.Date - desde.Date).Days + 1);
            var totalCiclos = sesiones.Sum(s => s.CiclosCompletados);
            var totalMinutos = sesiones.Where(s => s.CiclosCompletados > 0).Sum(s => s.FechaFin.HasValue
                ? (int)(s.FechaFin.Value - s.FechaInicio).TotalMinutes
                : 0);
            var totalXp = sesiones.Sum(s => s.XpOtorgado);

            var heatmap = Enumerable.Range(0, 24).Select(h => new HeatmapPorHora { Hora = h, Ciclos = 0 }).ToList();
            foreach (var sesion in sesiones.Where(s => s.CiclosCompletados > 0))
            {
                var horaLocal = TimeZoneInfo.ConvertTimeFromUtc(sesion.FechaInicio, tz).Hour;
                heatmap[horaLocal].Ciclos += sesion.CiclosCompletados;
            }

            var porMes = sesiones
                .GroupBy(s => new { Year = TimeZoneInfo.ConvertTimeFromUtc(s.FechaInicio, tz).Year, Month = TimeZoneInfo.ConvertTimeFromUtc(s.FechaInicio, tz).Month })
                .OrderBy(g => g.Key.Year).ThenBy(g => g.Key.Month)
                .Select(g => new EstadisticasPomodoroPeriodo
                {
                    Fecha = $"{g.Key.Year}-{g.Key.Month:D2}",
                    Ciclos = g.Sum(s => s.CiclosCompletados),
                    Minutos = g.Where(s => s.CiclosCompletados > 0).Sum(s => s.FechaFin.HasValue
                        ? (int)(s.FechaFin.Value - s.FechaInicio).TotalMinutes
                        : 0),
                    Xp = g.Sum(s => s.XpOtorgado)
                }).ToList();

            return new PomodoroEstadisticasAvanzadasResponse
            {
                TotalCiclos = totalCiclos,
                TotalMinutos = totalMinutos,
                TotalXp = totalXp,
                PromedioCiclosPorDia = Math.Round((double)totalCiclos / diasEnRango, 1),
                PorMes = porMes,
                HeatmapHoras = heatmap
            };
        }

        public async Task<List<SubTarea>> ObtenerSubTareasDisponibles(int usuarioId, int misionId)
        {
            var mision = await _context.Misiones
                .AsNoTracking()
                .Include(m => m.SubTareas)
                .FirstOrDefaultAsync(m => m.Id == misionId && m.UsuarioId == usuarioId);
            return mision?.SubTareas
                .OrderBy(st => st.Orden)
                .ThenBy(st => st.FechaCreacion)
                .ToList() ?? new();
        }

        public async Task<List<TareaPomodoro>> ObtenerTareasEnfoqueAsync(int usuarioId)
        {
            // Secuencial: ambas consultas comparten el mismo DbContext (scoped), que no admite
            // operaciones concurrentes. Ejecutarlas con Task.WhenAll producía
            // "A second operation was started on this context instance before a previous
            // operation completed" y un 500 en /Pomodoro.
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
