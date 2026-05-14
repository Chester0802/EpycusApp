namespace EPYCUS_WEB_v0._1.Modelos.Entidades
{
    public class FraseMotivacional
    {
        public int Id { get; set; }
        public string Frase { get; set; } = string.Empty;
        public string Autor { get; set; } = "Anónimo";
        public bool EstaActiva { get; set; } = true;
    }
}
