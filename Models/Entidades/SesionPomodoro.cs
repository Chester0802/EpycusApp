namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class SesionPomodoro
    {
        public int Id { get; set; }
        public DateTime FechaInicio { get; set; }
        public DateTime? FechaFin { get; set; }
        public int CiclosCompletados { get; set; } = 0;
        public int XpOtorgado { get; set; } = 0;
        public bool FueCompletada { get; set; } = false;
        public int UsuarioId { get; set; }
        public int? HabitoId { get; set; }
        public int? MisionId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Habito? Habito { get; set; }
        public Mision? Mision { get; set; }
    }
}
