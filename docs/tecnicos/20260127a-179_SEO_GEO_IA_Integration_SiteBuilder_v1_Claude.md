179
ESPECIFICACIÓN TÉCNICA
SEO/GEO + IA Integration
Schema.org | Local SEO | Hreflang | Core Web Vitals | IA Nativa
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	27 de Enero de 2026
Horas Estimadas:	50-60 horas
 
Índice de Contenidos
1. Resumen Ejecutivo
2. Schema.org Global
3. Blog SEO Avanzado
4. IA Nativa para Site Builder
5. Multi-idioma SEO (Hreflang)
6. Core Web Vitals
7. APIs REST
8. Roadmap de Implementación
 
1. Resumen Ejecutivo
Este documento especifica la integración de SEO/GEO avanzado (Doc 164), internacionalización (Doc 166), y AI Content Hub (Docs 128, 171) con el Site Builder (Docs 176-178).

Objetivo
Proporcionar capacidades SEO enterprise y asistencia IA nativa para que cualquier tenant pueda crear sitios optimizados sin conocimientos técnicos.

1.1. Matriz de Integración
Sistema	Doc	Integración Site Builder
Schema.org	164	WebSite, Organization, BreadcrumbList, BlogPosting
Local SEO	164	LocalBusiness, GeoCoordinates, OpeningHours
Core Web Vitals	164	Critical CSS, Preload hints, Lazy loading
Multi-idioma	166	Hreflang, Sitemap multi-idioma, og:locale
AI Content Hub	128	Generación posts, optimización SEO, internal linking
1.2. Capacidades IA Nativas
•	Sugerir estructura de sitio basada en vertical
•	Generar posts de blog con SEO optimizado
•	Optimizar meta tags y contenido existente
•	Sugerir internal linking automático
•	Auditoría SEO completa del sitio
 
2. Schema.org Global
 SchemaOrgGlobalService.php
<?php
namespace Drupal\jaraba_site_builder\Service;
 
class SchemaOrgGlobalService {
  public function generateWebSiteSchema(int $tenantId): array {
    $siteConfig = $this->getSiteConfig($tenantId);
    $baseUrl = $this->getBaseUrl($tenantId);
    
    return [
      '@context' => 'https://schema.org',
      '@type' => 'WebSite',
      '@id' => $baseUrl . '/#website',
      'url' => $baseUrl,
      'name' => $siteConfig['site_name'],
      'description' => $siteConfig['site_tagline'],
      'inLanguage' => $this->getDefaultLanguage($tenantId),
      'publisher' => ['@id' => $baseUrl . '/#organization'],
      'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $baseUrl . '/blog?search={q}'],
        'query-input' => 'required name=q',
      ],
    ];
  }
 
  public function generateOrganizationSchema(int $tenantId): array {
    $siteConfig = $this->getSiteConfig($tenantId);
    $tenantConfig = $this->getTenantConfig($tenantId);
    $baseUrl = $this->getBaseUrl($tenantId);
    $type = $tenantConfig['business_type'] ?? 'Organization';
    
    $schema = [
      '@context' => 'https://schema.org',
      '@type' => $type,
      '@id' => $baseUrl . '/#organization',
      'name' => $siteConfig['site_name'],
      'url' => $baseUrl,
      'logo' => ['@type' => 'ImageObject', 'url' => $this->getFileUrl($siteConfig['site_logo'])],
      'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => $siteConfig['contact_phone'],
        'email' => $siteConfig['contact_email'],
        'contactType' => 'customer service',
      ],
      'sameAs' => array_column(json_decode($siteConfig['social_links'], TRUE), 'url'),
    ];
    
    // LocalBusiness additions
    if (in_array($type, ['LocalBusiness', 'Store', 'Restaurant', 'ProfessionalService'])) {
      $schema['address'] = [
        '@type' => 'PostalAddress',
        'streetAddress' => $siteConfig['contact_address'],
        'addressLocality' => $tenantConfig['city'],
        'addressRegion' => $tenantConfig['region'],
        'postalCode' => $tenantConfig['postal_code'],
        'addressCountry' => 'ES',
      ];
      $coords = json_decode($siteConfig['contact_coordinates'], TRUE);
      if ($coords) {
        $schema['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $coords['lat'], 'longitude' => $coords['lng']];
      }
      $schema['openingHoursSpecification'] = $tenantConfig['opening_hours'] ?? [];
      $schema['priceRange'] = $tenantConfig['price_range'] ?? '€€';
    }
    return $schema;
  }
 
  public function generateBreadcrumbSchema(int $tenantId, int $pageTreeId): array {
    $baseUrl = $this->getBaseUrl($tenantId);
    $ancestors = $this->getPageAncestors($pageTreeId);
    $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => $baseUrl]];
    $pos = 2;
    foreach ($ancestors as $a) {
      $page = $this->pageStorage->load($a->page_id);
      $items[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $page->get('title')->value, 'item' => $baseUrl . $page->get('path_alias')->value];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
  }
}

 
2.1. WebSite con SearchAction
 schema-website.json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "@id": "https://ejemplo.com/#website",
  "url": "https://ejemplo.com",
  "name": "Mi Empresa",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {"@type": "EntryPoint", "urlTemplate": "https://ejemplo.com/blog?search={q}"},
    "query-input": "required name=q"
  }
}

