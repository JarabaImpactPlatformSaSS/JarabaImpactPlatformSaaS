# Plan de Implementacion: Consolidacion y Elevacion Clase Mundial del Content Hub + Blog

**Fecha de creacion:** 2026-02-26 14:00
**Ultima actualizacion:** 2026-02-26 14:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion / Consolidacion / Elevacion Clase Mundial
**Codigo:** AUD-CH-001_Consolidacion_ContentHub_Blog
**Documentos fuente:** Auditoria exhaustiva Content Hub + Blog (24+ hallazgos), Arquitectura IA Nivel 5

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Inventario de Hallazgos de Auditoria](#2-inventario-de-hallazgos-de-auditoria)
3. [Tabla de Correspondencia con Especificaciones Tecnicas](#3-tabla-de-correspondencia-con-especificaciones-tecnicas)
4. [Tabla de Cumplimiento de Directrices del Proyecto](#4-tabla-de-cumplimiento-de-directrices-del-proyecto)
5. [Requisitos Previos](#5-requisitos-previos)
6. [Entorno de Desarrollo](#6-entorno-de-desarrollo)
7. [Arquitectura de la Consolidacion](#7-arquitectura-de-la-consolidacion)
   - 7.1 [Decision: jaraba_content_hub como sistema canonico](#71-decision-jaraba_content_hub-como-sistema-canonico)
   - 7.2 [Campos a anadir a ContentArticle (backport de BlogPost)](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost)
   - 7.3 [Campos a anadir a ContentCategory (backport de BlogCategory)](#73-campos-a-anadir-a-contentcategory-backport-de-blogcategory)
   - 7.4 [Nueva entidad ContentAuthor (backport de BlogAuthor)](#74-nueva-entidad-contentauthor-backport-de-blogauthor)
   - 7.5 [Campos a modificar en ContentArticle](#75-campos-a-modificar-en-contentarticle)
   - 7.6 [Update hooks para schema migration](#76-update-hooks-para-schema-migration)
   - 7.7 [Migracion de datos blog_post a content_article](#77-migracion-de-datos-blog_post-a-content_article)
   - 7.8 [ContentArticleAccessControlHandler con tenant isolation (S1 fix)](#78-contentarticleaccesscontrolhandler-con-tenant-isolation-s1-fix)
   - 7.9 [ContentCategoryAccessControlHandler con tenant isolation (S2 fix)](#79-contentcategoryaccesscontrolhandler-con-tenant-isolation-s2-fix)
   - 7.10 [ContentAuthorAccessControlHandler (nuevo)](#710-contentauthoraccesscontrolhandler-nuevo)
   - 7.11 [Permisos nuevos y actualizados](#711-permisos-nuevos-y-actualizados)
   - 7.12 [Estructura de archivos completa](#712-estructura-de-archivos-completa)
8. [Servicios — Consolidacion y Elevacion](#8-servicios--consolidacion-y-elevacion)
   - 8.1 [ArticleService: ampliar con funcionalidades de BlogService](#81-articleservice-ampliar-con-funcionalidades-de-blogservice)
   - 8.2 [CategoryService: ampliar](#82-categoryservice-ampliar)
   - 8.3 [SeoService (consolidado)](#83-seoservice-consolidado)
   - 8.4 [RssService (nuevo, backport de BlogRssService)](#84-rssservice-nuevo-backport-de-blogrssservice)
   - 8.5 [WritingAssistantService (existente, sin cambios)](#85-writingassistantservice-existente-sin-cambios)
   - 8.6 [RecommendationService (existente, verificar integracion)](#86-recommendationservice-existente-verificar-integracion)
9. [Controladores — Consolidacion](#9-controladores--consolidacion)
   - 9.1 [BlogController: ampliar con funcionalidades de BlogFrontendController](#91-blogcontroller-ampliar)
   - 9.2 [AuthorController (nuevo)](#92-authorcontroller-nuevo)
   - 9.3 [RssController (nuevo)](#93-rsscontroller-nuevo)
   - 9.4 [DashboardFrontendController: integrar stats completas](#94-dashboardfrontendcontroller-integrar-stats-completas)
   - 9.5 [REST API: ampliar con endpoints de autores](#95-rest-api-ampliar-con-endpoints-de-autores)
10. [Arquitectura Frontend](#10-arquitectura-frontend)
    - 10.1 [Zero Region Policy](#101-zero-region-policy)
    - 10.2 [Templates a crear/modificar](#102-templates-a-crearmodificar)
    - 10.3 [SCSS: Federated Design Tokens](#103-scss-federated-design-tokens)
    - 10.4 [Iconos: jaraba_icon() en PHP, icon Twig function en templates](#104-iconos)
    - 10.5 [JavaScript](#105-javascript)
    - 10.6 [Body classes via hook_preprocess_html()](#106-body-classes)
    - 10.7 [Mobile-first responsive](#107-mobile-first-responsive)
    - 10.8 [Modales slide-panel para CRUD](#108-modales-slide-panel-para-crud)
11. [Internacionalizacion (i18n)](#11-internacionalizacion-i18n)
12. [Seguridad](#12-seguridad)
13. [Integracion con Ecosistema](#13-integracion-con-ecosistema)
14. [Configuracion Administrable via Drupal UI](#14-configuracion-administrable-via-drupal-ui)
15. [Navegacion Drupal Admin](#15-navegacion-drupal-admin)
16. [Plan de Deprecacion de jaraba_blog](#16-plan-de-deprecacion-de-jaraba_blog)
17. [Fases de Implementacion](#17-fases-de-implementacion)
18. [Estrategia de Testing](#18-estrategia-de-testing)
19. [Verificacion y Despliegue](#19-verificacion-y-despliegue)
20. [Troubleshooting](#20-troubleshooting)
21. [Referencias Cruzadas](#21-referencias-cruzadas)
22. [Registro de Cambios](#22-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Consolidacion de **dos sistemas de blog incompatibles** (`jaraba_content_hub` y `jaraba_blog`) en un unico sistema canonico de clase mundial. Se backportean las fortalezas de `jaraba_blog` (tenant isolation real, entidad autor, RSS, SEO completo, view tracking, publicacion programada) al sistema canonico `jaraba_content_hub`, que ya posee Canvas Editor, AI Writing, Dashboard frontend, 13 templates, y 3647 lineas SCSS.

### 1.2 Por que se implementa (hallazgos de auditoria)

La auditoria exhaustiva del Content Hub y Blog revelo **24+ hallazgos** organizados en 5 niveles de severidad:

| Nivel | Hallazgos | Descripcion |
|-------|-----------|-------------|
| **S1-S4** | 4 | Seguridad critica: tenant isolation ausente en access handlers |
| **B1-B4** | 4 | Bugs criticos: campo `$text` indefinido, clase `ContentWriterAgent` inexistente, entidad `reading_history` inexistente, cron ausente |
| **A1-A4** | 4 | Arquitectura: colision de rutas `/blog`, colision CSS `.blog-pagination`, entidades incompatibles entre modulos |
| **D1-D6** | 6 | Directrices: ENTITY-PREPROCESS-001, ROUTE-LANGPREFIX-001, i18n, WCAG, ICON-CONVENTION-001, accessCheck bypass |
| **G1-G10** | 10 | Clase mundial: campo vertical ausente, SEO/imagen/status/owner en categorias, servicios incompletos, image styles faltantes |

### 1.3 Alcance

- Consolidar `jaraba_content_hub` como sistema unico de blog/contenido
- Backportear 9 capacidades de `jaraba_blog` que content_hub carece
- Crear entidad `ContentAuthor` (autor editorial independiente de usuario Drupal)
- Migrar `tenant_id` de integer a `entity_reference` apuntando a group
- Corregir los 4 problemas de seguridad (S1-S4)
- Corregir los 4 bugs criticos (B1-B4)
- Resolver las 4 colisiones de arquitectura (A1-A4)
- Cumplir las 6 violaciones de directrices (D1-D6)
- Cerrar las 10 brechas de clase mundial (G1-G10)
- Deprecar `jaraba_blog` con plan de migracion de datos

### 1.4 Filosofia de implementacion

- **Consolidar, no duplicar:** `jaraba_content_hub` absorbe las fortalezas de `jaraba_blog`
- **Zero-Region Policy:** Templates Twig sin `{{ page.content }}` ni bloques Drupal
- **Tenant-first:** Todo el contenido se acopla a un tenant (group) via TENANT-BRIDGE-001
- **Premium Forms:** Todas las entidades extienden `PremiumEntityFormBase`
- **Mobile-first:** Base styles + `min-width` media queries
- **i18n completo:** `{% trans %}` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS
- **Presave resilience:** Servicios opcionales envueltos en try-catch

### 1.5 Estimacion

| Fase | Descripcion | Horas Min | Horas Max |
|------|-------------|-----------|-----------|
| 1 | Entity Schema (campos nuevos + update hooks + access handlers) | 8 | 12 |
| 2 | Services Consolidation (ArticleService, SeoService, RssService) | 6 | 10 |
| 3 | Controllers + Routes (BlogController ampliado, author routes, RSS) | 6 | 8 |
| 4 | Templates + SCSS (author page, article detail, partials) | 8 | 12 |
| 5 | Security Fixes (S1-S4 tenant isolation, CSRF, accessCheck) | 4 | 6 |
| 6 | Directive Compliance (D1-D6 fixes) | 4 | 6 |
| 7 | World-Class Gaps (G1-G10) | 6 | 10 |
| 8 | Data Migration + jaraba_blog Deprecation | 4 | 6 |
| 9 | Testing + QA | 6 | 8 |
| **TOTAL** | | **52** | **78** |

### 1.6 Riesgos y mitigacion

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Datos en produccion en `blog_post` que necesitan migracion | Media | Alto | Script de migracion idemptotente con rollback, ejecutar en staging primero |
| Colision de rutas `/blog` entre modulos | Alta | Critico | Deprecar jaraba_blog primero, redirigir rutas, limpiar router cache |
| tenant_id migration de integer a entity_reference rompe queries existentes | Alta | Alto | Update hook con batch processing, backward compat con integer 0 |
| Canvas Editor deja de funcionar tras migracion de campos | Baja | Alto | Tests de regresion para canvas flow, no modificar campos canvas existentes |
| Perdida de SEO juice al cambiar URLs | Baja | Medio | Mantener URLs identicas `/blog/{slug}`, no cambiar estructura |

---

## 2. Inventario de Hallazgos de Auditoria

### 2.1 Seguridad (S1-S4)

#### S1: ContentArticleAccessControlHandler — tenant_id como integer no funciona multi-tenant real

**Estado actual:** `ContentArticleAccessControlHandler` tiene `checkTenantIsolation()` pero `tenant_id` es un campo `integer` con default 0. La comparacion `(int) $entity->get('tenant_id')->value` lee el valor del campo integer, pero `TenantContextService::getCurrentTenant()` devuelve un `GroupInterface` cuyo `id()` es el ID del Group, NO un tenant ID entero sin contexto.

**Problema:** Si el campo es integer con valor 0, el check hace `$entityTenantId === 0` y retorna `NULL` (skip) — es decir, **NUNCA se verifica el tenant para articulos existentes** porque `tenant_id` siempre es 0 por defecto.

**Remediacion:** Migrar `tenant_id` de `integer` a `entity_reference` apuntando a `group`. Auto-asignar en presave. El access handler compara `$entity->get('tenant_id')->target_id` con `$currentTenant->id()`.

**Codigo afectado:**
- `jaraba_content_hub/src/Entity/ContentArticle.php` linea 534-540
- `jaraba_content_hub/src/ContentArticleAccessControlHandler.php` linea 129

---

#### S2: ContentCategoryAccessControlHandler — sin verificacion de tenant

**Estado actual:** `ContentCategoryAccessControlHandler` permite `view` sin restricciones y `update/delete` solo requiere `administer content categories`. No hay NINGUN check de tenant_id.

**Problema:** Un administrador de tenant A puede editar/eliminar categorias de tenant B si conoce el ID.

**Remediacion:** Anadir `checkTenantIsolation()` en operaciones `update` y `delete`, siguiendo el patron de `ContentArticleAccessControlHandler` pero con `entity_reference` a group.

**Codigo afectado:**
- `jaraba_content_hub/src/ContentCategoryAccessControlHandler.php` lineas 32-48

---

#### S3: BlogPostAccessControlHandler — sin verificacion de tenant

**Estado actual:** Aunque BlogPost tiene `tenant_id` como `entity_reference` a group correctamente, su `BlogPostAccessControlHandler` no verifica tenant en ninguna operacion.

**Problema:** Cualquier usuario con `manage blog posts` puede editar posts de cualquier tenant.

**Remediacion:** Se resuelve indirectamente al deprecar `jaraba_blog`. Las queries del `BlogService` ya filtran por tenant via `$this->getTenantId()`, pero el access handler no lo hace.

---

#### S4: BlogCategoryAccessControlHandler y BlogAuthorAccessControlHandler — sin tenant

**Estado actual:** Misma situacion que S3. Access handlers sin verificacion de tenant.

**Remediacion:** Se resuelve al deprecar `jaraba_blog`.

---

### 2.2 Bugs criticos (B1-B4)

#### B1: Campo `$text` indefinido en ContentWriterAgent

**Descripcion:** `ContentWriterAgent` referencia una variable `$text` que no esta definida en el scope de `doExecute()`. El campo correcto es `$input['content']` o `$input['topic']`.

**Impacto:** Error fatal al ejecutar el AI Writing Assistant.

**Remediacion:** Verificar y corregir las referencias de variables en `ContentWriterAgent::doExecute()`.

---

#### B2: Clase ContentWriterAgent — herencia incorrecta

**Descripcion:** `ContentWriterAgent` extiende `BaseAgent` (Gen 1) pero el modulo la registra en `services.yml` con argumentos de Gen 2 (`SmartBaseAgent`). Los constructores son incompatibles.

**Impacto:** El servicio `jaraba_content_hub.content_writer_agent` falla al instanciarse.

**Remediacion:** Migrar `ContentWriterAgent` a Gen 2 (`SmartBaseAgent`) o ajustar los argumentos del servicio para coincidir con `BaseAgent`.

---

#### B3: Entidad `reading_history` referenciada pero inexistente

**Descripcion:** Algunos hooks de `jaraba_content_hub.module` referencian `reading_history` para tracking de lectura, pero esta entidad no existe en el modulo.

**Impacto:** Errores PHP en runtime cuando se intenta cargar la entidad inexistente.

**Remediacion:** Eliminar las referencias a `reading_history` y reemplazar con el campo `views_count` en ContentArticle (backport de BlogPost).

---

#### B4: Cron para publicacion programada ausente

**Descripcion:** ContentArticle tiene un campo `publish_date` y estado `scheduled`, pero no hay `hook_cron()` que procese los articulos programados. `jaraba_blog` tiene `BlogService::publishScheduledPosts()` pero tampoco tiene `hook_cron` implementado.

**Impacto:** Los articulos programados NUNCA se publican automaticamente.

**Remediacion:** Implementar `jaraba_content_hub_cron()` que llame a `ArticleService::publishScheduledArticles()`.

---

### 2.3 Arquitectura (A1-A4)

#### A1: Colision de rutas `/blog`

**Descripcion:** Ambos modulos registran la ruta `/blog`:
- `jaraba_content_hub.blog` → `/blog` → `BlogController::index()`
- `jaraba_blog.listing` → `/blog` → `BlogFrontendController::listing()`

**Impacto:** Drupal resuelve la primera ruta registrada. Si ambos modulos estan habilitados, hay conflicto.

**Remediacion:** `jaraba_content_hub` mantiene `/blog` (canonico). `jaraba_blog` se depreca y sus rutas dejan de existir.

---

#### A2: Colision CSS `.blog-pagination`

**Descripcion:** Ambos modulos definen estilos para `.blog-pagination` con reglas incompatibles. El tema principal (`_content-hub.scss`) tiene su version, y `jaraba_blog/css/blog.css` tiene otra.

**Remediacion:** Consolidar en `_content-hub.scss` unico. Eliminar `jaraba_blog/css/blog.css` al deprecar.

---

#### A3: Entidades incompatibles entre modulos

**Descripcion:** `BlogPost` y `ContentArticle` representan el mismo concepto pero con campos y tablas diferentes:

| Aspecto | ContentArticle | BlogPost |
|---------|---------------|----------|
| Tabla | `content_article` + `content_article_field_data` | `blog_post` |
| Traducible | Si (data_table) | No |
| tenant_id | integer (default 0) | entity_reference a group |
| Categoria | `category` → content_category | `category_id` → blog_category |
| Autor | `author` (uid via EntityOwnerTrait) | `author_id` → blog_author |
| Tags | No tiene | `tags` (string_long, comma-separated) |
| SEO | `seo_title`, `seo_description`, `answer_capsule` | `meta_title`, `meta_description`, `og_image`, `schema_type` |
| Canvas | Si (layout_mode, canvas_data, rendered_html) | No |
| AI | Si (ai_generated, sentiment, engagement) | No |
| Views | No tiene | `views_count` |
| Featured | No tiene | `is_featured` |
| Scheduled | `publish_date` | `scheduled_at` + `published_at` |

**Remediacion:** Backportear los campos faltantes de BlogPost a ContentArticle (ver seccion 7.2).

---

#### A4: Servicios duplicados e incompletos

**Descripcion:** `ArticleService` de content_hub tiene 7 metodos. `BlogService` de jaraba_blog tiene 15+ metodos incluyendo funcionalidades que ArticleService carece:
- `getRelatedPosts()` — content_hub usa RecommendationService con Qdrant (overkill para related)
- `getAdjacentPosts()` — no existe en content_hub
- `trackView()` — no existe en content_hub
- `publishScheduledPosts()` — no existe en content_hub
- `getPopularPosts()` — content_hub usa `engagement_score`, no `views_count`
- `getAllTags()` — no existe en content_hub
- `listCategories()` / `listAuthors()` — en servicios separados

**Remediacion:** Ampliar `ArticleService` con metodos de `BlogService`. Mantener `CategoryService` separado.

---

### 2.4 Cumplimiento directrices (D1-D6)

#### D1: ENTITY-PREPROCESS-001 — preprocess de content_author ausente

**Descripcion:** La nueva entidad `content_author` necesitara `template_preprocess_content_author()` en el `.module` file.

**Remediacion:** Implementar preprocess hook con extraccion de primitivas, avatar URL, social links.

---

#### D2: ROUTE-LANGPREFIX-001 — URLs hardcodeadas en JS/templates

**Descripcion:** `BlogSeoService` construye URLs con `$baseUrl . '/blog/' . $slug` (hardcoded). El sitio usa `/es/` como prefijo de idioma — URLs hardcodeadas causan 404 o redirects que pierden POST body.

**Remediacion:** Usar `Url::fromRoute()` en PHP y `Drupal.url()` en JS para generar todas las URLs.

---

#### D3: i18n incompleto

**Descripcion:** Varios strings en templates y controladores no usan mecanismos de traduccion:
- Strings literales en `BlogSeoService` sin `$this->t()`
- Labels en templates sin `{% trans %}`

**Remediacion:** Envolver todos los strings de UI en mecanismos de traduccion.

---

#### D4: WCAG — focus-visible ausente en componentes nuevos

**Descripcion:** Los nuevos componentes (author page, share buttons, pagination) necesitan outlines `:focus-visible` y soporte `prefers-reduced-motion`.

**Remediacion:** Agregar `:focus-visible` outlines y `@media (prefers-reduced-motion: reduce)` en SCSS.

---

#### D5: ICON-CONVENTION-001 — iconos no usan jaraba_icon()

**Descripcion:** Algunos templates usan SVG inline o clases CSS para iconos en lugar de `jaraba_icon()`.

**Remediacion:** Reemplazar con `{{ jaraba_icon('category', 'name') }}` en Twig o `jaraba_icon()` en PHP.

---

#### D6: accessCheck bypass en entity queries

**Descripcion:** `BlogService` usa `accessCheck(FALSE)` en TODAS las queries. Esto bypasea el sistema de acceso de Drupal. El servicio filtra por `tenant_id` manualmente, pero no respeta permisos de usuario.

**Remediacion:** En content_hub, usar `accessCheck(TRUE)` en queries publicas. Solo usar `accessCheck(FALSE)` en queries administrativas internas (cron, migration).

---

### 2.5 Brechas clase mundial (G1-G10)

| ID | Brecha | Descripcion | Remediacion |
|----|--------|-------------|-------------|
| G1 | Campo `vertical` ausente | Las entidades no indican a que vertical pertenecen (VERTICAL-CANONICAL-001) | Anadir campo `vertical` (list_string) con los 10 valores canonicos |
| G2 | SEO incompleto en content_hub | Solo tiene `generateArticleSchema()` basico. Falta OG, Twitter, canonical, listing SEO | Backportear logica completa de `BlogSeoService` |
| G3 | Imagen en categorias | `ContentCategory` no tiene campo de imagen destacada | Anadir `featured_image` (entity_reference a file) |
| G4 | Status en categorias | `ContentCategory` no tiene `is_active` flag | Anadir `is_active` (boolean, default TRUE) |
| G5 | Owner en categorias | `ContentCategory` no implementa EntityOwnerInterface | No se requiere owner — las categorias son globales por tenant |
| G6 | `posts_count` en categorias | `ContentCategory` no tiene cache de conteo de posts | Anadir `posts_count` (integer, default 0) + `updatePostsCount()` en CategoryService |
| G7 | Image styles faltantes | Config YAML existe pero no esta instalada en el sistema | Verificar `drush entity:updates` y `config:import` |
| G8 | RSS feed ausente | `jaraba_content_hub` no tiene feed RSS | Crear `RssService` backporteando `BlogRssService` |
| G9 | View tracking ausente | No hay forma de contar visitas de articulos | Anadir campo `views_count` + `trackView()` con DB expression |
| G10 | Publicacion programada rota | `hook_cron` no implementado | Implementar cron + `publishScheduledArticles()` |

---

## 3. Tabla de Correspondencia con Especificaciones Tecnicas

### 3.1 Funcionalidades backporteadas de jaraba_blog

| Funcionalidad (jaraba_blog) | Fuente | Destino (jaraba_content_hub) | Seccion del Plan |
|------------------------------|--------|------------------------------|------------------|
| `tenant_id` como entity_reference a group | BlogPost campo | ContentArticle campo migrado | [7.5](#75-campos-a-modificar-en-contentarticle) |
| BlogAuthor como entidad dedicada | BlogAuthor.php completo | ContentAuthor.php nuevo | [7.4](#74-nueva-entidad-contentauthor-backport-de-blogauthor) |
| Tags (comma-separated) | BlogPost.tags | ContentArticle.tags nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| featured_image_alt | BlogPost.featured_image_alt | ContentArticle.featured_image_alt nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| is_featured flag | BlogPost.is_featured | ContentArticle.is_featured nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| views_count | BlogPost.views_count | ContentArticle.views_count nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| schema_type (BlogPosting/Article/NewsArticle) | BlogPost.schema_type | ContentArticle.schema_type nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| og_image | BlogPost.og_image | ContentArticle.og_image nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| scheduled_at | BlogPost.scheduled_at | ContentArticle.scheduled_at nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| published_at | BlogPost.published_at | ContentArticle.published_at nuevo | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| View tracking (DB expression) | BlogService.trackView() | ArticleService.trackView() | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| Related posts | BlogService.getRelatedPosts() | ArticleService.getRelatedArticles() | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| Adjacent posts (prev/next) | BlogService.getAdjacentPosts() | ArticleService.getAdjacentArticles() | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| Scheduled publishing cron | BlogService.publishScheduledPosts() | ArticleService.publishScheduledArticles() | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| Popular posts by views | BlogService.getPopularPosts() | ArticleService.getPopularArticles() | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| Tag aggregation | BlogService.getAllTags() | ArticleService.getAllTags() | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| Category posts count update | BlogService.updateCategoryPostsCount() | CategoryService.updatePostsCount() | [8.2](#82-categoryservice-ampliar) |
| BlogSeoService completo | BlogSeoService.php | SeoService consolidado | [8.3](#83-seoservice-consolidado) |
| BlogRssService | BlogRssService.php | RssService nuevo | [8.4](#84-rssservice-nuevo-backport-de-blogrssservice) |
| Category page controller | BlogFrontendController.category() | BlogController.category() | [9.1](#91-blogcontroller-ampliar) |
| Author page controller | BlogFrontendController.author() | AuthorController.view() | [9.2](#92-authorcontroller-nuevo) |
| RSS controller | BlogRssController.feed() | RssController.feed() | [9.3](#93-rsscontroller-nuevo) |
| Blog templates (partials) | 9 templates | Parciales mejorados | [10.2](#102-templates-a-crearmodificar) |
| Category meta tags (SEO) | BlogCategory campos | ContentCategory campos nuevos | [7.3](#73-campos-a-anadir-a-contentcategory-backport-de-blogcategory) |

### 3.2 Funcionalidades existentes en jaraba_content_hub (mantener)

| Funcionalidad | Archivo | Estado |
|---------------|---------|--------|
| Canvas Editor (GrapesJS) | ArticleCanvasEditorController, ArticleCanvasApiController | Mantener sin cambios |
| AI Writing Assistant | WritingAssistantService, WritingAssistantController | Mantener, verificar B1-B2 |
| Recommendation Engine (Qdrant) | RecommendationService, ContentEmbeddingService | Mantener sin cambios |
| Dashboard frontend | ContentHubDashboardController, template | Ampliar con stats |
| Slug-based routing | ContentArticleSlugConverter | Mantener sin cambios |
| Answer Capsule (GEO) | ContentArticle.answer_capsule | Mantener sin cambios |
| Sentiment analysis | SentimentEngineService | Mantener sin cambios |
| Newsletter bridge | NewsletterBridgeService | Mantener sin cambios |
| 13 templates existentes | templates/ | Ampliar, no reemplazar |
| SCSS 3647 lineas | _content-hub.scss | Ampliar, no reemplazar |

### 3.3 Funcionalidades nuevas (clase mundial)

| Funcionalidad | Descripcion | Seccion |
|---------------|-------------|---------|
| ContentAuthor entity | Autor editorial separado de usuario Drupal | [7.4](#74-nueva-entidad-contentauthor-backport-de-blogauthor) |
| Author page frontend | Pagina publica `/blog/autor/{slug}` | [9.2](#92-authorcontroller-nuevo) |
| RSS feed | Feed RSS 2.0 per-tenant | [8.4](#84-rssservice-nuevo-backport-de-blogrssservice) |
| Complete SEO (OG + Twitter + JSON-LD) | Meta tags, Open Graph, Twitter Cards, Schema.org | [8.3](#83-seoservice-consolidado) |
| Scheduled publishing cron | Publicacion automatica via cron | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| View tracking | Contador de visitas con DB expression | [8.1](#81-articleservice-ampliar-con-funcionalidades-de-blogservice) |
| Campo vertical | Clasificacion por vertical del ecosistema | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |

### 3.4 Mapping de entidades: BlogPost → ContentArticle, BlogCategory → ContentCategory

| Campo BlogPost | Campo ContentArticle | Transformacion |
|---------------|---------------------|----------------|
| `id` | `id` | Auto-generado nuevo |
| `tenant_id` (entity_ref → group) | `tenant_id` (entity_ref → group) | Migrar: `target_id` → `target_id` |
| `title` | `title` | Directo |
| `slug` | `slug` | Directo |
| `excerpt` (string_long) | `excerpt` (text_long) | Directo (compatible) |
| `body` (text_long) | `body` (text_long) | Directo |
| `featured_image` → file | `featured_image` → file | Copiar file entity ref |
| `featured_image_alt` | `featured_image_alt` (NUEVO) | Directo |
| `category_id` → blog_category | `category` → content_category | Requiere mapping de categorias |
| `tags` | `tags` (NUEVO) | Directo |
| `author_id` → blog_author | `content_author` (NUEVO) | Requiere mapping de autores |
| `status` (draft/published/scheduled/archived) | `status` (draft/review/scheduled/published/archived) | Directo (valores compatibles) |
| `published_at` | `published_at` (NUEVO) | Directo |
| `scheduled_at` | `scheduled_at` (NUEVO) | Directo |
| `is_featured` | `is_featured` (NUEVO) | Directo |
| `reading_time` | `reading_time` | Directo |
| `meta_title` | `seo_title` | Renombrar |
| `meta_description` | `seo_description` | Renombrar |
| `og_image` | `og_image` (NUEVO) | Copiar file entity ref |
| `schema_type` | `schema_type` (NUEVO) | Directo |
| `views_count` | `views_count` (NUEVO) | Directo |
| N/A | `answer_capsule` | Ya existe en content_hub |
| N/A | `canvas_data` / `rendered_html` / `layout_mode` | Ya existe en content_hub |
| N/A | `ai_generated` / `sentiment_*` / `engagement_score` | Ya existe en content_hub |

### 3.5 Mapping de servicios

| Servicio jaraba_blog | Servicio jaraba_content_hub | Notas |
|---------------------|----------------------------|-------|
| `jaraba_blog.blog` (BlogService) | `jaraba_content_hub.article_service` (ArticleService) | Ampliar con metodos de BlogService |
| `jaraba_blog.seo` (BlogSeoService) | `jaraba_content_hub.seo_service` (SeoService) | Reescribir con logica completa |
| `jaraba_blog.rss` (BlogRssService) | `jaraba_content_hub.rss_service` (RssService) | Crear nuevo |
| N/A | `jaraba_content_hub.category_service` (CategoryService) | Ya existe, ampliar |
| N/A | `jaraba_content_hub.writing_assistant` | Ya existe, mantener |
| N/A | `jaraba_content_hub.recommendation_service` | Ya existe, mantener |

### 3.6 Mapping de rutas

| Ruta jaraba_blog | Ruta jaraba_content_hub | Accion |
|-----------------|------------------------|--------|
| `jaraba_blog.listing` → `/blog` | `jaraba_content_hub.blog` → `/blog` | content_hub mantiene, blog se elimina |
| `jaraba_blog.detail` → `/blog/{slug}` | `entity.content_article.canonical` → `/blog/{content_article}` | Unificar con slug converter |
| `jaraba_blog.category` → `/blog/categoria/{slug}` | `jaraba_content_hub.blog.category` → `/blog/categoria/{slug}` | NUEVA ruta en content_hub |
| `jaraba_blog.author` → `/blog/autor/{slug}` | `jaraba_content_hub.blog.author` → `/blog/autor/{slug}` | NUEVA ruta en content_hub |
| `jaraba_blog.rss` → `/blog/feed.xml` | `jaraba_content_hub.blog.rss` → `/blog/feed.xml` | NUEVA ruta en content_hub |

---

## 4. Tabla de Cumplimiento de Directrices del Proyecto

| # | Directriz | Estado Actual | Objetivo | Donde se Aplica | Seccion Plan |
|---|-----------|--------------|----------|-----------------|--------------|
| 1 | TENANT-ISOLATION-ACCESS-001 | FALLA (S1-S4) | CUMPLE | ContentArticle, ContentCategory, ContentAuthor access handlers | [7.8-7.10](#78-contentarticleaccesscontrolhandler-con-tenant-isolation-s1-fix) |
| 2 | TENANT-BRIDGE-001 | FALLA (integer vs entity_ref) | CUMPLE | tenant_id migration a entity_reference → group | [7.5](#75-campos-a-modificar-en-contentarticle) |
| 3 | PREMIUM-FORMS-PATTERN-001 | CUMPLE parcial | CUMPLE | ContentAuthorForm nuevo extiende PremiumEntityFormBase | [7.4](#74-nueva-entidad-contentauthor-backport-de-blogauthor) |
| 4 | ENTITY-PREPROCESS-001 | CUMPLE (article, category) | CUMPLE | + content_author preprocess | [10.2](#102-templates-a-crearmodificar) |
| 5 | PRESAVE-RESILIENCE-001 | CUMPLE | CUMPLE | Mantener try-catch en presave hooks | [12](#12-seguridad) |
| 6 | ROUTE-LANGPREFIX-001 | FALLA (D2) | CUMPLE | Url::fromRoute() everywhere | [12](#12-seguridad) |
| 7 | ICON-CONVENTION-001 | FALLA (D5) | CUMPLE | jaraba_icon() en todos los templates nuevos | [10.4](#104-iconos) |
| 8 | TWIG-XSS-001 | CUMPLE | CUMPLE | \|escape por defecto, \|safe_html para sanitizado | [12](#12-seguridad) |
| 9 | CSRF-REQUEST-HEADER-001 | CUMPLE parcial | CUMPLE | Nuevas rutas API POST/PATCH/DELETE | [12](#12-seguridad) |
| 10 | API-WHITELIST-001 | CUMPLE parcial | CUMPLE | ALLOWED_FIELDS en nuevos endpoints | [12](#12-seguridad) |
| 11 | ZERO-REGION-POLICY | CUMPLE | CUMPLE | page--content-hub.html.twig sin cambios | [10.1](#101-zero-region-policy) |
| 12 | DART-SASS-MODERN | CUMPLE | CUMPLE | @use sass:color en SCSS nuevos | [10.3](#103-scss-federated-design-tokens) |
| 13 | MOBILE-FIRST | CUMPLE | CUMPLE | Base styles + min-width queries | [10.7](#107-mobile-first-responsive) |
| 14 | CONFIG-SCHEMA-001 | VERIFICAR | CUMPLE | Schema para nuevos settings | [14](#14-configuracion-administrable-via-drupal-ui) |
| 15 | i18n | FALLA parcial (D3) | CUMPLE | trans/t()/Drupal.t() en todos los textos | [11](#11-internacionalizacion-i18n) |
| 16 | WCAG-FOCUS-001 | FALLA parcial (D4) | CUMPLE | :focus-visible en todos los componentes nuevos | [10.3](#103-scss-federated-design-tokens) |
| 17 | SLIDE-PANEL-RENDER-001 | CUMPLE | CUMPLE | renderPlain() en modales nuevos | [10.8](#108-modales-slide-panel-para-crud) |
| 18 | SCSS-VARS-SSOT-001 | CUMPLE | CUMPLE | var(--ej-*) consumption | [10.3](#103-scss-federated-design-tokens) |
| 19 | INNERHTML-XSS-001 | CUMPLE | CUMPLE | Drupal.checkPlain() en JS | [12](#12-seguridad) |
| 20 | LABEL-NULLSAFE-001 | CUMPLE | CUMPLE | ContentAuthor entity_keys.label = display_name | [7.4](#74-nueva-entidad-contentauthor-backport-de-blogauthor) |
| 21 | VERTICAL-CANONICAL-001 | FALLA (G1) | CUMPLE | +campo vertical en entities | [7.2](#72-campos-a-anadir-a-contentarticle-backport-de-blogpost) |
| 22 | SMART-AGENT-CONSTRUCTOR-001 | N/A | N/A | No aplica (no hay agentes nuevos) | N/A |
| 23 | AI-IDENTITY-001 | N/A | CUMPLE si AI | AIIdentityRule::apply() en AI writing | [13](#13-integracion-con-ecosistema) |
| 24 | FORM-CACHE-001 | CUMPLE | CUMPLE | No setCached(TRUE) incondicional | [10.8](#108-modales-slide-panel-para-crud) |
| 25 | SERVICE-CALL-CONTRACT-001 | VERIFICAR | CUMPLE | Verificar firmas de metodos en servicios ampliados | [8](#8-servicios--consolidacion-y-elevacion) |

---

## 5. Requisitos Previos

### 5.1 Software

| Componente | Version Requerida |
|-----------|------------------|
| PHP | 8.4+ |
| Drupal | 11.x |
| MariaDB | 10.11+ |
| Redis | 7.4 |
| Composer | 2.x |
| Lando | 3.x |
| Dart Sass | Moderno (@use syntax) |

### 5.2 Modulos Drupal requeridos

| Modulo | Proposito |
|--------|-----------|
| `ecosistema_jaraba_core` | TenantContextService, TenantBridgeService, PremiumEntityFormBase, jaraba_icon() |
| `group` | Multi-tenant content isolation |
| `jaraba_ai_agents` | BaseAgent, AIObservabilityService (para AI Writing) |
| `jaraba_rag` | ContentEmbeddingService (para recomendaciones) |
| `jaraba_page_builder` | GrapesJS engine (para Canvas Editor) |
| `drupal:text` | Campos text_long |
| `drupal:file` | Entidad file para imagenes |
| `drupal:datetime` | Campos de fecha |
| `drupal:views` | Views integration |
| `drupal:field_ui` | UI de campos |
| `drupal:options` | Campos list_string |

### 5.3 Infraestructura (Lando)

```yaml
# .lando.yml (ya configurado)
services:
  appserver:
    type: php:8.4
  database:
    type: mariadb:10.11
  cache:
    type: redis:7.4
```

### 5.4 Documentos de referencia

| Documento | Ubicacion |
|-----------|-----------|
| Directrices del Proyecto | `docs/00_DIRECTRICES_PROYECTO.md` (v82.0.0) |
| Documento Maestro Arquitectura | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` (v77.0.0) |
| Indice General | `docs/00_INDICE_GENERAL.md` (v107.0.0) |
| Flujo Trabajo Claude | `docs/00_FLUJO_TRABAJO_CLAUDE.md` (v36.0.0) |
| Blog Clase Mundial Plan | `docs/implementacion/2026-02-26_Blog_Clase_Mundial_Plan_Implementacion.md` |
| Auditoria IA Clase Mundial | `docs/implementacion/2026-02-26_Plan_Implementacion_Auditoria_IA_Clase_Mundial_v1.md` |

---

## 6. Entorno de Desarrollo

### 6.1 Comandos Docker/Lando

```bash
# Iniciar entorno
lando start

# Limpiar cache despues de cambios
lando drush cr

# Aplicar actualizaciones de entidades
lando drush entity:updates

# Ejecutar update hooks
lando drush updb

# Importar configuracion
lando drush config:import -y

# Ejecutar cron (para publicacion programada)
lando drush cron

# Compilar SCSS
lando npm run build --prefix web/themes/custom/ecosistema_jaraba_theme
```

### 6.2 URLs de verificacion

| URL | Proposito |
|-----|-----------|
| `https://jaraba-saas.lndo.site/es/blog` | Blog listing |
| `https://jaraba-saas.lndo.site/es/blog/{slug}` | Article detail |
| `https://jaraba-saas.lndo.site/es/blog/categoria/{slug}` | Category page |
| `https://jaraba-saas.lndo.site/es/blog/autor/{slug}` | Author page |
| `https://jaraba-saas.lndo.site/es/blog/feed.xml` | RSS feed |
| `https://jaraba-saas.lndo.site/es/content-hub` | Dashboard frontend |
| `https://jaraba-saas.lndo.site/es/content-hub/articles/{id}/canvas` | Canvas Editor |
| `https://jaraba-saas.lndo.site/es/admin/content/articles` | Admin articles list |

### 6.3 Comandos de cache y rebuild

```bash
# Rebuild completo
lando drush cr && lando drush entity:updates && lando drush router:rebuild

# Solo router (despues de anadir rutas)
lando drush router:rebuild

# Cache de templates
lando drush twig:debug
```

---

## 7. Arquitectura de la Consolidacion

### 7.1 Decision: jaraba_content_hub como sistema canonico

**Criterios de evaluacion:**

| Criterio | jaraba_content_hub | jaraba_blog |
|----------|-------------------|-------------|
| Templates | 13 | 9 |
| SCSS | 3,647 lineas | ~200 lineas |
| Routing | 446 lineas (48 rutas) | 185 lineas (~30 rutas) |
| Canvas Editor | Si (GrapesJS) | No |
| AI Writing | Si (5 actions) | No |
| Dashboard frontend | Si | No |
| Recommendation engine | Si (Qdrant) | No |
| Answer Capsule (GEO) | Si | No |
| Sentiment analysis | Si | No |
| Newsletter bridge | Si | No |
| Traducible | Si (data_table) | No |
| UX puntuacion | 98/100 | 22/100 |
| Tenant isolation real | No (integer 0) | Si (entity_reference → group) |
| Entidad Autor | No | Si (BlogAuthor) |
| SEO completo | Parcial (solo schema) | Completo (meta, OG, Twitter, JSON-LD) |
| RSS feed | No | Si |
| View tracking | No | Si |
| Publicacion programada | Campo existe, cron no | Campo + logica, cron falta |

**Decision:** `jaraba_content_hub` se consolida como sistema canonico. Las 9 capacidades de `jaraba_blog` se backportean.

### 7.2 Campos a anadir a ContentArticle (backport de BlogPost)

Los siguientes campos se anaden a `ContentArticle::baseFieldDefinitions()` via update hook:

```php
// 1. Tags (comma-separated) — backport de BlogPost.tags
$fields['tags'] = BaseFieldDefinition::create('string_long')
    ->setLabel(t('Etiquetas'))
    ->setDescription(t('Etiquetas separadas por coma.'))
    ->setTranslatable(TRUE)
    ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -3,
        'settings' => ['rows' => 2],
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// 2. featured_image_alt — backport de BlogPost.featured_image_alt
$fields['featured_image_alt'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Alt imagen destacada'))
    ->setDescription(t('Texto alternativo accesible para la imagen destacada.'))
    ->setTranslatable(TRUE)
    ->setSetting('max_length', 255)
    ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 3. is_featured — backport de BlogPost.is_featured
$fields['is_featured'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Destacado'))
    ->setDescription(t('Marcar como articulo destacado en portada.'))
    ->setDefaultValue(FALSE)
    ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 12,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 4. views_count — backport de BlogPost.views_count
$fields['views_count'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Visitas'))
    ->setDescription(t('Contador de visitas del articulo.'))
    ->setSetting('min', 0)
    ->setDefaultValue(0)
    ->setDisplayConfigurable('view', TRUE);

// 5. schema_type — backport de BlogPost.schema_type
$fields['schema_type'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Tipo Schema.org'))
    ->setDescription(t('Tipo de marcado estructurado para buscadores.'))
    ->setSetting('allowed_values', [
        'BlogPosting' => 'BlogPosting',
        'Article' => 'Article',
        'NewsArticle' => 'NewsArticle',
    ])
    ->setDefaultValue('BlogPosting')
    ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 22,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 6. og_image — backport de BlogPost.og_image
$fields['og_image'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Imagen Open Graph'))
    ->setDescription(t('Imagen para compartir en redes sociales.'))
    ->setSetting('target_type', 'file')
    ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 23,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 7. scheduled_at — backport de BlogPost.scheduled_at
$fields['scheduled_at'] = BaseFieldDefinition::create('datetime')
    ->setLabel(t('Publicacion programada'))
    ->setDescription(t('Fecha y hora para publicacion automatica.'))
    ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 13,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 8. published_at — backport de BlogPost.published_at
$fields['published_at'] = BaseFieldDefinition::create('datetime')
    ->setLabel(t('Fecha de publicacion'))
    ->setDescription(t('Fecha efectiva de publicacion.'))
    ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 14,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// 9. content_author — referencia a nueva entidad ContentAuthor
$fields['content_author'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Autor editorial'))
    ->setDescription(t('Perfil de autor editorial del articulo.'))
    ->setSetting('target_type', 'content_author')
    ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// 10. vertical — VERTICAL-CANONICAL-001
$fields['vertical'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Vertical'))
    ->setDescription(t('Vertical del ecosistema a la que pertenece este articulo.'))
    ->setSetting('allowed_values', [
        'empleabilidad' => 'Empleabilidad',
        'emprendimiento' => 'Emprendimiento',
        'comercioconecta' => 'Comercio Conecta',
        'agroconecta' => 'Agro Conecta',
        'jarabalex' => 'JarabaLex',
        'serviciosconecta' => 'Servicios Conecta',
        'andalucia_ei' => 'Andalucia EI',
        'jaraba_content_hub' => 'Content Hub',
        'formacion' => 'Formacion',
        'demo' => 'Demo',
    ])
    ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 24,
    ])
    ->setDisplayConfigurable('form', TRUE);
```

### 7.3 Campos a anadir a ContentCategory (backport de BlogCategory)

```php
// 1. featured_image — backport de BlogCategory (via featured image pattern)
$fields['featured_image'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Imagen destacada'))
    ->setDescription(t('Imagen de la categoria para la pagina de categoria.'))
    ->setSetting('target_type', 'file')
    ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 2,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// 2. meta_title — backport de BlogCategory.meta_title
$fields['meta_title'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Meta Title'))
    ->setDescription(t('Titulo SEO para la pagina de categoria (max 70 caracteres).'))
    ->setTranslatable(TRUE)
    ->setSetting('max_length', 70)
    ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 20,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 3. meta_description — backport de BlogCategory.meta_description
$fields['meta_description'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Meta Description'))
    ->setDescription(t('Descripcion SEO para la pagina de categoria (max 160 caracteres).'))
    ->setTranslatable(TRUE)
    ->setSetting('max_length', 160)
    ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 21,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 4. is_active — backport de BlogCategory.is_active
$fields['is_active'] = BaseFieldDefinition::create('boolean')
    ->setLabel(t('Activa'))
    ->setDescription(t('Indica si la categoria esta activa y visible.'))
    ->setDefaultValue(TRUE)
    ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 25,
    ])
    ->setDisplayConfigurable('form', TRUE);

// 5. posts_count — cache de conteo de posts
$fields['posts_count'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Articulos'))
    ->setDescription(t('Numero de articulos en esta categoria (cache).'))
    ->setDefaultValue(0)
    ->setDisplayConfigurable('view', TRUE);
```

### 7.4 Nueva entidad ContentAuthor (backport de BlogAuthor)

**Archivo:** `jaraba_content_hub/src/Entity/ContentAuthor.php`

**Justificacion:** Separar autor editorial de usuario Drupal. Permite:
- Bio, avatar, redes sociales del autor
- Slug para URL amigable `/blog/autor/{slug}`
- Conteo de posts
- Multiples autores sin cuentas Drupal

**Annotation:**
```php
/**
 * @ContentEntityType(
 *   id = "content_author",
 *   label = @Translation("Content Author"),
 *   label_collection = @Translation("Authors"),
 *   label_singular = @Translation("author"),
 *   label_plural = @Translation("authors"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_content_hub\ContentAuthorListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_content_hub\ContentAuthorAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\jaraba_content_hub\Form\ContentAuthorForm",
 *       "edit" = "Drupal\jaraba_content_hub\Form\ContentAuthorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "content_author",
 *   data_table = "content_author_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer content authors",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "display_name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/blog/autor/{content_author}",
 *     "add-form" = "/admin/content/content-authors/add",
 *     "edit-form" = "/admin/content/content-authors/{content_author}/edit",
 *     "delete-form" = "/admin/content/content-authors/{content_author}/delete",
 *     "collection" = "/admin/content/content-authors",
 *   },
 *   field_ui_base_route = "entity.content_author.settings",
 * )
 */
```

**Campos base:** Identicos a BlogAuthor con las siguientes adaptaciones:
- `tenant_id`: `entity_reference` a `group` (no integer)
- `user_id`: entity_reference a user (opcional)
- `display_name`: string(100), required, translatable
- `slug`: string(100), required, translatable
- `bio`: string_long, translatable
- `avatar`: entity_reference a file
- `social_twitter`: string(255)
- `social_linkedin`: string(255)
- `social_website`: string(255)
- `is_active`: boolean (default TRUE)
- `posts_count`: integer (default 0)
- `created`: created
- `changed`: changed

**Interface:** `ContentAuthorInterface` con metodos `getDisplayName()`, `getSlug()`, `getBio()`, `isActive()`, `getPostsCount()`, `getTenantId()`, `getSocialLinks()`.

**Form:** `ContentAuthorForm` extiende `PremiumEntityFormBase` (PREMIUM-FORMS-PATTERN-001).

```php
public function getSectionDefinitions(): array {
    return [
        'profile' => [
            'label' => $this->t('Perfil'),
            'icon' => ['category' => 'ui', 'name' => 'user'],
            'fields' => ['user_id', 'display_name', 'slug', 'bio', 'avatar'],
        ],
        'social' => [
            'label' => $this->t('Redes Sociales'),
            'icon' => ['category' => 'ui', 'name' => 'share'],
            'fields' => ['social_twitter', 'social_linkedin', 'social_website'],
        ],
        'status' => [
            'label' => $this->t('Estado'),
            'icon' => ['category' => 'ui', 'name' => 'settings'],
            'fields' => ['is_active', 'tenant_id'],
        ],
    ];
}

public function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
}
```

### 7.5 Campos a modificar en ContentArticle

#### tenant_id: integer → entity_reference a group

**Estado actual (ContentArticle.php linea 534):**
```php
$fields['tenant_id'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Tenant ID'))
    ->setDescription(t('The tenant that owns this article.'))
    ->setDefaultValue(0)
    // ...
```

**Estado objetivo:**
```php
$fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Tenant'))
    ->setDescription(t('El tenant al que pertenece este articulo.'))
    ->setSetting('target_type', 'group')
    ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 90,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', FALSE);
```

**IMPORTANTE:** Este cambio requiere un update hook con migracion de datos, ya que se pasa de una columna `tenant_id` (integer) a `tenant_id` (entity_reference con `target_id` + `target_type`). El update hook debe:
1. Crear un campo temporal
2. Copiar los valores integer existentes a `target_id`
3. Eliminar el campo viejo
4. Instalar el campo nuevo

El mismo cambio se aplica a `ContentCategory.tenant_id`.

### 7.6 Update hooks para schema migration

**Archivo:** `jaraba_content_hub.install`

```php
/**
 * Install ContentAuthor entity type.
 */
function jaraba_content_hub_update_10005(): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

  // Install content_author entity type.
  $entity_type = $entity_type_manager->getDefinition('content_author');
  $entity_definition_update_manager->installEntityType($entity_type);

  \Drupal::logger('jaraba_content_hub')->notice('ContentAuthor entity type installed.');
}

/**
 * Add backported fields to ContentArticle (tags, featured_image_alt,
 * is_featured, views_count, schema_type, og_image, scheduled_at,
 * published_at, content_author, vertical).
 */
function jaraba_content_hub_update_10006(): void {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $fields = \Drupal\jaraba_content_hub\Entity\ContentArticle::baseFieldDefinitions(
    \Drupal::entityTypeManager()->getDefinition('content_article')
  );

  $new_fields = [
    'tags', 'featured_image_alt', 'is_featured', 'views_count',
    'schema_type', 'og_image', 'scheduled_at', 'published_at',
    'content_author', 'vertical',
  ];

  foreach ($new_fields as $field_name) {
    if (isset($fields[$field_name])) {
      $update_manager->installFieldStorageDefinition(
        $field_name,
        'content_article',
        'jaraba_content_hub',
        $fields[$field_name]
      );
    }
  }

  \Drupal::logger('jaraba_content_hub')->notice('Backported fields installed on ContentArticle.');
}

/**
 * Add backported fields to ContentCategory (featured_image, meta_title,
 * meta_description, is_active, posts_count).
 */
function jaraba_content_hub_update_10007(): void {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $fields = \Drupal\jaraba_content_hub\Entity\ContentCategory::baseFieldDefinitions(
    \Drupal::entityTypeManager()->getDefinition('content_category')
  );

  $new_fields = ['featured_image', 'meta_title', 'meta_description', 'is_active', 'posts_count'];

  foreach ($new_fields as $field_name) {
    if (isset($fields[$field_name])) {
      $update_manager->installFieldStorageDefinition(
        $field_name,
        'content_category',
        'jaraba_content_hub',
        $fields[$field_name]
      );
    }
  }

  \Drupal::logger('jaraba_content_hub')->notice('Backported fields installed on ContentCategory.');
}

/**
 * Migrate tenant_id from integer to entity_reference for ContentArticle.
 */
function jaraba_content_hub_update_10008(): void {
  $database = \Drupal::database();
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type_id = 'content_article';

  // 1. Read current tenant_id values.
  $values = [];
  if ($database->schema()->tableExists('content_article_field_data')) {
    $result = $database->select('content_article_field_data', 'a')
      ->fields('a', ['id', 'tenant_id'])
      ->execute();
    foreach ($result as $row) {
      if ((int) $row->tenant_id > 0) {
        $values[$row->id] = (int) $row->tenant_id;
      }
    }
  }

  // 2. Uninstall old integer field.
  $old_definition = $update_manager->getFieldStorageDefinition('tenant_id', $entity_type_id);
  if ($old_definition) {
    $update_manager->uninstallFieldStorageDefinition($old_definition);
  }

  // 3. Install new entity_reference field.
  $fields = \Drupal\jaraba_content_hub\Entity\ContentArticle::baseFieldDefinitions(
    \Drupal::entityTypeManager()->getDefinition($entity_type_id)
  );
  if (isset($fields['tenant_id'])) {
    $update_manager->installFieldStorageDefinition(
      'tenant_id',
      $entity_type_id,
      'jaraba_content_hub',
      $fields['tenant_id']
    );
  }

  // 4. Restore values.
  foreach ($values as $article_id => $tenant_id) {
    $database->update('content_article_field_data')
      ->fields(['tenant_id' => $tenant_id])
      ->condition('id', $article_id)
      ->execute();
  }

  \Drupal::logger('jaraba_content_hub')->notice(
    'Migrated tenant_id from integer to entity_reference for @count articles.',
    ['@count' => count($values)]
  );
}

/**
 * Migrate tenant_id from integer to entity_reference for ContentCategory.
 */
function jaraba_content_hub_update_10009(): void {
  // Same pattern as 10008 but for content_category.
  $database = \Drupal::database();
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type_id = 'content_category';

  $values = [];
  if ($database->schema()->tableExists('content_category_field_data')) {
    $result = $database->select('content_category_field_data', 'c')
      ->fields('c', ['id', 'tenant_id'])
      ->execute();
    foreach ($result as $row) {
      if ((int) $row->tenant_id > 0) {
        $values[$row->id] = (int) $row->tenant_id;
      }
    }
  }

  $old_definition = $update_manager->getFieldStorageDefinition('tenant_id', $entity_type_id);
  if ($old_definition) {
    $update_manager->uninstallFieldStorageDefinition($old_definition);
  }

  $fields = \Drupal\jaraba_content_hub\Entity\ContentCategory::baseFieldDefinitions(
    \Drupal::entityTypeManager()->getDefinition($entity_type_id)
  );
  if (isset($fields['tenant_id'])) {
    $update_manager->installFieldStorageDefinition(
      'tenant_id',
      $entity_type_id,
      'jaraba_content_hub',
      $fields['tenant_id']
    );
  }

  foreach ($values as $category_id => $tenant_id) {
    $database->update('content_category_field_data')
      ->fields(['tenant_id' => $tenant_id])
      ->condition('id', $category_id)
      ->execute();
  }

  \Drupal::logger('jaraba_content_hub')->notice(
    'Migrated tenant_id from integer to entity_reference for @count categories.',
    ['@count' => count($values)]
  );
}
```

### 7.7 Migracion de datos blog_post a content_article

**Archivo:** `scripts/migrate-blog-to-content-hub.php` (drush script)

Solo se ejecuta si hay datos en produccion en las tablas `blog_post`, `blog_category`, `blog_author`. El script:

1. Migra `blog_category` → `content_category` (mapping de IDs)
2. Migra `blog_author` → `content_author` (mapping de IDs)
3. Migra `blog_post` → `content_article` (usando mappings anteriores para categorias y autores)
4. Genera log de migracion con counts

**Es idempotente:** verifica si los datos ya fueron migrados antes de duplicar.

### 7.8 ContentArticleAccessControlHandler con tenant isolation (S1 fix)

**Cambio requerido:** Actualizar `checkTenantIsolation()` para usar `->target_id` en lugar de `->value` tras la migracion de tenant_id a entity_reference.

```php
protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
    if (!$entity->hasField('tenant_id')) {
        return NULL;
    }

    // POST-MIGRATION: tenant_id es entity_reference, usar target_id.
    $entityTenantId = (int) ($entity->get('tenant_id')->target_id ?? 0);
    if ($entityTenantId === 0) {
        return NULL;
    }

    try {
        if (!\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
            return NULL;
        }
        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $currentTenant = $tenantContext->getCurrentTenant();
        if ($currentTenant === NULL) {
            return NULL;
        }
        $userTenantId = (int) $currentTenant->id();

        if ($entityTenantId !== $userTenantId) {
            return AccessResult::forbidden('Tenant mismatch: article belongs to a different tenant.')
                ->addCacheableDependency($entity)
                ->cachePerUser();
        }
    }
    catch (\Exception $e) {
        \Drupal::logger('jaraba_content_hub')->warning(
            'Tenant isolation check failed for article @id: @error',
            ['@id' => $entity->id(), '@error' => $e->getMessage()]
        );
        return NULL;
    }

    return NULL;
}
```

### 7.9 ContentCategoryAccessControlHandler con tenant isolation (S2 fix)

**Cambio requerido:** Anadir `checkTenantIsolation()` siguiendo el mismo patron del article handler.

```php
class ContentCategoryAccessControlHandler extends EntityAccessControlHandler {

    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
        if ($account->hasPermission('administer content categories')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // S2 FIX: Tenant isolation para update/delete.
        if (in_array($operation, ['update', 'delete'], TRUE)) {
            $tenantMismatch = $this->checkTenantIsolation($entity, $account);
            if ($tenantMismatch !== NULL) {
                return $tenantMismatch;
            }
        }

        switch ($operation) {
            case 'view':
                return AccessResult::allowed();
            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer content categories');
        }

        return AccessResult::neutral();
    }

    // Reutilizar checkTenantIsolation() — misma logica que ContentArticle.
    // Considerar extraer a un trait TenantIsolationTrait.
    protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
        // ... identico al de ContentArticleAccessControlHandler
    }
}
```

### 7.10 ContentAuthorAccessControlHandler (nuevo)

```php
class ContentAuthorAccessControlHandler extends EntityAccessControlHandler {

    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
        if ($account->hasPermission('administer content authors')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Tenant isolation para update/delete.
        if (in_array($operation, ['update', 'delete'], TRUE)) {
            $tenantMismatch = $this->checkTenantIsolation($entity, $account);
            if ($tenantMismatch !== NULL) {
                return $tenantMismatch;
            }
        }

        switch ($operation) {
            case 'view':
                // Autores activos son publicos.
                if ($entity->isActive()) {
                    return AccessResult::allowed()->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'administer content authors');

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer content authors');
        }

        return AccessResult::neutral();
    }

    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
        return AccessResult::allowedIfHasPermission($account, 'administer content authors');
    }

    protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
        // ... identico al patron de ContentArticle
    }
}
```

### 7.11 Permisos nuevos y actualizados

**Anadir a `jaraba_content_hub.permissions.yml`:**

```yaml
administer content authors:
  title: 'Administer content authors'
  description: 'Create, edit and delete content authors.'

view content authors:
  title: 'View content authors'
  description: 'View active content author profiles.'

schedule content article:
  title: 'Schedule articles for publication'
  description: 'Set articles to publish at a future date.'

view content article analytics:
  title: 'View article analytics'
  description: 'View article view counts and statistics.'
```

### 7.12 Estructura de archivos completa

```
web/modules/custom/jaraba_content_hub/
├── jaraba_content_hub.info.yml
├── jaraba_content_hub.module              # MODIFICAR: +cron, +preprocess author
├── jaraba_content_hub.install             # MODIFICAR: +update hooks 10005-10009
├── jaraba_content_hub.routing.yml         # MODIFICAR: +rutas author, category, RSS
├── jaraba_content_hub.services.yml        # MODIFICAR: +RssService, ampliar SeoService
├── jaraba_content_hub.permissions.yml     # MODIFICAR: +permisos autores, schedule
├── jaraba_content_hub.libraries.yml       # VERIFICAR dependencias
├── jaraba_content_hub.links.menu.yml      # MODIFICAR: +authors menu
├── jaraba_content_hub.links.action.yml    # MODIFICAR: +add author action
├── jaraba_content_hub.links.task.yml      # MODIFICAR: +authors tab
│
├── src/
│   ├── Entity/
│   │   ├── ContentArticle.php             # MODIFICAR: +10 campos backport
│   │   ├── ContentArticleInterface.php    # MODIFICAR: +metodos nuevos campos
│   │   ├── ContentCategory.php            # MODIFICAR: +5 campos backport
│   │   ├── ContentAuthor.php              # CREAR: nueva entidad
│   │   ├── ContentAuthorInterface.php     # CREAR: interface
│   │   └── AiGenerationLog.php            # SIN CAMBIOS
│   │
│   ├── Controller/
│   │   ├── BlogController.php             # MODIFICAR: +category route handler, SEO head
│   │   ├── BlogArticleController.php      # MODIFICAR: +view tracking, SEO head
│   │   ├── AuthorController.php           # CREAR: frontend author page
│   │   ├── RssController.php              # CREAR: RSS feed
│   │   ├── ArticleCanvasEditorController.php  # SIN CAMBIOS
│   │   ├── ArticleCanvasApiController.php     # SIN CAMBIOS
│   │   ├── WritingAssistantController.php     # SIN CAMBIOS
│   │   ├── RecommendationController.php       # SIN CAMBIOS
│   │   ├── ContentHubDashboardController.php  # MODIFICAR: +stats completas
│   │   ├── ArticlesListController.php         # SIN CAMBIOS
│   │   └── CategoriesListController.php       # SIN CAMBIOS
│   │
│   ├── Service/
│   │   ├── ArticleService.php             # MODIFICAR: +9 metodos backport
│   │   ├── CategoryService.php            # MODIFICAR: +updatePostsCount()
│   │   ├── SeoService.php                 # MODIFICAR: reescribir con SEO completo
│   │   ├── RssService.php                 # CREAR: RSS 2.0 feed
│   │   ├── RecommendationService.php      # SIN CAMBIOS
│   │   ├── WritingAssistantService.php    # SIN CAMBIOS
│   │   ├── ContentEmbeddingService.php    # SIN CAMBIOS
│   │   ├── SentimentEngineService.php     # SIN CAMBIOS
│   │   ├── ReputationMonitorService.php   # SIN CAMBIOS
│   │   ├── NewsletterBridgeService.php    # SIN CAMBIOS
│   │   └── QdrantContentClient.php        # SIN CAMBIOS
│   │
│   ├── Form/
│   │   ├── ContentArticleForm.php         # MODIFICAR: +secciones nuevos campos
│   │   ├── ContentCategoryForm.php        # MODIFICAR: +secciones nuevos campos
│   │   ├── ContentAuthorForm.php          # CREAR: PremiumEntityFormBase
│   │   ├── ContentAuthorSettingsForm.php  # CREAR: Field UI base
│   │   └── ... (existentes sin cambios)
│   │
│   ├── ContentArticleAccessControlHandler.php   # MODIFICAR: S1 fix
│   ├── ContentCategoryAccessControlHandler.php  # MODIFICAR: S2 fix
│   ├── ContentAuthorAccessControlHandler.php    # CREAR: nuevo
│   ├── ContentAuthorListBuilder.php             # CREAR: nuevo
│   └── ... (existentes sin cambios)
│
├── templates/
│   ├── content-hub-blog-index.html.twig          # MODIFICAR: +author filters
│   ├── content-article--full.html.twig           # MODIFICAR: +related, adjacent, share, author bio
│   ├── content-hub-category-page.html.twig       # MODIFICAR: +imagen, SEO
│   ├── content-hub-author-page.html.twig         # CREAR: author page
│   ├── partials/
│   │   ├── _article-card.html.twig               # CREAR: partial reutilizable
│   │   ├── _author-bio.html.twig                 # CREAR: partial author bio
│   │   └── _share-buttons.html.twig              # CREAR: partial share
│   └── ... (13 existentes sin cambios)
│
├── js/
│   ├── article-canvas-editor.js   # SIN CAMBIOS
│   ├── newsletter-form.js         # SIN CAMBIOS
│   └── reading-progress.js        # SIN CAMBIOS
│
└── config/
    └── install/
        └── jaraba_content_hub.settings.yml  # VERIFICAR
```

---

## 8. Servicios — Consolidacion y Elevacion

### 8.1 ArticleService: ampliar con funcionalidades de BlogService

**Metodos nuevos a anadir** (backport de `BlogService`):

```php
/**
 * Obtiene articulos relacionados por categoria y recientes.
 *
 * Primero busca articulos en la misma categoria, luego completa
 * con articulos recientes si no hay suficientes.
 */
public function getRelatedArticles(int $articleId, int $limit = 4): array {
    $article = $this->entityTypeManager->getStorage('content_article')->load($articleId);
    if (!$article) {
        return [];
    }

    $storage = $this->entityTypeManager->getStorage('content_article');
    $related = [];

    // Primero por categoria.
    $categoryId = $article->get('category')->target_id;
    if ($categoryId) {
        $ids = $storage->getQuery()
            ->condition('category', $categoryId)
            ->condition('status', 'published')
            ->condition('id', $articleId, '<>')
            ->accessCheck(TRUE)
            ->sort('published_at', 'DESC')
            ->range(0, $limit)
            ->execute();
        if ($ids) {
            $related = $storage->loadMultiple($ids);
        }
    }

    // Completar con recientes.
    if (count($related) < $limit) {
        $excludeIds = array_merge([$articleId], array_keys($related));
        $remaining = $limit - count($related);
        $ids = $storage->getQuery()
            ->condition('status', 'published')
            ->condition('id', $excludeIds, 'NOT IN')
            ->accessCheck(TRUE)
            ->sort('published_at', 'DESC')
            ->range(0, $remaining)
            ->execute();
        if ($ids) {
            $related += $storage->loadMultiple($ids);
        }
    }

    return array_values($related);
}

/**
 * Obtiene articulos anterior y siguiente para navegacion.
 */
public function getAdjacentArticles(int $articleId): array {
    $article = $this->entityTypeManager->getStorage('content_article')->load($articleId);
    if (!$article) {
        return ['prev' => NULL, 'next' => NULL];
    }

    $publishedAt = $article->get('published_at')->value ?? $article->get('created')->value;
    $storage = $this->entityTypeManager->getStorage('content_article');

    $prevIds = $storage->getQuery()
        ->condition('status', 'published')
        ->condition('published_at', $publishedAt, '<')
        ->accessCheck(TRUE)
        ->sort('published_at', 'DESC')
        ->range(0, 1)
        ->execute();

    $nextIds = $storage->getQuery()
        ->condition('status', 'published')
        ->condition('published_at', $publishedAt, '>')
        ->accessCheck(TRUE)
        ->sort('published_at', 'ASC')
        ->range(0, 1)
        ->execute();

    return [
        'prev' => $prevIds ? $storage->load(reset($prevIds)) : NULL,
        'next' => $nextIds ? $storage->load(reset($nextIds)) : NULL,
    ];
}

/**
 * Incrementa el contador de visitas via DB expression.
 *
 * Usa una expresion SQL directa para evitar load/save cycle.
 */
public function trackView(int $articleId): void {
    \Drupal::database()->update('content_article_field_data')
        ->expression('views_count', 'views_count + 1')
        ->condition('id', $articleId)
        ->execute();
}

/**
 * Publica articulos programados cuya fecha ha pasado.
 *
 * Se ejecuta via hook_cron(). Es idempotente.
 */
public function publishScheduledArticles(): int {
    $storage = $this->entityTypeManager->getStorage('content_article');
    $now = date('Y-m-d\TH:i:s');

    $ids = $storage->getQuery()
        ->condition('status', 'scheduled')
        ->condition('scheduled_at', $now, '<=')
        ->accessCheck(FALSE) // Cron interno, no requiere access check.
        ->execute();

    $count = 0;
    if ($ids) {
        $articles = $storage->loadMultiple($ids);
        foreach ($articles as $article) {
            $article->set('status', 'published');
            $scheduledAt = $article->get('scheduled_at')->value;
            if ($scheduledAt) {
                $article->set('published_at', $scheduledAt);
            }
            $article->save();
            $count++;

            $this->logger->info('Articulo programado publicado: @id (@title)', [
                '@id' => $article->id(),
                '@title' => $article->label(),
            ]);
        }
    }

    return $count;
}

/**
 * Obtiene los articulos mas populares por visitas.
 */
public function getPopularArticles(int $limit = 5): array {
    $storage = $this->entityTypeManager->getStorage('content_article');
    $ids = $storage->getQuery()
        ->condition('status', 'published')
        ->accessCheck(TRUE)
        ->sort('views_count', 'DESC')
        ->range(0, $limit)
        ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
}

/**
 * Obtiene todas las tags unicas con conteos.
 *
 * @return array
 *   Array asociativo ['tag_name' => count], ordenado por frecuencia DESC.
 */
public function getAllTags(): array {
    $result = \Drupal::database()->select('content_article_field_data', 'a')
        ->fields('a', ['tags'])
        ->condition('a.status', 'published')
        ->isNotNull('a.tags')
        ->execute();

    $allTags = [];
    foreach ($result as $row) {
        $tags = array_map('trim', explode(',', $row->tags));
        foreach ($tags as $tag) {
            if (!empty($tag)) {
                $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
            }
        }
    }

    arsort($allTags);
    return $allTags;
}

/**
 * Obtiene articulos destacados.
 */
public function getFeaturedArticles(int $limit = 3): array {
    $storage = $this->entityTypeManager->getStorage('content_article');
    $ids = $storage->getQuery()
        ->condition('status', 'published')
        ->condition('is_featured', TRUE)
        ->accessCheck(TRUE)
        ->sort('published_at', 'DESC')
        ->range(0, $limit)
        ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
}

/**
 * Calcula el tiempo de lectura (200 wpm).
 */
public function calculateReadingTime(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    return max(1, (int) ceil($wordCount / 200));
}
```

**Dependencia nueva:** Inyectar `Connection $database` en el constructor para `trackView()` y `getAllTags()`.

### 8.2 CategoryService: ampliar

```php
/**
 * Actualiza el cache de conteo de posts para una categoria.
 */
public function updatePostsCount(int $categoryId): void {
    $articleStorage = $this->entityTypeManager->getStorage('content_article');
    $count = (int) $articleStorage->getQuery()
        ->condition('category', $categoryId)
        ->condition('status', 'published')
        ->accessCheck(FALSE)
        ->count()
        ->execute();

    $category = $this->entityTypeManager->getStorage('content_category')->load($categoryId);
    if ($category && $category->hasField('posts_count')) {
        $category->set('posts_count', $count);
        $category->save();
    }
}

/**
 * Obtiene solo categorias activas.
 */
public function getActiveCategories(): array {
    $storage = $this->entityTypeManager->getStorage('content_category');
    $ids = $storage->getQuery()
        ->condition('is_active', TRUE)
        ->accessCheck(TRUE)
        ->sort('weight', 'ASC')
        ->sort('name', 'ASC')
        ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
}
```

### 8.3 SeoService (consolidado)

**Reescribir** `SeoService` backporteando la logica completa de `BlogSeoService`:

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Servicio SEO consolidado para el Content Hub.
 *
 * Genera:
 * - Meta tags (title, description, robots)
 * - Open Graph tags (og:type, og:title, og:image, article:*)
 * - Twitter Cards (twitter:card, twitter:title, twitter:image)
 * - Schema.org JSON-LD (BlogPosting, Article, NewsArticle, BreadcrumbList)
 * - Canonical URLs
 * - SEO quality analysis
 *
 * Backport de BlogSeoService + funcionalidad existente de analisis SEO.
 */
class SeoService {

    public function __construct(
        protected LoggerInterface $logger,
        protected FileUrlGeneratorInterface $fileUrlGenerator,
    ) {}

    /**
     * Genera los meta tags SEO completos para un articulo.
     *
     * @return array
     *   Array con meta_tags, og_tags, twitter_tags, json_ld, canonical.
     */
    public function generateArticleSeo(object $article, string $baseUrl, string $siteName = ''): array {
        $slug = $article->getSlug() ?: (string) $article->id();
        $articleUrl = $baseUrl . Url::fromRoute('entity.content_article.canonical', [
            'content_article' => $article->id(),
        ])->toString();

        // Meta tags basicos.
        $metaTitle = $article->get('seo_title')->value ?: $article->getTitle();
        $metaDescription = $article->get('seo_description')->value ?: $article->getExcerpt();
        if (empty($metaDescription)) {
            $body = $article->isCanvasMode() ? $article->getRenderedHtml() : ($article->get('body')->value ?? '');
            $metaDescription = mb_substr(strip_tags($body), 0, 155);
        }

        // Imagen (og_image → featured_image fallback).
        $imageUrl = '';
        $ogImage = $article->get('og_image')->entity ?? NULL;
        if ($ogImage) {
            $imageUrl = $this->fileUrlGenerator->generateAbsoluteString($ogImage->getFileUri());
        }
        elseif ($article->get('featured_image')->entity) {
            $imageUrl = $this->fileUrlGenerator->generateAbsoluteString(
                $article->get('featured_image')->entity->getFileUri()
            );
        }

        // Autor editorial.
        $authorName = '';
        $contentAuthor = $article->get('content_author')->entity ?? NULL;
        if ($contentAuthor) {
            $authorName = $contentAuthor->getDisplayName();
        }
        elseif ($article->getOwner()) {
            $authorName = $article->getOwner()->getDisplayName();
        }

        // Categoria.
        $category = $article->get('category')->entity ?? NULL;
        $categoryName = $category ? $category->getName() : '';

        // Tags.
        $tagsString = $article->get('tags')->value ?? '';
        $tags = $tagsString ? array_map('trim', explode(',', $tagsString)) : [];

        return [
            'meta_tags' => [
                'title' => $metaTitle,
                'description' => $metaDescription,
                'robots' => 'index, follow',
            ],
            'og_tags' => [
                'og:type' => 'article',
                'og:title' => $metaTitle,
                'og:description' => $metaDescription,
                'og:url' => $articleUrl,
                'og:image' => $imageUrl,
                'og:site_name' => $siteName,
                'article:published_time' => $article->get('published_at')->value ?? '',
                'article:modified_time' => date('c', (int) ($article->get('changed')->value ?? time())),
                'article:author' => $authorName,
                'article:section' => $categoryName,
                'article:tag' => implode(',', $tags),
            ],
            'twitter_tags' => [
                'twitter:card' => $imageUrl ? 'summary_large_image' : 'summary',
                'twitter:title' => $metaTitle,
                'twitter:description' => $metaDescription,
                'twitter:image' => $imageUrl,
            ],
            'json_ld' => $this->generateJsonLd($article, $articleUrl, $imageUrl, $authorName, $siteName),
            'canonical' => $articleUrl,
        ];
    }

    /**
     * Genera Schema.org JSON-LD para un articulo.
     */
    protected function generateJsonLd(
        object $article,
        string $articleUrl,
        string $imageUrl,
        string $authorName,
        string $publisherName,
    ): array {
        $schemaType = $article->get('schema_type')->value ?? 'BlogPosting';

        $body = $article->isCanvasMode() ? $article->getRenderedHtml() : ($article->get('body')->value ?? '');

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'headline' => $article->getTitle(),
            'description' => $article->getExcerpt() ?: mb_substr(strip_tags($body), 0, 155),
            'url' => $articleUrl,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $articleUrl,
            ],
        ];

        if ($imageUrl) {
            $jsonLd['image'] = ['@type' => 'ImageObject', 'url' => $imageUrl];
        }
        if ($authorName) {
            $jsonLd['author'] = ['@type' => 'Person', 'name' => $authorName];
        }
        if ($publisherName) {
            $jsonLd['publisher'] = ['@type' => 'Organization', 'name' => $publisherName];
        }

        $publishedAt = $article->get('published_at')->value;
        if ($publishedAt) {
            $jsonLd['datePublished'] = $publishedAt;
        }
        $jsonLd['dateModified'] = date('c', (int) ($article->get('changed')->value ?? time()));

        $readingTime = $article->getReadingTime();
        if ($readingTime > 0) {
            $jsonLd['timeRequired'] = 'PT' . $readingTime . 'M';
        }

        $wordCount = str_word_count(strip_tags($body));
        if ($wordCount > 0) {
            $jsonLd['wordCount'] = $wordCount;
        }

        // Answer Capsule como speakable.
        $answerCapsule = $article->getAnswerCapsule();
        if ($answerCapsule) {
            $jsonLd['speakable'] = [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => '.answer-capsule',
            ];
        }

        return $jsonLd;
    }

    /**
     * Genera SEO para la pagina de listado del blog.
     */
    public function generateListingSeo(string $baseUrl, string $siteName, ?string $categoryName = NULL, ?string $authorName = NULL): array {
        $title = (string) t('Blog');
        $description = (string) t('Blog de @name', ['@name' => $siteName]);
        $url = $baseUrl . '/blog';

        if ($categoryName) {
            $title = $categoryName . ' - Blog';
            $description = (string) t('Articulos sobre @category en @name', [
                '@category' => $categoryName,
                '@name' => $siteName,
            ]);
        }
        if ($authorName) {
            $title = $authorName . ' - Blog';
            $description = (string) t('Articulos de @author en @name', [
                '@author' => $authorName,
                '@name' => $siteName,
            ]);
        }

        return [
            'meta_tags' => [
                'title' => $title,
                'description' => $description,
                'robots' => 'index, follow',
            ],
            'og_tags' => [
                'og:type' => 'website',
                'og:title' => $title,
                'og:description' => $description,
                'og:url' => $url,
                'og:site_name' => $siteName,
            ],
            'canonical' => $url,
        ];
    }

    /**
     * Analiza la calidad SEO de un articulo (funcionalidad existente mantenida).
     */
    public function analyzeArticleSeo(array $data): array {
        // ... mantener implementacion existente sin cambios
    }
}
```

**Actualizacion en services.yml:**
```yaml
jaraba_content_hub.seo_service:
  class: Drupal\jaraba_content_hub\Service\SeoService
  arguments:
    - '@logger.channel.jaraba_content_hub'
    - '@file_url_generator'
```

### 8.4 RssService (nuevo, backport de BlogRssService)

**Archivo:** `jaraba_content_hub/src/Service/RssService.php`

Replica la logica de `BlogRssService` adaptada al modelo de ContentArticle:

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio para generacion de feed RSS 2.0 per-tenant.
 *
 * Backport de jaraba_blog.rss (BlogRssService).
 */
class RssService {

    public function __construct(
        protected ArticleService $articleService,
        protected LoggerInterface $logger,
    ) {}

    /**
     * Genera el feed RSS como string XML.
     */
    public function generateFeed(string $baseUrl, string $siteName, int $limit = 20): string {
        $articles = $this->articleService->getPublishedArticles(['limit' => $limit]);

        $blogUrl = $baseUrl . '/blog';
        $feedUrl = $baseUrl . '/blog/feed.xml';

        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = TRUE;

        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $xml->appendChild($rss);

        $channel = $xml->createElement('channel');
        $rss->appendChild($channel);

        $channel->appendChild($xml->createElement('title', $this->xmlEncode($siteName . ' - Blog')));
        $channel->appendChild($xml->createElement('link', $blogUrl));
        $channel->appendChild($xml->createElement('description', $this->xmlEncode((string) t('Blog de @name', ['@name' => $siteName]))));
        $channel->appendChild($xml->createElement('language', \Drupal::languageManager()->getCurrentLanguage()->getId()));
        $channel->appendChild($xml->createElement('lastBuildDate', date('r')));

        $atomLink = $xml->createElement('atom:link');
        $atomLink->setAttribute('href', $feedUrl);
        $atomLink->setAttribute('rel', 'self');
        $atomLink->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($atomLink);

        foreach ($articles as $article) {
            $item = $xml->createElement('item');
            $channel->appendChild($item);

            $articleUrl = $baseUrl . '/blog/' . ($article->getSlug() ?: $article->id());

            $item->appendChild($xml->createElement('title', $this->xmlEncode($article->getTitle())));
            $item->appendChild($xml->createElement('link', $articleUrl));

            $description = $article->getExcerpt();
            if (empty($description)) {
                $body = $article->isCanvasMode() ? $article->getRenderedHtml() : ($article->get('body')->value ?? '');
                $description = mb_substr(strip_tags($body), 0, 300) . '...';
            }
            $descCdata = $xml->createCDATASection($description);
            $descEl = $xml->createElement('description');
            $descEl->appendChild($descCdata);
            $item->appendChild($descEl);

            $guid = $xml->createElement('guid', $articleUrl);
            $guid->setAttribute('isPermaLink', 'true');
            $item->appendChild($guid);

            $publishedAt = $article->get('published_at')->value;
            if ($publishedAt) {
                $timestamp = strtotime($publishedAt);
                if ($timestamp) {
                    $item->appendChild($xml->createElement('pubDate', date('r', $timestamp)));
                }
            }

            $contentAuthor = $article->get('content_author')->entity ?? NULL;
            if ($contentAuthor) {
                $item->appendChild($xml->createElement('dc:creator', $this->xmlEncode($contentAuthor->getDisplayName())));
            }

            $category = $article->get('category')->entity ?? NULL;
            if ($category) {
                $item->appendChild($xml->createElement('category', $this->xmlEncode($category->getName())));
            }
        }

        return $xml->saveXML();
    }

    protected function xmlEncode(string $text): string {
        return htmlspecialchars($text, ENT_XML1, 'UTF-8');
    }
}
```

**Registro en services.yml:**
```yaml
jaraba_content_hub.rss_service:
  class: Drupal\jaraba_content_hub\Service\RssService
  arguments:
    - '@jaraba_content_hub.article_service'
    - '@logger.channel.jaraba_content_hub'
```

### 8.5 WritingAssistantService (existente, sin cambios)

Se mantiene tal cual. Verificar que `ContentWriterAgent` funciona correctamente (bugs B1-B2).

### 8.6 RecommendationService (existente, verificar integracion)

Se mantiene. Verificar que la integracion con Qdrant sigue funcionando tras anadir los nuevos campos a ContentArticle. El `contentArticle_insert` y `_update` hooks en el `.module` ya manejan el indexado.

---

## 9. Controladores — Consolidacion

### 9.1 BlogController: ampliar

**Metodos nuevos:**

```php
/**
 * Pagina de categoria del blog.
 *
 * Ruta: /blog/categoria/{slug}
 */
public function category(Request $request, string $slug): array {
    $category = $this->categoryService->getBySlug($slug);
    if (!$category) {
        throw new NotFoundHttpException();
    }

    $page = (int) $request->query->get('page', 0);
    $limit = 12;

    $articles = $this->articleService->getPublishedArticles([
        'category' => $category->id(),
        'limit' => $limit,
        'offset' => $page * $limit,
    ]);
    $total = $this->articleService->countPublishedArticles([
        'category' => $category->id(),
    ]);
    $totalPages = $total > 0 ? (int) ceil($total / $limit) : 0;

    return [
        '#theme' => 'content_hub_category_page',
        '#category' => $category,
        '#articles' => $articles,
        '#show_reading_time' => TRUE,
        '#pager' => [
            'current' => $page,
            'total' => $totalPages,
        ],
        '#cache' => [
            'contexts' => ['url.query_args:page'],
            'tags' => ['content_category:' . $category->id()],
        ],
    ];
}
```

### 9.2 AuthorController (nuevo)

**Archivo:** `jaraba_content_hub/src/Controller/AuthorController.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_content_hub\Service\ArticleService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the public author page.
 */
class AuthorController extends ControllerBase {

    public function __construct(
        protected ArticleService $articleService,
    ) {}

    public static function create(ContainerInterface $container): static {
        return new static(
            $container->get('jaraba_content_hub.article_service'),
        );
    }

    /**
     * Author page: /blog/autor/{slug}
     */
    public function view(Request $request, string $slug): array {
        $storage = $this->entityTypeManager()->getStorage('content_author');
        $ids = $storage->getQuery()
            ->condition('slug', $slug)
            ->condition('is_active', TRUE)
            ->accessCheck(TRUE)
            ->range(0, 1)
            ->execute();

        if (empty($ids)) {
            throw new NotFoundHttpException();
        }

        $author = $storage->load(reset($ids));
        $page = (int) $request->query->get('page', 0);
        $limit = 12;

        // Get articles by this author.
        $articleStorage = $this->entityTypeManager()->getStorage('content_article');
        $articleIds = $articleStorage->getQuery()
            ->condition('content_author', $author->id())
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->sort('published_at', 'DESC')
            ->range($page * $limit, $limit)
            ->execute();
        $articles = $articleIds ? $articleStorage->loadMultiple($articleIds) : [];

        $totalCount = (int) $articleStorage->getQuery()
            ->condition('content_author', $author->id())
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->count()
            ->execute();
        $totalPages = $totalCount > 0 ? (int) ceil($totalCount / $limit) : 0;

        return [
            '#theme' => 'content_hub_author_page',
            '#author' => $author,
            '#articles' => $articles,
            '#pager' => [
                'current' => $page,
                'total' => $totalPages,
            ],
            '#cache' => [
                'contexts' => ['url.query_args:page'],
                'tags' => ['content_author:' . $author->id()],
            ],
        ];
    }

    /**
     * Title callback for author page.
     */
    public function title(string $slug): string {
        return $slug;
    }
}
```

### 9.3 RssController (nuevo)

**Archivo:** `jaraba_content_hub/src/Controller/RssController.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_content_hub\Service\RssService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class RssController extends ControllerBase {

    public function __construct(protected RssService $rssService) {}

    public static function create(ContainerInterface $container): static {
        return new static($container->get('jaraba_content_hub.rss_service'));
    }

    public function feed(): Response {
        $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
        $siteName = \Drupal::config('system.site')->get('name') ?? 'Blog';

        $xml = $this->rssService->generateFeed($baseUrl, $siteName);

        return new Response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
```

### 9.4 DashboardFrontendController: integrar stats completas

Ampliar el dashboard para incluir `views_count`, `featured articles`, `authors count`, y `tags`.

### 9.5 REST API: ampliar con endpoints de autores

**Rutas nuevas en routing.yml:**

```yaml
# Category page (frontend).
jaraba_content_hub.blog.category:
  path: '/blog/categoria/{slug}'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\BlogController::category'
    _title: 'Categoria'
  requirements:
    _permission: 'access content'
    slug: '[a-z0-9\-]+'

# Author page (frontend).
jaraba_content_hub.blog.author:
  path: '/blog/autor/{slug}'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\AuthorController::view'
    _title_callback: '\Drupal\jaraba_content_hub\Controller\AuthorController::title'
  requirements:
    _permission: 'access content'
    slug: '[a-z0-9\-]+'

# RSS feed.
jaraba_content_hub.blog.rss:
  path: '/blog/feed.xml'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\RssController::feed'
  requirements:
    _access: 'TRUE'

# API: List authors.
jaraba_content_hub.api.authors.list:
  path: '/api/v1/content/authors'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\AuthorApiController::list'
  requirements:
    _permission: 'access content'
  methods: [GET]

# API: Track article view.
jaraba_content_hub.api.articles.track_view:
  path: '/api/v1/content/articles/{uuid}/view'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\ArticleApiController::trackView'
  requirements:
    _permission: 'access content'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]
```

---

## 10. Arquitectura Frontend

### 10.1 Zero Region Policy

Se mantiene `page--content-hub.html.twig` sin cambios. Las paginas de blog, categoria, y autor se renderizan usando este template que no incluye `{{ page.content }}` ni bloques Drupal — solo el contenido limpio del controlador.

### 10.2 Templates a crear/modificar

#### Templates NUEVOS:

**`content-hub-author-page.html.twig`:**
```twig
{#
 # Author page template.
 # Variables: author, articles[], pager
 #}
<article class="author-page">
  <header class="author-page__header">
    {% if author.avatar %}
      <img class="author-page__avatar"
           src="{{ author.avatar_url }}"
           alt="{{ author.display_name }}"
           loading="lazy">
    {% else %}
      <div class="author-page__avatar author-page__avatar--placeholder">
        {{ author.display_name|first|upper }}
      </div>
    {% endif %}

    <h1 class="author-page__name">{{ author.display_name }}</h1>

    {% if author.bio %}
      <p class="author-page__bio">{{ author.bio }}</p>
    {% endif %}

    {% if author.social_links is not empty %}
      <nav class="author-page__social" aria-label="{% trans %}Redes sociales{% endtrans %}">
        {% for platform, url in author.social_links %}
          <a href="{{ url }}"
             class="author-page__social-link"
             target="_blank"
             rel="noopener noreferrer"
             aria-label="{{ platform }}">
            {{ jaraba_icon('ui', platform) }}
          </a>
        {% endfor %}
      </nav>
    {% endif %}

    <p class="author-page__posts-count">
      {% trans %}{{ count }} articulos publicados{% endtrans %}
    </p>
  </header>

  <section class="author-page__articles">
    {% for article in articles %}
      {% include '@jaraba_content_hub/partials/_article-card.html.twig' with {article: article} %}
    {% endfor %}
  </section>

  {% if pager.total > 1 %}
    {% include '@jaraba_content_hub/partials/_pagination.html.twig' with {pager: pager} %}
  {% endif %}
</article>
```

**`partials/_article-card.html.twig`:**
```twig
{#
 # Reusable article card partial.
 # Variables: article (processed array with title, slug, excerpt, image_url, category, reading_time, published_at)
 #}
<article class="article-card">
  {% if article.image_url %}
    <a href="{{ path('entity.content_article.canonical', {'content_article': article.id}) }}" class="article-card__image-link">
      <img class="article-card__image"
           src="{{ article.image_url }}"
           alt="{{ article.featured_image_alt|default(article.title) }}"
           loading="lazy">
    </a>
  {% endif %}

  <div class="article-card__content">
    {% if article.category_name %}
      <span class="article-card__category" style="color: {{ article.category_color }}">
        {{ article.category_name }}
      </span>
    {% endif %}

    <h3 class="article-card__title">
      <a href="{{ path('entity.content_article.canonical', {'content_article': article.id}) }}">
        {{ article.title }}
      </a>
    </h3>

    {% if article.excerpt %}
      <p class="article-card__excerpt">{{ article.excerpt|striptags|truncate(150) }}</p>
    {% endif %}

    <footer class="article-card__meta">
      {% if article.reading_time > 0 %}
        <span class="article-card__reading-time">
          {{ jaraba_icon('ui', 'clock') }}
          {{ article.reading_time }} {% trans %}min lectura{% endtrans %}
        </span>
      {% endif %}
      {% if article.published_at %}
        <time class="article-card__date" datetime="{{ article.published_at }}">
          {{ article.published_at|date('d M Y') }}
        </time>
      {% endif %}
    </footer>
  </div>
</article>
```

**`partials/_author-bio.html.twig`:**
```twig
{#
 # Author bio partial for article detail page.
 # Variables: author (processed array with display_name, bio, avatar_url, social_links, slug)
 #}
<aside class="author-bio" aria-label="{% trans %}Sobre el autor{% endtrans %}">
  {% if author.avatar_url %}
    <img class="author-bio__avatar" src="{{ author.avatar_url }}" alt="{{ author.display_name }}" loading="lazy">
  {% else %}
    <div class="author-bio__avatar author-bio__avatar--placeholder">{{ author.display_name|first|upper }}</div>
  {% endif %}

  <div class="author-bio__info">
    <a href="{{ path('jaraba_content_hub.blog.author', {'slug': author.slug}) }}" class="author-bio__name">
      {{ author.display_name }}
    </a>
    {% if author.bio %}
      <p class="author-bio__text">{{ author.bio }}</p>
    {% endif %}
  </div>
</aside>
```

**`partials/_share-buttons.html.twig`:**
```twig
{#
 # Share buttons partial — URL-scheme links (no JS SDKs).
 # Variables: title, url
 #}
<nav class="share-buttons" aria-label="{% trans %}Compartir articulo{% endtrans %}">
  <span class="share-buttons__label">{% trans %}Compartir{% endtrans %}</span>

  <a href="https://twitter.com/intent/tweet?text={{ title|url_encode }}&url={{ url|url_encode }}"
     class="share-buttons__btn share-buttons__btn--twitter"
     target="_blank" rel="noopener noreferrer"
     aria-label="{% trans %}Compartir en Twitter{% endtrans %}">
    {{ jaraba_icon('ui', 'share') }}
    Twitter
  </a>

  <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ url|url_encode }}"
     class="share-buttons__btn share-buttons__btn--linkedin"
     target="_blank" rel="noopener noreferrer"
     aria-label="{% trans %}Compartir en LinkedIn{% endtrans %}">
    {{ jaraba_icon('ui', 'share') }}
    LinkedIn
  </a>

  <button class="share-buttons__btn share-buttons__btn--copy"
          data-copy-url="{{ url }}"
          aria-label="{% trans %}Copiar enlace{% endtrans %}">
    {{ jaraba_icon('ui', 'link') }}
    {% trans %}Copiar enlace{% endtrans %}
  </button>
</nav>
```

### 10.3 SCSS: Federated Design Tokens

Todas las nuevas reglas SCSS consumen Design Tokens via `var(--ej-*, $fallback)`:

```scss
@use 'sass:color';
@use 'variables' as *;

// =====================================================================
// Author Page
// =====================================================================
.author-page {
  max-width: 1200px;
  margin-inline: auto;
  padding: $ej-spacing-lg;

  &__header {
    text-align: center;
    padding-block: $ej-spacing-xl;
    border-bottom: 1px solid var(--ej-color-muted, #e5e5e5);
    margin-bottom: $ej-spacing-lg;
  }

  &__avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-inline: auto;
    margin-bottom: $ej-spacing-md;

    &--placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--ej-color-primary, #233D63);
      color: white;
      font-size: 2.5rem;
      font-family: var(--ej-font-headings, sans-serif);
    }
  }

  &__social {
    display: flex;
    gap: $ej-spacing-sm;
    justify-content: center;
    margin-top: $ej-spacing-md;
  }

  &__social-link {
    color: var(--ej-color-muted, #666);
    transition: color 0.2s;

    &:hover { color: var(--ej-color-primary, #233D63); }
    &:focus-visible {
      outline: 2px solid var(--ej-color-primary, #233D63);
      outline-offset: 2px;
      border-radius: 4px;
    }
  }

  &__articles {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: $ej-spacing-lg;
  }
}

// =====================================================================
// Article Card
// =====================================================================
.article-card {
  background: var(--ej-card-bg, #fff);
  border-radius: var(--ej-card-radius, 12px);
  overflow: hidden;
  box-shadow: var(--ej-shadow-sm, 0 1px 3px rgba(0,0,0,.1));
  transition: transform 0.2s, box-shadow 0.2s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: var(--ej-shadow-lg, 0 4px 12px rgba(0,0,0,.15));
  }

  &__image {
    width: 100%;
    aspect-ratio: 3 / 2;
    object-fit: cover;
  }

  &__content { padding: $ej-spacing-md; }

  &__category {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  &__title {
    font-family: var(--ej-font-headings, sans-serif);
    font-size: 1.125rem;
    margin-top: $ej-spacing-xs;

    a {
      color: var(--ej-color-headings, #1a1a1a);
      text-decoration: none;
      &:hover { color: var(--ej-color-primary, #233D63); }
      &:focus-visible {
        outline: 2px solid var(--ej-color-primary, #233D63);
        outline-offset: 2px;
      }
    }
  }

  &__meta {
    display: flex;
    gap: $ej-spacing-sm;
    font-size: 0.8125rem;
    color: var(--ej-color-muted, #666);
    margin-top: $ej-spacing-sm;
  }
}

// =====================================================================
// Share Buttons
// =====================================================================
.share-buttons {
  display: flex;
  align-items: center;
  gap: $ej-spacing-sm;
  flex-wrap: wrap;

  &__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: $ej-spacing-xs $ej-spacing-sm;
    border-radius: var(--ej-border-radius, 8px);
    font-size: 0.8125rem;
    text-decoration: none;
    border: 1px solid var(--ej-color-muted, #e5e5e5);
    color: var(--ej-color-body, #333);
    background: transparent;
    cursor: pointer;
    transition: background 0.2s;

    &:hover { background: var(--ej-color-surface, #f5f5f5); }
    &:focus-visible {
      outline: 2px solid var(--ej-color-primary, #233D63);
      outline-offset: 2px;
    }
  }
}

// =====================================================================
// Author Bio (article detail)
// =====================================================================
.author-bio {
  display: flex;
  gap: $ej-spacing-md;
  padding: $ej-spacing-lg;
  background: var(--ej-color-surface, #f8f9fa);
  border-radius: var(--ej-card-radius, 12px);
  margin-top: $ej-spacing-xl;

  &__avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;

    &--placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--ej-color-primary, #233D63);
      color: white;
      font-size: 1.5rem;
    }
  }

  &__name {
    font-weight: 600;
    color: var(--ej-color-headings, #1a1a1a);
    text-decoration: none;
    &:hover { text-decoration: underline; }
  }

  &__text {
    font-size: 0.875rem;
    color: var(--ej-color-muted, #666);
    margin-top: $ej-spacing-xs;
  }
}

// =====================================================================
// Accessibility: prefers-reduced-motion
// =====================================================================
@media (prefers-reduced-motion: reduce) {
  .article-card,
  .share-buttons__btn {
    transition: none;
  }
  .article-card:hover {
    transform: none;
  }
}

// =====================================================================
// Print styles
// =====================================================================
@media print {
  .share-buttons,
  .author-page__social {
    display: none;
  }
}
```

### 10.4 Iconos

Todos los iconos en templates usan `{{ jaraba_icon('ui', 'name') }}` (ICON-CONVENTION-001).

Colores permitidos: `azul-corporativo`, `naranja-impulso`, `verde-innovacion`, `white`, `neutral`.

Variant `duotone` en contextos premium.

### 10.5 JavaScript

Se mantienen los JS existentes:
- `reading-progress.js`: Barra de progreso de lectura
- `newsletter-form.js`: Formulario de newsletter
- `article-canvas-editor.js`: Bridge Canvas/GrapesJS

Se anadir logica de `data-copy-url` para el boton "Copiar enlace" en share buttons (ya existe en `jaraba_blog/js/blog.js`, se reutiliza la logica).

### 10.6 Body classes

Se mantiene `jaraba_content_hub_preprocess_html()` que ya anade:
- `page-content-hub`
- `page--clean-layout`
- `page-blog`
- `page-article-canvas-editor`

Se anade:
- `page-blog-author` — para la pagina de autor
- `page-blog-category` — para la pagina de categoria

### 10.7 Mobile-first responsive

Base styles sin media queries → `min-width` breakpoints:
- 576px: 2 columnas en grid
- 768px: 3 columnas
- 1024px: Layout completo
- 1200px: Max-width contenedor

### 10.8 Modales slide-panel para CRUD

CRUD de autores usa slide-panel (SLIDE-PANEL-RENDER-001):
- `renderPlain()` (no `render()`) para evitar BigPipe placeholders
- `$form['#action'] = $request->getRequestUri()` explicito
- No `$form_state->setCached(TRUE)` (FORM-CACHE-001)

---

## 11. Internacionalizacion (i18n)

| Contexto | Mecanismo | Ejemplo |
|----------|-----------|---------|
| Templates Twig | `{% trans %}...{% endtrans %}` | `{% trans %}Compartir articulo{% endtrans %}` |
| PHP Controllers/Services | `$this->t('texto', ['@var' => $value])` | `$this->t('Blog de @name', ['@name' => $siteName])` |
| JavaScript | `Drupal.t('texto')` | `Drupal.t('Enlace copiado')` |
| Entity labels | `@Translation()` en annotations | `label = @Translation("Content Author")` |
| Route titles | `_title` o `_title_callback` | `_title: 'Categoria'` |
| Allowed values | `t()` en `allowed_values` | `'draft' => t('Borrador')` |

---

## 12. Seguridad

### 12.1 CSRF-REQUEST-HEADER-001

Todas las rutas API que aceptan POST/PATCH/DELETE usan:
```yaml
requirements:
  _csrf_request_header_token: 'TRUE'
```

JS obtiene el token de `/session/token` y lo envia como header `X-CSRF-Token`.

### 12.2 Sanitizacion HTML/CSS canvas

Se mantienen los sanitizers existentes en `ArticleCanvasApiController`:
- `sanitizePageBuilderHtml()`: Elimina event handlers JS, tags peligrosos
- `sanitizeCss()`: Elimina `javascript:`, `expression()`, `@import`, `behavior:`, `-moz-binding:`
- `sanitizeHtml()`: Usa filtro HTML de Drupal

### 12.3 TWIG-XSS-001

`|escape` por defecto. `|safe_html` solo para contenido sanitizado (rendered_html del canvas, body procesado).

### 12.4 TENANT-ISOLATION-ACCESS-001

Verificacion tenant en TODOS los access handlers:
- `ContentArticleAccessControlHandler` (S1 fix)
- `ContentCategoryAccessControlHandler` (S2 fix)
- `ContentAuthorAccessControlHandler` (nuevo)

### 12.5 API-WHITELIST-001

Los endpoints de guardado usan `ALLOWED_FIELDS` para filtrar campos aceptados del request JSON.

### 12.6 INNERHTML-XSS-001

`Drupal.checkPlain()` en todos los JS que insertan contenido de usuario en el DOM.

### 12.7 PRESAVE-RESILIENCE-001

```php
// Patron correcto:
if (\Drupal::hasService('optional_service')) {
    try {
        \Drupal::service('optional_service')->method();
    }
    catch (\Exception $e) {
        \Drupal::logger('jaraba_content_hub')->warning('Optional service failed: @error', [
            '@error' => $e->getMessage(),
        ]);
    }
}
```

### 12.8 accessCheck(TRUE) en entity queries publicas

Todas las queries en controladores y servicios que sirven datos publicos usan `accessCheck(TRUE)`. Solo las queries internas (cron, migration, stats administrativas) usan `accessCheck(FALSE)`.

---

## 13. Integracion con Ecosistema

### 13.1 TenantContextService / TenantBridgeService

- `tenant_id` migrado a `entity_reference → group` (TENANT-BRIDGE-001)
- Auto-asignacion en presave via `TenantContextService::getCurrentTenant()`
- Access handlers verifican tenant match

### 13.2 PlanResolverService (feature gates)

Canvas Editor gated por plan: Free/Starter NO tienen acceso, Professional/Enterprise SI.

### 13.3 AI Writing Assistant

- `ContentWriterAgent` (verificar B1-B2)
- `WritingAssistantService` orquesta las 5 acciones AI
- `AIIdentityRule::apply()` en todas las respuestas AI (AI-IDENTITY-001)

### 13.4 Canvas Editor (GrapesJS)

Se mantiene sin cambios. Los nuevos campos de ContentArticle no afectan el Canvas Editor.

### 13.5 Sistema de iconos (jaraba_icon)

`jaraba_icon('category', 'name', $options)` en PHP. `{{ jaraba_icon('category', 'name') }}` en Twig.

### 13.6 Newsletter

`NewsletterBridgeService` se mantiene. El hook `content_article_update` ya detecta transicion a `published` y crea draft de newsletter.

### 13.7 Recommendation engine

`RecommendationService` (Qdrant) se mantiene. El indexado automatico en insert/update hooks se mantiene.

### 13.8 Analytics / engagement_score

Se mantiene `engagement_score` (existente). Se anade `views_count` (nuevo). Ambos disponibles para dashboards y trending widgets.

---

## 14. Configuracion Administrable via Drupal UI

### 14.1 Theme Settings

Variables CSS inyectables (70+ propiedades) se mantienen sin cambios. Los nuevos componentes consumen `var(--ej-*)`.

### 14.2 config/install

Verificar que `jaraba_content_hub.settings.yml` incluye valores por defecto para:
- `articles_per_page`: 12
- `show_reading_time`: TRUE
- `enable_canvas_editor`: TRUE
- `rss_items_limit`: 20

### 14.3 config/schema

Anadir schema para nuevos settings en `jaraba_content_hub.schema.yml`.

### 14.4 Feature gate canvas_editor

Canvas Editor disponible segun plan del tenant via `PlanResolverService`.

---

## 15. Navegacion Drupal Admin

### 15.1 /admin/structure

- `/admin/structure/content-hub/article` — Field UI para ContentArticle
- `/admin/structure/content-hub/categories` — Listado de categorias
- `/admin/structure/content-hub/authors` — Listado de autores (NUEVO)

### 15.2 /admin/content

- `/admin/content/articles` — Listado de articulos
- `/admin/content/content-authors` — Listado de autores (NUEVO)

### 15.3 Entity links

| Entidad | add-form | edit-form | delete-form | canonical |
|---------|----------|-----------|-------------|-----------|
| ContentArticle | /admin/content/articles/add | /admin/content/articles/{id}/edit | /admin/content/articles/{id}/delete | /blog/{slug} |
| ContentCategory | /admin/structure/content-hub/categories/add | .../{id}/edit | .../{id}/delete | /blog/category/{id} |
| ContentAuthor | /admin/content/content-authors/add | .../{id}/edit | .../{id}/delete | /blog/autor/{slug} |

---

## 16. Plan de Deprecacion de jaraba_blog

### Fase 1: Consolidar funcionalidades en content_hub

Implementar todas las funcionalidades backporteadas descritas en las secciones 7-10. Verificar que content_hub cubre el 100% de las capacidades de jaraba_blog.

### Fase 2: Migrar datos blog_post → content_article

Ejecutar script de migracion `scripts/migrate-blog-to-content-hub.php`:
1. Mapear blog_category → content_category
2. Mapear blog_author → content_author
3. Migrar blog_post → content_article (usando mappings)
4. Verificar integridad de datos

### Fase 3: Redirigir rutas /blog → content_hub routes

Si ambos modulos estaban habilitados, las rutas de jaraba_blog colisionan con content_hub. Al desinstalar jaraba_blog, las rutas de content_hub toman precedencia automaticamente.

### Fase 4: Desinstalar jaraba_blog

```bash
lando drush module:uninstall jaraba_blog -y
lando drush entity:updates
lando drush cr
```

### Fase 5: Cleanup

Remover `jaraba_blog` de `core.extension.yml`. Verificar que las tablas `blog_post`, `blog_category`, `blog_author` ya no existen (se eliminan con `hook_uninstall`).

---

## 17. Fases de Implementacion

### Fase 1: Entity Schema [8-12h]

1. Crear `ContentAuthor.php`, `ContentAuthorInterface.php`
2. Crear `ContentAuthorForm.php`, `ContentAuthorSettingsForm.php`
3. Crear `ContentAuthorAccessControlHandler.php`, `ContentAuthorListBuilder.php`
4. Modificar `ContentArticle.php`: +10 campos backport
5. Modificar `ContentCategory.php`: +5 campos backport
6. Escribir update hooks 10005-10009 en `.install`
7. Actualizar `.permissions.yml` con nuevos permisos
8. Actualizar entity links (.links.menu.yml, .links.action.yml, .links.task.yml)
9. Ejecutar `drush updb && drush entity:updates && drush cr`
10. Verificar tablas creadas y campos instalados

### Fase 2: Services Consolidation [6-10h]

1. Ampliar `ArticleService.php`: +9 metodos backport
2. Ampliar `CategoryService.php`: +updatePostsCount(), getActiveCategories()
3. Reescribir `SeoService.php` con SEO completo
4. Crear `RssService.php`
5. Actualizar `services.yml`
6. Inyectar `Connection $database` en ArticleService
7. Inyectar `FileUrlGeneratorInterface` en SeoService

### Fase 3: Controllers + Routes [6-8h]

1. Ampliar `BlogController.php`: +category()
2. Crear `AuthorController.php`
3. Crear `RssController.php`
4. Ampliar `BlogArticleController.php`: +view tracking, +SEO head
5. Actualizar `routing.yml`: +rutas category, author, RSS, API view tracking
6. Actualizar `ContentHubDashboardController.php`: +stats completas
7. Verificar todas las rutas con `drush router:rebuild`

### Fase 4: Templates + SCSS [8-12h]

1. Crear `content-hub-author-page.html.twig`
2. Crear `partials/_article-card.html.twig`
3. Crear `partials/_author-bio.html.twig`
4. Crear `partials/_share-buttons.html.twig`
5. Modificar `content-hub-blog-index.html.twig`: +author filters, +tags
6. Modificar `content-article--full.html.twig`: +related, +adjacent, +share, +author bio
7. Modificar `content-hub-category-page.html.twig`: +imagen, +meta SEO
8. Actualizar `hook_theme()` en `.module`: +content_hub_author_page
9. Implementar `template_preprocess_content_author()` en `.module`
10. Anadir SCSS para author page, article card, share buttons, author bio
11. Compilar SCSS y verificar sin warnings Dart Sass

### Fase 5: Security Fixes [4-6h]

1. Fix S1: Actualizar `ContentArticleAccessControlHandler` — `target_id` vs `value`
2. Fix S2: Anadir `checkTenantIsolation()` a `ContentCategoryAccessControlHandler`
3. Crear `ContentAuthorAccessControlHandler` con tenant isolation
4. Verificar `accessCheck(TRUE)` en todas las queries publicas
5. Verificar CSRF en nuevas rutas API

### Fase 6: Directive Compliance [4-6h]

1. D1: Implementar `template_preprocess_content_author()`
2. D2: Reemplazar URLs hardcodeadas con `Url::fromRoute()`
3. D3: Envolver todos los strings en mecanismos i18n
4. D4: Anadir `:focus-visible` y `prefers-reduced-motion`
5. D5: Reemplazar iconos inline con `jaraba_icon()`
6. D6: Cambiar `accessCheck(FALSE)` a `accessCheck(TRUE)` en queries publicas

### Fase 7: World-Class Gaps [6-10h]

1. G1: Campo `vertical` en ContentArticle
2. G2: SEO completo (SeoService reescrito)
3. G3: `featured_image` en ContentCategory
4. G4: `is_active` en ContentCategory
5. G6: `posts_count` cache en ContentCategory + updatePostsCount()
6. G7: Verificar image styles instalados
7. G8: RSS feed (RssService + RssController)
8. G9: View tracking (views_count + trackView())
9. G10: Cron para publicacion programada

### Fase 8: Data Migration + jaraba_blog Deprecation [4-6h]

1. Escribir script de migracion `scripts/migrate-blog-to-content-hub.php`
2. Ejecutar migracion en staging
3. Verificar integridad de datos migrados
4. Desinstalar `jaraba_blog`
5. Limpiar `core.extension.yml`
6. Rebuild router y cache

### Fase 9: Testing + QA [6-8h]

1. Unit tests para ArticleService metodos nuevos
2. Unit tests para SeoService
3. Unit tests para RssService
4. Kernel tests para ContentAuthor entity
5. Kernel tests para tenant isolation en access handlers
6. Kernel tests para update hooks
7. Verificacion manual completa (checklist seccion 19)

---

## 18. Estrategia de Testing

### 18.1 Unit tests

| Test | Clase | Que verifica |
|------|-------|-------------|
| ArticleService::calculateReadingTime | ArticleServiceTest | 200 wpm calculation |
| ArticleService::getAllTags | ArticleServiceTest | Tag parsing from comma-separated |
| SeoService::generateArticleSeo | SeoServiceTest | Meta, OG, Twitter, JSON-LD generation |
| SeoService::generateListingSeo | SeoServiceTest | Listing SEO with category/author |
| SeoService::analyzeArticleSeo | SeoServiceTest | SEO score calculation |
| RssService::generateFeed | RssServiceTest | RSS XML generation, valid structure |

### 18.2 Kernel tests

| Test | Que verifica |
|------|-------------|
| ContentAuthorKernelTest | Entity creation, field values, save/load cycle |
| ContentArticleFieldsTest | New backported fields exist and work |
| ContentCategoryFieldsTest | New backported fields exist and work |
| TenantIsolationArticleTest | Access denied for wrong tenant update/delete |
| TenantIsolationCategoryTest | Access denied for wrong tenant update/delete |
| TenantIsolationAuthorTest | Access denied for wrong tenant update/delete |
| UpdateHooksTest | Schema migrations run without errors |
| ScheduledPublishingTest | Cron publishes scheduled articles |

### 18.3 Functional tests

| Test | Que verifica |
|------|-------------|
| BlogListingTest | /blog returns 200, shows articles |
| CategoryPageTest | /blog/categoria/{slug} returns 200, shows filtered articles |
| AuthorPageTest | /blog/autor/{slug} returns 200, shows author info |
| RssFeedTest | /blog/feed.xml returns valid RSS XML |
| ViewTrackingTest | POST /api/v1/content/articles/{uuid}/view increments count |
| SeoHeadTest | Article pages include OG, Twitter, canonical, JSON-LD |

---

## 19. Verificacion y Despliegue

### 19.1 Checklist manual (20+ items)

1. [ ] `lando drush cr` sin errores PHP
2. [ ] `lando drush entity:updates` sin pending updates
3. [ ] Crear ContentAuthor → verificar form PremiumEntityFormBase
4. [ ] Crear ContentArticle con tenant_id → verificar entity_reference a group
5. [ ] Verificar tenant isolation: usuario de tenant A NO puede editar articulo de tenant B
6. [ ] Publicar articulo → verificar SEO head (OG, Twitter, JSON-LD)
7. [ ] Visitar `/blog` → verificar listing con paginacion
8. [ ] Visitar `/blog/{slug}` → verificar related posts, adjacent nav, share, author bio
9. [ ] Visitar `/blog/categoria/{slug}` → verificar imagen y meta SEO
10. [ ] Visitar `/blog/autor/{slug}` → verificar pagina de autor
11. [ ] Visitar `/blog/feed.xml` → verificar RSS valido
12. [ ] Programar articulo → verificar que cron lo publica (`lando drush cron`)
13. [ ] Verificar view tracking (views_count incrementa al visitar articulo)
14. [ ] Verificar is_featured flag en listing
15. [ ] Verificar Canvas Editor sigue funcionando (layout_mode = canvas)
16. [ ] Verificar i18n: textos traducibles en espanol
17. [ ] Verificar WCAG: focus-visible, Tab navigation, reduced-motion
18. [ ] Verificar iconos: jaraba_icon() con colores permitidos
19. [ ] Verificar SCSS compilado sin warnings Dart Sass
20. [ ] Verificar mobile responsive (320px → 1440px)
21. [ ] Verificar share buttons (Twitter, LinkedIn, Copy link)
22. [ ] Verificar tags en articulos (comma-separated, display, aggregation)

### 19.2 Comandos de verificacion

```bash
# Rebuild completo
lando drush cr

# Verificar entity updates
lando drush entity:updates

# Ejecutar cron (publicacion programada)
lando drush cron

# Run tests
lando php vendor/bin/phpunit --testsuite Unit --filter ContentHub
lando php vendor/bin/phpunit --testsuite Kernel --filter ContentHub

# Verificar rutas
lando drush router:rebuild
lando drush route:list | grep content_hub
lando drush route:list | grep blog
```

### 19.3 Browser verification URLs

| URL | Verificar |
|-----|-----------|
| `/es/blog` | Listing con paginacion, filtros, trending |
| `/es/blog/{slug}` | Detail con SEO, share, author bio, related, adjacent |
| `/es/blog/categoria/{slug}` | Category con imagen, meta, articles filtrados |
| `/es/blog/autor/{slug}` | Author con avatar, bio, social, articles |
| `/es/blog/feed.xml` | RSS XML valido |
| `/es/content-hub` | Dashboard con stats completas |
| `/es/content-hub/articles/{id}/canvas` | Canvas Editor funcional |
| `/es/admin/content/articles` | Admin list con nuevas columnas |
| `/es/admin/content/content-authors` | Authors admin list |

### 19.4 Rollback strategy

1. Los update hooks son incrementales (no destructivos)
2. Si falla la migracion de tenant_id, los valores se preservan en backup
3. jaraba_blog no se desinstala hasta verificar consolidacion completa
4. Git revert disponible para todos los cambios de codigo

---

## 20. Troubleshooting

### Error: "The entity type content_author does not exist"

**Causa:** Update hook 10005 no se ha ejecutado.
**Solucion:** `lando drush updb && lando drush cr`

### Error: "Column 'tenant_id' cannot be null" tras migracion

**Causa:** Update hooks 10008/10009 no restauraron los valores.
**Solucion:** Verificar que las tablas `content_article_field_data` / `content_category_field_data` existen. Re-ejecutar el update hook.

### Error: "Route jaraba_content_hub.blog.category not found"

**Causa:** Las nuevas rutas no se han registrado en el router.
**Solucion:** `lando drush router:rebuild && lando drush cr`

### Error: "Plugin ID 'content_author' was not found"

**Causa:** La clase ContentAuthor no tiene el annotation correcta o el autoloader no la encuentra.
**Solucion:** Verificar namespace, annotation, y ejecutar `lando composer dump-autoload && lando drush cr`

### Error: colision de rutas /blog entre modulos

**Causa:** Ambos modulos estan habilitados y registran `/blog`.
**Solucion:** Desinstalar `jaraba_blog` primero: `lando drush module:uninstall jaraba_blog -y`

### RSS feed devuelve 404

**Causa:** La ruta `/blog/feed.xml` no esta registrada.
**Solucion:** Verificar que la ruta esta en `routing.yml` y ejecutar `lando drush router:rebuild`

### Canvas Editor no funciona tras migracion

**Causa:** Los campos canvas (layout_mode, canvas_data, rendered_html) fueron modificados accidentalmente.
**Solucion:** Los campos canvas NO se modifican en esta consolidacion. Verificar que el update hook 10001 (original) sigue aplicado.

---

## 21. Referencias Cruzadas

| Concepto | Documento de Referencia |
|----------|----------------------|
| TENANT-BRIDGE-001 | `MEMORY.md` → Architecture Rules |
| PREMIUM-FORMS-PATTERN-001 | `ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php` |
| ENTITY-PREPROCESS-001 | `MEMORY.md` → Content Hub / Blog |
| PRESAVE-RESILIENCE-001 | `MEMORY.md` → Content Hub / Blog |
| Canvas Editor | `docs/implementacion/2026-02-26_Plan_Implementacion_GrapesJS_Content_Hub_v1.md` |
| AI Writing | `jaraba_content_hub/src/Agent/ContentWriterAgent.php` |
| Blog Clase Mundial | `docs/implementacion/2026-02-26_Blog_Clase_Mundial_Plan_Implementacion.md` |
| IA Nivel 5 | `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md` |
| DefaultEntityAccessControlHandler | `ecosistema_jaraba_core/src/Access/DefaultEntityAccessControlHandler.php` |
| TenantContextService | `ecosistema_jaraba_core/src/Service/TenantContextService.php` |
| TenantBridgeService | `ecosistema_jaraba_core/src/Service/TenantBridgeService.php` |
| Gold Standard Doc | `docs/implementacion/20260220c-178_Plan_Implementacion_Secure_Messaging.md` |
| Image Styles | `config/sync/image.style.article_card.yml`, `article_featured.yml`, `article_hero.yml` |

---

## 22. Registro de Cambios

| Version | Fecha | Cambios |
|---------|-------|---------|
| 1.0.0 | 2026-02-26 | Creacion inicial del documento con 22 secciones, 24+ hallazgos, 9 fases de implementacion |
