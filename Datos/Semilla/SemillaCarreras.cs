using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaCarreras
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Carreras.AnyAsync()) return;

            contexto.Carreras.AddRange(
                new Carrera { Nombre = "Ingeniería de Sistemas", Area = "Ingeniería", Codigo = "ing-sistemas", EstaActiva = true },
                new Carrera { Nombre = "Ingeniería Civil", Area = "Ingeniería", Codigo = "ing-civil", EstaActiva = true },
                new Carrera { Nombre = "Ingeniería Industrial", Area = "Ingeniería", Codigo = "ing-industrial", EstaActiva = true },
                new Carrera { Nombre = "Administración de Empresas", Area = "Administración", Codigo = "administracion", EstaActiva = true },
                new Carrera { Nombre = "Contabilidad", Area = "Negocios", Codigo = "contabilidad", EstaActiva = true },
                new Carrera { Nombre = "Derecho", Area = "Legal", Codigo = "derecho", EstaActiva = true },
                new Carrera { Nombre = "Medicina Humana", Area = "Salud", Codigo = "medicina", EstaActiva = true },
                new Carrera { Nombre = "Enfermería", Area = "Salud", Codigo = "enfermeria", EstaActiva = true },
                new Carrera { Nombre = "Psicología", Area = "Salud", Codigo = "psicologia", EstaActiva = true },
                new Carrera { Nombre = "Educación", Area = "Educación", Codigo = "educacion", EstaActiva = true },
                new Carrera { Nombre = "Arquitectura", Area = "Arquitectura", Codigo = "arquitectura", EstaActiva = true },
                new Carrera { Nombre = "Comunicaciones", Area = "Comunicaciones", Codigo = "comunicaciones", EstaActiva = true });

            await contexto.SaveChangesAsync();
        }
    }
}
