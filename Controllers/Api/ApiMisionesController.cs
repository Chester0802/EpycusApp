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
    [Route("api/v1/misiones")]
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
            var resultado = misiones.Select(m => new MisionListaItemResponse
            {
                Id = m.Id,
                Nombre = m.Nombre,
                Descripcion = m.Descripcion,
                NombreCurso = m.NombreCurso,
                Prioridad = m.Prioridad,
                Estado = m.Estado,
                FechaLimite = m.FechaLimite == DateOnly.MinValue ? string.Empty : m.FechaLimite.ToString("yyyy-MM-dd"),
                XpOtorgado = m.XpOtorgado,
                FechaCreacion = m.FechaCreacion,
                CategoriaId = m.CategoriaId,
                SubTareasCount = m.SubTareas?.Count ?? 0,
                SubTareasCompletadas = m.SubTareas?.Count(st => st.EstaCompletada) ?? 0,
                TiempoEnfoqueSegundos = m.SubTareas?.Sum(st => st.TiempoEnfoqueSegundos) ?? 0
            }).ToList();
            return Ok(RespuestaApi<List<MisionListaItemResponse>>.Exitosa(resultado));
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
        public async Task<IActionResult> Crear([FromBody] CrearMisionDto? dto)
        {
            if (dto == null)
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("Solicitud inválida"));

            var usuarioId = ObtenerUsuarioId()!.Value;

            var modelo = new CrearMisionViewModel
            {
                Nombre = dto.Nombre,
                Descripcion = dto.Descripcion,
                NombreCurso = dto.NombreCurso,
                FechaLimite = string.IsNullOrEmpty(dto.FechaLimite) ? default : DateTime.Parse(dto.FechaLimite),
                Prioridad = dto.Prioridad,
                ConPomodoro = dto.ConPomodoro ?? false,
                CategoriaId = dto.CategoriaId ?? 0
            };

            try
            {
                await _servicioMisiones.CrearMision(modelo, usuarioId);
            }
            catch (InvalidOperationException ex)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(ex.Message));
            }
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpPut("{id}")]
        public async Task<IActionResult> Editar(int id, [FromBody] EditarMisionDto? dto)
        {
            if (dto == null)
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida("Solicitud inválida"));

            var usuarioId = ObtenerUsuarioId()!.Value;

            var modelo = new EditarMisionViewModel
            {
                Id = id,
                Nombre = dto.Nombre,
                Descripcion = dto.Descripcion,
                NombreCurso = dto.NombreCurso,
                FechaLimite = string.IsNullOrEmpty(dto.FechaLimite) ? default : DateTime.Parse(dto.FechaLimite),
                Prioridad = dto.Prioridad,
                ConPomodoro = dto.ConPomodoro ?? false,
                CategoriaId = dto.CategoriaId
            };

            try
            {
                await _servicioMisiones.EditarMision(modelo, usuarioId);
            }
            catch (InvalidOperationException ex)
            {
                return BadRequest(RespuestaApi<MensajeResponseDto>.Fallida(ex.Message));
            }
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpDelete("{id}")]
        public async Task<IActionResult> Eliminar(int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioMisiones.EliminarMision(id, usuarioId);
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
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

            return Ok(RespuestaApi<MisionCompletarResponse>.Exitosa(new MisionCompletarResponse { XpGanado = resultado.XpGanado }));
        }

        [HttpPost("{id}/estado")]
        public async Task<IActionResult> CambiarEstado(int id, [FromBody] EstadoDto dto)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            await _servicioMisiones.CambiarEstado(id, dto.Estado, usuarioId);
            return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
        }

        [HttpGet("{misionId}/sub-tareas")]
        public async Task<IActionResult> ObtenerSubTareas(int misionId)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var subTareas = await _servicioMisiones.ObtenerSubTareas(misionId, usuarioId);
            var resultado = subTareas.Select(st => new SubTareaResponse
            {
                Id = st.Id,
                Nombre = st.Nombre,
                Descripcion = st.Descripcion,
                EstaCompletada = st.EstaCompletada,
                Orden = st.Orden,
                TiempoEnfoqueSegundos = st.TiempoEnfoqueSegundos,
                TiempoEnfoqueFormateado = FormateadorTiempo.FormatearSegundos(st.TiempoEnfoqueSegundos),
                FechaCreacion = st.FechaCreacion,
                FechaCompletado = st.FechaCompletado,
                MisionId = st.MisionId
            }).ToList();
            return Ok(RespuestaApi<List<SubTareaResponse>>.Exitosa(resultado));
        }

        [HttpGet("{misionId}/sub-tareas/{id}")]
        public async Task<IActionResult> ObtenerSubTarea(int misionId, int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            var subTarea = await _servicioMisiones.ObtenerSubTareaPorId(id, usuarioId);
            if (subTarea == null)
                return NotFound(RespuestaApi<object>.Fallida("Sub-tarea no encontrada"));

            var resultado = new SubTareaResponse
            {
                Id = subTarea.Id,
                Nombre = subTarea.Nombre,
                Descripcion = subTarea.Descripcion,
                EstaCompletada = subTarea.EstaCompletada,
                Orden = subTarea.Orden,
                TiempoEnfoqueSegundos = subTarea.TiempoEnfoqueSegundos,
                TiempoEnfoqueFormateado = FormateadorTiempo.FormatearSegundos(subTarea.TiempoEnfoqueSegundos),
                FechaCreacion = subTarea.FechaCreacion,
                FechaCompletado = subTarea.FechaCompletado,
                MisionId = subTarea.MisionId
            };
            return Ok(RespuestaApi<SubTareaResponse>.Exitosa(resultado));
        }

        [HttpPost("{misionId}/sub-tareas")]
        public async Task<IActionResult> CrearSubTarea(int misionId, [FromBody] CrearSubTareaDto dto)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            try
            {
                await _servicioMisiones.CrearSubTarea(dto.Nombre, dto.Descripcion, misionId, usuarioId);
                return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
            }
            catch (Exception ex)
            {
                return BadRequest(RespuestaApi<object>.Fallida(ex.Message));
            }
        }

        [HttpPut("{misionId}/sub-tareas/{id}")]
        public async Task<IActionResult> EditarSubTarea(int misionId, int id, [FromBody] EditarSubTareaDto dto)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            try
            {
                await _servicioMisiones.EditarSubTarea(id, dto.Nombre, dto.Descripcion, dto.Orden, usuarioId);
                return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
            }
            catch (Exception ex)
            {
                return BadRequest(RespuestaApi<object>.Fallida(ex.Message));
            }
        }

        [HttpPost("{misionId}/sub-tareas/{id}/completar")]
        public async Task<IActionResult> CompletarSubTarea(int misionId, int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            try
            {
                await _servicioMisiones.CompletarSubTarea(id, usuarioId);
                return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
            }
            catch (Exception ex)
            {
                return BadRequest(RespuestaApi<object>.Fallida(ex.Message));
            }
        }

        [HttpPost("{misionId}/sub-tareas/{id}/descompletar")]
        public async Task<IActionResult> DescompletarSubTarea(int misionId, int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            try
            {
                await _servicioMisiones.DescompletarSubTarea(id, usuarioId);
                return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
            }
            catch (Exception ex)
            {
                return BadRequest(RespuestaApi<object>.Fallida(ex.Message));
            }
        }

        [HttpDelete("{misionId}/sub-tareas/{id}")]
        public async Task<IActionResult> EliminarSubTarea(int misionId, int id)
        {
            var usuarioId = ObtenerUsuarioId()!.Value;
            try
            {
                await _servicioMisiones.EliminarSubTarea(id, usuarioId);
                return Ok(RespuestaApi<SuccessResponseDto>.Exitosa(new SuccessResponseDto()));
            }
            catch (Exception ex)
            {
                return BadRequest(RespuestaApi<object>.Fallida(ex.Message));
            }
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
        public int? CategoriaId { get; set; }
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
