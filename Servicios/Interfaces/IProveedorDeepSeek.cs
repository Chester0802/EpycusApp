using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IProveedorDeepSeek
    {
        Task<string> LlamarAsync(ContextoUsuarioIA ctx, List<MensajeIA> historial, string? resumen = null);
    }
}
