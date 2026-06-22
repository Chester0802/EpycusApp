using EpycusApp.Ayudantes;

namespace EpycusApp.DTOs
{
    public class SubTareaResponse
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public bool EstaCompletada { get; set; }
        public int Orden { get; set; }
        public int TiempoEnfoqueSegundos { get; set; }
        public string TiempoEnfoqueFormateado { get; set; } = string.Empty;
        public DateTime FechaCreacion { get; set; }
        public DateTime? FechaCompletado { get; set; }
        public int MisionId { get; set; }
    }

    public class CrearSubTareaDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
    }

    public class EditarSubTareaDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public int? Orden { get; set; }
    }
}
