# PENDIENTES — Auditoría Pre-Producción EpycusApp

> Generado: 2026-06-15 | Última actualización: 2026-06-18 (ARQ: +17 hallazgos | corregidos: CRITICO-008, UX-014, UX-015, UX-016, UX-017 a UX-034 | auditoría UI/UX completa: 18 hallazgos nuevos UX-017 a UX-034 | **segunda auditoría UI/UX: 12 nuevos UX-035 a UX-046, corregidos UX-019, UX-027, UX-029, UX-035 a UX-046**) | **✅ TERCERA AUDITORÍA UI/UX: 12 nuevos UX-047 a UX-054, TODOS CORREGIDOS** | **🔄 CUARTA AUDITORÍA UI/UX: 5 nuevos UX-055 a UX-059, PENDIENTES** | **✅ CORREGIDO: ARQ-001, ARQ-002, ARQ-004, ARQ-005, IA-IMP-11 (credenciales reales reemplazadas por placeholders, API key movida a header)** | **✅ VPS: VPS-007 (monitoreo), VPS-008 (rotación logs), VPS-009 (página mantenimiento) — 3 nuevos scripts/conf: `deploy/monitoreo-uptime.sh`, `deploy/journald-log-rotation.conf`, `deploy/maintenance.html`, `deploy/maintenance.sh`** | **✅ ODS 3: audit complete — B-FALTA-01 a B-FALTA-11, B-ERR-01/06/07, B-INC-01 a B-INC-05, B-UI-02 a B-UI-05 corregidos** | **✅ CRITICO-009 (jQuery), CRITICO-010 (Categoria BD), VPS-010 (sincronización GitHub↔VPS) corregidos**
> Proyecto: EpycusApp (ASP.NET Core 9 + MariaDB + Gemini API) | VPS-007 ✅, VPS-008 ✅, VPS-009 ✅, VPS-010 ✅

## Flujo de trabajo (para la IA)

Este proyecto se desarrolla localmente en Windows y se despliega en un VPS Debian 13. El flujo es:

1. **Local (Windows):** Se edita el código, se commit y se push a GitHub (`main`)
2. **VPS (SSH desde cmd):** Se ejecutan los comandos de deploy manual:
   ```bash
   cd /tmp/epycus-build
   git pull origin main
   sudo systemctl stop epycus-web
   dotnet publish EpycusApp.csproj -c Release -o /var/www/epycus-web
   sudo chown -R www-data:www-data /var/www/epycus-web
   sudo systemctl start epycus-web
   ```
3. **Verificación:** `curl http://localhost:5000/health` y probar la web en `http://app.epycus.es`

> **Nota para la IA:** Cuando sugieras cambios, recuerda que el ciclo es: editar → commit → push → SSH → pull → publish → restart. No olvides indicar al usuario que ejecute el deploy manual después del push.

---

## 🔴 CRÍTICOS

| ID | Prioridad | Archivo | Problema | Riesgo | Estado | Solución |
|----|-----------|---------|----------|--------|--------|----------|
| CRITICO-001 | Alta | `appsettings.json` | API Key de Gemini y Google OAuth en texto plano (placeholder) | **Muy Alto** | ✅ Corregido | Usar variables de entorno en producción. El archivo está en `.gitignore`. |
| CRITICO-002 | Alta | `deploy/setup-vps.sh` | Contraseña de MariaDB hardcodeada (`***REMOVED***`) | **Muy Alto** | ✅ Corregido | Ahora usa `$DB_PASSWORD` (variable de entorno). |
| CRITICO-003 | Alta | `deploy/epycus-web.service` | Placeholder de credenciales en archivo de servicio | **Alto** | ⚠️ Mitigado | Archivo en `.gitignore`. Usar `.example` como template. |
| CRITICO-004 | Alta | `.github/workflows/ci-cd.yml` | Pipeline CI/CD vacío (0 bytes) | **Muy Alto** | ✅ Corregido | Creado pipeline completo con build, calidad, seguridad y deploy. |
| CRITICO-005 | Alta | `Servicios/Implementaciones/ServicioAutenticacion.cs:316` | Envía email de recuperación cuando debería enviar bienvenida | **Alto** | ✅ Corregido | Cambiado a `EnviarBienvenida()`. |
| CRITICO-006 | **Muy Alta** | `appsettings.json` | Google OAuth: `ClientId` y `ClientSecret` son placeholders (`YOUR_GOOGLE_CLIENT_ID...`) | **Muy Alto** | ⚠️ Pendiente | Configurar OAuth real en https://console.cloud.google.com/ y poner los valores por variable de entorno. |
| CRITICO-007 | **Muy Alta** | `appsettings.json` | Servicio de correo: `Correo:Contrasena` es placeholder (`ROTATED_SMTP_PASSWORD`) | **Muy Alto** | ⚠️ Pendiente | Generar App Password de Gmail y configurarlo como variable de entorno. Sin esto, registro, recuperación y verificación de correo no funcionan. |
| CRITICO-008 | **Muy Alta** | `ViewModels/Autenticacion/LoginViewModel.cs`, `AdminLoginViewModel.cs` y otros 6 ViewModels | **Mojibake en Display Names y ErrorMessages:** archivos `.cs` guardados en Windows-1252 en lugar de UTF-8 | **Alto** | ✅ Corregido | Archivados reescritos en UTF-8 sin BOM con caracteres españoles correctos (ó, ñ, á, é, etc.). |
| CRITICO-009 | **Muy Alta** | `Views/Shared/_ValidationScriptsPartial.cshtml` | **`jQuery is not defined`** — `jquery.validate.min.js` y `jquery.validate.unobtrusive.min.js` se cargaban sin que `jquery.min.js` estuviera disponible en producción | **Alto** | ✅ Corregido | Agregado `<script src="~/lib/jquery/dist/jquery.min.js">` como primera línea del partial. Commit `3da7ce5`. |
| CRITICO-010 | **Muy Alta** | `Models/Entidades/FraseMotivacional.cs` / BD | **500 Internal Server Error — `Unknown column 'f.Categoria'`** — propiedad `Categoria` agregada al modelo pero migración no aplicada en BD producción | **Alto** | ✅ Corregido | Ejecutado `ALTER TABLE FrasesMotivacionales ADD COLUMN Categoria` en MySQL. Migración `AddCategoriaToFraseMotivacional` subida a GitHub. |

---

## 🟡 IMPORTANTES

| ID | Prioridad | Archivo | Problema | Riesgo | Estado | Solución |
|----|-----------|---------|----------|--------|--------|----------|
| IMP-001 | Alta | `Datos/Semilla/DatosSemilla.cs` | Referencia a imágenes inexistentes (`nivel_0.png`) | **Medio** | ✅ Corregido | Cambiado a `IngSis_mas_nivel1.png` y `IngSis_fem_nivel1.png`. |
| IMP-002 | Alta | `Servicios/Implementaciones/ServicioIA.cs` | Sin reintentos ni timeout en llamadas a Gemini | **Medio** | ✅ Corregido | Agregado retry (2 intentos) con backoff exponencial y timeout de 30s. |
| IMP-003 | Alta | `Program.cs` | Sin headers de seguridad (CSP, X-Frame-Options, etc.) | **Medio** | ✅ Corregido | Agregado middleware de security headers. |
| IMP-004 | Alta | `Program.cs` | Sin configuración CORS | **Medio** | ✅ Corregido | Agregado CORS con orígenes configurables. |
| IMP-005 | Media | `Program.cs` | Sin Rate Limiting | **Medio** | ✅ Corregido | Agregado con políticas Api (100/min) y Gemini (20/min). |
| IMP-006 | Alta | `Datos/Semilla/DatosSemilla.cs` | Sin log de errores en operaciones de semilla | **Medio** | ✅ Corregido | Agregado try-catch con logging. |
| IMP-007 | Alta | Varios servicios | Ausencia de ILogger en `ServicioGamificacion`, `ServicioAdmin`, `ServicioBienestar`, `ServicioHabitos`, `ServicioMisiones`, `ServicioPerfil`, `ServicioProgreso` | **Medio** | ✅ Corregido | ILogger inyectado en todos los servicios. |
| IMP-008 | Media | `Ayudantes/CalculadorXP.cs` vs `Ayudantes/ConstantesGamificacion.cs` | Constantes XP duplicadas y parcialmente inconsistentes | **Bajo** | ✅ Corregido | Unificadas en `ConstantesGamificacion.cs`. `CalculadorXP.cs` referencia ese archivo. |
| IMP-009 | Media | `README.md` | Referencia a repositorio antiguo `Capstone_Epycus_Web` | **Bajo** | ✅ Corregido | README actualizado con URLs, instrucciones de deploy y CI/CD. |
| IMP-010 | Alta | `Servicios/Implementaciones/ServicioAutenticacion.cs:150-168` | Race condition en renovación de token refresh | **Medio** | ✅ Corregido | Envuelto en transacción de BD con rollback automático. |
| IMP-011 | Alta | Migraciones múltiples | Dos migraciones iniciales (`InitialMerge` e `InitialMigration`) | **Medio** | ✅ Corregido | Consolidado en una sola migración `Initial` (requiere reset de BD local). |
| IMP-012 | Media | `deploy/nginx-epycus.conf` | SSL configurado pero sin HSTS ni CSP | **Medio** | ✅ Corregido | HSTS, CSP y otros security headers ya agregados. |
| IMP-013 | Alta | `appsettings.Example.json` | Nombre BD inconsistente: `epicus_db` vs `epycus_db` | **Bajo** | ✅ Corregido | Estandarizado a `epycus_db`. |
| IMP-014 | **Muy Alta** | `Views/` (Login, auth, formularios) | **Mojibake en acentos:** `Correo ElectrÃ³nico`, `ContraseÃ±a` en lugar de `Correo Electrónico`, `Contraseña` en varios formularios | **Alto** | ✅ Ya corregido (todos los .cshtml están en UTF-8 válido) | Revisar encoding de archivos `.cshtml` — algunos estaban en Windows-1252 y se convirtieron a UTF-8. |
| IMP-015 | **Muy Alta** | `Program.cs` | **Nginx sin HTTPS.** `CookieSecurePolicy` está en `SameAsRequest` como workaround. La app no puede volver a `Always` hasta que nginx tenga SSL | **Alto** | ⚠️ Pendiente | Configurar Certbot/Let's Encrypt en nginx, luego revertir a `CookieSecurePolicy.Always` en producción. |

---

## 🟢 MEJORAS RECOMENDADAS

