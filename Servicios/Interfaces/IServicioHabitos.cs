using EPYCUS_WEB_v0._1.Modelos.Entidades;
using EPYCUS_WEB_v0._1.ViewModels;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioHabitos
    {
        Task<List<Habito>> ObtenerHabitosDeUsuario(int usuarioId);
        Task<List<EPYCUS_WEB_v0._1.ViewModels.HabitoViewModel>> ObtenerHabitosViewModel(int usuarioId);
        Task<List<Categoria>> ObtenerCategoriasActivas();
        Task<Habito?> ObtenerPorId(int id);
        Task<EPYCUS_WEB_v0._1.ViewModels.HabitoViewModel?> ObtenerPorIdViewModel(int id);
        Task CrearHabito(CrearHabitoViewModel modelo, int usuarioId);
        Task EditarHabito(EditarHabitoViewModel modelo, int usuarioId);
        Task EliminarHabito(int id, int usuarioId);
        Task<(bool Exito, int XpGanado)> CompletarHabito(int id, int usuarioId);
        Task<(bool Exito, string Mensaje)> FallarHabito(int id, int usuarioId);
        Task<EPYCUS_WEB_v0._1.ViewModels.HabitosDashboardViewModel> ObtenerDashboard(int usuarioId);
    }
}
