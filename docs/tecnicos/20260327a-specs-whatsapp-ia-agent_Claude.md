
ESPECIFICACIONES TÉCNICAS
Agente WhatsApp IA

Jaraba Impact Platform
Integración WhatsApp Cloud API + Claude Anthropic + CRM
para el Programa Andalucía +ei — 2ª Edición

Plataforma de Ecosistemas Digitales S.L.
José Jaraba Muñoz, CEO & Fundador

Documento de implementación para Claude Code — Marzo 2026 — Confidencial
 
ÍNDICE
1. Contexto y Alcance	1
1.1. Problema que resuelve	1
1.2. Objetivos técnicos	1
1.3. Stack tecnológico	1
1.4. Dependencias con documentos existentes	1
2. Arquitectura del Sistema	1
2.1. Capas del sistema	1
2.2. Flujo de un mensaje entrante	1
2.3. Flujo de un mensaje saliente (iniciado por el sistema)	1
3. Modelo de Datos	1
3.1. Tabla: wa_conversations	1
3.2. Tabla: wa_messages	1
3.3. Tabla: wa_templates	1
3.4. Índices y constraints adicionales	1
4. Contratos API	1
4.1. Webhook de recepción (Meta → Drupal)	1
4.2. Payload entrante (estructura relevante)	1
4.3. API de envío de mensajes (Drupal → Meta)	1
4.4. API interna de Claude (clasificación y conversación)	1
5. System Prompts de los Agentes IA	1
5.1. Prompt de clasificación (Claude Haiku)	1
5.2. Prompt del agente de participantes (Claude Sonnet)	1
5.3. Prompt del agente de negocios (Claude Sonnet)	1
5.4. Construcción del historial de conversación	1
6. Templates de WhatsApp (para aprobación Meta)	1
7. Lógica de Escalación	1
7.1. Detección de escalación	1
7.2. Reglas automáticas (sin depender de la IA)	1
7.3. Flujo de escalación	1
8. Seguridad y Cumplimiento RGPD	1
8.1. Seguridad de la integración	1
8.2. RGPD	1
9. Requisitos Funcionales	1
9.1. Módulo WhatsApp (backend)	1
9.2. Panel de gestión (frontend en Drupal)	1
9.3. Integración con CRM existente	1
9.4. Estimación total de esfuerzo	1
10. Estructura de Archivos del Módulo	1
11. Configuración y Variables de Entorno	1
11.1. Variables de entorno (NO en código)	1
11.2. Configuración Drupal (editable desde panel admin)	1
12. Testing y Plan de Despliegue	1
12.1. Estrategia de testing	1
12.2. Plan de despliegue	1
12.3. Costes operativos estimados	1
13. Extensibilidad: Estrategia B2G	1
13.1. Multi-tenancy	1
13.2. Propuesta de valor B2G	1
14. Próximos Pasos	1

 
1. Contexto y Alcance
Este documento especifica la arquitectura, modelo de datos, contratos API, system prompts, lógica de escalación, seguridad y plan de despliegue para integrar un Agente WhatsApp IA en Jaraba Impact Platform. El agente gestiona automáticamente las conversaciones de captación del Programa Andalucía +ei, tanto para participantes potenciales como para negocios piloto.

1.1. Problema que resuelve
El protocolo de gestión de leads del programa exige respuesta en menos de 2 horas tras la recepción de un formulario. Con 45 plazas de participantes y un objetivo de 50-80 negocios piloto, el volumen de conversaciones simultáneas puede superar la capacidad de respuesta manual del equipo (1-2 personas). El agente IA resuelve esto respondiendo instantáneamente al 80% de las consultas y escalando el 20% restante a humanos con contexto completo.

1.2. Objetivos técnicos
•	Respuesta automática en menos de 5 segundos al 80% de las consultas entrantes por WhatsApp.
•	Clasificación automática del lead en participante / negocio / otro con precisión superior al 95%.
•	Creación automática de lead en el CRM de la plataforma con historial completo de la conversación.
•	Escalación inteligente a humano cuando el agente detecta que no puede resolver la consulta.
•	Panel de supervisión en tiempo real para el equipo del programa.
•	Cumplimiento RGPD: consentimiento, cifrado, derecho de acceso y supresión.
•	Arquitectura extensible: reutilizable para otros programas y entidades colaboradoras del PIL (estrategia B2G).

1.3. Stack tecnológico
Componente	Tecnología	Versión/Detalle
Backend	Drupal	11.x con módulo custom ecosistema_jaraba_core
Módulo WhatsApp	Nuevo submódulo	ecosistema_jaraba_whatsapp
API de mensajería	WhatsApp Cloud API	Meta Graph API v21.0
API de IA	Claude (Anthropic)	claude-sonnet-4-20250514 (conversaciones) / claude-haiku-4-5-20251001 (clasificación)
Base de datos	MySQL/MariaDB	Integrada en Drupal
Cola de mensajes	Drupal Queue API	Con cron cada 30 segundos
Hosting	IONOS	Servidores UE (Alemania)
Dominio webhook	plataformadeecosistemas.com	HTTPS obligatorio

1.4. Dependencias con documentos existentes
Documento	Dependencia
Manual Operativo Final	Mensajes WhatsApp WA-P1/P2/P3 y WA-N1/N2/N3 como base de los templates
Specs Jaraba Impact Platform	Entidades Participante, NegocioProspectado, CRM. Requisitos CRM-01 a CRM-07
Estrategia Prospección Clientes Piloto	Embudo de 6 fases, criterios de clasificación
Catálogo de Servicios	Información de los 5 packs que el agente debe conocer
Diseño Formativo v2 Corregido	Estructura del programa, requisitos, horarios, modalidades

 
2. Arquitectura del Sistema
El sistema se estructura en 5 capas, desde el canal externo (WhatsApp) hasta la persistencia en base de datos. Cada capa tiene responsabilidad única y contrato definido.

