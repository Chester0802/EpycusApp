using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using EpycusApp.ViewModels.Autenticacion;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.ViewFeatures;
using Moq;

namespace EpycusApp.Tests.Unitarios.Controladores;

public class AutenticacionControllerTests
{
    private readonly Mock<IServicioAutenticacion> _authMock;
    private readonly AutenticacionController _controller;

    public AutenticacionControllerTests()
    {
        _authMock = new Mock<IServicioAutenticacion>();
        _controller = new AutenticacionController(_authMock.Object);

        var httpContext = new DefaultHttpContext();
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    [Fact]
    public async Task Registro_Get_RetornaVista()
    {
        _authMock.Setup(a => a.ObtenerCarrerasActivas())
            .ReturnsAsync(new List<Carrera>());

        var resultado = await _controller.Registro();

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task Registro_Post_ModeloInvalido_RetornaVista()
    {
        _controller.ModelState.AddModelError("Nombre", "Requerido");
        _authMock.Setup(a => a.ObtenerCarrerasActivas())
            .ReturnsAsync(new List<Carrera>());

        var resultado = await _controller.Registro(new RegistroViewModel());

        var viewResult = resultado.Should().BeOfType<ViewResult>().Subject;
        viewResult.Model.Should().BeOfType<RegistroViewModel>();
    }

    [Fact]
    public async Task Registro_Post_Exitoso_RedirigeAlHome()
    {
        _authMock.Setup(a => a.RegistrarUsuario(It.IsAny<RegistroViewModel>()))
            .ReturnsAsync((true, "Exito", "token123", "refresh123"));

        var modelo = new RegistroViewModel();
        var resultado = await _controller.Registro(modelo);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ControllerName.Should().Be("Home");
    }

    [Fact]
    public async Task Registro_Post_Fallo_RetornaVistaConError()
    {
        _authMock.Setup(a => a.RegistrarUsuario(It.IsAny<RegistroViewModel>()))
            .ReturnsAsync((false, "El correo ya está registrado", null, null));
        _authMock.Setup(a => a.ObtenerCarrerasActivas())
            .ReturnsAsync(new List<Carrera>());

        var resultado = await _controller.Registro(new RegistroViewModel());

        var viewResult = resultado.Should().BeOfType<ViewResult>().Subject;
        _controller.ModelState[string.Empty]?.Errors.Should().Contain(e => e.ErrorMessage == "El correo ya está registrado");
    }

    [Fact]
    public void Login_Get_NoAutenticado_RetornaVista()
    {
        var resultado = _controller.Login();

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public void Login_Get_Autenticado_RedirigeAlHome()
    {
        var claims = new[] { new Claim(ClaimTypes.Name, "test") };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        _controller.ControllerContext = new ControllerContext
        {
            HttpContext = new DefaultHttpContext { User = principal }
        };

        var resultado = _controller.Login();

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
    }

    [Fact]
    public async Task Login_Post_CredencialesInvalidas_RetornaVista()
    {
        _authMock.Setup(a => a.Login("test@test.com", "wrong"))
            .ReturnsAsync((false, "Credenciales incorrectas", null, null));

        var modelo = new LoginViewModel { CorreoElectronico = "test@test.com", Contrasena = "wrong" };
        var resultado = await _controller.Login(modelo);

        resultado.Should().BeOfType<ViewResult>();
        _controller.ModelState[string.Empty]?.Errors.Should().Contain(e => e.ErrorMessage == "Credenciales incorrectas");
    }

    [Fact]
    public void RecuperarContrasena_Get_RetornaVista()
    {
        var resultado = _controller.RecuperarContrasena();
        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task RecuperarContrasena_Post_Redirige()
    {
        _authMock.Setup(a => a.EnviarCorreoRecuperacion("test@test.com"))
            .ReturnsAsync(true);

        var modelo = new RecuperarContrasenaViewModel { CorreoElectronico = "test@test.com" };
        var resultado = await _controller.RecuperarContrasena(modelo);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be(nameof(AutenticacionController.RecuperarContrasena));
    }

    [Fact]
    public void RestablecerContrasena_Get_SinToken_RedirigeLogin()
    {
        var resultado = _controller.RestablecerContrasena("");

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be(nameof(AutenticacionController.Login));
    }

    [Fact]
    public void RestablecerContrasena_Get_ConToken_RetornaVista()
    {
        var resultado = _controller.RestablecerContrasena("token-valido");

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public void Logout_EliminaCookies_RedirigeLogin()
    {
        var resultado = _controller.Logout();

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Login");
    }
}
