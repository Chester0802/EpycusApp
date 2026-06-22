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
        Task<(bool Exito, string Mensaje)> RevertirMision(int id, int usuarioId);
        Task<List<SubTarea>> ObtenerSubTareas(int misionId, int usuarioId);
        Task<SubTarea?> ObtenerSubTareaPorId(int id, int usuarioId);
        Task CrearSubTarea(string nombre, string? descripcion, int misionId, int usuarioId);
        Task EditarSubTarea(int id, string nombre, string? descripcion, int? orden, int usuarioId);
        Task CompletarSubTarea(int id, int usuarioId);
        Task DescompletarSubTarea(int id, int usuarioId);
        Task EliminarSubTarea(int id, int usuarioId);
        Task<int> ObtenerTiempoEnfoqueSubTarea(int id, int usuarioId);
        Task<int> ObtenerTiempoEnfoqueMision(int misionId, int usuarioId);
    }
}
