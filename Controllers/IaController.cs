using System.Security.Claims;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels.Ia;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EPYCUS_WEB_v0._1.Controllers
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

        // ── GET /ia  o  /ia?conv={guid} ──────────────────────────────────────
        [HttpGet]
        public async Task<IActionResult> Index(string? conv)
        {
            // Sin conversación → generar nueva y redirigir
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

        // ── POST /ia/nueva  (botón "Nueva conversación") ─────────────────────
        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Nueva()
        {
            return RedirectToAction(nameof(Index));
        }

        // ── POST /api/ia/chat  (llamada AJAX) ─────────────────────────────────
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
                return Ok(new { exito = false, error = "No pude conectarme con EDY. Inténtalo de nuevo." });
            }
        }

        // ── Helper ────────────────────────────────────────────────────────────
        private int ObtenerUsuarioId()
            => int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
    }

    public sealed class ChatMensajeDto
    {
        public string Mensaje { get; set; } = string.Empty;
        public string ConversacionId { get; set; } = string.Empty;
    }
}
