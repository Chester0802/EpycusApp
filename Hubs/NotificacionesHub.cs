using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.SignalR;
using System.Security.Claims;

namespace EpycusApp.Hubs
{
    [Authorize]
    public class NotificacionesHub : Hub
    {
        private int ObtenerUsuarioId()
        {
            var claim = Context.User?.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            return int.TryParse(claim, out var id) ? id : 0;
        }

        public async Task UnirseAlGrupo(int usuarioId)
        {
            var currentUserId = ObtenerUsuarioId();
            if (currentUserId != usuarioId)
            {
                throw new HubException("No autorizado para unirse a este grupo");
            }
            await Groups.AddToGroupAsync(Context.ConnectionId, $"usuario_{usuarioId}");
        }

        public async Task SalirDelGrupo(int usuarioId)
        {
            var currentUserId = ObtenerUsuarioId();
            if (currentUserId != usuarioId)
            {
                throw new HubException("No autorizado para salir de este grupo");
            }
            await Groups.RemoveFromGroupAsync(Context.ConnectionId, $"usuario_{usuarioId}");
        }

        public override async Task OnConnectedAsync()
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId > 0)
            {
                await Groups.AddToGroupAsync(Context.ConnectionId, $"usuario_{usuarioId}");
            }
            await base.OnConnectedAsync();
        }

        public override async Task OnDisconnectedAsync(Exception? exception)
        {
            var usuarioId = ObtenerUsuarioId();
            if (usuarioId > 0)
            {
                await Groups.RemoveFromGroupAsync(Context.ConnectionId, $"usuario_{usuarioId}");
            }
            await base.OnDisconnectedAsync(exception);
        }
    }
}
