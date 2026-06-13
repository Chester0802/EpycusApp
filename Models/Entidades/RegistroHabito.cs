namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class RegistroHabito
    {
        public int Id { get; set; }
        public DateOnly Fecha { get; set; }
        public string Estado { get; set; } = "Pendiente";
        public int XpOtorgado { get; set; } = 0;
        public DateTime FechaRegistro { get; set; } = DateTime.Now;
        public int HabitoId { get; set; }
        public Habito Habito { get; set; } = null!;
    }
}
