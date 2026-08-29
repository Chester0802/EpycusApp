# Historias de usuario y criterios de aceptacion (Unificado)

> Este documento unifica todas las historias de usuario y criterios de aceptacion del proyecto Epycus.
> **ESTADO DEL PROYECTO (al 2026-08-17): TODAS LAS FASES IMPLEMENTADAS Y VERIFICADAS (131 TESTS PASANDO, 419 ASSERTIONS, 0 ERRORES PHPSTAN NIVEL 6)**
>
> **Formato de cada historia:**
> Como [rol], quiero [accion], para [beneficio]. Seguido de criterios de aceptacion en formato Given/When/Then.

---

## Indice de Modulos Implementados

| # | Modulo | Estado | Ubicacion Backend | Ubicacion Frontend |
|---|--------|--------|-------------------|--------------------|
| 0 | Fundamentos visuales | Completo | - | `resources/js/` |
| 1 | Identity (Autenticacion y perfil) | Completo | `app/Modules/Identity/` | `Pages/Auth/`, `Pages/Identity/` |
| 2 | Telemetria | Completo | `app/Modules/Telemetry/` | (solo backend) |
| 3 | Habitos | Completo | `app/Modules/Habits/` | `Pages/Habits/Index.vue` |
| 4 | Gamificacion (XP, niveles, rachas, monedas) | Completo | `app/Modules/Gamification/` | `Pages/Dashboard.vue` |
| 5 | Pomodoro | Completo | `app/Modules/Pomodoro/` | `Pages/Pomodoro/Index.vue` |
| 6 | Calendar | Completo | `app/Modules/Calendar/` | `Pages/Calendar/Index.vue` |
| 7 | Missions | Completo | `app/Modules/Missions/` | `Pages/Missions/` |
| 8 | Avatares (en Identity) | Completo | `app/Modules/Identity/` | `Components/ProceduralAvatar.vue` |
| 9 | Wellbeing | Completo | `app/Modules/Wellbeing/` | `Pages/Wellbeing/` |
| 10 | Villains | Completo | `app/Modules/Villains/` | `Pages/Villains/Index.vue` |
| 11 | Ranking + Personalization | Completo | `app/Modules/Ranking/` | `Pages/Ranking/Index.vue` |
| 12 | Achievements | Completo | `app/Modules/Achievements/` | `Pages/Achievements/Index.vue` |
| 13 | Motivation | Completo | `app/Modules/Motivation/` | (integrado en Dashboard) |
| 14 | StudyGroups | Completo | `app/Modules/StudyGroups/` | `Pages/StudyGroups/` |
| 15 | AiAssistant | Completo | `app/Modules/AiAssistant/` | `Pages/AiAssistant/Index.vue` |
| 16 | Admin | Completo | `app/Modules/Admin/` | `Pages/Admin/Index.vue` |
| 17 | Buzon de la Comunidad | Completo | `app/Http/Controllers/FeedbackController.php` | `Pages/Welcome.vue#buzon` |
| 18 | Landing Page Publica | Completo | `routes/web.php` | `Pages/Welcome.vue` |

---

## Fase 0 - Fundamentos visuales

### HU-00-1 - Sistema de diseno con tokens semanticos
Como desarrollador del frontend, quiero un sistema de diseno basado en tokens CSS y no en colores literales, para poder cambiar de tema o paleta sin reescribir componentes.
- Given las paletas definidas, when se aplican, then existen 5 completas: Kawaii, Bosque, Oceano, Nube, Mono.
- Given el modo de color, when se alterna, then los modos claro y oscuro son funcionales en todas las paletas.
- Given las superficies, when se renderizan, then existen Neumorfismo y Vidrio (con fondo de pantalla).
- Given los elementos, when se evalua el contraste, then texto normal es mayor o igual a 4.5:1 y elementos interactivos mayor o igual a 3:1 (con border-interactive).

### HU-00-2 - Componentes base reutilizables
Como desarrollador, quiero componentes base ya construidos, para no redisenarlos.
- Given la necesidad de un boton, when se usa BaseButton.vue, then soporta variantes primary, secondary, ghost, danger.
- Given las vistas modulares, when se arman, then se cuenta con BaseCard, BaseInput, BaseSelect, BaseModal, BaseBadge, ProgressBar, DonutChart, RadialProgressRing, UsageTipBanner, StudentIdCard.

### HU-00-3 - Navegacion responsive
Como estudiante, quiero una navegacion que se adapte a mi dispositivo, para moverme comodamente.
- Given pantalla de escritorio, when se renderiza, then muestra sidebar de 260px.
- Given pantalla movil, when se renderiza, then muestra bottom nav con iconos del modulo activo.
- Given modo Vidrio, when se activa, then la navegacion es semi-translucida sin ocultar contenido.

### HU-00-4 - Preferencias de tema persistentes
Como estudiante, quiero cambiar mis preferencias, para personalizar mi experiencia.
- Given el header, when se usa el boton sol/luna, then alterna el modo claro/oscuro y persiste en localStorage (epycus.theme).
- Given el modo Vidrio, when se activa, then se adapta la transparencia y tema visual.

### HU-00-5 - Modo claro / modo oscuro en la Landing
Como visitante de la landing publica (epycus.es), quiero alternar entre modo claro y oscuro, para ver el sitio en el modo que prefiero.
- Given el navbar de la landing, when se presiona el boton Sol/Luna, then la preferencia se guarda en localStorage y toda la pagina se adapta instantaneamente sin recargar.

