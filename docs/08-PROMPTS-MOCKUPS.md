\
# 08 — Prompts para generar mockups

> Objetivo: obtener **un código completo por estilo** que cubra todos los módulos, en móvil y escritorio, para poder comparar de verdad y elegir uno. Tras elegir, este documento se archiva y `04-DISENO-VISUAL.md` se actualiza con la decisión.

---

## 1. Estrategia: 3 bloques por estilo, no una sola generación

Son 15 pantallas. Pedir las 15 en un solo prompt da resultados truncados o pobres: los generadores tienen límite de salida y con demasiadas instrucciones empiezan a ignorar detalles.

**Divide cada estilo en 3 generaciones encadenadas:**

| Bloque | Pantallas | Por qué juntas |
|---|---|---|
| **0 · Sistema** | Tokens, colores, componentes base | Se genera **primero**. Los demás bloques lo reutilizan |
| **A · Núcleo** | Login, Onboarding, Dashboard, Hábitos, Pomodoro | Lo que se ve a diario |
| **B · Contenido** | Misiones, Diario, Asistente IA, Avatar, Logros, Villano | Los módulos de progresión |
| **C · Social y config** | Grupos de estudio, Ranking, Ajustes, Panel admin | Lo secundario |

Al generar B y C, **pega el CSS de variables que produjo el bloque 0** para que todo sea coherente.

**Recomendación práctica:** genera un estilo completo primero (bloques 0+A+B+C), revísalo, y solo si el enfoque te convence replica con los demás. Si el primero sale mal, ajustas el prompt antes de gastar 5 generaciones más.

---

## 2. Contexto común — pégalo SIEMPRE al inicio

```
CONTEXTO DEL PRODUCTO

Aplicación web responsive llamada Epycus, para estudiantes universitarios peruanos
de 18 a 25 años. Convierte hábitos de estudio en un juego de rol: el usuario tiene
un avatar que evoluciona hacia la vestimenta profesional de su carrera conforme
cumple hábitos, sesiones Pomodoro y tareas académicas.

Se usará durante 66 días seguidos, todos los días. El diseño debe ser cómodo a
largo plazo, no llamativo de golpe.

EL AVATAR ES ESTILO FUNKO POP:
- Cabeza grande, aproximadamente 1/3 de la altura total del personaje
- Ojos negros redondos y grandes, SIN pupila ni brillo
- Nariz ausente o apenas insinuada, boca ausente
- Cuerpo pequeño y compacto, extremidades cortas
- Postura rígida y frontal, colores planos con sombreado suave
- Lo que distingue cada carrera es la VESTIMENTA, no la anatomía
  (bata y estetoscopio para Medicina, casco para Ingeniería, toga para Derecho)
En los mockups usa un marcador de posición con esas proporciones (cabeza grande,
cuerpo pequeño), no un avatar humano realista ni un círculo genérico.

DOS TAMAÑOS, AMBOS DE PRIMERA CATEGORÍA:
- MÓVIL (375px): navegación en barra inferior de 5 iconos, una columna
- ESCRITORIO (1440px): barra lateral fija de 260px con etiquetas, contenido
  centrado con max-width 1200px, dashboard en cuadrícula de 3 columnas
El escritorio NO es el móvil estirado. Necesita composición propia.
Genera AMBAS versiones de cada pantalla.

REQUISITOS INNEGOCIABLES:
- Texto de cuerpo mínimo 16px
- Contraste mínimo 4.5:1 en texto y 3:1 en elementos de interfaz (WCAG AA)
- Áreas táctiles mínimas de 44x44px
- Todo en español
- Modo claro y modo oscuro, ambos funcionales
- Nada de texto sobre imágenes con detalle ni degradados bajo texto

TONO: cercano y motivador, sin infantilizar. Son universitarios, no niños.

ENTREGA: HTML + TailwindCSS. TODOS los colores como variables CSS
(--color-bg, --color-surface, --color-primary, etc.), nunca valores literales
en las clases. El cambio de tema se hace con [data-theme="dark"] en <html>.
```

---

