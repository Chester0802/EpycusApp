\
# 09 — Argumentación de las decisiones del sistema

> Registro de por qué el sistema es como es. Cada decisión con su contexto, las alternativas descartadas, el fundamento y el costo asumido.
>
> **Este documento alimenta la sección 3.4 del artículo (Desarrollo del sistema) y es la base de la defensa técnica ante el jurado.**

---

## 1. El principio rector

Casi todos los estudios de "aplicación + intervención" siguen esta secuencia: se construye un producto con criterios de producto, y después se mide qué pasó. El software se diseña para gustar, retener y crecer; la medición se acopla al final.

**Aquí la secuencia está invertida.** El software es el instrumento de medición, no el objeto de estudio. Cada decisión de ingeniería se tomó respondiendo primero a una pregunta:

> ¿Esta decisión contamina el experimento, lo protege, o le es indiferente?

Cuando una decisión contaminaba el experimento, se descartó aunque mejorara el producto. Cuando lo protegía, se adoptó aunque encareciera el desarrollo.

Llamamos a esto **validez de investigación por diseño**, por analogía con el principio de *security by design*: igual que la seguridad no se añade al final sino que condiciona la arquitectura, aquí la validez del dato condiciona la arquitectura.

Las secciones 3 y 4 son las que sostienen ese argumento. Las secciones 5 y 6 documentan decisiones más convencionales, incluidas por completitud.

---

## 2. Cómo leer cada decisión

| Campo | Qué contiene |
|---|---|
| **Contexto** | La situación que obligó a decidir |
| **Alternativas** | Lo que se evaluó y se descartó |
| **Decisión** | Lo que se hizo |
| **Fundamento** | Por qué, con evidencia cuando existe |
| **Costo asumido** | Lo que se perdió al decidir así |

---

# 3. Decisiones que protegen la validez del estudio

Estas son el núcleo del argumento. Todas nacen de la pregunta de la sección 1.

## D-A · Topes diarios de experiencia

**Contexto.** El sistema otorga XP por completar hábitos, sesiones Pomodoro y misiones. Sin límite, un participante podría crear veinte hábitos triviales y marcarlos todos en un minuto.

**Alternativas.** (a) Sin topes, confiando en el uso honesto. (b) Detección posterior de anomalías en el análisis. (c) Topes por fuente de XP.

**Decisión.** Topes diarios por fuente: 5 hábitos, 8 Pomodoros, 3 misiones, 1 entrada de diario.

**Fundamento.** La métrica de adherencia es una de las variables dependientes del estudio. Si el sistema permite inflarla artificialmente, la variable deja de medir constancia y pasa a medir habilidad para explotar el sistema. La detección posterior no resuelve el problema: obligaría a descartar participantes y reduciría la muestra, que ya es limitada.

El tope de 8 Pomodoros equivale a 200 minutos de foco diario. Superarlo es improbable en un estudiante con carga académica y, de ocurrir, es indicio de manipulación más que de rendimiento.

**Costo asumido.** Un participante genuinamente excepcional queda limitado. Se considera aceptable: preferimos truncar el extremo superior antes que admitir datos corruptos. El sistema registra `was_capped` en cada otorgamiento, de modo que en el análisis se sabe cuántas veces alguien chocó con el tope.

---

## D-B · El ranking como variable de control, no como funcionalidad

**Contexto.** El equipo quería un ranking entre participantes. El Pilar 2 del estudio sostiene que la evolución del avatar hacia la identidad profesional es lo que motiva el cambio de conducta.

**Alternativas.** (a) Ranking visible en el dashboard, como cualquier app gamificada. (b) Eliminarlo del MVP. (c) Incluirlo pero instrumentado.

**Decisión.** Se incluye, con tres restricciones: vive en una vista propia a la que se entra deliberadamente, **está prohibido mostrarlo como widget o de forma pasiva**, y cada consulta se registra con posición, total de usuarios y tiempo en pantalla.

**Fundamento.** El ranking introduce comparación social, un mecanismo psicológico distinto del que investiga el estudio. Si el efecto de la intervención resultara positivo con un ranking pasivo siempre visible, no habría forma de separar cuánto se debe a la identidad profesional y cuánto a competir con los compañeros. Un revisor lo señalaría de inmediato.

Al hacerlo deliberado e instrumentado, la exposición al ranking se convierte en una **variable medida**, que puede introducirse como covariable en el análisis. Eso permite estimar el efecto del Pilar 2 controlando el efecto de comparación social.

La condición de que sea deliberado es lo que da sentido al dato: un widget siempre visible registraría exposición accidental, no una decisión del participante.

**Costo asumido.** El ranking será menos usado que si estuviera a la vista. Es precisamente lo que se busca.

---

## D-C · Sin monetización durante la intervención

**Contexto.** Las encuestas diagnósticas midieron disposición a pagar, y el 84% de la muestra institucional declaró que pagaría algo. Existía la opción de implementar un modelo freemium.

