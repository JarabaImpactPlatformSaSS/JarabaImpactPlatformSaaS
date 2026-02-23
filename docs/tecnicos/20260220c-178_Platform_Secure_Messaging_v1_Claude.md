
SISTEMA DE MENSAJERÍA SEGURA
Comunicación Bidireccional Cifrada en Tiempo Real
WebSocket + AES-256-GCM + Redis Pub/Sub + Audit Trail Inmutable
Plataforma Cross-Vertical — JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Febrero 2026
Estado:	Especificación Técnica para Implementación
Código:	178_Platform_Secure_Messaging_v1
Horas Estimadas:	140–180 horas
Dependencias:	82_Services_Core, 88_Buzon_Confianza, 90_Portal_Cliente, 93_Copilot_Servicios, 94_Dashboard_Profesional, 98_Notificaciones_Multicanal, 34_Collaboration_Groups, 06_Core_Flujos_ECA, 04_Core_Permisos_RBAC
Integraciones:	WebSocket (Ratchet/Swoole), Redis 7 Pub/Sub, SendGrid, Twilio, Firebase FCM, Qdrant, Web Crypto API
Prioridad:	ALTA — Comunicación bidireccional es core del servicio profesional
Compliance:	RGPD, LOPD-GDD, eIDAS, secreto profesional (LOPJ art. 542), Ley 34/2002 LSSI-CE
Alcance:	Cross-vertical: ServiciosConecta/JarabaLex (primario), Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta
 
1. Resumen Ejecutivo
El Sistema de Mensajería Segura (jaraba_messaging) es un módulo de comunicación bidireccional cifrada nativo de la Jaraba Impact Platform que permite conversaciones en tiempo real entre profesionales y clientes, entre mentores y emprendedores, entre orientadores y candidatos, y entre productores y compradores. A diferencia de las notificaciones unidireccionales (doc 98), este sistema habilita diálogos completos dentro de la plataforma, eliminando la necesidad de recurrir a canales externos no trazables como WhatsApp personal o email directo.
El módulo reutiliza la infraestructura criptográfica del Buzón de Confianza (doc 88) para cifrado en reposo, la capa WebSocket del Dashboard Profesional (doc 94) para transporte en tiempo real, el sistema Redis pub/sub de los Grupos de Colaboración (doc 34) para distribución de mensajes, y el Sistema de Notificaciones Multicanal (doc 98) para alertas offline. Es un ejemplo puro de la filosofía ‘Sin Humo’: componentes probados ensamblados en una nueva capacidad de alto valor sin reinventar nada.
1.1 El Problema: Fragmentación de la Comunicación Profesional
Los profesionales liberales —abogados, asesores fiscales, arquitectos, consultores— gestionan comunicaciones sensibles con sus clientes a través de canales fragmentados, inseguros y sin trazabilidad. El Portal Cliente Documental (doc 90) resuelve el intercambio de documentos pero es asíncrono. Las notificaciones (doc 98) son unidireccionales. Falta la pieza de conversación bidireccional en tiempo real que complete el ciclo de comunicación profesional.
Canal Actual	Problemas Específicos	Consecuencias Legales/Operativas
Email personal	Sin cifrado E2E, hilos perdidos entre spam, adjuntos dispersos, sin confirmación de lectura fiable	Documentos sensibles expuestos (vulneración RGPD art. 32), imposibilidad de probar comunicación fehaciente
WhatsApp personal	Datos en dispositivo personal, sin audit log profesional, historial perdido al cambiar móvil, mezcla vida personal/profesional	Vulnerabilidad RGPD (datos en servidores Meta fuera de UE), sin valor probatorio en procedimientos judiciales
Teléfono	Sin registro escrito, requiere sincronía temporal, sin adjuntos, interpretaciones divergentes	"Yo no dije eso" — sin evidencia documental, malentendidos que derivan en reclamaciones
Formularios web	Unidireccional, sin conversación fluida, respuesta diferida, experiencia impersonal	Fricción alta que reduce conversión, abandono del proceso, percepción de servicio impersonal
Presencial	Requiere desplazamiento, sin copia digital, limitado a horario de oficina	Ineficiente para zonas rurales (target Jaraba), sin trazabilidad, coste de tiempo desproporcionado

1.2 La Solución: Mensajería Segura Nativa
Conversaciones contextuales: Cada conversación puede vincularse a un expediente (client_case del doc 90), una reserva (booking del doc 85), un grupo de colaboración (doc 34) o un pedido (order de docs 49/67). El contexto enriquece automáticamente la conversación con datos relevantes.
Cifrado en reposo AES-256-GCM con clave por tenant: Los mensajes se cifran en base de datos utilizando la misma jerarquía de claves del Buzón de Confianza (doc 88). La clave maestra del tenant se deriva vía Argon2id. El servidor puede descifrar para funciones de IA, búsqueda y compliance, pero los datos están protegidos contra acceso no autorizado al almacenamiento físico.
Transporte en tiempo real (<100ms): WebSocket nativo con Redis pub/sub para entrega instantánea. Indicadores de escritura (typing), presencia online y confirmación de lectura (read receipts). Cuando el destinatario no está online, el Sistema de Notificaciones (doc 98) dispara alertas por el canal preferido.
Trazabilidad inmutable con hash chain: Audit log que registra envío, entrega, lectura, descarga de adjuntos y eliminación, con cadena de hash SHA-256 (mismo patrón que document_audit_log del doc 88). Cada registro incluye el hash del anterior, creando una cadena criptográficamente verificable. Prueba legal de comunicación profesional-cliente.
Integración nativa con IA: El Copilot de Servicios (doc 93) accede al historial de mensajes para RAG contextual: “Resume lo que el cliente García me ha dicho sobre la escritura”. Los mensajes se indexan en Qdrant para búsqueda semántica. El AI Skills System (doc 129) incluye el skill client_communication para redacción asistida de mensajes.
Adjuntos cifrados vía Buzón de Confianza: Los archivos compartidos en conversaciones se enrutan automáticamente al DocumentVaultService (doc 88), reutilizando todo el pipeline de cifrado E2E, versionado y audit log existente. No se duplica lógica ni almacenamiento.

1.3 Diferenciadores vs. Competencia
Característica	WhatsApp Business	Intercom	Clio Manage	Jaraba Messaging
Cifrado en reposo	E2E (Meta controla claves)	TLS + at-rest	AES-256	AES-256-GCM + clave por tenant (Argon2id)
Multi-tenant SaaS	No	Workspace básico	Por despacho	Full multi-tenant con Group Module
Vinculado a expediente	No	Tickets de soporte	Sí (nativo legal)	Sí + Buzón Confianza + Firma Digital
Audit log inmutable	No	Básico (logs)	Enterprise only	Hash chain criptográfico (blockchain-like)
IA contextual RAG	No	Fin (básico)	No	Copilot con RAG sobre expediente completo
Firma digital integrada	No	No	Parcial (DocuSign)	AutoFirma/eIDAS nativo (doc 89)
Aplicabilidad	General	Solo soporte/ventas	Solo legal	5 verticales profesionales unificadas
Notificaciones offline	Push propio	Email + Push	Email	5 canales con fallback inteligente (doc 98)
RGPD/LOPD compliance	Datos en Meta (US)	Datos en US	US/EU	100% EU, data sovereignty, LOPD-GDD
Coste adicional	~0.05€/msg API	Desde 74€/seat/mes	Desde 39$/user/mes	Incluido en planes Pro/Enterprise

1.4 Aplicabilidad Cross-Vertical
El módulo se diseña como componente de plataforma (Platform-level, no vertical-specific) siguiendo el patrón ‘Base + Extension’ del ecosistema. La entidad secure_conversation acepta un context_type genérico que se vincula a la entidad específica de cada vertical:
Vertical	Participantes Típicos	context_type	Caso de Uso Principal
ServiciosConecta	Profesional ↔ Cliente	client_case	Abogado comunica estado de caso, solicita información adicional, envía borradores
JarabaLex	Abogado ↔ Cliente	legal_case	Consultas rápidas sobre plazos, notificación resoluciones, envío escritos para revisión
Empleabilidad	Orientador ↔ Candidato	learning_path	Seguimiento formativo, feedback tras entrevistas, motivación y reenganche
Emprendimiento	Mentor ↔ Emprendedor	action_plan	Asesoramiento continuo entre sesiones, revisión de hitos, compartir recursos
AgroConecta	Productor ↔ Comprador B2B	order	Negociación de condiciones, personalización de pedidos, coordinación logística
ComercioConecta	Comerciante ↔ Cliente	order	Consultas pre-compra, personalización productos, soporte post-venta

