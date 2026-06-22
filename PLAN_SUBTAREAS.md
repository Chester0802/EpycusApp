# Plan de Integración: Sistema de Sub-tareas + Tiempo de Enfoque en Misiones y Pomodoro

## Objetivo

Agregar un sistema de sub-tareas (checklist) dentro de cada misión, donde cada sub-tarea acumula tiempo de enfoque proveniente de sesiones Pomodoro. Este plan está diseñado para ser ejecutado en 3 rondas por 3 IAs distintas: **Implementación**, **Revisión**, **Corrección**.

---

## Fase 1 — Backend: Entidades y Migración

### 1.1 Nueva Entidad `SubTarea`

Crear `Models/Entidades/SubTarea.cs`:

```csharp
namespace EpycusApp.Models.Entidades
{
    public class SubTarea
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public bool EstaCompletada { get; set; } = false;
        public int Orden { get; set; } = 0;
        public int TiempoEnfoqueSegundos { get; set; } = 0;
        public DateTime FechaCreacion { get; set; } = DateTime.UtcNow;
        public DateTime? FechaCompletado { get; set; }
        public int MisionId { get; set; }
        public Mision Mision { get; set; } = null!;
        public ICollection<SesionPomodoro> SesionesPomodoro { get; set; } = new List<SesionPomodoro>();
    }
}
```

### 1.2 Modificar `SesionPomodoro`

Agregar propiedad `SubTareaId` nullable en `Models/Entidades/SesionPomodoro.cs`:

```csharp
public int? SubTareaId { get; set; }
public SubTarea? SubTarea { get; set; }
```

### 1.3 Modificar `Mision`

Agregar propiedad de navegación para SubTareas en `Models/Entidades/Mision.cs`:

```csharp
public ICollection<SubTarea> SubTareas { get; set; } = new List<SubTarea>();
```

### 1.4 Configuración en `ContextoAplicacion`

Agregar en `Datos/ContextoAplicacion.cs`:

**DbSet:**
```csharp
public DbSet<SubTarea> SubTareas { get; set; } = null!;
```

**Configuración en `OnModelCreating`:**
```csharp
modelBuilder.Entity<SubTarea>()
    .HasOne(st => st.Mision)
    .WithMany(m => m.SubTareas)
    .HasForeignKey(st => st.MisionId)
    .OnDelete(DeleteBehavior.Cascade);

modelBuilder.Entity<SesionPomodoro>()
    .HasOne(s => s.SubTarea)
    .WithMany(st => st.SesionesPomodoro)
    .HasForeignKey(s => s.SubTareaId)
    .OnDelete(DeleteBehavior.SetNull)
    .IsRequired(false);

modelBuilder.Entity<SubTarea>()
    .HasIndex(st => st.MisionId);

modelBuilder.Entity<SesionPomodoro>()
    .HasIndex(s => s.SubTareaId);
```

### 1.5 Migración

Desde Package Manager Console:
```
Add-Migration AddSubTareas
Update-Database
```

---

## Fase 2 — Backend: Servicios y Lógica de Negocio

### 2.1 Extender `IServicioMisiones`

Agregar en `Servicios/Interfaces/IServicioMisiones.cs`:

```csharp
Task<List<SubTarea>> ObtenerSubTareas(int misionId, int usuarioId);
Task<SubTarea?> ObtenerSubTareaPorId(int id, int usuarioId);
Task CrearSubTarea(string nombre, string? descripcion, int misionId, int usuarioId);
Task EditarSubTarea(int id, string nombre, string? descripcion, int? orden, int usuarioId);
Task CompletarSubTarea(int id, int usuarioId);
Task DescompletarSubTarea(int id, int usuarioId);
Task EliminarSubTarea(int id, int usuarioId);
Task<int> ObtenerTiempoEnfoqueSubTarea(int id, int usuarioId);
Task<int> ObtenerTiempoEnfoqueMision(int misionId, int usuarioId);
```

### 2.2 Implementar en `ServicioMisiones`

