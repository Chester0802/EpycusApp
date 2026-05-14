using System.Collections.Generic;

namespace EPYCUS_WEB_v0._1.ViewModels
{
    public class HabitosDashboardViewModel
    {
        public int TotalHabitos { get; set; }
        public int CompletadosSemana { get; set; }
        public int ParcialesSemana { get; set; }
        public int OmitidosSemana { get; set; }
        public List<(string Nombre, int Dias)> MejoresRachas { get; set; } = new List<(string, int)>();
        public Dictionary<string, int> DistribucionPorCategoria { get; set; } = new Dictionary<string, int>();
        public int TotalCompletadosHoy { get; set; }
        public int RachaActualMaxima { get; set; }
    }
}
