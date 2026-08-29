# 15 — Catálogo de Imágenes y Assets Visuales

> Inventario y convención de nombres de **todos** los assets visuales del proyecto: avatares vectoriales procedurales, GIFs contextuales por módulo e imágenes de marca.

---

## 1. Avatares de Personaje (Motor Vectorial Open Peeps)

El sistema de avatares fue migrado 100% al motor procedural vectorial basado en **Open Peeps de Pablo Stanley** (`@dicebear/open-peeps`), eliminando la dependencia de imágenes PNG estáticas pesadas.

### 1.1 Personalización por Usuario y Persistencia
- **Componente de Renderizado:** [ProceduralAvatar.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Components/ProceduralAvatar.vue)
- **Editor / Creador Interactivo:** [AvatarCustomizer.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Components/AvatarCustomizer.vue) en `/profile`.
- **Almacenamiento:** Columna `users.avatar_options` (JSON) en la base de datos.
- **Rasgos Personalizables:**
  - 🎨 **Tono de Piel:** 5 tonos naturales (Claro `#FFDBB4`, Cálido `#EDB98A`, Canela `#FDAC53`, Moreno `#D08B5B`, Oscuro `#AE5D29`).
  - 💇 **Cabello:** 15 cortes vectoriales (cortos, afro, rapado, largo, rizado, moño, etc.).
  - 😃 **Expresión Facial:** 8 emociones (sonriente, alegre, tierno, tranquilo, enfocado, explicando, serio, solemne).
  - 👓 **Lentes / Accesorios:** 7 estilos (sin lentes, cuadrados, retro, finos, ejecutivos, redondos, gafas de sol).
  - 🧔 **Barba / Vello Facial:** 5 estilos (sin barba, bigote clásico, perilla, candado, barba completa).
  - 👕 **Color de Ropa:** 8 colores (Azul Noche, Cyan Tech, Naranja, Esmeralda, Carmesí, Violeta, Blanco, Carbón).
  - 🖼️ **Color de Fondo:** 6 colores (Rosa, Lavanda, Menta, Melocotón, Celeste, Noche Estelar).

---

## 2. GIFs Animados Contextuales por Módulo

Carpeta: `public/assets/gifs/`

Cada módulo de estudio sin foto de perfil cuenta con su propio GIF animado temático:

| Archivo | Módulo | Contexto / Tema | Vista Frontend |
|---|---|---|---|
| `public/assets/gifs/pomodoro.gif` | Pomodoro | Temporizador, foco y sesión de estudio concentrado | `Pomodoro/Index.vue` |
| `public/assets/gifs/habits.gif` | Hábitos | Calendario de racha, lista de hábitos y consistencia | `Habits/Index.vue` |
| `public/assets/gifs/missions.gif` | Misiones | Diana de puntería, metas y descomposición de tareas | `Missions/Index.vue` |
| `public/assets/gifs/achievements.gif` | Logros e Insignias | Trofeo dorado de victoria, medallas y reconocimientos | `Achievements/Index.vue` |
| `public/assets/gifs/benny-typing-v2.gif` | Asistente IA | Edy el robot asistente escribiendo/analizando | `AiAssistant/Index.vue` |

---

## 3. Fondos de Pantalla Secuenciales (`Fondo_1` .. `Fondo_N`)

Carpeta: `public/assets/wallpapers/`

Los fondos de pantalla para el modo Vidrio se identifican de manera secuencial mediante su código:

- `Fondo_1`, `Fondo_2`, `Fondo_3`, etc.
- Seleccionables por el usuario desde las preferencias de interfaz.

---

## 4. Imágenes de Marca (Piezas Únicas)

| Archivo | Uso | Componente / Lugar |
|---|---|---|
| `public/assets/images/logo.webp` | Logo oficial de Epycus | `ApplicationLogo.vue`, Navbar |
| `public/assets/images/login-hero.webp` | Ilustración de la pantalla de inicio de sesión | `GuestLayout.vue` |
| `public/assets/images/favicon.ico` | Favicon de la aplicación | `<head>` global |

---

## 5. Villanos Semanales

Carpeta: `public/assets/villains/`

| Archivo | Villano | Código |
|---|---|---|
| `public/assets/villains/Villano_postergación.png` | La Postergación | `procrastination` |
| `public/assets/villains/Villano_distracción.png` | La Distracción | `distraction` |
| `public/assets/villains/Villano_ansiedad.png` | La Ansiedad | `anxiety` |
| `public/assets/villains/Villano_desorden.png` | El Desorden | `disorder` |
| `public/assets/villains/Villano_cansancio.png` | El Cansancio | `fatigue` |
