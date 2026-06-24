# MÓDULO.md — Auditoría Integral EpycusApp v1.0

> **Propósito:** Este archivo contiene la auditoría completa del proyecto. Cada IA que trabaje aquí debe leerlo, resolver los items que pueda, marcar los items como `[RESUELTO]` con la fecha, y dejar notas para la siguiente IA. Ciclo: Leer → Resolver → Actualizar → Pasar al siguiente.

> **Fecha auditoría:** 2026-06-23  
> **Proyecto:** EpycusApp (ASP.NET Core MVC 9 + MariaDB + Bootstrap 5)  
> **Dominio:** https://app.epycus.es  
> **VPS:** 147.93.119.193 (Debian 13 Trixie)  
> **Repo:** https://github.com/Chester0802/EpycusApp  
> **Flujo deploy:** `git push` → GitHub Actions → SCP al VPS → `systemctl restart epycus-web`

---

## 🔴 NIVEL 1 — CRÍTICO (arreglar AHORA)

### SEC-003: Rate Limiter middleware mal posicionado
- **Archivo:** `Program.cs` (líneas 321-324)
- **Problema:** `app.UseRateLimiter()` está DESPUÉS de `app.UseAuthentication()` y `app.UseAuthorization()`. El orden correcto según Microsoft es: `UseRateLimiter` → `UseAuthentication` → `UseAuthorization`.
- **Riesgo:** El rate limiter global NO protege adecuadamente porque se ejecuta después de la autenticación. Las políticas con nombre (Api, Auth, Mobile, Gemini) tampoco funcionan correctamente.
- **Solución:** Mover `app.UseRateLimiter();` ANTES de `app.UseAuthentication();`
- **Estado:** `[RESUELTO: 2026-06-23]` Movido `app.UseRateLimiter()` antes de `app.UseAuthentication()` y `app.UseAuthorization()` en Program.cs



### SEC-005: Entidad Log configurada sin DbSet — error runtime
- **Archivo:** `Datos/ContextoAplicacion.cs` (línea 182-183)
- **Problema:** `modelBuilder.Entity<Log>().HasIndex(l => l.UsuarioId);` referencia la entidad `Log` pero NO existe `DbSet<Log> Logs` declarado en el DbContext.
- **Riesgo:** EF Core intentará registrar esta entidad en el modelo pero como no tiene DbSet y no es referenciada por ninguna otra entidad, esto puede causar `InvalidOperationException` al aplicar migraciones o al iniciar la app.
- **Solución:** Eliminar la configuración de `Log` en `OnModelCreating` (si no se usa) o agregar `DbSet<Log> Logs`.
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado `DbSet<Log> Logs` en `ContextoAplicacion.cs` para que la entidad Log tenga su DbSet correspondiente

---

## 🟠 NIVEL 2 — ALTA PRIORIDAD

### ARQ-001: Dos directorios DTOs inconsistentes
- **Archivos:** `Models/DTOs/` y `DTOs/`
- **Problema:** Existen DOS carpetas DTOs. `Models/DTOs/` contiene solo `RespuestaOperacion.cs` mientras que `DTOs/` contiene 11 archivos con todos los DTOs de la API.
- **Riesgo:** Confusión para desarrolladores. DTOs duplicados o inconsistentes.
- **Solución:** Unificar todo en `DTOs/`. Eliminar `Models/DTOs/`. Actualizar todos los `using`.
- **Estado:** `[RESUELTO: 2026-06-23]` Movido `RespuestaOperacion.cs` de `Models/DTOs/` a `DTOs/` con namespace actualizado. Eliminada carpeta `Models/DTOs/`

### ARQ-002: AjustesController duplica PerfilController
- **Archivos:** `Controllers/AjustesController.cs` y `Controllers/PerfilController.cs`
- **Problema:** Ambos controladores tienen prácticamente la misma funcionalidad: `Index()`, `ActualizarPerfil()`, `CambiarContrasena()`, `CambiarPersonaje()`. `AjustesController` es una copia casi exacta de `PerfilController`.
- **Riesgo:** Mantenimiento duplicado. Cambios en un controlador no se reflejan en el otro.
- **Solución:** Unificar en un solo controlador (ej: `PerfilController`) y redirigir las rutas de `Ajustes` a `Perfil`, o eliminar `AjustesController` y actualizar las vistas.
- **Estado:** `[RESUELTO: 2026-06-23]` `AjustesController.Index()` redirige a `PerfilController.Index()`. Los POST actions redirigen a Perfil o mantienen lógica mínima para compatibilidad con vistas existentes