| ID | Prioridad | Archivo | Problema | Riesgo | Estado | Solución |
|----|-----------|---------|----------|--------|--------|----------|
| MEJ-001 | Baja | `EpycusApp.csproj` | Namespace `EPYCUS_WEB_v0._1` no coincide con nombre del proyecto | **Bajo** | ✅ Corregido | Renombrado a `EpycusApp` (100+ archivos). |
| MEJ-002 | Baja | Varios | `DateTime.Now` usado en lugar de `DateTime.UtcNow` en entidades | **Bajo** | ✅ Corregido | Normalizado a UTC en entidades y servicios. |
| MEJ-003 | Baja | `Servicios/Implementaciones/ServicioIA.cs` | `GeminiResponse` no maneja `promptFeedback` (bloqueo por safety) | **Bajo** | ✅ Corregido | Agregado DTO `PromptFeedback` + log de `blockReason` y `finishReason`. |
| MEJ-004 | Baja | Controladores | `ObtenerUsuarioId()` duplicado en varios controllers | **Bajo** | ✅ Corregido | Creados `BaseController` y `BaseApiController`. Refactorizados 17 controllers. |
| MEJ-005 | Baja | `Models/Entidades/MensajeIA.cs:17` | `FechaHora` usa `DateTime.UtcNow` (bien) pero inconsistente con otras entidades | **Bajo** | ✅ Corregido | Todas las entidades ahora usan `DateTime.UtcNow`. |
| MEJ-006 | Media | `Middleware/CargarPersonajeFilter.cs` | Silencia excepciones al cargar personaje | **Bajo** | ✅ Corregido | Logging mínimo agregado con ILogger. |
| MEJ-007 | Baja | `Ayudantes/ConstantesGamificacion.cs` | XP de misiones por prioridad no definido como constante | **Bajo** | ✅ Corregido | Agregadas `XP_MISION_ALTA`, `XP_MISION_MEDIA`, `XP_MISION_BAJA`. |
| MEJ-008 | Media | `wwwroot/img/personajes/` | Faltan imágenes para carreras diferentes a Ing. Sistemas y Medicina | **Medio** | ✅ Corregido | Seed data crea entradas para 12 carreras. Directorios creados. Solo falta colocar los PNG. |
| MEJ-009 | Baja | `appsettings.Example.json` | Versión de servidor MySQL no especificada | **Bajo** | ✅ Corregido | `MySql:ServerVersion` ya configurado como `11.8.6-mariadb`. |
| MEJ-010 | Media | `Views/` | Sin Logging de rendimiento (tiempo de carga de vistas) | **Bajo** | ✅ Corregido | Agregado `TelemetriaMiddleware` — logea requests lentos (>1s) y errores 500. |
| MEJ-011 | Alta | `Views/` (24 archivos) | UTF-8 mojibake en textos en español (`Ã¡`, `Ã©`, `Ã³`, etc.) | **Alto** | ✅ Corregido | Re-encoding de Windows-1252 a UTF-8 correcto en todas las vistas. |
| MEJ-012 | Media | `wwwroot/css/variables.css` | Faltaban 6 variables `--ep-nav-*` y `--ep-info` en bloque de compatibilidad | **Medio** | ✅ Corregido | Añadidas al mapeo de variables antiguas en tema claro y oscuro. |
| MEJ-013 | Media | `Servicios/Implementaciones/DiskHealthCheck.cs` | Ruta default `.` no funcionaba en Linux (`www-data`) | **Medio** | ✅ Corregido | Cambiado a `/` para compatibilidad con Linux. |
| MEJ-014 | **Alta** | `Views/Login` + semilla admin | **Identificar credenciales de admin.** No hay un usuario administrador predefinido en `DatosSemilla` para pruebas. | **Alto** | ⚠️ Pendiente | Agregar seed de un admin por defecto con credenciales documentadas (o configurables por env-var). |
| MEJ-015 | **Alta** | `wwwroot/css/` | **Responsividad móvil:** La app no se adapta bien a pantallas pequeñas. Tablas, sidebar y formularios se ven mal en móvil. | **Alto** | ✅ Corregido | Sidebar colapsable con botón hamburguesa, padding reducido en móvil, tablas responsivas. |
| MEJ-016 | **Alta** | `wwwroot/css/variables.css`, temas | **Modo oscuro/claro:** Letras negras se pierden en modo oscuro. Contraste insuficiente en varios componentes. | **Alto** | ✅ Corregido | Eliminadas clases hardcodeadas `bg-dark text-white` en modales y formularios. Ahora usan variables CSS. Bootstrap color utilities sobrescritas con variables del tema. |
| MEJ-017 | **Alta** | `wwwroot/css/` + `Views/` | **UI/UX general:** Diseño se siente genérico/hecho con IA. Imágenes placeholder, inicio plano, falta personalidad visual. | **Medio** | ✅ Corregido | Agregados spinners, paginación, animaciones, micro-interacciones, transiciones de página, páginas de error personalizadas, skeletons implementados en vistas clave, pressed states y hover colors corregidos. |
| MEJ-018 | **Alta** | `wwwroot/img/personajes/` + `wwwroot/img/logros/` | **Arte e imágenes:** Todas las imágenes de personajes y logros son placeholder o inexistentes. | **Alto** | ⚠️ Parcial | Logo (`logo.webp`), favicon (`favicon.ico`) e imagen de login (`login-hero.webp`) ya agregados con la marca Epycus. Personajes de Ing. Sistemas y Medicina tienen PNG reales. Faltan ilustraciones originales para el resto de carreras y logros. |
| MEJ-019 | **Media** | Varias vistas | Sin estados de carga (skeleton screens / spinners) — las páginas se ven en blanco hasta que los datos llegan | **Medio** | ✅ Corregido | Clases CSS `.ep-skeleton` implementadas en Home (chart), IA (conversación), Hábitos (lista), Progreso (personaje) y Pomodoro (stats). |
| MEJ-020 | **Media** | Controladores | Sin paginación en listados (hábitos, misiones, progreso) — podría degradarse con muchos registros | **Medio** | ✅ Corregido | Creado `PaginacionViewModel` y partial `_Paginacion.cshtml`. Pendiente conectar controllers. |
| MEJ-021 | **Baja** | `wwwroot/` | Sin soporte PWA (manifest.json, service worker, offline) | **Baja** | ⚠️ Pendiente | Convertir en PWA para instalación en móvil y soporte offline parcial |
| MEJ-022 | **Baja** | `wwwroot/favicon.ico` | Favicon es el default de ASP.NET — sin personalización de marca | **Baja** | ✅ Corregido | Reemplazado con favicon personalizado de Epycus |

---

## 🛡️ SEGURIDAD

| ID | Estado | Descripción |
|----|--------|-------------|
| SEC-001 | ✅ | `appsettings.json` en `.gitignore` — no se sube a GitHub |
| SEC-002 | ✅ | `deploy/epycus-web.service` en `.gitignore` |
| SEC-003 | ✅ | `deploy/setup-vps.sh` en `.gitignore` |
| SEC-004 | ✅ | Antiforgery tokens activos en formularios MVC |
| SEC-005 | ✅ | Cookies configuradas con HttpOnly, Secure, SameSite=Strict |
| SEC-006 | ✅ | JWT con validación de issuer, audience, lifetime y signing key |
| SEC-007 | ✅ | Security headers HTTP agregados (CSP, X-Frame-Options, HSTS, etc.) |
| SEC-008 | ✅ | Rate Limiting implementado (Api: 100/min, Gemini: 20/min, Global: 200/min) |
| SEC-009 | ✅ | CORS configurado con lista blanca de orígenes |
| SEC-010 | ✅ | Contraseñas hasheadas con BCrypt (workFactor=12) |
| SEC-011 | ✅ | Refresh tokens hash almacenados (SHA256), no en texto plano |
| SEC-012 | ✅ | Dependabot configurado (NuGet + GitHub Actions, semanal) |
| SEC-013 | ✅ | Gitleaks configurado en CI/CD — escanea secretos en cada push a main/master |
| SEC-014 | ✅ | Timeout y retry agregados a Gemini API |
| SEC-015 | ⚠️ Pendiente | Sin política de contraseñas: no hay longitud mínima, ni complejidad, ni bloqueo por intentos fallidos |
| SEC-016 | ⚠️ Pendiente | Sin CAPTCHA en Login / Registro — vulnerable a ataques de fuerza bruta y automatizados |
| SEC-017 | ⚠️ Pendiente | Sin bloqueo de cuenta después de N intentos fallidos de login |
| SEC-018 | ⚠️ Pendiente | Los endpoints de API no verifican CSRF (solo los formularios MVC tienen antiforgery) |
| SEC-019 | ⚠️ Pendiente | Refresh tokens no se rotan — usar uno nuevo e invalidar el anterior en cada renovación (previene replay attacks) |
| SEC-020 | ⚠️ Pendiente | No hay auditoría de operaciones sensibles (login fallidos, cambios de contraseña, acciones admin) |
| SEC-021 | ⚠️ Pendiente | Cambio de contraseña no invalida JWTs existentes — sesiones anteriores siguen activas |
| SEC-022 | ⚠️ Pendiente | Sin validación de tipo/tamaño de archivos subidos (nginx permite 10MB, pero la app no valida nada) |

---

## 🗄️ BASE DE DATOS

| ID | Estado | Descripción |
|----|--------|-------------|
| BD-001 | ✅ | Nombre de BD estandarizado: `epycus_db` |
| BD-002 | ✅ | Índices agregados en FK de `Log`, `MensajeIA`, `EstadoAnimo`, `TokenRefresh`, `RecuperacionContrasena`, `SesionPomodoro`, `LogroUsuario`, `VerificacionCorreo` |
| BD-003 | ✅ | Migraciones consolidadas en una sola `Initial` (requiere reset de BD local). |
| BD-004 | ✅ | `DiasSemana` normalizado a tabla `DiasSemanaHabito` (relación 1:N con `Habito`). |
| BD-005 | ✅ | Relaciones y foreign keys correctamente definidas en `OnModelCreating` |
| BD-006 | ✅ | Índice en `ConversacionId` y `UsuarioId` agregado en `MensajeIA` |
| BD-007 | ⚠️ Pendiente | Definir y automatizar backup periódico de la BD (cron + mysqldump a bucket/almacenamiento externo) |
| BD-008 | ⚠️ Pendiente | Agregar política de reintentos y pool de conexiones en `Program.cs` (`MaxRetryCount`, `EnableRetryOnFailure`) |

---

## 🔄 CI/CD

| ID | Estado | Descripción |
|----|--------|-------------|
| CI-001 | ✅ | Pipeline CI/CD creado con build, calidad, deploy y seguridad |
| CI-002 | ✅ | Backup automático antes del deploy (últimos 5 backups) |
| CI-003 | ✅ | Verificación de estado del servicio post-deploy |
| CI-004 | ❌ No planificado | Tests unitarios — el usuario decidió no implementarlos |
| CI-005 | ✅ | Warnings como errores en compilación |
| CI-006 | ⚠️ Pendiente | Agregar rollback automático: si el deploy falla (health check post-deploy), restaurar backup automáticamente |
| CI-007 | ⚠️ Pendiente | Agregar migraciones de BD al pipeline CI/CD (`dotnet ef database update` antes de iniciar la app) |
| CI-008 | ⚠️ Pendiente | Health check actual solo prueba BD, disco y Gemini — no verifica que el pipeline MVC funcione (controllers, razor, auth) |

---

## 🖥️ VPS

