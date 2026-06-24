using System.Net.Http.Json;
using Microsoft.Extensions.Options;

namespace EpycusApp.Ayudantes;

public class VerificadorTurnstile
{
    private readonly HttpClient _httpClient;
    private readonly TurnstileOptions _options;

    public VerificadorTurnstile(HttpClient httpClient, IOptions<TurnstileOptions> options)
    {
        _httpClient = httpClient;
        _options = options.Value;
    }

    public async Task<bool> VerificarTokenAsync(string? token)
    {
        if (string.IsNullOrEmpty(_options.SecretKey))
            return true;

        if (string.IsNullOrEmpty(token))
            return false;

        try
        {
            var contenido = new FormUrlEncodedContent(new[]
            {
                new KeyValuePair<string, string>("secret", _options.SecretKey),
                new KeyValuePair<string, string>("response", token)
            });

            var respuesta = await _httpClient.PostAsync(
                "https://challenges.cloudflare.com/turnstile/v0/siteverify", contenido);

            var resultado = await respuesta.Content.ReadFromJsonAsync<TurnstileResponse>();
            return resultado?.Success == true;
        }
        catch
        {
            return false;
        }
    }
}

public class TurnstileOptions
{
    public const string Seccion = "Turnstile";
    public string SiteKey { get; set; } = string.Empty;
    public string SecretKey { get; set; } = string.Empty;
}

public class TurnstileResponse
{
    public bool Success { get; set; }
    public List<string>? ErrorCodes { get; set; }
}
