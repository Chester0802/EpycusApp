namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class EstadoAnimo
    {
        public int Id { get; set; }
        public DateOnly Fecha { get; set; }
        public string Estado { get; set; } = string.Empty;
        public string? Nota { get; set; }
        public DateTime FechaRegistro { get; set; } = DateTime.Now;
        public int UsuarioId { get; set; }
        public Usuario Usuario { get; set; } = null!;
    }
}
