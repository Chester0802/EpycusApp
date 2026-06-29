using System.Security.Claims;
using EpycusApp.Controllers.Api;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Integracion;

[Trait("Categoria", "Integracion")]
public class ApiBienestarTests
{
    private readonly Mock<IServicioBienestar> _mock;
    private readonly ApiBienestarController _controller;

    public ApiBienestarTests()
    {
        _mock = new Mock<IServicioBienestar>();
        _mock.Setup(b => b.ObtenerAlertasActivas(It.IsAny<int>())).ReturnsAsync(new List<AlertaBienestar>());
        _mock.Setup(b => b.ObtenerHistorialAnimo(It.IsAny<int>(), It.IsAny<int>())).ReturnsAsync(new List<EstadoAnimo>());
        _controller = new ApiBienestarController(_mock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    [Fact]
    public async Task ObtenerResumen_RetornaOk()
        => (await _controller.ObtenerResumen()).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task ObtenerAlertas_RetornaOk()
        => (await _controller.ObtenerAlertas()).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task ObtenerFrase_RetornaOk()
        => (await _controller.ObtenerFrase()).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task ObtenerEstadoHoy_RetornaOk()
        => (await _controller.ObtenerEstadoHoy()).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task ObtenerHistorialAnimo_RetornaOk()
        => (await _controller.ObtenerHistorialAnimo(30)).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task ObtenerHabitosPendientes_RetornaOk()
    {
        _mock.Setup(b => b.ObtenerHabitosPendientesAsync(1)).ReturnsAsync(4);
        (await _controller.ObtenerHabitosPendientes()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ObtenerMisionesPendientes_RetornaOk()
    {
        _mock.Setup(b => b.ObtenerMisionesPendientesAsync(1)).ReturnsAsync(2);
        (await _controller.ObtenerMisionesPendientes()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public void ObtenerPausaActiva_Valido_RetornaOk()
    {
        var resultado = _controller.ObtenerPausaActiva(new ApiBienestarController.PausaActivaDto { CiclosCompletados = 4 });
        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public void ObtenerPausaActiva_ModeloInvalido_RetornaBadRequest()
    {
        _controller.ModelState.AddModelError("CiclosCompletados", "X");
        var resultado = _controller.ObtenerPausaActiva(new ApiBienestarController.PausaActivaDto());
        resultado.Should().BeOfType<BadRequestObjectResult>();
    }
}
