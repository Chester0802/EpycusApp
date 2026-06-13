namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class Categoria
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Icono { get; set; } = string.Empty;
        public string Tipo { get; set; } = string.Empty;
        public bool EstaActiva { get; set; } = true;
        public ICollection<Habito> Habitos { get; set; } = new List<Habito>();
        public ICollection<Mision> Misiones { get; set; } = new List<Mision>();
    }
}
