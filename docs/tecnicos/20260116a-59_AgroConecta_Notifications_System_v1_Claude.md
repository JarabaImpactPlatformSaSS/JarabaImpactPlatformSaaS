SISTEMA DE NOTIFICACIONES
Email, Push, SMS, In-App y Webhooks
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	59_AgroConecta_Notifications_System
Dependencias:	Symfony Mailer, Firebase FCM, Twilio
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Notificaciones Unificado para AgroConecta, que centraliza todas las comunicaciones del marketplace a través de múltiples canales, garantizando que clientes, productores y administradores reciban información relevante en el momento adecuado.
1.1 Objetivos del Sistema
•	Omnicanalidad: Comunicar por el canal preferido del usuario
•	Relevancia: Enviar notificaciones pertinentes y oportunas
•	Personalización: Contenido adaptado al contexto y usuario
•	Control: Preferencias de usuario respetadas (opt-in/out)
•	Trazabilidad: Registro completo de todas las comunicaciones
•	Escalabilidad: Sistema de colas para alto volumen
1.2 Canales de Notificación
Canal	Tecnología	Uso Principal	Latencia
📧 Email	Symfony Mailer + SendGrid/SES	Transaccional, marketing	< 30 seg
🔔 Push Web	Firebase Cloud Messaging (FCM)	Alertas en tiempo real	< 5 seg
📱 Push App	FCM (Android) + APNs (iOS)	Mobile engagement	< 5 seg
📲 SMS	Twilio / Vonage	Crítico (OTP, alertas)	< 10 seg
🖥️ In-App	Custom notification center	Dentro de la aplicación	Instantáneo
🔗 Webhook	HTTP POST a URLs externas	Integraciones B2B	< 5 seg
1.3 Stack Tecnológico
Componente	Tecnología
Orquestador	Custom Notification Service + ECA Rules
Cola de mensajes	Drupal Queue API + Redis (opcional RabbitMQ)
Templates	Twig templates con tokens dinámicos
Preferencias	User preferences entity por canal/tipo
Logging	Custom notification_log entity
Programación	Scheduler + Cron para envíos diferidos
Analytics	Open/click tracking, delivery reports
 
2. Catálogo de Notificaciones
2.1 Notificaciones de Pedidos (Cliente)
Evento	Mensaje	Canales	Prioridad
order_confirmed	Tu pedido #X ha sido confirmado	Email, Push	Alta
order_processing	Tu pedido está siendo preparado	Push, In-App	Media
order_shipped	Tu pedido ha sido enviado (tracking)	Email, Push, SMS	Alta
order_out_for_delivery	Tu pedido saldrá hoy para entrega	Push, SMS	Alta
order_delivered	Tu pedido ha sido entregado	Email, Push	Media
order_cancelled	Tu pedido ha sido cancelado	Email, Push	Alta
refund_processed	Tu reembolso de €X ha sido procesado	Email	Alta
delivery_issue	Hay un problema con tu entrega	Email, Push, SMS	Crítica
2.2 Notificaciones de Pedidos (Productor)
Evento	Mensaje	Canales	Prioridad
new_order	Nuevo pedido #X - €Y (N productos)	Email, Push, SMS	Crítica
order_reminder	Pedido #X pendiente de confirmar (4h)	Push, SMS	Alta
order_urgent	⚠️ Pedido #X sin confirmar (8h)	Email, SMS	Crítica
pickup_scheduled	Recogida programada para mañana	Email, Push	Alta
payout_sent	Pago de €X enviado a tu cuenta	Email	Media
review_received	Nueva reseña en tu producto X	Email, Push	Media
negative_review	⚠️ Reseña negativa requiere atención	Email, Push	Alta
2.3 Notificaciones de Cuenta
Evento	Mensaje	Canales	Prioridad
welcome	Bienvenido/a a AgroConecta	Email	Media
email_verification	Verifica tu email (código/link)	Email	Crítica
password_reset	Restablece tu contraseña	Email	Crítica
password_changed	Tu contraseña ha sido cambiada	Email	Alta
login_new_device	Nuevo inicio de sesión detectado	Email, Push	Alta
otp_code	Tu código de verificación es: XXXXXX	SMS	Crítica
producer_approved	Tu cuenta de productor ha sido aprobada	Email	Alta
producer_rejected	Tu solicitud necesita cambios	Email	Alta
 
