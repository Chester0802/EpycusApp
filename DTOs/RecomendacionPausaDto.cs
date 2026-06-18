namespace EpycusApp.DTOs
{
    public class RecomendacionPausaDto
    {
        public string Tipo { get; set; } = string.Empty;
        public int DuracionSegundos { get; set; }
        public string Descripcion { get; set; } = string.Empty;
        public string Icono { get; set; } = "bi-arrow-repeat";
    }
}
