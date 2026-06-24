using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaFrases
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.FrasesMotivacionales.AnyAsync()) return;

            contexto.FrasesMotivacionales.AddRange(
                new FraseMotivacional { Frase = "El secreto para avanzar es comenzar.", Autor = "Mark Twain", Categoria = "Motivación", EstaActiva = true },
                new FraseMotivacional { Frase = "No cuentes los días, haz que los días cuenten.", Autor = "Muhammad Ali", Categoria = "Motivación", EstaActiva = true },
                new FraseMotivacional { Frase = "Sé el cambio que quieres ver en el mundo.", Autor = "Mahatma Gandhi", Categoria = "Reflexión", EstaActiva = true },
                new FraseMotivacional { Frase = "El éxito es la suma de pequeños esfuerzos repetidos día tras día.", Autor = "Robert Collier", Categoria = "Constancia", EstaActiva = true },
                new FraseMotivacional { Frase = "No importa lo lento que vayas, siempre y cuando no te detengas.", Autor = "Confucio", Categoria = "Persistencia", EstaActiva = true },
                new FraseMotivacional { Frase = "La disciplina es el puente entre metas y logros.", Autor = "Jim Rohn", Categoria = "Disciplina", EstaActiva = true },
                new FraseMotivacional { Frase = "Cree que puedes y ya estarás a mitad de camino.", Autor = "Theodore Roosevelt", Categoria = "Confianza", EstaActiva = true },
                new FraseMotivacional { Frase = "Cada día es una nueva oportunidad para mejorar.", Autor = "Anónimo", Categoria = "Motivación", EstaActiva = true },
                new FraseMotivacional { Frase = "La constancia es la madre del éxito.", Autor = "Anónimo", Categoria = "Constancia", EstaActiva = true },
                new FraseMotivacional { Frase = "Haz de cada día tu obra maestra.", Autor = "John Wooden", Categoria = "Motivación", EstaActiva = true },
                new FraseMotivacional { Frase = "Tu salud mental es tan importante como tus notas. Priorízate.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                new FraseMotivacional { Frase = "Descansar no es perder el tiempo. Es invertir en tu rendimiento.", Autor = "Anónimo", Categoria = "Descanso", EstaActiva = true },
                new FraseMotivacional { Frase = "Está bien no estar bien. Lo importante es buscar ayuda cuando la necesites.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                new FraseMotivacional { Frase = "Duerme bien, come bien, muévete. Tu cerebro te lo agradecerá en los exámenes.", Autor = "Anónimo", Categoria = "Bienestar", EstaActiva = true },
                new FraseMotivacional { Frase = "Una mente descansada es una mente creativa. No olvides tus pausas.", Autor = "Anónimo", Categoria = "Descanso", EstaActiva = true },
                new FraseMotivacional { Frase = "La universidad no es una carrera de velocidad, es una maratón. Cuida tu paso.", Autor = "Anónimo", Categoria = "Reflexión", EstaActiva = true },
                new FraseMotivacional { Frase = "Hidrátate, estira las piernas y respira. Tu cuerpo es tu herramienta más valiosa.", Autor = "Anónimo", Categoria = "Bienestar", EstaActiva = true },
                new FraseMotivacional { Frase = "Compararte con otros es robarle la alegría a tu propio progreso.", Autor = "Theodore Roosevelt", Categoria = "Confianza", EstaActiva = true },
                new FraseMotivacional { Frase = "El descanso no es un lujo, es una necesidad biológica.", Autor = "Matthew Walker", Categoria = "Descanso", EstaActiva = true },
                new FraseMotivacional { Frase = "Hablar de lo que sientes no te hace débil. Te hace humano.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                new FraseMotivacional { Frase = "No necesitas hacer más. A veces necesitas hacer menos, pero mejor.", Autor = "Greg McKeown", Categoria = "Reflexión", EstaActiva = true },
                new FraseMotivacional { Frase = "Tomar agua, dormir bien y moverte: tres hábitos que cambian tu vida.", Autor = "Anónimo", Categoria = "Bienestar", EstaActiva = true },
                new FraseMotivacional { Frase = "Un paso a la vez. No tienes que tener todo resuelto hoy.", Autor = "Anónimo", Categoria = "Motivación", EstaActiva = true },
                new FraseMotivacional { Frase = "El estrés es temporal. Tu capacidad para superarlo también.", Autor = "Anónimo", Categoria = "Salud Mental", EstaActiva = true },
                new FraseMotivacional { Frase = "Respira. Todo lo que estás atravesando te está preparando para algo mejor.", Autor = "Anónimo", Categoria = "Reflexión", EstaActiva = true });

            await contexto.SaveChangesAsync();
        }
    }
}
