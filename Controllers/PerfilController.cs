using System.Security.Claims;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EPYCUS_WEB_v0._1.Controllers
{
    [Authorize]
    public class PerfilController : Controller
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

        // ── GET /Perfil ───────────────────────────────────────────────────────
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

        // ── POST /Perfil/ActualizarPerfil ─────────────────────────────────────
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

        // ── POST /Perfil/CambiarContrasena ────────────────────────────────────
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
                resultado.EsExitoso ? "Contraseña actualizada correctamente." : resultado.Mensaje;

            return RedirectToAction(nameof(Index));
        }

        // ── POST /Perfil/CambiarPersonaje ─────────────────────────────────────
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

        // ── POST /api/perfil/tema  (AJAX) ─────────────────────────────────────
        [HttpPost("/api/perfil/tema")]
        public async Task<IActionResult> CambiarTema([FromBody] CambiarTemaDto? dto)
        {
            if (!ModelState.IsValid || dto == null)
                return BadRequest(new { exito = false });

            var resultado = await _servicioPerfil.CambiarTemaAsync(ObtenerUsuarioId(), dto.TemaId);
            return Ok(new { exito = resultado.EsExitoso, mensaje = resultado.Mensaje });
        }

        // ── Helper ────────────────────────────────────────────────────────────
        private int ObtenerUsuarioId()
            => int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
    }

    public sealed class CambiarTemaDto
    {
        public int TemaId { get; set; }
    }
}
