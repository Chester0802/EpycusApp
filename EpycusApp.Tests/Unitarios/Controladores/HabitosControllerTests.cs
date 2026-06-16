using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.ViewFeatures;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Controladores;

public class HabitosControllerTests
{
    private readonly Mock<IServicioHabitos> _habitosMock;
    private readonly Mock<ILogger<HabitosController>> _loggerMock;
    private readonly HabitosController _controller;

    public HabitosControllerTests()
    {
        _habitosMock = new Mock<IServicioHabitos>();
        _loggerMock = new Mock<ILogger<HabitosController>>();
        _controller = new HabitosController(_habitosMock.Object, _loggerMock.Object);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    [Fact]
    public async Task Index_RetornaVistaConModelo()
    {
        _habitosMock.Setup(h => h.ObtenerHabitosViewModel(1))
            .ReturnsAsync(new List<HabitoViewModel>());
        _habitosMock.Setup(h => h.ObtenerDashboard(1))
            .ReturnsAsync(new HabitosDashboardViewModel());
        _habitosMock.Setup(h => h.ObtenerCategoriasActivas())
            .ReturnsAsync(new List<Categoria>());

        var resultado = await _controller.Index();

        var viewResult = resultado.Should().BeOfType<ViewResult>().Subject;
        viewResult.Model.Should().BeOfType<HabitosIndexViewModel>();
    }

    [Fact]
    public async Task Crear_Get_RetornaVista()
    {
        _habitosMock.Setup(h => h.ObtenerCategoriasActivas())
            .ReturnsAsync(new List<Categoria>());

        var resultado = await _controller.Crear();

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task Eliminar_Post_RedirigeIndex()
    {
        _habitosMock.Setup(h => h.EliminarHabito(1, 1))
            .Returns(Task.CompletedTask);

        var resultado = await _controller.Eliminar(1);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
    }

    [Fact]
    public async Task Editar_Get_Inexistente_RetornaNotFound()
    {
        _habitosMock.Setup(h => h.ObtenerPorId(999))
            .ReturnsAsync((Habito?)null);

        var resultado = await _controller.Editar(999);

        resultado.Should().BeOfType<NotFoundResult>();
    }

    [Fact]
    public async Task Editar_Get_DeOtroUsuario_RetornaNotFound()
    {
        _habitosMock.Setup(h => h.ObtenerPorId(1))
            .ReturnsAsync(new Habito { Id = 1, UsuarioId = 999, Nombre = "Test", Frecuencia = "Diaria" });

        var resultado = await _controller.Editar(1);

        resultado.Should().BeOfType<NotFoundResult>();
    }
}
