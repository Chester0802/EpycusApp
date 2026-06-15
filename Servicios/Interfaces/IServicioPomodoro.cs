using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioPomodoro
    {
        Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId);
        Task<(int XpGanado, bool SugerirDescanso, string? PausaActiva)> RegistrarCiclo(int sesionId, int ciclosCompletados);
        Task FinalizarSesion(int sesionId, int ciclosCompletados);
        Task CancelarSesion(int sesionId);
        Task<ConfiguracionPomodoro> ObtenerConfiguracion(int usuarioId);
        Task ActualizarConfiguracion(int usuarioId, ActualizarConfiguracionPomodoroDto dto);
        Task<string> ObtenerTipAleatorio();
        Task<SesionPomodoro?> ObtenerSesion(int sesionId);
        Task<List<SesionPomodoro>> ObtenerSesionesHoyAsync(int usuarioId);
        Task<int> ObtenerMisionesCompletadasHoyAsync(int usuarioId);
        Task<List<TareaPomodoro>> ObtenerTareasEnfoqueAsync(int usuarioId);
    }
}
