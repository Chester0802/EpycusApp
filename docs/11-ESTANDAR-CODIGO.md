\
# 11 — Estándar de código

> Reglas mecánicas. **Si algo aquí contradice tu criterio, gana este documento.** El objetivo es que el código escrito por personas distintas y por inteligencias artificiales distintas sea indistinguible entre sí.

---

## 1. Regla cero

**Antes de escribir un archivo, busca uno equivalente ya existente y cópialo estructuralmente.** La consistencia vale más que la elegancia individual. Si el módulo `Habits` resuelve algo de cierta manera, `Missions` lo resuelve igual aunque se te ocurra algo mejor.

Si de verdad hay una forma mejor, propónla en el pull request y, si se acepta, **se cambian todos los módulos a la vez**. Nunca uno solo.

---

## 2. PHP

### Encabezado obligatorio

Todo archivo PHP empieza igual:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Habits\Domain\Entities;
```

Sin excepciones. `strict_types` evita conversiones silenciosas que producen fallos difíciles de rastrear.

### Nomenclatura

| Elemento | Convención | Ejemplo |
|---|---|---|
| Clase | PascalCase, sustantivo | `HabitCompletion` |
| Interfaz | PascalCase + sufijo | `HabitRepositoryInterface` |
| Caso de uso | Verbo + sustantivo + `UseCase` | `CompleteHabitUseCase` |
| Evento | Sustantivo + verbo en pasado | `HabitCompleted` |
| Excepción | Descripción + `Exception` | `DailyHabitLimitReachedException` |
| Método | camelCase, verbo | `completeFor()` |
| Variable | camelCase | `$focusMinutes` |
| Constante | SCREAMING_SNAKE | `DAILY_CAP` |
| Tabla | snake_case plural | `habit_completions` |
| Columna | snake_case | `completed_for` |

**Nombres de clases, métodos y variables en inglés. Textos visibles al usuario en español.** Sin mezclar dentro de un identificador: nunca `$habitoCompletado`.

### Estructura de clase

Orden fijo de miembros:

```php
final class Habit
{
    // 1. Constantes
    public const DAILY_CAP = 5;

    // 2. Propiedades
    private array $completions = [];

    // 3. Constructor
    public function __construct(
        private readonly HabitId $id,
        private readonly int $userId,
        private string $title,
    ) {}

    // 4. Fábricas estáticas
    public static function create(int $userId, string $title): self { }

    // 5. Métodos públicos (comandos primero, consultas después)
    public function completeFor(DateTimeImmutable $date): HabitCompletion { }
    public function id(): HabitId { }

    // 6. Métodos privados
    private function assertNotCompletedOn(DateTimeImmutable $date): void { }
}
```

### Reglas duras

| Regla | Motivo |
|---|---|
| Clases `final` salvo justificación escrita | La herencia acopla; se prefiere composición |
| DTOs y eventos `final readonly` | Inmutabilidad; evita mutación accidental |
| Todo parámetro y retorno tipado | `mixed` solo si es inevitable, con comentario |
| Sin `else` después de `return` | Reduce anidamiento |
| Máximo 3 niveles de anidamiento | Si necesitas más, extrae un método |
| Métodos de máximo 20 líneas | Si es más largo, hace más de una cosa |
| Máximo 4 parámetros | Si necesitas más, pasa un DTO |
| Sin `static` salvo fábricas | Impide sustituir en pruebas |

### Cláusulas de guarda

Valida al principio y sal temprano.

```php
// MAL
public function complete(Habit $habit): void {
    if ($habit->isActive()) {
        if (!$habit->wasCompletedToday()) {
            if ($this->underDailyCap()) {
                // lógica real, con 3 niveles de sangría
            }
        }
    }
}

// BIEN
public function complete(Habit $habit): void {
    if (!$habit->isActive()) {
        throw new HabitInactiveException($habit->id());
    }
    if ($habit->wasCompletedToday()) {
        throw new HabitAlreadyCompletedException($habit->id());
    }
    if (!$this->underDailyCap()) {
        throw new DailyHabitLimitReachedException($this->userId);
    }

    // lógica real, sin sangría
}
```

### Comentarios

**Comenta el porqué, nunca el qué.**

```php
// MAL — describe lo obvio
// Incrementa el contador en 1
$counter++;

// BIEN — explica una decisión no evidente
// El evento se emite FUERA de la transacción: si hubiera rollback,
// habríamos otorgado XP por una acción que no ocurrió.
$this->events->dispatch(new HabitCompleted(...));
```

Comenta siempre que una decisión responda a una restricción del hosting o del estudio. Esas son las que nadie adivina leyendo el código.

### Importaciones

Ordenadas y agrupadas: primero PHP nativo, luego dependencias externas, luego del proyecto. Sin importaciones sin usar. Sin `use` con comodín.

---

## 3. Vue

### Estructura de componente

Orden fijo: `<script setup>`, `<template>`, `<style scoped>`.

```vue
<script setup>
// 1. Importaciones
import { ref, computed, onMounted } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'

