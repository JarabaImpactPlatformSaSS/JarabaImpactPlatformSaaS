GUÍA DE INTEGRACIÓN API
Documentación para Desarrolladores e Integradores
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	61_AgroConecta_API_Integration_Guide
API Version:	v1
 
1. Introducción
Esta guía proporciona toda la información necesaria para integrar sistemas externos con la API de AgroConecta. Está dirigida a desarrolladores, partners tecnológicos y equipos que necesiten conectar ERPs, CRMs, sistemas de logística u otras aplicaciones con el marketplace.
1.1 Casos de Uso de Integración
Integrador	Caso de Uso	APIs Principales
ERP Productor	Sincronizar catálogo, stock, pedidos, facturación	Products, Orders, Inventory
CRM	Sincronizar clientes, historial compras, segmentos	Customers, Orders
Logística	Recibir pedidos, actualizar tracking, notificar entregas	Orders, Shipments, Webhooks
Contabilidad	Importar ventas, comisiones, payouts para contabilizar	Finance, Reports
Marketing	Exportar audiencias, sincronizar campañas	Customers, Analytics
App Móvil	Consumir todas las funcionalidades del marketplace	Todas
1.2 Base URL y Versionado
# Producción
https://api.agroconecta.es/v1

# Sandbox (testing)
https://sandbox-api.agroconecta.es/v1
 
2. Autenticación
2.1 OAuth 2.0
La API utiliza OAuth 2.0 con diferentes flujos según el caso de uso:
Grant Type	Uso	Tokens
client_credentials	Server-to-server (ERPs, backends)	access_token (1h)
authorization_code	Apps que actúan en nombre de usuario	access + refresh (30d)
password	Apps propias (mobile), requiere aprobación	access + refresh
2.2 Obtener Token (Client Credentials)
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
&client_id=your_client_id
&client_secret=your_client_secret
&scope=products:read orders:read orders:write
// Response
{
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "products:read orders:read orders:write"
}
2.3 Usar Token en Requests
GET /v1/products
Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
Accept: application/json
2.4 Scopes Disponibles
Scope	Permisos
products:read	Leer catálogo de productos
products:write	Crear/actualizar productos (solo productor)
orders:read	Leer pedidos
orders:write	Actualizar estado de pedidos
inventory:write	Actualizar stock
customers:read	Leer datos de clientes
shipments:read	Leer información de envíos
shipments:write	Actualizar tracking
webhooks:manage	Gestionar suscripciones a webhooks
finance:read	Leer datos financieros (solo admin/productor)
 
3. Estructura de Respuestas
3.1 Formato JSON:API
Todas las respuestas siguen la especificación JSON:API para consistencia:
// Respuesta exitosa (single resource)
{
  "data": {
    "type": "product",
    "id": "prod_abc123",
    "attributes": {
      "title": "AOVE Picual Premium 500ml",
      "price": "12.50",
      "currency": "EUR",
      "stock": 150
    },
    "relationships": {
      "producer": { "data": { "type": "producer", "id": "prd_xyz789" } }
    }
  }
}
3.2 Paginación
// Respuesta con colección paginada
{
  "data": [ ... ],
  "meta": {
    "total": 245,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 13
  },
  "links": {
    "self": "/v1/products?page=1",
    "next": "/v1/products?page=2",
    "last": "/v1/products?page=13"
  }
}
3.3 Códigos de Error
Código	Nombre	Descripción
400	Bad Request	Parámetros inválidos o faltantes
401	Unauthorized	Token inválido o expirado
403	Forbidden	Sin permisos para este recurso
404	Not Found	Recurso no encontrado
409	Conflict	Conflicto (ej: SKU duplicado)
422	Unprocessable	Validación fallida
429	Too Many Requests	Rate limit excedido
500	Server Error	Error interno del servidor
 
4. Endpoints Principales
4.1 Productos
Método	Endpoint	Descripción
GET	/v1/products	Listar productos (paginado, filtros)
GET	/v1/products/{id}	Detalle de producto
POST	/v1/products	Crear producto (productor)
PATCH	/v1/products/{id}	Actualizar producto
DELETE	/v1/products/{id}	Eliminar producto
PATCH	/v1/products/{id}/stock	Actualizar stock
GET	/v1/categories	Listar categorías
GET	/v1/categories/{id}/products	Productos por categoría
4.2 Pedidos
Método	Endpoint	Descripción
GET	/v1/orders	Listar pedidos
GET	/v1/orders/{id}	Detalle de pedido
POST	/v1/orders/{id}/confirm	Confirmar pedido (productor)
POST	/v1/orders/{id}/ship	Marcar como enviado
POST	/v1/orders/{id}/cancel	Cancelar pedido
GET	/v1/orders/{id}/shipments	Envíos del pedido
PATCH	/v1/shipments/{id}	Actualizar tracking
4.3 Inventario
Método	Endpoint	Descripción
GET	/v1/inventory	Listar stock de todos los productos
PATCH	/v1/inventory/{product_id}	Actualizar stock de un producto
POST	/v1/inventory/bulk	Actualizar stock masivo (array)
GET	/v1/inventory/low-stock	Productos con stock bajo
 
