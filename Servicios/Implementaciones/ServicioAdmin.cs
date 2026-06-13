using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioAdmin : IServicioAdmin
    {
        private readonly Datos.ContextoAplicacion _contexto;

        public ServicioAdmin(Datos.ContextoAplicacion contexto)
        {
            _contexto = contexto;
        }

        public async Task<List<Usuario>> ObtenerTodosUsuarios()
        {
            return await _contexto.Usuarios
                .Include(u => u.Rol)
                .Include(u => u.Carrera)
                .Include(u => u.Suscripciones)
                .ToListAsync();
        }

        public async Task<Usuario?> ObtenerUsuarioPorId(int id)
        {
            return await _contexto.Usuarios
                .Include(u => u.Rol)
                .Include(u => u.Carrera)
                .Include(u => u.Suscripciones)
                .FirstOrDefaultAsync(u => u.Id == id);
        }

        public async Task ActivarSuscripcion(int usuarioId, int adminId)
        {
            var usuario = await _contexto.Usuarios
                .Include(u => u.Suscripciones)
                .FirstOrDefaultAsync(u => u.Id == usuarioId);

            if (usuario == null)
            {
                return;
            }

            var vigente = usuario.Suscripciones.FirstOrDefault(s => s.EstaActiva);
            if (vigente != null)
            {
                vigente.EstaActiva = false;
                vigente.FechaFin = DateOnly.FromDateTime(DateTime.Today);
            }

            _contexto.Suscripciones.Add(new Suscripcion
            {
                UsuarioId = usuarioId,
                Plan = "Premium",
                PrecioSoles = 0,
                FechaInicio = DateOnly.FromDateTime(DateTime.Today),
                FechaFin = DateOnly.FromDateTime(DateTime.Today.AddYears(1)),
                EstaActiva = true,
                ActivadaPorAdminId = adminId,
                FechaActivacion = DateTime.UtcNow
            });

            await _contexto.SaveChangesAsync();
        }

        public async Task DesactivarSuscripcion(int usuarioId)
        {
            var suscripcion = await _contexto.Suscripciones
                .FirstOrDefaultAsync(s => s.UsuarioId == usuarioId && s.EstaActiva);

            if (suscripcion == null)
            {
                return;
            }

            suscripcion.EstaActiva = false;
            suscripcion.FechaFin = DateOnly.FromDateTime(DateTime.Today);
            await _contexto.SaveChangesAsync();
        }

        public async Task<List<FraseMotivacional>> ObtenerFrases()
        {
            return await _contexto.FrasesMotivacionales
                .OrderByDescending(f => f.EstaActiva)
                .ThenBy(f => f.Frase)
                .ToListAsync();
        }

        public async Task CrearFrase(string frase, string autor)
        {
            if (string.IsNullOrWhiteSpace(frase))
            {
                return;
            }

            _contexto.FrasesMotivacionales.Add(new FraseMotivacional
            {
                Frase = frase.Trim(),
                Autor = string.IsNullOrWhiteSpace(autor) ? "Anónimo" : autor.Trim(),
                EstaActiva = true
            });

            await _contexto.SaveChangesAsync();
        }

        public async Task EliminarFrase(int id)
        {
            var frase = await _contexto.FrasesMotivacionales.FirstOrDefaultAsync(f => f.Id == id);
            if (frase == null)
            {
                return;
            }

            _contexto.FrasesMotivacionales.Remove(frase);
            await _contexto.SaveChangesAsync();
        }
    }
}
