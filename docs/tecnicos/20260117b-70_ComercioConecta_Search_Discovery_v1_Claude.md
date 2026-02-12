SISTEMA SEARCH & DISCOVERY
Búsqueda Inteligente y Descubrimiento de Productos
Vertical ComercioConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	70_ComercioConecta_Search_Discovery
Dependencias:	62_Commerce_Core, 66_Product_Catalog
Base:	55_AgroConecta_Search_Discovery (~70% reutilizable)
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Búsqueda y Descubrimiento para ComercioConecta. El sistema proporciona búsqueda full-text con Apache Solr, filtros facetados, autocompletado inteligente, y personalización basada en el comportamiento del usuario.
1.1 Objetivos del Sistema
• Búsqueda full-text en menos de 200ms (p95)
• Autocompletado con sugerencias en menos de 100ms
• Filtros facetados dinámicos por categoría, marca, precio, talla, color
• Corrección ortográfica y sugerencias "¿Quisiste decir?"
• Sinónimos y stemming para español
• Búsqueda geolocalizada: "cerca de mí"
• Analytics de búsqueda para optimización continua
1.2 Métricas de Éxito
Métrica	Benchmark	Objetivo
Search-to-Click Rate	30%	45%
Search-to-Purchase Rate	5%	8%
Zero Results Rate	15%	< 5%
Search Abandonment	25%	< 15%
Autocomplete Usage	40%	60%
Tiempo medio respuesta	500ms	< 200ms
1.3 Stack Tecnológico
Componente	Tecnología	Versión
Motor de búsqueda	Apache Solr	9.x
Integración Drupal	Search API	8.x-1.x
Backend Solr	Search API Solr	4.x
Autocompletado	Search API Autocomplete	1.x
Facetas	Facets	2.x
Spellcheck	Solr Spellcheck	Nativo
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────┐ │                    SEARCH & DISCOVERY SYSTEM                        │ ├─────────────────────────────────────────────────────────────────────┤ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Search     │  │  Autocomplete│  │    Facet                 │  │ │  │   Service    │──│   Service    │──│    Manager               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Index      │  │  Relevance   │  │    Synonym               │  │ │  │   Manager    │──│   Tuner      │──│    Manager               │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ │  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │ │  │   Spellcheck │  │  Geo Search  │  │    Analytics             │  │ │  │   Service    │──│   Service    │──│    Collector             │  │ │  │              │  │              │  │                          │  │ │  └──────────────┘  └──────────────┘  └──────────────────────────┘  │ │                                                                     │ └───────────────────────────────┬─────────────────────────────────────┘                                 │                                 ▼                     ┌───────────────────────┐                     │     Apache Solr 9     │                     │  ┌─────────────────┐  │                     │  │  Product Index  │  │                     │  │  Suggest Index  │  │                     │  │  Spell Index    │  │                     │  └─────────────────┘  │                     └───────────────────────┘
2.2 Flujo de Búsqueda
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐ │  Usuario │───▶│  Query   │───▶│  Solr    │───▶│ Results  │ │  Input   │    │ Builder  │    │  Query   │    │ Renderer │ └──────────┘    └──────────┘    └──────────┘    └──────────┘      │               │               │               │      │               ▼               ▼               ▼      │         ┌──────────┐    ┌──────────┐    ┌──────────┐      │         │ Synonym  │    │ Facets   │    │ Tracking │      │         │ Expansion│    │ Counts   │    │ & Analytics│      │         └──────────┘    └──────────┘    └──────────┘      │      └──────────────────────────────────────────────────────┐                                                            ▼                                                     ┌──────────┐                                                     │Autocomplete│                                                     │ (parallel) │                                                     └──────────┘
 
3. Configuración del Índice Solr
3.1 Schema de Producto
Campos indexados en Solr para cada producto:
Campo Solr	Tipo	Indexed	Stored	Descripción
id	string	Sí	Sí	ID único: product_retail:{id}
ss_title	text_es	Sí	Sí	Título del producto
tm_body	text_es	Sí	No	Descripción completa
ss_sku	string	Sí	Sí	SKU del producto
ss_ean	string	Sí	Sí	Código EAN/UPC
ss_brand	string	Sí	Sí	Marca
sm_categories	string	Sí	Sí	Categorías (multivalor)
ss_category_path	string	Sí	Sí	Path jerárquico
fts_price	tfloat	Sí	Sí	Precio actual
fts_original_price	tfloat	Sí	Sí	Precio original
bs_on_sale	boolean	Sí	Sí	¿Está rebajado?
sm_colors	string	Sí	Sí	Colores disponibles
sm_sizes	string	Sí	Sí	Tallas disponibles
ss_material	string	Sí	Sí	Material
ss_gender	string	Sí	Sí	Género: hombre|mujer|unisex
its_stock	tint	Sí	Sí	Stock total
bs_in_stock	boolean	Sí	Sí	¿Hay stock?
fts_rating	tfloat	Sí	Sí	Valoración media
its_reviews_count	tint	Sí	Sí	Número de reseñas
ss_merchant_id	string	Sí	Sí	ID del comercio
ss_merchant_name	string	Sí	Sí	Nombre del comercio
locs_location	location	Sí	Sí	Coordenadas tienda
ds_created	tdate	Sí	Sí	Fecha creación
its_sales_count	tint	Sí	No	Ventas (para ranking)
ss_image_url	string	No	Sí	URL imagen principal
 
