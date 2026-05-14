namespace EPYCUS_WEB_v0._1.Modelos.Entidades
{
    public class PersonajeUsuario
    {
        public int Id { get; set; }
        public bool EstaSeleccionado { get; set; } = false;
        public DateTime FechaObtenido { get; set; } = DateTime.Now;
        public int UsuarioId { get; set; }
        public int PersonajeId { get; set; }
        public Usuario Usuario { get; set; } = null!;
        public Personaje Personaje { get; set; } = null!;
    }
}
