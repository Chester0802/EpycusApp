using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels.Autenticacion
{
    public class RecuperarContrasenaViewModel
    {
        [Required(ErrorMessage = "El correo es obligatorio")]
        [EmailAddress(ErrorMessage = "Correo no válido")]
        [Display(Name = "Correo electrónico")]
        public string CorreoElectronico { get; set; } = string.Empty;
    }
}
