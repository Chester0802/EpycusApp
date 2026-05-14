using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using EPYCUS_WEB_v0._1.ViewModels;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.Datos;
using Microsoft.EntityFrameworkCore;
using System.Security.Claims;
using System.Linq;
using System.Threading.Tasks;
using System;
using System.Collections.Generic;

namespace EPYCUS_WEB_v0._1.Controllers
{
    [Authorize]
    public class PomodoroController : Controller
    {
        private readonly IServicioPomodoro _servicioPomodoro;
        private readonly ContextoAplicacion _contexto;

        public PomodoroController(IServicioPomodoro servicioPomodoro, ContextoAplicacion contexto)
        {
            _servicioPomodoro = servicioPomodoro;
            _contexto = contexto;
        }

        [AllowAnonymous]
        [HttpGet("")]
        [HttpGet("Index")]
        public async Task<IActionResult> Index()
        {
            var modelo = new PomodoroIndexViewModel();

            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            if (User.Identity != null && User.Identity.IsAuthenticated && int.TryParse(claim, out var usuarioId) && usuarioId != 0)
            {
                // Configuración
                modelo.Configuracion = await _servicioPomodoro.ObtenerConfiguracion(usuarioId);

                // Estadísticas e Historial de Hoy
                var hoy = DateTime.Today;
                var sesionesHoy = await _contexto.SesionesPomodoro
                    .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= hoy)
                    .OrderByDescending(s => s.FechaInicio)
                    .ToListAsync();

                modelo.HistorialHoy = sesionesHoy;

                modelo.EstadisticasHoy.CiclosCompletados = sesionesHoy.Sum(s => s.CiclosCompletados);
                // Calcular minutos enfocados basado en la configuración del usuario
                int tiempoEnfoque = modelo.Configuracion.TiempoEstudioMin;
                modelo.EstadisticasHoy.MinutosEnfocados = modelo.EstadisticasHoy.CiclosCompletados * tiempoEnfoque;
                modelo.EstadisticasHoy.XpGanado = sesionesHoy.Sum(s => s.XpOtorgado);

                // Misiones completadas hoy (se pueden consultar en la tabla Misiones)
                modelo.EstadisticasHoy.MisionesCompletadas = await _contexto.Misiones
                    .Where(m => m.UsuarioId == usuarioId && m.Estado == "Completado" && m.FechaCompletado != null && m.FechaCompletado.Value.Date == hoy)
                    .CountAsync();

                // Tareas Enfoque (Sólo las creadas con Pomodoro)
                var habitos = await _contexto.Habitos
                    .Include(h => h.Categoria)
                    .Where(h => h.UsuarioId == usuarioId && h.EstaActivo && h.ConPomodoro)
                    .Select(h => new TareaPomodoro
                    {
                        Id = h.Id,
                        Nombre = h.Nombre,
                        CategoriaNombre = h.Categoria != null ? h.Categoria.Nombre : "Sin categoría",
                        Tipo = "Habito"
                    }).ToListAsync();

                var misiones = await _contexto.Misiones
                    .Include(m => m.Categoria)
                    .Where(m => m.UsuarioId == usuarioId && m.Estado != "Completado" && m.ConPomodoro)
                    .Select(m => new TareaPomodoro
                    {
                        Id = m.Id,
                        Nombre = m.Nombre,
                        CategoriaNombre = m.Categoria != null ? m.Categoria.Nombre : "Sin categoría",
                        Tipo = "Mision"
                    }).ToListAsync();

                modelo.TareasEnfoque.AddRange(habitos);
                modelo.TareasEnfoque.AddRange(misiones);
            }

            return View(modelo);
        }
    }
}
