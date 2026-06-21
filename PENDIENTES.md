# PENDIENTES — Auditoría Pre-Producción EpycusApp

> Generado: 2026-06-15 | Última actualización: 2026-06-21 — **Todos los items ✅ han sido eliminados. Solo quedan pendientes ⚠️ y no planificados ❌.**

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


---

## 🟡 IMPORTANTES

| ID | Prioridad | Archivo | Problema | Riesgo | Estado | Solución |
|----|-----------|---------|----------|--------|--------|----------|


---

## 🟢 MEJORAS RECOMENDADAS

| ID | Prioridad | Archivo | Problema | Riesgo | Estado | Solución |
|----|-----------|---------|----------|--------|--------|----------|
| MEJ-014 | **Alta** | `Views/Login` + semilla admin | **Identificar credenciales de admin.** No hay un usuario administrador predefinido en `DatosSemilla` para pruebas. | **Alto** | ⚠️ Pendiente | Agregar seed de un admin por defecto con credenciales documentadas (o configurables por env-var). |
| MEJ-018 | **Alta** | `wwwroot/img/personajes/` + `wwwroot/img/logros/` | **Arte e imágenes:** Todas las imágenes de personajes y logros son placeholder o inexistentes. | **Alto** | ⚠️ Parcial | Logo (`logo.webp`), favicon (`favicon.ico`) e imagen de login (`login-hero.webp`) ya agregados con la marca Epycus. Personajes de Ing. Sistemas y Medicina tienen PNG reales. Faltan ilustraciones originales para el resto de carreras y logros. |

---

## 🛡️ SEGURIDAD

| ID | Estado | Descripción |
|----|--------|-------------|
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
| BD-007 | ⚠️ Pendiente | Definir y automatizar backup periódico de la BD (cron + mysqldump a bucket/almacenamiento externo) |
| BD-008 | ⚠️ Pendiente | Agregar política de reintentos y pool de conexiones en `Program.cs` (`MaxRetryCount`, `EnableRetryOnFailure`) |

---

## 🔄 CI/CD

| ID | Estado | Descripción |
|----|--------|-------------|
| CI-004 | ❌ No planificado | Tests unitarios — el usuario decidió no implementarlos |
| CI-006 | ⚠️ Pendiente | Agregar rollback automático: si el deploy falla (health check post-deploy), restaurar backup automáticamente |
| CI-007 | ⚠️ Pendiente | Agregar migraciones de BD al pipeline CI/CD (`dotnet ef database update` antes de iniciar la app) |
| CI-008 | ⚠️ Pendiente | Health check actual solo prueba BD, disco y Gemini — no verifica que el pipeline MVC funcione (controllers, razor, auth) |

---

## 🖥️ VPS

*(Todos los items VPS-001 a VPS-010 fueron corregidos. No hay pendientes.)*

---

## 🎨 UI/UX — Pendientes

| ID | Prioridad | Área | Problema | Estado |
|----|-----------|------|----------|--------|
| UX-006 | **Alta** | Imágenes | Personajes, logros e iconos son placeholder — sin arte original | ⚠️ Parcial |

---

## 📋 DEUDA TÉCNICA

| ID | Descripción | Esfuerzo | Estado |
|----|-------------|----------|--------|
| DEV-003 | Agregar proyecto de tests unitarios | 2-3 días | ❌ No planificado |
| DEV-006 | Dockerizar la aplicación | 1 día | ❌ No necesario (deploy directo a VPS) |
| DEV-009 | Agregar sistema de monitoreo/error tracking (Sentry, OpenTelemetry, etc.) | 1 día | ⚠️ Pendiente |
| DEV-010 | Implementar caché (Redis o MemoryCache) para datos frecuentes (carreras, niveles, frases) | 1 día | ⚠️ Pendiente |
| DEV-011 | Agregar tests de integración para los flujos críticos (registro, login, hábitos, pomodoro) | 3-4 días | ⚠️ Pendiente |
| DEV-012 | Agregar meta tags SEO, sitemap.xml y robots.txt | 4 horas | ⚠️ Pendiente |
| DEV-013 | Agregar banner de consentimiento de cookies (GDPR) | 4 horas | ⚠️ Pendiente |
| DEV-014 | Versionar la API (ej: `/api/v1/`, `/api/v2/`) para no romper clientes existentes | 1 día | ⚠️ Pendiente |
| DEV-015 | Agregar `apple-touch-icon` y `manifest.json` personalizados de la marca Epycus | 2 horas | ⚠️ Pendiente |
| DEV-016 | Revisar y actualizar dependencias NuGet a versiones recientes (seguridad y compatibilidad) | 2 horas | ⚠️ Pendiente |
| DEV-017 | Agregar graceful shutdown en `Program.cs` | 2 horas | ⚠️ Pendiente |

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
| MOB-011 | **Media** | App | Diseño UI/UX móvil nativo (Material Design 3 o Human Interface Guidelines) — no una copia de la web | ⚠️ Pendiente |
| MOB-012 | **Media** | Play Store | Crear cuenta de desarrollador en Google Play ($25 única vez) | ⚠️ Pendiente |
| MOB-013 | **Baja** | Play Store | Preparar assets: ícono, screenshots, descripción, política de privacidad para la ficha de Play Store | ⚠️ Pendiente |
| MOB-014 | **Baja** | Play Store | Configurar CI/CD para build y publish automático a Play Store (GitHub Actions + Fastlane) | ⚠️ Pendiente |
| MOB-015 | **Baja** | Play Store | Publicar versión beta cerrada (Closed Testing) con usuarios de prueba antes del release público | ⚠️ Pendiente |

