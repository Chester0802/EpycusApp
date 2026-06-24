using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class HabitosController : BaseController
    {
        private readonly IServicioHabitos _servicioHabitos;
        private readonly ILogger<HabitosController> _logger;

        public HabitosController(IServicioHabitos servicioHabitos, ILogger<HabitosController> logger)
        {
            _servicioHabitos = servicioHabitos;
            _logger = logger;
        }

        public async Task<IActionResult> Index()
        {
            var usuarioId = ObtenerUsuarioId();
            var lista = await _servicioHabitos.ObtenerHabitosViewModel(usuarioId);
            var dashboard = await _servicioHabitos.ObtenerDashboard(usuarioId);
            ViewBag.Categorias = await _servicioHabitos.ObtenerCategoriasActivas();

            return View(new HabitosIndexViewModel
            {
                Habitos = lista,
                Dashboard = dashboard
            });
        }

        public async Task<IActionResult> Crear()
        {
            ViewBag.Categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            return View(new CrearHabitoViewModel());
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Crear(CrearHabitoViewModel modelo)
        {
            modelo ??= new CrearHabitoViewModel();

            if (modelo.DiasSemana?.Any() != true)
            {
                var diasRaw = Request.Form["DiasSemana"].FirstOrDefault();
                if (!string.IsNullOrWhiteSpace(diasRaw))
                {
                    modelo.DiasSemana = diasRaw
                        .Split(',', StringSplitOptions.RemoveEmptyEntries)
                        .Select(s => int.Parse(s.Trim()))
                        .ToList();
                }
            }

            ViewBag.Categorias = await _servicioHabitos.ObtenerCategoriasActivas();

            if (!ModelState.IsValid)
            {
                if (EsAjax())
                {
                    return BadRequest(new { success = false, errors = ObtenerErroresModelo() });
                }

                ModelState.Remove("ConPomodoro");
                ModelState.Remove("EstaActivo");
                return View(modelo);
            }

            var usuarioId = ObtenerUsuarioId();

            try
            {
                await _servicioHabitos.CrearHabito(modelo, usuarioId);
            }
            catch (ArgumentException ex)
            {
                _logger.LogWarning(ex, "Categoria invalida al crear habito para usuario {UsuarioId}", usuarioId);
                ModelState.AddModelError(string.Empty, ex.Message);

                if (EsAjax())
                {
                    return BadRequest(new { success = false, errors = ObtenerErroresModelo() });
                }

                return View(modelo);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error al crear habito para usuario {UsuarioId}", usuarioId);
                ModelState.AddModelError(string.Empty, "Error inesperado al crear el habito. Intente de nuevo mas tarde.");

                if (EsAjax())
                {
                    return StatusCode(500, new { success = false, message = "Error inesperado al crear el habito." });
                }

                return View(modelo);
            }

            if (EsAjax())
            {
                return Json(new { success = true });
            }

            return RedirectToAction(nameof(Index));
        }

        public async Task<IActionResult> Editar(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            var habito = await _servicioHabitos.ObtenerPorId(id);
            if (habito is null || habito.UsuarioId != usuarioId)
            {
                return NotFound();
            }

            var vm = await _servicioHabitos.ObtenerPorIdViewModel(id);
            if (vm is null)
            {
                return NotFound();
            }

            ViewBag.Categorias = await _servicioHabitos.ObtenerCategoriasActivas();

            return View(new EditarHabitoViewModel
            {
                Id = vm.Id,
                Nombre = vm.Nombre,
                Descripcion = vm.Descripcion,
                CategoriaId = vm.CategoriaId,
                Frecuencia = vm.Frecuencia,
                DiasSemana = vm.DiasSemana,
                ConPomodoro = vm.ConPomodoro,
                RecordatorioHora = vm.RecordatorioHora,
                EstaActivo = vm.EstaActivo
            });
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Editar(EditarHabitoViewModel modelo)
        {
            if (modelo.DiasSemana?.Any() != true)
            {
                var diasRaw = Request.Form["DiasSemana"].FirstOrDefault();
                if (!string.IsNullOrWhiteSpace(diasRaw))
                {
                    modelo.DiasSemana = diasRaw
                        .Split(',', StringSplitOptions.RemoveEmptyEntries)
                        .Select(s => int.Parse(s.Trim()))
                        .ToList();
                }
            }

            ViewBag.Categorias = await _servicioHabitos.ObtenerCategoriasActivas();

            if (!ModelState.IsValid)
            {
                ModelState.Remove("ConPomodoro");
                ModelState.Remove("EstaActivo");
                return View(modelo);
            }

            var usuarioId = ObtenerUsuarioId();

            try
            {
                await _servicioHabitos.EditarHabito(modelo, usuarioId);
                return RedirectToAction(nameof(Index));
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error al editar habito {HabitoId} para usuario {UsuarioId}", modelo.Id, usuarioId);
                ModelState.AddModelError(string.Empty, "Error inesperado al guardar cambios.");
                return View(modelo);
            }
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Eliminar(int id)
        {
            await _servicioHabitos.EliminarHabito(id, ObtenerUsuarioId());
            return RedirectToAction(nameof(Index));
        }

        private bool EsAjax()
            => Request.Headers["X-Requested-With"] == "XMLHttpRequest"
               || Request.Headers.Accept.ToString().Contains("application/json");

        private Dictionary<string, string[]> ObtenerErroresModelo()
            => ModelState.Where(kvp => kvp.Value?.Errors.Any() == true)
                .ToDictionary(
                    kvp => kvp.Key,
                    kvp => kvp.Value!.Errors.Select(e => e.ErrorMessage).ToArray());


    }
}
