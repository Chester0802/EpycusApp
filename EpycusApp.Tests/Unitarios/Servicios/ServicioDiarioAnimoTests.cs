using EpycusApp.Datos;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Servicios;

[Trait("Categoria", "Unitario")]
public class ServicioDiarioAnimoTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioDiarioAnimo _servicio;
    private const int UsuarioId = 1;

    public ServicioDiarioAnimoTests()
    {
        _contexto = DbContextFactory.CrearContexto("DiarioAnimoTest");
        var logger = new Mock<ILogger<ServicioDiarioAnimo>>();
        _servicio = new ServicioDiarioAnimo(_contexto, logger.Object);
    }

    private static RegistrarEntradaDiarioViewModel CrearModel() => new()
    {
        EstadoAnimo = 4,
        NivelEnergia = 3,
        HorasSueno = 7.25m,
        NivelEstres = 2,
        ActividadFisica = true,
        DiarioTexto = "Buen día",
        RespuestaGuia = "Respuesta"
    };

    [Fact]
    public async Task ObtenerEntradaHoy_SinEntrada_RetornaNull()
    {
        var resultado = await _servicio.ObtenerEntradaHoy(UsuarioId);
        resultado.Should().BeNull();
    }

    [Fact]
    public async Task RegistrarEntrada_CreaNuevaEntradaConDatos()
    {
        var entrada = await _servicio.RegistrarEntrada(UsuarioId, CrearModel(), "Pregunta?");

        entrada.EstadoAnimo.Should().Be(4);
        entrada.PreguntaGuia.Should().Be("Pregunta?");
        entrada.UsuarioId.Should().Be(UsuarioId);
        (await _contexto.EntradasDiario.CountAsync()).Should().Be(1);
    }

    [Fact]
    public async Task RegistrarEntrada_RedondeaHorasSuenoAUnDecimal()
    {
        var model = CrearModel();
        model.HorasSueno = 7.26m;

        var entrada = await _servicio.RegistrarEntrada(UsuarioId, model, "P");

        entrada.HorasSueno.Should().Be(7.3m);
    }

    [Fact]
    public async Task RegistrarEntrada_DosVecesMismoDia_ActualizaSinDuplicar()
    {
        await _servicio.RegistrarEntrada(UsuarioId, CrearModel(), "P1");
        var model2 = CrearModel();
        model2.EstadoAnimo = 1;

        await _servicio.RegistrarEntrada(UsuarioId, model2, "P2");

        var entradas = await _contexto.EntradasDiario.Where(e => e.UsuarioId == UsuarioId).ToListAsync();
        entradas.Should().ContainSingle();
        entradas[0].EstadoAnimo.Should().Be(1);
        entradas[0].PreguntaGuia.Should().Be("P2");
    }

    [Fact]
    public async Task ActualizarEntrada_Inexistente_RetornaNull()
    {
        var resultado = await _servicio.ActualizarEntrada(UsuarioId, DateOnly.FromDateTime(DateTime.UtcNow), CrearModel());
        resultado.Should().BeNull();
    }

    [Fact]
    public async Task ActualizarEntrada_Existente_ModificaValores()
    {
        await _servicio.RegistrarEntrada(UsuarioId, CrearModel(), "P");
        var hoy = DateOnly.FromDateTime(DateTime.UtcNow);
        var model = CrearModel();
        model.NivelEnergia = 5;

        var resultado = await _servicio.ActualizarEntrada(UsuarioId, hoy, model);

        resultado.Should().NotBeNull();
        resultado!.NivelEnergia.Should().Be(5);
    }

    [Fact]
    public async Task ObtenerDiasConsecutivos_SinEntradas_RetornaCero()
    {
        var racha = await _servicio.ObtenerDiasConsecutivos(UsuarioId);
        racha.Should().Be(0);
    }

    [Fact]
    public async Task ObtenerDiasConsecutivos_TresDiasSeguidos_RetornaTres()
    {
        var hoy = DateOnly.FromDateTime(DateTime.UtcNow);
        for (int i = 0; i < 3; i++)
        {
            _contexto.EntradasDiario.Add(new Models.Entidades.EntradaDiario
            {
                UsuarioId = UsuarioId,
                Fecha = hoy.AddDays(-i),
                EstadoAnimo = 3,
                NivelEnergia = 3,
                FechaRegistro = DateTime.UtcNow
            });
        }
        await _contexto.SaveChangesAsync();

        var racha = await _servicio.ObtenerDiasConsecutivos(UsuarioId);

        racha.Should().Be(3);
    }

    [Fact]
    public async Task ObtenerDiasConsecutivos_SinEntradaHoy_RetornaCero()
    {
        var ayer = DateOnly.FromDateTime(DateTime.UtcNow).AddDays(-1);
        _contexto.EntradasDiario.Add(new Models.Entidades.EntradaDiario
        {
            UsuarioId = UsuarioId,
            Fecha = ayer,
            EstadoAnimo = 3,
            NivelEnergia = 3,
            FechaRegistro = DateTime.UtcNow
        });
        await _contexto.SaveChangesAsync();

        var racha = await _servicio.ObtenerDiasConsecutivos(UsuarioId);

        racha.Should().Be(0);
    }

    [Fact]
    public async Task ObtenerPromedioAnimoMes_SinEntradas_RetornaNull()
    {
        var hoy = DateTime.UtcNow;
        var promedio = await _servicio.ObtenerPromedioAnimoMes(UsuarioId, hoy.Year, hoy.Month);
        promedio.Should().BeNull();
    }

    [Fact]
    public async Task ObtenerPromedioAnimoMes_ConEntradas_CalculaPromedio()
    {
        var hoy = DateOnly.FromDateTime(DateTime.UtcNow);
        _contexto.EntradasDiario.AddRange(
            new Models.Entidades.EntradaDiario { UsuarioId = UsuarioId, Fecha = hoy, EstadoAnimo = 2, NivelEnergia = 3, FechaRegistro = DateTime.UtcNow },
            new Models.Entidades.EntradaDiario { UsuarioId = UsuarioId, Fecha = hoy, EstadoAnimo = 4, NivelEnergia = 3, FechaRegistro = DateTime.UtcNow }
        );
        await _contexto.SaveChangesAsync();

        var promedio = await _servicio.ObtenerPromedioAnimoMes(UsuarioId, hoy.Year, hoy.Month);

        promedio.Should().Be(3.0);
    }

    [Fact]
    public void ObtenerPreguntaGuia_RetornaPreguntaNoVacia()
    {
        var pregunta = _servicio.ObtenerPreguntaGuia();
        pregunta.Should().NotBeNullOrWhiteSpace();
    }
}
