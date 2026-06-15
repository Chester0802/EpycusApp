namespace EPYCUS_WEB_v0._1.DTOs
{
    public class ActualizarConfiguracionPomodoroDto
    {
        public int TiempoEstudioMin { get; set; }
        public int TiempoDescansoMin { get; set; }
        public int TiempoDescansoLargoMin { get; set; }
        public int CiclosAntesDescansoLargo { get; set; }
        public bool SonidoActivo { get; set; }
    }
}
