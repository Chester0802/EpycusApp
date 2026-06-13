using System.Security.Claims;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Filters;

namespace EPYCUS_WEB_v0._1.Middleware
{
    /// <summary>
    /// Action filter that loads the current user's character image into ViewBag
    /// so the sidebar can display it on every page.
    /// </summary>
    public class CargarPersonajeFilter : IAsyncActionFilter
    {
        private readonly IServicioPerfil _servicioPerfil;

        public CargarPersonajeFilter(IServicioPerfil servicioPerfil)
        {
            _servicioPerfil = servicioPerfil;
        }

        public async Task OnActionExecutionAsync(ActionExecutingContext context, ActionExecutionDelegate next)
        {
            var resultContext = await next();

            // Only set ViewBag if the result is a ViewResult (i.e., rendering a page)
            if (resultContext.Result is ViewResult viewResult)
            {
                var user = context.HttpContext.User;
                if (user.Identity != null && user.Identity.IsAuthenticated)
                {
                    var claim = user.FindFirst(ClaimTypes.NameIdentifier)?.Value;
                    if (int.TryParse(claim, out int usuarioId) && usuarioId > 0)
                    {
                        try
                        {
                            var imagen = await _servicioPerfil.ObtenerImagenPersonajeActual(usuarioId);
                            viewResult.ViewData["ImagenPersonaje"] = imagen;
                        }
                        catch
                        {
                            // Silently ignore - placeholder will be used
                        }
                    }
                }
            }
        }
    }
}
