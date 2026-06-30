# Epycus.es — Sitio Web Oficial (Hostinger)

## 0. Filosofía de Diseño: Anti-IA Generica

Este sitio debe EVITAR activamente los patrones que delatan diseño 100% generado por IA en 2026:

| ❌ EVITAR | ✅ HACER |
|-----------|----------|
| Botones `rounded-lg` con padding estándar de shadcn/ui | Bordes personalizados, radios únicos (ej: `rounded-[14px]`), padding no estándar |
| Iconos predecibles de Lucide (estrella=reseñas, rayo=velocidad, escudo=seguridad) | Iconografía custom, mezclar estilos, o usar iconos inesperados |
| Paleta gris Tailwind slate puro (slate-900 fondo, slate-50 texto) | Matices de color únicos en fondos y textos (indigo + ámbar + negro azabache) |
| Background beams, tracer lines, spotlight cursors, text reveal animados | Animaciones sutiles no sobrecargadas. Movimiento con propósito, no decorativo |
| Bento grid perfecto, secciones de igual altura, cuadrícula simétrica | Composición asimétrica, elementos que rompen la rejilla, alturas variables |
| Fotos de Midjourney con iluminación volumétrica perfecta y caras simétricas | Ilustraciones vectoriales originales, o fotos reales sin pulir, o personajes pixel art |
| Skeleton loaders con `animate-pulse` genérico | Sin skeletons, o con shimmer personalizado |
| "Tres columnas con icono + título + texto" en cada sección | Variedad rítmica: 2 columnas, 4 columnas, lista vertical, timeline, grid irregular |

**Referencia de estilo:** Think "manual de videojuego antiguo" mezclado con "UI de RPG moderno". No una startup de Silicon Valley.

---

## 1. Stack Técnico

| Capa | Tecnología | Razón |
|------|-----------|-------|
| Framework | **Astro 5** | Genera HTML estático, cero JS en producción si no se necesita |
| CSS | **Tailwind CSS 4** + CSS custom | Rápido, pero con personalización (no el Tailwind default) |
| Animaciones | **CSS keyframes + GSAP** (mínimo) | Animaciones propias, no copiar efectos de librerías populares |
| Iconos | **Phosphor Icons** (no Lucide) + SVG propios | Diferenciación visual inmediata |
| Fuentes | **Instrument Sans + Fraunces** (Google Fonts) | Combina sans-serif moderna con serif elegante para títulos |
| Despliegue | **Hostinger compartido** (cPanel, gestor archivos) | Subir carpeta `dist/` compilada manualmente |
| Build | Local, subir resultado | Hostinger no ejecuta build |

### Estructura de proyecto

```
epycus-web/
├── public/
│   ├── apk/epycus.apk          ← APK subido manualmente
│   ├── favicon.ico
│   ├── og-image.png
│   └── screenshots/            ← Capturas de la app
├── src/
│   ├── components/
│   ├── layouts/
│   │   └── BaseLayout.astro
│   ├── pages/
│   │   ├── index.astro         ← Inicio
│   │   ├── funciones.astro     ← Funciones
│   │   ├── descargar.astro     ← Descargar
│   │   ├── pro.astro           ← Plan Pro
│   │   ├── acerca.astro        ← Acerca de
│   │   ├── equipo.astro        ← Equipo
│   │   ├── blog/               ← Blog
│   │   ├── roadmap.astro       ← Roadmap
│   │   ├── faq.astro           ← FAQ
│   │   ├── privacidad.astro    ← Privacidad (Play Store)
│   │   └── terminos.astro      ← Términos (Play Store)
│   └── styles/
│       └── global.css
├── astro.config.mjs
├── tailwind.config.mjs
├── package.json
└── tsconfig.json
```

---

## 2. Mapa del Sitio (6 páginas)

```text
epycus.es/
│
├── /                        # Inicio (todo en una sola página)
│                            #   Hero + funciones completas + descargar +
│                            #   pro (próximamente) + CTA + footer
│
├── /acerca                  # Acerca de (todo incluido)
│                            #   Misión + historia + equipo + roadmap +
│                            #   stack técnico + GitHub
│
├── /faq                     # Preguntas frecuentes (acordeón, 12+ preguntas)
│
├── /blog                    # Artículos de productividad académica (SEO)
│   ├── /blog/como-empezar-con-epycus
│   ├── /blog/tecnica-pomodoro-universidad
│   ├── /blog/poder-de-las-rachas
│   ├── /blog/misiones-semestre-aventura
│   └── /blog/que-es-la-gamificacion
│
├── /privacidad              # Política de privacidad (requisito Play Store)
│
└── /terminos                # Términos de uso (requisito Play Store)
```

---

## 3. Guía de Estilo Visual

### 3.1 Paleta de Colores (100% custom, no Tailwind default)

```css
:root {
  /* Base */
  --bg-primary: #0a0a12;        /* Fondo general (oscuro) */
  --bg-secondary: #12121e;      /* Tarjetas, secciones alternas */
  --bg-elevated: #1a1a2e;       /* Elementos elevados, modales */

  /* Acento principal — naranja quemado / ámbar (diferente a apps típicas) */
  --accent-primary: #e8630a;    /* Naranja Epycus (botones, links, highlights) */
  --accent-secondary: #f59e0b;  /* Ámbar (Xp, logros, badges) */
  --accent-glow: rgba(232, 99, 10, 0.15); /* Brillo sutil */

  /* Texto */
  --text-primary: #f5f0eb;      /* Blanco cálido (no blanco puro) */
  --text-secondary: #a09888;    /* Gris cálido */
  --text-muted: #6b6358;        /* Gris oscuro cálido */

  /* Estados */
  --success: #10b981;           /* Hábito completado */
  --error: #ef4444;             /* Error */
  --info: #6366f1;              /* Información */

  /* Bordes */
  --border-color: rgba(232, 99, 10, 0.15);
  --border-radius: 10px;        /* Radio único, no redondeado genérico */

  /* Tipografía */
  --font-sans: 'Instrument Sans', system-ui, sans-serif;
  --font-serif: 'Fraunces', Georgia, serif;
}
```

