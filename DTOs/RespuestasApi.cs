using EpycusApp.Models.Entidades;
using EpycusApp.ViewModels;

namespace EpycusApp.DTOs;

// ── Auth ──
public class AuthResponseDto
{
    public string Token { get; set; } = string.Empty;
    public string RefreshToken { get; set; } = string.Empty;
    public string? Mensaje { get; set; }
}

public class MensajeResponseDto
{
    public string Mensaje { get; set; } = string.Empty;
}

public class SuccessResponseDto
{
    public bool Success { get; set; } = true;
}

// ── Dashboard ──
public class DashboardResumenResponse
{
    public DashboardKpis Kpis { get; set; } = new();
    public int HabitosPendientes { get; set; }
    public int MisionesPendientes { get; set; }
    public FraseResponseDto? Frase { get; set; }
}

public class DashboardKpis
{
    public int HabitosPendientes { get; set; }
    public int MisionesPendientes { get; set; }
}

public class FraseResponseDto
{
    public string Frase { get; set; } = string.Empty;
    public string Autor { get; set; } = string.Empty;
}

// ── Gamificacion ──
public class GamificacionProgresoResponse
{
    public int XpTotal { get; set; }
    public int Nivel { get; set; }
    public string Titulo { get; set; } = string.Empty;
    public int RachaActual { get; set; }
    public int XpParaSiguienteNivel { get; set; }
    public double PorcentajeProgreso { get; set; }
    public string? ImagenPersonaje { get; set; }
}

public class LogroConProgresoResponse
{
    public object? Logro { get; set; }
    public bool Desbloqueado { get; set; }
    public int Progreso { get; set; }
    public int Meta { get; set; }
}

// ── Bienestar ──
public class BienestarResumenResponse
{
    public object? Alertas { get; set; }
    public object? Frase { get; set; }
    public object? EstadoHoy { get; set; }
    public int HabitosPendientes { get; set; }
    public int MisionesPendientes { get; set; }
}

public class AlertasResponse
{
    public object? Alertas { get; set; }
}

public class EstadoHoyResponse
{
    public object? Estado { get; set; }
}

public class HistorialAnimoResponse
{
    public object? Historial { get; set; }
}

public class CantidadResponse
{
    public int Cantidad { get; set; }
}

public class PausaActivaResponse
{
    public RecomendacionPausaDto? Recomendacion { get; set; }
}

// ── Diario ──
public class DiarioEntradaResponseDto
{
    public int Id { get; set; }
    public string Fecha { get; set; } = string.Empty;
    public int EstadoAnimo { get; set; }
    public int NivelEnergia { get; set; }
    public string? DiarioTexto { get; set; }
    public string? PreguntaGuia { get; set; }
    public string? RespuestaGuia { get; set; }
}

public class DiarioEntradaResponse
{
    public DiarioEntradaResponseDto? Entrada { get; set; }
}

public class DiarioEntradasResponse
{
    public List<DiarioEntradaResponseDto>? Entradas { get; set; }
}

public class DiarioRachaResponse
{
    public int DiasConsecutivos { get; set; }
}

public class DiarioPromedioMesResponse
{
    public double? Promedio { get; set; }
}

public class PreguntaGuiaResponse
{
    public string Pregunta { get; set; } = string.Empty;
}

// ── IA ──
public class IaChatResponseDto
{
    public string Respuesta { get; set; } = string.Empty;
    public string? ConversacionId { get; set; }
}

public class IaHistorialResponse
{
    public object? Historial { get; set; }
}

public class IaConversacionesResponse
{
    public object? Conversaciones { get; set; }
}

public class IaSugerenciasResponse
{
    public object? Sugerencias { get; set; }
}

public class IaMensajesHoyResponse
{
    public int Cantidad { get; set; }
}

// ── Pomodoro ──
public class PomodoroIniciarResponse
{
    public int SesionId { get; set; }
    public DateTime FechaInicio { get; set; }
}

