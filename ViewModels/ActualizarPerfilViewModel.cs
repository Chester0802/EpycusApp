using System.ComponentModel.DataAnnotations;

namespace EPYCUS_WEB_v0._1.ViewModels
{
    public class ActualizarPerfilViewModel
    {
        [Required(ErrorMessage = "El nombre es requerido.")]
        [StringLength(100)]
        public string Nombre { get; set; } = string.Empty;

        public int? CarreraId { get; set; }
    }
}
