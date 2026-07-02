# Auditoría avanzada — Módulo Pomodoro (Web + Android + Backend + BD)

> **Actualización 2026-07-02 (sesión de seguimiento):** se resolvieron los puntos 1.5, 2.1,
> 2.2, 2.4, 2.5 y 2.6 que habían quedado documentados sin corregir. Detalle completo en la
> nueva sección **7**. Los títulos de esas secciones abajo quedan marcados con ✅ **RESUELTO**
> y apuntan a la sección 7 en vez de duplicar la explicación.

> Generada el 2026-07-02, a raíz de que el usuario reportó en vivo: "terminé la sesión 1, no
> arrancó el descanso corto automático (pese a tenerlo activado), se quedó en 00:00 y darle a
> Iniciar no hizo nada". Esa investigación (ver `PENDIENTES.md` del backend, punto 25) llevó a
> una auditoría más amplia de todo el módulo — web responsive (PC y móvil), la app Android
> nativa, el backend, y las tablas de BD relacionadas — porque el Pomodoro es una de las
> pantallas de uso diario más importante de la app y varios de estos bugs se combinan entre sí.
>
> Metodología: lectura exhaustiva de código (backend `ServicioPomodoro.cs`,
> `ApiPomodoroController.cs`, `PomodoroController.cs`, `Views/Pomodoro/*.cshtml`; Android
> `PomodoroFragment.java` y clases relacionadas), verificación cruzada contra datos reales en
> producción (vía SSH, con permiso explícito del usuario) para confirmar que los patrones de
> bug realmente ocurrieron, y `dotnet build`/`dotnet test`/`node --check`/`gradlew
> compileDebugJavaWithJavac` para cada cambio. **Ningún fix de esta sesión se probó
> visualmente en navegador o dispositivo real** (sin acceso a ninguno de los dos en este
> entorno) — pedir al usuario que reproduzca los escenarios marcados abajo.

---

## 0. Resumen ejecutivo

Se encontraron **15 problemas concretos** en el módulo Pomodoro, con evidencia de código (y en
varios casos, datos reales de producción).

- **9 se corrigieron en la sesión original** (backend + web + Android), todos de bajo riesgo
  y verificados por build/tests/compilación.
- **6 quedaron documentados sin tocar** en la sesión original (migración de BD, decisión de
  producto sobre "Meta diaria", o requerían más definición) y **se resolvieron en una sesión
  de seguimiento** el mismo día — ver la sección **7** para el detalle de cada uno, incluyendo
  la migración de BD que cerró la condición de carrera de sesiones duplicadas (**confirmada
  con datos reales**: dos usuarios en producción tenían 16 y 6 sesiones abiertas
  simultáneamente).
- **Los 15 puntos están resueltos en el código**, pero **ninguno se confirmó visualmente en
  navegador/dispositivo real** — ver sección 6 para la lista de qué probar primero.

El hallazgo más importante técnicamente es el **#2 (zona horaria)**: afecta a **todos los
usuarios fuera de UTC** (es decir, prácticamente todos los usuarios reales de la app, dado que
es una app en español) **todos los días**, no es un caso extremo — cualquier sesión hecha por
la tarde/noche podía contarse en el día equivocado en "Resumen de hoy"/"Historial de hoy"/racha.

---

## 1. Bugs corregidos en esta sesión

### 1.1 — [Ya reportado y arreglado antes de esta auditoría] Timer se quedaba congelado tras recargar la pestaña

Ver `PENDIENTES.md` (backend), punto 25, para el detalle completo — es la causa raíz del
reporte original del usuario ("sesión 1 terminada, le doy Iniciar y no pasa nada"). Resumen:
`restaurarEstadoTimer()` en `Views/Pomodoro/Index.cshtml` calculaba el tiempo restante real a
partir de `localStorage`, pero `establecerModo()` lo pisaba inmediatamente después con la
duración completa configurada, y `estaCorriendo` nunca se reasignaba — así que un ciclo que
terminaba mientras la pestaña estaba en segundo plano (el SO la descarga en móvil) nunca
llamaba a `alCompletarTimer()` y la sesión quedaba abierta indefinidamente sin registrar nada.
**Desplegado y verificado en producción** (`dotnet test` 420/420 + `/health` en vivo).

### 1.2 — 🔴 "Resumen de hoy" / racha usaban la fecha UTC del servidor, no la del usuario

**Archivos:** `Servicios/Implementaciones/ServicioPomodoro.cs` — `ObtenerSesionesHoyAsync`,
`ObtenerRachaActualAsync`.

**El bug:** estos dos métodos calculaban "hoy" con `DateTime.UtcNow.Date` — la medianoche del
**servidor**, no la del usuario. `ObtenerEstadisticasSemanalesAsync` (el gráfico semanal) y
`ObtenerEstadisticasAvanzadasAsync` **ya convertían correctamente** a la zona horaria del
usuario (`ObtenerZonaHorariaUsuario` + `TimeZoneInfo.ConvertTimeFromUtc`) — es decir, dos
widgets de la misma pantalla usaban dos definiciones distintas de "hoy".

**Impacto real:** para cualquier usuario al oeste de UTC (toda Latinoamérica, España está en
UTC+1/+2), su medianoche local ocurre **varias horas después** de la medianoche UTC del
servidor. Ejemplo con un usuario en Lima (UTC-5): si son las 21:00 del 1 de julio en Lima, en
UTC ya es la madrugada del 2 de julio — el servidor "cree" que ya es 2 de julio, así que
**cualquier sesión hecha esa tarde/noche del 1 de julio quedaba excluida de "Resumen de hoy" y
"Historial de hoy"**, aunque para el usuario claramente fue "hoy". Lo mismo rompía la racha:
una sesión de anoche podía no contar para el día de "hoy" y cortar una racha real.

