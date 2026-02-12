SISTEMA COMMERCE CORE RETAIL
Arquitectura E-Commerce para Comercio de Proximidad
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	62_ComercioConecta_Commerce_Core
Dependencias:	01_Core_Entidades, 06_Core_Flujos_ECA, 07_MultiTenant
Base:	47_AgroConecta_Commerce_Core (adaptación ~70%)
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del núcleo de comercio electrónico para la vertical ComercioConecta del Ecosistema Jaraba. El Commerce Core Retail es la base sobre la que se construye el "Sistema Operativo de Barrio", conectando comercios locales con consumidores en un modelo omnicanal (físico + digital).
1.1 Objetivos del Sistema
• Marketplace multi-vendor local: Múltiples comercios de proximidad en una plataforma unificada por zona/barrio
• Productos retail especializados: Soporte para tallas, colores, códigos de barras EAN/UPC, temporadas de moda
• Inventario multi-ubicación: Stock sincronizado entre tienda física, almacén y canal online
• Split payments automático: Distribución de pagos comercio/plataforma vía Stripe Connect
• Local SEO optimizado: Schema.org LocalBusiness + Product para búsquedas "cerca de mí"
• Experiencia Phygital: Puente entre experiencia física (QR, POS) y digital (web, app)
1.2 Stack Tecnológico
Componente	Tecnología
Core E-Commerce	Drupal Commerce 3.x con módulo jaraba_retail custom
Catálogo	Commerce Product + variaciones por talla/color + atributos configurables
Inventario	Commerce Stock + extensión multi-ubicación (tienda, almacén, online)
Códigos de barras	EAN-13, UPC-A, códigos internos con generación y lectura integrada
Pagos	Stripe Connect (Express) con Destination Charges + Bizum (próximamente)
POS Sync	Conectores modulares para Square, SumUp, Shopify POS, Zettle
Búsqueda	Search API + Solr con facetas: categoría, marca, precio, distancia, disponibilidad
Integraciones	Make.com para sync con Google Merchant, Meta Catalog, marketplaces
Automatización	ECA Module para stock, precios, notificaciones, ofertas flash
SEO/GEO	Schema.org Product/LocalBusiness/Store + JSON-LD dinámico
1.3 Filosofía 'Sin Humo'
• Reutilización máxima: ~70% del código base de AgroConecta Commerce Core
• Diferenciación clara: Componentes exclusivos retail (POS, Flash Offers, Local SEO)
• Sin over-engineering: Usar contrib modules de Drupal Commerce cuando existan
• Extensibilidad: Arquitectura preparada para ServiciosConecta (siguiente vertical)
1.4 Diferencias Clave vs. AgroConecta
Aspecto	AgroConecta	ComercioConecta
Tipo de producto	Agrario (perecedero, estacional)	Retail (moda, hogar, electrónica)
Variaciones	Peso, formato, añada	Talla, color, material
Códigos	SKU interno	EAN-13, UPC-A, código interno
Inventario	Productor único	Multi-ubicación (tienda + almacén + online)
Certificaciones	DO, IGP, Ecológico	No aplica (marcas, garantías)
Temporalidad	Estaciones agrícolas	Temporadas moda (SS, AW)
Trazabilidad	Origen, lote, caducidad	Proveedor, modelo, garantía
Canal principal	Online (envío)	Omnicanal (Click & Collect + envío)
SEO Focus	Producto + Productor	Producto + Tienda + Localización
 
