SISTEMA DE NOTIFICACIONES
Email, Push, SMS, WhatsApp - Comunicación Multicanal
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	76_ComercioConecta_Notifications_System
Dependencias:	75_Customer_Portal, 74_Merchant_Portal, 67_Order_System
Base:	Nuevo (específico ComercioConecta)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Notificaciones para ComercioConecta. El sistema proporciona comunicación multicanal unificada (Email, Push, SMS, WhatsApp) tanto para clientes como para comerciantes, con gestión centralizada de plantillas, preferencias y automatizaciones.
1.1 Objetivos del Sistema
• Unificar todas las comunicaciones en un sistema centralizado
• Soportar múltiples canales: Email, Push, SMS, WhatsApp
• Respetar preferencias de comunicación del usuario
• Proporcionar templates multiidioma personalizables
• Garantizar entregabilidad y cumplimiento legal
• Ofrecer analytics de engagement por canal
1.2 Canales de Comunicación
Canal	Proveedor	Uso Principal	Coste Estimado
Email	Amazon SES / Resend	Transaccional + Marketing	~0.10€/1000 emails
Push Web	Firebase Cloud Messaging	Alertas tiempo real	Gratis
Push App	Firebase Cloud Messaging	Alertas tiempo real	Gratis
SMS	Twilio / MessageBird	C&C, verificación, urgentes	~0.07€/SMS
WhatsApp	WhatsApp Business API	Soporte, tracking, promos	~0.05-0.15€/mensaje
1.3 Tipos de Notificaciones
Tipo	Descripción	Canales	Opt-out
Transaccional	Confirmaciones, tracking, facturas	Email, Push, SMS	No (requerido)
Servicio	Alertas de cuenta, seguridad	Email, Push, SMS	No
Promocional	Ofertas, descuentos, novedades	Email, Push, WhatsApp	Sí (opt-in)
Personalizada	Bajada precio, back in stock	Email, Push	Sí
Reminder	Carrito abandonado, reseñas	Email, Push	Sí
Merchant Alert	Nuevos pedidos, stock, reseñas	Email, Push, SMS	Parcial
 
2. Arquitectura del Sistema
2.1 Diagrama de Arquitectura
┌─────────────────────────────────────────────────────────────────────┐ │                    NOTIFICATION SYSTEM                              │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌─────────────────┐    ┌─────────────────┐    ┌───────────────┐   │ │  │    TRIGGERS     │    │   DISPATCHER    │    │   CHANNELS    │   │ │  │                 │    │                 │    │               │   │ │  │ • Order events  │───►│ • Route by type │───►│ • Email       │   │ │  │ • User events   │    │ • Check prefs   │    │ • Push Web    │   │ │  │ • Stock events  │    │ • Load template │    │ • Push App    │   │ │  │ • Review events │    │ • Render        │    │ • SMS         │   │ │  │ • Cron jobs     │    │ • Queue         │    │ • WhatsApp    │   │ │  │ • Manual        │    │ • Send          │    │               │   │ │  └─────────────────┘    └─────────────────┘    └───────────────┘   │ │           │                      │                      │          │ │           │                      │                      │          │ │           ▼                      ▼                      ▼          │ │  ┌─────────────────┐    ┌─────────────────┐    ┌───────────────┐   │ │  │    TEMPLATES    │    │      QUEUE      │    │   PROVIDERS   │   │ │  │                 │    │                 │    │               │   │ │  │ • Twig based    │    │ • Redis/DB      │    │ • Amazon SES  │   │ │  │ • Multi-lang    │    │ • Priority      │    │ • Firebase    │   │ │  │ • Variables     │    │ • Retry logic   │    │ • Twilio      │   │ │  │ • Previews      │    │ • Rate limiting │    │ • WhatsApp    │   │ │  └─────────────────┘    └─────────────────┘    └───────────────┘   │ │                                                                     │ │  ┌─────────────────────────────────────────────────────────────┐   │ │  │                      ANALYTICS & LOGS                        │   │ │  │  • Delivery rates  • Open rates  • Click rates  • Bounces   │   │ │  └─────────────────────────────────────────────────────────────┘   │ │                                                                     │ └─────────────────────────────────────────────────────────────────────┘
2.2 Flujo de Envío
1. TRIGGER (Evento)    │    ▼ 2. NOTIFICATION SERVICE    ├── Identificar tipo de notificación    ├── Cargar recipient(s)    ├── Verificar preferencias (opt-in/out)    └── Si no permitido → LOG y terminar    │    ▼ 3. TEMPLATE SERVICE    ├── Cargar plantilla por tipo + idioma    ├── Inyectar variables de contexto    └── Renderizar contenido por canal    │    ▼ 4. DISPATCHER    ├── Para cada canal habilitado:    │   ├── Crear mensaje específico del canal    │   └── Encolar con prioridad    │    ▼ 5. QUEUE PROCESSOR    ├── Procesar por prioridad (high → low)    ├── Rate limiting por canal    ├── Enviar al provider    └── Manejar reintentos si falla    │    ▼ 6. DELIVERY TRACKING    ├── Registrar estado: sent/delivered/failed    ├── Procesar webhooks (opens, clicks, bounces)    └── Actualizar analytics
 
