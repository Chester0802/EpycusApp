using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Rendering;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class MisionesController : BaseController
    {
        private readonly IServicioMisiones _servicioMisiones;

        public MisionesController(IServicioMisiones servicioMisiones)
        {
            _servicioMisiones = servicioMisiones;
        }

        public async Task<IActionResult> Index()
        {
            var misiones = await _servicioMisiones.ObtenerMisionesDeUsuario(ObtenerUsuarioId());
            return View(misiones);
        }

        public async Task<IActionResult> Crear()
        {
            await CargarCategorias();
            return View(new CrearMisionViewModel { FechaLimite = DateTime.Today.AddDays(1) });
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Crear(CrearMisionViewModel modelo)
        {
            if (ModelState.IsValid)
            {
                await _servicioMisiones.CrearMision(modelo, ObtenerUsuarioId());
                return RedirectToAction(nameof(Index));
            }

            await CargarCategorias();
            return View(modelo);
        }

        public async Task<IActionResult> Editar(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            var mision = await _servicioMisiones.ObtenerPorId(id);
            if (mision == null || mision.UsuarioId != usuarioId) return NotFound();

            if (mision.Estado == "Completado" || mision.Estado == "Fallido")
            {
                TempData["Error"] = "No se puede editar una mision completada o fallida.";
                return RedirectToAction(nameof(Index));
            }

            var modelo = new EditarMisionViewModel
            {
                Id = mision.Id,
                Nombre = mision.Nombre,
                Descripcion = mision.Descripcion,
                NombreCurso = mision.NombreCurso,
                FechaLimite = mision.FechaLimite.ToDateTime(TimeOnly.MinValue),
                Prioridad = mision.Prioridad,
                ConPomodoro = mision.ConPomodoro,
                CategoriaId = mision.CategoriaId
            };

            await CargarCategorias();
            return View(modelo);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Editar(EditarMisionViewModel modelo)
        {
            if (ModelState.IsValid)
            {
                try
                {
                    await _servicioMisiones.EditarMision(modelo, ObtenerUsuarioId());
                    return RedirectToAction(nameof(Index));
                }
                catch (Exception ex)
                {
                    ModelState.AddModelError("", ex.Message);
                }
            }

            await CargarCategorias();
            return View(modelo);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> CambiarEstado(int id, string estado)
        {
            await _servicioMisiones.CambiarEstado(id, estado, ObtenerUsuarioId());
            return RedirectToAction(nameof(Index));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Completar(int id)
        {
            var resultado = await _servicioMisiones.CompletarMision(id, ObtenerUsuarioId());
            if (resultado.Exito)
            {
                TempData["Exito"] = $"Mision completada. Ganaste {resultado.XpGanado} XP.";
            }
            else
            {
                TempData["Error"] = "No se pudo completar la mision.";
            }

            return RedirectToAction(nameof(Index));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Eliminar(int id)
        {
            await _servicioMisiones.EliminarMision(id, ObtenerUsuarioId());
            return RedirectToAction(nameof(Index));
        }

        private async Task CargarCategorias()
        {
            var categorias = await _servicioMisiones.ObtenerCategoriasMisionAsync();
            ViewBag.Categorias = new SelectList(categorias, "Id", "Nombre");
        }
    }
}
