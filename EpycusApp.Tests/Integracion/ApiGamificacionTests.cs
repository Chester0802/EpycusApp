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
public class ApiGamificacionTests
{
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private readonly Mock<IServicioProgreso> _progresoMock;
    private readonly ApiGamificacionController _controller;

    public ApiGamificacionTests()
    {
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _progresoMock = new Mock<IServicioProgreso>();
        _controller = new ApiGamificacionController(_gamificacionMock.Object, _progresoMock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    [Fact]
    public async Task ObtenerProgreso_RetornaOk()
    {
        _progresoMock.Setup(p => p.ObtenerProgreso(1)).ReturnsAsync(new ProgresoUsuario
        {
            UsuarioId = 1,
            XpTotal = 120,
            RachaActual = 3,
            NivelActual = new Nivel { Numero = 2, Titulo = "Aprendiz" }
        });
        _progresoMock.Setup(p => p.ObtenerImagenPersonaje(1, 2)).ReturnsAsync("/img/a.png");

        var resultado = await _controller.ObtenerProgreso();

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ObtenerLogros_RetornaOk()
    {
        _progresoMock.Setup(p => p.ObtenerTodosLosLogros()).ReturnsAsync(new List<Logro>
        {
            new() { Id = 1, Nombre = "Primero", CondicionValor = 1 }
        });
        _progresoMock.Setup(p => p.ObtenerLogrosUsuario(1)).ReturnsAsync(new List<LogroUsuario>());

        var resultado = await _controller.ObtenerLogros();

        resultado.Should().BeOfType<OkObjectResult>();
    }
}
