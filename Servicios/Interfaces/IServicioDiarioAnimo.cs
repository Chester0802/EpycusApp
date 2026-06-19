using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.Servicios.Interfaces
{
    public interface IServicioDiarioAnimo
    {
        Task<EntradaDiario?> ObtenerEntradaHoy(int usuarioId);
        Task<EntradaDiario?> ObtenerEntradaPorFecha(int usuarioId, DateOnly fecha);
        Task<List<EntradaDiario>> ObtenerEntradasMes(int usuarioId, int año, int mes);
        Task<EntradaDiario> RegistrarEntrada(int usuarioId, RegistrarEntradaDiarioViewModel model, string preguntaGuia);
        Task<EntradaDiario?> ActualizarEntrada(int usuarioId, DateOnly fecha, RegistrarEntradaDiarioViewModel model);
        Task<int> ObtenerDiasConsecutivos(int usuarioId);
        Task<double?> ObtenerPromedioAnimoMes(int usuarioId, int año, int mes);
        string ObtenerPreguntaGuia();
    }
}
