using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioHabitos
    {
        Task<List<Habito>> ObtenerHabitosDeUsuario(int usuarioId);
        Task<List<EpycusApp.ViewModels.HabitoViewModel>> ObtenerHabitosViewModel(int usuarioId);
        Task<List<Categoria>> ObtenerCategoriasActivas();
        Task<Habito?> ObtenerPorId(int id);
        Task<EpycusApp.ViewModels.HabitoViewModel?> ObtenerPorIdViewModel(int id);
        Task CrearHabito(CrearHabitoViewModel modelo, int usuarioId);
        Task EditarHabito(EditarHabitoViewModel modelo, int usuarioId);
        Task EliminarHabito(int id, int usuarioId);
        Task<(bool Exito, int XpGanado)> CompletarHabito(int id, int usuarioId);
        Task<(bool Exito, string Mensaje)> FallarHabito(int id, int usuarioId);
        Task<EpycusApp.ViewModels.HabitosDashboardViewModel> ObtenerDashboard(int usuarioId);
        Task<List<HabitoRespuestaDto>> ObtenerHabitosConEstadoHoy(int usuarioId);
        Task<List<HabitoHoyRespuestaDto>> ObtenerHabitosActivosConEstadoHoy(int usuarioId);
        Task<List<RegistroSemanaDto>> ObtenerRegistrosSemana(int habitoId, int usuarioId);
    }
}
