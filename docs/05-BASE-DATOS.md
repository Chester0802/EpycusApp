\
# 05 — Base de datos

> MariaDB 10.11+, `utf8mb4_unicode_ci`, motor InnoDB. Cada módulo tiene sus migraciones en `app/Modules/{Modulo}/Infrastructure/Migrations/`.

---

## 1. Convenciones

- Tablas en plural y `snake_case`: `habit_completions`
- Claves foráneas: `{tabla_singular}_id`
- Timestamps `created_at` / `updated_at` en toda tabla salvo `telemetry_events` (que usa los suyos)
- Borrado lógico (`deleted_at`) solo donde se indique
- **Toda fecha se guarda en UTC.** La conversión a hora de Lima ocurre en presentación
- Índices explícitos en todo campo usado en `WHERE`, `JOIN` u `ORDER BY`

---

## 2. Modelo entidad-relación

```mermaid
erDiagram
    users ||--o| participants : "es"
    users ||--o{ habits : crea
    users ||--o{ pomodoro_sessions : ejecuta
    users ||--o{ missions : crea
    users ||--o{ journal_entries : escribe
    users ||--|| user_progress : tiene
    users ||--o{ xp_transactions : acumula
    users ||--|| user_preferences : configura
    users ||--o{ villain_instances : enfrenta
    users ||--o{ telemetry_events : genera
    users ||--o{ ai_conversations : consulta

    habits ||--o{ habit_completions : registra
    missions ||--o{ subtasks : contiene
    missions ||--o{ pomodoro_sessions : "se enfoca en"
    villains ||--o{ villain_instances : instancia
    study_sessions ||--o{ session_participants : reune
    study_sessions ||--o{ chat_messages : contiene
    ai_conversations ||--o{ ai_messages : contiene
```

---

## 3. Tablas principales

### Identity

```sql
CREATE TABLE users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(120) NOT NULL,
    email             VARCHAR(180) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    alias             VARCHAR(40)  NOT NULL,
    role              ENUM('participant','admin') NOT NULL DEFAULT 'participant',
    career            VARCHAR(60)  NULL,
    avatar_style      ENUM('health','business','technical','systems','law') NULL,
    avatar_gender     ENUM('m','f') NULL,
    cycle             TINYINT UNSIGNED NULL,
    institution_type  ENUM('universidad','instituto') NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token    VARCHAR(100) NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    INDEX idx_role (role)
) ENGINE=InnoDB;
```

`career`, `cycle` e `institution_type` se pueblan **solo desde listas cerradas** definidas en `config/careers.php`. El backend valida contra esa lista. Decisión D-16 del proyecto.

```sql
-- Tabla de seudonimización: acceso restringido
CREATE TABLE participants (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT UNSIGNED NOT NULL UNIQUE,
    participant_code  VARCHAR(20) NOT NULL UNIQUE,
    student_code      VARCHAR(40) NULL,
    whatsapp          VARCHAR(30) NULL,
    consent_granted_at TIMESTAMP NULL,
    enrolled_at       TIMESTAMP NULL,
    withdrawn_at      TIMESTAMP NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    CONSTRAINT fk_participant_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
```

> Esta tabla es la única que vincula identidad real con el código del estudio. **Nunca aparece en exportaciones.** `student_code` y `whatsapp` se cifran con `Crypt::encryptString()`. Ver `docs/06-SEGURIDAD.md`.

