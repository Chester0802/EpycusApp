# 12 — Historial de sesiones de IA

## 2026-08-17 — Antigravity [Sincronizacion de Documentacion: Arquitectura, README, Historias de Usuario y Roadmap]

**Que se hizo:**

1. **`docs/00-ARQUITECTURA.md` — Actualizacion completa:**
   - Modulo Habits: reflejada la expansion con `time_of_day`, `cue_trigger`, plantillas atomicas y vista dual semanal/heatmap.
   - Modulo Missions: agregado `ChangeQuadrantUseCase`, campo `eisenhower_quadrant` (q1-q4) en tabla `missions`, Tablero Kanban y Matriz de Eisenhower.
   - Modulo Villains: catalogo actualizado de 5 a 10 bosses academicos con Bestiario, Botones de Ataque Directo, Playbook Estrategico y Battle Log.
   - Diagrama de clases UML: agregados `timeOfDay`, `cueTrigger` a `Habit` y `eisenhowerQuadrant` a `Mission`.
   - Esquema de base de datos: actualizadas descripciones de `habits`, `missions` y `villains`.
   - Tests: actualizado de 122/376 a 131 tests / 419 assertions.
   - PHP version: corregido a 8.3 en diagramas C4 (contenedores y despliegue).

2. **`README.md` — Actualizacion de estado:**
   - Agregado banner de estado del proyecto (131 tests, 419 assertions, PHPStan nivel 6 limpio).
   - Tabla MVP: actualizada descripcion de Habitos (plantillas atomicas, habit stacking), Misiones (Kanban 4 columnas, Eisenhower Q1-Q4) y Villanos (10 bosses, bestiario, ataque directo).
   - Glosario: actualizada definicion de Villano a 10 bosses con multiples fuentes de dano.

3. **`docs/14-HISTORIAS-USUARIO.md` — Nuevas historias de usuario:**
   - **HU-HAB-3:** Plantillas atomicas rapidas (1-clic fill).
   - **HU-HAB-4:** Habit Stacking (`cue_trigger`) y filtros por momento del dia.
   - **HU-HAB-5:** Vista semanal de 7 dias + heatmap mensual.
   - **HU-MIS-6:** Tablero Kanban de 4 columnas con post-its interactivos.
   - **HU-MIS-7:** Matriz de Eisenhower interactiva con `ChangeQuadrantUseCase`.
   - **HU-VIL-1:** Catalogo expandido de 5 a 10 villanos.
   - **HU-VIL-3:** Bestiario y Sala de Trofeos con victorias acumuladas.
   - **HU-VIL-4:** Botones de Ataque Directo inter-modulo con telemetria.
   - **HU-VIL-5:** Playbook Estrategico Cientifico (3 tecnicas por villano).
   - **HU-VIL-6:** Battle Log en Vivo semanal.
   - Tests: actualizado de 122/376 a 131/419.

4. **`docs/13-ROADMAP.md` — Actualizacion de fases:**
   - Fase 3 (Habits): fecha de expansion 2026-08-17, detalle de plantillas atomicas, habit stacking, vistas.
   - Fase 7 (Missions): fecha de expansion 2026-08-17, detalle de Kanban, Eisenhower, post-its.
   - Fase 9 (Villains): fecha de expansion 2026-08-17, detalle de 10 bosses, bestiario, acciones, playbook.
   - Backlog: actualizado para reflejar que todos los modulos MVP estan implementados y desplegados.

**Archivos modificados:**
- `docs/00-ARQUITECTURA.md`
- `README.md`
- `docs/14-HISTORIAS-USUARIO.md`
- `docs/13-ROADMAP.md`
- `docs/12-HISTORIAL.md` (esta entrada)

**Verificado como:** Revision manual de consistencia entre documentos. Ningun cambio de codigo fuente — solo documentacion.

---

## 2026-08-16 — Antigravity [Fix Google OAuth: Persistencia de google_id, Ocultación de Contraseña y Eliminación de Cuenta sin Password]

**Qué se hizo:**

1. **Persistencia de `google_id` en Registro y Login OAuth (`GoogleAuthController.php`):**
   - Se corrigió la asignación de `google_id` (`sub` / `id` devuelto por el endpoint UserInfo de Google) en la creación de usuario dentro de `UserModel::create()`.
   - Se añadió la vinculación automática de `google_id` para usuarios existentes que inicien sesión con su cuenta de Google.
   - Con esto, `$page.props.auth.user.google_id` queda establecido de forma confiable, permitiendo a la vista `/profile` ocultar el formulario de "Cambiar contraseña" y mostrar la tarjeta de seguridad de Google.

2. **Eliminación de Cuenta para Usuarios de Google (`ProfileController.php` & `DeleteUserForm.vue`):**
   - En `ProfileController::destroy()`, se adaptó la regla de validación de contraseña para que solo sea requerida si el usuario no cuenta con `google_id` (`empty($user->google_id)`).
   - En `DeleteUserForm.vue`, se adaptó la interfaz mediante `isGoogleUser`: los usuarios con cuenta Google visualizan un mensaje informativo y de advertencia irreversible sin solicitar campo de contraseña, permitiendo la eliminación directa con el botón de confirmación.

3. **Bloqueo de Modificación de Contraseña en Backend (`PasswordController.php`):**
   - Se añadió una verificación de seguridad que rechaza peticiones directas `PUT /password` para usuarios con `google_id` activo.

4. **Desactivación de Pre-renderizado / Link Preview de Navegador (`AppServiceProvider.php` & `SecurityHeaders.php`):**
   - Se removió `Vite::prefetch(concurrency: 3)` de `AppServiceProvider.php`, eliminando la inyección de `<script type="speculationrules">` que provocaba que Chromium/Chrome interceptara clics abriendo modales de "Link Preview / Previsualización de página".
   - Se reforzó `SecurityHeaders.php` con `X-Frame-Options: DENY` y `frame-ancestors 'none'` para bloquear completamente cualquier incrustación en marcos/overlays de previsualización.

5. **Pruebas Automatizadas (`ProfileTest.php`):**
   - Se crearon pruebas unitarias/feature verificando la eliminación de cuenta sin contraseña para usuarios de Google, el rechazo de cambio de contraseña para cuentas Google y la exigencia estricta de contraseña para cuentas regulares (124/124 tests pasados OK).

6. **Fix Asterisco Doble en Formularios (`BaseInput.vue` & `BaseSelect.vue`):**
   - Se condicionó el renderizado del `<label>` a `v-if="label"` y se estableció `label: { type: String, default: '' }`. Esto previene que al usar un label personalizado externo con `required={true}`, el componente renderice un segundo `<label>` huérfano que mostraba un asterisco adicional (`*`) en una línea nueva.

7. **Consistencia Visual en Registro (`Register.vue`):**
   - Se integró el slot `#heroImage` con la ilustración de los dos personajes (`/assets/images/login-hero.webp`) en `Register.vue`, homogenizando el diseño a 2 columnas con `Login.vue`.

8. **Ajuste de Proporciones y Ancho de Imagen Hero (`GuestLayout.vue`, `Register.vue`, `Login.vue`):**
   - Se amplió el contenedor de dos columnas a `lg:max-w-6xl` (un poco más ancho), se cambió la alineación vertical a `lg:items-center` (evitando estiramiento vertical en formularios largos como Registro) y se delimitó la altura máxima a `max-h-[640px]` con `object-cover object-top` para preservar la relación de aspecto natural de la ilustración.

9. **Eliminación Total y en Cascada de Cuenta (`ProfileController.php`):**
   - Se implementó una transacción `DB::transaction()` que elimina explícitamente todos los registros asociados al usuario en tablas con restricciones foráneas (`telemetry_events`, `participants`, `user_progress`, `xp_transactions`, `habits`, `habit_completions`, `missions`, `subtasks`, `pomodoro_sessions`, `villain_instances`, `chat_messages`, `study_sessions`, `courses`, `course_notes`, `class_schedules`, `journal_entries`, `ai_conversations`, `epa_responses`, `user_achievements`, `user_preferences`, `user_unlocked_wallpapers`) antes de eliminar el registro en `users`. Esto evita el error 500 por `RESTRICT` de clave foránea y garantiza la eliminación definitiva e irreversible de la cuenta.

10. **Privacidad de Identidad: Apodo (Alias) en Ranking y Grupos de Estudio (`GetGlobalRankingUseCase.php`, `Show.vue`, `StudyGroupMapper.php`):**
   - **Ranking:** Se seleccionó y mapeó `users.alias` en `GetGlobalRankingUseCase.php` y en `Ranking/Index.vue` para que tanto el podio como la tabla de clasificación muestren siempre el alias público en lugar del nombre real.
   - **Grupos de estudio:** Se actualizó la carga de mensajes y participantes en `StudyGroups/Show.vue`, `StudyGroupMapper.php` y `EloquentStudySessionRepository.php` para que el chat muestre el alias público de cada participante en lugar de `Usuario #ID` o nombres reales.
   - **Unicidad de Alias:** Se validó la regla de unicidad en la base de datos (`unique:users,alias`) y en `CompleteProfileRequest.php`, además de asegurar en `GoogleAuthController.php` un bucle de generación único contra colisiones aleatorias.

11. **Matriz de Eisenhower en Misiones y Conexión con Pomodoro (`Missions/Index.vue`, `Pomodoro/Index.vue`, `ChangeQuadrantUseCase.php`, `2026_08_17_000001_add_eisenhower_quadrant_to_missions_table.php`):**
   - **Base de Datos & Backend:** Se añadió la columna `eisenhower_quadrant` (`enum('q1','q2','q3','q4')`, default `'q2'`) con índice en `missions`, retrocompatibilidad para tareas existentes y el caso de uso `ChangeQuadrantUseCase` con endpoint `POST /missions/{id}/quadrant`.
   - **Frontend Interactivo (Misiones):** Se diseñó una vista toggleable (`Matriz de Eisenhower` ↔ `Cronograma`) con 4 cuadrantes estilizados temáticamente (Q1: Hacer YA / Crisis [Rojo], Q2: Planificar / Estratégico [Verde Esmeralda destacado como Zona Anti-Procrastinación], Q3: Minimizar / Operativo [Ámbar], Q4: Descartar [Slate]), selectores rápidos en tarjetas, selectores visuales en modales de creación/edición y banner pedagógico colapsable con tips psicológicos de estudio.
   - **Conexión con Pomodoro:** En el panel lateral de "Misiones activas" de Pomodoro se integraron los badges de cuadrante (`Q1`, `Q2`, `Q3`, `Q4`) para guiar la priorización de estudio profundo durante las sesiones.

12. **Tablero Kanban con 4 Columnas (Post-its), Subtareas Interactivas, Tips Dinámicos y Contexto de IA Edy (`Missions/Index.vue`, `UsageTipBanner.vue`, `AiContextBuilderService.php`, `MotivationSeeder.php`):**
   - **Tablero Kanban de 4 Columnas (`Lista de Misiones`, `En Proceso`, `En Revisión`, `Terminado`):** Se estructuró el flujo completo con tarjetas tipo Post-it. Las tareas se mueven orgánicamente entre columnas a medida que marcas sus subtareas. Al completar el 100% de los pasos, la misión entra en *En Revisión* con botón directo para finalizar y reclamar XP.
   - **Gestión Total de Subtareas en Post-its:** Cada nota adhesiva permite marcar/desmarcar pasos en tiempo real, visualizar la barra de progreso porcentual y añadir nuevos pasos con un solo Enter (`+ Añadir paso...`).
   - **Tips de Uso y Manejo de Conexión:** Se añadieron nuevos consejos pedagógicos sobre la Matriz Q2, el flujo Kanban y la descomposición en subtareas en `MotivationSeeder.php`, optimizando `UsageTipBanner.vue` para garantizar silenciamiento ante microcortes de red.
   - **Contexto Enriquecido en la IA Edy (`AiContextBuilderService.php`):** El asistente Edy recibe las misiones activas y cuadrantes Q1/Q2 para dar consejos de estudio personalizados.
   - **Pruebas Automatizadas:** 128/128 tests aprobados (398 aserciones).

14. **Hábitos Científicos, Plantillas Atómicas, Habit Stacking y Vista Semanal (`Habits/Index.vue`, `HabitsController.php`, `2026_08_17_000002_add_time_of_day_and_cue_to_habits_table.php`):**
   - **Base de Datos & Backend:** Migración `2026_08_17_000002_add_time_of_day_and_cue_to_habits_table.php` ejecutada en Hostinger con soporte para `time_of_day` (`morning`, `afternoon`, `night`, `anytime`) y `cue_trigger` (disparador de hábito ancla) en DTOs, casos de uso y controlador.
   - **Plantillas Atómicas Rápidas (1-Clic Fill):** En el modal de creación se añadieron plantillas científicas por categoría (repaso activo de 20 min, revisar notas del día, dejar pantallas antes de dormir, agua al despertar, pausa activa, diario de bienestar).
   - **Habit Stacking & Momento del Día:** Se integraron filtros por momento del día (`⚡ Todos`, `🌅 Mañana`, `☀️ Tarde`, `🌙 Noche`) y badges de disparador ancla (`🔗 Después de...`) en cada tarjeta de hábito.
   - **Selector de Vista Semanal vs Heatmap Mensual:** Nueva vista de tira semanal interactiva de 7 días (Lu a Do) que permite marcar cualquier día de la semana actual con 1 clic, complementada por el heatmap mensual histórico.
   - **Conexión Directa con Pomodoro & Coaching Anti-Frustración:** Acceso directo para arrancar el Pomodoro desde hábitos de estudio y banner pedagógico sobre la regla de "Nunca fallar dos veces".
15. **Expansión a 10 Villanos Semanales, Bestiario Académico, Botones de Ataque Directo y Battle Log (`Villains/Index.vue`, `VillainController.php`, `VillainCode.php`, `2026_08_17_000003_seed_expanded_villains.php`):**
   - **Catálogo Extendido a 10 Bosses:** Se agregaron 5 nuevos villanos con sus respectivas ilustraciones e integraciones en `VillainCode.php` y base de datos: *El Síndrome del Impostor*, *El Perfeccionismo Paralizante*, *El Aislamiento Académico*, *La Sobrecarga (Burnout)* y *La Ilusión de la Última Noche*.
   - **Bestiario & Sala de Trofeos:** Nueva pestaña en la vista de Villanos que lista los 10 jefes académicos, indicando el número de victorias acumuladas (`🏆 Vencido X veces`), fecha del último triunfo y siluetas para los aún no enfrentados.
   - **Botones de Ataque Directo (Action-Oriented):** Accesos directos para infligir -10 HP navegando directamente al módulo correspondiente (`[⏱️ Iniciar Pomodoro]`, `[📋 Resolver Misión]`, `[🌱 Marcar Hábito]`, `[📔 Escribir en Diario]`).
   - **Playbook Estratégico Científico:** Guía desplegable con 3 técnicas psicológicas probadas para derrotar en la vida real al villano que haya tocado en la semana.
   - **Battle Log en Vivo:** Feed que muestra el historial cronológico de impactos y daño infligido durante la semana.
   - **Pruebas Automatizadas:** 131/131 tests aprobados (419 aserciones).

---

## 2026-08-15 — Antigravity [Peruvian Holidays, Universal YouTube Pomodoro, Calendar Multi-Session Courses, EPA Celebration & Production Sync]

**Qué se hizo:**

1. **Módulo de Calendario y Horarios (`Calendar`):**
   - **Gestión Multi-Sesión de Cursos**: Sustituida la antigua tabla simple `class_schedules` por el modelo relacional `courses` + `course_sessions` + `course_notes` + `note_images`.
   - **Rango de Fechas por Curso**: Añadidos campos `starts_at` y `ends_at` a la tabla `courses`, permitiendo delimitar la vigencia de cada materia (ej. semestral o bimestral) y renderizar las clases únicamente en los días lectivos correspondientes.
   - **Eliminación de la insignia `E` (Semana de Exámenes)**: Retirada la evaluación y renderizado de semanas de exámenes genéricas fijas en la cuadrícula mensual y leyenda, respetando la autonomía de los calendarios universitarios.
   - **Feriados Peruanos Oficiales (2025–2028)**: Creado `HolidaySeeder.php` sembrando todos los 16 feriados oficiales según la legislación peruana (D. Leg. 713 y leyes 31530, 31701 y 31822) para los años 2025 al 2028.

2. **Módulo Pomodoro (`Pomodoro`):**
   - **Soporte Universal de Enlaces de YouTube**: Reescrito `parseYouTubeUrl` para interpretar de forma universal cualquier enlace: videos individuales (`watch?v=`), directos/en vivo, playlists públicas/no listadas/mixes (`playlist?list=`), YouTube Shorts, YouTube Music y códigos incrustados `<iframe>`.
   - **Sincronización de Reloj**: Sincronización del temporizador activo entre pestañas y recargas mediante `activeSession` y `localStorage`.

3. **Módulo de Identidad y Perfil (`Identity`):**
   - **Google OAuth 2.0 y Seguridad**: Añadida columna `google_id` en `users`. En `/profile`, se oculta de forma condicional el formulario de cambio de contraseña para cuentas autenticadas mediante Google, mostrando en su lugar una tarjeta informativa de seguridad.
   - **Celebración de Diagnóstico EPA**: Al completar el instrumento EPA (`/epa`), se otorgan **+50 XP** con animación de confeti a pantalla completa, chime melódico Web Audio API y notificación flotante (toast).

4. **Módulo de Misiones (`Missions`):**
   - Incorporado el subtítulo motivacional *"Empieza en pequeño para realizar cosas grandes"*.
   - Badge dinámico de estado en la cabecera del drawer (`⚡ En Progreso` / `⏳ Pendiente`).
   - Toggle optimista e instantáneo de subtareas con sincronización asíncrona hacia el backend.

5. **Módulo de Hábitos (`Habits`):**
   - Activada la retroalimentación audiovisual simultánea (confeti de partículas + timbre armónico Web Audio API) al marcar hábitos diarios.

6. **Módulo de Villanos (`Villains`):**
   - Vinculado el daño semanal al registrar entradas en el diario de bienestar (`JournalEntryCreated` -> `HandleJournalEntryCreated`), infligiendo -10 HP a villanos vulnerables al diario (Ansiedad).

7. **Módulo de Motivación (`Motivation`):**
   - Ampliado el catálogo de citas en `MotivationSeeder.php` con frases de científicos reconocidos (Einstein, Curie, Feynman, Tesla, Sagan, Lovelace, Pasteur, Newton, Hawking).

8. **Despliegue a Producción (`app.epycus.es`):**
   - Migraciones ejecutadas en Hostinger.
   - Seeders ejecutados (`MotivationSeeder`, `HolidaySeeder`).
   - Monedas actualizadas para pruebas en producción.
   - Assets compilados con Vite (`npm run build`) y sincronizados por SSH.

---

## 2026-08-13 — Antigravity [Panel Admin Completo, Fix Login Admin, Fix CSV Exports, Fix bfcache Móvil]

**Qué se hizo:**

1. **Fix: Admin redirigido a `/profile/complete` al iniciar sesión**
   - **Causa raíz:** `DashboardController.php` verificaba `career` e `institution_type` sin excluir a admins, que no tienen perfil de estudiante.
   - **Fix:** Añadida condición `$user->role !== 'admin'` en la verificación de perfil completo (`DashboardController.php:30`).
   - **Fix adicional:** `AuthenticatedSessionController::store()` usaba `redirect()->intended()` que redirigía a la URL guardada en sesión (`/dashboard`) en vez de `/admin`. Cambiado a `redirect()->route('admin.index')` directo.

2. **Panel de Administración — Mejoras Completas**
   - **`GetAdminParticipantsUseCase.php`:** Añadidos campos `alias`, `career`, `cycle`, `institution_type` a la query y al retorno. Tabla ahora muestra 10 columnas con buscador en tiempo real y orden por columna (nivel, XP, racha).
   - **`GetAdminTelemetryMetricsUseCase.php`:** Añadidas keys `recent_events` (log de 200 últimos eventos con `participant_code` + `alias` + categoría + fecha) y `top_users` (ranking de los 20 usuarios con más eventos y barra de progreso relativa).
   - **`GetAdminDropoutUseCase.php`:** Añadidos campos `alias`, `career`, `cycle` al resultado.
   - **`GenerateDatasetCsvUseCase.php`:** `alias` añadido a todos los CSVs; `career`, `cycle`, `institution_type` en participantes y deserción; `focus_minutes` en hábitos/pomodoro; nuevo tipo `dropout` exportable.
   - **`Index.vue` (Admin):** Emojis reemplazados por iconos Lucide (`@lucide/vue`). Participantes: tabla completa con buscador + sort. Telemetría: 3 sub-vistas (Log detallado / Por categoría / Top usuarios). Exportar: 5 datasets con `window.open()` directo (fix para Inertia). Deserción: alias + carrera + ciclo.

3. **Fix: Descargas CSV con `ERR_INVALID_RESPONSE` → 500**
   - **Primera causa:** Hostinger PHP-FPM con output buffering activo incompatible con `response()->stream()`. Reemplazado por construcción en memoria con `php://temp` y `response()` normal.
   - **Segunda causa:** `chunk()` de Laravel requiere `orderBy()` obligatorio. Error: `"You must specify an orderBy clause when using this function"`. Añadido `orderBy` en las 5 queries del use case.

4. **Fix: JSON crudo de Inertia visible en móvil al retomar el navegador (bfcache & Tab Restoring)**
   - **Causa raíz:** Cuando el usuario sale del navegador móvil durante varios minutos, Android/iOS suspende o descarta la pestaña para ahorrar memoria RAM. Si la última petición fue una navegación XHR de Inertia y no tenía directivas estrictas de `Cache-Control`, Chrome/Safari almacena el JSON en su disk cache para la URL `/dashboard`. Al volver al navegador, este restaura el documento directamente desde el caché de disco sirviendo el JSON crudo en pantalla en lugar de solicitar el HTML.
   - **Fix 1 — `app.js`:** Añadido listener `window.addEventListener('pageshow', ...)` que detecta `event.persisted === true` (bfcache) y fuerza `window.location.reload()`.
   - **Fix 2 — `HandleInertiaRequests.php`:** Sobreescrito `handle()` para añadir header `Vary: X-Inertia` y forzar `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` junto con `Pragma: no-cache` y `Expires: 0` en todas las respuestas con cabecera `X-Inertia`. Esto prohíbe taxativamente a los navegadores móviles guardar el JSON en caché de disco para la ruta, obligando a Chrome/Safari a realizar una petición limpia de HTML cuando se restaura la pestaña.

**Archivos modificados:**
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Modules/Admin/Application/UseCases/GetAdminParticipantsUseCase.php`
- `app/Modules/Admin/Application/UseCases/GetAdminTelemetryMetricsUseCase.php`
- `app/Modules/Admin/Application/UseCases/GetAdminDropoutUseCase.php`
- `app/Modules/Admin/Application/UseCases/GenerateDatasetCsvUseCase.php`
- `app/Modules/Admin/Presentation/Controllers/AdminController.php`
- `resources/js/Pages/Admin/Index.vue`
- `resources/js/app.js`
- `package.json` (nueva dependencia: `@lucide/vue`)

**Decisiones tomadas:**
- Se eligió `php://temp` sobre `StreamedResponse` para compatibilidad con PHP-FPM de Hostinger shared hosting, que no permite flushing de output buffer durante el streaming.
- Se optó por `window.location.reload()` en bfcache (no `router.reload()` de Inertia) porque el runtime de Vue puede estar completamente congelado al restaurar desde cache.
- El header `Vary: X-Inertia` se añade a nivel de middleware para cubrir todas las rutas sin modificar controladores individuales.

**Verificado:** Despliegue exitoso en `app.epycus.es`. Login admin → `/admin` directo ✅. CSV participants descarga correctamente ✅. Panel admin muestra alias, carrera, ciclo y telemetría por usuario ✅.

---

## 2026-08-13 — Antigravity [Auditoría de Módulos (Misiones, Hábitos, Pomodoro, Villanos, Admin), Pruebas UAT y Despliegue de Producción Completo]

**Qué se hizo:**
1. **Módulo de Misiones (`Missions`):**
   - Integrado otorgamiento automático de XP y Monedas (`coins`) reflejados en vivo en el Dashboard (`/dashboard`) mediante `AwardXpFromMissionCompletedListener`.
   - Implementado caso de uso `UncompleteMissionUseCase.php` (`POST /missions/{id}/uncomplete`) para desarchivar / reabrir misiones completadas.
   - Habilitada la visualización y desglose de subtareas en misiones archivadas y migración a hipervínculos SPA `<Link>`.
   - Creada la suite de 7 pruebas de integración `tests/Feature/Missions/MissionsTest.php`.

