using System.Security.Claims;
using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using EpycusApp.ViewModels.Autenticacion;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Google;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    public class AutenticacionController : Controller
    {
        private readonly IServicioAutenticacion _servicioAutenticacion;
        private readonly VerificadorTurnstile _verificadorTurnstile;

        public AutenticacionController(IServicioAutenticacion servicioAutenticacion, VerificadorTurnstile verificadorTurnstile)
        {
            _servicioAutenticacion = servicioAutenticacion;
            _verificadorTurnstile = verificadorTurnstile;
        }

        private CookieOptions CrearOpcionesCookie(int expiracionDias = 7)
        {
            return new CookieOptions
            {
                HttpOnly = true,
                Secure = true,
                SameSite = SameSiteMode.Lax,
                Expires = DateTime.UtcNow.AddDays(expiracionDias)
            };
        }

        private CookieOptions CrearOpcionesCookie(int expiracionMinutos, bool recordarme)
        {
            return new CookieOptions
            {
                HttpOnly = true,
                Secure = true,
                SameSite = SameSiteMode.Lax,
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

            var turnstileToken = Request.Form["cf-turnstile-response"];
            if (!await _verificadorTurnstile.VerificarTokenAsync(turnstileToken))
            {
                ModelState.AddModelError(string.Empty, "Verificacion de seguridad fallida. Intentalo de nuevo.");
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

            var turnstileToken = Request.Form["cf-turnstile-response"];
            if (!await _verificadorTurnstile.VerificarTokenAsync(turnstileToken))
            {
                ModelState.AddModelError(string.Empty, "Verificacion de seguridad fallida. Intentalo de nuevo.");
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

        [HttpGet]
        [AllowAnonymous]
        public IActionResult IniciarSesionGoogle()
        {
            var properties = new AuthenticationProperties
            {
                RedirectUri = Url.Action(nameof(CallbackGoogle))
            };
            return Challenge(properties, GoogleDefaults.AuthenticationScheme);
        }

        [HttpGet]
        [AllowAnonymous]
        public async Task<IActionResult> CallbackGoogle()
        {
            var result = await HttpContext.AuthenticateAsync("ExternalCookie");
            if (!result.Succeeded)
                return RedirectToAction(nameof(Login));

            var googleId = result.Principal.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            var email = result.Principal.FindFirst(ClaimTypes.Email)?.Value;
            var name = result.Principal.FindFirst(ClaimTypes.Name)?.Value;
            var photoUrl = result.Principal.FindFirst("picture")?.Value;

            await HttpContext.SignOutAsync("ExternalCookie");

            if (string.IsNullOrWhiteSpace(googleId) || string.IsNullOrWhiteSpace(email))
                return RedirectToAction(nameof(Login));

            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion
                .ProcesarAutenticacionGoogleAsync(googleId, email, name ?? "Usuario", photoUrl);

            if (exito && !string.IsNullOrEmpty(token))
            {
                var cookieOptions = CrearOpcionesCookie(7);
                Response.Cookies.Append("jwt_token", token, cookieOptions);
                if (!string.IsNullOrEmpty(refreshToken))
                    Response.Cookies.Append("refresh_token", refreshToken, cookieOptions);
                return RedirectToAction("Index", "Home");
            }

            if (mensaje == "completar_registro")
            {
                TempData["GoogleId"] = googleId;
                TempData["GoogleEmail"] = email;
                TempData["GoogleName"] = name ?? "Usuario";
                TempData["GooglePhoto"] = photoUrl;
                return RedirectToAction(nameof(CompletarRegistroGoogle));
            }

            TempData["ErrorAutenticacion"] = mensaje;
            return RedirectToAction(nameof(Login));
        }

        [HttpGet]
        [AllowAnonymous]
        public async Task<IActionResult> CompletarRegistroGoogle()
        {
            var googleId = TempData["GoogleId"] as string;
            var email = TempData["GoogleEmail"] as string;
            var name = TempData["GoogleName"] as string;

            if (string.IsNullOrWhiteSpace(googleId) || string.IsNullOrWhiteSpace(email))
                return RedirectToAction(nameof(Registro));

            ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();

            return View(new CompletarRegistroGoogleViewModel
            {
                Nombre = name ?? "",
                CorreoElectronico = email,
                GoogleId = googleId,
                FotoGoogleUrl = TempData["GooglePhoto"] as string
            });
        }

        [HttpPost]
        [AllowAnonymous]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> CompletarRegistroGoogle(CompletarRegistroGoogleViewModel modelo)
        {
            if (!ModelState.IsValid)
            {
                ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();
                return View(modelo);
            }

            var (exito, mensaje, token, refreshToken) = await _servicioAutenticacion
                .CompletarRegistroGoogleAsync(modelo);

            if (!exito || string.IsNullOrWhiteSpace(token))
            {
                ModelState.AddModelError(string.Empty, mensaje);
                ViewBag.Carreras = await _servicioAutenticacion.ObtenerCarrerasActivas();
                return View(modelo);
            }

            var cookieOptions = CrearOpcionesCookie(7);
            Response.Cookies.Append("jwt_token", token, cookieOptions);
            if (!string.IsNullOrEmpty(refreshToken))
                Response.Cookies.Append("refresh_token", refreshToken, cookieOptions);

            return RedirectToAction("Index", "Home");
        }

        [HttpPost]
        [Authorize]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Logout()
        {
            var token = Request.Cookies["jwt_token"];
            if (!string.IsNullOrEmpty(token))
            {
                var handler = new System.IdentityModel.Tokens.Jwt.JwtSecurityTokenHandler();
                var jwtToken = handler.ReadJwtToken(token);
                var jti = jwtToken.Claims.FirstOrDefault(c => c.Type == Microsoft.IdentityModel.JsonWebTokens.JwtRegisteredClaimNames.Jti)?.Value;
                var exp = jwtToken.ValidTo;
                
                if (!string.IsNullOrEmpty(jti) && exp > DateTime.UtcNow)
                {
                    var blacklist = HttpContext.RequestServices.GetRequiredService<EpycusApp.Servicios.Interfaces.IJwtBlacklist>();
                    await blacklist.AddToBlacklistAsync(jti, exp - DateTime.UtcNow);
                }
            }

            var usuarioId = User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)?.Value;
            if (int.TryParse(usuarioId, out var id))
            {
                await _servicioAutenticacion.CerrarSesion(id);
            }

            Response.Cookies.Delete("jwt_token");
            Response.Cookies.Delete("refresh_token");
            return RedirectToAction("Login");
        }
    }
}
