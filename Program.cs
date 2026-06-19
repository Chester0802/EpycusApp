using System.Text;
using Microsoft.AspNetCore.HttpOverrides;
using EpycusApp.Datos;
using EpycusApp.Datos.Semilla;
using EpycusApp.Middleware;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.AspNetCore.RateLimiting;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using System.Threading.RateLimiting;

var app = Program.CreateApp(args);

using (var scope = app.Services.CreateScope())
{
    var contexto = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();
    var proveedorDb = app.Configuration["Database:Provider"];

    if (proveedorDb == "InMemory")
        await contexto.Database.EnsureCreatedAsync();
    else
        await contexto.Database.MigrateAsync();

    await DatosSemilla.InicializarAsync(contexto);
}

app.Run();

public partial class Program
{
    public static WebApplication CreateApp(string[] args)
    {
        var builder = WebApplication.CreateBuilder(args);

        var proveedorDb = builder.Configuration["Database:Provider"];
        if (proveedorDb == "InMemory")
        {
            builder.Services.AddDbContext<ContextoAplicacion>(options =>
                options.UseInMemoryDatabase("EpycusDb"));
        }
        else
        {
            builder.Services.AddDbContext<ContextoAplicacion>(options =>
            {
                var cadenaConexion = builder.Configuration.GetConnectionString("ConexionPrincipal");
                var versionServidor = builder.Configuration["MySql:ServerVersion"];
                var serverVersion = string.IsNullOrWhiteSpace(versionServidor)
                    ? ServerVersion.AutoDetect(cadenaConexion)
                    : ServerVersion.Parse(versionServidor);

                options.UseMySql(cadenaConexion, serverVersion);
            });
        }

        var jwtClave = builder.Configuration["Jwt:Clave"];
        if (string.IsNullOrEmpty(jwtClave) || jwtClave.Length < 32)
        {
            throw new InvalidOperationException("La clave JWT (Jwt:Clave) no está configurada o es muy corta. Define una clave segura de al menos 32 caracteres en variables de entorno o en secretos.");
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
            .AddCookie("ExternalCookie", options =>
            {
                options.Cookie.Name = ".AspNetCore.ExternalAuth";
                options.Cookie.HttpOnly = true;
                options.Cookie.SameSite = SameSiteMode.Lax;
                options.Cookie.SecurePolicy = CookieSecurePolicy.Always;
                options.ExpireTimeSpan = TimeSpan.FromMinutes(5);
                options.SlidingExpiration = false;
            })
            .AddGoogle(options =>
            {
                options.ClientId = builder.Configuration["Google:ClientId"]!;
                options.ClientSecret = builder.Configuration["Google:ClientSecret"]!;
                options.SignInScheme = "ExternalCookie";
            });

        builder.Services.AddAuthorization();

        builder.Services.AddControllersWithViews(options =>
        {
            options.Filters.Add<CargarPersonajeFilter>();
        }).AddApplicationPart(typeof(Program).Assembly);

        var rateLimitConfig = builder.Configuration.GetSection("RateLimiting");
        var globalRl = rateLimitConfig.GetSection("Global");
        var apiRl = rateLimitConfig.GetSection("Api");
        var authRl = rateLimitConfig.GetSection("Auth");
        var mobileRl = rateLimitConfig.GetSection("Mobile");
        var geminiRl = rateLimitConfig.GetSection("Gemini");
        var deepseekRl = rateLimitConfig.GetSection("DeepSeek");

        builder.Services.AddRateLimiter(options =>
        {
            options.RejectionStatusCode = StatusCodes.Status429TooManyRequests;

            options.AddFixedWindowLimiter("Api", opt =>
            {
                opt.PermitLimit = int.Parse(apiRl["PermitLimit"] ?? "300");
                opt.Window = TimeSpan.FromMinutes(int.Parse(apiRl["WindowMinutes"] ?? "1"));
                opt.QueueProcessingOrder = QueueProcessingOrder.OldestFirst;
                opt.QueueLimit = int.Parse(apiRl["QueueLimit"] ?? "10");
            });

            options.AddFixedWindowLimiter("Auth", opt =>
            {
                opt.PermitLimit = int.Parse(authRl["PermitLimit"] ?? "20");
                opt.Window = TimeSpan.FromMinutes(int.Parse(authRl["WindowMinutes"] ?? "1"));
                opt.QueueLimit = 0;
            });

            options.AddFixedWindowLimiter("Mobile", opt =>
            {
                opt.PermitLimit = int.Parse(mobileRl["PermitLimit"] ?? "400");
                opt.Window = TimeSpan.FromMinutes(int.Parse(mobileRl["WindowMinutes"] ?? "1"));
                opt.QueueProcessingOrder = QueueProcessingOrder.OldestFirst;
                opt.QueueLimit = int.Parse(mobileRl["QueueLimit"] ?? "10");
            });

            options.AddFixedWindowLimiter("Gemini", opt =>
            {
                opt.PermitLimit = int.Parse(geminiRl["PermitLimit"] ?? "20");
                opt.Window = TimeSpan.FromMinutes(int.Parse(geminiRl["WindowMinutes"] ?? "1"));
                opt.QueueLimit = 0;
            });

            options.AddFixedWindowLimiter("DeepSeek", opt =>
            {
                opt.PermitLimit = int.Parse(deepseekRl["PermitLimit"] ?? "2500");
                opt.Window = TimeSpan.FromMinutes(int.Parse(deepseekRl["WindowMinutes"] ?? "1"));
                opt.QueueLimit = 0;
            });

            options.GlobalLimiter = PartitionedRateLimiter.Create<HttpContext, string>(context =>
                RateLimitPartition.GetFixedWindowLimiter(
                    partitionKey: context.User.Identity?.IsAuthenticated == true
                        ? context.User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)?.Value ?? "anon"
                        : "anon",
                    factory: _ => new FixedWindowRateLimiterOptions
                    {
                        PermitLimit = int.Parse(globalRl["PermitLimit"] ?? "600"),
                        Window = TimeSpan.FromMinutes(int.Parse(globalRl["WindowMinutes"] ?? "1")),
                        QueueLimit = 0
                    }));
        });

        builder.Services.AddAntiforgery(options =>
        {
            options.HeaderName = "X-CSRF-TOKEN";
            options.Cookie.HttpOnly = true;
            options.Cookie.SecurePolicy = CookieSecurePolicy.SameAsRequest;
            options.Cookie.SameSite = SameSiteMode.Strict;
        });

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

        builder.Services.AddHttpClient("DeepSeek", client =>
        {
            client.Timeout = TimeSpan.FromSeconds(30);
        });

        builder.Services.AddHttpClient();

        builder.Services.AddEndpointsApiExplorer();
        builder.Services.AddSwaggerGen(options =>
        {
            options.SwaggerDoc("v1", new() { Title = "EpycusApp API", Version = "v1" });
        });

        var cadenaConexion = builder.Configuration.GetConnectionString("ConexionPrincipal")!;
        var healthChecks = builder.Services.AddHealthChecks()
            .AddCheck<GeminiHealthCheck>("Gemini API", tags: ["api"])
            .AddCheck<DeepSeekHealthCheck>("DeepSeek API", tags: ["api"])
            .AddCheck<DiskHealthCheck>("Disco", tags: ["system"]);

        if (proveedorDb != "InMemory")
        {
            healthChecks.AddMySql(cadenaConexion, name: "Base de Datos", tags: ["db"]);
        }

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
        builder.Services.AddScoped<IServicioDiarioAnimo, ServicioDiarioAnimo>();
        builder.Services.AddScoped<IServicioIA, ServicioIA>();

        var app = builder.Build();

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

        if (origenesPermitidos is { Length: > 0 })
        {
            app.UseCors("ApiPolicy");
        }

        app.UseAuthentication();
        app.UseAuthorization();
        app.UseRateLimiter();
        app.UseMiddleware<TelemetriaMiddleware>();

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
