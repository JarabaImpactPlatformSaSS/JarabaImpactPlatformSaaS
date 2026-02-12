SISTEMA DE CATÁLOGO DE PRODUCTOS
Búsqueda Facetada, URLs SEO y Gestión Avanzada
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	48_AgroConecta_Product_Catalog
Dependencias:	47_Commerce_Core, 01_Core_Entidades, Search API
 
1. Resumen Ejecutivo
Este documento especifica el sistema de catálogo de productos para AgroConecta, incluyendo la arquitectura de búsqueda facetada, estructura de URLs SEO-friendly, vistas de listado, y gestión avanzada de contenido. El catálogo es la interfaz principal entre productores y consumidores.
1.1 Objetivos del Sistema
•	Búsqueda de alto rendimiento: Search API + Solr/Elasticsearch para millones de productos
•	Filtrado facetado: Categoría, origen, certificaciones, precio, disponibilidad
•	URLs semánticas: /aceite-oliva-virgen-extra/priego-cordoba/finca-los-olivos
•	SEO/GEO optimizado: Meta tags dinámicos, breadcrumbs estructurados, sitemaps XML
•	Experiencia móvil: Filtros responsive, infinite scroll, lazy loading de imágenes
1.2 Stack Tecnológico
Componente	Tecnología
Motor de Búsqueda	Search API + Solr 9.x (producción) o Database (desarrollo)
Facetas	Facets module con widget customizado para filtros agrarios
URLs	Pathauto + Custom Route Subscriber para patrones complejos
Vistas	Views module con displays personalizados (grid, list, map)
SEO	Metatag, Schema.org (via JSON-LD), XML Sitemap, Redirect
Rendimiento	BigPipe, Lazy Builder, Image Styles WebP, CDN ready
Frontend	Twig templates + Alpine.js para interactividad ligera
 
2. Arquitectura de Búsqueda
El catálogo utiliza Search API como capa de abstracción sobre el motor de búsqueda, permitiendo cambiar entre backends (Database, Solr, Elasticsearch) sin modificar la lógica de negocio.
2.1 Índice de Productos: agro_products
Configuración del índice principal de Search API:
Campo Indexado	Tipo Search API	Boost	Uso
title	Fulltext	5.0	Búsqueda principal
body (Answer Capsule)	Fulltext	2.0	Búsqueda + snippets
sku	String	3.0	Búsqueda exacta
producer_name	Fulltext	2.0	Búsqueda por productor
category_tid	Integer	-	Faceta + filtro
origin_region	String	-	Faceta + filtro
certifications	String (multiple)	-	Faceta multiselect
is_organic	Boolean	-	Faceta checkbox
price_amount	Decimal	-	Faceta rango + orden
is_in_stock	Boolean	-	Filtro disponibilidad
rating_average	Decimal	-	Faceta + orden
created	Date	-	Orden por novedad
total_sales	Integer	-	Orden por popularidad
tenant_id	Integer	-	Aislamiento multi-tenant
2.2 Procesadores de Índice
Procesadores configurados en el índice para mejorar relevancia:
Procesador	Función
HTML Filter	Elimina tags HTML de body para indexación limpia
Ignore Case	Búsqueda case-insensitive
Tokenizer	Divide texto en tokens por espacios y puntuación
Stemmer (Spanish)	Reduce palabras a raíz: 'aceites' → 'aceit'
Stopwords (Spanish)	Ignora palabras comunes: 'de', 'el', 'la', 'con'
Synonym (Custom)	AOVE = Aceite Oliva Virgen Extra, eco = ecológico
Boost by Freshness	Productos recientes tienen ligero boost
Boost by Rating	Productos mejor valorados aparecen primero
Content Access	Solo indexa productos publicados
Entity Status	Excluye productos de productores inactivos
 
