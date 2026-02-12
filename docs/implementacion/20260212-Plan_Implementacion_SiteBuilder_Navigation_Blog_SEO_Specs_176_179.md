# Plan de Implementación: Site Builder + Navegación Global + Blog Nativo + SEO/GEO/IA

> **Especificaciones Técnicas**: Docs 176, 177, 178, 179 (Serie 20260127)
> **Fecha**: 2026-02-12
> **Versión**: 1.0.0
> **Autor**: Equipo Técnico JarabaImpactPlatformSaaS
> **Estado**: Borrador para aprobación

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual de Implementación](#2-estado-actual-de-implementación)
3. [Análisis de Gaps por Especificación](#3-análisis-de-gaps-por-especificación)
4. [Tabla de Correspondencia: Especificaciones ↔ Directrices](#4-tabla-de-correspondencia-especificaciones--directrices)
5. [Fases de Implementación](#5-fases-de-implementación)
   - [Fase 1: Sistema de Navegación Global (Doc 177)](#fase-1-sistema-de-navegación-global-doc-177)
   - [Fase 2: Cierre de Gaps Site Structure Manager (Doc 176)](#fase-2-cierre-de-gaps-site-structure-manager-doc-176)
   - [Fase 3: Blog System Nativo (Doc 178)](#fase-3-blog-system-nativo-doc-178)
   - [Fase 4: SEO/GEO + Integración IA (Doc 179)](#fase-4-seogeo--integración-ia-doc-179)
6. [Arquitectura de Entidades](#6-arquitectura-de-entidades)
7. [Arquitectura de Servicios](#7-arquitectura-de-servicios)
8. [API REST: Catálogo Completo de Endpoints](#8-api-rest-catálogo-completo-de-endpoints)
9. [Templates Twig y Parciales](#9-templates-twig-y-parciales)
10. [Arquitectura SCSS y Design Tokens](#10-arquitectura-scss-y-design-tokens)
11. [Checklist de Cumplimiento de Directrices](#11-checklist-de-cumplimiento-de-directrices)
12. [Plan de Testing](#12-plan-de-testing)
13. [Estimación de Esfuerzo](#13-estimación-de-esfuerzo)
14. [Dependencias y Riesgos](#14-dependencias-y-riesgos)
15. [Criterios de Aceptación](#15-criterios-de-aceptación)

---

## 1. Resumen Ejecutivo

### 1.1 Alcance

Este plan cubre la implementación completa de cuatro especificaciones técnicas interrelacionadas que conforman el **sistema de construcción y gestión de sitios web** del SaaS:

| Doc | Especificación | Módulo Principal | Horas Spec |
|-----|---------------|------------------|------------|
| **176** | Site Structure Manager v1 | `jaraba_site_builder` | 40-50h |
| **177** | Global Navigation System v1 | `jaraba_site_builder` (extensión) | 50-60h |
| **178** | Blog System Nativo v1 | `jaraba_blog` (nuevo) | 60-80h |
| **179** | SEO/GEO + IA Integration v1 | `jaraba_site_builder` + `jaraba_blog` | 50-60h |

### 1.2 Objetivo de Negocio

Proporcionar a cada tenant del SaaS un **sistema completo de gestión de sitio web** que incluya:

- **Estructura de páginas** jerárquica con drag & drop (Doc 176)
- **Navegación personalizable** con header, footer y menús multi-nivel (Doc 177)
- **Blog nativo** con categorías, tags, autores y RSS (Doc 178)
- **Optimización SEO/GEO** con Schema.org, hreflang y asistente IA (Doc 179)

### 1.3 Principio Rector

> **Todo configurable desde la UI de Drupal, sin tocar código.**
> Los tenants configuran su sitio completo (estructura, navegación, blog, SEO) a través de interfaces limpias, modales slide-panel y APIs REST, sin acceder nunca al tema de administración de Drupal.

### 1.4 Horas Restantes Estimadas

| Doc | Total Spec | Implementado | Restante |
|-----|-----------|-------------|---------|
| 176 | 40-50h | ~60% | ~16-20h |
| 177 | 50-60h | ~25% | ~38-45h |
| 178 | 60-80h | ~30% (módulo distinto) | ~50-60h |
| 179 | 50-60h | ~20% | ~40-48h |
| **Total** | **200-250h** | — | **~144-173h** |

---

## 2. Estado Actual de Implementación

### 2.1 Doc 176 — Site Structure Manager (~60%)

**Módulo**: `web/modules/custom/jaraba_site_builder/`

**Entidades implementadas** (4/4):

| Entidad | Archivo | Estado | Campos | Handlers |
|---------|---------|--------|--------|----------|
| `SiteConfig` | `src/Entity/SiteConfig.php` | ✅ Completo | 30+ campos (branding, SEO, Canvas v2 header/footer, social, legal) | list_builder, access |
| `SitePageTree` | `src/Entity/SitePageTree.php` | ✅ Completo | 18 campos (tenant_id, page_id, parent_id, weight, depth, path, nav_*, status) | list_builder, access |
| `SiteRedirect` | `src/Entity/SiteRedirect.php` | ✅ Completo | 11 campos (source_path, destination_path, redirect_type, hit_count, expires_at) | list_builder, access, form |
| `SiteUrlHistory` | `src/Entity/SiteUrlHistory.php` | ✅ Completo | 9 campos (page_id, old_path, new_path, change_type, changed_by) | list_builder, access |

**Servicios implementados** (7/7 de los especificados):

| Servicio | Estado | Función |
|----------|--------|---------|
| `SiteStructureService` | ✅ | Gestión del árbol de páginas |
| `RedirectService` | ✅ | CRUD de redirects |
| `SitemapGeneratorService` | ✅ | Generación XML sitemap |
| `SiteAnalyticsService` | ✅ | Estadísticas del dashboard |
| `SeoAuditorService` | ✅ | Auditoría SEO por página |
| `HeaderVariantService` | ✅ | Variantes de cabecera Canvas v2 |
| `FooterVariantService` | ✅ | Variantes de pie Canvas v2 |

**Controllers** (4):

| Controller | Rutas | Estado |
|-----------|-------|--------|
| `SiteStructureApiController` | API REST: tree, reorder, pages | ✅ |
| `SiteConfigApiController` | API REST: config, logo, variants | ✅ |
| `SiteTreePageController` | Frontend: dashboard, tree, config | ✅ |
| `SitemapController` | `/sitemap.xml` público | ✅ |

**Gaps Doc 176** (lo que falta):

| Gap | Prioridad | Horas Est. |
|-----|-----------|------------|
| Bulk import/export de redirects (CSV) | P1 | 4h |
| Mapa visual del sitemap (árbol visual en frontend) | P2 | 4h |
| API de breadcrumbs para el frontend | P1 | 3h |
| Enforcement de límites por plan (pages, depth, redirects) | P1 | 4h |
| Integración con entity route_provider + Field UI completo en las 4 entidades | P0 | 5h |

### 2.2 Doc 177 — Global Navigation System (~25%)

**Estado actual**: La configuración de header/footer existe embebida en `SiteConfig` con campos `header_type`, `header_sticky`, `header_transparent`, `header_cta_*`, `footer_type`, `footer_columns`, etc. También existen `HeaderVariantService` y `FooterVariantService`.

**Lo que FALTA** (la mayor parte del spec):

| Componente | Estado | Descripción |
|-----------|--------|-------------|
| Entidad `SiteMenu` | ❌ No existe | Definiciones de menús por tenant |
| Entidad `SiteMenuItem` | ❌ No existe | Items jerárquicos con mega menu, badges, iconos |
| Entidad `SiteHeaderConfig` (separada) | ⚠️ Embebida en SiteConfig | Spec pide entidad separada con 30+ campos |
| Entidad `SiteFooterConfig` (separada) | ⚠️ Embebida en SiteConfig | Spec pide entidad separada con 25+ campos |
| API CRUD de menús | ❌ | 9 endpoints: list, create, get, delete, tree, add item, update item, delete item, reorder |
| API Header/Footer | ⚠️ Parcial | GET/PUT header, GET/PUT footer, upload logos |
| Template `header.html.twig` | ⚠️ Parcial | Variantes existen pero no con macro-sistema Twig del spec |
| Template mobile menu | ❌ | Menú off-canvas con submenús, CTA |
| Template mega menu | ❌ | Grid de columnas con iconos y badges |
| SCSS header/navigation | ⚠️ Parcial | Existe base pero falta el sistema completo (~400 líneas) |
| SCSS footer | ⚠️ Parcial | Existe base pero falta sistema de columnas, newsletter |
| SCSS mobile menu | ❌ | Off-canvas panel con animaciones |
| Top bar configurable | ❌ | Banner superior con color/texto personalizables |
| Menú drag & drop builder | ❌ | Interfaz visual para construir menús |

### 2.3 Doc 178 — Blog System Nativo (~30% en módulo distinto)

**Estado actual**: El módulo `jaraba_blog` **NO existe**. Existe funcionalidad parcial en `jaraba_content_hub` con entidades como `content_article`, pero con una arquitectura diferente a la especificada.

**Lo que FALTA** (prácticamente todo):

| Componente | Estado | Descripción |
|-----------|--------|-------------|
| Módulo `jaraba_blog` | ❌ | Módulo completo nuevo |
| Entidad `BlogConfig` | ❌ | Configuración del blog por tenant |
| Entidad `BlogPost` | ❌ | Posts con slug, excerpt, SEO, scheduling |
| Entidad `BlogCategory` | ❌ | Categorías jerárquicas con colores |
| Entidad `BlogTag` | ❌ | Tags con contador |
| Entidad `BlogAuthor` | ❌ | Perfiles extendidos de autor |
| Relaciones M2M (post↔category, post↔tag) | ❌ | Via entity_reference campo múltiple |
| Controller `BlogApiController` | ❌ | 14 endpoints REST de posts |
| Controller `BlogCategoryApiController` | ❌ | 5 endpoints de categorías |
| Controller `BlogTagApiController` | ❌ | 5 endpoints de tags |
| `RssFeedGenerator` service | ❌ | RSS 2.0 feed |
| Template `blog-list.html.twig` | ❌ | Listado con filtros, grid, paginación |
| Template `blog-post.html.twig` | ❌ | Post individual con 4 layouts |
| Template `page--blog.html.twig` | ❌ | Página frontend limpia |
| SCSS blog (~40 clases) | ❌ | Sistema completo de estilos |
| Frontend page `/blog` | ❌ | Página limpia sin regiones Drupal |

### 2.4 Doc 179 — SEO/GEO + IA Integration (~20%)

**Estado actual**: Existe `SeoAuditorService` en `jaraba_site_builder` con auditoría básica por página.

**Lo que FALTA**:

| Componente | Estado | Descripción |
|-----------|--------|-------------|
| `SchemaOrgGlobalService` | ❌ | WebSite, Organization, LocalBusiness, BreadcrumbList schema |
| `BlogSeoService` | ❌ | BlogPosting schema con Person, Publisher |
| `AISiteBuilderService` | ❌ | Sugerencia de estructura IA, generación de posts, optimización SEO |
| `MultiLanguageSeoService` | ❌ | Hreflang tags, sitemap multilingüe |
| `CoreWebVitalsService` | ❌ | Critical CSS, preload hints, lazy loading, audit CWV |
| API endpoints Schema.org | ❌ | 3 endpoints |
| API endpoints IA | ❌ | 7 endpoints |
| API endpoints CWV | ❌ | 2 endpoints |
| API endpoints Hreflang | ❌ | 2 endpoints |

---

## 3. Análisis de Gaps por Especificación

### 3.1 Mapa de Dependencias entre Especificaciones

```
┌─────────────────────────────────────────────────────┐
│                    Doc 179                            │
│             SEO/GEO + IA Integration                 │
│  (SchemaOrg, BlogSEO, AI, Hreflang, CoreWebVitals)  │
│                                                       │
│   Depende de: Doc 176, 177, 178, Doc 162, 166       │
└───────────────────┬───────────────────┬──────────────┘
                    │                   │
        ┌───────────▼──────┐   ┌────────▼──────────┐
        │     Doc 178       │   │     Doc 176        │
        │  Blog Nativo      │   │  Site Structure    │
        │ (jaraba_blog)     │   │  (site_builder)    │
        │                   │   │                    │
        │ Depende de:       │   │ Depende de:        │
        │  Doc 176, 177     │   │  Doc 162, 177      │
        └───────┬───────────┘   └────────┬───────────┘
                │                        │
                └──────────┬─────────────┘
                           │
                ┌──────────▼──────────┐
                │      Doc 177        │
                │ Navigation System   │
                │ (SiteMenu entities) │
                │                     │
                │ Depende de:         │
                │  Doc 176, 162, 166  │
                └─────────────────────┘
```

**Orden lógico de implementación**: Doc 177 → Doc 176 gaps → Doc 178 → Doc 179

### 3.2 Justificación del Orden

1. **Doc 177 primero** porque `SiteMenu` y `SiteMenuItem` son prerrequisitos para la navegación que usarán el blog (Doc 178), el header/footer (que sirven para las templates parciales de todas las páginas frontend), y los breadcrumbs (Doc 176 gap).

2. **Doc 176 gaps segundo** porque los breadcrumbs y los límites por plan son necesarios antes de construir el blog (para que el blog respete límites y los breadcrumbs funcionen).

3. **Doc 178 tercero** porque el Blog es prerrequisito del `BlogSeoService` y el `AISiteBuilderService::generateBlogPost()` del Doc 179.

4. **Doc 179 último** porque integra TODOS los anteriores (schema para site + blog, IA para structure + blog, CWV para pages + posts).

---

## 4. Tabla de Correspondencia: Especificaciones ↔ Directrices

Esta tabla mapea cada requisito funcional de las 4 especificaciones con las directrices y workflows del proyecto que deben cumplirse.

### 4.1 Directrices de Arquitectura

| Directriz | Referencia | Cumplimiento en este Plan |
|-----------|-----------|--------------------------|
| **Content Entities con Field UI y Views** | `00_DIRECTRICES_PROYECTO.md` §5.1-5.6 | Todas las entidades nuevas (SiteMenu, SiteMenuItem, SiteHeaderConfig, SiteFooterConfig, BlogPost, BlogCategory, BlogTag, BlogAuthor, BlogConfig) serán Content Entities con handlers `list_builder`, `views_data`, `form`, `access`, `route_provider` |
| **Navegación admin en `/admin/structure` y `/admin/content`** | `drupal-custom-modules.md` | Cada entidad tendrá: `links.menu.yml` (entrada en /admin/structure), `links.task.yml` (tabs), `links.action.yml` (botones "Añadir") |
| **4 archivos YAML obligatorios** | `drupal-custom-modules.md` | `routing.yml`, `links.menu.yml`, `links.task.yml`, `links.action.yml` para cada módulo |
| **No hardcodear configuraciones** | `00_DIRECTRICES_PROYECTO.md` §5.3 | Límites de plan, colores, textos: todo desde Content Entities o configuración UI |
| **Aislamiento multi-tenant** | `00_DIRECTRICES_PROYECTO.md` §3 | Toda entidad tiene campo `tenant_id` (entity_reference a `group`). Toda query filtra por tenant |

### 4.2 Directrices de Frontend

| Directriz | Referencia | Cumplimiento en este Plan |
|-----------|-----------|--------------------------|
| **Plantillas Twig limpias sin regiones Drupal** | `00_DIRECTRICES_PROYECTO.md` §2.2.2 | `page--blog.html.twig`, `page--site-builder.html.twig`: HTML completo con `{% include %}` de parciales |
| **Parciales reutilizables con `{% include %}`** | `frontend-page-pattern.md` | `_header.html.twig`, `_footer.html.twig`, `_blog-card.html.twig`, `_breadcrumbs.html.twig`: parciales compartidos entre páginas |
| **Body classes via `hook_preprocess_html()`** | `00_DIRECTRICES_PROYECTO.md` §2.2.2 (Lección Crítica) | **NUNCA** `attributes.addClass()` en templates. Siempre hook en `.theme` para `page-blog`, `page-site-builder`, etc. |
| **Full-width layout, mobile-first** | `frontend-page-pattern.md` | Todas las páginas frontend sin sidebar, 100% ancho, breakpoints mobile → tablet → desktop |
| **CRUD en slide-panel modales** | `slide-panel-modales.md` | Crear/editar posts, categorías, tags, menús: todo en slide-panel off-canvas desde la derecha |
| **Tenant sin acceso a admin theme** | Directriz usuario | Todas las interfaces de gestión en rutas frontend (`/blog/manage`, `/site-builder`) |
| **Variables de tema configurables desde UI** | `scss-estilos.md` | Parciales de header/footer usan `theme_settings` pasadas desde `hook_preprocess_page()` |

### 4.3 Directrices de SCSS/Theming

| Directriz | Referencia | Cumplimiento en este Plan |
|-----------|-----------|--------------------------|
| **Federated Design Tokens SSOT** | `2026-02-05_arquitectura_theming_saas_master.md` | Módulos SOLO consumen `var(--ej-*)`. NUNCA definen variables SCSS propias para colores |
| **Dart Sass moderno con `@use`** | `scss-estilos.md` §17 | Cada parcial SCSS declara `@use 'sass:color'; @use 'variables' as *;`. No `@import` |
| **`color.scale()` en vez de `darken()`/`lighten()`** | `scss-estilos.md` §19 | Todas las variaciones de color usan `color.scale()` |
| **CSS custom properties inyectables** | `scss-estilos.md` | Colores: `var(--ej-color-primary, #{$fallback})`. Personalizable por tenant en runtime |
| **Compilación NVM + Dart Sass** | `scss-estilos.md` §Compilación | `npm run build` desde WSL, `lando drush cr` desde Docker |
| **Parciales SCSS con prefijo `_`** | `scss-estilos.md` | `_blog-list.scss`, `_blog-post.scss`, `_navigation.scss`, `_mega-menu.scss`, `_mobile-menu.scss` |
| **Paleta de marca Jaraba** | `scss-estilos.md` §Paleta | corporate (#233D63), impulse (#FF8C42), innovation (#00A9A5), agro (#556B2F) |
| **Sistema de iconos SVG** | `scss-estilos.md` §Iconografía | `jaraba_icon('category', 'name', {color, size, variant})` para todos los iconos |

### 4.4 Directrices de i18n

| Directriz | Referencia | Cumplimiento en este Plan |
|-----------|-----------|--------------------------|
| **Textos UI siempre traducibles** | `i18n-traducciones.md` | `$this->t()` en controllers, `{% trans %}` en Twig, `Drupal.t()` en JS |
| **Texto base en español** | `i18n-traducciones.md` §1 | `{% trans %}Publicar artículo{% endtrans %}`, no `{% trans %}Publish article{% endtrans %}` |
| **Abreviaturas traducidas** | `i18n-traducciones.md` §2 | `{% trans %}min de lectura{% endtrans %}`, no `min read` |

### 4.5 Directrices de IA

| Directriz | Referencia | Cumplimiento en este Plan |
|-----------|-----------|--------------------------|
| **Siempre `@ai.provider`** | `ai-integration.md` | `AISiteBuilderService` inyecta `@ai.provider`, NUNCA HTTP directo |
| **Failover multi-proveedor** | `ai-integration.md` §5 | Anthropic → OpenAI → fallback graceful |
| **Claves en módulo Key** | `ai-integration.md` §2 | API keys en `/admin/config/system/keys` |
| **Rate limiting** | `00_DIRECTRICES_PROYECTO.md` §4.5 | 50 req/hora para IA por tenant |
| **Sanitización de prompts** | `00_DIRECTRICES_PROYECTO.md` §4.5 | Whitelist para datos interpolados en system prompts |

### 4.6 Directrices de Seguridad

| Directriz | Referencia | Cumplimiento en este Plan |
|-----------|-----------|--------------------------|
| **Autenticación en APIs** | `00_DIRECTRICES_PROYECTO.md` §4.6 | `_user_is_logged_in: 'TRUE'` en todas las rutas API excepto RSS y sitemap público |
| **Parámetros de ruta con regex** | `00_DIRECTRICES_PROYECTO.md` §4.6 | `slug: '[a-z0-9\-]+'`, `id: '\d+'` |
| **HMAC en webhooks** | `00_DIRECTRICES_PROYECTO.md` §4.6 | Verificación de firma en cualquier endpoint público |
| **Mensajes de error genéricos** | `00_DIRECTRICES_PROYECTO.md` §4.6 | Log detallado + respuesta genérica al frontend |

---

## 5. Fases de Implementación

---

### Fase 1: Sistema de Navegación Global (Doc 177)

> **Estimación**: 38-45 horas
> **Prioridad**: P0 (bloquea Fases 2, 3, 4)
> **Módulo**: `jaraba_site_builder` (extensión)

#### 1.1 Decisión Arquitectónica: Entidades Separadas vs. Embebidas

**Problema**: La spec 177 define `site_header_config` y `site_footer_config` como entidades separadas, pero la implementación actual tiene esos campos embebidos en `SiteConfig`.

**Decisión**: **Crear entidades separadas** `SiteHeaderConfig` y `SiteFooterConfig` conforme a la spec, por las siguientes razones:

1. **Separación de responsabilidades**: Cada entidad tiene 25-30 campos propios. Sobrecargar `SiteConfig` (que ya tiene 30+ campos) lo haría inmanejable.
2. **Field UI**: Con entidades separadas, se pueden añadir campos custom al header o footer independientemente.
3. **Cacheo granular**: Invalidar cache del header no requiere invalidar todo SiteConfig.
4. **Views**: Listados y filtros independientes por tipo de configuración.

**Migración**: Los campos `header_*` y `footer_*` de `SiteConfig` se mantienen temporalmente como deprecated. Un script de migración los volcará a las nuevas entidades. Una vez completada la migración, se eliminarán de `SiteConfig`.

#### 1.2 Entidades a Crear

##### 1.2.1 Entidad `SiteMenu`

```
Módulo: jaraba_site_builder
Tabla: site_menu
Tipo: Content Entity con Field UI

Handlers:
  list_builder: Drupal\jaraba_site_builder\SiteMenuListBuilder
  views_data: Drupal\views\EntityViewsData
  form:
    default: Drupal\jaraba_site_builder\Form\SiteMenuForm
    add: Drupal\jaraba_site_builder\Form\SiteMenuForm
    edit: Drupal\jaraba_site_builder\Form\SiteMenuForm
    delete: Drupal\Core\Entity\ContentEntityDeleteForm
  access: Drupal\jaraba_site_builder\SiteMenuAccessControlHandler
  route_provider:
    html: Drupal\Core\Entity\Routing\AdminHtmlRouteProvider

Links:
  collection: /admin/structure/site-menus
  add-form: /admin/structure/site-menus/add
  canonical: /admin/structure/site-menus/{site_menu}
  edit-form: /admin/structure/site-menus/{site_menu}/edit
  delete-form: /admin/structure/site-menus/{site_menu}/delete

Campos base:
  - tenant_id (entity_reference → group) REQUIRED
  - machine_name (string, max 100)
  - label (string, max 255) REQUIRED
  - description (string_long)
  - created (created)
  - changed (changed)

Índice UNIQUE: (tenant_id, machine_name)

field_ui_base_route: entity.site_menu.collection
```

##### 1.2.2 Entidad `SiteMenuItem`

```
Módulo: jaraba_site_builder
Tabla: site_menu_item
Tipo: Content Entity con Field UI

Handlers:
  list_builder: Drupal\jaraba_site_builder\SiteMenuItemListBuilder
  views_data: Drupal\views\EntityViewsData
  form:
    default: Drupal\jaraba_site_builder\Form\SiteMenuItemForm
    add: Drupal\jaraba_site_builder\Form\SiteMenuItemForm
    edit: Drupal\jaraba_site_builder\Form\SiteMenuItemForm
    delete: Drupal\Core\Entity\ContentEntityDeleteForm
  access: Drupal\jaraba_site_builder\SiteMenuItemAccessControlHandler
  route_provider:
    html: Drupal\Core\Entity\Routing\AdminHtmlRouteProvider

Links:
  collection: /admin/structure/site-menu-items
  add-form: /admin/structure/site-menu-items/add
  canonical: /admin/structure/site-menu-items/{site_menu_item}
  edit-form: /admin/structure/site-menu-items/{site_menu_item}/edit
  delete-form: /admin/structure/site-menu-items/{site_menu_item}/delete

Campos base:
  - menu_id (entity_reference → site_menu) REQUIRED
  - parent_id (entity_reference → site_menu_item, self-referencing)
  - title (string, max 255) REQUIRED
  - url (string, max 500) — URL manual
  - page_id (entity_reference → page_content) — Alternativa a URL
  - item_type (list_string): link, page, dropdown, mega_column, divider, heading
  - icon (string, max 50) — Nombre de icono Lucide/jaraba_icon
  - badge_text (string, max 50) — "Nuevo", "Beta"
  - badge_color (string, max 7) — Hex color del badge
  - highlight (boolean) — Destacar en navegación
  - mega_content (string_long) — JSON con contenido mega menu
  - open_in_new_tab (boolean)
  - is_enabled (boolean, default TRUE)
  - weight (integer, default 0)
  - depth (integer, default 0)
  - created (created)

Índice: (menu_id, parent_id, weight)

field_ui_base_route: entity.site_menu_item.collection
```

##### 1.2.3 Entidad `SiteHeaderConfig`

```
Módulo: jaraba_site_builder
Tabla: site_header_config
Tipo: Content Entity con Field UI

Handlers: (mismo patrón que SiteMenu)

Links:
  collection: /admin/structure/site-header-config
  add-form: /admin/structure/site-header-config/add
  canonical: /admin/structure/site-header-config/{site_header_config}
  edit-form: /admin/structure/site-header-config/{site_header_config}/edit
  delete-form: /admin/structure/site-header-config/{site_header_config}/delete

Campos base (30+ conforme a spec 177):
  - tenant_id (entity_reference → group) REQUIRED, UNIQUE
  - header_type (list_string): standard, centered, minimal, mega, transparent
  - logo_id (entity_reference → file)
  - logo_alt (string, max 255)
  - logo_width (integer, default 150)
  - logo_mobile_id (entity_reference → file)
  - is_sticky (boolean, default TRUE)
  - sticky_offset (integer, default 0)
  - transparent_on_hero (boolean)
  - hide_on_scroll_down (boolean)
  - main_menu_position (list_string): left, center, right
  - main_menu_id (entity_reference → site_menu)
  - show_cta (boolean, default TRUE)
  - cta_text (string, max 100)
  - cta_url (string, max 500)
  - cta_style (list_string): primary, secondary, outline, ghost
  - cta_icon (string, max 50) — Nombre de icono
  - show_search (boolean)
  - show_language_switcher (boolean)
  - show_user_menu (boolean)
  - show_phone (boolean)
  - show_email (boolean)
  - show_topbar (boolean)
  - topbar_content (string_long) — HTML/texto
  - topbar_bg_color (string, max 7, default '#1E3A5F')
  - topbar_text_color (string, max 7, default '#FFFFFF')
  - bg_color (string, max 7, default '#FFFFFF')
  - text_color (string, max 7, default '#1E293B')
  - height_desktop (integer, default 80)
  - height_mobile (integer, default 64)
  - shadow (list_string): none, sm, md, lg
  - created (created)
  - changed (changed)
```

##### 1.2.4 Entidad `SiteFooterConfig`

```
Módulo: jaraba_site_builder
Tabla: site_footer_config
Tipo: Content Entity con Field UI

Campos base (25+ conforme a spec 177):
  - tenant_id (entity_reference → group) REQUIRED, UNIQUE
  - footer_type (list_string): simple, columns, mega, minimal, cta
  - logo_id (entity_reference → file)
  - show_logo (boolean, default TRUE)
  - description (string_long)
  - columns_config (string_long) — JSON multi-columna
  - show_social (boolean, default TRUE)
  - social_position (list_string): top, bottom, column
  - show_newsletter (boolean)
  - newsletter_title (string, max 255)
  - newsletter_placeholder (string, max 100)
  - newsletter_cta (string, max 50)
  - cta_title (string, max 255)
  - cta_subtitle (string_long)
  - cta_button_text (string, max 100)
  - cta_button_url (string, max 500)
  - copyright_text (string, max 500) — Soporta {year} token
  - show_legal_links (boolean, default TRUE)
  - bg_color (string, max 7, default '#1E293B')
  - text_color (string, max 7, default '#94A3B8')
  - accent_color (string, max 7, default '#3B82F6')
  - created (created)
  - changed (changed)
```

#### 1.3 Servicios a Crear / Extender

| Servicio | Tipo | Dependencias | Métodos Principales |
|----------|------|-------------|-------------------|
| `MenuService` | **Nuevo** | entity_type.manager, database, tenant_context | `getMenuTree()`, `addItem()`, `reorderItems()`, `deleteItem()`, `buildFlatList()` |
| `NavigationRenderService` | **Nuevo** | MenuService, entity_type.manager, file_url_generator | `renderHeader()`, `renderFooter()`, `renderMobileMenu()`, `renderBreadcrumbs()` |
| `HeaderVariantService` | **Extender** | entity_type.manager, tenant_context, renderer | Refactorizar para usar SiteHeaderConfig entity |
| `FooterVariantService` | **Extender** | entity_type.manager, tenant_context, renderer | Refactorizar para usar SiteFooterConfig entity |

#### 1.4 Endpoints API (Doc 177)

| Método | Ruta | Función | Autenticación |
|--------|------|---------|---------------|
| GET | `/api/v1/site/header` | Configuración de header del tenant | `_user_is_logged_in` |
| PUT | `/api/v1/site/header` | Actualizar header | `_permission: 'edit site config'` |
| POST | `/api/v1/site/header/logo` | Subir logo header | `_permission: 'edit site config'` |
| DELETE | `/api/v1/site/header/logo` | Eliminar logo | `_permission: 'edit site config'` |
| GET | `/api/v1/site/footer` | Configuración de footer | `_user_is_logged_in` |
| PUT | `/api/v1/site/footer` | Actualizar footer | `_permission: 'edit site config'` |
| POST | `/api/v1/site/footer/logo` | Subir logo footer | `_permission: 'edit site config'` |
| GET | `/api/v1/site/menus` | Listar menús del tenant | `_user_is_logged_in` |
| POST | `/api/v1/site/menus` | Crear menú | `_permission: 'administer site structure'` |
| GET | `/api/v1/site/menus/{id}` | Detalle de menú | `_user_is_logged_in` |
| DELETE | `/api/v1/site/menus/{id}` | Eliminar menú | `_permission: 'administer site structure'` |
| GET | `/api/v1/site/menus/{id}/tree` | Árbol jerárquico de items | `_user_is_logged_in` |
| POST | `/api/v1/site/menus/{id}/items` | Añadir item a menú | `_permission: 'administer site structure'` |
| PATCH | `/api/v1/site/menus/{id}/items/{itemId}` | Actualizar item | `_permission: 'administer site structure'` |
| DELETE | `/api/v1/site/menus/{id}/items/{itemId}` | Eliminar item | `_permission: 'administer site structure'` |
| POST | `/api/v1/site/menus/{id}/reorder` | Reordenar items (drag & drop) | `_permission: 'administer site structure'` |

#### 1.5 Templates Twig (Doc 177)

##### Parciales reutilizables (en el tema):

| Parcial | Ubicación | Variables | Usado por |
|---------|-----------|-----------|-----------|
| `_jaraba-header.html.twig` | `templates/partials/` | `header_config`, `main_menu_tree`, `site_config` | Todas las page--*.html.twig |
| `_jaraba-footer.html.twig` | `templates/partials/` | `footer_config`, `footer_menus`, `site_config`, `social_links` | Todas las page--*.html.twig |
| `_jaraba-mobile-menu.html.twig` | `templates/partials/` | `main_menu_tree`, `header_config` | `_jaraba-header.html.twig` |
| `_jaraba-mega-menu.html.twig` | `templates/partials/` | `mega_items`, `columns` | `_jaraba-header.html.twig` |
| `_jaraba-topbar.html.twig` | `templates/partials/` | `topbar_content`, `bg_color`, `text_color` | `_jaraba-header.html.twig` |
| `_jaraba-breadcrumbs.html.twig` | `templates/partials/` | `breadcrumbs[]`, `current_title` | Páginas interiores |

**Patrón de inyección de datos**: Todas las variables llegan a los parciales a través de `hook_preprocess_page()` en el `.theme`, que lee las entidades SiteHeaderConfig, SiteFooterConfig y los menús del tenant actual, y los pasa como variables Twig. De este modo, **los parciales solo consumen variables**, nunca acceden a servicios ni entidades.

```php
// ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_preprocess_page(&$variables) {
  $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
  $tenant_id = $tenant_context->getCurrentTenantId();

  if ($tenant_id) {
    $nav_service = \Drupal::service('jaraba_site_builder.navigation_render');
    $variables['header_config'] = $nav_service->getHeaderConfig($tenant_id);
    $variables['footer_config'] = $nav_service->getFooterConfig($tenant_id);
    $variables['main_menu_tree'] = $nav_service->getMainMenuTree($tenant_id);
    $variables['footer_menus'] = $nav_service->getFooterMenus($tenant_id);
  }
}
```

#### 1.6 SCSS (Doc 177)

Archivos a crear en `jaraba_site_builder/scss/`:

| Parcial SCSS | Líneas Est. | Contenido |
|-------------|-------------|-----------|
| `_navigation.scss` | ~150 | `.jaraba-nav`, `.jaraba-nav__item`, `.jaraba-nav__link`, dropdown base |
| `_header.scss` | ~200 | `.jaraba-header` (5 variantes: standard, centered, minimal, mega, transparent), sticky, scroll behaviors |
| `_footer.scss` | ~200 | `.jaraba-footer` (5 variantes: simple, columns, mega, minimal, cta), grid columnas |
| `_mega-menu.scss` | ~100 | `.jaraba-mega-menu`, grid responsive, columnas, iconos, badges |
| `_mobile-menu.scss` | ~150 | `.jaraba-mobile-menu`, off-canvas panel, animaciones, submenú accordion |
| `_topbar.scss` | ~40 | `.jaraba-topbar`, colores custom |
| `_breadcrumbs.scss` | ~50 | `.jaraba-breadcrumbs`, schema.org markup, responsive |

**Reglas SCSS obligatorias** (conforme a `scss-estilos.md`):

```scss
// Inicio de CADA parcial:
@use 'sass:color';
@use 'variables' as *;

// Colores: SOLO custom properties
.jaraba-header {
  background: var(--ej-color-bg, #FFFFFF);
  color: var(--ej-text-primary, #1E293B);
}

// Variaciones: color.scale() NUNCA darken()
.jaraba-header--dark {
  background: color.scale($ej-color-corporate-fallback, $lightness: -20%);
}
```

---

### Fase 2: Cierre de Gaps Site Structure Manager (Doc 176)

> **Estimación**: 16-20 horas
> **Prioridad**: P1
> **Módulo**: `jaraba_site_builder`

#### 2.1 Gap: Bulk Import/Export de Redirects

**Problema**: El spec define `POST /api/v1/site/redirects/bulk-import` (CSV) y `GET /api/v1/site/redirects/export` (CSV), que no están implementados.

**Implementación**:

```php
// En RedirectService o nuevo BulkRedirectService

public function importFromCsv(UploadedFile $file, int $tenantId): array {
    // 1. Validar formato CSV (source_path, destination_path, redirect_type)
    // 2. Validar cada fila (no loops, paths válidos)
    // 3. Insertar en lote usando entity storage
    // 4. Retornar {imported: int, errors: [{row, message}]}
}

public function exportToCsv(int $tenantId): StreamedResponse {
    // 1. Query todos los redirects del tenant
    // 2. Generar CSV con headers: source_path, destination_path, redirect_type, hit_count, is_active
    // 3. Retornar StreamedResponse con Content-Disposition: attachment
}
```

**Endpoints**:

| Método | Ruta | Función |
|--------|------|---------|
| POST | `/api/v1/site/redirects/bulk-import` | FormData con file CSV |
| GET | `/api/v1/site/redirects/export` | Descarga CSV |

#### 2.2 Gap: API de Breadcrumbs

**Problema**: No existe endpoint para obtener el trail de breadcrumbs de una página.

**Implementación**: Método en `SiteStructureService`:

```php
public function getBreadcrumbs(int $pageTreeId): array {
    // 1. Cargar nodo del árbol
    // 2. Recorrer ancestros via parent_id hasta root
    // 3. Construir array: [{id, title, url, depth}]
    // 4. Ordenar de root a hoja
    // 5. Retornar array para renderizar en _jaraba-breadcrumbs.html.twig
}
```

**Endpoint**: `GET /api/v1/site/breadcrumbs/{pageTreeId}`

#### 2.3 Gap: Enforcement de Límites por Plan

**Problema**: La spec define límites por plan (Starter: 10 pages / 2 depth / 10 redirects, Professional: 50/4/100, Enterprise: ilimitado), pero no hay enforcement.

**Implementación**: Método en `SiteStructureService` o nuevo `PlanLimitsEnforcer`:

```php
public function validatePlanLimits(int $tenantId, string $action): bool {
    // 1. Obtener plan del tenant via entity_reference
    // 2. Leer campos del plan: field_max_pages, field_max_depth, field_max_redirects
    // 3. Contar entidades actuales del tenant
    // 4. Comparar y lanzar excepción si se excede
    // Integración con jaraba_billing.feature_access si disponible
}
```

#### 2.4 Gap: Field UI y Route Provider Completo

**Problema**: Las 4 entidades existentes tienen handlers mínimos (list_builder, access) pero les faltan `form`, `views_data` y `route_provider` para tener plena integración con Field UI, Views y la navegación admin estándar de Drupal.

**Implementación**: Actualizar las anotaciones `@ContentEntityType` de:
- `SiteConfig` → añadir form handlers, views_data, route_provider, links
- `SitePageTree` → añadir form handlers, views_data, route_provider, links
- `SiteRedirect` → ya tiene form, verificar views_data y route_provider
- `SiteUrlHistory` → añadir views_data (read-only, sin form de creación pública)

---

### Fase 3: Blog System Nativo (Doc 178)

> **Estimación**: 50-60 horas
> **Prioridad**: P1
> **Módulo**: `jaraba_blog` (nuevo)

#### 3.1 Creación del Módulo

```
web/modules/custom/jaraba_blog/
├── jaraba_blog.info.yml
├── jaraba_blog.install
├── jaraba_blog.module
├── jaraba_blog.routing.yml
├── jaraba_blog.services.yml
├── jaraba_blog.permissions.yml
├── jaraba_blog.links.menu.yml
├── jaraba_blog.links.task.yml
├── jaraba_blog.links.action.yml
├── jaraba_blog.libraries.yml
├── package.json
├── scss/
│   ├── _variables.scss
│   ├── _blog-list.scss
│   ├── _blog-post.scss
│   ├── _blog-card.scss
│   ├── _blog-sidebar.scss
│   └── main.scss
├── css/
│   └── jaraba-blog.css
├── js/
│   └── blog-manager.js
├── templates/
│   ├── blog-list.html.twig
│   ├── blog-post.html.twig
│   └── blog-rss.html.twig
├── src/
│   ├── Entity/
│   │   ├── BlogConfig.php
│   │   ├── BlogPost.php
│   │   ├── BlogCategory.php
│   │   ├── BlogTag.php
│   │   └── BlogAuthor.php
│   ├── Controller/
│   │   ├── BlogApiController.php
│   │   ├── BlogCategoryApiController.php
│   │   ├── BlogTagApiController.php
│   │   ├── BlogFeedController.php
│   │   └── BlogPageController.php
│   ├── Service/
│   │   ├── BlogService.php
│   │   ├── RssFeedGenerator.php
│   │   └── BlogSearchService.php
│   ├── Form/
│   │   ├── BlogConfigForm.php
│   │   ├── BlogPostForm.php
│   │   ├── BlogCategoryForm.php
│   │   ├── BlogTagForm.php
│   │   └── BlogAuthorForm.php
│   ├── Access/
│   │   ├── BlogPostAccessControlHandler.php
│   │   ├── BlogCategoryAccessControlHandler.php
│   │   ├── BlogTagAccessControlHandler.php
│   │   └── BlogAuthorAccessControlHandler.php
│   ├── BlogPostListBuilder.php
│   ├── BlogCategoryListBuilder.php
│   ├── BlogTagListBuilder.php
│   └── BlogAuthorListBuilder.php
└── tests/
    └── src/
        └── Unit/
            ├── Entity/
            │   └── BlogPostTest.php
            └── Service/
                ├── BlogServiceTest.php
                └── RssFeedGeneratorTest.php
```

**info.yml**:
```yaml
name: 'Jaraba Blog'
type: module
description: 'Sistema de blog nativo multi-tenant con categorías, tags, autores y RSS.'
core_version_requirement: ^11
package: Jaraba Impact Platform
dependencies:
  - jaraba_site_builder:jaraba_site_builder
  - ecosistema_jaraba_core:ecosistema_jaraba_core
```

#### 3.2 Entidades del Blog

##### 3.2.1 Entidad `BlogConfig`

```
Tabla: blog_config
Tipo: Content Entity con Field UI

Campos base (conforme a spec 178):
  - tenant_id (entity_reference → group) REQUIRED, UNIQUE
  - blog_title (string, max 255, default 'Blog')
  - blog_description (string_long)
  - posts_per_page (integer, default 12)
  - list_layout (list_string): grid, list, masonry, cards
  - grid_columns (integer, default 3)
  - show_featured_image (boolean, default TRUE)
  - show_excerpt (boolean, default TRUE)
  - excerpt_length (integer, default 160)
  - show_author (boolean, default TRUE)
  - show_date (boolean, default TRUE)
  - show_reading_time (boolean, default TRUE)
  - post_layout (list_string): standard, wide, fullwidth, sidebar
  - sidebar_position (list_string): left, right
  - show_toc (boolean)
  - show_share_buttons (boolean, default TRUE)
  - show_related_posts (boolean, default TRUE)
  - related_posts_count (integer, default 3)
  - show_author_bio (boolean, default TRUE)
  - show_comments (boolean)
  - default_og_image (entity_reference → file)
  - schema_type (list_string): BlogPosting, Article, NewsArticle
  - rss_enabled (boolean, default TRUE)
  - rss_full_content (boolean)
  - created (created)
  - changed (changed)
```

##### 3.2.2 Entidad `BlogPost`

```
Tabla: blog_post
Tipo: Content Entity con Field UI

Campos base (conforme a spec 178):
  - tenant_id (entity_reference → group) REQUIRED
  - title (string, max 255) REQUIRED
  - slug (string, max 255) — Auto-generado desde título, UNIQUE con tenant_id
  - excerpt (string_long) — Resumen para listados y meta description
  - content (text_long) REQUIRED — Contenido principal
  - content_format (list_string): markdown, html, blocks
  - page_content_id (entity_reference → page_content) — Integración Page Builder
  - featured_image_id (entity_reference → file)
  - featured_image_alt (string, max 255)
  - featured_image_caption (string_long)
  - primary_category_id (entity_reference → blog_category)
  - categories (entity_reference → blog_category, cardinality unlimited) — M2M
  - tags (entity_reference → blog_tag, cardinality unlimited) — M2M
  - author_id (entity_reference → blog_author) REQUIRED
  - status (list_string): draft, pending, published, scheduled, archived
  - published_at (timestamp)
  - scheduled_at (timestamp) — Para publicación programada
  - meta_title (string, max 60) — SEO
  - meta_description (string, max 160) — SEO
  - focus_keyword (string, max 100) — SEO
  - canonical_url (string, max 500)
  - noindex (boolean)
  - views_count (integer, default 0)
  - reading_time_minutes (integer) — Calculado automáticamente
  - allow_comments (boolean, default TRUE)
  - is_featured (boolean)
  - is_sticky (boolean) — Siempre aparece primero
  - created (created)
  - changed (changed)

Índices:
  - UNIQUE(tenant_id, slug)
  - INDEX(tenant_id, status, published_at DESC) — Para listados
  - INDEX(tenant_id, is_featured, published_at DESC)
  - FULLTEXT(title, excerpt, content) — Búsqueda

Métodos clave:
  calculateReadingTime(): int — Strip tags, contar palabras, dividir por 200 WPM
  generateSlug(string $title): string — Transliteración + lowercase + hyphens
  isPublished(): bool
  getCategories(): array
  getTags(): array
  getAuthor(): ?BlogAuthor
```

##### 3.2.3 Entidad `BlogCategory`

```
Tabla: blog_category
Tipo: Content Entity con Field UI

Campos base:
  - tenant_id (entity_reference → group) REQUIRED
  - name (string, max 100) REQUIRED
  - slug (string, max 100) UNIQUE con tenant_id
  - description (string_long)
  - parent_id (entity_reference → blog_category, self-referencing)
  - image_id (entity_reference → file)
  - color (string, max 7) — Hex para badge
  - meta_title (string, max 60)
  - meta_description (string, max 160)
  - weight (integer, default 0)
  - posts_count (integer, default 0) — Cache counter

Índices:
  - UNIQUE(tenant_id, slug)
  - INDEX(tenant_id, parent_id, weight)
```

##### 3.2.4 Entidad `BlogTag`

```
Tabla: blog_tag
Tipo: Content Entity con Field UI

Campos base:
  - tenant_id (entity_reference → group) REQUIRED
  - name (string, max 100) REQUIRED
  - slug (string, max 100) UNIQUE con tenant_id
  - posts_count (integer, default 0) — Cache counter
```

##### 3.2.5 Entidad `BlogAuthor`

```
Tabla: blog_author
Tipo: Content Entity con Field UI

Campos base:
  - tenant_id (entity_reference → group) REQUIRED
  - user_id (entity_reference → user) UNIQUE con tenant_id
  - display_name (string, max 255) REQUIRED
  - slug (string, max 100) UNIQUE con tenant_id
  - bio (string_long)
  - avatar_id (entity_reference → file)
  - website_url (string, max 500)
  - twitter_handle (string, max 50)
  - linkedin_url (string, max 500)
  - posts_count (integer, default 0)
  - is_active (boolean, default TRUE)
```

#### 3.3 Servicios del Blog

| Servicio | Inyecciones | Métodos Principales |
|----------|-----------|-------------------|
| `BlogService` | entity_type.manager, database, tenant_context, file_url_generator | `listPosts()`, `getPost()`, `createPost()`, `updatePost()`, `deletePost()`, `publishPost()`, `schedulePost()`, `duplicatePost()`, `getRelatedPosts()`, `calculateReadingTime()`, `enrichPost()` |
| `RssFeedGenerator` | entity_type.manager, tenant_context, file_url_generator | `generate(int $tenantId): string` — RSS 2.0 feed con últimos 20 posts publicados |
| `BlogSearchService` | database, tenant_context | `search(string $query, array $filters): array` — Búsqueda fulltext con filtros |

#### 3.4 Endpoints API del Blog

##### Posts (10 endpoints):

| Método | Ruta | Función |
|--------|------|---------|
| GET | `/api/v1/blog/posts` | Listar posts con filtros: status, category, tag, author, search, featured, paginación |
| GET | `/api/v1/blog/posts/{slug}` | Obtener post por slug (incrementa views) |
| POST | `/api/v1/blog/posts` | Crear post |
| PUT | `/api/v1/blog/posts/{id}` | Actualizar post |
| DELETE | `/api/v1/blog/posts/{id}` | Eliminar post |
| POST | `/api/v1/blog/posts/{id}/publish` | Publicar post |
| POST | `/api/v1/blog/posts/{id}/unpublish` | Despublicar post |
| POST | `/api/v1/blog/posts/{id}/schedule` | Programar publicación |
| POST | `/api/v1/blog/posts/{id}/duplicate` | Duplicar post |
| GET | `/api/v1/blog/posts/{id}/related` | Obtener posts relacionados |

##### Categorías (5 endpoints):

| Método | Ruta | Función |
|--------|------|---------|
| GET | `/api/v1/blog/categories` | Listar categorías |
| POST | `/api/v1/blog/categories` | Crear categoría |
| PUT | `/api/v1/blog/categories/{id}` | Actualizar categoría |
| DELETE | `/api/v1/blog/categories/{id}` | Eliminar categoría |
| POST | `/api/v1/blog/categories/reorder` | Reordenar categorías |

##### Tags (5 endpoints):

| Método | Ruta | Función |
|--------|------|---------|
| GET | `/api/v1/blog/tags` | Listar tags con contador |
| GET | `/api/v1/blog/tags/popular` | Tags más usados |
| GET | `/api/v1/blog/tags/search` | Autocompletado: `?q=trans` |
| POST | `/api/v1/blog/tags` | Crear tag |
| DELETE | `/api/v1/blog/tags/{id}` | Eliminar tag |

##### Feed:

| Método | Ruta | Función | Auth |
|--------|------|---------|------|
| GET | `/blog/feed.xml` | RSS 2.0 feed | `_access: 'TRUE'` (público) |

#### 3.5 Página Frontend del Blog

**Ruta**: `/blog` y `/blog/{slug}`

**Template**: `page--blog.html.twig` en `ecosistema_jaraba_theme/templates/`

```twig
{# page--blog.html.twig - Página frontend limpia para el Blog #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('jaraba_blog/blog') }}

<!DOCTYPE html>
<html{{ html_attributes }}>
<head>
  <head-placeholder token="{{ placeholder_token }}">
  <title>{{ head_title|safe_join(' | ') }}</title>
  <css-placeholder token="{{ placeholder_token }}">
  <js-placeholder token="{{ placeholder_token }}">
</head>

<body{{ attributes }}>
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Saltar al contenido principal{% endtrans %}
  </a>

  {# HEADER - Parcial reutilizable con config del tenant #}
  {% include '@ecosistema_jaraba_theme/partials/_jaraba-header.html.twig' with {
    header_config: header_config,
    main_menu_tree: main_menu_tree,
    site_config: site_config
  } %}

  {# BREADCRUMBS - Parcial reutilizable #}
  {% if breadcrumbs is not empty %}
    {% include '@ecosistema_jaraba_theme/partials/_jaraba-breadcrumbs.html.twig' with {
      breadcrumbs: breadcrumbs,
      current_title: current_title
    } %}
  {% endif %}

  {# MAIN - Full-width #}
  <main id="main-content" class="blog-main">
    {{ page.content }}
  </main>

  {# FOOTER - Parcial reutilizable con config del tenant #}
  {% include '@ecosistema_jaraba_theme/partials/_jaraba-footer.html.twig' with {
    footer_config: footer_config,
    footer_menus: footer_menus,
    site_config: site_config
  } %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

**Body classes** (en `.theme`):

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();

  if (str_starts_with($route, 'jaraba_blog.')) {
    $variables['attributes']['class'][] = 'page-blog';
    $variables['attributes']['class'][] = 'frontend-page';
    $variables['attributes']['class'][] = 'no-admin-sidebar';
  }

  if ($route === 'jaraba_blog.post') {
    $variables['attributes']['class'][] = 'page-blog-post';
  }
}
```

#### 3.6 SCSS del Blog

Archivos en `jaraba_blog/scss/`:

| Parcial | Contenido | Clases principales |
|---------|-----------|-------------------|
| `_variables.scss` | `@use 'variables' as *;` — Solo consume variables del core | — |
| `_blog-list.scss` | Grid de posts, filtros, paginación | `.jaraba-blog`, `.jaraba-blog__grid`, `.jaraba-blog__filters`, `.jaraba-blog__pagination` |
| `_blog-card.scss` | Card de post en listado | `.jaraba-post-card`, `.jaraba-post-card--featured`, `.jaraba-post-card__image`, `.jaraba-post-card__meta` |
| `_blog-post.scss` | Artículo individual | `.jaraba-post`, `.jaraba-post__header`, `.jaraba-post__prose`, `.jaraba-post__share` |
| `_blog-sidebar.scss` | Sidebar opcional | `.jaraba-sidebar`, `.jaraba-sidebar__author`, `.jaraba-sidebar__newsletter` |
| `main.scss` | Entry point | `@use` de todos los parciales |

**package.json**:
```json
{
  "name": "jaraba-blog",
  "scripts": {
    "build": "sass scss/main.scss css/jaraba-blog.css --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.77.0"
  }
}
```

---

### Fase 4: SEO/GEO + Integración IA (Doc 179)

> **Estimación**: 40-48 horas
> **Prioridad**: P2
> **Módulos**: `jaraba_site_builder` + `jaraba_blog`

#### 4.1 Servicios a Crear

##### 4.1.1 `SchemaOrgGlobalService`

**Ubicación**: `jaraba_site_builder/src/Service/SchemaOrgGlobalService.php`

```
Dependencias: entity_type.manager, tenant_context, file_url_generator, request_stack

Métodos:
  generateWebSiteSchema(int $tenantId): array
    → @type: WebSite con url, name, description, inLanguage,
      publisher (Organization), potentialAction (SearchAction)

  generateOrganizationSchema(int $tenantId): array
    → @type: Organization o LocalBusiness según configuración.
      Incluye: name, url, logo, contactPoint, sameAs (redes sociales).
      Si es LocalBusiness: address, geo, openingHours, priceRange.

  generateBreadcrumbSchema(int $tenantId, int $pageTreeId): array
    → @type: BreadcrumbList con array de ListItem
      Recorre ancestros del árbol de páginas.

  injectSchemaToPage(array &$page, int $tenantId): void
    → Inyecta JSON-LD en <head> de la página via #attached
```

##### 4.1.2 `BlogSeoService`

**Ubicación**: `jaraba_blog/src/Service/BlogSeoService.php`

```
Dependencias: entity_type.manager, file_url_generator, request_stack, tenant_context

Métodos:
  generatePostSchema(BlogPost $post, int $tenantId): array
    → @type: BlogPosting (o Article/NewsArticle según blog_config)
      Incluye: headline, description, datePublished, dateModified,
      image (ImageObject), author (Person con sameAs),
      publisher (Organization), articleSection, wordCount,
      timeRequired (ISO 8601 duración), keywords.

  generateCategorySchema(BlogCategory $category, array $posts): array
    → @type: CollectionPage con hasPart[]

  generateAuthorSchema(BlogAuthor $author): array
    → @type: Person con sameAs, jobTitle, worksFor
```

##### 4.1.3 `AISiteBuilderService`

**Ubicación**: `jaraba_site_builder/src/Service/AISiteBuilderService.php`

```
Dependencias: @ai.provider, entity_type.manager, tenant_context, logger

Métodos:
  suggestSiteStructure(int $tenantId, array $params): array
    Input: {industry, goals[], features[]}
    Prompt al LLM con contexto del sector y objetivos.
    Output: {site_tree[], main_menu[], essential_pages[], seo_recommendations[]}

  generateBlogPost(int $tenantId, array $params): array
    Input: {topic, keywords[], tone, length}
    Prompt especializado para contenido SEO.
    Output: {title, meta_title, meta_description, excerpt, content,
             focus_keyword, faq[], internal_link_suggestions[],
             suggested_categories[], suggested_tags[], image_suggestions[]}

  optimizePageSEO(int $tenantId, int $pageId): array
    Analiza contenido existente de la página.
    Output: {seo_score 0-100, issues[], optimized_meta{},
             keyword_suggestions[], content_suggestions[]}

  suggestInternalLinks(int $tenantId, int $postId): array
    Busca posts similares via embeddings o FULLTEXT.
    Output: [{anchor_text, target_id, target_title, target_url, score}] max 5

  callLLM(string $prompt, int $maxTokens): string
    Wrapper para @ai.provider con failover.
    Modelo: claude-sonnet-4-20250514 (o equivalente)
    Rate limiting: 50 req/hora por tenant.
```

**Integración IA** (conforme a `ai-integration.md`):

```php
use Drupal\ai\AiProviderPluginManager;

class AISiteBuilderService {
  private const PROVIDERS = ['anthropic', 'openai'];
  private const MAX_TOKENS = 4000;

  public function __construct(
    private AiProviderPluginManager $aiProvider,
    // ...
  ) {}

  private function callLLM(string $prompt, int $maxTokens = self::MAX_TOKENS): string {
    foreach (self::PROVIDERS as $provider) {
      try {
        $llm = $this->aiProvider->createInstance($provider);
        $response = $llm->chat([
          ['role' => 'user', 'content' => $prompt]
        ], $this->getModelForProvider($provider));
        return $response->getText();
      } catch (\Exception $e) {
        $this->logger->warning('Proveedor IA @id falló: @msg', [
          '@id' => $provider, '@msg' => $e->getMessage()
        ]);
        continue;
      }
    }
    return $this->getFallbackResponse();
  }
}
```

##### 4.1.4 `MultiLanguageSeoService`

**Ubicación**: `jaraba_site_builder/src/Service/MultiLanguageSeoService.php`

```
Dependencias: language_manager, entity_type.manager, tenant_context, request_stack

Métodos:
  generateHreflangTags(int $tenantId, int $pageId): array
    → Para cada idioma habilitado: {rel: 'alternate', hreflang: code, href: url}
    → Incluye x-default apuntando al idioma principal

  generateMultiLanguageSitemap(int $tenantId): string
    → XML sitemap con <xhtml:link rel="alternate"> por cada variante de idioma
```

##### 4.1.5 `CoreWebVitalsService`

**Ubicación**: `jaraba_site_builder/src/Service/CoreWebVitalsService.php`

```
Dependencias: entity_type.manager, database

Métodos:
  generateCriticalCSS(int $pageId): string
    → Extrae bloques above-the-fold (primeros 3)
    → Genera CSS crítico minificado

  generatePreloadHints(int $tenantId, int $pageId): array
    → Hints para fonts (con crossorigin), LCP image, CDN preconnect

  optimizeImagesForLazyLoading(string $html): string
    → fetchpriority="high" en primeras 2 imágenes
    → loading="lazy" decoding="async" en el resto

  auditCoreWebVitals(int $pageId): array
    → Score 0-100: -5 por imagen sin width/height (CLS),
      -15 si hay 2+ scripts bloqueantes (FID)
    → Output: {score, issues[{type, severity, message, fix}]}
```

#### 4.2 Endpoints API (Doc 179)

| Método | Ruta | Función | Auth |
|--------|------|---------|------|
| GET | `/api/v1/site/schema` | Schema.org completo del sitio | `_user_is_logged_in` |
| GET | `/api/v1/site/schema/page/{id}` | Schema de una página | `_user_is_logged_in` |
| GET | `/api/v1/site/schema/breadcrumbs/{id}` | BreadcrumbList schema | `_user_is_logged_in` |
| GET | `/api/v1/site/hreflang/{page_id}` | Tags hreflang | `_user_is_logged_in` |
| GET | `/api/v1/site/sitemap-multilingual.xml` | Sitemap multilingüe | `_access: 'TRUE'` |
| GET | `/api/v1/site/performance/{page_id}` | Critical CSS + preload hints | `_user_is_logged_in` |
| POST | `/api/v1/site/performance/audit` | Auditoría CWV | `_permission: 'administer site structure'` |
| POST | `/api/v1/ai/site/suggest-structure` | Sugerir estructura IA | `_permission: 'administer site structure'` |
| POST | `/api/v1/ai/site/analyze-architecture` | Analizar arquitectura IA | `_permission: 'administer site structure'` |
| POST | `/api/v1/ai/blog/generate-post` | Generar post con IA | `_permission: 'create blog_post'` |
| POST | `/api/v1/ai/blog/optimize-seo/{post_id}` | Optimizar SEO de post | `_permission: 'edit blog_post'` |
| POST | `/api/v1/ai/blog/suggest-internal-links/{post_id}` | Sugerir enlaces internos | `_permission: 'edit blog_post'` |
| POST | `/api/v1/ai/blog/generate-excerpt` | Auto-generar resumen | `_permission: 'edit blog_post'` |
| POST | `/api/v1/ai/blog/suggest-taxonomy` | Sugerir categorías/tags | `_permission: 'edit blog_post'` |
| GET | `/api/v1/seo/audit/site` | Auditoría SEO completa | `_permission: 'administer site structure'` |
| GET | `/api/v1/seo/audit/page/{id}` | Auditoría SEO página | `_permission: 'view site structure'` |

---

## 6. Arquitectura de Entidades

### 6.1 Diagrama de Relaciones

```
┌────────────────┐     ┌──────────────────┐
│  SiteConfig    │     │ SiteHeaderConfig  │
│  (existente)   │     │  (nuevo)          │
│                │     │                   │
│  tenant_id ────┼──┐  │  tenant_id ───┐   │
│  site_name     │  │  │  header_type  │   │
│  social_links  │  │  │  main_menu_id─┼───┼──┐
│  ...           │  │  │  logo_id      │   │  │
└────────────────┘  │  └──────────────────┘  │
                    │                         │
                    │  ┌──────────────────┐   │
                    │  │ SiteFooterConfig │   │
                    │  │  (nuevo)         │   │
                    │  │                  │   │
                    │  │  tenant_id ──┐   │   │
                    │  │  footer_type │   │   │
                    │  │  columns_config  │   │
                    │  └──────────────────┘   │
                    │                         │
                    │  ┌──────────────────┐   │
                    └──│  SiteMenu        │◄──┘
                       │  (nuevo)         │
                       │                  │
                       │  tenant_id       │
                       │  machine_name    │
                       │  label           │
                       └────────┬─────────┘
                                │ 1:N
                       ┌────────▼─────────┐
                       │  SiteMenuItem    │
                       │  (nuevo)         │
                       │                  │
                       │  menu_id ────────│→ SiteMenu
                       │  parent_id ──────│→ Self (jerárquico)
                       │  page_id ────────│→ page_content
                       │  title, url      │
                       │  item_type       │
                       └──────────────────┘

┌────────────────┐     ┌──────────────────┐
│ SitePageTree   │     │  SiteRedirect    │
│ (existente)    │     │  (existente)     │
│                │     │                  │
│ tenant_id      │     │  tenant_id       │
│ page_id ───────┼──→  │  source_path     │
│ parent_id ─────┼→Self│  destination_path│
│ weight, depth  │     │  redirect_type   │
│ nav_* campos   │     │  hit_count       │
└────────────────┘     └──────────────────┘

┌────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  BlogPost      │     │  BlogCategory    │     │  BlogTag         │
│  (nuevo)       │     │  (nuevo)         │     │  (nuevo)         │
│                │     │                  │     │                  │
│  tenant_id     │     │  tenant_id       │     │  tenant_id       │
│  title, slug   │     │  name, slug      │     │  name, slug      │
│  content       │     │  parent_id→Self  │     │  posts_count     │
│  author_id ────┼──┐  │  color           │     └──────────────────┘
│  categories[]──┼──┼→ │  weight          │
│  tags[] ───────┼──┼→ └──────────────────┘
│  status        │  │
│  published_at  │  │  ┌──────────────────┐
│  meta_*        │  │  │  BlogAuthor      │
│  is_featured   │  │  │  (nuevo)         │
└────────────────┘  │  │                  │
                    └→ │  tenant_id       │
                       │  user_id         │
                       │  display_name    │
                       │  bio, avatar_id  │
                       │  social links    │
                       └──────────────────┘

┌────────────────┐
│  BlogConfig    │
│  (nuevo)       │
│                │
│  tenant_id     │
│  blog_title    │
│  posts_per_page│
│  list_layout   │
│  post_layout   │
│  rss_enabled   │
│  schema_type   │
└────────────────┘
```

### 6.2 Resumen: Total de Entidades

| Categoría | Entidad | Estado | Módulo |
|-----------|---------|--------|--------|
| Navegación | SiteMenu | **Nuevo** | jaraba_site_builder |
| Navegación | SiteMenuItem | **Nuevo** | jaraba_site_builder |
| Navegación | SiteHeaderConfig | **Nuevo** | jaraba_site_builder |
| Navegación | SiteFooterConfig | **Nuevo** | jaraba_site_builder |
| Estructura | SiteConfig | Existente (completar handlers) | jaraba_site_builder |
| Estructura | SitePageTree | Existente (completar handlers) | jaraba_site_builder |
| Estructura | SiteRedirect | Existente | jaraba_site_builder |
| Estructura | SiteUrlHistory | Existente | jaraba_site_builder |
| Blog | BlogConfig | **Nuevo** | jaraba_blog |
| Blog | BlogPost | **Nuevo** | jaraba_blog |
| Blog | BlogCategory | **Nuevo** | jaraba_blog |
| Blog | BlogTag | **Nuevo** | jaraba_blog |
| Blog | BlogAuthor | **Nuevo** | jaraba_blog |

**Total**: 13 entidades (4 existentes + 4 nuevas navegación + 5 nuevas blog)

---

## 7. Arquitectura de Servicios

### 7.1 Servicios por Módulo

#### jaraba_site_builder (existentes + nuevos)

| Servicio | ID | Estado | Función |
|----------|----|--------|---------|
| SiteStructureService | `jaraba_site_builder.structure` | ✅ Existente + extender breadcrumbs | Gestión árbol de páginas |
| RedirectService | `jaraba_site_builder.redirect` | ✅ Existente + bulk import/export | CRUD redirects |
| SitemapGeneratorService | `jaraba_site_builder.sitemap` | ✅ Existente | XML sitemap |
| SiteAnalyticsService | `jaraba_site_builder.analytics` | ✅ Existente | Dashboard stats |
| SeoAuditorService | `jaraba_site_builder.seo_auditor` | ✅ Existente | Auditoría SEO |
| HeaderVariantService | `jaraba_site_builder.header_variant` | ✅ Refactorizar | Variantes header |
| FooterVariantService | `jaraba_site_builder.footer_variant` | ✅ Refactorizar | Variantes footer |
| **MenuService** | `jaraba_site_builder.menu` | **Nuevo** | CRUD menús + items |
| **NavigationRenderService** | `jaraba_site_builder.navigation_render` | **Nuevo** | Renderizado header/footer/breadcrumbs |
| **SchemaOrgGlobalService** | `jaraba_site_builder.schema_org` | **Nuevo** | Schema.org WebSite/Organization |
| **MultiLanguageSeoService** | `jaraba_site_builder.multilanguage_seo` | **Nuevo** | Hreflang + sitemap multilingüe |
| **CoreWebVitalsService** | `jaraba_site_builder.core_web_vitals` | **Nuevo** | Critical CSS, preload, audit CWV |
| **AISiteBuilderService** | `jaraba_site_builder.ai_site_builder` | **Nuevo** | IA: estructura, SEO, links |

#### jaraba_blog (todos nuevos)

| Servicio | ID | Función |
|----------|----|---------|
| **BlogService** | `jaraba_blog.blog` | CRUD posts, enrichment, reading time |
| **RssFeedGenerator** | `jaraba_blog.rss_feed` | Generación RSS 2.0 |
| **BlogSearchService** | `jaraba_blog.search` | Búsqueda fulltext |
| **BlogSeoService** | `jaraba_blog.seo` | Schema.org BlogPosting |

**Total**: 17 servicios (7 existentes + 6 nuevos en site_builder + 4 nuevos en blog)

---

## 8. API REST: Catálogo Completo de Endpoints

### 8.1 Resumen por Categoría

| Categoría | Nuevos Endpoints | Módulo |
|-----------|-----------------|--------|
| Header/Footer API | 7 | jaraba_site_builder |
| Menús API | 9 | jaraba_site_builder |
| Redirects API (gaps) | 2 | jaraba_site_builder |
| Breadcrumbs API | 1 | jaraba_site_builder |
| Blog Posts API | 10 | jaraba_blog |
| Blog Categories API | 5 | jaraba_blog |
| Blog Tags API | 5 | jaraba_blog |
| Blog Feed | 1 | jaraba_blog |
| Schema.org API | 3 | jaraba_site_builder |
| Hreflang API | 2 | jaraba_site_builder |
| CWV API | 2 | jaraba_site_builder |
| IA API | 7 | jaraba_site_builder + jaraba_blog |
| SEO Audit API | 2 | jaraba_site_builder |
| **Total** | **56 nuevos endpoints** | |

### 8.2 Convenciones de Ruta

Todas las rutas REST siguen el patrón existente:

```
/api/v1/{dominio}/{recurso}              → Listado / Creación
/api/v1/{dominio}/{recurso}/{id}         → Detalle / Actualización / Eliminación
/api/v1/{dominio}/{recurso}/{id}/{acción}→ Acciones específicas
```

Restricciones de parámetros (regex):

```yaml
# routing.yml
requirements:
  id: '\d+'
  slug: '[a-z0-9\-]+'
  itemId: '\d+'
  page_id: '\d+'
  post_id: '\d+'
```

---

## 9. Templates Twig y Parciales

### 9.1 Parciales del Tema (Compartidos)

Ubicación: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/`

| Parcial | Variables | Creado/Existente |
|---------|-----------|------------------|
| `_jaraba-header.html.twig` | header_config, main_menu_tree, site_config | Nuevo (reemplaza `_header.html.twig` genérico) |
| `_jaraba-footer.html.twig` | footer_config, footer_menus, site_config, social_links | Nuevo (reemplaza `_footer.html.twig` genérico) |
| `_jaraba-mobile-menu.html.twig` | main_menu_tree, header_config | Nuevo |
| `_jaraba-mega-menu.html.twig` | mega_items, columns | Nuevo |
| `_jaraba-topbar.html.twig` | topbar_content, bg_color, text_color | Nuevo |
| `_jaraba-breadcrumbs.html.twig` | breadcrumbs[], current_title | Nuevo |

### 9.2 Parciales del Módulo Blog

Ubicación: `web/modules/custom/jaraba_blog/templates/`

| Template | Variables | Función |
|----------|-----------|---------|
| `blog-list.html.twig` | blog_config, posts[], categories[], pagination, current_category, search_query | Listado de posts con filtros y grid |
| `blog-post.html.twig` | post, blog_config, related_posts[], schema_json | Post individual con layout variable |
| `blog-rss.html.twig` | channel, posts[] | Plantilla RSS XML |

### 9.3 Page Templates del Tema

Ubicación: `web/themes/custom/ecosistema_jaraba_theme/templates/`

| Template | Ruta | Body Class |
|----------|------|-----------|
| `page--blog.html.twig` | `/blog`, `/blog/{slug}` | `page-blog frontend-page no-admin-sidebar` |
| `page--site-builder.html.twig` | `/site-builder/*` | `page-site-builder frontend-page no-admin-sidebar` (ya existe, verificar) |

### 9.4 Patrón de Include Obligatorio

Todos los parciales DEBEN recibir sus datos como variables Twig, NUNCA acceder a servicios:

```twig
{# ✅ CORRECTO: Datos inyectados via hook_preprocess_page() #}
{% include '@ecosistema_jaraba_theme/partials/_jaraba-header.html.twig' with {
  header_config: header_config,
  main_menu_tree: main_menu_tree
} %}

{# ❌ INCORRECTO: Nunca acceder a servicios desde Twig #}
{% set config = drupal_service('jaraba_site_builder.navigation_render').getHeaderConfig() %}
```

---

## 10. Arquitectura SCSS y Design Tokens

### 10.1 Módulo jaraba_site_builder (extensiones)

Nuevos parciales SCSS a añadir al pipeline existente:

```
jaraba_site_builder/scss/
├── _variables.scss        ← (existente)
├── _site-tree.scss        ← (existente)
├── _navigation.scss       ← NUEVO: menú horizontal, dropdowns
├── _header.scss           ← NUEVO: 5 variantes header
├── _footer.scss           ← NUEVO: 5 variantes footer
├── _mega-menu.scss        ← NUEVO: grid mega menu
├── _mobile-menu.scss      ← NUEVO: off-canvas mobile
├── _topbar.scss           ← NUEVO: barra superior
├── _breadcrumbs.scss      ← NUEVO: trail de navegación
└── main.scss              ← Actualizar con @use nuevos parciales
```

### 10.2 Módulo jaraba_blog (pipeline nuevo)

```
jaraba_blog/scss/
├── _variables.scss        ← @use del core
├── _blog-list.scss        ← Grid, filtros, paginación
├── _blog-card.scss        ← Cards de posts
├── _blog-post.scss        ← Artículo individual, prose styles
├── _blog-sidebar.scss     ← Sidebar opcional
└── main.scss              ← Entry point
```

### 10.3 Reglas de Design Tokens

| Regla | Ejemplo |
|-------|---------|
| **Colores**: Solo CSS custom properties | `color: var(--ej-color-primary, #4F46E5);` |
| **Fallbacks**: SCSS variable como fallback | `background: var(--ej-color-corporate, #{$ej-color-corporate-fallback});` |
| **Spacing**: Custom properties | `padding: var(--ej-spacing-md, 1rem);` |
| **Sombras**: Custom properties | `box-shadow: var(--ej-shadow-md);` |
| **Tipografía**: Custom properties | `font-family: var(--ej-font-family);` |
| **Variaciones de color**: `color.scale()` | `background: color.scale($my-color, $lightness: 85%);` |
| **NO usar**: `darken()`, `lighten()`, `@import` | Deprecated en Dart Sass |
| **Cada parcial**: Imports propios | `@use 'sass:color'; @use 'variables' as *;` |

### 10.4 Compilación

```bash
# Site Builder (extensión)
cd web/modules/custom/jaraba_site_builder
export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use --lts && npm run build
lando drush cr

# Blog (nuevo)
cd web/modules/custom/jaraba_blog
npm install && chmod +x node_modules/.bin/sass
npm run build
lando drush cr
```

---

## 11. Checklist de Cumplimiento de Directrices

### 11.1 Checklist Pre-Implementación (verificar antes de empezar cada fase)

- [ ] **Content Entities**: Todas las entidades nuevas tienen handlers completos (list_builder, views_data, form, access, route_provider)
- [ ] **Field UI**: `field_ui_base_route` definido en la anotación, `fieldable = TRUE`
- [ ] **Links Admin**: `links.menu.yml` con entrada en `/admin/structure`, `links.task.yml` con tabs, `links.action.yml` con botones "Añadir"
- [ ] **Permisos**: `permissions.yml` con permisos granulares (administer, create, edit, delete, view)
- [ ] **Multi-tenant**: Toda entidad tiene `tenant_id` (entity_reference → group), toda query filtra por tenant
- [ ] **Routing**: `routing.yml` con restricciones regex en parámetros, autenticación adecuada
- [ ] **i18n**: `$this->t()` en PHP, `{% trans %}` en Twig, `Drupal.t()` en JS, texto base en español

### 11.2 Checklist Frontend (verificar en cada página/template)

- [ ] **Template limpio**: `page--{route}.html.twig` con HTML completo, sin regiones Drupal
- [ ] **Parciales**: `{% include %}` para header, footer, breadcrumbs — reutilizables
- [ ] **Body classes**: `hook_preprocess_html()` en .theme, NUNCA `attributes.addClass()` en template
- [ ] **Full-width**: Sin sidebar, 100% ancho, layout mobile-first
- [ ] **CRUD en slide-panel**: Botones con `data-slide-panel`, `data-slide-panel-url`, `data-slide-panel-title`
- [ ] **Library dependency**: `ecosistema_jaraba_theme/slide-panel` en libraries.yml
- [ ] **Sin admin theme**: Tenant no ve Drupal admin. Todo en rutas frontend `/blog/*`, `/site-builder/*`

### 11.3 Checklist SCSS (verificar en cada parcial)

- [ ] **`@use` moderno**: `@use 'sass:color'; @use 'variables' as *;` — NO `@import`
- [ ] **CSS custom properties**: `var(--ej-*)` para colores, spacing, tipografía
- [ ] **Paleta Jaraba**: corporate, impulse, innovation, agro como aliases semánticos
- [ ] **`color.scale()`**: NUNCA `darken()`, `lighten()`
- [ ] **Parcial con prefijo `_`**: `_blog-list.scss`, importado en `main.scss` con `@use`
- [ ] **Compilado**: `npm run build` + `lando drush cr` antes de commit

### 11.4 Checklist IA (verificar en servicios IA)

- [ ] **`@ai.provider`**: NUNCA cliente HTTP directo a APIs
- [ ] **Failover**: Anthropic → OpenAI → fallback graceful
- [ ] **Claves en Key module**: `/admin/config/system/keys`
- [ ] **Rate limiting**: FloodInterface, 50 req/hora por tenant
- [ ] **Sanitización de prompts**: Whitelist para datos interpolados
- [ ] **Logging**: Registrar tokens consumidos para FinOps

---

## 12. Plan de Testing

### 12.1 Unit Tests

| Test Suite | Módulo | Tests | Cobertura |
|-----------|--------|-------|-----------|
| `SiteMenuTest` | jaraba_site_builder | Creación, machine_name generation, validación | Entidad SiteMenu |
| `SiteMenuItemTest` | jaraba_site_builder | Jerarquía, depth calculation, mega content | Entidad SiteMenuItem |
| `MenuServiceTest` | jaraba_site_builder | buildTree, reorder, addItem, deleteItem | Servicio MenuService |
| `NavigationRenderServiceTest` | jaraba_site_builder | renderHeader, renderFooter, breadcrumbs | Servicio NavigationRender |
| `BlogPostTest` | jaraba_blog | Creación, slug generation, reading time | Entidad BlogPost |
| `BlogServiceTest` | jaraba_blog | listPosts, createPost, enrichPost | Servicio BlogService |
| `RssFeedGeneratorTest` | jaraba_blog | Formato RSS válido, 20 posts, enclosures | Servicio RSS |
| `SchemaOrgTest` | jaraba_site_builder | WebSite, Organization, BreadcrumbList | Servicio SchemaOrg |
| `BlogSeoServiceTest` | jaraba_blog | BlogPosting schema, author schema | Servicio BlogSeo |
| `CoreWebVitalsTest` | jaraba_site_builder | Audit scoring, critical CSS, preload | Servicio CWV |

### 12.2 Functional Tests

| Test | Verificación |
|------|-------------|
| Creación de menú con items jerárquicos | CRUD completo via API |
| Drag & drop reorder de menú | POST reorder y verificar weights |
| Blog post lifecycle: draft → scheduled → published | Estados + cron scheduler |
| RSS feed válido | Validar XML contra RSS 2.0 spec |
| Schema.org JSON-LD | Validar contra schema.org/BlogPosting |
| Plan limits enforcement | Verificar que se bloquea al exceder |

---

## 13. Estimación de Esfuerzo

### 13.1 Desglose por Fase

| Fase | Componente | Horas Min | Horas Max |
|------|-----------|----------|----------|
| **Fase 1** | Entidades SiteMenu + SiteMenuItem | 6 | 8 |
| | Entidades SiteHeaderConfig + SiteFooterConfig | 5 | 6 |
| | MenuService + NavigationRenderService | 6 | 8 |
| | API endpoints (16) | 8 | 10 |
| | Templates Twig parciales (6) | 6 | 8 |
| | SCSS navigation + header + footer + mobile + mega | 7 | 9 |
| **Subtotal Fase 1** | | **38** | **49** |
| **Fase 2** | Bulk import/export redirects | 3 | 4 |
| | API breadcrumbs | 2 | 3 |
| | Plan limits enforcement | 3 | 4 |
| | Field UI + route_provider en entidades existentes | 4 | 5 |
| | Mapa visual del sitemap | 4 | 5 |
| **Subtotal Fase 2** | | **16** | **21** |
| **Fase 3** | Módulo jaraba_blog scaffolding | 2 | 3 |
| | 5 Content Entities (Config, Post, Category, Tag, Author) | 10 | 12 |
| | BlogService + RssFeedGenerator + BlogSearchService | 8 | 10 |
| | API endpoints (21) | 10 | 12 |
| | Templates Twig (blog-list, blog-post, page--blog) | 8 | 10 |
| | SCSS blog completo | 5 | 6 |
| | JS blog-manager.js | 4 | 5 |
| | Publicación programada (cron) | 2 | 3 |
| **Subtotal Fase 3** | | **49** | **61** |
| **Fase 4** | SchemaOrgGlobalService | 5 | 6 |
| | BlogSeoService | 4 | 5 |
| | AISiteBuilderService (5 métodos IA) | 10 | 12 |
| | MultiLanguageSeoService | 4 | 5 |
| | CoreWebVitalsService | 5 | 6 |
| | API endpoints (16) | 8 | 10 |
| | Integración IA en UI blog | 4 | 5 |
| **Subtotal Fase 4** | | **40** | **49** |
| | | | |
| **Testing** | Unit tests (~10 suites) | 8 | 10 |
| | Functional tests | 4 | 5 |
| **Subtotal Testing** | | **12** | **15** |
| | | | |
| **TOTAL** | | **155** | **195** |

### 13.2 Calendario Sugerido

| Semana | Fase | Entregables |
|--------|------|-------------|
| S1-S2 | Fase 1 (Nav) | Entidades + servicios + APIs de menús |
| S3 | Fase 1 (Nav) | Templates + SCSS + integración con tema |
| S4 | Fase 2 (Gaps 176) | Bulk redirects + breadcrumbs + plan limits |
| S5-S6 | Fase 3 (Blog) | Entidades + servicios + APIs |
| S7 | Fase 3 (Blog) | Templates + SCSS + frontend page |
| S8-S9 | Fase 4 (SEO/IA) | Servicios SEO + Schema.org + CWV |
| S10 | Fase 4 (SEO/IA) | AISiteBuilderService + APIs IA |
| S11 | Testing | Unit + functional tests completos |

---

## 14. Dependencias y Riesgos

### 14.1 Dependencias Externas

| Dependencia | Módulo Requerido | Estado |
|------------|-----------------|--------|
| `ecosistema_jaraba_core` | Tenant context, Design Tokens | ✅ Disponible |
| `jaraba_page_builder` | page_content entity | ✅ Disponible |
| `ai` (Drupal AI) | @ai.provider para Fase 4 | ✅ Configurado |
| `jaraba_billing` | FeatureAccessService para plan limits | ✅ Disponible (dependencia condicional) |
| SortableJS | Drag & drop para menú builder | ✅ Ya en CDN (site_builder.libraries.yml) |

### 14.2 Riesgos y Mitigaciones

| Riesgo | Impacto | Probabilidad | Mitigación |
|--------|---------|-------------|-----------|
| Migración header/footer de SiteConfig a entidades separadas puede romper frontend existente | Alto | Media | Script de migración en `.install`, test de regresión, backward compat temporal |
| Content Entity install requiere `drush entity:update` o reinstalación | Medio | Alta | Implementar `hook_update_N()` con `\Drupal::entityDefinitionUpdateManager()` |
| Conflicto con parciales `_header.html.twig` y `_footer.html.twig` existentes | Medio | Alta | Nombrar nuevos parciales con prefijo `_jaraba-` para evitar colisión |
| Rate limiting IA puede bloquear UX si tenant alcanza límite | Bajo | Baja | Mensajes claros, upgrade path visible, quota reset automático |
| Rendimiento de Schema.org JSON-LD en cada page load | Bajo | Media | Cache por tenant + route con cache tags |

---

## 15. Criterios de Aceptación

### 15.1 Fase 1 — Navegación Global

- [ ] Las 4 entidades nuevas (SiteMenu, SiteMenuItem, SiteHeaderConfig, SiteFooterConfig) están creadas como Content Entities con Field UI, Views, formularios y route_provider
- [ ] Las entidades aparecen en `/admin/structure/` con listados y formularios funcionales
- [ ] Los 16 endpoints API de menús, header y footer responden correctamente
- [ ] El header se renderiza en todas las páginas frontend via parcial `_jaraba-header.html.twig`
- [ ] El footer se renderiza en todas las páginas frontend via parcial `_jaraba-footer.html.twig`
- [ ] El menú mobile se abre/cierra con animación off-canvas
- [ ] Los 5 tipos de header son visualmente distintos y funcionales
- [ ] Los 5 tipos de footer son visualmente distintos y funcionales
- [ ] Mega menu con grid de columnas, iconos y badges funciona
- [ ] Top bar configurable con colores y texto custom
- [ ] Todo texto UI usa `$this->t()`, `{% trans %}`, o `Drupal.t()`
- [ ] SCSS compilado sin errores con Dart Sass, usando `@use` y `var(--ej-*)`

### 15.2 Fase 2 — Gaps Doc 176

- [ ] Import CSV de redirects funciona (con validación de errores)
- [ ] Export CSV de redirects descarga archivo correcto
- [ ] API de breadcrumbs devuelve trail correcto desde root
- [ ] Límites por plan se aplican al crear páginas, nivelar profundidad y crear redirects
- [ ] Las 4 entidades existentes tienen Field UI y Views integration completa

### 15.3 Fase 3 — Blog Nativo

- [ ] Las 5 entidades del blog están creadas con Field UI, Views y formularios
- [ ] Los 21 endpoints API responden correctamente
- [ ] El listado de posts muestra grid responsive con filtros por categoría y búsqueda
- [ ] El post individual renderiza con los 4 layouts (standard, wide, fullwidth, sidebar)
- [ ] Crear/editar post abre en slide-panel desde la página `/blog`
- [ ] La publicación programada funciona via cron
- [ ] El RSS feed es válido y contiene los últimos 20 posts
- [ ] La página `/blog` usa template limpio sin regiones Drupal
- [ ] Body classes `page-blog` se aplican via `hook_preprocess_html()`

### 15.4 Fase 4 — SEO/GEO + IA

- [ ] Schema.org WebSite y Organization se inyectan en todas las páginas
- [ ] Schema.org BlogPosting se inyecta en cada post del blog
- [ ] BreadcrumbList schema se genera correctamente
- [ ] La generación IA de posts produce contenido coherente con título, meta, excerpt, contenido, keywords
- [ ] La optimización SEO analiza un post y devuelve score + issues + sugerencias
- [ ] Los hreflang tags se generan para idiomas habilitados
- [ ] La auditoría CWV devuelve score y issues accionables
- [ ] Rate limiting de 50 req/hora para endpoints IA funciona
- [ ] Failover multi-proveedor funciona (Anthropic → OpenAI)

---

## Apéndice A: Límites por Plan SaaS

### Site Structure Manager (Doc 176)

| Feature | Starter | Professional | Enterprise |
|---------|---------|--------------|-----------|
| Páginas en árbol | 10 | 50 | Ilimitado |
| Niveles de profundidad | 2 | 4 | Ilimitado |
| Redirects | 10 | 100 | Ilimitado |
| Sitemap XML | Básico | Con imágenes | Completo + news |
| Análisis SEO | — | Básico | Completo |
| Historial URLs | — | 30 días | 1 año |
| Import CSV | — | ✓ | ✓ |

### Global Navigation System (Doc 177)

| Feature | Starter | Professional | Enterprise |
|---------|---------|--------------|-----------|
| Tipos de header | 2 (standard, minimal) | 5 (todos) | Todos + custom |
| Menús custom | 1 | 5 | Ilimitado |
| Items por menú | 10 | 50 | Ilimitado |
| Profundidad menú | 1 | 2 | 3 |
| Mega menus | — | ✓ | ✓ |
| Top bar | — | ✓ | ✓ |
| Columnas footer | 2 | 4 | Ilimitado |
| Newsletter | — | ✓ | ✓ |
| CSS custom | — | — | ✓ |

### Blog System (Doc 178)

| Feature | Starter | Professional | Enterprise |
|---------|---------|--------------|-----------|
| Posts | 20 | 200 | Ilimitado |
| Categorías | 5 | 20 | Ilimitado |
| Autores | 1 | 5 | Ilimitado |
| Imágenes por post | 3 | 10 | Ilimitado |
| Publicación programada | — | ✓ | ✓ |
| Posts destacados | 1 | 5 | Ilimitado |
| RSS Feed | ✓ | ✓ | ✓ |
| Búsqueda fulltext | — | ✓ | ✓ |
| Bloques Page Builder | — | ✓ | ✓ |
| Múltiples autores | — | — | ✓ |

### SEO/GEO + IA (Doc 179)

| Feature | Starter | Professional | Enterprise |
|---------|---------|--------------|-----------|
| Schema.org básico | ✓ | ✓ | ✓ |
| Schema.org LocalBusiness | — | ✓ | ✓ |
| IA sugerir estructura | 1x | 5/mes | Ilimitado |
| IA generar posts | — | 10/mes | 100/mes |
| IA optimizar SEO | — | 20/mes | Ilimitado |
| Hreflang automático | — | ✓ | ✓ |
| Core Web Vitals audit | — | ✓ | ✓ |

---

## Apéndice B: Glosario Técnico

| Término | Definición |
|---------|-----------|
| **Content Entity** | Entidad de Drupal almacenada en BD con soporte para Field UI, Views, revisiones y CRUD estándar |
| **Field UI** | Interfaz de Drupal para añadir/quitar campos a entidades sin código |
| **Views** | Sistema de consultas visuales de Drupal para crear listados, filtros y exportaciones |
| **Slide-Panel** | Modal off-canvas que se desliza desde la derecha, usado para CRUD sin abandonar la página |
| **Design Tokens** | Variables CSS/SCSS que definen la identidad visual (colores, tipografía, spacing) configurables por tenant |
| **Federated Design Tokens** | Patrón donde el core define tokens SSOT y los módulos satélite solo consumen via `var(--ej-*)` |
| **Schema.org** | Vocabulario estándar para structured data en la web, usado por Google, Bing, etc. |
| **Hreflang** | Atributo HTML que indica el idioma y la región geográfica de una página alternativa |
| **CWV** | Core Web Vitals: métricas de Google para medir experiencia de usuario (LCP, FID, CLS) |
| **RSS** | Really Simple Syndication: formato XML para distribución de contenido web |
| **BEM** | Block Element Modifier: convención de nomenclatura CSS usada en el proyecto |
| **SSOT** | Single Source of Truth: punto único de verdad para configuraciones |

---

> **Documento generado el**: 2026-02-12
> **Próxima revisión**: Tras aprobación, antes del inicio de cada fase
> **Contacto técnico**: Equipo JarabaImpactPlatformSaaS
