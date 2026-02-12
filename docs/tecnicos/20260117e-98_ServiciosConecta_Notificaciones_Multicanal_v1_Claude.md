SISTEMA DE NOTIFICACIONES
Comunicación Multicanal Inteligente
Email + SMS + WhatsApp + Push + In-App
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	98_ServiciosConecta_Notificaciones_Multicanal
Dependencias:	Todos los módulos anteriores (82-97)
Integraciones:	SendGrid, Twilio, WhatsApp Business API, Firebase FCM
Prioridad:	CRÍTICA - Comunicación es el core del servicio
 
1. Resumen Ejecutivo
El Sistema de Notificaciones Multicanal centraliza toda la comunicación saliente de la plataforma hacia clientes y profesionales. Gestiona el envío de mensajes por múltiples canales (email, SMS, WhatsApp, push, in-app), respetando las preferencias del usuario, aplicando reglas anti-spam, y proporcionando trazabilidad completa de cada mensaje enviado.
El sistema utiliza plantillas configurables por tipo de evento, soporta personalización con datos del contexto, y permite fallback entre canales cuando uno falla o no está disponible. Todos los módulos de la plataforma utilizan este sistema centralizado en lugar de enviar notificaciones directamente.
1.1 Canales de Comunicación
Canal	Proveedor	Uso Principal	Coste
📧 Email	SendGrid / Amazon SES	Comunicación formal, documentos	~0.001€/email
📱 SMS	Twilio	Urgente, recordatorios citas	~0.07€/SMS
💬 WhatsApp	WhatsApp Business API (Twilio)	Conversacional, documentos	~0.05€/msg
🔔 Push	Firebase Cloud Messaging	Alertas tiempo real, mobile	Gratis
🖥️ In-App	WebSocket nativo	Dashboard, tiempo real	Gratis

1.2 Categorías de Notificaciones
Categoría	Ejemplos	Canales por Defecto
Transaccional	Confirmación cita, factura enviada, documento firmado	Email + Push
Recordatorio	Cita mañana, factura pendiente, documentos por subir	Email + SMS/WhatsApp
Urgente	Plazo crítico, cita en 1h, documento urgente	SMS + Push + Email
Informativa	Caso actualizado, nuevo mensaje, reseña recibida	Email + In-App
Marketing	Newsletter, promociones, novedades (opt-in)	Email

 
2. Arquitectura del Sistema
2.1 Diagrama de Flujo
┌─────────────────────────────────────────────────────────────────────────┐
│                 SISTEMA DE NOTIFICACIONES                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌───────────┐   ┌───────────┐   ┌───────────┐   ┌───────────┐         │
│  │  Booking  │   │  Invoice  │   │  Review   │   │  Case     │   ...   │
│  │  Module   │   │  Module   │   │  Module   │   │  Module   │         │
│  └─────┬─────┘   └─────┬─────┘   └─────┬─────┘   └─────┬─────┘         │
│        │               │               │               │               │
│        └───────────────┴───────────────┴───────────────┘               │
│                                │                                       │
│                                ▼                                       │
│                   ┌─────────────────────────┐                          │
│                   │   NotificationService   │                          │
│                   │  - Template rendering   │                          │
│                   │  - Channel selection    │                          │
│                   │  - Preference check     │                          │
│                   │  - Rate limiting        │                          │
│                   └───────────┬─────────────┘                          │
│                               │                                        │
│        ┌──────────┬───────────┼───────────┬──────────┐                 │
│        │          │           │           │          │                 │
│        ▼          ▼           ▼           ▼          ▼                 │
│   ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐               │
│   │ Email  │ │  SMS   │ │WhatsApp│ │  Push  │ │ In-App │               │
│   │Provider│ │Provider│ │Provider│ │Provider│ │Provider│               │
│   └───┬────┘ └───┬────┘ └───┬────┘ └───┬────┘ └───┬────┘               │
│       │         │         │         │         │                        │
│       ▼         ▼         ▼         ▼         ▼                        │
│   SendGrid   Twilio    Twilio    Firebase   WebSocket                  │
└─────────────────────────────────────────────────────────────────────────┘
 
