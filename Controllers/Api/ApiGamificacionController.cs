using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/gamificacion")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
    public class ApiGamificacionController : BaseApiController
    {
        private readonly IServicioGamificacion _servicioGamificacion;
        private readonly IServicioProgreso _servicioProgreso;

        public ApiGamificacionController(IServicioGamificacion servicioGamificacion, IServicioProgreso servicioProgreso)
        {
            _servicioGamificacion = servicioGamificacion;
            _servicioProgreso = servicioProgreso;
        }

        [HttpGet("mi-progreso")]
        public async Task<IActionResult> ObtenerProgreso()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var progreso = await _servicioProgreso.ObtenerProgreso(usuarioId);
            var nivelActual = progreso.NivelActual;
            var porcentaje = CalculadorXP.PorcentajeProgreso(progreso.XpTotal, nivelActual.Numero);
            var xpParaSiguiente = CalculadorXP.XpParaSiguienteNivel(nivelActual.Numero);
            var imagen = await _servicioProgreso.ObtenerImagenPersonaje(usuarioId, nivelActual.Numero);

            var respuesta = new
            {
                xpTotal = progreso.XpTotal,
                nivel = nivelActual.Numero,
                titulo = nivelActual.Titulo,
                rachaActual = progreso.RachaActual,
                xpParaSiguienteNivel = xpParaSiguiente,
                porcentajeProgreso = porcentaje,
                imagenPersonaje = ConvertirUrlAbsoluta(imagen)
            };

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }

        [HttpGet("logros")]
        public async Task<IActionResult> ObtenerLogros()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var logros = await _servicioProgreso.ObtenerTodosLosLogros();
            var desbloqueados = await _servicioProgreso.ObtenerLogrosUsuario(usuarioId);

            var respuesta = logros.Select(l => new
            {
                logro = l,
                desbloqueado = desbloqueados.Any(d => d.LogroId == l.Id),
                progreso = 0,
                meta = l.CondicionValor
            });

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }
    }
}