---

## Fase 1 - Identity

### HU-ID-1 - Registro e Inicio de Sesion
Como estudiante universitario, quiero registrarme e iniciar sesion con mi correo o con mi cuenta de Google, para acceder de manera rapida y segura a la plataforma.
- Given el formulario de registro estandar, when se envian los datos, then valida contrasena fuerte (mayor o igual a 10 caracteres, letras y numeros, HIBP).
- Given el boton "Continuar con Google", when el usuario autoriza la app, then se crea o vincula su cuenta mediante google_id (columna en users) y accede de inmediato.
- Given una cuenta autenticada con Google, when visita /profile, then se oculta el formulario de cambio de contrasena y se muestra un panel de seguridad gestionado por Google.

### HU-ID-2 - Diagnostico Inicial EPA y Celebracion
Como estudiante participante, quiero completar el cuestionario EPA inicial, para diagnosticar mis habitos de estudio y ser recompensado.
- Given el cuestionario EPA en /epa, when se envian las respuestas de los 8 items, then se otorgan +50 XP, se dispara una animacion de confeti a 60 FPS con chime de audio melodico y se muestra un toast celebratorio.

### HU-ID-3 - Completar perfil, Carrera y Avatar
Como participante, quiero completar mi institucion y carrera universitaria, para personalizar mi perfil y recibir el avatar que corresponde a mi area de estudio.
- Given la seleccion de carrera (catalogo cerrado de 11 carreras agrupadas en 5 estilos: health, business, technical, systems, law), when se guarda, then sincroniza automaticamente el avatar_style y los rangos profesionales correspondientes.
- Given el listado de carreras, when se muestra, then incluye: Medicina, Enfermeria, Obstetricia, Administracion de Empresas, Contabilidad, Ingenieria Civil, Ingenieria Industrial, Ingenieria de Minas, Arquitectura, Ingenieria de Sistemas, Derecho.
- Given el formulario de perfil, when se guarda el avatar personalizado, then se actualizan los campos avatar_options (JSON con opciones: color de piel, cabello, accesorios) y avatar_gender (m o f).

### HU-ID-4 - Consentimiento informado
Como participante, quiero aceptar el consentimiento, para ingresar al estudio.
- Given la pantalla de consentimiento (/consent), when se acepta, then no vuelve a solicitarse (idempotente - ConsentAlreadyGrantedException).

### HU-ID-5 - Seguridad
Como administrador, quiero seguridad estricta, para cumplir la ley N 29733.
- Given respuestas HTTP, when se envian, then incluyen CSP, HSTS y no-frame.
- Given datos sensibles, when se guardan, then el diario y mensajes de IA estan cifrados en reposo.

---

## Fase 2 - Telemetria

### HU-TEL-1 - Registro de eventos
Como investigador, quiero registrar cada accion en la plataforma, para tener evidencia empirica del comportamiento del estudiante.
- Given acciones en frontend, when ocurren, then se almacenan en un buffer JS y se envian en lotes a POST /telemetry/events.

### HU-TEL-2 - Eventos de dominio
Como backend, quiero escuchar eventos de dominio con el listener DomainEventTelemetryListener, para automatizar telemetria sin codigo repetido.
- Given eventos como HabitCompleted, PomodoroCompleted, MissionCompleted, when se emiten, then el listener los captura y persiste sin romper la peticion principal en caso de error.

### HU-TEL-3 - Exportacion de datos
Como investigador, quiero exportar CSV desde el panel Admin, para analisis estadistico.
- Given la exportacion (GenerateDatasetCsvUseCase), when corre, then produce archivos agrupados y detallados, sin datos personales, de forma asincrona a las 3 AM.

---

## Fase 3 - Habitos

### HU-HAB-1 - Gestion completa de habitos
Como estudiante, quiero crear, editar, archivar y eliminar habitos con frecuencia, para mantener rutinas de estudio organizadas.
- Given un nuevo habito, when se guarda, then requiere categoria y frecuencia.
- Given un habito existente, when se archiva (ArchiveHabitUseCase), then ya no aparece en la lista activa pero se conserva en la base de datos.
- Given un habito archivado, when se desarchiva (UnarchiveHabitUseCase), then vuelve a la lista activa.
- Given un habito, when se elimina (DeleteHabitUseCase), then se elimina logicamente (soft delete).
- Given un habito, when se actualiza (UpdateHabitUseCase), then persiste solo el nombre, categoria y frecuencia - nunca el historial de completados.

### HU-HAB-2 - Completar habitos con retroalimentacion audiovisual
Como estudiante, quiero marcar habitos cumplidos y recibir estimulos visuales y auditivos, para reforzar mi motivacion intrinseca.
- Given un habito marcado, when se completa o desmarca (ToggleHabitCompletionUseCase), then dispara confeti de particulas en pantalla, reproduce un timbre armonico (Web Audio API) y otorga 10 XP respetando el tope diario (5 habitos/dia por defecto).
- Given un habito, when se marca o desmarca, then la accion es idempotente.

### HU-HAB-3 - Plantillas atomicas rapidas (1-clic)
Como estudiante, quiero seleccionar un habito predefinido basado en ciencia, para no perder tiempo configurandolo.
- Given el modal de creacion de habito, when se muestra, then incluye plantillas atomicas por categoria (repaso activo 20 min, revisar notas del dia, dejar pantallas antes de dormir, agua al despertar, pausa activa, diario de bienestar).
- Given una plantilla, when se selecciona, then rellena automaticamente titulo, frecuencia y categoria sin obligar a confirmar inmediatamente.

