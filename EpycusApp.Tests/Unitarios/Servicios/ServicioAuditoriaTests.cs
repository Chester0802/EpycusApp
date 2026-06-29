using EpycusApp.Datos;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Servicios;

[Trait("Categoria", "Unitario")]
public class ServicioAuditoriaTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioAuditoria _servicio;

    public ServicioAuditoriaTests()
    {
        _contexto = DbContextFactory.CrearContexto("AuditoriaTest");
        _servicio = new ServicioAuditoria(_contexto);
    }

    [Fact]
    public async Task RegistrarAsync_CreaLogConAccionYDetalle()
    {
        await _servicio.RegistrarAsync("Login", "Detalle prueba", 5, "127.0.0.1");

        var log = await _contexto.Logs.SingleAsync();
        log.Accion.Should().Be("Login");
        log.Detalle.Should().Be("Detalle prueba");
        log.UsuarioId.Should().Be(5);
        log.DireccionIp.Should().Be("127.0.0.1");
    }

    [Fact]
    public async Task RegistrarAsync_SinUsuarioNiIp_PermiteNulos()
    {
        await _servicio.RegistrarAsync("EventoSistema", null, null);

        var log = await _contexto.Logs.SingleAsync();
        log.UsuarioId.Should().BeNull();
        log.DireccionIp.Should().BeNull();
        log.Detalle.Should().BeNull();
    }

    [Fact]
    public async Task RegistrarAsync_AsignaFechaRegistroUtc()
    {
        var antes = DateTime.UtcNow.AddSeconds(-1);

        await _servicio.RegistrarAsync("Accion", null, 1);

        var log = await _contexto.Logs.SingleAsync();
        log.FechaRegistro.Should().BeOnOrAfter(antes);
        log.FechaRegistro.Should().BeOnOrBefore(DateTime.UtcNow.AddSeconds(1));
    }
}
