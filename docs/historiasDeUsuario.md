# Historias de Usuario — Todo lo realizado en Epycus

> Documento que resume, desde la perspectiva del usuario, todo lo que se ha construido en el proyecto Epycus hasta la fecha. Organizado por módulos implementados, con el estado actual de cada uno.

---

## Índice de lo construido

| # | Módulo | Estado |
|---|--------|--------|
| 0 | Fundamentos visuales (Fase 0) | ✅ Completo |
| 1 | Identity — Autenticación y perfil | ✅ Completo |
| 2 | Telemetría | ✅ Completo |
| 3 | Hábitos | ✅ Completo |
| 4 | Gamificación — XP, niveles, rachas | ✅ Completo |
| 5 | Pomodoro | ✅ Completo |
| 6 | Calendar — Calendario peruano | ✅ Completo |
| 7 | Misiones (tareas + subtareas) | ✅ Completo |
| 8 | Avatares — Assets reales conectados | ✅ Bloque 1 completo |
| — | Wellbeing — Diario de ánimo | ❌ Pendiente |
| — | Villains — Villanos semanales | ❌ Pendiente |
| — | Ranking | ❌ Pendiente |
| — | Personalization — Fondos y temas | ❌ Pendiente |
| — | Achievements — Logros e insignias | ❌ Pendiente |
| — | Motivation — Frases y consejos | ❌ Pendiente |
| — | StudyGroups — Sesiones grupales + chat | ❌ Pendiente |
| — | AiAssistant — Asistente con DeepSeek | ❌ Pendiente |
| — | Admin — Panel de administración | ❌ Pendiente |

---

## Fase 0 — Fundamentos visuales (✅ Completo)

### HU-00-01 — Sistema de diseño con tokens semánticos

**Como** desarrollador del frontend, **quiero** un sistema de diseño basado en tokens CSS y no en colores literales, **para** poder cambiar de tema o paleta sin reescribir componentes.

- 5 paletas de color completas: **Kawaii** (rosa pastel), **Bosque** (teal/esmeralda), **Océano** (azul índigo), **Nube** (monocromático con acento violeta), **Mono** (gris neutro, sin croma).
- Modo **claro** y **oscuro** funcionales en todas las paletas.
- Dos modos de superficie: **Neumorfismo** (sombras dobles, sin fondo) y **Vidrio** (`backdrop-filter: blur`, con fondo de pantalla obligatorio).
- Contraste verificado: texto normal ≥4.5:1, elementos interactivos ≥3:1.
- `border-interactive` agregado para cumplir el mínimo de 3:1 en bordes interactivos (hallazgo real de accesibilidad, corregido).

### HU-00-02 — Componentes base reutilizables

**Como** desarrollador, **quiero** componentes base (Botón, Tarjeta, Input, Select, Modal, Badge, EmptyState, Spinner) ya construidos y probados, **para** no tener que diseñarlos desde cero en cada módulo.

- `BaseButton.vue` — variantes: primary, secondary, ghost, danger
- `BaseCard.vue` — elevaciones: flat, raised
- `BaseInput.vue`, `BaseSelect.vue` (con prop `compact` para Pomodoro)
- `BaseModal.vue`, `BaseBadge.vue`, `EmptyState.vue`, `LoadingSpinner.vue`
- `ProgressBar.vue`, `StreakFlame.vue`
- Escala tipográfica (12–36px) y de espaciado (4–64px) basada en rejilla de 4px

### HU-00-03 — Navegación responsive

**Como** estudiante, **quiero** una navegación que se adapte a mi dispositivo: barra lateral en escritorio y barra inferior en móvil, **para** moverme cómodamente entre módulos.

- `AppLayout.vue` con sidebar (260px) en escritorio, bottom nav en móvil.
- 5 íconos principales: Inicio, Hábitos, Pomodoro, Misiones, Perfil.
- Menú secundario con Ajustes y Salir.
- "Salir" resaltado en rojo en el footer del sidebar.
- Modo Vidrio: navegación semi-translúcida (opacidad 35%) con fondo de pantalla visible.
- Fix real: el header móvil era invisible en Vidrio (hallazgo corregido con `z-10`).

