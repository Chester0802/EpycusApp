using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels.Autenticacion
{
    public class RecuperarContrasenaViewModel
    {
        [Required(ErrorMessage = "El correo es obligatorio")]
        [EmailAddress(ErrorMessage = "Correo no vÃ¡lido")]
        [Display(Name = "Correo electrÃ³nico")]
        public string CorreoElectronico { get; set; } = string.Empty;
    }
}
