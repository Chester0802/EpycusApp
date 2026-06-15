namespace EpycusApp.DTOs
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
