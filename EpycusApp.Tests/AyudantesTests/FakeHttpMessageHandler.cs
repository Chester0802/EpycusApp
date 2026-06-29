using System.Net;

namespace EpycusApp.Tests.AyudantesTests;

public class FakeHttpMessageHandler : HttpMessageHandler
{
    private readonly Func<HttpRequestMessage, HttpResponseMessage> _responder;
    public int Llamadas { get; private set; }

    public FakeHttpMessageHandler(HttpStatusCode statusCode, string contenido)
        : this(_ => new HttpResponseMessage(statusCode)
        {
            Content = new StringContent(contenido, System.Text.Encoding.UTF8, "application/json")
        })
    {
    }

    public FakeHttpMessageHandler(Func<HttpRequestMessage, HttpResponseMessage> responder)
    {
        _responder = responder;
    }

    protected override Task<HttpResponseMessage> SendAsync(HttpRequestMessage request, CancellationToken cancellationToken)
    {
        Llamadas++;
        return Task.FromResult(_responder(request));
    }
}
