using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioMisionesTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioMisiones _servicio;
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private readonly Mock<ILogger<ServicioMisiones>> _loggerMock;

    public ServicioMisionesTests()
    {
        _contexto = DbContextFactory.CrearContexto("MisionesTest");
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _loggerMock = new Mock<ILogger<ServicioMisiones>>();
        _servicio = new ServicioMisiones(_contexto, _gamificacionMock.Object, _loggerMock.Object);
    }

    private async Task<int> SeedUsuarioConCategoriaAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST", EstaActiva = true });
        _contexto.Categorias.Add(new Categoria { Id = 1, Nombre = "Académico", Tipo = "Mision", EstaActiva = true });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "MIS001",
            Nombre = "Misiones Test",
            CorreoElectronico = "misiones@test.com",
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
    public async Task CrearMision_ConDatosValidos_CreaMision()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        var modelo = new CrearMisionViewModel
        {
            Nombre = "Examen Final",
            Descripcion = "Estudiar para el examen final",
            NombreCurso = "Matemáticas",
            FechaLimite = DateTime.Today.AddDays(7),
            Prioridad = "Alta",
            ConPomodoro = true,
            CategoriaId = 1
        };

        await _servicio.CrearMision(modelo, usuarioId);

        var misiones = await _contexto.Misiones.ToListAsync();
        misiones.Should().ContainSingle();
        misiones[0].Nombre.Should().Be("Examen Final");
        misiones[0].Estado.Should().Be("Pendiente");
        misiones[0].UsuarioId.Should().Be(usuarioId);
    }

    [Fact]
    public async Task ObtenerMisionesDeUsuario_RetornaMisionesOrdenadas()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.AddRange(
            new Mision { Nombre = "Misión A", Estado = "Pendiente", FechaLimite = new DateOnly(2025, 12, 31), UsuarioId = usuarioId, CategoriaId = 1, Prioridad = "Media" },
            new Mision { Nombre = "Misión B", Estado = "Completado", FechaLimite = new DateOnly(2025, 12, 1), UsuarioId = usuarioId, CategoriaId = 1, Prioridad = "Alta" }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerMisionesDeUsuario(usuarioId);

        resultado.Should().HaveCount(2);
        resultado[0].Nombre.Should().Be("Misión A");
    }

    [Fact]
    public async Task ObtenerPorId_Existente_RetornaMision()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.Add(new Mision { Id = 1, Nombre = "Misión Test", UsuarioId = usuarioId, CategoriaId = 1, Prioridad = "Media", FechaLimite = new DateOnly(2025, 12, 31) });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerPorId(1);

        resultado.Should().NotBeNull();
        resultado!.Nombre.Should().Be("Misión Test");
    }

    [Fact]
    public async Task EditarMision_ActualizaCampos()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.Add(new Mision { Id = 1, Nombre = "Original", Estado = "Pendiente", UsuarioId = usuarioId, CategoriaId = 1, Prioridad = "Baja", FechaLimite = new DateOnly(2025, 12, 31) });
        await _contexto.SaveChangesAsync();

        var modelo = new EditarMisionViewModel
        {
            Id = 1,
            Nombre = "Actualizado",
            Descripcion = "Nueva descripción",
            FechaLimite = DateTime.Today.AddDays(14),
            Prioridad = "Alta",
            ConPomodoro = true,
            CategoriaId = 1
        };

        await _servicio.EditarMision(modelo, usuarioId);

        var mision = await _contexto.Misiones.FirstAsync(m => m.Id == 1);
        mision.Nombre.Should().Be("Actualizado");
        mision.Prioridad.Should().Be("Alta");
    }

    [Fact]
    public async Task EditarMision_DeOtroUsuario_LanzaExcepcion()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.Add(new Mision { Id = 1, Nombre = "Misión", Estado = "Pendiente", UsuarioId = 999, CategoriaId = 1, Prioridad = "Media", FechaLimite = new DateOnly(2025, 12, 31) });
        await _contexto.SaveChangesAsync();

        var modelo = new EditarMisionViewModel { Id = 1, Nombre = "Hack", FechaLimite = DateTime.Today, Prioridad = "Alta", CategoriaId = 1 };

        await _servicio.Invoking(s => s.EditarMision(modelo, usuarioId))
            .Should().ThrowAsync<Exception>();
    }

    [Fact]
    public async Task CompletarMision_Pendiente_OtorgaXP()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.Add(new Mision { Id = 1, Nombre = "Misión XP", Estado = "Pendiente", Prioridad = "Alta", UsuarioId = usuarioId, CategoriaId = 1, FechaLimite = new DateOnly(2025, 12, 31) });
        await _contexto.SaveChangesAsync();

        _gamificacionMock.Setup(g => g.SumarXP(usuarioId, ConstantesGamificacion.XP_MISION_ALTA))
            .ReturnsAsync((ConstantesGamificacion.XP_MISION_ALTA, false, 1));

        var resultado = await _servicio.CompletarMision(1, usuarioId);

        resultado.Exito.Should().BeTrue();
        resultado.XpGanado.Should().Be(ConstantesGamificacion.XP_MISION_ALTA);

        var mision = await _contexto.Misiones.FirstAsync(m => m.Id == 1);
        mision.Estado.Should().Be("Completado");
        mision.FechaCompletado.Should().NotBeNull();
    }

    [Fact]
    public async Task CompletarMision_YaCompletada_RetornaFalso()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.Add(new Mision { Id = 1, Nombre = "Hecha", Estado = "Completado", UsuarioId = usuarioId, CategoriaId = 1, Prioridad = "Media", FechaLimite = new DateOnly(2025, 12, 31) });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.CompletarMision(1, usuarioId);

        resultado.Exito.Should().BeFalse();
    }

    [Fact]
    public async Task EliminarMision_Existente_RemueveMision()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.Add(new Mision { Id = 1, Nombre = "Eliminar", UsuarioId = usuarioId, CategoriaId = 1, Prioridad = "Media", FechaLimite = new DateOnly(2025, 12, 31) });
        await _contexto.SaveChangesAsync();

        await _servicio.EliminarMision(1, usuarioId);

        var misiones = await _contexto.Misiones.ToListAsync();
        misiones.Should().BeEmpty();
    }

    [Fact]
    public async Task CambiarEstado_TransicionValida_CambiaEstado()
    {
        var usuarioId = await SeedUsuarioConCategoriaAsync();
        _contexto.Misiones.Add(new Mision { Id = 1, Nombre = "Progreso", Estado = "Pendiente", UsuarioId = usuarioId, CategoriaId = 1, Prioridad = "Media", FechaLimite = new DateOnly(2025, 12, 31) });
        await _contexto.SaveChangesAsync();

        await _servicio.CambiarEstado(1, "EnProgreso", usuarioId);

        var mision = await _contexto.Misiones.FirstAsync(m => m.Id == 1);
        mision.Estado.Should().Be("EnProgreso");
    }

    [Fact]
    public async Task ObtenerCategoriasMisionAsync_RetornaCategoriasFiltradas()
    {
        _contexto.Categorias.AddRange(
            new Categoria { Id = 1, Nombre = "Misión", Tipo = "Mision", EstaActiva = true },
            new Categoria { Id = 2, Nombre = "Ambos", Tipo = "Ambos", EstaActiva = true },
            new Categoria { Id = 3, Nombre = "Solo Habito", Tipo = "Habito", EstaActiva = true }
        );
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.ObtenerCategoriasMisionAsync();

        resultado.Should().HaveCount(2);
        resultado.Should().Contain(c => c.Id == 1);
        resultado.Should().Contain(c => c.Id == 2);
    }
}
