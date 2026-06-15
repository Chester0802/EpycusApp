namespace EpycusApp.Models.Entidades
{
    public class TipPomodoro
    {
        public int Id { get; set; }
        public string Tip { get; set; } = string.Empty;
        public bool EstaActivo { get; set; } = true;
    }
}