2.1. Capas del sistema
Capa	Nombre	Responsabilidad	Tecnología
1	Canal	Recibir y enviar mensajes WhatsApp	WhatsApp Cloud API (webhook entrante + API saliente)
2	Ingestión	Validar firma, parsear payload, encolar	Drupal Controller + Queue API
3	Clasificación	Determinar tipo de lead y contexto	Claude Haiku (clasificación rápida, <1s)
4	Agente	Generar respuesta contextual con IA	Claude Sonnet (conversación, <5s)
5	Persistencia	Almacenar conversación, crear/actualizar lead en CRM	MySQL vía Drupal Entity API

2.2. Flujo de un mensaje entrante
1.	Usuario envía mensaje de WhatsApp al número del programa (623 17 43 04).
2.	Meta reenvía el mensaje al webhook: POST plataformadeecosistemas.com/api/whatsapp/webhook.
3.	El Controller de Drupal valida la firma HMAC-SHA256 del payload con el App Secret.
4.	Se parsea el payload y se extrae: phone_number_id, from, timestamp, message.text.body (o tipo interactivo).
5.	Se busca en la tabla wa_conversations si existe una conversación activa con ese número. Si no existe, se crea.
6.	El mensaje se añade al historial de la conversación (wa_messages).
7.	Se encola un job WhatsAppProcessMessageJob en la cola whatsapp_process.
8.	El worker de la cola (cron cada 30s o event-driven con Drupal hook) procesa el job.
9.	Si es el primer mensaje de la conversación: se envía el historial a Claude Haiku para clasificación (participante/negocio/otro). Se almacena la clasificación en wa_conversations.lead_type.
10.	Se construye el prompt del agente con: system prompt específico del tipo de lead + historial completo de la conversación + contexto del CRM (si el número ya está registrado).
11.	Se envía a Claude Sonnet. Se recibe la respuesta.
12.	Se evalúa si la respuesta contiene un flag de escalación (el system prompt instruye al agente a devolver [ESCALATE] cuando no puede resolver).
13.	Si no hay escalación: se envía la respuesta al usuario vía WhatsApp Cloud API (POST /messages). Se almacena en wa_messages.
14.	Si hay escalación: se notifica al equipo (email + notificación en dashboard + WhatsApp al número de José) con el resumen del contexto generado por la IA.
15.	Se actualiza o crea el lead en el CRM (entidades Participante o NegocioProspectado según clasificación).

2.3. Flujo de un mensaje saliente (iniciado por el sistema)
Cuando un formulario web (participante o negocio piloto) se envía, el sistema dispara automáticamente el primer mensaje de WhatsApp usando un template pre-aprobado por Meta. Este flujo usa la WhatsApp Cloud API en modo template (no conversacional) y requiere que el usuario haya dado su número de teléfono en el formulario.
16.	El hook de Drupal form_submit del formulario de participantes/negocios dispara un evento WhatsAppSendTemplateEvent.
17.	El subscriber del evento busca el template adecuado según el tipo de formulario.
18.	Se envía el template vía WhatsApp Cloud API con las variables personalizadas (nombre, etc.).
19.	Se crea la conversación en wa_conversations con estado initiated_by_system.
20.	A partir del primer mensaje que el usuario responda, la conversación cambia a estado active y entra en el flujo estándar del agente IA.

 
3. Modelo de Datos
Se añaden 3 tablas nuevas al esquema de la plataforma, dentro del submódulo ecosistema_jaraba_whatsapp. No se modifican tablas existentes; la relación con el CRM es via foreign keys a las entidades de Drupal.

3.1. Tabla: wa_conversations
Almacena cada conversación única entre el agente y un número de teléfono.
Campo	Tipo	Descripción
id	BIGINT AUTO_INCREMENT PK	Identificador único
wa_phone	VARCHAR(20) NOT NULL INDEX	Número WhatsApp del usuario (formato E.164: +34623...)
lead_type	ENUM('participante','negocio','otro','sin_clasificar')	Clasificación del lead por IA
lead_confidence	DECIMAL(3,2)	Confianza de la clasificación (0.00-1.00)
status	ENUM('initiated_by_system','active','escalated','closed','spam')	Estado de la conversación
drupal_entity_type	VARCHAR(50) NULL	Tipo de entidad CRM vinculada (node type)
drupal_entity_id	BIGINT NULL	ID de la entidad CRM vinculada
assigned_to	BIGINT NULL FK users	Usuario del equipo asignado (NULL = agente IA)
escalation_reason	TEXT NULL	Motivo de escalación generado por IA
escalation_summary	TEXT NULL	Resumen del contexto para el humano
message_count	INT DEFAULT 0	Contador de mensajes en la conversación
last_message_at	DATETIME	Último mensaje recibido o enviado
utm_source	VARCHAR(100) NULL	Fuente UTM si viene de formulario
utm_campaign	VARCHAR(100) NULL	Campaña UTM
utm_content	VARCHAR(100) NULL	Contenido UTM
created_at	DATETIME	Fecha de creación
updated_at	DATETIME	Fecha de actualización

3.2. Tabla: wa_messages
Almacena cada mensaje individual dentro de una conversación.
Campo	Tipo	Descripción
id	BIGINT AUTO_INCREMENT PK	Identificador único
conversation_id	BIGINT NOT NULL FK INDEX	Referencia a wa_conversations
wa_message_id	VARCHAR(100) NULL UNIQUE	ID del mensaje en WhatsApp (wamid)
direction	ENUM('inbound','outbound')	Entrante o saliente
sender_type	ENUM('user','agent_ia','agent_human','system')	Quién envió el mensaje
message_type	ENUM('text','template','interactive','image','document','audio','reaction')	Tipo de mensaje WhatsApp
body	TEXT NOT NULL	Contenido del mensaje
template_name	VARCHAR(100) NULL	Nombre del template si aplica
template_vars	JSON NULL	Variables del template
ai_model	VARCHAR(50) NULL	Modelo IA usado (claude-sonnet-4, claude-haiku-4-5)
ai_tokens_in	INT NULL	Tokens de entrada consumidos
ai_tokens_out	INT NULL	Tokens de salida consumidos
ai_latency_ms	INT NULL	Latencia de la llamada IA en ms
delivery_status	ENUM('sent','delivered','read','failed')	Estado de entrega WhatsApp
created_at	DATETIME INDEX	Fecha del mensaje

