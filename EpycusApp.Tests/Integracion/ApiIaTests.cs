using System.Security.Claims;
using EpycusApp.Controllers.Api;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels.Ia;
using FluentAssertions;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Integracion;

[Trait("Categoria", "Integracion")]
public class ApiIaTests
{
    private readonly Mock<IServicioIA> _iaMock;
    private readonly ApiIaController _controller;

    public ApiIaTests()
    {
        _iaMock = new Mock<IServicioIA>();
        _controller = new ApiIaController(_iaMock.Object);
        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, "1") };
        var principal = new ClaimsPrincipal(new ClaimsIdentity(claims, "TestAuth"));
        _controller.ControllerContext = new ControllerContext { HttpContext = new DefaultHttpContext { User = principal } };
    }

    [Fact]
    public async Task Chat_SinConversacionId_GeneraNuevaYRetornaOk()
    {
        _iaMock.Setup(s => s.NuevaConversacionId()).Returns("nueva");
        _iaMock.Setup(s => s.ChatAsync(1, "hola", "nueva")).ReturnsAsync("respuesta");

        var resultado = await _controller.Chat(new ChatRequest { Mensaje = "hola" });

        resultado.Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Chat_ConConversacionId_RetornaOk()
    {
        _iaMock.Setup(s => s.ChatAsync(1, "hola", "c1")).ReturnsAsync("respuesta");

        var resultado = await _controller.Chat(new ChatRequest { Mensaje = "hola", ConversacionId = "c1" });

        resultado.Should().BeOfType<OkObjectResult>();
        _iaMock.Verify(s => s.NuevaConversacionId(), Times.Never);
    }

    [Fact]
    public async Task Historial_RetornaOk()
    {
        _iaMock.Setup(s => s.ObtenerHistorialAsync(1, "c1")).ReturnsAsync(new List<MensajeIA>());
        (await _controller.Historial("c1")).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Conversaciones_RetornaOk()
    {
        _iaMock.Setup(s => s.ObtenerConversacionesAsync(1)).ReturnsAsync(new List<ConversacionResumen>());
        (await _controller.Conversaciones()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task Sugerencias_RetornaOk()
    {
        _iaMock.Setup(s => s.ObtenerSugerenciasPersonalizadasAsync(1)).ReturnsAsync(new List<string>());
        (await _controller.Sugerencias()).Should().BeOfType<OkObjectResult>();
    }

    [Fact]
    public async Task ContextoBienestar_RetornaOk()
        => (await _controller.ContextoBienestar()).Should().BeOfType<OkObjectResult>();

    [Fact]
    public async Task Feedback_RetornaOk()
    {
        var resultado = await _controller.Feedback(new FeedbackRequest { MensajeId = 5, Util = true });

        resultado.Should().BeOfType<OkObjectResult>();
        _iaMock.Verify(s => s.RegistrarFeedbackAsync(1, 5, true), Times.Once);
    }

    [Fact]
    public async Task MensajesHoy_RetornaOk()
    {
        _iaMock.Setup(s => s.ObtenerMensajesHoyAsync(1)).ReturnsAsync(7);
        (await _controller.MensajesHoy()).Should().BeOfType<OkObjectResult>();
    }
}
