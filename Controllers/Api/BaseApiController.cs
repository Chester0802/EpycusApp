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
    }
}