3.3. Tabla: wa_templates
Registro de los templates de WhatsApp pre-aprobados por Meta. Se sincronizan manualmente tras la aprobación.
Campo	Tipo	Descripción
id	INT AUTO_INCREMENT PK	Identificador
template_name	VARCHAR(100) UNIQUE	Nombre del template en Meta (ej: bienvenida_participante)
language	VARCHAR(10) DEFAULT 'es'	Idioma (es, en)
category	ENUM('marketing','utility','authentication')	Categoría Meta (afecta precio)
status	ENUM('approved','pending','rejected')	Estado de aprobación en Meta
header_type	ENUM('none','text','image','document') DEFAULT 'none'	Tipo de cabecera
body_text	TEXT	Texto del body con placeholders {{1}}, {{2}}...
footer_text	VARCHAR(60) NULL	Texto del footer (máx 60 chars)
buttons	JSON NULL	Definición de botones (CTA, quick reply)
variables_schema	JSON	Esquema de las variables esperadas [{name, type, example}]
meta_template_id	VARCHAR(50) NULL	ID del template en Meta Business Manager
created_at	DATETIME	Fecha de creación

3.4. Índices y constraints adicionales
SQL — Índices compuestos
CREATE INDEX idx_wa_conv_status_type ON wa_conversations(status, lead_type);
CREATE INDEX idx_wa_conv_last_msg ON wa_conversations(last_message_at DESC);
CREATE INDEX idx_wa_msg_conv_created ON wa_messages(conversation_id, created_at);
CREATE INDEX idx_wa_msg_direction ON wa_messages(conversation_id, direction);

-- Constraint: máximo 1 conversación activa por número
-- Se gestiona en la lógica de aplicación (PHP), no en SQL,
-- porque un número puede tener conversaciones cerradas + 1 activa.

 
4. Contratos API

4.1. Webhook de recepción (Meta → Drupal)
Propiedad	Valor
Endpoint	POST /api/whatsapp/webhook
Verificación (GET)	GET /api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=TOKEN&hub.challenge=CHALLENGE
Autenticación	HMAC-SHA256 del body con App Secret en header X-Hub-Signature-256
Content-Type	application/json
Timeout	5 segundos (Meta reenvía si no recibe 200 en 5s)
Respuesta esperada	HTTP 200 inmediatamente (procesamiento asíncrono via queue)

Crítico: responder 200 ANTES de procesar
Meta espera un HTTP 200 en menos de 5 segundos. Si el servidor tarda más (por ejemplo, esperando la respuesta de Claude), Meta considerará el webhook como fallido y lo reenviará.
La solución: el Controller responde 200 inmediatamente tras validar la firma y encolar el mensaje. TODO el procesamiento (clasificación, generación de respuesta, envío) ocurre en el worker de la cola.

4.2. Payload entrante (estructura relevante)
JSON — Mensaje de texto entrante (simplificado)
{
  "object": "whatsapp_business_account",
  "entry": [{
    "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
    "changes": [{
      "value": {
        "messaging_product": "whatsapp",
        "metadata": {
          "display_phone_number": "34623174304",
          "phone_number_id": "PHONE_NUMBER_ID"
        },
        "contacts": [{ "profile": { "name": "María" }, "wa_id": "34612345678" }],
        "messages": [{
          "from": "34612345678",
          "id": "wamid.ABGGFlA5FRSfAgo6...",
          "timestamp": "1711000000",
          "type": "text",
          "text": { "body": "Hola, me interesa el programa de empleo" }
        }]
      },
      "field": "messages"
    }]
  }]
}

4.3. API de envío de mensajes (Drupal → Meta)
Propiedad	Valor
Endpoint	POST https://graph.facebook.com/v21.0/{phone_number_id}/messages
Autenticación	Bearer {WHATSAPP_ACCESS_TOKEN} en header Authorization
Content-Type	application/json
Rate limit	80 mensajes/segundo por número (más que suficiente)

JSON — Envío de mensaje de texto
{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "to": "34612345678",
  "type": "text",
  "text": { "body": "Hola María! Gracias por tu interés..." }
}

JSON — Envío de template
{
  "messaging_product": "whatsapp",
  "to": "34612345678",
  "type": "template",
  "template": {
    "name": "bienvenida_participante",
    "language": { "code": "es" },
    "components": [{
      "type": "body",
      "parameters": [{ "type": "text", "text": "María" }]
    }]
  }
}

4.4. API interna de Claude (clasificación y conversación)
Se utilizan dos modelos diferentes para optimizar coste y latencia:
Función	Modelo	Max tokens	Temperatura	Latencia esperada	Coste/llamada
Clasificación de lead	claude-haiku-4-5-20251001	100	0.0	<1 segundo	~0,001€
Conversación agente	claude-sonnet-4-20250514	500	0.3	2-4 segundos	~0,005€
Resumen de escalación	claude-haiku-4-5-20251001	300	0.0	<1 segundo	~0,002€

 
5. System Prompts de los Agentes IA
Estos prompts son el alma del agente. Definen su personalidad, conocimiento, límites y comportamiento de escalación. Están diseñados para ser inyectados como system message en cada llamada a la API de Claude.

