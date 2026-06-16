using EpycusApp.Ayudantes;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioGamificacion : IServicioGamificacion
    {
        private readonly EpycusApp.Datos.ContextoAplicacion _contexto;
        private readonly ILogger<ServicioGamificacion> _logger;

        public ServicioGamificacion(EpycusApp.Datos.ContextoAplicacion contexto, ILogger<ServicioGamificacion> logger)
        {
            _contexto = contexto;
            _logger = logger;
        }

        public async Task<(int XpGanado, bool SubioDeNivel, int NivelNuevo)> SumarXP(int usuarioId, int xp)
        {
            var progreso = await _contexto.ProgresosUsuario
                .Include(p => p.NivelActual)
                .FirstOrDefaultAsync(p => p.UsuarioId == usuarioId);

            if (progreso == null)
            {
                return (0, false, 0);
            }

            var xpAnterior = progreso.XpTotal;
            progreso.XpTotal += xp;

            var nivelNuevoNumero = CalculadorXP.NivelParaXp(progreso.XpTotal);
            var subio = nivelNuevoNumero != progreso.NivelActual.Numero;

            if (subio)
            {
                var nuevoNivel = await _contexto.Niveles.FirstOrDefaultAsync(n => n.Numero == nivelNuevoNumero);
                if (nuevoNivel != null)
                {
                    progreso.NivelActualId = nuevoNivel.Id;
                }
            }

            await _contexto.SaveChangesAsync();

            return (progreso.XpTotal - xpAnterior, subio, nivelNuevoNumero);
        }

        public async Task VerificarYOtorgarLogros(int usuarioId)
        {
            var progreso = await _contexto.ProgresosUsuario
                .Include(p => p.NivelActual)
                .FirstOrDefaultAsync(p => p.UsuarioId == usuarioId);

            if (progreso == null)
            {
                return;
            }

            var logrosActivos = await _contexto.Logros.Where(l => l.EstaActivo).ToListAsync();
            var logrosUsuario = await _contexto.LogrosUsuario.Where(lu => lu.UsuarioId == usuarioId).ToListAsync();

            foreach (var logro in logrosActivos)
            {
                var yaTiene = logrosUsuario.Any(lu => lu.LogroId == logro.Id);
                if (yaTiene)
                {
                    continue;
                }

                var cumplido = logro.CondicionTipo switch
                {
                    "HabitosCompletados" => await _contexto.RegistrosHabito
                        .CountAsync(r => r.Habito.UsuarioId == usuarioId && r.Estado == "Completado") >= logro.CondicionValor,
                    "MisionesCompletadas" => await _contexto.Misiones
                        .CountAsync(m => m.UsuarioId == usuarioId && m.Estado == "Completado") >= logro.CondicionValor,
                    "RachaDias" => progreso.RachaActual >= logro.CondicionValor,
                    "SesionesPomodoro" => await _contexto.SesionesPomodoro
                        .CountAsync(s => s.UsuarioId == usuarioId && s.FueCompletada) >= logro.CondicionValor,
                    "XpTotal" => progreso.XpTotal >= logro.CondicionValor,
                    "NivelAlcanzado" => progreso.NivelActual.Numero >= logro.CondicionValor,
                    _ => false
                };

                if (!cumplido)
                {
                    continue;
                }

                _contexto.LogrosUsuario.Add(new LogroUsuario
                {
                    UsuarioId = usuarioId,
                    LogroId = logro.Id,
                    FechaObtenido = DateTime.UtcNow
                });

                if (logro.XpRecompensa > 0)
                {
                    progreso.XpTotal += logro.XpRecompensa;
                }
            }

            await _contexto.SaveChangesAsync();
        }

        public async Task ActualizarRacha(int usuarioId)
        {
            var progreso = await _contexto.ProgresosUsuario.FirstOrDefaultAsync(p => p.UsuarioId == usuarioId);
            if (progreso == null)
            {
                return;
            }

            var hoy = DateOnly.FromDateTime(DateTime.Today);
            var fechaUltima = progreso.FechaUltimaActividad.HasValue
                ? DateOnly.FromDateTime(progreso.FechaUltimaActividad.Value)
                : (DateOnly?)null;

            if (fechaUltima == hoy)
            {
                return;
            }

            if (!fechaUltima.HasValue)
            {
                progreso.FechaUltimaActividad = DateTime.Today;
                progreso.FechaInicioRacha = DateTime.Today;
                progreso.RachaActual = 1;
                progreso.RachaMaxima = Math.Max(progreso.RachaMaxima, progreso.RachaActual);
                await _contexto.SaveChangesAsync();
                return;
            }

            var diferencia = hoy.DayNumber - fechaUltima.Value.DayNumber;

            if (diferencia == 1)
            {
                progreso.RachaActual += 1;
                progreso.FechaUltimaActividad = DateTime.Today;
                progreso.RachaMaxima = Math.Max(progreso.RachaMaxima, progreso.RachaActual);
                progreso.DiaDeGraciaUsado = false;
            }
            else if (diferencia == 2 && !progreso.DiaDeGraciaUsado)
            {
                progreso.DiaDeGraciaUsado = true;
            }
            else if (diferencia > 1)
            {
                progreso.RachaActual = 0;
                progreso.DiaDeGraciaUsado = false;
                progreso.FechaInicioRacha = null;
            }

            await _contexto.SaveChangesAsync();
        }

        public async Task<decimal> CalcularProductividadDiaria(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var habitos = await _contexto.Habitos
                .Where(h => h.UsuarioId == usuarioId && h.EstaActivo)
                .ToListAsync();

            var habitosProgramadosHoy = habitos.Count(h => EstaProgramadoParaHoy(h, hoy));

            if (habitosProgramadosHoy == 0)
            {
                return 0;
            }

            var habitosCompletadosHoy = await _contexto.RegistrosHabito
                .Where(r => r.Habito.UsuarioId == usuarioId
                            && r.Fecha == hoy
                            && r.Estado == "Completado")
                .CountAsync();

            return Math.Round((decimal)habitosCompletadosHoy / habitosProgramadosHoy * 100, 1);
        }

        private bool EstaProgramadoParaHoy(Habito habito, DateOnly hoy)
        {
            return habito.Frecuencia switch
            {
                "Diaria" => true,
                "Semanal" => habito.DiasSemana.Any(d => d.DiaSemana == (int)hoy.DayOfWeek),
                "Personalizada" => habito.DiasSemana.Any(d => d.DiaSemana == (int)hoy.DayOfWeek),
                _ => false
            };
        }
    }
}
