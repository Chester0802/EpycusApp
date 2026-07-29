# 15 — Catálogo de imágenes

> Inventario y convención de nombres de **todos** los assets visuales del proyecto: avatares de
> personaje (el bloque grande, con convención de nombre propia) y las imágenes sueltas de marca
> (logo, hero de login, fondo de pantalla). Si agregás una imagen nueva, este es el documento que
> hay que actualizar — junto con `App\Shared\Domain\Services\AvatarAssetResolver` si es un avatar.

---

## 1. Avatares de personaje

Carpeta: `public/assets/avatars/`. Resueltos en runtime por
`app/Shared/Domain/Services/AvatarAssetResolver.php` — leer ese archivo antes de tocar esto,
tiene la lógica exacta de fallback.

### 1.1 Convención de nombre

```
{Grupo}_{Masc|Fem}_{Fase}{Posición}.png
```

Ejemplo: `Medicina_Masc_43.png` → grupo **Medicina**, género **masculino**, fase **4**,
posición **3**. Cuatro partes, separadas por `_`, sin espacios ni mayúsculas inconsistentes —
respetar exactamente este patrón o `AvatarAssetResolver` no va a encontrar el archivo
(usa `file_exists` contra el nombre armado, no una lista ni un glob).

**Los dos dígitos finales del nombre son dos campos distintos pegados, no un solo número:**
el primero es la **fase** (1-10, un dígito por ahora — si en algún momento hay fase 10+, este
esquema de "un solo dígito" hay que revisarlo, no antes) y el segundo es la **posición**
(siempre 1-4, cerrado, no va a crecer). `43` se lee `4` + `3`, nunca `43` como fase única.

### 1.2 Grupo (primer campo del nombre)

Uno por cada `avatarStyle` de `App\Modules\Identity\Domain\ValueObjects\Career.php`, más
`Base`. La correspondencia estilo→prefijo vive en `AvatarAssetResolver::STYLE_PREFIXES` — si
agregás un grupo nuevo, actualizá esa constante, si no el resolver nunca lo va a encontrar por
más que el PNG ya esté en la carpeta.

| Grupo (prefijo de archivo) | `avatarStyle` | Carreras que representa | Estado del arte |
|---|---|---|---|
| `Base` | *(ninguno — aplica a cualquier estilo en fase 1)* | Todas, antes de que la fase avance | ✅ Completo (fase 1, únca fase que usa) |
| `Medicina` | `health` | Medicina, Enfermería, Obstetricia | 🟡 Parcial (fases 2-4 de 10) |
| `Tecnico` | `technical` | Ingeniería Civil, Ingeniería Industrial, Ingeniería de Minas, **Arquitectura** | 🟡 Parcial (solo fase 2, solo `Fem`) |
| *(sin prefijo todavía)* | `business` | Administración de Empresas, Contabilidad | ❌ Sin arte — cae a `Base` |
| *(sin prefijo todavía)* | `systems` | Ingeniería de Sistemas | ❌ Sin arte — cae a `Base` |
| *(sin prefijo todavía)* | `law` | Derecho | ❌ Sin arte — cae a `Base` |

`Base` es especial: no depende de la carrera y **solo existe en fase 1** ("nivel uno para
todas las carreras, el aspecto de estudiante" — es el aspecto por defecto antes de que el
usuario avance de fase, o el de cualquier `avatarStyle` que todavía no tiene prefijo propio).
No hace falta generar `Base` en fases 2+: `AvatarAssetResolver` ya usa `Base` como último
fallback en cualquier fase donde el prefijo real no tenga esa combinación exacta.

### 1.3 Posición (segundo dígito, cierre semántico confirmado 2026-07-29)

Cuatro poses fijas, iguales para cualquier grupo/género/fase — **no** es un orden de
visualización ni un identificador arbitrario, es literalmente la pose del personaje:

| Posición | Pose |
|---|---|
| `1` | Parado, normal |
| `2` | Parado, saludando |
| `3` | Sentado |
| `4` | Sentado, usando laptop |

`AvatarAssetResolver::MODULE_POSITION` fija qué posición le corresponde a cada módulo de la
app (la fase sí se sortea al azar entre las disponibles; la posición no, es fija por módulo):

| Módulo | Posición fija | Por qué |
|---|---|---|
| Dashboard | `1` (parado normal) | Pantalla neutra, no hay una pose más específica que pedir |
| Hábitos | `2` (parado saludando) | Tono cercano, de rutina diaria |
| Misiones *(módulo sin construir todavía, ver `docs/13-ROADMAP.md`)* | `3` (sentado) | Reservado, nada lo usa aún |
| Pomodoro | `4` (sentado con laptop) | Coincide exactamente con "estudiando" — elegido a propósito |