### HU-00-04 — Preferencias de tema persistentes

**Como** estudiante, **quiero** cambiar entre tema claro/oscuro, modo neumorfismo/vidrio y paleta de color desde Ajustes, **para** personalizar mi experiencia.

- `ThemeToggle.vue` — botón sol/luna en el header.
- `SurfaceModeToggle.vue` y `PaletteToggle.vue` en `/settings`.
- Las preferencias son device-only (localStorage) por ahora.
- En modo Vidrio el tema se fuerza a oscuro y el botón de tema desaparece.

---

## Fase 1 — Identity (✅ Completo)

### HU-01-01 — Registro de nuevo usuario

**Como** estudiante universitario, **quiero** registrarme con mi correo y contraseña, **para** acceder a la plataforma.

- Formulario con nombre, correo, contraseña, fecha de nacimiento, género, carrera.
- Lista cerrada de carreras agrupadas por estilo visual (Health, Business, Technical, Systems, Law).
- Validación: contraseña ≥10 caracteres, con letras y números, verificada contra HaveIBeenPwned.
- Términos y Condiciones con checkbox obligatorio (página `/terms`).
- La verificación de correo está desactivada (decisión D-V por muestra multi-institucional).

### HU-01-02 — Inicio de sesión

**Como** estudiante registrado, **quiero** iniciar sesión con mi correo y contraseña, **para** acceder al dashboard.

- Hero visual (`login-hero.webp`) en columna izquierda (escritorio) / arriba (móvil).
- "Continuar con Google" disponible como alternativa (backend pendiente de cablear, botón visible pero deshabilitado).
- "Mantener sesión iniciada" y "¿Olvidaste tu contraseña?".
- Límite: 5 intentos por minuto, bloqueo tras 10 fallos.

### HU-01-03 — Completar perfil post-registro

**Como** nuevo participante, **quiero** completar mi perfil (institución, ciclo), **para** que el sistema asigne el estilo visual correcto a mi avatar.

- Pantalla `CompleteProfile.vue` con campos de institución (universidad/instituto), ciclo (1-10), y sincronización automática de `avatar_style` según la carrera elegida.
- Selector de género del avatar con botones visuales (🧑/👩).

### HU-01-04 — Consentimiento informado

**Como** participante del estudio, **quiero** leer y aceptar el consentimiento informado, **para** formar parte de la investigación de 66 días.

- Documento estructurado en 5 secciones (propósito, datos, privacidad, voluntariedad, contacto).
- Checkbox de aceptación obligatorio.
- "No acepto" redirige al logout.
- Idempotente: no se puede otorgar consentimiento dos veces.

### HU-01-05 — Preferencias de cuenta

**Como** estudiante, **quiero** configurar mi modo de superficie y notificaciones, **para** adaptar la app a mi gusto.

- `UserPreferences` 1:1 con usuario, creada automáticamente al registrarse.
- `surface_mode`: neumorfismo (default) o vidrio.
- `notifications_enabled`: false por defecto, se activa solo con permiso del navegador.

### HU-01-06 — Seguridad por diseño

**Como** administrador del estudio, **quiero** que la aplicación aplique cabeceras de seguridad, rate limiting y cifrado de datos sensibles, **para** cumplir con la Ley N.º 29733 y su reglamento.

- CSP con nonce por request (`script-src`, `frame-src https://www.youtube-nocookie.com`).
- HSTS, X-Frame-Options, Permissions-Policy activos.
- Contraseñas con bcrypt cost 12.
- 2FA obligatorio para administradores.
- Cifrado en reposo del diario de bienestar, datos de contacto y mensajes de IA.

---

## Fase 2 — Telemetría (✅ Completo)

### HU-02-01 — Registro de eventos de uso

**Como** investigador, **quiero** que cada acción significativa del usuario (completar hábito, iniciar Pomodoro, ver ranking) se registre como un evento de telemetría, **para** tener evidencia empírica del comportamiento real (Pilar 3 del estudio).

