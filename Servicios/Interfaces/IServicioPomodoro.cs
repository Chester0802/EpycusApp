using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioPomodoro
    {
        Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId, int? subTareaId = null);
        Task<(bool Exito, SesionPomodoro? Sesion, string? Error)> IniciarSesionSiNoActiva(int usuarioId, int? habitoId, int? misionId, int? subTareaId = null);
        Task<List<SubTarea>> ObtenerSubTareasDisponibles(int usuarioId, int misionId);
        Task<(int XpGanado, bool SugerirDescanso, string? PausaActiva)> RegistrarCiclo(int sesionId, int ciclosCompletados, int usuarioId);
        Task<(int XpTotal, int XpBonus)> FinalizarSesion(int sesionId, int ciclosCompletados, int usuarioId);
        Task CancelarSesion(int sesionId, int usuarioId);
        Task<SesionPomodoro> CrearSesionDescanso(int usuarioId, string tipoDescanso, int segundos);
        Task<ConfiguracionPomodoro> ObtenerConfiguracion(int usuarioId);
        Task ActualizarConfiguracion(int usuarioId, ActualizarConfiguracionPomodoroDto dto);
        Task<string> ObtenerTipAleatorio();
        Task<SesionPomodoro?> ObtenerSesion(int sesionId);
        Task<List<SesionPomodoro>> ObtenerSesionesHoyAsync(int usuarioId);
        Task<List<TareaPomodoro>> ObtenerTareasEnfoqueAsync(int usuarioId);
        Task<List<SesionPomodoro>> ObtenerHistorialAsync(int usuarioId, DateTime desde, DateTime hasta, int pagina = 1, int tamano = 20, bool? completada = null, bool? conXp = null);
        Task<int> ObtenerRachaActualAsync(int usuarioId);
        Task<EstadisticasPomodoroPeriodo> ObtenerEstadisticasPeriodoAsync(int usuarioId, DateTime desde, DateTime hasta);
        Task<List<EstadisticasPomodoroPeriodo>> ObtenerEstadisticasSemanalesAsync(int usuarioId);
        Task<PomodoroEstadisticasAvanzadasResponse> ObtenerEstadisticasAvanzadasAsync(int usuarioId, DateTime desde, DateTime hasta);
    }
}
