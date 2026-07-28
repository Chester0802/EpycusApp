---
name: epycus-ui
description: Diseño de interfaz de Epycus. Úsala SIEMPRE que trabajes en algo visual — componentes Vue, estilos Tailwind, paletas de color, temas claro/oscuro, modos de superficie (neumorfismo/vidrio), layouts, animaciones o accesibilidad. También cuando tengas que crear una paleta nueva, verificar contraste, o decidir cómo se ve algo. Contiene la matemática del color del proyecto (OKLCH), las reglas de los dos ejes visuales y los umbrales que no se pueden romper.
---

# Diseño visual de Epycus

Guía operativa para construir interfaz en este proyecto. No es teoría general: son las reglas concretas de Epycus, con los números que hay que respetar.

---

## 1. Antes de escribir una línea de estilo

Tres preguntas que hay que poder responder:

1. **¿En qué modo de superficie estoy?** Neumorfismo o vidrio. Cambian las reglas.
2. **¿Funciona en los dos temas?** Todo componente debe verse bien en claro y en oscuro.
3. **¿Cuál es el ratio de contraste?** No se estima a ojo. Se calcula.

Si no puedes responder las tres, no escribas el componente todavía.

---

## 2. El sistema: dos ejes independientes

Epycus tiene **dos ejes visuales que el usuario elige por separado**:

```
EJE A — Superficie          EJE B — Tema de color
├── Neumorfismo            ├── Claro  (Kawaii)
└── Vidrio                 └── Oscuro (Solo Leveling)
```

Cuatro combinaciones, todas deben funcionar:

| | Claro (Kawaii) | Oscuro (Solo Leveling) |
|---|---|---|
| **Neumorfismo** | Suave, algodonado, sin fondo | Sobrio, mate, sin fondo |
| **Vidrio** | Pastel translúcido sobre fondo | **El más característico**: ventanas del sistema |

### La regla que amarra los dos ejes

**El modo de superficie decide si hay fondo de pantalla. No es preferencia, es requisito técnico.**

| Modo | Fondo de pantalla | Por qué |
|---|---|---|
| **Neumorfismo** | **Desactivado, forzado** | El efecto necesita que fondo y elementos compartan el mismo color base para que las dobles sombras funcionen. Con una imagen detrás, el efecto desaparece |
| **Vidrio** | **Obligatorio** | `backdrop-filter` necesita algo que difuminar. Sin imagen detrás se ve como una tarjeta translúcida sin sentido |

Al cambiar de modo, la interfaz se reconfigura sola: elegir neumorfismo oculta el selector de fondos; elegir vidrio lo habilita y aplica uno por defecto.

---

## 3. Matemática del color: OKLCH

**Trabaja siempre en OKLCH, no en HSL ni en hex.** Los hex son solo el resultado final.

### Por qué

En HSL, dos colores con la misma `L` **no** se ven igual de brillantes. Un amarillo HSL al 50% de luminosidad se ve mucho más claro que un azul HSL al 50%. Eso hace imposible generar escalas coherentes o predecir contraste.

OKLCH es perceptualmente uniforme: **misma `L` = mismo brillo percibido, sin importar el matiz**. Consecuencia práctica: si verificas el contraste de un color, otro con la misma `L` en distinto matiz se comportará casi igual. Eso es lo que hace viable que el usuario personalice paletas sin romper la accesibilidad.

Soportado nativamente en CSS desde 2023, en todos los navegadores modernos.

```css
/* oklch(Lightness Chroma Hue) */
--color-primary: oklch(0.72 0.14 350);
/*                     │    │    └── matiz 0-360 */
/*                     │    └─────── croma (saturación) 0-0.4 aprox */
/*                     └──────────── luminosidad 0-1 */
```

### Generar una escala de tonos

Fija matiz y croma, varía solo la luminosidad en pasos iguales:

```css
--rose-50:  oklch(0.97 0.02 350);
--rose-100: oklch(0.94 0.04 350);
--rose-200: oklch(0.89 0.06 350);
--rose-300: oklch(0.82 0.09 350);
--rose-400: oklch(0.75 0.12 350);
--rose-500: oklch(0.68 0.15 350);   /* base */
--rose-600: oklch(0.60 0.16 350);
--rose-700: oklch(0.52 0.15 350);
--rose-800: oklch(0.42 0.12 350);
--rose-900: oklch(0.32 0.09 350);
```

Nota que el croma **sube hasta el 500 y luego baja**. Los colores muy claros y muy oscuros no admiten croma alto sin salirse del gamut. Es la curva natural.

### Rangos por familia visual

