using System.Collections.Generic;
using EpycusApp.Models.Entidades;

namespace EpycusApp.ViewModels
{
    public class PomodoroIndexViewModel
    {
        public ConfiguracionPomodoro Configuracion { get; set; } = new ConfiguracionPomodoro();
        public EstadisticasPomodoroHoy EstadisticasHoy { get; set; } = new EstadisticasPomodoroHoy();
        public List<TareaPomodoro> TareasEnfoque { get; set; } = new List<TareaPomodoro>();
        public List<SesionPomodoro> HistorialHoy { get; set; } = new List<SesionPomodoro>();
        public int RachaActual { get; set; }
        public List<EstadisticasPomodoroPeriodo> EstadisticasSemanales { get; set; } = new List<EstadisticasPomodoroPeriodo>();
    }

    public class EstadisticasPomodoroHoy
    {
        public int CiclosCompletados { get; set; }
        public int MinutosEnfocados { get; set; }
        public int XpGanado { get; set; }
        public int MisionesCompletadas { get; set; }
    }

    public class TareaPomodoro
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string CategoriaNombre { get; set; } = string.Empty;
        public string Tipo { get; set; } = string.Empty;
    }

    public class EstadisticasPomodoroPeriodo
    {
        public string Fecha { get; set; } = string.Empty;
        public int Ciclos { get; set; }
        public int Minutos { get; set; }
        public int Xp { get; set; }
    }
}
