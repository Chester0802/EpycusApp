using System.Diagnostics;
using System.Security.Claims;
using EPYCUS_WEB_v0._1.Models;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;
using Microsoft.AspNetCore.Mvc;
using EPYCUS_WEB_v0._1.Datos;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Controllers
{
    public class HomeController : Controller
    {
        private readonly IServicioHabitos _servicioHabitos;
        private readonly ContextoAplicacion _contexto;

        public HomeController(IServicioHabitos servicioHabitos, ContextoAplicacion contexto)
        {
            _servicioHabitos = servicioHabitos;
            _contexto = contexto;
        }

        public async Task<IActionResult> Index()
        {
            var modelo = new HomeDashboardViewModel();

            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            int usuarioId = 0;
            
            if (User.Identity != null && User.Identity.IsAuthenticated && int.TryParse(claim, out var parsed) && parsed != 0)
            {
                usuarioId = parsed;
                modelo.EstaAutenticado = true;
                
                var usuario = await _contexto.Usuarios.FindAsync(usuarioId);
                if (usuario != null)
                {
                    modelo.NombreUsuario = usuario.Nombre;
                }

                // Obtener datos del dashboard
                modelo.Estadisticas = await _servicioHabitos.ObtenerDashboard(usuarioId);
                
                // Obtener hábitos de hoy
                var todosLosHabitos = await _servicioHabitos.ObtenerHabitosViewModel(usuarioId);
                var hoy = (int)DateTime.Today.DayOfWeek;
                
                modelo.HabitosHoy = todosLosHabitos
                    .Where(h => h.EstaActivo && 
                                (h.Frecuencia == "Diaria" || 
                                 (h.DiasSemana != null && h.DiasSemana.Contains(hoy))))
                    .ToList();

                // Obtener frase motivacional
                var frase = await _contexto.FrasesMotivacionales
                    .Where(f => f.EstaActiva)
                    .OrderBy(f => Guid.NewGuid())
                    .FirstOrDefaultAsync();

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
