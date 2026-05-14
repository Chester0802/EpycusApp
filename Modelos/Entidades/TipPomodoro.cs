namespace EPYCUS_WEB_v0._1.Modelos.Entidades
{
    public class TipPomodoro
    {
        public int Id { get; set; }
        public string Tip { get; set; } = string.Empty;
        public bool EstaActivo { get; set; } = true;
    }
}