2.2. LocalBusiness
 schema-localbusiness.json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Mi Empresa",
  "address": {"@type": "PostalAddress", "streetAddress": "Calle 123", "addressLocality": "Córdoba"},
  "geo": {"@type": "GeoCoordinates", "latitude": 37.88, "longitude": -4.77},
  "openingHoursSpecification": [{"dayOfWeek": ["Monday","Friday"], "opens": "09:00", "closes": "18:00"}],
  "priceRange": "€€"
}

 
3. Blog SEO Avanzado
 BlogSeoService.php
<?php
namespace Drupal\jaraba_blog\Service;
 
class BlogSeoService {
  public function generatePostSchema(object $post, int $tenantId): array {
    $baseUrl = $this->getBaseUrl($tenantId);
    $blogConfig = $this->getBlogConfig($tenantId);
    $postUrl = $baseUrl . $blogConfig['blog_base_path'] . '/' . $post->slug;
    $author = $this->getAuthor($post->author_id);
    $authorUrl = $baseUrl . $blogConfig['author_base_path'] . '/' . $author->slug;
    
    return [
      '@context' => 'https://schema.org',
      '@type' => $blogConfig['schema_type'] ?? 'BlogPosting',
      '@id' => $postUrl . '#article',
      'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postUrl],
      'headline' => $post->meta_title ?: $post->title,
      'description' => $post->meta_description ?: $post->excerpt,
      'datePublished' => date('c', strtotime($post->published_at)),
      'dateModified' => date('c', strtotime($post->changed)),
      'image' => ['@type' => 'ImageObject', 'url' => $this->getFileUrl($post->featured_image_id)],
      'author' => [
        '@type' => 'Person', '@id' => $authorUrl . '#person',
        'name' => $author->display_name, 'url' => $authorUrl,
        'image' => $this->getFileUrl($author->avatar_id),
        'sameAs' => array_filter([$author->website_url, $author->twitter_handle ? 'https://twitter.com/'.$author->twitter_handle : null, $author->linkedin_url]),
      ],
      'publisher' => ['@type' => 'Organization', '@id' => $baseUrl . '/#organization'],
      'articleSection' => $this->getPostCategories($post->id)[0]->name ?? 'Blog',
      'wordCount' => str_word_count(strip_tags($post->content)),
      'timeRequired' => 'PT' . $post->reading_time_minutes . 'M',
      'keywords' => implode(', ', array_column($this->getPostTags($post->id), 'name')),
    ];
  }
}

