using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioDiarioAnimo : IServicioDiarioAnimo
    {
        private readonly ContextoAplicacion _contexto;
        private readonly ILogger<ServicioDiarioAnimo> _logger;

        private static readonly string[] PreguntasGuia =
        [
            "¿Qué cosas buenas pasaron hoy? (Psicología positiva — Martin Seligman)",
            "¿De qué te sientes orgulloso hoy? (Autoestima — Nathaniel Branden)",
            "¿Qué aprendiste hoy? (Mentalidad de crecimiento — Carol Dweck)",
            "¿Cómo trataste a los demás hoy? (Autocompasión — Kristin Neff)",
            "¿Qué harías diferente si pudieras repetir el día? (Reflexión — John Dewey)",
            "¿Qué fortaleza personal usaste hoy? (Fortalezas de carácter — Peterson & Seligman)",
            "¿A quién agradeces hoy? (Gratitud — Robert Emmons)",
            "¿Qué momento de flow experimentaste? (Flow — Mihály Csíkszentmihályi)",
            "¿Cómo cuidaste de ti mismo hoy? (Autocuidado — teoría del bienestar)",
            "¿Qué te causó estrés y cómo lo manejaste? (Manejo del estrés — Lazarus & Folkman)",
            "¿Qué meta pequeña lograste hoy? (Establecimiento de metas — Edwin Locke)",
            "¿Cómo se sintió tu cuerpo hoy? (Conexión mente-cuerpo — Bessel van der Kolk)",
            "¿Qué creencia limitante desafiaste hoy? (Reestructuración cognitiva — Aaron Beck)",
            "¿Qué hiciste hoy que estuvo alineado con tus valores? (Terapia de aceptación y compromiso — Steven Hayes)",
            "¿Qué te gustaría recordar de este día en 5 años? (Perspectiva temporal — Philip Zimbardo)"
        ];

        public ServicioDiarioAnimo(ContextoAplicacion contexto, ILogger<ServicioDiarioAnimo> logger)
        {
            _contexto = contexto;
            _logger = logger;
        }

        public async Task<EntradaDiario?> ObtenerEntradaHoy(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.UtcNow);
            return await _contexto.EntradasDiario
                .FirstOrDefaultAsync(e => e.UsuarioId == usuarioId && e.Fecha == hoy);
        }

        public async Task<EntradaDiario?> ObtenerEntradaPorFecha(int usuarioId, DateOnly fecha)
        {
            return await _contexto.EntradasDiario
                .FirstOrDefaultAsync(e => e.UsuarioId == usuarioId && e.Fecha == fecha);
        }

        public async Task<List<EntradaDiario>> ObtenerEntradasMes(int usuarioId, int año, int mes)
        {
            return await _contexto.EntradasDiario
                .Where(e => e.UsuarioId == usuarioId && e.Fecha.Year == año && e.Fecha.Month == mes)
                .OrderBy(e => e.Fecha)
                .ToListAsync();
        }

        public async Task<EntradaDiario> RegistrarEntrada(int usuarioId, RegistrarEntradaDiarioViewModel model, string preguntaGuia)
        {
            var hoy = DateOnly.FromDateTime(DateTime.UtcNow);

            var existente = await _contexto.EntradasDiario
                .FirstOrDefaultAsync(e => e.UsuarioId == usuarioId && e.Fecha == hoy);

            if (existente != null)
            {
                existente.EstadoAnimo = model.EstadoAnimo;
                existente.NivelEnergia = model.NivelEnergia;
                existente.HorasSueno = model.HorasSueno.HasValue ? Math.Round(model.HorasSueno.Value, 1) : null;
                existente.NivelEstres = model.NivelEstres;
                existente.ActividadFisica = model.ActividadFisica;
                existente.DiarioTexto = model.DiarioTexto;
                existente.PreguntaGuia = preguntaGuia;
                existente.RespuestaGuia = model.RespuestaGuia;
                existente.FechaRegistro = DateTime.UtcNow;
                await _contexto.SaveChangesAsync();
                return existente;
            }

            var entrada = new EntradaDiario
            {
                Fecha = hoy,
                EstadoAnimo = model.EstadoAnimo,
                NivelEnergia = model.NivelEnergia,
                HorasSueno = model.HorasSueno.HasValue ? Math.Round(model.HorasSueno.Value, 1) : null,
                NivelEstres = model.NivelEstres,
                ActividadFisica = model.ActividadFisica,
                DiarioTexto = model.DiarioTexto,
                PreguntaGuia = preguntaGuia,
                RespuestaGuia = model.RespuestaGuia,
                FechaRegistro = DateTime.UtcNow,
                UsuarioId = usuarioId
            };

            _contexto.EntradasDiario.Add(entrada);
            await _contexto.SaveChangesAsync();

            _logger.LogInformation("Entrada de diario creada para usuario {UsuarioId}, fecha {Fecha}", usuarioId, hoy);

            return entrada;
        }

        public async Task<EntradaDiario?> ActualizarEntrada(int usuarioId, DateOnly fecha, RegistrarEntradaDiarioViewModel model)
        {
            var entrada = await _contexto.EntradasDiario
                .FirstOrDefaultAsync(e => e.UsuarioId == usuarioId && e.Fecha == fecha);

            if (entrada == null) return null;

            entrada.EstadoAnimo = model.EstadoAnimo;
            entrada.NivelEnergia = model.NivelEnergia;
            entrada.HorasSueno = model.HorasSueno.HasValue ? Math.Round(model.HorasSueno.Value, 1) : null;
            entrada.NivelEstres = model.NivelEstres;
            entrada.ActividadFisica = model.ActividadFisica;
            entrada.DiarioTexto = model.DiarioTexto;
            entrada.RespuestaGuia = model.RespuestaGuia;
            entrada.FechaRegistro = DateTime.UtcNow;

            await _contexto.SaveChangesAsync();
            return entrada;
        }

        public async Task<int> ObtenerDiasConsecutivos(int usuarioId)
        {
            var entradas = await _contexto.EntradasDiario
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .Select(e => e.Fecha)
                .ToListAsync();

            if (entradas.Count == 0) return 0;

            var racha = 1;
            var hoy = DateOnly.FromDateTime(DateTime.UtcNow);

            if (entradas[0] != hoy) return 0;

            for (int i = 1; i < entradas.Count; i++)
            {
                if (entradas[i] == entradas[i - 1].AddDays(-1))
                    racha++;
                else
                    break;
            }

            return racha;
        }

        public async Task<double?> ObtenerPromedioAnimoMes(int usuarioId, int año, int mes)
        {
            var animos = await _contexto.EntradasDiario
                .Where(e => e.UsuarioId == usuarioId && e.Fecha.Year == año && e.Fecha.Month == mes)
                .Select(e => (double)e.EstadoAnimo)
                .ToListAsync();

            return animos.Count > 0 ? animos.Average() : null;
        }

        public string ObtenerPreguntaGuia()
        {
            var diaDelAño = DateTime.UtcNow.DayOfYear;
            var indice = diaDelAño % PreguntasGuia.Length;
            return PreguntasGuia[indice];
        }
    }
}
