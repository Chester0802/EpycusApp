# Auditoría Integral End-to-End — EpycusApp

## Credenciales y Accesos

> ⚠️ Las credenciales reales están en `credenciales.md` (en `.gitignore`). Este doc solo tiene placeholders.

| Recurso | Detalle |
|---|---|
| **URL producción** | `https://app.epycus.es` |
| **App Android** | `es.epycus.app` (Play Store) — código en `C:\Users\marco\Pictures\Epycus` |
| **SSH VPS** | `ssh -p 2222 root@147.93.119.193` (puerto `2222`) |
| **Repositorio web** | `https://github.com/Chester0802/EpycusApp.git` (rama `main`) |
| **Google OAuth redirect** | Web: `https://app.epycus.es/signin-google` |
| **Comandos VPS** | `journalctl -u epycus-web --no-pager -n 100` · `tail -50 /var/log/nginx/error.log` |
| **Deploy** | `cd /tmp/epycus-build && git pull && dotnet publish -c Release -o /var/www/epycus-web && systemctl restart epycus-web` |

---

## Agentes — Plan de Auditoría

### 1. Backend + API
Probar todos los endpoints REST y SignalR (éxito + casos borde). Verificar que no haya excepciones no controladas, fugas de memoria, timeouts, errores de serialización JSON.

**Endpoints**: Auth, Pomodoro, Hábitos, Diario, Perfil/Progreso, IA, Misiones, Bienestar, Admin, SignalR, Health.

### 2. Aspecto Visual (Frontend + CSS + UX)
Navegar todas las páginas. Verificar CSS, temas (noche épica/sakura), responsive, accesibilidad, service worker, PWA.

**Páginas**: `/Home`, `/Pomodoro`, `/Progreso`, `/Habitos`, `/Diario`, `/Perfil`, `/Login`.

### 3. Arquitectura y Código
Revisar estructura del proyecto, patrones, DI, middleware, seguridad, calidad del código.

**Áreas**: `Program.cs`, `Middleware/`, `Controllers/`, `Servicios/`, `Modelos/`, `wwwroot/`, `Views/`, `Datos/`, `deploy/`.

### 3b. Arquitectura Móvil (Android)
Revisar app Android: calidad código, manejo de estado, cache offline, ciclo de vida, errores conocidos.

**Áreas**: `api/`, `data/local/`, `repository/`, `ui/`, `util/`, `build.gradle.kts`.

### 4. Base de Datos y Rendimiento
Revisar esquema, índices, consultas lentas, EF Core, migraciones.

**Verificar**: índices, N+1, migraciones aplicadas, tamaño tablas, slow query log, pool conexiones.

### 5. Conexión Móvil-Web (Cross-platform)
Verificar que app Android y web compartan el mismo backend y datos. Detectar inconsistencias de API.

**Escenarios**: Pomodoro dual, login dual, offline→online, racha compartida.

### 6. Seguridad (Web + Móvil)
OWASP Top 10, hardening servidor, nginx, headers, sesiones web + JWT móvil, seguridad Android.

**Verificar**: headers HTTP, SQLi, XSS, CSRF, rate limiting, nginx, SSL, firewall, fail2ban.

### 7. Infraestructura y DevOps
Revisar deploy, CI/CD, monitoreo, logs, backups, escalabilidad, Play Store readiness.

**Verificar**: deploy script, systemd, backups, SSL renew, recursos, fail2ban.

### 8. Testing y Casos de Uso Reales
Simular usuarios reales: nuevo usuario, avanzado, multiplataforma, mala conexión, malicioso.

**Perfiles**: María (web), Ana (móvil), Carlos (multi), Pedro (3G), Hacker.

---

## Errores Activos

> Consolidado de todas las secciones. Ordenados por severidad.

### 🔴 CRÍTICOS

| # | Descripción | Sección | Archivo/Línea | Solución propuesta |
|---|---|---|---|---|
| C1 | **Secretos en historial git**: JWT secret, Gemini API key, DeepSeek API key, SMTP password, SSH password están en commits antiguos | Seguridad / Arq | Historial git | Rotar todas las credenciales. Usar `git filter-repo` o `bfg` para purgar del historial |
| C2 | **JWT no invalidable**: No hay blacklist de tokens ni verificación de `iat` contra SecurityStamp. Token robado sirve 60 min | Seguridad | `ConfiguracionServicios.cs:58-98` | Implementar blacklist distribuida o incrementar SecurityStamp en cada logout |
| C3 | **Sentry DSN vacío**: `"Dsn": ""` en producción. Errores no se reportan | Seguridad | `appsettings.json:54` | Configurar DSN real de Sentry |