| Familia | Luminosidad | Croma | Resultado |
|---|---|---|---|
| Pastel kawaii | 0.86 – 0.94 | 0.04 – 0.08 | Suave, algodonado |
| Acento sólido | 0.55 – 0.70 | 0.14 – 0.20 | Vibrante, para botones |
| Texto sobre claro | 0.25 – 0.35 | 0.02 – 0.05 | Oscuro con matiz |
| Fondo oscuro | 0.14 – 0.22 | 0.01 – 0.03 | Casi negro, con matiz |
| Superficie oscura | 0.22 – 0.30 | 0.02 – 0.04 | Elevación perceptible |
| Neón sobre oscuro | 0.70 – 0.82 | 0.12 – 0.18 | Brillante sin quemar |

### Armonía por matiz

Desde un matiz base `H`:

| Relación | Cálculo | Uso |
|---|---|---|
| Análogo | `H ± 30` | Acento secundario que acompaña |
| Triádico | `H ± 120` | Categorías distinguibles |
| Complementario | `H + 180` | Contraste fuerte, usar poco |
| Complementario dividido | `H + 150`, `H + 210` | Más suave que el complementario puro |

**En este proyecto usa análogos.** Los complementarios puros generan vibración óptica, que en 66 días de uso diario cansa.

---

## 4. Contraste: los números, no la intuición

### Umbrales obligatorios

| Elemento | Mínimo | Nivel |
|---|---|---|
| Texto normal (<18px o <14px negrita) | **4.5:1** | AA |
| Texto grande (≥18px o ≥14px negrita) | **3:1** | AA |
| Elementos de interfaz (bordes, iconos, casillas) | **3:1** | AA |
| Anillo de foco | **3:1** contra el fondo adyacente | AA |
| Texto deshabilitado | Sin mínimo, pero legible | — |

### Cómo calcularlo

```python
def luminancia(hex_color):
    h = hex_color.lstrip('#')
    r, g, b = [int(h[i:i+2], 16) / 255 for i in (0, 2, 4)]
    f = lambda c: c/12.92 if c <= 0.03928 else ((c + 0.055)/1.055) ** 2.4
    return 0.2126*f(r) + 0.7152*f(g) + 0.0722*f(b)

def contraste(a, b):
    l1, l2 = sorted([luminancia(a), luminancia(b)], reverse=True)
    return (l1 + 0.05) / (l2 + 0.05)
```

**Calcula siempre antes de dar por buena una combinación.** No estimes.

### Las tres trampas de este proyecto

**Trampa 1 — Blanco sobre pastel.** Falla siempre. Medido en las paletas de Epycus: 1.73 a 2.42, cuando el mínimo es 4.5.

```html
<!-- MAL: 2.42:1 -->
<button class="bg-primary text-white">Completar</button>
<!-- BIEN: 7.77:1 -->
<button class="bg-primary text-content-primary">Completar</button>
```

Sobre pastel va texto oscuro. El blanco solo va sobre el acento sólido (variante `-strong`).

**Trampa 2 — Neón sobre oscuro para botones.** Un cyan `#4FC3F7` da 9.61 contra el fondo oscuro (excelente para texto), pero blanco encima de ese cyan da 2.0 (falla). En botones de acento neón, el texto va en el color del fondo oscuro.

**Trampa 3 — Texto sobre vidrio.** El contraste depende de la imagen que haya detrás, que cambia. Solución: la capa de vidrio nunca baja del 85% de opacidad efectiva del color de superficie. Con eso el contraste queda garantizado (~12.9:1 medido) sin importar el fondo.

**Trampa 4 — `--color-border` no alcanza 3:1.** Los tokens `border`/`border-strong` de las dos paletas (§7) dan entre 1.25:1 y 2.21:1 contra `bg` — fallan el mínimo de elementos de interfaz, incluida la compensación obligatoria de neumorfismo (§5, "borde de 1px... con contraste ≥3:1"). Usa `--color-border-interactive` (agregado en §7) para el borde de cualquier elemento interactivo — `border`/`border-strong` quedan solo para separadores decorativos donde el contraste no es crítico. Encontrado midiendo, no asumiendo: es exactamente el tipo de error que esta sección pide no cometer.

---

## 5. Reglas del modo Neumorfismo

El efecto se logra con **doble sombra**: una clara arriba-izquierda y una oscura abajo-derecha, sobre un fondo del mismo color.