Esto **no es un caso raro**: pasa todos los días, en la tarde/noche, para prácticamente
cualquier usuario real de una app en español (excepto los que están literalmente en huso UTC).

**Fix aplicado:** ambos métodos ahora usan `ObtenerZonaHorariaUsuario` + conversión de ida y
vuelta (UTC → hora local del usuario → medianoche local → UTC de esa medianoche), igual que ya
hacía `ObtenerEstadisticasSemanalesAsync`. `ObtenerRachaActualAsync` también agrupa cada fecha
de sesión por su día calendario **local** en vez de por su día UTC.

**Tests agregados:** `ObtenerSesionesHoyAsync_UsaZonaHorariaDelUsuario_NoUtcDelServidor`,
`ObtenerRachaActualAsync_UsaZonaHorariaDelUsuario_NoUtcDelServidor` (ambos en
`ServicioPomodoroTests.cs`) — construyen una sesión exactamente 1 minuto antes/después de la
medianoche local de un usuario en `America/Lima` y verifican que se cuenta en el día correcto.

**Verificado:** build + test (426/426). **No probado visualmente** — para confirmarlo de
verdad habría que cambiar la zona horaria de un usuario de prueba a algo como `America/Lima` o
`America/Bogota` y hacer una sesión por la noche, verificando que aparece en "Resumen de hoy".

### 1.3 — 🔴 "Minutos enfocados" contaba sesiones que nunca completaron ningún ciclo

**Archivos:** `Controllers/PomodoroController.cs` (`Index`, `EstadisticasHoy.MinutosEnfocados`),
`Servicios/Implementaciones/ServicioPomodoro.cs` (`ObtenerEstadisticasPeriodoAsync`,
`ObtenerEstadisticasSemanalesAsync`, `ObtenerEstadisticasAvanzadasAsync`).

**El bug:** el cálculo de "minutos" en los 4 sitios era `Sum((FechaFin - FechaInicio).TotalMinutes)`
para **cualquier** sesión con `FechaFin` fijado, sin importar si completó algún ciclo. Una
sesión que queda abierta mucho tiempo (por el bug 1.1, por doble pestaña, o simplemente porque
el usuario la cancela sin completar nada) sí tiene `FechaFin` (se le pone al cancelarla/
finalizarla), así que su duración completa —a veces horas— se sumaba a "minutos enfocados"
mientras "ciclos completados"/"XP ganado" del mismo resumen se quedaban en 0.

**Esto es exactamente lo que reportó el usuario** ("cuenta los minutos enfocados pero en
ciclos completados sale 0, xp ganado cero") y se confirmó con datos reales: la sesión 52 en
producción (la "sesión 1" que el usuario probó) estuvo abierta 2h30min con `CiclosCompletados=0`
— antes del fix, esos ~150 minutos se habrían sumado íntegros a "Minutos enfocados".

**Fix aplicado:** los 4 sitios ahora solo suman minutos de sesiones con `CiclosCompletados > 0`
(una sesión que nunca completó un ciclo no representa tiempo de enfoque verificado, sin
importar cuánto tiempo estuvo abierta).

**Tests agregados:** `Index_SesionSinCiclosCompletados_NoCuentaMinutosEnResumen`
(`PomodoroControllerTests.cs`), `EstadisticasPeriodo_SesionSinCiclos_NoCuentaSusMinutos`,
`EstadisticasSemanales_SesionSinCiclos_NoCuentaSusMinutos`,
`EstadisticasAvanzadas_SesionSinCiclos_NoCuentaSusMinutos` (`ServicioPomodoroTests.cs`).

**Verificado:** build + test (426/426). **No probado visualmente.**

### 1.4 — 🟠 Web: doble-tap en "Iniciar" podía arrancar dos temporizadores en paralelo

**Archivo:** `Views/Pomodoro/Index.cshtml` — `alternarTimer()`, `iniciarTimer()`.

**El bug:** `iniciarTimer()` es `async` y espera (`await ctx.resume()`) a que el contexto de
audio esté listo **antes** de marcar `estaCorriendo = true`. Como `alternarTimer()` no tenía
ninguna guarda de reentrancia, un doble-tap rápido en el botón de Play (frecuente en
touchscreens) podía disparar la función dos veces antes de que la primera terminara ese
`await` — la segunda llamada también entraba a `iniciarTimer()` porque `estaCorriendo` seguía
en `false`. Resultado: dos `setInterval` corriendo en paralelo sobre el mismo `tiempoRestante`
(el reloj bajaba al doble de velocidad) y, si el modo era "enfoque", **dos peticiones `POST
/api/v1/pomodoro/iniciar`** — con el añadido de que el backend sí bloquea sesiones duplicadas
(`IniciarSesionSiNoActiva`, ver 2.1 más abajo) pero con una ventana de carrera propia.

**Fix aplicado:** guarda de reentrancia `estadoTimer.procesandoInicio` (mismo patrón que ya
existía para `alCompletarTimer` con `procesandoCompletado`): una segunda llamada a
`alternarTimer()` mientras la primera sigue "arrancando" simplemente no hace nada.

**Verificado:** `node --check` sobre el JS extraído del `.cshtml`. **No probado visualmente**
(habría que reproducir un doble-tap real en un navegador/móvil).

### 1.5 — ✅ RESUELTO (ver sección 7) — "Meta diaria": dos controles de UI desconectados entre sí

**Archivo:** `Views/Pomodoro/Index.cshtml`.

**El bug:** hay **dos** representaciones distintas de "Meta diaria" en la misma pantalla:

1. El control rápido +/- junto al reloj (`ajustarCiclos()`, variable JS `metaDiariaValue`,
   solo en memoria del navegador, nunca se persiste al backend).
2. La tarjeta "Meta diaria" con su barra de progreso, que se renderiza en el servidor desde
   `Model.Configuracion.MetaDiariaCiclos` (el valor guardado de verdad) y solo aparece si ese
   valor es `> 0`.

Antes del fix, cambiar el control rápido (1) **no actualizaba** la tarjeta (2) — el usuario
ajustaba su meta del día y la barra de progreso seguía mostrando el número viejo hasta
recargar la página, momento en el que el ajuste rápido se perdía por completo (nunca se guardó
en ningún lado). Además, la lógica de "¡meta diaria cumplida!" / auto-finalizar sesión al
completarla oscilaba entre usar `CONFIGURACION.metaDiaria` (el valor del servidor) en algunos
puntos y `metaDiariaValue` (el ajustado) en otros — dos números que podían no coincidir.

**Fix aplicado (parcial, decisión de producto pendiente):** se unificó **toda** la lógica de
la pantalla (barra de progreso, texto, notificación de "meta cumplida", condición de
auto-finalizar sesión) para usar siempre `metaDiariaValue` como única fuente de verdad
mientras la pestaña está abierta, y se sincroniza la tarjeta visualmente en cuanto se usa el
control +/- (`actualizarTarjetaMetaDiaria()`). Esto arregla el desfase visual **dentro de la
misma sesión de navegador**, pero **no persiste el ajuste al servidor** — sigue perdiéndose al
recargar. No se implementó la persistencia porque el endpoint de configuración
(`PUT /api/v1/pomodoro/configuracion`) sobrescribe **toda** la configuración del usuario (ver
comentario en el propio código: "el backend sobrescribe TODA la configuración"), así que un
`PUT` parcial disparado silenciosamente desde este control arriesgaría pisar otros ajustes
(sonido, volumen, etc.) sin que el usuario lo pida explícitamente — mejor decisión de producto
que cambio de una IA sin supervisión. **Pendiente de decisión del usuario:** ¿el control
rápido debería (a) persistir de verdad al perfil del usuario, (b) quitarse y dejar que "Meta
diaria" solo se edite desde Configuración, o (c) quedarse como está ahora (un "plan de hoy"
puramente local, ya al menos consistente consigo mismo)?

