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
public class ProveedorDeepSeekTests
{
    private static ProveedorDeepSeek CrearProveedor(FakeHttpMessageHandler handler)
    {
        var factory = new Mock<IHttpClientFactory>();
        factory.Setup(f => f.CreateClient("DeepSeek")).Returns(new HttpClient(handler));
        var config = new ConfigurationBuilder().AddInMemoryCollection(new Dictionary<string, string?>
        {
            ["DeepSeek:ApiKey"] = "test-key",
            ["DeepSeek:Modelo"] = "deepseek-chat"
        }).Build();
        var logger = new Mock<ILogger<ProveedorDeepSeek>>();
        return new ProveedorDeepSeek(factory.Object, config, logger.Object);
    }

    private static ContextoUsuarioIA Ctx() => new() { Nombre = "Test", DiasDesdeUltimoAnimo = -1 };

    [Fact]
    public async Task LlamarAsync_RespuestaExitosa_RetornaContenido()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.OK,
            "{\"choices\":[{\"message\":{\"content\":\"Hola, soy EDY\"},\"finish_reason\":\"stop\"}]}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Be("Hola, soy EDY");
    }

    [Fact]
    public async Task LlamarAsync_ContenidoVacio_RetornaMensajeReformular()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.OK,
            "{\"choices\":[{\"message\":{\"content\":\"\"}}]}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("No recibi respuesta");
    }

    [Fact]
    public async Task LlamarAsync_429_RetornaSaturada()
    {
        var handler = new FakeHttpMessageHandler((HttpStatusCode)429, "{\"error\":\"rate\"}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("saturada");
    }

    [Fact]
    public async Task LlamarAsync_Unauthorized_RetornaErrorConexion()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.Unauthorized, "{\"error\":\"unauth\"}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("no pude conectarme");
        handler.Llamadas.Should().Be(1);
    }

    [Fact]
    public async Task LlamarAsync_ErrorServidor_ReintentaYRetornaError()
    {
        var handler = new FakeHttpMessageHandler(HttpStatusCode.InternalServerError, "{\"error\":\"boom\"}");
        var proveedor = CrearProveedor(handler);

        var resultado = await proveedor.LlamarAsync(Ctx(), new List<MensajeIA>());

        resultado.Should().Contain("no pude conectarme");
        handler.Llamadas.Should().Be(3);
    }
}
