# Blog Clase Mundial — Plan de Implementación Integral

**Fecha**: 2026-02-26
**Módulo principal**: `jaraba_content_hub`
**Módulo secundario**: `jaraba_blog` (candidato a consolidación futura)
**Spec**: Platform_AI_Content_Hub_v2 (Doc 128), Blog_System_Nativo_v1 (Doc 178)
**Impacto**: Blog público del SaaS elevado a nivel clase mundial con cumplimiento total de directrices

---

## Índice de Navegación (TOC)

1. [Contexto y Diagnóstico](#1-contexto-y-diagnóstico)
   - 1.1 [Estado actual del blog](#11-estado-actual-del-blog)
   - 1.2 [Conflicto de módulos](#12-conflicto-de-módulos)
   - 1.3 [Gaps identificados](#13-gaps-identificados)
2. [Arquitectura del Blog](#2-arquitectura-del-blog)
   - 2.1 [Flujo de renderizado](#21-flujo-de-renderizado)
   - 2.2 [Entidades involucradas](#22-entidades-involucradas)
   - 2.3 [Servicios del Content Hub](#23-servicios-del-content-hub)
   - 2.4 [Integración con sistema de tenants](#24-integración-con-sistema-de-tenants)
3. [Fase A — Estilo Premium y Cumplimiento de Directrices (COMPLETADA)](#3-fase-a--estilo-premium-y-cumplimiento-de-directrices-completada)
   - 3.1 [A1: Limpieza de templates redundantes](#31-a1-limpieza-de-templates-redundantes)
   - 3.2 [A2: Reescritura del blog index con directrices premium](#32-a2-reescritura-del-blog-index-con-directrices-premium)
   - 3.3 [A3: Corrección de category page](#33-a3-corrección-de-category-page)
   - 3.4 [A4: Body classes para blog](#34-a4-body-classes-para-blog)
   - 3.5 [A5-A6: Compilación SCSS y caché](#35-a5-a6-compilación-scss-y-caché)
4. [Fase B — Gaps Funcionales (PENDIENTE)](#4-fase-b--gaps-funcionales-pendiente)
   - 4.1 [B1: Paginación en blog listing](#41-b1-paginación-en-blog-listing)
   - 4.2 [B2: SEO/OG meta tags en listing](#42-b2-seoog-meta-tags-en-listing)
   - 4.3 [B3: Schema markup en artículos](#43-b3-schema-markup-en-artículos)
   - 4.4 [B4: URLs slug en vez de ID](#44-b4-urls-slug-en-vez-de-id)
   - 4.5 [B5: Search UI pública](#45-b5-search-ui-pública)
   - 4.6 [B6: RSS feed](#46-b6-rss-feed)
5. [Fase C — Integración con Tenants y Suscripciones (PENDIENTE)](#5-fase-c--integración-con-tenants-y-suscripciones-pendiente)
   - 5.1 [C1: Campo tenant_id en ContentArticle](#51-c1-campo-tenant_id-en-contentarticle)
   - 5.2 [C2: Filtrado por tenant en BlogController](#52-c2-filtrado-por-tenant-en-blogcontroller)
   - 5.3 [C3: Feature gate y planes de suscripción](#53-c3-feature-gate-y-planes-de-suscripción)
   - 5.4 [C4: Quotas de artículos por plan](#54-c4-quotas-de-artículos-por-plan)
6. [Tabla de Correspondencia de Directrices](#6-tabla-de-correspondencia-de-directrices)
7. [Tabla de Especificaciones Técnicas](#7-tabla-de-especificaciones-técnicas)
8. [Archivos Modificados y de Referencia](#8-archivos-modificados-y-de-referencia)
9. [Verificación y Checklist](#9-verificación-y-checklist)

---

## 1. Contexto y Diagnóstico

### 1.1 Estado actual del blog

La página `/blog` es servida por el módulo `jaraba_content_hub` a través de la ruta `jaraba_content_hub.blog`. El controlador `BlogController::index()` consulta dos servicios (`ArticleService` y `CategoryService`) y renderiza el template `content-hub-blog-index.html.twig` usando el tema registrado `content_hub_blog_index`.

El **page template** que envuelve este contenido es `page--content-hub.html.twig`, registrado mediante `jaraba_content_hub_theme_suggestions_page_alter()` que mapea todas las rutas `jaraba_content_hub.*` al template `page__content_hub`. Este template implementa la **Zero Region Policy**: usa `{{ clean_content }}` (extraído por `preprocess_page()`) en vez de `{{ page.content }}`, incluye header y footer como parciales Twig reutilizables, y no renderiza bloques de Drupal.

**Cadena de renderizado:**
```
Ruta jaraba_content_hub.blog
  → BlogController::index()
    → #theme = content_hub_blog_index
      → content-hub-blog-index.html.twig (contenido del blog)
        → Envuelto por page--content-hub.html.twig (Zero Region Policy)
          → Envuelto por html.html.twig (DOCTYPE, head, body)
```

### 1.2 Conflicto de módulos

Existen **dos módulos** que sirven `/blog`:

| Módulo | Ruta | Prioridad | Features |
|--------|------|-----------|----------|
| `jaraba_content_hub` | `jaraba_content_hub.blog` | **GANA** (menor peso) | Artículos, categorías, trending, AI |
| `jaraba_blog` | `jaraba_blog.listing` | Fallback | SEO, RSS, paginación, schema, `tenant_id` |

El módulo `jaraba_content_hub` gana la ruta pero carece de funcionalidades que `jaraba_blog` sí tiene. La estrategia es **migrar las features de `jaraba_blog` a `jaraba_content_hub`** (Fases B y C) en vez de cambiar qué módulo sirve la ruta, porque `jaraba_content_hub` tiene una arquitectura más completa (AI, recommendation engine, sentiment analysis).

### 1.3 Gaps identificados

| # | Gap | Severidad | Fase |
|---|-----|-----------|------|
| G1 | Template blog sin estilo premium (clases CSS definidas pero no aprovechadas) | ALTA | A (HECHO) |
| G2 | URLs hardcodeadas (`/blog` en vez de `path()`) | ALTA | A (HECHO) |
| G3 | Textos sin `{% trans %}` en algunas partes | MEDIA | A (HECHO) |
| G4 | SVG inline en vez de `jaraba_icon()` | MEDIA | A (HECHO) |
| G5 | Empty states pobres (sin icono, sin CTA) | MEDIA | A (HECHO) |
| G6 | Sin `page-blog` body class para targeting CSS | MEDIA | A (HECHO) |
| G7 | Template `page--blog.html.twig` redundante causando conflicto | ALTA | A (HECHO) |
| G8 | Controller no pasaba `category_url` ni `featured_image` al template | ALTA | A (HECHO) |
| G9 | Sin paginación en listado | ALTA | B |
| G10 | Sin SEO/OG meta tags en listing | ALTA | B |
| G11 | Sin schema markup (Article, Blog, BreadcrumbList) | ALTA | B |
| G12 | URLs por ID en vez de slug | ALTA | B |
| G13 | Sin Search UI pública | MEDIA | B |
| G14 | Sin RSS feed | MEDIA | B |
| G15 | `ContentArticle` sin `tenant_id` | CRÍTICA | C |
| G16 | Blog no aparece en planes de suscripción | ALTA | C |
| G17 | BlogController sin filtrado por tenant | ALTA | C |
| G18 | Sin quotas de artículos por plan | MEDIA | C |

---

## 2. Arquitectura del Blog

### 2.1 Flujo de renderizado

```
┌─────────────────────────────────────────────────────────────────┐
│  CAPA 1: html.html.twig                                        │
│  ├── DOCTYPE, <html>, <head>, <body>                           │
│  ├── Body classes: page-content-hub, page--clean-layout,       │
│  │   page-blog (via hook_preprocess_html)                      │
│  └── Libraries: ecosistema_jaraba_theme/global                 │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  CAPA 2: page--content-hub.html.twig                       ││
│  │  ├── {% include _header.html.twig %}                       ││
│  │  ├── <main> {{ clean_content }}                            ││
│  │  │   ┌─────────────────────────────────────────────────┐   ││
│  │  │   │  CAPA 3: content-hub-blog-index.html.twig       │   ││
│  │  │   │  ├── .blog-hero (título + subtítulo)            │   ││
│  │  │   │  ├── .blog-filters                              │   ││
│  │  │   │  │   └── {% include _category-filter.html.twig %}│  ││
│  │  │   │  └── .blog-content                              │   ││
│  │  │   │      ├── .blog-grid__main                       │   ││
│  │  │   │      │   └── {% for %}                          │   ││
│  │  │   │      │       {% include _article-card.html.twig %}│  ││
│  │  │   │      └── .blog-grid__sidebar                    │   ││
│  │  │   │          ├── .sidebar-widget (Trending)         │   ││
│  │  │   │          └── .sidebar-widget--newsletter        │   ││
│  │  │   └─────────────────────────────────────────────────┘   ││
│  │  └── {% include _footer.html.twig %}                       ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Entidades involucradas

| Entidad | Tipo | Módulo | Campos clave |
|---------|------|--------|-------------|
| `content_article` | ContentEntity | `jaraba_content_hub` | title, slug, excerpt, body, answer_capsule, featured_image, category (ref), publish_date, status, reading_time, author |
| `content_category` | ContentEntity | `jaraba_content_hub` | name, slug, description, color, icon |
| `blog_post` | ContentEntity | `jaraba_blog` | title, slug, content, tenant_id, featured_image_id, meta_title, meta_description |

**Nota crítica**: `content_article` NO tiene campo `tenant_id` — esto impide el aislamiento multi-tenant. Ver Fase C.

### 2.3 Servicios del Content Hub

| Servicio | ID | Responsabilidad |
|----------|-----|-----------------|
| `ArticleService` | `jaraba_content_hub.article_service` | CRUD artículos, queries publicados, trending |
| `CategoryService` | `jaraba_content_hub.category_service` | CRUD categorías, conteo artículos por categoría |
| `RecommendationService` | `jaraba_content_hub.recommendation_service` | Recomendaciones AI via Qdrant |
| `SeoService` | `jaraba_content_hub.seo_service` | Meta tags, schema markup para artículos individuales |
| `SentimentEngine` | `jaraba_content_hub.sentiment_engine` | Análisis de sentimiento del contenido |
| `ReputationMonitor` | `jaraba_content_hub.reputation_monitor` | Monitoreo de reputación |

### 2.4 Integración con sistema de tenants

**Estado actual: SIN INTEGRACIÓN**

El blog opera globalmente sin aislamiento por tenant:

- `ContentArticle` no tiene campo `tenant_id`
- `BlogController::index()` no usa `TenantContextService` ni `TenantBridgeService`
- `ContentArticleAccessControlHandler` no verifica tenant match (viola `TENANT-ISOLATION-ACCESS-001`)
- El blog no aparece en `FeatureAccessService::FEATURE_ADDON_MAP` ni en `SaasPlan` features
- No hay quotas de artículos en `QuotaManagerService`

**Contraste con `jaraba_blog`**: La entidad `BlogPost` SÍ tiene `tenant_id` (líneas 74-82 de `BlogPost.php`), pero este módulo pierde la ruta frente a `jaraba_content_hub`.

---

## 3. Fase A — Estilo Premium y Cumplimiento de Directrices (COMPLETADA)

**Estado**: COMPLETADA (2026-02-26)
**Commit**: Pendiente de commit

### 3.1 A1: Limpieza de templates redundantes

**Problema**: Se creó `page--blog.html.twig` en el tema con header/footer duplicados. El módulo `jaraba_content_hub` ya registra `page--content-hub.html.twig` via `theme_suggestions_page_alter()`, que aplica Zero Region Policy correctamente.

**Acciones realizadas**:

1. **Eliminado** `web/themes/custom/ecosistema_jaraba_theme/templates/page--blog.html.twig`
2. **Eliminada** la sección blog del `theme_suggestions_page_alter()` en `.theme` (líneas ~3560-3575 que añadían `page__blog` a las sugerencias)

**Lógica**: Las rutas `jaraba_content_hub.*` son capturadas por el hook del módulo:
```php
// jaraba_content_hub.module
function jaraba_content_hub_theme_suggestions_page_alter(array &$suggestions, array $variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName() ?? '';
  if (str_starts_with($route_name, 'jaraba_content_hub.') && !str_contains($route_name, '.api.')) {
    $suggestions[] = 'page__content_hub';
  }
}
```

Esto mapea `/blog` → `page--content-hub.html.twig`, que ya implementa Zero Region Policy con header, footer y `{{ clean_content }}`.

### 3.2 A2: Reescritura del blog index con directrices premium

**Archivo**: `web/modules/custom/jaraba_content_hub/templates/content-hub-blog-index.html.twig`

**Cambios principales**:

| Aspecto | Antes | Después | Directriz cumplida |
|---------|-------|---------|---------------------|
| URL filtro "All" | `all_url: '/blog'` | `all_url: path('jaraba_content_hub.blog')` | ROUTE-LANGPREFIX-001 |
| Newsletter action | `action="/newsletter/subscribe"` | Eliminado (solo `method="post"`) | ROUTE-LANGPREFIX-001 |
| Empty state | `<p>No articles found.</p>` | Icono + título + texto descriptivo | UX premium |
| Icono empty state | Ninguno | `jaraba_icon('ui', 'search', { size: '48px', color: 'neutral' })` | ICON-CONVENTION-001 |
| Icono trending | Ninguno | `jaraba_icon('ui', 'arrow-right', { size: '18px' })` | ICON-CONVENTION-001 |
| Accesibilidad email | Sin aria-label | `aria-label="{% trans %}Email address{% endtrans %}"` | WCAG 2.1 |

**Controller actualizado** (`BlogController.php`):

Se añadieron dos campos al array de artículos que el parcial `_article-card.html.twig` necesita:

```php
// AÑADIDO: category_url para enlace a categoría en la card
'category_url' => $category ? $category->toUrl()->toString() : '',
// AÑADIDO: featured_image para imagen destacada en la card
'featured_image' => $this->getImageUrl($article),
```

Se inyectó `FileUrlGeneratorInterface` para generar URLs absolutas de imágenes:

```php
public function __construct(
    ArticleService $articleService,
    CategoryService $categoryService,
    FileUrlGeneratorInterface $fileUrlGenerator, // NUEVO
) { ... }
```

**Estructura del template reescrito**:

```twig
{# content-hub-blog-index.html.twig #}
{{ attach_library('ecosistema_jaraba_theme/content-hub') }}

<div class="blog-page">
  {# HERO — Clases de _content-hub.scss #}
  <section class="blog-hero">...</section>

  {# FILTERS — Parcial reutilizable #}
  <section class="blog-filters">
    {% include _category-filter.html.twig with { all_url: path('...') } %}
  </section>

  {# CONTENT — Grid + Sidebar #}
  <section class="blog-content">
    <div class="blog-grid">
      <div class="blog-grid__main">
        {% for article in articles %}
          {% include _article-card.html.twig %}
        {% endfor %}
        {# Empty state con jaraba_icon() #}
      </div>
      <aside class="blog-grid__sidebar">
        {# Trending con jaraba_icon() #}
        {# Newsletter form #}
      </aside>
    </div>
  </section>
</div>
```

### 3.3 A3: Corrección de category page

**Archivo**: `web/modules/custom/jaraba_content_hub/templates/content-hub-category-page.html.twig`

| Aspecto | Antes | Después | Directriz |
|---------|-------|---------|-----------|
| Back link URL | `href="/blog"` | `href="{{ path('jaraba_content_hub.blog') }}"` | ROUTE-LANGPREFIX-001 |
| Back link icon | SVG inline (polyline) | `jaraba_icon('ui', 'chevron-left', { size: '20px' })` | ICON-CONVENTION-001 |
| Empty CTA URL | `href="/blog"` | `href="{{ path('jaraba_content_hub.blog') }}"` | ROUTE-LANGPREFIX-001 |
| Empty state | Solo `<p>` + botón | Icono + título + texto + CTA | UX premium |

### 3.4 A4: Body classes para blog

**Archivo**: `web/modules/custom/jaraba_content_hub/jaraba_content_hub.module`

Se añadió la body class `page-blog` condicionalmente dentro de `jaraba_content_hub_preprocess_html()`:

```php
function jaraba_content_hub_preprocess_html(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName() ?? '';

  if (str_starts_with($route_name, 'jaraba_content_hub.')) {
    $variables['attributes']['class'][] = 'page-content-hub';
    $variables['attributes']['class'][] = 'page--clean-layout';

    // Body class específica para targeting CSS del blog.
    if ($route_name === 'jaraba_content_hub.blog') {
      $variables['attributes']['class'][] = 'page-blog';
    }
  }
}
```

**Importante**: Las body classes DEBEN establecerse en `hook_preprocess_html()`, NUNCA con `attributes.addClass()` en el template page (no funciona para `<body>`). Ver directriz frontend-page-pattern.md.

### 3.5 A5-A6: Compilación SCSS y caché

**Compilación SCSS**:
```bash
cd web/themes/custom/ecosistema_jaraba_theme
npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed
```

**Limpieza de caché**:
```bash
lando drush cr
```

No se requirieron cambios en SCSS porque todas las clases CSS del blog (`.blog-page`, `.blog-hero`, `.blog-grid`, `.article-card`, `.sidebar-widget`, `.trending-list`, `.newsletter-form`, `.blog-empty`, `.category-hero`, `.category-empty`) ya están definidas en `scss/_content-hub.scss` con tokens `var(--ej-*)`.

---

## 4. Fase B — Gaps Funcionales (PARCIALMENTE COMPLETADA)

### 4.1 B1: Paginación en blog listing (COMPLETADA)

**Prioridad**: ALTA
**Estado**: COMPLETADA (2026-02-26, sesión 2)

**Implementación realizada** (diferente a la propuesta — se usó paginación custom en vez de PagerManagerInterface):

1. **Inyección RequestStack** en `BlogController` para leer `?page=N` del query string.
2. **Nuevo método** `countPublishedArticles()` en `ArticleService` con filtro opcional por categoría.
3. **Sliding window ±2 páginas** con URLs generadas via `Url::fromRoute()`.
4. **Variable `pager`** declarada en `hook_theme()` para `content_hub_blog_index`.
5. **Template** `<nav class="blog-pagination">` con aria-label, `<ol>` de páginas, prev/next links.
6. **SCSS** `.blog-pagination` con hover/focus/current states y prefers-reduced-motion.
7. **Cache context** `url.query_args:page` añadido.

**Directrices cumplidas**: i18n (`{% trans %}`), `Url::fromRoute()`, aria-label, mobile-first, SCSS tokens.

### 4.2 B2: SEO/OG meta tags en listing

**Prioridad**: ALTA
**Archivo principal**: `BlogController.php` + nuevo `BlogSeoSubscriber`

**Estado actual**: El `SeoService` del módulo existe pero solo aplica a artículos individuales, no al listing.

**Implementación propuesta**:

1. Crear `EventSubscriber` que intercepte la ruta `jaraba_content_hub.blog`:
   ```php
   class BlogSeoSubscriber implements EventSubscriberInterface {
     public function onKernelResponse(ResponseEvent $event) {
       // Añadir: <title>, og:title, og:description, og:type=website,
       // og:image (imagen genérica del blog), canonical URL
     }
   }
   ```

2. Usar `#attached['html_head']` en el render array del controller como alternativa más simple:
   ```php
   '#attached' => [
     'html_head' => [
       [['#tag' => 'meta', '#attributes' => ['property' => 'og:title', 'content' => $title]], 'og_title'],
       [['#tag' => 'meta', '#attributes' => ['property' => 'og:description', 'content' => $description]], 'og_desc'],
       [['#tag' => 'link', '#attributes' => ['rel' => 'canonical', 'href' => $canonical_url]], 'canonical'],
     ],
   ],
   ```

### 4.3 B3: Schema markup en artículos

**Prioridad**: ALTA
**Servicio**: `SeoService` (existente, extender)

**Schemas a implementar**:

| Schema | Dónde | Datos |
|--------|-------|-------|
| `Blog` | Listing page | name, description, url, blogPost[] |
| `BlogPosting` | Article detail | headline, datePublished, author, image, articleBody |
| `BreadcrumbList` | Todas las páginas blog | Blog > Categoría > Artículo |
| `Organization` | Listing page | publisher info |

**Formato**: JSON-LD inyectado via `#attached['html_head']`.

### 4.4 B4: URLs slug en vez de ID

**Prioridad**: ALTA
**Archivos**: `jaraba_content_hub.routing.yml`, `ContentArticle.php`, `ContentCategory.php`

**Estado actual**: Las entidades usan `toUrl()->toString()` que genera URLs por ID (`/content_article/5`).

**Implementación propuesta**:

1. Modificar el `route_provider` para usar slugs:
   ```yaml
   entity.content_article.canonical:
     path: '/blog/{slug}'
     defaults:
       _controller: '\Drupal\jaraba_content_hub\Controller\ArticleController::view'
     requirements:
       slug: '[a-z0-9\-]+'
   ```

2. Crear `ParamConverter` para resolver slug → entity.

3. Actualizar `ContentArticle::toUrl()` para generar URLs con slug.

### 4.5 B5: Search UI pública

**Prioridad**: MEDIA

**Implementación propuesta**:

1. Añadir campo de búsqueda en la hero section del blog.
2. Endpoint API: `GET /api/v1/content-hub/search?q={term}` (respetando `CSRF-API-001`).
3. Resultados renderizados con el parcial `_article-card.html.twig` variant `horizontal`.
4. Autocomplete con debounce (300ms).

### 4.6 B6: RSS feed

**Prioridad**: MEDIA
**Ruta**: `/blog/rss.xml`

**Implementación propuesta**:

1. Controller que genere XML RSS 2.0 con los últimos 20 artículos.
2. `<link rel="alternate" type="application/rss+xml">` en `<head>`.
3. Filtrado por tenant cuando se implemente Fase C.

---

## 5. Fase C — Integración con Tenants y Suscripciones (PENDIENTE)

### 5.1 C1: Campo tenant_id en ContentArticle

**Prioridad**: CRÍTICA

**Implementación propuesta**:

1. Añadir campo `tenant_id` a `ContentArticle::baseFieldDefinitions()`:
   ```php
   $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
     ->setLabel(t('Tenant'))
     ->setSetting('target_type', 'tenant')
     ->setRequired(TRUE)
     ->setDefaultValueCallback(static::class . '::getDefaultTenantId');
   ```

2. Migración de datos existentes: asignar tenant basándose en el `author` del artículo.

3. Actualizar `ContentArticleAccessControlHandler` para verificar tenant match (TENANT-ISOLATION-ACCESS-001):
   ```php
   protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
     if (in_array($operation, ['update', 'delete'])) {
       $user_tenant = $this->tenantContext->getCurrentTenant();
       $entity_tenant = $entity->get('tenant_id')->target_id;
       if ($user_tenant && $user_tenant->id() !== $entity_tenant) {
         return AccessResult::forbidden('Tenant mismatch');
       }
     }
     // ...
   }
   ```

### 5.2 C2: Filtrado por tenant en BlogController

**Implementación propuesta**:

1. Inyectar `TenantContextService` en `BlogController`.
2. Filtrar artículos por tenant activo:
   ```php
   $tenant = $this->tenantContext->getCurrentTenant();
   $articles = $this->articleService->getPublishedArticles([
     'limit' => $limit,
     'tenant_id' => $tenant ? $tenant->id() : NULL,
   ]);
   ```

### 5.3 C3: Feature gate y planes de suscripción

**Implementación propuesta**:

1. Añadir `'content_hub' => 'CONTENT_HUB'` al `FEATURE_ADDON_MAP` en `FeatureAccessService`.
2. Añadir feature `blog_avanzado` en `SaasPlan` para planes Pro/Enterprise.
3. Gate en `BlogController` para verificar acceso antes de renderizar.

### 5.4 C4: Quotas de artículos por plan

**Implementación propuesta**:

| Plan | Artículos/mes | Categorías | Featured images |
|------|---------------|------------|-----------------|
| Starter | 5 | 3 | No |
| Pro | 50 | Ilimitadas | Sí |
| Enterprise | Ilimitadas | Ilimitadas | Sí |

Extender `QuotaManagerService` con métodos `checkArticleQuota()` y `getArticleUsage()`.

---

## 5b. Fase A+ — Trabajo Adicional Completado (Sesión 2)

**Estado**: COMPLETADA (2026-02-26)

Además de la Fase A original y B1 (paginación), la sesión 2 completó las siguientes mejoras:

### 5b.1 P0 — Correcciones Críticas

| # | Acción | Archivo(s) | Directriz |
|---|--------|-----------|-----------|
| P0-1 | `ContentCategoryAccessControlHandler` creado con DI + EntityHandlerInterface | `src/ContentCategoryAccessControlHandler.php` | TENANT-ISOLATION-ACCESS-001 |
| P0-2 | Fix canonical URL y OG tags en presave (usaban `/content_article/{id}` hardcoded) | `jaraba_content_hub.module` presave hook | ROUTE-LANGPREFIX-001 |
| P0-3 | Image Styles creados: `article_card` (600x400), `article_featured` (1200x600), `article_hero` (1600x800). Srcset en BlogController y CategoryController | `BlogController.php`, `CategoryController.php` | Responsive images |
| P0-4 | Newsletter form action URL fix + `related-articles.js` migrado a `Url::fromRoute()` | Templates + JS | ROUTE-LANGPREFIX-001 |

### 5b.2 P1 — SCSS y Accesibilidad

| # | Acción | Archivo(s) |
|---|--------|-----------|
| P1-1 | SCSS gaps: empty state hover/focus, breakpoints tablet (768px-1024px), newsletter focus-visible | `_content-hub.scss` |
| P1-2 | Heading hierarchy: trending `<h3>` → `<h2>`, skip link, aria-label en aside, category filter URL fix | Templates + `page--content-hub.html.twig` |

### 5b.3 P2 — UX de Lectura

| # | Acción | Archivo(s) |
|---|--------|-----------|
| P2-1a | `template_preprocess_content_article()` creado (~90 LOC): entity data, author bio, responsive srcset | `jaraba_content_hub.module` |
| P2-1b | `content-article--full.html.twig` reescrito: progress bar, figure+srcset, time+datetime, author bio, share buttons (FB/TW/LI/copy) | Template |
| P2-1c | `reading-progress.js` nuevo: requestAnimationFrame + Clipboard API + prefers-reduced-motion | `js/reading-progress.js` |
| P2-1d | Library `reading-progress` registrada | `jaraba_content_hub.libraries.yml` |

### 5b.4 P2 — Backend

| # | Acción | Archivo(s) |
|---|--------|-----------|
| P2-2a | N+1 query fix: `getArticleCountsByCategory()` con GROUP BY | `CategoryService.php` |
| P2-2b | CategoryController enhanced: `FileUrlGeneratorInterface` DI + `getImageData()` + cache contexts | `CategoryController.php` |
| P2-2c | Presave resilience: `hasService()` + try-catch para 3 servicios opcionales | `jaraba_content_hub.module` |

### 5b.5 P3 — SCSS y Compilación

| # | Acción | Archivo(s) |
|---|--------|-----------|
| P3-1 | Nuevos estilos: `.reading-progress`, `.author-bio`, `.blog-pagination`, share link enhancements | `_content-hub.scss` |
| P3-2 | Updated `@media (prefers-reduced-motion)` y `@media print` | `_content-hub.scss` |
| P3-3 | 2 SVGs nuevos: `social/facebook.svg`, `ui/sparkles.svg` | Icons dir |
| P3-4 | SCSS compilado + cache limpiada | CSS output |

### 5b.6 Archivos Creados/Modificados (Sesión 2)

| Archivo | Acción |
|---------|--------|
| `jaraba_content_hub.module` | +template_preprocess_content_article, +presave resilience, +pager var in hook_theme |
| `BlogController.php` | +RequestStack DI, +pagination, +batch category counts |
| `CategoryController.php` | +FileUrlGenerator DI, +getImageData(), +enhanced article items |
| `ArticleService.php` | +countPublishedArticles() |
| `CategoryService.php` | +getArticleCountsByCategory() GROUP BY |
| `content-article--full.html.twig` | REESCRITO (progress bar, srcset, author, share, aria) |
| `content-hub-blog-index.html.twig` | +pagination section |
| `page--content-hub.html.twig` | +skip link |
| `_category-filter.html.twig` | URL fix path() |
| `_content-hub.scss` | +reading-progress, +author-bio, +blog-pagination, +share, +print/motion |
| `js/reading-progress.js` | NUEVO |
| `jaraba_content_hub.libraries.yml` | +reading-progress library |
| `social/facebook.svg` | NUEVO |
| `ui/sparkles.svg` | NUEVO |

---

## 6. Tabla de Correspondencia de Directrices

Esta tabla mapea cada directriz del proyecto con su cumplimiento en la implementación del blog.

| Código Directriz | Nombre | Estado | Dónde se aplica | Notas |
|------------------|--------|--------|-----------------|-------|
| **Zero Region Policy** | Frontend limpio sin `{{ page.* }}` | CUMPLIDA | `page--content-hub.html.twig` usa `{{ clean_content }}` | Aplica a todas las rutas content_hub |
| **ROUTE-LANGPREFIX-001** | URLs con `path()` o `Url::fromRoute()` | CUMPLIDA (Fase A) | Templates blog-index y category-page | Eliminadas 4 URLs hardcodeadas `/blog` |
| **i18n** | Textos con `{% trans %}` / `$this->t()` | CUMPLIDA (Fase A) | Todos los templates del blog | Hero subtitle, empty states, labels |
| **ICON-CONVENTION-001** | `jaraba_icon('category', 'name', {opts})` | CUMPLIDA (Fase A) | Empty states, trending, back link | Reemplazados SVG inline |
| **ICON-DUOTONE-001** | Variant duotone en premium | PARCIAL | Se usa `outline` en UI funcional | Duotone para decorativo, outline para nav |
| **ICON-COLOR-001** | Colores Jaraba palette | CUMPLIDA | `color: 'neutral'` en empty states | No hex codes directos |
| **TENANT-ISOLATION-ACCESS-001** | Verificar tenant match | PENDIENTE (Fase C) | `ContentArticleAccessControlHandler` | Requiere campo `tenant_id` |
| **TENANT-BRIDGE-001** | Usar `TenantBridgeService` | PENDIENTE (Fase C) | `BlogController` | No existe tenant_id aún |
| **PREMIUM-FORMS-PATTERN-001** | Forms extienden `PremiumEntityFormBase` | NO APLICA | Blog es lectura pública | Las forms de admin ya cumplen |
| **FORM-CACHE-001** | No `setCached(TRUE)` en GET | NO APLICA | Newsletter form es HTML puro | No usa Drupal Form API |
| **SCSS tokens** | Solo `var(--ej-*)`, no `$ej-*` local | CUMPLIDA | `_content-hub.scss` usa tokens | No se modificó SCSS |
| **5-Layer Architecture** | Tokens → CSS Props → Component → Tenant → Vertical | CUMPLIDA | Clases CSS del blog usan capas 2 y 3 | Override por tenant en capa 4 |
| **Dart Sass** | Compilar con `npx sass` | CUMPLIDA | Compilación A5 | `--style=compressed` |
| **Body classes via preprocess_html** | `hook_preprocess_html()`, no `attributes.addClass()` | CUMPLIDA (Fase A) | `jaraba_content_hub.module` | Añadida `page-blog` |
| **TPL-PAGE-001** | page templates sin DOCTYPE/html/head | CUMPLIDA | `page--content-hub.html.twig` | Solo content dentro de body |
| **Parciales reutilizables** | `{% include %}` para elementos compartidos | CUMPLIDA | `_article-card`, `_category-filter`, `_header`, `_footer` | 4 parciales usados |
| **Mobile-first** | Layout responsive | CUMPLIDA | CSS grid en `_content-hub.scss` | `.blog-grid` ya es responsive |
| **Glassmorphism** | Premium card pattern | CUMPLIDA | `.blog-hero` en SCSS | `backdrop-filter: blur(10px)` |
| **CSRF-API-001** | Token CSRF en endpoints API | PENDIENTE (Fase B) | Search API, newsletter | Requiere implementación |
| **TWIG-XSS-001** | `\|safe_html` para user content | CUMPLIDA | `_article-card` usa autoescaping | Twig autoescapa por defecto |
| **TM-CAST-001** | Cast `(string)` en `$this->t()` para render arrays | CUMPLIDA | `BlogController` usa `$this->t('Blog')` en config default | No pasa a sub-templates |
| **Slide-panel** | Acciones crear/editar en modal | NO APLICA | Blog público es solo lectura | Admin forms del módulo son separadas |
| **GrapesJS** | Bloques con naming `jaraba-*` | NO APLICA | Blog no usa Page Builder | Los artículos usan body field, no canvas |

---

## 7. Tabla de Especificaciones Técnicas

### 7.1 Stack Tecnológico

| Componente | Versión | Uso en Blog |
|------------|---------|-------------|
| PHP | 8.4 | BlogController, servicios, entidades |
| Drupal | 11 | Framework base, routing, template engine |
| MariaDB | 10.11+ | Storage de ContentArticle, ContentCategory |
| Redis | 7.4 | Cache de render arrays (max-age: 300s) |
| Dart Sass | Último | Compilación de `_content-hub.scss` |
| Node.js | 22.x | Runtime para Dart Sass (via npx) |
| Twig | 3.x | Templates del blog (integrado en Drupal 11) |

### 7.2 Entidades y Campos

#### ContentArticle

| Campo | Tipo | Requerido | Uso |
|-------|------|-----------|-----|
| `title` | string | Sí | Título del artículo |
| `slug` | string | Sí | URL amigable (Fase B) |
| `excerpt` | text_long | No | Extracto para cards |
| `body` | text_long | No | Contenido completo |
| `answer_capsule` | text_long | No | Respuesta rápida AI |
| `featured_image` | entity_reference (file) | No | Imagen destacada |
| `category` | entity_reference (content_category) | No | Categoría |
| `publish_date` | datetime | No | Fecha de publicación |
| `status` | boolean | Sí | Publicado/borrador |
| `reading_time` | integer | No | Minutos de lectura |
| `author` | entity_reference (user) | Sí | Autor |
| `tenant_id` | **FALTA** | — | Aislamiento multi-tenant (Fase C) |

#### ContentCategory

| Campo | Tipo | Uso |
|-------|------|-----|
| `name` | string | Nombre visible |
| `slug` | string | URL amigable |
| `description` | text_long | Descripción para hero de categoría |
| `color` | string | Color hex para pills y estilos |
| `icon` | string | Nombre de icono |

### 7.3 Clases CSS del Blog (definidas en `_content-hub.scss`)

| Clase | Propósito | Tokens CSS usados |
|-------|-----------|-------------------|
| `.blog-page` | Wrapper principal | — |
| `.blog-hero` | Hero section con glassmorphism | `var(--ej-color-corporate)`, `var(--ej-space-*)` |
| `.blog-hero__title` | Título H1 | `var(--ej-font-size-4xl)`, `var(--ej-color-white)` |
| `.blog-hero__subtitle` | Subtítulo | `var(--ej-color-white)` con opacidad |
| `.blog-filters` | Contenedor de filtros | `var(--ej-space-*)` |
| `.blog-content` | Sección principal | `var(--ej-space-*)` |
| `.blog-grid` | Grid 2 columnas (main + sidebar) | CSS Grid, responsive |
| `.blog-grid__main` | Columna principal | `grid-column: 1 / -2` |
| `.blog-grid__sidebar` | Sidebar derecho | `grid-column: -2 / -1` |
| `.articles-grid` | Grid de article cards | CSS Grid auto-fit |
| `.article-card` | Card individual | Glassmorphism premium |
| `.article-card--featured` | Card destacada (primera) | Tamaño doble |
| `.sidebar-widget` | Widget genérico sidebar | `var(--ej-bg-surface)` |
| `.sidebar-widget--newsletter` | Widget newsletter | Acento `var(--ej-color-impulse)` |
| `.trending-list` | Lista numerada trending | Counter CSS |
| `.newsletter-form` | Formulario email | `var(--ej-color-primary)` |
| `.blog-empty` | Empty state | Centrado, padding generoso |
| `.category-hero` | Hero de categoría | `var(--category-color)` dinámica |
| `.category-content` | Grid de categoría | 3 columnas |
| `.category-empty` | Empty state categoría | Similar a `.blog-empty` |

### 7.4 Paleta de Colores Aplicada

| Variable CSS | Hex | Uso en Blog |
|--------------|-----|-------------|
| `--ej-color-corporate` | `#233D63` | Hero background, links |
| `--ej-color-impulse` | `#FF8C42` | Newsletter CTA, highlights |
| `--ej-color-innovation` | `#00A9A5` | Success states, badges |
| `--ej-color-primary` | `#4F46E5` | Botones, focus states |
| `--ej-color-neutral` | `#64748B` | Texto secundario, empty states |
| `--ej-bg-surface` | `#FFFFFF` | Cards, widgets |
| `--ej-bg-muted` | `#F8FAFC` | Backgrounds alternos |

### 7.5 Parciales Twig Reutilizados

| Parcial | Ubicación | Variables esperadas |
|---------|-----------|---------------------|
| `_header.html.twig` | `templates/partials/` | `site_name`, `logo`, `logged_in`, `theme_settings`, `avatar_nav` |
| `_footer.html.twig` | `templates/partials/` | `site_name`, `logo`, `theme_settings` |
| `_article-card.html.twig` | `templates/partials/` | `article` (object), `show_reading_time`, `variant` |
| `_category-filter.html.twig` | `templates/partials/` | `categories`, `active_category`, `show_all_link`, `all_url` |

### 7.6 Cache Strategy

| Componente | Estrategia | TTL |
|------------|------------|-----|
| Blog listing render | Cache tags: `content_article_list`, `content_category_list` | 300s |
| Article cards | Lazy loading images (`loading="lazy"`) | — |
| SCSS compilation | Archivos estáticos con hash de Drupal | Browser cache |

---

## 8. Archivos Modificados y de Referencia

### 8.1 Archivos modificados (Fase A)

| Archivo | Acción | Líneas afectadas |
|---------|--------|-----------------|
| `web/themes/.../templates/page--blog.html.twig` | ELIMINADO | Completo (74 líneas) |
| `web/themes/.../ecosistema_jaraba_theme.theme` | EDITADO | ~3560-3575 (blog suggestion eliminada) |
| `web/modules/.../templates/content-hub-blog-index.html.twig` | REESCRITO | Completo (130 líneas) |
| `web/modules/.../templates/content-hub-category-page.html.twig` | REESCRITO | Completo (60 líneas) |
| `web/modules/.../src/Controller/BlogController.php` | EDITADO | +30 líneas (DI, campos, helper) |
| `web/modules/.../jaraba_content_hub.module` | EDITADO | +4 líneas (body class) |
| `web/themes/.../css/ecosistema-jaraba-theme.css` | RECOMPILADO | Output de SCSS |

### 8.2 Archivos de referencia (solo lectura)

| Archivo | Propósito |
|---------|-----------|
| `scss/_content-hub.scss` | Definición de todas las clases CSS del blog |
| `templates/partials/_article-card.html.twig` | Parcial de tarjeta de artículo |
| `templates/partials/_category-filter.html.twig` | Parcial de filtro por categorías |
| `templates/page--content-hub.html.twig` | Page wrapper Zero Region Policy |
| `.agent/workflows/frontend-page-pattern.md` | Patrón de página frontend |
| `.agent/workflows/scss-estilos.md` | Directriz de compilación SCSS |
| `docs/tecnicos/aprendizajes/2026-02-24_icon_system_zero_chinchetas.md` | Sistema de iconos |

---

## 9. Verificación y Checklist

### 9.1 Verificación Fase A (completar manualmente)

- [ ] Navegar a `https://jaraba-saas.lndo.site/blog`
- [ ] Verificar: un solo header, un solo footer (no duplicados)
- [ ] Verificar: layout full-width sin sidebar admin
- [ ] Verificar: Hero section visible con título y subtítulo
- [ ] Verificar: Cards de artículos con imagen, categoría, título, excerpt
- [ ] Verificar: Sidebar con trending y newsletter
- [ ] Verificar: Responsive en móvil (F12 → toggle device)
- [ ] Verificar: Navegar a una categoría, verificar back link funciona
- [ ] Verificar: Empty state si no hay artículos (icono + mensaje)
- [ ] Verificar: No hay URLs hardcodeadas en el HTML generado (View Source)
- [ ] Verificar: Body tiene clases `page-content-hub page--clean-layout page-blog`

### 9.2 Checklist de Directrices Cumplidas (Fase A)

- [x] Zero Region Policy (`{{ clean_content }}`, no `{{ page.* }}`)
- [x] i18n: Todos los textos con `{% trans %}` / `$this->t()`
- [x] URLs: `path('route')` en vez de strings hardcodeados
- [x] Iconos: `jaraba_icon()` en vez de SVG inline
- [x] Parciales: `{% include %}` para elementos reutilizables
- [x] SCSS: Tokens `var(--ej-*)`, compilación Dart Sass
- [x] Body classes: via `hook_preprocess_html()` (no en template)
- [x] Template suggestions: via hook del módulo
- [x] Cache tags: `content_article_list`, `content_category_list`
- [x] Accesibilidad: `aria-label` en inputs, `role` en landmarks
- [x] No `$ej-*` locales en módulos satélite (Golden Rule)
- [x] Mobile-first: CSS Grid responsive en `_content-hub.scss`

### 9.3 Checklist Pendiente (Fases B y C)

- [x] B1: Paginación server-side con sliding window (COMPLETADA sesión 2)
- [ ] B2: OG tags + canonical URL en listing
- [ ] B3: Schema JSON-LD (Blog, BlogPosting, BreadcrumbList)
- [ ] B4: URLs con slug en vez de entity ID
- [ ] B5: Search UI con autocomplete
- [ ] B6: RSS feed en `/blog/rss.xml`
- [ ] C1: Campo `tenant_id` en ContentArticle
- [ ] C2: Filtrado por tenant en BlogController
- [ ] C3: Feature en `FEATURE_ADDON_MAP` + `SaasPlan`
- [ ] C4: Quotas de artículos por plan en `QuotaManagerService`