5.1. Prompt de clasificación (Claude Haiku)
Objetivo: clasificar en <1s con >95% precisión
Se envía el primer mensaje del usuario (o los primeros 3 si llegan rápido) a Haiku para determinar el tipo de lead antes de activar el agente conversacional completo.

System Prompt — Clasificador (Haiku)
Eres un clasificador de leads del Programa Andalucía +ei.
Analiza el mensaje del usuario y devuelve SOLO un JSON válido, sin nada más.

Categorías:
- participante: persona desempleada interesada en formación/empleo/emprendimiento
- negocio: dueño/a de negocio interesado en servicios digitales o prueba gratuita
- otro: consulta general, spam, número equivocado

Pistas para 'participante': desempleo, paro, busco trabajo, formación, curso gratuito,
emprender, cambiar situación, me interesa el programa, plazas, 528 euros, SAE

Pistas para 'negocio': tengo un bar/restaurante/tienda, necesito redes sociales,
mi negocio, reseñas, página web, presencia online, prueba gratuita, cliente piloto

Formato de respuesta (estricto):
{"type":"participante|negocio|otro","confidence":0.95,"reason":"breve explicación"}

5.2. Prompt del agente de participantes (Claude Sonnet)
System Prompt — Agente Participantes
Eres el asistente virtual del Programa Andalucía +ei, un programa GRATUITO de la Junta de
Andalucía cofinanciado por la UE para ayudar a personas desempleadas a conseguir empleo o
emprender con inteligencia artificial.

DATOS DEL PROGRAMA:
- 45 plazas, 2ª Edición, Sevilla y Málaga
- 100% gratuito. Incentivo de 528€ por participar
- 10h orientación + 50h formación (presencial + online) + 40h acompañamiento (12 meses)
- Horario habitual: 9:30 a 13:30, sesiones presenciales en la capital de provincia
- 1ª Edición: 46% de inserción laboral (23 de 50 participantes)
- Requisitos: estar inscrito en el SAE + pertenecer a colectivo vulnerable
  (desempleo larga duración, >45 años, discapacidad ≥33%, migrante, exclusión social, RAI)
- 5 caminos profesionales (packs): Contenido Digital (250€/mes), Asistente Virtual (200€/mes),
  Presencia Online (150€/mes), Tienda Digital (300€/mes), Community Manager (200€/mes)
- Con 3-4 clientes puedes vivir de esto, sin oficina, sin inversión
- Solicitar: plataformadeecosistemas.com/andalucia-ei/solicitar

TU PERSONALIDAD:
- Cercano/a, cálido/a, motivador/a. Tuteas siempre.
- Usas un lenguaje sencillo, sin jerga técnica.
- Respondes en 2-4 frases máximo. WhatsApp no es para párrafos largos.
- Usas emojis con moderación (1-2 por mensaje, no más).
- Si el usuario tiene dudas, resuelves la duda y siempre terminas con una pregunta
  o una invitación a la acción.

REGLAS ESTRICTAS:
- NUNCA inventes datos. Si no sabes algo, di que lo consultas y se lo confirmas.
- NUNCA prometas plazas. Di que «las plazas se asignan por idoneidad».
- NUNCA preguntes datos sensibles (DNI, dirección exacta, datos médicos) por WhatsApp.
- Si el usuario pregunta algo fuera del programa, redirige amablemente.
- Si el usuario muestra interés real, guíale al formulario web.
- Si el usuario muestra frustración, resistencia o una situación compleja, ESCALA.

ESCALACIÓN:
Cuando no puedas resolver la consulta o detectes una situación sensible, responde
normalmente al usuario (no le digas que escalas) y añade AL FINAL de tu respuesta
la cadena exacta: [ESCALATE:motivo breve]
Ejemplos de cuándo escalar:
- Más de 3 intercambios sin conversión
- Situación personal compleja (discapacidad, violencia, etc.)
- Solicitud de reunión presencial
- Queja o problema técnico
- Negociación de condiciones especiales
- Tema fuera del programa que no puedes redirigir

5.3. Prompt del agente de negocios (Claude Sonnet)
System Prompt — Agente Negocios
Eres el asistente virtual del Programa Andalucía +ei. Hablas con dueños/as de negocios
locales de Sevilla y Málaga a los que ofrecemos un servicio de digitalización GRATUITO
durante 2-4 semanas, sin compromiso.

DATOS DE LA OFERTA:
- Prueba gratuita de 2-4 semanas de servicio digital profesional
- Sin compromiso, sin permanencia, sin coste
- Programa de la Junta de Andalucía cofinanciado por la UE
- Servicios disponibles: gestión de redes sociales, creación/mejora de web,
  gestión de reseñas Google, asistencia administrativa digital, tienda online
- El servicio lo ejecuta un profesional formado en IA, supervisado por expertos
- Objetivo: demostrar el valor de la digitalización para su negocio
- Si le gusta, puede contratar el servicio (150-300€/mes según pack)
- Si no le gusta, no pasa nada. Sin compromiso real.
- Solicitar: plataformadeecosistemas.com/andalucia-ei/negocio-piloto

TU PERSONALIDAD:
- Profesional pero cercano. Tratas de USTED (son dueños de negocio).
- Lenguaje directo, sin rodeos. El tiempo del comerciante es oro.
- Respondes en 2-3 frases máximo.
- Enfocas en el beneficio concreto para SU negocio, no en el programa.
- Si el negocio es hostelero, hablas de reseñas y fotos de platos.
  Si es comercio, hablas de tienda online y visibilidad en Google.

REGLAS ESTRICTAS:
- NUNCA presiones. Si dicen que no, agradece y cierra amablemente.
- NUNCA prometas resultados concretos («le triplicamos las ventas»).
- Sí puedes mencionar el dato de +35% visitas por responder reseñas.
- Si muestran interés, guía al formulario web o a agendar una visita/videollamada.
- Ofrece preparar una propuesta personalizada en 2-3 días.

