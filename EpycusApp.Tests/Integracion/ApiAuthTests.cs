using System.Security.Claims;
using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Controllers.Api;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Integracion;

public class ApiAuthTests : IDisposable
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioAutenticacion _servicio;
    private readonly ApiAuthController _controller;
    private readonly Mock<IServicioCorreo> _correoMock;
    private readonly Mock<ILogger<ServicioAutenticacion>> _loggerMock;
    private readonly Mock<IServicioAuditoria> _auditoriaMock;

    public ApiAuthTests()
    {
        _contexto = DbContextFactory.CrearContexto("AuthIntegracion");
        _correoMock = new Mock<IServicioCorreo>();
        _loggerMock = new Mock<ILogger<ServicioAutenticacion>>();
        _auditoriaMock = new Mock<IServicioAuditoria>();

        var configData = new Dictionary<string, string?>
        {
            ["Jwt:Clave"] = "ClaveSuperSeguraParaTests1234567890!!",
            ["Jwt:Emisor"] = "EpycusTest",
            ["Jwt:Audiencia"] = "EpycusTestAudience"
        };
        var config = new ConfigurationBuilder().AddInMemoryCollection(configData).Build();

        _servicio = new ServicioAutenticacion(_contexto, config, _correoMock.Object, _auditoriaMock.Object, _loggerMock.Object);

        var verificadorTurnstile = new EpycusApp.Ayudantes.VerificadorTurnstile(
            new HttpClient(),
            Microsoft.Extensions.Options.Options.Create(new EpycusApp.Ayudantes.TurnstileOptions()));
        _controller = new ApiAuthController(_servicio, verificadorTurnstile);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "0") };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };

        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        _contexto.SaveChangesAsync().GetAwaiter().GetResult();
    }

    public void Dispose()
    {
        _contexto.Dispose();
    }

    [Fact]
    public async Task Registro_ConDatosValidos_RetornaOkConToken()
    {
        var dto = new ApiAuthController.RegistroRequestDto
        {
            Nombre = "Test User",
            CorreoElectronico = $"test_{Guid.NewGuid():N}@test.com",
            Contrasena = "Test1234!",
            ConfirmarContrasena = "Test1234!",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            CarreraId = 1,
            AceptoTerminos = true,
            TurnstileToken = "test-token"
        };

        var result = await _controller.Registro(dto);

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<AuthResponseDto>>(okResult.Value);
        Assert.True(apiResp.Exito);
        Assert.NotNull(apiResp.Datos?.Token);
    }

    [Fact]
    public async Task Registro_CorreoDuplicado_RetornaBadRequest()
    {
        var correo = $"dup_{Guid.NewGuid():N}@test.com";
        var dto = new ApiAuthController.RegistroRequestDto
        {
            Nombre = "First",
            CorreoElectronico = correo,
            Contrasena = "Test1234!",
            ConfirmarContrasena = "Test1234!",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            CarreraId = 1,
            AceptoTerminos = true,
            TurnstileToken = "test-token"
        };

        await _controller.Registro(dto);

        var result = await _controller.Registro(dto);

        var badResult = Assert.IsType<BadRequestObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<MensajeResponseDto>>(badResult.Value);
        Assert.False(apiResp.Exito);
    }

    [Fact]
    public async Task Login_ConCredencialesValidas_RetornaOkConToken()
    {
        var correo = $"login_{Guid.NewGuid():N}@test.com";
        _contexto.Usuarios.Add(new Usuario
        {
            CodigoUnico = "LOGIN_" + Guid.NewGuid().ToString()[..6],
            Nombre = "Login Test",
            CorreoElectronico = correo,
            ContrasenaHash = BCrypt.Net.BCrypt.HashPassword("Pass1234!"),
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            RolId = 1,
            CarreraId = 1,
            CorreoVerificado = true
        });
        await _contexto.SaveChangesAsync();

        var result = await _controller.Login(new ApiAuthController.LoginDto
        {
            Correo = correo,
            Contrasena = "Pass1234!"
        });

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<AuthResponseDto>>(okResult.Value);
        Assert.True(apiResp.Exito);
        Assert.NotNull(apiResp.Datos?.Token);
    }

    [Fact]
    public async Task Login_CredencialesInvalidas_RetornaBadRequest()
    {
        var result = await _controller.Login(new ApiAuthController.LoginDto
        {
            Correo = "noexiste@test.com",
            Contrasena = "wrong"
        });

        var badResult = Assert.IsType<BadRequestObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<MensajeResponseDto>>(badResult.Value);
        Assert.False(apiResp.Exito);
    }

    [Fact]
    public async Task ObtenerCarreras_RetornaLista()
    {
        var result = await _controller.Carreras();

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<List<Carrera>>>(okResult.Value);
        Assert.True(apiResp.Exito);
        Assert.NotNull(apiResp.Datos);
        Assert.NotEmpty(apiResp.Datos);
    }
}
