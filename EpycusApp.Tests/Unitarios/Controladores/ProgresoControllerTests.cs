using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Moq;

namespace EpycusApp.Tests.Unitarios.Controladores;

public class ProgresoControllerTests
{
    private readonly Mock<IServicioProgreso> _progresoMock;
    private readonly ProgresoController _controller;

    public ProgresoControllerTests()
    {
        _progresoMock = new Mock<IServicioProgreso>();
        _controller = new ProgresoController(_progresoMock.Object);
    }

    [Fact]
    public async Task Index_NoAutenticado_RetornaVistaConValoresDefault()
    {
        var httpContext = new DefaultHttpContext();
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };

        _progresoMock.Setup(p => p.ObtenerNivelInicialAsync())
            .ReturnsAsync(new Nivel { Id = 1, Numero = 1, Titulo = "Novato", Descripcion = "Inicio" });
        _progresoMock.Setup(p => p.ObtenerNivelSiguiente(1))
            .ReturnsAsync((Nivel?)null);
        _progresoMock.Setup(p => p.ObtenerTodosLosLogros())
            .ReturnsAsync(new List<Logro>());

        var resultado = await _controller.Index();

        var viewResult = resultado.Should().BeOfType<ViewResult>().Subject;
        viewResult.Model.Should().BeOfType<ProgresoViewModel>();
        var modelo = viewResult.Model as ProgresoViewModel;
        modelo!.Progreso.UsuarioId.Should().Be(0);
    }

    [Fact]
    public async Task Index_Autenticado_RetornaVistaConProgreso()
    {
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };

        _progresoMock.Setup(p => p.ObtenerProgreso(1))
            .ReturnsAsync(new ProgresoUsuario
            {
                UsuarioId = 1,
                XpTotal = 100,
                RachaActual = 5,
                RachaMaxima = 10,
                NivelActual = new Nivel { Numero = 2, Titulo = "Aprendiz", XpRequerido = 150 },
                NivelActualId = 2
            });
        _progresoMock.Setup(p => p.ObtenerNivelSiguiente(2))
            .ReturnsAsync(new Nivel { Numero = 3, Titulo = "Experto", XpRequerido = 350 });
        _progresoMock.Setup(p => p.ObtenerTodosLosLogros())
            .ReturnsAsync(new List<Logro>());
        _progresoMock.Setup(p => p.ObtenerLogrosUsuario(1))
            .ReturnsAsync(new List<LogroUsuario>());
        _progresoMock.Setup(p => p.ObtenerImagenPersonaje(1, 2))
            .ReturnsAsync("https://example.com/avatar.png");

        var resultado = await _controller.Index();

        var viewResult = resultado.Should().BeOfType<ViewResult>().Subject;
        var modelo = viewResult.Model as ProgresoViewModel;
        modelo.Should().NotBeNull();
        modelo!.Progreso.XpTotal.Should().Be(100);
        modelo.Progreso.RachaActual.Should().Be(5);
    }
}