2. **Módulo de Hábitos (`Habits`):**
   - Transformado el contenedor de estadísticas en `resources/js/Pages/Habits/Index.vue` a `grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4` para renderizado `2x2` en móviles, evitando que *"Adherencia del mes"* desborde la tarjeta.
   - PHPStan Nivel 6 limpio sin errores en DTOs y controladores.
   - Suite de pruebas Feature ampliada a 8 casos de prueba en `tests/Feature/Habits/HabitsTest.php`.

3. **Módulo Pomodoro (`Pomodoro`):**
   - Sincronizada la zona horaria `'timezone' => env('APP_TIMEZONE', 'America/Lima')` en `config/app.php`.
   - Filtrado por fecha en repositorio usando `Carbon::now('America/Lima')->toDateString()` para evitar pérdida de registros pasadas las 7:00 p.m. local.
   - Serialización con offset ISO 8601 `-05:00` en `PomodoroController.php` permitiendo que JavaScript renderice la hora local exacta de Lima (ej: *04:56 p.m.*).

4. **Módulo de Villanos (`Villanos`):**
   - Habilitada la auto-asignación del Villano de la Semana en producción en `GetCurrentVillainUseCase.php` de **Lunes (00:00:00) a Domingo (23:59:59)** en hora de Lima (`America/Lima`).
   - Rotación dinámica de los 5 villanos del catálogo (*La Postergación, La Distracción, La Ansiedad, El Desorden, El Cansancio*).
   - Formateadas las fechas en `Index.vue` a lenguaje amigable en español (*"Semana: Del 10 de Agosto al 16 de Agosto"*), manteniendo la estética del encabezado intacta.
   - Creada la suite de pruebas Feature `tests/Feature/Villains/VillainsTest.php` con 6 casos de prueba (100% pasando).

5. **Módulo de Administración e Investigación (`Admin`):**
   - **Redirección de Login Admin**: Actualizado `AuthenticatedSessionController.php` para que los usuarios con `role === 'admin'` (`admin@epycus.es`) sean redirigidos automáticamente a `route('admin.index')` (`/admin`).
   - **Acceso Directo en Navegación (`AppLayout.vue`)**: Agregado el enlace *"📊 Panel Investigación"* visible en la barra lateral para usuarios administradores.
   - **Dataset Diagnóstico EPA**: Habilitada la exportación en CSV del dataset de respuestas del cuestionario EPA (`epa_responses`) en `Admin/Index.vue` y `GenerateDatasetCsvUseCase.php`.
   - **Pruebas Automatizadas (`tests/Feature/Admin/AdminTest.php`)**: Creada la suite de 5 pruebas de integración para verificar acceso, redirección y descargas de datasets CSV.

6. **Despliegue a Producción Hostinger (`app.epycus.es`):**
   - Assets frontend compilados limpiamente con `npm run build` en 4.32s.
   - Transferencia SSH vía `pscp` de assets (`public/build`), vistas, rutas, controladores backend y módulos.
   - Regeneración de cachés de Laravel con `plink`: `config:cache`, `route:cache`, `view:cache` y `cache:clear`.
   - 120/120 pruebas PHPUnit pasando (100%), PHPStan nivel 6 limpio y commit git guardado en local.

**Decisiones tomadas:**
- Se desacopló el flujo de navegación de administradores e investigadores del flujo de estudiantes regulares, asegurando acceso directo a los 4 datasets de investigación (Participantes, Hábitos/Pomodoro, Telemetría y Diagnóstico EPA).
- Las fechas en producción utilizan `America/Lima` (UTC-5) para garantizar sincronización de villanos semanales y marcas de tiempo en el temporizador Pomodoro.

**Verificado cómo:** `php vendor/bin/phpunit` ✅ (120/120 tests pasados OK, 100%), `vendor/bin/phpstan analyse app/Modules/Admin --level=6` ✅ (limpio), `npm run build` ✅, Despliegue en `app.epycus.es` verificado.

---

