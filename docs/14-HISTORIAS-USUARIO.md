# 14 — Historias de usuario y criterios de aceptación por módulo pendiente

> Este documento traduce cada módulo que falta de `docs/01-MODULOS.md` a historias de usuario
> con criterios de aceptación concretos, **para que cualquier IA (o persona) que continúe el
> proyecto pueda implementar sin tener que inferir el comportamiento exacto.** No inventa
> reglas de producto nuevas — cada criterio cita la regla de dominio de la que sale. Si una
> historia no tiene de dónde citar la regla, es porque hay una decisión de producto pendiente:
> se marca explícitamente como `⚠️ DECISIÓN PENDIENTE`, no se inventa a ciegas.
>
> **Formato de cada historia:**
> `Como <rol>, quiero <acción>, para <beneficio>.` seguido de criterios de aceptación en
> formato Given/When/Then. Un criterio sin fuente citada entre paréntesis es una consecuencia
> directa de otro criterio de la misma historia, no una regla nueva.
>
> **Antes de implementar un módulo de este documento:** leer también su sección completa en
> `docs/01-MODULOS.md` (tiene diagramas, ejemplos de código y contexto que acá no se repite) y
> `docs/13-ROADMAP.md` para confirmar que le toca el turno. Este documento complementa, no
> reemplaza, a esos dos.

---

## Orden recomendado (y por qué cambia respecto al de `docs/01-MODULOS.md` §17)

`docs/01-MODULOS.md §17` propone un orden pensado para 20 días con dos personas trabajando en
paralelo. `docs/13-ROADMAP.md` ya lo adaptó a "un módulo completo a la vez" y avanzó Habits,
Gamification y Pomodoro. Este documento extiende esa tabla con los módulos que al `13-ROADMAP.md`
todavía le faltaban **por completo** (`Calendar`, `Achievements`, `Motivation` no tenían fila
propia pese a estar documentados en `01-MODULOS.md`) y corrige el orden real que corresponde
ahora que la base ya existe:

| # | Módulo | Por qué en esta posición |
|---|---|---|
| 6 | **Calendar** | Sin interfaz propia, autocontenido, y **Missions y Wellbeing ya lo necesitan** (`docs/01-MODULOS.md §15`) — `CalendarReaderInterface` ya existe en `app/Shared/Domain/Contracts/` sin implementación real. Construirlo ahora evita que Missions/Wellbeing tengan que volver atrás después. |
| 7 | **Missions** | Trae `subtasks`, más complejidad de UI que Pomodoro. Usa `Calendar` para vencimientos. |
| 8 | **Wellbeing** | Diario + mood score; usa `Calendar` para marcar feriados/semanas de examen en su vista de calendario. |
| 9 | **Villains** | Depende de eventos de Habits, Pomodoro y Missions (`HabitCompleted`, `PomodoroCompleted`, etc. — todos ya emiten o van a emitir estos eventos). |
| 10 | **Ranking + Personalization** | Solo *leen* de Gamification por contrato — no pueden ir antes que su fuente, que ya existe. |
| 11 | **Achievements** | Escucha eventos de Habits, Pomodoro, Missions, Villains y Wellbeing (`VillainDefeated` incluido) — tiene que ir después de todos esos. |
| 12 | **Motivation** | Contenido estático sin dependencias reales — **puede adelantarse en paralelo a cualquier fase anterior** si hay más de una persona disponible, se numera acá solo para tener un lugar fijo en la lista. |
| 13 | **StudyGroups** | El más complejo de infraestructura del MVP (polling, rate limiting, purga a 7 días). |
| 14 | **AiAssistant** | Necesita que Wellbeing ya exista (es una de sus tres fuentes de contexto) y depende del protocolo de derivación de crisis — el más sensible, va casi al final a propósito. |
| 15 | **Admin** | Panel de solo lectura sobre todos los módulos anteriores. No puede ir antes que ellos. |

**No renumerar `docs/13-ROADMAP.md` sin también actualizar esta tabla** (y viceversa) — mantenerlas
en sync es responsabilidad de quien las toque, igual que exige `docs/13-ROADMAP.md` en su
cabecera.

---

## Fase 6 — Calendar

Sin pantalla propia. Provee datos a Wellbeing, Missions, Dashboard y Telemetry. Detalle completo
(feriados, período académico, `CalendarReaderInterface`) en `docs/01-MODULOS.md §15`.

