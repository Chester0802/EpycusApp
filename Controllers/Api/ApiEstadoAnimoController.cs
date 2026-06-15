using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/estado-animo")]
    [Authorize]
    public class ApiEstadoAnimoController : BaseApiController
    {
        private readonly IServicioBienestar _servicioBienestar;

        public ApiEstadoAnimoController(IServicioBienestar servicioBienestar)
        {
            _servicioBienestar = servicioBienestar;
        }

        [HttpPost]
        public async Task<IActionResult> Registrar([FromBody] EstadoAnimoDto dto)
        {
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
            public string Estado { get; set; } = string.Empty;
            public string? Nota { get; set; }
        }
    }
}
