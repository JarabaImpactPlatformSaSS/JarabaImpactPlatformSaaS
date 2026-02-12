GUÍA DE INTEGRACIÓN API
Documentación para Desarrolladores e Integradores
Vertical ComercioConecta - JARABA IMPACT PLATFORM
API Version 1.0

Campo	Valor
Versión API:	v1.0
Base URL:	https://api.comercioconecta.es/v1
Sandbox URL:	https://sandbox-api.comercioconecta.es/v1
Documento:	79_ComercioConecta_API_Integration_Guide
Última actualización:	Enero 2026
Contacto:	developers@comercioconecta.es
 
1. Introducción
Esta guía proporciona toda la información necesaria para integrar sistemas externos con la plataforma ComercioConecta. Cubre autenticación, endpoints disponibles, webhooks, límites de uso, manejo de errores y ejemplos de código.
1.1 Casos de Uso de Integración
Caso de Uso	Descripción	APIs Principales
Sincronización POS	Sincronizar inventario y ventas con TPV físico	Products, Orders, Stock
ERP Integration	Conectar con sistemas de gestión empresarial	Orders, Products, Finance
Marketplace Sync	Publicar productos en otros marketplaces	Products, Stock, Pricing
Shipping Providers	Integrar transportistas personalizados	Shipments, Webhooks
Analytics Tools	Exportar datos a herramientas de BI	Analytics, Reports
CRM Integration	Sincronizar clientes y pedidos con CRM	Customers, Orders
Accounting Software	Integrar con software de contabilidad	Orders, Invoices, Finance
1.2 Entornos Disponibles
Entorno	URL Base	Propósito
Production	https://api.comercioconecta.es/v1	Datos reales, uso en producción
Sandbox	https://sandbox-api.comercioconecta.es/v1	Pruebas, datos de test
Staging	https://staging-api.comercioconecta.es/v1	Pre-producción (bajo solicitud)
 
