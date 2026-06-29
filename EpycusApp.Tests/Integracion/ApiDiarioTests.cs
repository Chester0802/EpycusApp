using System.Security.Claims;
using EpycusApp.Controllers.Api;
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
public class ApiDiarioTests
{
    private readonly Mock<IServicioDiarioAnimo> _mock;
    private readonly ApiDiarioController _controller;

    public ApiDiarioTests()
    {
        _mock = new Mock<IServicioDiarioAnimo>();
        _mock.Setup(d => d.ObtenerPreguntaGuia()).Returns("¿Qué aprendiste hoy?");
        _controller = new ApiDiarioController(_mock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    private static EntradaDiario Entrada() => new()
    {
        Id = 1, Fecha = DateOnly.FromDateTime(DateTime.Today), EstadoAnimo = 3, NivelEnergia = 3, FechaRegistro = DateTime.UtcNow
    };

    [Fact]
    public async Task ObtenerHoy_SinEntrada_RetornaOk()
        => (await _controller.ObtenerHoy()).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task ObtenerPorFecha_FormatoInvalido_RetornaBadRequest()
        => (await _controller.ObtenerPorFecha("2026/01/01")).Should().BeOfType<BadRequestObjectResult>();

    [Fact]
    public async Task ObtenerPorFecha_FormatoValido_RetornaOk()
    {
        _mock.Setup(d => d.ObtenerEntradaPorFecha(1, It.IsAny<DateOnly>())).ReturnsAsync(Entrada());
        (await _controller.ObtenerPorFecha("2026-01-15")).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ObtenerMes_RetornaOk()
    {
        _mock.Setup(d => d.ObtenerEntradasMes(1, 2026, 1)).ReturnsAsync(new List<EntradaDiario>());
        (await _controller.ObtenerMes(2026, 1)).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Registrar_Valido_RetornaCreated()
    {
        _mock.Setup(d => d.RegistrarEntrada(1, It.IsAny<RegistrarEntradaDiarioViewModel>(), It.IsAny<string>()))
            .ReturnsAsync(Entrada());

        var resultado = await _controller.Registrar(new RegistrarEntradaDiarioViewModel { EstadoAnimo = 3, NivelEnergia = 3 });

        resultado.Should().BeOfType<CreatedAtActionResult>();
    }

    [Fact]
    public async Task Registrar_ModeloInvalido_RetornaBadRequest()
    {
        _controller.ModelState.AddModelError("EstadoAnimo", "X");
        (await _controller.Registrar(new RegistrarEntradaDiarioViewModel())).Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task Actualizar_Inexistente_RetornaNotFound()
    {
        _mock.Setup(d => d.ActualizarEntrada(1, It.IsAny<DateOnly>(), It.IsAny<RegistrarEntradaDiarioViewModel>()))
            .ReturnsAsync((EntradaDiario?)null);

        var resultado = await _controller.Actualizar("2026-01-15", new RegistrarEntradaDiarioViewModel { EstadoAnimo = 3, NivelEnergia = 3 });

        resultado.Should().BeOfType<NotFoundObjectResult>();
    }

    [Fact]
    public async Task Actualizar_FechaInvalida_RetornaBadRequest()
        => (await _controller.Actualizar("bad", new RegistrarEntradaDiarioViewModel { EstadoAnimo = 3, NivelEnergia = 3 }))
            .Should().BeOfType<BadRequestObjectResult>();

    [Fact]
    public async Task ObtenerRacha_RetornaOk()
    {
        _mock.Setup(d => d.ObtenerDiasConsecutivos(1)).ReturnsAsync(5);
        (await _controller.ObtenerRacha()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ObtenerPromedioMes_RetornaOk()
        => (await _controller.ObtenerPromedioMes(2026, 1)).Should().BeOfType<OkObjectResult>();

    [Fact]
    public void ObtenerPreguntaGuia_RetornaOk()
        => _controller.ObtenerPreguntaGuia().Should().BeOfType<OkObjectResult>();
}
