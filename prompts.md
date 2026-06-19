# Auditoría EpycusApp — Prompts por Especialidad

> Proyecto: EpycusApp (ASP.NET Core 9 + MariaDB + Gemini API + Bootstrap 5)
> Propósito: Sistema de gamificación de hábitos profesionales (estilo Solo Leveling) para universitarios peruanos
> Repositorio: https://github.com/Chester0802/EpycusApp
> Web: http://app.epycus.es | Swagger: http://app.epycus.es/swagger | Health: http://app.epycus.es/health
> VPS: 147.93.119.193 (Debian 13 Trixie) | Dominio: app.epycus.es
> Última actualización: 2026-06-19

---

## Cómo usar estos prompts

Cada prompt está diseñado para un **auditor especializado**. Copia el prompt correspondiente en una sesión de IA con contexto del proyecto o pásalo a un revisor humano con esa especialidad. Cada uno incluye:

1. **Contexto del proyecto** (resumido para esa especialidad)
2. **Alcance de la auditoría** (qué archivos/directorios revisar)
3. **Checklist de revisión** (qué buscar específicamente)
4. **Entregables esperados**

---

## Índice de Especialidades

| # | Especialidad | Código | Enfoque principal |
|---|-------------|--------|-------------------|
| 1 | Arquitectura y Diseño | ARQ | Estructura general, patrones, acoplamiento, SOLID, separación de capas |
| 2 | Seguridad | SEC | OWASP, JWT, CSRF, XSS, inyecciones, manejo de secretos, rate limiting |
| 3 | Base de Datos | BD | Schema, índices, migraciones, rendimiento de queries, normalización |
| 4 | UI/UX & Frontend | UX | Accesibilidad (WCAG), responsive, sistema de temas, micro-interacciones, consistencia visual |
| 5 | Rendimiento y Escalabilidad | PERF | Caching, N+1 queries, lazy loading, memory leaks, bottleneck de IA |
| 6 | Gamificación y Lógica de Negocio | GAME | Balance XP/niveles, logros, economía del sistema, engagement, ODS 3 |
| 7 | API & Integraciones | API | RESTful design, versionado, manejo de errores, documentación, contratos móvil |
| 8 | Infraestructura & DevOps | OPS | CI/CD, deploy, monitoreo, backup, SSL, systemd, Nginx |
| 9 | Calidad de Código | DEV | Clean Code, nombres, duplicación, cobertura de tests, convenciones C# |
| 10 | Cumplimiento y Regulatorio | REG | ODS 3, GDPR, política de privacidad, términos de servicio, datos de menores |
| 11 | IA / Asistente EDY | IA | Gemini API, prompts, safety settings, fallback DeepSeek, contexto de ánimo |
| 12 | Gamificación Avanzada (RPG) | RPG | Sistema de personajes, carreras, niveles por carrera, skins, personalización |

---

## 1. ARQ — Arquitectura y Diseño

### Contexto
EpycusApp es una aplicación MVC en ASP.NET Core 9 con Entity Framework Core + MariaDB, patrón de servicios con interfaces, controladores MVC + API REST, sistema de autenticación JWT + Google OAuth, y un subsistema de gamificación (XP, niveles, logros, personajes). Se despliega en un VPS Debian 13 con Nginx como reverse proxy.

### Archivos a revisar
- `Program.cs` — Configuración completa de DI, middleware, auth, rate limiting, health checks
- `Controllers/` — 12 MVC + 8 API + 2 Base controllers
- `Servicios/Implementaciones/` — 13 servicios con lógica de negocio
- `Servicios/Interfaces/` — 12 interfaces
- `Models/Entidades/` — 29 entidades
- `Middleware/` — TelemetriaMiddleware, CargarPersonajeFilter
- `Ayudantes/` — CalculadorXP, ConstantesGamificacion, RespuestaApi, GeneradorCodigo
- `Datos/` — ContextoAplicacion, DatosSemilla

