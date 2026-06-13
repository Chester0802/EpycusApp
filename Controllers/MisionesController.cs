using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Rendering;

namespace EPYCUS_WEB_v0._1.Controllers
{
    public class MisionesController : Controller
    {
        private readonly IServicioMisiones _servicioMisiones;
        private readonly ContextoAplicacion _contexto;
        private readonly int _usuarioIdPrueba = 1;

        public MisionesController(IServicioMisiones servicioMisiones, ContextoAplicacion contexto)
        {
            _servicioMisiones = servicioMisiones;
            _contexto = contexto;
        }

        private void AsegurarUsuarioPrueba()
        {
            if (!_contexto.Usuarios.Any(u => u.Id == _usuarioIdPrueba))
            {
                var usuario = new Usuario
                {
                    Id = _usuarioIdPrueba,
                    Nombre = "Usuario Prueba",
                    CorreoElectronico = "prueba@epycus.com",
                    ContrasenaHash = "123456",
                    RolId = _contexto.Roles.First().Id,
                    CarreraId = _contexto.Carreras.First().Id,
                    CodigoUnico = "USR-001"
                };
                
                var progreso = new ProgresoUsuario
                {
                    Id = 1,
                    UsuarioId = _usuarioIdPrueba,
                    NivelActualId = _contexto.Niveles.First().Id,
                    XpTotal = 0,
                    RachaActual = 0,
                    RachaMaxima = 0
                };
                _contexto.Usuarios.Add(usuario);
                _contexto.ProgresosUsuario.Add(progreso);
                _contexto.SaveChanges();
            }
        }

        public async Task<IActionResult> Index()
        {
            AsegurarUsuarioPrueba();
            var misiones = await _servicioMisiones.ObtenerMisionesDeUsuario(_usuarioIdPrueba);
            return View(misiones);
        }

        public IActionResult Crear()
        {
            AsegurarUsuarioPrueba();
            CargarCategorias();
            return View(new CrearMisionViewModel { FechaLimite = DateTime.Today.AddDays(1) });
        }

        [HttpPost]
        public async Task<IActionResult> Crear(CrearMisionViewModel modelo)
        {
            if (ModelState.IsValid)
            {
                await _servicioMisiones.CrearMision(modelo, _usuarioIdPrueba);
                return RedirectToAction(nameof(Index));
            }
            CargarCategorias();
            return View(modelo);
        }

        public async Task<IActionResult> Editar(int id)
        {
            AsegurarUsuarioPrueba();
            var mision = await _servicioMisiones.ObtenerPorId(id);
            if (mision == null || mision.UsuarioId != _usuarioIdPrueba) return NotFound();

            if (mision.Estado == "Completado" || mision.Estado == "Fallido")
            {
                TempData["Error"] = "No se puede editar una misión completada o fallida.";
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

            CargarCategorias();
            return View(modelo);
        }

        [HttpPost]
        public async Task<IActionResult> Editar(EditarMisionViewModel modelo)
        {
            if (ModelState.IsValid)
            {
                try
                {
                    await _servicioMisiones.EditarMision(modelo, _usuarioIdPrueba);
                    return RedirectToAction(nameof(Index));
                }
                catch (Exception ex)
                {
                    ModelState.AddModelError("", ex.Message);
                }
            }
            CargarCategorias();
            return View(modelo);
        }

        [HttpPost]
        public async Task<IActionResult> CambiarEstado(int id, string estado)
        {
            await _servicioMisiones.CambiarEstado(id, estado, _usuarioIdPrueba);
            return RedirectToAction(nameof(Index));
        }

        [HttpPost]
        public async Task<IActionResult> Completar(int id)
        {
            var resultado = await _servicioMisiones.CompletarMision(id, _usuarioIdPrueba);
            if (resultado.Exito)
            {
                TempData["Exito"] = $"¡Misión completada! Ganaste {resultado.XpGanado} XP.";
            }
            else
            {
                TempData["Error"] = "No se pudo completar la misión.";
            }
            return RedirectToAction(nameof(Index));
        }

        [HttpPost]
        public async Task<IActionResult> Eliminar(int id)
        {
            await _servicioMisiones.EliminarMision(id, _usuarioIdPrueba);
            return RedirectToAction(nameof(Index));
        }

        private void CargarCategorias()
        {
            ViewBag.Categorias = new SelectList(
                _contexto.Categorias.Where(c => c.Tipo == "Mision" || c.Tipo == "Ambos"),
                "Id", "Nombre");
        }
    }
}
