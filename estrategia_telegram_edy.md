# 📱 Estrategia de Negocio y Producto: Edy x Telegram

La integración de Epycus con Telegram no solo es una mejora técnica, sino un **salto cuántico en el modelo de negocio**. Convierte a Epycus de ser "una página web de productividad" a ser un **"Tutor de IA omnipresente"**.

## 1. El Concepto: Interacción Conversacional
Con Telegram, Edy actúa de forma proactiva y conversacional, capaz de registrar datos en la base de datos de Epycus sin que el usuario abra la web.

### Caso de Uso Estrella: Creación de Misiones (Paso a Paso)
El usuario no necesita llenar formularios, solo habla de forma natural:

1. **Usuario:** *"Edy, tengo examen parcial el próximo viernes."*
2. **Edy (Bot):** *"¡Anotado! 📝 Un examen siempre es importante (Prioridad Q1 o Q2). ¿De qué curso es el examen y a qué hora?"*
3. **Usuario:** *"Es de Redes Inalámbricas a las 10:00 am."*
4. **Edy (Bot):** *"¡Perfecto! Acabo de registrar la Misión: 'Examen Parcial - Redes Inalámbricas' para el Viernes a las 10:00 am. ¿Quieres que te arme un plan de estudio dividido en Pomodoros para llegar preparado?"*

*Todo este flujo inserta registros reales en las tablas `missions` y `courses` mediante la API del servidor.*

### Otros Casos de Uso Potentes
* **Recordatorios Activos:** *"Ey, he notado que tu misión de Tesis está vencida desde ayer. ¿Hacemos 25 minutos ahora para avanzar un poco?"*
* **Cierre del Día (Daily Recap):** A las 9:00 PM Edy envía un mensaje: *"Hoy completaste 2 misiones, lograste 90 minutos de foco y bebiste tus 8 vasos de agua. ¡Ganaste 50 monedas! 🪙 Descansa."*
* **Botón de Pánico (Salud Mental):** Si el estudiante escribe "Estoy colapsando", el Bot detecta la crisis y responde con ejercicios de respiración o líneas de ayuda, registrando el estado de ánimo bajo en el Diario (`journal_entries`).

---

## 2. Modelo de Suscripción (¿Cuánto cobrar?)

El servicio de **Edy por Telegram** debe ser considerado un servicio **Premium**. Los estudiantes están dispuestos a pagar por conveniencia y por sentir que tienen un "coach privado".

### Propuesta de Precios (SaaS - Software as a Service)
* **Plan Gratuito (Epycus Web):** Acceso a todos los módulos web. Edy IA web con límite de 5 consultas al día.
* **Plan "Epycus Pro" (Mensual):** **$3.99 a $5.99 USD / mes**.
  * Acceso ilimitado al Bot de Telegram 24/7.
  * Consultas ilimitadas de IA.
  * Creación de misiones y hábitos por voz/texto en Telegram.
  * Resúmenes diarios en PDF generados por la IA.
* **Plan "Semestre" (6 Meses):** **$19.99 USD** (Ahorran 15%, ideal para el ciclo universitario).

*Nota psicológica del precio:* El costo de $4.99 USD equivale al precio de un café de Starbucks al mes o a una suscripción estudiantil de Spotify. Es un precio de "micropago" muy accesible para universitarios.

---

## 3. Costos Operativos para Ti (Como creador)

Si cobras **$5 USD/mes** por usuario, tus costos de mantener esta función son mínimos:

1. **Hostinger / Servidor:** $0 (Ya lo pagas en tu plan anual/premium). Telegram usa webhooks que no sobrecargan el servidor.
2. **API de Telegram:** $0. Es completamente gratuita de por vida.
3. **DeepSeek v4-flash (API de IA):** El modelo `flash` es extremadamente barato (centavos por cada millón de tokens). Un usuario intenso podría costarte entre **$0.05 y $0.15 centavos de dólar al mes** en consumo de IA.
4. **Pasarela de Pago (Stripe/PayPal):** Se quedan aproximadamente con un 3% a 5% de cada transacción. (Ej. de $5 USD, te quedan ~$4.70 USD limpios).

**Margen de ganancia:** Tu margen de ganancia operativa supera el **90%**. Con 100 usuarios suscritos, estarías facturando ~$500 USD mensuales recurrentes con un costo menor a $15 USD.

---

## 4. Hoja de Ruta para Implementarlo

1. **Crear el Bot:** Hablar con `@BotFather` en Telegram y obtener el Token.
2. **Configurar el Webhook:** Crear un archivo en Epycus (`TelegramController.php`) que reciba los mensajes.
3. **Conectar Identidades:** Cuando el usuario entra a la web de Epycus, ve un botón: *"Conectar con Telegram"*. Se genera un código único para que Epycus sepa qué Telegram ID pertenece a qué cuenta.
4. **Programar los Flujos:** Darle instrucciones a DeepSeek (Prompt) para que devuelva un formato JSON (ej. `{"action": "create_mission", "title": "...", "date": "..."}`) cuando detecte que el usuario quiere programar una tarea. Tu backend lee el JSON y crea la misión.
