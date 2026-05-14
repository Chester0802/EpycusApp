namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioBienestar
    {
        Task<List<AlertaBienestar>> ObtenerAlertasActivas(int usuarioId);
        Task<AlertaBienestar?> VerificarUsoExcesivoPomodoro(int usuarioId);
        Task<AlertaBienestar?> VerificarAnimoNegativoConsecutivo(int usuarioId);
    }
}
