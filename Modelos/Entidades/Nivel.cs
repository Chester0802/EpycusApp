namespace EPYCUS_WEB_v0._1.Modelos.Entidades
{
    public class Nivel
    {
        public int Id { get; set; }
        public int Numero { get; set; }
        public string Titulo { get; set; } = string.Empty;
        public int XpRequerido { get; set; }
        public string? Descripcion { get; set; }
        public ICollection<ProgresoUsuario> Progresos { get; set; } = new List<ProgresoUsuario>();
    }
}
