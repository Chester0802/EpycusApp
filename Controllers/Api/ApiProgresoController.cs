using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/progreso")]
    [Authorize]
    public class ApiProgresoController : ControllerBase
    {
        private readonly IServicioProgreso _servicioProgreso;

        public ApiProgresoController(IServicioProgreso servicioProgreso)
        {
            _servicioProgreso = servicioProgreso;
        }

        [HttpGet]
        public async Task<IActionResult> Obtener()
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);
            var progreso = await _servicioProgreso.ObtenerProgreso(usuarioId);
            var nivelSiguiente = await _servicioProgreso.ObtenerNivelSiguiente(progreso.NivelActual.Numero);
            var xpParaSiguiente = CalculadorXP.XpParaSiguienteNivel(progreso.NivelActual.Numero);
            var porcentaje = CalculadorXP.PorcentajeProgreso(progreso.XpTotal, progreso.NivelActual.Numero);

            return Ok(RespuestaApi<object>.Exitosa(new
            {
                progreso,
                nivelSiguiente,
                xpParaSiguiente,
                porcentaje
            }));
        }

        [HttpGet("logros")]
        public async Task<IActionResult> Logros()
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);
            var logros = await _servicioProgreso.ObtenerLogrosUsuario(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(logros));
        }

        [HttpGet("historial-animo")]
        public async Task<IActionResult> HistorialAnimo()
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);
            var historial = await _servicioProgreso.ObtenerHistorialAnimo(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(historial));
        }
    }
}
