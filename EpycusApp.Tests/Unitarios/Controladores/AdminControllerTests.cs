using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Admin;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.ViewFeatures;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Controladores;

[Trait("Categoria", "Unitario")]
public class AdminControllerTests
{
    private readonly Mock<IServicioAdmin> _adminMock;
    private readonly Mock<IServicioAutenticacion> _authMock;
    private readonly AdminController _controller;

    public AdminControllerTests()
    {
        _adminMock = new Mock<IServicioAdmin>();
        _authMock = new Mock<IServicioAutenticacion>();
        _controller = new AdminController(_adminMock.Object, _authMock.Object);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "9") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    [Fact]
    public void Login_Get_NoAutenticado_RetornaVista()
    {
        _controller.Login().Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task Login_Post_ModeloInvalido_RetornaVista()
    {
        _controller.ModelState.AddModelError("CorreoElectronico", "Requerido");

        var resultado = await _controller.Login(new AdminLoginViewModel());

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task Login_Post_CredencialesInvalidas_RetornaVistaConError()
    {
        _authMock.Setup(a => a.Login("admin@epycus.es", "mala"))
            .ReturnsAsync((false, "Credenciales incorrectas", null, null));

        var modelo = new AdminLoginViewModel { CorreoElectronico = "admin@epycus.es", Contrasena = "mala" };
        var resultado = await _controller.Login(modelo);

        resultado.Should().BeOfType<ViewResult>();
        _controller.ModelState[string.Empty]!.Errors.Should().NotBeEmpty();
    }

    [Fact]
    public async Task Login_Post_Exitoso_RedirigeIndex()
    {
        _authMock.Setup(a => a.Login("admin@epycus.es", "Admin123@"))
            .ReturnsAsync((true, "ok", "token-jwt", "refresh"));

        var modelo = new AdminLoginViewModel { CorreoElectronico = "admin@epycus.es", Contrasena = "Admin123@" };
        var resultado = await _controller.Login(modelo);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
    }

    [Fact]
    public void Logout_RedirigeLogin()
    {
        _controller.Logout().Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Login");
    }

    [Fact]
    public async Task Index_RetornaDashboard()
    {
        _adminMock.Setup(a => a.ObtenerTodosUsuarios()).ReturnsAsync(new List<Usuario>
        {
            new() { Nombre = "U1", EstaActivo = true, Suscripciones = new List<Suscripcion>() }
        });
        _adminMock.Setup(a => a.ObtenerFrases()).ReturnsAsync(new List<FraseMotivacional>());

        var resultado = await _controller.Index();

        var view = resultado.Should().BeOfType<ViewResult>().Subject;
        view.Model.Should().BeOfType<AdminDashboardViewModel>();
    }

    [Fact]
    public async Task Usuarios_RetornaVistaConLista()
    {
        _adminMock.Setup(a => a.ObtenerTodosUsuarios()).ReturnsAsync(new List<Usuario>());

        var resultado = await _controller.Usuarios();

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task DetalleUsuario_Inexistente_RetornaNotFound()
    {
        _adminMock.Setup(a => a.ObtenerUsuarioPorId(99)).ReturnsAsync((Usuario?)null);

        var resultado = await _controller.DetalleUsuario(99);

        resultado.Should().BeOfType<NotFoundResult>();
    }

    [Fact]
    public async Task DetalleUsuario_Existente_RetornaVista()
    {
        _adminMock.Setup(a => a.ObtenerUsuarioPorId(3)).ReturnsAsync(new Usuario { Id = 3, Nombre = "U" });

        var resultado = await _controller.DetalleUsuario(3);

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task ActivarSuscripcion_LlamaServicioYRedirigeUsuarios()
    {
        var resultado = await _controller.ActivarSuscripcion(7);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Usuarios");
        _adminMock.Verify(a => a.ActivarSuscripcion(7, 9), Times.Once);
    }

    [Fact]
    public async Task DesactivarSuscripcion_RedirigeUsuarios()
    {
        var resultado = await _controller.DesactivarSuscripcion(7);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Usuarios");
        _adminMock.Verify(a => a.DesactivarSuscripcion(7), Times.Once);
    }

    [Fact]
    public async Task CrearFrase_LlamaServicioYRedirigeFrases()
    {
        var resultado = await _controller.CrearFrase("Animo", "Anonimo");

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Frases");
        _adminMock.Verify(a => a.CrearFrase("Animo", "Anonimo"), Times.Once);
    }

    [Fact]
    public async Task EliminarFrase_RedirigeFrases()
    {
        var resultado = await _controller.EliminarFrase(4);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Frases");
        _adminMock.Verify(a => a.EliminarFrase(4), Times.Once);
    }

    [Fact]
    public async Task Frases_RetornaVista()
    {
        _adminMock.Setup(a => a.ObtenerFrases()).ReturnsAsync(new List<FraseMotivacional>());

        var resultado = await _controller.Frases();

        resultado.Should().BeOfType<ViewResult>();
    }
}