2.4 Notificaciones de Marketing
Evento	Mensaje	Canales	Opt-in
abandoned_cart	Has dejado productos en tu carrito	Email, Push	Requerido
price_drop	Un producto de tu wishlist ha bajado	Email, Push	Requerido
back_in_stock	X vuelve a estar disponible	Email, Push	Requerido
new_from_favorite	Nuevo producto de productor favorito	Email, Push	Requerido
promotion	Ofertas especiales para ti	Email	Requerido
newsletter	Newsletter semanal	Email	Requerido
win_back	Te echamos de menos - 10% descuento	Email	Requerido
birthday	¡Feliz cumpleaños! Regalo especial	Email, Push	Requerido
2.5 Notificaciones de Sistema (Admin)
Evento	Mensaje	Canales	Prioridad
stock_low	Stock bajo en producto X (<10 uds)	Email, Slack	Media
stock_out	Producto X agotado	Email, Push, Slack	Alta
order_stuck	Pedido #X sin procesar >4h	Email, Slack	Alta
payment_failed	Fallo de pago - revisar gateway	Email, SMS, Slack	Crítica
producer_inactive	Productor X inactivo 7 días	Email	Media
daily_summary	Resumen diario de ventas y KPIs	Email	Baja
weekly_report	Informe semanal de rendimiento	Email	Baja
 
3. Modelo de Datos
3.1 Entidad: notification_template
Plantillas de notificación para cada tipo y canal:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
type	VARCHAR(64)	Tipo de notificación	NOT NULL, INDEX
channel	VARCHAR(20)	email, push, sms, in_app	NOT NULL, INDEX
name	VARCHAR(100)	Nombre descriptivo	NOT NULL
subject	VARCHAR(200)	Asunto (email) o título (push)	NOT NULL
body	TEXT	Contenido con tokens Twig	NOT NULL
body_html	TEXT	Versión HTML (email)	NULLABLE
tokens	JSON	Tokens disponibles documentados	NULLABLE
is_active	BOOLEAN	Template activo	DEFAULT TRUE
language	VARCHAR(5)	Código idioma (es, en)	DEFAULT 'es'
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL
3.2 Entidad: notification_log
Registro de todas las notificaciones enviadas:
Campo	Tipo	Descripción	Restricciones
id	BigSerial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
template_id	INT	Template utilizado	FK notification_template.id
type	VARCHAR(64)	Tipo de notificación	NOT NULL, INDEX
channel	VARCHAR(20)	Canal utilizado	NOT NULL, INDEX
recipient_type	VARCHAR(20)	user, producer, admin	NOT NULL
recipient_id	INT	ID del destinatario	NOT NULL, INDEX
recipient_email	VARCHAR(255)	Email o teléfono destino	NOT NULL
subject	VARCHAR(200)	Asunto renderizado	NULLABLE
body_preview	VARCHAR(500)	Preview del contenido	NULLABLE
context	JSON	Datos de contexto usados	NULLABLE
status	VARCHAR(20)	pending, sent, delivered, failed, bounced	NOT NULL, INDEX
error_message	TEXT	Mensaje de error si falló	NULLABLE
external_id	VARCHAR(100)	ID del proveedor externo	NULLABLE
opened_at	DATETIME	Momento de apertura	NULLABLE
clicked_at	DATETIME	Momento de click	NULLABLE
created	DATETIME	Momento de envío	NOT NULL, INDEX
3.3 Entidad: notification_preference
Preferencias de notificación del usuario:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK user.id, NOT NULL
notification_type	VARCHAR(64)	Tipo de notificación	NOT NULL
channel_email	BOOLEAN	Recibir por email	DEFAULT TRUE
channel_push	BOOLEAN	Recibir push	DEFAULT TRUE
channel_sms	BOOLEAN	Recibir SMS	DEFAULT FALSE
channel_in_app	BOOLEAN	Ver en app	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL
 
4. Sistema de Templates
4.1 Tokens Disponibles
Token	Descripción	Ejemplo
{{ user.name }}	Nombre del usuario	María García
{{ user.email }}	Email del usuario	maria@email.com
{{ order.number }}	Número de pedido	#AC-10234
{{ order.total }}	Total del pedido formateado	€67,50
{{ order.items_count }}	Número de productos	3
{{ order.tracking_url }}	URL de seguimiento	https://track.mrw.es/...
{{ product.name }}	Nombre del producto	AOVE Picual Premium
{{ producer.name }}	Nombre del productor	Finca Los Olivos
{{ site.name }}	Nombre del marketplace	AgroConecta
{{ site.url }}	URL base del sitio	https://agroconecta.es
4.2 Ejemplo Template Email
{% block subject %}
Tu pedido {{ order.number }} ha sido enviado 📦
{% endblock %}

{% block body %}
Hola {{ user.name }},

¡Tu pedido {{ order.number }} está en camino!

**Detalles del envío:**
- Transportista: {{ shipment.carrier }}
- Nº seguimiento: {{ shipment.tracking_number }}
- Entrega estimada: {{ shipment.estimated_delivery|date('d/m/Y') }}

[Seguir mi pedido]({{ order.tracking_url }})

Gracias por confiar en {{ site.name }}.
{% endblock %}
 