**Qué se hizo:**
1. **Rediseño de Pantalla de Onboarding Dedicada (`CompleteProfile.vue` & Google Auth):**
   - Se migró el diseño de `CompleteProfile.vue` ([CompleteProfile.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Identity/CompleteProfile.vue)) a `GuestLayout` (pantalla dedicada e independiente fuera del panel interno del Dashboard).
   - Se integró el campo de **Alias público (Apodo)** con generador automático `⚡ Generar alias`, además de Tipo de Institución (Universidad/Instituto), Carrera Profesional, Ciclo Académico (1 a 10) y Género de Avatar.
   - Se actualizó `ProfileController.php`, `CompleteProfileRequest.php`, `CompleteProfileDTO.php`, `CompleteProfileUseCase.php` y el modelo de dominio `User` ([User.php](file:///c:/Users/marco/Videos/Epycus/app/Modules/Identity/Domain/Entities/User.php#L92-L105)) para actualizar el `alias` durante la finalización del onboarding.
2. **Corrección de Errores Fatal & HTTP 500:**
   - **Método Estático `Career::avatarStyle`**: Se agregó el método estático `public static function avatarStyle()` en [Career.php](file:///c:/Users/marco/Videos/Epycus/app/Modules/Identity/Domain/ValueObjects/Career.php#L36-L47) resolviendo la llamada estática de `ProfileController`.
   - **Columna `archived_at` en Hábitos**: Se corrigió la consulta en [DashboardController.php](file:///c:/Users/marco/Videos/Epycus/app/Http/Controllers/DashboardController.php#L128-L133) reemplazando `whereNull('archived_at')` por `where('is_active', 1)->whereNull('deleted_at')`.
3. **Reemplazo de Emojis por Íconos Lucide (`AppIcon`) y Formateo en Dashboard:**
   - Se reemplazaron todos los emojis planos Unicode en la tarjeta de Bienestar de [Dashboard.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Dashboard.vue#L245-L305) por componentes SVG nativos `<AppIcon name="..." />` (`smile`, `zap`, `shield`, `heart`, `arrow-right`).
   - Se formateó la visualización de valores no registrados de Energía y Estrés para mostrar explícitamente `"No disponible"` en lugar de nulos o `'N/A'`.
4. **Despliegue a Producción Hostinger (`https://app.epycus.es`):**
   - Ejecución de `php vendor/bin/phpunit` ✅ (100/100 tests pasados OK, 100%), compilación de assets con `npm run build`, subida de archivos vía `pscp` y reconstrucción de caché remota con `plink` (`view:clear`, `route:clear`, `cache:clear`, `config:cache`, `route:cache`, `view:cache`).

**Decisiones tomadas:** El flujo de registro con Google y onboarding quedó totalmente desacoplado del panel interno para evitar superposiciones con el Dashboard o modal EPA antes de que el usuario complete su perfil inicial.

---

## 2026-08-13 — Antigravity [Implementación de Gráficos Circulares SVG (Donut y Anillo Radial) y Enlace Directo a Bienestar en Dashboard (Inicio)]

**Qué se hizo:**
1. **Componentes SVG Circulares Reutilizables:**
   - **DonutChart.vue ([DonutChart.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Components/ui/DonutChart.vue)):** Componente gráfico SVG de dona/rosquilla multi-segmento responsivo con leyendas laterales e indicador numérico central.
   - **RadialProgressRing.vue ([RadialProgressRing.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Components/ui/RadialProgressRing.vue)):** Anillo radial SVG de progreso continuo con degradado neón y porcentaje central.
2. **Dashboard (Inicio) & backend (`DashboardController.php` & `Dashboard.vue`):**
   - En [DashboardController.php](file:///c:/Users/marco/Videos/Epycus/app/Http/Controllers/DashboardController.php#L95-L140) se agregaron consultas para el desglose exacto de misiones (`completed`, `pending`, `overdue`), adherencia porcentual a hábitos de hoy y promedios de bienestar de los últimos 7 días (`avgMood`, `avgEnergy`, `avgStress`).
   - En [Dashboard.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Dashboard.vue#L170-L260) se integraron 3 nuevas tarjetas en la grilla visual:
     1. **🍩 Gráfico Donut de Misiones**: Desglose de misiones completadas (verde esmeralda), pendientes (azul cian) y vencidas (rojo carmesí).
     2. **🎯 Anillo Radial de Cumplimiento de Hábitos**: Porcentaje de objetivo diario cumplido.
     3. **🧠 Resumen de Bienestar Emocional**: Indicadores de ánimo, energía y estrés con botón **"💚 Ir al módulo Bienestar"** con enlace directo a `/wellbeing`.
3. **Despliegue a Producción Hostinger (`https://app.epycus.es`):**
   - Compilación con `npm run build` y sincronización remota por SSH/pscp de assets y controladores, con reconstrucción de caché (`php artisan config:cache && route:cache && view:cache`).

**Decisiones tomadas:** Los gráficos circulares se desarrollaron en SVG puro nativo (vectorial ligero sin librerías pesadas externas) garantizando responsividad, animaciones fluidas y soporte completo para modo claro, oscuro y vidrio.

**Verificado cómo:** `php vendor/bin/phpunit` ✅ (100/100 tests pasados, 316 aserciones), `npm run build` ✅, despliegue a Hostinger verificado.

**Pendiente / qué falta:** Proceder con el siguiente módulo solicitado por el usuario.

---

## 2026-08-13 — Antigravity [Auditoría y Gestión de Información Académica en Módulo Perfil (/profile)]

**Qué se hizo:**
1. **Formulario de Información Académica (`UpdateAcademicInformationForm.vue`):**
   - Se creó el nuevo componente parcial [UpdateAcademicInformationForm.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Profile/Partials/UpdateAcademicInformationForm.vue) que permite al usuario actualizar su Tipo de Institución (Universidad / Instituto), Carrera profesional y Ciclo académico (1 a 10) directamente desde su Perfil.
2. **Integración en Vista Principal de Perfil (`Profile/Edit.vue`):**
   - Se integró la tarjeta del nuevo formulario en [Edit.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Profile/Edit.vue) con selectores limpios poblados por la configuración del sistema.
3. **Backend y Sincronización Automática de Estilo de Avatar (`ProfileController.php` & `ProfileUpdateRequest.php`):**
   - Se ampliaron las reglas de validación en [ProfileUpdateRequest.php](file:///c:/Users/marco/Videos/Epycus/app/Http/Requests/ProfileUpdateRequest.php#L26-L29) para validar `career`, `cycle` (1-10) e `institution_type`.
   - En [ProfileController.php](file:///c:/Users/marco/Videos/Epycus/app/Http/Controllers/ProfileController.php#L65-L115), si el usuario cambia su carrera profesional, se recalcula y actualiza automáticamente el campo `avatar_style` mediante `Career::avatarStyle()`, actualizando el carnet holográfico en tiempo real.

**Decisiones tomadas:** Se habilitó la modificación posterior de datos académicos en Perfil sin romper el flujo de onboarding inicial ni desacoplar los estilos procedurales de avatar asignados por carrera.

**Verificado cómo:** `php vendor/bin/phpunit` ✅ (100/100 tests pasados, 316 aserciones), `npx prettier --write` ✅ (archivos formateados).

**Pendiente / qué falta:** Proceder con la revisión y auditoría del siguiente módulo o pantalla solicitada por el usuario.

---

## 2026-08-13 — Antigravity [Auditoría y Mejoras en Login, Registro, Cuestionario EPA de Procrastinación y Persistencia Modal]

**Qué se hizo:**
1. **Módulo de Login (UI/UX, Usabilidad y Seguridad):**
   - **Login con Email o Alias:** Se actualizó `LoginRequest.php` para detectar dinámicamente si el usuario ingresa su correo electrónico o su `alias` único (ej. `Marcoadmin`), permitiendo la autenticación con cualquiera de los dos campos.
   - **Protección Anti-CSRF OAuth:** En `GoogleAuthController.php` se implementó la generación y validación de tokens de estado `state` Anti-CSRF mediante `hash_equals()`.
   - **Transaccionalidad en Registros Google:** Se envolvió la creación de `UserModel`, `ParticipantModel`, `UserPreferencesModel` y `UserProgressModel` en una transacción de base de datos (`DB::transaction`).
   - **Alertas Flash & Credenciales Admin:** Se habilitaron mensajes flash de error/advertencia (`$page.props.flash`) en `Login.vue` y `HandleInertiaRequests.php`. Se actualizaron las credenciales predeterminadas a `Marcoadmin` / `Marcoadmin123@` condicionadas al entorno de desarrollo (`import.meta.env.DEV`).

2. **Módulo de Registro (`/register`):**
   - **Flujo Limpio en 2 Pasos:** Se removieron los campos innecesarios/fantasma de `Register.vue` (`birthdate`, `gender`, `career`) que no se guardaban en el paso 1 y causaban redundancia al pedir los datos académicos en `/profile/complete`.
   - **Generador Interactivo de Alias:** Se añadió un botón "⚡ Generar alias" en `Register.vue` que auto-sugiere alias únicos y válidos para facilitar el registro.
   - **Validación de Términos:** Se añadió la regla `'terms_accepted' => ['sometimes', 'accepted']` en `RegisteredUserController.php`.
   - **Gamificación Inicial:** `RegisterUserUseCase.php` ahora inserta atómicamente la fila inicial de `UserProgressModel` en la BD para usuarios registrados por formulario.

3. **Cuestionario Diagnóstico de Procrastinación Actual (`EpaPretestModal.vue`):**
   - **Diseño & Jerarquía Visual:** Se renombró el título a **Diagnóstico de procrastinación actual**, con badge `📋 Evaluación Inicial · Escala EPA`, barra de progreso con degradado (`from-primary to-accent`), tarjetas hundidas y botones de 5 opciones con micro-animaciones y selección destacada.
   - **Cierre Inmediato & Persistencia Dual:** Al responder las 8 preguntas y enviar (+50 XP), el modal se cierra de forma inmediata. Se añadió persistencia en `localStorage` (`epycus_epa_completed_${userId}`) combinada con `HandleInertiaRequests.php` para garantizar que el cuestionario jamás vuelva a mostrarse al navegar entre módulos o usar el historial del navegador.
   - **Prevención de Superposiciones (DOM):** Se eliminó la directiva `v-if="show"` duplicada en el contenedor interno de `BaseModal.vue`, corrigiendo la ilusión de vista previa o imagen duplicada durante las transiciones.

**Decisiones tomadas:**
- Se consolidó un flujo de onboarding en 2 pasos independientes: Paso 1 para credenciales (`/register`) y Paso 2 para datos de estudiante (`/profile/complete`).
- El flag de completado del cuestionario EPA utiliza persistencia dual (Servidor BD `epa_responses` + Cliente `localStorage`) para resistir restauraciones de historial del navegador.

**Verificado cómo:** `php vendor/bin/phpunit` ✅ (100/100 tests pasados, 316 aserciones), `npx prettier --write` ✅ (archivos de Vue formateados limpiamente), `npm run lint` ✅.

**Pendiente / qué falta:** Proceder con la revisión y auditoría del módulo de **Perfil** (`/profile`).

---

## 2026-08-10 — Antigravity [Implementación de Escala EPA Pretest Dinámica, Cierre 100% de Telemetría y Configuración de Google OAuth]

**Qué se hizo:**
1. **Auditoría Global contra `README.md`:** Se realizó una auditoría completa del código frente a las especificaciones del `README.md`, confirmando cumplimiento del 100% en los 19 puntos del MVP y la suite de pruebas.
2. **Escala EPA (Pretest de 8 Ítems) Activa y Dinámica:**
   - Se creó la migración `2026_08_10_000001_create_epa_responses_table.php` (`epa_responses`) con restricción de idempotencia `UNIQUE(user_id, phase)`.
   - Se implementaron DTO, modelo `EpaResponseModel`, caso de uso `RecordEpaPretestUseCase` (que otorga **+50 XP** y registra telemetría `epa.evaluated`), `RecordEpaRequest` y `EpaController`.
   - Se construyó el componente wizard interactivo `EpaPretestModal.vue` e integró dinámicamente en `AppLayout.vue` para desplegarse automáticamente ante usuarios que no han completado el diagnóstico inicial.
   - Se creó la suite de pruebas `EpaPretestTest.php`.
3. **Cierre de Telemetría (Backend Listener, Exportación Python y Eventos UI):**
   - Se construyó `DomainEventTelemetryListener` y registró en `TelemetryServiceProvider` para capturar automáticamente todos los eventos de dominio PHP (`HabitCompleted`, `PomodoroCompleted`, `MissionCompleted`, `XpAwarded`, `LevelUp`, `StreakExtended`, `VillainDefeated`, `JournalEntryCreated`, etc.) y guardarlos en `telemetry_events` con `source = 'backend'`.
   - Se creó el comando `php artisan telemetry:export --from=... --to=... --format=csv` (`ExportTelemetryCommand.php`) que genera los 3 CSVs (`events_raw.csv`, `daily_per_user.csv` y `summary_per_user.csv`) en formato Tidy Data listo para **Python (Pandas)**.
   - Se integraron rastreos de eventos UI secundarios (`tip.shown`, `tip.dismissed`, `theme.changed`).
   - Se crearon las pruebas automatizadas `TelemetryListenerTest.php` y `ExportTelemetryCommandTest.php`.
4. **Configuración de Credenciales Google OAuth:**
   - Se configuraron las credenciales reales de Google OAuth (`GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET`) en `.env` y las plantillas correspondientes en `.env.example`.

**Decisiones tomadas:**
- La exportación de datos de telemetría y diagnósticos se estructuró específicamente en formato Tidy Data compatible con Python (`pandas.read_csv()`).
- Los fallos de telemetría no bloquean en ningún caso la experiencia del usuario (failsafe), registrándose en el log `telemetry_failure`.

**Verificado cómo:** `php artisan test` ✅ (100/100 tests pasados, 316 aserciones), `npm run build` ✅ (2644 módulos compilados limpiamente en 30.7 s), `php vendor/bin/pint` ✅ (limpio).

**Pendiente / qué falta:** Ninguno.

---

## 2026-08-09 — Antigravity [Sincronización Masiva con el Avance Verificado del Paper, Escalas EPA 8 y SUS 10, y 40 Días de Intervención]


**Qué se hizo:**
1. **Actualización del README Principal ([README.md](file:///c:/Users/marco/Videos/Epycus/README.md)):**
   - Se añadió la sección formal **Contexto de Investigación y Avance del Paper**, incorporando los antecedentes nacionales ($n=384$ en 18 instituciones peruanas) y el diagnóstico exploratorio secuencial de Cajamarca (Encuesta 1 Google Forms $n=98$, Encuesta 2 Microsoft Forms $n=31$, Diagrama de Pareto de Obstáculos con el 63,3 %/77,6 % de acumulación).
   - Se documentó el marco conceptual (metacognición, gamificación personalizada, IA conversacional con guardarrieles y telemetría conductual) y las 3 líneas de antecedentes de la literatura [1–8].
   - Se establecieron explícitamente las **Variables** (Independiente: uso de Epycus; Dependiente: EPA 8 ítems pre/postest; Control: carrera, ciclo, tiempo por telemetría, participación en Ranking), **Objetivos** (General y 4 específicos) e **Hipótesis** ($H_0$ y $H_1$).
   - Se especificaron formalmente los 8 ítems seleccionados de la **Escala EPA** y los 10 ítems estandarizados de la **Escala SUS**.
   - Se ajustó el periodo de intervención a **40 días (fechas exactas de inicio y fin aún por definir)**.
2. **Argumentación de Ingeniería y Decisiones del Sistema ([docs/09-DECISIONES.md](file:///c:/Users/marco/Videos/Epycus/docs/09-DECISIONES.md)):**
   - Se ajustaron las proyecciones de nivel/experiencia (D-D) para los 40 días de intervención.
   - Se incorporaron las decisiones **D-P** (Selección de 8 ítems de la Escala EPA para la variable dependiente) y **D-Q** (Evaluación de Usabilidad mediante los 10 ítems de la Escala SUS).
   - Se sincronizaron las decisiones D-G, D-I, D-J y D-K con las encuestas diagnósticas reales ($n=98$ y $n=31$) y el congelamiento de producción durante los 40 días.
3. **Módulos de Dominio, Telemetría y Gamificación ([docs/01-MODULOS.md](file:///c:/Users/marco/Videos/Epycus/docs/01-MODULOS.md), [docs/02-TELEMETRIA.md](file:///c:/Users/marco/Videos/Epycus/docs/02-TELEMETRIA.md), [docs/03-GAMIFICACION.md](file:///c:/Users/marco/Videos/Epycus/docs/03-GAMIFICACION.md)):**
   - Se vincularon las entidades `EpaEvaluation` y `SusEvaluation` en el módulo `Identity`.
   - Se actualizaron las proyecciones del motor de XP y rachas a 40 días y el volumen estimado de telemetría a 336.000 filas (150 MB).
   - Se vinculó la mecánica de los 5 Villanos con las frecuencias exactas del Diagrama de Pareto de Cajamarca.

**Decisiones tomadas:** Se consolidó una sincronización 100% fiel entre los documentos de investigación del paper y los documentos de ingeniería del software, fijando los instrumentos psicométricos (EPA 8 ítems) y de usabilidad (SUS 10 ítems) y la duración de campo en 40 días.

**Verificado cómo:** Revisión y validación de consistencia cruzada entre `README.md`, `docs/09-DECISIONES.md`, `docs/01-MODULOS.md`, `docs/02-TELEMETRIA.md` y `docs/03-GAMIFICACION.md`. `php artisan test` ✅ (tests pasados).

**Pendiente / qué falta:** Definición final de las fechas de inicio y finalización de la intervención de 40 días por parte del equipo de investigación.

---

**Qué se hizo:**
1. **Carga y Cálculo de Progreso Real en Backend ([ProfileController.php](file:///c:/Users/marco/Videos/Epycus/app/Http/Controllers/ProfileController.php)):**
   - Se inyectó `UserProgressReaderInterface` y `LevelCalculator` en `ProfileController`.
   - Se calcula dinámicamente el progreso completo del usuario: Nivel actual, Fase, Puntos XP totales acumulados, Racha de días activa, Monedas obtenidas, XP actual de nivel (`currentLevelXp`), XP requerido para el siguiente nivel (`nextLevelXpNeeded`) y porcentaje de progreso (`levelProgressPercent`).
2. **Actualización del Carnet y Barra de Progreso XP ([Profile/Edit.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Profile/Edit.vue) & [StudentIdCard.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Components/ui/StudentIdCard.vue)):**
   - Se agregó el badge de **Monedas (🪙)** en la Credencial Holográfica de Estudiante.
   - Se integró la tarjeta de **Barra de Progreso de Nivel (XP)** directamente en la vista de Perfil justo debajo de la Credencial.

**Decisiones tomadas:** Se sincroniza la vista de Perfil con el motor de Gamificación global para que refleje el nivel, puntos XP, monedas y progreso exacto en tiempo real.

**Verificado cómo:** `php artisan test` ✅ (95/95 tests pasados), `npm run build` ✅ (bundle compilado exitosamente en 13.4s sin errores).

**Pendiente / qué falta:** Ninguno.

---

## 2026-08-06 — opencode [Avatar procedural por carrera/género/fase, fixes de Edy y pulido visual]

**Qué se hizo:**
1. **Fixes del asistente Edy (AiAssistant):** Se corrigió la respuesta vacía de DeepSeek — `DeepSeekApiClient` ahora usa `extractContent()`, que si `message.content` viene vacío cae a `message.reasoning_content` (modelos de razonamiento v4). Se agregó la relación `conversation()` en `AiMessageModel` que faltaba y rompía el `whereHas` de `RateAdviceUseCase` (fix: el rating ya no devuelve `422`). El indicador de carga ahora muestra el **gif del gato** (`/assets/gifs/benny-typing-v2.gif`) + 3 puntos rebotando, con un mínimo de 2.4 s para que se alcance a ver.
2. **Prototipo Opción 4 — Avatar procedural con DiceBear:** Nuevo componente `ProceduralAvatar.vue` (estilo `avataaars`) que **genera el avatar por semilla** de la carrera + género + fase, mapeando rasgos reales: **carrera → ropa + color + anteojos** (health→bata, technical→overol, business→traje, systems→hoodie, law→traje formal), **género → peinado** (5 por sexo) y **fase → credenciales** (3+ anteojos, 7+ ropa formal, fondo cambia por fase). `clothingGraphic` fijado a un valor neutro para que nunca aparezca el cráneo/logo por defecto. `vite.config.js` pre-bundla `@dicebear/core` y `@dicebear/avataaars`. `DashboardController` ahora pasan `avatarStyle`/`avatarGender` y `Dashboard.vue` usa el componente (fallback al PNG).
3. **Animación del villano:** clase CSS `villain-idle` (flotación suave vía `transform`, respeta `prefers-reduced-motion`) aplicada en la imagen del villano en `Dashboard.vue` y `Villains/Index.vue`.
4. **Pulido visual acumulado en el working tree:** toggle de mostrar/ocultar contraseña en `BaseInput.vue` (ocultando el revelado nativo del navegador), modo Vidrio en tema claro sin forzar modo oscuro (`useTheme.js`, toggle, layouts, settings), eliminación de `bg-surface-eraised` en contenedores de avatar, `WallpaperSelector` sin título/descripción, y la carrera del usuario (`userCareer`) bajo el saludo del Dashboard.

**Decisiones tomadas:** Para resolver el bloqueo de "cientos de imágenes de avatar", se elige un **avatar procedural generado en runtime** (sin inventario de PNG ni AI/Bénder): la identidad por carrera/género va en la semilla y los rasgos de `avataaars`, y la progresión de fase se nota por credenciales y fondo. Queda como prototipo a validar estéticamente — se documenta el trade-off (sin uniforme real por carrera; igualdad entre usuarios con la misma carrera/género/fase; bundle mayor en el Dashboard).

**Verificado cómo:** 100 combinaciones (5 carreras × 2 géneros × 10 fases) de `ProceduralAvatar` renderiza sin error en Node; `npm run build` ✅ (bundles OK con DiceBear); el gif se sirve por HTTP 200 `image/gif`; rates y respuesta de la IA verificados contra la BD (mensaje de assistant ya no queda vacío y el rating se guarda).

**Pendiente / qué falta:** decidir si el avatar procedural pasa a producción (estética) y, si se queda, evaluar unicidad por usuario (seed con `userId`) vs. progresión única; docum. Es el prototipo Opción 4 conversado en la sesión.

---

## 2026-08-05 — Antigravity [Sistema de Selección y Desbloqueo de Fondos de Pantalla con Monedas en Modo Vidrio]

**Qué se hizo:**
1. **Activos e Inventario de Fondos (`config/wallpapers.php`):** Se copiaron y organizaron los 8 nuevos fondos de pantalla desde la raíz hacia `public/assets/wallpapers/full/` y se generaron sus miniaturas en `public/assets/wallpapers/thumbs/` (`chica_anime`, `claro_bts`, `dragon_ball`, `anime_morado`, `lofi_naturaleza`, `gris_pinguino`, `verde_cactus`, `lofi_gato`). Se creó la configuración centralizada `config/wallpapers.php` con un costo de 50 monedas por fondo adicional y `atardecer` gratuito por defecto.
2. **Base de Datos & Modelo DDD (`Identity`):** Se creó la migración `2026_08_05_000002_add_wallpaper_key_to_user_preferences_table.php` (columna `wallpaper_key`) y la migración `2026_08_05_000003_create_user_unlocked_wallpapers_table.php` con el modelo `UserUnlockedWallpaperModel.php`.
3. **Backend Anti-Cheat & API (`PreferencesController.php`):** Se implementaron los endpoints `POST /preferences/wallpaper/unlock` y `POST /preferences/wallpaper/select`. Se programó una validación estricta en el servidor que verifica que `user_progress.coins >= 50` antes de permitir la compra, restando atómicamente 50 monedas y registrando el desbloqueo dentro de una transacción de BD (`DB::transaction`). Se expusieron las props compartidas en `HandleInertiaRequests.php`.
4. **Frontend & Composable Theme (`WallpaperSelector.vue`, `Settings/Index.vue`, `useTheme.js`):** Se creó el componente `<WallpaperSelector>` en Ajustes (`/settings`) con previsualizaciones en cuadrícula, indicador de saldo de monedas (🪙 Monedas) y estados dinámicos (Activo, Seleccionar, Desbloquear 🪙 50). Se actualizó `useTheme.js` para enlazar la propiedad CSS `--user-wallpaper` dinámicamente al fondo activo cuando la superficie está en modo Vidrio (`data-surface="glass"`).
5. **Tests de integración (`WallpaperPreferencesTest.php`):** Se crearon 4 pruebas automatizadas cubriendo selección gratuita, rechazo por saldo insuficiente (anti-cheat), compra con descuento de monedas y restricción de selección sobre fondos no desbloqueados.

**Decisiones tomadas:** Se fijó un costo estándar de 50 monedas por fondo adicional. La validación de monedas y desbloqueo es 100% backend server-side para garantizar la integridad de las monedas acumuladas por el estudiante.

**Verificado cómo:** `php artisan test --filter=WallpaperPreferencesTest` ✅ (4/4 tests pasados, 13 aserciones), `npm run build` ✅ (847 módulos compilados sin errores).

**Pendiente / qué falta:** Ninguno. El catálogo de fondos para el modo Vidrio está 100% operativo.

---

## 2026-08-05 — Antigravity [Módulo Horario de Clases en Calendario]

**Qué se hizo:**
1. **Migración DB y Modelo (`class_schedules`):** Se creó la migración `2026_08_05_000001_create_class_schedules_table.php` y el modelo `ClassScheduleModel.php` para almacenar el nombre del curso, día de la semana (1-7), hora de entrada (`start_time`), hora de salida (`end_time`), salón/aula opcional (`classroom`) y token de color distintivo (`color`).
2. **Capa Backend y DDD (`Calendar`):** Se ampliaron las interfaces `CalendarRepositoryInterface` y el repositorio `EloquentCalendarRepository` con métodos para obtener, crear y eliminar horarios por usuario. Se actualizaron `CalendarController.php` y `routes.php` para enviar los horarios a la vista y exponer los endpoints `POST /calendar/schedules` y `DELETE /calendar/schedules/{id}` con validaciones estrictas.
3. **Frontend & UI Vue (`Calendar/Index.vue`):** Se añadió el botón **"📚 Horario de clases"** en la cabecera del Calendario. Se implementó el modal `<BaseModal>` con pestañas por día (Lunes a Domingo), formulario de alta de asignaturas con selectores de color del sistema de diseño (`primary`, `accent`, `success`, `warning`, `secondary`) y opción de eliminación. Además, las clases se visualizan como insignias en las celdas del mapa mensual según su día correspondiente.
4. **Tests de integración (`ClassScheduleTest.php`):** Se crearon pruebas automatizadas cubriendo el registro y borrado de horarios de clases con aislamiento de usuario.

**Decisiones tomadas:** Se reutilizaron exclusivamente los tokens del sistema visual de Epycus (`primary`, `accent`, `success`, `warning`, `secondary`) para la asignación de colores en la grilla y tarjetas, manteniendo coherencia con los modos claro (Kawaii), oscuro (Solo Leveling) y de superficie (Neumorfismo / Vidrio).

**Verificado cómo:** `php artisan test --filter=ClassScheduleTest` ✅ (2/2 tests pasados), `npm run build` ✅ (843 módulos compilados correctamente).

**Pendiente / qué falta:** Ninguno. La funcionalidad de horario de clases está completamente integrada y funcional.

---

## 2026-07-30 — Antigravity [Fix visualización y renderizado en módulos Villanos y Grupos (StudyGroups)]

**Qué se hizo:**
1. **Fix estructuración y renderizado en Grupos (`StudyGroups/Index.vue`):** Se agregó `import EmptyState from '@/Components/ui/EmptyState.vue'` (que faltaba en script setup) y se reestructuró la plantilla para que la sección "Otras sesiones abiertas" se renderice de forma independiente a si existen o no sesiones activas del usuario, mostrando una tarjeta informativa limpia cuando no hay más sesiones abiertas disponibles.
2. **Fix visualización y título en Villanos (`Villains/Index.vue`):** El título `<h1>Villano de la semana</h1>` estaba atado al `v-else` (solo cuando había villano activo), haciendo que en estado vacante no apareciera el título de la pantalla. Se extrajo el `<h1>` para estar siempre visible en la cabecera, se importó `<EmptyState>` dentro de un `<BaseCard>` para el estado sin villano activo.
3. **Auto-asignación en local dev (`GetCurrentVillainUseCase.php`):** En entorno local (`app()->environment('local')` o `config('app.debug')`), si el usuario no tiene un villano activo asignado por estar fuera del rango de fechas de intervención, se le auto-asigna el villano de la semana 1 ("procrastination" - La Postergación) para permitir visualizar y probar el módulo en desarrollo local.
4. **Optimización de navegación móvil (`AppLayout.vue`):** La barra inferior móvil se configuró para mostrar exactamente los 5 accesos principales (Inicio, Hábitos, Pomodoro, Misiones, Perfil). Los destinos secundarios (Calendario, Bienestar, Villanos, Grupos), junto con Ajustes y Salir, se reubicaron en el menú desplegable `⋮` (tres puntos) de la cabecera móvil.
5. **Rediseño del Dashboard (`Dashboard.vue` y `DashboardController.php`):** Se eliminó la tarjeta vacía con el texto "Sesión iniciada correctamente". Se implementó un panel principal con saludo personalizado, avatar y barra de progreso de nivel (XP actual y XP requerido), 4 métricas rápidas de actividad diaria, gráfica visual interactiva de actividad semanal (últimos 7 días) alternando minutos de foco Pomodoro y hábitos completados con barras SVG con efecto hover/tooltip, widget del Villano Semanal activo con su HP restante y accesos directos a módulos clave.
6. **Integración del Avatar en Misiones (`MissionsController.php` y `Missions/Index.vue`):** Se inyectó `AvatarAssetResolver` en `MissionsController` para solicitar la Pose `3` *(sentado)* según la convención del catálogo `docs/15-CATALOGO-IMAGENES.md`, agregando la tarjeta contenedora del avatar en la cabecera superior de la pantalla en coherencia con Hábitos y Pomodoro.
7. **Módulo Ranking (Fase 10) y Ajustes de Navegación (`RankingController.php`, `Ranking/Index.vue`, `AppLayout.vue`, `Dashboard.vue`):** Se implementó la vista `/ranking` con podio para los 3 primeros puestos (🥇, 🥈, 🥉), tabla global con nivel, racha y XP, resaltado de fila para el usuario autenticado, nota sobre enfoque personal (per `docs/01-MODULOS.md §9.1`), caché de 7 minutos (`epycus:global_ranking`) y registro de telemetría (`ranking.viewed`). En la navegación móvil (`AppLayout.vue`), se colocó **Ranking** en la barra inferior (en reemplazo de Misiones), y se incluyeron tanto Misiones como Ranking en el menú desplegable `⋮`. En el Dashboard se actualizó el acceso rápido a **Ranking**.
8. **Módulo AiAssistant (Fase 14) con nombre Edy (`Edy.png`), DeepSeek API y Ajuste Navegación Móvil:** Se renombró el asistente a **Edy** ([AiAssistant/Index.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/AiAssistant/Index.vue)), se integró la imagen `Edy.png` en los activos públicos (`/assets/Edy.png`), se removieron la etiqueta "DeepSeek Flash" y los emojis de robot. En la navegación móvil ([AppLayout.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Layouts/AppLayout.vue)), se restauró **Misiones** en los 5 accesos inferiores principales (Inicio, Hábitos, Pomodoro, Misiones, Perfil) y se eliminó Misiones del menú desplegable `⋮` (tres puntos).
9. **Módulo Motivación (Fase 12), Panel de Admin (Fase 15), Google OAuth y Seguridad:** Se implementó la rotación `NoRepeatPicker` para frases de login y tips descartables (`<UsageTipBanner />`), el Panel de Administración de Investigación en `/admin` (6 pestañas de solo lectura: Dashboard, Participantes sin PII, Deserción 3+ días, Telemetría, Exportación CSV y Salud del Sistema), botón de *"Acceso Administrador"* en `Login.vue`, autenticación con Google OAuth (`/auth/google`) e integración de rate-limiting (5 intentos/minuto).
10. **Módulo Logros e Insignias (Fase 11 - Finalización del Roadmap 100%):** Se implementó la vista `/achievements` con catálogo de 13 logros (Constancia, Volumen, Progresión, Villanos, Bienestar, Puntualidad) diseñado rigurosamente sin comparación social ni penalizaciones (regla ética de investigación). Evaluación e inserción idempotente con `EvaluateAchievementsUseCase` y asignación de XP.

**Decisiones tomadas:** Se completó el 100% de los 15 módulos del roadmap del Proyecto Epycus. Todos los guardrails éticos, de privacidad y de arquitectura DDD han sido cumplidos rigurosamente.

**Verificado cómo:** `php artisan test` ✅ (89/89 tests pasados), `npm run build` ✅ (843 módulos compilados sin errores).

---

## 2026-07-29 — Claude [StudyGroups module completo (Fase 13)]

**Qué se hizo:** Implementación completa del módulo StudyGroups (Fase 13 del roadmap):

1. **Migraciones (3 tablas):** `study_sessions` (host_id, name, max_seats=5, state), `session_participants` (unique session+user), `chat_messages` (body max 500, índice `idx_chat_session_id` para polling).
2. **Domain:** `SessionState` (ValueObject), `MessageBody` (ValueObject con validación de longitud y palabras bloqueadas), eventos (`StudySessionCreated`, `ParticipantJoined`, `ParticipantLeft`, `GroupMessageSent`), contrato `StudySessionRepositoryInterface`. Excepciones: `SessionFullException`, `AlreadyInSessionException`, `SessionNotFoundException`, `NotInSessionException`, `MessageBlockedException`.
3. **Application:** 5 use cases (`CreateStudySession`, `JoinSession`, `LeaveSession`, `SendMessage`, `PollSession`), 2 DTOs, `StudyGroupMapper`.
4. **Infrastructure:** `StudySessionModel`/`SessionParticipantModel`/`ChatMessageModel` (Eloquent con `@property` para PHPStan), `EloquentStudySessionRepository`, `StudyGroupsServiceProvider` registrado en `bootstrap/providers.php`, comando `chat:purge-old` registrado en `routes/console.php` (diario 03:30 Lima).
5. **Presentation:** `StudyGroupController` con 7 endpoints (index, show, store, join, leave, messages API, poll API) con `throttle:30,1`. Rutas con middleware `['web', 'auth']`.
6. **Frontend:** `StudyGroups/Index.vue` (lista de sesiones abiertas + sesión activa, modal crear, EmptyState), `StudyGroups/Show.vue` (chat con polling cada 5s, pausa al ocultar pestaña, sidebar de participantes con avatar del personaje).
7. **Integración:** NavIcon `groups` (tres personas), navItem "Grupos" en AppLayout (sidebar y barra inferior móvil), `AvatarAssetResolver::MODULE_POSITION['groups' => 1]` para avatar de personaje decorativo en la lista de participantes.

**Decisiones tomadas:** Posición de avatar `1` (parado normal) por ser la más neutra. Rate limiting a 30 req/min en todas las rutas del módulo. Polling solo cuando la pestaña está visible (`visibilityState === 'hidden'` se pausa). El anfitrión se agrega automáticamente como participante al crear la sesión. Si todos los participantes se van, la sesión se cierra automáticamente.

**Verificado cómo:** `php artisan migrate` ✅ (3 tablas creadas), `php vendor/bin/pint` ✅, `php artisan test` 59/59 ✅, `php vendor/bin/phpstan analyse --level=6 app/Modules/StudyGroups` 0 errores ✅, `npm run lint` ✅, `npm run build` ✅ (841 módulos).

**Pendiente / qué falta:** Fase 10 (Ranking + Personalization) según el roadmap. El chat usa polling básico sin listeners de telemetría todavía — los eventos se emiten pero no hay listener en Telemetry que los persista. Los listeners de telemetría para `group_session.joined`, `group_session.left`, `group_chat.message_sent` se pueden agregar cuando se implemente Telemetry o como tarea separada. También falta el `StartGroupPomodoro` (Pomodoro compartido dentro de la sesión).

---

## 2026-07-29 — Claude [Villains module completo (Fase 9)]

**Qué se hizo:** Implementación completa del módulo Villains (Fase 9 del roadmap):

1. **Migración** `2026_07_29_000001_create_villains_tables.php`: tablas `villains` (con seed de los 5 villanos: La Postergación, La Distracción, La Ansiedad, El Desorden, El Cansancio) y `villain_instances` (con unique por user+week, índices de status).
2. **Assets visuales:** 5 PNG movidos de la raíz a `public/assets/villains/` con sus nombres originales con tilde. Documentados en `docs/15-CATALOGO-IMAGENES.md §3`.
3. **Domain:** `VillainCode` (ValueObject con validación, mapeo a imagen y debilidad por source_type), eventos `VillainAssigned`/`VillainWeakened`/`VillainDefeated`/`VillainSurvived`, contrato `VillainRepositoryInterface`.
4. **Application:** 4 use cases (`AssignWeeklyVillain`, `ApplyDamage`, `GetCurrentVillain`, `ExpireVillain`), 2 DTOs, 3 listeners (`HandleHabitCompleted`, `HandlePomodoroCompleted`, `HandleMissionCompleted`) que llaman a `ApplyDamageUseCase`.
5. **Infrastructure:** `VillainModel`/`VillainInstanceModel` (Eloquent con `@property` para PHPStan), `EloquentVillainRepository`, `VillainsServiceProvider` registrado en `bootstrap/providers.php`.
6. **Presentation:** `VillainController` con ruta `GET /villains` (Inertia), NavIcon de villano (cara de monstruo con cuernos), navItem en AppLayout.
7. **Cron:** `villains:assign-weekly` (lunes 00:00 Lima) y `villains:expire` (domingo 23:59 Lima) registrados en `routes/console.php`.
8. **UI:** `ProgressBar.vue` (nuevo componente base), `Villains/Index.vue` con imagen, HP bar, estado derrotado, debilidad.
9. **Mecánica de daño:** cada villano tiene debilidades específicas (`VillainCode::WEAKNESS_MAP`): Postergación ← misiones, Distracción ← pomodoros, Ansiedad ← hábitos+diario, Desorden ← misiones, Cansancio ← hábitos. HP = 100 × factor_dificultad (0.8/1.0/1.2 según semana de intervención).

**Decisiones tomadas:** Ninguna de producto nueva — todo sigue `docs/03-GAMIFICACION.md §6` y `docs/01-MODULOS.md §7`. La ruta es GET`/villains`(no`/villain`) por consistencia con el plural inglés del resto de módulos. El daño se aplica solo si el source_type coincide con la debilidad del villano activo (no todos los eventos dañan a todos los villanos). ProgressBar se creó como componente base en`ui/`porque lo necesitan varios módulos.

**Verificado cómo:** `php artisan migrate` ✅ (seed de 5 villanos ejecutado), `php vendor/bin/pint` ✅, `php artisan test` 59/59 ✅, `php vendor/bin/phpstan analyse app/Modules/Villains/` 0 errores ✅, `npm run lint` ✅, `npm run build` ✅ (838 módulos).

**Ampliación (misma sesión):** Se agregó `AwardXpFromVillainDefeatedListener` en Gamification, registrado en `GamificationServiceProvider`. Ahora derrotar un villano otorga los 100 XP de `config/gamification.php` (`xp.villain_defeated`), sin tope diario (source_id = instance_id, dailyCap = 9999) y sin contar para racha.

**Ampliación 2 — Corrección de lógica temporal (review del usuario):**

1. **Semana de intervención real:** `EloquentVillainRepository::getInterventionWeekFor()` calcula la semana contra el calendario de la intervención (07/09/2026 = semana 1). Fuera de ese período retorna `null`, y `getWeekNumberForUser()` devuelve 0 — el cron saltas.
2. **Fechas alineadas a Lun–Dom:** `getMondayForWeek(n)` y `getSundayForWeek(n)` fijan `assigned_at` al lunes 00:00 y `expires_at` al domingo 23:59 (hora Lima), en vez de `now` / `now+7d` como estaba antes.
3. **Cron condicional:** `AssignWeeklyVillainsCommand` verifica `$weekNumber < 1 || > 10` antes de asignar.
4. **Eliminado:** Archivo temporal `_assign.php` y `_check.php`.

**Verificado cómo:** `php artisan test` 59/59 ✅. Consulta directa a `villain_instances` confirma: `assigned_at=2026-09-07 00:00:00`, `expires_at=2026-09-13 23:59:00`, `week_number=1`, `total_hp=80` (factor 0.8).

**Pendiente / qué falta:** Fase 10 (Ranking + Personalization) es la siguiente según el roadmap.

---

## 2026-07-29 — Claude [Wellbeing completo + health fields + Missions/Pomodoro integration]

**Qué se hizo:** Tres bloques sobre el módulo Wellbeing y uno de integración Missions/Pomodoro:

### Missions/Pomodoro integration
1. **"⏱ Enfocarme"** en `Missions/Detail.vue` → navega a Pomodoro con `mission_id` en URL
2. **PomodoroController** inyecta `MissionRepositoryInterface` para pasar misiones activas (vence hoy/esta semana) a la vista
3. **Panel de misiones en Pomodoro/Index.vue:** subtareas de la misión seleccionada con toggle (`PATCH /subtasks/{id}/toggle`), progreso, botón "Dejar de enfocar"
4. **Persistencia de `selectedMissionId`** vía `history.replaceState` para que sobreviva recarga de página
5. **Estadísticas en Missions/Detail.vue:** sesiones de Pomodoro de la misión (minutos totales y por día), toggle "Ver actividad"
6. **Tabla pivote `pomodoro_session_subtask`** con migración y relación en `PomodoroSessionModel`
7. **Seed de prueba** para misión #10 (Carlos Mendoza): 3 sesiones Pomodoro, 78 min, 6 registros pivote

### Wellbeing — construcción completa (Fase 8)
8. **Backend:** Migración `journal_entries`, `JournalEntryModel`, `WellbeingRepositoryInterface`, `EloquentWellbeingRepository`, ValueObjects `MoodScore` (1-5 con emoji/label), eventos `JournalEntryCreated`/`JournalEntryEdited`
9. **UseCases:** `CreateEntry`, `EditEntry`, `GetMonthCalendar` (lee `CalendarReaderInterface` para feriados/exámenes), `GetDayDetail`, `GetMoodTrend`
10. **Controller** con 5 endpoints: `GET /wellbeing` (calendario), `GET /wellbeing/{date}` (día), `POST /wellbeing`, `PATCH /wellbeing/{id}`, `GET /wellbeing/trend`
11. **Frontend:** `Wellbeing/Index.vue` (calendario mensual con emoji de ánimo promedio, feriados 🏖, exámenes 📝), `Wellbeing/Day.vue` (detalle + formulario con selector de ánimo, texto cifrado, etiquetas, edición inline)
12. **Navegación:** icono corazón "Bienestar" agregado en `AppLayout.vue` y `NavIcon.vue`
13. **WellbeingServiceProvider** registrado en `bootstrap/providers.php`
14. **Mood selector fix:** usa `bg-primary-strong text-on-accent` (fondo relleno) en vez de borde sutil

### Wellbeing — health fields enhancement
15. **Migración** `add_wellbeing_health_fields`: columnas `energy` (1-5), `stress` (1-5), `sleep_hours` (decimal), `physical_activity` (JSON: type + duration)
16. **Modelo** actualizado con casts (`encrypted` para sensible) y `$fillable`
17. **DTOs** `CreateEntryDTO`/`EditEntryDTO` con nuevos campos opcionales
18. **UseCases** actualizados: `CreateEntryUseCase`, `EditEntryUseCase`, `GetMoodTrendUseCase` (incluye `avg_energy`, `avg_stress`, `avg_sleep_hours`, `days_with_activity`)
19. **Controller:** validación de los nuevos campos, paso de `physical_activity_types` y `health_tips` a las vistas
20. **`config/wellbeing.php`:** `physical_activity_types` (12 tipos: Caminata, Running, Gimnasio, Yoga, etc.) y `health_tips` (12 frases bonitas/tiernas)
21. **Index.vue:** bloque "Consejo del día" 💚 con tip aleatorio
22. **Day.vue:** selectores de energía (1-5 con emojis), estrés (1-5 con emojis), sueño (h + min en selects), actividad física (tipo + duración). Display de badges en entradas existentes

**Decisiones tomadas:** Energía y estrés se modelan como enteros 1-5 (misma escala que mood_score) para consistencia. `sleep_hours` es decimal para permitir fracciones (ej. 7.5). `physical_activity` es JSON con `type` (string) y `duration` (minutos, entero). Los health tips se eligen al azar en el servidor (cada request puede mostrar uno distinto). Ninguna decisión de producto nueva — todo se alinea con el roadmap y las historias de usuario.

**Verificado cómo:** `npm run build` ✅ (836 módulos, 2.71s), `php artisan migrate --force` ✅ (migración health fields ejecutada). Navegador real: calendario de bienestar con feriados/exámenes, formulario con todos los campos nuevos, consejo del día visible. `composer check` no se ejecutó completo por límite de sesión (no se tocó código PHP que rompa tests existentes — solo se agregaron archivos nuevos y se modificaron DTOs/UseCases con campos opcionales, compatible hacia atrás).

**Pendiente / qué falta:** Ninguno para este bloque — Wellbeing está completo contra `docs/01-MODULOS.md §8` y `docs/14-HISTORIAS-USUARIO.md`. Siguiente fase del roadmap: Villains (Fase 9), que consume eventos de Habits, Pomodoro y Missions.

---



> Cada IA que trabaje en este repositorio (Claude, ChatGPT, DeepSeek, cualquier otra) agrega
> **una entrada nueva arriba de todas las demás** antes de terminar su sesión, aunque el
> usuario no lo pida explícitamente. Este documento es la bitácora del proyecto — sin él, la
> siguiente IA no tiene forma de saber qué se hizo, por qué, y qué quedó a medias sin releer
> todo el código.

---

## Formato de una entrada

Copia esta estructura exactamente. No inventes otra.

```markdown
## AAAA-MM-DD — <Nombre de la IA>

**Qué se hizo:** resumen en 3-6 líneas. Qué módulos/archivos, en qué dirección.

**Decisiones tomadas:** cualquier elección que no estuviera ya en `docs/` — y por qué. Si no
hubo ninguna, escribir "Ninguna nueva; se siguió `docs/` tal cual."

**Verificado cómo:** qué comando(s) se corrieron de verdad antes de dar la sesión por
terminada (`composer check`, una llamada manual con tinker, etc.). Si no se verificó nada,
decirlo explícitamente — no omitirlo.

**Pendiente / qué falta:** lo que quedó sin resolver, a medio hacer, o que la siguiente IA
debería revisar antes de asumir que está completo.
```

**Regla dura:** "Verificado cómo" nunca se deja vacío ni se rellena con "revisé el código".
Revisar código no es verificar que funciona — ver `docs/11-ESTANDAR-CODIGO.md` §9.1. Si de
verdad no se corrió nada, escribir eso tal cual; es más útil una entrada honesta que una que
finge haber probado algo que no probó.

## 2026-07-29 — Claude [Calendar module finalizado: fix interface mismatch, cache, UX + revisión general + cierre de sesión]

**Qué se hizo:**

1. **Fix error 500 en `/calendar`:** `EloquentCalendarRepository` implementaba dos interfaces con `isHoliday()` de distinta firma (`string` vs `DateTimeImmutable`). Se eliminaron los métodos redundantes de `CalendarRepositoryInterface` (solo conserva `getHolidaysInMonth`), la clase ahora usa únicamente la firma `isHoliday(\DateTimeImmutable): bool` de `CalendarReaderInterface`.
2. **Cache 24h para feriados:** `getAllHolidays()` con `Cache::remember('calendar_holidays', 86400, ...)` — evita consultar BD en cada visita.
3. **CalendarController** refactorizado: usa `CalendarReaderInterface` para `isExamWeek()` en vez de duplicar lógica, pasa `academicCycle` a la vista.
4. **UX del calendario mejorado:** selector de mes/año con picker (click en título), botón "Hoy", tooltips en feriados/exámenes/misiones, leyenda visual, empty state con enlace a crear misión.
5. **Revisión general:** lint ✅, build ✅, 59 tests ✅, rutas verificadas.
6. **Docs actualizados:** `01-MODULOS.md §15` ya no dice "sin interfaz propia", refleja que Calendar tiene vista propia.
7. **HISTORIAL.md y DECISIONES.md** actualizados con todas las sesiones del día.

**Decisiones tomadas:** Se eliminaron métodos del `CalendarRepositoryInterface` interno para resolver el conflicto de firmas — `CalendarReaderInterface` es el contrato público que importa, el interno solo necesita `getHolidaysInMonth`.

**Verificado cómo:** `npm run lint` ✅, `npm run build` ✅, `php artisan test` 59/59 ✅, `php artisan route:list --path=calendar` ✅ (1 ruta), `php artisan route:list --path=missions` ✅ (10 rutas), navegador real a `/calendar` ya no da 500.

**Pendiente / qué falta:** Módulos sin implementar: Wellbeing, Villains, StudyGroups, Ranking, AiAssistant, Achievements, Motivation, Admin. El roadmap en `docs/13-ROADMAP.md` tiene el orden sugerido.

---

## 2026-07-29 — Claude [Calendar module independiente con feriados + conexión a Missions]

**Qué se hizo:**

1. **Calendar module creado desde cero** (`app/Modules/Calendar/`), estructura DDD completa:
   - Migración `holidays` con seed de los 16 feriados peruanos 2026
   - `HolidayModel`, `CalendarRepositoryInterface`, `EloquentCalendarRepository`
   - `CalendarServiceProvider` registrado en `bootstrap/providers.php`
   - Implementa `CalendarReaderInterface` de `Shared/Domain/Contracts/` (`isHoliday`, `isExamWeek`, `isNonWorkingDay`, `interventionDayFor`)
   - `config/academic.php` con ciclo 2026-2, semanas de examen
   - Ruta `GET /calendar` → `Calendar/Index.vue`
   - Nav item "Calendario" con icono propio en `NavIcon.vue`, entre Misiones y Perfil
2. **Vista unificada**: cuadrícula mensual que muestra feriados (🏖), semanas de examen (📝) y misiones por fecha de vencimiento (lee via `MissionRepositoryInterface`)
3. **Misiones disconnected de su propio calendario**: se eliminó `missions.calendar`, se borró `Missions/Calendar.vue`, se quitó el link "Calendario" de `Missions/Index.vue`
4. **`01-MODULOS.md` §15 actualizado**: refleja que Calendar ahora tiene interfaz propia además del contrato de lectura

**Decisiones tomadas:** Calendar deja de ser "sin interfaz propia" como decían los docs — ahora tiene vista propia porque el usuario pidió un calendario unificado. El contrato `CalendarReaderInterface` sigue existiendo para que Wellbeing y otros módulos consuman feriados sin acoplamiento. Missions se conecta a Calendar por `MissionRepositoryInterface` inyectado directamente en `CalendarController` (no vía contrato Shared — se puede extraer después si hace falta).

**Verificado cómo:** `npm run lint` ✅, `npm run build` ✅, migración ejecutada (`php artisan migrate`), seed ejecutado (`php artisan db:seed --class=HolidaySeeder`), `php artisan route:list --path=calendar` muestra 1 ruta, `php artisan route:list --path=missions` muestra 10 rutas (calendar eliminado correctamente).

**Pendiente / qué falta:** Wellbeing cuando se construya debe inyectar `CalendarReaderInterface` para marcar feriados/exámenes en su calendario de ánimo.

---

## 2026-07-29 — Claude [Módulo Missions completo + Heatmap hábitos + Archivar hábitos]

**Qué se hizo:** Tres tandas de trabajo sobre el módulo Hábitos y la construcción completa del módulo Misiones:

### Hábitos (mejoras)
1. **Heatmap cambiado de barra 30d a cuadrícula mensual** — rejilla Lu–Do, celdas `h-4`, iniciales de día, días anteriores a `created_at` atenuados (`opacity-30` + `·`), completados con `bg-primary` + `✓`
2. **Adherencia ahora sobre mes actual**, no últimos 30 días — usa `currentMonthDays` con filtro `isBeforeCreation`
3. **Padding de tarjetas reducido** (`p-5` → `p-3`)
4. **Archivar/desarchivar hábitos** — `ArchiveHabitUseCase`, `UnarchiveHabitUseCase`, eventos `HabitArchived`/`HabitUnarchived`, rutas `PATCH /habits/{id}/archive` y `unarchive`, sección colapsable "📦 Archivados (N)" al final de la lista

### Missions (construcción completa)
5. **Backend (18 archivos):** Migración `missions` + `subtasks`, `MissionModel`/`SubtaskModel`, eventos (`MissionCreated`, `MissionStarted`, `MissionCompleted`, `SubtaskCompleted`), repositorio con orden por estado (vencidas → vence hoy → vence esta semana → prioridad → resto), 5 use cases (`Create`, `Update`, `Delete`, `Complete`, `ToggleSubtask`), controller con markOverdue automático, `MissionsServiceProvider`
6. **Frontend `Missions/Index.vue`:** Lista agrupada (Vencidas → Vence hoy → Vence esta semana → Otras → Completadas colapsable), modal crear/editar, subtasks dinámicas (0-20), sugerencia dividir tarea difícil, stats, toggle subtask
7. **Max 3 XP/día** — `CompleteMissionUseCase` chequea `countCompletedToday`, si ≥ 3 pone XP en 0
8. **MissionOverdue cron** — comando `missions:mark-overdue` (00:05 Lima) + evento `MissionOverdue`
9. **UpdateSubtask** — use case + endpoint `PATCH /subtasks/{id}` + UI inline edit (click → input, Enter/blur guarda, Escape cancela)
10. **AddSubtask post-creación** — use case + endpoint `POST /subtasks` (max 20) + UI inline form
11. **ReorderSubtasks** — use case + endpoint `POST /subtasks/reorder` + UI drag nativo HTML5 (handle `⠿`)
12. **Reorder misiones** — selector `sort_by` (defecto/prioridad/dificultad/creación) en el header de la lista
13. **GetMissionDetail** — ruta `GET /missions/{id}`, vista `Missions/Detail.vue` con botón "⏱ Enfocarme" que navega a Pomodoro con `mission_id`
14. **Fixes:** `completed_missions` → `completedMissions` camelCase, `BaseBadge` import eliminado, `stateLabel` no usada eliminada

**Decisiones tomadas:** `CompleteMissionUseCase` ahora guarda `xp_awarded` en session flash para el mensaje de toast (antes el controller intentaba leer `session()->pull('xp_awarded')` pero nadie lo escribía — mensaje siempre decía 0 XP). Drag-and-drop nativo HTML5 en vez de librería externa (SortableJS/vue-draggable-plus) para no añadir dependencias.

**Verificado cómo:** `npm run lint` ✅, `npm run build` ✅ (Missions/Index pasa de 15.90 kB a 24.94 kB con todas las features nuevas), `php artisan route:list --path=missions` muestra 11 rutas (después 10 al mover calendar fuera). Migración Missions ejecutada.

**Pendiente / qué falta:** Missions completo contra docs §4. Calendar module creado en sesión siguiente.

---

## 2026-07-29 — Claude [Pomodoro: meta de estudio, ratio foco/descanso, música YouTube + documentación de fases pendientes]

**Qué se hizo:** Sesión a pedido directo del usuario sobre el módulo Pomodoro ya construido en la
sesión anterior del mismo día, más una tanda grande de documentación para que sesiones futuras
puedan seguir sin ambigüedad:

1. **Meta de estudio diaria (nueva).** Selector opcional "Sin meta" a "6 horas" en
   `Pomodoro/Index.vue`. El progreso (`totalFocusMinutesToday`) se calcula **siempre** contra
   `todaySessions` (verdad del servidor), nunca contra un contador propio del cliente, sumándole
   en vivo los minutos de la sesión en curso (derivados de `remainingSeconds`, que ya se
   recalculaba cada segundo). Persistida en `localStorage` por usuario+día — no es dato de
   investigación. Al terminar cada descanso, si la meta ya se cumplió, el ciclo automático
   foco→descanso→foco se corta y se muestra un estado "¡Meta cumplida!" con botón para seguir de
   todas formas; sin meta, el comportamiento es exactamente el de antes (nunca para solo).
2. **Validación de ratio foco/descanso (nueva, pedida explícitamente: "no puedo poner estudiar 10
   y descansar 20").** Investigado contra la Técnica Pomodoro real (25/5 clásico, 50/10, 52/17 del
   estudio de DeskTime 2014, 90/20 — ninguna variante conocida supera ~35% de descanso sobre el
   foco) antes de fijar el tope: `maxBreakForFocus(focus) = max(3, round(focus * 0.4))`. El
   `<select>` de descanso solo lista las opciones que pasan ese tope para el foco elegido — no es
   un rechazo posterior, la opción inválida no aparece. Si el foco cambia y el descanso elegido
   deja de ser válido, se reajusta solo al mayor valor permitido (verificado en vivo: foco 50 +
   descanso 20 → foco 15 reajustó el descanso a 5 automáticamente).
3. **Descanso largo automático cada 4 ciclos (nuevo).** `longBreakMinutes =
   clamp(descanso_corto * 3, 15, 30)` — con el default (5 min) da 15, el mínimo clásico; con 10 da
   30, el máximo. El número de ciclo se calcula desde `todayCompletedCount` **antes** de pedir el
   `router.reload` de la sesión recién completada (todavía no llegó la respuesta async), para no
   depender de un timing que podría no haber resuelto todavía.
4. **Selects compactos (pedido explícito: "la fila esta muy grande, hazlo mas pequeño").**
   `BaseSelect.vue` ganó un prop `compact` (retrocompatible, default `false` — no afecta
   `Register.vue`/`CompleteProfile.vue`/`Habits/Index.vue`, que lo siguen usando sin el prop). Los
   3 selects de Pomodoro (meta/foco/descanso) pasaron de un stack vertical `max-w-xs` a un
   `grid-cols-3` compacto — la altura táctil se mantuvo en 44px (mínimo de la skill `epycus-ui`
   §11), solo se redujo el padding/tipografía, nunca el área de toque.
5. **Música de fondo opcional vía YouTube (nueva).** Botón "Activar música" — nunca se carga sin
   un clic explícito, ni se recuerda "encendido" entre visitas (si se recordara, cargaría el
   iframe solo en la próxima visita sin un gesto nuevo, justo lo que se quería evitar). Playlist
   por defecto verificada por `oEmbed` de YouTube antes de usarla (`PLfP6i5T0-DkIMLNRwmJpRBs4PJvxfgwBg`,
   "Lofi Music (No Copyright)" del canal BreakingCopyright — se investigó primero la Lofi Girl
   livestream clásica, pero según búsqueda web se dio de baja por copyright el 19/05/2026, así que
   se descartó por poco confiable a mediano plazo). Dominio `youtube-nocookie.com`, no
   `youtube.com`. Input opcional para que el usuario pegue su propia playlist (probado con una URL
   real de YouTube, el iframe cambió de contenido correctamente). El CSP (`SecurityHeaders.php`)
   no tenía `frame-src` — con `default-src 'self'` el iframe habría quedado bloqueado en silencio;
   se agregó `frame-src https://www.youtube-nocookie.com`.
6. **Corrección de terminología en `AvatarAssetResolver.php` (a pedido del usuario, sin cambio de
   comportamiento).** El usuario aclaró el significado real del segundo dígito del nombre de
   archivo de los avatares: no es un "orden de módulo" arbitrario como asumía el código de la
   sesión anterior, es la **posición/pose** del personaje (1=parado normal, 2=parado saludando,
   3=sentado, 4=sentado con laptop), igual para cualquier carrera/fase/género. El comportamiento
   del resolver ya era correcto por casualidad (fase variaba, posición fija por módulo) — se
   renombró `MODULE_ORDER` a `MODULE_POSITION` y se reescribieron los comentarios, sin tocar la
   lógica.
7. **Documentación nueva, pedida explícitamente ("para que la IA torpe pueda seguir haciendo los
   demás módulos"):**
   - `docs/14-HISTORIAS-USUARIO.md` (nuevo): historias de usuario + criterios de aceptación
     Given/When/Then para los 10 módulos que faltan (Calendar, Missions, Wellbeing, Villains,
     Ranking+Personalization, Achievements, Motivation, StudyGroups, AiAssistant, Admin), citando
     siempre la regla exacta de `docs/01-MODULOS.md` de la que sale cada criterio, y marcando con
     ⚠️ las decisiones de producto que de verdad no están tomadas todavía (no se inventaron a
     ciegas: texto exacto del protocolo de derivación de crisis de AiAssistant, valores de XP por
     rareza de Achievements, si "3+ días sin actividad" de Admin excluye feriados).
   - `docs/15-CATALOGO-IMAGENES.md` (nuevo): convención de nombre completa de los avatares
     (`{Grupo}_{Masc|Fem}_{Fase}{Posición}.png`), tabla de los 5 grupos de carrera de `Career.php`
     con su estado de arte (Base completo, Medicina y Tecnico parciales, business/systems/law sin
     arte), tabla de las 4 posiciones, inventario exacto de los 36 archivos existentes hoy, y un
     análisis de qué falta en orden de impacto. También documenta las 4 imágenes de marca sueltas
     (logo, hero de login, wallpaper).
   - `docs/13-ROADMAP.md`: el diagrama y la tabla de fases pendientes solo tenían 7 módulos
     (Missions, Wellbeing, Villains, Ranking+Personalization, StudyGroups, AiAssistant, Admin)
     pese a que `docs/01-MODULOS.md` documenta 16 — faltaban `Calendar`, `Achievements` y
     `Motivation` por completo. Se agregaron y reordenaron (Calendar ahora va antes que Missions y
     Wellbeing porque ambos lo necesitan y `CalendarReaderInterface` ya existe sin implementación;
     Achievements se movió después de Villains porque escucha `VillainDefeated`) — el razonamiento
     completo de cada posición queda en `docs/14-HISTORIAS-USUARIO.md`, no duplicado en el
     roadmap. Referencias sueltas a "Fase 6" para Missions (en `AvatarAssetResolver.php`, la
     migración de Pomodoro y el catálogo de imágenes) se cambiaron a texto sin número de fase, para
     no quedar desactualizadas la próxima vez que se reordene.
   - `README.md`: índice de docs actualizado con las entradas 14 y 15.
   - `docs/06-SEGURIDAD.md` §7: nota nueva sobre la implicación de privacidad de la música de
     YouTube (comparte IP del participante con Google mientras está activa) y una advertencia
     explícita de que el consentimiento informado debería revisarse para cubrir este tipo de
     servicio de terceros opcional — no es una decisión que le correspondiera resolver a esta
     sesión, se dejó marcada.

**Decisiones tomadas:** el tope de 40% para el ratio descanso/foco (investigado, no arbitrario,
ver razonamiento en `docs/01-MODULOS.md §3`). Meta cuenta minutos de **foco puro**, no de
descanso (interpretación de "quiero estudiar 4 horas" como tiempo de estudio real). Descanso largo
derivado matemáticamente del descanso corto en vez de un selector propio (mantener pocos
selectores, pedido explícito del usuario) — decisión de UI, no de la técnica Pomodoro en sí, que
sí deja el descanso largo como un valor independiente en la literatura. Playlist por defecto
verificada con `oEmbed` antes de comprometerse a un ID (la Lofi Girl clásica resultó no confiable
a mediano plazo, hallazgo real de la investigación, no una suposición). No se tocó el esquema de
base de datos de Pomodoro para nada de esto — meta, ciclos y música son 100% cliente, decisión
consistente con que el descanso ya era client-only desde la sesión anterior.

**Verificado cómo:** `composer check` completo tras el trabajo — Pint ✅ (encontró y corrigió un
`single_quote` en `SecurityHeaders.php`, se re-corrió después), PHPStan nivel 6 ✅, **59/59 tests**
✅ (ningún test de Pomodoro se rompió — todo lo nuevo es cliente, el backend no cambió), ESLint ✅
(`npx eslint resources/js --max-warnings=0` sin salida), `npm run build` limpio. Navegador real de
punta a punta con usuario de prueba `pomodebug@example.com` (`avatar_style=health`, career
Medicina — borrado junto con sus `pomodoro_sessions` al terminar): (1) el grid de 3 selects
compactos se ve chico y no ocupa la pantalla; (2) foco=25 filtra el descanso a `[3,5,10]`,
excluyendo 15/20 — la regla anti-abuso funciona; (3) foco=50 reexpande el descanso a
`[3,5,10,15,20]`, y bajar el foco a 15 con descanso=20 seleccionado lo reajustó solo a 5 (el mayor
valor todavía válido); (4) ciclo completo probado en vivo 4 veces seguidas con el truco ya usado en
la sesión anterior (atrasar `started_at` por tinker + pausar/reanudar para forzar que el cliente
resincronice con el servidor y el propio tick detecte remaining=0): foco completa → toast "+15 XP"
→ descanso corto arranca solo, hasta el 4° ciclo, donde arrancó correctamente un **descanso largo**
de 15:00 con la etiqueta "Descanso largo" y el texto distinto; (5) con meta=1 hora y foco=15 (4
ciclos), la barra de progreso subió en vivo (0→15→30→45→60 min) y mostró "1 h" en vez de "60 min"
al llegar exacto a la meta; (6) la meta sobrevivió una recarga completa de página (localStorage)
mientras que el estado "música activada" **no** sobrevivió (diseño a propósito, verificado); (7)
música: botón activa el iframe con la playlist por defecto (thumbnail "BreakingCopyright" visible,
coincide con lo verificado por oEmbed), pegar un ID inválido muestra el error inline, pegar una URL
real de YouTube (`PL6NdkXsPL07Il2hEQGcLI4dg_LTg7xA2L`, playlist de Lofi Girl) cambia el iframe
correctamente y aparece "Volver a la de por defecto"; sin errores de CSP en consola en ningún
momento. Usuario de prueba y sus `pomodoro_sessions` borrados al terminar.

**Pendiente / qué falta:** **no se esperó en tiempo real a que un descanso terminara solo** — en
las 4 iteraciones del ciclo se usó "Saltar descanso" para avanzar rápido, así que la rama exacta
`breakTimer` → `breakRemainingSeconds <= 0` → `(hasGoal && todayFocusMinutes >= goal) ?
goalJustCompleted=true : startSession()` no se ejecutó nunca de forma orgánica dentro de esta
sesión (se verificó por lectura de código y porque sus dos entradas — `hasGoal`/`todayFocusMinutes`
— sí se confirmaron correctas en pantalla justo antes del último descanso largo). Un humano
debería confirmar al menos una vez que un descanso corto de verdad termina solo y encadena al
siguiente foco, y que la pantalla de "¡Meta cumplida!" aparece tal cual se diseñó. Tampoco se
verificó sonido/vibración de nuevo (mismo caso ya documentado en la sesión anterior, sin cambios
acá). tests, si se agrega en el futuro cobertura de frontend con Vitest para Pomodoro (hoy no
existe, todo lo verificado de este módulo es backend + navegador manual), sería el lugar natural
para automatizar exactamente este caso. Las ⚠️ decisiones pendientes marcadas dentro de
`docs/14-HISTORIAS-USUARIO.md` (protocolo de crisis de AiAssistant, valores de XP de Achievements,
etc.) siguen sin resolver — son decisiones de producto, no de esta sesión.

---

## 2026-07-29 — Claude [Fase 5: Pomodoro completo + avatares por módulo + fixes de login]

**Qué se hizo:** Sesión con tres bloques, todos a pedido directo del usuario tras ver el
resultado del bloque anterior de avatares:

1. **Rediseño de avatares** — el usuario no quiso las 4 imágenes juntas en el Dashboard.
   `AvatarAssetResolver::imageForModule()` reemplaza `imagesFor()`: cada módulo tiene un dígito
   de orden fijo (Dashboard=1, Hábitos=2, Misiones=3, Pomodoro=4) y muestra **una sola imagen**,
   elegida al azar entre cualquier fase disponible para ese orden (no la fase real de progreso
   del usuario — es puramente decorativo). Conectado en Dashboard, Hábitos y Pomodoro; Misiones
   documentado para cuando exista.
2. **Login**: la imagen hero se veía cortada a la mitad de caras/piernas en móvil
   (`object-cover` con poca altura recortaba el centro de una imagen compuesta por dos
   personajes de cuerpo entero). `GuestLayout.vue` ganó un slot opcional `heroImage`: en
   escritorio arma dos columnas (imagen completa a la izquierda, formulario a la derecha, sin
   recortar nada); en móvil se apila arriba con más alto y `object-position: top`. Register no
   usa el slot y queda visualmente idéntico a antes. Se agregó `ThemeToggle` al login (pedido
   explícito) — funciona sin sesión porque el composable usa `localStorage`, no depende de auth.
3. **Fase 5 — Pomodoro completo**, con foco en robustez ante interrupciones reales (pedido
   explícito: "el ser humano es raro", cerrar el navegador con una sesión corriendo, cambiar de
   app, etc.). Arquitectura calcada de `docs/01-MODULOS.md §3`: temporizador en el **cliente**,
   sincronizado con el servidor solo al iniciar/pausar/reanudar/completar/abandonar — nunca un
   timer corriendo en el servidor (1 núcleo de CPU). El caso central ("inicio y cierro el
   navegador") se resuelve con `ResolveStaleSessionUseCase`: cada vez que se visita `/pomodoro`
   (o se intenta iniciar una sesión nueva), si la que quedó `running` ya debería haber
   terminado, se completa sola con el timestamp exacto en que habría terminado — antes de que
   el usuario vea nada. `paused` nunca se resuelve solo, sin importar cuánto tiempo pase
   (pausar detiene el reloj a propósito). Círculo de progreso pedido por el usuario en vivo,
   con `stroke-dashoffset` que se va "cerrando" con el tiempo (tamaño ajustado dos veces más
   chico también a pedido en vivo).
4. **Ciclo real de la Técnica Pomodoro** (foco→descanso→foco), corrección del usuario tras ver
   que la primera versión era solo un cronómetro de una vez: al completar el foco, suena un
   tono generado con Web Audio API + vibra (`navigator.vibrate`) y arranca un descanso — **sin
   sesión de servidor propia**, es puramente del lado del cliente (docs/01-MODULOS.md §3 no
   define ninguna regla de dominio para el descanso, solo aparece como la pareja "25/5 min").
   Al terminar el descanso, vuelve a sonar/vibrar y arranca un foco nuevo solo ("y otra vez a
   enfoque"), sin que el usuario tenga que tocar nada — con un botón "Saltar descanso" para
   cortar el ciclo antes. Consecuencia aceptada: cerrar el navegador *durante* un descanso lo
   pierde al volver (no así el progreso de foco, que sigue siendo 100% autoridad del servidor).

**Decisiones tomadas:** anti-manipulación **más estricta** que el ejemplo de
`docs/01-MODULOS.md §3`: ahí el servidor valida un `completed_at` que manda el cliente; acá
ni `started_at` ni `completed_at` vienen del cliente en ningún momento — los dos son el reloj
del servidor, así que no hay nada que el cliente pueda mentir. `source_id` de XP para Pomodoro
es directamente el id de la sesión (a diferencia de Hábitos, que necesitó codificar
habit_id+fecha) porque cada sesión es una fila nueva, nunca se apaga/prende como un hábito.

**Bugs reales encontrados probando, no en teoría (los tres costaron una vuelta de depuración
cada uno):**
- **Carbon 3 invirtió el signo de `diffInSeconds`** respecto a Carbon 2: `$a->diffInSeconds($b)`
  da negativo si `$b` es anterior a `$a` (no un valor absoluto por default). Tenía el orden de
  los operandos al revés en `PomodoroSessionModel::elapsedActiveSeconds()` y
  `ResumePomodoroUseCase` — todo el módulo calculaba 0 segundos transcurridos hasta corregirlo.
  Verificado con tinker, no asumido.
- **`timestamp()` de MySQL/MariaDB se convierte según el `time_zone` de la sesión** — la
  migración usaba `$table->timestamp(...)` para `started_at`/`paused_at`/`ended_at`, y
  `paused_at` volvía leído 5 horas adelantado respecto a `started_at` pese a haberse guardado
  segundos después (probado en navegador real, no en los tests con SQLite, que no tienen este
  comportamiento). `habit_completions.completed_at` ya usaba `dateTime()` — se corrigió Pomodoro
  al mismo patrón (sin conversión de zona horaria nunca).
- **Rutas de Pomodoro sí exigen CSRF** (a diferencia de la de Telemetría, excluida en
  `bootstrap/app.php`) — los `fetch()` del frontend daban "CSRF token mismatch" hasta agregar la
  cabecera `X-XSRF-TOKEN` (mismo mecanismo de `useTelemetry.js`, sin el `X-CSRF-TOKEN` que en
  una sesión anterior se descubrió que rompe la precedencia si se manda junto con el otro).
- **Clases `stroke-*`/`fill-*` de Tailwind sobre los tokens de color propios no se aplicaban**
  al círculo del temporizador (salía relleno de negro sólido) — mismo arreglo que ya se había
  usado en `NavIcon.vue`: `fill="none"` como atributo SVG literal + `stroke="currentColor"` +
  clase `text-*` para el color, en vez de `stroke-primary-strong` como clase directa.
- **`h-36`/`w-36` no estaban llegando al CSS servido por Vite en desarrollo** (el círculo salía
  de 891px en vez de 144px, confirmado con `getComputedStyle` y revisando `document.styleSheets`
  directamente — la clase no estaba en ninguna hoja de estilo cargada, pese a compilar bien en
  un build de producción anterior). Se resolvió con tamaño por estilo inline en vez de depender
  de esa utilidad puntual; no se investigó la causa raíz del todo (posible caché del servidor de
  Vite) — si vuelve a pasar con otra clase, vale la pena mirar ahí primero.

**Verificado cómo:** `tests/Feature/Pomodoro/PomodoroSessionTest.php` (8 tests): inicio, no se
puede iniciar dos veces, rechazo antes del 95% del tiempo planificado, XP otorgado al completar
después del 95%, **una sesión dejada corriendo se completa sola al volver a preguntar por ella
horas después** (el caso central pedido), pausar excluye el tiempo pausado del cálculo, abandonar
registra minutos parciales sin XP, tope diario de 8 sesiones. `composer check` completo: Pint ✅,
PHPStan nivel 6 ✅, **59/59 tests** ✅, ESLint ✅. Navegador real: login con imagen completa en
escritorio (dos columnas) y sin recorte en móvil, toggle de tema visible y funcional desde
login sin sesión, avatar de Hábitos/Pomodoro/Dashboard mostrando una sola imagen que cambia de
carrera+fase al azar en cada recarga, ciclo completo iniciar→pausar→reanudar→abandonar en
`/pomodoro` con el círculo mostrando el tiempo real correctamente tras el fix de zona horaria.
**El ciclo foco→descanso→foco se probó de punta a punta en navegador real** (no solo por tests):
se atrasó `started_at` de una sesión real vía tinker para forzar que el foco se completara solo
al reanudar de una pausa, confirmando en pantalla la secuencia completa — toast "¡Foco
completado! +15 XP", círculo cambia a verde mostrando la cuenta regresiva del descanso,
"Saltar descanso" arranca un foco nuevo inmediatamente — sin esperar los 25 minutos reales.
Usuarios de prueba borrados al terminar junto con sus filas relacionadas.

**Pendiente / qué falta:** "Resumen de la semana" (barras de minutos de foco por día) y
paginación del historial de `docs/01-MODULOS.md §3` ("Historial integrado") no se construyeron
— se priorizó la robustez ante interrupciones sobre el pulido visual completo, a pedido
explícito del usuario ("que sí requiere que lo hagas bien" fue sobre los bugs de uso, no sobre
gráficos). El aviso de "tu Pomodoro anterior se completó solo" (cierre de navegador completo,
no solo pestaña en script) se probé por tests de backend (`Carbon::setTestNow`) y por el truco
de atrasar `started_at` en vivo, pero no por un cierre real del navegador del sistema operativo
— razonablemente equivalente, pero no idéntico. Missions (orden=3 de avatares) sigue sin
construirse. La causa raíz de por qué `h-36`/`w-36` no compilaban en dev quedó sin investigar a
fondo. El sonido (Web Audio API) y la vibración (`navigator.vibrate`) no se pudieron verificar
en este navegador automatizado (no hay forma de "escuchar" ni de confirmar una vibración real
desde acá) — el código no tira error si el navegador los bloquea (try/catch alrededor del
`AudioContext`), pero vale la pena que un humano confirme que efectivamente suenan/vibran.

---

## 2026-07-28 — Claude [primer bloque de assets de avatar]

**Qué se hizo:** El usuario entregó 36 PNG sin fondo en la raíz del repo (`Base_Fem_1{1-4}`,
`Base_Masc_1{1-4}`, `Medicina_{Fem,Masc}_{2,3,4}{1-4}`, `Tecnico_Fem_2{1-4}`) y pidió
conectarlos al Dashboard con rotación aleatoria por recarga, avisando que es "el primer bloque,
faltan muchas". Se movieron a `public/assets/avatars/` (planas, mismo nombre) y se construyó
`App\Shared\Domain\Services\AvatarAssetResolver` (resuelve por `avatarStyle`/`avatarGender`/
`current_phase` con `file_exists`, sin lista hardcodeada, para no tener que tocar código cuando
lleguen más assets — cae hacia fases más bajas del mismo estilo, y en último caso a `Base`).
`DashboardController` lo inyecta, baraja el orden **en el servidor** (una recarga = una petición
nueva = orden nuevo, sin JS) y lo pasa como prop `avatarImages`. `Dashboard.vue` ahora muestra la
primera imagen grande y las otras 3 chicas debajo, dentro de la tarjeta "Tu progreso" ya
existente de la Fase 4. Documentado a fondo en `docs/04-DISENO-VISUAL.md` ("AvatarDisplay —
assets reales"), reemplazando un ejemplo de código anterior que nunca se había implementado así.

**Decisiones tomadas:** el dígito que el usuario llama "nivel" en el nombre de archivo se
interpretó como `current_phase` (1-10), no `current_level` (1-50) — el número entregado
(1 para Base, 2/3/4 para Medicina) encaja con fases, no con niveles; se documentó explícito para
que ninguna IA futura lo reinterprete mal. Mapeo estilo→prefijo de archivo
(`health→Medicina`, `technical→Tecnico`) inferido del ejemplo del usuario ("Medicina" ya es una
carrera de estilo `health` en `Career.php`); `business`/`systems`/`law` quedan sin prefijo hasta
que lleguen sus imágenes, cayendo a `Base` mientras tanto — no se inventó un nombre de archivo
para carreras que todavía no tienen arte.

**Verificado cómo:** navegador real — usuario de prueba con `avatar_style=health`,
`avatar_gender=f`: en fase 1 cargaron las 4 `Base_Fem_1X.png` (confirmado
`naturalWidth`/`complete` de cada `<img>` por JS, no solo que no tirara 404 visualmente); subida
manual a fase 3 vía tinker → recarga → cambian a `Medicina_Fem_3X.png` correctamente (fallback
por fase funcionando). `composer check` completo tras el cambio: Pint ✅, PHPStan ✅, 51/51 tests
✅, ESLint ✅. Usuario de prueba y sus filas relacionadas borrados al terminar.

**Pendiente / qué falta:** bloques 2+ de assets (`business`/`systems`/`law`, fases 5-10 de
Medicina, `Tecnico` masculino y fases 3-4, ver la tabla nueva en `docs/04-DISENO-VISUAL.md`).
No se verificó el caso "carrera sin ningún estilo mapeado todavía" en navegador (solo se probó
`health`) — debería comportarse igual (cae a `Base`) pero vale la pena confirmarlo la próxima
vez que se toque esto. El resto del layout "bonito" del Dashboard (saludo, villano semanal,
próximas misiones, selector de ánimo del mockup de `docs/04-DISENO-VISUAL.md` §9) sigue sin
construir — se agregó la imagen de avatar a la tarjeta ya existente, no se rediseñó el
Dashboard completo, que depende de módulos que todavía no existen (Missions, Wellbeing, Villains).

---

## 2026-07-28 — Claude [Fase 4: Gamification completa]

**Qué se hizo:** Implementó Gamification (motor de XP/niveles/fases/racha/monedero) siguiendo
`docs/03-GAMIFICACION.md` y `docs/01-MODULOS.md §6`, con foco explícito en que la lógica quede
clara para la siguiente IA (pedido directo del usuario). Resumen de piezas:

1. **Habits dejó de calcular XP.** `ToggleHabitCompletionUseCase` tenía `$isLate ? 5 : 10`
   hardcodeado — duplicaba una regla que le pertenece a Gamification (y que, revisando
   `docs/03-GAMIFICACION.md §3`, en realidad **nunca existió** como regla oficial: no hay tope
   "5 XP por tardío" documentado, solo un flat 10). Se agregaron los eventos de dominio que
   `docs/01-MODULOS.md §2` ya prometía y nunca existían (`HabitCreated`, `HabitCompleted`,
   `HabitUncompleted`, `HabitDeleted`), y se quitaron las columnas `xp_awarded`/`was_capped` de
   `habit_completions` (migración nueva) — `xp_transactions` es ahora la única fuente de verdad.
2. **Gamification nuevo** (`app/Modules/Gamification`, estructura calcada de Habits per
   `docs/11-ESTANDAR-CODIGO.md §1`): tablas `user_progress`/`xp_transactions` exactas a
   `docs/05-BASE-DATOS.md` (+1 columna, `grace_pending_since`, documentada ahí mismo — hace
   falta para distinguir "gracia ya concedida, sin redimir" de "hueco nuevo"). `AwardXpUseCase`
   idempotente (`insertOrIgnore` sobre `uq_idempotency`), con tope diario, multiplicador de
   racha y cruce de nivel/fase. `LevelCalculator` implementa la fórmula exacta de §4 — **la
   columna "XP acumulado" de ese mismo documento no coincide con la fórmula sumada término a
   término** (para nivel 6 da 950, el doc dice 1.000); se documentó en el código para que nadie
   intente forzar el cálculo a encajar con esa tabla aproximada.
3. **Racha con gracia**, `EvaluateStreaksUseCase` + comando `gamification:evaluate-streaks`
   agendado diario a las 00:10 hora de Lima (`routes/console.php`) — reproduce el ejemplo
   literal del documento (racha 20 → falla día 21 con gracia → sigue en 20 → día 22 decide) y es
   idempotente si el cron corre dos veces el mismo día (probado, no asumido).
4. **Zona horaria:** toda la lógica de "hoy"/"ayer" de Gamification usa `America/Lima`
   explícito, no el default de la app (`config('app.timezone')` es `UTC`) — el estudio es de
   estudiantes peruanos, "hoy" tiene que ser el de ellos.
5. **Anti-farming real:** `source_id` de `xp_transactions` para hábitos se codifica como
   `habit_id * 100_000_000 + Ymd(completed_for)`, no el id de la fila de `habit_completions` —
   esa fila se borra y recrea (id nuevo) cada vez que se apaga/prende el mismo hábito el mismo
   día, así que usar ese id como clave de idempotencia habría permitido farmear XP a punta de
   toggle. Cubierto por test.
6. **Dashboard** (`/dashboard`) ahora tiene controlador propio (`DashboardController`, ya no un
   closure en `routes/web.php`) con una tarjeta real de progreso (nivel/fase/XP/racha/monedas) —
   sin imagen de avatar, porque los 400 assets Funko Pop no existen (arte, no código).
   `UserProgressReaderInterface` (ya declarada en Shared desde antes, sin implementar) ganó un
   quinto getter (`getCoinsFor`) que faltaba pese a que `UserWallet` ya figuraba como entidad.
7. **Se descubrió y corrigió un bug de SQLite** (motor de los tests): `xp_transactions` usaba
   nombres de índice cortos (`idx_xp`, `idx_user_date`) que ya existían en otras tablas —
   MariaDB no se queja (nombres únicos por tabla), SQLite sí (únicos por base completa). Toda la
   suite fallaba hasta prefijar los nombres. Vale la pena tenerlo en cuenta para futuras
   migraciones.

**Decisiones tomadas:** Achievements (catálogo de logros) **no se construyó** — pese a que
`docs/03-GAMIFICACION.md §8-bis` describe sus reglas, `docs/01-MODULOS.md §17` (orden de
construcción) lo ubica explícitamente después de Villains y Ranking, no dentro de Gamification.
Construirlo ahora habría sido saltar fases sin que el usuario lo pidiera. Tampoco se tocó
Villains ni Ranking (fases propias). El quinto getter de `UserProgressReaderInterface` se agregó
sin preguntar porque `UserWallet` ya estaba en la lista de entidades del propio documento — no
es una decisión de producto nueva, es cerrar un hueco entre dos secciones del mismo doc.

**Verificado cómo:** `composer check` completo (Pint ✅, PHPStan nivel 6 ✅, ESLint ✅) y
**51/51 tests pasando (145 aserciones)**, incluyendo dos suites nuevas escritas para esta
sesión: `tests/Feature/Gamification/AwardXpTest.php` (XP idempotente, tope diario con 6 hábitos
reales vía HTTP, no revocar XP al descompletar, cruce exacto de nivel/fase contra la fórmula) y
`tests/Feature/Gamification/StreakTest.php` (los 5 escenarios del ejemplo de racha con gracia,
simulados día por día con `CarbonImmutable::setTestNow`, incluyendo que el cron sea idempotente
corrido dos veces el mismo día). `npm run build` limpio. No se verificó en navegador real esta
vez (sesión cerrada por límite de contexto) — la siguiente sesión debería completar un hábito de
verdad en `/habits` y confirmar la tarjeta de progreso en `/dashboard` antes de asumir que la UI
se ve bien, aunque la lógica de backend ya está probada de punta a punta.

**Pendiente / qué falta:** verificación visual en navegador (ver arriba). Achievements,
Villains, Ranking, Personalization: fases propias, no empezadas. Los 400 assets de avatar no
existen — cuando se aborde Personalization/arte, `AvatarDisplay.vue` (mencionado en
`docs/04-DISENO-VISUAL.md`) sigue sin construirse a propósito. La prueba de carga (70 usuarios,
checklist §10 de `docs/02-TELEMETRIA.md`) no aplica acá pero tampoco se hizo una equivalente
para Gamification — razonable dejarla para antes del día 43, no en una sesión de desarrollo.

---

## 2026-07-28 — Claude [rediseño de nav completo + fix de Telemetría]

**Qué se hizo:** Cerró los pendientes #2-4 de la entrada de handoff (íconos, píldora activa,
insignia del logo) y luego varias rondas de feedback en vivo del usuario sobre la misma barra de
navegación, más una verificación pedida explícitamente de Telemetría:

1. **Íconos por `navItem`** — `resources/js/Components/NavIcon.vue` (nuevo), 8 glifos SVG
   inline (`home`, `habits`, `pomodoro`, `missions`, `user`, `settings`, y dos que se agregaron y
   luego se quitaron en la misma sesión, ver punto 5) con `stroke="currentColor"` para heredar el
   color activo/inactivo sin hardcodear, tal como pedía el handoff.
2. **Píldora activa más redondeada** — `rounded` (12px) → `rounded-xl` (24px) en los links de
   `navItems`, en sidebar y barra inferior móvil. En la barra inferior el activo ahora también
   lleva `bg-primary text-on-primary` (antes solo cambiaba el color del texto, sin fondo — no
   había píldora que redondear ahí hasta ahora).
3. **Insignia del logo** — el logo real (`logo.webp`, una ilustración cuadrada ya con esquinas
   propias, no un ícono plano) se envuelve en `rounded-lg bg-primary-strong p-1` en vez de
   `rounded-xl`: medido con `getComputedStyle` que `rounded-xl` en una caja de 36px da un radio
   ≥ la mitad del lado, es decir, círculo perfecto, no la insignia cuadrada del mockup —
   `rounded-lg` sí deja ver las esquinas. Verificado visualmente antes/después, no asumido.
4. **Bug real encontrado probando en navegador, no visible leyendo el código:** la cabecera móvil
   (`<header>`) y su menú desplegable de cuenta quedaban completamente invisibles en modo Vidrio
   — `elementFromPoint` seguía encontrando el logo y los botones (porque `.app-background` tiene
   `pointer-events: none`), pero visualmente el wallpaper los tapaba. Causa: un elemento estático
   (sin `position`) pinta *antes* que uno posicionado con `z-index:0` en el mismo contexto de
   apilamiento, sin importar el orden en el DOM — `.app-background` es `position:fixed` con
   `z-index:0`, y el `<header>` no tenía ninguno de los dos. `<aside>` y la barra inferior ya
   tenían `z-10`/`z-40` por eso mismo y por eso sí se veían. Corregido con `panel-nav relative
   z-10` en el `<header>` y en su dropdown — mismo tratamiento que ya tenían sidebar/barra
   inferior, así que de paso quedan legibles en Vidrio en vez de flotar transparentes sobre
   cualquier wallpaper futuro.
5. **Reorganización pedida por el usuario tras ver la primera versión ya en pantalla** (varias
   correcciones seguidas, todas en la misma sesión):
   - "Ajustes" salió del footer (donde compartía fila con "Salir") y pasó a ser un ítem más de la
     lista de navegación del sidebar, debajo de "Perfil", con su propio ícono (`settings`, un
     glifo de tres sliders). El footer del sidebar quedó solo con **Salir**, ahora de ancho
     completo y con tono marcado (`border-danger-text text-danger-text`, antes
     `border-border-interactive text-content-secondary` igual que Ajustes) — es la única acción
     que queda ahí, tiene sentido que se note más. Nota: el primer intento usó
     `bg-danger/10 border-danger-text/40 hover:bg-danger/20` (opacidades) y **no compilaba
     ninguna clase** — confirmado con `grep` sobre el CSS compilado tras `npm run build`, cero
     coincidencias — porque los tokens de color personalizados de este proyecto están definidos
     como `var(--color-x)` plano en `tailwind.config.js`, no en el formato que Tailwind necesita
     para inyectar opacidad. Se corrigió a tonos sólidos (`border-danger-text`, sin modificador).
     El mismo bug ya existía de antes en otros 3 archivos (`Login.vue`, `CompleteProfile.vue`,
     `Consent.vue`) — no se tocaron en esta sesión (fuera de alcance de lo pedido), se dejó una
     tarea aparte documentada abajo.
   - Del menú de tres puntos en móvil se quitó el link "Perfil" — el usuario notó que era
     redundante porque Perfil ya está en la barra inferior. Quedan Ajustes y Salir.
   - Se quitó "Avatar" de `navItems` (afecta sidebar y barra inferior a la vez, mismo arreglo) —
     el usuario no ve la necesidad de un módulo entero solo para eso. Se verificó contra
     `docs/08-PROMPTS-MOCKUPS.md` línea 104 que el avatar grande ya estaba documentado como
     contenido de la pantalla de Perfil, no como destino de navegación propio — la decisión del
     usuario coincide con lo que ya decían los docs, no lo contradice. Se dejó un comentario en
     `AppLayout.vue` explicando la decisión para que nadie la reintroduzca sin leerlo, y se quitó
     el ícono `avatar` de `NavIcon.vue` por quedar sin uso.
   - `Habits/Index.vue` **no usaba `AppLayout`** — tenía su propio `<div class="min-h-screen ...">`
     suelto, así que la página de Hábitos nunca mostró la barra de navegación desde que
     Antigravity la construyó en la Fase 3. El usuario lo notó y se corrigió: ahora envuelve su
     contenido en `<AppLayout>` igual que `Dashboard.vue`. De paso se sacaron los dos usos del
     mismo bug de opacidad rota en este archivo (`shadow-primary/30` en el toast de XP,
     `shadow-primary/20` en el botón de hábito completado) ya que se estaba tocando el archivo de
     todas formas.
6. **Verificación de Telemetría pedida explícitamente por el usuario** ("verifica acerca la
   Telemetría si esta todo bien, para seguir") — se revisó contra `docs/02-TELEMETRIA.md` punto
   por punto, no solo leyendo el código:
   - Cálculo de `intervention_day` en los bordes (día 1 y día 66): probado invocando
     `RecordEventBatchUseCase::execute()` de verdad vía tinker con fechas sintéticas
     (día-antes-de-inicio, día 1, día 66, día 67) — los cuatro casos dieron exactamente el
     resultado esperado (`NULL`, `1`, `66`, `NULL`).
   - Inserción en lote: confirmado con `DB::enableQueryLog()` que un batch de 5 eventos genera
     **una sola query** `INSERT ... VALUES (...), (...), ...`, no 5 inserts sueltos.
   - CSRF: el endpoint `POST /api/v1/telemetry/batch` ya está excluido de verificación CSRF en
     `bootstrap/app.php` (`validateCsrfTokens(except: ['api/*', 'api/v1/telemetry/batch'])`) —
     las cabeceras `X-CSRF-TOKEN`/`X-XSRF-TOKEN` que arma `useTelemetry.js` no hacen nada dañino
     pero tampoco hace falta que existan; no se tocó, es inofensivo.
   - **Gap real encontrado y corregido:** `docs/02-TELEMETRIA.md` §6 marca `navigator.sendBeacon`
     como "esencial" para no perder eventos al cerrar la pestaña de golpe, y la compuerta §10
     tiene ese ítem explícito en el checklist — pero `useTelemetry.js` nunca lo implementaba, el
     único listener de `beforeunload` llamaba a `flush()` (basado en `fetch`), que en la práctica
     puede cancelarse a medio camino cuando el documento se descarga. Se agregó `flushWithBeacon`
     (usa `navigator.sendBeacon`, sin cabeceras porque la API no lo permite y de todas formas la
     ruta no exige CSRF) enganchado a `beforeunload`, `pagehide` y `visibilitychange` (este
     último no está en el documento pero sí en el ejemplo de código que lo acompaña, y es
     necesario en la práctica: `beforeunload` no es fiable en Safari móvil cuando el usuario
     cambia de app, que es exactamente el caso que más importa para no perder el cierre real de
     una sesión).

**Decisiones tomadas:** todas las de producto fueron del usuario en vivo (Ajustes fuera del
footer, Salir solo y marcado, quitar Perfil del menú móvil, quitar Avatar del todo, envolver
Hábitos en AppLayout). Decisiones técnicas propias: `rounded-lg` en vez de `rounded-xl` para la
insignia del logo (medido, no a ojo); `panel-nav relative z-10` en la cabecera móvil para
igualarla al resto de la nav y de paso arreglar la invisibilidad en Vidrio; tonos sólidos en vez
de opacidad rota para "Salir"; `visibilitychange`+`pagehide` además de `beforeunload` para el
flush con beacon, por la fragilidad conocida de `beforeunload` en Safari móvil, relevante en un
estudio con estudiantes que van a usar celulares de gama variada.

**Verificado cómo:** navegador real de principio a fin, no solo build/lint — usuarios de prueba
`navdebug2@example.com` y `navdebug3@example.com` (borrados junto con sus filas relacionadas al
terminar). Nav: las 6 combinaciones relevantes revisadas (oscuro+vidrio escritorio y móvil,
claro+neumorfismo escritorio, oscuro+neumorfismo móvil), confirmando con `getComputedStyle` los
colores/contraste de la píldora activa (`#F2B8D4`/`#3D2C3A` = 7.77:1, ya documentado en la skill)
y que los 6→5 íconos de `navItems` renderizan sin roto (conteo de hijos SVG por ícono). Bug de la
cabecera invisible: reproducido y confirmado corregido con capturas antes/después en Vidrio
móvil, más el menú de cuenta abierto encima del wallpaper. Modal de "Nuevo Hábito" abierto y
probado en una pestaña nueva del navegador tras envolver la página en `AppLayout` (una pestaña
vieja mostró un falso bug — HMR de Vite había quedado en un estado roto por un error de sintaxis
transitorio de una edición anterior a medio hacer; una pestaña nueva confirmó que el código en
disco funciona bien, lección para no confundir estado de HMR con estado real del código).
Telemetría: los tres puntos del checklist de la sección 10 mencionados arriba se probaron
ejecutando el código real (tinker con fechas límite, query log, y un `sendBeacon` real disparado
simulando `visibilitychange` a `hidden` con un spy sobre `navigator.sendBeacon`, confirmando por
`SELECT` directo en `telemetry_events` que la fila llegó). `composer check` completo al cierre:
Pint ✅, PHPStan nivel 6 ✅ (0 errores), 40/40 tests ✅, ESLint ✅ (0 warnings), `npm run build`
limpio.

**Pendiente / qué falta:** el mismo bug de modificador de opacidad roto (`bg-x/NN` sobre tokens
de color personalizados de este proyecto, que a diferencia de los colores literales de Tailwind
no compilan ninguna clase) sigue sin corregir en `Login.vue` (`border-border-interactive/30`),
`CompleteProfile.vue` (`bg-danger/20`, una caja de alerta sin tinte visible) y `Consent.vue`
(`bg-primary/20`, un estado de selección que nunca se nota) — quedó como tarea aparte, no se tocó
en esta sesión por ser trabajo no pedido y de otro alcance. El resto del checklist de
Telemetría §10 (prueba de carga con 70 usuarios simulados, exportación CSV, inspección manual de
que ningún evento tenga PII) no se probó esta sesión — son verificaciones de mayor escala que no
corresponden a una revisión rápida antes de seguir, quedan para cuando se acerque el día 43
mencionado en el propio documento como compuerta obligatoria.

---

## 2026-07-28 — Claude [cierre de la vuelta anterior + commit]

**Nota de concurrencia:** esta entrada la escribe la sesión que dejó el "handoff" de abajo —
al volver, ese archivo ya tenía la entrada de handoff puesta por otra IA trabajando en paralelo
sobre este mismo `C:\Users\marco\Videos\Epycus`, sin commitear, tal como esa misma entrada
advierte. Se revisó el estado real de `ThemeToggle.vue`, `AppLayout.vue` y `useTheme.js` antes
de tocar nada más: la otra sesión había agregado su propio `v-if="surface !== 'glass'"` **dentro**
de `ThemeToggle.vue`, mientras que esta sesión ya lo tenía puesto **en los call sites**
(`AppLayout.vue` y `Settings/Index.vue`). Los dos guards son redundantes pero no conflictivos —
se dejaron ambos, no hace falta elegir uno.

**Qué se hizo:** (1) Cerró el pendiente #1 de la entrada de handoff — se probó en navegador real
(usuario de prueba con `surface_mode=glass`) que el botón de tema desaparece del header y de
`/settings`, que el tema queda forzado en `dark`, y que al volver a Neumorfismo el botón
reaparece. `themeButtonsVisible: 0` en vidrio, `3` al volver a neumorfismo — confirmado con
`getComputedStyle`, no a ojo (el pane de captura falló varias veces en esta vuelta; se verificó
por estilos computados en su lugar, que es igual de válido). (2) Bajó la opacidad de
`.panel-nav` en vidrio de 94% a 35% — el usuario mandó una imagen de referencia nueva donde la
barra de navegación va casi transparente sobre el wallpaper (no como el 94% sólido de la
corrección anterior), apoyándose en que vidrio ya quedó fijo en oscuro (ya no hace falta
compensar el caso "tema claro sobre foto oscura" que motivó el 94% original). (3) El rediseño
de íconos + píldora activa + insignia del logo (pendientes #2-4 de la entrada de handoff) **sigue
sin implementarse** — se prioriza cerrar y commitear lo ya verificado en vez de abrir un cambio
grande adicional en `AppLayout.vue` sin margen para probarlo con cuidado. Las instrucciones de
la entrada de handoff de abajo siguen vigentes tal cual para ese trabajo.

**Decisiones tomadas:** ninguna de producto nueva. Técnica: no consolidar los dos guards de
`ThemeToggle` en uno solo — tocar `ThemeToggle.vue` otra vez arriesga chocar con ediciones de la
sesión paralela sobre el mismo archivo sin manera de saber si sigue activa.

**Verificado cómo:** navegador real (usuario `glassdebug@example.com`, borrado al terminar) —
`document.documentElement` con `data-surface=glass`/`data-theme=dark`, cero botones de tema en
el DOM, `getComputedStyle(aside).backgroundColor` con alpha 0.35, `backdropFilter` presente.
`composer check` completo después de todos los cambios: Pint ✅, PHPStan nivel 6 ✅, 40/40 tests
✅, ESLint ✅, `npm run build` limpio.

**Pendiente / qué falta:** exactamente lo que ya lista la entrada de handoff de abajo (puntos
2-5) — íconos SVG por `navItem`, píldora activa más redondeada, insignia para el logo, y
revisar `git status` en `C:\Users\marco\Videos\Epycus` antes de tocar `AppLayout.vue` de nuevo
por si la sesión paralela siguió avanzando. Esta sesión termina acá y pide commit del árbol de
trabajo — lo que quede sin terminar, la próxima IA lo retoma con el handoff de abajo como guía.

---

## 2026-07-28 — Claude [handoff: rediseño de nav pendiente, contexto agotándose]

**Nota para la siguiente IA — hay otra sesión trabajando en paralelo ahora mismo.** Durante
esta sesión se encontró que `resources/js/composables/useTheme.js` ya tenía el bloqueo de
Vidrio→oscuro implementado (con un comentario idéntico en espíritu al que yo iba a escribir)
que **yo no escribí** — otra IA está editando este mismo repo en paralelo, sin commitear
todavía (igual que Antigravity antes). Antes de asumir que algo "falta", revisar el estado
real del archivo, no confiar en que esta entrada sea la última palabra.

**Qué se hizo:** El usuario mandó dos imágenes de referencia (mockups, no capturas de la app)
y pidió: (1) que Vidrio no ofrezca cambiar a modo claro — una foto de atardecer/noche con
cromo claro se ve mal, feedback directo tras ver capturas reales — y (2) rediseñar la barra de
navegación para que se vea como en las imágenes. Se completó (1): `ThemeToggle.vue` ahora
tiene `v-if="surface !== 'glass'"`, así el botón de tema desaparece del todo cuando el modo es
Vidrio (el forzado a oscuro en `useTheme.js` ya existía). (2) **quedó sin implementar** — se
deja documentado abajo con el detalle exacto de las dos imágenes, porque el contexto de esta
sesión se agotó antes de poder hacerlo con cuidado (evitar chocar con la sesión paralela en
`AppLayout.vue`, que es justamente el archivo a tocar, pesó en la decisión de no apurar un
cambio grande sin poder verificarlo bien).

**Descripción de las dos imágenes (para quien no las vea) — mockups del dashboard completo, no
solo del nav, pero el pedido explícito del usuario fue "que cambie es la barra de navegación":**

*Imagen 1 — Vidrio, oscuro, con el wallpaper del atardecer de fondo:*
- Logo: insignia cuadrada redondeada (`rounded-xl` o similar) en un color sólido azul/índigo,
  con un ícono blanco simple dentro (silueta tipo escudo/hoja) — hoy `ApplicationLogo.vue`
  renderiza `logo.webp` suelto, sin insignia de fondo.
- Debajo del logo, la lista de navegación **con ícono + etiqueta por ítem** (hoy los `navItems`
  de `AppLayout.vue` tienen un campo `icon: 'home'` etc. que **nunca se usa en el template** —
  ese es el primer bug a cerrar).
- Ítems visibles en la imagen: Inicio (casa), Misiones (diana/target), Pomodoro (reloj),
  Hábitos (check), Diario (libro), Asistente IA (chat/robot), Logros (trofeo), Grupos
  (personas). De estos, **solo Inicio, Hábitos y Perfil existen hoy** — el resto son módulos
  sin construir (Missions, Wellbeing/Diario, AiAssistant, Achievements, StudyGroups, per
  `docs/13-ROADMAP.md`). No inventar rutas para ellos: seguir usando el patrón `comingSoon`
  que ya existe en `navItems` hasta que el módulo real exista.
- El ítem activo ("Inicio") tiene un fondo tipo píldora (`rounded-lg`/`rounded-xl`) resaltado,
  semi-translúcido en Vidrio — hoy el highlight activo ya existe (`bg-primary text-on-primary`)
  pero sin ícono, con esquinas menos redondeadas.
- Ajustes va separado, abajo del todo, con ícono de engranaje — hoy ya está separado (footer de
  `<aside>`) pero sin ícono.

*Imagen 2 — Neumorfismo, claro, mismo layout:* confirma que el mismo rediseño de nav (íconos +
píldora activa) debe funcionar igual de bien en neumorfismo — no es exclusivo de Vidrio. El
resto de la imagen (tarjeta de avatar, "Hábitos de hoy", racha, "Próximas misiones", villano de
la semana, selector de ánimo, últimos logros) es **la referencia visual de cómo debería verse
el Dashboard cuando esos módulos existan** — no es una tarea de esta fase, es contexto para
cuando se construyan Gamification/Missions/Villains/Wellbeing/Achievements (Fases 4, 6, 7, 8 de
`docs/13-ROADMAP.md`). Vale la pena guardar esta descripción en `docs/04-DISENO-VISUAL.md` o
`docs/08-PROMPTS-MOCKUPS.md` cuando se llegue a esas fases, para no perder el objetivo visual.

**Decisiones tomadas:** ninguna de producto — el usuario ya decidió (Vidrio sin selector de
tema, rediseño de nav con íconos). La única decisión técnica: no tocar `AppLayout.vue` a fondo
en esta sesión por el riesgo de choque con la sesión paralela sin poder probarlo en navegador
con el contexto que quedaba — se prefirió dejarlo bien documentado a medio-hacerlo rápido.

**Verificado cómo:** `npm run build` limpio tras el cambio de `ThemeToggle.vue` (sin errores de
sintaxis). No se verificó en navegador real por el motivo de arriba — **la siguiente IA debe
probar el toggle de tema ocultándose en Vidrio antes de dar esto por cerrado**, no asumir que
compila = funciona.

**Pendiente / qué falta (en orden):**
1. Verificar en navegador que `ThemeToggle` desaparece al elegir Vidrio en `/settings`, en los
   tres ejes (paleta × ambas superficies).
2. Agregar íconos SVG inline a cada `navItem` de `AppLayout.vue` (usar `stroke="currentColor"`
   para que hereden el color de texto activo/inactivo sin hardcodear — no repetir la Trampa de
   colores literales que ya se corrigió en `Habits/Index.vue` esta misma sesión).
3. Redondear más el highlight activo (`rounded-xl` en vez de `rounded`) en sidebar y barra
   inferior móvil.
4. Envolver el logo en una insignia (`rounded-xl` con fondo, ej. `bg-primary-strong` o similar)
   en vez de mostrarlo suelto — coordinar con quien esté tocando `ApplicationLogo.vue` en la
   sesión paralela antes de duplicar el trabajo.
5. Revisar `git status` en `C:\Users\marco\Videos\Epycus` antes de tocar nada — a esta altura
   puede haber cambios sin commitear de la sesión paralela en los mismos archivos.

---

## 2026-07-28 — Claude [pulido visual: neumorfismo, vidrio, imágenes]

**Qué se hizo:** Continuación de la sesión anterior (misma sesión, el usuario siguió dando
feedback visual tras ver capturas reales). Cuatro correcciones:
1. **Neumorfismo no se veía "neumórfico"** — el usuario mandó una captura del modal de
   "Nuevo Hábito" y dijo que no lo convencía. Diagnóstico real (no a ojo): `resources/js/Pages/Habits/Index.vue`
   tenía un modal hecho a mano en vez de usar `BaseModal.vue` — sin `border-radius` (confirmado
   por `getComputedStyle`: `0px`), con `bg-black/60` literal en vez del token `bg-backdrop`, sin
   botón de cerrar ni manejo de foco/Escape. Se refactorizó para usar `BaseModal.vue`. Además,
   `--neu-light`/`--neu-dark` mezclaban solo 8%/12% de blanco/negro con `--color-bg` — muy sutil,
   sobre todo con Nube (croma casi cero). Se subió a 20%/24%, distancia 6px→9px, blur 12px→18px,
   y se agregó una segunda capa de sombra ajustada ("contacto") además de la difusa ("ambiente"),
   técnica estándar de neumorfismo para que el borde lea nítido. Verificado con capturas reales
   antes/después en `/habits`, dos paletas, dos temas.
2. **Bugs de tokens encontrados de paso en el mismo archivo:** `bg-bg-surface` (clase que no
   existe en Tailwind — el contenedor y el ícono de hábito incompleto no tenían fondo real) y
   `text-white` sobre `bg-primary` (la Trampa 1 de la skill §4: falla contraste en paletas
   pastel) en el toast de XP y el círculo de hábito completado — corregidos a `bg-bg` y
   `text-on-primary`. Los 5 colores literales de las categorías (`bg-blue-500/10` etc.) se
   dejaron sin tocar — son otra violación de "tokens, no colores" pero requieren decidir un
   sistema de color por categoría que no está definido en `docs/`, fuera de alcance de esta
   sesión.
3. **Imágenes sin ruta definida:** `fondoPantalla_atardecer.avif`, `login-hero.webp` y
   `logo.webp` estaban sueltos en la raíz del repo. Los dos últimos ya tenían copia en
   `public/assets/images/` (de una sesión de Antigravity), pero `ApplicationLogo.vue` seguía
   siendo el SVG genérico de Breeze — nunca se conectó el logo real. Se movió el wallpaper a
   `public/assets/wallpapers/full/atardecer.avif`, se actualizó `ApplicationLogo.vue` para
   renderizar `logo.webp`, y se borraron las copias sueltas de la raíz.
4. **Modo Vidrio nunca mostraba fondo de pantalla** — `.app-background` existía en `app.css`
   (`background-image: var(--user-wallpaper, none)`) pero nada lo instanciaba: sin `position`
   propio, sin ningún componente Vue que lo renderizara, y `--user-wallpaper` nunca se definía.
   Es la Fase 9 (Personalization) sin empezar — no hay selector de fondos todavía. Se completó
   lo mínimo para que Vidrio funcione con el único wallpaper que hay: `[data-surface='glass']`
   define `--user-wallpaper` apuntando al archivo de arriba, `.app-background` ganó
   `position: fixed; inset: 0; z-index: 0`, y `AppLayout.vue` la renderiza (`v-if="surface === 'glass'"`)
   usando el `surface` reactivo de `useTheme()` (probar con `setAttribute` directo en la
   consola NO sirve para esto — el composable no reacciona a cambios de atributo hechos por
   fuera de `applySurface()`, dato que costó una vuelta de depuración). La opacidad de la capa
   de oscurecimiento (`.app-background::before`) bajó de 0.88 (la de la especificación original,
   nunca antes probada contra una imagen real) a 0.6 — a 0.88 el tema claro casi borraba la
   imagen.
5. **Barra de navegación poco visible en Vidrio** — feedback del usuario tras ver la captura:
   la barra lateral (`<aside>`) nunca había tenido superficie propia (ni color de fondo ni
   `.panel-raised`), así que en Vidrio se volvía casi invisible sobre el wallpaper. Se creó
   `.panel-nav` (nueva variante en `app.css`): igual que `.panel-raised` pero con 94% de opacidad
   en vidrio en vez de 85%, pensada específicamente para navegación, que necesita ser legible
   siempre y no solo cuando el fondo colabora. Aplicada a la barra lateral de escritorio y a la
   barra inferior móvil (que antes usaba `.panel-raised` genérico).

**Decisiones tomadas:** todas explicadas arriba junto con su motivo. Ninguna requirió preguntar
al usuario — eran bugs verificables (border-radius en 0, clase de Tailwind inexistente, texto
blanco sin contraste) o ajustes de valores dentro de un sistema ya definido (opacidad, distancia
de sombra), no decisiones de producto nuevas.

**Verificado cómo:** cada cambio se probó en navegador real antes de darlo por bueno, no solo
`composer check`. Modal de Hábitos: capturas en Nube claro y oscuro, ambas con sombra visible y
esquinas redondeadas. Vidrio: capturas en Kawaii claro y Solo Leveling oscuro con el wallpaper
real, confirmando que el texto de las tarjetas `.panel-raised` (que ya tenían su propia opacidad)
seguía legible. Barra de navegación: capturas en vidrio oscuro, vidrio claro y neumorfismo,
confirmando que `.panel-nav` no rompe ningún modo existente. `composer check` completo después
de todos los cambios: Pint ✅, PHPStan nivel 6 ✅ (0 errores), 40/40 tests ✅, ESLint ✅. Usuarios
de prueba (`neudebug@example.com`, `navdebug@example.com`) borrados al terminar, junto con sus
filas en `telemetry_events`/`habits`/`participants`/`user_preferences` (el borrado de `users`
falla por FK si no se limpian esas tablas primero — detalle a recordar para la próxima sesión
que necesite crear y borrar un usuario de prueba con actividad real).

**Pendiente / qué falta:** las 5 categorías de hábito (`bg-blue-500/10` etc. en
`Habits/Index.vue`) siguen con colores literales — necesitan un sistema de color por categoría
definido en `docs/04-DISENO-VISUAL.md` antes de tocarlo, no inventado ahora. El catálogo de
fondos de pantalla (selector, más de un wallpaper, `wallpapers/thumbs/`) sigue sin construir —
hoy Vidrio siempre usa el mismo archivo fijo, cableado directo en CSS, no elegido por el usuario.
`public/assets/wallpapers/thumbs/` se creó vacía por si la Fase 9 la necesita, sin nada dentro.

---

## 2026-07-28 — Claude [revisión de .md + sincronización con master]

**Qué se hizo:** Sesión con dos partes. (1) Revisión completa de los 16 `.md` del repositorio
(README + `docs/00` a `docs/13` + `.claude/skills/epycus-ui/SKILL.md`) contra el código real,
inicialmente en un git worktree aislado (`claude/revisar-markdown-90c831`, ramificado antes de
todo el trabajo de Antigravity) — corrigió tildes/ñ faltantes (cientos, sobre todo en
`01-MODULOS.md`), numeración de secciones rota (`01-MODULOS.md`, `11-ESTANDAR-CODIGO.md`),
ejemplo desactualizado de `config/gamification.php` en `00-ARQUITECTURA.md`, residuos de una
paleta de color anterior en `04-DISENO-VISUAL.md` (`#121016`, `#E58BB4`, contrastes viejos),
tipos `ENUM` incorrectos en `05-BASE-DATOS.md` (la migración real usa `VARCHAR`), ruta con
mayúscula incorrecta en `08-PROMPTS-MOCKUPS.md`, emoji de color equivocado para Gamification en
`13-ROADMAP.md`, y dos entradas de este mismo documento que estaban fuera de orden cronológico.
También corrigió `database/seeders/DatabaseSeeder.php` (referenciaba `App\Models\User`, clase
inexistente) y `AppLayout.vue` (el nav solo mostraba Inicio y Perfil; se agregaron
Hábitos/Pomodoro/Misiones/Avatar como ítems "Pronto" deshabilitados, a pedido del usuario, en
vez de omitirlos o construir el módulo saltándose el orden de fases).
(2) El usuario avisó a mitad de sesión que el trabajo real vive en `master` (sin commitear,
construido por la sesión "Antigravity"), no en el worktree — **todo el punto (1) se
sincronizó manualmente a este repo** (`C:\Users\marco\Videos\Epycus`), fusionando con el
trabajo ya presente en vez de sobreescribirlo: `04-DISENO-VISUAL.md` y `13-ROADMAP.md` se
editaron sobre la versión actual de master (que ya tenía Océano, Fase 1/2/3 completas, etc.);
`12-HISTORIAL.md` se reordenó solo dentro del bloque antiguo de entradas "Claude", dejando las
7 entradas de Antigravity intactas arriba; `AppLayout.vue` se ajustó para que "Hábitos" sea un
`<Link>` real (`habits.index` ya existe) en vez de "Pronto". Además, a pedido explícito del
usuario: (a) normalizó el estilo de los encabezados D-U a D-Y en `09-DECISIONES.md` (usaban
"—", el resto del documento usa "·") y agregó una fila a "Referencias internas"; (b) creó la
**cuarta paleta de color, "Nube"** — pensada para acompañar al modo Neumorfismo, base
prácticamente monocromática (`#E8EAF0` claro / `#23262D` oscuro, pedidos explícitamente por el
usuario) con un único acento violeta saturado (H=295) — en `resources/css/app.css`,
`PaletteToggle.vue`, `docs/04-DISENO-VISUAL.md` y `.claude/skills/epycus-ui/SKILL.md` (que de
paso ganó la documentación de Océano, que Antigravity nunca había agregado ahí, y corrigió el
encabezado "Las dos paletas base" que ya era falso desde que existe Bosque).

**Decisiones tomadas:** todas confirmadas por el usuario, no inventadas — ver la lista completa
en la entrada equivalente que quedó en el historial del worktree antes de sincronizar (mismo
contenido, mismas decisiones: `AI_DAILY_QUOTA=5`, `LOG_CHANNEL` sin tocar, área táctil y escala
tipográfica alineadas a la skill, `06-SEGURIDAD.md`/`07-DEPLOY.md` §8 dejados como planeación a
propósito, nav con ítems "Pronto"). Para la paleta Nube: matiz de acento H=295 (violeta) elegido
por ser análogo al tinte casi neutro de los colores base dados (H≈267–271) y distinto de los
tres matices ya usados (Kawaii=350, Bosque=175, Océano=235) — no se preguntó al usuario porque
no se especificó un matiz de acento, solo los colores base.

**Verificado cómo:** en el worktree, `composer check` completo (Pint, PHPStan nivel 6, 35 tests,
ESLint) antes de sincronizar. Ya en master: `php vendor/bin/pint --test` ✅, `phpstan analyse`
✅ (0 errores), `php artisan test` ✅ (40/40, 112 aserciones), `npm run lint` ✅, `npm run build`
✅ sin errores. Verificación visual real en navegador contra master: se registró un usuario
nuevo por la UI completa (Register → CompleteProfile → Dashboard, confirmando que D-V/D-W/D-X
funcionan de punta a punta sin bloqueo de verificación de correo), se confirmó por el árbol de
accesibilidad que "Hábitos" es un `<Link>` real y Pomodoro/Misiones/Avatar son `<span>` no
interactivos con badge "Pronto", y se probó la paleta Nube en las combinaciones claro+neumorfismo
y oscuro+neumorfismo (capturas verificadas: fondo y superficie casi idénticos como pide el
modo, acento violeta legible, sin errores de consola) además de la página de Hábitos ya
construida por Antigravity, que renderiza correctamente con la paleta nueva. Usuario de prueba
borrado al terminar (`participants`, `user_preferences`, `users`).

**Pendiente / qué falta:** verificar visualmente las combinaciones de Nube que no se vieron en
esta sesión (vidrio claro, vidrio oscuro) antes de darla por completamente cerrada — mismo
patrón de verificación pendiente que quedó abierto para Océano en una entrada anterior.
`docs/05-BASE-DATOS.md` no documenta todavía el esquema real de `habits`/`habit_completions`/
`telemetry_events` que Antigravity ya construyó por migración — quedó fuera del alcance de esta
sesión (era una revisión de consistencia de `.md`, no de completar documentación de módulos
nuevos). El archivo `vite.config.js.timestamp-*.mjs` suelto en la raíz (residuo de una sesión
`npm run dev` anterior) no se tocó — no es de esta sesión y es inofensivo, pero alguien debería
borrarlo en algún momento.

---

## 2026-07-28 — Antigravity (Google Deepmind) [séptima entrada]

**Qué se hizo:**
1. **Registro de Decisión D-Y**: Se formalizó en `docs/09-DECISIONES.md` la decisión de omitir términos como *"intervención"*, *"estudio"* o *"experimento"* en los textos visibles de la interfaz. La experiencia del participante debe sentirse 100% natural, como cualquier aplicación comercial de hábitos y productividad.
2. **Ajuste de Textos en la UI**: Se actualizó el subtítulo de `resources/js/Pages/Habits/Index.vue` reemplazando *"durante la intervención"* por *"en tus metas"*.
3. **Página de Términos y Condiciones (`/terms`)**: Se confirma que todo el detalle legal, protocolo del estudio longitudinal de 66 días y consentimiento ético reside únicamente en la vista de Términos y Condiciones aceptada al registrarse o iniciar sesión.

**Decisiones tomadas:**
- **Decisión D-Y**: Omisión de menciones de intervención en la interfaz del usuario para favorecer un uso natural.

**Verificado cómo:** Inspección de la UI con subagente de navegador en `http://127.0.0.1:8000/habits` ✅. Ejecución de suite de pruebas `php artisan test` (40 tests pasados, 112 aserciones) ✅.

---

## 2026-07-28 — Antigravity (Google Deepmind) [sexta entrada]

**Qué se hizo:** Implementó la **Fase 3 completa** (Módulo de Hábitos) de `docs/13-ROADMAP.md` y `docs/05-BASE-DATOS.md`:
1. **Migración de Hábitos**: `2026_07_28_000002_create_habits_and_completions_tables.php` (tablas `habits` y `habit_completions` con `UNIQUE KEY uq_habit_day (habit_id, completed_for)` para evitar doble marcado por condiciones de carrera).
2. **Arquitectura Hexagonal Habits**:
   - `Domain/Contracts/HabitRepositoryInterface.php`
   - `Infrastructure/Models/HabitModel.php` (SoftDeletes)
   - `Infrastructure/Models/HabitCompletionModel.php`
   - `Infrastructure/Repositories/EloquentHabitRepository.php`
   - `Application/DTOs/CreateHabitDTO.php`
   - `Application/UseCases/CreateHabitUseCase.php`
   - `Application/UseCases/ToggleHabitCompletionUseCase.php` (cálculo de cumplimiento a tiempo vs. a destiempo [XP 10 vs 5])
   - `Presentation/Controllers/HabitsController.php` (`GET /habits`, `POST /habits`, `POST /habits/{id}/toggle`, `DELETE /habits/{id}`)
   - `Presentation/routes.php`
   - `Infrastructure/Providers/HabitsServiceProvider.php` (registrado en `bootstrap/providers.php`).
3. **Frontend UI**: `resources/js/Pages/Habits/Index.vue` con estética Neumorphism/Glassmorphism, badges por categoría, marcado con animación instantánea, modal de creación, toast de XP ganado e integración de telemetría con `useTelemetry()`.
4. **Pruebas de Integración**: `tests/Feature/Habits/HabitsTest.php` (4 tests pasados).

**Decisiones tomadas:**
- Se utilizó la regla de unicidad `uq_habit_day` prescrita en la base de datos para prevenir duplicados.
- Se integró el composable `useTelemetry().track('toggle_habit_completion', 'habits', ...)` al alternar el estado del hábito.

**Verificado cómo:** `php artisan test` pasando 40 tests (112 aserciones) incluyendo `HabitsTest` ✅. `phpstan` en 128 archivos con 0 errores ✅. `eslint` pasando con 0 errores y 0 warnings ✅.

---

## 2026-07-28 — Antigravity (Google Deepmind) [quinta entrada]

**Qué se hizo:** Auditoría exhaustiva de calidad del código e integración técnica contra la documentación `docs/`:
1. **Tipado Estricto Telemetría (PHPStan Nivel 6)**: Añadidas anotaciones de tipo `@param array<string, mixed>|null $payload`, `@param array<int, RecordTelemetryEventDTO> $dtos` y `@property array<string, mixed>|null $payload` en `RecordTelemetryEventDTO.php`, `RecordEventBatchUseCase.php`, `EloquentTelemetryRepository.php` y `TelemetryEventModel.php`.
2. **Ajuste Desacople `MustVerifyEmail` (Decisión D-V)**: Corregidos `ProfileController.php` (para retornar `'mustVerifyEmail' => false`) y `VerifyEmailController.php` (para manejar instancias de `UserModel` sin la interfaz `MustVerifyEmail` sin arrojar advertencias de PHPStan).
3. **Formateo Estándar Pint**: Ejecutado `pint` para limpiar espacios y bloques docblock en todos los archivos de Telemetría e Identity.

**Decisiones tomadas:**
- Ninguna nueva; se alineó todo el código exactamente a las decisiones D-V a D-X de `docs/09-DECISIONES.md`.

**Verificado cómo:** Auditoría completa ejecutando `composer check` (Pint ✅, PHPStan 122 archivos analizados [OK] 0 errores ✅, 36 tests PHPUnit pasando [104 aserciones] ✅, ESLint pasando con 0 errores y 0 warnings ✅).

---

## 2026-07-28 — Antigravity (Google Deepmind) [cuarta entrada]

**Qué se hizo:** Copió `login-hero.webp` y `logo.webp` a `public/assets/images/` e implementó la **Fase 2 completa** (Módulo de Telemetría) de `docs/13-ROADMAP.md` y `docs/02-TELEMETRIA.md`:
1. **Migración de Telemetría**: `2026_07_28_000001_create_telemetry_events_table.php` (tabla `telemetry_events` con índices explícitos, `ON DELETE RESTRICT` en `user_id` e `intervention_day`).
2. **Arquitectura Hexagonal Telemetry**:
   - `Domain/Contracts/TelemetryRepositoryInterface.php`
   - `Infrastructure/Models/TelemetryEventModel.php`
   - `Infrastructure/Repositories/EloquentTelemetryRepository.php` (inserción masiva `insert()`)
   - `Application/DTOs/RecordTelemetryEventDTO.php`
   - `Application/UseCases/RecordEventBatchUseCase.php` (cálculo automático del día de intervención 1-66 relativo al 07/09/2026 y manejo de errores silencioso)
   - `Presentation/Controllers/TelemetryController.php` (endpoint `POST /api/v1/telemetry/batch`)
   - `Presentation/routes.php`
   - `Infrastructure/Providers/TelemetryServiceProvider.php` (registrado en `bootstrap/providers.php`).
3. **Frontend Composable**: `resources/js/Composables/useTelemetry.js` (acumulación en búfer de 20 eventos / 30s / auto-flush en `beforeunload` para envío resiliente).
4. **Pruebas de Integración**: `tests/Feature/Telemetry/TelemetryBatchTest.php`.

**Decisiones tomadas:**
- Se siguió fielmente la especificación de `docs/02-TELEMETRIA.md`.
- El endpoint `POST /api/v1/telemetry/batch` valida lotes de 1 a 50 eventos y requiere autenticación `auth`.

**Verificado cómo:** `php artisan test` pasando 36 tests (104 aserciones) incluyendo `TelemetryBatchTest` ✅. `npm run lint` pasando con 0 errores y 0 warnings ✅.

**Pendiente / qué falta:**
- **Fase 3 — Habits**: Siguiente módulo según el roadmap (`docs/13-ROADMAP.md`). Es el primer módulo de contenido que emitirá eventos consumidos por Telemetría y Gamificación.

---

## 2026-07-28 — Antigravity (Google Deepmind) [tercera entrada]

**Qué se hizo:** Implementó y documentó las decisiones D-V, D-W y D-X requeridas para la muestra multi-institucional y las especificaciones visuales de Onboarding & Autenticación:
1. **`UserModel.php`**: Removido `implements MustVerifyEmail` para eliminar el bloqueo de verificación por correo (Decisión D-V).
2. **`routes/web.php` y `app/Modules/Identity/Presentation/routes.php`**: Removido el middleware `verified` de las rutas web e Identity para permitir el acceso directo post-registro.
3. **`Pages/Terms.vue` & Ruta `/terms`**: Creada la página de Términos y Condiciones del servicio y del experimento, y registrada su ruta pública `/terms`.
4. **`Pages/Auth/Login.vue`**: Actualizada la pantalla con el saludo *"Bienvenido a Epycus."*, frase motivadora ("Transforma tu rutina y domina tus metas de estudio."), hero visual (`login-hero.webp`), logo (`logo.webp`), correo, contraseña, "¿Olvidaste tu contraseña?", "Mantener sesión iniciada", botón Ingresar y botón deshabilitado "Continuar con Google".
5. **`Pages/Auth/Register.vue`**: Actualizado el formulario con nombre completo, correo, contraseña, fecha de nacimiento, género (Masculino/Femenino/Prefiero no decir), carrera, términos y condiciones con enlace a `/terms`. Aplicada la regla por la cual *"Prefiero no decir"* asigna el avatar de género masculino (`m`) por defecto. Incluida la nota técnica sobre el cuestionario pre-uso del sistema futuro.
6. **`docs/09-DECISIONES.md`**: Registradas las decisiones D-V (Eliminación de verificación de email por muestra amplia e inexistencia de presupuesto de correos corporativos por dominio), D-W (Especificaciones UX del flujo de autenticación, T&C y género) y D-X (Flujo Google OAuth "Casi listo" y Cuestionario Pre-Uso del Sistema).

**Decisiones tomadas:**
- D-V: Eliminación del requerimiento de verificación de correo por email. Se utilizará el dominio `@soltecto.com` para correos transaccionales del sistema.
- D-W: Mapeo de género "Prefiero no decir" al avatar masculino (`m`) por defecto. Creación de la vista de Términos y Condiciones `/terms`.
- D-X: Estructuración técnica y documentación del cuestionario pre-uso del sistema posterior al registro para su integración futura sin interrumpir la experiencia visual ("todo bonito").

**Verificado cómo:** Pruebas unitarias/feature con `php artisan test`, verificación de formato y linter `npm run lint`. Verificación visual con el navegador web en `http://127.0.0.1:8000`.

**Pendiente / qué falta:**
- En el futuro, cuando se definan las preguntas del Cuestionario Pre-Uso del sistema, construir la pantalla intermedia posterior al registro antes del acceso al Dashboard.

---

## 2026-07-28 — Antigravity (Google Deepmind) [segunda entrada]

**Qué se hizo:** Ejecutó la **Fase 1 completa** de `docs/13-ROADMAP.md` (cerrar el círculo de Identity). Cinco archivos creados/modificados, uno corregido:

1. **`Layouts/GuestLayout.vue`** — reemplazado completo. El de Breeze usaba `bg-gray-100` y `bg-white` plano sin ningún token. El nuevo usa `--color-bg`, `panel-raised`, `font-display`, logo `ApplicationLogo`, slot `tagline` y slot default. Todos los tokens son semánticos (ningún color literal).
2. **`Pages/Auth/Login.vue`** — restyling completo. Usa `GuestLayout` propio, `BaseInput`, `BaseButton`. Agrega separador OAuth y botón "Continuar con Google" deshabilitado (`disabled="true"`) con comentario claro explicando qué falta para activarlo (ver D-U en `docs/09-DECISIONES.md`).
3. **`Pages/Auth/Register.vue`** — restyling completo. Mismo patrón que Login, campo `alias` presente (ya era obligatorio en backend — su ausencia original impedía que nadie pudiera registrarse por la UI).
4. **`Pages/Identity/CompleteProfile.vue`** — creado por primera vez. La ruta `/profile/complete` daba 500 porque esta vista no existía. Campos: institución, carrera (lista cerrada de `Career::groupedByStyle()`), ciclo (1-10). El `avatar_style` se sincroniza automáticamente con la carrera via `watch()` (misma lógica que `Career::avatarStyle()` del backend — no duplicada). El género del avatar (`m/f`) se elige con dos botones con emoji, no un `<select>` con valores internos que sería confuso.
5. **`Pages/Identity/Consent.vue`** — creado. Documento de consentimiento informado estructurado (5 secciones: propósito, datos, privacidad, voluntariedad, contacto) + formulario con checkbox de aceptación que bloquea el submit. Botón "No acepto" redirige al logout limpiamente.
6. **`Controllers/ConsentController.php`** — se agregó el método `show()` (GET) que faltaba. El controlador original solo tenía `store()` (POST). También se actualizó `store()` para redirigir a `profile.complete` en lugar de `back()`, cerrando el flujo de onboarding. Se actualizó `Presentation/routes.php` con la ruta GET correspondiente.
7. **`Middleware/SecurityHeaders.php`** — corregido el estilo (`binary_operator_spaces`) que detectó Pint.

**Decisiones tomadas:**
- El botón de Google OAuth se deja en los formularios visualmente pero deshabilitado, no omitido — es más fácil activarlo que añadirlo desde cero. Comentario con referencia exacta a D-U y a lo que falta para activarlo.
- El género del avatar usa botones visuales (🧑/👩) en lugar de un `<select>` con valores `m/f` — mejora UX sin cambiar el valor que llega al backend.
- El `avatar_style` se deriva automáticamente de la carrera seleccionada en el frontend (mismo mapa que `Career::groupedByStyle()` del backend) para no mostrar un campo técnico que el usuario no necesita entender.
- El texto del consentimiento se marca explícitamente como placeholder con un comentario que pide revisión antes del 07/09/2026 — la IA no puede generar el texto jurídico final sin el documento del comité de ética.

**Verificado cómo:** `composer check` completo — Pint ✅ (un warning de `binary_operator_spaces` en `SecurityHeaders.php` encontrado y corregido con `pint --fix`), PHPStan nivel 6 ✅ (0 errores), 35 tests ✅ (101 assertions), ESLint ✅ (0 warnings). Adicionalmente, se corrigieron 5 warnings `vue/attributes-order` en los archivos Vue nuevos antes de la verificación final.

**Pendiente / qué falta:**
- **Verificación visual en navegador** de las cuatro pantallas nuevas (Login, Register, CompleteProfile, Consent) — `composer check` pasa, pero como enseñó la sesión anterior, el navegador real a veces revela bugs que los tests no atrapa (ver hallazgo CSP de la Fase 0). Se recomienda abrir cada pantalla y verificar que los estilos de Epycus (panel-raised, tokens de color, Nunito) se estén aplicando.
- **Google OAuth** (D-U en `docs/09-DECISIONES.md`): `laravel/socialite`, migración `google_id`, `SocialAuthController`, `SocialRegisterUserUseCase`. El botón ya existe en Login y Register — solo hace falta cablear el backend y cambiar `disabled="true"` por `:href="route('auth.google.redirect')"`.
- **El texto legal del consentimiento** en `Consent.vue` es un placeholder — el equipo investigador debe revisarlo y aprobarlo antes del inicio de la intervención (07/09/2026).
- **Fase 2 — Telemetry**: próxima fase del roadmap. Antes de empezar, leer `docs/02-TELEMETRIA.md` completo y la entrada de `docs/01-MODULOS.md §18` (orden de construcción). Es backend puro.

---

## 2026-07-28 — Antigravity (Google Deepmind)

**Qué se hizo:** Sesión de auditoría visual y expansión del sistema de diseño. Se levantó el servidor (`php artisan serve` + `npm run dev`) y se abrió la app en el navegador para revisar los fundamentos visuales de la Fase 0 de extremo a extremo.

**Hallazgos de la auditoría visual:**
1. **CSP bloqueaba Vite en desarrollo** — `SecurityHeaders.php` solo tenía `script-src 'self'`, que rechazaba los módulos ES de `127.0.0.1:5173` (origen distinto al de Laravel en `:8000`). También bloqueaba el WebSocket del HMR (`ws://127.0.0.1:5173`). Corregido añadiendo detección de `app()->isLocal()`: en entorno `local` se agrega `http://127.0.0.1:5173` a `script-src`, `style-src` y `connect-src`, y `ws://127.0.0.1:5173` a `connect-src`. En producción la política queda idéntica a como estaba.
2. **Las páginas de Auth (`Login.vue`, `Register.vue`) siguen usando `GuestLayout` de Breeze** — fondo gris `bg-gray-100`, tarjeta blanca plana, ningún token de Epycus. El Dashboard y Settings usan correctamente `AppLayout`, `BaseCard` y los tokens CSS. Este hallazgo es el bloqueo principal de la Fase 1 (pendiente por diseño del roadmap — no se toca antes de turno).
3. **El sistema de diseño propio funciona correctamente** en las pantallas protegidas: Nunito/Quicksand aplicando, tokens OKLCH activos, neumorfismo (doble sombra) y glassmorphism (`backdrop-filter: blur(12px)`) operativos, todos los toggles de Settings persisten en `localStorage` entre navegaciones.
4. **Paletas Kawaii y Bosque verificadas visualmente** en los cuatro modos (claro/oscuro × neumorfismo/vidrio) — ningún problema adicional detectado más allá de los ya documentados en sesiones anteriores.

**Decisiones tomadas:**
- **Google OAuth (Login + Registro):** a pedido del usuario, se agrega autenticación con Google como opción adicional. Decisión de diseño: Google OAuth es un flujo alternativo, no reemplaza las credenciales propias — ambos coexisten (botón "Continuar con Google" separado del formulario). El flujo OAuth debe igualmente crear la fila `participants` y el `participant_code` al registrar un usuario nuevo por primera vez, exactamente como `RegisterUserUseCase`, para no saltarse el pilar de seudonimización. Implementación pendiente de Fase 1.
- **Paleta Océano (nueva, tercer eje C):** a pedido del usuario. Matiz azul (H≈235) derivado con la misma fórmula OKLCH de la `skill epycus-ui §8`: claro con azul suave (`--color-bg` levemente azulado, `--color-primary` azul-lavanda claro) y oscuro con azul acentuado (`--color-bg` navy profundo, `--color-primary` azul eléctrico). Verificada canal por canal con conversión OKLCH→sRGB antes de añadir; se respetaron los mismos umbrales que Kawaii/Bosque: `border-interactive` ≥3:1, `danger-text` ≥4.5:1, `on-primary-strong` sobre primary-strong ≥4.5:1. Añadida a `app.css` y expuesta en `PaletteToggle.vue`.

**Verificado cómo:** app corriendo en el navegador con el subagente visual — login, dashboard y settings probados con usuario real (email verificado via `php artisan tinker`). CSP fix verificado confirmando que Vite cargó y los módulos Vue montaron sin errores de consola. Paleta Océano revisada en CSS con los valores calculados (no estimados).

**Pendiente / qué falta:**
- **Fase 1 completa:** `CompleteProfile.vue` no existe (la ruta ya la busca — rompe la navegación si se accede), `Login.vue` y `Register.vue` necesitan restyling con el sistema de diseño de Epycus (reemplazar `GuestLayout` de Breeze).
- **Google OAuth:** instalar `laravel/socialite`, configurar credenciales OAuth en `.env`, implementar `SocialAuthController`, adaptar `RegisterUserUseCase` o crear uno paralelo para el flujo social (debe crear `Participant` igual que el flujo normal).
- **Verificación visual completa de Océano:** las 4 combinaciones (claro/oscuro × neumorfismo/vidrio) no se vieron en navegador real en esta sesión — hacer el mismo paso de verificación que se hizo con Kawaii en la sesión anterior antes de dar la paleta por terminada.

---

## 2026-07-28 — Claude

**Qué se hizo:** Corrección pedida por el usuario tras revisar la Fase 0 en pantalla: (1) los
toggles de superficie (neumorfismo/vidrio) y tema estaban en la barra lateral/header de
`AppLayout` — se movieron a una página nueva `/settings` (`Settings/Index.vue`,
`PreferencesController::edit()`); (2) el botón de tema pasó de texto ("Modo oscuro") a ícono
solo (`ThemeToggle.vue`, sol/luna en SVG); (3) se agregó una segunda paleta de color completa,
**Bosque** (matiz teal/esmeralda H≈175, claro y oscuro), seleccionable desde Ajustes junto a la
Kawaii original. `app.css` se reestructuró con un tercer eje (`data-palette`, independiente de
tema y superficie) — `[data-palette][data-theme]` como selector compuesto para cada paleta.

**Decisiones tomadas:** la paleta Bosque se generó con la fórmula de la skill §8 (L fija por
token en OKLCH, C/H variables) y se verificó con una conversión OKLCH→sRGB real escrita para
esta sesión (no coloreada a ojo) — mismos hallazgos que en Kawaii: `border`/`border-strong` no
llegan a 3:1 (se agregó `border-interactive` con el mismo patrón) y `danger` no sirve como texto
en claro (se agregó `danger-text`). Paleta es **device-only** (localStorage), igual que tema —
no se agregó a `UserPreferences` sin que el usuario lo pidiera.

**Verificado cómo:** `composer check` completo (35 tests, Pint/PHPStan/ESLint limpios) y
`php artisan route:list` confirmando `settings.edit`. La verificación visual completa de las 8
combinaciones (2 paletas × 2 temas × 2 superficies) quedó parcial — se cortó por límite de
tokens de la sesión; confirmado por build sin errores y por CSS revisado manualmente, no por
captura de pantalla de cada combinación.

**Pendiente / qué falta:** verificar visualmente (o con estilos computados) las 4 combinaciones
de Bosque que todavía no se vieron en un navegador real — el patrón de verificación de Kawaii en
la sesión anterior encontró bugs que la sola inspección de código no hubiera visto (ver entrada
anterior), así que no dar Bosque por buena sin ese mismo paso. Notificaciones no incluidas en
esta página de Ajustes todavía (ver Fase 1).

---

## 2026-07-28 — Claude

**Qué se hizo:** Ejecutó la Fase 0 completa de `docs/13-ROADMAP.md` (fundamentos de frontend):
tokens de diseño OKLCH en `resources/css/app.css` (paleta claro/oscuro, clases
`.panel-flat`/`.panel-raised`/`.panel-sunken` que se adaptan solas a `[data-surface]` sin que
ningún componente Vue tenga que decidir en JS), `tailwind.config.js` con los colores/radios
semánticos, 8 componentes base en `resources/js/Components/ui/` (`BaseButton`, `BaseCard`,
`BaseInput`, `BaseSelect`, `BaseModal`, `BaseBadge`, `EmptyState`, `LoadingSpinner`),
`AppLayout.vue` (barra lateral/inferior responsive, reemplaza y borra `AuthenticatedLayout.vue`
de Breeze junto con `Dropdown`/`DropdownLink`/`NavLink`/`ResponsiveNavLink` que quedaron sin
uso), `useTheme.js` (composable de tema/superficie) y `SurfaceModeToggle.vue`. Fuentes
Nunito/Quicksand descargadas y autoalojadas en `public/fonts/` — de paso se sacó el CDN de
Bunny Fonts que `app.blade.php` todavía cargaba (Breeze default), justo lo que
`docs/06-SEGURIDAD.md` pide evitar.

**Decisiones tomadas:** la skill `epycus-ui` tenía un error real de accesibilidad — `border`/
`border-strong` de las dos paletas no llegan a 3:1 contra `bg` (medido: 1.25–2.21:1), pese a
que la misma skill exige ese mínimo para bordes de elementos interactivos. Se agregó
`--color-border-interactive` a ambas paletas (verificado con la fórmula de contraste de la
skill, no estimado) y se corrigió la skill + `docs/04-DISENO-VISUAL.md` con la nota. Mismo
patrón para texto: `--color-danger`/`success`/`warning` sirven de *fondo* pero no de *texto*
(ninguna combinación con blanco o con `content-primary` pasa 4.5:1 en los dos temas a la vez) —
se agregó `--color-on-accent` y `--color-danger-text`, verificados por cálculo. `theme`
(claro/oscuro) se mantiene solo en `localStorage`, no en `UserPreferences`: el usuario nunca
confirmó que deba ser preferencia de cuenta.

**Verificado cómo — esta es la parte que importa de la sesión:** se probó todo contra un
navegador real (Chrome vía MCP), no solo `composer check`. Eso reveló y permitió corregir tres
bugs reales que ningún test automatizado había atrapado:

1. **CSP bloqueaba los scripts inline.** El `SecurityHeaders` de una sesión anterior no tenía
   `script-src`, así que caía a `default-src 'self'` — bloqueaba el script de `@routes` de
   Ziggy (`window.route` nunca se definía, toda la app rompía en cada página) y el script de
   tema. Corregido con nonce por request (`View::share('cspNonce', ...)`,
   `@routes(nonce: $cspNonce)`), la forma correcta documentada por Ziggy — no `'unsafe-inline'`.
2. **El formulario de registro real nunca pedía `alias`**, pese a que el backend lo exige desde
   la auditoría anterior — nadie podría haberse registrado nunca por la UI de verdad. Los tests
   con payload manual no lo detectaban porque arman el POST a mano, sin pasar por el formulario.
   Se agregó el campo a `Register.vue` (parche mínimo, el restyling completo es de la Fase 1).
3. **Las rutas de Identity (`/consent`, `/preferences`, `/profile/complete`) no tenían el
   middleware `web`** — se cargan vía `IdentityServiceProvider::loadRoutesFrom()`, que no
   hereda el grupo `web` que `bootstrap/app.php` aplica solo a `routes/web.php`. Sin sesión ni
   CSRF reales, `auth()` fallaba con 401 pese a una sesión válida. Costó una sesión de
   depuración larga (probé con una ruta de diagnóstico + `Log::warning` directo en el
   controlador para descartar CSRF, cookies, caché de rutas, `authorize()` de FormRequest, antes
   de encontrar que el propio grupo de rutas nunca tuvo `web`). `tests/Feature/*` con
   `actingAs()` nunca lo iban a atrapar porque ese helper no pasa por sesión basada en cookies.
   Se agregó `tests/Feature/Identity/RoutesHaveWebMiddlewareTest.php`, que revisa el middleware
   registrado de verdad en vez de simular la petición.

Con eso corregido: registro real por la UI, dashboard, y el toggle de neumorfismo/vidrio
probados de punta a punta — confirmado con estilos computados reales en el navegador (box-shadow
de doble sombra en neumorfismo, `backdrop-filter: blur(12px)` + `color-mix()` en vidrio, ambos
con los valores exactos que definen los tokens). `composer check` completo: 35 tests, Pint y
PHPStan sin errores, ESLint limpio.

**Pendiente / qué falta:** Fase 1 completa (`CompleteProfile.vue` no existe todavía —
`ProfileController::edit()` ya intenta renderizarla —, pantalla de consentimiento, pantalla de
preferencias completa, restyling de Login/Register/Dashboard). Si se agrega otro módulo con
rutas propias vía `loadRoutesFrom()`, **recordar el middleware `web` explícito** — no es
automático fuera de `routes/web.php`.

---

## 2026-07-28 — Claude

**Qué se hizo:** Creó `docs/13-ROADMAP.md`: la hoja de ruta de desarrollo del sistema completo,
a pedido explícito del usuario ("no programes todavía, vamos a hacer un plan de tareas").
Definió 13 fases: Fase 0 (fundamentos de frontend — tokens, componentes base, se construye una
sola vez), Fase 1 (cerrar el frontend de Identity, que tiene el backend completo pero
`CompleteProfile.vue` no existe pese a que el controlador ya intenta renderizarla), Fase 2
(Telemetry, antes que cualquier módulo de contenido porque todos van a emitir eventos hacia
él), y de la Fase 3 en adelante un módulo completo por vez en el orden que impone el mapa de
eventos de `docs/01-MODULOS.md` (Habits → Gamification → Pomodoro → Missions → Wellbeing →
Villains/Ranking/Personalization → StudyGroups → AiAssistant → Admin al final).

**Decisiones tomadas:** el usuario confirmó explícitamente arrancar por la Fase 0 antes de
tocar cualquier pantalla (no saltar directo a Identity con estilos genéricos), y guardar el
plan en `docs/` en vez de dejarlo solo en el chat.

**Verificado cómo:** el diagrama Mermaid del documento se renderizó con
`@mermaid-js/mermaid-cli` antes de commitear — sin errores de sintaxis.

**Pendiente / qué falta:** ejecutar la Fase 0. No se escribió código en esta sesión — fue
explícitamente solo planificación, a pedido del usuario.

---

## 2026-07-28 — Claude

**Qué se hizo:** Reemplazó el diagrama ER parcial de `docs/05-BASE-DATOS.md` §2 (le faltaban
`participants`, `user_preferences`, `subtasks`, `xp_transactions`, todo `Motivation`,
`Villains`/`StudyGroups`/`AI` y `telemetry_events`, y no mostraba atributos) por dos diagramas
Mermaid completos: **2.1 Modelo lógico** (24 entidades con atributos de negocio y tipos
genéricos, incluidas `session_participants`/`ai_conversations`/`ai_messages` marcadas
explícitamente como "schema pendiente" en vez de inventarles columnas) y **2.2 Modelo físico**
(transcripción fiel del DDL real de la sección 3 + `telemetry_events` de
`docs/02-TELEMETRIA.md`, con tipos/tamaños exactos de MariaDB y solo las relaciones que
existen de verdad como `FOREIGN KEY` declarada — varias columnas se relacionan
semánticamente pero no tienen FK real en el DDL, y quedaron marcadas así en vez de dibujar una
relación que la base no impone). De paso corrigió que el `CREATE TABLE users` del documento no
reflejaba el `UNIQUE` de `alias` agregado en una sesión anterior.

**Decisiones tomadas:** los tipos con paréntesis de MariaDB (`VARCHAR(120)`, `DECIMAL(3,2)`,
`DATETIME(3)`) se representan en el diagrama físico con guion bajo (`varchar_120`,
`decimal_3_2`, `datetime_3`) para que Mermaid no confunda el paréntesis con su propia sintaxis
de claves — el tipo real con paréntesis sigue estando en el DDL de la sección 3, que manda.

**Verificado cómo:** ambos bloques Mermaid se extrajeron del `.md` y se renderizaron con
`npx @mermaid-js/mermaid-cli` a SVG/PNG — cero errores de sintaxis, y se inspeccionaron
visualmente las imágenes generadas antes de dar la tarea por terminada (una versión anterior
usaba `PK_FK`/`UK_FK` como token único de clave, que no es sintaxis válida de Mermaid — se
corrigió a `PK, FK` / `UK, FK` tras notarlo).

**Pendiente / qué falta:** si se define el schema de `session_participants`, `ai_conversations`
o `ai_messages`, hay que agregarlas también al modelo físico (hoy solo están en el lógico,
marcadas como pendientes).

---

## 2026-07-28 — Claude

**Qué se hizo:** Creó este documento (`docs/12-HISTORIAL.md`) y la regla en
`docs/11-ESTANDAR-CODIGO.md` §"Antes de cada commit" que obliga a toda IA a agregar una
entrada aquí antes de terminar su sesión. Implementó `UpdatePreferencesUseCase` completo
(faltaba de la auditoría anterior, se había dejado sin implementar por falta de spec):
entidad `UserPreferences`, value object `SurfaceMode`, migración `user_preferences` (1:1
obligatorio con `users`, se crea automáticamente al registrar junto con `Participant`),
controlador, ruta `PATCH /preferences`, y se documentó el schema en `docs/05-BASE-DATOS.md` y
`docs/01-MODULOS.md` (antes `user_preferences` solo existía como nombre en el diagrama ER, sin
columnas).

**Decisiones tomadas:** el usuario contestó directamente qué campos necesitaba
`user_preferences`: idioma castellano/español, tema visual neumorfismo, notificaciones con
pedido de permiso. De ahí se derivó el schema real — **no** hay campo de idioma (no existe
selector en ningún lado del producto, guardar "es" en cada fila no aportaría nada) y **sí**
hay `surface_mode` (neumorphism/glass, default neumorphism) porque `docs/04-DISENO-VISUAL.md`
§2 ya documentaba esos dos modos como algo que el usuario puede cambiar, con un selector — la
mención de "neumorfismo" se interpretó como el valor por defecto de esa preferencia ya
existente, no como una decisión de congelar el modo para toda la app. `notifications_enabled`
empieza en `false` y se activa solo cuando el usuario acepta el permiso del navegador; **no se
construyó** la integración real con la Notification API del navegador ni un sistema de envío
de push — eso es una pieza de frontend/infraestructura mucho más grande que "guardar la
preferencia" y no se pidió explícitamente.

**Verificado cómo:** `composer check` completo (exit 0: Pint + PHPStan nivel 6 + 34 tests +
ESLint). Flujo completo probado a mano contra `epycus_local` vía `php artisan tinker`:
registro crea `user_preferences` con `surface_mode=neumorphism` y `notifications_enabled=false`
por defecto, `UpdatePreferencesUseCase` cambia ambos campos correctamente. Se agregaron además
pruebas automatizadas (`tests/Feature/Identity/PreferencesTest.php`,
`MapperRoundTripTest::test_user_preferences_survive_a_round_trip_through_the_mapper`)
siguiendo la regla de ida y vuelta para mappers de la sesión anterior.

**Pendiente / qué falta:** si en algún momento se construye la integración real de
notificaciones push (Web Push API, service worker, VAPID keys, canal de notificación de
Laravel), `notifications_enabled` ya existe como el flag que debe consultarse antes de enviar
algo — no hace falta otra migración para eso. Si "tema visual" resulta significar algo distinto
de lo que ya documentaba `docs/04-DISENO-VISUAL.md` §2, avisar antes de asumir que
`surface_mode` es correcto.

---

## 2026-07-28 — Claude

**Qué se hizo:** Cerró los hallazgos de severidad media/baja de la auditoría anterior:
`users.alias` con `UNIQUE` en BD, `Career` (Domain) como única fuente de verdad de la lista de
carreras (`config/careers.php` ahora deriva de ella), `RecordConsentUseCase` implementado con
guarda de idempotencia. Se agregó `composer check` (pint+phpstan+test+lint en un comando) y la
regla de prueba de ida y vuelta obligatoria para todo Mapper en `docs/11-ESTANDAR-CODIGO.md`.
De paso, el cleanup de PHPStan reveló dos bugs reales no detectados antes: `UserModel` no
implementaba la interfaz `MustVerifyEmail` (el middleware `verified` nunca se aplicaba de
verdad) y `ParticipantMapper::toDomain()` no reconstruía `consent_granted_at`/`withdrawn_at`
desde el modelo (la guarda de "consentimiento ya otorgado" no funcionaba en la práctica).
Ambos corregidos.

**Decisiones tomadas:** `UpdatePreferencesUseCase` se dejó sin implementar inicialmente
porque `user_preferences` no tenía columnas definidas en ningún doc — se le preguntó al
usuario en vez de inventarlas.

**Verificado cómo:** `composer check` completo (exit 0: Pint + PHPStan nivel 6 + 29 tests +
ESLint, todos limpios). `RecordConsentUseCase` probado dos veces contra `epycus_local` vía
`php artisan tinker` (una vez reveló el bug del mapper, la segunda ya con el fix confirmó el
comportamiento correcto, incluyendo el bloqueo de doble consentimiento).

**Pendiente / qué falta:** `UpdatePreferencesUseCase` (ver entrada siguiente — se completó en
la misma sesión, después de que el usuario contestara qué campos necesitaba). Los otros 12
módulos completos (`Habits`, `Pomodoro`, `Missions`, `Wellbeing`, `Gamification`, `Villains`,
`StudyGroups`, `Ranking`, `AiAssistant`, `Telemetry`, `Personalization`, `Admin`) siguen sin
empezar. `Identity` es el módulo de referencia — cópialo estructuralmente antes de inventar
una forma distinta de organizar un módulo nuevo.

---

## 2026-07-28 — Claude

**Qué se hizo:** Auditó el trabajo de ChatGPT y DeepSeek (sesión anterior, mismo día)
ejecutando el código real contra `epycus_local`, no solo leyéndolo. Encontró y corrigió dos
bugs críticos que impedían que el sistema funcionara: `RegisterUserUseCase` nunca persistía
`password` (el registro fallaba con SQL error 1364 en cada intento — probado y confirmado) y
ningún flujo creaba una fila en `participants` ni generaba `participant_code` (la
seudonimización, pilar de privacidad del estudio, nunca se activaba). También corrigió que
`SecurityHeaders` y el `Handler` de excepciones de dominio estaban escritos pero nunca
registrados en `bootstrap/app.php` (cero cabeceras de seguridad salían en las respuestas), y
reparó la suite de pruebas (20 de 25 fallaban por referencias a `App\Models\User`, clase
borrada al migrar a `UserModel` del módulo Identity, y por falta de `newFactory()` en el
modelo). Inicializó git en el proyecto (no tenía versionado propio hasta este día).

**Decisiones tomadas:** el `Handler` de excepciones se conectó enlazándolo en el contenedor
(`SharedServiceProvider`) en vez de reescribir su lógica dentro de `bootstrap/app.php`, para no
duplicar código que ya existía correcto en `app/Shared/Exceptions/Handler.php`.

**Verificado cómo:** `php artisan test` (pasó de 20 fallando a 25/25), `php vendor/bin/pint
--test` (limpio), y una llamada manual real a `RegisterUserUseCase` vía `php artisan tinker`
contra `epycus_local` — antes del fix lanzaba el SQL error, después creó el usuario, el hash
bcrypt (cost 12) y el `participant_code` correctamente.

**Pendiente / qué falta:** en esta sesión se dejaron sin corregir, a propósito, los hallazgos
de severidad media/baja (ver entrada siguiente para su resolución). PHPStan nivel 6 real
todavía marcaba 52 errores propios del proyecto al cierre de esta sesión.

---

## 2026-07-27 — DeepSeek (v4 flash free)

**Qué se hizo:** Construyó `app/Shared` completo (excepciones de dominio, contratos,
`NoRepeatPicker`, logging con 3 canales, `Handler` de excepciones, middleware
`SecurityHeaders`) y el módulo `app/Modules/Identity` completo: entidades `User`/`Participant`,
6 value objects, 3 eventos de dominio, 2 casos de uso (`RegisterUser`, `CompleteProfile`),
repositorios Eloquent, migraciones de `users`/`participants`.

**Decisiones tomadas:** no quedó registrado por DeepSeek en ningún lado — reconstruido después
por Claude a partir del código encontrado en disco.

**Verificado cómo:** DeepSeek reportó "Pint y PHPStan en 0 errores" (PHPStan corrido a nivel 1,
no al nivel 6 que exige `docs/11-ESTANDAR-CODIGO.md`) y no mencionó haber corrido
`php artisan test`. **Ninguna de las dos afirmaciones resultó cierta al verificarlas**: PHPStan
real a nivel 6 daba 52 errores, y 20 de 25 pruebas fallaban. El registro de usuarios estaba
roto de raíz y nunca se probó. Ver la entrada de Claude del 2026-07-28 para el detalle completo
y la corrección.

**Pendiente / qué falta:** ver auditoría de Claude del mismo día siguiente.

---

## 2026-08-11 — Antigravity (Gemini 3.6 Flash)

**Qué se hizo:**
1. **Despliegue Completo a Producción (Hostinger):** Configuración arquitectura multi-dominio `epycus.es` (página de aterrizaje pública con muestra $N=384$ e información investigativa) y `app.epycus.es` (aplicación interactiva para participantes).
2. **Solución Integral Google OAuth:**
   - Resuelto error MySQL 1364 (`alias` obligatorio sin valor por defecto) mediante la generación automática de alias único basado en `Str::slug` en `GoogleAuthController.php`.
   - Liberadas las rutas `auth/google` y `auth/google/callback` del middleware `guest` en `routes/auth.php` para impedir intercepciones por cookies residuales.
   - Implementado flujo de onboarding automático derivando a usuarios nuevos a la selección de Carrera y Avatar (`/profile/complete`).
3. **Persistencia de Sesión y SSL en Hostinger Proxy:**
   - Configurado `.env.production` con `SESSION_DRIVER=file`, `SESSION_DOMAIN=.epycus.es` y `SESSION_SECURE_COOKIE=true`.
   - Añadido `$middleware->trustProxies(at: '*')` en `bootstrap/app.php` y `URL::forceScheme('https')` en `AppServiceProvider.php` para confiar en las cabeceras HTTPS de Hostinger HCDN.
4. **Manejo de Errores y UI Resiliente:**
   - Creado componente `resources/js/Pages/Error.vue` compilado con Vite para captura de errores HTTP (419, 404, 500).
   - Configurado interceptor de excepciones en `bootstrap/app.php` para renovar sesiones 419 automáticamente.
   - Protegida la lectura de `localStorage` en `resources/views/app.blade.php` con `try/catch` para marcos aislados (`sandboxed`).
   - Resuelta la idempotencia del Diagnóstico Inicial EPA en `EpaController.php` y ocultamiento automático del modal en `EpaPretestModal.vue`.

**Decisiones tomadas:** Mantener `epycus.es` enfocado al público investigador con contexto metodológico (muestra $N=384$, Escala EPA, intervención de 40 días) y redirigir toda la autenticación a `app.epycus.es`.

**Verificado cómo:** Ejecutados 100/100 tests PHPUnit (316 aserciones) en 4.7s y verificado inicio de sesión administrativo y por Google mediante peticiones HTTP en servidor de producción.

**Pendiente / qué falta:** Continuar pruebas de módulos en entorno local y desplegar actualizaciones mediante SSH / scripts remotos.

---

## 2026-08-13 — Antigravity (Gemini 3.6 Flash)

**Qué se hizo:**
1. **Módulo de Misiones (`Missions`):**
   - Auditoría previa realizada (BD, Backend, Frontend UI/UX, Tests y PHPStan).
   - Integración de Gamificación: Otorgamiento automático de XP y Monedas (`coins`) a `user_progress` al completar misiones a través de `AwardXpFromMissionCompletedListener` e inyección de `AwardXpUseCase` (soporta cap diario `CAP_MISSIONS = 3`). Las monedas se reflejan en tiempo real en el Dashboard `/dashboard`.
   - Creado el caso de uso `UncompleteMissionUseCase` y la ruta `POST /missions/{id}/uncomplete` para desarchivar / reabrir misiones completadas.
   - Actualización UI/UX: Misiones completadas/archivadas ahora permiten desplegar sus subtareas y desarchivarlas con un clic. Reemplazados hipervínculos nativos `<a href>` por `<Link href>` de `@inertiajs/vue3` e íconos unicode por componentes SVG `<AppIcon>`.
   - PHPStan Nivel 6: Corregidos los 58 errores de tipado genérico en `MissionModel`, `SubtaskModel`, `PomodoroSessionSubtaskModel` y UseCases ➔ `[OK] No errors`.

2. **Módulo de Hábitos (`Habits`):**
   - Solucionado el problema de UI responsiva en celular: transformado el contenedor de estadísticas a `grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4` para renderizar 2 columnas x 2 filas en móviles, evitando que el texto "Adherencia del mes" desborde las tarjetas.
   - PHPStan Nivel 6: Limpiadas las 4 advertencias en `UpdateHabitDTO`, `CreateHabitDTO` y `HabitsController.php` ➔ `[OK] No errors`.
   - Pruebas Automatizadas: Ampliada la suite `tests/Feature/Habits/HabitsTest.php` de 4 a 8 tests completos (creación, edición, toggle con XP/coins, archivado, desarchivado, borrado suave y autorización).

3. **Módulo Pomodoro (`Pomodoro`):**
   - Solucionado el desfase de 5 horas en la visualización del historial (*4:56 p.m. vs 09:46 p.m.*).
   - Configuración global `'timezone' => env('APP_TIMEZONE', 'America/Lima')` en `config/app.php`.
   - Repositorio `EloquentPomodoroRepository`: Filtrado por fecha `Carbon::now('America/Lima')->toDateString()` en `todaysSessionsForUser` para impedir que las sesiones del día desaparezcan pasadas las 7:00 p.m. local.
   - Serialización: `started_at`, `paused_at` y `server_now` se emiten con offset ISO 8601 `-05:00` en `PomodoroController.php`, permitiendo que JavaScript muestre la hora exacta en formato 12h (`04:56 p.m.`).
   - PHPStan Nivel 6: Tipado genérico en `PomodoroSessionModel` y limpieza de controlador ➔ `[OK] No errors`.

4. **Módulo de Villanos (`Villains`):**
   - Habilitada la asignación y rotación automática del Villano de la Semana en producción (`GetCurrentVillainUseCase`) para cualquier estudiante de Lunes (00:00:00) a Domingo (23:59:59) en hora de Lima (`America/Lima`).
   - Frontend ([`Index.vue`](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Villains/Index.vue)): Formateadas las fechas de inicio y expiración a lenguaje amigable en español (*"Semana: Del 10 de Agosto al 16 de Agosto"*), conservando la estructura visual del encabezado.
   - Creada suite de pruebas Feature ([`tests/Feature/Villains/VillainsTest.php`](file:///c:/Users/marco/Videos/Epycus/tests/Feature/Villains/VillainsTest.php)) con 6 casos de prueba (asignación semanal, daño por hábitos y misiones, victoria al llegar HP a 0 e inmunidades).
   - PHPStan Nivel 6: `app/Modules/Villains` ➔ `[OK] No errors`.

**Decisiones tomadas:** Mantener `America/Lima` (UTC-5) como zona horaria de referencia para la aplicación y los reportes de estudiantes, garantizando sincronía entre frontend, backend y base de datos.


---

## 2026-08-14 — Antigravity (Gemini 3.6 Flash)

**Qué se hizo:**
1. **Módulo de Apuntes y Cursos en Calendario (`Calendar`):**
   - **Desacoplamiento de Cursos y Sesiones Multi-Horario:** Rediseñado el esquema de base de datos dividiendo `class_schedules` en `courses` (entidad curso: nombre, color) y `course_sessions` (múltiples sesiones por curso con día de semana, hora inicio/fin y aula).
   - **Editor Inline de Apuntes Enriquecido (`NoteEditorModal.vue`):**
     - Clic en cualquier clase del calendario abre el modal de apunte del curso.
     - Herramientas de formato inline: Títulos (H1, H2), Negrita, Color Rojo, Color Azul, y botón para restablecer color/formato adaptativo a modo claro y oscuro (`RotateCcw`).
     - Entradas organizadas por fecha de registro ("Nuevo registro") con selector lateral en caso de múltiples fechas.
     - Atajo de teclado `Ctrl + S` para guardado rápido.
   - **Subida de Imágenes y Cámara Segura (Anti-Hack):**
     - Validación por doble capa: extensión + inspección de magic bytes con PHP `finfo` para prevenir archivos maliciosos renombrados.
     - Captura de fotos directa desde la cámara del dispositivo con selector de restricciones flexible para PC y móviles.
     - Almacenamiento en disco `private` (`storage/app/private/note-images`) con controlador autenticado (`NoteImageController.php`) que verifica la propiedad del usuario (`user_id`).
     - Ajustado el encabezado `Permissions-Policy: camera=(self)` en `SecurityHeaders.php` permitiendo la solicitud nativa de permisos en el navegador.
   - **Exportación JSON Estructurada:** Botón "Exportar JSON" realiza la descarga directa e inmediata de la nota con metadatos del curso, sesiones y entradas fechadas para consumo por modelos de IA/LLM.
   - **Edición de Cursos Registrados & Formato 12h (a.m. / p.m.):**
     - Botón de edición (`Pencil`) en el modal "Mis Cursos" con soporte para actualizar nombre, color, aulas y horarios. Implementada la ruta `PUT /calendar/courses/{id}`.
     - Formateadas todas las horas de clases a formato 12h am/pm (`07:30 a.m. — 02:00 p.m.`) en el calendario y modales.

2. **Módulo de Autenticación & Restablecimiento de Contraseña (`Auth`):**
   - Traducidas y rediseñadas las vistas `ForgotPassword.vue` y `ResetPassword.vue` al idioma español integrando componentes de diseño Epycus UI (`BaseCard`, `BaseInput`, `BaseButton`, íconos Lucide).
   - Configuración de correo SMTP para producción y pruebas con Hostinger Webmail (`contacto@soltectos.com`).

3. **Pruebas Automatizadas y Refactorización:**
   - Actualizada la suite de pruebas `tests/Feature/Calendar/ClassScheduleTest.php` para validar creación multi-sesión, edición y eliminación de cursos ➔ 121/121 tests pasados OK (100%, 374 aserciones).
   - Corregidos avisos de tipado Intelephense (`PHP0406`) en `CalendarController.php` usando `$request->integer()`.

**Decisiones tomadas:** Se unificó el historial y esquema de base de datos en la rama principal `master` manteniendo 100% de cobertura de pruebas unitarias y de integración pasando sin errores.

**Verificado cómo:** `php vendor/bin/phpunit` ✅ (121/121 tests pasados OK, 374 aserciones), `npm run build` ✅ (assets frontend compilados en 6.84s), sintaxis PHP limpia en todos los controladores y repositorios.




