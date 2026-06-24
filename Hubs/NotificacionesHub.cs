using Microsoft.AspNetCore.SignalR;

namespace EpycusApp.Hubs
{
    public class NotificacionesHub : Hub
    {
        public async Task UnirseAlGrupo(int usuarioId)
        {
            await Groups.AddToGroupAsync(Context.ConnectionId, $"usuario_{usuarioId}");
        }

        public async Task SalirDelGrupo(int usuarioId)
        {
            await Groups.RemoveFromGroupAsync(Context.ConnectionId, $"usuario_{usuarioId}");
        }

        public override async Task OnConnectedAsync()
        {
            await base.OnConnectedAsync();
        }

        public override async Task OnDisconnectedAsync(Exception? exception)
        {
            await base.OnDisconnectedAsync(exception);
        }
    }
}
