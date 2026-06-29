using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.DTOs;
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
public class AjustesControllerTests
{
    private readonly Mock<IServicioPerfil> _perfilMock;
    private readonly Mock<IServicioAutenticacion> _authMock;
    private readonly AjustesController _controller;

    public AjustesControllerTests()
    {
        _perfilMock = new Mock<IServicioPerfil>();
        _authMock = new Mock<IServicioAutenticacion>();
        _controller = new AjustesController(_perfilMock.Object, _authMock.Object);

        var claims = new[]
        {
            new Claim(ClaimTypes.NameIdentifier, "1"),
            new Claim(ClaimTypes.Email, "user@test.com")
        };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    [Fact]
    public void Index_RedirigeAPerfil()
    {
        var resultado = _controller.Index();

        var redirect = resultado.Should().BeOfType<RedirectToActionResult>().Subject;
        redirect.ActionName.Should().Be("Index");
        redirect.ControllerName.Should().Be("Perfil");
    }

    [Fact]
    public async Task ActualizarPerfil_ModeloInvalido_NoLlamaServicio()
    {
        _controller.ModelState.AddModelError("Nombre", "Requerido");

        var resultado = await _controller.ActualizarPerfil(new ActualizarPerfilViewModel());

        resultado.Should().BeOfType<RedirectToActionResult>();
        _perfilMock.Verify(p => p.ActualizarPerfilAsync(It.IsAny<int>(), It.IsAny<ActualizarPerfilViewModel>()), Times.Never);
    }

    [Fact]
    public async Task ActualizarPerfil_Exitoso_RedirigeConExito()
    {
        _perfilMock.Setup(p => p.ActualizarPerfilAsync(1, It.IsAny<ActualizarPerfilViewModel>()))
            .ReturnsAsync(RespuestaOperacion.Exitosa());

        var resultado = await _controller.ActualizarPerfil(new ActualizarPerfilViewModel());

        resultado.Should().BeOfType<RedirectToActionResult>();
        _controller.TempData["Exito"].Should().NotBeNull();
    }

    [Fact]
    public async Task CambiarContrasena_Exitoso_RedirigeConExito()
    {
        _authMock.Setup(a => a.CambiarContrasenaAsync("user@test.com", "actual", "nueva"))
            .ReturnsAsync((true, (string?)null));

        var modelo = new CambiarContrasenaViewModel { ContrasenaActual = "actual", NuevaContrasena = "nueva" };
        var resultado = await _controller.CambiarContrasena(modelo);

        resultado.Should().BeOfType<RedirectToActionResult>();
        _controller.TempData["Exito"].Should().NotBeNull();
    }

    [Fact]
    public async Task CambiarPersonaje_RetornaJsonExito()
    {
        _perfilMock.Setup(p => p.ObtenerImagenPersonajeActual(1)).ReturnsAsync("/img/p.png");

        var resultado = await _controller.CambiarPersonaje(5);

        resultado.Should().BeOfType<JsonResult>();
        _perfilMock.Verify(p => p.CambiarPersonaje(5, 1), Times.Once);
    }

    [Fact]
    public async Task CambiarTema_Exitoso_RetornaJson()
    {
        _perfilMock.Setup(p => p.CambiarTemaAsync(1, 2)).ReturnsAsync(RespuestaOperacion.Exitosa());

        var resultado = await _controller.CambiarTema(2);

        resultado.Should().BeOfType<JsonResult>();
    }
}
