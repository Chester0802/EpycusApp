using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/[controller]")]
    [Authorize]
    public class ApiHabitosController : BaseApiController
    {
        private readonly IServicioHabitos _servicioHabitos;

        public ApiHabitosController(IServicioHabitos servicioHabitos)
        {
            _servicioHabitos = servicioHabitos;
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
