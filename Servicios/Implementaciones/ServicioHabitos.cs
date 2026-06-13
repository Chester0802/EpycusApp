using System;
using EPYCUS_WEB_v0._1.DTOs;
using EPYCUS_WEB_v0._1.Models.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using EPYCUS_WEB_v0._1.ViewModels;
using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Ayudantes;
using Microsoft.EntityFrameworkCore;
using System.Text.Json;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace EPYCUS_WEB_v0._1.Servicios.Implementaciones
{
    public class ServicioHabitos : IServicioHabitos
    {
        private readonly ContextoAplicacion _context;
        private readonly EPYCUS_WEB_v0._1.Servicios.Interfaces.IServicioGamificacion _servicioGamificacion;

        public ServicioHabitos(ContextoAplicacion context, EPYCUS_WEB_v0._1.Servicios.Interfaces.IServicioGamificacion servicioGamificacion)
        {
            _context = context;
            _servicioGamificacion = servicioGamificacion;
        }

        public async Task<EPYCUS_WEB_v0._1.ViewModels.HabitosDashboardViewModel> ObtenerDashboard(int usuarioId)
        {
            var vm = new EPYCUS_WEB_v0._1.ViewModels.HabitosDashboardViewModel();

            var habitos = await _context.Habitos.Where(h => h.UsuarioId == usuarioId).ToListAsync();
            vm.TotalHabitos = habitos.Count;

            // Conteo registros últimos 7 días
            var desde = DateOnly.FromDateTime(DateTime.Now.AddDays(-6));
            var registrosSemana = await (from r in _context.RegistrosHabito
                                         join h in _context.Habitos on r.HabitoId equals h.Id
                                         where r.Fecha >= desde && h.UsuarioId == usuarioId
                                         select r).ToListAsync();

            vm.CompletadosSemana = registrosSemana.Count(r => r.Estado == "Completado");
            vm.ParcialesSemana = registrosSemana.Count(r => r.Estado == "Parcial");
            vm.OmitidosSemana = registrosSemana.Count(r => r.Estado == "Omitido");

            // Mejores rachas: top 5 por racha maxima
            vm.MejoresRachas = habitos.OrderByDescending(h => h.RachaMaxima)
                .Take(5)
                .Select(h => (h.Nombre, h.RachaMaxima))
                .ToList();

            // Distribucion por categoria
            vm.DistribucionPorCategoria = habitos
                .GroupBy(h => h.CategoriaId)
                .Select(g => new { CatId = g.Key, Count = g.Count() })
                .Join(_context.Categorias,
                    g => g.CatId,
                    c => c.Id,
                    (g, c) => new { c.Nombre, g.Count })
                .ToDictionary(x => x.Nombre, x => x.Count);

            // Totales hoy y racha máxima actual entre hábitos
            var hoy = DateOnly.FromDateTime(DateTime.Now);
            vm.TotalCompletadosHoy = await (from r in _context.RegistrosHabito
                                            join h in _context.Habitos on r.HabitoId equals h.Id
                                            where r.Fecha == hoy && h.UsuarioId == usuarioId && r.Estado == "Completado"
                                            select r).CountAsync();

            vm.RachaActualMaxima = habitos.Any() ? habitos.Max(h => h.RachaActual) : 0;

            return vm;
        }

        public async Task<List<Habito>> ObtenerHabitosDeUsuario(int usuarioId)
        {
            return await _context.Habitos
                .Include(h => h.Categoria)
                .Where(h => h.UsuarioId == usuarioId)
                .ToListAsync();
        }

        public async Task<List<EPYCUS_WEB_v0._1.ViewModels.HabitoViewModel>> ObtenerHabitosViewModel(int usuarioId)
        {
            var habitos = await _context.Habitos
                .Include(h => h.Categoria)
                .Where(h => h.UsuarioId == usuarioId)
                .ToListAsync();

            return habitos.Select(h => new EPYCUS_WEB_v0._1.ViewModels.HabitoViewModel
            {
                Id = h.Id,
                Nombre = h.Nombre,
                Descripcion = h.Descripcion,
                Frecuencia = h.Frecuencia,
                DiasSemana = string.IsNullOrEmpty(h.DiasSemana) ? null : System.Text.Json.JsonSerializer.Deserialize<List<int>>(h.DiasSemana),
                ConPomodoro = h.ConPomodoro,
                RecordatorioHora = h.RecordatorioHora,
                RachaActual = h.RachaActual,
                RachaMaxima = h.RachaMaxima,
                EstaActivo = h.EstaActivo,
                FechaCreacion = h.FechaCreacion,
                CategoriaId = h.CategoriaId,
                CategoriaNombre = h.Categoria?.Nombre ?? string.Empty
            }).ToList();
        }

        public async Task<Habito?> ObtenerPorId(int id)
        {
            return await _context.Habitos
                .Include(h => h.Categoria)
                .Include(h => h.Registros)
                .FirstOrDefaultAsync(h => h.Id == id);
        }

        public async Task<EPYCUS_WEB_v0._1.ViewModels.HabitoViewModel?> ObtenerPorIdViewModel(int id)
        {
            var h = await _context.Habitos
                .Include(x => x.Categoria)
                .FirstOrDefaultAsync(x => x.Id == id);

            if (h is null)
                return null;

            return new EPYCUS_WEB_v0._1.ViewModels.HabitoViewModel
            {
                Id = h.Id,
                Nombre = h.Nombre,
                Descripcion = h.Descripcion,
                Frecuencia = h.Frecuencia,
                DiasSemana = string.IsNullOrEmpty(h.DiasSemana) ? null : System.Text.Json.JsonSerializer.Deserialize<List<int>>(h.DiasSemana),
                ConPomodoro = h.ConPomodoro,
                RecordatorioHora = h.RecordatorioHora,
                RachaActual = h.RachaActual,
                RachaMaxima = h.RachaMaxima,
                EstaActivo = h.EstaActivo,
                FechaCreacion = h.FechaCreacion,
                CategoriaId = h.CategoriaId,
                CategoriaNombre = h.Categoria.Nombre
            };
        }

        public async Task CrearHabito(CrearHabitoViewModel modelo, int usuarioId)
        {
            // Validar categoría
            var categoria = await _context.Categorias.FirstOrDefaultAsync(c => c.Id == modelo.CategoriaId && c.EstaActiva);
            if (categoria is null)
                throw new ArgumentException("Categoría no válida");

            // Normalizar DiasSemana: si viene en formato de texto separado por comas en el formulario
            if (modelo.DiasSemana == null)
            {
                // intentar leer de Request form si existe (cuando el input es string)
                // Notar: aquí no se puede acceder a HttpContext, por lo que esperaremos que el controlador convierta correctamente.
            }
            var habito = new Habito
            {
                Nombre = modelo.Nombre,
                Descripcion = modelo.Descripcion,
                CategoriaId = modelo.CategoriaId,
                Frecuencia = modelo.Frecuencia,
                DiasSemana = modelo.DiasSemana is null ? null : JsonSerializer.Serialize(modelo.DiasSemana),
                ConPomodoro = modelo.ConPomodoro,
                RecordatorioHora = modelo.RecordatorioHora,
                EstaActivo = modelo.EstaActivo,
                UsuarioId = usuarioId
            };

            _context.Habitos.Add(habito);
            await _context.SaveChangesAsync();
        }

        public async Task<List<EPYCUS_WEB_v0._1.Models.Entidades.Categoria>> ObtenerCategoriasActivas()
        {
            return await _context.Categorias.Where(c => c.EstaActiva).ToListAsync();
        }

        public async Task EditarHabito(EditarHabitoViewModel modelo, int usuarioId)
        {
            var habito = await _context.Habitos.FirstOrDefaultAsync(h => h.Id == modelo.Id && h.UsuarioId == usuarioId);
            if (habito is null)
                return;

            habito.Nombre = modelo.Nombre;
            habito.Descripcion = modelo.Descripcion;
            habito.CategoriaId = modelo.CategoriaId;
            habito.Frecuencia = modelo.Frecuencia;
            habito.DiasSemana = modelo.DiasSemana is null ? null : JsonSerializer.Serialize(modelo.DiasSemana);
            habito.ConPomodoro = modelo.ConPomodoro;
            habito.RecordatorioHora = modelo.RecordatorioHora;
            habito.EstaActivo = modelo.EstaActivo;

            _context.Habitos.Update(habito);
            await _context.SaveChangesAsync();
        }

        public async Task EliminarHabito(int id, int usuarioId)
        {
            var habito = await _context.Habitos.FirstOrDefaultAsync(h => h.Id == id && h.UsuarioId == usuarioId);
            if (habito is null)
                return;

            _context.Habitos.Remove(habito);
            await _context.SaveChangesAsync();
        }

        public async Task<(bool Exito, int XpGanado)> CompletarHabito(int id, int usuarioId)
        {
            var habito = await _context.Habitos.Include(h => h.Registros).FirstOrDefaultAsync(h => h.Id == id && h.UsuarioId == usuarioId);
            if (habito is null)
                return (false, 0);

            // Evitar completar dos veces el mismo día
            var hoy = DateOnly.FromDateTime(DateTime.Now);
            if (habito.Registros.Any(r => r.Fecha == hoy && r.Estado == "Completado"))
                return (false, 0);

            int xpGanado = ConstantesGamificacion.XP_BASE_HABITO;

            // Actualizar rachas
            habito.RachaActual += 1;
            if (habito.RachaActual > habito.RachaMaxima)
                habito.RachaMaxima = habito.RachaActual;

            // Bonus por racha semanal
            if (habito.RachaActual % ConstantesGamificacion.DIAS_RACHA_BONUS == 0)
            {
                xpGanado += ConstantesGamificacion.XP_BONUS_RACHA_SEMANAL;
            }

            var registro = new RegistroHabito
            {
                HabitoId = habito.Id,
                Fecha = hoy,
                Estado = "Completado",
                XpOtorgado = xpGanado,
                FechaRegistro = DateTime.Now
            };

            _context.RegistrosHabito.Add(registro);

            // Persistir cambios
            _context.Habitos.Update(habito);
            await _context.SaveChangesAsync();

            // Llamar al servicio de gamificación: sumar XP calculado (xpGanado)
            try
            {
                await _servicioGamificacion.SumarXP(usuarioId, xpGanado);
            }
            catch (Exception ex)
            {
                // Log el error pero no interrumpir el flujo principal
                // TODO: Implementar logging apropiado (ILogger)
                System.Diagnostics.Debug.WriteLine($"Error al sumar XP para usuario {usuarioId}: {ex.Message}");
            }

            return (true, xpGanado);
        }

        public async Task<(bool Exito, string Mensaje)> FallarHabito(int id, int usuarioId)
        {
            var habito = await _context.Habitos
                .Include(h => h.Registros)
                .FirstOrDefaultAsync(h => h.Id == id && h.UsuarioId == usuarioId);

            if (habito == null)
            {
                return (false, "Hábito no encontrado");
            }

            var hoy = DateOnly.FromDateTime(DateTime.Today);
            if (habito.Registros.Any(r => r.Fecha == hoy))
            {
                return (false, "Ya se registró el estado de hoy");
            }

            // Romper racha
            habito.RachaActual = 0;

            // Registrar como fallido
            _context.RegistrosHabito.Add(new RegistroHabito
            {
                HabitoId = habito.Id,
                Fecha = hoy,
                Estado = "Fallido",
                XpOtorgado = 0,
                FechaRegistro = DateTime.UtcNow
            });

            _context.Habitos.Update(habito);
            await _context.SaveChangesAsync();

            return (true, "Hábito marcado como fallido");
        }

        public async Task<List<HabitoRespuestaDto>> ObtenerHabitosConEstadoHoy(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var habitos = await _context.Habitos
                .Include(h => h.Categoria)
                .Include(h => h.Registros)
                .Where(h => h.UsuarioId == usuarioId)
                .ToListAsync();

            return habitos.Select(h => new HabitoRespuestaDto
            {
                Id = h.Id,
                Nombre = h.Nombre,
                Estado = h.Registros.FirstOrDefault(r => r.Fecha == hoy)?.Estado ?? "Pendiente",
                RachaActual = h.RachaActual,
                Categoria = h.Categoria?.Nombre ?? string.Empty
            }).ToList();
        }

        public async Task<List<HabitoHoyRespuestaDto>> ObtenerHabitosActivosConEstadoHoy(int usuarioId)
        {
            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var habitos = await _context.Habitos
                .Include(h => h.Registros)
                .Where(h => h.UsuarioId == usuarioId && h.EstaActivo)
                .ToListAsync();

            return habitos.Select(h => new HabitoHoyRespuestaDto
            {
                Id = h.Id,
                Nombre = h.Nombre,
                EstadoHoy = h.Registros.FirstOrDefault(r => r.Fecha == hoy)?.Estado ?? "Pendiente",
                XpPotencial = ConstantesGamificacion.XP_BASE_HABITO
            }).ToList();
        }

        public async Task<List<RegistroSemanaDto>> ObtenerRegistrosSemana(int habitoId, int usuarioId)
        {
            var desde = DateOnly.FromDateTime(DateTime.Today.AddDays(-6));

            var registros = await _context.RegistrosHabito
                .Include(r => r.Habito)
                .Where(r => r.HabitoId == habitoId &&
                           r.Habito.UsuarioId == usuarioId &&
                           r.Fecha >= desde)
                .OrderBy(r => r.Fecha)
                .Select(r => new RegistroSemanaDto
                {
                    Dia = r.Fecha.ToString("yyyy-MM-dd"),
                    Estado = r.Estado
                })
                .ToListAsync();

            return registros;
        }
    }
}
