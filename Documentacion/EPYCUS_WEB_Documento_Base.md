# EPYCUS WEB — Documento Base del Proyecto
**ASP.NET 10 Core MVC · MariaDB · Entity Framework Core**  
**Metodología: Scrumban | Equipo: 4 integrantes | Fecha límite: 25 de mayo de 2026**

---

> **¿Para qué sirve este documento?**  
> Es la única fuente de verdad del proyecto. Antes de escribir cualquier línea de código, todos deben leer y respetar este documento. Cuando uses Copilot, pégale la sección relevante como contexto para que genere código consistente con el resto del equipo.

---

## Índice

1. [Esquema de Base de Datos y Reglas de Negocio](#1-esquema-de-base-de-datos-y-reglas-de-negocio)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Convenciones de Código](#3-convenciones-de-código)
4. [Colores, Estilo y Librerías CSS](#4-colores-estilo-y-librerías-css)
5. [División de Módulos por Integrante](#5-división-de-módulos-por-integrante)

---

## 1. Esquema de Base de Datos y Reglas de Negocio

### 1.1 Tablas de la Base de Datos

> **Configuración de conexión:** XAMPP · MariaDB · usuario `root` · sin contraseña  
> **Nombre de la BD:** `epycus_db`

---

#### AUTENTICACION Y USUARIOS

---

**Tabla: `Roles`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Nombre` | VARCHAR(50) | NOT NULL, UNIQUE | `"Usuario"` o `"Administrador"` |

**Seed inicial:**
```sql
INSERT INTO Roles (Nombre) VALUES ('Usuario'), ('Administrador');
```

---

**Tabla: `Carreras`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Nombre` | VARCHAR(150) | NOT NULL | Ej: `"Ingeniería de Sistemas"` |
| `Area` | VARCHAR(100) | NOT NULL | Ej: `"Ingeniería y Tecnología"` |
| `Codigo` | VARCHAR(30) | NOT NULL, UNIQUE | Slug para rutas de imágenes: `"ing-sistemas"` |
| `EstaActiva` | TINYINT(1) | DEFAULT 1 | |

**Seed inicial:**
```sql
INSERT INTO Carreras (Nombre, Area, Codigo) VALUES
('Ingeniería de Sistemas',      'Ingeniería y Tecnología',  'ing-sistemas'),
('Ingeniería Civil',            'Ingeniería y Tecnología',  'ing-civil'),
('Ingeniería Industrial',       'Ingeniería y Tecnología',  'ing-industrial'),
('Administración de Empresas',  'Ciencias Empresariales',   'administracion'),
('Contabilidad',                'Ciencias Empresariales',   'contabilidad'),
('Derecho',                     'Ciencias Jurídicas',       'derecho'),
('Medicina Humana',             'Ciencias de la Salud',     'medicina'),
('Enfermería',                  'Ciencias de la Salud',     'enfermeria'),
('Psicología',                  'Ciencias de la Salud',     'psicologia'),
('Educación',                   'Ciencias de la Educación', 'educacion'),
('Arquitectura',                'Ingeniería y Tecnología',  'arquitectura'),
('Comunicaciones',              'Humanidades',              'comunicaciones');
```

---

**Tabla: `Usuarios`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `CodigoUnico` | VARCHAR(20) | NOT NULL, UNIQUE | Formato: `EPY-XXXXXXXX` generado automáticamente |
| `Nombre` | VARCHAR(150) | NOT NULL | |
| `CorreoElectronico` | VARCHAR(200) | NOT NULL, UNIQUE | |
| `ContrasenaHash` | VARCHAR(255) | NULL | NULL si usa solo Google |
| `FechaNacimiento` | DATE | NOT NULL | |
| `Genero` | ENUM('Masculino','Femenino','Prefiero no decir') | NOT NULL | |
| `CarreraId` | INT | FK → Carreras.Id | |
| `RolId` | INT | FK → Roles.Id, DEFAULT 1 | 1=Usuario, 2=Administrador |
| `GoogleId` | VARCHAR(255) | NULL, UNIQUE | ID de cuenta Google vinculada |
| `FotoGoogleUrl` | VARCHAR(500) | NULL | Foto de perfil de Google |
| `CorreoVerificado` | TINYINT(1) | DEFAULT 0 | |
| `AceptoTerminos` | TINYINT(1) | DEFAULT 0 | |
| `EstaActivo` | TINYINT(1) | DEFAULT 1 | |
| `FechaRegistro` | DATETIME | DEFAULT NOW() | |
| `UltimoAcceso` | DATETIME | NULL | |
| `TemaActualId` | INT | FK → Temas.Id, NULL | Tema visual activo |

---

**Tabla: `TokensRefresh`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `Token` | VARCHAR(500) | NOT NULL | Token de refresco JWT |
| `ExpiraEn` | DATETIME | NOT NULL | |
| `Revocado` | TINYINT(1) | DEFAULT 0 | |
| `FechaCreacion` | DATETIME | DEFAULT NOW() | |

---

**Tabla: `VerificacionesCorreo`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `Token` | VARCHAR(255) | NOT NULL | Token UUID aleatorio |
| `ExpiraEn` | DATETIME | NOT NULL | NOW() + 24 horas |
| `Usado` | TINYINT(1) | DEFAULT 0 | |
| `FechaCreacion` | DATETIME | DEFAULT NOW() | |

---

**Tabla: `RecuperacionesContrasena`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `Token` | VARCHAR(255) | NOT NULL | Token UUID aleatorio |
| `ExpiraEn` | DATETIME | NOT NULL | NOW() + 1 hora |
| `Usado` | TINYINT(1) | DEFAULT 0 | |
| `FechaCreacion` | DATETIME | DEFAULT NOW() | |

---

#### GAMIFICACION

---

**Tabla: `Niveles`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Numero` | INT | NOT NULL, UNIQUE | Del 0 al 20 |
| `Titulo` | VARCHAR(100) | NOT NULL | Título genérico de la etapa profesional |
| `XpRequerido` | INT | NOT NULL | XP acumulado total para alcanzar este nivel |
| `Descripcion` | VARCHAR(255) | NULL | Frase motivacional del nivel |

**Sistema de progresión profesional — 20 niveles:**

> El sistema representa la evolución del usuario desde que llega a la universidad sin experiencia hasta convertirse en un profesional legendario en su carrera. Los títulos son genéricos y se combinan con el nombre de la carrera del usuario en la interfaz.
>
> Ejemplo para Ingeniería de Sistemas: **nivel 6 → "Junior en Ingeniería de Sistemas"**  
> Ejemplo para Medicina: **nivel 10 → "Senior en Medicina"**  
> Ejemplo para Derecho: **nivel 20 → "Abogado Leyenda Viviente"**
>
> **Fórmula de XP:** `XP_para_siguiente_nivel = 100 + (nivel_actual × 50)`

```sql
INSERT INTO Niveles (Numero, Titulo, XpRequerido, Descripcion) VALUES
(0,  'Novato',                   0,      'Aún no llevas el título, pero el camino comienza hoy. Bienvenido.'),
(1,  'Curioso',                  100,    'La curiosidad es el primer paso de todo gran profesional.'),
(2,  'Aprendiz',                 250,    'Estás absorbiendo conocimiento. Cada hábito cuenta.'),
(3,  'Estudiante Comprometido',  450,    'Tu constancia ya te diferencia de la mayoría.'),
(4,  'Practicante',              700,    'Empiezas a aplicar lo que aprendes. El mundo te espera.'),
(5,  'Asistente',                1000,   'Ya formas parte del campo profesional. Sigue creciendo.'),
(6,  'Junior',                   1350,   'Tienes base sólida. Los desafíos reales ya no te asustan.'),
(7,  'Semi-Senior',              1750,   'Tu experiencia empieza a hablar por ti.'),
(8,  'Profesional',              2200,   'Dominas los fundamentos y resuelves problemas con soltura.'),
(9,  'Especialista',             2700,   'Tienes un área en la que pocos te superan.'),
(10, 'Senior',                   3250,   'Mitad del camino. Tu criterio vale más que el de muchos.'),
(11, 'Senior Avanzado',          3850,   'Lideras con el ejemplo. Otros aprenden de ti.'),
(12, 'Experto',                  4500,   'Tu nivel de profundidad es notable. Eres referente.'),
(13, 'Consultor',                5200,   'Te buscan cuando el problema es difícil. Eso es poder.'),
(14, 'Líder',                    5950,   'No solo resuelves: diriges, inspiras y construyes equipos.'),
(15, 'Maestro',                  6750,   'Tu conocimiento trasciende lo técnico. Ya es sabiduría.'),
(16, 'Arquitecto',               7600,   'Diseñas sistemas, estrategias y futuros completos.'),
(17, 'Eminencia',                8500,   'Tu nombre es sinónimo de excelencia en tu campo.'),
(18, 'Gran Maestro',             9450,   'Has forjado a otros profesionales con tu guía.'),
(19, 'Leyenda en Ascenso',       10450,  'El umbral del máximo poder está justo frente a ti.'),
(20, 'Leyenda Viviente',         11500,  'Has llegado a la cima. Eres la definición de tu profesión.');
```

---

**Tabla de referencia XP por nivel:**
| Nivel | Título | XP Acumulado | XP para subir al siguiente |
|---|---|---|---|
| 0 | Novato | 0 | 100 |
| 1 | Curioso | 100 | 150 |
| 2 | Aprendiz | 250 | 200 |
| 3 | Estudiante Comprometido | 450 | 250 |
| 4 | Practicante | 700 | 300 |
| 5 | Asistente | 1,000 | 350 |
| 6 | Junior | 1,350 | 400 |
| 7 | Semi-Senior | 1,750 | 450 |
| 8 | Profesional | 2,200 | 500 |
| 9 | Especialista | 2,700 | 550 |
| 10 | Senior | 3,250 | 600 |
| 11 | Senior Avanzado | 3,850 | 650 |
| 12 | Experto | 4,500 | 700 |
| 13 | Consultor | 5,200 | 750 |
| 14 | Líder | 5,950 | 800 |
| 15 | Maestro | 6,750 | 850 |
| 16 | Arquitecto | 7,600 | 900 |
| 17 | Eminencia | 8,500 | 950 |
| 18 | Gran Maestro | 9,450 | 1,000 |
| 19 | Leyenda en Ascenso | 10,450 | 1,050 |
| 20 | Leyenda Viviente | 11,500 | — |

---

**Tabla: `ProgresosUsuario`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id, UNIQUE | Un registro por usuario |
| `XpTotal` | INT | DEFAULT 0 | XP acumulado total (nunca disminuye) |
| `NivelActualId` | INT | FK → Niveles.Id, DEFAULT 1 | Nivel 0 al inicio |
| `RachaActual` | INT | DEFAULT 0 | Días consecutivos activos |
| `RachaMaxima` | INT | DEFAULT 0 | Máxima racha histórica |
| `FechaUltimaActividad` | DATE | NULL | Último día con al menos 1 acción |
| `FechaInicioRacha` | DATE | NULL | Cuándo inició la racha actual |
| `DiaDeGraciaUsado` | TINYINT(1) | DEFAULT 0 | Si ya usó el día de gracia en esta racha |
| `ProductividadDiaria` | DECIMAL(5,2) | DEFAULT 0.00 | % de hábitos completados hoy |

---

#### PERSONAJES

> Los personajes son ilustraciones originales del equipo: estudiantes universitarios peruanos vestidos con ropa y accesorios propios de su carrera profesional. Cada personaje tiene versión masculina y femenina. La imagen del personaje cambia visualmente conforme el usuario sube de nivel: ropa más elaborada, accesorios profesionales, postura más segura, mayor detalle visual.

---

**Tabla: `Personajes`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Nombre` | VARCHAR(100) | NOT NULL | Nombre del personaje |
| `Genero` | ENUM('Masculino','Femenino') | NOT NULL | |
| `CarreraId` | INT | FK → Carreras.Id, NULL | NULL = genérico (aplica a todas las carreras) |
| `EstaActivo` | TINYINT(1) | DEFAULT 1 | |

**Seed inicial:**
```sql
INSERT INTO Personajes (Nombre, Genero, CarreraId) VALUES
('Kai',  'Masculino', 1),    -- Ingeniería de Sistemas (imagen real disponible)
('Luna', 'Femenino',  1),    -- Ingeniería de Sistemas (imagen real disponible)
('Ares', 'Masculino', NULL), -- Genérico masculino (placeholder para otras carreras)
('Nova', 'Femenino',  NULL); -- Genérico femenino (placeholder para otras carreras)
```

---

**Tabla: `ImagenesNivelPersonaje`**

> Esta tabla registra qué imágenes existen para cada personaje en cada nivel. Permite agregar nuevas imágenes de nivel sin tener las 21 disponibles desde el inicio. El sistema usa la imagen disponible más cercana hacia abajo si la del nivel exacto no existe.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `PersonajeId` | INT | FK → Personajes.Id | |
| `NivelNumero` | INT | NOT NULL | 0 al 20 |
| `ImagenUrl` | VARCHAR(500) | NOT NULL | Ruta relativa: `/img/personajes/...` |
| `EsPlaceholder` | TINYINT(1) | DEFAULT 0 | 1 si es imagen temporal/silueta |

**Seed inicial:**
```sql
-- Kai: imagen real nivel 0 disponible
INSERT INTO ImagenesNivelPersonaje (PersonajeId, NivelNumero, ImagenUrl, EsPlaceholder) VALUES
(1, 0, '/img/personajes/ing-sistemas/masculino/nivel_0.png', 0);

-- Luna: imagen real nivel 0 disponible
INSERT INTO ImagenesNivelPersonaje (PersonajeId, NivelNumero, ImagenUrl, EsPlaceholder) VALUES
(2, 0, '/img/personajes/ing-sistemas/femenino/nivel_0.png', 0);

-- Ares: placeholder genérico masculino
INSERT INTO ImagenesNivelPersonaje (PersonajeId, NivelNumero, ImagenUrl, EsPlaceholder) VALUES
(3, 0, '/img/personajes/generico/masculino/placeholder.png', 1);

-- Nova: placeholder genérico femenino
INSERT INTO ImagenesNivelPersonaje (PersonajeId, NivelNumero, ImagenUrl, EsPlaceholder) VALUES
(4, 0, '/img/personajes/generico/femenino/placeholder.png', 1);
```

**Convención de nombres de archivo:**
```
/img/personajes/{codigo-carrera}/{masculino|femenino}/nivel_{0-20}.png

Ejemplos:
  /img/personajes/ing-sistemas/masculino/nivel_0.png   → Novato: mochila universitaria, ropa casual
  /img/personajes/ing-sistemas/masculino/nivel_5.png   → Asistente: laptop, ropa semiformal
  /img/personajes/ing-sistemas/masculino/nivel_10.png  → Senior: auriculares, tablet, confianza
  /img/personajes/ing-sistemas/masculino/nivel_20.png  → Leyenda: traje, postura de líder total
  /img/personajes/generico/masculino/placeholder.png   → Silueta azul genérica
  /img/personajes/generico/femenino/placeholder.png    → Silueta rosada genérica
```

**Lógica de selección de imagen en el servicio:**
```
1. Obtener nivel actual del usuario (ej: nivel 7)
2. Obtener PersonajeId activo del usuario
3. Buscar en ImagenesNivelPersonaje:
   WHERE PersonajeId = X AND NivelNumero <= 7
   ORDER BY NivelNumero DESC
   LIMIT 1
4. Si no encuentra ninguna → usar placeholder según género del usuario
```

---

**Tabla: `PersonajesUsuario`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `PersonajeId` | INT | FK → Personajes.Id | |
| `EstaSeleccionado` | TINYINT(1) | DEFAULT 0 | Solo uno puede ser true por usuario |
| `FechaObtenido` | DATETIME | DEFAULT NOW() | |

---

#### LOGROS

---

**Tabla: `Logros`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Nombre` | VARCHAR(150) | NOT NULL | |
| `Descripcion` | VARCHAR(300) | NOT NULL | |
| `IconoUrl` | VARCHAR(500) | NOT NULL | `/img/logros/nombre.png` |
| `CondicionTipo` | ENUM('HabitosCompletados','MisionesCompletadas','RachaDias','SesionesPomodoro','XpTotal','NivelAlcanzado') | NOT NULL | |
| `CondicionValor` | INT | NOT NULL | Cantidad requerida para desbloquearlo |
| `XpRecompensa` | INT | DEFAULT 0 | XP bonus al desbloquear |
| `EstaActivo` | TINYINT(1) | DEFAULT 1 | |

**Seed inicial:**
```sql
INSERT INTO Logros (Nombre, Descripcion, IconoUrl, CondicionTipo, CondicionValor, XpRecompensa) VALUES
('Primer Paso',        'Completa tu primer hábito.',              '/img/logros/primer_paso.png',     'HabitosCompletados',  1,   10),
('Semana Perfecta',    'Mantén una racha de 7 días.',             '/img/logros/semana_perfecta.png', 'RachaDias',           7,   50),
('Mes Imparable',      'Mantén una racha de 30 días.',            '/img/logros/mes_imparable.png',   'RachaDias',           30,  200),
('Primera Misión',     'Completa tu primera misión.',             '/img/logros/primera_mision.png',  'MisionesCompletadas', 1,   20),
('Productivo',         'Completa 10 misiones.',                   '/img/logros/productivo.png',      'MisionesCompletadas', 10,  80),
('Maestro del Foco',   'Completa 50 sesiones Pomodoro.',          '/img/logros/maestro_foco.png',    'SesionesPomodoro',    50,  100),
('Asistente',          'Alcanza el nivel 5.',                     '/img/logros/nivel_5.png',         'NivelAlcanzado',      5,   150),
('Profesional',        'Alcanza el nivel 10.',                    '/img/logros/nivel_10.png',        'NivelAlcanzado',      10,  300),
('Gran Maestro',       'Alcanza el nivel 18.',                    '/img/logros/nivel_18.png',        'NivelAlcanzado',      18,  700),
('Leyenda Viviente',   'Alcanza el nivel máximo: nivel 20.',      '/img/logros/leyenda.png',         'NivelAlcanzado',      20,  1000);
```

---

**Tabla: `LogrosUsuario`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `LogroId` | INT | FK → Logros.Id | |
| `FechaObtenido` | DATETIME | DEFAULT NOW() | |

---

#### HABITOS

---

**Tabla: `Categorias`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Nombre` | VARCHAR(100) | NOT NULL | |
| `Icono` | VARCHAR(100) | NOT NULL | Nombre del ícono Bootstrap Icons |
| `Tipo` | ENUM('Habito','Mision','Ambos') | NOT NULL | |
| `EstaActiva` | TINYINT(1) | DEFAULT 1 | |

**Seed inicial:**
```sql
INSERT INTO Categorias (Nombre, Icono, Tipo) VALUES
('Salud y Bienestar',  'bi-heart-pulse',       'Ambos'),
('Estudio',            'bi-book',              'Ambos'),
('Ejercicio',          'bi-activity',          'Habito'),
('Sueño',              'bi-moon-stars',        'Habito'),
('Hidratación',        'bi-droplet',           'Habito'),
('Nutrición',          'bi-egg-fried',         'Habito'),
('Meditación',         'bi-peace',             'Habito'),
('Tarea Académica',    'bi-file-earmark-text', 'Mision'),
('Proyecto',           'bi-kanban',            'Mision'),
('Lectura',            'bi-book-half',         'Ambos'),
('Hábito Personal',    'bi-star',              'Habito');
```

---

**Tabla: `Habitos`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `CategoriaId` | INT | FK → Categorias.Id | |
| `Nombre` | VARCHAR(200) | NOT NULL | |
| `Descripcion` | VARCHAR(500) | NULL | |
| `Frecuencia` | ENUM('Diaria','Semanal','Personalizada') | NOT NULL | |
| `DiasSemana` | VARCHAR(20) | NULL | JSON: `"[1,3,5]"` (Lun=1 … Dom=7) |
| `ConPomodoro` | TINYINT(1) | DEFAULT 0 | |
| `RecordatorioHora` | TIME | NULL | |
| `RachaActual` | INT | DEFAULT 0 | Racha individual del hábito |
| `RachaMaxima` | INT | DEFAULT 0 | |
| `EstaActivo` | TINYINT(1) | DEFAULT 1 | |
| `FechaCreacion` | DATETIME | DEFAULT NOW() | |

---

**Tabla: `RegistrosHabito`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `HabitoId` | INT | FK → Habitos.Id | |
| `Fecha` | DATE | NOT NULL | Fecha del registro |
| `Estado` | ENUM('Completado','Fallido','Pendiente') | DEFAULT 'Pendiente' | |
| `XpOtorgado` | INT | DEFAULT 0 | XP ganado al completar |
| `FechaRegistro` | DATETIME | DEFAULT NOW() | |

---

#### POMODORO

---

**Tabla: `ConfiguracionesPomodoro`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id, UNIQUE | Una configuración por usuario |
| `TiempoEstudioMin` | INT | DEFAULT 25 | |
| `TiempoDescansoMin` | INT | DEFAULT 5 | |
| `TiempoDescansoLargoMin` | INT | DEFAULT 15 | |
| `CiclosAntesDescansoLargo` | INT | DEFAULT 4 | |
| `SonidoActivo` | TINYINT(1) | DEFAULT 1 | |
| `FechaActualizacion` | DATETIME | DEFAULT NOW() | |

---

**Tabla: `SesionesPomodoro`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `HabitoId` | INT | FK → Habitos.Id, NULL | Opcional |
| `MisionId` | INT | FK → Misiones.Id, NULL | Opcional |
| `FechaInicio` | DATETIME | NOT NULL | |
| `FechaFin` | DATETIME | NULL | NULL si no terminó |
| `CiclosCompletados` | INT | DEFAULT 0 | |
| `XpOtorgado` | INT | DEFAULT 0 | |
| `FueCompletada` | TINYINT(1) | DEFAULT 0 | |

---

#### MISIONES

---

**Tabla: `Misiones`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `CategoriaId` | INT | FK → Categorias.Id | |
| `Nombre` | VARCHAR(200) | NOT NULL | |
| `Descripcion` | VARCHAR(500) | NULL | |
| `NombreCurso` | VARCHAR(150) | NULL | Nombre del curso universitario |
| `FechaLimite` | DATE | NOT NULL | |
| `Prioridad` | ENUM('Baja','Media','Alta') | DEFAULT 'Media' | |
| `Estado` | ENUM('Pendiente','EnProgreso','Completado','Fallido') | DEFAULT 'Pendiente' | |
| `ConPomodoro` | TINYINT(1) | DEFAULT 0 | |
| `XpOtorgado` | INT | DEFAULT 0 | Se calcula al completar según prioridad |
| `FechaCreacion` | DATETIME | DEFAULT NOW() | |
| `FechaCompletado` | DATETIME | NULL | |

---

#### BIENESTAR

---

**Tabla: `EstadosAnimo`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `Fecha` | DATE | NOT NULL | |
| `Estado` | ENUM('Feliz','Bien','Neutral','Cansado','Estresado') | NOT NULL | |
| `Nota` | VARCHAR(300) | NULL | Nota opcional |
| `FechaRegistro` | DATETIME | DEFAULT NOW() | |

---

#### CONTENIDO DEL SISTEMA

---

**Tabla: `FrasesMotivacionales`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Frase` | VARCHAR(500) | NOT NULL | |
| `Autor` | VARCHAR(150) | DEFAULT 'Anónimo' | |
| `EstaActiva` | TINYINT(1) | DEFAULT 1 | Gestionable desde admin |

**Seed inicial (10 de 100 — el resto se agrega desde el panel admin):**
```sql
INSERT INTO FrasesMotivacionales (Frase, Autor) VALUES
('El secreto para avanzar es comenzar.', 'Mark Twain'),
('No cuentes los días, haz que los días cuenten.', 'Muhammad Ali'),
('Sé el cambio que quieres ver en el mundo.', 'Mahatma Gandhi'),
('El éxito es la suma de pequeños esfuerzos repetidos día tras día.', 'Robert Collier'),
('No importa lo lento que vayas, siempre y cuando no te detengas.', 'Confucio'),
('La disciplina es el puente entre metas y logros.', 'Jim Rohn'),
('Cree que puedes y ya estarás a mitad de camino.', 'Theodore Roosevelt'),
('Cada día es una nueva oportunidad para mejorar.', 'Anónimo'),
('La constancia es la madre del éxito.', 'Anónimo'),
('Haz de cada día tu obra maestra.', 'John Wooden');
```

---

**Tabla: `TipsPomodoro`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Tip` | VARCHAR(500) | NOT NULL | |
| `EstaActivo` | TINYINT(1) | DEFAULT 1 | |

**Seed completo (5 tips):**
```sql
INSERT INTO TipsPomodoro (Tip) VALUES
('Pon tu teléfono en modo avión durante el tiempo de estudio. Cada notificación interrumpida son 23 minutos de concentración perdida.'),
('Prepara todo antes de empezar: agua, apuntes y la tarea específica. No busques materiales mientras el temporizador corre.'),
('En el descanso, aléjate de la pantalla. Estira las manos, camina un poco o mira por la ventana. Tu cerebro lo necesita.'),
('Si una idea te distrae, anótala rápido en un papel y regresa al foco. La anotarás después, no la perderás.'),
('Después de 4 ciclos completos, tómate 20-30 minutos de descanso largo. Comer algo ligero y caminar ayuda a resetear tu energía.');
```

---

#### TEMAS VISUALES

---

**Tabla: `Temas`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `Nombre` | VARCHAR(100) | NOT NULL | |
| `Descripcion` | VARCHAR(300) | NULL | |
| `Modo` | ENUM('Oscuro','Claro') | NOT NULL | |
| `ArchivoCss` | VARCHAR(100) | NOT NULL | Nombre del archivo en `/wwwroot/css/temas/` |
| `ImagenPreviewUrl` | VARCHAR(500) | NULL | Preview en la pantalla de ajustes |
| `EsPremium` | TINYINT(1) | DEFAULT 0 | 0 = gratis |
| `Precio` | DECIMAL(6,2) | DEFAULT 0.00 | En soles |
| `EstaActivo` | TINYINT(1) | DEFAULT 1 | |

**Seed inicial:**
```sql
INSERT INTO Temas (Nombre, Descripcion, Modo, ArchivoCss, EsPremium, Precio) VALUES
('Noche Épica', 'Modo oscuro con azules eléctricos y detalles en cyan. Para quienes estudian de noche con intensidad.', 'Oscuro', 'tema-noche-epica.css', 0, 0.00),
('Sakura',      'Modo claro en tonos pastel: lavanda, rosa y menta. Suave, ordenado y motivador.', 'Claro', 'tema-sakura.css', 0, 0.00);
```

---

**Tabla: `TemasUsuario`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `TemaId` | INT | FK → Temas.Id | |
| `FechaObtenido` | DATETIME | DEFAULT NOW() | |

---

#### SUSCRIPCIONES Y ADMIN

---

**Tabla: `Suscripciones`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id | |
| `Plan` | VARCHAR(50) | NOT NULL | Ej: `"Premium Mensual"` |
| `PrecioSoles` | DECIMAL(6,2) | NOT NULL | |
| `FechaInicio` | DATE | NOT NULL | |
| `FechaFin` | DATE | NOT NULL | |
| `EstaActiva` | TINYINT(1) | DEFAULT 0 | Lo activa manualmente el admin |
| `ActivadaPorAdminId` | INT | FK → Usuarios.Id, NULL | |
| `FechaActivacion` | DATETIME | NULL | |

---

**Tabla: `Logs`**
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `Id` | INT | PK, AUTO_INCREMENT | |
| `UsuarioId` | INT | FK → Usuarios.Id, NULL | NULL si es acción del sistema |
| `Accion` | VARCHAR(100) | NOT NULL | Ej: `"Login"`, `"CompletarHabito"` |
| `Detalle` | VARCHAR(500) | NULL | Información adicional |
| `DireccionIp` | VARCHAR(45) | NULL | |
| `FechaRegistro` | DATETIME | DEFAULT NOW() | |

---

### 1.2 Sistema de XP (Reglas de Negocio de Gamificación)

```
┌─────────────────────────────────────────────────────────────┐
│                    TABLA DE XP POR ACCIÓN                   │
├─────────────────────────────────────────┬───────────────────┤
│ Acción                                  │ XP Ganado         │
├─────────────────────────────────────────┼───────────────────┤
│ Login diario                            │ +10 XP            │
│ Completar un hábito                     │ +20 XP            │
│ Completar hábito (bonus racha x7)       │ +10 XP extra      │
│ Completar misión — Prioridad Baja       │ +30 XP            │
│ Completar misión — Prioridad Media      │ +50 XP            │
│ Completar misión — Prioridad Alta       │ +80 XP            │
│ Completar 1 ciclo Pomodoro              │ +15 XP            │
│ Mantener racha de 7 días               │ +50 XP bonus      │
│ Mantener racha de 30 días              │ +200 XP bonus     │
│ Desbloquear un logro                    │ XP del logro      │
└─────────────────────────────────────────┴───────────────────┘
```

> REGLA CLAVE: El XP nunca se descuenta. Solo la racha se pierde por inactividad.

---

### 1.3 Sistema de Rachas (Reglas de Negocio)

```
RACHA ACTIVA:
  - El usuario completa al menos 1 hábito en el día → RachaActual++
  - FechaUltimaActividad = HOY

DÍA DE GRACIA:
  - Si hoy == FechaUltimaActividad + 2 días Y DiaDeGraciaUsado == false
  → La racha no se pierde, DiaDeGraciaUsado = true
  → Al día siguiente DEBE completar al menos 1 hábito

PÉRDIDA DE RACHA:
  - Si hoy > FechaUltimaActividad + 2 días (o ya usó el día de gracia)
  → RachaActual = 0, DiaDeGraciaUsado = false
  → No se pierden XP acumulados (nunca)

EVALUACIÓN: Al hacer login y mediante un job programado a medianoche.
```

---

### 1.4 Fórmula de Productividad Diaria

```
ProductividadDiaria = (HábitosCompletadosHoy / HábitosProgramadosHoy) x 100

Si no hay hábitos programados para hoy: mostrar "Sin hábitos programados hoy"
```

---

## 2. Arquitectura del Sistema

### 2.1 Stack Tecnológico

| Componente | Tecnología |
|---|---|
| Framework | ASP.NET 10 Core MVC |
| ORM | Entity Framework Core |
| Base de datos | MariaDB (vía XAMPP) |
| Autenticación | JWT + Refresh Tokens + Google OAuth |
| Frontend | Razor Views + Bootstrap 5.3 |
| Iconos | Bootstrap Icons 1.11 |
| Gráficos | Chart.js 4 (CDN) |
| Hash contraseñas | BCrypt.Net-Next |
| Correos | MailKit |
| Mapeo | AutoMapper |
| IA (futuro) | Google Gemini API |

---

### 2.2 Paquetes NuGet

> Los paquetes de Entity Framework Core y el conector Pomelo para MariaDB se instalan manualmente desde el Administrador de paquetes NuGet de Visual Studio.
>
> Los siguientes paquetes adicionales se instalan via consola o NuGet Manager:

```
Microsoft.AspNetCore.Authentication.JwtBearer
Microsoft.AspNetCore.Authentication.Google
BCrypt.Net-Next
AutoMapper.Extensions.Microsoft.DependencyInjection
MailKit
```

---

### 2.3 Estructura de Carpetas del Proyecto

```
EpycusWeb/
│
├── Controllers/
│   ├── AutenticacionController.cs
│   ├── DashboardController.cs
│   ├── HabitosController.cs
│   ├── PomodoroController.cs
│   ├── MisionesController.cs
│   ├── ProgresoController.cs
│   ├── PerfilController.cs
│   ├── AjustesController.cs
│   └── Admin/
│       └── AdminController.cs
│
├── Views/
│   ├── Autenticacion/
│   │   ├── Login.cshtml
│   │   ├── Registro.cshtml
│   │   ├── CompletarPerfil.cshtml
│   │   ├── VerificarCorreo.cshtml
│   │   └── RecuperarContrasena.cshtml
│   ├── Dashboard/
│   │   └── Index.cshtml
│   ├── Habitos/
│   │   ├── Index.cshtml
│   │   ├── Crear.cshtml
│   │   └── Editar.cshtml
│   ├── Pomodoro/
│   │   └── Index.cshtml
│   ├── Misiones/
│   │   ├── Index.cshtml
│   │   ├── Crear.cshtml
│   │   └── Editar.cshtml
│   ├── Progreso/
│   │   └── Index.cshtml
│   ├── Perfil/
│   │   └── Index.cshtml
│   ├── Ajustes/
│   │   └── Index.cshtml
│   ├── Admin/
│   │   ├── Index.cshtml
│   │   └── Usuarios.cshtml
│   └── Shared/
│       ├── _Layout.cshtml
│       ├── _LayoutAdmin.cshtml
│       ├── _LayoutAuth.cshtml
│       └── _Parciales/
│           ├── _NavbarLateral.cshtml
│           ├── _BarraXP.cshtml
│           └── _Notificaciones.cshtml
│
├── ViewModels/
│   ├── Autenticacion/
│   │   ├── LoginViewModel.cs
│   │   ├── RegistroViewModel.cs
│   │   └── CompletarPerfilViewModel.cs
│   ├── Dashboard/
│   │   └── DashboardViewModel.cs
│   ├── Habitos/
│   │   ├── HabitoViewModel.cs
│   │   └── CrearHabitoViewModel.cs
│   ├── Misiones/
│   │   ├── MisionViewModel.cs
│   │   └── CrearMisionViewModel.cs
│   ├── Progreso/
│   │   └── ProgresoViewModel.cs
│   ├── Perfil/
│   │   └── PerfilViewModel.cs
│   └── Admin/
│       └── AdminUsuariosViewModel.cs
│
├── Modelos/
│   ├── Entidades/
│   │   ├── Usuario.cs
│   │   ├── Rol.cs
│   │   ├── Carrera.cs
│   │   ├── ProgresoUsuario.cs
│   │   ├── Nivel.cs
│   │   ├── Personaje.cs
│   │   ├── ImagenNivelPersonaje.cs
│   │   ├── PersonajeUsuario.cs
│   │   ├── Logro.cs
│   │   ├── LogroUsuario.cs
│   │   ├── Habito.cs
│   │   ├── RegistroHabito.cs
│   │   ├── ConfiguracionPomodoro.cs
│   │   ├── SesionPomodoro.cs
│   │   ├── Mision.cs
│   │   ├── Categoria.cs
│   │   ├── EstadoAnimo.cs
│   │   ├── FraseMotivacional.cs
│   │   ├── TipPomodoro.cs
│   │   ├── Tema.cs
│   │   ├── TemaUsuario.cs
│   │   ├── Suscripcion.cs
│   │   ├── TokenRefresh.cs
│   │   ├── VerificacionCorreo.cs
│   │   ├── RecuperacionContrasena.cs
│   │   └── Log.cs
│   └── Enums/
│       ├── EstadoMision.cs
│       ├── PrioridadMision.cs
│       ├── FrecuenciaHabito.cs
│       ├── EstadoAnimoEnum.cs
│       └── CondicionLogro.cs
│
├── Servicios/
│   ├── Interfaces/
│   │   ├── IServicioAutenticacion.cs
│   │   ├── IServicioGamificacion.cs
│   │   ├── IServicioHabitos.cs
│   │   ├── IServicioPomodoro.cs
│   │   ├── IServicioMisiones.cs
│   │   ├── IServicioProgreso.cs
│   │   ├── IServicioPerfil.cs
│   │   ├── IServicioCorreo.cs
│   │   └── IServicioAdmin.cs
│   └── Implementaciones/
│       ├── ServicioAutenticacion.cs
│       ├── ServicioGamificacion.cs
│       ├── ServicioHabitos.cs
│       ├── ServicioPomodoro.cs
│       ├── ServicioMisiones.cs
│       ├── ServicioProgreso.cs
│       ├── ServicioPerfil.cs
│       ├── ServicioCorreo.cs
│       └── ServicioAdmin.cs
│
├── Datos/
│   ├── ContextoAplicacion.cs
│   └── Semilla/
│       └── DatosSemilla.cs
│
├── DTOs/
│   ├── UsuarioDto.cs
│   ├── HabitoDto.cs
│   ├── MisionDto.cs
│   └── ProgresoDto.cs
│
├── Ayudantes/
│   ├── GeneradorCodigo.cs
│   ├── CalculadorXP.cs
│   └── ExtensionesString.cs
│
├── Middleware/
│   └── ManejadorExcepciones.cs
│
├── wwwroot/
│   ├── css/
│   │   ├── site.css
│   │   └── temas/
│   │       ├── tema-noche-epica.css
│   │       └── tema-sakura.css
│   ├── js/
│   │   ├── site.js
│   │   ├── pomodoro.js
│   │   └── graficos.js
│   └── img/
│       ├── personajes/
│       │   ├── ing-sistemas/
│       │   │   ├── masculino/
│       │   │   │   └── nivel_0.png
│       │   │   └── femenino/
│       │   │       └── nivel_0.png
│       │   └── generico/
│       │       ├── masculino/
│       │       │   └── placeholder.png
│       │       └── femenino/
│       │           └── placeholder.png
│       └── logros/
│           └── placeholder_logro.png
│
├── appsettings.json
├── appsettings.Development.json
└── Program.cs
```

---

### 2.4 Capas del Sistema

```
Usuario (Navegador)
       │
       ▼
┌──────────────────────────────────────────┐
│          CAPA DE PRESENTACIÓN            │
│  Controller recibe la petición HTTP      │
│  Valida el ViewModel (DataAnnotations)   │
│  Llama al Servicio correspondiente       │
└─────────────────┬────────────────────────┘
                  │ llama a
                  ▼
┌──────────────────────────────────────────┐
│          CAPA DE APLICACIÓN              │
│  Servicio contiene la lógica de negocio  │
│  Usa DTOs para transferir datos          │
│  Llama al ContextoAplicacion (EF Core)   │
└─────────────────┬────────────────────────┘
                  │ consulta
                  ▼
┌──────────────────────────────────────────┐
│          CAPA DE INFRAESTRUCTURA         │
│  ContextoAplicacion.cs (DbContext)       │
│  Entity Framework Core → MariaDB         │
└──────────────────────────────────────────┘
                  │
                  ▼
           Base de Datos
            epycus_db
```

---

### 2.5 Configuración de `appsettings.json`

```json
{
  "ConnectionStrings": {
    "ConexionPrincipal": "Server=localhost;Port=3306;Database=epycus_db;User=root;Password=;"
  },
  "Jwt": {
    "Clave": "TU_CLAVE_SECRETA_MUY_LARGA_AQUI_MINIMO_32_CARACTERES",
    "Emisor": "EpycusWeb",
    "Audiencia": "EpycusUsuarios",
    "ExpiracionMinutos": 60,
    "ExpiracionRefreshDias": 7
  },
  "Google": {
    "ClientId": "TU_CLIENT_ID_GOOGLE",
    "ClientSecret": "TU_CLIENT_SECRET_GOOGLE"
  },
  "Correo": {
    "Servidor": "smtp.gmail.com",
    "Puerto": 587,
    "Usuario": "epycusapp@gmail.com",
    "Contrasena": "TU_APP_PASSWORD_GMAIL",
    "NombreRemitente": "Epycus App"
  },
  "App": {
    "UrlBase": "https://localhost:7000",
    "Version": "1.0.0"
  }
}
```

---

## 3. Convenciones de Código

> REGLA DE ORO: Si todos siguen estas convenciones, Copilot generará código compatible entre módulos sin necesidad de reescribir nada.

---

### 3.1 Nombres de Archivos y Clases

| Elemento | Convención | Ejemplo |
|---|---|---|
| Controladores | PascalCase + sufijo Controller | `HabitosController.cs` |
| Vistas | PascalCase, carpeta = controlador | `Views/Habitos/Crear.cshtml` |
| ViewModels | PascalCase + sufijo ViewModel | `CrearHabitoViewModel.cs` |
| Entidades | PascalCase singular | `Habito.cs`, `Usuario.cs` |
| Servicios (interfaz) | PascalCase con prefijo I | `IServicioHabitos.cs` |
| Servicios (clase) | PascalCase + prefijo Servicio | `ServicioHabitos.cs` |
| DTOs | PascalCase + sufijo Dto | `HabitoDto.cs` |
| Enums | PascalCase | `EstadoMision.cs` |
| Ayudantes | PascalCase descriptivo | `CalculadorXP.cs` |

---

### 3.2 Variables y Propiedades en C#

```csharp
// CORRECTO — Propiedades de clase: PascalCase
public string NombreCompleto { get; set; }
public int XpTotal { get; set; }
public DateTime FechaRegistro { get; set; }

// CORRECTO — Variables locales: camelCase
var nombreUsuario = "Carlos";
int xpGanado = 20;
bool estaActivo = true;

// CORRECTO — Parámetros de método: camelCase
public async Task<bool> CompletarHabito(int habitoId, int usuarioId)

// CORRECTO — Constantes: MAYUSCULAS_CON_GUION_BAJO
private const int XP_POR_HABITO = 20;
private const int DIAS_GRACIA = 1;

// INCORRECTO — no usar snake_case ni campos públicos sin propiedad
public string nombre_completo { get; set; }
public string NombreCompleto;
var HabitoId = 5;
```

---

### 3.3 Nombres en Base de Datos

```
Tablas:           PascalCase, plural        → Usuarios, Habitos, RegistrosHabito
Columnas:         PascalCase                → NombreCompleto, XpTotal, FechaRegistro
Claves foráneas:  NombreTablaOrigen + Id    → UsuarioId, HabitoId, CarreraId
```

---

### 3.4 Estructura de Métodos en Controladores

```csharp
public class HabitosController : Controller
{
    private readonly IServicioHabitos _servicioHabitos;

    public HabitosController(IServicioHabitos servicioHabitos)
    {
        _servicioHabitos = servicioHabitos;
    }

    // GET: /Habitos
    public async Task<IActionResult> Index()
    {
        var usuarioId = ObtenerUsuarioIdActual();
        var habitos = await _servicioHabitos.ObtenerHabitosDeUsuario(usuarioId);
        return View(habitos);
    }

    // GET: /Habitos/Crear
    public IActionResult Crear()
    {
        return View(new CrearHabitoViewModel());
    }

    // POST: /Habitos/Crear
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> Crear(CrearHabitoViewModel modelo)
    {
        if (!ModelState.IsValid)
            return View(modelo);

        await _servicioHabitos.CrearHabito(modelo, ObtenerUsuarioIdActual());
        TempData["Exito"] = "Hábito creado correctamente.";
        return RedirectToAction(nameof(Index));
    }

    private int ObtenerUsuarioIdActual()
    {
        return int.Parse(User.FindFirst(ClaimTypes.NameIdentifier)?.Value ?? "0");
    }
}
```

---

### 3.5 Comentarios

```csharp
// Comentario de una línea para aclarar lógica no obvia
// El día de gracia solo se puede usar una vez por racha activa

/// <summary>
/// Calcula el XP necesario para pasar al siguiente nivel.
/// Fórmula: 100 + (nivelActual x 50)
/// </summary>
public int CalcularXpParaSiguienteNivel(int nivelActual)
{
    return 100 + (nivelActual * 50);
}
```

---

### 3.6 Validaciones en ViewModels

```csharp
public class CrearHabitoViewModel
{
    [Required(ErrorMessage = "El nombre del hábito es obligatorio.")]
    [MaxLength(200, ErrorMessage = "El nombre no puede tener más de 200 caracteres.")]
    public string Nombre { get; set; }

    [Required(ErrorMessage = "Selecciona una categoría.")]
    public int CategoriaId { get; set; }

    [Required(ErrorMessage = "Selecciona la frecuencia del hábito.")]
    public string Frecuencia { get; set; }

    public bool ConPomodoro { get; set; } = false;
}
```

---

### 3.7 Nombrado de Rutas

```
/                          → Dashboard
/autenticacion/login       → Login
/autenticacion/registro    → Registro
/habitos                   → Lista de hábitos
/habitos/crear             → Crear hábito
/habitos/editar/{id}       → Editar hábito
/pomodoro                  → Módulo Pomodoro
/misiones                  → Lista de misiones
/misiones/crear            → Crear misión
/progreso                  → Estadísticas
/perfil                    → Perfil del usuario
/ajustes                   → Ajustes
/admin                     → Panel admin (requiere rol Administrador)
```

---

## 4. Colores, Estilo y Librerías CSS

### 4.1 Librería CSS: Bootstrap 5.3

**Por qué Bootstrap 5:**
- No requiere proceso de compilación. Funciona con CDN o archivos locales, listo para subir a Hostinger VPS.
- Copilot lo conoce muy bien y genera código correcto y consistente.
- Las variables CSS nativas hacen que cambiar de tema sea solo intercambiar un archivo.
- Bootstrap Icons incluye más de 2,000 íconos sin dependencias extra.
- Documentación extensa, ideal para principiantes.

**CDN para `_Layout.cshtml`:**
```html
<!-- Bootstrap 5.3 CSS -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<!-- Bootstrap Icons 1.11 -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Fuente Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet">

<!-- CSS base del proyecto (siempre primero) -->
<link rel="stylesheet" href="~/css/site.css" asp-append-version="true">

<!-- Tema activo del usuario (intercambiable) -->
<link rel="stylesheet" href="~/css/temas/tema-noche-epica.css"
      asp-append-version="true" id="hoja-tema">

<!-- Chart.js — solo en la vista de Progreso -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<!-- Bootstrap JS — al final del body -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

---

### 4.2 Paleta — Modo Oscuro "Noche Épica"

> Fondo negro azulado, azul eléctrico como color principal, cyan como acento brillante. Para sesiones nocturnas de estudio intenso.

```css
/* Archivo: /wwwroot/css/temas/tema-noche-epica.css */

:root {
  /* Fondos */
  --ep-fondo:               #070B14;
  --ep-superficie:          #0E1420;
  --ep-superficie-2:        #141B2D;
  --ep-borde:               #1E293B;

  /* Colores principales */
  --ep-primario:            #3B82F6;
  --ep-primario-hover:      #2563EB;
  --ep-acento:              #6366F1;
  --ep-neon:                #00D4FF;
  --ep-neon-suave:          rgba(0, 212, 255, 0.12);
  --ep-neon-borde:          rgba(0, 212, 255, 0.30);

  /* Textos */
  --ep-texto:               #F1F5F9;
  --ep-texto-secundario:    #94A3B8;
  --ep-texto-apagado:       #475569;

  /* Estados */
  --ep-exito:               #10B981;
  --ep-advertencia:         #F59E0B;
  --ep-peligro:             #EF4444;
  --ep-info:                #38BDF8;

  /* Gamificación */
  --ep-xp:                  #FACC15;
  --ep-xp-suave:            rgba(250, 204, 21, 0.15);
  --ep-racha:               #F97316;
  --ep-racha-suave:         rgba(249, 115, 22, 0.15);
  --ep-nivel:               #A78BFA;

  /* Barra XP */
  --ep-barra-xp-fondo:      #1E293B;
  --ep-barra-xp-relleno:    linear-gradient(90deg, #3B82F6, #00D4FF);

  /* Navegación */
  --ep-nav-fondo:           #0A0F1E;
  --ep-nav-item-hover:      rgba(59, 130, 246, 0.15);
  --ep-nav-item-activo:     rgba(59, 130, 246, 0.25);
  --ep-nav-texto:           #94A3B8;
  --ep-nav-texto-activo:    #00D4FF;

  /* Sombras */
  --ep-sombra:              0 4px 24px rgba(0, 0, 0, 0.60);
  --ep-sombra-neon:         0 0 20px rgba(0, 212, 255, 0.20);
  --ep-sombra-primario:     0 4px 15px rgba(59, 130, 246, 0.30);

  /* Radios */
  --ep-radio:               12px;
  --ep-radio-sm:            8px;
  --ep-radio-lg:            20px;
}
```

**Referencia rápida de colores:**
```
Fondos:    #070B14 · #0E1420 · #141B2D · #1E293B
Colores:   #3B82F6 (primario) · #6366F1 (acento) · #00D4FF (neon)
Textos:    #F1F5F9 · #94A3B8 · #475569
Extra:     #FACC15 (XP) · #F97316 (racha) · #10B981 (exito)
```

---

### 4.3 Paleta — Modo Claro "Sakura"

> Fondo lavanda suave, púrpura como color principal, rosa como acento. Ordenado, luminoso y motivador.

```css
/* Archivo: /wwwroot/css/temas/tema-sakura.css */

:root {
  /* Fondos */
  --ep-fondo:               #FDF4FF;
  --ep-superficie:          #FFFFFF;
  --ep-superficie-2:        #F5E6FF;
  --ep-borde:               #E9D5FF;

  /* Colores principales */
  --ep-primario:            #A855F7;
  --ep-primario-hover:      #9333EA;
  --ep-acento:              #EC4899;
  --ep-neon:                #C084FC;
  --ep-neon-suave:          rgba(192, 132, 252, 0.12);
  --ep-neon-borde:          rgba(192, 132, 252, 0.40);

  /* Textos */
  --ep-texto:               #1E1B4B;
  --ep-texto-secundario:    #6B7280;
  --ep-texto-apagado:       #9CA3AF;

  /* Estados */
  --ep-exito:               #059669;
  --ep-advertencia:         #D97706;
  --ep-peligro:             #DC2626;
  --ep-info:                #7C3AED;

  /* Gamificación */
  --ep-xp:                  #F59E0B;
  --ep-xp-suave:            rgba(245, 158, 11, 0.12);
  --ep-racha:               #F97316;
  --ep-racha-suave:         rgba(249, 115, 22, 0.10);
  --ep-nivel:               #A855F7;

  /* Barra XP */
  --ep-barra-xp-fondo:      #E9D5FF;
  --ep-barra-xp-relleno:    linear-gradient(90deg, #A855F7, #EC4899);

  /* Navegación */
  --ep-nav-fondo:           #FFFFFF;
  --ep-nav-item-hover:      rgba(168, 85, 247, 0.08);
  --ep-nav-item-activo:     rgba(168, 85, 247, 0.15);
  --ep-nav-texto:           #6B7280;
  --ep-nav-texto-activo:    #A855F7;

  /* Sombras */
  --ep-sombra:              0 4px 24px rgba(168, 85, 247, 0.10);
  --ep-sombra-neon:         0 0 20px rgba(192, 132, 252, 0.15);
  --ep-sombra-primario:     0 4px 15px rgba(168, 85, 247, 0.20);

  /* Radios */
  --ep-radio:               12px;
  --ep-radio-sm:            8px;
  --ep-radio-lg:            20px;
}
```

**Referencia rápida de colores:**
```
Fondos:    #FDF4FF · #FFFFFF · #F5E6FF · #E9D5FF
Colores:   #A855F7 (primario) · #EC4899 (acento) · #C084FC (neon)
Textos:    #1E1B4B · #6B7280 · #9CA3AF
Extra:     #F59E0B (XP) · #F97316 (racha) · #059669 (exito)
```

---

### 4.4 Archivo `site.css` — Estilos Globales

```css
/* ================================================
   EPYCUS WEB — Estilos Globales
   Se carga siempre, independiente del tema activo
   ================================================ */

body {
  font-family: 'Inter', sans-serif;
  background-color: var(--ep-fondo);
  color: var(--ep-texto);
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* Cards */
.ep-card {
  background-color: var(--ep-superficie);
  border: 1px solid var(--ep-borde);
  border-radius: var(--ep-radio);
  box-shadow: var(--ep-sombra);
  padding: 1.5rem;
  transition: box-shadow 0.2s ease;
}
.ep-card:hover { box-shadow: var(--ep-sombra-neon); }
.ep-card-neon  { border-color: var(--ep-neon-borde); box-shadow: var(--ep-sombra-neon); }

/* Barra de XP */
.ep-barra-xp {
  height: 10px;
  border-radius: 99px;
  background-color: var(--ep-barra-xp-fondo);
  overflow: hidden;
}
.ep-barra-xp-relleno {
  height: 100%;
  background: var(--ep-barra-xp-relleno);
  border-radius: 99px;
  transition: width 0.6s ease;
}

/* Badges */
.ep-badge-nivel {
  background: var(--ep-neon-suave);
  border: 1px solid var(--ep-neon-borde);
  color: var(--ep-neon);
  border-radius: 99px;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
}
.ep-badge-xp {
  background: var(--ep-xp-suave);
  border: 1px solid var(--ep-xp);
  color: var(--ep-xp);
  border-radius: 99px;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
}
.ep-badge-racha {
  background: var(--ep-racha-suave);
  border: 1px solid var(--ep-racha);
  color: var(--ep-racha);
  border-radius: 99px;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
}

/* Botones */
.btn-ep {
  background-color: var(--ep-primario);
  color: #fff;
  border: none;
  border-radius: var(--ep-radio-sm);
  padding: 0.6rem 1.5rem;
  font-weight: 600;
  transition: background-color 0.2s, box-shadow 0.2s;
}
.btn-ep:hover {
  background-color: var(--ep-primario-hover);
  box-shadow: var(--ep-sombra-primario);
  color: #fff;
}
.btn-ep-outline {
  background-color: transparent;
  color: var(--ep-primario);
  border: 1.5px solid var(--ep-primario);
  border-radius: var(--ep-radio-sm);
  padding: 0.6rem 1.5rem;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-ep-outline:hover { background-color: var(--ep-primario); color: #fff; }

/* Inputs */
.ep-input {
  background-color: var(--ep-superficie-2);
  border: 1px solid var(--ep-borde);
  border-radius: var(--ep-radio-sm);
  color: var(--ep-texto);
  padding: 0.6rem 1rem;
  width: 100%;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.ep-input:focus {
  border-color: var(--ep-primario);
  box-shadow: 0 0 0 3px var(--ep-neon-suave);
  outline: none;
  background-color: var(--ep-superficie-2);
  color: var(--ep-texto);
}

/* Sidebar */
.ep-sidebar {
  background-color: var(--ep-nav-fondo);
  border-right: 1px solid var(--ep-borde);
  width: 260px;
  min-height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
}
.ep-nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: var(--ep-nav-texto);
  text-decoration: none;
  border-radius: var(--ep-radio-sm);
  margin: 0.1rem 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
}
.ep-nav-item:hover         { background-color: var(--ep-nav-item-hover);  color: var(--ep-nav-texto-activo); }
.ep-nav-item.activo        { background-color: var(--ep-nav-item-activo); color: var(--ep-nav-texto-activo); }

/* Contenido principal */
.ep-contenido {
  margin-left: 260px;
  padding: 2rem;
  min-height: 100vh;
}

/* Estados */
.ep-estado-completado { color: var(--ep-exito); }
.ep-estado-pendiente  { color: var(--ep-advertencia); }
.ep-estado-fallido    { color: var(--ep-peligro); }

/* Título con gradiente */
.ep-texto-gradiente {
  background: linear-gradient(135deg, var(--ep-primario), var(--ep-neon));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Separador */
.ep-separador {
  border: none;
  border-top: 1px solid var(--ep-borde);
  margin: 1.5rem 0;
}

/* Responsive */
@media (max-width: 768px) {
  .ep-sidebar   { display: none; }
  .ep-contenido { margin-left: 0; padding: 1rem; }
}
```

---

### 4.5 Tipografía

| Uso | Tamaño | Peso |
|---|---|---|
| Título de página | `1.75rem` | 700 |
| Subtítulo / Card | `1.1rem` | 600 |
| Texto normal | `1rem` | 400 |
| Texto secundario / labels | `0.875rem` | 400 |
| Badges / Tags | `0.75rem` | 600 |
| Número XP grande | `2rem` | 700 |

**Fuente:** `Inter` (Google Fonts) — moderna, legible, gratuita.

---

### 4.6 Sistema de Cambio de Tema

```javascript
/* /wwwroot/js/site.js */

function cambiarTema(nombreArchivoCss) {
    const hojaTema = document.getElementById('hoja-tema');
    hojaTema.href = '/css/temas/' + nombreArchivoCss;
    localStorage.setItem('epycus_tema', nombreArchivoCss);
}

document.addEventListener('DOMContentLoaded', function () {
    const temaGuardado = localStorage.getItem('epycus_tema');
    if (temaGuardado) {
        const hojaTema = document.getElementById('hoja-tema');
        if (hojaTema) hojaTema.href = '/css/temas/' + temaGuardado;
    }
});
```

> Para agregar un tema futuro: crear el archivo `.css` con las mismas variables `--ep-*`, guardarlo en `/css/temas/`, y registrarlo en la tabla `Temas`. No hay que tocar ningún otro archivo.

---

## 5. División de Módulos por Integrante

### 5.1 Asignación de Módulos

| Integrante | Módulos | Complejidad |
|---|---|---|
| Tú (Líder) | Auth + Gamificación + Dashboard | Alta |
| Integrante 2 | Hábitos + Pomodoro | Media |
| Integrante 3 | Misiones + Progreso | Media |
| Integrante 4 | Perfil + Ajustes + Admin | Media-Baja |

---

### 5.2 Detalle de Tareas por Módulo

---

#### TU (LIDER) — Auth · Gamificación · Dashboard

**Semana 1 (antes del jueves 7):**
- [ ] Crear proyecto ASP.NET 10 Core MVC en Visual Studio 2026
- [ ] Configurar conexión a MariaDB con EF Core
- [ ] Crear todas las entidades en `Modelos/Entidades/`
- [ ] Crear `ContextoAplicacion.cs` con todos los DbSets
- [ ] Ejecutar migraciones y verificar tablas en XAMPP
- [ ] Ejecutar seed de datos iniciales (Niveles, Logros, Frases, Tips, Temas, Carreras)
- [ ] Implementar registro manual (correo + contraseña con BCrypt)
- [ ] Implementar login con Google OAuth
- [ ] Pantalla "Completar Perfil" después de Google
- [ ] Verificación de correo electrónico
- [ ] Recuperación de contraseña
- [ ] Generación de código único `EPY-XXXXXXXX`
- [ ] Middleware de autenticación y autorización por roles
- [ ] Subir proyecto base a carpeta `01_Principal` en Drive

**Semana 2 (antes del jueves 14):**
- [ ] `ServicioGamificacion`: sumar XP, verificar subida de nivel, actualizar racha con día de gracia
- [ ] `CalculadorXP.cs`: fórmulas de XP y niveles
- [ ] Verificación automática de logros después de cada acción que da XP
- [ ] Selección automática de personaje al registrarse (según carrera y género)
- [ ] Lógica de imagen del personaje según nivel actual
- [ ] `DashboardController` + vista completa con: personaje, barra XP, nivel, título, racha, frase del día, hábitos pendientes, estado de ánimo

---

#### INTEGRANTE 2 — Hábitos + Pomodoro

> Siempre descargar desde `01_Principal` en Drive. No modificar archivos fuera de tu módulo.

**Semana 1 (antes del jueves 7):**
- [ ] Leer este documento completo
- [ ] Explorar entidades: `Habito`, `RegistroHabito`, `Categoria`, `ConfiguracionPomodoro`, `SesionPomodoro`

**Semana 2 (antes del jueves 14):**
- [ ] `HabitosController`: Index, Crear, Editar, Eliminar, Completar (acción POST)
- [ ] `Views/Habitos/Index.cshtml`: lista con pestañas Pendientes / Completados / Fallidos
- [ ] `Views/Habitos/Crear.cshtml`: formulario con categoría, frecuencia, recordatorio, opción Pomodoro
- [ ] Seguimiento semanal visual Lunes a Domingo con íconos
- [ ] `ServicioHabitos`: CRUD + marcar completado + racha del hábito
- [ ] Al completar hábito: llamar `_servicioGamificacion.SumarXP(usuarioId, 20)`

**Semana 3 (antes del jueves 21):**
- [ ] `PomodoroController` + `Views/Pomodoro/Index.cshtml`
- [ ] Temporizador en `pomodoro.js`: cuenta regresiva, ciclos, pantalla completa
- [ ] Guardar sesión al terminar en `SesionesPomodoro`
- [ ] Al completar ciclo: llamar `_servicioGamificacion.SumarXP(usuarioId, 15)`
- [ ] Mostrar tips del Pomodoro de forma aleatoria
- [ ] Configuración personalizable: tiempo de estudio, descanso, ciclos
- [ ] Sonido de notificación al terminar el ciclo

---

#### INTEGRANTE 3 — Misiones + Progreso

> Siempre descargar desde `01_Principal` en Drive. No modificar archivos fuera de tu módulo.

**Semana 1 (antes del jueves 7):**
- [ ] Leer este documento completo
- [ ] Explorar entidades: `Mision`, `Categoria`, `LogroUsuario`, `EstadoAnimo`, `ProgresoUsuario`

**Semana 2 (antes del jueves 14):**
- [ ] `MisionesController`: Index, Crear, Editar, Eliminar, CambiarEstado
- [ ] `Views/Misiones/Index.cshtml`: lista con filtros por estado y prioridad, colores por prioridad
- [ ] `Views/Misiones/Crear.cshtml`: formulario con categoría, nombre de curso, fecha límite, prioridad
- [ ] `ServicioMisiones`: CRUD + cambio de estado + XP al completar según prioridad
- [ ] Al completar: llamar `_servicioGamificacion.SumarXP(usuarioId, xp)` (30/50/80 según prioridad)

**Semana 3 (antes del jueves 21):**
- [ ] `ProgresoController` + `Views/Progreso/Index.cshtml`
- [ ] Gráfico de hábitos completados por semana (Chart.js — barras)
- [ ] Gráfico de XP acumulado por semana (Chart.js — líneas)
- [ ] Lista de logros: desbloqueados con fecha y bloqueados con barra de progreso
- [ ] Historial de estados de ánimo con ícono por tipo
- [ ] KPIs: hábitos completados, misiones totales, racha máxima, XP total, sesiones Pomodoro

---

#### INTEGRANTE 4 — Perfil + Ajustes + Admin

> Siempre descargar desde `01_Principal` en Drive. No modificar archivos fuera de tu módulo.

**Semana 1 (antes del jueves 7):**
- [ ] Leer este documento completo
- [ ] Explorar entidades: `Usuario`, `Personaje`, `ImagenNivelPersonaje`, `PersonajeUsuario`, `Tema`, `Suscripcion`

**Semana 2 (antes del jueves 14):**
- [ ] `PerfilController` + `Views/Perfil/Index.cshtml`
- [ ] Mostrar: nombre, código `EPY-XXXXXXXX`, carrera, nivel con título combinado, personaje con imagen del nivel actual
- [ ] Editar: nombre, fecha de nacimiento, género, carrera
- [ ] Galería de personajes con selector visual

**Semana 3 (antes del jueves 21):**
- [ ] `AjustesController` + `Views/Ajustes/Index.cshtml`
- [ ] Cambio de contraseña con validación BCrypt
- [ ] Selector de tema visual con preview (Noche Épica / Sakura)
- [ ] Vinculación y desvinculación de Google
- [ ] Eliminación de cuenta con pantalla de aviso y confirmación
- [ ] `AdminController` con atributo `[Authorize(Roles = "Administrador")]`:
  - Lista paginada de usuarios, buscable por nombre, correo o código único
  - Ver detalle del usuario por ID
  - Activar/desactivar suscripción premium manualmente
  - CRUD de frases motivacionales
  - Métricas: total usuarios, activos hoy, suscripciones activas

---

### 5.3 Cronograma Scrumban

```
SEMANA 1: Sprint Arranque — 4 al 7 de mayo
───────────────────────────────────────────────────────
Lun 04/05  REUNION DE INICIO (5:00 – 5:30 pm)
            - Presentar este documento al equipo
            - Mostrar la estructura del proyecto en VS
            - Cada uno descarga el proyecto base de 01_Principal
            - Asignar módulos formalmente
Mar 05/05  Clase Capstone (presencial)
            - Lectura del documento + explorar entidades asignadas
Mié 06/05  Trabajo individual con Copilot
Jue 07/05  REUNION DE REVISION
            - Cada uno muestra su avance y entiende su módulo
            - Líder integra en 01_Principal y todos descargan

SEMANA 2: Sprint Módulos Principales — 7 al 14 de mayo
───────────────────────────────────────────────────────
Vie 08/05  Trabajo individual
Lun 11/05  REUNION DE SEGUIMIENTO
            - Mostrar avances: Auth/Dashboard, Hábitos, Misiones, Perfil
            - Resolver dudas de integración
Mar 12/05  Clase Capstone (presencial)
Mié 13/05  Trabajo individual / correcciones
Jue 14/05  REUNION DE REVISION
            - Integración de módulos principales
            - Líder sube versión integrada a 01_Principal
            - Todos descargan y continúan

SEMANA 3: Sprint Módulos Secundarios — 14 al 21 de mayo
───────────────────────────────────────────────────────
Lun 18/05  REUNION DE SEGUIMIENTO
            - Mostrar: Pomodoro, Progreso, Ajustes, Admin
            - Verificar que el cambio de tema funciona
Mar 19/05  Clase Capstone (presencial)
Mié 20/05  Trabajo individual / ajustes y pruebas
Jue 21/05  REUNION DE REVISION
            - Integración final de todos los módulos
            - Pruebas en conjunto

SEMANA 4: Cierre — 21 al 25 de mayo
───────────────────────────────────────────────────────
Lun 25/05  ENTREGA FINAL
            - Sistema completo y funcionando
            - Líder sube el proyecto final a GitHub
            - Presentación para Capstone Project
```

---

### 5.4 Flujo de Trabajo con Google Drive

```
ESTRUCTURA DE CARPETAS EN DRIVE:
──────────────────────────────────────────────────────
📁 EPYCUS_WEB/
│
├── 📁 01_Principal/        ← Versión integrada y funcional
│   └── EpycusWeb_v1.0.zip  (actualizada cada jueves por el líder)
│
├── 📁 02_Lider/
│   └── EpycusWeb_lider.zip
│
├── 📁 03_Integrante2/
│   └── EpycusWeb_int2.zip
│
├── 📁 04_Integrante3/
│   └── EpycusWeb_int3.zip
│
└── 📁 05_Integrante4/
    └── EpycusWeb_int4.zip

FLUJO CADA JUEVES:
  1. Cada integrante comprime su carpeta del proyecto
  2. Sube el .zip a SU carpeta personal en Drive
  3. El líder descarga los 3 .zip
  4. Copia SOLO los archivos del módulo de cada uno
     al proyecto principal
  5. Prueba que todo funcione junto
  6. Sube el proyecto integrado a 01_Principal/
  7. Cada integrante descarga el nuevo 01_Principal para continuar

QUE EXCLUIR AL COMPRIMIR (para que el .zip no sea muy pesado):
  - Carpeta bin/
  - Carpeta obj/
  - Carpeta .vs/
  Solo comprimir código fuente y wwwroot.

REGLA FUNDAMENTAL:
  Cada integrante SOLO modifica los archivos de SU módulo.
  Si necesitas cambiar algo de otro módulo, consúltalo
  primero con el líder antes de tocarlo.
```

---

### 5.5 Instrucción para Copilot (Dar a cada integrante)

> Copia este texto y pégalo en Copilot al inicio de cada sesión de trabajo, antes de pedir cualquier código.

```
Estoy trabajando en el proyecto EPYCUS WEB. Reglas obligatorias del proyecto:

Framework: ASP.NET 10 Core MVC con Razor Views
Base de datos: MariaDB con Entity Framework Core (Pomelo.EntityFrameworkCore.MySql)
CSS: Bootstrap 5.3 + Bootstrap Icons 1.11
Gráficos: Chart.js 4 (solo en el módulo de Progreso)
Idioma del código: TODO en español (variables, métodos, clases, comentarios)

Convenciones:
- Clases y propiedades: PascalCase
- Variables locales y parámetros: camelCase
- Controladores: sufijo Controller (ej: HabitosController)
- Interfaces de servicios: prefijo I (ej: IServicioHabitos)
- Clases de servicios: prefijo Servicio (ej: ServicioHabitos)
- DbContext se llama: ContextoAplicacion
- Mensajes de validación: siempre en español
- Los controladores inyectan servicios por interfaz en el constructor con _nombreServicio
- Toda acción que da XP debe llamar a IServicioGamificacion
- Nunca usar inglés en nombres de variables, métodos ni comentarios
```

---

*Documento base EPYCUS WEB — Capstone Project 2026*  
*Versión 2.0 — Mayo 2026*
