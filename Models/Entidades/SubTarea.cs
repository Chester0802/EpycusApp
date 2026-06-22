namespace EpycusApp.Models.Entidades
{
    public class SubTarea
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public bool EstaCompletada { get; set; } = false;
        public int Orden { get; set; } = 0;
        public int TiempoEnfoqueSegundos { get; set; } = 0;
        public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
        public DateTime? FechaCompletado { get; set; }
        public int MisionId { get; set; }
        public Mision Mision { get; set; } = null!;
        public ICollection<SesionPomodoro> SesionesPomodoro { get; set; } = new List<SesionPomodoro>();
    }
}
