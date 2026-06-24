using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaNiveles
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.Niveles.AnyAsync()) return;

            contexto.Niveles.AddRange(
                new Nivel { Numero = 0, Titulo = "Novato", XpRequerido = 0, Descripcion = "Aún no llevas el título, pero el camino comienza hoy. Bienvenido." },
                new Nivel { Numero = 1, Titulo = "Curioso", XpRequerido = 100, Descripcion = "La curiosidad es el primer paso de todo gran profesional." },
                new Nivel { Numero = 2, Titulo = "Aprendiz", XpRequerido = 250, Descripcion = "Estás absorbiendo conocimiento. Cada hábito cuenta." },
                new Nivel { Numero = 3, Titulo = "Estudiante Comprometido", XpRequerido = 450, Descripcion = "Tu constancia ya te diferencia de la mayoría." },
                new Nivel { Numero = 4, Titulo = "Practicante", XpRequerido = 700, Descripcion = "Empiezas a aplicar lo que aprendes. El mundo te espera." },
                new Nivel { Numero = 5, Titulo = "Asistente", XpRequerido = 1000, Descripcion = "Ya formas parte del campo profesional. Sigue creciendo." },
                new Nivel { Numero = 6, Titulo = "Junior", XpRequerido = 1350, Descripcion = "Tienes base sólida. Los desafíos reales ya no te asustan." },
                new Nivel { Numero = 7, Titulo = "Semi-Senior", XpRequerido = 1750, Descripcion = "Tu experiencia empieza a hablar por ti." },
                new Nivel { Numero = 8, Titulo = "Profesional", XpRequerido = 2200, Descripcion = "Dominas los fundamentos y resuelves problemas con soltura." },
                new Nivel { Numero = 9, Titulo = "Especialista", XpRequerido = 2700, Descripcion = "Tienes un área en la que pocos te superan." },
                new Nivel { Numero = 10, Titulo = "Senior", XpRequerido = 3250, Descripcion = "Mitad del camino. Tu criterio vale más que el de muchos." },
                new Nivel { Numero = 11, Titulo = "Senior Avanzado", XpRequerido = 3850, Descripcion = "Lideras con el ejemplo. Otros aprenden de ti." },
                new Nivel { Numero = 12, Titulo = "Experto", XpRequerido = 4500, Descripcion = "Tu nivel de profundidad es notable. Eres referente." },
                new Nivel { Numero = 13, Titulo = "Consultor", XpRequerido = 5200, Descripcion = "Te buscan cuando el problema es difícil. Eso es poder." },
                new Nivel { Numero = 14, Titulo = "Líder", XpRequerido = 5950, Descripcion = "No solo resuelves: diriges, inspiras y construyes equipos." },
                new Nivel { Numero = 15, Titulo = "Maestro", XpRequerido = 6750, Descripcion = "Tu conocimiento trasciende lo técnico. Ya es sabiduría." },
                new Nivel { Numero = 16, Titulo = "Arquitecto", XpRequerido = 7600, Descripcion = "Diseñas sistemas, estrategias y futuros completos." },
                new Nivel { Numero = 17, Titulo = "Eminencia", XpRequerido = 8500, Descripcion = "Tu nombre es sinónimo de excelencia en tu campo." },
                new Nivel { Numero = 18, Titulo = "Gran Maestro", XpRequerido = 9450, Descripcion = "Has forjado a otros profesionales con tu guía." },
                new Nivel { Numero = 19, Titulo = "Leyenda en Ascenso", XpRequerido = 10450, Descripcion = "El umbral del máximo poder está justo frente a ti." },
                new Nivel { Numero = 20, Titulo = "Leyenda Viviente", XpRequerido = 11500, Descripcion = "Has llegado a la cima. Eres la definición de tu profesión." });

            await contexto.SaveChangesAsync();
        }
    }
}
