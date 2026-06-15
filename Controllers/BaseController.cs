using System.Security.Claims;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    public abstract class BaseController : Controller
    {
        protected int ObtenerUsuarioId()
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            if (int.TryParse(claim, out var usuarioId) && usuarioId > 0)
                return usuarioId;
            return 0;
        }
    }
}
