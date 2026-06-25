# Auditoría Integral End-to-End — EpycusApp

## Credenciales y Accesos

> ⚠️ **CRÍTICO**: Este documento está versionado en git. Las credenciales reales NO deben estar aquí.
> Usa `credenciales.md` (en `.gitignore`) para almacenarlas localmente.

| Recurso | Detalle |
|---|---|
| **URL producción web** | `https://app.epycus.es` |
| **App Android (Play Store)** | `es.epycus.app` |
| **SSH VPS** | `ssh -p 2222 root@147.93.119.193` (usar `credenciales.md` para password) |
| **Comandos útiles en VPS** | `journalctl -u epycus-web --no-pager -n 100` · `tail -50 /var/log/nginx/error.log` |
| **Repositorio web** | `https://github.com/Chester0802/EpycusApp.git` (rama `main`) |
| **Repositorio móvil** | `C:\Users\marco\Pictures\Epycus` (local) |
| **Deploy web** | `cd /tmp/epycus-build && git pull && dotnet publish -c Release -o /var/www/epycus-web && systemctl restart epycus-web` |
| **Build Android** | `cd C:\Users\marco\Pictures\Epycus && ./gradlew assembleRelease` |
| **Nginx** | Config en `/etc/nginx/sites-enabled/epycus-web` |
| **Systemd** | `/etc/systemd/system/epycus-web.service` |
| **Google Client ID** | `[VER credenciales.md]` |
| **Google OAuth redirect** | Web: `https://app.epycus.es/signin-google` |

---

## 1. Agente de Funcionamiento (Backend + API)

### Alcance
Probar **todos los endpoints** de la API REST y SignalR, tanto éxito como casos borde (errores, falta de auth, datos inválidos). Verificar que no haya excepciones no controladas, fugas de memoria, timeouts, ni errores de serialización JSON.

### Endpoints a probar

> ⚠️ **Nota importante sobre la arquitectura**:  
> La API REST está **unificada** bajo el prefijo `/api/v1/`. NO existe una versión `/api/` sin versionado.  
> La **web** (`app.epycus.es`) usa controladores **MVC con Razor** para las vistas y envía formularios con anti-forgery token.  
> El **JavaScript del frontend web** consume los mismos endpoints `/api/v1/...` mediante AJAX/fetch que la app móvil.  
> Solo hay 3 endpoints AJAX excepcionales montados en controladores MVC (no en ApiControllers):  
> `POST /api/perfil/tema` (PerfilController), `POST /api/ia/chat`, `POST /api/ia/feedback`, `POST /api/ia/registrar-animo` (IaController).

#### API REST — Endpoints compartidos (web JS + móvil Android)

**Pomodoro:**
- `POST /api/v1/pomodoro/iniciar` — iniciar sesión focus
- `POST /api/v1/pomodoro/{sesionId}/ciclo-completado` — completar ciclo por ID de sesión
- `POST /api/v1/pomodoro/{sesionId}/finalizar` — finalizar sesión (con bonus XP)
- `POST /api/v1/pomodoro/{sesionId}/cancelar` — cancelar sesión
- `POST /api/v1/pomodoro/descanso` — registrar descanso (corto/largo)
- `GET /api/v1/pomodoro/configuracion` — obtener configuración
- `PUT /api/v1/pomodoro/configuracion` — actualizar configuración
- `GET /api/v1/pomodoro/sesion-activa` — obtener sesión activa previa
- `GET /api/v1/pomodoro/historial?desde=&hasta=&pagina=&tamano=` — historial paginado
- `GET /api/v1/pomodoro/racha` — racha actual
- `GET /api/v1/pomodoro/tip-aleatorio` — tip motivacional
- `GET /api/v1/pomodoro/estadisticas?desde=&hasta=` — estadísticas por rango
- `GET /api/v1/pomodoro/estadisticas-semanales` — estadísticas semanales
- `GET /api/v1/pomodoro/estadisticas-avanzadas?desde=&hasta=` — estadísticas avanzadas
- `GET /api/v1/pomodoro/mision/{misionId}/sub-tareas` — sub-tareas para enfoque

> ❌ **Endpoints documentados que NO existen en el código**: `saltar`, `completar-descanso`, `historial-descansos`.  
> El descanso se registra vía `POST /api/v1/pomodoro/descanso` y se completa implícitamente al iniciar un nuevo ciclo.

**Auth:**
- `POST /api/v1/auth/login` — login (devuelve JWT + refresh token)
- `POST /api/v1/auth/refresh` — renovar JWT
- `POST /api/v1/auth/logout` — cerrar sesión
- `POST /api/v1/auth/registro` — registro
- `GET /api/v1/auth/verificar-correo?token=` — verificar email
- `POST /api/v1/auth/recuperar-contrasena` — recuperar contraseña
- `POST /api/v1/auth/restablecer-contrasena` — restablecer contraseña
- `POST /api/v1/auth/google` — Google Sign-In (móvil)
- `POST /api/v1/auth/completar-registro-google` — completar registro Google
- `GET /api/v1/auth/carreras` — lista carreras

> ℹ️ **Web**: login y registro se hacen mediante formularios MVC (`POST /Autenticacion/Login`, `POST /Autenticacion/Registro`) con anti-forgery token y Turnstile. Google OAuth web usa `GET /Autenticacion/IniciarSesionGoogle` → callback → cookie JWT.

**Hábitos:**
- `GET /api/v1/habitos` — lista hábitos con estado hoy
- `GET /api/v1/habitos/hoy` — hábitos de hoy
- `GET /api/v1/habitos/{id}` — hábito por ID
- `GET /api/v1/habitos/{id}/semana` — registros semanales de un hábito
- `POST /api/v1/habitos/{id}/completar` — completar hábito
- `POST /api/v1/habitos/{id}/fallar` — fallar hábito
- `POST /api/v1/habitos` — crear hábito
- `PUT /api/v1/habitos/{id}` — actualizar hábito
- `DELETE /api/v1/habitos/{id}` — eliminar hábito
- `GET /api/v1/habitos/categorias` — categorías

> ℹ️ **Web**: CRUD de hábitos también mediante formularios MVC (`/Habitos/Crear`, `/Habitos/Editar/{id}`, etc.) con anti-forgery.

**Diario:**
- `GET /api/v1/diario/hoy` — entrada de hoy
- `GET /api/v1/diario/fecha?fecha=yyyy-MM-dd` — entrada por fecha
- `GET /api/v1/diario/mes?anio=&mes=` — entradas del mes
- `POST /api/v1/diario` — crear entrada
- `PUT /api/v1/diario/{fecha}` — actualizar entrada
- `GET /api/v1/diario/racha` — días consecutivos
- `GET /api/v1/diario/promedio-mes?anio=&mes=` — promedio de ánimo mensual
- `GET /api/v1/diario/pregunta-guia` — pregunta guía diaria

> ℹ️ **Web**: diario también vía MVC (`/DiarioAnimo/Registrar`) con anti-forgery.

**Perfil/Progreso:**
- `GET /api/v1/perfil` — obtener perfil completo
- `PUT /api/v1/perfil` — actualizar perfil
- `PUT /api/v1/perfil/cambiar-contrasena` — cambiar contraseña
- `PUT /api/v1/perfil/personaje` — cambiar personaje
- `PUT /api/v1/perfil/tema` — cambiar tema (disponible en API v1 y también como `POST /api/perfil/tema`)
- `GET /api/v1/perfil/personajes` — lista personajes
- `GET /api/v1/perfil/logros` — logros del usuario
- `GET /api/v1/progreso` — progreso (XP, nivel, % avance)
- `GET /api/v1/progreso/logros` — logros de progreso
- `GET /api/v1/progreso/historial-animo` — historial de ánimo
- `GET /api/v1/dashboard/resumen` — dashboard resumen (kpis, hábitos pendientes, frase)
- `GET /api/v1/dashboard/frase-del-dia` — frase del día
- `GET /api/v1/gamificacion/mi-progreso` — progreso gamificación
- `GET /api/v1/gamificacion/logros` — logros de gamificación

**IA:**
- `POST /api/v1/ia/chat` — chat con Edy (AI) — también disponible como `POST /api/ia/chat` (IaController MVC)
- `POST /api/v1/ia/feedback` — feedback sobre respuesta
- `GET /api/v1/ia/historial` — historial de conversaciones
- `GET /api/v1/ia/conversaciones` — lista de conversaciones
- `GET /api/v1/ia/sugerencias` — sugerencias personalizadas
- `GET /api/v1/ia/contexto-bienestar` — contexto de bienestar para IA
- `GET /api/v1/ia/mensajes-hoy` — mensajes del día

**Misiones (compartidas web+móvil):**
- `GET /api/v1/misiones` — lista misiones
- `GET /api/v1/misiones/{id}` — misión por ID
- `POST /api/v1/misiones` — crear misión
- `PUT /api/v1/misiones/{id}` — actualizar misión
- `DELETE /api/v1/misiones/{id}` — eliminar misión
- `POST /api/v1/misiones/{id}/completar` — completar misión
- `POST /api/v1/misiones/{id}/estado` — cambiar estado
- `GET /api/v1/misiones/categorias` — categorías
- `GET/POST/PUT/DELETE /api/v1/misiones/{misionId}/sub-tareas[/{id}]` — CRUD sub-tareas
- `POST /api/v1/misiones/{misionId}/sub-tareas/{id}/completar` — completar sub-tarea
- `POST /api/v1/misiones/{misionId}/sub-tareas/{id}/descompletar` — descompletar sub-tarea

**Bienestar:**
- `GET /api/v1/bienestar/resumen` — resumen bienestar
- `GET /api/v1/bienestar/alertas` — alertas activas
- `GET /api/v1/bienestar/frase` — frase motivacional
- `GET /api/v1/bienestar/estado-hoy` — estado de ánimo hoy
- `GET /api/v1/bienestar/historial-animo` — historial ánimo
- `GET /api/v1/bienestar/habitos-pendientes` — hábitos pendientes
- `GET /api/v1/bienestar/misiones-pendientes` — misiones pendientes
- `POST /api/v1/bienestar/pausa-activa` — registrar pausa activa

