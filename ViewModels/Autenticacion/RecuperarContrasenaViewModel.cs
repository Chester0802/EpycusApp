using System.ComponentModel.DataAnnotations;

namespace EPYCUS_WEB_v0._1.ViewModels.Autenticacion
{
    public class RecuperarContrasenaViewModel
    {
        [Required(ErrorMessage = "El correo es obligatorio")]
        [EmailAddress(ErrorMessage = "Correo no válido")]
        [Display(Name = "Correo electrónico")]
        public string CorreoElectronico { get; set; } = string.Empty;
    }
}
