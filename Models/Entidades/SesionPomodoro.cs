namespace EpycusApp.Models.Entidades
{
    public class SesionPomodoro
    {
        public int Id { get; set; }
        public DateTime FechaInicio { get; set; }
        public DateTime? FechaFin { get; set; }
        public int CiclosCompletados { get; set; } = 0;
        public int XpOtorgado { get; set; } = 0;
        public bool FueCompletada { get; set; } = false;
        public string Tipo { get; set; } = "Enfoque";
        public int UsuarioId { get; set; }
        public int? HabitoId { get; set; }
        public int? MisionId { get; set; }
        public int? SubTareaId { get; set; }

        // Columna calculada en BD (ver ContextoAplicacion.OnModelCreating): UsuarioId si
        // FechaFin es null (sesión abierta), null en caso contrario. Con un índice único
        // sobre esta columna, la BD garantiza "máximo una sesión abierta por usuario" a
        // nivel de esquema, cerrando la ventana de carrera de IniciarSesionSiNoActiva
        // (check-then-insert sin bloqueo, dos peticiones casi simultáneas podían crear dos
        // sesiones activas). No se asigna nunca desde código: EF la trata como solo-lectura.
        public int? SesionAbiertaMarcador { get; private set; }
        public Usuario Usuario { get; set; } = null!;
        public Habito? Habito { get; set; }
        public Mision? Mision { get; set; }
        public SubTarea? SubTarea { get; set; }
    }
}
