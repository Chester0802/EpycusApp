namespace EpycusApp.DTOs
{
    /// <summary>
    /// DTO para el registro de un hábito en la semana
    /// </summary>
    public class RegistroSemanaDto
    {
        public string Dia { get; set; } = string.Empty;
        public string Estado { get; set; } = string.Empty;
    }
}
