SISTEMA DE ENVÍOS Y LOGÍSTICA
Transportistas, Tarifas, Tracking y Fulfillment
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	51_AgroConecta_Shipping_Logistics
Dependencias:	49_Order_System, 50_Checkout_Flow
 
1. Resumen Ejecutivo
Este documento especifica el sistema de envíos y logística para AgroConecta, incluyendo integración con transportistas, cálculo de tarifas por zona, tracking en tiempo real y gestión de productos perecederos con envío refrigerado.
1.1 Objetivos del Sistema
•	Multi-carrier: Integración con MRW, SEUR, GLS y Correos Express
•	Tarifas dinámicas: Cálculo por zona, peso, volumen y tipo de producto
•	Envío refrigerado: Soporte para productos perecederos con cadena de frío
•	Tracking unificado: Seguimiento en tiempo real independiente del transportista
•	Etiquetas automáticas: Generación de etiquetas de envío en formato estándar
•	Recogida en origen: Opción de pickup en instalaciones del productor
1.2 Stack Tecnológico
Componente	Tecnología
Shipping Core	Commerce Shipping 2.x con plugins custom por carrier
Integración Carriers	APIs REST de cada transportista + adaptadores unificados
Cálculo de Tarifas	Motor de reglas con zonas, pesos y condiciones especiales
Tracking	Webhooks de carriers + polling fallback + AfterShip API (opcional)
Etiquetas	PDF generado vía API del carrier o FPDF para formato propio
Notificaciones	ECA + ActiveCampaign para emails de tracking
Mapa de Seguimiento	Leaflet.js para visualización de ruta (carriers compatibles)
1.3 Transportistas Soportados
Carrier	Servicios	Cobertura	API
MRW	Estándar, Express 24h, Frío	Nacional + Portugal	REST + SOAP
SEUR	Estándar, Express, Frío, Islas	Nacional + Internacional	REST
GLS	Estándar, Express, Puntos de recogida	Nacional + Europa	REST
Correos Express	Estándar, Paq Premium, Islas	Nacional completo	REST
Envialia	Estándar, Express regional	Andalucía optimizado	REST
 
