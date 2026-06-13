using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Models.DTOs;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioPerfil : IServicioPerfil
    {
        private readonly ContextoAplicacion _contexto;

        public ServicioPerfil(ContextoAplicacion contexto)
        {
            _contexto = contexto;
        }

        public Task<Usuario?> ObtenerPerfil(int usuarioId)
        {
            return _contexto.Usuarios.FindAsync(usuarioId).AsTask();
        }

        public async Task<PerfilViewModel?> ObtenerPerfilCompletoAsync(int usuarioId)
        {
            var usuario = await _contexto.Usuarios
                .Include(u => u.Carrera)
                .FirstOrDefaultAsync(u => u.Id == usuarioId);

            if (usuario == null) return null;

            return new PerfilViewModel
            {
                Usuario = usuario,
                Nombre = usuario.Nombre,
                FechaNacimiento = usuario.FechaNacimiento,
                Genero = usuario.Genero,
                CarreraId = usuario.CarreraId,
                CarreraNombre = usuario.Carrera?.Nombre,
                UsaGoogle = !string.IsNullOrEmpty(usuario.GoogleId),
                TemaActualId = usuario.TemaActualId
            };
        }

        public async Task<List<PersonajePerfilItem>> ObtenerPersonajesDisponiblesAsync(int usuarioId)
        {
            var personajeActualId = await _contexto.PersonajesUsuario
                .Where(pu => pu.UsuarioId == usuarioId && pu.EstaSeleccionado)
                .Select(pu => pu.PersonajeId)
                .FirstOrDefaultAsync();

            var personajes = await _contexto.Personajes
                .Where(p => p.EstaActivo)
                .Include(p => p.Imagenes)
                .ToListAsync();

            return personajes.Select(p => new PersonajePerfilItem
            {
                Id = p.Id,
                Nombre = p.Nombre,
                Genero = p.Genero,
                EsSeleccionado = p.Id == personajeActualId,
                ImagenPreviewUrl = p.Imagenes.FirstOrDefault()?.ImagenUrl
            }).ToList();
        }

        public Task ActualizarPerfil(PerfilViewModel modelo, int usuarioId)
        {
            throw new NotImplementedException();
        }

        public async Task<RespuestaOperacion> ActualizarPerfilAsync(int usuarioId, ActualizarPerfilViewModel modelo)
        {
            var usuario = await _contexto.Usuarios.FindAsync(usuarioId);
            if (usuario == null)
                return RespuestaOperacion.Fallo("Usuario no encontrado.");

            usuario.Nombre = modelo.Nombre;

            if (modelo.CarreraId.HasValue)
                usuario.CarreraId = modelo.CarreraId.Value;

            await _contexto.SaveChangesAsync();
            return RespuestaOperacion.Exitosa("Perfil actualizado correctamente.");
        }

        public Task CambiarPersonaje(int personajeId, int usuarioId)
        {
            throw new NotImplementedException();
        }

        public async Task<string> ObtenerImagenPersonajeActual(int usuarioId)
        {
            var personajeActivo = await _contexto.PersonajesUsuario
                .Where(pu => pu.UsuarioId == usuarioId && pu.EstaSeleccionado)
                .FirstOrDefaultAsync();

            if (personajeActivo == null)
                return "https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff&size=200";

            var progreso = await _contexto.ProgresosUsuario
                .Include(p => p.NivelActual)
                .FirstOrDefaultAsync(p => p.UsuarioId == usuarioId);

            var nivelNumero = progreso?.NivelActual?.Numero ?? 0;

            var imagen = await _contexto.ImagenesNivelPersonaje
                .Where(i => i.PersonajeId == personajeActivo.PersonajeId && i.NivelNumero <= nivelNumero)
                .OrderByDescending(i => i.NivelNumero)
                .FirstOrDefaultAsync();

            return imagen?.ImagenUrl ?? "https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff&size=200";
        }

        public async Task<RespuestaOperacion> CambiarTemaAsync(int usuarioId, int temaId)
        {
            var temaUsuario = await _contexto.TemasUsuario
                .FirstOrDefaultAsync(tu => tu.UsuarioId == usuarioId);

            if (temaUsuario == null)
            {
                _contexto.TemasUsuario.Add(new TemaUsuario
                {
                    UsuarioId = usuarioId,
                    TemaId = temaId,
                    FechaObtenido = DateTime.UtcNow
                });
            }
            else
            {
                temaUsuario.TemaId = temaId;
                temaUsuario.FechaObtenido = DateTime.UtcNow;
            }

            await _contexto.SaveChangesAsync();
            return RespuestaOperacion.Exitosa("Tema actualizado.");
        }
    }
}
