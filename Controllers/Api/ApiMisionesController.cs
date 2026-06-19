using EpycusApp.Ayudantes;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;

namespace EpycusApp.Controllers.Api
{
    [ApiController]
    [Route("api/misiones")]
    [Authorize]
    [EnableRateLimiting("Mobile")]
    public class ApiMisionesController : BaseApiController
    {
        private readonly IServicioMisiones _servicioMisiones;

        public ApiMisionesController(IServicioMisiones servicioMisiones)
        {
            _servicioMisiones = servicioMisiones;
        }

        [HttpGet]
        public async Task<IActionResult> ObtenerMisiones()
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var misiones = await _servicioMisiones.ObtenerMisionesDeUsuario(usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(misiones));
        }

        [HttpGet("{id}")]
        public async Task<IActionResult> ObtenerPorId(int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var mision = await _servicioMisiones.ObtenerPorId(id);
            if (mision == null)
                return NotFound(RespuestaApi<object>.Fallida("Misión no encontrada"));
            return Ok(RespuestaApi<object>.Exitosa(mision));
        }

        [HttpPost]
        public async Task<IActionResult> Crear([FromBody] CrearMisionDto dto)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;

            var modelo = new CrearMisionViewModel
            {
                Nombre = dto.Nombre,
                Descripcion = dto.Descripcion,
                NombreCurso = dto.NombreCurso,
                FechaLimite = DateTime.Parse(dto.FechaLimite),
                Prioridad = dto.Prioridad,
                ConPomodoro = dto.ConPomodoro ?? false,
                CategoriaId = dto.CategoriaId
            };

            await _servicioMisiones.CrearMision(modelo, usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpPut("{id}")]
        public async Task<IActionResult> Editar(int id, [FromBody] EditarMisionDto dto)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;

            var modelo = new EditarMisionViewModel
            {
                Id = id,
                Nombre = dto.Nombre,
                Descripcion = dto.Descripcion,
                NombreCurso = dto.NombreCurso,
                FechaLimite = DateTime.Parse(dto.FechaLimite),
                Prioridad = dto.Prioridad,
                ConPomodoro = dto.ConPomodoro ?? false,
                CategoriaId = dto.CategoriaId
            };

            await _servicioMisiones.EditarMision(modelo, usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpDelete("{id}")]
        public async Task<IActionResult> Eliminar(int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioMisiones.EliminarMision(id, usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpPost("{id}/completar")]
        public async Task<IActionResult> Completar(int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var resultado = await _servicioMisiones.CompletarMision(id, usuarioId);

            if (!resultado.Exito)
            {
                return BadRequest(RespuestaApi<object>.Fallida("No se pudo completar la misión"));
            }

            return Ok(RespuestaApi<object>.Exitosa(new { xpGanado = resultado.XpGanado }));
        }

        [HttpPost("{id}/estado")]
        public async Task<IActionResult> CambiarEstado(int id, [FromBody] EstadoDto dto)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioMisiones.CambiarEstado(id, dto.Estado, usuarioId);
            return Ok(RespuestaApi<object>.Exitosa(new { success = true }));
        }

        [HttpGet("categorias")]
        public async Task<IActionResult> Categorias()
        {
            var categorias = await _servicioMisiones.ObtenerCategoriasMisionAsync();
            return Ok(RespuestaApi<object>.Exitosa(categorias));
        }

        public class EstadoDto
        {
            public string Estado { get; set; } = string.Empty;
        }
    }

    public class CrearMisionDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public string? NombreCurso { get; set; }
        public string FechaLimite { get; set; } = string.Empty;
        public string Prioridad { get; set; } = "Media";
        public bool? ConPomodoro { get; set; }
        public int CategoriaId { get; set; }
    }

    public class EditarMisionDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public string? NombreCurso { get; set; }
        public string FechaLimite { get; set; } = string.Empty;
        public string Prioridad { get; set; } = "Media";
        public bool? ConPomodoro { get; set; }
        public int CategoriaId { get; set; }
    }
}