3. Sistema de Facetas
Las facetas permiten a los usuarios refinar resultados de búsqueda de forma intuitiva. Se implementan con el módulo Facets de Drupal integrado con Search API.
3.1 Facetas Configuradas
Faceta	Widget	Operador	Configuración
Categoría	Hierarchical Links	OR	Árbol expandible, mostrar conteo
Región de Origen	Checkbox Links	OR	Ordenar por conteo DESC, top 10
Certificaciones	Checkbox Links	AND	Mostrar iconos junto al nombre
Ecológico	Boolean Checkbox	-	'Solo productos ecológicos'
Rango de Precio	Range Slider	-	Min/Max dinámico, step: 1€
Valoración	Star Rating	>=	4+ estrellas, 3+ estrellas, etc.
Disponibilidad	Boolean Checkbox	-	'Solo en stock' (default ON)
Productor	Dropdown Select	OR	Búsqueda autocomplete
Formato	Checkbox Links	OR	Botella, Caja, Saco...
3.2 URLs de Facetas (Pretty Paths)
Las facetas generan URLs limpias y legibles para SEO usando el módulo Facets Pretty Paths:
# Estructura de URL con facetas
/productos/categoria/aceites/origen/cordoba/certificacion/ecologico

# Múltiples valores en una faceta (OR)
/productos/categoria/vinos+quesos/origen/montilla-moriles

# Búsqueda con facetas
/productos/buscar/aceite+picual/categoria/aceites/precio/10-25
3.3 Comportamiento de Facetas
•	Hide empty facets: No mostrar opciones sin resultados
•	Show counts: Número de productos por cada opción
•	AJAX refresh: Actualización sin recarga de página
•	URL update: pushState para historial del navegador
•	Mobile collapse: Facetas en drawer lateral en móvil
•	Reset link: Botón 'Limpiar filtros' siempre visible
 
4. Estructura de URLs SEO
Las URLs del catálogo siguen una estructura jerárquica semántica optimizada para SEO y comprensión humana. Se implementan con Pathauto + Custom Route Subscribers.
4.1 Patrones de URL
Tipo de Página	Patrón de URL	Ejemplo
Listado general	/productos	-
Categoría nivel 1	/productos/[category-slug]	/productos/aceites
Categoría nivel 2	/productos/[cat-l1]/[cat-l2]	/productos/aceites/virgen-extra
Producto individual	/producto/[product-slug]	/producto/aove-picual-finca
Listado por región	/productos/origen/[region-slug]	/productos/origen/cordoba
Listado por certificación	/productos/certificacion/[cert-slug]	/productos/certificacion/ecologico
Listado por productor	/productor/[producer-slug]/productos	/productor/bodegas-robles/productos
Perfil de productor	/productor/[producer-slug]	/productor/bodegas-robles
Resultados búsqueda	/productos/buscar/[keywords]	/productos/buscar/vino-dulce
Ofertas/Promociones	/productos/ofertas	-
Nuevos productos	/productos/novedades	-
4.2 Configuración Pathauto
Patrones configurados en Pathauto para generación automática de alias:
# Producto agrario
Patrón: producto/[product_agro:title]
Resultado: /producto/aceite-oliva-virgen-extra-picual-500ml

# Perfil de productor
Patrón: productor/[producer_profile:slug]
Resultado: /productor/finca-los-olivos

# Término de taxonomía (categorías)
Patrón: productos/[term:parents:join-path]/[term:name]
Resultado: /productos/aceites/virgen-extra
4.3 Redirecciones y Canonical
•	301 Redirect: Automático si se modifica título/slug del producto
•	Canonical URL: Siempre apunta a URL sin facetas para evitar duplicados
•	Trailing slash: Configuración consistente sin slash final
•	Lowercase enforce: Todas las URLs en minúsculas
 
