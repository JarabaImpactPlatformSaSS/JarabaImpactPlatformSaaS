API INTEGRATION GUIDE
Guía de Integraciones Externas y API Pública
REST API + Webhooks + OAuth2 + SDK
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	99_ServiciosConecta_API_Integration_Guide
API Version:	v1 (Stable)
Base URL:	https://api.jaraba.es/v1/servicios
Auth:	OAuth2 Bearer Token / API Key
 
1. Resumen Ejecutivo
Esta guía documenta la API REST pública de ServiciosConecta para desarrolladores externos y partners. Permite integrar sistemas de terceros (CRM, ERP, contabilidad) con la plataforma, automatizar flujos de trabajo, y construir aplicaciones que consuman datos de casos, citas, facturas y documentos.
La API sigue principios RESTful, utiliza JSON para request/response, soporta paginación y filtrado, proporciona webhooks para eventos en tiempo real, y está protegida por OAuth2. Todas las operaciones están sujetas a rate limiting y requieren autenticación.
1.1 Casos de Uso de Integraciones
Integración	Descripción	APIs Utilizadas
CRM (Salesforce, HubSpot)	Sincronizar clientes y expedientes	Clients, Cases
Contabilidad (A3, Sage)	Exportar facturas y cobros	Invoices, Payments
Calendario (Google, Outlook)	Sincronizar citas bidireccional	Bookings, Calendar
Gestión documental	Subir/descargar documentos	Documents
Web del despacho	Formulario de contacto, reserva citas	Inquiries, Bookings
App móvil propia	App personalizada para clientes	Todas las APIs
Firma electrónica	Integrar otros proveedores de firma	Documents, Webhooks

2. Autenticación
2.1 OAuth2 (Aplicaciones)
Para aplicaciones que actúan en nombre de usuarios, usar OAuth2 Authorization Code Flow:
1. Redirigir usuario a autorización:
GET https://api.jaraba.es/oauth/authorize
  ?client_id={client_id}
  &redirect_uri={redirect_uri}
  &response_type=code
  &scope=read:cases write:bookings
  &state={random_state}

2. Intercambiar code por token:
POST https://api.jaraba.es/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code={authorization_code}
&client_id={client_id}
&client_secret={client_secret}
&redirect_uri={redirect_uri}

3. Respuesta:
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "def502003b7c0...",
  "scope": "read:cases write:bookings"
}

2.2 API Key (Server-to-Server)
Para integraciones server-to-server sin interacción de usuario:
GET https://api.jaraba.es/v1/servicios/cases
Authorization: Bearer sk_live_abc123xyz789...
X-Tenant-ID: tenant_abc123

2.3 Scopes Disponibles
Scope	Permisos
read:cases	Leer expedientes y actividades
write:cases	Crear/modificar expedientes
read:clients	Leer datos de clientes
write:clients	Crear/modificar clientes
read:bookings	Leer citas y disponibilidad
write:bookings	Crear/cancelar citas
read:invoices	Leer facturas y cobros
write:invoices	Crear/modificar facturas
read:documents	Leer/descargar documentos
write:documents	Subir documentos
webhooks	Gestionar webhooks

 
3. Endpoints Principales
3.1 Cases (Expedientes)
Método	Endpoint	Descripción
GET	/cases	Listar expedientes (paginado, filtrable)
POST	/cases	Crear nuevo expediente
GET	/cases/{uuid}	Obtener detalle de expediente
PATCH	/cases/{uuid}	Actualizar expediente
POST	/cases/{uuid}/close	Cerrar expediente
GET	/cases/{uuid}/activities	Historial de actividad
POST	/cases/{uuid}/notes	Añadir nota interna

// Ejemplo: Crear expediente
POST /v1/servicios/cases
{
  "client_id": "cli_abc123",
  "category": "civil",
  "title": "Reclamación de cantidad",
  "description": "Deuda de 5.000€ por servicios prestados",
  "assigned_provider_id": "usr_xyz789"
}