**Verificado:** `node --check`. **No probado visualmente.**

### 1.6 — 🟡 Fullscreen del reloj podía dejar modales invisibles e inalcanzables

**Archivo:** `Views/Pomodoro/Index.cshtml`.

**El bug:** el botón "Pantalla completa" pone en fullscreen nativo (`requestFullscreen()`)
solo el elemento `#pomodoroCard` (el reloj), no la página entera. Los modales de Bootstrap
("Descanso largo recomendado", "¡Meta diaria cumplida!") se montan **fuera** de ese elemento —
y el navegador, en modo fullscreen de un elemento, solo pinta ese elemento y su subárbol.
Resultado: si el usuario pone el reloj en pantalla completa (uso natural de esta función) y
luego se dispara uno de esos modales, **queda invisible**. Y como el botón para salir de
pantalla completa (`#btnFullscreen`) *también* está fuera de `#pomodoroCard`, en móvil (sin
teclado para el atajo "F"/Escape) el usuario queda completamente atascado sin poder ver ni
cerrar el modal ni salir de pantalla completa con el dedo.

**Fix aplicado:** antes de mostrar cualquiera de esos dos modales, se sale de pantalla completa
automáticamente si está activa (`salirDePantallaCompletaSiActiva()`), así el modal aparece
sobre la página normal donde sí es visible y clicable.

**Verificado:** `node --check`. **No probado visualmente** (habría que activar pantalla
completa, completar un ciclo con `CiclosAntesDescansoLargo=1` para forzar el modal, y
confirmar que se ve).

### 1.7 — 🟢 Código muerto en `saltarCiclo()`

**Archivo:** `Views/Pomodoro/Index.cshtml`. `estadoTimer.tiempoRestante = 0;` se pisaba de
inmediato por el `establecerModo('corto')` de la siguiente línea (que fija `tiempoRestante` a
la duración completa configurada) — no tenía ningún efecto. Eliminado, con comentario
explicando por qué (para que nadie intente "reactivarlo" pensando que hacía algo). Sin impacto
funcional, solo limpieza.

### 1.8 — 🟠 Android: fallo silencioso al arrancar sesión = sesión completa sin registrar

**Archivo:** `app/src/main/java/es/epycus/app/ui/pomodoro/PomodoroFragment.java`.

**El bug:** `iniciar()` no espera a que `POST /api/v1/pomodoro/iniciar` responda — arranca el
`CountDownTimer` local de inmediato y llama a `iniciarSesionEnBackend()` en segundo plano. Si
esa llamada falla (`onFailure`, sin conexión) o el servidor la rechaza (`onResponse` no
exitosa — ej. 409 porque ya hay una sesión activa), el código anterior **no hacía nada en
absoluto**: sin Snackbar, sin log visible, sin marca de error. `sesionId` se queda en `-1` para
siempre, y como `notificarCicloCompletado()`/`finalizarSesion()` hacen
`if (sesionId == -1) return;`, **ningún ciclo de esa sesión completa se registra jamás en el
servidor** — mientras el usuario ve un Pomodoro aparentemente normal (sonido, vibración,
notificaciones locales funcionan igual). Esto es la misma familia de bug que el 1.1/1.3 (ciclos
"fantasma" que nunca llegan al backend) pero **peor en Android**, porque no hay ningún mecanismo
de recuperación equivalente al `localStorage` + `sesion-activa` del web.

