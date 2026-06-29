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
public class DiarioAnimoControllerTests
{
    private readonly Mock<IServicioDiarioAnimo> _diarioMock;
    private readonly DiarioAnimoController _controller;

    public DiarioAnimoControllerTests()
    {
        _diarioMock = new Mock<IServicioDiarioAnimo>();
        _controller = new DiarioAnimoController(_diarioMock.Object);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    [Fact]
    public async Task Index_RetornaVistaConModelo()
    {
        _diarioMock.Setup(d => d.ObtenerEntradasMes(1, It.IsAny<int>(), It.IsAny<int>()))
            .ReturnsAsync(new List<EntradaDiario>());
        _diarioMock.Setup(d => d.ObtenerPreguntaGuia()).Returns("¿Qué aprendiste hoy?");

        var resultado = await _controller.Index(null, null);

        var view = resultado.Should().BeOfType<ViewResult>().Subject;
        view.Model.Should().BeOfType<DiarioAnimoViewModel>();
    }

    [Fact]
    public async Task Registrar_Valido_RegistraYRedirige()
    {
        _diarioMock.Setup(d => d.ObtenerPreguntaGuia()).Returns("P");
        var model = new RegistrarEntradaDiarioViewModel { EstadoAnimo = 3, NivelEnergia = 3 };

        var resultado = await _controller.Registrar(model);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
        _diarioMock.Verify(d => d.RegistrarEntrada(1, model, "P"), Times.Once);
    }

    [Fact]
    public async Task Registrar_ModeloInvalido_NoRegistra()
    {
        _controller.ModelState.AddModelError("EstadoAnimo", "Requerido");

        var resultado = await _controller.Registrar(new RegistrarEntradaDiarioViewModel());

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
        _diarioMock.Verify(d => d.RegistrarEntrada(It.IsAny<int>(), It.IsAny<RegistrarEntradaDiarioViewModel>(), It.IsAny<string>()), Times.Never);
    }

    [Fact]
    public void NavegarMes_RedirigeIndexConRuta()
    {
        var resultado = _controller.NavegarMes(1);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
    }
}
