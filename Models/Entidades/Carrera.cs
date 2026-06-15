namespace EpycusApp.Models.Entidades
{
    public class Carrera
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Area { get; set; } = string.Empty;
        public string Codigo { get; set; } = string.Empty;
        public bool EstaActiva { get; set; } = true;
        public ICollection<Usuario> Usuarios { get; set; } = new List<Usuario>();
    }
}
