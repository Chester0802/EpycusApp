using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioPomodoro
    {
        Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId);
        Task<(bool Exito, SesionPomodoro? Sesion, string? Error)> IniciarSesionSiNoActiva(int usuarioId, int? habitoId, int? misionId);
        Task<(int XpGanado, bool SugerirDescanso, string? PausaActiva)> RegistrarCiclo(int sesionId, int ciclosCompletados);
        Task<(int XpTotal, int XpBonus)> FinalizarSesion(int sesionId, int ciclosCompletados);
        Task CancelarSesion(int sesionId);
        Task<ConfiguracionPomodoro> ObtenerConfiguracion(int usuarioId);
        Task ActualizarConfiguracion(int usuarioId, ActualizarConfiguracionPomodoroDto dto);
        Task<string> ObtenerTipAleatorio();
        Task<SesionPomodoro?> ObtenerSesion(int sesionId);
        Task<List<SesionPomodoro>> ObtenerSesionesHoyAsync(int usuarioId);
        Task<List<TareaPomodoro>> ObtenerTareasEnfoqueAsync(int usuarioId);
        Task<List<SesionPomodoro>> ObtenerHistorialAsync(int usuarioId, DateTime desde, DateTime hasta, int pagina = 1, int tamano = 20);
        Task<int> ObtenerRachaActualAsync(int usuarioId);
        Task<EstadisticasPomodoroPeriodo> ObtenerEstadisticasPeriodoAsync(int usuarioId, DateTime desde, DateTime hasta);
        Task<List<EstadisticasPomodoroPeriodo>> ObtenerEstadisticasSemanalesAsync(int usuarioId);
    }
}
