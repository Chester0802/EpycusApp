using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels.Admin
{
    public class AdminLoginViewModel
    {
        [Required(ErrorMessage = "El correo electrÃ³nico es requerido")]
        [EmailAddress(ErrorMessage = "El formato del correo no es vÃ¡lido")]
        [Display(Name = "Correo ElectrÃ³nico")]
        public string CorreoElectronico { get; set; } = string.Empty;

        [Required(ErrorMessage = "La contraseÃ±a es requerida")]
        [DataType(DataType.Password)]
        [Display(Name = "ContraseÃ±a")]
        public string Contrasena { get; set; } = string.Empty;

        [Display(Name = "Recordarme")]
        public bool Recordarme { get; set; }
    }
}
