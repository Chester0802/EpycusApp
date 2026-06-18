namespace EpycusApp.Models.Entidades
{
    public class FraseMotivacional
    {
        public int Id { get; set; }
        public string Frase { get; set; } = string.Empty;
        public string Autor { get; set; } = "Anónimo";
        public string Categoria { get; set; } = "General";
        public bool EstaActiva { get; set; } = true;
    }
}