1.5 Métricas de Reutilización
Siguiendo la filosofía ‘Sin Humo’, el módulo maximiza la reutilización de componentes existentes:
Componente Reutilizado	Documento Fuente	Reutilización Específica	Ahorro Estimado
Cifrado AES-256-GCM	doc 88 Buzón Confianza	Jerarquía de claves, Argon2id, Web Crypto API patterns	~30h de desarrollo criptográfico
WebSocket infrastructure	doc 94 Dashboard Profesional	Conexión WS, autenticación JWT, event handlers	~15h de infraestructura WS
Redis Pub/Sub	doc 34 Collaboration Groups	Canal de distribución de mensajes cross-proceso	~10h de message broker
Notification bridge	doc 98 Notificaciones Multicanal	5 canales, plantillas, preferencias, rate limiting	~20h de notificaciones
Audit log con hash chain	doc 88 document_audit_log	Patrón de registro inmutable con cadena SHA-256	~12h de audit trail
Permisos RBAC	doc 04 Permisos RBAC	Sistema de permisos granular por rol y tenant	~8h de authorization
ECA automation	doc 06 Flujos ECA	Plugin architecture para eventos de mensajería	~5h de automation hooks
Total reutilización estimada: ~100 horas de desarrollo ahorradas, lo que reduce el coste real del módulo de ~280h teóricas a ~140–180h efectivas.
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────┐
│              JARABA SECURE MESSAGING (jaraba_messaging)          │
├─────────────────────────────────────────────────────────────────┤
│  CAPA PRESENTACION                                              │
│  ┌───────────────┐  ┌────────────────┐  ┌───────────────────┐ │
│  │ Chat Panel UI │  │ WS Client (JS) │  │ Notification Toast │ │
│  │ React + Twig  │  │ Native WebSocket│  │ Badge + Sound     │ │
│  └───────┬───────┘  └────────┬───────┘  └─────────┬─────────┘ │
├─────────┴─────────────┴──────────────┴────────────────────────┤
│  CAPA API                                                       │
│  ┌───────────────┐  ┌────────────────┐  ┌───────────────────┐ │
│  │ REST API     │  │ WebSocket Server│  │ Webhook Receivers  │ │
│  │ Controllers  │  │ Ratchet/Swoole  │  │ (delivery status)  │ │
│  └───────┬───────┘  └────────┬───────┘  └─────────┬─────────┘ │
├─────────┴─────────────┴──────────────┴────────────────────────┤
│  CAPA SERVICIOS                                                 │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │                  MessagingService                          │ │
│  │              (Orquestador Central del Módulo)               │ │
│  └────┬─────────┬─────────┬─────────┬─────────┬─────────┬────┘ │
│       │         │         │         │         │         │     │
│  ┌────┴──┐ ┌───┴───┐ ┌───┴───┐ ┌───┴───┐ ┌───┴──┐ ┌───┴──┐     │
│  │Encrypt│ │Convers│ │Message│ │Notif. │ │Attach │ │Audit  │     │
│  │Service│ │Service│ │Service│ │Bridge │ │Bridge │ │Service│     │
│  └───┬───┘ └───┬───┘ └───┬───┘ └───┬───┘ └───┬──┘ └───┬──┘     │
├─────┴────────┴────────┴────────┴────────┴───────┴───────────────┤
│  INTEGRACIONES CON ECOSISTEMA EXISTENTE                         │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐     │
│  │ Buzon     │ │ Notifica- │ │ Copilot   │ │ Redis     │     │
│  │ Confianza │ │ ciones    │ │ IA/RAG   │ │ Pub/Sub   │     │
│  │ (doc 88)  │ │ (doc 98)  │ │ (doc 93)  │ │ (doc 34)  │     │
│  └───────────┘ └───────────┘ └───────────┘ └───────────┘     │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐     │
│  │ Portal    │ │ Dashboard │ │ RBAC      │ │ Qdrant    │     │
│  │ Cliente   │ │ Profes.  │ │ Permisos  │ │ VectorDB  │     │
│  │ (doc 90)  │ │ (doc 94)  │ │ (doc 04)  │ │ (Semantic) │     │
│  └───────────┘ └───────────┘ └───────────┘ └───────────┘     │
└─────────────────────────────────────────────────────────────────┘

2.2 Stack Tecnológico
Componente	Tecnología	Justificación
Backend Core	Drupal 11 Custom Entities + Service Layer	Coherencia con ecosistema, reutilización de RBAC, multi-tenant, ECA
WebSocket Server	Ratchet PHP 0.4 (dev) / Swoole 5.x (prod)	Ratchet para desarrollo rápido, Swoole para rendimiento en producción con coroutines
Message Broker	Redis 7 Pub/Sub + Streams	Ya desplegado para cache; pub/sub para distribución cross-proceso, streams para persistencia de eventos
Cifrado en reposo	AES-256-GCM + Argon2id (clave por tenant)	Mismo stack criptográfico que Buzón de Confianza (doc 88), reutilización directa
Cifrado en tránsito	TLS 1.3 (HTTPS) + WSS (WebSocket Secure)	Estándar industria, certificado Let’s Encrypt ya configurado en IONOS
Adjuntos	DocumentVaultService (doc 88)	Reutilización total: cifrado E2E, versionado, audit log, sin duplicar almacenamiento
Notificaciones offline	jaraba_notifications (doc 98)	5 canales con fallback: Email (SendGrid), SMS (Twilio), WhatsApp, Push (FCM), In-App
Búsqueda semántica	Qdrant + embeddings (Gemini/OpenAI)	Indexación de mensajes para RAG del Copilot; búsqueda por similitud semántica
Búsqueda full-text	MySQL FULLTEXT en body_preview	Búsqueda rápida sin dependencia externa para queries simples
Cola asíncrona	Drupal Queue API + Redis	Procesamiento de indexación Qdrant, generación de previews, limpieza RGPD
UI Chat	React component (slide-in panel lateral)	Consistente con UI del Copilot (doc 93); no interrumpe navegación principal
Typing/Presencia	Redis SETEX con TTL de 5s	Ephemeral: no persiste en BD, solo en memoria Redis con auto-expiración
Audit Log	message_audit_log con hash chain SHA-256	Patrón idéntico a document_audit_log (doc 88), verificable criptográficamente

2.3 Relación con Componentes Existentes
Este módulo NO reemplaza ningún componente existente. Extiende el ecosistema añadiendo comunicación bidireccional que se integra con cada pieza. El principio de diseño es: si existe un servicio que hace algo, delegamos a él en lugar de reimplementar.
Componente	Doc	Integración	Código Reutilizado
Buzón de Confianza	88	Adjuntos: archivos enviados en chat se cifran y almacenan en vault	DocumentVaultService::store(), VaultClient (WebCrypto), cifrado AES-256-GCM, audit log
Portal Cliente	90	Nuevo tab ‘Mensajes’ junto a ‘Documentos’ y ‘Expediente’ en el portal del cliente	ClientCaseService, portal UI framework, token-based access
Notificaciones	98	Puente: mensaje nuevo → notificación offline si destinatario no conectado por WebSocket	NotificationService::send(), canales, plantillas MJML, preferencias usuario, anti-spam
Copilot Servicios	93	RAG sobre mensajes: Copilot busca en historial de conversaciones para contextualizar respuestas	RAG pipeline, Qdrant indexing, system prompts, strict grounding
Dashboard Profesional	94	Widget ‘Mensajes no leídos’ en dashboard + infraestructura WebSocket existente	WebSocket connection manager, JWT auth, event dispatch, badge counter pattern
Grupos Colaboración	34	Mensajería 1:1 complementa foros de grupo; comparte Redis pub/sub	Redis pub/sub infrastructure, activity stream pattern, notification digest
ECA Module	06	Eventos de mensajería como triggers para automatizaciones del ecosistema	Plugin architecture: Event/Condition/Action, YAML config, async queue
RBAC	04	Permisos granulares: quién puede iniciar conversación con quién, por rol y vertical	Permission system, role hierarchy, tenant isolation, context-based access
AI Skills	129	Skill client_communication para redacción asistida de mensajes profesionales	Skill registry, task types (client_email, follow_up), template system

2.4 Flujo de Mensaje: Envío a Entrega
Flujo completo de un mensaje desde que el profesional pulsa ‘Enviar’ hasta que el cliente lo lee:
Paso	Actor	Acción	Componente	Latencia
1	Profesional (browser)	Escribe mensaje y pulsa Enviar	Chat Panel UI (React)	0ms
2	JS Client	Cifra body con tenant key (AES-256-GCM via WebCrypto API)	MessagingClient.js	<5ms
3	JS Client	Envía mensaje cifrado por WebSocket (WSS)	Native WebSocket	<10ms
4	WS Server	Valida JWT, verifica permisos RBAC, anti-flood check	MessagingWebSocket.php	<5ms
5	MessagingService	Persiste en BD (body_encrypted + iv + tag + hash)	ConversationService.php	<15ms
6	MessagingService	Registra en audit log con hash chain	MessageAuditService.php	<5ms
7	MessagingService	Publica en Redis channel: msg:{conversation_id}	Redis Pub/Sub	<2ms
8a	WS Server	Si destinatario conectado: entrega instantánea por WebSocket	Ratchet/Swoole broadcast	<5ms
8b	NotificationBridge	Si destinatario offline: dispara notificación multicanal (doc 98)	jaraba_notifications	<30s
9	Queue Worker	Asíncrono: indexa mensaje en Qdrant para RAG del Copilot	Drupal Queue + Qdrant	<5s
10	Cliente (browser)	Recibe mensaje por WS, descifra, renderiza en Chat Panel	MessagingClient.js	<5ms
11	JS Client	Envía read receipt por WebSocket	MessagingClient.js	<5ms
12	MessagingService	Actualiza read_by en mensaje, registra en audit log	MessageService.php	<10ms
Latencia total end-to-end (online): <60ms — comparable a WhatsApp Web (~50-80ms en condiciones óptimas).

