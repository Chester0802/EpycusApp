using System.Text;
using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Datos.Semilla;
using EPYCUS_WEB_v0._1.Servicios.Implementaciones;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddDbContext<ContextoAplicacion>(options =>
{
    var cadenaConexion = builder.Configuration.GetConnectionString("ConexionPrincipal");
    options.UseMySql(cadenaConexion, ServerVersion.AutoDetect(cadenaConexion));
});

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
builder.Services.AddControllersWithViews();

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

var app = builder.Build();

if (!app.Environment.IsDevelopment())
{
    app.UseExceptionHandler("/Home/Error");
    app.UseHsts();
}

app.UseHttpsRedirection();
app.UseStaticFiles();

app.UseRouting();

app.UseAuthentication();
app.UseAuthorization();

app.MapDefaultControllerRoute();

using (var scope = app.Services.CreateScope())
{
    var contexto = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();

    // Aplicar migraciones pendientes
    await contexto.Database.MigrateAsync();

    // Inicializar datos semilla
    await DatosSemilla.InicializarAsync(contexto);
}

app.Run();