**Admin:**
- `POST /api/v1/admin/login` — login admin
- `GET /api/v1/admin/usuarios` — lista usuarios
- `GET /api/v1/admin/usuarios/{id}` — detalle usuario
- `POST /api/v1/admin/usuarios/{id}/suscripcion/activar` — activar suscripción
- `POST /api/v1/admin/usuarios/{id}/suscripcion/desactivar` — desactivar suscripción
- `GET /api/v1/admin/frases` — frases motivacionales
- `POST /api/v1/admin/frases` — crear frase
- `DELETE /api/v1/admin/frases/{id}` — eliminar frase

**SignalR:**
- `/hub/notificaciones` — conexión WebSocket, negotiate, grupos por usuario

**Health:**
- `GET /health` — health checks (MySQL, Gemini, DeepSeek, disco, MVC pipeline)

### Verificaciones
- [ ] **Errores 500** en logs del VPS: `journalctl -u epycus-web --no-pager -p err --since 24h`
- [x] **JSON cycle errors** — fix aplicado en `ReferenceHandler.IgnoreCycles` (`Program.cs:45`) ✅
- [ ] **Errores SignalR** en nginx: `tail -50 /var/log/nginx/error.log | grep 'hub'`
- [ ] **Latencia** de cada endpoint (<500ms en p99)
- [x] **Autenticación web** — rutas protegidas usan `[Authorize]` correctamente ✅
- [x] **Autenticación API/móvil** — endpoints usan `[Authorize]`/`[AllowAnonymous]` según corresponda ✅
- [x] **Anti-CSRF web** — `AutoValidateAntiforgeryTokenAttribute` global en MVC ✅
- [ ] **Login Google OAuth** — flujo completo y errores "Correlation failed"
- [x] **Rate limiting** — 6 políticas configuradas + Global limiter ✅
- [ ] **Consistencia web↔móvil** — el mismo endpoint `/api/v1/pomodoro/iniciar` debe devolver mismo schema desde JS web y app Android
- [x] **JWT** — cookie HttpOnly + Bearer token soportados ✅
- [x] **Sin rutas duplicadas** — confirmado: NO existen `/api/` y `/api/v1/` para la misma ruta (excepto 4 endpoints legacy MVC correctamente documentados) ✅

### Guiones de uso real (simular con curl/Postman o script)
1. Usuario se registra → login → inicia Pomodoro → completa 4 ciclos con descansos → consulta estadísticas semanales
2. Usuario inicia Pomodoro → fuerza error de red (matar proceso) → verificar que al reconectar no quede timer negativo
3. Usuario abre WebSocket → recibe notificación de hábito pendiente → marca hábito como completo
4. **[Cross-platform]** Usuario inicia Pomodoro en web → deja timer corriendo → abre app móvil → llama a `GET /api/v1/pomodoro/sesion-activa` → verificar que devuelve la sesión activa correcta
5. **[Cross-platform]** Usuario completa 3 ciclos en móvil → abre web → verificar que `GET /api/v1/pomodoro/estadisticas-semanales` refleja los 3 ciclos
6. **[Cross-platform]** Usuario configura meta diaria = 6 en móvil (`PUT /api/v1/pomodoro/configuracion`) → abre web → verificar que la meta aparece como 6

### Errores actuales conocidos
- "Correlation failed" en OAuth (intermitente, acceptable si es <1% de intentos)
- "no live upstreams" en nginx solo durante reinicio del servicio (esperado)

### Mejoras propuestas
- Cachear respuestas de estadísticas (Redis o memory cache)
- Implementar health check endpoint (`GET /health`)
- Agregar structured logging (Serilog con sink a archivo)
- Timeout configurable en SignalR
- Retry policy con Polly para llamadas a DB

---

## 1b. Verificación Fase 2 — Agente 1 (Backend + API)

### ✅ Correcciones verificadas correctamente

| Corrección | Estado | Evidencia |
|---|---|---|
| XSS en `@Html.Raw()` chat IA — fix de `HtmlEncode` | ✅ **VERIFICADO** | `FormatearMensaje()` en `Views/Ia/Index.cshtml:174` aplica `System.Net.WebUtility.HtmlEncode(texto)` antes del formateo markdown |
| Credenciales sanitizadas en `PROMT_AUDITORIA.md` | ✅ **VERIFICADO** | Placeholders reemplazan valores reales (SSH, Google Client ID, DB) |
| `appsettings.json` en `.gitignore` | ✅ **VERIFICADO** | Confirmado (commit `b96eeb9`) — no se suben secretos nuevos |
| JSON cycle fix | ✅ **VERIFICADO** | `ReferenceHandler.IgnoreCycles` en `Program.cs:45` |

### ⚠️ Errores confirmados aún sin corregir

| Error | Severidad | Archivo/Línea | Detalle |
|---|---|---|---|
| `ApiAdminController` sin verificación de rol | **CRÍTICA** ✅ CORREGIDO | `ApiAdminController.cs:12` | Cambiado a `[Authorize(Roles = "Admin")]` + método `EsAdministrador()` agregado |
| Admin login no distingue administradores | **ALTA** ✅ CORREGIDO | `ApiAdminController.cs:34-35` | Agregada verificación `_servicioAdmin.EsAdministrador()` en login |
| `SuppressModelStateInvalidFilter = true` | **MEDIA** | `Program.cs:35` | Desactiva validación automática, no todos los endpoints verifican `ModelState.IsValid` |
| `AddApplicationPart` redundante | **BAJA** | `Program.cs:42` | Assembly actual ya se escanea por defecto |
| **Documento**: "3 endpoints" en línea 36 (debería ser 4) | **BAJA** | `PROMT_AUDITORIA.md:36` | Dice 3 pero lista 4; se reintrodujo tras corrección inicial |

---

## 2. Agente de Aspecto Visual (Frontend + CSS + UX)

### Alcance
Navegar todas las páginas como usuario real. Verificar que el CSS cargue correctamente, que no haya modales transparentes, que los temas (noche épica / sakura) funcionen, accesibilidad, responsive, y que el service worker no rompa la navegación.

### Páginas a auditar
- `/Home/Index` (landing / login)
- `/Pomodoro` (pomodoro con timer, indicador de sesión, botones)
- `/Progreso` (vista de progreso con gráficos)
- `/Habitos` (gestión de hábitos)
- `/Diario` (entradas de diario)
- `/Perfil` (configuración de usuario, cambio de tema)
- `/Login` con Google OAuth

### Verificaciones (Web)
- [ ] **Consola del navegador** — sin errores JS (abrir DevTools > Console)
- [ ] **Service Worker** — en DevTools > Application > Service Workers, verificar estado "activated and is running"
- [ ] **Cache de SW** — DevTools > Application > Cache Storage, verificar que almacena respuestas correctas
- [ ] **Modales y popups** — sin `aria-hidden` incorrecto, focus management correcto
- [x] **Variables CSS** — `--ep-superficie`, `--ep-superficie-2`, `--bg-elevated` definidas en todos los temas ✅
- [ ] **Responsive** — probar en 360px, 768px, 1920px (Chrome DevTools > toggle device toolbar)
- [ ] **Contraste** — verificar textos sobre fondos (especialmente en tema sakura). ⚠️ Error #3 detectado
- [ ] **Transiciones** — cambios de estado (focus→break, break→focus) deben ser fluidos, sin saltos
- [ ] **Tiempo de carga** — <3s en 3G simulado
- [x] **Tema oscuro/claro** — cambiar en perfil, verificar que persiste tras recargar ✅ (`localStorage`, `theme-manager.js` sincrónico en `<head>`)
- [x] **Indicador "Sesión X / Y"** — visible, con texto "Sesión" (no "Ciclo") ✅
- [ ] **Gráfico semanal** — días en español ("lun", "mar", "mié"...), barras con altura correcta
- [ ] **Historial de descansos** — duración correcta (no 0 min)
- [ ] **Meta diaria** — muestra el número configurado (no hardcoded 1)
- [ ] **PWA** — manifest.json válido, iconos, splash screen. ⚠️ Error #2 (faltan iconos 192x192 y 512x512)

### Verificaciones (App Android)
- [ ] **Tema MD3** — Material Design 3 consistente en todas las pantallas
- [ ] **Barra inferior** — 5 tabs (Inicio, Hábitos, Misiones, Diario, Perfil) con iconos correctos y tintado
- [ ] **Pomodoro overlay** — al abrir desde cualquier tab, timer visible, botones play/pausa, indicador de ciclo
- [ ] **Avatar** — imagen de personaje visible (no fondo morado) en Perfil y tarjeta de Inicio
- [ ] **Modo oscuro/claro** — cambiar en Perfil, verificar que persiste al rotar/reabrir
- [ ] **Rotación** — Pomodoro mantiene estado del timer al girar el dispositivo
- [ ] **Edge-to-edge** — contenido detrás de barra de estado y navegación correctamente
- [ ] **Icono adaptativo** — en lanzador, forma correcta (redondeada/cuadrada según OEM)
- [ ] **Splash screen** — API SplashScreen de Android 12+ con logo y fondo correctos
- [ ] **Google Sign-In** — botón con estilo Google, flujo completo, sin errores de Client ID
- [ ] **Diario > selector de ánimo** — 5 estados de ánimo con iconos y colores correctos
- [ ] **Misiones > colores de prioridad** — rojo/naranja/verde según prioridad alta/media/baja
- [ ] **Hábitos > swipe** — deslizar para completar/fallar hábito funciona sin glitches
- [ ] **Chat Edy (IA)** — icono robot en lugar de letra "E", burbujas de mensaje bien alineadas
- [ ] **Perfil > selección personaje** — Kai/Ares/Luna muestran el personaje correcto (no siempre Luna)
- [ ] **Perfil > cambio carrera** — persiste al cerrar y reabrir (verificar que SharedPrefs se actualiza)
- [ ] **Pantallas grandes** — tablets (≥600dp ancho) no estiran elementos, usan layout alternativo si existe
- [ ] **TalkBack** — todos los elementos interactivos tienen contentDescription

