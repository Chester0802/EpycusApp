using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Linq;
using System.Security.Claims;
using System.Threading.Tasks;

namespace EPYCUS_WEB_v0._1.Controllers
{
    [Authorize]
    public class ProgresoController : Controller
    {
        private readonly IServicioProgreso _servicioProgreso;
        private readonly ContextoAplicacion _contexto;

        public ProgresoController(IServicioProgreso servicioProgreso, ContextoAplicacion contexto)
        {
            _servicioProgreso = servicioProgreso;
            _contexto = contexto;
        }

        [AllowAnonymous]
        public async Task<IActionResult> Index()
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            if (string.IsNullOrEmpty(claim) || !int.TryParse(claim, out var usuarioId))
            {
                // Usuario anónimo o sin sesión
                var nivelInicial = _contexto.Niveles.OrderBy(n => n.Numero).FirstOrDefault();
                var progresoDefecto = new ProgresoUsuario
                {
                    UsuarioId = 0,
                    NivelActual = nivelInicial ?? new Nivel { Numero = 1, Descripcion = "Novato", Titulo = "Principiante" },
                    NivelActualId = nivelInicial?.Id ?? 1,
                    XpTotal = 0,
                    RachaActual = 0,
                    RachaMaxima = 0
                };

                return View(new ProgresoViewModel
                {
                    Progreso = progresoDefecto,
                    NivelSiguiente = await _servicioProgreso.ObtenerNivelSiguiente(progresoDefecto.NivelActual.Numero),
                    TodosLosLogros = await _servicioProgreso.ObtenerTodosLosLogros(),
                    LogrosDesbloqueadosIds = new System.Collections.Generic.List<int>(),
                    ImagenPersonajeUrl = "https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff&size=200"
                });
            }

            var progreso = await _servicioProgreso.ObtenerProgreso(usuarioId);
            var nivelSiguiente = await _servicioProgreso.ObtenerNivelSiguiente(progreso.NivelActual.Numero);
            var todosLosLogros = await _servicioProgreso.ObtenerTodosLosLogros();
            var logrosUsuario = await _servicioProgreso.ObtenerLogrosUsuario(usuarioId);
            var imagenUrl = await _servicioProgreso.ObtenerImagenPersonaje(usuarioId, progreso.NivelActual.Numero);

            var viewModel = new ProgresoViewModel
            {
                Progreso = progreso,
                NivelSiguiente = nivelSiguiente,
                TodosLosLogros = todosLosLogros,
                LogrosDesbloqueadosIds = logrosUsuario.Select(l => l.LogroId).ToList(),
                ImagenPersonajeUrl = imagenUrl
            };

            return View(viewModel);
        }
    }
}