### HU-CAL-1 — Sembrar feriados peruanos del ciclo

**Como** equipo de investigación, **quiero** que el sistema conozca los 16 feriados nacionales y
los días no laborables del año, **para** poder distinguir una caída de adherencia por feriado de
una caída por desmotivación real.

- **Given** la tabla `holidays` vacía, **when** corre el seeder de `config/holidays.php`,
  **then** existen 16 filas `type='holiday'` con las fechas exactas de la tabla de
  `docs/01-MODULOS.md §15` (Jueves/Viernes Santo de 2026 ya resueltos: 2 y 3 de abril).
- **Given** que el Ejecutivo puede publicar días no laborables adicionales por decreto,
  **when** se agregan al seeder, **then** quedan con `type='non_working'`, nunca mezclados con
  `type='holiday'` (son conceptos distintos, ver la tabla de distinción en `01-MODULOS.md`).
- **Given** cualquier fecha del `current_cycle` en `config/academic.php`, **when** cae dentro de
  `exam_weeks`, **then** `CalendarReaderInterface::isExamWeek()` devuelve `true`.
- ⚠️ **DECISIÓN PENDIENTE:** `docs/01-MODULOS.md` pide "verificar en gob.pe/feriados antes de
  sembrar la tabla, y anotar la fecha de consulta" — quien implemente esto debe hacer esa
  verificación real, no asumir que la tabla del documento sigue vigente sin revisarla.

### HU-CAL-2 — Exponer el contrato de lectura

**Como** desarrollador de Missions/Wellbeing/Telemetry, **quiero** consultar si una fecha es
feriado, no laborable o semana de examen sin conocer el detalle interno de Calendar, **para** no
duplicar esta lógica en cada módulo (razón de ser del módulo, `01-MODULOS.md §15`).

- **Given** `CalendarReaderInterface` ya declarado en `app/Shared/Domain/Contracts/`, **when** se
  implementa `EloquentCalendarReader`, **then** los 6 métodos de la interfaz (`isHoliday`,
  `isNonWorkingDay`, `isExamWeek`, `getHolidayName`, `interventionDayFor`, `holidaysInMonth`)
  quedan cubiertos por tests, no solo declarados.
- **Given** que los feriados no cambian durante el día, **when** se llama dos veces seguidas a
  cualquier método del contrato, **then** la segunda lectura viene de caché de 24 horas (regla de
  rendimiento explícita del documento — 16 filas no justifican una consulta por petición).

---

## Fase 7 — Missions

Detalle completo (estados, subtareas, `days_early_or_late`) en `docs/01-MODULOS.md §4`.

### HU-MIS-1 — Crear una misión con subtareas

**Como** estudiante, **quiero** crear una tarea académica y partirla en pasos chicos, **para**
que una tarea grande deje de sentirse pesada (razón declarada del nombre del módulo).

- **Given** el formulario de nueva misión, **when** el usuario la crea con dificultad `hard` y
  sin subtareas, **then** el sistema **sugiere** dividirla con el texto exacto documentado
  ("Las tareas grandes se sienten menos pesadas por partes. ¿Quieres dividirla?") — sugiere, no
  obliga (`01-MODULOS.md §4`).
- **Given** una misión ya creada, **when** se agregan subtareas, **then** el límite es 0 a 20
  por misión, reordenables por arrastre.
- **Given** una subtarea, **when** se le intenta poner una fecha propia, **then** la UI no lo
  permite — la fecha es siempre de la misión, nunca de la subtarea.
- **Given** una misión sin fecha de vencimiento, **when** se guarda, **then** nunca genera
  `days_early_or_late` ni puede pasar a estado `overdue`.

### HU-MIS-2 — Transición automática a "en progreso"

