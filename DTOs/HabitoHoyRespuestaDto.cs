namespace EpycusApp.DTOs
{
    public class HabitoHoyRespuestaDto
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string EstadoHoy { get; set; } = string.Empty;
        public int XpPotencial { get; set; }
        public string Categoria { get; set; } = string.Empty;
    }
}
