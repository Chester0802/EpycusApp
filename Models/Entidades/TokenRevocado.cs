namespace EpycusApp.Models.Entidades
{
    public class TokenRevocado
    {
        public int Id { get; set; }
        public string Jti { get; set; } = string.Empty;
        public DateTime ExpiraEn { get; set; }
        public DateTime FechaRevocacion { get; set; } = DateTime.UtcNow;
    }
}
