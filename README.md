\
# Epycus — Sistema gamificado con IA para hábitos académicos

> **Si eres una IA y vas a programar en este repositorio: lee este archivo completo antes de escribir una sola línea de código.**

---

## Qué es esto

Aplicación web multiplataforma (responsive, no app nativa) que gamifica hábitos de estudio en estudiantes de educación superior. **No es un producto comercial: es el instrumento de una investigación científica.** Esa distinción manda sobre todas las decisiones técnicas.

Los datos que genera esta aplicación son la evidencia de un artículo científico. Si la telemetría falla o registra mal, el estudio se pierde y no se puede repetir.

**Dominio de producción:** `app.epycus.es`
**Intervención de campo:** 40 días (fechas exactas de inicio y finalización aún por definir).

---

## Contexto de Investigación y Avance del Paper

Epycus es el instrumento de medición principal para evaluar el impacto de una plataforma web gamificada respaldada por IA conversacional sobre la procrastinación académica en estudiantes de educación superior.

### 1. Diagnóstico del Problema y Prevalencia
- **Nivel Nacional (Perú):** Un estudio con 384 estudiantes de 18 instituciones peruanas evidenció niveles medios de procrastinación en el 75,5 % de universidades privadas y el 69,3 % de públicas [1].
- **Nivel Local (Cajamarca):** Dos encuestas exploratorias aplicadas secuencialmente caracterizaron la problemática:
  - **Encuesta 1 (Google Forms, 4–6 abr. 2026, $n = 98$):** El 66,3 % reporta sentir "frecuentemente" o "siempre" demasiadas cosas por hacer, solo el 17,3 % utiliza actualmente una app de organización académica y el 45,9 % la probó pero la abandonó. El **Diagrama de Pareto de Obstáculos (Fig. 1)** muestra que la falta de motivación (25,5 %), la distracción por celular o redes sociales (19,4 %) y el olvido de lo planificado (18,4 %) acumulan el 63,3 % de los casos; añadiendo "no saber por dónde empezar" (14,3 %) se alcanza el 77,6 %. El 64,3 % expresó bastante o mucho interés en la solución propuesta (media = 3,74/5) y el 75,5 % usaría un asistente de IA en español.
  - **Encuesta 2 (Microsoft Forms, 8 abr.–6 may. 2026, $n = 31$):** Complementó el diagnóstico con ítems de la EPA: el 77,4 % no usa ninguna app de organización, el 87,1 % utilizaría un asistente de IA en español y el 90,3 % mostró disposición para ser beta-tester.
  - **Síntesis:** Los hallazgos confirman una brecha entre la alta prevalencia del problema y la baja adopción de herramientas digitales, agravada por la ausencia de soluciones adaptadas al contexto académico peruano.

### 2. Marco Conceptual y Antecedentes
- **Marco Conceptual:** La procrastinación académica emerge cuando el estudiante carece de recursos metacognitivos para planificar, monitorear y evaluar su propio proceso de estudio [2][3]. La gamificación (XP, niveles, rachas, insignias y rankings) incrementa la motivación intrínseca; su efectividad depende críticamente de la personalización, pues las soluciones uniformes resultan consistentemente menos eficaces [4][8]. La IA conversacional aplicada al aprendizaje, cuando opera con guardarrieles cognitivo-conductuales, produce mejoras significativas en gestión del tiempo y procrastinación respecto a aplicaciones sin componente de IA [5]. La telemetría conductual constituye una fuente de datos válida y complementaria a los instrumentos psicológicos tradicionales [3][7].
- **Antecedentes:**
  1. *Prevalencia e intervenciones:* Badajoz-Ramos y Jaime Flores [1] cuantificaron el problema en Perú. Salguero-Pazos y Reyes-de-Cózar [2] sistematizaron 32 intervenciones empíricas (autorregulación 90,6 %, autoeficacia 28,1 %, motivación 21,8 %).
  2. *Gamificación y hábitos:* Kirchner-Krath et al. [4] (misiones y narrativa reducen procrastinación en proyectos a largo plazo); Pereira, Classe y Siqueira [8] (revisión de 24 estudios: personalización gamificada como clave); Baars et al. [3] (validación de telemetría como dato científico); Husni et al. [6] (combinación Pomodoro–tareas en 100 estudiantes); Murad y Collins [7] (autoseguimiento sin gamificación ni IA es insuficiente).
  3. *IA conversacional:* Lee et al. [5] (RCT $n = 85$: chatbot con guardarrieles cognitivo-conductuales mejora gestión del tiempo y reduce procrastinación irracional).
  - *Vacío:* Ningún estudio previo integra autorregulación, gamificación personalizada e IA conversacional con guardarrieles en una sola plataforma adaptada a Latinoamérica. Epycus atiende esta brecha.