ESCALACIÓN: mismas reglas que el agente de participantes. Usa [ESCALATE:motivo].
Además, escala siempre si:
- El negocio tiene más de 10 empleados (es grande para el programa)
- Solicita un servicio que no está en los 5 packs
- Quiere negociar un precio especial

5.4. Construcción del historial de conversación
Cada llamada a Claude Sonnet incluye el historial completo de la conversación en formato de mensajes alternados user/assistant. Si el historial supera los 4.000 tokens, se truncan los mensajes más antiguos manteniendo siempre el primer mensaje (para preservar el contexto inicial) y los últimos 6 mensajes.
Estructura de la llamada a Claude Sonnet
{
  "model": "claude-sonnet-4-20250514",
  "max_tokens": 500,
  "temperature": 0.3,
  "system": "<<SYSTEM PROMPT SEGÚN LEAD TYPE>>",
  "messages": [
    {"role":"user","content":"Hola, me interesa el programa de empleo"},
    {"role":"assistant","content":"¡Hola! 👋 Me alegra que te interese..."},
    {"role":"user","content":"¿Es gratuito?"},
    {"role":"assistant","content":"Sí, es 100% gratuito..."},
    {"role":"user","content":"¿Cómo me apunto?"}
  ]
}

 
6. Templates de WhatsApp (para aprobación Meta)
Los templates son mensajes pre-aprobados por Meta que el sistema puede enviar sin que el usuario haya iniciado la conversación. Son necesarios para los mensajes automáticos post-formulario. Deben enviarse a Meta Business Manager para aprobación (24-48h).

Template	Categoría	Trigger	Cuerpo (con variables)
bienvenida_participante	utility	Formulario de participante enviado	¡Hola {{1}}! 👋 Soy del Programa Andalucía +ei. Hemos recibido tu solicitud. En los próximos días te llamamos para una conversación breve (5 min). ¿Tienes alguna duda que pueda resolverte?
bienvenida_negocio	utility	Formulario negocio piloto enviado	Buenos días, {{1}}. Soy del Programa Andalucía +ei. Hemos recibido su solicitud para {{2}}. En 2-3 días le preparamos una propuesta personalizada. ¿Hay algo que le preocupe?
seguimiento_participante	utility	24h sin respuesta al primer msg	¡Hola {{1}}! Te escribimos ayer desde Andalucía +ei. Solo queríamos confirmar que recibiste el mensaje. ¿Te va bien que te llamemos esta semana? 🙏
seguimiento_negocio	utility	5 días sin respuesta	Buenos días, {{1}}. Le escribo de nuevo por la propuesta para {{2}}. Recuerde que el servicio es gratuito durante 2-4 semanas. ¿Le parece bien que me pase por su negocio?
citacion_entrevista	utility	Lead cualificado por orientador	¡{{1}}! Buenas noticias 🎉 Tu perfil encaja muy bien en el programa. Te citamos: 📅 {{2}} ⏰ {{3}} 📍 {{4}}. Dura 30 min. ¿Te viene bien?
propuesta_negocio	utility	Propuesta lista para enviar	{{1}}, aquí le envío la propuesta para {{2}}. Le he preparado ejemplos de lo que haríamos. ¿Cuándo le viene bien 15 min para ultimar detalles?

Reglas de Meta para templates
Máximo 1.024 caracteres en el body.
Las variables {{1}}, {{2}} deben tener ejemplos en la solicitud de aprobación.
No se permite lenguaje amenazante, urgencia falsa, o contenido engañoso.
Categoría 'utility' (transaccional) es más barata que 'marketing' (~0,03€ vs ~0,05€).
Los templates de utility se aprueban en 24h; los de marketing pueden tardar 48h.

 
7. Lógica de Escalación

7.1. Detección de escalación
El agente IA tiene instrucción de añadir [ESCALATE:motivo] al final de su respuesta cuando no puede resolver la consulta. El sistema parsea esta cadena y activa el flujo de escalación. El texto [ESCALATE:...] se elimina ANTES de enviar el mensaje al usuario (el usuario nunca lo ve).

7.2. Reglas automáticas (sin depender de la IA)
Además de la escalación por decisión del agente, el sistema aplica reglas automáticas:
Regla	Condición	Acción
Conversación larga	message_count > 8 sin conversión (sin clic a formulario)	Escalar + resumen automático
Inactividad prolongada	Usuario no responde en 48h tras 2 mensajes del agente	Cerrar conversación + marcar como 'frío'
Mensaje multimedia	Usuario envía imagen, audio, vídeo o documento	Escalar (el agente no procesa multimedia)
Palabra clave crítica	Mensaje contiene 'queja', 'denuncia', 'problema', 'enfadado'	Escalar inmediatamente
Fuera de horario	Mensaje recibido entre 22:00 y 07:00	Respuesta automática + procesar al día siguiente
Spam detectado	3+ mensajes en 10 segundos o contenido sospechoso	Silenciar + marcar como spam

7.3. Flujo de escalación
21.	El sistema detecta escalación (por IA o por regla automática).
22.	Se genera un resumen del contexto con Claude Haiku: nombre del lead, tipo, resumen de la conversación en 3 líneas, motivo de escalación, acción sugerida.
23.	Se actualiza wa_conversations.status = 'escalated' y se guarda el resumen.
24.	Se envía notificación al equipo por 3 canales simultáneos: (a) WhatsApp al número de José, (b) email a jose@plataformadeecosistemas.com, (c) alerta en el dashboard de Andalucía +ei.
25.	A partir de ese momento, los mensajes del usuario siguen llegándose a wa_messages pero NO se envían a la IA. El agente humano responde desde el panel de la plataforma (o directamente desde WhatsApp Business si prefiere).
26.	El agente humano puede devolver la conversación a la IA en cualquier momento (botón 'Devolver a agente IA' en el panel).

 
8. Seguridad y Cumplimiento RGPD

