namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioGamificacion
    {
        Task<(int XpGanado, bool SubioDeNivel, int NivelNuevo)> SumarXP(int usuarioId, int xp);
        Task VerificarYOtorgarLogros(int usuarioId);
        Task ActualizarRacha(int usuarioId);
        Task<decimal> CalcularProductividadDiaria(int usuarioId);
    }
}
