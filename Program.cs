using System.Text;
using EpycusApp.Datos;
using EpycusApp.Datos.Semilla;
using EpycusApp.Middleware;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.AspNetCore.RateLimiting;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using System.Threading.RateLimiting;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddDbContext<ContextoAplicacion>(options =>
{
    var cadenaConexion = builder.Configuration.GetConnectionString("ConexionPrincipal");
    var versionServidor = builder.Configuration["MySql:ServerVersion"];
    var serverVersion = string.IsNullOrWhiteSpace(versionServidor)
        ? ServerVersion.AutoDetect(cadenaConexion)
        : ServerVersion.Parse(versionServidor);

    options.UseMySql(cadenaConexion, serverVersion);
});

// ValidaciÃ³n temprana de configuraciÃ³n crÃ­tica
var jwtClave = builder.Configuration["Jwt:Clave"];
if (string.IsNullOrEmpty(jwtClave) || jwtClave.Length < 32)
{
    throw new InvalidOperationException("La clave JWT (Jwt:Clave) no estÃ¡ configurada o es muy corta. Define una clave segura de al menos 32 caracteres en variables de entorno o en secretos.");
}

builder.Services.AddAuthentication(JwtBearerDefaults.AuthenticationScheme)
    .AddJwtBearer(options =>
    {
        options.TokenValidationParameters = new TokenValidationParameters
        {
            ValidateIssuer = true,
            ValidateAudience = true,
            ValidateLifetime = true,
            ValidateIssuerSigningKey = true,
            ValidIssuer = builder.Configuration["Jwt:Emisor"],
            ValidAudience = builder.Configuration["Jwt:Audiencia"],
            IssuerSigningKey = new SymmetricSecurityKey(
                Encoding.UTF8.GetBytes(builder.Configuration["Jwt:Clave"]!))
        };
        options.Events = new JwtBearerEvents
        {
            OnMessageReceived = context =>
            {
                var esAdmin = context.Request.Path.StartsWithSegments("/admin");
                var token = esAdmin
                    ? context.Request.Cookies["admin_jwt_token"]
                    : context.Request.Cookies["jwt_token"];

                if (!string.IsNullOrEmpty(token))
                {
                    context.Token = token;
                }

                return Task.CompletedTask;
            },
            OnChallenge = context =>
            {
                if (!context.Request.Path.StartsWithSegments("/api"))
                {
                    context.HandleResponse();
                    context.Response.Redirect(context.Request.Path.StartsWithSegments("/admin")
                        ? "/admin/login"
                        : "/Autenticacion/Login");
                }

                return Task.CompletedTask;
            }
        };
    })
    .AddGoogle(options =>
    {
        options.ClientId = builder.Configuration["Google:ClientId"]!;
        options.ClientSecret = builder.Configuration["Google:ClientSecret"]!;
    });

builder.Services.AddAuthorization();

builder.Services.AddControllersWithViews(options =>
{
    options.Filters.Add<CargarPersonajeFilter>();
});

// Rate Limiting â€” proteger contra abuso de API
builder.Services.AddRateLimiter(options =>
{
    options.RejectionStatusCode = StatusCodes.Status429TooManyRequests;

    options.AddFixedWindowLimiter("Api", opt =>
    {
        opt.PermitLimit = 100;
        opt.Window = TimeSpan.FromMinutes(1);
        opt.QueueProcessingOrder = QueueProcessingOrder.OldestFirst;
        opt.QueueLimit = 5;
    });

    options.AddFixedWindowLimiter("Gemini", opt =>
    {
        opt.PermitLimit = 20;
        opt.Window = TimeSpan.FromMinutes(1);
        opt.QueueLimit = 0;
    });

    // PolÃ­tica global: cualquier request no cubierto por polÃ­ticas especÃ­ficas
    options.GlobalLimiter = PartitionedRateLimiter.Create<HttpContext, string>(context =>
        RateLimitPartition.GetFixedWindowLimiter(
            partitionKey: context.User.Identity?.IsAuthenticated == true
                ? context.User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)?.Value ?? "anon"
                : "anon",
            factory: _ => new FixedWindowRateLimiterOptions
            {
                PermitLimit = 200,
                Window = TimeSpan.FromMinutes(1),
                QueueLimit = 0
            }));
});

