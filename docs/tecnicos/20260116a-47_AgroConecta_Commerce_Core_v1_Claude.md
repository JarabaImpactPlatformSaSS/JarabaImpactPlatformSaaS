SISTEMA COMMERCE CORE
Arquitectura E-Commerce para Marketplace Agrario
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	47_AgroConecta_Commerce_Core
Dependencias:	01_Core_Entidades, 06_Core_Flujos_ECA, 07_MultiTenant
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del núcleo de comercio electrónico para la vertical AgroConecta del Ecosistema Jaraba. El Commerce Core es la base sobre la que se construye el marketplace de productos agrarios, conectando productores locales con consumidores y empresas.
1.1 Objetivos del Sistema
•	Marketplace multi-vendor: Múltiples productores vendiendo en una plataforma unificada
•	Productos agrarios especializados: Soporte para productos perecederos, estacionales, con trazabilidad y certificaciones
•	Split payments automático: Distribución de pagos productor/plataforma vía Stripe Connect
•	GEO-optimizado: Schema.org Product + Answer Capsules para visibilidad en IA
•	Multi-tenant: Catálogos aislados por tenant con productos globales compartibles
1.2 Stack Tecnológico
Componente	Tecnología
Core E-Commerce	Drupal Commerce 3.x con módulo jaraba_commerce custom
Catálogo	Commerce Product + variaciones + atributos + taxonomías agrarias
Pagos	Stripe Connect (Express) con Destination Charges para split automático
Búsqueda	Search API + Solr/Elasticsearch con facetas por origen, certificación, temporada
Integraciones	Make.com para sync con Meta Catalog, Google Merchant, marketplaces externos
Automatización	ECA Module para flujos de inventario, precios estacionales, notificaciones
SEO/GEO	Schema.org Product/Offer/AggregateRating + JSON-LD dinámico
1.3 Filosofía 'Sin Humo'
•	Reutilización máxima: Commerce Core sirve como base para ComercioConecta y ServiciosConecta
•	Sin over-engineering: Usar contrib modules de Drupal Commerce cuando existan
•	Extensibilidad: Arquitectura que permite añadir verticales sin refactoring
 