Agregar en `Servicios/Implementaciones/ServicioMisiones.cs`:

**Reglas de negocio:**
- Solo se pueden crear/editar sub-tareas en misiones `Pendiente` o `EnProgreso`
- Al completar la última sub-tarea pendiente, la misión debe marcarse como completada automáticamente (solo si todas las sub-tareas están completadas)
- Al completar una misión manualmente, todas las sub-tareas pendientes se marcan como completadas
- No se puede eliminar una sub-tarea si la misión está `Completado` o `Fallido`
- El tiempo de enfoque de una sub-tarea es la suma de los segundos de enfoque de las sesiones Pomodoro asociadas
- El tiempo de enfoque de una misión es la suma del tiempo de todas sus sub-tareas

### 2.3 Modificar `ServicioPomodoro` — Vínculo con SubTarea

**En `ServicioPomodoro.IniciarSesion()`:**
- Aceptar `int? subTareaId` como parámetro adicional
- Validar que la sub-tarea existe y pertenece al usuario (vía MisionId)
- Guardar `SubTareaId` en la sesión

**En `ServicioPomodoro.IniciarSesionSiNoActiva()`:**
- Propagar el nuevo parámetro `subTareaId`

**En `ServicioPomodoro.RegistrarCiclo()`:**
- Cuando se completa un ciclo y la sesión tiene `SubTareaId`, calcular segundos de enfoque del ciclo (`config.TiempoEstudioMin * 60`) y acumularlos en `SubTarea.TiempoEnfoqueSegundos`
- Si se completa la sesión y hay `SubTareaId`, también acumular el tiempo de la sesión finalizada

**En `ServicioPomodoro.FinalizarSesion()`:**
- Si la sesión tiene `SubTareaId`, calcular los segundos reales de la sesión (`(FechaFin - FechaInicio).TotalSeconds`) y sumarlos a `SubTarea.TiempoEnfoqueSegundos`

### 2.4 Extender `IServicioPomodoro` y su implementación

En `IServicioPomodoro`:
```csharp
// Modificar firmas existentes
Task<SesionPomodoro> IniciarSesion(int usuarioId, int? habitoId, int? misionId, int? subTareaId);
Task<(bool Exito, SesionPomodoro? Sesion, string? Error)> IniciarSesionSiNoActiva(int usuarioId, int? habitoId, int? misionId, int? subTareaId);

// Nuevo método
Task<List<SubTarea>> ObtenerSubTareasDisponibles(int usuarioId, int misionId);
```

**Implementación de `ObtenerSubTareasDisponibles`:**
```csharp
public async Task<List<SubTarea>> ObtenerSubTareasDisponibles(int usuarioId, int misionId)
{
    var mision = await _context.Misiones
        .Include(m => m.SubTareas)
        .FirstOrDefaultAsync(m => m.Id == misionId && m.UsuarioId == usuarioId);
    return mision?.SubTareas.OrderBy(st => st.Orden).ThenBy(st => st.FechaCreacion).ToList() ?? new();
}
```

---

## Fase 3 — Backend: DTOs y API Endpoints

### 3.1 DTOs para SubTarea

Crear en `DTOs/SubTareaDto.cs`:

```csharp
namespace EpycusApp.DTOs
{
    public class SubTareaResponse
    {
        public int Id { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public bool EstaCompletada { get; set; }
        public int Orden { get; set; }
        public int TiempoEnfoqueSegundos { get; set; }
        public string TiempoEnfoqueFormateado { get; set; } = string.Empty;
        public DateTime FechaCreacion { get; set; }
        public DateTime? FechaCompletado { get; set; }
        public int MisionId { get; set; }
    }

    public class CrearSubTareaDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
    }

    public class EditarSubTareaDto
    {
        public string Nombre { get; set; } = string.Empty;
        public string? Descripcion { get; set; }
        public int? Orden { get; set; }
    }
}
```

### 3.2 Modificar `IniciarRequest`