2. Arquitectura de Entidades
El Commerce Core Retail introduce entidades Drupal personalizadas que extienden Commerce Product para las necesidades específicas del comercio minorista. Se integran con el esquema base definido en 01_Core_Entidades.
2.1 Entidad: product_retail
Extiende commerce_product para productos de comercio minorista. Incluye campos específicos de categorización retail, marcas, y gestión de garantías. Un producto puede tener múltiples variaciones (tallas, colores).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
sku	VARCHAR(64)	SKU maestro del producto	UNIQUE, NOT NULL, INDEX
ean	VARCHAR(13)	Código EAN-13 principal	NULLABLE, INDEX
upc	VARCHAR(12)	Código UPC-A (mercado US)	NULLABLE, INDEX
title	VARCHAR(255)	Nombre del producto	NOT NULL
body	TEXT	Descripción larga HTML + Answer Capsule en primeros 150 chars	NOT NULL
summary	VARCHAR(300)	Resumen SEO/cards sociales	NOT NULL
merchant_id	INT	Comercio propietario	FK merchant_profile.id, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
brand_tid	INT	Marca del producto	FK taxonomy_term.tid (vocab: retail_brand)
category_tid	INT	Categoría principal	FK taxonomy_term.tid (vocab: retail_category)
subcategory_tid	INT	Subcategoría	FK taxonomy_term.tid, NULLABLE
product_type	VARCHAR(32)	Tipo de producto	ENUM: physical|digital|service|gift_card
gender_target	VARCHAR(16)	Género objetivo	ENUM: unisex|male|female|kids|baby, NULLABLE
season	VARCHAR(8)	Temporada de moda	ENUM: SS|AW|permanent, DEFAULT 'permanent'
season_year	INT	Año de colección	NULLABLE, ej: 2026
warranty_months	INT	Meses de garantía	DEFAULT 24, >= 0
manufacturer	VARCHAR(128)	Fabricante/proveedor	NULLABLE
model_number	VARCHAR(64)	Número de modelo fabricante	NULLABLE
material	VARCHAR(255)	Material principal	NULLABLE (ej: 100% algodón)
care_instructions	TEXT	Instrucciones de cuidado	NULLABLE
country_origin	VARCHAR(2)	País de fabricación ISO 3166-1	DEFAULT 'ES'
is_returnable	BOOLEAN	Admite devolución	DEFAULT TRUE
return_days	INT	Días para devolución	DEFAULT 30, >= 0
images	JSON	Array de file_managed.fid	NOT NULL, min 1
is_published	BOOLEAN	Visible en tienda	DEFAULT FALSE
is_featured	BOOLEAN	Destacado en home	DEFAULT FALSE
is_new_arrival	BOOLEAN	Novedad	DEFAULT FALSE, auto-expire 30d
is_bestseller	BOOLEAN	Más vendido	DEFAULT FALSE, calculado
seo_title	VARCHAR(70)	Meta title SEO	NULLABLE
seo_description	VARCHAR(160)	Meta description	NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: product_variation_retail
Variación de producto que define un SKU vendible específico. Cada variación tiene su propio código de barras, precio, stock por ubicación, y atributos (talla, color). Extiende commerce_product_variation.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
product_id	INT	Producto padre	FK product_retail.id, NOT NULL, INDEX
sku	VARCHAR(64)	SKU único de variación	UNIQUE, NOT NULL, INDEX
ean	VARCHAR(13)	EAN-13 de esta variación	NULLABLE, UNIQUE, INDEX
upc	VARCHAR(12)	UPC-A de esta variación	NULLABLE, UNIQUE, INDEX
internal_code	VARCHAR(32)	Código interno del comercio	NULLABLE, INDEX
title	VARCHAR(255)	Título variación (auto)	NOT NULL
size_tid	INT	Talla	FK taxonomy_term.tid (vocab: retail_size)
color_tid	INT	Color	FK taxonomy_term.tid (vocab: retail_color)
color_hex	VARCHAR(7)	Código hexadecimal color	NULLABLE, ej: #FF5733
material_tid	INT	Material específico	FK taxonomy_term.tid, NULLABLE
price_amount	DECIMAL(10,2)	Precio de venta (PVP)	NOT NULL, >= 0
price_currency	VARCHAR(3)	Moneda ISO 4217	DEFAULT 'EUR'
compare_price	DECIMAL(10,2)	Precio anterior (tachado)	NULLABLE
cost_price	DECIMAL(10,2)	Coste para cálculo margen	NULLABLE
wholesale_price	DECIMAL(10,2)	Precio mayorista	NULLABLE
tax_rate	DECIMAL(5,2)	% IVA aplicable	DEFAULT 21.00
weight_value	DECIMAL(8,3)	Peso para envío	NULLABLE
weight_unit	VARCHAR(8)	Unidad de peso	ENUM: g|kg, DEFAULT 'g'
dimensions_length	DECIMAL(8,2)	Largo (cm)	NULLABLE
dimensions_width	DECIMAL(8,2)	Ancho (cm)	NULLABLE
dimensions_height	DECIMAL(8,2)	Alto (cm)	NULLABLE
image_fid	INT	Imagen específica variación	FK file_managed.fid, NULLABLE
is_active	BOOLEAN	Variación activa	DEFAULT TRUE
sort_order	INT	Orden de visualización	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.3 Entidad: stock_location
Ubicación de inventario para soporte multi-ubicación. Un comercio puede tener stock en tienda física, almacén trasero, y reserva para canal online. Entidad nueva y exclusiva de ComercioConecta.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
merchant_id	INT	Comercio propietario	FK merchant_profile.id, NOT NULL, INDEX
name	VARCHAR(128)	Nombre ubicación	NOT NULL, ej: 'Tienda Principal'
code	VARCHAR(32)	Código interno	UNIQUE per merchant, NOT NULL
type	VARCHAR(32)	Tipo de ubicación	ENUM: store|warehouse|online_reserve|popup
address_line1	VARCHAR(255)	Dirección línea 1	NULLABLE
address_line2	VARCHAR(255)	Dirección línea 2	NULLABLE
locality	VARCHAR(128)	Ciudad/localidad	NULLABLE
postal_code	VARCHAR(16)	Código postal	NULLABLE
country_code	VARCHAR(2)	País ISO 3166-1	DEFAULT 'ES'
latitude	DECIMAL(10,8)	Latitud GPS	NULLABLE
longitude	DECIMAL(11,8)	Longitud GPS	NULLABLE
is_pickup_point	BOOLEAN	Punto Click & Collect	DEFAULT FALSE
is_ship_from	BOOLEAN	Puede enviar pedidos	DEFAULT TRUE
priority	INT	Prioridad fulfillment	DEFAULT 0, mayor = primero
is_active	BOOLEAN	Ubicación activa	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL, UTC
2.4 Entidad: stock_level
Nivel de stock de una variación en una ubicación específica. Permite gestión granular del inventario por punto de venta.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
variation_id	INT	Variación de producto	FK product_variation_retail.id, NOT NULL
location_id	INT	Ubicación de stock	FK stock_location.id, NOT NULL
quantity	INT	Cantidad disponible	NOT NULL, >= 0
reserved_qty	INT	Reservado (carritos)	DEFAULT 0, >= 0
threshold_low	INT	Umbral stock bajo	DEFAULT 5
threshold_reorder	INT	Umbral reposición	DEFAULT 10
last_count_date	DATETIME	Último inventario físico	NULLABLE
last_count_qty	INT	Cantidad último conteo	NULLABLE
updated	DATETIME	Última actualización	NOT NULL, UTC
UNIQUE CONSTRAINT: (variation_id, location_id) - Una variación solo puede tener un registro de stock por ubicación.
 