2. Arquitectura de Entidades
El Commerce Core introduce entidades Drupal personalizadas que extienden Commerce Product para las necesidades específicas del sector agrario. Se integran con el esquema base definido en 01_Core_Entidades.
2.1 Entidad: product_agro
Extiende commerce_product para productos agrarios. Incluye campos específicos de trazabilidad, certificaciones, y estacionalidad. Un producto puede tener múltiples variaciones (formatos, pesos).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
sku	VARCHAR(64)	SKU único del producto	UNIQUE, NOT NULL, INDEX
title	VARCHAR(255)	Nombre del producto	NOT NULL
body	TEXT	Descripción larga HTML + Answer Capsule en primeros 150 chars	NOT NULL
summary	VARCHAR(300)	Resumen SEO/cards	NOT NULL
producer_id	INT	Productor propietario	FK producer_profile.id, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
category_tid	INT	Categoría principal	FK taxonomy_term.tid (vocab: agro_category)
subcategory_tid	INT	Subcategoría	FK taxonomy_term.tid, NULLABLE
origin_region	VARCHAR(128)	Región de origen (ej: Montilla-Moriles)	NOT NULL
origin_country	VARCHAR(2)	País ISO 3166-1 alpha-2	DEFAULT 'ES'
denomination_origin	VARCHAR(128)	DO/IGP si aplica	NULLABLE
certifications	JSON	Array de certification_id	NULLABLE, array
is_organic	BOOLEAN	Producto ecológico certificado	DEFAULT FALSE, INDEX
is_seasonal	BOOLEAN	Disponibilidad estacional	DEFAULT FALSE
season_start	VARCHAR(5)	Inicio temporada (MM-DD)	NULLABLE
season_end	VARCHAR(5)	Fin temporada (MM-DD)	NULLABLE
allergens	JSON	Alérgenos (gluten, lactose, etc.)	NULLABLE, array
storage_instructions	VARCHAR(500)	Instrucciones de conservación	NULLABLE
shelf_life_days	INT	Vida útil en días	NULLABLE, > 0
images	JSON	Array de file_managed.fid	NOT NULL, min 1
is_published	BOOLEAN	Visible en tienda	DEFAULT FALSE
is_featured	BOOLEAN	Destacado en home	DEFAULT FALSE
seo_title	VARCHAR(70)	Meta title SEO	NULLABLE
seo_description	VARCHAR(160)	Meta description	NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: product_variation_agro
Variación de producto que define un SKU vendible específico. Cada variación tiene su propio precio, stock, y atributos (peso, formato, añada). Extiende commerce_product_variation.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
product_id	INT	Producto padre	FK product_agro.id, NOT NULL, INDEX
sku	VARCHAR(64)	SKU único de variación	UNIQUE, NOT NULL, INDEX
title	VARCHAR(255)	Título de variación (auto-generado)	NOT NULL
price_amount	DECIMAL(10,2)	Precio base	NOT NULL, >= 0
price_currency	VARCHAR(3)	Moneda ISO 4217	DEFAULT 'EUR'
compare_price	DECIMAL(10,2)	Precio anterior (tachado)	NULLABLE
cost_price	DECIMAL(10,2)	Coste para cálculo margen	NULLABLE
weight_value	DECIMAL(8,3)	Peso neto	NOT NULL
weight_unit	VARCHAR(8)	Unidad de peso	ENUM: g|kg|ml|l|unit
format	VARCHAR(64)	Formato (botella, caja, saco...)	NULLABLE
vintage	INT	Añada (vinos)	NULLABLE, 1900-2100
lot_number	VARCHAR(32)	Número de lote producción	NULLABLE
harvest_date	DATE	Fecha de cosecha/elaboración	NULLABLE
expiry_date	DATE	Fecha de caducidad	NULLABLE, INDEX
stock_quantity	INT	Stock disponible	NOT NULL, >= 0
stock_threshold	INT	Umbral alerta stock bajo	DEFAULT 10
is_in_stock	BOOLEAN	Indicador rápido de stock	COMPUTED
allow_backorder	BOOLEAN	Permitir pedidos sin stock	DEFAULT FALSE
max_quantity	INT	Máximo por pedido	NULLABLE
barcode	VARCHAR(32)	EAN/UPC para POS	NULLABLE, INDEX
is_active	BOOLEAN	Variación activa/vendible	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.3 Entidad: producer_profile
Perfil del productor/vendedor en el marketplace. Contiene datos de negocio, ubicación, certificaciones verificadas y configuración de Stripe Connect.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario Drupal propietario	FK users.uid, UNIQUE, NOT NULL
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
business_name	VARCHAR(255)	Nombre comercial	NOT NULL
slug	VARCHAR(64)	URL-friendly name	UNIQUE, NOT NULL
legal_name	VARCHAR(255)	Razón social	NOT NULL
tax_id	VARCHAR(20)	NIF/CIF	NOT NULL
description	TEXT	Historia/valores del productor	NULLABLE
short_bio	VARCHAR(300)	Bio corta para cards	NULLABLE
logo	INT	Logo del productor	FK file_managed.fid
cover_image	INT	Imagen de portada	FK file_managed.fid
gallery	JSON	Galería de imágenes	NULLABLE, array of fid
address	JSON	Dirección completa (address field)	NOT NULL
coordinates	POINT	Lat/Lng para mapa	NULLABLE
phone	VARCHAR(20)	Teléfono de contacto	NOT NULL
email	VARCHAR(255)	Email de negocio	NOT NULL
website	VARCHAR(255)	Web propia si tiene	NULLABLE
stripe_account_id	VARCHAR(64)	Stripe Connect account ID	NULLABLE, INDEX
stripe_onboarding_complete	BOOLEAN	KYC completado	DEFAULT FALSE
commission_rate	DECIMAL(4,2)	Comisión plataforma %	DEFAULT 5.00
certifications_verified	JSON	Certificaciones validadas por admin	NULLABLE
is_verified	BOOLEAN	Productor verificado	DEFAULT FALSE
is_active	BOOLEAN	Puede vender	DEFAULT FALSE
rating_average	DECIMAL(2,1)	Rating promedio	DEFAULT 0, 0-5
rating_count	INT	Número de reseñas	DEFAULT 0
total_sales	INT	Ventas totales	DEFAULT 0
joined_at	DATETIME	Fecha alta	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.4 Entidad: agro_certification
Catálogo de certificaciones agrarias disponibles. Permite asociar certificaciones a productos y productores con verificación administrativa.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
machine_name	VARCHAR(64)	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(128)	Nombre de certificación	NOT NULL
description	TEXT	Descripción completa	NULLABLE
issuing_body	VARCHAR(255)	Organismo emisor	NOT NULL
logo	INT	Logo/sello oficial	FK file_managed.fid
verification_url	VARCHAR(255)	URL verificación oficial	NULLABLE
cert_type	VARCHAR(32)	Tipo de certificación	ENUM: organic|origin|quality|sustainability|other
is_active	BOOLEAN	Activa en el sistema	DEFAULT TRUE
Certificaciones Pre-cargadas
•	organic_eu: Agricultura Ecológica UE (hoja verde)
•	do_montilla: Denominación de Origen Montilla-Moriles
•	igp_aceite_cordoba: IGP Aceite de Córdoba
•	produccion_integrada: Producción Integrada Andalucía
•	km0: Producto de proximidad (< 100km)
2.5 Vocabularios de Taxonomía
El catálogo utiliza vocabularios de taxonomía de Drupal para clasificación jerárquica:
Vocabulario	Descripción	Ejemplo de términos
agro_category	Categorías principales de productos	Vinos, Aceites, Quesos, Embutidos, Frutas, Conservas
agro_subcategory	Subcategorías (parent: agro_category)	Vino Tinto, AOVE, Queso Curado...
agro_origin_region	Regiones de origen	Montilla-Moriles, Priego de Córdoba, Sierra de Cazorla
agro_allergen	Alérgenos alimentarios	Gluten, Lactosa, Frutos secos, Sulfitos
agro_format	Formatos de venta	Botella 750ml, Lata 5L, Caja 6ud, Saco 25kg
 