Agregar propiedad en `DTOs/IniciarRequest.cs`:
```csharp
public int? SubTareaId { get; set; }
```

### 3.3 API Endpoints en `ApiMisionesController`

Agregar:

```csharp
// GET api/misiones/{misionId}/sub-tareas
public async Task<IActionResult> ObtenerSubTareas(int misionId)

// GET api/misiones/{misionId}/sub-tareas/{id}
public async Task<IActionResult> ObtenerSubTarea(int misionId, int id)

// POST api/misiones/{misionId}/sub-tareas
public async Task<IActionResult> CrearSubTarea(int misionId, [FromBody] CrearSubTareaDto dto)

// PUT api/misiones/{misionId}/sub-tareas/{id}
public async Task<IActionResult> EditarSubTarea(int misionId, int id, [FromBody] EditarSubTareaDto dto)

// POST api/misiones/{misionId}/sub-tareas/{id}/completar
public async Task<IActionResult> CompletarSubTarea(int misionId, int id)

// POST api/misiones/{misionId}/sub-tareas/{id}/descompletar
public async Task<IActionResult> DescompletarSubTarea(int misionId, int id)

// DELETE api/misiones/{misionId}/sub-tareas/{id}
public async Task<IActionResult> EliminarSubTarea(int misionId, int id)
```

Cada endpoint debe validar que la misión pertenece al usuario autenticado (usar `ObtenerUsuarioId()` como en los demás endpoints).

### 3.4 Modificar `ApiPomodoroController`

**Modificar `Iniciar` endpoint:**
- Leer `SubTareaId` del `IniciarRequest`
- Pasar a `_servicioPomodoro.IniciarSesionSiNoActiva()`

**Nuevo endpoint:**
```csharp
// GET api/pomodoro/mision/{misionId}/sub-tareas
// Retorna lista de sub-tareas disponibles para vincular al Pomodoro
public async Task<IActionResult> ObtenerSubTareasDeMision(int misionId)
```

### 3.5 Modificar DTOs de Misión para incluir sub-tareas

En `MisionListaItemResponse` (DTOs/RespuestasApi.cs), agregar:
```csharp
public int SubTareasCount { get; set; }
public int SubTareasCompletadas { get; set; }
public int TiempoEnfoqueSegundos { get; set; }
```

---

## Fase 4 — Frontend: Vistas MVC de Misiones

### 4.1 Modificar `Views/Misiones/Index.cshtml`

Cada tarjeta de misión debe tener un **botón expandible** que muestre las sub-tareas:

```
┌─────────────────────────────────┐
│ [Prioridad] [Estado]            │
│ Nombre de la Misión             │
│ Curso: Matemáticas              │
│ 📅 Límite: 15/07/2026           │
│ ⏱ 2h 30m enfocados             │
│ [▼] 3/5 sub-tareas              │ ← expandible
│ [Iniciar] [Editar]              │
│ ─────────────────────           │
│ ├ ☑ Sub-tarea 1     ✔ 45m      │ ← visible al expandir
│ ├ ☐ Sub-tarea 2       30m      │
│ ├ ☐ Sub-tarea 3       15m      │
│ └ ☐ Sub-tarea 4       60m      │
│   [+ Nueva sub-tarea]           │
└─────────────────────────────────┘
```

**Requisitos de UI/UX:**
- Mostrar badge de progreso tipo "3/5" con barra de progreso circular o lineal
- Cada sub-tarea muestra: checkbox (completar/descompletar), nombre, tiempo de enfoque formateado (min o "h m")
- Las sub-tareas completadas se muestran en verde/tachado
- Botón "+ Nueva sub-tarea" agrega inline o abre modal pequeño
- Cada sub-tarea tiene botón de editar (lápiz) y eliminar (papelera)
- Al completar la última sub-tarea, recargar el estado de la misión padre
- Tiempo de enfoque se actualiza dinámicamente (ideal: polling cada 30s o al recargar)

