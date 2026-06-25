using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/v1/auth")]
    [EnableRateLimiting("Auth")]
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
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(mensaje));
            }

            return Ok(RespuestaApi<AuthResponseDto>.Exitosa(new AuthResponseDto { Token = token, RefreshToken = refreshToken, Mensaje = mensaje }));
        }

        [HttpPost("refresh")]
        [AllowAnonymous]
        public async Task<IActionResult> Refresh([FromBody] RefreshDto request)
        {
            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.RenovarToken(request.RefreshToken);
            if (!exito || token == null || refreshToken == null)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(mensaje));
            }

            return Ok(RespuestaApi<AuthResponseDto>.Exitosa(new AuthResponseDto { Token = token, RefreshToken = refreshToken, Mensaje = mensaje }));
        }

        [HttpPost("logout")]
        [Authorize]
        public async Task<IActionResult> Logout()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioAutenticacion.CerrarSesion(usuarioId);
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpPost("registro")]
        [AllowAnonymous]
        public async Task<IActionResult> Registro([FromBody] RegistroRequestDto request)
        {
            var modelo = new RegistroViewModel
            {
                Nombre = request.Nombre,
                CorreoElectronico = request.CorreoElectronico,
                Contrasena = request.Contrasena,
                ConfirmarContrasena = request.ConfirmarContrasena,
                FechaNacimiento = request.FechaNacimiento,
                Genero = request.Genero,
                CarreraId = request.CarreraId,
                AceptoTerminos = request.AceptoTerminos
            };

            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.RegistrarUsuario(modelo);
            if (!exito || token == null || refreshToken == null)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(mensaje));
            }

            return Ok(RespuestaApi<AuthResponseDto>.Exitosa(new AuthResponseDto { Token = token, RefreshToken = refreshToken, Mensaje = mensaje }));
        }

        [HttpGet("verificar-correo")]
        [AllowAnonymous]
        public async Task<IActionResult> VerificarCorreo([FromQuery] string token)
        {
            var resultado = await _servicioAutenticacion.VerificarCorreo(token);
            if (!resultado)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("No se pudo verificar el correo"));
            }

            return Ok(RespuestaApi<MensajeResponseDto>.Exitosa(new MensajeResponseDto { Mensaje = "Correo verificado exitosamente" }));
        }

        [HttpPost("recuperar-contrasena")]
        [AllowAnonymous]
        public async Task<IActionResult> RecuperarContrasena([FromBody] RecuperarContrasenaDto request)
        {
            var resultado = await _servicioAutenticacion.EnviarCorreoRecuperacion(request.Correo);
            if (!resultado)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("No se pudo enviar el correo de recuperación"));
            }

            return Ok(RespuestaApi<MensajeResponseDto>.Exitosa(new MensajeResponseDto { Mensaje = "Correo de recuperación enviado" }));
        }

        [HttpPost("restablecer-contrasena")]
        [AllowAnonymous]
        public async Task<IActionResult> RestablecerContrasena([FromBody] RestablecerContrasenaDto request)
        {
            var resultado = await _servicioAutenticacion.RestablecerContrasena(request.Token, request.NuevaContrasena);
            if (!resultado)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("No se pudo restablecer la contraseña"));
            }

            return Ok(RespuestaApi<MensajeResponseDto>.Exitosa(new MensajeResponseDto { Mensaje = "Contraseña restablecida exitosamente" }));
        }

        [HttpPost("google")]
        [AllowAnonymous]
        public async Task<IActionResult> GoogleAuth([FromBody] GoogleAuthDto request)
        {
            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.ProcesarAutenticacionGoogleAsync(
                request.GoogleId, request.Correo, request.Nombre, request.FotoUrl);
            if (!exito || token == null || refreshToken == null)
            {
                return BadRequest(RespuestaApi<object>.Fallida(mensaje));
            }

            return Ok(RespuestaApi<AuthResponseDto>.Exitosa(new AuthResponseDto { Token = token, RefreshToken = refreshToken, Mensaje = mensaje }));
        }

        [HttpPost("completar-registro-google")]
        [AllowAnonymous]
        public async Task<IActionResult> CompletarRegistroGoogle([FromBody] CompletarRegistroGoogleDto request)
        {
            var modelo = new CompletarRegistroGoogleViewModel
            {
                Nombre = request.Nombre,
                CorreoElectronico = request.CorreoElectronico,
                FechaNacimiento = request.FechaNacimiento,
                Genero = request.Genero,
                CarreraId = request.CarreraId,
                AceptoTerminos = request.AceptoTerminos,
                GoogleId = request.GoogleId,
                FotoGoogleUrl = request.FotoGoogleUrl
            };

            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.CompletarRegistroGoogleAsync(modelo);
            if (!exito || token == null || refreshToken == null)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(mensaje));
            }

            return Ok(RespuestaApi<AuthResponseDto>.Exitosa(new AuthResponseDto { Token = token, RefreshToken = refreshToken, Mensaje = mensaje }));
        }

        [HttpGet("carreras")]
        [AllowAnonymous]
        public async Task<IActionResult> Carreras()
        {
            var carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();
            return Ok(RespuestaApi<List<Carrera>>.Exitosa(carreras));
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

        public class RegistroRequestDto
        {
            public string Nombre { get; set; } = string.Empty;
            public string CorreoElectronico { get; set; } = string.Empty;
            public string Contrasena { get; set; } = string.Empty;
            public string ConfirmarContrasena { get; set; } = string.Empty;
            public DateOnly FechaNacimiento { get; set; }
            public string Genero { get; set; } = string.Empty;
            public int CarreraId { get; set; }
            public bool AceptoTerminos { get; set; }
        }

        public class RecuperarContrasenaDto
        {
            public string Correo { get; set; } = string.Empty;
        }

        public class RestablecerContrasenaDto
        {
            public string Token { get; set; } = string.Empty;
            public string NuevaContrasena { get; set; } = string.Empty;
        }

        public class GoogleAuthDto
        {
            public string GoogleId { get; set; } = string.Empty;
            public string Correo { get; set; } = string.Empty;
            public string Nombre { get; set; } = string.Empty;
            public string? FotoUrl { get; set; }
        }

        public class CompletarRegistroGoogleDto
        {
            public string Nombre { get; set; } = string.Empty;
            public string CorreoElectronico { get; set; } = string.Empty;
            public DateOnly FechaNacimiento { get; set; }
            public string Genero { get; set; } = string.Empty;
            public int CarreraId { get; set; }
            public bool AceptoTerminos { get; set; }
            public string GoogleId { get; set; } = string.Empty;
            public string? FotoGoogleUrl { get; set; }
        }
    }
}
