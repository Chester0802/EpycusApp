using EPYCUS_WEB_v0._1.DTOs;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.ViewModels;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
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