public class PomodoroCicloCompletadoResponse
{
    public int XpGanado { get; set; }
    public bool SugerirDescanso { get; set; }
    public string? PausaActiva { get; set; }
}

public class PomodoroFinalizarResponse
{
    public int XpTotal { get; set; }
    public int XpBonus { get; set; }
    public bool SesionGuardada { get; set; }
}

public class PomodoroConfiguracionResponse
{
    public int TiempoEstudio { get; set; }
    public int TiempoDescanso { get; set; }
    public int TiempoDescansoLargo { get; set; }
    public int CiclosAntesDescansoLargo { get; set; }
    public bool SonidoActivo { get; set; }
    public string? SonidoSeleccionado { get; set; }
    public int Volumen { get; set; }
    public bool AutoIniciarDescanso { get; set; }
    public bool AutoIniciarEnfoque { get; set; }
    public bool TicTacActivo { get; set; }
    public int MetaDiariaCiclos { get; set; }
    public int ModoPersonalizadoMin { get; set; }
    public bool VibracionActiva { get; set; }
    public bool NotificacionDesktop { get; set; }
}

public class PomodoroTipResponse
{
    public string? Consejo { get; set; }
}

public class PomodoroSesionActivaResponse
{
    public bool Activa { get; set; }
    public int? SesionId { get; set; }
    public DateTime? FechaInicio { get; set; }
    public int? CiclosCompletados { get; set; }
}

public class SesionPomodoroDto
{
    public int Id { get; set; }
    public DateTime FechaInicio { get; set; }
    public DateTime? FechaFin { get; set; }
    public int CiclosCompletados { get; set; }
    public int XpOtorgado { get; set; }
    public bool FueCompletada { get; set; }
    public string Tipo { get; set; } = string.Empty;
}

public class PomodoroHistorialResponse
{
    public List<SesionPomodoroDto>? Historial { get; set; }
    public int Pagina { get; set; }
    public int Tamano { get; set; }
}

public class PomodoroDescansoRequest
{
    public string Tipo { get; set; } = "DescansoCorto";
    public int Segundos { get; set; }
}

public class PomodoroRachaResponse
{
    public int Racha { get; set; }
}

public class PomodoroEstadisticasAvanzadasResponse
{
    public double PromedioCiclosPorDia { get; set; }
    public int TotalCiclos { get; set; }
    public int TotalMinutos { get; set; }
    public int TotalXp { get; set; }
    public List<EstadisticasPomodoroPeriodo> PorMes { get; set; } = new();
    public List<HeatmapPorHora> HeatmapHoras { get; set; } = new();
}

public class HeatmapPorHora
{
    public int Hora { get; set; }
    public int Ciclos { get; set; }
}

// ── Misiones ──
public class MisionListaItemResponse
{
    public int Id { get; set; }
    public string Nombre { get; set; } = string.Empty;
    public string? Descripcion { get; set; }
    public string? NombreCurso { get; set; }
    public string Prioridad { get; set; } = string.Empty;
    public string Estado { get; set; } = string.Empty;
    public string FechaLimite { get; set; } = string.Empty;
    public int XpOtorgado { get; set; }
    public DateTime FechaCreacion { get; set; }
    public int? CategoriaId { get; set; }
    public int SubTareasCount { get; set; }
    public int SubTareasCompletadas { get; set; }
    public int TiempoEnfoqueSegundos { get; set; }
}

public class MisionCompletarResponse
{
    public int XpGanado { get; set; }
}

// ── Progreso ──
public class ProgresoResponseDto
{
    public object? Progreso { get; set; }
    public object? NivelSiguiente { get; set; }
    public int XpParaSiguiente { get; set; }
    public double Porcentaje { get; set; }
}

// ── Admin ──
public class AdminLoginResponseDto
{
    public string Token { get; set; } = string.Empty;
    public string RefreshToken { get; set; } = string.Empty;
    public string Mensaje { get; set; } = string.Empty;
}
