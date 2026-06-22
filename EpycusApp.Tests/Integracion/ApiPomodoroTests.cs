using System.ComponentModel.DataAnnotations;
using System.Security.Claims;
using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.DTOs;
using EpycusApp.Controllers.Api;
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

public class ApiPomodoroTests : IDisposable
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioPomodoro _servicio;
    private readonly ApiPomodoroController _controller;
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private readonly Mock<IServicioBienestar> _bienestarMock;
    private readonly Mock<IServicioHabitos> _habitosMock;
    private readonly Mock<IServicioMisiones> _misionesMock;
    private readonly Mock<ILogger<ServicioPomodoro>> _loggerMock;
    private int _usuarioId;

    public ApiPomodoroTests()
    {
        _contexto = DbContextFactory.CrearContexto("PomodoroIntegracion");
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _bienestarMock = new Mock<IServicioBienestar>();
        _habitosMock = new Mock<IServicioHabitos>();
        _misionesMock = new Mock<IServicioMisiones>();
        _loggerMock = new Mock<ILogger<ServicioPomodoro>>();

        _servicio = new ServicioPomodoro(
            _contexto, _gamificacionMock.Object, _bienestarMock.Object,
            _habitosMock.Object, _misionesMock.Object, _loggerMock.Object);

        _controller = new ApiPomodoroController(_servicio);

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
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "API_POM_" + Guid.NewGuid().ToString()[..6],
            Nombre = "Api Test",
            CorreoElectronico = "api_pom@test.com",
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

        _gamificacionMock.Setup(g => g.SumarXP(It.IsAny<int>(), It.IsAny<int>()))
            .ReturnsAsync((15, false, 1));
        _gamificacionMock.Setup(g => g.VerificarYOtorgarLogros(It.IsAny<int>()))
            .Returns(Task.CompletedTask);

        return usuario.Id;
    }

    // T8: Validación DTO ActualizarConfiguracionPomodoroDto
    [Fact]
    public void T8_ActualizarConfiguracionDto_ValoresValidos_PasaValidacion()
    {
        var dto = new ActualizarConfiguracionPomodoroDto
        {
            TiempoEstudioMin = 25,
            TiempoDescansoMin = 5,
            TiempoDescansoLargoMin = 15,
            CiclosAntesDescansoLargo = 4,
            SonidoSeleccionado = "campana",
            Volumen = 80,
            MetaDiariaCiclos = 8,
            ModoPersonalizadoMin = 30
        };

        var ctx = new ValidationContext(dto);
        var resultados = new List<ValidationResult>();
        var esValido = Validator.TryValidateObject(dto, ctx, resultados, true);

        esValido.Should().BeTrue();
    }

    [Fact]
    public void T8_ActualizarConfiguracionDto_DescansoMayorAEstudio_FallaValidacion()
    {
        var dto = new ActualizarConfiguracionPomodoroDto
        {
            TiempoEstudioMin = 10,
            TiempoDescansoMin = 15,
            TiempoDescansoLargoMin = 20,
            CiclosAntesDescansoLargo = 4,
            SonidoSeleccionado = "campana",
        };

        var ctx = new ValidationContext(dto);
        var resultados = new List<ValidationResult>();
        var esValido = Validator.TryValidateObject(dto, ctx, resultados, true);

        esValido.Should().BeFalse();
        resultados.Should().Contain(r => r.MemberNames.Contains("TiempoDescansoMin"));
    }

    [Fact]
    public void T8_ActualizarConfiguracionDto_SonidoInvalido_FallaValidacion()
    {
        var dto = new ActualizarConfiguracionPomodoroDto
        {
            TiempoEstudioMin = 25,
            TiempoDescansoMin = 5,
            TiempoDescansoLargoMin = 15,
            CiclosAntesDescansoLargo = 4,
            SonidoSeleccionado = "inexistente",
        };

        var ctx = new ValidationContext(dto);
        var resultados = new List<ValidationResult>();
        var esValido = Validator.TryValidateObject(dto, ctx, resultados, true);

        esValido.Should().BeFalse();
        resultados.Should().Contain(r => r.MemberNames.Contains("SonidoSeleccionado"));
    }

    [Fact]
    public void T8_ActualizarConfiguracionDto_TiempoEstudioFueraDeRango_FallaValidacion()
    {
        var dto = new ActualizarConfiguracionPomodoroDto
        {
            TiempoEstudioMin = 200,
            TiempoDescansoMin = 5,
            TiempoDescansoLargoMin = 15,
            CiclosAntesDescansoLargo = 4,
            SonidoSeleccionado = "campana",
        };

        var ctx = new ValidationContext(dto);
        var resultados = new List<ValidationResult>();
        var esValido = Validator.TryValidateObject(dto, ctx, resultados, true);

        esValido.Should().BeFalse();
    }

    [Fact]
    public void T8_ActualizarConfiguracionDto_DescansoLargoNoMayorQueCorto_FallaValidacion()
    {
        var dto = new ActualizarConfiguracionPomodoroDto
        {
            TiempoEstudioMin = 25,
            TiempoDescansoMin = 15,
            TiempoDescansoLargoMin = 10,
            CiclosAntesDescansoLargo = 4,
            SonidoSeleccionado = "campana",
        };

        var ctx = new ValidationContext(dto);
        var resultados = new List<ValidationResult>();
        var esValido = Validator.TryValidateObject(dto, ctx, resultados, true);

        esValido.Should().BeFalse();
        resultados.Should().Contain(r => r.MemberNames.Contains("TiempoDescansoLargoMin"));
    }

    // T9: Validación DTO CicloCompletadoRequest
    [Fact]
    public void T9_CicloCompletadoRequest_RangoValido_PasaValidacion()
    {
        var req = new CicloCompletadoRequest { CiclosCompletados = 5 };
        var ctx = new ValidationContext(req);
        var resultados = new List<ValidationResult>();

        var esValido = Validator.TryValidateObject(req, ctx, resultados, true);

        esValido.Should().BeTrue();
    }

    [Fact]
    public void T9_CicloCompletadoRequest_Cero_FallaValidacion()
    {
        var req = new CicloCompletadoRequest { CiclosCompletados = 0 };
        var ctx = new ValidationContext(req);
        var resultados = new List<ValidationResult>();

        var esValido = Validator.TryValidateObject(req, ctx, resultados, true);

        esValido.Should().BeFalse();
    }

    [Fact]
    public void T9_CicloCompletadoRequest_MayorQue100_FallaValidacion()
    {
        var req = new CicloCompletadoRequest { CiclosCompletados = 101 };
        var ctx = new ValidationContext(req);
        var resultados = new List<ValidationResult>();

        var esValido = Validator.TryValidateObject(req, ctx, resultados, true);

        esValido.Should().BeFalse();
    }

    // T10: POST /api/pomodoro/iniciar
    [Fact]
    public async Task T10_Iniciar_SinSesionActiva_CreaSesion()
    {
        await SeedUsuarioAsync();

        var resultado = await _controller.Iniciar(new IniciarRequest());

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroIniciarResponse>>().Subject;
        respuesta.Exito.Should().BeTrue();
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.SesionId.Should().BeGreaterThan(0);
    }

    [Fact]
    public async Task T10_Iniciar_ConSesionActiva_RetornaConflict()
    {
        await SeedUsuarioAsync();
        await _servicio.IniciarSesion(_usuarioId, null, null);

        var resultado = await _controller.Iniciar(new IniciarRequest());

        resultado.Should().BeOfType<ConflictObjectResult>();
    }

    // T11: POST /api/pomodoro/{id}/ciclo-completado
    [Fact]
    public async Task T11_CicloCompletado_SesionValida_OtorgaXP()
    {
        await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(_usuarioId, null, null);

        var resultado = await _controller.CicloCompletado(sesion.Id, new CicloCompletadoRequest { CiclosCompletados = 2 });

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroCicloCompletadoResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.XpGanado.Should().Be(ConstantesGamificacion.XpBasePomodoro * 2);
    }

    [Fact]
    public async Task T11_CicloCompletado_SesionInexistente_RetornaNotFound()
    {
        await SeedUsuarioAsync();

        var resultado = await _controller.CicloCompletado(999, new CicloCompletadoRequest { CiclosCompletados = 1 });

        resultado.Should().BeOfType<NotFoundResult>();
    }

    [Fact]
    public async Task T11_CicloCompletado_SinAutenticacion_RetornaUnauthorized()
    {
        await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(_usuarioId, null, null);
        var otroUsuarioId = _usuarioId + 1;

        var claims = new[] { new Claim(ClaimTypes.NameIdentifier, otroUsuarioId.ToString()) };
        var identity = new ClaimsIdentity(claims, "TestAuth");
        var principal = new ClaimsPrincipal(identity);
        _controller.ControllerContext.HttpContext.User = principal;

        var resultado = await _controller.CicloCompletado(sesion.Id, new CicloCompletadoRequest { CiclosCompletados = 1 });

        resultado.Should().BeOfType<UnauthorizedObjectResult>();
    }

    // T12: POST /api/pomodoro/{id}/finalizar
    [Fact]
    public async Task T12_Finalizar_SesionValida_OtorgaBonusXP()
    {
        await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(_usuarioId, null, null);

        var resultado = await _controller.Finalizar(sesion.Id, new CicloCompletadoRequest { CiclosCompletados = 3 });

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroFinalizarResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.XpBonus.Should().Be(3 * 5 + 10);
        respuesta.Datos.SesionGuardada.Should().BeTrue();
    }

    [Fact]
    public async Task T12_Finalizar_SesionInexistente_RetornaNotFound()
    {
        await SeedUsuarioAsync();

        var resultado = await _controller.Finalizar(999, new CicloCompletadoRequest { CiclosCompletados = 1 });

        resultado.Should().BeOfType<NotFoundResult>();
    }

    // T13: POST /api/pomodoro/{id}/cancelar
    [Fact]
    public async Task T13_Cancelar_SesionValida_MarcaCancelada()
    {
        await SeedUsuarioAsync();
        var sesion = await _servicio.IniciarSesion(_usuarioId, null, null);

        var resultado = await _controller.Cancelar(sesion.Id);

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<SuccessResponseDto>>().Subject;
        respuesta.Exito.Should().BeTrue();

        var sesionDb = await _contexto.SesionesPomodoro.FindAsync(sesion.Id);
        sesionDb.Should().NotBeNull();
        sesionDb!.FueCompletada.Should().BeFalse();
        sesionDb.FechaFin.Should().NotBeNull();
    }

    [Fact]
    public async Task T13_Cancelar_SesionInexistente_RetornaNotFound()
    {
        await SeedUsuarioAsync();

        var resultado = await _controller.Cancelar(999);

        resultado.Should().BeOfType<NotFoundResult>();
    }

    // T14: GET/PUT /api/pomodoro/configuracion
    [Fact]
    public async Task T14_ObtenerConfiguracion_SinConfig_RetornaDefault()
    {
        await SeedUsuarioAsync();

        var resultado = await _controller.ObtenerConfiguracion();

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroConfiguracionResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.TiempoEstudio.Should().Be(25);
    }

    [Fact]
    public async Task T14_ObtenerConfiguracion_SinConfig_NoPersisteEnBD()
    {
        await SeedUsuarioAsync();
        var countBefore = await _contexto.ConfiguracionesPomodoro.CountAsync();

        await _controller.ObtenerConfiguracion();

        var countAfter = await _contexto.ConfiguracionesPomodoro.CountAsync();
        countAfter.Should().Be(countBefore);
    }

    [Fact]
    public async Task T14_ActualizarConfiguracion_ValoresValidos_GuardaCambios()
    {
        await SeedUsuarioAsync();
        var dto = new ActualizarConfiguracionPomodoroDto
        {
            TiempoEstudioMin = 50,
            TiempoDescansoMin = 10,
            TiempoDescansoLargoMin = 20,
            CiclosAntesDescansoLargo = 4,
            SonidoSeleccionado = "digital",
            Volumen = 80,
            AutoIniciarDescanso = true,
            MetaDiariaCiclos = 6,
            ModoPersonalizadoMin = 30,
            VibracionActiva = true,
            NotificacionDesktop = true
        };

        var resultado = await _controller.ActualizarConfiguracion(dto);

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<SuccessResponseDto>>().Subject;
        respuesta.Exito.Should().BeTrue();

        var config = await _contexto.ConfiguracionesPomodoro.FirstAsync(c => c.UsuarioId == _usuarioId);
        config.TiempoEstudioMin.Should().Be(50);
        config.SonidoSeleccionado.Should().Be("digital");
    }

    [Fact]
    public async Task T14_ActualizarConfiguracion_DatosInvalidos_RetornaBadRequest()
    {
        await SeedUsuarioAsync();
        _controller.ModelState.AddModelError("TiempoEstudioMin", "Fuera de rango");

        var dto = new ActualizarConfiguracionPomodoroDto();
        var resultado = await _controller.ActualizarConfiguracion(dto);

        resultado.Should().BeOfType<BadRequestObjectResult>();
    }

    // T15: GET /api/pomodoro/tip-aleatorio
    [Fact]
    public async Task T15_TipAleatorio_SinTips_RetornaVacio()
    {
        await SeedUsuarioAsync();

        var resultado = await _controller.ObtenerTip();

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroTipResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.Consejo.Should().BeEmpty();
    }

    [Fact]
    public async Task T15_TipAleatorio_ConTips_RetornaTip()
    {
        await SeedUsuarioAsync();
        _contexto.TipsPomodoro.Add(new TipPomodoro { Tip = "Test tip", EstaActivo = true });
        await _contexto.SaveChangesAsync();

        var resultado = await _controller.ObtenerTip();

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroTipResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.Consejo.Should().Be("Test tip");
    }

    // NF-22: Tests de filtros en GET /api/pomodoro/historial
    [Fact]
    public async Task T16_Historial_FiltroCompletadaTrue_RetornaSoloCompletadas()
    {
        await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = _usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = _usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 0, XpOtorgado = 0, FueCompletada = false };
        _contexto.SesionesPomodoro.AddRange(s1, s2);
        await _contexto.SaveChangesAsync();

        var resultado = await _controller.ObtenerHistorial(DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, true, null);

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroHistorialResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.Historial.Should().HaveCount(1);
        respuesta.Datos.Historial![0].FueCompletada.Should().BeTrue();
    }

    [Fact]
    public async Task T16_Historial_FiltroConXpTrue_RetornaSoloConXp()
    {
        await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = _usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = _usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 1, XpOtorgado = 0, FueCompletada = true };
        _contexto.SesionesPomodoro.AddRange(s1, s2);
        await _contexto.SaveChangesAsync();

        var resultado = await _controller.ObtenerHistorial(DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, null, true);

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroHistorialResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.Historial.Should().HaveCount(1);
        respuesta.Datos.Historial![0].XpOtorgado.Should().BeGreaterThan(0);
    }

    [Fact]
    public async Task T16_Historial_FiltrosCombinados_RetornaCoincidentes()
    {
        await SeedUsuarioAsync();
        var s1 = new SesionPomodoro { UsuarioId = _usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-3), CiclosCompletados = 2, XpOtorgado = 30, FueCompletada = true };
        var s2 = new SesionPomodoro { UsuarioId = _usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-2), CiclosCompletados = 0, XpOtorgado = 0, FueCompletada = false };
        var s3 = new SesionPomodoro { UsuarioId = _usuarioId, FechaInicio = DateTime.UtcNow.AddHours(-1), CiclosCompletados = 1, XpOtorgado = 15, FueCompletada = true };
        _contexto.SesionesPomodoro.AddRange(s1, s2, s3);
        await _contexto.SaveChangesAsync();

        var resultado = await _controller.ObtenerHistorial(DateTime.UtcNow.AddDays(-1), DateTime.UtcNow, 1, 20, true, true);

        var okResult = resultado.Should().BeOfType<OkObjectResult>().Subject;
        var respuesta = okResult.Value.Should().BeOfType<RespuestaApi<PomodoroHistorialResponse>>().Subject;
        respuesta.Datos.Should().NotBeNull();
        respuesta.Datos!.Historial.Should().HaveCount(2);
    }
}