### Checklist
- [ ] ¿La separación de capas es correcta? (Controller → Service → Repository/EF)
- [ ] ¿Hay fugas de responsabilidades? (lógica de negocio en controllers, queries en vistas)
- [ ] ¿Se respeta la Inversión de Dependencias? (todas las dependencias vía interfaces)
- [ ] ¿El acoplamiento entre módulos es manejable?
- [ ] ¿Hay dependencias circulares?
- [ ] ¿Los DTOs/ViewModels están correctamente separados de las entidades?
- [ ] ¿El patrón de paginación es escalable?
- [ ] ¿El `BaseController` y `BaseApiController` son consistentes?
- [ ] ¿La configuración en `Program.cs` es mantenible? (demasiado larga)
- [ ] ¿El middleware pipeline tiene el orden correcto?
- [ ] ¿Hay sobre-ingeniería en alguna parte?
- [ ] ¿Los Filters (`CargarPersonajeFilter`) tienen side effects?
- [ ] ¿El sistema de temas (claro/oscuro) está bien integrado a nivel arquitectura?

### Entregables
- Diagrama de arquitectura actual (capas y flujo de datos)
- Hallazgos de acoplamiento indebido
- Recomendaciones de refactorización priorizadas
- Propuesta de evolución arquitectónica (DDD, CQRS, Event Sourcing si aplica)

---

## 2. SEC — Seguridad

### Contexto
Aplicación web con autenticación JWT (HttpOnly cookies), Google OAuth, registro de usuarios, sistema de roles (admin/usuario), panel admin, API REST, integración con Gemini API, manejo de contraseñas con BCrypt, rate limiting. Pendientes conocidos: sin CAPTCHA, sin bloqueo de cuenta, sin política de contraseñas fuertes.

### Archivos a revisar
- `Program.cs` — Configuración JWT, CORS, antiforgery, cookies, rate limiting
- `appsettings.json` — Secretos, API keys, config de correo
- `appsettings.Example.json` — Placeholders
- `Controllers/AutenticacionController.cs` — Login, registro, recuperación
- `Controllers/Api/ApiAuthController.cs` — API auth endpoints
- `Servicios/Implementaciones/ServicioAutenticacion.cs` — JWT, refresh tokens, hash
- `Models/Entidades/Usuario.cs`, `TokenRefresh.cs`, `RecuperacionContrasena.cs`
- `deploy/epycus-web.service` — Variables de entorno para secretos
- `Views/Autenticacion/` — Formularios de login, registro, recuperación
- `Middleware/` — Security headers
- `.github/workflows/ci-cd.yml` — Gitleaks step

### Checklist
- [ ] ¿Las contraseñas se hashean con BCrypt (workFactor >= 12)?
- [ ] ¿Los JWT se almacenan en cookies HttpOnly, Secure, SameSite?
- [ ] ¿Se validan issuer, audience, lifetime y signing key?
- [ ] ¿Los refresh tokens se almacenan como hash SHA256?
- [ ] ¿Se rotan los refresh tokens? (invalida el anterior al renovar)
- [ ] ¿Hay protección contra CSRF en formularios MVC y APIs?
- [ ] ¿Las APIs verifican antiforgery o solo los formularios?
- [ ] ¿Hay rate limiting en login/registro?
- [ ] ¿Hay bloqueo de cuenta tras N intentos fallidos?
- [ ] ¿La política de contraseñas exige longitud mínima y complejidad?
- [ ] ¿Hay CAPTCHA en formularios públicos?
- [ ] ¿Se auditan eventos de seguridad (login fallido, cambio de contraseña)?
- [ ] ¿Las API keys (Gemini, DeepSeek) están protegidas?
- [ ] ¿CORS está configurado correctamente (whitelist)?
- [ ] ¿Los security headers están completos? (CSP, HSTS, X-Frame-Options)
- [ ] ¿El CSP no es muy permisivo? (`unsafe-inline`, CDNs)
- [ ] ¿Hay validación de tamaño/tipo de archivos subidos?
- [ ] ¿El cambio de contraseña invalida JWTs existentes?
- [ ] ¿Gitleaks está correctamente configurado?
- [ ] ¿Hay secretos hardcodeados en el código?

