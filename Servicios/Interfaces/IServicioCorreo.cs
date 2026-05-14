namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioCorreo
    {
        Task EnviarVerificacion(string correo, string nombre, string token);
        Task EnviarRecuperacion(string correo, string nombre, string token);
    }
}
