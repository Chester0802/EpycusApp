using System.ComponentModel.DataAnnotations;

namespace EpycusApp.DTOs
{
    public class CicloCompletadoRequest
    {
        [Range(1, 100, ErrorMessage = "CiclosCompletados debe ser entre 1 y 100.")]
        public int CiclosCompletados { get; set; }
    }
}