## 3. Bloque 0 — Sistema de diseño

Genera esto **primero** en cada estilo.

```
[PEGAR CONTEXTO COMÚN]

[PEGAR EL BLOQUE DE ESTILO DE LA SECCIÓN 5]

TAREA: Genera el sistema de diseño base, sin pantallas todavía.

1. Variables CSS completas para modo claro y oscuro:
   --color-bg, --color-surface, --color-surface-raised, --color-surface-sunken
   --color-content-primary, --color-content-secondary, --color-content-muted
   --color-primary, --color-primary-hover, --color-primary-soft
   --color-secondary, --color-accent
   --color-success, --color-warning, --color-danger, --color-info
   --color-border, --color-border-strong
   --shadow-sm, --shadow-md

2. Componentes base, cada uno en sus estados (normal, hover, activo, deshabilitado,
   con foco visible):
   - Botón: primario, secundario, fantasma, peligro
   - Tarjeta: plana y elevada
   - Campo de texto y lista desplegable
   - Casilla de verificación (para marcar hábitos)
   - Barra de progreso (XP, vida del villano, avance de misión)
   - Insignia / etiqueta
   - Modal
   - Avatar Funko en 3 tamaños (pequeño en cabecera, mediano en tarjeta, grande en perfil)

3. Escala tipográfica y de espaciado.

Muéstralo todo en una página de catálogo, con un interruptor de tema claro/oscuro
funcionando. Incluye junto a cada combinación de color su ratio de contraste.
```

---

## 4. Bloques A, B y C — las pantallas

Al generar estos, **añade esta línea al inicio**:

```
Usa exactamente estas variables CSS y estos componentes base, ya definidos:
[PEGAR EL CSS DEL BLOQUE 0]
```

### Bloque A — Núcleo

```
TAREA: Genera estas 5 pantallas, cada una en versión MÓVIL (375px) y
ESCRITORIO (1440px). Muéstralas una debajo de otra, etiquetadas.

--- 1. LOGIN ---
Formulario de correo y contraseña, botón "Entrar", enlace "Crear cuenta".
Logo de Epycus. Ilustración o avatar Funko decorativo.

--- 2. ONBOARDING (paso 2 de 3: elegir carrera) ---
Título "¿Qué estudias?", lista desplegable de carreras (Medicina, Enfermería,
Obstetricia, Administración, Contabilidad, Ing. Civil, Ing. Industrial,
Ing. de Minas, Arquitectura, Ing. de Sistemas, Derecho).
Debajo, vista previa del avatar Funko con la vestimenta de fase 1 de esa carrera.
Selector de género del avatar (masculino/femenino).
Indicador de progreso del onboarding. Botones "Atrás" y "Continuar".

--- 3. DASHBOARD ---
- Cabecera: "Hola, Marco", nivel 12, 340/505 XP, racha de 8 días
- Tarjeta destacada: avatar Funko con bata de laboratorio (fase 3),
  barra de progreso al nivel 13
- Tarjeta: villano de la semana "La Distracción", barra de vida al 60%
- Hábitos de hoy: 4 hábitos, 2 marcados, 2 sin marcar
- Botón grande "Iniciar Pomodoro"
- Tarjeta de ánimo: "¿Cómo te sientes hoy?" con 5 caritas
- Próximas misiones: 2 con fecha de vencimiento
EN ESCRITORIO: cuadrícula de 3 columnas, con el avatar en tarjeta grande a la
izquierda y una columna derecha con racha, misiones y últimos logros.

--- 4. HÁBITOS ---
Título "Mis hábitos", fecha de hoy, contador "3 de 5 completados".
Lista de 6 hábitos con icono, nombre, racha individual y casilla:
  Leer 20 páginas (racha 12) marcado
  Repasar apuntes (racha 8) marcado
  Dormir 7 horas (racha 3) marcado
  Ejercicio 30 min (racha 0) sin marcar
  Revisar agenda (racha 5) sin marcar
  Sin celular al estudiar (racha 1) sin marcar
Botón flotante para agregar. Diferencia visual clara entre marcado y sin marcar.
EN ESCRITORIO: 2 columnas de tarjetas.

--- 5. POMODORO ACTIVO ---
Temporizador circular grande: 18:42 restantes.
"Sesión 3 de 8 · Enfocado en: Ensayo de Metodología"
Botones "Pausar" y "Terminar" (este último discreto).
Indicador discreto de XP a ganar (+15).
Pantalla despejada, de concentración. Navegación atenuada o ausente.
EN ESCRITORIO: temporizador centrado, con panel lateral que muestra la misión
vinculada y las sesiones ya completadas hoy.
```

