using System.Globalization;
using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/v1/diario")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
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
            return Ok(RespuestaApi<DiarioEntradaResponse>.Exitosa(new DiarioEntradaResponse { Entrada = entrada }));
        }

        [HttpGet("fecha")]
        public async Task<IActionResult> ObtenerPorFecha([FromQuery] string fecha)
        {
            if (!DateOnly.TryParseExact(fecha, "yyyy-MM-dd", CultureInfo.InvariantCulture, DateTimeStyles.None, out var fechaParsed))
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("Formato de fecha inválido. Use yyyy-MM-dd."));

            var usuarioId = ObtenerUsuarioId()!.Value;
            var entrada = await _servicioDiario.ObtenerEntradaPorFecha(usuarioId, fechaParsed);
            return Ok(RespuestaApi<DiarioEntradaResponse>.Exitosa(new DiarioEntradaResponse { Entrada = entrada }));
        }

        [HttpGet("mes")]
        public async Task<IActionResult> ObtenerMes([FromQuery] int anio, [FromQuery] int mes)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var entradas = await _servicioDiario.ObtenerEntradasMes(usuarioId, anio, mes);
            return Ok(RespuestaApi<DiarioEntradasResponse>.Exitosa(new DiarioEntradasResponse { Entradas = entradas }));
        }

        [HttpPost]
        public async Task<IActionResult> Registrar([FromBody] RegistrarEntradaDiarioViewModel model)
        {
            if (!ModelState.IsValid)
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("Datos inválidos"));

            var usuarioId = ObtenerUsuarioId()!.Value;
            var preguntaGuia = _servicioDiario.ObtenerPreguntaGuia();
            var entrada = await _servicioDiario.RegistrarEntrada(usuarioId, model, preguntaGuia);
            return CreatedAtAction(nameof(ObtenerHoy), null, RespuestaApi<DiarioEntradaResponse>.Exitosa(new DiarioEntradaResponse { Entrada = entrada }, "Entrada registrada correctamente"));
        }

        [HttpPut("{fecha}")]
        public async Task<IActionResult> Actualizar(string fecha, [FromBody] RegistrarEntradaDiarioViewModel model)
        {
            if (!ModelState.IsValid)
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("Datos inválidos"));

            if (!DateOnly.TryParseExact(fecha, "yyyy-MM-dd", CultureInfo.InvariantCulture, DateTimeStyles.None, out var fechaParsed))
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("Formato de fecha inválido. Use yyyy-MM-dd."));

            var usuarioId = ObtenerUsuarioId()!.Value;
            var entrada = await _servicioDiario.ActualizarEntrada(usuarioId, fechaParsed, model);

            if (entrada is null)
                return NotFound(RespuestaApi<MensajeResponseDto>.Fallida("No se encontró una entrada para esa fecha"));

            return Ok(RespuestaApi<DiarioEntradaResponse>.Exitosa(new DiarioEntradaResponse { Entrada = entrada }, "Entrada actualizada correctamente"));
        }

        [HttpGet("racha")]
        public async Task<IActionResult> ObtenerRacha()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var diasConsecutivos = await _servicioDiario.ObtenerDiasConsecutivos(usuarioId);
            return Ok(RespuestaApi<DiarioRachaResponse>.Exitosa(new DiarioRachaResponse { DiasConsecutivos = diasConsecutivos }));
        }

        [HttpGet("promedio-mes")]
        public async Task<IActionResult> ObtenerPromedioMes([FromQuery] int anio, [FromQuery] int mes)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var promedio = await _servicioDiario.ObtenerPromedioAnimoMes(usuarioId, anio, mes);
            return Ok(RespuestaApi<DiarioPromedioMesResponse>.Exitosa(new DiarioPromedioMesResponse { Promedio = promedio }));
        }

        [HttpGet("pregunta-guia")]
        public IActionResult ObtenerPreguntaGuia()
        {
            var pregunta = _servicioDiario.ObtenerPreguntaGuia();
            return Ok(RespuestaApi<PreguntaGuiaResponse>.Exitosa(new PreguntaGuiaResponse { Pregunta = pregunta }));
        }
    }
}
