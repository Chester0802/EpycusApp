using EpycusApp.Models.Entidades;

namespace EpycusApp.ViewModels.Ia
{
    public class IaChatViewModel
    {
        public string ConversacionId { get; set; } = string.Empty;
        public List<MensajeIA> Mensajes { get; set; } = new();
    }
}