### Errores actuales conocidos
- sw.js a veces cachea respuestas de error (parcialmente fixeado con try-catch y network-first para CSS/JS)
- **NUEVO**: 12 errores identificados en auditoría de código (ver tabla abajo), 0 corregidos hasta ahora
- **NUEVO**: Estilos de sidebar duplicados entre site.css y epycus-modern.css (conflicto de width: 260px vs 280px)

### Mejoras propuestas
- ~~Skeleton loaders mientras carga data~~ ✅ Ya implementados con clases `.ep-skeleton`, `.ep-skeleton-text`, `.ep-skeleton-card`, etc.
- ~~Añadir animaciones suaves en transiciones~~ ✅ Ya implementadas (`ep-fade-in`, `ep-count-up`, `ep-toast-in`, ripple effect, hover cards)
- ~~Focus-visible~~ ✅ Implementado en todos los elementos interactivos
- ~~`prefers-reduced-motion`~~ ✅ Implementado
- Modo "quitar accesibilidad"/"alto contraste" toggle
- Implementar virtual scrolling en historial de sesiones (si hay muchas)
- Notificaciones push reales (Web Push API) en lugar de solo SignalR
- Offline mode completo con cached shell architecture
- Consolidar estilos de sidebar en un único archivo (eliminar duplicados)
- Eliminar redundancia de `ep-fade-in` y unificar animaciones
- Decidir fuente de verdad para tema oscuro (`variables.css` vs `tema-noche-epica.css`)

---

## 2b. Agente de Aspecto Visual — Resultados de Auditoría

### ✅ Funcionando correctamente

- **Sistema de variables CSS** completo y bien estructurado. `--ep-superficie`, `--ep-superficie-2`, `--bg-elevated` definidas en todos los temas.
- **Theme switching sin FOUC**: `theme-manager.js` se ejecuta sincrónicamente en `<head>` antes del render. `_LayoutAuth.cshtml` también tiene script inline para evitar FOUC.
- **Persistencia de tema** vía `localStorage`, toggle funcional en sidebar y en auth layout.
- **Sidebar responsive**: colapsa en overlay en móvil, toggle button funcional, cierre al click fuera.
- **Pomodoro**: timer con SVG progress ring, atajos de teclado (espacio, R, 1-4, F, S), BroadcastChannel para sincronización multi-pestaña, manejo de `visibilitychange` para corregir desviación.
- **Dashboard**: héroe con animación de personaje, partículas flotantes, gráfico Chart.js dona con actualización de tema, skeleton loader.
- **Micro-interacciones**: ripple effect en botones, hover elevación en cards, iconos rotan en hover.
- **Transiciones de página**: `ep-fade-in` en contenido, staggered entrance en columnas.
- **`prefers-reduced-motion`** implementado.
- **Focus-visible** en todos los elementos interactivos.
- **Skeleton loaders** definidos con clases `.ep-skeleton`, `.ep-skeleton-text`, `.ep-skeleton-card`, etc.
- **Indicador "Sesión X / Y"** usa texto "Sesión", no "Ciclo".
- **Formularios con validación** visual (is-valid/is-invalid con iconos SVG inline).
- **Notificaciones toast** con animación slide-in, variantes de color.
- **Character `onerror` fallback** consistente en todas las vistas.
- **Service Worker mejorado**: usa network-first para CSS/JS, cache-first para el resto con actualización en background.
- **Sidebar toggle** con botón fijo y cierre al click fuera en móvil.
- **Clases utilitarias**: `ep-text-gradient`, `ep-card`, `ep-input` con soporte completo de temas.
- **Responsive avanzado**: media queries para 768px y 576px con ajustes de layout, touch targets (44px min-height), paginación adaptativa.

### ❌ Errores encontrados

| # | Descripción | Severidad | Módulo | Archivo/Línea | Solución propuesta | Estado |
|---|---|---|---|---|---|---|
| 1 | **`diario-animo.css` usa variables CSS no estándar** (`--texto`, `--texto-secundario`, `--hover`, `--superficie`) que NO existen en el sistema de diseño. El archivo no tiene selector `[data-theme]` y rompe el theme. | alta | Diario Ánimo | `wwwroot/css/diario-animo.css:8,37,61,66,70,81,96,114,118,138,146,154,164,178,204,226,245,284,304,310,318,328` | Reemplazar con variables estándar: `--texto`→`--text-primary`, `--texto-secundario`→`--text-secondary`, `--hover`→`--bg-hover`, `--superficie`→`--surface` | ✅ CORREGIDO — reemplazadas 22 ocurrencias |
| 2 | **Manifest.json sin iconos 192x192 y 512x512** requeridos por PWA. Solo tiene 48x48 y 256x256. | media | PWA | `wwwroot/manifest.json:11-24` | Agregar iconos en tamaños 192x192 y 512x512 con `purpose: "any maskable"` | ❌ No corregido |
| 3 | **Contraste insuficiente en tema Sakura**: `--text-secondary: #7d5f7a` sobre `--bg-elevated: #ffe8f5` (ratio ~2.5:1, falla WCAG AA). | media | CSS/Variables | `wwwroot/css/variables.css:27-28` | Oscurecer `--text-secondary` a ~#6b4a68 o usar fondo más claro | ❌ No corregido |
| 4 | **Fonts Google (Inter, Orbitron, Nunito) no se cargan** — no hay `@import` ni `<link>` a Google Fonts en ningún layout. Las variables `--font-sans`, `--font-display`, `--font-cute` referencian estas fuentes pero nunca se descargan, cayendo en fallbacks del sistema. | media | Layout | `Views/Shared/_Layout.cshtml` · `Views/Shared/_LayoutAuth.cshtml` · `wwwroot/css/variables.css:312-316` | Agregar `<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Orbitron:wght@400;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">` en ambos layouts | ❌ No corregido |
| 5 | **Gráfico semanal — días no asegurados en español**. `dia.Fecha` viene del backend. Si el servidor usa cultura invariante, saldrán en inglés ("Mon", "Tue"...). | media | Pomodoro | `Views/Pomodoro/Index.cshtml:262` | Asegurar que el endpoint `/api/v1/pomodoro/estadisticas-semanales` formatee fechas con cultura "es-ES" (backend) | ❌ No corregido |
| 6 | **Skeleton page loading no funcional**: la clase `ep-page-loading` se agrega y remueve sin añadir `.is-loading`. El shimmer overlay nunca se activa. | baja | Frontend | `Views/Shared/_Layout.cshtml:99-103` | Eliminar las líneas 99-103 o agregar `body.classList.add('is-loading')` y quitarlo tras fetch inicial | ❌ No corregido |
| 7 | **Imagen hero de login usa `alt="Epycus"` en vez de `alt=""`**. La imagen `login-hero.webp` es decorativa (tiene overlay con texto encima), debería tener `alt=""` para lectores de pantalla. | baja | Accesibilidad | `Views/Shared/_LayoutAuth.cshtml:34` | Cambiar `alt="Epycus"` a `alt=""` | ❌ No corregido |
| 8 | **Falta `<meta name="color-scheme">`** para renderizado nativo del browser según tema. Sin esto, Chrome/Safari usan sus defaults claros en formularios y scrollbars incluso en modo oscuro. | baja | Layout | `Views/Shared/_Layout.cshtml` · `Views/Shared/_LayoutAuth.cshtml` | Agregar `<meta name="color-scheme" content="dark light">` en ambos layouts | ❌ No corregido |
| 9 | **Carga excesiva de CSS** en todas las páginas: dashboard.css, perfil.css, ia.css cargados en el layout general aunque no se usen en todas las vistas. | baja | Rendimiento | `Views/Shared/_Layout.cshtml:11-13` | Mover dashboard.css, perfil.css, ia.css a `@section Styles` en cada vista que los necesite | ❌ No corregido |
| 10 | **NUEVO: Estilos de sidebar duplicados** entre `site.css:192-201` y `epycus-modern.css:37-51`. Definen `.ep-sidebar` con mismas propiedades pero valores distintos (width: 260px vs 280px, padding diferente). | baja | CSS | `wwwroot/css/site.css:192-201` · `wwwroot/css/epycus-modern.css:37-51` | Consolidar en un solo archivo. `epycus-modern.css` tiene prioridad por cargarse antes que `site.css`, pero `site.css` sobrescribe algunas propiedades. | ❌ No corregido |
| 11 | **NUEVO: `ep-fade-in` animación definida dos veces** en `site.css:823-826` y `site.css:1253-1256`. `.ep-contenido` también tiene dos definiciones con duración distinta (0.3s vs 0.35s). | baja | CSS | `wwwroot/css/site.css:823-830,1253-1260` | Eliminar el bloque duplicado (líneas 1253-1260) y unificar duración | ❌ No corregido |
| 12 | **NUEVO: Tema oscuro duplicado** — las mismas variables están definidas en `variables.css:173-309` (bajo `[data-theme="dark"]`) y en `temas/tema-noche-epica.css`. Esto causa conflictos si ambos se cargan. | baja | CSS | `wwwroot/css/variables.css:173-309` · `wwwroot/css/temas/tema-noche-epica.css` | `tema-noche-epica.css` es el que se aplica via `hoja-tema`, las variables duplicadas en `variables.css` bajo `[data-theme="dark"]` son redundantes. Decidir cuál es la fuente de verdad. | ❌ No corregido |

### ⚠️ Advertencias / Mejoras

