# EPYCUS WEB — Especificación Técnica Complementaria
**Complemento obligatorio del Documento Base**  
**ASP.NET 10 Core MVC · MariaDB · Entity Framework Core**

---

> **¿Para qué sirve este documento?**  
> El Documento Base define la BD, arquitectura, convenciones y división de trabajo.  
> Este documento define lo que una IA necesita para generar código correcto:  
> relaciones de entidades, endpoints, reglas de negocio exactas, lógica de bienestar ODS 3 y seguridad.  
> Ambos documentos deben leerse juntos antes de escribir cualquier línea de código.

---

## Índice

1. [Relaciones del Modelo de Datos](#1-relaciones-del-modelo-de-datos)
2. [Arquitectura MVC vs API — Separación Clara](#2-arquitectura-mvc-vs-api--separación-clara)
3. [Catálogo de Endpoints](#3-catálogo-de-endpoints)
4. [Reglas de Negocio Determinísticas](#4-reglas-de-negocio-determinísticas)
5. [Lógica ODS 3 — Bienestar Explícito](#5-lógica-ods-3--bienestar-explícito)
6. [Seguridad y Autorización](#6-seguridad-y-autorización)
7. [Configuración de Entity Framework Core](#7-configuración-de-entity-framework-core)

---

## 1. Relaciones del Modelo de Datos

### 1.1 Mapa de Relaciones

```
Roles ──────────────── (1:N) ──────────────── Usuarios
Carreras ───────────── (1:N) ──────────────── Usuarios
Usuarios ───────────── (1:1) ──────────────── ProgresosUsuario
Usuarios ───────────── (1:N) ──────────────── Habitos
Usuarios ───────────── (1:N) ──────────────── Misiones
Usuarios ───────────── (1:N) ──────────────── SesionesPomodoro
Usuarios ───────────── (1:1) ──────────────── ConfiguracionesPomodoro
Usuarios ───────────── (1:N) ──────────────── PersonajesUsuario
Usuarios ───────────── (1:N) ──────────────── LogrosUsuario
Usuarios ───────────── (1:N) ──────────────── EstadosAnimo
Usuarios ───────────── (1:N) ──────────────── Suscripciones
Usuarios ───────────── (1:N) ──────────────── TemasUsuario
Usuarios ───────────── (1:N) ──────────────── TokensRefresh
Usuarios ───────────── (1:N) ──────────────── VerificacionesCorreo
Usuarios ───────────── (1:N) ──────────────── RecuperacionesContrasena
Niveles ────────────── (1:N) ──────────────── ProgresosUsuario
Habitos ────────────── (1:N) ──────────────── RegistrosHabito
Habitos ────────────── (1:N) ──────────────── SesionesPomodoro (opcional)
Misiones ───────────── (1:N) ──────────────── SesionesPomodoro (opcional)
Categorias ─────────── (1:N) ──────────────── Habitos
Categorias ─────────── (1:N) ──────────────── Misiones
Personajes ─────────── (1:N) ──────────────── PersonajesUsuario
Personajes ─────────── (1:N) ──────────────── ImagenesNivelPersonaje
Logros ─────────────── (1:N) ──────────────── LogrosUsuario
Temas ──────────────── (1:N) ──────────────── TemasUsuario
```

---

### 1.2 Entidades C# con Propiedades de Navegación

> Estas clases van en `Modelos/Entidades/`. Copiar exactamente para que EF Core genere las migraciones correctas.

#### `Usuario.cs`
```csharp
public class Usuario
{
    public int Id { get; set; }
    public string CodigoUnico { get; set; }           // EPY-XXXXXXXX
    public string Nombre { get; set; }
    public string CorreoElectronico { get; set; }
    public string? ContrasenaHash { get; set; }       // null si usa solo Google
    public DateTime FechaNacimiento { get; set; }
    public string Genero { get; set; }
    public bool CorreoVerificado { get; set; } = false;
    public bool AceptoTerminos { get; set; } = false;
    public bool EstaActivo { get; set; } = true;
    public DateTime FechaRegistro { get; set; } = DateTime.Now;
    public DateTime? UltimoAcceso { get; set; }
    public string? GoogleId { get; set; }
    public string? FotoGoogleUrl { get; set; }

    // Claves foráneas
    public int RolId { get; set; }
    public int CarreraId { get; set; }
    public int? TemaActualId { get; set; }

    // Navegación
    public Rol Rol { get; set; }
    public Carrera Carrera { get; set; }
    public Tema? TemaActual { get; set; }
    public ProgresoUsuario Progreso { get; set; }
    public ICollection<Habito> Habitos { get; set; }
    public ICollection<Mision> Misiones { get; set; }
    public ICollection<SesionPomodoro> SesionesPomodoro { get; set; }
    public ConfiguracionPomodoro ConfiguracionPomodoro { get; set; }
    public ICollection<PersonajeUsuario> PersonajesUsuario { get; set; }
    public ICollection<LogroUsuario> LogrosUsuario { get; set; }
    public ICollection<EstadoAnimo> EstadosAnimo { get; set; }
    public ICollection<Suscripcion> Suscripciones { get; set; }
    public ICollection<TemaUsuario> TemasUsuario { get; set; }
    public ICollection<TokenRefresh> TokensRefresh { get; set; }
}
```

#### `ProgresoUsuario.cs`
```csharp
public class ProgresoUsuario
{
    public int Id { get; set; }
    public int XpTotal { get; set; } = 0;
    public int RachaActual { get; set; } = 0;
    public int RachaMaxima { get; set; } = 0;
    public DateTime? FechaUltimaActividad { get; set; }
    public DateTime? FechaInicioRacha { get; set; }
    public bool DiaDeGraciaUsado { get; set; } = false;
    public decimal ProductividadDiaria { get; set; } = 0;

    // Claves foráneas
    public int UsuarioId { get; set; }
    public int NivelActualId { get; set; }

    // Navegación
    public Usuario Usuario { get; set; }
    public Nivel NivelActual { get; set; }
}
```

#### `Habito.cs`
```csharp
public class Habito
{
    public int Id { get; set; }
    public string Nombre { get; set; }
    public string? Descripcion { get; set; }
    public string Frecuencia { get; set; }      // "Diaria", "Semanal", "Personalizada"
    public string? DiasSemana { get; set; }     // JSON: "[1,3,5]"
    public bool ConPomodoro { get; set; } = false;
    public TimeSpan? RecordatorioHora { get; set; }
    public int RachaActual { get; set; } = 0;
    public int RachaMaxima { get; set; } = 0;
    public bool EstaActivo { get; set; } = true;
    public DateTime FechaCreacion { get; set; } = DateTime.Now;

    // Claves foráneas
    public int UsuarioId { get; set; }
    public int CategoriaId { get; set; }

    // Navegación
    public Usuario Usuario { get; set; }
    public Categoria Categoria { get; set; }
    public ICollection<RegistroHabito> Registros { get; set; }
    public ICollection<SesionPomodoro> SesionesPomodoro { get; set; }
}
```

#### `RegistroHabito.cs`
```csharp
public class RegistroHabito
{
    public int Id { get; set; }
    public DateOnly Fecha { get; set; }
    public string Estado { get; set; }          // "Completado", "Fallido", "Pendiente"
    public int XpOtorgado { get; set; } = 0;
    public DateTime FechaRegistro { get; set; } = DateTime.Now;

    // Claves foráneas
    public int HabitoId { get; set; }

    // Navegación
    public Habito Habito { get; set; }
}
```

#### `Mision.cs`
```csharp
public class Mision
{
    public int Id { get; set; }
    public string Nombre { get; set; }
    public string? Descripcion { get; set; }
    public string? NombreCurso { get; set; }
    public DateOnly FechaLimite { get; set; }
    public string Prioridad { get; set; }       // "Baja", "Media", "Alta"
    public string Estado { get; set; }          // "Pendiente", "EnProgreso", "Completado", "Fallido"
    public bool ConPomodoro { get; set; } = false;
    public int XpOtorgado { get; set; } = 0;
    public DateTime FechaCreacion { get; set; } = DateTime.Now;
    public DateTime? FechaCompletado { get; set; }

    // Claves foráneas
    public int UsuarioId { get; set; }
    public int CategoriaId { get; set; }

    // Navegación
    public Usuario Usuario { get; set; }
    public Categoria Categoria { get; set; }
    public ICollection<SesionPomodoro> SesionesPomodoro { get; set; }
}
```

#### `SesionPomodoro.cs`
```csharp
public class SesionPomodoro
{
    public int Id { get; set; }
    public DateTime FechaInicio { get; set; }
    public DateTime? FechaFin { get; set; }
    public int CiclosCompletados { get; set; } = 0;
    public int XpOtorgado { get; set; } = 0;
    public bool FueCompletada { get; set; } = false;

    // Claves foráneas
    public int UsuarioId { get; set; }
    public int? HabitoId { get; set; }          // Opcional
    public int? MisionId { get; set; }          // Opcional

    // Navegación
    public Usuario Usuario { get; set; }
    public Habito? Habito { get; set; }
    public Mision? Mision { get; set; }
}
```

#### `EstadoAnimo.cs`
```csharp
public class EstadoAnimo
{
    public int Id { get; set; }
    public DateOnly Fecha { get; set; }
    public string Estado { get; set; }          // "Feliz", "Bien", "Neutral", "Cansado", "Estresado"
    public string? Nota { get; set; }
    public DateTime FechaRegistro { get; set; } = DateTime.Now;

    // Clave foránea
    public int UsuarioId { get; set; }

    // Navegación
    public Usuario Usuario { get; set; }
}
```

#### `Nivel.cs`
```csharp
public class Nivel
{
    public int Id { get; set; }
    public int Numero { get; set; }             // 0 al 20
    public string Titulo { get; set; }
    public int XpRequerido { get; set; }
    public string? Descripcion { get; set; }

    // Navegación
    public ICollection<ProgresoUsuario> Progresos { get; set; }
}
```

#### `Personaje.cs`
```csharp
public class Personaje
{
    public int Id { get; set; }
    public string Nombre { get; set; }
    public string Genero { get; set; }          // "Masculino", "Femenino"
    public bool EstaActivo { get; set; } = true;
    public int? CarreraId { get; set; }         // null = genérico para todas

    // Navegación
    public Carrera? Carrera { get; set; }
    public ICollection<ImagenNivelPersonaje> Imagenes { get; set; }
    public ICollection<PersonajeUsuario> PersonajesUsuario { get; set; }
}
```

#### `ImagenNivelPersonaje.cs`
```csharp
public class ImagenNivelPersonaje
{
    public int Id { get; set; }
    public int NivelNumero { get; set; }        // 0 al 20
    public string ImagenUrl { get; set; }
    public bool EsPlaceholder { get; set; } = false;

    // Clave foránea
    public int PersonajeId { get; set; }

    // Navegación
    public Personaje Personaje { get; set; }
}
```

---

## 2. Arquitectura MVC vs API — Separación Clara

### 2.1 Decisión de Arquitectura

Este proyecto usa **ASP.NET Core MVC con Razor Views** como arquitectura principal. Dentro del mismo proyecto existen dos tipos de controladores:

```
EpycusWeb/
├── Controllers/              ← Controladores MVC (devuelven Views HTML)
│   ├── DashboardController.cs
│   ├── HabitosController.cs
│   └── ...
└── Controllers/Api/          ← Controladores API (devuelven JSON para AJAX)
    ├── ApiHabitosController.cs
    ├── ApiPomodoroController.cs
    ├── ApiGamificacionController.cs
    └── ApiEstadoAnimoController.cs
```

### 2.2 ¿Cuándo usar MVC (Razor View)?

Usar cuando la acción carga una página completa:
- Navegar al módulo de Hábitos → `GET /habitos` → devuelve `Index.cshtml`
- Abrir formulario de crear hábito → `GET /habitos/crear` → devuelve `Crear.cshtml`
- Hacer login → `POST /autenticacion/login` → redirige o devuelve `Login.cshtml` con errores

### 2.3 ¿Cuándo usar API (JSON)?

Usar cuando la acción ocurre **sin recargar la página** (AJAX desde JavaScript):
- Marcar un hábito como completado con un click → `POST /api/habitos/{id}/completar`
- Guardar el estado de ánimo diario → `POST /api/estado-animo`
- Actualizar el temporizador Pomodoro → `POST /api/pomodoro/iniciar`
- Obtener el XP actualizado después de una acción → `GET /api/gamificacion/mi-progreso`

### 2.4 Decoradores de los Controladores

```csharp
// Controlador MVC — devuelve Views
public class HabitosController : Controller { ... }

// Controlador API — devuelve JSON
[ApiController]
[Route("api/[controller]")]
public class ApiHabitosController : ControllerBase { ... }
```

---

## 3. Catálogo de Endpoints

### 3.1 Rutas MVC (Razor Views)

| Método | Ruta | Controlador / Acción | Auth | Descripción |
|---|---|---|---|---|
| GET | `/autenticacion/login` | `Autenticacion/Login` | No | Formulario de login |
| POST | `/autenticacion/login` | `Autenticacion/Login` | No | Procesar login |
| GET | `/autenticacion/registro` | `Autenticacion/Registro` | No | Formulario de registro |
| POST | `/autenticacion/registro` | `Autenticacion/Registro` | No | Procesar registro |
| GET | `/autenticacion/completar-perfil` | `Autenticacion/CompletarPerfil` | Sí | Completar datos tras Google |
| POST | `/autenticacion/completar-perfil` | `Autenticacion/CompletarPerfil` | Sí | Guardar datos faltantes |
| GET | `/autenticacion/verificar-correo` | `Autenticacion/VerificarCorreo` | No | Verificar token de email |
| GET | `/autenticacion/recuperar` | `Autenticacion/RecuperarContrasena` | No | Solicitar recuperación |
| POST | `/autenticacion/recuperar` | `Autenticacion/RecuperarContrasena` | No | Enviar correo |
| POST | `/autenticacion/nueva-contrasena` | `Autenticacion/NuevaContrasena` | No | Guardar nueva contraseña |
| GET | `/autenticacion/cerrar-sesion` | `Autenticacion/CerrarSesion` | Sí | Logout |
| GET | `/` | `Dashboard/Index` | Sí | Dashboard principal |
| GET | `/habitos` | `Habitos/Index` | Sí | Lista de hábitos |
| GET | `/habitos/crear` | `Habitos/Crear` | Sí | Formulario crear hábito |
| POST | `/habitos/crear` | `Habitos/Crear` | Sí | Guardar nuevo hábito |
| GET | `/habitos/editar/{id}` | `Habitos/Editar` | Sí | Formulario editar |
| POST | `/habitos/editar/{id}` | `Habitos/Editar` | Sí | Guardar edición |
| POST | `/habitos/eliminar/{id}` | `Habitos/Eliminar` | Sí | Eliminar hábito |
| GET | `/pomodoro` | `Pomodoro/Index` | Sí | Módulo Pomodoro |
| GET | `/misiones` | `Misiones/Index` | Sí | Lista de misiones |
| GET | `/misiones/crear` | `Misiones/Crear` | Sí | Formulario crear misión |
| POST | `/misiones/crear` | `Misiones/Crear` | Sí | Guardar misión |
| GET | `/misiones/editar/{id}` | `Misiones/Editar` | Sí | Formulario editar |
| POST | `/misiones/editar/{id}` | `Misiones/Editar` | Sí | Guardar edición |
| POST | `/misiones/eliminar/{id}` | `Misiones/Eliminar` | Sí | Eliminar misión |
| GET | `/progreso` | `Progreso/Index` | Sí | Estadísticas y logros |
| GET | `/perfil` | `Perfil/Index` | Sí | Perfil del usuario |
| POST | `/perfil/editar` | `Perfil/Editar` | Sí | Guardar cambios de perfil |
| POST | `/perfil/cambiar-personaje/{id}` | `Perfil/CambiarPersonaje` | Sí | Cambiar personaje activo |
| GET | `/ajustes` | `Ajustes/Index` | Sí | Pantalla de ajustes |
| POST | `/ajustes/cambiar-contrasena` | `Ajustes/CambiarContrasena` | Sí | Actualizar contraseña |
| POST | `/ajustes/cambiar-tema/{temaId}` | `Ajustes/CambiarTema` | Sí | Cambiar tema visual |
| POST | `/ajustes/desvincular-google` | `Ajustes/DesvincularGoogle` | Sí | Quitar Google OAuth |
| POST | `/ajustes/eliminar-cuenta` | `Ajustes/EliminarCuenta` | Sí | Borrar cuenta completa |
| GET | `/admin` | `Admin/Index` | Admin | Panel admin |
| GET | `/admin/usuarios` | `Admin/Usuarios` | Admin | Lista de usuarios |
| GET | `/admin/usuario/{id}` | `Admin/DetalleUsuario` | Admin | Detalle de un usuario |
| POST | `/admin/suscripcion/activar/{usuarioId}` | `Admin/ActivarSuscripcion` | Admin | Activar premium |
| POST | `/admin/suscripcion/desactivar/{usuarioId}` | `Admin/DesactivarSuscripcion` | Admin | Quitar premium |
| GET | `/admin/frases` | `Admin/Frases` | Admin | Gestionar frases |
| POST | `/admin/frases/crear` | `Admin/CrearFrase` | Admin | Agregar frase |
| POST | `/admin/frases/eliminar/{id}` | `Admin/EliminarFrase` | Admin | Eliminar frase |

---

### 3.2 Rutas API (JSON — usadas por AJAX desde JavaScript)

**Base URL:** `/api/`  
**Headers requeridos:** `Content-Type: application/json` · `Authorization: Bearer {jwt_token}`

---

#### AUTH
| Método | Ruta | Auth | Request Body | Response |
|---|---|---|---|---|
| POST | `/api/auth/login` | No | `{ correo, contrasena }` | `{ token, refreshToken, usuario }` |
| POST | `/api/auth/refresh` | No | `{ refreshToken }` | `{ token, refreshToken }` |
| POST | `/api/auth/logout` | Sí | — | `{ success: true }` |

---

#### HÁBITOS
| Método | Ruta | Auth | Request Body | Response |
|---|---|---|---|---|
| GET | `/api/habitos` | Sí | — | `[{ id, nombre, estado, rachaActual, ... }]` |
| GET | `/api/habitos/hoy` | Sí | — | `[{ id, nombre, estadoHoy, xpPotencial }]` |
| POST | `/api/habitos/{id}/completar` | Sí | — | `{ xpGanado, nuevoXpTotal, subioDeNivel, nivelNuevo? }` |
| POST | `/api/habitos/{id}/fallar` | Sí | — | `{ rachaRota: bool }` |
| GET | `/api/habitos/{id}/semana` | Sí | — | `[{ dia, estado }]` × 7 días |

---

#### POMODORO
| Método | Ruta | Auth | Request Body | Response |
|---|---|---|---|---|
| POST | `/api/pomodoro/iniciar` | Sí | `{ habitoId?, misionId? }` | `{ sesionId, fechaInicio }` |
| POST | `/api/pomodoro/{sesionId}/ciclo-completado` | Sí | `{ ciclosCompletados }` | `{ xpGanado, sugerirDescanso: bool }` |
| POST | `/api/pomodoro/{sesionId}/finalizar` | Sí | `{ ciclosCompletados }` | `{ xpTotal, sesionGuardada: true }` |
| POST | `/api/pomodoro/{sesionId}/cancelar` | Sí | — | `{ success: true }` |
| GET | `/api/pomodoro/configuracion` | Sí | — | `{ tiempoEstudio, tiempoDescanso, ciclos, ... }` |
| PUT | `/api/pomodoro/configuracion` | Sí | `{ tiempoEstudio, tiempoDescanso, ... }` | `{ success: true }` |
| GET | `/api/pomodoro/tip-aleatorio` | Sí | — | `{ tip }` |

---

#### MISIONES
| Método | Ruta | Auth | Request Body | Response |
|---|---|---|---|---|
| POST | `/api/misiones/{id}/completar` | Sí | — | `{ xpGanado, nuevoXpTotal, subioDeNivel }` |
| POST | `/api/misiones/{id}/estado` | Sí | `{ estado }` | `{ success: true }` |

---

#### GAMIFICACIÓN
| Método | Ruta | Auth | Request Body | Response |
|---|---|---|---|---|
| GET | `/api/gamificacion/mi-progreso` | Sí | — | `{ xpTotal, nivel, titulo, rachaActual, xpParaSiguienteNivel, porcentajeProgreso, imagenPersonaje }` |
| GET | `/api/gamificacion/logros` | Sí | — | `[{ logro, desbloqueado, progreso, meta }]` |

---

#### ESTADO DE ÁNIMO
| Método | Ruta | Auth | Request Body | Response |
|---|---|---|---|---|
| POST | `/api/estado-animo` | Sí | `{ estado, nota? }` | `{ success: true, alertaBienestar? }` |
| GET | `/api/estado-animo/historial` | Sí | — | `[{ fecha, estado, nota }]` |

---

#### DASHBOARD
| Método | Ruta | Auth | Request Body | Response |
|---|---|---|---|---|
| GET | `/api/dashboard/resumen` | Sí | — | `{ kpis, habitosPendientes, misionesPendientes, frase }` |
| GET | `/api/dashboard/frase-del-dia` | Sí | — | `{ frase, autor }` |

---

### 3.3 Contrato de Respuesta Estándar de la API

Todos los endpoints API deben devolver la misma estructura:

```csharp
// Clase: Ayudantes/RespuestaApi.cs
public class RespuestaApi<T>
{
    public bool Exito { get; set; }
    public string? Mensaje { get; set; }
    public T? Datos { get; set; }
    public List<string>? Errores { get; set; }

    public static RespuestaApi<T> Exitosa(T datos, string? mensaje = null)
        => new() { Exito = true, Datos = datos, Mensaje = mensaje };

    public static RespuestaApi<T> Fallida(string mensaje, List<string>? errores = null)
        => new() { Exito = false, Mensaje = mensaje, Errores = errores };
}

// Ejemplo de uso en controlador API:
[HttpPost("{id}/completar")]
public async Task<IActionResult> Completar(int id)
{
    var resultado = await _servicioHabitos.CompletarHabito(id, ObtenerUsuarioIdActual());
    if (!resultado.Exito)
        return BadRequest(RespuestaApi<object>.Fallida(resultado.Mensaje));

    return Ok(RespuestaApi<ResultadoXpDto>.Exitosa(resultado.Datos));
}
```

---

## 4. Reglas de Negocio Determinísticas

### 4.1 Sistema de XP — Lógica Exacta

```csharp
// Clase: Ayudantes/CalculadorXP.cs

public static class CalculadorXP
{
    // XP por acción
    public const int XP_LOGIN_DIARIO           = 10;
    public const int XP_HABITO_COMPLETADO      = 20;
    public const int XP_BONUS_RACHA_7_DIAS     = 10;   // extra por cada hábito con racha múltiplo de 7
    public const int XP_MISION_BAJA            = 30;
    public const int XP_MISION_MEDIA           = 50;
    public const int XP_MISION_ALTA            = 80;
    public const int XP_POMODORO_POR_CICLO     = 15;
    public const int XP_BONUS_RACHA_GLOBAL_7   = 50;   // racha global de 7 días
    public const int XP_BONUS_RACHA_GLOBAL_30  = 200;  // racha global de 30 días
    public const int NIVEL_MAXIMO              = 20;

    /// <summary>
    /// XP adicional necesario para pasar del nivel actual al siguiente.
    /// Fórmula: 100 + (nivelActual × 50)
    /// </summary>
    public static int XpParaSiguienteNivel(int nivelActual)
    {
        if (nivelActual >= NIVEL_MAXIMO) return 0;
        return 100 + (nivelActual * 50);
    }

    /// <summary>
    /// XP total acumulado necesario para ALCANZAR un nivel.
    /// Suma de todos los XP requeridos desde nivel 0.
    /// </summary>
    public static int XpTotalParaNivel(int nivel)
    {
        int total = 0;
        for (int i = 0; i < nivel; i++)
            total += XpParaSiguienteNivel(i);
        return total;
    }

    /// <summary>
    /// Determina el nivel correspondiente a un total de XP.
    /// </summary>
    public static int NivelParaXp(int xpTotal)
    {
        int nivel = 0;
        while (nivel < NIVEL_MAXIMO && xpTotal >= XpTotalParaNivel(nivel + 1))
            nivel++;
        return nivel;
    }

    /// <summary>
    /// XP del usuario dentro de su nivel actual (para mostrar barra de progreso).
    /// </summary>
    public static int XpDentroDelNivelActual(int xpTotal, int nivelActual)
        => xpTotal - XpTotalParaNivel(nivelActual);

    /// <summary>
    /// Porcentaje de progreso hacia el siguiente nivel (0 a 100).
    /// </summary>
    public static decimal PorcentajeProgreso(int xpTotal, int nivelActual)
    {
        if (nivelActual >= NIVEL_MAXIMO) return 100;
        int xpEnNivel = XpDentroDelNivelActual(xpTotal, nivelActual);
        int xpNecesario = XpParaSiguienteNivel(nivelActual);
        return Math.Round((decimal)xpEnNivel / xpNecesario * 100, 1);
    }

    /// <summary>
    /// XP por completar una misión según su prioridad.
    /// </summary>
    public static int XpPorMision(string prioridad) => prioridad switch
    {
        "Alta"  => XP_MISION_ALTA,
        "Media" => XP_MISION_MEDIA,
        "Baja"  => XP_MISION_BAJA,
        _       => XP_MISION_BAJA
    };
}
```

---

### 4.2 Sistema de Rachas — Lógica Exacta

```
DEFINICIÓN: Un día "activo" es aquel en que el usuario completa al menos 1 hábito programado.

CASO 1 — Usuario activo hoy:
  Condición: Hoy hay al menos 1 hábito marcado como "Completado"
  Acción:
    - Si FechaUltimaActividad == ayer → RachaActual++
    - Si FechaUltimaActividad == hoy  → no cambiar (ya se contó)
    - Si FechaUltimaActividad es anterior a ayer → racha rota (ver CASO 3)
    - Actualizar FechaUltimaActividad = hoy
    - DiaDeGraciaUsado = false (se resetea al ser activo de nuevo)
    - Si RachaActual > RachaMaxima → RachaMaxima = RachaActual
    - Verificar bonuses de racha (7 días, 30 días)

CASO 2 — Día de gracia:
  Condición:
    - Hoy == FechaUltimaActividad + 2 días
    - DiaDeGraciaUsado == false
    - El usuario hace al menos 1 acción de login
  Acción:
    - DiaDeGraciaUsado = true
    - La racha NO se rompe
    - Mostrar notificación: "¡Usaste tu día de gracia! Mañana debes completar al menos un hábito."
    - NO sumar RachaActual (el día de gracia es un puente, no un día activo)

CASO 3 — Racha rota:
  Condición:
    - Hoy > FechaUltimaActividad + 2 días
    - O: DiaDeGraciaUsado == true Y hoy > FechaUltimaActividad + 1 día sin actividad
  Acción:
    - RachaActual = 0
    - DiaDeGraciaUsado = false
    - FechaInicioRacha = null
    - El XP acumulado NO cambia (nunca se descuenta)
    - Mostrar notificación: "Tu racha se rompió. ¡Empieza una nueva hoy!"

EVALUACIÓN: Se ejecuta en dos momentos:
  1. Al hacer login (verificar si la racha cambió desde ayer)
  2. Job nocturno a las 00:01 (marcar hábitos sin registro como "Fallido")
```

---

### 4.3 Hábitos — Estados Exactos

```
ESTADOS DE UN HÁBITO EN UN DÍA:

"Pendiente":
  - El hábito está programado para hoy
  - Aún no se ha marcado como completado ni fallido
  - Estado por defecto al inicio del día

"Completado":
  - El usuario hizo clic en "Completar" durante el día
  - Se registra en RegistrosHabito con Estado = "Completado"
  - Se otorga XP inmediatamente

"Fallido":
  - El hábito estaba programado para hoy pero el día terminó sin completarse
  - Se marca automáticamente a las 00:01 del día siguiente (job nocturno)
  - O si el usuario activa manualmente el modo "No lo hice hoy"
  - NO se otorga XP
  - Rompe la racha individual del hábito (no la racha global)

REGLA: Un hábito solo puede estar en un estado por día.
Una vez marcado como "Completado", no puede cambiarse a "Fallido" ni viceversa.

¿CUÁNDO ESTÁ PROGRAMADO UN HÁBITO?
  Frecuencia "Diaria":  Todos los días
  Frecuencia "Semanal": Solo el día de la semana configurado
  Frecuencia "Personalizada": Los días guardados en DiasSemana (JSON "[1,3,5]" = Lun/Mié/Vie)

RACHA INDIVIDUAL DEL HÁBITO (Habito.RachaActual):
  - Sube 1 por cada día que se completa
  - Vuelve a 0 cuando el hábito es "Fallido"
  - Es independiente de la racha global del usuario
```

---

### 4.4 Misiones — Estados Exactos y Transiciones

```
ESTADOS DE UNA MISIÓN:

"Pendiente" → Estado inicial al crear
"EnProgreso" → El usuario la inicia manualmente (clic en "Iniciar")
"Completado" → El usuario la marca como terminada → se otorga XP
"Fallido" → La FechaLimite pasó y la misión no estaba "Completado"

TRANSICIONES VÁLIDAS:
  Pendiente   → EnProgreso  (usuario hace clic en "Iniciar")
  Pendiente   → Completado  (usuario la completa sin pasar por EnProgreso)
  EnProgreso  → Completado  (usuario la termina)
  EnProgreso  → Fallido     (usuario abandona O la fecha límite pasó)
  Pendiente   → Fallido     (la fecha límite pasó sin acción)

TRANSICIONES INVÁLIDAS (no permitir en la UI ni en el servicio):
  Completado → cualquier estado (irreversible)
  Fallido    → cualquier estado (irreversible)

REGLA DE FECHA LÍMITE:
  Un job nocturno a las 00:01 revisa todas las misiones donde:
    FechaLimite < hoy AND Estado IN ("Pendiente", "EnProgreso")
  Y las marca como "Fallido" automáticamente.

XP AL COMPLETAR:
  Baja:  30 XP
  Media: 50 XP
  Alta:  80 XP
  Se guarda en Mision.XpOtorgado y se suma al ProgresosUsuario.XpTotal
```

---

### 4.5 Pomodoro — Reglas Exactas

```
CICLO ESTÁNDAR (configurable por usuario):
  Tiempo de estudio:       25 min (configurable: 5–90 min)
  Tiempo de descanso:      5 min  (configurable: 1–30 min)
  Tiempo descanso largo:   15 min (configurable: 10–60 min)
  Ciclos antes de largo:   4      (configurable: 2–8)

FLUJO DE UNA SESIÓN:
  1. Usuario hace clic en "Iniciar" → POST /api/pomodoro/iniciar
     → Se crea SesionPomodoro con FechaInicio = ahora
  2. Temporizador corre en el navegador (JavaScript puro, sin servidor)
  3. Al completar cada ciclo → POST /api/pomodoro/{id}/ciclo-completado
     → Se otorga XP: 15 XP por ciclo
     → Si ciclosCompletados >= 4 → sugerirDescanso = true
  4. Al terminar la sesión → POST /api/pomodoro/{id}/finalizar
     → Se registra FechaFin y FueCompletada = true
  5. Si el usuario cancela → POST /api/pomodoro/{id}/cancelar
     → FueCompletada = false, pero los ciclos completados se guardan

XP POR POMODORO:
  Por cada ciclo completado: +15 XP (se otorga ciclo por ciclo, no al final)

REGLA DE USO EXCESIVO (ODS 3):
  Si ciclosCompletados >= 4 en una sola sesión:
    → Devolver sugerirDescanso = true en la respuesta
    → En el frontend: mostrar mensaje "Llevas 4 ciclos seguidos. ¡Tómate un descanso largo!"
  Si el usuario inicia una nueva sesión con menos de 15 minutos desde la última:
    → Mostrar advertencia: "Acabas de terminar una sesión. ¿Seguro que quieres continuar?"
```

---

### 4.6 Productividad Diaria — Cálculo Exacto

```csharp
// En ServicioGamificacion.cs

public async Task<decimal> CalcularProductividadDiaria(int usuarioId)
{
    var hoy = DateOnly.FromDateTime(DateTime.Today);

    // Obtener todos los hábitos programados para hoy
    var habitosProgramadosHoy = await _contexto.Habitos
        .Where(h => h.UsuarioId == usuarioId && h.EstaActivo)
        .Where(h => EstaProgradoParaHoy(h, hoy))
        .CountAsync();

    if (habitosProgramadosHoy == 0) return 0; // Sin hábitos hoy

    // Obtener cuántos se completaron hoy
    var habitosCompletadosHoy = await _contexto.RegistrosHabito
        .Where(r => r.Habito.UsuarioId == usuarioId
                 && r.Fecha == hoy
                 && r.Estado == "Completado")
        .CountAsync();

    return Math.Round((decimal)habitosCompletadosHoy / habitosProgramadosHoy * 100, 1);
}

private bool EstaProgradoParaHoy(Habito habito, DateOnly hoy)
{
    return habito.Frecuencia switch
    {
        "Diaria"        => true,
        "Semanal"       => (int)hoy.DayOfWeek == ObtenerDiaSemana(habito),
        "Personalizada" => ObtenerDiasSemana(habito.DiasSemana).Contains((int)hoy.DayOfWeek),
        _               => false
    };
}
```

---

## 5. Lógica ODS 3 — Bienestar Explícito

> ODS 3: Salud y Bienestar. Las siguientes reglas generan alertas y sugerencias automáticas.  
> Se evalúan al hacer login, al registrar estado de ánimo, y en el job nocturno.

### 5.1 Reglas de Alerta Activa

```csharp
// Clase: Servicios/Interfaces/IServicioBienestar.cs
// Implementación: Servicios/Implementaciones/ServicioBienestar.cs

public class AlertaBienestar
{
    public string Tipo { get; set; }        // "Descanso", "Estres", "Sobrecarga", "Logro"
    public string Mensaje { get; set; }
    public string Icono { get; set; }
    public bool EsCritica { get; set; }
}

public class ServicioBienestar
{
    // REGLA 1: Uso excesivo de Pomodoro
    // Si el usuario completó más de 4 ciclos Pomodoro sin registrar
    // un descanso (estado de ánimo posterior), generar alerta.
    public async Task<AlertaBienestar?> VerificarUsoExcesivoPomodoro(int usuarioId)
    {
        var hoy = DateTime.Today;
        var ciclosHoy = await _contexto.SesionesPomodoro
            .Where(s => s.UsuarioId == usuarioId
                     && s.FechaInicio.Date == hoy
                     && s.FueCompletada)
            .SumAsync(s => s.CiclosCompletados);

        if (ciclosHoy > 8)
            return new AlertaBienestar
            {
                Tipo = "Sobrecarga",
                Mensaje = "Llevas más de 8 ciclos Pomodoro hoy. Es momento de una pausa real. Tu cerebro lo necesita.",
                Icono = "bi-exclamation-triangle",
                EsCritica = true
            };

        return null;
    }

    // REGLA 2: Estado de ánimo negativo consecutivo
    // Si el usuario registró "Cansado" o "Estresado" 3 o más días seguidos.
    public async Task<AlertaBienestar?> VerificarAnimoNegativoConsecutivo(int usuarioId)
    {
        var ultimosTresEstados = await _contexto.EstadosAnimo
            .Where(e => e.UsuarioId == usuarioId)
            .OrderByDescending(e => e.Fecha)
            .Take(3)
            .Select(e => e.Estado)
            .ToListAsync();

        bool tresNegativosConsecutivos = ultimosTresEstados.Count == 3
            && ultimosTresEstados.All(e => e == "Cansado" || e == "Estresado");

        if (tresNegativosConsecutivos)
            return new AlertaBienestar
            {
                Tipo = "Estres",
                Mensaje = "Has registrado estrés o cansancio 3 días seguidos. Considera reducir la carga de hábitos por hoy y priorizar el descanso.",
                Icono = "bi-heart-pulse",
                EsCritica = true
            };

        return null;
    }

    // REGLA 3: Hábito de sueño no registrado
    // Si el usuario tiene un hábito de categoría "Sueño" y no lo ha completado
    // en los últimos 3 días, generar sugerencia.
    public async Task<AlertaBienestar?> VerificarHabitoSueno(int usuarioId)
    {
        var categoriasSueno = new[] { "Sueño" };
        var hace3dias = DateOnly.FromDateTime(DateTime.Today.AddDays(-3));

        var tieneHabitoSueno = await _contexto.Habitos
            .AnyAsync(h => h.UsuarioId == usuarioId
                        && h.EstaActivo
                        && categoriasSueno.Contains(h.Categoria.Nombre));

        if (!tieneHabitoSueno) return null;

        var suenoCumplido = await _contexto.RegistrosHabito
            .AnyAsync(r => r.Habito.UsuarioId == usuarioId
                        && r.Habito.Categoria.Nombre == "Sueño"
                        && r.Fecha >= hace3dias
                        && r.Estado == "Completado");

        if (!suenoCumplido)
            return new AlertaBienestar
            {
                Tipo = "Descanso",
                Mensaje = "No has registrado tu hábito de sueño en 3 días. El descanso es fundamental para tu rendimiento académico.",
                Icono = "bi-moon-stars",
                EsCritica = false
            };

        return null;
    }

    // REGLA 4: Sobrecarga de misiones vencidas próximas
    // Si el usuario tiene 3 o más misiones con fecha límite en los próximos 2 días.
    public async Task<AlertaBienestar?> VerificarSobrecargaMisiones(int usuarioId)
    {
        var en2dias = DateOnly.FromDateTime(DateTime.Today.AddDays(2));
        var hoy = DateOnly.FromDateTime(DateTime.Today);

        var misionesUrgentes = await _contexto.Misiones
            .CountAsync(m => m.UsuarioId == usuarioId
                          && m.FechaLimite >= hoy
                          && m.FechaLimite <= en2dias
                          && (m.Estado == "Pendiente" || m.Estado == "EnProgreso"));

        if (misionesUrgentes >= 3)
            return new AlertaBienestar
            {
                Tipo = "Sobrecarga",
                Mensaje = $"Tienes {misionesUrgentes} misiones que vencen en los próximos 2 días. Prioriza y divide el trabajo en sesiones Pomodoro.",
                Icono = "bi-lightning",
                EsCritica = true
            };

        return null;
    }

    // REGLA 5: Recomendación de pausas activas en Pomodoro
    // Se envía en la respuesta de ciclo-completado cuando corresponde.
    public string? RecomendacionPausaActiva(int ciclosCompletados)
    {
        return ciclosCompletados switch
        {
            2 => "Estira los dedos y mueve las muñecas. 30 segundos.",
            4 => "Párate, camina y mira por la ventana. 5 minutos.",
            6 => "Come algo ligero y toma agua. Tu cerebro necesita glucosa.",
            _ => null
        };
    }

    // REGLA 6: Racha alta — refuerzo positivo
    // Mensaje especial cuando el usuario lleva una racha importante.
    public string? MensajeRefuerzoRacha(int rachaActual)
    {
        return rachaActual switch
        {
            7  => "¡Una semana perfecta! Estás construyendo un hábito real.",
            14 => "¡Dos semanas sin parar! Eres constante de verdad.",
            30 => "¡Un mes entero! Eso ya es una transformación de vida.",
            _  => null
        };
    }
}
```

### 5.2 Cómo se Muestran las Alertas

```
Las alertas de bienestar se evalúan en dos momentos:

1. Al hacer login diario:
   → El DashboardController llama a IServicioBienestar.ObtenerAlertasActivas(usuarioId)
   → Devuelve una lista de AlertaBienestar
   → Se renderizan en el Dashboard como notificaciones destacadas

2. Al registrar el estado de ánimo (POST /api/estado-animo):
   → Se evalúa VerificarAnimoNegativoConsecutivo() inmediatamente
   → Si hay alerta, se devuelve en la respuesta JSON como alertaBienestar

Las alertas NO son bloqueantes. El usuario puede ignorarlas y seguir usando la app.
```

---

## 6. Seguridad y Autorización

### 6.1 Hash de Contraseñas

```csharp
// Usar BCrypt.Net-Next en ServicioAutenticacion.cs

// Al registrar:
string hashContrasena = BCrypt.Net.BCrypt.HashPassword(dto.Contrasena, workFactor: 12);

// Al hacer login:
bool esValida = BCrypt.Net.BCrypt.Verify(dto.Contrasena, usuario.ContrasenaHash);

// NUNCA guardar la contraseña en texto plano.
// NUNCA usar MD5 ni SHA1 sin salt.
```

---

### 6.2 Validación de Contraseñas

```
Requisitos mínimos (validar en el ViewModel y en el servicio):
  - Mínimo 8 caracteres
  - Al menos 1 letra mayúscula
  - Al menos 1 letra minúscula
  - Al menos 1 número
  - Al menos 1 carácter especial: !@#$%^&*

Regex: ^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$

Mensaje de error: "La contraseña debe tener mínimo 8 caracteres,
una mayúscula, una minúscula, un número y un carácter especial."
```

---

### 6.3 Generación de JWT

```csharp
// En ServicioAutenticacion.cs

private string GenerarToken(Usuario usuario)
{
    var claims = new[]
    {
        new Claim(ClaimTypes.NameIdentifier, usuario.Id.ToString()),
        new Claim(ClaimTypes.Email, usuario.CorreoElectronico),
        new Claim(ClaimTypes.Name, usuario.Nombre),
        new Claim(ClaimTypes.Role, usuario.Rol.Nombre),
        new Claim("CodigoUnico", usuario.CodigoUnico),
        new Claim("CarreraId", usuario.CarreraId.ToString()),
    };

    var clave = new SymmetricSecurityKey(
        Encoding.UTF8.GetBytes(_config["Jwt:Clave"]));
    var credenciales = new SigningCredentials(clave, SecurityAlgorithms.HmacSha256);

    var token = new JwtSecurityToken(
        issuer:   _config["Jwt:Emisor"],
        audience: _config["Jwt:Audiencia"],
        claims:   claims,
        expires:  DateTime.UtcNow.AddMinutes(int.Parse(_config["Jwt:ExpiracionMinutos"])),
        signingCredentials: credenciales
    );

    return new JwtSecurityTokenHandler().WriteToken(token);
}
```

---

### 6.4 Protección de Rutas por Rol

```csharp
// Controladores MVC — usuario autenticado cualquier rol:
[Authorize]
public class HabitosController : Controller { ... }

[Authorize]
public class DashboardController : Controller { ... }

// Controladores MVC — solo Administrador:
[Authorize(Roles = "Administrador")]
public class AdminController : Controller { ... }

// Controladores API — usuario autenticado:
[ApiController]
[Authorize]
[Route("api/[controller]")]
public class ApiHabitosController : ControllerBase { ... }

// Acción individual con rol:
[HttpPost("suscripcion/activar/{usuarioId}")]
[Authorize(Roles = "Administrador")]
public async Task<IActionResult> ActivarSuscripcion(int usuarioId) { ... }

// Rutas públicas (sin autenticación):
[AllowAnonymous]
public IActionResult Login() { ... }
```

---

### 6.5 Protección de Datos Propios

```csharp
// REGLA: Un usuario solo puede ver y modificar sus propios datos.
// Siempre validar que el recurso pertenece al usuario autenticado.

// CORRECTO ✅
public async Task<IActionResult> EditarHabito(int id, EditarHabitoViewModel modelo)
{
    var usuarioId = ObtenerUsuarioIdActual();
    var habito = await _servicioHabitos.ObtenerPorId(id);

    if (habito == null) return NotFound();
    if (habito.UsuarioId != usuarioId) return Forbid(); // ← Validación clave

    // ... continuar
}

// INCORRECTO ❌ — nunca hacer esto (permite ver datos de otros usuarios)
public async Task<IActionResult> EditarHabito(int id)
{
    var habito = await _servicioHabitos.ObtenerPorId(id); // sin verificar dueño
    return View(habito);
}
```

---

### 6.6 Generación del Código Único de Usuario

```csharp
// Clase: Ayudantes/GeneradorCodigo.cs

public static class GeneradorCodigo
{
    /// <summary>
    /// Genera un código único en formato EPY-XXXXXXXX (8 caracteres alfanuméricos).
    /// Verificar en BD que no exista antes de guardar.
    /// </summary>
    public static string GenerarCodigoUsuario()
    {
        const string caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"; // sin 0,O,I,1 para evitar confusión
        var random = new Random();
        var codigo = new string(
            Enumerable.Range(0, 8)
                      .Select(_ => caracteres[random.Next(caracteres.Length)])
                      .ToArray()
        );
        return $"EPY-{codigo}";
    }
}

// Uso en ServicioAutenticacion.cs al registrar usuario:
string codigoUnico;
do {
    codigoUnico = GeneradorCodigo.GenerarCodigoUsuario();
} while (await _contexto.Usuarios.AnyAsync(u => u.CodigoUnico == codigoUnico));
// El loop garantiza unicidad
```

---

## 7. Configuración de Entity Framework Core

### 7.1 `ContextoAplicacion.cs` — Estructura Base

```csharp
// Datos/ContextoAplicacion.cs

public class ContextoAplicacion : DbContext
{
    public ContextoAplicacion(DbContextOptions<ContextoAplicacion> options) : base(options) { }

    // DbSets — uno por entidad
    public DbSet<Usuario> Usuarios { get; set; }
    public DbSet<Rol> Roles { get; set; }
    public DbSet<Carrera> Carreras { get; set; }
    public DbSet<Nivel> Niveles { get; set; }
    public DbSet<ProgresoUsuario> ProgresosUsuario { get; set; }
    public DbSet<Personaje> Personajes { get; set; }
    public DbSet<ImagenNivelPersonaje> ImagenesNivelPersonaje { get; set; }
    public DbSet<PersonajeUsuario> PersonajesUsuario { get; set; }
    public DbSet<Logro> Logros { get; set; }
    public DbSet<LogroUsuario> LogrosUsuario { get; set; }
    public DbSet<Categoria> Categorias { get; set; }
    public DbSet<Habito> Habitos { get; set; }
    public DbSet<RegistroHabito> RegistrosHabito { get; set; }
    public DbSet<ConfiguracionPomodoro> ConfiguracionesPomodoro { get; set; }
    public DbSet<SesionPomodoro> SesionesPomodoro { get; set; }
    public DbSet<Mision> Misiones { get; set; }
    public DbSet<EstadoAnimo> EstadosAnimo { get; set; }
    public DbSet<FraseMotivacional> FrasesMotivacionales { get; set; }
    public DbSet<TipPomodoro> TipsPomodoro { get; set; }
    public DbSet<Tema> Temas { get; set; }
    public DbSet<TemaUsuario> TemasUsuario { get; set; }
    public DbSet<Suscripcion> Suscripciones { get; set; }
    public DbSet<TokenRefresh> TokensRefresh { get; set; }
    public DbSet<VerificacionCorreo> VerificacionesCorreo { get; set; }
    public DbSet<RecuperacionContrasena> RecuperacionesContrasena { get; set; }
    public DbSet<Log> Logs { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);

        // ── Usuario ──────────────────────────────────────────────
        modelBuilder.Entity<Usuario>(e =>
        {
            e.HasIndex(u => u.CorreoElectronico).IsUnique();
            e.HasIndex(u => u.CodigoUnico).IsUnique();
            e.HasIndex(u => u.GoogleId).IsUnique();

            e.HasOne(u => u.Rol)
             .WithMany()
             .HasForeignKey(u => u.RolId)
             .OnDelete(DeleteBehavior.Restrict);

            e.HasOne(u => u.Carrera)
             .WithMany(c => c.Usuarios)
             .HasForeignKey(u => u.CarreraId)
             .OnDelete(DeleteBehavior.Restrict);

            e.HasOne(u => u.TemaActual)
             .WithMany()
             .HasForeignKey(u => u.TemaActualId)
             .OnDelete(DeleteBehavior.SetNull);
        });

        // ── ProgresoUsuario ───────────────────────────────────────
        modelBuilder.Entity<ProgresoUsuario>(e =>
        {
            e.HasIndex(p => p.UsuarioId).IsUnique();

            e.HasOne(p => p.Usuario)
             .WithOne(u => u.Progreso)
             .HasForeignKey<ProgresoUsuario>(p => p.UsuarioId)
             .OnDelete(DeleteBehavior.Cascade);

            e.HasOne(p => p.NivelActual)
             .WithMany(n => n.Progresos)
             .HasForeignKey(p => p.NivelActualId)
             .OnDelete(DeleteBehavior.Restrict);
        });

        // ── Habito ────────────────────────────────────────────────
        modelBuilder.Entity<Habito>(e =>
        {
            e.HasOne(h => h.Usuario)
             .WithMany(u => u.Habitos)
             .HasForeignKey(h => h.UsuarioId)
             .OnDelete(DeleteBehavior.Cascade);

            e.HasOne(h => h.Categoria)
             .WithMany(c => c.Habitos)
             .HasForeignKey(h => h.CategoriaId)
             .OnDelete(DeleteBehavior.Restrict);
        });

        // ── RegistroHabito ────────────────────────────────────────
        modelBuilder.Entity<RegistroHabito>(e =>
        {
            e.HasOne(r => r.Habito)
             .WithMany(h => h.Registros)
             .HasForeignKey(r => r.HabitoId)
             .OnDelete(DeleteBehavior.Cascade);
        });

        // ── SesionPomodoro ────────────────────────────────────────
        modelBuilder.Entity<SesionPomodoro>(e =>
        {
            e.HasOne(s => s.Usuario)
             .WithMany(u => u.SesionesPomodoro)
             .HasForeignKey(s => s.UsuarioId)
             .OnDelete(DeleteBehavior.Cascade);

            e.HasOne(s => s.Habito)
             .WithMany(h => h.SesionesPomodoro)
             .HasForeignKey(s => s.HabitoId)
             .OnDelete(DeleteBehavior.SetNull)
             .IsRequired(false);

            e.HasOne(s => s.Mision)
             .WithMany(m => m.SesionesPomodoro)
             .HasForeignKey(s => s.MisionId)
             .OnDelete(DeleteBehavior.SetNull)
             .IsRequired(false);
        });

        // ── Mision ────────────────────────────────────────────────
        modelBuilder.Entity<Mision>(e =>
        {
            e.HasOne(m => m.Usuario)
             .WithMany(u => u.Misiones)
             .HasForeignKey(m => m.UsuarioId)
             .OnDelete(DeleteBehavior.Cascade);

            e.HasOne(m => m.Categoria)
             .WithMany(c => c.Misiones)
             .HasForeignKey(m => m.CategoriaId)
             .OnDelete(DeleteBehavior.Restrict);
        });

        // ── Personaje / Imágenes ──────────────────────────────────
        modelBuilder.Entity<Personaje>(e =>
        {
            e.HasOne(p => p.Carrera)
             .WithMany()
             .HasForeignKey(p => p.CarreraId)
             .OnDelete(DeleteBehavior.SetNull)
             .IsRequired(false);
        });

        modelBuilder.Entity<ImagenNivelPersonaje>(e =>
        {
            e.HasOne(i => i.Personaje)
             .WithMany(p => p.Imagenes)
             .HasForeignKey(i => i.PersonajeId)
             .OnDelete(DeleteBehavior.Cascade);
        });

        // ── Suscripcion ───────────────────────────────────────────
        modelBuilder.Entity<Suscripcion>(e =>
        {
            e.HasOne(s => s.Usuario)
             .WithMany(u => u.Suscripciones)
             .HasForeignKey(s => s.UsuarioId)
             .OnDelete(DeleteBehavior.Cascade);

            // El admin que activó la suscripción — sin cascade para evitar conflicto
            e.HasOne<Usuario>()
             .WithMany()
             .HasForeignKey(s => s.ActivadaPorAdminId)
             .OnDelete(DeleteBehavior.SetNull)
             .IsRequired(false);
        });
    }
}
```

---

### 7.2 Registro en `Program.cs`

```csharp
// Program.cs

var connectionString = builder.Configuration.GetConnectionString("ConexionPrincipal");

builder.Services.AddDbContext<ContextoAplicacion>(options =>
    options.UseMySql(
        connectionString,
        ServerVersion.AutoDetect(connectionString),
        mySqlOptions => mySqlOptions.EnableRetryOnFailure()
    )
);

// Registro de servicios (inyección de dependencias)
builder.Services.AddScoped<IServicioAutenticacion, ServicioAutenticacion>();
builder.Services.AddScoped<IServicioGamificacion, ServicioGamificacion>();
builder.Services.AddScoped<IServicioHabitos, ServicioHabitos>();
builder.Services.AddScoped<IServicioPomodoro, ServicioPomodoro>();
builder.Services.AddScoped<IServicioMisiones, ServicioMisiones>();
builder.Services.AddScoped<IServicioProgreso, ServicioProgreso>();
builder.Services.AddScoped<IServicioPerfil, ServicioPerfil>();
builder.Services.AddScoped<IServicioCorreo, ServicioCorreo>();
builder.Services.AddScoped<IServicioAdmin, ServicioAdmin>();
builder.Services.AddScoped<IServicioBienestar, ServicioBienestar>();

// JWT
builder.Services.AddAuthentication(JwtBearerDefaults.AuthenticationScheme)
    .AddJwtBearer(options =>
    {
        options.TokenValidationParameters = new TokenValidationParameters
        {
            ValidateIssuer           = true,
            ValidateAudience         = true,
            ValidateLifetime         = true,
            ValidateIssuerSigningKey = true,
            ValidIssuer              = builder.Configuration["Jwt:Emisor"],
            ValidAudience            = builder.Configuration["Jwt:Audiencia"],
            IssuerSigningKey         = new SymmetricSecurityKey(
                Encoding.UTF8.GetBytes(builder.Configuration["Jwt:Clave"]))
        };
    })
    .AddGoogle(options =>
    {
        options.ClientId     = builder.Configuration["Google:ClientId"];
        options.ClientSecret = builder.Configuration["Google:ClientSecret"];
    });

builder.Services.AddAuthorization();
builder.Services.AddAutoMapper(typeof(Program));
builder.Services.AddControllersWithViews();
```

---

### 7.3 Comandos de Migración

```bash
# Crear la primera migración (ejecutar en la terminal del proyecto)
dotnet ef migrations add MigracionInicial

# Aplicar migraciones a la BD (crea las tablas en MariaDB)
dotnet ef database update

# Si hay cambios en las entidades, crear nueva migración:
dotnet ef migrations add NombreDescriptivo

# Ver el SQL que generaría la migración (sin ejecutar):
dotnet ef migrations script
```

---

*Documento Técnico Complementario — EPYCUS WEB*  
*Versión 1.0 — Mayo 2026*  
*Leer junto con: EPYCUS_WEB_Documento_Base.md*
