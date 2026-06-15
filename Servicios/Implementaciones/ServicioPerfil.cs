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

        public async Task ActualizarPerfil(PerfilViewModel modelo, int usuarioId)
        {
            var usuario = await _contexto.Usuarios.FindAsync(usuarioId);
            if (usuario == null)
                throw new KeyNotFoundException("Usuario no encontrado.");

            usuario.Nombre = modelo.Nombre;
            usuario.FechaNacimiento = modelo.FechaNacimiento ?? usuario.FechaNacimiento;
            usuario.Genero = modelo.Genero;

            if (modelo.CarreraId.HasValue)
                usuario.CarreraId = modelo.CarreraId.Value;

            await _contexto.SaveChangesAsync();
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

        public async Task CambiarPersonaje(int personajeId, int usuarioId)
        {
            var personajeExiste = await _contexto.Personajes.AnyAsync(p => p.Id == personajeId && p.EstaActivo);
            if (!personajeExiste)
                throw new KeyNotFoundException("Personaje no encontrado o inactivo.");

            var personajesUsuario = await _contexto.PersonajesUsuario
                .Where(pu => pu.UsuarioId == usuarioId)
                .ToListAsync();

            foreach (var pu in personajesUsuario)
            {
                pu.EstaSeleccionado = false;
            }

            var seleccionado = personajesUsuario.FirstOrDefault(pu => pu.PersonajeId == personajeId);
            if (seleccionado == null)
            {
                _contexto.PersonajesUsuario.Add(new PersonajeUsuario
                {
                    UsuarioId = usuarioId,
                    PersonajeId = personajeId,
                    EstaSeleccionado = true,
                    FechaObtenido = DateTime.UtcNow
                });
            }
            else
            {
                seleccionado.EstaSeleccionado = true;
            }

            await _contexto.SaveChangesAsync();
        }

        public async Task<string> ObtenerImagenPersonajeActual(int usuarioId)
        {
            var placeholder = "https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff&size=200";

            var personajeActivo = await _contexto.PersonajesUsuario
                .Where(pu => pu.UsuarioId == usuarioId && pu.EstaSeleccionado)
                .Select(pu => pu.PersonajeId)
                .FirstOrDefaultAsync();

            if (personajeActivo == 0)
                return placeholder;

            var progreso = await _contexto.ProgresosUsuario
                .Where(p => p.UsuarioId == usuarioId)
                .Select(p => p.NivelActual.Numero)
                .FirstOrDefaultAsync();

            var img = await _contexto.ImagenesNivelPersonaje
                .Where(i => i.PersonajeId == personajeActivo && i.NivelNumero <= progreso)
                .OrderByDescending(i => i.NivelNumero)
                .Select(i => i.ImagenUrl)
                .FirstOrDefaultAsync();

            return img ?? placeholder;
        }

        public async Task<List<LogroUsuario>> ObtenerLogrosUsuarioConLogroAsync(int usuarioId)
        {
            return await _contexto.LogrosUsuario
                .Include(lu => lu.Logro)
                .Where(lu => lu.UsuarioId == usuarioId)
                .OrderByDescending(lu => lu.FechaObtenido)
                .ToListAsync();
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
