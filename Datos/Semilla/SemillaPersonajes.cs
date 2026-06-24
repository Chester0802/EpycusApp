using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaPersonajes
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Personajes.AnyAsync()) return;

            var carreraSistemasId = await contexto.Carreras
                .Where(c => c.Codigo == "ing-sistemas")
                .Select(c => c.Id)
                .FirstOrDefaultAsync();

            contexto.Personajes.AddRange(
                new Personaje { Nombre = "Kai", Genero = "Masculino", CarreraId = carreraSistemasId, EstaActivo = true },
                new Personaje { Nombre = "Luna", Genero = "Femenino", CarreraId = carreraSistemasId, EstaActivo = true },
                new Personaje { Nombre = "Ares", Genero = "Masculino", CarreraId = null, EstaActivo = true },
                new Personaje { Nombre = "Nova", Genero = "Femenino", CarreraId = null, EstaActivo = true });

            await contexto.SaveChangesAsync();
            await SembrarImagenesAsync(contexto);
        }

        private static async Task SembrarImagenesAsync(ContextoAplicacion contexto)
        {
            if (await contexto.ImagenesNivelPersonaje.AnyAsync()) return;

            var kaiId = await contexto.Personajes.Where(p => p.Nombre == "Kai").Select(p => p.Id).FirstOrDefaultAsync();
            var lunaId = await contexto.Personajes.Where(p => p.Nombre == "Luna").Select(p => p.Id).FirstOrDefaultAsync();
            var aresId = await contexto.Personajes.Where(p => p.Nombre == "Ares").Select(p => p.Id).FirstOrDefaultAsync();
            var novaId = await contexto.Personajes.Where(p => p.Nombre == "Nova").Select(p => p.Id).FirstOrDefaultAsync();

            var imagenes = new List<ImagenNivelPersonaje>
            {
                new() { PersonajeId = kaiId, NivelNumero = 0, ImagenUrl = "/img/personajes/ing-sistemas/masculino/IngSis_mas_nivel1.png", EsPlaceholder = false },
                new() { PersonajeId = lunaId, NivelNumero = 0, ImagenUrl = "/img/personajes/ing-sistemas/femenino/IngSis_fem_nivel1.png", EsPlaceholder = false },
                new() { PersonajeId = aresId, NivelNumero = 0, ImagenUrl = "/img/personajes/generico/masculino/placeholder.png", EsPlaceholder = true },
                new() { PersonajeId = novaId, NivelNumero = 0, ImagenUrl = "/img/personajes/generico/femenino/placeholder.png", EsPlaceholder = true }
            };

            var todasCarreras = await contexto.Carreras.Where(c => c.EstaActiva).ToListAsync();
            var generos = new[] { "masculino", "femenino" };

            foreach (var carrera in todasCarreras)
            {
                if (carrera.Codigo == "ing-sistemas") continue;

                foreach (var genero in generos)
                {
                    var carpeta = carrera.Codigo;
                    var prefijo = CarreraPrefijoImagen(carrera.Codigo);
                    var gen = genero[..3];
                    var nombreArchivo = $"{prefijo}_{gen}_nivel1.png";

                    var webRoot = Path.Combine(Directory.GetCurrentDirectory(), "wwwroot");
                    var rutaArchivo = Path.Combine(webRoot, "img", "personajes", carpeta, genero, nombreArchivo);
                    var existeArchivo = File.Exists(rutaArchivo);

                    imagenes.Add(new ImagenNivelPersonaje
                    {
                        PersonajeId = genero == "masculino" ? aresId : novaId,
                        NivelNumero = 0,
                        ImagenUrl = $"/img/personajes/{carpeta}/{genero}/{nombreArchivo}",
                        EsPlaceholder = !existeArchivo
                    });
                }
            }

            contexto.ImagenesNivelPersonaje.AddRange(imagenes);
            await contexto.SaveChangesAsync();
        }

        private static string CarreraPrefijoImagen(string codigo) => codigo switch
        {
            "ing-civil" => "IngCiv",
            "ing-industrial" => "IngInd",
            "administracion" => "Admin",
            "contabilidad" => "Contab",
            "derecho" => "Derec",
            "medicina" => "Medici",
            "enfermeria" => "Enferm",
            "psicologia" => "Psicol",
            "educacion" => "Educac",
            "arquitectura" => "Arquit",
            "comunicaciones" => "Comunic",
            _ => codigo.Length > 6 ? codigo[..6] : codigo
        };
    }
}
