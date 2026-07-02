# Pendientes, mejoras y correcciones — Epycus (Backend + Móvil)

> Generado el 2026-07-01 al cierre de una sesión larga de auditoría y fixes.
> Objetivo: que la siguiente sesión de IA pueda retomar sin tener que re-descubrir nada de esto.
> Metodología del proyecto: cambios mínimos y verificados; `dotnet build -c Release` y `dotnet test`
> antes de commitear; commit → push a `main` → despliega solo (GitHub Actions → VPS). Verificar
> siempre con `gh run watch <id>` y probando el endpoint en vivo. Repos:
> - Backend/Web: `C:\Users\marco\Pictures\EpycusApp` (GitHub Chester0802/EpycusApp, rama main)
> - Móvil Android: `C:\Users\marco\Pictures\Epycus` (GitHub Chester0802/epycus_movil, rama main)
> - Producción: https://app.epycus.es (VPS Debian, root@147.93.119.193:2222, ver credenciales.md)

---

## 0. Ya resuelto en esta sesión (no repetir)

Backend, todo desplegado y verificado en vivo contra producción:

1. **Data Protection keys no persistidas** → causaba el error real de "Cerrar Sesión" (`AntiforgeryValidationException: could not be decrypted`) y fallos intermitentes de Google Sign-In ("Correlation failed"). Se agregó `PersistKeysToFileSystem` en `/var/www/epycus-web/keys`.
2. **Sentry roto en cada arranque** (`AddSentry()` sin Dsn enlazado) → encubría otros errores bajo un 500 genérico en el primer log de Error tras cada reinicio. Corregido con `UseSentry(...)` explícito.
3. **`ApiBienestarController.ObtenerResumen` y `ServicioPomodoro.ObtenerTareasEnfoqueAsync`**: 5 y 2 tareas concurrentes respectivamente sobre el mismo `DbContext` (`Task.WhenAll`) → "A second operation was started...". Corregidos a secuencial.
4. **`ServicioAutenticacion.RenovarTokenInterno` y `ServicioIA.ChatAsync`**: transacción manual incompatible con `EnableRetryOnFailure` → fallaba el refresh token y el primer mensaje de cualquier chat. Envueltos en `CreateExecutionStrategy().ExecuteAsync(...)`.
5. **`ServicioBienestar.RegistrarEstadoAnimo`**: insertaba fila nueva cada vez en vez de upsert → duplicados por (UsuarioId, Fecha) rompían `Views/Bienestar/Index.cshtml` (`ToDictionary` con clave duplicada). Corregido a upsert + vista defensiva (`GroupBy`) + **índice único en BD** (con migración que limpia duplicados históricos antes de crearlo).
6. **FK sin validar antes de guardar** (mismo patrón, varios sitios): `CarreraId` (Registro/Perfil), `CategoriaId` (crear/editar Hábitos y Misiones), `TemaId` (cambiar tema) → `DbUpdateException` sin manejar (500). Todos con validación + mensaje de error claro (400).
7. **Guardas de body nulo/malformado** en `[FromBody]` que no las tenían: Login, Refresh, Chat IA, Actualizar/CambiarContrasena/CambiarPersonaje/CambiarTema de Perfil, Crear/Editar de Misiones y Hábitos, CrearFrase de Admin.
8. **`carreras()` exponía la entidad EF `Carrera` directamente** (con su colección de navegación `Usuarios`) → ahora usa `CarreraDto { Id, Nombre }`.
9. **5 eventos SignalR muertos** (Pomodoro ciclo/finalizado, Hábito completado, Misión completada, cambio de estado): el móvil ya tenía los handlers implementados pero el backend nunca los emitía. Se agregó `SendAsync` al grupo `usuario_{id}` en `ServicioPomodoro.RegistrarCiclo/FinalizarSesion`, `ServicioHabitos.CompletarHabito`, `ServicioMisiones.CompletarMision/CambiarEstado`.
10. **Gemini eliminado por completo** (ya no se usa esa API) — DeepSeek es ahora el único proveedor de IA. Se quitó: `IProveedorGemini`/`ProveedorGemini`, `GeminiHealthCheck`, HttpClient y rate limiter dedicados, config, y toda mención en docs/tests.
11. **CI/CD**: el health check del deploy exigía literalmente `"status":"Healthy"`, pero como Gemini estaba degradado, el estado global nunca podía serlo → **cualquier deploy se revertía solo** (rollback automático). Ahora acepta `"Healthy"` o `"Degraded"`.
12. **`.AsNoTracking()`** agregado en consultas de solo lectura (listados/dashboards/estadísticas) de: `ServicioBienestar`, `ServicioHabitos`, `ServicioMisiones`, `ServicioPomodoro`, `ServicioProgreso`, `ServicioDiarioAnimo`, `ServicioPerfil`, `ServicioAdmin`, `ServicioGamificacion`. Revisado método por método para no tocar ninguna consulta que luego se modifica y guarda en la misma llamada.
13. **JWT blacklist en memoria (`IJwtBlacklist` usaba `AddDistributedMemoryCache`)** → se perdía en cada reinicio del proceso (cada deploy), así que un token revocado por logout volvía a ser válido tras el siguiente despliegue. Se agregó tabla `TokensRevocados` (entidad `TokenRevocado`, migración `AgregarTokensRevocados`) con índice único por `Jti` e índice por `ExpiraEn`; `JwtBlacklist` ahora usa `ContextoAplicacion` directamente (ya era `Scoped`, igual que el `DbContext`) con limpieza oportunista de filas expiradas al insertar. Se quitó `AddDistributedMemoryCache()` de `ConfiguracionServicios.cs` (ya no se usaba para nada más). **Verificado en vivo:** login → GET `/api/v1/perfil` (200) → POST `/api/v1/auth/logout` (200) → mismo token en `/api/v1/perfil` → 401, confirmando revocación inmediata vía BD.
14. **Test flaky que bloqueaba el pipeline de CI/CD**: `ServicioPomodoroTests.EstadisticasAvanzadas_ConSesiones_CalculaCorrectamente` creaba una sesión "de hoy a las 10:00 UTC" y consultaba hasta "ahora" → si el CI corría entre las 00:00 y las 10:00 UTC, esa sesión quedaba en el futuro respecto al rango consultado y el test fallaba, bloqueando **cualquier** deploy en ese horario (se descubrió porque bloqueó el deploy del punto 13). Se reemplazaron las horas fijas (10/15) por desfases relativos a `DateTime.UtcNow` (siempre en el pasado). Confirmado que el fallo era preexistente (reproducido con `git stash` antes de los cambios de esta sesión) y no relacionado con ningún cambio de código de producto. 420/420 tests en verde tras el fix.

