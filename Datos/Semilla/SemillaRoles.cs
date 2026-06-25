using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaRoles
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Roles.AnyAsync())
                return;

            contexto.Roles.AddRange(
                new Rol { Id = 1, Nombre = "Usuario" },
                new Rol { Id = 2, Nombre = "Admin" }
            );
            await contexto.SaveChangesAsync();
        }
    }
}