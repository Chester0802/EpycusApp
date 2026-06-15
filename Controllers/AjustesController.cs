using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using System.Security.Claims;

namespace EpycusApp.Controllers;

[Authorize]
public class AjustesController : Controller
{
    private readonly IServicioPerfil _servicioPerfil;
    private readonly IServicioAutenticacion _servicioAutenticacion;

    public AjustesController(IServicioPerfil servicioPerfil, IServicioAutenticacion servicioAutenticacion)
    {
        _servicioPerfil = servicioPerfil;
        _servicioAutenticacion = servicioAutenticacion;
    }

    public async Task<IActionResult> Index()
    {
        var usuarioId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var perfil = await _servicioPerfil.ObtenerPerfilCompletoAsync(usuarioId);

        if (perfil == null)
        {
            return NotFound();
        }

        perfil.PersonajesDisponibles = await _servicioPerfil.ObtenerPersonajesDisponiblesAsync(usuarioId);
        ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();
        return View(perfil);
    }

    [HttpPost]
    public async Task<IActionResult> ActualizarPerfil(ActualizarPerfilViewModel modelo)
    {
        if (!ModelState.IsValid)
        {
            TempData["Error"] = string.Join("; ", ModelState.Values
                .SelectMany(v => v.Errors)
                .Select(e => e.ErrorMessage));
            return RedirectToAction(nameof(Index));
        }

        var usuarioId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var resultado = await _servicioPerfil.ActualizarPerfilAsync(usuarioId, modelo);

        if (resultado.EsExitoso)
        {
            TempData["Mensaje"] = "Perfil actualizado correctamente.";
        }
        else
        {
            TempData["Error"] = resultado.Mensaje ?? "No se pudo actualizar el perfil.";
        }

        return RedirectToAction(nameof(Index));
    }

    [HttpPost]
    public async Task<IActionResult> CambiarContrasena(CambiarContrasenaViewModel modelo)
    {
        if (!ModelState.IsValid)
        {
            TempData["Error"] = "Por favor verifica los datos ingresados.";
            return RedirectToAction(nameof(Index));
        }

        var usuarioId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var correo = User.FindFirstValue(ClaimTypes.Email);

        var resultado = await _servicioAutenticacion.CambiarContrasenaAsync(
            correo!,
            modelo.ContrasenaActual,
            modelo.NuevaContrasena
        );

        if (resultado.EsExitoso)
        {
            TempData["Mensaje"] = "ContraseÃ±a cambiada correctamente.";
        }
        else
        {
            TempData["Error"] = resultado.Mensaje ?? "No se pudo cambiar la contraseÃ±a.";
        }

        return RedirectToAction(nameof(Index));
    }

    [HttpPost]
    public async Task<IActionResult> CambiarPersonaje(int personajeId)
    {
        var usuarioId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        await _servicioPerfil.CambiarPersonaje(personajeId, usuarioId);
        var nuevaImagen = await _servicioPerfil.ObtenerImagenPersonajeActual(usuarioId);
        return Json(new { exito = true, imagenUrl = nuevaImagen });
    }

    [HttpPost]
    public async Task<IActionResult> CambiarTema(int temaId)
    {
        var usuarioId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var resultado = await _servicioPerfil.CambiarTemaAsync(usuarioId, temaId);

        if (resultado.EsExitoso)
        {
            return Json(new { exito = true, mensaje = "Tema actualizado correctamente." });
        }

        return Json(new { exito = false, mensaje = resultado.Mensaje ?? "No se pudo cambiar el tema." });
    }
}