5. Vistas y Displays
El catálogo ofrece múltiples formas de visualizar productos según el contexto y preferencia del usuario. Se implementan con Drupal Views y templates Twig personalizados.
5.1 Vista: agro_product_catalog
Vista principal del catálogo con múltiples displays:
Display	Path / Uso	Configuración
page_grid	/productos (default)	Grid 4 cols desktop, 2 tablet, 1 móvil. 24 items/página
page_list	/productos?view=list	Lista vertical con más detalle. 12 items/página
page_map	/productos?view=map	Mapa con marcadores de productores. Leaflet.js
block_featured	Homepage carousel	6 productos destacados. Filtro: is_featured = TRUE
block_new	Homepage / Sidebar	8 productos más recientes. Orden: created DESC
block_offers	Banner ofertas	Productos con compare_price > 0
block_producer	Página de productor	Productos del productor. Contextual filter: producer_id
block_related	Página de producto	4 productos misma categoría. Excluir actual
feed_rss	/productos/feed.xml	RSS 2.0 para agregadores
rest_export	/api/v1/catalog	JSON para integraciones. Paginación: 100/request
5.2 Product Card Component
Estructura del componente de tarjeta de producto (Twig):
•	Imagen principal: Lazy loading, WebP con fallback JPG, aspect ratio 1:1
•	Badges: Ecológico (verde), Oferta (rojo), Nuevo (azul), Agotado (gris)
•	Título: Max 2 líneas con ellipsis, link a producto
•	Productor: Nombre con link, badge verificado si aplica
•	Origen: Región + icono de ubicación
•	Rating: Estrellas + número de reseñas
•	Precio: Precio actual (destacado) + precio anterior tachado si oferta
•	CTA: Botón 'Añadir al carrito' o 'Ver opciones' si hay variaciones
•	Quick actions: Iconos de wishlist y quick view (hover)
5.3 Ordenación de Resultados
Opción de Orden	Campo(s)	Notas
Relevancia (default búsqueda)	search_api_relevance DESC	Solo cuando hay keywords
Más populares (default browse)	total_sales DESC, rating DESC	Combinación ventas + valoración
Mejor valorados	rating_average DESC, rating_count DESC	Mínimo 5 reseñas para ranking
Precio: menor a mayor	price_amount ASC	-
Precio: mayor a menor	price_amount DESC	-
Más recientes	created DESC	-
Nombre A-Z	title ASC	-
 
6. SEO y Metatags
Cada página del catálogo genera metatags optimizados automáticamente usando el módulo Metatag con tokens dinámicos.
6.1 Metatags por Tipo de Página
Página de Producto
Meta Tag	Valor / Patrón
title	[product:title] | [product:producer] | AgroConecta
description	[product:summary] - Compra online desde [product:price]€. Envío a toda España.
og:title	[product:title] - [product:origin_region]
og:description	[product:summary]
og:image	[product:image:url] (1200x630 social crop)
og:type	product
product:price:amount	[product:price]
product:price:currency	EUR
canonical	[product:url:absolute]
Página de Categoría
Meta Tag	Valor / Patrón
title	Comprar [category:name] Online | Productos Locales | AgroConecta
description	Descubre [category:count] productos de [category:name] de productores locales verificados. Envío directo del productor. ✓ Ecológico ✓ Certificado
robots	index, follow (noindex si página > 3 o con filtros complejos)
6.2 Breadcrumbs Estructurados
Breadcrumbs con Schema.org BreadcrumbList para rich snippets:
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
  {"@type":"ListItem","position":1,"name":"Inicio","item":"https://agroconecta.es"},
  {"@type":"ListItem","position":2,"name":"Aceites","item":"https://agroconecta.es/productos/aceites"},
  {"@type":"ListItem","position":3,"name":"Virgen Extra","item":"https://agroconecta.es/productos/aceites/virgen-extra"},
  {"@type":"ListItem","position":4,"name":"AOVE Picual Finca Los Olivos"}
]}
6.3 XML Sitemap
•	Productos: /sitemap-products.xml - Prioridad 0.8, changefreq weekly
•	Categorías: /sitemap-categories.xml - Prioridad 0.6, changefreq monthly
•	Productores: /sitemap-producers.xml - Prioridad 0.7, changefreq weekly
•	Imágenes: Incluidas en sitemap de productos con <image:image>
•	Límite: 50,000 URLs por archivo, índice si se supera
 
