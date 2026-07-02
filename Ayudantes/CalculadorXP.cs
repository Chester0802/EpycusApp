namespace EpycusApp.Ayudantes
{
    public static class CalculadorXP
    {
        public static int XpParaSiguienteNivel(int nivelActual)
        {
            return 100 + (nivelActual * 50);
        }

        // Los niveles arrancan en 0 ("Novato", ver SemillaNiveles): los umbrales acumulados
        // deben coincidir con el XpRequerido sembrado (nivel 1 = 100, 2 = 250, 3 = 450...).
        public static int XpTotalParaNivel(int nivel)
        {
            if (nivel <= 0)
            {
                return 0;
            }

            var acumulado = 0;

            for (var i = 0; i < nivel; i++)
            {
                acumulado += XpParaSiguienteNivel(i);
            }

            return acumulado;
        }

        public static int NivelParaXp(int xpTotal)
        {
            for (var nivel = 0; nivel < ConstantesGamificacion.NIVEL_MAXIMO; nivel++)
            {
                var xpSiguiente = XpTotalParaNivel(nivel + 1);

                if (xpTotal < xpSiguiente)
                {
                    return nivel;
                }
            }

            return ConstantesGamificacion.NIVEL_MAXIMO;
        }

        public static int XpDentroDelNivelActual(int xpTotal, int nivelActual)
        {
            return xpTotal - XpTotalParaNivel(nivelActual);
        }

        public static decimal PorcentajeProgreso(int xpTotal, int nivelActual)
        {
            var xpNivelActual = XpDentroDelNivelActual(xpTotal, nivelActual);
            var xpSiguiente = XpParaSiguienteNivel(nivelActual);

            if (xpSiguiente <= 0)
            {
                return 0;
            }

            var porcentaje = (decimal)xpNivelActual / xpSiguiente * 100m;

            if (porcentaje < 0)
            {
                return 0;
            }

            if (porcentaje > 100)
            {
                return 100;
            }

            return porcentaje;
        }

        public static int XpPorMision(string prioridad)
        {
            return prioridad switch
            {
                "Alta" => ConstantesGamificacion.XP_MISION_ALTA,
                "Media" => ConstantesGamificacion.XP_MISION_MEDIA,
                "Baja" => ConstantesGamificacion.XP_MISION_BAJA,
                _ => ConstantesGamificacion.XP_MISION_MEDIA
            };
        }
    }
}
