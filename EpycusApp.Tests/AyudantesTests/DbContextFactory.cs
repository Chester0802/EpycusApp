using EpycusApp.Datos;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Diagnostics;
using Microsoft.EntityFrameworkCore.InMemory.Diagnostics.Internal;

namespace EpycusApp.Tests.AyudantesTests;

public static class DbContextFactory
{
    public static ContextoAplicacion CrearContexto(string nombreDb = "TestDb")
    {
        var opciones = new DbContextOptionsBuilder<ContextoAplicacion>()
            .UseInMemoryDatabase(databaseName: $"{nombreDb}_{Guid.NewGuid()}")
            // El proveedor InMemory no soporta transacciones reales (no hay atomicidad que
            // probar), pero por defecto convierte ese aviso en excepcion. Servicios como
            // ServicioIA/ServicioAutenticacion usan BeginTransactionAsync porque el proveedor
            // real (MariaDB) si las soporta -- sin esto, ningun test podria ejercitar esos
            // metodos mas alla de las validaciones que lanzan antes de abrir la transaccion.
            .ConfigureWarnings(w => w.Ignore(InMemoryEventId.TransactionIgnoredWarning))
            .Options;

        return new ContextoAplicacion(opciones);
    }
}