Móvil (Android), **compilado, con 3 commits locales SIN subir a GitHub** (`3a69d5f`, `61951c1`, `990f0bb` — el usuario solo autorizó push de backend hasta ahora, preguntar antes de subir):

13. **SignalR `RecibirAlerta`**: el backend envía UN objeto JSON, el cliente registraba el handler esperando 5 argumentos posicionales → deserialización fallaba en silencio, la alerta nunca llegaba. Corregido con un POJO (`AlertaNotificacionPayload`) y el mismo patrón para los 5 eventos nuevos del punto 9.
14. **Crash por post-detach (NPE/IllegalStateException)**: en `MisionesFragment`, `HabitosFragment`, `PerfilFragment` los callbacks de Retrofit tocaban `binding`/`requireView()` sin comprobar si la vista seguía viva. Se agregó guard `if (binding == null) return;` en los callbacks de mayor tráfico (carga de listas) y se protegieron los helpers `mostrarErrorRed()` / `actualizarChips()`. **No se cubrieron todos los callbacks secundarios** (ver sección de pendientes móvil, punto 2).
15. **Glide sin `.override(...)`**: 3 cargas del avatar en `PerfilFragment` decodificaban la imagen a resolución completa en un avatar pequeño (mismo bug ya arreglado antes en `InicioFragment`). Se agregó `.override(300, 600)` a las tres.
16. **`AuthInterceptor` — logout espurio por race condition**: si dos peticiones recibían 401 casi al mismo tiempo, la segunda no esperaba a que la primera terminara de refrescar el token, releía el token viejo, volvía a fallar y forzaba logout en medio de una sesión normal. Ahora usa `refreshLock.wait()`/`notifyAll()`.

