namespace EpycusApp.Models.Entidades
{
    public class SesionPomodoro
    {
        public int Id { get; set; }
        public DateTime FechaInicio { get; set; }
        public DateTime? FechaFin { get; set; }
        public int CiclosCompletados { get; set; } = 0;
        public int XpOtorgado { get; set; } = 0;
        public bool FueCompletada { get; set; } = false;
        public string Tipo { get; set; } = "Enfoque";
        public int UsuarioId { get; set; }
        public int? HabitoId { get; set; }
        public int? MisionId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Habito? Habito { get; set; }
        public Mision? Mision { get; set; }
    }
}
