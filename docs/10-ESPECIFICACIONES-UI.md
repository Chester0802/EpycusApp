\
# 10 — Especificaciones de interfaz

> Medidas exactas, con su fundamento. **Si una IA necesita saber cuánto mide algo, la respuesta está aquí.** No inventes valores: si algo no está especificado, pregunta antes de decidir.
>
> Complementa la skill `epycus-ui`, que cubre color y matemática cromática. Este documento cubre tamaño, espacio y ritmo.

---

## 1. Los tres sistemas numéricos

Todo en la interfaz sale de tres progresiones. Nada de números arbitrarios.

| Sistema | Base | Progresión | Para qué |
|---|---|---|---|
| **Espaciado** | 4 px | 4, 8, 12, 16, 24, 32, 48, 64 | Márgenes, rellenos, separaciones |
| **Tipografía** | 16 px | ×1.25 (tercera mayor) | Tamaños de texto |
| **Radios** | 4 px | 8, 12, 16, 24, full | Esquinas |

### Por qué base 4 y no 5 o 10

La rejilla de 8 puntos con base de 4 es el estándar de facto en interfaz de producto desde que Material Design la sistematizó. Razón práctica: las pantallas tienen densidades de 1×, 1.5×, 2×, 3×. Un valor de 4 px escala a enteros en todas (4, 6, 8, 12). Un valor de 5 px produce 7.5 px en 1.5×, y el navegador redondea de forma inconsistente entre dispositivos. Con base 4 nunca hay medios píxeles.

### Por qué ×1.25 en tipografía

Las escalas modulares derivan de intervalos musicales. La **tercera mayor (1.25)** y la cuarta justa (1.333) son las más usadas en web. Se eligió 1.25 porque genera pasos suficientemente distintos para dar jerarquía, pero no tan separados como para que falten tamaños intermedios en pantallas pequeñas.

```
16 ÷ 1.25 = 12.8 → 12    (redondeado a la rejilla de 4)
16 ÷ 1.25 ≈ 14           (paso intermedio, para etiquetas densas)
16                        BASE
18                        (paso intermedio, subtítulos y texto destacado)
16 × 1.25 = 20
20 × 1.25 = 25 → 24      (redondeado a la rejilla)
24 × 1.25 = 30
30 × 1.25 = 37.5 → 36
```

Se redondea a múltiplos de 4 para que la tipografía caiga sobre la misma rejilla que el espaciado, salvo el paso de 18 (intermedio, tomado tal cual de la escala de la skill `epycus-ui` §9). Sin ese redondeo, el ritmo vertical se descuadra.

**Los números de gran formato (temporizador Pomodoro, XP) no forman parte de esta escala de lectura**: son cifras de display, no texto corrido, y usan el tamaño que fija su propia especificación (§6), no un token `text-*`.

---

## 2. Escala tipográfica

| Token | px | Interlineado | Peso | Uso |
|---|---|---|---|---|
| `text-xs` | 12 | 16 | 400 | Metadatos, marcas de tiempo. **Nunca para leer** |
| `text-sm` | 14 | 20 | 400 | Etiquetas, texto secundario |
| `text-base` | **16** | 24 | 400 | **Cuerpo. Mínimo absoluto para leer** |
| `text-md` | 18 | 26 | 500 | Subtítulo, texto destacado dentro de párrafo |
| `text-lg` | 20 | 28 | 600 | Texto destacado, título de tarjeta |
| `text-xl` | 24 | 32 | 600 | Título de sección |
| `text-2xl` | 30 | 36 | 600 | Título de página |
| `text-3xl` | 36 | 40 | 700 | Números grandes, XP |

**Regla del interlineado:** cuerpo a 1.5×, títulos a 1.2×. El texto pequeño necesita más aire relativo; el grande, menos.

**Prohibido bajar de 16 px en texto corrido.** Los participantes usan esto a diario durante 66 días; la fatiga visual es un problema real de adherencia.

**Longitud de línea:** entre 45 y 75 caracteres. En escritorio esto obliga a limitar el ancho del contenido con `max-width: 65ch`, no a estirarlo a toda la pantalla.

---

## 3. Áreas táctiles

Aquí hay tres estándares distintos y conviene saber cuál se aplica.

| Estándar | Mínimo | Naturaleza |
|---|---|---|
| WCAG 2.2 (SC 2.5.8, AA) | 24×24 px | Requisito de accesibilidad |
| Apple HIG | 44×44 pt | Recomendación de plataforma |
| Material Design | 48×48 dp | Recomendación de plataforma |

