namespace EpycusApp.ViewModels
{
    public class CrearMisionViewModel
    {
        [System.ComponentModel.DataAnnotations.Required(ErrorMessage = "El nombre de la misión es obligatorio.")]
        [System.ComponentModel.DataAnnotations.MaxLength(200, ErrorMessage = "El nombre no puede exceder 200 caracteres.")]
        public string Nombre { get; set; } = string.Empty;

        [System.ComponentModel.DataAnnotations.MaxLength(500, ErrorMessage = "La descripción no puede exceder 500 caracteres.")]
        public string? Descripcion { get; set; }

        [System.ComponentModel.DataAnnotations.MaxLength(150, ErrorMessage = "El nombre del curso no puede exceder 150 caracteres.")]
        public string? NombreCurso { get; set; }

        [System.ComponentModel.DataAnnotations.Required(ErrorMessage = "Fecha límite es obligatoria.")]
        [System.ComponentModel.DataAnnotations.DataType(System.ComponentModel.DataAnnotations.DataType.Date)]
        public System.DateTime FechaLimite { get; set; }

        [System.ComponentModel.DataAnnotations.Required(ErrorMessage = "Selecciona una prioridad.")]
        public string Prioridad { get; set; } = "Media"; // "Baja", "Media", "Alta"

        public bool ConPomodoro { get; set; } = false;

        [System.ComponentModel.DataAnnotations.Required(ErrorMessage = "Selecciona una categoría.")]
        public int CategoriaId { get; set; }
    }
}
