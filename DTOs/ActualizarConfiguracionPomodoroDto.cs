using System.ComponentModel.DataAnnotations;

namespace EpycusApp.DTOs
{
    public class ActualizarConfiguracionPomodoroDto : IValidatableObject
    {
        private static readonly string[] SonidosPermitidos = ["campana", "digital", "naturaleza", "silencio"];

        [Range(1, 180, ErrorMessage = "El tiempo de estudio debe ser entre 1 y 180 minutos.")]
        public int TiempoEstudioMin { get; set; }

        [Range(1, 60, ErrorMessage = "El tiempo de descanso debe ser entre 1 y 60 minutos.")]
        public int TiempoDescansoMin { get; set; }

        [Range(1, 120, ErrorMessage = "El tiempo de descanso largo debe ser entre 1 y 120 minutos.")]
        public int TiempoDescansoLargoMin { get; set; }

        [Range(1, 20, ErrorMessage = "Los ciclos antes del descanso largo deben ser entre 1 y 20.")]
        public int CiclosAntesDescansoLargo { get; set; }

        public bool SonidoActivo { get; set; }

        [Required]
        public string SonidoSeleccionado { get; set; } = "campana";

        [Range(0, 100, ErrorMessage = "El volumen debe ser entre 0 y 100.")]
        public int Volumen { get; set; } = 100;

        public bool AutoIniciarDescanso { get; set; }
        public bool AutoIniciarEnfoque { get; set; }
        public bool TicTacActivo { get; set; }

        [Range(0, 50, ErrorMessage = "La meta diaria debe ser entre 0 y 50 ciclos.")]
        public int MetaDiariaCiclos { get; set; }

        [Range(1, 180, ErrorMessage = "El modo personalizado debe ser entre 1 y 180 minutos.")]
        public int ModoPersonalizadoMin { get; set; } = 25;

        public bool VibracionActiva { get; set; } = true;
        public bool NotificacionDesktop { get; set; } = true;

        public IEnumerable<ValidationResult> Validate(ValidationContext validationContext)
        {
            if (!SonidosPermitidos.Contains(SonidoSeleccionado))
            {
                yield return new ValidationResult(
                    $"El sonido seleccionado no es válido. Valores permitidos: {string.Join(", ", SonidosPermitidos)}.",
                    [nameof(SonidoSeleccionado)]);
            }

            if (TiempoDescansoMin >= TiempoEstudioMin)
            {
                yield return new ValidationResult(
                    "El tiempo de descanso debe ser menor que el tiempo de estudio.",
                    [nameof(TiempoDescansoMin)]);
            }

            if (TiempoDescansoLargoMin <= TiempoDescansoMin)
            {
                yield return new ValidationResult(
                    "El tiempo de descanso largo debe ser mayor que el tiempo de descanso corto.",
                    [nameof(TiempoDescansoLargoMin)]);
            }


        }
    }
}