| # | Descripción | Impacto | Módulo | Propuesta |
|---|---|---|---|---|
| 1 | SW usa cache-first para todo excepto CSS/JS. Puede servir contenido stale indefinidamente. | medio | Service Worker | Implementar `stale-while-revalidate` con timeout |
| 2 | No hay offline fallback real para navegación — solo assets estáticos cacheados. | medio | PWA | Implementar "offline shell" con `caches.match('/offline.html')` |
| 3 | No hay manifest `screenshots` para mejorar experiencia de instalación PWA. | bajo | PWA | Agregar screenshots al manifest.json |
| 4 | Estilos `.ep-sidebar` duplicados entre `site.css` y `epycus-modern.css`. Puede causar conflictos. | medio | CSS | Consolidar estilos de sidebar en un único archivo |
| 5 | Botón Google Sign-In en Login.cshtml usa estilos inline vs clases del sistema (`auth-btn-google`). | bajo | UI | Migrar a clases definidas en auth.css |
| 6 | Meta diaria en Pomodoro tiene dos representaciones: `metaDiariaValue` (local) y `CONFIGURACION.metaDiaria` (servidor). Pueden desincronizarse. | medio | Pomodoro | Unificar en una sola fuente de verdad |
| 7 | Falta `loading="lazy"` en imágenes de personaje fuera del viewport inicial. | bajo | Rendimiento | Agregar `loading="lazy"` en imágenes no críticas |

### 🚀 Nuevas funcionalidades propuestas para módulos existentes

| Módulo | Funcionalidad | Prioridad | Esfuerzo estimado | Plataforma |
|---|---|---|---|---|
| Diario Ánimo | Migrar a variables CSS estándar del sistema | alta | 2 horas | Web |
| PWA | Iconos correctos + offline shell completo | alta | 1 día | Web |
| CSS | Carga diferida de CSS específico por página | media | 1 día | Web |
| Tema Sakura | Ajustar colores de texto para contraste WCAG AA (mín 4.5:1) | alta | 30 min | Web |
| Accesibilidad | Auditoría de contraste completa + etiquetas ARIA faltantes | media | 2 días | Web |
| Pomodoro | Sincronización meta diaria local-servidor | media | 1 día | Web |

---

## 3. Agente de Arquitectura y Código

### Alcance
Revisar estructura del proyecto, patrones de diseño, separación de responsabilidades, uso de inyección de dependencias, configuración de middleware, seguridad, y calidad del código.

### Áreas a revisar
- `Program.cs` — configuración general, orden de middleware, CORS, CSP
- `Middleware/` — `ConfiguracionMiddleware.cs` (CSP), `CargarPersonajeFilter.cs`
- `Controllers/` — estructura, validación, uso de `RespuestaApi<T>`
- `Servicios/` — `ServicioPomodoro.cs`, `ServicioAutenticacion.cs`, inyección de dependencias
- `Modelos/` — entidades, relaciones EF, migrations
- `wwwroot/` — sw.js, css/, js/ (si existe)
- `Views/` — Razor pages, layouts, partials
- `Datos/` — `ContextoAplicacion.cs`, configuraciones de entidades, `DatosSemilla.cs`
- `deploy/` — scripts, nginx template

### Verificaciones
- [x] **Arquitectura en capas** — Controllers → Services → EF/Database correcta
- [x] **Inyección de dependencias** — todos los servicios registrados correctamente
- [x] **No hay dependencias circulares** entre servicios
- [x] **Async/await** — todos los métodos de DB son async, sin `.Result`/`.Wait()`
- [x] **Manejo de errores** — `TelemetriaMiddleware` y `ExceptionHandlerMiddleware` presentes
- [x] **DTOs vs Entidades** — mayormente bien, pero `ApiAuthController` expone `List<Carrera>` (entidad EF) directamente
- [x] **Migrations** — snapshot actualizado, migrations aplicadas, ninguna pendiente
- [x] **CSP** — configurada en middleware con CDNs necesarios (jsdelivr, cdnjs, ui-avatars). **⚠️ NOTA**: No incluye `fonts.googleapis.com` ni `fonts.gstatic.com` (bloquearía Google Fonts si se cargaran)
- [x] **CORS** — configurado con orígenes permitidos desde `appsettings.json`
- [x] **Anti-forgery** — `AutoValidateAntiforgeryTokenAttribute` global + `[IgnoreAntiforgeryToken]` en API
- [x] **Rate limiting** — 6 políticas (Auth, Api, Mobile, Gemini, DeepSeek, Pomodoro) + Global limiter
- [ ] **Seguridad** — ⚠️ VER ERRORES CRÍTICOS ABAJO
- [x] **JWT/Cookies** — Cookies HttpOnly, Secure, SameSite configurado correctamente
- [x] **appsettings.json en .gitignore** (desde commit b96eeb9) — credenciales ya no se suben al repo
- [x] **`credenciales.md` en .gitignore** — desde commit b96eeb9
- [ ] **PROMT_AUDITORIA.md aún expone credenciales** — ⚠️ Líneas 9-18 tenían password SSH real. Parcialmente sanitizado.

### Errores encontrados

| # | Descripción | Severidad | Módulo | Archivo/Línea | Solución propuesta |
|---|---|---|---|---|---|
| 1 | ~~**`@Html.Raw()` con contenido de usuario en chat IA** — `FormatearMensaje()` procesa `msg.Contenido` sin sanitizar XSS~~ ✅ CORREGIDO ✅ VERIFICADO | ~~**CRÍTICA**~~ | ~~Seguridad/XSS~~ | ~~`Views/Ia/Index.cshtml:110`~~ | Se agregó `System.Net.WebUtility.HtmlEncode(texto)` antes del formateo markdown en `FormatearMensaje()` — confirmado en `Views/Ia/Index.cshtml:174` |
| 2 | **`PROMT_AUDITORIA.md` expone credenciales** — commit `b96eeb9` sanitizó appsettings.json pero el doc auditado aún contenía password SSH real | **CRÍTICA** | Seguridad | `PROMT_AUDITORIA.md:9` (ya sanitizado en esta revisión) | Verificar que no haya commits históricos con las credenciales y rotar la contraseña SSH |
| 3 | **Secretos en source control**: `appsettings.json` y `deploy/epycus-web.service` tenían claves reales. Ya puestos en `.gitignore` (commit `b96eeb9`) pero **aún en historial git**. Se debe rotar: JWT secret, Gemini API key, DeepSeek API key, SMTP password. | **CRÍTICA** | Seguridad | Historial git | Rotar todas las credenciales históricamente expuestas |
| 4 | **Entidades EF expuestas en API**: `ApiAuthController.Carreras()` devuelve `List<Carrera>` (entidad) sin DTO | **ALTA** | API | `Controllers/Api/ApiAuthController.cs:167` | Crear `CarreraDto` con solo campos necesarios |
| 5 | ~~**`@Html.Raw()` con datos serializados del servidor** — aunque controlados, rompe el escaping automático de Razor~~ ✅ CORREGIDO | ~~**MEDIA**~~ | ~~MVC~~ | ~~`Views/Home/Index.cshtml:153-154`~~ | Reemplazado `@Html.Raw(Json.Serialize(...))` por `@Json.Serialize(...)` (Razor escapa automáticamente) |
| 6 | **Validación ModelState inconsistente**: `SuppressModelStateInvalidFilter = true` desactiva validación automática. `ApiPomodoroController.CicloCompletado()` y `Finalizar()` sí verifican `ModelState.IsValid`, pero otros endpoints no | **MEDIA** | Controllers | `Program.cs:35` · `ApiPomodoroController.cs:45,63` | Eliminar suppress o agregar check en todos los endpoints |
| 7 | **`RespuestaApi<object>` sin tipo**: Múltiples endpoints usan `RespuestaApi<object>.Exitosa(respuesta)` perdiendo type safety | **MEDIA** | API | `ApiHabitosController.cs:36,50,109,123,201,208` · `ApiIaController.cs:63` | Crear DTOs específicos |
| 8 | **Lógica de cálculo en controller**: XP y progreso de nivel calculados en `HomeController.Index()` en lugar de servicio | **MEDIA** | MVC | `Controllers/HomeController.cs:49-56` | Mover a `IServicioProgreso` |
| 9 | **N+1 queries**: `ServicioPomodoro.RegistrarCiclo()` carga sesión, luego config, luego sub-tarea en 3 queries separadas | **MEDIA** | Rendimiento | `Servicios/ServicioPomodoro.cs:100-126` | Usar `.Include()` + `.ThenInclude()` |
| 10 | **Racha calculada en memoria**: `ObtenerRachaActualAsync()` carga TODAS las sesiones y calcula en C#. Con muchas sesiones, esto se vuelve O(n) en memoria | **MEDIA** | Rendimiento | `Servicios/ServicioPomodoro.cs:316-339` | Usar SQL ventana o limitar a últimos 30 días |
| 11 | **Sin `AsNoTracking()`**: Queries de solo lectura (estadísticas, historial) trackean entidades innecesariamente | **MEDIA** | Rendimiento | `Servicios/ServicioPomodoro.cs:343-356,364-388` | Agregar `.AsNoTracking()` |
| 12 | **Excepción como flujo de control**: `ServicioPomodoro.IniciarSesion()` lanza `InvalidOperationException` para validaciones de negocio. El método `IniciarSesionSiNoActiva()` lo captura, pero el patrón es frágil | **MEDIA** | Arquitectura | `Servicios/ServicioPomodoro.cs:43,50,59` | Usar Result pattern (FluentResults / OneOf / ErrorOr) |
| 13 | **CSP no incluye Google Fonts**: `font-src` y `style-src` no listan `fonts.googleapis.com` ni `fonts.gstatic.com`. Si se agregan Google Fonts en el futuro, serán bloqueadas | **MEDIA** | Seguridad/CSP | `Middleware/ConfiguracionMiddleware.cs:43` | Agregar `https://fonts.googleapis.com` y `https://fonts.gstatic.com` a las directivas correspondientes |
| 14 | ~~**Response compression no configurada**~~ ✅ CORREGIDO | ~~**MEDIA**~~ | ~~Rendimiento~~ | ~~`Middleware/ConfiguracionMiddleware.cs`~~ | Agregado `AddResponseCompression()` en servicios y `UseResponseCompression()` en middleware (Brotli + Gzip) |
| 15 | **Tema oscuro duplicado**: Variables definidas tanto en `variables.css` como en `tema-noche-epica.css` | **BAJA** | CSS | `wwwroot/css/variables.css:173` · `wwwroot/css/temas/tema-noche-epica.css` | Unificar: un solo archivo de tema |
| 16 | **`site.js` vacío**: Archivo solo con comentario pero se carga en cada página | **BAJA** | Rendimiento | `wwwroot/js/site.js:1` | Eliminar referencia o poblar con código útil |
| 17 | **Carga excesiva de CSS**: 10 archivos CSS + 2 CDN en layout general (dashboard.css, perfil.css, ia.css se cargan en TODAS las páginas) | **BAJA** | Rendimiento | `Views/Shared/_Layout.cshtml:7-17` | Mover CSS específico a `@section Styles` |
| 18 | **SW cache-first**: Service worker sirve contenido stale para navegación (páginas HTML cacheadas) | **BAJA** | PWA | `wwwroot/sw.js:56-67` | Cambiar a network-first con fallback a cache |
| 19 | **Header obsoleto**: `X-XSS-Protection: 1; mode=block` deprecado en Chrome. Los navegadores modernos lo ignoran | **BAJA** | Seguridad | `Middleware/ConfiguracionMiddleware.cs:38` | Eliminar (CSP `script-src` ya protege contra XSS) |
| 20 | **`[ValidateAntiForgeryToken]` redundante**: Ya aplicado globalmente via `AutoValidateAntiforgeryTokenAttribute` | **BAJA** | MVC | `AutenticacionController.cs` · `PerfilController.cs` | Quitar atributos redundantes |
| 21 | **`AddApplicationPart` redundante** en `Program.cs` | **BAJA** | Config | `Program.cs:42` | Eliminar |
| 22 | **`DiasSemanaHabito` DbSet con formato inconsistente**: salto de línea extraño en la declaración | **BAJA** | Código | `Datos/ContextoAplicacion.cs:38-39` | Formatear correctamente |
| 23 | **Sin `Strict-Transport-Security` explícito**: `UseHsts()` por defecto no incluye `preload` ni `includeSubDomains` configurados explícitamente | **BAJA** | Seguridad | `Middleware/ConfiguracionMiddleware.cs:16` | Configurar `HstsOptions` con `Preload = true` y `IncludeSubDomains = true` si aplica |

