using System.Collections.Generic;

namespace EPYCUS_WEB_v0._1.ViewModels
{
    public class HomeDashboardViewModel
    {
        public HabitosDashboardViewModel Estadisticas { get; set; } = new HabitosDashboardViewModel();
        public List<HabitoViewModel> HabitosHoy { get; set; } = new List<HabitoViewModel>();
        public string FraseMotivacional { get; set; } = string.Empty;
        public string AutorFrase { get; set; } = string.Empty;
        public bool EstaAutenticado { get; set; } = false;
        public string NombreUsuario { get; set; } = string.Empty;
    }
}
