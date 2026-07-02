using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioGamificacionTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioGamificacion _servicio;
    private readonly Mock<ILogger<ServicioGamificacion>> _loggerMock;

    public ServicioGamificacionTests()
    {
        _contexto = DbContextFactory.CrearContexto("GamificacionTest");
        _loggerMock = new Mock<ILogger<ServicioGamificacion>>();
        _servicio = new ServicioGamificacion(_contexto, _loggerMock.Object);
    }

    // Mismo esquema que SemillaNiveles en producción: los niveles arrancan en 0 y los
    // umbrales acumulados son 100/250/450 (deben coincidir con CalculadorXP).
    private async Task SeedNivelesAsync()
    {
        _contexto.Niveles.AddRange(
            new Nivel { Id = 1, Numero = 0, Titulo = "Novato", XpRequerido = 0 },
            new Nivel { Id = 2, Numero = 1, Titulo = "Curioso", XpRequerido = 100 },
            new Nivel { Id = 3, Numero = 2, Titulo = "Aprendiz", XpRequerido = 250 },
            new Nivel { Id = 4, Numero = 3, Titulo = "Estudiante Comprometido", XpRequerido = 450 }
        );
        await _contexto.SaveChangesAsync();
    }

    private async Task<int> SeedProgresoAsync(int nivelId, int xpTotal, int racha = 0)
    {
        var usuario = new Usuario
        {
            Id = 0,
            CodigoUnico = "TEST001",
            Nombre = "Test",
            CorreoElectronico = "test@test.com",
            ContrasenaHash = "hash",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            RolId = 1,
            CarreraId = 1
        };
        _contexto.Usuarios.Add(usuario);
        await _contexto.SaveChangesAsync();

        var progreso = new ProgresoUsuario
        {
            UsuarioId = usuario.Id,
            NivelActualId = nivelId,
            XpTotal = xpTotal,
            RachaActual = racha,
            RachaMaxima = racha
        };
        _contexto.ProgresosUsuario.Add(progreso);
        await _contexto.SaveChangesAsync();

        return usuario.Id;
    }

    [Fact]
    public async Task SumarXP_SinProgreso_CreaLaFilaYAcumula()
    {
        await SeedNivelesAsync();

        var (xp, subio, nivel) = await _servicio.SumarXP(999, 20);

        xp.Should().Be(20);
        subio.Should().BeFalse();
        nivel.Should().Be(0);

        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == 999);
        progreso.XpTotal.Should().Be(20);
        progreso.NivelActualId.Should().Be(1);
    }

    [Fact]
    public async Task SumarXP_SinProgresoNiNiveles_RetornaCero()
    {
        var (xp, subio, nivel) = await _servicio.SumarXP(999, 100);

        xp.Should().Be(0);
        subio.Should().BeFalse();
        nivel.Should().Be(0);
    }

    [Fact]
    public async Task SumarXP_ConProgreso_AcumulaXP()
    {
        await SeedNivelesAsync();
        var usuarioId = await SeedProgresoAsync(1, 0);

        var (xp, subio, nivel) = await _servicio.SumarXP(usuarioId, 50);

        xp.Should().Be(50);
        subio.Should().BeFalse();
        nivel.Should().Be(0);

        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.XpTotal.Should().Be(50);
    }

    [Fact]
    public async Task SumarXP_SuficienteParaSubirNivel_SubeDeNivel()
    {
        await SeedNivelesAsync();
        var usuarioId = await SeedProgresoAsync(1, 0);

        var (xp, subio, nivel) = await _servicio.SumarXP(usuarioId, 100);

        xp.Should().Be(100);
        subio.Should().BeTrue();
        nivel.Should().Be(1);

        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.NivelActualId.Should().Be(2);
    }

    [Fact]
    public async Task SumarXP_MuchoXP_SubeMultiplesNiveles()
    {
        await SeedNivelesAsync();
        var usuarioId = await SeedProgresoAsync(1, 0);

        var (xp, subio, nivel) = await _servicio.SumarXP(usuarioId, 500);

        subio.Should().BeTrue();
        nivel.Should().Be(3);

        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.NivelActualId.Should().Be(4);
    }

    [Fact]
    public async Task ActualizarRacha_PrimeraVez_IniciaRacha()
    {
        var usuarioId = await SeedProgresoAsync(1, 0);

        await _servicio.ActualizarRacha(usuarioId);

        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.RachaActual.Should().Be(1);
        progreso.RachaMaxima.Should().Be(1);
    }

    [Fact]
    public async Task ActualizarRacha_DiaConsecutivo_IncrementaRacha()
    {
        var usuarioId = await SeedProgresoAsync(1, 0);
        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.FechaUltimaActividad = DateTime.Today.AddDays(-1);
        progreso.RachaActual = 5;
        await _contexto.SaveChangesAsync();

        await _servicio.ActualizarRacha(usuarioId);

        progreso.RachaActual.Should().Be(6);
        progreso.RachaMaxima.Should().Be(6);
    }

    [Fact]
    public async Task ActualizarRacha_MismoDia_NoCambiaRacha()
    {
        var usuarioId = await SeedProgresoAsync(1, 0);
        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.FechaUltimaActividad = DateTime.Today;
        progreso.RachaActual = 5;
        await _contexto.SaveChangesAsync();

        await _servicio.ActualizarRacha(usuarioId);

        progreso.RachaActual.Should().Be(5);
    }

    [Fact]
    public async Task ActualizarRacha_SaltaDosDias_UsaDiaDeGracia()
    {
        var usuarioId = await SeedProgresoAsync(1, 0);
        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.FechaUltimaActividad = DateTime.Today.AddDays(-2);
        progreso.RachaActual = 10;
        await _contexto.SaveChangesAsync();

        await _servicio.ActualizarRacha(usuarioId);

        progreso.DiaDeGraciaUsado.Should().BeTrue();
        progreso.RachaActual.Should().Be(10);
    }

    [Fact]
    public async Task ActualizarRacha_SaltaTresDias_ReiniciaRacha()
    {
        var usuarioId = await SeedProgresoAsync(1, 0);
        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.FechaUltimaActividad = DateTime.Today.AddDays(-3);
        progreso.RachaActual = 10;
        await _contexto.SaveChangesAsync();

        await _servicio.ActualizarRacha(usuarioId);

        progreso.RachaActual.Should().Be(0);
        progreso.DiaDeGraciaUsado.Should().BeFalse();
    }

    [Fact]
    public async Task VerificarYOtorgarLogros_XpTotal_CumpleLogro()
    {
        await SeedNivelesAsync();
        _contexto.Logros.Add(new Logro
        {
            Id = 1,
            Nombre = "100 XP",
            CondicionTipo = "XpTotal",
            CondicionValor = 100,
            XpRecompensa = 10,
            EstaActivo = true
        });
        await _contexto.SaveChangesAsync();

        var usuarioId = await SeedProgresoAsync(1, 150);

        await _servicio.VerificarYOtorgarLogros(usuarioId);

        var logrosUsuario = await _contexto.LogrosUsuario.Where(lu => lu.UsuarioId == usuarioId).ToListAsync();
        logrosUsuario.Should().ContainSingle(lu => lu.LogroId == 1);

        var progreso = await _contexto.ProgresosUsuario.FirstAsync(p => p.UsuarioId == usuarioId);
        progreso.XpTotal.Should().Be(160);
    }

    [Fact]
    public async Task VerificarYOtorgarLogros_RachaDias_CumpleLogro()
    {
        await SeedNivelesAsync();
        _contexto.Logros.Add(new Logro
        {
            Id = 1,
            Nombre = "Racha de 7",
            CondicionTipo = "RachaDias",
            CondicionValor = 7,
            XpRecompensa = 50,
            EstaActivo = true
        });
        await _contexto.SaveChangesAsync();

        var usuarioId = await SeedProgresoAsync(1, 100, racha: 7);

        await _servicio.VerificarYOtorgarLogros(usuarioId);

        var logrosUsuario = await _contexto.LogrosUsuario.Where(lu => lu.UsuarioId == usuarioId).ToListAsync();
        logrosUsuario.Should().ContainSingle(lu => lu.LogroId == 1);
    }

    [Fact]
    public async Task VerificarYOtorgarLogros_NivelAlcanzado_CumpleLogro()
    {
        await SeedNivelesAsync();
        _contexto.Logros.Add(new Logro
        {
            Id = 1,
            Nombre = "Nivel 2",
            CondicionTipo = "NivelAlcanzado",
            CondicionValor = 2,
            EstaActivo = true
        });
        await _contexto.SaveChangesAsync();

        var usuarioId = await SeedProgresoAsync(3, 250);

        await _servicio.VerificarYOtorgarLogros(usuarioId);

        var logrosUsuario = await _contexto.LogrosUsuario.Where(lu => lu.UsuarioId == usuarioId).ToListAsync();
        logrosUsuario.Should().ContainSingle(lu => lu.LogroId == 1);
    }

    [Fact]
    public async Task CalcularProductividadDiaria_SinHabitos_RetornaCero()
    {
        var usuarioId = await SeedProgresoAsync(1, 0);

        var productividad = await _servicio.CalcularProductividadDiaria(usuarioId);

        productividad.Should().Be(0);
    }

    [Fact]
    public async Task CalcularProductividadDiaria_ConHabitosDiarios_CalculaPorcentaje()
    {
        await SeedNivelesAsync();
        var usuarioId = await SeedProgresoAsync(1, 0);

        _contexto.Categorias.Add(new Categoria { Id = 1, Nombre = "Salud", Tipo = "Habito", EstaActiva = true });
        await _contexto.SaveChangesAsync();

        _contexto.Habitos.AddRange(
            new Habito { Id = 1, Nombre = "Lectura", Frecuencia = "Diaria", EstaActivo = true, UsuarioId = usuarioId, CategoriaId = 1 },
            new Habito { Id = 2, Nombre = "Ejercicio", Frecuencia = "Diaria", EstaActivo = true, UsuarioId = usuarioId, CategoriaId = 1 },
            new Habito { Id = 3, Nombre = "Meditar", Frecuencia = "Diaria", EstaActivo = true, UsuarioId = usuarioId, CategoriaId = 1 }
        );
        await _contexto.SaveChangesAsync();

        var hoy = DateOnly.FromDateTime(DateTime.Today);
        _contexto.RegistrosHabito.Add(
            new RegistroHabito { HabitoId = 1, Fecha = hoy, Estado = "Completado" }
        );
        _contexto.RegistrosHabito.Add(
            new RegistroHabito { HabitoId = 2, Fecha = hoy, Estado = "Completado" }
        );
        await _contexto.SaveChangesAsync();

        var productividad = await _servicio.CalcularProductividadDiaria(usuarioId);

        productividad.Should().Be(66.7m);
    }
}
