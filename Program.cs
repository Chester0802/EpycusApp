using EpycusApp.Datos;
using EpycusApp.Datos.Semilla;
using EpycusApp.Middleware;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

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

        builder.ConfigurarBaseDeDatos();
        builder.ConfigurarAutenticacion();

        builder.Services.Configure<ApiBehaviorOptions>(options =>
        {
            options.SuppressModelStateInvalidFilter = true;
        });

        builder.Services.AddControllersWithViews(options =>
        {
            options.Filters.Add<CargarPersonajeFilter>();
        }).AddApplicationPart(typeof(Program).Assembly);

        builder.ConfigurarRateLimiting();
        builder.ConfigurarServiciosAplicacion();

        var app = builder.Build();
        app.ConfigurarMiddleware();

        return app;
    }
}
