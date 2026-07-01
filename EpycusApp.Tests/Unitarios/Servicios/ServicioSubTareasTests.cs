using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Hubs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Microsoft.AspNetCore.SignalR;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioSubTareasTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioMisiones _servicio;
    private readonly Mock<IServicioGamificacion> _gamificacionMock;
    private readonly Mock<ILogger<ServicioMisiones>> _loggerMock;

    public ServicioSubTareasTests()
    {
        _contexto = DbContextFactory.CrearContexto("SubTareasTest");
        _gamificacionMock = new Mock<IServicioGamificacion>();
        _loggerMock = new Mock<ILogger<ServicioMisiones>>();
        var hubMock = new Mock<IHubContext<NotificacionesHub>>();
        _servicio = new ServicioMisiones(_contexto, _gamificacionMock.Object, hubMock.Object, _loggerMock.Object);
    }

    private async Task<int> SeedMisionPendienteAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST", EstaActiva = true });
        _contexto.Categorias.Add(new Categoria { Id = 1, Nombre = "Academico", Tipo = "Mision", EstaActiva = true });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "SUB001",
            Nombre = "SubTareas Test",
            CorreoElectronico = "subtareas@test.com",
            ContrasenaHash = "hash",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Masculino",
            RolId = 1,
            CarreraId = 1
        };
        _contexto.Usuarios.Add(usuario);
        await _contexto.SaveChangesAsync();

        var mision = new Mision
        {
            Nombre = "Mision Test",
            Estado = "Pendiente",
            Prioridad = "Media",
            UsuarioId = usuario.Id,
            CategoriaId = 1,
            FechaLimite = new DateOnly(2025, 12, 31),
            FechaCreacion = DateTime.UtcNow
        };
        _contexto.Misiones.Add(mision);
        await _contexto.SaveChangesAsync();
        return usuario.Id;
    }

    [Fact]
    public async Task CrearSubTarea_EnMisionValida_CreaCorrectamente()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();

        await _servicio.CrearSubTarea("Estudiar capitulo 1", null, mision.Id, usuarioId);

        var subTareas = await _contexto.SubTareas.ToListAsync();
        subTareas.Should().ContainSingle();
        subTareas[0].Nombre.Should().Be("Estudiar capitulo 1");
        subTareas[0].Orden.Should().Be(0);
        subTareas[0].MisionId.Should().Be(mision.Id);
    }

    [Fact]
    public async Task CrearSubTarea_EnMisionCompletada_LanzaError()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        mision.Estado = "Completado";
        await _contexto.SaveChangesAsync();

        await _servicio.Invoking(s => s.CrearSubTarea("Test", null, mision.Id, usuarioId))
            .Should().ThrowAsync<Exception>();
    }

    [Fact]
    public async Task CrearSubTarea_EnMisionFallida_LanzaError()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        mision.Estado = "Fallido";
        await _contexto.SaveChangesAsync();

        await _servicio.Invoking(s => s.CrearSubTarea("Test", null, mision.Id, usuarioId))
            .Should().ThrowAsync<Exception>();
    }

    [Fact]
    public async Task CompletarSubTarea_UnicaPendiente_CompletaMision()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        _contexto.SubTareas.Add(new SubTarea
        {
            Nombre = "Unica tarea",
            MisionId = mision.Id,
            Orden = 0,
            FechaCreacion = DateTime.UtcNow
        });
        await _contexto.SaveChangesAsync();

        _gamificacionMock.Setup(g => g.SumarXP(usuarioId, ConstantesGamificacion.XP_MISION_MEDIA))
            .ReturnsAsync((ConstantesGamificacion.XP_MISION_MEDIA, false, 1));

        var subTarea = await _contexto.SubTareas.FirstAsync();
        await _servicio.CompletarSubTarea(subTarea.Id, usuarioId);

        var misionActualizada = await _contexto.Misiones.FirstAsync();
        misionActualizada.Estado.Should().Be("Completado");
        misionActualizada.FechaCompletado.Should().NotBeNull();
    }

    [Fact]
    public async Task CompletarSubTarea_NoEsUltima_NoCompletaMision()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        _contexto.SubTareas.AddRange(
            new SubTarea { Nombre = "Tarea 1", MisionId = mision.Id, Orden = 0, FechaCreacion = DateTime.UtcNow },
            new SubTarea { Nombre = "Tarea 2", MisionId = mision.Id, Orden = 1, FechaCreacion = DateTime.UtcNow }
        );
        await _contexto.SaveChangesAsync();

        var subTarea = await _contexto.SubTareas.FirstAsync();
        await _servicio.CompletarSubTarea(subTarea.Id, usuarioId);

        var misionActualizada = await _contexto.Misiones.FirstAsync();
        misionActualizada.Estado.Should().Be("Pendiente");

        var subTareas = await _contexto.SubTareas.ToListAsync();
        subTareas.Count(st => st.EstaCompletada).Should().Be(1);
    }

    [Fact]
    public async Task EliminarSubTarea_EnMisionPendiente_Elimina()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        _contexto.SubTareas.Add(new SubTarea
        {
            Nombre = "Tarea a eliminar",
            MisionId = mision.Id,
            Orden = 0,
            FechaCreacion = DateTime.UtcNow
        });
        await _contexto.SaveChangesAsync();

        var subTarea = await _contexto.SubTareas.FirstAsync();
        await _servicio.EliminarSubTarea(subTarea.Id, usuarioId);

        var subTareas = await _contexto.SubTareas.ToListAsync();
        subTareas.Should().BeEmpty();
    }

    [Fact]
    public async Task EliminarSubTarea_EnMisionCompletada_LanzaError()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        mision.Estado = "Completado";
        await _contexto.SaveChangesAsync();
        _contexto.SubTareas.Add(new SubTarea
        {
            Nombre = "Tarea",
            MisionId = mision.Id,
            Orden = 0,
            FechaCreacion = DateTime.UtcNow
        });
        await _contexto.SaveChangesAsync();

        var subTarea = await _contexto.SubTareas.FirstAsync();
        await _servicio.Invoking(s => s.EliminarSubTarea(subTarea.Id, usuarioId))
            .Should().ThrowAsync<Exception>();
    }

    [Fact]
    public async Task ObtenerSubTareas_OrdenCorrecto()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        _contexto.SubTareas.AddRange(
            new SubTarea { Nombre = "B", MisionId = mision.Id, Orden = 2, FechaCreacion = DateTime.UtcNow },
            new SubTarea { Nombre = "A", MisionId = mision.Id, Orden = 1, FechaCreacion = DateTime.UtcNow },
            new SubTarea { Nombre = "C", MisionId = mision.Id, Orden = 1, FechaCreacion = DateTime.UtcNow.AddMinutes(1) }
        );
        await _contexto.SaveChangesAsync();

        var subTareas = await _servicio.ObtenerSubTareas(mision.Id, usuarioId);

        subTareas.Should().HaveCount(3);
        subTareas[0].Nombre.Should().Be("A");
        subTareas[1].Nombre.Should().Be("C");
        subTareas[2].Nombre.Should().Be("B");
    }

    [Fact]
    public async Task DescompletarSubTarea_RevertirCompletado()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        _contexto.SubTareas.Add(new SubTarea
        {
            Nombre = "Tarea",
            MisionId = mision.Id,
            EstaCompletada = true,
            FechaCompletado = DateTime.UtcNow,
            Orden = 0,
            FechaCreacion = DateTime.UtcNow
        });
        await _contexto.SaveChangesAsync();

        var subTarea = await _contexto.SubTareas.FirstAsync();
        await _servicio.DescompletarSubTarea(subTarea.Id, usuarioId);

        var actualizada = await _contexto.SubTareas.FirstAsync();
        actualizada.EstaCompletada.Should().BeFalse();
        actualizada.FechaCompletado.Should().BeNull();
    }

    [Fact]
    public async Task EditarSubTarea_ActualizaCampos()
    {
        var usuarioId = await SeedMisionPendienteAsync();
        var mision = await _contexto.Misiones.FirstAsync();
        _contexto.SubTareas.Add(new SubTarea
        {
            Nombre = "Original",
            MisionId = mision.Id,
            Orden = 0,
            FechaCreacion = DateTime.UtcNow
        });
        await _contexto.SaveChangesAsync();

        var subTarea = await _contexto.SubTareas.FirstAsync();
        await _servicio.EditarSubTarea(subTarea.Id, "Actualizado", "Nueva desc", 5, usuarioId);

        var actualizada = await _contexto.SubTareas.FirstAsync();
        actualizada.Nombre.Should().Be("Actualizado");
        actualizada.Descripcion.Should().Be("Nueva desc");
        actualizada.Orden.Should().Be(5);
    }
}