2.5 Estructura del Módulo
modules/custom/jaraba_messaging/
├── jaraba_messaging.info.yml
├── jaraba_messaging.module
├── jaraba_messaging.services.yml
├── jaraba_messaging.permissions.yml
├── jaraba_messaging.routing.yml
├── jaraba_messaging.links.menu.yml
├── src/
│   ├── Entity/
│   │   ├── SecureConversation.php
│   │   ├── ConversationParticipant.php
│   │   ├── SecureMessage.php
│   │   └── MessageAuditLog.php
│   ├── Service/
│   │   ├── MessagingService.php           # Orquestador central
│   │   ├── ConversationService.php        # CRUD conversaciones
│   │   ├── MessageService.php             # CRUD mensajes
│   │   ├── MessageEncryptionService.php   # Cifrado/descifrado AES-256-GCM
│   │   ├── MessageAuditService.php        # Audit log con hash chain
│   │   ├── NotificationBridgeService.php  # Puente a doc 98
│   │   ├── AttachmentBridgeService.php    # Puente a doc 88
│   │   ├── PresenceService.php            # Online/typing via Redis
│   │   ├── SearchService.php              # Full-text + Qdrant semantic
│   │   └── RetentionService.php           # Limpieza RGPD programada
│   ├── Controller/
│   │   ├── ConversationController.php     # REST API conversaciones
│   │   ├── MessageController.php          # REST API mensajes
│   │   └── PresenceController.php         # REST API presencia
│   ├── WebSocket/
│   │   ├── MessagingWebSocketServer.php   # Servidor WS principal
│   │   ├── ConnectionManager.php          # Pool de conexiones activas
│   │   ├── MessageHandler.php             # Procesamiento de mensajes WS
│   │   └── AuthMiddleware.php             # Validacion JWT en WS handshake
│   ├── Plugin/
│   │   └── ECA/
│   │       ├── Event/MessageSentEvent.php
│   │       ├── Event/MessageReadEvent.php
│   │       ├── Event/ConversationCreatedEvent.php
│   │       ├── Condition/IsFirstMessage.php
│   │       └── Action/SendAutoReply.php
│   └── Queue/
│       ├── MessageIndexWorker.php         # Indexacion Qdrant asincrona
│       └── RetentionCleanupWorker.php     # Limpieza RGPD programada
├── js/
│   ├── messaging-client.js                # WebSocket client + cifrado
│   ├── chat-panel.jsx                     # React chat UI component
│   ├── conversation-list.jsx              # Lista de conversaciones
│   └── message-bubble.jsx                 # Componente de mensaje individual
├── templates/
│   ├── chat-panel.html.twig               # Contenedor Twig del chat
│   └── conversation-widget.html.twig      # Widget para dashboard
├── config/
│   ├── install/
│   │   └── jaraba_messaging.settings.yml  # Configuracion por defecto
│   └── eca/
│       ├── eca.model.message_sent.yml     # Flujo ECA: mensaje enviado
│       ├── eca.model.first_message.yml    # Flujo ECA: primera respuesta
│       └── eca.model.unread_reminder.yml  # Flujo ECA: recordatorio no leidos
└── tests/
    ├── src/Kernel/MessagingServiceTest.php
    ├── src/Kernel/EncryptionServiceTest.php
    └── src/Functional/ConversationApiTest.php
 
3. Modelo de Datos
El modelo de datos consta de 4 entidades principales y 1 entidad de soporte. Todas las entidades incluyen tenant_id para aislamiento multi-tenant (doc 07) y siguen las convenciones de nomenclatura del ecosistema Jaraba.
3.1 Entidad: secure_conversation
Representa una conversación entre dos o más participantes, opcionalmente vinculada a un contexto (expediente, reserva, grupo, pedido). Una conversación existe independientemente de los mensajes que contenga.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno autoincrementable	PRIMARY KEY
uuid	UUID	Identificador único público (para APIs)	UNIQUE, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
title	VARCHAR(255)	Título opcional de la conversación	NULLABLE (auto-gen si vacío: 'Conv. con {participante}')
conversation_type	VARCHAR(32)	Tipo de conversación	ENUM: direct|case_linked|booking_linked|group_dm|support|ai_assisted, NOT NULL, INDEX
context_type	VARCHAR(64)	Entity type de contexto vinculado	NULLABLE (ej: client_case, booking, order, learning_path, action_plan)
context_id	INT	ID de la entidad de contexto	NULLABLE, INDEX compuesto con context_type
initiated_by	INT	Usuario que inició la conversación	FK users.uid, NOT NULL
encryption_key_id	VARCHAR(64)	Referencia a clave de cifrado del tenant	NOT NULL (derivada de tenant master key via Argon2id)
is_confidential	BOOLEAN	Modo confidencialidad elevada (restricción acceso IA)	DEFAULT FALSE
max_participants	INT	Límite de participantes	DEFAULT 10, NOT NULL, CHECK > 1
is_archived	BOOLEAN	Conversación archivada por profesional	DEFAULT FALSE
is_muted_by_system	BOOLEAN	Silenciada por admin/sistema (ej: moderación)	DEFAULT FALSE
last_message_at	DATETIME(3)	Timestamp último mensaje con precisión de milisegundos	NULLABLE, INDEX (para ordenación)
last_message_preview	VARCHAR(255)	Preview cifrado del último mensaje (truncado)	NULLABLE
last_message_sender_id	INT	Remitente del último mensaje	NULLABLE, FK users.uid
message_count	INT	Total mensajes en conversación (counter cache)	DEFAULT 0, NOT NULL
participant_count	TINYINT	Total participantes activos (counter cache)	DEFAULT 0, NOT NULL
metadata	JSON	Metadatos extensibles por vertical	NULLABLE (ej: {"vertical": "legal", "priority": "high"})
retention_days	INT	Días de retención para política RGPD	NULLABLE (NULL = política por defecto del tenant)
auto_close_days	INT	Días de inactividad antes de auto-cerrar	NULLABLE (NULL = no auto-cerrar)
status	VARCHAR(16)	Estado de la conversación	ENUM: active|archived|closed|deleted, DEFAULT active, NOT NULL
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Ultima modificación	NOT NULL

Indices de secure_conversation
Índice	Campos	Propósito
idx_conv_tenant_status	tenant_id, status, last_message_at DESC	Listado de conversaciones activas por tenant (query principal)
idx_conv_context	context_type, context_id	Búsqueda de conversación vinculada a un expediente/reserva/pedido
idx_conv_last_msg	last_message_at DESC	Ordenación cronológica global
idx_conv_type	conversation_type, tenant_id	Filtrado por tipo de conversación
idx_conv_initiated	initiated_by, created DESC	Conversaciones iniciadas por un usuario

3.2 Entidad: conversation_participant
Tabla de unión que define los participantes de cada conversación con su rol, permisos y estado de lectura. Permite configuración granular de notificaciones por conversación.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
conversation_id	INT	Conversación	FK secure_conversation.id ON DELETE CASCADE, NOT NULL
user_id	INT	Usuario participante	FK users.uid, NOT NULL
role	VARCHAR(32)	Rol en la conversación	ENUM: owner|participant|observer|system, NOT NULL, DEFAULT participant
display_name	VARCHAR(128)	Nombre mostrado (override)	NULLABLE (hereda de user profile si NULL)
can_send	BOOLEAN	Permiso para enviar mensajes	DEFAULT TRUE
can_attach	BOOLEAN	Permiso para adjuntar archivos	DEFAULT TRUE
can_invite	BOOLEAN	Permiso para invitar otros participantes	DEFAULT FALSE
is_muted	BOOLEAN	Notificaciones silenciadas por el usuario	DEFAULT FALSE
is_pinned	BOOLEAN	Conversación fijada en lista del usuario	DEFAULT FALSE
last_read_at	DATETIME(3)	Timestamp del último mensaje leído	NULLABLE
last_read_message_id	BIGINT	ID del último mensaje leído	NULLABLE, FK secure_message.id
unread_count	INT	Mensajes no leídos (counter cache)	DEFAULT 0
notification_pref	VARCHAR(16)	Override de notificación para esta conversación	ENUM: default|all|mentions|none, DEFAULT default
joined_at	DATETIME	Fecha de unión a la conversación	NOT NULL
left_at	DATETIME	Fecha de salida	NULLABLE
removed_by	INT	Quién eliminó al participante	NULLABLE, FK users.uid
status	VARCHAR(16)	Estado de participación	ENUM: active|left|removed|blocked, DEFAULT active, NOT NULL
Constraint UNIQUE: (conversation_id, user_id) — Un usuario solo puede ser participante una vez por conversación.
Constraint CHECK: Al menos 2 participantes con status=active por conversación (enforced a nivel de servicio, no de BD).

3.3 Entidad: secure_message
Cada mensaje individual dentro de una conversación. El cuerpo se almacena cifrado con AES-256-GCM usando clave derivada del tenant. Se usa BIGSERIAL como PK por el alto volumen esperado de mensajes.
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno (BIGINT para alto volumen)	PRIMARY KEY
uuid	UUID	Identificador único público	UNIQUE, NOT NULL, INDEX
conversation_id	INT	Conversación contenedora	FK secure_conversation.id, NOT NULL, INDEX
sender_id	INT	Usuario remitente	FK users.uid, NOT NULL, INDEX
message_type	VARCHAR(24)	Tipo de mensaje	ENUM: text|file|image|voice_note|system|ai_suggestion|location, NOT NULL
body_encrypted	MEDIUMBLOB	Cuerpo cifrado (AES-256-GCM)	NOT NULL
body_iv	VARBINARY(12)	IV para cifrado (12 bytes)	NOT NULL
body_tag	VARBINARY(16)	Authentication tag GCM (16 bytes)	NOT NULL
body_plaintext_hash	CHAR(64)	SHA-256 del texto plano (integridad y deduplicación)	NOT NULL
body_length	INT	Longitud del texto plano original en caracteres	NOT NULL
body_preview_encrypted	VARBINARY(512)	Preview cifrado (primeros 100 chars, para listado)	NULLABLE
reply_to_id	BIGINT	Mensaje al que responde (threading)	NULLABLE, FK secure_message.id
forward_from_id	BIGINT	Mensaje reenviado desde otra conversación	NULLABLE, FK secure_message.id
attachment_ids	JSON	UUIDs de documentos en Buzón de Confianza	NULLABLE (ej: ["uuid-doc-1", "uuid-doc-2"])
attachment_count	TINYINT	Número de adjuntos (counter cache)	DEFAULT 0
mentions	JSON	Usuarios mencionados en el mensaje	NULLABLE (ej: [{"uid": 5, "name": "García", "offset": 12}])
reactions	JSON	Reacciones al mensaje	NULLABLE (ej: {"thumbsup": [3,7], "check": [3]})
metadata	JSON	Metadatos extensibles	NULLABLE (ej: {"ai_generated": true, "skill_used": "client_communication"})
is_edited	BOOLEAN	Editado después de enviar	DEFAULT FALSE
edited_at	DATETIME(3)	Timestamp de última edición	NULLABLE
original_body_encrypted	MEDIUMBLOB	Cuerpo original antes de edición (cifrado)	NULLABLE (solo si is_edited=true)
is_deleted	BOOLEAN	Soft-delete por el remitente	DEFAULT FALSE
deleted_at	DATETIME(3)	Timestamp de eliminación	NULLABLE
deleted_by	INT	Quién eliminó (remitente o admin)	NULLABLE, FK users.uid
delivered_at	DATETIME(3)	Timestamp entrega al servidor WS	NULLABLE
status	VARCHAR(16)	Estado del mensaje	ENUM: sending|sent|delivered|read|failed, DEFAULT sending, NOT NULL
created	DATETIME(3)	Fecha creación (precisión milisegundos)	NOT NULL

