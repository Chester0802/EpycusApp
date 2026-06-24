using System.Net.Http.Json;
using System.Text.Json.Serialization;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.Extensions.Logging;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ProveedorGemini : IProveedorGemini
    {
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;
        private readonly string _modelo;
        private readonly ILogger<ProveedorGemini> _logger;

        public ProveedorGemini(IHttpClientFactory httpClientFactory, IConfiguration config, ILogger<ProveedorGemini> logger)
        {
            _httpClient = httpClientFactory.CreateClient("Gemini");
            _apiKey = config["Gemini:ApiKey"] ?? "";
            _modelo = config["Gemini:Modelo"] ?? "gemini-2.0-flash";
            _logger = logger;
        }

        public async Task<string> LlamarAsync(ContextoUsuarioIA ctx, List<MensajeIA> historial, string? resumen = null)
        {
            var systemPrompt = ConstructorContextoIA.ConstruirSystemPrompt(ctx);

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
                        var errorBody = await httpResp.Content.ReadAsStringAsync(cts.Token);
                        _logger.LogWarning("Gemini respondio {StatusCode}: {Error}", httpResp.StatusCode, errorBody);
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
                    return "La conexion con EDY AI tardo demasiado. Intentelo de nuevo.";
                }
                catch (HttpRequestException)
                {
                    if (intento < maxReintentos)
                    {
                        await Task.Delay(TimeSpan.FromSeconds(1 << intento));
                        continue;
                    }
                    return "Hubo un error al comunicarme con EDY AI. Verifica tu conexion e intentelo de nuevo.";
                }
            }

            return "Hubo un error al comunicarme con EDY AI. Verifica tu conexion e intentelo de nuevo.";
        }
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