2.5 Entidad: merchant_profile
Perfil del comerciante que vende en el marketplace. Equivalente a producer_profile de AgroConecta pero adaptado para retail. Incluye datos de negocio, configuración de Stripe Connect, y horarios de apertura.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario Drupal propietario	FK users.uid, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
business_name	VARCHAR(255)	Nombre comercial	NOT NULL
legal_name	VARCHAR(255)	Razón social	NOT NULL
tax_id	VARCHAR(20)	CIF/NIF	NOT NULL, INDEX
business_type	VARCHAR(32)	Tipo de comercio	ENUM: retail|food|services|crafts|other
description	TEXT	Descripción del comercio	NOT NULL
logo_fid	INT	Logo del comercio	FK file_managed.fid, NULLABLE
cover_fid	INT	Imagen de portada	FK file_managed.fid, NULLABLE
address_line1	VARCHAR(255)	Dirección tienda principal	NOT NULL
address_line2	VARCHAR(255)	Línea 2 dirección	NULLABLE
locality	VARCHAR(128)	Ciudad/barrio	NOT NULL
postal_code	VARCHAR(16)	Código postal	NOT NULL, INDEX
province	VARCHAR(64)	Provincia	NOT NULL
country_code	VARCHAR(2)	País	DEFAULT 'ES'
latitude	DECIMAL(10,8)	Latitud GPS tienda	NOT NULL
longitude	DECIMAL(11,8)	Longitud GPS tienda	NOT NULL
phone	VARCHAR(20)	Teléfono de contacto	NOT NULL
email	VARCHAR(255)	Email de contacto	NOT NULL
website	VARCHAR(255)	Web propia (si tiene)	NULLABLE
opening_hours	JSON	Horarios de apertura	NOT NULL, formato OpeningHoursSpecification
google_place_id	VARCHAR(64)	ID de Google Business Profile	NULLABLE, INDEX
stripe_account_id	VARCHAR(64)	ID cuenta Stripe Connect	NULLABLE, UNIQUE
stripe_onboarding_complete	BOOLEAN	Onboarding Stripe completado	DEFAULT FALSE
commission_rate	DECIMAL(5,2)	Comisión específica comercio	NULLABLE, override tenant
payment_delay_days	INT	Días de retención de pago	DEFAULT 7
is_verified	BOOLEAN	Verificado por admin	DEFAULT FALSE
is_active	BOOLEAN	Comercio activo	DEFAULT FALSE
avg_rating	DECIMAL(3,2)	Rating promedio	DEFAULT 0.00
review_count	INT	Número de reseñas	DEFAULT 0
total_sales	INT	Ventas totales	DEFAULT 0
joined_at	DATETIME	Fecha de alta	NOT NULL, UTC
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3. Taxonomías Retail
Las taxonomías definen las clasificaciones y atributos específicos del comercio minorista. A diferencia de AgroConecta (certificaciones agrarias), ComercioConecta utiliza estructuras orientadas a moda, hogar, y retail general.
3.1 Vocabulario: retail_category
Categorías jerárquicas de productos retail. Estructura de 3 niveles máximo.
Nivel 1	Ejemplos Nivel 2	Ejemplos Nivel 3
Moda	Mujer, Hombre, Niños, Bebé	Camisetas, Pantalones, Vestidos, Calzado
Hogar	Decoración, Cocina, Baño, Jardín	Cojines, Vajilla, Toallas, Macetas
Electrónica	Móviles, Audio, Fotografía, Gaming	Smartphones, Auriculares, Cámaras
Alimentación	Frescos, Conservas, Bebidas, Dulces	Quesos, Aceites, Vinos, Chocolates
Belleza	Cuidado facial, Maquillaje, Perfumes	Cremas, Labiales, Colonias
Deportes	Fitness, Running, Ciclismo, Outdoor	Zapatillas, Ropa técnica, Accesorios
Librería	Libros, Papelería, Arte	Novela, Cuadernos, Pinturas
3.2 Vocabulario: retail_size
Sistema de tallas con soporte para múltiples estándares (EU, UK, US).
Tipo	Valores	Campo adicional
Ropa general	XS, S, M, L, XL, XXL, 3XL	size_type: alpha
Ropa numérica	34, 36, 38, 40, 42, 44, 46, 48	size_type: numeric_eu
Calzado EU	35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46	size_type: shoe_eu
Calzado UK	3, 4, 5, 6, 7, 8, 9, 10, 11, 12	size_type: shoe_uk
Calzado US	5, 6, 7, 8, 9, 10, 11, 12, 13	size_type: shoe_us
Infantil	0-3m, 3-6m, 6-12m, 12-18m, 2a, 3a, 4a...	size_type: kids
Único	Talla única, One size	size_type: one_size
3.3 Vocabulario: retail_color
Colores con código hexadecimal para swatch visual en frontend.
Nombre	Código Hex	Familia
Blanco	#FFFFFF	Neutros
Negro	#000000	Neutros
Gris	#808080	Neutros
Beige	#F5F5DC	Neutros
Azul marino	#000080	Azules
Azul claro	#ADD8E6	Azules
Rojo	#FF0000	Cálidos
Rosa	#FFC0CB	Cálidos
Verde	#008000	Fríos
Amarillo	#FFFF00	Cálidos
Marrón	#8B4513	Tierra
Multicolor	#GRADIENT	Especiales
3.4 Vocabulario: retail_brand
Marcas de productos. Gestionado dinámicamente por los comerciantes con validación del admin. Campos: name, logo_fid, website, is_verified.
 
