namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioCorreo
{
    Task EnviarVerificacion(string correo, string nombre, string token);
    Task EnviarRecuperacion(string correo, string nombre, string token);
    Task EnviarBienvenida(string correo, string nombre);
}
}