**Alternativas.** (a) Freemium con funciones premium. (b) Todo gratuito durante la intervención. (c) Freemium simulado sin cobro real.

**Decisión.** Ninguna forma de monetización en la versión de campo.

**Fundamento.** Un modelo freemium introduce dos confusores. Primero, diferenciaría la experiencia entre participantes según quién pague, cuando el diseño requiere que todos reciban la misma intervención. Segundo, la disposición a pagar correlaciona con nivel socioeconómico, que a su vez correlaciona con variables académicas: se introduciría una variable no controlada por una vía indirecta.

La moneda blanda del juego se acumula desde el MVP, pero no hay nada que comprar hasta después de la intervención.

**Costo asumido.** No se obtienen datos reales de conversión ni de comportamiento de pago. Se acepta: ese dato interesa al producto, no al estudio.

---

## D-D · Curva de niveles diseñada para producir varianza

**Contexto.** El avatar tiene 50 niveles en 10 fases. Había que calibrar cuánta experiencia cuesta cada nivel.

**Alternativas.** (a) Curva que permita alcanzar el nivel 50 durante los 40 días de intervención. (b) Curva donde el participante medio quede a mitad de camino.

**Decisión.** Curva `XP(n) = 100 + (n-1) × 45`, con la que nadie alcanza el nivel 50 durante los 40 días. Proyección: perfil bajo llega a nivel 6, medio a 14, alto a 22, máximo teórico a 28.

**Fundamento.** Hay una razón de producto y una razón estadística, y la segunda es la que decidió.

La de producto: una progresión alcanzable produce un techo, y quien llega al techo pierde el incentivo justo cuando el estudio necesita que siga.

La estadística: **el nivel alcanzado es una variable continua del análisis.** Si la mayoría de los participantes tocara el máximo, la variable se comprimiría contra el techo (efecto techo) y perdería capacidad de correlacionarse con el cambio psicométrico en la escala EPA (8 ítems). Una distribución diferenciada da recorrido suficiente para detectar asociación.

**Costo asumido.** Las fases 8, 9 y 10 del avatar no las verá casi nadie durante el estudio, pese a que hay que producir sus assets. Se acepta: quedan como contenido posterior y como demostración de que el sistema tiene recorrido.

---

## D-E · Idempotencia en el otorgamiento de experiencia

**Contexto.** Las llamadas en cola pueden reintentarse. Un mismo evento de dominio podría procesarse dos veces.

**Alternativas.** (a) Confiar en que la cola no duplica. (b) Verificación en código antes de otorgar. (c) Restricción única en base de datos.

**Decisión.** Índice único sobre `(user_id, source_type, source_id)` en la tabla de transacciones de experiencia. Un reintento viola la restricción y se descarta.

**Fundamento.** Una duplicación silenciosa inflaría el XP de algunos participantes y no de otros, de forma no sistemática. Eso corrompe la correlación entre progreso y comportamiento real, que es el núcleo del Pilar 3, y sería casi imposible de detectar en el análisis posterior.

Se resolvió en la base de datos y no en el código porque una verificación en aplicación es vulnerable a condiciones de carrera. La restricción única es la única garantía real.

**Costo asumido.** Ninguno relevante.

---

## D-F · Logros sin comparación social ni penalización

**Contexto.** El sistema incluye logros e insignias desbloqueables.

**Decisión.** Ningún logro premia posición relativa frente a otros. Ningún logro es negativo.

**Fundamento.** Un logro del tipo "entraste al top 10" reforzaría exactamente la variable que se está tratando de controlar por separado (ver D-B), y contaminaría el dato del ranking.

La ausencia de logros negativos responde al riesgo de deserción, que es el mayor riesgo del estudio: un mensaje de "rompiste tu racha" en un momento de baja motivación es un empujón hacia el abandono, y cada participante perdido reduce una muestra ya limitada.

**Costo asumido.** Se renuncia a un mecanismo de enganche que la industria usa. Aceptado.

---

## D-G · Recuperación progresiva de rachas

**Contexto.** Las rachas premian la constancia. En los sistemas clásicos, fallar un día la reduce a cero.

**Alternativas.** (a) Reinicio total, como Duolingo clásico. (b) Sin rachas. (c) Días de gracia.

**Decisión.** Tres días de gracia por mes calendario, no acumulables. Al usar uno, la racha se congela en vez de romperse.

## D-G · Recuperación progresiva de rachas

**Contexto.** Las rachas premian la constancia. En los sistemas clásicos, fallar un día la reduce a cero.

**Alternativas.** (a) Reinicio total, como Duolingo clásico. (b) Sin rachas. (c) Días de gracia.

**Decisión.** Tres días de gracia por mes calendario, no acumulables. Al usar uno, la racha se congela en vez de romperse.

**Fundamento.** El reinicio total tiene un efecto documentado de abandono: quien pierde una racha larga tiende a dejar el sistema, porque siente que perdió lo acumulado. Con una intervención de 40 días donde la deserción es el riesgo principal, ese diseño es contraproducente.