**Epycus adopta 44×44 px como mínimo**, el estándar de Apple HIG, alineado con el checklist de la skill `epycus-ui` (§11: "Área táctil ≥44×44px"). Está por encima del mínimo de accesibilidad WCAG (24×24) y es compatible con Material Design (48×48): nada impide que un componente concreto use 48 o más cuando el layout lo permite, el mínimo solo fija el piso, no el techo. Ver la tabla de medidas por componente más abajo para los tamaños reales usados en cada caso.

### El dato que suele ignorarse

> **La separación entre elementos afecta más a la tasa de error que el tamaño**, una vez superados los ~40 px.

Esto cambia la prioridad de diseño: antes de agrandar un botón, sepáralo de sus vecinos.

**Separación mínima entre elementos táctiles: 8 px.** En listas de acciones destructivas junto a acciones frecuentes (por ejemplo eliminar junto a completar), **16 px**.

### Tabla de medidas por componente

| Componente | Alto | Ancho mínimo | Relleno horizontal |
|---|---|---|---|
| Botón primario | 48 | 120 | 24 |
| Botón secundario | 48 | 100 | 20 |
| Botón pequeño | 40 | 80 | 16 |
| Botón de icono | 48 | 48 | — |
| Botón flotante | 56 | 56 | — |
| Campo de texto | 48 | — | 16 |
| Área de texto | 96 mín. | — | 16 |
| Casilla de verificación | 24 visual, **48 táctil** | — | — |
| Interruptor | 32×52 visual, 48 táctil | — | — |
| Fila de lista | 56 mín. | — | 16 |
| Fila con dos líneas | 72 | — | 16 |
| Pestaña de navegación inferior | 56 | — | — |
| Celda de calendario | 44×44 mín. | — | — |

**Casilla de verificación:** el cuadro se ve de 24 px, pero el área que responde al toque es de 48. Se logra con relleno transparente. Es el caso donde más se equivoca la gente: un cuadro de 24 px sin área ampliada es inutilizable con el pulgar.

```css
.checkbox-wrapper {
  min-width: 48px;
  min-height: 48px;
  display: grid;
  place-items: center;   /* el cuadro de 24px queda centrado */
}
```

---

## 4. Espaciado

| Token | px | Uso |
|---|---|---|
| `space-1` | 4 | Entre icono y su texto |
| `space-2` | 8 | Separación mínima entre táctiles. Dentro de un grupo |
| `space-3` | 12 | Entre elementos relacionados |
| `space-4` | 16 | **Relleno estándar de tarjeta.** Entre tarjetas |
| `space-6` | 24 | Entre secciones dentro de una vista |
| `space-8` | 32 | Entre bloques mayores |
| `space-12` | 48 | Margen superior de página |
| `space-16` | 64 | Separación de secciones en escritorio |

### Ley de proximidad aplicada

Los elementos relacionados van más juntos que los no relacionados. Regla operativa: **la separación entre grupos debe ser al menos el doble que la separación dentro del grupo.**

```
Título de sección
  ↕ 12   (relacionado con lo de abajo)
Tarjeta 1
  ↕ 8    (elementos del mismo grupo)
Tarjeta 2
  ↕ 8
Tarjeta 3
  ↕ 32   (cambio de sección: 4× la separación interna)
Título de otra sección
```

Si esto no se respeta, el usuario no percibe la agrupación y la pantalla se lee como una lista plana.

---

## 5. Rejilla y contenedores

| Punto de corte | Ancho | Columnas | Margen lateral | Canaleta |
|---|---|---|---|---|
| Móvil | <640 | 4 | 16 | 16 |
| Tableta | 640–1023 | 8 | 24 | 24 |
| Escritorio | ≥1024 | 12 | 32 | 24 |

**Ancho máximo del contenido:** 1200 px. Por encima, se centra con márgenes automáticos.

**Barra lateral en escritorio:** 260 px fijos. Elegido para que quepa "Producción científica" (la etiqueta más larga) a 14 px sin cortar, con icono de 24 px y rellenos de 16.

**Columna derecha (solo ≥1280):** 300 px. Contiene apoyo, nunca acciones exclusivas.

```
Escritorio 1440px:
32 │ 260 │ 24 │────── contenido 780 ──────│ 24 │ 300 │ 32
   sidebar          max-width aplicado           aside
```

---

## 6. Especificaciones por pantalla clave

### Temporizador Pomodoro

| Elemento | Móvil | Escritorio |
|---|---|---|
| Diámetro del círculo | 240 | 320 |
| Grosor del anillo | 12 | 16 |
| Tamaño del número | 44 | 56 |
| Separación al texto de contexto | 16 | 24 |
| Botones de acción | 48 alto, ancho completo con 16 de margen | 48 alto, 160 ancho, en fila |

El anillo de progreso se anima con `stroke-dashoffset`, no redibujando. Circunferencia = `2πr`; para r=120 son 754 px de trazo.