---

## 🌱 ODS 3 — BIENESTAR

### Pendientes

| ID | Prioridad | Estado | Descripción |
|----|-----------|--------|-------------|
| B-FALTA-02 | **Alta** | ⚠️ Pendiente | SignalR/WebSocket para alertas en tiempo real (actualmente se muestran al cargar página) |
| B-FALTA-03 | **Alta** | ⚠️ Parcial | `RecomendacionPausaActiva` no considera historial de ánimo ni IA |
| B-FALTA-06 | **Baja** | ⚠️ Pendiente | Analytics/gráficos Chart.js no implementados |
| B-INC-06 | **Media** | ⚠️ Pendiente | Solo 5 estados de ánimo se mantienen. Requiere análisis de impacto antes de expandir |
| B-ODS3-04 | **Media** | ⚠️ Pendiente | Tendencias semanales/mensuales con Chart.js |
| B-ODS3-06 | **Media** | ⚠️ Pendiente | Análisis de sentimiento con IA (Gemini) en entradas del diario |
| B-ODS3-07 | **Media** | ⚠️ Parcial | Racha de días visible en UI. Pendiente integrar con sistema de logros |
| B-ODS3-08 | **Media** | ⚠️ Pendiente | Exportar diario personal (JSON, CSV o PDF) |
| B-ODS3-09 | **Baja** | ⚠️ Pendiente | Recordatorio diario push/email a las 20:00 |
| B-ODS3-10 | **Baja** | ⚠️ Pendiente | Widget en Dashboard con resumen del último registro |

---

## 🤖 MÓDULO IA — EDY (Gemini)

### 🔴 CRÍTICOS

| ID | Prioridad | Archivo | Problema | Solución propuesta |
|----|-----------|---------|----------|--------------------|
| IA-CRIT-01 | **Muy Alta** | `Program.cs`, `IaController.cs` | Rate limiter "Gemini" (20/min) NUNCA aplicado. Sin `[EnableRateLimiting("Gemini")]` en el controller | Agregar `[EnableRateLimiting("Gemini")]` en `IaController` |
| IA-CRIT-02 | **Muy Alta** | `IaController.cs:50` | Endpoint `/api/ia/chat` sin `[ValidateAntiForgeryToken]`. JS fetch no envía token CSRF | Agregar `[ValidateAntiForgeryToken]` y enviar token CSRF desde JS |
| IA-CRIT-03 | **Alta** | `ServicioIA.cs` | Todo el archivo tiene mojibake (Windows-1252 en lugar de UTF-8) | Re-encoding a UTF-8 sin BOM |
| IA-CRIT-04 | **Alta** | `Views/Ia/Index.cshtml` | Sin límite de longitud server-side en el mensaje | Agregar validación server-side de longitud máxima |

### 🟡 IMPORTANTES

| ID | Prioridad | Archivo | Problema | Solución propuesta |
|----|-----------|---------|----------|--------------------|
| IA-IMP-01 | **Alta** | `ia.css` | Avatares EDY con gradiente púrpura fijo — no se adaptan a tema claro | Usar `var(--accent-primary)` y `var(--accent-secondary)` |
| IA-IMP-02 | **Alta** | `Views/Ia/Index.cshtml` | Sugerencias de bienvenida estáticas y hardcodeadas | Generar desde servidor basado en datos del usuario |
| IA-IMP-03 | **Alta** | `IaController.cs`, `Views/Ia/Index.cshtml` | No hay lista/selector de conversaciones pasadas | Agregar endpoint `GET /ia/conversaciones` y sidebar/historial |
| IA-IMP-04 | **Alta** | `ServicioIA.cs` | System prompt carece de "banderas de bienestar" explícitas | Agregar sección con alertas detectadas en el system prompt |
| IA-IMP-05 | **Media** | `ServicioIA.cs` | Límite de 20 mensajes de historial sin resumen automático | Implementar resumen automático al alcanzar 15 mensajes |
| IA-IMP-06 | **Media** | `GeminiHealthCheck.cs` | No usa `IHttpClientFactory.CreateClient("Gemini")` | **⚠️ Parcial:** Lee modelo de configuración. Pendiente usar `CreateClient("Gemini")` |
| IA-IMP-07 | **Media** | `ServicioIA.cs` | Guarda mensaje en DB antes de llamar a Gemini — mensajes huérfanos si falla | Envolver en transacción con rollback |
| IA-IMP-08 | **Media** | `IaController.cs` | Action `Nueva()` usa POST sin formulario que envíe datos | Cambiar a `[HttpGet]` o mejorar flujo |
| IA-IMP-09 | **Baja** | `Views/Ia/Index.cshtml` | "En línea" siempre verde sin verificación real | JS que llame a `/health` periódicamente |
| IA-IMP-10 | **Baja** | `MensajeIA.cs` | Sin fecha de último acceso a la conversación | Agregar entidad `ConversacionIA` con metadatos |