3. Sistema de Precios
AgroConecta implementa un sistema de precios flexible que soporta precios estacionales, descuentos por volumen, y ofertas flash para producto próximo a caducar.
3.1 Tipos de Precio
Tipo	Descripción	Implementación
Precio Base	Precio estándar de la variación	product_variation_agro.price_amount
Precio Tachado	Precio anterior mostrado tachado	product_variation_agro.compare_price
Precio por Volumen	Descuento al comprar X+ unidades	commerce_price_list + resolver custom
Precio Estacional	Precio especial en temporada alta/baja	commerce_price_list con fechas
Oferta Flash	Descuento temporal (producto próximo a caducar)	commerce_promotion + ECA trigger
3.2 Entidad: volume_pricing_tier
Define escalones de precio por cantidad para incentivar compras al por mayor:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
variation_id	INT	Variación de producto	FK product_variation_agro.id, INDEX
min_quantity	INT	Cantidad mínima	NOT NULL, > 0
max_quantity	INT	Cantidad máxima (NULL = sin límite)	NULLABLE
price_amount	DECIMAL(10,2)	Precio unitario en este tier	NOT NULL, >= 0
discount_percent	DECIMAL(4,2)	% descuento (alternativo)	NULLABLE, 0-100
is_active	BOOLEAN	Tier activo	DEFAULT TRUE
Ejemplo: Aceite 1L → 1-5 uds: 12€/ud | 6-11 uds: 10€/ud | 12+ uds: 8.50€/ud
3.3 Configuración Stripe Connect
El split de pagos se configura usando Destination Charges de Stripe Connect:
•	Tipo de cuenta: Express (onboarding simplificado, Stripe gestiona KYC)
•	Modelo de cargo: Destination Charges (el cliente paga a la plataforma, se transfiere al productor)
•	Comisión plataforma: Configurable por productor (default 5%, rango 3-15%)
•	Timing de transferencia: T+2 días tras captura del pago
// Ejemplo Destination Charge con application_fee
PaymentIntent::create([
  'amount' => 10000, // €100.00
  'currency' => 'eur',
  'application_fee_amount' => 500, // €5.00 (5%)
  'transfer_data' => ['destination' => 'acct_PRODUCER_ID']
]);
 
