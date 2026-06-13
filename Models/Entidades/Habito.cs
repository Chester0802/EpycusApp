namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class Habito
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public string Frecuencia { get; set; } = string.Empty;
        public string? DiasSemana { get; set; }
        public bool ConPomodoro { get; set; } = false;
        public TimeSpan? RecordatorioHora { get; set; }
        public int RachaActual { get; set; } = 0;
        public int RachaMaxima { get; set; } = 0;
        public bool EstaActivo { get; set; } = true;
        public DateTime FechaCreacion { get; set; } = DateTime.Now;
        public int UsuarioId { get; set; }
        public int CategoriaId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Categoria Categoria { get; set; } = null!;
        public ICollection<RegistroHabito> Registros { get; set; } = new List<RegistroHabito>();
        public ICollection<SesionPomodoro> SesionesPomodoro { get; set; } = new List<SesionPomodoro>();
    }
}