3.1. BlogPosting Schema Completo
 schema-blogposting.json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "Título SEO del Artículo",
  "author": {"@type": "Person", "name": "Pepe Jaraba", "sameAs": ["https://twitter.com/pepejaraba"]},
  "publisher": {"@type": "Organization", "@id": "https://ejemplo.com/#organization"},
  "datePublished": "2026-01-27T10:00:00+01:00",
  "wordCount": 1500,
  "timeRequired": "PT8M",
  "articleSection": "Transformación Digital"
}

 
4. IA Nativa para Site Builder
 AISiteBuilderService.php
<?php
namespace Drupal\jaraba_site_builder\Service;
 
class AISiteBuilderService {
  protected $claudeApiKey;
 
  public function suggestSiteStructure(int $tenantId, array $params): array {
    $tenantConfig = $this->getTenantConfig($tenantId);
    $vertical = $tenantConfig['vertical'] ?? 'general';
    
    $prompt = "Eres experto en arquitectura de información para sitios web.
VERTICAL: {$vertical}
INDUSTRIA: {$params['industry']}
OBJETIVOS: " . implode(', ', $params['goals'] ?? []) . "
FUNCIONALIDADES: " . implode(', ', $params['features'] ?? []) . "
 
Genera estructura óptima en JSON:
{
  "site_tree": [{"title": "...", "slug": "/", "template": "landing", "children": []}],
  "main_menu": [{"title": "...", "url": "/", "children": []}],
  "essential_pages": [{"page": "...", "purpose": "..."}],
  "seo_recommendations": ["..."]
}";
 
    return $this->parseJSON($this->callClaude($prompt));
  }
 
  public function generateBlogPost(int $tenantId, array $params): array {
    $prompt = "Genera artículo de blog completo sobre: "{$params['topic']}"
KEYWORDS: " . implode(', ', $params['keywords'] ?? []) . "
TONO: {$params['tone']}
LONGITUD: {$params['length']} palabras
 
Responde con JSON:
{
  "title": "Título SEO (max 60 chars)",
  "meta_title": "Meta title (60 chars)",
  "meta_description": "Meta description (155 chars)",
  "excerpt": "Resumen (150 chars)",
  "content": "Contenido Markdown completo...",
  "focus_keyword": "keyword principal",
  "faq": [{"question": "...", "answer": "..."}],
  "internal_link_suggestions": [{"anchor": "...", "target_topic": "..."}]
}";
 
    $result = $this->parseJSON($this->callClaude($prompt, 4000));
    $result['suggested_categories'] = $this->suggestCategories($tenantId, $result['content']);
    $result['suggested_tags'] = $this->extractKeywords($result['content']);
    $result['image_suggestions'] = $this->suggestImages($params['topic']);
    return $result;
  }
 
  public function optimizePageSEO(int $tenantId, int $pageId): array {
    $page = $this->pageStorage->load($pageId);
    $content = $page->get('content_data')->value;
    
    $prompt = "Analiza y optimiza SEO de esta página:
TÍTULO: {$page->get('title')->value}
META ACTUAL: {$page->get('meta_description')->value}
CONTENIDO: " . substr(strip_tags($content), 0, 2000) . "
 
Responde con JSON:
{
  "seo_score": 0-100,
  "issues": [{"type": "...", "severity": "high|medium|low", "message": "...", "fix": "..."}],
  "optimized_meta": {"title": "...", "description": "..."},
  "keyword_suggestions": ["..."],
  "content_suggestions": ["..."]
}";
 
    return $this->parseJSON($this->callClaude($prompt));
  }
 
  public function suggestInternalLinks(int $tenantId, int $postId): array {
    $post = $this->blogPostStorage->load($postId);
    $vector = $this->getEmbedding($post->content);
    $similar = $this->qdrantService->search('blog_posts', $vector, $tenantId, 10);
    
    $suggestions = [];
    foreach ($similar as $s) {
      $anchors = $this->findPotentialAnchors($post->content, $s['title']);
      foreach ($anchors as $anchor) {
        $suggestions[] = ['anchor_text' => $anchor, 'target_id' => $s['id'], 'target_title' => $s['title'], 'target_url' => $s['url'], 'score' => $s['score']];
      }
    }
    return array_slice($suggestions, 0, 5);
  }
 
