using System.Net.Http.Json;
using System.Text.Json.Serialization;
using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioIA : IServicioIA
    {
        private const int MaxMensajesHistorial = 20;

        private readonly ContextoAplicacion _contexto;
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;
        private readonly string _modelo;
        private readonly ILogger<ServicioIA> _logger;

        public ServicioIA(
            ContextoAplicacion contexto,
            IHttpClientFactory httpClientFactory,
            IConfiguration config,
            ILogger<ServicioIA> logger)
        {
            _contexto  = contexto;
            _httpClient = httpClientFactory.CreateClient("Gemini");
            _apiKey    = config["Gemini:ApiKey"]
                ?? throw new InvalidOperationException("Gemini:ApiKey no estÃ¡ configurado.");
            _modelo    = config["Gemini:Modelo"] ?? "gemini-2.5-flash-lite";
            _logger = logger;
        }

        // â”€â”€ PÃºblico â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            // Seguridad: si la conversaciÃ³n ya tiene mensajes, validar que pertenezca al usuario
            var primerMensaje = await _contexto.MensajesIA
                .Where(m => m.ConversacionId == conversacionId)
                .Select(m => (int?)m.UsuarioId)
                .FirstOrDefaultAsync();

            if (primerMensaje.HasValue && primerMensaje.Value != usuarioId)
                throw new UnauthorizedAccessException("La conversaciÃ³n no pertenece al usuario.");

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

            // 3. Cargar los Ãºltimos N mensajes (incluye el reciÃ©n guardado).
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

        // â”€â”€ Privados â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

            var url = $"https://generativelanguage.googleapis.com/v1beta/models/{_modelo}:generateContent?key={_apiKey}";

            var maxReintentos = 2;
            for (var intento = 0; intento <= maxReintentos; intento++)
            {
                try
                {
                    using var cts = new CancellationTokenSource(TimeSpan.FromSeconds(30));
                    var reqMsg = new HttpRequestMessage(HttpMethod.Post, url)
                    {
                        Content = JsonContent.Create(request)
                    };

                    var httpResp = await _httpClient.SendAsync(reqMsg, cts.Token);
                    if (!httpResp.IsSuccessStatusCode)
                    {
                        if (intento < maxReintentos && (int)httpResp.StatusCode >= 500)
                        {
                            await Task.Delay(TimeSpan.FromSeconds(1 << intento));
                            continue;
                        }
                        return "Lo siento, no pude conectarme a la IA en este momento. IntÃ©ntalo de nuevo mÃ¡s tarde. ðŸ”„";
                    }

                    var geminiResp = await httpResp.Content.ReadFromJsonAsync<GeminiResponse>(cancellationToken: cts.Token);

                    if (geminiResp?.PromptFeedback?.BlockReason != null)
                    {
                        _logger.LogWarning("Gemini bloqueó la respuesta: {BlockReason}", geminiResp.PromptFeedback.BlockReason);
                        return "No puedo responder a eso. Intenta reformular tu pregunta de otra manera.";
                    }

                    var candidate = geminiResp?.Candidates?.FirstOrDefault();
                    if (candidate?.FinishReason == "SAFETY" || candidate?.FinishReason == "BLOCKLIST")
                    {
                        _logger.LogWarning("Gemini candidate bloqueado: {FinishReason}", candidate.FinishReason);
                        return "No puedo responder a eso. Intenta reformular tu pregunta de otra manera.";
                    }

                    var texto = candidate?.Content?.Parts?.FirstOrDefault()?.Text;

                    return string.IsNullOrWhiteSpace(texto)
                        ? "No recibÃ­ respuesta de la IA. Intenta reformular tu pregunta. ðŸ”„"
                        : texto;
                }
                catch (OperationCanceledException)
                {
                    if (intento < maxReintentos) continue;
                    return "La conexiÃ³n con EDY tardÃ³ demasiado. IntÃ©ntalo de nuevo. ðŸ”„";
                }
                catch (HttpRequestException)
                {
                    if (intento < maxReintentos)
                    {
                        await Task.Delay(TimeSpan.FromSeconds(1 << intento));
                        continue;
                    }
                    return "Hubo un error al comunicarme con EDY. Verifica tu conexiÃ³n e intÃ©ntalo de nuevo. ðŸ”„";
                }
            }

            return "Hubo un error al comunicarme con EDY. Verifica tu conexiÃ³n e intÃ©ntalo de nuevo. ðŸ”„";
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

            // â”€â”€ Resumen de la Ãºltima semana (Ãºltimos 7 dÃ­as) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
                    $"  â€¢ {h.Nombre} ({h.Categoria}) â€” {h.Frecuencia}, racha: {h.Racha} dÃ­as"))
                : "  â€¢ Sin hÃ¡bitos activos todavÃ­a";

            var misiones = ctx.Misiones.Count > 0
                ? string.Join("\n", ctx.Misiones.Select(m =>
                {
                    var estado = m.DiasRestantes >= 0
                        ? $"{m.DiasRestantes} dÃ­as restantes"
                        : $"vencida hace {Math.Abs(m.DiasRestantes)} dÃ­as âš ï¸";
                    return $"  â€¢ [{m.Prioridad}] {m.Nombre} â€” {m.Estado} Â· {estado}";
                }))
                : "  â€¢ Sin misiones pendientes";

            var animoInfo = ctx.DiasDesdeUltimoAnimo >= 0
                ? $"{ctx.UltimoEstadoAnimo} (registrado hace {ctx.DiasDesdeUltimoAnimo} " +
                  $"dÃ­a{(ctx.DiasDesdeUltimoAnimo != 1 ? "s" : "")})"
                : "Sin registros de estado de Ã¡nimo recientes";

            var animosSemana = ctx.AnimosSemana.Count > 0
                ? string.Join("\n", ctx.AnimosSemana.Select(a => $"  â€¢ {a}"))
                : "  â€¢ Sin registros esta semana";

            return $"""
                Eres EDY, el asistente de inteligencia artificial de EPYCUS â€” una plataforma acadÃ©mica
                gamificada diseÃ±ada para estudiantes universitarios. Tu misiÃ³n es actuar como coach personal
                que combina productividad, bienestar y motivaciÃ³n.

                ## FilosofÃ­a EPYCUS:
                - PRODUCTIVIDAD: Ayudas a completar misiones y mantener hÃ¡bitos con consistencia.
                - BIENESTAR (ODS 3): Cuidas la salud mental y emocional. Si detectas seÃ±ales de estrÃ©s,
                  agotamiento o desmotivaciÃ³n, priorizas el bienestar sobre la productividad.
                - GAMIFICACIÃ“N: El aprendizaje es una aventura. Celebras logros, XP, niveles y rachas.
                  Incluso los pequeÃ±os avances merecen reconocimiento.

                ## CÃ³mo te comportas:
                - Respondes SIEMPRE en espaÃ±ol.
                - Tono cercano, motivador y empÃ¡tico â€” eres un compaÃ±ero de estudio, no un bot frÃ­o.
                - MÃ¡ximo 3-4 pÃ¡rrafos por respuesta (salvo que el usuario pida algo extenso o tÃ©cnico).
                - Usas emojis con moderaciÃ³n (mÃ¡x 3 por respuesta).
                - Solo referencias datos reales del usuario que te proporciono â€” nunca inventas informaciÃ³n.
                - Si el usuario pregunta algo fuera de tu alcance (medicina, legal, financiero), redirige con empatÃ­a.
                - Si el usuario pregunta quiÃ©n eres o cÃ³mo te llamas, dices que eres EDY, el asistente de EPYCUS.

                ## Fecha actual: {hoy}

                ## Perfil del usuario ({ctx.Nombre}):
                - Nivel: {ctx.NivelNumero} â€” "{ctx.TituloNivel}"
                - XP acumulado: {ctx.XpTotal:N0} puntos
                - Racha actual: {ctx.RachaActual} dÃ­as consecutivos
                - Productividad diaria registrada: {ctx.ProductividadDiaria:F0}%
                - Estado de Ã¡nimo reciente: {animoInfo}
                - Logros desbloqueados: {ctx.TotalLogros}

                ## Resumen de la Ãºltima semana (Ãºltimos 7 dÃ­as):
                - HÃ¡bitos completados: {ctx.HabitosCompletadosSemana}
                - Sesiones Pomodoro: {ctx.PomodorosSemana} iniciadas, {ctx.PomodorosCompletadosSemana} completadas ({ctx.CiclosPomodoroSemana} ciclos en total)
                - Misiones completadas: {ctx.MisionesCompletadasSemana}
                - Estados de Ã¡nimo registrados:
                {animosSemana}

                Usa este resumen semanal para personalizar tus consejos: reconoce el esfuerzo si la
                semana fue productiva, y motiva con empatÃ­a (sin regaÃ±ar) si fue floja.

                ## HÃ¡bitos activos ({ctx.Habitos.Count}):
                {habitos}

                ## Misiones pendientes ({ctx.Misiones.Count}):
                {misiones}
                """;
        }
    }

    // â”€â”€ Clases de contexto interno â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

        // Resumen de la Ãºltima semana
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

    // â”€â”€ DTOs Gemini API â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

        [JsonPropertyName("promptFeedback")]
        public GeminiPromptFeedback? PromptFeedback { get; set; }
    }

    internal sealed class GeminiCandidate
    {
        [JsonPropertyName("content")]
        public GeminiContent? Content { get; set; }

        [JsonPropertyName("finishReason")]
        public string? FinishReason { get; set; }
    }

    internal sealed class GeminiPromptFeedback
    {
        [JsonPropertyName("blockReason")]
        public string? BlockReason { get; set; }
    }
}
