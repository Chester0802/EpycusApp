using System.Diagnostics;

namespace EpycusApp.Middleware
{
    public class TelemetriaMiddleware
    {
        private readonly RequestDelegate _next;
        private readonly ILogger<TelemetriaMiddleware> _logger;

        public TelemetriaMiddleware(RequestDelegate next, ILogger<TelemetriaMiddleware> logger)
        {
            _next = next;
            _logger = logger;
        }

        public async Task InvokeAsync(HttpContext context)
        {
            var sw = Stopwatch.StartNew();
            await _next(context);
            sw.Stop();

            if (context.Response.StatusCode >= 500)
            {
                _logger.LogWarning("Request {Method} {Path} responded {Status} en {ElapsedMs}ms",
                    context.Request.Method, context.Request.Path, context.Response.StatusCode, sw.ElapsedMilliseconds);
            }
            else if (sw.ElapsedMilliseconds > 1000)
            {
                _logger.LogInformation("Request lento: {Method} {Path} ({Status}) — {ElapsedMs}ms",
                    context.Request.Method, context.Request.Path, context.Response.StatusCode, sw.ElapsedMilliseconds);
            }
        }
    }
}
