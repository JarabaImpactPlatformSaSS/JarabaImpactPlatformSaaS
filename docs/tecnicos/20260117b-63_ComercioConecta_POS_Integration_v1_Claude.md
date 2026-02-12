SISTEMA POS INTEGRATION
Motor de Sincronización con Terminales Punto de Venta
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	63_ComercioConecta_POS_Integration
Dependencias:	62_Commerce_Core, 01_Core_Entidades, 06_Core_Flujos_ECA
Tipo:	Componente Exclusivo ComercioConecta
 
1. Resumen Ejecutivo
Este documento especifica el Motor POS Sync, un componente exclusivo de ComercioConecta que permite la sincronización bidireccional entre la plataforma Drupal Commerce y los principales terminales punto de venta (TPV) del mercado. Este sistema es clave para habilitar la experiencia omnicanal que diferencia a ComercioConecta: las ventas en tienda física se reflejan automáticamente en el inventario online y viceversa.
1.1 Objetivos del Sistema
• Sincronización de catálogo: Productos, precios y variaciones entre Drupal y TPV
• Sincronización de inventario: Stock en tiempo real bidireccional
• Captura de ventas físicas: Transacciones del TPV registradas en Drupal para analytics unificado
• Gestión de conflictos: Reconciliación automática cuando hay discrepancias
• Arquitectura extensible: Conectores modulares para añadir nuevos TPV sin refactoring
1.2 TPVs Soportados (v1.0)
TPV	Fabricante	Cuota Mercado ES	API	Webhooks
Square	Block (Square)	~15%	REST v2	Sí (robustos)
SumUp	SumUp	~25%	REST v0.1	Limitados
Shopify POS	Shopify	~10%	GraphQL + REST	Sí (completos)
Zettle	PayPal	~20%	REST v2	Sí (básicos)
Lightspeed	Lightspeed	~8%	REST v3	Sí (fase 2)
TPV Genérico	Varios	~22%	CSV/Excel import	No (polling)
1.3 Arquitectura de Alto Nivel
El sistema sigue un patrón de adaptadores (Adapter Pattern) donde cada TPV tiene su propio conector que implementa una interfaz común:
┌─────────────────────────────────────────────────────────────────┐ │                    DRUPAL COMMERCE (jaraba_retail)              │ │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │ │  │  Products   │  │    Stock    │  │    Orders/Sales         │  │ │  └──────┬──────┘  └──────┬──────┘  └───────────┬─────────────┘  │ └─────────┼────────────────┼─────────────────────┼────────────────┘           │                │                     │           ▼                ▼                     ▼ ┌─────────────────────────────────────────────────────────────────┐ │                     POS SYNC ENGINE                             │ │  ┌──────────────────────────────────────────────────────────┐   │ │  │              POSConnectorInterface                        │   │ │  │  + syncCatalog()  + syncStock()  + importSales()         │   │ │  └──────────────────────────────────────────────────────────┘   │ │         │              │              │              │           │ │    ┌────┴────┐   ┌─────┴────┐  ┌─────┴────┐   ┌─────┴────┐     │ │    │ Square  │   │  SumUp   │  │ Shopify  │   │  Zettle  │     │ │    │Connector│   │Connector │  │Connector │   │Connector │     │ │    └────┬────┘   └────┬─────┘  └────┬─────┘   └────┬─────┘     │ └─────────┼─────────────┼─────────────┼──────────────┼────────────┘           │             │             │              │           ▼             ▼             ▼              ▼      ┌─────────┐  ┌──────────┐  ┌───────────┐  ┌──────────┐      │ Square  │  │  SumUp   │  │ Shopify   │  │  Zettle  │      │  API    │  │   API    │  │   API     │  │   API    │      └─────────┘  └──────────┘  └───────────┘  └──────────┘
 
