namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class TemaUsuario
    {
        public int Id { get; set; }
        public DateTime FechaObtenido { get; set; } = DateTime.Now;
        public int UsuarioId { get; set; }
        public int TemaId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Tema Tema { get; set; } = null!;
    }
}