### Mejoras propuestas

| Prioridad | Mejora | Esfuerzo | Módulo |
|---|---|---|---|
| **Crítica** | Rotar credenciales expuestas históricamente (JWT secret, Gemini API Key, DeepSeek API Key, SMTP password, SSH password) | 1 día | Seguridad |
| **Crítica** | Sanitizar `@Html.Raw()` en chat IA — implementar `HtmlSanitizer` o escapar contenido antes de renderizar | 4 horas | Seguridad/XSS |
| **Alta** | Añadir `.AsNoTracking()` en queries de solo lectura (estadísticas, historial) | 2 horas | Rendimiento |
| **Alta** | Optimizar query de racha con SQL ventana en lugar de in-memory | 4 horas | Rendimiento |
| **Alta** | Implementar **Response Compression** middleware para respuestas JSON/HTML | 2 horas | Rendimiento |
| **Alta** | Implementar **FluentValidation** con validadores separados por DTO | 2 días | Arquitectura |
| **Media** | Migrar a **Result pattern** (OneOf/FluentResults/ErrorOr) en servicios | 3 días | Arquitectura |
| **Media** | Agregar **mediator pattern** (MediatR) para desacoplar Controllers de Services | 3 días | Arquitectura |
| **Media** | Añadir **métricas** con `System.Diagnostics.Metrics` para Prometheus/Grafana | 1 día | Observabilidad |
| **Media** | Implementar **blacklist de JWT** con cache distribuida (tokens no invalidables hoy) | 1 día | Seguridad |
| **Media** | Unit tests con **xUnit + Moq + FluentAssertions** (ya hay algunos, expandir cobertura) | 1 semana | Testing |
| **Media** | Integration tests con **TestContainers** para MySQL real | 3 días | Testing |
| **Media** | Agregar HSTS preload: `options.Preload = true; options.IncludeSubDomains = true;` | 30 min | Seguridad |
| **Baja** | Consolidar CSS en bundles (reducir de 10 archivos a 2-3) | 4 horas | Rendimiento |
| **Baja** | Cambiar Service Worker a network-first para páginas | 2 horas | PWA |
| **Baja** | Poblar `site.js` con utilidades o eliminar referencia | 1 hora | Mantenimiento |
| **Baja** | Configurar pre-commit hook con gitleaks para prevenir fugas de secretos | 2 horas | Seguridad |
| **Baja** | Feature flags para activar/desactivar funcionalidades sin deploy | 2 días | Arquitectura |
| **Baja** | Agregar `Turnstile` section faltante a `appsettings.Example.json` | 15 min | Configuración |
| **Baja** | Unificar `DiasSemanaHabito` DbSet formateo | 5 min | Código |

---

## 3b. Agente de Arquitectura Móvil (Android)

### Alcance
Revisar la app Android en `C:\Users\marco\Pictures\Epycus`. Analizar calidad del código, patrones, manejo de estado, cache offline, gestión de ciclo de vida, y errores conocidos.

### Áreas a revisar
- `app/src/main/java/es/epycus/app/` — toda la estructura
- `api/` — Retrofit services, AuthInterceptor, RetrofitClient
- `data/local/` — Room database, DAOs, entities
- `repository/` — lógica de negocio
- `ui/` — fragments, activities, adapters
- `util/` — SessionManager, CacheManager, ThemeManager
- `build.gradle.kts` — dependencias, minSdk, targetSdk

### Verificaciones
- [ ] **Sin dependencias circulares** entre repositories, fragments, managers
- [ ] **Manejo de ciclo de vida** — `onSaveInstanceState`/`onCreateView` restauran estado del Pomodoro correctamente (timer, ciclo, sesión activa)
- [ ] **Cancelación de llamadas** — todos los Retrofit `Call<?>` se cancelan en `onDestroyView()` (lista `activeCalls`)
- [ ] **Token refresh** — `AuthInterceptor` refresca JWT automáticamente al recibir 401, sin loops infinitos, con `synchronized` lock
- [ ] **EncryptedSharedPreferences** — JWT y refresh token almacenados cifrados (AES256 GCM), fallback seguro si falla cifrado
- [ ] **Room** — `allowMainThreadQueries()` habilitado (⚠️ riesgo de ANR en consultas pesadas), `fallbackToDestructiveMigration()` (⚠️ pérdida de datos en migración)
- [ ] **Cache TTLs** — `CacheManager` con tiempos razonables: dashboard 5min, hábitos 2min, perfil 10min, config pomodoro 30min
- [ ] **Offline primero** — fragments intentan network, fallback a Room, muestran offline banner
- [ ] **Sin ViewModels** — la app usa Fragment como controlador (⚠️ riesgo de pérdida de estado en recreación). Verificar que `onSaveInstanceState` cubre todas las variables críticas
- [ ] **Sin Hilt/Dagger** — singletons manuales (⚠️ memory leaks potenciales si se retiene contexto). Verificar que no haya fugas de `Activity`/`Context`
- [ ] **Sin Navigation Component** — navegación con intents + ViewPager2 (⚠️ backstack management manual). Verificar transiciones suaves y sin estados huérfanos
- [ ] **Sin SignalR/WebSocket** — la app no recibe notificaciones en tiempo real (solo REST polling)
- [ ] **Sin FCM** — no hay push notifications, Pomodoro solo funciona con app en foreground
- [ ] **Exact Alarms** — permisos `USE_EXACT_ALARM` / `SCHEDULE_EXACT_ALARM` solicitados correctamente según SDK

### Bugs conocidos (de `bugs-pendientes.md`)
- [ ] **BUG-1** Perfil > Avatar: muestra fondo morado sin imagen en dark mode
- [ ] **BUG-2** Inicio > Header card: necesita mejoras
- [ ] **BUG-3** Pomodoro > BottomNav: tabs no responden cuando Pomodoro overlay está abierto (touch event consumption)
- [ ] **BUG-4** Diario > Chat Edy: muestra letra "E" en vez de icono robot
- [ ] **BUG-5** Registro > Gender spinner: opciones no se muestran (adapter no recibe `R.array.generos`)
- [ ] **BUG-6** Perfil > Edit career: el cambio de carrera revierte al cerrar diálogo (SharedPrefs no actualizado)
- [ ] **BUG-7** Perfil > Selección personaje: Kai/Ares siempre muestra a Luna (drawable mapping error)

### Mejoras propuestas
- Migrar a **Kotlin + Jetpack Compose** para modernizar UI
- Implementar **ViewModels + StateFlow** para estado reactivo y survival a recreación
- Añadir **Hilt** para inyección de dependencias (eliminar singletons manuales)
- Integrar **SignalR client** para notificaciones en tiempo real (sincronización con web)
- Integrar **FCM** para push notifications cuando app está en background/killed
- Añadir **WorkManager** para tareas en background (sincronización offline, Pomodoro en background)
- Implementar **Navigation Component** con Deep Links
- Reemplazar `allowMainThreadQueries()` con `suspend` functions o `LiveData`
- Añadir **DataStore** como reemplazo moderno de SharedPreferences
- Unit tests con **JUnit + Mockito + Robolectric**
- UI tests con **Compose UI Test** o **Espresso**
- **Acceso a cámara/galería** para foto de perfil personalizada

---

## 4. Agente de Base de Datos y Rendimiento

### Alcance
Revisar esquema de base de datos, índices, consultas lentas, uso de EF Core, migraciones, y rendimiento general.

### Verificaciones
- [ ] **Índices** — `EXPLAIN` en consultas frecuentes (estadísticas semanales, historial)
- [ ] **Consultas N+1** — verificar `.Include()` y `.ThenInclude()` correctos
- [ ] **Migraciones** — todas aplicadas en producción (`SELECT * FROM __EFMigrationsHistory`)
- [ ] **Tamaño de tablas** — `SELECT TABLE_NAME, ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS SIZE_MB FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'epicus_db' ORDER BY SIZE_MB DESC`
- [ ] **Slow query log** — revisar si está habilitado
- [ ] **Conexiones activas** — `SHOW PROCESSLIST;`
- [ ] **Consultas más lentas** — instalar y ejecutar `pt-query-digest` o similar
- [ ] **Pool de conexiones** — verificar `Max Pool Size` en connection string

