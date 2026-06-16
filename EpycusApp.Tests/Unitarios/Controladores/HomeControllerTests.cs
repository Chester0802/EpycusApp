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

public class HomeControllerTests
{
    private readonly Mock<IServicioHabitos> _habitosMock;
    private readonly Mock<IServicioPerfil> _perfilMock;
    private readonly Mock<IServicioBienestar> _bienestarMock;
    private readonly HomeController _controller;

    public HomeControllerTests()
    {
        _habitosMock = new Mock<IServicioHabitos>();
        _perfilMock = new Mock<IServicioPerfil>();
        _bienestarMock = new Mock<IServicioBienestar>();
        _controller = new HomeController(_habitosMock.Object, _perfilMock.Object, _bienestarMock.Object);
    }

    [Fact]
    public async Task Index_NoAutenticado_RedirigeLogin()
    {
        var httpContext = new DefaultHttpContext();
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };

        var resultado = await _controller.Index();

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Login");
    }

    [Fact]
    public async Task Index_Autenticado_RetornaVistaConModelo()
    {
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };

        _perfilMock.Setup(p => p.ObtenerPerfil(1))
            .ReturnsAsync(new Usuario { Nombre = "Test User" });
        _habitosMock.Setup(h => h.ObtenerDashboard(1))
            .ReturnsAsync(new HabitosDashboardViewModel());
        _habitosMock.Setup(h => h.ObtenerHabitosViewModel(1))
            .ReturnsAsync(new List<HabitoViewModel>());
        _bienestarMock.Setup(b => b.ObtenerFraseMotivacionalAleatoria())
            .ReturnsAsync((FraseMotivacional?)null);

        var resultado = await _controller.Index();

        var viewResult = resultado.Should().BeOfType<ViewResult>().Subject;
        viewResult.Model.Should().BeOfType<HomeDashboardViewModel>();
        var modelo = viewResult.Model as HomeDashboardViewModel;
        modelo!.EstaAutenticado.Should().BeTrue();
        modelo.NombreUsuario.Should().Be("Test User");
    }

    [Fact]
    public void Privacy_RetornaVista()
    {
        var resultado = _controller.Privacy();
        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public void Error_RetornaVista()
    {
        var httpContext = new DefaultHttpContext();
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };

        var resultado = _controller.Error();
        resultado.Should().BeOfType<ViewResult>();
    }
}
