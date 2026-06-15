namespace EpycusApp.Ayudantes
{
    public static class CalculadorXP
    {
        public const int XP_LOGIN_DIARIO = 10;
        public const int XP_HABITO_COMPLETADO = 20;
        public const int XP_BONUS_RACHA_7_DIAS = 10;
        public const int XP_MISION_BAJA = 30;
        public const int XP_MISION_MEDIA = 50;
        public const int XP_MISION_ALTA = 80;
        public const int XP_POMODORO_POR_CICLO = 15;
        public const int XP_BONUS_RACHA_GLOBAL_7 = 50;
        public const int XP_BONUS_RACHA_GLOBAL_30 = 200;
        public const int NIVEL_MAXIMO = 20;

        public static int XpParaSiguienteNivel(int nivelActual)
        {
            return 100 + (nivelActual * 50);
        }

        public static int XpTotalParaNivel(int nivel)
        {
            if (nivel <= 1)
            {
                return 0;
            }

            var acumulado = 0;

            for (var i = 1; i < nivel; i++)
            {
                acumulado += XpParaSiguienteNivel(i);
            }

            return acumulado;
        }

        public static int NivelParaXp(int xpTotal)
        {
            for (var nivel = 1; nivel < NIVEL_MAXIMO; nivel++)
            {
                var xpSiguiente = XpTotalParaNivel(nivel + 1);

                if (xpTotal < xpSiguiente)
                {
                    return nivel;
                }
            }

            return NIVEL_MAXIMO;
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
                "Alta" => XP_MISION_ALTA,
                "Media" => XP_MISION_MEDIA,
                "Baja" => XP_MISION_BAJA,
                _ => XP_MISION_MEDIA
            };
        }
    }
}