```sql
CREATE TABLE user_preferences (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                BIGINT UNSIGNED NOT NULL UNIQUE,
    surface_mode           VARCHAR(20) NOT NULL DEFAULT 'neumorphism',  -- 'neumorphism' | 'glass'
    notifications_enabled  BOOLEAN NOT NULL DEFAULT FALSE,
    created_at             TIMESTAMP NULL,
    updated_at             TIMESTAMP NULL,
    CONSTRAINT fk_preferences_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

> Se crea automáticamente al registrar (relación 1:1 obligatoria con `users`, ver diagrama
> arriba), con `surface_mode = 'neumorphism'` por defecto — ver `docs/04-DISENO-VISUAL.md` §2
> para los dos modos de superficie. `notifications_enabled` empieza en `false`: se activa
> explícitamente cuando el usuario acepta el permiso del navegador, nunca antes. **No hay
> campo de idioma**: toda la interfaz es en español, no existe selector (decisión del
> 2026-07-28, ver `docs/12-HISTORIAL.md`).

### Habits

```sql
CREATE TABLE habits (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    title       VARCHAR(120) NOT NULL,
    category    ENUM('estudio','sueno','ejercicio','alimentacion','otro') NOT NULL,
    frequency   JSON NOT NULL,          -- {"type":"daily"} | {"type":"weekly","days":[1,3,5]}
    icon        VARCHAR(40) NULL,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,
    INDEX idx_user_active (user_id, is_active),
    CONSTRAINT fk_habit_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE habit_completions (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    habit_id      BIGINT UNSIGNED NOT NULL,
    user_id       BIGINT UNSIGNED NOT NULL,
    completed_for DATE NOT NULL,        -- día al que corresponde, no cuándo se marcó
    completed_at  DATETIME NOT NULL,    -- momento real del marcado
    is_late       BOOLEAN NOT NULL DEFAULT FALSE,
    xp_awarded    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    was_capped    BOOLEAN NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP NULL,
    UNIQUE KEY uq_habit_day (habit_id, completed_for),
    INDEX idx_user_date (user_id, completed_for),
    CONSTRAINT fk_completion_habit FOREIGN KEY (habit_id)
        REFERENCES habits(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

La restricción `uq_habit_day` impone en la base de datos la regla "no se puede completar el mismo hábito dos veces el mismo día". **No confíes solo en la validación de aplicación**: una condición de carrera con doble clic la burlaría.

### Pomodoro

```sql
CREATE TABLE pomodoro_sessions (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          BIGINT UNSIGNED NOT NULL,
    mission_id       BIGINT UNSIGNED NULL,
    planned_minutes  TINYINT UNSIGNED NOT NULL,
    focus_minutes    TINYINT UNSIGNED NULL,
    state            ENUM('running','paused','completed','abandoned') NOT NULL,
    started_at       DATETIME NOT NULL,
    completed_at     DATETIME NULL,
    paused_seconds   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    xp_awarded       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    was_capped       BOOLEAN NOT NULL DEFAULT FALSE,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    INDEX idx_user_state (user_id, state),
    INDEX idx_user_date  (user_id, started_at),
    CONSTRAINT fk_pomodoro_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pomodoro_mission FOREIGN KEY (mission_id)
        REFERENCES missions(id) ON DELETE SET NULL
) ENGINE=InnoDB;
```

### Missions

```sql
CREATE TABLE missions (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id            BIGINT UNSIGNED NOT NULL,
    title              VARCHAR(160) NOT NULL,
    description        TEXT NULL,
    difficulty         ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
    due_date           DATE NULL,
    completed_at       DATETIME NULL,
    days_early_or_late SMALLINT NULL,   -- negativo = antes, positivo = tarde
    is_overdue         BOOLEAN NOT NULL DEFAULT FALSE,
    xp_awarded         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    deleted_at         TIMESTAMP NULL,
    INDEX idx_user_due (user_id, due_date),
    INDEX idx_overdue  (is_overdue, due_date),
    CONSTRAINT fk_mission_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE subtasks (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_id   BIGINT UNSIGNED NOT NULL,
    title        VARCHAR(160) NOT NULL,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    completed_at DATETIME NULL,
    sort_order   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    INDEX idx_mission (mission_id, sort_order),
    CONSTRAINT fk_subtask_mission FOREIGN KEY (mission_id)
        REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

`days_early_or_late` es la medida conductual de procrastinación más directa del sistema. Se calcula al completar y **no se recalcula nunca después**.

### Gamification

```sql
CREATE TABLE user_progress (
    user_id          BIGINT UNSIGNED PRIMARY KEY,
    total_xp         INT UNSIGNED NOT NULL DEFAULT 0,
    current_level    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    current_phase    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    current_streak   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    longest_streak   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    grace_days_left  TINYINT UNSIGNED NOT NULL DEFAULT 3,
    grace_month      CHAR(7) NULL,           -- 'YYYY-MM'
    last_activity_on DATE NULL,
    coins            INT UNSIGNED NOT NULL DEFAULT 0,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    INDEX idx_xp (total_xp DESC),            -- para el ranking
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE xp_transactions (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    amount       SMALLINT UNSIGNED NOT NULL,
    base_amount  SMALLINT UNSIGNED NOT NULL,
    multiplier   DECIMAL(3,2) NOT NULL DEFAULT 1.00,
    source_type  VARCHAR(32) NOT NULL,       -- habit|pomodoro|mission|journal|villain
    source_id    BIGINT UNSIGNED NOT NULL,
    was_capped   BOOLEAN NOT NULL DEFAULT FALSE,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_idempotency (user_id, source_type, source_id),
    INDEX idx_user_date (user_id, created_at),
    CONSTRAINT fk_xp_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

> **`uq_idempotency` es obligatoria.** Impide que un reintento de cola otorgue XP dos veces por la misma acción. Sin ella, el XP se infla y se corrompe la correlación entre progreso y comportamiento real, que es el núcleo del Pilar 3.

### Wellbeing

```sql
CREATE TABLE journal_entries (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    mood_score  TINYINT UNSIGNED NOT NULL,   -- 1..5
    content     TEXT NULL,                   -- CIFRADO
    tags        JSON NULL,
    entry_date  DATE NOT NULL,
    xp_awarded  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    INDEX idx_user_date (user_id, entry_date),
    CONSTRAINT fk_journal_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

`content` se cifra en la aplicación con el cast `encrypted` de Laravel. **Es dato sensible de salud mental**: no sale en exportaciones, no aparece en el panel de administración, no se loguea. Solo `mood_score` se usa en agregados.


### Motivation

```sql
CREATE TABLE motivational_quotes (
    id                      TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    text_es                 VARCHAR(280) NOT NULL,
    author                  VARCHAR(80)  NOT NULL,
    attribution_confidence  ENUM('documentada','atribuida') NOT NULL DEFAULT 'atribuida',
    is_active               BOOLEAN NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE usage_tips (
    id          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(32) NOT NULL,      -- 'habits','pomodoro','missions', etc.
    text_es     VARCHAR(200) NOT NULL,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NULL,
    INDEX idx_module (module_code, is_active)
) ENGINE=InnoDB;

CREATE TABLE user_quote_views (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL,
    quote_id   TINYINT UNSIGNED NOT NULL,
    shown_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_quote (user_id, quote_id),
    CONSTRAINT fk_uqv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_tip_views (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    tip_id       SMALLINT UNSIGNED NOT NULL,
    shown_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dismissed_at TIMESTAMP NULL,
    INDEX idx_user_tip (user_id, tip_id),
    CONSTRAINT fk_utv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

`user_quote_views` y `user_tip_views` guardan el historial completo, no solo el ultimo estado — de ahi se deriva que falta por mostrar en el ciclo actual (`NoRepeatPicker`, ver `01-MODULOS.md` #15). Volumen despreciable: 70 usuarios x 1 frase por login x 120 dias son unas 8.400 filas como mucho.

### Villains, StudyGroups, AI

```sql
CREATE TABLE villains (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(32) NOT NULL UNIQUE,   -- procrastination|distraction|...
    name        VARCHAR(60) NOT NULL,
    description TEXT NOT NULL,
    image_path  VARCHAR(180) NOT NULL,
    weakness    JSON NOT NULL                   -- qué acciones le hacen daño
) ENGINE=InnoDB;

CREATE TABLE villain_instances (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    villain_id  TINYINT UNSIGNED NOT NULL,
    week_number TINYINT UNSIGNED NOT NULL,
    max_hp      SMALLINT UNSIGNED NOT NULL,
    current_hp  SMALLINT UNSIGNED NOT NULL,
    starts_on   DATE NOT NULL,
    ends_on     DATE NOT NULL,
    defeated_at DATETIME NULL,
    UNIQUE KEY uq_user_week (user_id, week_number),
    CONSTRAINT fk_vi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE study_sessions (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    host_id     BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(80) NOT NULL,
    max_seats   TINYINT UNSIGNED NOT NULL DEFAULT 8,
    state       ENUM('open','running','closed') NOT NULL DEFAULT 'open',
    started_at  DATETIME NULL,
    closed_at   DATETIME NULL,
    created_at  TIMESTAMP NULL,
    INDEX idx_state (state, created_at)
) ENGINE=InnoDB;

CREATE TABLE chat_messages (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    body       VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id, id),   -- clave para el polling incremental
    CONSTRAINT fk_msg_session FOREIGN KEY (session_id)
        REFERENCES study_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ai_quotas (
    user_id       BIGINT UNSIGNED NOT NULL,
    quota_date    DATE NOT NULL,
    used_count    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, quota_date),
    CONSTRAINT fk_quota_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

El índice `idx_session_id (session_id, id)` es el que hace barato el polling: `WHERE session_id = ? AND id > ?` se resuelve con una búsqueda por rango en el índice.

**Los mensajes de chat se purgan a los 7 días** con un cron. Son efímeros y no son dato del estudio; conservarlos consume espacio y añade riesgo de protección de datos sin aportar nada.

---

## 4. Telemetría

Esquema completo en `docs/02-TELEMETRIA.md` §4. Resumen: `telemetry_events` con índices sobre `(user_id, occurred_at)`, `(event_name, occurred_at)` e `(intervention_day)`.

Volumen estimado: ~554.000 filas, ~250 MB. **Cabe en el límite de 3 GB por base del hosting.** No requiere particionado.

---

## 5. Sesiones y cache en base de datos

Obligatorio por la restricción de inodos del hosting (400.000 totales, 151.585 ya usados):

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

```bash
php artisan session:table
php artisan cache:table
php artisan queue:table
```

Si se dejan en `file`, cada sesión y cada entrada de cache es un archivo. En 66 días con 70 usuarios eso son decenas de miles de inodos consumidos para nada.

---

## 6. Reglas de integridad

| Regla | Implementación |
|---|---|
| Un hábito, un completado por día | `UNIQUE (habit_id, completed_for)` |
| XP idempotente | `UNIQUE (user_id, source_type, source_id)` |
| Un villano por usuario y semana | `UNIQUE (user_id, week_number)` |
| Una cuota de IA por usuario y día | `PRIMARY KEY (user_id, quota_date)` |
| No borrar usuarios con telemetría | `ON DELETE RESTRICT` en `telemetry_events` |
| No borrar participantes | `ON DELETE RESTRICT` en `participants` |

Las dos últimas son deliberadas: protegen el dataset del estudio de un borrado accidental.

---

## 7. Semillas

```bash
php artisan db:seed --class=VillainSeeder        # los 5 villanos
php artisan db:seed --class=WallpaperSeeder      # catálogo de fondos
php artisan db:seed --class=CareerSeeder         # lista cerrada de carreras
php artisan db:seed --class=SuggestedHabitSeeder # hábitos sugeridos al registrarse
```

Solo en desarrollo:

```bash
php artisan db:seed --class=DemoParticipantSeeder  # 70 usuarios con 66 días de datos falsos
```

`DemoParticipantSeeder` es indispensable: permite probar el export, el ranking y el panel de administración con volumen realista **antes** del día 43, y sirve para la prueba de carga del checklist de telemetría.

---

## 8. Copias de seguridad durante la intervención

Del 07/09 al 11/11 es innegociable:

| Frecuencia | Qué | Dónde |
|---|---|---|
| Diaria, 03:00 | `mysqldump` completo comprimido | Servidor + descarga a Drive |
| Semanal | Export CSV de telemetría | Drive del equipo |

```bash
# cron diario 03:00
0 3 * * * cd /home/u897008619/domains/app.epycus.es && \
  mysqldump -u USER -pPASS DB | gzip > storage/backups/$(date +\%F).sql.gz && \
  find storage/backups -name "*.sql.gz" -mtime +14 -delete
```

Los datos de los 66 días no se pueden reconstruir. **Una copia local en el mismo servidor no es una copia de seguridad**: si el hosting falla, se pierde todo. Descarga semanal obligatoria al Drive del equipo.