2. Arquitectura de Entidades
2.1 Entidad: shipment
Representa un envío físico asociado a un sub-order. Un sub-order puede tener múltiples shipments si se divide el envío.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
sub_order_id	INT	Sub-pedido asociado	FK sub_order.id, NOT NULL, INDEX
shipment_number	VARCHAR(32)	Número interno (SHP-2026-00001)	UNIQUE, NOT NULL
carrier_id	VARCHAR(32)	Código del transportista	NOT NULL, INDEX
service_code	VARCHAR(32)	Código del servicio (standard, express, cold)	NOT NULL
tracking_number	VARCHAR(64)	Número de seguimiento del carrier	NULLABLE, INDEX
tracking_url	VARCHAR(500)	URL de seguimiento público	NULLABLE
state	VARCHAR(32)	Estado del envío	ENUM, NOT NULL, INDEX
label_url	VARCHAR(500)	URL de la etiqueta PDF	NULLABLE
label_generated_at	DATETIME	Fecha de generación de etiqueta	NULLABLE
weight_value	DECIMAL(8,3)	Peso real del paquete (kg)	NOT NULL
weight_unit	VARCHAR(8)	Unidad de peso	DEFAULT 'kg'
dimensions	JSON	Dimensiones: {length, width, height, unit}	NULLABLE
is_refrigerated	BOOLEAN	Requiere cadena de frío	DEFAULT FALSE
shipping_cost	DECIMAL(10,2)	Coste real del envío	NOT NULL, >= 0
insurance_amount	DECIMAL(10,2)	Valor asegurado	DEFAULT 0
pickup_scheduled_at	DATETIME	Fecha programada de recogida	NULLABLE
pickup_confirmed_at	DATETIME	Fecha real de recogida	NULLABLE
estimated_delivery	DATE	Fecha estimada de entrega	NULLABLE
delivered_at	DATETIME	Fecha/hora real de entrega	NULLABLE
delivery_signature	VARCHAR(255)	Nombre de quien recibió	NULLABLE
notes	TEXT	Notas para el transportista	NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: shipping_zone
Define zonas geográficas para cálculo de tarifas. Cada productor puede tener configuraciones diferentes.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
name	VARCHAR(100)	Nombre de la zona (Local, Regional, Nacional...)	NOT NULL
producer_id	INT	Productor (NULL = global)	FK producer_profile.id, NULLABLE, INDEX
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
zone_type	VARCHAR(32)	Tipo de definición	ENUM: postal_codes|provinces|countries
zone_data	JSON	Códigos postales, provincias o países incluidos	NOT NULL
is_enabled	BOOLEAN	Zona activa	DEFAULT TRUE
sort_order	INT	Orden de evaluación	DEFAULT 0
created	DATETIME	Fecha de creación	NOT NULL, UTC
2.3 Entidad: shipping_rate
Tarifa de envío para una combinación de zona, carrier y servicio. Permite configuración granular por productor.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
zone_id	INT	Zona de envío	FK shipping_zone.id, NOT NULL, INDEX
producer_id	INT	Productor (NULL = default)	FK producer_profile.id, NULLABLE, INDEX
carrier_id	VARCHAR(32)	Código del transportista	NOT NULL
service_code	VARCHAR(32)	Código del servicio	NOT NULL
rate_type	VARCHAR(32)	Tipo de tarifa	ENUM: flat|weight|volume|table
base_rate	DECIMAL(10,2)	Tarifa base fija	NOT NULL, >= 0
per_kg_rate	DECIMAL(10,2)	Coste adicional por kg	DEFAULT 0
per_item_rate	DECIMAL(10,2)	Coste adicional por item	DEFAULT 0
min_weight	DECIMAL(8,3)	Peso mínimo aplicable	DEFAULT 0
max_weight	DECIMAL(8,3)	Peso máximo (NULL = sin límite)	NULLABLE
free_shipping_threshold	DECIMAL(10,2)	Umbral para envío gratis	NULLABLE
is_refrigerated	BOOLEAN	Tarifa para envío frío	DEFAULT FALSE
estimated_days_min	INT	Días mínimos de entrega	NOT NULL
estimated_days_max	INT	Días máximos de entrega	NOT NULL
is_enabled	BOOLEAN	Tarifa activa	DEFAULT TRUE
valid_from	DATE	Fecha inicio validez	NULLABLE
valid_until	DATE	Fecha fin validez	NULLABLE
2.4 Entidad: tracking_event
Evento de tracking recibido del transportista. Permite historial completo del envío.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
shipment_id	INT	Envío asociado	FK shipment.id, NOT NULL, INDEX
event_code	VARCHAR(32)	Código normalizado del evento	NOT NULL, INDEX
carrier_event_code	VARCHAR(64)	Código original del carrier	NOT NULL
description	VARCHAR(500)	Descripción del evento	NOT NULL
location	VARCHAR(255)	Ubicación del evento	NULLABLE
coordinates	POINT	Coordenadas GPS si disponibles	NULLABLE
event_timestamp	DATETIME	Fecha/hora del evento (del carrier)	NOT NULL
raw_data	JSON	Datos crudos del carrier	NULLABLE
created	DATETIME	Fecha de registro en sistema	NOT NULL, UTC
 
3. Estados del Envío
El sistema normaliza los estados de todos los transportistas a un conjunto unificado para consistencia en la experiencia del cliente.
3.1 Estados Normalizados
Estado	Descripción	Siguiente Estado
pending	Envío creado, pendiente de generar etiqueta	→ label_created
label_created	Etiqueta generada, esperando recogida	→ picked_up
picked_up	Recogido por el transportista	→ in_transit
in_transit	En tránsito hacia destino	→ out_for_delivery, in_transit
out_for_delivery	En reparto, llegará hoy	→ delivered, delivery_attempt
delivery_attempt	Intento de entrega fallido	→ out_for_delivery, returned
delivered	Entregado al destinatario	(final)
returned	Devuelto al remitente	(final)
exception	Incidencia (dirección incorrecta, daño...)	→ in_transit, returned
cancelled	Envío cancelado antes de recogida	(final)
3.2 Mapeo de Estados por Carrier
Cada transportista usa códigos propios que se mapean a estados normalizados:
Estado Normal	MRW	SEUR	GLS
picked_up	RECOGIDO	010 - Recogido	PICKUP
in_transit	EN TRANSITO, EN ALMACEN	020, 030, 040	INTRANSIT, HUB
out_for_delivery	EN REPARTO	050 - En reparto	OUTFORDELIVERY
delivered	ENTREGADO	060 - Entregado	DELIVERED
delivery_attempt	AUSENTE	070 - Ausente	NOTDELIVERED
exception	INCIDENCIA	080, 090 - Incidencia	EXCEPTION
 
