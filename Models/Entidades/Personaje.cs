namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class Personaje
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Genero { get; set; } = string.Empty;
        public bool EstaActivo { get; set; } = true;
        public int? CarreraId { get; set; }
        public Carrera? Carrera { get; set; }
        public ICollection<ImagenNivelPersonaje> Imagenes { get; set; } = new List<ImagenNivelPersonaje>();
        public ICollection<PersonajeUsuario> PersonajesUsuario { get; set; } = new List<PersonajeUsuario>();
    }
}
