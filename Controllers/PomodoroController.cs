using EpycusApp.DTOs;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using EpycusApp.ViewModels;
using EpycusApp.Servicios.Interfaces;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class PomodoroController : BaseController
    {
        private readonly IServicioPomodoro _servicioPomodoro;
        private readonly IServicioMisiones _servicioMisiones;
        private readonly ILogger<PomodoroController> _logger;

        public PomodoroController(IServicioPomodoro servicioPomodoro, IServicioMisiones servicioMisiones, ILogger<PomodoroController> logger)
        {
            _servicioPomodoro = servicioPomodoro;
            _servicioMisiones = servicioMisiones;
            _logger = logger;
        }

        [HttpGet]
        [ResponseCache(NoStore = true, Location = ResponseCacheLocation.None)]
        public async Task<IActionResult> Index()
        {
            var modelo = new PomodoroIndexViewModel();

            var usuarioId = ObtenerUsuarioId();
            if (usuarioId != 0)
            {
                modelo.Configuracion = await _servicioPomodoro.ObtenerConfiguracion(usuarioId);

                var sesionesHoy = await _servicioPomodoro.ObtenerSesionesHoyAsync(usuarioId);
                modelo.HistorialHoy = sesionesHoy.Where(s => s.FueCompletada || s.CiclosCompletados > 0).ToList();

                modelo.EstadisticasHoy.CiclosCompletados = 0;
                modelo.EstadisticasHoy.XpGanado = 0;
                foreach (var s in sesionesHoy)
                {
                    modelo.EstadisticasHoy.CiclosCompletados += s.CiclosCompletados;
                    modelo.EstadisticasHoy.XpGanado += s.XpOtorgado;
                }
                // Solo cuenta minutos de sesiones con al menos 1 ciclo completado: una sesión
                // que quedó abierta y se canceló/finalizó sin completar nada (bug de cliente,
                // doble pestaña, o cancelación manual) no debe inflar "minutos enfocados"
                // mientras "ciclos completados"/"XP ganado" se quedan en 0 en el mismo resumen.
                // Para una sesión que TODAVÍA sigue abierta (el usuario no le dio Finalizar ni
                // llegó a su meta diaria — lo normal en un uso de varios ciclos seguidos) pero
                // ya completó ciclos reales, se estima ciclos x duración de ciclo configurada
                // en vez de 0: antes, "Minutos enfocados"/"Historial de hoy" se veían vacíos
                // durante TODA una sesión larga en curso, y ese "0" cambiaba de golpe a un
                // número distinto en el siguiente reload en cuanto la sesión por fin se
                // cerraba — confuso, reportado en vivo por el usuario ("cambié la config y el
                // historial pasó de 7 a 0 min").
                modelo.EstadisticasHoy.MinutosEnfocados = sesionesHoy
                    .Where(s => s.CiclosCompletados > 0)
                    .Sum(s => s.FechaFin.HasValue
                        ? (int)(s.FechaFin.Value - s.FechaInicio).TotalMinutes
                        : s.CiclosCompletados * modelo.Configuracion.TiempoEstudioMin);

                modelo.EstadisticasHoy.MisionesCompletadas = await _servicioMisiones.ContarCompletadasHoyAsync(usuarioId);
                modelo.TareasEnfoque = await _servicioPomodoro.ObtenerTareasEnfoqueAsync(usuarioId);
                modelo.RachaActual = await _servicioPomodoro.ObtenerRachaActualAsync(usuarioId);

                modelo.EstadisticasSemanales = await _servicioPomodoro.ObtenerEstadisticasSemanalesAsync(usuarioId);
            }

            return View(modelo);
        }

        [HttpGet]
        [ResponseCache(NoStore = true, Location = ResponseCacheLocation.None)]
        public async Task<IActionResult> Configuracion()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == 0) return Challenge();
            var config = await _servicioPomodoro.ObtenerConfiguracion(usuarioId);
            var dto = new ActualizarConfiguracionPomodoroDto
            {
                TiempoEstudioMin = config.TiempoEstudioMin,
                TiempoDescansoMin = config.TiempoDescansoMin,
                TiempoDescansoLargoMin = config.TiempoDescansoLargoMin,
                CiclosAntesDescansoLargo = config.CiclosAntesDescansoLargo,
                SonidoActivo = config.SonidoActivo,
                SonidoSeleccionado = config.SonidoSeleccionado,
                Volumen = config.Volumen,
                AutoIniciarDescanso = config.AutoIniciarDescanso,
                AutoIniciarEnfoque = config.AutoIniciarEnfoque,
                TicTacActivo = config.TicTacActivo,
                MetaDiariaCiclos = config.MetaDiariaCiclos,
                ModoPersonalizadoMin = config.ModoPersonalizadoMin,
                VibracionActiva = config.VibracionActiva,
                NotificacionDesktop = config.NotificacionDesktop
            };
            return View(dto);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Configuracion(ActualizarConfiguracionPomodoroDto dto)
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == 0) return Challenge();
            if (!ModelState.IsValid)
            {
                TempData["Error"] = "Corrige los errores antes de guardar.";
                return View(dto);
            }
            try
            {
                await _servicioPomodoro.ActualizarConfiguracion(usuarioId, dto);
                TempData["Exito"] = "Configuración guardada correctamente.";
                return RedirectToAction("Index");
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error al guardar configuración Pomodoro para usuario {UsuarioId}", usuarioId);
                TempData["Error"] = "Ocurrió un error al guardar la configuración. Intenta de nuevo.";
                return View(dto);
            }
        }
    }
}
