# PROMPT PARA IA: COMPLETAR EPYCUSAPP (CERO ERRORES, COBERTURA TOTAL)

Eres una IA avanzada con acceso a internet, capaz de leer, analizar, modificar código y conectarte por SSH. Tu misión es llevar EpycusApp a **cobertura de tests completa (unitarios + aceptación + E2E) y cero errores de compilación/ejecución**.

---

## 1. CONTEXTO DEL PROYECTO

### Backend (ASP.NET Core 9 + MVC + WebAPI)
- Ruta local: `C:\Users\marco\Pictures\EpycusApp\`
- Solución: `EpycusApp.slnx`
- Proyecto principal: `EpycusApp.csproj`
- Tests unitarios: `EpycusApp.Tests\` (xUnit + Moq + FluentAssertions + EF InMemory)
- Tests aceptación C#: `EpycusApp.AcceptanceTests\` (xUnit + Playwright + WebApplicationFactory)
- Tests E2E Playwright: `tests\` (TypeScript, playwright.config.ts)

### Android (Kotlin + Gradle)
- Ruta local: `C:\Users\marco\Pictures\Epycus\`
- Build system: Gradle Kotlin DSL
- SDK mínimo: API 26 (Android 8.0)

### Servidor producción (VPS)
- IP: `147.93.119.193`, Puerto SSH: `2222`
- Usuario: `root`, Password: `MarcoCastillo123-`
- Host key: `ssh-ed25519 255 SHA256:Ps0Bo+yf84fE+SjjDdrhtfQhN52raugF4qhBWef+njc`
- Web: `https://app.epycus.es` (nginx proxy → `localhost:5000`)
- Service: `epycus-web` (systemd)
- BD: MariaDB `epicus_db`, usuario `epicus_user`, password `epycusDb123`

### Tools disponibles
- `dotnet build/publish/test`
- `npx playwright test`
- Gradle wrapper (`gradlew` en Android)
- `plink` / `pscp` para SSH/SCP
- `curl` para pruebas HTTP

---

## 2. OBJETIVOS (en orden de prioridad)

### 2.1. Cobertura de tests unitarios
Escribir tests xUnit para TODOS los servicios y controladores que no los tienen:

**Servicios sin tests** (crear en `EpycusApp.Tests/Unitarios/Servicios/`):
- ServicioAutenticacion (parcial en integración, necesita unitarios puros)
- ServicioIA
- ServicioDiarioAnimo
- ServicioCache
- ServicioAuditoria
- ServicioCorreo
- ProveedorGemini
- ProveedorDeepSeek
- ConstructorContextoIA

**Controladores MVC sin tests** (crear en `EpycusApp.Tests/Unitarios/Controladores/`):
- AdminController (11 actions)
- AjustesController (5 actions)
- BienestarController (2 actions)
- DiarioAnimoController (3 actions)
- IaController (5 actions)
- MisionesController (9 actions)
- PerfilController (5 actions)
- PomodoroController (3 actions)

**Controladores API sin tests** (crear en `EpycusApp.Tests/Integracion/`):
- ApiAdminController (8 actions)
- ApiBienestarController (8 actions)
- ApiDashboardController (2 actions)
- ApiDiarioController (8 actions)
- ApiEstadoAnimoController (2 actions)
- ApiGamificacionController (2 actions)
- ApiIaController (7 actions)
- ApiMisionesController (15 actions)
- ApiPerfilController (7 actions)
- ApiProgresoController (3 actions)

**Seguir el patrón existente:**
- Usar `DbContextFactory` para crear contexto InMemory
- Usar `Mock<>` para dependencias (servicios de correo, logging, etc.)
- Usar `FluentAssertions` para asserts
- Usar `[Fact]` y `[Theory]` según corresponda

### 2.2. Cobertura de tests de aceptación (E2E C#)

