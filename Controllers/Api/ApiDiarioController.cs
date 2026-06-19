using System.Globalization;
using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/diario")]
    [Authorize]
    public class ApiDiarioController : BaseApiController
    {
        private readonly IServicioDiarioAnimo _servicioDiario;

        public ApiDiarioController(IServicioDiarioAnimo servicioDiario)
        {
            _servicioDiario = servicioDiario;
        }

        [HttpGet("hoy")]
        public async Task<IActionResult> ObtenerHoy()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var entrada = await _servicioDiario.ObtenerEntradaHoy(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { entrada }));
        }

        [HttpGet("fecha")]
        public async Task<IActionResult> ObtenerPorFecha([FromQuery] string fecha)
        {
            if (!DateOnly.TryParseExact(fecha, "yyyy-MM-dd", CultureInfo.InvariantCulture, DateTimeStyles.None, out var fechaParsed))
                return BadRequest(RespuestaApi<object>.Fallida("Formato de fecha inválido. Use yyyy-MM-dd."));

            var usuarioId = ObtenerUsuarioId()!.Value;
            var entrada = await _servicioDiario.ObtenerEntradaPorFecha(usuarioId, fechaParsed);
            return Ok(RespuestaApi<object>.Exitosa(new { entrada }));
        }

        [HttpGet("mes")]
        public async Task<IActionResult> ObtenerMes([FromQuery] int anio, [FromQuery] int mes)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var entradas = await _servicioDiario.ObtenerEntradasMes(usuarioId, anio, mes);
            return Ok(RespuestaApi<object>.Exitosa(new { entradas }));
        }

        [HttpPost]
        public async Task<IActionResult> Registrar([FromBody] RegistrarEntradaDiarioViewModel model)
        {
            if (!ModelState.IsValid)
                return BadRequest(RespuestaApi<object>.Fallida("Datos inválidos"));

            var usuarioId = ObtenerUsuarioId()!.Value;
            var preguntaGuia = _servicioDiario.ObtenerPreguntaGuia();
            var entrada = await _servicioDiario.RegistrarEntrada(usuarioId, model, preguntaGuia);
            return CreatedAtAction(nameof(ObtenerHoy), null, RespuestaApi<object>.Exitosa(new { entrada }, "Entrada registrada correctamente"));
        }

        [HttpPut("{fecha}")]
        public async Task<IActionResult> Actualizar(string fecha, [FromBody] RegistrarEntradaDiarioViewModel model)
        {
            if (!ModelState.IsValid)
                return BadRequest(RespuestaApi<object>.Fallida("Datos inválidos"));

            if (!DateOnly.TryParseExact(fecha, "yyyy-MM-dd", CultureInfo.InvariantCulture, DateTimeStyles.None, out var fechaParsed))
                return BadRequest(RespuestaApi<object>.Fallida("Formato de fecha inválido. Use yyyy-MM-dd."));

            var usuarioId = ObtenerUsuarioId()!.Value;
            var entrada = await _servicioDiario.ActualizarEntrada(usuarioId, fechaParsed, model);

            if (entrada is null)
                return NotFound(RespuestaApi<object>.Fallida("No se encontró una entrada para esa fecha"));

            return Ok(RespuestaApi<object>.Exitosa(new { entrada }, "Entrada actualizada correctamente"));
        }

        [HttpGet("racha")]
        public async Task<IActionResult> ObtenerRacha()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var diasConsecutivos = await _servicioDiario.ObtenerDiasConsecutivos(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { diasConsecutivos }));
        }

        [HttpGet("promedio-mes")]
        public async Task<IActionResult> ObtenerPromedioMes([FromQuery] int anio, [FromQuery] int mes)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var promedio = await _servicioDiario.ObtenerPromedioAnimoMes(usuarioId, anio, mes);
            return Ok(RespuestaApi<object>.Exitosa(new { promedio }));
        }

        [HttpGet("pregunta-guia")]
        public IActionResult ObtenerPreguntaGuia()
        {
            var pregunta = _servicioDiario.ObtenerPreguntaGuia();
            return Ok(RespuestaApi<object>.Exitosa(new { pregunta }));
        }
    }
}
