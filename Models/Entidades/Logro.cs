namespace EPYCUS_WEB_v0._1.Models.Entidades
{
    public class Logro
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public string IconoUrl { get; set; } = string.Empty;
        public string CondicionTipo { get; set; } = string.Empty;
        public int CondicionValor { get; set; }
        public int XpRecompensa { get; set; } = 0;
        public bool EstaActivo { get; set; } = true;
    }
}
