using System.Net.Http.Json;
using System.Text.Json.Serialization;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.Extensions.Logging;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ProveedorDeepSeek : IProveedorDeepSeek
    {
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;
        private readonly string _modelo;
        private readonly ILogger<ProveedorDeepSeek> _logger;

        public ProveedorDeepSeek(IHttpClientFactory httpClientFactory, IConfiguration config, ILogger<ProveedorDeepSeek> logger)
        {
            _httpClient = httpClientFactory.CreateClient("DeepSeek");
            _apiKey = config["DeepSeek:ApiKey"] ?? "";
            _modelo = config["DeepSeek:Modelo"] ?? "deepseek-v4-flash";
            _logger = logger;
        }

        public async Task<string> LlamarAsync(ContextoUsuarioIA ctx, List<MensajeIA> historial, string? resumen = null)
        {
            var systemPrompt = ConstructorContextoIA.ConstruirSystemPrompt(ctx);

            var messages = new List<DeepSeekMessage>
            {
                new() { Role = "system", Content = systemPrompt }
            };

            if (!string.IsNullOrEmpty(resumen))
            {
                messages.Add(new DeepSeekMessage
                {
                    Role = "user",
                    Content = "[Contexto previo] " + resumen
                });
            }

            foreach (var m in historial)
            {
                messages.Add(new DeepSeekMessage
                {
                    Role = m.Rol == "user" ? "user" : "assistant",
                    Content = m.Contenido
                });
            }

            var request = new DeepSeekRequest
            {
                Model = _modelo,
                Messages = messages,
                Temperature = 0.75,
                MaxTokens = 900,
                Stream = false
            };

            var url = "https://api.deepseek.com/chat/completions";

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
                    reqMsg.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", _apiKey);

                    var httpResp = await _httpClient.SendAsync(reqMsg, cts.Token);
                    if (!httpResp.IsSuccessStatusCode)
                    {
                        var errorBody = await httpResp.Content.ReadAsStringAsync(cts.Token);
                        _logger.LogWarning("DeepSeek respondio {StatusCode}: {Error}", httpResp.StatusCode, errorBody);
                        if (intento < maxReintentos && (int)httpResp.StatusCode >= 500)
                        {
                            await Task.Delay(TimeSpan.FromSeconds(1 << intento));
                            continue;
                        }
                        if ((int)httpResp.StatusCode == 429)
                            return "La IA esta saturada en este momento. Espera un momento y vuelve a intentarlo.";
                        return "Lo siento, no pude conectarme a la IA en este momento. Intentelo de nuevo mas tarde.";
                    }

                    var deepSeekResp = await httpResp.Content.ReadFromJsonAsync<DeepSeekResponse>(cancellationToken: cts.Token);

                    var choice = deepSeekResp?.Choices?.FirstOrDefault();
                    if (choice?.FinishReason == "length")
                    {
                        _logger.LogWarning("DeepSeek respuesta truncada por longitud maxima");
                    }

                    var texto = choice?.Message?.Content;

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

    internal sealed class DeepSeekRequest
    {
        [JsonPropertyName("model")]
        public string Model { get; set; } = "deepseek-v4-flash";

        [JsonPropertyName("messages")]
        public List<DeepSeekMessage> Messages { get; set; } = new();

        [JsonPropertyName("temperature")]
        public double Temperature { get; set; } = 0.75;

        [JsonPropertyName("max_tokens")]
        public int MaxTokens { get; set; } = 900;

        [JsonPropertyName("stream")]
        public bool Stream { get; set; } = false;
    }

    internal sealed class DeepSeekMessage
    {
        [JsonPropertyName("role")]
        public string Role { get; set; } = string.Empty;

        [JsonPropertyName("content")]
        public string Content { get; set; } = string.Empty;
    }

    internal sealed class DeepSeekResponse
    {
        [JsonPropertyName("choices")]
        public List<DeepSeekChoice>? Choices { get; set; }
    }

    internal sealed class DeepSeekChoice
    {
        [JsonPropertyName("message")]
        public DeepSeekMessage? Message { get; set; }

        [JsonPropertyName("finish_reason")]
        public string? FinishReason { get; set; }
    }
}
