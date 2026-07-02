using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.Tests.AyudantesTests;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Moq;
using Xunit;

namespace EpycusApp.Tests.Integracion;

public class EliminarCuentaTests : IDisposable
{
    private readonly ContextoAplicacion _contexto;
    private readonly ServicioAutenticacion _servicio;

    public EliminarCuentaTests()
    {
        _contexto = DbContextFactory.CrearContexto("EliminarCuenta");
        var correoMock = new Mock<IServicioCorreo>();
        var loggerMock = new Mock<ILogger<ServicioAutenticacion>>();
        var auditoriaMock = new Mock<IServicioAuditoria>();
        var config = new ConfigurationBuilder().AddInMemoryCollection(new Dictionary<string, string?>()).Build();

        _servicio = new ServicioAutenticacion(_contexto, config, correoMock.Object, auditoriaMock.Object, loggerMock.Object);

        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        _contexto.Categorias.Add(new Categoria { Id = 1, Nombre = "General", Icono = "star", Tipo = "Mision" });
        _contexto.SaveChanges();
    }

    public void Dispose() => _contexto.Dispose();

    private Usuario CrearUsuarioConDatos(string? contrasenaHash, string correo)
    {
        var usuario = new Usuario
        {
            CodigoUnico = Guid.NewGuid().ToString("N"),
            Nombre = "Usuario Prueba",
            CorreoElectronico = correo,
            ContrasenaHash = contrasenaHash,
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Otro",
            RolId = 1,
            CarreraId = 1,
        };
        _contexto.Usuarios.Add(usuario);
        _contexto.SaveChanges();

        _contexto.Misiones.Add(new Mision
        {
            Nombre = "Mision de prueba",
            UsuarioId = usuario.Id,
            CategoriaId = 1,
            SubTareas = new List<SubTarea> { new SubTarea { Nombre = "Sub-tarea de prueba" } }
        });
        _contexto.Habitos.Add(new Habito
        {
            Nombre = "Habito de prueba",
            UsuarioId = usuario.Id,
            CategoriaId = 1
        });
        _contexto.TokensRefresh.Add(new TokenRefresh
        {
            Token = "token-de-prueba",
            ExpiraEn = DateTime.UtcNow.AddDays(7),
            UsuarioId = usuario.Id
        });
        _contexto.SaveChanges();

        return usuario;
    }

    [Fact]
    public async Task EliminarCuentaAsync_ConContrasenaCorrecta_BorraUsuarioYDatosRelacionados()
    {
        var hash = BCrypt.Net.BCrypt.HashPassword("MiClaveSegura123!", workFactor: 4);
        var usuario = CrearUsuarioConDatos(hash, $"borrar_{Guid.NewGuid():N}@test.com");
        int usuarioId = usuario.Id;

        var (exito, mensaje) = await _servicio.EliminarCuentaAsync(usuarioId, "MiClaveSegura123!");

        Assert.True(exito, mensaje);
        Assert.False(await _contexto.Usuarios.AnyAsync(u => u.Id == usuarioId));
        Assert.False(await _contexto.Misiones.AnyAsync(m => m.UsuarioId == usuarioId));
        Assert.False(await _contexto.SubTareas.AnyAsync(st => st.Mision!.UsuarioId == usuarioId));
        Assert.False(await _contexto.Habitos.AnyAsync(h => h.UsuarioId == usuarioId));
        Assert.False(await _contexto.TokensRefresh.AnyAsync(t => t.UsuarioId == usuarioId));
    }

    [Fact]
    public async Task EliminarCuentaAsync_ConContrasenaIncorrecta_NoBorraYRetornaError()
    {
        var hash = BCrypt.Net.BCrypt.HashPassword("MiClaveSegura123!", workFactor: 4);
        var usuario = CrearUsuarioConDatos(hash, $"nobrar_{Guid.NewGuid():N}@test.com");
        int usuarioId = usuario.Id;

        var (exito, mensaje) = await _servicio.EliminarCuentaAsync(usuarioId, "ClaveIncorrecta");

        Assert.False(exito);
        Assert.True(await _contexto.Usuarios.AnyAsync(u => u.Id == usuarioId));
    }

    [Fact]
    public async Task EliminarCuentaAsync_CuentaGoogleSinContrasena_NoRequiereContrasena()
    {
        var usuario = CrearUsuarioConDatos(null, $"google_{Guid.NewGuid():N}@test.com");
        int usuarioId = usuario.Id;

        var (exito, mensaje) = await _servicio.EliminarCuentaAsync(usuarioId, null);

        Assert.True(exito, mensaje);
        Assert.False(await _contexto.Usuarios.AnyAsync(u => u.Id == usuarioId));
    }

    [Fact]
    public async Task EliminarCuentaAsync_UsuarioInexistente_RetornaError()
    {
        var (exito, mensaje) = await _servicio.EliminarCuentaAsync(999999, "cualquiera");

        Assert.False(exito);
        Assert.Equal("Usuario no encontrado", mensaje);
    }
}
