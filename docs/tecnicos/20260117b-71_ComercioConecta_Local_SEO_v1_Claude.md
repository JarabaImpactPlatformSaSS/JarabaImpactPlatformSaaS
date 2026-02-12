SISTEMA LOCAL SEO
Optimización para Búsqueda Local y "Cerca de Mí"
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	71_ComercioConecta_Local_SEO
Dependencias:	62_Commerce_Core, 65_Dynamic_QR, 70_Search_Discovery
Base:	Nuevo (específico ComercioConecta)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de SEO Local para ComercioConecta. El sistema optimiza la visibilidad de los comercios de proximidad en búsquedas locales como "tiendas cerca de mí", integrando Google Business Profile, datos estructurados Schema.org, y gestión centralizada de NAP (Name, Address, Phone).
1.1 Importancia del SEO Local
Estadística	Valor	Fuente
Búsquedas "cerca de mí"	+900% en 5 años	Google
Visitas a tienda tras búsqueda local	76% en 24h	Google
Usuarios que buscan negocios locales	97%	BrightLocal
Clics en Local Pack (3 primeros)	44%	Moz
Búsquedas móviles con intención local	30%	Google
1.2 Objetivos del Sistema
• Aparecer en el Local Pack de Google para búsquedas relevantes
• Sincronización automática con Google Business Profile
• Consistencia NAP al 100% en todas las plataformas
• Schema.org LocalBusiness completo en cada ficha
• Gestión centralizada de reseñas multi-plataforma
• Optimización para búsquedas por voz locales
1.3 Factores de Ranking Local (Google)
Factor	Peso Estimado	Componentes
Google Business Profile	~36%	Categoría, completitud, verificación
Reseñas	~17%	Cantidad, calidad, velocidad, respuestas
On-Page SEO	~16%	NAP, Schema.org, keywords locales
Links	~13%	Autoridad, relevancia local, anchor text
Citaciones	~7%	Consistencia NAP, volumen, calidad
Comportamiento	~7%	CTR, llamadas, direcciones, tiempo
Personalización	~4%	Historial, ubicación exacta
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────┐ │                       LOCAL SEO SYSTEM                              │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │    GBP       │  │   Schema     │  │    NAP                   │  │ │  │   Manager    │──│   Generator  │──│    Manager               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Review     │  │   Citation   │  │    Local                 │  │ │  │   Aggregator │──│   Manager    │──│    Landing Builder       │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Geo        │  │   Voice      │  │    Analytics             │  │ │  │   Sitemap    │──│   Search     │──│    Local                 │  │ │  │   Generator  │  │   Optimizer  │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └───────────────────────────────────────────────────────────────────┘                               │         ┌─────────────────────┼─────────────────────┐         ▼                     ▼                     ▼  ┌────────────┐        ┌────────────┐        ┌────────────┐  │   Google   │        │   Bing     │        │   Apple    │  │  Business  │        │   Places   │        │   Maps     │  │  Profile   │        │            │        │            │  └────────────┘        └────────────┘        └────────────┘
2.2 Flujo de Datos SEO Local
┌──────────────┐     ┌──────────────┐     ┌──────────────┐ │   Merchant   │────▶│   Platform   │────▶│   External   │ │   Profile    │     │   Sync       │     │   Platforms  │ └──────────────┘     └──────────────┘     └──────────────┘        │                    │                    │        │                    ▼                    │        │           ┌──────────────┐              │        │           │   Schema.org │              │        │           │   Generator  │              │        │           └──────────────┘              │        │                    │                    │        ▼                    ▼                    ▼ ┌──────────────────────────────────────────────────────┐ │                  Local Landing Page                  │ │  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐    │ │  │ NAP    │  │ Schema │  │ Reviews│  │ Map    │    │ │  │ Block  │  │ JSON-LD│  │ Widget │  │ Embed  │    │ │  └────────┘  └────────┘  └────────┘  └────────┘    │ └──────────────────────────────────────────────────────┘
 