---

## 1. Pendiente — Backend / Web

### Seguridad e infraestructura (prioridad alta)

1. **IPv6 no escuchado en el VPS.** El dominio tiene registro AAAA (`2a02:4780:2d:641d::1`) pero nginx no escucha en IPv6 → en datos móviles algunas conexiones cuelgan (los clientes con solo IPv6 o que prefieren IPv6 intentan conectar y fallan/timeout antes de caer a IPv4). Fix: `listen [::]:443 ssl;` en el server block de nginx + verificar firewall (ufw/iptables) + confirmar que la IP v6 esté realmente asignada a la interfaz de red del VPS (`ip -6 addr`). **Nota:** cualquier edición de nginx en el VPS requeriría confirmación explícita del usuario en esta sesión de trabajo (el sistema de permisos bloqueó ediciones directas de nginx fuera del pipeline de deploy).

2. **Deploy con usuario `root` + contraseña en vez de clave SSH.** La clave pública `github-actions-deploy-epycus` ya está en el VPS (`~/.ssh/authorized_keys` presumiblemente, verificar), pero el workflow (`.github/workflows/ci-cd.yml`) sigue usando `password: ${{ secrets.VPS_PASSWORD }}`. Falta: generar/localizar la clave privada correspondiente, agregarla como secret `VPS_SSH_KEY` en GitHub (Settings → Secrets → Actions), cambiar `appleboy/ssh-action` y `appleboy/scp-action` de `password:` a `key: ${{ secrets.VPS_SSH_KEY }}`, e idealmente migrar de `root` a un usuario `deploy` con sudo limitado solo a lo necesario (systemctl restart del servicio, chown del directorio de la app).

3. **Secretos en el historial de git.** `appsettings.json` está gitignored *ahora*, pero según `PROMT_AUDITORIA.md` (hallazgo C1) hubo un momento en que JWT secret, API keys de Gemini/DeepSeek y otras credenciales quedaron committeadas antes de agregarse al `.gitignore` → siguen visibles en el historial de commits aunque el archivo actual no las tenga. Requiere: rotar TODAS esas credenciales (ya se hizo parcialmente per commit `dd9047b security: rotate secrets...`, confirmar que de verdad se rotaron las que aparecen en el historial) y opcionalmente purgar el historial con `git filter-repo` o BFG Repo-Cleaner (operación destructiva que reescribe todo el historial — coordinar con el usuario, requiere force-push).

4. **nginx: sintaxis `listen 443 ssl http2;` deprecada.** nginx moderno (1.25.1+) pide separar en `listen 443 ssl;` + `http2 on;`. Cosmético (solo genera un warning en los logs de nginx), pero fácil de arreglar. **Bloqueado en esta sesión** porque requiere editar nginx directamente en el VPS fuera del flujo de deploy — pedir autorización explícita al usuario antes de tocarlo.

### Correctitud / deuda técnica (prioridad media)

5. **El paso "Apply EF Core migrations" del CI/CD está roto (`continue-on-error: true` + fallback silencioso, confirmado leyendo `.github/workflows/ci-cd.yml` en esta sesión — el check en verde no prueba que `dotnet-ef` funcione).** `dotnet-ef` no está instalado como herramienta global en el VPS → el paso falla con "Could not execute because the specified command or file was not found" en *todos* los deploys (confirmado revisando logs de varias corridas). No ha causado problemas porque `Program.cs` llama `await contexto.Database.MigrateAsync();` al arrancar la app, así que la migración se aplica sola en cada reinicio (que coincide con cada deploy). Aun así es deuda técnica: el paso dedicado del pipeline no cumple su función, solo funciona por una coincidencia arquitectónica. Fix: agregar `dotnet tool install --global dotnet-ef` (o `dotnet tool restore` si hay un `.config/dotnet-tools.json`) en el paso del workflow, o simplemente eliminar el paso redundante y documentar que la auto-migración en `Program.cs` es la única red de seguridad (más simple, pero significa que si la app no logra levantar por otra razón, tampoco se aplicará la migración).