### HU-HAB-4 - Habit Stacking y Momento del dia
Como estudiante, quiero anclar un habito a otro que ya hago, para aprovechar la inercia de mis rutinas existentes.
- Given el campo `cue_trigger` en el formulario de creacion, when se rellena (ej: "Despues de desayunar"), then se guarda y aparece como badge `🔗 Despues de...` en la tarjeta del habito.
- Given el filtro por momento del dia, when se selecciona (Manana, Tarde, Noche), then solo se muestran los habitos con `time_of_day` correspondiente.
- Given el campo `time_of_day`, when no se selecciona, then el habito aparece en todos los filtros (valor `anytime`).

### HU-HAB-5 - Vista semanal de 7 dias y heatmap mensual
Como estudiante, quiero ver de un vistazo mi cumplimiento semanal y mensual, para motivarme con mi progreso visual.
- Given la vista semanal (tira de 7 dias, Lu a Do), when se muestra, then permite marcar/desmarcar cualquier dia de la semana actual con 1 clic.
- Given la vista heatmap mensual, when se muestra, then renderiza una cuadricula de intensidad de color segun el porcentaje de habitos completados por dia.
- Given el selector de vista, when se alterna entre semanal y mensual, then el cambio es instantaneo sin recarga.

---

## Fase 4 - Gamificacion

### HU-GAM-1 - XP y Niveles
Como estudiante, quiero ganar XP con cada accion completada, para subir de nivel y progresar visualmente.
- Given cualquier accion premiada (AwardXpUseCase), when se completa, then otorga XP respetando topes diarios. Valores: habito=10, pomodoro=15, mision facil=20/media=30/dificil=40, subtarea=5, diario=10, villano derrotado=100.
- Given el nivel maximo, when se alcanza el nivel 50, then el XP sigue acumulandose pero no sube de nivel.

### HU-GAM-2 - Racha y dias de gracia
Como estudiante, quiero no perder mi racha por un dia de descanso, para no desmotivarme.
- Given que no se registra actividad en un dia, when el usuario tiene dias de gracia disponibles (hasta 3/mes - EvaluateStreaksUseCase), then congela la racha sin romperla.
- Given un bonus de racha, when el usuario mantiene la racha semanal, then recibe un bonus de +10% de XP por semana (maximo +50%).

### HU-GAM-3 - Monedas (Coins)
Como estudiante, quiero acumular monedas junto al XP, para desbloquear contenido visual.
- Given cualquier accion premiada, when se otorga XP, then tambien se calculan monedas a razon de 1 moneda cada 10 XP.
- Given el Dashboard, when carga, then muestra el saldo de monedas acumulado en la StudentIdCard.

---

## Fase 5 - Pomodoro

### HU-POM-1 - Temporizador resiliente y sincronizacion
Como estudiante, quiero que el temporizador siga activo si cambio de pestana o recargo la pagina, para no perder mi sesion de concentracion.
- Given un Pomodoro en curso (StartPomodoroUseCase), when se recarga la pagina, then GetActiveSessionUseCase calcula el tiempo restante exacto basado en la hora de inicio y estado persistido.
- Given un fin de foco, when llega a 0, then inicia el descanso automaticamente (CompletePomodoroUseCase o ResolveStaleSessionUseCase).
- Given un Pomodoro en pausa (PausePomodoroUseCase) o reanudado (ResumePomodoroUseCase), when el usuario cambia de vista y vuelve, then el estado es el mismo que dejo.
- Given un Pomodoro abandonado (AbandonPomodoroUseCase), when el usuario lo cancela, then no se otorga XP y el estado queda como abandoned.
- Given una sesion registrada, when se consulta en el historial, then el backend serializa la hora en zona America/Lima y el frontend renderiza en formato 12 horas.

### HU-POM-2 - Reproductor Universal de Musica de YouTube
Como estudiante, quiero pegar cualquier enlace de YouTube (videos, playlists, directos, shorts), para estudiar con mi musica favorita de fondo.
- Given un enlace valido o codigo iframe de YouTube, when el usuario lo ingresa, then el sistema extrae el ID de video o lista y lo embebe mediante youtube-nocookie.com.
- Given un enlace personalizado configurado, when el usuario regresa en futuras sesiones, then su enlace preferido permanece guardado en localStorage.

### HU-POM-3 - Vinculacion con Misiones
Como estudiante, quiero vincular un Pomodoro a una Mision concreta, para que el tiempo de foco cuente como avance de esa tarea.
- Given la vista de detalle de una mision, when se inicia un Pomodoro, then la sesion registra el mission_id en pomodoro_sessions (columna nullable).
- Given un Pomodoro vinculado completado, when se emite PomodoroCompleted, then la mision pasa automaticamente a in_progress si estaba en pending.

---

## Fase 6 - Calendar

Vista dedicada en /calendar con mapa mensual de actividades, gestion relacional de Cursos, Apuntes y Feriados Nacionales del Peru.

