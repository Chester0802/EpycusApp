using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
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
        Task<List<LogroUsuario>> ObtenerLogrosUsuarioConLogroAsync(int usuarioId);
    }
}
