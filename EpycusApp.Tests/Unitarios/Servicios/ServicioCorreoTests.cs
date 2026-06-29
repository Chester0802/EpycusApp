using EpycusApp.Servicios.Implementaciones;
using FluentAssertions;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Servicios;

[Trait("Categoria", "Unitario")]
public class ServicioCorreoTests
{
    private static ServicioCorreo CrearServicio(Dictionary<string, string?> config)
    {
        var configuration = new ConfigurationBuilder().AddInMemoryCollection(config).Build();
        var logger = new Mock<ILogger<ServicioCorreo>>();
        return new ServicioCorreo(configuration, logger.Object);
    }

    [Fact]
    public async Task EnviarVerificacion_SinUrlBase_LanzaInvalidOperation()
    {
        var servicio = CrearServicio(new Dictionary<string, string?>());

        var accion = async () => await servicio.EnviarVerificacion("a@test.com", "Ana", "tok");

        (await accion.Should().ThrowAsync<InvalidOperationException>())
            .Which.Message.Should().Contain("App:UrlBase");
    }

    [Fact]
    public async Task EnviarVerificacion_SinServidorCorreo_LanzaInvalidOperation()
    {
        var servicio = CrearServicio(new Dictionary<string, string?>
        {
            ["App:UrlBase"] = "https://app.epycus.es"
        });

        var accion = async () => await servicio.EnviarVerificacion("a@test.com", "Ana", "tok");

        (await accion.Should().ThrowAsync<InvalidOperationException>())
            .Which.Message.Should().Contain("Correo:Servidor");
    }

    [Fact]
    public async Task EnviarRecuperacion_SinUrlBase_LanzaInvalidOperation()
    {
        var servicio = CrearServicio(new Dictionary<string, string?>());

        var accion = async () => await servicio.EnviarRecuperacion("a@test.com", "Ana", "tok");

        await accion.Should().ThrowAsync<InvalidOperationException>();
    }

    [Fact]
    public async Task EnviarBienvenida_SinServidorCorreo_LanzaInvalidOperation()
    {
        var servicio = CrearServicio(new Dictionary<string, string?>());

        var accion = async () => await servicio.EnviarBienvenida("a@test.com", "Ana");

        (await accion.Should().ThrowAsync<InvalidOperationException>())
            .Which.Message.Should().Contain("Correo:Servidor");
    }
}
