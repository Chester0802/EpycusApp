using System.Net.Http.Json;
using System.Net.Http.Headers;
using System.Text.Json.Serialization;
using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioIA : IServicioIA
    {
        private const int MaxMensajesHistorial = 20;

        private readonly ContextoAplicacion _contexto;
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;
        private readonly string _modelo;

        public ServicioIA(
            ContextoAplicacion contexto,
            IHttpClientFactory httpClientFactory,
            IConfiguration config)
        {
            _contexto  = contexto;
            _httpClient = httpClientFactory.CreateClient("Gemini");
            _apiKey    = config["Gemini:ApiKey"]
                ?? throw new InvalidOperationException("Gemini:ApiKey no está configurado.");
            _modelo    = config["Gemini:Modelo"] ?? "gemini-2.5-flash-lite";
        }

        // ── Público ───────────────────────────────────────────────────────────

        public string NuevaConversacionId() => Guid.NewGuid().ToString();

        public async Task<List<MensajeIA>> ObtenerHistorialAsync(int usuarioId, string conversacionId)
        {
            return await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId)
                .OrderBy(m => m.FechaHora)
                .ToListAsync();
        }

        public async Task<string> ChatAsync(int usuarioId, string mensaje, string conversacionId)
        {
            // Seguridad: si la conversación ya tiene mensajes, validar que pertenezca al usuario
            var primerMensaje = await _contexto.MensajesIA
                .Where(m => m.ConversacionId == conversacionId)
                .Select(m => (int?)m.UsuarioId)
                .FirstOrDefaultAsync();

            if (primerMensaje.HasValue && primerMensaje.Value != usuarioId)
                throw new UnauthorizedAccessException("La conversación no pertenece al usuario.");

            // 1. Construir contexto del usuario para el system prompt
            var ctxUsuario = await ConstruirContextoAsync(usuarioId);

            // 2. Persistir el mensaje del usuario
            _contexto.MensajesIA.Add(new MensajeIA
            {
                ConversacionId = conversacionId,
                UsuarioId      = usuarioId,
                Rol            = "user",
                Contenido      = mensaje,
                FechaHora      = DateTime.UtcNow
            });
            await _contexto.SaveChangesAsync();

            // 3. Cargar los últimos N mensajes (incluye el recién guardado).
            // Nota: TakeLast no es traducible a SQL por EF Core; se toma en orden
            // descendente y se invierte en memoria.
            var historial = (await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId)
                .OrderByDescending(m => m.FechaHora)
                .ThenByDescending(m => m.Id)
                .Take(MaxMensajesHistorial)
                .ToListAsync())
                .OrderBy(m => m.FechaHora)
                .ThenBy(m => m.Id)
                .ToList();

            // 4. Llamar a Gemini
            var respuestaTexto = await LlamarGeminiAsync(ctxUsuario, historial);

            // 5. Persistir la respuesta de EDY
            _contexto.MensajesIA.Add(new MensajeIA
            {
                ConversacionId = conversacionId,
                UsuarioId      = usuarioId,
                Rol            = "model",
                Contenido      = respuestaTexto,
                FechaHora      = DateTime.UtcNow
            });
            await _contexto.SaveChangesAsync();

            return respuestaTexto;
        }

        // ── Privados ──────────────────────────────────────────────────────────

        private async Task<string> LlamarGeminiAsync(ContextoUsuarioIA ctx, List<MensajeIA> historial)
        {
            var systemPrompt = ConstruirSystemPrompt(ctx);

            var contents = historial.Select(m => new GeminiContent
            {
                Role  = m.Rol == "user" ? "user" : "model",
                Parts = new List<GeminiPart> { new() { Text = m.Contenido } }
            }).ToList();

            var request = new GeminiRequest
            {
                SystemInstruction = new GeminiContent
                {
                    Parts = new List<GeminiPart> { new() { Text = systemPrompt } }
                },
                Contents         = contents,
                GenerationConfig = new GeminiConfig { Temperature = 0.75, MaxOutputTokens = 900 }
            };

            var url = $"https://generativelanguage.googleapis.com/v1beta/models/{_modelo}:generateContent";

            try
            {
                var reqMsg = new HttpRequestMessage(HttpMethod.Post, url)
                {
                    Content = JsonContent.Create(request)
                };
                reqMsg.Headers.Authorization = new AuthenticationHeaderValue("Bearer", _apiKey);

                var httpResp = await _httpClient.SendAsync(reqMsg);
                if (!httpResp.IsSuccessStatusCode)
                    return "Lo siento, no pude conectarme a la IA en este momento. Inténtalo de nuevo más tarde. 🔄";

                var geminiResp = await httpResp.Content.ReadFromJsonAsync<GeminiResponse>();
                var texto = geminiResp?.Candidates?.FirstOrDefault()?.Content?.Parts?.FirstOrDefault()?.Text;

                return string.IsNullOrWhiteSpace(texto)
                    ? "No recibí respuesta de la IA. Intenta reformular tu pregunta. 🔄"
                    : texto;
            }
            catch (Exception)
            {
                // No exponer detalles sensibles en la respuesta al usuario
                return "Hubo un error al comunicarme con EDY. Verifica tu conexión e inténtalo de nuevo. 🔄";
            }
        }

        private async Task<ContextoUsuarioIA> ConstruirContextoAsync(int usuarioId)
        {
            var usuario = await _contexto.Usuarios
                .Include(u => u.Progreso).ThenInclude(p => p.NivelActual)
                .Include(u => u.Habitos.Where(h => h.EstaActivo)).ThenInclude(h => h.Categoria)
                .Include(u => u.Misiones.Where(m => m.Estado != "Completada"))
                .Include(u => u.EstadosAnimo.OrderByDescending(e => e.Fecha).Take(7))
                .Include(u => u.LogrosUsuario)
                .FirstOrDefaultAsync(u => u.Id == usuarioId)
                ?? throw new KeyNotFoundException($"Usuario {usuarioId} no encontrado.");

            var ultimoAnimo = usuario.EstadosAnimo
                .OrderByDescending(e => e.Fecha)
                .FirstOrDefault();

            // ── Resumen de la última semana (últimos 7 días) ──────────────────
            var hoyFecha       = DateOnly.FromDateTime(DateTime.Today);
            var inicioSemana   = hoyFecha.AddDays(-6);
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
                              && m.Estado == "Completada"
                              && m.FechaCompletado != null
                              && m.FechaCompletado >= inicioSemanaDt);

            var cultura = new System.Globalization.CultureInfo("es-ES");
            var animosSemana = usuario.EstadosAnimo
                .Where(e => e.Fecha >= inicioSemana)
                .OrderBy(e => e.Fecha)
                .Select(e => $"{e.Fecha.ToDateTime(TimeOnly.MinValue).ToString("dddd dd/MM", cultura)}: {e.Estado}")
                .ToList();

            return new ContextoUsuarioIA
            {
                Nombre                = usuario.Nombre,
                NivelNumero           = usuario.Progreso?.NivelActual?.Numero ?? 0,
                TituloNivel           = usuario.Progreso?.NivelActual?.Titulo ?? "Iniciado",
                XpTotal               = usuario.Progreso?.XpTotal ?? 0,
                RachaActual           = usuario.Progreso?.RachaActual ?? 0,
                ProductividadDiaria   = usuario.Progreso?.ProductividadDiaria ?? 0,
                UltimoEstadoAnimo     = ultimoAnimo?.Estado ?? "Sin registro",
                DiasDesdeUltimoAnimo  = ultimoAnimo != null
                    ? DateOnly.FromDateTime(DateTime.Today).DayNumber - ultimoAnimo.Fecha.DayNumber
                    : -1,
                Habitos = usuario.Habitos.Take(6).Select(h => new HabitoIA
                {
                    Nombre     = h.Nombre,
                    Categoria  = h.Categoria?.Nombre ?? "General",
                    Frecuencia = h.Frecuencia,
                    Racha      = h.RachaActual
                }).ToList(),
                Misiones = usuario.Misiones
                    .OrderByDescending(m => m.Prioridad == "Alta")
                    .ThenByDescending(m => m.Prioridad == "Media")
                    .Take(5).Select(m => new MisionIA
                    {
                        Nombre         = m.Nombre,
                        Estado         = m.Estado,
                        Prioridad      = m.Prioridad,
                        DiasRestantes  = m.FechaLimite.DayNumber - DateOnly.FromDateTime(DateTime.Today).DayNumber
                    }).ToList(),
                TotalLogros = usuario.LogrosUsuario.Count,
                HabitosCompletadosSemana   = habitosCompletadosSemana,
                PomodorosSemana            = sesionesSemana.Count,
                PomodorosCompletadosSemana = sesionesSemana.Count(s => s.FueCompletada),
                CiclosPomodoroSemana       = sesionesSemana.Sum(s => s.CiclosCompletados),
                MisionesCompletadasSemana  = misionesCompletadasSemana,
                AnimosSemana               = animosSemana
            };
        }

        private static string ConstruirSystemPrompt(ContextoUsuarioIA ctx)
        {
            var hoy = DateTime.Today.ToString("dddd, dd 'de' MMMM 'de' yyyy", new System.Globalization.CultureInfo("es-ES"));

            var habitos = ctx.Habitos.Count > 0
                ? string.Join("\n", ctx.Habitos.Select(h =>
                    $"  • {h.Nombre} ({h.Categoria}) — {h.Frecuencia}, racha: {h.Racha} días"))
                : "  • Sin hábitos activos todavía";

            var misiones = ctx.Misiones.Count > 0
                ? string.Join("\n", ctx.Misiones.Select(m =>
                {
                    var estado = m.DiasRestantes >= 0
                        ? $"{m.DiasRestantes} días restantes"
                        : $"vencida hace {Math.Abs(m.DiasRestantes)} días ⚠️";
                    return $"  • [{m.Prioridad}] {m.Nombre} — {m.Estado} · {estado}";
                }))
                : "  • Sin misiones pendientes";

            var animoInfo = ctx.DiasDesdeUltimoAnimo >= 0
                ? $"{ctx.UltimoEstadoAnimo} (registrado hace {ctx.DiasDesdeUltimoAnimo} " +
                  $"día{(ctx.DiasDesdeUltimoAnimo != 1 ? "s" : "")})"
                : "Sin registros de estado de ánimo recientes";

            var animosSemana = ctx.AnimosSemana.Count > 0
                ? string.Join("\n", ctx.AnimosSemana.Select(a => $"  • {a}"))
                : "  • Sin registros esta semana";

            return $"""
                Eres EDY, el asistente de inteligencia artificial de EPYCUS — una plataforma académica
                gamificada diseñada para estudiantes universitarios. Tu misión es actuar como coach personal
                que combina productividad, bienestar y motivación.

                ## Filosofía EPYCUS:
                - PRODUCTIVIDAD: Ayudas a completar misiones y mantener hábitos con consistencia.
                - BIENESTAR (ODS 3): Cuidas la salud mental y emocional. Si detectas señales de estrés,
                  agotamiento o desmotivación, priorizas el bienestar sobre la productividad.
                - GAMIFICACIÓN: El aprendizaje es una aventura. Celebras logros, XP, niveles y rachas.
                  Incluso los pequeños avances merecen reconocimiento.

                ## Cómo te comportas:
                - Respondes SIEMPRE en español.
                - Tono cercano, motivador y empático — eres un compañero de estudio, no un bot frío.
                - Máximo 3-4 párrafos por respuesta (salvo que el usuario pida algo extenso o técnico).
                - Usas emojis con moderación (máx 3 por respuesta).
                - Solo referencias datos reales del usuario que te proporciono — nunca inventas información.
                - Si el usuario pregunta algo fuera de tu alcance (medicina, legal, financiero), redirige con empatía.
                - Si el usuario pregunta quién eres o cómo te llamas, dices que eres EDY, el asistente de EPYCUS.

                ## Fecha actual: {hoy}

                ## Perfil del usuario ({ctx.Nombre}):
                - Nivel: {ctx.NivelNumero} — "{ctx.TituloNivel}"
                - XP acumulado: {ctx.XpTotal:N0} puntos
                - Racha actual: {ctx.RachaActual} días consecutivos
                - Productividad diaria registrada: {ctx.ProductividadDiaria:F0}%
                - Estado de ánimo reciente: {animoInfo}
                - Logros desbloqueados: {ctx.TotalLogros}

                ## Resumen de la última semana (últimos 7 días):
                - Hábitos completados: {ctx.HabitosCompletadosSemana}
                - Sesiones Pomodoro: {ctx.PomodorosSemana} iniciadas, {ctx.PomodorosCompletadosSemana} completadas ({ctx.CiclosPomodoroSemana} ciclos en total)
                - Misiones completadas: {ctx.MisionesCompletadasSemana}
                - Estados de ánimo registrados:
                {animosSemana}

                Usa este resumen semanal para personalizar tus consejos: reconoce el esfuerzo si la
                semana fue productiva, y motiva con empatía (sin regañar) si fue floja.

                ## Hábitos activos ({ctx.Habitos.Count}):
                {habitos}

                ## Misiones pendientes ({ctx.Misiones.Count}):
                {misiones}
                """;
        }
    }

    // ── Clases de contexto interno ────────────────────────────────────────────

    internal sealed class ContextoUsuarioIA
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

        // Resumen de la última semana
        public int HabitosCompletadosSemana { get; set; }
        public int PomodorosSemana { get; set; }
        public int PomodorosCompletadosSemana { get; set; }
        public int CiclosPomodoroSemana { get; set; }
        public int MisionesCompletadasSemana { get; set; }
        public List<string> AnimosSemana { get; set; } = new();
    }

    internal sealed class HabitoIA
    {
        public string Nombre { get; set; } = string.Empty;
        public string Categoria { get; set; } = string.Empty;
        public string Frecuencia { get; set; } = string.Empty;
        public int Racha { get; set; }
    }

    internal sealed class MisionIA
    {
        public string Nombre { get; set; } = string.Empty;
        public string Estado { get; set; } = string.Empty;
        public string Prioridad { get; set; } = string.Empty;
        public int DiasRestantes { get; set; }
    }

    // ── DTOs Gemini API ───────────────────────────────────────────────────────

    internal sealed class GeminiRequest
    {
        [JsonPropertyName("system_instruction")]
        public GeminiContent SystemInstruction { get; set; } = new();

        [JsonPropertyName("contents")]
        public List<GeminiContent> Contents { get; set; } = new();

        [JsonPropertyName("generationConfig")]
        public GeminiConfig? GenerationConfig { get; set; }
    }

    internal sealed class GeminiContent
    {
        [JsonPropertyName("role")]
        public string? Role { get; set; }

        [JsonPropertyName("parts")]
        public List<GeminiPart> Parts { get; set; } = new();
    }

    internal sealed class GeminiPart
    {
        [JsonPropertyName("text")]
        public string Text { get; set; } = string.Empty;
    }

    internal sealed class GeminiConfig
    {
        [JsonPropertyName("temperature")]
        public double Temperature { get; set; } = 0.75;

        [JsonPropertyName("maxOutputTokens")]
        public int MaxOutputTokens { get; set; } = 900;
    }

    internal sealed class GeminiResponse
    {
        [JsonPropertyName("candidates")]
        public List<GeminiCandidate>? Candidates { get; set; }
    }

    internal sealed class GeminiCandidate
    {
        [JsonPropertyName("content")]
        public GeminiContent? Content { get; set; }
    }
}
