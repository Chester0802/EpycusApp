using EpycusApp.Models.Entidades;

namespace EpycusApp.ViewModels.Ia
{
    public class IaChatViewModel
    {
        public string ConversacionId { get; set; } = string.Empty;
        public List<MensajeIA> Mensajes { get; set; } = new();
        public List<string> Sugerencias { get; set; } = new();
        public List<ConversacionResumen> Conversaciones { get; set; } = new();
        public BienestarContextoIA? BienestarCtx { get; set; }
    }

    public class ConversacionResumen
    {
        public string ConversacionId { get; set; } = string.Empty;
        public DateTime UltimoMensaje { get; set; }
        public int CantidadMensajes { get; set; }
        public string? Titulo { get; set; }
    }

    public class BienestarContextoIA
    {
        public bool TieneAlertasActivas { get; set; }
        public int DiasAnimoNegativo { get; set; }
        public bool PomodoroExcesivo { get; set; }
        public bool SobrecargaMisiones { get; set; }
        public string? UltimoEstadoAnimo { get; set; }
    }
}