### 🟢 MEJORAS RECOMENDADAS

| ID | Prioridad | Archivo | Problema | Solución propuesta |
|----|-----------|---------|----------|--------------------|
| IA-MEJ-01 | **Media** | `ServicioIA.cs` | Sin gamificación del chat (XP, logros, rachas) | Agregar XP por mensaje usando `IServicioGamificacion` |
| IA-MEJ-02 | **Media** | `IaController.cs`, `ServicioIA.cs` | Sin análisis de sentimiento en mensajes del usuario | Detectar tono y ajustar respuesta; detectar crisis (línea 113) |
| IA-MEJ-03 | **Media** | `Views/Ia/Index.cshtml` | Sin integración con bienestar (EDY no puede crear alertas) | Acciones post-chat para registrar ánimo o pausa activa |
| IA-MEJ-04 | **Baja** | `Views/Ia/Index.cshtml` | Sin botón de feedback en respuestas (👍/👎) | Agregar botones y persistir feedback en DB |
| IA-MEJ-05 | **Baja** | `ServicioIA.cs` | Sin soporte para streaming de respuesta (SSE/WebSocket) | Implementar streaming de tokens Gemini al cliente |
| IA-MEJ-06 | **Baja** | `Views/Ia/Index.cshtml` | Sin búsqueda en conversaciones | Agregar campo de búsqueda con endpoint dedicado |
| IA-MEJ-07 | **Baja** | `Views/Ia/Index.cshtml`, `ia.css` | Sin soporte completo de markdown (listas, código, links, tablas) | Ampliar sanitización de markdown |
| IA-MEJ-08 | **Baja** | `ServicioIA.cs` | Sin paginación en `ObtenerHistorialAsync` | Agregar `Take(50)` con offset |
| IA-MEJ-09 | **Baja** | `appsettings.json` | Sin límite de costos diarios de Gemini API | Agregar `Gemini:MaxTokensPorDia` y contador de uso |

---

## 🏗️ ARQUITECTURA — Pendientes

### 🔴 CRÍTICOS

| ID | Prioridad | Archivo | Problema | Solución |
|----|-----------|---------|----------|----------|
| ARQ-003 | **Muy Alta** | `Program.cs`, `IaController.cs` | Rate limiter "Gemini" (20/min) NUNCA aplicado | Agregar `[EnableRateLimiting("Gemini")]` en IaController |
| ARQ-006 | **Alta** | `IaController.cs:50` | Endpoint `/api/ia/chat` sin `[ValidateAntiForgeryToken]` | Agregar antiforgery + header `X-CSRF-TOKEN` |
| ARQ-007 | **Alta** | `Program.cs` | Middleware pipeline fuera de orden según docs Microsoft | Reordenar pipeline |

### 🟡 IMPORTANTES

| ID | Prioridad | Archivo | Problema | Solución |
|----|-----------|---------|----------|----------|
| ARQ-008 | **Alta** | `HabitosController.cs` | Lógica de negocio en controller (parseo manual de `Request.Form`) | Mover a servicio o Custom Model Binder |
| ARQ-009 | **Alta** | `DTOs/` vs `Models/DTOs/` | Dos directorios DTOs inconsistentes | Unificar todo en `DTOs/` |
| ARQ-010 | **Alta** | `ViewModels/` | ViewModels referencian directamente entidades del modelo | Crear DTOs específicos para vistas |
| ARQ-011 | **Media** | `ServicioIA.cs` | Guarda mensaje en DB antes de llamar a Gemini | Envolver en transacción |
| ARQ-012 | **Media** | `ServicioIA.cs`, `GeminiHealthCheck.cs` | Inconsistencia de HttpClient | Unificar uso de `CreateClient("Gemini")` |
| ARQ-013 | **Media** | `DatosSemilla.cs` | Seed data usa `Debug.WriteLine` en lugar de `ILogger` | Inyectar `ILogger<DatosSemilla>` |
| ARQ-014 | **Media** | Varios controladores | Nested DTO classes definidas dentro de archivos de controller | Mover a `DTOs/` como archivos separados |

### 🟢 MEJORAS RECOMENDADAS

| ID | Prioridad | Archivo | Problema | Solución |
|----|-----------|---------|----------|----------|
| ARQ-015 | **Media** | Toda la app | Sin capa de caché para datos quasi-estáticos | Implementar `IMemoryCache` o `IDistributedCache` |
| ARQ-016 | **Baja** | `Program.cs` | Sin graceful shutdown | Configurar `ShutdownTimeout` |
| ARQ-017 | **Baja** | `Suscripcion.cs` | `PrecioSoles` sin precisión explícita de columna | Agregar `[Column(TypeName = "decimal(10,2)")]` |

---

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

## Leyenda

- ⚠️ Pendiente / Requiere acción
- ❌ No resuelto / No planificado
