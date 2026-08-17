# Plan de Implementación: Ajustes y Correcciones de Producción

Basado en el feedback recibido de pruebas de usuarios en producción (`app.epycus.es`), se plantean los siguientes ajustes organizados por componente.

---

## 📋 Diagnóstico y Soluciones Propuestas

### 1. Bono Inicial de XP por Evaluación EPA (`/epa`)
- **Problema:** El cuestionario inicial se responde y no vuelve a aparecer, pero el usuario no percibe la notificación de la experiencia (+50 XP) ganada.
- **Solución:**
  - En `EpaController.php` y `RecordEpaPretestUseCase.php`, asegurar que `AwardXpUseCase` registre los +50 XP y emita el estado actualizado de `user_progress`.
  - En el frontend `EpaModal.vue` / `Dashboard.vue`, mostrar un aviso flotante / toast visible `"¡+50 XP Ganados por tu evaluación inicial!"` al enviar las respuestas.

### 2. Módulo de Misiones (`/missions`)
- **Cambio de Texto:** Cambiar el subtítulo `"Descompón tus tareas grandes en pasos manejables"` por `"Empieza en pequeño para realizar cosas grandes"`.
- **Transición a "En Progreso":**
  - Al marcar una subtarea, si aún quedan subtareas pendientes, la misión debe cambiar explícitamente su estado visual a **"En Progreso"** con su insignia correspondiente (en lugar de ocultarse o marcarse como realizada).
- **Despliegue de Misiones Completadas / Archivadas:**
  - En `Missions/Index.vue`, asegurar que la sección de misiones completadas/archivadas sea desplegable, permitiendo ver sus subtareas y ofreciendo el botón **"Desarchivar / Reabrir"**.

### 3. Módulo de Hábitos (`/habits`)
- **Efectos Visuales de Cumplimiento:**
  - Agregar animación de celebración / confeti visual e indicador sonoro (opcional/desactivable) cuando se marca un hábito diario.

### 4. Módulo Pomodoro (`/pomodoro`)
- **Contador en Pantalla Durante Sesión de Foco:**
  - Asegurar que el temporizador de foco descuente y refresque numéricamente los segundos en pantalla de manera continua y fluida, manteniendo la resiliencia al recargar la página.

### 5. Módulo de Villanos (`/villains`)
- **Aplicación de Daño por Diario/Bienestar:**
  - Conectar el caso de uso de registro en el Diario de Bienestar (`WellbeingRecordUseCase.php`) con el Villano de la Semana. Si la debilidad del villano incluye `journal`, aplicarle -10 HP de daño inmediatamente al guardar la entrada.

### 6. Módulo Bienestar / Diario (`/wellbeing`)
- **Actualización Reactiva Sin Recargar:**
  - En `Wellbeing/Index.vue`, hacer que al guardar el registro emocional del día, el calendario y el registro actual de hoy se actualicen en tiempo real sin requerir `F5` / recarga manual.

### 7. Módulo de Perfil (`/profile`)
- **Ocultar "Actualizar Contraseña" para Usuarios de Google:**
  - En `Profile/Edit.vue` o `UpdatePasswordForm.vue`, verificar si el usuario se registró / autenticó vía Google (`user.google_id`). Si es un usuario de Google, ocultar el bloque de cambio de contraseña ya que es administrado por Google.

### 8. 🚨 FIX CRÍTICO: Eliminación de Subventana / Vista Previa Desfasada
- **Problema:** En ciertas navegaciones aparece un marco interno con una vista previa desfasada (en modo claro dentro de la pantalla oscura).
- **Causa Raíz:** Ocurre debido al mecanismo de pre-renderizado/prefetching de enlaces de Inertia.js o contenedores duplicados en `AppLayout.vue`.
- **Solución:** Desactivar el pre-renderizado ambiguo en la navegación principal y asegurar que `AppLayout.vue` limpie y monte un solo contenedor raíz de renderizado sin duplicación de `page`.

### 9. Módulo Calendario (`/calendar`) — Fechas de Inicio y Fin de Cursos
- **Añadir Rango de Fechas a los Cursos:**
  - Modificar la migración y modelo `CourseModel` agregando `starts_at` (fecha de inicio) y `ends_at` (fecha de fin).
  - En el modal de creación y edición de cursos, permitir seleccionar estas dos fechas.
  - En la grilla del calendario, renderizar las clases únicamente en los días que se encuentren dentro del rango de fechas del curso.

### 10. Frases de Motivación de Científicos Famosos
- **Ampliación de Frases:**
  - Agregar una colección curada de citas célebres de científicos famosos (Albert Einstein, Marie Curie, Richard Feynman, Nikola Tesla, Carl Sagan, Ada Lovelace, Louis Pasteur, Isaac Newton, Stephen Hawking) al banner de motivación.

---

## 🛠️ Cambios Propuestos por Componente

#### [MODIFY] [Missions/Index.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Missions/Index.vue)
- Actualizar texto descriptivo.
- Corregir renderizado de estado "En Progreso" al marcar subtareas.
- Permitir desplegar y desarchivar misiones en la lista de completadas.

#### [MODIFY] [Profile/Edit.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Profile/Edit.vue)
- Ocultar `UpdatePasswordForm` si el usuario tiene `google_id`.

#### [NEW] [migration courses_add_date_range](file:///c:/Users/marco/Videos/Epycus/database/migrations/)
- Agregar columnas `starts_at` y `ends_at` a la tabla `courses`.

#### [MODIFY] [Calendar/Index.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Pages/Calendar/Index.vue)
- Filtrar la renderización de clases en el calendario según `starts_at` y `ends_at`.
- Actualizar el formulario de cursos para solicitar fecha de inicio y fin.

#### [MODIFY] [WellbeingRecordUseCase.php](file:///c:/Users/marco/Videos/Epycus/app/Modules/Wellbeing/Application/UseCases/WellbeingRecordUseCase.php)
- Despachar daño al villano activo si la debilidad es `journal`.

#### [MODIFY] [UsageTipBanner.vue](file:///c:/Users/marco/Videos/Epycus/resources/js/Components/ui/UsageTipBanner.vue)
- Añadir citas de científicos famosos.

---

## 🧪 Plan de Verificación

### Pruebas Automatizadas (PHPUnit)
- Ejecutar `php vendor/bin/phpunit` para asegurar que las 121+ pruebas sigan pasando al 100%.

### Verificación Manual
- Crear y editar un curso con rango de fechas y comprobar que solo aparezca en ese período en el calendario.
- Marcar una subtarea en Misiones y comprobar el estado "En Progreso".
- Registrar una entrada en el Diario y verificar que el Villano reciba daño si es débil contra `journal`.
- Probar la navegación con cuenta de Google y confirmar que no aparezca la opción de cambiar contraseña.
