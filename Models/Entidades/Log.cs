namespace EpycusApp.Models.Entidades
{
    public class Log
    {
        public int Id { get; set; }
        public string Accion { get; set; } = string.Empty;
        public string? Detalle { get; set; }
        public string? DireccionIp { get; set; }
        public DateTime FechaRegistro { get; set; } = DateTime.UtcNow;
        public int? UsuarioId { get; set; }
        public Usuario? Usuario { get; set; }
    }
}
