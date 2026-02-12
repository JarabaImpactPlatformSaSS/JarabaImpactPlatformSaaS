BÚSQUEDA Y DESCUBRIMIENTO
Search, Filtros, Categorías y Navegación Facetada
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	55_AgroConecta_Search_Discovery
Dependencias:	48_Product_Catalog, Search API, Elasticsearch
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Búsqueda y Descubrimiento para AgroConecta, que permite a los usuarios encontrar productos de forma rápida e intuitiva mediante búsqueda textual, filtros facetados, navegación por categorías y recomendaciones personalizadas.
1.1 Objetivos del Sistema
•	Relevancia: Resultados precisos y ordenados por relevancia
•	Velocidad: Respuesta < 200ms incluso con catálogo grande
•	Usabilidad: Interfaz intuitiva con filtros claros y aplicables
•	Descubrimiento: Ayudar a encontrar productos no buscados activamente
•	Conversión: Optimizar el camino desde búsqueda hasta compra
•	SEO: Páginas de categoría indexables y optimizadas
1.2 Stack Tecnológico
Componente	Tecnología
Motor de Búsqueda	Elasticsearch 8.x / OpenSearch (via Search API module)
Índice Drupal	Search API + Search API Solr/Elasticsearch
Autocomplete	Search API Autocomplete + sugerencias personalizadas
Facetas	Facets module con facetas jerárquicas y rangos
Taxonomías	Drupal Taxonomy con estructura jerárquica (3 niveles)
URLs amigables	Pathauto + Facets Pretty Paths
Caché	Redis/Varnish para resultados frecuentes
Analytics	Tracking de búsquedas, clicks, conversiones
1.3 Modos de Descubrimiento
Modo	Descripción	Uso Principal
Búsqueda textual	Query en caja de búsqueda con autocomplete	Sabe qué busca
Navegación categorías	Explorar árbol de categorías jerárquico	Explora opciones
Filtros facetados	Refinar resultados por atributos	Comparar productos
Colecciones	Agrupaciones temáticas curadas	Inspiración
Productores	Navegar por tienda/productor	Fidelización
Recomendaciones	Productos sugeridos por IA/historial	Cross-sell
 
2. Taxonomía de Categorías
Estructura jerárquica de 3 niveles que organiza el catálogo de productos agroalimentarios.
2.1 Entidad: product_category
Campo	Tipo	Descripción	Restricciones
tid	Serial	ID del término	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
vid	VARCHAR(32)	Vocabulario: product_categories	NOT NULL
name	VARCHAR(100)	Nombre de la categoría	NOT NULL
description	TEXT	Descripción para SEO y página	NULLABLE
parent	INT	Categoría padre (0 = raíz)	DEFAULT 0, INDEX
weight	INT	Orden de visualización	DEFAULT 0
slug	VARCHAR(100)	URL amigable	UNIQUE, NOT NULL
image	Image	Imagen representativa	NULLABLE
icon	VARCHAR(50)	Icono (clase CSS o emoji)	NULLABLE
is_featured	BOOLEAN	Mostrar en home/menú	DEFAULT FALSE
meta_title	VARCHAR(70)	Title tag para SEO	NULLABLE
meta_description	VARCHAR(160)	Meta description	NULLABLE
product_count	INT	Contador de productos (cache)	DEFAULT 0
2.2 Árbol de Categorías
🫒 Aceites y Vinagres
   ├── Aceite de Oliva Virgen Extra
   │   ├── Picual
   │   ├── Hojiblanca
   │   ├── Arbequina
   │   └── Coupage / Blend
   ├── Otros Aceites
   └── Vinagres

🍷 Vinos y Bebidas
   ├── Vinos Tintos
   ├── Vinos Blancos
   ├── Vinos Rosados
   ├── Espumosos y Cavas
   └── Licores y Destilados

🧀 Quesos y Lácteos
   ├── Quesos de Vaca
   ├── Quesos de Oveja
   ├── Quesos de Cabra
   └── Otros Lácteos

🥩 Carnes y Embutidos
   ├── Jamón y Paleta
   ├── Embutidos Curados
   └── Carnes Frescas

🍯 Dulces y Conservas
   ├── Miel
   ├── Mermeladas
   └── Conservas