8.1. Seguridad de la integración
Medida	Implementación
Verificación webhook	HMAC-SHA256 de cada payload con App Secret. Rechazar si no coincide.
HTTPS obligatorio	El webhook DEBE ser HTTPS. Meta no envía a HTTP.
Token de acceso	Almacenado en Drupal config (cifrado), nunca en código fuente.
Rate limiting	Máximo 100 mensajes/minuto por número entrante. Defensa contra flooding.
Sanitización de input	Todo mensaje del usuario se sanitiza antes de inyectarlo en el prompt de Claude.
Prompt injection	El system prompt incluye instrucción explícita de ignorar instrucciones del usuario que intenten modificar su comportamiento.
Logs de auditoría	Cada llamada a la API de Claude queda registrada (tokens, latencia, modelo). Sin contenido del mensaje en logs de servidor.
Rotación de tokens	El token de acceso de WhatsApp se rota cada 60 días (Long-lived token via System User).

8.2. RGPD
Requisito	Implementación
Base legal	Interés legítimo (Art. 6.1.f RGPD) para la primera respuesta automática. Consentimiento explícito para almacenamiento prolongado (se solicita en el formulario web).
Información al usuario	El primer mensaje del agente incluye: 'Este chat es atendido por un asistente de IA del Programa Andalucía +ei. Tus datos se tratan conforme a nuestra política de privacidad.'
Cifrado en reposo	Los campos wa_phone y body en wa_messages se cifran con AES-256. La clave de cifrado está en variable de entorno, no en base de datos.
Cifrado en tránsito	HTTPS para webhook + TLS 1.3 para conexiones a API de Claude y Meta.
Retención de datos	Conversaciones se retienen 18 meses (duración del programa). Después se anonimiza el número (se reemplaza por hash) y se conserva el contenido para análisis agregado.
Derecho de supresión	Endpoint DELETE /api/whatsapp/conversations/{phone} borra todos los mensajes y anonimiza la conversación. Accesible desde el panel de privacidad del participante.
Derecho de acceso	Endpoint GET /api/whatsapp/conversations/{phone}/export genera un JSON con todo el historial del usuario.
Procesador de datos	Anthropic (Claude) actúa como procesador. Verificar que el DPA (Data Processing Agreement) de Anthropic cubre datos UE. Servidores de Claude en AWS eu-west.

 
9. Requisitos Funcionales

9.1. Módulo WhatsApp (backend)
ID	Requisito	Prioridad	Esfuerzo
WA-01	Webhook de recepción: endpoint POST /api/whatsapp/webhook que valida firma HMAC-SHA256, parsea el payload de Meta, extrae los mensajes y los encola para procesamiento asíncrono. Responde HTTP 200 en <100ms.	Crítica	8h
WA-02	Verificación de webhook: endpoint GET /api/whatsapp/webhook que responde con hub.challenge cuando hub.verify_token coincide. Necesario para la configuración inicial en Meta.	Crítica	1h
WA-03	Worker de procesamiento: consumer de la cola whatsapp_process que ejecuta el flujo completo (clasificación → agente → envío → persistencia). Configurable via cron (30s) o Drupal Queue Worker.	Crítica	12h
WA-04	Servicio de envío: WhatsAppSendService que encapsula las llamadas a la API de Meta (texto, template, interactivo). Manejo de errores, reintentos (3x con backoff exponencial), logging.	Crítica	8h
WA-05	Servicio de clasificación: WhatsAppClassifierService que envía el primer mensaje a Claude Haiku y parsea la respuesta JSON. Fallback a 'sin_clasificar' si la respuesta no es JSON válido.	Crítica	4h
WA-06	Servicio de agente: WhatsAppAgentService que construye el prompt (system + historial + contexto CRM), llama a Claude Sonnet, parsea la respuesta, detecta [ESCALATE], y devuelve el texto limpio.	Crítica	10h
WA-07	Gestión de conversaciones: crear, buscar por teléfono, actualizar estado, vincular con entidad CRM. Lógica de '1 conversación activa por número'.	Alta	6h
WA-08	Servicio de escalación: genera resumen con Haiku, actualiza estado, envía notificaciones por 3 canales (WA a José, email, dashboard).	Alta	6h
WA-09	Disparo automático post-formulario: event subscriber que envía template de bienvenida cuando se envía un formulario de participante o negocio.	Alta	4h
WA-10	Seguimiento automático: cron job que detecta conversaciones sin respuesta (24h participantes, 5d negocios) y envía template de seguimiento.	Media	4h
WA-11	Reglas de auto-escalación: implementar las 6 reglas de la §7.2 como middleware del worker.	Media	4h
WA-12	Detección de fuera de horario: si el mensaje llega entre 22:00-07:00, responder con mensaje estándar y encolar para procesamiento a las 07:00.	Baja	2h

9.2. Panel de gestión (frontend en Drupal)
ID	Requisito	Prioridad	Esfuerzo
WA-13	Dashboard de conversaciones: vista de lista con filtros por estado (activa, escalada, cerrada), tipo de lead, fecha. Indicador visual de conversaciones que requieren atención humana.	Alta	10h
WA-14	Vista de conversación individual: historial completo tipo chat (burbujas). Identificación visual de mensajes de usuario, agente IA y agente humano. Datos del lead en sidebar.	Alta	12h
WA-15	Respuesta manual desde panel: campo de texto para que el agente humano responda directamente desde el panel (envía vía API de WhatsApp). Botón 'Devolver a agente IA'.	Alta	6h
WA-16	KPIs en dashboard: mensajes hoy, conversaciones activas, tasa de escalación, tiempo medio de respuesta, leads creados, coste IA acumulado.	Media	6h
WA-17	Configuración de agente: panel para editar system prompts sin tocar código. Selector de modelo (Haiku/Sonnet), temperatura, max_tokens. Preview de respuesta antes de guardar.	Media	8h
WA-18	Gestión de templates: lista de templates sincronizados con Meta, estado de aprobación, botón de prueba.	Baja	4h

