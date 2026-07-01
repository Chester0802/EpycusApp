using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Hubs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.AspNetCore.SignalR;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioHabitosTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioHabitos _servicio;
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private readonly Mock<ILogger<ServicioHabitos>> _loggerMock;

    public ServicioHabitosTests()
    {
        _contexto = DbContextFactory.CrearContexto("HabitosTest");
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _loggerMock = new Mock<ILogger<ServicioHabitos>>();
        var hubMock = new Mock<IHubContext<NotificacionesHub>>();
        _servicio = new ServicioHabitos(_contexto, _gamificacionMock.Object, hubMock.Object, _loggerMock.Object);
    }

    private async Task<int> SeedBaseAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        _contexto.Categorias.Add(new Categoria { Id = 1, Nombre = "Salud", Tipo = "Habito", EstaActiva = true });
        _contexto.Niveles.Add(new Nivel { Id = 1, Numero = 1, Titulo = "Novato", XpRequerido = 0 });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "HAB001",
            Nombre = "Habitos Test",
            CorreoElectronico = "habitos@test.com",
            ContrasenaHash = "hash",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            RolId = 1,
            CarreraId = 1
        };
        _contexto.Usuarios.Add(usuario);
        await _contexto.SaveChangesAsync();
        return usuario.Id;
    }

    [Fact]
    public async Task CrearHabito_ConDatosValidos_CreaHabito()
    {
        var usuarioId = await SeedBaseAsync();
        var modelo = new CrearHabitoViewModel
        {
            Nombre = "Correr",
            Descripcion = "Correr 30 min",
            Frecuencia = "Diaria",
            CategoriaId = 1,
            ConPomodoro = false
        };

        await _servicio.CrearHabito(modelo, usuarioId);

        var habitos = await _contexto.Habitos.ToListAsync();
        habitos.Should().ContainSingle();
        habitos[0].Nombre.Should().Be("Correr");
        habitos[0].UsuarioId.Should().Be(usuarioId);
    }

    [Fact]
    public async Task ObtenerHabitosDeUsuario_RetornaSoloSusHabitos()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.AddRange(
            new Habito { Nombre = "Hábito 1", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1, EstaActivo = true },
            new Habito { Nombre = "Hábito 2", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1, EstaActivo = true },
            new Habito { Nombre = "De otro", Frecuencia = "Diaria", UsuarioId = 999, CategoriaId = 1, EstaActivo = true }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerHabitosDeUsuario(usuarioId);

        resultado.Should().HaveCount(2);
    }

    [Fact]
    public async Task ObtenerPorId_Existente_RetornaHabito()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.Add(new Habito { Id = 1, Nombre = "Test", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1 });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerPorId(1);

        resultado.Should().NotBeNull();
        resultado!.Nombre.Should().Be("Test");
    }

    [Fact]
    public async Task EditarHabito_ActualizaCampos()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.Add(new Habito { Id = 1, Nombre = "Viejo", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1 });
        await _contexto.SaveChangesAsync();

        var modelo = new EditarHabitoViewModel
        {
            Id = 1,
            Nombre = "Nuevo nombre",
            Descripcion = "Actualizado",
            Frecuencia = "Semanal",
            CategoriaId = 1,
            DiasSemana = new List<int> { 1, 3, 5 }
        };

        await _servicio.EditarHabito(modelo, usuarioId);

        var habito = await _contexto.Habitos.FirstAsync(h => h.Id == 1);
        habito.Nombre.Should().Be("Nuevo nombre");
    }

    [Fact]
    public async Task EliminarHabito_RemueveHabito()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.Add(new Habito { Id = 1, Nombre = "Eliminar", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1 });
        await _contexto.SaveChangesAsync();

        await _servicio.EliminarHabito(1, usuarioId);

        var habitos = await _contexto.Habitos.ToListAsync();
        habitos.Should().BeEmpty();
    }

    [Fact]
    public async Task CompletarHabito_OtorgaXPYActualizaRacha()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.Add(new Habito { Id = 1, Nombre = "Completar", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1, RachaActual = 0, RachaMaxima = 0 });
        await _contexto.SaveChangesAsync();

        _gamificacionMock.Setup(g => g.SumarXP(usuarioId, ConstantesGamificacion.XP_BASE_HABITO))
            .ReturnsAsync((ConstantesGamificacion.XP_BASE_HABITO, false, 1));
        _gamificacionMock.Setup(g => g.ActualizarRacha(usuarioId))
            .Returns(Task.CompletedTask);

        var resultado = await _servicio.CompletarHabito(1, usuarioId);

        resultado.Exito.Should().BeTrue();
        resultado.XpGanado.Should().Be(ConstantesGamificacion.XP_BASE_HABITO);

        var registro = await _contexto.RegistrosHabito.FirstOrDefaultAsync(r => r.HabitoId == 1);
        registro.Should().NotBeNull();
        registro!.Estado.Should().Be("Completado");
    }

    [Fact]
    public async Task CompletarHabito_YaCompletadoHoy_RetornaFalso()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.Add(new Habito { Id = 1, Nombre = "Completar", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1 });
        _contexto.RegistrosHabito.Add(new RegistroHabito { HabitoId = 1, Fecha = DateOnly.FromDateTime(DateTime.Today), Estado = "Completado" });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.CompletarHabito(1, usuarioId);

        resultado.Exito.Should().BeFalse();
    }

    [Fact]
    public async Task ObtenerDashboard_CalculaEstadisticas()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.AddRange(
            new Habito { Id = 1, Nombre = "H1", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1, RachaActual = 3, RachaMaxima = 5 },
            new Habito { Id = 2, Nombre = "H2", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1, RachaActual = 7, RachaMaxima = 10 }
        );
        await _contexto.SaveChangesAsync();

        var dashboard = await _servicio.ObtenerDashboard(usuarioId);

        dashboard.TotalHabitos.Should().Be(2);
        dashboard.RachaActualMaxima.Should().Be(7);
        dashboard.MejoresRachas.Should().HaveCount(2);
    }

    [Fact]
    public async Task FallarHabito_RegistraComoFallido()
    {
        var usuarioId = await SeedBaseAsync();
        _contexto.Habitos.Add(new Habito { Id = 1, Nombre = "Fallar", Frecuencia = "Diaria", UsuarioId = usuarioId, CategoriaId = 1, RachaActual = 5 });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.FallarHabito(1, usuarioId);

        resultado.Exito.Should().BeTrue();
        var registro = await _contexto.RegistrosHabito.FirstAsync(r => r.HabitoId == 1);
        registro.Estado.Should().Be("Fallido");
    }

    [Fact]
    public async Task ObtenerCategoriasActivas_RetornaSoloActivas()
    {
        _contexto.Categorias.AddRange(
            new Categoria { Id = 1, Nombre = "Activa", Tipo = "Habito", EstaActiva = true },
            new Categoria { Id = 2, Nombre = "Inactiva", Tipo = "Habito", EstaActiva = false }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerCategoriasActivas();

        resultado.Should().ContainSingle();
        resultado[0].Nombre.Should().Be("Activa");
    }
}
