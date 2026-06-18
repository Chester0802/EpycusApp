using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Admin;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    public class AdminController : BaseController
    {
        private readonly IServicioAdmin _servicioAdmin;
        private readonly IServicioAutenticacion _servicioAutenticacion;

        public AdminController(IServicioAdmin servicioAdmin, IServicioAutenticacion servicioAutenticacion)
        {
            _servicioAdmin = servicioAdmin;
            _servicioAutenticacion = servicioAutenticacion;
        }

        [AllowAnonymous]
        [HttpGet("/admin/login")]
        public IActionResult Login()
        {
            if (User.Identity != null && User.Identity.IsAuthenticated && User.IsInRole("Administrador"))
            {
                return RedirectToAction(nameof(Index));
            }

            return View();
        }

        [AllowAnonymous]
        [HttpPost("/admin/login")]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Login(AdminLoginViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                return View(modelo);
            }

            var (exito, mensaje, token, _) = await _servicioAutenticacion.Login(modelo.CorreoElectronico, modelo.Contrasena);

            if (!exito || string.IsNullOrWhiteSpace(token))
            {
                ModelState.AddModelError(string.Empty, mensaje);
                return View(modelo);
            }

            var cookieOptions = new CookieOptions
            {
                HttpOnly = true,
                Secure = true,
                SameSite = SameSiteMode.Strict,
                Expires = DateTime.UtcNow.AddHours(2)
            };

            if (modelo.Recordarme)
            {
                cookieOptions.Expires = DateTime.UtcNow.AddDays(3);
            }

            Response.Cookies.Append("admin_jwt_token", token, cookieOptions);

            return RedirectToAction(nameof(Index));
        }

        [Authorize(Roles = "Administrador")]
        [HttpPost("/admin/logout")]
        [ValidateAntiForgeryToken]
        public IActionResult Logout()
        {
            Response.Cookies.Delete("admin_jwt_token");
            return RedirectToAction(nameof(Login));
        }

        [Authorize(Roles = "Administrador")]
        public async Task<IActionResult> Index()
        {
            var usuarios = await _servicioAdmin.ObtenerTodosUsuarios();
            var frases = await _servicioAdmin.ObtenerFrases();

            var suscripcionesActivas = usuarios
                .SelectMany(u => u.Suscripciones)
                .Count(s => s.EstaActiva);

            var modelo = new AdminDashboardViewModel
            {
                TotalUsuarios = usuarios.Count,
                UsuariosActivos = usuarios.Count(u => u.EstaActivo),
                SuscripcionesActivas = suscripcionesActivas,
                TotalFrases = frases.Count
            };

            return View(modelo);
        }

        [Authorize(Roles = "Administrador")]
        public async Task<IActionResult> Usuarios()
        {
            var usuarios = await _servicioAdmin.ObtenerTodosUsuarios();
            return View(usuarios);
        }

        [Authorize(Roles = "Administrador")]
        public async Task<IActionResult> DetalleUsuario(int id)
        {
            var usuario = await _servicioAdmin.ObtenerUsuarioPorId(id);
            if (usuario == null)
            {
                return NotFound();
            }

            return View(usuario);
        }

        [Authorize(Roles = "Administrador")]
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> ActivarSuscripcion(int usuarioId)
        {
            var adminId = ObtenerUsuarioId();
            await _servicioAdmin.ActivarSuscripcion(usuarioId, adminId);
            TempData["Exito"] = "Suscripción activada correctamente.";
            return RedirectToAction(nameof(Usuarios));
        }

        [Authorize(Roles = "Administrador")]
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> DesactivarSuscripcion(int usuarioId)
        {
            await _servicioAdmin.DesactivarSuscripcion(usuarioId);
            TempData["Exito"] = "Suscripción desactivada correctamente.";
            return RedirectToAction(nameof(Usuarios));
        }

        [Authorize(Roles = "Administrador")]
        public async Task<IActionResult> Frases()
        {
            var frases = await _servicioAdmin.ObtenerFrases();
            return View(frases);
        }

        [Authorize(Roles = "Administrador")]
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> CrearFrase(string frase, string autor)
        {
            await _servicioAdmin.CrearFrase(frase, autor);
            TempData["Exito"] = "Frase creada correctamente.";
            return RedirectToAction(nameof(Frases));
        }

        [Authorize(Roles = "Administrador")]
        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> EliminarFrase(int id)
        {
            await _servicioAdmin.EliminarFrase(id);
            TempData["Exito"] = "Frase eliminada correctamente.";
            return RedirectToAction(nameof(Frases));
        }
    }
}