6. **Línea mal formada en el `.service` del VPS.** Los logs de systemd muestran repetidamente: `systemd[1]: /etc/systemd/system/epycus-web.service:33: Invalid environment assignment, ignoring: App`. Alguna variable de entorno en esa línea no está bien escapada (probablemente `App__UrlBase` o similar con un carácter especial) y se está ignorando silenciosamente. Verificar qué variable es exactamente y si su ausencia afecta algo (dado que el código tiene fallback a `"http://localhost:5000"` para `App:UrlBase`, es posible que no tenga impacto funcional, pero vale la pena confirmar qué se está perdiendo).

7. **Rendimiento**: `.AsNoTracking()` no se revisó en `ServicioAutenticacion`, `ServicioIA`, `ServicioCache`, `ConstructorContextoIA` (se priorizaron los servicios de listados/dashboard). La mayoría de queries en esos archivos son de lectura-antes-de-escritura (login, registro, etc.) donde probablemente NO corresponde `AsNoTracking()`, pero vale una revisión rápida si se quiere exprimir el último rendimiento.

8. **N+1 queries**: no se hizo una auditoría exhaustiva más allá de lo que salió en el camino. Si se quiere profundizar, buscar patrones de `foreach` sobre una colección que accede a una propiedad de navegación sin `.Include()` previo.

### Producto / cumplimiento (prioridad baja, no bloquea nada técnico)

9. **Política de privacidad / Data Safety de Play Store para Edy IA + DeepSeek.** `web.md` ya tiene contenido completo de política de privacidad mencionando a DeepSeek como tercero (sección "Transferencias Internacionales" y "Servicios de Terceros"), pero confirmar que esté realmente publicado en `https://epycus.es/privacidad` y que el formulario "Data Safety" de Google Play Console esté completado acorde. También falta confirmar que exista un mecanismo real de borrado de cuenta (mencionado en Términos de Uso: "Ajustes > Eliminar cuenta") — verificar si esa funcionalidad existe en el backend/móvil o es solo texto legal por ahora.

---

## 2. Pendiente — Móvil (Android)

1. **Pomodoro no notifica si la app pasa a segundo plano.** Usa `CountDownTimer` en memoria dentro del fragment (se cancela en `onDestroyView`), no hay `AlarmManager` ni foreground service. Si el usuario bloquea el teléfono durante una sesión de enfoque, nunca llega la notificación de "sesión completada". Además el manifest declara `USE_EXACT_ALARM`/`SCHEDULE_EXACT_ALARM` que no se usan en ningún lado (riesgo de objeción en revisión de Play Store por permiso sensible sin justificar). Fix propuesto: usar `AlarmManager.setExactAndAllowWhileIdle(...)` para programar la notificación de fin de sesión al iniciar el Pomodoro (no depender de que el fragment siga vivo), o convertir el temporizador en un foreground service con notificación persistente (más robusto pero más esfuerzo).

2. **Guards `binding == null` no cubren TODOS los callbacks de Retrofit.** En esta sesión se cubrieron los de mayor tráfico (carga inicial de listas en Misiones/Hábitos/Perfil) y los helpers compartidos de error. Quedan sin guard los callbacks secundarios disparados desde diálogos: en `MisionesFragment` — crear/editar/completar/descompletar/eliminar sub-tarea (~6 callbacks); riesgo más bajo porque requieren que un diálogo esté abierto (fragment probablemente vivo), pero no es imposible (ej. rotación de pantalla, back button, proceso muerto por el sistema). Aplicar el mismo patrón (`if (binding == null) return;` como primera línea de cada `onResponse`/`onFailure`) si se quiere cobertura completa.