### HU-CAL-1 - Feriados peruanos oficiales (2025-2028)
Como estudiante y equipo de investigacion, quiero que el sistema conozca todos los feriados oficiales del Peru, para planificar mis estudios y distinguir caidas de adherencia por feriado.
- Given la tabla holidays, when corre HolidaySeeder, then se registran los 16 feriados oficiales segun D. Leg. 713 para 2025, 2026, 2027 y 2028 (con Jueves y Viernes Santo moviles calculados).
- Given la vista de calendario mensual, when se renderizan las celdas, then los feriados se muestran con la insignia roja F y el nombre descriptivo.

### HU-CAL-2 - Gestion de Cursos con Multi-Sesion y Rango de Fechas
Como estudiante universitario, quiero crear mis cursos con multiples horarios semanales y fechas de vigencia (starts_at a ends_at), para que mis clases aparezcan en el calendario solo durante las semanas lectivas reales.
- Given el formulario de creacion/edicion de curso, when se ingresa el nombre, color, fecha de inicio y fecha de fin, then se guarda en la tabla courses y permite asociar multiples sesiones semanales (course_sessions).
- Given la cuadricula mensual, when se visualiza un dia, then las clases se muestran unicamente si la fecha se encuentra dentro del rango [starts_at, ends_at] del curso.
- Given el selector de color, when se elige, then los valores disponibles son: primary, accent, success, warning, secondary.

### HU-CAL-3 - Apuntes Integrados por Curso y Carga de Imagenes
Como estudiante, quiero redactar apuntes y adjuntar capturas de pizarras o diapositivas para cada curso, para tener mi material de estudio centralizado en un solo lugar.
- Given la pestana de apuntes de un curso, when el estudiante escribe texto y adjunta imagenes (png, jpg, webp), then se guardan en course_notes y note_images permitiendo visualizacion y descarga inmediata.
- Given una clase registrada, when el usuario presiona eliminar, then se remueve de forma segura, perteneciendo unicamente a su cuenta.
- Given la vista de apuntes en movil, when se renderiza, then el nombre del curso y el boton Exportar no se superponen (responsividad garantizada).

---

## Fase 7 - Missions

Detalle completo (estados, subtareas, days_early_or_late) en docs/01-MODULOS.md seccion 4.

### HU-MIS-1 - Crear una mision con subtareas
Como estudiante, quiero crear una tarea academica y partirla en pasos chicos, para que una tarea grande deje de sentirse pesada.
- Given el formulario de nueva mision con dificultad hard sin subtareas, when se guarda, then el sistema sugiere dividirla ("Las tareas grandes se sienten menos pesadas por partes. Quieres dividirla?") - sugiere, no obliga.
- Given una mision ya creada, when se agregan subtareas (AddSubtaskUseCase), then el limite es 0 a 20 por mision, reordenables por arrastre (ReorderSubtasksUseCase).
- Given una subtarea, when se le intenta poner una fecha propia, then la UI no lo permite - la fecha es siempre de la mision.
- Given una mision sin fecha de vencimiento, when se guarda, then nunca genera days_early_or_late ni puede pasar a overdue.

### HU-MIS-2 - Transicion automatica a "en progreso"
Como investigador, quiero que el estado de una mision refleje actividad real, para tener un dato de telemetria mas fiable.
- Given una mision pending, when se completa su primera subtarea o se inicia un Pomodoro vinculado, then pasa a in_progress sola.
- Given una mision sin completarse, when pasa la fecha de vencimiento, then pasa a overdue (via cron diario 00:05 hora de Lima).
- Given una mision overdue, when se completa tarde, then pasa a completed con days_early_or_late positivo.

### HU-MIS-3 - Medir procrastinacion con days_early_or_late
Como investigador, quiero un numero que capture si el estudiante entrego antes o despues de lo previsto.
- Given una mision con fecha de vencimiento, when se completa (CompleteMissionUseCase), then days_early_or_late se calcula una sola vez: negativo si fue antes, positivo si despues.
- Given un valor ya calculado, when ocurre cualquier evento posterior (reabrir con UncompleteMissionUseCase, editar con UpdateMissionUseCase), then el valor nunca se recalcula - es inmutable una vez escrito.

### HU-MIS-4 - Completar subtareas y XP
Como estudiante, quiero que completar todas las subtareas cierre la mision sola.
- Given una mision con N subtareas, when se completa la ultima (ToggleSubtaskUseCase), then la mision pasa a completed automaticamente.
- Given una mision ya completed, when se completa una subtarea (caso de reapertura), then no se otorga XP.
- Given el tope diario (3 misiones/dia), when ya se cumplio, then la cuarta se completa igual pero sin XP.

### HU-MIS-5 - Vistas del modulo
Como estudiante, quiero ver mis misiones en lista y detalle, para elegir la vista que mejor se ajusta a como pienso ese dia.
- Given la vista Lista, when carga, then el orden es: vencidas mas antigua arriba, vencen hoy, vencen esta semana, resto, completadas al final.
- Given la vista Detalle (Missions/Detail.vue), when carga, then muestra subtareas y boton para iniciar Pomodoro vinculado.
- Given la vista Completadas, when carga, then cada fila muestra days_early_or_late visible.

