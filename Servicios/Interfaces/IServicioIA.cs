using EpycusApp.Models.Entidades;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioIA
    {
        /// <summary>
        /// EnvÃ­a un mensaje del usuario a EDY (Gemini Flash) y devuelve la respuesta.
        /// Guarda ambos mensajes en la base de datos y verifica que la conversaciÃ³n
        /// pertenece al usuario indicado.
        /// </summary>
        Task<string> ChatAsync(int usuarioId, string mensaje, string conversacionId);

        /// <summary>Recupera el historial de mensajes de una conversaciÃ³n para un usuario.</summary>
        Task<List<MensajeIA>> ObtenerHistorialAsync(int usuarioId, string conversacionId);

        /// <summary>Genera un nuevo identificador de conversaciÃ³n (GUID).</summary>
        string NuevaConversacionId();
    }
}
