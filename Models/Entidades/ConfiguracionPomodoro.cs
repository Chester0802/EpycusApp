using System.ComponentModel.DataAnnotations.Schema;

namespace EpycusApp.Models.Entidades
{
    public class ConfiguracionPomodoro
    {
        public int Id { get; set; }
        public int TiempoEstudioMin { get; set; } = 25;
        public int TiempoDescansoMin { get; set; } = 5;
        public int TiempoDescansoLargoMin { get; set; } = 15;
        public int CiclosAntesDescansoLargo { get; set; } = 4;
        public bool SonidoActivo { get; set; } = true;
        public string SonidoSeleccionado { get; set; } = "campana";
        public int Volumen { get; set; } = 100;
        public bool AutoIniciarDescanso { get; set; } = false;
        public bool AutoIniciarEnfoque { get; set; } = false;
        public bool TicTacActivo { get; set; } = false;
        public int MetaDiariaCiclos { get; set; } = 0;
        [Column("ModoPersonalizadoMinutos")]
        public int ModoPersonalizadoMin { get; set; } = 25;
        public bool VibracionActiva { get; set; } = true;
        public bool NotificacionDesktop { get; set; } = true;
        public DateTime FechaActualizacion { get; set; } = DateTime.UtcNow;
        public int UsuarioId { get; set; }
        public Usuario Usuario { get; set; } = null!;
    }
}