### HU-MIS-6 - Tablero Kanban de 4 columnas
Como estudiante, quiero organizar mis misiones visualmente en un tablero Kanban, para ver de un vistazo el estado de todo mi trabajo.
- Given la vista Kanban, when se renderiza, then muestra 4 columnas: `Lista de Misiones` (pendientes), `En Proceso` (con subtareas parcialmente completadas), `En Revision` (100% subtareas completadas, esperando finalizacion), `Terminado` (completadas).
- Given una tarjeta tipo Post-it en cualquier columna, when se interactua, then permite marcar/desmarcar subtareas en tiempo real con barra de progreso porcentual visible.
- Given el campo `+ Anadir paso...` en un post-it, when se presiona Enter, then se crea una nueva subtarea en tiempo real via AddSubtaskUseCase.
- Given una mision en `En Revision`, when se presiona el boton de finalizar, then CompleteMissionUseCase la completa y pasa a la columna `Terminado` con recompensa de XP.

### HU-MIS-7 - Matriz de Eisenhower interactiva
Como estudiante, quiero clasificar mis misiones por urgencia e importancia, para enfocarme en lo que realmente importa.
- Given la vista de Misiones, when se activa el toggle Matriz de Eisenhower, then se renderiza una cuadricula 2x2 con los 4 cuadrantes estilizados: Q1 Hacer YA (rojo), Q2 Planificar (verde esmeralda, destacado como Zona Anti-Procrastinacion), Q3 Minimizar (ambar), Q4 Descartar (slate).
- Given una mision en cualquier cuadrante, when se arrastra o selecciona otro cuadrante, then ChangeQuadrantUseCase actualiza el `eisenhower_quadrant` via `POST /missions/{id}/quadrant`.
- Given el panel lateral de Pomodoro con misiones activas, when se muestra, then incluye badges de cuadrante (Q1, Q2, Q3, Q4) para guiar la priorizacion.
- Given el banner pedagogico de la Matriz, when se despliega, then muestra tips psicologicos sobre priorizacion de estudio y la zona anti-procrastinacion Q2.

---

## Fase 8 - Wellbeing

Detalle completo en docs/01-MODULOS.md seccion 5. Es el modulo con mayor sensibilidad de datos del proyecto.

### HU-WEL-1 - Registrar el animo del dia
Como estudiante, quiero registrar como me siento cuando quiera, con o sin texto, para llevar un registro emocional sin que se sienta una obligacion.
- Given el formulario de nueva entrada (CreateEntryUseCase), when se envia solo con mood_score (1-5), then se guarda valida - el texto es opcional, el animo es obligatorio.
- Given un dia cualquiera, when el usuario registra 4 entradas, then las 4 se guardan - no hay limite de entradas por dia.
- Given la primera entrada del dia, when se guarda, then otorga XP. La segunda entrada del mismo dia NO otorga XP.

### HU-WEL-2 - Calendario mensual con emoji persistente
Como estudiante, quiero ver de un vistazo como fue mi mes emocional, para notar patrones sin tener que abrir cada entrada.
- Given un dia con una sola entrada, when se pinta (GetMonthCalendarUseCase), then muestra el emoji de ese mood_score exacto.
- Given un dia con varias entradas, when se pinta, then muestra el emoji del promedio redondeado, no de la ultima entrada.
- Given un emoji ya pintado, when pasa el tiempo, then se queda de forma permanente.
- Given un clic en cualquier dia, when se abre (GetDayDetailUseCase), then muestra todas las entradas de ese dia con su hora, animo, texto y etiquetas.

### HU-WEL-3 - Etiquetas de catalogo cerrado
Como investigador, quiero que las etiquetas emocionales vengan de una lista cerrada, para poder agregarlas estadisticamente.
- Given el formulario de nueva entrada, when se muestran las etiquetas, then son exactamente estas 8 (seleccion multiple, opcional): estres, motivado, cansado, tranquilo, agobiado, enfocado, disperso, satisfecho. Sin campo de texto libre.

### HU-WEL-4 - Resumen numerico para el asistente de IA (sin contenido sensible)
Como responsable de etica, quiero que el asistente de IA reciba solo datos agregados de Wellbeing.
- Given GetMoodTrendUseCase, when se llama, then devuelve: promedio de animo de 7 dias, tendencia, etiquetas mas frecuentes, numero de dias con registro. Nunca el texto de las entradas.

### HU-WEL-5 - Alerta de bienestar (solo al participante)
Como estudiante en una racha de animo bajo, quiero ver un contacto de apoyo, para tener una salida visible sin que se rompa mi confidencialidad.
- Given un promedio diario menor o igual a 2 durante 5 dias consecutivos, when se detecta, then se muestra el contacto de una ONG solo al participante afectado.
- Given esa alerta, when se dispara, then no se notifica a ningun administrador.

---

## Fase 9 - Villains

Detalle completo de reglas de dano/HP en docs/03-GAMIFICACION.md seccion 6.

### HU-VIL-1 - Asignacion semanal automatica
Como estudiante, quiero recibir un villano tematico nuevo cada semana, para tener un objetivo narrativo concreto.
- Given cualquier participante, when consulta /villains sin instancia activa, then AssignWeeklyVillainUseCase asigna el Villano de la Semana (lunes a domingo hora de Lima).
- Given el catalogo expandido de 10 villanos (La Postergacion, La Distraccion, La Ansiedad, El Desorden, El Cansancio, El Sindrome del Impostor, El Perfeccionismo Paralizante, El Aislamiento Academico, La Sobrecarga/Burnout, La Ilusion de la Ultima Noche), when cambia la semana, then rota automaticamente.
- Given una instancia activa, when se renderiza en Villains/Index.vue, then muestra el rango de fechas en espanol legible.
- Given que el villano ya expiro (ExpireVillainUseCase), when el usuario completa una accion, then se marca como survived sin aplicar dano.

