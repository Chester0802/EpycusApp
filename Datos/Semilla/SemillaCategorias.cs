using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaCategorias
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Categorias.AnyAsync()) return;

            contexto.Categorias.AddRange(
                new Categoria { Nombre = "Salud y Bienestar", Icono = "bi-heart-pulse", Tipo = "Ambos", EstaActiva = true },
                new Categoria { Nombre = "Estudio", Icono = "bi-book", Tipo = "Ambos", EstaActiva = true },
                new Categoria { Nombre = "Ejercicio", Icono = "bi-activity", Tipo = "Habito", EstaActiva = true },
                new Categoria { Nombre = "Sueño", Icono = "bi-moon-stars", Tipo = "Habito", EstaActiva = true },
                new Categoria { Nombre = "Hidratación", Icono = "bi-droplet", Tipo = "Habito", EstaActiva = true },
                new Categoria { Nombre = "Nutrición", Icono = "bi-egg-fried", Tipo = "Habito", EstaActiva = true },
                new Categoria { Nombre = "Meditación", Icono = "bi-peace", Tipo = "Habito", EstaActiva = true },
                new Categoria { Nombre = "Tarea Académica", Icono = "bi-file-earmark-text", Tipo = "Mision", EstaActiva = true },
                new Categoria { Nombre = "Proyecto", Icono = "bi-kanban", Tipo = "Mision", EstaActiva = true },
                new Categoria { Nombre = "Lectura", Icono = "bi-book-half", Tipo = "Ambos", EstaActiva = true },
                new Categoria { Nombre = "Hábito Personal", Icono = "bi-star", Tipo = "Habito", EstaActiva = true });

            await contexto.SaveChangesAsync();
        }
    }
}
