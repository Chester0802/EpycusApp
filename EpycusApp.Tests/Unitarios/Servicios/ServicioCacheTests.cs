using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Servicios;

[Trait("Categoria", "Unitario")]
public class ServicioCacheTests
{
    private readonly ServiceProvider _proveedor;
    private readonly IMemoryCache _cache;
    private readonly ServicioCache _servicio;
    private readonly string _dbName = $"CacheTest_{Guid.NewGuid()}";

    public ServicioCacheTests()
    {
        var servicios = new ServiceCollection();
        servicios.AddDbContext<ContextoAplicacion>(o => o.UseInMemoryDatabase(_dbName));
        servicios.AddMemoryCache();
        _proveedor = servicios.BuildServiceProvider();
        _cache = _proveedor.GetRequiredService<IMemoryCache>();
        var scopeFactory = _proveedor.GetRequiredService<IServiceScopeFactory>();
        var logger = new Mock<ILogger<ServicioCache>>();
        _servicio = new ServicioCache(_cache, scopeFactory, logger.Object);
    }

    private async Task SeedAsync()
    {
        using var scope = _proveedor.CreateScope();
        var ctx = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();
        ctx.Carreras.Add(new Carrera { Nombre = "Ingeniería", Area = "STEM", Codigo = "ING", EstaActiva = true });
        ctx.Carreras.Add(new Carrera { Nombre = "Inactiva", Area = "X", Codigo = "INA", EstaActiva = false });
        ctx.Categorias.Add(new Categoria { Nombre = "Estudio", Tipo = "Habito", EstaActiva = true });
        ctx.Niveles.Add(new Nivel { Numero = 2, XpRequerido = 100, Titulo = "Aprendiz" });
        ctx.Niveles.Add(new Nivel { Numero = 1, XpRequerido = 0, Titulo = "Novato" });
        ctx.FrasesMotivacionales.Add(new FraseMotivacional { Frase = "Ánimo", Autor = "A", EstaActiva = true });
        await ctx.SaveChangesAsync();
    }

    [Fact]
    public async Task ObtenerCarrerasAsync_RetornaSoloActivas()
    {
        await SeedAsync();
        var carreras = await _servicio.ObtenerCarrerasAsync();
        carreras.Should().ContainSingle();
        carreras[0].Nombre.Should().Be("Ingeniería");
    }

    [Fact]
    public async Task ObtenerNivelesAsync_RetornaOrdenadosPorNumero()
    {
        await SeedAsync();
        var niveles = await _servicio.ObtenerNivelesAsync();
        niveles.Should().HaveCount(2);
        niveles[0].Numero.Should().Be(1);
        niveles[1].Numero.Should().Be(2);
    }

    [Fact]
    public async Task ObtenerCategoriasAsync_RetornaActivas()
    {
        await SeedAsync();
        var categorias = await _servicio.ObtenerCategoriasAsync();
        categorias.Should().ContainSingle();
    }

    [Fact]
    public async Task ObtenerCarrerasAsync_SegundaLlamada_UsaCache()
    {
        await SeedAsync();
        await _servicio.ObtenerCarrerasAsync();
        _cache.TryGetValue("Carreras", out _).Should().BeTrue();

        var carreras = await _servicio.ObtenerCarrerasAsync();
        carreras.Should().ContainSingle();
    }

    [Fact]
    public async Task LimpiarCache_EliminaEntradas()
    {
        await SeedAsync();
        await _servicio.ObtenerCarrerasAsync();
        _cache.TryGetValue("Carreras", out _).Should().BeTrue();

        _servicio.LimpiarCache();

        _cache.TryGetValue("Carreras", out _).Should().BeFalse();
    }
}
