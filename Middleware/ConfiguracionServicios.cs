using System.Text;
using System.Threading.RateLimiting;
using EpycusApp.Ayudantes;
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
using Sentry;

namespace Microsoft.Extensions.DependencyInjection
{
    public static class ConfiguracionServiciosExtensions
    {
        public static WebApplicationBuilder ConfigurarBaseDeDatos(this WebApplicationBuilder builder)
        {
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

                    options.UseMySql(cadenaConexion, serverVersion, mysqlOptions =>
                    {
                        mysqlOptions.EnableRetryOnFailure(
                            maxRetryCount: 3,
                            maxRetryDelay: TimeSpan.FromSeconds(10),
                            errorNumbersToAdd: null);
                    });
                });
            }
            return builder;
        }

        public static WebApplicationBuilder ConfigurarAutenticacion(this WebApplicationBuilder builder)
        {
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
            return builder;
        }

        public static WebApplicationBuilder ConfigurarRateLimiting(this WebApplicationBuilder builder)
        {
            var rateLimitConfig = builder.Configuration.GetSection("RateLimiting");

            builder.Services.AddRateLimiter(options =>
            {
                options.RejectionStatusCode = StatusCodes.Status429TooManyRequests;

                options.AddFixedWindowLimiter("Api", opt =>
                {
                    opt.PermitLimit = int.Parse(rateLimitConfig["Api:PermitLimit"] ?? "300");
                    opt.Window = TimeSpan.FromMinutes(int.Parse(rateLimitConfig["Api:WindowMinutes"] ?? "1"));
                    opt.QueueProcessingOrder = QueueProcessingOrder.OldestFirst;
                    opt.QueueLimit = int.Parse(rateLimitConfig["Api:QueueLimit"] ?? "10");
                });

                options.AddFixedWindowLimiter("Auth", opt =>
                {
                    opt.PermitLimit = int.Parse(rateLimitConfig["Auth:PermitLimit"] ?? "20");
                    opt.Window = TimeSpan.FromMinutes(int.Parse(rateLimitConfig["Auth:WindowMinutes"] ?? "1"));
                    opt.QueueLimit = 0;
                });

                options.AddFixedWindowLimiter("Mobile", opt =>
                {
                    opt.PermitLimit = int.Parse(rateLimitConfig["Mobile:PermitLimit"] ?? "400");
                    opt.Window = TimeSpan.FromMinutes(int.Parse(rateLimitConfig["Mobile:WindowMinutes"] ?? "1"));
                    opt.QueueProcessingOrder = QueueProcessingOrder.OldestFirst;
                    opt.QueueLimit = int.Parse(rateLimitConfig["Mobile:QueueLimit"] ?? "10");
                });

                options.AddFixedWindowLimiter("Gemini", opt =>
                {
                    opt.PermitLimit = int.Parse(rateLimitConfig["Gemini:PermitLimit"] ?? "20");
                    opt.Window = TimeSpan.FromMinutes(int.Parse(rateLimitConfig["Gemini:WindowMinutes"] ?? "1"));
                    opt.QueueLimit = 0;
                });

                options.AddFixedWindowLimiter("DeepSeek", opt =>
                {
                    opt.PermitLimit = int.Parse(rateLimitConfig["DeepSeek:PermitLimit"] ?? "2500");
                    opt.Window = TimeSpan.FromMinutes(int.Parse(rateLimitConfig["DeepSeek:WindowMinutes"] ?? "1"));
                    opt.QueueLimit = 0;
                });

                options.AddFixedWindowLimiter("Pomodoro", opt =>
                {
                    opt.PermitLimit = 60;
                    opt.Window = TimeSpan.FromMinutes(1);
                    opt.QueueLimit = 5;
                });

                options.GlobalLimiter = PartitionedRateLimiter.Create<HttpContext, string>(context =>
                    RateLimitPartition.GetFixedWindowLimiter(
                        partitionKey: context.User.Identity?.IsAuthenticated == true
                            ? context.User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)?.Value ?? "anon"
                            : "anon",
                        factory: _ => new FixedWindowRateLimiterOptions
                        {
                            PermitLimit = int.Parse(rateLimitConfig["Global:PermitLimit"] ?? "600"),
                            Window = TimeSpan.FromMinutes(int.Parse(rateLimitConfig["Global:WindowMinutes"] ?? "1")),
                            QueueLimit = 0
                        }));
            });
            return builder;
        }

        public static WebApplicationBuilder ConfigurarServiciosAplicacion(this WebApplicationBuilder builder)
        {
            builder.Services.Configure<HostOptions>(options =>
            {
                options.ShutdownTimeout = TimeSpan.FromSeconds(30);
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

            builder.Services.AddHttpClient<VerificadorTurnstile>(client =>
            {
                client.Timeout = TimeSpan.FromSeconds(10);
            });
            builder.Services.Configure<TurnstileOptions>(builder.Configuration.GetSection(TurnstileOptions.Seccion));
            builder.Services.AddHttpClient();
            builder.Services.AddEndpointsApiExplorer();
            builder.Services.AddSwaggerGen(options =>
            {
                options.SwaggerDoc("v1", new() { Title = "EpycusApp API", Version = "v1" });
            });

            builder.Services.AddHttpClient<MvcHealthCheck>(client =>
            {
                client.BaseAddress = new Uri(builder.Configuration["App:UrlBase"] ?? "http://localhost:5000");
                client.Timeout = TimeSpan.FromSeconds(5);
            });

            var cadenaConexion = builder.Configuration.GetConnectionString("ConexionPrincipal")!;
            var proveedorDb = builder.Configuration["Database:Provider"];
            var healthChecks = builder.Services.AddHealthChecks()
                .AddCheck<GeminiHealthCheck>("Gemini API", tags: ["api"])
                .AddCheck<DeepSeekHealthCheck>("DeepSeek API", tags: ["api"])
                .AddCheck<DiskHealthCheck>("Disco", tags: ["system"])
                .AddCheck<MvcHealthCheck>("Pipeline MVC", tags: ["mvc"]);

            if (proveedorDb != "InMemory")
            {
                healthChecks.AddMySql(cadenaConexion, name: "Base de Datos", tags: ["db"]);
            }

            builder.Services.AddScoped<IServicioAuditoria, ServicioAuditoria>();
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
            builder.Services.AddScoped<ConstructorContextoIA>();
            builder.Services.AddScoped<IProveedorGemini, ProveedorGemini>();
            builder.Services.AddScoped<IProveedorDeepSeek, ProveedorDeepSeek>();
            builder.Services.AddScoped<IServicioIA, ServicioIA>();
            builder.Services.AddSignalR();
            builder.Services.AddMemoryCache();
            builder.Services.AddSingleton<IServicioCache, ServicioCache>();

            var sentryDsn = builder.Configuration["Sentry:Dsn"];
            if (!string.IsNullOrEmpty(sentryDsn))
            {
                builder.Services.AddSentry();
            }

            return builder;
        }
    }
}
