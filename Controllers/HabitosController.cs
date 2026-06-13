using System.Security.Claims;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EPYCUS_WEB_v0._1.Controllers
{
    [Authorize]
    public class HabitosController : Controller
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
            CompletarModeloDesdeFormulario(modelo);

            ViewBag.Categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            TryValidateModel(modelo);

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
            CompletarDiasSemanaDesdeFormulario(modelo);
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

        private int ObtenerUsuarioId()
            => int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);

        private bool EsAjax()
            => Request.Headers["X-Requested-With"] == "XMLHttpRequest"
               || Request.Headers.Accept.ToString().Contains("application/json");

        private Dictionary<string, string[]> ObtenerErroresModelo()
            => ModelState.Where(kvp => kvp.Value?.Errors.Any() == true)
                .ToDictionary(
                    kvp => kvp.Key,
                    kvp => kvp.Value!.Errors.Select(e => e.ErrorMessage).ToArray());

        private void CompletarModeloDesdeFormulario(CrearHabitoViewModel modelo)
        {
            var nombreForm = Request.Form["Nombre"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(nombreForm)) modelo.Nombre = nombreForm;

            var descForm = Request.Form["Descripcion"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(descForm)) modelo.Descripcion = descForm;

            var catForm = Request.Form["CategoriaId"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(catForm) && int.TryParse(catForm, out var catId)) modelo.CategoriaId = catId;

            var freqForm = Request.Form["Frecuencia"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(freqForm)) modelo.Frecuencia = freqForm;

            CompletarDiasSemanaDesdeFormulario(modelo);

            modelo.ConPomodoro = EsCheckboxMarcado("ConPomodoro");
            modelo.EstaActivo = EsCheckboxMarcado("EstaActivo");

            var horaForm = Request.Form["RecordatorioHora"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(horaForm) && TimeSpan.TryParse(horaForm, out var ts))
            {
                modelo.RecordatorioHora = ts;
            }
        }

        private void CompletarDiasSemanaDesdeFormulario(CrearHabitoViewModel modelo)
        {
            var diasRaw = Request.Form["DiasSemana"].FirstOrDefault();
            if (string.IsNullOrWhiteSpace(diasRaw) || modelo.DiasSemana?.Any() == true)
            {
                return;
            }

            try
            {
                modelo.DiasSemana = diasRaw.Split(',', StringSplitOptions.RemoveEmptyEntries)
                    .Select(s => int.Parse(s.Trim()))
                    .ToList();
            }
            catch
            {
                ModelState.AddModelError("DiasSemana", "Formato de dias invalido. Use: 1,3,5");
            }
        }

        private void CompletarDiasSemanaDesdeFormulario(EditarHabitoViewModel modelo)
        {
            var diasRaw = Request.Form["DiasSemana"].FirstOrDefault();
            if (string.IsNullOrWhiteSpace(diasRaw) || modelo.DiasSemana?.Any() == true)
            {
                return;
            }

            try
            {
                modelo.DiasSemana = diasRaw.Split(',', StringSplitOptions.RemoveEmptyEntries)
                    .Select(s => int.Parse(s.Trim()))
                    .ToList();
            }
            catch
            {
                ModelState.AddModelError("DiasSemana", "Formato de dias invalido. Use: 1,3,5");
            }
        }

        private bool EsCheckboxMarcado(string nombre)
            => Request.Form.ContainsKey(nombre)
               && (Request.Form[nombre] == "on" || Request.Form[nombre] == "true");
    }
}
