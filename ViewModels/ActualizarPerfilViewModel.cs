using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels
{
    public class ActualizarPerfilViewModel
    {
        [Required(ErrorMessage = "El nombre es requerido.")]
        [StringLength(100)]
        public string Nombre { get; set; } = string.Empty;

        public int? CarreraId { get; set; }
    }
}