Indices de secure_message
Índice	Campos	Propósito
idx_msg_conv_created	conversation_id, created DESC	Paginación de mensajes de una conversación (query más frecuente)
idx_msg_sender	sender_id, created DESC	Mensajes enviados por un usuario específico
idx_msg_reply	reply_to_id	Búsqueda de respuestas a un mensaje (threading)
idx_msg_status	conversation_id, status	Mensajes pendientes de entrega/lectura
idx_msg_search	FULLTEXT(body_preview_encrypted)	Búsqueda full-text en previews (solo sobre datos descifrados en runtime)

Política de Particionado (escalabilidad)
Para tenants con alto volumen (>100K mensajes), se recomienda particionado por rango de fechas:
-- Particionado por mes (implementar cuando message_count > 100K por tenant)
ALTER TABLE secure_message PARTITION BY RANGE (YEAR(created) * 100 + MONTH(created)) (
  PARTITION p_2026_01 VALUES LESS THAN (202602),
  PARTITION p_2026_02 VALUES LESS THAN (202603),
  -- ... particiones futuras se crean vía cron mensual
  PARTITION p_future VALUES LESS THAN MAXVALUE
);

3.4 Entidad: message_audit_log
Registro inmutable de todas las operaciones sobre mensajes y conversaciones. Append-only: nunca se modifica ni elimina. Implementa el mismo patrón de hash chain que document_audit_log del Buzón de Confianza (doc 88).
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID autoincremental	PRIMARY KEY
tenant_id	INT	Tenant (para queries rápidas)	FK tenant.id, NOT NULL, INDEX
conversation_id	INT	Conversación afectada	FK secure_conversation.id, NOT NULL, INDEX
message_id	BIGINT	Mensaje afectado (si aplica)	NULLABLE, FK secure_message.id
action	VARCHAR(40)	Tipo de acción realizada	ENUM (ver catálogo abajo), NOT NULL
actor_id	INT	Usuario que realizó la acción	NULLABLE (NULL = sistema), FK users.uid
actor_ip	VARCHAR(45)	IP del actor (IPv4/IPv6)	NOT NULL
actor_user_agent	VARCHAR(512)	User-Agent del navegador	NULLABLE
details	JSON	Detalles adicionales de la acción	NULLABLE (ej: {"recipient_id": 7, "channel": "ws"})
created	DATETIME(6)	Timestamp con precisión de microsegundos	NOT NULL, INDEX
hash_chain	CHAR(64)	SHA-256(prev_hash + this_record)	NOT NULL (verificación integridad)

Catálogo de Acciones del Audit Log
Acción	Trigger	Detalles Registrados
conversation.created	Nueva conversación iniciada	{participants: [...], context_type, context_id}
conversation.archived	Profesional archiva conversación	{reason: 'manual'|'auto_close'}
conversation.closed	Conversación cerrada definitivamente	{reason, closed_by}
conversation.reopened	Conversación reactivada	{reopened_by}
participant.added	Nuevo participante añadido	{user_id, role, added_by}
participant.removed	Participante eliminado	{user_id, removed_by, reason}
participant.left	Participante abandona voluntariamente	{user_id}
message.sent	Mensaje enviado	{message_uuid, type, body_length, has_attachments}
message.delivered	Mensaje entregado por WebSocket	{message_uuid, recipient_id, channel: 'ws'}
message.read	Destinatario lee el mensaje	{message_uuid, reader_id}
message.edited	Remitente edita mensaje	{message_uuid, original_hash, new_hash}
message.deleted	Mensaje eliminado (soft)	{message_uuid, deleted_by, reason}
file.attached	Archivo adjuntado a mensaje	{message_uuid, vault_doc_uuid, filename, size}
file.downloaded	Destinatario descarga adjunto	{vault_doc_uuid, downloader_id}
notification.sent	Notificación offline disparada (doc 98)	{recipient_id, channel: 'email'|'sms'|'whatsapp'|'push'}
search.performed	Búsqueda en historial de conversación	{query_hash, results_count} (no se registra el query en claro)
retention.applied	Limpieza RGPD ejecutada	{messages_purged, oldest_date, policy_days}
export.requested	Exportación de datos solicitada (RGPD art. 20)	{requester_id, format: 'json'|'pdf'}

3.5 Entidad: message_read_receipt (Soporte)
Tabla dedicada para read receipts en conversaciones de más de 2 participantes. Para conversaciones 1:1, el campo read_by del mensaje es suficiente; esta tabla se usa cuando hay 3+ participantes y se necesita tracking granular.
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
message_id	BIGINT	Mensaje leído	FK secure_message.id, NOT NULL
user_id	INT	Usuario que leyó	FK users.uid, NOT NULL
read_at	DATETIME(3)	Momento de lectura	NOT NULL
Constraint UNIQUE: (message_id, user_id) — Un usuario solo puede marcar un mensaje como leído una vez.

3.6 Diagrama Entidad-Relación
secure_conversation 1────N conversation_participant N────1 users
       │                                                       │
       │ 1────N secure_message N────1 users (sender)            │
       │               │                                        │
       │               ├── 0..N message_read_receipt             │
       │               ├── 0..N secure_document (via attachment_ids, doc 88)
       │               └── self-ref (reply_to_id, forward_from_id)
       │
       └───── 1────N message_audit_log

secure_conversation.context_type + context_id ────▶ Polimórfico:
  • client_case (doc 90)     • booking (doc 85)
  • order (doc 49/67)        • learning_path (doc 09)
  • action_plan (doc 29)     • group (doc 34)
 
4. Arquitectura de Seguridad
4.1 Decisión Arquitectónica: Cifrado Servidor vs. E2E
Se evalúan tres opciones de cifrado. La decisión se toma considerando que la IA contextual (Copilot) es un diferenciador competitivo clave de la plataforma:
Opción	Descripción	Ventajas	Limitaciones	Veredicto
A: E2E puro	Cifrado extremo a extremo. Servidor no puede leer mensajes (patrón Buzón doc 88)	Máxima seguridad, zero-knowledge	Copilot IA no puede acceder contenido; búsqueda full-text imposible en servidor; sin compliance scan	Descartada para v1
B: Cifrado en servidor con clave por tenant	Mensajes cifrados en tránsito (TLS 1.3) y reposo (AES-256-GCM con clave derivada por tenant)	IA puede contextualizar; búsqueda funcional; compliance posible; estándar industria (Clio, PracticePanther)	Administradores con acceso a BD + clave podrían descifrar teóricamente	SELECCIONADA
C: Híbrida	Opción B por defecto + flag is_confidential que activa E2E puro	Lo mejor de ambos mundos	Mayor complejidad, doble lógica de cifrado	Preparada para v2
Justificación Sin Humo: El 95% del valor del módulo está en la combinación mensajería + IA + trazabilidad. E2E puro mata dos de esas tres patas. Las plataformas líderes del sector legal (Clio, PracticePanther, Smokeball) usan cifrado en servidor con clave por cuenta, y sus clientes —abogados— lo aceptan. El campo is_confidential queda preparado para activar E2E puro en conversaciones específicas si hay demanda real.

4.2 Jerarquía de Claves
JERARQUÍA DE CLAVES (reutiliza patrón doc 88)

┌─────────────────────────────────────────────────────┐
│  Platform Master Key (PMK)                          │
│  Almacenada en variable de entorno (JARABA_PMK)      │
│  Nunca persiste en BD ni en código                   │
└───────────────────────┬─────────────────────────────┘
                        │ Argon2id(PMK + tenant_salt)
               ┌────────┴────────┐
               │  Tenant Key (TK)     │  ← Derivada por tenant
               │  Única por tenant     │  ← Almacenada cifrada en BD
               └──────┬───────┬──────┘
                     │       │
          ┌────────┴─┐   ┌─┴─────────┐
          │ Mensajes   │   │ Previews   │
          │ AES-256-GCM│   │ AES-256-GCM│
          │ IV=12 bytes│   │ IV=12 bytes│
          └───────────┘   └──────────┘

4.3 Algoritmos Criptográficos
Componente	Algoritmo	Parámetros	Referencia
Derivación de TK	Argon2id	memory=64MB, iterations=3, parallelism=4, output=32 bytes	doc 88 §2.2
Cifrado de mensajes	AES-256-GCM	IV=12 bytes random (CSPRNG), tag=16 bytes	doc 88 §2.2
Cifrado de previews	AES-256-GCM	Misma TK, IV independiente por preview	Nuevo
Hash de integridad	SHA-256	Sobre texto plano antes de cifrar	doc 88 §3.1
Hash chain (audit)	SHA-256	SHA-256(prev_hash + json(record))	doc 88 §3.3
Generación IV/salt	CSPRNG	random_bytes() de PHP (/dev/urandom)	doc 88 §2.2
Token de sesión WS	JWT (HS256)	Exp: 1h, refresh transparente	doc 94

