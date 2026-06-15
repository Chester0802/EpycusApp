using System.Collections.Generic;

namespace EpycusApp.ViewModels
{
    public class HabitosIndexViewModel
    {
        public List<HabitoViewModel> Habitos { get; set; } = new List<HabitoViewModel>();
        public HabitosDashboardViewModel Dashboard { get; set; } = new HabitosDashboardViewModel();
    }
}
