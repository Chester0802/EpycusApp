namespace EPYCUS_WEB_v0._1.Modelos.Entidades
{
    public class Log
    {
        public int Id { get; set; }
        public string Accion { get; set; } = string.Empty;
        public string? Detalle { get; set; }
        public string? DireccionIp { get; set; }
        public DateTime FechaRegistro { get; set; } = DateTime.Now;
        public int? UsuarioId { get; set; }
        public Usuario? Usuario { get; set; }
    }
}