4.4 Servicio de Cifrado (MessageEncryptionService)
<?php
namespace Drupal\jaraba_messaging\Service;

class MessageEncryptionService {

  private TenantKeyService $tenantKeyService;

  public function encrypt(string $plaintext, int $tenantId): EncryptedPayload {
    // 1. Obtener Tenant Key (cached en memoria del proceso)
    $tk = $this->tenantKeyService->getTenantKey($tenantId);

    // 2. Generar IV aleatorio (12 bytes para GCM)
    $iv = random_bytes(12);

    // 3. Cifrar con AES-256-GCM
    $ciphertext = openssl_encrypt(
      $plaintext,
      'aes-256-gcm',
      $tk,
      OPENSSL_RAW_DATA,
      $iv,
      $tag,
      '',     // AAD (Additional Authenticated Data) - vacío
      16      // Tag length
    );

    if ($ciphertext === false) {
      throw new EncryptionException('AES-256-GCM encryption failed');
    }

    // 4. Calcular hash del texto plano (integridad)
    $plaintextHash = hash('sha256', $plaintext);

    return new EncryptedPayload(
      ciphertext: $ciphertext,
      iv: $iv,
      tag: $tag,
      plaintextHash: $plaintextHash,
      plaintextLength: mb_strlen($plaintext, 'UTF-8'),
    );
  }

  public function decrypt(EncryptedPayload $payload, int $tenantId): string {
    $tk = $this->tenantKeyService->getTenantKey($tenantId);

    $plaintext = openssl_decrypt(
      $payload->ciphertext,
      'aes-256-gcm',
      $tk,
      OPENSSL_RAW_DATA,
      $payload->iv,
      $payload->tag
    );

    if ($plaintext === false) {
      throw new DecryptionException('AES-256-GCM decryption failed - tampered data?');
    }

    // Verificar integridad
    if (hash('sha256', $plaintext) !== $payload->plaintextHash) {
      throw new IntegrityException('Plaintext hash mismatch');
    }

    return $plaintext;
  }

  public function encryptPreview(string $plaintext, int $tenantId): EncryptedPayload {
    $preview = mb_substr($plaintext, 0, 100, 'UTF-8');
    return $this->encrypt($preview, $tenantId);
  }
}

4.5 Modelo de Amenazas y Mitigaciones
Amenaza	Vector	Mitigación	Componente Responsable
Interceptación en tránsito	MITM en red	TLS 1.3 + WSS obligatorio; HSTS header	Nginx/Apache + Let's Encrypt
Acceso no autorizado a BD	SQL injection, backup robado	Cifrado AES-256-GCM en reposo; BD cifrada en disco (IONOS)	MessageEncryptionService + IONOS
Suplantación de identidad	Token WS robado	JWT con exp=1h + refresh; validación IP/UA en cada mensaje	AuthMiddleware.php
Manipulación de audit log	Admin malicioso altera registros	Hash chain SHA-256 inmutable; verificación de integridad programada	MessageAuditService.php
Flood/DoS por mensajes	Usuario envía miles de mensajes	Rate limiting: 30 msg/min por usuario; 100 msg/min por conv.	MessagingService + Redis counter
Enumeración de conversaciones	Iterar UUIDs	UUIDs v4 aleatorios (122 bits entropía); permisos RBAC en cada request	ConversationController.php
XSS en contenido de mensajes	HTML/JS en body del mensaje	Sanitización en renderizado (DOMPurify en React); body almacenado como texto plano	chat-panel.jsx
Acceso cross-tenant	Bug en filtrado de tenant	tenant_id obligatorio en EVERY query; middleware de tenant isolation	Multi-tenant middleware (doc 07)
Fuga de datos por IA	Copilot muestra datos de otro tenant	Filtrado estricto por tenant_id en Qdrant; strict grounding (doc 93)	SearchService.php + Qdrant filters
Exfiltración masiva	Descarga de todo el historial	Rate limit en export API; audit log de exportaciones; alertas a admin	RetentionService.php + ECA alert

4.6 Política de Retención RGPD
Configuración	Valor por Defecto	Override	Acción
Retención mensajes	730 días (2 años)	Configurable por tenant en admin	Soft-delete + purge de body_encrypted después de periodo
Retención audit log	2555 días (7 años)	Mínimo 3 años (obligación legal profesional)	Los registros de audit NUNCA se eliminan automáticamente
Derecho al olvido	Bajo demanda	Via API o panel admin	Anonimiza sender_id, purga body_encrypted, mantiene audit con hash
Portabilidad (art. 20)	Bajo demanda	Via API REST	Exportación JSON/PDF del historial completo descifrado
Conversaciones cerradas	90 días tras cierre	Configurable	Auto-archive después del periodo configurado
 
5. Servicios Principales
5.1 MessagingService (Orquestador Central)
Clase de servicio principal que coordina todos los sub-servicios. Cada operación pública es atómica y transaccional.
<?php
namespace Drupal\jaraba_messaging\Service;

class MessagingService {

  public function __construct(
    private ConversationService $conversations,
    private MessageService $messages,
    private MessageEncryptionService $encryption,
    private MessageAuditService $audit,
    private NotificationBridgeService $notifications,
    private AttachmentBridgeService $attachments,
    private PresenceService $presence,
    private SearchService $search,
    private Connection $database,
    private AccountProxyInterface $currentUser,
    private RequestStack $requestStack,
  ) {}

  /**
   * Envía un mensaje en una conversación existente.
   * Flujo completo: cifrar -> persistir -> audit -> broadcast -> notify
   */
  public function sendMessage(
    int $conversationId,
    string $body,
    string $messageType = 'text',
    ?int $replyToId = NULL,
    array $attachmentUuids = [],
    array $mentions = [],
    array $metadata = [],
  ): SecureMessage {

    $conversation = $this->conversations->load($conversationId);
    $senderId = $this->currentUser->id();
    $tenantId = $conversation->getTenantId();

    // 1. Validaciones
    $this->conversations->assertParticipantCanSend($conversation, $senderId);
    $this->validateRateLimit($senderId, $conversationId);
    $this->validateMessageBody($body, $messageType);

    // 2. Cifrar mensaje
    $encrypted = $this->encryption->encrypt($body, $tenantId);
    $previewEncrypted = $this->encryption->encryptPreview($body, $tenantId);

    // 3. Transacción atómica: persistir + audit + counters
    $message = $this->database->startTransaction(function () use (
      $conversation, $senderId, $encrypted, $previewEncrypted,
      $messageType, $replyToId, $attachmentUuids, $mentions, $metadata
    ) {
      // 3a. Crear mensaje cifrado
      $message = $this->messages->create([
        'conversation_id' => $conversation->id(),
        'sender_id' => $senderId,
        'message_type' => $messageType,
        'body_encrypted' => $encrypted->ciphertext,
        'body_iv' => $encrypted->iv,
        'body_tag' => $encrypted->tag,
        'body_plaintext_hash' => $encrypted->plaintextHash,
        'body_length' => $encrypted->plaintextLength,
        'body_preview_encrypted' => $previewEncrypted->ciphertext,
        'reply_to_id' => $replyToId,
        'attachment_ids' => $attachmentUuids ?: NULL,
        'attachment_count' => count($attachmentUuids),
        'mentions' => $mentions ?: NULL,
        'metadata' => $metadata ?: NULL,
        'status' => 'sent',
        'delivered_at' => new DateTime(),
      ]);

      // 3b. Actualizar contadores de conversación
      $this->conversations->updateLastMessage($conversation, $message);

      // 3c. Incrementar unread_count de todos los participantes excepto sender
      $this->conversations->incrementUnreadForOthers($conversation, $senderId);

      // 3d. Registrar en audit log
      $this->audit->log($conversation->id(), $message->id(), 'message.sent', [
        'message_uuid' => $message->uuid(),
        'type' => $messageType,
        'body_length' => $encrypted->plaintextLength,
        'has_attachments' => !empty($attachmentUuids),
      ]);

      return $message;
    });

    // 4. Post-transacción (no-rollback): broadcast + notify + index
    // 4a. Publicar en Redis para broadcast WebSocket
    $this->presence->broadcastMessage($conversation, $message, $senderId);

    // 4b. Notificar offline participants (doc 98)
    $this->notifications->notifyOfflineParticipants($conversation, $message);

    // 4c. Encolar indexación semántica en Qdrant (asíncrono)
    $this->search->enqueueForIndexing($message, $body);

    // 4d. Procesar adjuntos via Buzón de Confianza (doc 88)
    if (!empty($attachmentUuids)) {
      $this->attachments->linkToMessage($message, $attachmentUuids);
    }

    return $message;
  }