4. Gestión de Códigos de Barras
ComercioConecta soporta múltiples formatos de códigos de barras para integración con sistemas de inventario existentes y TPV físicos. Esta es una funcionalidad exclusiva que no existe en AgroConecta.
4.1 Formatos Soportados
Formato	Longitud	Uso	Validación
EAN-13	13 dígitos	Estándar europeo, productos de marca	Checksum dígito 13
EAN-8	8 dígitos	Productos pequeños	Checksum dígito 8
UPC-A	12 dígitos	Estándar USA, importaciones	Checksum dígito 12
CODE-128	Variable	Uso interno, logística	Alfanumérico
QR Code	Variable	Trazabilidad, enlaces web	URL o texto
Interno	Max 32 chars	Código propio del comercio	Alfanumérico, único por merchant
4.2 Servicio: BarcodeService
Servicio Drupal para validación, generación y lectura de códigos de barras.
Namespace: Drupal\jaraba_retail\Service\BarcodeService
• validateEan13(string $code): bool - Valida código EAN-13 con checksum
• validateUpc(string $code): bool - Valida código UPC-A con checksum
• generateInternalCode(int $merchantId): string - Genera código interno único
• lookupVariation(string $code): ?ProductVariationRetail - Busca variación por cualquier código
• generateBarcodeSvg(string $code, string $format): string - Genera imagen SVG del código
4.3 Algoritmo de Checksum EAN-13
function calculateEan13Checksum($code12) {   $sum = 0;   for ($i = 0; $i < 12; $i++) {     $sum += $code12[$i] * ($i % 2 === 0 ? 1 : 3);   }   return (10 - ($sum % 10)) % 10; }
 