### 3. Variables de Investigación
- **Variable Independiente:** Uso del sistema Epycus.
- **Variable Dependiente:** Nivel de procrastinación académica (medido con la Escala de Procrastinación Académica - EPA, 8 ítems seleccionados, pre/postest).
- **Variables de Control:** Carrera profesional, ciclo académico, tiempo de uso registrado por telemetría y nivel de participación en el módulo Ranking.

### 4. Objetivos
- **Objetivo General:** Determinar el efecto de Epycus sobre la procrastinación académica en estudiantes de educación superior de Cajamarca durante una intervención de 40 días.
- **Objetivos Específicos:**
  1. Comparar los puntajes de la escala EPA entre el pretest y el postest.
  2. Analizar los patrones de comportamiento de estudio de los participantes mediante telemetría.
  3. Evaluar la usabilidad del sistema Epycus utilizando la Escala de Usabilidad del Sistema (SUS).
  4. Identificar la relación entre el uso de los módulos gamificados y la variación en los puntajes de la escala EPA.

### 5. Hipótesis
- **$H_0$:** El uso de Epycus durante 40 días no produce una diferencia estadísticamente significativa en los niveles de procrastinación académica (EPA) de los participantes.
- **$H_1$:** El uso de Epycus durante 40 días reduce significativamente los niveles de procrastinación académica, evidenciado por la disminución en los puntajes EPA entre el pretest y el postest.

### 6. Instrumentos Psicológicos y de Usabilidad
- **Escala de Procrastinación Académica (EPA - 8 ítems seleccionados para pre/postest):**
  - **Ítem 2:** Generalmente me preparo por adelantado para los exámenes.
  - **Ítem 5:** Cuando tengo problemas para entender algo, inmediatamente trato de buscar ayuda.
  - **Ítem 7:** Trato de completar el trabajo asignado lo más pronto posible.
  - **Ítem 10:** Constantemente intento mejorar mis hábitos de estudio.
  - **Ítem 11:** Invierto el tiempo necesario en estudiar aun cuando el tema sea aburrido.
  - **Ítem 12:** Trato de motivarme para mantener mi ritmo de estudio.
  - **Ítem 13:** Trato de terminar mis trabajos importantes con tiempo de sobra.
  - **Ítem 14:** Me tomo el tiempo de revisar mis tareas antes de entregarlas.
- **System Usability Scale (SUS - 10 ítems adaptados):**
  - **1:** Creo que me gustaría utilizar frecuentemente este sitio web.
  - **2:** Encontré el sitio web sencillo.
  - **3:** Pienso que el sitio web es fácil de usar.
  - **4:** Pienso que podré utilizar este sitio web sin el apoyo de personal técnico.
  - **5:** Encontré que varias de las funciones en el sitio web estaban bien integradas.
  - **6:** Pensé que había demasiada consistencia en el sitio web.
  - **7:** Me imagino que la mayoría de las personas podrían aprender a usar este sitio web muy rápido.
  - **8:** Encontré el sitio web muy intuitivo.
  - **9:** Me sentí muy confiado (seguro) al utilizar el sitio web.
  - **10:** Pude utilizar el sitio web sin tener que aprender nada nuevo.

---

## Reglas que no se rompen

1. **La telemetría es sagrada.** Todo evento definido en `docs/02-TELEMETRIA.md` debe registrarse siempre, sin excepciones y sin pérdida. Si un registro de telemetría falla, la acción del usuario **no** debe fallar, pero el fallo debe quedar logueado y ser recuperable.

2. **Durante los 40 días de intervención, producción se congela.** Ningún despliegue a `app.epycus.es` durante la ventana de intervención salvo corrección de un fallo que impida el uso. Todo desarrollo va a staging.

3. **Nada que dé ventaja competitiva pagada o desbalanceada.** La tienda es exclusivamente cosmética. No hay monetización en la versión de campo (decisión D-12 del proyecto).

4. **No agregues funcionalidades que no estén en `docs/01-MODULOS.md`.** Si crees que falta algo, proponlo en un comentario, no lo implementes.

5. **Toda escritura de datos de usuario pasa por validación de dominio**, no solo por Form Request.

6. **Datos personales:** nunca en logs, nunca en telemetría. La telemetría usa `user_id`, jamás nombre, correo ni WhatsApp. Ley peruana N.º 29733 y su reglamento DS 016-2024-JUS.