**Modo claro:** Invertir — fondo blanco roto `#faf6f0`, texto oscuro cálido `#1a1410`, acento naranja igual.

### 3.2 Tipografía

| Uso | Fuente | Peso | Tamaño |
|-----|--------|------|--------|
| Títulos grandes (h1) | Fraunces (serif) | 700 | clamp(2.5rem, 5vw, 4.5rem) |
| Subtítulos (h2/h3) | Fraunces (serif) | 600 | clamp(1.5rem, 3vw, 2.5rem) |
| Cuerpo | Instrument Sans | 400 | 1rem / 1.125rem |
| Navegación / pequeño | Instrument Sans | 500 | 0.875rem |
| Números / XP | Fraunces (serif, italic) | 800 | variable |

### 3.3 Reglas de Diseño (no-negociables)

1. **Sin glassmorphism** — nada de `backdrop-blur` en tarjetas. Superficies sólidas con bordes naranja sutiles.
2. **Sin gradient meshes de fondo.** Fondos sólidos o con textura sutil (noise SVG).
3. **Cada sección con ritmo diferente** — no repetir el mismo layout 3 veces.
4. **Tipografía como elemento visual** — usar Fraunces en tamaños grandes como parte del diseño, no solo para leer.
5. **Bordes naranja tenues** en tarjetas y contenedores (`1px solid var(--border-color)`).
6. **Sin iconos de Lucide.** Usar Phosphor Icons o SVG propios dibujados a mano.
7. **Modo oscuro por defecto**, toggle manual (sol/luna) con preferencia del sistema como fallback.

---

## 4. Contenido por Página

### 4.1 Inicio (`/`)

#### Hero Section (asalto visual inmediato)
```text
[Logo SVG]  [Funciones] [Pro] [Acerca] [FAQ] [Blog]    [Descargar]

┌──────────────────────────────────────────────────────────────┐
│                                                              │
│  ┌─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─┐    │
│                                                              │
│  │  EPYCUS                                                │  │
│      El sistema de hábitos                                  │
│  │  que funciona como un RPG                              │  │
│                                                              │
│  │  ───────────────                                       │  │
│                                                              │
│  │  Crea hábitos. Completa misiones.                      │  │
│      Gana XP. Sube de nivel.                                │
│  │  Desbloquea logros mientras estudias.                  │  │
│                                                              │
│  │  [Empezar gratis →]  [Conocer funciones ↓]            │  │
│                                                              │
│  └─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─┘    │
│                                                              │
│  [Ilustración de personaje subiendo de nivel — pixel art    │
│   o vectorial, animación sutil tipo idle animation RPG]     │
│                                                              │
│  12.000+ horas de enfoque gestionadas · Estudiantes en     │
│  +15 países · 4.8 ⭐                                   │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

**Nota de diseño:** El personaje/pixel art DEBE ser original. No usar Midjourney. Puede ser animación CSS simple (idle breathing, XP bar que sube).

#### Sección "El problema" (1 columna, texto grande, asimétrico)
```
┌────────────────────────────────────────┐
│                                        │
│  Universitarios contra el caos         │
│                                        │
│  Entre clases, trabajos, prácticas     │
│  y vida personal, organizarse es       │
│  otra materia más.                     │
│                                        │
│  Los métodos tradicionales no          │
│  funcionan porque no se adaptan        │
│  a cómo piensa un estudiante.         │
│                                        │
│  La mayoría abandona sus hábitos       │
│  a las 3 semanas.                      │
│                                        │
└────────────────────────────────────────┘
```

#### Sección Features (rítmicamente variada)

**Feature 1 — Hábitos** (2 columnas: texto + ilustración)
```
┌───────────── ─ ─ ─ ─ ─ ─┐
│  Crea rachas que pesan    │
│                            │
│  Hábitos diarios o         │
│  semanales con recorda-    │
│  torios inteligentes.      │
│                            │
│  No romper la racha        │
│  se vuelve más importante  │
│  que la tarea misma.       │
│                            │
│  ─── 12 días es el         │
│  punto de inflexión.       │
└───────────── ─ ─ ─ ─ ─ ─┘
```

**Feature 2 — Pomodoro** (full width con gráfica de estadísticas)
```
┌──────────────────────────────────────────┐
│                                          │
│  Enfoque sin interrupciones              │
│                                          │
│  [Gráfica de sesiones de enfoque]       │
│                                          │
│  Temporizador pomodoro inteligente       │
│  que se adapta a tu ritmo.              │
│  Pausas activas con estiramientos        │
│  guiados. Estadísticas de tu             │
│  concentración semanal.                  │
│                                          │
│  25 min de enfoque → 5 min de pausa     │
│                                          │
└──────────────────────────────────────────┘
```

**Feature 3 — Misiones** (3 columnas desiguales)
```
┌───────┐  ┌──────────────┐  ┌──────────┐
│       │  │              │  │          │
│  Tus  │  │  Cada        │  │  Las     │
│  pro- │  │  misión      │  │  sub-    │
│  yec- │  │  completada  │  │  tareas  │
│  tos  │  │  da XP y    │  │  evitan  │
│  como │  │  recompensas │  │  el      │
│  aven │  │  especiales. │  │  bloqueo │
│  turas│  │              │  │  por     │
│       │  │              │  │  exceso. │
│       │  │              │  │          │
│       │  │              │  │          │
└───────┘  └──────────────┘  └──────────┘
```

**Feature 4 — Progreso + Personajes** (full width, visual grande)
```
┌──────────────────────────────────────────┐
│                                          │
│  Tus números cuentan una historia       │
│                                          │
│  ┌──────────────────────────────────┐   │
│  │                                  │   │
│  │  [Personaje animado]             │   │
│  │                                  │   │
│  │  Nivel 27 — Caballero de Ébano   │   │
│  │  ████████████░░░░░░ 12,450/15,000│   │
│  │                                  │   │
│  │  Logros desbloqueados: 24/50    │   │
│  │  Rachas activas: 3              │   │
│  │  Horas de enfoque: 342          │   │
│  │                                  │   │
│  └──────────────────────────────────┘   │
│                                          │
│  10 personajes desbloqueables según     │
│  tu estilo de productividad.            │
│                                          │
└──────────────────────────────────────────┘
```

**Feature 5 — IA Edy** (2 columnas, mención Pro)
```
┌──────────────┐  ┌──────────────────────┐
│  Edy 🤖      │  │  Tu asistente        │
│              │  │  con IA que:          │
│  ★ Pro       │  │                      │
│              │  │  • Planifica tu día   │
│              │  │  • Responde dudas     │
│              │  │  • Analiza tu         │
│              │  │    rendimiento        │
│              │  │  • Te motiva cuando   │
│              │  │    más lo necesitas   │
│              │  │                      │
│  [Conocer    │  │  (Próximamente en     │
│   Pro →]     │  │   Plan Pro)           │
└──────────────┘  └──────────────────────┘
```

#### Sección CTA Final
```text
┌──────────────────────────────────────────────┐
│                                              │
│  Tu próxima evolución empieza hoy.           │
│                                              │
│  [Descargar para Android]  [Probar Web App] │
│                                              │
│  Gratis. Sin publicidad. Sin límites.        │
│  Solo tú y tu progreso.                      │
│                                              │
└──────────────────────────────────────────────┘
```

#### Footer
```text
Logo Epycus + "Hecho para estudiantes, por estudiantes"