4. APIs REST
El módulo jaraba_commerce expone los siguientes endpoints RESTful. Todos requieren autenticación OAuth2 y respetan el contexto multi-tenant. Los endpoints públicos están marcados como [PUBLIC].
4.1 Endpoints de Catálogo
Método	Endpoint	Descripción
GET	/api/v1/products [PUBLIC]	Listar productos (filtros: category, producer, organic, region, price_range)
GET	/api/v1/products/{id} [PUBLIC]	Detalle de producto con variaciones y stock
POST	/api/v1/products	Crear producto (rol: merchant+)
PATCH	/api/v1/products/{id}	Actualizar producto (owner o admin)
DELETE	/api/v1/products/{id}	Eliminar producto (soft delete)
GET	/api/v1/products/{id}/variations	Listar variaciones de un producto
POST	/api/v1/products/{id}/variations	Añadir variación a producto
PATCH	/api/v1/variations/{id}/stock	Actualizar stock de variación
4.2 Endpoints de Productores
Método	Endpoint	Descripción
GET	/api/v1/producers [PUBLIC]	Listar productores (filtros: region, verified, category)
GET	/api/v1/producers/{slug} [PUBLIC]	Perfil público del productor con productos destacados
GET	/api/v1/producers/me	Perfil del productor autenticado
PATCH	/api/v1/producers/me	Actualizar perfil propio
POST	/api/v1/producers/me/stripe-onboarding	Iniciar onboarding Stripe Connect
GET	/api/v1/producers/me/dashboard	Métricas del dashboard (ventas, pedidos, ratings)
4.3 Endpoints de Búsqueda y Filtros
Método	Endpoint	Descripción
GET	/api/v1/search/products [PUBLIC]	Búsqueda fulltext con facetas (Search API)
GET	/api/v1/categories [PUBLIC]	Árbol de categorías con conteo de productos
GET	/api/v1/certifications [PUBLIC]	Listado de certificaciones disponibles
GET	/api/v1/regions [PUBLIC]	Regiones de origen con productores activos
 
5. Flujos de Automatización (ECA)
Los siguientes flujos ECA automatizan operaciones críticas del catálogo y gestión de productores.
5.1 ECA-AGRO-001: Alerta Stock Bajo
Trigger: Actualización de product_variation_agro.stock_quantity
1.	Verificar si stock_quantity <= stock_threshold
2.	Obtener producer_profile del producto
3.	Enviar email al productor con detalle de producto y stock actual
4.	Crear notificación in-app en dashboard del productor
5.	Si stock_quantity = 0: marcar variación como out_of_stock
5.2 ECA-AGRO-002: Producto Próximo a Caducar
Trigger: Cron diario a las 06:00
6.	Buscar variaciones donde expiry_date <= HOY + 7 días AND stock_quantity > 0
7.	Para cada variación encontrada:
○	Notificar al productor con opciones: aplicar descuento, retirar producto
○	Si expiry_date <= HOY + 3 días: sugerir descuento automático del 30%
8.	Crear promoción temporal si el productor tiene auto_discount_enabled
9.	Si expiry_date < HOY: despublicar automáticamente la variación
5.3 ECA-AGRO-003: Nuevo Productor Onboarding
Trigger: Creación de producer_profile
10.	Asignar rol 'merchant' al usuario asociado
11.	Enviar email de bienvenida con guía de primeros pasos
12.	Crear checklist de onboarding (completar perfil, subir logo, añadir primer producto, configurar Stripe)
13.	Programar recordatorio a las 48h si no ha completado el primer producto
14.	Webhook a CRM (ActiveCampaign) con tag 'new_producer'
5.4 ECA-AGRO-004: Stripe Onboarding Completado
Trigger: Webhook de Stripe: account.updated con charges_enabled = true
15.	Actualizar producer_profile.stripe_onboarding_complete = TRUE
16.	Actualizar producer_profile.is_active = TRUE
17.	Enviar email de confirmación: '¡Ya puedes empezar a vender!'
18.	Marcar paso 'Configurar cobros' como completado en checklist
5.5 ECA-AGRO-005: Sincronización de Catálogo
Trigger: Creación o actualización de product_agro con is_published = TRUE
19.	Disparar webhook product.created o product.updated
20.	Make.com recibe webhook y ejecuta escenario de sync:
○	Facebook/Instagram Catalog API
○	Google Merchant Center
21.	Registrar resultado de sync en log de producto
22.	Si error: notificar al admin del marketplace
 
