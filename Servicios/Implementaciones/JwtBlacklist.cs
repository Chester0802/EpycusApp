using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;

namespace EpycusApp.Servicios.Implementaciones
{
    public class JwtBlacklist : IJwtBlacklist
    {
        private readonly ContextoAplicacion _contexto;
        private readonly ILogger<JwtBlacklist> _logger;

        public JwtBlacklist(ContextoAplicacion contexto, ILogger<JwtBlacklist> logger)
        {
            _contexto = contexto;
            _logger = logger;
        }

        public async Task AddToBlacklistAsync(string jti, TimeSpan ttl)
        {
            var yaExiste = await _contexto.TokensRevocados.AnyAsync(t => t.Jti == jti);
            if (yaExiste)
            {
                return;
            }

            _contexto.TokensRevocados.Add(new TokenRevocado
            {
                Jti = jti,
                ExpiraEn = DateTime.UtcNow.Add(ttl)
            });

            // Limpieza oportunista de entradas ya expiradas para no crecer sin límite.
            var expirados = await _contexto.TokensRevocados
                .Where(t => t.ExpiraEn < DateTime.UtcNow)
                .ToListAsync();
            if (expirados.Count > 0)
            {
                _contexto.TokensRevocados.RemoveRange(expirados);
            }

            await _contexto.SaveChangesAsync();
            _logger.LogDebug("JWT added to blacklist: {Jti}", jti);
        }

        public async Task<bool> IsBlacklistedAsync(string jti)
        {
            return await _contexto.TokensRevocados
                .AsNoTracking()
                .AnyAsync(t => t.Jti == jti && t.ExpiraEn > DateTime.UtcNow);
        }
    }
}
