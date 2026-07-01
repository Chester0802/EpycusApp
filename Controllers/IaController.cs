using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Ia;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers
{
    [Authorize]
    [EnableRateLimiting("DeepSeek")]
    public class IaController : BaseController
    {
        private readonly IServicioIA _servicioIA;
        private readonly IServicioBienestar _servicioBienestar;
        private readonly ILogger<IaController> _logger;

        public IaController(
            IServicioIA servicioIA,
            IServicioBienestar servicioBienestar,
            ILogger<IaController> logger)
        {
            _servicioIA = servicioIA;
            _servicioBienestar = servicioBienestar;
            _logger = logger;
        }

        [HttpGet]
        public async Task<IActionResult> Index(string? conv)
        {
            var usuarioId = ObtenerUsuarioId();

            if (string.IsNullOrWhiteSpace(conv))
            {
                var nuevaId = _servicioIA.NuevaConversacionId();
                return RedirectToAction(nameof(Index), new { conv = nuevaId });
            }

            var mensajes = await _servicioIA.ObtenerHistorialAsync(usuarioId, conv);
            var sugerencias = await _servicioIA.ObtenerSugerenciasPersonalizadasAsync(usuarioId);
            var conversaciones = await _servicioIA.ObtenerConversacionesAsync(usuarioId);
            var bienestarCtx = await _servicioIA.ObtenerBienestarContextoAsync(usuarioId);

            return View(new IaChatViewModel
            {
                ConversacionId = conv,
                Mensajes = mensajes,
                Sugerencias = sugerencias,
                Conversaciones = conversaciones,
                BienestarCtx = bienestarCtx
            });
        }

        [HttpGet]
        public IActionResult Nueva()
        {
            return RedirectToAction(nameof(Index));
        }

        [HttpPost("/api/ia/chat")]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Chat([FromBody] ChatMensajeDto? dto)
        {
            if (dto == null
                || string.IsNullOrWhiteSpace(dto.Mensaje)
                || string.IsNullOrWhiteSpace(dto.ConversacionId))
            {
                return BadRequest(new { exito = false, error = "Datos incompletos." });
            }

            dto.Mensaje = dto.Mensaje.Trim();
            if (dto.Mensaje.Length > 2000)
                return BadRequest(new { exito = false, error = "El mensaje no puede superar los 2000 caracteres." });

            var usuarioId = ObtenerUsuarioId();

            try
            {
                var respuesta = await _servicioIA.ChatAsync(
                    usuarioId,
                    dto.Mensaje,
                    dto.ConversacionId);

                return Ok(new { exito = true, respuesta, mensajeId = 0 });
            }
            catch (UnauthorizedAccessException)
            {
                return Forbid();
            }
            catch (InvalidOperationException ex) when (ex.Message.Contains("limite diario"))
            {
                return Ok(new { exito = false, error = ex.Message });
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error en chat IA para usuario {UsuarioId}", usuarioId);
                return Ok(new { exito = false, error = "No pude conectarme con EDY AI. Intentelo de nuevo." });
            }
        }

        [HttpPost("/api/ia/feedback")]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Feedback([FromBody] FeedbackDto? dto)
        {
            if (dto == null || dto.MensajeId <= 0)
                return BadRequest(new { exito = false, error = "Datos incompletos." });

            var usuarioId = ObtenerUsuarioId();
            await _servicioIA.RegistrarFeedbackAsync(usuarioId, dto.MensajeId, dto.Util);
            return Ok(new { exito = true });
        }

        [HttpPost("/api/ia/registrar-animo")]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> RegistrarAnimo([FromBody] AnimoChatDto? dto)
        {
            if (dto == null || string.IsNullOrWhiteSpace(dto.Estado))
                return BadRequest(new { exito = false });

            var usuarioId = ObtenerUsuarioId();
            await _servicioBienestar.RegistrarEstadoAnimo(usuarioId, dto.Estado, dto.Nota);
            return Ok(new { exito = true });
        }

    }

}
