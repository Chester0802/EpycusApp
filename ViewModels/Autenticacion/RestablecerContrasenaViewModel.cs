using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels.Autenticacion
{
    public class RestablecerContrasenaViewModel
    {
        [Required]
        public string Token { get; set; } = string.Empty;

        [Required(ErrorMessage = "La nueva contraseÃ±a es obligatoria")]
        [MinLength(6, ErrorMessage = "MÃ­nimo 6 caracteres")]
        [Display(Name = "Nueva contraseÃ±a")]
        public string NuevaContrasena { get; set; } = string.Empty;

        [Required(ErrorMessage = "Confirma tu contraseÃ±a")]
        [Compare(nameof(NuevaContrasena), ErrorMessage = "Las contraseÃ±as no coinciden")]
        [Display(Name = "Confirmar contraseÃ±a")]
        public string ConfirmarContrasena { get; set; } = string.Empty;
    }
}
