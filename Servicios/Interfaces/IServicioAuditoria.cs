namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioAuditoria
    {
        Task RegistrarAsync(string accion, string? detalle, int? usuarioId, string? direccionIp = null);
    }
}
