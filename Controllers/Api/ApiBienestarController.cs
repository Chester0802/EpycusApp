using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/bienestar")]
    [Authorize]
    public class ApiBienestarController : BaseApiController
    {
        private readonly IServicioBienestar _servicioBienestar;

        public ApiBienestarController(IServicioBienestar servicioBienestar)
        {
            _servicioBienestar = servicioBienestar;
        }

        [HttpGet("resumen")]
        public async Task<IActionResult> ObtenerResumen()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;

            var alertasTask = _servicioBienestar.ObtenerAlertasActivas(usuarioId);
            var fraseTask = _servicioBienestar.ObtenerFraseMotivacionalAleatoria();
            var estadoHoyTask = _servicioBienestar.ObtenerEstadoHoy(usuarioId);
            var habitosTask = _servicioBienestar.ObtenerHabitosPendientesAsync(usuarioId);
            var misionesTask = _servicioBienestar.ObtenerMisionesPendientesAsync(usuarioId);

            await Task.WhenAll(alertasTask, fraseTask, estadoHoyTask, habitosTask, misionesTask);

            var resultado = new
            {
                alertas = alertasTask.Result,
                frase = fraseTask.Result,
                estadoHoy = estadoHoyTask.Result,
                habitosPendientes = habitosTask.Result,
                misionesPendientes = misionesTask.Result
            };

            return Ok(RespuestaApi<object>.Exitosa(resultado));
        }

        [HttpGet("alertas")]
        public async Task<IActionResult> ObtenerAlertas()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var alertas = await _servicioBienestar.ObtenerAlertasActivas(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { alertas }));
        }

        [HttpGet("frase")]
        public async Task<IActionResult> ObtenerFrase()
        {
            var frase = await _servicioBienestar.ObtenerFraseMotivacionalAleatoria();
            return Ok(RespuestaApi<object>.Exitosa(new { frase }));
        }

        [HttpGet("estado-hoy")]
        public async Task<IActionResult> ObtenerEstadoHoy()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var estado = await _servicioBienestar.ObtenerEstadoHoy(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { estado }));
        }

        [HttpGet("historial-animo")]
        public async Task<IActionResult> ObtenerHistorialAnimo([FromQuery] int dias = 30)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var historial = await _servicioBienestar.ObtenerHistorialAnimo(usuarioId, dias);
            return Ok(RespuestaApi<object>.Exitosa(new { historial }));
        }

        [HttpGet("habitos-pendientes")]
        public async Task<IActionResult> ObtenerHabitosPendientes()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var cantidad = await _servicioBienestar.ObtenerHabitosPendientesAsync(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { cantidad }));
        }

        [HttpGet("misiones-pendientes")]
        public async Task<IActionResult> ObtenerMisionesPendientes()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var cantidad = await _servicioBienestar.ObtenerMisionesPendientesAsync(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { cantidad }));
        }

        [HttpPost("pausa-activa")]
        public IActionResult ObtenerPausaActiva([FromBody] PausaActivaDto dto)
        {
            if (!ModelState.IsValid)
                return BadRequest(RespuestaApi<object>.Fallida("Datos inválidos"));

            var recomendacion = _servicioBienestar.RecomendacionPausaActiva(dto.CiclosCompletados);
            return Ok(RespuestaApi<object>.Exitosa(new { recomendacion }));
        }

        public class PausaActivaDto
        {
            public int CiclosCompletados { get; set; }
        }
    }
}
