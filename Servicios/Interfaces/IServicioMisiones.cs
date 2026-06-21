using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioMisiones
    {
        Task<List<Mision>> ObtenerMisionesDeUsuario(int usuarioId);
        Task<Mision?> ObtenerPorId(int id);
        Task CrearMision(CrearMisionViewModel modelo, int usuarioId);
        Task EditarMision(EditarMisionViewModel modelo, int usuarioId);
        Task EliminarMision(int id, int usuarioId);
        Task<(bool Exito, int XpGanado)> CompletarMision(int id, int usuarioId);
        Task CambiarEstado(int id, string estado, int usuarioId);
        Task<List<Categoria>> ObtenerCategoriasMisionAsync();
        Task<int> ContarCompletadasHoyAsync(int usuarioId);
    }
}
