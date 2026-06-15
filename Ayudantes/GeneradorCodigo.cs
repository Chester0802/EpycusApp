using System.Text;

namespace EpycusApp.Ayudantes
{
    public static class GeneradorCodigo
    {
        private const string Caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

        public static string GenerarCodigoUsuario()
        {
            var resultado = new StringBuilder(8);

            for (var i = 0; i < 8; i++)
            {
                var indice = Random.Shared.Next(Caracteres.Length);
                resultado.Append(Caracteres[indice]);
            }

            return $"EPY-{resultado}";
        }
    }
}
