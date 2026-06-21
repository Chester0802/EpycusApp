# Auditoría del Módulo Pomodoro

**Fecha:** 2026-06-21
**Puntuación general:** 4.5 / 10

---

## Archivos del módulo (12)

| # | Archivo | Líneas | Rol |
|---|---------|--------|-----|
| 1 | `Models/Entidades/ConfiguracionPomodoro.cs` | 15 | Entidad configuración usuario |
| 2 | `Models/Entidades/SesionPomodoro.cs` | 18 | Entidad sesión de enfoque |
| 3 | `Models/Entidades/TipPomodoro.cs` | 9 | Entidad tips/consejos |
| 4 | `DTOs/ActualizarConfiguracionPomodoroDto.cs` | 21 | DTO para actualizar config |
| 5 | `Servicios/Interfaces/IServicioPomodoro.cs` | 21 | Contrato del servicio |
| 6 | `Servicios/Implementaciones/ServicioPomodoro.cs` | 208 | Lógica de negocio |
| 7 | `Controllers/Api/ApiPomodoroController.cs` | 132 | API REST |
| 8 | `Controllers/PomodoroController.cs` | 50 | Controller MVC |
| 9 | `ViewModels/PomodoroIndexViewModel.cs` | 29 | ViewModels |
| 10 | `Views/Pomodoro/Index.cshtml` | 475 | Vista principal |
| 11 | `Views/Pomodoro/Configuracion.cshtml` | 42 | Vista configuración |
| 12 | `EpycusApp.Tests/.../ServicioPomodoroTests.cs` | 192 | Tests unitarios |

---

## 🔴 CRÍTICOS (5)

### 1. Inflación masiva de XP — `RegistrarCiclo` acumula XP de forma multiplicativa

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs` — líneas 44-72
**Impacto:** Alto — Invalida todo el sistema de gamificación (niveles, leaderboards, logros)

**Problema:**
Cada llamada a `RegistrarCiclo` recibe `ciclosCompletados` (el total acumulado, ej: 1, 2, 3...) y calcula `xpGanado = ciclosCompletados * XP_BASE`. El frontend llama a este endpoint CADA VEZ que un ciclo termina, pasando el total acumulado de ciclos.

Ejemplo con 3 ciclos completados:
- Ciclo 1 → frontend envía `ciclosCompletados: 1` → `xpGanado = 1 * 15 = 15` → `SumarXP(15)`. Total XP usuario: +15
- Ciclo 2 → frontend envía `ciclosCompletados: 2` → `xpGanado = 2 * 15 = 30` → `SumarXP(30)`. Total XP usuario: +45
- Ciclo 3 → frontend envía `ciclosCompletados: 3` → `xpGanado = 3 * 15 = 45` → `SumarXP(45)`. Total XP usuario: +90

**Resultado:** 90 XP en lugar de 45 XP esperados. Factor de error: 2×.

**Sugerencia de corrección:**
Cambiar `RegistrarCiclo` para que solo otorgue XP incremental (1 ciclo × XP_BASE = 15 por ciclo), y no el total acumulado. El cálculo del XP debería ser `XP_BASE_POMODORO` por cada ciclo individual, no `ciclosCompletados * XP_BASE_POMODORO`.

```csharp
// En ServicioPomodoro.cs - RegistrarCiclo
// Calcular XP incremental (solo este ciclo, no el acumulado)
int xpGanado = ConstantesGamificacion.XP_BASE_POMODORO; // Siempre 15 por ciclo
sesion.XpOtorgado += xpGanado; // Acumular en la sesión
```

O bien, cambiar el contrato para que el frontend solo envíe `1` cada vez, y que el backend acumule.

---

### 2. `FinalizarSesion` otorga XP nuevamente (doble descuento)

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs` — líneas 74-90
**Impacto:** Alto — Si se llama a `RegistrarCiclo` + `FinalizarSesion` con el mismo `ciclosCompletados`, el XP se otorga dos veces

**Problema:**
`RegistrarCiclo` ya suma XP vía `_servicioGamificacion.SumarXP()`. Si luego se llama a `FinalizarSesion` (que ahora también suma XP después de la corrección anterior), el usuario recibe XP duplicado.

**Sugerencia de corrección:**
- `FinalizarSesion` NO debe llamar a `SumarXP`. Solo debe persistir el estado de la sesión.
- Si se quieren otorgar XP de finalización, debe hacerse solo por los ciclos que NO se hayan registrado previamente.