[Inicio] [Funciones] [Pro] [Descargar] [Acerca] [Equipo] [Roadmap] [FAQ] [Blog]
[Privacidad] [Términos]

© 2026 Epycus. Todos los derechos reservados.
Contacto: hola@epycus.es
```

### 4.2 Funciones (`/funciones`)

Página detallada: cada función ocupa una sección completa con:
- Descripción ampliada
- Screenshot real de la app
- Beneficios concretos (bullet points)
- Indicador de si es gratuito o Pro

**Orden de funciones:**
1. Gestión de Hábitos (gratuito)
2. Temporizador Pomodoro (gratuito)
3. Misiones y Subtareas (gratuito)
4. Progreso, Niveles y Personajes (gratuito)
5. Diario de Ánimo y Bienestar (gratuito)
6. Asistente IA Edy (Pro)

### 4.3 Descargar (`/descargar`)

```text
┌──────────────────────────────────────────────┐
│                                              │
│  Lleva Epycus contigo                        │
│                                              │
│  Dos formas de usarlo:                       │
│                                              │
│  ┌────────────────────┐  ┌────────────────┐ │
│  │  Android           │  │  Web App       │ │
│  │                    │  │                │ │
│  │  [APK directo]     │  │  [Abrir en     │ │
│  │  [Código QR]       │  │   navegador]   │ │
│  │                    │  │                │ │
│  │  Peso: 37 MB       │  │  Funciona en   │ │
│  │  Android 8.0+      │  │  cualquier     │ │
│  │  Espacio: 100 MB   │  │  navegador.    │ │
│  │                    │  │  Añade a       │ │
│  │  También en        │  │  pantalla de   │ │
│  │  Google Play       │  │  inicio (PWA). │ │
│  │  (próximamente)    │  │                │ │
│  └────────────────────┘  └────────────────┘ │
│                                              │
│  [Acceder a Epycus → redirige a             │
│   https://app.epycus.es/Autenticacion/Login]│
│                                              │
└──────────────────────────────────────────────┘
```

### 4.4 Pro (`/pro`)

Página minimalista. Una sola sección:
```text
┌──────────────────────────────────────────────┐
│                                              │
│  Epycus Pro                                  │
│                                              │
│  Desbloquea Edy, tu asistente               │
│  personal con inteligencia artificial.       │
│                                              │
│  • Chat ilimitado con IA                     │
│  • Análisis semanal de rendimiento           │
│  • Recomendaciones personalizadas            │
│  • Prioridad en nuevas funciones             │
│                                              │
│  ┌──────────────────────────────────────┐   │
│  │                                      │   │
│  │        PRÓXIMAMENTE                  │   │
│  │                                      │   │
│  │  Estamos preparando algo especial    │   │
│  │  para llevar tu productividad        │   │
│  │  al siguiente nivel.                 │   │
│  │                                      │   │
│  │  Déjanos tu correo y te avisamos:    │   │
│  │  [________________________] [→]     │   │
│  │                                      │   │
│  └──────────────────────────────────────┘   │
│                                              │
└──────────────────────────────────────────────┘
```

### 4.5 Acerca (`/acerca`)

- **Misión:** "Convertir la productividad académica en una experiencia adictiva y gratificante."
- **Historia:** Proyecto nacido de la necesidad de un estudiante universitario.
- **Stack técnico:** .NET 9, Android (Kotlin/Java), SignalR, DeepSeek IA, MySQL.
- **Enlace al repositorio de GitHub.**
- **Público objetivo:** Mayores de 13 años, uso internacional.
- **Inspiración:** Sistemas RPG de progresión y gamificación conductual (sin marcas registradas).

### 4.6 Equipo (`/equipo`)

- Sección tipo "single founder". Puede ser una sola persona o un equipo pequeño.
- Fotos reales (no IA). Si no hay fotos, ilustraciones simples.
- Cada miembro: nombre + rol + qué hace en Epycus.

### 4.7 Blog (`/blog/`)

- Listado de artículos con tarjeta (imagen, título, extracto, fecha).
- Cada artículo es una página independiente.
- **Artículos iniciales sugeridos:**
  1. "Cómo empezar con Epycus en 5 minutos"
  2. "Técnica Pomodoro para estudiantes universitarios"
  3. "El poder de las rachas: cómo no romper la cadena"
  4. "Gestión de misiones: convierte tu semestre en una aventura"
  5. "Qué es la gamificación y por qué funciona en educación"

### 4.8 Roadmap (`/roadmap`)

Línea de tiempo vertical con hitos:
```text
2026 Q1 — Lanzamiento versión beta (completado ✅)
2026 Q2 — Lanzamiento oficial Android (completado ✅)
2026 Q3 — Epycus Pro con IA Edy (en desarrollo 🚧)
2026 Q4 — Versión iOS (planeado 📋)
2027 Q1 — Comunidad y logros sociales (planeado 📋)
...
```

### 4.9 FAQ (`/faq`)

Preguntas frecuentes en acordeón. Mínimo 12 preguntas cubriendo:
- ¿Qué es Epycus?
- ¿Es gratis?
- ¿Cómo empiezo?
- ¿Qué datos guarda?
- ¿Cómo funciona el sistema de niveles?
- ¿Puedo usar Epycus sin conexión?
- ¿Cuándo estará en Google Play?
- ¿Habrá versión iOS?
- ¿Qué es el Plan Pro?
- ¿Cómo cancelo mi suscripción Pro?
- ¿Epycus es seguro?
- ¿Puedo exportar mis datos?

### 4.10 Privacidad (`/privacidad`)

**URL para añadir en Google Play Store y Samsung Galaxy Store:**
```
https://epycus.es/privacidad
```

**Texto completo de la página de privacidad (debe ser renderizado en HTML):**

```text
# Política de Privacidad

**Última actualización:** Julio 2026

---

## 1. Responsable del Tratamiento

Epycus
Correo electrónico: hola@epycus.es

---

## 2. Datos que Recopilamos

### 2.1 Datos proporcionados por el usuario
- Correo electrónico
- Nombre de usuario
- Fecha de nacimiento (para verificar edad mínima)
- Género (opcional)
- Contenido de las entradas del diario de ánimo
- Mensajes enviados al asistente IA

### 2.2 Datos generados por el uso de la aplicación
- Hábitos creados, completados y rachas
- Sesiones de Pomodoro (duración, pausas, completadas)
- Misiones y subtareas creadas y completadas
- Estado de ánimo registrado diariamente
- Nivel, experiencia (XP) y logros desbloqueados
- Tiempo total de enfoque
- Preferencias de tema (oscuro/claro)

### 2.3 Datos técnicos
- Versión de la aplicación
- Idioma del dispositivo
- Zona horaria
- Identificador único del dispositivo (solo para notificaciones push)

---

## 3. Finalidad del Tratamiento

| Finalidad | Base legal |
|-----------|-----------|
| Gestionar tu cuenta y autenticación | Ejecución del contrato |
| Proporcionar las funciones de la app (hábitos, pomodoro, misiones, progreso, diario) | Ejecución del contrato |
| Sincronizar tus datos entre dispositivos | Ejecución del contrato |
| Ofrecer el asistente IA (Edy) | Consentimiento |
| Mejorar la aplicación con estadísticas anónimas | Interés legítimo |
| Enviar recordatorios y notificaciones | Consentimiento |
| Cumplir con obligaciones legales | Obligación legal |

---

## 4. Conservación de Datos

Mantenemos tus datos mientras tu cuenta esté activa. Una vez que solicites la eliminación de tu cuenta, todos tus datos personales se eliminarán en un plazo máximo de 30 días, excepto aquellos que debamos conservar por obligaciones legales.

Los datos anonimizados con fines estadísticos pueden conservarse de forma indefinida una vez disociados de tu identidad.

---

## 5. Transferencias Internacionales

Utilizamos servicios de terceros que pueden implicar transferencias internacionales de datos:

| Servicio | Ubicación | Medida de protección |
|----------|-----------|---------------------|
| DeepSeek (IA) | China / Singapur | Cláusulas Contractuales Tipo |
| Sentry (errores) | EE. UU. | Data Privacy Framework |
| Google Fonts / CDN | EE. UU. | Data Privacy Framework |

Todas las transferencias cuentan con garantías adecuadas según el RGPD.

---

## 6. Servicios de Terceros

| Servicio | Propósito | Datos compartidos |
|----------|-----------|-------------------|
| DeepSeek | Procesar mensajes del asistente IA (solo Plan Pro) | Mensajes de texto anonimizados |
| Sentry | Monitoreo de errores y rendimiento | Datos técnicos anónimos |
| Google Fonts | Visualización de tipografías | Dirección IP |
| MariaDB (MySQL) | Almacenamiento de datos | Todos los datos de la cuenta |
| Nginx | Servidor web | Dirección IP |

---

## 7. Seguridad

Implementamos las siguientes medidas de seguridad:

- Cifrado TLS 1.3 en todas las comunicaciones
- Tokens JWT con blacklist para sesiones
- Contraseñas almacenadas con BCrypt (coste 12)
- Rate limiting para prevenir abusos
- Headers de seguridad (CSP, HSTS, X-Frame-Options)
- Auditoría de accesos a la base de datos
- Política de contraseñas fuertes

---

## 8. Derechos del Usuario (ARCO + RGPD)

Tienes derecho a:

- **Acceso:** Saber qué datos tuyos tenemos y cómo los usamos
- **Rectificación:** Corregir datos inexactos
- **Cancelación:** Solicitar la eliminación de tus datos
- **Oposición:** Oponerte al tratamiento de tus datos
- **Portabilidad:** Recibir tus datos en formato estructurado
- **Limitación:** Restringir el tratamiento de tus datos
- **Retirada del consentimiento:** En cualquier momento, sin afectar a la licitud del tratamiento previo

Para ejercer cualquiera de estos derechos, escribe a **hola@epycus.es** desde el correo asociado a tu cuenta. Responderemos en un plazo máximo de 30 días.

También puedes presentar una reclamación ante la **Agencia Española de Protección de Datos (AEPD)**.

---

## 9. Edad Mínima

Epycus está dirigido a mayores de **13 años**. Si tienes menos de 13 años, no puedes crear una cuenta. Si descubrimos que un usuario es menor de 13 años, eliminaremos su cuenta y todos sus datos.

---

## 10. Cambios en esta Política

Notificaremos cualquier cambio material en esta política a través de la aplicación o por correo electrónico. El uso continuado de Epycus después de los cambios implica la aceptación de la política actualizada.

---

## 11. Contacto

Para cualquier pregunta sobre esta política de privacidad:

- Correo: **hola@epycus.es**
- Web: **https://epycus.es**

---

*Esta política de privacidad cumple con el Reglamento General de Protección de Datos (RGPD - UE 2016/679) y la Ley Orgánica de Protección de Datos y Garantía de los Derechos Digitales (LOPDGDD - España).*
```

### 4.11 Términos (`/terminos`)

**URL para añadir en Google Play Store y Samsung Galaxy Store:**
```
https://epycus.es/terminos
```

**Texto completo de la página de términos:**

```text
# Términos de Uso

**Última actualización:** Julio 2026

---

## 1. Aceptación de los Términos

Al crear una cuenta en Epycus, aceptas estos términos en su totalidad. Si no estás de acuerdo, no utilices la aplicación.

---

## 2. Descripción del Servicio

Epycus es un sistema multiplataforma de gamificación de hábitos profesionales para estudiantes. Incluye:

- Gestión de hábitos (crear, completar, seguimiento de rachas)
- Temporizador Pomodoro con estadísticas
- Sistema de misiones y subtareas
- Progreso con personajes, niveles y logros
- Diario de ánimo y bienestar
- Asistente con inteligencia artificial (Edy) — disponible en Plan Pro

La aplicación es gratuita con funciones premium opcionales (Epycus Pro, próximamente).

---

## 3. Elegibilidad

Debes tener al menos **13 años** para usar Epycus. Si tienes entre 13 y 18 años, necesitas permiso de tus padres o tutores legales.

---

## 4. Cuentas de Usuario

- Eres responsable de mantener la confidencialidad de tu contraseña
- No puedes compartir tu cuenta con otras personas
- Debes proporcionar información precisa y actualizada
- Nos reservamos el derecho de suspender cuentas que violen estos términos

---

## 5. Conducta del Usuario

No puedes:

- Utilizar Epycus para actividades ilegales
- Intentar acceder a cuentas de otros usuarios
- Abusar del sistema de gamificación (explotar bugs para ganar XP)
- Enviar contenido inapropiado a través del asistente IA
- Realizar ingeniería inversa de la aplicación

---

## 6. Propiedad Intelectual

Epycus es propiedad de sus creadores. El contenido que generes dentro de la aplicación (hábitos, misiones, entradas del diario) te pertenece. Nos otorgas una licencia para almacenar y mostrar este contenido dentro de la aplicación.

Los personajes, sistema de niveles, logros y diseño visual son propiedad de Epycus.

---

## 7. Plan Pro (Suscripciones)

Actualmente no hay planes de pago activos. Cuando estén disponibles:

- Las suscripciones se renovarán automáticamente
- Puedes cancelar en cualquier momento
- No se realizarán reembolsos parciales
- Los precios se anunciarán con antelación

---

## 8. Limitación de Responsabilidad

Epycus se proporciona "tal cual", sin garantías de funcionamiento ininterrumpido. No nos hacemos responsables de:

- Pérdida de datos por causas ajenas a nuestro control
- Daños derivados del uso de la aplicación
- Interrupciones del servicio por mantenimiento

---

## 9. Privacidad

El tratamiento de tus datos personales se rige por nuestra [Política de Privacidad](/privacidad).

---

## 10. Cancelación

Puedes eliminar tu cuenta en cualquier momento desde Ajustes > Eliminar cuenta. Tras la eliminación, tus datos personales se borrarán en un plazo máximo de 30 días.

Nos reservamos el derecho de suspender o cancelar cuentas que violen estos términos.

---

## 11. Cambios en los Términos

Notificaremos cualquier cambio con 30 días de antelación. El uso continuado después de los cambios implica la aceptación de los nuevos términos.

---

## 12. Legislación Aplicable

Estos términos se rigen por la legislación española. Para cualquier controversia, las partes se someten a los juzgados de Madrid (España).

---

## 13. Contacto

Para cualquier consulta sobre estos términos:

- Correo: **hola@epycus.es**
- Web: **https://epycus.es**
```

---

## 5. SEO (Avanzado)

### 5.1 Meta Tags (base para todas las páginas)
```html
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Epycus — Sistema de hábitos gamificado para estudiantes</title>
<meta name="description" content="Epycus convierte tu rutina universitaria en un RPG. Crea hábitos, usa Pomodoro, completa misiones y sube de nivel. Gratis, sin publicidad." />
<meta name="keywords" content="hábitos, pomodoro, misiones, gamificación, estudiantes, productividad, RPG, niveles, universidad" />
<meta name="author" content="Epycus" />
<meta name="robots" content="index, follow" />
<link rel="canonical" href="https://epycus.es/" />
```

### 5.2 Open Graph + Twitter
```html
<meta property="og:title" content="Epycus — Sistema de hábitos gamificado" />
<meta property="og:description" content="Convierte tu rutina universitaria en un RPG. Gratis." />
<meta property="og:image" content="https://epycus.es/og-image.png" />
<meta property="og:url" content="https://epycus.es/" />
<meta property="og:type" content="website" />
<meta property="og:locale" content="es_ES" />
<meta name="twitter:card" content="summary_large_image" />
```

### 5.3 JSON-LD (Software Application + WebSite)
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Epycus",
  "operatingSystem": "Android, Web",
  "applicationCategory": "GameApplication",
  "applicationSubCategory": "Productivity",
  "description": "Sistema multiplataforma de gamificación de hábitos profesionales para estudiantes",
  "offers": [
    {
      "@type": "Offer",
      "price": "0",
      "priceCurrency": "EUR"
    },
    {
      "@type": "Offer",
      "price": "4.99",
      "priceCurrency": "EUR",
      "name": "Epycus Pro (próximamente)"
    }
  ],
  "author": {
    "@type": "Organization",
    "name": "Epycus",
    "url": "https://epycus.es"
  }
}
</script>
```

### 5.4 Sitemap y RSS
- `sitemap.xml` generado automáticamente por Astro
- `rss.xml` para el blog
- `robots.txt` apuntando a sitemap

### 5.5 Estrategia de Contenido para Blog (SEO)
- 5 artículos iniciales (1.500-2.000 palabras cada uno)
- Palabras clave objetivo: "hábitos para estudiantes", "técnica pomodoro universidad", "gamificación productividad", "organización universitaria"
- Internal linking entre artículos y páginas principales
- URLs amigables: `/blog/como-empezar-con-epycus`

---

## 6. Requisitos Legales Play Store

Para publicar en Google Play necesitas:

### 6.1 Política de Privacidad (`/privacidad`)
- Alojada en `https://epycus.es/privacidad`
- Enlace directo desde la ficha de Google Play
- Idioma: español (principal) + opción inglés
- **DEBE CUBRIR:**
  - Tipos de datos personales recogidos
  - Finalidad del tratamiento
  - Base legal (consentimiento, RGPD)
  - Derechos ARCO/LOPD
  - Transferencias internacionales
  - Seguridad de los datos
  - Contacto del responsable de tratamiento
  - Período de conservación

### 6.2 Términos y Condiciones (`/terminos`)
- Alojada en `https://epycus.es/terminos`
- **DEBE CUBRIR:**
  - Edad mínima (13+ años)
  - Creación y responsabilidad de cuenta
  - Propiedad intelectual del contenido
  - Limitación de responsabilidad
  - Ley aplicable (España)
  - Suspensión del servicio

### 6.3 Página de Descargas
- Si la app se distribuye como APK fuera de Play Store, la web debe tener:
  - Versión de la app
  - Fecha de actualización
  - Notas de la versión
  - Enlace de descarga directa

---

## 7. Estructura del Proyecto (final)

```
epycus-web/
├── public/
│   ├── apk/
│   │   └── epycus-v1.0.0.apk          ← Subir manualmente
│   ├── favicon.ico
│   ├── og-image.png
│   ├── robots.txt
│   ├── screenshots/
│   │   ├── dashboard.webp
│   │   ├── habitos.webp
│   │   ├── pomodoro.webp
│   │   ├── misiones.webp
│   │   ├── ia-chat.webp
│   │   ├── progreso.webp
│   │   └── perfil.webp
│   └── assets/
│       ├── icons/                       ← Iconos SVG personalizados
│       └── illustrations/               ← Ilustraciones vectoriales
│
├── src/
│   ├── components/
│   │   ├── Navbar.astro
│   │   ├── Footer.astro
│   │   ├── HeroSection.astro
│   │   ├── ProblemSection.astro
│   │   ├── FeatureHabitos.astro
│   │   ├── FeaturePomodoro.astro
│   │   ├── FeatureMisiones.astro
│   │   ├── FeatureProgreso.astro
│   │   ├── FeatureIA.astro
│   │   ├── CTASection.astro
│   │   ├── DownloadCard.astro
│   │   ├── LevelShowcase.astro
│   │   ├── RoadmapTimeline.astro
│   │   ├── FAQAccordion.astro
│   │   ├── BlogCard.astro
│   │   ├── ThemeToggle.astro
│   │   └── MobileMenu.astro
│   │
│   ├── layouts/
│   │   └── BaseLayout.astro            ← Head, meta, navbar, footer, theme
│   │
│   ├── pages/
│   │   ├── index.astro
│   │   ├── funciones.astro
│   │   ├── descargar.astro
│   │   ├── pro.astro
│   │   ├── acerca.astro
│   │   ├── equipo.astro
│   │   ├── roadmap.astro
│   │   ├── faq.astro
│   │   ├── privacidad.astro
│   │   ├── terminos.astro
│   │   └── blog/
│   │       ├── index.astro             ← Listado de artículos
│   │       ├── como-empezar-con-epycus.astro
│   │       ├── tecnica-pomodoro-universidad.astro
│   │       ├── poder-de-las-rachas.astro
│   │       ├── misiones-semestre-aventura.astro
│   │       └── gamificacion-educacion.astro
│   │
│   └── styles/
│       ├── global.css                   ← Variables, reset, theme
│       └── fonts.css                    ← Google Fonts imports
│
├── astro.config.mjs
├── tailwind.config.mjs
├── package.json
├── tsconfig.json
└── README.md
```

---

## 8. Plan de Implementación (para la IA)

### Fase 1: Inicializar Proyecto
1. `npm create astro@latest epycus-web -- --template basics --typescript`
2. `cd epycus-web && npx astro add tailwind`
3. Instalar Phosphor Icons: `npm install @phosphor-icons/react` (o CDN para Astro)
4. Configurar `tailwind.config.mjs` con la paleta custom (NO usar colores default)
5. Crear `global.css` con variables CSS, reset personalizado, estilos base

### Fase 2: Layout Base
1. `BaseLayout.astro` — meta tags, fonts (Instrument Sans + Fraunces), theme toggle
2. `Navbar.astro` — logo, links, botón descargar, theme toggle, menú móvil asimétrico
3. `Footer.astro` — links, redes sociales, copyright

### Fase 3: Landing Page (`index.astro`)
1. Hero section con personaje animado (CSS idle animation)
2. Problem section (una columna, texto grande)
3. Features una por una (cada sección con layout diferente)
4. CTA section
5. **NO USAR ACETERNITY, MAGIC UI, NI SHADCN**

### Fase 4: Páginas internas
1. `/funciones` — 6 secciones detalladas con screenshots reales
2. `/descargar` — tarjetas de descarga, QR, acceso a web app
3. `/pro` — próximamente con formulario de notificación
4. `/acerca` + `/equipo`
5. `/roadmap` — timeline vertical
6. `/faq` — acordeón
7. `/privacidad` + `/terminos`
8. `/blog/` — listado + 5 artículos

### Fase 5: Generar Screenshots
- Usar Playwright contra `https://app.epycus.es` para capturar:
  - Dashboard principal
  - Vista de hábitos
  - Pomodoro en acción
  - Lista de misiones
  - Chat con Edy
  - Perfil con nivel y logros
- Credenciales de test: crear usuario vía API o usar las existentes

### Fase 6: SEO
1. Sitemap generado automáticamente (Astro)
2. RSS para blog
3. JSON-LD en todas las páginas

### Fase 7: Build y Despliegue
1. `npm run build` → carpeta `dist/`
2. Subir todo `dist/` al cPanel de Hostinger (gestor de archivos → `public_html/`)
3. Subir APK manualmente a `public_html/apk/`
4. Verificar que todo funciona

---

## 9. Screenshots (Generar con Playwright)

```typescript
// scripts/screenshots.ts
import { chromium } from 'playwright';

async function takeScreenshots() {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const page = await context.newPage();

  // Login
  await page.goto('https://app.epycus.es/api/v1/auth/login');
  // ... llamada API para obtener token, setear cookies

  const pages = [
    { path: '/', name: 'dashboard' },
    { path: '/Habitos', name: 'habitos' },
    { path: '/Pomodoro', name: 'pomodoro' },
    { path: '/Misiones', name: 'misiones' },
    { path: '/Ia', name: 'ia-chat' },
    { path: '/Progreso', name: 'progreso' },
    { path: '/Perfil', name: 'perfil' },
  ];

  for (const { path, name } of pages) {
    await page.goto(`https://app.epycus.es${path}`);
    await page.waitForTimeout(2000);
    await page.screenshot({ path: `public/screenshots/${name}.webp`, fullPage: true });
  }

  await browser.close();
}

