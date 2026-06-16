using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioProgresoTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioProgreso _servicio;
    private readonly Mock<ILogger<ServicioProgreso>> _loggerMock;

    public ServicioProgresoTests()
    {
        _contexto = DbContextFactory.CrearContexto("ProgresoTest");
        _loggerMock = new Mock<ILogger<ServicioProgreso>>();
        _servicio = new ServicioProgreso(_contexto, _loggerMock.Object);
    }

    private async Task<int> SeedUsuarioAsync()
    {
        var nivelInicial = new Nivel { Id = 1, Numero = 1, Titulo = "Novato", XpRequerido = 0 };
        _contexto.Niveles.Add(nivelInicial);
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "PROG001",
            Nombre = "Progreso Test",
            CorreoElectronico = "progreso@test.com",
            ContrasenaHash = "hash",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            RolId = 1,
            CarreraId = 1
        };
        _contexto.Usuarios.Add(usuario);
        await _contexto.SaveChangesAsync();
        return usuario.Id;
    }

    [Fact]
    public async Task ObtenerProgreso_Existente_RetornaProgreso()
    {
        var usuarioId = await SeedUsuarioAsync();
        var progreso = new ProgresoUsuario
        {
            UsuarioId = usuarioId,
            NivelActualId = 1,
            XpTotal = 100,
            RachaActual = 5,
            RachaMaxima = 10
        };
        _contexto.ProgresosUsuario.Add(progreso);
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerProgreso(usuarioId);

        resultado.Should().NotBeNull();
        resultado.XpTotal.Should().Be(100);
        resultado.RachaActual.Should().Be(5);
    }

    [Fact]
    public async Task ObtenerProgreso_SinProgreso_CreaValoresDefault()
    {
        var usuarioId = await SeedUsuarioAsync();

        var resultado = await _servicio.ObtenerProgreso(usuarioId);

        resultado.Should().NotBeNull();
        resultado.XpTotal.Should().Be(0);
        resultado.RachaActual.Should().Be(0);
        resultado.NivelActual.Numero.Should().Be(1);
    }

    [Fact]
    public async Task ObtenerLogrosUsuario_SinLogros_RetornaListaVacia()
    {
        var resultado = await _servicio.ObtenerLogrosUsuario(999);
        resultado.Should().BeEmpty();
    }

    [Fact]
    public async Task ObtenerLogrosUsuario_ConLogros_RetornaLogros()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Logros.Add(new Logro { Id = 1, Nombre = "Logro 1", CondicionTipo = "XpTotal", CondicionValor = 100, EstaActivo = true });
        await _contexto.SaveChangesAsync();

        _contexto.LogrosUsuario.Add(new LogroUsuario { UsuarioId = usuarioId, LogroId = 1 });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerLogrosUsuario(usuarioId);

        resultado.Should().ContainSingle();
        resultado[0].LogroId.Should().Be(1);
    }

    [Fact]
    public async Task ObtenerNivelSiguiente_Existente_RetornaNivel()
    {
        _contexto.Niveles.AddRange(
            new Nivel { Id = 1, Numero = 1, Titulo = "Uno", XpRequerido = 0 },
            new Nivel { Id = 2, Numero = 2, Titulo = "Dos", XpRequerido = 150 }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerNivelSiguiente(1);

        resultado.Should().NotBeNull();
        resultado!.Numero.Should().Be(2);
    }

    [Fact]
    public async Task ObtenerNivelSiguiente_NoExistente_RetornaNull()
    {
        _contexto.Niveles.Add(new Nivel { Id = 1, Numero = 1, Titulo = "Uno", XpRequerido = 0 });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerNivelSiguiente(99);

        resultado.Should().BeNull();
    }

    [Fact]
    public async Task ObtenerTodosLosLogros_RetornaSoloActivos()
    {
        _contexto.Logros.AddRange(
            new Logro { Id = 1, Nombre = "Activo 1", EstaActivo = true },
            new Logro { Id = 2, Nombre = "Inactivo", EstaActivo = false },
            new Logro { Id = 3, Nombre = "Activo 2", EstaActivo = true }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerTodosLosLogros();

        resultado.Should().HaveCount(2);
        resultado.Should().Contain(l => l.Id == 1);
        resultado.Should().Contain(l => l.Id == 3);
    }

    [Fact]
    public async Task ObtenerNivelInicialAsync_RetornaNivelMasBajo()
    {
        _contexto.Niveles.AddRange(
            new Nivel { Id = 2, Numero = 5, Titulo = "Alto", XpRequerido = 500 },
            new Nivel { Id = 1, Numero = 1, Titulo = "Inicial", XpRequerido = 0 },
            new Nivel { Id = 3, Numero = 3, Titulo = "Medio", XpRequerido = 200 }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerNivelInicialAsync();

        resultado.Should().NotBeNull();
        resultado!.Numero.Should().Be(1);
    }
}