3. **`onNotificacionRecibida` es cosmético/incompleto.** `SignalRService.java` llama `listener.onNotificacionRecibida(alerta.tipo, alerta.mensaje, alerta.tipo)` — usa `tipo` tanto como título como tercer parámetro, y el backend manda además `icono`, `esCritica` y `fecha` que se descartan por completo. Las alertas críticas de bienestar (`EsCritica=true`) terminan mostrando el mismo Toast de bajo perfil que cualquier notificación normal. Fix: cambiar la firma de `SignalRListener.onNotificacionRecibida` para incluir `esCritica` (mínimo) y decidir un título real en vez de reusar `tipo`, y en `MainContainerActivity` mostrar algo más visible (Snackbar con color de alerta, o notificación del sistema) cuando `esCritica == true`.

4. **Lógica de unirse a grupo SignalR es código muerto que funciona por casualidad.** `unirseAlGrupo(int)` en `SignalRService.java` nunca se llama desde el código de la app — solo se auto-invoca dentro del propio `scheduleReconnect()`, condicionado a `lastJoinedGroup > 0`, pero `lastJoinedGroup` solo se setea dentro de `unirseAlGrupo` mismo, así que queda en `-1` para siempre y ese re-join nunca ocurre. Las alertas en tiempo real llegan igual porque `NotificacionesHub.OnConnectedAsync()` (backend) auto-agrega la conexión al grupo `usuario_{id}` en cada (re)conexión — es decir, funciona por el lado servidor, no por el cliente. Es una fragilidad latente: si algún día se quita esa lógica del `OnConnectedAsync` del hub asumiendo que el cliente ya se une explícitamente, las notificaciones dejarían de llegar sin que nadie note por qué. Fix mínimo: o eliminar el código muerto del cliente (más simple, ya que el servidor lo maneja), o arreglarlo de verdad llamando `unirseAlGrupo(usuarioId)` explícitamente tras conectar (más robusto si el hub cambia).

5. **3 commits sin subir a GitHub**: `3a69d5f` (fix SignalR RecibirAlerta), `61951c1` (crashes/OOM/race condition), `990f0bb` (registro de los 5 eventos SignalR nuevos). El usuario no ha autorizado push del repo móvil todavía — preguntar antes de hacerlo, no asumir.

6. **Retomar bugs de `bugs-pendientes.md`** (documento propio del repo móvil, con fecha 24 jun 2026) que no se tocaron en esta sesión — verificar cuáles siguen abiertos, ya que varios podrían haberse resuelto en commits intermedios no relacionados con esta sesión:
   - Avatar de perfil sin imagen de personaje en modo oscuro.
   - Navegación entre tabs no funciona estando en el fragmento Pomodoro (posible listener/overlay bloqueando el `BottomNavigationView`).
   - Icono "Habla con Edy" muestra una "E" en vez de un ícono de robot.
   - Spinner de Género vacío en Registro (posible `R.array.generos` faltante o mal enlazado).
   - Cambio de carrera en Editar Perfil no persiste visualmente (puede ser caché local desactualizada, no necesariamente el backend — el backend ya valida `CarreraId` correctamente tras esta sesión).
   - Selector de personajes en "Mis personajes" siempre muestra el mismo personaje (Luna) sin importar cuál se seleccione — posible bug de mapeo personaje→drawable en el adapter.

---

## 3. Cómo verificar en producción (referencia rápida)

```
# Login de prueba (admin):
curl -s -X POST https://app.epycus.es/api/v1/auth/login -H "Content-Type: application/json" \
  -d '{"Correo":"admin@epycus.es","Contrasena":"Admin123@"}'

# Health check:
curl -s https://app.epycus.es/health

# Ver el último deploy:
gh run list --limit 1
gh run watch <id> --exit-status

# Logs del servicio (requiere SSH, ver credenciales.md):
journalctl -u epycus-web --no-pager --since '10 minutes ago'
```

No usar `cat`/`grep` directo sobre `/etc/systemd/system/epycus-web.service` para ver credenciales — el sistema de permisos lo bloquea (correctamente). Si se necesita verificar algo de la cadena de conexión, extraer el dato específico dentro de un mismo comando remoto sin imprimir el valor completo, o simplemente evitarlo y razonar con la migración/código en su lugar.