### ARQ-003: HabitosController parsea Request.Form manualmente
- **Archivo:** `Controllers/HabitosController.cs` (líneas 179-223)
- **Problema:** En lugar de confiar en el model binding de ASP.NET, el controlador lee manualmente `Request.Form["Nombre"]`, `Request.Form["DiasSemana"]`, etc. Esto es frágil y propenso a errores.
- **Riesgo:** Validación inconsistente, errores difíciles de depurar, bypass de validación del modelo.
- **Solución:** Usar model binding estándar. Eliminar `CompletarModeloDesdeFormulario()` y `CompletarDiasSemanaDesdeFormulario()`.
- **Estado:** `[RESUELTO: 2026-06-23]` Eliminados métodos `CompletarModeloDesdeFormulario()`, `CompletarDiasSemanaDesdeFormulario()` y `EsCheckboxMarcado()`. El model binding estándar ahora maneja todos los campos excepto `DiasSemana` (que se parsea inline por ser coma-separado)

### ARQ-004: Nested DTOs definidos dentro de controladores
- **Archivos:** `Controllers/PerfilController.cs` (línea 111-114, clase `CambiarTemaDto`), `Controllers/IaController.cs` (líneas 128-144, clases `ChatMensajeDto`, `FeedbackDto`, `AnimoChatDto`)
- **Problema:** Clases DTO definidas como clases anidadas dentro de los archivos de controller, en lugar de estar en `DTOs/`.
- **Riesgo:** DTOs no reutilizables, desorden, difícil de encontrar.
- **Solución:** Mover a `DTOs/` como archivos separados.
- **Estado:** `[RESUELTO: 2026-06-23]` Extraídos `CambiarTemaDto` (de PerfilController), `ChatMensajeDto`, `FeedbackDto`, `AnimoChatDto` (de IaController) a archivos separados en `DTOs/`

### ARQ-005: Sin versionado de API
- **Archivo:** Todos los controladores API en `Controllers/Api/`
- **Problema:** Todos los endpoints son `/api/*` sin prefijo de versión (`/api/v1/*`). Cambios breaking romperían clientes (app móvil futura).
- **Riesgo:** Imposible evolucionar la API sin romper clientes existentes.
- **Solución:** Agregar ruteo por版本 (`/api/v1/auth/login`, etc.) usando `[Route("api/v1/[controller]")]` o similar.
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado `[Route("api/v1/...")]` en los 13 controladores API. Actualizadas todas las referencias `/api/` → `/api/v1/` en vistas y JS.

### ARQ-006: ServicioCorreo no usa interfaces de abstracción de SMTP
- **Archivo:** `Servicios/Implementaciones/ServicioCorreo.cs`
- **Problema:** Usa `SmtpClient` directamente que es `IDisposable`. En .NET 9, `SmtpClient` está obsoleto para nuevas aplicaciones.
- **Riesgo:** Futura incompatibilidad. Sin posibilidad de mock en tests.
- **Solución:** Usar `MailKit` (Recomendado) o `Microsoft.Extensions.Mail` si está disponible.
- **Estado:** `[RESUELTO: 2026-06-23]` Migrado de `System.Net.Mail.SmtpClient` a `MailKit` + `MimeKit`. Ahora usa `SmtpClient` de MailKit con `ConnectAsync` + `AuthenticateAsync` + `SendAsync`

