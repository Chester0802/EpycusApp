# Auditoría del Módulo Pomodoro

**Fecha:** 2026-06-21  
**Proyecto:** EpycusApp  
**Total archivos analizados:** 23  

---

## Índice

1. [Arquitectura del Módulo](#1-arquitectura-del-módulo)
2. [Flujo Completo del Temporizador](#2-flujo-completo-del-temporizador)
3. [Bugs Críticos](#3-bugs-críticos)
4. [Bugs de Lógica](#4-bugs-de-lógica)
5. [Problemas UX/UI](#5-problemas-uxui)
6. [Nombres y Consistencia](#6-nombres-y-consistencia)
7. [Validaciones Faltantes](#7-validaciones-faltantes)
8. [Seguridad](#8-seguridad)
9. [Tests Faltantes](#9-tests-faltantes)
10. [Nuevas Funcionalidades Sugeridas](#10-nuevas-funcionalidades-sugeridas)
11. [Resumen de Métricas](#11-resumen-de-métricas)

---

## 1. Arquitectura del Módulo

### 1.1 Capa de Presentación (MVC)

| Archivo | Propósito |
|---------|-----------|
| `Controllers/PomodoroController.cs` | Controlador MVC para vistas Index y Configuracion |
| `Controllers/Api/ApiPomodoroController.cs` | API RESTful para operaciones del temporizador |
| `Views/Pomodoro/Index.cshtml` | Vista principal: temporizador, estadísticas, tareas, historial |
| `Views/Pomodoro/Configuracion.cshtml` | Vista de configuración personalizada del Pomodoro |
| `ViewModels/PomodoroIndexViewModel.cs` | ViewModel del Index (clases: `EstadisticasPomodoroHoy`, `TareaPomodoro`, `EstadisticasPomodoroPeriodo`) |
| `wwwroot/css/site.css` (líneas 967–1148) | Estilos CSS específicos del Pomodoro (timer ring, tabs, controles, tareas) |

### 1.2 Capa de Servicios / Lógica de Negocio

| Archivo | Propósito |
|---------|-----------|
| `Servicios/Interfaces/IServicioPomodoro.cs` | Contrato del servicio — 12 métodos |
| `Servicios/Implementaciones/ServicioPomodoro.cs` | Implementación completa (264 líneas) |
| `Ayudantes/ConstantesGamificacion.cs` | Constantes de XP (`XP_BASE_POMODORO = 15`) |

### 1.3 Modelos / Entidades

| Archivo | Propósito |
|---------|-----------|
| `Models/Entidades/ConfiguracionPomodoro.cs` | 15 propiedades de configuración por usuario |
| `Models/Entidades/SesionPomodoro.cs` | Sesión con FK a Usuario, Hábito y Misión |
| `Models/Entidades/TipPomodoro.cs` | Tips de productividad |

### 1.4 DTOs

| Archivo | Propósito |
|---------|-----------|
| `DTOs/ActualizarConfiguracionPomodoroDto.cs` | DataAnnotations (Range) para validar config |
| `DTOs/IniciarRequest.cs` | HabitoId y MisionId opcionales |
| `DTOs/CicloCompletadoRequest.cs` | `[Range(1,100)]` para CiclosCompletados |
| `Ayudantes/RespuestaApi.cs` | Helper genérico `RespuestaApi<T>` |

### 1.5 Infraestructura

| Archivo | Propósito |
|---------|-----------|
| `Datos/Semilla/DatosSemilla.cs` (líneas 206–215) | Seed de 5 TipPomodoro |
| `Migrations/20260615234414_Initial.cs` | Tablas ConfiguracionesPomodoro, SesionesPomodoro, TipsPomodoro |
| `Migrations/20260621163455_AddPomodoroConfigExtras.cs` | +9 columnas extra a ConfiguracionesPomodoro |

### 1.6 Tests

| Archivo | Propósito |
|---------|-----------|
| `EpycusApp.Tests/Unitarios/Servicios/ServicioPomodoroTests.cs` | 11 tests unitarios (230 líneas) |

### 1.7 Archivos Relacionados

| Archivo | Propósito |
|---------|-----------|
| `Controllers/BaseController.cs` | `ObtenerUsuarioId()` vía `ClaimTypes.NameIdentifier` |
| `Controllers/Api/BaseApiController.cs` | `ObtenerUsuarioId()` nullable para API |

### 1.8 Integraciones con otros servicios

- **IServicioGamificacion** — Sumar XP al completar ciclos
- **IServicioBienestar** — Recomendación de pausa activa según ciclos
- **IServicioHabitos** (potencial) — Registrar progreso en hábito vinculado (NO IMPLEMENTADO)
- **IServicioMisiones** (potencial) — Registrar progreso en misión vinculada (NO IMPLEMENTADO)

---

## 2. Flujo Completo del Temporizador

### 2.1 Estados del Temporizador (Frontend JS)

```
timerState.mode:
  ├─ "enfoque"       (default, 25 min por defecto)
  ├─ "corto"         (descanso corto, 5 min)
  ├─ "largo"         (descanso largo, 15 min)
  └─ "personalizado" (configurable, 25 min por defecto)

timerState.props:
  mode, timeLeft, totalTime, isRunning, intervalId, sesionId,
  tareaSeleccionadaId, tareaSeleccionadaTipo, ciclosCompletados,
  minutosTotales, xpTotal, ciclosObjetivo, enPausaActiva
```

### 2.2 Diagrama de Transiciones

```
[START]
   │
   ▼
setMode('enfoque')
   │
   │ (click Play / Space)
   ▼
startTimer()
   │
   │-- Si es 'enfoque' y no hay sesionId:
   │      POST /api/pomodoro/iniciar  (opcional: habitoId o misionId)
   │
   ▼
setInterval (cada 1s): timeLeft--
   │
   │-- timeLeft > 0: updateTimerUI()
   │
   │-- timeLeft <= 0: onTimerComplete()
         │
         ├── pausaTimer()
         ├── reproducirSonido('completado')
         ├── notificar()
         │
         ├── SI mode es 'enfoque' o 'personalizado':
         │      ciclosCompletados++
         │      minutosTotales += duracion
         │      Actualiza UI stats
         │      Actualiza barra de meta diaria
         │      agregarHistorial("Enfoque")
         │      POST /api/pomodoro/{sesionId}/ciclo-completado
         │           ├── SI sugerirDescanso (largo):
         │           │      alert("Descanso Largo")
         │           │      setMode('largo')
         │           │      autoIniciarEnfoque? toggleTimer()
         │           │      RETURN
         │           └── SI no:
         │                  setMode('corto')
         │                  autoIniciarDescanso? toggleTimer()
         │
         └── SI mode es 'corto' o 'largo':
                agregarHistorial("Descanso corto/largo")
                setMode('enfoque')
                autoIniciarEnfoque? toggleTimer()

Transiciones manuales:
  pauseTimer():      isRunning=false, clearInterval
  stopTimer():       isRunning=false + POST cancelar (si hay sesionId)
  resetTimer():      pauseTimer + setMode(actual)
  saltarCiclo():     confirmar, timeLeft=0, pauseTimer,
                     POST cancelar, setMode('corto')
  setMode(nuevo):    si running: confirmar + stopTimer,
                     cambia mode, resetea timeLeft
```

### 2.3 Flujo del Backend (API)

```
POST /api/pomodoro/iniciar
  1. Verifica autenticación
  2. Busca sesión activa (sin FechaFin) → si existe: Conflict
  3. Crea SesionPomodoro (FechaInicio=UtcNow, CiclosCompletados=0, XpOtorgado=0)
  4. Return { sesionId, fechaInicio }

POST /api/pomodoro/{sesionId}/ciclo-completado
  1. Verifica sesión existe y pertenece al usuario
  2. Si ciclosCompletados <= sesion.CiclosCompletados → return (0, false, null)
  3. Actualiza CiclosCompletados
  4. XP_BASE_POMODORO (15) fijo por ciclo
  5. Lee config: si es múltiplo de CiclosAntesDescansoLargo → sugerir=true
  6. Llama a ServicioBienestar.RecomendacionPausaActiva(ciclosCompletados)
  7. SaveChanges + SumarXP al usuario
  8. Return { xpGanado, sugerirDescanso, pausaActiva }

POST /api/pomodoro/{sesionId}/finalizar
  1. Actualiza ciclosCompletados, FechaFin=UtcNow, FueCompletada=true
  2. Return { xpTotal, sesionGuardada }

POST /api/pomodoro/{sesionId}/cancelar
  1. FechaFin=UtcNow, FueCompletada=false
  2. Return { success=true }
```

### 2.4 Seed Data (TipsPomodoro)

| Tip |
|-----|
| "Una tarea a la vez mejora tu concentración." |
| "Toma agua entre ciclos para mantenerte hidratado." |
| "Ajusta tu postura antes de iniciar un nuevo ciclo." |
| "Revisa tu progreso al final del día." |
| "El descanso es parte del trabajo." |

---

## 3. Bugs Críticos

### C-1: Duración incorrecta en historial
- **Archivo:** `Views/Pomodoro/Index.cshtml:249`
- **Código actual:** `(sesion.FechaFin.Value - sesion.FechaInicio).Minutes`
- **Problema:** `.Minutes` obtiene solo la porción de minutos (no total). Si la sesión duró 1h05, devuelve 5 en vez de 65.
- **Solución:** Usar `(int)(sesion.FechaFin.Value - sesion.FechaInicio).TotalMinutes`

### C-2: Auto-iniciar con variable equivocada
- **Archivo:** `Index.cshtml` JS ~ línea 617
- **Código actual:** `if (CONFIG.autoIniciarEnfoque) ...` al sugerir descanso largo
- **Problema:** Debería usar `CONFIG.autoIniciarDescanso` porque lo que se auto-inicia es el descanso largo.
- **Solución:** Cambiar `CONFIG.autoIniciarEnfoque` → `CONFIG.autoIniciarDescanso`

### C-3: Pausa activa del backend ignorada en frontend
- **Archivos:** `ServicioPomodoro.cs` :67-73, `Index.cshtml` JS
- **Problema:** El backend devuelve `pausaActiva` con descripciones (ej. "Estira los dedos...") pero el frontend nunca procesa ese valor. `timerState.enPausaActiva` se declara pero jamás se usa.
- **Solución:** Integrar `data.datos.pausaActiva` en `onTimerComplete()` mostrando modal con la recomendación.

### C-4: Sin validación `ModelState.IsValid` en API
- **Archivo:** `ApiPomodoroController.cs` (múltiples endpoints)
- **Problema:** Los endpoints no verifican `ModelState.IsValid`. Los DTOs tienen `[Required]` y `[Range]` pero nunca se validan.
- **Solución:** Agregar `if (!ModelState.IsValid) return BadRequest(ModelState)` en todos los endpoints.

### C-5: Ciclos negativos aceptados en backend
- **Archivo:** `ServicioPomodoro.cs` `RegistrarCiclo()` ~ línea 50
- **Código actual:** `if (ciclosCompletados <= sesion.CiclosCompletados)`
- **Problema:** Si se envía `-1`, `-1 <= 0` es true, pasa la validación. El DTO valida `[Range(1,100)]` pero nunca se evalúa (ver C-4).
- **Solución:** Cambiar a `if (ciclosCompletados <= 0 || ciclosCompletados <= sesion.CiclosCompletados)`

### C-6: Estado perdido al recargar la página
- **Archivo:** `Index.cshtml` JS
- **Problema:** `timerState` es un objeto en memoria volátil. Al recargar se pierde el progreso. La sesión en backend sigue activa (sin FechaFin), pero el frontend no sabe que existe. El usuario puede iniciar otra sesión y el backend responde Conflict.
- **Solución:** Crear endpoint `GET /api/pomodoro/sesion-activa` y restaurar el estado al cargar la página.

### C-7: Errores de fetch silenciados
- **Archivo:** `Index.cshtml` JS (múltiples `.catch()`)
- **Problema:** Todos los `.catch()` tragan errores sin feedback al usuario. El usuario no sabe si su ciclo se registró o no.
- **Solución:** Implementar sistema de toasts global y reemplazar `.catch(function() {})` por notificaciones visibles.

---

## 4. Bugs de Lógica

| ID | Problema | Archivo | Solución |
|----|----------|---------|----------|
| L1 | `MinutosEnfocados` calculado como `ciclos * tiempoConfig` en vez de sumar duraciones reales | `Controllers/PomodoroController.cs:42` | Calcular desde sesiones reales (suma `(FechaFin-FechaInicio).TotalMinutes`) |
| L2 | Validación de sesión activa en Controller en vez de Service | `ApiPomodoroController.cs:32-37` | Mover lógica a `ServicioPomodoro.IniciarSesion()` |
| L3 | 7 queries separadas para estadísticas semanales | `Controllers/PomodoroController.cs:48-56` | Crear método único con GROUP BY por fecha en el servicio |
| L4 | Historial muestra sesiones canceladas como "Descanso" | `Views/Pomodoro/Index.cshtml:243-271` | Filtrar `FueCompletada = true` o `CiclosCompletados > 0` |
| L5 | No se registra progreso en hábito/misión vinculado al completar ciclo | `ServicioPomodoro.cs` | En `RegistrarCiclo()` si `HabitoId` existe, llamar a `IServicioHabitos`; igual para `MisionId` |
| L6 | Sesión activa duplicada: verificación en Controller no en Service | `ApiPomodoroController.cs` | Centralizar en `ServicioPomodoro.IniciarSesionAsync()` |
| L7 | `ObtenerTareasEnfoqueAsync` no verifica duplicados hábito/misión | `ServicioPomodoro.cs:234-262` | Agregar deduplicación por ID |

---

## 5. Problemas UX/UI

| ID | Problema | Detalle | Solución |
|----|----------|---------|----------|
| UX1 | `alert()` nativo para sugerencia de descanso largo | El navegador muestra diálogo nativo disruptivo | Reemplazar por modal Bootstrap con botón "Iniciar descanso largo" y "Recordar más tarde" |
| UX2 | Tips aleatorios existen en backend pero nunca se muestran | Endpoint `api/pomodoro/tip-aleatorio` implementado, frontend nunca lo llama | En `DOMContentLoaded`, fetch al endpoint y mostrar en área de consejos o toast |
| UX3 | Atajos de teclado no visibles | Espacio (Play/Pause), R (Reset), 1-4 (modos), F (Fullscreen), S (Skip) | Agregar icono `?` con modal/tooltip de atajos |
| UX4 | Sin feedback visual de errores | Errores de red se tragan sin aviso | Sistema de toasts global reutilizable |
| UX5 | Icono de pantalla completa no cambia según estado | Siempre muestra `bi-arrows-fullscreen` | Usar `document.fullscreenElement` para alternar icono |
| UX6 | **"Ciclos objetivo"** — nombre poco claro | El usuario no entiende qué significa | Renombrar a **"Meta diaria de ciclos"** y añadir tooltip: *"Nº de Pomodoros (enfoque) que planeas completar hoy"* |
| UX7 | **"Historial de hoy"** limitado | Solo muestra sesiones del día actual | Agregar toggle **día/semana** con vista semanal detallada y gráfico de barras |
| UX8 | Spinner de carga nunca se oculta si hay error JS | `#ep-spinner` se oculta en `DOMContentLoaded`, si hay error JS antes, queda visible | Agregar timeout de seguridad para ocultar spinner |
| UX9 | Slider de volumen en Index no persiste cambios | Cambiar volumen en Index se pierde al recargar | Persistir en `localStorage` o actualizar configuración en backend vía API |
| UX10 | Sin estado visual de "Pausa Activa" | Backend recomienda pausa activa, frontend la ignora | Agregar capa semitransparente con recomendación y contador regresivo |
| UX11 | Sin indicador de modo "No molestar" | No hay indicación visual cuando el timer está activo | Cambiar subtlemente el tema/color de fondo durante el enfoque |

### 5.1 Detalle de mejoras UX/UI propuestas

#### Meta diaria de ciclos (antes "Ciclos objetivo")
```
Antes:  "🎯 Ciclos objetivo: [4]"
Después: "🎯 Meta diaria: [4] ciclos"
         └── tooltip: "Número de Pomodoros (sesiones de enfoque)
                       que planeas completar hoy. ¡Cumple tu meta
                       y mantén la racha!"
```

#### Historial de hoy → Historial detallado (día/semana)
```
[📅 Hoy] [📊 Semana]  ← toggle

VISTA DÍA:
  ├── 09:00 - 09:25  Enfoque  ⚡+15 XP
  ├── 09:25 - 09:30  Descanso corto
  ├── 09:30 - 09:55  Enfoque  ⚡+15 XP
  └── 10:00 - 10:15  Descanso largo

VISTA SEMANA:
  ┌──────┬──────┬──────┬──────┬──────┬──────┬──────┐
  │ LUN  │ MAR  │ MIE  │ JUE  │ VIE  │ SAB  │ DOM  │
  │ ▓▓▓▓ │ ▓▓▓▓ │ ▓▓▓  │ ▓▓▓▓ │ ▓    │      │      │
  │  4   │  5   │  3   │  6   │  1   │  0   │  0   │
  │ cicl │ cicl │ cicl │ cicl │ cicl │      │      │
  └──────┴──────┴──────┴──────┴──────┴──────┴──────┘
  Tooltip por día: "Miércoles: 3 ciclos, 75 min, 45 XP"
```

---

## 6. Nombres y Consistencia

| ID | Problema | Ubicación | Solución |
|----|----------|-----------|----------|
| N1 | Mezcla español/inglés en JS | `Index.cshtml` JS | Unificar a español: `iniciar` → `startTimer`, `pausar` → `pauseTimer`, `estaCorriendo` → `isRunning`, `tiempoRestante` → `timeLeft` |
| N2 | `XpGanado` vs `XP` inconsistente | `ViewModels`, `ConstantesGamificacion` | Usar `Xp` consistentemente: `XpBasePomodoro`, `XpGanado` |
| N3 | `SonidoSeleccionado` sin validación de valores permitidos | `DTOs/ActualizarConfiguracionPomodoroDto.cs` | Agregar `[RegularExpression]` o validación custom para: "campana", "digital", "naturaleza", "silencio" |
| N4 | `ConfiguracionPomodoro.cs` tiene propiedad `TiempoEstudioMin` con abreviatura inconsistente | Entidad | O bien `TiempoEstudioEnMinutos` o mantener `Min` pero consistente en todo el módulo |
| N5 | `ciclosObjetivo` no se persiste ni se usa realmente | JS frontend | Decidir si se elimina o se implementa completamente con persistencia |
| N6 | `enPausaActiva` declarado pero nunca usado | JS frontend | Implementar o eliminar |
| N7 | Métodos API mezclan snake_case y camelCase | Rutas API | Unificar a kebab-case (actualmente es consistente con `ciclo-completado`, bien) |

---

## 7. Validaciones Faltantes

| ID | Problema | Archivo | Solución |
|----|----------|---------|----------|
| V1 | Sin validación `TiempoDescanso < TiempoEstudio` | `ServicioPomodoro.ActualizarConfiguracion` | Agregar regla de negocio: descanso no debe ser mayor que estudio |
| V2 | Sin validación `CiclosAntesDescansoLargo > 0` con meta diaria | `ServicioPomodoro.ActualizarConfiguracion` | Validar que no sea 0 si `MetaDiariaCiclos > 0` |
| V3 | Sin validación `SonidoSeleccionado` contra valores permitidos | `DTOs/ActualizarConfiguracionPomodoroDto.cs` | Lista blanca: "campana", "digital", "naturaleza", "silencio" |
| V4 | Sin validación de `IniciarRequest.HabitoId` existente | `ApiPomodoroController.cs` | Verificar que el hábito pertenece al usuario |
| V5 | Sin validación de `IniciarRequest.MisionId` existente | `ApiPomodoroController.cs` | Verificar que la misión pertenece al usuario |
| V6 | Sin validación de tiempo mínimo/máximo por ciclo | `DTOs/ActualizarConfiguracionPomodoroDto.cs` | Ej: TiempoEstudioMin entre 1 y 120 minutos |
| V7 | Sin validación de sesión pertenece al usuario en `ciclo-completado` | `ApiPomodoroController.cs` | Actualmente se verifica en el servicio, debería estar también en controller |

---

## 8. Seguridad

| ID | Problema | Detalle | Solución |
|----|----------|---------|----------|
| S1 | Rate limiting solo "Mobile" cubre Pomodoro | Los endpoints Pomodoro usan `[EnableRateLimiting("Mobile")]` | Verificar que el rate limit es adecuado (400/min puede ser mucho). Considerar policy específica "Pomodoro" con 60/min |
| S2 | Sin validación de ownership en algunos endpoints | El servicio verifica que la sesión pertenece al usuario, pero la verificación está en service no en controller | Doble validación: controller + service |
| S3 | `requestFullscreen()` con catch vacío | Si el navegador bloquea fullscreen, el error se traga silenciosamente | Agregar feedback al usuario si falla |

---

## 9. Tests Faltantes

| ID | Test a implementar | Tipo |
|----|-------------------|------|
| T1 | `ObtenerRachaActualAsync` — racha 0 sin sesiones | Unitario |
| T2 | `ObtenerRachaActualAsync` — racha 1 con sesión hoy | Unitario |
| T3 | `ObtenerRachaActualAsync` — racha 3 con 3 días consecutivos | Unitario |
| T4 | `ObtenerRachaActualAsync` — racha 0 con gap de 2 días | Unitario |
| T5 | `ObtenerEstadisticasPeriodoAsync` — cálculo de ciclos, minutos y XP | Unitario |
| T6 | `ObtenerTareasEnfoqueAsync` — con hábitos activos + ConPomodoro=true | Unitario |
| T7 | `ObtenerTareasEnfoqueAsync` — con misiones activas + ConPomodoro=true | Unitario |
| T8 | Validación DTO `ActualizarConfiguracionPomodoroDto` — valores válidos e inválidos | Unitario |
| T9 | Validación DTO `CicloCompletadoRequest` — rango 1-100 | Unitario |
| T10 | Tests de integración para `POST /api/pomodoro/iniciar` | Integración |
| T11 | Tests de integración para `POST /api/pomodoro/{id}/ciclo-completado` | Integración |
| T12 | Tests de integración para `POST /api/pomodoro/{id}/finalizar` | Integración |
| T13 | Tests de integración para `POST /api/pomodoro/{id}/cancelar` | Integración |
| T14 | Tests de integración para `GET/PUT /api/pomodoro/configuracion` | Integración |
| T15 | Tests de integración para `GET /api/pomodoro/tip-aleatorio` | Integración |

---

## 10. Nuevas Funcionalidades Sugeridas

### 10.1 Funcionalidades Core (Prioridad Alta)

#### NF-1: Persistencia del temporizador (State Management)
- **Problema:** El estado se pierde al recargar la página
- **Solución:** Usar `localStorage` para persistir `timerState` entre recargas + endpoint `GET /api/pomodoro/sesion-activa`
- **Beneficio:** El usuario no pierde progreso si recarga accidentalmente

#### NF-2: Bonus XP por completar sesión completa
- **Problema:** Solo se gana XP por ciclo (15 XP), no hay bonus por terminar la sesión
- **Solución:** Al `FinalizarSesion`, otorgar XP bonus: `5 XP por ciclo completado + 10 XP bonus finalización`
- **Beneficio:** Incentiva a completar sesiones en vez de solo hacer ciclos sueltos

#### NF-3: Racha específica de Pomodoro
- **Problema:** La racha actual cuenta días con cualquier sesión, no diferencia Pomodoro
- **Solución:** Racha específica de Pomodoro (días consecutivos con ≥1 ciclo completado)
- **Beneficio:** Gamificación más rica y específica

#### NF-4: Logros de Pomodoro conectados
- **Problema:** El seed data incluye logros como "Maestro del Foco" (50 sesiones) pero nunca se otorgan
- **Solución:** Conectar con `IServicioGamificacion.OtorgarLogro` al cumplir condiciones
- **Logros disponibles:**
  - "Primer Pomodoro" — 1 sesión completada
  - "Enfoque Total" — 10 sesiones
  - "Maestro del Foco" — 50 sesiones
  - "Maratón de Productividad" — 100 sesiones

### 10.2 Gamificación y Motivación (Prioridad Media)

| ID | Funcionalidad | Descripción |
|----|---------------|-------------|
| NF-5 | Niveles de Pomodoro | Desbloquear funcionalidades (temas, sonidos, modos) al alcanzar cierto nº de ciclos totales |
| NF-6 | Estadísticas avanzadas | Promedio ciclos/día, tiempo total enfocado (mes/año), heatmap por hora del día |
| NF-7 | Meta semanal de ciclos | Además de la meta diaria, una meta semanal con bonus de XP al cumplirla |
| NF-8 | Ranking personal vs. histórico | "Tu mejor racha: 12 días" vs "Racha actual: 5 días" |

### 10.3 UX y Configuración (Prioridad Media-Baja)

| ID | Funcionalidad | Descripción |
|----|---------------|-------------|
| NF-9 | Múltiples perfiles Pomodoro | "Estudio" (25/5/15), "Trabajo Profundo" (50/10/20), "Creativo" (15/5/10), "Personalizado" |
| NF-10 | Modo oscuro automático | Al activar el temporizador, cambiar a tema oscuro de concentración (opcional en configuración) |
| NF-11 | Sonidos personalizados | Permitir subir MP3 personalizados para notificación de fin de ciclo |
| NF-12 | Modo "Flow" | Ocultar todo excepto timer circular grande + contador de ciclos. Sin distracciones. Acceso vía botón o doble click |
| NF-13 | Modo "No molestar" | Activar automáticamente DND del sistema operativo al iniciar ciclo |
| NF-14 | Exportar datos Pomodoro | CSV/PDF con estadísticas de sesiones (para informes de productividad) |

### 10.4 Bienestar y Salud (Prioridad Media)

| ID | Funcionalidad | Descripción |
|----|---------------|-------------|
| NF-15 | Ejercicios de respiración | En pausas activas, guiar con animación de respiración (técnica 4-7-8) |
| NF-16 | Recordatorio de hidratación | Después de 2 ciclos, notificar "Bebe agua" |
| NF-17 | Postura correcta | Cada 30 min, recordatorio de ajustar postura |
| NF-18 | Análisis productividad-emocional | Relacionar estado de ánimo (del Diario de Ánimo) con productividad Pomodoro del día. Mostrar correlación en gráfico |
| NF-19 | Pausa activa mejorada | Modal con animación, ejercicios guiados, conteo regresivo y botón "Saltar" visible |

### 10.5 Técnicas / Infraestructura (Prioridad Baja)

| ID | Funcionalidad | Descripción |
|----|---------------|-------------|
| NF-20 | Sincronización entre pestañas | Usar BroadcastChannel API o SignalR para que el timer se sincronice en múltiples pestañas |
| NF-21 | Pomodoro en segundo plano | Usar `visibilitychange` + `Date.now()` para que el timer no se detenga al cambiar de pestaña (setInterval se throttlea en background) |
| NF-22 | Historial con filtros | Endpoint `GET /historial` con filtros: `desde`, `hasta`, `completada`, `conXp`, `página`, `tamaño` |
| NF-23 | Pomodoro grupal/competitivo | Salas de estudio compartidas con temporizador sincronizado y ranking de ciclos |

---

## 11. Resumen de Métricas

| Categoría | Cantidad |
|-----------|----------|
| Archivos del módulo | 23 |
| Bugs críticos | 7 (C-1 a C-7) |
| Bugs de lógica | 7 (L-1 a L-7) |
| Problemas UX/UI | 11 (UX-1 a UX-11) |
| Problemas de nombres | 7 (N-1 a N-7) |
| Validaciones faltantes | 7 (V-1 a V-7) |
| Problemas de seguridad | 3 (S-1 a S-3) |
| Tests existentes | 11 (solo servicio, sin API, sin validación) |
| Tests faltantes | 15 (T-1 a T-15) |
| Nuevas funcionalidades sugeridas | 23 (NF-1 a NF-23) |

### Prioridad de corrección sugerida

```
FASE 1 — Bugs críticos (corregir ahora)
  ├── C-1 (Duración historial)
  ├── C-2 (Auto-iniciar variable equivocada)
  ├── C-3 (Pausa activa en frontend)
  ├── C-4 (ModelState.IsValid en API)
  ├── C-5 (Ciclos negativos)
  ├── C-6 (Estado al recargar)
  └── C-7 (Errores silenciados)

FASE 2 — Bugs de lógica + UX/UI rápido
  ├── L-1 a L-7 (lógica)
  ├── UX-1 (modal descanso)
  ├── UX-2 (tips aleatorios)
  ├── UX-6 (renombrar ciclos objetivo)
  └── UX-7 (historial semanal)

FASE 3 — Validaciones + Nombres + Tests
  ├── V-1 a V-7 (validaciones)
  ├── N-1 a N-7 (consistencia)
  └── T-1 a T-15 (tests)

FASE 4 — Nuevas funcionalidades (priorizar)
  ├── NF-1 (persistencia timer)
  ├── NF-2 (bonus XP)
  ├── NF-3 (racha Pomodoro)
  ├── NF-4 (logros)
  └── NF-15 (ejercicios respiración)
```

---

## Anexo: Estado actual del frontend JS

El temporizador corre completamente en el cliente con `setInterval` de 1 segundo. Las variables clave del state:

```javascript
// Index.cshtml - Script embebido (inline)
var timerState = {
    mode: 'enfoque',          // enfoque | corto | largo | personalizado
    timeLeft: 1500,           // segundos restantes
    totalTime: 1500,          // duración total en segundos
    isRunning: false,         // estado del timer
    intervalId: null,         // id del setInterval
    sesionId: null,           // id de la sesión activa en backend
    tareaSeleccionadaId: null, // id de hábito/misión vinculado
    tareaSeleccionadaTipo: null, // "habito" | "mision"
    ciclosCompletados: 0,     // contador de ciclos de la sesión actual
    minutosTotales: 0,        // suma de minutos enfocados
    xpTotal: 0,               // XP acumulado en la sesión
    ciclosObjetivo: 4,        // meta diaria (solo frontend)
    enPausaActiva: false       // declarado pero nunca usado
};
```

### Funciones principales del frontend

| Función | Propósito |
|---------|-----------|
| `startTimer()` | Inicia setInterval, llama a POST iniciar si es necesario |
| `pauseTimer()` | Pausa, clearInterval |
| `stopTimer()` | Detiene + POST cancelar |
| `resetTimer()` | Pausa + vuelve al modo actual |
| `onTimerComplete()` | Timer llega a 0, reproduce sonido, notifica, cambia modo |
| `setMode(mode)` | Cambia modo, reset timeLeft |
| `saltarCiclo()` | Skip, POST cancelar, va a descanso corto |
| `toggleFullscreen()` | Fullscreen API |
| `toggleTimer()` | Play/Pause toggle |
| `reproducirSonido(tipo)` | AudioContext API para sonidos |
| `iniciarTicTac()` / `detenerTicTac()` | Sonido de tic-tac durante el enfoque |
| `agregarHistorial(tipo)` | Actualiza el DOM del historial |
| `ajustarCiclos()` | Actualiza la barra de meta diaria |
| `notificar(mensaje)` | Notification API del navegador |
| `cargarDatosIniciales()` | Fetch inicial de configuración y estadísticas |

---

*Auditoría generada el 2026-06-21 basada en el código fuente completo de EpycusApp.*  
*23 archivos analizados del módulo Pomodoro (controladores, servicio, vistas, JS, CSS, DTOs, entidades, tests, seed data)*