🥬 Frutas y Verduras
   ├── Frutas de Temporada
   ├── Verduras y Hortalizas
   └── Legumbres y Cereales
 
3. Motor de Búsqueda
Configuración del índice de búsqueda y algoritmos de relevancia para encontrar productos.
3.1 Campos Indexados
Campo	Tipo Índice	Boost	Notas
title	fulltext + keyword	5.0	Campo principal de búsqueda
sku	keyword	4.0	Búsqueda exacta por código
body	fulltext	2.0	Descripción del producto
category_name	fulltext + keyword	3.0	Nombres de categorías
producer_name	fulltext + keyword	3.0	Nombre del productor
tags	keyword	2.5	Etiquetas del producto
attributes	keyword	1.5	Formato, origen, etc.
price	float	-	Para filtros y ordenación
rating	float	-	Valoración media
stock_status	boolean	-	En stock / agotado
created	date	-	Para ordenar por novedad
3.2 Algoritmo de Relevancia
Factores que influyen en el orden de resultados:
1.	Text Match Score (40%): BM25 con boost por campo
2.	Popularidad (25%): Ventas últimos 30 días + vistas
3.	Valoración (15%): Rating × log(num_reviews)
4.	Disponibilidad (10%): En stock > bajo stock > agotado
5.	Recency (10%): Productos nuevos con boost temporal
3.3 Autocomplete
Sistema de sugerencias mientras el usuario escribe:
┌─────────────────────────────────────────────┐
│  🔍 acei                                    │
├─────────────────────────────────────────────┤
│  📂 Categorías                              │
│     Aceites y Vinagres                      │
│     Aceite de Oliva Virgen Extra            │
│                                             │
│  🏷️ Productos                               │
│     AOVE Picual Premium 500ml      €12.50   │
│     AOVE Hojiblanca 1L             €18.90   │
│     Aceite de Coco Ecológico       €8.50    │
│                                             │
│  🏪 Productores                             │
│     Finca Los Olivos (Aceites)              │
│                                             │
│  🔎 Buscar "acei" en todo el catálogo       │
└─────────────────────────────────────────────┘
Configuración del autocomplete:
•	Trigger: Activar tras 2 caracteres
•	Debounce: 150ms para evitar exceso de peticiones
•	Max resultados: 3 categorías + 5 productos + 2 productores
•	Histórico: Mostrar últimas 3 búsquedas del usuario
 
4. Filtros Facetados
Sistema de filtros dinámicos que permiten refinar los resultados de búsqueda o navegación.
4.1 Facetas Disponibles
Faceta	Tipo	Widget	Ordenación
Categoría	Jerárquica	Tree / Dropdown	Por peso
Precio	Rango numérico	Slider doble	N/A
Productor	Lista múltiple	Checkboxes	Por count desc
Valoración	Mínimo	Estrellas clickables	5→1
Origen / D.O.	Lista múltiple	Checkboxes	Alfabético
Formato	Lista múltiple	Checkboxes	Por count desc
Certificaciones	Lista múltiple	Checkboxes con icono	Por count desc
Disponibilidad	Booleano	Toggle	N/A
Envío gratis	Booleano	Toggle	N/A
4.2 Layout de Página de Búsqueda
┌─────────────────────────────────────────────────────────────────────────┐
│  🔍 "aceite oliva"                              [Buscar]               │
├─────────────────────────────────────────────────────────────────────────┤
│  127 resultados para "aceite oliva"    Ordenar: [Relevancia ▼]         │
│  Filtros activos: [Ecológico ✕] [> €10 ✕]       [Limpiar filtros]       │
├──────────────────┬──────────────────────────────────────────────────────┤
│  FILTROS         │  RESULTADOS                                         │
│                  │                                                     │
│  📂 Categoría    │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐    │
│  ▼ Aceites (127) │  │ [Img]   │ │ [Img]   │ │ [Img]   │ │ [Img]   │    │
│    ├ AOVE (98)   │  │ AOVE    │ │ AOVE    │ │ Aceite  │ │ Pack    │    │
│    └ Otros (29)  │  │ Picual  │ │ Hojib.  │ │ Arbequi │ │ Degust. │    │
│                  │  │ €12.50  │ │ €18.90  │ │ €14.00  │ │ €35.00  │    │
│  💰 Precio       │  │ ⭐ 4.8  │ │ ⭐ 4.6  │ │ ⭐ 4.9  │ │ ⭐ 4.7  │    │
│  €5 ───●───● €50 │  └─────────┘ └─────────┘ └─────────┘ └─────────┘    │
│  [€10] - [€50]   │                                                     │
│                  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐    │
│  ⭐ Valoración   │  │ [Img]   │ │ [Img]   │ │ [Img]   │ │ [Img]   │    │
│  ☆☆☆☆☆ y más(5) │  │ ...     │ │ ...     │ │ ...     │ │ ...     │    │
│  ★☆☆☆☆ y más(89)│  └─────────┘ └─────────┘ └─────────┘ └─────────┘    │
│                  │                                                     │
│  🏷️ Certificado │                                                     │
│  [✓] Ecológico   │              [1] [2] [3] ... [13] [→]               │
│  [ ] D.O.P.      │                                                     │
└──────────────────┴──────────────────────────────────────────────────────┘
4.3 URLs Amigables para Facetas
Estructura de URLs SEO-friendly para filtros:
Patrón	Ejemplo
/categoria/{slug}	/categoria/aceites-vinagres
/categoria/{parent}/{child}	/categoria/aceites/aove-picual
/buscar/{query}	/buscar/aceite-oliva
/categoria/{slug}/precio-{min}-{max}	/categoria/vinos/precio-10-30
/categoria/{slug}/certificado-{cert}	/categoria/aceites/certificado-ecologico
/productor/{slug}	/productor/finca-los-olivos
/coleccion/{slug}	/coleccion/navidad-2026
 