### Entregables
- Matriz de riesgos de seguridad (Crítico/Alto/Medio/Bajo)
- Lista de vulnerabilidades OWASP Top 10 aplicables
- Recomendaciones con severity y esfuerzo estimado
- Pruebas de penetración sugeridas

---

## 3. BD — Base de Datos

### Contexto
MariaDB 11.8 con EF Core 9 (Pomelo provider), 29 tablas, migraciones EF Core, seed data. Pendientes: sin backup automatizado, sin pooling de conexiones configurado.

### Archivos a revisar
- `Datos/ContextoAplicacion.cs` — DbSets, relaciones, índices, configuración de FKs
- `Models/Entidades/` — 29 entidades (atributos, tipos de datos, navegación)
- `Migrations/` — Migraciones existentes (4 migraciones + snapshot)
- `Datos/Semilla/DatosSemilla.cs` — Seed data
- `Program.cs` — Configuración de DbContext, health check MySQL
- `appsettings.json` — Connection string

### Checklist
- [ ] ¿Las tablas están normalizadas (3FN)?
- [ ] ¿Hay tipos de datos correctos? (DateTime vs DateTime2, decimal para montos)
- [ ] ¿Hay índices en todas las foreign keys y columnas de búsqueda frecuente?
- [ ] ¿Los índices compuestos cubren las queries más comunes?
- [ ] ¿Hay consultas N+1 en servicios? (Include/ThenInclude vs lazy loading)
- [ ] ¿Las migraciones están consolidadas?
- [ ] ¿La semilla tiene data suficiente para desarrollo?
- [ ] ¿Hay Soft Delete vs Hard Delete?
- [ ] ¿Las relaciones tienen DeleteBehavior correcto? (Cascade, Restrict, SetNull)
- [ ] ¿Hay auditoría de cambios (created_at, updated_at)?
- [ ] ¿La estrategia de concurrencia es adecuada?
- [ ] ¿Hay columnas que deberían ser índices únicos compuestos?
- [ ] ¿La tabla `Log` tiene estrategia de retención/purgado?
- [ ] ¿Hay dependencias circulares entre tablas?
- [ ] ¿Se usa correctamente `DateOnly` vs `DateTime`?
- [ ] ¿El pooling de conexiones está configurado? (`MaxRetryCount`, `EnableRetryOnFailure`)
- [ ] ¿Hay plan de backup automatizado?

### Entregables
- Diagrama entidad-relación (DER) actualizado
- Plan de indexing
- Queries lentas identificadas (si aplica)
- Recomendaciones de migración y mantenimiento
- Estrategia de backup/restore

---

## 4. UX — UI/UX & Frontend

### Contexto
Frontend con Razor Views + Bootstrap 5 + Chart.js, sistema de temas claro (Sakura) y oscuro (Solo Leveling), CSS con variables, theme-manager.js. Tres layouts: `_Layout.cshtml` (app), `_LayoutAdmin.cshtml` (admin), `_LayoutAuth.cshtml` (login/registro). Pendientes conocidos: múltiples hallazgos WCAG, contrastes en Sakura, hardcodeo de colores en JS/CSS.

### Archivos a revisar
- `wwwroot/css/` — variables.css, site.css, auth.css, dashboard.css, bienestar.css, diario-animo.css, ia.css, perfil.css, epycus-modern.css, notificaciones.css, admin.css, temas/tema-sakura.css, temas/tema-noche-epica.css
- `wwwroot/js/` — site.js, dashboard.js, notificaciones.js, theme-manager.js
- `Views/` — Todas las vistas (Habitos, Pomodoro, Misiones, Progreso, Perfil, Bienestar, DiarioAnimo, IA, Ajustes, Admin, Home, Autenticacion)
- `Views/Shared/` — Layouts, partials (_MensajeFeedback, _Paginacion, _ValidationScriptsPartial)
- `wwwroot/img/` — Personajes, logros, favicon, logo

