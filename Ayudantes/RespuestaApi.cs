namespace EPYCUS_WEB_v0._1.Ayudantes
{
    public class RespuestaApi<T>
    {
        public bool Exito { get; set; }
        public string? Mensaje { get; set; }
        public T? Datos { get; set; }
        public List<string>? Errores { get; set; }

        public static RespuestaApi<T> Exitosa(T datos, string? mensaje = null)
        {
            return new RespuestaApi<T>
            {
                Exito = true,
                Datos = datos,
                Mensaje = mensaje
            };
        }

        public static RespuestaApi<T> Fallida(string mensaje, List<string>? errores = null)
        {
            return new RespuestaApi<T>
            {
                Exito = false,
                Mensaje = mensaje,
                Errores = errores
            };
        }
    }
}