9.3. Integración con CRM existente
ID	Requisito	Prioridad	Esfuerzo
WA-19	Creación automática de lead: cuando la clasificación es 'participante', crear (o vincular si existe) entidad Participante en el CRM. Idem para 'negocio' con NegocioProspectado.	Alta	6h
WA-20	Historial de WhatsApp en ficha CRM: en la ficha del participante/negocio, pestaña 'WhatsApp' que muestra el historial de la conversación.	Alta	4h
WA-21	Sincronización bidireccional: si el orientador actualiza el estado del lead en el CRM (ej: 'citado para entrevista'), el agente IA lo sabe en la siguiente interacción (inyección de contexto CRM).	Media	6h
WA-22	UTM tracking: si la conversación se inició desde un formulario con UTMs, almacenar en la conversación y propagar al lead del CRM.	Media	2h

9.4. Estimación total de esfuerzo
Componente	Requisitos	Horas estimadas
Backend (webhook + servicios + workers)	WA-01 a WA-12	69h
Frontend (panel de gestión)	WA-13 a WA-18	46h
Integración CRM	WA-19 a WA-22	18h
Testing + QA + despliegue	Transversal	20h
Configuración Meta Business + templates	Transversal	8h
TOTAL	22 requisitos	161h (~4 semanas a jornada completa)

 
10. Estructura de Archivos del Módulo
El módulo se implementa como submódulo de ecosistema_jaraba_core siguiendo las convenciones de Drupal 11.

Estructura de directorios
modules/custom/ecosistema_jaraba_whatsapp/
├── ecosistema_jaraba_whatsapp.info.yml
├── ecosistema_jaraba_whatsapp.module
├── ecosistema_jaraba_whatsapp.install        # Schema: tablas wa_*
├── ecosistema_jaraba_whatsapp.routing.yml     # Rutas webhook + panel
├── ecosistema_jaraba_whatsapp.services.yml    # Servicios DI
├── ecosistema_jaraba_whatsapp.permissions.yml
├── ecosistema_jaraba_whatsapp.links.menu.yml  # Menú admin
├── ecosistema_jaraba_whatsapp.libraries.yml   # CSS/JS panel
├── config/
│   ├── install/
│   │   └── ecosistema_jaraba_whatsapp.settings.yml  # Config por defecto
│   └── schema/
│       └── ecosistema_jaraba_whatsapp.schema.yml
├── src/
│   ├── Controller/
│   │   ├── WhatsAppWebhookController.php    # Endpoint webhook
│   │   └── WhatsAppDashboardController.php  # Panel de gestión
│   ├── Service/
│   │   ├── WhatsAppApiService.php          # Llamadas a Meta API
│   │   ├── WhatsAppClassifierService.php   # Clasificación con Haiku
│   │   ├── WhatsAppAgentService.php        # Conversación con Sonnet
│   │   ├── WhatsAppConversationService.php # CRUD conversaciones
│   │   ├── WhatsAppEscalationService.php   # Lógica de escalación
│   │   ├── WhatsAppTemplateService.php     # Envío de templates
│   │   └── WhatsAppCrmBridgeService.php    # Puente con CRM
│   ├── Plugin/
│   │   └── QueueWorker/
│   │       └── WhatsAppProcessMessage.php  # Queue worker
│   ├── EventSubscriber/
│   │   └── FormSubmitSubscriber.php        # Trigger post-formulario
│   ├── Event/
│   │   └── WhatsAppSendTemplateEvent.php
│   └── Form/
│       ├── WhatsAppSettingsForm.php         # Config admin
│       └── WhatsAppAgentPromptForm.php      # Edición de prompts
├── templates/
│   ├── whatsapp-dashboard.html.twig
│   ├── whatsapp-conversation.html.twig
│   └── whatsapp-settings.html.twig
├── css/
│   └── whatsapp-panel.css
├── js/
│   └── whatsapp-panel.js                    # Polling/websocket chat
└── tests/
    ├── src/Unit/
    │   ├── WhatsAppClassifierServiceTest.php
    │   ├── WhatsAppAgentServiceTest.php
    │   └── WhatsAppWebhookControllerTest.php
    └── src/Functional/
        └── WhatsAppFlowTest.php

 
11. Configuración y Variables de Entorno

11.1. Variables de entorno (NO en código)
Variable	Descripción	Ejemplo
WHATSAPP_VERIFY_TOKEN	Token de verificación del webhook (elegido por ti)	andalucia_ei_wa_2026_xyz
WHATSAPP_APP_SECRET	App Secret de Meta Business para validar firma HMAC	abc123def456...
WHATSAPP_ACCESS_TOKEN	Token de acceso de WhatsApp Cloud API (long-lived)	EAAG...
WHATSAPP_PHONE_NUMBER_ID	ID del número de teléfono en Meta	123456789012345
WHATSAPP_BUSINESS_ACCOUNT_ID	ID de la cuenta de WhatsApp Business	987654321098765
ANTHROPIC_API_KEY	API Key de Claude (ya existente en la plataforma)	sk-ant-...
WA_ENCRYPTION_KEY	Clave AES-256 para cifrado de datos sensibles	base64:...
WA_JOSE_PHONE	Número de WhatsApp de José para escalaciones	+34623174304
WA_JOSE_EMAIL	Email de José para escalaciones	jose@plataformadeecosistemas.com

