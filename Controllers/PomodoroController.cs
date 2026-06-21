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

        public PomodoroController(IServicioPomodoro servicioPomodoro, IServicioMisiones servicioMisiones)
        {
            _servicioPomodoro = servicioPomodoro;
            _servicioMisiones = servicioMisiones;
        }

        [HttpGet]
        public async Task<IActionResult> Index()
        {
            var modelo = new PomodoroIndexViewModel();

            var usuarioId = ObtenerUsuarioId();
            if (usuarioId != 0)
            {
                // Configuración
                modelo.Configuracion = await _servicioPomodoro.ObtenerConfiguracion(usuarioId);

                // Estadísticas e Historial de Hoy
                var sesionesHoy = await _servicioPomodoro.ObtenerSesionesHoyAsync(usuarioId);
                modelo.HistorialHoy = sesionesHoy;

                modelo.EstadisticasHoy.CiclosCompletados = 0;
                modelo.EstadisticasHoy.XpGanado = 0;
                foreach (var s in sesionesHoy)
                {
                    modelo.EstadisticasHoy.CiclosCompletados += s.CiclosCompletados;
                    modelo.EstadisticasHoy.XpGanado += s.XpOtorgado;
                }
                int tiempoEnfoque = modelo.Configuracion.TiempoEstudioMin;
                modelo.EstadisticasHoy.MinutosEnfocados = modelo.EstadisticasHoy.CiclosCompletados * tiempoEnfoque;

                modelo.EstadisticasHoy.MisionesCompletadas = await _servicioMisiones.ContarCompletadasHoyAsync(usuarioId);
                modelo.TareasEnfoque = await _servicioPomodoro.ObtenerTareasEnfoqueAsync(usuarioId);
            }

            return View(modelo);
        }

        [HttpGet]
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
                SonidoActivo = config.SonidoActivo
            };
            return View(dto);
        }

        [HttpPost]
        public async Task<IActionResult> Configuracion(ActualizarConfiguracionPomodoroDto dto)
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == 0) return Challenge();
            if (!ModelState.IsValid) return View(dto);
            await _servicioPomodoro.ActualizarConfiguracion(usuarioId, dto);
            return RedirectToAction("Index");
        }
    }
}