### 🟠 ALTOS

| # | Descripción | Sección | Archivo/Línea | Solución propuesta |
|---|---|---|---|---|
| A1 | **Entidades EF expuestas en API**: `Carreras()` devuelve `List<Carrera>` (entidad EF) sin DTO | Arq (3) | `ApiAuthController.cs:167` | Crear `CarreraDto` con solo campos necesarios |
| A2 | **Sin rate limiting en admin endpoints**: AdminController MVC no tiene rate limiting en login | Seguridad | `AdminController.cs:31-65` | Agregar `[EnableRateLimiting("Auth")]` |
| A3 | **Logout no invalida JWT server-side**: refresca token pero JWT sigue válido hasta expirar | Seguridad | `AutenticacionController.cs:331-336` | Implementar blacklist de JWT |

### 🟡 MEDIOS

| # | Descripción | Sección | Archivo/Línea | Solución propuesta |
|---|---|---|---|---|
| M1 | **`SuppressModelStateInvalidFilter = true`**: desactiva validación automática, no todos los endpoints verifican `ModelState.IsValid` | Arq (3) | `Program.cs:35` | Eliminar suppress o agregar check en todos los endpoints |
| M2 | **`RespuestaApi<object>` sin tipo**: endpoints pierden type safety | Arq (3) | `ApiHabitosController.cs:36,50,109,123,201,208` · `ApiIaController.cs:63` | Crear DTOs específicos |
| M3 | **Lógica de cálculo en controller**: XP y nivel calculados en `HomeController.Index()` en lugar de servicio | Arq (3) | `HomeController.cs:49-56` | Mover a `IServicioProgreso` |
| M4 | **N+1 queries**: `RegistrarCiclo()` carga sesión, config y sub-tarea en 3 queries separadas | Arq (3) | `ServicioPomodoro.cs:100-126` | Usar `.Include()` + `.ThenInclude()` |
| M5 | **Racha calculada en memoria**: carga TODAS las sesiones y calcula en C# | Arq (3) | `ServicioPomodoro.cs:316-339` | Usar SQL ventana o limitar a últimos 30 días |
| M6 | **Sin `AsNoTracking()`**: queries de solo lectura trackean entidades innecesariamente | Arq (3) | `ServicioPomodoro.cs:343-356,364-388` | Agregar `.AsNoTracking()` |
| M7 | **Excepción como flujo de control**: `InvalidOperationException` para validaciones de negocio | Arq (3) | `ServicioPomodoro.cs:43,50,59` | Usar Result pattern (FluentResults/OneOf/ErrorOr) |
| M8 | **CSP no incluye Google Fonts**: `font-src` y `style-src` no listan `fonts.googleapis.com` ni `fonts.gstatic.com` | Arq (3) | `ConfiguracionMiddleware.cs:43` | Agregar a CSP |
| M9 | **Manifest.json sin iconos 192x192 y 512x512**: requeridos por PWA | Frontend (2) | `manifest.json:11-24` | Agregar iconos con `purpose: "any maskable"` |
| M10 | **Contraste insuficiente tema Sakura**: `--text-secondary: #7d5f7a` sobre `--bg-elevated: #ffe8f5` (ratio ~2.5:1) | Frontend (2) | `variables.css:27-28` | Oscurecer a ~#6b4a68 o usar fondo más claro |
| M11 | **Google Fonts (Inter, Orbitron, Nunito) no cargadas**: referenciadas en CSS pero sin `<link>` en layouts | Frontend (2) | `_Layout.cshtml` · `_LayoutAuth.cshtml` · `variables.css:312-316` | Agregar `<link>` a Google Fonts en ambos layouts |
| M12 | **Gráfico semanal — días no asegurados en español**: backend podría devolver inglés con cultura invariante | Frontend (2) | Backend endpoint `/api/v1/pomodoro/estadisticas-semanales` | Formatear fechas con cultura "es-ES" |
| M13 | **CSP sin `report-uri`/`report-to`**: vulnerabilidades CSP no se reportan | Seguridad | `ConfiguracionMiddleware.cs:43` | Agregar `report-uri /csp-report;` |
| M14 | **`PROMT_AUDITORIA.md` línea 36 dice "3 endpoints" pero lista 4**: error de documentación | Arq (3) | `PROMT_AUDITORIA.md:36` | Cambiar "3" por "4" |

### 🟢 BAJOS

