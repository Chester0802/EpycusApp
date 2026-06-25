namespace EpycusApp.Models.Entidades
{
    public class Usuario
    {
        public int Id { get; set; }
        public string CodigoUnico { get; set; } = string.Empty;
        public string Nombre { get; set; } = string.Empty;
        public string CorreoElectronico { get; set; } = string.Empty;
        public string? ContrasenaHash { get; set; }
        public DateOnly FechaNacimiento { get; set; }
        public string Genero { get; set; } = string.Empty;
        public bool CorreoVerificado { get; set; } = false;
        public bool AceptoTerminos { get; set; } = false;
        public bool EstaActivo { get; set; } = true;
        public DateTime FechaRegistro { get; set; } = DateTime.UtcNow;
        public DateTime? UltimoAcceso { get; set; }
        public string? GoogleId { get; set; }
        public string? FotoGoogleUrl { get; set; }
        public int IntentosFallidos { get; set; }
        public DateTime? BloqueoHasta { get; set; }
        public int RolId { get; set; }
        public int CarreraId { get; set; }
        public int? TemaActualId { get; set; }
        public Rol Rol { get; set; } = null!;
        public Carrera Carrera { get; set; } = null!;
        public Tema? TemaActual { get; set; }
        public ProgresoUsuario Progreso { get; set; } = null!;
        public ICollection<Habito> Habitos { get; set; } = new List<Habito>();
        public ICollection<Mision> Misiones { get; set; } = new List<Mision>();
        public ICollection<SesionPomodoro> SesionesPomodoro { get; set; } = new List<SesionPomodoro>();
        public ConfiguracionPomodoro ConfiguracionPomodoro { get; set; } = null!;
        public ICollection<PersonajeUsuario> PersonajesUsuario { get; set; } = new List<PersonajeUsuario>();
        public ICollection<LogroUsuario> LogrosUsuario { get; set; } = new List<LogroUsuario>();
        public ICollection<EstadoAnimo> EstadosAnimo { get; set; } = new List<EstadoAnimo>();
        public ICollection<Suscripcion> Suscripciones { get; set; } = new List<Suscripcion>();
        public ICollection<TemaUsuario> TemasUsuario { get; set; } = new List<TemaUsuario>();
        public ICollection<TokenRefresh> TokensRefresh { get; set; } = new List<TokenRefresh>();
    public string ZonaHoraria { get; set; } = "Europe/Madrid";
    }
}
