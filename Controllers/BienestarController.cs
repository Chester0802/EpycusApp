using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class BienestarController : Controller
    {
        private readonly IServicioBienestar _servicioBienestar;

        public BienestarController(IServicioBienestar servicioBienestar)
        {
            _servicioBienestar = servicioBienestar;
        }

        private int ObtenerUsuarioId() =>
            int.Parse(User.FindFirst(ClaimTypes.NameIdentifier)!.Value);

        public async Task<IActionResult> Index()
        {
            var usuarioId = ObtenerUsuarioId();

            var modelo = new BienestarViewModel
            {
                EstadoHoy    = await _servicioBienestar.ObtenerEstadoHoy(usuarioId),
                Alertas      = await _servicioBienestar.ObtenerAlertasActivas(usuarioId),
                FraseMotivacional = await _servicioBienestar.ObtenerFraseMotivacionalAleatoria(),
                HistorialAnimo    = await _servicioBienestar.ObtenerHistorialAnimo(usuarioId, 14)
            };

            return View(modelo);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> RegistrarAnimo(string estado, string? nota)
        {
            if (string.IsNullOrWhiteSpace(estado))
                return RedirectToAction(nameof(Index));

            var usuarioId = ObtenerUsuarioId();
            await _servicioBienestar.RegistrarEstadoAnimo(usuarioId, estado, nota);

            TempData["AnimoRegistrado"] = estado;
            return RedirectToAction(nameof(Index));
        }
    }
}