6. Schema.org y Optimización GEO
Cada producto y productor genera JSON-LD estructurado para máxima visibilidad en buscadores tradicionales y modelos de IA (GEO).
6.1 Schema.org Product
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Aceite de Oliva Virgen Extra - Finca Los Olivos",
  "description": "AOVE de primera presión en frío, variedad picual...",
  "image": ["https://..."],
  "sku": "AOVE-FINCA-500",
  "brand": {"@type": "Brand", "name": "Finca Los Olivos"},
  "countryOfOrigin": "ES",
  "offers": {
    "@type": "Offer",
    "price": "12.50",
    "priceCurrency": "EUR",
    "availability": "https://schema.org/InStock",
    "seller": {"@type": "Organization", "name": "Finca Los Olivos"}
  },
  "aggregateRating": {"@type": "AggregateRating", "ratingValue": "4.8", "reviewCount": "127"}
}
6.2 Answer Capsule (GEO)
Los primeros 150 caracteres de la descripción del producto deben contener una 'Answer Capsule' optimizada para extracción por LLMs:
Formato: [Qué es] + [Origen] + [Diferenciador] + [Beneficio]
Ejemplo: "Aceite de oliva virgen extra de primera presión en frío, elaborado en Priego de Córdoba con aceitunas picual. Ganador de 3 premios internacionales. Ideal para consumo en crudo."
 
7. Configuración Multi-Tenant
AgroConecta soporta múltiples instancias (tenants) donde cada marketplace tiene su propio catálogo, productores y configuración, pero puede compartir productos globales.
7.1 Aislamiento de Datos
Entidad	Aislamiento	Compartición
product_agro	Por tenant_id (obligatorio)	Puede marcarse como 'global' para compartir
producer_profile	Por tenant_id (obligatorio)	Un productor puede estar en múltiples tenants
agro_certification	Global (sin tenant)	Compartido entre todos los tenants
Taxonomías	Global (sin tenant)	Categorías y atributos compartidos
Pedidos	Por tenant_id (obligatorio)	Nunca compartidos
7.2 Configuración por Tenant
Cada tenant puede personalizar:
•	Comisión de plataforma: % que retiene el marketplace (override del default)
•	Categorías habilitadas: Subset del árbol global de categorías
•	Zonas de envío: Configuración de shipping zones propia
•	Branding: Logo, colores, textos personalizados
•	Dominio: Subdominio o dominio propio
 
8. Sistema de Webhooks
Los webhooks permiten integración con sistemas externos (Make.com, CRM, marketplaces) mediante eventos en tiempo real.
Evento	Trigger	Payload
product.created	Nuevo producto publicado	Product entity JSON completo + variations
product.updated	Producto editado	Product entity JSON con campos modificados
product.deleted	Producto despublicado/eliminado	{product_id, sku, reason}
stock.updated	Cambio de stock en variación	{variation_id, sku, old_qty, new_qty, is_in_stock}
stock.low	Stock bajo umbral	{variation_id, sku, current_qty, threshold}
producer.created	Nuevo productor registrado	Producer profile JSON (sin datos sensibles)
producer.verified	Productor verificado por admin	{producer_id, business_name, verified_at}
price.changed	Cambio de precio	{variation_id, old_price, new_price, currency}
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Módulo jaraba_commerce: entidades product_agro, product_variation_agro. Migrations. Admin UI básico con formularios de producto.	Core entities
Sprint 2	Semana 3-4	Entidad producer_profile. Onboarding wizard. APIs REST de catálogo y productores. Tests unitarios.	Sprint 1
Sprint 3	Semana 5-6	Integración Stripe Connect Express. Flujo de onboarding KYC. Destination Charges. Webhook handlers.	Sprint 2 + Stripe
Sprint 4	Semana 7-8	Taxonomías agrarias. Search API con facetas. Certificaciones. Sistema de precios por volumen.	Sprint 3
Sprint 5	Semana 9-10	Flujos ECA completos. Sistema de webhooks. Integración Make.com. Schema.org JSON-LD.	Sprint 4 + ECA
Sprint 6	Semana 11-12	Frontend de catálogo. Páginas de producto y productor. SEO/GEO. QA completo. Go-live.	Sprint 5
--- Fin del Documento ---
47_AgroConecta_Commerce_Core_v1.docx | Jaraba Impact Platform | Enero 2026
