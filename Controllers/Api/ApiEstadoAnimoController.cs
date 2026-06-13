using EPYCUS_WEB_v0._1.Ayudantes;
using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Controllers.Api
{
    [ApiController]
    [Route("api/estado-animo")]
    [Authorize]
    public class ApiEstadoAnimoController : ControllerBase
    {
        private readonly ContextoAplicacion _contexto;
        private readonly IServicioBienestar _servicioBienestar;

        public ApiEstadoAnimoController(ContextoAplicacion contexto, IServicioBienestar servicioBienestar)
        {
            _contexto = contexto;
            _servicioBienestar = servicioBienestar;
        }

        [HttpPost]
        public async Task<IActionResult> Registrar([FromBody] EstadoAnimoDto dto)
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);

            var estado = new EstadoAnimo
            {
                UsuarioId = usuarioId,
                Estado = dto.Estado,
                Nota = dto.Nota,
                Fecha = DateOnly.FromDateTime(DateTime.Today),
                FechaRegistro = DateTime.UtcNow
            };

            _contexto.EstadosAnimo.Add(estado);
            await _contexto.SaveChangesAsync();

            var alerta = await _servicioBienestar.VerificarAnimoNegativoConsecutivo(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true, alertaBienestar = alerta }));
        }

        [HttpGet("historial")]
        public async Task<IActionResult> Historial()
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);
            var historial = await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .Select(e => new { fecha = e.Fecha, estado = e.Estado, nota = e.Nota })
                .ToListAsync();

            return Ok(RespuestaApi<object>.Exitosa(historial));
        }

        public class EstadoAnimoDto
        {
            public string Estado { get; set; } = string.Empty;
            public string? Nota { get; set; }
        }
    }
}
