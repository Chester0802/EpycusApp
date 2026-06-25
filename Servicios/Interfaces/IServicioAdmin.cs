using EpycusApp.Models.Entidades;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioAdmin
    {
        Task<List<Usuario>> ObtenerTodosUsuarios();
        Task<Usuario?> ObtenerUsuarioPorId(int id);
        Task ActivarSuscripcion(int usuarioId, int adminId);
        Task DesactivarSuscripcion(int usuarioId);
        Task<List<FraseMotivacional>> ObtenerFrases();
        Task CrearFrase(string frase, string autor);
        Task EliminarFrase(int id);
        Task<bool> EsAdministrador(string correo);
    }
}
