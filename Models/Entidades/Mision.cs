namespace EpycusApp.Models.Entidades
{
    public class Mision
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public string? NombreCurso { get; set; }
        public DateOnly FechaLimite { get; set; }
        public string Prioridad { get; set; } = "Media";
        public string Estado { get; set; } = "Pendiente";
        public bool ConPomodoro { get; set; } = false;
        public int XpOtorgado { get; set; } = 0;
        public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
        public DateTime? FechaCompletado { get; set; }
        public int UsuarioId { get; set; }
        public int CategoriaId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Categoria Categoria { get; set; } = null!;
        public ICollection<SesionPomodoro> SesionesPomodoro { get; set; } = new List<SesionPomodoro>();
    }
}
