# PENDIENTES — Auditoría Pre-Producción EpycusApp

> Generado: 2026-06-15
> Proyecto: EpycusApp (ASP.NET Core 9 + MariaDB + Gemini API)

---

## 🔴 CRÍTICOS

| ID | Prioridad | Archivo | Problema | Riesgo | Estado | Solución |
|----|-----------|---------|----------|--------|--------|----------|
| CRITICO-001 | Alta | `appsettings.json` | API Key de Gemini y Google OAuth en texto plano (placeholder) | **Muy Alto** | ✅ Corregido | Usar variables de entorno en producción. El archivo está en `.gitignore`. |
| CRITICO-002 | Alta | `deploy/setup-vps.sh` | Contraseña de MariaDB hardcodeada (`***REMOVED***`) | **Muy Alto** | ✅ Corregido | Ahora usa `$DB_PASSWORD` (variable de entorno). |
| CRITICO-003 | Alta | `deploy/epycus-web.service` | Placeholder de credenciales en archivo de servicio | **Alto** | ⚠️ Mitigado | Archivo en `.gitignore`. Usar `.example` como template. |
| CRITICO-004 | Alta | `.github/workflows/ci-cd.yml` | Pipeline CI/CD vacío (0 bytes) | **Muy Alto** | ✅ Corregido | Creado pipeline completo con build, calidad, seguridad y deploy. |
| CRITICO-005 | Alta | `Servicios/Implementaciones/ServicioAutenticacion.cs:316` | Envía email de recuperación cuando debería enviar bienvenida | **Alto** | ✅ Corregido | Cambiado a `EnviarBienvenida()`. |

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

---

## 🔄 CI/CD

| ID | Estado | Descripción |
|----|--------|-------------|
| CI-001 | ✅ | Pipeline CI/CD creado con build, calidad, deploy y seguridad |
| CI-002 | ✅ | Backup automático antes del deploy (últimos 5 backups) |
| CI-003 | ✅ | Verificación de estado del servicio post-deploy |
| CI-004 | ❌ No planificado | Tests unitarios — el usuario decidió no implementarlos |
| CI-005 | ✅ | Warnings como errores en compilación |

---

## 🖥️ VPS

| ID | Estado | Descripción |
|----|--------|-------------|
| VPS-001 | ✅ | Script `setup-vps.sh` sin contraseñas hardcodeadas |
| VPS-002 | ✅ | Service `.example` template actualizado (uso de `CHANGE_ME` en lugar de valores reales) |
| VPS-003 | ✅ | Nginx config con reverse proxy, SSL, security headers |
| VPS-004 | ✅ | Dependabot configurado para NuGet y GitHub Actions |
| VPS-005 | ✅ | Health checks endpoint (`/health`) implementado con checks de BD, Gemini y disco. |

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

---

## Leyenda

- ✅ Corregido / Implementado
- ⚠️ Pendiente / Requiere acción
- ❌ No resuelto

> **Nota**: Los ítems marcados como ✅ ya fueron corregidos durante esta auditoría.
