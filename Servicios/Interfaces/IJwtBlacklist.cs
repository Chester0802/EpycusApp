namespace EpycusApp.Servicios.Interfaces
{
    public interface IJwtBlacklist
    {
        Task AddToBlacklistAsync(string jti, TimeSpan ttl);
        Task<bool> IsBlacklistedAsync(string jti);
    }
}