takeScreenshots();
```

---

## 10. Notas para la IA

1. **No uses shadcn/ui, Aceternity UI, Magic UI, ni Tailwind UI.** Nada de componentes predefinidos. Todo debe sentirse artesanal.
2. **No uses Lucide React.** Usa Phosphor Icons o iconos SVG propios.
3. **No uses fotos de Midjourney.** Las ilustraciones deben ser vectores originales o personajes pixel art.
4. **No uses glassmorphism, gradients backgrounds, spotlight effects, ni background beams.**
5. **Cada sección debe tener un layout único.** Alterna: 1 columna, 2 columnas, 3 columnas desiguales, full width, timeline, acordeón.
6. **Fraunces para títulos, Instrument Sans para cuerpo.** La serif en titulares es parte del ADN visual.
7. **El personaje del hero debe tener animación CSS idle** (respiración, parpadeo, barra de XP que sube).
8. **No generes contenido con IA** para los artículos del blog. Escribe contenido genuino sobre productividad académica.
9. **El APK se sube manualmente** a `public/apk/epycus.apk`. La web solo enlaza.
10. **Modo oscuro por defecto.** El toggle sol/luna guarda preferencia en localStorage.
11. **El botón "Acceder"** redirige a `https://app.epycus.es/Autenticacion/Login`.
12. **Hostinger**: compartido con cPanel. Subir carpeta `dist/` completa al `public_html/`. No hay build en servidor.

