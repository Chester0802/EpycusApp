using EPYCUS_WEB_v0._1.Models.Entidades;

namespace EPYCUS_WEB_v0._1.ViewModels
{
    public class ProgresoViewModel
    {
        public ProgresoUsuario Progreso { get; set; } = null!;
        public Nivel? NivelSiguiente { get; set; }
        public int XpParaSiguienteNivel => NivelSiguiente != null ? NivelSiguiente.XpRequerido - Progreso.NivelActual.XpRequerido : 0;
        public int XpEnNivelActual => Progreso.XpTotal - Progreso.NivelActual.XpRequerido;
        public int PorcentajeNivel => XpParaSiguienteNivel > 0 ? (int)((double)XpEnNivelActual / XpParaSiguienteNivel * 100) : 100;
        public List<Logro> TodosLosLogros { get; set; } = new List<Logro>();
        public List<int> LogrosDesbloqueadosIds { get; set; } = new List<int>();
        public string ImagenPersonajeUrl { get; set; } = "/img/personajes/generico/masculino/placeholder.png";
    }
}
