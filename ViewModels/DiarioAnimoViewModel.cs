using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;

namespace EpycusApp.ViewModels
{
    public class DiarioAnimoViewModel
    {
        public EntradaDiario? EntradaHoy { get; set; }
        public List<EntradaDiario> EntradasMes { get; set; } = new();
        public int Año { get; set; }
        public int Mes { get; set; }
        public string PreguntaGuiaHoy { get; set; } = string.Empty;
        public int DiasConsecutivos { get; set; }
        public int TotalEntradasMes { get; set; }
        public double? PromedioAnimoMes { get; set; }
    }

    public class RegistrarEntradaDiarioViewModel
    {
        public int EstadoAnimo { get; set; }
        public int NivelEnergia { get; set; }
        public decimal? HorasSueno { get; set; }
        public int? NivelEstres { get; set; }
        public bool? ActividadFisica { get; set; }
        public string? DiarioTexto { get; set; }
        public string? RespuestaGuia { get; set; }
    }

    public class CalendarioDiarioViewModel
    {
        public int Año { get; set; }
        public int Mes { get; set; }
        public List<DiaCalendario> Dias { get; set; } = new();
    }

    public class DiaCalendario
    {
        public int Dia { get; set; }
        public bool TieneEntrada { get; set; }
        public int? EstadoAnimo { get; set; }
        public bool EsHoy { get; set; }
        public bool EsMesActual { get; set; }
    }
}
