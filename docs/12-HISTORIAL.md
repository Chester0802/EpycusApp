\
# 12 — Historial de sesiones de IA

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

## 2026-07-27 — ChatGPT

**Qué se hizo:** Instaló la base del proyecto: Laravel 12 con PHP 8.3 portable (el sistema
tenía PHP 8.2), Inertia 2 + Vue 3 + Tailwind 3.4 + Vite 5, Laravel Breeze (autenticación
inicial), MariaDB de XAMPP configurada con la base `epycus_local` y migraciones base
ejecutadas. Creó la capa `Shared` inicial (`TransactionManagerInterface`,
`DatabaseTransactionManager`, `SharedServiceProvider`), los archivos de configuración del
proyecto (`config/gamification.php`, `config/careers.php`, `config/intervention.php`) y las
herramientas de calidad (Pint, PHPStan nivel 6, ESLint, Prettier).

**Decisiones tomadas:** no quedó registrado por ChatGPT.

**Verificado cómo:** no quedó registrado. Se quedó sin tokens antes de completar el primer
módulo funcional.

**Pendiente / qué falta:** ver entrada de DeepSeek, mismo día, que continuó desde aquí.
