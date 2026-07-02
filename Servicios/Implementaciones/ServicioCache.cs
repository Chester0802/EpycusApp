using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioCache : IServicioCache
    {
        private readonly IMemoryCache _cache;
        private readonly IServiceScopeFactory _scopeFactory;
        private readonly ILogger<ServicioCache> _logger;
        private static readonly TimeSpan Ttl = TimeSpan.FromMinutes(30);

        public ServicioCache(IMemoryCache cache, IServiceScopeFactory scopeFactory, ILogger<ServicioCache> logger)
        {
            _cache = cache;
            _scopeFactory = scopeFactory;
            _logger = logger;
        }

        public async Task<List<Carrera>> ObtenerCarrerasAsync()
        {
            return await _cache.GetOrCreateAsync("Carreras", async entry =>
            {
                entry.AbsoluteExpirationRelativeToNow = Ttl;
                _logger.LogDebug("Cache miss: Carreras");
                using var scope = _scopeFactory.CreateScope();
                var ctx = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();
                return await ctx.Carreras.AsNoTracking().Where(c => c.EstaActiva).ToListAsync();
            }) ?? [];
        }

        public async Task<List<Nivel>> ObtenerNivelesAsync()
        {
            return await _cache.GetOrCreateAsync("Niveles", async entry =>
            {
                entry.AbsoluteExpirationRelativeToNow = Ttl;
                _logger.LogDebug("Cache miss: Niveles");
                using var scope = _scopeFactory.CreateScope();
                var ctx = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();
                return await ctx.Niveles.AsNoTracking().OrderBy(n => n.Numero).ToListAsync();
            }) ?? [];
        }

        public async Task<List<Categoria>> ObtenerCategoriasAsync()
        {
            return await _cache.GetOrCreateAsync("Categorias", async entry =>
            {
                entry.AbsoluteExpirationRelativeToNow = Ttl;
                _logger.LogDebug("Cache miss: Categorias");
                using var scope = _scopeFactory.CreateScope();
                var ctx = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();
                return await ctx.Categorias.AsNoTracking().Where(c => c.EstaActiva).ToListAsync();
            }) ?? [];
        }

        public async Task<List<FraseMotivacional>> ObtenerFrasesMotivacionalesAsync()
        {
            return await _cache.GetOrCreateAsync("FrasesMotivacionales", async entry =>
            {
                entry.AbsoluteExpirationRelativeToNow = Ttl;
                _logger.LogDebug("Cache miss: FrasesMotivacionales");
                using var scope = _scopeFactory.CreateScope();
                var ctx = scope.ServiceProvider.GetRequiredService<ContextoAplicacion>();
                return await ctx.FrasesMotivacionales.AsNoTracking().Where(f => f.EstaActiva).ToListAsync();
            }) ?? [];
        }

        public void LimpiarCache()
        {
            foreach (var key in new[] { "Carreras", "Niveles", "Categorias", "FrasesMotivacionales" })
            {
                _cache.Remove(key);
                _logger.LogInformation("Cache limpiado: {Key}", key);
            }
        }
    }
}
