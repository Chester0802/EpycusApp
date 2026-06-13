using EPYCUS_WEB_v0._1.Models.Entidades;

namespace EPYCUS_WEB_v0._1.Servicios.Interfaces
{
    public interface IServicioIA
    {
        /// <summary>
        /// Envía un mensaje del usuario a EDY (Gemini Flash) y devuelve la respuesta.
        /// Guarda ambos mensajes en la base de datos y verifica que la conversación
        /// pertenece al usuario indicado.
        /// </summary>
        Task<string> ChatAsync(int usuarioId, string mensaje, string conversacionId);

        /// <summary>Recupera el historial de mensajes de una conversación para un usuario.</summary>
        Task<List<MensajeIA>> ObtenerHistorialAsync(int usuarioId, string conversacionId);

        /// <summary>Genera un nuevo identificador de conversación (GUID).</summary>
        string NuevaConversacionId();
    }
}
