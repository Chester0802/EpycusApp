using System;
using System.Collections.Generic;

namespace EpycusApp.ViewModels
{
    public class HabitoViewModel
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public string Frecuencia { get; set; } = string.Empty;
        public List<int>? DiasSemana { get; set; }
        public bool ConPomodoro { get; set; }
        public TimeSpan? RecordatorioHora { get; set; }
        public int RachaActual { get; set; }
        public int RachaMaxima { get; set; }
        public bool EstaActivo { get; set; }
        public DateTime FechaCreacion { get; set; }
        public int CategoriaId { get; set; }
        public string CategoriaNombre { get; set; } = string.Empty;
    }
}
