using System.Security.Claims;
using EpycusApp.Controllers.Api;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Integracion;

[Trait("Categoria", "Integracion")]
public class ApiPerfilTests
{
    private readonly Mock<IServicioPerfil> _perfilMock;
    private readonly Mock<IServicioAutenticacion> _authMock;
    private readonly ApiPerfilController _controller;

    public ApiPerfilTests()
    {
        _perfilMock = new Mock<IServicioPerfil>();
        _authMock = new Mock<IServicioAutenticacion>();
        _controller = new ApiPerfilController(_perfilMock.Object, _authMock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    [Fact]
    public async Task Obtener_PerfilNulo_RetornaNotFound()
    {
        _perfilMock.Setup(p => p.ObtenerPerfilCompletoAsync(1)).ReturnsAsync((PerfilViewModel?)null);
        (await _controller.Obtener()).Should().BeOfType<NotFoundObjectResult>();
    }

    [Fact]
    public async Task Obtener_PerfilExistente_RetornaOk()
    {
        _perfilMock.Setup(p => p.ObtenerPerfilCompletoAsync(1)).ReturnsAsync(new PerfilViewModel());
        _perfilMock.Setup(p => p.ObtenerImagenPersonajeActual(1)).ReturnsAsync("/img/a.png");
        (await _controller.Obtener()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Actualizar_Exitoso_RetornaOk()
    {
        _perfilMock.Setup(p => p.ActualizarPerfilAsync(1, It.IsAny<ActualizarPerfilViewModel>()))
            .ReturnsAsync(RespuestaOperacion.Exitosa("ok"));

        var resultado = await _controller.Actualizar(new ApiPerfilController.ActualizarRequestDto { Nombre = "Nuevo" });

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Actualizar_Fallo_RetornaBadRequest()
    {
        _perfilMock.Setup(p => p.ActualizarPerfilAsync(1, It.IsAny<ActualizarPerfilViewModel>()))
            .ReturnsAsync(RespuestaOperacion.Fallo("no"));

        var resultado = await _controller.Actualizar(new ApiPerfilController.ActualizarRequestDto());

        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task CambiarContrasena_UsuarioNulo_RetornaNotFound()
    {
        _perfilMock.Setup(p => p.ObtenerPerfil(1)).ReturnsAsync((Usuario?)null);

        var resultado = await _controller.CambiarContrasena(new ApiPerfilController.CambiarContrasenaRequestDto());

        resultado.Should().BeOfType<NotFoundObjectResult>();
    }

    [Fact]
    public async Task CambiarContrasena_Exitoso_RetornaOk()
    {
        _perfilMock.Setup(p => p.ObtenerPerfil(1)).ReturnsAsync(new Usuario { CorreoElectronico = "u@test.com" });
        _authMock.Setup(a => a.CambiarContrasenaAsync("u@test.com", "a", "b")).ReturnsAsync((true, "ok"));

        var resultado = await _controller.CambiarContrasena(new ApiPerfilController.CambiarContrasenaRequestDto
        {
            ContrasenaActual = "a", NuevaContrasena = "b"
        });

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task CambiarPersonaje_RetornaOk()
    {
        _perfilMock.Setup(p => p.ObtenerImagenPersonajeActual(1)).ReturnsAsync("/img/x.png");

        var resultado = await _controller.CambiarPersonaje(new ApiPerfilController.PersonajeRequestDto { PersonajeId = 2 });

        resultado.Should().BeOfType<OkObjectResult>();
        _perfilMock.Verify(p => p.CambiarPersonaje(2, 1), Times.Once);
    }

    [Fact]
    public async Task CambiarTema_Fallo_RetornaBadRequest()
    {
        _perfilMock.Setup(p => p.CambiarTemaAsync(1, 9)).ReturnsAsync(RespuestaOperacion.Fallo("no"));

        var resultado = await _controller.CambiarTema(new ApiPerfilController.TemaRequestDto { TemaId = 9 });

        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task Personajes_RetornaOk()
    {
        _perfilMock.Setup(p => p.ObtenerPersonajesDisponiblesAsync(1)).ReturnsAsync(new List<PersonajePerfilItem>());
        (await _controller.Personajes()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Logros_RetornaOk()
    {
        _perfilMock.Setup(p => p.ObtenerLogrosUsuarioConLogroAsync(1)).ReturnsAsync(new List<LogroUsuario>());
        (await _controller.Logros()).Should().BeOfType<OkObjectResult>();
    }
}