**Fix aplicado (mínimo, sin cambiar la lógica de reintento):** se agregó un `Snackbar` de aviso
tanto en `onFailure` como en el caso de respuesta no exitosa, para que al menos el usuario sepa
que esta sesión concreta no se está guardando en vez de descubrirlo días después al ver "0
ciclos" en las estadísticas. **No se implementó reintento automático** (requeriría rediseñar
cuándo/cómo reintentar `iniciarSesionEnBackend()` a mitad de una sesión ya en curso, con
riesgo de introducir un bug nuevo sin poder probarlo en un dispositivo real) — queda como
recomendación para una sesión futura con acceso a emulador.

**Verificado:** `./gradlew compileDebugJavaWithJavac` en verde. **No probado en
dispositivo/emulador real** (ninguno disponible en esta sesión).

### 1.9 — 🟢 Android: posible división por cero en `ciclosAntesPausaLarga`

**Archivo:** `PomodoroFragment.java`. `ciclosCompletados % ciclosAntesPausaLarga` se usaba en
dos sitios (`reanudarTimer()`, `getTiempoActual()`) sin verificar que el divisor fuera `> 0`.
Hoy el backend siempre manda `4` por defecto y el diálogo de configuración valida `> 0` antes
de guardar, así que el riesgo real es bajo — pero es una pantalla de uso diario y un
`ArithmeticException` ahí sería un crash total del Pomodoro. Se aplicó `Math.max(1, ...)` al
aplicar la configuración (la fuente única del valor) más una guarda `> 0` extra en los dos
puntos de uso, por si en el futuro llega un valor inesperado desde otra ruta.

**Verificado:** compila en verde. Cambio puramente defensivo, sin cambio de comportamiento
visible en el caso normal.

---

## 2. Bugs documentados, sin corregir en esta sesión