---

## Stack

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | Laravel | 12.x |
| Frontend | Vue 3 + Inertia.js | Vue 3.4+, Inertia 2.x |
| Build | Vite | 5.x |
| Estilos | TailwindCSS | 3.4+ |
| 3D (solo Fase 2) | three.js | última estable |
| Base de datos | MariaDB | 10.11+ |
| Servidor local | XAMPP | PHP 8.3 |
| Producción | Hostinger Premium (compartido) | PHP 8.3, LiteSpeed |

**PHP 8.3 en local y en producción. Misma versión, sin excepciones.**

---

## Restricciones del hosting que condicionan el código

El servidor es **hosting compartido**, no un VPS. Esto no es negociable y define varias decisiones de arquitectura:

| Restricción | Valor | Consecuencia en el código |
|---|---|---|
| WebSockets entrantes | **No permitidos** | El chat usa **polling AJAX**, no Reverb ni Pusher |
| Procesos demonio | No permitidos | Las colas corren por cron con `--stop-when-empty` |
| Núcleos de CPU | 1 | Nada pesado en horario de uso; reportes a las 3 AM |
| PHP workers | 40 | Suficiente, pero no desperdiciar requests |
| IOPS | 128 | Telemetría **en lotes**, nunca un INSERT por evento suelto |
| Inodos | 400.000 (151.585 ya usados) | Sesiones y cache en base de datos, no en archivos. Logs con rotación |
| RAM | 2 GB | — |
| Disco | 100 GB | Assets se alojan aquí, no en CDN externo |

Detalles completos en `docs/07-DEPLOY.md`.

---

## Orden de lectura de la documentación

| # | Documento | Qué contiene |
|---|---|---|
| 00 | [`docs/00-ARQUITECTURA.md`](docs/00-ARQUITECTURA.md) | Estructura de carpetas, capas, reglas de dependencia, convenciones de código |
| 01 | [`docs/01-MODULOS.md`](docs/01-MODULOS.md) | Cada módulo: entidades, contratos, casos de uso, eventos |
| 02 | [`docs/02-TELEMETRIA.md`](docs/02-TELEMETRIA.md) | Catálogo de eventos. **El más importante del proyecto** |
| 03 | [`docs/03-GAMIFICACION.md`](docs/03-GAMIFICACION.md) | XP, niveles, fases del avatar, rachas, villanos |
| 04 | [`docs/04-DISENO-VISUAL.md`](docs/04-DISENO-VISUAL.md) | Temas claro/oscuro, paletas, tokens, componentes |
| 05 | [`docs/05-BASE-DATOS.md`](docs/05-BASE-DATOS.md) | Esquema completo, índices, particionado |
| 06 | [`docs/06-SEGURIDAD.md`](docs/06-SEGURIDAD.md) | Security by design, autorización, protección de datos |
| 07 | [`docs/07-DEPLOY.md`](docs/07-DEPLOY.md) | Entornos, staging, despliegue, cron |
| — | [`.claude/skills/epycus-ui/`](.claude/skills/epycus-ui/SKILL.md) | **Skill de UI/UX.** Cárgala para cualquier trabajo visual: matemática del color, contraste, modos de superficie |
| 08 | [`docs/08-PROMPTS-MOCKUPS.md`](docs/08-PROMPTS-MOCKUPS.md) | Prompts para generar y comparar mockups. **Temporal**: se archiva al elegir estilo |
| 09 | [`docs/09-DECISIONES.md`](docs/09-DECISIONES.md) | **Por qué el sistema es como es.** Argumentación de cada decisión. Base de la defensa ante el jurado y de la sección 3.4 del artículo |
| 10 | [`docs/10-ESPECIFICACIONES-UI.md`](docs/10-ESPECIFICACIONES-UI.md) | **Medidas exactas.** Tamaños, espaciado, áreas táctiles, con su fundamento |
| 11 | [`docs/11-ESTANDAR-CODIGO.md`](docs/11-ESTANDAR-CODIGO.md) | **Estándar de código.** Reglas mecánicas, plantillas, prohibiciones |
| 12 | [`docs/12-HISTORIAL.md`](docs/12-HISTORIAL.md) | **Bitácora de sesiones de IA.** Qué se hizo, qué se decidió, cómo se verificó, qué queda pendiente — toda IA agrega una entrada antes de terminar |
| 13 | [`docs/13-ROADMAP.md`](docs/13-ROADMAP.md) | Hoja de ruta de desarrollo por fases. Antes de programar, mira qué fase está en curso |
| 14 | [`docs/14-HISTORIAS-USUARIO.md`](docs/14-HISTORIAS-USUARIO.md) | Historias de usuario + criterios de aceptación de cada módulo pendiente (Fase 6 en adelante) |
| 15 | [`docs/15-CATALOGO-IMAGENES.md`](docs/15-CATALOGO-IMAGENES.md) | Convención de nombres e inventario de avatares e imágenes de marca |