3.2 Configuración de Analizadores
<!-- schema.xml: Analizador para español --> <fieldType name="text_es" class="solr.TextField" positionIncrementGap="100">   <analyzer type="index">     <!-- Tokenización estándar -->     <tokenizer class="solr.StandardTokenizerFactory"/>     <!-- Lowercase -->     <filter class="solr.LowerCaseFilterFactory"/>     <!-- Acentos: á → a -->     <filter class="solr.ASCIIFoldingFilterFactory" preserveOriginal="true"/>     <!-- Stopwords español -->     <filter class="solr.StopFilterFactory" words="lang/stopwords_es.txt"/>     <!-- Stemming español -->     <filter class="solr.SpanishLightStemFilterFactory"/>     <!-- Sinónimos en index time -->     <filter class="solr.SynonymGraphFilterFactory"              synonyms="synonyms_es.txt" expand="true"/>     <filter class="solr.FlattenGraphFilterFactory"/>   </analyzer>   <analyzer type="query">     <tokenizer class="solr.StandardTokenizerFactory"/>     <filter class="solr.LowerCaseFilterFactory"/>     <filter class="solr.ASCIIFoldingFilterFactory" preserveOriginal="true"/>     <filter class="solr.StopFilterFactory" words="lang/stopwords_es.txt"/>     <filter class="solr.SpanishLightStemFilterFactory"/>   </analyzer> </fieldType>
3.3 Sinónimos Español (synonyms_es.txt)
# Ropa camiseta, camiseta, playera, remera, t-shirt, tshirt pantalón, pantalones, vaqueros, jeans, tejanos zapatillas, deportivas, tenis, sneakers chaqueta, cazadora, chamarra, jacket vestido, dress falda, pollera  # Colores blanco, white negro, black azul, blue rojo, red verde, green  # Tallas pequeña, pequeño, small, s, talla s mediana, mediano, medium, m, talla m grande, large, l, talla l extra grande, xl, talla xl  # Materiales algodón, cotton lana, wool seda, silk cuero, piel, leather  # Conceptos barato, económico, low cost, oferta premium, lujo, luxury, alta gama
 
