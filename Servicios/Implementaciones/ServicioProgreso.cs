using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioProgreso : IServicioProgreso
    {
        private readonly ContextoAplicacion _contexto;
        private readonly ILogger<ServicioProgreso> _logger;

        public ServicioProgreso(ContextoAplicacion contexto, ILogger<ServicioProgreso> logger)
        {
            _contexto = contexto;
            _logger = logger;
        }

        public async Task<ProgresoUsuario> ObtenerProgreso(int usuarioId)
        {
            var progreso = await _contexto.ProgresosUsuario
                .Include(p => p.NivelActual)
                .FirstOrDefaultAsync(p => p.UsuarioId == usuarioId);

            if (progreso == null)
            {
                var nivelInicial = await _contexto.Niveles.OrderBy(n => n.Numero).FirstOrDefaultAsync();
                return new ProgresoUsuario
                {
                    UsuarioId = usuarioId,
                    NivelActual = nivelInicial ?? new Nivel { Numero = 1, Descripcion = "Novato", Titulo = "Principiante" },
                    NivelActualId = nivelInicial?.Id ?? 1,
                    XpTotal = 0,
                    RachaActual = 0,
                    RachaMaxima = 0
                };
            }

            return progreso;
        }

        public async Task<List<LogroUsuario>> ObtenerLogrosUsuario(int usuarioId)
        {
            return await _contexto.LogrosUsuario
                .Include(l => l.Logro)
                .Where(l => l.UsuarioId == usuarioId)
                .ToListAsync();
        }

        public async Task<List<EstadoAnimo>> ObtenerHistorialAnimo(int usuarioId)
        {
            return await _contexto.EstadosAnimo
                .Where(e => e.UsuarioId == usuarioId)
                .OrderByDescending(e => e.FechaRegistro)
                .Take(7)
                .ToListAsync();
        }

        public async Task<Nivel?> ObtenerNivelSiguiente(int nivelActualNumero)
        {
            return await _contexto.Niveles.FirstOrDefaultAsync(n => n.Numero == nivelActualNumero + 1);
        }

        public async Task<List<Logro>> ObtenerTodosLosLogros()
        {
            return await _contexto.Logros.Where(l => l.EstaActivo).ToListAsync();
        }

        public async Task<Nivel?> ObtenerNivelInicialAsync()
        {
            return await _contexto.Niveles.OrderBy(n => n.Numero).FirstOrDefaultAsync();
        }

        public async Task<string> ObtenerImagenPersonaje(int usuarioId, int nivelActual)
        {
            var personajeActivo = await _contexto.PersonajesUsuario
                .Where(p => p.UsuarioId == usuarioId)
                .FirstOrDefaultAsync();

            if (personajeActivo == null) return "https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff&size=200";

            var img = await _contexto.ImagenesNivelPersonaje
                .Where(i => i.PersonajeId == personajeActivo.PersonajeId && i.NivelNumero <= nivelActual)
                .OrderByDescending(i => i.NivelNumero)
                .FirstOrDefaultAsync();

            return img?.ImagenUrl ?? "https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff&size=200";
        }
    }
}
