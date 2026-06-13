namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class LogroUsuario
    {
        public int Id { get; set; }
        public DateTime FechaObtenido { get; set; } = DateTime.Now;
        public int UsuarioId { get; set; }
        public int LogroId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Logro Logro { get; set; } = null!;
    }
}
