using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaLogros
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Logros.AnyAsync()) return;

            contexto.Logros.AddRange(
                new Logro { Nombre = "Primer Paso", Descripcion = "Completa tu primer hábito.", IconoUrl = "/img/logros/primer_paso.png", CondicionTipo = "HabitosCompletados", CondicionValor = 1, XpRecompensa = 10, EstaActivo = true },
                new Logro { Nombre = "Semana Perfecta", Descripcion = "Mantén una racha de 7 días.", IconoUrl = "/img/logros/semana_perfecta.png", CondicionTipo = "RachaDias", CondicionValor = 7, XpRecompensa = 50, EstaActivo = true },
                new Logro { Nombre = "Mes Imparable", Descripcion = "Mantén una racha de 30 días.", IconoUrl = "/img/logros/mes_imparable.png", CondicionTipo = "RachaDias", CondicionValor = 30, XpRecompensa = 200, EstaActivo = true },
                new Logro { Nombre = "Primera Misión", Descripcion = "Completa tu primera misión.", IconoUrl = "/img/logros/primera_mision.png", CondicionTipo = "MisionesCompletadas", CondicionValor = 1, XpRecompensa = 20, EstaActivo = true },
                new Logro { Nombre = "Productivo", Descripcion = "Completa 10 misiones.", IconoUrl = "/img/logros/productivo.png", CondicionTipo = "MisionesCompletadas", CondicionValor = 10, XpRecompensa = 80, EstaActivo = true },
                new Logro { Nombre = "Maestro del Foco", Descripcion = "Completa 50 sesiones Pomodoro.", IconoUrl = "/img/logros/maestro_foco.png", CondicionTipo = "SesionesPomodoro", CondicionValor = 50, XpRecompensa = 100, EstaActivo = true },
                new Logro { Nombre = "Asistente", Descripcion = "Alcanza el nivel 5.", IconoUrl = "/img/logros/nivel_5.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 5, XpRecompensa = 150, EstaActivo = true },
                new Logro { Nombre = "Profesional", Descripcion = "Alcanza el nivel 10.", IconoUrl = "/img/logros/nivel_10.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 10, XpRecompensa = 300, EstaActivo = true },
                new Logro { Nombre = "Gran Maestro", Descripcion = "Alcanza el nivel 18.", IconoUrl = "/img/logros/nivel_18.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 18, XpRecompensa = 700, EstaActivo = true },
                new Logro { Nombre = "Leyenda Viviente", Descripcion = "Alcanza el nivel máximo: nivel 20.", IconoUrl = "/img/logros/leyenda.png", CondicionTipo = "NivelAlcanzado", CondicionValor = 20, XpRecompensa = 1000, EstaActivo = true },
                new Logro { Nombre = "Ánimo Estable", Descripcion = "Mantén 7 días consecutivos sin registrar ánimo negativo.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "RachaAnimoPositivo", CondicionValor = 7, XpRecompensa = 30, EstaActivo = true },
                new Logro { Nombre = "Autoconsciente", Descripcion = "Registra tu estado de ánimo 30 veces.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "TotalRegistrosAnimo", CondicionValor = 30, XpRecompensa = 50, EstaActivo = true },
                new Logro { Nombre = "Alerta Superada", Descripcion = "Sigue una recomendación de alerta de bienestar.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "AlertasAtendidas", CondicionValor = 1, XpRecompensa = 20, EstaActivo = true },
                new Logro { Nombre = "Bienestar Constante", Descripcion = "Registra tu estado de ánimo por 14 días consecutivos.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "RachaRegistroAnimo", CondicionValor = 14, XpRecompensa = 80, EstaActivo = true },
                new Logro { Nombre = "Maestro del Equilibrio", Descripcion = "Completa 5 pausas activas recomendadas.", IconoUrl = "/img/logros/placeholder.png", CondicionTipo = "PausasActivasCompletadas", CondicionValor = 5, XpRecompensa = 40, EstaActivo = true });

            await contexto.SaveChangesAsync();
        }
    }
}
