using EPYCUS_WEB_v0._1.Models.Entidades;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioBienestar
    {
        Task<List<AlertaBienestar>> ObtenerAlertasActivas(int usuarioId);
        Task<AlertaBienestar?> VerificarUsoExcesivoPomodoro(int usuarioId);
        Task<AlertaBienestar?> VerificarAnimoNegativoConsecutivo(int usuarioId);
        Task<FraseMotivacional?> ObtenerFraseMotivacionalAleatoria();

        // Métodos usados por el controlador
        Task<EstadoAnimo?> ObtenerEstadoHoy(int usuarioId);
        Task<List<EstadoAnimo>> ObtenerHistorialAnimo(int usuarioId, int dias);
        Task RegistrarEstadoAnimo(int usuarioId, string estado, string? nota);
    }
}