2. Entidades del Sistema
El sistema POS Integration introduce nuevas entidades para gestionar las conexiones con TPV, el mapeo de productos, y el historial de sincronización.
2.1 Entidad: pos_connection
Representa la conexión de un comercio con un TPV específico. Un comercio puede tener múltiples conexiones (ej: Square para tienda principal, SumUp para popup).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio propietario	FK merchant_profile.id, NOT NULL, INDEX
location_id	INT	Ubicación de stock asociada	FK stock_location.id, NOT NULL
provider	VARCHAR(32)	Proveedor del TPV	ENUM: square|sumup|shopify|zettle|lightspeed|generic
name	VARCHAR(128)	Nombre descriptivo	NOT NULL, ej: 'TPV Tienda Principal'
api_key_encrypted	TEXT	API Key cifrada (AES-256)	NOT NULL
api_secret_encrypted	TEXT	API Secret cifrado	NULLABLE
access_token_encrypted	TEXT	OAuth access token	NULLABLE
refresh_token_encrypted	TEXT	OAuth refresh token	NULLABLE
token_expires_at	DATETIME	Expiración del token	NULLABLE
external_location_id	VARCHAR(64)	ID de ubicación en el TPV	NULLABLE
external_merchant_id	VARCHAR(64)	ID de comercio en el TPV	NULLABLE
webhook_secret	VARCHAR(128)	Secret para verificar webhooks	NULLABLE
sync_catalog_enabled	BOOLEAN	Sincronizar catálogo	DEFAULT TRUE
sync_stock_enabled	BOOLEAN	Sincronizar stock	DEFAULT TRUE
sync_sales_enabled	BOOLEAN	Importar ventas	DEFAULT TRUE
sync_direction	VARCHAR(16)	Dirección de sync	ENUM: bidirectional|to_pos|from_pos
last_catalog_sync	DATETIME	Última sync de catálogo	NULLABLE
last_stock_sync	DATETIME	Última sync de stock	NULLABLE
last_sales_import	DATETIME	Última importación de ventas	NULLABLE
status	VARCHAR(16)	Estado de la conexión	ENUM: active|paused|error|disconnected
error_message	TEXT	Último mensaje de error	NULLABLE
error_count	INT	Errores consecutivos	DEFAULT 0
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: pos_product_mapping
Mapeo entre productos/variaciones de Drupal y sus equivalentes en el TPV. Permite que los IDs internos difieran entre sistemas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
connection_id	INT	Conexión POS	FK pos_connection.id, NOT NULL, INDEX
variation_id	INT	Variación en Drupal	FK product_variation_retail.id, NOT NULL, INDEX
external_product_id	VARCHAR(64)	ID producto en TPV	NOT NULL
external_variation_id	VARCHAR(64)	ID variación en TPV	NULLABLE
external_sku	VARCHAR(64)	SKU en el TPV	NULLABLE, INDEX
sync_status	VARCHAR(16)	Estado del mapeo	ENUM: synced|pending|error|orphan
last_synced_at	DATETIME	Última sincronización	NULLABLE
drupal_updated_at	DATETIME	Última actualización Drupal	NOT NULL
pos_updated_at	DATETIME	Última actualización POS	NULLABLE
checksum_drupal	VARCHAR(64)	Hash de datos Drupal	Para detección de cambios
checksum_pos	VARCHAR(64)	Hash de datos POS	Para detección de cambios
UNIQUE CONSTRAINT: (connection_id, variation_id) - Una variación solo tiene un mapeo por conexión.
2.3 Entidad: pos_sync_log
Registro de operaciones de sincronización para auditoría y debugging.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
connection_id	INT	Conexión POS	FK pos_connection.id, NOT NULL, INDEX
operation	VARCHAR(32)	Tipo de operación	ENUM: catalog_sync|stock_sync|sales_import|webhook
direction	VARCHAR(16)	Dirección	ENUM: to_pos|from_pos
status	VARCHAR(16)	Resultado	ENUM: success|partial|failed
items_processed	INT	Items procesados	DEFAULT 0
items_created	INT	Items creados	DEFAULT 0
items_updated	INT	Items actualizados	DEFAULT 0
items_failed	INT	Items fallidos	DEFAULT 0
error_details	JSON	Detalle de errores	NULLABLE, array de {item_id, error}
duration_ms	INT	Duración en ms	NOT NULL
started_at	DATETIME	Inicio operación	NOT NULL, UTC
completed_at	DATETIME	Fin operación	NOT NULL, UTC
triggered_by	VARCHAR(32)	Origen del trigger	ENUM: cron|webhook|manual|eca
2.4 Entidad: pos_sale_record
Registro de ventas importadas desde el TPV. Se usa para analytics unificado sin crear pedidos Drupal Commerce completos (las ventas físicas no necesitan checkout flow).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
connection_id	INT	Conexión POS origen	FK pos_connection.id, NOT NULL, INDEX
merchant_id	INT	Comercio	FK merchant_profile.id, NOT NULL, INDEX
external_transaction_id	VARCHAR(64)	ID transacción en TPV	NOT NULL, UNIQUE per connection
external_receipt_number	VARCHAR(32)	Número de ticket	NULLABLE
transaction_date	DATETIME	Fecha/hora de la venta	NOT NULL, INDEX
subtotal	DECIMAL(10,2)	Subtotal sin IVA	NOT NULL
tax_amount	DECIMAL(10,2)	IVA total	NOT NULL
discount_amount	DECIMAL(10,2)	Descuentos aplicados	DEFAULT 0
total_amount	DECIMAL(10,2)	Total cobrado	NOT NULL
currency	VARCHAR(3)	Moneda	DEFAULT 'EUR'
payment_method	VARCHAR(32)	Método de pago	ENUM: card|cash|mixed|other
line_items	JSON	Detalle de líneas	Array de {variation_id, qty, price, name}
customer_email	VARCHAR(255)	Email cliente (si capturado)	NULLABLE
employee_id	VARCHAR(64)	ID empleado en TPV	NULLABLE
imported_at	DATETIME	Fecha de importación	NOT NULL, UTC
 