Esta opción apareció además en las encuestas exploratorias (Cajamarca: Encuesta 1 $n=98$, Encuesta 2 $n=31$) como característica valorada por los participantes.

**Costo asumido.** La racha deja de ser una medida pura de días consecutivos. Se compensa registrando en telemetría cada uso de día de gracia, de modo que en el análisis se puede reconstruir la racha estricta si hace falta.

---

## D-H · Panel de administración de solo lectura

**Contexto.** El equipo necesita monitorear la intervención: adherencia, deserción, volumen de datos.

**Alternativas.** (a) Panel con capacidad de edición, para corregir errores. (b) Panel de solo lectura sobre los datos del estudio.

**Decisión.** El panel no puede modificar experiencia, niveles ni eventos de telemetría. Tampoco puede leer el diario de bienestar, el chat ni las conversaciones con la IA.

**Fundamento.** Si un administrador puede editar los datos, un revisor del artículo puede cuestionar la integridad de todo el conjunto: no hay forma de demostrar que no se modificó nada. La imposibilidad técnica de editar es un argumento más fuerte que la declaración de que no se editó.

Las restricciones de lectura responden a protección de datos y al compromiso de confidencialidad del consentimiento informado.

**Costo asumido.** Si un dato entra mal, no se corrige: se documenta y se trata en el análisis. Es el comportamiento correcto para un instrumento de medición.

---

## D-I · Congelamiento de producción durante los 40 días de intervención

**Contexto.** El desarrollo continúa antes y después de la intervención de campo.

**Decisión.** Ningún despliegue a producción durante la ventana de los 40 días de intervención (fechas de inicio y fin aún por definir), salvo fallo que impida el uso. Entorno de staging separado para todo lo demás. Toda excepción se registra en bitácora con fecha, hora y efecto.

**Fundamento.** Si el sistema cambia a mitad del periodo, los participantes no reciben la misma intervención: quienes usaron la versión de la semana 2 y quienes usaron la de la semana 5 tuvieron experiencias distintas. Eso rompe el supuesto de tratamiento homogéneo.

El registro en bitácora existe porque cualquier cambio inevitable debe declararse en las limitaciones del artículo. Un revisor tiene derecho a preguntar si el instrumento fue idéntico durante todo el periodo.

**Costo asumido.** Periodo de intervención sin desplegar mejoras. Se compensa con staging, que además no supone gasto extra porque el plan de hosting admite cien sitios.

---

# 4. Decisiones derivadas de evidencia empírica

## D-J · Los villanos salen del diagnóstico, no de la imaginación

**Contexto.** El sistema incluye un antagonista semanal temático.

**Decisión.** Los cinco villanos son: la Postergación, la Distracción, la Ansiedad, el Desorden y el Cansancio.

**Fundamento.** No son categorías inventadas. Corresponden al **Diagrama de Pareto de Obstáculos** de la encuesta diagnóstica (Google Forms, 4–6 abr. 2026, $n=98$), con esta distribución: falta de motivación o constancia 25,5 %; distracción con el celular o redes 19,4 %; olvido de lo planificado 18,4 % (acumulando el 63,3 %); no saber por dónde empezar 14,3 % (alcanzando el 77,6 %); cansancio tras las clases 12,2 %.

Esto permite argumentar ante el jurado que la mecánica del juego no es decorativa: **cada villano representa una barrera real y medida de la población objetivo**. Y en el análisis se puede examinar si los participantes derrotan con más frecuencia al villano que corresponde a su obstáculo declarado.

**Costo asumido.** Ninguno.

---

## D-K · Carrera y ciclo como campos cerrados

**Contexto.** El avatar evoluciona según la carrera del participante.

**Decisión.** Ambos campos son listas desplegables. Nunca texto libre.

**Fundamento.** La encuesta 2 (Microsoft Forms, 8 abr.–6 may. 2026, $n=31$) y los antecedentes institucionales recogieron la carrera como texto libre y produjeron **25 variantes escritas para 11 carreras reales** ("Ing. Sistemas", "ING de sistemas y computacionales", "Ing. Sitemas computacionales"). Agrupar exigió normalización manual y sujeta a criterio.

Si ese error se repitiera en la aplicación, la telemetría del Pilar 2 sería inservible: no se podría agrupar por carrera ni verificar que el avatar correcto se asignó a cada participante.

**Costo asumido.** Un participante de una carrera no listada debe elegir la opción más cercana. Se registra cuántos casos ocurren.

---

## D-L · Diseño móvil primero, escritorio con composición propia

**Contexto.** Había que priorizar un tamaño de pantalla.

**Fundamento.** La encuesta diagnóstica ($n=98$) muestra que la mayoría de los participantes accede desde teléfono. Pero las sesiones largas de trabajo académico —cuando más se usan el Pomodoro y las misiones— ocurren en computadora.

**Decisión.** Se diseña móvil primero, pero el escritorio recibe una composición propia, no una versión estirada del móvil.