### Checklist
- [ ] ¿El sistema de temas (claro/oscuro) es consistente en todas las vistas?
- [ ] ¿Hay colores hardcodeados en lugar de variables CSS?
- [ ] ¿Los contrastes WCAG AA se cumplen? (ratio >= 4.5:1 texto normal)
- [ ] ¿Hay FOUC (Flash of Unstyled Content) en algún layout?
- [ ] ¿Los skeletons/spinners funcionan correctamente?
- [ ] ¿La navegación por teclado es completa? (aria-label, tabindex, focus visible)
- [ ] ¿Los formularios tienen feedback visual de validación?
- [ ] ¿Las tablas son responsivas en móvil?
- [ ] ¿El sidebar funciona correctamente en móvil con hamburguesa?
- [ ] ¿Los toasts/notificaciones se muestran correctamente en ambos temas?
- [ ] ¿Chart.js respeta el tema activo? (colores cambian con getComputedStyle)
- [ ] ¿Hay `alert()` nativos en lugar del sistema de notificaciones?
- [ ] ¿El CSS tiene código muerto/no usado?
- [ ] ¿Las vistas tienen metadatos SEO básicos? (title, description, og:)
- [ ] ¿Hay soporte PWA? (manifest.json, service worker)
- [ ] ¿Las imágenes tienen alt text descriptivo?
- [ ] ¿Los iconos Bootstrap Icons se usan consistentemente (sin emojis)?
- [ ] ¿Hay páginas de error personalizadas? (404, 500)
- [ ] ¿La jerarquía tipográfica es consistente?
- [ ] ¿Los botones tienen hover/pressed/active states?

### Entregables
- Reporte de accesibilidad WCAG 2.1 (A + AA)
- Lista de inconsistencias visuales por vista
- Recomendaciones de mejora de contraste
- Plan de migración a PWA
- Auditoría de rendimiento frontend (Lighthouse)

---

## 5. PERF — Rendimiento y Escalabilidad

### Contexto
App con EF Core + MariaDB, integración con Gemini API (latencia variable), telemetría para requests lentos, health checks, sin caché de datos frecuentes, sin Redis.

### Archivos a revisar
- `Servicios/Implementaciones/` — Todos los servicios (posibles N+1, queries ineficientes)
- `Program.cs` — HttpClient config, rate limiting, health checks
- `Middleware/TelemetriaMiddleware.cs` — Monitoreo de requests lentos
- `Views/` — Renderizado de vistas, partials costosos
- `wwwroot/` — Bundling de JS/CSS, tamaño de assets
- `Datos/ContextoAplicacion.cs` — Índices, Include/ThenInclude en queries

### Checklist
- [ ] ¿Hay consultas N+1 en servicios?
- [ ] ¿Hay queries sin filtro (SELECT sin WHERE) en endpoints frecuentes?
- [ ] ¿Las vistas usan Partial/Component eficientemente?
- [ ] ¿Los assets estáticos están minificados/bundlizados?
- [ ] ¿Hay lazy loading de imágenes?
- [ ] ¿Chart.js se carga solo donde se usa?
- [ ] ¿Las llamadas a Gemini/DeepSeek tienen timeout y retry?
- [ ] ¿La telemetría monitorea endpoints críticos?
- [ ] ¿Hay caching de datos maestros? (carreras, niveles, frases)
- [ ] ¿Se recomienda Redis o MemoryCache?
- [ ] ¿El rate limiting es adecuado por endpoint?
- [ ] ¿Hay páginas que renderizan demasiados datos sin paginación?
- [ ] ¿Los health checks son pesados? (consultan BD y APIs externas)
- [ ] ¿El HttpClient está correctamente gestionado? (IHttpClientFactory)
- [ ] ¿El pool de conexiones MySQL está optimizado?
- [ ] ¿Hay compresión de respuestas HTTP?

### Entregables
- Reporte de cuellos de botella identificados
- Recomendaciones de caching (qué, cómo, TTL)
- Perfil de rendimiento por endpoint (tiempo de respuesta estimado)
- Plan de optimización priorizado

---

## 6. GAME — Gamificación y Lógica de Negocio

### Contexto
Sistema RPG gamificado: XP por hábitos, pomodoros, misiones y logins diarios; niveles (1-20), personajes por carrera universitaria, logros, rachas. Inspirado en Solo Leveling. Incluye módulo ODS 3 (bienestar) con alertas y diario de ánimo.