3. Entidades del Sistema
3.1 Entidad: notification_template
Plantillas de notificación multicanal y multiidioma.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
key	VARCHAR(64)	Clave única	UNIQUE, NOT NULL, ej: 'order_confirmed'
name	VARCHAR(128)	Nombre descriptivo	NOT NULL
description	TEXT	Descripción de uso	NULLABLE
category	VARCHAR(32)	Categoría	ENUM: order|shipping|account|promo|review|alert
channels	JSON	Canales habilitados	Array: ['email','push','sms']
is_transactional	BOOLEAN	Es transaccional	DEFAULT FALSE
is_active	BOOLEAN	Activa	DEFAULT TRUE
variables	JSON	Variables disponibles	Schema de variables
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última modificación	NOT NULL
3.2 Entidad: notification_template_content
Contenido de plantillas por canal e idioma.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
template_id	INT	Plantilla padre	FK notification_template.id, NOT NULL
channel	VARCHAR(16)	Canal	ENUM: email|push_web|push_app|sms|whatsapp
language	VARCHAR(5)	Idioma	DEFAULT 'es'
subject	VARCHAR(255)	Asunto (email)	NULLABLE
title	VARCHAR(128)	Título (push)	NULLABLE
body	TEXT	Cuerpo del mensaje	NOT NULL
html_body	TEXT	Cuerpo HTML (email)	NULLABLE
action_url	VARCHAR(512)	URL de acción	NULLABLE
action_label	VARCHAR(64)	Texto del botón	NULLABLE
image_url	VARCHAR(512)	Imagen (push rich)	NULLABLE
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última modificación	NOT NULL
UNIQUE: (template_id, channel, language)
 
3.3 Entidad: notification_log
Registro de todas las notificaciones enviadas.
Campo	Tipo	Descripción	Restricciones
id	BigSerial	ID interno	PRIMARY KEY
template_key	VARCHAR(64)	Clave de plantilla	NOT NULL, INDEX
channel	VARCHAR(16)	Canal utilizado	NOT NULL
recipient_type	VARCHAR(16)	Tipo destinatario	ENUM: customer|merchant|admin
recipient_id	INT	ID del destinatario	NOT NULL, INDEX
recipient_address	VARCHAR(255)	Email/Phone/Token	NOT NULL
subject	VARCHAR(255)	Asunto enviado	NULLABLE
body_preview	VARCHAR(500)	Preview del cuerpo	NULLABLE
context	JSON	Variables usadas	NULLABLE
status	VARCHAR(16)	Estado	ENUM: queued|sent|delivered|failed|bounced
provider	VARCHAR(32)	Proveedor usado	ej: 'ses', 'firebase', 'twilio'
provider_id	VARCHAR(128)	ID del proveedor	NULLABLE
error_message	TEXT	Error si falló	NULLABLE
retry_count	TINYINT	Intentos	DEFAULT 0
opened_at	DATETIME	Fecha apertura	NULLABLE
clicked_at	DATETIME	Fecha clic	NULLABLE
created	DATETIME	Fecha envío	NOT NULL, INDEX
3.4 Entidad: push_subscription
Suscripciones a push notifications.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, NOT NULL, INDEX
user_type	VARCHAR(16)	Tipo	ENUM: customer|merchant
platform	VARCHAR(16)	Plataforma	ENUM: web|ios|android
device_token	VARCHAR(512)	Token FCM/APNs	NOT NULL
device_name	VARCHAR(128)	Nombre dispositivo	NULLABLE
browser	VARCHAR(64)	Navegador (si web)	NULLABLE
endpoint	TEXT	Endpoint (web push)	NULLABLE
auth_key	VARCHAR(128)	Auth key (web push)	NULLABLE
p256dh_key	VARCHAR(128)	P256DH key (web push)	NULLABLE
is_active	BOOLEAN	Activa	DEFAULT TRUE
last_used	DATETIME	Último uso	NULLABLE
created	DATETIME	Fecha registro	NOT NULL
UNIQUE: (user_id, device_token)
 
