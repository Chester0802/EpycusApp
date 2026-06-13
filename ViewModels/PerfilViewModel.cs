using EPYCUS_WEB_v0._1.Models.Entidades;

namespace EPYCUS_WEB_v0._1.ViewModels
{
    public class PerfilViewModel
    {
        public Usuario? Usuario { get; set; }

        // Propiedades de vista
        public string Nombre { get; set; } = string.Empty;
        public string CorreoElectronico { get; set; } = string.Empty;
        public string CodigoUnico { get; set; } = string.Empty;
        public DateTime? FechaNacimiento { get; set; }
        public string Genero { get; set; } = string.Empty;
        public int? CarreraId { get; set; }
        public string? CarreraNombre { get; set; }
        public bool UsaGoogle { get; set; }
        public string? FotoGoogleUrl { get; set; }
        public int? TemaActualId { get; set; }

        // Gamificación
        public int NivelActual { get; set; }
        public int XpTotal { get; set; }
        public int RachaActual { get; set; }
        public int RachaMaxima { get; set; }
        public DateTime FechaRegistro { get; set; }

        // Personajes
        public List<PersonajePerfilItem> PersonajesDisponibles { get; set; } = new();
    }

    public class PersonajePerfilItem
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Genero { get; set; } = string.Empty;
        public bool EsSeleccionado { get; set; }
        public bool EsPlaceholder { get; set; }
        public string? ImagenPreviewUrl { get; set; }
    }
}
