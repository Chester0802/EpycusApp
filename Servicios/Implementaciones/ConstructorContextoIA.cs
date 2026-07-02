using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ConstructorContextoIA
    {
        private readonly ContextoAplicacion _contexto;

        public ConstructorContextoIA(ContextoAplicacion contexto)
        {
            _contexto = contexto;
        }

        public async Task<ContextoUsuarioIA> ConstruirAsync(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var animosRecientes = await _contexto.EstadosAnimo
                .AsNoTracking()
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .Take(7)
                .ToListAsync();

            var usuario = await _contexto.Usuarios
                .AsNoTracking()
                .Include(u => u.Progreso).ThenInclude(p => p.NivelActual)
                .Include(u => u.Habitos.Where(h => h.EstaActivo)).ThenInclude(h => h.Categoria)
                .Include(u => u.Misiones.Where(m => m.Estado != "Completado"))
                .Include(u => u.LogrosUsuario)
                .FirstOrDefaultAsync(u => u.Id == usuarioId)
                ?? throw new KeyNotFoundException($"Usuario {usuarioId} no encontrado.");

            var ultimoAnimo = animosRecientes.FirstOrDefault();

            var inicioSemana = hoy.AddDays(-6);
            var inicioSemanaDt = DateTime.Today.AddDays(-6);

            var habitosCompletadosSemana = await _contexto.RegistrosHabito
                .CountAsync(r => r.Habito.UsuarioId == usuarioId
                              && r.Fecha >= inicioSemana
                              && r.Estado == "Completado");

            var sesionesSemana = await _contexto.SesionesPomodoro
                .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= inicioSemanaDt)
                .Select(s => new { s.FueCompletada, s.CiclosCompletados })
                .ToListAsync();

            var misionesCompletadasSemana = await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId
                              && m.Estado == "Completado"
                              && m.FechaCompletado != null
                              && m.FechaCompletado >= inicioSemanaDt);

            var cultura = new System.Globalization.CultureInfo("es-ES");
            var animosSemana = animosRecientes
                .Where(e => e.Fecha >= inicioSemana)
                .OrderBy(e => e.Fecha)
                .Select(e => $"{e.Fecha.ToDateTime(TimeOnly.MinValue).ToString("dddd dd/MM", cultura)}: {e.Estado}")
                .ToList();

            return new ContextoUsuarioIA
            {
                Nombre = usuario.Nombre,
                NivelNumero = usuario.Progreso?.NivelActual?.Numero ?? 0,
                TituloNivel = usuario.Progreso?.NivelActual?.Titulo ?? "Iniciado",
                XpTotal = usuario.Progreso?.XpTotal ?? 0,
                RachaActual = usuario.Progreso?.RachaActual ?? 0,
                ProductividadDiaria = usuario.Progreso?.ProductividadDiaria ?? 0,
                UltimoEstadoAnimo = ultimoAnimo?.Estado ?? "Sin registro",
                DiasDesdeUltimoAnimo = ultimoAnimo != null
                    ? DateOnly.FromDateTime(DateTime.Today).DayNumber - ultimoAnimo.Fecha.DayNumber
                    : -1,
                Habitos = usuario.Habitos.Take(6).Select(h => new HabitoIA
                {
                    Nombre = h.Nombre,
                    Categoria = h.Categoria?.Nombre ?? "General",
                    Frecuencia = h.Frecuencia,
                    Racha = h.RachaActual
                }).ToList(),
                Misiones = usuario.Misiones
                    .OrderByDescending(m => m.Prioridad == "Alta")
                    .ThenByDescending(m => m.Prioridad == "Media")
                    .Take(5).Select(m => new MisionIA
                    {
                        Nombre = m.Nombre,
                        Estado = m.Estado,
                        Prioridad = m.Prioridad,
                        DiasRestantes = m.FechaLimite.DayNumber - DateOnly.FromDateTime(DateTime.Today).DayNumber
                    }).ToList(),
                TotalLogros = usuario.LogrosUsuario.Count,
                HabitosCompletadosSemana = habitosCompletadosSemana,
                PomodorosSemana = sesionesSemana.Count,
                PomodorosCompletadosSemana = sesionesSemana.Count(s => s.FueCompletada),
                CiclosPomodoroSemana = sesionesSemana.Sum(s => s.CiclosCompletados),
                MisionesCompletadasSemana = misionesCompletadasSemana,
                AnimosSemana = animosSemana
            };
        }

        public static string ConstruirSystemPrompt(ContextoUsuarioIA ctx)
        {
            var hoy = DateTime.Today.ToString("dddd, dd 'de' MMMM 'de' yyyy", new System.Globalization.CultureInfo("es-ES"));

            var habitos = ctx.Habitos.Count > 0
                ? string.Join("\n", ctx.Habitos.Select(h =>
                    $"  - {h.Nombre} ({h.Categoria}) - {h.Frecuencia}, racha: {h.Racha} dias"))
                : "  - Sin habitos activos todavia";

            var misiones = ctx.Misiones.Count > 0
                ? string.Join("\n", ctx.Misiones.Select(m =>
                {
                    var estado = m.DiasRestantes >= 0
                        ? $"{m.DiasRestantes} dias restantes"
                        : $"vencida hace {Math.Abs(m.DiasRestantes)} dias - vencida";
                    return $"  - [{m.Prioridad}] {m.Nombre} - {m.Estado} - {estado}";
                }))
                : "  - Sin misiones pendientes";

            var animoInfo = ctx.DiasDesdeUltimoAnimo >= 0
                ? $"{ctx.UltimoEstadoAnimo} (registrado hace {ctx.DiasDesdeUltimoAnimo} " +
                  $"dia{(ctx.DiasDesdeUltimoAnimo != 1 ? "s" : "")})"
                : "Sin registros de estado de animo recientes";

            var animosSemana = ctx.AnimosSemana.Count > 0
                ? string.Join("\n", ctx.AnimosSemana.Select(a => $"  - {a}"))
                : "  - Sin registros esta semana";

            return $"""
                Eres EDY AI, el asistente de inteligencia artificial de EPYCUS - una plataforma academica
                gamificada disenada para estudiantes universitarios. Tu mision es actuar como coach personal
                que combina productividad, bienestar y motivacion.

                ## Filosofia EPYCUS:
                - PRODUCTIVIDAD: Ayudas a completar misiones y mantener habitos con consistencia.
                - BIENESTAR (ODS 3): Cuidas la salud mental y emocional. Si detectas senales de estres,
                  agotamiento o desmotivacion, priorizas el bienestar sobre la productividad.
                - GAMIFICACION: El aprendizaje es una aventura. Celebras logros, XP, niveles y rachas.
                  Incluso los pequenos avances merecen reconocimiento.

                ## Como te comportas:
                - Respondes SIEMPRE en espanol.
                - Tono cercano, motivador y empatico - eres un companero de estudio, no un bot frio.
                - Maximo 3-4 parrafos por respuesta (salvo que el usuario pida algo extenso o tecnico).
                - Usas emojis con moderacion (max 3 por respuesta).
                - Solo referencias datos reales del usuario que te proporciono - nunca inventas informacion.
                - Si el usuario pregunta algo fuera de tu alcance (medicina, legal, financiero), redirige con empatia.
                - Si el usuario pregunta quien eres o como te llamas, dices que eres EDY AI, el asistente de EPYCUS.

                ## Fecha actual: {hoy}

                ## Perfil del usuario ({ctx.Nombre}):
                - Nivel: {ctx.NivelNumero} - "{ctx.TituloNivel}"
                - XP acumulado: {ctx.XpTotal:N0} puntos
                - Racha actual: {ctx.RachaActual} dias consecutivos
                - Productividad diaria registrada: {ctx.ProductividadDiaria:F0}%
                - Estado de animo reciente: {animoInfo}
                - Logros desbloqueados: {ctx.TotalLogros}

                ## Resumen de la ultima semana (ultimos 7 dias):
                - Habitos completados: {ctx.HabitosCompletadosSemana}
                - Sesiones Pomodoro: {ctx.PomodorosSemana} iniciadas, {ctx.PomodorosCompletadosSemana} completadas ({ctx.CiclosPomodoroSemana} ciclos en total)
                - Misiones completadas: {ctx.MisionesCompletadasSemana}
                - Estados de animo registrados:
                {animosSemana}

                Usa este resumen semanal para personalizar tus consejos: reconoce el esfuerzo si la
                semana fue productiva, y motiva con empatia (sin reganar) si fue floja.

                ## Habitos activos ({ctx.Habitos.Count}):
                {habitos}

                ## Misiones pendientes ({ctx.Misiones.Count}):
                {misiones}
                """;
        }
    }

    public sealed class ContextoUsuarioIA
    {
        public string Nombre { get; set; } = string.Empty;
        public int NivelNumero { get; set; }
        public string TituloNivel { get; set; } = string.Empty;
        public int XpTotal { get; set; }
        public int RachaActual { get; set; }
        public decimal ProductividadDiaria { get; set; }
        public string UltimoEstadoAnimo { get; set; } = string.Empty;
        public int DiasDesdeUltimoAnimo { get; set; }
        public List<HabitoIA> Habitos { get; set; } = new();
        public List<MisionIA> Misiones { get; set; } = new();
        public int TotalLogros { get; set; }
        public int HabitosCompletadosSemana { get; set; }
        public int PomodorosSemana { get; set; }
        public int PomodorosCompletadosSemana { get; set; }
        public int CiclosPomodoroSemana { get; set; }
        public int MisionesCompletadasSemana { get; set; }
        public List<string> AnimosSemana { get; set; } = new();
    }

    public sealed class HabitoIA
    {
        public string Nombre { get; set; } = string.Empty;
        public string Categoria { get; set; } = string.Empty;
        public string Frecuencia { get; set; } = string.Empty;
        public int Racha { get; set; }
    }

    public sealed class MisionIA
    {
        public string Nombre { get; set; } = string.Empty;
        public string Estado { get; set; } = string.Empty;
        public string Prioridad { get; set; } = string.Empty;
        public int DiasRestantes { get; set; }
    }
}
