using EpycusApp.Datos;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Tests.AyudantesTests;

public static class DbContextFactory
{
    public static ContextoAplicacion CrearContexto(string nombreDb = "TestDb")
    {
        var opciones = new DbContextOptionsBuilder<ContextoAplicacion>()
            .UseInMemoryDatabase(databaseName: $"{nombreDb}_{Guid.NewGuid()}")
            .Options;

        return new ContextoAplicacion(opciones);
    }
}
