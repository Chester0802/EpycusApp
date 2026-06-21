using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioPomodoroTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioPomodoro _servicio;
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private readonly Mock<IServicioBienestar> _bienestarMock;
    private readonly Mock<ILogger<ServicioPomodoro>> _loggerMock;

    public ServicioPomodoroTests()
    {
        _contexto = DbContextFactory.CrearContexto("PomodoroTest");
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _bienestarMock = new Mock<IServicioBienestar>();
        _loggerMock = new Mock<ILogger<ServicioPomodoro>>();
        _servicio = new ServicioPomodoro(_contexto, _gamificacionMock.Object, _bienestarMock.Object, _loggerMock.Object);
    }

    private async Task<int> SeedUsuarioAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "POM001",
            Nombre = "Pomodoro Test",
            CorreoElectronico = "pomodoro@test.com",
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
    public async Task IniciarSesion_CreaSesionCorrectamente()
    {
        var usuarioId = await SeedUsuarioAsync();

        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        sesion.Should().NotBeNull();
        sesion.UsuarioId.Should().Be(usuarioId);
        sesion.FueCompletada.Should().BeFalse();
        sesion.CiclosCompletados.Should().Be(0);
    }

    [Fact]
    public async Task RegistrarCiclo_ActualizaCiclosYOtorgaXP()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, CiclosAntesDescansoLargo = 4 });
        await _contexto.SaveChangesAsync();

        _gamificacionMock.Setup(g => g.SumarXP(usuarioId, ConstantesGamificacion.XP_BASE_POMODORO))
            .ReturnsAsync((ConstantesGamificacion.XP_BASE_POMODORO, false, 1));

        var (xp, sugerirDescanso, _) = await _servicio.RegistrarCiclo(sesion.Id, 2);

        xp.Should().Be(ConstantesGamificacion.XP_BASE_POMODORO);
        sugerirDescanso.Should().BeFalse();

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.CiclosCompletados.Should().Be(2);
        sesionDb.XpOtorgado.Should().Be(ConstantesGamificacion.XP_BASE_POMODORO);

        _gamificacionMock.Verify(g => g.SumarXP(usuarioId, ConstantesGamificacion.XP_BASE_POMODORO), Times.Once);
    }

    [Fact]
    public async Task RegistrarCiclo_SugiereDescansoLargo_CuandoMultiplo()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, CiclosAntesDescansoLargo = 4 });
        await _contexto.SaveChangesAsync();

        _gamificacionMock.Setup(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()))
            .ReturnsAsync((15, false, 1));

        var (_, sugerirDescanso, pausaActiva) = await _servicio.RegistrarCiclo(sesion.Id, 4);

        sugerirDescanso.Should().BeTrue();

        _gamificacionMock.Verify(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()), Times.Once);
    }

    [Fact]
    public async Task FinalizarSesion_MarcaComoCompletada()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        await _servicio.FinalizarSesion(sesion.Id, 3);

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.FueCompletada.Should().BeTrue();
        sesionDb.CiclosCompletados.Should().Be(3);
        sesionDb.FechaFin.Should().NotBeNull();

        _gamificacionMock.Verify(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()), Times.Never);
    }

    [Fact]
    public async Task CancelarSesion_MarcaComoNoCompletada()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        await _servicio.CancelarSesion(sesion.Id);

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.FueCompletada.Should().BeFalse();
        sesionDb.FechaFin.Should().NotBeNull();
    }

    [Fact]
    public async Task ObtenerConfiguracion_SinConfig_CreaDefault()
    {
        var usuarioId = await SeedUsuarioAsync();

        var config = await _servicio.ObtenerConfiguracion(usuarioId);

        config.Should().NotBeNull();
        config.TiempoEstudioMin.Should().Be(25);
        config.TiempoDescansoMin.Should().Be(5);
    }

    [Fact]
    public async Task ActualizarConfiguracion_ActualizaValores()
    {
        var usuarioId = await SeedUsuarioAsync();
        var dto = new ActualizarConfiguracionPomodoroDto
        {
            TiempoEstudioMin = 50,
            TiempoDescansoMin = 10,
            TiempoDescansoLargoMin = 20,
            CiclosAntesDescansoLargo = 6,
            SonidoActivo = false,
            SonidoSeleccionado = "digital",
            Volumen = 80,
            AutoIniciarDescanso = true,
            AutoIniciarEnfoque = false,
            TicTacActivo = true,
            MetaDiariaCiclos = 8,
            ModoPersonalizadoMinutos = 30,
            VibracionActiva = false,
            NotificacionDesktop = true
        };

        await _servicio.ActualizarConfiguracion(usuarioId, dto);

        var config = await _contexto.ConfiguracionesPomodoro.FirstAsync(c => c.UsuarioId == usuarioId);
        config.TiempoEstudioMin.Should().Be(50);
        config.TiempoDescansoMin.Should().Be(10);
        config.SonidoActivo.Should().BeFalse();
        config.SonidoSeleccionado.Should().Be("digital");
        config.Volumen.Should().Be(80);
        config.AutoIniciarDescanso.Should().BeTrue();
        config.TicTacActivo.Should().BeTrue();
        config.MetaDiariaCiclos.Should().Be(8);
        config.ModoPersonalizadoMinutos.Should().Be(30);
    }

    [Fact]
    public async Task RegistrarCiclo_CiclosNoDecrecientes_NoOtorgaXP()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, CiclosAntesDescansoLargo = 4 });
        await _contexto.SaveChangesAsync();

        await _servicio.RegistrarCiclo(sesion.Id, 3);

        var (xp, _, _) = await _servicio.RegistrarCiclo(sesion.Id, 2);

        xp.Should().Be(0);
        _gamificacionMock.Verify(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()), Times.Once);
    }

    [Fact]
    public async Task ObtenerSesionesHoyAsync_RetornaSesionesDeHoy()
    {
        var usuarioId = await SeedUsuarioAsync();
        await _servicio.IniciarSesion(usuarioId, null, null);
        await _servicio.IniciarSesion(usuarioId, null, null);

        var sesiones = await _servicio.ObtenerSesionesHoyAsync(usuarioId);

        sesiones.Should().HaveCount(2);
    }

    [Fact]
    public async Task ObtenerTipAleatorio_SinTips_RetornaVacio()
    {
        var tip = await _servicio.ObtenerTipAleatorio();
        tip.Should().BeEmpty();
    }

    [Fact]
    public async Task ObtenerTipAleatorio_ConTip_RetornaTip()
    {
        _contexto.TipsPomodoro.Add(new TipPomodoro { Tip = "Toma agua frecuentemente", EstaActivo = true });
        await _contexto.SaveChangesAsync();

        var tip = await _servicio.ObtenerTipAleatorio();

        tip.Should().Be("Toma agua frecuentemente");
    }
}
