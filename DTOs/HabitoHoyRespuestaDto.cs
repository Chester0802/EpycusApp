namespace EPYCUS_WEB_v0._1.DTOs
{
    /// <summary>
    /// DTO para la respuesta de hábitos activos con estado de hoy
    /// </summary>
    public class HabitoHoyRespuestaDto
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string EstadoHoy { get; set; } = string.Empty;
        public int XpPotencial { get; set; }
    }
}
