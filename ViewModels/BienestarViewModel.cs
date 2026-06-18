using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;

namespace EpycusApp.ViewModels
{
    public class BienestarViewModel
    {
        public EstadoAnimo? EstadoHoy { get; set; }
        public List<AlertaBienestar> Alertas { get; set; } = new();
        public FraseMotivacional? FraseMotivacional { get; set; }
        public List<EstadoAnimo> HistorialAnimo { get; set; } = new();

        public int HabitosPendientes { get; set; }
        public int MisionesPendientes { get; set; }
        public RecomendacionPausaDto? RecomendacionActiva { get; set; }
    }
}
