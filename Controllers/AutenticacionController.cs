using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Autenticacion;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    public class AutenticacionController : Controller
    {
        private readonly IServicioAutenticacion _servicioAutenticacion;

        public AutenticacionController(IServicioAutenticacion servicioAutenticacion)
        {
            _servicioAutenticacion = servicioAutenticacion;
        }

        private CookieOptions CrearOpcionesCookie(int expiracionDias = 7)
        {
            return new CookieOptions
            {
                HttpOnly = true,
                Secure = Request.IsHttps,
                SameSite = SameSiteMode.Strict,
                Expires = DateTime.UtcNow.AddDays(expiracionDias)
            };
        }

        private CookieOptions CrearOpcionesCookie(int expiracionMinutos, bool recordarme)
        {
            return new CookieOptions
            {
                HttpOnly = true,
                Secure = Request.IsHttps,
                SameSite = SameSiteMode.Strict,
                Expires = recordarme
                    ? DateTime.UtcNow.AddDays(7)
                    : DateTime.UtcNow.AddMinutes(expiracionMinutos)
            };
        }

        [HttpGet]
        [AllowAnonymous]
        public async Task<IActionResult> Registro()
        {
            ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();
            return View(new EpycusApp.ViewModels.RegistroViewModel());
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Registro(EpycusApp.ViewModels.RegistroViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();
                return View(modelo);
            }

            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion.RegistrarUsuario(modelo);
            if (!exito || string.IsNullOrWhiteSpace(token))
            {
                ModelState.AddModelError(string.Empty, mensaje);
                ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();
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

        [HttpGet]
        [AllowAnonymous]
        public IActionResult RecuperarContrasena()
        {
            return View(new RecuperarContrasenaViewModel());
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> RecuperarContrasena(RecuperarContrasenaViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                return View(modelo);
            }

            await _servicioAutenticacion.EnviarCorreoRecuperacion(modelo.CorreoElectronico);
            TempData["RecuperacionEnviada"] = true;
            return RedirectToAction(nameof(RecuperarContrasena));
        }

        [HttpGet]
        [AllowAnonymous]
        public async Task<IActionResult> VerificarCorreo(string token)
        {
            if (string.IsNullOrWhiteSpace(token))
            {
                return RedirectToAction(nameof(Login));
            }

            var exito = await _servicioAutenticacion.VerificarCorreo(token);

            if (!exito)
            {
                TempData["ErrorVerificacion"] = "El enlace de verificacion no es valido o ya expiro.";
                return RedirectToAction(nameof(Login));
            }

            TempData["CorreoVerificado"] = true;
            return RedirectToAction("Index", "Home");
        }

        [HttpGet]
        [AllowAnonymous]
        public IActionResult RestablecerContrasena(string token)
        {
            if (string.IsNullOrWhiteSpace(token))
            {
                return RedirectToAction(nameof(Login));
            }

            return View(new RestablecerContrasenaViewModel { Token = token });
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> RestablecerContrasena(RestablecerContrasenaViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                return View(modelo);
            }

            var exito = await _servicioAutenticacion.RestablecerContrasena(
                modelo.Token,
                modelo.NuevaContrasena);

            if (!exito)
            {
                ModelState.AddModelError(string.Empty, "El enlace no es valido o ya expiro.");
                return View(modelo);
            }

            return RedirectToAction(nameof(Login));
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