- Composable `useTelemetry().track()` en el frontend.
- Buffer en el cliente: se acumulan hasta 20 eventos o 30 segundos, luego se envían en lote.
- `navigator.sendBeacon` al cerrar pestaña (Safari móvil incluido, con `pagehide` y `visibilitychange`).
- Inserción en lotes de 100 filas (único INSERT múltiple, nunca uno por evento).
- Catálogo de ~30 eventos en 8 categorías: habits, pomodoro, missions, gamification, villains, ai, wellbeing, social, app.

### HU-02-02 — Eventos de backend por bus de eventos

**Como** desarrollador del backend, **quiero** que los eventos de dominio (HabitCompleted, PomodoroCompleted, XpAwarded, etc.) se capturen automáticamente por un listener de telemetría, **para** no tener que llamar manualmente a la telemetría desde cada caso de uso.

- `DomainEventTelemetryListener` mapea clases de eventos a nombres del catálogo.
- El fallo de telemetría **nunca** rompe la acción del usuario (try/catch que traga la excepción intencionalmente).
- Los fallos se loguean en `telemetry-fail-*.log` con retención de 90 días.

### HU-02-03 — Exportación para análisis estadístico

**Como** investigador, **quiero** exportar los datos de telemetría en CSV, **para** alimentar el análisis estadístico del estudio.

- Tres formatos: `events_raw.csv` (todos los eventos), `daily_per_user.csv` (agregado diario), `summary_per_user.csv` (resumen del período).
- Contiene `participant_code`, nunca nombre real ni correo.
- Se ejecuta fuera de horario de uso (3 AM) por el límite de 1 núcleo de CPU.

---

## Fase 3 — Hábitos (✅ Completo)

### HU-03-01 — Crear y gestionar hábitos

**Como** estudiante, **quiero** crear hábitos de estudio con una categoría y frecuencia, **para** registrar mis rutinas diarias.

- Crear hábito con título, categoría (estudio, sueño, ejercicio, alimentación, otro), frecuencia e icono.
- Máximo 5 hábitos completados con XP por día (tope que protege la validez del dato).
- Editar y eliminar hábitos (borrado lógico, el historial se conserva).

### HU-03-02 — Marcar hábitos como completados

**Como** estudiante, **quiero** marcar un hábito como cumplido en el día, **para** ver mi progreso y ganar XP.

- Marcado con un solo clic, con animación y toast de "+XP" (el XP real que otorgó Gamification).
- Desmarcar un hábito (toggle) — no duplica XP el mismo día (idempotencia).
- Si se marca fuera del día correspondiente, `is_late=true` en telemetría.
- Heatmap mensual con cuadrícula Lu-Do y celdas de completado.

### HU-03-03 — Archivar hábitos

**Como** estudiante, **quiero** archivar un hábito que ya no me sirve sin perder su historial, **para** mantener limpia mi lista activa.

- Archivar/desarchivar con `ArchiveHabitUseCase` y `UnarchiveHabitUseCase`.
- Los archivados aparecen en sección colapsable al final de la lista.

---

## Fase 4 — Gamificación (✅ Completo)

### HU-04-01 — Ganar XP por acciones

**Como** estudiante, **quiero** ganar puntos de experiencia (XP) al completar hábitos, pomodoros, misiones y entradas de diario, **para** progresar en el juego.

- **Hábito**: 10 XP (tope 5/día = 50 XP)
- **Pomodoro**: 15 XP (tope 8/día = 120 XP)
- **Misión fácil/media/difícil**: 20/30/40 XP (tope 3/día)
- **Subtarea**: 5 XP
- **Entrada de diario**: 10 XP (tope 1/día)
- **Villano derrotado**: 100 XP (tope 1/semana)
- Techo diario base: ~300 XP, con racha máxima ~450 XP.

### HU-04-02 — Subir de nivel y fase

**Como** estudiante, **quiero** subir de nivel (1 a 50) y desbloquear nuevas fases del avatar (10 fases de 5 niveles cada una), **para** ver la evolución visual de mi personaje.