### Mejoras propuestas
- Añadir índices compuestos para consultas de estadísticas (UsuarioId, FechaCreacion, Tipo)
- Implementar paginación con cursor en historial (no offset)
- Cache en Redis para datos de dashboard
- Particionamiento por fecha para tablas grandes (SesionPomodoro, EntradaDiario)
- Read replicas para consultas pesadas de reportes

---

## 5. Agente de Conexión Móvil-Web (Cross-platform)

### Alcance
Verificar que la app Android (`C:\Users\marco\Pictures\Epycus`) y la web (`https://app.epycus.es`) compartan **el mismo backend**, los mismos datos de usuario, y que las operaciones en una plataforma se reflejen correctamente en la otra. Detectar inconsistencias de API, diferencias de comportamiento, y problemas de sincronización.

### Endpoints compartidos (misma API, mismo prefijo)
> La web usa **controladores MVC (Razor)** para renderizar páginas y formularios (login, registro, CRUD de hábitos, diario, perfil).  
> El **JS del frontend web** consume los mismos endpoints REST `/api/v1/...` que la app Android para operaciones AJAX.  
> Por lo tanto **NO hay dos versiones de ruta**: todo está unificado en `/api/v1/` para la API REST.

| Funcionalidad | Web (MVC form) | Web (JS AJAX) | Móvil (API) | Backend compartido |
|---|---|---|---|---|
| Login email | `POST /Autenticacion/Login` | — | `POST /api/v1/auth/login` | Mismo servicio `IServicioAutenticacion.Login()` |
| Registro | `POST /Autenticacion/Registro` | — | `POST /api/v1/auth/registro` | Mismo servicio |
| Google OAuth | `GET /Autenticacion/IniciarSesionGoogle` → callback → cookie | — | `POST /api/v1/auth/google` | Mismo servicio `ProcesarAutenticacionGoogleAsync()` |
| Iniciar Pomodoro | — | `POST /api/v1/pomodoro/iniciar` | `POST /api/v1/pomodoro/iniciar` | **Mismo endpoint, mismo controlador** |
| Completar ciclo | — | `POST /api/v1/pomodoro/{id}/ciclo-completado` | `POST /api/v1/pomodoro/{id}/ciclo-completado` | **Mismo endpoint, mismo controlador** |
| Config Pomodoro | `GET/POST /Pomodoro/Configuracion` | — | `GET/PUT /api/v1/pomodoro/configuracion` | Mismo servicio |
| Hábitos CRUD | `POST/GET /Habitos/Crear`, `/Habitos/Editar/{id}`, etc. | `POST /api/v1/habitos/{id}/completar` (AJAX) | `GET/POST/PUT/DELETE /api/v1/habitos[/{id}]` | Mismo servicio |
| Hábitos hoy | — | — | `GET /api/v1/habitos/hoy` | Mismo servicio |
| Diario | `POST /DiarioAnimo/Registrar` | — | `GET/POST /api/v1/diario` | Mismo servicio |
| Perfil | `GET /Perfil`, `POST /Perfil/ActualizarPerfil` | `POST /api/perfil/tema` (AJAX, legacy) | `GET/PUT /api/v1/perfil` | Mismo servicio |
| Progreso | `GET /Progreso` | — | `GET /api/v1/progreso`, `GET /api/v1/dashboard/resumen` | Mismo servicio |
| IA Chat | — | `POST /api/ia/chat` (legacy, MVC) devuelve formato `{ exito, respuesta }` | `POST /api/v1/ia/chat` devuelve `RespuestaApi<T>` | **⚠️ Distinto formato de respuesta**. Web usa objeto plano, API usa `RespuestaApi<T>`. Mismo servicio subyacente `IServicioIA` |

### Verificaciones de consistencia
- [ ] **Misma sesión usuario** — login en web y móvil con mismas credenciales, acceden a los mismos datos
- [ ] **Pomodoro cross-device** — iniciar Pomodoro en web → consultar sesión activa desde móvil (`GET /api/v1/pomodoro/sesion-activa`) → devuelve el mismo `sesionId` y estado
- [ ] **Estadísticas unificadas** — completar 3 ciclos en móvil → consultar estadísticas semanales en web → muestra 3 ciclos
- [ ] **Configuración compartida** — cambiar meta diaria, duración focus/break en móvil (`PUT /api/v1/pomodoro/configuracion`) → web refleja el cambio al recargar
- [ ] **Hábitos sincronizados** — crear hábito en web → aparece en `GET /api/v1/habitos/hoy` del móvil; completar hábito en móvil → web muestra racha actualizada
- [ ] **Diario sincronizado** — escribir entrada en web → `GET /api/v1/diario/hoy` del móvil devuelve la misma entrada
- [ ] **Perfil compartido** — cambiar personaje o tema en web → móvil refleja el cambio (y viceversa)
- [ ] **Racha y progreso** — racha actual, XP, nivel deben ser idénticos consultados desde ambos clientes
- [ ] **Formato de respuestas** — `RespuestaApi<T>` debe tener misma estructura (`exito`, `mensaje`, `datos`, `errores`) desde todos los endpoints `/api/v1/...`
- [ ] **Manejo de errores** — error 401, 400, 500 deben tener mismo `mensaje` desde cualquier endpoint `/api/v1/...`
- [ ] **Consistencia MVC→API** — los formularios MVC y los endpoints API deben usar los mismos servicios subyacentes para evitar lógica duplicada
- [ ] **Zona horaria** — fechas en estadísticas deben ser consistentes sin importar desde dónde se consulten

### Problemas potenciales
- **Timer no sincronizado**: la web podría tener un timer server-side, el móvil es solo local. Si el móvil inicia Pomodoro y cierra app, el backend no sabe que el timer sigue corriendo → inconsistencia al consultar estadísticas
- **Falta de WebSocket móvil**: notificaciones en tiempo real (ej: "tu descanso terminó") llegan a web via SignalR pero no al móvil
- **Concurrencia**: ¿qué pasa si el usuario inicia Pomodoro en web y móvil simultáneamente? El backend debería rechazar o manejar el conflicto
- **Refresh token**: web usa cookies HttpOnly, móvil usa JWT Bearer. El backend debe manejar ambos sin conflictos de CORS

### Escenarios cross-platform
1. **[Dual Pomodoro]** Usuario tiene Pomodoro corriendo en web → abre app móvil → la app detecta sesión activa via `GET /api/v1/pomodoro/sesion-activa` → pregunta "¿continuar o cancelar sesión web?" → si continúa, timer móvil empieza con los segundos restantes correctos
2. **[Dual login]** Usuario logueado en web (cookie) → abre app móvil → loguea con mismas credenciales (JWT) → ambos clientes funcionales simultáneamente → cerrar sesión en un dispositivo NO debe cerrar el otro (sesiones independientes)
3. **[Offline móvil → online]** Usuario completa 2 hábitos offline en móvil → recupera conexión → Room cache sincroniza con servidor → web muestra hábitos completados
4. **[Racha compartida]** Usuario completa 1 ciclo Pomodoro en web por la mañana → 3 ciclos en móvil por la tarde → estadísticas en web muestran 4 ciclos hoy, racha actualizada

### Mejoras propuestas
- Implementar **SignalR client** en Android para notificaciones en tiempo real
- Añadir **sincronización bidireccional** con cola offline + WorkManager
- Implementar **WebSocket/SignalR** para estado compartido del Pomodoro (un solo timer activo por usuario)
- Añadir **detección de sesión duplicada** y opción de transferir sesión entre dispositivos
- **Deep links** para abrir app móvil desde web con estado preservado

---

## 6. Agente de Seguridad (Web + Móvil)

### Alcance
OWASP Top 10, hardening de servidor, configuración de nginx, headers de seguridad, manejo de sesiones web y JWT móvil, seguridad Android.

### Verificaciones (Web — revisión de código)
- [x] **Headers HTTP** — configurados en `ConfiguracionMiddleware.cs:36-44`: CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy. X-XSS-Protection presente pero deprecado.
- [x] **SQL Injection** — EF Core con parametrización. Sin SQL raw en código.
- [x] **XSS** — Razor escapa por defecto. Sin `@Html.Raw()` con datos de usuario.
- [x] **CSRF** — `AutoValidateAntiforgeryTokenAttribute` global en MVC. API usa `[IgnoreAntiforgeryToken]` + JWT.
- [x] **Rate limiting** — 6 políticas configuradas en `ConfiguracionServicios.cs:120-183`: Auth (20/min), Api (300/min), Mobile (400/min), Gemini (20/min), DeepSeek (2500/min), Pomodoro (60/min) + Global (600/min). Sin rate limiting en admin endpoints.
- [x] **nginx** — `ssl_protocols TLSv1.2 TLSv1.3;`, HSTS configurado. Falta `server_tokens off;`.
- [x] **Logs sensibles** — servicios loguean con ILogger pero no se observan contraseñas/tokens en mensajes de log. `ServicioDiarioAnimo.cs:100` loguea `usuarioId` pero sin datos sensibles.

### Verificaciones (Móvil - Android)
- [ ] **EncryptedSharedPreferences** — no se pudo verificar (código Android en otro repositorio)
- [ ] **SSL pinning** — no se pudo verificar
- [ ] **WebView** — no se pudo verificar
- [ ] **Keystore** — no se pudo verificar

### ✅ Funcionando correctamente

