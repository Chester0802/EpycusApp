# 🎓 Epycus Killer Features — Análisis Técnico Universitario

> **Documento de referencia técnica:** Evalúa la viabilidad de cada idea contra el código actual (rama `main`). Todo lo marcado ✅ Alta es desarrollable. El documento se actualiza de forma incremental.

---

## 📋 Índice

1. [Módulo Misiones (Tareas) — Rediseño con Eisenhower + Kanban](#1-módulo-misiones-tareas--rediseño-con-eisenhower--kanban)
2. [Categorías de Tareas — Académicas, Trabajo y Personal](#2-categorías-de-tareas--académicas-trabajo-y-personal)
3. [Módulo Cursos — Nodo Central](#3-módulo-cursos--nodo-central)
4. [Gestión de Proyectos por Curso (ABP)](#4-gestión-de-proyectos-por-curso-abp)
5. [Periodo Académico — Del Perfil al Sistema](#5-periodo-académico--del-perfil-al-sistema)
6. [Sílabo PDF con Visor](#6-sílabo-pdf-con-visor)
7. [Simulador de Notas y Calculadora Predictiva](#7-simulador-de-notas-y-calculadora-predictiva)
8. [Apuntes: Imágenes Redimensionables y Arrastrables](#8-apuntes-imágenes-redimensionables-y-arrastrables)
9. [Flashcards Editables + Sistema de Cajas de Leitner](#9-flashcards-editables--sistema-de-cajas-de-leitner)
10. [Modo Simulacro — Opción Múltiple + Respuesta Libre con IA](#10-modo-simulacro--opción-múltiple--respuesta-libre-con-ia)
11. [Hábitos: Modo «Dejar Malos Hábitos»](#11-hábitos-modo-dejar-malos-hábitos)
12. [Plan Diario — Integración Bidireccional con Misiones](#12-plan-diario--integración-bidireccional-con-misiones)
13. [Avatar en Canvas — Mapa Visual de Progreso](#13-avatar-en-canvas--mapa-visual-de-progreso)
14. [Módulo Lecturas — Libros, Artículos y Tesis](#14-módulo-lecturas--libros-artículos-y-tesis)
15. [Módulo Habilidades — Aprendizaje Extracurricular](#15-módulo-habilidades--aprendizaje-extracurricular)
16. [Pomodoro como Motor de Control de Tiempo Universal](#16-pomodoro-como-motor-de-control-de-tiempo-universal)
17. [Calendario Total — Vida Académica, Trabajo y Personal](#17-calendario-total--vida-académica-trabajo-y-personal)
18. [Tienda + Entretenimiento — Películas, Series y Anime](#18-tienda--entretenimiento--películas-series-y-anime)
19. [El Personaje como Espejo del Progreso Real](#19-el-personaje-como-espejo-del-progreso-real)
20. [Automatización Pura — Sin IA, Solo Algoritmos](#20-automatización-pura--sin-ia-solo-algoritmos)

---

## 1. Módulo Misiones (Tareas) — Eisenhower + Kanban + Cronograma

### 📊 Estado Actual — Hallazgo clave: Ya está implementado

El módulo Misiones ya tiene **las 3 vistas completamente funcionales** en la rama `2.0`:
- **Matriz Eisenhower** — 4 cuadrantes Q1/Q2/Q3/Q4 con `ChangeQuadrantUseCase`
- **Tablero Kanban** — columnas: Lista de Misiones → En Proceso → En Revisión → Terminado
- **Cronograma / Lista** — vista filtrada con subtareas, Pomodoro inline, etc.

Lo que **YA existe** en DB:
```
missions
  id, user_id, course_id, title, description
  difficulty: easy | medium | hard
  priority: baja | normal | alta
  eisenhower_quadrant: q1 | q2 | q3 | q4   ← YA EXISTE
  due_date, completed_at, is_overdue, xp_awarded
  → HasMany: subtasks (con sort_order y completed_at)
```

### ✅ Viabilidad — Muy Alta

El único trabajo pendiente es añadir `mission_type` para categorizar las tareas (académica, trabajo, personal, proyecto) y vincularlas con fases de proyecto. Las 3 vistas ya existen y el canvas de proyectos reutilizará exactamente el mismo componente Kanban.

### 🏗️ Propuesta de Implementación

**Migración mínima — solo 3 campos nuevos:**
```sql
ALTER TABLE missions
  ADD COLUMN mission_type ENUM('academic', 'work', 'personal', 'project') DEFAULT 'academic' AFTER course_id,
  ADD COLUMN project_phase_id BIGINT UNSIGNED NULL AFTER mission_type,
  ADD COLUMN planned_date  DATE NULL AFTER due_date,
  ADD COLUMN planned_start TIME NULL AFTER planned_date,
  ADD COLUMN planned_end   TIME NULL AFTER planned_start;

ALTER TABLE missions ADD CONSTRAINT fk_missions_phase
  FOREIGN KEY (project_phase_id) REFERENCES project_phases(id) ON DELETE SET NULL;
```

**Lo que cambia en la UI:**
- Filtro por tipo en la barra superior: `[Todas] [Académicas] [Trabajo] [Personal] [Proyecto]`
- Al crear misión: selector de tipo + opción de vincular a curso o fase de proyecto
- El Kanban y la Matriz ya funcionan tal cual para cualquier tipo de misión

**Relación completa de una Misión (con los campos nuevos):**
```
Misión "Implementar API REST"
  ├── mission_type: 'project'
  ├── course_id: 5 (Sistemas Distribuidos)    ← opcional
  ├── project_phase_id: 12 (Fase 2: Backend)  ← opcional
  ├── eisenhower_quadrant: 'q1'
  ├── subtasks:
  │     ├── [✅] Setup del servidor Node.js
  │     ├── [🔄] Endpoints de usuarios
  │     └── [⬜] Tests de integración
  └── Pomodoro vinculado: 🍅 45 min de foco hoy
```


---

## 2. Categorías de Tareas — Académicas, Trabajo y Personal

### 📊 Estado Actual

`missions.course_id` asume que toda misión pertenece a un curso. No hay un campo para distinguir misiones de trabajo, proyectos personales o tareas del hogar.

### ✅ Viabilidad — Alta (solo un campo nuevo)

**El campo `mission_type` (definido en §1) es la clave:**

| Tipo | `mission_type` | Vincula con | Aparece en |
|:-----|:--------------:|:-----------:|:----------:|
| Tarea de curso | `academic` | `course_id` | Calendario académico |
| Tarea de proyecto | `project` | `course_id` + `project_phase_id` | Canvas del proyecto |
| Tarea de trabajo | `work` | *(ninguno)* | Calendario general |
| Tarea personal | `personal` | *(ninguno)* | Calendario general |

**Filtros en el módulo de Misiones:**
```
[Todas] [Académicas] [Trabajo] [Personal] [Proyectos]
[Q1 Urgente] [Q2 Importante] [Vencidas]
```

**Misiones de trabajo en el Calendario:** Un estudiante que trabaja como freelancer, en una empresa o en un emprendimiento puede crear tareas de `type = 'work'` con fecha y hora, y estas aparecen en su calendario junto con las clases y tareas universitarias. Así puede ver toda su vida en una sola pantalla.

---

## 3. Módulo Cursos — Nodo Central

### 📊 Estado Actual — ✅ COMPLETADO
`CourseModel` extendido, migraciones y controladores creados. Interfaz UI (Hub Académico en `Courses/Index.vue` y `Courses/Show.vue`) implementada en el menú lateral.

### 🏗️ Migración y estructura

```sql
ALTER TABLE courses
  ADD COLUMN syllabus_path  VARCHAR(255) NULL  AFTER color,
  ADD COLUMN professor      VARCHAR(100) NULL  AFTER syllabus_path,
  ADD COLUMN credits        TINYINT UNSIGNED DEFAULT 3,
  ADD COLUMN target_grade   DECIMAL(4,2)  NULL,   -- Sin default, el estudiante lo pone
  ADD COLUMN min_pass_grade DECIMAL(4,2)  NULL;   -- Sin default, idem
```

**Pestañas del hub del curso:**
```
[📝 Apuntes] [📊 Simulador de Notas] [📄 Sílabo] [🏗️ Proyecto] [🃏 Flashcards]
```

---

## 4. Gestión de Proyectos por Curso (ABP)

### 📊 Contexto — ✅ COMPLETADO

El módulo de Proyectos ABP ya está implementado integrando el diseño del Kanban de Misiones en un solo tablero unificado por fases. El proyecto es opcional por curso y tiene fases diferenciadas por colores.

### ✅ Viabilidad — Alta

Reutiliza `missions` (las tareas son misiones normales con `project_phase_id`) y las **vistas Kanban+Matriz+Cronograma ya implementadas** del módulo Misiones. El canvas del proyecto **usa exactamente el mismo componente** del módulo Misiones, filtrado por `project_phase_id`. No se necesita nada nuevo en el front-end para la vista del canvas.

**Tablas:**
```sql
CREATE TABLE course_projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    study_group_id BIGINT UNSIGNED NULL,   -- Para proyectos grupales
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    weight_percentage DECIMAL(5,2) DEFAULT 0.00, -- % en la nota final
    due_date DATE NULL,
    status ENUM('planning', 'in_progress', 'review', 'completed') DEFAULT 'planning',
    rubric_criteria JSON NULL,  -- [{"name": "Metodología", "weight": 30}, ...]
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE project_phases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,     -- "Fase 1: Propuesta", "Fase 2: Desarrollo"
    color VARCHAR(30) DEFAULT 'blue',-- Color distintivo de la fase
    due_date DATE NULL,
    sort_order TINYINT UNSIGNED DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (project_id) REFERENCES course_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Vista del proyecto — Tablero Kanban Unificado con Fases por Color:**

En lugar de cambiar de pestaña por cada fase, **un solo tablero Kanban muestra todas las misiones del proyecto**. Cada misión / post-it lleva la etiqueta y cinta de color de su fase correspondiente.

```
🏗️ Proyecto Final — Sistemas Distribuidos
   Estado: En Progreso · Peso: 30% · Entrega: 15 Nov 2026 · 🍅 12.5 hrs Pomodoro

   Filtros: [🔵 Todas las Fases] [Fase 1: Propuesta (Azul)] [Fase 2: Backend (Verde)] [Fase 3: Sustentación (Naranja)]

   ┌───────────────────────────┬───────────────────────────┬───────────────────────────┬───────────────────────────┐
   │     Lista de Misiones     │        ⚡ En Proceso       │       🔍 En Revisión      │        ✅ Terminado       │
   ├───────────────────────────┼───────────────────────────┼───────────────────────────┼───────────────────────────┤
   │ 🟩 [Fase 2] API usuarios  │ 🟩 [Fase 2] Setup Node.js │                           │ 🟦 [Fase 1] Propuesta PDF │
   │ 🟧 [Fase 3] Slides demo   │                           │                           │ 🟦 [Fase 1] Diagrama ER   │
   └───────────────────────────┴───────────────────────────┴───────────────────────────┴───────────────────────────┘

   Progreso del Proyecto: ████████░░░░  55%
```

> **Ventaja:** El estudiante tiene la visión panorámica de todo el proyecto en una sola pantalla, diferenciando el estado de cada fase a través de su código cromático.

---

## 5. Periodo Académico — Del Perfil al Sistema

```sql
CREATE TABLE academic_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,       -- 2026
    period TINYINT UNSIGNED NOT NULL,      -- 0=Verano, 1=Primer, 2=Segundo
    is_current BOOLEAN DEFAULT FALSE,
    starts_at DATE NULL,
    ends_at DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_period (user_id, year, period)
) ENGINE=InnoDB;

ALTER TABLE courses ADD COLUMN period_id BIGINT UNSIGNED NULL;
```

---

## 6. Sílabo PDF con Visor

### 📊 Estado Actual — ✅ COMPLETADO

Implementado en `Courses/Show.vue` bajo la pestaña Sílabo. Usa `SyllabusViewer.vue` que permite la subida de un PDF y su visualización usando un `iframe` nativo para rendimiento óptimo.

---

## 7. Simulador de Notas y Calculadora Predictiva

### 📊 Estado Actual — ✅ COMPLETADO

Implementado en `GradeSimulator.vue`. Funciona mediante `computed` properties de Vue para ser instantáneo:
- **Nota Actual**: Promedio de lo ya evaluado.
- **Predictor**: Si el estudiante configuró una nota mínima, se le indica matemáticamente cuánto necesita obtener en el porcentaje restante del curso.

```sql
CREATE TABLE grade_evaluations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    obtained_score DECIMAL(4,2) NULL,   -- null hasta que el estudiante lo llene
    max_score DECIMAL(4,2) DEFAULT 20.00,
    eval_date DATE NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

Lógica de promedio ponderado + «¿cuánto necesito sacar?» es 100% Vue `computed`, sin IA.

---

## 8. Apuntes: Imágenes Redimensionables y Arrastrables

El JSON del bloque imagen ya es extensible: añadir `width`, `align`, `float`, `caption`. Librería recomendada: `vue-draggable-resizable` (4 KB gzipped).

---

## 9. Flashcards Editables + Sistema de Cajas de Leitner

### 📊 Estado Actual — ✅ COMPLETADO

Implementado mediante `FlashcardModel.php`, `FlashcardsController.php` y `CourseFlashcardsTab.vue` bajo la pestaña Flashcards en `Courses/Show.vue`. Incluye visor interactivo con efecto de giro 3D (Flip Card), filtrado por cajas de Leitner (1 a 5), cálculo de intervalos según dificultad ('easy', 'good', 'hard', 'fail') y otorgamiento de XP.

```sql
CREATE TABLE flashcards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    -- Polimórfico: puede pertenecer a curso, lectura, habilidad o proyecto
    course_id    BIGINT UNSIGNED NULL,
    reading_id   BIGINT UNSIGNED NULL,
    skill_id     BIGINT UNSIGNED NULL,
    node_id VARCHAR(80) NULL,
    source ENUM('ai', 'manual') DEFAULT 'ai',
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    leitner_box TINYINT UNSIGNED DEFAULT 1,  -- 1..5
    next_review_at DATE NULL,
    last_reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_review (user_id, next_review_at)
) ENGINE=InnoDB;
```

| Caja | Repaso | Avanza si | Retrocede a |
|:----:|:------:|:---------:|:-----------:|
| 1 | Cada 1 día | Fácil o Regular | Caja 1 |
| 2 | Cada 3 días | Fácil | Caja 1 |
| 3 | Cada 7 días | Fácil | Caja 2 |
| 4 | Cada 14 días | Fácil | Caja 2 |
| 5 | Cada 30 días | — (dominada) | Caja 3 si falla |

---

## 10. Modo Simulacro — Opción Múltiple + Respuesta Libre con IA

### 📊 Estado Actual — ✅ COMPLETADO

Implementado en `CourseExamSimulatorModal.vue` y `FlashcardsController.php`. Genera exámenes de 10 preguntas (6 de opción múltiple + 4 de desarrollo libre) cronometrados a 20 min. La IA evalúa las respuestas abiertas, califica de 0 a 20 con desglose detallado y convierte automáticamente los fallos en Flashcards de Caja 1 para Active Recall.

---

## 11. Hábitos: Modo «Dejar Malos Hábitos»

### 📊 Estado Actual — ✅ COMPLETADO

Implementado soporte para hábitos de tipo `break` y `build` en `habits` (`habit_type`), `HabitModel.php`, `HabitsController.php` y `Habits/Index.vue`. Los hábitos destructivos se muestran con insignia roja distintiva `🚫 A Eliminar`, botón de marcado `"¡Evitado Hoy! 🚫"` y cálculo de racha de días limpios sin recaída.

```sql
ALTER TABLE habits
  ADD COLUMN habit_type ENUM('build', 'break') DEFAULT 'build',
  ADD COLUMN max_per_week TINYINT UNSIGNED NULL;
```

| Acción | Build | Break |
|:-------|:-----:|:-----:|
| Completar | "Lo hice ✅" | "Lo evité hoy 🚫" |
| Racha | Crece al hacer | Crece sin caer |
| XP | +25 al completar | +25 libre; -10 recaída |

---

## 12. Plan Diario — Integración Bidireccional con Misiones

### 📊 Estado Actual — ✅ COMPLETADO

Integrado en `Missions/Index.vue`, `CalendarController.php` y `DailyPlanItemModel`. Los estudiantes pueden pulsar el botón directo «📅 Planificar en Agenda» en cualquier misión para programar un bloque de tiempo en la fecha deseada (mañana, tarde, noche) que se sincroniza directamente con el Time-Blocking del calendario.

---

## 13. Avatar en Canvas — Mapa Visual de Progreso

Todo el dato ya existe: `CharacterStatsCalculator`, `HerosJourneyPhases`, XP, nivel, racha. Solo falta la capa visual SVG animada (recomendada) o Three.js (ya está en el proyecto para el Knowledge Graph 3D).

---

## 14. Módulo Lecturas — Libros, Artículos y Tesis

### 📊 Estado Actual — ✅ COMPLETADO

Implementado mediante `readings`, `reading_tags`, `ReadingModel.php`, `ReadingTagModel.php`, `ReadingsController.php` y `Readings/Index.vue`. Permite organizar libros de ficción y no ficción, papers y tesis por estados (`reading`, `want_to_read`, `finished`, `paused`), registrar avance de páginas con cálculo automático de progreso porcentual, calificar con estrellas y otorgar XP por avance (+15 XP por sesión, +50 XP al completar).

```sql
CREATE TABLE readings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(200) NULL,
    year SMALLINT UNSIGNED NULL,
    type ENUM('book_fiction', 'book_nonfiction', 'academic_article', 'thesis', 'manual', 'other') NOT NULL,
    total_pages SMALLINT UNSIGNED NULL,
    isbn VARCHAR(30) NULL,
    cover_url VARCHAR(500) NULL,
    status ENUM('want_to_read', 'reading', 'finished', 'paused', 'dropped') DEFAULT 'want_to_read',
    current_page SMALLINT UNSIGNED DEFAULT 0,
    rating TINYINT UNSIGNED NULL,
    linked_habit_id BIGINT UNSIGNED NULL,
    started_at DATE NULL,
    finished_at DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reading_tags (
    reading_id BIGINT UNSIGNED NOT NULL,
    tag VARCHAR(50) NOT NULL,
    PRIMARY KEY (reading_id, tag)
) ENGINE=InnoDB;
```

---

## 15. Módulo Habilidades — Aprendizaje Extracurricular

### 📊 Estado Actual — ✅ COMPLETADO

Implementado mediante `personal_skills`, `personal_skill_logs`, `SkillModel.php`, `SkillLogModel.php`, `SkillsController.php` y `Skills/Index.vue`. Árbol de competencias técnicas, blandas, idiomas y talentos creativos. Incluye cálculo de progresión de niveles por práctica deliberada (1.5 XP por minuto), bitácora de sesiones con notas de aprendizaje y enlace directo con sesiones de enfoque Pomodoro.

| Concepto | Uso |
|:---------|:----|
| **Hábito** | Acción diaria. *"Practicar piano 20 min"* |
| **Misión** | Entregable puntual. *"Aprender escala de Do"* |
| **Habilidad** | Contenedor de progreso a largo plazo. *"Tocar Ukelele"* |

| Nivel | Título |
|:-----:|:------:|
| 1 | Principiante |
| 2 | Iniciado |
| 3 | Aprendiz |
| 4 | Practicante |
| 5 | Competente |
| 6 | Avanzado |
| 7 | Experto |
| 8 | Maestro |
| 9 | Gran Maestro |
| 10 | Leyenda |

---

## 16. Pomodoro como Motor de Control de Tiempo Universal

### 📊 Estado Actual — ✅ COMPLETADO

Implementado. Anteriormente, el Pomodoro solo se asociaba a Misiones. Ahora, el `PomodoroController` y `PomodoroSessionModel` soportan polimorfismo mediante `context_type` y `context_id`.

Se agregó un selector en `Pomodoro/Index.vue` para que el estudiante indique en qué se enfocará:
- Libre
- Misión Académica
- Proyecto
- Lectura
- Habilidad
- Personal/Hábitos

Se creó el endpoint `GET /api/focus-report` que agrega los minutos de enfoque y los muestra en una UI en el Dashboard de Pomodoro, pintando barras de progreso según el porcentaje de tiempo dedicado a cada contexto.

### 🏗️ Migración mínima

```sql
ALTER TABLE pomodoro_sessions
  ADD COLUMN context_type VARCHAR(30) NULL AFTER mission_id,
  -- 'mission' | 'reading' | 'skill' | 'course_project' | 'habit' | 'free'
  ADD COLUMN context_id BIGINT UNSIGNED NULL AFTER context_type;
```

**Pantalla de inicio del Pomodoro — "¿En qué te vas a enfocar?":**
```
┌─────────────────────────────────────────────────┐
│  ⏱️ Nuevo Pomodoro                               │
├─────────────────────────────────────────────────┤
│  🎯 Misiones de hoy                              │
│     ○ Estudiar parcial de Algoritmos (Q1 · Alta) │
│     ○ Avanzar API del proyecto BD (Q1 · Media)   │
│     ○ Entregar informe trabajo (Trabajo · Alta)  │
│                                                  │
│  📚 Lecturas activas                             │
│     ○ El Hobbit (pág 180/310)                   │
│                                                  │
│  🎸 Habilidades                                  │
│     ○ Ukelele — Nivel 3                         │
│                                                  │
│  [ ▶️ Iniciar sin contexto ]                     │
└─────────────────────────────────────────────────┘
```

**Reporte Mensual de Tiempo de Enfoque:**
```
Tiempo de Enfoque — Septiembre 2026
Total: 38.5 horas

  ████████░░░░  Cursos / Misiones  52%  (20h)
  ████░░░░░░░░  Trabajo            21%  (8h)
  ██░░░░░░░░░░  Habilidades        13%  (5h)
  ██░░░░░░░░░░  Lecturas           8%   (3h)
  █░░░░░░░░░░░  Sin contexto       6%   (2.5h)
```

---

## 17. Calendario Total — Vida Académica, Trabajo y Personal

### 📊 Estado Actual — ✅ COMPLETADO

Implementado mediante la tabla `personal_events`, `PersonalEventModel.php`, `PersonalEventsController.php` y `Calendar/Index.vue`. Permite agendar cumpleaños, citas médicas, reuniones, compromisos de trabajo y recordatorios. La interfaz incluye una barra de filtros por capas toggleables (`[🎓 Clases]`, `[🎯 Misiones]`, `[🎂 Eventos Personales]`) y renderizado integrado tanto en la cuadrícula mensual como en el Time-Blocking de 24 horas.

### 📊 Concepto

El Módulo Calendario debe ser **el centro de la vida del estudiante**, no solo de sus clases. Un universitario tiene:
- Clases (`course_sessions`)
- Misiones con fecha (`missions.planned_date`)
- Hábitos diarios
- Eventos personales (cumpleaños, reuniones, citas)
- Tareas de trabajo (`mission_type = 'work'`)
- Sesiones de lectura y habilidades (del Plan Diario)

### ✅ Viabilidad — Alta

**Nueva tabla para eventos personales:**
```sql
CREATE TABLE personal_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NULL,
    type ENUM('birthday', 'meeting', 'appointment', 'social', 'reminder', 'other') NOT NULL,
    event_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_rule VARCHAR(100) NULL, -- Ej: "FREQ=YEARLY" para cumpleaños
    color VARCHAR(30) DEFAULT 'primary',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, event_date)
) ENGINE=InnoDB;
```

**Vista del Calendario — Capas de información (toggleables):**
```
[✅ Clases] [✅ Misiones] [✅ Hábitos] [✅ Trabajo] [✅ Personal] [⬜ Eventos]

LUNES 2 SEP
  08:00  🎓 Algoritmos (clase)
  10:00  🎯 Estudiar parcial (misión académica)
  12:00  💼 Reunión cliente (trabajo)
  15:00  📚 Leer El Hobbit (plan diario)
  20:00  🎂 Cumpleaños de Ana (personal)
```

**Código de colores por tipo:**
- Clases: color del curso
- Misiones académicas: azul
- Misiones de trabajo: verde
- Eventos personales: lavanda
- Hábitos: ámbar

---

## 18. Tienda + Entretenimiento — Películas, Series y Anime

### 📊 Estado Actual

La Tienda (módulo `Shop`) ya tiene:
- `custom_rewards`: recompensas personalizadas con `cost_coins`, `icon`, `category`
- `reward_redemptions`: historial de canjeos con `status: redeemed | used`

El estudiante ya puede crear una recompensa "Ver una película" y canjearla con monedas. **Lo que falta** es registrar qué película fue, darle rating y reseña — convertir el canje en un registro de entretenimiento.

### ✅ Viabilidad — Alta

**Extensión de la Tienda con categoría `entertainment`:**

El flujo es sencillo:
1. El admin/estudiante crea una recompensa: **título "Ver El Padrino"**, `category = 'entertainment'`, `cost_coins = 150`.
2. El estudiante canjea la recompensa (ya funciona).
3. **Nuevo:** después del canje, puede abrir ese item y completar su reseña.

```sql
-- Añadir reseña a las redenciones de entretenimiento
ALTER TABLE reward_redemptions
  ADD COLUMN entertainment_type ENUM('movie', 'series', 'anime', 'game', 'other') NULL,
  ADD COLUMN review_title VARCHAR(255) NULL,
  ADD COLUMN review_text TEXT NULL,
  ADD COLUMN review_rating TINYINT UNSIGNED NULL,   -- 1..5 estrellas
  ADD COLUMN review_season VARCHAR(20) NULL,        -- Para series: "Temporada 1"
  ADD COLUMN reviewed_at TIMESTAMP NULL;
```

**Flujo de experiencia de usuario:**
```
[Tienda] El estudiante ve:
  🎬 "Ver El Padrino" — 150 monedas    [Canjear]

Después del canje:
  ✅ Canjeado — ¿Ya lo viste?
  [📝 Agregar reseña]

Formulario de reseña:
  Tipo: ○ Película  ○ Serie  ○ Anime  ○ Otro
  Temporada: _________ (solo para series/anime)
  Tu reseña: [____________________________]
  Calificación: ⭐⭐⭐⭐☆ (4/5)
  [Guardar]
```

**Vista "Mi Entretenimiento" (sección dentro de Tienda o módulo propio):**
```
┌─────────────────────────────────────────────┐
│  🎬 El Padrino  · Película                  │
│  "Obra maestra del cine. La actuación..."   │
│  ⭐⭐⭐⭐⭐  Canjeado: 12 Sep 2026           │
├─────────────────────────────────────────────┤
│  📺 Breaking Bad · Serie · Temp. 1-3        │
│  "Me enganchó desde el primer episodio..."  │
│  ⭐⭐⭐⭐⭐  Canjeado: 5 Sep 2026            │
├─────────────────────────────────────────────┤
│  🌸 Attack on Titan · Anime · Temp. 4      │
│  "El final fue muy impactante, aunque..."  │
│  ⭐⭐⭐⭐☆  Canjeado: 28 Ago 2026           │
└─────────────────────────────────────────────┘
```

**Filtros:**
```
[Todos] [Películas] [Series] [Anime] [Por ver] [Vistos]
⭐ Ordenar por: fecha | calificación | costo
```

**Plan Diario — Opcional:** Si el estudiante canjea "Ver una película el viernes", puede añadirlo al Plan Diario de ese día como un ítem de ocio planificado. El Plan Diario ya acepta cualquier ítem con categoría libre.

---

## 19. El Personaje como Espejo del Progreso Real

Todo lo que el estudiante hace en Epycus alimenta a su personaje con datos medibles:

```
DATO REAL                              ATRIBUTO DEL PERSONAJE
───────────────────────────────────────────────────────────
pomodoro_sessions.focus_minutes     →  🧠 Sabiduría
habit_completions (racha)           →  ⚡ Disciplina
skill_logs.total_practice_minutes   →  🎭 Talento
readings.finished (libros leídos)   →  📚 Cultura
grade_evaluations (promedio)        →  🎓 Rendimiento Académico
project_phases (fases completadas)  →  🏗️ Maestría Práctica
flashcard_reviews (dominio >80%)    →  🧩 Retención Cognitiva
```

**Canvas del Personaje:**
```
┌────────────────────────────────────────────┐
│  ✨ Marco · Nivel 7 · "El Estratega"       │
│                                            │
│          [Avatar personalizado]            │
│                                            │
│  🧠 Sabiduría  ██████████░  82% (41h)     │
│  ⚡ Disciplina  █████████░░  74% (18 días) │
│  🎭 Talento    ████░░░░░░░  35% (Ukelele) │
│  📚 Cultura    ██████░░░░░  48% (6 libros)│
│  🎓 Rendimiento ████████░░  16.4/20       │
│                                            │
│  [📊 Reporte Mensual] [🗺️ Camino del Héroe]│
└────────────────────────────────────────────┘
```

---

## 20. Automatización Pura — Sin IA, Solo Algoritmos

| # | Automatización | Qué hace |
|:--|:--------------|:---------|
| 20.1 | **Planificador del Día** | Genera borrador del Plan Diario con misiones + hábitos + clases en los slots libres |
| 20.2 | **Racha Inteligente** | No rompe racha si el hábito no tiene ese día de semana activo |
| 20.3 | **Alertas Académicas** | Job nocturno: detecta si el promedio proyectado cae bajo la nota mínima |
| 20.4 | **Leitner Diario** | Recomienda las 30 flashcards con `next_review_at <= hoy` por caja |
| 20.5 | **Hora Pico** | Cruza `habit_completions + pomodoro_sessions` → *"Tus mejores horas son a las 9:00"* |
| 20.6 | **Endpoint Unificado** | `GET /api/unified-day` → clases + misiones + hábitos + eventos + flashcards pendientes |

> **Cumpleaños y eventos personales:** No requieren automatización. El estudiante los revisa visualmente en el calendario mensual. Los eventos con `type = 'birthday'` aparecen destacados en el calendario con un icono propio — sin jobs ni notificaciones push.


---

## 🗺️ Roadmap Técnico Recomendado

```mermaid
gantt
    title Epycus — Roadmap de Features
    dateFormat YYYY-MM-DD
    section Fase 1 — Misiones & Cursos (Base)
        Misiones: tipo + Kanban + Canvas    :2026-09-01, 2w
        Módulo Cursos independiente         :2026-09-08, 2w
        Periodo Académico                   :2026-09-15, 1w
    section Fase 2 — Evaluación & Proyectos
        Simulador de Notas                  :2026-09-22, 2w
        Gestión de Proyectos ABP            :2026-09-22, 2w
        Sílabo PDF + Visor                  :2026-10-01, 1w
    section Fase 3 — Aprendizaje Profundo
        Flashcards editables + Leitner      :2026-10-06, 2w
        Modo Simulacro (IA)                 :2026-10-13, 2w
    section Fase 4 — Planner & Tiempo
        Plan Diario bidireccional           :2026-10-20, 1w
        Pomodoro Universal (contexto)       :2026-10-20, 1w
        Calendario Total + Eventos Personal :2026-10-27, 2w
    section Fase 5 — Vida & Crecimiento
        Módulo Lecturas                     :2026-11-03, 2w
        Módulo Habilidades                  :2026-11-10, 2w
        Hábitos modo Break                  :2026-11-10, 1w
    section Fase 6 — Gamificación & Tienda
        Avatar Canvas + Atributos           :2026-11-17, 2w
        Tienda + Entretenimiento            :2026-11-24, 1w
        Motor de Automatización             :2026-12-01, 1w
```

---

## ✅ Resumen de Viabilidad

| # | Feature | Viabilidad | Nueva tabla/campo | Reutiliza |
|:--|:--------|:----------:|:-----------------:|:---------:|
| 1 | Misiones Kanban + Eisenhower visual | ✅ Completado | Campo `mission_type` en `missions` | `eisenhower_quadrant` ya existe |
| 2 | Tipos de tarea (académica, trabajo, personal) | ✅ Completado | Campo `mission_type` | `MissionModel`, `CalendarController` |
| 3 | Módulo Cursos independiente | ✅ Completado | Campos en `courses` | `CourseModel` ya existe |
| 4 | Gestión de Proyectos ABP + Canvas | ✅ Completado | `course_projects`, `project_phases` | `MissionModel`, `StudyGroups` |
| 5 | Periodo Académico | ✅ Completado | `academic_periods` | `users.cycle` |
| 6 | Sílabo PDF | ✅ Completado | Campo `syllabus_path` | Laravel `Storage` |
| 7 | Simulador de Notas | ✅ Completado | `grade_evaluations` | Vue `computed` |
| 8 | Imágenes arrastrables | ✅ Completado | JSON del bloque | Editor de apuntes |
| 9 | Flashcards + Leitner | ✅ Completado | `flashcards` | Cursos, Leitner 5 cajas |
| 10 | Simulacro con IA | ✅ Completado | — | `DeepSeekApiClient`, Flashcards auto-sync |
| 11 | Malos hábitos | ✅ Completado | Campo `habit_type` en `habits` | `HabitModel`, `Habits/Index.vue` |
| 12 | Plan Diario bidireccional | ✅ Completado | 3 campos en `missions` | `linked_mission_id`, `Missions/Index.vue` |
| 13 | Avatar en Canvas | ✅ Completado | — | `Gamification/Index.vue`, Procedural Avatar |
| 14 | Módulo Lecturas | ✅ Completado | `readings`, `reading_tags` | Biblioteca, avance de páginas |
| 15 | Módulo Habilidades | ✅ Completado | `personal_skills`, `personal_skill_logs` | Árbol de destrezas, niveles |
| 16 | Pomodoro Universal | ✅ Completado | `pomodoro_sessions.context_type` | Asociar a cualquier módulo |
| 17 | Calendario Total | ✅ Completado | `personal_events` | Selector de capas, `Calendar/Index.vue` |
| 18 | Tienda + Entretenimiento (reseñas) | ✅ Completado | 5 campos en `reward_redemptions` | `ShopController`, `Shop/Index.vue` |
| 19 | Personaje como espejo | ✅ Completado | — | Avatar de progreso y atributos RPG |
| 20 | Automatización sin IA | ✅ Completado | `automations` | `AutomationsService`, motor autónomo |

---

*Documento técnico de evaluación y diseño — Epycus · Rama `main`*