| # | Descripción | Sección | Archivo/Línea | Solución propuesta |
|---|---|---|---|---|
| B1 | **Skeleton page loading no funcional**: `ep-page-loading` nunca añade `.is-loading` | Frontend (2) | `_Layout.cshtml:99-103` | Eliminar o corregir lógica |
| B2 | **Hero image login `alt="Epycus"`**: imagen decorativa debería tener `alt=""` | Frontend (2) | `_LayoutAuth.cshtml:34` | Cambiar a `alt=""` |
| B3 | **Falta `<meta name="color-scheme">`**: Chrome/Safari usan defaults claros en modo oscuro | Frontend (2) | `_Layout.cshtml` · `_LayoutAuth.cshtml` | Agregar `content="dark light"` |
| B4 | **CSS excesivo en layout general**: dashboard.css, perfil.css, ia.css cargados en todas las páginas | Frontend (2) | `_Layout.cshtml:11-13` | Mover a `@section Styles` |
| B5 | **Sidebar duplicado**: `site.css:192` (260px) vs `epycus-modern.css:37` (280px) con conflictos | Frontend (2) | `site.css:192-201` · `epycus-modern.css:37-51` | Consolidar en un archivo |
| B6 | **`ep-fade-in` duplicado**: definido en `site.css:823` y `site.css:1253` con duración distinta (0.3s vs 0.35s) | Frontend (2) | `site.css:823-830,1253-1260` | Eliminar duplicado, unificar duración |
| B7 | **Tema oscuro duplicado**: mismas variables en `variables.css:173` (`[data-theme="dark"]`) y `tema-noche-epica.css` | Frontend (2) · Arq (3) | `variables.css:173-309` · `tema-noche-epica.css` | Decidir fuente de verdad única |
| B8 | **`site.js` vacío**: se carga en cada página pero solo tiene un comentario | Arq (3) | `site.js:1` | Eliminar referencia o poblar |
| B9 | **Carga excesiva de CSS**: 10 archivos + 2 CDN en layout general | Arq (3) | `_Layout.cshtml:7-17` | Consolidar en bundles |
| B10 | **SW cache-first**: sirve contenido stale para navegación | Arq (3) | `sw.js:56-67` | Cambiar a network-first |
| B11 | **Header `X-XSS-Protection` deprecado**: Chrome lo ignora desde 2019 | Seguridad | `ConfiguracionMiddleware.cs:38` | Eliminar |
| B12 | **`[ValidateAntiForgeryToken]` redundante**: ya aplicado globalmente | Arq (3) | `AutenticacionController.cs` · `PerfilController.cs` | Quitar atributos redundantes |
| B13 | **`AddApplicationPart` redundante** en `Program.cs` | Arq (3) | `Program.cs:42` | Eliminar |
| B14 | **`DiasSemanaHabito` DbSet formato inconsistente**: salto de línea extraño | Arq (3) | `ContextoAplicacion.cs:38-39` | Formatear correctamente |
| B15 | **HSTS sin `preload`**: no se puede incluir en listas preload | Seguridad | `nginx-epycus.conf:29` | Agregar `; preload` |
| B16 | **Permissions-Policy**: no incluye `interest-cohort=()` para evitar FLoC | Seguridad | `ConfiguracionMiddleware.cs:40` | Agregar |
| B17 | **nginx sin `server_tokens off;`**: expone versión de nginx | Seguridad | `nginx-epycus.conf` | Agregar `server_tokens off;` |

---

## Correcciones Realizadas

