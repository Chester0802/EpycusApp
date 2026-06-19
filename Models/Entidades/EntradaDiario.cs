namespace EpycusApp.Models.Entidades
{
    public class EntradaDiario
    {
        public int Id { get; set; }
        public DateOnly Fecha { get; set; }
        public int EstadoAnimo { get; set; }
        public int NivelEnergia { get; set; }
        public decimal? HorasSueno { get; set; }
        public int? NivelEstres { get; set; }
        public bool? ActividadFisica { get; set; }
        public string? DiarioTexto { get; set; }
        public string? PreguntaGuia { get; set; }
        public string? RespuestaGuia { get; set; }
        public DateTime FechaRegistro { get; set; } = DateTime.UtcNow;
        public int UsuarioId { get; set; }
        public Usuario Usuario { get; set; } = null!;
    }
}