---

## 11. Lista Completa de Assets (Imágenes)

```
public/
├── logo.svg                         ← Logo Epycus vector (escalable)
├── logo-symbol.svg                  ← Solo el símbolo (favicon, footer, avatar)
├── favicon.ico                      ← 32x32 + 16x16
├── og-image.png                     ← Open Graph 1200×630px
├── robots.txt
│
├── illustrations/
│   ├── hero-character.webp          ← Personaje principal (animación CSS idle)
│   ├── habitos-scene.webp           ← Escena de hábitos (estilo tablero RPG)
│   ├── pomodoro-graph.webp          ← Visualización pomodoro
│   ├── misiones-scene.webp          ← Mapa de misiones
│   ├── progreso-levels.webp         ← Sistema de niveles visual
│   └── acerca-team.webp             ← Ilustración para página Acerca
│
├── screenshots/                     ← Capturas reales de app.epycus.es
│   ├── dashboard.webp               (390×844 — vista móvil)
│   ├── habitos.webp
│   ├── pomodoro.webp
│   ├── misiones.webp
│   ├── ia-chat.webp
│   ├── progreso.webp
│   └── perfil.webp
│
├── icons/                           ← SVG propios (no Lucide)
│   ├── icon-habitos.svg
│   ├── icon-pomodoro.svg
│   ├── icon-misiones.svg
│   ├── icon-progreso.svg
│   ├── icon-bienestar.svg
│   ├── icon-ia.svg
│   ├── icon-xp.svg
│   ├── icon-level.svg
│   ├── icon-achievement.svg
│   ├── icon-android.svg
│   ├── icon-web.svg
│   ├── icon-download.svg
│   ├── icon-check.svg
│   ├── icon-sun.svg
│   ├── icon-moon.svg
│   ├── icon-menu.svg
│   └── icon-close.svg
│
└── apk/
    └── epycus-v1.0.0.apk           ← APK (subir manualmente al cPanel)
```

