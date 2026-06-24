using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/v1/gamificacion")]
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

            var respuesta = new GamificacionProgresoResponse
            {
                XpTotal = progreso.XpTotal,
                Nivel = nivelActual.Numero,
                Titulo = nivelActual.Titulo,
                RachaActual = progreso.RachaActual,
                XpParaSiguienteNivel = xpParaSiguiente,
                PorcentajeProgreso = (double)porcentaje,
                ImagenPersonaje = ConvertirUrlAbsoluta(imagen)
            };

            return Ok(RespuestaApi<GamificacionProgresoResponse>.Exitosa(respuesta));
        }

        [HttpGet("logros")]
        public async Task<IActionResult> ObtenerLogros()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var logros = await _servicioProgreso.ObtenerTodosLosLogros();
            var desbloqueados = await _servicioProgreso.ObtenerLogrosUsuario(usuarioId);

            var respuesta = logros.Select(l => new LogroConProgresoResponse
            {
                Logro = l,
                Desbloqueado = desbloqueados.Any(d => d.LogroId == l.Id),
                Progreso = 0,
                Meta = l.CondicionValor
            }).ToList();

            return Ok(RespuestaApi<List<LogroConProgresoResponse>>.Exitosa(respuesta));
        }
    }
}
