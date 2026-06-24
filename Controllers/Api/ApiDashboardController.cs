using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/v1/dashboard")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
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

            var respuesta = new DashboardResumenResponse
            {
                Kpis = new DashboardKpis { HabitosPendientes = habitosPendientes, MisionesPendientes = misionesPendientes },
                HabitosPendientes = habitosPendientes,
                MisionesPendientes = misionesPendientes,
                Frase = frase == null ? null : new FraseResponseDto { Frase = frase.Frase, Autor = frase.Autor }
            };

            return Ok(RespuestaApi<DashboardResumenResponse>.Exitosa(respuesta));
        }

        [HttpGet("frase-del-dia")]
        public async Task<IActionResult> FraseDelDia()
        {
            var frase = await _servicioBienestar.ObtenerFraseMotivacionalAleatoria();
            FraseResponseDto? resultado = frase == null ? null : new FraseResponseDto { Frase = frase.Frase, Autor = frase.Autor };
            return Ok(RespuestaApi<FraseResponseDto?>.Exitosa(resultado));
        }
    }
}
