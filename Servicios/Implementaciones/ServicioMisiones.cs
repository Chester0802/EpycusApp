using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.EntityFrameworkCore;

// Servicio de Misiones: implementaciÃ³n de la lÃ³gica de negocio para CRUD y cambios de estado
// Todas las variables, mÃ©todos y comentarios en espaÃ±ol segÃºn convenciones del proyecto

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioMisiones : IServicioMisiones
    {
        private readonly ContextoAplicacion _contexto;
        private readonly IServicioGamificacion _servicioGamificacion;
        private readonly ILogger<ServicioMisiones> _logger;

        public ServicioMisiones(ContextoAplicacion contexto, IServicioGamificacion servicioGamificacion, ILogger<ServicioMisiones> logger)
        {
            _contexto = contexto;
            _servicioGamificacion = servicioGamificacion;
            _logger = logger;
        }

        public async Task<List<Mision>> ObtenerMisionesDeUsuario(int usuarioId)
        {
            return await _contexto.Misiones
                .Include(m => m.Categoria)
                .Where(m => m.UsuarioId == usuarioId)
                .OrderBy(m => m.Estado == "Completado" ? 1 : 0)
                .ThenBy(m => m.FechaLimite)
                .ToListAsync();
        }

        public async Task<Mision?> ObtenerPorId(int id)
        {
            return await _contexto.Misiones
                .Include(m => m.Categoria)
                .FirstOrDefaultAsync(m => m.Id == id);
        }

        public async Task CrearMision(CrearMisionViewModel modelo, int usuarioId)
        {
            var mision = new Mision
            {
                Nombre = modelo.Nombre,
                Descripcion = modelo.Descripcion,
                NombreCurso = modelo.NombreCurso,
                FechaLimite = DateOnly.FromDateTime(modelo.FechaLimite),
                Prioridad = modelo.Prioridad,
                Estado = "Pendiente",
                ConPomodoro = modelo.ConPomodoro,
                UsuarioId = usuarioId,
                CategoriaId = modelo.CategoriaId,
                FechaCreacion = DateTime.UtcNow
            };

            _contexto.Misiones.Add(mision);
            await _contexto.SaveChangesAsync();
        }

        public async Task EditarMision(EditarMisionViewModel modelo, int usuarioId)
        {
            var mision = await _contexto.Misiones.FirstOrDefaultAsync(m => m.Id == modelo.Id && m.UsuarioId == usuarioId);
            if (mision == null) throw new Exception("MisiÃ³n no encontrada o no autorizada.");

            if (mision.Estado == "Completado" || mision.Estado == "Fallido")
                throw new Exception("No se puede editar una misiÃ³n que ya estÃ¡ completada o fallida.");

            mision.Nombre = modelo.Nombre;
            mision.Descripcion = modelo.Descripcion;
            mision.NombreCurso = modelo.NombreCurso;
            mision.FechaLimite = DateOnly.FromDateTime(modelo.FechaLimite);
            mision.Prioridad = modelo.Prioridad;
            mision.ConPomodoro = modelo.ConPomodoro;
            mision.CategoriaId = modelo.CategoriaId;

            await _contexto.SaveChangesAsync();
        }

        public async Task EliminarMision(int id, int usuarioId)
        {
            var mision = await _contexto.Misiones.FirstOrDefaultAsync(m => m.Id == id && m.UsuarioId == usuarioId);
            if (mision != null)
            {
                _contexto.Misiones.Remove(mision);
                await _contexto.SaveChangesAsync();
            }
        }

        public async Task<(bool Exito, int XpGanado)> CompletarMision(int id, int usuarioId)
        {
            var mision = await _contexto.Misiones.FirstOrDefaultAsync(m => m.Id == id && m.UsuarioId == usuarioId);
            if (mision == null) return (false, 0);

            if (mision.Estado != "Pendiente" && mision.Estado != "EnProgreso")
                return (false, 0);

            int xp = CalculadorXP.XpPorMision(mision.Prioridad);

            mision.Estado = "Completado";
            mision.FechaCompletado = DateTime.UtcNow;
            mision.XpOtorgado = xp;

            await _contexto.SaveChangesAsync();
            await _servicioGamificacion.SumarXP(usuarioId, xp);

            return (true, xp);
        }

        public async Task CambiarEstado(int id, string estado, int usuarioId)
        {
            var mision = await _contexto.Misiones.FirstOrDefaultAsync(m => m.Id == id && m.UsuarioId == usuarioId);
            if (mision == null) return;

            if ((mision.Estado == "Pendiente" && estado == "EnProgreso") ||
                (mision.Estado == "EnProgreso" && estado == "Pendiente"))
            {
                mision.Estado = estado;
                await _contexto.SaveChangesAsync();
            }
        }

        public async Task<List<Categoria>> ObtenerCategoriasMisionAsync()
        {
            return await _contexto.Categorias
                .Where(c => c.Tipo == "Mision" || c.Tipo == "Ambos")
                .ToListAsync();
        }
    }
}
