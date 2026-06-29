using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.DTOs;
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
public class PerfilControllerTests
{
    private readonly Mock<IServicioPerfil> _perfilMock;
    private readonly Mock<IServicioAutenticacion> _authMock;
    private readonly PerfilController _controller;

    public PerfilControllerTests()
    {
        _perfilMock = new Mock<IServicioPerfil>();
        _authMock = new Mock<IServicioAutenticacion>();
        _controller = new PerfilController(_perfilMock.Object, _authMock.Object);

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
    public async Task Index_PerfilInexistente_RetornaNotFound()
    {
        _perfilMock.Setup(p => p.ObtenerPerfilCompletoAsync(1)).ReturnsAsync((PerfilViewModel?)null);

        var resultado = await _controller.Index();

        resultado.Should().BeOfType<NotFoundResult>();
    }

    [Fact]
    public async Task Index_PerfilExistente_RetornaVista()
    {
        _perfilMock.Setup(p => p.ObtenerPerfilCompletoAsync(1)).ReturnsAsync(new PerfilViewModel());
        _perfilMock.Setup(p => p.ObtenerPersonajesDisponiblesAsync(1)).ReturnsAsync(new List<PersonajePerfilItem>());
        _perfilMock.Setup(p => p.ObtenerLogrosUsuarioConLogroAsync(1)).ReturnsAsync(new List<LogroUsuario>());
        _authMock.Setup(a => a.ObtenerCarrerasActivas()).ReturnsAsync(new List<Carrera>());

        var resultado = await _controller.Index();

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task ActualizarPerfil_ModeloInvalido_RedirigeSinLlamar()
    {
        _controller.ModelState.AddModelError("Nombre", "Requerido");

        var resultado = await _controller.ActualizarPerfil(new ActualizarPerfilViewModel());

        resultado.Should().BeOfType<RedirectToActionResult>();
        _perfilMock.Verify(p => p.ActualizarPerfilAsync(It.IsAny<int>(), It.IsAny<ActualizarPerfilViewModel>()), Times.Never);
    }

    [Fact]
    public async Task CambiarPersonaje_Valido_LlamaServicioYRedirige()
    {
        var resultado = await _controller.CambiarPersonaje(3);

        resultado.Should().BeOfType<RedirectToActionResult>();
        _perfilMock.Verify(p => p.CambiarPersonaje(3, 1), Times.Once);
    }

    [Fact]
    public async Task CambiarTema_DtoNulo_RetornaBadRequest()
    {
        var resultado = await _controller.CambiarTema(null);

        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task CambiarTema_Valido_RetornaOk()
    {
        _perfilMock.Setup(p => p.CambiarTemaAsync(1, 7)).ReturnsAsync(RespuestaOperacion.Exitosa());

        var resultado = await _controller.CambiarTema(new CambiarTemaDto { TemaId = 7 });

        resultado.Should().BeOfType<OkObjectResult>();
    }
}