  protected function callClaude(string $prompt, int $maxTokens = 2000): string {
    $response = $this->httpClient->post('https://api.anthropic.com/v1/messages', [
      'headers' => ['x-api-key' => $this->claudeApiKey, 'anthropic-version' => '2024-01-01', 'Content-Type' => 'application/json'],
      'json' => ['model' => 'claude-sonnet-4-20250514', 'max_tokens' => $maxTokens, 'messages' => [['role' => 'user', 'content' => $prompt]]],
    ]);
    return json_decode($response->getBody(), TRUE)['content'][0]['text'];
  }
}

 
4.1. Flujos de IA Disponibles
Flujo	Input	Output
Sugerir estructura	vertical, industria, objetivos	site_tree, menu, páginas esenciales
Generar post	tema, keywords, tono, longitud	post completo con meta, FAQ, tags
Optimizar SEO	page_id	score, issues, meta optimizada
Internal linking	post_id	sugerencias de enlaces con anchors
Generar títulos	topic, keyword	5 variantes optimizadas
4.2. Ejemplo: Generar Post
 ai-generate-post.json
POST /api/v1/ai/blog/generate-post
{
  "topic": "Transformación digital para PYMES rurales",
  "keywords": ["transformación digital", "PYMES", "rural"],
  "tone": "profesional",
  "length": "medium"
}
 
Response:
{
  "title": "Guía de Transformación Digital para PYMES Rurales en 2026",
  "meta_description": "Descubre cómo digitalizar tu negocio rural...",
  "content": "## Introducción\n\nLa transformación digital...",
  "faq": [{"question": "¿Cuánto cuesta?", "answer": "..."}],
  "suggested_tags": ["digitalización", "rural", "PYMES"],
  "image_suggestions": ["negocio rural tecnología", "tablet campo"]
}

 
5. Multi-idioma SEO (Hreflang)
 MultiLanguageSeoService.php
<?php
namespace Drupal\jaraba_site_builder\Service;
 
class MultiLanguageSeoService {
  public function generateHreflangTags(int $tenantId, int $pageId): array {
    $page = $this->pageStorage->load($pageId);
    $baseUrl = $this->getBaseUrl($tenantId);
    $langs = $this->getEnabledLanguages($tenantId);
    $translations = json_decode($page->get('content_data')->value, TRUE)['translations'] ?? [];
    $defaultLang = $this->getDefaultLanguage($tenantId);
    $path = $page->get('path_alias')->value;
    
    if (count($langs) <= 1) return [];
    
    $tags = [];
    foreach ($langs as $code => $config) {
      if (isset($translations[$code]) || $code === $defaultLang) {
        $tags[] = ['rel' => 'alternate', 'hreflang' => $code, 'href' => $baseUrl . '/' . $code . $path];
      }
    }
    $tags[] = ['rel' => 'alternate', 'hreflang' => 'x-default', 'href' => $baseUrl . '/' . $defaultLang . $path];
    return $tags;
  }
 
