namespace EpycusApp.Models.Entidades
{
    public class MensajeIA
    {
        public int Id { get; set; }

        public string ConversacionId { get; set; } = string.Empty;

        public int UsuarioId { get; set; }

        public string Rol { get; set; } = string.Empty;

        public string Contenido { get; set; } = string.Empty;

        public DateTime FechaHora { get; set; } = DateTime.UtcNow;

        public bool? FeedbackRecibido { get; set; }

        public bool? FeedbackUtil { get; set; }

        public Usuario Usuario { get; set; } = null!;
    }
}
