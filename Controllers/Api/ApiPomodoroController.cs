using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [Authorize]
    [ApiController]
    [Route("api/pomodoro")]
    [EnableRateLimiting("Pomodoro")]
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

            var (exito, sesion, error) = await _servicioPomodoro.IniciarSesionSiNoActiva(usuarioId.Value, req?.HabitoId, req?.MisionId);
            if (!exito)
            {
                return Conflict(RespuestaApi<object>.Fallida(error ?? "No se pudo iniciar la sesión."));
            }

            return Ok(RespuestaApi<PomodoroIniciarResponse>.Exitosa(new PomodoroIniciarResponse { SesionId = sesion!.Id, FechaInicio = sesion.FechaInicio }));
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
            return Ok(RespuestaApi<PomodoroCicloCompletadoResponse>.Exitosa(new PomodoroCicloCompletadoResponse { XpGanado = resultado.XpGanado, SugerirDescanso = resultado.SugerirDescanso, PausaActiva = resultado.PausaActiva }));
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

            var (xpTotal, xpBonus) = await _servicioPomodoro.FinalizarSesion(sesionId, req?.CiclosCompletados ?? 0);
            return Ok(RespuestaApi<PomodoroFinalizarResponse>.Exitosa(new PomodoroFinalizarResponse { XpTotal = xpTotal, SesionGuardada = true, XpBonus = xpBonus }));
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
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
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
            return Ok(RespuestaApi<PomodoroConfiguracionResponse>.Exitosa(new PomodoroConfiguracionResponse
            {
                TiempoEstudio = config.TiempoEstudioMin,
                TiempoDescanso = config.TiempoDescansoMin,
                TiempoDescansoLargo = config.TiempoDescansoLargoMin,
                CiclosAntesDescansoLargo = config.CiclosAntesDescansoLargo,
                SonidoActivo = config.SonidoActivo,
                SonidoSeleccionado = config.SonidoSeleccionado,
                Volumen = config.Volumen,
                AutoIniciarDescanso = config.AutoIniciarDescanso,
                AutoIniciarEnfoque = config.AutoIniciarEnfoque,
                TicTacActivo = config.TicTacActivo,
                MetaDiariaCiclos = config.MetaDiariaCiclos,
                ModoPersonalizadoMinutos = config.ModoPersonalizadoMinutos,
                VibracionActiva = config.VibracionActiva,
                NotificacionDesktop = config.NotificacionDesktop
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
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpGet("tip-aleatorio")]
        public async Task<IActionResult> ObtenerTip()
        {
            var tip = await _servicioPomodoro.ObtenerTipAleatorio();
            return Ok(RespuestaApi<PomodoroTipResponse>.Exitosa(new PomodoroTipResponse { Consejo = tip }));
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
                return Ok(RespuestaApi<PomodoroSesionActivaResponse>.Exitosa(new PomodoroSesionActivaResponse { Activa = false }));

            return Ok(RespuestaApi<PomodoroSesionActivaResponse>.Exitosa(new PomodoroSesionActivaResponse
            {
                Activa = true,
                SesionId = activa.Id,
                FechaInicio = activa.FechaInicio,
                CiclosCompletados = activa.CiclosCompletados
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
            return Ok(RespuestaApi<PomodoroHistorialResponse>.Exitosa(new PomodoroHistorialResponse { Historial = historial, Pagina = pagina, Tamano = tamano }));
        }

        [HttpGet("racha")]
        public async Task<IActionResult> ObtenerRacha()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var racha = await _servicioPomodoro.ObtenerRachaActualAsync(usuarioId.Value);
            return Ok(RespuestaApi<PomodoroRachaResponse>.Exitosa(new PomodoroRachaResponse { Racha = racha }));
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

        [HttpGet("estadisticas-semanales")]
        public async Task<IActionResult> ObtenerEstadisticasSemanales()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == null)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var stats = await _servicioPomodoro.ObtenerEstadisticasSemanalesAsync(usuarioId.Value);
            return Ok(RespuestaApi<List<EstadisticasPomodoroPeriodo>>.Exitosa(stats));
        }
    }
}
