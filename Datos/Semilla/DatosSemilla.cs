namespace EpycusApp.Datos.Semilla
{
    public static class DatosSemilla
    {
        public static async Task InicializarAsync(ContextoAplicacion contexto)
        {
            try
            {
                await SemillaCarreras.SembrarAsync(contexto);
                await SemillaNiveles.SembrarAsync(contexto);
                await SemillaCategorias.SembrarAsync(contexto);
                await SemillaTemas.SembrarAsync(contexto);
                await SemillaPersonajes.SembrarAsync(contexto);
                await SemillaLogros.SembrarAsync(contexto);
                await SemillaFrases.SembrarAsync(contexto);
                await SemillaTipsPomodoro.SembrarAsync(contexto);
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"[DatosSemilla] Error: {ex.Message}");
                throw;
            }
        }
    }
}
