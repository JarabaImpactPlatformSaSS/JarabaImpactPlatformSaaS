



JARABA IMPACT PLATFORM
Especificación Técnica para Implementación

SISTEMA DE SOPORTE Y TICKETS
CON IA PROACTIVA PARA TENANTS

Gestión de Incidencias Multi-Tenant con Clasificación Inteligente,
Adjuntos Multimedia, Soporte Integrado y Resolución Autónoma


Versión:	1.0
Fecha:	Febrero 2026
Código:	178_Platform_Tenant_Support_Tickets_v1
Estado:	Especificación para EDI
Dependencias:	104_SaaS_Admin_Center, 113_Customer_Success, 114_Knowledge_Base, 129_AI_Skills, 130_Tenant_Knowledge
Prioridad:	CRÍTICA — Gap identificado en auditoría de mercado
Módulo Drupal:	jaraba_support
Integraciones:	Claude API, Qdrant, Sistema Notificaciones, ECA, Stripe
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Soporte y Tickets con IA Proactiva para la Jaraba Impact Platform. El sistema permite a los tenant admins y usuarios finales reportar incidencias, solicitar soporte técnico y funcional, y recibir asistencia automatizada impulsada por IA antes, durante y después de abrir un caso.
El análisis del mercado SaaS 2025-2026 revela que los sistemas de tickets con IA resuelven autónomamente entre el 60-80% de las incidencias, reducen tiempos de respuesta hasta un 45%, y son considerados baseline obligatorio por clientes B2B e institucionales. Nuestra plataforma actualmente carece de un sistema formal de gestión de incidencias tenant-facing, lo que constituye un gap crítico detectado en la Investigación de Mercado SaaS (Gaps #6 y #7).
1.1 Diagnóstico del Gap
Tras revisar exhaustivamente los 170+ documentos del ecosistema, se identifica lo siguiente:
•	Lo que tenemos: Knowledge Base con FAQ Bot y escalación básica (doc 114), Customer Success con health scores (doc 113), SaaS Admin Center con referencias a tickets (doc 104), AI Skills con protocolo de escalación (doc 129), paneles admin con rol Support Agent (docs 58, 78).
•	Lo que NO tenemos: Módulo formal jaraba_support con entidades de ticket, portal tenant para abrir/seguir casos, sistema de adjuntos multimedia, clasificación y enrutamiento inteligente por IA, SLA management por plan, integración bidireccional con el sistema de comunicaciones, ni analytics de soporte alimentando el health score.
1.2 Objetivos del Sistema
Objetivo	Métrica Target	Benchmark Mercado
Resolución autónoma por IA	50-65% tickets	Best: 60-80%
Tiempo primera respuesta	< 2 minutos (IA) / < 4h (humano)	Industry: < 1h
Tiempo resolución medio	< 8 horas	Best B2B: 4-12h
CSAT en soporte	> 4.2/5.0	Good: 4.0+
Ticket deflection (KB+Bot)	30-40% pre-ticket	Industry: 20-40%
SLA compliance	> 95%	Enterprise: > 99%
Reducción carga operativa	60-70% menos tickets manuales	AI-first: 50-70%
Escalación a humano	< 15% de tickets	Best: < 10%
1.3 Componentes del Sistema
•	Portal de Soporte Tenant: Interfaz para crear, seguir y gestionar tickets con adjuntos multimedia
•	Motor de Clasificación IA: Auto-triaje, categorización, priorización y enrutamiento inteligente
•	Agente IA Proactivo: Diagnóstico automático, sugerencias de solución, resolución autónoma
•	SLA Engine: Gestión de niveles de servicio por plan de suscripción
•	Sistema de Adjuntos: Upload de documentos, capturas, vídeos con análisis IA
•	Panel Agente/Admin: Backoffice para gestión de tickets con contexto completo
•	Integración Comunicaciones: Soporte in-app, email y chat integrado
•	Analytics de Soporte: Métricas, tendencias, alimentación al health score
 
2. Contexto de Mercado y Justificación
2.1 Estado del Mercado de Tickets IA (2025-2026)
El mercado global de software de helpdesk y ticketing alcanzó los 7.500 millones de USD en 2025, con crecimiento sostenido esperado en 2026 impulsado por la adopción masiva de IA agente. Las principales tendencias que validan esta especificación son:
•	Resolución autónoma: Los agentes IA modernos resuelven 60-80% de tickets sin intervención humana. Plataformas como Fini alcanzan 80% de tasa de resolución con 98% de precisión. Zendesk reporta que el 67% de consumidores prefieren interactuar con IA para asistencia inmediata.
•	IA agente (Agentic AI): 2025-2026 marca la transición de chatbots reactivos a agentes IA que ejecutan workflows completos: procesan reembolsos, modifican suscripciones, verifican KYC, y escalan con contexto completo.
•	Clasificación inteligente: La clasificación automática por IA reduce tiempos de respuesta hasta un 45% y mejora la precisión del enrutamiento. Análisis de sentimiento en tiempo real permite detectar urgencia y frustración.
•	Omnicanalidad unificada: Los sistemas líderes (Zendesk, Pylon, Freshdesk) unifican soporte desde email, chat, Slack, Teams, formularios y redes sociales en un único panel.
•	Pricing por resolución: El modelo de pricing se está moviendo de “por agente/mes” a “por resolución IA”, lo que valida que la IA resuelva autónomamente como baseline.
2.2 Benchmark Competitivo
Plataforma	Resolución IA	Canales	Precio Base	Enfoque
Zendesk AI	80%+	Email, Chat, Tel, Social	55€/agente + 50€ AI add-on	Enterprise B2C/B2B
Intercom Fin	70-80%	In-app, Email, Chat	29€/seat + 0.99€/resolución	Product-led SaaS
Freshdesk Freddy	60-70%	Email, Chat, Tel, Social	Gratis - 79€/agente	SMB/Mid-market
Ada	83%	Chat, Email, Voz	Desde 30K€/año	B2C alto volumen
HappyFox	N/D (macros)	Email, Chat, Tel, Social	Desde 29€/agente	Operaciones mid-market
Jaraba (PROPUESTO)	50-65%	In-app, Email, WhatsApp	Incluido en plan	Rural SaaS, B2B/B2G
2.3 Ventaja Competitiva Jaraba
Nuestro sistema no compite con Zendesk en volumen, sino que se diferencia por:
•	Contexto vertical profundo: La IA conoce el negocio del tenant (AgroConecta, Empleabilidad, etc.) y puede diagnosticar problemas específicos de cada vertical.
•	Grounding estricto: Respuestas basadas exclusivamente en la KB del tenant y la plataforma, eliminando alucinaciones. Ya tenemos esta infraestructura (Qdrant + Skills System).
•	Incluido en el plan: Sin coste adicional por IA, a diferencia de Zendesk (+50€/agente) o Intercom (+0.99€/resolución). Esto es un diferenciador clave para nuestro mercado rural.
•	Integración nativa: El ticket system alimenta y se alimenta del Customer Success, Knowledge Base, Tenant Knowledge Training y FOC existentes.
 
3. Modelo de Datos
3.1 Entidad: support_ticket
Entidad principal que representa un caso de soporte abierto por un tenant.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
ticket_number	VARCHAR(20)	Sí	Número legible: JRB-YYYYMM-NNNN
tenant_id	UUID FK	Sí	FK groups.id — Tenant que reporta
reporter_uid	INT FK	Sí	FK users.uid — Usuario que abre el ticket
assignee_uid	INT FK	No	FK users.uid — Agente asignado
vertical	ENUM	Sí	empleabilidad|emprendimiento|agro|comercio|servicios|platform|billing
category	VARCHAR(64)	Sí	Categoría principal (configurable por vertical)
subcategory	VARCHAR(64)	No	Subcategoría específica
subject	VARCHAR(255)	Sí	Asunto del ticket
description	TEXT	Sí	Descripción detallada del problema
status	ENUM	Sí	new|ai_handling|open|pending_customer|pending_internal|escalated|resolved|closed
priority	ENUM	Sí	critical|high|medium|low (asignada por IA + override manual)
severity	ENUM	No	blocker|degraded|minor|cosmetic
channel	ENUM	Sí	portal|email|chat|whatsapp|phone|api
ai_classification	JSON	No	Resultado clasificación IA: {category, confidence, sentiment, urgency}
ai_resolution_attempted	BOOLEAN	Sí	DEFAULT FALSE — Si la IA intentó resolver
ai_resolution_accepted	BOOLEAN	No	Si el usuario aceptó la solución IA
ai_suggested_solution	TEXT	No	Solución propuesta por la IA
sla_policy_id	UUID FK	Sí	Política SLA aplicable
sla_first_response_due	TIMESTAMP	No	Deadline primera respuesta
sla_resolution_due	TIMESTAMP	No	Deadline resolución
sla_breached	BOOLEAN	Sí	DEFAULT FALSE
satisfaction_rating	INT(1-5)	No	Rating CSAT post-resolución
satisfaction_comment	TEXT	No	Comentario CSAT
resolution_notes	TEXT	No	Notas de resolución del agente
tags	JSON	No	Array de tags para organización
related_entity_type	VARCHAR(32)	No	order|product|user|booking|course...
related_entity_id	UUID	No	ID de la entidad relacionada
created_at	TIMESTAMP	Sí	Fecha de creación
updated_at	TIMESTAMP	Sí	Auto-actualizado
resolved_at	TIMESTAMP	No	Momento de resolución
closed_at	TIMESTAMP	No	Momento de cierre
3.2 Entidad: ticket_message
Mensajes del hilo de conversación de un ticket (incluye mensajes humanos e IA).
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
ticket_id	UUID FK	Sí	FK support_ticket.id
author_uid	INT FK	No	FK users.uid (NULL = sistema/IA)
author_type	ENUM	Sí	customer|agent|ai|system
body	TEXT	Sí	Contenido del mensaje (Markdown)
body_html	TEXT	No	Contenido renderizado HTML
is_internal_note	BOOLEAN	Sí	DEFAULT FALSE — Nota interna no visible al tenant
is_ai_generated	BOOLEAN	Sí	DEFAULT FALSE
ai_confidence	DECIMAL(3,2)	No	Confianza de la respuesta IA (0.00-1.00)
ai_sources	JSON	No	Array de fuentes KB usadas para la respuesta
attachments	JSON	No	Array de file references
created_at	TIMESTAMP	Sí	Fecha del mensaje
3.3 Entidad: ticket_attachment
Archivos adjuntos a tickets: documentos, capturas de pantalla, vídeos.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
ticket_id	UUID FK	Sí	FK support_ticket.id
message_id	UUID FK	No	FK ticket_message.id (si va ligado a un mensaje)
file_id	INT FK	Sí	FK file_managed.fid de Drupal
filename	VARCHAR(255)	Sí	Nombre original del archivo
mime_type	VARCHAR(128)	Sí	Tipo MIME
file_size	BIGINT	Sí	Tamaño en bytes
ai_analysis	JSON	No	Resultado de análisis IA del adjunto (OCR, clasificación, extracción)
thumbnail_url	VARCHAR(500)	No	Miniatura para previsualización
is_screenshot	BOOLEAN	Sí	DEFAULT FALSE — Detectado como captura de pantalla
uploaded_by	INT FK	Sí	FK users.uid
created_at	TIMESTAMP	Sí	Fecha de subida
3.4 Entidad: sla_policy
Políticas de SLA configurables por plan de suscripción y prioridad.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
name	VARCHAR(128)	Sí	Nombre: 'Starter SLA', 'Professional SLA', 'Enterprise SLA'
plan_tier	ENUM	Sí	starter|professional|enterprise|institutional
priority	ENUM	Sí	critical|high|medium|low
first_response_hours	INT	Sí	Horas para primera respuesta
resolution_hours	INT	Sí	Horas para resolución
business_hours_only	BOOLEAN	Sí	DEFAULT TRUE — Solo cuenta horas laborables
escalation_after_hours	INT	No	Horas sin actividad para auto-escalación
includes_phone	BOOLEAN	Sí	DEFAULT FALSE — Soporte telefónico incluido
includes_priority_queue	BOOLEAN	Sí	DEFAULT FALSE — Cola prioritaria
active	BOOLEAN	Sí	DEFAULT TRUE
3.5 Entidad: ticket_event_log
Log completo de eventos del ciclo de vida del ticket para auditoría.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
ticket_id	UUID FK	Sí	FK support_ticket.id
event_type	ENUM	Sí	created|assigned|status_changed|priority_changed|escalated|sla_warning|sla_breached|ai_classified|ai_responded|resolved|closed|reopened|merged|tagged
actor_uid	INT FK	No	Usuario que causó el evento (NULL = sistema)
actor_type	ENUM	Sí	customer|agent|ai|system|eca
old_value	VARCHAR(255)	No	Valor anterior
new_value	VARCHAR(255)	No	Valor nuevo
metadata	JSON	No	Datos adicionales del evento
created_at	TIMESTAMP	Sí	Momento del evento
 
4. Motor de IA Proactiva
4.1 Filosofía: IA Proactiva, No Solo Reactiva
El diferenciador clave de nuestro sistema frente a helpdesks tradicionales es que la IA actúa en tres momentos, no solo cuando se crea un ticket:
4.1.1 Pre-Ticket: Prevención e Interceptación
•	Detección de patrones: La IA monitoriza el comportamiento del tenant (errores recurrentes, páginas con alta tasa de abandono, features no usados) y sugiere soluciones antes de que el usuario abra un ticket.
•	Alertas proactivas: Si la IA detecta un patrón que históricamente genera tickets (ej: fallo de sincronización Stripe, límite de almacenamiento cercano), envía notificación al tenant admin con la solución.
•	KB contextual: Antes de mostrar el formulario de ticket, el sistema muestra artículos relevantes de la Knowledge Base basándose en la página donde está el usuario y las palabras clave que empieza a escribir (deflection).
4.1.2 Durante el Ticket: Diagnóstico y Resolución Automática
•	Auto-clasificación: Al crear el ticket, la IA analiza asunto + descripción + adjuntos para asignar categoría, prioridad, sentimiento, urgencia y vertical automáticamente.
•	Análisis de adjuntos: Los screenshots se procesan con OCR + visión para detectar errores visibles, códigos de error, y elementos de UI problemáticos. Los documentos se indexan para contexto.
•	Respuesta IA inmediata: En < 2 minutos, la IA genera una primera respuesta con solución propuesta basada en KB del tenant + KB de plataforma + historial de tickets similares. Si la confianza es > 0.85, se ofrece como resolución automática.
•	Enrutamiento inteligente: Si la IA no puede resolver, enruta al agente humano más apropiado según vertical, especialidad, carga actual y disponibilidad.
4.1.3 Post-Ticket: Aprendizaje y Mejora Continua
•	Feedback loop: Cada resolución (IA o humana) alimenta el modelo de clasificación. Las correcciones de agentes a respuestas IA se integran en el training del tenant (doc 130).
•	FAQ auto-generation: Tickets resueltos recurrentes se proponen como nuevas FAQs para la Knowledge Base, cerrando el círculo KB → Bot → Ticket → KB.
•	Health Score feed: La frecuencia, tipo y resolución de tickets alimenta directamente el Health Score del Customer Success (doc 113), permitiendo detección temprana de churn.
4.2 Pipeline de Procesamiento IA
Flujo técnico cuando un tenant abre un ticket:
Paso	Acción	Tecnología	Tiempo
1	Recepción del ticket (texto + adjuntos)	Drupal Form API / REST API	Instantáneo
2	Embedding del texto del ticket	Claude API → Qdrant	< 1s
3	Búsqueda semántica en KB plataforma + tenant	Qdrant vector search (top_k=10)	< 500ms
4	Búsqueda de tickets similares previos	Qdrant + historial tenant	< 500ms
5	Análisis de adjuntos (si hay screenshots)	Claude Vision API	2-5s
6	Clasificación: categoría + prioridad + sentimiento	Claude API con prompt específico	< 2s
7	Generación de respuesta con strict grounding	Claude API + RAG context	3-5s
8	Validación de confianza (umbral 0.85 para auto-resolución)	Lógica de negocio	< 100ms
9	Presentación al usuario o enrutamiento a agente	Notificación multicanal	< 1s
TOTAL	Primera respuesta IA disponible	—	< 15s
4.3 Análisis Inteligente de Adjuntos
Cuando un tenant sube un archivo al ticket, la IA ejecuta análisis específico según el tipo:
Tipo de Archivo	Análisis IA	Output
Screenshot / Imagen	Visión: detectar errores visibles, códigos de error, estado de la UI, elementos rotos	JSON con errores detectados, sugerencia de categorización, posible solución
PDF / Documento	Extracción de texto + indexación semántica + detección de tipo (factura, contrato, informe)	Resumen del documento, datos relevantes extraídos, contexto para el agente
CSV / Excel	Análisis de estructura, detección de errores de formato, validación de datos	Informe de problemas detectados, sugerencias de corrección
Vídeo (< 30s)	Extracción de frames clave + análisis visual	Captura de momentos problemáticos, resumen del flujo mostrado
Log / Texto plano	Parsing de errores, stack traces, timestamps de eventos	Errores identificados, timeline del problema, sugerencia de causa raíz
 
5. Motor de SLAs por Plan
5.1 Matriz de SLA
Los SLAs se configuran dinámicamente desde el SaaS Admin Center (doc 104), sin hardcoding. Esta tabla muestra los valores por defecto propuestos:
Plan / Prioridad	Critical	High	Medium	Low
Starter — 1ª Respuesta	4h	8h	24h	48h
Starter — Resolución	24h	48h	5 días	10 días
Professional — 1ª Respuesta	2h	4h	8h	24h
Professional — Resolución	8h	24h	3 días	5 días
Enterprise — 1ª Respuesta	30min	1h	4h	8h
Enterprise — Resolución	4h	8h	24h	3 días
Institutional (B2G) — 1ª Resp.	15min	30min	2h	4h
Institutional (B2G) — Resolución	2h	4h	8h	24h
5.2 Canales de Soporte por Plan
Canal	Starter	Professional	Enterprise	Institutional
IA Proactiva (Chat Bot)	✓	✓	✓	✓
Portal de Tickets	✓	✓	✓	✓
Email de Soporte	✓	✓	✓	✓
Chat en Vivo (In-App)	✗	✓	✓	✓
WhatsApp Business	✗	✗	✓	✓
Soporte Telefónico	✗	✗	✓	✓
CSM Dedicado	✗	✗	✗	✓
Cola Prioritaria	✗	✗	✓	✓
Horario	L-V 9-18	L-V 9-18	L-V 8-20 + Sábados	24/7
Tickets Simultáneos	3	10	Ilimitados	Ilimitados
 
6. Portal de Soporte Tenant
6.1 Flujo de Creación de Ticket
El flujo está diseñado para maximizar la deflection y resolución automática antes de crear un ticket formal:
Paso 1: Interceptación Pre-Ticket
El usuario accede al portal de soporte. Antes de ver el formulario, el sistema muestra:
•	Artículos de KB relevantes basados en su actividad reciente y página de origen
•	Estado de la plataforma (si hay incidentes conocidos)
•	Tickets abiertos existentes (para evitar duplicados)
•	Opción de chat rápido con el FAQ Bot antes de abrir ticket formal
Paso 2: Formulario Inteligente
Si el usuario decide abrir un ticket:
•	Campo de asunto con autocompletado que busca en KB en tiempo real (cada keystroke)
•	Descripción rich-text con soporte Markdown
•	Selector de vertical (auto-detectado según el contexto de navegación)
•	Zona de drag-and-drop para adjuntos (máx 10 archivos, 25MB total por plan, 100MB enterprise)
•	Captura de pantalla integrada: botón que captura la pantalla actual del usuario automáticamente
•	Vinculación a entidad: selector para vincular el ticket a un pedido, producto, reserva, curso, etc.
Paso 3: Diagnóstico IA Instantáneo
Al enviar el formulario, el sistema muestra una pantalla de 'diagnóstico en progreso' (< 15s):
•	Animación de análisis mientras la IA procesa
•	Si la IA encuentra una solución con confianza > 0.85: se muestra como 'Solución sugerida' con botón 'Esto resolvió mi problema' + 'Necesito más ayuda'
•	Si el usuario acepta: ticket se marca como ai_resolved, CSAT survey inmediato
•	Si el usuario rechaza: ticket se crea formalmente y se enruta a agente humano con todo el contexto IA
6.2 Vista de Mis Tickets
Dashboard del tenant para gestionar sus casos activos:
Columna	Descripción
#Ticket	Número JRB-YYYYMM-NNNN con enlace al detalle
Asunto	Título del ticket con badge de prioridad
Estado	Badge de color: Nuevo (azul), IA Atendiendo (turquesa), Abierto (verde), Pendiente (amarillo), Resuelto (gris)
Prioridad	Badge: Critical (rojo), High (naranja), Medium (azul), Low (gris)
Última actualización	Timestamp con indicador de 'nueva respuesta'
SLA	Indicador visual: dentro de SLA (verde), warning (amarillo), breach (rojo)
Asignado a	Avatar + nombre del agente (o 'IA' si en auto-resolución)
Acciones	Ver detalle, Añadir mensaje, Cerrar, Reabrir
6.3 Vista de Detalle de Ticket
Interfaz conversacional tipo chat para el hilo del ticket:
•	Timeline del ticket con todos los mensajes, notas internas (solo para agentes) y eventos del sistema
•	Panel lateral con: info del ticket, SLA countdown, entidad vinculada, adjuntos, historial de cambios
•	Editor de respuesta con rich-text, adjuntos, y opción de captura de pantalla
•	Botón 'Marcar como resuelto' accesible tanto para el tenant como para el agente
•	Encuesta CSAT automática al resolver o cerrar
 
7. Integración con Sistema de Comunicaciones
7.1 Arquitectura de Soporte Multicanal
La respuesta a tu tercera pregunta es sí: el sistema de soporte debe estar profundamente integrado con la infraestructura de comunicaciones existente (docs 59, 76, 98). La clave es que el ticket sea el 'single source of truth' y los canales sean puntos de acceso:
Canal	Flujo Entrada	Flujo Salida	Integración Técnica
In-App Chat	Widget flotante en toda la plataforma. Inicia conversación con FAQ Bot. Si escala, crea ticket automáticamente.	Respuestas IA y de agentes se muestran en el chat. Notificación push cuando hay respuesta.	WebSocket nativo + jaraba_support API
Email	Emails a soporte@[tenant].jarabaimpact.com se parsean y crean/actualizan tickets. Reply-to threading.	Cada respuesta al ticket se envía por email. Incluye historial reciente y link al portal.	SendGrid Inbound Parse + ECA
WhatsApp (Pro+)	Mensajes al número de soporte WhatsApp. Bot IA atiende. Si necesita ticket, lo crea con contexto completo.	Notificaciones de actualización de ticket. CSAT via WhatsApp buttons.	WhatsApp Business API via Twilio
Portal Web	Formulario inteligente con deflection y diagnóstico IA previo. Es el canal principal y más rico.	Dashboard de tickets, timeline conversacional, adjuntos, SLA visible.	React component + REST API
API (dev)	POST /api/v1/support/tickets con payload JSON. Para integraciones programáticas.	Webhooks a URLs configuradas para actualizaciones de estado.	REST API + Webhooks
7.2 Widget de Soporte In-App
Componente React embebido en todas las páginas de la plataforma que ofrece soporte contextual:
•	Botón flotante en esquina inferior derecha (personalizable por tenant vía theming)
•	Al abrirse: muestra ayuda contextual de la página actual (Contextual Help, doc 114)
•	Pestañas: Chat con IA | Mis Tickets | Buscar en KB
•	El chat con IA usa el FAQ Bot con strict grounding (doc 114) + protocolo de escalación (doc 129 Anexo A)
•	Transición fluida: si el bot no resuelve, ofrece crear ticket pre-rellenado con el contexto de la conversación
•	Badge de notificación cuando hay respuestas pendientes en tickets abiertos
7.3 Integración con Customer Success (doc 113)
La métrica de soporte es componente directo del Health Score:
Señal	Impacto en Health Score	Peso
0 tickets en 30 días	+5 puntos (saludable)	15% del support_score
1-3 tickets resueltos rápido	Neutral (0)	—
4+ tickets en 30 días	-10 puntos (posible fricción)	15%
Ticket critical sin resolver > 24h	-15 puntos (riesgo)	20%
SLA breach	-20 puntos (crítico)	25%
CSAT < 3.0 en últimos 3 tickets	-10 puntos	15%
Ticket de billing/cancelación	Trigger churn alert inmediato	Trigger directo
 
8. Flujos de Automatización (ECA)
Código	Evento	Condición	Acciones
SUP-001	ticket.created	Siempre	Ejecutar pipeline IA (clasificación + búsqueda + respuesta). Calcular SLA deadlines. Notificar al tenant por email + push. Log event.
SUP-002	ticket.ai_resolved	ai_confidence > 0.85 AND user_accepted	Marcar status=resolved. Solicitar CSAT (24h). Proponer FAQ si patrón recurrente (3+ similares). Actualizar métricas IA.
SUP-003	ticket.ai_rejected	User rechaza solución IA	Status=open. Enrutar a agente humano. Incluir contexto IA + respuesta rechazada. Notificar agente.
SUP-004	ticket.assigned	Agente asignado	Notificar agente (email + in-app). Mostrar contexto: historial tenant, health score, tickets previos, solución IA rechazada.
SUP-005	sla.warning	Tiempo restante < 25% del SLA	Alertar agente asignado. Alertar team lead si priority=critical. Incrementar prioridad visual en dashboard.
SUP-006	sla.breached	SLA deadline superado	Escalar a team lead. Alertar admin de plataforma. Marcar sla_breached=true. Reducir health score. Log de incumplimiento.
SUP-007	ticket.idle_48h	Sin actividad 48h AND status=pending_customer	Enviar recordatorio al tenant. Si no responde en 72h más, auto-cerrar con opción de reabrir.
SUP-008	ticket.resolved	Resolución marcada	Enviar CSAT survey (24h). Programar auto-cierre (7 días). Alimentar KB si aplica. Actualizar health score.
SUP-009	ticket.pattern_detected	3+ tickets similares del mismo tenant en 30 días	Crear alerta proactiva para CS. Sugerir sesión de training personalizado. Proponer artículo KB específico.
SUP-010	ticket.billing_category	Categoría = billing OR cancelación	Priorizar automáticamente. Trigger churn alert en Customer Success. Asignar a equipo especializado.
SUP-011	attachment.uploaded	Tipo = imagen/screenshot	Ejecutar análisis de visión IA. Extraer info relevante. Añadir resultado como nota interna del ticket.
SUP-012	ticket.satisfaction_low	CSAT ≤ 2	Notificar CS manager. Crear follow-up task. Registrar para análisis de causa raíz.
 
9. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/support/tickets	Crear nuevo ticket (con adjuntos multipart)	Tenant User
GET	/api/v1/support/tickets	Listar tickets (filtros: status, priority, date, assignee)	Tenant User / Agent
GET	/api/v1/support/tickets/{id}	Detalle de ticket con mensajes y adjuntos	Tenant User / Agent
PATCH	/api/v1/support/tickets/{id}	Actualizar ticket (status, priority, assignee, tags)	Agent / Admin
POST	/api/v1/support/tickets/{id}/messages	Añadir mensaje/respuesta al ticket	Tenant User / Agent
POST	/api/v1/support/tickets/{id}/attachments	Subir adjunto (multipart, max 25/100MB)	Tenant User / Agent
GET	/api/v1/support/tickets/{id}/attachments	Listar adjuntos del ticket	Tenant User / Agent
POST	/api/v1/support/tickets/{id}/resolve	Marcar como resuelto	Tenant User / Agent
POST	/api/v1/support/tickets/{id}/reopen	Reabrir ticket cerrado	Tenant User
POST	/api/v1/support/tickets/{id}/escalate	Escalar a nivel superior	Agent
POST	/api/v1/support/tickets/{id}/satisfaction	Enviar rating CSAT	Tenant User
POST	/api/v1/support/tickets/{id}/merge	Fusionar con otro ticket	Agent / Admin
GET	/api/v1/support/stats	Estadísticas de soporte (por tenant, vertical, periodo)	Agent / Admin
GET	/api/v1/support/sla-policies	Listar políticas SLA activas	Admin
POST	/api/v1/support/ai/classify	Clasificar texto con IA (standalone)	System
POST	/api/v1/support/ai/suggest	Obtener sugerencia de solución IA	System
GET	/api/v1/support/search	Búsqueda semántica en tickets históricos	Agent / Admin
POST	/api/v1/support/inbound/email	Webhook para emails entrantes (SendGrid Parse)	System
 
10. Panel de Agente de Soporte
10.1 Dashboard del Agente
Vista principal optimizada para eficiencia máxima del agente:
•	Cola de tickets: filtrable por vertical, prioridad, SLA status, asignación. Ordenación inteligente por urgencia real (combinación de prioridad + tiempo restante SLA).
•	Vista rápida: hover sobre ticket muestra preview del problema + solución IA sugerida (si la hubo) + health score del tenant.
•	Asignación inteligente: botón 'Tomar siguiente' que asigna automáticamente el ticket más urgente compatible con las skills del agente.
•	Métricas personales: tickets resueltos hoy, CSAT medio, SLA compliance, tiempo medio de resolución.
10.2 Vista de Ticket del Agente
Paneles de información contextual al gestionar un ticket:
•	Panel izquierdo: Timeline conversacional con mensajes del tenant, respuestas IA (resaltadas), notas internas del equipo.
•	Panel derecho superior: Ficha del tenant (plan, vertical, health score, MRR, tickets históricos, CSM asignado).
•	Panel derecho inferior: Sugerencias IA en tiempo real — artículos KB relevantes, tickets similares resueltos, respuesta sugerida editable.
•	Acción rápida: botón 'Usar respuesta IA' que precarga la sugerencia en el editor de respuesta para que el agente la refine.
•	Macros de respuesta: templates configurables por vertical y categoría con variables dinámicas ({tenant_name}, {ticket_number}, etc.).
 
11. Analytics de Soporte
11.1 KPIs del Dashboard
KPI	Fórmula / Cálculo	Target	Período
Volumen de tickets	Total tickets creados	Trending down	Semanal
Tasa resolución IA	ai_resolved / total_tickets * 100	> 50%	Semanal
MTTR (Mean Time To Resolution)	AVG(resolved_at - created_at)	< 8 horas	Semanal
First Response Time	AVG(first_message_agent - created_at)	< SLA	Diario
SLA Compliance	tickets_within_sla / total * 100	> 95%	Semanal
CSAT Score	AVG(satisfaction_rating)	> 4.2/5	Mensual
Ticket Deflection Rate	kb_views_pre_ticket / (kb_views + tickets) * 100	> 30%	Mensual
Reopened Rate	reopened_tickets / resolved_tickets * 100	< 5%	Mensual
Tickets por 100 tenants	total_tickets / active_tenants * 100	< 15	Mensual
Top categorías	Ranking por volumen con trend	Identificar patrones	Semanal
11.2 Reportes Especializados
•	Informe de tendencias: Evolución semanal/mensual de volumen, MTTR, CSAT, resolución IA. Identificación de spikes y correlación con cambios de plataforma.
•	Informe por vertical: Comparativa de métricas entre Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta. Detectar verticales con mayor fricción.
•	Informe de IA: Accuracy de clasificación, tasa de aceptación de soluciones IA, temas donde la IA falla más, sugerencias de mejora de KB.
•	Informe de SLA: Compliance por plan, agente, vertical. Desglose de breaches con root cause analysis.
•	Informe de voz del cliente: Análisis de sentimiento agregado, temas recurrentes, correlación CSAT con tipos de incidencia.
 
12. Permisos RBAC
Permiso	Tenant User	Tenant Admin	Support Agent	Support Lead	Platform Admin
Crear ticket	✓	✓	✓	✓	✓
Ver sus tickets	✓	✓	✓	✓	✓
Ver tickets del tenant	✗	✓	✓ (asignados)	✓	✓
Ver todos los tickets	✗	✗	✗	✓	✓
Responder a tickets	✓ (propios)	✓ (tenant)	✓	✓	✓
Añadir notas internas	✗	✗	✓	✓	✓
Cambiar prioridad	✗	✗	✓	✓	✓
Cambiar asignación	✗	✗	✗	✓	✓
Escalar ticket	✗	✗	✓	✓	✓
Fusionar tickets	✗	✗	✗	✓	✓
Cerrar ticket	✓ (propios)	✓ (tenant)	✓	✓	✓
Configurar SLAs	✗	✗	✗	✗	✓
Ver analytics soporte	✗	✗	✓ (propias)	✓	✓
Exportar datos	✗	✗	✗	✓	✓
Gestionar macros	✗	✗	✓	✓	✓
 
13. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades BD (support_ticket, ticket_message, ticket_attachment, sla_policy, ticket_event_log). Migraciones. RBAC básico.
Sprint 2	Semana 3-4	Portal tenant: formulario de ticket, lista de tickets, vista de detalle. Upload de adjuntos con validación.
Sprint 3	Semana 5-6	Motor IA: pipeline de clasificación + búsqueda semántica + generación de respuesta. Integración Claude API + Qdrant.
Sprint 4	Semana 7-8	SLA Engine: cálculo de deadlines, warnings, breach detection. Flujos ECA de notificación y escalación.
Sprint 5	Semana 9-10	Panel de agente: dashboard, cola inteligente, vista de ticket con contexto IA, macros. Notas internas.
Sprint 6	Semana 11-12	Widget in-app de soporte. Integración email inbound. Análisis IA de screenshots. CSAT surveys.
Sprint 7	Semana 13-14	Analytics dashboard. Integración con Customer Success health score. Informes automatizados.
Sprint 8	Semana 15-16	WhatsApp Business (Enterprise+). Chat en vivo. Optimización. Testing integral. Go-live.
13.1 Estimación de Esfuerzo
Componente	Horas Estimadas	Prioridad
Modelo de datos + APIs REST	60-80h	CRÍTICA
Portal tenant (formulario + lista + detalle)	80-100h	CRÍTICA
Motor IA (clasificación + respuesta + grounding)	80-120h	CRÍTICA
SLA Engine + ECA automations	40-60h	ALTA
Panel de agente	60-80h	ALTA
Widget in-app + integración comunicaciones	40-60h	ALTA
Análisis IA de adjuntos (visión + OCR)	30-40h	MEDIA
Email inbound parsing	20-30h	MEDIA
WhatsApp Business integration	30-40h	MEDIA
Analytics dashboard + reporting	40-50h	ALTA
Integración Customer Success + Health Score	20-30h	ALTA
Testing + QA + seguridad	40-60h	CRÍTICA
TOTAL	520-750h	—
 
14. Dependencias con Documentos Existentes
Documento	Código	Tipo de Dependencia
SaaS Admin Center Premium	104	Bidireccional: Admin Center gestiona SLAs y ve métricas de soporte. Tickets visibles en detalle de tenant.
Customer Success Proactivo	113	Tickets alimentan health score. Churn alerts se conectan con tickets de billing.
Knowledge Base & Self-Service	114	FAQ Bot escala a ticket. KB deflecta tickets. Tickets resueltos generan nuevas FAQs.
AI Skills System	129	Protocolo de escalación (Anexo A) define comportamiento del agente IA en tickets.
Tenant Knowledge Training	130	Correcciones de respuestas IA en tickets alimentan el conocimiento del tenant.
Notificaciones Multicanal	59/76/98	Sistema de tickets usa la infraestructura de notificaciones existente.
Paneles Admin (AgroConecta, ComercioConecta)	58/78	Rol 'Support Agent' ya definido. Tickets referenciados en perfiles de cliente.
Core Permisos RBAC	04	Nuevos permisos jaraba_support se integran en el sistema RBAC existente.
Core APIs Contratos	03	APIs REST del módulo siguen los contratos y estándares definidos.
Core Flujos ECA	06	Los 12 flujos ECA de soporte se registran en el sistema ECA existente.
Stripe Billing Integration	134	Tickets de billing se vinculan a suscripciones Stripe. Estado de pago visible en ticket.
Investigación de Mercado SaaS	Inv.	Gaps #6 y #7 identificados como origen de esta especificación.

--- Fin del Documento ---