  /**
   * Crea nueva conversación y opcionalmente envía primer mensaje.
   */
  public function startConversation(
    array $participantIds,
    string $type = 'direct',
    ?string $contextType = NULL,
    ?int $contextId = NULL,
    ?string $firstMessage = NULL,
  ): SecureConversation {

    $initiator = $this->currentUser->id();
    $tenantId = $this->getTenantForUser($initiator);

    // Validar permisos de inicio de conversación
    $this->conversations->assertCanInitiate($initiator, $participantIds, $type);

    // Verificar conversación existente (evitar duplicados para direct)
    if ($type === 'direct' && count($participantIds) === 1) {
      $existing = $this->conversations->findDirect($initiator, $participantIds[0], $tenantId);
      if ($existing && $existing->getStatus() === 'active') {
        if ($firstMessage) {
          $this->sendMessage($existing->id(), $firstMessage);
        }
        return $existing;
      }
    }

    $conversation = $this->database->startTransaction(function () use (
      $participantIds, $type, $contextType, $contextId, $initiator, $tenantId
    ) {
      $conv = $this->conversations->create([
        'tenant_id' => $tenantId,
        'conversation_type' => $type,
        'context_type' => $contextType,
        'context_id' => $contextId,
        'initiated_by' => $initiator,
        'encryption_key_id' => 'tk_' . $tenantId,
      ]);

      // Añadir iniciador como owner
      $this->conversations->addParticipant($conv, $initiator, 'owner');

      // Añadir otros participantes
      foreach ($participantIds as $uid) {
        $this->conversations->addParticipant($conv, $uid, 'participant');
      }

      $this->audit->log($conv->id(), NULL, 'conversation.created', [
        'participants' => array_merge([$initiator], $participantIds),
        'context_type' => $contextType,
      ]);

      return $conv;
    });

    if ($firstMessage) {
      $this->sendMessage($conversation->id(), $firstMessage);
    }

    return $conversation;
  }

  /**
   * Marca mensajes como leídos hasta un punto.
   */
  public function markAsRead(int $conversationId, int $upToMessageId): void {
    $userId = $this->currentUser->id();
    $this->conversations->markRead($conversationId, $userId, $upToMessageId);
    $this->presence->broadcastReadReceipt($conversationId, $userId, $upToMessageId);
    $this->audit->log($conversationId, $upToMessageId, 'message.read', [
      'reader_id' => $userId,
    ]);
  }

  private function validateRateLimit(int $userId, int $convId): void {
    $key = "msg_rate:{$userId}:{$convId}";
    $count = (int) $this->redis->get($key);
    if ($count >= 30) { // 30 msg/min per user per conversation
      throw new RateLimitException('Message rate limit exceeded');
    }
    $this->redis->multi()->incr($key)->expire($key, 60)->exec();
  }
}

5.2 NotificationBridgeService (Puente a doc 98)
Conecta el sistema de mensajería con el Sistema de Notificaciones Multicanal existente (doc 98). Cuando un participante no está conectado por WebSocket, se le notifica por el canal preferido.
<?php
class NotificationBridgeService {

  public function notifyOfflineParticipants(
    SecureConversation $conv,
    SecureMessage $message,
  ): void {
    $participants = $this->conversations->getActiveParticipants($conv);
    $senderId = $message->getSenderId();

    foreach ($participants as $participant) {
      if ($participant->getUserId() === $senderId) continue;
      if ($participant->getNotificationPref() === 'none') continue;
      if ($participant->isMuted()) continue;

      // Verificar si está online via WebSocket
      if ($this->presence->isOnline($participant->getUserId())) {
        continue; // Ya recibirá el mensaje por WS
      }

      // Esperar 30s antes de notificar (puede conectarse en ese tiempo)
      $this->queue->createItem([
        'type' => 'offline_notification',
        'conversation_id' => $conv->id(),
        'message_id' => $message->id(),
        'recipient_id' => $participant->getUserId(),
        'delay_until' => time() + 30,
      ]);
    }
  }

  /**
   * Procesado por Queue Worker después del delay de 30s.
   * Reutiliza completamente jaraba_notifications (doc 98).
   */
  public function processOfflineNotification(array $item): void {
    $recipientId = $item['recipient_id'];

    // Re-verificar: quizás se conectó durante el delay
    if ($this->presence->isOnline($recipientId)) return;

    // Verificar que no haya leído el mensaje mientras tanto
    $participant = $this->conversations->getParticipant(
      $item['conversation_id'], $recipientId
    );
    if ($participant->getLastReadMessageId() >= $item['message_id']) return;

    // Disparar notificación via Sistema Multicanal (doc 98)
    $this->notificationService->send([
      'type' => 'message.received',        // Nuevo tipo en catálogo doc 98
      'recipient_id' => $recipientId,
      'data' => [
        'conversation_id' => $item['conversation_id'],
        'sender_name' => $this->getSenderName($item['message_id']),
        'preview' => $this->getDecryptedPreview($item['message_id']),
        'unread_count' => $participant->getUnreadCount(),
      ],
    ]);
  }
}

5.3 MessageAuditService (Hash Chain)
Implementación del audit log inmutable con cadena de hash, reutilizando el patrón exacto del DocumentAuditService del Buzón de Confianza (doc 88 §4.2).
<?php
class MessageAuditService {

  public function log(
    int $conversationId,
    ?int $messageId,
    string $action,
    array $details = [],
  ): MessageAuditLog {
    // Obtener hash del registro anterior (para la cadena)
    $lastLog = $this->repository->findLastForConversation($conversationId);
    $prevHash = $lastLog ? $lastLog->getHashChain() : str_repeat('0', 64);

    $entry = [
      'tenant_id' => $this->getTenantFromConversation($conversationId),
      'conversation_id' => $conversationId,
      'message_id' => $messageId,
      'action' => $action,
      'actor_id' => $this->currentUser->id(),
      'actor_ip' => $this->request->getClientIp(),
      'actor_user_agent' => $this->request->headers->get('User-Agent'),
      'details' => $details,
      'created' => (new DateTime())->format('Y-m-d H:i:s.u'),
    ];

    // Calcular hash de la cadena (incluye hash anterior)
    $dataToHash = $prevHash . json_encode($entry);
    $entry['hash_chain'] = hash('sha256', $dataToHash);

    // INSERT (nunca UPDATE ni DELETE)
    return $this->repository->insert($entry);
  }

  public function verifyIntegrity(int $conversationId): IntegrityReport {
    $logs = $this->repository->findAllForConversation($conversationId);
    $prevHash = str_repeat('0', 64);
    $valid = true;
    $brokenAt = null;

    foreach ($logs as $log) {
      $entry = [/* reconstruct entry fields */];
      $expectedHash = hash('sha256', $prevHash . json_encode($entry));
      if ($expectedHash !== $log->getHashChain()) {
        $valid = false;
        $brokenAt = $log->id();
        break;
      }
      $prevHash = $log->getHashChain();
    }
    return new IntegrityReport($valid, count($logs), $brokenAt);
  }
}
 
6. Servidor WebSocket
6.1 Arquitectura de Transporte en Tiempo Real
El servidor WebSocket gestiona conexiones persistentes para entrega instantánea de mensajes, indicadores de escritura (typing), presencia online y confirmaciones de lectura. Reutiliza la infraestructura WebSocket del Dashboard Profesional (doc 94) extendiéndola con canales de mensajería.
Componente	Responsabilidad	Implementación
MessagingWebSocketServer	Punto de entrada WS, handshake, routing de frames	Ratchet\MessageComponentInterface (dev) / Swoole\WebSocket\Server (prod)
AuthMiddleware	Validación JWT en handshake, extracción user_id y tenant_id	Verifica token en query param ?token=xxx durante upgrade HTTP
ConnectionManager	Pool de conexiones activas, mapeo user_id -> connection[]	Array en memoria + Redis SET para presencia cross-proceso
MessageHandler	Procesamiento de frames entrantes, dispatch a servicios	Switch por type: message|typing|read_receipt|presence
Redis Subscriber	Escucha Redis pub/sub para broadcasts cross-proceso	Suscripción a canales conv:{id} y user:{id}

6.2 Protocolo WebSocket
Mensajes WebSocket usan JSON con estructura estandarizada. El campo 'type' determina el handler:
Cliente -> Servidor
// Enviar mensaje
{
  "type": "message",
  "conversation_id": 42,
  "body": "Texto del mensaje (ya cifrado en cliente si E2E)",
  "message_type": "text",
  "reply_to_id": null,
  "attachment_uuids": [],
  "client_id": "temp_abc123"  // ID temporal para ack
}

// Indicador de escritura
{ "type": "typing", "conversation_id": 42, "is_typing": true }

// Confirmación de lectura
{ "type": "read_receipt", "conversation_id": 42, "up_to_message_id": 1234 }

// Ping (keepalive cada 30s)
{ "type": "ping" }
Servidor -> Cliente
// Mensaje nuevo recibido
{
  "type": "message.new",
  "conversation_id": 42,
  "message": {
    "id": 1235, "uuid": "xxx", "sender_id": 7,
    "body": "texto descifrado", "message_type": "text",
    "created": "2026-02-20T10:30:00.123Z",
    "sender_name": "Elena García",
    "reply_to": null, "attachments": []
  }
}

// Typing indicator
{ "type": "typing", "conversation_id": 42, "user_id": 7, "user_name": "Elena", "is_typing": true }

// Read receipt
{ "type": "read_receipt", "conversation_id": 42, "user_id": 7, "up_to_message_id": 1234 }

// Presencia
{ "type": "presence", "user_id": 7, "status": "online"|"offline"|"away" }

// Ack de mensaje enviado (vincula client_id temporal con server id)
{ "type": "message.ack", "client_id": "temp_abc123", "message_id": 1235, "status": "sent" }

// Pong (respuesta a ping)
{ "type": "pong" }

6.3 PresenceService (Redis)
La presencia online y los indicadores de escritura usan Redis con TTL para auto-limpieza. No se persisten en base de datos (datos efímeros).
<?php
class PresenceService {
  private const ONLINE_TTL = 120;    // 2 min (renovado por pings cada 30s)
  private const TYPING_TTL = 5;      // 5 seg (auto-expire)

  public function setOnline(int $userId): void {
    $this->redis->setex("presence:{$userId}", self::ONLINE_TTL, time());
    $this->redis->sadd("online_users:{$this->tenantId}", $userId);
  }

  public function setOffline(int $userId): void {
    $this->redis->del("presence:{$userId}");
    $this->redis->srem("online_users:{$this->tenantId}", $userId);
  }

