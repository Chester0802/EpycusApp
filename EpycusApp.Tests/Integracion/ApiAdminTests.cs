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
public class ApiAdminTests
{
    private readonly Mock<IServicioAdmin> _adminMock;
    private readonly Mock<IServicioAutenticacion> _authMock;
    private readonly ApiAdminController _controller;

    public ApiAdminTests()
    {
        _adminMock = new Mock<IServicioAdmin>();
        _authMock = new Mock<IServicioAutenticacion>();
        _controller = new ApiAdminController(_adminMock.Object, _authMock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "9") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    [Fact]
    public async Task Login_CredencialesInvalidas_RetornaOkFallida()
    {
        _authMock.Setup(a => a.Login("admin@epycus.es", "mala"))
            .ReturnsAsync((false, "Credenciales incorrectas", null, null));

        var resultado = await _controller.Login(new AdminLoginRequest { Correo = "admin@epycus.es", Contrasena = "mala" });

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Login_NoEsAdmin_RetornaOkFallida()
    {
        _authMock.Setup(a => a.Login("user@epycus.es", "Pass1234!"))
            .ReturnsAsync((true, "ok", "token", "refresh"));
        _adminMock.Setup(a => a.EsAdministrador("user@epycus.es")).ReturnsAsync(false);

        var resultado = await _controller.Login(new AdminLoginRequest { Correo = "user@epycus.es", Contrasena = "Pass1234!" });

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Login_AdminValido_RetornaOk()
    {
        _authMock.Setup(a => a.Login("admin@epycus.es", "Admin123@"))
            .ReturnsAsync((true, "ok", "token", "refresh"));
        _adminMock.Setup(a => a.EsAdministrador("admin@epycus.es")).ReturnsAsync(true);

        var resultado = await _controller.Login(new AdminLoginRequest { Correo = "admin@epycus.es", Contrasena = "Admin123@" });

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Usuarios_RetornaOk()
    {
        _adminMock.Setup(a => a.ObtenerTodosUsuarios()).ReturnsAsync(new List<Usuario>());
        (await _controller.Usuarios()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task UsuarioPorId_Inexistente_RetornaNotFound()
    {
        _adminMock.Setup(a => a.ObtenerUsuarioPorId(99)).ReturnsAsync((Usuario?)null);
        (await _controller.UsuarioPorId(99)).Should().BeOfType<NotFoundObjectResult>();
    }

    [Fact]
    public async Task UsuarioPorId_Existente_RetornaOk()
    {
        _adminMock.Setup(a => a.ObtenerUsuarioPorId(3)).ReturnsAsync(new Usuario { Id = 3 });
        (await _controller.UsuarioPorId(3)).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ActivarSuscripcion_RetornaOk()
    {
        var resultado = await _controller.ActivarSuscripcion(7);
        resultado.Should().BeOfType<OkObjectResult>();
        _adminMock.Verify(a => a.ActivarSuscripcion(7, 9), Times.Once);
    }

    [Fact]
    public async Task DesactivarSuscripcion_RetornaOk()
    {
        var resultado = await _controller.DesactivarSuscripcion(7);
        resultado.Should().BeOfType<OkObjectResult>();
        _adminMock.Verify(a => a.DesactivarSuscripcion(7), Times.Once);
    }

    [Fact]
    public async Task Frases_RetornaOk()
    {
        _adminMock.Setup(a => a.ObtenerFrases()).ReturnsAsync(new List<FraseMotivacional>());
        (await _controller.Frases()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task CrearFrase_RetornaCreated()
    {
        var resultado = await _controller.CrearFrase(new CrearFraseRequest { Frase = "Animo", Autor = "A" });
        resultado.Should().BeOfType<CreatedResult>();
        _adminMock.Verify(a => a.CrearFrase("Animo", "A"), Times.Once);
    }

    [Fact]
    public async Task EliminarFrase_RetornaOk()
    {
        var resultado = await _controller.EliminarFrase(4);
        resultado.Should().BeOfType<OkObjectResult>();
        _adminMock.Verify(a => a.EliminarFrase(4), Times.Once);
    }
}