- Curva: `XP(n) = 100 + (n-1) × 45`.
- Nadie alcanza el nivel 50 en 66 días (diseño intencional para generar varianza estadística).
- Proyección: perfil bajo = nivel 8, medio = 19, alto = 28, máximo = 35.
- Idempotencia garantizada por índice único `(user_id, source_type, source_id)`.

### HU-04-03 — Racha con días de gracia

**Como** estudiante, **quiero** que mi racha no se pierda si fallo un día, **para** no desmotivarme y abandonar el estudio.

- Mínimo diario: 1 hábito o 1 pomodoro.
- 3 días de gracia por mes calendario (congelan la racha, no la rompen).
- Multiplicador de XP por racha: +10% por semana, tope +50% (35+ días).
- Registro en telemetría de cada uso de gracia.

### HU-04-04 — Dashboard con progreso

**Como** estudiante, **quiero** ver mi nivel, fase, XP total y racha en el dashboard, **para** tener visibilidad de mi avance.

- Tarjeta "Tu progreso" en `/dashboard`.
- `UserProgressReaderInterface` expone getters para que otros módulos lean el progreso.

---

## Fase 5 — Pomodoro (✅ Completo)

### HU-05-01 — Temporizador de enfoque

**Como** estudiante, **quiero** un temporizador Pomodoro que funcione incluso si cierro el navegador sin querer, **para** no perder mi sesión de estudio por accidentes técnicos.

- **Temporizador en el cliente**, sincronizado con el servidor solo al iniciar/pausar/completar/abandonar.
- `ResolveStaleSessionUseCase`: si una sesión quedó `running` y ya debería haber terminado, se completa sola al volver a visitar la página.
- Validación anti-manipulación: el servidor rechaza sesiones donde el tiempo transcurrido sea <95% del planificado.
- Solo una sesión activa por usuario a la vez.
- Rango configurable: 15 a 50 minutos de foco.

### HU-05-02 — Ciclo completo foco-descanso

**Como** estudiante, **quiero** que el Pomodoro siga el ciclo clásico (foco → descanso → foco), **para** aplicar la técnica correctamente sin tener que gestionarlo manualmente.

- Al completar el foco: suena un tono (Web Audio API), vibra el dispositivo, y arranca el descanso automáticamente.
- Al terminar el descanso: vuelve a sonar y arranca un nuevo foco solo.
- Botón "Saltar descanso" para cortar el ciclo antes.
- Relación foco/descanso validada: descanso máximo = 40% del foco (investigado contra la técnica real).

### HU-05-03 — Descanso largo cada 4 ciclos

**Como** estudiante, **quiero** un descanso más largo después de 4 sesiones de enfoque, **para** seguir la técnica Pomodoro clásica (Cirillo).

- `longBreakMinutes = clamp(descanso_corto × 3, 15, 30)`.
- Con el default (5 min) da 15 min; con 10 min de descanso corto da 30 min.

### HU-05-04 — Meta de estudio diaria

**Como** estudiante, **quiero** fijar una meta de minutos de foco para el día, **para** tener un objetivo concreto de estudio.

- Selector de "Sin meta" a "6 horas".
- El progreso se calcula contra `todaySessions` (verdad del servidor).
- Al cumplir la meta, el ciclo automático se corta y muestra "¡Meta cumplida!" con opción de seguir.
- Se persiste en localStorage (no es dato de investigación).

### HU-05-05 — Música de fondo opcional

**Como** estudiante, **quiero** activar música de fondo mientras estudio, **para** concentrarme mejor.

- Botón "Activar música" — nunca se carga sin clic explícito.
- Playlist por defecto (BreakingCopyright Lofi) verificada con oEmbed.
- Opción de pegar URL de playlist propia.
- Usa `youtube-nocookie.com` para reducir tracking.
- No recuerda el estado "encendido" entre visitas.

### HU-05-06 — Historial de sesiones

**Como** estudiante, **quiero** ver mi historial de sesiones de hoy, el resumen de la semana y estadísticas del período, **para** evaluar mi rendimiento.

