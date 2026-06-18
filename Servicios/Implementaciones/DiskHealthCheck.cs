using Microsoft.Extensions.Diagnostics.HealthChecks;

namespace EpycusApp.Servicios.Implementaciones
{
    public class DiskHealthCheck : IHealthCheck
    {
        private readonly string _ruta;

        public DiskHealthCheck(IConfiguration config)
        {
            _ruta = config["HealthChecks:DiscoRuta"] ?? "/";
        }

        public Task<HealthCheckResult> CheckHealthAsync(HealthCheckContext context, CancellationToken cancellationToken = default)
        {
            try
            {
                var drive = new System.IO.DriveInfo(System.IO.Path.GetPathRoot(_ruta)!);
                var libreMB = drive.AvailableFreeSpace / (1024 * 1024);
                var libreGB = libreMB / 1024;

                var data = new Dictionary<string, object>
                {
                    { "EspacioLibreMB", libreMB },
                    { "EspacioTotalMB", drive.TotalSize / (1024 * 1024) }
                };

                if (libreGB < 1)
                    return Task.FromResult(HealthCheckResult.Unhealthy($"Disco casi lleno: {libreGB} GB libres", data: data));

                if (libreGB < 5)
                    return Task.FromResult(HealthCheckResult.Degraded($"Disco por debajo de 5 GB: {libreGB} GB libres", data: data));

                return Task.FromResult(HealthCheckResult.Healthy($"Disco OK: {libreGB} GB libres", data));
            }
            catch (Exception ex)
            {
                return Task.FromResult(HealthCheckResult.Unhealthy("No se pudo leer el disco", ex));
            }
        }
    }
}