**Total: ~30 archivos** entre ilustraciones, screenshots, iconos y logo.

---

## 12. Prompt para Generar Mockups de Inspiración

Pega este prompt en Midjourney, DALL-E 3, Stable Diffusion o Leonardo AI para generar mockups visuales del estilo que debe tener la web.

### Para el Hero / Personaje principal

```
A pixel-art style RPG character standing confidently, viewed from front, full body. 
The character is a university student hero wearing a modern hoodie with subtle armor accents. 
Color palette: deep charcoal (#0a0a12) background, warm orange (#e8630a) as primary accent, 
amber (#f59e0b) for glowing details. The character glows with a subtle orange aura. 
Beside them floats a floating level badge showing "LVL 27" and an XP bar 
that is 80% full. Style: modern pixel art with smooth gradients, 
not retro 8-bit. Cozy RPG aesthetic, warm lighting. 
No blue/purple tones. Dark fantasy academia vibes. 
Background: abstract dark with floating orange particles.
--ar 2:3 --s 250 --v 6.1
```

### Para la paleta visual general / Moodboard

```
A dark academia RPG-inspired landing page design. 
Split screen showing a mobile app interface on the right and text on the left.
Color scheme: deep black backgrounds (#0a0a12), warm off-white text (#f5f0eb),
burnt orange (#e8630a) for all interactive elements, amber (#f59e0b) for XP/accent.
No glassmorphism, no blurred backgrounds. Solid surfaces with thin orange borders.
Typography: Fraunces serif for large headlines, Instrument Sans for body text.
The UI has pixel-art style icons, custom illustrations, and an RPG stat screen aesthetic.
Mood: cozy, warm, scholarly, gamified. Not a generic startup site.
No blue colors. No gradients. No glass effects. No Lucide icons.
--ar 16:9 --s 200 --v 6.1
```

