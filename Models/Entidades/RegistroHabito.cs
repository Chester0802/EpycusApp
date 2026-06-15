namespace EpycusApp.Models.Entidades
{
    public class RegistroHabito
    {
        public int Id { get; set; }
        public DateOnly Fecha { get; set; }
        public string Estado { get; set; } = "Pendiente";
        public int XpOtorgado { get; set; } = 0;
        public DateTime FechaRegistro { get; set; } = DateTime.UtcNow;
        public int HabitoId { get; set; }
        public Habito Habito { get; set; } = null!;
    }
}