Si se agrega un módulo nuevo que necesite avatar decorativo, sumarlo a `MODULE_POSITION` con
la posición que mejor calce semánticamente, siguiendo esta misma lógica — no reusar `3`
(reservado para Misiones) sin revisar primero si Misiones ya está construido.

### 1.4 Inventario actual (36 archivos, 2026-07-29)

Fase = fila, Posición = columna. ✅ = existe, ⬜ = falta.

**`Base` — fase 1 únicamente (aplica a cualquier carrera):**

| Género | Pos. 1 | Pos. 2 | Pos. 3 | Pos. 4 |
|---|---|---|---|---|
| Masc | ✅ | ✅ | ✅ | ✅ |
| Fem | ✅ | ✅ | ✅ | ✅ |

**`Medicina` (`health`):**

| Fase | Género | Pos. 1 | Pos. 2 | Pos. 3 | Pos. 4 |
|---|---|---|---|---|---|
| 2 | Masc | ✅ | ✅ | ✅ | ✅ |
| 2 | Fem | ✅ | ✅ | ✅ | ✅ |
| 3 | Masc | ✅ | ✅ | ✅ | ✅ |
| 3 | Fem | ✅ | ✅ | ✅ | ✅ |
| 4 | Masc | ✅ | ✅ | ✅ | ✅ |
| 4 | Fem | ✅ | ✅ | ✅ | ✅ |
| 5-10 | Masc/Fem | ⬜ | ⬜ | ⬜ | ⬜ |

**`Tecnico` (`technical`):**

| Fase | Género | Pos. 1 | Pos. 2 | Pos. 3 | Pos. 4 |
|---|---|---|---|---|---|
| 2 | Fem | ✅ | ✅ | ✅ | ✅ |
| 2 | Masc | ⬜ | ⬜ | ⬜ | ⬜ |
| 3-10 | Masc/Fem | ⬜ | ⬜ | ⬜ | ⬜ |

**`business`, `systems`, `law`:** sin ningún archivo — 0 de 4 grupos con arte propio de los 5
que existen en `Career.php` (`health` y `technical` sí tienen). Caen a `Base` en todas las
fases hasta que exista arte.

### 1.5 Qué falta, en orden de impacto (para quien encargue el próximo bloque de arte)

1. **`Tecnico_Masc_2{1-4}.png`** (4 archivos) — cierra el género faltante de la única fase que
   ya tiene `Fem`. Es el hueco más chico y más notorio (usuarios `technical` + masculino hoy
   ven `Base`, no `Tecnico`, ni bien llegan a fase 2).
2. **Fases 5-10 de `Medicina`** (Masc+Fem × 4 posiciones × 6 fases = 48 archivos) — el grupo
   con más usuarios probables, ya con fases 2-4 listas.
3. **Fases 3-10 de `Tecnico`** (Masc+Fem × 4 posiciones × 8 fases = 64 archivos).
4. **`business`, `systems`, `law` completos** (4 posiciones × 10 fases × 2 géneros cada uno =
   80 archivos por grupo, 240 en total) — ningún archivo todavía, prioridad más baja porque no
   hay evidencia de cuántos participantes reales caen en estos tres estilos.

---

## 2. Imágenes de marca (sin convención de nombre — son piezas únicas)

| Archivo | Uso | Componente/lugar |
|---|---|---|
| `public/assets/images/logo.webp` | Logo de la app | `ApplicationLogo.vue`, insignia del nav |
| `public/assets/images/login-hero.webp` | Ilustración de la pantalla de login | `GuestLayout.vue` (slot `heroImage`) |
| `public/assets/wallpapers/full/atardecer.avif` | Único fondo de pantalla disponible hoy, fijo por CSS para el modo Vidrio | `app.css` (`--user-wallpaper`), ver `docs/04-DISENO-VISUAL.md` |
| `public/assets/wallpapers/thumbs/` | Carpeta creada para miniaturas de un futuro selector de fondos (Fase 9 — Personalization) | Vacía, sin construir todavía |

Estas no siguen convención de nombre porque no son parte de un set generado/repetible — cuando
la Fase 9 (Ranking + Personalization) agregue más fondos de pantalla, ese es el momento de
definir una convención para `wallpapers/` (no antes, no inventada acá sin necesidad real).