**Costo asumido.** Más trabajo de diseño. Necesario: una experiencia degradada en escritorio afectaría justamente el escenario de uso más intensivo.

---

## D-P · Selección de 8 ítems de la Escala EPA para medir procrastinación (Variable Dependiente)

**Contexto.** La Escala de Procrastinación Académica (EPA) original posee múltiples reactivos. Se requería una versión ágil y validada para pre/postest sin fatigar al participante.

**Decisión.** Seleccionar exactamente 8 ítems estratégicos de la EPA (reactivos 2, 5, 7, 10, 11, 12, 13 y 14):
1. Ítem 2: Generalmente me preparo por adelantado para los exámenes.
2. Ítem 5: Cuando tengo problemas para entender algo, inmediatamente trato de buscar ayuda.
3. Ítem 7: Trato de completar el trabajo asignado lo más pronto posible.
4. Ítem 10: Constantemente intento mejorar mis hábitos de estudio.
5. Ítem 11: Invierto el tiempo necesario en estudiar aun cuando el tema sea aburrido.
6. Ítem 12: Trato de motivarme para mantener mi ritmo de estudio.
7. Ítem 13: Trato de terminar mis trabajos importantes con tiempo de sobra.
8. Ítem 14: Me tomo el tiempo de revisar mis tareas antes de entregarlas.

**Fundamento.** Estos 8 ítems cubren directamente la autorregulación académica y la gestión del tiempo frente al estudio. Al medirse antes de la intervención (pretest) y al finalizar los 40 días (postest), constituyen la métrica principal para probar la hipótesis $H_1$.

---

## D-Q · Evaluación de Usabilidad mediante los 10 ítems de la Escala SUS

**Contexto.** Cumplimiento del Objetivo Específico 3 para medir la usabilidad percibida del software Epycus.

**Decisión.** Implementar el cuestionario SUS estandarizado de 10 ítems:
1. Creo que me gustaría utilizar frecuentemente este sitio web.
2. Encontré el sitio web sencillo.
3. Pienso que el sitio web es fácil de usar.
4. Pienso que podré utilizar este sitio web sin el apoyo de personal técnico.
5. Encontré que varias de las funciones en el sitio web estaban bien integradas.
6. Pensé que había demasiada consistencia en el sitio web.
7. Me imagino que la mayoría de las personas podrían aprender a usar este sitio web muy rápido.
8. Encontré el sitio web muy intuitivo.
9. Me sentí muy confiado (seguro) al utilizar el sitio web.
10. Pude utilizar el sitio web sin tener que aprender nada nuevo.

**Fundamento.** Permite obtener un puntaje normalizado (0 a 100) sobre la usabilidad percibida del sistema y validar cuantitativamente si la interfaz no representó una barrera tecnológica durante los 40 días de intervención.

**Decisión.** Se diseña móvil primero, pero el escritorio recibe una composición propia, no una versión estirada del móvil.

**Costo asumido.** Más trabajo de diseño. Necesario: una experiencia degradada en escritorio afectaría justamente el escenario de uso más intensivo.

---

# 5. Decisiones condicionadas por la infraestructura

Estas no protegen la validez del estudio, pero explican por qué el sistema es como es. Se documentan porque un jurado técnico va a preguntar.

## D-M · Comunicación en tiempo real por sondeo, no por WebSockets

**Contexto.** Las sesiones de estudio grupal incluyen chat.

**Restricción verificada.** El hosting compartido de Hostinger permite conexiones WebSocket salientes pero **no permite que un proceso escuche conexiones entrantes**. Laravel Reverb no puede ejecutarse ahí. No es un problema de configuración.

**Alternativas.** (a) Migrar a VPS. (b) Servicio externo tipo Pusher o Ably. (c) Sondeo AJAX periódico.

**Decisión.** Sondeo cada 5 segundos, activo solo dentro de una sesión abierta y con la pestaña visible.

**Fundamento.** El VPS encarece y complica la administración sin beneficio para 70 usuarios. El servicio externo implicaría que los mensajes de los participantes pasen por un tercero, lo que obliga a declararlo como encargado de tratamiento en el expediente de ética y en el consentimiento informado — complejidad regulatoria a cambio de una mejora que nadie percibiría.

El precedente es sólido: **el módulo de chat de Moodle no usa WebSockets**, sino que los clientes consultan al servidor periódicamente. Por eso funciona en cualquier hosting.

Carga calculada: dos sesiones simultáneas de cinco participantes con sondeo cada 5 segundos son 2 peticiones por segundo. El plan dispone de 40 procesos PHP.

**Costo asumido.** Latencia de hasta 5 segundos en el chat. Irrelevante para el caso de uso.

---

## D-N · Telemetría en lotes

**Restricción.** El plan de hosting permite 128 operaciones de entrada/salida por segundo.

**Decisión.** Los eventos se acumulan en el cliente y se envían cada 30 segundos, o al llegar a 20 eventos, o al cerrar la pestaña mediante `sendBeacon`. En el servidor se insertan en bloques de 100 filas.

