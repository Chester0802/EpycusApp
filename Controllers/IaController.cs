using System.Security.Claims;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Ia;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class IaController : Controller
    {
        private readonly IServicioIA _servicioIA;
        private readonly ILogger<IaController> _logger;

        public IaController(IServicioIA servicioIA, ILogger<IaController> logger)
        {
            _servicioIA = servicioIA;
            _logger = logger;
        }

        // â”€â”€ GET /ia  o  /ia?conv={guid} â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpGet]
        public async Task<IActionResult> Index(string? conv)
        {
            // Sin conversaciÃ³n â†’ generar nueva y redirigir
            if (string.IsNullOrWhiteSpace(conv))
            {
                var nuevaId = _servicioIA.NuevaConversacionId();
                return RedirectToAction(nameof(Index), new { conv = nuevaId });
            }

            var usuarioId = ObtenerUsuarioId();
            var mensajes  = await _servicioIA.ObtenerHistorialAsync(usuarioId, conv);

            return View(new IaChatViewModel
            {
                ConversacionId = conv,
                Mensajes       = mensajes
            });
        }

        // â”€â”€ POST /ia/nueva  (botÃ³n "Nueva conversaciÃ³n") â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Nueva()
        {
            return RedirectToAction(nameof(Index));
        }

        // â”€â”€ POST /api/ia/chat  (llamada AJAX) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpPost("/api/ia/chat")]
        public async Task<IActionResult> Chat([FromBody] ChatMensajeDto? dto)
        {
            if (dto == null
                || string.IsNullOrWhiteSpace(dto.Mensaje)
                || string.IsNullOrWhiteSpace(dto.ConversacionId))
            {
                return BadRequest(new { exito = false, error = "Datos incompletos." });
            }

            var usuarioId = ObtenerUsuarioId();

            try
            {
                var respuesta = await _servicioIA.ChatAsync(
                    usuarioId,
                    dto.Mensaje.Trim(),
                    dto.ConversacionId);

                return Ok(new { exito = true, respuesta });
            }
            catch (UnauthorizedAccessException)
            {
                return Forbid();
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error en chat IA para usuario {UsuarioId}", usuarioId);
                return Ok(new { exito = false, error = "No pude conectarme con EDY. IntÃ©ntalo de nuevo." });
            }
        }

        // â”€â”€ Helper â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        private int ObtenerUsuarioId()
            => int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
    }

    public sealed class ChatMensajeDto
    {
        public string Mensaje { get; set; } = string.Empty;
        public string ConversacionId { get; set; } = string.Empty;
    }
}
