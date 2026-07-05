# Auditoría Completa de EpycusApp

**Fecha:** 2026-06-24  
**Proyecto:** EpycusApp - Plataforma de gamificación para productividad y bienestar universitario  
**Repositorio:** GitHub con CI/CD vía GitHub Actions  
**Entorno:** Windows (desarrollo local) → Debian 13 VPS (producción)

---

## Índice

1. [Estructura General](#1-estructura-general)
2. [Tecnologías Usadas](#2-tecnologías-usadas)
3. [Funcionalidades Principales](#3-funcionalidades-principales)
4. [Rutas y Endpoints](#4-rutas-y-endpoints)
5. [Base de Datos](#5-base-de-datos)
6. [Autenticación y Seguridad](#6-autenticación-y-seguridad)
7. [Tests](#7-tests)
8. [Archivos de Configuración](#8-archivos-de-configuración)
9. [Dependencias](#9-dependencias)
10. [Debilidades y Pendientes](#10-debilidades-y-pendientes)
11. [Roadmap](#11-roadmap)

---

## 1. Estructura General

**Tipo de proyecto:** Monolito Web (ASP.NET Core MVC 9)  
**Arquitectura:** Monolítica con capas bien definidas (Controladores → Servicios → Datos/ORM)  
**Idioma del código:** Español (nombres de clases, métodos, variables, comentarios en español)

### Árbol de directorios

```
C:\Users\marco\Pictures\EpycusApp\
├── Ayudantes/                      # Clases de utilidad (helpers)
├── bin/                            # Build output
├── Controllers/                    # Controladores MVC
│   └── Api/                       # Controladores API REST
├── Datos/                          # DbContext, migraciones, seed data
│   └── Semilla/
├── deploy/                         # Scripts de deploy, nginx, systemd
├── diagrams/                       # Diagramas PlantUML
├── DTOs/                           # Data Transfer Objects
├── EpycusApp.AcceptanceTests/      # Tests de aceptación (Playwright)
├── EpycusApp.Tests/                # Tests unitarios (xUnit)
├── Middleware/                      # Filtros y middleware personalizado
├── Migrations/                     # Migraciones EF Core (5 migraciones)
├── Models/                         # Entidades, Enums, DTOs
│   ├── Entidades/                 # 29 entidades de dominio
│   ├── Enums/                     # 5 enumeraciones
│   └── DTOs/                      # RespuestaOperacion
├── obj/                            # Build output
├── Properties/                     # launchSettings.json
├── Servicios/                      # Lógica de negocio
│   ├── Interfaces/                # 13 interfaces
│   └── Implementaciones/          # 15 implementaciones
├── ViewModels/                     # 21 ViewModels (más subdirectorios)
│   ├── Admin/
│   ├── Autenticacion/
│   └── Ia/
├── Views/                          # Vistas Razor (15 directorios)
│   ├── Admin/
│   ├── Ajustes/
│   ├── Autenticacion/
│   ├── Bienestar/
│   ├── DiarioAnimo/
│   ├── Habitos/
│   ├── Home/
│   ├── Ia/
│   ├── Misiones/
│   ├── Perfil/
│   ├── Pomodoro/
│   ├── Progreso/
│   └── Shared/
├── wwwroot/                        # Archivos estáticos
│   ├── css/ (12 archivos)
│   ├── js/ (4 archivos)
│   ├── lib/ (jQuery, Bootstrap, Chart.js, etc.)
│   └── img/
├── Documentacion.md                # Documentación completa del proyecto
├── README.md                       # Readme principal
└── Archivos raíz:
    ├── Program.cs
    ├── appsettings.json
    ├── appsettings.Development.json
    ├── appsettings.Example.json
    ├── .gitignore
    ├── .gitleaks.toml
    ├── EpycusApp.csproj
    └── EpycusApp.sln
```

---

## 2. Tecnologías Usadas

| Capa | Tecnología | Versión |
|------|-----------|---------|
| **Runtime** | .NET | 9.0 |
| **Framework** | ASP.NET Core MVC | 9.0 |
| **ORM** | Entity Framework Core | 9.0 |
| **Conector MySQL** | Pomelo.EntityFrameworkCore.MySql | 9.0 |
| **Base de datos** | MariaDB | 11.8.6 |
| **Autenticación** | JWT Bearer + Cookies | 9.0 |
| **Autenticación Google** | Microsoft.AspNetCore.Authentication.Google | 9.0 |
| **Hashing** | BCrypt.Net-Next | 4.2.0 |
| **Swagger/OpenAPI** | Swashbuckle.AspNetCore | 10.2.1 |
| **Health Checks** | AspNetCore.HealthChecks.MySql | 9.0 |
| **Frontend** | Bootstrap 5 + Chart.js + jQuery | — |
| **Iconos** | Bootstrap Icons | — |
| **Testing unitario** | xUnit + Moq + FluentAssertions | — |
| **Testing aceptación** | xUnit + Microsoft.Playwright | 1.51.0 |
| **CI/CD** | GitHub Actions | — |
| **Proxy inverso** | Nginx | — |
| **Servicio** | systemd (Linux) | — |
| **Monitoreo** | Health Checks personalizados | — |

### APIs externas integradas

- **DeepSeek API** (modelo: deepseek-v4-flash)
- **Google OAuth 2.0**
- **SMTP Gmail** (envío de correos)

---

## 3. Funcionalidades Principales

### Módulo 1: Autenticación y Usuarios
**Controladores:** `AutenticacionController.cs`, `ApiAuthController.cs`

- Registro de usuarios con nombre, correo, contraseña (BCrypt), fecha nacimiento, género, carrera
- Login con JWT (token en cookie HttpOnly `jwt_token`)
- Login/Registro con Google OAuth 2.0
- Completar registro Google (si el usuario ya existe en Google pero no en la BD)
- Verificación de correo electrónico (token por email)
- Recuperación y restablecimiento de contraseña
- Refresh token rotation (SHA256 hasheado en BD)
- Logout (revocación de tokens refresh)
- Roles: `Usuario`, `Admin`

### Módulo 2: Hábitos
**Controladores:** `HabitosController.cs`, `ApiHabitosController.cs`  
**Servicio:** `ServicioHabitos.cs`

- CRUD completo de hábitos
- Frecuencias: Diaria, Semanal, Personalizada (días de semana)
- Categorización de hábitos (Salud, Estudio, Ejercicio, Sueño, etc.)
- Registro diario de estado (Completado/Parcial/Omitido)
- Rachas (streaks) con día de gracia
- Dashboard de hábitos (completados hoy, esta semana, distribución por categoría, mejores rachas)
- Vinculación con Pomodoro
- Recordatorio por hora

### Módulo 3: Misiones
**Controladores:** `MisionesController.cs`, `ApiMisionesController.cs`  
**Servicio:** `ServicioMisiones.cs`

- CRUD completo de misiones con fecha límite
- Estados: Pendiente, EnProgreso, Completado, Fallido
- Prioridades: Baja, Media, Alta
- Asignación de XP según prioridad (30/50/80 XP)
- Vinculación con Pomodoro
- Categorías específicas para misiones

### Módulo 4: Pomodoro
**Controladores:** `PomodoroController.cs`, `ApiPomodoroController.cs`  
**Servicio:** `ServicioPomodoro.cs`

- Temporizador con tiempos configurables: estudio, descanso, descanso largo
- Configuración: sonido, volumen, auto-iniciar, tic-tac, vibración, notificaciones desktop
- Sesiones asociables a hábitos o misiones
- Ciclos completados con ganancia de XP
- Meta diaria de ciclos
- Modo personalizado
- Tips de productividad
- Rachas de Pomodoro
- Estadísticas semanales
- Alertas de bienestar por uso excesivo (>8 ciclos/día)

### Módulo 5: Progreso y Gamificación
**Controlador:** `ProgresoController.cs`  
**Servicio:** `ServicioGamificacion.cs`

- Sistema de niveles (20 niveles: Novato → Leyenda Viviente)
- Cálculo de XP: fórmula `100 + (nivel * 50)` por nivel
- XP por: hábitos (20), misiones (30-80), pomodoro (15), login diario (10), mensajes IA (1)
- Rachas con día de gracia
- Productividad diaria (% de hábitos completados)
- Barra de progreso de nivel animada
- Página de progreso visible para usuarios anónimos

### Módulo 6: Logros
**Servicio:** `ServicioGamificacion.cs`

- 15 logros definidos (semilla)
- Condiciones: HábitosCompletados, MisionesCompletadas, RachaDias, SesionesPomodoro, XpTotal, NivelAlcanzado
- Logros ODS-3 adicionales: Ánimo Estable, Autoconsciente, Alerta Superada, Bienestar Constante, Maestro del Equilibrio
- XP de recompensa por logro
- Verificación automática al ganar XP

### Módulo 7: Perfil
**Controladores:** `PerfilController.cs`, `AjustesController.cs`, `ApiPerfilController.cs`

- Visualización y edición de perfil (nombre, carrera)
- Cambio de contraseña
- Selección de personaje (Kai, Luna, Ares, Nova)
- Cambio de tema visual (Noche Épica / Sakura)
- Visualización de logros obtenidos
- Personajes disponibles

### Módulo 8: Personajes y Niveles

- 4 personajes base (Kai - Sistemas masculino, Luna - Sistemas femenino, Ares - genérico masculino, Nova - genérico femenino)
- Imágenes por nivel para cada personaje/carrera
- Personajes vinculados a carreras universitarias
- Sistema de evolución visual del personaje

### Módulo 9: Bienestar (ODS 3)
**Controlador:** `BienestarController.cs`  
**Servicio:** `ServicioBienestar.cs`

- Registro de estado de ánimo (Genial, Bien, Normal, Cansado, Estresado)
- Alertas de bienestar automáticas:
  - Uso excesivo de Pomodoro (>8 ciclos/día)
  - Ánimo negativo consecutivo (>3 días)
  - Hábito de sueño descuidado
  - Sobrecarga de misiones (>8 pendientes)
- Frases motivacionales (25 frases precargadas)
- Recomendación de pausa activa
- Integración con el asistente IA

### Módulo 10: Diario de Ánimo
**Controladores:** `DiarioAnimoController.cs`, `ApiDiarioController.cs`

- Entrada diaria con: estado de ánimo (1-5), nivel de energía, horas de sueño, nivel de estrés, actividad física
- Pregunta guía diaria para reflexión
- Diario textual libre
- Visualización por mes con promedio de ánimo
- Rachas de días consecutivos registrando
- Preguntas guía rotativas

### Módulo 11: Asistente IA - EDY
**Controlador:** `IaController.cs`  
**Servicio:** `ServicioIA.cs`

- Chat con IA usando DeepSeek API
- System prompt contextual con datos del usuario (nombre, nivel, XP, racha, hábitos, misiones, estado de ánimo)
- Historial de conversaciones por usuario
- Sugerencias personalizadas basadas en el estado del usuario
- Contexto de bienestar para alertas
- Feedback del usuario (útil/no útil) en respuestas
- Límite de 50 mensajes/día
- Resumen automático de conversaciones largas
- XP por mensaje al asistente

### Módulo 12: Administración
**Controladores:** `AdminController.cs`, `ApiAdminController.cs`

- Dashboard admin: total usuarios, activos, suscripciones activas, total frases
- Listado y detalle de usuarios
- Activación/desactivación de suscripciones
- CRUD de frases motivacionales
- Panel de administración con layout separado

### Módulo 13: Suscripciones

- Planes de suscripción (Premium)
- Activación manual por admin
- Fechas de inicio y fin
- Precio en soles (PEN)

### Módulo 14: Temas Visuales

- 2 temas: "Noche Épica" (oscuro) y "Sakura" (claro)
- Temas premium (planificados)
- Persistencia de selección por usuario
- Archivos CSS separados por tema

### Módulo 15: Home / Dashboard
**Controlador:** `HomeController.cs`

- Dashboard principal con resumen: nivel, XP, rachas, barra de progreso
- Hábitos del día
- Frase motivacional aleatoria
- Estadísticas rápidas
- Imagen del personaje del usuario

---

## 4. Rutas y Endpoints

### Rutas MVC (Vistas)

| Ruta | Controlador | Acción | Auth |
|------|-------------|--------|------|
| `/` | Home | Index | Requerido |
| `/Home/Index` | Home | Index | Requerido |
| `/Home/Privacy` | Home | Privacy | No |
| `/Home/Error` | Home | Error | No |
| `/Autenticacion/Registro` | Autenticacion | Registro (GET/POST) | No |
| `/Autenticacion/Login` | Autenticacion | Login (GET/POST) | No |
| `/Autenticacion/Logout` | Autenticacion | Logout | Sí |
| `/Autenticacion/RecuperarContrasena` | Autenticacion | RecuperarContrasena (GET/POST) | No |
| `/Autenticacion/RestablecerContrasena` | Autenticacion | RestablecerContrasena (GET/POST) | No |
| `/Autenticacion/VerificarCorreo` | Autenticacion | VerificarCorreo | No |
| `/Autenticacion/IniciarSesionGoogle` | Autenticacion | IniciarSesionGoogle | No |
| `/Autenticacion/CallbackGoogle` | Autenticacion | CallbackGoogle | No |
| `/Autenticacion/CompletarRegistroGoogle` | Autenticacion | CompletarRegistroGoogle (GET/POST) | No |
| `/Habitos` | Habitos | Index | Sí |
| `/Habitos/Crear` | Habitos | Crear (GET/POST) | Sí |
| `/Habitos/Editar/{id}` | Habitos | Editar (GET/POST) | Sí |
| `/Habitos/Eliminar/{id}` | Habitos | Eliminar (POST) | Sí |
| `/Misiones` | Misiones | Index | Sí |
| `/Misiones/Crear` | Misiones | Crear (GET/POST) | Sí |
| `/Misiones/Editar/{id}` | Misiones | Editar (GET/POST) | Sí |
| `/Misiones/CambiarEstado` | Misiones | CambiarEstado | Sí |
| `/Misiones/Completar/{id}` | Misiones | Completar | Sí |
| `/Misiones/Eliminar/{id}` | Misiones | Eliminar | Sí |
| `/Pomodoro` | Pomodoro | Index | Sí |
| `/Pomodoro/Configuracion` | Pomodoro | Configuracion (GET/POST) | Sí |
| `/Progreso` | Progreso | Index | No (público) |
| `/Perfil` | Perfil | Index | Sí |
| `/Perfil/ActualizarPerfil` | Perfil | ActualizarPerfil | Sí |
| `/Perfil/CambiarContrasena` | Perfil | CambiarContrasena | Sí |
| `/Perfil/CambiarPersonaje` | Perfil | CambiarPersonaje | Sí |
| `/api/perfil/tema` | Perfil | CambiarTema (POST AJAX) | Sí |
| `/Ajustes` | Ajustes | Index | Sí |
| `/Ajustes/ActualizarPerfil` | Ajustes | ActualizarPerfil | Sí |
| `/Ajustes/CambiarContrasena` | Ajustes | CambiarContrasena | Sí |
| `/Ajustes/CambiarPersonaje` | Ajustes | CambiarPersonaje | Sí |
| `/Ajustes/CambiarTema` | Ajustes | CambiarTema | Sí |
| `/Bienestar` | Bienestar | Index | Sí |
| `/Bienestar/RegistrarAnimo` | Bienestar | RegistrarAnimo | Sí |
| `/DiarioAnimo` | DiarioAnimo | Index | Sí |
| `/DiarioAnimo/Registrar` | DiarioAnimo | Registrar | Sí |
| `/DiarioAnimo/NavegarMes` | DiarioAnimo | NavegarMes | Sí |
| `/Ia` | Ia | Index | Sí |
| `/Ia/Nueva` | Ia | Nueva | Sí |
| `/api/ia/chat` | Ia | Chat (POST) | Sí |
| `/api/ia/feedback` | Ia | Feedback (POST) | Sí |
| `/api/ia/registrar-animo` | Ia | RegistrarAnimo (POST) | Sí |
| `/admin/login` | Admin | Login (GET/POST) | No |
| `/admin/logout` | Admin | Logout | Admin |
| `/admin` | Admin | Index | Admin |
| `/admin/Usuarios` | Admin | Usuarios | Admin |
| `/admin/DetalleUsuario/{id}` | Admin | DetalleUsuario | Admin |
| `/admin/ActivarSuscripcion` | Admin | ActivarSuscripcion | Admin |
| `/admin/DesactivarSuscripcion` | Admin | DesactivarSuscripcion | Admin |
| `/admin/Frases` | Admin | Frases | Admin |
| `/admin/CrearFrase` | Admin | CrearFrase | Admin |
| `/admin/EliminarFrase` | Admin | EliminarFrase | Admin |
| `/health` | — | Health Checks | No |

### Rutas API REST (`/api/*`)

#### Autenticación

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/auth/login` | POST | Login | Auth (20/min) |
| `api/auth/refresh` | POST | Renovar token | Auth |
| `api/auth/logout` | POST | Logout | Auth |
| `api/auth/registro` | POST | Registro | Auth |
| `api/auth/verificar-correo` | GET | Verificar correo | Auth |
| `api/auth/recuperar-contrasena` | POST | Recuperar contraseña | Auth |
| `api/auth/restablecer-contrasena` | POST | Restablecer contraseña | Auth |
| `api/auth/google` | POST | Login Google | Auth |
| `api/auth/completar-registro-google` | POST | Completar registro Google | Auth |
| `api/auth/carreras` | GET | Listar carreras | Auth |

#### Hábitos

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/habitos` | GET | Listar hábitos | Mobile (400/min) |
| `api/habitos/hoy` | GET | Hábitos de hoy | Mobile |
| `api/habitos/{id}` | GET | Hábito por ID | Mobile |
| `api/habitos` | POST | Crear hábito | Mobile |
| `api/habitos/{id}` | PUT | Editar hábito | Mobile |
| `api/habitos/{id}` | DELETE | Eliminar hábito | Mobile |
| `api/habitos/{id}/completar` | POST | Completar hábito | Mobile |
| `api/habitos/{id}/fallar` | POST | Fallar hábito | Mobile |
| `api/habitos/{id}/semana` | GET | Registros semana | Mobile |
| `api/habitos/dashboard` | GET | Dashboard hábitos | Mobile |
| `api/habitos/categorias` | GET | Categorías | Mobile |

#### Misiones

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/misiones` | GET | Listar misiones | Mobile |
| `api/misiones/{id}` | GET | Misión por ID | Mobile |
| `api/misiones` | POST | Crear misión | Mobile |
| `api/misiones/{id}` | PUT | Editar misión | Mobile |
| `api/misiones/{id}` | DELETE | Eliminar misión | Mobile |
| `api/misiones/{id}/completar` | POST | Completar misión | Mobile |
| `api/misiones/{id}/estado` | POST | Cambiar estado | Mobile |
| `api/misiones/categorias` | GET | Categorías misión | Mobile |

#### Pomodoro

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/pomodoro/iniciar` | POST | Iniciar sesión | Mobile |
| `api/pomodoro/{id}/ciclo-completado` | POST | Ciclo completado | Mobile |
| `api/pomodoro/{id}/finalizar` | POST | Finalizar sesión | Mobile |
| `api/pomodoro/{id}/cancelar` | POST | Cancelar sesión | Mobile |
| `api/pomodoro/configuracion` | GET | Obtener configuración | Mobile |
| `api/pomodoro/configuracion` | PUT | Actualizar configuración | Mobile |
| `api/pomodoro/tip-aleatorio` | GET | Tip aleatorio | Mobile |
| `api/pomodoro/historial` | GET | Historial sesiones | Mobile |
| `api/pomodoro/racha` | GET | Racha actual | Mobile |
| `api/pomodoro/estadisticas` | GET | Estadísticas | Mobile |

#### Perfil

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/perfil` | GET | Obtener perfil | Mobile |
| `api/perfil` | PUT | Actualizar perfil | Mobile |
| `api/perfil/cambiar-contrasena` | PUT | Cambiar contraseña | Mobile |
| `api/perfil/personaje` | PUT | Cambiar personaje | Mobile |
| `api/perfil/tema` | PUT | Cambiar tema | Mobile |
| `api/perfil/personajes` | GET | Personajes disponibles | Mobile |
| `api/perfil/logros` | GET | Logros del usuario | Mobile |

#### Progreso y Gamificación

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/progreso` | GET | Obtener progreso | Mobile |
| `api/progreso/logros` | GET | Logros desbloqueados | Mobile |
| `api/progreso/historial-animo` | GET | Historial ánimo | Mobile |
| `api/gamificacion/mi-progreso` | GET | Progreso completo | Mobile |
| `api/gamificacion/logros` | GET | Todos los logros | Mobile |

#### Bienestar

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/bienestar/resumen` | GET | Resumen bienestar | Mobile |
| `api/bienestar/alertas` | GET | Alertas activas | Mobile |
| `api/bienestar/frase` | GET | Frase del día | Mobile |
| `api/bienestar/estado-hoy` | GET | Estado hoy | Mobile |
| `api/bienestar/historial-animo` | GET | Historial ánimo | Mobile |
| `api/bienestar/habitos-pendientes` | GET | Hábitos pendientes | Mobile |
| `api/bienestar/misiones-pendientes` | GET | Misiones pendientes | Mobile |
| `api/bienestar/pausa-activa` | POST | Recomendar pausa | Mobile |

#### Diario de Ánimo

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/diario/hoy` | GET | Entrada de hoy | Mobile |
| `api/diario/fecha` | GET | Entrada por fecha | Mobile |
| `api/diario/mes` | GET | Entradas del mes | Mobile |
| `api/diario` | POST | Registrar entrada | Mobile |
| `api/diario/{fecha}` | PUT | Actualizar entrada | Mobile |
| `api/diario/racha` | GET | Racha diario | Mobile |
| `api/diario/promedio-mes` | GET | Promedio ánimo | Mobile |
| `api/diario/pregunta-guia` | GET | Pregunta guía | Mobile |

#### Asistente IA

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/ia/chat` | POST | Chat IA | Mobile |
| `api/ia/historial` | GET | Historial chat | Mobile |
| `api/ia/conversaciones` | GET | Conversaciones | Mobile |
| `api/ia/sugerencias` | GET | Sugerencias | Mobile |
| `api/ia/contexto-bienestar` | GET | Contexto bienestar | Mobile |
| `api/ia/feedback` | POST | Feedback | Mobile |
| `api/ia/mensajes-hoy` | GET | Mensajes hoy | Mobile |

#### Dashboard

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/dashboard/resumen` | GET | Resumen dashboard | Mobile |
| `api/dashboard/frase-del-dia` | GET | Frase del día | Mobile |

#### Administración

| Endpoint | Método | Descripción | Rate Limit |
|----------|--------|-------------|------------|
| `api/admin/login` | POST | Login admin | Api (300/min) |
| `api/admin/usuarios` | GET | Listar usuarios | Api |
| `api/admin/usuarios/{id}` | GET | Usuario por ID | Api |
| `api/admin/usuarios/{id}/suscripcion/activar` | POST | Activar suscripción | Api |
| `api/admin/usuarios/{id}/suscripcion/desactivar` | POST | Desactivar suscripción | Api |
| `api/admin/frases` | GET | Listar frases | Api |
| `api/admin/frases` | POST | Crear frase | Api |
| `api/admin/frases/{id}` | DELETE | Eliminar frase | Api |

---

## 5. Base de Datos

**Motor:** MariaDB 11.8.6 (vía Pomelo.EntityFrameworkCore.MySql 9.0)  
Soporta también modo InMemory para desarrollo/testing.

### Esquema completo (29 entidades)

#### Usuarios y Seguridad

| Entidad | Campos clave |
|---------|--------------|
| `Usuario` | Id, CodigoUnico, Nombre, CorreoElectronico, ContrasenaHash, FechaNacimiento, Genero, CorreoVerificado, AceptoTerminos, EstaActivo, FechaRegistro, UltimoAcceso, GoogleId, FotoGoogleUrl, RolId, CarreraId, TemaActualId |
| `Rol` | Id, Nombre — "Usuario" o "Admin" |
| `TokenRefresh` | Id, Token, ExpiraEn, Revocado, FechaCreacion, UsuarioId |
| `RecuperacionContrasena` | Id, Token, ExpiraEn, Usado, FechaCreacion, UsuarioId |
| `VerificacionCorreo` | Id, Token, ExpiraEn, Usado, FechaCreacion, UsuarioId |

#### Hábitos y Misiones

| Entidad | Campos clave |
|---------|--------------|
| `Habito` | Id, Nombre, Descripcion, Frecuencia, ConPomodoro, RecordatorioHora, RachaActual, RachaMaxima, EstaActivo, FechaCreacion, UsuarioId, CategoriaId |
| `DiasSemanaHabito` | Id, HabitoId, DiaSemana |
| `RegistroHabito` | Id, Fecha, Estado, XpOtorgado, FechaRegistro, HabitoId |
| `Categoria` | Id, Nombre, Icono, Tipo, EstaActiva — "Habito", "Mision" o "Ambos" |
| `Mision` | Id, Nombre, Descripcion, NombreCurso, FechaLimite, Prioridad, Estado, ConPomodoro, XpOtorgado, FechaCreacion, FechaCompletado, UsuarioId, CategoriaId |

#### Pomodoro

| Entidad | Campos clave |
|---------|--------------|
| `ConfiguracionPomodoro` | Id, TiempoEstudioMin, TiempoDescansoMin, TiempoDescansoLargoMin, CiclosAntesDescansoLargo, SonidoActivo, SonidoSeleccionado, Volumen, AutoIniciarDescanso, AutoIniciarEnfoque, TicTacActivo, MetaDiariaCiclos, ModoPersonalizadoMinutos, VibracionActiva, NotificacionDesktop, FechaActualizacion, UsuarioId |
| `SesionPomodoro` | Id, FechaInicio, FechaFin, CiclosCompletados, XpOtorgado, FueCompletada, UsuarioId, HabitoId, MisionId |
| `TipPomodoro` | Id, Tip, EstaActivo |

#### Gamificación

| Entidad | Campos clave |
|---------|--------------|
| `Nivel` | Id, Numero, Titulo, XpRequerido, Descripcion — 20 niveles |
| `ProgresoUsuario` | Id, XpTotal, RachaActual, RachaMaxima, FechaUltimaActividad, FechaInicioRacha, DiaDeGraciaUsado, ProductividadDiaria, UsuarioId, NivelActualId — 1:1 con Usuario |
| `Logro` | Id, Nombre, Descripcion, IconoUrl, CondicionTipo, CondicionValor, XpRecompensa, EstaActivo |
| `LogroUsuario` | Id, FechaObtenido, UsuarioId, LogroId |

#### Personajes

| Entidad | Campos clave |
|---------|--------------|
| `Personaje` | Id, Nombre, Genero, EstaActivo, CarreraId |
| `ImagenNivelPersonaje` | Id, NivelNumero, ImagenUrl, EsPlaceholder, PersonajeId |
| `PersonajeUsuario` | Id, EstaSeleccionado, FechaObtenido, UsuarioId, PersonajeId |

#### Carreras Universitarias

| Entidad | Campos clave |
|---------|--------------|
| `Carrera` | Id, Nombre, Area, Codigo, EstaActiva — 12 carreras precargadas |

#### Bienestar

| Entidad | Campos clave |
|---------|--------------|
| `EstadoAnimo` | Id, Fecha, Estado, Nota, FechaRegistro, UsuarioId |
| `EntradaDiario` | Id, Fecha, EstadoAnimo, NivelEnergia, HorasSueno, NivelEstres, ActividadFisica, DiarioTexto, PreguntaGuia, RespuestaGuia, FechaRegistro, UsuarioId |
| `FraseMotivacional` | Id, Frase, Autor, Categoria, EstaActiva |

#### IA

| Entidad | Campos clave |
|---------|--------------|
| `MensajeIA` | Id, ConversacionId, UsuarioId, Rol, Contenido, FechaHora, FeedbackRecibido, FeedbackUtil |

#### Otros

| Entidad | Campos clave |
|---------|--------------|
| `Suscripcion` | Id, Plan, PrecioSoles, FechaInicio, FechaFin, EstaActiva, ActivadaPorAdminId, FechaActivacion, UsuarioId |
| `Tema` | Id, Nombre, Descripcion, Modo, ArchivoCss, ImagenPreviewUrl, EsPremium, Precio, EstaActivo |
| `TemaUsuario` | Id, FechaObtenido, UsuarioId, TemaId |
| `Log` | Id, Accion, Detalle, DireccionIp, FechaRegistro, UsuarioId |

### Migraciones (5 migraciones)

1. `20260615234414_Initial` — Creación inicial de todas las tablas
2. `20260618182406_AddCategoriaToFraseMotivacional` — Columna Categoria en FraseMotivacional
3. `20260618223131_AddFeedbackToMensajesIA` — Feedback en MensajesIA
4. `20260619000237_AddEntradaDiario` — Nueva entidad EntradaDiario
5. `20260621163455_AddPomodoroConfigExtras` — Configuraciones extras de Pomodoro

### Índices clave

- `Usuario.CorreoElectronico` (único)
- `Usuario.CodigoUnico` (único)
- `Usuario.GoogleId` (único)
- `ProgresoUsuario.UsuarioId` (único, 1:1)
- `EntradaDiario.UsuarioId + Fecha` (único compuesto)
- `MensajeIA.ConversacionId`
- `MensajeIA.UsuarioId`
- `TokenRefresh.UsuarioId`, `TokenRefresh.Token`
- Índices en `UsuarioId` de varias tablas

---

## 6. Autenticación y Seguridad

### Sistema de Autenticación

- **JWT (Json Web Tokens)** con cookies HttpOnly
- Cookie principal: `jwt_token` (usuarios normales)
- Cookie admin: `admin_jwt_token` (rutas `/admin/*`)
- Cookie de autenticación externa: `.AspNetCore.ExternalAuth` (Google OAuth temporal)

### Configuración JWT

| Parámetro | Valor |
|-----------|-------|
| Algoritmo | HMAC-SHA256 |
| Claims | NameIdentifier, Email, Name, Role, CodigoUnico, CarreraId |
| Expiración | 60 minutos (configurable) |
| Refresh tokens | Rotación con hash SHA256, expiran en 7 días |
| Emisor | `EpycusWeb` |
| Audiencia | `EpycusUsuarios` |

### Google OAuth 2.0

- Login con Google account
- Esquema de autenticación externa: `ExternalCookie` (5 min de expiración)
- Claims: NameIdentifier (Google ID), Email, Name, picture
- Flujo: Challenge → Callback → ProcesarAutenticacionGoogleAsync

### Roles

- `Usuario` (rol por defecto al registrarse)
- `Admin` (acceso a panel `/admin/*` con autorización `[Authorize(Roles = "Admin")]`)

### Rate Limiting

| Policy | Límite | Ventana | Cola |
|--------|--------|---------|------|
| Global | 600 | 1 min | 0 |
| Api | 300 | 1 min | 10 |
| Auth | 20 | 1 min | 0 |
| Mobile | 400 | 1 min | 10 |
| DeepSeek | 2500 | 1 min | 0 |

### Protecciones de seguridad implementadas

- ✅ `[ValidateAntiForgeryToken]` en formularios MVC, header `X-CSRF-TOKEN`
- ✅ Content-Security-Policy en producción
- ✅ `Strict-Transport-Security` (en producción vía Nginx)
- ✅ X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy
- ✅ Cookies: HttpOnly, Secure, SameSite=Lax/Strict
- ✅ HTTPS redirect configurable (`DisableHttpsRedirect`)
- ✅ BCrypt con work factor 12

### Pendientes de seguridad

- ❌ Sin CAPTCHA en login/registro
- ❌ Sin bloqueo de cuenta por intentos fallidos
- ❌ Sin política de contraseñas (longitud mínima, complejidad)
- ❌ API endpoints sin CSRF
- ❌ Refresh tokens no se rotan adecuadamente
- ❌ Sin auditoría de operaciones sensibles
- ❌ Cambio de contraseña no invalida JWTs existentes

### Middleware de seguridad

- Middleware de telemetría (logs de requests lentos y errores 500+)
- Health checks de BD, disco y DeepSeek

---

## 7. Tests

### Tests Unitarios (`EpycusApp.Tests`)

**Framework:** xUnit 2.9.3 + Moq 4.20.72 + FluentAssertions 8.2.0  
**SDK:** Microsoft.NET.Test.Sdk 17.13.0  
**Cobertura:** coverlet.collector 6.0.4  
**Base de datos:** InMemory para tests

#### Archivos de test (13 archivos)

**Servicios (8):**
- `ServicioGamificacionTests.cs`
- `ServicioBienestarTests.cs`
- `ServicioAdminTests.cs`
- `ServicioMisionesTests.cs`
- `ServicioHabitosTests.cs`
- `ServicioPerfilTests.cs`
- `ServicioPomodoroTests.cs`
- `ServicioProgresoTests.cs`

**Controladores (4):**
- `ProgresoControllerTests.cs`
- `HomeControllerTests.cs`
- `HabitosControllerTests.cs`
- `AutenticacionControllerTests.cs`

**Ayudantes (1):**
- `CalculadorXPTests.cs` (teoría con múltiples casos)

**Helper:**
- `DbContextFactory.cs` (factory para InMemory DbContext)

### Tests de Aceptación (`EpycusApp.AcceptanceTests`)

**Framework:** xUnit + Microsoft.Playwright 1.51.0 + Microsoft.AspNetCore.Mvc.Testing 9.0  
**Tipo:** Pruebas end-to-end con navegador real

#### Archivos (4)

- `RegistroYLoginTests.cs` — Flujo completo registro, login, login inválido, redirección
- `GestionHabitosTests.cs` — Crear hábito, listar hábitos
- `GestionMisionesTests.cs` — Gestión de misiones
- `ProgresoYPerfilTests.cs` — Progreso y perfil

**Screenshots:** Se guardan capturas automáticas en `Screenshots/` durante los tests.

### Observaciones sobre tests

- `EpycusApp.Tests/Integracion/` está vacío
- Los tests de aceptación requieren la app corriendo (usa `AcceptanceFixture` con CustomWebApplicationFactory)
- No hay tests de integración implementados

---

## 8. Archivos de Configuración

### CI/CD (GitHub Actions)

**`.github/workflows/ci-cd.yml`** — Pipeline principal:

1. **Code Quality**: Restore → Format check → Build (warnings as errors)
2. **Build & Publish**: Restore → Build → Publish → Upload artifact
3. **Deploy to VPS**: Backup actual → SCP al VPS → Restart systemd service
4. **Security Scan**: Gitleaks (detección de secretos en código)

**`.github/workflows/deploy.yml`** — Deploy manual (workflow_dispatch):
- Similar al deploy automático pero activado manualmente

**`.github/dependabot.yml`**: Actualizaciones automáticas semanales de NuGet y GitHub Actions.

### Variables de entorno (producción vía systemd)

```
ASPNETCORE_ENVIRONMENT=Production
ASPNETCORE_URLS=http://localhost:5000
ConnectionStrings__ConexionPrincipal=<MariaDB>
Jwt__Clave=<secreta>
Google__ClientId / Google__ClientSecret
Correo__Servidor / Correo__Puerto / Correo__Usuario / Correo__Contrasena
DeepSeek__ApiKey / DeepSeek__Modelo
Cors__OrigenesPermitidos__0 / __1
```

### Archivos de configuración principales

| Archivo | Propósito |
|---------|-----------|
| `appsettings.json` | Config principal (BD, JWT, Google, Correo, IA, CORS, RateLimiting) |
| `appsettings.Development.json` | Override desarrollo |
| `appsettings.Example.json` | Template con placeholders |
| `Properties/launchSettings.json` | Perfiles de ejecución (http:5053, https:7254) |
| `.gitignore` | 444 líneas, ignora bin, obj, secrets, etc. |
| `.gitleaks.toml` | Whitelist para evitar falsos positivos de secretos |
| `deploy/epycus-web.service.example` | Template systemd service |
| `deploy/nginx-epycus.conf` | Config Nginx (HTTP→HTTPS, proxy, HSTS, caching) |
| `deploy/setup-vps.sh.example` | Script automatizado de aprovisionamiento VPS |
| `deploy/maintenance.sh` | Activar/desactivar modo mantenimiento |
| `deploy/monitoreo-uptime.sh` | Health check con notificaciones Discord/Telegram |
| `deploy/journald-log-rotation.conf` | Rotación de logs (500MB máx, 7 días retención) |
| `deploy/maintenance.html` | Página 503 de mantenimiento |

---

## 9. Dependencias

### NuGet Packages (producción)

| Paquete | Versión | Propósito |
|---------|---------|-----------|
| `AspNetCore.HealthChecks.MySql` | 9.0.0 | Health check de BD |
| `BCrypt.Net-Next` | 4.2.0 | Hashing de contraseñas |
| `Microsoft.AspNetCore.Authentication.Google` | 9.0.0 | Google OAuth |
| `Microsoft.AspNetCore.Authentication.JwtBearer` | 9.0.0 | JWT authentication |
| `Microsoft.EntityFrameworkCore` | 9.0.0 | ORM principal |
| `Microsoft.EntityFrameworkCore.InMemory` | 9.0.0 | BD InMemory para tests |
| `Microsoft.EntityFrameworkCore.Design` | 9.0.0 | Migraciones (solo diseño) |
| `Microsoft.EntityFrameworkCore.Tools` | 9.0.0 | Herramientas CLI (solo diseño) |
| `Pomelo.EntityFrameworkCore.MySql` | 9.0.0 | Provider MySQL/MariaDB |
| `Swashbuckle.AspNetCore` | 10.2.1 | Swagger/OpenAPI |

### NuGet Packages (testing)

| Paquete | Versión |
|---------|---------|
| `Microsoft.NET.Test.Sdk` | 17.13.0 |
| `xunit` | 2.9.3 |
| `xunit.runner.visualstudio` | 3.0.2 |
| `Moq` | 4.20.72 |
| `FluentAssertions` | 8.2.0 |
| `coverlet.collector` | 6.0.4 |
| `Microsoft.Playwright` | 1.51.0 |
| `Microsoft.AspNetCore.Mvc.Testing` | 9.0.0 |

### Frontend Libraries (`wwwroot/lib/`)

- Bootstrap 5 (CSS + JS bundle)
- Bootstrap Icons
- Chart.js
- jQuery + jQuery Validation + jQuery Validation Unobtrusive

---

## 10. Debilidades y Pendientes

### Seguridad

| # | Pendiente | Impacto |
|---|-----------|---------|
| 1 | Sin CAPTCHA en login/registro | Ataques de fuerza bruta automatizados |
| 2 | Sin bloqueo de cuenta por intentos fallidos | Cuentas vulnerables a brute force |
| 3 | Sin política de contraseñas | Contraseñas débiles permitidas |
| 4 | API endpoints sin CSRF | Vulnerabilidad en APIs |
| 5 | Refresh tokens no se rotan adecuadamente | Riesgo de reuso de tokens |
| 6 | Sin auditoría de operaciones sensibles | Sin trazabilidad de acciones críticas |
| 7 | Cambio de contraseña no invalida JWTs existentes | Sesiones antiguas siguen activas |

### Técnicos

| # | Pendiente | Detalle |
|---|-----------|---------|
| 1 | Sin migraciones automáticas en CI/CD | Las migraciones se aplican manualmente |
| 2 | Sin caché para datos frecuentes | Carga innecesaria en BD |
| 3 | Sin graceful shutdown | Pérdida de datos en sesiones activas |
| 4 | API no versionada | Breaking changes afectan a clients |
| 5 | Sin logging/auditoría de operaciones sensibles | No se rastrean acciones admin |
| 6 | Sin análisis de sentimiento en el chat IA | No se detectan estados de ánimo del usuario |
| 7 | Personajes y logros con imágenes placeholder | Mayoría de carreras sin imágenes reales |

### Testing

| # | Pendiente | Detalle |
|---|-----------|---------|
| 1 | Sin tests de integración | No se prueba la interacción entre capas |
| 2 | `EpycusApp.Tests/Integracion/` vacío | Directorio preparado pero sin contenido |
| 3 | Cobertura limitada | Solo 13 archivos de test |

### Infraestructura

| # | Pendiente | Detalle |
|---|-----------|---------|
| 1 | Sin PWA | Sin service worker, manifest, offline support |
| 2 | Sin SignalR | Sin notificaciones en tiempo real |
| 3 | Sin caché distribuido | Sesiones en memoria del servidor |

---

## 11. Roadmap

El proyecto tiene una hoja de ruta con 15 módulos nuevos planificados:

### Fase 1: Inmediata
- PWA (service worker, manifest, offline)
- API Móvil definitiva
- Notificaciones SignalR en tiempo real

### Fase 2: Corto plazo
- Amigos / Red social
- Misiones automáticas
- Retos de bienestar ODS-3+

### Fase 3: Medio plazo
- Tienda / Recompensas
- Analytics
- Auditoría

### Fase 4: Largo plazo
- Coleccionables
- Desafíos
- Memoria IA
- Internacionalización (i18n)
- Animaciones avanzadas

---

*Auditoría generada el 2026-06-21 basada en el código fuente completo de EpycusApp.*  
*Total de archivos C# analizados: ~90+ (controladores, servicios, modelos, DTOs, ViewModels, middleware, helpers, tests)*
