using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/auth")]
    public class ApiAuthController : BaseApiController
    {
        private readonly IServicioAutenticacion _servicioAutenticacion;

        public ApiAuthController(IServicioAutenticacion servicioAutenticacion)
        {
            _servicioAutenticacion = servicioAutenticacion;
        }

        [HttpPost("login")]
        [AllowAnonymous]
        public async Task<IActionResult> Login([FromBody] LoginDto request)
        {
            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.Login(request.Correo, request.Contrasena);
            if (!exito || token == null || refreshToken == null)
            {
                return BadRequest(RespuestaApi<object>.Fallida(mensaje));
            }

            return Ok(RespuestaApi<object>.Exitosa(new { token, refreshToken }));
        }

        [HttpPost("refresh")]
        [AllowAnonymous]
        public async Task<IActionResult> Refresh([FromBody] RefreshDto request)
        {
            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.RenovarToken(request.RefreshToken);
            if (!exito || token == null || refreshToken == null)
            {
                return BadRequest(RespuestaApi<object>.Fallida(mensaje));
            }

            return Ok(RespuestaApi<object>.Exitosa(new { token, refreshToken }));
        }

        [HttpPost("logout")]
        [Authorize]
        public async Task<IActionResult> Logout()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioAutenticacion.CerrarSesion(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        public class LoginDto
        {
            public string Correo { get; set; } = string.Empty;
            public string Contrasena { get; set; } = string.Empty;
        }

        public class RefreshDto
        {
            public string RefreshToken { get; set; } = string.Empty;
        }
    }
}