// 2. Props
const props = defineProps({
    habit:      { type: Object,  required: true },
    isLoading:  { type: Boolean, default: false },
})

// 3. Emits
const emit = defineEmits(['complete', 'delete'])

// 4. Composables
const { track } = useTelemetry()

// 5. Estado reactivo
const isExpanded = ref(false)

// 6. Computadas
const streakLabel = computed(() => `${props.habit.streak} días`)

// 7. Funciones
function handleComplete() {
    track('habit.completed', 'habits', { habit_id: props.habit.id })
    emit('complete', props.habit.id)
}

// 8. Ciclo de vida
onMounted(() => { /* ... */ })
</script>
```

### Nomenclatura

| Elemento | Convención | Ejemplo |
|---|---|---|
| Componente | PascalCase, 2+ palabras | `HabitCard.vue` |
| Componente base | Prefijo `Base` | `BaseButton.vue` |
| Página Inertia | PascalCase en `Pages/` | `Pages/Habits/Index.vue` |
| Composable | `use` + camelCase | `useTelemetry.js` |
| Prop | camelCase en script, kebab en plantilla | `isLoading` → `:is-loading` |
| Evento | kebab-case, verbo | `@complete`, `@subtask-added` |

**Nunca un componente de una sola palabra.** `Card.vue` puede chocar con un elemento HTML futuro; `HabitCard.vue` no.

### Reglas de plantilla

| Regla | Motivo |
|---|---|
| Un componente por archivo | — |
| Máximo 250 líneas | Si es más, divide |
| `v-for` siempre con `:key` estable | Nunca el índice del array |
| **Prohibido `v-html`** con contenido de usuario | Vector de XSS |
| Sin lógica compleja en plantilla | Extrae a `computed` |
| Máximo 3 niveles de anidamiento condicional | — |
| Clases con tokens semánticos | Ver `docs/10-ESPECIFICACIONES-UI.md` |

### Nada de colores ni medidas literales

```html
<!-- MAL -->
<div class="bg-pink-100 p-[14px] rounded-[10px] text-[15px]">

<!-- BIEN -->
<div class="bg-surface p-4 rounded-lg text-base">
```

Todo valor debe salir de las progresiones definidas. Si necesitas un valor que no existe en la escala, es señal de que la escala está mal o de que el diseño está mal. Pregunta antes de inventarlo.

---

## 4. Base de datos

### Migraciones

Una migración por tabla. Nombre descriptivo: `2026_08_01_000000_create_habits_table.php`.

```php
Schema::create('habits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title', 120);
    $table->enum('category', ['estudio','sueno','ejercicio','alimentacion','otro']);
    $table->json('frequency');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'is_active']);
});
```

**Toda columna consultada en `WHERE`, `JOIN` u `ORDER BY` lleva índice.** Con un solo núcleo de CPU, un escaneo completo de tabla se nota.

**Toda regla de unicidad se impone en la base de datos, no solo en la aplicación.** Una validación en código es vulnerable a condiciones de carrera; una restricción única no.

### Consultas

| Regla | Motivo |
|---|---|
| Nunca concatenar en `DB::raw()` | Inyección SQL |
| Precargar relaciones con `with()` | Evita el problema N+1 |
| Paginar toda lista que pueda crecer | — |
| Inserción masiva para telemetría | El límite es de 128 operaciones por segundo |
| Sin consultas dentro de bucles | — |

```php
// MAL — N+1
foreach ($missions as $mission) {
    echo $mission->subtasks->count();
}

// BIEN
$missions = Mission::withCount('subtasks')->get();
```

---

## 5. Pruebas

### Nomenclatura

Método en snake_case describiendo el comportamiento esperado:

```php
public function test_completing_a_habit_twice_in_the_same_day_throws_exception(): void
public function test_xp_is_not_awarded_beyond_the_daily_cap(): void
public function test_telemetry_event_is_recorded_when_pomodoro_completes(): void
```

Se lee como una frase. Al fallar, el nombre ya dice qué se rompió.

### Estructura AAA

```php
public function test_xp_is_not_awarded_beyond_the_daily_cap(): void
{
    // Arrange
    $user = User::factory()->create();
    $this->completeHabits($user, count: 5);   // ya en el tope

    // Act
    $result = $this->awardXp->execute(new AwardXpDTO($user->id, 'habit', 6));

    // Assert
    $this->assertSame(0, $result->amount);
    $this->assertTrue($result->wasCapped);
}
```

### Qué se prueba obligatoriamente

| Área | Cobertura |
|---|---|
| Módulo `Telemetry` completo | **Obligatoria, sin excepción** |
| Cálculo de XP en `Gamification` | **Obligatoria, sin excepción** |
| Entidades y objetos de valor de `Domain` | Obligatoria |
| Casos de uso que escriben datos | Obligatoria |
| Especificaciones de dominio | Obligatoria |
| Controladores y policies | Recomendada |
| Componentes Vue | Solo los complejos |

Las dos primeras son innegociables: si fallan en silencio, se pierde el estudio y no hay forma de recuperarlo.

---

## 6. Git

### Ramas

```
main              siempre desplegable
develop           integración
feature/{modulo}-{descripcion}
fix/{descripcion}
```

### Mensajes de commit

```
tipo(modulo): descripción en imperativo

