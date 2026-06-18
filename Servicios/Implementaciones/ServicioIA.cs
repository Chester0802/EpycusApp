using System.Net.Http.Json;
using System.Text.Json.Serialization;
using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Ia;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioIA : IServicioIA
    {
        private const int MaxMensajesHistorial = 20;
        private const int MaxMensajesPorDia = 50;
        private const int XpPorMensaje = 1;

        private readonly ContextoAplicacion _contexto;
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;
        private readonly string _modelo;
        private readonly IServicioGamificacion _gamificacion;
        private readonly ILogger<ServicioIA> _logger;

        public ServicioIA(
            ContextoAplicacion contexto,
            IHttpClientFactory httpClientFactory,
            IConfiguration config,
            IServicioGamificacion gamificacion,
            ILogger<ServicioIA> logger)
        {
            _contexto = contexto;
            _httpClient = httpClientFactory.CreateClient("Gemini");
            _apiKey = config["Gemini:ApiKey"]
                ?? throw new InvalidOperationException("Gemini:ApiKey no esta configurado.");
            _modelo = config["Gemini:Modelo"] ?? "gemini-2.5-flash-lite";
            _gamificacion = gamificacion;
            _logger = logger;
        }

        public string NuevaConversacionId() => Guid.NewGuid().ToString();

        public async Task<List<MensajeIA>> ObtenerHistorialAsync(int usuarioId, string conversacionId)
        {
            return await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId)
                .OrderBy(m => m.FechaHora)
                .ToListAsync();
        }

        public async Task<List<ConversacionResumen>> ObtenerConversacionesAsync(int usuarioId)
        {
            var conversaciones = await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId)
                .GroupBy(m => m.ConversacionId)
                .Select(g => new ConversacionResumen
                {
                    ConversacionId = g.Key,
                    UltimoMensaje = g.Max(m => m.FechaHora),
                    CantidadMensajes = g.Count(),
                    Titulo = g.OrderBy(m => m.FechaHora).Select(m => m.Contenido).FirstOrDefault()
                })
                .OrderByDescending(c => c.UltimoMensaje)
                .Take(20)
                .ToListAsync();

            foreach (var c in conversaciones)
            {
                if (!string.IsNullOrEmpty(c.Titulo) && c.Titulo.Length > 60)
                    c.Titulo = c.Titulo[..60] + "...";
            }

            return conversaciones;
        }

        public async Task<List<string>> ObtenerSugerenciasPersonalizadasAsync(int usuarioId)
        {
            var sugerencias = new List<string>();

            var misionesUrgentes = await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId && m.Estado != "Completada"
                    && m.FechaLimite.DayNumber - DateOnly.FromDateTime(DateTime.Today).DayNumber <= 2);
            if (misionesUrgentes > 0)
                sugerencias.Add($"Tengo {misionesUrgentes} mision(es) urgente(s)");

            var habitosHoy = await _contexto.RegistrosHabito
                .CountAsync(r => r.Habito.UsuarioId == usuarioId && r.Fecha == DateOnly.FromDateTime(DateTime.Today));
            if (habitosHoy == 0)
                sugerencias.Add("Recordarme mis habitos de hoy");

            var racha = await _contexto.ProgresosUsuario
                .Where(p => p.UsuarioId == usuarioId)
                .Select(p => p.RachaActual)
                .FirstOrDefaultAsync();
            if (racha > 0)
                sugerencias.Add($"Como mejorar mi racha de {racha} dias");

            sugerencias.Add("Como van mis habitos");
            sugerencias.Add("Dame un consejo de productividad");
            sugerencias.Add("Me siento desmotivado");

            return sugerencias.Distinct().Take(6).ToList();
        }

        public async Task<BienestarContextoIA?> ObtenerBienestarContextoAsync(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);
            var ultimosAnimos = await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .Take(3)
                .Select(e => e.Estado)
                .ToListAsync();

            var animosNegativos = ultimosAnimos.Count(a => a is "Triste" or "Enojado" or "Estrés");

            var pomodoroHoy = await _contexto.SesionesPomodoro
                .CountAsync(s => s.UsuarioId == usuarioId && s.FechaInicio >= DateTime.Today);

            var misionesPendientes = await _contexto.Misiones
                .CountAsync(m => m.UsuarioId == usuarioId && m.Estado != "Completada");

            var alertas = await _contexto.MensajesIA
                .Where(m => m.UsuarioId == usuarioId && m.Rol == "alerta_bienestar"
                    && m.FechaHora >= DateTime.UtcNow.AddDays(-1))
                .AnyAsync();

            return new BienestarContextoIA
            {
                TieneAlertasActivas = alertas || animosNegativos >= 2,
                DiasAnimoNegativo = animosNegativos,
                PomodoroExcesivo = pomodoroHoy > 6,
                SobrecargaMisiones = misionesPendientes > 8,
                UltimoEstadoAnimo = ultimosAnimos.FirstOrDefault()
            };
        }

        public async Task RegistrarFeedbackAsync(int usuarioId, int mensajeId, bool util)
        {
            var mensaje = await _contexto.MensajesIA
                .FirstOrDefaultAsync(m => m.Id == mensajeId && m.UsuarioId == usuarioId);
            if (mensaje != null)
            {
                mensaje.FeedbackRecibido = true;
                mensaje.FeedbackUtil = util;
                await _contexto.SaveChangesAsync();
            }
        }

        public async Task<int> ObtenerMensajesHoyAsync(int usuarioId)
        {
            var hoy = DateTime.UtcNow.Date;
            return await _contexto.MensajesIA
                .CountAsync(m => m.UsuarioId == usuarioId && m.FechaHora >= hoy);
        }

        public async Task<string> ChatAsync(int usuarioId, string mensaje, string conversacionId)
        {
            var primerMensaje = await _contexto.MensajesIA
                .Where(m => m.ConversacionId == conversacionId)
                .Select(m => (int?)m.UsuarioId)
                .FirstOrDefaultAsync();

            if (primerMensaje.HasValue && primerMensaje.Value != usuarioId)
                throw new UnauthorizedAccessException("La conversacion no pertenece al usuario.");

            var mensajesHoy = await ObtenerMensajesHoyAsync(usuarioId);
            if (mensajesHoy >= MaxMensajesPorDia)
                throw new InvalidOperationException($"Has alcanzado el limite diario de {MaxMensajesPorDia} mensajes. Vuelve manana.");

            var ctxUsuario = await ConstruirContextoAsync(usuarioId);

            using var transaction = await _contexto.Database.BeginTransactionAsync();

            try
            {
                var msgUsuario = new MensajeIA
                {
                    ConversacionId = conversacionId,
                    UsuarioId = usuarioId,
                    Rol = "user",
                    Contenido = mensaje,
                    FechaHora = DateTime.UtcNow
                };
                _contexto.MensajesIA.Add(msgUsuario);
                await _contexto.SaveChangesAsync();

                var historial = (await _contexto.MensajesIA
                    .Where(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId)
                    .OrderByDescending(m => m.FechaHora)
                    .ThenByDescending(m => m.Id)
                    .Take(MaxMensajesHistorial)
                    .ToListAsync())
                    .OrderBy(m => m.FechaHora)
                    .ThenBy(m => m.Id)
                    .ToList();

                var resumen = await GenerarResumenSiNecesarioAsync(usuarioId, conversacionId, historial);

                var respuestaTexto = await LlamarGeminiAsync(ctxUsuario, historial, resumen);

                _contexto.MensajesIA.Add(new MensajeIA
                {
                    ConversacionId = conversacionId,
                    UsuarioId = usuarioId,
                    Rol = "model",
                    Contenido = respuestaTexto,
                    FechaHora = DateTime.UtcNow
                });
                await _contexto.SaveChangesAsync();

                await transaction.CommitAsync();

                await _gamificacion.SumarXP(usuarioId, XpPorMensaje);

                return respuestaTexto;
            }
            catch
            {
                await transaction.RollbackAsync();
                throw;
            }
        }

        private async Task<string?> GenerarResumenSiNecesarioAsync(int usuarioId, string conversacionId, List<MensajeIA> historial)
        {
            var totalMensajes = await _contexto.MensajesIA
                .CountAsync(m => m.UsuarioId == usuarioId && m.ConversacionId == conversacionId);

            if (totalMensajes > MaxMensajesHistorial && historial.Count >= MaxMensajesHistorial)
            {
                var msgAntiguos = historial.Take(5).ToList();
                var resumenPartes = msgAntiguos.Select(m => $"[{m.Rol}]: {m.Contenido[..Math.Min(m.Contenido.Length, 100)]}");
                return "Resumen de la conversacion anterior: " + string.Join(" | ", resumenPartes);
            }

            return null;
        }

        private async Task<string> LlamarGeminiAsync(ContextoUsuarioIA ctx, List<MensajeIA> historial, string? resumen = null)
        {
            var systemPrompt = ConstruirSystemPrompt(ctx);

            var contents = historial.Select(m => new GeminiContent
            {
                Role = m.Rol == "user" ? "user" : "model",
                Parts = new List<GeminiPart> { new() { Text = m.Contenido } }
            }).ToList();

            if (!string.IsNullOrEmpty(resumen))
            {
                contents.Insert(0, new GeminiContent
                {
                    Role = "user",
                    Parts = new List<GeminiPart> { new() { Text = "[Contexto previo] " + resumen } }
                });
            }

            var request = new GeminiRequest
            {
                SystemInstruction = new GeminiContent
                {
                    Parts = new List<GeminiPart> { new() { Text = systemPrompt } }
                },
                Contents = contents,
                GenerationConfig = new GeminiConfig { Temperature = 0.75, MaxOutputTokens = 900 }
            };

            var url = $"https://generativelanguage.googleapis.com/v1beta/models/{_modelo}:generateContent";

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
                    reqMsg.Headers.Add("x-goog-api-key", _apiKey);

                    var httpResp = await _httpClient.SendAsync(reqMsg, cts.Token);
                    if (!httpResp.IsSuccessStatusCode)
                    {
                        if (intento < maxReintentos && (int)httpResp.StatusCode >= 500)
                        {
                            await Task.Delay(TimeSpan.FromSeconds(1 << intento));
                            continue;
                        }
                        return "Lo siento, no pude conectarme a la IA en este momento. Intentelo de nuevo mas tarde.";
                    }

                    var geminiResp = await httpResp.Content.ReadFromJsonAsync<GeminiResponse>(cancellationToken: cts.Token);

                    if (geminiResp?.PromptFeedback?.BlockReason != null)
                    {
                        _logger.LogWarning("Gemini bloqueo la respuesta: {BlockReason}", geminiResp.PromptFeedback.BlockReason);
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
                        ? "No recibi respuesta de la IA. Intenta reformular tu pregunta."
                        : texto;
                }
                catch (OperationCanceledException)
                {
                    if (intento < maxReintentos) continue;
                    return "La conexion con EDY tardo demasiado. Intentelo de nuevo.";
                }
                catch (HttpRequestException)
                {
                    if (intento < maxReintentos)
                    {
                        await Task.Delay(TimeSpan.FromSeconds(1 << intento));
                        continue;
                    }
                    return "Hubo un error al comunicarme con EDY. Verifica tu conexion e intentelo de nuevo.";
                }
            }

            return "Hubo un error al comunicarme con EDY. Verifica tu conexion e intentelo de nuevo.";
        }

        private async Task<ContextoUsuarioIA> ConstruirContextoAsync(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var animosRecientes = await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.Fecha)
                .Take(7)
                .ToListAsync();

            var usuario = await _contexto.Usuarios
                .Include(u => u.Progreso).ThenInclude(p => p.NivelActual)
                .Include(u => u.Habitos.Where(h => h.EstaActivo)).ThenInclude(h => h.Categoria)
                .Include(u => u.Misiones.Where(m => m.Estado != "Completada"))
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
                              && m.Estado == "Completada"
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

        private static string ConstruirSystemPrompt(ContextoUsuarioIA ctx)
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
                Eres EDY, el asistente de inteligencia artificial de EPYCUS - una plataforma academica
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
                - Si el usuario pregunta quien eres o como te llamas, dices que eres EDY, el asistente de EPYCUS.

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
