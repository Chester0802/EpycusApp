using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/ia")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
    public class ApiIaController : BaseApiController
    {
        private readonly IServicioIA _servicioIA;

        public ApiIaController(IServicioIA servicioIA)
        {
            _servicioIA = servicioIA;
        }

        [HttpPost("chat")]
        public async Task<IActionResult> Chat([FromBody] ChatRequest request)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var conversacionId = string.IsNullOrWhiteSpace(request.ConversacionId)
                ? _servicioIA.NuevaConversacionId()
                : request.ConversacionId;
            var respuesta = await _servicioIA.ChatAsync(usuarioId, request.Mensaje, conversacionId);
            return Ok(RespuestaApi<object>.Exitosa(new { respuesta, conversacionId }));
        }

        [HttpGet("historial")]
        public async Task<IActionResult> Historial([FromQuery] string conversacionId)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var historial = await _servicioIA.ObtenerHistorialAsync(usuarioId, conversacionId);
            return Ok(RespuestaApi<object>.Exitosa(historial));
        }

        [HttpGet("conversaciones")]
        public async Task<IActionResult> Conversaciones()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var conversaciones = await _servicioIA.ObtenerConversacionesAsync(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(conversaciones));
        }

        [HttpGet("sugerencias")]
        public async Task<IActionResult> Sugerencias()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var sugerencias = await _servicioIA.ObtenerSugerenciasPersonalizadasAsync(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(sugerencias));
        }

        [HttpGet("contexto-bienestar")]
        public async Task<IActionResult> ContextoBienestar()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var contexto = await _servicioIA.ObtenerBienestarContextoAsync(usuarioId);
            return Ok(RespuestaApi<object?>.Exitosa(contexto));
        }

        [HttpPost("feedback")]
        public async Task<IActionResult> Feedback([FromBody] FeedbackRequest request)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioIA.RegistrarFeedbackAsync(usuarioId, request.MensajeId, request.Util);
            return Ok(RespuestaApi<object>.Exitosa(new { }));
        }

        [HttpGet("mensajes-hoy")]
        public async Task<IActionResult> MensajesHoy()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var cantidad = await _servicioIA.ObtenerMensajesHoyAsync(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { cantidad }));
        }
    }

    public class ChatRequest
    {
        public string Mensaje { get; set; } = string.Empty;
        public string? ConversacionId { get; set; }
    }

    public class FeedbackRequest
    {
        public int MensajeId { get; set; }
        public bool Util { get; set; }
    }
}
