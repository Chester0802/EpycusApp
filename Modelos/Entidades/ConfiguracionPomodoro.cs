namespace EPYCUS_WEB_v0._1.Modelos.Entidades
{
    public class ConfiguracionPomodoro
    {
        public int Id { get; set; }
        public int TiempoEstudioMin { get; set; } = 25;
        public int TiempoDescansoMin { get; set; } = 5;
        public int TiempoDescansoLargoMin { get; set; } = 15;
        public int CiclosAntesDescansoLargo { get; set; } = 4;
        public bool SonidoActivo { get; set; } = true;
        public DateTime FechaActualizacion { get; set; } = DateTime.Now;
        public int UsuarioId { get; set; }
        public Usuario Usuario { get; set; } = null!;
    }
}