```css
[data-surface="neu"] {
  --neu-light: /* base aclarada ~8% */;
  --neu-dark:  /* base oscurecida ~12% */;
  --neu-distance: 6px;
  --neu-blur: 12px;
}

.neu-raised {
  background: var(--color-surface);
  box-shadow:
    calc(var(--neu-distance) * -1) calc(var(--neu-distance) * -1)
      var(--neu-blur) var(--neu-light),
    var(--neu-distance) var(--neu-distance)
      var(--neu-blur) var(--neu-dark);
}

.neu-pressed {
  box-shadow:
    inset calc(var(--neu-distance) * -1) calc(var(--neu-distance) * -1)
      var(--neu-blur) var(--neu-light),
    inset var(--neu-distance) var(--neu-distance)
      var(--neu-blur) var(--neu-dark);
}
```

### Compensaciones obligatorias

El neumorfismo distingue elementos por sombra, no por contraste de color. Por naturaleza incumple el mínimo de 3:1 para elementos de interfaz. **En Epycus no se acepta sin estas tres compensaciones:**

1. **Borde de 1px** en todo elemento interactivo, con contraste ≥3:1 contra la superficie
2. **Anillo de foco visible** de 2px con offset, en color de acento
3. **Estado activo diferenciado por color**, no solo por sombra invertida

Sin esto, a plena luz del día en un celular los bordes desaparecen y la app deja de ser usable.

### Prohibido en neumorfismo

- Fondo de pantalla (rompe el efecto)
- Texto sobre elemento hundido sin aumentar peso tipográfico
- Más de 2 niveles de elevación (se vuelve ilegible)

---

## 6. Reglas del modo Vidrio

```css
[data-surface="glass"] .glass-card {
  background: color-mix(in oklch, var(--color-surface) 85%, transparent);
  backdrop-filter: blur(12px) saturate(1.2);
  -webkit-backdrop-filter: blur(12px) saturate(1.2);
  border: 1px solid color-mix(in oklch, var(--color-border) 60%, transparent);
  box-shadow: var(--shadow-md);
}
```

### Límites de rendimiento — críticos en este proyecto

`backdrop-filter` es intensivo en GPU. Cada elemento con vidrio dispara su propio cálculo de desenfoque. Los datos:

| Cantidad de elementos con vidrio | Efecto |
|---|---|
| 3 – 5 | Impacto despreciable en dispositivos modernos |
| 10 o más | Lag perceptible en móviles de gama media |

**Máximo 4 elementos con vidrio por pantalla.** Esto importa especialmente aquí: los participantes son estudiantes peruanos y el riesgo R-12 del proyecto es justamente que la app no funcione en gama baja de Android. Un dashboard con 12 tarjetas de vidrio sería inutilizable para parte de la muestra, y eso sesga los datos del estudio.

Otras reglas:

- **Desenfoque máximo 16px.** Por encima de 20px hay caídas de frames en móvil
- **Nunca animar `backdrop-filter`.** Es carísimo. Anima opacidad o transformación
- **Opacidad efectiva mínima 85%** para garantizar contraste sin importar el fondo
- **Degradación automática:** si el dispositivo es de gama baja, sustituir por fondo sólido

```js
// Detección de capacidad — aplicar al montar la app
const lowEnd =
  navigator.hardwareConcurrency <= 4 ||
  navigator.deviceMemory <= 4 ||
  !CSS.supports('backdrop-filter', 'blur(1px)')

if (lowEnd) document.documentElement.dataset.glassFallback = 'true'
```

```css
[data-glass-fallback="true"] .glass-card {
  backdrop-filter: none;
  background: var(--color-surface);   /* sólido, sin desenfoque */
}
```

### Prohibido en vidrio

- Vidrio sobre vidrio (el desenfoque se acumula y se vuelve papilla)
- Texto directamente sobre el desenfoque sin capa de opacidad
- Fondos de pantalla con mucho detalle o alto contraste detrás de texto

---

## 7. Las dos paletas base

Ambas verificadas. Los ratios son medidos, no estimados.

### Claro — Kawaii

Fundamento: los pastel se caracterizan por **luminosidad alta y saturación baja**. El rango de referencia para UI pastel es saturación 14–21% y brillo 89–96%.