- **Autenticación multicanal unificada**: JWT Bearer + cookies HttpOnly manejadas por el mismo `JwtBearerEvents` en `ConfiguracionServicios.cs:72-98`. OnMessageReceived extrae token de cookie para web y de header para API.
- **Redirect a login en web sin JWT**: `OnChallenge` en `ConfiguracionServicios.cs:87-97` redirige a `/Autenticacion/Login` para rutas MVC no autenticadas (excepto `/api` que devuelve 401).
- **Anti-CSRF dual**: `AutoValidateAntiforgeryTokenAttribute` global en controladores MVC (línea 41 de Program.cs). API usa `[IgnoreAntiforgeryToken]` y confía en JWT Bearer.
- **CSP configurada**: `Content-Security-Policy` en `ConfiguracionMiddleware.cs:43` con orígenes para CDNs (jsdelivr, cdnjs, ui-avatars).
- **Rate limiting por endpoint**: Políticas especificadas por controlador via `[EnableRateLimiting("Auth")]`, `[EnableRateLimiting("Api")]`. Global limiter por usuario autenticado o anónimo.
- **Turnstile Cloudflare**: Implementado en login y registro web (`AutenticacionController.cs:67-73, 194-199`).
- **Cookies seguras**: HttpOnly, Secure, SameSite=Lax/Strict configurado consistentemente en `CrearOpcionesCookie()`.
- **HSTS en nginx**: `max-age=31536000; includeSubDomains` (sin `preload`).
- **CORS restringido**: Solo orígenes `http://app.epycus.es` y `https://app.epycus.es` permitidos.
- **Google ExternalCookie**: Timeout de 5 min, HttpOnly, Secure.
- **Admin JWT separado**: Cookie `admin_jwt_token` con SameSite=Strict y expiración de 2h (vs `jwt_token` con SameSite=Lax y 7 días).

### ❌ Errores encontrados

| # | Descripción | Severidad | Módulo | Archivo/Línea | Solución propuesta |
|---|---|---|---|---|---|
| 1 | **API keys reales en `appsettings.json`**: Gemini (`AIzaSyAh_...`) y DeepSeek (`sk-9dd0d7f7...`) son reales y funcionales, no placeholders. Misma situación para JWT secret y SMTP password. | **CRÍTICA** | Seguridad | `appsettings.json:15,40,44` | Mover a User Secrets / variables de entorno. Rotar todas las credenciales inmediatamente |
| 2 | **`ApiAdminController` sin verificación de rol**: `[Authorize]` a nivel de clase pero sin `Roles = "Admin"`. Cualquier usuario autenticado puede listar usuarios, frases, activar suscripciones. ✅ CORREGIDO (cambiado a `[Authorize(Roles = "Admin")]`, añadido `EsAdministrador()` en login) | **CRÍTICA** | API/Admin | `Controllers/Api/ApiAdminController.cs:12` | ✅ Fix: `[Authorize(Roles = "Admin")]` + verificación admin en `Login()` |
| 3 | **`PROMT_AUDITORIA.md` expone SSH password real** (`ROTATED_SSH_PASSWORD`), Google Client ID, DB user/password en líneas 9-18 — ✅ YA CORREGIDO (placeholders) | **CRÍTICA** | Documentación | `PROMT_AUDITORIA.md:9` | Verificar historial git y rotar SSH password |
| 4 | **JWT no invalidable**: No hay blacklist de tokens ni `iat` (issued-at) verificado contra versión de token del usuario. Un token robado sirve hasta su expiración (60 min) | **ALTA** | Autenticación | `ConfiguracionServicios.cs:58-98` | Implementar blacklist distribuida o incrementar `SecurityStamp` del usuario en cada logout |
| 5 | **Admin login API no verifica rol**: `ApiAdminController.Login()` acepta cualquier credencial válida, no solo administradores. ✅ VERIFICADO (código revisado: usa `_servicioAutenticacion.Login()` genérico sin filtro de rol) | **ALTA** | API/Admin | `Controllers/Api/ApiAdminController.cs:25-35` | Verificar `User.IsInRole("Administrador")` después del login o en el servicio |
| 6 | **Sentry DSN vacío**: `appsettings.json:54` tiene `"Dsn": ""`. Errores en producción no se reportan a Sentry. | **ALTA** | Monitoreo | `appsettings.json:54` | Configurar DSN real de Sentry |
| 7 | **nginx sin `server_tokens off;`**: Expone versión de nginx en respuestas de error y headers. | **MEDIA** | Infraestructura | `deploy/nginx-epycus.conf` | Agregar `server_tokens off;` en bloque http o server |
| 8 | **CSP sin `report-uri`/`report-to`**: Vulnerabilidades CSP no se reportan. | **MEDIA** | Backend | `Middleware/ConfiguracionMiddleware.cs:43` | Agregar `report-uri /csp-report;` a CSP |
| 9 | **Sin rate limiting en admin endpoints**: `ApiAdminController` usa `[EnableRateLimiting("Api")]` pero `AdminController` MVC no tiene rate limiting en login admin. | **MEDIA** | Admin | `Controllers/AdminController.cs:31-65` | Agregar `[EnableRateLimiting("Auth")]` en login admin |
| 10 | **HSTS sin `preload`**: No se puede incluir en listas preload de navegadores. | **BAJA** | Infraestructura | `deploy/nginx-epycus.conf:29` | Cambiar a `max-age=31536000; includeSubDomains; preload` |
| 11 | **`X-XSS-Protection` deprecado**: Chrome lo ignora desde 2019. | **BAJA** | Middleware | `Middleware/ConfiguracionMiddleware.cs:38` | Eliminar el header |
| 12 | **Permissions-Policy restrictiva**: `camera=(), microphone=(), geolocation=()` — no incluye `interest-cohort=()` para evitar FLoC. | **BAJA** | Middleware | `Middleware/ConfiguracionMiddleware.cs:40` | Agregar `interest-cohort=()` |
| 13 | **Logout no invalida JWT server-side**: `CerrarSesion()` marca refresh token como usado pero el JWT sigue siendo válido hasta expirar. | **MEDIA** | Autenticación | `Controllers/AutenticacionController.cs:331-336` | Implementar blacklist de JWT con cache distribuida |

### ⚠️ Advertencias / Mejoras

| # | Descripción | Impacto | Módulo | Propuesta |
|---|---|---|---|---|
| 1 | `appsettings.Example.json` tiene Turnstile config pero `appsettings.json` no (faltó migrar al merge). | medio | Config | Agregar sección Turnstile a `appsettings.json` |
| 2 | `appsettings.json` en `.gitignore` línea 406, pero se trackeó antes. Verificar que no haya commits históricos con secretos. | medio | Repo | Usar `git filter-repo` o `bfg` para purgar secretos del historial |
| 3 | Anti-CSRF token header name es `X-CSRF-TOKEN` pero los AJAX del frontend podrían usar `X-XSRF-TOKEN` (convención Angular). | bajo | Frontend | Verificar que JS use `X-CSRF-TOKEN` |
| 4 | `X-Frame-Options: SAMEORIGIN` en middleware (línea 37) vs `DENY` en doc (línea 575). SAMEORIGIN es menos restrictivo. | bajo | Documentación | Decidir si SAMEORIGIN es aceptable o cambiar a DENY |
| 5 | No hay CSP para WebSocket/SignalR — `connect-src 'self'` puede bloquear conexiones si se sirven desde subdominio distinto. | bajo | Backend | Verificar que SignalR funcione con CSP actual |
| 6 | `AddApplicationPart` redundante en `Program.cs:42` | bajo | Config | Eliminar |

## 7. Agente de Infraestructura y DevOps (Web + Android)

### Alcance
Revisar deploy web, CI/CD para Android, monitoreo, logs, backups, escalabilidad, y Play Store readiness.

### Verificaciones (Web - mismas que antes)
- [ ] **Deploy script** — funciona de principio a fin (git pull → build → publish → restart)
- [ ] **Rollback plan** — ¿cómo se vuelve a versión anterior si algo falla?
- [ ] **Systemd service** — `Restart=always`, `RestartSec=5`, `TimeoutStopSec=30`
- [ ] **Monitoreo** — ¿hay alertas si el servicio cae? (uptimerobot, healthchecks.io, etc.)
- [ ] **Backups** — ¿base de datos se respalda automáticamente? frecuencia, retención
- [ ] **Log rotation** — logs de nginx y systemd tienen rotation configurado
- [ ] **SSL renew** — certbot auto-renovación funcionando (`certbot renew --dry-run`)
- [ ] **Recursos** — CPU <70%, RAM <80%, disco <80%
- [ ] **Updates** — `apt list --upgradable` paquetes pendientes
- [ ] **Swap** — `swapon --show` (0B actualmente, quizás necesite swap)
- [ ] **Fail2ban jail** — configurado para nginx-auth y sshd

### Verificaciones (Android - Play Store)
- [ ] **AAB firmado** — `./gradlew bundleRelease` genera AAB firmado correctamente
- [ ] **Play Console** — app publicada con `versionCode=2`, `versionName=1.1`
- [ ] **Ficha de Play Store** — descripción, capturas de pantalla, icono (512x512), feature graphic (1024x500)
- [ ] **Política de datos** — enlace a política de privacidad en Play Console
- [ ] **API levels** — minSdk=28 (Android 9), targetSdk=36 (Android 16)
- [ ] **Permisos** — solo INTERNET, POST_NOTIFICATIONS, USE/SCHEDULE_EXACT_ALARM
- [ ] **Android App Bundles** — dynamic delivery, Play Asset Delivery si hay assets grandes
- [ ] **Testing en dispositivos reales** — probar en Android 9, 12, 14, 16 (beta)
- [ ] **Pantallas grandes** — probar en tablet (≥600dp), plegable, ChromeOS si aplica
- [ ] **TalkBack** — revisar guía en `AuditoriaUX.md` sección TalkBack
- [ ] **Política de "Bad Behavior"** — no hay contenido generado por usuario que pueda violar políticas
- [ ] **Revisión de Google** — tiempo estimado de revisión, checklist pre-publicación
- [ ] **Pre-launch report** — ejecutar en Play Console y revisar crash/ANR

### Verificaciones (CI/CD)
- [ ] **GitHub Actions** — ¿existe workflow de CI para web? ¿para Android?
- [ ] **Build automático** — ¿al pushear a main se genera AAB automáticamente?
- [ ] **Tests automáticos** — ¿se ejecutan unit tests en CI?
- [ ] **Deploy automático web** — ¿CI deploya automáticamente al VPS? (actualmente manual)

