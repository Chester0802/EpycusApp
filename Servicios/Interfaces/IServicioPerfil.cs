using EPYCUS_WEB_v0._1.Modelos.Entidades;
using EPYCUS_WEB_v0._1.ViewModels;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioPerfil
    {
        Task<Usuario?> ObtenerPerfil(int usuarioId);
        Task ActualizarPerfil(PerfilViewModel modelo, int usuarioId);
        Task CambiarPersonaje(int personajeId, int usuarioId);
        Task<string> ObtenerImagenPersonajeActual(int usuarioId);
    }
}
