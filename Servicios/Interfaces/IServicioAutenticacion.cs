using EPYCUS_WEB_v0._1.Modelos.Entidades;
using EPYCUS_WEB_v0._1.ViewModels;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioAutenticacion
    {
        Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> RegistrarUsuario(RegistroViewModel modelo);
        Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> Login(string correo, string contrasena);
        Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> RenovarToken(string refreshToken);
        Task<bool> VerificarCorreo(string token);
        Task<bool> EnviarCorreoRecuperacion(string correo);
        Task<bool> RestablecerContrasena(string token, string nuevaContrasena);
        Task CerrarSesion(int usuarioId);
        Task<List<Carrera>> ObtenerCarrerasActivas();
    }
}
