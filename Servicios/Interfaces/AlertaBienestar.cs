namespace EpycusApp.Servicios.Interfaces
{
    public class AlertaBienestar
    {
        public string Tipo { get; set; } = string.Empty;
        public string Mensaje { get; set; } = string.Empty;
        public string Icono { get; set; } = string.Empty;
        public bool EsCritica { get; set; }
    }
}