### Archivos a revisar
- `Ayudantes/CalculadorXP.cs` — Fórmulas de XP y niveles
- `Ayudantes/ConstantesGamificacion.cs` — Constantes de XP
- `Servicios/Implementaciones/ServicioGamificacion.cs` — Lógica de gamificación
- `Servicios/Implementaciones/ServicioHabitos.cs` — Asignación de XP y rachas
- `Servicios/Implementaciones/ServicioPomodoro.cs` — XP por sesiones
- `Servicios/Implementaciones/ServicioMisiones.cs` — XP por misiones
- `Servicios/Implementaciones/ServicioProgreso.cs` — Cálculo de progreso
- `Servicios/Implementaciones/ServicioBienestar.cs` — Alertas ODS 3
- `Models/Enums/` — PrioridadMision, EstadoMision, FrecuenciaHabito, CondicionLogro, EstadoAnimoEnum
- `Models/Entidades/` — Logro, LogroUsuario, Nivel, Personaje, PersonajeUsuario, ImagenNivelPersonaje, ProgresoUsuario
- `Datos/Semilla/DatosSemilla.cs` — Logros, niveles, frases seed

### Checklist
- [ ] ¿El balance XP es adecuado para engagement? (demasiado lento/rápido subir de nivel)
- [ ] ¿Hay recompensas por rachas? (días consecutivos)
- [ ] ¿Los logros son alcanzables y motivantes?
- [ ] ¿Hay variedad de tipos de logro? (progresión, rareza, desafío)
- [ ] ¿Las constantes XP están centralizadas y son configurables?
- [ ] ¿El cálculo de XP es consistente entre servicios?
- [ ] ¿Hay penalización por fallar hábitos/misiones?
- [ ] ¿El sistema de niveles por carrera universitaria tiene sentido?
- [ ] ¿Hay recompensas cosméticas por nivel? (imágenes de personaje)
- [ ] ¿Los personajes tienen progresión visual?
- [ ] ¿Hay elementos sociales? (rankings, comparación)
- [ ] ¿El módulo ODS 3 está integrado con la gamificación? (logros ODS 3)
- [ ] ¿Hay eventos especiales o misiones diarias/semanales?
- [ ] ¿La economía del sistema es sostenible? (no inflación de XP)
- [ ] ¿Hay feedback visual de progreso y logros? (animaciones, popups)

### Entregables
- Análisis de curva de progresión (XP por nivel, tiempo estimado)
- Recomendaciones de balance (valores ajustados)
- Propuestas de nuevos tipos de logros/recompensas
- Estrategia de monetización gamificada (si aplica)
- Auditoría de engagement y retención

---

## 7. API — API & Integraciones

### Contexto
API REST en `/api/*` con 8 controllers, JWT en cookies (no en header), documentación Swagger, destinada a futura app Flutter. Pendientes: los endpoints no devuelven JWT en body para móvil, no hay versionado de API.

### Archivos a revisar
- `Controllers/Api/` — 8 API controllers + BaseApiController
- `Ayudantes/RespuestaApi.cs` — DTO genérico de respuesta
- `DTOs/` — DTOs específicos (CompletarHabitoRespuestaDto, etc.)
- `Program.cs` — Rate limiting para API, CORS, JWT config
- `Servicios/Implementaciones/` — Servicios que usan los API controllers

### Checklist
- [ ] ¿Los endpoints RESTful siguen convenciones? (sustantivos, plurales, HTTP methods)
- [ ] ¿Hay consistencia en los formatos de respuesta? (todos usan RespuestaApi<T>)
- [ ] ¿Los códigos HTTP son correctos? (201 Created, 204 No Content, etc.)
- [ ] ¿El manejo de errores es uniforme?
- [ ] ¿Hay paginación en endpoints de listado?
- [ ] ¿Los filtros/búsqueda están implementados? (query params)
- [ ] ¿Los endpoints requieren autenticación consistentemente? `[Authorize]`
- [ ] ¿La API devuelve JWT en body para clientes móviles? (MOB-002 pendiente)
- [ ] ¿Hay rate limiting diferenciado por endpoint?
- [ ] ¿La documentación Swagger está completa? (schemas, ejemplos)
- [ ] ¿Hay versionado de API? (v1, v2)
- [ ] ¿Los endpoints exponen datos sensibles?
- [ ] ¿Hay validación de entrada en todos los endpoints?
- [ ] ¿Los DTOs de request/response están correctamente diseñados?
- [ ] ¿Hay tests de API (integración)?

