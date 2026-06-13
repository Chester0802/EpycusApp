namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class MensajeIA
    {
        public int Id { get; set; }

        /// <summary>GUID que agrupa todos los mensajes de una misma conversación.</summary>
        public string ConversacionId { get; set; } = string.Empty;

        public int UsuarioId { get; set; }

        /// <summary>"user" para el usuario, "model" para EDY.</summary>
        public string Rol { get; set; } = string.Empty;

        public string Contenido { get; set; } = string.Empty;

        public DateTime FechaHora { get; set; } = DateTime.UtcNow;

        // ── Navegación ────────────────────────────────────────────────────────
        public Usuario Usuario { get; set; } = null!;
    }
}