5. Sistema de Inventario Multi-Ubicación
El inventario multi-ubicación es una funcionalidad exclusiva de ComercioConecta que permite a los comercios gestionar stock en diferentes puntos: tienda física, almacén trasero, y reserva para canal online. Esto habilita estrategias omnicanal como Click & Collect y Ship-from-Store.
5.1 Arquitectura de Stock
• Stock Total = Σ (stock por ubicación)
• Stock Disponible = Stock Total - Reservado (carritos activos)
• Stock Online = Stock en ubicaciones con is_ship_from = TRUE
• Stock Click & Collect = Stock en ubicaciones con is_pickup_point = TRUE
5.2 Servicio: StockService
Namespace: Drupal\jaraba_retail\Service\StockService
• getAvailableStock(int $variationId, ?int $locationId = NULL): int
• reserveStock(int $variationId, int $qty, int $cartId): bool
• releaseReservation(int $cartId): void
• decrementStock(int $variationId, int $qty, int $locationId): bool
• transferStock(int $variationId, int $qty, int $fromLocation, int $toLocation): bool
• syncFromPos(int $merchantId, array $stockData): SyncResult
• getLowStockAlerts(int $merchantId): array
5.3 Estrategia de Fulfillment
Cuando se confirma un pedido, el sistema selecciona la ubicación de fulfillment según prioridad:
1. Si Click & Collect: ubicación seleccionada por el cliente
2. Si envío: ubicación con is_ship_from=TRUE y mayor prioridad con stock disponible
3. Si múltiples ubicaciones tienen stock: se prefiere la más cercana al cliente (geolocalización)
4. Si sin stock en ninguna ubicación: pedido en backorder o rechazo
 
