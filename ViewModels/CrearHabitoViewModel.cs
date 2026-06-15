namespace EpycusApp.ViewModels
{
    using System;
    using System.Collections.Generic;
    using System.ComponentModel.DataAnnotations;

    public class CrearHabitoViewModel
    {
        [Required(ErrorMessage = "El nombre es obligatorio")]
        [StringLength(200, ErrorMessage = "El nombre no puede tener mÃ¡s de 200 caracteres")]
        public string Nombre { get; set; } = string.Empty;

        [StringLength(500, ErrorMessage = "La descripciÃ³n no puede tener mÃ¡s de 500 caracteres")]
        public string? Descripcion { get; set; }

        [Range(1, int.MaxValue, ErrorMessage = "Seleccione una categorÃ­a")]
        public int CategoriaId { get; set; }

        [Required(ErrorMessage = "Seleccione la frecuencia")]
        [Display(Name = "Frecuencia")]
        public string Frecuencia { get; set; } = "Diaria"; // Diaria | Semanal | Personalizada

        [Display(Name = "DÃ­as de la semana")]
        public List<int>? DiasSemana { get; set; } // 1=Lun ... 7=Dom (se usa cuando Frecuencia = "Semanal" o "Personalizada")

        [Display(Name = "Usar Pomodoro")]
        public bool ConPomodoro { get; set; } = false;

        [DataType(DataType.Time)]
        [Display(Name = "Hora de recordatorio")]
        public TimeSpan? RecordatorioHora { get; set; }

        [Display(Name = "Activo")]
        public bool EstaActivo { get; set; } = true;
    }
}