### Bloque B — Contenido y progresión

```
TAREA: Genera estas 6 pantallas, en MÓVIL y ESCRITORIO.

--- 6. MISIONES ---
Lista de misiones con título, dificultad (fácil/media/difícil), fecha de
vencimiento y progreso de subtareas (ej. "3 de 5").
  Ensayo de Metodología — difícil — vence en 2 días — 3/5 subtareas
  Resolver práctica de Cálculo — media — vence hoy — 0/3
  Leer capítulo 4 — fácil — vence en 5 días — completada
Una misión vencida en rojo. Botón para crear misión.

--- 7. DETALLE DE MISIÓN ---
Título, descripción, dificultad, fecha límite.
Lista de subtareas con casillas, algunas marcadas.
Botón "Iniciar Pomodoro en esta misión".
Botón "Completar misión".

--- 8. DIARIO DE BIENESTAR ---
"¿Cómo te sientes hoy?" con 5 caritas (escala 1 a 5).
Campo de texto opcional "¿Quieres contar algo? (opcional)".
Etiquetas seleccionables: estrés, motivado, cansado, tranquilo, agobiado.
Debajo: gráfico de línea con el ánimo de los últimos 14 días.
Nota discreta de privacidad: "Solo tú puedes leer esto".

--- 9. ASISTENTE DE IA ---
Interfaz de conversación. 3 mensajes de ejemplo:
  Usuario: "Tengo 3 exámenes esta semana y no sé por dónde empezar"
  IA: respuesta con un plan de estudio sugerido
  Usuario: "gracias"
Campo de entrada abajo. Indicador de cuota: "Te quedan 7 consultas hoy".
Sugerencias rápidas como chips: "Plan de estudio", "Cómo concentrarme",
"Organizar mi semana".

--- 10. AVATAR Y PROGRESIÓN ---
Avatar Funko grande en el centro, con la vestimenta de su fase actual (fase 3).
Nombre de la fase, nivel 12, barra de XP.
Debajo: las 10 fases en cuadrícula. Las 3 primeras desbloqueadas a color,
las 7 restantes bloqueadas en escala de grises con un candado.
Selector de las 4 posiciones del personaje (frontal, lateral izq, lateral der, espalda).
EN ESCRITORIO: avatar grande a la izquierda, cuadrícula de fases a la derecha.

--- 11. LOGROS E INSIGNIAS ---
Cuadrícula de insignias. Desbloqueadas a color, bloqueadas atenuadas.
Agrupadas por categoría: Constancia, Volumen, Progresión, Villanos, Bienestar,
Puntualidad.
Ejemplos: "Primera semana" (desbloqueado), "30 días seguidos" (bloqueado,
con progreso 8/30), "10 Pomodoros" (desbloqueado).
Contador arriba: "12 de 45 logros".

--- 12. VILLANO SEMANAL ---
Ilustración del villano "La Distracción" (estilo coherente con el Funko).
Barra de vida grande al 60%.
"Se debilita cuando: completas Pomodoros sin abandonarlos"
Registro de daño de la semana: lista de acciones que le quitaron vida.
Días restantes de la semana. Recompensa al derrotarlo: +100 XP y un fondo nuevo.
```

### Bloque C — Social y configuración