### HU-VIL-2 - Dano por eventos de otros modulos
Como estudiante, quiero que completar habitos, pomodoros y misiones dane al villano, para que mi trabajo normal tenga un efecto visible.
- Given HabitCompleted, PomodoroCompleted o MissionCompleted, when se emiten, then los listeners (HandleHabitCompleted, HandlePomodoroCompleted, HandleMissionCompleted) aplican dano via ApplyDamageUseCase.
- Given el HP del villano llega a 0 (GetCurrentVillainUseCase), when se detecta, then se emite VillainDefeated para que Achievements evalue logros de categoria "Villanos".

### HU-VIL-3 - Bestiario y Sala de Trofeos
Como estudiante, quiero ver un registro historico de todos los villanos que he enfrentado, para sentir mi progreso acumulado.
- Given la pestana Bestiario en /villains, when se renderiza, then muestra los 10 bosses academicos en cuadricula con su ilustracion, nombre y descripcion.
- Given un villano ya derrotado, when se muestra en el Bestiario, then indica el numero total de victorias acumuladas (`Vencido X veces`) y la fecha del ultimo triunfo.
- Given un villano nunca enfrentado, when se muestra en el Bestiario, then aparece como silueta con estado `Aun no enfrentado`.

### HU-VIL-4 - Botones de Ataque Directo inter-modulo
Como estudiante, quiero accesos directos desde la pantalla de Villanos para infligir dano, para no tener que navegar manualmente a cada modulo.
- Given el villano activo de la semana, when se muestra la seccion de acciones, then presenta 4 botones: `Iniciar Pomodoro` (navega a /pomodoro), `Resolver Mision` (navega a /missions), `Marcar Habito` (navega a /habits), `Escribir en Diario` (navega a /wellbeing).
- Given cada boton de ataque, when se presiona, then registra un evento de telemetria (`villains.action_clicked`) y navega al modulo correspondiente.

### HU-VIL-5 - Playbook Estrategico Cientifico
Como estudiante, quiero aprender estrategias reales para combatir cada villano en mi vida academica, para que la gamificacion tenga impacto pedagogico.
- Given el villano activo de la semana, when se despliega la seccion de estrategias, then muestra 3 tecnicas psicologicas probadas especificas para ese villano (ej: para El Sindrome del Impostor: Diario de Evidencia, Regla del 70%, Charla con Mentor).
- Given la guia de estrategias, when se abre, then registra un evento de telemetria (`villains.guide_opened`).

### HU-VIL-6 - Battle Log en Vivo
Como estudiante, quiero ver un feed cronologico de mis impactos contra el villano, para sentir que cada accion cuenta.
- Given la semana activa, when se renderiza el Battle Log, then muestra las entradas de dano en orden cronologico inverso (mas reciente arriba) con fuente, timestamp y puntos de dano.

---

## Fase 10 - Ranking + Personalization

### HU-RANK-1 - Tabla de posiciones como variable de control
Como investigador, quiero que el ranking no se convierta en la motivacion principal del estudio, para no contaminar la variable de constancia.
- Given el ranking (GetGlobalRankingUseCase, GetOwnPositionUseCase), when se implementa, then vive en /ranking exclusivamente - nunca en el Dashboard.
- Given un cambio de posicion, when ocurre, then no dispara ninguna notificacion.
- Given cualquier visita a /ranking, when ocurre, then se registra ranking.viewed en Telemetry.
- Given la lista de usuarios, when se renderiza, then solo muestra alias, nivel y posicion.
- Given el calculo, when se hace, then es por XP total y se cachea 7 minutos.

### HU-PERS-1 - Preferencias visuales del usuario
Como estudiante, quiero elegir tema y fondo de pantalla dentro de un catalogo curado.
- Given UpdatePreferencesUseCase, when el usuario cambia el tema, then se aplica sin recargar la pagina.
- Given el catalogo de fondos, when se muestra, then es cerrado - el usuario no sube fondos propios.
- Given un fondo desbloqueable al derrotar villanos (tabla user_unlocked_wallpapers), when el usuario no lo desbloqueo, then aparece bloqueado en el selector, no oculto.

---

## Fase 11 - Achievements

### HU-ACH-1 - Desbloqueo idempotente por evento
Como estudiante, quiero que mis logros se desbloqueen solos al cumplir la condicion.
- Given cualquier evento de dominio relevante, when se emite, then EvaluateAchievementsUseCase lo evalua en cola, no en la peticion del usuario.
- Given un logro ya desbloqueado, when se re-evalua, then no pasa nada - UNIQUE (user_id, achievement_id) garantiza desbloqueo unico.
- Given un logro desbloqueado, when se otorga, then da XP entre 20 y 100 segun rareza y puede desbloquear un fondo.

### HU-ACH-2 - Catalogo sin comparacion social ni penalizaciones
Como investigador, quiero que ningun logro premie compararse con otros ni castigue romper una racha.
- Given el catalogo (seeders), when se disena, then ninguna condicion usa posicion en ranking ni datos de otro usuario.
- Given cualquier evento negativo (romper racha, abandonar Pomodoro, mision vencida), when ocurre, then no existe ningun logro que se dispare por eso.
- Given las 6 categorias (Constancia, Volumen, Progresion, Villanos, Bienestar, Puntualidad), when se carga el catalogo (GetUserAchievementsUseCase), then viene de seeder, cerrado.