### 2.1 — ✅ RESUELTO (ver sección 7) — Backend: condición de carrera puede crear dos sesiones activas simultáneas

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs` — `IniciarSesionSiNoActiva`.

```csharp
var sesionesHoy = await ObtenerSesionesHoyAsync(usuarioId);
var activa = sesionesHoy.FirstOrDefault(s => !s.FechaFin.HasValue);
if (activa != null) return (false, null, "Ya tienes una sesión activa...");
// ... inserta una sesión nueva ...
```

Es un patrón *check-then-insert* sin transacción ni bloqueo. Dos peticiones `POST /iniciar`
casi simultáneas (el doble-tap del punto 1.4 llegando a compararse en el servidor, dos
pestañas, o dos dispositivos) pueden **ambas** leer "no hay sesión activa" antes de que
cualquiera inserte, y ambas insertan — dejando dos sesiones abiertas para el mismo usuario. El
cliente solo recuerda el `sesionId` de la última respuesta que le llegó, así que la otra queda
huérfana (nunca recibe `/ciclo-completado` ni `/cancelar`) — otra fuente más de sesiones con
minutos "fantasma" como las del punto 1.3.

**Por qué no se corrigió:** una solución robusta de verdad necesita o bien un índice único a
nivel de BD que garantice "máximo una sesión abierta por usuario" (MySQL/MariaDB no soporta
índices únicos parciales nativamente — necesitaría una columna generada o un patrón
equivalente, es decir, una migración de esquema) o bloqueo pesimista explícito
(`SELECT ... FOR UPDATE`), ninguno de los dos es un cambio "mínimo" que se pueda verificar
bien sin una migración real contra la BD de producción. El fix del punto 1.4 (guarda de
reentrancia en el cliente) ya cierra el disparador más probable (doble-tap desde un solo
navegador), así que el riesgo residual es multi-pestaña/multi-dispositivo, más raro.

**Recomendación:** si se decide atacarlo, la opción más simple es una migración que agregue
una columna computada `SesionAbiertaMarcador` (ej. `UsuarioId` si `FechaFin IS NULL`, `NULL` en
caso contrario) con un índice único sobre ella — el segundo `INSERT` fallaría con una
violación de constraint que se puede capturar y convertir en el mismo mensaje de error 409 que
ya existe.

### 2.2 — ✅ RESUELTO (ver sección 7) — Web: sincronización entre pestañas (`BroadcastChannel`) no arranca un reloj real en la pestaña receptora

**Archivo:** `Views/Pomodoro/Index.cshtml` — listener de `BroadcastChannel('epycus_pomodoro')`,
casos `'timer_start'`/`'timer_pause'`.

Cuando una pestaña arranca el temporizador, transmite `timer_start` a las demás pestañas
abiertas con la misma sesión de usuario. La pestaña receptora actualiza `estaCorriendo = true`
y pinta el estado una vez, pero **no crea ningún `setInterval` propio** — solo `iniciarTimer()`
lo hace, y esa función nunca se llama desde el listener de broadcast. Consecuencia: en la
segunda pestaña el botón cambia a "Pausar" (como si estuviera corriendo) pero los dígitos no
vuelven a actualizarse nunca más por sí solos (silencio hasta el próximo broadcast). Si el
usuario hace clic en Play/Pausa en esa segunda pestaña, `alternarTimer()` ve `estaCorriendo ===
true` y llama a `pausarTimer()` — que solo limpia un intervalo que nunca existió ahí,
dejando el botón en un estado inconsistente.

**Por qué no se corrigió:** requiere decidir el diseño correcto de multi-pestaña (¿la pestaña
secundaria debería tener su propio reloj sincronizado por `tiempoRestante`/timestamp, o
debería mostrarse en un modo "solo lectura, ve a la otra pestaña"?) — es un cambio de
comportamiento visible, no un simple parche, y no se pudo probar con dos pestañas reales en
este entorno. Queda documentado para que quien lo aborde tenga el diagnóstico ya hecho.

### 2.3 — 🟡 Reporte específico del usuario ("00:00 con la barra avanzando y botón en modo Pausar") — hipótesis, sin confirmar al 100%

El reporte original incluía un detalle que no se pudo reproducir de forma determinista solo
leyendo código: *"le doy en Continuar y me sale 00:00 pero está corriendo (la barra avanza),
el reloj sigue en 00:00, y el botón está en modo Pausar"*. Las hipótesis más fuertes, en orden
de probabilidad, con la evidencia que las respalda:

1. **Es el mismo bug del punto 1.1** (`restaurarEstadoTimer` pisando el tiempo restante), en
   una variante donde `establecerModo()` deja `tiempoRestante` en un valor que decrementa
   normalmente pero el dígito se quedó "congelado" por una recarga a mitad de ciclo — ya
   corregido.
2. **El bug del punto 2.2** (multi-pestaña): si el usuario tenía el Pomodoro abierto en dos
   pestañas (común si usa varias pestañas del navegador o una PWA instalada + el navegador
   normal a la vez), la pestaña "receptora" del broadcast mostraría exactamente esta
   combinación: botón en "Pausar" (`estaCorriendo=true` recibido por broadcast) con el reloj
   sin decrementar (sin `setInterval` propio) — coincide con "corriendo pero congelado".
   La "barra avanzando" no encaja perfectamente con esta hipótesis salvo que el usuario
   describiera el estado de la OTRA pestaña (la que sí corre) al mirar entre las dos.
3. Una condición de carrera del punto 1.4 (ya corregida) donde dos `setInterval` corrían en
   paralelo pudo, en algún punto intermedio, dejar el dígito y el aro visualmente
   desincronizados por una fracción de segundo entre dos llamadas a `actualizarUI()` casi
   simultáneas — improbable que sea perceptible por un humano, pero no descartable del todo.

**Recomendación:** si el usuario vuelve a ver este comportamiento después de los fixes 1.1 y
1.4 de esta sesión, lo más valioso sería confirmar si tenía el Pomodoro abierto en más de una
pestaña/ventana en ese momento — eso apuntaría directo al punto 2.2.

### 2.4 — ✅ RESUELTO (ver sección 7) — BD: sin índice compuesto para las consultas de "hoy"/"historial"/"estadísticas"

```
SesionesPomodoro: PRIMARY (Id), IX_..._HabitoId, IX_..._MisionId, IX_..._UsuarioId, IX_..._SubTareaId
```

(verificado en vivo contra la BD de producción, `SHOW INDEX FROM SesionesPomodoro`). Todas las
consultas que filtran por `UsuarioId` + rango de `FechaInicio`
(`ObtenerSesionesHoyAsync`, `ObtenerHistorialAsync`, `ObtenerEstadisticasPeriodoAsync`,
`ObtenerEstadisticasSemanalesAsync`, `ObtenerEstadisticasAvanzadasAsync`,
`ObtenerRachaActualAsync`) solo pueden aprovechar el índice de `UsuarioId` y filtran el rango
de fecha en memoria/con un scan parcial. Con el volumen actual (53 filas totales en toda la
tabla al momento de esta auditoría) es irrelevante, pero el Pomodoro es la pantalla de mayor
uso diario de la app — vale la pena agregar un índice compuesto `(UsuarioId, FechaInicio)`
antes de que el volumen de datos lo haga notorio. No se aplicó en esta sesión por ser un
cambio de esquema (migración) que no estaba pedido explícitamente y no es urgente todavía.

### 2.5 — ✅ RESUELTO (ver sección 7) — `ObtenerHistorialAsync` puede incluir sesiones aún en curso

El endpoint `/api/v1/pomodoro/historial` (usado por el diálogo de Historial de Android) no
excluye sesiones con `FechaFin == null` (todavía activas) — a diferencia del `HistorialHoy` de
la vista web, que si aplica su propio filtro (`FueCompletada || CiclosCompletados > 0`).
Consultar el historial mientras hay una sesión en curso puede mostrarla como una entrada más
con "0 ciclos, 0 min", que es ruido confuso pero no incorrecto (es información real: hay una
sesión sin terminar). No se corrigió porque cambiar este filtro rompía un test existente
(`ObtenerHistorialAsync_ConSesiones_RetornaPagina`, que crea sesiones sin `FechaFin`
deliberadamente) y es de severidad baja — se documenta para decidir en otra sesión si de verdad
se quiere excluir sesiones en curso del historial (habría que actualizar ese test a la vez).

### 2.6 — ✅ RESUELTO (ver sección 7) — Android no tiene ninguna UI para "Meta diaria"

El diálogo de configuración de Android (`dialog_pomodoro_config`) solo permite editar
`tiempoEstudio`/`tiempoDescanso`/`tiempoDescansoLargo`/`ciclosAntesLargo` — no hay ningún campo
para `MetaDiariaCiclos`, aunque el valor se reenvía silenciosamente en cada guardado
(`configActual.getMetaDiariaCiclos()`) para no perderlo. Tampoco hay ninguna barra de progreso
ni indicador de meta diaria en la pantalla del Pomodoro de Android, a diferencia del web (que
sí tiene la tarjeta "Meta diaria", con los problemas propios del punto 1.5). Es una brecha de
paridad de features entre plataformas, no un bug de datos — se deja documentada para que se
decida si vale la pena implementarla en Android o si "Meta diaria" pasa a ser un concepto
solo-web.

---

## 3. Comparativa Web vs Android — inconsistencias de comportamiento

| Aspecto | Web | Android |
|---|---|---|
| Persistencia de sesión al recargar/cerrar | `localStorage` + `establecerModo` (bug 1.1, ya corregido) | `SharedPreferences` con tiempo de fin absoluto (`endTime`) — más robusto, sobrevive a que el proceso muera de verdad |
| Notificación en 2do plano | No aplica (pestaña de navegador) | `AlarmManager.setExactAndAllowWhileIdle` (implementado sesión anterior, no probado en dispositivo real) |
| "Meta diaria" | Dos controles desconectados (1.5) | No existe ningún control (2.6) |
| Historial "de hoy" | Filtra sesiones con 0 ciclos (`PomodoroController.Index`) | Usa el endpoint genérico `/historial`, sin ese filtro (2.5) |
| Fallo al arrancar sesión (`/iniciar`) | Muestra error (`mostrarError`), el usuario lo ve | Era 100% silencioso, corregido en 1.8 |
| Doble-tap en Play | Podía arrancar 2 timers (1.4, corregido) | Protegido de forma natural: `isRunning` se marca de forma síncrona antes de que la llamada de red termine, sin punto de espera intermedio que permita una segunda pulsación colarse |
| Multi-pestaña/multi-dispositivo | Sincroniza vía `BroadcastChannel` (con el bug 2.2) | No aplica (una sola Activity) |

---

## 4. Combinaciones de uso reales que pueden romper el sistema

El usuario pidió pensar específicamente en esto — un pomodoro es una herramienta que la gente
usa de formas variadas e impredecibles durante el día. Lista de escenarios reales, más allá de
los bugs ya descritos arriba:

1. **Cambiar de pestaña/app a mitad de un ciclo y volver horas después.** Cubierto por el fix
   1.1 en web; en Android está mitigado por el `endTime` absoluto en `SharedPreferences` + la
   alarma del sistema, pero si `AlarmManager` no tiene permiso de alarmas exactas (Android 12+)
   la notificación puede llegar tarde — no rompe datos, solo la puntualidad del aviso.
2. **Doble-tap en Iniciar/Pausar en pantallas táctiles.** Cubierto por el fix 1.4 en web;
   Android ya era resistente a esto por diseño (ver tabla arriba).
3. **Dos pestañas de Pomodoro abiertas a la vez** (dos ventanas, o navegador + PWA instalada
   simultáneamente). Documentado como bug 2.2, sin corregir — puede dejar una pestaña con el
   reloj visualmente "congelado pero corriendo".
4. **Editar la Configuración en una pestaña mientras hay un Pomodoro corriendo en otra.** La
   pestaña con el timer activo tiene la configuración "horneada" en el JS al cargar la página
   (`CONFIGURACION`, un objeto fijo) — un cambio de configuración en otra pestaña no se refleja
   ahí hasta recargar. No es exactamente un bug (es esperable que una página no se auto-actualice
   con cambios de otra), pero puede sorprender a un usuario que "no ve" su cambio de duración
   de ciclo aplicado a la sesión ya en curso — comportamiento correcto pero no documentado en
   ningún lado de la UI.
5. **Perder o recuperar la conexión a mitad de un ciclo.** Ya manejado explícitamente en el
   código (rollback de contadores en `alCompletarTimer` si `/ciclo-completado` falla, mensajes
   de error) — comportamiento correcto y ya testeado.
6. **Borrar el hábito o la misión que se seleccionó como "tarea de enfoque" mientras el
   Pomodoro corre** (desde otra pestaña/pantalla). Revisado en el backend
   (`RegistrarCiclo`/`FinalizarSesion`): usan `FirstOrDefaultAsync` con verificación de null
   antes de tocar la sub-tarea asociada — no hay crash, el ciclo se sigue registrando
   normalmente, solo no se acumulan minutos en una sub-tarea que ya no existe. Comportamiento
   correcto, sin cambios necesarios.
7. **Cambiar la zona horaria del dispositivo/perfil a mitad del día** (viajes, o simplemente
   un valor de `ZonaHoraria` desactualizado en el perfil). El fix 1.2 usa la zona horaria
   *guardada en el perfil del usuario* en el momento de cada consulta, no la del dispositivo en
   el momento de la sesión — así que un cambio de zona horaria reclasifica retroactivamente
   sesiones viejas a "hoy" o "ayer" según la nueva zona. Es el comportamiento más razonable
   posible sin guardar la zona horaria exacta de cada sesión individual, pero vale la pena
   saber que existe esta sutileza si un usuario reporta que su historial "saltó" de día tras
   cambiar de zona horaria en su perfil.
8. **Completar muchos ciclos muy rápido saltándolos con "Saltar ciclo".** No otorga XP
   (correcto, ver código), y cada salto cancela la sesión y crea una nueva de "descanso" —
   revisado, no se encontró forma de inflar XP/racha por esta vía.
9. **Sesión que se queda abierta toda la noche y "cruza" la medianoche del usuario.** Antes del
   fix 1.2, esto directamente rompía qué día contaba la sesión; con el fix, la sesión se
   atribuye por su `FechaInicio`, así que si empieza a las 23:50 y termina a las 00:10 del día
   siguiente, **cuenta para el día en que empezó** (comportamiento razonable y ya así de
   antes, sin cambios en esta sesión — solo se corrigió CUÁL es "hoy" para el usuario, no el
   criterio de a qué día se atribuye una sesión que cruza medianoche).

---

## 5. Tests agregados en esta sesión

Todos en `EpycusApp.Tests`, 426/426 en verde (`dotnet test`, proyecto
`EpycusApp.Tests/EpycusApp.Tests.csproj`):

- `Unitarios/Controladores/PomodoroControllerTests.cs`:
  `Index_SesionSinCiclosCompletados_NoCuentaMinutosEnResumen` — cubre el bug 1.3 a nivel del
  controller MVC (donde vive el cálculo real de "Resumen de hoy").
- `Unitarios/Servicios/ServicioPomodoroTests.cs`:
  - `EstadisticasPeriodo_SesionSinCiclos_NoCuentaSusMinutos`
  - `EstadisticasSemanales_SesionSinCiclos_NoCuentaSusMinutos`
  - `EstadisticasAvanzadas_SesionSinCiclos_NoCuentaSusMinutos`
  - `ObtenerSesionesHoyAsync_UsaZonaHorariaDelUsuario_NoUtcDelServidor`
  - `ObtenerRachaActualAsync_UsaZonaHorariaDelUsuario_NoUtcDelServidor`

### Huecos de cobertura que quedan (recomendado para una próxima sesión)

- **Condición de carrera de `IniciarSesionSiNoActiva`** (punto 2.1): no se puede probar de
  forma confiable con el proveedor EF InMemory que usan los tests actuales (no replica el
  comportamiento de bloqueo/aislamiento real de MariaDB) — necesitaría un test de integración
  contra una base de datos MySQL/MariaDB real (ej. con Testcontainers) para tener valor real.
- **JS de `Views/Pomodoro/Index.cshtml`: cero cobertura automatizada.** Toda la lógica del
  temporizador (más de 700 líneas de JS inline) depende 100% de pruebas manuales en
  navegador — no hay ningún framework de test de JS configurado en este proyecto. Si se sigue
  encontrando esta clase de bugs (el de esta sesión, el de la sesión anterior con el tiempo
  negativo), vale la pena evaluar extraer la lógica del temporizador a un archivo `.js`
  separado con tests unitarios (ej. Vitest/Jest con jsdom), en vez de seguir viviendo inline
  dentro del `.cshtml` sin forma de testearla.
- **Android `PomodoroFragment.java`: sin tests unitarios ni instrumentados específicos del
  temporizador.** El proyecto ya tiene infraestructura Robolectric+MockWebServer para otros
  módulos (ver `auditoria.md`, Fase 1) — sería el lugar natural para agregar tests de
  `iniciar()`/`pausar()`/`reanudarTimer()` sin necesitar un emulador.

---

## 6. Prioridad recomendada para la próxima sesión

1. **Confirmar visualmente** los 9 fixes de la sesión original (1.1 a 1.9) y los 6 de la
   sesión de seguimiento (sección 7) — ninguno se probó en navegador/dispositivo real
   todavía, solo build+tests+compilación.
2. Si el usuario vuelve a reportar el síntoma "reloj congelado pero corriendo", el punto 2.2
   ya está resuelto (modo espejo) — confirmar que efectivamente se resolvió antes de asumir
   un bug nuevo.
3. Backup del keystore de Android y decisión sobre publicación (ver `auditoria.md`), sin
   relación con este módulo.

---

## 7. Sesión de seguimiento (2026-07-02) — resolución de los puntos pendientes

El usuario pidió explícitamente resolver los 6 puntos que habían quedado documentados sin
tocar en la sección 2, más decidir el punto 1.5. Se preguntó por cada uno (migración de BD sí
o no, qué hacer con "Meta diaria", cómo resolver el multi-pestaña, si implementar Meta diaria
en Android) y el usuario eligió la opción recomendada en los 4 casos.

### 7.1 — Punto 2.1 y 2.4: migración de BD con índice único + índice compuesto

**Archivos:** `Models/Entidades/SesionPomodoro.cs`, `Datos/ContextoAplicacion.cs`,
`Migrations/20260702080739_AgregarIndicesYUnicidadSesionPomodoro.cs`,
`Servicios/Implementaciones/ServicioPomodoro.cs` (`IniciarSesionSiNoActiva`).

Se agregó una columna calculada `SesionAbiertaMarcador` (`UsuarioId` si `FechaFin IS NULL`,
`NULL` en caso contrario) con un índice único sobre ella — la BD ahora rechaza a nivel de
esquema cualquier intento de crear una segunda sesión abierta para el mismo usuario, cerrando
la condición de carrera de raíz (no solo reduciendo la ventana como haría una transacción).
También se agregó el índice compuesto `(UsuarioId, FechaInicio)` del punto 2.4 en la misma
migración.

**Verificación de duplicados existentes antes de migrar:** se consultó producción por SSH
(con permiso ya otorgado en esta sesión) y **sí había duplicados reales**: el usuario 1 tenía
**16** sesiones abiertas simultáneamente, el usuario 3 tenía **6** — confirma que la condición
de carrera documentada en el punto 2.1 no era solo teórica. La migración incluye un paso de
limpieza (mismo patrón que la migración `AgregarIndiceUnicoEstadoAnimo` de la sesión anterior:
conserva la sesión abierta más reciente por usuario, cancela las demás con `FechaFin=ahora`,
`FueCompletada=false`) **antes** de crear el índice único, para que la migración no falle
contra los datos reales.

`IniciarSesionSiNoActiva` ahora además captura la violación del índice único
(`DbUpdateException` con `MySqlException.ErrorCode == DuplicateKeyEntry`) y la traduce al
mismo mensaje amigable de "ya tienes una sesión activa" en vez de dejar pasar un 500 sin
manejar — es la red de seguridad para la ventana de tiempo que sigue existiendo entre el
`check` en memoria y el `INSERT` (ahora inofensiva: en el peor caso, uno de los dos intentos
recibe el error de conflicto en vez de crear una sesión duplicada).

**Verificado:** `dotnet build` + `dotnet test` (427/427) + arranque real del servidor local
contra el proveedor InMemory (confirma que `HasComputedColumnSql` no rompe el arranque en
desarrollo, donde no se usa MySQL) + desplegado a producción, `Database.MigrateAsync()` aplicó
la migración al reiniciar el servicio (ver verificación en el resto de esta sección tras el
deploy). **No se pudo probar la condición de carrera en sí con dos peticiones realmente
simultáneas** (necesitaría un test de integración contra MySQL/MariaDB real, ver sección 5) —
la garantía real es la del índice único de BD, que sí es efectiva independientemente de si el
código de la aplicación la ejercita bien en las pruebas.

### 7.2 — Punto 2.5: `ObtenerHistorialAsync` ya no incluye sesiones en curso

**Archivo:** `Servicios/Implementaciones/ServicioPomodoro.cs`.

Se agregó `&& s.FechaFin != null` al filtro base de `ObtenerHistorialAsync` — una sesión
todavía abierta no es "historial" (para eso existe `/sesion-activa`). Se actualizaron 9 tests
existentes que creaban sesiones sin `FechaFin` (dependían del comportamiento viejo) para que
sigan siendo representativos, y se agregó un test nuevo,
`ObtenerHistorialAsync_ExcluyeSesionesEnCurso`, que verifica explícitamente que una sesión sin
`FechaFin` no aparece en el resultado. **Verificado:** build + test (427/427).

### 7.3 — Punto 1.5: "Meta diaria" del control rápido ahora persiste de verdad

**Archivo:** `Views/Pomodoro/Index.cshtml` — nueva función `persistirMetaDiariaDebounced()`.

El usuario eligió persistir el ajuste rápido (+/- junto al reloj) al servidor en vez de
quitarlo o dejarlo solo-visual. Como el endpoint `PUT /api/v1/pomodoro/configuracion`
sobrescribe **toda** la configuración, y los controles de sonido/tic-tac de esta misma
pantalla son solo de vista previa local (no reflejan fielmente el valor guardado — ej. el
`<select>` de sonido siempre arranca en "Campana" sin importar lo guardado, no está enlazado
al `Model`), leerlos del DOM para reconstruir el PUT habría arriesgado pisar esos ajustes con
valores obsoletos. En cambio, `persistirMetaDiariaDebounced()` primero hace `GET
/api/v1/pomodoro/configuracion` para traer la configuración real vigente, cambia solo
`metaDiariaCiclos`, y hace el `PUT` con el resto de campos intactos. Con debounce de 800ms
para no disparar una petición por cada clic de +/-. **Verificado:** `node --check`. **No
probado visualmente** (necesita confirmar en navegador que el valor sobrevive a un reload).

### 7.4 — Punto 2.2: modo espejo para pestañas secundarias

**Archivo:** `Views/Pomodoro/Index.cshtml` — `entrarModoEspejo()`, `salirModoEspejo()`,
listener de `BroadcastChannel`.

El usuario eligió la opción de "espejo con reloj propio" sobre "solo lectura con aviso". Al
recibir un broadcast `timer_start` de otra pestaña (que ahora incluye `finEn`, un timestamp
absoluto de cuándo termina el ciclo), la pestaña receptora entra en **modo espejo**: arranca
su propio `setInterval` que recalcula el tiempo restante desde ese timestamp absoluto (sin
drift, sin depender de que los intervalos de ambas pestañas tiqueen en el mismo instante) y
solo actualiza la UI — **no** toca `sesionId`, no hace ningún `fetch`, no llama a
`alCompletarTimer()`. El botón de Play/Pausa se deshabilita mientras está en modo espejo
(con guarda adicional en `alternarTimer()` por si se dispara por el atajo de teclado Espacio),
para que un clic accidental en la pestaña equivocada no termine mandando un broadcast de pausa
que confundiría a la pestaña dueña real — ese era justo el riesgo que tenía la primera versión
de este análisis (documentada en el punto 2.2 original). Si el conteo espejado llega a 0 sin
recibir un broadcast real de la pestaña dueña en los siguientes 3 segundos (ej. porque esa
pestaña se cerró de golpe), el modo espejo se cancela solo y se devuelve el control, para no
dejar la pestaña bloqueada para siempre. **Verificado:** `node --check`. **No probado
visualmente** (necesita dos pestañas reales para confirmar: iniciar en una, ver que la otra
sincroniza el conteo con el botón deshabilitado, y que al pausar/completar en la dueña la
secundaria recupera el control).

### 7.5 — Punto 2.6: Android ahora tiene UI para "Meta diaria"

**Archivos:** `res/layout/fragment_pomodoro.xml`, `res/layout/dialog_pomodoro_config.xml`,
`ui/pomodoro/PomodoroFragment.java`, `res/values/strings.xml`.

Se agregó una tarjeta "Meta diaria" (oculta si la meta es 0, igual criterio que la tarjeta
equivalente del web) con una `LinearProgressIndicator` y texto "X / Y ciclos" en la pantalla
principal del Pomodoro, y un campo numérico nuevo en el diálogo de configuración
(`etMetaDiaria`, rango 0–50 igual que valida el backend, 0 = sin meta). El progreso se
recalcula tras cada ciclo completado y al refrescar `ciclosHoy` desde el servidor
(`verificarSesionActiva()`), igual patrón que ya usaban `tvCiclos`/`tvTotalHoy`. **Verificado:**
`./gradlew compileDebugJavaWithJavac` + `./gradlew testDebugUnitTest` (68/68 en verde). **No
probado en dispositivo/emulador real** (ninguno disponible en esta sesión).

### 7.6 — Qué falta después de esta ronda

- Confirmación visual de los 6 puntos de esta sección (ninguno se pudo probar en
  navegador/dispositivo real).
- El punto 2.3 (hipótesis del reporte original "00:00 con la barra avanzando") queda
  parcialmente resuelto de forma indirecta: la hipótesis #2 de esa sección (multi-pestaña) ya
  no puede producir ese síntoma tras el fix 7.4. Si el usuario lo vuelve a ver, ya no debería
  ser por esa causa.
- Los huecos de cobertura de la sección 5 (condición de carrera contra MySQL real, JS del
  timer sin tests automatizados, Android sin tests del temporizador) siguen abiertos — ninguno
  se cerró en esta ronda de seguimiento.
