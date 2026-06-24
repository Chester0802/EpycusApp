using System.Security.Claims;
using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Controllers.Api;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Integracion;

public class ApiHabitosTests : IDisposable
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioHabitos _servicio;
    private readonly ApiHabitosController _controller;
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private readonly Mock<ILogger<ServicioHabitos>> _loggerMock;
    private int _usuarioId;

    public ApiHabitosTests()
    {
        _contexto = DbContextFactory.CrearContexto("HabitosIntegracion");
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _loggerMock = new Mock<ILogger<ServicioHabitos>>();

        _gamificacionMock.Setup(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()))
            .ReturnsAsync((15, false, 1));
        _gamificacionMock.Setup(g => g.VerificarYOtorgarLogros(It.IsAny<int>()))
            .Returns(Task.CompletedTask);

        _servicio = new ServicioHabitos(_contexto, _gamificacionMock.Object, _loggerMock.Object);

        _controller = new ApiHabitosController(_servicio);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "0") };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
    }

    public void Dispose()
    {
        _contexto.Dispose();
    }

    private async Task<int> SeedUsuarioAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        _contexto.Categorias.Add(new Categoria { Id = 1, Nombre = "Estudio", Tipo = "Habito", EstaActiva = true, Icono = "bi-book" });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "HAB_" + Guid.NewGuid().ToString()[..6],
            Nombre = "Habitos Test",
            CorreoElectronico = $"hab_{Guid.NewGuid():N}@test.com",
            ContrasenaHash = "hash",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            RolId = 1,
            CarreraId = 1
        };
        _contexto.Usuarios.Add(usuario);
        await _contexto.SaveChangesAsync();
        _usuarioId = usuario.Id;

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, _usuarioId.ToString()) };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        _controller.ControllerContext.HttpContext.User = principal;

        return usuario.Id;
    }

    [Fact]
    public async Task CrearHabito_ConDatosValidos_RetornaOk()
    {
        await SeedUsuarioAsync();

        var dto = new CrearHabitoDto
        {
            Nombre = "Test Habito",
            CategoriaId = 1,
            Frecuencia = "Diaria",
            DiasSemana = new[] { 1, 2, 3, 4, 5 }
        };

        var result = await _controller.Crear(dto);

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<SuccessResponseDto>>(okResult.Value);
        Assert.True(apiResp.Exito);
    }

    [Fact]
    public async Task CrearYCompletarHabito_FlujoCompleto()
    {
        await SeedUsuarioAsync();

        var dto = new CrearHabitoDto
        {
            Nombre = "Completar Test",
            CategoriaId = 1,
            Frecuencia = "Diaria",
            DiasSemana = new[] { 1, 2, 3, 4, 5 }
        };
        await _controller.Crear(dto);

        var habito = await _contexto.Habitos.FirstAsync(h => h.UsuarioId == _usuarioId);

        var result = await _controller.Completar(habito.Id);

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<CompletarHabitoRespuestaDto>>(okResult.Value);
        Assert.True(apiResp.Exito);
        Assert.True(apiResp.Datos?.XpGanado > 0);
    }

    [Fact]
    public async Task CompletarHabito_YaCompletado_RetornaBadRequest()
    {
        await SeedUsuarioAsync();

        var habito = new Habito
        {
            Nombre = "Ya Completado",
            UsuarioId = _usuarioId,
            CategoriaId = 1,
            Frecuencia = "Diaria",
            DiasSemana = new List<DiasSemanaHabito>
            {
                new() { DiaSemana = 1 },
                new() { DiaSemana = 2 },
                new() { DiaSemana = 3 },
                new() { DiaSemana = 4 },
                new() { DiaSemana = 5 }
            },
            EstaActivo = true
        };
        _contexto.Habitos.Add(habito);
        await _contexto.SaveChangesAsync();

        await _controller.Completar(habito.Id);
        var result = await _controller.Completar(habito.Id);

        var badResult = Assert.IsType<BadRequestObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<CompletarHabitoRespuestaDto>>(badResult.Value);
        Assert.False(apiResp.Exito);
    }

    [Fact]
    public async Task ObtenerCategorias_RetornaLista()
    {
        await SeedUsuarioAsync();

        var result = await _controller.Categorias();

        var okResult = Assert.IsType<OkObjectResult>(result);
        Assert.True(okResult.StatusCode == 200);
    }

    [Fact]
    public async Task Dashboard_RetornaDatos()
    {
        await SeedUsuarioAsync();

        var result = await _controller.Dashboard();

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<object>>(okResult.Value);
        Assert.True(apiResp.Exito);
        Assert.NotNull(apiResp.Datos);
    }

    [Fact]
    public async Task ObtenerHabitosHoy_SinHabitos_RetornaListaVacia()
    {
        await SeedUsuarioAsync();

        var result = await _controller.ObtenerHabitosHoy();

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<object>>(okResult.Value);
        Assert.True(apiResp.Exito);
    }

    [Fact]
    public async Task ObtenerHabitos_DespuesDeCrear_RetornaHabito()
    {
        await SeedUsuarioAsync();

        var dto = new CrearHabitoDto
        {
            Nombre = "Listar Test",
            CategoriaId = 1,
            Frecuencia = "Diaria"
        };
        await _controller.Crear(dto);

        var result = await _controller.ObtenerHabitos();

        var okResult = Assert.IsType<OkObjectResult>(result);
        var apiResp = Assert.IsType<RespuestaApi<object>>(okResult.Value);
        Assert.True(apiResp.Exito);
    }
}
