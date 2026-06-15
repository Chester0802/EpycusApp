using System.Security.Claims;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Filters;

namespace EpycusApp.Middleware
{
    /// <summary>
    /// Action filter that loads the current user's character image into ViewBag
    /// so the sidebar can display it on every page.
    /// </summary>
    public class CargarPersonajeFilter : IAsyncActionFilter
    {
        private readonly IServicioPerfil _servicioPerfil;
        private readonly ILogger<CargarPersonajeFilter> _logger;

        public CargarPersonajeFilter(IServicioPerfil servicioPerfil, ILogger<CargarPersonajeFilter> logger)
        {
            _servicioPerfil = servicioPerfil;
            _logger = logger;
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
                        catch (Exception ex)
                        {
                            _logger.LogWarning(ex, "Error al cargar imagen de personaje para usuario {UsuarioId}", usuarioId);
                        }
                    }
                }
            }
        }
    }
}
