using System.Text.Json;
using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using FluentAssertions;

namespace EpycusApp.Tests;

public class ContratoJsonTests
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        WriteIndented = false
    };

    [Fact]
    public void AuthResponseDto_Serializa_Y_Deserializa_Correctamente()
    {
        var original = new AuthResponseDto { Token = "jwt.123", RefreshToken = "refresh.456" };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("token", out _).Should().BeTrue();
        jsonDoc.RootElement.TryGetProperty("refreshToken", out _).Should().BeTrue();

        var deserializado = JsonSerializer.Deserialize<AuthResponseDto>(json, JsonOptions);
        deserializado.Should().NotBeNull();
        deserializado!.Token.Should().Be("jwt.123");
        deserializado.RefreshToken.Should().Be("refresh.456");
    }

    [Fact]
    public void MensajeResponseDto_Serializa_Correctamente()
    {
        var original = new MensajeResponseDto { Mensaje = "Operacion exitosa" };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("mensaje", out var prop).Should().BeTrue();
        prop.GetString().Should().Be("Operacion exitosa");
    }

    [Fact]
    public void SuccessResponseDto_Serializa_Correctamente()
    {
        var original = new SuccessResponseDto();
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("success", out var prop).Should().BeTrue();
        prop.GetBoolean().Should().BeTrue();
    }

    [Fact]
    public void DashboardResumenResponse_Serializa_Todas_Las_Propiedades()
    {
        var original = new DashboardResumenResponse
        {
            Kpis = new DashboardKpis { HabitosPendientes = 3, MisionesPendientes = 2 },
            HabitosPendientes = 3,
            MisionesPendientes = 2,
            Frase = new FraseResponseDto { Frase = "Persiste", Autor = "Anonimo" }
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        var propiedadesEsperadas = new[] { "kpis", "habitosPendientes", "misionesPendientes", "frase" };
        foreach (var prop in propiedadesEsperadas)
            jsonDoc.RootElement.TryGetProperty(prop, out _).Should().BeTrue($"'{prop}' deberia existir");
    }

    [Fact]
    public void GamificacionProgresoResponse_Serializa_Todas_Las_Propiedades()
    {
        var original = new GamificacionProgresoResponse
        {
            XpTotal = 1500,
            Nivel = 5,
            Titulo = "Guerrero",
            RachaActual = 7,
            XpParaSiguienteNivel = 500,
            PorcentajeProgreso = 75.0,
            ImagenPersonaje = "https://ejemplo.com/avatar.png"
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        var propiedades = new[] { "xpTotal", "nivel", "titulo", "rachaActual", "xpParaSiguienteNivel", "porcentajeProgreso", "imagenPersonaje" };
        foreach (var prop in propiedades)
            jsonDoc.RootElement.TryGetProperty(prop, out _).Should().BeTrue($"'{prop}' deberia existir");
    }

    [Fact]
    public void PomodoroIniciarResponse_Serializa_Correctamente()
    {
        var original = new PomodoroIniciarResponse { SesionId = 42, FechaInicio = new DateTime(2025, 3, 15, 10, 30, 0) };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("sesionId", out var idProp).Should().BeTrue();
        idProp.GetInt32().Should().Be(42);
        jsonDoc.RootElement.TryGetProperty("fechaInicio", out _).Should().BeTrue();
    }

    [Fact]
    public void PomodoroCicloCompletadoResponse_Serializa_Correctamente()
    {
        var original = new PomodoroCicloCompletadoResponse
        {
            XpGanado = 50,
            SugerirDescanso = true,
            PausaActiva = "Estiramientos"
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("xpGanado", out _).Should().BeTrue();
        jsonDoc.RootElement.TryGetProperty("sugerirDescanso", out _).Should().BeTrue();
        jsonDoc.RootElement.TryGetProperty("pausaActiva", out _).Should().BeTrue();
    }

    [Fact]
    public void PomodoroConfiguracionResponse_Serializa_Todas_Las_Propiedades()
    {
        var original = new PomodoroConfiguracionResponse
        {
            TiempoEstudio = 25,
            TiempoDescanso = 5,
            TiempoDescansoLargo = 15,
            CiclosAntesDescansoLargo = 4,
            SonidoActivo = true,
            SonidoSeleccionado = "lluvia",
            Volumen = 80,
            AutoIniciarDescanso = false,
            AutoIniciarEnfoque = true,
            TicTacActivo = false,
            MetaDiariaCiclos = 8,
            ModoPersonalizadoMinutos = null,
            VibracionActiva = true,
            NotificacionDesktop = false
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        var propiedades = new[]
        {
            "tiempoEstudio", "tiempoDescanso", "tiempoDescansoLargo", "ciclosAntesDescansoLargo",
            "sonidoActivo", "sonidoSeleccionado", "volumen", "autoIniciarDescanso",
            "autoIniciarEnfoque", "ticTacActivo", "metaDiariaCiclos", "modoPersonalizadoMinutos",
            "vibracionActiva", "notificacionDesktop"
        };
        foreach (var prop in propiedades)
            jsonDoc.RootElement.TryGetProperty(prop, out _).Should().BeTrue($"'{prop}' deberia existir");
    }

    [Fact]
    public void MisionListaItemResponse_Serializa_Todas_Las_Propiedades()
    {
        var original = new MisionListaItemResponse
        {
            Id = 1,
            Nombre = "Estudiar matematicas",
            Descripcion = "Capitulo 5",
            NombreCurso = "Matematicas",
            Prioridad = "Alta",
            Estado = "Pendiente",
            FechaLimite = "2025-04-01",
            XpOtorgado = 100,
            FechaCreacion = DateTime.Now,
            CategoriaId = 2
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        var propiedades = new[]
        {
            "id", "nombre", "descripcion", "nombreCurso", "prioridad",
            "estado", "fechaLimite", "xpOtorgado", "fechaCreacion", "categoriaId"
        };
        foreach (var prop in propiedades)
            jsonDoc.RootElement.TryGetProperty(prop, out _).Should().BeTrue($"'{prop}' deberia existir");
    }

    [Fact]
    public void MisionCompletarResponse_Serializa_Correctamente()
    {
        var original = new MisionCompletarResponse { XpGanado = 75 };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("xpGanado", out var prop).Should().BeTrue();
        prop.GetInt32().Should().Be(75);
    }

    [Fact]
    public void DiarioRachaResponse_Serializa_Correctamente()
    {
        var original = new DiarioRachaResponse { DiasConsecutivos = 5 };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("diasConsecutivos", out var prop).Should().BeTrue();
        prop.GetInt32().Should().Be(5);
    }

    [Fact]
    public void PreguntaGuiaResponse_Serializa_Correctamente()
    {
        var original = new PreguntaGuiaResponse { Pregunta = "Que aprendiste hoy?" };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("pregunta", out var prop).Should().BeTrue();
        prop.GetString().Should().Be("Que aprendiste hoy?");
    }

    [Fact]
    public void PomodoroTipResponse_Serializa_Correctamente()
    {
        var original = new PomodoroTipResponse { Consejo = "Toma descansos frecuentes" };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("consejo", out var prop).Should().BeTrue();
        prop.GetString().Should().Be("Toma descansos frecuentes");
    }

    [Fact]
    public void PomodoroSesionActivaResponse_Serializa_Correctamente()
    {
        var original = new PomodoroSesionActivaResponse
        {
            Activa = true,
            SesionId = 10,
            FechaInicio = DateTime.Now,
            CiclosCompletados = 2
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        var propiedades = new[] { "activa", "sesionId", "fechaInicio", "ciclosCompletados" };
        foreach (var prop in propiedades)
            jsonDoc.RootElement.TryGetProperty(prop, out _).Should().BeTrue($"'{prop}' deberia existir");
    }

    [Fact]
    public void IaChatResponseDto_Serializa_Correctamente()
    {
        var original = new IaChatResponseDto { Respuesta = "Hola!", ConversacionId = "conv-1" };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("respuesta", out _).Should().BeTrue();
        jsonDoc.RootElement.TryGetProperty("conversacionId", out _).Should().BeTrue();
    }

    [Fact]
    public void FraseResponseDto_Serializa_Correctamente()
    {
        var original = new FraseResponseDto { Frase = "El exito es la suma de pequenos esfuerzos", Autor = "R. Collard" };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("frase", out var fraseProp).Should().BeTrue();
        fraseProp.GetString().Should().Be("El exito es la suma de pequenos esfuerzos");
        jsonDoc.RootElement.TryGetProperty("autor", out var autorProp).Should().BeTrue();
        autorProp.GetString().Should().Be("R. Collard");
    }

    [Fact]
    public void RespuestaApi_Exitosa_Serializa_Estructura_Correcta()
    {
        var datos = new AuthResponseDto { Token = "test", RefreshToken = "test" };
        var respuesta = RespuestaApi<AuthResponseDto>.Exitosa(datos, "Login exitoso");

        var json = JsonSerializer.Serialize(respuesta, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("exito", out var exitoProp).Should().BeTrue();
        exitoProp.GetBoolean().Should().BeTrue();

        jsonDoc.RootElement.TryGetProperty("mensaje", out var msgProp).Should().BeTrue();
        msgProp.GetString().Should().Be("Login exitoso");

        jsonDoc.RootElement.TryGetProperty("datos", out var datosProp).Should().BeTrue();
        datosProp.TryGetProperty("token", out _).Should().BeTrue();
    }

    [Fact]
    public void RespuestaApi_Fallida_Serializa_Estructura_Correcta()
    {
        var respuesta = RespuestaApi<object>.Fallida("Error de autenticacion",
            new List<string> { "Credenciales invalidas" });

        var json = JsonSerializer.Serialize(respuesta, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("exito", out var exitoProp).Should().BeTrue();
        exitoProp.GetBoolean().Should().BeFalse();

        jsonDoc.RootElement.TryGetProperty("mensaje", out var msgProp).Should().BeTrue();
        msgProp.GetString().Should().Be("Error de autenticacion");

        jsonDoc.RootElement.TryGetProperty("errores", out var errProp).Should().BeTrue();
        errProp.GetArrayLength().Should().Be(1);
    }

    [Fact]
    public void ProgresoResponseDto_Serializa_Correctamente()
    {
        var original = new ProgresoResponseDto
        {
            Progreso = new { xp = 1000 },
            NivelSiguiente = new { nivel = 6 },
            XpParaSiguiente = 500,
            Porcentaje = 66.7
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        var propiedades = new[] { "progreso", "nivelSiguiente", "xpParaSiguiente", "porcentaje" };
        foreach (var prop in propiedades)
            jsonDoc.RootElement.TryGetProperty(prop, out _).Should().BeTrue($"'{prop}' deberia existir");
    }

    [Fact]
    public void PomodoroRachaResponse_Serializa_Correctamente()
    {
        var original = new PomodoroRachaResponse { Racha = 10 };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("racha", out var prop).Should().BeTrue();
        prop.GetInt32().Should().Be(10);
    }

    [Fact]
    public void PomodoroFinalizarResponse_Serializa_Correctamente()
    {
        var original = new PomodoroFinalizarResponse { XpTotal = 300, SesionGuardada = true };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("xpTotal", out _).Should().BeTrue();
        jsonDoc.RootElement.TryGetProperty("sesionGuardada", out _).Should().BeTrue();
    }

    [Fact]
    public void DashboardKpis_Serializa_Correctamente()
    {
        var original = new DashboardKpis { HabitosPendientes = 5, MisionesPendientes = 3 };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("habitosPendientes", out var hProp).Should().BeTrue();
        hProp.GetInt32().Should().Be(5);
        jsonDoc.RootElement.TryGetProperty("misionesPendientes", out var mProp).Should().BeTrue();
        mProp.GetInt32().Should().Be(3);
    }

    [Fact]
    public void BienestarResumenResponse_Serializa_Correctamente()
    {
        var original = new BienestarResumenResponse
        {
            Alertas = new { hayAlertas = false },
            Frase = new { frase = "Respira" },
            EstadoHoy = new { estado = "Feliz" },
            HabitosPendientes = 2,
            MisionesPendientes = 1
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        var propiedades = new[] { "alertas", "frase", "estadoHoy", "habitosPendientes", "misionesPendientes" };
        foreach (var prop in propiedades)
            jsonDoc.RootElement.TryGetProperty(prop, out _).Should().BeTrue($"'{prop}' deberia existir");
    }

    [Fact]
    public void PomodoroHistorialResponse_Serializa_Correctamente()
    {
        var original = new PomodoroHistorialResponse
        {
            Historial = new[] { new { id = 1, fecha = "2025-01-01" } },
            Pagina = 1,
            Tamano = 20
        };
        var json = JsonSerializer.Serialize(original, JsonOptions);
        var jsonDoc = JsonDocument.Parse(json);

        jsonDoc.RootElement.TryGetProperty("historial", out _).Should().BeTrue();
        jsonDoc.RootElement.TryGetProperty("pagina", out _).Should().BeTrue();
        jsonDoc.RootElement.TryGetProperty("tamano", out _).Should().BeTrue();
    }
}
