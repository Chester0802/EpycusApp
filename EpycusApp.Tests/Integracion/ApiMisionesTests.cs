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
public class ApiMisionesTests
{
    private readonly Mock<IServicioMisiones> _mock;
    private readonly ApiMisionesController _controller;

    public ApiMisionesTests()
    {
        _mock = new Mock<IServicioMisiones>();
        _controller = new ApiMisionesController(_mock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    private static Mision Mision() => new()
    {
        Id = 5, UsuarioId = 1, Nombre = "M", Prioridad = "Alta", Estado = "Pendiente",
        FechaLimite = DateOnly.FromDateTime(DateTime.Today.AddDays(1)), SubTareas = new List<SubTarea>()
    };

    private static SubTarea SubTarea() => new()
    {
        Id = 2, MisionId = 5, Nombre = "ST", EstaCompletada = false, Orden = 1, FechaCreacion = DateTime.UtcNow
    };

    private static CrearMisionDto CrearDto() => new() { Nombre = "M", FechaLimite = "2026-12-31", Prioridad = "Alta" };
    private static EditarMisionDto EditarDto() => new() { Nombre = "M", FechaLimite = "2026-12-31", Prioridad = "Alta" };

    [Fact]
    public async Task ObtenerMisiones_RetornaOk()
    {
        _mock.Setup(m => m.ObtenerMisionesDeUsuario(1)).ReturnsAsync(new List<Mision> { Mision() });
        (await _controller.ObtenerMisiones()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ObtenerPorId_Inexistente_RetornaNotFound()
    {
        _mock.Setup(m => m.ObtenerPorId(5)).ReturnsAsync((Mision?)null);
        (await _controller.ObtenerPorId(5)).Should().BeOfType<NotFoundObjectResult>();
    }

    [Fact]
    public async Task ObtenerPorId_Existente_RetornaOk()
    {
        _mock.Setup(m => m.ObtenerPorId(5)).ReturnsAsync(Mision());
        (await _controller.ObtenerPorId(5)).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Crear_RetornaOk()
    {
        var resultado = await _controller.Crear(CrearDto());
        resultado.Should().BeOfType<OkObjectResult>();
        _mock.Verify(m => m.CrearMision(It.IsAny<CrearMisionViewModel>(), 1), Times.Once);
    }

    [Fact]
    public async Task Editar_RetornaOk()
    {
        var resultado = await _controller.Editar(5, EditarDto());
        resultado.Should().BeOfType<OkObjectResult>();
        _mock.Verify(m => m.EditarMision(It.IsAny<EditarMisionViewModel>(), 1), Times.Once);
    }

    [Fact]
    public async Task Eliminar_RetornaOk()
    {
        var resultado = await _controller.Eliminar(5);
        resultado.Should().BeOfType<OkObjectResult>();
        _mock.Verify(m => m.EliminarMision(5, 1), Times.Once);
    }

    [Fact]
    public async Task Completar_Exito_RetornaOk()
    {
        _mock.Setup(m => m.CompletarMision(5, 1)).ReturnsAsync((true, 40));
        (await _controller.Completar(5)).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Completar_Fallo_RetornaBadRequest()
    {
        _mock.Setup(m => m.CompletarMision(5, 1)).ReturnsAsync((false, 0));
        (await _controller.Completar(5)).Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task CambiarEstado_RetornaOk()
    {
        var resultado = await _controller.CambiarEstado(5, new ApiMisionesController.EstadoDto { Estado = "EnProgreso" });
        resultado.Should().BeOfType<OkObjectResult>();
        _mock.Verify(m => m.CambiarEstado(5, "EnProgreso", 1), Times.Once);
    }

    [Fact]
    public async Task ObtenerSubTareas_RetornaOk()
    {
        _mock.Setup(m => m.ObtenerSubTareas(5, 1)).ReturnsAsync(new List<SubTarea> { SubTarea() });
        (await _controller.ObtenerSubTareas(5)).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ObtenerSubTarea_Inexistente_RetornaNotFound()
    {
        _mock.Setup(m => m.ObtenerSubTareaPorId(2, 1)).ReturnsAsync((SubTarea?)null);
        (await _controller.ObtenerSubTarea(5, 2)).Should().BeOfType<NotFoundObjectResult>();
    }

    [Fact]
    public async Task ObtenerSubTarea_Existente_RetornaOk()
    {
        _mock.Setup(m => m.ObtenerSubTareaPorId(2, 1)).ReturnsAsync(SubTarea());
        (await _controller.ObtenerSubTarea(5, 2)).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task CrearSubTarea_RetornaOk()
    {
        var resultado = await _controller.CrearSubTarea(5, new CrearSubTareaDto { Nombre = "ST" });
        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task CrearSubTarea_Excepcion_RetornaBadRequest()
    {
        _mock.Setup(m => m.CrearSubTarea(It.IsAny<string>(), It.IsAny<string?>(), 5, 1))
            .ThrowsAsync(new InvalidOperationException("limite"));

        var resultado = await _controller.CrearSubTarea(5, new CrearSubTareaDto { Nombre = "ST" });

        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task EditarSubTarea_RetornaOk()
    {
        var resultado = await _controller.EditarSubTarea(5, 2, new EditarSubTareaDto { Nombre = "ST2" });
        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task CompletarSubTarea_RetornaOk()
    {
        var resultado = await _controller.CompletarSubTarea(5, 2);
        resultado.Should().BeOfType<OkObjectResult>();
        _mock.Verify(m => m.CompletarSubTarea(2, 1), Times.Once);
    }

    [Fact]
    public async Task DescompletarSubTarea_RetornaOk()
        => (await _controller.DescompletarSubTarea(5, 2)).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task EliminarSubTarea_RetornaOk()
    {
        var resultado = await _controller.EliminarSubTarea(5, 2);
        resultado.Should().BeOfType<OkObjectResult>();
        _mock.Verify(m => m.EliminarSubTarea(2, 1), Times.Once);
    }

    [Fact]
    public async Task Categorias_RetornaOk()
    {
        _mock.Setup(m => m.ObtenerCategoriasMisionAsync()).ReturnsAsync(new List<Categoria>());
        (await _controller.Categorias()).Should().BeOfType<OkObjectResult>();
    }
}