6. Integración Stripe Connect
La integración con Stripe Connect sigue el mismo patrón que AgroConecta (Destination Charges), pero con configuraciones específicas para retail como gestión de devoluciones y pagos en tienda.
6.1 Flujo de Pagos Retail
Escenario	Flujo	Split
Compra online estándar	Cliente → Stripe → Destination Charge	Comercio 92% / Plataforma 8%
Click & Collect (pago online)	Cliente → Stripe → Destination Charge	Comercio 94% / Plataforma 6%
Click & Collect (pago en tienda)	Cliente → TPV comercio → Sin split	100% comercio (sin comisión)
Devolución parcial	Refund proporcional desde Stripe	Se revierte el split original
Tarjeta regalo	Cliente → Stripe → Wallet interno	100% plataforma hasta uso
6.2 Comisiones por Defecto
Plan Comercio	Comisión Venta Online	Comisión Click & Collect	Cuota Mensual
Básico	10%	8%	0 €
Profesional	8%	6%	29 €/mes
Premium	6%	4%	79 €/mes
Enterprise	Negociable	Negociable	Personalizado
6.3 Webhook Events
Evento Stripe	Acción en Drupal
payment_intent.succeeded	Confirmar pedido, disparar ECA-RET-002
payment_intent.payment_failed	Marcar pedido como fallido, notificar cliente
charge.refunded	Procesar devolución, actualizar stock, notificar
account.updated	Actualizar merchant_profile.stripe_onboarding_complete
payout.paid	Registrar pago al comercio en historial
 
7. Flujos de Automatización (ECA)
Los flujos ECA automatizan procesos críticos del Commerce Core Retail. Integran con los flujos base definidos en 06_Core_Flujos_ECA.
7.1 ECA-RET-001: Nuevo Producto Publicado
Trigger: Creación de product_retail con is_published = TRUE
1. Validar que tiene al menos 1 variación con stock > 0
2. Generar JSON-LD Schema.org Product
3. Disparar webhook product.created
4. Make.com: sincronizar con Google Merchant Center
5. Make.com: sincronizar con Meta Catalog (si habilitado)
6. Notificar al comerciante: 'Tu producto ya está visible'
7.2 ECA-RET-002: Pedido Confirmado
Trigger: Order state cambia a 'confirmed'
1. Convertir reservas de stock en decrementos reales
2. Determinar ubicación de fulfillment (ver 5.3)
3. Si Click & Collect: enviar email con código de recogida
4. Si envío: crear shipment y notificar al comercio
5. Registrar venta en analytics del comercio
6. Webhook order.confirmed a sistemas externos
7.3 ECA-RET-003: Stock Bajo
Trigger: stock_level.quantity <= threshold_low
1. Disparar webhook stock.low
2. Notificar al comerciante por email/push
3. Si quantity = 0: ocultar variación del frontend
4. Registrar evento en log para analytics
7.4 ECA-RET-004: Sincronización POS
Trigger: Webhook desde TPV (Square, SumUp, etc.)
1. Validar autenticidad del webhook (firma, merchant_id)
2. Mapear transaction a orden interna
3. Decrementar stock en ubicación 'store'
4. Registrar venta física en analytics
5. Actualizar inventario en canal online si aplica
 