Completar los tests de aceptación existentes en `EpycusApp.AcceptanceTests\`:
- `RegistroYLoginTests.cs` - cubrir registro + login + refresh token + cierre sesión
- `GestionHabitosTests.cs` - CRUD hábitos + completar + rachas
- `GestionMisionesTests.cs` - CRUD misiones + subtareas + progreso
- `ProgresoYPerfilTests.cs` - XP, niveles, logros, perfil, personaje

Usar `CustomWebApplicationFactory` + `AcceptanceFixture` + `AcceptanceTestCollection` (patrón existente).

### 2.3. Tests E2E Playwright (TypeScript)

Completar los tests en `tests/`:
- `auth.spec.ts` (6 tests) - ya existe y pasa
- `admin.spec.ts` - login admin, CRUD frases, listar usuarios
- `dashboard.spec.ts` - carga post-login, redirección sin auth
- `habitos.spec.ts` - crear, listar, completar hábitos
- `misiones.spec.ts` - crear, listar, progreso misiones
- `pomodoro.spec.ts` - iniciar, pausar, completar pomodoro
- `bienestar.spec.ts` - bienestar + diario ánimo
- `ia.spec.ts` - cargar IA, enviar mensaje, ver respuesta
- `perfil.spec.ts` - cargar perfil, cambiar settings
- `general.spec.ts` - health, sitemap, robots, 404, privacidad

### 2.4. Arreglar bugs existentes

1. **Gemini API degradada**: Cambiar provider por defecto en appsettings.json de "Gemini" a "DeepSeek". Verificar que `AI:Provider` en el servidor también se actualice.

2. **WriteBackQueue Android**: Implementar cola offline real en `app/src/main/java/es/epycus/app/util/WriteBackQueue.java`:
   - Almacenar operaciones pendientes en SQLite local (Room)
   - Reintentar sync cuando haya conexión
   - Notificar al usuario del estado de sync
   - No perder datos si la app se cierra

3. **Migración BD pendiente**: El log muestra `"The model for context 'ContextoAplicacion' has pending changes. Add a new migration before updating the database."`. Crear nueva migración con `dotnet ef migrations add` y aplicarla en el VPS.

4. **SignalR Android**: Verificar que `SignalRService.java` compile correctamente con signalr 7.0.0 (ya corregido, verificar build completo).

### 2.5. Auditoría de seguridad y rendimiento

- Verificar rate limiting en todas las APIs
- Verificar validación de entrada en todos los endpoints
- Verificar SQL injection (EF Core ya protege, pero revisar consultas raw)
- Verificar JWT blacklist funciona correctamente
- Verificar CSP headers en todas las respuestas
- Buscar fugas de información en respuestas de error
- Verificar que las contraseñas de la BD y JWT en appsettings.json del VPS son las correctas (las variables de entorno del systemd service deben prevalecer)

---

## 3. REGLAS Y CONVENCIONES

### Código
- NO agregar comentarios a menos que sea estrictamente necesario
- Seguir el estilo existente del código (nombres en español, PascalCase para clases/métodos, camelCase para parámetros)
- NO modificar `.gitignore`
- NO añadir emojis a ningún archivo
- NO crear archivos de documentación (README.md, etc.) a menos que se solicite explícitamente

### Tests
- No modificar tests existentes que ya pasan
- Un test por escenario, no múltiples asserts en un solo test (excepto para validar respuesta completa)
- Tests deben ser independientes entre sí
- Usar `[Trait("Categoria", "...")]` para categorizar tests

### Android
- Usar Kotlin + corrutinas para operaciones async
- Room para persistencia local
- Retrofit para llamadas HTTP
- SignalR 7.0.0 Java para tiempo real
- Seguir el patrón MVVM existente

### SSH y despliegue
- Usar `plink -ssh -P 2222 -pw "MarcoCastillo123-" -hostkey "ssh-ed25519 255 SHA256:Ps0Bo+yf84fE+SjjDdrhtfQhN52raugF4qhBWef+njc" root@147.93.119.193 "comando"`
- Usar `pscp` para copiar archivos
- Para reiniciar: `systemctl restart epycus-web`
- Para ver logs: `journalctl -u epycus-web --since "10 min ago" --no-pager -l`
- Para MySQL: `mysql -uepicus_user -pepycusDb123 epicus_db -e "QUERY"`
- Para editar archivos en VPS: usar `sed -i` en scripts subidos con pscp

---

## 4. MÉTRICA DE ÉXITO

```bash
# 1. Build sin errores
dotnet build -c Release

# 2. Tests unitarios pasan
dotnet test EpycusApp.Tests -c Release --no-build

# 3. Tests aceptación C# pasan
dotnet test EpycusApp.AcceptanceTests -c Release --no-build

# 4. Tests Playwright pasan
npx playwright test

# 5. Android compila
cd C:\Users\marco\Pictures\Epycus
./gradlew assembleDebug

# 6. Despliegue en VPS funciona
curl -s https://app.epycus.es/health | jq .

# 7. Admin login funciona
curl -s -X POST https://app.epycus.es/api/v1/admin/login \
  -H "Content-Type: application/json" \
  -d '{"correo":"admin@epycus.es","contrasena":"Admin123@"}' | jq .

# 8. Cero errores en logs del VPS
journalctl -u epycus-web --since "5 min ago" --no-pager -l | grep -i "fail"
```

---

## 5. ENTREGABLES FINALES

1. **Código completo** con todos los tests escritos
2. **VPS actualizado** con últimas correcciones
3. **Build Android** compilado sin errores (APK en `app/build/outputs/apk/debug/`)
4. **Suite completa de tests pasando** (tanto local como en servidor)
5. **Sin warnings** de migración BD pendiente
6. **Provider IA funcional** (DeepSeek como default)

---

## 6. PUNTOS CRÍTICOS A RECORDAR

- La BD real del VPS usa la password `epycusDb123` (vía variable de entorno, NO la del appsettings.json)
- La appsettings.json del VPS tiene un Sentry DSN vacío (ya corregido)
- La columna de password en la BD es `ContrasenaHash` (NO `HashContrasena`)
- Rate limiting: Auth 20 req/min, Global 600 req/min
- CAPTCHA Turnstile en formularios web, pero las APIs no lo requieren
- Playwright tests usan login vía API + cookies para evadir CAPTCHA
- El usuario admin es `admin@epycus.es` / `Admin123@`
- La app Android usa signalr 7.0.0 Java (API específica, no compatible con versiones 8+)

---

**IMPORTANTE**: No marques nada como completado hasta que TODAS las métricas de éxito (sección 4) pasen sin errores. Trabaja de forma iterativa: corrige errores de compilación primero, luego tests unitarios, luego tests de aceptación, luego E2E, y finalmente despliega.
