using EPYCUS_WEB_v0._1.Ayudantes;
using EPYCUS_WEB_v0._1.Datos;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Controllers.Api
{
    [ApiController]
    [Route("api/dashboard")]
    [Authorize]
    public class ApiDashboardController : ControllerBase
    {
        private readonly ContextoAplicacion _contexto;

        public ApiDashboardController(ContextoAplicacion contexto)
        {
            _contexto = contexto;
        }

        [HttpGet("resumen")]
        public async Task<IActionResult> Resumen()
        {
            var usuarioId = int.Parse(User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)!.Value);
            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var habitosPendientes = await _contexto.Habitos
                .Include(h => h.Registros)
                .Where(h => h.UsuarioId == usuarioId && h.EstaActivo)
                .CountAsync(h => !h.Registros.Any(r => r.Fecha == hoy && r.Estado == "Completado"));

            var misionesPendientes = await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId && (m.Estado == "Pendiente" || m.Estado == "EnProgreso"));

            var frase = await _contexto.FrasesMotivacionales
                .Where(f => f.EstaActiva)
                .OrderBy(f => Guid.NewGuid())
                .Select(f => new { frase = f.Frase, autor = f.Autor })
                .FirstOrDefaultAsync();

            var respuesta = new
            {
                kpis = new { habitosPendientes, misionesPendientes },
                habitosPendientes,
                misionesPendientes,
                frase
            };

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }

        [HttpGet("frase-del-dia")]
        public async Task<IActionResult> FraseDelDia()
        {
            var frase = await _contexto.FrasesMotivacionales
                .Where(f => f.EstaActiva)
                .OrderBy(f => Guid.NewGuid())
                .Select(f => new { frase = f.Frase, autor = f.Autor })
                .FirstOrDefaultAsync();

            return Ok(RespuestaApi<object?>.Exitosa(frase));
        }
    }
}
