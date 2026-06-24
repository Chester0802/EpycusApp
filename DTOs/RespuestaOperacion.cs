namespace EpycusApp.DTOs
{
    public class RespuestaOperacion
    {
        public bool EsExitoso { get; set; }
        public string? Mensaje { get; set; }
        public object? Datos { get; set; }

        public static RespuestaOperacion Exitosa(string? mensaje = null, object? datos = null)
        {
            return new RespuestaOperacion
            {
                EsExitoso = true,
                Mensaje = mensaje,
                Datos = datos
            };
        }

        public static RespuestaOperacion Fallo(string mensaje)
        {
            return new RespuestaOperacion
            {
                EsExitoso = false,
                Mensaje = mensaje
            };
        }
    }
}
