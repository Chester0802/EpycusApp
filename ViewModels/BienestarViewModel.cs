using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;

namespace EPYCUS_WEB_v0._1.ViewModels
{
    public class BienestarViewModel
    {
        public EstadoAnimo? EstadoHoy { get; set; }
        public List<AlertaBienestar> Alertas { get; set; } = new();
        public FraseMotivacional? FraseMotivacional { get; set; }
        public List<EstadoAnimo> HistorialAnimo { get; set; } = new();
    }
}
