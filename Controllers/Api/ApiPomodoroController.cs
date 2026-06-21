using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [Authorize]
    [ApiController]
    [Route("api/pomodoro")]
    [EnableRateLimiting("Mobile")]
    public class ApiPomodoroController : BaseApiController
    {
        private readonly IServicioPomodoro _servicioPomodoro;

        public ApiPomodoroController(IServicioPomodoro servicioPomodoro)
        {
            _servicioPomodoro = servicioPomodoro;
        }

        [HttpPost("iniciar")]
        public async Task<IActionResult> Iniciar([FromBody] IniciarRequest req)
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            if (!ModelState.IsValid)
            {
                return BadRequest(RespuestaApi<object>.Fallida("Datos inválidos."));
            }

            var sesionesHoy = await _servicioPomodoro.ObtenerSesionesHoyAsync(usuarioId.Value);
            var sesionActiva = sesionesHoy.FirstOrDefault(s => !s.FechaFin.HasValue);
            if (sesionActiva != null)
            {
                return Conflict(RespuestaApi<object>.Fallida("Ya tienes una sesión activa. Finalízala o cancélala antes de iniciar una nueva."));
            }

            var sesion = await _servicioPomodoro.IniciarSesion(usuarioId.Value, req?.HabitoId, req?.MisionId);
            return Ok(RespuestaApi<object>.Exitosa(new { sesionId = sesion.Id, fechaInicio = sesion.FechaInicio }));
        }

        [HttpPost("{sesionId}/ciclo-completado")]
        public async Task<IActionResult> CicloCompletado(int sesionId, [FromBody] CicloCompletadoRequest req)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(RespuestaApi<object>.Fallida("CiclosCompletados debe ser entre 1 y 100."));
            }

            var sesion = await _servicioPomodoro.ObtenerSesion(sesionId);
            if (sesion == null) return NotFound();
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null || sesion.UsuarioId != usuarioId.Value)
                return Unauthorized(RespuestaApi<object>.Fallida("No autorizado"));

            var resultado = await _servicioPomodoro.RegistrarCiclo(sesionId, req?.CiclosCompletados ?? 0);
            return Ok(RespuestaApi<object>.Exitosa(new { xpGanado = resultado.XpGanado, sugerirDescanso = resultado.SugerirDescanso, pausaActiva = resultado.PausaActiva }));
        }

        [HttpPost("{sesionId}/finalizar")]
        public async Task<IActionResult> Finalizar(int sesionId, [FromBody] CicloCompletadoRequest req)
        {
            if (!ModelState.IsValid)
            {
                return BadRequest(RespuestaApi<object>.Fallida("CiclosCompletados debe ser entre 1 y 100."));
            }

            var sesion = await _servicioPomodoro.ObtenerSesion(sesionId);
            if (sesion == null) return NotFound();
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null || sesion.UsuarioId != usuarioId.Value)
                return Unauthorized(RespuestaApi<object>.Fallida("No autorizado"));

            await _servicioPomodoro.FinalizarSesion(sesionId, req?.CiclosCompletados ?? 0);

            var sesionActualizada = await _servicioPomodoro.ObtenerSesion(sesionId);
            return Ok(RespuestaApi<object>.Exitosa(new { xpTotal = sesionActualizada?.XpOtorgado ?? 0, sesionGuardada = true }));
        }

        [HttpPost("{sesionId}/cancelar")]
        public async Task<IActionResult> Cancelar(int sesionId)
        {
            var sesion = await _servicioPomodoro.ObtenerSesion(sesionId);
            if (sesion == null) return NotFound();
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null || sesion.UsuarioId != usuarioId.Value)
                return Unauthorized(RespuestaApi<object>.Fallida("No autorizado"));

            await _servicioPomodoro.CancelarSesion(sesionId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpGet("configuracion")]
        public async Task<IActionResult> ObtenerConfiguracion()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }
            var config = await _servicioPomodoro.ObtenerConfiguracion(usuarioId.Value);
            return Ok(RespuestaApi<object>.Exitosa(new {
                tiempoEstudio = config.TiempoEstudioMin,
                tiempoDescanso = config.TiempoDescansoMin,
                tiempoDescansoLargo = config.TiempoDescansoLargoMin,
                ciclosAntesDescansoLargo = config.CiclosAntesDescansoLargo,
                sonidoActivo = config.SonidoActivo,
                sonidoSeleccionado = config.SonidoSeleccionado,
                volumen = config.Volumen,
                autoIniciarDescanso = config.AutoIniciarDescanso,
                autoIniciarEnfoque = config.AutoIniciarEnfoque,
                ticTacActivo = config.TicTacActivo,
                metaDiariaCiclos = config.MetaDiariaCiclos,
                modoPersonalizadoMinutos = config.ModoPersonalizadoMinutos,
                vibracionActiva = config.VibracionActiva,
                notificacionDesktop = config.NotificacionDesktop
            }));
        }

        [HttpPut("configuracion")]
        public async Task<IActionResult> ActualizarConfiguracion([FromBody] ActualizarConfiguracionPomodoroDto dto)
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            if (dto == null)
            {
                return BadRequest(RespuestaApi<object>.Fallida("Datos inválidos."));
            }

            if (!ModelState.IsValid)
            {
                return BadRequest(RespuestaApi<object>.Fallida("Uno o más valores no son válidos."));
            }

            await _servicioPomodoro.ActualizarConfiguracion(usuarioId.Value, dto);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpGet("tip-aleatorio")]
        public async Task<IActionResult> ObtenerTip()
        {
            var tip = await _servicioPomodoro.ObtenerTipAleatorio();
            return Ok(RespuestaApi<object>.Exitosa(new { consejo = tip }));
        }

        [HttpGet("sesion-activa")]
        public async Task<IActionResult> ObtenerSesionActiva()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var sesionesHoy = await _servicioPomodoro.ObtenerSesionesHoyAsync(usuarioId.Value);
            var activa = sesionesHoy.FirstOrDefault(s => !s.FechaFin.HasValue);
            if (activa == null)
                return Ok(RespuestaApi<object>.Exitosa(new { activa = false }));

            return Ok(RespuestaApi<object>.Exitosa(new
            {
                activa = true,
                sesionId = activa.Id,
                fechaInicio = activa.FechaInicio,
                ciclosCompletados = activa.CiclosCompletados
            }));
        }

        [HttpGet("historial")]
        public async Task<IActionResult> ObtenerHistorial([FromQuery] DateTime? desde, [FromQuery] DateTime? hasta, [FromQuery] int pagina = 1, [FromQuery] int tamano = 20)
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var desdeDate = desde ?? DateTime.UtcNow.AddDays(-30);
            var hastaDate = hasta ?? DateTime.UtcNow;
            tamano = Math.Clamp(tamano, 1, 100);

            var historial = await _servicioPomodoro.ObtenerHistorialAsync(usuarioId.Value, desdeDate, hastaDate, pagina, tamano);
            return Ok(RespuestaApi<object>.Exitosa(new { historial, pagina, tamano }));
        }

        [HttpGet("racha")]
        public async Task<IActionResult> ObtenerRacha()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var racha = await _servicioPomodoro.ObtenerRachaActualAsync(usuarioId.Value);
            return Ok(RespuestaApi<object>.Exitosa(new { racha }));
        }

        [HttpGet("estadisticas")]
        public async Task<IActionResult> ObtenerEstadisticas([FromQuery] DateTime? desde, [FromQuery] DateTime? hasta)
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var desdeDate = desde ?? DateTime.UtcNow.AddDays(-7);
            var hastaDate = hasta ?? DateTime.UtcNow;

            var stats = await _servicioPomodoro.ObtenerEstadisticasPeriodoAsync(usuarioId.Value, desdeDate, hastaDate);
            return Ok(RespuestaApi<object>.Exitosa(stats));
        }
    }
}
