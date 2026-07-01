using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Servicios;

[Trait("Categoria", "Unitario")]
public class ServicioIATests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioIA _servicio;
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private const int UsuarioId = 1;

    public ServicioIATests()
    {
        _contexto = DbContextFactory.CrearContexto("ServicioIATest");
        var constructor = new ConstructorContextoIA(_contexto);
        var deepSeekMock = new Mock<IProveedorDeepSeek>();
        _gamificacionMock = new Mock<IServicioGamificacion>();

        _servicio = new ServicioIA(_contexto, constructor, deepSeekMock.Object, _gamificacionMock.Object);
    }

    private MensajeIA NuevoMensaje(string conversacionId, string rol, string contenido, DateTime? fecha = null, int usuarioId = UsuarioId)
        => new()
        {
            ConversacionId = conversacionId,
            UsuarioId = usuarioId,
            Rol = rol,
            Contenido = contenido,
            FechaHora = fecha ?? DateTime.UtcNow
        };

    [Fact]
    public void NuevaConversacionId_RetornaGuidValido()
    {
        var id = _servicio.NuevaConversacionId();
        Guid.TryParse(id, out _).Should().BeTrue();
    }

    [Fact]
    public async Task ObtenerHistorialAsync_RetornaMensajesOrdenados()
    {
        _contexto.MensajesIA.AddRange(
            NuevoMensaje("c1", "user", "primero", DateTime.UtcNow.AddMinutes(-2)),
            NuevoMensaje("c1", "model", "segundo", DateTime.UtcNow.AddMinutes(-1)),
            NuevoMensaje("c2", "user", "otra conversacion")
        );
        await _contexto.SaveChangesAsync();

        var historial = await _servicio.ObtenerHistorialAsync(UsuarioId, "c1");

        historial.Should().HaveCount(2);
        historial[0].Contenido.Should().Be("primero");
        historial[1].Contenido.Should().Be("segundo");
    }

    [Fact]
    public async Task ObtenerConversacionesAsync_AgrupaPorConversacion()
    {
        _contexto.MensajesIA.AddRange(
            NuevoMensaje("c1", "user", "hola"),
            NuevoMensaje("c1", "model", "respuesta"),
            NuevoMensaje("c2", "user", "otra")
        );
        await _contexto.SaveChangesAsync();

        var conversaciones = await _servicio.ObtenerConversacionesAsync(UsuarioId);

        conversaciones.Should().HaveCount(2);
        conversaciones.Should().Contain(c => c.ConversacionId == "c1" && c.CantidadMensajes == 2);
    }

    [Fact]
    public async Task ObtenerMensajesHoyAsync_CuentaSoloDeHoy()
    {
        _contexto.MensajesIA.AddRange(
            NuevoMensaje("c1", "user", "hoy1"),
            NuevoMensaje("c1", "user", "hoy2"),
            NuevoMensaje("c1", "user", "ayer", DateTime.UtcNow.AddDays(-1))
        );
        await _contexto.SaveChangesAsync();

        var cuenta = await _servicio.ObtenerMensajesHoyAsync(UsuarioId);

        cuenta.Should().Be(2);
    }

    [Fact]
    public async Task RegistrarFeedbackAsync_MarcaMensaje()
    {
        var mensaje = NuevoMensaje("c1", "model", "respuesta");
        _contexto.MensajesIA.Add(mensaje);
        await _contexto.SaveChangesAsync();

        await _servicio.RegistrarFeedbackAsync(UsuarioId, mensaje.Id, true);

        var actualizado = await _contexto.MensajesIA.FindAsync(mensaje.Id);
        actualizado!.FeedbackRecibido.Should().BeTrue();
        actualizado.FeedbackUtil.Should().BeTrue();
    }

    [Fact]
    public async Task RegistrarFeedbackAsync_MensajeDeOtroUsuario_NoModifica()
    {
        var mensaje = NuevoMensaje("c1", "model", "respuesta", usuarioId: 999);
        _contexto.MensajesIA.Add(mensaje);
        await _contexto.SaveChangesAsync();

        await _servicio.RegistrarFeedbackAsync(UsuarioId, mensaje.Id, true);

        var actualizado = await _contexto.MensajesIA.FindAsync(mensaje.Id);
        actualizado!.FeedbackRecibido.Should().BeNull();
    }

    [Fact]
    public async Task ObtenerSugerenciasPersonalizadasAsync_IncluyeSugerenciasBase()
    {
        var sugerencias = await _servicio.ObtenerSugerenciasPersonalizadasAsync(UsuarioId);

        sugerencias.Should().Contain("Dame un consejo de productividad");
        sugerencias.Count.Should().BeLessThanOrEqualTo(6);
    }

    [Fact]
    public async Task ObtenerBienestarContextoAsync_AnimosNegativos_TieneAlertas()
    {
        _contexto.EstadosAnimo.AddRange(
            new EstadoAnimo { UsuarioId = UsuarioId, Estado = "Triste", Fecha = DateOnly.FromDateTime(DateTime.Today) },
            new EstadoAnimo { UsuarioId = UsuarioId, Estado = "Enojado", Fecha = DateOnly.FromDateTime(DateTime.Today.AddDays(-1)) }
        );
        await _contexto.SaveChangesAsync();

        var ctx = await _servicio.ObtenerBienestarContextoAsync(UsuarioId);

        ctx.Should().NotBeNull();
        ctx!.DiasAnimoNegativo.Should().Be(2);
        ctx.TieneAlertasActivas.Should().BeTrue();
    }

    [Fact]
    public async Task ChatAsync_ConversacionDeOtroUsuario_LanzaUnauthorized()
    {
        _contexto.MensajesIA.Add(NuevoMensaje("cAjena", "user", "hola", usuarioId: 999));
        await _contexto.SaveChangesAsync();

        var accion = async () => await _servicio.ChatAsync(UsuarioId, "hola", "cAjena");

        await accion.Should().ThrowAsync<UnauthorizedAccessException>();
    }

    [Fact]
    public async Task ChatAsync_LimiteDiarioAlcanzado_LanzaInvalidOperation()
    {
        for (int i = 0; i < 50; i++)
            _contexto.MensajesIA.Add(NuevoMensaje("historico", "user", $"msg{i}"));
        await _contexto.SaveChangesAsync();

        var accion = async () => await _servicio.ChatAsync(UsuarioId, "uno mas", "nueva-conv");

        (await accion.Should().ThrowAsync<InvalidOperationException>())
            .Which.Message.Should().Contain("limite diario");
    }
}
