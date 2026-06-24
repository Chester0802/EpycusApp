using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaTemas
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Temas.AnyAsync()) return;

            contexto.Temas.AddRange(
                new Tema { Nombre = "Noche Épica", Modo = "Oscuro", ArchivoCss = "tema-noche-epica.css", EsPremium = false, Precio = 0, EstaActivo = true },
                new Tema { Nombre = "Sakura", Modo = "Claro", ArchivoCss = "tema-sakura.css", EsPremium = false, Precio = 0, EstaActivo = true });

            await contexto.SaveChangesAsync();
        }
    }
}