3. Integración Google Business Profile
3.1 GBPService
<?php namespace Drupal\jaraba_local_seo\Service;  class GBPService {    // Conexión   public function connectAccount(int $merchantId, string $authCode): GBPConnection;   public function getConnectedLocations(int $merchantId): array;   public function verifyLocation(int $merchantId, string $locationId): VerificationResult;      // Sincronización   public function syncToGBP(MerchantProfile $merchant): SyncResult;   public function syncFromGBP(int $merchantId): MerchantProfile;   public function syncAll(): array;      // Información básica   public function updateBusinessInfo(int $merchantId, array $data): bool;   public function updateOpeningHours(int $merchantId, array $hours): bool;   public function updateCategories(int $merchantId, array $categories): bool;   public function updateAttributes(int $merchantId, array $attributes): bool;      // Media   public function uploadPhoto(int $merchantId, string $type, string $filePath): string;   public function deletePhoto(int $merchantId, string $photoId): bool;   public function getPhotos(int $merchantId): array;      // Posts   public function createPost(int $merchantId, GBPPost $post): string;   public function getPostInsights(int $merchantId, string $postId): array;      // Reviews   public function getReviews(int $merchantId, int $limit = 50): array;   public function replyToReview(int $merchantId, string $reviewId, string $reply): bool;      // Insights   public function getInsights(int $merchantId, string $period = '30d'): GBPInsights; }
3.2 Campos Sincronizados con GBP
Campo GBP	Campo Platform	Dirección Sync	Prioridad
title	merchant_name	Bidireccional	Platform → GBP
storefrontAddress	address	Bidireccional	Platform → GBP
primaryPhone	phone	Bidireccional	Platform → GBP
websiteUri	store_url	Platform → GBP	Platform
regularHours	opening_hours	Bidireccional	Platform → GBP
specialHours	special_hours	Bidireccional	Platform → GBP
primaryCategory	gbp_category	Platform → GBP	Platform
additionalCategories	gbp_categories	Platform → GBP	Platform
attributes	gbp_attributes	Platform → GBP	Platform
description	description	Platform → GBP	Platform
photos	media	Platform → GBP	Platform
reviews	reviews	GBP → Platform	GBP
 
3.3 Categorías GBP para Retail
Categoría Principal	gcid	Ejemplo de Uso
Tienda de ropa	gcid:clothing_store	Moda general
Boutique	gcid:boutique	Moda selecta
Tienda de ropa de mujer	gcid:womens_clothing_store	Especializado mujer
Tienda de ropa de hombre	gcid:mens_clothing_store	Especializado hombre
Tienda de calzado	gcid:shoe_store	Zapaterías
Tienda de artículos deportivos	gcid:sporting_goods_store	Deportes
Joyería	gcid:jewelry_store	Joyerías
Tienda de regalos	gcid:gift_shop	Regalos
Tienda de electrónica	gcid:electronics_store	Electrónica
Librería	gcid:book_store	Librerías
3.4 Atributos GBP Relevantes
// Atributos para comercios de proximidad $gbpAttributes = [   // Accesibilidad   'wheelchair_accessible_entrance' => true,   'wheelchair_accessible_parking' => true,      // Servicios   'curbside_pickup' => true,       // Recogida en acera   'delivery' => true,               // Entrega a domicilio   'in_store_pickup' => true,        // Click & Collect   'in_store_shopping' => true,      // Compra en tienda      // Pagos   'pay_credit_card_types_accepted' => ['visa', 'mastercard', 'amex'],   'pay_mobile_nfc' => true,         // Apple Pay, Google Pay   'pay_cash' => true,      // Características   'wi_fi' => true,   'restroom' => false,   'parking_lot' => true,      // Seguridad   'mask_required' => false,   'staff_fully_vaccinated' => false,      // Identidad   'lgbtq_friendly' => true,   'women_owned' => true,            // Si aplica ];
 
4. Schema.org LocalBusiness
4.1 SchemaGeneratorService
<?php namespace Drupal\jaraba_local_seo\Service;  class SchemaGeneratorService {    // Generación de schemas   public function generateLocalBusiness(MerchantProfile $merchant): array;   public function generateStore(MerchantProfile $merchant): array;   public function generateProduct(ProductVariation $product): array;   public function generateBreadcrumb(array $path): array;   public function generateFAQ(array $faqs): array;      // Tipos específicos de tienda   public function generateClothingStore(MerchantProfile $merchant): array;   public function generateShoeStore(MerchantProfile $merchant): array;   public function generateJewelryStore(MerchantProfile $merchant): array;      // Renderizado   public function toJsonLd(array $schema): string;   public function toMicrodata(array $schema): string;      // Validación   public function validate(array $schema): ValidationResult; }
4.2 Schema LocalBusiness Completo
{   "@context": "https://schema.org",   "@type": "ClothingStore",   "@id": "https://comercioconecta.es/tienda/moda-local-santaella#business",   "name": "Moda Local Santaella",   "description": "Boutique de moda con las últimas tendencias...",   "url": "https://comercioconecta.es/tienda/moda-local-santaella",   "telephone": "+34957123456",   "email": "info@modalocal.es",      "address": {     "@type": "PostalAddress",     "streetAddress": "Calle Comercio 15",     "addressLocality": "Santaella",     "addressRegion": "Córdoba",     "postalCode": "14940",     "addressCountry": "ES"   },      "geo": {     "@type": "GeoCoordinates",     "latitude": 37.5183,     "longitude": -4.8547   },      "openingHoursSpecification": [     {       "@type": "OpeningHoursSpecification",       "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],       "opens": "10:00",       "closes": "14:00"     },     {       "@type": "OpeningHoursSpecification",       "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],       "opens": "17:00",       "closes": "20:30"     },     {       "@type": "OpeningHoursSpecification",       "dayOfWeek": "Saturday",       "opens": "10:00",       "closes": "14:00"     }   ],      "priceRange": "€€",   "currenciesAccepted": "EUR",   "paymentAccepted": "Cash, Credit Card, Bizum",      "image": [     "https://comercioconecta.es/sites/files/tienda-fachada.jpg",     "https://comercioconecta.es/sites/files/tienda-interior.jpg"   ],      "logo": {     "@type": "ImageObject",     "url": "https://comercioconecta.es/sites/files/logo.png"   },      "aggregateRating": {     "@type": "AggregateRating",     "ratingValue": "4.7",     "reviewCount": "89",     "bestRating": "5",     "worstRating": "1"   },      "review": [     {       "@type": "Review",       "author": { "@type": "Person", "name": "María G." },       "datePublished": "2026-01-10",       "reviewRating": {         "@type": "Rating",         "ratingValue": "5"       },       "reviewBody": "Excelente atención y productos de calidad..."     }   ],      "hasOfferCatalog": {     "@type": "OfferCatalog",     "name": "Catálogo de Moda",     "itemListElement": [       { "@type": "OfferCatalog", "name": "Ropa de Mujer" },       { "@type": "OfferCatalog", "name": "Ropa de Hombre" },       { "@type": "OfferCatalog", "name": "Accesorios" }     ]   },      "sameAs": [     "https://www.facebook.com/modalocalsantaella",     "https://www.instagram.com/modalocalsantaella",     "https://g.page/moda-local-santaella"   ],      "potentialAction": {     "@type": "OrderAction",     "target": {       "@type": "EntryPoint",       "urlTemplate": "https://comercioconecta.es/tienda/moda-local-santaella",       "actionPlatform": [         "http://schema.org/DesktopWebPlatform",         "http://schema.org/MobileWebPlatform"       ]     }   } }
 
4.3 Tipos de Schema por Vertical
Tipo de Comercio	Schema.org Type	Propiedades Específicas
Moda general	ClothingStore	hasOfferCatalog, priceRange
Calzado	ShoeStore	hasOfferCatalog
Joyería	JewelryStore	makesOffer
Electrónica	ElectronicsStore	hasOfferCatalog
Librería	BookStore	makesOffer
Deportes	SportingGoodsStore	hasOfferCatalog
Hogar	HomeGoodsStore	hasOfferCatalog
Alimentación	GroceryStore	hasOfferCatalog
Farmacia	Pharmacy	openingHours especiales
General	Store	Genérico retail
4.4 Schema para Eventos Locales
// Schema para Flash Offers como Event {   "@context": "https://schema.org",   "@type": "SaleEvent",   "name": "Happy Hour: 20% en toda la tienda",   "description": "Descuento especial de última hora",   "startDate": "2026-01-17T18:00:00+01:00",   "endDate": "2026-01-17T20:00:00+01:00",   "location": {     "@type": "Place",     "name": "Moda Local Santaella",     "address": { ... }   },   "offers": {     "@type": "Offer",     "description": "20% de descuento",     "discount": "20%",     "validFrom": "2026-01-17T18:00:00+01:00",     "validThrough": "2026-01-17T20:00:00+01:00"   },   "organizer": {     "@type": "Organization",     "name": "Moda Local Santaella",     "url": "https://comercioconecta.es/tienda/moda-local-santaella"   } }
 
5. Gestión NAP (Name, Address, Phone)
5.1 Importancia de la Consistencia NAP
La consistencia NAP es crítica para el SEO local. Discrepancias entre plataformas confunden a los motores de búsqueda y reducen la confianza en el negocio.
Problema NAP	Ejemplo	Impacto SEO
Nombre inconsistente	"Moda Local" vs "Moda Local S.L."	Alto - confunde identidad
Dirección diferente	"C/ Comercio" vs "Calle Comercio"	Alto - ubicación incorrecta
Teléfono distinto	957123456 vs +34957123456	Medio - pérdida de señales
Horarios desactualizados	Abierto vs cerrado	Alto - mala experiencia
Categoría errónea	Restaurante vs Tienda	Muy alto - tráfico irrelevante
5.2 NAPService
<?php namespace Drupal\jaraba_local_seo\Service;  class NAPService {    // Normalización   public function normalizeName(string $name): string;   public function normalizeAddress(Address $address): Address;   public function normalizePhone(string $phone, string $country = 'ES'): string;      // Validación   public function validateNAP(MerchantProfile $merchant): NAPValidationResult;   public function checkConsistency(int $merchantId): ConsistencyReport;   public function findInconsistencies(int $merchantId): array;      // Auditoría externa   public function auditCitations(int $merchantId): CitationAudit;   public function getExternalNAP(int $merchantId, string $platform): ?array;      // Corrección   public function suggestCorrections(array $inconsistencies): array;   public function applyCorrection(int $merchantId, string $platform, array $correction): bool; }
5.3 Normalización de Dirección España
// Reglas de normalización para direcciones españolas public function normalizeAddress(Address $address): Address {   // Tipo de vía normalizado   $viaTypes = [     'c/' => 'Calle', 'c.' => 'Calle', 'cl/' => 'Calle',     'av/' => 'Avenida', 'avda/' => 'Avenida', 'avda.' => 'Avenida',     'pza/' => 'Plaza', 'pza.' => 'Plaza', 'pl/' => 'Plaza',     'ps/' => 'Paseo', 'pso/' => 'Paseo',     'ctra/' => 'Carretera', 'ctra.' => 'Carretera',   ];      $street = $address->street;   foreach ($viaTypes as $abbrev => $full) {     $street = preg_replace('/^' . preg_quote($abbrev, '/') . '/i', $full, $street);   }      // Números: siempre con espacio   $street = preg_replace('/,(\d)/', ', $1', $street);      // Eliminar "nº", "núm", etc.   $street = preg_replace('/\s*(nº|núm\.?|número)\s*/i', ' ', $street);      // Código postal: 5 dígitos   $postalCode = str_pad($address->postalCode, 5, '0', STR_PAD_LEFT);      // Ciudad: capitalización correcta   $city = $this->normalizeCity($address->city);      return new Address(     street: trim($street),     postalCode: $postalCode,     city: $city,     region: $address->region,     country: 'ES'   ); }
 
5.4 Entidad: nap_citation
Registro de citaciones en plataformas externas para auditoría de consistencia.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
merchant_id	INT	Comercio	FK merchant_profile.id, NOT NULL
platform	VARCHAR(64)	Plataforma externa	NOT NULL, ej: 'google', 'yelp', 'facebook'
external_id	VARCHAR(128)	ID en la plataforma	NULLABLE
external_url	VARCHAR(500)	URL del listing	NULLABLE
name_found	VARCHAR(255)	Nombre encontrado	NOT NULL
address_found	TEXT	Dirección encontrada	NULLABLE
phone_found	VARCHAR(32)	Teléfono encontrado	NULLABLE
name_match	BOOLEAN	¿Coincide nombre?	NOT NULL
address_match	BOOLEAN	¿Coincide dirección?	NOT NULL
phone_match	BOOLEAN	¿Coincide teléfono?	NOT NULL
overall_score	INT	Score consistencia 0-100	NOT NULL
is_claimed	BOOLEAN	¿Listing reclamado?	DEFAULT FALSE
last_checked	DATETIME	Última verificación	NOT NULL
created	DATETIME	Fecha creación	NOT NULL
5.5 Plataformas de Citación Prioritarias (España)
Plataforma	Autoridad	API Disponible	Prioridad
Google Business Profile	Muy Alta	Sí	Crítica
Facebook	Alta	Sí (Graph API)	Alta
Bing Places	Media	Sí	Media
Apple Maps Connect	Media-Alta	Parcial	Alta
Yelp	Media	Sí	Media
TripAdvisor	Media	Limitada	Media (turismo)
Páginas Amarillas	Baja-Media	No	Baja
11870	Baja	No	Baja
QDQ	Baja	No	Baja
Cylex	Baja	No	Baja
 
6. Gestión de Reseñas
6.1 ReviewAggregatorService
<?php namespace Drupal\jaraba_local_seo\Service;  class ReviewAggregatorService {    // Agregación   public function fetchAllReviews(int $merchantId): array;   public function fetchFromPlatform(int $merchantId, string $platform): array;   public function syncReviews(int $merchantId): SyncResult;      // Métricas   public function getAggregateRating(int $merchantId): AggregateRating;   public function getRatingByPlatform(int $merchantId): array;   public function getRatingTrend(int $merchantId, int $months = 6): array;      // Respuestas   public function queueReply(Review $review, string $reply): void;   public function sendReply(Review $review, string $reply): bool;   public function generateReplyTemplate(Review $review): string;      // Solicitudes   public function requestReview(int $merchantId, int $orderId): bool;   public function getBestTimeToRequest(int $merchantId): string;      // Alertas   public function alertNegativeReview(Review $review): void;   public function getUnrepliedReviews(int $merchantId): array; }
6.2 Entidad: merchant_review
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
merchant_id	INT	Comercio	FK merchant_profile.id, NOT NULL, INDEX
platform	VARCHAR(32)	Plataforma origen	NOT NULL, ej: 'google', 'platform', 'facebook'
external_id	VARCHAR(128)	ID en plataforma	NULLABLE
author_name	VARCHAR(128)	Nombre del autor	NULLABLE
author_avatar	VARCHAR(500)	Avatar URL	NULLABLE
rating	DECIMAL(2,1)	Puntuación 1-5	NOT NULL
review_text	TEXT	Texto de la reseña	NULLABLE
review_date	DATETIME	Fecha de la reseña	NOT NULL
reply_text	TEXT	Respuesta del comercio	NULLABLE
reply_date	DATETIME	Fecha de respuesta	NULLABLE
sentiment	VARCHAR(16)	Sentimiento detectado	ENUM: positive|neutral|negative
topics	JSON	Temas mencionados	Array: ['atención', 'precios', 'calidad']
is_verified_purchase	BOOLEAN	Compra verificada	DEFAULT FALSE
order_id	INT	Pedido relacionado	FK retail_order.id, NULLABLE
is_featured	BOOLEAN	Destacada en web	DEFAULT FALSE
status	VARCHAR(16)	Estado	ENUM: active|hidden|flagged
created	DATETIME	Fecha importación	NOT NULL
 
6.3 Flujo de Solicitud de Reseñas
1. Pedido completado (entregado o recogido)
2. Esperar 24-48h para que cliente use el producto
3. Enviar email: "¿Qué tal tu experiencia?"
4. Link lleva a página intermedia en la plataforma
5. Si rating ≥ 4 → redirigir a Google
6. Si rating < 4 → capturar feedback interno
// Flujo de solicitud de reseña (implementado en 65_Dynamic_QR) public function requestReview(int $merchantId, int $orderId): bool {   $order = $this->orderService->load($orderId);      // No solicitar si ya se pidió   if ($order->review_requested) {     return false;   }      // Generar URL de review con QR dinámico   $reviewUrl = $this->qrService->getReviewUrl($merchantId, [     'order_id' => $orderId,     'customer_email' => $order->email,   ]);      // Enviar email   $this->mailer->send('review_request', [     'to' => $order->email,     'merchant' => $this->merchantService->load($merchantId),     'order' => $order,     'review_url' => $reviewUrl,   ]);      $order->review_requested = true;   $order->review_requested_at = new \DateTime();   $order->save();      return true; }
6.4 Plantillas de Respuesta a Reseñas
Tipo	Trigger	Plantilla Base
5 estrellas	rating = 5	¡Muchas gracias, {nombre}! Nos alegra que...
4 estrellas	rating = 4	Gracias por tu valoración, {nombre}. Trabajamos...
3 estrellas	rating = 3	Gracias por compartir, {nombre}. Lamentamos...
1-2 estrellas	rating <= 2	Lamentamos mucho tu experiencia, {nombre}...
Mención producto	topics.product	Nos alegra que hayas disfrutado de {producto}...
Mención atención	topics.service	Transmitiremos tus palabras al equipo...
 
7. Local Landing Pages
7.1 LocalLandingService
<?php namespace Drupal\jaraba_local_seo\Service;  class LocalLandingService {    // Generación   public function generateStorePage(MerchantProfile $merchant): string;   public function generateCityPage(string $city, array $merchants): string;   public function generateCategoryLocalPage(string $category, string $city): string;      // Contenido   public function getLocalKeywords(MerchantProfile $merchant): array;   public function generateLocalDescription(MerchantProfile $merchant): string;   public function getLocalFAQs(MerchantProfile $merchant): array;      // SEO   public function generateMetaTags(MerchantProfile $merchant): array;   public function generateCanonicalUrl(MerchantProfile $merchant): string;   public function generateHreflang(MerchantProfile $merchant): array;      // Sitemaps   public function addToLocalSitemap(MerchantProfile $merchant): void;   public function generateGeoSitemap(): string; }
7.2 Estructura de URL Local
Tipo de Página	Patrón URL	Ejemplo
Ficha de tienda	/tienda/{slug}	/tienda/moda-local-santaella
Tiendas en ciudad	/{ciudad}/tiendas	/cordoba/tiendas
Categoría en ciudad	/{ciudad}/{categoria}	/cordoba/tiendas-ropa
Producto en tienda	/tienda/{slug}/p/{producto}	/tienda/moda-local/p/camiseta-basica
Ofertas locales	/{ciudad}/ofertas	/santaella/ofertas
Mapa de tiendas	/tiendas/mapa	/tiendas/mapa?cerca=santaella
7.3 Meta Tags para Páginas Locales
// Meta tags para ficha de tienda <title>Moda Local Santaella | Tienda de Ropa en Santaella, Córdoba</title> <meta name="description" content="Moda Local Santaella: boutique de moda con  las últimas tendencias en Santaella. Ropa de mujer, hombre y accesorios.  Abierto L-V 10-14h y 17-20:30h. Click & Collect disponible.">  <meta property="og:title" content="Moda Local Santaella"> <meta property="og:description" content="Boutique de moda en Santaella..."> <meta property="og:type" content="business.business"> <meta property="og:url" content="https://comercioconecta.es/tienda/moda-local-santaella"> <meta property="og:image" content="https://comercioconecta.es/.../tienda-fachada.jpg"> <meta property="business:contact_data:street_address" content="Calle Comercio 15"> <meta property="business:contact_data:locality" content="Santaella"> <meta property="business:contact_data:postal_code" content="14940"> <meta property="business:contact_data:country_name" content="España">  <meta name="geo.region" content="ES-CO"> <meta name="geo.placename" content="Santaella"> <meta name="geo.position" content="37.5183;-4.8547"> <meta name="ICBM" content="37.5183, -4.8547">  <link rel="canonical" href="https://comercioconecta.es/tienda/moda-local-santaella">
 
7.4 Contenido de Landing Page Local
// Estructura de contenido para SEO local  1. HERO SECTION    - Nombre de tienda (H1)    - Tagline con ciudad    - Imagen de fachada/interior    - CTA: "Ver productos" / "Cómo llegar"  2. NAP BLOCK (Prominente, consistente)    - Dirección completa    - Teléfono clickeable (tel:)    - Horarios (tabla)    - Mapa embebido  3. ABOUT SECTION    - Descripción del negocio (300-500 palabras)    - Historia / valores    - Keywords locales naturales    - "Tienda de ropa en Santaella desde 2010..."  4. PRODUCTOS DESTACADOS    - Grid de productos con Schema Product    - Filtro por categoría    - Precios visibles  5. RESEÑAS    - Widget de reseñas con Schema Review    - Rating agregado visible    - Últimas 5-10 reseñas  6. FAQ LOCAL    - "¿Dónde está ubicada la tienda?"    - "¿Cuál es el horario de apertura?"    - "¿Hacéis envíos a domicilio?"    - "¿Tenéis Click & Collect?"  7. TIENDAS CERCANAS (si aplica)    - Otras tiendas en la zona    - Links internos con anchor local
 
8. GeoSitemap y Local Sitemap
8.1 GeoSitemapService
<?php namespace Drupal\jaraba_local_seo\Service;  class GeoSitemapService {    // Generación   public function generateGeoSitemap(): string;   public function generateKML(): string;   public function generateLocalSitemap(): string;      // URLs   public function getStoreUrls(): array;   public function getCityUrls(): array;   public function getCategoryLocalUrls(): array;      // Registro   public function submitToSearchConsole(): bool;   public function pingSearchEngines(): void; }
8.2 Formato GeoSitemap (KML)
<?xml version="1.0" encoding="UTF-8"?> <kml xmlns="http://www.opengis.net/kml/2.2">   <Document>     <name>ComercioConecta - Tiendas</name>     <description>Directorio de tiendas locales</description>          <Placemark>       <name>Moda Local Santaella</name>       <description>         <![CDATA[           <p><strong>Tienda de Ropa</strong></p>           <p>Calle Comercio 15, 14940 Santaella</p>           <p>Tel: +34 957 123 456</p>           <p><a href="https://comercioconecta.es/tienda/moda-local-santaella">             Ver tienda           </a></p>         ]]>       </description>       <Point>         <coordinates>-4.8547,37.5183,0</coordinates>       </Point>       <ExtendedData>         <Data name="phone"><value>+34957123456</value></Data>         <Data name="category"><value>Clothing Store</value></Data>         <Data name="url">           <value>https://comercioconecta.es/tienda/moda-local-santaella</value>         </Data>       </ExtendedData>     </Placemark>          <!-- Más tiendas... -->   </Document> </kml>
8.3 Local Sitemap XML
<?xml version="1.0" encoding="UTF-8"?> <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"         xmlns:geo="http://www.google.com/geo/schemas/sitemap/1.0">      <url>     <loc>https://comercioconecta.es/tienda/moda-local-santaella</loc>     <lastmod>2026-01-17</lastmod>     <changefreq>weekly</changefreq>     <priority>0.8</priority>     <geo:geo>       <geo:format>kml</geo:format>     </geo:geo>   </url>      <url>     <loc>https://comercioconecta.es/cordoba/tiendas-ropa</loc>     <lastmod>2026-01-17</lastmod>     <changefreq>daily</changefreq>     <priority>0.7</priority>   </url>      <!-- Más URLs locales... --> </urlset>
 
9. Optimización para Búsqueda por Voz
9.1 Características de Voice Search Local
Característica	Implicación SEO	Acción
Queries conversacionales	"Dónde puedo comprar..."	Contenido en lenguaje natural
Preguntas directas	"¿Está abierta la tienda X?"	FAQs estructuradas
Búsquedas "cerca de mí"	"Tiendas de ropa cerca"	Schema LocalBusiness + geo
Resultados únicos	Solo 1 respuesta	Position Zero / Featured Snippet
Dispositivos móviles	70%+ desde móvil	Mobile-first, velocidad
Contexto local	Ubicación del usuario	NAP consistente, GBP optimizado
9.2 VoiceSearchService
<?php namespace Drupal\jaraba_local_seo\Service;  class VoiceSearchService {    // Generación de contenido optimizado   public function generateSpeakableContent(MerchantProfile $merchant): string;   public function generateFAQsForVoice(MerchantProfile $merchant): array;   public function generateAnswerCapsule(MerchantProfile $merchant): string;      // Schema Speakable   public function generateSpeakableSchema(string $content, string $cssSelector): array;      // Análisis   public function getVoiceSearchQueries(int $merchantId): array;   public function identifyVoiceOpportunities(int $merchantId): array; }
9.3 Schema Speakable
// Schema.org Speakable para contenido optimizado para voz {   "@context": "https://schema.org",   "@type": "WebPage",   "name": "Moda Local Santaella - Tienda de Ropa",   "speakable": {     "@type": "SpeakableSpecification",     "cssSelector": [       ".store-description",       ".opening-hours-summary",       ".location-summary"     ]   },   "mainEntity": {     "@type": "LocalBusiness",     ...   } }  // Contenido optimizado para ser leído por asistentes: <div class="store-description">   Moda Local es una tienda de ropa ubicada en el centro de Santaella,    Córdoba. Ofrecemos moda para mujer, hombre y accesorios. Puedes    visitarnos de lunes a viernes de diez de la mañana a dos de la tarde    y de cinco a ocho y media de la tarde. Los sábados abrimos solo por    la mañana. </div>
9.4 FAQs Optimizadas para Voz
// FAQs que responden preguntas de voz comunes $faqs = [   [     'question' => '¿Dónde está la tienda Moda Local en Santaella?',     'answer' => 'Moda Local está en la Calle Comercio número 15, en el                  centro de Santaella, Córdoba. Estamos junto a la Plaza Mayor.'   ],   [     'question' => '¿A qué hora abre Moda Local?',     'answer' => 'Moda Local abre de lunes a viernes de 10 de la mañana a                  2 de la tarde, y de 5 a 8 y media de la tarde. Los sábados                  abrimos de 10 a 2.'   ],   [     'question' => '¿Moda Local tiene servicio de Click and Collect?',     'answer' => 'Sí, puedes comprar online y recoger tu pedido en la tienda                  en menos de 2 horas. El servicio de Click and Collect es gratuito.'   ],   [     'question' => '¿Moda Local hace envíos a domicilio?',     'answer' => 'Sí, hacemos envíos a toda España. El envío es gratuito para                  pedidos superiores a 50 euros.'   ],   [     'question' => '¿Cuál es el teléfono de Moda Local Santaella?',     'answer' => 'Puedes llamarnos al 957 12 34 56 o enviarnos un WhatsApp                  al mismo número.'   ] ];
 
10. APIs REST
10.1 Endpoints de SEO Local
Método	Endpoint	Descripción	Auth
GET	/api/v1/merchants/{id}/local-seo	Estado SEO local del comercio	Merchant
GET	/api/v1/merchants/{id}/nap-audit	Auditoría de consistencia NAP	Merchant
GET	/api/v1/merchants/{id}/citations	Listado de citaciones	Merchant
POST	/api/v1/merchants/{id}/gbp/sync	Sincronizar con GBP	Merchant
GET	/api/v1/merchants/{id}/gbp/insights	Insights de GBP	Merchant
GET	/api/v1/merchants/{id}/reviews	Reseñas agregadas	Merchant
POST	/api/v1/merchants/{id}/reviews/{rid}/reply	Responder reseña	Merchant
10.2 Endpoints Públicos
Método	Endpoint	Descripción	Auth
GET	/api/v1/stores/nearby	Tiendas cercanas	Público
GET	/api/v1/stores/{slug}	Ficha de tienda	Público
GET	/api/v1/stores/{slug}/schema	Schema.org JSON-LD	Público
GET	/api/v1/cities/{city}/stores	Tiendas en ciudad	Público
GET	/api/v1/sitemap/local.xml	Sitemap local	Público
GET	/api/v1/sitemap/geo.kml	GeoSitemap KML	Público
 
11. Flujos de Automatización (ECA)
11.1 ECA-SEO-001: Merchant Profile Actualizado
Trigger: MerchantProfile guardado con cambios en NAP
1. Normalizar NAP con NAPService
2. Regenerar Schema.org JSON-LD
3. Encolar sincronización con GBP
4. Invalidar caché de landing page
5. Actualizar sitemap local
11.2 ECA-SEO-002: Nueva Reseña Recibida
Trigger: Webhook de GBP con nueva reseña
1. Importar reseña a merchant_review
2. Analizar sentimiento con AI
3. Actualizar aggregateRating
4. Si rating <= 3 → alertar al comercio
5. Sugerir plantilla de respuesta
11.3 ECA-SEO-003: Pedido Completado
Trigger: Order state = 'completed'
1. Programar solicitud de reseña (48h después)
2. Generar URL de review con QR dinámico
11.4 ECA-SEO-004: Sync Nocturno GBP
Trigger: Cron diario a las 04:00
1. Sincronizar todos los comercios con GBP
2. Importar nuevas reseñas
3. Importar insights de GBP
4. Regenerar sitemap local
5. Generar reporte de inconsistencias NAP
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	NAPService. Normalización. Entidad nap_citation. Auditoría básica.	62_Commerce_Core
Sprint 2	Semana 3-4	SchemaGeneratorService. LocalBusiness completo. JSON-LD injection.	Sprint 1
Sprint 3	Semana 5-6	GBPService. OAuth2 flow. Sincronización bidireccional.	Sprint 2
Sprint 4	Semana 7-8	ReviewAggregatorService. Entidad merchant_review. Respuestas automatizadas.	Sprint 3
Sprint 5	Semana 9-10	LocalLandingService. Páginas de tienda. Páginas de ciudad. Meta tags.	Sprint 4
Sprint 6	Semana 11-12	VoiceSearchService. FAQs. GeoSitemap. Flujos ECA. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 3 (GBP)
✓ OAuth2 flow completo con Google
✓ Sincronización de NAP hacia GBP
✓ Actualización de horarios funcional
✓ Subida de fotos
✓ Lectura de reseñas
12.2 Dependencias Externas
• Google Business Profile API
• Google My Business OAuth 2.0
• Schema.org vocabulary
• Google Maps Embed API
• Google Search Console API (sitemaps)
--- Fin del Documento ---
71_ComercioConecta_Local_SEO_v1.docx | Jaraba Impact Platform | Enero 2026