  public function isOnline(int $userId): bool {
    return $this->redis->exists("presence:{$userId}");
  }

  public function setTyping(int $userId, int $conversationId): void {
    $this->redis->setex("typing:{$conversationId}:{$userId}", self::TYPING_TTL, 1);
    // Broadcast a otros participantes de la conversación
    $this->redis->publish("conv:{$conversationId}", json_encode([
      'type' => 'typing', 'user_id' => $userId, 'is_typing' => true,
    ]));
  }

  public function broadcastMessage(SecureConversation $conv, SecureMessage $msg, int $senderId): void {
    $this->redis->publish("conv:{$conv->id()}", json_encode([
      'type' => 'message.new',
      'conversation_id' => $conv->id(),
      'message_id' => $msg->id(),
      'sender_id' => $senderId,
    ]));
  }
}
 
7. APIs REST
7.1 Endpoints de Conversaciones
Método	Endpoint	Descripción	Auth	Rate Limit
GET	/api/v1/messaging/conversations	Listar conversaciones del usuario (paginado, filtrable)	Bearer JWT	60/min
POST	/api/v1/messaging/conversations	Crear nueva conversación	Bearer JWT	10/min
GET	/api/v1/messaging/conversations/{uuid}	Detalle de conversación con últimos mensajes	Bearer JWT + participant	60/min
PATCH	/api/v1/messaging/conversations/{uuid}	Actualizar (archivar, silenciar, fijar)	Bearer JWT + participant	30/min
DELETE	/api/v1/messaging/conversations/{uuid}	Cerrar conversación	Bearer JWT + owner	5/min
POST	/api/v1/messaging/conversations/{uuid}/participants	Añadir participante	Bearer JWT + owner/can_invite	10/min
DELETE	/api/v1/messaging/conversations/{uuid}/participants/{uid}	Eliminar participante	Bearer JWT + owner	10/min

7.2 Endpoints de Mensajes
Método	Endpoint	Descripción	Auth	Rate Limit
GET	/api/v1/messaging/conversations/{uuid}/messages	Listar mensajes (cursor pagination, desc)	Bearer JWT + participant	120/min
POST	/api/v1/messaging/conversations/{uuid}/messages	Enviar mensaje	Bearer JWT + can_send	30/min
PATCH	/api/v1/messaging/messages/{uuid}	Editar mensaje (solo propio, <15min)	Bearer JWT + sender	10/min
DELETE	/api/v1/messaging/messages/{uuid}	Eliminar mensaje (soft, solo propio)	Bearer JWT + sender	10/min
POST	/api/v1/messaging/messages/{uuid}/reactions	Añadir reacción	Bearer JWT + participant	60/min
POST	/api/v1/messaging/conversations/{uuid}/read	Marcar como leído hasta message_id	Bearer JWT + participant	120/min
POST	/api/v1/messaging/conversations/{uuid}/attachments	Subir adjunto (proxy a Buzón, doc 88)	Bearer JWT + can_attach	10/min

7.3 Endpoints de Búsqueda y Utilidades
Método	Endpoint	Descripción	Auth	Rate Limit
GET	/api/v1/messaging/search	Búsqueda full-text + semántica en mensajes	Bearer JWT	20/min
GET	/api/v1/messaging/unread-count	Total de mensajes no leídos del usuario	Bearer JWT	120/min
GET	/api/v1/messaging/presence/{uid}	Estado de presencia de un usuario	Bearer JWT	60/min
POST	/api/v1/messaging/export/{conv_uuid}	Exportar conversación (RGPD art. 20)	Bearer JWT + owner	2/hora
GET	/api/v1/messaging/conversations/{uuid}/audit	Audit log de la conversación	Bearer JWT + owner/admin	10/min

7.4 Formato de Respuesta Estándar
// GET /api/v1/messaging/conversations?status=active&page[cursor]=xxx
{
  "data": [
    {
      "uuid": "conv-uuid-1",
      "type": "direct",
      "title": "Conversación con Elena García",
      "context": { "type": "client_case", "id": 42, "label": "EXP-2026-0142" },
      "participants": [
        { "uid": 3, "name": "Dr. López", "role": "owner", "online": true },
        { "uid": 7, "name": "Elena García", "role": "participant", "online": false }
      ],
      "last_message": {
        "preview": "He revisado la escritura y...",
        "sender_name": "Dr. López",
        "created": "2026-02-20T10:30:00.123Z"
      },
      "unread_count": 3,
      "is_pinned": false, "is_muted": false,
      "status": "active",
      "created": "2026-01-15T09:00:00Z"
    }
  ],
  "meta": {
    "cursor": { "next": "xxx", "prev": null },
    "total_unread": 12
  }
}
 
8. Interfaz de Usuario (UI/UX)
8.1 Chat Panel (Componente React)
El chat se implementa como un panel lateral deslizable (slide-in) que no interrumpe la navegación principal, consistente con el patrón del Copilot (doc 93). El usuario puede tener abierto el chat mientras navega por expedientes, reservas o documentos.
Componente React	Responsabilidad	Dimensiones
ChatPanelContainer	Contenedor principal: abre/cierra panel, gestiona estado global	Width: 380px (desktop), 100% (mobile); Height: 100vh
ConversationList	Lista de conversaciones con búsqueda, filtros, badges de no leídos	Scroll virtual para rendimiento con 100+ conversaciones
ConversationHeader	Título, participantes, acciones (archivar, silenciar, buscar)	Height: 56px fijo
MessageThread	Lista de mensajes con scroll infinito (cursor pagination hacia arriba)	Scroll virtual, carga 50 mensajes iniciales, 25 por página
MessageBubble	Mensaje individual: body, hora, estado, adjuntos, reacciones, reply	Max-width: 70% del panel; colores: verde propio, gris ajeno
MessageComposer	Input de mensaje: textarea auto-grow, botón adjuntar, emojis, enviar	Height: 48-120px (auto-grow); max 5 líneas visibles
TypingIndicator	Animación 'Elena está escribiendo...' con 3 dots pulsantes	Height: 24px; auto-hide después de 5s sin typing event
AttachmentPreview	Preview de archivos adjuntos antes de enviar (imagen, PDF, doc)	Thumbnail 48x48px con nombre y tamaño
SearchOverlay	Búsqueda dentro de conversación con highlight de resultados	Overlay sobre MessageThread; resultados navegables con flechas

8.2 Integración en Portal Cliente (doc 90)
El Portal Cliente Documental (doc 90) incorpora una nueva tab 'Mensajes' junto a 'Documentos' y 'Expediente'. El cliente accede sin contraseña via token único (mismo patrón de acceso del Portal), y ve solo las conversaciones vinculadas a su expediente.
Contexto de Integración	Ubicación UI	Trigger
Portal Cliente (doc 90)	Nueva tab 'Mensajes' en el portal del expediente	Cliente abre portal via token único
Dashboard Profesional (doc 94)	Widget 'Mensajes no leídos' con badge + lista rápida	Profesional entra al dashboard; WebSocket actualiza en real-time
Detalle Expediente (doc 90)	Botón 'Enviar mensaje al cliente' en cada expediente	Click abre ChatPanel con conversación vinculada al case
Detalle Reserva (doc 85)	Botón 'Mensaje al profesional' en confirmación de cita	Click abre/crea conversación vinculada al booking
Copilot Panel (doc 93)	Sugerencia 'Enviar esto al cliente como mensaje' tras redacción IA	Copilot genera texto → botón de acción envía como mensaje en chat
Mobile PWA (doc 109)	Pantalla dedicada de mensajería en nav inferior	Tab de mensajes en la barra de navegación principal

8.3 Notificaciones Visuales
Evento	Indicación Visual	Indicación Sonora
Mensaje nuevo (app abierta)	Badge rojo en icono chat + toast notification con preview	Sonido sutil 'ding' (configurable, silenciable)
Mensaje nuevo (app cerrada)	Push notification nativa (Firebase FCM, doc 98)	Sonido del sistema operativo
Typing indicator	Animación '...' pulsante bajo último mensaje	Ninguna
Read receipt	Double check azul junto al mensaje (estilo WhatsApp)	Ninguna
Participante online	Círculo verde junto a avatar en lista de conversaciones	Ninguna
Error de envío	Icono de exclamación rojo + botón 'Reintentar'	Ninguna
 
9. Flujos de Automatización (ECA)
Nuevos eventos, condiciones y acciones ECA que se integran en el catálogo del ecosistema (doc 06). Se registran como plugins ECA del módulo jaraba_messaging.
9.1 Catálogo de Flujos
ID	Nombre	Trigger	Condiciones	Acciones
ECA-MSG-001	Notificación offline	message.sent	Destinatario no online (Redis check)	Esperar 30s + verificar + disparar notificación multicanal (doc 98)
ECA-MSG-002	Auto-respuesta fuera de horario	message.sent	conversation_type=direct AND hora fuera del horario del profesional AND is_first_unanswered	Enviar mensaje de sistema: 'Fuera de horario. Responderé a las {next_available}'
ECA-MSG-003	Recordatorio mensajes no leídos	cron (cada 4h)	Participante con unread_count > 0 AND last_notification > 4h	Email digest con resumen de mensajes no leídos
ECA-MSG-004	Auto-cierre por inactividad	cron (diario)	Conversación con last_message_at > auto_close_days	Cambiar status a 'closed' + notificar participantes
ECA-MSG-005	Primer mensaje de cliente	message.sent	sender es cliente AND es primer mensaje en conversación	Notificar profesional con prioridad alta + incrementar contador 'consultas entrantes' en dashboard
ECA-MSG-006	Mensaje con adjunto legal	message.sent	attachment_count > 0 AND context_type = client_case	Vincular documentos al expediente automáticamente via Portal Cliente (doc 90)
ECA-MSG-007	Alerta SLA de respuesta	cron (cada 30min)	Mensaje de cliente sin respuesta AND elapsed > SLA_threshold (configurable por tenant)	Alerta al profesional + registro en métricas de tiempo de respuesta
ECA-MSG-008	Integración Copilot	message.sent	sender es cliente AND conversation has AI assistant enabled	Encolar análisis de sentimiento + sugerir respuesta al profesional via Copilot (doc 93)

