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
public class ApiDashboardTests
{
    private readonly Mock<IServicioBienestar> _bienestarMock;
    private readonly ApiDashboardController _controller;

    public ApiDashboardTests()
    {
        _bienestarMock = new Mock<IServicioBienestar>();
        _controller = new ApiDashboardController(_bienestarMock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    [Fact]
    public async Task Resumen_RetornaOk()
    {
        _bienestarMock.Setup(b => b.ObtenerHabitosPendientesAsync(1)).ReturnsAsync(3);
        _bienestarMock.Setup(b => b.ObtenerMisionesPendientesAsync(1)).ReturnsAsync(2);
        _bienestarMock.Setup(b => b.ObtenerFraseMotivacionalAleatoria())
            .ReturnsAsync(new FraseMotivacional { Frase = "Vamos", Autor = "A" });

        var resultado = await _controller.Resumen();

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task FraseDelDia_SinFrase_RetornaOk()
    {
        _bienestarMock.Setup(b => b.ObtenerFraseMotivacionalAleatoria())
            .ReturnsAsync((FraseMotivacional?)null);

        var resultado = await _controller.FraseDelDia();

        resultado.Should().BeOfType<OkObjectResult>();
    }
}
