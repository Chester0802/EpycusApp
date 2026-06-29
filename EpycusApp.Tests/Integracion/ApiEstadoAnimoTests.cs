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
public class ApiEstadoAnimoTests
{
    private readonly Mock<IServicioBienestar> _bienestarMock;
    private readonly ApiEstadoAnimoController _controller;

    public ApiEstadoAnimoTests()
    {
        _bienestarMock = new Mock<IServicioBienestar>();
        _controller = new ApiEstadoAnimoController(_bienestarMock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    [Fact]
    public async Task Registrar_EstadoPermitido_RetornaOk()
    {
        _bienestarMock.Setup(b => b.RegistrarEstadoAnimo(1, "Bien", null)).ReturnsAsync((AlertaBienestar?)null);

        var resultado = await _controller.Registrar(new ApiEstadoAnimoController.EstadoAnimoDto { Estado = "Bien" });

        resultado.Should().BeOfType<OkObjectResult>();
        _bienestarMock.Verify(b => b.RegistrarEstadoAnimo(1, "Bien", null), Times.Once);
    }

    [Fact]
    public async Task Registrar_EstadoNoPermitido_RetornaBadRequest()
    {
        var resultado = await _controller.Registrar(new ApiEstadoAnimoController.EstadoAnimoDto { Estado = "Eufórico" });

        resultado.Should().BeOfType<BadRequestObjectResult>();
        _bienestarMock.Verify(b => b.RegistrarEstadoAnimo(It.IsAny<int>(), It.IsAny<string>(), It.IsAny<string?>()), Times.Never);
    }

    [Fact]
    public async Task Registrar_ModeloInvalido_RetornaBadRequest()
    {
        _controller.ModelState.AddModelError("Estado", "Requerido");

        var resultado = await _controller.Registrar(new ApiEstadoAnimoController.EstadoAnimoDto());

        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task Historial_RetornaOk()
    {
        _bienestarMock.Setup(b => b.ObtenerHistorialAnimoCompletoAsync(1)).ReturnsAsync(new List<EstadoAnimo>());

        var resultado = await _controller.Historial();

        resultado.Should().BeOfType<OkObjectResult>();
    }
}