5. Ordenación de Resultados
5.1 Opciones de Ordenación
Opción	Lógica	Default
Relevancia	Score de búsqueda combinado (ver 3.2)	✓ En búsqueda
Más vendidos	Unidades vendidas últimos 30 días DESC	✓ En categoría
Mejor valorados	Rating DESC, luego num_reviews DESC	
Precio: menor a mayor	Precio ASC	
Precio: mayor a menor	Precio DESC	
Novedades	Fecha de creación DESC	
Alfabético A-Z	Título ASC	
5.2 Productos Destacados
Reglas para posicionar productos en posiciones privilegiadas:
•	Pinning manual: Admin puede fijar productos en top de categoría
•	Promocionados: Productos con promoción activa suben posiciones
•	Nuevos: Boost temporal para productos < 30 días
•	Agotados: Siempre al final de resultados
 
6. Colecciones y Landing Pages
Agrupaciones temáticas de productos curadas manualmente o generadas por reglas.
6.1 Entidad: collection
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
title	VARCHAR(100)	Nombre de la colección	NOT NULL
slug	VARCHAR(100)	URL amigable	UNIQUE, NOT NULL
description	TEXT	Descripción para SEO	NULLABLE
image	Image	Banner de la colección	NULLABLE
type	VARCHAR(32)	Tipo de colección	ENUM: manual|smart
rules	JSON	Reglas para smart collection	NULLABLE
sort_order	VARCHAR(32)	Ordenación de productos	DEFAULT 'manual'
is_published	BOOLEAN	Visible públicamente	DEFAULT FALSE
publish_from	DATETIME	Fecha inicio publicación	NULLABLE
publish_until	DATETIME	Fecha fin publicación	NULLABLE
meta_title	VARCHAR(70)	Title tag	NULLABLE
meta_description	VARCHAR(160)	Meta description	NULLABLE
6.2 Tipos de Colección
Manual Collection
Productos añadidos y ordenados manualmente por el admin:
•	Selección directa de productos específicos
•	Orden personalizado (drag & drop)
•	Ideal para: 'Selección del mes', 'Favoritos del equipo', 'Regalos'
Smart Collection
Productos que cumplen reglas definidas (se actualizan automáticamente):
// Ejemplo: Colección 'Ofertas de Navidad'
rules: {
  "conditions": [
    {"field": "tags", "operator": "contains", "value": "navidad"},
    {"field": "has_discount", "operator": "equals", "value": true}
  ],
  "logic": "AND",
  "sort": "discount_percentage_desc",
  "limit": 50
}
6.3 Ejemplos de Colecciones
Colección	Tipo	Criterio
Navidad 2026	Smart	Tag 'navidad' + publicado + período dic
Productos Ecológicos	Smart	Certificación = 'ecológico'
Novedades	Smart	Creado últimos 30 días, ordenado por fecha
Más vendidos	Smart	Top 50 por ventas_30d
Selección del Chef	Manual	Curada por equipo editorial
Packs y Regalos	Smart	Tipo producto = 'pack'
Ofertas Flash	Smart	Promoción activa + fin < 48h
 
