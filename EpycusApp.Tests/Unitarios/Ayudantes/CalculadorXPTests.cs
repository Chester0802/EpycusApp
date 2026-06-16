using EpycusApp.Ayudantes;
using FluentAssertions;

namespace EpycusApp.Tests.Unitarios.Ayudantes;

public class CalculadorXPTests
{
    [Theory]
    [InlineData(1, 150)]
    [InlineData(2, 200)]
    [InlineData(5, 350)]
    [InlineData(10, 600)]
    public void XpParaSiguienteNivel_CalculaCorrectamente(int nivelActual, int xpEsperado)
    {
        var resultado = CalculadorXP.XpParaSiguienteNivel(nivelActual);
        resultado.Should().Be(xpEsperado);
    }

    [Theory]
    [InlineData(1, 0)]
    [InlineData(2, 150)]
    [InlineData(3, 350)]
    [InlineData(4, 600)]
    [InlineData(5, 900)]
    public void XpTotalParaNivel_AcumulaCorrectamente(int nivel, int xpEsperado)
    {
        var resultado = CalculadorXP.XpTotalParaNivel(nivel);
        resultado.Should().Be(xpEsperado);
    }

    [Theory]
    [InlineData(0, 1)]
    [InlineData(50, 1)]
    [InlineData(149, 1)]
    [InlineData(150, 2)]
    [InlineData(350, 3)]
    [InlineData(10000, 18)]
    public void NivelParaXp_RetornaNivelCorrecto(int xpTotal, int nivelEsperado)
    {
        var resultado = CalculadorXP.NivelParaXp(xpTotal);
        resultado.Should().Be(nivelEsperado);
    }

    [Theory]
    [InlineData(150, 2, 0)]
    [InlineData(350, 3, 0)]
    [InlineData(400, 3, 50)]
    public void XpDentroDelNivelActual_CalculaCorrectamente(int xpTotal, int nivelActual, int xpEsperado)
    {
        var resultado = CalculadorXP.XpDentroDelNivelActual(xpTotal, nivelActual);
        resultado.Should().Be(xpEsperado);
    }

    [Theory]
    [InlineData(150, 2, 0)]
    [InlineData(250, 2, 50)]
    [InlineData(400, 3, 20)]
    public void PorcentajeProgreso_CalculaCorrectamente(int xpTotal, int nivelActual, decimal porcentajeEsperado)
    {
        var resultado = CalculadorXP.PorcentajeProgreso(xpTotal, nivelActual);
        resultado.Should().Be(porcentajeEsperado);
    }

    [Fact]
    public void PorcentajeProgreso_NivelMaximoSinSiguiente_RetornaCero()
    {
        var resultado = CalculadorXP.PorcentajeProgreso(10000, 20);
        resultado.Should().Be(0);
    }

    [Theory]
    [InlineData("Baja", 30)]
    [InlineData("Media", 50)]
    [InlineData("Alta", 80)]
    [InlineData("Desconocida", 50)]
    public void XpPorMision_RetornaXpSegunPrioridad(string prioridad, int xpEsperado)
    {
        var resultado = CalculadorXP.XpPorMision(prioridad);
        resultado.Should().Be(xpEsperado);
    }
}
