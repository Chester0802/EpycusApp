using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.ViewFeatures;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Controladores;

[Trait("Categoria", "Unitario")]
public class BienestarControllerTests
{
    private readonly Mock<IServicioBienestar> _bienestarMock;
    private readonly BienestarController _controller;

    public BienestarControllerTests()
    {
        _bienestarMock = new Mock<IServicioBienestar>();
        _controller = new BienestarController(_bienestarMock.Object);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    [Fact]
    public async Task Index_RetornaVistaConModelo()
    {
        _bienestarMock.Setup(b => b.ObtenerHistorialAnimo(1, 14)).ReturnsAsync(new List<EstadoAnimo>());
        _bienestarMock.Setup(b => b.ObtenerAlertasActivas(1)).ReturnsAsync(new List<AlertaBienestar>());

        var resultado = await _controller.Index();

        var view = resultado.Should().BeOfType<ViewResult>().Subject;
        view.Model.Should().BeOfType<BienestarViewModel>();
    }

    [Fact]
    public async Task RegistrarAnimo_EstadoVacio_RedirigeIndexSinRegistrar()
    {
        var resultado = await _controller.RegistrarAnimo("", null);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
        _bienestarMock.Verify(b => b.RegistrarEstadoAnimo(It.IsAny<int>(), It.IsAny<string>(), It.IsAny<string?>()), Times.Never);
    }

    [Fact]
    public async Task RegistrarAnimo_EstadoValido_RegistraYRedirige()
    {
        _bienestarMock.Setup(b => b.RegistrarEstadoAnimo(1, "Feliz", "nota"))
            .ReturnsAsync((AlertaBienestar?)null);

        var resultado = await _controller.RegistrarAnimo("Feliz", "nota");

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
        _bienestarMock.Verify(b => b.RegistrarEstadoAnimo(1, "Feliz", "nota"), Times.Once);
    }
}
