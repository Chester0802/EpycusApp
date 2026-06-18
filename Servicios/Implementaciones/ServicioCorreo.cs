using System.Net;
using System.Net.Mail;
using EpycusApp.Servicios.Interfaces;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioCorreo : IServicioCorreo
    {
        private readonly IConfiguration _config;
        private readonly ILogger<ServicioCorreo> _logger;

        public ServicioCorreo(IConfiguration config, ILogger<ServicioCorreo> logger)
        {
            _config = config;
            _logger = logger;
        }

        public Task EnviarVerificacion(string correo, string nombre, string token)
        {
            var enlace = ConstruirUrl($"/Autenticacion/VerificarCorreo?token={Uri.EscapeDataString(token)}");
            var cuerpo = $"""
                Hola {WebUtility.HtmlEncode(nombre)},

                Confirma tu correo para activar tu cuenta en Epycus:
                {enlace}

                Si no creaste esta cuenta, puedes ignorar este mensaje.
                """;

            return EnviarAsync(correo, "Confirma tu correo en Epycus", cuerpo);
        }

        public Task EnviarRecuperacion(string correo, string nombre, string token)
        {
            var enlace = ConstruirUrl($"/Autenticacion/RestablecerContrasena?token={Uri.EscapeDataString(token)}");
            var cuerpo = $"""
                Hola {WebUtility.HtmlEncode(nombre)},

                Usa este enlace para restablecer tu contrasena:
                {enlace}

                El enlace expira en 1 hora. Si no solicitaste el cambio, puedes ignorar este mensaje.
                """;

            return EnviarAsync(correo, "Restablece tu contrasena de Epycus", cuerpo);
        }

        public Task EnviarBienvenida(string correo, string nombre)
        {
            var cuerpo = $"""
                Hola {WebUtility.HtmlEncode(nombre)},

                ¡Bienvenido a Epycus! Tu correo ha sido verificado exitosamente.

                Ahora puedes empezar a crear hábitos, completar misiones y subir de nivel.
                ¡Cada día es una nueva oportunidad para mejorar!

                â€” El equipo de Epycus
                """;

            return EnviarAsync(correo, "¡Bienvenido a Epycus!", cuerpo);
        }

        private async Task EnviarAsync(string destinatario, string asunto, string cuerpo)
        {
            var servidor = ObtenerConfig("Correo:Servidor");
            var usuario = ObtenerConfig("Correo:Usuario");
            var contrasena = ObtenerConfig("Correo:Contrasena");
            var remitente = _config["Correo:NombreRemitente"] ?? "Epycus App";
            var puerto = int.TryParse(_config["Correo:Puerto"], out var valorPuerto) ? valorPuerto : 587;

            using var mensaje = new MailMessage
            {
                From = new MailAddress(usuario, remitente),
                Subject = asunto,
                Body = cuerpo,
                IsBodyHtml = false
            };
            mensaje.To.Add(destinatario);

            using var cliente = new SmtpClient(servidor, puerto)
            {
                EnableSsl = true,
                Credentials = new NetworkCredential(usuario, contrasena)
            };

            try
            {
                await cliente.SendMailAsync(mensaje);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "No se pudo enviar correo a {Destinatario}", destinatario);
                throw;
            }
        }

        private string ConstruirUrl(string ruta)
        {
            var baseUrl = _config["App:UrlBase"]?.TrimEnd('/');
            if (string.IsNullOrWhiteSpace(baseUrl))
            {
                throw new InvalidOperationException("App:UrlBase no esta configurado.");
            }

            return $"{baseUrl}{ruta}";
        }

        private string ObtenerConfig(string clave)
        {
            var valor = _config[clave];
            if (string.IsNullOrWhiteSpace(valor))
            {
                throw new InvalidOperationException($"{clave} no esta configurado.");
            }

            return valor;
        }
    }
}
