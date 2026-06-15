using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/dashboard")]
    [Authorize]
    public class ApiDashboardController : BaseApiController
    {
        private readonly IServicioBienestar _servicioBienestar;

        public ApiDashboardController(IServicioBienestar servicioBienestar)
        {
            _servicioBienestar = servicioBienestar;
        }

        [HttpGet("resumen")]
        public async Task<IActionResult> Resumen()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;

            var habitosPendientes = await _servicioBienestar.ObtenerHabitosPendientesAsync(usuarioId);
            var misionesPendientes = await _servicioBienestar.ObtenerMisionesPendientesAsync(usuarioId);
            var frase = await _servicioBienestar.ObtenerFraseMotivacionalAleatoria();

            var respuesta = new
            {
                kpis = new { habitosPendientes, misionesPendientes },
                habitosPendientes,
                misionesPendientes,
                frase = frase == null ? null : new { frase = frase.Frase, autor = frase.Autor }
            };

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }

        [HttpGet("frase-del-dia")]
        public async Task<IActionResult> FraseDelDia()
        {
            var frase = await _servicioBienestar.ObtenerFraseMotivacionalAleatoria();
            var resultado = frase == null ? null : new { frase = frase.Frase, autor = frase.Autor };
            return Ok(RespuestaApi<object?>.Exitosa(resultado));
        }
    }
}
