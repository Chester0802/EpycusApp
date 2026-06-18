using Microsoft.Extensions.Diagnostics.HealthChecks;

namespace EpycusApp.Servicios.Implementaciones
{
    public class DeepSeekHealthCheck : IHealthCheck
    {
        private readonly HttpClient _httpClient;
        private readonly string _apiKey;
        private readonly string _modelo;

        public DeepSeekHealthCheck(IHttpClientFactory httpClientFactory, IConfiguration config)
        {
            _httpClient = httpClientFactory.CreateClient("DeepSeek");
            _apiKey = config["DeepSeek:ApiKey"] ?? "";
            _modelo = config["DeepSeek:Modelo"] ?? "deepseek-v4-flash";
        }

        public async Task<HealthCheckResult> CheckHealthAsync(HealthCheckContext context, CancellationToken cancellationToken = default)
        {
            try
            {
                var url = "https://api.deepseek.com/models";
                using var cts = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
                cts.CancelAfter(TimeSpan.FromSeconds(5));

                var reqMsg = new HttpRequestMessage(HttpMethod.Get, url);
                reqMsg.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", _apiKey);
                var resp = await _httpClient.SendAsync(reqMsg, cts.Token);
                return resp.IsSuccessStatusCode
                    ? HealthCheckResult.Healthy("DeepSeek API disponible")
                    : HealthCheckResult.Degraded($"DeepSeek API respondio {resp.StatusCode}");
            }
            catch (Exception ex)
            {
                return HealthCheckResult.Unhealthy("DeepSeek API no accesible", ex);
            }
        }
    }
}