5. Centro de Preferencias
5.1 Interfaz de Usuario
┌─────────────────────────────────────────────────────────────────────────┐
│  🔔 PREFERENCIAS DE NOTIFICACIONES                                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  📦 PEDIDOS                              Email   Push    SMS    App     │
│  ──────────────────────────────────────────────────────────────────     │
│  Confirmación de pedido                   [✓]    [✓]     [ ]    [✓]     │
│  Pedido enviado                           [✓]    [✓]     [✓]    [✓]     │
│  Pedido entregado                         [✓]    [✓]     [ ]    [✓]     │
│  Incidencias                              [✓]    [✓]     [✓]    [✓]     │
│                                                                         │
│  📣 MARKETING                            Email   Push    SMS    App     │
│  ──────────────────────────────────────────────────────────────────     │
│  Ofertas y promociones                    [✓]    [ ]     [ ]    [ ]     │
│  Newsletter semanal                       [✓]    [ ]     [ ]    [ ]     │
│  Productos de favoritos                   [✓]    [✓]     [ ]    [✓]     │
│  Carrito abandonado                       [✓]    [ ]     [ ]    [ ]     │
│                                                                         │
│  ⚙️ CUENTA (no desactivables)            Email   Push    SMS    App     │
│  ──────────────────────────────────────────────────────────────────     │
│  Seguridad de cuenta                      [✓]    [✓]     [✓]    [✓]     │
│  Verificación                             [✓]    [ ]     [✓]    [ ]     │
│                                                                         │
│                              [Guardar Preferencias]                     │
└─────────────────────────────────────────────────────────────────────────┘
5.2 Reglas de Negocio
•	Transaccionales obligatorias: Confirmación pedido, envío, seguridad siempre activas
•	Marketing opt-in: Requiere consentimiento explícito (GDPR)
•	Unsubscribe global: Un click para desactivar todo el marketing
•	SMS opt-in doble: Confirmación de número + consentimiento
•	Defaults inteligentes: Email ON, Push ON, SMS OFF para nuevos usuarios
 
6. Arquitectura del Sistema
6.1 Flujo de Envío
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   EVENTO    │────▶│ NOTIFICATION│────▶│    QUEUE    │────▶│   WORKERS   │
│  (trigger)  │     │   SERVICE   │     │   (Redis)   │     │  (channel)  │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                           │                                      │
                           ▼                                      ▼
                    ┌─────────────┐                        ┌─────────────┐
                    │ PREFERENCES │                        │  PROVIDERS  │
                    │   CHECK     │                        │ SendGrid,FCM│
                    └─────────────┘                        │ Twilio...   │
                                                           └─────────────┘
                                                                  │
                                                                  ▼
                                                           ┌─────────────┐
                                                           │ NOTIFICATION│
                                                           │     LOG     │
                                                           └─────────────┘
6.2 Pseudocódigo del Servicio
class NotificationService {
  
  function send(type, recipient, context, channels = null) {
    // 1. Determinar canales habilitados
    channels = channels ?? this.getEnabledChannels(type, recipient);
    
    // 2. Verificar preferencias del usuario
    channels = this.filterByPreferences(channels, type, recipient);
    
    // 3. Para cada canal, encolar mensaje
    for (channel of channels) {
      template = this.getTemplate(type, channel);
      rendered = this.render(template, context);
      
      queue.add('notification:' + channel, {
        type, channel, recipient, rendered, context
      });
    }
  }
}
 
7. APIs
7.1 Endpoints de Notificaciones
Método	Endpoint	Descripción
GET	/api/v1/me/notifications	Listar notificaciones in-app
GET	/api/v1/me/notifications/unread-count	Contador de no leídas
POST	/api/v1/me/notifications/{id}/read	Marcar como leída
POST	/api/v1/me/notifications/read-all	Marcar todas como leídas
DELETE	/api/v1/me/notifications/{id}	Eliminar notificación
7.2 Endpoints de Preferencias
Método	Endpoint	Descripción
GET	/api/v1/me/notification-preferences	Obtener preferencias actuales
PATCH	/api/v1/me/notification-preferences	Actualizar preferencias
POST	/api/v1/me/push-token	Registrar token push (FCM)
DELETE	/api/v1/me/push-token/{token}	Eliminar token push
POST	/api/v1/unsubscribe/{token}	Unsubscribe vía link en email
7.3 Webhooks Salientes
Eventos disponibles para suscripción externa:
•	order.created, order.shipped, order.delivered, order.cancelled
•	payment.completed, payment.failed, refund.processed
•	product.created, product.updated, product.out_of_stock
•	producer.approved, producer.suspended
•	review.created, review.flagged
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Modelo datos, notification service base, templates Twig, queue system	Drupal Queue API
Sprint 2	Semana 3-4	Canal Email: integración SendGrid/SES, templates pedidos, tracking	Symfony Mailer
Sprint 3	Semana 5-6	Canal Push: FCM setup, service worker, notificaciones web/app	Firebase
Sprint 4	Semana 7-8	Canal SMS: integración Twilio, OTP, alertas críticas	Twilio
Sprint 5	Semana 9-10	Centro preferencias, notification center in-app, APIs usuario	Sprint 4
Sprint 6	Semana 11-12	Webhooks salientes, admin templates, analytics, QA	Sprint 5
--- Fin del Documento ---
59_AgroConecta_Notifications_System_v1.docx | Jaraba Impact Platform | Enero 2026
