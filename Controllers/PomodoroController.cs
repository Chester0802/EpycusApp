using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using EpycusApp.ViewModels;
using EpycusApp.Servicios.Interfaces;
using System.Security.Claims;
using System.Threading.Tasks;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class PomodoroController : Controller
    {
        private readonly IServicioPomodoro _servicioPomodoro;

        public PomodoroController(IServicioPomodoro servicioPomodoro)
        {
            _servicioPomodoro = servicioPomodoro;
        }

        [AllowAnonymous]
        [HttpGet("")]
        [HttpGet("Index")]
        public async Task<IActionResult> Index()
        {
            var modelo = new PomodoroIndexViewModel();

            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            if (User.Identity != null && User.Identity.IsAuthenticated && int.TryParse(claim, out var usuarioId) && usuarioId != 0)
            {
                // ConfiguraciÃ³n
                modelo.Configuracion = await _servicioPomodoro.ObtenerConfiguracion(usuarioId);

                // EstadÃ­sticas e Historial de Hoy
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

                modelo.EstadisticasHoy.MisionesCompletadas = await _servicioPomodoro.ObtenerMisionesCompletadasHoyAsync(usuarioId);
                modelo.TareasEnfoque = await _servicioPomodoro.ObtenerTareasEnfoqueAsync(usuarioId);
            }

            return View(modelo);
        }
    }
}
