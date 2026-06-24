using Microsoft.Extensions.Diagnostics.HealthChecks;

namespace EpycusApp.Servicios.Implementaciones
{
    public class MvcHealthCheck : IHealthCheck
    {
        private readonly HttpClient _httpClient;

        public MvcHealthCheck(HttpClient httpClient)
        {
            _httpClient = httpClient;
        }

        public async Task<HealthCheckResult> CheckHealthAsync(HealthCheckContext context, CancellationToken cancellationToken = default)
        {
            try
            {
                using var cts = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
                cts.CancelAfter(TimeSpan.FromSeconds(5));

                var resp = await _httpClient.GetAsync("/Home/Index", cts.Token);
                var content = await resp.Content.ReadAsStringAsync(cts.Token);

                if (!resp.IsSuccessStatusCode)
                    return HealthCheckResult.Unhealthy($"MVC respondio {resp.StatusCode}");

                if (!content.Contains("<!DOCTYPE html>"))
                    return HealthCheckResult.Degraded("MVC respondio pero no es HTML valido");

                return HealthCheckResult.Healthy("Pipeline MVC funciona correctamente");
            }
            catch (Exception ex)
            {
                return HealthCheckResult.Unhealthy("Pipeline MVC no responde", ex);
            }
        }
    }
}
