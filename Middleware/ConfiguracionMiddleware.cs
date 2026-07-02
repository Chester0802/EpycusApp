using EpycusApp.Hubs;
using EpycusApp.Middleware;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.HttpOverrides;

namespace Microsoft.Extensions.DependencyInjection
{
    public static class ConfiguracionMiddlewareExtensions
    {
        /// <summary>
        /// Decodifica (sin validar firma) el claim "exp" de un JWT para decidir si conviene
        /// intentar una renovación silenciosa. La validación real de seguridad la sigue
        /// haciendo JwtBearer más adelante en el pipeline con el token ya renovado.
        /// </summary>
        private static bool JwtEstaPorExpirar(string jwt)
        {
            try
            {
                var partes = jwt.Split('.');
                if (partes.Length < 2) return true;
                var payload = partes[1].Replace('-', '+').Replace('_', '/');
                switch (payload.Length % 4)
                {
                    case 2: payload += "=="; break;
                    case 3: payload += "="; break;
                }
                var bytes = Convert.FromBase64String(payload);
                using var doc = System.Text.Json.JsonDocument.Parse(bytes);
                if (doc.RootElement.TryGetProperty("exp", out var expElement) && expElement.TryGetInt64(out var expUnix))
                {
                    var expira = DateTimeOffset.FromUnixTimeSeconds(expUnix);
                    // Margen de 30s para no quedarse corto por el resto del pipeline.
                    return expira <= DateTimeOffset.UtcNow.AddSeconds(30);
                }
                return true;
            }
            catch
            {
                return true;
            }
        }

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

            app.UseResponseCompression();

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
                    context.Response.Headers["Content-Security-Policy"] = "default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https://ui-avatars.com; font-src 'self' data: https://cdnjs.cloudflare.com; connect-src 'self' https://cdn.jsdelivr.net; report-uri /csp-report;";
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

            // Renovación silenciosa de la sesión web: la cookie "jwt_token" vive poco
            // (Jwt:ExpiracionMinutos, 60 por defecto), y hasta ahora nada usaba la cookie
            // "refresh_token" (de vida mas larga) para renovarla en el flujo web -> el
            // usuario quedaba deslogueado sin aviso al volver tras un rato sin usar la app,
            // y de forma inconsistente entre paginas segun cual llegara a tocar el servidor
            // (una pagina servida desde cache del Service Worker parecia "seguir logueado"
            // hasta navegar a una que si pegara contra el backend). Si el jwt_token falta o
            // esta por expirar y hay un refresh_token valido, se renueva antes de que
            // JwtBearer evalúe la petición.
            app.Use(async (context, next) =>
            {
                var path = context.Request.Path;
                if (context.Request.Method == HttpMethods.Get &&
                    !path.StartsWithSegments("/api") &&
                    !path.StartsWithSegments("/admin") &&
                    !path.StartsWithSegments("/Autenticacion"))
                {
                    var jwtCookie = context.Request.Cookies["jwt_token"];
                    var refreshCookie = context.Request.Cookies["refresh_token"];

                    if ((string.IsNullOrEmpty(jwtCookie) || JwtEstaPorExpirar(jwtCookie))
                        && !string.IsNullOrEmpty(refreshCookie))
                    {
                        var servicioAuth = context.RequestServices.GetRequiredService<IServicioAutenticacion>();
                        var (exito, _, nuevoToken, nuevoRefresh) = await servicioAuth.RenovarToken(refreshCookie);
                        if (exito && !string.IsNullOrEmpty(nuevoToken))
                        {
                            var cookieOptions = new CookieOptions
                            {
                                HttpOnly = true,
                                Secure = true,
                                SameSite = SameSiteMode.Lax,
                                Expires = DateTimeOffset.UtcNow.AddDays(7)
                            };
                            context.Response.Cookies.Append("jwt_token", nuevoToken, cookieOptions);
                            if (!string.IsNullOrEmpty(nuevoRefresh))
                            {
                                context.Response.Cookies.Append("refresh_token", nuevoRefresh, cookieOptions);
                            }
                            context.Response.Redirect(context.Request.Path + context.Request.QueryString);
                            return;
                        }
                    }
                }
                await next();
            });

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
