# Nuevos Módulos y Funciones — EpycusApp

> Basado en auditoría completa del proyecto (2026-06-19)
> Prioridad: 🔥 Crítica | ⭐ Alta | 🔵 Media | 🟢 Baja

---

## 📦 Módulo 1: Sistema de Amigos y Social

**Código**: `SOC-`
**Prioridad**: ⭐ Alta
**Esfuerzo**: 3-5 días
**Dependencia de BD**: Nueva entidad `Amistad`, migración

### Descripción
Añadir interacción social entre usuarios: agregar amigos, ver progreso, comparar logros, rankings semanales.

### Funcionalidades
- [ ] Enviar/aceptar/rechazar solicitudes de amistad
- [ ] Leaderboard semanal: XP ganado en la semana entre amigos
- [ ] Ver perfil público de amigos (nivel, racha, logros recientes)
- [ ] Notificaciones de amigos (subió de nivel, logro desbloqueado)

### Archivos a crear
```
Models/Entidades/Amistad.cs
Servicios/Interfaces/IServicioSocial.cs
Servicios/Implementaciones/ServicioSocial.cs
Controllers/SocialController.cs
Views/Social/Index.cshtml
Views/Social/Amigos.cshtml
Views/Social/Rankings.cshtml
```

### Archivos a modificar
```
Datos/ContextoAplicacion.cs (+ DbSet, relaciones)
Datos/Semilla/DatosSemilla.cs (+ datos de prueba)
Program.cs (+ DI)
_ViewImports.cshtml, _Layout.cshtml (+ navegación)
wwwroot/css/social.css (nuevo)
```

---

## 📦 Módulo 2: Notificaciones Push y en Tiempo Real (SignalR)

**Código**: `NOT-`
**Prioridad**: 🔥 Crítica
**Esfuerzo**: 3-4 días
**Dependencia de BD**: Nueva entidad `Notificacion`

### Descripción
Reemplazar las alertas del módulo ODS 3 (que solo se muestran al cargar página) con notificaciones en tiempo real vía SignalR, más notificaciones push para móvil (FCM).

### Funcionalidades
- [ ] Hub SignalR para notificaciones en tiempo real
- [ ] Recordatorio de hábitos pendientes
- [ ] Alerta ODS 3 en tiempo real (ánimo bajo, pomodoro excesivo)
- [ ] Notificación de subida de nivel / logro desbloqueado
- [ ] Recordatorio de Pomodoro completado / descanso
- [ ] (Futuro) Push notifications vía Firebase Cloud Messaging

### Paquetes NuGet
```
Microsoft.AspNetCore.SignalR
FirebaseAdmin (futuro)
```

---

## 📦 Módulo 3: Misiones Diarias y Semanales Automáticas

**Código**: `MDA-`
**Prioridad**: ⭐ Alta
**Esfuerzo**: 2-3 días
**Dependencia de BD**: Nueva tabla `MisionAutomatica` o columna `Tipo` en `Mision`

### Descripción
Generar automáticamente misiones rotativas diarias y semanales para mantener el engagement.

### Funcionalidades
- [ ] Misiones diarias (completar 3 hábitos, 1 pomodoro, registrar ánimo)
- [ ] Misiones semanales (5 hábitos seguidos, 10 pomodoros, 3 días de ánimo positivo)
- [ ] Pool de plantillas de misiones en BD
- [ ] Rotación automática al iniciar día/semana
- [ ] Recompensas especiales por completar todas las misiones diarias del día

### Archivos a crear
```
Models/Entidades/PlantillaMision.cs
Servicios/Interfaces/IServicioMisionesAutomaticas.cs
Servicios/Implementaciones/ServicioMisionesAutomaticas.cs
BackgroundTasks/RotadorMisionesHostedService.cs
```

---

## 📦 Módulo 4: Sistema de Insignias y Coleccionables

**Código**: `COL-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 4-5 días
**Dependencia de BD**: Nuevas entidades `Insignia`, `InsigniaUsuario`, `Coleccion`

### Descripción
Expandir el sistema de logros con insignias coleccionables temáticas, sets de carrera, y álbum de colección.

### Funcionalidades
- [ ] Insignias por carrera universitaria (6-8 por carrera)
- [ ] Sets de insignias con bonificación por colección completa
- [ ] Álbum de colección visible en perfil
- [ ] Insignias por eventos especiales (temporeadas)
- [ ] Insignias ocultas (condiciones sorpresa)
- [ ] Barra de progreso por colección

---

## 📦 Módulo 5: Desafíos y Competiciones

**Código**: `COM-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 4-5 días
**Dependencia de BD**: Nuevas entidades `Desafio`, `ParticipanteDesafio`

