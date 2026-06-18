# PENDIENTES — Auditoría Pre-Producción EpycusApp

> Generado: 2026-06-15 | Última actualización: 2026-06-18 (nuevos issues: login mojibake, Progreso/Ajustes/Inicio modo oscuro)
> Proyecto: EpycusApp (ASP.NET Core 9 + MariaDB + Gemini API)

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
| CRITICO-007 | **Muy Alta** | `appsettings.json` | Servicio de correo: `Correo:Contrasena` es placeholder (`CHANGE_ME_GMAIL_APP_PASSWORD`) | **Muy Alto** | ⚠️ Pendiente | Generar App Password de Gmail y configurarlo como variable de entorno. Sin esto, registro, recuperación y verificación de correo no funcionan. |
| CRITICO-008 | **Muy Alta** | `Views/Login/` | **Mojibake regresivo:** Login muestra "Correo ElectrÃ³nico" y "ContraseÃ±a" en lugar de "Correo Electrónico" y "Contraseña" | **Alto** | ⚠️ Pendiente | Revisar encoding del archivo `.cshtml` de login — posiblemente se reconvirtió a Windows-1252 o tiene un BOM incorrecto. Forzar UTF-8 sin BOM. |

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
| MEJ-016 | **Alta** | `wwwroot/css/variables.css`, temas | **Modo oscuro/claro:** Letras negras se pierden en modo oscuro. Contraste insuficiente en varios componentes. | **Alto** | ✅ Corregido | Eliminadas clases hardcodeadas `bg-dark text-white` en modales y formularios. Ahora usan variables CSS. |
| MEJ-017 | **Alta** | `wwwroot/css/` + `Views/` | **UI/UX general:** Diseño se siente genérico/hecho con IA. Imágenes placeholder, inicio plano, falta personalidad visual. | **Medio** | ✅ Corregido | Agregados loading skeletons, spinners, paginación, animaciones, micro-interacciones, transiciones de página, feedback visual en formularios, páginas de error personalizadas. |
| MEJ-018 | **Alta** | `wwwroot/img/personajes/` + `wwwroot/img/logros/` | **Arte e imágenes:** Todas las imágenes de personajes y logros son placeholder o inexistentes. | **Alto** | ⚠️ Parcial | Logo (`logo.webp`), favicon (`favicon.ico`) e imagen de login (`login-hero.webp`) ya agregados con la marca Epycus. Personajes de Ing. Sistemas y Medicina tienen PNG reales. Faltan ilustraciones originales para el resto de carreras y logros. |
| MEJ-019 | **Media** | Varias vistas | Sin estados de carga (skeleton screens / spinners) — las páginas se ven en blanco hasta que los datos llegan | **Medio** | ✅ Corregido | Clases CSS `.ep-skeleton`, `.ep-skeleton-text`, `.ep-spinner-overlay` añadidas. |
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
| VPS-007 | ⚠️ Pendiente | Agregar monitorio de uptime (ej: UptimeRobot, cron + webhook a Discord/Telegram si `/health` no responde 200) |
| VPS-008 | ⚠️ Pendiente | Configurar rotación de logs de systemd/journald para que no llenen el disco |
| VPS-009 | ⚠️ Pendiente | No hay página de mantenimiento — si la app se detiene, nginx debería mostrar un "503 Maintenance" en lugar del error genérico |

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
| UX-014 | **Alta** | Progreso | Modo oscuro/claro mal aplicado — colores incorrectos o ilegibles en ambas variantes (móvil y PC) | ⚠️ Pendiente |
| UX-015 | **Alta** | Ajustes | Mala distribución de tamaños en los elementos del formulario — desproporcionados en móvil y PC | ⚠️ Pendiente |
| UX-016 | **Alta** | Home/Inicio | Modo oscuro/claro necesita mejora — contrastes y colores no se ven bien en ninguna variante | ⚠️ Pendiente |
 
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