**Implementación:**
- Usar un `<div class="collapse">` de Bootstrap para el acordeón de sub-tareas
- Cargar sub-tareas vía API fetch a `GET /api/misiones/{id}/sub-tareas`
- Las acciones (completar, crear, eliminar) son fetch asíncronos con actualización del DOM
- Agregar un `data-mision-id` a cada tarjeta para identificar

### 4.2 Modificar `Views/Misiones/Crear.cshtml`

No necesita cambios significativos (las sub-tareas se agregan después de crear la misión).

### 4.3 Modificar `Views/Misiones/Editar.cshtml`

Agregar sección de gestión de sub-tareas al final del formulario:
- Lista de sub-tareas existentes con edición inline
- Botón para agregar nueva sub-tarea
- Opción de reordenar (simple: mover arriba/abajo)

---

## Fase 5 — Frontend: Integración en Pomodoro

### 5.1 Modificar `Views/Pomodoro/Index.cshtml`

**En el selector de tareas de enfoque (donde se listan hábitos y misiones):**

Cuando el usuario selecciona una **Misión** como tarea de enfoque, debe aparecer un **segundo desplegable** con las sub-tareas de esa misión.

```
Seleccionar tarea:
[Tipo ▼] [Misión   ▼]
[Misión ▼] [Misión: Estudio Álgebra ▼]
          [Sub-tarea ▼] [▼ Ejercicios 1-10   ]
                        [  Leer capítulo 3    ]
                        [  Resumen y fórmula  ]
```

**Flujo:**
1. Usuario selecciona tipo "Misión"
2. Usuario selecciona la misión específica
3. Aparece dropdown "Sub-tarea" con las sub-tareas disponibles (no completadas primero)
4. Al iniciar el Pomodoro, se envía `misionId` + `subTareaId`

**En la UI del temporizador activo:**
- Mostrar: `🔍 Misión: Álgebra Lineal > Ejercicios 1-10` cuando hay sub-tarea
- Mostrar: `🔍 Misión: Álgebra Lineal` cuando solo hay misión sin sub-tarea

**En el resumen de la sesión finalizada:**
- Mostrar el tiempo acumulado en la sub-tarea

### 5.2 Agregar método para obtener sub-tareas en el modelo del Index

El `PomodoroIndexViewModel.TareasEnfoque` (tipo `TareaPomodoro`) debe incluir las sub-tareas como parte de cada misión. Opciones:
- Opción A: Agregar una propiedad `SubTareas` en `TareaPomodoro`
- Opción B: Crear un endpoint separado que el frontend consulta al seleccionar una misión

**Recomendación: Opción B** (más limpio, menos datos transferidos):
- El frontend ya tiene las misiones en `tareasEnfoque`
- Al seleccionar una misión, el JS hace fetch a `GET /api/pomodoro/mision/{misionId}/sub-tareas`
- Renderiza el dropdown de sub-tareas

---

## Fase 6 — Lógica de Tiempo de Enfoque

### 6.1 Cálculo del tiempo de enfoque

**Al completar un ciclo (`RegistrarCiclo`):**
```
si sesion.SubTareaId tiene valor:
    segundosEnfoque = config.TiempoEstudioMin * 60
    subTarea.TiempoEnfoqueSegundos += segundosEnfoque
    _context.SubTareas.Update(subTarea)
```

**Al finalizar sesión (`FinalizarSesion`):**
```
si sesion.SubTareaId tiene valor:
    segundosReales = (sesion.FechaFin - sesion.FechaInicio).TotalSeconds
    // Solo sumar si es mayor que lo ya registrado por ciclos
    segundosYaRegistrados = sesion.CiclosCompletados * config.TiempoEstudioMin * 60
    if (segundosReales > segundosYaRegistrados):
        diferencia = (int)(segundosReales - segundosYaRegistrados)
        subTarea.TiempoEnfoqueSegundos += diferencia
    _context.SubTareas.Update(subTarea)
```

### 6.2 Formateo del tiempo

Crear helper estático en `Ayudantes/FormateadorTiempo.cs`:

```csharp
public static class FormateadorTiempo
{
    public static string FormatearSegundos(int segundos)
    {
        if (segundos < 60) return $"{segundos}s";
        if (segundos < 3600) return $"{segundos / 60}min";
        int horas = segundos / 3600;
        int mins = (segundos % 3600) / 60;
        return mins > 0 ? $"{horas}h {mins}m" : $"{horas}h";
    }
}
```

Usar este helper en los DTOs de respuesta (`SubTareaResponse.TiempoEnfoqueFormateado`).

---

## Fase 7 — Verificación de Consistencia y Tests

### 7.1 Reglas de consistencia a verificar

1. Una sub-tarea no puede pertenecer a una misión de otro usuario
2. No se pueden agregar sub-tareas a misiones completadas o fallidas
3. Al completar una misión manualmente, todas sus sub-tareas pendientes se marcan como completadas
4. Al descompletar una sub-tarea, la misión padre no debe revertirse automáticamente (la misión sigue completada)
5. El tiempo de enfoque de una sub-tarea solo se acumula desde sesiones Pomodoro donde se vinculó explícitamente esa sub-tarea
6. Si se elimina una sub-tarea, las sesiones Pomodoro vinculadas deben mantener `SubTareaId = null` (SetNull)
7. Si se elimina una misión, todas sus sub-tareas se eliminan en cascada

### 7.2 Tests unitarios sugeridos (nuevos)

Crear `EpycusApp.Tests/Unitarios/Servicios/ServicioSubTareasTests.cs`:

| Test | Descripción |
|------|-------------|
| `CrearSubTarea_EnMisionValida_CreaCorrectamente` | Crear sub-tarea en misión Pendiente |
| `CrearSubTarea_EnMisionCompletada_LanzaError` | No permitir crear en misión completada |
| `CompletarSubTarea_UnicaPendiente_CompletaMision` | Al completar última sub-tarea, misión se completa |
| `CompletarSubTarea_NoEsUltima_NoCompletaMision` | Quedan otras pendientes, misión no se completa |
| `EliminarSubTarea_EnMisionPendiente_Elimina` | Eliminación normal |
| `TiempoEnfoqueSubTarea_AcumulaSesiones_SumaCorrecta` | Múltiples sesiones suman tiempo |
| `ObtenerSubTareas_OrdenCorrecto` | Orden por `Orden` luego `FechaCreacion` |

### 7.3 Tests a modificar

- `ServicioMisionesTests`: Agregar setup de sub-tareas en los tests de completar misión
- `ServicioPomodoroTests`: Modificar tests de `IniciarSesion`, `RegistrarCiclo`, `FinalizarSesion` para incluir el nuevo parámetro `subTareaId`

---

## Resumen de Archivos a Crear vs Modificar

### Crear
| Archivo | Propósito |
|---------|-----------|
| `Models/Entidades/SubTarea.cs` | Entidad SubTarea |
| `DTOs/SubTareaDto.cs` | DTOs de respuesta/request |
| `Ayudantes/FormateadorTiempo.cs` | Helper de formato de tiempo |
| `EpycusApp.Tests/Unitarios/Servicios/ServicioSubTareasTests.cs` | Tests de sub-tareas |

