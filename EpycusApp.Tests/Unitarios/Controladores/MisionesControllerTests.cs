using System.Security.Claims;
using EpycusApp.Controllers;
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
public class MisionesControllerTests
{
    private readonly Mock<IServicioMisiones> _misionesMock;
    private readonly MisionesController _controller;

    public MisionesControllerTests()
    {
        _misionesMock = new Mock<IServicioMisiones>();
        _misionesMock.Setup(m => m.ObtenerCategoriasMisionAsync()).ReturnsAsync(new List<Categoria>());
        _controller = new MisionesController(_misionesMock.Object);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
        _controller.TempData = new TempDataDictionary(httpContext, Mock.Of<ITempDataProvider>());
    }

    private static Mision MisionPropia(int id = 5) => new()
    {
        Id = id,
        UsuarioId = 1,
        Nombre = "Estudiar",
        Descripcion = "desc",
        NombreCurso = "Mate",
        FechaLimite = DateOnly.FromDateTime(DateTime.Today.AddDays(1)),
        Prioridad = "Alta",
        Estado = "Pendiente"
    };

    [Fact]
    public async Task Index_RetornaVistaConMisiones()
    {
        _misionesMock.Setup(m => m.ObtenerMisionesDeUsuario(1)).ReturnsAsync(new List<Mision>());

        var resultado = await _controller.Index();

        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task Crear_Get_RetornaVista()
    {
        var resultado = await _controller.Crear();
        resultado.Should().BeOfType<ViewResult>();
    }

    [Fact]
    public async Task Crear_Post_Valido_CreaYRedirige()
    {
        var modelo = new CrearMisionViewModel { Nombre = "X", FechaLimite = DateTime.Today.AddDays(1) };

        var resultado = await _controller.Crear(modelo);

        resultado.Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
        _misionesMock.Verify(m => m.CrearMision(modelo, 1), Times.Once);
    }

    [Fact]
    public async Task Crear_Post_Invalido_RetornaVista()
    {
        _controller.ModelState.AddModelError("Nombre", "Requerido");

        var resultado = await _controller.Crear(new CrearMisionViewModel());

        resultado.Should().BeOfType<ViewResult>();
        _misionesMock.Verify(m => m.CrearMision(It.IsAny<CrearMisionViewModel>(), It.IsAny<int>()), Times.Never);
    }

    [Fact]
    public async Task Editar_Get_MisionAjena_RetornaNotFound()
    {
        var ajena = MisionPropia();
        ajena.UsuarioId = 999;
        _misionesMock.Setup(m => m.ObtenerPorId(5)).ReturnsAsync(ajena);

        var resultado = await _controller.Editar(5);

        resultado.Should().BeOfType<NotFoundResult>();
    }

    [Fact]
    public async Task Editar_Get_MisionPropia_RetornaVista()
    {
        _misionesMock.Setup(m => m.ObtenerPorId(5)).ReturnsAsync(MisionPropia());

        var resultado = await _controller.Editar(5);

        var view = resultado.Should().BeOfType<ViewResult>().Subject;
        view.Model.Should().BeOfType<EditarMisionViewModel>();
    }

    [Fact]
    public async Task CambiarEstado_LlamaServicioYRedirige()
    {
        var resultado = await _controller.CambiarEstado(5, "EnProgreso");

        resultado.Should().BeOfType<RedirectToActionResult>();
        _misionesMock.Verify(m => m.CambiarEstado(5, "EnProgreso", 1), Times.Once);
    }

    [Fact]
    public async Task Completar_Exito_RedirigeConExito()
    {
        _misionesMock.Setup(m => m.CompletarMision(5, 1)).ReturnsAsync((true, 50));

        var resultado = await _controller.Completar(5);

        resultado.Should().BeOfType<RedirectToActionResult>();
        _controller.TempData["Exito"].Should().NotBeNull();
    }

    [Fact]
    public async Task Revertir_Fallo_RedirigeConError()
    {
        _misionesMock.Setup(m => m.RevertirMision(5, 1)).ReturnsAsync((false, "No se puede revertir"));

        var resultado = await _controller.Revertir(5);

        resultado.Should().BeOfType<RedirectToActionResult>();
        _controller.TempData["Error"].Should().Be("No se puede revertir");
    }

    [Fact]
    public async Task Eliminar_LlamaServicioYRedirige()
    {
        var resultado = await _controller.Eliminar(5);

        resultado.Should().BeOfType<RedirectToActionResult>();
        _misionesMock.Verify(m => m.EliminarMision(5, 1), Times.Once);
    }
}
