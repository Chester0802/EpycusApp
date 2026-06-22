# Plan de Integración: Sistema de Sub-tareas + Tiempo de Enfoque en Misiones y Pomodoro

## Estado Actual: ✅ COMPLETADO (22/06/2026)

| Fase | Estado |
|------|--------|
| Fase 1 — Backend: Entidades y Migración | ✅ Completado |
| Fase 2 — Backend: Servicios y Lógica de Negocio | ✅ Completado |
| Fase 3 — Backend: DTOs y API Endpoints | ✅ Completado |
| Fase 4 — Frontend: Vistas MVC de Misiones | ✅ Completado |
| Fase 5 — Frontend: Integración en Pomodoro | ✅ Completado |
| Fase 6 — Lógica de Tiempo de Enfoque | ✅ Completado |
| Fase 7 — Verificación de Consistencia y Tests | ✅ Completado |

**Build:** 0 errores, 0 warnings
**Tests:** 225/225 pasan (10 nuevos tests de sub-tareas)

---

## Resumen de Archivos Creados vs Modificados

### Crear

| Archivo | Propósito |
|---------|-----------|
| `Models/Entidades/SubTarea.cs` | Entidad SubTarea |
| `DTOs/SubTareaDto.cs` | DTOs de respuesta/request |
| `Ayudantes/FormateadorTiempo.cs` | Helper de formato de tiempo |
| `EpycusApp.Tests/Unitarios/Servicios/ServicioSubTareasTests.cs` | 10 tests unitarios de sub-tareas |
| `Migrations/20260622214253_AddSubTareas.cs` | Migración EF Core |

### Modificar

| Archivo | Cambio |
|---------|--------|
| `Models/Entidades/SesionPomodoro.cs` | + `SubTareaId`, `SubTarea` nav prop |
| `Models/Entidades/Mision.cs` | + `SubTareas` collection nav prop |
| `Datos/ContextoAplicacion.cs` | + `DbSet<SubTarea>`, config relaciones, índices |
| `Servicios/Interfaces/IServicioMisiones.cs` | + 10 métodos de sub-tareas |
| `Servicios/Implementaciones/ServicioMisiones.cs` | + Implementación completa + lógica de completado automático + `Include(m => m.SubTareas)` en queries |
| `Servicios/Interfaces/IServicioPomodoro.cs` | + `subTareaId` en `IniciarSesion`, + `ObtenerSubTareasDisponibles` |
| `Servicios/Implementaciones/ServicioPomodoro.cs` | + Lógica de vinculación y acumulación de tiempo en `RegistrarCiclo` y `FinalizarSesion` |
| `DTOs/IniciarRequest.cs` | + `SubTareaId` |
| `DTOs/RespuestasApi.cs` | + `SubTareasCount`, `SubTareasCompletadas`, `TiempoEnfoqueSegundos` en `MisionListaItemResponse` |
| `Controllers/Api/ApiMisionesController.cs` | + 7 endpoints CRUD sub-tareas |
| `Controllers/Api/ApiPomodoroController.cs` | + `SubTareaId` en Iniciar, + endpoint `GET mision/{id}/sub-tareas` |
| `Views/Misiones/Index.cshtml` | + Expandible con sub-tareas, checkboxes, tiempo, crear inline |
| `Views/Pomodoro/Index.cshtml` | + Dropdown de sub-tareas al seleccionar misión, envío de subTareaId |

---

## Detalle de Implementación

### Fase 1 — Entidades y Migración

- `SubTarea.cs`: `Id`, `Nombre`, `Descripcion`, `EstaCompletada`, `Orden`, `TiempoEnfoqueSegundos`, `FechaCreacion`, `FechaCompletado`, `MisionId` (FK), `SesionesPomodoro` (nav)
- `SesionPomodoro.cs`: `SubTareaId` nullable, `SubTarea` nav
- `Mision.cs`: `SubTareas` collection
- `ContextoAplicacion.cs`:
  - `DbSet<SubTarea> SubTareas`
  - `SubTarea → Mision`: Cascade delete
  - `SesionPomodoro → SubTarea`: SetNull delete
  - Índices en `MisionId` y `SubTareaId`
- Migración `AddSubTareas` generada y compilada

### Fase 2 — Servicios

