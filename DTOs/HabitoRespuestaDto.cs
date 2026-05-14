namespace EPYCUS_WEB_v0._1.DTOs
{
    /// <summary>
    /// DTO para la respuesta de un hábito con su estado
    /// </summary>
    public class HabitoRespuestaDto
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Estado { get; set; } = string.Empty;
        public int RachaActual { get; set; }
        public string Categoria { get; set; } = string.Empty;
    }
}