4. Cálculo de Tarifas
El motor de tarifas evalúa múltiples factores para calcular el coste de envío: zona, peso, dimensiones, tipo de producto y configuración del productor.
4.1 Algoritmo de Cálculo
function calculateShippingRate(cart, destination) {
  // 1. Agrupar items por productor
  const producerGroups = groupByProducer(cart.items);
  
  let totalShipping = 0;
  
  for (const [producerId, items] of producerGroups) {
    const producer = getProducer(producerId);
    
    // 2. Determinar zona: origen productor → destino cliente
    const zone = findZone(producer.postalCode, destination.postalCode);
    
    // 3. Calcular peso total y verificar si necesita frío
    const totalWeight = items.reduce((sum, i) => sum + i.weight * i.qty, 0);
    const needsColdChain = items.some(i => i.requiresRefrigeration);
    
    // 4. Obtener tarifa aplicable
    const rate = findRate(zone, producer, needsColdChain);
    
    // 5. Calcular coste
    let cost = rate.baseRate + (totalWeight * rate.perKgRate);
    
    // 6. Verificar envío gratis
    const subtotal = items.reduce((sum, i) => sum + i.price * i.qty, 0);
    if (rate.freeThreshold && subtotal >= rate.freeThreshold) {
      cost = 0;
    }
    
    totalShipping += cost;
  }
  
  return totalShipping;
}
4.2 Definición de Zonas (España)
Zona	Definición	Ejemplo
Local	Mismo código postal o CPs adyacentes (radio ~20km)	14800 → 14800-14899
Provincial	Misma provincia (2 primeros dígitos del CP)	14XXX → 14XXX
Regional	Misma comunidad autónoma	Andalucía → Andalucía
Nacional Cercano	Comunidades limítrofes	Andalucía → Extremadura
Nacional	Resto de península	Andalucía → Cataluña
Baleares	Islas Baleares (07XXX)	Cualquier origen → 07XXX
Canarias	Islas Canarias (35XXX, 38XXX)	Cualquier origen → 35/38XXX
Ceuta/Melilla	Ciudades autónomas (51XXX, 52XXX)	Cualquier origen → 51/52XXX
 
5. Integración con Carriers
El sistema implementa adaptadores unificados para cada transportista, abstrayendo las diferencias de API en una interfaz común.
5.1 Interfaz de Carrier (Adaptador)
interface CarrierAdapterInterface {
  // Obtener tarifas disponibles para un envío
  public function getRates(ShipmentRequest $request): array;
  
  // Crear envío y obtener etiqueta
  public function createShipment(ShipmentRequest $request): ShipmentResponse;
  
  // Obtener etiqueta en PDF
  public function getLabel(string $trackingNumber): string; // base64 PDF
  
  // Obtener tracking actualizado
  public function getTracking(string $trackingNumber): TrackingResponse;
  
  // Cancelar envío (si no recogido)
  public function cancelShipment(string $trackingNumber): bool;
  
  // Programar recogida
  public function schedulePickup(PickupRequest $request): PickupResponse;
}
5.2 Operaciones por Carrier
Operación	MRW	SEUR	GLS	Correos	Envialia
Cotización online	✓	✓	✓	✓	✓
Crear envío	✓	✓	✓	✓	✓
Etiqueta PDF	✓	✓	✓	✓	✓
Tracking webhook	✓	✓	✓	Polling	Polling
Cancelación	✓	✓	✓	Manual	Manual
Programar recogida	✓	✓	✓	✓	✓
Envío frío	✓	✓	—	—	—
5.3 Webhooks de Tracking
Endpoint unificado para recibir actualizaciones de todos los carriers:
•	URL: POST /api/v1/shipping/webhook/{carrier_id}
•	Autenticación: Firma HMAC o IP whitelist según carrier
•	Proceso: Parsear → Normalizar estado → Crear tracking_event → Actualizar shipment.state
•	Notificación: Trigger ECA para email al cliente según estado
 
6. Envío Refrigerado
Para productos perecederos que requieren cadena de frío, el sistema gestiona transportistas especializados y validaciones adicionales.
6.1 Productos que Requieren Frío
•	Lácteos: Quesos frescos, yogures artesanales, mantequillas
•	Cárnicos: Embutidos frescos, carnes curadas (según tipo)
•	Pescado: Conservas frescas, ahumados, salazones
•	Frutas/Verduras: Productos frescos de temporada (opcional)
6.2 Configuración del Producto
Campo en product_agro para indicar requisitos de temperatura:
storage_requirements: {
  requires_refrigeration: true,
  min_temp: 2,   // °C
  max_temp: 8,   // °C
  max_transit_hours: 48
}
6.3 Lógica de Envío Frío
1.	Si algún item del sub-order requiere refrigeración → marcar shipment.is_refrigerated = TRUE
2.	Filtrar carriers: solo mostrar los que ofrecen servicio de frío (MRW Frío, SEUR Frío)
3.	Validar zona: algunos destinos no disponibles (ej: Canarias para frío)
4.	Aplicar tarifa de frío: generalmente +50-100% sobre tarifa estándar
5.	Restringir días de envío: no enviar viernes para evitar fin de semana en tránsito
6.	Mostrar aviso al cliente: información sobre cadena de frío y recomendaciones de recepción
6.4 Restricciones de Destino para Frío
Destino	Disponibilidad Frío	Alternativa
Península	✓ Disponible	-
Baleares	✓ Con suplemento	-
Canarias	✗ No disponible	Solo productos no perecederos
Ceuta/Melilla	✗ No disponible	Solo productos no perecederos
 
