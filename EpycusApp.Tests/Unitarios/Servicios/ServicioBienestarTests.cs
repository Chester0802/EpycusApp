using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioBienestarTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioBienestar _servicio;
    private readonly Mock<ILogger<ServicioBienestar>> _loggerMock;

    public ServicioBienestarTests()
    {
        _contexto = DbContextFactory.CrearContexto("BienestarTest");
        _loggerMock = new Mock<ILogger<ServicioBienestar>>();
        _servicio = new ServicioBienestar(_contexto, _loggerMock.Object);
    }

    private async Task<int> SeedUsuarioAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        _contexto.Categorias.Add(new Categoria { Id = 1, Nombre = "Sueño", Tipo = "Habito", EstaActiva = true });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "BIE001",
            Nombre = "Bienestar Test",
            CorreoElectronico = "bienestar@test.com",
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
    public async Task ObtenerEstadoHoy_SinEstado_RetornaNull()
    {
        var usuarioId = await SeedUsuarioAsync();

        var resultado = await _servicio.ObtenerEstadoHoy(usuarioId);

        resultado.Should().BeNull();
    }

    [Fact]
    public async Task ObtenerEstadoHoy_ConEstadoHoy_RetornaEstado()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.EstadosAnimo.Add(new EstadoAnimo
        {
            UsuarioId = usuarioId,
            Estado = "Feliz",
            Fecha = DateOnly.FromDateTime(DateTime.Today)
        });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerEstadoHoy(usuarioId);

        resultado.Should().NotBeNull();
        resultado!.Estado.Should().Be("Feliz");
    }

    [Fact]
    public async Task RegistrarEstadoAnimo_CreaRegistro()
    {
        var usuarioId = await SeedUsuarioAsync();

        var alerta = await _servicio.RegistrarEstadoAnimo(usuarioId, "Motivado", "Hoy tengo energía");

        var estados = await _contexto.EstadosAnimo.Where(e => e.UsuarioId == usuarioId).ToListAsync();
        estados.Should().ContainSingle();
        estados[0].Estado.Should().Be("Motivado");
        estados[0].Nota.Should().Be("Hoy tengo energía");
    }

    [Fact]
    public async Task ObtenerHistorialAnimo_ConDias_RetornaRango()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.EstadosAnimo.AddRange(
            new EstadoAnimo { UsuarioId = usuarioId, Estado = "Feliz", Fecha = DateOnly.FromDateTime(DateTime.Today) },
            new EstadoAnimo { UsuarioId = usuarioId, Estado = "Cansado", Fecha = DateOnly.FromDateTime(DateTime.Today.AddDays(-1)) },
            new EstadoAnimo { UsuarioId = usuarioId, Estado = "Estresado", Fecha = DateOnly.FromDateTime(DateTime.Today.AddDays(-5)) }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerHistorialAnimo(usuarioId, 3);

        resultado.Should().HaveCount(2);
    }

    [Fact]
    public async Task VerificarUsoExcesivoPomodoro_SinCiclos_RetornaNull()
    {
        var usuarioId = await SeedUsuarioAsync();

        var alerta = await _servicio.VerificarUsoExcesivoPomodoro(usuarioId);

        alerta.Should().BeNull();
    }

    [Fact]
    public async Task VerificarUsoExcesivoPomodoro_MasDe8Ciclos_RetornaAlerta()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.SesionesPomodoro.Add(new SesionPomodoro
        {
            UsuarioId = usuarioId,
            FechaInicio = DateTime.Today,
            CiclosCompletados = 9,
            FueCompletada = true
        });
        await _contexto.SaveChangesAsync();

        var alerta = await _servicio.VerificarUsoExcesivoPomodoro(usuarioId);

        alerta.Should().NotBeNull();
        alerta!.Tipo.Should().Be("Sobrecarga");
        alerta.EsCritica.Should().BeTrue();
    }

    [Fact]
    public async Task VerificarAnimoNegativoConsecutivo_TresDias_RetornaAlerta()
    {
        var usuarioId = await SeedUsuarioAsync();
        for (int i = 0; i < 3; i++)
        {
            _contexto.EstadosAnimo.Add(new EstadoAnimo
            {
                UsuarioId = usuarioId,
                Estado = "Cansado",
                Fecha = DateOnly.FromDateTime(DateTime.Today.AddDays(-i))
            });
        }
        await _contexto.SaveChangesAsync();

        var alerta = await _servicio.VerificarAnimoNegativoConsecutivo(usuarioId);

        alerta.Should().NotBeNull();
        alerta!.Tipo.Should().Be("Estres");
    }

    [Fact]
    public async Task VerificarAnimoNegativoConsecutivo_MenosDeTres_RetornaNull()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.EstadosAnimo.Add(new EstadoAnimo { UsuarioId = usuarioId, Estado = "Feliz", Fecha = DateOnly.FromDateTime(DateTime.Today) });
        await _contexto.SaveChangesAsync();

        var alerta = await _servicio.VerificarAnimoNegativoConsecutivo(usuarioId);

        alerta.Should().BeNull();
    }

    [Fact]
    public async Task ObtenerFraseMotivacionalAleatoria_SinFrases_RetornaNull()
    {
        var frase = await _servicio.ObtenerFraseMotivacionalAleatoria();
        frase.Should().BeNull();
    }

    [Fact]
    public async Task ObtenerFraseMotivacionalAleatoria_ConFrases_RetornaUna()
    {
        _contexto.FrasesMotivacionales.AddRange(
            new FraseMotivacional { Frase = "Nunca te rindas", Autor = "Anónimo", EstaActiva = true },
            new FraseMotivacional { Frase = "Sigue adelante", Autor = "Anónimo", EstaActiva = true }
        );
        await _contexto.SaveChangesAsync();

        var frase = await _servicio.ObtenerFraseMotivacionalAleatoria();

        frase.Should().NotBeNull();
    }

    [Fact]
    public async Task RecomendacionPausaActiva_RetornaMensajeSegunCiclos()
    {
        _servicio.RecomendacionPausaActiva(2).Should().NotBeNull();
        _servicio.RecomendacionPausaActiva(4).Should().NotBeNull();
        _servicio.RecomendacionPausaActiva(6).Should().NotBeNull();
        _servicio.RecomendacionPausaActiva(1).Should().BeNull();
    }

    [Fact]
    public async Task ObtenerHabitosPendientesAsync_SinHabitos_RetornaCero()
    {
        var usuarioId = await SeedUsuarioAsync();

        var pendientes = await _servicio.ObtenerHabitosPendientesAsync(usuarioId);

        pendientes.Should().Be(0);
    }

    [Fact]
    public async Task ObtenerMisionesPendientesAsync_SinMisiones_RetornaCero()
    {
        var usuarioId = await SeedUsuarioAsync();

        var pendientes = await _servicio.ObtenerMisionesPendientesAsync(usuarioId);

        pendientes.Should().Be(0);
    }
}
