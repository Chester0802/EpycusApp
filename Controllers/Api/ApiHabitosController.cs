using System;
using System.Linq;
using System.Security.Claims;
using System.Threading.Tasks;
using EPYCUS_WEB_v0._1.Ayudantes;
using EPYCUS_WEB_v0._1.Datos;
using EPYCUS_WEB_v0._1.Modelos.Entidades;
using EPYCUS_WEB_v0._1.Servicios.Interfaces;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace EPYCUS_WEB_v0._1.Controllers.Api
{
    [ApiController]
    [Route("api/[controller]")]
    [Authorize]
    public class ApiHabitosController : ControllerBase
    {
        private readonly IServicioHabitos _servicioHabitos;
        private readonly ContextoAplicacion _contexto;

        public ApiHabitosController(IServicioHabitos servicioHabitos, ContextoAplicacion contexto)
        {
            _servicioHabitos = servicioHabitos;
            _contexto = contexto;
        }

        private int? ObtenerUsuarioId()
        {
            var claim = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            return int.TryParse(claim, out var usuarioId) ? usuarioId : null;
        }

        [HttpGet]
        public async Task<IActionResult> ObtenerHabitos()
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var habitos = await _contexto.Habitos
                .Include(h => h.Categoria)
                .Include(h => h.Registros)
                .Where(h => h.UsuarioId == usuarioId)
                .ToListAsync();

            var respuesta = habitos.Select(h => new
            {
                id = h.Id,
                nombre = h.Nombre,
                estado = h.Registros.FirstOrDefault(r => r.Fecha == hoy)?.Estado ?? "Pendiente",
                rachaActual = h.RachaActual,
                categoria = h.Categoria?.Nombre ?? string.Empty
            });

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }

        [HttpGet("hoy")]
        public async Task<IActionResult> ObtenerHabitosHoy()
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var hoy = DateOnly.FromDateTime(DateTime.Today);

            var habitos = await _contexto.Habitos
                .Include(h => h.Registros)
                .Where(h => h.UsuarioId == usuarioId && h.EstaActivo)
                .ToListAsync();

            var respuesta = habitos.Select(h => new
            {
                id = h.Id,
                nombre = h.Nombre,
                estadoHoy = h.Registros.FirstOrDefault(r => r.Fecha == hoy)?.Estado ?? "Pendiente",
                xpPotencial = Ayudantes.ConstantesGamificacion.XP_BASE_HABITO
            });

            return Ok(RespuestaApi<object>.Exitosa(respuesta));
        }

        [HttpPost("{id}/completar")]
        public async Task<IActionResult> Completar(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var resultado = await _servicioHabitos.CompletarHabito(id, usuarioId.Value);
            if (!resultado.Exito)
                return BadRequest(RespuestaApi<object>.Fallida("No se pudo completar el hábito"));

            return Ok(RespuestaApi<object>.Exitosa(new { xpGanado = resultado.XpGanado }));
        }

        [HttpPost("{id}/fallar")]
        public async Task<IActionResult> Fallar(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var resultado = await _servicioHabitos.FallarHabito(id, usuarioId.Value);

            if (!resultado.Exito)
            {
                return BadRequest(RespuestaApi<object>.Fallida(resultado.Mensaje));
            }

            return Ok(RespuestaApi<object>.Exitosa(new { rachaRota = true }));
        }

        [HttpGet("{id}/semana")]
        public async Task<IActionResult> Semana(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var desde = DateOnly.FromDateTime(DateTime.Today.AddDays(-6));
            var registros = await _contexto.RegistrosHabito
                .Include(r => r.Habito)
                .Where(r => r.HabitoId == id && r.Habito.UsuarioId == usuarioId.Value && r.Fecha >= desde)
                .OrderBy(r => r.Fecha)
                .Select(r => new { dia = r.Fecha.ToString("yyyy-MM-dd"), estado = r.Estado })
                .ToListAsync();

            return Ok(RespuestaApi<object>.Exitosa(registros));
        }
    }
}