**Como** investigador, **quiero** que el estado de una misión refleje actividad real, no un clic
manual, **para** tener un dato de telemetría más fiable (`01-MODULOS.md §4`, "el paso a
`in_progress` es automático").

- **Given** una misión `pending`, **when** se completa su primera subtarea **o** se inicia un
  Pomodoro vinculado a ella, **then** pasa a `in_progress` sola, sin que el usuario la toque.
- **Given** una misión `in_progress` o `pending`, **when** pasa la fecha de vencimiento sin
  completarse, **then** pasa a `overdue` (vía cron diario 00:05 hora de Lima, evento
  `MissionOverdue`).
- **Given** una misión `overdue`, **when** se completa tarde, **then** pasa a `completed` con
  `days_early_or_late` positivo — el estado vencido no bloquea completarla después.

### HU-MIS-3 — Medir procrastinación con `days_early_or_late`

**Como** investigador, **quiero** un número que capture si el estudiante entregó antes o después
de lo previsto, **para** tener "la medida conductual de procrastinación más directa del sistema"
(cita textual de `01-MODULOS.md §4`).

- **Given** una misión con fecha de vencimiento, **when** se completa, **then**
  `days_early_or_late` se calcula **una sola vez**, en ese momento: negativo si fue antes,
  positivo si después.
- **Given** un `days_early_or_late` ya calculado, **when** ocurre cualquier evento posterior
  (reabrir la misión, editarla, etc.), **then** el valor **nunca se recalcula** — es inmutable
  una vez escrito.

### HU-MIS-4 — Completar subtareas y XP

**Como** estudiante, **quiero** que completar todas las subtareas cierre la misión sola, **para**
no tener que marcar dos veces lo mismo.

- **Given** una misión con N subtareas, **when** se completa la última, **then** la misión pasa
  a `completed` automáticamente (sin acción extra del usuario).
- **Given** una misión ya `completed`, **when** se intenta completar una subtarea suya (caso
  raro: reabrir y volver a marcar), **then** no se otorga XP — "completar una subtarea de una
  misión ya completada no da XP" (regla explícita).
- **Given** el tope diario de XP por misiones, **when** el usuario ya completó 3 misiones con XP
  hoy, **then** la cuarta se completa igual (el estado cambia) pero sin XP — mismo patrón que
  Habits/Pomodoro, nunca bloquear la acción del usuario, solo el XP.

### HU-MIS-5 — Vistas del módulo

**Como** estudiante, **quiero** ver mis misiones en lista, calendario y detalle, **para** elegir
la vista que mejor se ajusta a cómo pienso ese día (`01-MODULOS.md §4`, tabla de vistas).

- **Given** la vista Lista (principal), **when** carga, **then** el orden por defecto es:
  vencidas primero (más antigua arriba) → vencen hoy → vencen esta semana por prioridad → resto
  por fecha → completadas al final, colapsadas. El usuario puede reordenar por fecha, prioridad,
  dificultad o creación.
- **Given** la vista Detalle, **when** carga, **then** muestra las subtareas y un botón para
  iniciar un Pomodoro vinculado a esa misión (`mission_id`, columna ya nullable y sin FK en
  `pomodoro_sessions` desde la Fase 5, lista para conectar).
- **Given** la vista Completadas, **when** carga, **then** cada fila muestra
  `days_early_or_late` visible, no oculto en el detalle.

---

## Fase 8 — Wellbeing

Detalle completo (calendario, etiquetas, protección de datos) en `docs/01-MODULOS.md §5`. **Es
el módulo con mayor sensibilidad de datos del proyecto hasta ahora** — leer la sección de
protección de datos antes de escribir una sola línea de persistencia.

### HU-WEL-1 — Registrar el ánimo del día

**Como** estudiante, **quiero** registrar cómo me siento cuando quiera, con o sin texto,
**para** llevar un registro emocional sin que se sienta una obligación.

- **Given** el formulario de nueva entrada, **when** se envía solo con `mood_score` (1-5) y sin
  texto, **then** se guarda válida — el texto es **opcional**, el ánimo es **obligatorio**.
- **Given** un día cualquiera, **when** el usuario registra 4 entradas, **then** las 4 se
  guardan — no hay límite de entradas por día.
- **Given** la primera entrada del día, **when** se guarda, **then** otorga XP. **Given** una
  segunda entrada el mismo día, **when** se guarda, **then** NO otorga XP (evita farmear
  escribiendo veinte veces, sin impedir registrar el ánimo varias veces — regla explícita).

### HU-WEL-2 — Calendario mensual con emoji persistente

**Como** estudiante, **quiero** ver de un vistazo cómo fue mi mes emocional, **para** notar
patrones sin tener que abrir cada entrada.

- **Given** un día con una sola entrada, **when** se pinta en el calendario, **then** muestra el
  emoji de ese `mood_score` exacto.
- **Given** un día con varias entradas, **when** se pinta en el calendario, **then** muestra el
  emoji del **promedio redondeado**, no de la última entrada (razón: "el día no fue 'un 4'" si
  hubo un 2 a la mañana y un 4 a la noche — cita de `01-MODULOS.md §5`).
- **Given** un emoji ya pintado en un día pasado, **when** pasa el tiempo, **then** se queda de
  forma **permanente** — no se recalcula ni desaparece.
- **Given** un día del calendario, **when** coincide con un feriado o semana de examen
  (`CalendarReaderInterface` de la Fase 6), **then** se marca visualmente distinto — es la razón
  documentada por la que Wellbeing necesita Calendar antes de poder terminarse bien.
- **Given** un clic en cualquier día, **when** se abre, **then** muestra el detalle: todas las
  entradas de ese día con su hora, ánimo, texto y etiquetas.

### HU-WEL-3 — Etiquetas de catálogo cerrado

**Como** investigador, **quiero** que las etiquetas emocionales vengan de una lista cerrada,
**para** poder agregarlas estadísticamente (evita el error de la encuesta 2 / decisión D-16).

- **Given** el formulario de nueva entrada, **when** se muestran las etiquetas, **then** son
  exactamente estas 8, selección múltiple, opcional: `estrés`, `motivado`, `cansado`,
  `tranquilo`, `agobiado`, `enfocado`, `disperso`, `satisfecho`. Sin campo de texto libre para
  etiquetas nuevas.

### HU-WEL-4 — Resumen numérico para el asistente de IA (sin contenido sensible)

**Como** responsable de ética del proyecto, **quiero** que el asistente de IA reciba solo datos
agregados de Wellbeing, **para** poder declarar en el expediente de ética que ningún contenido
sensible sale del sistema (cita textual, `01-MODULOS.md §5`).

- **Given** `GetAiContextSummary`, **when** se llama, **then** devuelve exactamente: promedio de
  ánimo de 7 días, tendencia (subiendo/estable/bajando), etiquetas más frecuentes del período,
  número de días con registro. **Nunca** el texto de las entradas, ni etiquetas por entrada
  individual, ni fechas concretas — esos 4 campos están explícitamente prohibidos de salir.
- **Given** el contenido de una entrada, **when** se persiste, **then** está cifrado en reposo,
  y **nunca** aparece en telemetría (solo `mood_score` y longitud del texto van a telemetría),
  logs, ni exportaciones CSV del panel de Admin.

### HU-WEL-5 — Alerta de bienestar (solo al participante)

**Como** estudiante en una racha de ánimo bajo, **quiero** ver un contacto de apoyo, **para**
tener una salida visible sin que se rompa mi confidencialidad.

- **Given** un promedio diario ≤2 durante 5 días consecutivos, **when** se detecta, **then** se
  muestra el contacto de una ONG **solo al participante afectado**.
- **Given** esa misma alerta, **when** se dispara, **then** **no** se notifica a ningún
  administrador — la razón documentada es que notificar violaría la confidencialidad y
  desincentivaría el uso honesto del diario. No implementar ningún camino que filtre esto a
  Admin, ni "solo para casos graves".

---

## Fase 9 — Villains

Detalle completo de reglas de daño/HP en `docs/03-GAMIFICACION.md §6`; acá solo la estructura de
casos de uso (`01-MODULOS.md §7`).

### HU-VIL-1 — Asignación semanal automática

**Como** estudiante, **quiero** recibir un villano temático nuevo cada semana, **para** tener un
objetivo narrativo concreto en vez de "sigue estudiando" genérico.

- **Given** el cron de los lunes 00:00 hora de Lima, **when** corre, **then** asigna
  `VillainAssigned` a cada usuario activo, y expira (`VillainExpired`) la instancia de la
  semana anterior el domingo 23:59 — verificar los valores exactos de HP/daño por acción en
  `docs/03-GAMIFICACION.md §6`, no inventarlos acá.
- **Given** que el villano ya expiró, **when** el usuario completa una acción, **then** no debe
  poder aplicarle daño a una instancia vencida — validar el estado antes de `ApplyDamage`.

### HU-VIL-2 — Daño por eventos de otros módulos

**Como** estudiante, **quiero** que completar hábitos, pomodoros y misiones dañe al villano de la
semana, **para** que mi trabajo normal tenga un efecto visible narrativamente.

- **Given** `HabitCompleted`, `PomodoroCompleted` o `MissionCompleted` (los tres ya declarados
  como eventos de dominio en sus módulos respectivos), **when** se emiten, **then** Villains los
  escucha y aplica el daño correspondiente vía `ApplyDamage` — Villains es **listener**, nunca
  el emisor de estos tres eventos.
- **Given** el HP del villano llega a 0, **when** se detecta (`CheckDefeat`), **then** se emite
  `VillainDefeated` — este es el evento que Achievements (Fase 11) necesita para sus logros de
  categoría "Villanos".

---

## Fase 10 — Ranking + Personalization

Dos módulos chicos y sin dependencias entre sí, agrupados en una fase porque ninguno bloquea al
otro y ambos son rápidos una vez que Gamification (ya construido) existe.

### HU-RANK-1 — Tabla de posiciones como variable de control

**Como** investigador, **quiero** que el ranking no se convierta en la motivación principal del
estudio, **para** no contaminar la variable que realmente se está midiendo (constancia, no
competencia) — las 5 reglas de esta historia son **obligatorias**, citadas literal de
`01-MODULOS.md §9`.

- **Given** el ranking, **when** se implementa, **then** vive en una vista dedicada `/ranking`,
  **nunca** como widget embebido en el Dashboard.
- **Given** un cambio de posición de cualquier usuario, **when** ocurre, **then** no dispara
  ninguna notificación a nadie.
- **Given** cualquier visita a `/ranking`, **when** ocurre, **then** se registra
  `ranking.viewed` en Telemetry con posición, total de participantes y tiempo en pantalla.
- **Given** la lista de otros usuarios en el ranking, **when** se renderiza, **then** solo
  muestra alias, nivel y posición — nada más (ni racha, ni XP exacto, ni avatar).
- **Given** el cálculo de posición, **when** se hace, **then** es por XP total acumulado, y el
  resultado completo se cachea 7 minutos (con 70 usuarios no hace falta recalcular en cada
  visita, y hay 1 solo núcleo de CPU disponible).

### HU-PERS-1 — Preferencias visuales del usuario

**Como** estudiante, **quiero** elegir tema y fondo de pantalla, **para** personalizar mi
experiencia dentro de un catálogo curado.

- **Given** `UserPreferences` (`theme`, `wallpaper_id`, `notifications_enabled` — los campos
  `pomodoro_focus_minutes`/`pomodoro_break_minutes` de `01-MODULOS.md §12` **ya no aplican tal
  cual**: Pomodoro terminó guardando esa preferencia en `localStorage` del cliente, no en el
  servidor, ver `docs/01-MODULOS.md §3` "Ciclo completo, meta de estudio y música" — no
  dupliques ese campo acá sin revisar antes si sigue haciendo falta), **when** el usuario cambia
  el tema, **then** se aplica sin recargar la página.
- **Given** el catálogo de fondos de pantalla, **when** se muestra, **then** es cerrado — el
  usuario elige entre los que el equipo subió, nunca sube uno propio.
- **Given** un fondo marcado como "desbloqueable al derrotar villanos" (dependencia directa con
  Villains, Fase 9), **when** el usuario no lo desbloqueó todavía, **then** aparece
  visiblemente bloqueado en el selector, no oculto (para que sepa que existe como incentivo).
- **Given** que hoy solo existe **un** wallpaper fijo cableado por CSS (`docs/04-DISENO-VISUAL.md`,
  `--user-wallpaper` fijo a `atardecer.avif`), **when** se implemente el selector real, **then**
  hay que generalizar ese CSS para leer de la preferencia guardada, no solo agregar la UI encima
  de un valor que sigue fijo.

---

## Fase 11 — Achievements

Detalle completo de categorías/reglas en `docs/01-MODULOS.md §13`. Depende de que Habits,
Pomodoro, Missions, Wellbeing y Villains ya emitan sus eventos — por diseño, va al final de esa
lista de dependencias.

### HU-ACH-1 — Desbloqueo idempotente por evento

**Como** estudiante, **quiero** que mis logros se desbloqueen solos al cumplir la condición,
**para** no tener que reclamarlos manualmente.

- **Given** cualquier evento de dominio relevante (`HabitCompleted`, `PomodoroCompleted`,
  `StreakExtended`, `VillainDefeated`, etc.), **when** se emite, **then** Achievements lo
  escucha y evalúa **en cola**, no en la petición del usuario (evaluar 30 condiciones de forma
  síncrona bloquearía la respuesta con 1 núcleo de CPU — regla de rendimiento explícita).
- **Given** un logro ya desbloqueado para un usuario, **when** se vuelve a evaluar la misma
  condición (p. ej. el mismo evento se reprocesa por un reintento de cola), **then** no pasa
  nada — `UNIQUE (user_id, achievement_id)` garantiza que se desbloquea **una sola vez**.
- **Given** un logro desbloqueado, **when** se otorga, **then** da XP fijo entre 20 y 100 según
  rareza (definir la tabla exacta de rareza→XP al implementar, siguiendo ese rango — no está
  cerrada en `01-MODULOS.md`, es un rango, no valores exactos: ⚠️ **DECISIÓN PENDIENTE** los
  valores puntuales por logro) y puede desbloquear un fondo de pantalla (conexión directa con
  Personalization, Fase 10).

### HU-ACH-2 — Catálogo sin comparación social ni penalizaciones

**Como** investigador, **quiero** que ningún logro premie compararse con otros ni castigue
romper una racha, **para** no contaminar la variable de constancia con una de competencia
(alineación explícita con el diseño del estudio).

- **Given** el catálogo de logros, **when** se diseña, **then** ninguna condición usa la
  posición en el ranking ni ningún dato de otro usuario.
- **Given** cualquier evento negativo (romper racha, abandonar un Pomodoro, misión vencida),
  **when** ocurre, **then** **no existe** ningún logro que se dispare por eso — "no hay logros
  negativos ni penalizaciones" es una prohibición, no una omisión por ahora.
- **Given** las 6 categorías documentadas (Constancia, Volumen, Progresión, Villanos, Bienestar,
  Puntualidad), **when** se carga el catálogo, **then** viene de seeder, cerrado — no se crean
  logros desde la UI ni desde el panel de Admin.

---

## Fase 12 — Motivation

Contenido curado, sin gamificación real. Detalle completo (catálogo de frases, mecanismo de
rotación `NoRepeatPicker`) en `docs/01-MODULOS.md §14`. **Puede construirse en paralelo a
cualquier fase anterior** si hay más de una persona disponible — no tiene dependencias reales
más allá de Identity (saber quién inició sesión).

### HU-MOT-1 — Frase motivacional al iniciar sesión

**Como** estudiante, **quiero** ver una frase distinta cada vez que inicio sesión, **para** un
pequeño empujón sin que se sienta repetitivo.

- **Given** el catálogo de 10 frases de `01-MODULOS.md §14.1` (usar exactamente ese texto y
  atribución — 6 tienen fuente confirmada, 4 están marcadas "atribuida"; **no presentar las 4
  atribuidas como cita textual verificada**, mostrar la marca de honestidad), **when** el
  usuario inicia sesión, **then** se elige una con `NoRepeatPicker` (no se repite ninguna hasta
  que las 10 se mostraron al menos una vez) y se registra en `user_quote_views`.
- **Given** que el usuario ya vio la frase de esta sesión, **when** navega entre páginas del
  Dashboard sin cerrar sesión, **then** la frase **no cambia** — es una elección por login, no
  por visita.

### HU-MOT-2 — Consejos de uso por módulo

**Como** estudiante, **quiero** un tip breve y accionable la primera vez que entro a un módulo en
la sesión, **para** aprender a usarlo mejor sin que se sienta como un manual.

- **Given** el catálogo de `01-MODULOS.md §14.2` (1-2 consejos por módulo, ampliable con el mismo
  tono: específico, breve, accionable — nunca genérico), **when** el usuario visita un módulo por
  primera vez en la sesión, **then** se muestra un consejo elegible con `NoRepeatPicker`, en una
  tarjeta descartable.
- **Given** un consejo descartado, **when** el usuario lo cierra, **then** no vuelve a aparecer
  ese mismo consejo hasta agotar el ciclo del módulo (se registra en `user_tip_views`).

### HU-MOT-3 — Excepción al congelamiento de despliegue

**Como** equipo de investigación, **quiero** poder ampliar el catálogo de frases/consejos durante
los 66 días de intervención, **para** enriquecer el contenido sin comprometer la validez del
estudio.

- **Given** la prohibición general de `docs/07-DEPLOY.md` de desplegar durante la intervención,
  **when** el cambio es solo agregar filas a `MotivationalQuote`/`UsageTip`, **then** es la
  **única excepción explícita** permitida — no toca ninguna variable medida. Cualquier otro
  cambio (código, `config/gamification.php`, etc.) sigue prohibido durante ese período.
- **Given** cualquier adición al catálogo durante la intervención, **when** se hace, **then** se
  registra en `docs/12-HISTORIAL.md` de todas formas, por transparencia — la excepción es al
  congelamiento de despliegue, no a la obligación de dejar rastro.

---

## Fase 13 — StudyGroups

**El módulo de infraestructura más delicado del MVP.** Leer `docs/01-MODULOS.md §8` completo
antes de tocar esto — el diagrama de secuencia del polling y el cálculo de carga están ahí, no
se repiten acá.

### HU-SG-1 — Restricción de infraestructura (no negociable)

**Como** equipo técnico, **quiero** que el chat grupal funcione sin WebSockets, **para** que siga
funcionando en el hosting compartido de Hostinger, que no acepta conexiones entrantes de ese
tipo (restricción documentada como "no negociable").

- **Given** cualquier implementación de chat, **when** se construye, **then** usa **polling
  AJAX** cada 5 segundos exactos — no menos (con 1 núcleo, 3s triplica la carga sin beneficio
  perceptible), no un valor "configurable" que alguien pueda bajar sin revisar esta razón.
  **Laravel Reverb u otra solución con WebSocket entrante queda descartada de raíz.**
- **Given** que la pestaña del usuario pasa a segundo plano, **when**
  `document.visibilityState === 'hidden'`, **then** el polling se pausa — no sigue corriendo
  invisible.
- **Given** el endpoint de polling, **when** se llama, **then** responde solo los mensajes
  posteriores a `lastMessageId` — nunca el historial completo en cada poll.

### HU-SG-2 — Sesión de estudio grupal con límite de participantes

**Como** estudiante, **quiero** crear o unirme a una sesión de estudio con hasta 4 compañeros más,
**para** sostener el enfoque acompañado.

- **Given** una sesión con 5 participantes, **when** un sexto intenta unirse, **then** se
  rechaza — 5 es el máximo, no 5 "recomendado".
- **Given** una sesión abierta, **when** se inicia un Pomodoro grupal (`StartGroupPomodoro`),
  **then** aplica las mismas reglas de dominio de Pomodoro (Fase 5) para cada participante — no
  se inventa un motor de temporizador distinto para el caso grupal.
- **Given** mensajes con más de 7 días, **when** corre la purga programada, **then** se borran —
  son efímeros, explícitamente "no son dato del estudio".

### HU-SG-3 — Moderación básica

**Como** estudiante, **quiero** poder reportar un mensaje inapropiado, **para** que el
administrador pueda revisarlo sin que el contenido normal quede expuesto.

- **Given** cualquier mensaje enviado, **when** pasa por el filtro de palabras prohibidas,
  **then** se bloquea si coincide (filtro básico, no un modelo de IA — mantenerlo simple, es
  hosting compartido de 1 núcleo).
- **Given** un mensaje reportado, **when** se envía el reporte, **then** entra a una cola que
  revisa el administrador — el contenido del mensaje reportado **nunca entra en telemetría**,
  solo su longitud (misma regla de privacidad que el resto del chat).

---

## Fase 14 — AiAssistant

**El módulo más sensible del proyecto.** Leer `docs/01-MODULOS.md §10` completo, y
`docs/06-SEGURIDAD.md §9` antes de escribir el prompt de sistema — los guardrails son "no
opcionales ni negociables", cita textual.

### HU-AI-1 — Cuota diaria y cola asíncrona

**Como** equipo técnico, **quiero** que las consultas al asistente nunca bloqueen un PHP worker,
**para** proteger el único núcleo de CPU disponible del resto de la aplicación.

- **Given** una consulta nueva, **when** se envía, **then** se despacha a la cola `ai`
  (procesada por cron cada minuto con `--stop-when-empty`, el hosting no permite demonios) — la
  petición HTTP original nunca espera síncronamente la respuesta de DeepSeek.
- **Given** la cuota diaria (5 consultas/día por defecto, configurable), **when** el usuario ya
  la agotó, **then** el intento siguiente se rechaza con un mensaje claro, sin llegar a
  encolarse.
- **Given** que DeepSeek no responde en 30s o devuelve error, **when** ocurre, **then** el
  usuario ve un mensaje claro y **no se le descuenta la cuota** — un fallo del proveedor no
  puede costarle una consulta al participante.
- **Given** la cuota, **when** llega la medianoche hora de Lima, **then** se reinicia a 0.

### HU-AI-2 — Guardrails obligatorios del prompt de sistema

**Como** responsable de ética del proyecto, **quiero** que el asistente nunca dé consejo clínico
ni prometa resultados académicos, **para** cumplir el expediente del comité de ética e
ISO/IEC 23894.

- **Given** el prompt de sistema, **when** se escribe, **then** prohíbe explícitamente:
  diagnóstico, recomendación farmacológica, consejo clínico, y promesas de resultado académico.
- **Given** una consulta con señales de crisis (ideación suicida, autolesión, angustia severa),
  **when** se detecta, **then** la respuesta es un mensaje de contención — ⚠️ **DECISIÓN
  PENDIENTE**: el texto exacto del protocolo de derivación de crisis no está escrito en
  `01-MODULOS.md` (dice "mensaje de contenció*" cortado), hay que definirlo junto con
  `docs/06-SEGURIDAD.md §9` y probablemente con supervisión humana antes de escribirlo en
  código — no improvisar el texto de una situación de crisis real sin ese respaldo.
- **Given** cualquier respuesta, **when** se genera, **then** es en español peruano neutro.

### HU-AI-3 — Contexto sin datos personales al proveedor

**Como** responsable de ética, **quiero** que DeepSeek nunca reciba nombre, correo ni contenido
del diario, **para** minimizar la fuga de datos personales a un proveedor externo
(`docs/06-SEGURIDAD.md §9`).

- **Given** las tres fuentes de contexto (Wellbeing, y las otras dos que haya que definir al
  implementar — `01-MODULOS.md` solo confirma que Wellbeing es "una de las tres"; ⚠️ **DECISIÓN
  PENDIENTE**: cuáles son las otras dos no está escrito explícitamente, probablemente Habits y
  Pomodoro/Missions por ser las otras fuentes de datos conductuales, pero confirmar antes de
  construir), **when** se arma el contexto que viaja a DeepSeek, **then** solo van métricas
  agregadas (ver el resumen exacto que Wellbeing expone en HU-WEL-4) — nunca texto libre del
  usuario, nunca su nombre o correo.

---

## Fase 15 — Admin

Panel de solo lectura. Detalle completo en `docs/01-MODULOS.md §16`. Depende de que todos los
módulos anteriores existan — es literalmente el último por diseño.

### HU-ADM-1 — Panel de solo lectura, sin excepciones

**Como** equipo de investigación, **quiero** un panel administrativo que no pueda escribir datos
del estudio, **para** que la integridad del dataset no pueda cuestionarse en una revisión
académica ("si un administrador puede editar los datos, un revisor puede cuestionar todo el
dataset" — cita textual).

- **Given** cualquier endpoint del panel de Admin, **when** se implementa, **then** es de
  **lectura únicamente** — no existe un solo endpoint de escritura sobre datos de otros módulos.
- **Given** el rol requerido, **when** se protege cada ruta, **then** exige `admin` exclusivamente
  con doble factor obligatorio.
- **Given** las 5 prohibiciones explícitas del documento (leer diario, leer chat grupal, leer
  conversaciones de IA, ver nombre real junto a `participant_code`, modificar XP/niveles/
  telemetría), **when** se diseña cualquier vista nueva, **then** ninguna de las 5 puede
  violarse aunque parezca útil para "debug" — no hay excepción de conveniencia para el equipo.

### HU-ADM-2 — Las 6 vistas del panel

**Como** investigador, **quiero** las 6 vistas documentadas (Dashboard, Participantes, Deserción,
Telemetría, Exportación, Salud del sistema), **para** monitorear el estudio sin acceso a
contenido sensible.

- **Given** la vista Participantes, **when** carga, **then** muestra `participant_code`, nivel,
  racha y último acceso — **sin ningún dato personal** (el nombre real vive en una tabla
  separada con acceso restringido, no en esta vista).
- **Given** la vista Deserción, **when** carga, **then** lista usuarios sin actividad en 3+ días
  — "el indicador más importante durante la intervención", priorizarlo en el diseño de la UI.
  Cruzar contra `CalendarReaderInterface` (Fase 6) para no contar como "desertó" a alguien
  inactivo durante un feriado o fin de semana sin evidencia adicional — ⚠️ **DECISIÓN
  PENDIENTE**: el documento no especifica si el cálculo de "3+ días sin actividad" excluye
  feriados; decidirlo antes de implementar, con el equipo de investigación.
- **Given** la vista Exportación, **when** se genera, **then** produce los tres CSV del dataset
  (definidos en `docs/02-TELEMETRIA.md`, no repetidos acá).