```
TAREA: Genera estas 4 pantallas, en MÓVIL y ESCRITORIO.

--- 13. SESIONES DE ESTUDIO GRUPAL ---
Dos vistas:
(a) LISTA: sesiones abiertas con nombre, anfitrión, participantes (3/8),
    y botón "Unirse". Botón para crear sesión.
(b) SESIÓN ACTIVA: temporizador Pomodoro compartido (12:30), lista de 4
    participantes con sus avatares Funko pequeños y su estado (enfocado/en pausa),
    y chat con 4 mensajes de ejemplo.
EN MÓVIL: chat a pantalla completa con el temporizador arriba fijo.
EN ESCRITORIO: temporizador y participantes a la izquierda, chat en panel
derecho fijo.

--- 14. RANKING ---
Lista de posiciones con alias, nivel y XP total. 10 usuarios.
La posición del usuario actual destacada (puesto 7).
IMPORTANTE: es una pantalla a la que se entra deliberadamente. No la diseñes
como widget ni como algo que aparezca solo.
EN ESCRITORIO: tabla con columnas de posición, alias, nivel, XP y racha.

--- 15. AJUSTES ---
Secciones:
- Apariencia: interruptor claro/oscuro/automático, selector de fondo de pantalla
  (cuadrícula de 8 miniaturas, 2 bloqueadas con candado)
- Pomodoro: duración de foco y descanso con deslizadores
- Notificaciones: interruptores
- Cuenta: alias, carrera, ciclo
- Privacidad: enlace a "Descargar mis datos" y "Retirarme del estudio"

--- 16. PANEL DE ADMINISTRACIÓN (SOLO ESCRITORIO) ---
Dashboard de investigación:
- Tarjetas de resumen: 68 participantes activos hoy, adherencia media 72%,
  1.240 Pomodoros esta semana, 3 alertas de deserción
- Gráfico de líneas: usuarios activos por día durante la intervención
- Gráfico de barras: eventos de telemetría por categoría
- Tabla de participantes: código (EPY-A3F2), nivel, racha, último acceso.
  SIN nombres reales.
- Botón "Exportar dataset"
Aspecto sobrio y denso en datos, distinto al resto de la app.
Si se abre en móvil, muestra un aviso de que requiere pantalla grande.
```

---

## 5. Los 6 estilos

Pega uno de estos bloques junto al contexto común.

### Estilo 1 — Neumorfismo

> ⚠️ **Advertencia.** El neumorfismo distingue elementos por sombras sutiles, no por contraste de color. Por naturaleza tiende a incumplir el mínimo de 3:1 de WCAG para elementos de interfaz, y a plena luz en un celular los bordes desaparecen. Además es el estilo más frágil al pasar a modo oscuro, porque depende de tener una sombra clara y una oscura sobre un mismo tono base. Genéralo para verlo, pero si lo eliges habrá que reforzarlo con bordes visibles y foco explícito.

```
ESTILO: Neumorfismo (soft UI)

- Fondo y elementos comparten el mismo color base (#E8EAF0 en claro, #23262D en oscuro)
- Elementos extruidos o hundidos mediante doble sombra: clara arriba-izquierda,
  oscura abajo-derecha
- Sin bordes duros. Radios de 16 a 24px
- Los botones presionados invierten las sombras
- Paleta monocromática con UN color de acento saturado
- OBLIGATORIO: borde de 1px sutil en elementos interactivos y anillo de foco
  visible, para compensar el bajo contraste inherente del estilo
```

### Estilo 2 — Claymorphism

```
ESTILO: Claymorphism (plastilina 3D suave)

- Elementos con aspecto de plastilina: radios de 24 a 32px, sombra interior
  clara arriba y sombra exterior difusa abajo
- Colores pastel saturados, con texto siempre en tono oscuro para contraste
- Los elementos parecen tener volumen y grosor
- Iconos con volumen, no lineales
- Encaja muy bien con el avatar Funko, que también tiene aspecto de figura
```

### Estilo 3 — Glassmorphism

```
ESTILO: Glassmorphism (vidrio esmerilado)

- Tarjetas semitransparentes con backdrop-filter: blur(12px)
- Borde de 1px blanco al 20% de opacidad
- Fondo con formas orgánicas de color difuminadas
- OBLIGATORIO: el texto va sobre una capa de opacidad suficiente para
  garantizar 4.5:1. Nunca texto directamente sobre el desenfoque
- Encaja con la funcionalidad de fondos de pantalla personalizables
```