---

## Fase 12 - Motivation

### HU-MOT-1 - Frase motivacional al iniciar sesion
Como estudiante, quiero ver una frase distinta cada vez que inicio sesion.
- Given el catalogo de 10 frases, when el usuario inicia sesion, then GetQuoteForLoginUseCase elige una con NoRepeatPicker y la muestra en el Dashboard. Se registra en user_quote_views.
- Given la frase de esta sesion, when el usuario navega entre paginas, then la frase no cambia.

### HU-MOT-2 - Consejos de uso por modulo
Como estudiante, quiero un tip breve y accionable la primera vez que entro a un modulo.
- Given el catalogo de tips, when el usuario visita un modulo por primera vez en la sesion, then GetTipForModuleUseCase muestra un consejo con NoRepeatPicker en UsageTipBanner.
- Given un consejo descartado (DismissTipUseCase), when el usuario lo cierra, then no vuelve a aparecer hasta agotar el ciclo del modulo (registrado en user_tip_views).

### HU-MOT-3 - Excepcion al congelamiento de despliegue
Como equipo de investigacion, quiero poder ampliar el catalogo de frases/consejos durante los 66 dias de intervencion.
- Given la prohibicion general de despliegue, when el cambio es solo agregar filas a MotivationalQuote/UsageTip, then es la unica excepcion permitida.
- Given cualquier adicion durante la intervencion, when se hace, then se registra en docs/12-HISTORIAL.md.

---

## Fase 13 - StudyGroups

### HU-SG-1 - Restriccion de infraestructura (no negociable)
Como equipo tecnico, quiero que el chat grupal funcione sin WebSockets, para que siga funcionando en el hosting compartido.
- Given cualquier implementacion de chat, when se construye, then usa polling AJAX cada 5 segundos exactos. Laravel Reverb queda descartado de raiz.
- Given que la pestana pasa a segundo plano, when document.visibilityState === 'hidden', then el polling se pausa.
- Given el endpoint de polling (PollSessionUseCase), when se llama, then responde solo los mensajes posteriores a lastMessageId.

### HU-SG-2 - Sesion de estudio grupal con limite de participantes
Como estudiante, quiero crear o unirme a una sesion de estudio con hasta 4 companeros mas.
- Given una sesion con 5 participantes, when un sexto intenta unirse (JoinSessionUseCase), then se rechaza - 5 es el maximo.
- Given una sesion abierta, when se inicia un Pomodoro grupal (StartGroupPomodoroUseCase), then aplica las mismas reglas de dominio de Pomodoro para cada participante.
- Given mensajes con mas de 7 dias, when corre la purga (ChatPurgeOld), then se borran.
- Given la configuracion de sala, when el dueno la modifica (ConfigureRoomUseCase), then persiste en study_sessions.room_config.
- Given la fase de la sesion, when avanza (AdvancePhaseUseCase), then transita entre fases: warm-up, focus, break, review.

### HU-SG-3 - Moderacion basica
Como estudiante, quiero poder reportar un mensaje inapropiado.
- Given cualquier mensaje enviado (SendMessageUseCase), when pasa por el filtro de palabras prohibidas, then se bloquea si coincide.
- Given un mensaje reportado, when se envia, then entra a una cola de revision del administrador - el contenido nunca entra en telemetria, solo su longitud.

---

## Fase 14 - AiAssistant

### HU-AI-1 - Cuota diaria y cola asincrona
Como equipo tecnico, quiero que las consultas al asistente nunca bloqueen un PHP worker.
- Given una consulta nueva (SendConsultationUseCase), when se envia, then se despacha a la cola ai (procesada por cron cada minuto con --stop-when-empty).
- Given la cuota diaria (5 consultas/dia - CheckQuotaUseCase), when el usuario ya la agoto, then el intento siguiente se rechaza con mensaje claro.
- Given que DeepSeek no responde en 30s o devuelve error, when ocurre, then el usuario ve un mensaje claro y no se le descuenta la cuota.
- Given la cuota, when llega la medianoche hora de Lima, then se reinicia a 0.

### HU-AI-2 - Guardrails obligatorios del prompt de sistema
Como responsable de etica, quiero que el asistente nunca de consejo clinico.
- Given el prompt de sistema, when se escribe, then prohibe explicitamente: diagnostico, recomendacion farmacologica, consejo clinico, y promesas de resultado academico.
- Given cualquier respuesta, when se genera, then es en espanol peruano neutro.

### HU-AI-3 - Contexto sin datos personales al proveedor
Como responsable de etica, quiero que DeepSeek nunca reciba nombre, correo ni contenido del diario.
- Given las fuentes de contexto (Wellbeing, Habits, Missions), when se arma el contexto, then solo van metricas agregadas - nunca texto libre ni datos personales.

### HU-AI-4 - Historial y valoracion de conversaciones
Como estudiante, quiero consultar el historial de mis conversaciones con Edy y valorar los consejos.
- Given la vista /ai-assistant, when carga, then ListUserConversationsUseCase muestra las conversaciones anteriores (GetConversationHistoryUseCase).
- Given un consejo recibido, when el usuario lo valora, then RateAdviceUseCase registra la puntuacion.

---

## Fase 15 - Admin

Panel de solo lectura. Detalle completo en docs/01-MODULOS.md seccion 16.

