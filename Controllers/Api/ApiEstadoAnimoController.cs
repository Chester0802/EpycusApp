using System.ComponentModel.DataAnnotations;
using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/estado-animo")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
    public class ApiEstadoAnimoController : BaseApiController
    {
        private static readonly HashSet<string> EstadosPermitidos = ["Genial", "Bien", "Normal", "Cansado", "Estresado"];

        private readonly IServicioBienestar _servicioBienestar;

        public ApiEstadoAnimoController(IServicioBienestar servicioBienestar)
        {
            _servicioBienestar = servicioBienestar;
        }

        [HttpPost]
        public async Task<IActionResult> Registrar([FromBody] EstadoAnimoDto dto)
        {
            if (!ModelState.IsValid)
                return BadRequest(RespuestaApi<object>.Fallida("Datos inválidos"));

            if (!EstadosPermitidos.Contains(dto.Estado))
                return BadRequest(RespuestaApi<object>.Fallida($"Estado no válido. Permitidos: {string.Join(", ", EstadosPermitidos)}"));

            var usuarioId = ObtenerUsuarioId()!.Value;
            var alerta = await _servicioBienestar.RegistrarEstadoAnimo(usuarioId, dto.Estado, dto.Nota);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true, alertaBienestar = alerta }));
        }

        [HttpGet("historial")]
        public async Task<IActionResult> Historial()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var historial = await _servicioBienestar.ObtenerHistorialAnimoCompletoAsync(usuarioId);
            var resultado = historial.Select(e => new { fecha = e.Fecha, estado = e.Estado, nota = e.Nota });
            return Ok(RespuestaApi<object>.Exitosa(resultado));
        }

        public class EstadoAnimoDto
        {
            [Required(ErrorMessage = "El estado es obligatorio")]
            [StringLength(20, MinimumLength = 2, ErrorMessage = "El estado debe tener entre 2 y 20 caracteres")]
            public string Estado { get; set; } = string.Empty;

            [StringLength(500, ErrorMessage = "La nota no puede superar los 500 caracteres")]
            public string? Nota { get; set; }
        }
    }
}
