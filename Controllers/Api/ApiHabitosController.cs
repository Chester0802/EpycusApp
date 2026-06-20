using System.Linq;
using EpycusApp.Ayudantes;
using EpycusApp.DTOs;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/habitos")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
    public class ApiHabitosController : BaseApiController
    {
        private readonly IServicioHabitos _servicioHabitos;

        public ApiHabitosController(IServicioHabitos servicioHabitos)
        {
            _servicioHabitos = servicioHabitos;
        }

        [HttpGet]
        public async Task<IActionResult> ObtenerHabitos()
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var respuesta = await _servicioHabitos.ObtenerHabitosConEstadoHoy(usuarioId.Value);

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

            var respuesta = await _servicioHabitos.ObtenerHabitosActivosConEstadoHoy(usuarioId.Value);

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
                return BadRequest(RespuestaApi<CompletarHabitoRespuestaDto>.Fallida("No se pudo completar el hábito"));

            var respuesta = new CompletarHabitoRespuestaDto { XpGanado = resultado.XpGanado };
            return Ok(RespuestaApi<CompletarHabitoRespuestaDto>.Exitosa(respuesta));
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
                return BadRequest(RespuestaApi<FallarHabitoRespuestaDto>.Fallida(resultado.Mensaje));
            }

            var respuesta = new FallarHabitoRespuestaDto { RachaRota = true };
            return Ok(RespuestaApi<FallarHabitoRespuestaDto>.Exitosa(respuesta));
        }

        [HttpGet("{id}/semana")]
        public async Task<IActionResult> Semana(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
            {
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));
            }

            var registros = await _servicioHabitos.ObtenerRegistrosSemana(id, usuarioId.Value);

            return Ok(RespuestaApi<object>.Exitosa(registros));
        }

        [HttpGet("{id}")]
        public async Task<IActionResult> ObtenerPorId(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var habito = await _servicioHabitos.ObtenerPorIdViewModel(id);
            if (habito == null)
                return NotFound(RespuestaApi<object>.Fallida("Hábito no encontrado"));

            return Ok(RespuestaApi<object>.Exitosa(habito));
        }

        [HttpPost]
        public async Task<IActionResult> Crear([FromBody] CrearHabitoDto dto)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            int categoriaId = dto.CategoriaId;
            if (categoriaId <= 0 && !string.IsNullOrWhiteSpace(dto.Categoria))
            {
                var categorias = await _servicioHabitos.ObtenerCategoriasActivas();
                var cat = categorias.FirstOrDefault(c =>
                    c.Nombre.Equals(dto.Categoria, StringComparison.OrdinalIgnoreCase));
                if (cat != null) categoriaId = cat.Id;
            }

            var modelo = new CrearHabitoViewModel
            {
                Nombre = dto.Nombre,
                Descripcion = dto.Descripcion,
                CategoriaId = categoriaId,
                Frecuencia = dto.Frecuencia,
                DiasSemana = dto.DiasSemana?.ToList(),
                ConPomodoro = dto.ConPomodoro ?? false,
                RecordatorioHora = dto.RecordatorioHora != null ? TimeSpan.Parse(dto.RecordatorioHora) : null,
                EstaActivo = dto.EstaActivo ?? true
            };

            await _servicioHabitos.CrearHabito(modelo, usuarioId.Value);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpPut("{id}")]
        public async Task<IActionResult> Editar(int id, [FromBody] EditarHabitoDto dto)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var modelo = new EditarHabitoViewModel
            {
                Id = id,
                Nombre = dto.Nombre,
                Descripcion = dto.Descripcion,
                CategoriaId = dto.CategoriaId,
                Frecuencia = dto.Frecuencia,
                DiasSemana = dto.DiasSemana?.ToList(),
                ConPomodoro = dto.ConPomodoro ?? false,
                RecordatorioHora = dto.RecordatorioHora != null ? TimeSpan.Parse(dto.RecordatorioHora) : null,
                EstaActivo = dto.EstaActivo ?? true
            };

            await _servicioHabitos.EditarHabito(modelo, usuarioId.Value);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpDelete("{id}")]
        public async Task<IActionResult> Eliminar(int id)
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            await _servicioHabitos.EliminarHabito(id, usuarioId.Value);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpGet("dashboard")]
        public async Task<IActionResult> Dashboard()
        {
            var usuarioId = ObtenerUsuarioId();
            if (!usuarioId.HasValue)
                return Unauthorized(RespuestaApi<object>.Fallida("No autenticado"));

            var dashboard = await _servicioHabitos.ObtenerDashboard(usuarioId.Value);
            return Ok(RespuestaApi<object>.Exitosa(dashboard));
        }

        [HttpGet("categorias")]
        public async Task<IActionResult> Categorias()
        {
            var categorias = await _servicioHabitos.ObtenerCategoriasActivas();
            return Ok(RespuestaApi<object>.Exitosa(categorias));
        }
    }

    public class CrearHabitoDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public int CategoriaId { get; set; }
        public string? Categoria { get; set; }
        public string Frecuencia { get; set; } = "Diaria";
        public int[]? DiasSemana { get; set; }
        public bool? ConPomodoro { get; set; }
        public string? RecordatorioHora { get; set; }
        public bool? EstaActivo { get; set; }
    }

    public class EditarHabitoDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public int CategoriaId { get; set; }
        public string Frecuencia { get; set; } = "Diaria";
        public int[]? DiasSemana { get; set; }
        public bool? ConPomodoro { get; set; }
        public string? RecordatorioHora { get; set; }
        public bool? EstaActivo { get; set; }
    }
}
