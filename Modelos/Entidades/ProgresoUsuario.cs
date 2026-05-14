namespace EPYCUS_WEB_v0._1.Modelos.Entidades
{
    public class ProgresoUsuario
    {
        public int Id { get; set; }
        public int XpTotal { get; set; } = 0;
        public int RachaActual { get; set; } = 0;
        public int RachaMaxima { get; set; } = 0;
        public DateTime? FechaUltimaActividad { get; set; }
        public DateTime? FechaInicioRacha { get; set; }
        public bool DiaDeGraciaUsado { get; set; } = false;
        public decimal ProductividadDiaria { get; set; } = 0;
        public int UsuarioId { get; set; }
        public int NivelActualId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Nivel NivelActual { get; set; } = null!;
    }
}