8. Schema.org y Optimización SEO Local
Cada producto, variación y comercio genera JSON-LD estructurado optimizado para búsquedas locales y visibilidad en "cerca de mí". A diferencia de AgroConecta, el énfasis está en LocalBusiness y Store.
8.1 Schema.org Product (Retail)
{   "@context": "https://schema.org",   "@type": "Product",   "name": "Camiseta Básica Algodón Orgánico",   "description": "Camiseta de manga corta 100% algodón orgánico...",   "image": ["https://..."],   "sku": "CAM-BAS-001",   "gtin13": "8412345678901",   "brand": {"@type": "Brand", "name": "EcoWear"},   "color": "Blanco",   "size": "M",   "material": "100% algodón orgánico",   "offers": {     "@type": "Offer",     "price": "29.95",     "priceCurrency": "EUR",     "availability": "https://schema.org/InStock",     "seller": {"@type": "LocalBusiness", "name": "Moda Local Santaella"},     "priceValidUntil": "2026-12-31",     "shippingDetails": {...},     "hasMerchantReturnPolicy": {...}   },   "aggregateRating": {"@type": "AggregateRating", "ratingValue": "4.5", "reviewCount": "23"} }
8.2 Schema.org LocalBusiness (Comercio)
{   "@context": "https://schema.org",   "@type": "Store",   "name": "Moda Local Santaella",   "description": "Tienda de moda sostenible en el centro de Santaella...",   "image": "https://...",   "address": {     "@type": "PostalAddress",     "streetAddress": "Calle Mayor 15",     "addressLocality": "Santaella",     "postalCode": "14546",     "addressCountry": "ES"   },   "geo": {"@type": "GeoCoordinates", "latitude": 37.5234, "longitude": -4.8456},   "telephone": "+34 957 123 456",   "openingHoursSpecification": [     {"@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],      "opens": "10:00", "closes": "14:00"},     {"@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],      "opens": "17:00", "closes": "20:30"}   ],   "aggregateRating": {"@type": "AggregateRating", "ratingValue": "4.7", "reviewCount": "89"},   "priceRange": "€€" }
8.3 Answer Capsule (GEO)
Los primeros 150 caracteres de la descripción deben ser una Answer Capsule optimizada para LLMs:
Formato: [Qué es] + [Dónde está] + [Diferenciador] + [Beneficio]
Ejemplo: "Camiseta básica de algodón orgánico certificado, disponible en Moda Local Santaella. Fabricación ética en España. Perfecta para looks casuales y sostenibles."
 
9. APIs REST
Las APIs REST del Commerce Core Retail extienden las definidas en 03_Core_APIs_Contratos con endpoints específicos para retail.
9.1 Endpoints de Catálogo
Método	Endpoint	Descripción	Auth
GET	/api/v1/products	Listar productos con filtros	Público
GET	/api/v1/products/{id}	Detalle de producto con variaciones	Público
POST	/api/v1/products	Crear producto	Merchant
PATCH	/api/v1/products/{id}	Actualizar producto	Merchant
DELETE	/api/v1/products/{id}	Eliminar producto	Merchant
GET	/api/v1/products/barcode/{code}	Buscar por código de barras	Merchant
POST	/api/v1/products/{id}/variations	Añadir variación	Merchant
GET	/api/v1/categories	Árbol de categorías retail	Público
GET	/api/v1/brands	Listar marcas	Público
9.2 Endpoints de Inventario
Método	Endpoint	Descripción	Auth
GET	/api/v1/stock/locations	Listar ubicaciones del comercio	Merchant
POST	/api/v1/stock/locations	Crear ubicación	Merchant
GET	/api/v1/stock/{variationId}	Stock por ubicación de variación	Merchant
PATCH	/api/v1/stock/{variationId}	Actualizar stock	Merchant
POST	/api/v1/stock/transfer	Transferir entre ubicaciones	Merchant
POST	/api/v1/stock/sync	Sincronizar desde POS (batch)	Merchant
GET	/api/v1/stock/alerts	Alertas de stock bajo	Merchant
9.3 Endpoints de Comercios
Método	Endpoint	Descripción	Auth
GET	/api/v1/merchants	Listar comercios del marketplace	Público
GET	/api/v1/merchants/{id}	Detalle de comercio	Público
GET	/api/v1/merchants/nearby	Comercios cercanos (geoloc)	Público
PATCH	/api/v1/merchants/{id}	Actualizar perfil comercio	Merchant
POST	/api/v1/merchants/{id}/hours	Actualizar horarios	Merchant
GET	/api/v1/merchants/{id}/products	Productos de un comercio	Público
GET	/api/v1/merchants/{id}/stats	Estadísticas del comercio	Merchant
 
