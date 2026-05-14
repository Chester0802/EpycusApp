using System.Linq;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels.Autenticacion;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EPYCUS_WEB_v0._1.Controllers
{
    public class AutenticacionController : Controller
    {
        private readonly IServicioAutenticacion _servicioAutenticacion;
        private readonly Datos.ContextoAplicacion _contexto;

        public AutenticacionController(IServicioAutenticacion servicioAutenticacion, Datos.ContextoAplicacion contexto)
        {
            _servicioAutenticacion = servicioAutenticacion;
            _contexto = contexto;
        }

        private CookieOptions CrearOpcionesCookie(int expiracionDias = 7)
        {
            return new CookieOptions
            {
                HttpOnly = true,
                Secure = true,
                SameSite = SameSiteMode.Strict,
                Expires = DateTime.UtcNow.AddDays(expiracionDias)
            };
        }

        private CookieOptions CrearOpcionesCookie(int expiracionMinutos, bool recordarme)
        {
            return new CookieOptions
            {
                HttpOnly = true,
                Secure = true,
                SameSite = SameSiteMode.Strict,
                Expires = recordarme
                    ? DateTime.UtcNow.AddDays(7)
                    : DateTime.UtcNow.AddMinutes(expiracionMinutos)
            };
        }

        [HttpGet]
        [AllowAnonymous]
        public IActionResult Registro()
        {
            ViewBag.Carreras = _contexto.Carreras.Where(c => c.EstaActiva).ToList();
            return View(new EPYCUS_WEB_v0._1.ViewModels.RegistroViewModel());
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Registro(EPYCUS_WEB_v0._1.ViewModels.RegistroViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                ViewBag.Carreras = _contexto.Carreras.Where(c => c.EstaActiva).ToList();
                return View(modelo);
            }

            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.RegistrarUsuario(modelo);
            if (!exito || string.IsNullOrWhiteSpace(token))
            {
                ModelState.AddModelError(string.Empty, mensaje);
                ViewBag.Carreras = _contexto.Carreras.Where(c => c.EstaActiva).ToList();
                return View(modelo);
            }

            var cookieOptions = CrearOpcionesCookie(7);

            Response.Cookies.Append("jwt_token", token, cookieOptions);

            if (!string.IsNullOrEmpty(refreshToken))
            {
                Response.Cookies.Append("refresh_token", refreshToken, cookieOptions);
            }

            return RedirectToAction("Index", "Home");
        }

        [HttpGet]
        [AllowAnonymous]
        public IActionResult Login()
        {
            if (User.Identity != null && User.Identity.IsAuthenticated)
            {
                return RedirectToAction("Index", "Home");
            }
            return View();
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Login(LoginViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                return View(modelo);
            }

            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.Login(modelo.CorreoElectronico, modelo.Contrasena);

            if (exito && !string.IsNullOrEmpty(token))
            {
                // Guardar el token en una cookie segura para las peticiones MVC
                var cookieOptions = CrearOpcionesCookie(60, modelo.Recordarme);

                Response.Cookies.Append("jwt_token", token, cookieOptions);

                if (!string.IsNullOrEmpty(refreshToken))
                {
                    var cookieRefreshOptions = CrearOpcionesCookie(modelo.Recordarme ? 7 : 1);
                    Response.Cookies.Append("refresh_token", refreshToken, cookieRefreshOptions);
                }

                return RedirectToAction("Index", "Home");
            }

            ModelState.AddModelError(string.Empty, mensaje);
            return View(modelo);
        }

        [HttpPost]
        [Authorize]
        [ValidateAntiForgeryToken]
        public IActionResult Logout()
        {
            Response.Cookies.Delete("jwt_token");
            Response.Cookies.Delete("refresh_token");
            return RedirectToAction("Login");
        }
    }
}
