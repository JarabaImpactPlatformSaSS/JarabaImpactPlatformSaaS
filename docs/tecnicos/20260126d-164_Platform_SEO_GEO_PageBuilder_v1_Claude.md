164
ESPECIFICACIÓN TÉCNICA
SEO/GEO Avanzado para Page Builder
Schema.org por Vertical | Local SEO | Rich Snippets | Core Web Vitals
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	26 de Enero de 2026
Horas Estimadas:	80-100 horas
 
Índice de Contenidos
1. Schema.org Structured Data por Vertical
   1.1. JobPosting (Empleabilidad)
   1.2. Course (Formación)
   1.3. Product (AgroConecta/ComercioConecta)
   1.4. LocalBusiness (ComercioConecta)
   1.5. ProfessionalService (ServiciosConecta)
   1.6. BreadcrumbList y FAQPage
2. Campos SEO Avanzados por Bloque
3. Local SEO / GEO
4. Sitemap XML Dinámico
5. Core Web Vitals Optimization
6. APIs SEO
7. Roadmap de Implementación
 
1. Schema.org Structured Data por Vertical
Cada vertical del Ecosistema Jaraba requiere schemas específicos para Rich Snippets en Google. Los templates Twig generan JSON-LD automáticamente basado en el contenido de cada página.
Rich Snippets Habilitados
JobPosting → Google for Jobs | Course → Course Rich Results | Product → Product Snippets con precio y reviews | LocalBusiness → Knowledge Panel local | FAQPage → FAQ Accordion en SERP

1.1. JobPosting (Empleabilidad)
Schema completo para ofertas de empleo que aparecen en Google for Jobs. Incluye salario, ubicación, tipo de contrato y requisitos.
 schema-job-posting.json.twig
{
  "@context": "https://schema.org",
  "@type": "JobPosting",
  "title": "{{ job.title }}",
  "description": "{{ job.description|striptags }}",
  "identifier": {
    "@type": "PropertyValue",
    "name": "{{ tenant.name }}",
    "value": "{{ job.uuid }}"
  },
  "datePosted": "{{ job.created|date('Y-m-d') }}",
  "validThrough": "{{ job.expires|date('Y-m-d\\TH:i:sP') }}",
  "employmentType": "{{ job.employment_type|upper }}",
  "hiringOrganization": {
    "@type": "Organization",
    "name": "{{ job.company_name }}",
    "sameAs": "{{ job.company_url }}",
    "logo": "{{ job.company_logo|file_url }}"
  },
  "jobLocation": {
    "@type": "Place",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{ job.address }}",
      "addressLocality": "{{ job.city }}",
      "addressRegion": "{{ job.region }}",
      "postalCode": "{{ job.postal_code }}",
      "addressCountry": "ES"
    }
  },
  {% if job.remote_work %}
  "jobLocationType": "TELECOMMUTE",
  "applicantLocationRequirements": {
    "@type": "Country",
    "name": "Spain"
  },
  {% endif %}
  "baseSalary": {
    "@type": "MonetaryAmount",
    "currency": "EUR",
    "value": {
      "@type": "QuantitativeValue",
      "minValue": {{ job.salary_min }},
      "maxValue": {{ job.salary_max }},
      "unitText": "{{ job.salary_period|upper }}"
    }
  },
  "skills": "{{ job.skills|join(', ') }}",
  "qualifications": "{{ job.requirements }}",
  "responsibilities": "{{ job.responsibilities }}",
  "industry": "{{ job.industry }}",
  "directApply": true
}

1.2. Course (Formación)
Schema para cursos con información de instructor, duración, precio y credenciales otorgadas.
 schema-course.json.twig
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": "{{ course.title }}",
  "description": "{{ course.description|striptags }}",
  "provider": {
    "@type": "Organization",
    "name": "{{ tenant.name }}",
    "sameAs": "{{ tenant.url }}"
  },
  "offers": {
    "@type": "Offer",
    "price": "{{ course.price }}",
    "priceCurrency": "EUR",
    "availability": "https://schema.org/InStock",
    "validFrom": "{{ course.available_from|date('Y-m-d') }}"
  },
  "hasCourseInstance": {
    "@type": "CourseInstance",
    "courseMode": "{{ course.mode }}",
    "courseWorkload": "PT{{ course.duration_hours }}H",
    "startDate": "{{ course.start_date|date('Y-m-d') }}",
    "endDate": "{{ course.end_date|date('Y-m-d') }}",
    "instructor": {
      "@type": "Person",
      "name": "{{ course.instructor_name }}"
    }
  },
  "educationalLevel": "{{ course.level }}",
  "teaches": "{{ course.skills|join(', ') }}",
  "numberOfCredits": {{ course.credits|default(0) }},
  "occupationalCredentialAwarded": "{{ course.credential_type }}",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ course.rating }}",
    "reviewCount": "{{ course.review_count }}"
  }
}

