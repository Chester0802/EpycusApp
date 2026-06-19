using System.Security.Claims;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    public abstract class BaseApiController : ControllerBase
    {
        protected int? ObtenerUsuarioId()
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            return int.TryParse(claim, out var usuarioId) ? usuarioId : null;
        }

        protected string ConvertirUrlAbsoluta(string? ruta)
        {
            if (string.IsNullOrEmpty(ruta)) return string.Empty;
            if (ruta.StartsWith("http://") || ruta.StartsWith("https://")) return ruta;
            var baseUrl = $"{Request.Scheme}://{Request.Host}";
            return $"{baseUrl}{ruta}";
        }
    }
}