### Mejoras propuestas
- **GitHub Actions CI/CD** — auto-build + auto-deploy web al pushear a main
- **GitHub Actions Android** — build + test + upload AAB a Play Console (Google Play GitHub Action)
- **Fastlane** — automatizar subida a Play Store (screenshots, release notes, version bump)
- **Docker** — contenerizar web para entornos consistentes
- **Docker Compose** — web + nginx + mysql + redis
- **Prometheus + Grafana** — métricas de app (request rate, latency, errors)
- **Sentry** — error tracking en producción con stack traces (web + Android)
- **CDN** — Cloudflare o similar para assets estáticos y DDoS protection
- **Blue-green deployment** — dos instancias para zero-downtime deploys
- **Auto-scaling** — si el VPS se queda corto, plan de migración a cloud (AWS/GCP/Azure)

---

## 8. Agente de Testing y Casos de Uso Reales

### Alcance
Simular usuarios reales desde distintas perspectivas: nuevo usuario, usuario avanzado, usuario malicioso, usuario con mala conexión, y **usuario multiplataforma** (web + móvil simultáneo).

### Perfiles y guiones

#### A. Nuevo usuario solo web ("María, 25 años, nunca usó Pomodoro")
1. Ingresa a `https://app.epycus.es`
2. Crea cuenta (registro con email y contraseña)
3. Explora landing page
4. Inicia su primer Pomodoro (usa los defaults)
5. Completa 1 ciclo focus + 1 descanso corto
6. Revisa su progreso (vacío, solo 1 sesión)
7. Cierra sesión

Métricas: tiempo de registro, claridad de UI, errores en el camino, ¿entendió cómo funciona?

#### B. Nuevo usuario solo móvil ("Ana, descubre la app en Play Store")
1. Descarga app de Play Store (o sideload AAB)
2. Abre app → Splash → Login/Registro
3. Se registra con email (o Google Sign-In)
4. Completa onboarding (selecciona carrera, personaje)
5. Inicia Pomodoro desde la app
6. Completa 2 ciclos (focus + break ×2)
7. Explora tabs: Inicio, Hábitos, Misiones, Diario, Perfil
8. Revisa su progreso/estadísticas

Métricas: tiempo de registro, errores de login Google, bugs visuales en la UI móvil

#### C. Usuario avanzado multiplataforma ("Carlos, 3 meses usando la app en web y móvil")
1. **Web**: Login con Google OAuth
2. **Web**: Configura meta diaria a 8 sesiones, descanso largo cada 4
3. **Web**: Inicia Pomodoro, completa 2 ciclos
4. **Móvil**: Abre app, verifica que las estadísticas muestran 2 ciclos completados hoy
5. **Móvil**: Inicia Pomodoro desde donde quedó (sesión activa del paso 3)
6. **Móvil**: Completa 2 ciclos más + descanso largo
7. **Web**: Recarga página, verifica 4 ciclos completados y descanso largo registrado
8. **Móvil**: Cambia tema a "Noche Épica"
9. **Web**: Recarga, verifica que tema NO cambió (el tema es local a cada plataforma, o debería sincronizarse?)
10. **Móvil**: Crea 2 hábitos nuevos
11. **Web**: Recarga hábitos, verifica que aparecen los nuevos
12. **Web**: Marca 1 hábito como completo
13. **Móvil**: Verifica hábito aparece como completado

#### D. Usuario con mala conexión ("Pedro, 3G inestable")
1. **Web**: Simular conexión lenta en DevTools (Network > throttling > Slow 3G)
2. Navegar a Pomodoro
3. Iniciar sesión — verificar que el timer no se desincroniza
4. Mientras carga, verificar que aparezca indicador de carga
5. Forzar desconexión (offline mode) durante un focus
6. Reconectar — verificar que el estado del timer sea correcto (no negativo)
7. Verificar service worker entrega assets cacheados mientras offline
8. **Móvil**: Activar modo avión durante un Pomodoro
9. **Móvil**: Verificar que timer local sigue corriendo (no depende de red)
10. **Móvil**: Desactivar modo avión → app sincroniza ciclo completado con servidor
11. **Móvil**: Verificar que la racha se actualizó correctamente

#### E. Usuario malicioso ("Hacker")
1. Intentar SQL injection en campos de login (web y móvil API)
2. Intentar XSS en campos de nombre de hábito (web)
3. Intentar CSRF (POST sin token) en web
4. Intentar acceder a API sin autenticación (web y móvil)
5. Enviar request masivos a login (rate limiting test)
6. Probar path traversal en URLs
7. **Móvil**: interceptar tráfico con mitmproxy/Charles → intentar modificar requests
8. **Móvil**: extraer APK → decompilar con Jadx → buscar secrets hardcodeados

### Entregables
- Resultado de cada guión (✅ éxito / ❌ fallo / ⚠️ parcial)
- Capturas de pantalla de errores visuales (web + móvil)
- Logs relevantes de consola, red, y VPS
- Tiempo de cada flujo
- Bugs encontrados con severidad
- Matriz de compatibilidad cross-platform

---

## Formato de Reporte Final

Para cada agente, entregar:

```markdown
## [Agente] — Resumen

### ✅ Funcionando correctamente
- Item 1
- Item 2

### ❌ Errores encontrados
| # | Descripción | Severidad | Módulo | Archivo/Línea | Solución propuesta |
|---|---|---|---|---|---|
| 1 | ... | alta/media/baja | Pomodoro | ... | ... |

### ⚠️ Advertencias / Mejoras
| # | Descripción | Impacto | Módulo | Propuesta |
|---|---|---|---|---|
| 1 | ... | medio | ... | ... |

### 🚀 Nuevas funcionalidades propuestas para módulos existentes
| Módulo | Funcionalidad | Prioridad | Esfuerzo estimado | Plataforma |
|---|---|---|---|---|
| Pomodoro | Sincronización cross-device (SignalR + FCM) | alta | 2 semanas | Web + Móvil |
| Pomodoro | Timer en background con WorkManager | alta | 1 semana | Móvil |
| Pomodoro | Modo "no molestar" automático durante focus | media | 3 días | Web + Móvil |
| Hábitos | Recordatorios push (FCM) para hábitos pendientes | media | 1 semana | Móvil |
| Hábitos | Vista semanal/mensual con calendario | baja | 2 semanas | Web + Móvil |
| Diario | Prompt diario con IA personalizado | media | 1 semana | Web + Móvil |
| Diario | Análisis de ánimo semanal con gráficos | baja | 1 semana | Web + Móvil |
| Perfil | Foto de perfil personalizada (cámara/galería) | baja | 1 semana | Móvil |
| Perfil | Vinculación con Google Fit / Health Connect | baja | 2 semanas | Móvil |
| IA Chat | Streaming de respuestas (SSE) | media | 1 semana | Web + Móvil |
| IA Chat | Contexto de bienestar en prompt | baja | 3 días | Web + Móvil |
| Misiones | Sub-misiones y checklist | baja | 1 semana | Web + Móvil |
| Autenticación | Biometric unlock (huella/rostro) | media | 1 semana | Móvil |
| Autenticación | Modo invitado con datos locales | baja | 1 semana | Web + Móvil |

### 🆕 Nuevos módulos propuestos
| Módulo | Descripción | Prioridad | Esfuerzo | Justificación | Plataforma |
|---|---|---|---|---|---|
| Gamificación | Logros, puntos, niveles por constancia | alta | 2 semanas | Aumenta retención | Web + Móvil |
| Comunidad/Foros | Grupos de estudio, retos entre amigos | media | 4 semanas | Engagement social | Web + Móvil |
| Calendario/Planning | Vista mensual con planificación de sesiones | media | 3 semanas | Organización | Web + Móvil |
| Exportación/Reportes | PDF o CSV del historial personal | baja | 1 semana | Utilidad para el usuario | Web |
| Widget Pomodoro | Widget en pantalla de inicio Android | baja | 1 semana | Acceso rápido | Móvil |
| Wear OS | App companion para reloj (timer Pomodoro) | baja | 3 semanas | Diferenciación | Móvil |
| Dashboard avanzado | Métricas: productividad semanal, horas focales, tendencias | media | 2 semanas | Insight para el usuario | Web + Móvil |
| Modo oscuro automático | Cambio según hora del día (no manual) | baja | 2 días | UX | Web + Móvil |
| PWA offline-first | Shell cache + sincronización background | media | 3 semanas | Experiencia sin red | Web |
| Temas personalizados | Editor de colores/fondo (no solo sakura/noche) | baja | 2 semanas | Personalización | Web + Móvil |

### 📊 Métricas de rendimiento
- Tiempo promedio de carga: X ms
- Endpoints más lentos: ...
- Uso de memoria: ...
- Consultas DB lentas: ...

### 📋 Checklist de seguridad
- [ ] Headers HTTP
- [ ] SSL
- [ ] Rate limiting
- [ ] etc.
```

---

## Instrucciones de Ejecución

1. Lanzar **un agente por área** en paralelo (8 agentes total).
2. Cada agente tiene **45 minutos** de tiempo de ejecución (30 es poco para cubrir web + móvil).
3. **Agente 5 (Conexión Móvil-Web)** debe ejecutarse después de que los agentes 1 y 3b terminen, porque necesita sus hallazgos de endpoints y arquitectura móvil para probar consistencia.
4. **Agente 8 (Testing)** es el último y consume los hallazgos de todos los anteriores.
5. Compartir hallazgos cross-area (ej: un bug visual puede tener causa en backend).
6. Al finalizar, consolidar todos los reportes en un documento único.
7. Priorizar bugs por severidad (alta = bloqueante para usuarios, media = afecta experiencia, baja = cosmético).
8. Para cada bug de alta severidad, proponer fix inmediato con estimación en horas/días.
9. **Android**: para probar la app móvil se necesita:
   - Un dispositivo Android (real o emulador) con Android 9+
   - ADB para capturar logs (`adb logcat -s "EpycusApp"`)
   - La APK release o debug instalada
   - Charles Proxy / mitmproxy para interceptar tráfico (opcional, para seguridad)
10. **Dependencias Android SDK**: el proyecto usa compileSdk 36, asegurar que el SDK correspondiente esté instalado (via Android Studio SDK Manager o `sdkmanager`).
