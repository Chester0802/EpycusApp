using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioBienestar
    {
        Task<List<AlertaBienestar>> ObtenerAlertasActivas(int usuarioId);
        Task<AlertaBienestar?> VerificarUsoExcesivoPomodoro(int usuarioId);
        Task<AlertaBienestar?> VerificarAnimoNegativoConsecutivo(int usuarioId);
        Task<FraseMotivacional?> ObtenerFraseMotivacionalAleatoria();

        Task<EstadoAnimo?> ObtenerEstadoHoy(int usuarioId);
        Task<List<EstadoAnimo>> ObtenerHistorialAnimo(int usuarioId, int dias);
        Task<List<EstadoAnimo>> ObtenerHistorialAnimoCompletoAsync(int usuarioId);
        Task<AlertaBienestar?> RegistrarEstadoAnimo(int usuarioId, string estado, string? nota);

        Task<int> ObtenerHabitosPendientesAsync(int usuarioId);
        Task<int> ObtenerMisionesPendientesAsync(int usuarioId);

        RecomendacionPausaDto? RecomendacionPausaActiva(int ciclosCompletados);
    }
}
