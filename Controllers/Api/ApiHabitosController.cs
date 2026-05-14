using System.Security.Claims;
using EPYCUS_WEB_v0._1.Ayudantes;
using EPYCUS_WEB_v0._1.DTOs;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EPYCUS_WEB_v0._1.Controllers.Api
{
    [ApiController]
    [Route("api/[controller]")]
    [Authorize]
    public class ApiHabitosController : ControllerBase
    {
        private readonly IServicioHabitos _servicioHabitos;

        public ApiHabitosController(IServicioHabitos servicioHabitos)
        {
            _servicioHabitos = servicioHabitos;
        }

        private int? ObtenerUsuarioId()
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            return int.TryParse(claim, out var usuarioId) ? usuarioId : null;
        }

        [HttpGet]
        public async Task<IActionResult> ObtenerHabitos()
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var respuesta = await _servicioHabitos.ObtenerHabitosConEstadoHoy(usuarioId.Value);

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }

        [HttpGet("hoy")]
        public async Task<IActionResult> ObtenerHabitosHoy()
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var respuesta = await _servicioHabitos.ObtenerHabitosActivosConEstadoHoy(usuarioId.Value);

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }

        [HttpPost("{id}/completar")]
        public async Task<IActionResult> Completar(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var resultado = await _servicioHabitos.CompletarHabito(id, usuarioId.Value);
            if (!resultado.Exito)
                return BadRequest(RespuestaApi<CompletarHabitoRespuestaDto>.Fallida("No se pudo completar el hábito"));

            var respuesta = new CompletarHabitoRespuestaDto { XpGanado = resultado.XpGanado };
            return Ok(RespuestaApi<CompletarHabitoRespuestaDto>.Exitosa(respuesta));
        }

        [HttpPost("{id}/fallar")]
        public async Task<IActionResult> Fallar(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var resultado = await _servicioHabitos.FallarHabito(id, usuarioId.Value);

            if (!resultado.Exito)
            {
                return BadRequest(RespuestaApi<FallarHabitoRespuestaDto>.Fallida(resultado.Mensaje));
            }

            var respuesta = new FallarHabitoRespuestaDto { RachaRota = true };
            return Ok(RespuestaApi<FallarHabitoRespuestaDto>.Exitosa(respuesta));
        }

        [HttpGet("{id}/semana")]
        public async Task<IActionResult> Semana(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var registros = await _servicioHabitos.ObtenerRegistrosSemana(id, usuarioId.Value);

            return Ok(RespuestaApi<object>.Exitosa(registros));
        }
    }
}
