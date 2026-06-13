namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class ImagenNivelPersonaje
    {
        public int Id { get; set; }
        public int NivelNumero { get; set; }
        public string ImagenUrl { get; set; } = string.Empty;
        public bool EsPlaceholder { get; set; } = false;
        public int PersonajeId { get; set; }
        public Personaje Personaje { get; set; } = null!;
    }
}
