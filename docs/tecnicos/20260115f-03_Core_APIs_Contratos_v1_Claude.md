
ESPECIFICACIÓN DE APIs
Y CONTRATOS DE INTEGRACIÓN

REST API + Webhooks + Integraciones Externas

JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Base URL:	https://api.jarabaimpact.com/v1
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Overview de la API	1
1.1 Autenticación	1
1.2 Rate Limiting	1
2. Endpoints Core	1
2.1 Tenants	1
GET /tenants/me - Response	1
2.2 Financial Transactions	1
GET /transactions - Query Parameters	1
Response	1
2.3 Metrics (FOC)	1
GET /metrics/current - Response	1
3. Webhooks Salientes	1
3.1 Configuración de Endpoints	1
3.2 Eventos Disponibles	1
3.3 Formato del Webhook	1
3.4 Verificación de Firma	1
4. Webhooks Entrantes	1
4.1 Stripe Connect	1
4.2 ActiveCampaign	1
5. Integración Make.com	1
5.1 Escenarios Predefinidos	1
5.2 Payload para Make.com	1
6. Códigos de Error	1
6.1 Formato de Error	1

 
1. Overview de la API
La API de Jaraba Impact Platform proporciona acceso programático a todas las funcionalidades del ecosistema. Diseñada con principios REST, soporta autenticación OAuth 2.0 y API Keys para diferentes casos de uso.
1.1 Autenticación
La API soporta dos métodos de autenticación:
API Key: Para integraciones server-to-server. Header: X-API-Key
OAuth 2.0: Para aplicaciones que actúan en nombre de usuarios. Bearer token.
 # Ejemplo de autenticación con API Key curl -X GET "https://api.jarabaimpact.com/v1/tenants/me" \   -H "X-API-Key: jrb_live_sk_xxxxxxxxxxxxxxxx" \   -H "Content-Type: application/json"  # Ejemplo con OAuth 2.0 Bearer Token curl -X GET "https://api.jarabaimpact.com/v1/tenants/me" \   -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..." \   -H "Content-Type: application/json"
1.2 Rate Limiting
Plan	Requests/min	Burst	Headers
Starter	60	100	X-RateLimit-*
Professional	300	500	X-RateLimit-*
Enterprise	1000	2000	X-RateLimit-*
 
2. Endpoints Core
2.1 Tenants
Método	Endpoint	Descripción
GET	/tenants/me	Obtener información del tenant actual
PATCH	/tenants/me	Actualizar configuración del tenant
GET	/tenants/me/users	Listar usuarios del tenant
POST	/tenants/me/users	Invitar usuario al tenant
GET	/tenants/me/metrics	Obtener métricas del tenant
GET /tenants/me - Response
 {   "data": {     "id": "tnt_abc123",     "uuid": "550e8400-e29b-41d4-a716-446655440000",     "name": "Artesanías El Molino",     "machine_name": "artesanias_molino",     "vertical": {       "id": "agro",       "name": "AgroConecta"     },     "plan_type": "professional",     "stripe_account_id": "acct_1234567890",     "stripe_onboarding_complete": true,     "platform_fee_percent": 5.00,     "status": "active",     "created_at": "2026-01-15T10:30:00Z",     "updated_at": "2026-01-15T14:22:00Z",     "settings": {       "currency": "EUR",       "timezone": "Europe/Madrid",       "locale": "es_ES"     }   } }
 
2.2 Financial Transactions
Método	Endpoint	Descripción
GET	/transactions	Listar transacciones (paginado)
GET	/transactions/{id}	Obtener transacción específica
GET	/transactions/summary	Resumen por período
GET /transactions - Query Parameters
Parámetro	Tipo	Default	Descripción
page	int	1	Número de página
per_page	int	25	Items por página (max 100)
type	string	all	income_recurring, income_one_time, cost_direct, etc.
date_from	ISO8601	-30 days	Fecha inicio (inclusive)
date_to	ISO8601	now	Fecha fin (inclusive)
motor	string	all	institutional, private, licenses
Response
 {   "data": [     {       "id": "txn_xyz789",       "uuid": "660e8400-e29b-41d4-a716-446655440001",       "amount": 150.00,       "currency": "EUR",       "net_amount": 142.35,       "platform_fee": 7.50,       "processor_fee": 4.65,       "transaction_type": "income_one_time",       "motor_type": "private",       "source_system": "stripe_connect",       "external_id": "pi_3Ox1234567890",       "timestamp": "2026-01-15T14:30:00Z",       "metadata": {         "product_id": "prod_123",         "order_id": "ord_456"       }     }   ],   "meta": {     "current_page": 1,     "per_page": 25,     "total_count": 156,     "total_pages": 7   },   "links": {     "self": "/v1/transactions?page=1",     "next": "/v1/transactions?page=2",     "last": "/v1/transactions?page=7"   } }
 
