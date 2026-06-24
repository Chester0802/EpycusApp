using EpycusApp.Models.Entidades;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioCache
    {
        Task<List<Carrera>> ObtenerCarrerasAsync();
        Task<List<Nivel>> ObtenerNivelesAsync();
        Task<List<Categoria>> ObtenerCategoriasAsync();
        Task<List<FraseMotivacional>> ObtenerFrasesMotivacionalesAsync();
        void LimpiarCache();
    }
}
