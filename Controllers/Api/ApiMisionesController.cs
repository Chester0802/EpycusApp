using EPYCUS_WEB_v0._1.Ayudantes;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EPYCUS_WEB_v0._1.Controllers.Api
{
    [ApiController]
    [Route("api/misiones")]
    [Authorize]
    public class ApiMisionesController : ControllerBase
    {
        private readonly IServicioMisiones _servicioMisiones;

        public ApiMisionesController(IServicioMisiones servicioMisiones)
        {
            _servicioMisiones = servicioMisiones;
        }

        [HttpPost("{id}/completar")]
        public async Task<IActionResult> Completar(int id)
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);
            var resultado = await _servicioMisiones.CompletarMision(id, usuarioId);

            if (!resultado.Exito)
            {
                return BadRequest(RespuestaApi<object>.Fallida("No se pudo completar la misión"));
            }

            return Ok(RespuestaApi<object>.Exitosa(new { xpGanado = resultado.XpGanado }));
        }

        [HttpPost("{id}/estado")]
        public async Task<IActionResult> CambiarEstado(int id, [FromBody] EstadoDto dto)
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);
            await _servicioMisiones.CambiarEstado(id, dto.Estado, usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        public class EstadoDto
        {
            public string Estado { get; set; } = string.Empty;
        }
    }
}