2.3 Metrics (FOC)
Método	Endpoint	Descripción
GET	/metrics/current	Métricas actuales del tenant
GET	/metrics/history	Histórico de métricas (snapshots)
GET	/metrics/forecast	Proyecciones a 3/6/12 meses
GET /metrics/current - Response
 {   "data": {     "snapshot_date": "2026-01-15",     "scope": "tenant",     "scope_id": "tnt_abc123",     "health_metrics": {       "mrr": 4500.00,       "arr": 54000.00,       "mrr_growth_mom": 0.08,       "arr_growth_yoy": 0.45     },     "retention_metrics": {       "revenue_churn_rate": 0.025,       "logo_churn_rate": 0.03,       "nrr": 1.12,       "grr": 0.92     },     "unit_economics": {       "cac": 125.00,       "ltv": 1800.00,       "ltv_cac_ratio": 14.4,       "cac_payback_months": 3.2     },     "operational": {       "gross_margin": 0.78,       "active_users": 156,       "gmv": 28500.00,       "application_fee_rate": 0.05     },     "benchmarks": {       "mrr_status": "healthy",       "churn_status": "excellent",       "ltv_cac_status": "excellent",       "margin_status": "healthy"     }   } }
 
3. Webhooks Salientes
Los webhooks permiten recibir notificaciones en tiempo real sobre eventos del ecosistema. Cada webhook incluye firma HMAC para verificación.
3.1 Configuración de Endpoints
Método	Endpoint	Descripción
GET	/webhooks/endpoints	Listar endpoints configurados
POST	/webhooks/endpoints	Crear nuevo endpoint
DELETE	/webhooks/endpoints/{id}	Eliminar endpoint
POST	/webhooks/endpoints/{id}/test	Enviar evento de prueba
3.2 Eventos Disponibles
Evento	Descripción y Campos del Payload
order.completed	order_id, customer, items[], total, payment_method, shipping_address
order.cancelled	order_id, cancellation_reason, refund_amount, cancelled_by
product.created	product_id, title, price, stock, categories[], images[]
product.updated	product_id, changed_fields{}, previous_values{}, new_values{}
user.registered	user_id, email, source, diagnostic_score, profile_type
diagnostic.completed	session_id, user_id (si registrado), score, profile, primary_gap, action
cart.abandoned	cart_id, user_id, items[], total, abandoned_at, recovery_url
tenant.onboarded	tenant_id, stripe_account_status, capabilities[], payout_enabled
alert.triggered	alert_type, severity, metric_name, threshold, current_value, playbook_id
3.3 Formato del Webhook
 POST https://your-server.com/webhook/jaraba HTTP/1.1 Content-Type: application/json X-Jaraba-Event: order.completed X-Jaraba-Signature: sha256=abc123def456... X-Jaraba-Timestamp: 1705323000 X-Jaraba-Delivery-ID: dlv_xyz789  {   "event": "order.completed",   "created_at": "2026-01-15T14:30:00Z",   "tenant_id": "tnt_abc123",   "data": {     "order_id": "ord_456",     "customer": {       "id": "cus_789",       "email": "cliente@example.com",       "name": "María García"     },     "items": [       {         "product_id": "prod_123",         "title": "Aceite de Oliva Virgen Extra",         "quantity": 2,         "unit_price": 15.00,         "total": 30.00       }     ],     "subtotal": 30.00,     "shipping": 5.00,     "total": 35.00,     "currency": "EUR",     "payment_method": "card",     "stripe_payment_id": "pi_3Ox1234567890"   } }
3.4 Verificación de Firma
 <?php // Verificación de webhook en servidor receptor  function verifyWebhookSignature(Request $request, string $secret): bool {     $signature = $request->headers->get('X-Jaraba-Signature');     $timestamp = $request->headers->get('X-Jaraba-Timestamp');     $payload = $request->getContent();          // Verificar que el timestamp no sea muy antiguo (prevenir replay attacks)     if (abs(time() - (int)$timestamp) > 300) {         return false;     }          // Calcular firma esperada     $expectedSignature = 'sha256=' . hash_hmac(         'sha256',         $timestamp . '.' . $payload,         $secret     );          return hash_equals($expectedSignature, $signature); }
 
