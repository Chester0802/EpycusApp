using EpycusApp.Servicios.Interfaces;
using Microsoft.Extensions.Caching.Distributed;
using Microsoft.Extensions.Logging;

namespace EpycusApp.Servicios.Implementaciones
{
    public class JwtBlacklist : IJwtBlacklist
    {
        private readonly IDistributedCache _cache;
        private readonly ILogger<JwtBlacklist> _logger;
        private const string KeyPrefix = "jwt_blacklist:";

        public JwtBlacklist(IDistributedCache cache, ILogger<JwtBlacklist> logger)
        {
            _cache = cache;
            _logger = logger;
        }

        public async Task AddToBlacklistAsync(string jti, TimeSpan ttl)
        {
            var key = KeyPrefix + jti;
            await _cache.SetStringAsync(key, "revoked", new DistributedCacheEntryOptions
            {
                AbsoluteExpirationRelativeToNow = ttl
            });
            _logger.LogDebug("JWT added to blacklist: {Jti}", jti);
        }

        public async Task<bool> IsBlacklistedAsync(string jti)
        {
            var key = KeyPrefix + jti;
            var value = await _cache.GetStringAsync(key);
            return !string.IsNullOrEmpty(value);
        }
    }
}