| # | Descripción | Severidad original | IA responsable | Evidencia |
|---|---|---|---|---|
| ✅ 1 | **XSS en chat IA**: `FormatearMensaje()` sanitiza con `HtmlEncode()` | CRÍTICA | IA 1 / IA 3 | `Views/Ia/Index.cshtml:174` — `System.Net.WebUtility.HtmlEncode(texto)` antes del formateo markdown |
| ✅ 2 | **`ApiAdminController` sin verificación de rol**: cambiado `[Authorize]` → `[Authorize(Roles = "Admin")]` + método `EsAdministrador()` | CRÍTICA | IA 2 / IA 3 | `ApiAdminController.cs:12` y `ServicioAdmin.cs` |
| ✅ 3 | **Admin login no distinguía administradores**: agregada verificación `_servicioAdmin.EsAdministrador()` | ALTA | IA 2 / IA 3 | `ApiAdminController.cs:34-35` |
| ✅ 4 | **`@Html.Raw()` con datos serializados en Home/Index**: reemplazado por `@Json.Serialize()` | MEDIA | IA 1 | `Views/Home/Index.cshtml:153-154` |
| ✅ 5 | **Response compression no configurada**: agregado Brotli + Gzip | MEDIA | IA 3 | `ConfiguracionServicios.cs` — `AddResponseCompression()` + `UseResponseCompression()` |
| ✅ 6 | **`PROMT_AUDITORIA.md` expone credenciales SSH/DB**: reemplazadas por placeholders | CRÍTICA | IA 1 / IA 3 | `PROMT_AUDITORIA.md:5-21` — referencias a `credenciales.md` |
| ✅ 7 | **`appsettings.json` en `.gitignore`**: credenciales no se suben nuevas | CRÍTICA | IA 1 | Commit `b96eeb9` |
| ✅ 8 | **JSON cycle errors**: `ReferenceHandler.IgnoreCycles` en opciones globales | MEDIA | IA 1 | `Program.cs:45` |
| ✅ 9 | **`diario-animo.css` con vars no estándar**: migradas 22 ocurrencias de `--texto`→`--text-primary`, `--hover`→`--bg-hover`, etc. | ALTA | IA 4 (1IA) | `wwwroot/css/diario-animo.css` — todas las vars reemplazadas |
| ✅ 10 | **`credenciales.md` añadido a `.gitignore`**: archivo seguro local/VPS, no se sube a GitHub | CRÍTICA | 1IA | Commit `b96eeb9` · `.gitignore` |

---

## Trabajo Pendiente — Asignación Paralela

### Para nuevas IAs (secciones intactas)

| Asignado | Sección | Descripción |
|---|---|---|
| — | **4. Base de Datos** | Revisar índices, N+1, migraciones, tamaño tablas, slow queries, pool conexiones |
| — | **5. Cross-platform** | Verificar consistencia web↔móvil en endpoints, sesiones, estadísticas |
| — | **7. Infraestructura/DevOps** | Revisar deploy, backups, SSL, recursos, fail2ban, CI/CD |
| — | **8. Testing** | Ejecutar casos de uso reales (María, Ana, Carlos, Pedro, Hacker) |
| — | **3b. Android** | Revisar código móvil: bugs-1 a 7, patterns, Room, cache, ciclo de vida |

### Para IAs correctoras (errores activos)

| Asignado | Errores | Prioridad |
|---|---|---|
| — | **C1** Rotar credenciales + purgar historial git | 🔴 Crítica |
| — | **C2** Implementar blacklist JWT o SecurityStamp | 🔴 Crítica |
| — | **C3** Configurar DSN de Sentry | 🔴 Crítica |
| — | **A1** Crear `CarreraDto` para endpoint Carreras() | 🟠 Alta |
| — | **A2** Rate limiting en admin login | 🟠 Alta |
| — | **A3** JWT logout invalidation | 🟠 Alta |
| — | **M1-M8** Errores de arquitectura (ModelState, DTOs, N+1, AsNoTracking, Result pattern) | 🟡 Media |
| — | **M9-M14** Errores frontend pendientes (manifest, contraste, fonts, gráfico, CSP, doc) | 🟡 Media |
| — | **B1-B17** Errores bajos (skeleton, alt, meta, CSS duplicado, site.js, HSTS, etc.) | 🟢 Bajo |

---

## Resultados de Auditoría

> Cada IA escribe aquí sus hallazgos. Leer lo que las otras escribieron para evitar duplicados y complementar.

### IA-1 — Base de Datos y Rendimiento

**✅ Funcionando correctamente:**
- **8 migraciones** aplicadas sin pendientes, snapshot actualizado con EF Core 9.0
- **26 DbSets** bien definidos y mapeados
- **UTF8mb4** charset configurado globalmente
- **Índices individuales** en todas las FK (`UsuarioId`, `HabitoId`, `CategoriaId`, etc.)
- **Relaciones** con `OnDelete` correcto (Cascade/SetNull/Restrict según el caso)
- **`EF.Functions.Random()`** usado para tip aleatorio (no carga toda la tabla)
- **Charset utf8mb4** consistente en todas las columnas `longtext`
- **Connection string** sin `Max Pool Size` explícito (usa default de MariaDB/MySQL)

**❌ Errores encontrados:**