5. Webhooks
Los webhooks permiten recibir notificaciones en tiempo real cuando ocurren eventos en AgroConecta.
5.1 Eventos Disponibles
Evento	Descripción
order.created	Nuevo pedido recibido
order.confirmed	Pedido confirmado por productor
order.shipped	Pedido marcado como enviado
order.delivered	Pedido entregado al cliente
order.cancelled	Pedido cancelado
payment.completed	Pago procesado correctamente
payment.failed	Fallo en el pago
refund.processed	Reembolso procesado
product.created	Nuevo producto creado
product.updated	Producto actualizado
inventory.low	Stock por debajo del umbral
inventory.out	Producto agotado
review.created	Nueva reseña recibida
5.2 Payload del Webhook
POST https://your-server.com/webhooks/agroconecta
Content-Type: application/json
X-AgroConecta-Signature: sha256=abc123...

{
  "id": "evt_abc123",
  "type": "order.created",
  "created_at": "2026-01-16T10:30:00Z",
  "data": {
    "order_id": "ord_xyz789",
    "order_number": "AC-10234",
    "total": "67.50",
    "currency": "EUR",
    "items": [ ... ]
  }
}
5.3 Verificar Firma
// Verificar que el webhook viene de AgroConecta
const crypto = require('crypto');

function verifySignature(payload, signature, secret) {
  const expected = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');
  return crypto.timingSafeEqual(
    Buffer.from(signature), Buffer.from(expected)
  );
}
 
6. Rate Limits y Best Practices
6.1 Límites de Peticiones
Plan	Límite	Ventana
Free / Sandbox	100 req	Por minuto
Basic	500 req	Por minuto
Pro	2,000 req	Por minuto
Enterprise	10,000 req	Por minuto (negociable)
6.2 Headers de Rate Limit
X-RateLimit-Limit: 500        // Límite total
X-RateLimit-Remaining: 423    // Peticiones restantes
X-RateLimit-Reset: 1705405200 // Timestamp reset (Unix)
6.3 Best Practices
•	Cachear respuestas: Productos y categorías cambian poco, cachear 5-15 min
•	Usar webhooks: En lugar de polling para cambios de estado
•	Bulk operations: Usar endpoints /bulk cuando disponibles
•	Exponential backoff: Reintentos con delay creciente en 429
•	Compresión: Aceptar gzip para respuestas grandes
•	Campos específicos: Usar ?fields= para pedir solo lo necesario
•	Idempotencia: Usar Idempotency-Key en POSTs críticos
 
7. SDKs y Ejemplos
7.1 SDKs Oficiales
Lenguaje	Package	Instalación
PHP	agroconecta/php-sdk	composer require
JavaScript	@agroconecta/js-sdk	npm install
Python	agroconecta-python	pip install
Ruby	agroconecta	gem install
7.2 Ejemplo: Sincronizar Stock (JavaScript)
const AgroConecta = require('@agroconecta/js-sdk');

const client = new AgroConecta({
  clientId: process.env.AGROCONECTA_CLIENT_ID,
  clientSecret: process.env.AGROCONECTA_CLIENT_SECRET,
  sandbox: false
});

// Actualizar stock de múltiples productos
async function syncStock(updates) {
  try {
    const result = await client.inventory.bulkUpdate(updates);
    console.log(`Updated ${result.updated} products`);
  } catch (error) {
    console.error('Sync failed:', error.message);
  }
}

// Ejemplo de uso
syncStock([
  { sku: 'AOVE-500', stock: 150 },
  { sku: 'QUESO-MC', stock: 45 },
  { sku: 'MIEL-ROM', stock: 80 }
]);
7.3 Postman Collection
Disponible colección Postman con todos los endpoints para testing:
•	URL: https://docs.agroconecta.es/postman-collection.json
•	Variables: base_url, client_id, client_secret, access_token
•	Environments: Sandbox, Production
 
8. Soporte y Recursos
8.1 Documentación
Recurso	URL
Documentación API	https://docs.agroconecta.es/api
OpenAPI Spec	https://api.agroconecta.es/v1/openapi.json
Changelog	https://docs.agroconecta.es/changelog
Status Page	https://status.agroconecta.es
GitHub SDKs	https://github.com/agroconecta
8.2 Contacto
•	Email técnico: api@agroconecta.es
•	Portal partners: https://partners.agroconecta.es
•	Slack community: Invitación en portal partners
•	SLA producción: 99.9% uptime, soporte 24/7 (Enterprise)
8.3 Proceso de Onboarding
1.	Registro: Crear cuenta en partners.agroconecta.es
2.	Credenciales: Obtener client_id y client_secret sandbox
3.	Desarrollo: Implementar integración en sandbox
4.	Testing: Validar todos los flujos con datos de prueba
5.	Review: Solicitar revisión técnica del equipo
6.	Producción: Recibir credenciales de producción
7.	Go-Live: Activar integración y monitorear
--- Fin del Documento ---
61_AgroConecta_API_Integration_Guide_v1.docx | Jaraba Impact Platform | Enero 2026