2. Autenticación
2.1 OAuth 2.0
La API utiliza OAuth 2.0 con Client Credentials para autenticación server-to-server.
# Paso 1: Obtener Access Token  curl -X POST https://api.comercioconecta.es/oauth/token \   -H "Content-Type: application/x-www-form-urlencoded" \   -d "grant_type=client_credentials" \   -d "client_id=YOUR_CLIENT_ID" \   -d "client_secret=YOUR_CLIENT_SECRET" \   -d "scope=products orders customers"  # Respuesta {   "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",   "token_type": "Bearer",   "expires_in": 3600,   "scope": "products orders customers" }
# Paso 2: Usar el token en requests  curl -X GET https://api.comercioconecta.es/v1/products \   -H "Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..." \   -H "Content-Type: application/json"
2.2 API Keys (Simplificado)
Para integraciones simples, también soportamos API Keys en el header.
# Autenticación con API Key  curl -X GET https://api.comercioconecta.es/v1/products \   -H "X-API-Key: sk_live_abc123xyz789" \   -H "Content-Type: application/json"  # Tipos de API Keys: # sk_live_*  - Producción (datos reales) # sk_test_*  - Sandbox (datos de prueba)
2.3 Scopes Disponibles
Scope	Permisos	Endpoints
products:read	Leer catálogo de productos	GET /products/*
products:write	Crear/actualizar productos	POST, PATCH /products/*
orders:read	Leer pedidos	GET /orders/*
orders:write	Actualizar estado de pedidos	PATCH /orders/*
stock:read	Leer niveles de stock	GET /stock/*
stock:write	Actualizar stock	PATCH /stock/*
customers:read	Leer datos de clientes	GET /customers/*
finance:read	Leer datos financieros	GET /finance/*
webhooks:manage	Gestionar webhooks	*/webhooks/*
 
3. Endpoints Principales
3.1 Productos
Método	Endpoint	Descripción	Scope
GET	/products	Listar productos	products:read
GET	/products/{id}	Obtener producto	products:read
POST	/products	Crear producto	products:write
PATCH	/products/{id}	Actualizar producto	products:write
DELETE	/products/{id}	Eliminar producto	products:write
GET	/products/{id}/variations	Listar variaciones	products:read
POST	/products/{id}/variations	Crear variación	products:write
POST	/products/{id}/images	Subir imagen	products:write
# GET /products - Listar productos # Query params: page, per_page, status, category_id, sku, barcode, updated_since  curl -X GET "https://api.comercioconecta.es/v1/products?page=1&per_page=50&status=active" \   -H "Authorization: Bearer {token}"  # Response {   "data": [     {       "id": 12345,       "sku": "CAMISETA-001",       "barcode": "8412345678901",       "title": "Camiseta Premium Algodón",       "description": "Camiseta 100% algodón orgánico...",       "status": "active",       "price": 29.95,       "sale_price": 24.95,       "cost": 12.00,       "stock_quantity": 150,       "category_id": 45,       "brand": "MarcaLocal",       "weight": 0.25,       "images": [...],       "variations": [...],       "created_at": "2026-01-15T10:30:00Z",       "updated_at": "2026-01-16T14:20:00Z"     }   ],   "meta": {     "current_page": 1,     "per_page": 50,     "total": 234,     "total_pages": 5   } }
 
3.2 Pedidos
Método	Endpoint	Descripción	Scope
GET	/orders	Listar pedidos	orders:read
GET	/orders/{id}	Obtener pedido	orders:read
PATCH	/orders/{id}	Actualizar pedido	orders:write
POST	/orders/{id}/fulfill	Marcar como preparado	orders:write
POST	/orders/{id}/ship	Marcar como enviado	orders:write
POST	/orders/{id}/cancel	Cancelar pedido	orders:write
GET	/orders/{id}/shipments	Obtener envíos	orders:read
POST	/orders/{id}/shipments	Crear envío	orders:write
# GET /orders/{id} - Obtener detalle de pedido  {   "id": 98765,   "order_number": "ORD-2026-098765",   "status": "processing",   "payment_status": "paid",   "fulfillment_type": "shipping",   "customer": {     "id": 5432,     "name": "María García",     "email": "maria@example.com",     "phone": "+34612345678"   },   "shipping_address": {     "street": "Calle Mayor 15, 2ºB",     "city": "Sevilla",     "province": "Sevilla",     "postal_code": "41001",     "country": "ES"   },   "items": [     {       "id": 1,       "product_id": 12345,       "sku": "CAMISETA-001-M-AZUL",       "title": "Camiseta Premium - M - Azul",       "quantity": 2,       "unit_price": 24.95,       "total": 49.90     }   ],   "subtotal": 49.90,   "shipping_cost": 4.95,   "discount": 5.00,   "tax": 11.52,   "total": 61.37,   "notes": "Dejar en portería si no estoy",   "created_at": "2026-01-17T09:15:00Z" }
 
3.3 Stock
Método	Endpoint	Descripción	Scope
GET	/stock	Listar niveles de stock	stock:read
GET	/stock/{product_id}	Stock de un producto	stock:read
PATCH	/stock/{product_id}	Actualizar stock	stock:write
POST	/stock/bulk-update	Actualización masiva	stock:write
GET	/stock/low	Productos con stock bajo	stock:read
GET	/stock/movements	Historial de movimientos	stock:read
# PATCH /stock/{product_id} - Actualizar stock  curl -X PATCH "https://api.comercioconecta.es/v1/stock/12345" \   -H "Authorization: Bearer {token}" \   -H "Content-Type: application/json" \   -d '{     "quantity": 150,     "adjustment_type": "set",     "reason": "inventory_count",     "note": "Recuento físico semanal"   }'  # adjustment_type: "set" (valor absoluto), "increment", "decrement" # reason: "sale", "return", "inventory_count", "damage", "transfer", "purchase"
# POST /stock/bulk-update - Actualización masiva  curl -X POST "https://api.comercioconecta.es/v1/stock/bulk-update" \   -H "Authorization: Bearer {token}" \   -H "Content-Type: application/json" \   -d '{     "updates": [       { "sku": "CAMISETA-001", "quantity": 150 },       { "sku": "CAMISETA-002", "quantity": 80 },       { "sku": "PANTALON-001", "quantity": 45 }     ],     "adjustment_type": "set",     "reason": "pos_sync"   }'  # Response {   "success": 3,   "failed": 0,   "errors": [] }
 
3.4 Envíos
Método	Endpoint	Descripción	Scope
POST	/orders/{id}/shipments	Crear envío	orders:write
GET	/shipments/{id}	Obtener envío	orders:read
GET	/shipments/{id}/tracking	Info de tracking	orders:read
POST	/shipments/{id}/label	Generar etiqueta	orders:write
GET	/carriers	Listar carriers disponibles	orders:read
# POST /orders/{order_id}/shipments - Crear envío  curl -X POST "https://api.comercioconecta.es/v1/orders/98765/shipments" \   -H "Authorization: Bearer {token}" \   -H "Content-Type: application/json" \   -d '{     "carrier": "mrw",     "service": "standard",     "tracking_number": "MRW123456789ES",     "items": [       { "order_item_id": 1, "quantity": 2 }     ],     "weight": 0.5,     "dimensions": {       "length": 30,       "width": 20,       "height": 5     }   }'  # Response {   "id": 54321,   "order_id": 98765,   "carrier": "mrw",   "tracking_number": "MRW123456789ES",   "tracking_url": "https://www.mrw.es/seguimiento?num=MRW123456789ES",   "label_url": "https://api.comercioconecta.es/v1/shipments/54321/label.pdf",   "status": "pending",   "created_at": "2026-01-17T10:00:00Z" }
 
4. Webhooks
Los webhooks permiten recibir notificaciones en tiempo real cuando ocurren eventos en la plataforma.
4.1 Eventos Disponibles
Evento	Descripción	Payload
order.created	Nuevo pedido creado	Order completo
order.paid	Pedido pagado	Order + payment
order.cancelled	Pedido cancelado	Order + reason
order.fulfilled	Pedido preparado	Order
order.shipped	Pedido enviado	Order + shipment
order.delivered	Pedido entregado	Order + shipment
product.created	Producto creado	Product
product.updated	Producto actualizado	Product + changes
product.deleted	Producto eliminado	Product ID
stock.low	Stock bajo umbral	Product + quantity
stock.out	Sin stock	Product
review.created	Nueva reseña	Review
refund.created	Reembolso procesado	Refund + order
4.2 Configurar Webhook
# POST /webhooks - Registrar webhook  curl -X POST "https://api.comercioconecta.es/v1/webhooks" \   -H "Authorization: Bearer {token}" \   -H "Content-Type: application/json" \   -d '{     "url": "https://tu-servidor.com/webhooks/comercioconecta",     "events": ["order.created", "order.paid", "stock.low"],     "secret": "whsec_tu_secreto_seguro"   }'  # Response {   "id": "wh_abc123",   "url": "https://tu-servidor.com/webhooks/comercioconecta",   "events": ["order.created", "order.paid", "stock.low"],   "status": "active",   "created_at": "2026-01-17T10:00:00Z" }
4.3 Payload de Webhook
# Ejemplo: order.created  POST https://tu-servidor.com/webhooks/comercioconecta Content-Type: application/json X-Webhook-Signature: sha256=5d41402abc4b2a76b9719d911017c592... X-Webhook-ID: evt_123456789 X-Webhook-Timestamp: 1705485600  {   "id": "evt_123456789",   "type": "order.created",   "created_at": "2026-01-17T10:00:00Z",   "data": {     "object": {       "id": 98765,       "order_number": "ORD-2026-098765",       "status": "confirmed",       "total": 61.37,       "customer": {...},       "items": [...]     }   } }
4.4 Verificar Firma
// Node.js - Verificar firma del webhook  const crypto = require('crypto');  function verifyWebhookSignature(payload, signature, secret) {   const expectedSignature = crypto     .createHmac('sha256', secret)     .update(payload, 'utf8')     .digest('hex');      const providedSignature = signature.replace('sha256=', '');      return crypto.timingSafeEqual(     Buffer.from(expectedSignature),     Buffer.from(providedSignature)   ); }  // Uso app.post('/webhooks/comercioconecta', (req, res) => {   const signature = req.headers['x-webhook-signature'];   const isValid = verifyWebhookSignature(     JSON.stringify(req.body),     signature,     process.env.WEBHOOK_SECRET   );      if (!isValid) {     return res.status(401).send('Invalid signature');   }      // Procesar evento...   res.status(200).send('OK'); });
 
5. Rate Limits y Cuotas
5.1 Límites por Plan
Plan	Requests/min	Requests/día	Webhooks	Burst
Free	60	10,000	3	10 req/s
Basic	300	100,000	10	50 req/s
Premium	1,000	500,000	25	100 req/s
Enterprise	5,000	Ilimitado	100	500 req/s
5.2 Headers de Rate Limit
# Headers en cada respuesta:  X-RateLimit-Limit: 300           # Límite por minuto X-RateLimit-Remaining: 295       # Requests restantes X-RateLimit-Reset: 1705485660    # Timestamp de reset (Unix)  # Si excedes el límite: HTTP/1.1 429 Too Many Requests Retry-After: 30                  # Segundos hasta poder reintentar  {   "error": {     "code": "rate_limit_exceeded",     "message": "Has excedido el límite de requests. Espera 30 segundos."   } }
5.3 Mejores Prácticas
• Implementar retry con exponential backoff
• Usar bulk endpoints cuando sea posible (/stock/bulk-update)
• Cachear respuestas que no cambian frecuentemente
• Usar webhooks en lugar de polling
• Respetar el header Retry-After en respuestas 429
 
6. Manejo de Errores
6.1 Códigos HTTP
Código	Significado	Acción Recomendada
200	OK - Éxito	Procesar respuesta
201	Created - Recurso creado	Procesar respuesta
400	Bad Request - Error en request	Revisar parámetros enviados
401	Unauthorized - No autenticado	Verificar token/API key
403	Forbidden - Sin permisos	Verificar scopes
404	Not Found - No existe	Verificar ID/endpoint
409	Conflict - Conflicto	Recurso ya existe o estado inválido
422	Unprocessable - Validación fallida	Revisar datos enviados
429	Too Many Requests	Esperar según Retry-After
500	Server Error	Reintentar con backoff
503	Service Unavailable	Reintentar más tarde
6.2 Formato de Error
# Estructura estándar de error  {   "error": {     "code": "validation_error",     "message": "Los datos enviados no son válidos",     "details": [       {         "field": "price",         "message": "El precio debe ser mayor que 0"       },       {         "field": "sku",         "message": "El SKU ya existe"       }     ],     "request_id": "req_abc123xyz"   } }  # Códigos de error comunes: # validation_error - Datos inválidos # authentication_error - Problema de autenticación # authorization_error - Sin permisos # not_found - Recurso no encontrado # conflict - Conflicto de estado # rate_limit_exceeded - Límite excedido # internal_error - Error interno
 
7. SDKs y Ejemplos de Código
7.1 SDKs Oficiales
Lenguaje	Instalación	Repositorio
PHP	composer require comercioconecta/sdk	github.com/comercioconecta/php-sdk
Node.js	npm install @comercioconecta/sdk	github.com/comercioconecta/node-sdk
Python	pip install comercioconecta	github.com/comercioconecta/python-sdk
7.2 Ejemplo PHP
<?php use ComercioConecta\Client; use ComercioConecta\Exception\ApiException;  $client = new Client([     'api_key' => 'sk_live_abc123', ]);  // Listar productos $products = $client->products->list([     'status' => 'active',     'per_page' => 50, ]);  foreach ($products->data as $product) {     echo $product->title . " - " . $product->price . "€\n"; }  // Actualizar stock try {     $client->stock->update('12345', [         'quantity' => 100,         'adjustment_type' => 'set',         'reason' => 'pos_sync',     ]); } catch (ApiException $e) {     echo "Error: " . $e->getMessage(); }
7.3 Ejemplo Node.js
const ComercioConecta = require('@comercioconecta/sdk');  const client = new ComercioConecta({   apiKey: 'sk_live_abc123', });  // Listar pedidos nuevos async function getNewOrders() {   const orders = await client.orders.list({     status: 'confirmed',     created_since: '2026-01-17T00:00:00Z',   });      for (const order of orders.data) {     console.log(`Pedido ${order.order_number}: ${order.total}€`);          // Marcar como procesando     await client.orders.update(order.id, {       status: 'processing',     });   } }  // Escuchar webhooks const express = require('express'); const app = express();  app.post('/webhooks', (req, res) => {   const event = client.webhooks.verify(     req.body,     req.headers['x-webhook-signature'],     process.env.WEBHOOK_SECRET   );      switch (event.type) {     case 'order.created':       handleNewOrder(event.data.object);       break;     case 'stock.low':       alertLowStock(event.data.object);       break;   }      res.sendStatus(200); });
 
8. Casos de Uso Comunes
8.1 Sincronización con POS
// Flujo de sincronización POS → ComercioConecta  1. SINCRONIZACIÓN INICIAL    - GET /products?per_page=100 (paginado)    - Mapear productos locales con SKU/barcode    - Actualizar stock: POST /stock/bulk-update  2. VENTA EN TIENDA FÍSICA    - Cuando se vende en POS:    - PATCH /stock/{product_id} { adjustment_type: 'decrement', quantity: 1 }  3. PEDIDO ONLINE (Webhook)    - Recibir webhook 'order.created'    - Reservar stock en POS local    - Cuando se prepare: POST /orders/{id}/fulfill  4. SINCRONIZACIÓN PERIÓDICA (cada 15 min)    - GET /stock?updated_since={last_sync}    - Actualizar cantidades en POS local    - POST /stock/bulk-update con cantidades de POS
8.2 Integración con ERP
// Flujo ERP → ComercioConecta  1. PRODUCTOS    - ERP crea producto → POST /products    - ERP actualiza precio → PATCH /products/{id}    - Mapear categorías ERP con categorías plataforma  2. PEDIDOS    - Webhook 'order.paid' → Crear pedido en ERP    - ERP procesa → PATCH /orders/{id} { status: 'processing' }    - ERP envía → POST /orders/{id}/shipments  3. FACTURACIÓN    - Webhook 'order.completed' → Generar factura en ERP    - Sincronizar número de factura: PATCH /orders/{id} { invoice_number: 'F-2026-001' }  4. DEVOLUCIONES    - Webhook 'refund.created' → Procesar en ERP    - Actualizar stock si procede
 
9. Sandbox y Testing
9.1 Entorno Sandbox
El entorno sandbox es idéntico a producción pero con datos de prueba. Usa API keys que empiezan por sk_test_.
Base URL: https://sandbox-api.comercioconecta.es/v1 API Key: sk_test_*  Datos de prueba disponibles: - 100 productos de ejemplo en diferentes categorías - Órdenes de prueba para testing - Clientes de prueba  Tarjetas de prueba (Stripe Test Mode): - 4242 4242 4242 4242 - Pago exitoso - 4000 0000 0000 0002 - Tarjeta rechazada - 4000 0000 0000 3220 - Requiere 3D Secure
9.2 Simular Webhooks
# Enviar webhook de prueba desde sandbox  curl -X POST "https://sandbox-api.comercioconecta.es/v1/webhooks/test" \   -H "Authorization: Bearer {token}" \   -H "Content-Type: application/json" \   -d '{     "event": "order.created",     "webhook_id": "wh_abc123"   }'  # Esto enviará un webhook de prueba a tu endpoint configurado # con datos simulados
 
10. Soporte y Recursos
10.1 Recursos de Documentación
Recurso	URL	Descripción
API Reference	https://developers.comercioconecta.es/api	Documentación completa de API
Guías	https://developers.comercioconecta.es/guides	Tutoriales paso a paso
Changelog	https://developers.comercioconecta.es/changelog	Historial de cambios
Status Page	https://status.comercioconecta.es	Estado de los servicios
Postman	https://developers.comercioconecta.es/postman	Colección de Postman
10.2 Canales de Soporte
Canal	Contacto	Tiempo Respuesta
Email	developers@comercioconecta.es	24-48h
Slack	#api-support (invitación)	4-8h
GitHub Issues	github.com/comercioconecta/api-issues	48-72h
Soporte Premium	Ticket en dashboard	4h (Enterprise)
10.3 Proceso de Certificación
1. Desarrollar integración en Sandbox
2. Ejecutar test suite de validación
3. Solicitar revisión técnica
4. Obtener credenciales de producción
5. Aparecer en directorio de integraciones (opcional)
--- Fin del Documento ---
79_ComercioConecta_API_Integration_Guide_v1.docx | Jaraba Impact Platform | Enero 2026