```csharp
public async Task FinalizarSesion(int sesionId, int ciclosCompletados)
{
    var sesion = await _context.SesionesPomodoro.FirstOrDefaultAsync(s => s.Id == sesionId);
    if (sesion is null) return;

    sesion.CiclosCompletados = ciclosCompletados;
    sesion.FechaFin = DateTime.UtcNow;
    sesion.FueCompletada = true;

    // No otorgar XP aquí, ya se otorgó en RegistrarCiclo
    // Si hay ciclos no registrados, calcular diferencia y otorgar solo esos

    await _context.SaveChangesAsync();
}
```

---

### 3. `Notificaciones.mostrarExito(...)` lanza ReferenceError en runtime

**Archivo:** `Views/Pomodoro/Index.cshtml` — línea 422
**Impacto:** Alto — Excepción JS no capturada que rompe el flujo del temporizador

**Problema:**
Se invoca `Notificaciones.mostrarExito("...")` al sugerir descanso largo, pero el objeto global `Notificaciones` **no está definido en ningún archivo** del proyecto.

**Código problemático:**
```javascript
if (data.datos.sugerirDescanso) {
    Notificaciones.mostrarExito("¡Alerta de Bienestar! Has completado varios ciclos seguidos. Te sugerimos tomar un Descanso Largo ahora.");
    setMode('largo');
    return;
}
```

**Sugerencia de corrección:**
Reemplazar por una función JS existente o implementar una función simple de notificación (ej: `alert()`, o mostrar un mensaje en el DOM).

---

### 4. Vista `Configuracion.cshtml` es no-funcional (dead page)

**Archivo:** `Controllers/PomodoroController.cs` — solo tiene action `Index()`
**Archivo:** `Views/Pomodoro/Configuracion.cshtml` — 42 líneas
**Impacto:** Medio — El formulario de configuración no funciona, publica a 404

**Problema:**
No existe action `Configuracion` (ni GET ni POST) en `PomodoroController`. El formulario en `Configuracion.cshtml` publica a `POST /Pomodoro/Configuracion` que devuelve 404. El usuario no puede guardar configuración por esta vía.

**Sugerencia de corrección:**
Agregar actions en `PomodoroController`:
```csharp
[HttpGet]
public async Task<IActionResult> Configuracion()
{
    var usuarioId = ObtenerUsuarioId();
    if (usuarioId == 0) return Challenge();
    var config = await _servicioPomodoro.ObtenerConfiguracion(usuarioId);
    var dto = new ActualizarConfiguracionPomodoroDto
    {
        TiempoEstudioMin = config.TiempoEstudioMin,
        TiempoDescansoMin = config.TiempoDescansoMin,
        TiempoDescansoLargoMin = config.TiempoDescansoLargoMin,
        CiclosAntesDescansoLargo = config.CiclosAntesDescansoLargo,
        SonidoActivo = config.SonidoActivo
    };
    return View(dto);
}

[HttpPost]
public async Task<IActionResult> Configuracion(ActualizarConfiguracionPomodoroDto dto)
{
    var usuarioId = ObtenerUsuarioId();
    if (usuarioId == 0) return Challenge();
    if (!ModelState.IsValid) return View(dto);
    await _servicioPomodoro.ActualizarConfiguracion(usuarioId, dto);
    return RedirectToAction("Index");
}
```

---

### 5. Inconsistencia en el ciclo de vida de la sesión (frontend vs backend)

**Archivo:** `Views/Pomodoro/Index.cshtml` — líneas 406-418
**Impacto:** Medio — Sesiones huérfanas en BD, cancelaciones incorrectas

**Problema:**
Tras `ciclo-completado`, el frontend asigna `timerState.sesionId = null` (línea 418) aunque la sesión sigue activa en backend. Luego, si el usuario vuelve a iniciar, se crea una NUEVA sesión. La sesión anterior queda "activa" (sin `FechaFin`) en BD.

Además, `stopTimer()` (línea 376) intenta cancelar la sesión vía API, pero si `sesionId` ya es `null`, no cancela nada, dejando la sesión huérfana.

**Sugerencia de corrección:**
- No setear `sesionId = null` tras ciclo-completado. La sesión debe mantenerse activa hasta que el usuario explícitamente la finalice o cancele.
- Al completar un ciclo, el backend debe marcar la sesión como "en progreso" pero no cerrarla.
- Al cambiar a modo descanso y luego volver a enfoque, reutilizar la misma `sesionId` si sigue activa.

---

## 🟡 ALTOS (6)