4. Webhooks Entrantes
4.1 Stripe Connect
Endpoint: POST /stripe/webhook
Evento Stripe	Acción en Jaraba
payment_intent.succeeded	Crear financial_transaction, emitir order.completed, actualizar stock
payment_intent.payment_failed	Registrar intento fallido, trigger email recovery
charge.refunded	Crear transacción compensatoria (refund), actualizar order status
account.updated	Actualizar stripe_onboarding_complete, capabilities del tenant
invoice.paid	Crear transacción recurrente para suscripciones
customer.subscription.deleted	Marcar churn, trigger churn prevention playbook
4.2 ActiveCampaign
Endpoint: POST /activecampaign/webhook
Evento AC	Acción en Jaraba
contact_tag_added	Sincronizar tags con user profile, actualizar segmentación
deal_stage_changed	Actualizar lead status, trigger content personalizado
campaign_sent	Registrar coste de campaña para CAC calculation
 
5. Integración Make.com
Make.com actúa como hub de integración para conectar con marketplaces y servicios externos sin acoplar el core de la plataforma.
5.1 Escenarios Predefinidos
Escenario	Trigger	Acciones
sync_amazon	product.created/updated	Crear/actualizar listing en Amazon Seller Central
sync_ebay	product.created/updated	Crear/actualizar listing en eBay
sync_meta_catalog	product.created/updated	Actualizar Facebook/Instagram Shop
order_notification	order.completed	Email al productor, SMS opcional, Slack (si configurado)
cart_recovery	cart.abandoned	Secuencia de 3 emails de recuperación en AC
review_request	order.completed + 7 días	Email solicitando reseña del producto
5.2 Payload para Make.com
 // Webhook enviado a Make.com para product.updated {   "event": "product.updated",   "timestamp": "2026-01-15T14:30:00Z",   "tenant": {     "id": "tnt_abc123",     "name": "Artesanías El Molino",     "vertical": "agro"   },   "product": {     "id": "prod_123",     "sku": "AOVE-500ML-2026",     "title": "Aceite de Oliva Virgen Extra 500ml",     "description": "Aceite de primera prensada en frío...",     "price": 15.00,     "compare_at_price": 18.00,     "currency": "EUR",     "stock_quantity": 150,     "weight": 0.6,     "weight_unit": "kg",     "images": [       "https://cdn.jarabaimpact.com/products/aove-500ml-1.jpg",       "https://cdn.jarabaimpact.com/products/aove-500ml-2.jpg"     ],     "categories": ["aceites", "gourmet", "ecologico"],     "attributes": {       "origen": "Córdoba",       "certificacion": "Ecológico EU"     },     "seo": {       "meta_title": "Aceite de Oliva Virgen Extra 500ml | Artesanías El Molino",       "meta_description": "Aceite de primera prensada...",       "answer_capsule": "Este aceite de oliva virgen extra..."     }   },   "changed_fields": ["price", "stock_quantity"],   "previous_values": {     "price": 14.00,     "stock_quantity": 200   } }
 
6. Códigos de Error
Código	Nombre	Descripción y Resolución
400	Bad Request	Parámetros inválidos. Ver campo 'errors' en response.
401	Unauthorized	API Key inválida o expirada. Regenerar en dashboard.
403	Forbidden	Sin permisos para este recurso/acción.
404	Not Found	Recurso no existe o no pertenece al tenant.
409	Conflict	Conflicto de estado (ej: orden ya cancelada).
422	Unprocessable	Validación de negocio fallida. Ver campo 'errors'.
429	Too Many Requests	Rate limit excedido. Ver header Retry-After.
500	Internal Error	Error del servidor. Incluir request_id al reportar.
6.1 Formato de Error
 {   "error": {     "code": "validation_error",     "message": "Los datos proporcionados no son válidos",     "request_id": "req_abc123xyz",     "errors": [       {         "field": "amount",         "code": "invalid_type",         "message": "El campo amount debe ser un número decimal"       },       {         "field": "currency",         "code": "invalid_value",         "message": "Moneda no soportada. Use EUR, USD o GBP"       }     ],     "documentation_url": "https://docs.jarabaimpact.com/errors/validation_error"   } }

FIN DEL DOCUMENTO
APIs y Contratos de Integración v1.0 | Jaraba Impact Platform | Enero 2026
