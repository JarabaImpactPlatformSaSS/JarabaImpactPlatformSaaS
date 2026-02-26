# Plan de Implementacion: Integracion GrapesJS Canvas Editor en Content Hub

**Fecha de creacion:** 2026-02-26 14:00
**Ultima actualizacion:** 2026-02-26 14:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion
**Modulo:** `jaraba_content_hub`
**Documentos fuente:** Plan de arquitectura interno, `CanvasEditorController.php`, `CanvasApiController.php`, `PageContentViewBuilder.php`, `ContentArticle.php`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
3. [Tabla de Cumplimiento de Directrices del Proyecto](#3-tabla-de-cumplimiento-de-directrices-del-proyecto)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Entorno de Desarrollo](#5-entorno-de-desarrollo)
6. [Arquitectura del Modulo](#6-arquitectura-del-modulo)
   - 6.1 [Decision Arquitectonica: Shared Service Pattern](#61-decision-arquitectonica-shared-service-pattern)
   - 6.2 [Nuevos Campos en ContentArticle](#62-nuevos-campos-en-contentarticle)
   - 6.3 [Update Hook para Schema Migration](#63-update-hook-para-schema-migration)
   - 6.4 [ArticleCanvasEditorController](#64-articlecanvaseditorcontroller)
   - 6.5 [ArticleCanvasApiController (REST Endpoints)](#65-articlecanvasapicontroller-rest-endpoints)
   - 6.6 [Renderizado Publico: Integracion en Preprocess](#66-renderizado-publico-integracion-en-preprocess)
   - 6.7 [Servicios Reutilizados del Page Builder](#67-servicios-reutilizados-del-page-builder)
   - 6.8 [Permisos y Access Control](#68-permisos-y-access-control)
   - 6.9 [Estructura de Archivos Completa](#69-estructura-de-archivos-completa)
7. [Arquitectura Frontend](#7-arquitectura-frontend)
   - 7.1 [Zero Region Policy](#71-zero-region-policy)
   - 7.2 [Template article-canvas-editor.html.twig](#72-template-article-canvas-editorhtmltwig)
   - 7.3 [Template Articulo Publico con Canvas Renderizado](#73-template-articulo-publico-con-canvas-renderizado)
   - 7.4 [Parciales Reutilizados y Nuevos](#74-parciales-reutilizados-y-nuevos)
   - 7.5 [SCSS: Federated Design Tokens + Canvas Article Styles](#75-scss-federated-design-tokens--canvas-article-styles)
   - 7.6 [JavaScript: Reutilizacion del Engine GrapesJS](#76-javascript-reutilizacion-del-engine-grapesjs)
   - 7.7 [Modales para Acciones CRUD del Editor](#77-modales-para-acciones-crud-del-editor)
   - 7.8 [Integracion con Header, Navegacion y Footer](#78-integracion-con-header-navegacion-y-footer)
   - 7.9 [Mobile-first Responsive Layout](#79-mobile-first-responsive-layout)
   - 7.10 [Body Classes via hook_preprocess_html()](#710-body-classes-via-hook_preprocess_html)
8. [Internacionalizacion (i18n)](#8-internacionalizacion-i18n)
9. [Seguridad](#9-seguridad)
   - 9.1 [CSRF en Rutas API (CSRF-API-001)](#91-csrf-en-rutas-api-csrf-api-001)
   - 9.2 [Sanitizacion HTML/CSS del Canvas](#92-sanitizacion-htmlcss-del-canvas)
   - 9.3 [XSS Prevention en Twig (TWIG-XSS-001)](#93-xss-prevention-en-twig-twig-xss-001)
   - 9.4 [Tenant Isolation en Access Handler](#94-tenant-isolation-en-access-handler)
   - 9.5 [API Field Whitelist (API-WHITELIST-001)](#95-api-field-whitelist-api-whitelist-001)
10. [Integracion con Ecosistema Existente](#10-integracion-con-ecosistema-existente)
11. [Configuracion Administrable desde UI de Drupal](#11-configuracion-administrable-desde-ui-de-drupal)
12. [Navegacion en Estructura Drupal](#12-navegacion-en-estructura-drupal)
13. [Fases de Implementacion](#13-fases-de-implementacion)
14. [Estrategia de Testing](#14-estrategia-de-testing)
15. [Verificacion y Despliegue](#15-verificacion-y-despliegue)
16. [Troubleshooting](#16-troubleshooting)
17. [Referencias Cruzadas](#17-referencias-cruzadas)
18. [Registro de Cambios](#18-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Integracion del **Canvas Editor GrapesJS** existente en el modulo `jaraba_page_builder` con la entidad `ContentArticle` del modulo `jaraba_content_hub`. Los autores podran componer articulos premium con edicion visual drag-and-drop, los mismos 67+ bloques, 13 plugins JS (AI, SEO, thumbnails, icons, assets, etc.), 8 breakpoints responsivos y el copiloto IA integrado que ya disfrutan para landing pages.

### 1.2 Por que se implementa

Actualmente, los articulos del Content Hub se escriben en un campo `body` de tipo `text_textarea` (20 filas) sin capacidades visuales. Esto limita severamente la calidad visual de los articulos frente a las landing pages del Page Builder, creando una experiencia inconsistente. Al integrar el Canvas Editor, los autores podran crear articulos con el mismo nivel de calidad visual que las paginas del Page Builder, sin duplicar codigo.

### 1.3 Alcance

Este documento cubre:

- **3 campos nuevos** en `ContentArticle`: `canvas_data`, `rendered_html`, `layout_mode`
- **1 controlador de editor**: `ArticleCanvasEditorController` (pagina completa con GrapesJS)
- **1 controlador API**: `ArticleCanvasApiController` (GET + PATCH para canvas data)
- **1 template Twig** para el editor: `article-canvas-editor.html.twig`
- **1 archivo JS bridge**: `article-canvas-editor.js` (configuracion GrapesJS para articulos)
- **Modificaciones** en `ContentArticleForm`, modulo, routing, permissions, libraries, SCSS
- **Retrocompatibilidad total**: articulos existentes siguen funcionando sin migracion

**Fuera de alcance** (Fase B/C futura):
- Revisionabilidad del canvas (revisions table)
- Tenant_id en ContentArticle
- URLs con slug en lugar de entity ID
- Busqueda publica

### 1.4 Filosofia de implementacion

- **Reutilizacion maxima:** El engine JS GrapesJS, los 13 plugins, los sanitizers HTML/CSS y los bloques se reutilizan directamente desde `jaraba_page_builder` via dependencia de library. NO se copian archivos.
- **Frontend limpio:** Template Zero-Region via `page--content-hub.html.twig` existente.
- **Retrocompatibilidad:** Campo `body` y textarea clasico siguen funcionando. Selector `layout_mode` permite elegir.
- **Feature gate:** Canvas Editor solo disponible para planes Professional/Enterprise via `PlanResolverService`.
- **Separacion de endpoints:** API propia en `jaraba_content_hub` (no compartir rutas de `jaraba_page_builder`) para permisos, logging y observabilidad independientes.

### 1.5 Estimacion

| Concepto | Horas Min | Horas Max |
|----------|-----------|-----------|
| Fase 1: Schema + Entity fields | 4h | 6h |
| Fase 2: Controllers (editor + API) | 8h | 12h |
| Fase 3: Templates + SCSS (editor UI + public view) | 8h | 10h |
| Fase 4: JS integration (library + drupalSettings bridge) | 6h | 8h |
| Fase 5: AI + SEO integration | 4h | 6h |
| Fase 6: Testing + QA | 6h | 8h |
| **TOTAL** | **36h** | **50h** |

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

### 2.1 Correspondencia con Page Builder existente (componentes reutilizados)

| Componente Page Builder | Reutilizacion | Seccion de este Plan |
|-------------------------|---------------|---------------------|
| `CanvasEditorController` | Patron de carga, design tokens, tenant context | [6.4](#64-articlecanvaseditorcontroller) |
| `CanvasApiController` | Patron save/load, sanitizacion HTML/CSS | [6.5](#65-articlecanvasapicontroller-rest-endpoints) |
| `PageContentViewBuilder` | Patron de renderizado canvas_data → HTML publico | [6.6](#66-renderizado-publico-integracion-en-preprocess) |
| `canvas-editor.html.twig` | Estructura HTML (toolbar, canvas, panels) — simplificada | [7.2](#72-template-article-canvas-editorhtmltwig) |
| `grapesjs-jaraba-canvas.js` | Engine JS completo reutilizado via library dependency | [7.6](#76-javascript-reutilizacion-del-engine-grapesjs) |
| 13 plugins JS (AI, SEO, icons, blocks, etc.) | Todos reutilizados via `jaraba_page_builder/grapesjs-canvas` | [7.6](#76-javascript-reutilizacion-del-engine-grapesjs) |
| `sanitizePageBuilderHtml()` | Metodo duplicado (protegido, no extraible como servicio sin refactor) | [9.2](#92-sanitizacion-htmlcss-del-canvas) |
| `sanitizeCss()` | Metodo duplicado (mismo motivo) | [9.2](#92-sanitizacion-htmlcss-del-canvas) |
| `sanitizeHtml()` | Metodo duplicado (limpia data-gjs-* y clases gjs-*) | [9.2](#92-sanitizacion-htmlcss-del-canvas) |
| Bloques premium (67+) | Todos disponibles en el canvas del articulo | [7.6](#76-javascript-reutilizacion-del-engine-grapesjs) |
| Design Tokens | Misma extraccion de CSS custom properties del tenant | [7.5](#75-scss-federated-design-tokens--canvas-article-styles) |

### 2.2 Correspondencia con Content Hub existente (entidad + servicios)

| Componente Content Hub | Cambio | Seccion de este Plan |
|------------------------|--------|---------------------|
| `ContentArticle.php` baseFieldDefinitions | +3 campos (canvas_data, rendered_html, layout_mode) | [6.2](#62-nuevos-campos-en-contentarticle) |
| `ContentArticleForm.php` | +seccion canvas, layout_mode selector, link al editor | [6.4](#64-articlecanvaseditorcontroller) |
| `jaraba_content_hub.module` hook_theme | +article_canvas_editor theme hook | [7.2](#72-template-article-canvas-editorhtmltwig) |
| `jaraba_content_hub.module` preprocess_html | +body class page-article-canvas-editor | [7.10](#710-body-classes-via-hook_preprocess_html) |
| `template_preprocess_content_article()` | +logica canvas_data/layout_mode para vista publica | [6.6](#66-renderizado-publico-integracion-en-preprocess) |
| `jaraba_content_hub.routing.yml` | +3 rutas (editor, API get, API save) | [6.5](#65-articlecanvasapicontroller-rest-endpoints) |
| `jaraba_content_hub.permissions.yml` | +permiso `use article canvas editor` | [6.8](#68-permisos-y-access-control) |
| `jaraba_content_hub.libraries.yml` | +library `article-canvas-editor` | [7.6](#76-javascript-reutilizacion-del-engine-grapesjs) |
| `WritingAssistantService` | Se integra en panel lateral del canvas editor | [10.1](#101-writingassistantservice) |
| `SeoService` | Se usa para validacion SEO dentro del editor | [10.2](#102-seoservice) |
| `ArticleService` | Sin cambios, sigue gestionando queries | - |
| `CategoryService` | Sin cambios | - |
| `BlogController` | Sin cambios — la vista publica ya funciona | - |

### 2.3 Diferencias clave con Page Builder

| Aspecto | Page Builder | Content Hub Canvas |
|---------|-------------|-------------------|
| Entidad | `PageContent` (revisionable) | `ContentArticle` (NO revisionable) |
| Seccion sections | Sidebar con drag-drop de secciones | Eliminada — canvas libre |
| Global Partials | Header/footer variant selectors | Eliminados — articulo usa header/footer del tema |
| Multipage | Tabs de pagina (IDE-style) | Eliminado — un articulo = un canvas |
| Marketplace | Template gallery slide-panel | Eliminado — usa bloques directos |
| Metadatos | Panel page-config (SEO, slug) | Panel lateral con categoria, SEO, publish date |
| API base path | `/api/v1/pages/{id}/canvas` | `/api/v1/articles/{id}/canvas` |
| Permiso requerido | `edit page builder content` | `use article canvas editor` |
| Revision en save | Si (setNewRevision) | No (sobrescribe) |
| Feature gate | Siempre disponible | Solo Professional/Enterprise |

---

## 3. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz | Prioridad | Estado | Donde se aplica |
|-----------|-----------|--------|-----------------|
| ZERO-REGION-POLICY | P0 | Cumple | Template editor usa `page--content-hub.html.twig` existente. Clean_content pattern. |
| PREMIUM-FORMS-PATTERN-001 | P1 | Cumple | `ContentArticleForm` ya extiende `PremiumEntityFormBase`. Solo se anade seccion canvas. |
| ICON-CONVENTION-001 | P1 | Cumple | `jaraba_icon()` en template del editor (toolbar, sidebar, actions). |
| ICON-DUOTONE-001 | P1 | Cumple | `variant: 'duotone'` en iconos de contexto premium (toolbar del editor). |
| CSRF-API-001 | P0 | Cumple | `_csrf_request_header_token: 'TRUE'` en rutas PATCH del API. |
| CSRF-JS-CACHE-001 | P1 | Cumple | Promise cacheada para CSRF token en `article-canvas-editor.js`. |
| TWIG-XSS-001 | P0 | Cumple | `{{ content|safe_html }}` en vista publica. Nunca `|raw` para contenido de usuario sin sanitizar. |
| API-WHITELIST-001 | P1 | Cumple | `ALLOWED_FIELDS` const en `ArticleCanvasApiController::saveCanvas()`. |
| TENANT-ISOLATION-ACCESS-001 | P0 | N/A | ContentArticle no tiene `tenant_id` actualmente (pendiente Fase C). Access handler valida ownership via `author` field. |
| ENTITY-PREPROCESS-001 | P0 | Cumple | `template_preprocess_content_article()` extendido con logica canvas/legacy. |
| ROUTE-LANGPREFIX-001 | P0 | Cumple | Todas las URLs JS usan `Drupal.url()` (que respeta prefijo `/es/`). |
| CONFIG-SCHEMA-001 | P1 | Cumple | Schema para nuevas config values si se anaden. |
| CSS-STICKY-001 | P1 | Cumple | Toolbar del editor con `position: sticky; top: 0; z-index: 100`. |
| FORM-MSG-001 | P1 | Cumple | Messages en `clean_messages` del template custom. |
| AI-IDENTITY-001 | P1 | Cumple | Si se integra copilot IA, usa `AIIdentityRule::apply()`. |
| INNERHTML-XSS-001 | P0 | Cumple | `Drupal.checkPlain()` para cualquier insercion textual en JS. |
| PRESAVE-RESILIENCE-001 | P0 | Cumple | Try-catch en presave hooks existentes, canvas save no afecta presave. |
| DART-SASS-MODERN | P1 | Cumple | `color.adjust()`, `@use`, no `darken()`/`lighten()` en nuevos SCSS. |
| MOBILE-FIRST | P1 | Cumple | SCSS mobile-first. Editor es desktop-only por UX (min-width: 1024px warning). |
| i18n | P0 | Cumple | `{% trans %}` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS. |
| SLIDE-PANEL-RENDER-001 | P1 | N/A | El editor no usa slide-panel para el canvas. Metadatos usan panel lateral integrado. |
| LABEL-NULLSAFE-001 | P1 | Cumple | ContentArticle tiene `"label" = "title"` en entity_keys, label() es safe. |

---

## 4. Requisitos Previos

### 4.1 Software

| Software | Version | Verificacion |
|----------|---------|-------------|
| PHP | 8.4+ | `php -v` |
| Drupal | 11.x | `lando drush status` |
| MariaDB | 10.11+ | `lando mysql --version` |
| Redis | 7.4 | `lando redis-cli info server` |
| Node.js | 18+ (para SCSS compilation) | `node -v` |
| Lando | 3.x | `lando version` |

### 4.2 Modulos Drupal requeridos

| Modulo | Estado | Proposito |
|--------|--------|-----------|
| `jaraba_content_hub` | Instalado | Modulo base — se modifica |
| `jaraba_page_builder` | Instalado | Engine GrapesJS — se reutiliza via library |
| `ecosistema_jaraba_core` | Instalado | PremiumEntityFormBase, TenantContextService, PlanResolverService |
| `jaraba_icon` | Instalado | Sistema de iconos jaraba_icon() |

### 4.3 Infraestructura

- Acceso a la instancia Drupal via Lando
- Tema `ecosistema_jaraba_theme` activo
- SCSS compilation funcional (`npx sass`)

### 4.4 Documentos de referencia

| Documento | Codigo | Proposito |
|-----------|--------|-----------|
| Directrices del Proyecto | v77.0.0 | Reglas de calidad y patrones |
| Canvas Editor v3 Arquitectura | doc 204b | Arquitectura maestra del canvas |
| AI Content Hub v2 | doc 128 | Especificacion de ContentArticle |
| Blog Clase Mundial Plan | 2026-02-26 | Plan de elevacion del blog |

---

## 5. Entorno de Desarrollo

### 5.1 Comandos

```bash
# Iniciar entorno
lando start

# Cache rebuild tras cambios en module/routing/services
lando drush cr

# Compilar SCSS
cd web/themes/custom/ecosistema_jaraba_theme
npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed

# Ejecutar update hooks tras anadir campos a la entidad
lando drush updb -y

# Reinstalar entidad (si se cambian baseFieldDefinitions sin update hook)
lando drush entity:updates

# Tests
cd /home/PED/JarabaImpactPlatformSaaS
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Kernel
```

### 5.2 URLs

| URL | Proposito |
|-----|-----------|
| `/content-hub/articles` | Lista de articulos (frontend tenant) |
| `/content-hub/articles/{id}/canvas` | Canvas Editor para un articulo (NUEVA) |
| `/api/v1/articles/{id}/canvas` | API GET/PATCH canvas data (NUEVA) |
| `/blog/{id}` | Vista publica del articulo |

---

## 6. Arquitectura del Modulo

### 6.1 Decision Arquitectonica: Shared Service Pattern

**Decision:** El Canvas Editor JS engine y sus plugins se **reutilizan directamente** desde `jaraba_page_builder`. La library del Content Hub declara dependencia en `jaraba_page_builder/grapesjs-canvas`.

**Justificacion:**
- El engine GrapesJS es identico para paginas y articulos
- Solo cambia: UI envolvente (toolbar simplificado, sin sections sidebar), endpoints API, y entidad destino
- Copiar archivos JS crea deuda tecnica (sincronizar cambios en dos sitios)
- Los sanitizers HTML/CSS se duplican como metodos protegidos porque no existe un servicio compartido (refactor futuro)

**Alternativa descartada:** Crear un modulo `jaraba_canvas_shared` — over-engineering para esta fase. Se puede extraer como servicio compartido cuando haya un tercer consumidor.

### 6.2 Nuevos Campos en ContentArticle

Se anaden 3 campos a `ContentArticle::baseFieldDefinitions()` en `jaraba_content_hub/src/Entity/ContentArticle.php`:

```php
// === Canvas Editor Fields (GrapesJS Integration) ===

// Datos JSON del estado completo del Canvas Editor.
$fields['canvas_data'] = BaseFieldDefinition::create('string_long')
    ->setLabel(t('Canvas Data'))
    ->setDescription(t('JSON con el estado completo del Canvas Editor (components, styles, html, css).'))
    ->setTranslatable(TRUE)
    ->setDefaultValue('{}')
    ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 50,
        'settings' => [
            'rows' => 5,
        ],
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

// HTML renderizado para vista publica (pre-sanitizado).
$fields['rendered_html'] = BaseFieldDefinition::create('string_long')
    ->setLabel(t('HTML Renderizado'))
    ->setDescription(t('HTML sanitizado generado desde el Canvas Editor para la vista publica.'))
    ->setTranslatable(TRUE)
    ->setDefaultValue('')
    ->setDisplayConfigurable('view', TRUE);

// Modo de layout: legacy (textarea) o canvas (GrapesJS).
$fields['layout_mode'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Modo de Edicion'))
    ->setDescription(t('Determina si el articulo usa el editor clasico o el Canvas Editor visual.'))
    ->setDefaultValue('legacy')
    ->setSetting('allowed_values', [
        'legacy' => t('Editor Clasico (textarea)'),
        'canvas' => t('Canvas Editor Visual'),
    ])
    ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
    ])
    ->setDisplayConfigurable('form', TRUE);
```

**Notas:**
- `canvas_data` sigue el mismo patron que `PageContent.php` linea 442 (tipo `string_long`, default `'{}'`)
- `rendered_html` almacena el HTML ya sanitizado para evitar sanitizacion en cada page view
- `layout_mode` con default `legacy` garantiza retrocompatibilidad total
- Los campos NO son revisionables (ContentArticle no tiene revision_table)
- `canvas_data` y `rendered_html` se ocultan del formulario premium (se gestionan via API)
- `layout_mode` se muestra en la seccion "Content" del formulario

### 6.3 Update Hook para Schema Migration

Archivo: `jaraba_content_hub/jaraba_content_hub.install` (crear si no existe)

```php
<?php

declare(strict_types=1);

/**
 * @file
 * Install, update and uninstall functions for jaraba_content_hub module.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Add canvas_data, rendered_html and layout_mode fields to ContentArticle.
 */
function jaraba_content_hub_update_10001(): string {
  $entity_type_id = 'content_article';
  $definition_manager = \Drupal::entityDefinitionUpdateManager();

  // canvas_data field.
  $canvas_data = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Canvas Data'))
      ->setDescription(t('JSON con el estado completo del Canvas Editor.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('{}')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
  $definition_manager->installFieldStorageDefinition(
      'canvas_data', $entity_type_id, 'jaraba_content_hub', $canvas_data
  );

  // rendered_html field.
  $rendered_html = BaseFieldDefinition::create('string_long')
      ->setLabel(t('HTML Renderizado'))
      ->setDescription(t('HTML sanitizado del Canvas Editor.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE);
  $definition_manager->installFieldStorageDefinition(
      'rendered_html', $entity_type_id, 'jaraba_content_hub', $rendered_html
  );

  // layout_mode field.
  $layout_mode = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modo de Edicion'))
      ->setDescription(t('Editor clasico o Canvas Editor visual.'))
      ->setDefaultValue('legacy')
      ->setSetting('allowed_values', [
          'legacy' => t('Editor Clasico (textarea)'),
          'canvas' => t('Canvas Editor Visual'),
      ])
      ->setDisplayOptions('form', [
          'type' => 'options_select',
          'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE);
  $definition_manager->installFieldStorageDefinition(
      'layout_mode', $entity_type_id, 'jaraba_content_hub', $layout_mode
  );

  return 'Added canvas_data, rendered_html, and layout_mode fields to ContentArticle.';
}
```

**Ejecucion:** `lando drush updb -y`

### 6.4 ArticleCanvasEditorController

Archivo: `jaraba_content_hub/src/Controller/ArticleCanvasEditorController.php`

Sigue el patron de `CanvasEditorController` del Page Builder pero simplificado:

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para el Canvas Editor visual de articulos.
 *
 * PROPOSITO:
 * Proporciona una experiencia de edicion visual con GrapesJS para
 * articulos del Content Hub. Version simplificada del CanvasEditorController
 * del Page Builder (sin sections sidebar, sin header/footer variants,
 * sin multipage).
 *
 * FEATURE GATE:
 * Solo accesible para planes Professional/Enterprise.
 * PlanResolverService::hasFeature('jaraba_content_hub', $tier, 'canvas_editor')
 */
class ArticleCanvasEditorController extends ControllerBase {

  /**
   * Tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Renderiza el Canvas Editor para un articulo.
   *
   * @param \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $content_article
   *   La entidad ContentArticle a editar.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP.
   *
   * @return array
   *   Render array con el Canvas Editor.
   */
  public function editor(ContentArticleInterface $content_article, Request $request): array {
    // Informacion del tenant para design tokens.
    $tenant_info = NULL;
    try {
      $tenant_info = $this->tenantContext->getCurrentTenant();
    }
    catch (\Exception $e) {
      // Sin contexto de tenant — usar defaults.
    }

    // Metadatos del articulo para panel lateral.
    $article_meta = [
      'id' => $content_article->id(),
      'title' => $content_article->getTitle(),
      'status' => $content_article->getPublicationStatus(),
      'excerpt' => $content_article->getExcerpt(),
      'seo_title' => $content_article->get('seo_title')->value ?? '',
      'seo_description' => $content_article->get('seo_description')->value ?? '',
      'reading_time' => $content_article->getReadingTime(),
    ];

    // Categoria.
    if ($content_article->hasField('category') && !$content_article->get('category')->isEmpty()) {
      $category = $content_article->get('category')->entity;
      if ($category) {
        $article_meta['category_name'] = $category->getName();
        $article_meta['category_id'] = $category->id();
      }
    }

    // Bibliotecas: reutilizar GrapesJS del Page Builder + JS bridge propio.
    $libraries = [
      'jaraba_page_builder/grapesjs-canvas',
      'jaraba_content_hub/article-canvas-editor',
      'ecosistema_jaraba_theme/slide-panel',
    ];

    return [
      '#theme' => 'article_canvas_editor',
      '#article' => $content_article,
      '#article_meta' => $article_meta,
      '#attached' => [
        'library' => $libraries,
        'drupalSettings' => [
          'articleCanvasEditor' => [
            'articleId' => $content_article->id(),
            'csrfToken' => \Drupal::service('csrf_token')->get('rest'),
            'apiBaseUrl' => Url::fromRoute(
              'jaraba_content_hub.api.article_canvas.get',
              ['content_article' => $content_article->id()]
            )->toString(),
            'articleMeta' => $article_meta,
          ],
          'jarabaCanvas' => [
            'editorMode' => 'article',
            'pageId' => $content_article->id(),
            'tenantId' => $tenant_info?->id(),
            'vertical' => $tenant_info?->get('vertical') ?? 'generic',
            'csrfToken' => \Drupal::service('csrf_token')->get('rest'),
            'designTokens' => $this->getDesignTokens($tenant_info),
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url'],
        'tags' => ['content_article:' . $content_article->id()],
      ],
    ];
  }

  /**
   * Titulo dinamico para la pagina del editor.
   */
  public function editorTitle(ContentArticleInterface $content_article): string {
    return (string) $this->t('Canvas Editor: @title', ['@title' => $content_article->getTitle()]);
  }

  /**
   * Obtiene Design Tokens del tenant.
   */
  protected function getDesignTokens(?object $tenant_info): array {
    if (!$tenant_info) {
      return [];
    }

    $tokens = [];
    if (method_exists($tenant_info, 'hasField')) {
      if ($tenant_info->hasField('color_primary') && !$tenant_info->get('color_primary')->isEmpty()) {
        $tokens['color-primary'] = $tenant_info->get('color_primary')->value;
      }
      if ($tenant_info->hasField('color_secondary') && !$tenant_info->get('color_secondary')->isEmpty()) {
        $tokens['color-secondary'] = $tenant_info->get('color_secondary')->value;
      }
      if ($tenant_info->hasField('font_family') && !$tenant_info->get('font_family')->isEmpty()) {
        $tokens['font-family'] = $tenant_info->get('font_family')->value;
      }
    }

    return $tokens;
  }

}
```

### 6.5 ArticleCanvasApiController (REST Endpoints)

Archivo: `jaraba_content_hub/src/Controller/ArticleCanvasApiController.php`

**Rutas nuevas** en `jaraba_content_hub.routing.yml`:

```yaml
# === Canvas Editor Routes ===

# Canvas Editor page (visual editor for articles)
jaraba_content_hub.article_canvas_editor:
  path: '/content-hub/articles/{content_article}/canvas'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\ArticleCanvasEditorController::editor'
    _title_callback: '\Drupal\jaraba_content_hub\Controller\ArticleCanvasEditorController::editorTitle'
  requirements:
    _permission: 'use article canvas editor'
  options:
    _admin_route: FALSE
    parameters:
      content_article:
        type: entity:content_article

# Canvas API: Get canvas data
jaraba_content_hub.api.article_canvas.get:
  path: '/api/v1/articles/{content_article}/canvas'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\ArticleCanvasApiController::getCanvas'
  methods: [GET]
  requirements:
    _permission: 'use article canvas editor'
  options:
    parameters:
      content_article:
        type: entity:content_article

# Canvas API: Save canvas data
jaraba_content_hub.api.article_canvas.save:
  path: '/api/v1/articles/{content_article}/canvas'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\ArticleCanvasApiController::saveCanvas'
  methods: [PATCH]
  requirements:
    _permission: 'use article canvas editor'
    _csrf_request_header_token: 'TRUE'
  options:
    parameters:
      content_article:
        type: entity:content_article
```

**Controlador API completo:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_content_hub\Entity\ContentArticleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API REST para el Canvas Editor de articulos.
 *
 * Endpoints:
 * - GET  /api/v1/articles/{id}/canvas — cargar datos canvas
 * - PATCH /api/v1/articles/{id}/canvas — guardar datos canvas
 *
 * Reutiliza los mismos metodos de sanitizacion que CanvasApiController
 * del Page Builder (FIX C2). Los sanitizers se duplican como metodos
 * protegidos aqui; un refactor futuro los extraera a un trait o servicio.
 *
 * SEGURIDAD:
 * - CSRF: _csrf_request_header_token en ruta PATCH
 * - HTML: sanitizePageBuilderHtml() — elimina script, on*, javascript:
 * - CSS: sanitizeCss() — elimina expression(), @import, behavior:
 * - API-WHITELIST-001: ALLOWED_FIELDS constante
 */
class ArticleCanvasApiController extends ControllerBase {

  /**
   * Campos permitidos en el payload de save.
   *
   * API-WHITELIST-001: Solo estos campos se procesan del JSON body.
   */
  private const ALLOWED_FIELDS = ['components', 'styles', 'html', 'css'];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * GET /api/v1/articles/{content_article}/canvas
   *
   * Obtiene los datos del canvas GrapesJS para un articulo.
   */
  public function getCanvas(ContentArticleInterface $content_article): JsonResponse {
    try {
      $canvasData = [];

      if ($content_article->hasField('canvas_data') && !$content_article->get('canvas_data')->isEmpty()) {
        $raw = $content_article->get('canvas_data')->value;
        if ($raw && $raw !== '{}') {
          $canvasData = json_decode($raw, TRUE) ?? [];
        }
      }

      return new JsonResponse([
        'components' => $canvasData['components'] ?? [],
        'styles' => $canvasData['styles'] ?? [],
        'html' => $canvasData['html'] ?? '',
        'css' => $canvasData['css'] ?? '',
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_content_hub')->error(
        'Error obteniendo canvas para articulo @id: @error',
        ['@id' => $content_article->id(), '@error' => $e->getMessage()]
      );

      return new JsonResponse(
        ['error' => 'Error al obtener el canvas'],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * PATCH /api/v1/articles/{content_article}/canvas
   *
   * Guarda los datos del canvas GrapesJS.
   */
  public function saveCanvas(ContentArticleInterface $content_article, Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE);

      if (empty($data)) {
        return new JsonResponse(
          ['error' => 'Datos invalidos'],
          Response::HTTP_BAD_REQUEST
        );
      }

      // API-WHITELIST-001: Solo procesar campos permitidos.
      $canvasData = [
        'components' => $data['components'] ?? [],
        'styles' => $data['styles'] ?? [],
        'html' => $this->sanitizePageBuilderHtml($data['html'] ?? ''),
        'css' => $this->sanitizeCss($data['css'] ?? ''),
        'updated_at' => date('c'),
      ];

      // Almacenar canvas_data.
      if ($content_article->hasField('canvas_data')) {
        $content_article->set('canvas_data', json_encode($canvasData, JSON_UNESCAPED_UNICODE));
      }

      // Almacenar rendered_html (limpio de artefactos GrapesJS).
      if ($content_article->hasField('rendered_html') && !empty($data['html'])) {
        $content_article->set('rendered_html', $this->sanitizeHtml($data['html']));
      }

      // Asegurar layout_mode = 'canvas' al guardar desde el editor.
      if ($content_article->hasField('layout_mode')) {
        $content_article->set('layout_mode', 'canvas');
      }

      $content_article->save();

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Canvas guardado correctamente',
        'article_id' => $content_article->id(),
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_content_hub')->error(
        'Error guardando canvas para articulo @id: @error',
        ['@id' => $content_article->id(), '@error' => $e->getMessage()]
      );

      return new JsonResponse(
        ['error' => 'Error al guardar el canvas: ' . $e->getMessage()],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Sanitiza HTML con lista blanca ampliada para el Canvas.
   *
   * FIX C2: Preserva svg, form, input, button, video, iframe, canvas, picture.
   * Elimina: <script>, event handlers on*, javascript: URLs, <object>, <embed>.
   *
   * Identico a CanvasApiController::sanitizePageBuilderHtml().
   */
  protected function sanitizePageBuilderHtml(string $html): string {
    if (empty($html)) {
      return '';
    }
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/\s+(href|src|action|data|formaction)\s*=\s*(?:"javascript:[^"]*"|\'javascript:[^\']*\')/i', '', $html);
    $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
    $html = preg_replace('/<embed\b[^>]*\/?>/i', '', $html);
    return $html;
  }

  /**
   * Sanitiza CSS para prevenir inyeccion de codigo.
   *
   * Identico a CanvasApiController::sanitizeCss().
   */
  protected function sanitizeCss(string $css): string {
    $css = preg_replace('/javascript\s*:/i', '', $css);
    $css = preg_replace('/expression\s*\(/i', '', $css);
    $css = preg_replace('/@import\b/i', '', $css);
    $css = preg_replace('/behavior\s*:/i', '', $css);
    $css = preg_replace('/-moz-binding\s*:/i', '', $css);
    return $css;
  }

  /**
   * Sanitiza HTML para almacenamiento publico.
   *
   * Limpia artefactos de GrapesJS (data-gjs-*, clases gjs-*).
   * Identico a CanvasApiController::sanitizeHtml().
   */
  protected function sanitizeHtml(string $html): string {
    $html = $this->sanitizePageBuilderHtml($html);
    $html = preg_replace('/\s+data-gjs-[^=]+="[^"]*"/i', '', $html);
    $html = preg_replace_callback(
      '/\sclass="([^"]*)"/i',
      function ($matches) {
        $classes = preg_split('/\s+/', $matches[1]);
        $filtered = array_filter($classes, fn($c) => !str_starts_with($c, 'gjs-'));
        if (empty($filtered)) {
          return '';
        }
        return ' class="' . implode(' ', $filtered) . '"';
      },
      $html
    );
    return trim($html);
  }

}
```

### 6.6 Renderizado Publico: Integracion en Preprocess

La vista publica del articulo usa la funcion `template_preprocess_content_article()` existente. Se extiende con logica para detectar `layout_mode` y renderizar el canvas:

**Modificacion en `jaraba_content_hub.module`:**

```php
/**
 * Extension de template_preprocess_content_article() para canvas.
 *
 * Cadena de prioridad:
 * 1. layout_mode === 'canvas' Y canvas_data no vacio → rendered_html con CSS
 * 2. layout_mode === 'legacy' O canvas_data vacio → body como texto (actual)
 */
function template_preprocess_content_article(array &$variables): void {
  /** @var \Drupal\jaraba_content_hub\Entity\ContentArticle $entity */
  $entity = $variables['elements']['#content_article'] ?? NULL;
  if (!$entity) {
    return;
  }

  // ... (codigo existente para $article array) ...

  // Canvas Editor integration.
  $layout_mode = $entity->hasField('layout_mode')
    ? ($entity->get('layout_mode')->value ?? 'legacy')
    : 'legacy';
  $article['layout_mode'] = $layout_mode;

  if ($layout_mode === 'canvas') {
    $rendered_html = $entity->hasField('rendered_html')
      ? ($entity->get('rendered_html')->value ?? '')
      : '';

    // Fallback: extraer HTML de canvas_data si rendered_html esta vacio.
    if (empty($rendered_html) && $entity->hasField('canvas_data')) {
      $canvas_raw = $entity->get('canvas_data')->value ?? '{}';
      $canvas_data = json_decode($canvas_raw, TRUE) ?: [];
      $rendered_html = $canvas_data['html'] ?? '';
    }

    $article['canvas_html'] = $rendered_html;

    // CSS personalizado del canvas.
    if ($entity->hasField('canvas_data')) {
      $canvas_raw = $entity->get('canvas_data')->value ?? '{}';
      $canvas_data = json_decode($canvas_raw, TRUE) ?: [];
      $article['canvas_css'] = $canvas_data['css'] ?? '';
    }
  }

  $variables['article'] = $article;
}
```

**En el template `content-article--full.html.twig`**, se anade la bifurcacion:

```twig
{# Contenido del articulo — bifurcacion canvas/legacy #}
{% if article.layout_mode == 'canvas' and article.canvas_html %}
  {# Canvas Editor: HTML pre-renderizado con estilos inyectados #}
  {% if article.canvas_css %}
    <style data-canvas-styles="true">{{ article.canvas_css }}</style>
  {% endif %}
  <div class="article-content article-content--canvas">
    {{ article.canvas_html|safe_html }}
  </div>
{% else %}
  {# Legacy: Texto procesado del campo body #}
  <div class="article-content article-content--prose">
    {{ article.body|safe_html }}
  </div>
{% endif %}
```

### 6.7 Servicios Reutilizados del Page Builder

No se crean servicios nuevos. Se reutilizan via dependency:

| Servicio | Modulo | Uso en Content Hub |
|----------|--------|--------------------|
| `jaraba_page_builder/grapesjs-canvas` | Library | Engine GrapesJS completo via library dependency |
| `ecosistema_jaraba_core.tenant_context` | Service | Contexto tenant para design tokens |
| `ecosistema_jaraba_core.plan_resolver` | Service | Feature gate canvas_editor |
| CSRF token | `csrf_token` service | Token para API calls |

### 6.8 Permisos y Access Control

**Nuevo permiso** en `jaraba_content_hub.permissions.yml`:

```yaml
# Canvas Editor permission
'use article canvas editor':
  title: 'Use Article Canvas Editor'
  description: 'Access the visual Canvas Editor for articles (GrapesJS).'
```

**Access control:**
- La ruta del editor requiere `use article canvas editor`
- Las rutas API requieren el mismo permiso
- La ruta PATCH ademas requiere `_csrf_request_header_token: 'TRUE'`
- `ContentArticleAccessControlHandler` existente valida ownership para update (via author field)

### 6.9 Estructura de Archivos Completa

```
jaraba_content_hub/
├── jaraba_content_hub.install                    ← CREAR (update hook 10001)
├── jaraba_content_hub.module                     ← MODIFICAR (+theme hook, +preprocess, +body class)
├── jaraba_content_hub.routing.yml                ← MODIFICAR (+3 rutas)
├── jaraba_content_hub.permissions.yml            ← MODIFICAR (+1 permiso)
├── jaraba_content_hub.libraries.yml              ← MODIFICAR (+library)
├── src/
│   ├── Entity/
│   │   └── ContentArticle.php                    ← MODIFICAR (+3 campos)
│   ├── Form/
│   │   └── ContentArticleForm.php                ← MODIFICAR (+seccion canvas)
│   └── Controller/
│       ├── ArticleCanvasEditorController.php      ← CREAR
│       ├── ArticleCanvasApiController.php         ← CREAR
│       ├── BlogController.php                     (sin cambios)
│       └── ...
├── templates/
│   ├── article-canvas-editor.html.twig            ← CREAR
│   ├── content-article--full.html.twig            ← MODIFICAR (+canvas bifurcacion)
│   └── ...
├── js/
│   ├── article-canvas-editor.js                   ← CREAR (JS bridge)
│   └── ...
└── tests/
    └── src/
        ├── Unit/
        │   └── Controller/
        │       └── ArticleCanvasApiSanitizationTest.php  ← CREAR
        └── Kernel/
            └── Entity/
                └── ContentArticleCanvasFieldsTest.php    ← CREAR

ecosistema_jaraba_theme/
└── scss/
    └── _content-hub.scss                          ← MODIFICAR (+canvas editor styles)
```

---

## 7. Arquitectura Frontend

### 7.1 Zero Region Policy

El Canvas Editor de articulos reutiliza el template `page--content-hub.html.twig` existente, que ya implementa la Zero Region Policy:

```twig
{# Zero Region Policy — No {{ page.* }} blocks #}
<a href="#main-content" class="visually-hidden focusable skip-link">
  {% trans %}Skip to main content{% endtrans %}
</a>

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  site_name: site_name, logo: logo, logged_in: logged_in
} %}

<main id="main-content" role="main" class="dashboard-main">
  {% if clean_messages %}
    <div class="messages-wrapper">{{ clean_messages }}</div>
  {% endif %}
  {{ clean_content }}
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

El editor Canvas se inyecta via `clean_content` desde el controlador, tal como el resto de paginas frontend del Content Hub.

### 7.2 Template article-canvas-editor.html.twig

Archivo: `jaraba_content_hub/templates/article-canvas-editor.html.twig`

Version simplificada del `canvas-editor.html.twig` del Page Builder:

```twig
{#
/**
 * @file
 * Canvas Editor para Articulos del Content Hub.
 *
 * Version simplificada del canvas-editor del Page Builder:
 * - Mantiene: toolbar, canvas GrapesJS, right panel (styles/properties/layers)
 * - Elimina: sections sidebar, global partials, multipage, marketplace
 * - Anade: panel lateral con metadatos del articulo
 *
 * VARIABLES:
 * - article: Entidad ContentArticle
 * - article_meta: Array con metadatos del articulo
 */
#}

<div class="article-canvas-editor" data-article-id="{{ article.id() }}">

  {# Toolbar Superior #}
  <header class="article-canvas-editor__toolbar" role="toolbar">
    <div class="article-canvas-editor__toolbar-left">
      <a href="{{ path('jaraba_content_hub.articles.frontend') }}" class="article-canvas-editor__back">
        {{ jaraba_icon('ui', 'arrow-left', { size: '20px' }) }}
        <span>{% trans %}Volver{% endtrans %}</span>
      </a>
      <div class="article-canvas-editor__separator" aria-hidden="true"></div>
      <h1 class="article-canvas-editor__title" title="{{ article.getTitle() }}">
        {{ article.getTitle()|length > 40 ? article.getTitle()|slice(0, 40) ~ '...' : article.getTitle() }}
      </h1>
    </div>

    <div class="article-canvas-editor__toolbar-center">
      {# Viewport Dropdown #}
      <div class="article-canvas-editor__viewport-dropdown" role="group"
           aria-label="{% trans %}Cambiar viewport{% endtrans %}">
        <button type="button" class="article-canvas-editor__viewport-trigger"
                id="viewport-dropdown-trigger"
                title="{% trans %}Cambiar resolucion{% endtrans %}"
                aria-expanded="false" aria-haspopup="true">
          <span id="viewport-trigger-icon">
            {{ jaraba_icon('ui', 'monitor', { size: '18px' }) }}
          </span>
          <span id="viewport-trigger-label">1920px</span>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>
        <div class="article-canvas-editor__viewport-panel" id="viewport-dropdown-panel" aria-hidden="true">
          <button type="button" class="article-canvas-editor__viewport-btn is-active"
                  data-viewport="desktop-xl" title="{% trans %}Escritorio XL{% endtrans %} (1920px)">
            {{ jaraba_icon('ui', 'monitor', { size: '16px' }) }} <span>XL</span>
          </button>
          <button type="button" class="article-canvas-editor__viewport-btn"
                  data-viewport="desktop" title="{% trans %}Escritorio{% endtrans %} (1280px)">
            {{ jaraba_icon('ui', 'monitor', { size: '16px' }) }} <span>1280</span>
          </button>
          <button type="button" class="article-canvas-editor__viewport-btn"
                  data-viewport="tablet" title="{% trans %}Tablet{% endtrans %} (768px)">
            {{ jaraba_icon('ui', 'tablet', { size: '16px' }) }} <span>768</span>
          </button>
          <button type="button" class="article-canvas-editor__viewport-btn"
                  data-viewport="mobile" title="{% trans %}Movil{% endtrans %} (375px)">
            {{ jaraba_icon('ui', 'smartphone', { size: '16px' }) }} <span>375</span>
          </button>
        </div>
      </div>

      {# Undo/Redo #}
      <div class="article-canvas-editor__history" role="group" aria-label="{% trans %}Historial{% endtrans %}">
        <button type="button" class="article-canvas-editor__btn" id="btn-undo"
                title="{% trans %}Deshacer{% endtrans %}" disabled>
          {{ jaraba_icon('ui', 'undo-2', { size: '18px' }) }}
        </button>
        <button type="button" class="article-canvas-editor__btn" id="btn-redo"
                title="{% trans %}Rehacer{% endtrans %}" disabled>
          {{ jaraba_icon('ui', 'redo-2', { size: '18px' }) }}
        </button>
      </div>
    </div>

    <div class="article-canvas-editor__toolbar-right">
      {# Preview #}
      <a href="{{ path('entity.content_article.canonical', { 'content_article': article.id() }) }}"
         class="article-canvas-editor__btn" target="_blank"
         title="{% trans %}Vista previa{% endtrans %}">
        {{ jaraba_icon('ui', 'eye', { size: '18px' }) }}
        <span class="article-canvas-editor__btn-label">{% trans %}Preview{% endtrans %}</span>
      </a>

      {# Save #}
      <button type="button" class="article-canvas-editor__btn article-canvas-editor__btn--primary"
              id="btn-save" title="{% trans %}Guardar cambios{% endtrans %}">
        {{ jaraba_icon('ui', 'save', { size: '18px' }) }}
        <span class="article-canvas-editor__btn-label">{% trans %}Guardar{% endtrans %}</span>
      </button>
    </div>
  </header>

  {# Layout Principal: Bloques | Canvas | Right Panel #}
  <div class="article-canvas-editor__layout">

    {# Panel Izquierdo: Bloques disponibles #}
    <aside class="article-canvas-editor__blocks-panel" id="gjs-blocks-panel">
      <div class="article-canvas-editor__panel-header">
        <h3>{{ jaraba_icon('ui', 'layout-grid', { size: '16px' }) }} {% trans %}Bloques{% endtrans %}</h3>
        <input type="search" class="article-canvas-editor__block-search"
               placeholder="{% trans %}Buscar bloque...{% endtrans %}"
               id="block-search" />
      </div>
      <div id="gjs-blocks-container" class="article-canvas-editor__blocks-list"></div>
    </aside>

    {# Canvas GrapesJS Central #}
    <div class="article-canvas-editor__canvas-wrapper">
      <div id="gjs-editor" class="article-canvas-editor__canvas">
        {# GrapesJS loading skeleton #}
        <div class="article-canvas-editor__loading" id="canvas-loading">
          <div class="article-canvas-editor__loading-spinner"></div>
          <p>{% trans %}Cargando Canvas Editor...{% endtrans %}</p>
        </div>
      </div>
    </div>

    {# Panel Derecho: Styles / Properties / Layers #}
    <aside class="article-canvas-editor__right-panel" id="gjs-right-panel">
      <div class="article-canvas-editor__panel-tabs" role="tablist">
        <button type="button" class="article-canvas-editor__tab is-active" data-tab="styles"
                role="tab" aria-selected="true">
          {{ jaraba_icon('ui', 'paintbrush', { size: '14px' }) }}
          <span>{% trans %}Estilos{% endtrans %}</span>
        </button>
        <button type="button" class="article-canvas-editor__tab" data-tab="traits"
                role="tab" aria-selected="false">
          {{ jaraba_icon('ui', 'settings', { size: '14px' }) }}
          <span>{% trans %}Props{% endtrans %}</span>
        </button>
        <button type="button" class="article-canvas-editor__tab" data-tab="layers"
                role="tab" aria-selected="false">
          {{ jaraba_icon('ui', 'layers', { size: '14px' }) }}
          <span>{% trans %}Capas{% endtrans %}</span>
        </button>
      </div>
      <div id="gjs-styles-container" class="article-canvas-editor__tab-content is-active" data-tab-content="styles"></div>
      <div id="gjs-traits-container" class="article-canvas-editor__tab-content" data-tab-content="traits"></div>
      <div id="gjs-layers-container" class="article-canvas-editor__tab-content" data-tab-content="layers"></div>
    </aside>
  </div>

  {# Status Indicator #}
  <div class="article-canvas-editor__status" id="canvas-status" aria-live="polite">
    <span class="article-canvas-editor__status-text">{% trans %}Listo{% endtrans %}</span>
  </div>

</div>
```

### 7.3 Template Articulo Publico con Canvas Renderizado

La modificacion en `content-article--full.html.twig` se describe en la seccion [6.6](#66-renderizado-publico-integracion-en-preprocess). La bifurcacion canvas/legacy renderiza:

- **Canvas mode:** `<div class="article-content--canvas">{{ article.canvas_html|safe_html }}</div>` con `<style>` inyectado
- **Legacy mode:** `<div class="article-content--prose">{{ article.body|safe_html }}</div>` (comportamiento actual)

### 7.4 Parciales Reutilizados y Nuevos

| Parcial | Estado | Uso |
|---------|--------|-----|
| `_header.html.twig` | Reutilizado | Header del tema (dentro de page--content-hub) |
| `_footer.html.twig` | Reutilizado | Footer del tema |
| `_reading-progress.html.twig` | Reutilizado | Barra de lectura en vista publica |
| `article-canvas-editor.html.twig` | Nuevo | Template completo del editor canvas |

### 7.5 SCSS: Federated Design Tokens + Canvas Article Styles

**Archivo:** `ecosistema_jaraba_theme/scss/_content-hub.scss` — se anade seccion para el canvas editor:

```scss
// === Article Canvas Editor ===

.article-canvas-editor {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 80px); // Descontar header del tema
  background: var(--ej-color-surface, #f8fafc);
}

.article-canvas-editor__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1rem;
  height: 52px;
  background: var(--ej-color-bg, #ffffff);
  border-bottom: 1px solid var(--ej-color-border, #e2e8f0);
  position: sticky;
  top: 0;
  z-index: 100; // CSS-STICKY-001
  gap: 1rem;
}

.article-canvas-editor__toolbar-left,
.article-canvas-editor__toolbar-center,
.article-canvas-editor__toolbar-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.article-canvas-editor__back {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: var(--ej-color-text-secondary, #64748b);
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.15s ease;

  &:hover {
    color: var(--ej-color-primary, #233D63);
  }
}

.article-canvas-editor__title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--ej-color-text, #1e293b);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 300px;
}

.article-canvas-editor__separator {
  width: 1px;
  height: 24px;
  background: var(--ej-color-border, #e2e8f0);
}

.article-canvas-editor__btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border: 1px solid var(--ej-color-border, #e2e8f0);
  border-radius: 6px;
  background: var(--ej-color-bg, #ffffff);
  color: var(--ej-color-text-secondary, #64748b);
  font-size: 0.8125rem;
  cursor: pointer;
  transition: all 0.15s ease;
  text-decoration: none;

  &:hover:not(:disabled) {
    background: var(--ej-color-surface, #f1f5f9);
    color: var(--ej-color-text, #1e293b);
  }

  &:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }

  &--primary {
    background: var(--ej-color-primary, #233D63);
    color: #ffffff;
    border-color: transparent;

    &:hover:not(:disabled) {
      background: color.adjust(#233D63, $lightness: -5%);
      color: #ffffff;
    }
  }
}

// Layout 3 columnas
.article-canvas-editor__layout {
  display: grid;
  grid-template-columns: 260px 1fr 280px;
  flex: 1;
  overflow: hidden;
}

// Panel Bloques (izquierda)
.article-canvas-editor__blocks-panel {
  background: var(--ej-color-bg, #ffffff);
  border-right: 1px solid var(--ej-color-border, #e2e8f0);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.article-canvas-editor__panel-header {
  padding: 0.75rem;
  border-bottom: 1px solid var(--ej-color-border, #e2e8f0);

  h3 {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: var(--ej-color-text, #1e293b);
  }
}

.article-canvas-editor__block-search {
  width: 100%;
  padding: 0.375rem 0.625rem;
  border: 1px solid var(--ej-color-border, #e2e8f0);
  border-radius: 6px;
  font-size: 0.8125rem;
  background: var(--ej-color-surface, #f8fafc);

  &:focus {
    outline: none;
    border-color: var(--ej-color-primary, #233D63);
    box-shadow: 0 0 0 2px rgba(35, 61, 99, 0.1);
  }
}

// Canvas Central
.article-canvas-editor__canvas-wrapper {
  background: var(--ej-color-surface, #f1f5f9);
  overflow: auto;
  display: flex;
  justify-content: center;
  padding: 1rem;
}

.article-canvas-editor__canvas {
  width: 100%;
  height: 100%;
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

// Panel Derecho
.article-canvas-editor__right-panel {
  background: var(--ej-color-bg, #ffffff);
  border-left: 1px solid var(--ej-color-border, #e2e8f0);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.article-canvas-editor__panel-tabs {
  display: flex;
  border-bottom: 1px solid var(--ej-color-border, #e2e8f0);
}

.article-canvas-editor__tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  padding: 0.625rem 0.5rem;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--ej-color-text-secondary, #64748b);
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.15s ease;

  &.is-active {
    color: var(--ej-color-primary, #233D63);
    border-bottom-color: var(--ej-color-primary, #233D63);
  }

  &:hover:not(.is-active) {
    color: var(--ej-color-text, #1e293b);
    background: var(--ej-color-surface, #f8fafc);
  }
}

.article-canvas-editor__tab-content {
  display: none;
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem;

  &.is-active {
    display: block;
  }
}

// Loading skeleton
.article-canvas-editor__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 1rem;
  color: var(--ej-color-text-secondary, #64748b);
}

.article-canvas-editor__loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--ej-color-border, #e2e8f0);
  border-top-color: var(--ej-color-primary, #233D63);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

// Status indicator
.article-canvas-editor__status {
  padding: 0.375rem 1rem;
  background: var(--ej-color-bg, #ffffff);
  border-top: 1px solid var(--ej-color-border, #e2e8f0);
  font-size: 0.75rem;
  color: var(--ej-color-text-secondary, #64748b);
}

// Viewport dropdown
.article-canvas-editor__viewport-dropdown {
  position: relative;
}

.article-canvas-editor__viewport-trigger {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.625rem;
  border: 1px solid var(--ej-color-border, #e2e8f0);
  border-radius: 6px;
  background: var(--ej-color-bg, #ffffff);
  font-size: 0.8125rem;
  cursor: pointer;
}

.article-canvas-editor__viewport-panel {
  position: absolute;
  top: calc(100% + 4px);
  left: 50%;
  transform: translateX(-50%);
  display: none;
  gap: 0.25rem;
  padding: 0.5rem;
  background: var(--ej-color-bg, #ffffff);
  border: 1px solid var(--ej-color-border, #e2e8f0);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  z-index: 200;

  &[aria-hidden="false"] {
    display: flex;
  }
}

.article-canvas-editor__viewport-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid transparent;
  border-radius: 6px;
  background: none;
  font-size: 0.6875rem;
  color: var(--ej-color-text-secondary, #64748b);
  cursor: pointer;

  &.is-active,
  &:hover {
    background: var(--ej-color-surface, #f1f5f9);
    color: var(--ej-color-primary, #233D63);
    border-color: var(--ej-color-border, #e2e8f0);
  }
}

// === Canvas content in public article view ===

.article-content--canvas {
  max-width: 100%;
  overflow: hidden;

  // Heredar estilos del tema para consistencia visual
  img {
    max-width: 100%;
    height: auto;
  }
}

// Responsive: Editor es desktop-only
@media (max-width: 1023px) {
  .article-canvas-editor {
    &::before {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.7);
      z-index: 9998;
    }

    &::after {
      content: attr(data-mobile-message);
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: var(--ej-color-bg, #ffffff);
      padding: 2rem;
      border-radius: 12px;
      text-align: center;
      z-index: 9999;
      max-width: 320px;
    }
  }
}
```

### 7.6 JavaScript: Reutilizacion del Engine GrapesJS

**Library declaration** en `jaraba_content_hub.libraries.yml`:

```yaml
# Canvas Editor for Articles (simplified GrapesJS integration)
article-canvas-editor:
  version: 1.0.0
  js:
    js/article-canvas-editor.js: { attributes: { defer: true } }
  dependencies:
    - core/drupal
    - core/drupalSettings
    - jaraba_page_builder/grapesjs-canvas
```

**JS Bridge** — `jaraba_content_hub/js/article-canvas-editor.js`:

```javascript
/**
 * @file
 * Article Canvas Editor — Bridge JS para GrapesJS en articulos.
 *
 * Configura GrapesJS con endpoints de la API de articulos
 * y maneja save/load especifico para ContentArticle.
 *
 * Reutiliza el engine grapesjs-jaraba-canvas.js del Page Builder
 * via library dependency. Solo sobreescribe URLs y comportamiento
 * de guardado.
 */
(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * CSRF token promise cache (CSRF-JS-CACHE-001).
   */
  let csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(response => response.text());
    }
    return csrfTokenPromise;
  }

  Drupal.behaviors.articleCanvasEditor = {
    attach: function (context) {
      const editorEl = context.querySelector('#gjs-editor');
      if (!editorEl || editorEl.dataset.gjsInitialized) {
        return;
      }
      editorEl.dataset.gjsInitialized = 'true';

      const config = drupalSettings.articleCanvasEditor || {};
      const articleId = config.articleId;
      const apiBaseUrl = config.apiBaseUrl;

      if (!articleId || !apiBaseUrl) {
        console.error('Article Canvas Editor: missing configuration');
        return;
      }

      // Wait for GrapesJS to be loaded by the page builder library.
      const waitForGrapesJS = setInterval(function () {
        if (typeof grapesjs !== 'undefined') {
          clearInterval(waitForGrapesJS);
          initEditor(editorEl, config);
        }
      }, 100);
    }
  };

  /**
   * Initialize GrapesJS for article editing.
   */
  function initEditor(editorEl, config) {
    // The GrapesJS instance is created by grapesjs-jaraba-canvas.js
    // if drupalSettings.jarabaCanvas is present. We hook into the
    // existing initialization and override save behavior.

    const canvasConfig = drupalSettings.jarabaCanvas || {};

    // Wait for the editor instance to be available.
    const checkEditor = setInterval(function () {
      if (window.jarabaCanvasEditor) {
        clearInterval(checkEditor);
        setupArticleSave(window.jarabaCanvasEditor, config);
        loadArticleCanvas(window.jarabaCanvasEditor, config);
        hideLoadingSkeleton();
      }
    }, 200);

    // Timeout: if editor doesn't load in 10s, show error.
    setTimeout(function () {
      clearInterval(checkEditor);
      if (!window.jarabaCanvasEditor) {
        showError(Drupal.t('Error al cargar el Canvas Editor. Recarga la pagina.'));
      }
    }, 10000);
  }

  /**
   * Load canvas data from the article API.
   */
  function loadArticleCanvas(editor, config) {
    fetch(config.apiBaseUrl, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.components && data.components.length > 0) {
          editor.setComponents(data.components);
        }
        if (data.styles && data.styles.length > 0) {
          editor.setStyle(data.styles);
        }
        updateStatus(Drupal.t('Canvas cargado'));
      })
      .catch(function (error) {
        console.error('Error loading canvas:', error);
        updateStatus(Drupal.t('Error al cargar canvas'));
      });
  }

  /**
   * Setup save handler for the article canvas.
   */
  function setupArticleSave(editor, config) {
    const saveBtn = document.getElementById('btn-save');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', function () {
      saveArticleCanvas(editor, config);
    });

    // Keyboard shortcut: Ctrl+S / Cmd+S.
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveArticleCanvas(editor, config);
      }
    });

    // Undo/Redo buttons.
    const undoBtn = document.getElementById('btn-undo');
    const redoBtn = document.getElementById('btn-redo');

    if (undoBtn) {
      undoBtn.addEventListener('click', function () {
        editor.UndoManager.undo();
      });
    }
    if (redoBtn) {
      redoBtn.addEventListener('click', function () {
        editor.UndoManager.redo();
      });
    }

    // Update undo/redo button states.
    editor.on('change:changesCount', function () {
      if (undoBtn) undoBtn.disabled = !editor.UndoManager.hasUndo();
      if (redoBtn) redoBtn.disabled = !editor.UndoManager.hasRedo();
    });
  }

  /**
   * Save article canvas data via PATCH API.
   */
  function saveArticleCanvas(editor, config) {
    updateStatus(Drupal.t('Guardando...'));

    const data = {
      components: editor.getComponents(),
      styles: editor.getStyle(),
      html: editor.getHtml(),
      css: editor.getCss(),
    };

    getCsrfToken().then(function (token) {
      return fetch(config.apiBaseUrl, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': token,
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
      });
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (result.success) {
          updateStatus(Drupal.t('Guardado correctamente'));
        } else {
          updateStatus(Drupal.t('Error al guardar: @error', { '@error': result.error || '' }));
        }
      })
      .catch(function (error) {
        console.error('Save error:', error);
        updateStatus(Drupal.t('Error de red al guardar'));
        // Reset CSRF token cache on failure (may have expired).
        csrfTokenPromise = null;
      });
  }

  /**
   * Update the status indicator text.
   */
  function updateStatus(message) {
    var statusEl = document.getElementById('canvas-status');
    if (statusEl) {
      statusEl.querySelector('.article-canvas-editor__status-text').textContent = message;
    }
  }

  /**
   * Hide the loading skeleton.
   */
  function hideLoadingSkeleton() {
    var loadingEl = document.getElementById('canvas-loading');
    if (loadingEl) {
      loadingEl.style.display = 'none';
    }
  }

  /**
   * Show an error message in the canvas area.
   */
  function showError(message) {
    var loadingEl = document.getElementById('canvas-loading');
    if (loadingEl) {
      // INNERHTML-XSS-001: Use Drupal.checkPlain for user-visible text.
      loadingEl.innerHTML = '<p style="color: #ef4444;">' + Drupal.checkPlain(message) + '</p>';
    }
  }

})(Drupal, drupalSettings);
```

### 7.7 Modales para Acciones CRUD del Editor

El Canvas Editor de articulos NO usa modales para acciones CRUD del canvas. Las acciones son:

| Accion | Implementacion |
|--------|---------------|
| Guardar | Boton "Guardar" en toolbar → API PATCH (sin modal) |
| Vista previa | Link al articulo publico (target="_blank") |
| Undo/Redo | Botones en toolbar → `editor.UndoManager` |
| Viewport | Dropdown en toolbar (no modal) |

Los metadatos del articulo (categoria, SEO, status) se editan desde el formulario `ContentArticleForm`, no desde el editor canvas. Link "Editar metadatos" en la toolbar lleva a `/content-hub/articles/{id}/edit`.

### 7.8 Integracion con Header, Navegacion y Footer

El editor canvas vive dentro del layout `page--content-hub.html.twig` que incluye:

- **Header:** Parcial `_header.html.twig` del tema (sticky, con nav del tenant)
- **Footer:** Parcial `_footer.html.twig` del tema

No se usan header/footer variant selectors (a diferencia del Page Builder). El articulo publicado usa el header/footer estandar del tema.

### 7.9 Mobile-first Responsive Layout

El editor Canvas es **desktop-only** por diseno UX (GrapesJS requiere espacio de pantalla):

| Viewport | Comportamiento |
|----------|---------------|
| < 1024px | Overlay con mensaje "El editor visual requiere pantalla de escritorio" |
| >= 1024px | Editor 3 columnas: bloques (260px) + canvas (1fr) + propiedades (280px) |
| >= 1440px | Espaciado mas holgado en toolbar y panels |

La **vista publica** del articulo con canvas SI es responsive (hereda el responsive del tema y los breakpoints del contenido creado en el canvas).

### 7.10 Body Classes via hook_preprocess_html()

Modificacion en `jaraba_content_hub_preprocess_html()`:

```php
// Body class para la ruta del Canvas Editor.
if ($route_name === 'jaraba_content_hub.article_canvas_editor') {
  $variables['attributes']['class'][] = 'page-article-canvas-editor';
  $variables['attributes']['class'][] = 'page--full-viewport';
}
```

---

## 8. Internacionalizacion (i18n)

### 8.1 Textos Twig con {% trans %}

Todos los textos visibles en `article-canvas-editor.html.twig` usan `{% trans %}`:

```twig
{% trans %}Volver{% endtrans %}
{% trans %}Guardar{% endtrans %}
{% trans %}Preview{% endtrans %}
{% trans %}Bloques{% endtrans %}
{% trans %}Buscar bloque...{% endtrans %}
{% trans %}Estilos{% endtrans %}
{% trans %}Props{% endtrans %}
{% trans %}Capas{% endtrans %}
{% trans %}Cargando Canvas Editor...{% endtrans %}
{% trans %}Listo{% endtrans %}
{% trans %}Cambiar viewport{% endtrans %}
{% trans %}Cambiar resolucion{% endtrans %}
{% trans %}Historial{% endtrans %}
{% trans %}Deshacer{% endtrans %}
{% trans %}Rehacer{% endtrans %}
{% trans %}Vista previa{% endtrans %}
{% trans %}Guardar cambios{% endtrans %}
{% trans %}Escritorio XL{% endtrans %}
{% trans %}Escritorio{% endtrans %}
{% trans %}Tablet{% endtrans %}
{% trans %}Movil{% endtrans %}
```

### 8.2 Textos PHP con $this->t()

```php
$this->t('Canvas Editor: @title', ['@title' => $content_article->getTitle()])
$this->t('Canvas Data')
$this->t('HTML Renderizado')
$this->t('Modo de Edicion')
$this->t('Editor Clasico (textarea)')
$this->t('Canvas Editor Visual')
```

### 8.3 Textos JS con Drupal.t()

```javascript
Drupal.t('Canvas cargado')
Drupal.t('Error al cargar canvas')
Drupal.t('Guardando...')
Drupal.t('Guardado correctamente')
Drupal.t('Error al guardar: @error', { '@error': result.error || '' })
Drupal.t('Error de red al guardar')
Drupal.t('Error al cargar el Canvas Editor. Recarga la pagina.')
```

---

## 9. Seguridad

### 9.1 CSRF en Rutas API (CSRF-API-001)

La ruta PATCH `/api/v1/articles/{content_article}/canvas` incluye:

```yaml
requirements:
  _csrf_request_header_token: 'TRUE'
```

El JS bridge obtiene el token via `fetch(Drupal.url('session/token'))` y lo envia en el header `X-CSRF-Token`. El token se cachea en una Promise (CSRF-JS-CACHE-001).

La ruta GET no requiere CSRF (lectura idempotente).

### 9.2 Sanitizacion HTML/CSS del Canvas

Se aplican los mismos 3 niveles de sanitizacion que el Page Builder:

| Metodo | Vectores eliminados | Donde |
|--------|---------------------|-------|
| `sanitizePageBuilderHtml()` | `<script>`, `on*` handlers, `javascript:` URLs, `<object>`, `<embed>` | `saveCanvas()` — campo html |
| `sanitizeCss()` | `javascript:`, `expression()`, `@import`, `behavior:`, `-moz-binding:` | `saveCanvas()` — campo css |
| `sanitizeHtml()` | Todo lo anterior + `data-gjs-*` attrs + `gjs-*` classes | `saveCanvas()` — campo rendered_html |

Los metodos se duplican en `ArticleCanvasApiController` (son `protected` en `CanvasApiController`, no extraibles sin refactor). Refactor futuro: extraer a `CanvasHtmlSanitizerTrait`.

### 9.3 XSS Prevention en Twig (TWIG-XSS-001)

En la vista publica del articulo:
- `{{ article.canvas_html|safe_html }}` — filtro safe_html de Drupal (autoescaping)
- `{{ article.canvas_css }}` — dentro de `<style>`, CSS ya sanitizado server-side
- Nunca `|raw` para contenido de usuario no sanitizado

En el editor:
- `{{ article.getTitle() }}` — autoescaping de Twig
- `Drupal.checkPlain()` en JS para mensajes de error (INNERHTML-XSS-001)

### 9.4 Tenant Isolation en Access Handler

`ContentArticleAccessControlHandler` existente valida:
- **view:** Articulos publicados son publicos
- **update/delete:** Solo el author o usuarios con `edit any content article`

ContentArticle **no tiene campo `tenant_id`** actualmente (pendiente Fase C). El access control se basa en ownership via el campo `author`.

### 9.5 API Field Whitelist (API-WHITELIST-001)

```php
private const ALLOWED_FIELDS = ['components', 'styles', 'html', 'css'];
```

Solo estos 4 campos del JSON body se procesan en `saveCanvas()`. Cualquier campo adicional enviado por el cliente es ignorado.

---

## 10. Integracion con Ecosistema Existente

### 10.1 WritingAssistantService

El `WritingAssistantService` existente proporciona generacion de contenido IA:
- `generateOutline()` — Generar esquema de articulo
- `expandSection()` — Expandir una seccion
- `optimizeHeadline()` — Optimizar titulos
- `improveSeo()` — Mejorar Answer Capsule y meta tags
- `generateFullArticle()` — Articulo completo

**Integracion con Canvas Editor:** El plugin `grapesjs-jaraba-ai.js` del Page Builder ya proporciona generacion de contenido IA dentro del canvas. Este plugin se reutiliza via la library dependency y funciona con los mismos endpoints `/api/v1/content/ai/*` existentes.

### 10.2 SeoService

`SeoService::analyzeArticleSeo()` proporciona scoring SEO (0-100) evaluando:
- SEO title length (30-60 chars = 20 pts)
- Meta description length (120-160 chars = 20 pts)
- Answer Capsule presence (15 pts)
- Featured image (15 pts)
- Content length (>300 words = 15 pts, >1000 = 15 pts)

El plugin `grapesjs-jaraba-seo.js` reutilizado puede invocar este servicio para auditar SEO en tiempo real.

### 10.3 RecommendationService

Sin cambios. Los articulos publicados se indexan en Qdrant automaticamente via `hook_content_article_insert/update()` existentes, independientemente de si usan canvas o legacy mode.

### 10.4 ArticleService

Sin cambios. `getPublishedArticles()` funciona igual para articulos canvas y legacy.

### 10.5 CategoryService

Sin cambios. Las categorias aplican igual a ambos modos.

### 10.6 Sistema de Iconos (jaraba_icon)

`jaraba_icon()` se usa extensivamente en el template del editor para toolbar, botones y paneles. Sigue el patron ICON-CONVENTION-001.

### 10.7 Newsletter y Engagement

Sin cambios. El hook `hook_content_article_update()` existente trigger la creacion de newsletter al publicar, independientemente del layout_mode.

---

## 11. Configuracion Administrable desde UI de Drupal

### 11.1 Theme Settings para Variables Inyectables

Los Design Tokens del tenant (color-primary, color-secondary, font-family) se extraen automaticamente via `TenantContextService` y se inyectan en `drupalSettings.jarabaCanvas.designTokens`. No requieren configuracion adicional.

### 11.2 Selector de layout_mode en Formulario de Articulo

El campo `layout_mode` se muestra como `options_select` en la seccion "Content" del formulario `ContentArticleForm`. Valores:
- **Editor Clasico (textarea)** — default para articulos existentes
- **Canvas Editor Visual** — abre enlace al editor visual

Modificacion en `ContentArticleForm::getSectionDefinitions()`:

```php
'content' => [
  'label' => $this->t('Content'),
  'icon' => ['category' => 'ui', 'name' => 'edit'],
  'description' => $this->t('Title, body, and featured image.'),
  'fields' => ['title', 'slug', 'excerpt', 'body', 'answer_capsule', 'featured_image', 'layout_mode'],
],
```

### 11.3 Feature Gate por Plan de Suscripcion

El acceso al Canvas Editor se controla por plan:

| Plan | Canvas Editor |
|------|--------------|
| Free | No — solo textarea |
| Starter | No — solo textarea |
| Professional | Si |
| Enterprise | Si |

Implementacion en `ArticleCanvasEditorController::editor()`:

```php
// Feature gate: verificar plan del tenant.
if (\Drupal::hasService('ecosistema_jaraba_core.plan_resolver')) {
  $planResolver = \Drupal::service('ecosistema_jaraba_core.plan_resolver');
  $tenant = $this->tenantContext->getCurrentTenant();
  if ($tenant) {
    $plan = $tenant->getSubscriptionPlan();
    $tier = $plan ? $planResolver->normalize($plan->get('machine_name')->value) : 'free';
    if (!$planResolver->hasFeature('jaraba_content_hub', $tier, 'canvas_editor')) {
      throw new AccessDeniedHttpException('Canvas Editor requires Professional or Enterprise plan.');
    }
  }
}
```

---

## 12. Navegacion en Estructura Drupal

### 12.1 /admin/structure: Field UI + Views Integration

- `/admin/structure/content-hub/article` — Article Settings (Field UI base route)
- Los 3 nuevos campos (canvas_data, rendered_html, layout_mode) aparecen automaticamente en "Manage form display" y "Manage display"
- `canvas_data` y `rendered_html` deben ocultarse del display publico (gestionados via API)

### 12.2 /admin/content: Listado de Articulos

Sin cambios. El listado de articulos en `/admin/content/articles` y `/content-hub/articles` muestra todos los articulos independientemente de su layout_mode.

### 12.3 Rutas Frontend

| Ruta | Controlador | Descripcion |
|------|------------|-------------|
| `/content-hub/articles/{id}/canvas` | `ArticleCanvasEditorController::editor()` | Canvas Editor visual |
| `/api/v1/articles/{id}/canvas` (GET) | `ArticleCanvasApiController::getCanvas()` | Obtener datos canvas |
| `/api/v1/articles/{id}/canvas` (PATCH) | `ArticleCanvasApiController::saveCanvas()` | Guardar datos canvas |

---

## 13. Fases de Implementacion

### Fase 1: Schema + Entity Fields

1. Anadir 3 campos a `ContentArticle::baseFieldDefinitions()` (canvas_data, rendered_html, layout_mode)
2. Crear `jaraba_content_hub.install` con update hook 10001
3. Ejecutar `lando drush updb -y`
4. Verificar: los 3 campos existen en la tabla `content_article_field_data`

### Fase 2: Controllers (editor + API)

1. Crear `ArticleCanvasEditorController.php`
2. Crear `ArticleCanvasApiController.php`
3. Anadir 3 rutas a `jaraba_content_hub.routing.yml`
4. Anadir permiso `use article canvas editor` a `jaraba_content_hub.permissions.yml`
5. Registrar theme hook `article_canvas_editor` en `jaraba_content_hub_theme()`
6. Ejecutar `lando drush cr`
7. Verificar: ruta `/content-hub/articles/{id}/canvas` responde

### Fase 3: Templates + SCSS (editor UI + public view)

1. Crear `article-canvas-editor.html.twig`
2. Modificar `content-article--full.html.twig` (+bifurcacion canvas/legacy)
3. Anadir estilos canvas editor a `_content-hub.scss`
4. Compilar SCSS: `npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed`
5. Anadir body classes en `jaraba_content_hub_preprocess_html()`
6. Verificar: editor carga con layout 3 columnas

### Fase 4: JS Integration (library + drupalSettings bridge)

1. Crear `article-canvas-editor.js`
2. Anadir library `article-canvas-editor` a `jaraba_content_hub.libraries.yml`
3. Verificar: GrapesJS inicializa en el canvas
4. Verificar: Guardar (Ctrl+S) envia PATCH a la API
5. Verificar: Cargar articulo con canvas_data muestra contenido

### Fase 5: AI + SEO Integration

1. Verificar que `grapesjs-jaraba-ai.js` funciona con endpoints existentes del Content Hub
2. Verificar que `grapesjs-jaraba-seo.js` invoca `SeoService` correctamente
3. Integrar feature gate con `PlanResolverService`
4. Anadir layout_mode a `ContentArticleForm::getSectionDefinitions()`

### Fase 6: Testing + QA

1. Crear `ArticleCanvasApiSanitizationTest.php` (unit)
2. Crear `ContentArticleCanvasFieldsTest.php` (kernel)
3. Ejecutar todos los tests: `./vendor/bin/phpunit`
4. Verificar checklist manual (seccion 15.1)

---

## 14. Estrategia de Testing

### 14.1 Unit Tests

**Archivo:** `tests/src/Unit/Controller/ArticleCanvasApiSanitizationTest.php`

Tests identicos a `CanvasApiSanitizationTest.php` del Page Builder, aplicados a los metodos de `ArticleCanvasApiController`:

| Test | Verifica |
|------|----------|
| `testSanitizeRemovesScriptTags` | `<script>alert(1)</script>` eliminado |
| `testSanitizeRemovesEventHandlers` | `onclick`, `onerror`, `onload` eliminados |
| `testSanitizeRemovesJavascriptUrls` | `javascript:void(0)` en href/src eliminado |
| `testSanitizeRemovesObjectEmbed` | `<object>`, `<embed>` eliminados |
| `testSanitizePreservesLegitimateHtml` | `<div>`, `<p>`, `<img>`, `<svg>` preservados |
| `testSanitizeCssRemovesExpression` | `expression()` eliminado |
| `testSanitizeCssRemovesImport` | `@import` eliminado |
| `testSanitizeCssRemovesBehavior` | `behavior:` eliminado |
| `testSanitizeHtmlRemovesGjsAttributes` | `data-gjs-*` eliminados |
| `testSanitizeHtmlRemovesGjsClasses` | `gjs-*` classes eliminadas, user classes preservadas |
| `testAllowedFieldsWhitelist` | Solo ALLOWED_FIELDS procesados |

### 14.2 Kernel Tests

**Archivo:** `tests/src/Kernel/Entity/ContentArticleCanvasFieldsTest.php`

| Test | Verifica |
|------|----------|
| `testCanvasDataFieldExists` | Campo canvas_data en schema |
| `testRenderedHtmlFieldExists` | Campo rendered_html en schema |
| `testLayoutModeFieldExists` | Campo layout_mode con allowed_values |
| `testLayoutModeDefaultValue` | Default es 'legacy' |
| `testCanvasDataDefaultValue` | Default es '{}' |
| `testSaveAndLoadCanvasData` | Persistencia JSON completa |
| `testLayoutModeAllowedValues` | Solo 'legacy' y 'canvas' aceptados |

### 14.3 Functional Tests

Tests manuales (ver seccion 15.1) cubriendo:
- Editor load con GrapesJS funcional
- Save/load cycle via API
- Vista publica con canvas renderizado
- Retrocompatibilidad con articulos legacy
- Feature gate por plan

---

## 15. Verificacion y Despliegue

### 15.1 Checklist Manual

- [ ] Crear articulo nuevo, seleccionar `layout_mode = 'canvas'`
- [ ] Abrir Canvas Editor en `/content-hub/articles/{id}/canvas`
- [ ] Verificar: toolbar con viewport presets, undo/redo, save, preview
- [ ] Arrastrar bloques al canvas, editar estilos
- [ ] Guardar (Ctrl+S) → verificar JSON en campo canvas_data
- [ ] Ver articulo publico `/blog/{id}` → verificar HTML renderizado con estilos
- [ ] Crear articulo legacy (textarea) → verificar que sigue funcionando
- [ ] Verificar permisos: solo usuarios con `use article canvas editor` ven enlace al editor
- [ ] Verificar responsive: editor funciona en desktop (>= 1024px)
- [ ] Verificar responsive: overlay de advertencia en mobile/tablet (< 1024px)
- [ ] Verificar i18n: todos los textos traducibles
- [ ] Verificar CSRF: header X-CSRF-Token presente en llamadas PATCH
- [ ] `lando drush cr` + verificar sin errores PHP
- [ ] Tests unitarios pasan: `./vendor/bin/phpunit --testsuite Unit`
- [ ] Tests kernel pasan: `./vendor/bin/phpunit --testsuite Kernel`

### 15.2 Comandos de Verificacion

```bash
# 1. Ejecutar update hooks
lando drush updb -y

# 2. Rebuild cache
lando drush cr

# 3. Verificar que los campos existen
lando drush php-eval "
  \$fields = \Drupal\Core\Field\BaseFieldDefinition::class;
  \$entity_type = \Drupal::entityTypeManager()->getDefinition('content_article');
  \$field_definitions = \Drupal\jaraba_content_hub\Entity\ContentArticle::baseFieldDefinitions(\$entity_type);
  echo 'canvas_data: ' . (isset(\$field_definitions['canvas_data']) ? 'OK' : 'MISSING') . PHP_EOL;
  echo 'rendered_html: ' . (isset(\$field_definitions['rendered_html']) ? 'OK' : 'MISSING') . PHP_EOL;
  echo 'layout_mode: ' . (isset(\$field_definitions['layout_mode']) ? 'OK' : 'MISSING') . PHP_EOL;
"

# 4. Verificar que la ruta existe
lando drush router:debug | grep article_canvas

# 5. Compilar SCSS
cd web/themes/custom/ecosistema_jaraba_theme
npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed

# 6. Tests
cd /home/PED/JarabaImpactPlatformSaaS
./vendor/bin/phpunit --testsuite Unit --filter ArticleCanvas
./vendor/bin/phpunit --testsuite Kernel --filter ContentArticleCanvas

# 7. PHP syntax check
find web/modules/custom/jaraba_content_hub/src/Controller/ArticleCanvas* -name "*.php" | xargs php -l
```

### 15.3 Rollback

Si es necesario revertir:

```bash
# 1. Revertir campos (eliminar de baseFieldDefinitions y crear update hook de rollback)
# 2. Eliminar archivos creados
rm web/modules/custom/jaraba_content_hub/src/Controller/ArticleCanvasEditorController.php
rm web/modules/custom/jaraba_content_hub/src/Controller/ArticleCanvasApiController.php
rm web/modules/custom/jaraba_content_hub/templates/article-canvas-editor.html.twig
rm web/modules/custom/jaraba_content_hub/js/article-canvas-editor.js

# 3. Revertir cambios en archivos modificados (git checkout)
# 4. Rebuild
lando drush updb -y && lando drush cr
```

**Nota:** Los articulos que ya usaban `layout_mode = 'canvas'` perderian su contenido visual. Se recomienda exportar canvas_data antes de revertir.

---

## 16. Troubleshooting

| Problema | Causa | Solucion |
|----------|-------|----------|
| GrapesJS no carga | Library dependency no resuelta | Verificar que `jaraba_page_builder` esta instalado y activo |
| "Access denied" en editor | Falta permiso `use article canvas editor` | Asignar permiso al rol del usuario |
| CSRF error en save | Token expirado o ausente | Verificar que `_csrf_request_header_token: 'TRUE'` esta en la ruta PATCH |
| Canvas vacio al cargar | canvas_data es '{}' | Normal para articulos nuevos — el canvas inicia vacio |
| Estilos no se aplican en vista publica | canvas_css vacio | Verificar que saveCanvas() almacena CSS en canvas_data |
| Update hook falla | Campos ya existen (migracion parcial) | Ejecutar `lando drush entity:updates` como alternativa |
| Editor muy lento | Demasiados bloques o CSS pesado | Verificar que los bloques usan thumbnails optimizados |
| `layout_mode` no aparece en formulario | Campo no registrado en seccion | Verificar `getSectionDefinitions()` incluye 'layout_mode' en fields |
| 404 en `/content-hub/articles/{id}/canvas` | Cache de rutas desactualizada | `lando drush cr` |
| Error PHP: "Class not found" | Namespace incorrecto o autoload no actualizado | `composer dump-autoload && lando drush cr` |

---

## 17. Referencias Cruzadas

| Referencia | Ubicacion |
|------------|-----------|
| Directrices del Proyecto v77.0.0 | `docs/00_DIRECTRICES_PROYECTO.md` |
| Indice General v102.0.0 | `docs/00_INDICE_GENERAL.md` |
| Flujo de Trabajo Claude v32.0.0 | `docs/00_FLUJO_TRABAJO_CLAUDE.md` |
| Canvas Editor v3 Arquitectura | `docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md` |
| AI Content Hub v2 | Doc 128 |
| Blog Clase Mundial Plan | `docs/implementacion/2026-02-26_Blog_Clase_Mundial_Plan_Implementacion.md` |
| Plan Elevacion IA Nivel 5 | `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md` |
| Secure Messaging (formato referencia) | `docs/implementacion/20260220c-178_Plan_Implementacion_Secure_Messaging.md` |
| PremiumEntityFormBase | `ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php` |
| PlanResolverService | `ecosistema_jaraba_core/src/Service/PlanResolverService.php` |
| PageContent Entity (canvas_data pattern) | `jaraba_page_builder/src/Entity/PageContent.php` |
| CanvasEditorController (patron) | `jaraba_page_builder/src/Controller/CanvasEditorController.php` |
| CanvasApiController (patron) | `jaraba_page_builder/src/Controller/CanvasApiController.php` |
| PageContentViewBuilder (patron) | `jaraba_page_builder/src/PageContentViewBuilder.php` |
| GrapesJS Engine JS | `jaraba_page_builder/js/grapesjs-jaraba-canvas.js` |
| ContentArticle Entity | `jaraba_content_hub/src/Entity/ContentArticle.php` |
| ContentArticleForm | `jaraba_content_hub/src/Form/ContentArticleForm.php` |
| BlogController | `jaraba_content_hub/src/Controller/BlogController.php` |
| WritingAssistantService | `jaraba_content_hub/src/Service/WritingAssistantService.php` |
| SeoService | `jaraba_content_hub/src/Service/SeoService.php` |
| Blog SCSS | `ecosistema_jaraba_theme/scss/_content-hub.scss` |
| Page template zero-region | `ecosistema_jaraba_theme/templates/page--content-hub.html.twig` |

---

## 18. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-02-26 | Claude Opus 4.6 | Creacion inicial del documento de implementacion |