### HU-ADM-1 - Panel de solo lectura, sin excepciones
Como equipo de investigacion, quiero un panel administrativo que no pueda escribir datos del estudio.
- Given cualquier endpoint del panel, when se implementa, then es de lectura unicamente.
- Given el rol requerido, when se protege cada ruta, then exige admin exclusivamente con doble factor obligatorio.

### HU-ADM-2 - Las 6 vistas del panel
Como investigador, quiero las 6 vistas documentadas (Dashboard, Participantes, Desercion, Telemetria, Exportacion, Salud del sistema).
- Given la vista Dashboard, when carga, then GetAdminDashboardMetricsUseCase muestra metricas globales: participantes activos, adherencia promedio, distribucion de niveles.
- Given la vista Participantes, when carga, then GetAdminParticipantsUseCase muestra participant_code, nivel, racha y ultimo acceso - sin datos personales.
- Given la vista Desercion, when carga, then GetAdminDropoutUseCase lista usuarios sin actividad en 3+ dias.
- Given la vista Telemetria, when carga, then GetAdminTelemetryMetricsUseCase muestra volumen de eventos por modulo.
- Given la vista Exportacion, when se genera, then GenerateDatasetCsvUseCase produce los tres CSV del dataset.
- Given la vista Salud del sistema, when carga, then GetAdminSystemHealthUseCase muestra estado de la BD, cola, logs y espacio en disco.

---

## Fase 16 - Landing Page Publica

### HU-LAND-1 - Landing page en epycus.es
Como visitante, quiero ver una landing page publica informativa sobre Epycus antes de registrarme.
- Given el dominio epycus.es, when se visita la raiz, then se muestra Welcome.vue con el contenido marketing del proyecto.
- Given el dominio app.epycus.es, when se visita la raiz, then se redirige a /login o a /dashboard segun el estado de autenticacion.
- Given cualquier ruta de app accedida desde epycus.es, when se intenta acceder, then se redirige a app.epycus.es.

### HU-LAND-2 - Seccion informativa con modo claro/oscuro
Como visitante, quiero que la landing sea visualmente atractiva y se adapte a mis preferencias de tema.
- Given el navbar de la landing, when se presiona el boton Sol/Luna, then alterna entre modo oscuro (#070A12) y claro (bg-slate-50), guardando en localStorage.
- Given la seccion de comparativa, when carga, then muestra tabla comparativa de Epycus vs. Notion, Habitica, Forest, TickTick, Focus y Todoist con scroll horizontal en movil.

### HU-LAND-3 - SEO, Sitemap y robots.txt
Como equipo de marketing, quiero que la landing sea indexable por Google y otras IAs.
- Given la ruta /sitemap.xml, when se solicita, then devuelve un sitemap XML valido con todas las URLs publicas.
- Given la ruta /robots.txt, when se solicita, then permite el crawling de la landing y bloquea el area de la aplicacion.
- Given las paginas de la landing, when se renderizan, then incluyen meta tags og:title, og:description, og:image correctos.

---

## Fase 17 - Buzon de la Comunidad

### HU-BUZ-1 - Formulario de buzon publico en la landing
Como visitante o usuario de Epycus, quiero enviar un mensaje al equipo sin necesitar cuenta, para compartir agradecimientos, reportar errores o sugerir mejoras.
- Given el formulario en https://epycus.es/#buzon, when se completa y envia, then el mensaje se guarda en la tabla feedbacks de la base de datos de produccion.
- Given el tipo de mensaje, when el usuario elige, then las categorias disponibles son: Idea/Funcion Nueva, Reporte de Error, Sugerencia de Mejora, Agradecimiento.
- Given el nombre y correo del remitente, when se envian, then son opcionales - se acepta el mensaje de forma anonima.
- Given el mensaje enviado, when se procesa (FeedbackController::store), then se envia automaticamente por correo SMTP (smtp.hostinger.com:465 SSL) a contacto@soltectos.com con el asunto [Buzon Epycus] tipo - nombre.
- Given una falla temporal del servidor SMTP, when ocurre, then el mensaje ya quedo registrado en la base de datos y la respuesta al usuario sigue siendo exitosa.
- Given el envio en movil, when el formulario se envia con exito, then el dispositivo vibra (API navigator.vibrate) como confirmacion tactil haptica.

---

## Notas de Implementacion Transversales

### Persistencia de Sesion / Reconexion
- Given el usuario cierra el navegador y vuelve horas despues, when la sesion de Laravel sigue vigente, then la app carga sin requerir nuevo inicio de sesion. Si la sesion expiro, redirige a /login limpiamente sin pantallas en blanco.

### Responsividad Garantizada
- Given cualquier modulo, when se visualiza en pantalla movil (360px-430px), then todos los elementos son usables sin superposicion ni desbordamiento. Modulos criticos revisados: Calendar (vista mensual), Apuntes de Curso (header sin superposicion), Tabla comparativa de la landing (scroll horizontal con 5+ plataformas visibles), Bottom Nav.

### Vibracion Haptica en Movil
- Given cualquier evento de recompensa (monedas ganadas, puntos XP, mision completada, buzon enviado), when ocurre en un dispositivo movil compatible, then el dispositivo vibra mediante la API navigator.vibrate como retroalimentacion tactil.

### Tests
- Cobertura actual: 131 tests, 419 assertions, 0 fallos (verificado al 2026-08-17).
- Suite de referencia: php vendor/bin/phpunit.
