using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using EpycusApp.ViewModels;
using FluentAssertions;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using Moq;

namespace EpycusApp.Tests.Unitarios.Servicios;

public class ServicioPerfilTests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioPerfil _servicio;
    private readonly Mock<ILogger<ServicioPerfil>> _loggerMock;

    public ServicioPerfilTests()
    {
        _contexto = DbContextFactory.CrearContexto("PerfilTest");
        _loggerMock = new Mock<ILogger<ServicioPerfil>>();
        _servicio = new ServicioPerfil(_contexto, _loggerMock.Object);
    }

    private async Task<int> SeedUsuarioAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Ingeniería", Area = "STEM", Codigo = "ING" });
        _contexto.Niveles.Add(new Nivel { Id = 1, Numero = 1, Titulo = "Novato", XpRequerido = 0 });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "PER001",
            Nombre = "Perfil Test",
            CorreoElectronico = "perfil@test.com",
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
    public async Task ObtenerPerfil_Existente_RetornaUsuario()
    {
        var usuarioId = await SeedUsuarioAsync();

        var resultado = await _servicio.ObtenerPerfil(usuarioId);

        resultado.Should().NotBeNull();
        resultado!.Nombre.Should().Be("Perfil Test");
    }

    [Fact]
    public async Task ObtenerPerfil_NoExistente_RetornaNull()
    {
        var resultado = await _servicio.ObtenerPerfil(999);
        resultado.Should().BeNull();
    }

    [Fact]
    public async Task ObtenerPerfilCompletoAsync_Existente_RetornaViewModel()
    {
        var usuarioId = await SeedUsuarioAsync();

        var resultado = await _servicio.ObtenerPerfilCompletoAsync(usuarioId);

        resultado.Should().NotBeNull();
        resultado!.Nombre.Should().Be("Perfil Test");
        resultado.CarreraNombre.Should().Be("Ingeniería");
    }

    [Fact]
    public async Task ActualizarPerfilAsync_ActualizaNombre()
    {
        var usuarioId = await SeedUsuarioAsync();
        var modelo = new ActualizarPerfilViewModel { Nombre = "Nuevo Nombre" };

        var resultado = await _servicio.ActualizarPerfilAsync(usuarioId, modelo);

        resultado.EsExitoso.Should().BeTrue();
        var usuario = await _contexto.Usuarios.FirstAsync(u => u.Id == usuarioId);
        usuario.Nombre.Should().Be("Nuevo Nombre");
    }

    [Fact]
    public async Task ActualizarPerfilAsync_UsuarioNoExistente_RetornaFallo()
    {
        var modelo = new ActualizarPerfilViewModel { Nombre = "Test" };

        var resultado = await _servicio.ActualizarPerfilAsync(999, modelo);

        resultado.EsExitoso.Should().BeFalse();
    }

    [Fact]
    public async Task CambiarPersonaje_SeleccionaPersonaje()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Personajes.Add(new Personaje { Id = 1, Nombre = "Guerrero", Genero = "Masculino", EstaActivo = true });
        await _contexto.SaveChangesAsync();

        await _servicio.CambiarPersonaje(1, usuarioId);

        var personajeUsuario = await _contexto.PersonajesUsuario.FirstAsync(pu => pu.UsuarioId == usuarioId);
        personajeUsuario.PersonajeId.Should().Be(1);
        personajeUsuario.EstaSeleccionado.Should().BeTrue();
    }

    [Fact]
    public async Task CambiarPersonaje_PersonajeInactivo_LanzaExcepcion()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Personajes.Add(new Personaje { Id = 1, Nombre = "Inactivo", Genero = "Masculino", EstaActivo = false });
        await _contexto.SaveChangesAsync();

        await _servicio.Invoking(s => s.CambiarPersonaje(1, usuarioId))
            .Should().ThrowAsync<KeyNotFoundException>();
    }

    [Fact]
    public async Task CambiarTemaAsync_ActualizaTema()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.Temas.Add(new Tema { Id = 1, Nombre = "Oscuro", Modo = "dark", ArchivoCss = "dark.css", EstaActivo = true });
        await _contexto.SaveChangesAsync();

        var resultado = await _servicio.CambiarTemaAsync(usuarioId, 1);

        resultado.EsExitoso.Should().BeTrue();
        var temaUsuario = await _contexto.TemasUsuario.FirstAsync(tu => tu.UsuarioId == usuarioId);
        temaUsuario.TemaId.Should().Be(1);
    }
}
