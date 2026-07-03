using System.Security.Claims;
using EpycusApp.Controllers;
using EpycusApp.DTOs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Ia;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Controladores;

[Trait("Categoria", "Unitario")]
public class IaControllerTests
{
    private readonly Mock<IServicioIA> _iaMock;
    private readonly Mock<IServicioBienestar> _bienestarMock;
    private readonly IaController _controller;

    public IaControllerTests()
    {
        _iaMock = new Mock<IServicioIA>();
        _bienestarMock = new Mock<IServicioBienestar>();
        var logger = new Mock<ILogger<IaController>>();
        _controller = new IaController(_iaMock.Object, _bienestarMock.Object, logger.Object);

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        var httpContext = new DefaultHttpContext { User = principal };
        _controller.ControllerContext = new ControllerContext { HttpContext = httpContext };
    }

    [Fact]
    public async Task Index_SinConversacion_RedirigeConNuevaId()
    {
        _iaMock.Setup(s => s.NuevaConversacionId()).Returns("nueva-id");

        var resultado = await _controller.Index(null);

        var redirect = resultado.Should().BeOfType<RedirectToActionResult>().Subject;
        redirect.RouteValues!["conv"].Should().Be("nueva-id");
    }

    [Fact]
    public async Task Index_ConConversacion_RetornaVista()
    {
        _iaMock.Setup(s => s.ObtenerHistorialAsync(1, "c1")).ReturnsAsync(new List<MensajeIA>());
        _iaMock.Setup(s => s.ObtenerSugerenciasPersonalizadasAsync(1)).ReturnsAsync(new List<string>());
        _iaMock.Setup(s => s.ObtenerConversacionesAsync(1)).ReturnsAsync(new List<ConversacionResumen>());

        var resultado = await _controller.Index("c1");

        var view = resultado.Should().BeOfType<ViewResult>().Subject;
        view.Model.Should().BeOfType<IaChatViewModel>();
    }

    [Fact]
    public void Nueva_RedirigeIndex()
    {
        _controller.Nueva().Should().BeOfType<RedirectToActionResult>()
            .Which.ActionName.Should().Be("Index");
    }

    [Fact]
    public async Task Chat_DtoNulo_RetornaBadRequest()
    {
        var resultado = await _controller.Chat(null);
        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task Chat_MensajeMuyLargo_RetornaBadRequest()
    {
        var dto = new ChatMensajeDto { Mensaje = new string('a', 2001), ConversacionId = "c1" };

        var resultado = await _controller.Chat(dto);

        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task Chat_Valido_RetornaOk()
    {
        _iaMock.Setup(s => s.ChatAsync(1, "hola", "c1")).ReturnsAsync(("respuesta IA", 7));
        var dto = new ChatMensajeDto { Mensaje = "hola", ConversacionId = "c1" };

        var resultado = await _controller.Chat(dto);

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Chat_ConversacionAjena_RetornaForbid()
    {
        _iaMock.Setup(s => s.ChatAsync(1, "hola", "c1")).ThrowsAsync(new UnauthorizedAccessException());
        var dto = new ChatMensajeDto { Mensaje = "hola", ConversacionId = "c1" };

        var resultado = await _controller.Chat(dto);

        resultado.Should().BeOfType<ForbidResult>();
    }

    [Fact]
    public async Task Chat_LimiteDiario_RetornaOkConError()
    {
        _iaMock.Setup(s => s.ChatAsync(1, "hola", "c1"))
            .ThrowsAsync(new InvalidOperationException("Has alcanzado el limite diario de 5 mensajes."));
        var dto = new ChatMensajeDto { Mensaje = "hola", ConversacionId = "c1" };

        var resultado = await _controller.Chat(dto);

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Feedback_MensajeIdInvalido_RetornaBadRequest()
    {
        var resultado = await _controller.Feedback(new FeedbackDto { MensajeId = 0, Util = true });
        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task Feedback_Valido_RetornaOk()
    {
        var resultado = await _controller.Feedback(new FeedbackDto { MensajeId = 5, Util = true });

        resultado.Should().BeOfType<OkObjectResult>();
        _iaMock.Verify(s => s.RegistrarFeedbackAsync(1, 5, true), Times.Once);
    }

    [Fact]
    public async Task RegistrarAnimo_EstadoVacio_RetornaBadRequest()
    {
        var resultado = await _controller.RegistrarAnimo(new AnimoChatDto { Estado = "" });
        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    [Fact]
    public async Task RegistrarAnimo_Valido_RetornaOk()
    {
        _bienestarMock.Setup(b => b.RegistrarEstadoAnimo(1, "Feliz", null)).ReturnsAsync((AlertaBienestar?)null);

        var resultado = await _controller.RegistrarAnimo(new AnimoChatDto { Estado = "Feliz" });

        resultado.Should().BeOfType<OkObjectResult>();
    }
}
