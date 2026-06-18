namespace EpycusApp.ViewModels;

public class PaginacionViewModel
{
    public int PaginaActual { get; set; } = 1;
    public int TotalPaginas { get; set; } = 1;
    public int TotalItems { get; set; }
    public int ItemsPorPagina { get; set; } = 20;
    public string? Accion { get; set; }
    public string? Controlador { get; set; }
    public object? RouteValues { get; set; }

    public bool TienePaginaAnterior => PaginaActual > 1;
    public bool TienePaginaSiguiente => PaginaActual < TotalPaginas;

    public int Desde => (PaginaActual - 1) * ItemsPorPagina + 1;
    public int Hasta => Math.Min(PaginaActual * ItemsPorPagina, TotalItems);
}
