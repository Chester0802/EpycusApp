using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.EntityFrameworkCore;

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
                .Include(m => m.SubTareas)
                .Where(m => m.UsuarioId == usuarioId)
                .OrderBy(m => m.Estado == "Completado" ? 1 : 0)
                .ThenBy(m => m.FechaLimite)
                .ToListAsync();
        }

        public async Task<Mision?> ObtenerPorId(int id)
        {
            return await _contexto.Misiones
                .Include(m => m.Categoria)
                .Include(m => m.SubTareas)
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
            if (mision == null) throw new Exception("Misi\u00f3n no encontrada o no autorizada.");

            if (mision.Estado == "Completado" || mision.Estado == "Fallido")
                throw new Exception("No se puede editar una misi\u00f3n que ya est\u00e1 completada o fallida.");

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
            var mision = await _contexto.Misiones
                .Include(m => m.SubTareas)
                .FirstOrDefaultAsync(m => m.Id == id && m.UsuarioId == usuarioId);
            if (mision == null) return (false, 0);

            if (mision.Estado != "Pendiente" && mision.Estado != "EnProgreso")
                return (false, 0);

            int xp = CalculadorXP.XpPorMision(mision.Prioridad);

            mision.Estado = "Completado";
            mision.FechaCompletado = DateTime.UtcNow;
            mision.XpOtorgado = xp;

            foreach (var st in mision.SubTareas.Where(st => !st.EstaCompletada))
            {
                st.EstaCompletada = true;
                st.FechaCompletado = DateTime.UtcNow;
            }

            await _contexto.SaveChangesAsync();
            await _servicioGamificacion.SumarXP(usuarioId, xp);
            await _servicioGamificacion.ActualizarRacha(usuarioId);

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

        public async Task<int> ContarCompletadasHoyAsync(int usuarioId)
        {
            var hoy = DateTime.UtcNow.Date;
            return await _contexto.Misiones
                .Where(m => m.UsuarioId == usuarioId
                    && m.Estado == "Completado"
                    && m.FechaCompletado != null
                    && m.FechaCompletado.Value.Date == hoy)
                .CountAsync();
        }

        public async Task<List<SubTarea>> ObtenerSubTareas(int misionId, int usuarioId)
        {
            return await _contexto.SubTareas
                .Where(st => st.MisionId == misionId && st.Mision.UsuarioId == usuarioId)
                .OrderBy(st => st.Orden)
                .ThenBy(st => st.FechaCreacion)
                .ToListAsync();
        }

        public async Task<SubTarea?> ObtenerSubTareaPorId(int id, int usuarioId)
        {
            return await _contexto.SubTareas
                .Include(st => st.Mision)
                .FirstOrDefaultAsync(st => st.Id == id && st.Mision.UsuarioId == usuarioId);
        }

        public async Task CrearSubTarea(string nombre, string? descripcion, int misionId, int usuarioId)
        {
            var mision = await _contexto.Misiones.FirstOrDefaultAsync(m => m.Id == misionId && m.UsuarioId == usuarioId);
            if (mision == null) throw new Exception("Misi\u00f3n no encontrada o no autorizada.");
            if (mision.Estado == "Completado" || mision.Estado == "Fallido")
                throw new Exception("No se pueden agregar sub-tareas a una misi\u00f3n completada o fallida.");

            var maxOrden = await _contexto.SubTareas
                .Where(st => st.MisionId == misionId)
                .MaxAsync(st => (int?)st.Orden) ?? -1;

            var subTarea = new SubTarea
            {
                Nombre = nombre,
                Descripcion = descripcion,
                Orden = maxOrden + 1,
                MisionId = misionId,
                FechaCreacion = DateTime.UtcNow
            };

            _contexto.SubTareas.Add(subTarea);
            await _contexto.SaveChangesAsync();
        }

        public async Task EditarSubTarea(int id, string nombre, string? descripcion, int? orden, int usuarioId)
        {
            var subTarea = await _contexto.SubTareas
                .Include(st => st.Mision)
                .FirstOrDefaultAsync(st => st.Id == id && st.Mision.UsuarioId == usuarioId);
            if (subTarea == null) throw new Exception("Sub-tarea no encontrada o no autorizada.");
            if (subTarea.Mision.Estado == "Completado" || subTarea.Mision.Estado == "Fallido")
                throw new Exception("No se puede editar una sub-tarea de una misi\u00f3n completada o fallida.");

            subTarea.Nombre = nombre;
            subTarea.Descripcion = descripcion;
            if (orden.HasValue) subTarea.Orden = orden.Value;

            await _contexto.SaveChangesAsync();
        }

        public async Task CompletarSubTarea(int id, int usuarioId)
        {
            var subTarea = await _contexto.SubTareas
                .Include(st => st.Mision)
                .ThenInclude(m => m.SubTareas)
                .FirstOrDefaultAsync(st => st.Id == id && st.Mision.UsuarioId == usuarioId);
            if (subTarea == null) throw new Exception("Sub-tarea no encontrada o no autorizada.");

            subTarea.EstaCompletada = true;
            subTarea.FechaCompletado = DateTime.UtcNow;

            if (subTarea.Mision.SubTareas.All(st => st.EstaCompletada))
            {
                var (exito, _) = await CompletarMision(subTarea.MisionId, usuarioId);
                if (exito) return;
            }

            await _contexto.SaveChangesAsync();
        }

        public async Task DescompletarSubTarea(int id, int usuarioId)
        {
            var subTarea = await _contexto.SubTareas
                .Include(st => st.Mision)
                .FirstOrDefaultAsync(st => st.Id == id && st.Mision.UsuarioId == usuarioId);
            if (subTarea == null) throw new Exception("Sub-tarea no encontrada o no autorizada.");

            subTarea.EstaCompletada = false;
            subTarea.FechaCompletado = null;

            await _contexto.SaveChangesAsync();
        }

        public async Task EliminarSubTarea(int id, int usuarioId)
        {
            var subTarea = await _contexto.SubTareas
                .Include(st => st.Mision)
                .FirstOrDefaultAsync(st => st.Id == id && st.Mision.UsuarioId == usuarioId);
            if (subTarea == null) throw new Exception("Sub-tarea no encontrada o no autorizada.");
            if (subTarea.Mision.Estado == "Completado" || subTarea.Mision.Estado == "Fallido")
                throw new Exception("No se puede eliminar una sub-tarea de una misi\u00f3n completada o fallida.");

            _contexto.SubTareas.Remove(subTarea);
            await _contexto.SaveChangesAsync();
        }

        public async Task<int> ObtenerTiempoEnfoqueSubTarea(int id, int usuarioId)
        {
            var subTarea = await _contexto.SubTareas
                .FirstOrDefaultAsync(st => st.Id == id && st.Mision.UsuarioId == usuarioId);
            return subTarea?.TiempoEnfoqueSegundos ?? 0;
        }

        public async Task<int> ObtenerTiempoEnfoqueMision(int misionId, int usuarioId)
        {
            return await _contexto.SubTareas
                .Where(st => st.MisionId == misionId && st.Mision.UsuarioId == usuarioId)
                .SumAsync(st => st.TiempoEnfoqueSegundos);
        }
    }
}