### 6. Timezone mismatch: `DateTime.Today` (local) vs `DateTime.UtcNow` (UTC)

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs` — líneas 153, 160-164, 30, 82, 94
**Archivo:** `Models/Entidades/SesionPomodoro.cs` — línea 6
**Impacto:** Medio — Sesiones se filtran incorrectamente según zona horaria del servidor

**Problema:**
`ObtenerSesionesHoyAsync` compara `FechaInicio >= DateTime.Today` (fecha local del servidor) pero `FechaInicio` se almacena como `DateTime.UtcNow`. En servidores con zona horaria distinta a la del usuario, las sesiones se filtran incorrectamente.

**Sugerencia de corrección:**
Usar `DateTime.UtcNow.Date` para consistencia:
```csharp
var hoy = DateTime.UtcNow.Date;
return await _context.SesionesPomodoro
    .Where(s => s.UsuarioId == usuarioId && s.FechaInicio >= hoy)
    ...
```

---

### 7. XP hardcodeado (15) en frontend

**Archivo:** `Views/Pomodoro/Index.cshtml` — línea 415
**Impacto:** Medio — Si `XP_BASE_POMODORO` cambia, frontend queda desincronizado

**Código problemático:**
```javascript
timerState.xpTotal += 15; // 15 XP por ciclo
```

El frontend ignora `data.datos.xpGanado` que la API devuelve. Debería usar ese valor.

**Sugerencia de corrección:**
```javascript
if (res.ok) {
    const data = await res.json();
    timerState.xpTotal += data.datos.xpGanado;
    document.getElementById('statXP').innerText = timerState.xpTotal;
    ...
}
```

---

### 8. `ObtenerMisionesCompletadasHoyAsync` viola separación de responsabilidades

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs` — líneas 167-176
**Impacto:** Bajo — Código misplaced, acoplamiento innecesario

**Problema:**
Método que consulta la tabla `Misiones` dentro del servicio Pomodoro. Debería estar en `IServicioMisiones`.

**Sugerencia de corrección:**
Inyectar `IServicioMisiones` en `ServicioPomodoro` (o en `PomodoroController`) y delegar la consulta.

---

### 9. Tips aleatorios: 2 consultas SQL en vez de 1

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs` — líneas 146-150
**Impacto:** Bajo — Rendimiento subóptimo para tablas grandes

**Código actual:**
```csharp
var count = await _context.TipsPomodoro.CountAsync(t => t.EstaActivo);
if (count == 0) return string.Empty;
var skip = Random.Shared.Next(count);
var tip = await _context.TipsPomodoro.Where(t => t.EstaActivo).Skip(skip).Select(t => t.Tip).FirstOrDefaultAsync();
```

**Sugerencia de corrección:**
Usar una sola consulta con `OrderBy` + `FirstOrDefault` (SQL `NEWID()` o `RANDOM()`):
```csharp
var tip = await _context.TipsPomodoro
    .Where(t => t.EstaActivo)
    .OrderBy(t => EF.Functions.Random())
    .Select(t => t.Tip)
    .FirstOrDefaultAsync();
```

---

### 10. Sin validación de `ciclosCompletados` en API

**Archivo:** `Controllers/Api/ApiPomodoroController.cs` — línea 45
**Impacto:** Medio — Cliente puede enviar valores negativos o arbitrarios

**Problema:**
`CicloCompletadoRequest` no tiene `[Range]` ni validación.

```csharp
public class CicloCompletadoRequest { public int CiclosCompletados { get; set; } }
```

**Sugerencia de corrección:**
```csharp
public class CicloCompletadoRequest
{
    [Range(1, 100, ErrorMessage = "CiclosCompletados debe ser entre 1 y 100.")]
    public int CiclosCompletados { get; set; }
}
```

---

### 11. `_context.Update()` redundante

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs` — líneas 66, 86, 100
**Impacto:** Bajo — Rendimiento marginal

**Problema:**
Las entidades recuperadas con `FirstOrDefaultAsync` ya son trackeadas por el `DbContext`. Llamar a `.Update()` fuerza un `UPDATE` de todas las columnas innecesariamente. EF Core detecta cambios automáticamente.

**Sugerencia de corrección:**
Eliminar las líneas `_context.SesionesPomodoro.Update(sesion);` — no son necesarias.

---

## 🟢 MEDIOS/BAJOS (8)

### 12. Clases `IniciarRequest` y `CicloCompletadoRequest` anidadas en el controller