1.3. Product (AgroConecta/ComercioConecta)
Schema completo de producto con precio, disponibilidad, reviews, envío y origen.
 schema-product.json.twig
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "{{ product.title }}",
  "description": "{{ product.description|striptags }}",
  "image": [
    {% for img in product.images %}
    "{{ img|file_url }}"{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "sku": "{{ product.sku }}",
  "mpn": "{{ product.mpn }}",
  "brand": {
    "@type": "Brand",
    "name": "{{ product.brand }}"
  },
  "offers": {
    "@type": "Offer",
    "url": "{{ url('entity.node.canonical', {'node': product.id}) }}",
    "priceCurrency": "EUR",
    "price": "{{ product.price }}",
    "priceValidUntil": "{{ 'now'|date_modify('+1 year')|date('Y-m-d') }}",
    "availability": "{{ product.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
    "itemCondition": "https://schema.org/NewCondition",
    "seller": {
      "@type": "Organization",
      "name": "{{ product.seller_name }}"
    },
    "shippingDetails": {
      "@type": "OfferShippingDetails",
      "shippingRate": {
        "@type": "MonetaryAmount",
        "value": "{{ product.shipping_cost }}",
        "currency": "EUR"
      },
      "deliveryTime": {
        "@type": "ShippingDeliveryTime",
        "handlingTime": {
          "@type": "QuantitativeValue",
          "minValue": 1,
          "maxValue": 2,
          "unitCode": "DAY"
        },
        "transitTime": {
          "@type": "QuantitativeValue",
          "minValue": 2,
          "maxValue": 5,
          "unitCode": "DAY"
        }
      }
    }
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ product.rating }}",
    "reviewCount": "{{ product.review_count }}",
    "bestRating": "5",
    "worstRating": "1"
  },
  {% if product.origin %}
  "countryOfOrigin": {
    "@type": "Country",
    "name": "{{ product.origin }}"
  },
  {% endif %}
  "category": "{{ product.category }}"
}

 
1.4. LocalBusiness (ComercioConecta)
Schema para negocios locales con horarios, coordenadas, áreas de servicio y reseñas.
 schema-local-business.json.twig
{
  "@context": "https://schema.org",
  "@type": "{{ business.type|default('LocalBusiness') }}",
  "name": "{{ business.name }}",
  "description": "{{ business.description|striptags }}",
  "url": "{{ business.website }}",
  "telephone": "{{ business.phone }}",
  "email": "{{ business.email }}",
  "image": "{{ business.logo|file_url }}",
  "logo": "{{ business.logo|file_url }}",
  "priceRange": "{{ business.price_range }}",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "{{ business.address }}",
    "addressLocality": "{{ business.city }}",
    "addressRegion": "{{ business.region }}",
    "postalCode": "{{ business.postal_code }}",
    "addressCountry": "ES"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": {{ business.latitude }},
    "longitude": {{ business.longitude }}
  },
  "openingHoursSpecification": [
    {% for hours in business.opening_hours %}
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": "{{ hours.day }}",
      "opens": "{{ hours.opens }}",
      "closes": "{{ hours.closes }}"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "areaServed": [
    {% for area in business.service_areas %}
    {
      "@type": "{{ area.type|default('City') }}",
      "name": "{{ area.name }}"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ business.rating }}",
    "reviewCount": "{{ business.review_count }}"
  },
  "sameAs": [
    {% for social in business.social_links %}
    "{{ social }}"{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "hasMap": "https://www.google.com/maps?q={{ business.latitude }},{{ business.longitude }}"
}

1.5. ProfessionalService (ServiciosConecta)
Schema para profesionales con servicios ofrecidos, área de cobertura, credenciales y valoraciones.
 schema-professional-service.json.twig
{
  "@context": "https://schema.org",
  "@type": "ProfessionalService",
  "name": "{{ provider.name }}",
  "description": "{{ provider.bio|striptags }}",
  "url": "{{ url('entity.user.canonical', {'user': provider.id}) }}",
  "image": "{{ provider.avatar|file_url }}",
  "telephone": "{{ provider.phone }}",
  "email": "{{ provider.email }}",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "{{ provider.city }}",
    "addressRegion": "{{ provider.region }}",
    "addressCountry": "ES"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": {{ provider.latitude }},
    "longitude": {{ provider.longitude }}
  },
  "areaServed": {
    "@type": "GeoCircle",
    "geoMidpoint": {
      "@type": "GeoCoordinates",
      "latitude": {{ provider.latitude }},
      "longitude": {{ provider.longitude }}
    },
    "geoRadius": "{{ provider.service_radius_km * 1000 }}"
  },
  "makesOffer": [
    {% for service in provider.services %}
    {
      "@type": "Offer",
      "itemOffered": {
        "@type": "Service",
        "name": "{{ service.name }}",
        "description": "{{ service.description }}"
      },
      "price": "{{ service.price }}",
      "priceCurrency": "EUR"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "knowsAbout": [
    {% for skill in provider.skills %}
    "{{ skill }}"{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "hasCredential": [
    {% for cert in provider.certifications %}
    {
      "@type": "EducationalOccupationalCredential",
      "name": "{{ cert.name }}",
      "credentialCategory": "{{ cert.type }}"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ provider.rating }}",
    "reviewCount": "{{ provider.review_count }}"
  }
}

1.6. BreadcrumbList y FAQPage
Schemas universales para navegación y preguntas frecuentes que aplican a todas las verticales.
 schema-breadcrumb.json.twig
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {% for item in breadcrumbs %}
    {
      "@type": "ListItem",
      "position": {{ loop.index }},
      "name": "{{ item.title }}",
      "item": "{{ item.url }}"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}

 schema-faq.json.twig
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {% for faq in block.items %}
    {
      "@type": "Question",
      "name": "{{ faq.question }}",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "{{ faq.answer|striptags }}"
      }
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}

 
2. Campos SEO Avanzados por Bloque
Extensión del JSON Schema de cada bloque para incluir campos SEO configurables desde el Form Builder.
 seo-fields-schema.json
{
  "seo_settings": {
    "type": "object",
    "title": "Configuración SEO",
    "properties": {
      "meta_title": {
        "type": "string",
        "title": "Título SEO",
        "maxLength": 60,
        "description": "Título para buscadores (máx. 60 caracteres)"
      },
      "meta_description": {
        "type": "string",
        "title": "Meta Descripción",
        "maxLength": 160,
        "description": "Descripción para buscadores (máx. 160 caracteres)"
      },
      "focus_keyword": {
        "type": "string",
        "title": "Keyword Principal",
        "description": "Palabra clave objetivo para esta página"
      },
      "secondary_keywords": {
        "type": "array",
        "title": "Keywords Secundarias",
        "items": { "type": "string" },
        "maxItems": 5
      },
      "canonical_url": {
        "type": "string",
        "format": "uri",
        "title": "URL Canónica",
        "description": "Dejar vacío para usar URL actual"
      },
      "robots": {
        "type": "object",
        "title": "Directivas Robots",
        "properties": {
          "index": { "type": "boolean", "default": true },
          "follow": { "type": "boolean", "default": true },
          "noarchive": { "type": "boolean", "default": false },
          "nosnippet": { "type": "boolean", "default": false }
        }
      },
      "og_title": {
        "type": "string",
        "title": "Título Open Graph",
        "maxLength": 95
      },
      "og_description": {
        "type": "string",
        "title": "Descripción Open Graph",
        "maxLength": 200
      },
      "og_image": {
        "type": "string",
        "format": "uri",
        "title": "Imagen Open Graph",
        "description": "Recomendado: 1200x630px"
      },
      "og_type": {
        "type": "string",
        "enum": ["website", "article", "product", "profile"],
        "default": "website"
      },
      "twitter_card": {
        "type": "string",
        "enum": ["summary", "summary_large_image", "app", "player"],
        "default": "summary_large_image"
      },
      "twitter_title": { "type": "string", "maxLength": 70 },
      "twitter_description": { "type": "string", "maxLength": 200 },
      "twitter_image": { "type": "string", "format": "uri" }
    }
  },
  "local_seo": {
    "type": "object",
    "title": "SEO Local / GEO",
    "properties": {
      "enable_local_schema": {
        "type": "boolean",
        "title": "Activar Schema LocalBusiness",
        "default": false
      },
      "business_type": {
        "type": "string",
        "title": "Tipo de Negocio",
        "enum": [
          "LocalBusiness",
          "Restaurant",
          "Store",
          "ProfessionalService",
          "HealthAndBeautyBusiness",
          "HomeAndConstructionBusiness",
          "LegalService",
          "FinancialService",
          "EducationalOrganization"
        ]
      },
      "address": {
        "type": "object",
        "properties": {
          "street": { "type": "string" },
          "city": { "type": "string" },
          "region": { "type": "string" },
          "postal_code": { "type": "string" },
          "country": { "type": "string", "default": "ES" }
        }
      },
      "coordinates": {
        "type": "object",
        "properties": {
          "latitude": { "type": "number" },
          "longitude": { "type": "number" }
        }
      },
      "service_areas": {
        "type": "array",
        "title": "Áreas de Servicio",
        "items": {
          "type": "object",
          "properties": {
            "type": { "type": "string", "enum": ["City", "State", "Country"] },
            "name": { "type": "string" }
          }
        }
      },
      "opening_hours": {
        "type": "array",
        "title": "Horarios",
        "items": {
          "type": "object",
          "properties": {
            "day": { "type": "string" },
            "opens": { "type": "string", "format": "time" },
            "closes": { "type": "string", "format": "time" }
          }
        }
      }
    }
  }
}

3. Local SEO / GEO
Configuración de SEO local para negocios con presencia física o área de servicio definida.
3.1. Campos GEO por Tenant
Campo	Tipo	Descripción
business_name	string	Nombre del negocio para Google Business Profile
business_type	enum	Tipo Schema.org (LocalBusiness, Restaurant, Store, etc.)
primary_address	object	Dirección principal completa
coordinates	object	Latitud y longitud (GeoCoordinates)
service_radius_km	integer	Radio de servicio en kilómetros
service_areas	array	Ciudades/provincias donde opera
opening_hours	array	Horarios por día de la semana
phone	string	Teléfono principal con formato E.164
google_place_id	string	ID de Google Places para integración
apple_maps_id	string	ID de Apple Maps

3.2. Hreflang Multi-idioma
Configuración de URLs alternativas por idioma para SEO internacional.
 hreflang-tags.html.twig
<link rel="alternate" hreflang="es" href="{{ url_es }}" />
<link rel="alternate" hreflang="ca" href="{{ url_ca }}" />
<link rel="alternate" hreflang="eu" href="{{ url_eu }}" />
<link rel="alternate" hreflang="gl" href="{{ url_gl }}" />
<link rel="alternate" hreflang="x-default" href="{{ url_es }}" />

 
4. Sitemap XML Dinámico
Generación automática de sitemaps XML con prioridades calculadas por tipo de contenido.
 SitemapController.php
<?php
 
namespace Drupal\jaraba_page_builder\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
 
/**
 * Controller para generación de sitemap XML dinámico.
 */
class SitemapController extends ControllerBase {
 
  /**
   * Genera sitemap XML principal.
   */
  public function index(): Response {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    // Sitemap por tipo de contenido
    $sitemaps = [
      'pages' => '/sitemap-pages.xml',
      'jobs' => '/sitemap-jobs.xml',
      'courses' => '/sitemap-courses.xml',
      'products' => '/sitemap-products.xml',
      'providers' => '/sitemap-providers.xml',
    ];
    
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    
    foreach ($sitemaps as $type => $path) {
      $xml .= '<sitemap>';
      $xml .= '<loc>' . $base_url . $path . '</loc>';
      $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
      $xml .= '</sitemap>';
    }
    
    $xml .= '</sitemapindex>';
    
    return new Response($xml, 200, [
      'Content-Type' => 'application/xml',
      'Cache-Control' => 'public, max-age=3600',
    ]);
  }
 
  /**
   * Genera sitemap de páginas del Page Builder.
   */
  public function pages(): Response {
    $storage = \Drupal::entityTypeManager()->getStorage('page_content');
    $pages = $storage->loadByProperties(['status' => 1]);
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $xml .= '<urlset xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
    
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    
    foreach ($pages as $page) {
      $xml .= '<url>';
      $xml .= '<loc>' . $base_url . $page->get('path_alias')->value . '</loc>';
      $xml .= '<lastmod>' . date('Y-m-d', $page->get('changed')->value) . '</lastmod>';
      
      // Prioridad basada en template
      $priority = $this->calculatePriority($page);
      $xml .= '<priority>' . $priority . '</priority>';
      
      // Frecuencia de cambio
      $changefreq = $this->calculateChangefreq($page);
      $xml .= '<changefreq>' . $changefreq . '</changefreq>';
      
      // Imágenes del contenido
      $images = $this->extractImages($page);
      foreach ($images as $image) {
        $xml .= '<image:image>';
        $xml .= '<image:loc>' . $image['url'] . '</image:loc>';
        if (!empty($image['title'])) {
          $xml .= '<image:title>' . htmlspecialchars($image['title']) . '</image:title>';
        }
        $xml .= '</image:image>';
      }
      
      $xml .= '</url>';
    }
    
    $xml .= '</urlset>';
    
    return new Response($xml, 200, [
      'Content-Type' => 'application/xml',
      'Cache-Control' => 'public, max-age=3600',
    ]);
  }
 
  /**
   * Calcula prioridad basada en tipo de página.
   */
  protected function calculatePriority($page): string {
    $template = $page->get('template_id')->value;
    
    $priorities = [
      'landing_main' => '1.0',
      'landing_vertical' => '0.9',
      'service_detail' => '0.8',
      'product_detail' => '0.8',
      'job_detail' => '0.7',
      'course_detail' => '0.7',
      'blog_post' => '0.6',
      'about' => '0.5',
      'contact' => '0.5',
      'faq' => '0.4',
      'terms' => '0.3',
      'privacy' => '0.3',
    ];
    
    return $priorities[$template] ?? '0.5';
  }
 
  /**
   * Calcula frecuencia de cambio.
   */
  protected function calculateChangefreq($page): string {
    $template = $page->get('template_id')->value;
    
    $frequencies = [
      'landing_main' => 'weekly',
      'job_detail' => 'daily',
      'product_detail' => 'weekly',
      'blog_post' => 'monthly',
      'terms' => 'yearly',
      'privacy' => 'yearly',
    ];
    
    return $frequencies[$template] ?? 'monthly';
  }
 
  /**
   * Extrae imágenes del content_data.
   */
  protected function extractImages($page): array {
    $images = [];
    $content = json_decode($page->get('content_data')->value, TRUE);
    
    // Recursivamente buscar campos de imagen
    array_walk_recursive($content, function($value, $key) use (&$images) {
      if (in_array($key, ['image', 'background_image', 'og_image', 'avatar'])) {
        if (!empty($value)) {
          $images[] = [
            'url' => file_create_url($value),
            'title' => '',
          ];
        }
      }
    });
    
    return array_slice($images, 0, 10); // Máximo 10 imágenes
  }
}

 
5. Core Web Vitals Optimization
Optimizaciones CSS y HTML para cumplir con LCP < 2.5s, FID < 100ms y CLS < 0.1.
5.1. Métricas Objetivo
Métrica	Objetivo	Estrategia
LCP (Largest Contentful Paint)	< 2.5s	Critical CSS inline, preload hero image, lazy load below-fold
FID (First Input Delay)	< 100ms	Defer non-critical JS, code splitting, web workers
CLS (Cumulative Layout Shift)	< 0.1	Aspect ratios, font-display: swap, reserved space
INP (Interaction to Next Paint)	< 200ms	Event delegation, requestIdleCallback, virtualization
TTFB (Time to First Byte)	< 600ms	Edge caching, Redis, database optimization

5.2. CSS Crítico y Lazy Loading
 core-web-vitals.css
/**
 * Core Web Vitals Optimization
 * Estilos críticos inline + lazy loading
 */
 
/* ============================================================
   CRITICAL CSS - Inline en <head>
   Solo estilos above-the-fold para LCP < 2.5s
   ============================================================ */
 
/* Reset crítico */
*, *::before, *::after {
  box-sizing: border-box;
}
 
/* Prevenir CLS - Reservar espacio para elementos dinámicos */
.jaraba-hero {
  min-height: 100vh;
  min-height: 100dvh; /* Dynamic viewport height */
}
 
.jaraba-hero__image {
  aspect-ratio: 16/9;
  width: 100%;
  background-color: var(--color-surface-elevated);
}
 
/* Prevenir CLS en imágenes */
img {
  max-width: 100%;
  height: auto;
  display: block;
}
 
img[loading="lazy"] {
  /* Reservar espacio mientras carga */
  background-color: var(--color-surface-elevated);
}
 
/* Aspect ratios para contenedores de imagen */
.aspect-video { aspect-ratio: 16/9; }
.aspect-square { aspect-ratio: 1/1; }
.aspect-portrait { aspect-ratio: 3/4; }
 
/* Fonts - Prevenir FOUT/FOIT */
@font-face {
  font-family: 'Inter';
  font-display: swap; /* CRÍTICO: Mostrar fallback inmediatamente */
  src: local('Inter'), 
       url('/fonts/inter-var.woff2') format('woff2-variations');
  font-weight: 100 900;
}
 
/* Container queries para componentes responsivos sin reflow */
@container (min-width: 640px) {
  .jaraba-features__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
 
@container (min-width: 1024px) {
  .jaraba-features__grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
 
/* ============================================================
   NON-CRITICAL CSS - Cargado async
   ============================================================ */
 
/* Animaciones - Solo después de interacción */
@media (prefers-reduced-motion: no-preference) {
  .jaraba-animate {
    animation-play-state: running;
  }
}
 
/* Contenido below-the-fold */
.jaraba-section:not(:first-child) {
  content-visibility: auto;
  contain-intrinsic-size: 0 500px;
}
 
/* ============================================================
   PERFORMANCE UTILITIES
   ============================================================ */
 
/* GPU acceleration para elementos animados */
.will-animate {
  will-change: transform, opacity;
}
 
/* Contención para rendimiento */
.contain-layout {
  contain: layout;
}
 
.contain-paint {
  contain: paint;
}
 
.contain-strict {
  contain: strict;
}
 
/* Lazy loading de secciones */
.lazy-section {
  content-visibility: auto;
  contain-intrinsic-block-size: 500px;
}

 
6. APIs SEO
Endpoints para auditoría SEO, scores y sugerencias automáticas.
Método	Endpoint	Descripción
GET	/api/v1/seo/audit/{page_id}	Auditoría SEO completa de una página
GET	/api/v1/seo/score/{page_id}	Score SEO (0-100) con desglose
POST	/api/v1/seo/suggestions	Sugerencias IA para mejorar SEO
GET	/api/v1/seo/keywords/research	Research de keywords por vertical
GET	/api/v1/seo/serp-preview	Preview de cómo aparecerá en Google
POST	/api/v1/seo/schema/validate	Validar Schema.org contra Google Rich Results Test
GET	/api/v1/seo/core-web-vitals/{url}	Métricas CWV de una URL
GET	/api/v1/sitemap.xml	Sitemap XML principal
GET	/api/v1/sitemap-{type}.xml	Sitemap por tipo de contenido

6.1. Respuesta API Auditoría
 seo-audit-response.json
{
  "page_id": "123",
  "url": "/servicios/consultoria",
  "score": 85,
  "breakdown": {
    "meta_tags": { "score": 90, "issues": [] },
    "headings": { "score": 80, "issues": ["H1 too long (65 chars, max 60)"] },
    "images": { "score": 75, "issues": ["2 images missing alt text"] },
    "schema": { "score": 100, "issues": [] },
    "performance": { "score": 82, "issues": ["LCP: 2.8s (target <2.5s)"] },
    "mobile": { "score": 95, "issues": [] }
  },
  "suggestions": [
    {
      "priority": "high",
      "category": "meta",
      "message": "Add focus keyword 'consultoría empresarial' to meta description",
      "auto_fix_available": true
    }
  ],
  "competitors": {
    "average_score": 72,
    "your_ranking": 2
  }
}

 
7. Roadmap de Implementación
Sprint	Componente	Horas	Dependencias
1	Schema.org Templates (6 tipos)	25-30h	Doc 162 (Page Builder Core)
1	Campos SEO en Form Builder	15-20h	JSON Schema extensión
2	Local SEO / GEO fields	15-20h	Google Maps API
2	Sitemap XML dinámico	10-15h	Routing Drupal
3	Core Web Vitals CSS	10-15h	Critical CSS extraction
3	APIs SEO + Auditoría	20-25h	Claude API para sugerencias

Total estimado: 80-100 horas (€6,400-€8,000 @ €80/h)

Integración con Documento 162
Este documento extiende el Page Builder especificado en 162_Page_Builder_Sistema_Completo_EDI_v1.docx, añadiendo la capa de SEO/GEO avanzado que diferencia los planes Professional y Enterprise.

Fin del documento.