### Para las ilustraciones de features (hábitos, pomodoro, misiones)

```
An illustration representing habit tracking gamified for university students.
Scene: a student desk with a magical floating checklist that has a glowing streak counter.
Warm orange lighting from the checklist illuminates the desk.
Small pixel-art style character on the screen doing a victory pose.
Art style: vector illustration with pixel-art influences, flat colors with subtle shading.
Color palette: dark background, orange accents, amber highlights.
Cozy, magical realism, dark academia.
No blue tones. No photorealism. No Midjourney smooth portraits.
--ar 4:3 --s 150 --v 6.1
```

### Para el sistema de niveles y progreso (mostrar personajes)

```
A dark RPG-style character evolution chart showing 5 stages of a student hero.
Stage 1 (LVL 1-10): student in casual clothes with a small book.
Stage 2 (LVL 11-30): student with a backpack and glowing pen.
Stage 3 (LVL 31-60): student in armor-like hoodie with a sword made of a ruler.
Stage 4 (LVL 61-90): student as a mage with a glowing book.
Stage 5 (LVL 91-100): legendary student with wings made of pages.
Each stage connected by glowing orange arrows.
Background: dark with floating achievement badges.
Color palette: orange (#e8630a), amber (#f59e0b), dark charcoal (#0a0a12).
Style: vector illustration with pixel-art influences. Character design sheet layout.
--ar 16:9 --s 250 --v 6.1
```