| ID | Estado | Descripción |
|----|--------|-------------|
| VPS-001 | ✅ | Script `setup-vps.sh` sin contraseñas hardcodeadas |
| VPS-002 | ✅ | Service `.example` template actualizado (uso de `CHANGE_ME` en lugar de valores reales) |
| VPS-003 | ✅ | Nginx config con reverse proxy, SSL, security headers |
| VPS-004 | ✅ | Dependabot configurado para NuGet y GitHub Actions |
| VPS-005 | ✅ | Health checks endpoint (`/health`) implementado con checks de BD, Gemini y disco. |
| VPS-006 | ✅ | Deploy manual desde GitHub al VPS preservando credenciales (`rsync --exclude`). |
| VPS-007 | ✅ Corregido | Script `deploy/monitoreo-uptime.sh` — chequea `/health` cada N minutos vía cron y notifica por Discord/Telegram webhook con cooldown de 5 min para evitar spam |
| VPS-008 | ✅ Corregido | Config `deploy/journald-log-rotation.conf` — límite 500MB total, 100MB por archivo, retención 7 días, compresión activada. Instalar: `cp deploy/journald-log-rotation.conf /etc/systemd/journald.conf.d/epycus.conf && systemctl restart systemd-journald` |
| VPS-009 | ✅ Corregido | `deploy/maintenance.html` + `deploy/maintenance.sh` (on/off/status). Nginx config actualizado con `error_page 502 503 @maintenance` que sirve la página desde `/var/www/maintenance/`. Funciona tanto para mantenimiento planificado (flag file) como para caídas inesperadas (502). |
| VPS-010 | ✅ Corregido | **Sincronización GitHub↔VPS:** El commit del VPS (`0dd1ffa`) subió migraciones pero no incluyó el fix de jQuery. Se sincronizó haciendo commit local del fix (`3da7ce5`) y pull en VPS. Los 3 entornos (local, GitHub, VPS) ahora están en el mismo commit. |

---

---

## 🎨 UI/UX — REDISEÑO COMPLETO

