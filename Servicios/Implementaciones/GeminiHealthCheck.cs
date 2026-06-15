using Microsoft.Extensions.Diagnostics.HealthChecks;

namespace EpycusApp.Servicios.Implementaciones
{
    public class GeminiHealthCheck : IHealthCheck
    {
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;

        public GeminiHealthCheck(IHttpClientFactory httpClientFactory, IConfiguration config)
        {
            _httpClient = httpClientFactory.CreateClient();
            _apiKey = config["Gemini:ApiKey"] ?? "";
        }

        public async Task<HealthCheckResult> CheckHealthAsync(HealthCheckContext context, CancellationToken cancellationToken = default)
        {
            try
            {
                var modelo = "gemini-2.5-flash-lite";
                var url = $"https://generativelanguage.googleapis.com/v1beta/models/{modelo}?key={_apiKey}";
                using var cts = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
                cts.CancelAfter(TimeSpan.FromSeconds(5));

                var resp = await _httpClient.GetAsync(url, cts.Token);
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