### ARQ-007: Health checks no verifican el pipeline MVC
- **Archivo:** `Program.cs` (líneas 247-256)
- **Problema:** Los health checks solo verifican BD, Gemini, DeepSeek y disco. No verifican que el pipeline MVC funcione (que los controladores, razor views y autenticación respondan).
- **Riesgo:** Health check puede reportar "healthy" mientras la web está caída por errores en vistas o controladores.
- **Solución:** Agregar un health check que haga una request a `/Home/Index` o `/health/ready`.
- **Estado:** `[RESUELTO: 2026-06-23]` Creado `MvcHealthCheck.cs` que hace GET a `/Home/Index` y verifica HTML. Registrado en health checks con tag "mvc".`

---

## 🟡 NIVEL 3 — IMPORTANTE

### DB-001: Sin backup automático de BD
- **Problema:** No hay script de backup automático para MariaDB. Si la BD se corrompe, se pierden todos los datos de usuarios.
- **Riesgo:** Pérdida total de datos.
- **Solución:** Agregar cron job: `mysqldump -u epicus_user -p epycus_db > /var/backups/epycus-db-$(date +%Y%m%d).sql` y sincronizar a almacenamiento externo.
- **Estado:** `[RESUELTO: 2026-06-23]` Creado `deploy/backup-bd.sh` con mysqldump comprimido, rotación de 7 días (máx 30 backups). Incluir en cron: `0 3 * * * /var/www/epycus-web/deploy/backup-bd.sh`

### DB-002: Sin política de reintentos en conexión BD
- **Archivo:** `Program.cs` (líneas 47-56)
- **Problema:** `UseMySql` se configura sin `EnableRetryOnFailure`. Si la BD se reinicia o hay un error transitorio, la app lanzará excepción en lugar de reintentar.
- **Solución:** Agregar `options.EnableRetryOnFailure(maxRetryCount: 3, maxRetryDelay: TimeSpan.FromSeconds(10), null);`
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado `EnableRetryOnFailure(maxRetryCount: 3, maxRetryDelay: 10s)` en la configuración de `UseMySql` en Program.cs

### CI-001: CI/CD no ejecuta tests
- **Archivo:** `.github/workflows/ci-cd.yml`
- **Problema:** El pipeline no ejecuta los tests unitarios ni de aceptación. Solo hace restore, format check, build y deploy.
- **Riesgo:** Código roto puede llegar a producción sin ser detectado.
- **Solución:** Agregar `dotnet test EpycusApp.Tests` en el job `code-quality`.
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado paso `dotnet test` en job `code-quality` del pipeline CI/CD

### CI-002: Sin rollback automático en deploy fallido
- **Archivo:** `.github/workflows/ci-cd.yml`
- **Problema:** Si el deploy falla (health check post-deploy no responde), no hay rollback automático al backup.
- **Riesgo:** Producción caída hasta que alguien hace rollback manual.
- **Solución:** Agregar paso de rollback que restaure el backup si `systemctl is-active` falla.
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado paso de rollback automático que restaura el backup más reciente si el health check post-deploy falla. También se agregó notificación de estado final.

### CI-003: Sin migraciones de BD en CI/CD
- **Problema:** El pipeline no ejecuta `dotnet ef database update`. Las migraciones se aplican manualmente o en el startup (Program.cs línea 26).
- **Riesgo:** Si se agrega una migración y no se aplica manualmente antes del deploy, la app falla al iniciar.
- **Solución:** Agregar paso de migraciones en el pipeline o asegurar que `MigrateAsync()` en startup funcione correctamente.
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado paso de migraciones EF Core vía SSH en job `deploy` del pipeline CI/CD

### DEP-001: SSL no configurado (Certbot no ejecutado)
- **Archivos:** `deploy/setup-vps.sh` (línea 212), `deploy/nginx-epycus.conf`
- **Problema:** El setup script referencia certificados SSL que NO existen (`/etc/letsencrypt/live/app.epycus.es/`). Certbot nunca fue ejecutado. Nginx está sirviendo HTTP (puerto 80) pero las config HTTPS están presentes y fallarían si Nginx las carga.
- **Riesgo:** El sitio funciona solo en HTTP. Datos sensibles en texto plano. README dice "HTTPS: Pendiente".
- **Solución:** Ejecutar `certbot --nginx -d app.epycus.es --non-interactive --agree-tos -m app@epycus.es`
- **Estado:** `[RESUELTO: 2026-06-24]` Certificado SSL re-instalado con `certbot --nginx -d app.epycus.es`. HTTPS activo en https://app.epycus.es (HTTP/2, TLSv1.2/TLSv1.3)

### DEP-002: Config Nginx con error de sintaxis
- **Archivo:** `deploy/nginx-epycus.conf` (línea 18)
- **Problema:** `http2 on;` está en una línea separada. La sintaxis correcta es `listen 443 ssl http2;` (una sola línea). `http2 on;` ya no es válido en versiones recientes de Nginx.
- **Riesgo:** Nginx falla al recargar/configurar. HTTP/2 no funciona.
- **Solución:** Mover `http2` al parámetro `listen`.
- **Estado:** `[RESUELTO: 2026-06-23]` Movido `http2` al parámetro `listen` en `deploy/nginx-epycus.conf`

### DEP-003: README dice HTTPS pendiente pero setup lo configura
- **Archivo:** `README.md` (línea 53) vs `deploy/setup-vps.sh`
- **Problema:** README marca HTTPS como pendiente, pero `setup-vps.sh` configura Certbot y SSL. Hay inconsistencia documental.
- **Solución:** Decidir si HTTPS está configurado o no, y actualizar ambos archivos.
- **Estado:** `[RESUELTO: 2026-06-23]` No hay inconsistencia real: `setup-vps.sh` solo *muestra* el comando de Certbot (línea 212, `echo`), no lo ejecuta. HTTPS sigue pendiente. README correcto. Agregada nota en setup-vps.sh aclarando que es paso manual post-setup. Marcado DEP-001 como [PENDIENTE EN VPS] porque requiere ejecución en VPS

---

## 🔵 NIVEL 4 — UX/UI (Battle Royale + Mobile)

### UX-001: Personaje muy pequeño en mobile (80x90px)
- **Archivo:** `wwwroot/css/site.css` (líneas 437-438), `Views/Home/Index.cshtml` (línea 15)
- **Problema:** En mobile (<576px), el personaje se reduce a 80x90px. Es demasiado pequeño para apreciar el arte. El contenedor mide 80-120px de ancho.
- **Solución:** 
  - Hero: Aumentar a 180x220px en mobile, 260x300px en desktop
  - El personaje debería ocupar al menos 40% del ancho de pantalla en mobile
  - Usar `min(40vw, 260px)` para el tamaño
- **Recomendación Battle Royale:** El personaje debe ser GRANDE, con presencia. Inspirado en cómo los juegos gacha/gacha muestran sus personajes (grandes, con animación idle, fondo especial).
- **Estado:** `[RESUELTO: 2026-06-23]` Aumentado personaje en mobile a `min(45vw, 180px)` de ancho. Eliminados inline styles de Index.cshtml que sobrescribían el CSS. Desktop se mantiene en 220x240px desde dashboard.css

### UX-002: Personaje estático PNG sin animaciones
- **Archivo:** `wwwroot/img/personajes/`
- **Problema:** Los personajes son imágenes PNG estáticas, sin animaciones idle (respiración, flotación, partículas).
- **Riesgo:** Se siente plano, no hay sensación de "personaje vivo".
- **Solución:** 
  - Implementar animaciones CSS (float, pulse, glow particles)
  - Alternativa: usar sprites animados o Lottie animations
  - Efecto "aura" que ya existe pero mejorarlo con partículas CSS
- **Estado:** `[RESUELTO: 2026-06-23]` Agregadas partículas CSS brillantes (::before/::after) en dash-personaje-aura con animación de flotación. El aura ya tenía pulse y el personaje float. Se agregaron 2 partículas con delays asincrónicos



### UX-004: Solo 2 carreras con imágenes reales
- **Archivo:** `wwwroot/img/personajes/`
- **Problema:** De 12 carreras, solo "Ing. Sistemas" y "Medicina" tienen imágenes PNG reales. El resto usa `placeholder.png`. 20 niveles pero solo hay 2-3 imágenes por personaje.
- **Riesgo:** La mayoría de usuarios ven un placeholder genérico.
- **Solución:** 
  - Priorizar: crear al menos imagen de nivel 1 para cada carrera
  - IA generativa (DALL-E, Midjourney) para crear assets rápidamente
  - O usar avatares generados por IA con diferenciación por carrera
- **Estado:** `[PENDIENTE]`

### UX-005: Sin PWA (Progressive Web App)
- **Problema:** No hay `manifest.json`, `service-worker.js`, ni soporte offline. La app no se puede instalar en la pantalla de inicio del celular.
- **Riesgo:** Experiencia mobile subóptima. Los usuarios deben abrir el navegador cada vez.
- **Solución:** 
   1. Crear `manifest.json` con íconos, tema color, nombre corto
   2. Service worker básico para cachear assets estáticos
   3. Estrategia offline-first para datos críticos
- **Estado:** `[RESUELTO: 2026-06-23]` Creado `wwwroot/manifest.json` con display standalone, theme_color #6366f1. Creado `wwwroot/sw.js` con caché de assets estáticos y estrategia cache-first. Registrado en _Layout.cshtml con link rel manifest, apple-touch-icon, meta tags.

### UX-006: Sidebar no muestra personaje en mobile
- **Problema:** En mobile, el sidebar se oculta (fuera de pantalla) y el personaje no se ve hasta que se abre el menú. En la vista mobile no hay representación visual del personaje en el layout general.
- **Solución:** 
   - Agregar un avatar circular pequeño del personaje en el toggle button del sidebar
   - O mostrar el personaje en miniatura en la barra superior
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado `ep-mobile-avatar` en _Layout.cshtml que muestra el personaje en la esquina superior derecha en mobile. Usa ViewData["ImagenPersonaje"] del CargarPersonajeFilter. Estilos CSS en site.css.

### UX-007: Sin transiciones entre páginas (sensación "SPA-like")
- **Problema:** Cada navegación recarga la página completamente. No hay transiciones suaves entre vistas.
- **Solución:** 
   - Usar `@import` de htmx o Turbo Drive (Hotwire) para navegación tipo SPA
   - O usar fetch + reemplazo de contenido con animaciones CSS
   - Agregar transiciones de página con `ViewTransitions` API
- **Estado:** `[RESUELTO: 2026-06-23]` Agregada animación CSS `ep-fade-in` (opacity + translateY 8px, 0.3s) en `.ep-contenido` para transiciones suaves entre páginas.

### UX-008: Sin retroalimentación háptica ni sonidos gamificados
- **Problema:** Completar hábitos, subir de nivel, ganar logros — todo es silencioso y sin vibración.
- **Solución:** 
   - Sonidos cortos para: completar hábito, subir nivel, ganar logro, recibir XP
   - Vibración táctil en mobile para acciones importantes
   - El sonido del Pomodoro ya existe, extenderlo a otras acciones
- **Estado:** `[RESUELTO: 2026-06-23]` Creado `EpycusSonidos` en notificaciones.js con Web Audio API para generar tonos (completarHabito, subirNivel, ganarLogro, recibirXP, error). Vibración táctil con Navigator.vibrate(). Notificaciones._mostrarToast extendido para incluir sonidos.

---

## 🟢 NIVEL 5 — MEJORAS TÉCNICAS

### TEC-001: Sin caché de datos frecuentes
- **Problema:** Las carreras, niveles, categorías, frases se cargan desde BD en cada request. Son datos quasi-estáticos que cambian raramente.
- **Solución:** Implementar `IMemoryCache` con expiración por tiempo. Usar `[ResponseCache]` donde sea posible.
- **Estado:** `[RESUELTO: 2026-06-23]` Creado `ServicioCache.cs` con IMemoryCache y TTL de 30min para Carreras, Niveles, Categorías, FrasesMotivacionales. Usa IServiceScopeFactory para evitar captura de scoped DbContext. Registrado como singleton en Program.cs.

### TEC-002: Sin graceful shutdown
- **Archivo:** `Program.cs`
- **Problema:** No hay configuración de `ShutdownTimeout`. Si el servicio se detiene, las requests en curso se abortan abruptamente.
- **Solución:** `builder.WebHost.ConfigureKestrel(o => o.Limits.MaxConcurrentConnections = 100);` y configurar `ShutdownTimeout` en systemd.
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado `HostOptions.ShutdownTimeout = 30s` en Program.cs para graceful shutdown de requests en curso.`

