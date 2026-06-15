namespace EpycusApp.Models.Entidades
{
    public class RecuperacionContrasena
    {
        public int Id { get; set; }
        public string Token { get; set; } = string.Empty;
        public DateTime ExpiraEn { get; set; }
        public bool Usado { get; set; } = false;
        public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
        public int UsuarioId { get; set; }
        public Usuario Usuario { get; set; } = null!;
    }
}