### Estilo 4 — Flat moderno (Material 3)

```
ESTILO: Flat moderno siguiendo Material Design 3

- Superficies planas diferenciadas por color de relleno, no por sombra
- Elevación expresada con tono, siguiendo el sistema de surface de M3
- Radios medios de 12 a 16px
- Acento aplicado con moderación, solo en acciones principales
- Jerarquía tipográfica clara y fuerte
- El más conservador de los seis: máxima legibilidad, mínimo riesgo,
  el que mejor sobrevive al cambio de tema
```

### Estilo 5 — Bento grid

```
ESTILO: Bento grid

- Cuadrícula de tarjetas de distintos tamaños, como una caja bento japonesa
- Cada módulo ocupa una celda proporcional a su importancia
- Bordes definidos, separación uniforme de 12 a 16px
- Cada tarjeta con su propio color de fondo suave, distinto entre sí
- En móvil la cuadrícula colapsa a una columna
- Aprovecha especialmente bien el espacio en escritorio
```

### Estilo 6 — Kawaii ilustrado

```
ESTILO: Kawaii ilustrado con paleta pastel

- Base plana con ilustraciones vectoriales simples y redondeadas
- Paleta pastel: rosas, lavandas, menta
- Elementos decorativos discretos: estrellitas, nubes, corazones pequeños,
  sin saturar
- Iconos redondeados de trazo grueso
- OBLIGATORIO: el pastel va en superficies y acentos decorativos. El TEXTO va
  en tono oscuro del mismo matiz (#3D2C3A sobre rosa), nunca pastel sobre
  pastel ni blanco sobre pastel
- Microcopy cálido pero no infantil
```

---

## 6. Cómo comparar y decidir

Con los 6 estilos completos, puntúa cada uno de 1 a 5:

| Criterio | Peso | Pregunta guía |
|---|---|---|
| **Legibilidad real** | Alto | ¿Se lee bien a plena luz en un celular? |
| **Contraste medido** | Alto | ¿Pasa 4.5:1 verificado, no a ojo? |
| **Fatiga a 66 días** | Alto | ¿Lo verías todos los días dos meses sin cansarte? |
| **Escritorio de verdad** | Alto | ¿Aprovecha el ancho o es móvil estirado? |
| **Convive con el Funko** | Medio | ¿El estilo compite con el avatar o lo realza? |
| **Sobrevive al modo oscuro** | Alto | ¿Los dos temas se ven igual de bien? |
| **Viable en 20 días** | Medio | ¿Es razonable implementarlo con Tailwind? |
| **Opinión de beta-testers** | **Máximo** | ¿Cuál usarían a diario? |

La última fila decide. **Enseña los mockups a varios beta-testers y pregunta cuál usarían todos los días durante dos meses.** Además de ser la mejor forma de acertar, es evidencia del proceso de diseño centrado en el humano (ISO 9241-210) que el artículo debe documentar en la tarea 5.3.

> Nota sobre el avatar Funko: fíjate en qué estilos lo integran bien. Un Funko es una figura con volumen y colores planos; los estilos con volumen propio (**Claymorphism**) lo acompañan de forma natural, mientras que los muy planos pueden hacerlo ver pegado encima. El neumorfismo, al ser casi monocromático, puede apagarlo.

---

## 7. Qué hacer después de elegir

1. Actualizar `docs/04-DISENO-VISUAL.md` con el estilo elegido y sus paletas definitivas
2. Verificar cada combinación de color con una herramienta de contraste
3. Construir los componentes de `resources/js/components/ui/` con ese estilo
4. Renombrar este archivo a `08-PROMPTS-MOCKUPS-CERRADO.md`

**Lo que NO cambia al elegir estilo:** arquitectura, módulos, telemetría, base de datos y lógica de gamificación. Por eso se trabaja con tokens semánticos desde el principio: cambiar de estilo es reescribir variables CSS y componentes base, no la aplicación.