**Fundamento.** Una inserción por evento suelto saturaría el límite de operaciones y degradaría la aplicación para todos. `sendBeacon` garantiza el envío al cerrar la pestaña de golpe, que es cuando se pierden los eventos que indican si el participante abandonó o terminó.

**Costo asumido.** Un evento puede tardar hasta 30 segundos en persistirse. Irrelevante para el análisis, que trabaja con agregados diarios.

---

## D-O · Temporizador en el cliente

**Decisión.** El Pomodoro corre en el navegador, con sincronización al servidor al iniciar, pausar y terminar. El servidor valida que el intervalo declarado sea coherente.

**Fundamento.** Un temporizador en servidor obligaría a que todos los participantes activos consultaran constantemente, multiplicando la carga sobre un único núcleo de CPU. La validación del intervalo detecta manipulación sin coste de rendimiento.

**Costo asumido.** Un usuario con conocimientos técnicos podría falsear una sesión. La validación del 95% del tiempo declarado limita el margen, y la telemetría permitiría detectar patrones anómalos en el análisis.

---

## D-P · Recursos gráficos en el propio servidor

**Contexto.** Se evaluó alojar los 400 recursos del avatar en Cloudflare R2.

**Decisión.** Se quedan en el hosting.

**Fundamento.** El conjunto pesa unos 42 MB frente a 100 GB disponibles. El servidor está en São Paulo, con latencia de 40 a 60 milisegundos hasta Cajamarca. El servidor web sirve archivos estáticos sin consumir procesos PHP. R2 aportaría unos 20 milisegundos de mejora a cambio de una dependencia externa adicional.

**Costo asumido.** Ninguno relevante a esta escala. Si los recursos crecieran mucho en fase 2, se reevalúa.

---

## D-Q · Monolito modular con arquitectura limpia

**Alternativas.** (a) Laravel convencional por capas técnicas. (b) Microservicios. (c) Monolito modular con dominio aislado.

**Decisión.** Monolito modular. Un despliegue, una base de datos, módulos con fronteras explícitas que se comunican solo por eventos.

**Fundamento.** Tres razones concretas:

Primera, **el alcance va a cambiar**. El documento de requisitos aún no está cerrado, y las conclusiones del análisis de encuestas pueden añadir o quitar módulos. Las fronteras explícitas permiten hacerlo sin efectos colaterales.

Segunda, **varias inteligencias artificiales distintas van a escribir código en el repositorio**. Las fronteras entre módulos reducen que un cambio hecho por una rompa lo que hizo otra.

Tercera, y la más importante, **aislar la telemetría como módulo con contratos propios impide que un cambio en otro módulo rompa el registro de eventos**.

Los microservicios se descartaron por absurdos a esta escala y en este hosting.

**Costo asumido.** Más ceremonia inicial: interfaces, mapeadores, eventos. Se acepta porque el módulo que más se beneficia es el crítico.

---

# 6. Decisiones de protección de datos

## D-R · Seudonimización desde el diseño

**Decisión.** La telemetría solo contiene el identificador numérico del usuario. La tabla que vincula identidad real con código de participante vive separada, con acceso restringido, y nunca aparece en una exportación.

**Fundamento.** Cumplimiento de la Ley N.º 29733 y su reglamento DS 016-2024-JUS, vigente desde marzo de 2025. Además, una vez desvinculada de la identidad, la telemetría deja de ser dato personal y se convierte en dato de investigación anonimizado, lo que permite conservarla legítimamente aunque un participante se retire.

---

## D-S · Cifrado del diario de bienestar

**Decisión.** El contenido del diario se cifra en reposo. No aparece en telemetría (solo la puntuación de ánimo y la longitud del texto), ni en registros de la aplicación, ni en exportaciones. El panel de administración no puede leerlo.

**Fundamento.** Es dato sensible de salud mental. La confidencialidad no es solo una obligación legal: si los participantes sospechan que el equipo lee sus entradas, dejarán de escribir con honestidad y la variable pierde valor.

---

## D-T · Protocolo de derivación en el asistente de IA

**Decisión.** Ante señales de crisis, el sistema no envía la consulta al proveedor de IA. Responde con un mensaje de contención aprobado y deriva al servicio de bienestar psicológico de la institución. No se notifica a los administradores.

**Fundamento.** Requisito del expediente de comité de ética y alineado con ISO/IEC 23894 sobre gestión de riesgos de inteligencia artificial. La no notificación protege la confidencialidad: avisar al equipo desincentivaría el uso honesto de la herramienta.

---

# 7. Trazabilidad: decisión → pilar del estudio

