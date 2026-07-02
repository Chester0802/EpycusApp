using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.ViewFeatures;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Controladores;

[Trait("Categoria", "Unitario")]
public class PomodoroControllerTests
{
    private readonly Mock<IServicioPomodoro> _pomodoroMock;
    private readonly Mock<IServicioMisiones> _misionesMock;
    private readonly PomodoroController _controller;

    public PomodoroControllerTests()
    {
        _pomodoroMock = new Mock<IServicioPomodoro>();
        _misionesMock = new Mock<IServicioMisiones>();
        var logger = new Mock<ILogger<PomodoroController>>();
        _controller = new PomodoroController(_pomodoroMock.Object, _misionesMock.Object, logger.Object);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    [Fact]
    public async Task Index_Autenticado_RetornaVista()
    {
        _pomodoroMock.Setup(p => p.ObtenerConfiguracion(1)).ReturnsAsync(new ConfiguracionPomodoro { UsuarioId = 1 });
        _pomodoroMock.Setup(p => p.ObtenerSesionesHoyAsync(1)).ReturnsAsync(new List<SesionPomodoro>());

        var resultado = await _controller.Index();

        resultado.Should().BeOfType<ViewResult>();
    }

    // Regresión: una sesión que quedó abierta mucho tiempo y se canceló/finalizó sin
    // completar ningún ciclo (ej. bug de restauración de timer, doble pestaña, cancelación
    // manual) NO debe inflar "Minutos enfocados" del Resumen de hoy mientras "Ciclos
    // completados"/"XP ganado" se quedan en 0 en el mismo resumen — ese desfase fue
    // justo lo reportado por el usuario probando el Pomodoro en producción.
    [Fact]
    public async Task Index_SesionSinCiclosCompletados_NoCuentaMinutosEnResumen()
    {
        _pomodoroMock.Setup(p => p.ObtenerConfiguracion(1)).ReturnsAsync(new ConfiguracionPomodoro { UsuarioId = 1 });
        var sesionRota = new SesionPomodoro
        {
            Id = 1,
            UsuarioId = 1,
            FechaInicio = DateTime.UtcNow.AddHours(-2),
            FechaFin = DateTime.UtcNow,
            CiclosCompletados = 0,
            XpOtorgado = 0,
            FueCompletada = false
        };
        var sesionReal = new SesionPomodoro
        {
            Id = 2,
            UsuarioId = 1,
            FechaInicio = DateTime.UtcNow.AddMinutes(-25),
            FechaFin = DateTime.UtcNow,
            CiclosCompletados = 1,
            XpOtorgado = 15,
            FueCompletada = true
        };
        _pomodoroMock.Setup(p => p.ObtenerSesionesHoyAsync(1)).ReturnsAsync(new List<SesionPomodoro> { sesionRota, sesionReal });

        var resultado = await _controller.Index();

        var view = resultado.Should().BeOfType<ViewResult>().Subject;
        var modelo = view.Model.Should().BeOfType<EpycusApp.ViewModels.PomodoroIndexViewModel>().Subject;

        modelo.EstadisticasHoy.CiclosCompletados.Should().Be(1);
        modelo.EstadisticasHoy.XpGanado.Should().Be(15);
        // 120 min de la sesión rota NO deben sumarse; solo los ~25 min de la sesión real.
        modelo.EstadisticasHoy.MinutosEnfocados.Should().BeInRange(24, 25);
        modelo.HistorialHoy.Should().ContainSingle(s => s.Id == 2);
    }

    [Fact]
    public async Task Configuracion_Get_RetornaVistaConDto()
    {
        _pomodoroMock.Setup(p => p.ObtenerConfiguracion(1))
            .ReturnsAsync(new ConfiguracionPomodoro { UsuarioId = 1, TiempoEstudioMin = 25 });

        var resultado = await _controller.Configuracion();

        var view = resultado.Should().BeOfType<ViewResult>().Subject;
        view.Model.Should().BeOfType<ActualizarConfiguracionPomodoroDto>();
    }

    [Fact]
    public async Task Configuracion_Post_Valido_RedirigeIndex()
    {
        var dto = new ActualizarConfiguracionPomodoroDto { TiempoEstudioMin = 30 };

        var resultado = await _controller.Configuracion(dto);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
        _pomodoroMock.Verify(p => p.ActualizarConfiguracion(1, dto), Times.Once);
    }

    [Fact]
    public async Task Configuracion_Post_ModeloInvalido_RetornaVista()
    {
        _controller.ModelState.AddModelError("TiempoEstudioMin", "Requerido");
        var dto = new ActualizarConfiguracionPomodoroDto();

        var resultado = await _controller.Configuracion(dto);

        resultado.Should().BeOfType<ViewResult>();
        _pomodoroMock.Verify(p => p.ActualizarConfiguracion(It.IsAny<int>(), It.IsAny<ActualizarConfiguracionPomodoroDto>()), Times.Never);
    }
}