- Sesiones de hoy: hora, duración, misión vinculada, resultado.
- Resumen semanal con barras de minutos de foco.
- Estadísticas: sesiones completadas, tasa de finalización, minutos de foco totales, racha de días con Pomodoro.
- Filtros: hoy, 7 días, 30 días, todo el período.

---

## Fase 6 — Calendar (✅ Completo)

### HU-06-01 — Calendario con feriados peruanos

**Como** investigador, **quiero** que el sistema conozca los 16 feriados nacionales y las semanas de examen del período académico, **para** distinguir caídas de adherencia por feriado de caídas por desmotivación.

- 16 feriados peruanos 2026 cargados por seeder (Jueves/Viernes Santo ya resueltos: 2 y 3 de abril).
- Días no laborables distinguidos de feriados (`type='non_working'` vs `type='holiday'`).
- Semanas de examen del ciclo 2026-2 configuradas en `config/academic.php`.
- Cache de 24 horas para consultas repetidas.
- **Vista unificada con interfaz propia** (`/calendar`): muestra feriados, semanas de examen y misiones por fecha de vencimiento en una cuadrícula mensual.

### HU-06-02 — Contrato de lectura compartido

**Como** desarrollador de Wellbeing, Missions y Telemetry, **quiero** consultar fechas sin duplicar lógica, **para** mantener el principio DRY.

- `CalendarReaderInterface` en `Shared/Domain/Contracts/`.
- Métodos: `isHoliday`, `isNonWorkingDay`, `isExamWeek`, `getHolidayName`, `interventionDayFor`, `holidaysInMonth`.

---

## Fase 7 — Misiones (✅ Completo)

### HU-07-01 — Crear tareas académicas con subtareas

**Como** estudiante, **quiero** crear una tarea (misión) y dividirla en pasos pequeños, **para** que una tarea grande no se sienta abrumadora.

- Crear misión con título, descripción, dificultad (fácil/media/difícil), fecha de vencimiento.
- 0 a 20 subtareas por misión, reordenables por arrastre (drag & drop nativo HTML5).
- Si la misión es difícil y no tiene subtareas, el sistema **sugiere** dividirla (no obliga).
- Prioridad: baja, normal, alta (default normal).

### HU-07-02 — Estados automáticos

**Como** investigador, **quiero** que el estado de una misión refleje actividad real, no un clic manual, **para** tener datos de telemetría más fiables.

- `pending` → `in_progress`: al completar la primera subtarea o iniciar un Pomodoro vinculado.
- `in_progress` → `completed`: al completar todas las subtareas.
- `pending`/`in_progress` → `overdue`: al pasar la fecha sin completar (cron diario 00:05).
- `overdue` → `completed`: se puede completar tarde igualmente.

### HU-07-03 — Medir procrastinación

**Como** investigador, **quiero** un número que capture si el estudiante entregó antes o después de lo previsto, **para** tener la medida conductual de procrastinación más directa del sistema.

- `days_early_or_late` se calcula **una sola vez** al completar: negativo si antes, positivo si después.
- **Nunca se recalcula** (es inmutable una vez escrito).
- Una misión sin fecha de vencimiento no genera esta métrica.

### HU-07-04 — Vistas del módulo

**Como** estudiante, **quiero** ver mis misiones en lista y detalle, **para** organizarme según lo que necesite cada día.

- **Lista**: orden por defecto: vencidas → vence hoy → vence esta semana → resto → completadas colapsadas.
- Reordenable por: fecha, prioridad, dificultad o creación.
- **Detalle**: misión con subtareas, botón "Enfocarme" que navega a Pomodoro con `mission_id`.
- Vista de misiones completadas con `days_early_or_late` visible.
- Botón "Completar misión" manual.

### HU-07-05 — XP por misiones

**Como** estudiante, **quiero** ganar XP al completar misiones, **para** progresar en el juego.

- XP por dificultad: fácil=20, media=30, difícil=40.
- Tope: 3 misiones con XP por día (la cuarta se completa sin XP).
- Subtareas: 5 XP cada una (dentro del tope de la misión).
- Completar una subtarea de una misión ya completada no da XP.

---

## Avatares — Assets visuales (✅ Bloque 1)

