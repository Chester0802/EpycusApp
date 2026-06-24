using EpycusApp.Hubs;
using EpycusApp.Middleware;
using Microsoft.AspNetCore.HttpOverrides;

namespace Microsoft.Extensions.DependencyInjection
{
    public static class ConfiguracionMiddlewareExtensions
    {
        public static WebApplication ConfigurarMiddleware(this WebApplication app)
        {
            app.UseStatusCodePagesWithReExecute("/Home/Error", "?statusCode={0}");

            if (!app.Environment.IsDevelopment())
            {
                app.UseExceptionHandler("/Home/Error");
                app.UseHsts();
            }
            else
            {
                app.UseSwagger();
                app.UseSwaggerUI();
            }

            app.UseForwardedHeaders(new ForwardedHeadersOptions
            {
                ForwardedHeaders = ForwardedHeaders.XForwardedFor | ForwardedHeaders.XForwardedProto
            });

            if (!app.Configuration.GetValue<bool>("DisableHttpsRedirect"))
                app.UseHttpsRedirection();

            app.UseStaticFiles();

            app.Use(async (context, next) =>
            {
                context.Response.Headers["X-Content-Type-Options"] = "nosniff";
                context.Response.Headers["X-Frame-Options"] = "SAMEORIGIN";
                context.Response.Headers["X-XSS-Protection"] = "1; mode=block";
                context.Response.Headers["Referrer-Policy"] = "strict-origin-when-cross-origin";
                context.Response.Headers["Permissions-Policy"] = "camera=(), microphone=(), geolocation=()";
                if (!app.Environment.IsDevelopment())
                {
                    context.Response.Headers["Content-Security-Policy"] = "default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https://ui-avatars.com; font-src 'self' data: https://cdnjs.cloudflare.com; connect-src 'self'";
                }
                if (context.Response.ContentType == "text/html" || context.Response.ContentType?.StartsWith("text/html") == true)
                {
                    context.Response.Headers["Content-Type"] = "text/html; charset=utf-8";
                }
                await next();
            });

            app.UseRouting();

            var origenesPermitidos = app.Configuration.GetSection("Cors:OrigenesPermitidos").Get<string[]>();
            if (origenesPermitidos is { Length: > 0 })
            {
                app.UseCors("ApiPolicy");
            }

            app.UseRateLimiter();
            app.UseAuthentication();
            app.UseAuthorization();
            app.UseMiddleware<TelemetriaMiddleware>();

            app.MapHub<NotificacionesHub>("/hub/notificaciones");

            app.MapDefaultControllerRoute();

            app.MapHealthChecks("/health", new Microsoft.AspNetCore.Diagnostics.HealthChecks.HealthCheckOptions
            {
                ResponseWriter = async (context, report) =>
                {
                    context.Response.ContentType = "application/json";
                    var json = System.Text.Json.JsonSerializer.Serialize(new
                    {
                        status = report.Status.ToString(),
                        duration = report.TotalDuration,
                        checks = report.Entries.Select(e => new
                        {
                            name = e.Key,
                            status = e.Value.Status.ToString(),
                            description = e.Value.Description,
                            data = e.Value.Data?.ToDictionary(kv => kv.Key, kv => kv.Value)
                        })
                    });
                    await context.Response.WriteAsync(json);
                }
            });

            return app;
        }
    }
}
