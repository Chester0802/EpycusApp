using System.ComponentModel.DataAnnotations;

namespace EpycusApp.DTOs
{
    public class ActualizarConfiguracionPomodoroDto
    {
        [Range(1, 120, ErrorMessage = "El tiempo de estudio debe ser entre 1 y 120 minutos.")]
        public int TiempoEstudioMin { get; set; }

        [Range(1, 60, ErrorMessage = "El tiempo de descanso debe ser entre 1 y 60 minutos.")]
        public int TiempoDescansoMin { get; set; }

        [Range(1, 120, ErrorMessage = "El tiempo de descanso largo debe ser entre 1 y 120 minutos.")]
        public int TiempoDescansoLargoMin { get; set; }

        [Range(1, 20, ErrorMessage = "Los ciclos antes del descanso largo deben ser entre 1 y 20.")]
        public int CiclosAntesDescansoLargo { get; set; }

        public bool SonidoActivo { get; set; }
    }
}
