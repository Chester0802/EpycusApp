using EPYCUS_WEB_v0._1.Servicios.Interfaces;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioCorreo : IServicioCorreo
    {
        public Task EnviarVerificacion(string correo, string nombre, string token)
        {
            throw new NotImplementedException();
        }

        public Task EnviarRecuperacion(string correo, string nombre, string token)
        {
            throw new NotImplementedException();
        }
    }
}