| Decisión | Pilar 1 (66 días) | Pilar 2 (identidad) | Pilar 3 (triangulación) |
|---|:---:|:---:|:---:|
| D-A Topes de experiencia | | | ● |
| D-B Ranking instrumentado | | ● | ● |
| D-C Sin monetización | ● | | |
| D-D Curva de niveles | | ● | ● |
| D-E Idempotencia | | | ● |
| D-F Logros sin comparación | | ● | |
| D-G Recuperación de rachas | ● | | |
| D-H Panel de solo lectura | | | ● |
| D-I Congelamiento | ● | | ● |
| D-J Villanos del diagnóstico | | ● | |
| D-K Campos cerrados | | ● | ● |
| D-N Telemetría en lotes | | | ● |
| D-R Seudonimización | | | ● |

---

# 8. Limitaciones reconocidas

Un documento que solo justifica no es creíble. Estas son las debilidades asumidas, que deben declararse en el artículo:

| Limitación | Efecto | Mitigación |
|---|---|---|
| El temporizador corre en el cliente | Un usuario técnico podría falsear una sesión | Validación del 95% del intervalo; detección de patrones anómalos |
| Los topes truncan el extremo superior | Se pierde información del participante excepcional | Se registra `was_capped` para conocer su frecuencia |
| La racha admite días de gracia | Deja de ser días consecutivos estrictos | Cada uso se registra; la racha estricta es reconstruible |
| Hosting compartido | Sin control total del entorno | Copias diarias; incidencias en bitácora |
| Sondeo en vez de tiempo real | Latencia de hasta 5 segundos en el chat | Irrelevante para el caso de uso |
| Chat con moderación básica | Contenido inapropiado posible | Filtro de palabras, reportes, purga a 7 días |
| Muestra de una sola institución | Limita la generalización | Se declara explícitamente en el alcance |

---

# 9. Qué distingue este enfoque

El argumento que sostiene la originalidad técnica no es el conjunto de funciones —hay aplicaciones de hábitos con avatares, rachas y gamificación—, sino **la subordinación sistemática de las decisiones de ingeniería a la validez del instrumento de medición**.

Tres ejemplos concretos que un jurado puede verificar en el código:

**Se rechazaron funciones que mejoraban el producto porque contaminaban el experimento.** El modelo freemium tenía respaldo en la encuesta (84% declaró disposición a pagar) y se descartó por confusor.

**Se diseñaron mecánicas contra la intuición de producto para servir al análisis.** La curva de niveles está calibrada para que nadie llegue al máximo, no por dificultad artificial, sino para evitar un efecto techo en una variable del estudio.

**Se renunció a capacidades de administración para poder defender la integridad del dato.** El panel no puede editar experiencia ni telemetría, de modo que la imposibilidad técnica sustituye a la declaración de buena fe.

Esa inversión de prioridades —el instrumento por encima del producto— es lo que permite defender que los datos obtenidos miden lo que dicen medir. Y es reproducible: cualquier equipo puede aplicar la misma pregunta de la sección 1 a sus propias decisiones.

---

## Referencias internas

| Documento | Contenido |
|---|---|
| `00-ARQUITECTURA.md` | Estructura y capas que materializan D-Q |
| `02-TELEMETRIA.md` | Catálogo de eventos, sostiene D-A, D-B, D-N |
| `03-GAMIFICACION.md` | Parámetros de D-A, D-D, D-F, D-G, D-J |
| `06-SEGURIDAD.md` | Implementación de D-H, D-R, D-S, D-T, D-U, D-V |
| `07-DEPLOY.md` | Restricciones que originan D-M, D-N, D-O, D-P |
| `01-MODULOS.md` | Casos de uso de Identity que materializan D-U, D-V, D-W, D-X |

---

## D-U · Google OAuth como método de autenticación adicional

**Fecha:** 2026-07-28. **Solicitante:** usuario (marcó).

**Contexto:** La autenticación propia (email + contraseña) ya está implementada. El registro y login manual funciona. Se solicita agregar Google como alternativa para reducir fricción de entrada.

**Alternativas evaluadas:**

| Alternativa | Problema |
|---|---|
| Solo Google OAuth, sin credenciales propias | Crea dependencia de tercero para el inicio del estudio. Si Google revoca credenciales OAuth o hay un corte, los participantes no pueden acceder — inaceptable para una intervención de 66 días con fecha fija |
| Google OAuth reemplazando el flujo actual | Mismos riesgos de dependencia. Además, requeriría migrar los usuarios ya registrados |
| Google OAuth adicional (coexiste con el flujo actual) | ✅ **La elegida** |

**Decisión:** Google OAuth es un flujo **adicional y opcional**. El botón "Continuar con Google" aparece en las pantallas de login y registro junto al formulario existente, separado visualmente. Ambos flujos desembocan en el mismo resultado del dominio.

**Fundamento técnico:**