7. Rendimiento y Caché
El catálogo está optimizado para servir miles de productos con tiempos de respuesta < 200ms usando la estrategia de caché de Drupal.
7.1 Estrategia de Caché
Componente	Cache Tags	Invalidación
Listado de productos	product_agro_list, tenant:[id]	Al crear/editar/eliminar cualquier producto
Página de producto	product_agro:[id]	Al editar ese producto específico
Bloque de facetas	search_api_list:agro_products	Al reindexar Search API
Perfil de productor	producer_profile:[id]	Al editar perfil o sus productos
Menú de categorías	taxonomy_term_list:agro_category	Al modificar taxonomía
7.2 Optimizaciones de Rendimiento
•	BigPipe: Facetas y bloques dinámicos cargados en placeholders
•	Lazy Builders: Precio y stock actualizados en tiempo real sin invalidar caché de página
•	Image Styles: WebP automático, múltiples tamaños (thumbnail 150x150, card 300x300, full 800x800)
•	Lazy Loading: Imágenes below-the-fold con loading='lazy'
•	Infinite Scroll: Carga AJAX de siguientes páginas sin reload
•	Search API cache: Resultados cacheados por query+facets hash
•	CDN ready: Headers Cache-Control configurados para edge caching
7.3 Métricas Objetivo
Métrica	Objetivo	Herramienta
Time to First Byte (TTFB)	< 200ms	WebPageTest
Largest Contentful Paint (LCP)	< 2.5s	Lighthouse
First Input Delay (FID)	< 100ms	Web Vitals
Cumulative Layout Shift (CLS)	< 0.1	Lighthouse
Lighthouse Performance Score	> 90	Lighthouse
 
8. APIs de Catálogo
Endpoints específicos para operaciones de catálogo, complementando los definidos en 47_Commerce_Core.
Método	Endpoint	Descripción
GET	/api/v1/catalog/search	Búsqueda fulltext con facetas. Params: q, category, region, cert, price_min, price_max, organic, sort, page
GET	/api/v1/catalog/autocomplete	Sugerencias de búsqueda. Param: q (min 2 chars). Retorna productos + categorías + productores
GET	/api/v1/catalog/facets	Valores disponibles de facetas para búsqueda actual. Incluye conteos
GET	/api/v1/catalog/categories/tree	Árbol jerárquico de categorías con conteo de productos
GET	/api/v1/catalog/products/featured	Productos destacados (is_featured = true). Limit default: 8
GET	/api/v1/catalog/products/new	Productos más recientes. Param: days (default 30)
GET	/api/v1/catalog/products/offers	Productos en oferta (compare_price > price)
GET	/api/v1/catalog/products/{id}/related	Productos relacionados (misma categoría, mismo productor)
GET	/api/v1/catalog/sitemap	URLs de productos para sitemap externo. Paginado, incluye lastmod
8.1 Respuesta de Búsqueda
{
  "meta": {"total": 1234, "page": 1, "per_page": 24, "pages": 52},
  "facets": {
    "category": [{"id": 5, "name": "Aceites", "count": 234, "selected": true}],
    "origin": [{"id": "cordoba", "name": "Córdoba", "count": 89}],
    "price_range": {"min": 5.00, "max": 150.00}
  },
  "products": [
    {"id": 123, "title": "AOVE Picual...", "url": "/producto/aove-picual",
     "image": "https://...", "price": 12.50, "producer": {...}, "rating": 4.8}
  ]
}
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Configuración Search API + Solr. Índice agro_products. Procesadores de texto. Tests de indexación.	47_Commerce_Core
Sprint 2	Semana 3-4	Módulo Facets. Configuración de facetas. Pretty Paths. Widget customizado de precio.	Sprint 1
Sprint 3	Semana 5-6	Views del catálogo. Templates Twig de producto. Grid/List/Map displays. Ordenación.	Sprint 2
Sprint 4	Semana 7-8	Pathauto patterns. URL aliases. Redirecciones. Metatag configuration.	Sprint 3
Sprint 5	Semana 9-10	Schema.org JSON-LD. Breadcrumbs. XML Sitemap. APIs de catálogo.	Sprint 4
Sprint 6	Semana 11-12	Optimización de rendimiento. Caché tuning. Lazy loading. QA SEO. Go-live.	Sprint 5
--- Fin del Documento ---
48_AgroConecta_Product_Catalog_v1.docx | Jaraba Impact Platform | Enero 2026
