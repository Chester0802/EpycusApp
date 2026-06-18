namespace EpycusApp.ViewModels
{
    using System;
    using System.Collections.Generic;
    using System.ComponentModel.DataAnnotations;

    public class EditarHabitoViewModel
    {
        [Required]
        public int Id { get; set; }

        [Required(ErrorMessage = "El nombre es obligatorio")]
        [StringLength(200, ErrorMessage = "El nombre no puede tener más de 200 caracteres")]
        public string Nombre { get; set; } = string.Empty;

        [StringLength(500, ErrorMessage = "La descripción no puede tener más de 500 caracteres")]
        public string? Descripcion { get; set; }

        [Required(ErrorMessage = "Seleccione una categoría")]
        public int CategoriaId { get; set; }

        [Required(ErrorMessage = "Seleccione la frecuencia")]
        public string Frecuencia { get; set; } = "Diaria";

        public List<int>? DiasSemana { get; set; }

        public bool ConPomodoro { get; set; } = false;

        [DataType(DataType.Time)]
        public TimeSpan? RecordatorioHora { get; set; }

        public bool EstaActivo { get; set; } = true;
    }
}
