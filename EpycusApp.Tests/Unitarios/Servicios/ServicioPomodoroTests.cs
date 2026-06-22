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
    private readonly Mock<IServicioHabitos> _habitosMock;
    private readonly Mock<IServicioMisiones> _misionesMock;
    private readonly Mock<ILogger<ServicioPomodoro>> _loggerMock;

    public ServicioPomodoroTests()
    {
        _contexto = DbContextFactory.CrearContexto("PomodoroTest");
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _bienestarMock = new Mock<IServicioBienestar>();
        _habitosMock = new Mock<IServicioHabitos>();
        _misionesMock = new Mock<IServicioMisiones>();
        _loggerMock = new Mock<ILogger<ServicioPomodoro>>();
        _servicio = new ServicioPomodoro(_contexto, _gamificacionMock.Object, _bienestarMock.Object, _habitosMock.Object, _misionesMock.Object, _loggerMock.Object);

        _gamificacionMock.Setup(g => g.VerificarYOtorgarLogros(It.IsAny<int>())).Returns(Task.CompletedTask);
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

    // T1: ObtenerRachaActualAsync — racha 0 sin sesiones
    [Fact]
    public async Task Racha_SinSesiones_RetornaCero()
    {
        var racha = await _servicio.ObtenerRachaActualAsync(999);
        racha.Should().Be(0);
    }

    // T2: ObtenerRachaActualAsync — racha 1 con sesión hoy
    [Fact]
    public async Task Racha_UnaSesionHoy_RetornaUno()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        await _servicio.FinalizarSesion(sesion.Id, 1, usuarioId);

        var racha = await _servicio.ObtenerRachaActualAsync(usuarioId);
        racha.Should().Be(1);
    }

    // T3: ObtenerRachaActualAsync — racha 3 con 3 días consecutivos
    [Fact]
    public async Task Racha_TresDiasConsecutivos_RetornaTres()
    {
        var usuarioId = await SeedUsuarioAsync();
        for (int i = 2; i >= 0; i--)
        {
            var sesion = new SesionPomodoro
            {
                UsuarioId = usuarioId,
                FechaInicio = DateTime.UtcNow.Date.AddDays(-i).AddHours(10),
                FechaFin = DateTime.UtcNow.Date.AddDays(-i).AddHours(11),
                CiclosCompletados = 1,
                XpOtorgado = 15,
                FueCompletada = true
            };
            _contexto.SesionesPomodoro.Add(sesion);
        }
        await _contexto.SaveChangesAsync();

        var racha = await _servicio.ObtenerRachaActualAsync(usuarioId);
        racha.Should().Be(3);
    }

    // T4: ObtenerRachaActualAsync — racha 1 (gap de 2 días rompe racha, pero ayer hay sesión)
    [Fact]
    public async Task Racha_GapDeDosDias_RetornaUno()
    {
        var usuarioId = await SeedUsuarioAsync();
        for (int i = 3; i >= 0; i -= 2)
        {
            var sesion = new SesionPomodoro
            {
                UsuarioId = usuarioId,
                FechaInicio = DateTime.UtcNow.Date.AddDays(-i).AddHours(10),
                FechaFin = DateTime.UtcNow.Date.AddDays(-i).AddHours(11),
                CiclosCompletados = 1,
                XpOtorgado = 15,
                FueCompletada = true
            };
            _contexto.SesionesPomodoro.Add(sesion);
        }
        await _contexto.SaveChangesAsync();

        var racha = await _servicio.ObtenerRachaActualAsync(usuarioId);
        racha.Should().Be(1);
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
    public async Task IniciarSesionSiNoActiva_SinActiva_CreaSesion()
    {
        var usuarioId = await SeedUsuarioAsync();

        var (exito, sesion, error) = await _servicio.IniciarSesionSiNoActiva(usuarioId, null, null);

        exito.Should().BeTrue();
        sesion.Should().NotBeNull();
        error.Should().BeNull();
    }

    [Fact]
    public async Task IniciarSesionSiNoActiva_ConActiva_RetornaError()
    {
        var usuarioId = await SeedUsuarioAsync();
        await _servicio.IniciarSesion(usuarioId, null, null);

        var (exito, sesion, error) = await _servicio.IniciarSesionSiNoActiva(usuarioId, null, null);

        exito.Should().BeFalse();
        sesion.Should().BeNull();
        error.Should().NotBeNull();
    }

    [Fact]
    public async Task RegistrarCiclo_ActualizaCiclosYOtorgaXP()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, CiclosAntesDescansoLargo = 4 });
        await _contexto.SaveChangesAsync();

        _gamificacionMock.Setup(g => g.SumarXP(usuarioId, ConstantesGamificacion.XpBasePomodoro * 2))
            .ReturnsAsync((ConstantesGamificacion.XpBasePomodoro * 2, false, 1));

        var (xp, sugerirDescanso, _) = await _servicio.RegistrarCiclo(sesion.Id, 2, usuarioId);

        xp.Should().Be(ConstantesGamificacion.XpBasePomodoro * 2);
        sugerirDescanso.Should().BeFalse();

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.CiclosCompletados.Should().Be(2);
        sesionDb.XpOtorgado.Should().Be(ConstantesGamificacion.XpBasePomodoro * 2);

        _gamificacionMock.Verify(g => g.SumarXP(usuarioId, ConstantesGamificacion.XpBasePomodoro * 2), Times.Once);
    }

    [Fact]
    public async Task RegistrarCiclo_XpPorDeltaDeCiclos_CuandoMultipleLlamadas()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, CiclosAntesDescansoLargo = 4 });
        await _contexto.SaveChangesAsync();

        _gamificacionMock.Setup(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()))
            .ReturnsAsync((15, false, 1));

        await _servicio.RegistrarCiclo(sesion.Id, 2, usuarioId);
        var (xp, _, _) = await _servicio.RegistrarCiclo(sesion.Id, 4, usuarioId);

        xp.Should().Be(ConstantesGamificacion.XpBasePomodoro * 2);

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.CiclosCompletados.Should().Be(4);
        sesionDb.XpOtorgado.Should().Be(ConstantesGamificacion.XpBasePomodoro * 4);
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

        var (_, sugerirDescanso, pausaActiva) = await _servicio.RegistrarCiclo(sesion.Id, 4, usuarioId);

        sugerirDescanso.Should().BeTrue();
    }

    [Fact]
    public async Task FinalizarSesion_MarcaComoCompletadaYOtorgaBonus()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        _gamificacionMock.Setup(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()))
            .ReturnsAsync((25, false, 1));

        var (xpTotal, xpBonus) = await _servicio.FinalizarSesion(sesion.Id, 3, usuarioId);

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.FueCompletada.Should().BeTrue();
        sesionDb.CiclosCompletados.Should().Be(3);
        sesionDb.FechaFin.Should().NotBeNull();
        xpBonus.Should().Be(3 * 5 + 10);

        _gamificacionMock.Verify(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()), Times.Once);
    }

    [Fact]
    public async Task FinalizarSesion_SinCiclos_NoOtorgaBonus()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        var (xpTotal, xpBonus) = await _servicio.FinalizarSesion(sesion.Id, 0, usuarioId);

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.FueCompletada.Should().BeTrue();
        sesionDb.CiclosCompletados.Should().Be(0);
        sesionDb.FechaFin.Should().NotBeNull();
        sesionDb.XpOtorgado.Should().Be(0);
        xpBonus.Should().Be(0);
        xpTotal.Should().Be(0);

        _gamificacionMock.Verify(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()), Times.Never);
        _gamificacionMock.Verify(g => g.VerificarYOtorgarLogros(It.IsAny<int>()), Times.Never);
    }

    [Fact]
    public async Task CancelarSesion_MarcaComoNoCompletada()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        await _servicio.CancelarSesion(sesion.Id, usuarioId);

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.FueCompletada.Should().BeFalse();
        sesionDb.FechaFin.Should().NotBeNull();
    }

    [Fact]
    public async Task ObtenerConfiguracion_SinConfig_RetornaDefaultSinGuardar()
    {
        var usuarioId = await SeedUsuarioAsync();

        var config = await _servicio.ObtenerConfiguracion(usuarioId);

        config.Should().NotBeNull();
        config.TiempoEstudioMin.Should().Be(25);
        config.TiempoDescansoMin.Should().Be(5);

        var countEnDb = await _contexto.ConfiguracionesPomodoro.CountAsync();
        countEnDb.Should().Be(0);
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
            ModoPersonalizadoMin = 30,
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
        config.ModoPersonalizadoMin.Should().Be(30);
    }

    [Fact]
    public async Task RegistrarCiclo_CiclosNoDecrecientes_NoOtorgaXP()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, CiclosAntesDescansoLargo = 4 });
        await _contexto.SaveChangesAsync();

        await _servicio.RegistrarCiclo(sesion.Id, 3, usuarioId);

        var (xp, _, _) = await _servicio.RegistrarCiclo(sesion.Id, 2, usuarioId);

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

    // T5: ObtenerEstadisticasPeriodoAsync
    [Fact]
    public async Task EstadisticasPeriodo_CalculaCiclosMinutosXP()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, TiempoEstudioMin = 25 });
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);
        sesion.CiclosCompletados = 3;
        sesion.XpOtorgado = 45;
        await _contexto.SaveChangesAsync();

        var desde = DateTime.UtcNow.Date;
        var hasta = DateTime.UtcNow.Date.AddDays(1);
        var stats = await _servicio.ObtenerEstadisticasPeriodoAsync(usuarioId, desde, hasta);

        stats.Ciclos.Should().Be(3);
        stats.Minutos.Should().Be(3 * 25);
        stats.Xp.Should().Be(45);
    }

    // T6-T7: ObtenerTareasEnfoqueAsync
    [Fact]
    public async Task TareasEnfoque_ConHabitosActivos_RetornaTareas()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Categorias.Add(new Categoria { Nombre = "Test", Icono = "bi-test", Tipo = "Habito", EstaActiva = true });
        await _contexto.SaveChangesAsync();
        var catId = _contexto.Categorias.First().Id;

        _contexto.Habitos.Add(new Habito
        {
            UsuarioId = usuarioId, Nombre = "Leer", EstaActivo = true, ConPomodoro = true,
            CategoriaId = catId, Frecuencia = "Diario"
        });
        await _contexto.SaveChangesAsync();

        var tareas = await _servicio.ObtenerTareasEnfoqueAsync(usuarioId);
        tareas.Should().Contain(t => t.Nombre == "Leer" && t.Tipo == "Habito");
    }

    [Fact]
    public async Task TareasEnfoque_ConMisionesActivas_RetornaTareas()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Categorias.Add(new Categoria { Nombre = "Test", Icono = "bi-test", Tipo = "Mision", EstaActiva = true });
        await _contexto.SaveChangesAsync();
        var catId = _contexto.Categorias.First().Id;

        _contexto.Misiones.Add(new Mision
        {
            UsuarioId = usuarioId, Nombre = "Proyecto Final", Estado = "En progreso", ConPomodoro = true,
            CategoriaId = catId, Prioridad = "Alta"
        });
        await _contexto.SaveChangesAsync();

        var tareas = await _servicio.ObtenerTareasEnfoqueAsync(usuarioId);
        tareas.Should().Contain(t => t.Nombre == "Proyecto Final" && t.Tipo == "Mision");
    }

    [Fact]
    public async Task ObtenerEstadisticasSemanalesAsync_Retorna7Dias()
    {
        var usuarioId = await SeedUsuarioAsync();
        var stats = await _servicio.ObtenerEstadisticasSemanalesAsync(usuarioId);
        stats.Should().HaveCount(7);
    }

    [Fact]
    public async Task IniciarSesion_ConHabitoInvalido_LanzaError()
    {
        var usuarioId = await SeedUsuarioAsync();
        await Assert.ThrowsAsync<InvalidOperationException>(() =>
            _servicio.IniciarSesion(usuarioId, 999, null));
    }

    [Fact]
    public async Task IniciarSesion_ConMisionInvalida_LanzaError()
    {
        var usuarioId = await SeedUsuarioAsync();
        await Assert.ThrowsAsync<InvalidOperationException>(() =>
            _servicio.IniciarSesion(usuarioId, null, 999));
    }

    [Fact]
    public async Task ObtenerHistorialAsync_SinSesiones_RetornaVacio()
    {
        var usuarioId = await SeedUsuarioAsync();

        var historial = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-30), DateTime.UtcNow);

        historial.Should().BeEmpty();
    }

    [Fact]
    public async Task ObtenerHistorialAsync_ConSesiones_RetornaPagina()
    {
        var usuarioId = await SeedUsuarioAsync();
        for (int i = 0; i < 5; i++)
        {
            var s = new SesionPomodoro
            {
                UsuarioId = usuarioId,
                FechaInicio = DateTime.UtcNow.AddHours(-i),
                CiclosCompletados = 1,
                XpOtorgado = 15,
                FueCompletada = true
            };
            _contexto.SesionesPomodoro.Add(s);
        }
        await _contexto.SaveChangesAsync();

        var pagina1 = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 2);
        var pagina2 = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 2, 2);

        pagina1.Should().HaveCount(2);
        pagina2.Should().HaveCount(2);
    }

    [Fact]
    public async Task ObtenerHistorialAsync_FueraDeRango_RetornaVacio()
    {
        var usuarioId = await SeedUsuarioAsync();
        var s = new SesionPomodoro
        {
            UsuarioId = usuarioId,
            FechaInicio = DateTime.UtcNow.AddDays(-10),
            CiclosCompletados = 1,
            XpOtorgado = 15,
            FueCompletada = true
        };
        _contexto.SesionesPomodoro.Add(s);
        await _contexto.SaveChangesAsync();

        var historial = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-5), DateTime.UtcNow);

        historial.Should().BeEmpty();
    }

    [Fact]
    public async Task EstadisticasPeriodo_SinSesiones_RetornaCeros()
    {
        var usuarioId = await SeedUsuarioAsync();

        var stats = await _servicio.ObtenerEstadisticasPeriodoAsync(usuarioId, DateTime.UtcNow.AddDays(-30), DateTime.UtcNow);

        stats.Ciclos.Should().Be(0);
        stats.Minutos.Should().Be(0);
        stats.Xp.Should().Be(0);
    }

    [Fact]
    public async Task RegistrarCiclo_SesionInexistente_RetornaCeros()
    {
        var (xp, sugerir, pausa) = await _servicio.RegistrarCiclo(999, 1, 1);

        xp.Should().Be(0);
        sugerir.Should().BeFalse();
        pausa.Should().BeNull();
    }

    [Fact]
    public async Task FinalizarSesion_SesionInexistente_RetornaCeros()
    {
        var (xpTotal, xpBonus) = await _servicio.FinalizarSesion(999, 1, 1);

        xpTotal.Should().Be(0);
        xpBonus.Should().Be(0);
    }

    [Fact]
    public async Task CancelarSesion_SesionInexistente_NoLanzaExcepcion()
    {
        await _servicio.CancelarSesion(999, 1);
    }

    [Fact]
    public async Task RegistrarCiclo_DeOtroUsuario_NoOtorgaXP()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        var (xp, _, _) = await _servicio.RegistrarCiclo(sesion.Id, 1, 999);

        xp.Should().Be(0);
    }

    [Fact]
    public async Task FinalizarSesion_DeOtroUsuario_RetornaCeros()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        var (xpTotal, xpBonus) = await _servicio.FinalizarSesion(sesion.Id, 1, 999);

        xpTotal.Should().Be(0);
        xpBonus.Should().Be(0);
    }

    [Fact]
    public async Task CancelarSesion_DeOtroUsuario_NoCancela()
    {
        var usuarioId = await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(usuarioId, null, null);

        await _servicio.CancelarSesion(sesion.Id, 999);

        var sesionDb = await _contexto.SesionesPomodoro.FirstAsync(s => s.Id == sesion.Id);
        sesionDb.FechaFin.Should().BeNull();
        sesionDb.FueCompletada.Should().BeFalse();
    }

    // NF-22: Tests de filtros en ObtenerHistorialAsync
    [Fact]
    public async Task ObtenerHistorialAsync_FiltroCompletada_True_RetornaSoloCompletadas()
    {
        var usuarioId = await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 0, XpOtorgado = 0, FueCompletada = false };
        _contexto.SesionesPomodoro.AddRange(s1, s2);
        await _contexto.SaveChangesAsync();

        var historial = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, completada: true);

        historial.Should().HaveCount(1);
        historial[0].FueCompletada.Should().BeTrue();
    }

    [Fact]
    public async Task ObtenerHistorialAsync_FiltroCompletada_False_RetornaSoloNoCompletadas()
    {
        var usuarioId = await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 0, XpOtorgado = 0, FueCompletada = false };
        _contexto.SesionesPomodoro.AddRange(s1, s2);
        await _contexto.SaveChangesAsync();

        var historial = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, completada: false);

        historial.Should().HaveCount(1);
        historial[0].FueCompletada.Should().BeFalse();
    }

    [Fact]
    public async Task ObtenerHistorialAsync_FiltroConXp_True_RetornaSoloConXp()
    {
        var usuarioId = await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 1, XpOtorgado = 0, FueCompletada = true };
        _contexto.SesionesPomodoro.AddRange(s1, s2);
        await _contexto.SaveChangesAsync();

        var historial = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, conXp: true);

        historial.Should().HaveCount(1);
        historial[0].XpOtorgado.Should().BeGreaterThan(0);
    }

    [Fact]
    public async Task ObtenerHistorialAsync_FiltroConXp_False_RetornaSoloSinXp()
    {
        var usuarioId = await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 1, XpOtorgado = 0, FueCompletada = true };
        _contexto.SesionesPomodoro.AddRange(s1, s2);
        await _contexto.SaveChangesAsync();

        var historial = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, conXp: false);

        historial.Should().HaveCount(1);
        historial[0].XpOtorgado.Should().Be(0);
    }

    // NF-6: Tests de estadísticas avanzadas
    [Fact]
    public async Task EstadisticasAvanzadas_SinSesiones_RetornaCeros()
    {
        var usuarioId = await SeedUsuarioAsync();

        var stats = await _servicio.ObtenerEstadisticasAvanzadasAsync(usuarioId, DateTime.UtcNow.AddMonths(-1), DateTime.UtcNow);

        stats.Should().NotBeNull();
        stats.TotalCiclos.Should().Be(0);
        stats.TotalMinutos.Should().Be(0);
        stats.TotalXp.Should().Be(0);
        stats.PromedioCiclosPorDia.Should().Be(0);
        stats.PorMes.Should().BeEmpty();
        stats.HeatmapHoras.Should().HaveCount(24);
        stats.HeatmapHoras.Should().AllSatisfy(h => h.Ciclos.Should().Be(0));
    }

    [Fact]
    public async Task EstadisticasAvanzadas_ConSesiones_CalculaCorrectamente()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.ConfiguracionesPomodoro.Add(new ConfiguracionPomodoro { UsuarioId = usuarioId, TiempoEstudioMin = 25 });
        await _contexto.SaveChangesAsync();

        var s1 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.Date.AddHours(10), FechaFin = DateTime.UtcNow.Date.AddHours(11), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.Date.AddDays(-1).AddHours(15), FechaFin = DateTime.UtcNow.Date.AddDays(-1).AddHours(16), CiclosCompletados = 1, XpOtorgado = 15, FueCompletada = true };
        _contexto.SesionesPomodoro.AddRange(s1, s2);
        await _contexto.SaveChangesAsync();

        var stats = await _servicio.ObtenerEstadisticasAvanzadasAsync(usuarioId, DateTime.UtcNow.AddDays(-2), DateTime.UtcNow);

        stats.TotalCiclos.Should().Be(3);
        stats.TotalMinutos.Should().Be(120);
        stats.TotalXp.Should().Be(45);
        stats.PromedioCiclosPorDia.Should().Be(1.0);
        stats.PorMes.Should().NotBeEmpty();
        stats.HeatmapHoras.Should().HaveCount(24);
        stats.HeatmapHoras.First(h => h.Hora == 10).Ciclos.Should().Be(2);
        stats.HeatmapHoras.First(h => h.Hora == 15).Ciclos.Should().Be(1);
    }

    [Fact]
    public async Task ObtenerHistorialAsync_FiltrosCombinados_RetornaCoincidentes()
    {
        var usuarioId = await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-3), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 0, XpOtorgado = 0, FueCompletada = false };
        var s3 = new SesionPomodoro { UsuarioId = usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 1, XpOtorgado = 15, FueCompletada = true };
        _contexto.SesionesPomodoro.AddRange(s1, s2, s3);
        await _contexto.SaveChangesAsync();

        var historial = await _servicio.ObtenerHistorialAsync(usuarioId, DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, completada: true, conXp: true);

        historial.Should().HaveCount(2);
        historial.Should().AllSatisfy(s => { s.FueCompletada.Should().BeTrue(); s.XpOtorgado.Should().BeGreaterThan(0); });
    }
}
