using System.Diagnostics;
using EpycusApp.Models;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    public class HomeController : BaseController
    {
        private readonly IServicioHabitos _servicioHabitos;
        private readonly IServicioPerfil _servicioPerfil;
        private readonly IServicioBienestar _servicioBienestar;

        public HomeController(
            IServicioHabitos servicioHabitos,
            IServicioPerfil servicioPerfil,
            IServicioBienestar servicioBienestar)
        {
            _servicioHabitos = servicioHabitos;
            _servicioPerfil = servicioPerfil;
            _servicioBienestar = servicioBienestar;
        }

        public async Task<IActionResult> Index()
        {
            var modelo = new HomeDashboardViewModel();

            var usuarioId = ObtenerUsuarioId();
            if (usuarioId != 0)
            {
                modelo.EstaAutenticado = true;

                var usuario = await _servicioPerfil.ObtenerPerfil(usuarioId);
                if (usuario != null)
                {
                    modelo.NombreUsuario = usuario.Nombre;
                }

                // Obtener datos del dashboard
                modelo.Estadisticas = await _servicioHabitos.ObtenerDashboard(usuarioId);

                // Obtener hÃ¡bitos de hoy
                var todosLosHabitos = await _servicioHabitos.ObtenerHabitosViewModel(usuarioId);
                var hoy = (int)DateTime.Today.DayOfWeek;

                modelo.HabitosHoy = todosLosHabitos
                    .Where(h => h.EstaActivo &&
                                (h.Frecuencia == "Diaria" ||
                                 (h.DiasSemana?.Contains(hoy) == true)))
                    .ToList();

                // Obtener frase motivacional
                var frase = await _servicioBienestar.ObtenerFraseMotivacionalAleatoria();

                if (frase != null)
                {
                    modelo.FraseMotivacional = frase.Frase;
                    modelo.AutorFrase = frase.Autor;
                }

                return View(modelo);
            }

            return RedirectToAction("Login", "Autenticacion");
        }

        public IActionResult Privacy()
        {
            return View();
        }

        [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
        public IActionResult Error()
        {
            return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
        }
    }
}