**`IServicioMisiones` / `ServicioMisiones`:**
- `ObtenerSubTareas`, `ObtenerSubTareaPorId`
- `CrearSubTarea` — valida que la misión no esté completada/fallida
- `EditarSubTarea` — valida estado de la misión
- `CompletarSubTarea` — si es la última pendiente, completa la misión automáticamente
- `DescompletarSubTarea` — revierte completado
- `EliminarSubTarea` — solo si misión no está completada/fallida
- `ObtenerTiempoEnfoqueSubTarea`, `ObtenerTiempoEnfoqueMision`
- `ObtenerMisionesDeUsuario` y `ObtenerPorId` ahora incluyen `SubTareas`

**`IServicioPomodoro` / `ServicioPomodoro`:**
- `IniciarSesion` acepta `subTareaId` opcional
- `RegistrarCiclo`: acumula `config.TiempoEstudioMin * 60` segundos en `SubTarea.TiempoEnfoqueSegundos`
- `FinalizarSesion`: calcula diferencia de tiempo real sobrante y la acumula
- `ObtenerSubTareasDisponibles`: retorna sub-tareas de una misión

### Fase 3 — API Endpoints

**`ApiMisionesController`:**
- `GET /api/misiones/{misionId}/sub-tareas` — lista
- `GET /api/misiones/{misionId}/sub-tareas/{id}` — detalle
- `POST /api/misiones/{misionId}/sub-tareas` — crear
- `PUT /api/misiones/{misionId}/sub-tareas/{id}` — editar
- `POST /api/misiones/{misionId}/sub-tareas/{id}/completar` — completar
- `POST /api/misiones/{misionId}/sub-tareas/{id}/descompletar` — descompletar
- `DELETE /api/misiones/{misionId}/sub-tareas/{id}` — eliminar

**`ApiPomodoroController`:**
- `POST /api/pomodoro/iniciar` — ahora acepta `subTareaId` en el body
- `GET /api/pomodoro/mision/{misionId}/sub-tareas` — sub-tareas disponibles

### Fase 4 — Frontend Misiones

- Cada tarjeta muestra contador de sub-tareas (ej: "2/5 sub-tareas")
- Botón expandible (Bootstrap collapse) que revela la lista
- Cada sub-tarea: checkbox (completar/descompletar), nombre, tiempo formateado, botón eliminar
- Input inline para agregar nueva sub-tarea
- Carga asíncrona vía `fetch` a la API
- Las acciones (crear, completar, eliminar) actualizan el DOM vía recarga de la lista

### Fase 5 — Frontend Pomodoro

- Al seleccionar una misión como tarea de enfoque, aparece un dropdown "Sub-tarea (opcional)"
- Se cargan las sub-tareas vía `GET /api/pomodoro/mision/{id}/sub-tareas`
- Al iniciar el Pomodoro, se envía `subTareaId` en el body si hay una sub-tarea seleccionada

### Fase 6 — Lógica de Tiempo de Enfoque

- `RegistrarCiclo`: `SubTarea.TiempoEnfoqueSegundos += config.TiempoEstudioMin * 60`
- `FinalizarSesion`: `diferencia = segundosReales - (ciclos * tiempoEstudio * 60)`, se suma si es positiva

### Fase 7 — Tests

**10 tests nuevos en `ServicioSubTareasTests`:**
1. Crear sub-tarea en misión válida → se crea correctamente
2. Crear en misión completada → lanza error
3. Crear en misión fallida → lanza error
4. Completar única sub-tarea pendiente → misión se completa automáticamente
5. Completar sub-tarea no última → misión no se completa
6. Eliminar en misión pendiente → se elimina
7. Eliminar en misión completada → lanza error
8. Obtener sub-tareas ordenadas → orden por `Orden` luego `FechaCreacion`
9. Descompletar sub-tarea → revierte estado
10. Editar sub-tarea → actualiza nombre, descripción y orden

---

## Notas Técnicas

- El proyecto usa .NET 9, ASP.NET Core MVC, EF Core con Pomelo MySQL
- Los tests usan InMemory database, Moq, FluentAssertions, xUnit
- El frontend usa Bootstrap 5, fetch API plano (sin framework JS)
- La migración debe aplicarse con `dotnet ef database update` en producción
- Endpoints protegidos con `[Authorize]` y rate limiting `Mobile`/`Pomodoro`