### Modificar
| Archivo | Cambio |
|---------|--------|
| `Models/Entidades/SesionPomodoro.cs` | + SubTareaId, SubTarea nav prop |
| `Models/Entidades/Mision.cs` | + SubTareas collection nav prop |
| `Datos/ContextoAplicacion.cs` | + DbSet<SubTarea>, config relaciones, índices |
| `Servicios/Interfaces/IServicioMisiones.cs` | + 9 métodos de sub-tareas |
| `Servicios/Implementaciones/ServicioMisiones.cs` | + Implementación completa + lógica de completado automático |
| `Servicios/Interfaces/IServicioPomodoro.cs` | + SubTareaId en IniciarSesion, + ObtenerSubTareasDisponibles |
| `Servicios/Implementaciones/ServicioPomodoro.cs` | + Lógica de vinculación y acumulación de tiempo |
| `DTOs/IniciarRequest.cs` | + SubTareaId |
| `DTOs/RespuestasApi.cs` | + Propiedades en MisionListaItemResponse |
| `Controllers/Api/ApiMisionesController.cs` | + 7 endpoints CRUD sub-tareas |
| `Controllers/Api/ApiPomodoroController.cs` | + SubTareaId en Iniciar, + endpoint sub-tareas |
| `Controllers/MisionesController.cs` | + Actions para vistas MVC (si aplica) |
| `Views/Misiones/Index.cshtml` | + Expandible con sub-tareas, checkboxes, tiempo |
| `Views/Misiones/Editar.cshtml` | + Gestión de sub-tareas |
| `Views/Pomodoro/Index.cshtml` | + Dropdown de sub-tareas, display en timer activo |

---

## Orden de Implementación Sugerido (para IA #1)

```
Paso 1:  SubTarea.cs (entidad)
Paso 2:  Modificar SesionPomodoro.cs y Mision.cs
Paso 3:  ContextoAplicacion.cs (DbSet + config)
Paso 4:  Migración (Add-Migration / Update-Database)
Paso 5:  FormateadorTiempo.cs
Paso 6:  SubTareaDto.cs
Paso 7:  IServicioMisiones (agregar métodos)
Paso 8:  ServicioMisiones (implementar)
Paso 9:  Modificar IServicioPomodoro + ServicioPomodoro
Paso 10: Modificar IniciarRequest.cs + RespuestasApi.cs
Paso 11: ApiMisionesController (nuevos endpoints)
Paso 12: ApiPomodoroController (modificaciones)
Paso 13: Views/Misiones/Index.cshtml
Paso 14: Views/Misiones/Editar.cshtml
Paso 15: Views/Pomodoro/Index.cshtml
Paso 16: Tests
Paso 17: dotnet build + dotnet test
```

---

## Checklist de Revisión (para IA #2)

- [ ] `SubTarea.cs` tiene todas las propiedades necesarias y relaciones correctas
- [ ] `ContextoAplicacion.cs` tiene DbSet y config de relaciones con DeleteBehavior correcto
- [ ] Migración genera las tablas y columnas correctas (ver SQL generada)
- [ ] `ServicioMisiones.CrearSubTarea` valida que la misión no esté completada/fallida
- [ ] `ServicioMisiones.CompletarSubTarea` verifica si es la última y auto-completa la misión
- [ ] `ServicioPomodoro.IniciarSesion` acepta y valida `subTareaId`
- [ ] `ServicioPomodoro.RegistrarCiclo` acumula tiempo de enfoque en la sub-tarea
- [ ] `ServicioPomodoro.FinalizarSesion` acumula tiempo real de enfoque
- [ ] API endpoints validan ownership del usuario en cada operación
- [ ] `IniciarRequest.SubTareaId` es nullable y opcional
- [ ] Frontend de Misiones muestra sub-tareas expandibles con tiempo
- [ ] Frontend de Pomodoro muestra dropdown de sub-tareas al seleccionar misión
- [ ] Tiempo de enfoque se formatea correctamente (segundos, minutos, horas)
- [ ] `dotnet build` compila sin errores
- [ ] `dotnet test` pasa (tests existentes + nuevos)

---

## Notas para IA #3 (Corrección)

Al recibir el reporte de IA #2 con los issues encontrados, IA #3 debe:

1. Leer este plan completo
2. Leer el reporte de revisión de IA #2
3. Para cada issue marcado como ❌:
   - Identificar el archivo y línea exacta
   - Aplicar la corrección según el plan
   - Verificar que no rompe otras funcionalidades
4. Ejecutar `dotnet build` y `dotnet test` después de cada corrección
5. Si IA #2 marcó algo como ⚠️ (discutible), evaluar y decidir si corregir
6. Devolver el diff completo de cambios realizados
