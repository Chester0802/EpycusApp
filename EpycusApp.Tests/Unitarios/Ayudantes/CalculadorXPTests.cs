using EpycusApp.Ayudantes;
using FluentAssertions;

namespace EpycusApp.Tests.Unitarios.Ayudantes;

// Los valores esperados provienen de SemillaNiveles (nivel 0 = "Novato" con XpRequerido 0;
// umbrales 100, 250, 450, 700, 1000...): CalculadorXP debe coincidir con la tabla sembrada.
public class CalculadorXPTests
{
    [Theory]
    [InlineData(0, 100)]
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
    [InlineData(0, 0)]
    [InlineData(1, 100)]
    [InlineData(2, 250)]
    [InlineData(3, 450)]
    [InlineData(4, 700)]
    [InlineData(5, 1000)]
    [InlineData(6, 1350)]
    public void XpTotalParaNivel_CoincideConXpRequeridoSembrado(int nivel, int xpEsperado)
    {
        var resultado = CalculadorXP.XpTotalParaNivel(nivel);
        resultado.Should().Be(xpEsperado);
    }

    [Theory]
    [InlineData(0, 0)]
    [InlineData(50, 0)]
    [InlineData(99, 0)]
    [InlineData(100, 1)]
    [InlineData(249, 1)]
    [InlineData(250, 2)]
    [InlineData(450, 3)]
    [InlineData(1000, 5)]
    [InlineData(10000, 18)]
    public void NivelParaXp_RetornaNivelCorrecto(int xpTotal, int nivelEsperado)
    {
        var resultado = CalculadorXP.NivelParaXp(xpTotal);
        resultado.Should().Be(nivelEsperado);
    }

    [Theory]
    [InlineData(100, 1, 0)]
    [InlineData(250, 2, 0)]
    [InlineData(300, 2, 50)]
    public void XpDentroDelNivelActual_CalculaCorrectamente(int xpTotal, int nivelActual, int xpEsperado)
    {
        var resultado = CalculadorXP.XpDentroDelNivelActual(xpTotal, nivelActual);
        resultado.Should().Be(xpEsperado);
    }

    [Theory]
    [InlineData(100, 1, 0)]
    [InlineData(250, 2, 0)]
    [InlineData(300, 2, 25)]
    [InlineData(20, 0, 20)]
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
