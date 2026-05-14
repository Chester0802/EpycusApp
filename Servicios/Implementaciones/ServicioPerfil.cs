using EPYCUS_WEB_v0._1.Modelos.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioPerfil : IServicioPerfil
    {
        public Task<Usuario?> ObtenerPerfil(int usuarioId)
        {
            throw new NotImplementedException();
        }

        public Task ActualizarPerfil(PerfilViewModel modelo, int usuarioId)
        {
            throw new NotImplementedException();
        }

        public Task CambiarPersonaje(int personajeId, int usuarioId)
        {
            throw new NotImplementedException();
        }

        public Task<string> ObtenerImagenPersonajeActual(int usuarioId)
        {
            throw new NotImplementedException();
        }
    }
}