### HU-AV-01 — Avatar Funko Pop visible

**Como** estudiante, **quiero** ver un avatar con estilo Funko Pop que evoluciona según mi fase, **para** sentir que mi progreso tiene una representación visual.

- 36 PNG reales conectados y funcionales (primer bloque entregado por el usuario).
- 5 estilos de carrera: Base (fase 1), Medicina (fases 2-4), Técnico (fase 2, solo femenino), Business/Systems/Law (sin arte aún, caen a Base).
- 4 posiciones: parado normal (Dashboard), parado saludando (Hábitos), sentado (Misiones, reservado), sentado con laptop (Pomodoro).
- `AvatarAssetResolver` con resolución dinámica por `file_exists` — no requiere tocar código al agregar nuevos assets.
- **Selector por módulo**: cada módulo muestra una posición fija del avatar, con fase aleatoria entre las disponibles.
- Dashboard: avatar grande en tarjeta "Tu progreso".

---

## Pendientes — Próximas fases a construir

### Wellbeing — Diario de ánimo
- Registro emocional con escala 1-5, texto opcional, etiquetas cerradas (8 etiquetas).
- Calendario mensual con emoji del promedio diario.
- Cifrado en reposo del contenido.
- Alerta de bienestar si promedio ≤2 durante 5 días consecutivos.
- Resumen numérico para el asistente de IA (sin contenido sensible).

### Villains — Villanos semanales
- 5 villanos basados en el diagnóstico real de la encuesta (n=98).
- Asignación semanal automática (lunes 00:00).
- HP variable según semana de intervención (80/100/120).
- Derrota otorga 100 XP + fondo de pantalla.

### Ranking
- Vista dedicada en `/ranking` (nunca widget en dashboard).
- Sin notificaciones de cambio de posición.
- Solo alias, nivel y posición.
- Cache de 7 minutos.

### Personalization
- Selector de fondos de pantalla (catálogo cerrado).
- Desbloqueo de fondos al derrotar villanos.
- Aplicación sin recargar la página.

### Achievements — Logros e insignias
- 6 categorías: Constancia, Volumen, Progresión, Villanos, Bienestar, Puntualidad.
- Sin logros de comparación social ni penalizaciones.
- Evaluación en cola (no bloquea la respuesta).
- Catálogo cerrado cargado por seeder.

### Motivation — Frases y consejos
- 10 frases motivacionales al iniciar sesión (rotación cíclica, sin repetición hasta agotar catálogo).
- 13 consejos de uso por módulo (tarjeta descartable).
- Excepción al congelamiento de despliegue: se puede ampliar durante la intervención.

### StudyGroups — Sesiones grupales
- Polling AJAX cada 5 segundos (no WebSockets, el hosting no lo permite).
- Máximo 5 participantes por sesión.
- Moderación básica + reportes.
- Purga de mensajes a los 7 días.

### AiAssistant — Asistente DeepSeek
- Cuota diaria de 5 consultas.
- Llamadas en cola (no bloquean el worker).
- Guardrails obligatorios: sin consejo clínico, protocolo de derivación de crisis.
- Contexto solo con datos agregados (nunca texto del diario).

### Admin — Panel de investigación
- 6 vistas: Dashboard, Participantes, Deserción, Telemetría, Exportación, Salud del sistema.
- Solo lectura (no puede modificar XP ni telemetría).
- 2FA obligatorio.
- Exportación de los 3 CSV del dataset.

---

## Resumen del proyecto

| Métrica | Valor |
|---------|-------|
| Módulos implementados | 8 de 16 |
| Tests pasando | ~59 |
| Paletas de color | 5 (Kawaii, Bosque, Océano, Nube, Mono) |
| Assets de avatar | 36 PNG (de 400 planificados) |
| Eventos de telemetría | ~30 en 8 categorías |
| Ventana de intervención | 66 días (07/09/2026 — 11/11/2026) |
| Hosting | Hostinger Premium, PHP 8.3, 1 núcleo |
| Framework | Laravel 12 + Vue 3 + Inertia 2 + TailwindCSS 3.4 |