4. Servicios Principales
4.1 NotificationService
<?php namespace Drupal\jaraba_notifications\Service;  class NotificationService {    // Envío principal   public function send(string $templateKey, Recipient $recipient, array $context = []): void;   public function sendToMany(string $templateKey, array $recipients, array $context = []): int;   public function sendNow(string $templateKey, Recipient $recipient, array $context = []): bool;      // Canales específicos   public function sendEmail(string $templateKey, string $email, array $context = []): bool;   public function sendPush(string $templateKey, int $userId, array $context = []): bool;   public function sendSMS(string $templateKey, string $phone, array $context = []): bool;   public function sendWhatsApp(string $templateKey, string $phone, array $context = []): bool;      // Preferencias   public function canSend(string $templateKey, int $userId, string $channel): bool;   public function getPreferredChannels(string $templateKey, int $userId): array;      // Scheduling   public function schedule(string $templateKey, Recipient $recipient, array $context, \DateTime $at): int;   public function cancelScheduled(int $scheduleId): bool;      // Bulk   public function sendCampaign(Campaign $campaign): CampaignResult;   public function sendToSegment(string $templateKey, Segment $segment, array $context = []): int; }
4.2 TemplateService
<?php namespace Drupal\jaraba_notifications\Service;  class TemplateService {    // CRUD   public function getTemplate(string $key): ?NotificationTemplate;   public function createTemplate(array $data): NotificationTemplate;   public function updateTemplate(string $key, array $data): NotificationTemplate;   public function deleteTemplate(string $key): bool;   public function listTemplates(array $filters = []): array;      // Contenido   public function getContent(string $key, string $channel, string $lang = 'es'): ?TemplateContent;   public function setContent(string $key, string $channel, string $lang, array $content): void;      // Renderizado   public function render(string $key, string $channel, array $context, string $lang = 'es'): RenderedMessage;   public function preview(string $key, string $channel, array $sampleContext): RenderedMessage;      // Variables   public function getAvailableVariables(string $key): array;   public function validateContext(string $key, array $context): ValidationResult;      // Import/Export   public function exportTemplates(): string;   public function importTemplates(string $json): int; }
 
4.3 EmailService
<?php namespace Drupal\jaraba_notifications\Service;  class EmailService implements ChannelInterface {    // Envío   public function send(RenderedMessage $message, string $to): DeliveryResult;   public function sendBatch(array $messages): array;      // Configuración   public function setProvider(string $provider): void;  // 'ses', 'resend', 'smtp'   public function getProvider(): EmailProviderInterface;      // Templates   public function renderHtml(string $template, array $context): string;   public function inlineStyles(string $html): string;      // Attachments   public function attachFile(string $path, string $filename): void;   public function attachInvoice(RetailOrder $order): void;   public function attachShippingLabel(Shipment $shipment): void;      // Tracking   public function generateTrackingPixel(int $logId): string;   public function generateTrackedLink(string $url, int $logId): string;      // Webhooks   public function handleWebhook(array $payload, string $provider): void;      // Validación   public function validateEmail(string $email): bool;   public function checkBounceStatus(string $email): ?string; }  // Proveedores de email interface EmailProviderInterface {   public function send(Email $email): string;  // Returns provider message ID   public function sendBatch(array $emails): array;   public function getDeliveryStatus(string $messageId): string; }
4.4 PushService
<?php namespace Drupal\jaraba_notifications\Service;  class PushService implements ChannelInterface {    // Suscripciones   public function subscribe(int $userId, string $userType, PushSubscription $sub): void;   public function unsubscribe(int $userId, string $deviceToken): void;   public function getSubscriptions(int $userId): array;   public function cleanInvalidSubscriptions(): int;      // Envío   public function send(RenderedMessage $message, int $userId): DeliveryResult;   public function sendToDevice(RenderedMessage $message, string $token): DeliveryResult;   public function sendToTopic(RenderedMessage $message, string $topic): int;      // Web Push   public function generateVapidKeys(): array;   public function getPublicVapidKey(): string;      // Rich Notifications   public function setImage(string $imageUrl): void;   public function setActions(array $actions): void;   public function setBadgeCount(int $count): void;   public function setSound(string $sound): void;      // Topics (FCM)   public function subscribeToTopic(string $token, string $topic): void;   public function unsubscribeFromTopic(string $token, string $topic): void;      // Silent Push (data only)   public function sendDataMessage(int $userId, array $data): bool; }
 
4.5 SMSService
<?php namespace Drupal\jaraba_notifications\Service;  class SMSService implements ChannelInterface {    // Envío   public function send(RenderedMessage $message, string $phone): DeliveryResult;   public function sendBatch(array $messages): array;      // Configuración   public function setProvider(string $provider): void;  // 'twilio', 'messagebird'   public function setSenderId(string $senderId): void;   // 'COMERCIO' max 11 chars      // Validación   public function validatePhone(string $phone): bool;   public function formatPhone(string $phone, string $country = 'ES'): string;   public function isLandline(string $phone): bool;      // Rate Limiting (evitar spam)   public function canSendTo(string $phone): bool;   public function getRemainingQuota(string $phone): int;      // Coste   public function estimateCost(string $message, string $country): float;   public function getMessageSegments(string $message): int;      // Webhooks   public function handleDeliveryReport(array $payload): void; }  // Límites SMS const SMS_LIMITS = [   'max_per_phone_per_day' => 5,   'max_per_phone_per_month' => 20,   'max_message_length' => 160,  // 1 segment   'unicode_segment_length' => 70, ];
4.6 WhatsAppService
<?php namespace Drupal\jaraba_notifications\Service;  class WhatsAppService implements ChannelInterface {    // Envío   public function send(RenderedMessage $message, string $phone): DeliveryResult;   public function sendTemplate(string $templateName, string $phone, array $params): DeliveryResult;   public function sendMedia(string $phone, string $mediaUrl, string $caption): DeliveryResult;      // Templates (requeridos por WhatsApp Business API)   public function listApprovedTemplates(): array;   public function submitTemplate(WhatsAppTemplate $template): string;   public function getTemplateStatus(string $templateId): string;      // Sesiones   public function isInSession(string $phone): bool;  // 24h window   public function canSendFreeform(string $phone): bool;      // Interactivo   public function sendButtons(string $phone, string $body, array $buttons): DeliveryResult;   public function sendList(string $phone, string $body, array $sections): DeliveryResult;      // Webhooks   public function handleIncoming(array $payload): void;   public function handleStatus(array $payload): void;      // Opt-in   public function recordOptIn(string $phone, string $source): void;   public function hasOptIn(string $phone): bool; }  // WhatsApp requiere templates pre-aprobados para mensajes fuera de sesión // La sesión de 24h se abre cuando el usuario envía un mensaje
 
5. Catálogo de Notificaciones
5.1 Notificaciones de Pedidos (Cliente)
Key	Trigger	Email	Push	SMS	WhatsApp
order_confirmed	Pedido confirmado	✓	✓	—	—
order_paid	Pago recibido	✓	—	—	—
order_processing	En preparación	—	✓	—	—
order_shipped	Enviado	✓	✓	Opt	Opt
order_out_for_delivery	En reparto	—	✓	Opt	—
order_delivered	Entregado	✓	✓	—	—
order_ready_pickup	Listo para recoger (C&C)	✓	✓	✓	✓
order_pickup_reminder	Recordatorio C&C (24h)	—	✓	✓	—
order_pickup_expiring	C&C expira en 4h	—	✓	✓	—
order_cancelled	Pedido cancelado	✓	✓	—	—
order_refunded	Reembolso procesado	✓	—	—	—
5.2 Notificaciones de Cuenta (Cliente)
Key	Trigger	Email	Push	SMS
welcome	Registro completado	✓	—	—
email_verification	Verificar email	✓	—	—
password_reset	Solicitud reset password	✓	—	—
password_changed	Contraseña cambiada	✓	✓	—
login_new_device	Login desde nuevo dispositivo	✓	✓	—
account_locked	Cuenta bloqueada	✓	—	—
phone_verification	Verificar teléfono	—	—	✓
profile_updated	Perfil actualizado	—	✓	—
5.3 Notificaciones de Fidelización
Key	Trigger	Email	Push
points_earned	Puntos ganados (compra/reseña)	—	✓
points_expiring	Puntos por expirar (30 días)	✓	✓
level_up	Subida de nivel	✓	✓
birthday_bonus	Cumpleaños	✓	✓
referral_success	Referido completó compra	✓	✓
reward_earned	Recompensa disponible	—	✓
 
5.4 Notificaciones Personalizadas
Key	Trigger	Email	Push	Opt-in
wishlist_price_drop	Bajada de precio en favorito	✓	✓	Sí
wishlist_back_in_stock	Producto disponible de nuevo	✓	✓	Sí
cart_abandoned_1h	Carrito abandonado 1h	—	✓	Sí
cart_abandoned_24h	Carrito abandonado 24h	✓	—	Sí
review_request	Solicitar reseña (7 días)	✓	—	Sí
flash_offer_alert	Flash offer de interés	—	✓	Sí
5.5 Notificaciones para Comerciantes
Key	Trigger	Email	Push	SMS
merchant_new_order	Nuevo pedido recibido	✓	✓	Opt
merchant_order_cancelled	Pedido cancelado por cliente	✓	✓	—
merchant_low_stock	Stock bajo (< umbral)	✓	✓	—
merchant_out_of_stock	Producto agotado	✓	✓	Opt
merchant_new_review	Nueva reseña recibida	✓	✓	—
merchant_negative_review	Reseña negativa (1-2★)	✓	✓	✓
merchant_question	Nueva pregunta de producto	✓	✓	—
merchant_pickup_pending	C&C sin recoger >24h	—	✓	—
merchant_payout	Pago procesado	✓	—	—
merchant_daily_summary	Resumen diario	✓	—	—
 
6. Plantillas de Email
6.1 Estructura Base de Email
<!-- base_email.html.twig --> <!DOCTYPE html> <html lang="{{ lang }}"> <head>   <meta charset="utf-8">   <meta name="viewport" content="width=device-width, initial-scale=1.0">   <title>{{ subject }}</title> </head> <body style="margin: 0; padding: 0; background: #f5f5f5;">   <table width="100%" cellpadding="0" cellspacing="0">     <tr>       <td align="center" style="padding: 20px 0;">                  <!-- Container -->         <table width="600" cellpadding="0" cellspacing="0" style="background: #fff;">                      <!-- Header con logo -->           <tr>             <td style="padding: 30px; text-align: center; background: {{ merchant.primary_color|default('#1B4F72') }};">               <img src="{{ merchant.logo_url }}" alt="{{ merchant.name }}" height="40">             </td>           </tr>                      <!-- Contenido -->           <tr>             <td style="padding: 40px 30px;">               {% block content %}{% endblock %}             </td>           </tr>                      <!-- CTA Button -->           {% if action_url %}           <tr>             <td style="padding: 0 30px 40px; text-align: center;">               <a href="{{ action_url }}" style="                 display: inline-block;                 padding: 14px 28px;                 background: {{ merchant.primary_color|default('#1B4F72') }};                 color: #fff;                 text-decoration: none;                 border-radius: 4px;                 font-weight: bold;               ">{{ action_label }}</a>             </td>           </tr>           {% endif %}                      <!-- Footer -->           <tr>             <td style="padding: 20px 30px; background: #f9f9f9; font-size: 12px; color: #666;">               {% block footer %}               <p>{{ merchant.name }} | {{ merchant.address }}</p>               <p>                 <a href="{{ unsubscribe_url }}">Gestionar preferencias</a> |                 <a href="{{ merchant.privacy_url }}">Privacidad</a>               </p>               {% endblock %}             </td>           </tr>                    </table>       </td>     </tr>   </table>      <!-- Tracking pixel -->   <img src="{{ tracking_pixel_url }}" width="1" height="1" style="display:none;"> </body> </html>
 
6.2 Template: Pedido Confirmado
<!-- order_confirmed.html.twig --> {% extends 'base_email.html.twig' %}  {% block content %} <h1 style="color: #333; margin: 0 0 20px;">¡Gracias por tu pedido!</h1>  <p>Hola {{ customer.first_name }},</p> <p>Hemos recibido tu pedido <strong>{{ order.number }}</strong> y lo estamos preparando.</p>  <!-- Resumen de pedido --> <table width="100%" style="margin: 30px 0; border-collapse: collapse;">   <tr style="background: #f5f5f5;">     <th style="padding: 10px; text-align: left;">Producto</th>     <th style="padding: 10px; text-align: right;">Cantidad</th>     <th style="padding: 10px; text-align: right;">Precio</th>   </tr>   {% for item in order.items %}   <tr>     <td style="padding: 10px; border-bottom: 1px solid #eee;">       <img src="{{ item.image }}" width="50" style="vertical-align: middle;">       {{ item.title }}       {% if item.variation %}<br><small>{{ item.variation }}</small>{% endif %}     </td>     <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">       {{ item.quantity }}     </td>     <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">       {{ item.total|format_currency('EUR') }}     </td>   </tr>   {% endfor %}   <tr>     <td colspan="2" style="padding: 10px; text-align: right;"><strong>Subtotal</strong></td>     <td style="padding: 10px; text-align: right;">{{ order.subtotal|format_currency('EUR') }}</td>   </tr>   {% if order.discount > 0 %}   <tr>     <td colspan="2" style="padding: 10px; text-align: right;">Descuento</td>     <td style="padding: 10px; text-align: right; color: green;">-{{ order.discount|format_currency('EUR') }}</td>   </tr>   {% endif %}   <tr>     <td colspan="2" style="padding: 10px; text-align: right;">Envío</td>     <td style="padding: 10px; text-align: right;">       {{ order.shipping > 0 ? order.shipping|format_currency('EUR') : 'Gratis' }}     </td>   </tr>   <tr style="font-size: 18px;">     <td colspan="2" style="padding: 15px 10px; text-align: right;"><strong>Total</strong></td>     <td style="padding: 15px 10px; text-align: right;"><strong>{{ order.total|format_currency('EUR') }}</strong></td>   </tr> </table>  <!-- Dirección de envío --> <div style="background: #f9f9f9; padding: 20px; margin: 20px 0;">   <h3 style="margin: 0 0 10px;">📦 Dirección de envío</h3>   <p style="margin: 0;">     {{ order.shipping_address.name }}<br>     {{ order.shipping_address.street }}<br>     {{ order.shipping_address.postal_code }} {{ order.shipping_address.city }}   </p> </div>  <p>Te avisaremos cuando tu pedido esté en camino.</p> {% endblock %}
 
6.3 Template: Click & Collect Listo
<!-- order_ready_pickup.html.twig --> {% extends 'base_email.html.twig' %}  {% block content %} <h1 style="color: #333; margin: 0 0 20px;">🎉 ¡Tu pedido está listo!</h1>  <p>Hola {{ customer.first_name }},</p> <p>Tu pedido <strong>{{ order.number }}</strong> está preparado y listo para recoger.</p>  <!-- Código de recogida DESTACADO --> <div style="background: #e8f5e9; border: 2px solid #4caf50; border-radius: 8px; padding: 30px; text-align: center; margin: 30px 0;">   <p style="margin: 0 0 10px; font-size: 14px; color: #666;">Tu código de recogida</p>   <p style="margin: 0; font-size: 36px; font-weight: bold; letter-spacing: 4px; color: #2e7d32;">     {{ order.pickup_code }}   </p>   <p style="margin: 10px 0 0; font-size: 12px; color: #666;">     Muestra este código o el QR en tienda   </p> </div>  <!-- QR Code --> <div style="text-align: center; margin: 20px 0;">   <img src="{{ order.pickup_qr_url }}" width="150" height="150" alt="QR Code"> </div>  <!-- Tienda de recogida --> <div style="background: #f5f5f5; padding: 20px; margin: 20px 0;">   <h3 style="margin: 0 0 10px;">📍 Recoge en</h3>   <p style="margin: 0; font-weight: bold;">{{ order.pickup_store.name }}</p>   <p style="margin: 5px 0;">{{ order.pickup_store.address }}</p>   <p style="margin: 5px 0;">     <strong>Horario:</strong> {{ order.pickup_store.hours }}   </p>   <p style="margin: 10px 0 0;">     <a href="{{ order.pickup_store.maps_url }}">Ver en Google Maps →</a>   </p> </div>  <!-- Fecha límite --> <p style="color: #f57c00; font-weight: bold;">   ⏰ Tienes hasta el {{ order.pickup_expires_at|date('d/m/Y') }} para recogerlo </p>  <p>Recuerda llevar tu DNI o el email de confirmación.</p> {% endblock %}
 
7. Push Notifications
7.1 Configuración Web Push
// Service Worker: sw.js self.addEventListener('push', (event) => {   const data = event.data?.json() || {};      const options = {     body: data.body,     icon: data.icon || '/icons/icon-192.png',     badge: data.badge || '/icons/badge-72.png',     image: data.image,  // Big image     data: {       url: data.url,       notificationId: data.notificationId,     },     actions: data.actions || [],     tag: data.tag,  // Reemplaza notificaciones con mismo tag     renotify: data.renotify || false,     requireInteraction: data.requireInteraction || false,     silent: data.silent || false,     vibrate: [200, 100, 200],   };      event.waitUntil(     self.registration.showNotification(data.title, options)   ); });  self.addEventListener('notificationclick', (event) => {   event.notification.close();      const url = event.notification.data?.url;   if (url) {     event.waitUntil(       clients.openWindow(url)     );   }      // Track click   fetch('/api/notifications/track-click', {     method: 'POST',     body: JSON.stringify({ id: event.notification.data?.notificationId }),   }); });
7.2 Ejemplos de Push Notifications
// Nuevo pedido (para comerciante) {   title: '🛒 Nuevo pedido',   body: 'Pedido #ORD-2026-001234 por 89,95€',   icon: '/icons/order.png',   badge: '/icons/badge.png',   tag: 'new-order',   data: {     url: '/merchant/orders/ORD-2026-001234',     sound: 'order.mp3',   },   actions: [     { action: 'view', title: 'Ver pedido' },     { action: 'process', title: 'Procesar' },   ],   requireInteraction: true,  // No desaparece solo }  // Pedido enviado (para cliente) {   title: '📦 Tu pedido está en camino',   body: 'El pedido #ORD-2026-001234 ha sido enviado con MRW',   icon: '/icons/shipping.png',   image: 'https://example.com/tracking-map.png',  // Big image   data: {     url: '/account/orders/ORD-2026-001234',   },   actions: [     { action: 'track', title: 'Seguir envío' },   ], }  // Bajada de precio (para cliente) {   title: '💰 ¡Bajada de precio!',   body: '"Camiseta Premium" ahora a 19,95€ (antes 29,95€)',   icon: '/icons/sale.png',   image: 'https://example.com/products/camiseta-123.jpg',   data: {     url: '/producto/camiseta-premium',   },   actions: [     { action: 'buy', title: 'Comprar ahora' },     { action: 'wishlist', title: 'Ver favoritos' },   ], }
 
8. SMS y WhatsApp
8.1 Templates SMS
// SMS Templates (max 160 chars para 1 segmento)  const smsTemplates = {   // Click & Collect   order_ready_pickup: {     body: '{{merchant}}: Tu pedido {{order_number}} está listo. Código: {{pickup_code}}. Recoge antes del {{expires}}. Info: {{short_url}}',     // Ejemplo: "MODASHOP: Tu pedido ORD-1234 está listo. Código: ABCD-1234. Recoge antes del 20/01. Info: bit.ly/xyz"     maxLength: 160,   },      order_pickup_reminder: {     body: '{{merchant}}: Recuerda recoger tu pedido {{order_number}} hoy. Código: {{pickup_code}}. Horario: {{store_hours}}',     maxLength: 160,   },      order_pickup_expiring: {     body: '⚠️ {{merchant}}: Tu pedido {{order_number}} caduca en 4h. Recógelo con código {{pickup_code}} o será cancelado.',     maxLength: 160,   },      // Verificación   phone_verification: {     body: 'Tu código de verificación para {{merchant}} es: {{code}}. Válido por 10 minutos.',     maxLength: 100,   },      // Merchant alerts   merchant_new_order: {     body: '🛒 Nuevo pedido en {{merchant}}: {{order_number}} por {{total}}. Ver: {{short_url}}',     maxLength: 160,   },      merchant_negative_review: {     body: '⚠️ {{merchant}}: Nueva reseña de {{rating}}⭐ en "{{product}}". Responde pronto: {{short_url}}',     maxLength: 160,   }, };  // Notas: // - Sender ID: "COMERCIO" (max 11 chars alfanuméricos) // - Emojis cuentan como Unicode (70 chars/segmento) // - Links acortados con bit.ly o similar // - Cumplir LSSI: incluir forma de darse de baja
8.2 Templates WhatsApp Business
// WhatsApp Business API requiere templates pre-aprobados por Meta // Categorías: MARKETING, UTILITY, AUTHENTICATION  const whatsappTemplates = {   // UTILITY: Actualizaciones de pedido (no requiere opt-in previo)   order_shipped: {     name: 'order_shipped_es',     language: 'es',     category: 'UTILITY',     components: [       {         type: 'HEADER',         format: 'TEXT',         text: '📦 Tu pedido está en camino',       },       {         type: 'BODY',         text: 'Hola {{1}}, tu pedido {{2}} ha sido enviado.\n\n'             + 'Transportista: {{3}}\n'             + 'Nº seguimiento: {{4}}\n\n'             + 'Entrega estimada: {{5}}',         // {{1}} = nombre, {{2}} = order_number, {{3}} = carrier, {{4}} = tracking, {{5}} = eta       },       {         type: 'FOOTER',         text: 'Responde a este mensaje si tienes dudas',       },       {         type: 'BUTTONS',         buttons: [           { type: 'URL', text: 'Seguir envío', url: 'https://comercioconecta.es/tracking/{{1}}' },         ],       },     ],   },      // UTILITY: Click & Collect   order_ready_pickup: {     name: 'order_ready_pickup_es',     language: 'es',     category: 'UTILITY',     components: [       {         type: 'HEADER',         format: 'IMAGE',  // Imagen del QR       },       {         type: 'BODY',         text: '🎉 ¡Tu pedido {{1}} está listo!\n\n'             + 'Código de recogida: *{{2}}*\n\n'             + '📍 {{3}}\n'             + '🕐 Horario: {{4}}\n\n'             + 'Tienes hasta el {{5}} para recogerlo.',       },       {         type: 'BUTTONS',         buttons: [           { type: 'URL', text: 'Ver en mapa', url: 'https://maps.google.com/?q={{1}}' },         ],       },     ],   },      // MARKETING: Requiere opt-in explícito   flash_offer: {     name: 'flash_offer_es',     language: 'es',     category: 'MARKETING',     components: [       {         type: 'HEADER',         format: 'IMAGE',       },       {         type: 'BODY',         text: '⚡ OFERTA FLASH en {{1}}\n\n'             + '{{2}}% de descuento en {{3}}\n'             + 'Solo hasta las {{4}}\n\n'             + 'No te lo pierdas!',       },       {         type: 'BUTTONS',         buttons: [           { type: 'URL', text: 'Ver oferta', url: 'https://comercioconecta.es/offer/{{1}}' },           { type: 'QUICK_REPLY', text: 'No me interesa' },         ],       },     ],   }, };
 
9. Preferencias y Opt-in/Out
9.1 NotificationPreferencesService
<?php namespace Drupal\jaraba_notifications\Service;  class NotificationPreferencesService {    // Consultar   public function getPreferences(int $userId, string $userType): NotificationPreferences;   public function getPreference(int $userId, string $key, string $channel): bool;      // Actualizar   public function updatePreferences(int $userId, array $prefs): void;   public function setPreference(int $userId, string $key, string $channel, bool $enabled): void;      // Opt-in/out global   public function optInMarketing(int $userId, string $channel): void;   public function optOutMarketing(int $userId, string $channel): void;   public function optOutAll(int $userId): void;  // Solo promocionales      // Unsubscribe link   public function generateUnsubscribeToken(int $userId): string;   public function processUnsubscribe(string $token, ?array $categories): void;      // Verificar antes de enviar   public function canSend(int $userId, string $templateKey, string $channel): bool;   public function getBlockReason(int $userId, string $templateKey, string $channel): ?string;      // Consentimiento GDPR   public function recordConsent(int $userId, string $type, string $source): void;   public function getConsentHistory(int $userId): array;   public function withdrawConsent(int $userId, string $type): void; }
9.2 Matriz de Preferencias
// Estructura de preferencias por usuario $preferences = [   // Transaccionales (no desactivables)   'orders' => [     'email' => true,  // Siempre true     'push' => true,   // Configurable     'sms' => false,   // Opt-in   ],      // Envíos   'shipping' => [     'email' => true,     'push' => true,     'sms' => false,     'whatsapp' => false,   ],      // Marketing (requiere opt-in)   'marketing' => [     'email' => false,     'push' => false,     'whatsapp' => false,   ],      // Personalizadas   'price_alerts' => [     'email' => true,     'push' => false,   ],      // Fidelización   'loyalty' => [     'email' => true,     'push' => true,   ],      // Reseñas   'reviews' => [     'email' => true,     'push' => false,   ], ];  // Reglas de negocio const PREFERENCE_RULES = [   // Transaccionales: siempre email, push/sms opcionales   'order_confirmed' => ['email' => 'forced', 'push' => 'optional', 'sms' => 'optional'],   'order_shipped' => ['email' => 'forced', 'push' => 'optional', 'sms' => 'optional'],      // C&C: SMS recomendado pero opt-in   'order_ready_pickup' => ['email' => 'forced', 'push' => 'optional', 'sms' => 'recommended'],      // Marketing: todo requiere opt-in   'flash_offer_alert' => ['email' => 'opt-in', 'push' => 'opt-in', 'whatsapp' => 'opt-in'],      // Seguridad: no desactivable   'password_changed' => ['email' => 'forced', 'push' => 'forced'], ];
 
10. Queue y Procesamiento
10.1 NotificationQueueService
<?php namespace Drupal\jaraba_notifications\Service;  class NotificationQueueService {    // Encolar   public function enqueue(QueuedNotification $notification): int;   public function enqueueWithPriority(QueuedNotification $notification, string $priority): int;   public function enqueueBatch(array $notifications, string $priority = 'normal'): array;      // Procesar   public function process(int $batchSize = 100): ProcessResult;   public function processChannel(string $channel, int $batchSize = 100): ProcessResult;   public function processHighPriority(): ProcessResult;      // Gestión   public function getQueueStats(): QueueStats;   public function getQueuedCount(string $channel = null): int;   public function clearChannel(string $channel): int;   public function retryFailed(int $maxRetries = 3): int;      // Rate limiting   public function checkRateLimit(string $channel, string $recipient): bool;   public function getRateLimitStatus(): array; }  // Prioridades enum QueuePriority: string {   case HIGH = 'high';      // Transaccionales urgentes (C&C, verificación)   case NORMAL = 'normal';  // Transaccionales normales   case LOW = 'low';        // Marketing, reminders   case BULK = 'bulk';      // Campañas masivas }  // Rate limits por canal const RATE_LIMITS = [   'email' => [     'per_second' => 50,     'per_minute' => 1000,     'per_hour' => 10000,   ],   'push' => [     'per_second' => 100,     'per_minute' => 5000,   ],   'sms' => [     'per_second' => 10,     'per_minute' => 200,     'per_recipient_per_day' => 5,   ],   'whatsapp' => [     'per_second' => 10,     'per_minute' => 200,     'per_recipient_per_day' => 10,   ], ];
10.2 Retry Logic
// Estrategia de reintentos para fallos const RETRY_CONFIG = [   'email' => [     'max_retries' => 3,     'delays' => [60, 300, 900],  // 1min, 5min, 15min     'retry_on' => ['timeout', 'temporary_failure', 'rate_limited'],     'fail_on' => ['invalid_email', 'hard_bounce', 'spam_complaint'],   ],      'push' => [     'max_retries' => 2,     'delays' => [30, 120],     'retry_on' => ['timeout', 'server_error'],     'fail_on' => ['invalid_token', 'unregistered'],   ],      'sms' => [     'max_retries' => 2,     'delays' => [60, 300],     'retry_on' => ['timeout', 'carrier_error'],     'fail_on' => ['invalid_number', 'blocked', 'landline'],   ],      'whatsapp' => [     'max_retries' => 2,     'delays' => [60, 300],     'retry_on' => ['timeout', 'rate_limited'],     'fail_on' => ['invalid_number', 'not_whatsapp_user', 'blocked'],   ], ];  // Backoff exponencial para rate limiting function calculateBackoff(int $attempt, string $channel): int {   $baseDelay = RETRY_CONFIG[$channel]['delays'][$attempt - 1] ?? 900;   $jitter = random_int(0, $baseDelay / 4);   return $baseDelay + $jitter; }
 
11. Analytics y Métricas
11.1 NotificationAnalyticsService
<?php namespace Drupal\jaraba_notifications\Service;  class NotificationAnalyticsService {    // Métricas globales   public function getOverview(DateRange $range): NotificationOverview;   public function getByChannel(DateRange $range): array;   public function getByTemplate(string $templateKey, DateRange $range): TemplateStats;      // Tasas   public function getDeliveryRate(string $channel, DateRange $range): float;   public function getOpenRate(string $channel, DateRange $range): float;   public function getClickRate(string $channel, DateRange $range): float;   public function getBounceRate(string $channel, DateRange $range): float;   public function getUnsubscribeRate(DateRange $range): float;      // Por template   public function getTemplatePerformance(DateRange $range): array;   public function compareTemplates(array $templateKeys, DateRange $range): array;      // Por comercio (multi-tenant)   public function getMerchantStats(int $merchantId, DateRange $range): array;      // Tendencias   public function getTrend(string $metric, string $channel, DateRange $range): array;      // Exportación   public function exportReport(DateRange $range, string $format): string; }
11.2 KPIs de Notificaciones
Métrica	Fórmula	Benchmark Email	Objetivo
Delivery Rate	Delivered / Sent × 100	>98%	>99%
Open Rate	Opened / Delivered × 100	15-25%	>25%
Click Rate (CTR)	Clicked / Opened × 100	2-5%	>5%
Click-to-Open Rate	Clicked / Opened × 100	10-15%	>15%
Bounce Rate	Bounced / Sent × 100	<2%	<1%
Unsubscribe Rate	Unsubs / Delivered × 100	<0.5%	<0.2%
Spam Complaint Rate	Complaints / Delivered × 100	<0.1%	<0.05%
11.3 Dashboard de Analytics
// Widgets del dashboard de notificaciones  1. RESUMEN GENERAL    - Total enviadas (período)    - Por canal: Email, Push, SMS, WhatsApp    - Delivery rate global    - Engagement rate global  2. GRÁFICO DE VOLUMEN    - Línea temporal de envíos    - Stacked por canal    - Comparativa con período anterior  3. FUNNEL DE ENGAGEMENT (Email)    Sent (100%) → Delivered (99%) → Opened (25%) → Clicked (5%)  4. TOP TEMPLATES    - Mejor open rate    - Mejor click rate    - Más enviadas    - Peor performance (para optimizar)  5. PROBLEMAS    - Bounces recientes    - Spam complaints    - Tokens inválidos (push)    - Rate limit warnings  6. MAPA DE CALOR    - Mejores horas para enviar    - Mejores días de la semana    - Por tipo de notificación
 
12. APIs REST
12.1 Endpoints de Preferencias
Método	Endpoint	Descripción
GET	/api/v1/notifications/preferences	Obtener mis preferencias
PATCH	/api/v1/notifications/preferences	Actualizar preferencias
POST	/api/v1/notifications/unsubscribe	Darse de baja (con token)
GET	/api/v1/notifications/history	Mi historial de notificaciones
12.2 Endpoints de Push
Método	Endpoint	Descripción
POST	/api/v1/push/subscribe	Registrar suscripción push
DELETE	/api/v1/push/subscribe/{token}	Eliminar suscripción
GET	/api/v1/push/vapid-key	Obtener clave pública VAPID
POST	/api/v1/push/test	Enviar push de prueba
12.3 Endpoints Admin
Método	Endpoint	Descripción
GET	/api/v1/admin/notifications/templates	Listar templates
POST	/api/v1/admin/notifications/templates	Crear template
GET	/api/v1/admin/notifications/templates/{key}	Obtener template
PATCH	/api/v1/admin/notifications/templates/{key}	Actualizar template
POST	/api/v1/admin/notifications/templates/{key}/preview	Preview de template
GET	/api/v1/admin/notifications/logs	Logs de envío
GET	/api/v1/admin/notifications/analytics	Analytics
POST	/api/v1/admin/notifications/send	Envío manual
 
13. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades core. NotificationService. EmailService básico.	Drupal Mail System
Sprint 2	Semana 3-4	TemplateService. Templates de pedidos. Amazon SES.	Sprint 1
Sprint 3	Semana 5-6	PushService. Web Push. Firebase Cloud Messaging.	Sprint 2
Sprint 4	Semana 7-8	SMSService. Twilio integration. Templates SMS.	Sprint 3
Sprint 5	Semana 9-10	WhatsAppService. Business API. Templates aprobados.	Sprint 4
Sprint 6	Semana 11-12	Analytics. Dashboard admin. Queue optimizations. QA.	Sprint 5
13.1 Criterios de Aceptación Sprint 2 (Email)
✓ Templates de pedidos funcionando
✓ Envío via Amazon SES
✓ Tracking de opens y clicks
✓ Manejo de bounces
✓ Unsubscribe link funcional
13.2 Dependencias
• Amazon SES o Resend (email)
• Firebase Cloud Messaging (push)
• Twilio o MessageBird (SMS)
• WhatsApp Business API via 360dialog o Twilio
• Redis o similar para queue
• 75_Customer_Portal (preferencias)
--- Fin del Documento ---
76_ComercioConecta_Notifications_System_v1.docx | Jaraba Impact Platform | Enero 2026