### Descripción
Desafíos semanales/mensuales donde los usuarios compiten por objetivos comunes.

### Funcionalidades
- [ ] Desafíos globales (toda la comunidad) con meta de XP colectiva
- [ ] Desafíos individuales (meta personal en N días)
- [ ] Medallas por posición en desafío
- [ ] Temporizador de desafío activo
- [ ] Historial de desafíos completados

---

## 📦 Módulo 6: Sistema de Recompensas y Tienda

**Código**: `TIE-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 5-6 días
**Dependencia de BD**: Nuevas entidades `ProductoTienda`, `Compra`, `InventarioUsuario`

### Descripción
Tienda virtual donde los usuarios pueden gastar su XP acumulado en recompensas cosméticas y funcionales.

### Funcionalidades
- [ ] Moneda virtual (XP acumulado como moneda de gasto)
- [ ] Productos cosméticos: temas de color, borders, iconos, efectos
- [ ] Potenciadores: XP boost 2x por 24h, bonus de racha
- [ ] Backgrounds de perfil personalizables
- [ ] Tienda rotativa (productos que cambian semanalmente)
- [ ] Historial de compras

---

## 📦 Módulo 7: Análisis de Datos y Reportes (Dashboard Analytics)

**Código**: `ANA-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 3-4 días
**Dependencia de BD**: Sin cambios BD (usa datos existentes)

### Descripción
Panel de análisis avanzado con gráficos detallados de rendimiento, tendencias y predicciones.

### Funcionalidades
- [ ] Tendencia de hábitos (últimos 30/90 días)
- [ ] Horas productivas (distribución temporal de pomodoros)
- [ ] Correlación ánimo-productividad
- [ ] Predicción de racha (probabilidad de mantener racha)
- [ ] Reporte semanal auto-generado (PDF o HTML)
- [ ] Heatmap de actividad (similar a GitHub contribution graph)
- [ ] Exportación de datos personales (JSON/CSV)

---

## 📦 Módulo 8: PWA (Progressive Web App)

**Código**: `PWA-`
**Prioridad**: ⭐ Alta
**Esfuerzo**: 2-3 días
**Dependencia de BD**: Ninguna

### Descripción
Convertir la web en PWA instalable para permitir acceso offline parcial y experiencia mobile nativa.

### Funcionalidades
- [ ] `manifest.json` con iconos y colores de marca
- [ ] Service Worker para caché de assets
- [ ] Estrategia offline-first para datos estáticos
- [ ] Pantalla de splash personalizada
- [ ] Notificaciones push (con SignalR)
- [ ] Botón "Instalar app" detectable

### Archivos a crear
```
wwwroot/manifest.json
wwwroot/service-worker.js
wwwroot/js/pwa-install.js
wwwroot/img/icons/icon-192x192.png
wwwroot/img/icons/icon-512x512.png
```

---

## 📦 Módulo 9: Internacionalización (i18n)

**Código**: `I18-`
**Prioridad**: 🟢 Baja
**Esfuerzo**: 5-7 días
**Dependencia de BD**: Ninguna (cambios en vistas)

### Descripción
Soporte multilingüe (español, inglés, quechua) para expandir reach.

### Funcionalidades
- [ ] Archivos .resx de recursos por idioma
- [ ] Selector de idioma en ajustes
- [ ] Localización de vistas (etiquetas, validaciones, mensajes)
- [ ] Localización de fechas, moneda, unidades
- [ ] Contenido generado por IA localizado

---

## 📦 Módulo 10: Gamificación de Bienestar (ODS 3+) — Retos de Salud Mental

**Código**: `ODS-`
**Prioridad**: ⭐ Alta
**Esfuerzo**: 3-4 días
**Dependencia de BD**: Ampliación de `EntradaDiario`

### Descripción
Expandir el módulo ODS 3 con retos semanales de bienestar, ejercicios de mindfulness, y tracking de sueño.

### Funcionalidades
- [ ] Reto semanal de bienestar (meditar 3 min/día, caminar 10 min, etc.)
- [ ] Tracking de sueño (horas, calidad)
- [ ] Ejercicios guiados de respiración (ampliar el mini componente)
- [ ] Recordatorio de hidratación
- [ ] Diario de gratitud (entrada rápida diaria)
- [ ] Conexión con recursos de salud mental (línea 113 Perú + otros)
- [ ] Reporte semanal de bienestar con recomendaciones IA

---

## 📦 Módulo 11: API Móvil (Flutter) — Contrato Completo

**Código**: `MOB-`
**Prioridad**: 🔥 Crítica
**Esfuerzo**: 6-8 días
**Dependencia de BD**: Podría requerir nuevos endpoints