3. Interfaz de Conectores
Todos los conectores de TPV implementan la interfaz POSConnectorInterface, garantizando consistencia y permitiendo añadir nuevos proveedores sin modificar el core.
3.1 POSConnectorInterface
<?php namespace Drupal\jaraba_pos\Connector;  interface POSConnectorInterface {    // Autenticación   public function authenticate(POSConnection $connection): bool;   public function refreshToken(POSConnection $connection): bool;   public function testConnection(POSConnection $connection): ConnectionTestResult;    // Catálogo   public function syncCatalogToPos(POSConnection $connection, array $variations): SyncResult;   public function syncCatalogFromPos(POSConnection $connection): SyncResult;   public function createProductInPos(POSConnection $connection, ProductVariationRetail $variation): ?string;   public function updateProductInPos(POSConnection $connection, ProductVariationRetail $variation): bool;   public function deleteProductInPos(POSConnection $connection, string $externalId): bool;    // Stock   public function syncStockToPos(POSConnection $connection, array $stockLevels): SyncResult;   public function syncStockFromPos(POSConnection $connection): SyncResult;   public function getStockFromPos(POSConnection $connection, string $externalVariationId): ?int;    // Ventas   public function importSales(POSConnection $connection, \DateTime $since): array;   public function getTransactionDetails(POSConnection $connection, string $transactionId): ?array;    // Webhooks   public function registerWebhook(POSConnection $connection, string $event, string $url): bool;   public function verifyWebhookSignature(POSConnection $connection, string $payload, string $signature): bool;   public function handleWebhook(POSConnection $connection, string $event, array $payload): void;    // Metadata   public function getProviderName(): string;   public function getSupportedFeatures(): array;   public function getRequiredCredentials(): array; }
3.2 Estructuras de Datos
// SyncResult - Resultado de sincronización class SyncResult {   public bool $success;   public int $created;   public int $updated;   public int $deleted;   public int $failed;   public array $errors; // [{item_id, message, code}]   public int $durationMs; }  // ConnectionTestResult - Resultado de test de conexión class ConnectionTestResult {   public bool $success;   public ?string $merchantName;   public ?string $locationName;   public array $availableFeatures;   public ?string $errorMessage; }
 
4. Conector Square
Square es uno de los TPV más completos del mercado con una API robusta y webhooks fiables. Es el conector de referencia para ComercioConecta.
4.1 Características
Característica	Soporte	Notas
Autenticación	OAuth 2.0	Access token + refresh automático
Sync Catálogo → POS	✅ Completo	Productos, variaciones, imágenes, precios
Sync Catálogo ← POS	✅ Completo	Importación de productos creados en POS
Sync Stock → POS	✅ Completo	Actualización de inventario en Square
Sync Stock ← POS	✅ Completo	Lectura de stock desde Square
Importar Ventas	✅ Completo	Transacciones con detalle de líneas
Webhooks	✅ Completo	inventory.count.updated, payment.completed
Multi-ubicación	✅ Soportado	location_id por conexión
4.2 Configuración OAuth
// Credenciales requeridas para Square [   'application_id' => 'sq0idp-XXXXX',        // Application ID   'application_secret' => 'sq0csp-XXXXX',    // Application Secret (cifrado)   'access_token' => 'EAAAE...',              // Access Token (cifrado)   'refresh_token' => 'EQAAe...',             // Refresh Token (cifrado)   'environment' => 'production|sandbox',     // Entorno   'location_id' => 'LXXXXX'                  // ID de ubicación Square ]
4.3 Mapeo de Campos Square ↔ Drupal
Campo Drupal	Campo Square	Transformación
product_retail.title	CatalogItem.name	Directo
product_retail.body	CatalogItem.description	Strip HTML
variation.sku	CatalogItemVariation.sku	Directo
variation.price_amount	CatalogItemVariation.price_money.amount	EUR cents × 100
variation.ean	CatalogItemVariation.upc	Directo si 13 dígitos
stock_level.quantity	InventoryCount.quantity	Directo (string en Square)
size_tid (term name)	CatalogItemVariation.item_option_values	Mapeo de opciones
color_tid (term name)	CatalogItemVariation.item_option_values	Mapeo de opciones
images[0]	CatalogImage.url	Upload a Square, obtener ID
4.4 Webhooks Square
Evento Square	Acción en Drupal	Prioridad
inventory.count.updated	Actualizar stock_level.quantity de la ubicación	Alta
catalog.version.updated	Disparar sync de catálogo desde POS	Media
payment.completed	Importar transacción como pos_sale_record	Alta
payment.refunded	Marcar venta como refunded, ajustar stock	Alta
oauth.authorization.revoked	Desactivar conexión, notificar merchant	Crítica
 
5. Conector SumUp
SumUp es muy popular en España para pequeños comercios. Su API es más limitada que Square, pero cubre los casos de uso esenciales.
5.1 Características
Característica	Soporte	Notas
Autenticación	OAuth 2.0	Scopes: products, transactions
Sync Catálogo → POS	✅ Completo	Productos y variaciones
Sync Catálogo ← POS	⚠️ Limitado	Solo lectura, sin imágenes
Sync Stock → POS	❌ No soportado	SumUp no tiene gestión de inventario
Sync Stock ← POS	❌ No soportado	Decrementar localmente tras venta
Importar Ventas	✅ Completo	API de transacciones
Webhooks	⚠️ Limitado	Solo payment.successful
Multi-ubicación	❌ No aplica	Un merchant = una ubicación
5.2 Estrategia de Stock para SumUp
Como SumUp no tiene gestión de inventario nativa, el sistema ComercioConecta implementa una estrategia alternativa:
1. Cuando se recibe webhook payment.successful o se importa venta vía polling:
2. Mapear product_id de SumUp → variation_id de Drupal
3. Decrementar stock_level.quantity en la ubicación asociada
4. Si stock llega a 0, el producto sigue visible en SumUp (requiere acción manual del comerciante)
5.3 Polling de Transacciones
Dado que los webhooks de SumUp son limitados, se implementa un job de cron que hace polling cada 15 minutos:
// Cron job: sumup_sales_import // Frecuencia: cada 15 minutos // Endpoint: GET /v0.1/me/transactions?newest_time=&oldest_time=  foreach ($activeConnections as $connection) {   $since = $connection->last_sales_import ?? new \DateTime('-1 day');   $transactions = $sumupConnector->importSales($connection, $since);      foreach ($transactions as $tx) {     if (!POSSaleRecord::existsByExternalId($connection, $tx['id'])) {       POSSaleRecord::createFromSumUp($connection, $tx);       // Decrementar stock       foreach ($tx['products'] as $product) {         $stockService->decrementFromPOS($product['id'], $product['quantity'], $connection);       }     }   } }
 
6. Conector Shopify POS
Shopify POS es ideal para comercios que ya tienen tienda Shopify online. La integración permite unificar inventario entre Shopify y ComercioConecta.
6.1 Características
Característica	Soporte	Notas
Autenticación	OAuth 2.0 + API Key	Admin API access scopes
Sync Catálogo → POS	✅ Completo	GraphQL Bulk Operations
Sync Catálogo ← POS	✅ Completo	Productos, variantes, imágenes
Sync Stock → POS	✅ Completo	InventoryLevel mutations
Sync Stock ← POS	✅ Completo	Inventory webhooks
Importar Ventas	✅ Completo	Orders API + POS channel filter
Webhooks	✅ Completo	products/*, inventory_levels/*, orders/*
Multi-ubicación	✅ Soportado	location_id nativo
6.2 Consideraciones Especiales
• Shopify como fuente primaria: Si el comercio ya usa Shopify, se recomienda sync unidireccional FROM_POS
• Rate limits: 2 requests/segundo (bucket), usar GraphQL bulk para operaciones masivas
• Variantes: Shopify tiene límite de 100 variantes por producto (suficiente para retail)
• Metafields: Usar metafields para almacenar drupal_variation_id en Shopify
 
7. Conector Zettle (PayPal)
Zettle (antes iZettle, ahora de PayPal) es muy popular en España. API decente con algunas limitaciones.
7.1 Características
Característica	Soporte	Notas
Autenticación	OAuth 2.0	Client credentials + user authorization
Sync Catálogo → POS	✅ Completo	Products API v2
Sync Catálogo ← POS	✅ Completo	Lectura de biblioteca de productos
Sync Stock → POS	⚠️ Parcial	Solo lectura, no escritura de stock
Sync Stock ← POS	✅ Completo	Inventory tracking si está habilitado
Importar Ventas	✅ Completo	Purchases API
Webhooks	⚠️ Básico	PurchaseCreated, ProductUpdated
Multi-ubicación	❌ No soportado	Un merchant = una ubicación
7.2 Mapeo de Variantes Zettle
Zettle usa un modelo simplificado de variantes donde cada combinación talla/color es un producto independiente con su propio UUID:
// Estructura de producto Zettle {   "uuid": "abc-123",   "name": "Camiseta Básica - M - Blanco",   "sku": "CAM-BAS-M-WHT",   "price": { "amount": 2995, "currencyId": "EUR" },   "variants": []  // Zettle recomienda crear productos separados }  // Estrategia: Un product_variation_retail → Un producto Zettle // El título incluye [Producto] - [Talla] - [Color]
 
8. Conector Genérico (CSV/Excel)
Para TPV que no tienen API o para comercios que usan sistemas propietarios, se ofrece un conector genérico basado en importación/exportación de archivos CSV o Excel.
8.1 Flujo de Trabajo
1. Comerciante exporta CSV de productos/ventas desde su TPV
2. Sube el archivo a ComercioConecta (Merchant Portal → Importar desde TPV)
3. Sistema mapea columnas automáticamente (o manual si es primera vez)
4. Previsualización de cambios antes de confirmar
5. Aplicar cambios y generar log de sincronización
8.2 Formato CSV de Ventas
Columna	Requerido	Formato	Ejemplo
transaction_id	Sí	String	TXN-2026-001234
date	Sí	ISO 8601 o DD/MM/YYYY	2026-01-15T14:30:00
sku	Sí	String	CAM-BAS-M-WHT
quantity	Sí	Integer	2
unit_price	Sí	Decimal	29.95
total	Sí	Decimal	59.90
payment_method	No	card|cash|other	card
customer_email	No	Email	cliente@email.com
8.3 Automatización con Folder Watch
Para comercios más técnicos, se puede configurar una carpeta vigilada (Dropbox, Google Drive, SFTP) donde el TPV deposita exports automáticos:
// Configuración folder watch [   'provider' => 'dropbox|gdrive|sftp',   'watch_path' => '/TPV_Exports/',   'file_pattern' => 'ventas_*.csv',   'check_interval' => 3600,  // segundos   'archive_after_import' => true,   'archive_path' => '/TPV_Exports/Procesados/' ]
 
9. Servicio de Sincronización
El servicio central POSSyncService coordina todas las operaciones de sincronización, gestiona conflictos, y mantiene los logs.
9.1 POSSyncService
<?php namespace Drupal\jaraba_pos\Service;  class POSSyncService {    public function syncCatalog(POSConnection $connection, string $direction = 'bidirectional'): SyncResult;   public function syncStock(POSConnection $connection, string $direction = 'bidirectional'): SyncResult;   public function importSales(POSConnection $connection, ?\DateTime $since = null): array;      public function resolveConflict(POSProductMapping $mapping, string $strategy): void;   public function detectChanges(POSConnection $connection): array;      public function scheduleSync(POSConnection $connection, string $operation, ?\DateTime $at = null): void;   public function runPendingSyncs(): void;      public function getConnector(POSConnection $connection): POSConnectorInterface;   public function logOperation(POSConnection $connection, string $operation, SyncResult $result): POSSyncLog; }
9.2 Estrategias de Resolución de Conflictos
Cuando un producto ha sido modificado tanto en Drupal como en el TPV desde la última sincronización, el sistema debe resolver el conflicto:
Estrategia	Descripción	Uso Recomendado
drupal_wins	Los datos de Drupal sobreescriben el TPV	Drupal es fuente de verdad
pos_wins	Los datos del TPV sobreescriben Drupal	TPV es fuente de verdad
newest_wins	La modificación más reciente gana	Ambos sistemas son iguales
manual	Se crea alerta y requiere intervención	Datos críticos (precios)
merge	Combinar campos no conflictivos	Campos independientes
Configuración por defecto: Stock → newest_wins, Precios → manual, Títulos → drupal_wins
9.3 Detección de Cambios (Checksums)
Para optimizar la sincronización y evitar updates innecesarios, se calculan checksums de los datos relevantes:
// Checksum de producto (campos que afectan sincronización) $checksumFields = ['title', 'body', 'price_amount', 'sku', 'ean', 'is_active']; $data = array_map(fn($f) => $variation->get($f)->value, $checksumFields); $checksum = hash('sha256', json_encode($data));  // Comparación if ($mapping->checksum_drupal !== $checksum) {   // Producto ha cambiado en Drupal → sincronizar al POS }
 
10. Flujos de Automatización (ECA)
Los flujos ECA automatizan la sincronización sin intervención manual.
10.1 ECA-POS-001: Producto Modificado en Drupal
Trigger: Update de product_retail o product_variation_retail
1. Verificar que el comercio tiene conexiones POS activas
2. Para cada conexión con sync_catalog_enabled = TRUE y direction ≠ 'from_pos':
   a. Calcular nuevo checksum
   b. Si checksum cambió, encolar sync al POS
3. Procesar cola en batch (max 50 productos por minuto)
10.2 ECA-POS-002: Stock Modificado en Drupal
Trigger: Update de stock_level.quantity
1. Obtener conexión POS asociada a la ubicación
2. Si sync_stock_enabled = TRUE y direction ≠ 'from_pos':
   a. Actualizar stock en POS inmediatamente (crítico)
3. Registrar en pos_sync_log
10.3 ECA-POS-003: Webhook Recibido
Trigger: POST a /api/v1/pos/webhook/{connection_uuid}
1. Validar firma del webhook (HMAC)
2. Identificar tipo de evento (inventory, payment, product)
3. Despachar al handler apropiado del conector
4. Actualizar entidades Drupal según el evento
5. Registrar en pos_sync_log con triggered_by = 'webhook'
10.4 ECA-POS-004: Cron de Importación de Ventas
Trigger: Cron cada 15 minutos
1. Obtener conexiones con sync_sales_enabled = TRUE
2. Para cada conexión:
   a. Llamar importSales(connection, last_sales_import)
   b. Crear pos_sale_record por cada transacción nueva
   c. Decrementar stock según líneas de venta
3. Actualizar connection.last_sales_import
 
11. APIs REST
11.1 Endpoints de Conexiones
Método	Endpoint	Descripción	Auth
GET	/api/v1/pos/connections	Listar conexiones del comercio	Merchant
POST	/api/v1/pos/connections	Crear nueva conexión	Merchant
GET	/api/v1/pos/connections/{id}	Detalle de conexión	Merchant
PATCH	/api/v1/pos/connections/{id}	Actualizar configuración	Merchant
DELETE	/api/v1/pos/connections/{id}	Eliminar conexión	Merchant
POST	/api/v1/pos/connections/{id}/test	Probar conexión	Merchant
GET	/api/v1/pos/providers	Listar proveedores disponibles	Público
11.2 Endpoints de Sincronización
Método	Endpoint	Descripción	Auth
POST	/api/v1/pos/connections/{id}/sync/catalog	Forzar sync de catálogo	Merchant
POST	/api/v1/pos/connections/{id}/sync/stock	Forzar sync de stock	Merchant
POST	/api/v1/pos/connections/{id}/sync/sales	Forzar importación de ventas	Merchant
GET	/api/v1/pos/connections/{id}/mappings	Ver mapeos de productos	Merchant
POST	/api/v1/pos/connections/{id}/mappings/{variation}/resolve	Resolver conflicto	Merchant
GET	/api/v1/pos/connections/{id}/logs	Ver logs de sincronización	Merchant
11.3 Endpoints de Webhooks
Método	Endpoint	Descripción	Auth
POST	/api/v1/pos/webhook/{connection_uuid}	Receptor de webhooks	Signature
GET	/api/v1/pos/oauth/{provider}/callback	Callback OAuth	State token
 
12. Seguridad
12.1 Almacenamiento de Credenciales
• Todas las API keys y tokens se cifran con AES-256-GCM antes de almacenar
• La clave de cifrado se almacena en variable de entorno, nunca en base de datos
• Los tokens de acceso se rotan automáticamente antes de expirar
• Los logs nunca contienen credenciales, solo IDs de conexión
12.2 Verificación de Webhooks
// Verificación HMAC de webhook public function verifyWebhookSignature(   POSConnection $connection,    string $payload,    string $signature ): bool {   $secret = $this->decrypt($connection->webhook_secret);   $expected = hash_hmac('sha256', $payload, $secret);   return hash_equals($expected, $signature); }
12.3 Rate Limiting
• Webhooks: Max 100 requests/minuto por conexión
• Sync manual: Max 10 operaciones/hora por conexión
• APIs de POS: Se respetan los rate limits de cada proveedor
 
13. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades pos_connection, pos_product_mapping, pos_sync_log. POSConnectorInterface. Servicio base POSSyncService.	62_Commerce_Core
Sprint 2	Semana 3-4	Conector Square completo: OAuth, catálogo, stock, webhooks. Tests de integración.	Sprint 1 + Square Sandbox
Sprint 3	Semana 5-6	Conector SumUp: OAuth, catálogo, polling de ventas. Conector Zettle: OAuth, catálogo, webhooks.	Sprint 2
Sprint 4	Semana 7-8	Conector Shopify POS: OAuth, GraphQL, webhooks. Entidad pos_sale_record y analytics de ventas físicas.	Sprint 3
Sprint 5	Semana 9-10	Conector Genérico CSV/Excel. UI de importación. Folder watch para automatización.	Sprint 4
Sprint 6	Semana 11-12	Flujos ECA completos. UI en Merchant Portal. Documentación. QA y go-live.	Sprint 5
13.1 Criterios de Aceptación Sprint 2 (Square)
✓ OAuth flow completo con obtención y refresh de tokens
✓ Crear producto en Square desde Drupal
✓ Actualizar stock bidireccional
✓ Recibir y procesar webhook de venta
✓ Tests de integración con Square Sandbox
13.2 Dependencias Externas
• Square PHP SDK: square/square ^28.0
• Shopify PHP SDK: shopify/shopify-api ^5.0
• PhpSpreadsheet: phpoffice/phpspreadsheet ^1.29 (para CSV/Excel)
• Guzzle HTTP: guzzlehttp/guzzle ^7.8 (para SumUp y Zettle)
--- Fin del Documento ---
63_ComercioConecta_POS_Integration_v1.docx | Jaraba Impact Platform | Enero 2026
