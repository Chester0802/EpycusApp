namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class Tema
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public string Modo { get; set; } = string.Empty;
        public string ArchivoCss { get; set; } = string.Empty;
        public string? ImagenPreviewUrl { get; set; }
        public bool EsPremium { get; set; } = false;
        public decimal Precio { get; set; } = 0;
        public bool EstaActivo { get; set; } = true;
    }
}