| ID | Prioridad | Área | Problema | Estado |
|----|-----------|------|----------|--------|
| UX-001 | **Crítica** | Login / Registro | Encoding roto en acentos (`Ã±`, `Ã³`, `Ã¡`) en formularios y textos | ✅ Corregido |
| UX-002 | **Crítica** | General | Sin HTTPS — la app se sirve por HTTP puro | ⚠️ Pendiente |
| UX-003 | **Alta** | Sidebar | No es responsive — sidebar fijo inservible en móvil | ✅ Corregido |
| UX-004 | **Alta** | Modo oscuro | Texto negro sobre fondo oscuro ilegible en varios componentes | ✅ Corregido |
| UX-005 | **Alta** | Modo claro | Verificar contraste en todos los componentes | ✅ Corregido |
| UX-006 | **Alta** | Imágenes | Personajes, logros e iconos son placeholder — sin arte original | ⚠️ Parcial (favicon, logo e imagen de login personalizados de Epycus. Personajes Ing. Sistemas y Medicina con PNG reales. Faltan ilustraciones del resto) |
| UX-007 | **Alta** | Home/Inicio | Dashboard genérico, sin personalidad ni micro-interacciones | ✅ Corregido |
| UX-008 | **Media** | Tipografía | Unificar jerarquía tipográfica (tamaños, pesos, colores) | ✅ Corregido |
| UX-009 | **Media** | Formularios | Feedback visual pobre en validaciones y estados | ✅ Corregido |
| UX-010 | **Media** | Transiciones | Faltan animaciones suaves en navegación y cambios de estado | ✅ Corregido |
| UX-011 | **Media** | Tablas | Datos en tablas no responsive — no se ven en móvil | ✅ Corregido |
| UX-012 | **Baja** | 404 / Error | Páginas de error genéricas sin diseño cuidado | ✅ Corregido |
| UX-013 | **Media** | General | Sin micro-interacciones (hover effects, transiciones suaves entre páginas, feedback táctil) | ✅ Corregido |
| UX-014 | **Alta** | Progreso | Modo oscuro/claro mal aplicado — colores incorrectos o ilegibles en ambas variantes (móvil y PC) | ✅ Corregido |
| UX-015 | **Alta** | Ajustes | Mala distribución de tamaños en los elementos del formulario — desproporcionados en móvil y PC | ✅ Corregido |
| UX-016 | **Alta** | Home/Inicio | Modo oscuro/claro necesita mejora — contrastes y colores no se ven bien en ninguna variante | ✅ Corregido |
| UX-017 | **Alta** | Admin `_LayoutAdmin.cshtml` | **Sin soporte de temas (claro/oscuro).** NO carga `theme-manager.js`, NO tiene inline FOUC fix, NO tiene toggle de tema. `data-theme` NUNCA se setea. Siempre carga `tema-noche-epica.css` ignorando preferencia del usuario. | ✅ Corregido |
| UX-018 | **Alta** | `notificaciones.css:110` | **Selector de tema roto:** usa `body:not([data-theme="oscuro"])` pero el theme-manager setea el atributo en `<html>` con valores `"dark"`/`"light"` (no `"oscuro"`). La condición NUNCA se cumple, el box-shadow de toasts nunca se aplica correctamente. | ✅ Corregido |
| UX-019 | **Alta** | `Bienestar/Index.cshtml:105` | **Variable CSS undefined:** usa `var(--ep-primario-rgb, 99,102,241)` pero `--ep-primario-rgb` NO está definida en `variables.css` ni en ningún tema. La carta de frase motivacional se renderiza con fallback hardcodeado, no con colores del tema activo. | ✅ Corregido |
| UX-020 | **Alta** | TODAS las vistas | **100+ instancias de Bootstrap color utilities hardcodeadas** que NO respetan el sistema de temas: `text-muted`, `text-primary`, `text-success`, `text-warning`, `text-danger`, `text-secondary`, `bg-success`, `bg-warning`, `bg-danger`, `bg-light`, `bg-secondary`, `border-secondary`, `border-success`, etc. Estos colores Bootstrap (#6c757d, #0d6efd, etc.) NO cambian con el tema claro/oscuro, ignorando completamente `variables.css`. | ✅ Corregido |
| UX-021 | **Alta** | `Habitos/Index.cshtml:140` | **`dropdown-menu-dark` forzado en TODOS los temas.** Bootstrap siempre pinta el menú oscuro aunque el usuario esté en tema claro (Sakura). Inconsistencia visual evidente. | ✅ Corregido |
| UX-022 | **Media** | `Habitos/Index.cshtml:269` | **Botón `btn-close` invisible en modo oscuro.** El inline `style="filter: none"` anula el filtro SVG que Bootstrap usa para mostrar la X blanca. En tema oscuro el botón de cerrar modal es negro sobre fondo oscuro. | ✅ Corregido |
| UX-023 | **Media** | Varias vistas (Login, Registro, Hábitos, Pomodoro, Misiones, Progreso, Admin) | **Estados de validación usan `text-danger` de Bootstrap** en lugar de `var(--error)`. El rojo de Bootstrap (#dc3545) no coincide con el del tema (--error: #ff6b9d claro / #ef4444 oscuro). | ✅ Corregido |
| UX-024 | **Media** | CSS general | **Iconos de categoría hardcodeados** en `dashboard.css:331-337` (`.dash-habit-icon.icon-*`) sin `[data-theme="dark"]` override. Colores como #3b82f6, #10b981 no tienen variante oscura. | ✅ Corregido |
| UX-025 | **Media** | `site.css:1100-1105` | **`.ep-task-tag` con colores hardcodeados** (`rgba(167, 139, 250, 0.15)`, `#A78BFA`) sin override para modo oscuro. Se ve igual en ambos temas. | ✅ Corregido |
| UX-026 | **Media** | Login / Registro | **Mezcla inconsistente de layout:** Usan `_LayoutAuth` que tiene diseño de split (hero + form), pero los `auth.css` y `_LayoutAuth.cshtml` definen estructuras DUPLICADAS (`.auth-hero` vs `.auth-hero-side`, `.auth-form-side` vs `.auth-form-wrapper`). Hay 990 líneas en `auth.css` con mucho código duplicado/no usado. | ✅ Corregido | `auth.css` reescrito de 990 a ~340 líneas. Eliminadas clases duplicadas `.auth-hero`, `.auth-form-wrapper`, partículas decorativas, toggle flotante y stats no usados. Solo se mantienen las clases que realmente usa `_LayoutAuth.cshtml`. |
| UX-027 | **Media** | Varias vistas | **Uso inconsistente de emojis:** IA/Index usa emoji "👋", "🔄" mezclado con Font Awesome/Bootstrap Icons. El resto de la app usa exclusivamente Bootstrap Icons. Sin consistencia. | ✅ Corregido |
| UX-028 | **Media** | Múltiples vistas | **Tres patrones diferentes para mensajes success/error:** (1) `TempData["Exito"]`/`["Error"]` con `ep-alerta` en Misiones y Admin, (2) `TempData["Mensaje"]`/`["Error"]` con estilo inline en Ajustes, (3) `TempData["AnimoRegistrado"]` con alert inline en Bienestar. Sin componente unificado. | ✅ Corregido | Creado `_MensajeFeedback.cshtml` partial unificado. Controllers actualizados: `AjustesController` usa `TempData["Exito"]`, `BienestarController` usa `TempData["Exito"]`. Vistas Ajustes, Bienestar y Misiones ahora usan `<partial name="_MensajeFeedback" />`. |
| UX-029 | **Media** | Múltiples vistas | **Loading skeletons definidos en CSS (`.ep-skeleton`, `.ep-skeleton-text`, `.ep-skeleton-card`) pero NUNCA usados en ninguna vista.** No hay shimmer/placeholder de carga en ninguna página. | ✅ Corregido | Implementado spinner decorativo (`.ep-spinner-overlay`) en Home, Hábitos, Progreso, Pomodoro, IA y Misiones. Los skeletons fantasma con `display:none` fueron reemplazados. |
| UX-030 | **Media** | Botones | **Pressed state inconsistente:** site.css tiene `transform: scale(0.97)` en `:active` para `.btn-ep`, `.btn-ep-outline`, `.auth-btn-primary`, pero otros botones (`.ep-btn`, `.perfil-btn-primary`, `.ep-sidebar-btn`) no tienen pressed state. | ✅ Corregido |
| UX-031 | **Media** | `site.css:918,923,1037,1045` | **Hover colors hardcodeados** sin variables CSS | ✅ Corregido |
| UX-032 | **Baja** | `Pomodoro/Index.cshtml:51` | **`border-secondary` de Bootstrap** no se adapta al tema. | ✅ Corregido |
| UX-033 | **Baja** | `Ajustes/Index.cshtml` | **`btn.btn-link` en Login** (`line 59: Acceso admin`) usa estilo Bootstrap `btn-link` que no respeta el theme. | ✅ Corregido |
| UX-034 | **Baja** | `Admin/Usuarios.cshtml:58,62` | **Badges de suscripción usan Bootstrap** `badge bg-warning text-dark` y `badge bg-light text-dark` en vez de colores del tema. | ✅ Corregido |
| UX-035 | **Media** | `Bienestar/Index.cshtml:9-13` | **Colores de estado de ánimo hardcodeados** (`#22c55e`, `#3b82f6`, `#eab308`, `#f97316`, `#ef4444`) en el Dictionary `estadoConfig`. No usan variables CSS del tema. En modo oscuro se ven igual que en claro. | ✅ Corregido |
| UX-036 | **Media** | `Bienestar/Index.cshtml:126,130-131,147,149` | **Alertas de bienestar con colores hardcodeados:** `#ef4444`, `#f97316`, `#22c55e` para criticidad/normal. Bypass total del sistema de temas. | ✅ Corregido |
| UX-037 | **Media** | `Habitos/Index.cshtml:240` | **Conic-gradient del gráfico de distribución usa colores hex hardcodeados** (`#3B82F6`, `#A78BFA`, `#F59E0B`, `#10B981`) en lugar de variables CSS. No cambia con el tema. | ✅ Corregido |
| UX-038 | **Baja** | `Pomodoro/Index.cshtml:162` | **Card de consejo usa hardcoded `#F59E0B` y `rgba(245, 158, 11, 0.1)`** en lugar de `var(--warning)` / `var(--warning-bg)`. | ✅ Corregido |
| UX-039 | **Media** | `Progreso/Index.cshtml:48,57` | **Drop-shadows hardcodeados** `rgba(245, 158, 11, 0.5)` y `rgba(59, 130, 246, 0.5)` para iconos de racha y trofeo. Deberían usar `var(--warning)`/`var(--info)` con opacidad. | ✅ Corregido |
| UX-040 | **Media** | `site.css:587-605` | **`.ep-summary-icon` variantes de color** (`.icon-purple`, `.icon-green`, `.icon-orange`, `.icon-blue`) usan colores hex hardcodeados (`#A78BFA`, `#10B981`, `#F97316`, `#3B82F6`) SIN overrides `[data-theme="dark"]`. No se adaptan al modo oscuro. | ✅ Corregido |
| UX-041 | **Alta** | `Habitos/Index.cshtml:353,375,409,423,426,465,468,475` | **8 usos de `alert()` nativo del browser para errores** en lugar del sistema de toasts de la app (`Notificaciones.mostrarError`). Experiencia inconsistente: Ajustes usa toasts, Hábitos usa alerts nativos. | ✅ Corregido | Todos los `alert()` reemplazados por `Notificaciones.mostrarError()`/`mostrarExito()`. notificaciones.js ahora se carga en ambos layouts. |
| UX-042 | **Media** | `Pomodoro/Index.cshtml:418` | **`alert()` nativo usado para notificación de bienestar.** Debería ser toast o notificación no-bloqueante. | ✅ Corregido |
| UX-043 | **Baja** | `Ajustes/Index.cshtml:61` | **Variable CSS `var(--accent-primary-bg)` no está definida** en `variables.css` ni en ningún tema. Usa fallback `rgba(139,92,246,0.15)`. El badge de código único puede no renderizarse correctamente en todos los temas. | ✅ Corregido |
| UX-044 | **Media** | Múltiples vistas (11 de 13 vistas auditadas) | **Loading skeletons NO implementados.** Solo `Home/Index.cshtml:151` tiene 1 skeleton para el chart. Las clases `.ep-skeleton`, `.ep-skeleton-text`, `.ep-skeleton-card`, `.ep-skeleton-avatar` existen en CSS pero no se usan. Tampoco se usa `.ep-spinner-overlay` ni `.ep-spinner` en ninguna vista. | ✅ Corregido | Spinner overlay (`.ep-spinner-overlay` + `.ep-spinner`) implementado en Home, Hábitos, Progreso, Pomodoro, IA y Misiones con ocultamiento vía DOMContentLoaded. |
| UX-045 | **Baja** | `_Layout.cshtml:27`, `_LayoutAdmin.cshtml:20` | **Botones de toggle sidebar usan inline styles** (`style="top: 0.75rem; left: 0.75rem; z-index: 1060;..."`) en lugar de clases CSS. Dificulta mantenimiento y personalización temática. | ✅ Corregido |
| UX-046 | **Baja** | `Autenticacion/RestablecerContrasena.cshtml:86,93` | **Colores de fortaleza de contraseña hardcodeados** (`#ef4444`, `#f97316`, `#eab308`, `#22c55e`) en JavaScript. Deberían usar variables CSS o clases predefinidas. | ✅ Corregido |
| UX-047 | **Crítica** | `wwwroot/js/notificaciones.js` / TODOS los layouts | **`notificaciones.js` NO se carga en NINGÚN layout.** El archivo existe en `wwwroot/js/notificaciones.js` con la clase `Notificaciones` (toast system), pero ningún `<script>` lo incluye. Las 12 llamadas a `Notificaciones.mostrar*` en Views (Hábitos ×8, Pomodoro ×1, Ajustes ×3) siempre caen en el fallback `alert()` nativo del navegador porque `typeof Notificaciones === 'undefined'`. Los usuarios NUNCA ven toasts. UX-041 y UX-042 reportados como corregidos pero SIGUEN ROTOS. | ✅ Corregido | `<script src="~/js/notificaciones.js">` agregado en `_Layout.cshtml` y `_LayoutAdmin.cshtml` después de `site.js`. Todos los `alert()` fallbacks removidos (8 en Hábitos, 1 en Pomodoro). |
| UX-048 | **Alta** | `Views/Shared/_LayoutAdmin.cshtml` | **FOUC (Flash of Unstyled Content) en Admin Layout.** A diferencia de `_LayoutAuth.cshtml` que tiene inline script en `<head>` para prevenir flash del tema incorrecto, `_LayoutAdmin.cshtml` NO tiene este fix. Si el usuario tiene tema claro guardado, la página cargará `tema-noche-epica.css` (oscuro) por defecto y mostrará un flash del tema oscuro antes de que `theme-manager.js` lo corrija. | ✅ Corregido | Inline FOUC fix agregado en `<head>` de `_LayoutAdmin.cshtml` (lee localStorage, setea data-theme y cambia href de hoja-tema antes del render). |
| UX-049 | **Media** | `Pomodoro/Index.cshtml`, `Habitos/Index.cshtml` | **11 instancias de `border-secondary` de Bootstrap sin override de tema.** Las clases `border-secondary`, `border-top border-secondary`, `border-bottom border-secondary`, `border-start border-2 border-secondary` usan el color Bootstrap `#6c757d` que NO cambia con el tema claro/oscuro. No hay `[data-theme] .border-secondary` en `variables.css`. | ✅ Corregido | `[data-theme] .border-secondary { border-color: var(--border-color) !important; }` ya existe en `variables.css:400`. Verificado en el archivo. |
| UX-050 | **Media** | Múltiples vistas (Habitos, IA, Home, Progreso, Pomodoro) | **Loading skeletons decorativos pero invisibles.** Las 4 vistas que declaran skeletons (Home:151, IA:99-108, Hábitos:100, Progreso:16, Pomodoro:99) los ocultan inmediatamente en `DOMContentLoaded` con `display: none`. Como todos los datos se renderizan server-side (sin async/SPA), los skeletons NUNCA son visibles. No cumplen función de carga. Son "skeletons fantasma". UX-029/UX-044 reportados como corregidos pero SIGUEN SIN FUNCIONAR. | ✅ Corregido | Opción A implementada: skeletons fantasma removidos, reemplazados por `.ep-spinner-overlay` decorativo visible durante el paint y oculto en DOMContentLoaded en Home, Hábitos, Progreso, Pomodoro, IA y Misiones. |
| UX-051 | **Baja** | `Views/Misiones/Index.cshtml:58` | **`text-dark`/`text-white` hardcodeado en badge de prioridad.** `class="badge bg-@colorPrioridad @(colorPrioridad == "warning" ? "text-dark" : "text-white")"` — sobrescrito en variables.css pero con override `!important`. Funciona pero es frágil. | ✅ Corregido | Reemplazado por `style="color: var(--text-inverse)"` en el badge. Eliminada dependencia de clases Bootstrap text-dark/text-white. |
| UX-052 | **Baja** | `Views/Pomodoro/Index.cshtml:185,449` | **`bg-warning text-dark` / `bg-success` hardcodeado en historial.** `badge bg-success` y `badge bg-warning text-dark` en historial de Pomodoro (server-side y JS). Sobrescrito en variables.css pero los colores Bootstrap se filtran. | ✅ Corregido | Reemplazadas clases Bootstrap bg-success/bg-warning por inline styles con `var(--success-bg)`, `var(--warning-bg)`, `var(--success)`, `var(--warning)` en server-side y JS. |
| UX-053 | **Baja** | `Views/Ajustes/Index.cshtml:274-302` | **6 colores hex hardcodeados en swatches de preview de temas.** Aceptable visualmente (son demostrativos), pero si se cambian las variables del tema, los swatches quedarían desactualizados. | ✅ Corregido | Todos los colores hex reemplazados por variables CSS: `#8b5cf6` → `var(--accent-primary)`, `#3b82f6` → `var(--info)`, `#10b981` → `var(--success)`, `#ec4899` → `var(--accent-primary)`, `#a855f7` → `var(--accent-secondary)`, `#06b6d4` → `var(--info)`. |
| UX-054 | **Baja** | `Views/Habitos/Index.cshtml:272` | **`btn-close` de Bootstrap funcional** gracias al fix en `variables.css:430-432` que aplica `filter` según tema. El fix existe y funciona correctamente en ambos temas. No requiere acción. | ✅ Corregido (verificado en código) |
| UX-055 | **Alta** | `variables.css:401` | **`bg-primary` de Bootstrap — ya tiene override en variables.css.** `[data-theme] .bg-primary { background-color: var(--accent-primary) !important; }` existe en línea 401. Esta entrada es stale. | ✅ Ya corregido en variable.css línea 401 |
| UX-056 | **Alta** | `variables.css:406` | **`border-info` de Bootstrap — ya tiene override en variables.css.** `[data-theme] .border-info { border-color: var(--info) !important; }` existe en línea 406. Esta entrada es stale. | ✅ Ya corregido en variable.css línea 406 |
| UX-057 | **Alta** | `variables.css:443` | **`btn-primary` de Bootstrap — ya tiene override en variables.css.** `[data-theme] .btn-primary { ... }` con hover/active/focus existe en líneas 443-459. Esta entrada es stale. | ✅ Ya corregido en variable.css línea 443 |
| UX-058 | **Media** | `Views/Ajustes/Index.cshtml:357,364` | **Hardcode de color `rgba(239,68,68,...)` en zona de peligro.** Las líneas 357 y 364 usan `rgba(239,68,68,...)` para bordes y fondo de la tarjeta "Zona de peligro". En modo claro (Sakura) el color de error es rosa `#ff6b9d`, no rojo `#ef4444`. El color hardcodeado NO coincide con el tema cuando está en modo claro. | ⚠️ Pendiente — Reemplazar con `var(--error)` con opacidad. Ej: `rgba(var(--accent-primary-rgb), 0.25)` o usar `border-color: var(--error)` con `opacity`. |
| UX-059 | **Baja** | `_LayoutAuth.cshtml:61` | **`_LayoutAuth.cshtml` SÍ carga `theme-manager.js`** en línea 61 (antes de `RenderSectionAsync("Scripts")`). Tiene su propio inline FOUC fix en `<head>` que previene el flash. La entrada stale fue eliminada. | ✅ Ya corregido — theme-manager.js se carga, FOUC fix inline funciona |
| UX-060 | **Alta** | `wwwroot/css/epycus-modern.css:355-371` | **`.ep-stat-icon` usa `linear-gradient()` con hex fijos** (`#8b5cf6, #6366f1`). No cambia con el tema. En Sakura debería usar rosa pastel. | ⚠️ Pendiente — Reemplazar con `var(--accent-primary)` y `var(--accent-secondary)` |
| UX-061 | **Alta** | `wwwroot/js/dashboard.js:72-78` | **Chart.js doughnut usa colores `rgba()` hardcodeados** (`rgba(59,130,246,0.8)`, etc.) que no se adaptan al tema activo. | ⚠️ Pendiente — Leer colores de CSS variables via `getComputedStyle()` en `initGraficosCompletados()` |
| UX-062 | **Alta** | `wwwroot/css/notificaciones.css:71-91` | **Toasts notificaciones usan `rgba()` fijos** (`rgba(16,185,129,0.95)` success, `rgba(239,68,68,0.95)` error, `rgba(59,130,246,0.95)` info). En Sakura deberían usar tonos pastel. | ⚠️ Pendiente — Usar `var(--success)`, `var(--error)`, `var(--info)` con opacidad vía `rgba(var(--success-rgb), 0.95)` |
| UX-063 | **Alta** | `wwwroot/css/ia.css:41,48,72` | **Avatar EDY usa `linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%)` fijo** — no se adapta a tema Sakura (debería ser rosa). | ⚠️ Pendiente — Usar `var(--accent-primary)` y `var(--accent-secondary)` en el gradiente |
| UX-064 | **Media** | `wwwroot/css/ia.css:53-54,233-235` | **EDY box-shadows con `rgba(139,92,246,0.4)` y `rgba(139,92,246,0.7)` fijos** — sombra púrpura incluso en Sakura donde debería ser rosa. | ⚠️ Pendiente — Usar `var(--accent-primary)` con opacidad |
| UX-065 | **Media** | `wwwroot/css/site.css:1163` | **`.ep-page-loading::before` shimmer usa `rgba(255,255,255,0.06)`** — casi invisible en tema claro Sakura (fondo blanco). | ⚠️ Pendiente — Usar variable con opacidad o color alternativo para tema claro |
| UX-066 | **Media** | `wwwroot/css/perfil.css:18,22,43,102` | **Fondos con `rgba()` hardcodeados**: `rgba(34,197,94,0.25)`, `rgba(239,68,68,0.25)`, `rgba(139,92,246,0.3)` no se adaptan al tema. | ⚠️ Pendiente — Reemplazar con `var(--success)`, `var(--error)`, `var(--accent-primary)` con opacidad |
| UX-067 | **Alta** | `wwwroot/js/dashboard.js:20,42` | **Usa `alert()` nativo en catch de errores AJAX** en lugar de `Notificaciones.mostrarError()`. Muestra diálogo nativo feo en lugar de toast elegante. | ⚠️ Pendiente — Reemplazar `alert(...)` con `Notificaciones.mostrarError(mensaje)` |
| UX-068 | **Alta** | `wwwroot/css/variables.css` | **Faltan overrides de Bootstrap para: `.alert-*`, `.table-*`, `.list-group-*`, `.btn-*` (todos menos primary), `.btn-outline-*`, `.form-control`, `.form-select`** — estos componentes mantienen colores Bootstrap por defecto si se usan en vistas. | ⚠️ Pendiente — Agregar `[data-theme]` overrides para cada clase listada |
| UX-069 | **Alta** | Multiple vistas (sidebar) | **Faltan `aria-label` en: `#ep-sidebar-toggle`, `#ep-sidebar-toggle-admin`, `#switch-tema`, `#btn-toggle-tema`, `.edy-btn-nueva`, `.ep-toast-close`** — navegación por teclado y screen readers no pueden identificar estos controles. | ⚠️ Pendiente — Agregar `aria-label` descriptivos a cada elemento |
| UX-070 | **Alta** | `wwwroot/css/auth.css:78` | **`.auth-form-side` con `min-width: 480px` fijo** — causa overflow horizontal en pantallas <480px. El auth layout no es completamente responsivo. | ⚠️ Pendiente — Cambiar a `min-width: min(480px, 100vw)` |
| UX-071 | **Media** | `wwwroot/css/variables.css` | **Tema Sakura: `--text-tertiary: #b094ad` sobre `--surface: #ffffff`** = ratio de contraste 3.3:1 ❌ Falla WCAG AA para texto normal (necesita ≥4.5:1). | ⚠️ Pendiente — Oscurecer a ~`#9a7a99` (ratio ~4.7:1) o restringir a large text |
| UX-072 | **Media** | `wwwroot/css/variables.css` | **Tema Sakura: `--accent-primary: #ff6b9d` sobre fondo blanco** = ratio 3.5:1 ❌ Falla WCAG AA. Texto sobre botones accent ilegible en modo claro. | ⚠️ Pendiente — Usar `--text-on-accent: #fff` en botones/accent. Para enlaces, oscurecer accent a ~`#e55d8a` |
| UX-073 | **Media** | `wwwroot/css/variables.css` | **Tema oscuro: `--surface-border: #2d1b4e` sobre `--surface: #0f1419`** = ratio 2.5:1 ❌ Bordes de cards casi invisibles en Solo Leveling. | ⚠️ Pendiente — Aclarar a ~`#4a2d6e` o añadir outline sutil |
| UX-074 | **Baja** | `Views/Autenticacion/RestablecerContrasena.cshtml:36` | **Fortaleza de contraseña aplicada inline por JS** — el JS escribe `style="background: ...; width: ...%"` directamente en el div. Si se desactiva JS, no hay feedback visual. | ⚠️ Pendiente — Considerar clases CSS predefinidas para cada nivel de fortaleza |
| UX-075 | **Baja** | `Views/Shared/_LayoutAuth.cshtml:10` | **_LayoutAuth.cshtml y _LayoutAdmin.cshtml SÍ cargan `epycus-modern.css`** (línea 10 en ambos). Entrada stale. Todas las vistas tienen acceso a las mismas variables y fuentes. | ✅ Ya existe en todos los layouts |

---

## 📋 DEUDA TÉCNICA

| ID | Descripción | Esfuerzo | Estado |
|----|-------------|----------|--------|
| DEV-001 | Renombrar namespace `EPYCUS_WEB_v0._1` → `EpycusApp` | 2-3 días | ✅ |
| DEV-002 | Unificar constantes XP en un solo archivo | 1 hora | ✅ |
| DEV-003 | Agregar proyecto de tests unitarios | 2-3 días | ❌ No planificado |
| DEV-004 | Agregar logging con ILogger en servicios | 1 día | ✅ |
| DEV-005 | Implementar health checks endpoint | 4 horas | ✅ |
| DEV-006 | Dockerizar la aplicación | 1 día | ❌ No necesario (deploy directo a VPS) |
| DEV-007 | Agregar OpenAPI/Swagger para API endpoints | 4 horas | ✅ |
| DEV-008 | Migrar a `DateOnly` consistente en toda la BD | 1 día | ✅ (parcial: `FechaNacimiento` + entidades existentes) |
| DEV-009 | Agregar sistema de monitoreo/error tracking (Sentry, OpenTelemetry, etc.) | 1 día | ⚠️ Pendiente |
| DEV-010 | Implementar caché (Redis o MemoryCache) para datos frecuentes (carreras, niveles, frases) | 1 día | ⚠️ Pendiente |
| DEV-011 | Agregar tests de integración para los flujos críticos (registro, login, hábitos, pomodoro) | 3-4 días | ⚠️ Pendiente |
| DEV-012 | Agregar meta tags SEO, sitemap.xml y robots.txt | 4 horas | ⚠️ Pendiente |
| DEV-013 | Agregar banner de consentimiento de cookies (GDPR) | 4 horas | ⚠️ Pendiente |
| DEV-014 | Versionar la API (ej: `/api/v1/`, `/api/v2/`) para no romper clientes existentes | 1 día | ⚠️ Pendiente |
| DEV-015 | Agregar `apple-touch-icon` y `manifest.json` personalizados de la marca Epycus (`favicon.ico` ya colocado) | 2 horas | ⚠️ Pendiente |
| DEV-016 | Revisar y actualizar dependencias NuGet a versiones recientes (seguridad y compatibilidad) | 2 horas | ⚠️ Pendiente |
| DEV-017 | Agregar `Program.cs` graceful shutdown: finalizar requests en curso al recibir SIGINT antes de apagarse | 2 horas | ⚠️ Pendiente |

---

## 📱 MÓVIL — API + APP PLAY STORE

| ID | Prioridad | Área | Problema | Estado |
|----|-----------|------|----------|--------|
| MOB-001 | **Crítica** | API existente | Los endpoints `/api/*` ya existen pero no están documentados para consumo móvil. Definir contrato API (request/response) para todos los endpoints | ⚠️ Pendiente |
| MOB-002 | **Crítica** | API Auth | El login móvil necesita devolver JWT en el body (no en cookie). El endpoint actual usa cookies — agregar endpoint que devuelva `{ "token": "...", "refreshToken": "..." }` | ⚠️ Pendiente |
| MOB-003 | **Alta** | API | Agregar CORS para orígenes móviles o remover dependencia de cookies (usar `Authorization: Bearer` header) | ⚠️ Pendiente |
| MOB-004 | **Alta** | API | Agregar rate limiting diferenciado para móvil vs web (los móviles hacen más requests en ráfagas) | ⚠️ Pendiente |
| MOB-005 | **Alta** | API Docs | Generar y publicar documentación OpenAPI/Swagger completa para el equipo móvil | ⚠️ Pendiente |
| MOB-006 | **Alta** | App | Definir tecnología: **Flutter** (recomendado) vs React Native vs Kotlin/Swift nativo | ⚠️ Pendiente |
| MOB-007 | **Alta** | App | Crear proyecto base con autenticación (login/registro JWT), navegación y manejo de sesión | ⚠️ Pendiente |
| MOB-008 | **Alta** | App | Implementar módulos: Hábitos, Pomodoro, Misiones, Progreso, Dashboard — consumiendo API existente | ⚠️ Pendiente |
| MOB-009 | **Media** | App | Sincronización offline: caché local de datos y cola de comandos pendientes para cuando no hay conexión | ⚠️ Pendiente |
| MOB-010 | **Media** | App | Push notifications con Firebase Cloud Messaging (FCM) para recordatorios de hábitos y Pomodoro | ⚠️ Pendiente |
| MOB-011 | **Media** | App | Diseño UI/UX móvil nativo (Material Design 3 o Human Interface Guidelines) — no una copia de la web | ⚠️ Pendiente |
| MOB-012 | **Media** | Play Store | Crear cuenta de desarrollador en Google Play ($25 única vez) | ⚠️ Pendiente |
| MOB-013 | **Baja** | Play Store | Preparar assets: ícono, screenshots, descripción, política de privacidad para la ficha de Play Store | ⚠️ Pendiente |
| MOB-014 | **Baja** | Play Store | Configurar CI/CD para build y publish automático a Play Store (GitHub Actions + Fastlane) | ⚠️ Pendiente |
| MOB-015 | **Baja** | Play Store | Publicar versión beta cerrada (Closed Testing) con usuarios de prueba antes del release público | ⚠️ Pendiente |

---

## 🌱 ODS 3 — BIENESTAR

> Auditoría: 2026-06-18 | Última corrección: 2026-06-18 | Cobertura: Estado de ánimo, alertas, frases motivacionales, UI, API, BD, IA, Pomodoro

### ✅ Implementado

| ID | Funcionalidad | Archivos |
|----|--------------|----------|
| B-OK-01 | Registro de estado de ánimo (5 estados + nota opcional) vía MVC y API | `BienestarController.cs:35`, `ApiEstadoAnimoController.cs:21`, `ServicioBienestar.cs:205` |
| B-OK-02 | Historial de ánimo (14 días con grid de puntos + lista) | `BienestarController.cs:27`, `Index.cshtml:156-226` |
| B-OK-03 | Frases motivacionales aleatorias (10 semilladas, admin CRUD) | `ServicioBienestar.cs:169`, `DatosSemilla.cs:168`, `Admin/Frases.cshtml` |
| B-OK-04 | Frase motivacional en Dashboard home | `Views/Home/Index.cshtml:31-39` |
| B-OK-05 | Alertas de bienestar (4 tipos: Pomodoro excesivo, ánimo negativo 3d, sueño olvidado, sobrecarga misiones) | `ServicioBienestar.cs:19-48`, `AlertaBienestar.cs` |
| B-OK-06 | Alerta ODS 3 en Pomodoro (sugiere descanso largo tras ciclos) | `Views/Pomodoro/Index.cshtml:416-421` |
| B-OK-07 | Recomendación de pausa activa (integrada en ServicioPomodoro) | `ServicioBienestar.cs:158`, `ServicioPomodoro.cs:64` |
| B-OK-08 | Contexto de ánimo enviado a IA (Gemini) para respuestas personalizadas | `ServicioIA.cs:238,289,357` |
| B-OK-09 | REST API para registro y consulta de estado de ánimo | `ApiEstadoAnimoController.cs` |
| B-OK-10 | DbSet + índice en BD para `EstadoAnimo` y `FraseMotivacional` | `ContextoAplicacion.cs:29-30,183-184` |
| B-OK-11 | Responsividad básica (clamp, media queries para círculos y botones) | `Index.cshtml:184-200,504-506` |
| B-OK-12 | Branding ODS 3 explícito (badge + link UN) | `Views/Bienestar/Index.cshtml` |
| B-OK-13 | Summary cards con hábitos/misiones pendientes y stats | `BienestarViewModel.cs`, `BienestarController.cs`, `Index.cshtml` |
| B-OK-14 | Recomendación de pausa activa visible en UI | `ServicioBienestar.cs`, `BienestarViewModel.cs`, `Index.cshtml` |
| B-OK-15 | Recursos de crisis/salud mental (línea 113, ODS 3, consejos) | `Views/Bienestar/Index.cshtml` |
| B-OK-16 | Mini componente respiración guiada (técnica 4-7-8) | `Views/Bienestar/Index.cshtml` |
| B-OK-17 | CSS dedicado `bienestar.css` — estilos movidos fuera de la vista | `wwwroot/css/bienestar.css` |
| B-OK-18 | 25 frases motivacionales (15 nuevas) con categorías | `DatosSemilla.cs:176-202` |
| B-OK-19 | `Categoria` en `FraseMotivacional` para filtrar por tema | `Models/Entidades/FraseMotivacional.cs` |
| B-OK-20 | 5 logros ODS-3 semillados (Ánimo Estable, Autoconsciente, Alerta Superada, Bienestar Constante, Maestro del Equilibrio) | `DatosSemilla.cs` |
| B-OK-21 | DTO `RecomendacionPausaDto` con Tipo, DuracionSegundos, Descripcion, Icono | `DTOs/RecomendacionPausaDto.cs` |
| B-OK-22 | Validación server-side en API estado ánimo (Required, StringLength, lista blanca) | `ApiEstadoAnimoController.cs` |
| B-OK-23 | Mojibake corregido en `ServicioBienestar.cs` (re-encoding UTF-8) | `ServicioBienestar.cs` |
| B-OK-24 | TempData unificado a clave `"Exito"` | `BienestarController.cs:43` |

### ✅ CORREGIDO — Funcionalidades implementadas

| ID | Prioridad | Estado | Solución aplicada |
|----|-----------|--------|-------------------|
| B-FALTA-01 | **Alta** | ✅ | Badge ODS 3 en header con link a UN SDG. `Views/Bienestar/Index.cshtml` |
| B-FALTA-02 | **Alta** | ⚠️ Pendiente | SignalR/WebSocket no implementado. Las alertas se muestran al cargar la página. |
| B-FALTA-03 | **Alta** | ⚠️ Parcial | `RecomendacionPausaActiva` ahora usa DTO estructurado, visible en UI. No considera historial de ánimo ni IA. |
| B-FALTA-04 | **Media** | ✅ | 5 logros ODS-3 semillados (`DatosSemilla.cs`): Ánimo Estable, Autoconsciente, Alerta Superada, Bienestar Constante, Maestro del Equilibrio |
| B-FALTA-05 | **Media** | ⚠️ Pendiente | Integración Pomodoro-bienestar no implementada. |
| B-FALTA-06 | **Baja** | ⚠️ Pendiente | Analytics/gráficos Chart.js no implementados. |
| B-FALTA-07 | **Baja** | ✅ | Mini componente respiración guiada (modal con técnica 4-7-8 + animación círculo). `Index.cshtml` |
| B-FALTA-08 | **Alta** | ✅ | Sección "Recursos de apoyo" con línea 113, link ODS 3, técnica respiración, consejo del día |
| B-FALTA-09 | **Baja** | ✅ | Logros ODS-3 semillados (equivalente funcional a metas/desafíos) |
| B-FALTA-10 | **Media** | ✅ | Summary cards con hábitos pendientes, misiones pendientes, estado hoy y alertas |
| B-FALTA-11 | **Media** | ✅ | Recomendación activa visible en UI sobre el contenido principal |

### ✅ CORREGIDO — Errores

| ID | Prioridad | Estado | Solución aplicada |
|----|-----------|--------|-------------------|
| B-ERR-01 | **Alta** | ✅ | Archivo reescrito en UTF-8 sin BOM. Mojibake corregido en todas las cadenas. |
| B-ERR-02 | **Alta** | ✅ | Corregido (UX-019). |
| B-ERR-03 | **Media** | ✅ | Corregido (UX-035). |
| B-ERR-04 | **Media** | ✅ | Corregido (UX-036). |
| B-ERR-05 | **Media** | ✅ | No requiere cambio. |
| B-ERR-06 | **Media** | ✅ | `[Required]`, `[StringLength]` y validación contra lista blanca de estados permitidos agregados. |
| B-ERR-07 | **Baja** | ✅ | Estilos movidos a `wwwroot/css/bienestar.css`. Vista ahora referencia el archivo via `<link>`. |

### ✅ CORREGIDO — Mejoras implementadas

| ID | Prioridad | Estado | Solución aplicada |
|----|-----------|--------|-------------------|
| B-INC-01 | **Media** | ✅ | `RecomendacionPausaActiva` ahora devuelve `RecomendacionPausaDto` (Tipo, DuracionSegundos, Descripcion, Icono). Visible en UI. |
| B-INC-02 | **Media** | ✅ | ViewModel actualizado con `HabitosPendientes`, `MisionesPendientes`, `RecomendacionActiva`. Controller los popula. |
| B-INC-03 | **Baja** | ✅ | `TempData` ya usaba `"Exito"` en el controller (verificado). |
| B-INC-04 | **Baja** | ✅ | 15 frases nuevas agregadas (total: 25). Incluyen categorías y enfoque en bienestar universitario. |
| B-INC-05 | **Baja** | ✅ | Propiedad `Categoria` (string) agregada a `FraseMotivacional`. |
| B-INC-06 | **Media** | ⚠️ Pendiente | Solo 5 estados de ánimo se mantienen. Requiere análisis de impacto antes de expandir. |

### 🌙 UI — Responsividad y modo oscuro/claro

| ID | Prioridad | Estado | Solución aplicada |
|----|-----------|--------|-------------------|
| B-UI-01 | **Alta** | ✅ | Corregido (UX-019). |
| B-UI-02 | **Media** | ✅ | `.ep-animo-btn` movido a `bienestar.css`. Usa variables CSS del tema (`--ep-borde`, `--ep-superficie-2`, etc.). |
| B-UI-03 | **Media** | ✅ | Círculos de historial ahora usan `EstadoColor()` y `EstadoBg()` con `var(--ep-animo-*)`. Colores hardcodeados reemplazados. |
| B-UI-04 | **Baja** | ✅ | `EstadoColor()` y `EstadoBg()` ahora devuelven variables CSS (`var(--ep-animo-*)`) en lugar de colores hardcodeados. |
| B-UI-05 | **Baja** | ✅ | `wwwroot/css/bienestar.css` creado. Todos los estilos inline de la vista migrados al archivo dedicado. |

---

## 🤖 MÓDULO IA — EDY (Gemini)

> Auditoría: 2026-06-18 | Archivos: `ServicioIA.cs`, `IaController.cs`, `Views/Ia/Index.cshtml`, `IaChatViewModel.cs`, `MensajeIA.cs`, `IServicioIA.cs`, `GeminiHealthCheck.cs`, `ia.css`, `appsettings.json`, `Program.cs`

### 🔴 CRÍTICOS — Errores funcionales / seguridad

| ID | Prioridad | Archivo | Problema | Solución propuesta |
|----|-----------|---------|----------|--------------------|
| IA-CRIT-01 | **Muy Alta** | `Program.cs:132-137`, `IaController.cs` | **Rate limiter "Gemini" (20/min) definido pero NUNCA aplicado.** Sin `[EnableRateLimiting("Gemini")]` en el controller/action. Solo el global (200/min) protege. Un atacante autenticado puede hacer 200 llamadas/min a Gemini = costos reales de API. | Agregar `[EnableRateLimiting("Gemini")]` en `IaController` a nivel de clase o en el action `Chat()`. |
| IA-CRIT-02 | **Muy Alta** | `Controllers/IaController.cs:50` | **Endpoint `/api/ia/chat` sin `[ValidateAntiForgeryToken]`.** JS fetch no envía token CSRF. Cualquier sitio externo podría enviar requests autenticados (si el usuario tiene sesión activa). | Agregar `[ValidateAntiForgeryToken]` al action `Chat()`. Enviar token CSRF desde JS (header `X-CSRF-TOKEN` ya configurado en `Program.cs:153`). |
| IA-CRIT-03 | **Alta** | `Servicios/Implementaciones/ServicioIA.cs` | **Todo el archivo tiene mojibake (Windows-1252 en lugar de UTF-8).** Mensajes de error en español rotos: `no estÃ¡` → `no está`, `IntÃ©ntalo` → `Inténtalo`, `tardÃ³` → `tardó`, `Ãºltimos` → `últimos`, etc. El usuario ve texto con caracteres extraños. | Re-encoding del archivo a UTF-8 sin BOM. Corregir todas las cadenas con acentos/ñ. |
| IA-CRIT-04 | **Alta** | `Views/Ia/Index.cshtml` | **Sin límite de longitud server-side.** El textarea tiene `maxlength="2000"` (client-side) pero el backend no valida tamaño del mensaje. Un atacante podría enviar payloads enormes directamente a `/api/ia/chat`. | Agregar validación server-side: `if (dto.Mensaje.Length > 2000) return BadRequest(...)`. Validar también en `ServicioIA.ChatAsync()`. |

### 🟡 IMPORTANTES — UX / funcionalidad

| ID | Prioridad | Archivo | Problema | Solución propuesta |
|----|-----------|---------|----------|--------------------|
| IA-IMP-01 | **Alta** | `wwwroot/css/ia.css:38-49,161-172,232-243` | **Avatares de EDY con gradiente hardcodeado a púrpura oscuro.** `.edy-avatar-lg`, `.edy-avatar-sm`, `.edy-welcome-avatar` usan `linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%)` — NO se adaptan al tema claro (Sakura/pastel). En modo claro se ven púrpura en lugar de rosa. | Usar `var(--accent-primary)` y `var(--accent-secondary)` en lugar de valores fijos. Ej: `linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))`. |
| IA-IMP-02 | **Alta** | `Views/Ia/Index.cshtml:56-59` | **Sugerencias de bienvenida estáticas y hardcodeadas.** 4 botones fijos: "¿Cómo van mis hábitos?", "Tengo misiones urgentes", "Me siento desmotivado", "¿Cómo mejoro mi racha?". No se personalizan según datos reales del usuario. | Reemplazar con sugerencias generadas desde el servidor basadas en contexto del usuario (ej: si tiene misiones urgentes, priorizar esa; si racha baja, sugerir mejorar racha). Pasar como modelo en `IaChatViewModel`. |
| IA-IMP-03 | **Alta** | `IaController.cs`, `Views/Ia/Index.cshtml` | **No hay lista/selector de conversaciones pasadas.** El usuario solo ve la conversación activa. No puede navegar entre sesiones anteriores. Las conversaciones quedan "perdidas" en la DB sin forma de acceder. | Agregar endpoint `GET /ia/conversaciones` que liste conversaciones del usuario. Mostrar sidebar o dropdown con historial de conversaciones. Agregar propiedad `FechaUltimoMensaje` para ordenarlas. |
| IA-IMP-04 | **Alta** | `ServicioIA.cs:297-343` | **System prompt carece de "banderas de bienestar" explícitas.** Pasa datos crudos de ánimo pero obliga a la IA a inferir problemas de salud mental. Si el usuario tiene 3+ días de ánimo negativo, sobrecarga de misiones o racha rota, la IA no recibe esta señal procesada. | Agregar sección en el system prompt con "⚠️ Señales de Bienestar:" que incluya alertas detectadas (ánimo negativo recurrente, sobrecarga de tareas, falta de hábitos). Usar los mismos datos de `ServicioBienestar.ObtenerAlertasAsync()`. |
| IA-IMP-05 | **Media** | `ServicioIA.cs:72-82` | **Límite de 20 mensajes de historial.** When se excede, los mensajes más antiguos se descartan sin resumen. La IA pierde contexto de conversaciones largas. | Implementar resumen automático: cuando se alcanzan 15 mensajes, generar un resumen del contexto anterior y enviarlo como mensaje de sistema adicional en lugar de descartar. |
| IA-IMP-06 | **Media** | `Servicios/Implementaciones/GeminiHealthCheck.cs` | **`GeminiHealthCheck` crea su propio `HttpClient` genérico** en lugar de usar el cliente nombrado `"Gemini"`. Hardcodea el modelo `"gemini-2.5-flash-lite"` en lugar de leer `Gemini:Modelo` de configuración. | **⚠️ Parcial:** El modelo ahora se lee de configuración. Pendiente: usar `_httpClient = httpClientFactory.CreateClient("Gemini")`. |
| IA-IMP-07 | **Media** | `ServicioIA.cs:46` | **`ChatAsync` guarda en DB antes de llamar a Gemini.** Si Gemini falla después de reintentos, el mensaje del usuario queda persistido pero la respuesta no, dejando conversaciones con mensajes huérfanos del usuario. | Envolver en transacción: si Gemini falla, eliminar el mensaje del usuario de la DB (rollback). O guardar ambos mensajes juntos al final. |
| IA-IMP-08 | **Media** | `IaController.cs:44-47` | **Action `Nueva()` usa POST** pero no hay formulario que envíe datos — solo un botón. Podría ser un `GET` simple (redirige sin side-effects). | Cambiar a `[HttpGet]` o mantener POST pero sin necesidad de antiforgery. O mejor: que el JS genere un nuevo `convid` en cliente y redirija. |
| IA-IMP-09 | **Baja** | `Views/Ia/Index.cshtml:27-28` | **"En línea" siempre verde.** El status dot siempre muestra "En línea" aunque el health check de Gemini falle. No hay verificación real de disponibilidad. | Agregar JS que llame a `/health` periodicamente (o al endpoint de Gemini health check) para mostrar estado real. |
| IA-IMP-10 | **Baja** | `Models/Entidades/MensajeIA.cs:17` | **Sin fecha de último acceso a la conversación.** `FechaHora` existe por mensaje pero no hay metadatos de la conversación como `Titulo`, `UltimoAcceso`, `Resumen`. | Agregar entidad `ConversacionIA` con `Id` (el GUID), `UsuarioId`, `Titulo` (generado por IA del primer mensaje), `UltimoAcceso`, `CantidadMensajes`. |
| IA-IMP-11 | **Alta** | `Servicios/Implementaciones/ServicioIA.cs:137-138` | **API Key expuesta en la URL de la request** (`?key={_apiKey}`). Queda en logs del servidor, proxies, y potencialmente en telemetría. | **✅ Corregido:** Ahora usa header `x-goog-api-key` en lugar de query parameter. |

### 🟢 MEJORAS RECOMENDADAS

| ID | Prioridad | Archivo | Problema | Solución propuesta |
|----|-----------|---------|----------|--------------------|
| IA-MEJ-01 | **Media** | `ServicioIA.cs` | **Sin gamificación del chat.** El usuario puede hablar con EDY todo lo que quiera pero no gana XP, no hay logros por interactuar, ni rachas por usar el chat. | Agregar XP por mensaje (ej: 1 XP por mensaje, 5 XP si fue útil según feedback del usuario). Usar `IServicioGamificacion` existente. |
| IA-MEJ-02 | **Media** | `IaController.cs`, `ServicioIA.cs` | **Sin análisis de sentimiento.** Las respuestas de EDY no se ajustan al tono emocional del mensaje del usuario (enojo, tristeza, urgencia). | Integrar análisis de sentimiento básico (o pedirle a Gemini que clasifique el tono) y ajustar respuesta. También detectar crisis (menciones de suicidio, depresión) para mostrar recursos de ayuda (línea 113). |
| IA-MEJ-03 | **Media** | `Views/Ia/Index.cshtml` | **Sin integración con bienestar.** EDY no puede crear alertas de bienestar, registrar estado de ánimo, ni sugerir acciones que se persistan en el sistema. | Agregar acciones post-chat: "¿Quieres registrar tu estado de ánimo?", "He notado que estás estresado — ¿quieres hacer una pausa activa?". Usar APIs de `ServicioBienestar`. |
| IA-MEJ-04 | **Baja** | `Views/Ia/Index.cshtml` | **Sin botón de "Me gusta" / feedback en respuestas.** No hay forma de que el usuario califique si la respuesta de EDY fue útil. | Agregar botones 👍/👎 en cada respuesta de EDY. Persistir feedback en DB para mejorar el prompt engineering. |
| IA-MEJ-05 | **Baja** | `ServicioIA.cs:103-121` | **Sin soporte para streaming de respuesta.** El usuario espera hasta que Gemini termine completamente antes de ver la respuesta. Para respuestas largas la espera es notable. | Implementar SSE (Server-Sent Events) o WebSocket para streaming de tokens de Gemini al cliente. |
| IA-MEJ-06 | **Baja** | `Views/Ia/Index.cshtml` | **Sin búsqueda en conversaciones.** El usuario no puede buscar dentro de una conversación o en todo su historial. | Agregar campo de búsqueda con endpoint `GET /api/ia/buscar?q=...` que haga `LIKE` en `Contenido` de `MensajeIA`. |
| IA-MEJ-07 | **Baja** | `Views/Ia/Index.cshtml`, `ia.css` | **Sin markdown completo.** Solo soporta `**bold**` y `*cursiva*`. No soporta listas, código, links, tablas que Gemini puede devolver. | Agregar sanitización de más elementos markdown: listas (`-`, `1.`), código `` ` `` y ``` ``` ```, links. Usar librería client-side como `marked` o implementar regex adicionales. |
| IA-MEJ-08 | **Baja** | `ServicioIA.cs:38-44` | **Sin paginación en `ObtenerHistorialAsync`.** Si una conversación tiene cientos de mensajes, la carga será lenta. | Agregar `Take(50)` con offset y paginación desde el controller. |
| IA-MEJ-09 | **Baja** | `appsettings.json` | **No hay `Gemini:MaxTokensPorDia` ni límite de costos.** Sin protección contra uso excesivo que genere costos altos de API. | Agregar límite diario de tokens/mensajes por usuario, configurable en `appsettings.json`. Persistir contador de uso diario en DB. |

---

## Leyenda

- ✅ Corregido / Implementado
- ⚠️ Pendiente / Requiere acción
- ❌ No resuelto

> **Nota**: Los ítems marcados como ✅ ya fueron corregidos durante esta auditoría.

## 📄 Cómo usar la paginación (MEJ-020)

Se crearon:
- `ViewModels/PaginacionViewModel.cs` — modelo reutilizable con página actual, total, etc.
- `Views/Shared/_Paginacion.cshtml` — partial con navegación responsiva

Para usar en un controlador:
```csharp
ViewBag.Paginacion = new PaginacionViewModel {
    PaginaActual = pagina,
    TotalPaginas = (int)Math.Ceiling((double)totalItems / itemsPorPagina),
    TotalItems = totalItems,
    ItemsPorPagina = itemsPorPagina,
    Accion = "Index",
    Controlador = "Habitos"
};
```

En la vista:
```html
<partial name="_Paginacion" model='ViewBag.Paginacion' />
```

> **Pendiente:** Los controllers existentes aún no tienen lógica `Skip`/`Take` — hay que agregar `pagina` como parámetro en cada action y aplicar `.Skip((pagina-1)*tamano).Take(tamano)` en las queries.

---

## 📱 RESPONSIVIDAD — Auditoría de issues (corregido 2026-06-18)

> Generado: 2026-06-17 | Corregido: 2026-06-18

Todos los issues de responsividad identificados en la auditoría han sido corregidos:

| Prioridad | Archivo | Solución aplicada | Estado |
|-----------|---------|-------------------|--------|
| 🔴 1 | `Habitos/Index.cshtml` | `.ep-habit-card` con `flex-wrap`, day-checkboxes ocultos en <576px, nombre con `text-truncate` | ✅ |
| 🟡 2 | `Home/Index.cshtml` | `d-none d-sm-block` → `d-none d-md-block` en racha/streak | ✅ |
| 🟡 3 | `Habitos/Index.cshtml` | Encabezado días: `width: 24px` fijo → `min-width: 24px; flex: 0 0 auto` | ✅ |
| 🟢 4 | `Progreso/Index.cshtml` | `min-width: 64px` → `width: clamp(48px, 12vw, 64px)` | ✅ |
| 🟢 5 | `Pomodoro/Index.cshtml` | Timer ring: `280px` fijo → `min(280px, 80vw)` | ✅ |
| 🟢 6 | `Ia/Index.cshtml` | `.edy-status-text` oculto en <576px | ✅ |
| 🟢 7 | `Shared/_Paginacion.cshtml` | `flex-wrap` + botones más pequeños en móvil | ✅ |
| 🟢 8 | `Admin/Usuarios.cshtml` | Botones apilados verticalmente en móvil (`.ep-admin-actions`) | ✅ |
| 🟢 9 | `Home/Index.cshtml` | Chart `height: 300px` → `clamp(200px, 40vw, 300px)` | ✅ |
| 🟢 10 | `Shared/_LayoutAdmin.cshtml` | `overflow-y: auto` en sidebar admin móvil | ✅ |
| 🟢 11 | `Ajustes/Index.cshtml` | Texto zona peligro con `flex-fill` + `min-w-0` | ✅ |
| 🟢 12 | `Bienestar/Index.cshtml` | Círculos historial con `clamp()`, animo-btn responsive | ✅ |
| 🟢 13 | `Pomodoro/Index.cshtml` | Timeline icon `left: -1.35rem` → `left: 0` + `ps-4` | ✅ |
| 🔵 14 | `site.css` | Override columnas suavizado (col-md-6 y col-lg-5/6 ya no forzados a 100%) | ✅ |
| 🔵 15 | `Bienestar/Index.cshtml` | `.ep-animo-btn` min-width reducido en móvil | ✅ |

---

## 📱 RESPONSIVIDAD MÓVIL — Nueva auditoría (2026-06-18)

Issues pendientes identificados en auditoría posterior:

### 🔴 Críticos — ✅ Resueltos

| ID | Archivo | Solución aplicada | Estado |
|----|---------|-------------------|--------|
| RMOB-01 | `Pomodoro/Index.cshtml:57-58` | Botones `+`/`-` cambiados a 44×44px con centrado flex | ✅ |
| RMOB-02 | `Habitos/Index.cshtml:240` | Pie chart cambiado a `min(100px, 35vw)` | ✅ |

### 🟡 Altos — ✅ Resueltos

| ID | Archivo | Solución aplicada | Estado |
|----|---------|-------------------|--------|
| RMOB-03 | `site.css` + vistas | Override de `font-size: 0.5-0.7rem` a ≥0.7-0.85rem en <576px | ✅ |
| RMOB-04 | `Habitos/Index.cshtml:295-321` | `col-6` → `col-12 col-sm-6` en modal | ✅ |
| RMOB-05 | `site.css` | `.btn-sm` con `min-height:44px` y padding mejorado en móvil | ✅ |
| RMOB-06 | `site.css` + `_Paginacion.cshtml` | Paginación con `min-width:44px; min-height:44px` | ✅ |

### 🟢 Medios — ✅ Resueltos

| ID | Archivo | Solución aplicada | Estado |
|----|---------|-------------------|--------|
| RMOB-07 | `Progreso/Index.cshtml:17` | Avatar cambiado a `min(130px, 35vw)` | ✅ |
| RMOB-08 | `site.css` | Personaje hero reducido a 80×90px en <576px | ✅ |
| RMOB-09 | `Pomodoro/Index.cshtml:98-127` | Stats apilados a 100% con clase `.pomodoro-stats` | ✅ |
| RMOB-10 | `site.css` | Círculos de ánimo reducidos a min-width:22px en móvil | ✅ |
| RMOB-11 | Login, Registro, Admin/Login + `site.css` | Icono 64px → 48px con clase `.auth-icon-box` en móvil | ✅ |
| RMOB-12 | `Shared/Error.cshtml:40` | Icono error con `clamp(64px, 25vw, 100px)` | ✅ |
| RMOB-13 | `Ajustes/Index.cshtml:51,57` | Foto perfil con `min(88px, 30vw)` | ✅ |
| RMOB-14 | `site.css` | Padding de Pomodoro card reducido a 1.5rem en móvil | ✅ |

### 🔵 Bajos — ✅ Resueltos

| ID | Archivo | Solución aplicada | Estado |
|----|---------|-------------------|--------|
| RMOB-15 | `site.css` | col-5/col-7 forzados a 100% en <576px | ✅ |
| RMOB-16 | `Progreso/Index.cshtml:7` | `container-fluid` externo eliminado | ✅ |
| RMOB-17 | `site.css` | Sidebar cambiado a `overflow-y:auto` | ✅ |
| RMOB-18 | `site.css` | Días de hábito visibles en versión condensada en móvil | ✅ |
| RMOB-19 | `Ajustes/Index.cshtml:241-244` | Barras de fortaleza aumentadas a 6px | ✅ |
| RMOB-20 | `site.css` | Hint de input IA visible en móvil con fuente compacta | ✅ |

---

## 🏗️ ARQUITECTURA — Auditoría (2026-06-18)

> Auditoría de arquitectura completa del proyecto monolitico ASP.NET Core 9 + MariaDB + Gemini API.
> VPS: Debian 13, 1 CPU, 4GB RAM, 50GB SSD, 4TB ancho de banda.

### 🔴 CRÍTICOS — Violaciones arquitectónicas graves

| ID | Prioridad | Archivo | Problema | Riesgo | Solución |
|----|-----------|---------|----------|--------|----------|
| ARQ-001 | **Muy Alta** | `appsettings.json` | **Gemini API Key real en texto plano** — el archivo está en `.gitignore` pero existía en histórico de git con la key real | **Crítico** — Exposición en git history | **✅ Corregido:** Reemplazado por placeholder `CHANGE_ME_GEMINI_API_KEY`. El valor real debe ir en variable de entorno `Gemini__ApiKey`. **Pendiente:** Rotar la API key comprometida en Google Cloud Console y purgar del historial git. |
| ARQ-002 | **Muy Alta** | `appsettings.json` | **Contraseña de BD en texto plano** en `appsettings.json` dentro del repo | **Crítico** — Exposición de credenciales de base de datos | **✅ Corregido:** Reemplazado por placeholder `CHANGE_ME_DB_PASSWORD`. **Pendiente:** Rotar la contraseña comprometida en MariaDB. |
| ARQ-003 | **Muy Alta** | `Program.cs:132-137`, `IaController.cs` | **Rate limiter "Gemini" (20/min) NUNCA aplicado.** No hay `[EnableRateLimiting("Gemini")]` en IaController. Solo el global (200/min) protege. Un atacante autenticado puede hacer 200 llamadas/min a Gemini = costos reales. | **Alto** — Costos de API inesperados | Agregar `[EnableRateLimiting("Gemini")]` en IaController a nivel clase. |
| ARQ-004 | **Muy Alta** | `Servicios/Implementaciones/GeminiHealthCheck.cs:24` | **API Key expuesta en URL como query parameter** `?key={_apiKey}` — queda en logs del servidor, proxies, nginx, y telemetría | **Alto** — Exposición de API key en logs | **✅ Corregido:** Ahora usa header `x-goog-api-key`. También lee `Gemini:Modelo` de configuración en vez de hardcodear. |
| ARQ-005 | **Alta** | `Servicios/Implementaciones/ServicioIA.cs` | **API key enviada como query param** `?key={_apiKey}` en las llamadas a Gemini API. Visible en logs, proxies y nginx access log | **Alto** — Exposición en logs | **✅ Corregido:** Ahora usa header `x-goog-api-key`. |
| ARQ-006 | **Alta** | `Controllers/IaController.cs:50` | **Endpoint `/api/ia/chat` sin `[ValidateAntiForgeryToken]`.** JS fetch no envía token CSRF. Vulnerable a ataques CSRF. | **Alto** — Ataque CSRF en endpoint IA | Agregar `[ValidateAntiForgeryToken]`. Enviar token desde JS usando `X-CSRF-TOKEN` header (ya configurado en Program.cs:153). |
| ARQ-007 | **Alta** | `Program.cs:237-253` | **Middleware pipeline fuera de orden según docs Microsoft.** Orden actual: StaticFiles → Routing → RateLimiter → SecurityHeaders → CORS → Auth. Orden correcto: ExceptionHandler → HSTS → HttpsRedirection → StaticFiles → Routing → CORS → Auth → RateLimiter → Endpoints | **Medio** — Security headers no cubren static files, CORS antes de auth puede causar problemas | Reordenar pipeline según https://learn.microsoft.com/en-us/aspnet/core/fundamentals/middleware/#middleware-order |

### 🟡 IMPORTANTES — Malas prácticas y deuda técnica

| ID | Prioridad | Archivo | Problema | Solución |
|----|-----------|---------|----------|----------|
| ARQ-008 | **Alta** | `Controllers/HabitosController.cs` | **Lógica de negocio en controller.** `CompletarModeloDesdeFormulario()`, `CompletarDiasSemanaDesdeFormulario()`, `EsCheckboxMarcado()` son métodos que parsean manualmente `Request.Form` — deberían estar en el servicio o usar model binding correcto | Mover lógica de parseo de formulario a un Custom Model Binder o al servicio `ServicioHabitos`. |
| ARQ-009 | **Alta** | `DTOs/` vs `Models/DTOs/` | **Dos directorios DTOs inconsistentes.** `DTOs/` tiene 6 DTOs de hábitos/pomodoro. `Models/DTOs/` tiene `RespuestaOperacion.cs`. `Ayudantes/RespuestaApi.cs` es otro helper similar. Duplicidad conceptual. | Unificar todo en `DTOs/`. Eliminar `Models/DTOs/`. Decidir si usar `RespuestaOperacion` o `RespuestaApi<T>` (no ambos). |
| ARQ-010 | **Alta** | `ViewModels/` | **ViewModels referencian directamente entidades del modelo.** `BienestarViewModel` expone `EstadoAnimo?` directamente en lugar de un DTO específico. `PerfilViewModel` expone `Usuario?`. Esto acopla la vista al modelo de datos. | Crear DTOs específicos para vistas. No exponer entidades EF directamente en ViewModels. |
| ARQ-011 | **Media** | `ServicioIA.cs` aprox línea 46 | **ChatAsync guarda mensaje en DB ANTES de llamar a Gemini.** Si Gemini falla tras reintentos, el mensaje del usuario queda persistido pero la respuesta no. Conversaciones con mensajes huérfanos. | Envolver en transacción. Si Gemini falla, hacer rollback del mensaje de usuario. O guardar ambos (user+model) juntos al final. |
| ARQ-012 | **Media** | `ServicioIA.cs`, `GeminiHealthCheck.cs` | **Inconsistencia de HttpClient.** `ServicioIA.cs` podría usar el cliente nombrado `"Gemini"`. `GeminiHealthCheck.cs` crea su propio `HttpClient` genérico en vez de usar `IHttpClientFactory.CreateClient("Gemini")`. Hardcodea el modelo en vez de leer `Gemini:Modelo` de config. | **⚠️ Parcial:** `GeminiHealthCheck` ahora lee `Gemini:Modelo` de configuración. Pendiente: unificar uso de `CreateClient("Gemini")` en ambos archivos. |
| ARQ-013 | **Media** | `DatosSemilla.cs:197` | **Seed data usa `Debug.WriteLine` para logging de errores** en lugar de `ILogger<DatosSemilla>`. En producción, `Debug.WriteLine` no genera salida. | Inyectar `ILogger<DatosSemilla>` en el método o usar `ILoggerFactory` desde el scope. |
| ARQ-014 | **Media** | Varios controladores | **Nested DTO classes definidas dentro de archivos de controller.** `IaController.ChatMensajeDto` y `PerfilController.CambiarTemaDto` están al final de archivos de controller en vez de en `DTOs/`. | Mover a `DTOs/` como archivos separados. |

### 🟢 MEJORAS RECOMENDADAS

| ID | Prioridad | Archivo | Problema | Solución |
|----|-----------|---------|----------|----------|
| ARQ-015 | **Media** | Toda la app | **Sin capa de caché.** Las consultas a categorías, carreras, niveles, frases motivacionales y tips Pomodoro se hacen a BD en cada request. Datos quasi-estáticos que podrían cachearse. | Implementar `IMemoryCache` o `IDistributedCache` (Redis si hay recursos) para datos maestros. TTL: 5-30 min según volatilidad. |
| ARQ-016 | **Baja** | `Program.cs` | **Sin graceful shutdown.** Al recibir SIGINT/SIGTERM, ASP.NET Core corta requests en curso. Podría dejar operaciones a medias. | Agregar `builder.Services.Configure<HostOptions>(o => o.ShutdownTimeout = TimeSpan.FromSeconds(10))`. |
| ARQ-017 | **Baja** | `Models/Entidades/Suscripcion.cs:9` | **`PrecioSoles` usa `decimal`** pero es un campo monetario. Podría beneficiarse de `[Column(TypeName = "decimal(10,2)")]` para precisión en BD. | Agregar atributo de columna para tipo decimal con precisión explícita. |