4. Servicio de Búsqueda
4.1 SearchService
<?php namespace Drupal\jaraba_search\Service;  class SearchService {    // Búsqueda principal   public function search(SearchQuery $query): SearchResult;   public function searchProducts(string $keywords, array $filters = []): SearchResult;   public function searchByCategory(int $categoryId, array $filters = []): SearchResult;   public function searchByMerchant(int $merchantId, array $filters = []): SearchResult;      // Filtros   public function applyFilters(SearchQuery $query, array $filters): SearchQuery;   public function getPriceRange(SearchQuery $query): array;      // Ordenación   public function applySorting(SearchQuery $query, string $sort): SearchQuery;      // Paginación   public function paginate(SearchQuery $query, int $page, int $perPage): SearchQuery;      // Utilidades   public function buildQuery(array $params): SearchQuery;   public function parseResults(array $solrResults): SearchResult; }
4.2 SearchQuery Builder
<?php class SearchQueryBuilder {    private string $keywords = '';   private array $filters = [];   private array $facets = [];   private string $sort = 'relevance';   private int $page = 1;   private int $perPage = 24;      public function keywords(string $keywords): self {     $this->keywords = $keywords;     return $this;   }      public function filter(string $field, $value): self {     $this->filters[$field] = $value;     return $this;   }      public function priceRange(float $min, float $max): self {     $this->filters['fts_price'] = "[{$min} TO {$max}]";     return $this;   }      public function inStock(bool $only = true): self {     if ($only) {       $this->filters['bs_in_stock'] = true;     }     return $this;   }      public function nearLocation(float $lat, float $lng, float $radiusKm): self {     $this->filters['_geo'] = "{!geofilt pt={$lat},{$lng} sfield=locs_location d={$radiusKm}}";     return $this;   }      public function withFacets(array $facets): self {     $this->facets = $facets;     return $this;   }      public function sortBy(string $sort): self {     $this->sort = $sort;     return $this;   }      public function build(): SearchQuery; }
 
4.3 Opciones de Ordenación
Código	Campo Solr	Orden	Descripción
relevance	score	DESC	Relevancia (default)
price_asc	fts_price	ASC	Precio: menor a mayor
price_desc	fts_price	DESC	Precio: mayor a menor
newest	ds_created	DESC	Más recientes
bestseller	its_sales_count	DESC	Más vendidos
rating	fts_rating	DESC	Mejor valorados
discount	discount_percentage	DESC	Mayor descuento
name_asc	ss_title	ASC	Nombre A-Z
name_desc	ss_title	DESC	Nombre Z-A
distance	geodist()	ASC	Más cercano (geo)
4.4 Boost de Relevancia
// Factores de boost para ranking de resultados $boostConfig = [   // Boost por campo donde aparece el match   'title_match' => 10.0,     // Match en título: x10   'brand_match' => 5.0,      // Match en marca: x5   'category_match' => 3.0,   // Match en categoría: x3   'description_match' => 1.0, // Match en descripción: x1      // Boost por atributos del producto   'in_stock' => 2.0,         // Con stock: x2   'on_sale' => 1.5,          // En oferta: x1.5   'high_rating' => 1.3,      // Rating > 4: x1.3   'recent' => 1.2,           // Creado hace < 30 días: x1.2   'bestseller' => 1.5,       // Top ventas: x1.5      // Boost por comercio   'verified_merchant' => 1.2, // Comercio verificado: x1.2   'local_merchant' => 1.3,    // Comercio cercano: x1.3 ];  // Query con boosts aplicados $query = "title:{$keywords}^10 brand:{$keywords}^5 category:{$keywords}^3 body:{$keywords}"; $query .= " AND bs_in_stock:true^2";
 
5. Sistema de Facetas y Filtros
5.1 FacetManager
<?php namespace Drupal\jaraba_search\Service;  class FacetManager {    // Configuración de facetas   public function getAvailableFacets(): array;   public function getFacetsForCategory(int $categoryId): array;      // Procesamiento   public function buildFacetQuery(array $facetConfig): array;   public function parseFacetResults(array $solrFacets): array;      // Facetas específicas   public function getCategoryFacet(SearchResult $result): CategoryFacet;   public function getBrandFacet(SearchResult $result): BrandFacet;   public function getPriceFacet(SearchResult $result): PriceFacet;   public function getColorFacet(SearchResult $result): ColorFacet;   public function getSizeFacet(SearchResult $result): SizeFacet;      // Utilidades   public function sortFacetValues(array $values, string $sortBy): array;   public function limitFacetValues(array $values, int $limit): array; }
5.2 Facetas Disponibles
Faceta	Campo Solr	Tipo	UI Component
Categoría	sm_categories	Jerárquica	Tree/Accordion
Marca	ss_brand	Lista checkbox	Checkbox list
Precio	fts_price	Rango slider	Range slider
Color	sm_colors	Swatches	Color swatches
Talla	sm_sizes	Lista checkbox	Button group
Material	ss_material	Lista checkbox	Checkbox list
Género	ss_gender	Radio buttons	Radio group
En oferta	bs_on_sale	Toggle	Switch/Toggle
Valoración	fts_rating	Estrellas	Star rating
Comercio	ss_merchant_name	Lista	Checkbox list
Disponibilidad	bs_in_stock	Toggle	Switch/Toggle
5.3 Faceta de Precio con Rangos
// Configuración de rangos de precio dinámicos public function getPriceRanges(SearchResult $result): array {   $minPrice = $result->stats['fts_price']['min'];   $maxPrice = $result->stats['fts_price']['max'];      // Generar rangos dinámicos basados en distribución   $ranges = [];      if ($maxPrice <= 50) {     $ranges = [       ['min' => 0, 'max' => 10, 'label' => 'Hasta 10€'],       ['min' => 10, 'max' => 25, 'label' => '10€ - 25€'],       ['min' => 25, 'max' => 50, 'label' => '25€ - 50€'],     ];   } elseif ($maxPrice <= 200) {     $ranges = [       ['min' => 0, 'max' => 25, 'label' => 'Hasta 25€'],       ['min' => 25, 'max' => 50, 'label' => '25€ - 50€'],       ['min' => 50, 'max' => 100, 'label' => '50€ - 100€'],       ['min' => 100, 'max' => 200, 'label' => '100€ - 200€'],     ];   } else {     // Rangos para productos de alto valor     $ranges = $this->generateDynamicRanges($minPrice, $maxPrice, 5);   }      return $ranges; }
 
5.4 Faceta Jerárquica de Categorías
// Estructura de faceta de categorías jerárquica [   'Moda' => [     'count' => 1250,     'children' => [       'Mujer' => [         'count' => 680,         'children' => [           'Tops' => ['count' => 230],           'Pantalones' => ['count' => 180],           'Vestidos' => ['count' => 150],           'Faldas' => ['count' => 120]         ]       ],       'Hombre' => [         'count' => 450,         'children' => [           'Camisas' => ['count' => 150],           'Pantalones' => ['count' => 130],           'Chaquetas' => ['count' => 100],           'Camisetas' => ['count' => 70]         ]       ],       'Niños' => ['count' => 120]     ]   ],   'Calzado' => [     'count' => 450,     'children' => [       'Zapatillas' => ['count' => 200],       'Zapatos' => ['count' => 150],       'Botas' => ['count' => 100]     ]   ] ]
5.5 Color Swatches
// Componente React: ColorFacet export function ColorFacet({ colors, selected, onChange }) {   return (     <div className="color-facet">       <h4>Color</h4>       <div className="color-swatches">         {colors.map(color => (           <button             key={color.value}             className={`swatch ${selected.includes(color.value) ? 'selected' : ''}`}             style={{ backgroundColor: color.hex }}             onClick={() => onChange(color.value)}             title={`${color.label} (${color.count})`}           >             {selected.includes(color.value) && <CheckIcon />}           </button>         ))}       </div>     </div>   ); }  // Mapeo de colores a hex const colorMap = {   'blanco': '#FFFFFF',   'negro': '#000000',   'azul_marino': '#1E3A5F',   'azul_claro': '#87CEEB',   'rojo': '#DC143C',   'rosa': '#FFC0CB',   'verde': '#228B22',   'amarillo': '#FFD700',   'marron': '#8B4513',   'gris': '#808080',   'beige': '#F5F5DC',   'multicolor': 'linear-gradient(...)' };
 
6. Sistema de Autocompletado
6.1 AutocompleteService
<?php namespace Drupal\jaraba_search\Service;  class AutocompleteService {    // Sugerencias principales   public function getSuggestions(string $prefix, int $limit = 10): array;      // Tipos de sugerencias   public function getProductSuggestions(string $prefix): array;   public function getCategorySuggestions(string $prefix): array;   public function getBrandSuggestions(string $prefix): array;   public function getPopularSearches(string $prefix): array;   public function getRecentSearches(int $userId, string $prefix): array;      // Combinación de fuentes   public function getBlendedSuggestions(string $prefix): array;      // Tracking   public function trackSelection(string $query, string $suggestion, string $type): void; }
6.2 Tipos de Sugerencias
Tipo	Fuente	Ejemplo	Prioridad
Productos	Índice Solr suggest	"Camiseta básica blanca"	Alta
Categorías	Taxonomía	"Moda > Mujer > Tops"	Alta
Marcas	Entidad brand	"Nike"	Media
Búsquedas populares	search_analytics	"vestido verano"	Media
Historial personal	user_search_history	"zapatillas running"	Baja
Correcciones	Spellchecker	"¿Quisiste decir: camiseta?"	Baja
6.3 Solr Suggester Configuration
<!-- solrconfig.xml: Suggester --> <searchComponent name="suggest" class="solr.SuggestComponent">   <lst name="suggester">     <str name="name">productSuggester</str>     <str name="lookupImpl">AnalyzingInfixLookupFactory</str>     <str name="dictionaryImpl">DocumentDictionaryFactory</str>     <str name="field">ss_title</str>     <str name="weightField">its_sales_count</str>     <str name="suggestAnalyzerFieldType">text_es</str>     <str name="buildOnStartup">false</str>     <str name="buildOnCommit">true</str>     <bool name="exactMatchFirst">true</bool>     <str name="highlight">true</str>   </lst> </searchComponent>  <requestHandler name="/suggest" class="solr.SearchHandler" startup="lazy">   <lst name="defaults">     <str name="suggest">true</str>     <str name="suggest.count">10</str>     <str name="suggest.dictionary">productSuggester</str>   </lst>   <arr name="components">     <str>suggest</str>   </arr> </requestHandler>
 
6.4 Componente React Autocomplete
// SearchAutocomplete.jsx import { useState, useEffect, useCallback } from 'react'; import { debounce } from 'lodash';  export function SearchAutocomplete({ onSearch }) {   const [query, setQuery] = useState('');   const [suggestions, setSuggestions] = useState([]);   const [isOpen, setIsOpen] = useState(false);   const [highlighted, setHighlighted] = useState(-1);      const fetchSuggestions = useCallback(     debounce(async (prefix) => {       if (prefix.length < 2) {         setSuggestions([]);         return;       }              const response = await fetch(`/api/v1/search/autocomplete?q=${prefix}`);       const data = await response.json();       setSuggestions(data.suggestions);       setIsOpen(true);     }, 150), // Debounce 150ms     []   );      useEffect(() => {     fetchSuggestions(query);   }, [query, fetchSuggestions]);      const handleKeyDown = (e) => {     if (e.key === 'ArrowDown') {       setHighlighted(prev => Math.min(prev + 1, suggestions.length - 1));     } else if (e.key === 'ArrowUp') {       setHighlighted(prev => Math.max(prev - 1, -1));     } else if (e.key === 'Enter') {       if (highlighted >= 0) {         selectSuggestion(suggestions[highlighted]);       } else {         onSearch(query);       }     }   };      return (     <div className="search-autocomplete">       <input         type="text"         value={query}         onChange={(e) => setQuery(e.target.value)}         onKeyDown={handleKeyDown}         placeholder="Buscar productos..."       />       {isOpen && suggestions.length > 0 && (         <SuggestionsList            suggestions={suggestions}           highlighted={highlighted}           onSelect={selectSuggestion}         />       )}     </div>   ); }
6.5 Formato de Respuesta Autocomplete
// GET /api/v1/search/autocomplete?q=cami {   "query": "cami",   "suggestions": [     {       "type": "product",       "text": "<em>Cami</em>seta básica algodón",       "value": "camiseta básica algodón",       "url": "/p/camiseta-basica-algodon",       "image": "https://...",       "price": 19.95,       "brand": "Basics"     },     {       "type": "category",       "text": "<em>Cami</em>setas",       "value": "Camisetas",       "url": "/c/moda/mujer/camisetas",       "count": 234     },     {       "type": "brand",       "text": "<em>Cami</em>sería López",       "value": "Camisería López",       "url": "/marca/camiseria-lopez"     },     {       "type": "popular",       "text": "<em>cami</em>seta blanca hombre",       "value": "camiseta blanca hombre",       "searches": 1250     }   ] }
 
7. Corrección Ortográfica
7.1 SpellcheckService
<?php namespace Drupal\jaraba_search\Service;  class SpellcheckService {    public function check(string $query): SpellcheckResult;   public function getSuggestion(string $query): ?string;   public function getAlternatives(string $query, int $limit = 5): array;      // ¿La consulta original tuvo resultados?   public function shouldSuggestCorrection(string $query, int $resultsCount): bool; }
7.2 Configuración Solr Spellcheck
<!-- solrconfig.xml: Spellchecker --> <searchComponent name="spellcheck" class="solr.SpellCheckComponent">   <str name="queryAnalyzerFieldType">text_es</str>      <lst name="spellchecker">     <str name="name">default</str>     <str name="field">ss_title</str>     <str name="classname">solr.DirectSolrSpellChecker</str>     <str name="distanceMeasure">internal</str>     <float name="accuracy">0.5</float>     <int name="maxEdits">2</int>     <int name="minPrefix">1</int>     <int name="maxInspections">5</int>     <int name="minQueryLength">3</int>     <float name="maxQueryFrequency">0.01</float>     <float name="thresholdTokenFrequency">.01</float>   </lst> </searchComponent>  <requestHandler name="/spell" class="solr.SearchHandler" startup="lazy">   <lst name="defaults">     <str name="spellcheck">true</str>     <str name="spellcheck.dictionary">default</str>     <str name="spellcheck.onlyMorePopular">true</str>     <str name="spellcheck.extendedResults">true</str>     <str name="spellcheck.collate">true</str>     <str name="spellcheck.collateExtendedResults">true</str>     <str name="spellcheck.maxCollationTries">10</str>     <str name="spellcheck.maxCollations">5</str>   </lst>   <arr name="last-components">     <str>spellcheck</str>   </arr> </requestHandler>
7.3 Flujo "¿Quisiste decir?"
// Si la búsqueda tiene 0 resultados o pocos resultados, // y el spellchecker tiene una sugerencia:  public function searchWithSpellcheck(string $query): SearchResult {   $result = $this->search($query);      // Si hay pocos resultados, intentar corrección   if ($result->totalCount < 5) {     $correction = $this->spellcheckService->getSuggestion($query);          if ($correction && $correction !== $query) {       $correctedResult = $this->search($correction);              // Si la corrección da más resultados, sugerirla       if ($correctedResult->totalCount > $result->totalCount) {         $result->spellcheckSuggestion = $correction;         $result->correctedResultsCount = $correctedResult->totalCount;       }     }   }      return $result; }  // En la UI: // "No encontramos resultados para 'camizeta'" // "¿Quisiste decir 'camiseta'? (234 resultados)"
 
8. Búsqueda Geolocalizada
8.1 GeoSearchService
<?php namespace Drupal\jaraba_search\Service;  class GeoSearchService {    // Búsqueda por proximidad   public function searchNearby(     string $keywords,     float $lat,     float $lng,     float $radiusKm = 10   ): SearchResult;      // Filtro geográfico   public function addGeoFilter(     SearchQuery $query,     float $lat,     float $lng,     float $radiusKm   ): SearchQuery;      // Ordenar por distancia   public function sortByDistance(     SearchQuery $query,     float $lat,     float $lng   ): SearchQuery;      // Faceta por distancia   public function getDistanceFacet(     SearchResult $result,     float $lat,     float $lng   ): array;      // Geocoding   public function geocodeAddress(string $address): ?array;   public function reverseGeocode(float $lat, float $lng): ?string; }
8.2 Query Geoespacial en Solr
// Búsqueda con filtro geográfico // Encontrar productos de tiendas a menos de 5km  $query = "camiseta"; $lat = 37.8882; $lng = -4.7794; $radius = 5; // km  // Solr query con geofilt $solrQuery = [   'q' => $query,   'fq' => "{!geofilt sfield=locs_location pt={$lat},{$lng} d={$radius}}",   'sort' => "geodist() asc",  // Ordenar por distancia   'fl' => "*, _dist_:geodist()", // Incluir distancia en resultados ];  // Faceta por rangos de distancia $solrQuery['facet'] = 'true'; $solrQuery['facet.query'] = [   "{!frange l=0 u=1}geodist()",   // 0-1 km   "{!frange l=1 u=3}geodist()",   // 1-3 km   "{!frange l=3 u=5}geodist()",   // 3-5 km   "{!frange l=5 u=10}geodist()",  // 5-10 km ];
8.3 Casos de Uso Geo
Caso de Uso	Query	Resultado
Cerca de mí	camiseta cerca de mi	Productos de tiendas cercanas, ordenados por distancia
En mi ciudad	zapatos en Córdoba	Productos de tiendas en Córdoba
Click & Collect	Filtro: Solo recogida	Tiendas con C&C habilitado dentro del radio
Tiendas cercanas	Ver tiendas cerca	Mapa con comercios del marketplace
Entrega local	Entrega hoy	Comercios que ofrecen same-day en la zona
 
9. Analytics de Búsqueda
9.1 SearchAnalyticsService
<?php namespace Drupal\jaraba_search\Service;  class SearchAnalyticsService {    // Tracking de eventos   public function trackSearch(SearchEvent $event): void;   public function trackClick(ClickEvent $event): void;   public function trackAddToCart(CartEvent $event): void;   public function trackPurchase(PurchaseEvent $event): void;      // Métricas   public function getSearchVolume(\DateTime $from, \DateTime $to): array;   public function getPopularSearches(int $limit = 100): array;   public function getZeroResultSearches(int $limit = 100): array;   public function getSearchToClickRate(): float;   public function getSearchToPurchaseRate(): float;      // Análisis   public function getSearchFunnel(): array;   public function getQueryPerformance(string $query): QueryStats;   public function getUnderutilizedFilters(): array; }
9.2 Entidad: search_event
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
session_id	VARCHAR(64)	ID de sesión	NOT NULL, INDEX
user_id	INT	Usuario (si logueado)	FK, NULLABLE
query	VARCHAR(255)	Consulta original	NOT NULL, INDEX
query_normalized	VARCHAR(255)	Consulta normalizada	NOT NULL
filters_applied	JSON	Filtros aplicados	NULLABLE
sort_used	VARCHAR(32)	Ordenación usada	DEFAULT 'relevance'
results_count	INT	Número de resultados	NOT NULL
results_page	INT	Página vista	DEFAULT 1
response_time_ms	INT	Tiempo de respuesta	NOT NULL
clicked_position	INT	Posición del clic	NULLABLE
clicked_product_id	INT	Producto clickeado	NULLABLE
added_to_cart	BOOLEAN	¿Añadió al carrito?	DEFAULT FALSE
purchased	BOOLEAN	¿Compró?	DEFAULT FALSE
spellcheck_used	BOOLEAN	¿Usó corrección?	DEFAULT FALSE
autocomplete_used	BOOLEAN	¿Usó autocompletado?	DEFAULT FALSE
geo_lat	DECIMAL(10,8)	Latitud del usuario	NULLABLE
geo_lng	DECIMAL(11,8)	Longitud del usuario	NULLABLE
device_type	VARCHAR(16)	Tipo de dispositivo	mobile|tablet|desktop
created	DATETIME	Fecha del evento	NOT NULL, INDEX
 
9.3 Dashboard de Búsqueda
Métrica	Descripción	Acción si bajo
Search Volume	Búsquedas totales/día	Promocionar barra de búsqueda
Zero Results Rate	% búsquedas sin resultados	Añadir sinónimos, mejorar catálogo
Click-Through Rate	% búsquedas con clic	Mejorar relevancia, snippets
Add-to-Cart Rate	% búsquedas que añaden	Revisar precios, fotos
Conversion Rate	% búsquedas que compran	Optimizar checkout
Avg. Position Clicked	Posición media del clic	Mejorar ranking si > 5
Autocomplete Usage	% que usa autocomplete	Mejorar sugerencias
Filter Usage	Filtros más usados	Destacar filtros populares
Refinement Rate	% que refina búsqueda	Puede indicar mala relevancia
9.4 Zero Results Analysis
// Análisis de búsquedas sin resultados // Cron job diario para identificar oportunidades  public function analyzeZeroResults(): array {   $zeroResults = $this->getZeroResultSearches(last: '7 days');      $opportunities = [];      foreach ($zeroResults as $search) {     // Agrupar por query normalizado     $normalized = $this->normalizeQuery($search['query']);          if (!isset($opportunities[$normalized])) {       $opportunities[$normalized] = [         'query' => $search['query'],         'count' => 0,         'suggestions' => []       ];     }          $opportunities[$normalized]['count']++;          // Buscar sinónimos potenciales     $similar = $this->findSimilarQueries($search['query']);     if ($similar) {       $opportunities[$normalized]['suggestions'][] = $similar;     }   }      // Ordenar por frecuencia   usort($opportunities, fn($a, $b) => $b['count'] - $a['count']);      return $opportunities; }  // Resultado: Lista de términos a añadir como sinónimos // o productos a añadir al catálogo
 
10. APIs REST
10.1 Endpoint de Búsqueda Principal
// GET /api/v1/search/products // Query params: //   q=camiseta blanca //   category=moda-mujer //   brand=nike,adidas //   price_min=10&price_max=50 //   color=blanco,negro //   size=m,l //   in_stock=1 //   on_sale=1 //   sort=price_asc //   page=1 //   per_page=24 //   lat=37.88&lng=-4.77&radius=10 (geo)  // Response: {   "query": "camiseta blanca",   "total": 234,   "page": 1,   "per_page": 24,   "pages": 10,   "response_time_ms": 45,   "products": [     {       "id": 123,       "title": "Camiseta Básica Blanca",       "url": "/p/camiseta-basica-blanca",       "image": "https://...",       "price": 19.95,       "original_price": 29.95,       "brand": "Basics",       "rating": 4.5,       "reviews_count": 42,       "in_stock": true,       "merchant": {         "id": 789,         "name": "Moda Local"       }     }   ],   "facets": {     "categories": [...],     "brands": [...],     "colors": [...],     "sizes": [...],     "price_ranges": [...]   },   "spellcheck": null }
10.2 Endpoints de Búsqueda
Método	Endpoint	Descripción	Auth
GET	/api/v1/search/products	Búsqueda de productos	Público
GET	/api/v1/search/autocomplete	Autocompletado	Público
GET	/api/v1/search/suggestions	Sugerencias de búsqueda	Público
GET	/api/v1/search/popular	Búsquedas populares	Público
GET	/api/v1/search/recent	Búsquedas recientes del usuario	User
DELETE	/api/v1/search/recent	Borrar historial	User
POST	/api/v1/search/track	Track evento de búsqueda	Session
10.3 Endpoints de Facetas
Método	Endpoint	Descripción	Auth
GET	/api/v1/search/facets	Obtener facetas para query	Público
GET	/api/v1/search/filters/available	Filtros disponibles	Público
GET	/api/v1/search/price-range	Rango de precios	Público
 
11. Componentes Frontend
11.1 Arquitectura de Componentes
src/ ├── components/ │   ├── search/ │   │   ├── SearchBar.jsx           // Barra de búsqueda principal │   │   ├── SearchAutocomplete.jsx  // Dropdown de autocompletado │   │   ├── SearchResults.jsx       // Grid de resultados │   │   ├── SearchFilters.jsx       // Panel de filtros lateral │   │   ├── SearchSorting.jsx       // Selector de ordenación │   │   ├── SearchPagination.jsx    // Paginación │   │   └── ZeroResults.jsx         // Estado sin resultados │   │ │   ├── facets/ │   │   ├── FacetPanel.jsx          // Contenedor de facetas │   │   ├── CategoryFacet.jsx       // Faceta jerárquica │   │   ├── CheckboxFacet.jsx       // Faceta tipo checkbox │   │   ├── ColorFacet.jsx          // Swatches de color │   │   ├── SizeFacet.jsx           // Selector de tallas │   │   ├── PriceRangeFacet.jsx     // Slider de precio │   │   └── RatingFacet.jsx         // Filtro por estrellas │   │ │   └── products/ │       ├── ProductCard.jsx         // Card de producto │       ├── ProductGrid.jsx         // Grid responsive │       └── QuickView.jsx           // Modal de vista rápida
11.2 SearchBar Component
// SearchBar.jsx export function SearchBar({ initialQuery, onSearch }) {   const [query, setQuery] = useState(initialQuery || '');   const [isListening, setIsListening] = useState(false);      // Voice search (Web Speech API)   const startVoiceSearch = () => {     if ('webkitSpeechRecognition' in window) {       const recognition = new webkitSpeechRecognition();       recognition.lang = 'es-ES';       recognition.onresult = (event) => {         const transcript = event.results[0][0].transcript;         setQuery(transcript);         onSearch(transcript);       };       recognition.start();       setIsListening(true);     }   };      return (     <div className="search-bar">       <SearchIcon />       <input         type="text"         value={query}         onChange={(e) => setQuery(e.target.value)}         onKeyDown={(e) => e.key === 'Enter' && onSearch(query)}         placeholder="Buscar productos, marcas, categorías..."       />       <button onClick={startVoiceSearch} className="voice-btn">         <MicIcon className={isListening ? 'listening' : ''} />       </button>       <button onClick={() => onSearch(query)} className="search-btn">         Buscar       </button>     </div>   ); }
 
12. Flujos de Automatización (ECA)
12.1 ECA-SRCH-001: Producto Creado/Actualizado
Trigger: Producto publicado o actualizado
1. Serializar producto a formato Solr
2. Enviar a cola de indexación
3. Indexar en Solr (async)
4. Actualizar suggester si es nuevo
12.2 ECA-SRCH-002: Producto Despublicado
Trigger: Producto despublicado o eliminado
1. Eliminar del índice Solr
2. Reconstruir suggester (scheduled)
12.3 ECA-SRCH-003: Búsqueda Sin Resultados
Trigger: Búsqueda con results_count = 0
1. Registrar en search_event como zero_result
2. Si query frecuente (>10/día) → alertar a merchandising
3. Sugerir sinónimos automáticamente si hay match parcial
12.4 ECA-SRCH-004: Rebuild Nocturno
Trigger: Cron diario a las 03:00
1. Reconstruir índice de sugerencias
2. Reconstruir diccionario de spellcheck
3. Optimizar índice Solr
4. Generar reporte de zero-results
 
13. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Configuración Solr 9. Schema de producto. Search API + Solr backend. Indexación básica.	66_Product_Catalog
Sprint 2	Semana 3-4	SearchService completo. FacetManager. Filtros básicos. APIs de búsqueda.	Sprint 1
Sprint 3	Semana 5-6	AutocompleteService. Suggester Solr. Componentes React autocomplete.	Sprint 2
Sprint 4	Semana 7-8	SpellcheckService. Sinónimos español. "¿Quisiste decir?"	Sprint 3
Sprint 5	Semana 9-10	GeoSearchService. Búsqueda por proximidad. Mapa de tiendas.	Sprint 4
Sprint 6	Semana 11-12	SearchAnalyticsService. Dashboard de métricas. Flujos ECA. QA y go-live.	Sprint 5
13.1 Criterios de Aceptación Sprint 2
✓ Búsqueda full-text funcional con relevancia
✓ Facetas de categoría, marca, precio, color, talla
✓ Ordenación por relevancia, precio, nuevos
✓ Tiempo de respuesta < 200ms p95
✓ Paginación funcionando
13.2 Dependencias Externas
• Apache Solr 9.x
• Drupal Search API 8.x-1.x
• Search API Solr 4.x
• Facets module 2.x
• Search API Autocomplete 1.x
• Google Maps JavaScript API (para geo)
--- Fin del Documento ---
70_ComercioConecta_Search_Discovery_v1.docx | Jaraba Impact Platform | Enero 2026