### Entregables
- Postman Collection / OpenAPI spec actualizada
- Contrato API completo (request/response para móvil)
- Hallazgos de inconsistencias RESTful
- Plan de versionado de API

---

## 8. OPS — Infraestructura & DevOps

### Contexto
VPS Debian 13 + Nginx + systemd + MariaDB 11.8, CI/CD con GitHub Actions (4 jobs), deploy manual alternativo por SSH, monitoreo con health checks + uptime script, pendiente: HTTPS con Let's Encrypt, rollback automático.

### Archivos a revisar
- `deploy/` — nginx-epycus.conf, epycus-web.service, setup-vps.sh, monitoreo-uptime.sh, journald-log-rotation.conf, maintenance.html, maintenance.sh, *.example
- `.github/workflows/ci-cd.yml` — Pipeline CI/CD
- `.github/workflows/deploy.yml` — Deploy workflow
- `.github/dependabot.yml` — Dependencias
- `.gitleaks.toml` — Config de Gitleaks
- `Program.cs` — ForwardedHeaders, HSTS, HTTPS redirect

### Checklist
- [ ] ¿El pipeline CI/CD tiene stages de calidad? (build, lint, tests)
- [ ] ¿Hay rollback automático si el health check post-deploy falla?
- [ ] ¿Las migraciones de BD se ejecutan automáticamente en el pipeline?
- [ ] ¿El backup de BD está automatizado? (cron + mysqldump)
- [ ] ¿SSL/HTTPS está configurado? (Let's Encrypt + Certbot)
- [ ] ¿El Nginx tiene configuraciones de seguridad? (límites, timeout, buffer)
- [ ] ¿El servicio systemd tiene restart on failure?
- [ ] ¿Las variables de entorno del servicio están seguras?
- [ ] ¿Hay monitoreo de uptime con alertas? (Discord/Telegram webhook)
- [ ] ¿La rotación de logs está configurada?
- [ ] ¿Hay página de mantenimiento para downtime planificado?
- [ ] ¿El firewall del VPS está configurado?
- [ ] ¿Hay plan de disaster recovery?
- [ ] ¿Las credenciales de GitHub Actions (secrets) son seguras?
- [ ] ¿Dependabot está activo y revisando semanalmente?

### Entregables
- Checklist de hardening del servidor
- Plan de disaster recovery
- Recomendaciones de CI/CD (rollback, migraciones, tests)
- Propuesta de monitoreo avanzado (Datadog, Sentry, OpenTelemetry)

---

## 9. DEV — Calidad de Código

### Contexto
Proyecto en C# 12 con .NET 9, tests unitarios (xUnit + Moq + FluentAssertions), tests de aceptación (Playwright), convenciones de nombres en español (variables, clases, métodos). PENDIENTES.md registra más de 100 hallazgos previos.

### Archivos a revisar
- Todos los archivos .cs en Controllers/, Servicios/, Models/, Ayudantes/, Datos/, Middleware/
- `EpycusApp.Tests/` — Tests unitarios (9 archivos de servicios, 4 de controllers)
- `EpycusApp.AcceptanceTests/` — Tests de aceptación (5 archivos)
- `.editorconfig` (si existe)
- `EpycusApp.csproj` — Configuración del proyecto
- `PENDIENTES.md` — Historial de deuda técnica

### Checklist
- [ ] ¿Las convenciones de nombres son consistentes? (PascalCase, camelCase, prefijos)
- [ ] ¿Los métodos tienen una sola responsabilidad?
- [ ] ¿Hay código duplicado (DRY)?
- [ ] ¿Los nombres de variables/clases/métodos son auto-documentados?
- [ ] ¿Los comentarios son necesarios o código muerto?
- [ ] ¿Hay métodos largos (>30 líneas)?
- [ ] ¿Hay clases grandes (>300 líneas)?
- [ ] ¿Los servicios usan ILogger correctamente?
- [ ] ¿Las excepciones se manejan adecuadamente? (no tragar, no exponer)
- [ ] ¿Covertura de tests: unitarios + aceptación?
- [ ] ¿Los tests son independientes y repetibles?
- [ ] ¿Hay tests para casos de borde y error?
- [ ] ¿Los ViewModels tienen validación con DataAnnotations?
- [ ] ¿Los DTOs están limpios (sin lógica)?
- [ ] ¿El código async/await es correcto? (no async void, Task.WhenAll, etc.)
- [ ] ¿Hay magic strings/numbers sin constantes?
- [ ] ¿Los archivos están en UTF-8 sin BOM? (historial de mojibake)
- [ ] ¿El namespace coincide con la estructura de carpetas?

### Entregables
- Reporte de calidad de código (SonarQube o similar)
- Archivos con mayor deuda técnica
- Recomendaciones de refactorización
- Plan de mejora de cobertura de tests

---

## 10. REG — Cumplimiento y Regulatorio

### Contexto
App dirigida a universitarios peruanos, con registro de usuarios (correo, contraseña, fecha de nacimiento, carrera), ODS 3 (Salud y Bienestar), logros, datos de estado de ánimo. Potencialmente usuarios menores de edad.

### Archivos a revisar
- `Models/Entidades/Usuario.cs` — Datos personales recolectados
- `Views/Autenticacion/Registro.cshtml` — Términos y condiciones
- `Views/Autenticacion/Login.cshtml` — Formulario
- `Views/Bienestar/` — Datos de salud/ánimo
- `Views/DiarioAnimo/` — Preguntas psicológicas
- `Views/Shared/` — Layouts, cookies banner
- `PENDIENTES.md` — SEC-* items, DEV-013 (cookies GDPR)
- `Program.cs` — Configuración de cookies, CSP
- `wwwroot/` — Si hay manifest.json o meta tags de privacidad

### Checklist
- [ ] ¿Hay política de privacidad publicada?
- [ ] ¿Hay términos de servicio?
- [ ] ¿Los usuarios aceptan términos al registrarse?
- [ ] ¿Hay banner de consentimiento de cookies? (GDPR)
- [ ] ¿Los datos sensibles (ánimo, salud) tienen protección adicional?
- [ ] ¿Hay menores de edad en el público objetivo? ¿Cómo se maneja?
- [ ] ¿Se recolecta el mínimo de datos necesario? (minimización)
- [ ] ¿Los usuarios pueden eliminar su cuenta y datos? (right to be forgotten)
- [ ] ¿Hay portabilidad de datos? (exportar datos personales)
- [ ] ¿Las preguntas psicológicas del Diario de Ánimo tienen advertencia?
- [ ] ¿Hay recursos de crisis visibles? (línea 113 Perú)
- [ ] ¿ODS 3 está correctamente referenciado y atribuido?
- [ ] ¿Los datos se almacenan con cifrado en reposo?
- [ ] ¿Hay registro de consentimiento (audit trail)?

### Entregables
- Matriz de cumplimiento (GDPR, ley peruana de protección de datos)
- Checklist de términos y condiciones
- Recomendaciones de privacidad por diseño
- Evaluación de impacto de datos de salud/ánimo

---

## 11. IA — Asistente EDY (Gemini/DeepSeek)

### Contexto
Asistente virtual "EDY" integrado con Gemini API 2.5 Flash Lite, con fallback a DeepSeek, contexto de ánimo del usuario para personalizar respuestas, historial de conversación. Registra feedback de usuario en mensajes.

### Archivos a revisar
- `Servicios/Implementaciones/ServicioIA.cs` — Lógica de llamada a Gemini y DeepSeek
- `Servicios/Implementaciones/GeminiHealthCheck.cs` — Health check de Gemini
- `Servicios/Implementaciones/DeepSeekHealthCheck.cs` — Health check de DeepSeek
- `Controllers/IaController.cs` — MVC para chat
- `Controllers/Api/ApiChatController.cs` (si existe)
- `Models/Entidades/MensajeIA.cs` — Entidad de mensajes
- `Views/Ia/Index.cshtml` — UI del chat
- `wwwroot/css/ia.css` — Estilos del chat
- `Program.cs` — Configuración de IA, rate limiting Gemini
- `appsettings.json` — API keys, modelos

### Checklist
- [ ] ¿Los prompts de sistema son efectivos y seguros? (prompt injection protection)
- [ ] ¿El contexto de ánimo se incluye correctamente en el prompt?
- [ ] ¿Hay safety settings configurados? (bloqueo de contenido inapropiado)
- [ ] ¿Se maneja `promptFeedback`? (bloqueo por safety de Gemini)
- [ ] ¿El retry con backoff exponencial funciona correctamente?
- [ ] ¿El fallback a DeepSeek es transparente para el usuario?
- [ ] ¿Hay límite de tokens/configuración de ventana de contexto?
- [ ] ¿El historial de conversación se limita/trunca?
- [ ] ¿Los mensajes del usuario se sanitizan antes de enviar a la API?
- [ ] ¿El feedback del usuario se almacena y se usa?
- [ ] ¿Hay costos asociados a la API controlados?
- [ ] ¿El avatar de EDY y la UI son consistentes con la marca?
- [ ] ¿Hay rate limiting específico para Gemini (20/min)?
- [ ] ¿Hay pruebas de integración con la API de Gemini?
- [ ] ¿El health check de IA es confiable?

### Entregables
- Análisis de calidad de respuestas de EDY
- Recomendaciones de optimización de prompts
- Estrategia de costos (caching, límites, modelo más barato)
- Plan de evolución (fine-tuning, RAG, memoria a largo plazo)

---

## 12. RPG — Sistema de Personajes y Skins (Gamificación Avanzada)

### Contexto
Sistema de personajes por carrera universitaria (12+ carreras), niveles con imágenes progresivas, personalización de personaje, personaje visible en sidebar y perfil. Las imágenes de personaje varían por nivel y carrera. Pendiente: arte para la mayoría de carreras.

### Archivos a revisar
- `Models/Entidades/Personaje.cs` — Definición de personaje
- `Models/Entidades/PersonajeUsuario.cs` — Personaje asignado a usuario
- `Models/Entidades/ImagenNivelPersonaje.cs` — Imagen por nivel
- `Models/Entidades/Nivel.cs` — Definición de nivel
- `Servicios/Implementaciones/ServicioPerfil.cs` — Lógica de personaje
- `Servicios/Implementaciones/ServicioGamificacion.cs` — Progresión
- `Datos/Semilla/DatosSemilla.cs` — Seed de personajes y niveles
- `wwwroot/img/personajes/` — Carpetas por carrera (12 carpetas)
- `Middleware/CargarPersonajeFilter.cs` — Carga en ViewBag

### Checklist
- [ ] ¿Cada carrera tiene personaje único? (masculino/femenino o neutro)
- [ ] ¿Las imágenes progresan por nivel visualmente? (equipo, aura, fondo)
- [ ] ¿Hay al menos 1 imagen por nivel por carrera?
- [ ] ¿Los placeholder SVG son funcionales?
- [ ] ¿La personalización es suficiente? (color, accesorios, fondo)
- [ ] ¿Los personajes tienen nombres/lore?
- [ ] ¿Hay skins/temas especiales? (eventos, logros, temporada)
- [ ] ¿El personaje se muestra en todas las páginas? (sidebar)
- [ ] ¿Hay animaciones de evolución al subir de nivel?
- [ ] ¿Se puede cambiar de personaje dentro de la misma carrera?
- [ ] ¿Hay personajes desbloqueables por logros?
- [ ] ¿El sistema soporta múltiples carreras? (ingeniería, medicina, derecho, etc.)
- [ ] ¿Hay plan de arte para las carreras faltantes?

### Entregables
- Catálogo de personajes actual vs deseado
- Propuesta de skins y personalización
- Roadmap de arte (qué carreras priorizar)
- Sistema de desbloqueo y rarity
- Integración con logros y eventos especiales