### Para el apartado visual general de la web (mood completo)

```
A dark academic fantasy website landing page mockup. 
Header has a custom pixel-art style logo in orange on dark background.
Navigation is minimal, text-heavy with serif fonts.
Hero section shows a large Fraunces serif headline "Tu vida universitaria, ahora es una aventura"
in warm off-white on deep charcoal background.
Below, a 3-column feature grid with custom orange-bordered cards,
each containing a small pixel-art icon, a serif heading, and body text.
The cards have thin 1px orange borders, solid backgrounds (#12121e), no glass effects.
Bottom has a call-to-action button in solid orange with rounded corners (not fully rounded).
No gradient backgrounds. No blur effects. No glowing elements except subtle orange ambient light.
Mood: cozy dark RPG academy. Warm amber tones dominating.
Composition: centered, breathing room, asymmetrical but balanced.
--ar 16:9 --s 200 --v 6.1
```

### Para los iconos de features (estilo único)

```
A set of 6 pixel-art inspired icons for a gamified productivity app.
Each icon is highly detailed, 64x64 pixel art style but rendered at high resolution.
1. A checklist with a star (habits)
2. A tomato timer with flames (pomodoro)
3. A scroll with a seal (missions)
4. A level-up arrow with a gem (progress)
5. A heart with a leaf (wellness)
6. A robot with a graduation cap (AI assistant)
Color palette: burnt orange (#e8630a), amber (#f59e0b), on transparent background.
Thin white outlines. Cozy RPG item icon aesthetic.
Icons should feel like they belong in an RPG inventory screen.
--ar 1:1 --s 300 --v 6.1
```