  public function generateMultiLanguageSitemap(int $tenantId): string {
    $baseUrl = $this->getBaseUrl($tenantId);
    $langs = $this->getEnabledLanguages($tenantId);
    $defaultLang = $this->getDefaultLanguage($tenantId);
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
    
    $pages = $this->database->select('site_page_tree', 'spt')->fields('spt')->condition('tenant_id', $tenantId)->condition('status', 'published')->condition('show_in_sitemap', TRUE)->execute()->fetchAll();
    
    foreach ($pages as $pt) {
      $page = $this->pageStorage->load($pt->page_id);
      $translations = json_decode($page->get('content_data')->value, TRUE)['translations'] ?? [];
      $path = $page->get('path_alias')->value;
      $availableLangs = array_merge([$defaultLang], array_keys(array_intersect_key($translations, $langs)));
      
      foreach (array_unique($availableLangs) as $lang) {
        $url = $baseUrl . '/' . $lang . $path;
        $xml .= '<url><loc>' . htmlspecialchars($url) . '</loc><lastmod>' . date('Y-m-d', strtotime($page->get('changed')->value)) . '</lastmod>';
        foreach ($availableLangs as $alt) {
          $xml .= '<xhtml:link rel="alternate" hreflang="' . $alt . '" href="' . htmlspecialchars($baseUrl . '/' . $alt . $path) . '"/>';
        }
        $xml .= '<xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($baseUrl . '/' . $defaultLang . $path) . '"/></url>';
      }
    }
    return $xml . '</urlset>';
  }
}

5.1. Tags Hreflang
 hreflang.html
<link rel="alternate" hreflang="es" href="https://ejemplo.com/es/servicios" />
<link rel="alternate" hreflang="ca" href="https://ejemplo.com/ca/serveis" />
<link rel="alternate" hreflang="en" href="https://ejemplo.com/en/services" />
<link rel="alternate" hreflang="x-default" href="https://ejemplo.com/es/servicios" />

5.2. Sitemap Multi-idioma
 sitemap-hreflang.xml
<url>
  <loc>https://ejemplo.com/es/servicios</loc>
  <xhtml:link rel="alternate" hreflang="es" href="https://ejemplo.com/es/servicios"/>
  <xhtml:link rel="alternate" hreflang="ca" href="https://ejemplo.com/ca/serveis"/>
  <xhtml:link rel="alternate" hreflang="x-default" href="https://ejemplo.com/es/servicios"/>
</url>

 
6. Core Web Vitals
 CoreWebVitalsService.php
<?php
namespace Drupal\jaraba_site_builder\Service;
 
class CoreWebVitalsService {
  public function generateCriticalCSS(int $pageId): string {
    $page = $this->pageStorage->load($pageId);
    $blocks = json_decode($page->get('content_data')->value, TRUE)['blocks'] ?? [];
    $criticalBlocks = array_slice($blocks, 0, 3); // Above fold
    
    $css = $this->getBaseCriticalCSS();
    foreach ($criticalBlocks as $b) {
      $css .= $this->getBlockCriticalCSS($b['type']);
    }
    return $this->minifyCSS($css);
  }
 
  public function generatePreloadHints(int $tenantId, int $pageId): array {
    $hints = [];
    
    // Fonts
    foreach ($this->getUsedFonts($tenantId) as $font) {
      $hints[] = ['rel' => 'preload', 'href' => $font['url'], 'as' => 'font', 'type' => $font['type'], 'crossorigin' => 'anonymous'];
    }
    
    // LCP image
    $lcpImage = $this->detectLCPImage($pageId);
    if ($lcpImage) {
      $hints[] = ['rel' => 'preload', 'href' => $lcpImage, 'as' => 'image', 'fetchpriority' => 'high'];
    }
    
    // Preconnects
    $hints[] = ['rel' => 'preconnect', 'href' => 'https://fonts.googleapis.com'];
    $hints[] = ['rel' => 'preconnect', 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];
    
    return $hints;
  }
 
  public function optimizeImagesForLazyLoading(string $html): string {
    return preg_replace_callback('/<img([^>]+)>/i', function($m) use (&$i) {
      $i = ($i ?? 0) + 1;
      $attr = $i <= 2 ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"';
      return '<img' . $m[1] . $attr . '>';
    }, $html);
  }
 
