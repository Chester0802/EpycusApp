namespace EpycusApp.Ayudantes
{
    public static class FormateadorTiempo
    {
        public static string FormatearSegundos(int segundos)
        {
            if (segundos < 60) return $"{segundos}s";
            if (segundos < 3600) return $"{segundos / 60}min";
            int horas = segundos / 3600;
            int mins = (segundos % 3600) / 60;
            return mins > 0 ? $"{horas}h {mins}m" : $"{horas}h";
        }
    }
}
