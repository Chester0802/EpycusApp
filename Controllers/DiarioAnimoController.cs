using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace EpycusApp.Controllers
{
    [Authorize]
    public class DiarioAnimoController : BaseController
    {
        private readonly IServicioDiarioAnimo _servicioDiario;

        public DiarioAnimoController(IServicioDiarioAnimo servicioDiario)
        {
            _servicioDiario = servicioDiario;
        }

        public async Task<IActionResult> Index(int? año, int? mes)
        {
            var usuarioId = ObtenerUsuarioId();
            var ahora = DateTime.UtcNow;
            var añoActual = año ?? ahora.Year;
            var mesActual = mes ?? ahora.Month;

            var modelo = new DiarioAnimoViewModel
            {
                EntradaHoy = await _servicioDiario.ObtenerEntradaHoy(usuarioId),
                EntradasMes = await _servicioDiario.ObtenerEntradasMes(usuarioId, añoActual, mesActual),
                Año = añoActual,
                Mes = mesActual,
                PreguntaGuiaHoy = _servicioDiario.ObtenerPreguntaGuia(),
                DiasConsecutivos = await _servicioDiario.ObtenerDiasConsecutivos(usuarioId),
                PromedioAnimoMes = await _servicioDiario.ObtenerPromedioAnimoMes(usuarioId, añoActual, mesActual),
                TotalEntradasMes = await _servicioDiario.ObtenerEntradasMes(usuarioId, añoActual, mesActual).ContinueWith(t => t.Result.Count)
            };

            return View(modelo);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Registrar(RegistrarEntradaDiarioViewModel model)
        {
            if (!ModelState.IsValid)
            {
                TempData["Error"] = "Revisa los campos del formulario.";
                return RedirectToAction(nameof(Index));
            }

            var usuarioId = ObtenerUsuarioId();
            var preguntaGuia = _servicioDiario.ObtenerPreguntaGuia();
            await _servicioDiario.RegistrarEntrada(usuarioId, model, preguntaGuia);

            TempData["Exito"] = "Entrada de diario guardada. Sigue cultivando tu bienestar.";
            return RedirectToAction(nameof(Index));
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult NavegarMes(int direccion)
        {
            var ahora = DateTime.UtcNow;
            var fechaActual = new DateTime(ahora.Year, ahora.Month, 1).AddMonths(direccion);
            return RedirectToAction(nameof(Index), new { año = fechaActual.Year, mes = fechaActual.Month });
        }
    }
}