builder.Services.AddAntiforgery(options =>
{
    options.HeaderName = "X-CSRF-TOKEN";
    options.Cookie.HttpOnly = true;
    options.Cookie.SecurePolicy = CookieSecurePolicy.Always;
    options.Cookie.SameSite = SameSiteMode.Strict;
});

// CORS â€” permitir solo orÃ­genes conocidos (ajustar en producciÃ³n)
var origenesPermitidos = builder.Configuration.GetSection("Cors:OrigenesPermitidos").Get<string[]>();
if (origenesPermitidos is { Length: > 0 })
{
    builder.Services.AddCors(options =>
    {
        options.AddPolicy("ApiPolicy", policy =>
        {
            policy.WithOrigins(origenesPermitidos)
                  .AllowAnyHeader()
                  .AllowAnyMethod()
                  .AllowCredentials();
        });
    });
}

builder.Services.AddHttpClient("Gemini", client =>
{
    client.Timeout = TimeSpan.FromSeconds(30);
});

builder.Services.AddHttpClient();

// Health Checks
var cadenaConexion = builder.Configuration.GetConnectionString("ConexionPrincipal")!;
builder.Services.AddHealthChecks()
    .AddMySql(cadenaConexion, name: "Base de Datos", tags: ["db"])
    .AddCheck<GeminiHealthCheck>("Gemini API", tags: ["api"])
    .AddCheck<DiskHealthCheck>("Disco", tags: ["system"]);

builder.Services.AddScoped<IServicioAutenticacion, ServicioAutenticacion>();
builder.Services.AddScoped<IServicioGamificacion, ServicioGamificacion>();
builder.Services.AddScoped<IServicioHabitos, ServicioHabitos>();
builder.Services.AddScoped<IServicioPomodoro, ServicioPomodoro>();
builder.Services.AddScoped<IServicioMisiones, ServicioMisiones>();
builder.Services.AddScoped<IServicioProgreso, ServicioProgreso>();
builder.Services.AddScoped<IServicioPerfil, ServicioPerfil>();
builder.Services.AddScoped<IServicioCorreo, ServicioCorreo>();
builder.Services.AddScoped<IServicioAdmin, ServicioAdmin>();
builder.Services.AddScoped<IServicioBienestar, ServicioBienestar>();
builder.Services.AddScoped<IServicioIA, ServicioIA>();

var app = builder.Build();

if (!app.Environment.IsDevelopment())
{
    app.UseExceptionHandler("/Home/Error");
    app.UseHsts();
}

app.UseHttpsRedirection();
app.UseStaticFiles();

app.UseRouting();

app.UseRateLimiter();

// Seguridad: headers HTTP
app.Use(async (context, next) =>
{
    context.Response.Headers["X-Content-Type-Options"] = "nosniff";
    context.Response.Headers["X-Frame-Options"] = "SAMEORIGIN";
    context.Response.Headers["X-XSS-Protection"] = "1; mode=block";
    context.Response.Headers["Referrer-Policy"] = "strict-origin-when-cross-origin";
    context.Response.Headers["Permissions-Policy"] = "camera=(), microphone=(), geolocation=()";
    if (!app.Environment.IsDevelopment())
    {
        context.Response.Headers["Content-Security-Policy"] = "default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https://ui-avatars.com; font-src 'self' data:; connect-src 'self'";
    }
    await next();
});

if (origenesPermitidos is { Length: > 0 })
{
    app.UseCors("ApiPolicy");
}

app.UseAuthentication();
app.UseAuthorization();

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

using (var scope = app.Services.CreateScope())
{
    var contexto = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();

    // Aplicar migraciones pendientes
    await contexto.Database.MigrateAsync();

    // Datos semilla base (roles, niveles, categorÃ­as) â€” requeridos en todos los entornos
    await DatosSemilla.InicializarAsync(contexto);
}

app.Run();
