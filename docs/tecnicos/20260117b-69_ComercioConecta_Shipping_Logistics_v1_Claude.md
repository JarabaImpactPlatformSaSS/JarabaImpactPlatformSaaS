SISTEMA SHIPPING & LOGISTICS
Gestión de Envíos y Logística Multi-Carrier
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	69_ComercioConecta_Shipping_Logistics
Dependencias:	62_Commerce_Core, 67_Order_System, 68_Checkout_Flow
Base:	51_AgroConecta_Shipping_Logistics (~60% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Envíos y Logística para ComercioConecta. El sistema integra los principales carriers españoles (MRW, SEUR, GLS, Correos Express, Envialia) proporcionando cotización de tarifas en tiempo real, generación de etiquetas, y tracking unificado.
1.1 Objetivos del Sistema
• Integración con 5+ carriers españoles mediante APIs oficiales
• Cotización de tarifas en tiempo real durante checkout
• Generación automática de etiquetas de envío (PDF/ZPL)
• Tracking unificado con webhooks y polling
• Soporte para envíos express, mismo día, y estándar
• Gestión de puntos de recogida y lockers
• Devoluciones con etiqueta prepagada
1.2 Carriers Soportados
Carrier	Cuota Mercado ES	Servicios Principales	API
MRW	~15%	MRW Bag, Express, Urgente 14h	REST API v2
SEUR	~18%	SEUR 24, SEUR 48, SEUR 10	REST API
GLS	~12%	Business, Express, Flex Delivery	Web Services
Correos Express	~20%	Paq Premium, Paq Hoy, ePaq	REST API v2
Envialia	~8%	E-24, E-48, Retorno	REST API
DHL Express	~10%	Express Worldwide, Economy	REST API
UPS	~7%	Standard, Express Saver	REST API
Nacex	~5%	Nacex 19h, e-Nacex	SOAP/REST
Entrega Propia	N/A	Local Delivery	N/A
1.3 Diferencias vs. AgroConecta Shipping
Aspecto	AgroConecta	ComercioConecta
Productos	Perecederos, frío	No perecederos, retail
Urgencia	Crítica (frescura)	Flexible (estándar/express)
Peso típico	5-30 kg	0.5-5 kg
Temperatura	Cadena de frío	Ambiente
Embalaje	Específico alimentario	Estándar retail
Puntos recogida	Limitados	Amplia red de lockers
Devoluciones	Complicadas	Flujo estándar
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────┐ │                    SHIPPING & LOGISTICS SYSTEM                      │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Shipping   │  │    Rate      │  │    Label                 │  │ │  │   Manager    │──│   Calculator │──│    Generator             │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Carrier    │  │   Tracking   │  │    Pickup Points         │  │ │  │   Connector  │──│   Service    │──│    Service               │  │ │  │  (Adapter)   │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Return     │  │   Webhook    │  │    Zone                  │  │ │  │   Shipment   │──│   Handler    │──│    Manager               │  │ │  │   Service    │  │              │  │  (Tarifas por zona)      │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └───────────────────────────────────────────────────────────────────┘                               │         ┌─────────────────────┼─────────────────────┐         ▼                     ▼                     ▼  ┌────────────┐        ┌────────────┐        ┌────────────┐  │    MRW     │        │    SEUR    │        │    GLS     │  │  Connector │        │  Connector │        │  Connector │  └────────────┘        └────────────┘        └────────────┘         │                     │                     │         ▼                     ▼                     ▼  ┌────────────┐        ┌────────────┐        ┌────────────┐  │  Correos   │        │  Envialia  │        │    DHL     │  │  Express   │        │  Connector │        │  Connector │  └────────────┘        └────────────┘        └────────────┘
2.2 Patrón Adapter para Carriers
Cada carrier implementa una interfaz común (CarrierConnectorInterface) permitiendo añadir nuevos carriers sin modificar el código del sistema:
interface CarrierConnectorInterface {   // Identificación   public function getCarrierCode(): string;   public function getCarrierName(): string;   public function getSupportedServices(): array;      // Tarifas   public function getRates(ShipmentRequest $request): array;   public function validateAddress(Address $address): ValidationResult;      // Envíos   public function createShipment(ShipmentRequest $request): ShipmentResponse;   public function cancelShipment(string $trackingNumber): bool;   public function getLabel(string $trackingNumber, string $format): string;      // Tracking   public function getTrackingInfo(string $trackingNumber): TrackingInfo;   public function registerWebhook(string $callbackUrl): bool;   public function handleWebhook(array $payload): TrackingEvent;      // Puntos de recogida   public function getPickupPoints(Address $near, float $radiusKm): array;      // Recogidas   public function schedulePickup(PickupRequest $request): PickupResponse;   public function cancelPickup(string $pickupId): bool; }
 
3. Entidades del Sistema
3.1 Entidad: shipping_method
Métodos de envío disponibles configurados por tenant/comercio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio (null=tenant)	FK, NULLABLE
tenant_id	INT	Tenant	FK, NOT NULL
carrier	VARCHAR(32)	Código del carrier	ENUM: mrw|seur|gls|correos|envialia|dhl|ups|self
service_code	VARCHAR(64)	Código de servicio	NOT NULL, ej: 'seur_24'
name	VARCHAR(128)	Nombre visible	NOT NULL, ej: 'Envío Express 24h'
description	TEXT	Descripción	NULLABLE
delivery_estimate	VARCHAR(32)	Tiempo estimado	ej: '24-48h', '3-5 días'
price_type	VARCHAR(16)	Tipo de precio	ENUM: flat|weight|zone|carrier_rate
flat_rate	DECIMAL(10,2)	Tarifa plana	NULLABLE, si price_type=flat
free_shipping_threshold	DECIMAL(10,2)	Envío gratis desde	NULLABLE
handling_fee	DECIMAL(10,2)	Fee de manipulación	DEFAULT 0
min_weight	DECIMAL(8,3)	Peso mínimo kg	DEFAULT 0
max_weight	DECIMAL(8,3)	Peso máximo kg	NULLABLE
min_order_value	DECIMAL(10,2)	Pedido mínimo	DEFAULT 0
max_order_value	DECIMAL(10,2)	Pedido máximo	NULLABLE
allowed_zones	JSON	Zonas permitidas	Array de zone_id, NULLABLE
excluded_postcodes	JSON	CPs excluidos	Array de strings
is_express	BOOLEAN	Es express	DEFAULT FALSE
requires_signature	BOOLEAN	Requiere firma	DEFAULT FALSE
allows_pickup_points	BOOLEAN	Permite puntos recogida	DEFAULT TRUE
sort_order	INT	Orden de display	DEFAULT 0
is_active	BOOLEAN	Método activo	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
 
3.2 Entidad: shipping_zone
Zonas geográficas para cálculo de tarifas diferenciadas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK, NOT NULL
name	VARCHAR(128)	Nombre de zona	NOT NULL, ej: 'Península'
zone_type	VARCHAR(16)	Tipo de zona	ENUM: country|region|postcode_range|postcode_list
countries	JSON	Países incluidos	Array ISO 3166-1, ej: ['ES', 'PT']
regions	JSON	Regiones/provincias	Array, ej: ['ES-AN', 'ES-CT']
postcode_from	VARCHAR(10)	CP desde	NULLABLE, para rangos
postcode_to	VARCHAR(10)	CP hasta	NULLABLE, para rangos
postcodes	JSON	CPs específicos	Array de strings
is_default	BOOLEAN	Zona por defecto	DEFAULT FALSE
sort_order	INT	Prioridad matching	DEFAULT 0, mayor = primero
3.3 Entidad: shipping_rate
Tarifas por peso/zona para métodos de envío con price_type = 'zone' o 'weight'.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
method_id	INT	Método de envío	FK shipping_method.id, NOT NULL
zone_id	INT	Zona aplicable	FK shipping_zone.id, NULLABLE
weight_from	DECIMAL(8,3)	Peso desde kg	NOT NULL
weight_to	DECIMAL(8,3)	Peso hasta kg	NOT NULL
price	DECIMAL(10,2)	Precio en EUR	NOT NULL
price_per_kg	DECIMAL(10,2)	Precio adicional/kg	NULLABLE, para exceso
UNIQUE: (method_id, zone_id, weight_from)
3.4 Entidad: carrier_account
Credenciales de API de cada carrier por comercio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
merchant_id	INT	Comercio	FK merchant_profile.id, NOT NULL
carrier	VARCHAR(32)	Código del carrier	NOT NULL
account_number	VARCHAR(64)	Número de cuenta/cliente	NULLABLE
api_key	VARCHAR(255)	API Key (encrypted)	NULLABLE
api_secret	VARCHAR(255)	API Secret (encrypted)	NULLABLE
username	VARCHAR(128)	Usuario API	NULLABLE
password	VARCHAR(255)	Password (encrypted)	NULLABLE
contract_id	VARCHAR(64)	ID de contrato	NULLABLE
sender_address	JSON	Dirección remitente default	NOT NULL
webhook_secret	VARCHAR(128)	Secret para webhooks	NULLABLE
is_sandbox	BOOLEAN	Modo pruebas	DEFAULT FALSE
is_active	BOOLEAN	Cuenta activa	DEFAULT TRUE
last_used_at	DATETIME	Último uso	NULLABLE
created	DATETIME	Fecha creación	NOT NULL
 
3.5 Entidad: pickup_point
Puntos de recogida y lockers de los diferentes carriers.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
carrier	VARCHAR(32)	Carrier propietario	NOT NULL, INDEX
external_id	VARCHAR(64)	ID en el carrier	NOT NULL
name	VARCHAR(255)	Nombre del punto	NOT NULL
type	VARCHAR(32)	Tipo de punto	ENUM: store|locker|post_office|partner
address_line1	VARCHAR(255)	Dirección	NOT NULL
address_line2	VARCHAR(255)	Dirección adicional	NULLABLE
city	VARCHAR(128)	Ciudad	NOT NULL, INDEX
postcode	VARCHAR(10)	Código postal	NOT NULL, INDEX
region	VARCHAR(64)	Provincia/región	NULLABLE
country	VARCHAR(2)	País ISO	DEFAULT 'ES'
latitude	DECIMAL(10,8)	Latitud	NOT NULL
longitude	DECIMAL(11,8)	Longitud	NOT NULL
opening_hours	JSON	Horarios apertura	OpeningHoursSpecification
phone	VARCHAR(20)	Teléfono	NULLABLE
max_weight_kg	DECIMAL(8,3)	Peso máximo	NULLABLE
max_dimensions	JSON	Dimensiones máximas	{l, w, h} en cm
has_locker	BOOLEAN	Tiene locker 24h	DEFAULT FALSE
is_active	BOOLEAN	Punto activo	DEFAULT TRUE
last_sync	DATETIME	Última sincronización	NOT NULL
UNIQUE: (carrier, external_id). INDEX SPATIAL: (latitude, longitude) para búsquedas geográficas.
3.6 Entidad: tracking_event
Eventos de tracking recibidos de los carriers.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
shipment_id	INT	Envío	FK order_shipment.id, NOT NULL, INDEX
event_code	VARCHAR(32)	Código normalizado	NOT NULL
carrier_event_code	VARCHAR(64)	Código original carrier	NOT NULL
description	VARCHAR(500)	Descripción del evento	NOT NULL
location	VARCHAR(255)	Ubicación del evento	NULLABLE
event_time	DATETIME	Fecha/hora del evento	NOT NULL
raw_data	JSON	Datos originales	NULLABLE
created	DATETIME	Fecha registro	NOT NULL, UTC
 
4. Servicios Principales
4.1 ShippingService
<?php namespace Drupal\jaraba_shipping\Service;  class ShippingService {    // Cotización   public function getAvailableMethods(Cart $cart, Address $destination): array;   public function calculateRate(ShippingMethod $method, Cart $cart, Address $destination): ?ShippingRate;   public function getBestRate(Cart $cart, Address $destination): ?ShippingRate;      // Creación de envío   public function createShipment(RetailOrder $order): OrderShipment;   public function generateLabel(OrderShipment $shipment, string $format = 'pdf'): string;   public function schedulePickup(OrderShipment $shipment, \DateTime $date): PickupConfirmation;      // Gestión   public function cancelShipment(OrderShipment $shipment): bool;   public function updateTracking(OrderShipment $shipment): TrackingInfo;   public function getTrackingUrl(OrderShipment $shipment): string;      // Devoluciones   public function createReturnShipment(ReturnRequest $return): OrderShipment;   public function generateReturnLabel(OrderShipment $shipment): string;      // Utilidades   public function validateAddress(Address $address): AddressValidationResult;   public function calculateVolumetricWeight(array $dimensions): float;   public function determineZone(Address $destination): ?ShippingZone; }
4.2 RateCalculatorService
<?php namespace Drupal\jaraba_shipping\Service;  class RateCalculatorService {    public function calculate(ShippingMethod $method, ShipmentRequest $request): RateResult;      // Estrategias de cálculo   public function calculateFlat(ShippingMethod $method, ShipmentRequest $request): float;   public function calculateByWeight(ShippingMethod $method, ShipmentRequest $request): float;   public function calculateByZone(ShippingMethod $method, ShipmentRequest $request): float;   public function fetchCarrierRate(ShippingMethod $method, ShipmentRequest $request): float;      // Ajustes   public function applyFreeShipping(ShippingMethod $method, float $orderTotal): bool;   public function applyHandlingFee(ShippingMethod $method, float $baseRate): float;   public function applyFuelSurcharge(string $carrier, float $baseRate): float;      // Peso   public function calculateTotalWeight(array $items): float;   public function calculateVolumetricWeight(array $items): float;   public function getBillableWeight(array $items): float;  // Mayor de real/volumétrico }
4.3 Cálculo de Peso Volumétrico
// Fórmula estándar de peso volumétrico // Peso volumétrico (kg) = (Largo × Ancho × Alto en cm) / Factor // Factor típico: 5000 para courier, 6000 para paquetería  public function calculateVolumetricWeight(array $dimensions, int $factor = 5000): float {   $length = $dimensions['length'] ?? 0;  // cm   $width = $dimensions['width'] ?? 0;    // cm   $height = $dimensions['height'] ?? 0;  // cm      if ($length <= 0 || $width <= 0 || $height <= 0) {     return 0;   }      return ($length * $width * $height) / $factor; }  public function getBillableWeight(float $realWeight, array $dimensions): float {   $volumetricWeight = $this->calculateVolumetricWeight($dimensions);   return max($realWeight, $volumetricWeight);  // El mayor de los dos }
 
5. Conectores de Carriers
5.1 MRW Connector
Aspecto	Detalle
API	REST API v2 (SaaS)
Autenticación	API Key + Client Secret
Servicios	MRW Bag (1kg), Express, Urgente 14h, Ecommerce
Etiquetas	PDF A4, PDF 10x15, ZPL
Tracking	Webhooks + polling
Puntos recogida	Red de puntos SEUR-MRW
Sandbox	Sí, entorno de pruebas disponible
// MRW API - Crear envío POST https://api.mrw.es/v2/shipments Headers:   Authorization: Bearer {access_token}   Content-Type: application/json  Body: {   "serviceCode": "0005",  // MRW Express   "deliveryAddress": {     "name": "Juan García",     "street": "Calle Mayor 123",     "city": "Madrid",     "postalCode": "28001",     "country": "ES",     "phone": "+34612345678"   },   "packages": [{     "weight": 2.5,     "length": 30, "width": 20, "height": 15   }],   "reference": "ORD-2026-001234",   "cashOnDelivery": 0,   "notifications": {     "email": "cliente@email.com",     "sms": true   } }
5.2 SEUR Connector
Aspecto	Detalle
API	REST API + SOAP (legacy)
Autenticación	Usuario + Password + CIT (código integrador)
Servicios	SEUR 24, SEUR 48, SEUR 10, SEUR Frío
Etiquetas	PDF, ZPL, EPL
Tracking	Webhooks (preferido), polling cada 4h
Puntos recogida	Puntos SEUR + Lockers
Sandbox	Entorno de homologación requerido
// SEUR API - Crear envío POST https://api.seur.com/shipments Headers:   X-CIT: {codigo_integrador}   Authorization: Basic {base64(user:pass)}  Body: {   "product": "24",  // SEUR 24   "sender": { ... },   "receiver": {     "name": "Juan García",     "address": "Calle Mayor 123",     "postalCode": "28001",     "city": "Madrid",     "country": "ES",     "phone": "612345678",     "email": "cliente@email.com"   },   "parcels": [{     "weight": 2.5,     "height": 15, "width": 20, "length": 30   }],   "reference": "ORD-2026-001234" }
 
5.3 GLS Connector
Aspecto	Detalle
API	Web Services SOAP + REST (parcial)
Autenticación	Usuario contrato + Password
Servicios	BusinessParcel, ExpressParcel, EuroBusinessParcel
Etiquetas	PDF, ZPL
Tracking	Polling recomendado (webhooks limitados)
Puntos recogida	ParcelShops GLS
Flex Delivery	Cliente elige fecha/hora
5.4 Correos Express Connector
Aspecto	Detalle
API	REST API v2 (PreRegistro)
Autenticación	Usuario + Password + Código cliente
Servicios	Paq Premium, Paq Estándar, Paq Hoy, ePaq
Etiquetas	PDF (A4, 10x15), ZPL
Tracking	Webhooks + API consulta
Puntos recogida	Oficinas Correos + CityPaq lockers
Cobertura	La más amplia en España rural
// Correos Express - PreRegistro POST https://preregistros.correos.es/preregistroenvios Headers:   Content-Type: application/json   Authorization: Basic {credentials}  Body: {   "codigoProducto": "S0132",  // Paq Premium   "referenciaCliente": "ORD-2026-001234",   "destinatario": {     "nombre": "Juan García",     "direccion": "Calle Mayor 123",     "codigoPostal": "28001",     "localidad": "Madrid",     "telefono": "612345678",     "email": "cliente@email.com"   },   "bultos": [{     "peso": 2.5,     "alto": 15, "ancho": 20, "largo": 30   }] }
5.5 Envialia Connector
Aspecto	Detalle
API	REST API
Autenticación	API Key
Servicios	E-24, E-48, E-72, Retorno
Etiquetas	PDF
Tracking	API consulta (webhooks en desarrollo)
Puntos recogida	Limitados
Especialidad	Buen precio para paquetería estándar
 
6. Sistema de Tracking Unificado
6.1 TrackingService
<?php namespace Drupal\jaraba_shipping\Service;  class TrackingService {    public function getTracking(OrderShipment $shipment): TrackingInfo;   public function refreshTracking(OrderShipment $shipment): TrackingInfo;   public function refreshAllPendingShipments(): int;      public function handleWebhook(string $carrier, array $payload): void;   public function normalizeEvent(string $carrier, array $rawEvent): TrackingEvent;      public function getPublicTrackingUrl(OrderShipment $shipment): string;   public function getCarrierTrackingUrl(OrderShipment $shipment): string;      public function subscribeToUpdates(OrderShipment $shipment, string $email): void;   public function notifyStatusChange(OrderShipment $shipment, TrackingEvent $event): void; }
6.2 Códigos de Estado Normalizados
El sistema normaliza los códigos de cada carrier a un conjunto común:
Código	Descripción	MRW	SEUR	GLS	Correos
pending	Etiqueta creada	00	01	0.0	A0000
picked_up	Recogido	01	03	1.0	B0000
in_transit	En tránsito	02	05	2.0	C0000
out_for_delivery	En reparto	03	07	5.0	E0000
delivered	Entregado	04	09	6.0	I0000
attempted	Intento fallido	05	10	7.0	H0000
exception	Incidencia	06	11	9.0	J0000
returned	Devuelto	07	13	10.0	K0000
cancelled	Cancelado	99	99	99.0	Z0000
6.3 Webhooks de Tracking
// Endpoint para recibir webhooks de carriers POST /api/v1/shipping/webhook/{carrier}  // Validación de firma (ejemplo MRW) public function validateWebhookSignature(Request $request, string $carrier): bool {   $payload = $request->getContent();   $signature = $request->headers->get('X-Webhook-Signature');   $secret = $this->getCarrierWebhookSecret($carrier);      $expectedSignature = hash_hmac('sha256', $payload, $secret);      return hash_equals($expectedSignature, $signature); }  // Procesamiento del webhook public function handleWebhook(string $carrier, array $payload): void {   $trackingNumber = $this->extractTrackingNumber($carrier, $payload);   $shipment = $this->findShipmentByTracking($trackingNumber);      if (!$shipment) {     throw new ShipmentNotFoundException();   }      $event = $this->normalizeEvent($carrier, $payload);   $this->saveTrackingEvent($shipment, $event);   $this->updateShipmentStatus($shipment, $event);   $this->notifyStatusChange($shipment, $event); }
 
6.4 Polling de Tracking
Para carriers sin webhooks o como backup, el sistema hace polling periódico:
// Cron job: tracking_refresh // Frecuencia: cada 4 horas para envíos en tránsito // Frecuencia: cada 15 min para envíos en reparto  public function refreshAllPendingShipments(): int {   $refreshed = 0;      // Envíos en reparto: prioridad alta   $outForDelivery = $this->getShipmentsByStatus('out_for_delivery');   foreach ($outForDelivery as $shipment) {     $this->refreshTracking($shipment);     $refreshed++;   }      // Envíos en tránsito: prioridad normal   $inTransit = $this->getShipmentsByStatus(['picked_up', 'in_transit']);   foreach ($inTransit as $shipment) {     // Rate limiting: no más de 100 requests/min por carrier     $this->rateLimiter->wait($shipment->carrier);     $this->refreshTracking($shipment);     $refreshed++;   }      return $refreshed; }
6.5 Página de Tracking Pública
Los clientes pueden ver el tracking en una URL pública sin necesidad de login:
// URL de tracking público // https://comercioconecta.es/tracking/{order_number}/{verification_token}  // El token se genera a partir del email del cliente $token = substr(hash('sha256', $order->email . $order->order_number), 0, 8);  // Vista de tracking muestra: // - Timeline visual de eventos // - Mapa con ubicación actual (si disponible) // - Fecha estimada de entrega // - Enlace a tracking del carrier // - Botón para contactar soporte
 
7. Sistema de Puntos de Recogida
7.1 PickupPointService
<?php namespace Drupal\jaraba_shipping\Service;  class PickupPointService {    // Búsqueda   public function findNearby(float $lat, float $lng, float $radiusKm = 5): array;   public function findByPostcode(string $postcode, int $limit = 10): array;   public function findByCarrier(string $carrier, string $postcode): array;      // Filtros   public function filterByMaxWeight(array $points, float $weight): array;   public function filterByMaxDimensions(array $points, array $dimensions): array;   public function filterByLocker(array $points, bool $lockerOnly = false): array;      // Sincronización   public function syncCarrierPoints(string $carrier): SyncResult;   public function syncAllCarriers(): array;      // Validación   public function isPointAvailable(PickupPoint $point): bool;   public function canAcceptPackage(PickupPoint $point, OrderShipment $shipment): bool; }
7.2 Tipos de Puntos de Recogida
Tipo	Descripción	Horario	Carriers
store	Tienda colaboradora	Horario comercial	SEUR, MRW, GLS
locker	Locker automático 24h	24/7	Correos CityPaq, Amazon Locker
post_office	Oficina de correos	L-S mañanas	Correos Express
partner	Punto asociado (gasolinera, etc.)	Variable	Varios
7.3 Mapa de Puntos de Recogida
// Componente React: PickupPointMap import { GoogleMap, Marker, InfoWindow } from '@react-google-maps/api';  export function PickupPointMap({ center, points, onSelect }) {   const [selected, setSelected] = useState(null);      return (     <GoogleMap center={center} zoom={13}>       {points.map(point => (         <Marker           key={point.id}           position={{ lat: point.latitude, lng: point.longitude }}           icon={getMarkerIcon(point.type, point.carrier)}           onClick={() => setSelected(point)}         />       ))}              {selected && (         <InfoWindow           position={{ lat: selected.latitude, lng: selected.longitude }}           onCloseClick={() => setSelected(null)}         >           <PickupPointCard              point={selected}              onSelect={() => onSelect(selected)}            />         </InfoWindow>       )}     </GoogleMap>   ); }
7.4 Sincronización de Puntos
Los puntos de recogida se sincronizan periódicamente desde cada carrier:
// Cron job: sync_pickup_points // Frecuencia: diaria a las 03:00  public function syncAllCarriers(): array {   $results = [];      $carriers = ['seur', 'mrw', 'gls', 'correos'];      foreach ($carriers as $carrier) {     $connector = $this->getConnector($carrier);     $points = $connector->getAllPickupPoints();          $created = 0;     $updated = 0;     $deactivated = 0;          foreach ($points as $pointData) {       $existing = $this->findByExternalId($carrier, $pointData['id']);              if ($existing) {         $this->updatePoint($existing, $pointData);         $updated++;       } else {         $this->createPoint($carrier, $pointData);         $created++;       }     }          // Desactivar puntos que ya no existen     $deactivated = $this->deactivateMissing($carrier, $points);          $results[$carrier] = compact('created', 'updated', 'deactivated');   }      return $results; }
 
8. Generación de Etiquetas
8.1 LabelService
<?php namespace Drupal\jaraba_shipping\Service;  class LabelService {    // Generación   public function generate(OrderShipment $shipment, string $format = 'pdf'): string;   public function generateBatch(array $shipments, string $format = 'pdf'): string;   public function generateReturnLabel(OrderShipment $shipment): string;      // Formatos   public function toPdf(string $labelData, string $size = '10x15'): string;   public function toZpl(string $labelData): string;  // Zebra   public function toEpl(string $labelData): string;  // Eltron   public function toPng(string $labelData): string;  // Preview      // Storage   public function store(OrderShipment $shipment, string $labelContent): string;  // Returns URL   public function getStoredLabel(OrderShipment $shipment): ?string;      // Impresión   public function sendToPrinter(string $labelUrl, string $printerId): bool; }
8.2 Formatos de Etiqueta
Formato	Tamaño	Uso	Impresoras
PDF A4	210×297mm	Impresión doméstica	Cualquier impresora
PDF 10×15	100×150mm	Térmica estándar	Zebra, Brother
PDF 10×10	100×100mm	Etiqueta cuadrada	Varias
ZPL	Variable	Impresión directa Zebra	Zebra ZD420, GK420
EPL	Variable	Impresión directa Eltron	Eltron LP2844
PNG	300dpi	Preview en pantalla	N/A
8.3 Contenido de la Etiqueta
// Elementos obligatorios en etiqueta de envío [   'barcode' => '1234567890123',        // Código de barras (Code128 o 2D)   'tracking_number' => 'MRW123456789', // Número de seguimiento   'carrier_logo' => 'mrw_logo.png',    // Logo del carrier      'sender' => [     'name' => 'Moda Local Santaella',     'address' => 'Calle Comercio 15',     'postcode' => '14940',     'city' => 'Santaella',     'phone' => '957123456'   ],      'receiver' => [     'name' => 'Juan García',     'address' => 'Calle Mayor 123, 2ºB',     'postcode' => '28001',     'city' => 'Madrid',     'phone' => '612345678'   ],      'service' => 'Express 24h',   'weight' => '2.5 kg',   'reference' => 'ORD-2026-001234',   'packages' => '1/1',                 // Bulto X de Y   'route_code' => 'MAD-001',           // Código de ruta   'date' => '2026-01-17' ]
 
9. Gestión de Devoluciones
9.1 ReturnShipmentService
<?php namespace Drupal\jaraba_shipping\Service;  class ReturnShipmentService {    // Creación   public function createReturnShipment(ReturnRequest $return): OrderShipment;   public function generateReturnLabel(ReturnRequest $return): string;      // Opciones de devolución   public function getReturnOptions(RetailOrder $order): array;   public function getDropoffPoints(Address $customerAddress): array;   public function schedulePickup(ReturnRequest $return, \DateTime $date): PickupConfirmation;      // Tracking   public function trackReturn(OrderShipment $returnShipment): TrackingInfo;   public function markAsReceived(OrderShipment $returnShipment): void; }
9.2 Opciones de Devolución
Opción	Descripción	Coste Cliente	Disponibilidad
Etiqueta prepagada	Cliente imprime y lleva a punto	Gratis / Comercio paga	Siempre
Recogida a domicilio	Carrier recoge en casa cliente	5-10€ / Gratis según política	Bajo pedido
Devolución en tienda	Cliente devuelve en tienda física	Gratis	Si tienda disponible
Punto de entrega	Cliente lleva a punto de recogida	Gratis	Red de puntos
9.3 Flujo de Devolución con Envío
1. Cliente solicita devolución → se aprueba
2. Sistema genera etiqueta de devolución (carrier del envío original o alternativo)
3. Cliente recibe email con etiqueta PDF + instrucciones
4. Cliente imprime etiqueta y lleva a punto de entrega (o recogida a domicilio)
5. Tracking de devolución activo → comercio ve el progreso
6. Paquete llega al comercio → comercio verifica estado
7. Si todo OK → procesar reembolso
 
10. APIs REST
10.1 Endpoints de Cotización
Método	Endpoint	Descripción	Auth
POST	/api/v1/shipping/rates	Obtener tarifas disponibles	Session
GET	/api/v1/shipping/methods	Listar métodos de envío	Público
GET	/api/v1/shipping/zones	Listar zonas de envío	Merchant
POST	/api/v1/shipping/validate-address	Validar dirección	Session
10.2 Endpoints de Envíos
Método	Endpoint	Descripción	Auth
POST	/api/v1/shipments	Crear envío	Merchant
GET	/api/v1/shipments/{id}	Detalle de envío	Merchant
GET	/api/v1/shipments/{id}/label	Descargar etiqueta	Merchant
POST	/api/v1/shipments/{id}/cancel	Cancelar envío	Merchant
GET	/api/v1/shipments/{id}/tracking	Obtener tracking	Merchant
POST	/api/v1/shipments/batch-labels	Generar etiquetas batch	Merchant
10.3 Endpoints de Puntos de Recogida
Método	Endpoint	Descripción	Auth
GET	/api/v1/pickup-points	Buscar puntos cercanos	Público
GET	/api/v1/pickup-points/{id}	Detalle de punto	Público
GET	/api/v1/pickup-points/by-postcode/{cp}	Puntos por CP	Público
10.4 Endpoints de Tracking Público
Método	Endpoint	Descripción	Auth
GET	/api/v1/tracking/{orderNumber}/{token}	Tracking público	Token
GET	/api/v1/tracking/{trackingNumber}	Tracking por número	Público
POST	/api/v1/shipping/webhook/{carrier}	Webhook de carrier	Signature
 
11. Flujos de Automatización (ECA)
11.1 ECA-SHIP-001: Pedido Listo para Envío
Trigger: Order state cambia a 'processing' con fulfillment_type = 'ship_*'
1. Crear order_shipment con estado 'pending'
2. Determinar carrier y servicio según shipping_method seleccionado
3. Notificar al comercio: "Pedido listo para preparar y enviar"
4. Si auto_label = true → generar etiqueta automáticamente
11.2 ECA-SHIP-002: Etiqueta Generada
Trigger: Etiqueta generada exitosamente
1. Actualizar shipment.status a 'label_created'
2. Guardar tracking_number
3. Enviar email al cliente: "Tu pedido será enviado pronto"
4. Registrar webhook de tracking con el carrier
11.3 ECA-SHIP-003: Tracking Update
Trigger: Webhook recibido de carrier o polling detecta cambio
1. Crear tracking_event con evento normalizado
2. Actualizar shipment.status
3. Si estado = 'out_for_delivery' → push al cliente
4. Si estado = 'delivered' → actualizar order.state
5. Si estado = 'exception' → alertar a comercio
11.4 ECA-SHIP-004: Incidencia en Envío
Trigger: Tracking event con código 'exception'
1. Notificar al comercio inmediatamente (push + email)
2. Crear tarea de seguimiento en dashboard
3. Si es "destinatario ausente" → intentar reprogramar entrega
4. Si es "dirección incorrecta" → contactar al cliente
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades: shipping_method, shipping_zone, shipping_rate. ShippingService base. RateCalculatorService.	62_Commerce_Core
Sprint 2	Semana 3-4	CarrierConnectorInterface. MRW Connector completo. SEUR Connector completo.	Sprint 1
Sprint 3	Semana 5-6	GLS Connector. Correos Express Connector. Envialia Connector. LabelService.	Sprint 2
Sprint 4	Semana 7-8	TrackingService. Webhooks de carriers. Polling job. Normalización de eventos.	Sprint 3
Sprint 5	Semana 9-10	PickupPointService. Sincronización de puntos. Mapa de puntos en checkout.	Sprint 4
Sprint 6	Semana 11-12	ReturnShipmentService. Etiquetas de devolución. Flujos ECA. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 2 (MRW + SEUR)
✓ Obtener tarifas en tiempo real de MRW y SEUR
✓ Crear envío y generar etiqueta PDF
✓ Obtener tracking por número
✓ Cancelar envío antes de recogida
✓ Tests de integración con sandbox de ambos carriers
12.2 Dependencias Externas
• MRW API v2 (contrato comercial requerido)
• SEUR API + CIT de integrador
• GLS Web Services
• Correos Express API PreRegistro
• Guzzle HTTP Client ^7.8
• TCPDF para generación de etiquetas PDF
--- Fin del Documento ---
69_ComercioConecta_Shipping_Logistics_v1.docx | Jaraba Impact Platform | Enero 2026
