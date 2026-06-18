using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Linq;
using System.Threading.Tasks;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class ProgresoController : BaseController
    {
        private readonly IServicioProgreso _servicioProgreso;

        public ProgresoController(IServicioProgreso servicioProgreso)
        {
            _servicioProgreso = servicioProgreso;
        }

        [AllowAnonymous]
        public async Task<IActionResult> Index()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId == 0)
            {
                // Usuario anónimo o sin sesión
                var nivelInicial = await _servicioProgreso.ObtenerNivelInicialAsync();
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
