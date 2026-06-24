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
    [Route("api/perfil")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
    public class ApiPerfilController : BaseApiController
    {
        private readonly IServicioPerfil _servicioPerfil;
        private readonly IServicioAutenticacion _servicioAutenticacion;

        public ApiPerfilController(IServicioPerfil servicioPerfil, IServicioAutenticacion servicioAutenticacion)
        {
            _servicioPerfil = servicioPerfil;
            _servicioAutenticacion = servicioAutenticacion;
        }

        [HttpGet]
        public async Task<IActionResult> Obtener()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var perfil = await _servicioPerfil.ObtenerPerfilCompletoAsync(usuarioId);
            if (perfil == null)
            {
                return NotFound(RespuestaApi<MensajeResponseDto>.Fallida("Perfil no encontrado"));
            }

            var imagenPersonaje = ConvertirUrlAbsoluta(await _servicioPerfil.ObtenerImagenPersonajeActual(usuarioId));

            return Ok(RespuestaApi<object>.Exitosa(new
            {
                perfil,
                imagenPersonaje
            }));
        }

        [HttpPut]
        public async Task<IActionResult> Actualizar([FromBody] ActualizarRequestDto request)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;

            var modelo = new ActualizarPerfilViewModel
            {
                Nombre = request.Nombre,
                CarreraId = request.CarreraId
            };

            var resultado = await _servicioPerfil.ActualizarPerfilAsync(usuarioId, modelo);
            if (!resultado.EsExitoso)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(resultado.Mensaje ?? "Error al actualizar perfil"));
            }

            return Ok(RespuestaApi<MensajeResponseDto>.Exitosa(new MensajeResponseDto { Mensaje = resultado.Mensaje ?? "Perfil actualizado" }));
        }

        [HttpPut("cambiar-contrasena")]
        public async Task<IActionResult> CambiarContrasena([FromBody] CambiarContrasenaRequestDto request)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var perfil = await _servicioPerfil.ObtenerPerfil(usuarioId);
            if (perfil == null)
            {
                return NotFound(RespuestaApi<MensajeResponseDto>.Fallida("Usuario no encontrado"));
            }

            var (exito, mensaje) = await _servicioAutenticacion.CambiarContrasenaAsync(
                perfil.CorreoElectronico, request.ContrasenaActual, request.NuevaContrasena);
            if (!exito)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(mensaje ?? "Error al cambiar contraseña"));
            }

            return Ok(RespuestaApi<MensajeResponseDto>.Exitosa(new MensajeResponseDto { Mensaje = mensaje ?? "Contraseña cambiada exitosamente" }));
        }

        [HttpPut("personaje")]
        public async Task<IActionResult> CambiarPersonaje([FromBody] PersonajeRequestDto request)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioPerfil.CambiarPersonaje(request.PersonajeId, usuarioId);
            var nuevaImagen = ConvertirUrlAbsoluta(await _servicioPerfil.ObtenerImagenPersonajeActual(usuarioId));

            return Ok(RespuestaApi<object>.Exitosa(new { imagenUrl = nuevaImagen }));
        }

        [HttpPut("tema")]
        public async Task<IActionResult> CambiarTema([FromBody] TemaRequestDto request)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var resultado = await _servicioPerfil.CambiarTemaAsync(usuarioId, request.TemaId);
            if (!resultado.EsExitoso)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(resultado.Mensaje ?? "Error al cambiar tema"));
            }

            return Ok(RespuestaApi<MensajeResponseDto>.Exitosa(new MensajeResponseDto { Mensaje = resultado.Mensaje ?? "Tema actualizado" }));
        }

        [HttpGet("personajes")]
        public async Task<IActionResult> Personajes()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var personajes = await _servicioPerfil.ObtenerPersonajesDisponiblesAsync(usuarioId);
            return Ok(RespuestaApi<List<PersonajePerfilItem>>.Exitosa(personajes));
        }

        [HttpGet("logros")]
        public async Task<IActionResult> Logros()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var logros = await _servicioPerfil.ObtenerLogrosUsuarioConLogroAsync(usuarioId);
            return Ok(RespuestaApi<List<LogroUsuario>>.Exitosa(logros));
        }

        public class ActualizarRequestDto
        {
            public string Nombre { get; set; } = string.Empty;
            public int? CarreraId { get; set; }
        }

        public class CambiarContrasenaRequestDto
        {
            public string ContrasenaActual { get; set; } = string.Empty;
            public string NuevaContrasena { get; set; } = string.Empty;
        }

        public class PersonajeRequestDto
        {
            public int PersonajeId { get; set; }
        }

        public class TemaRequestDto
        {
            public int TemaId { get; set; }
        }
    }
}
