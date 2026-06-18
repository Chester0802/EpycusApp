using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels.Ia;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioIA
    {
        Task<string> ChatAsync(int usuarioId, string mensaje, string conversacionId);
        Task<List<MensajeIA>> ObtenerHistorialAsync(int usuarioId, string conversacionId);
        string NuevaConversacionId();
        Task<List<string>> ObtenerSugerenciasPersonalizadasAsync(int usuarioId);
        Task<List<ConversacionResumen>> ObtenerConversacionesAsync(int usuarioId);
        Task<BienestarContextoIA?> ObtenerBienestarContextoAsync(int usuarioId);
        Task RegistrarFeedbackAsync(int usuarioId, int mensajeId, bool util);
        Task<int> ObtenerMensajesHoyAsync(int usuarioId);
    }
}
