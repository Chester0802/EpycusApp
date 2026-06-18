using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class BienestarController : BaseController
    {
        private readonly IServicioBienestar _servicioBienestar;

        public BienestarController(IServicioBienestar servicioBienestar)
        {
            _servicioBienestar = servicioBienestar;
        }

        public async Task<IActionResult> Index()
        {
            var usuarioId = ObtenerUsuarioId();

            var modelo = new BienestarViewModel
            {
                EstadoHoy          = await _servicioBienestar.ObtenerEstadoHoy(usuarioId),
                Alertas            = await _servicioBienestar.ObtenerAlertasActivas(usuarioId),
                FraseMotivacional  = await _servicioBienestar.ObtenerFraseMotivacionalAleatoria(),
                HistorialAnimo     = await _servicioBienestar.ObtenerHistorialAnimo(usuarioId, 14),
                HabitosPendientes  = await _servicioBienestar.ObtenerHabitosPendientesAsync(usuarioId),
                MisionesPendientes = await _servicioBienestar.ObtenerMisionesPendientesAsync(usuarioId),
                RecomendacionActiva = _servicioBienestar.RecomendacionPausaActiva(0)
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

            TempData["Exito"] = $"Estado de ánimo registrado: {estado}";
            return RedirectToAction(nameof(Index));
        }
    }
}
