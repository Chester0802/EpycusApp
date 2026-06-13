using EPYCUS_WEB_v0._1.Models.DTOs;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.ViewModels;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioPerfil
    {
        Task<Usuario?> ObtenerPerfil(int usuarioId);
        Task<PerfilViewModel?> ObtenerPerfilCompletoAsync(int usuarioId);
        Task<List<PersonajePerfilItem>> ObtenerPersonajesDisponiblesAsync(int usuarioId);
        Task ActualizarPerfil(PerfilViewModel modelo, int usuarioId);
        Task<RespuestaOperacion> ActualizarPerfilAsync(int usuarioId, ActualizarPerfilViewModel modelo);
        Task CambiarPersonaje(int personajeId, int usuarioId);
        Task<string> ObtenerImagenPersonajeActual(int usuarioId);
        Task<RespuestaOperacion> CambiarTemaAsync(int usuarioId, int temaId);
    }
}