### Descripción
Completar la API REST para consumo desde app Flutter: JWT en body, refresh token rotation, endpoints de datos offline.

### Funcionalidades
- [ ] Endpoint login que devuelve `{ token, refreshToken }` en body
- [ ] Endpoint refresh token que rota el token
- [ ] Endpoint register con validación completa
- [ ] Sincronización de hábitos offline (batch POST/PUT)
- [ ] Endpoint de datos de inicio (dashboard data en una llamada)
- [ ] Endpoint de upload de foto de perfil
- [ ] Rate limiting diferenciado para móvil
- [ ] Documentación OpenAPI completa con ejemplos

---

## 📦 Módulo 12: Gamificación Visual (Animaciones de Evolución)

**Código**: `VIS-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 3-4 días
**Dependencia de BD**: Sin cambios BD

### Descripción
Añadir animaciones y efectos visuales para celebrar logros, subidas de nivel y rachas.

### Funcionalidades
- [ ] Animación de subida de nivel (partículas, glow, transición)
- [ ] Popup de logro desbloqueado (tarjeta animada)
- [ ] Efecto de racha (llamas, aura alrededor del personaje)
- [ ] Confetti celebration al completar misión diaria
- [ ] Barra de XP animada (progreso suave)
- [ ] Efecto de evolución del personaje al subir de nivel
- [ ] Animación de personaje idle en sidebar (mínima: levitar/pulso)

### Librerías sugeridas
```
canvas-confetti (npm)
GSAP o anime.js (animaciones avanzadas)
```

---

## 📦 Módulo 13: Logging y Auditoría Avanzada

**Código**: `LOG-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 2-3 días
**Dependencia de BD**: Ya existe tabla `Log`

### Descripción
Sistema completo de auditoría de operaciones sensibles con panel de visualización admin.

### Funcionalidades
- [ ] Auditoría de: login (éxito/fallo), cambios de contraseña, acciones admin
- [ ] Panel admin: visor de logs con filtros (tipo, usuario, fecha)
- [ ] Exportación de logs a CSV/JSON
- [ ] Retención configurable de logs (purgado automático)
- [ ] Alertas admin ante eventos críticos (múltiples login fallidos)

---

## 📦 Módulo 14: Memoria a Largo Plazo para EDY (IA)

**Código**: `IA-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 3-4 días
**Dependencia de BD**: Ampliación de `MensajeIA`

### Descripción
Dar memoria persistente al asistente EDY para que recuerde información del usuario entre sesiones.

### Funcionalidades
- [ ] Tabla `MemoriaIA` (UsuarioId, Clave, Valor, Contexto)
- [ ] EDY recuerda: nombre del usuario, objetivos, preferencias
- [ ] EDY recuerda: estado de ánimo recurrente, hábitos que cuesta mantener
- [ ] EDY puede sugerir basado en memoria histórica
- [ ] Resumen semanal generado por IA con recomendaciones
- [ ] Interfaz para que el usuario pueda ver/editar/borrar lo que EDY recuerda

---

## 📦 Módulo 15: Módulo de Administración Avanzada

**Código**: `ADM-`
**Prioridad**: 🔵 Media
**Esfuerzo**: 3-4 días
**Dependencia de BD**: Sin cambios mayores

### Descripción
Expandir el panel admin con herramientas de gestión de contenido, usuarios y monitoreo.

### Funcionalidades
- [ ] Estadísticas en tiempo real (usuarios activos, XP total generado)
- [ ] Gestión de contenido: frases, tips, misiones plantilla
- [ ] Moderación de usuarios (banear, suspender, ver actividad)
- [ ] Logs de actividad admin
- [ ] Métricas de uso de IA (costos estimados, tokens consumidos)
- [ ] Editor de personajes/niveles desde admin
- [ ] Backup manual de BD desde admin
- [ ] Gestión de suscripciones

---

## Resumen de Roadmap

| Fase | Módulos | Prioridad | Timeline estimado |
|------|---------|-----------|-------------------|
| **Fase 1** (Inmediata) | PWA, API Móvil (contrato), Notificaciones SignalR | Crítica | 1-2 semanas |
| **Fase 2** (Corto plazo) | Amigos/Social, Misiones automáticas, ODS3+ retos | Alta | 2-3 semanas |
| **Fase 3** (Medio plazo) | Tienda/Recompensas, Analytics, Audit logging | Media | 3-4 semanas |
| **Fase 4** (Largo plazo) | Coleccionables, Desafíos, Memoria IA, i18n, Animaciones | Media | 4-6 semanas |
