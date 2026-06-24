using EpycusApp.Datos;
using EpycusApp.Servicios.Interfaces;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioAuditoria : IServicioAuditoria
    {
        private readonly ContextoAplicacion _contexto;

        public ServicioAuditoria(ContextoAplicacion contexto)
        {
            _contexto = contexto;
        }

        public async Task RegistrarAsync(string accion, string? detalle, int? usuarioId, string? direccionIp = null)
        {
            _contexto.Logs.Add(new Models.Entidades.Log
            {
                Accion = accion,
                Detalle = detalle,
                UsuarioId = usuarioId,
                DireccionIp = direccionIp,
                FechaRegistro = DateTime.UtcNow
            });
            await _contexto.SaveChangesAsync();
        }
    }
}