```css
[data-theme="light"] {
  --color-bg:              #FFF8FB;   /* oklch(0.98 0.01 340) */
  --color-surface:         #FFFFFF;
  --color-surface-raised:  #FDF0F6;
  --color-surface-sunken:  #F9E8F1;

  --color-content-primary:   #3D2C3A;  /* 12.39:1 sobre bg  AAA */
  --color-content-secondary: #6B5A67;  /*  6.11:1 sobre bg  AA  */
  --color-content-muted:     #9A8B96;

  --color-primary:        #F2B8D4;   /* pastel. Texto oscuro encima: 7.77:1 */
  --color-primary-strong: #C4477E;   /* sólido. Blanco encima: 4.62:1 */
  --color-secondary:      #C9B8F2;   /* lavanda. Texto oscuro: 7.17:1 */
  --color-accent:         #B8EDDC;   /* menta.   Texto oscuro: 9.99:1 */

  --color-success: #7BC49A;
  --color-warning: #F7D9A0;
  --color-danger:  #E58B8B;

  --color-border:        #F0DCE6;
  --color-border-strong: #DCC0D2;
  /* Ni border ni border-strong llegan al mínimo de 3:1 para elementos de
     interfaz (medido: 1.25:1 y 1.60:1 contra bg, ambos fallan §4). Usar
     este token en el borde de todo elemento interactivo (compensación
     obligatoria del neumorfismo, §5) — medido: 3.78:1. */
  --color-border-interactive: #B06A90;
}
```

### Oscuro — Solo Leveling

Inspirada en el aspecto del anime: azul neón, violeta y carmesí sobre negro azulado, con ventanas del sistema translúcidas. **Es una interpretación del estilo, no una paleta oficial de la obra.**

```css
[data-theme="dark"] {
  --color-bg:              #0A0E1A;   /* azul casi negro */
  --color-surface:         #131A2B;   /* elevación 1.11 */
  --color-surface-raised:  #1C2540;   /* elevación 1.15 */
  --color-surface-sunken:  #060911;

  --color-content-primary:   #E8EDF7;  /* 16.40:1  AAA */
  --color-content-secondary: #A8B4CC;  /*  9.23:1  AAA */
  --color-content-muted:     #6B7899;

  --color-primary:        #4FC3F7;   /* cyan sistema.  9.61:1  AAA */
  --color-primary-strong: #4FC3F7;   /* botón: texto en --color-bg encima */
  --color-secondary:      #9D7BEA;   /* violeta sombra. 5.92:1  AA */
  --color-accent:         #F5C85C;   /* dorado.        12.19:1  AAA */

  --color-success: #5CD9A6;
  --color-warning: #F5C85C;
  --color-danger:  #F2555A;          /* carmesí.        5.71:1  AA */

  --color-border:        #253150;
  --color-border-strong: #3A4A73;
  /* Mismo problema que en claro: border-strong da 2.21:1 contra bg,
     todavía bajo el mínimo de 3:1. Medido: 4.75:1. */
  --color-border-interactive: #5A7EC0;
}
```

**Detalle de estilo:** en modo oscuro + vidrio, los bordes de las tarjetas llevan un halo cyan sutil que evoca las ventanas del sistema del anime. Un `box-shadow` de 1px con el cyan al 30%, nada más. Discreto: es un detalle, no un neón parpadeante.

### Bosque (segunda paleta, agregada 2026-07-28)

Matiz teal/esmeralda (H≈175), derivada con la fórmula de §8 y verificada con conversión
OKLCH→sRGB real (no coloreada a ojo). Selector en `/settings`.

```css
[data-palette="bosque"][data-theme="light"] {
  --color-bg:              #F1FBF8;
  --color-surface:         #FFFFFF;
  --color-content-primary:   #19342D;  /* 12.67:1 sobre bg  AAA */
  --color-content-secondary: #4E6A62;  /*  5.59:1 sobre bg  AA  */
  --color-primary:        #82D8C1;   /* pastel. Texto oscuro encima: 7.99:1 */
  --color-primary-strong: #008164;   /* sólido. Blanco encima: 4.86:1 */
  --color-secondary:      #83D4DD;
  --color-accent:         #A5E0A5;
  --color-danger:         #FD736D;   /* fondo, no texto — falla 2.53:1 como texto */
  --color-danger-text:    #B71824;   /* 6.28:1 sobre bg */
  --color-border-interactive: #3A927D;  /* 3.56:1 */
}

[data-palette="bosque"][data-theme="dark"] {
  --color-bg:              #081511;
  --color-content-primary:   #DEEBE7;  /* 15.23:1  AAA */
  --color-primary:        #28D4B2;   /* teal neón. 9.91:1 sobre bg. Blanco encima falla (1.88:1) */
  --color-secondary:      #2CC3D2;
  --color-accent:         #7BD77F;
  --color-danger:         #F3625D;   /* sí sirve como texto en oscuro: 5.97:1 */
  --color-border-interactive: #446D62;  /* 3.21:1 */
}
```

Valores completos (todos los tokens) en `resources/css/app.css`.

---

## 8. Crear paletas nuevas (personalización futura)

El usuario podrá elegir entre varias paletas de cada tema. Para crear una nueva:

