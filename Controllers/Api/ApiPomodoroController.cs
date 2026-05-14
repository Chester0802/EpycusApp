using System.Security.Claims;
using System.Threading.Tasks;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.Ayudantes;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EPYCUS_WEB_v0._1.Controllers.Api
{
    [Authorize]
    [ApiController]
    [Route("api/[controller]")]
    public class ApiPomodoroController : ControllerBase
    {
        private readonly IServicioPomodoro _servicioPomodoro;

        public ApiPomodoroController(IServicioPomodoro servicioPomodoro)
        {
            _servicioPomodoro = servicioPomodoro;
        }

        private int ObtenerUsuarioIdActual()
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            if (int.TryParse(claim, out var usuarioId) && usuarioId > 0)
                return usuarioId;
            return 0;
        }

        public class IniciarRequest { public int? HabitoId { get; set; } public int? MisionId { get; set; } }

        [HttpPost("iniciar")]
        public async Task<IActionResult> Iniciar([FromBody] IniciarRequest req)
        {
            int usuarioId = ObtenerUsuarioIdActual();
            if (usuarioId == 0)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }
            var sesion = await _servicioPomodoro.IniciarSesion(usuarioId, req?.HabitoId, req?.MisionId);
            return Ok(RespuestaApi<object>.Exitosa(new { sesionId = sesion.Id, fechaInicio = sesion.FechaInicio }));
        }

        public class CicloCompletadoRequest { public int CiclosCompletados { get; set; } }

        [HttpPost("{sesionId}/ciclo-completado")]
        public async Task<IActionResult> CicloCompletado(int sesionId, [FromBody] CicloCompletadoRequest req)
        {
            var resultado = await _servicioPomodoro.RegistrarCiclo(sesionId, req?.CiclosCompletados ?? 0);
            return Ok(RespuestaApi<object>.Exitosa(new { xpGanado = resultado.XpGanado, sugerirDescanso = resultado.SugerirDescanso, pausaActiva = resultado.PausaActiva }));
        }

        [HttpPost("{sesionId}/finalizar")]
        public async Task<IActionResult> Finalizar(int sesionId, [FromBody] CicloCompletadoRequest req)
        {
            await _servicioPomodoro.FinalizarSesion(sesionId, req?.CiclosCompletados ?? 0);
            
            // Recalcular xpTotal de la sesión para devolverlo (o leerlo de base de datos)
            var sesion = await _servicioPomodoro.ObtenerSesion(sesionId);
            return Ok(RespuestaApi<object>.Exitosa(new { xpTotal = sesion?.XpOtorgado ?? 0, sesionGuardada = true }));
        }

        [HttpPost("{sesionId}/cancelar")]
        public async Task<IActionResult> Cancelar(int sesionId)
        {
            await _servicioPomodoro.CancelarSesion(sesionId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpGet("configuracion")]
        public async Task<IActionResult> ObtenerConfiguracion()
        {
            int usuarioId = ObtenerUsuarioIdActual();
            if (usuarioId == 0)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }
            var config = await _servicioPomodoro.ObtenerConfiguracion(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new {
                tiempoEstudio = config.TiempoEstudioMin,
                tiempoDescanso = config.TiempoDescansoMin,
                tiempoDescansoLargo = config.TiempoDescansoLargoMin,
                ciclosAntesDescansoLargo = config.CiclosAntesDescansoLargo,
                sonidoActivo = config.SonidoActivo
            }));
        }

        [HttpPut("configuracion")]
        public async Task<IActionResult> ActualizarConfiguracion([FromBody] EPYCUS_WEB_v0._1.Modelos.Entidades.ConfiguracionPomodoro config)
        {
            int usuarioId = ObtenerUsuarioIdActual();
            if (usuarioId == 0)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }
            await _servicioPomodoro.ActualizarConfiguracion(usuarioId, config);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpGet("tip-aleatorio")]
        public async Task<IActionResult> ObtenerTip()
        {
            var tip = await _servicioPomodoro.ObtenerTipAleatorio();
            return Ok(RespuestaApi<object>.Exitosa(new { tip }));
        }
    }
}
