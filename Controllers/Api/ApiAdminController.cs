using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/v1/admin")]
    [Authorize(Roles = "Admin")]
    [EnableRateLimiting("Api")]
    public class ApiAdminController : BaseApiController
    {
        private readonly IServicioAdmin _servicioAdmin;
        private readonly IServicioAutenticacion _servicioAutenticacion;

        public ApiAdminController(IServicioAdmin servicioAdmin, IServicioAutenticacion servicioAutenticacion)
        {
            _servicioAdmin = servicioAdmin;
            _servicioAutenticacion = servicioAutenticacion;
        }

        [AllowAnonymous]
        [HttpPost("login")]
        public async Task<IActionResult> Login([FromBody] AdminLoginRequest request)
        {
            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.Login(request.Correo, request.Contrasena);

            if (!exito)
                return Ok(RespuestaApi<MensajeResponseDto>.Fallida(mensaje));

            if (!await _servicioAdmin.EsAdministrador(request.Correo))
                return Ok(RespuestaApi<MensajeResponseDto>.Fallida("No tienes permisos de administrador"));

            return Ok(RespuestaApi<AdminLoginResponseDto>.Exitosa(new AdminLoginResponseDto { Token = token!, RefreshToken = refreshToken!, Mensaje = mensaje }));
        }

        [HttpGet("usuarios")]
        public async Task<IActionResult> Usuarios()
        {
            var usuarios = await _servicioAdmin.ObtenerTodosUsuarios();
            return Ok(RespuestaApi<object>.Exitosa(usuarios));
        }

        [HttpGet("usuarios/{id}")]
        public async Task<IActionResult> UsuarioPorId(int id)
        {
            var usuario = await _servicioAdmin.ObtenerUsuarioPorId(id);
            if (usuario == null)
                return NotFound(RespuestaApi<MensajeResponseDto>.Fallida("Usuario no encontrado"));
            return Ok(RespuestaApi<object>.Exitosa(usuario));
        }

        [HttpPost("usuarios/{usuarioId}/suscripcion/activar")]
        public async Task<IActionResult> ActivarSuscripcion(int usuarioId)
        {
            var adminId = ObtenerUsuarioId()!.Value;
            await _servicioAdmin.ActivarSuscripcion(usuarioId, adminId);
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpPost("usuarios/{usuarioId}/suscripcion/desactivar")]
        public async Task<IActionResult> DesactivarSuscripcion(int usuarioId)
        {
            await _servicioAdmin.DesactivarSuscripcion(usuarioId);
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpGet("frases")]
        public async Task<IActionResult> Frases()
        {
            var frases = await _servicioAdmin.ObtenerFrases();
            return Ok(RespuestaApi<object>.Exitosa(frases));
        }

        [HttpPost("frases")]
        public async Task<IActionResult> CrearFrase([FromBody] CrearFraseRequest request)
        {
            await _servicioAdmin.CrearFrase(request.Frase, request.Autor ?? "Anonimo");
            return Created(string.Empty, RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpDelete("frases/{id}")]
        public async Task<IActionResult> EliminarFrase(int id)
        {
            await _servicioAdmin.EliminarFrase(id);
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }
    }

    public class AdminLoginRequest
    {
        public string Correo { get; set; } = string.Empty;
        public string Contrasena { get; set; } = string.Empty;
    }

    public class CrearFraseRequest
    {
        public string Frase { get; set; } = string.Empty;
        public string? Autor { get; set; }
    }
}