1. **`laravel/socialite`** para manejar el callback OAuth — no reimplementar el protocolo.
2. El flujo social **debe crear `Participant` y generar `participant_code`** igual que `RegisterUserUseCase`. La seudonimización es un pilar del estudio y no depende del método de autenticación. Si se salta este paso en el flujo OAuth, los datos de telemetría de ese usuario no tienen `participant_code` y quedan fuera del dataset.
3. La columna `google_id` se añade a la tabla `users` como `VARCHAR(100) NULL UNIQUE`. No es `NOT NULL` porque los usuarios con credenciales propias no lo tienen. No se guarda el access token de Google — no hay razón para ello y añade superficie de ataque.
4. Al hacer login con Google de un email que ya existe en `users` (registrado con contraseña), se vincula la cuenta (se rellena `google_id`) y se loguea. No se crean dos cuentas.

**Impacto en la validez del estudio:** neutro. El método de registro no correlaciona con ninguna variable del estudio. La asignación a grupo experimental/control sigue siendo aleatoria al momento de `CompleteProfile`.

**Costo asumido:**
- Dependencia de la disponibilidad de los servidores de Google durante el onboarding (no durante la intervención activa — una vez registrado, el login con Google requiere que Google responda, pero el usuario puede cambiarse a email+contraseña si lo pierde).
- Requiere configurar `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` en Hostinger, con la URL de callback registrada en Google Cloud Console apuntando al dominio de producción.
- El dominio debe tener SSL válido para que Google acepte el callback — ya está contemplado en `docs/07-DEPLOY.md`.

**Implementación pendiente (Fase 1):**
- Migración: `ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL UNIQUE AFTER password;`
- `SocialAuthController` con métodos `redirect()` y `callback()`
- Ruta `GET /auth/google/redirect` y `GET /auth/google/callback` en el grupo `web` del `IdentityServiceProvider` (con middleware `web` explícito — ver bug documentado en `docs/12-HISTORIAL.md`)
- `SocialRegisterUserUseCase` (o reutilizar `RegisterUserUseCase` con un `SocialUserDTO`) — **debe llamar `CreateParticipantUseCase` igual que el flujo normal**
- Botón en `Login.vue` y `Register.vue` cuando se haga el restyling de Fase 1

---

## D-V · Eliminación de la verificación de correo obligatoria (multi-institución, presupuesto de servidor)

**Fecha:** 2026-07-28. **Solicitante:** usuario (marcó).

**Contexto:** La muestra del estudio incluye estudiantes de múltiples institutos y universidades (públicas y privadas), no limitada exclusivamente a UPN. Actualmente no se dispone de presupuesto para contratar servicios de correo corporativo por dominio en Hostinger para cada entidad institucional (contando únicamente con un servidor de correo transaccional del dominio `@soltecto.com`). Requerir verificación obligatoria por enlace de correo (`MustVerifyEmail`) generaría altas tasas de rebote y bloqueo en spam de correos institucionales, impidiendo el ingreso de los participantes.

**Decisión:** Desactivar la verificación de correo por email (`MustVerifyEmail`). Tras registrarse o iniciar sesión con sus credenciales, los usuarios ingresan de forma inmediata a la plataforma y continúan el flujo de onboarding/intervención sin bloqueos de middleware `verified`.

---

## D-W · Especificaciones UX de login, registro, términos y condiciones, y mapeo género-avatar

**Fecha:** 2026-07-28. **Solicitante:** usuario (marcó).

**Contexto:** Se requiere estandarizar visual y funcionalmente las pantallas públicas de autenticación y consentimiento para maximizar la conversión y asegurar el cumplimiento ético del estudio.

**Decisiones de interfaz:**
1. **Login**:
   - Layout: Hero visual (`login-hero.webp`) + Logo (`logo.webp`).
   - Saludo: *"Bienvenido a Epycus."* acompañado de frase motivadora (*"Transforma tu rutina y domina tus metas de estudio."*).
   - Acciones: Correo, Contraseña, enlace "¿Olvidaste tu contraseña?" (envío de enlace de restablecimiento), casilla "Mantener sesión iniciada", botón Ingresar y botón Continuar con Google.
2. **Registro**:
   - Campos: Nombre completo, correo, contraseña, fecha de nacimiento, género (Masculino / Femenino / Prefiero no decir), carrera.
   - **Mapeo Género → Avatar**: La opción *"Prefiero no decir"* asigna por defecto el avatar de género masculino (`m`), permitiendo al usuario modificarlo posteriormente en su perfil.
   - **Términos y Condiciones**: Checkbox obligatorio "Acepto los términos y condiciones" con enlace a la página `/terms` creada para el proyecto.
3. **Página /terms**: Se crea la ruta `/terms` y la vista `Terms.vue` que detalla los términos del servicio y del estudio.

---

## D-X · Flujo de registro con Google ("Casi listo") y cuestionario pre-uso futuro

**Fecha:** 2026-07-28. **Solicitante:** usuario (marcó).

**Contexto:** Se especifica el comportamiento cuando un nuevo usuario ingresa con Google OAuth y la inclusión futura de un cuestionario de entrada.

**Decisiones de arquitectura:**
1. **Pantalla "Casi listo" (Google OAuth)**: Cuando un usuario se registra vía Google y faltan datos clave del estudio, se muestra la pantalla *"Casi listo"* para completar: Nombre, Fecha de nacimiento, Género y Carrera.

