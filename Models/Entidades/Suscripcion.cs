namespace EpycusApp.Models.Entidades
{
    public class Suscripcion
    {
        public int Id { get; set; }
        public string Plan { get; set; } = string.Empty;
        public decimal PrecioSoles { get; set; }
        public DateOnly FechaInicio { get; set; }
        public DateOnly FechaFin { get; set; }
        public bool EstaActiva { get; set; } = false;
        public int? ActivadaPorAdminId { get; set; }
        public DateTime? FechaActivacion { get; set; }
        public int UsuarioId { get; set; }
        public Usuario Usuario { get; set; } = null!;
    }
}
