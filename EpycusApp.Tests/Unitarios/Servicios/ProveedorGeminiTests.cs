using System.Net;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Servicios;

[Trait("Categoria", "Unitario")]
public class ProveedorGeminiTests
{
    private static ProveedorGemini CrearProveedor(FakeHttpMessageHandler handler)
    {
        var factory = new Mock<IHttpClientFactory>();
        factory.Setup(f => f.CreateClient("Gemini")).Returns(new HttpClient(handler));
        var config = new ConfigurationBuilder().AddInMemoryCollection(new Dictionary<string, string?>
        {
            ["Gemini:ApiKey"] = "test-key",
            ["Gemini:Modelo"] = "gemini-2.0-flash"
        }).Build();
        var logger = new Mock<ILogger<ProveedorGemini>>();
        return new ProveedorGemini(factory.Object, config, logger.Object);
    }

    private static ContextoUsuarioIA Ctx() => new() { Nombre = "Test", DiasDesdeUltimoAnimo = -1 };

    [Fact]
    public async Task LlamarAsync_RespuestaExitosa_RetornaTexto()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.OK,
            "{\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"Respuesta IA\"}]},\"finishReason\":\"STOP\"}]}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Be("Respuesta IA");
    }

    [Fact]
    public async Task LlamarAsync_BlockReason_RetornaMensajeReformular()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.OK,
            "{\"promptFeedback\":{\"blockReason\":\"SAFETY\"}}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("No puedo responder");
    }

    [Fact]
    public async Task LlamarAsync_CandidatoSafety_RetornaMensajeReformular()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.OK,
            "{\"candidates\":[{\"finishReason\":\"SAFETY\"}]}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("No puedo responder");
    }

    [Fact]
    public async Task LlamarAsync_BadRequest_RetornaErrorConexion()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.BadRequest, "{\"error\":\"bad\"}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("no pude conectarme");
        handler.Llamadas.Should().Be(1);
    }

    [Fact]
    public async Task LlamarAsync_TextoVacio_RetornaMensajeReformular()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.OK,
            "{\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"   \"}]}}]}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("No recibi respuesta");
    }
}