---

## D-Y · Omisión de textos explícitos de intervención en la interfaz (uso natural)

**Fecha:** 2026-07-28. **Solicitante:** usuario (marcó).

**Contexto:** Para garantizar la validez ecológica de la investigación longitudinal de 66 días, los participantes no deben ser constantemente recordados de que forman parte de un "estudio" o "intervención" dentro de la interfaz cotidiana de la plataforma. El uso debe sentirse 100% natural, como cualquier otra aplicación comercial de hábitos y productividad.

**Decisión:**
1. **Interfaz Limpia y Natural**: Se omitirán términos como *"intervención"*, *"estudio"* o *"experimento"* en los textos visibles de la aplicación (subtítulos, dashboards, botones y módulos de contenido).
2. **Ubicación Exclusiva en Términos y Condiciones**: Toda la información detallada del protocolo de investigación, derechos del participante y consentimiento ético permanecerá exclusivamente en la página de Términos y Condiciones (`/terms`) enlazada durante el registro y login.

---

## D-Z · Calendar module con interfaz propia (desviación de los docs)

**Fecha:** 2026-07-29. **Solicitante:** usuario (marcó).

**Contexto:** `docs/01-MODULOS.md §15` define Calendar como un "servicio de calendario compartido" sin interfaz propia, que provee datos a Wellbeing, Missions y Dashboard. El usuario pidió expresamente un calendario unificado que muestre feriados, semanas de examen y misiones juntos.

**Alternativas.** (a) Mantener Calendar sin interfaz y que cada módulo tenga su propio calendario (missions.calendar, wellbeing.calendar, etc.). (b) Crear Calendar con interfaz propia y eliminar los calendarios individuales de otros módulos.

**Decisión.** Calendar module con interfaz propia (`/calendar`), implementando `CalendarReaderInterface` (el contrato de Shared) para que Wellbeing y otros módulos sigan pudiendo leer feriados sin acoplamiento. Missions ya no tiene su propio calendario.

**Fundamento.** El usuario prefiere un solo punto de entrada calendárico. La implementación de `CalendarReaderInterface` en el mismo módulo evita duplicar la lógica de feriados/exámenes. Missions se conecta a Calendar por inyección directa de `MissionRepositoryInterface`.

**Costo asumido:** Calendar deja de ser "sin interfaz" como dicen los docs — ese párrafo queda desactualizado. Wellbeing cuando se construya debe inyectar `CalendarReaderInterface` para marcar feriados, no duplicar lógica.

---

## D-AA · Drag-and-drop nativo HTML5 en subtareas (sin librería externa)

**Fecha:** 2026-07-29. **Solicitante:** ingeniería.

**Contexto:** Las subtareas de Misiones necesitan reordenarse por arrastre (`docs/01-MODULOS.md §4`). Las opciones disponibles son SortableJS, vue-draggable-plus o HTML5 Drag and Drop nativo.

**Alternativas.** (a) SortableJS — librería probada pero dependencia externa. (b) vue-draggable-plus — wrapper Vue, misma dependencia subyacente. (c) HTML5 Drag and Drop nativo — cero dependencias, soportado en todos los navegadores modernos.

**Decisión.** Implementar con HTML5 Drag and Drop nativo.

**Fundamento.** La funcionalidad es simple (reordenar una lista plana), no necesita animaciones complejas ni soporte táctil avanzado. Agregar una dependencia para esto no se justifica. El handle visual `⠿` da feedback suficiente.

**Costo asumido:** En dispositivos táctiles la experiencia de arrastre nativa es menos pulida que con SortableJS. Si en pruebas con usuarios resulta frustrante, se puede reemplazar sin cambiar el backend (el endpoint `reorderSubtasks` ya existe).

---

## D-AB · Session flash para XP en lugar de calcularlo desde el controlador

**Fecha:** 2026-07-29. **Solicitante:** ingeniería (bug).

**Contexto:** El método `MissionsController::complete()` intentaba leer `session()->pull('xp_awarded', 0)` para mostrar el XP ganado en el toast, pero ningún código escribía ese valor en sesión. Siempre mostraba "+0 XP" aunque el use case awardara XP real.

**Alternativas.** (a) Hacer que el use case retorne el valor y pasarlo desde el controlador. (b) Guardar en session flash dentro del use case.

**Decisión.** El use case escribe `session()->flash('xp_awarded', $xp)` antes de emitir el evento.

**Fundamento.** Coincide con el patrón del módulo Habits (`HabitsController::toggle()` mide XP por diferencia de totales). El flash sobrevive exactamente un request y se limpia solo, sin riesgo de contaminar otros flujos.

**Costo asumido:** El use case now tiene un side effect de sesión, que no es ideal para un caso de uso puro. Alternativa más limpia sería retornar el DTO, pero requeriría cambiar la firma del execute() — y eso cascaría en ToggleSubtaskUseCase que también llama a CompleteMissionUseCase.