**Contexto del proyecto de investigación (fuera de este repositorio):** la carpeta de Drive del equipo (`G:\Mi unidad\Epycus\Equipo` en las máquinas del equipo) tiene `CONTEXTO-IA.md`, pensado para pegarse al inicio de una conversación con cualquier IA cuando la tarea es sobre el paper (metodología, ISO, muestra, cronograma), no sobre este código. Si una IA necesita ese contexto y tiene acceso a la carpeta, ese es el archivo.

---

## Alcance: qué se construye y qué no

### MVP — debe estar funcionando y validado antes del 07/09/2026

| Módulo | Estado |
|---|---|
| Autenticación y perfil | Confirmado |
| Hábitos | Confirmado |
| Pomodoro | Confirmado |
| Misiones (tareas y subtareas) | Confirmado |
| Avatar personalizable (Open Peeps) + Progresión por marcos, credencial holográfica y rangos por carrera (50 niveles) | Confirmado |
| **Telemetría** | Confirmado — crítico |
| Asistente de IA (DeepSeek) | Confirmado |
| Diario de bienestar | Confirmado |
| Villanos semanales | Confirmado |
| Sesiones de estudio grupal + chat | Confirmado (versión básica) |
| Ranking | Confirmado (básico, **instrumentado como variable de control**) |
| Fondos de pantalla seleccionables (`Fondo_1`..`Fondo_N`) | Confirmado |
| Calendario peruano (feriados y período académico) | Confirmado |
| Frases motivacionales al iniciar sesión (10 iniciales) | Confirmado |
| Consejos de uso por módulo | Confirmado |
| Tema claro (Kawaii) + tema oscuro (Solo Leveling) | Confirmado |
| Modos de superficie: neumorfismo y vidrio | Confirmado |
| Logros e insignias | Confirmado |
| Panel de administración + exportación | Confirmado |

### Fase 2 — después de la intervención, o si sobra tiempo

| Módulo | Nota |
|---|---|
| Tienda interna cosmética | Incluye three.js y objetos 3D hechos en Blender |

**No implementes nada de Fase 2 sin confirmación escrita del equipo.**

---

## Por qué el ranking está instrumentado

El ranking introduce comparación social, que es un mecanismo psicológico **distinto** al de la identidad profesional que investiga el estudio. Para poder controlarlo estadísticamente, cada consulta al ranking se registra como evento de telemetría (`ranking.viewed`).

Esto significa que **el ranking no puede mostrarse de forma pasiva** (por ejemplo, un widget siempre visible en el dashboard). Debe ser una vista a la que el usuario entra deliberadamente, para que la telemetría capture una decisión real y no una exposición accidental. Ver `docs/02-TELEMETRIA.md`.

---

## Entorno de desarrollo local

```bash
# Requisitos: XAMPP con PHP 8.3, Composer 2.x, Node 20+

composer install
npm install
cp .env.example .env
php artisan key:generate

# Crear base de datos 'epycus' en phpMyAdmin (utf8mb4_unicode_ci)
php artisan migrate --seed

npm run dev          # desarrollo con hot reload
php artisan serve    # o usar el vhost de XAMPP
```

**Nunca subas `node_modules/` al servidor.** El build se hace en local con `npm run build` y solo se sube `public/build`.

---

## Glosario del proyecto

| Término | Significado |
|---|---|
| **Fase (del avatar)** | Uno de los 10 escalones visuales. Cada fase abarca 5 niveles |
| **Nivel** | 1 a 50. El usuario sube de nivel con XP |
| **Estilo** | Uno de los 5 grupos visuales de carrera (Técnica, ciencias de la salud, Derecho, Arquitectura, Negocios) |
| **Outfit** | El conjunto de ropa de una fase concreta, en 4 posiciones |
| **Estilo Funko** | Proporción visual del avatar: cabeza grande, ojos negros redondos, cuerpo pequeño |
| **Racha** | Días consecutivos cumpliendo el mínimo diario |
| **Villano** | Obstáculo semanal temático que se debilita al cumplir hábitos |
| **Participante** | Usuario que forma parte de la intervención de 66 días |
| **Telemetría** | Registro de comportamiento real de uso, evidencia del estudio |