3. Modelo de Datos
3.1 Entidad: notification (Notificación)
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
recipient_type	VARCHAR(16)	Tipo de destinatario	client|provider|admin
recipient_id	INT	ID del destinatario	NOT NULL, INDEX
recipient_email	VARCHAR(255)	Email destino	NOT NULL
recipient_phone	VARCHAR(20)	Teléfono destino	NULLABLE
notification_type	VARCHAR(64)	Tipo de notificación	booking.confirmation, invoice.sent...
category	VARCHAR(32)	Categoría	transactional|reminder|urgent|info|marketing
channel	VARCHAR(16)	Canal utilizado	email|sms|whatsapp|push|inapp
subject	VARCHAR(255)	Asunto (email)	NULLABLE
body_text	TEXT	Cuerpo texto plano	NOT NULL
body_html	TEXT	Cuerpo HTML (email)	NULLABLE
context_type	VARCHAR(32)	Entidad relacionada	case|booking|invoice|quote|review
context_id	INT	ID de la entidad	NULLABLE
status	VARCHAR(16)	Estado de envío	queued|sent|delivered|failed|bounced
external_id	VARCHAR(128)	ID del proveedor externo	SendGrid ID, Twilio SID...
sent_at	DATETIME	Cuándo se envió	NULLABLE
delivered_at	DATETIME	Cuándo se entregó	NULLABLE
opened_at	DATETIME	Cuándo se abrió (email)	NULLABLE
clicked_at	DATETIME	Cuándo se hizo clic	NULLABLE
error_message	TEXT	Mensaje de error si falló	NULLABLE
created	DATETIME	Fecha creación	NOT NULL

 
3.2 Entidad: notification_preference (Preferencias)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, UNIQUE
email_enabled	BOOLEAN	Email activo	DEFAULT TRUE
sms_enabled	BOOLEAN	SMS activo	DEFAULT TRUE
whatsapp_enabled	BOOLEAN	WhatsApp activo	DEFAULT FALSE
push_enabled	BOOLEAN	Push activo	DEFAULT TRUE
marketing_enabled	BOOLEAN	Marketing opt-in	DEFAULT FALSE
quiet_hours_start	TIME	Inicio horas de silencio	NULLABLE (22:00)
quiet_hours_end	TIME	Fin horas de silencio	NULLABLE (08:00)
preferred_channel	VARCHAR(16)	Canal preferido	email|sms|whatsapp
category_settings	JSON	Configuración por categoría	{transactional: {email: true, sms: false}...}
updated	DATETIME	Última actualización	NOT NULL

3.3 Entidad: notification_template (Plantillas)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant (o NULL = global)	FK tenant.id, NULLABLE
notification_type	VARCHAR(64)	Tipo de notificación	UNIQUE per tenant + type + channel
channel	VARCHAR(16)	Canal	email|sms|whatsapp|push
language	VARCHAR(5)	Idioma	DEFAULT 'es'
subject_template	VARCHAR(255)	Plantilla asunto	Con variables {{client_name}}
body_template	TEXT	Plantilla cuerpo	Twig syntax
html_template	TEXT	Plantilla HTML (email)	NULLABLE
is_active	BOOLEAN	Activa	DEFAULT TRUE
variables	JSON	Variables disponibles	[{name, type, required}]
updated	DATETIME	Última modificación	NOT NULL

 
4. Servicios Principales
4.1 NotificationService
<?php namespace Drupal\jaraba_notifications\Service;

class NotificationService {
  
  public function send(
    string $notificationType,
    User $recipient,
    array $context = [],
    ?array $channelOverride = null
  ): array {
    $results = [];
    
    // 1. Obtener preferencias del usuario
    $preferences = $this->preferenceService->getForUser($recipient->id());
    
    // 2. Determinar canales a usar
    $channels = $channelOverride ?? $this->resolveChannels(
      $notificationType,
      $preferences
    );
    
    // 3. Verificar quiet hours
    if ($this->isInQuietHours($preferences) && !$this->isUrgent($notificationType)) {
      $channels = ['email']; // Solo email fuera de horario
    }
    
    // 4. Rate limiting
    if (!$this->rateLimiter->canSend($recipient->id(), $notificationType)) {
      throw new RateLimitExceededException();
    }
    
    // 5. Enviar por cada canal
    foreach ($channels as $channel) {
      if (!$this->isChannelEnabled($channel, $preferences)) {
        continue;
      }
      
      try {
        $notification = $this->createNotification(
          $notificationType,
          $recipient,
          $channel,
          $context
        );
        
        $this->channelProviders[$channel]->send($notification);
        $results[$channel] = 'sent';
        
      } catch (\Exception $e) {
        $results[$channel] = 'failed: ' . $e->getMessage();
        $this->logger->error("Notification failed: {$e->getMessage()}");
      }
    }
    
    return $results;
  }
  