7. APIs de Envío
7.1 Endpoints Públicos (Cliente)
Método	Endpoint	Descripción
GET	/api/v1/shipping/rates	Calcular tarifas para carrito actual
GET	/api/v1/shipping/tracking/{number}	Obtener tracking público de un envío
GET	/api/v1/orders/{id}/shipments	Listar envíos de un pedido
7.2 Endpoints de Productor
Método	Endpoint	Descripción
POST	/api/v1/producer/shipments	Crear envío para sub-order
GET	/api/v1/producer/shipments/{id}/label	Descargar etiqueta PDF
POST	/api/v1/producer/shipments/{id}/pickup	Programar recogida
DELETE	/api/v1/producer/shipments/{id}	Cancelar envío (si no recogido)
GET	/api/v1/producer/shipping/zones	Ver zonas configuradas
GET	/api/v1/producer/shipping/rates	Ver tarifas configuradas
7.3 Webhooks Recibidos
Método	Endpoint	Descripción
POST	/api/v1/shipping/webhook/mrw	Eventos de tracking de MRW
POST	/api/v1/shipping/webhook/seur	Eventos de tracking de SEUR
POST	/api/v1/shipping/webhook/gls	Eventos de tracking de GLS
 
8. Flujos de Automatización (ECA)
8.1 ECA-SHIP-001: Generar Etiqueta Automática
Trigger: sub_order.state cambia a 'ready_for_shipping'
7.	Verificar que no existe shipment para este sub_order
8.	Calcular peso total de items
9.	Obtener carrier preferido del productor (o default del tenant)
10.	Llamar API del carrier: createShipment()
11.	Guardar tracking_number, label_url en shipment
12.	Notificar al productor: 'Etiqueta lista para imprimir'
8.2 ECA-SHIP-002: Actualización de Tracking
Trigger: Webhook recibido de carrier
13.	Parsear payload según carrier
14.	Buscar shipment por tracking_number
15.	Normalizar estado del carrier a estado interno
16.	Crear tracking_event con detalles
17.	Actualizar shipment.state si ha cambiado
18.	Si estado = 'out_for_delivery': email al cliente 'Tu pedido llegará hoy'
19.	Si estado = 'delivered': actualizar sub_order.state, trigger payout
8.3 ECA-SHIP-003: Polling de Tracking
Trigger: Cron cada 2 horas
20.	Buscar shipments con state IN ('picked_up', 'in_transit', 'out_for_delivery')
21.	Filtrar carriers sin webhook (Correos, Envialia)
22.	Para cada shipment: llamar getTracking() del carrier
23.	Procesar eventos nuevos (comparar con últimos guardados)
24.	Aplicar misma lógica que ECA-SHIP-002
8.4 ECA-SHIP-004: Incidencia de Envío
Trigger: shipment.state cambia a 'exception' o 'delivery_attempt'
25.	Crear order_event con detalles de la incidencia
26.	Notificar al cliente: explicar situación y próximos pasos
27.	Notificar al productor: alerta de incidencia
28.	Si 3 intentos fallidos: escalar a admin para resolución manual
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades shipment, shipping_zone, shipping_rate, tracking_event. Migrations.	49_Order_System
Sprint 2	Semana 3-4	Motor de cálculo de tarifas. Zonas de España. Configuración por productor.	Sprint 1
Sprint 3	Semana 5-6	Adaptador MRW: createShipment, getLabel, getTracking. Tests integración.	Sprint 2 + API MRW
Sprint 4	Semana 7-8	Adaptadores SEUR y GLS. Webhooks de tracking. Normalización de estados.	Sprint 3
Sprint 5	Semana 9-10	Envío refrigerado. Correos Express (polling). Página de tracking cliente.	Sprint 4
Sprint 6	Semana 11-12	Flujos ECA completos. Panel de envíos productor. Notificaciones. QA. Go-live.	Sprint 5 + ECA
--- Fin del Documento ---
51_AgroConecta_Shipping_Logistics_v1.docx | Jaraba Impact Platform | Enero 2026