### Celda de calendario (diario de ánimo)

| Elemento | Medida |
|---|---|
| Celda | 44×44 mín. en móvil, 56×56 en escritorio |
| Número del día | 14 px, arriba |
| Emoji de ánimo | 24×24 px, centrado abajo |
| Separación entre celdas | 4 |
| Indicador de hoy | Borde de 2 px en color primario |
| Indicador de feriado | Punto de 4 px en la esquina superior derecha |

Con 7 columnas de 44 px más 6 separaciones de 4 px son 332 px: cabe en 375 px con márgenes de 16. **Ese cálculo es el que fija el mínimo de 44** — no es una preferencia.

### Fila de hábito

```
┌──────────────────────────────────────────────┐  56 alto
│ [24]  Leer 20 páginas              [🔥12] [☐]│
│  ↑16   ↑12                          ↑12  ↑16 │
│ icono  título 16px            racha    casilla
└──────────────────────────────────────────────┘
```

El área táctil de la casilla es de 48×48 aunque el cuadro se vea de 24. La fila completa también responde al toque, salvo los 48 px de la derecha, que son de la casilla.

### Tarjeta del avatar

| Contexto | Tamaño del avatar |
|---|---|
| Miniatura de cabecera | 40×40 |
| Tarjeta del dashboard | 160×160 (móvil), 240×240 (escritorio) |
| Pantalla de perfil | 240×240 (móvil), 360×360 (escritorio) |
| Participante en sesión grupal | 32×32 |

Los PNG se sirven a 2× del tamaño de presentación para pantallas de alta densidad. El mayor se presenta a 360 px, así que el archivo es de 720×720.

---

## 7. Estados obligatorios

Todo elemento interactivo necesita **seis** estados. Si falta uno, el componente no está terminado.

| Estado | Señal visual | Regla |
|---|---|---|
| Normal | Base | — |
| Hover | Fondo más claro/oscuro 8% | Solo en dispositivos con puntero |
| Activo (presionado) | Escala 0.97 + fondo 12% | Duración 100 ms |
| Foco | Anillo de 2 px con 2 px de separación | **Contraste ≥3:1.** Obligatorio |
| Deshabilitado | Opacidad 0.5, cursor no permitido | Sin hover ni foco |
| Cargando | Indicador, texto oculto, ancho fijo | **Mantener el ancho** para que no salte la maquetación |

```css
.btn:focus-visible {
  outline: 2px solid var(--color-primary-strong);
  outline-offset: 2px;
}
```

Usa `:focus-visible`, no `:focus`. Así el anillo aparece al navegar con teclado pero no al hacer clic con el ratón.

---

## 8. Elevación

| Nivel | Uso | Tema claro | Tema oscuro |
|---|---|---|---|
| 0 | Fondo | ninguna | ninguna |
| 1 | Tarjeta | `0 1px 3px rgba(0,0,0,.06)` | superficie +5% luminosidad |
| 2 | Menú, tarjeta elevada | `0 4px 12px rgba(0,0,0,.08)` | superficie +8% |
| 3 | Modal | `0 8px 24px rgba(0,0,0,.12)` | superficie +12% |
| 4 | Botón flotante | `0 6px 16px rgba(0,0,0,.16)` | superficie +12% + sombra |

En tema oscuro la elevación se comunica **con luminosidad, no con sombra**: sobre fondo oscuro las sombras no se perciben.

En modo neumorfismo la elevación usa el sistema de doble sombra de la skill `epycus-ui` §5, no esta tabla.

---

## 9. Iconografía

| Contexto | Tamaño |
|---|---|
| En línea con texto | 16 |
| En botón | 20 |
| Navegación | 24 |
| Destacado en tarjeta | 32 |
| Estado vacío | 64 |

Grosor de trazo: **2 px** en todos los tamaños. Un icono de 16 con trazo de 1 px se ve anémico junto a texto de peso 400.

Todo icono decorativo lleva `aria-hidden="true"`. Todo icono que es la única señal de una acción lleva `aria-label`.

---

## 10. Verificación numérica antes de entregar

- [ ] Ningún valor fuera de las tres progresiones de la sección 1
- [ ] Todo elemento táctil ≥44×44 px
- [ ] Separación entre táctiles ≥8 px (≥16 si uno es destructivo)
- [ ] Ningún texto de lectura por debajo de 16 px
- [ ] Longitud de línea entre 45 y 75 caracteres
- [ ] Separación entre grupos ≥2× la separación interna
- [ ] Los seis estados presentes en cada interactivo
- [ ] Anillo de foco con contraste ≥3:1
- [ ] Funciona a 375 px sin desbordamiento horizontal
- [ ] Funciona a 1440 px sin estirarse más de 1200 px
