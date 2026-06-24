using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaAdmin
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Usuarios.AnyAsync(u => u.CorreoElectronico == "admin@epycus.es"))
                return;

            var rolAdmin = await contexto.Roles.FirstOrDefaultAsync(r => r.Nombre == "Admin");
            if (rolAdmin == null)
            {
                contexto.Roles.Add(new Rol { Nombre = "Admin" });
                await contexto.SaveChangesAsync();
                rolAdmin = await contexto.Roles.FirstOrDefaultAsync(r => r.Nombre == "Admin");
                if (rolAdmin == null) return;
            }

            var carreraAdmin = await contexto.Carreras.FirstOrDefaultAsync();
            if (carreraAdmin == null) return;

            var admin = new Usuario
            {
                CodigoUnico = "ADMIN-001",
                Nombre = "Administrador",
                CorreoElectronico = "admin@epycus.es",
                ContrasenaHash = BCrypt.Net.BCrypt.HashPassword("Admin123!", workFactor: 12),
                FechaNacimiento = new DateOnly(2000, 1, 1),
                Genero = "otro",
                CorreoVerificado = true,
                AceptoTerminos = true,
                EstaActivo = true,
                FechaRegistro = DateTime.UtcNow,
                RolId = rolAdmin.Id,
                CarreraId = carreraAdmin.Id
            };

            contexto.Usuarios.Add(admin);
            await contexto.SaveChangesAsync();
        }
    }
}
