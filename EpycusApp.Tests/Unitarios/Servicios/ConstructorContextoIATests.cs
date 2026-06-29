using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Implementaciones;
using EpycusApp.Tests.AyudantesTests;
using FluentAssertions;
using Xunit;

namespace EpycusApp.Tests.Unitarios.Servicios;

[Trait("Categoria", "Unitario")]
public class ConstructorContextoIATests
{
    private readonly ContextoAplicacion _contexto;
    private readonly ConstructorContextoIA _constructor;

    public ConstructorContextoIATests()
    {
        _contexto = DbContextFactory.CrearContexto("ContextoIATest");
        _constructor = new ConstructorContextoIA(_contexto);
    }

    private async Task<int> SeedUsuarioAsync()
    {
        _contexto.Roles.Add(new Rol { Id = 1, Nombre = "Usuario" });
        _contexto.Carreras.Add(new Carrera { Id = 1, Nombre = "Test", Area = "Test", Codigo = "TST" });
        await _contexto.SaveChangesAsync();

        var usuario = new Usuario
        {
            CodigoUnico = "IA001",
            Nombre = "Ana",
            CorreoElectronico = "ana@test.com",
            ContrasenaHash = "hash",
            FechaNacimiento = new DateOnly(2000, 1, 1),
            Genero = "Femenino",
            RolId = 1,
            CarreraId = 1
        };
        _contexto.Usuarios.Add(usuario);
        await _contexto.SaveChangesAsync();
        return usuario.Id;
    }

    [Fact]
    public async Task ConstruirAsync_UsuarioInexistente_LanzaKeyNotFound()
    {
        var accion = async () => await _constructor.ConstruirAsync(99999);
        await accion.Should().ThrowAsync<KeyNotFoundException>();
    }

    [Fact]
    public async Task ConstruirAsync_UsuarioSinDatos_RetornaContextoConDefaults()
    {
        var usuarioId = await SeedUsuarioAsync();

        var ctx = await _constructor.ConstruirAsync(usuarioId);

        ctx.Nombre.Should().Be("Ana");
        ctx.NivelNumero.Should().Be(0);
        ctx.TituloNivel.Should().Be("Iniciado");
        ctx.UltimoEstadoAnimo.Should().Be("Sin registro");
        ctx.DiasDesdeUltimoAnimo.Should().Be(-1);
        ctx.Habitos.Should().BeEmpty();
        ctx.Misiones.Should().BeEmpty();
    }

    [Fact]
    public async Task ConstruirAsync_ConEstadoAnimo_ReflejaUltimoAnimo()
    {
        var usuarioId = await SeedUsuarioAsync();
        _contexto.EstadosAnimo.Add(new EstadoAnimo
        {
            UsuarioId = usuarioId,
            Estado = "Feliz",
            Fecha = DateOnly.FromDateTime(DateTime.Today)
        });
        await _contexto.SaveChangesAsync();

        var ctx = await _constructor.ConstruirAsync(usuarioId);

        ctx.UltimoEstadoAnimo.Should().Be("Feliz");
        ctx.DiasDesdeUltimoAnimo.Should().Be(0);
    }

    [Fact]
    public void ConstruirSystemPrompt_IncluyeNombreYTituloNivel()
    {
        var ctx = new ContextoUsuarioIA
        {
            Nombre = "Carlos",
            NivelNumero = 3,
            TituloNivel = "Aprendiz",
            XpTotal = 250
        };

        var prompt = ConstructorContextoIA.ConstruirSystemPrompt(ctx);

        prompt.Should().Contain("Carlos");
        prompt.Should().Contain("Aprendiz");
        prompt.Should().Contain("EDY AI");
    }

    [Fact]
    public void ConstruirSystemPrompt_SinHabitosNiMisiones_MuestraMensajesVacios()
    {
        var ctx = new ContextoUsuarioIA { Nombre = "Sara", DiasDesdeUltimoAnimo = -1 };

        var prompt = ConstructorContextoIA.ConstruirSystemPrompt(ctx);

        prompt.Should().Contain("Sin habitos activos todavia");
        prompt.Should().Contain("Sin misiones pendientes");
    }

    [Fact]
    public void ConstruirSystemPrompt_ConHabito_ListaHabito()
    {
        var ctx = new ContextoUsuarioIA
        {
            Nombre = "Luis",
            DiasDesdeUltimoAnimo = -1,
            Habitos = new List<HabitoIA>
            {
                new() { Nombre = "Leer", Categoria = "Estudio", Frecuencia = "Diaria", Racha = 5 }
            }
        };

        var prompt = ConstructorContextoIA.ConstruirSystemPrompt(ctx);

        prompt.Should().Contain("Leer");
        prompt.Should().Contain("racha: 5");
    }
}
