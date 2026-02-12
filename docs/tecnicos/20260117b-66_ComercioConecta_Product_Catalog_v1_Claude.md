SISTEMA PRODUCT CATALOG
Catálogo de Productos para Comercio Minorista
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	66_ComercioConecta_Product_Catalog
Dependencias:	62_Commerce_Core, 03_Core_APIs, 70_Search_Discovery
Base:	48_AgroConecta_Product_Catalog (~75% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Catálogo de Productos para la vertical ComercioConecta. Adapta la arquitectura de catálogo de AgroConecta para las necesidades específicas del comercio minorista: gestión de tallas, colores, marcas, temporadas de moda, y atributos configurables por categoría.
1.1 Objetivos del Sistema
• Catálogo multi-atributo: Productos con variaciones por talla, color, material
• Gestión de marcas: Taxonomía de marcas con logos y verificación
• Temporadas: Soporte para colecciones SS/AW y productos permanentes
• Importación masiva: CSV, Excel, feeds de proveedores
• SEO optimizado: Schema.org Product, rich snippets, Answer Capsules
• Sincronización: Google Merchant Center, Meta Catalog, marketplaces
1.2 Diferencias vs. AgroConecta Catalog
Aspecto	AgroConecta	ComercioConecta
Variaciones	Peso, formato, añada	Talla, color, material
Atributos	Origen, certificaciones, temporada agrícola	Marca, género, temporada moda
Códigos	SKU interno	EAN-13, UPC-A, código interno
Perecederos	Sí (fecha caducidad, lote)	No (garantía, modelo)
Trazabilidad	Productor, finca, lote	Fabricante, modelo, proveedor
Imágenes	Producto + finca	Producto + variación + 360°
Filtros clave	Origen, ecológico, temporada	Talla, color, marca, precio
1.3 Arquitectura de Alto Nivel
┌─────────────────────────────────────────────────────────────────────┐ │                      PRODUCT CATALOG SYSTEM                         │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Product    │  │  Variation   │  │    Attribute             │  │ │  │   Manager    │──│   Manager    │──│    Manager               │  │ │  │              │  │              │  │  (Talla/Color/etc)       │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │    Brand     │  │   Category   │  │    Media                 │  │ │  │   Manager    │──│   Manager    │──│    Manager               │  │ │  │              │  │              │  │  (Imágenes, vídeos)      │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Import     │  │   Export     │  │    Search                │  │ │  │   Engine     │──│   Engine     │──│    Indexer               │  │ │  │  (CSV/XML)   │  │ (Feeds)      │  │  (Solr/Elastic)          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └─────────────────────────────────────────────────────────────────────┘
 
2. Modelo de Datos
El catálogo utiliza las entidades definidas en 62_Commerce_Core (product_retail, product_variation_retail) y extiende con entidades de soporte para atributos y medios.
2.1 Relación Producto-Variación
┌─────────────────────────────────────────────────────────────────┐ │                    product_retail (Producto padre)              │ │  - Información común: título, descripción, marca, categoría     │ │  - SEO: meta title, meta description, Answer Capsule            │ │  - Imágenes generales del producto                              │ └─────────────────────────────────┬───────────────────────────────┘                                   │ 1:N           ┌───────────────────────┼───────────────────────┐           ▼                       ▼                       ▼ ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐ │ variation_retail│     │ variation_retail│     │ variation_retail│ │  Talla: S       │     │  Talla: M       │     │  Talla: L       │ │  Color: Blanco  │     │  Color: Blanco  │     │  Color: Blanco  │ │  EAN: 841234... │     │  EAN: 841234... │     │  EAN: 841234... │ │  Precio: 29.95€ │     │  Precio: 29.95€ │     │  Precio: 29.95€ │ │  Stock: 5       │     │  Stock: 12      │     │  Stock: 3       │ └─────────────────┘     └─────────────────┘     └─────────────────┘
2.2 Entidad: product_attribute
Define los tipos de atributos disponibles para variaciones. Cada comercio puede tener atributos personalizados además de los globales (talla, color).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
machine_name	VARCHAR(64)	Nombre máquina	UNIQUE, NOT NULL, ej: 'size', 'color'
label	VARCHAR(128)	Etiqueta visible	NOT NULL, ej: 'Talla', 'Color'
attribute_type	VARCHAR(32)	Tipo de atributo	ENUM: select|color_swatch|text|number
is_global	BOOLEAN	Disponible para todos	DEFAULT FALSE
merchant_id	INT	Comercio propietario	FK, NULLABLE si is_global
is_variation_attribute	BOOLEAN	Genera variaciones	DEFAULT TRUE
is_filterable	BOOLEAN	Aparece en filtros	DEFAULT TRUE
is_visible	BOOLEAN	Visible en ficha	DEFAULT TRUE
sort_order	INT	Orden de display	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL
2.3 Entidad: product_attribute_value
Valores posibles para cada atributo (ej: para atributo 'Talla': S, M, L, XL).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
attribute_id	INT	Atributo padre	FK product_attribute.id, NOT NULL
value	VARCHAR(128)	Valor textual	NOT NULL, ej: 'M', 'Azul marino'
label	VARCHAR(128)	Etiqueta display	NULLABLE, override de value
color_hex	VARCHAR(7)	Hex si es color	NULLABLE, ej: '#000080'
image_fid	INT	Imagen del valor	FK file_managed.fid, NULLABLE
sort_order	INT	Orden de display	DEFAULT 0
is_active	BOOLEAN	Valor activo	DEFAULT TRUE
UNIQUE: (attribute_id, value) - Un valor solo existe una vez por atributo.
 
2.4 Entidad: product_media
Gestión de medios (imágenes, vídeos) asociados a productos y variaciones con soporte para ordenamiento y roles.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
product_id	INT	Producto	FK product_retail.id, NULLABLE
variation_id	INT	Variación específica	FK product_variation_retail.id, NULLABLE
media_type	VARCHAR(16)	Tipo de medio	ENUM: image|video|360|document
file_fid	INT	Archivo en Drupal	FK file_managed.fid, NOT NULL
external_url	VARCHAR(500)	URL externa (YouTube, etc)	NULLABLE
alt_text	VARCHAR(255)	Texto alternativo	NOT NULL para imágenes
title	VARCHAR(255)	Título del medio	NULLABLE
role	VARCHAR(32)	Rol del medio	ENUM: main|gallery|thumbnail|zoom|swatch|video
sort_order	INT	Orden de display	DEFAULT 0
is_active	BOOLEAN	Medio activo	DEFAULT TRUE
created	DATETIME	Fecha de subida	NOT NULL
Nota: Un medio puede estar asociado al producto (aplica a todas las variaciones) o a una variación específica (ej: foto del color azul).
2.5 Entidad: brand
Marcas de productos con gestión de logos, verificación y datos extendidos.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(128)	Nombre de la marca	NOT NULL, INDEX
slug	VARCHAR(128)	URL amigable	UNIQUE, NOT NULL
logo_fid	INT	Logo de la marca	FK file_managed.fid, NULLABLE
description	TEXT	Descripción	NULLABLE
website	VARCHAR(255)	Web oficial	NULLABLE
country_origin	VARCHAR(2)	País de origen	NULLABLE, ISO 3166-1
is_verified	BOOLEAN	Marca verificada	DEFAULT FALSE
is_active	BOOLEAN	Marca activa	DEFAULT TRUE
products_count	INT	Productos con esta marca	DEFAULT 0, computed
created	DATETIME	Fecha creación	NOT NULL
 
3. Taxonomías de Catálogo
3.1 Árbol de Categorías (retail_category)
Estructura jerárquica de hasta 4 niveles para clasificación de productos retail.
retail_category (vocabulario Drupal taxonomy) ├── Moda │   ├── Mujer │   │   ├── Tops │   │   │   ├── Camisetas │   │   │   ├── Blusas │   │   │   └── Jerseys │   │   ├── Bottoms │   │   │   ├── Pantalones │   │   │   ├── Faldas │   │   │   └── Shorts │   │   ├── Vestidos │   │   └── Calzado │   ├── Hombre │   │   ├── Camisas │   │   ├── Pantalones │   │   └── Calzado │   └── Niños ├── Hogar │   ├── Decoración │   ├── Cocina │   └── Baño ├── Electrónica ├── Alimentación ├── Belleza └── Deportes
3.2 Campos de Categoría
Campo	Tipo	Descripción	Restricciones
tid	Serial	ID de término	PRIMARY KEY
name	VARCHAR(255)	Nombre categoría	NOT NULL
description	TEXT	Descripción	NULLABLE
parent_tid	INT	Categoría padre	FK taxonomy_term.tid, 0 = raíz
weight	INT	Orden de display	DEFAULT 0
image_fid	INT	Imagen de categoría	FK file_managed.fid, NULLABLE
icon	VARCHAR(64)	Icono (clase CSS)	NULLABLE, ej: 'icon-fashion'
attributes	JSON	Atributos aplicables	Array de product_attribute.id
seo_title	VARCHAR(70)	Meta title	NULLABLE
seo_description	VARCHAR(160)	Meta description	NULLABLE
is_featured	BOOLEAN	Destacada en home	DEFAULT FALSE
3.3 Atributos por Categoría
Cada categoría define qué atributos son relevantes para sus productos:
Categoría	Atributos Aplicables	Filtros Principales
Moda > Mujer > Tops	Talla (XS-3XL), Color, Material	Talla, Color, Precio
Moda > Calzado	Talla (35-46), Color, Material	Talla, Color, Marca
Hogar > Decoración	Color, Material, Dimensiones	Color, Precio, Estilo
Electrónica	Capacidad, Color, Conectividad	Marca, Precio, Specs
Alimentación	Peso, Origen, Alérgenos	Origen, Tipo, Precio
 
4. Sistema de Tallas
ComercioConecta implementa un sistema de tallas flexible que soporta múltiples estándares internacionales y conversiones automáticas.
4.1 Grupos de Tallas
Grupo	Tipo	Valores	Categorías
Ropa General	alpha	XXS, XS, S, M, L, XL, XXL, 3XL	Tops, jerseys, vestidos
Ropa Numérica EU	numeric_eu	32, 34, 36, 38, 40, 42, 44, 46, 48, 50	Pantalones mujer
Ropa Numérica Hombre	numeric_men	38, 40, 42, 44, 46, 48, 50, 52, 54	Pantalones, camisas hombre
Calzado EU	shoe_eu	35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46	Zapatos, zapatillas
Calzado UK	shoe_uk	3, 4, 5, 6, 7, 8, 9, 10, 11, 12	Zapatos (UK)
Calzado US	shoe_us	5, 6, 7, 8, 9, 10, 11, 12, 13, 14	Zapatos (US)
Infantil Edad	kids_age	0-3m, 3-6m, 6-12m, 12-18m, 2a, 3a, 4a, 5a...	Ropa bebé/niño
Talla Única	one_size	Única, One Size, U	Accesorios, bufandas
4.2 Tabla de Conversión de Tallas
Alpha	EU Mujer	EU Hombre	UK	US Mujer	US Hombre
XS	34	44	6	2	34
S	36	46	8	4	36
M	38	48	10	6	38
L	40	50	12	8	40
XL	42	52	14	10	42
XXL	44	54	16	12	44
4.3 SizeService
<?php namespace Drupal\jaraba_catalog\Service;  class SizeService {    // Conversión entre sistemas   public function convertSize(string $value, string $fromSystem, string $toSystem): ?string;      // Obtener tallas disponibles para una categoría   public function getSizesForCategory(int $categoryTid): array;      // Guía de tallas para un producto   public function getSizeGuide(ProductRetail $product): array;      // Sugerir talla basada en historial del usuario   public function suggestSize(int $userId, int $productId): ?string;      // Normalizar talla de input externo (import)   public function normalizeSize(string $input, string $context): ?string; }
4.4 Guía de Tallas por Producto
Cada producto puede tener una guía de tallas personalizada con medidas específicas:
// product_retail.size_guide (JSON) {   "type": "clothing_top",   "unit": "cm",   "measurements": [     { "size": "S", "chest": 88, "waist": 72, "length": 65 },     { "size": "M", "chest": 92, "waist": 76, "length": 67 },     { "size": "L", "chest": 96, "waist": 80, "length": 69 },     { "size": "XL", "chest": 100, "waist": 84, "length": 71 }   ],   "fit": "regular",  // slim, regular, loose   "model_info": {     "height": 175,     "size_worn": "M"   } }
 
5. Sistema de Colores
5.1 Paleta de Colores Base
Color	Hex	Familia	Variantes
Blanco	#FFFFFF	Neutros	Blanco roto, Crema, Marfil
Negro	#000000	Neutros	Negro azabache, Antracita
Gris	#808080	Neutros	Gris claro, Gris oscuro, Grafito
Beige	#F5F5DC	Neutros	Arena, Camel, Tostado
Azul marino	#000080	Azules	Navy, Azul noche
Azul claro	#ADD8E6	Azules	Celeste, Cielo, Agua
Rojo	#FF0000	Cálidos	Granate, Burdeos, Coral
Rosa	#FFC0CB	Cálidos	Fucsia, Salmón, Rosa palo
Verde	#008000	Fríos	Oliva, Caqui, Menta
Amarillo	#FFFF00	Cálidos	Mostaza, Oro, Limón
Marrón	#8B4513	Tierra	Chocolate, Caramelo, Cognac
Naranja	#FFA500	Cálidos	Terracota, Melocotón
Morado	#800080	Fríos	Lila, Violeta, Lavanda
Multicolor	N/A	Especiales	Estampado, Rayas, Cuadros
5.2 Swatches de Color
Los colores se muestran como swatches visuales en el frontend. Configuración de swatch:
// product_attribute_value para color {   "value": "Azul marino",   "label": "Navy",   "color_hex": "#000080",   "swatch_type": "solid",  // solid, gradient, image, pattern   "image_fid": null,        // Para patrones/estampados   "border": true            // Borde para colores claros (blanco) }
5.3 Agrupación de Colores para Filtros
Para simplificar la navegación, los colores se agrupan en familias para los filtros:
// Mapeo color específico → familia para filtros $colorFamilies = [   'Blanco' => ['Blanco', 'Blanco roto', 'Crema', 'Marfil', 'Perla'],   'Negro' => ['Negro', 'Negro azabache', 'Antracita'],   'Azul' => ['Azul marino', 'Azul claro', 'Celeste', 'Navy', 'Cobalto'],   'Rojo' => ['Rojo', 'Granate', 'Burdeos', 'Coral', 'Escarlata'],   'Verde' => ['Verde', 'Oliva', 'Caqui', 'Menta', 'Esmeralda'],   // ... ];  // Filtro muestra: Azul (23)  en lugar de Azul marino (5), Navy (8), Celeste (10)
 
6. Servicios del Catálogo
6.1 ProductCatalogService
<?php namespace Drupal\jaraba_catalog\Service;  class ProductCatalogService {    // CRUD de productos   public function createProduct(MerchantProfile $merchant, array $data): ProductRetail;   public function updateProduct(ProductRetail $product, array $data): ProductRetail;   public function deleteProduct(ProductRetail $product): void;   public function duplicateProduct(ProductRetail $product): ProductRetail;      // Variaciones   public function addVariation(ProductRetail $product, array $attributes, array $data): ProductVariationRetail;   public function generateVariations(ProductRetail $product, array $attributeCombinations): array;   public function bulkUpdateVariations(ProductRetail $product, array $updates): int;      // Consultas   public function getByMerchant(MerchantProfile $merchant, array $filters = []): array;   public function getByCategory(int $categoryTid, array $filters = []): array;   public function searchProducts(string $query, array $filters = []): SearchResult;   public function getRelatedProducts(ProductRetail $product, int $limit = 4): array;      // Estado   public function publish(ProductRetail $product): void;   public function unpublish(ProductRetail $product): void;   public function archive(ProductRetail $product): void; }
6.2 VariationGeneratorService
Genera automáticamente variaciones a partir de combinaciones de atributos:
// Ejemplo: generar todas las variaciones de talla + color $product = $catalogService->createProduct($merchant, [   'title' => 'Camiseta Básica',   'price' => 29.95,   // ... ]);  $combinations = [   'size' => ['S', 'M', 'L', 'XL'],   'color' => ['Blanco', 'Negro', 'Azul marino'] ];  $variations = $variationGenerator->generateVariations($product, $combinations); // Resultado: 12 variaciones (4 tallas × 3 colores) // SKUs auto-generados: CAM-BAS-S-WHT, CAM-BAS-M-WHT, CAM-BAS-S-BLK, ...
6.3 PricingService
<?php namespace Drupal\jaraba_catalog\Service;  class PricingService {    // Cálculo de precios   public function getDisplayPrice(ProductVariationRetail $variation): Price;   public function getOriginalPrice(ProductVariationRetail $variation): Price;   public function getDiscountPercentage(ProductVariationRetail $variation): ?float;      // Precios especiales   public function setComparePrice(ProductVariationRetail $variation, float $price): void;   public function setSalePrice(ProductVariationRetail $variation, float $price, ?\DateTime $until): void;   public function clearSalePrice(ProductVariationRetail $variation): void;      // Bulk pricing   public function applyDiscountToCategory(int $categoryTid, float $percentage): int;   public function applyDiscountToMerchant(MerchantProfile $merchant, float $percentage): int;      // Margen   public function calculateMargin(ProductVariationRetail $variation): ?float; }
 
7. Importación de Productos
El sistema soporta importación masiva de productos desde múltiples formatos para facilitar la migración y actualización de catálogos.
7.1 Formatos Soportados
Formato	Extensiones	Uso Típico	Límite
CSV	.csv	Exportación de cualquier sistema	50,000 filas
Excel	.xlsx, .xls	Catálogos manuales	50,000 filas
XML	.xml	Feeds de proveedores	Sin límite (streaming)
JSON	.json	APIs externas	Sin límite (streaming)
Google Sheets	URL	Catálogos colaborativos	10,000 filas
7.2 Estructura CSV de Importación
sku,ean,title,description,category,brand,price,compare_price,size,color,stock,image_url CAM-001,8412345678901,"Camiseta Básica Blanca","100% algodón...",Moda>Mujer>Tops,MiMarca,29.95,39.95,M,Blanco,25,https://... CAM-002,8412345678902,"Camiseta Básica Blanca","100% algodón...",Moda>Mujer>Tops,MiMarca,29.95,39.95,L,Blanco,18,https://... CAM-003,8412345678903,"Camiseta Básica Negra","100% algodón...",Moda>Mujer>Tops,MiMarca,29.95,39.95,M,Negro,30,https://...
7.3 ProductImportService
<?php namespace Drupal\jaraba_catalog\Service;  class ProductImportService {    public function importFromFile(MerchantProfile $merchant, string $filePath, array $mapping): ImportResult;   public function importFromUrl(MerchantProfile $merchant, string $url, array $options): ImportResult;   public function importFromGoogleSheets(MerchantProfile $merchant, string $sheetId): ImportResult;      // Mapeo de columnas   public function detectColumnMapping(string $filePath): array;   public function validateMapping(array $mapping): ValidationResult;      // Procesamiento   public function previewImport(string $filePath, array $mapping, int $limit = 10): array;   public function processInBackground(int $importJobId): void;      // Resultados   public function getImportStatus(int $importJobId): ImportStatus;   public function getImportErrors(int $importJobId): array;   public function rollbackImport(int $importJobId): void; }  class ImportResult {   public int $total;   public int $created;   public int $updated;   public int $skipped;   public int $failed;   public array $errors;   public float $durationSeconds; }
7.4 Reglas de Importación
• Si SKU existe → actualizar producto/variación (upsert)
• Si EAN existe pero SKU diferente → error (código duplicado)
• Categorías se crean automáticamente si no existen
• Marcas se crean automáticamente (sin verificar)
• Imágenes se descargan en background (no bloquean)
• Stock a 0 → variación se marca como out_of_stock pero no se elimina
 
8. Exportación y Feeds
8.1 Feeds Automáticos
Feed	Formato	URL	Uso
Google Merchant	XML (RSS 2.0)	/feeds/{merchant}/google-merchant.xml	Google Shopping
Meta Catalog	XML/CSV	/feeds/{merchant}/meta-catalog.xml	Facebook/Instagram Shops
Sitemap Productos	XML Sitemap	/sitemap-products.xml	SEO, indexación
CSV Export	CSV	/feeds/{merchant}/products.csv	Descarga manual
JSON API	JSON	/api/v1/merchants/{id}/products	Integraciones custom
8.2 Google Merchant Feed
<?xml version="1.0" encoding="UTF-8"?> <rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">   <channel>     <title>Moda Local Santaella - Google Shopping</title>     <link>https://comercioconecta.es/moda-local</link>     <description>Catálogo de productos</description>     <item>       <g:id>CAM-BAS-M-WHT</g:id>       <g:title>Camiseta Básica Blanca - Talla M</g:title>       <g:description>Camiseta de algodón 100% orgánico...</g:description>       <g:link>https://comercioconecta.es/p/camiseta-basica?size=M&amp;color=white</g:link>       <g:image_link>https://cdn.../camiseta-blanca.jpg</g:image_link>       <g:availability>in_stock</g:availability>       <g:price>29.95 EUR</g:price>       <g:sale_price>24.95 EUR</g:sale_price>       <g:brand>EcoWear</g:brand>       <g:gtin>8412345678901</g:gtin>       <g:condition>new</g:condition>       <g:size>M</g:size>       <g:color>White</g:color>       <g:gender>female</g:gender>       <g:age_group>adult</g:age_group>       <g:product_type>Apparel &gt; Women &gt; Tops</g:product_type>     </item>   </channel> </rss>
8.3 FeedGeneratorService
<?php namespace Drupal\jaraba_catalog\Service;  class FeedGeneratorService {    public function generateGoogleMerchantFeed(MerchantProfile $merchant): string;   public function generateMetaCatalogFeed(MerchantProfile $merchant): string;   public function generateProductSitemap(): string;   public function generateCsvExport(MerchantProfile $merchant, array $fields): string;      // Scheduling   public function scheduleRegeneration(MerchantProfile $merchant, string $feedType): void;   public function regenerateAllFeeds(): void;      // Validación   public function validateGoogleMerchantFeed(string $feedUrl): ValidationResult;   public function getGoogleMerchantErrors(MerchantProfile $merchant): array; }
 
9. SEO y Schema.org
9.1 Schema.org Product (Completo)
{   "@context": "https://schema.org",   "@type": "Product",   "name": "Camiseta Básica Algodón Orgánico",   "description": "Camiseta de manga corta fabricada en España con algodón 100% orgánico...",   "image": [     "https://cdn.../camiseta-blanca-front.jpg",     "https://cdn.../camiseta-blanca-back.jpg"   ],   "sku": "CAM-BAS-001",   "gtin13": "8412345678901",   "brand": { "@type": "Brand", "name": "EcoWear" },   "manufacturer": { "@type": "Organization", "name": "Textiles Eco España" },   "color": "Blanco",   "material": "100% algodón orgánico",   "audience": { "@type": "PeopleAudience", "suggestedGender": "female" },   "offers": {     "@type": "AggregateOffer",     "lowPrice": "29.95",     "highPrice": "29.95",     "priceCurrency": "EUR",     "availability": "https://schema.org/InStock",     "itemCondition": "https://schema.org/NewCondition",     "seller": { "@type": "LocalBusiness", "name": "Moda Local Santaella" },     "priceValidUntil": "2026-12-31",     "shippingDetails": { "@type": "OfferShippingDetails", "..." },     "hasMerchantReturnPolicy": { "@type": "MerchantReturnPolicy", "..." }   },   "aggregateRating": {     "@type": "AggregateRating",     "ratingValue": "4.7",     "reviewCount": "34"   },   "review": [ { "@type": "Review", "..." } ] }
9.2 Answer Capsule (GEO)
Los primeros 150 caracteres de la descripción deben ser una Answer Capsule para IA:
// Formato: [Qué es] + [Material/Origen] + [Diferenciador] + [Beneficio]  // ✅ Bueno: "Camiseta básica de manga corta 100% algodón orgánico certificado GOTS,  fabricada en España. Corte regular, ideal para looks casuales y sostenibles."  // ❌ Malo: "¡OFERTA! Compra ya esta increíble camiseta. Envío gratis.  Disponible en todas las tallas. La mejor calidad al mejor precio."
9.3 URLs Amigables
// Estructura de URLs de producto /p/{product-slug}                              // Producto sin variación seleccionada /p/{product-slug}?size=M&color=blanco          // Con variación preseleccionada /p/{product-slug}/{variation-sku}              // URL canónica de variación  // Ejemplos: /p/camiseta-basica-algodon-organico /p/camiseta-basica-algodon-organico?size=M&color=blanco /p/camiseta-basica-algodon-organico/CAM-BAS-M-WHT
 
10. APIs REST
10.1 Endpoints de Catálogo (Merchant)
Método	Endpoint	Descripción	Auth
GET	/api/v1/products	Listar productos del comercio	Merchant
POST	/api/v1/products	Crear producto	Merchant
GET	/api/v1/products/{id}	Detalle de producto	Merchant
PATCH	/api/v1/products/{id}	Actualizar producto	Merchant
DELETE	/api/v1/products/{id}	Eliminar producto	Merchant
POST	/api/v1/products/{id}/variations	Añadir variación	Merchant
POST	/api/v1/products/{id}/generate-variations	Generar variaciones	Merchant
PATCH	/api/v1/products/{id}/variations/bulk	Update masivo variaciones	Merchant
POST	/api/v1/products/{id}/media	Añadir imagen/vídeo	Merchant
POST	/api/v1/products/import	Importar desde archivo	Merchant
GET	/api/v1/products/export	Exportar catálogo	Merchant
10.2 Endpoints Públicos (Frontend)
Método	Endpoint	Descripción	Auth
GET	/api/v1/catalog/products	Listar productos (filtros, paginación)	Público
GET	/api/v1/catalog/products/{slug}	Detalle de producto	Público
GET	/api/v1/catalog/categories	Árbol de categorías	Público
GET	/api/v1/catalog/categories/{slug}/products	Productos de categoría	Público
GET	/api/v1/catalog/brands	Listar marcas	Público
GET	/api/v1/catalog/brands/{slug}/products	Productos de marca	Público
GET	/api/v1/catalog/search	Búsqueda full-text	Público
GET	/api/v1/catalog/filters	Filtros disponibles	Público
GET	/api/v1/catalog/products/{id}/related	Productos relacionados	Público
10.3 Query Parameters de Filtrado
GET /api/v1/catalog/products?   category=moda-mujer-tops&           // Slug de categoría   brand=ecowear,mimarca&              // Marcas (OR)   size=S,M,L&                         // Tallas (OR)   color=blanco,negro&                 // Colores (OR)   price_min=20&price_max=50&          // Rango de precio   in_stock=true&                      // Solo con stock   on_sale=true&                       // Solo en oferta   sort=price_asc&                     // Ordenamiento   page=1&limit=24                     // Paginación
 
11. Flujos de Automatización (ECA)
11.1 ECA-CAT-001: Producto Publicado
Trigger: product_retail.is_published cambia a TRUE
1. Validar que tiene al menos 1 variación con stock > 0
2. Validar que tiene al menos 1 imagen
3. Generar Schema.org JSON-LD
4. Indexar en Search API (Solr)
5. Marcar feeds como dirty (regenerar en próximo cron)
6. Webhook product.published
11.2 ECA-CAT-002: Variación Sin Stock
Trigger: stock_level.quantity = 0 para una variación
1. Marcar variación como out_of_stock
2. Si TODAS las variaciones del producto sin stock → marcar producto como out_of_stock
3. Actualizar Schema.org availability
4. Notificar al comerciante si el producto era bestseller
11.3 ECA-CAT-003: Importación Completada
Trigger: Import job status = 'completed'
1. Enviar email al comerciante con resumen
2. Reindexar productos importados/actualizados
3. Regenerar feeds de Google Merchant y Meta
4. Si errores > 10% → marcar para revisión manual
11.4 ECA-CAT-004: Precio Modificado
Trigger: variation.price_amount cambia
1. Recalcular descuento si hay compare_price
2. Actualizar Schema.org price
3. Marcar feeds como dirty
4. Si baja > 20% → candidato para Flash Offer
 
12. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades product_attribute, product_attribute_value. Sistema de tallas y colores base. AttributeManager.	62_Commerce_Core
Sprint 2	Semana 3-4	Entidad brand con CRUD. Entidad product_media. MediaManager. Integración con file system.	Sprint 1
Sprint 3	Semana 5-6	ProductCatalogService completo. VariationGeneratorService. PricingService. APIs de catálogo.	Sprint 2
Sprint 4	Semana 7-8	ProductImportService (CSV, Excel). UI de importación con mapeo. Procesamiento en background.	Sprint 3
Sprint 5	Semana 9-10	FeedGeneratorService. Google Merchant Feed. Meta Catalog Feed. Sitemap de productos.	Sprint 4
Sprint 6	Semana 11-12	Schema.org completo. Answer Capsules. SEO automático. Flujos ECA. QA y go-live.	Sprint 5
12.1 Criterios de Aceptación Sprint 3
✓ CRUD completo de productos desde API
✓ Generación automática de variaciones talla × color
✓ Precios con compare_price y cálculo de descuento
✓ Búsqueda con filtros funcionando
✓ Tests unitarios con cobertura > 80%
12.2 Dependencias Externas
• Drupal Commerce 3.x (product, variation)
• Search API + Solr 9.x
• PhpSpreadsheet: phpoffice/phpspreadsheet ^1.29
• Intervention Image: intervention/image ^3.0
• Google Merchant Center API (para validación)
--- Fin del Documento ---
66_ComercioConecta_Product_Catalog_v1.docx | Jaraba Impact Platform | Enero 2026
