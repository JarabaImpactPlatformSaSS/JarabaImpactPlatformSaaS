SISTEMA ORDER MANAGEMENT
Gestión de Pedidos Omnicanal para Comercio de Proximidad
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	67_ComercioConecta_Order_System
Dependencias:	62_Commerce_Core, 63_POS_Integration, 68_Checkout_Flow
Base:	49_AgroConecta_Order_System (~70% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Gestión de Pedidos Omnicanal para la vertical ComercioConecta. El sistema soporta múltiples canales de venta y fulfillment, incluyendo Click & Collect, Ship-from-Store, y envío desde almacén, permitiendo a los comercios de proximidad competir con la experiencia omnicanal de grandes retailers.
1.1 Propuesta de Valor
"Compra online, recoge en tienda en 2 horas"
• Click & Collect Express: Recogida en tienda en menos de 2 horas
• Ship-from-Store: El comercio envía desde su tienda, sin intermediarios
• Reserva en tienda: Ver en tienda, pagar con calma online
• Fulfillment inteligente: El sistema elige la mejor ubicación automáticamente
• Visibilidad total: Cliente y comercio ven el estado en tiempo real
1.2 Canales de Venta Soportados
Canal	Descripción	Fulfillment Disponible
Web Marketplace	Compra en comercioconecta.es	Envío, Click & Collect
Web Propia	Tienda del comercio (subdominio)	Envío, Click & Collect
App Móvil	App iOS/Android	Envío, Click & Collect, Reserva
POS Tienda	Venta en mostrador	Entrega inmediata, Envío a domicilio
WhatsApp	Pedidos vía WhatsApp Business	Envío, Click & Collect
Teléfono	Pedidos por llamada	Envío, Click & Collect
1.3 Modos de Fulfillment
Modo	Origen	Destino	Tiempo Típico
Click & Collect	Tienda física	Cliente recoge en tienda	2-24 horas
Ship-from-Store	Tienda física	Domicilio cliente	1-3 días
Ship-from-Warehouse	Almacén	Domicilio cliente	2-5 días
Entrega Local	Tienda física	Domicilio cercano	Mismo día
Reserva en Tienda	Tienda física	Cliente prueba y decide	24-48 horas hold
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────┐ │                      ORDER MANAGEMENT SYSTEM                        │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │    Order     │  │  Fulfillment │  │    Shipment              │  │ │  │   Manager    │──│   Router     │──│    Manager               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Stock      │  │   Pickup     │  │    Notification          │  │ │  │   Allocator  │──│   Manager    │──│    Engine                │  │ │  │              │  │ (Click&Coll) │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Return     │  │   Invoice    │  │    Analytics             │  │ │  │   Manager    │──│   Generator  │──│    Tracker               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └───────────────────────────┬─────────────────────────────────────────┘                             │         ┌───────────────────┼───────────────────┐         ▼                   ▼                   ▼  ┌────────────┐      ┌────────────┐      ┌────────────┐  │   Stripe   │      │  Carriers  │      │    POS     │  │  Connect   │      │ (MRW,SEUR) │      │   Sync     │  └────────────┘      └────────────┘      └────────────┘
2.2 Flujo de Vida de un Pedido
┌─────────┐    ┌──────────┐    ┌───────────┐    ┌────────────┐ │  CART   │───▶│ CHECKOUT │───▶│  PAYMENT  │───▶│  CONFIRMED │ └─────────┘    └──────────┘    └───────────┘    └─────┬──────┘                                                       │                     ┌─────────────────────────────────┘                     ▼             ┌───────────────┐             │  FULFILLMENT  │             │    ROUTER     │             └───────┬───────┘                     │       ┌─────────────┼─────────────┐       ▼             ▼             ▼ ┌──────────┐  ┌──────────┐  ┌──────────┐ │  CLICK   │  │  SHIP    │  │ DELIVERY │ │ &COLLECT │  │  FROM    │  │  LOCAL   │ │          │  │  STORE   │  │          │ └────┬─────┘  └────┬─────┘  └────┬─────┘      │             │             │      ▼             ▼             ▼ ┌──────────┐  ┌──────────┐  ┌──────────┐ │  READY   │  │ SHIPPED  │  │   OUT    │ │FOR PICKUP│  │          │  │   FOR    │ │          │  │          │  │ DELIVERY │ └────┬─────┘  └────┬─────┘  └────┬─────┘      │             │             │      └─────────────┼─────────────┘                    ▼             ┌──────────┐             │COMPLETED │             └──────────┘
 
3. Entidades del Sistema
3.1 Entidad: retail_order (extiende commerce_order)
Extiende la entidad commerce_order de Drupal Commerce con campos específicos para retail omnicanal.
Campo	Tipo	Descripción	Restricciones
order_id	INT	ID de commerce_order base	PRIMARY KEY, FK commerce_order
merchant_id	INT	Comercio vendedor	FK merchant_profile.id, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
order_number	VARCHAR(32)	Número de pedido visible	UNIQUE, NOT NULL, ej: 'ORD-2026-001234'
sales_channel	VARCHAR(32)	Canal de origen	ENUM: web|app|pos|whatsapp|phone
fulfillment_type	VARCHAR(32)	Tipo de fulfillment	ENUM: click_collect|ship_store|ship_warehouse|local_delivery|in_store
fulfillment_location_id	INT	Ubicación de fulfillment	FK stock_location.id, NULLABLE
pickup_location_id	INT	Ubicación de recogida	FK stock_location.id, NULLABLE (si click_collect)
pickup_code	VARCHAR(8)	Código de recogida	NULLABLE, generado para click_collect
pickup_ready_at	DATETIME	Listo para recoger	NULLABLE
pickup_deadline	DATETIME	Fecha límite recogida	NULLABLE
pickup_collected_at	DATETIME	Fecha de recogida	NULLABLE
delivery_date_requested	DATE	Fecha entrega solicitada	NULLABLE
delivery_time_slot	VARCHAR(16)	Franja horaria	NULLABLE, ej: '10:00-14:00'
delivery_instructions	TEXT	Instrucciones entrega	NULLABLE
is_gift	BOOLEAN	Es regalo	DEFAULT FALSE
gift_message	TEXT	Mensaje de regalo	NULLABLE
gift_wrap	BOOLEAN	Envolver para regalo	DEFAULT FALSE
customer_notes	TEXT	Notas del cliente	NULLABLE
merchant_notes	TEXT	Notas internas comercio	NULLABLE
source_qr_id	INT	QR origen (si aplica)	FK dynamic_qr.id, NULLABLE
flash_offer_id	INT	Oferta flash aplicada	FK flash_offer.id, NULLABLE
pos_transaction_id	VARCHAR(64)	ID transacción POS	NULLABLE, si viene de POS
invoice_number	VARCHAR(32)	Número de factura	NULLABLE, UNIQUE
invoice_generated_at	DATETIME	Fecha generación factura	NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3.2 Entidad: order_line_item (extiende commerce_order_item)
Líneas de pedido con información extendida de fulfillment y stock allocation.
Campo	Tipo	Descripción	Restricciones
order_item_id	INT	ID de commerce_order_item	PRIMARY KEY, FK
variation_id	INT	Variación comprada	FK product_variation_retail.id, NOT NULL
quantity	INT	Cantidad pedida	NOT NULL, >= 1
unit_price	DECIMAL(10,2)	Precio unitario	NOT NULL
total_price	DECIMAL(10,2)	Precio total línea	NOT NULL
discount_amount	DECIMAL(10,2)	Descuento aplicado	DEFAULT 0
tax_amount	DECIMAL(10,2)	IVA de la línea	NOT NULL
fulfillment_status	VARCHAR(32)	Estado de esta línea	ENUM: pending|allocated|picked|packed|shipped|delivered|cancelled
allocated_location_id	INT	Ubicación asignada	FK stock_location.id, NULLABLE
allocated_at	DATETIME	Fecha de asignación	NULLABLE
picked_at	DATETIME	Fecha de picking	NULLABLE
picked_by	INT	Usuario que hizo picking	FK users.uid, NULLABLE
serial_numbers	JSON	Números de serie (si aplica)	Array de strings, NULLABLE
is_gift	BOOLEAN	Esta línea es regalo	DEFAULT FALSE
return_status	VARCHAR(32)	Estado de devolución	ENUM: none|requested|approved|received|refunded
return_reason	VARCHAR(128)	Motivo devolución	NULLABLE
return_quantity	INT	Cantidad devuelta	DEFAULT 0
3.3 Entidad: order_shipment
Envíos asociados a un pedido. Un pedido puede tener múltiples shipments (split shipment).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
order_id	INT	Pedido padre	FK retail_order.order_id, NOT NULL, INDEX
shipment_number	VARCHAR(32)	Número de envío	UNIQUE, NOT NULL
carrier	VARCHAR(32)	Transportista	ENUM: mrw|seur|gls|correos|envialia|dhl|ups|self
service_type	VARCHAR(32)	Tipo de servicio	ENUM: standard|express|same_day|economy
tracking_number	VARCHAR(64)	Número de seguimiento	NULLABLE, INDEX
tracking_url	VARCHAR(500)	URL de seguimiento	NULLABLE
label_url	VARCHAR(500)	URL etiqueta de envío	NULLABLE
origin_location_id	INT	Ubicación origen	FK stock_location.id, NOT NULL
destination_address	JSON	Dirección destino	NOT NULL, AddressFormat
weight_kg	DECIMAL(8,3)	Peso total kg	NULLABLE
dimensions	JSON	Dimensiones paquete	{length, width, height} en cm
packages_count	INT	Número de bultos	DEFAULT 1
shipping_cost	DECIMAL(10,2)	Coste de envío	NOT NULL
insurance_value	DECIMAL(10,2)	Valor asegurado	NULLABLE
status	VARCHAR(32)	Estado del envío	ENUM: pending|label_created|picked_up|in_transit|out_for_delivery|delivered|exception|returned
estimated_delivery	DATE	Fecha estimada entrega	NULLABLE
actual_delivery	DATETIME	Fecha real entrega	NULLABLE
delivery_signature	VARCHAR(128)	Firma de recepción	NULLABLE
delivery_photo_fid	INT	Foto de entrega	FK file_managed.fid, NULLABLE
shipped_at	DATETIME	Fecha de envío	NULLABLE
created	DATETIME	Fecha creación	NOT NULL, UTC
 
3.4 Entidad: pickup_slot
Franjas horarias disponibles para Click & Collect en cada ubicación.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
location_id	INT	Ubicación de recogida	FK stock_location.id, NOT NULL, INDEX
date	DATE	Fecha del slot	NOT NULL, INDEX
start_time	TIME	Hora inicio	NOT NULL
end_time	TIME	Hora fin	NOT NULL
capacity	INT	Capacidad máxima	NOT NULL, pedidos por slot
booked_count	INT	Reservas actuales	DEFAULT 0
is_available	BOOLEAN	Slot disponible	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
UNIQUE: (location_id, date, start_time)
3.5 Entidad: order_status_history
Historial de cambios de estado para auditoría y timeline del cliente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
order_id	INT	Pedido	FK retail_order.order_id, NOT NULL, INDEX
previous_state	VARCHAR(32)	Estado anterior	NULLABLE
new_state	VARCHAR(32)	Nuevo estado	NOT NULL
changed_by	INT	Usuario que cambió	FK users.uid, NULLABLE
changed_by_system	VARCHAR(32)	Sistema que cambió	NULLABLE, ej: 'carrier_webhook'
notes	TEXT	Notas del cambio	NULLABLE
metadata	JSON	Datos adicionales	NULLABLE
created	DATETIME	Fecha del cambio	NOT NULL, UTC
 
4. Estados de Pedido
4.1 Máquina de Estados
Estado	Descripción	Siguiente Estados Posibles
draft	Carrito en proceso	checkout, cancelled
checkout	En proceso de checkout	payment_pending, cancelled
payment_pending	Esperando pago	confirmed, payment_failed, cancelled
payment_failed	Pago fallido	payment_pending, cancelled
confirmed	Pago confirmado	processing, cancelled
processing	En preparación	ready_pickup, shipped, cancelled
ready_pickup	Listo para recoger (C&C)	collected, cancelled, expired
shipped	Enviado	in_transit, delivered, exception
in_transit	En tránsito	out_for_delivery, delivered, exception
out_for_delivery	En reparto	delivered, exception
delivered	Entregado	return_requested, completed
collected	Recogido (C&C)	return_requested, completed
completed	Completado	return_requested
return_requested	Devolución solicitada	return_approved, return_rejected
return_approved	Devolución aprobada	return_received
return_received	Devolución recibida	refunded
refunded	Reembolsado	(final)
cancelled	Cancelado	(final)
expired	Expirado (C&C no recogido)	(final)
4.2 Estados por Tipo de Fulfillment
Fulfillment	Estados Específicos	Tiempo Límite
Click & Collect	processing → ready_pickup → collected	48h para recoger
Ship-from-Store	processing → shipped → in_transit → delivered	3-5 días
Local Delivery	processing → out_for_delivery → delivered	Mismo día
In-Store (POS)	confirmed → completed (inmediato)	N/A
4.3 Acciones por Estado
// Acciones disponibles según estado actual $stateActions = [   'confirmed' => ['process', 'cancel'],   'processing' => ['mark_ready', 'ship', 'cancel'],   'ready_pickup' => ['mark_collected', 'extend_deadline', 'cancel'],   'shipped' => ['update_tracking', 'mark_delivered', 'report_exception'],   'delivered' => ['complete', 'initiate_return'],   'collected' => ['complete', 'initiate_return'],   'return_requested' => ['approve_return', 'reject_return'],   'return_received' => ['process_refund'], ];
 
5. Sistema Click & Collect
Click & Collect permite al cliente comprar online y recoger en la tienda física, combinando la comodidad del e-commerce con la inmediatez del comercio de proximidad.
5.1 Flujo Click & Collect
1. Cliente selecciona "Recoger en tienda" en checkout
2. Sistema muestra tiendas con stock disponible y horarios
3. Cliente selecciona tienda y franja horaria (si aplica)
4. Pago online (o selecciona "Pagar en tienda")
5. Sistema reserva stock en la ubicación seleccionada
6. Notificación al comercio: "Nuevo pedido Click & Collect"
7. Comercio prepara el pedido
8. Comercio marca "Listo para recoger" → genera código de recogida
9. Notificación al cliente con código: "Tu pedido está listo"
10. Cliente recoge mostrando código (QR o alfanumérico)
11. Comercio verifica código y entrega → marca "Recogido"
5.2 PickupService
<?php namespace Drupal\jaraba_orders\Service;  class PickupService {    // Disponibilidad   public function getAvailableLocations(array $items, float $lat, float $lng): array;   public function getAvailableSlots(int $locationId, \DateTime $date): array;   public function checkStockAtLocation(array $items, int $locationId): StockCheckResult;      // Reserva   public function reserveSlot(RetailOrder $order, int $locationId, int $slotId): bool;   public function releaseSlot(RetailOrder $order): void;      // Código de recogida   public function generatePickupCode(RetailOrder $order): string;   public function validatePickupCode(string $code, int $locationId): ?RetailOrder;      // Proceso   public function markReady(RetailOrder $order): void;   public function markCollected(RetailOrder $order, ?string $collectedBy = null): void;   public function extendDeadline(RetailOrder $order, int $hours = 24): void;   public function expireUnclaimedOrders(): int; }
5.3 Código de Recogida
// Formato del código de recogida // 8 caracteres alfanuméricos: XXXX-XXXX // Ejemplo: AB12-CD34  public function generatePickupCode(RetailOrder $order): string {   do {     $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));     $code = substr($code, 0, 4) . '-' . substr($code, 4, 4);   } while ($this->codeExists($code));      $order->pickup_code = $code;   $order->save();      return $code; }  // También se genera QR con el código para escaneo rápido
5.4 Políticas de Click & Collect
Política	Valor Default	Configurable
Tiempo preparación mínimo	2 horas	Por comercio
Tiempo máximo recogida	48 horas	Por comercio
Extensión permitida	1 vez, +24 horas	Por comercio
Pago en tienda permitido	Sí	Por comercio
Recordatorios automáticos	24h y 4h antes de expirar	Global
Acción al expirar	Cancelar + reembolso	Global
 
6. Sistema Ship-from-Store
Ship-from-Store permite a los comercios enviar pedidos directamente desde su tienda física, aprovechando el stock local y reduciendo tiempos de entrega.
6.1 Flujo Ship-from-Store
1. Pedido confirmado con fulfillment_type = 'ship_store'
2. FulfillmentRouter determina mejor ubicación con stock
3. Stock se reserva en la ubicación seleccionada
4. Notificación al comercio: "Nuevo pedido para enviar"
5. Comercio hace picking y packing
6. Comercio solicita etiqueta de envío desde el panel
7. Sistema genera etiqueta con carrier integrado
8. Comercio imprime etiqueta y prepara paquete
9. Carrier recoge en tienda o comercio lleva a punto
10. Tracking automático hasta entrega
6.2 ShipmentService
<?php namespace Drupal\jaraba_orders\Service;  class ShipmentService {    // Creación de envío   public function createShipment(RetailOrder $order, string $carrier, string $service): OrderShipment;   public function splitShipment(RetailOrder $order, array $itemGroups): array;      // Etiquetas   public function generateLabel(OrderShipment $shipment): LabelResult;   public function downloadLabel(OrderShipment $shipment, string $format = 'pdf'): string;   public function voidLabel(OrderShipment $shipment): bool;      // Tracking   public function updateTracking(OrderShipment $shipment): TrackingInfo;   public function getTrackingEvents(OrderShipment $shipment): array;      // Carriers   public function getAvailableCarriers(RetailOrder $order): array;   public function calculateShippingRates(RetailOrder $order): array;   public function schedulePickup(OrderShipment $shipment, \DateTime $date): bool; }
6.3 Carriers Integrados
Carrier	API	Servicios	Recogida en Tienda
MRW	REST API	Standard, Express, Bag	Sí, programable
SEUR	REST API	Classic, 24h, 10h	Sí, programable
GLS	Web Services	Business, Express	Sí, programable
Correos Express	REST API	Paq Premium, Hoy	Sí, puntos de entrega
Envialia	REST API	Standard, Urgente	Sí, programable
DHL	REST API	Express, Economy	Sí, programable
Entrega Propia	N/A	Local Delivery	N/A (comercio entrega)
6.4 Cálculo de Tarifa de Envío
// Factores para cálculo de tarifa $shippingFactors = [   'weight' => $shipment->weight_kg,   'dimensions' => $shipment->dimensions,  // Peso volumétrico   'origin_postal' => $origin->postal_code,   'destination_postal' => $destination->postal_code,   'service_level' => $selectedService,  // express, standard   'insurance' => $order->subtotal > 100,   'saturday_delivery' => $requestedDate->isSaturday(), ];  // El sistema consulta las APIs de cada carrier y muestra opciones al comercio // Tarifa final = Tarifa carrier + Margen plataforma (si aplica)
 
7. Fulfillment Router
El Fulfillment Router es el componente inteligente que decide desde qué ubicación se fulfilla cada pedido, optimizando stock, coste y tiempo de entrega.
7.1 FulfillmentRouterService
<?php namespace Drupal\jaraba_orders\Service;  class FulfillmentRouterService {    public function determineFulfillment(RetailOrder $order): FulfillmentDecision;      public function findBestLocation(     array $items,     Address $destination,     string $fulfillmentType   ): ?StockLocation;      public function canFulfillFromLocation(     array $items,      StockLocation $location   ): FulfillmentCapability;      public function calculateFulfillmentScore(     StockLocation $location,     Address $destination,     array $items   ): float;      public function allocateStock(     RetailOrder $order,      StockLocation $location   ): AllocationResult;      public function deallocateStock(RetailOrder $order): void; }
7.2 Algoritmo de Selección de Ubicación
El algoritmo calcula un score para cada ubicación candidata:
function calculateFulfillmentScore($location, $destination, $items) {   $score = 100;  // Base score      // 1. Disponibilidad de stock (-50 si no tiene todo)   $availability = $this->checkStockAvailability($location, $items);   if (!$availability->complete) {     $score -= 50;   }      // 2. Distancia al destino (-0.5 por cada 10km)   $distance = $this->calculateDistance($location, $destination);   $score -= ($distance / 10) * 0.5;      // 3. Prioridad de la ubicación (+10 por cada nivel)   $score += $location->priority * 10;      // 4. Capacidad de envío (+5 si puede enviar)   if ($location->is_ship_from) {     $score += 5;   }      // 5. Historial de fulfillment (+2 si buen track record)   $successRate = $this->getFulfillmentSuccessRate($location);   if ($successRate > 0.95) {     $score += 2;   }      return max(0, $score); }
7.3 Estrategias de Fulfillment
Estrategia	Descripción	Cuándo Usar
nearest_with_stock	Ubicación más cercana con stock completo	Default para envíos
highest_stock	Ubicación con mayor stock disponible	Productos de alta rotación
lowest_cost	Ubicación con menor coste de envío	Pedidos sensibles a precio
fastest_delivery	Ubicación que entrega antes	Express, mismo día
merchant_preferred	Ubicación preferida del comercio	Control manual
split_allowed	Permite dividir entre ubicaciones	Cuando no hay stock completo
7.4 Split Shipment
Si ninguna ubicación tiene todo el stock, el sistema puede dividir el pedido en múltiples envíos (split shipment). El cliente es notificado y puede elegir aceptar o cancelar.
// Ejemplo de split shipment $order = [   'item_1' => ['qty' => 2, 'allocated' => 'tienda_a'],  // Shipment 1   'item_2' => ['qty' => 1, 'allocated' => 'tienda_a'],  // Shipment 1   'item_3' => ['qty' => 3, 'allocated' => 'almacen'],   // Shipment 2 ];  // Resultado: 2 envíos separados con tracking independiente
 
8. Sistema de Devoluciones
8.1 Flujo de Devolución
1. Cliente solicita devolución desde su cuenta o app
2. Selecciona productos y motivo de devolución
3. Sistema valida política de devolución (plazo, estado)
4. Comercio aprueba o rechaza (automático si dentro de política)
5. Sistema genera etiqueta de devolución prepagada
6. Cliente envía productos o devuelve en tienda
7. Comercio recibe y verifica estado
8. Sistema procesa reembolso vía Stripe
8.2 ReturnService
<?php namespace Drupal\jaraba_orders\Service;  class ReturnService {    public function initiateReturn(RetailOrder $order, array $items, string $reason): ReturnRequest;   public function validateReturnEligibility(RetailOrder $order, array $items): ValidationResult;      public function approveReturn(ReturnRequest $return): void;   public function rejectReturn(ReturnRequest $return, string $reason): void;      public function generateReturnLabel(ReturnRequest $return): string;   public function markReceived(ReturnRequest $return, array $receivedItems): void;      public function processRefund(ReturnRequest $return): RefundResult;   public function restockItems(ReturnRequest $return): void; }
8.3 Motivos de Devolución
Código	Motivo	Requiere Verificación	Coste Devolución
wrong_size	Talla incorrecta	No	Gratis
wrong_color	Color diferente al esperado	No	Gratis
not_as_described	No coincide con descripción	No	Gratis
defective	Producto defectuoso	Sí	Gratis
damaged_shipping	Dañado en transporte	Sí (foto)	Gratis
changed_mind	Ya no lo quiero	No	Cliente paga (config)
late_delivery	Llegó tarde	No	Gratis
other	Otro motivo	Según caso	Según caso
8.4 Política de Devolución por Defecto
• Plazo: 30 días desde entrega
• Estado requerido: Sin usar, con etiquetas originales
• Reembolso: Mismo método de pago original
• Tiempo de reembolso: 5-10 días hábiles tras recepción
• Devolución en tienda: Permitida (reembolso inmediato)
 
9. Sistema de Notificaciones de Pedido
9.1 Notificaciones al Cliente
Evento	Canal	Contenido
Pedido confirmado	Email + Push	Resumen pedido, número, tiempo estimado
En preparación	Push	"Tu pedido está siendo preparado"
Listo para recoger (C&C)	Email + Push + SMS	Código recogida, dirección, horarios
Enviado	Email + Push	Tracking number, enlace seguimiento
En reparto	Push	"Tu pedido está en camino"
Entregado	Email + Push	Confirmación, solicitud reseña
Recordatorio recogida	Push + SMS	"No olvides recoger tu pedido" (24h y 4h)
Devolución aprobada	Email	Instrucciones, etiqueta devolución
Reembolso procesado	Email	Confirmación, tiempo llegada fondos
9.2 Notificaciones al Comercio
Evento	Canal	Acción Requerida
Nuevo pedido	Email + Push + Dashboard	Procesar pedido
Nuevo Click & Collect	Email + Push + Dashboard	Preparar para recogida
Recogida expirando	Push + Dashboard	Contactar cliente o cancelar
Devolución solicitada	Email + Dashboard	Aprobar/rechazar
Devolución recibida	Dashboard	Verificar y procesar reembolso
Pedido cancelado	Email + Dashboard	Reponer stock si aplica
Excepción en envío	Email + Push	Contactar carrier/cliente
9.3 Plantillas de Email
// Plantillas de email disponibles $emailTemplates = [   'order_confirmed' => [     'subject' => 'Pedido #{order_number} confirmado',     'variables' => ['order', 'customer', 'items', 'totals', 'shipping'],   ],   'order_shipped' => [     'subject' => 'Tu pedido #{order_number} está en camino',     'variables' => ['order', 'shipment', 'tracking_url', 'estimated_delivery'],   ],   'pickup_ready' => [     'subject' => '¡Tu pedido está listo para recoger!',     'variables' => ['order', 'pickup_code', 'location', 'deadline', 'qr_code'],   ],   // ... más plantillas ];  // Las plantillas son editables por tenant y usan Twig
 
10. APIs REST
10.1 Endpoints de Pedidos (Comerciante)
Método	Endpoint	Descripción	Auth
GET	/api/v1/orders	Listar pedidos del comercio	Merchant
GET	/api/v1/orders/{id}	Detalle de pedido	Merchant
PATCH	/api/v1/orders/{id}/status	Cambiar estado	Merchant
POST	/api/v1/orders/{id}/process	Iniciar procesamiento	Merchant
POST	/api/v1/orders/{id}/ready-pickup	Marcar listo C&C	Merchant
POST	/api/v1/orders/{id}/collected	Marcar recogido	Merchant
POST	/api/v1/orders/{id}/ship	Crear envío	Merchant
GET	/api/v1/orders/{id}/shipments	Listar envíos	Merchant
POST	/api/v1/orders/{id}/cancel	Cancelar pedido	Merchant
POST	/api/v1/orders/{id}/refund	Procesar reembolso	Merchant
10.2 Endpoints de Pedidos (Cliente)
Método	Endpoint	Descripción	Auth
GET	/api/v1/my/orders	Mis pedidos	Customer
GET	/api/v1/my/orders/{id}	Detalle de mi pedido	Customer
GET	/api/v1/my/orders/{id}/tracking	Tracking del pedido	Customer
POST	/api/v1/my/orders/{id}/return	Solicitar devolución	Customer
GET	/api/v1/my/orders/{id}/invoice	Descargar factura	Customer
10.3 Endpoints de Click & Collect
Método	Endpoint	Descripción	Auth
GET	/api/v1/pickup/locations	Tiendas con C&C disponible	Público
GET	/api/v1/pickup/locations/{id}/slots	Slots disponibles	Público
POST	/api/v1/pickup/validate-code	Validar código recogida	Merchant
GET	/api/v1/my/pickups/pending	Mis recogidas pendientes	Customer
10.4 Webhooks Entrantes
Origen	Evento	Acción
Carrier (MRW, SEUR...)	tracking_update	Actualizar estado shipment
Carrier	delivered	Marcar como entregado
Carrier	exception	Notificar excepción
Stripe	payment_intent.succeeded	Confirmar pedido
Stripe	charge.refunded	Actualizar estado reembolso
 
11. Flujos de Automatización (ECA)
11.1 ECA-ORD-001: Pedido Confirmado
Trigger: Order state cambia a 'confirmed'
1. Convertir reservas de stock en asignaciones firmes
2. Ejecutar FulfillmentRouter para determinar ubicación
3. Enviar email de confirmación al cliente
4. Notificar al comercio (push + dashboard)
5. Registrar en order_status_history
11.2 ECA-ORD-002: Click & Collect Listo
Trigger: Order state cambia a 'ready_pickup'
1. Generar código de recogida
2. Calcular deadline de recogida (pickup_deadline)
3. Enviar email + push + SMS al cliente
4. Programar recordatorios (24h y 4h antes de expirar)
11.3 ECA-ORD-003: Pedido Enviado
Trigger: Order state cambia a 'shipped'
1. Decrementar stock de la ubicación de fulfillment
2. Enviar email con tracking al cliente
3. Programar polling de tracking (cada 4h)
4. Actualizar métricas de envío del comercio
11.4 ECA-ORD-004: C&C Expirado
Trigger: Cron detecta pickup_deadline < NOW() y state = 'ready_pickup'
1. Cambiar estado a 'expired'
2. Liberar stock reservado
3. Procesar reembolso automático
4. Notificar a cliente y comercio
5. Registrar en métricas de abandono
11.5 ECA-ORD-005: Pedido Completado
Trigger: Order state cambia a 'completed'
1. Actualizar estadísticas del comercio
2. Actualizar estadísticas del cliente (total compras)
3. Programar solicitud de reseña (24h después)
4. Asignar puntos de fidelización si aplica
5. Webhook order.completed
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidad retail_order extendida. Máquina de estados. OrderService básico. APIs de pedidos.	62_Commerce_Core
Sprint 2	Semana 3-4	Sistema Click & Collect completo. PickupService. Pickup slots. Código de recogida.	Sprint 1
Sprint 3	Semana 5-6	FulfillmentRouter. Algoritmo de selección. Stock allocation. Split shipment.	Sprint 2
Sprint 4	Semana 7-8	ShipmentService. Integración carriers (MRW, SEUR). Etiquetas. Tracking webhooks.	Sprint 3
Sprint 5	Semana 9-10	ReturnService completo. Flujo de devolución. Reembolsos via Stripe.	Sprint 4
Sprint 6	Semana 11-12	Sistema de notificaciones. Plantillas email. Flujos ECA. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 2 (Click & Collect)
✓ Cliente puede seleccionar tienda y slot en checkout
✓ Comercio recibe notificación de nuevo C&C
✓ Comercio puede marcar como listo
✓ Código de recogida se genera y envía
✓ Validación de código funciona
12.2 Dependencias Externas
• Drupal Commerce 3.x (order, order_item)
• Stripe PHP SDK ^10.0 (refunds)
• APIs de carriers: MRW, SEUR, GLS, Correos Express
• TCPDF / Dompdf (etiquetas, facturas)
• Drupal State Machine module
--- Fin del Documento ---
67_ComercioConecta_Order_System_v1.docx | Jaraba Impact Platform | Enero 2026
