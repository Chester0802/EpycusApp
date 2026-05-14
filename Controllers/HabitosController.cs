using System;
using System.Linq;
using System.Security.Claims;
using System.Threading.Tasks;
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
        private readonly Microsoft.Extensions.Logging.ILogger<HabitosController> _logger;

        public HabitosController(IServicioHabitos servicioHabitos, Microsoft.Extensions.Logging.ILogger<HabitosController> logger)
        {
            _servicioHabitos = servicioHabitos;
            _logger = logger;
        }

        [AllowAnonymous]
        public async Task<IActionResult> Index()
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            int usuarioId = 1; // Por defecto usuario 1 para depuración
            if (int.TryParse(claim, out var parsed) && parsed != 0)
            {
                usuarioId = parsed;
            }

            var lista = await _servicioHabitos.ObtenerHabitosViewModel(usuarioId);
            var dashboard = await _servicioHabitos.ObtenerDashboard(usuarioId);
            // pasar categorias para el modal de creación
            var categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            ViewBag.Categorias = categorias;

            var modelo = new EPYCUS_WEB_v0._1.ViewModels.HabitosIndexViewModel
            {
                Habitos = lista,
                Dashboard = dashboard
            };

            return View(modelo);
        }

        public async Task<IActionResult> Crear()
        {
            // Obtener categorías activas para el select
            var categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            ViewBag.Categorias = categorias;
            return View(new CrearHabitoViewModel());
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Crear(CrearHabitoViewModel modelo)
        {
            // Asegurar que tomamos valores incluso si el binding falló (p. ej. al enviar desde modal)
            // Rellenar desde Request.Form cuando falte información en el modelo
            if (modelo == null)
                modelo = new CrearHabitoViewModel();

            var nombreForm = Request.Form["Nombre"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(nombreForm)) modelo.Nombre = nombreForm;

            var descForm = Request.Form["Descripcion"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(descForm)) modelo.Descripcion = descForm;

            var catForm = Request.Form["CategoriaId"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(catForm) && int.TryParse(catForm, out var catId)) modelo.CategoriaId = catId;

            var freqForm = Request.Form["Frecuencia"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(freqForm)) modelo.Frecuencia = freqForm;

            var diasRaw = Request.Form["DiasSemana"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(diasRaw) && (modelo.DiasSemana == null || !modelo.DiasSemana.Any()))
            {
                try
                {
                    modelo.DiasSemana = diasRaw.Split(',', StringSplitOptions.RemoveEmptyEntries)
                        .Select(s => int.Parse(s.Trim())).ToList();
                }
                catch
                {
                    ModelState.AddModelError("DiasSemana", "Formato de días inválido. Use: 1,3,5");
                }
            }

            // Checkboxes suelen enviar "on" si están marcados
            modelo.ConPomodoro = Request.Form.ContainsKey("ConPomodoro") && (Request.Form["ConPomodoro"] == "on" || Request.Form["ConPomodoro"] == "true");
            modelo.EstaActivo = Request.Form.ContainsKey("EstaActivo") && (Request.Form["EstaActivo"] == "on" || Request.Form["EstaActivo"] == "true");

            var horaForm = Request.Form["RecordatorioHora"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(horaForm) && TimeSpan.TryParse(horaForm, out var ts)) modelo.RecordatorioHora = ts;

            var categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            ViewBag.Categorias = categorias;

            // Validar con atributos de data annotations
            TryValidateModel(modelo);

            if (!ModelState.IsValid)
            {
                var isAjaxError = Request.Headers["X-Requested-With"] == "XMLHttpRequest" || Request.Headers["Accept"].ToString().Contains("application/json");
                if (isAjaxError)
                {
                    var errors = ModelState.Where(kvp => kvp.Value.Errors.Any())
                        .ToDictionary(kvp => kvp.Key, kvp => kvp.Value.Errors.Select(e => e.ErrorMessage).ToArray());
                    return BadRequest(new { success = false, errors });
                }
                
                // Limpiar valores booleanos problemáticos del ModelState antes de renderizar la vista
                ModelState.Remove("ConPomodoro");
                ModelState.Remove("EstaActivo");
                
                // Si hay errores, mostrar el formulario Crear (página completa) con errores para facilitar corrección.
                return View(modelo);
            }

            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            int usuarioId = 1; // Por defecto usuario 1 para depuración
            if (int.TryParse(claim, out var parsed) && parsed != 0)
            {
                usuarioId = parsed;
            }

            try
            {
                await _servicioHabitos.CrearHabito(modelo, usuarioId);
                // Placeholder for future post-create logic (no-op for now).
                // e.g., send notification, enqueue background job, etc.
            }
            catch (ArgumentException ex)
            {
                _logger.LogWarning(ex, "Categoría inválida al crear hábito para usuario {UsuarioId}", usuarioId);
                ModelState.AddModelError(string.Empty, ex.Message);
                // si es petición AJAX devolver errores en JSON
                var isAjax = Request.Headers["X-Requested-With"] == "XMLHttpRequest" || Request.Headers["Accept"].ToString().Contains("application/json");
                if (isAjax)
                {
                    var errors = ModelState.Where(kvp => kvp.Value.Errors.Any())
                        .ToDictionary(kvp => kvp.Key, kvp => kvp.Value.Errors.Select(e => e.ErrorMessage).ToArray());
                    return BadRequest(new { success = false, errors });
                }
                return View(modelo);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error al crear hábito para usuario {UsuarioId}", usuarioId);
                ModelState.AddModelError(string.Empty, "Error inesperado al crear el hábito. Intente de nuevo más tarde.");
                var isAjax = Request.Headers["X-Requested-With"] == "XMLHttpRequest" || Request.Headers["Accept"].ToString().Contains("application/json");
                if (isAjax)
                {
                    return StatusCode(500, new { success = false, message = "Error inesperado al crear el hábito." });
                }
                return View(modelo);
            }

            var isAjaxSuccess = Request.Headers["X-Requested-With"] == "XMLHttpRequest" || Request.Headers["Accept"].ToString().Contains("application/json");
            if (isAjaxSuccess)
                return Json(new { success = true });

            return RedirectToAction(nameof(Index));
        }

        [AllowAnonymous]
        public async Task<IActionResult> Editar(int id)
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            int usuarioId = 1; // Por defecto usuario 1 para depuración
            if (int.TryParse(claim, out var parsed) && parsed != 0)
            {
                usuarioId = parsed;
            }

            var vm = await _servicioHabitos.ObtenerPorIdViewModel(id);
            if (vm is null)
                return NotFound();

            var modelo = new EditarHabitoViewModel
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
            };

            // pasar categorias activas
            var categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            ViewBag.Categorias = categorias;

            return View(modelo);
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Editar(EditarHabitoViewModel modelo)
        {
            // parsear DiasSemana si viene como texto
            var diasRaw = Request.Form["DiasSemana"].FirstOrDefault();
            if (!string.IsNullOrWhiteSpace(diasRaw) && (modelo.DiasSemana == null || !modelo.DiasSemana.Any()))
            {
                try
                {
                    modelo.DiasSemana = diasRaw.Split(',', StringSplitOptions.RemoveEmptyEntries)
                        .Select(s => int.Parse(s.Trim())).ToList();
                }
                catch
                {
                    ModelState.AddModelError("DiasSemana", "Formato de días inválido. Use: 1,3,5");
                }
            }

            var categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            ViewBag.Categorias = categorias;

            if (!ModelState.IsValid)
            {
                ModelState.Remove("ConPomodoro");
                ModelState.Remove("EstaActivo");
                return View(modelo);
            }

            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            int usuarioId = 1; // Por defecto usuario 1 para depuración
            if (int.TryParse(claim, out var parsed) && parsed != 0)
            {
                usuarioId = parsed;
            }

            try
            {
                await _servicioHabitos.EditarHabito(modelo, usuarioId);
                return RedirectToAction(nameof(Index));
            }
            catch (Exception ex)
            {
                ModelState.AddModelError(string.Empty, "Error inesperado al guardar cambios: " + ex.Message);
                return View(modelo);
            }
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Eliminar(int id)
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            int usuarioId = 1; // Por defecto usuario 1 para depuración
            if (int.TryParse(claim, out var parsed) && parsed != 0)
            {
                usuarioId = parsed;
            }

            await _servicioHabitos.EliminarHabito(id, usuarioId);
            return RedirectToAction(nameof(Index));
        }
    }
}
