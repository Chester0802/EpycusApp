using System.Collections.Generic;

namespace EPYCUS_WEB_v0._1.ViewModels
{
    public class HabitosIndexViewModel
    {
        public List<HabitoViewModel> Habitos { get; set; } = new List<HabitoViewModel>();
        public HabitosDashboardViewModel Dashboard { get; set; } = new HabitosDashboardViewModel();
    }
}