feat(habits): agregar tope diario de completados
fix(telemetry): evitar pérdida de eventos al cerrar pestaña
refactor(pomodoro): extraer cálculo de duración a servicio de dominio
test(gamification): cubrir idempotencia del otorgamiento de XP
docs(readme): actualizar alcance del MVP
```

Tipos: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`.

En imperativo y en español. Máximo 72 caracteres en la primera línea.

### Antes de cada commit

- [ ] El código corre sin errores
- [ ] Las pruebas pasan
- [ ] Sin `dd()`, `dump()`, `var_dump()`, `console.log()`
- [ ] Sin credenciales ni claves
- [ ] Sin código comentado "por si acaso"
- [ ] Sin importaciones sin usar

---

## 7. Prohibiciones absolutas

| Prohibido | Motivo |
|---|---|
| `use Illuminate\...` dentro de `Domain/` | Rompe la regla de dependencia |
| Modelo Eloquent dentro de un evento | Acopla módulos, falla al serializar en cola |
| Emitir evento dentro de la transacción | Si hay rollback, se otorga XP por algo que no pasó |
| `env()` fuera de `config/` | Devuelve `null` con configuración cacheada |
| Importar clase de otro módulo | Usa eventos o contrato compartido |
| Un INSERT por evento de telemetría | Satura el límite de operaciones del hosting |
| Sesiones o cache en archivos | Agota los inodos disponibles |
| `v-html` con contenido de usuario | XSS |
| Color o medida literal en un componente | Rompe el sistema de tokens |
| Dato personal en logs o telemetría | Ley 29733 y compromiso de ética |
| Omitir `$this->authorize()` en un controlador | Fuga de datos entre usuarios |
| Tragar una excepción con `catch` vacío | Único caso permitido: el listener de telemetría |

---

## 8. Plantilla de caso de uso

Copia esta estructura. Todos los casos de uso son iguales.

```php
<?php

declare(strict_types=1);

namespace App\Modules\{Modulo}\Application\UseCases;

final readonly class {Verbo}{Sustantivo}UseCase
{
    public function __construct(
        private {Entidad}RepositoryInterface $repository,
        private TransactionManagerInterface $transaction,
        private Dispatcher $events,
    ) {}

    public function execute({Verbo}{Sustantivo}DTO $dto): {Sustantivo}DTO
    {
        // 1. Recuperar, fallar rápido si no existe
        $entity = $this->repository->findById($dto->id)
            ?? throw new {Sustantivo}NotFoundException($dto->id);

        // 2. Validar reglas de dominio (specifications)
        if (! $this->specification->isSatisfiedBy($entity)) {
            throw new {Regla}Exception();
        }

        // 3. Ejecutar dentro de transacción
        $result = $this->transaction->run(function () use ($entity, $dto) {
            $entity->{accionDeDominio}($dto->valor);
            $this->repository->save($entity);
            return $entity;
        });

        // 4. Emitir evento DESPUÉS del commit
        $this->events->dispatch(new {Sustantivo}{VerboPasado}(
            userId: $dto->userId,
            entityId: $result->id()->value(),
            occurredAt: $dto->occurredAt,
        ));

        // 5. Devolver DTO, nunca un modelo Eloquent
        return {Sustantivo}DTO::fromDomain($result);
    }
}
```

Los cinco pasos siempre en ese orden. Si tu caso de uso no encaja, probablemente hace más de una cosa.

---

## 9. Herramientas

```bash
composer require --dev laravel/pint          # formato PHP
composer require --dev larastan/larastan     # análisis estático
npm i -D eslint prettier eslint-plugin-vue   # frontend
```

`pint.json` con preset `laravel`. `phpstan.neon` en **nivel 6** como mínimo.

Antes de un pull request:

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
php artisan test
npm run lint
```

Los cuatro deben pasar. Sin excepciones.

---

## 10. Si tienes una duda

En este orden:

1. Busca un archivo equivalente en otro módulo y cópialo estructuralmente
2. Revisa el documento correspondiente en `docs/`
3. Si es visual, carga la skill `epycus-ui`
4. **Si sigues sin saber, pregunta. No inventes.**

Una decisión inventada que se replica en veinte archivos cuesta mucho más que una pregunta.