7. Sistema de Recomendaciones
Algoritmos para sugerir productos relevantes y aumentar el valor del carrito.
7.1 Tipos de Recomendación
Tipo	Algoritmo	Ubicación
Productos relacionados	Misma categoría + tags similares	Ficha de producto
Comprados juntos	Análisis de pedidos históricos	Ficha + Carrito
Vistos recientemente	Historial de navegación (localStorage)	Home + Categoría
Para ti	Basado en compras + favoritos del usuario	Home (logged in)
Completa tu pedido	Productos complementarios al carrito	Carrito + Checkout
Del mismo productor	Otros productos del productor	Ficha de producto
Los clientes también vieron	Productos vistos en la misma sesión	Ficha de producto
7.2 Algoritmo 'Comprados Juntos'
function getFrequentlyBoughtTogether(productId) {
  // 1. Obtener pedidos que contienen este producto
  const orders = getOrdersContaining(productId, last6Months);
  
  // 2. Contar otros productos en esos pedidos
  const coProducts = countCoProducts(orders, productId);
  
  // 3. Calcular score de afinidad
  // Score = (veces juntos / total pedidos producto) * log(veces juntos)
  const scored = coProducts.map(p => ({
    ...p,
    score: (p.count / orders.length) * Math.log(p.count + 1)
  }));
  
  // 4. Devolver top 4, excluyendo agotados
  return scored
    .filter(p => p.inStock)
    .sort((a, b) => b.score - a.score)
    .slice(0, 4);
}
 
8. APIs de Búsqueda y Descubrimiento
8.1 Endpoints de Búsqueda
Método	Endpoint	Descripción
GET	/api/v1/search	Búsqueda con query y filtros
GET	/api/v1/search/autocomplete	Sugerencias de autocomplete
GET	/api/v1/search/suggestions	Sugerencias de búsqueda populares
8.2 Endpoints de Categorías
Método	Endpoint	Descripción
GET	/api/v1/categories	Árbol completo de categorías
GET	/api/v1/categories/{slug}	Detalle de categoría
GET	/api/v1/categories/{slug}/products	Productos de una categoría
GET	/api/v1/categories/{slug}/facets	Facetas disponibles
8.3 Endpoints de Colecciones
Método	Endpoint	Descripción
GET	/api/v1/collections	Listar colecciones publicadas
GET	/api/v1/collections/{slug}	Detalle de colección
GET	/api/v1/collections/{slug}/products	Productos de la colección
8.4 Endpoints de Recomendaciones
Método	Endpoint	Descripción
GET	/api/v1/products/{id}/related	Productos relacionados
GET	/api/v1/products/{id}/bought-together	Comprados juntos frecuentemente
GET	/api/v1/recommendations/for-you	Recomendaciones personalizadas
POST	/api/v1/recommendations/cart	Sugerencias para el carrito actual
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Taxonomía de categorías. Configuración Elasticsearch/Search API. Índice básico.	48_Product_Catalog
Sprint 2	Semana 3-4	Búsqueda fulltext. Autocomplete. Página de resultados básica.	Elasticsearch
Sprint 3	Semana 5-6	Filtros facetados: categoría, precio, rating. URLs amigables (Pretty Paths).	Facets module
Sprint 4	Semana 7-8	Páginas de categoría. Colecciones (manual y smart). Ordenación.	Sprint 3
Sprint 5	Semana 9-10	Recomendaciones: relacionados, comprados juntos. Widgets reutilizables.	Sprint 4
Sprint 6	Semana 11-12	SEO: meta tags, Schema.org. Analytics de búsqueda. Optimización. QA.	Sprint 5
--- Fin del Documento ---
55_AgroConecta_Search_Discovery_v1.docx | Jaraba Impact Platform | Enero 2026