**Paso 1 — Elige el matiz base.** Un solo número, 0 a 360.

**Paso 2 — Deriva los tokens con luminosidad fija.** Aquí es donde OKLCH paga: como la luminosidad es perceptualmente uniforme, si respetas estos valores el contraste se mantiene sea cual sea el matiz.

| Token | Tema claro | Tema oscuro |
|---|---|---|
| `bg` | `oklch(0.98 0.01 H)` | `oklch(0.16 0.02 H)` |
| `surface` | `oklch(1.00 0 0)` | `oklch(0.22 0.03 H)` |
| `surface-raised` | `oklch(0.96 0.02 H)` | `oklch(0.27 0.04 H)` |
| `content-primary` | `oklch(0.32 0.03 H)` | `oklch(0.94 0.01 H)` |
| `content-secondary` | `oklch(0.52 0.03 H)` | `oklch(0.75 0.02 H)` |
| `primary` (pastel/neón) | `oklch(0.84 0.08 H)` | `oklch(0.76 0.14 H)` |
| `primary-strong` | `oklch(0.55 0.17 H)` | `oklch(0.70 0.16 H)` |
| `border` | `oklch(0.92 0.03 H)` | `oklch(0.32 0.04 H)` |

**Paso 3 — Verifica.** Calcula el contraste de `content-primary` sobre `bg` y sobre `surface`, y de blanco sobre `primary-strong`. Si alguno falla, ajusta la luminosidad, nunca el croma.

**Paso 4 — Comprueba la elevación en oscuro.** El ratio entre `bg` y `surface` debe estar entre 1.10 y 1.20. Menos de 1.10 no se percibe; más de 1.25 se ve como parche.

---

## 9. Tipografía y ritmo

| Rol | Fuente | Peso |
|---|---|---|
| Títulos | Quicksand | 600 |
| Cuerpo | Nunito | 400 / 600 |
| Números y temporizador | Nunito | 700 |

Autoalojadas en `public/fonts/`. **Nunca desde el CDN de Google**: registraría la IP de los participantes, que es un tratamiento de datos no declarado en el consentimiento informado.

Escala tipográfica: 12 / 14 / **16** / 18 / 20 / 24 / 30 / 36. Cuerpo mínimo 16px, sin excepciones.

Espaciado: múltiplos de 4. Solo `4, 8, 12, 16, 24, 32, 48, 64`.

Radios: 8 (chips) / 12 (botones, campos) / 16 (tarjetas) / 24 (modales) / full (avatares).

---

## 10. Movimiento

| Interacción | Duración |
|---|---|
| Hover | 150 ms |
| Modal | 200 ms |
| Cambio de página | 250 ms |
| Rotación del avatar | 400 ms de fundido |
| Subida de nivel | 800 ms |

Respetar `prefers-reduced-motion` siempre. No es opcional: hay personas con sensibilidad vestibular y esto se usa a diario durante 66 días.

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

Con movimiento reducido, la rotación de las 4 imágenes del avatar se detiene y queda fija en `frontal`.

---

## 11. Antes de dar por terminado un componente

- [ ] Usa tokens semánticos, ningún color literal
- [ ] Se ve bien en claro y en oscuro
- [ ] Se ve bien en neumorfismo y en vidrio
- [ ] Contraste calculado y anotado, no estimado
- [ ] Área táctil ≥44×44px
- [ ] Foco visible al navegar con teclado
- [ ] Estados: normal, hover, activo, deshabilitado, foco, error
- [ ] Funciona a 375px y a 1440px, con composición propia en cada uno
- [ ] Si usa vidrio: no supera el máximo de 4 por pantalla
- [ ] Si usa neumorfismo: tiene borde y anillo de foco
- [ ] Respeta `prefers-reduced-motion`
- [ ] Textos en español, tono cercano sin infantilizar

---

## 12. Errores frecuentes

| Error | Corrección |
|---|---|
| `bg-pink-200` en un componente | Usa `bg-primary`, siempre token |
| Blanco sobre acento pastel | Texto oscuro sobre pastel; blanco solo sobre `-strong` |
| Fondo de pantalla con neumorfismo | Incompatible. El modo lo desactiva |
| 12 tarjetas de vidrio en el dashboard | Máximo 4 por pantalla |
| Animar `backdrop-filter` | Anima opacidad o transformación |
| Escritorio = móvil estirado | El escritorio necesita composición propia |
| Contraste "se ve bien" | Se calcula, no se estima |
| Complementarios puros en gran superficie | Vibración óptica. Usa análogos |
| Elevación oscura de 1.03 | No se percibe. Rango 1.10–1.20 |
