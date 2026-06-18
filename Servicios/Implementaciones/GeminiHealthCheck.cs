using Microsoft.Extensions.Diagnostics.HealthChecks;

namespace EpycusApp.Servicios.Implementaciones
{
    public class GeminiHealthCheck : IHealthCheck
    {
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;
        private readonly string _modelo;

        public GeminiHealthCheck(IHttpClientFactory httpClientFactory, IConfiguration config)
        {
            _httpClient = httpClientFactory.CreateClient();
            _apiKey = config["Gemini:ApiKey"] ?? "";
            _modelo = config["Gemini:Modelo"] ?? "gemini-2.5-flash-lite";
        }

        public async Task<HealthCheckResult> CheckHealthAsync(HealthCheckContext context, CancellationToken cancellationToken = default)
        {
            try
            {
                var modelo = _modelo;
                var url = $"https://generativelanguage.googleapis.com/v1beta/models/{modelo}";
                using var cts = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
                cts.CancelAfter(TimeSpan.FromSeconds(5));

                var reqMsg = new HttpRequestMessage(HttpMethod.Get, url);
                reqMsg.Headers.Add("x-goog-api-key", _apiKey);
                var resp = await _httpClient.SendAsync(reqMsg, cts.Token);
                return resp.IsSuccessStatusCode
                    ? HealthCheckResult.Healthy("Gemini API disponible")
                    : HealthCheckResult.Degraded($"Gemini API respondió {resp.StatusCode}");
            }
            catch (Exception ex)
            {
                return HealthCheckResult.Unhealthy("Gemini API no accesible", ex);
            }
        }
    }
}