9.2 Ejemplo YAML: ECA-MSG-001
# config/eca/eca.model.message_offline_notification.yml
id: message_offline_notification
label: 'Notificación de mensaje a usuario offline'
status: true
version: '1.0'
events:
  - plugin: 'jaraba_messaging:message_sent'
    label: 'Mensaje enviado en conversación'
conditions:
  - plugin: 'jaraba_messaging:recipient_not_online'
    settings:
      check_method: 'redis_presence'
  - plugin: 'jaraba_messaging:notification_not_muted'
actions:
  - plugin: 'eca_queue:delayed_action'
    settings:
      delay_seconds: 30
      action: 'jaraba_notifications:send'
      notification_type: 'message.received'
      channels: '[recipient:preferred_channels]'
      template_data:
        sender_name: '[message:sender:display_name]'
        preview: '[message:body_preview]'
        conversation_url: '[conversation:url]'
 
10. Integración con IA
10.1 RAG sobre Mensajes (Copilot, doc 93)
Los mensajes se indexan en Qdrant para que el Copilot de Servicios (doc 93) pueda buscar en el historial de conversaciones y contextualizar sus respuestas. Cuando un profesional pregunta al Copilot 'Qué me dijo el cliente García sobre la escritura?', el sistema busca en los mensajes de las conversaciones vinculadas a ese cliente.
Componente	Función	Implementación
MessageIndexWorker	Indexa mensajes nuevos en Qdrant (asíncrono via Queue)	Descifra body → genera embedding (Gemini/OpenAI) → upsert en Qdrant con metadata
Qdrant Collection	messaging_{tenant_id}: colección por tenant para aislamiento	Vector size: 768 (Gemini) o 1536 (OpenAI); payload: conversation_id, sender_id, created, context
SearchService	Búsqueda semántica + full-text combinada	Qdrant similarity search + MySQL FULLTEXT; merge y re-rank por relevancia
Copilot Context	Inyecta mensajes relevantes en el system prompt del Copilot	Top-5 mensajes similares al query del profesional, con strict grounding

10.2 AI Skills para Mensajería (doc 129)
Nuevos task types para el AI Skills System que permiten redacción asistida de mensajes profesionales:
Skill	Task Type	Input	Output	Ejemplo
client_communication	chat_reply	Historial conversación + datos expediente	Borrador de respuesta profesional	"Redacta respuesta al cliente sobre el estado de su escritura"
client_communication	chat_summary	Todos los mensajes de una conversación	Resumen estructurado	"Resume mi conversación con García de la última semana"
client_communication	sentiment_analysis	Últimos N mensajes del cliente	Score de sentimiento + alertas	Detectar frustración del cliente para intervención proactiva
appointment_prep	pre_meeting_brief	Mensajes + documentos + datos del caso	Briefing para reunión	"Prepárame un resumen del caso Martínez para la reunión de mañana"

10.3 Filtrado de Seguridad para IA
Cuando is_confidential = true en una conversación, los mensajes NO se indexan en Qdrant y NO están disponibles para el Copilot. Esto respeta el secreto profesional para conversaciones marcadas como sensibles. El profesional controla este flag por conversación.
is_confidential	Indexación Qdrant	Acceso Copilot	Búsqueda	Audit Log
false (default)	Sí (asíncrona)	Sí (RAG contextual)	Full-text + semántica	Completo
true	NO	NO (excluido explícitamente)	Solo full-text básica	Completo (siempre)
 
11. Estrategia de Testing
Tipo	Cobertura	Herramienta	Criterio de Aceptación
Unit Tests	MessageEncryptionService, MessageAuditService, PresenceService	PHPUnit	100% cobertura en lógica criptográfica y audit
Kernel Tests	MessagingService, ConversationService (con BD real)	Drupal KernelTestBase	>85% cobertura; transaccionalidad verificada
Functional Tests	API REST completa: CRUD, permisos, rate limiting	Drupal BrowserTestBase + PHPUnit	Todos los endpoints; 401/403 en accesos no autorizados
WebSocket Tests	Handshake, auth, envío/recepción, typing, reconnect	Ratchet test client + PHPUnit	Latencia <100ms; reconexion automática en <5s
Integration Tests	Flujo completo: enviar → cifrar → persistir → WS → notificar	Cypress + WebSocket mock	Flujo E2E sin errores en 3 escenarios principales
Security Tests	Cifrado, audit chain integrity, XSS, rate limiting, RBAC	Custom + OWASP ZAP	0 vulnerabilidades críticas; hash chain verificable
Performance Tests	100 conexiones WS concurrentes, 1000 msg/min	k6 + WebSocket plugin	Latencia p95 <200ms; 0% message loss
Accessibility	Chat panel WCAG 2.1 AA (teclado, screen reader, contraste)	axe DevTools + WAVE	0 errores críticos nivel A/AA
 
12. Roadmap de Implementación
12.1 Plan de Sprints
Sprint	Semanas	Horas	Entregables	Dependencias
Sprint 1: Foundation	Sem 1–2	30–35h	Entidades BD (3.1–3.5) + MessageEncryptionService + MessageAuditService + migrations + unit tests	doc 88 (Buzón) desplegado; Redis 7 operativo
Sprint 2: Core API	Sem 3–4	28–32h	MessagingService + ConversationService + REST API completa (sec 7) + NotificationBridge + functional tests	Sprint 1 completado; doc 98 (notificaciones) operativo
Sprint 3: Real-Time	Sem 5–6	25–30h	WebSocket Server (sec 6) + PresenceService + ConnectionManager + Redis pub/sub + protocolo WS completo	Sprint 2 completado
Sprint 4: Frontend	Sem 7–8	30–35h	Chat Panel React (sec 8) + ConversationList + MessageThread + MessageComposer + TypingIndicator + responsive	Sprint 3 completado; doc 94 dashboard desplegado
Sprint 5: Integration	Sem 9–10	22–28h	Integración Portal Cliente (doc 90) + Dashboard widget + AttachmentBridge (doc 88) + ECA flows (sec 9)	Sprint 4 completado; docs 90, 94 desplegados
Sprint 6: AI + QA	Sem 11–12	25–30h	Qdrant indexing + Copilot RAG (sec 10) + AI Skills + security audit + performance tests + RGPD compliance + go-live	Sprint 5 completado; doc 93 Copilot operativo

12.2 Resumen de Estimación
Concepto	Horas Mínimas	Horas Máximas	Coste (à 80€/h)
Sprint 1: Foundation	30h	35h	2.400€ – 2.800€
Sprint 2: Core API	28h	32h	2.240€ – 2.560€
Sprint 3: Real-Time	25h	30h	2.000€ – 2.400€
Sprint 4: Frontend	30h	35h	2.400€ – 2.800€
Sprint 5: Integration	22h	28h	1.760€ – 2.240€
Sprint 6: AI + QA	25h	30h	2.000€ – 2.400€
TOTAL	160h	190h	12.800€ – 15.200€
Nota: La estimación incluye testing completo y documentación de código. No incluye: despliegue en producción (cubierto por doc 131/139), ni configuración de tenants individuales. El ahorro por reutilización de ~100h ya está descontado de estas cifras.

12.3 Criterios de Aceptación Globales
•	Funcional: Profesional y cliente pueden mantener conversación bidireccional cifrada en tiempo real
•	Rendimiento: Latencia end-to-end <100ms online; notificación offline <60s; soporta 100 conexiones WS simultáneas por tenant
•	Seguridad: Cifrado AES-256-GCM verificable; audit log con hash chain íntegro; 0 vulnerabilidades críticas en security audit
•	RGPD: Exportación de datos funcional (art. 20); derecho al olvido implementado; retención configurable por tenant
•	Integración: Adjuntos enrutados a Buzón de Confianza (doc 88); notificaciones via Sistema Multicanal (doc 98); Copilot RAG operativo
•	Accesibilidad: Chat panel WCAG 2.1 AA; navegable por teclado; compatible con screen readers
•	Cross-vertical: Funcional en ServiciosConecta, Empleabilidad, Emprendimiento, AgroConecta y ComercioConecta

12.4 Dependencias Críticas
Dependencia	Documento	Estado Requerido	Impacto si No Disponible
Buzón de Confianza	doc 88	Desplegado: cifrado AES-256-GCM funcional	BLOQUEANTE: sin cifrado ni adjuntos seguros
Notificaciones Multicanal	doc 98	Desplegado: al menos email + push operativos	DEGRADADO: sin notificaciones offline (solo WS)
Redis 7	doc 131	Operativo con pub/sub habilitado	BLOQUEANTE: sin real-time ni presencia
Dashboard Profesional	doc 94	Desplegado con WebSocket funcional	PARCIAL: se puede desplegar WS independiente
Portal Cliente	doc 90	Desplegado con framework de tabs	PARCIAL: chat funciona standalone sin tab en portal
Copilot Servicios	doc 93	Desplegado con RAG pipeline	NO BLOQUEANTE: chat funciona sin IA; se integra cuando esté listo
Migración IONOS	doc 131	AMD EPYC con root access + MariaDB >5GB	PARCIAL: funciona en servidor actual con límites de conexiones

——— Fin del Documento ———
178_Platform_Secure_Messaging_v1.docx | Jaraba Impact Platform | Febrero 2026
