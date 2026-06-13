using EPYCUS_WEB_v0._1.Models.Entidades;

namespace EPYCUS_WEB_v0._1.ViewModels.Ia
{
    public class IaChatViewModel
    {
        public string ConversacionId { get; set; } = string.Empty;
        public List<MensajeIA> Mensajes { get; set; } = new();
    }
}