10. Configuración Multi-Tenant
ComercioConecta soporta múltiples instancias (tenants) donde cada marketplace local tiene su propio catálogo de comercios, configuración, y branding. Utiliza el mismo modelo de 07_Core_Configuracion_MultiTenant con extensiones específicas.
10.1 Aislamiento de Datos
Entidad	Aislamiento	Compartición
product_retail	Por tenant_id (obligatorio)	Puede marcarse como 'global' para catálogos compartidos
merchant_profile	Por tenant_id (obligatorio)	Un comercio puede estar en múltiples tenants
stock_location	Por merchant → tenant	Nunca compartido
Taxonomías (categorías)	Global (sin tenant)	Compartido entre todos los tenants
Taxonomías (marcas)	Por tenant (opcional)	Pueden ser globales o específicas
Pedidos	Por tenant_id (obligatorio)	Nunca compartidos
10.2 Configuración por Tenant
Cada tenant (marketplace local) puede personalizar:
• Comisión de plataforma: % por defecto para todos los comercios del tenant
• Categorías habilitadas: Subset del árbol global de categorías retail
• Zona geográfica: Radio de cobertura para búsquedas 'cerca de mí'
• Branding: Logo, colores, textos de la asociación/ayuntamiento
• Dominio: Subdominio o dominio propio (comercio-santaella.es)
• Opciones de envío: Carriers habilitados, zonas de entrega
 
11. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Módulo jaraba_retail: entidades product_retail, product_variation_retail. Migraciones. Admin UI con formularios de producto. Gestión de códigos de barras.	Core entities
Sprint 2	Semana 3-4	Entidades stock_location, stock_level. StockService con multi-ubicación. APIs de inventario. Tests unitarios.	Sprint 1
Sprint 3	Semana 5-6	Entidad merchant_profile. Onboarding wizard de comercio. Integración Stripe Connect Express. Destination Charges.	Sprint 2 + Stripe
Sprint 4	Semana 7-8	Taxonomías retail (categorías, tallas, colores, marcas). Search API con facetas. BarcodeService completo.	Sprint 3
Sprint 5	Semana 9-10	Flujos ECA completos (RET-001 a RET-004). Sistema de webhooks. Integración Make.com. Schema.org JSON-LD.	Sprint 4 + ECA
Sprint 6	Semana 11-12	Frontend de catálogo. Páginas de producto y comercio. SEO Local. QA completo. Go-live MVP.	Sprint 5
11.1 Criterios de Aceptación Sprint 1
✓ CRUD completo de product_retail desde Admin UI
✓ Creación de variaciones con talla/color
✓ Validación de códigos EAN-13 y UPC-A
✓ Generación de códigos internos únicos
✓ Tests unitarios con cobertura > 80%
11.2 Dependencias Externas
• Drupal Commerce 3.x (core)
• Commerce Stock module (base para extensión)
• Stripe PHP SDK ^10.0
• Search API + Solr 8.x
• ECA module + ECA Condition/Action plugins
--- Fin del Documento ---
62_ComercioConecta_Commerce_Core_v1.docx | Jaraba Impact Platform | Enero 2026
