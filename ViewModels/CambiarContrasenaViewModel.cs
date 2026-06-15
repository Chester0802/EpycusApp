using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels
{
    public class CambiarContrasenaViewModel
    {
        [Required(ErrorMessage = "La contraseÃ±a actual es requerida.")]
        public string ContrasenaActual { get; set; } = string.Empty;

        [Required(ErrorMessage = "La nueva contraseÃ±a es requerida.")]
        [StringLength(100, MinimumLength = 6, ErrorMessage = "La contraseÃ±a debe tener al menos 6 caracteres.")]
        public string NuevaContrasena { get; set; } = string.Empty;

        [Required(ErrorMessage = "Debe confirmar la nueva contraseÃ±a.")]
        [Compare("NuevaContrasena", ErrorMessage = "Las contraseÃ±as no coinciden.")]
        public string ConfirmarContrasena { get; set; } = string.Empty;
    }
}
