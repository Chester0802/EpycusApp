# Flujo de Trabajo: Brain Dump ➔ Epycus (Versión Detallada)

Aquí tienes la versión ampliada y corregida. Tienes toda la razón: **Epycus sí cuenta con un módulo de Finanzas nativo** (con control de ingresos/egresos, presupuestos y metas de ahorro), además de la **Inteligencia Artificial (Edy)**, Grupos de Estudio, y un ecosistema completo de gamificación. 

Estos diagramas ahora reflejan con total fidelidad la arquitectura técnica de Epycus para que tu explicación en el video sea perfecta.

---

## 1. El Vaciado Mental (Brain Dump)
Este diagrama inicial (qué poner en el papel) se mantiene conceptualmente igual, ya que representa la psicología del estudiante antes de usar el sistema.

```mermaid
mindmap
  root((Brain Dump:
  Vaciado Mental))
    Área Académica
      El Presente "El Ahora"
        Cursos y horarios
        Pendientes inmediatos
        Fechas clave y exámenes
      El Futuro "El Mañana"
        Metas de notas
        Aspiraciones a mediano plazo
    Personal y Bienestar
      El Presente "El Ahora"
        Rutina y sueño
        Salud mental y estrés
        Hábitos vigentes
      El Futuro "El Mañana"
        Metas físicas y rutinas
        Crecimiento personal
    Proyectos y Hobbies
      El Presente "El Ahora"
        Proyectos alternos
        Pasatiempos actuales
      El Futuro "El Mañana"
        Sueños de emprendimiento
        Nuevas habilidades
    Relaciones Sociales
      El Presente "El Ahora"
        Compromisos sociales
        Tiempo con seres queridos
      El Futuro "El Mañana"
        Networking
        Mejorar relaciones
    Área Financiera
      El Presente "El Ahora"
        Presupuesto actual
        Gastos fijos
        Deudas inmediatas
      El Futuro "El Mañana"
        Metas de ahorro
```

---

## 2. Procesar y Asignar en Epycus (La Estructura)
Este es el diagrama más importante para enseñar a usar la app. Muestra **dónde va cada cosa**. Destaca que el **Calendario es el corazón inicial** (ahí nacen los cursos) y que **Edy (IA)** actúa como copiloto para ayudar al estudiante a organizar este caos si se siente abrumado.

```mermaid
flowchart TD
    A[Hojas del Brain Dump llenas] --> B(1. Procesar la Información)
    
    %% IA Assistant como copiloto
    A -.->|¿No sabes por dónde empezar?| IA[Edy: Inteligencia Artificial]
    IA -.->|Consejos de organización y priorización| B
    
    %% Área Académica y Proyectos
    B --> C[Clases y Apuntes]
    C -->|Paso 1: Crear Cursos y Fechas| CAL[Módulo Calendario]
    
    B --> D[Tareas Académicas y Proyectos Hobbies]
    CAL -.->|Vinculación Opcional| MIS[Módulo Misiones]
    D -->|Paso 2: Crear Tareas y Subtareas| MIS
    
    %% Bienestar y Físico
    B --> E[Acciones Repetitivas y Salud Física]
    E -->|Tracking de rachas| HAB[Hábitos]
    E -->|Rutinas visuales| FIT[Fitness Player]
    
    %% Salud Mental
    B --> F[Carga Mental y Emociones]
    F -->|Energía, estrés y notas| DIA[Diario de Bienestar]
    
    %% Finanzas
    B --> FIN[Presupuestos, Gastos y Ahorros]
    FIN -->|Ingresos, egresos y metas| FINANCE[Módulo de Finanzas]
    
    %% Asignación de Prioridad y Tiempo
    CAL --> G(2. Asignar Prioridad y Tiempo)
    MIS --> G
    HAB --> G
    FIT --> G
    
    G --> H[Organizar Kanban y Urgencia]
    H --> EIS[Matriz de Eisenhower]
    
    G --> I[Construir el día de hoy]
    I --> DAY[Day Planner / Time-blocking]
    
    classDef module fill:#e2e8f0,stroke:#334155,stroke-width:2px,color:#0f172a,font-weight:bold
    classDef ai fill:#c084fc,stroke:#6b21a8,stroke-width:2px,color:#ffffff,font-weight:bold
    class CAL,MIS,HAB,FIT,DIA,FINANCE,EIS,DAY module
    class IA ai
```

---

## 3. Ejecutar y Recompensar (La Acción Gamificada)
Aquí es donde Epycus brilla frente a Notion u otras agendas. El estudiante no solo marca un "check", sino que **juega** contra la procrastinación.

```mermaid
flowchart TD
    A[Día estructurado en Day Planner] --> B(3. EJECUCIÓN)
    
    B --> C{Modos de Acción}
    
    C -->|Foco Individual| P[Temporizador Pomodoro]
    C -->|Estudio con Amigos| G[Grupos de Estudio Multiplayer]
    C -->|Entrenamiento| F[Fitness Visual Player]
    C -->|Dudas sobre la materia| IA[Edy: IA Assistant]
    
    P --> E(Resultados del Día)
    G --> E
    F --> E
    IA -.->|Consultas y resúmenes| E
    
    E -->|Acumular Experiencia| R1[Subir de Rango y Nivel Académico]
    E -->|Batallas Semanales| R3[Derrotar Villanos de la Procrastinación]
    E -->|Ganar Monedas| R2[Comprar en la Tienda]
    E -->|Posicionamiento| R4[Módulo Ranking Global]
    
    R2 --> W[Canjear Premios Reales o Avatares]
    
    classDef module fill:#e2e8f0,stroke:#334155,stroke-width:2px,color:#0f172a,font-weight:bold
    classDef reward fill:#dcfce3,stroke:#166534,stroke-width:2px,color:#14532d,font-weight:bold
    classDef battle fill:#fecdd3,stroke:#9f1239,stroke-width:2px,color:#4c0519,font-weight:bold
    classDef ai fill:#c084fc,stroke:#6b21a8,stroke-width:2px,color:#ffffff,font-weight:bold
    
    class P,G,F module
    class R1,R2,R4,W reward
    class R3 battle
    class IA ai
```

### Casos de Uso Específicos para mencionar en el Video:
1. **Inteligencia Artificial (Edy):**
   * *Caso 1 (En el Procesamiento):* Si en su Brain Dump tienen 40 cosas mezcladas y sienten ansiedad, abren a Edy y le dicen: *"Edy, esto es lo que tengo que hacer esta semana: [lista]. Ayúdame a priorizarlo según la matriz de Eisenhower"*.
   * *Caso 2 (En la Ejecución):* Mientras usan el Pomodoro, si se atascan con un problema de Cálculo, le consultan a Edy para destrabarse rápidamente sin romper su concentración navegando por internet.
2. **Finanzas Estudiantiles:**
   * Mostrar cómo pueden fijar su *presupuesto mensual de fotocopias o transporte*, ir restando gastos y establecer una *meta de ahorro* (ej. "Ahorro para laptop nueva") con una barra de progreso visual.
3. **Misiones Multiuso:**
   * Mostrar que una Misión no tiene por qué ser obligatoriamente un trabajo de universidad. Pueden crear una misión llamada *"Aprender a editar en Premiere"*, no vincularla a ningún curso, y dividirla en Kanban (Subtareas).
4. **Villanos y Ranking (El Gancho Final):**
   * Explicar que fallar en sus tareas le da "puntos de vida" a los Villanos semanales. Si el estudiante ejecuta, les hace daño; si procrastina, el villano ataca sus estadísticas y los hace bajar en el Ranking global de estudiantes.
