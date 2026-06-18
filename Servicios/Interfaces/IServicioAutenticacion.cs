using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
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
        
        // Cambiar contraseña: correo + actual + nueva
        Task<(bool EsExitoso, string? Mensaje)> CambiarContrasenaAsync(string correo, string contrasenaActual, string nuevaContrasena);
    }
}