**Archivo:** `Controllers/Api/ApiPomodoroController.cs` — líneas 23, 45
**Impacto:** Muy bajo

Impiden reutilización. Convención ASP.NET: moverlas a `Models/Request/`.

---

### 13. `PomodoroController.Index()` sin `[HttpGet]` explícito

**Archivo:** `Controllers/PomodoroController.cs` — línea 19
**Impacto:** Muy bajo

Funciona por convención de nombres, pero la ausencia del atributo es ambigua. Agregar `[HttpGet]`.

---

### 14. Tests sin verificar `SumarXP` en `FinalizarSesion`

**Archivo:** `EpycusApp.Tests/.../ServicioPomodoroTests.cs` — líneas 103-115
**Impacto:** Bajo

No hay asserts sobre `_gamificacionMock.Verify(g => g.SumarXP(...))` en los tests de `FinalizarSesion`.

---

### 15. Emoji `🍅` en markup

**Archivo:** `Views/Pomodoro/Index.cshtml` — línea 34
**Impacto:** Muy bajo

Inofensivo pero poco idiomático para una aplicación profesional. Reemplazar con icono de Bootstrap.

---

### 16. `null!` en navegaciones

**Archivo:** `Models/Entidades/ConfiguracionPomodoro.cs` — línea 13
**Archivo:** `Models/Entidades/SesionPomodoro.cs` — líneas 14-16
**Impacto:** Muy bajo

Silencia advertencias nullable; si se accede sin `Include()` lanza NullReferenceException en runtime.

---

### 17. `<select>` de notificaciones no vinculado a lógica

**Archivo:** `Views/Pomodoro/Index.cshtml` — líneas 79-82
**Impacto:** Muy bajo

El selector `#configNotificaciones` nunca se lee en JS; es UI muerta. Eliminarlo o implementar su funcionalidad.

---

### 18. Tests sin verificar llamado real a `SumarXP` en `RegistrarCiclo`

**Archivo:** `EpycusApp.Tests/.../ServicioPomodoroTests.cs`
**Impacto:** Bajo

El mock de `_servicioGamificacion.SumarXP` está configurado pero no hay `Verify` del llamado real con los parámetros correctos.

---

### 19. `agregarHistorial` usa `innerHTML` con datos interpolados

**Archivo:** `Views/Pomodoro/Index.cshtml` — líneas 453-461
**Impacto:** Bajo

Aunque los datos son actualmente controlados, es una práctica insegura que podría derivar en XSS si cambia la fuente de datos.

```javascript
// En vez de innerHTML, usar manipulación segura del DOM:
const item = document.createElement('div');
item.className = "position-relative mb-3";
// ... construir el árbol DOM con createElement en vez de innerHTML
```

---

## Resumen de puntuación

| Categoría | Puntaje | Comentario |
|-----------|---------|------------|
| Estructura | 6/10 | Bien organizado en capas, pero código misplaced (Misiones en Pomodoro) |
| Funcionalidad | 3/10 | Bugs críticos de XP invalidan gamificación; vista Configuración rota |
| Seguridad | 6/10 | Ya no hay IDOR; falta validación de entrada en CicloCompletadoRequest |
| Rendimiento | 6/10 | 2 queries para tips, Update() redundante |
| Tests | 5/10 | Tests básicos pero sin Verify de llamadas clave |
| Calidad código | 5/10 | XP hardcodeado, innerHTML, null!, dead UI |

**Puntuación global: 4.5 / 10**

---

## Prioridad de corrección sugerida

1. 🔴 **Inflación XP** + **doble otorgación** — Arreglar `RegistrarCiclo` para XP incremental y que `FinalizarSesion` no re-otorgue
2. 🔴 **Notificaciones.mostrarExito** — Implementar o reemplazar función
3. 🔴 **Vista Configuración** — Agregar actions en controller o eliminar la vista
4. 🔴 **Ciclo de vida sesión** — Revisar lógica frontend/backend de sesionId
5. 🟡 **Timezone** — Unificar a UTC
6. 🟡 **XP hardcodeado** — Usar valor de API
7. 🟡 **Validación ciclosCompletados** — Agregar `[Range]`
8. 🟡 **SRP Misiones** — Delegar a `IServicioMisiones`
9. 🟡 **Tips 2 queries** — Unificar en una sola query
10. 🟡 **Update() redundante** — Eliminar líneas innecesarias
11. 🟢 **Cosas menores** — Tests, null!, innerHTML, emoji, etc.

---

*Documento generado para que una IA asistente corrija todos los problemas listados.*
