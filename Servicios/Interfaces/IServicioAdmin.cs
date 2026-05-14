using EPYCUS_WEB_v0._1.Modelos.Entidades;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
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
    }
}