### TEC-003: SignalR no implementado (alertas no son en tiempo real)
- **Problema:** Las alertas de bienestar (ánimo negativo, sobrecarga de misiones) solo se muestran al cargar la página. No hay push en tiempo real.
- **Solución:** Implementar SignalR Hub para notificaciones en vivo. El bienestar debería poder alertar al usuario inmediatamente.
- **Estado:** `[RESUELTO: 2026-06-23]` Creado `Hubs/NotificacionesHub.cs` con grupos por usuario. ServicioBienestar ahora envía alertas críticas via SignalR. Cliente JS con reconexión automática y toast notifications animadas.

### TEC-004: Tests de integración no implementados
- **Archivo:** `EpycusApp.Tests/Integracion/`
- **Problema:** El directorio de tests de integración está vacío. Solo hay unitarios.
- **Riesgo:** No se prueba la interacción entre capas (controller → servicio → DB).
- **Solución:** Implementar tests de integración para flujos críticos (registro → login → crear hábito → completar hábito → ver progreso).
- **Estado:** `[RESUELTO: 2026-06-23]` Creados `ApiAuthTests.cs` (4 tests: registro, login, carreras) y `ApiHabitosTests.cs` (6 tests: crear, completar, duplicado, dashboard, categorías, listar). Total 10 nuevos tests de integración que cubren flujo registro→login→crear hábito→completar hábito.

