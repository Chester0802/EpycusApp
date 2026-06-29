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
public class ApiProgresoTests
{
    private readonly Mock<IServicioProgreso> _progresoMock;
    private readonly ApiProgresoController _controller;

    public ApiProgresoTests()
    {
        _progresoMock = new Mock<IServicioProgreso>();
        _controller = new ApiProgresoController(_progresoMock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    private void SetupProgreso() =>
        _progresoMock.Setup(p => p.ObtenerProgreso(1)).ReturnsAsync(new ProgresoUsuario
        {
            UsuarioId = 1,
            XpTotal = 200,
            NivelActual = new Nivel { Numero = 2, Titulo = "Aprendiz" }
        });

    [Fact]
    public async Task Obtener_RetornaOk()
    {
        SetupProgreso();
        _progresoMock.Setup(p => p.ObtenerNivelSiguiente(2)).ReturnsAsync(new Nivel { Numero = 3, Titulo = "Experto" });

        var resultado = await _controller.Obtener();

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Logros_RetornaOk()
    {
        _progresoMock.Setup(p => p.ObtenerLogrosUsuario(1)).ReturnsAsync(new List<LogroUsuario>());

        var resultado = await _controller.Logros();

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task HistorialAnimo_RetornaOk()
    {
        _progresoMock.Setup(p => p.ObtenerHistorialAnimo(1)).ReturnsAsync(new List<EstadoAnimo>());

        var resultado = await _controller.HistorialAnimo();

        resultado.Should().BeOfType<OkObjectResult>();
    }
}
