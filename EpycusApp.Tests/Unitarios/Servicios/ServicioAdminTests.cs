using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioAdminTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioAdmin _servicio;
    private readonly Mock<ILogger<ServicioAdmin>> _loggerMock;

    public ServicioAdminTests()
    {
        _contexto = DbContextFactory.CrearContexto("AdminTest");
        _loggerMock = new Mock<ILogger<ServicioAdmin>>();
        _servicio = new ServicioAdmin(_contexto, _loggerMock.Object);
    }

    private async Task<int> SeedUsuarioAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "ADM001",
            Nombre = "Admin Test",
            CorreoElectronico = "admin@test.com",
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
    public async Task ObtenerTodosUsuarios_ConUsuarios_RetornaLista()
    {
        await SeedUsuarioAsync();

        var usuarios = await _servicio.ObtenerTodosUsuarios();

        usuarios.Should().NotBeEmpty();
        usuarios[0].Nombre.Should().Be("Admin Test");
    }

    [Fact]
    public async Task ObtenerUsuarioPorId_Existente_RetornaUsuario()
    {
        var usuarioId = await SeedUsuarioAsync();

        var usuario = await _servicio.ObtenerUsuarioPorId(usuarioId);

        usuario.Should().NotBeNull();
        usuario!.Id.Should().Be(usuarioId);
    }

    [Fact]
    public async Task ObtenerUsuarioPorId_NoExistente_RetornaNull()
    {
        var usuario = await _servicio.ObtenerUsuarioPorId(999);
        usuario.Should().BeNull();
    }

    [Fact]
    public async Task ActivarSuscripcion_UsuarioSinSuscripcion_CreaNueva()
    {
        var usuarioId = await SeedUsuarioAsync();

        await _servicio.ActivarSuscripcion(usuarioId, adminId: 1);

        var suscripciones = await _contexto.Suscripciones.Where(s => s.UsuarioId == usuarioId).ToListAsync();
        suscripciones.Should().ContainSingle();
        suscripciones[0].Plan.Should().Be("Premium");
        suscripciones[0].EstaActiva.Should().BeTrue();
    }

    [Fact]
    public async Task ActivarSuscripcion_ConSuscripcionVigente_DesactivaViejaYCreaNueva()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Suscripciones.Add(new Suscripcion
        {
            UsuarioId = usuarioId,
            Plan = "Básico",
            PrecioSoles = 10,
            FechaInicio = DateOnly.FromDateTime(DateTime.Today.AddMonths(-1)),
            FechaFin = DateOnly.FromDateTime(DateTime.Today.AddMonths(11)),
            EstaActiva = true
        });
        await _contexto.SaveChangesAsync();

        await _servicio.ActivarSuscripcion(usuarioId, adminId: 1);

        var suscripciones = await _contexto.Suscripciones
            .Where(s => s.UsuarioId == usuarioId)
            .OrderBy(s => s.Id)
            .ToListAsync();
        suscripciones[0].EstaActiva.Should().BeFalse();
        suscripciones[1].EstaActiva.Should().BeTrue();
        suscripciones[1].Plan.Should().Be("Premium");
    }

    [Fact]
    public async Task DesactivarSuscripcion_SuscripcionActiva_Desactiva()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Suscripciones.Add(new Suscripcion
        {
            UsuarioId = usuarioId,
            Plan = "Premium",
            FechaInicio = DateOnly.FromDateTime(DateTime.Today),
            FechaFin = DateOnly.FromDateTime(DateTime.Today.AddYears(1)),
            EstaActiva = true
        });
        await _contexto.SaveChangesAsync();

        await _servicio.DesactivarSuscripcion(usuarioId);

        var suscripcion = await _contexto.Suscripciones.FirstAsync(s => s.UsuarioId == usuarioId);
        suscripcion.EstaActiva.Should().BeFalse();
    }

    [Fact]
    public async Task ObtenerFrases_ConFrases_RetornaOrdenadas()
    {
        _contexto.FrasesMotivacionales.AddRange(
            new FraseMotivacional { Frase = "Beta", Autor = "A", EstaActiva = true },
            new FraseMotivacional { Frase = "Alfa", Autor = "B", EstaActiva = true }
        );
        await _contexto.SaveChangesAsync();

        var frases = await _servicio.ObtenerFrases();

        frases.Should().HaveCount(2);
        frases[0].Frase.Should().Be("Alfa");
    }

    [Fact]
    public async Task CrearFrase_ConDatosValidos_AgregaFrase()
    {
        await _servicio.CrearFrase("Nunca te rindas", "Anónimo");

        var frases = await _contexto.FrasesMotivacionales.ToListAsync();
        frases.Should().ContainSingle();
        frases[0].Frase.Should().Be("Nunca te rindas");
    }

    [Fact]
    public async Task CrearFrase_TextoVacio_NoAgrega()
    {
        await _servicio.CrearFrase("", "Anónimo");

        var frases = await _contexto.FrasesMotivacionales.ToListAsync();
        frases.Should().BeEmpty();
    }

    [Fact]
    public async Task EliminarFrase_Existente_Remueve()
    {
        _contexto.FrasesMotivacionales.Add(new FraseMotivacional { Id = 1, Frase = "Test", Autor = "A", EstaActiva = true });
        await _contexto.SaveChangesAsync();

        await _servicio.EliminarFrase(1);

        var frases = await _contexto.FrasesMotivacionales.ToListAsync();
        frases.Should().BeEmpty();
    }

    [Fact]
    public async Task EliminarFrase_NoExistente_NoLanzaExcepcion()
    {
        await _servicio.Invoking(s => s.EliminarFrase(999))
            .Should().NotThrowAsync();
    }
}
