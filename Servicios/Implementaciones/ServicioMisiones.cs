using EpycusApp.Ayudantes;
using EpycusApp.Datos;
using EpycusApp.Hubs;
using EpycusApp.Models.Entidades;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.SignalR;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioMisiones : IServicioMisiones
    {
        private readonly ContextoAplicacion _contexto;
        private readonly IServicioGamificacion _servicioGamificacion;
        private readonly IHubContext<NotificacionesHub> _hubContext;
        private readonly ILogger<ServicioMisiones> _logger;

        public ServicioMisiones(ContextoAplicacion contexto, IServicioGamificacion servicioGamificacion, IHubContext<NotificacionesHub> hubContext, ILogger<ServicioMisiones> logger)
        {
            _contexto = contexto;
            _servicioGamificacion = servicioGamificacion;
            _hubContext = hubContext;
            _logger = logger;
        }

        public async Task<List<Mision>> ObtenerMisionesDeUsuario(int usuarioId)
        {
            return await _contexto.Misiones
                .AsNoTracking()
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
                .AsNoTracking()
                .Include(m => m.Categoria)
                .Include(m => m.SubTareas)
                .FirstOrDefaultAsync(m => m.Id == id);
        }

        public async Task CrearMision(CrearMisionViewModel modelo, int usuarioId)
        {
            // Evita una FK violation sin manejar (500) si llega un CategoriaId obsoleto o
            // inexistente (ej. 0 por un fallback en el cliente movil).
            if (!await _contexto.Categorias.AnyAsync(c => c.Id == modelo.CategoriaId))
                throw new InvalidOperationException("La categoría seleccionada no es válida.");

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

            if (!await _contexto.Categorias.AnyAsync(c => c.Id == modelo.CategoriaId))
                throw new InvalidOperationException("La categor\u00eda seleccionada no es v\u00e1lida.");

            if (!string.IsNullOrEmpty(modelo.Estado) && modelo.Estado != mision.Estado)
            {
                if (modelo.Estado == "Completado")
                {
                    var (exito, _) = await CompletarMision(mision.Id, usuarioId);
                    if (!exito) throw new Exception("No se pudo completar la misi\u00f3n.");
                    await _contexto.SaveChangesAsync();
                    return;
                }
                mision.Estado = modelo.Estado;
                if (modelo.Estado == "Pendiente") mision.FechaCompletado = null;
            }

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

            try
            {
                await _hubContext.Clients.Group($"usuario_{usuarioId}")
                    .SendAsync("MisionCompletada", new { MisionId = id, XpGanado = xp });
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Error al enviar notificacion SignalR de mision completada para usuario {UsuarioId}", usuarioId);
            }

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

                try
                {
                    await _hubContext.Clients.Group($"usuario_{usuarioId}")
                        .SendAsync("EstadoCambio", new { Entidad = "Mision", EntidadId = id, NuevoEstado = estado });
                }
                catch (Exception ex)
                {
                    _logger.LogWarning(ex, "Error al enviar notificacion SignalR de cambio de estado para usuario {UsuarioId}", usuarioId);
                }
            }
        }

        public async Task<List<Categoria>> ObtenerCategoriasMisionAsync()
        {
            return await _contexto.Categorias
                .AsNoTracking()
                .Where(c => c.Tipo == "Mision" || c.Tipo == "Ambos")
                .ToListAsync();
        }

        public async Task<(bool Exito, string Mensaje)> RevertirMision(int id, int usuarioId)
        {
            var mision = await _contexto.Misiones
                .Include(m => m.SubTareas)
                .FirstOrDefaultAsync(m => m.Id == id && m.UsuarioId == usuarioId);
            if (mision == null) return (false, "Misi\u00f3n no encontrada.");
            if (mision.Estado != "Completado")
                return (false, "Solo se puede revertir una misi\u00f3n completada.");

            mision.Estado = "EnProgreso";
            mision.FechaCompletado = null;
            mision.XpOtorgado = 0;

            foreach (var st in mision.SubTareas.Where(st => st.EstaCompletada))
            {
                st.EstaCompletada = false;
                st.FechaCompletado = null;
            }

            await _contexto.SaveChangesAsync();
            return (true, "Misi\u00f3n revertida a En Progreso.");
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
                .AsNoTracking()
                .Where(st => st.MisionId == misionId && st.Mision.UsuarioId == usuarioId)
                .OrderBy(st => st.Orden)
                .ThenBy(st => st.FechaCreacion)
                .ToListAsync();
        }

        public async Task<SubTarea?> ObtenerSubTareaPorId(int id, int usuarioId)
        {
            return await _contexto.SubTareas
                .AsNoTracking()
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
