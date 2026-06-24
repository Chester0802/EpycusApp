using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos.Semilla
{
    public static class SemillaTipsPomodoro
    {
        public static async Task SembrarAsync(ContextoAplicacion contexto)
        {
            if (await contexto.TipsPomodoro.AnyAsync()) return;

            contexto.TipsPomodoro.AddRange(
                new TipPomodoro { Tip = "Pon tu teléfono en modo avión durante el tiempo de estudio. Cada notificación interrumpida son 23 minutos de concentración perdida.", EstaActivo = true },
                new TipPomodoro { Tip = "Prepara todo antes de empezar: agua, apuntes y la tarea específica. No busques materiales mientras el temporizador corre.", EstaActivo = true },
                new TipPomodoro { Tip = "En el descanso, aléjate de la pantalla. Estira las manos, camina un poco o mira por la ventana. Tu cerebro lo necesita.", EstaActivo = true },
                new TipPomodoro { Tip = "Si una idea te distrae, anótala rápido en un papel y regresa al foco. La anotarás después, no la perderás.", EstaActivo = true },
                new TipPomodoro { Tip = "Después de 4 ciclos completos, tómate 20-30 minutos de descanso largo. Comer algo ligero y caminar ayuda a resetear tu energía.", EstaActivo = true });

            await contexto.SaveChangesAsync();
        }
    }
}