3.2 Bookings (Citas)
Método	Endpoint	Descripción
GET	/bookings	Listar citas
POST	/bookings	Crear cita
GET	/bookings/{uuid}	Detalle de cita
POST	/bookings/{uuid}/cancel	Cancelar cita
POST	/bookings/{uuid}/reschedule	Reprogramar cita
GET	/availability	Consultar disponibilidad
GET	/availability/slots	Slots disponibles por fecha

3.3 Invoices (Facturas)
Método	Endpoint	Descripción
GET	/invoices	Listar facturas
POST	/invoices	Crear factura
GET	/invoices/{uuid}	Detalle de factura
POST	/invoices/{uuid}/send	Enviar factura al cliente
GET	/invoices/{uuid}/pdf	Descargar PDF
POST	/invoices/{uuid}/mark-paid	Marcar como pagada

 
4. Webhooks
Los webhooks notifican eventos en tiempo real a URLs configuradas por el integrador:
4.1 Eventos Disponibles
Evento	Descripción
case.created	Nuevo expediente creado
case.updated	Expediente actualizado
case.closed	Expediente cerrado
booking.created	Nueva cita creada
booking.confirmed	Cita confirmada por cliente
booking.cancelled	Cita cancelada
invoice.created	Nueva factura creada
invoice.paid	Factura cobrada
document.uploaded	Documento subido por cliente
document.signed	Documento firmado
inquiry.received	Nueva consulta recibida
review.created	Nueva reseña recibida

4.2 Estructura del Payload
POST {your_webhook_url}
Content-Type: application/json
X-Webhook-Signature: sha256=abc123...
X-Webhook-ID: wh_evt_abc123

{
  "id": "wh_evt_abc123xyz",
  "type": "invoice.paid",
  "created": "2026-01-17T14:30:00Z",
  "tenant_id": "tenant_abc123",
  "data": {
    "invoice_id": "inv_xyz789",
    "invoice_number": "FAC-2026-0042",
    "amount": 125000,
    "currency": "EUR",
    "client_id": "cli_abc123",
    "paid_at": "2026-01-17T14:29:55Z"
  }
}

4.3 Verificación de Firma
// PHP ejemplo
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$expected = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

 
5. Rate Limiting y Errores
5.1 Límites de Rate
Plan	Requests/minuto	Requests/día
Free/Trial	60	1,000
Professional	300	10,000
Enterprise	1,000	100,000

Headers de respuesta con información de rate limiting:
X-RateLimit-Limit: 300
X-RateLimit-Remaining: 295
X-RateLimit-Reset: 1705502400

5.2 Códigos de Error HTTP
Código	Nombre	Descripción
400	Bad Request	Parámetros inválidos o malformados
401	Unauthorized	Token inválido o expirado
403	Forbidden	Sin permisos para este recurso
404	Not Found	Recurso no encontrado
422	Unprocessable	Errores de validación
429	Rate Limited	Demasiadas solicitudes
500	Server Error	Error interno del servidor

6. Integraciones Nativas
ServiciosConecta incluye integraciones preconfiguradas con:
Servicio	Uso	Configuración
Google Calendar	Sincronización bidireccional de citas	OAuth2 en Config > Integraciones
Google Drive	Backup automático de documentos	OAuth2 en Config > Integraciones
Stripe	Cobros online, facturación	API Keys en Config > Pagos
SendGrid	Envío de emails transaccionales	API Key en Config > Email
Twilio	SMS y WhatsApp Business	Account SID + Token
Firebase	Push notifications móvil	Server Key en Config > Push
Zapier	Conectar con 5000+ apps	Webhooks automáticos

7. SDKs y Librerías
•	PHP SDK: composer require jaraba/servicios-php
•	JavaScript/Node: npm install @jaraba/servicios-js
•	Python: pip install jaraba-servicios
•	Postman Collection: Disponible en docs.jaraba.es/postman
// Ejemplo PHP SDK
use Jaraba\Servicios\Client;

$client = new Client('sk_live_abc123...');

// Crear expediente
$case = $client->cases->create([
    'client_id' => 'cli_abc123',
    'category' => 'civil',
    'title' => 'Reclamación de cantidad'
]);

// Listar facturas pendientes
$invoices = $client->invoices->all(['status' => 'pending']);


--- Fin del Documento ---
