using System.Security.Claims;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class PerfilController : BaseController
    {
        private const string ClaveExitoPerfil     = "ExitoPerfil";
        private const string ClaveErrorPerfil     = "ErrorPerfil";
        private const string ClaveExitoContrasena = "ExitoContrasena";
        private const string ClaveErrorContrasena = "ErrorContrasena";

        private readonly IServicioPerfil _servicioPerfil;
        private readonly IServicioAutenticacion _servicioAutenticacion;

        public PerfilController(
            IServicioPerfil servicioPerfil,
            IServicioAutenticacion servicioAutenticacion)
        {
            _servicioPerfil         = servicioPerfil;
            _servicioAutenticacion  = servicioAutenticacion;
        }

        // â”€â”€ GET /Perfil â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpGet]
        public async Task<IActionResult> Index()
        {
            var usuarioId = ObtenerUsuarioId();

            var perfil = await _servicioPerfil.ObtenerPerfilCompletoAsync(usuarioId);
            if (perfil == null) return NotFound();

            perfil.PersonajesDisponibles =
                await _servicioPerfil.ObtenerPersonajesDisponiblesAsync(usuarioId);

            ViewBag.Logros = await _servicioPerfil.ObtenerLogrosUsuarioConLogroAsync(usuarioId);
            ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();

            return View(perfil);
        }

        // â”€â”€ POST /Perfil/ActualizarPerfil â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> ActualizarPerfil(ActualizarPerfilViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                TempData[ClaveErrorPerfil] = "Corrige los errores en el formulario.";
                return RedirectToAction(nameof(Index));
            }

            var resultado = await _servicioPerfil.ActualizarPerfilAsync(ObtenerUsuarioId(), modelo);
            TempData[resultado.EsExitoso ? ClaveExitoPerfil : ClaveErrorPerfil] =
                resultado.EsExitoso ? "Datos del perfil actualizados." : resultado.Mensaje;

            return RedirectToAction(nameof(Index));
        }

        // â”€â”€ POST /Perfil/CambiarContrasena â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> CambiarContrasena(CambiarContrasenaViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                TempData[ClaveErrorContrasena] = "Corrige los errores en el formulario.";
                return RedirectToAction(nameof(Index));
            }

            var correo    = User.FindFirstValue(ClaimTypes.Email)!;
            var resultado = await _servicioAutenticacion.CambiarContrasenaAsync(
                correo, modelo.ContrasenaActual, modelo.NuevaContrasena);

            TempData[resultado.EsExitoso ? ClaveExitoContrasena : ClaveErrorContrasena] =
                resultado.EsExitoso ? "ContraseÃ±a actualizada correctamente." : resultado.Mensaje;

            return RedirectToAction(nameof(Index));
        }

        // â”€â”€ POST /Perfil/CambiarPersonaje â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> CambiarPersonaje(int personajeId)
        {
            if (!ModelState.IsValid)
                return RedirectToAction(nameof(Index));

            await _servicioPerfil.CambiarPersonaje(personajeId, ObtenerUsuarioId());
            TempData[ClaveExitoPerfil] = "Personaje actualizado correctamente.";
            return RedirectToAction(nameof(Index));
        }

        // â”€â”€ POST /api/perfil/tema  (AJAX) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        [HttpPost("/api/perfil/tema")]
        public async Task<IActionResult> CambiarTema([FromBody] CambiarTemaDto? dto)
        {
            if (!ModelState.IsValid || dto == null)
                return BadRequest(new { exito = false });

            var resultado = await _servicioPerfil.CambiarTemaAsync(ObtenerUsuarioId(), dto.TemaId);
            return Ok(new { exito = resultado.EsExitoso, mensaje = resultado.Mensaje });
        }

    }

    public sealed class CambiarTemaDto
    {
        public int TemaId { get; set; }
    }
}