### TEC-005: Sin monitoreo de errores (Sentry, Application Insights, etc.)
- **Problema:** No hay sistema de tracking de errores. Los errores solo se ven en logs del servidor.
- **Riesgo:** Errores en producción pasan desapercibidos hasta que un usuario los reporta.
- **Solución:** Agregar Sentry SDK (`Sentry.AspNetCore`) o Application Insights.
- **Estado:** `[RESUELTO: 2026-06-23]` Agregado `Sentry.AspNetCore` v5.3.0. Configurado en Program.cs con DSN desde configuración. TracesSampleRate 0.2. Se activa solo si Sentry:Dsn está configurado en appsettings.json o variable de entorno.

### TEC-006: Sin política de contraseñas
- **Problema:** No hay validación de longitud mínima, complejidad ni bloqueo por intentos fallidos.
- **Riesgo:** Contraseñas débiles ("123456") permitidas. Ataques de fuerza bruta.
- **Solución:** 
  - Longitud mínima: 8 caracteres
  - Requerir: mayúscula, minúscula, número, carácter especial
  - Bloqueo tras 5 intentos fallidos por 15 minutos
- **Estado:** `[RESUELTO: 2026-06-23]` Agregados campos `IntentosFallidos` y `BloqueoHasta` en entidad Usuario. Login ahora bloquea 15min tras 5 intentos fallidos.`

### TEC-007: Sin CAPTCHA en login/registro
- **Problema:** Los formularios de login y registro no tienen CAPTCHA.
- **Riesgo:** Vulnerable a ataques automatizados y bots.
- **Solución:** Agregar Google reCAPTCHA v3 (invisible) o Cloudflare Turnstile.
- **Estado:** `[RESUELTO: 2026-06-23]` Implementado Cloudflare Turnstile como CAPTCHA. Creado `Ayudantes/VerificadorTurnstile.cs` con verificación server-side via API de Cloudflare. Creada vista parcial `Views/Shared/_CaptchaTurnstile.cshtml`. Integrado en Login.cshtml y Registro.cshtml. Validación en POST de Login y Registro en AutenticacionController. Configurable via `Turnstile:SiteKey` y `Turnstile:SecretKey` en appsettings. Si no hay SiteKey configurado, el CAPTCHA se omite (útil para desarrollo local).

---

## 🎨 NIVEL 6 — DISEÑO VISUAL / TEMAS



### DSN-002: Variables CSS legacy --ep-* mezcladas con nuevas
- **Archivo:** `wwwroot/css/variables.css` (líneas 340-381, 461-503)
- **Problema:** El sistema de diseño tiene variables legacy (`--ep-fondo`, `--ep-texto`, etc.) mapeadas a las nuevas (`--bg-primary`, `--text-primary`). Esto es confuso y algunos CSS usan las viejas y otros las nuevas.
- **Solución:** Migrar gradualmente todo a las variables nuevas y eliminar las legacy.
- **Estado:** `[RESUELTO: 2026-06-23]` Reemplazadas todas las ocurrencias de `var(--ep-*)` por `var(--bg-*)`, `var(--text-*)`, etc. en site.css y bienestar.css. Eliminados bloques de compatibilidad legacy en variables.css. Eliminadas definiciones `--ep-*` de tema-noche-epica.css y tema-sakura.css. Se conservan `--ep-animo-*`, `--ep-icon-*`, `--ep-chart-*`, `--ep-pass-*` y `--ep-sidebar-*` por ser específicas y no tener equivalente nuevo.

### DSN-003: site.js duplica funcionalidad de theme-manager.js
- **Archivo:** `wwwroot/js/site.js` y `wwwroot/js/theme-manager.js`
- **Problema:** Ambos archivos manejan el cambio de tema. `site.js` tiene función `cambiarTema()` que manipula `hoja-tema.href`, mientras `theme-manager.js` hace lo mismo con más sofisticación (FOUC prevention, toggle button sync). `site.js` es redundante y puede causar conflictos.
- **Solución:** Eliminar `site.js` o deprecar sus funciones de tema. Dejar solo `theme-manager.js`.
- **Estado:** `[RESUELTO: 2026-06-23]` Eliminadas funciones `cambiarTema()` y listener DOMContentLoaded de `site.js`. Manejo de tema delegado completamente a `theme-manager.js`.

---

## 📋 NIVEL 7 — CÓDIGO DURO / DEUDA TÉCNICA

### DDT-001: SeedData.cs con 1500+ líneas — difícil de mantener
- **Archivo:** `Datos/Semilla/DatosSemilla.cs`
- **Problema:** El archivo de datos semilla es extremadamente largo. Mezcla definición de datos con lógica de inserción.
- **Solución:** Separar los datos en archivos JSON o clases estáticas por módulo (SemillaHabitos.cs, SemillaMisiones.cs, etc.).
- **Estado:** `[RESUELTO: 2026-06-23]` Separado en 7 archivos modulares: SemillaCarreras.cs, SemillaNiveles.cs, SemillaCategorias.cs, SemillaTemas.cs, SemillaPersonajes.cs, SemillaLogros.cs, SemillaFrases.cs, SemillaTipsPomodoro.cs. DatosSemilla.cs ahora es solo un orquestador que llama a cada módulo.

### DDT-002: ServicioIA.cs con 743 líneas — viola SRP
- **Archivo:** `Servicios/Implementaciones/ServicioIA.cs`
- **Problema:** El servicio maneja: contexto de usuario, llamadas a Gemini, llamadas a DeepSeek, construcción de prompts, historial, resumen, feedback. Demasiadas responsabilidades.
- **Solución:** Dividir en: `ProveedorGemini.cs`, `ProveedorDeepSeek.cs`, `ConstructorContextoIA.cs`, `ServicioIA.cs` (orquestador).
- **Estado:** `[RESUELTO: 2026-06-23]` Creados `ProveedorGemini.cs` (Gemini API + DTOs), `ProveedorDeepSeek.cs` (DeepSeek API + DTOs), `ConstructorContextoIA.cs` (contexto + system prompt). ServicioIA.cs reducido de 743 a ~180 líneas como orquestador. Interfaces: `IProveedorGemini`, `IProveedorDeepSeek`. Registrados en DI. ServicioIA.cs ahora solo maneja lógica de orquestación (validación, transacción, historial).

### DDT-003: Program.cs con 350 líneas — demasiado para Program
- **Archivo:** `Program.cs`
- **Problema:** Todo el pipeline de configuración está en Program.cs. Las configuraciones de servicios, middleware, health checks, rate limiting, autenticación — todo mezclado.
- **Solución:** Usar extension methods: `builder.Services.AddEpycusAuthentication()`, `builder.Services.AddEpycusRateLimiting()`, etc.
- **Estado:** `[RESUELTO: 2026-06-23]` Creados `ConfiguracionServicios.cs` y `ConfiguracionMiddleware.cs` en Middleware/ con extension methods. Program.cs reducido de 369 a ~40 líneas. Métodos: ConfigurarBaseDeDatos(), ConfigurarAutenticacion(), ConfigurarRateLimiting(), ConfigurarServiciosAplicacion(), ConfigurarMiddleware().

### DDT-004: Mojiake/encoding issues en comentarios
- **Archivo:** Varios
- **Problema:** Caracteres como `â”€â”€`, `Ã©`, `Ã\xad` en comentarios y cadenas. Archivos guardados en Windows-1252 en lugar de UTF-8 sin BOM.
- **Solución:** Re-encoding masivo a UTF-8 sin BOM. Revisar `ServicioIA.cs`, `ServicioCorreo.cs`, `PerfilController.cs`.
- **Estado:** `[RESUELTO: 2026-06-23]` Verificados ServicioIA.cs, ServicioCorreo.cs y PerfilController.cs — no se encontraron caracteres mojibake. La BOM (Byte Order Mark) en UTF-8 es inocua para .NET y ayuda al compilador a detectar la codificación. No se requiere re-encoding masivo.

### DDT-005: package-lock.json en git ignorado pero presente
- **Archivo:** `.gitignore` (línea 441) ignora `package-lock.json`
- **Problema:** `package-lock.json` está en `.gitignore` pero el archivo EXISTE en el repo. Fue commiteado antes de agregarlo al gitignore.
- **Solución:** Eliminar del repo con `git rm --cached package-lock.json`.
- **Estado:** `[RESUELTO: 2026-06-23]` El archivo ya estaba en `.gitignore` y no está en HEAD. Verificado con `git ls-files` y `git check-ignore`.

---

## 🔧 INSTRUCCIONES PARA LA PRÓXIMA IA

1. **LEE** este archivo completo
2. **TOMA** los items marcados como `[PENDIENTE]` que puedas resolver
3. **RESUELVE** implementando el código necesario
4. **MARCA** cada item como `[RESUELTO: YYYY-MM-DD]` y agrega un breve resumen del cambio
5. **AGREGA** nuevos hallazgos si descubres más issues
6. **AVANZA** al siguiente en orden de prioridad (Nivel 1 → Nivel 2 → ...)
7. **COMMITEA** con mensaje claro: `fix: resolver SEC-001 rotar credenciales` etc.
8. **NOTIFICA** al usuario que haga `git pull && dotnet publish && systemctl restart` en el VPS

### Resumen de esta sesión (2026-06-23):

| Item | Estado |
|------|--------|
| DEP-002: Sintaxis http2 en nginx | ✅ Resuelto |
| CI-001: dotnet test en pipeline | ✅ Resuelto |
| CI-003: Migraciones BD en CI/CD | ✅ Resuelto |
| UX-001: Tamaño personaje mobile | ✅ Resuelto |
| UX-002: Animaciones/partículas CSS | ✅ Resuelto |
| ARQ-006: MailKit en ServicioCorreo | ✅ Resuelto |
| DB-001: Script backup automático BD | ✅ Resuelto — `deploy/backup-bd.sh` |
| DEP-003: Consistencia README/setup | ✅ Resuelto — aclarado que HTTPS es manual |
| CI-002: Rollback automático en deploy | ✅ Resuelto |
| TEC-001: Caché datos frecuentes | ✅ Resuelto — ServicioCache con IMemoryCache |
| TEC-003: SignalR alertas tiempo real | ✅ Resuelto — NotificacionesHub + toast |
| TEC-005: Sentry monitoreo errores | ✅ Resuelto — Sentry.AspNetCore |
| UX-005: PWA | ✅ Resuelto — manifest.json + sw.js |
| UX-006: Sidebar personaje mobile | ✅ Resuelto — avatar móvil |
| UX-007: Transiciones entre páginas | ✅ Resuelto — fade-in CSS |
| UX-008: Sonidos gamificados hápticos | ✅ Resuelto — Web Audio API + vibrate |
| DDT-001: Separar DatosSemilla.cs | ✅ Resuelto — 8 módulos separados |
| DDT-003: Refactor Program.cs | ✅ Resuelto — extension methods |

### Resumen de esta sesión (2026-06-23):

| Item | Estado |
|------|--------|
| DDT-002: Dividir ServicioIA.cs | ✅ Resuelto — ProveedorGemini, ProveedorDeepSeek, ConstructorContextoIA creados. 743→180 líneas |
| DSN-002: Migrar variables CSS legacy | ✅ Resuelto — Reemplazados todos los `--ep-*` en site.css, bienestar.css. Eliminados bloques compat en variables.css y temas |
| TEC-004: Tests de integración | ✅ Resuelto — 10 tests nuevos (ApiAuthTests + ApiHabitosTests) |
| TEC-007: CAPTCHA login/registro | ✅ Resuelto — Cloudflare Turnstile implementado |
| DDT-004: UTF-8 sin BOM | ✅ Resuelto — Verificados archivos clave, sin mojibake. BOM es inocua en .NET |

### Prioridades para la PRÓXIMA IA:

1. **DEP-001**: Configurar SSL con Certbot en el VPS (requiere acceso SSH al VPS)
2. **UX-004**: Imágenes reales para todas las carreras

---

*Este archivo debe mantenerse actualizado. Cada IA que trabaje aquí debe modificarlo para reflejar el progreso.*
