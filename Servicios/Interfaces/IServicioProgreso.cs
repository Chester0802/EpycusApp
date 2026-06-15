using EpycusApp.Models.Entidades;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioProgreso
    {
        Task<ProgresoUsuario> ObtenerProgreso(int usuarioId);
        Task<List<LogroUsuario>> ObtenerLogrosUsuario(int usuarioId);
        Task<List<EstadoAnimo>> ObtenerHistorialAnimo(int usuarioId);
        Task<Nivel?> ObtenerNivelSiguiente(int nivelActualNumero);
        Task<List<Logro>> ObtenerTodosLosLogros();
        Task<string> ObtenerImagenPersonaje(int usuarioId, int nivelActual);
        Task<Nivel?> ObtenerNivelInicialAsync();
    }
}