  private function createNotification(
    string $type,
    User $recipient,
    string $channel,
    array $context
  ): Notification {
    // Obtener plantilla
    $template = $this->templateService->getTemplate($type, $channel);
    
    // Renderizar contenido
    $rendered = $this->templateService->render($template, $context);
    
    return Notification::create([
      'tenant_id' => $context['tenant_id'],
      'recipient_type' => $this->getRecipientType($recipient),
      'recipient_id' => $recipient->id(),
      'recipient_email' => $recipient->getEmail(),
      'recipient_phone' => $recipient->getPhone(),
      'notification_type' => $type,
      'category' => $template->getCategory(),
      'channel' => $channel,
      'subject' => $rendered['subject'],
      'body_text' => $rendered['body_text'],
      'body_html' => $rendered['body_html'] ?? null,
      'context_type' => $context['context_type'] ?? null,
      'context_id' => $context['context_id'] ?? null,
      'status' => 'queued',
    ]);
  }
}

 
5. Catálogo de Notificaciones
Tipo	Descripción	Canales Default
booking.confirmation	Confirmación de cita reservada	email, sms, push
booking.reminder_24h	Recordatorio 24h antes	email, sms
booking.reminder_1h	Recordatorio 1h antes	sms, push
booking.cancelled	Cita cancelada	email, sms
invoice.sent	Factura enviada con enlace pago	email
invoice.reminder	Recordatorio factura pendiente	email, sms
invoice.paid	Confirmación de pago recibido	email
document.request	Solicitud de documentos	email, whatsapp
document.uploaded	Cliente subió documento	push, inapp (provider)
document.ready	Documento listo para descargar	email, push
document.signed	Documento firmado	email
case.opened	Expediente abierto	email
case.update	Actualización en expediente	email, inapp
case.closed	Expediente cerrado	email
review.request	Solicitud de valoración	email, whatsapp
review.received	Nueva reseña recibida	push, inapp (provider)
quote.sent	Presupuesto enviado	email
quote.accepted	Presupuesto aceptado	email, push (provider)

 
6. APIs REST
Método	Endpoint	Descripción	Auth
GET	/api/v1/notifications	Listar notificaciones enviadas	Provider
GET	/api/v1/notifications/inbox	Bandeja de entrada (in-app)	User
POST	/api/v1/notifications/{id}/read	Marcar como leída	User
GET	/api/v1/notifications/preferences	Obtener preferencias del usuario	User
PUT	/api/v1/notifications/preferences	Actualizar preferencias	User
GET	/api/v1/notifications/templates	Listar plantillas (admin)	Admin
PUT	/api/v1/notifications/templates/{id}	Editar plantilla	Admin
POST	/api/v1/webhooks/sendgrid	Webhook SendGrid (eventos)	Webhook
POST	/api/v1/webhooks/twilio	Webhook Twilio (SMS/WhatsApp)	Webhook

7. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 14.1	Semana 45	Modelo datos + NotificationService + TemplateService	Todos los módulos
Sprint 14.2	Semana 46	EmailProvider (SendGrid) + SMSProvider (Twilio)	Sprint 14.1
Sprint 14.3	Semana 47	WhatsApp + Push (Firebase) + InApp (WebSocket)	Sprint 14.2
Sprint 14.4	Semana 48	Preferencias usuario + webhooks + plantillas default + tests	Sprint 14.3

7.1 Criterios de Aceptación
•	✓ Emails se envían correctamente via SendGrid con tracking
•	✓ SMS funcionan para recordatorios de citas
•	✓ WhatsApp Business API integrado para mensajes transaccionales
•	✓ Push notifications funcionan en móvil (iOS/Android)
•	✓ Preferencias del usuario se respetan en todos los envíos
•	✓ Quiet hours funcionan (no SMS/Push nocturno)
•	✓ Rate limiting previene spam

--- Fin del Documento ---