| # | Descripción | Severidad | Archivo/Línea |
|---|---|---|---|
| 1 | **Sin índice compuesto en `SesionPomodoro(UsuarioId, FechaInicio)`** — las consultas más frecuentes (historial, estadísticas semanales, avanzadas) filtran por ambos campos pero solo hay índice individual en `UsuarioId`. Con muchas sesiones, hará escaneo de filas innecesario. | **MEDIA** | `ContextoAplicacion.cs:230-232` · consultas en `ServicioPomodoro.cs:294-311,342-356,359-389,391-433` |
| 2 | **Sin índice en `SesionPomodoro(UsuarioId, FueCompletada, FechaInicio)`** — el cálculo de racha (`ObtenerRachaActualAsync`) filtra por `UsuarioId` + `FueCompletada=true` + ordena por `FechaInicio`. Sin índice compuesto, hará escaneo completo de sesiones del usuario. | **MEDIA** | `ServicioPomodoro.cs:316-339` |
| 3 | **Sin índice en `ConfiguracionesPomodoro(UsuarioId)`** — cada operación de pomodoro (`RegistrarCiclo`, `FinalizarSesion`) carga la configuración por `UsuarioId`. Sin índice, escanea toda la tabla. | **MEDIA** | `ContextoAplicacion.cs` (falta declaración) |
| 4 | **Racha calculada en memoria** (`ObtenerRachaActualAsync`): carga TODAS las sesiones completadas del usuario y calcula la secuencia en C#. Con usuarios de varios meses, esto cargará cientos/miles de filas en memoria. | **MEDIA** | `ServicioPomodoro.cs:314-339` |
| 5 | **Sin `AsNoTracking()` en queries de solo lectura**: `ObtenerHistorialAsync`, `ObtenerEstadisticasPeriodoAsync`, `ObtenerEstadisticasSemanalesAsync`, `ObtenerEstadisticasAvanzadasAsync`, `ObtenerSesionesHoyAsync`, `ObtenerRachaActualAsync` trackean entidades que nunca se modifican. | **MEDIA** | `ServicioPomodoro.cs:288,294,316,342,364,393` |
| 6 | **N+1 queries en `RegistrarCiclo()`**: 3 queries separadas para sesión (line 100), config (line 113) y subTarea (line 121). Podrían cargarse con `.Include()`. | **MEDIA** | `ServicioPomodoro.cs:98-126` |
| 7 | **N+1 queries en `FinalizarSesion()`**: misma pauta — sesión (line 143), subTarea (line 158), config (line 161) en queries separadas. | **MEDIA** | `ServicioPomodoro.cs:141-170` |
| 8 | **N+1 queries en `IniciarSesion()`**: 3 queries separadas para hábito (line 41), misión (line 48) y subTarea (line 55). La subTarea ya incluye `.Include(st => st.Mision)` innecesario si solo se verifica existencia. | **MEDIA** | `ServicioPomodoro.cs:37-60` |
| 9 | **Fechas formateadas en C# sin hora local**: `Fecha = dia.ToString("ddd", ...)` y `Fecha = desde.ToString("yyyy-MM-dd")` se hacen en memoria con `CultureInfo`, pero las fechas vienen en UTC. No hay conversión a zona horaria del usuario. | **BAJA** | `ServicioPomodoro.cs:348,379,416` |
| 10 | **`ObtenerTareasEnfoqueAsync()` hace 2 DB calls paralelizables**: carga hábitos y misiones en dos queries separadas secuenciales cuando podrían ser `Task.WhenAll()`. | **BAJA** | `ServicioPomodoro.cs:448-468` |
| 11 | **`DiasSemanaHabito` DbSet con salto de línea extraño** en la declaración (línea 38-39). | **BAJA** | `ContextoAplicacion.cs:38-39` |
| 12 | **Connection string sin `Max Pool Size` ni `Connection Lifetime`**: usa defaults (pool=100, lifetime=0). En producción con muchos usuarios concurrentes, puede haber contención de conexiones. | **BAJA** | `appsettings.json:9` |

**⚠️ Complementos a otras IAs:**
- Coincido con el error #4 de Arquitectura (N+1 en ServicioPomodoro) — lo detallo con más precisión en errores 6-8 de esta tabla.
- El error #5 de Arquitectura (racha en memoria) lo confirmo y amplío en error #4.
- El error #6 de Arquitectura (AsNoTracking) lo confirmo y detallo líneas exactas en error #5.
- Los errores #1, #2, #3 (índices) no estaban documentados previamente — son nuevos hallazgos de esta auditoría.

### IA-2 — Conexión Móvil-Web (Cross-platform)
*(pendiente)*

### IA-3 — Infraestructura y DevOps
*(pendiente)*

### IA-4 — Testing y Casos de Uso
*(pendiente)*

### IA-5 — Arquitectura Móvil (Android)
*(pendiente)*

### IA-6 — Seguridad (Web + Móvil)
*(pendiente)*