  public function auditCoreWebVitals(int $pageId): array {
    $html = $this->renderPage($pageId);
    $issues = [];
    $score = 100;
    
    // Images without dimensions (CLS)
    preg_match_all('/<img[^>]*>/i', $html, $imgs);
    foreach ($imgs[0] as $img) {
      if (!preg_match('/width=/i', $img) || !preg_match('/height=/i', $img)) {
        $issues[] = ['type' => 'cls', 'severity' => 'high', 'message' => 'Imagen sin width/height'];
        $score -= 5;
      }
    }
    
    // Blocking scripts (FID)
    preg_match_all('/<script(?![^>]*(?:async|defer))[^>]*src=/i', $html, $scripts);
    if (count($scripts[0]) > 2) {
      $issues[] = ['type' => 'fid', 'severity' => 'high', 'message' => count($scripts[0]) . ' scripts bloqueantes'];
      $score -= 15;
    }
    
    return ['score' => max(0, $score), 'issues' => $issues];
  }
}

6.1. Optimizaciones Automáticas
Métrica	Optimización	Implementación
LCP	Preload imagen hero	generatePreloadHints()
LCP	Critical CSS inline	generateCriticalCSS()
CLS	Dimensiones en imágenes	width/height automático
CLS	Aspect-ratio CSS	Previene layout shift
FID	Defer scripts	async/defer automático
 
7. APIs REST
 api-endpoints.txt
// APIs REST - SEO + IA para Site Builder
 
// === SCHEMA.ORG ===
GET /api/v1/site/schema                    // Schema completo del sitio
GET /api/v1/site/schema/page/{id}          // Schema de página específica
GET /api/v1/site/schema/breadcrumbs/{id}   // BreadcrumbList
 
// === HREFLANG ===
GET /api/v1/site/hreflang/{page_id}        // Tags hreflang para página
GET /api/v1/site/sitemap-multilingual.xml  // Sitemap multi-idioma
 
// === CORE WEB VITALS ===
GET /api/v1/site/performance/{page_id}     // Critical CSS + preload hints
POST /api/v1/site/performance/audit        // Auditoría CWV
 
// === IA: ESTRUCTURA ===
POST /api/v1/ai/site/suggest-structure
Body: {"industry": "...", "goals": [...], "features": [...]}
 
POST /api/v1/ai/site/analyze-architecture
// Analiza arquitectura de información existente
 
// === IA: BLOG ===
POST /api/v1/ai/blog/generate-post
Body: {"topic": "...", "keywords": [...], "tone": "...", "length": "medium"}
 
POST /api/v1/ai/blog/optimize-seo/{post_id}
// Optimiza SEO de post existente
 
POST /api/v1/ai/blog/suggest-internal-links/{post_id}
// Sugiere enlaces internos
 
POST /api/v1/ai/blog/generate-excerpt
Body: {"content": "...", "keyword": "..."}
 
POST /api/v1/ai/blog/suggest-taxonomy
Body: {"title": "...", "content": "..."}
 
// === SEO AUDIT ===
GET /api/v1/seo/audit/site                 // Auditoría completa del sitio
GET /api/v1/seo/audit/page/{id}            // Auditoría de página

 
8. Roadmap de Implementación
Sprint	Componente	Horas
1	SchemaOrgGlobalService	10-12h
1	BlogSeoService	10-12h
2	AISiteBuilderService - estructura	12-15h
2	AISiteBuilderService - blog IA	12-15h
3	MultiLanguageSeoService	8-10h
3	CoreWebVitalsService	8-10h
4	APIs REST + UI integration	10-12h

Total: 50-60 horas (€4,000-€4,800 @ €80/h)

9. Límites por Plan
Capacidad	Starter	Professional	Enterprise
Schema.org básico	✓	✓	✓
Schema.org LocalBusiness	—	✓	✓
IA sugerir estructura	1 vez	5/mes	Ilimitado
IA generar posts	—	10/mes	100/mes
IA optimizar SEO	—	20/mes	Ilimitado
Hreflang automático	—	✓	✓
Auditoría Core Web Vitals	—	✓	✓

Resultado Final
Con este documento, el Site Builder (176-178) queda integrado con SEO/GEO avanzado (164), i18n (166), y AI Content Hub (128, 171), proporcionando capacidades enterprise sin conocimientos técnicos.

Fin del documento.