11.2. Configuración Drupal (editable desde panel admin)
Parámetro	Valor por defecto	Descripción
classifier_model	claude-haiku-4-5-20251001	Modelo para clasificación
agent_model	claude-sonnet-4-20250514	Modelo para conversación
agent_temperature	0.3	Creatividad del agente (0.0-1.0)
agent_max_tokens	500	Máximo de tokens por respuesta
max_history_tokens	4000	Máximo de tokens de historial inyectados
escalation_message_threshold	8	Mensajes sin conversión antes de escalar
followup_participante_hours	24	Horas antes de enviar seguimiento a participante
followup_negocio_days	5	Días antes de enviar seguimiento a negocio
quiet_hours_start	22:00	Inicio de horario silencioso
quiet_hours_end	07:00	Fin de horario silencioso
queue_process_interval	30	Segundos entre ejecuciones del cron de la cola
spam_threshold_messages	3	Mensajes en 10s para detectar spam

 
12. Testing y Plan de Despliegue

12.1. Estrategia de testing
Tipo	Qué se testea	Herramienta
Unit tests	Clasificación de mensajes, parsing de payload, detección de escalación, construcción de prompts, sanitización de input	PHPUnit (Drupal Test)
Integration tests	Flujo completo: webhook → clasificación → agente → envío. Con mocks de API de Meta y Claude.	PHPUnit + Guzzle MockHandler
Manual QA	10 conversaciones simuladas por tipo (participante, negocio, spam, escalación). Verificar tono, exactitud de datos, escalación correcta.	WhatsApp desde teléfono personal
Load testing	Simular 50 mensajes simultáneos. Verificar que el webhook responde 200 en <100ms y el worker procesa sin pérdida.	wrk / Apache Bench
Seguridad	Payload con firma inválida, prompt injection en mensaje de usuario, rate limiting	curl / scripts ad-hoc

12.2. Plan de despliegue
Fase	Duración	Actividades
Fase 0: Configuración Meta	Día 1-2	Crear App en Meta Business Manager. Verificar cuenta business. Configurar WhatsApp Cloud API. Obtener tokens. Enviar templates para aprobación.
Fase 1: Backend core	Día 3-12	Implementar WA-01 a WA-08 (webhook, servicios, worker, clasificación, agente, escalación). Unit tests.
Fase 2: Integración CRM	Día 13-16	Implementar WA-19 a WA-22 (creación de leads, historial en ficha, sincronización, UTMs).
Fase 3: Panel de gestión	Día 17-24	Implementar WA-13 a WA-18 (dashboard, vista chat, respuesta manual, KPIs, config prompts).
Fase 4: Automatizaciones	Día 25-28	Implementar WA-09 a WA-12 (trigger post-formulario, seguimiento automático, reglas, fuera de horario).
Fase 5: QA + Piloto	Día 29-32	Testing manual con 10 conversaciones simuladas. Ajuste de prompts. Despliegue en producción con 5 usuarios reales controlados.
Fase 6: Lanzamiento	Día 33	Activación completa. Monitorización intensiva las primeras 48h.

12.3. Costes operativos estimados
Concepto	Volumen estimado/mes	Coste unitario	Coste mensual
Conversaciones WA (utility)	200 conversaciones	0,03€	6€
Conversaciones WA (marketing)	50 conversaciones	0,05€	2,50€
Claude Haiku (clasificación)	250 llamadas	0,001€	0,25€
Claude Sonnet (conversación)	1.000 llamadas	0,005€	5€
Claude Haiku (escalación)	50 resúmenes	0,002€	0,10€
Hosting adicional	Mínimo	0€ (dentro del hosting actual)	0€
TOTAL			~14€/mes

Coste por lead adquirido
Con 250 conversaciones/mes y una tasa de conversión del 30%, el agente generaría ~75 leads cualificados/mes por ~14€. Eso es 0,19€/lead.
Comparado con el coste de responder manualmente (1 persona × 2h/día × 20 días × 15€/h = 600€/mes), el agente IA ahorra un 97,7% del coste de gestión de leads.
Además, la respuesta es instantánea (5 segundos vs 2 horas del protocolo manual), lo que multiplica la tasa de conversión.

 
13. Extensibilidad: Estrategia B2G
El módulo WhatsApp IA está diseñado como componente genérico de Jaraba Impact Platform, no como solución ad-hoc para Andalucía +ei. Esto significa que cuando se posicione la plataforma como solución para otras entidades colaboradoras del PIL, el agente WhatsApp IA es un diferencial competitivo inmediato.

13.1. Multi-tenancy
•	Cada programa (tenant) tiene sus propios system prompts, templates, número de WhatsApp y configuración.
•	La tabla wa_conversations incluye un campo program_id (no detallado arriba por simplificación, pero necesario para multi-tenancy).
•	Los prompts se almacenan en la configuración de Drupal por programa, editables desde el panel admin sin tocar código.

13.2. Propuesta de valor B2G
Cuando otras entidades colaboradoras pregunten qué ofrece Jaraba Impact Platform que otras plataformas no, la respuesta incluye: agente WhatsApp IA integrado que gestiona captación de participantes con respuesta instantánea, clasificación automática de leads, escalación inteligente a humanos, y CRM con historial completo. Coste: 14€/mes. Tiempo de respuesta: 5 segundos. Eso es imbatible.

 
14. Próximos Pasos
27.	Revisión de este documento por el equipo técnico. Validar estimaciones.
28.	Crear la App en Meta Business Manager y configurar WhatsApp Cloud API.
29.	Enviar los 6 templates para aprobación de Meta (24-48h).
30.	Implementar el backend core (Fase 1) con Claude Code.
31.	Testing manual con 10 conversaciones simuladas.
32.	Despliegue piloto con 5 usuarios reales controlados.
33.	Lanzamiento completo + monitorización 48h.

Fin de las Especificaciones Técnicas
Agente WhatsApp IA — Jaraba Impact Platform — Marzo 2026
