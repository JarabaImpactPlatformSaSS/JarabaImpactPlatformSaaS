# Plan de Implementacion v2: Site Builder + Navegacion Global + Blog Nativo + SEO/GEO/IA

> **Especificaciones Tecnicas**: Docs 176, 177, 178, 179 (Serie 20260127)
> **Fecha**: 2026-02-12
> **Version**: 2.0.0 (Sustituye a v1.0.0 incorporando auditoria de conformidad)
> **Autor**: Equipo Tecnico JarabaImpactPlatformSaaS
> **Estado**: Plan detallado para aprobacion

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Auditoria de Conformidad: Fase 1 ya Implementada](#2-auditoria-de-conformidad-fase-1-ya-implementada)
   - [2.1 Resultados de la Auditoria](#21-resultados-de-la-auditoria)
   - [2.2 Hallazgos Criticos](#22-hallazgos-criticos)
   - [2.3 Acciones Correctivas Prioritarias](#23-acciones-correctivas-prioritarias)
3. [Arquitectura de Theming y Design Tokens](#3-arquitectura-de-theming-y-design-tokens)
   - [3.1 Jerarquia de 5 Capas](#31-jerarquia-de-5-capas)
   - [3.2 SSOT: Variables SCSS e Inyectables](#32-ssot-variables-scss-e-inyectables)
   - [3.3 Reconciliacion Theme Settings vs Entidades de Navegacion](#33-reconciliacion-theme-settings-vs-entidades-de-navegacion)
   - [3.4 Patron de Compilacion SCSS por Modulo](#34-patron-de-compilacion-scss-por-modulo)
4. [Tabla de Correspondencia: Especificaciones vs Directrices](#4-tabla-de-correspondencia-especificaciones-vs-directrices)
5. [Fase 1A: Acciones Correctivas Doc 177 (Navegacion)](#5-fase-1a-acciones-correctivas-doc-177)
   - [5.1 Compilacion SCSS](#51-compilacion-scss)
   - [5.2 Registro de Libreria en libraries.yml](#52-registro-de-libreria)
   - [5.3 Body Classes en hook_preprocess_html](#53-body-classes)
   - [5.4 Template Suggestions](#54-template-suggestions)
   - [5.5 JavaScript de Comportamientos](#55-javascript-de-comportamientos)
   - [5.6 Verificacion i18n en Templates Twig](#56-verificacion-i18n)
6. [Fase 2: Cierre de Gaps Site Structure Manager (Doc 176)](#6-fase-2-cierre-de-gaps-doc-176)
   - [6.1 Gaps Identificados](#61-gaps-identificados)
   - [6.2 Implementacion Detallada](#62-implementacion-detallada)
7. [Fase 3: Blog System Nativo (Doc 178)](#7-fase-3-blog-system-nativo-doc-178)
   - [7.1 Arquitectura del Modulo jaraba_blog](#71-arquitectura)
   - [7.2 Entidades](#72-entidades)
   - [7.3 Servicios](#73-servicios)
   - [7.4 API REST](#74-api-rest)
   - [7.5 Templates y Frontend](#75-templates-frontend)
   - [7.6 SCSS y Design Tokens](#76-scss-design-tokens)
8. [Fase 4: SEO/GEO + Integracion IA (Doc 179)](#8-fase-4-seo-geo-ia)
   - [8.1 Servicios de SEO Avanzado](#81-servicios-seo)
   - [8.2 Schema.org JSON-LD](#82-schema-org)
   - [8.3 Hreflang y Geo-targeting](#83-hreflang)
   - [8.4 Asistente IA para SEO](#84-asistente-ia)
9. [Arquitectura de Entidades Completa](#9-arquitectura-de-entidades)
10. [Arquitectura de Servicios Completa](#10-arquitectura-de-servicios)
11. [Catalogo Completo de API REST](#11-catalogo-api-rest)
12. [Templates Twig y Parciales](#12-templates-twig)
13. [Checklist de Cumplimiento de Directrices](#13-checklist-directrices)
14. [Plan de Testing](#14-plan-de-testing)
15. [Estimacion de Esfuerzo y Cronograma](#15-estimacion)
16. [Dependencias y Riesgos](#16-dependencias-riesgos)
17. [Criterios de Aceptacion](#17-criterios-aceptacion)

---

## 1. Resumen Ejecutivo

### 1.1 Alcance

Este plan cubre la implementacion completa de cuatro especificaciones tecnicas interrelacionadas que conforman el **sistema de construccion y gestion de sitios web** del SaaS Jaraba Impact Platform:

| Doc | Especificacion | Modulo Principal | Horas Spec | Implementado | Restante |
|-----|---------------|------------------|------------|-------------|---------|
| **176** | Site Structure Manager v1 | `jaraba_site_builder` | 40-50h | ~60% | ~16-20h |
| **177** | Global Navigation System v1 | `jaraba_site_builder` (extension) | 50-60h | ~70% (Fase 1 completada) | ~15-18h |
| **178** | Blog System Nativo v1 | `jaraba_blog` (nuevo) | 60-80h | ~30% (modulo distinto) | ~50-60h |
| **179** | SEO/GEO + IA Integration v1 | `jaraba_site_builder` + `jaraba_blog` | 50-60h | ~20% | ~40-48h |
| **Total** | | | **200-250h** | | **~121-146h** |

### 1.2 Principio Rector

> **Todo configurable desde la UI de Drupal, sin tocar codigo.**
> Los tenants configuran su sitio completo (estructura, navegacion, blog, SEO) a traves de interfaces limpias, modales slide-panel y APIs REST, sin acceder nunca al tema de administracion de Drupal.

### 1.3 Actualizacion respecto a v1.0.0

La version 2.0 de este plan incorpora:

1. **Auditoria de conformidad** completa de la Fase 1 (Doc 177) contra TODAS las directrices del Proyecto.
2. **Reconciliacion arquitectonica** entre las entidades de navegacion (`SiteHeaderConfig`, `SiteFooterConfig`) y la configuracion existente del tema (`ecosistema_jaraba_theme.settings`).
3. **Acciones correctivas prioritarias** (Fase 1A) para corregir los gaps de conformidad detectados antes de continuar con nuevas fases.
4. **Plan de theming detallado** basado en la revision exhaustiva de `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`.

---

## 2. Auditoria de Conformidad: Fase 1 ya Implementada

### 2.1 Resultados de la Auditoria

La Fase 1 (Doc 177 - Sistema de Navegacion Global) fue implementada creando:

- **4 Content Entities**: `SiteMenu`, `SiteMenuItem`, `SiteHeaderConfig`, `SiteFooterConfig`
- **12 Handlers**: 4 ListBuilders, 4 AccessControlHandlers, 4 Forms
- **2 Servicios**: `MenuService`, `NavigationRenderService`
- **1 Controller**: `NavigationApiController` (17 endpoints)
- **6 Templates Twig**: header, footer, mobile-menu, mega-menu, topbar, breadcrumbs
- **7 SCSS Partials**: _navigation, _header, _footer, _mega-menu, _mobile-menu, _topbar, _breadcrumbs
- **17 Rutas API** en routing.yml
- **1 Update Hook** en install (update_10001)

#### Tabla de Conformidad

| # | Directriz | Estado | Detalle |
|---|-----------|--------|---------|
| D-01 | Content Entities con `fieldable = TRUE` | CONFORME | Las 4 entidades tienen `fieldable = TRUE` |
| D-02 | Field UI (`field_ui_base_route`) | CONFORME | Las 4 entidades definen `field_ui_base_route` |
| D-03 | Views integration (`views_data` handler) | CONFORME | Todas usan `EntityViewsData` |
| D-04 | Route Provider (`AdminHtmlRouteProvider`) | CONFORME | Todas tienen `route_provider.html` |
| D-05 | Handlers completos (list, access, form, delete) | CONFORME | 12 handlers implementados |
| D-06 | Navegacion en `/admin/structure` | CONFORME | `links.menu.yml` con 4 entradas parent `system.admin_structure` |
| D-07 | SCSS con `var(--ej-*)` y fallbacks inline | CONFORME | Todos los SCSS usan CSS custom properties |
| D-08 | Mobile-first (`@media min-width`) | CONFORME | Breakpoints ascendentes en todos los SCSS |
| D-09 | BEM naming | CONFORME | `.jaraba-header__*`, `.jaraba-footer__*`, etc. |
| D-10 | Package.json con Dart Sass | CONFORME | `package.json` existe con `sass ^1.77.0` |
| D-11 | Multi-tenant (`tenant_id` en entidades) | CONFORME | Todas las entidades y queries filtran por tenant |
| D-12 | Servicios con DI en `services.yml` | CONFORME | 2 servicios registrados correctamente |
| D-13 | API: errores genericos al frontend | CONFORME | `errorResponse()` logea detalles, devuelve mensaje generico |
| D-14 | API: restricciones regex en parametros | CONFORME | `id: '\d+'` en todas las rutas con parametros |
| D-15 | i18n en PHP (`$this->t()`) | CONFORME | Todos los mensajes de error usan `$this->t()` |
| D-16 | API: requiere autenticacion | CONFORME | `_user_is_logged_in: 'TRUE'` o `_permission` en todas las rutas |
| D-17 | `hook_theme()` para parciales | CONFORME | 6 entradas en `hook_theme()` para navegacion |
| D-18 | **SCSS compilado a CSS** | **NO CONFORME** | SCSS creado pero NO compilado |
| D-19 | **Libreria registrada en `libraries.yml`** | **NO CONFORME** | Falta libreria `navigation` en libraries.yml |
| D-20 | **Body classes via `hook_preprocess_html()`** | **NO CONFORME** | Faltan clases para rutas de navegacion en el tema |
| D-21 | **Template suggestions en el tema** | **NO CONFORME** | Falta `page__navigation` suggestion |
| D-22 | **JavaScript de comportamientos** | **NO CONFORME** | Falta JS para sticky, autohide, mobile-menu, topbar |
| D-23 | **i18n en Twig (`{% trans %}`)** | **PARCIAL** | Requiere verificacion en los 6 templates |
| D-24 | **Dart Sass moderno (`@use`, no `@import`)** | **PARCIAL** | main.scss usa `@use`, pero parciales individuales NO importan variables via `@use` (aceptable porque solo usan `var()` CSS) |
| D-25 | **Integracion con theme settings existentes** | **PENDIENTE** | El tema tiene 70+ settings de header/footer que necesitan coexistir con las entidades |

### 2.2 Hallazgos Criticos

#### HC-01: SCSS No Compilado
Los 7 archivos SCSS de navegacion fueron creados y registrados en `main.scss` via `@use`, pero el comando de compilacion no se ejecuto. El fichero `css/site-builder.css` no contiene los estilos de navegacion.

**Impacto**: Los componentes de navegacion no tendran estilos cuando se rendericen.
**Solucion**: Ejecutar `npx sass scss/main.scss css/site-builder.css --style=compressed` dentro del contenedor Docker.

#### HC-02: Libreria de Navegacion No Registrada
El fichero `libraries.yml` no tiene una entrada para los assets de navegacion (CSS + JS futuro). Las paginas que rendericen los parciales de navegacion no cargaran los estilos automaticamente.

**Impacto**: Drupal no adjuntara los CSS/JS de navegacion a las paginas.
**Solucion**: Crear entrada `navigation` en `jaraba_site_builder.libraries.yml`.

#### HC-03: Reconciliacion Theme Settings vs Entidades
El tema `ecosistema_jaraba_theme` ya tiene configuracion de header y footer:
- **Header**: Visual picker (classic/centered/hero/split/minimal), color_header_bg, color_header_text, navigation_items (textarea Texto|URL), header_cta_text, header_cta_url.
- **Footer**: Visual picker (minimal/standard/mega/split), color_footer_bg, color_footer_text, footer_nav_col1_*, footer_social_*, footer_copyright.

Las nuevas entidades `SiteHeaderConfig` y `SiteFooterConfig` crean un sistema paralelo per-tenant. La reconciliacion es:

| Aspecto | Theme Settings (global) | Entidades (per-tenant) | Estrategia |
|---------|------------------------|----------------------|------------|
| Layout de header | `header_layout` | `SiteHeaderConfig.header_type` | **Entidad tiene prioridad** si existe para el tenant; fallback a theme settings |
| Colores de header | `color_header_bg/text` | `SiteHeaderConfig.bg_color/text_color` | Entidad tiene prioridad per-tenant |
| Items de navegacion | `navigation_items` (textarea) | `SiteMenu` + `SiteMenuItem` (entidades) | **Entidades tienen prioridad** (mas potentes) |
| CTA del header | `header_cta_text/url` | `SiteHeaderConfig.cta_text/url` | Entidad tiene prioridad |
| Layout de footer | `footer_layout` | `SiteFooterConfig.footer_type` | Entidad tiene prioridad |
| Columnas del footer | `footer_nav_col1_*` | `SiteFooterConfig.columns_config` (JSON) | Entidad tiene prioridad |
| Social links | `footer_social_*` | Via `SiteConfig` existente | Se mantienen en `SiteConfig` |
| Copyright | `footer_copyright` | `SiteFooterConfig.copyright_text` | Entidad tiene prioridad |

**Estrategia de convivencia**: El `NavigationRenderService` ya implementa el patron correcto: busca la entidad por tenant_id, si no existe devuelve configuracion por defecto. Los parciales de tema (_header.html.twig, _footer.html.twig existentes en el tema) siguen usando theme_settings para el frontend global (landing, homepage). Los nuevos parciales del modulo site_builder se usan en las paginas de los tenants.

### 2.3 Acciones Correctivas Prioritarias

Las siguientes acciones deben ejecutarse **antes** de continuar con nuevas fases:

| # | Accion | Prioridad | Esfuerzo |
|---|--------|-----------|----------|
| AC-01 | Compilar SCSS de navegacion | P0 | 5 min |
| AC-02 | Registrar libreria `navigation` en libraries.yml | P0 | 10 min |
| AC-03 | Crear JS de comportamientos (sticky, autohide, mobile-menu, topbar) | P0 | 2h |
| AC-04 | Agregar body classes en `hook_preprocess_html()` | P1 | 15 min |
| AC-05 | Agregar template suggestion `page__navigation` | P1 | 10 min |
| AC-06 | Verificar y completar `{% trans %}` en templates Twig | P1 | 30 min |
| AC-07 | Verificar que drush entity-updates funciona para las 4 entidades nuevas | P0 | 10 min |

---

## 3. Arquitectura de Theming y Design Tokens

### 3.1 Jerarquia de 5 Capas

Segun `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`:

```
CAPA 1: SCSS Tokens (Build-time fallbacks)
   Archivo: ecosistema_jaraba_core/scss/_variables.scss
   ↓
CAPA 2: CSS Custom Properties (:root injection)
   Archivo: ecosistema_jaraba_core/scss/_injectable.scss
   ↓
CAPA 3: Component Tokens (Scoped SCSS por modulo)
   Archivo: jaraba_site_builder/scss/_header.scss, etc.
   ↓
CAPA 4: Tenant Override (hook_preprocess_html)
   Archivo: ecosistema_jaraba_theme.theme
   ↓
CAPA 5: Vertical Presets (Config Entity)
   StylePresetService via DesignTokenConfig
```

### 3.2 SSOT: Variables SCSS e Inyectables

**Fuente de verdad unica**: `ecosistema_jaraba_core/scss/`

| Archivo | Responsabilidad |
|---------|----------------|
| `_variables.scss` | Fallbacks SCSS: `$ej-color-primary-fallback: #2E7D32`, escalas tipograficas, spacing, shadows, breakpoints, z-index |
| `_injectable.scss` | CSS Custom Properties en `:root`: `--ej-color-primary`, `--ej-text-primary`, `--ej-shadow-*`, etc. |
| `_mixins.scss` | Utilidades: `css-var($property, $var-name, $fallback)` |

**Variables CSS disponibles para navegacion**:

```scss
// Colores (inyectados por hook_preprocess_html desde theme_settings):
--ej-color-primary        // Color primario (personalizable por tenant)
--ej-color-secondary      // Color secundario
--ej-color-corporate      // #233D63 (azul corporativo Jaraba)

// Header/Footer (inyectados desde theme_settings):
--ej-header-bg            // Fondo del header
--ej-header-text          // Texto del header
--ej-footer-bg            // Fondo del footer
--ej-footer-text          // Texto del footer

// Textos:
--ej-text-primary         // #212121
--ej-text-secondary       // #757575
--ej-text-muted           // #9E9E9E

// Fondos:
--ej-bg-surface           // Fondo de superficies
--ej-bg-card              // Fondo de tarjetas

// Sombras:
--ej-shadow-sm/md/lg      // Sombras escalonadas

// Spacing:
--ej-spacing-xs/sm/md/lg/xl/2xl  // NO inyectados como CSS vars, solo SCSS

// Tipografia:
--ej-font-headings        // Familia de fuentes para titulos
--ej-font-body            // Familia de fuentes para cuerpo
--ej-font-size-base       // Tamano base (inyectado)

// Bordes:
--ej-border-color         // Color de bordes
--ej-border-radius        // Radio de bordes base
```

### 3.3 Reconciliacion Theme Settings vs Entidades de Navegacion

**Arquitectura de dos niveles**:

1. **Nivel global (theme_settings)**: Los parciales existentes del tema (`_header.html.twig`, `_footer.html.twig` en `templates/partials/`) usan variables de `theme_settings`. Se usan en: homepage, landing pages de verticales, paginas de autenticacion.

2. **Nivel per-tenant (entidades)**: Los nuevos parciales del modulo (`jaraba-header.html.twig`, `jaraba-footer.html.twig` en `templates/partials/`) usan datos de las entidades `SiteHeaderConfig` y `SiteFooterConfig`. Se usan en: paginas del tenant creadas con Page Builder, blog del tenant, frontend del site builder.

**Flujo de datos para el header de un tenant**:

```
1. NavigationRenderService.getHeaderConfig($tenantId)
   ↓
2. Busca SiteHeaderConfig WHERE tenant_id = $tenantId
   ↓
3. SI existe → Serializa entidad (header_type, logo, menu, colores, etc.)
   SI NO existe → Devuelve defaults (header_type: 'standard', is_sticky: true, etc.)
   ↓
4. Controller o hook_preprocess_page() pasa datos al template
   ↓
5. jaraba-header.html.twig renderiza con los datos
   ↓
6. Los colores de la entidad se inyectan como CSS vars inline
   (--header-bg, --header-text, --header-height-*)
```

### 3.4 Patron de Compilacion SCSS por Modulo

**package.json obligatorio** (ya existe):

```json
{
    "name": "jaraba-site-builder",
    "version": "1.0.0",
    "scripts": {
        "build": "sass scss/main.scss css/site-builder.css --style=compressed",
        "watch": "sass scss/main.scss css/site-builder.css --watch"
    },
    "devDependencies": {
        "sass": "^1.77.0"
    }
}
```

**Compilacion dentro del contenedor Docker**:

```bash
lando ssh -c "cd /app/web/modules/custom/jaraba_site_builder && npx sass scss/main.scss css/site-builder.css --style=compressed"
lando drush cr
```

**Regla Dart Sass**: Cada parcial SCSS es aislado con `@use`. En nuestro caso, como los parciales solo usan `var(--ej-*)` con fallbacks literales y no necesitan variables SCSS, NO necesitan `@use 'variables'`. Esto es correcto y conforme con la directriz.

---

## 4. Tabla de Correspondencia: Especificaciones vs Directrices

### 4.1 Directrices Transversales

| ID | Directriz | Referencia | Aplicacion en Specs 176-179 |
|----|-----------|------------|----------------------------|
| **DIR-01** | Textos de UI siempre traducibles | `00_DIRECTRICES_PROYECTO.md` | PHP: `$this->t()` / `t()`. Twig: `{% trans %}`. JS: `Drupal.t()`. Base en espanol. |
| **DIR-02** | Modelo SCSS con variables inyectables | `2026-02-05_arquitectura_theming_saas_master.md` | Usar `var(--ej-*, fallback)` en SCSS. NUNCA definir `$variables` propias en modulos satelite. |
| **DIR-03** | Dart Sass moderno | Workflow `scss-estilos.md` | `@use` en lugar de `@import`. `color.adjust()` en lugar de `darken()`/`lighten()`. |
| **DIR-04** | Templates Twig limpios | Workflow `frontend-page-pattern.md` | Sin regiones ni bloques de Drupal. Layout full-width. `{% include %}` para parciales. |
| **DIR-05** | Parciales Twig reutilizables | Workflow `frontend-page-pattern.md` | Header, footer, breadcrumbs como parciales. Verificar si ya existe antes de crear uno nuevo. |
| **DIR-06** | Theme settings configurables desde UI | `ecosistema_jaraba_theme.theme` | Colores, layouts, tipografia configurables sin codigo. Parciales usan variables de theme_settings. |
| **DIR-07** | SaaS con frontend limpio | `00_DIRECTRICES_PROYECTO.md` | Sin `page.content` ni bloques heredados. Paginas especiales por ruta con `page--*.html.twig`. |
| **DIR-08** | Mobile-first | Workflow `frontend-page-pattern.md` | SCSS con `@media (min-width:)`. Touch targets 44px min. Grid 1col→2→3. |
| **DIR-09** | CRUD en slide-panel modales | Workflow `slide-panel-modales.md` | Todas las acciones de crear/editar/ver abren modal. `data-slide-panel`, `data-slide-panel-url`. |
| **DIR-10** | Body classes via `hook_preprocess_html()` | Workflow `frontend-page-pattern.md` | NUNCA `attributes.addClass()` en templates. Siempre en PHP en el .theme. |
| **DIR-11** | Content Entities con Field UI + Views | Workflow `drupal-custom-modules.md` | `fieldable = TRUE`, `views_data`, `field_ui_base_route`, handlers completos. |
| **DIR-12** | Navegacion `/admin/structure` + `/admin/content` | Workflow `drupal-custom-modules.md` | Config entities en `/admin/structure`. Content entities en `/admin/content`. |
| **DIR-13** | Multi-tenant: `tenant_id` en todo | `00_DIRECTRICES_PROYECTO.md` S3 | Todas las entidades tienen `tenant_id` (ref → group). Queries filtran SIEMPRE. |
| **DIR-14** | Iconos: `jaraba_icon()` con duotone SVG | Workflow `scss-estilos.md` | Usar `{{ jaraba_icon('categoria', 'nombre', {variant: 'duotone', color: 'azul-corporativo'}) }}`. |
| **DIR-15** | API Security: auth obligatoria, rate limiting | `00_DIRECTRICES_PROYECTO.md` S4.5 | `_user_is_logged_in` o `_permission`. Error generico al frontend. Log detallado. |
| **DIR-16** | Paleta de colores oficial Jaraba | Workflow `scss-estilos.md` | 7 colores de marca + 6 UI extendidos. Fallbacks con `$ej-color-*-fallback`. |
| **DIR-17** | AI: `@ai.provider`, NUNCA HTTP directo | `00_DIRECTRICES_PROYECTO.md` | Para Doc 179 (SEO IA): usar `@ai.provider` con failover, circuit breaker. |
| **DIR-18** | package.json obligatorio por modulo con SCSS | `2026-02-05_arquitectura_theming_saas_master.md` | `jaraba_site_builder/package.json` existe. `jaraba_blog` necesitara el suyo. |
| **DIR-19** | Ejecutar comandos dentro de Docker | `00_DIRECTRICES_PROYECTO.md` | `lando ssh -c "..."` para compilacion SCSS, drush, etc. |

### 4.2 Correspondencia por Especificacion

| Spec | Seccion | Directriz(es) Aplicable(s) | Implementacion Requerida |
|------|---------|---------------------------|-------------------------|
| Doc 176 S4.1 | Arbol de paginas | DIR-04, DIR-07, DIR-08, DIR-09 | Frontend limpio con drag & drop, slide-panel para CRUD |
| Doc 176 S4.3 | Redirects 301/302 | DIR-11, DIR-12, DIR-13 | Content entity con Field UI, en `/admin/structure` y `/admin/content` |
| Doc 177 S3.1 | Header multi-variante | DIR-02, DIR-05, DIR-06, DIR-10 | 5 variantes, colores inyectables, body classes para layout |
| Doc 177 S3.2 | Menu jerarquico | DIR-01, DIR-11, DIR-13 | SiteMenu + SiteMenuItem entities, textos traducibles |
| Doc 177 S3.3 | Mega menu | DIR-04, DIR-05, DIR-08 | Parcial Twig, mobile-first grid, accesible |
| Doc 177 S3.4 | Footer configurable | DIR-02, DIR-05, DIR-06 | 5 variantes, columnas JSON, newsletter, social |
| Doc 177 S3.5 | Breadcrumbs Schema.org | DIR-01, DIR-05 | Parcial con JSON-LD, textos traducibles |
| Doc 177 S3.6 | Mobile off-canvas | DIR-08, DIR-09 | Slide-panel desde derecha, accordion submenus |
| Doc 177 S3.7 | Topbar | DIR-02, DIR-05 | Banner configurable con colores inyectables |
| Doc 178 S3.1 | Blog posts | DIR-01, DIR-11, DIR-13 | Content entity `BlogPost` con Field UI |
| Doc 178 S3.2 | Categorias y tags | DIR-11, DIR-12, DIR-13 | Taxonomias o content entities |
| Doc 178 S3.3 | Autores | DIR-11, DIR-13 | Perfil de autor vinculado a usuario |
| Doc 178 S3.4 | RSS Feed | DIR-13 | Feed XML per-tenant |
| Doc 178 S3.5 | Listado/detalle | DIR-04, DIR-07, DIR-08, DIR-14 | Paginas frontend limpias, cards con iconos |
| Doc 179 S3.1 | Meta tags SEO | DIR-01, DIR-13 | Per-page, per-tenant, traducibles |
| Doc 179 S3.2 | Schema.org | DIR-01 | JSON-LD en head, tipos: Article, BreadcrumbList, Organization |
| Doc 179 S3.3 | Hreflang | DIR-01 | Multi-idioma per-tenant |
| Doc 179 S3.4 | Asistente IA SEO | DIR-17 | Via @ai.provider, sugerencias de meta tags |
| Doc 179 S3.5 | Sitemap XML | DIR-13 | Per-tenant con prioridades |

---

## 5. Fase 1A: Acciones Correctivas Doc 177 (Navegacion)

> **Duracion estimada**: 4-5 horas
> **Prioridad**: P0 - Ejecutar ANTES de continuar con Fase 2

### 5.1 Compilacion SCSS

**Accion**: Compilar el SCSS del modulo site_builder que ahora incluye los 7 parciales de navegacion.

**Comando**:
```bash
lando ssh -c "cd /app/web/modules/custom/jaraba_site_builder && npx sass scss/main.scss css/site-builder.css --style=compressed"
lando drush cr
```

**Verificacion**: El fichero `css/site-builder.css` debe contener las reglas `.jaraba-header`, `.jaraba-footer`, `.jaraba-nav`, `.jaraba-mega-menu`, `.jaraba-mobile-menu`, `.jaraba-topbar`, `.jaraba-breadcrumbs`.

### 5.2 Registro de Libreria en libraries.yml

**Accion**: Anadir entrada `navigation` en `jaraba_site_builder.libraries.yml`.

```yaml
# Fase 1 Doc 177: Componentes de navegacion global
navigation:
  version: 1.0
  js:
    js/navigation.js: {}
  css:
    theme:
      css/site-builder.css: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
```

**Razon**: El CSS de navegacion se compila dentro de `site-builder.css` (un unico punto de salida). El JS `navigation.js` (a crear en AC-03) contiene los comportamientos de sticky, autohide, mobile-menu y topbar.

### 5.3 Body Classes en hook_preprocess_html()

**Accion**: Agregar clases de body para las rutas de navegacion en `ecosistema_jaraba_theme.theme` dentro de la funcion existente `ecosistema_jaraba_theme_preprocess_html()`.

**Insertar despues del bloque de `$site_builder_frontend_routes`** (linea ~953):

```php
// =========================================================================
// NAVIGATION MANAGEMENT - Rutas de gestion de navegacion (Doc 177)
// =========================================================================
$navigation_management_routes = [
  'jaraba_site_builder.api.header_get',
  'jaraba_site_builder.api.header_update',
  'jaraba_site_builder.api.footer_get',
  'jaraba_site_builder.api.footer_update',
];
// Las rutas de navegacion frontend comparten body class con site-builder
// ya que se gestionan desde el mismo dashboard.
```

**Nota**: Las rutas de navegacion son APIs REST que devuelven JSON, no necesitan body classes propias. Los parciales de navegacion se renderizan dentro de las paginas existentes del site-builder que ya tienen `page-site-builder` como body class.

### 5.4 Template Suggestions

**Accion**: Verificar que `hook_theme_suggestions_page_alter()` ya cubre las rutas del site-builder. Segun la lectura del tema (lineas 1890-1989), ya existe la suggestion `page__site_builder` para las rutas de site-builder. Los parciales de navegacion se renderizan DENTRO de estas paginas, no como paginas independientes, por lo que no se necesitan suggestions adicionales.

### 5.5 JavaScript de Comportamientos

**Accion**: Crear `js/navigation.js` con los comportamientos de:

1. **Header sticky**: Detectar scroll y anadir clase `is-scrolled` al header.
2. **Header autohide**: Detectar direccion de scroll y togglear `is-hidden`.
3. **Mobile menu**: Abrir/cerrar panel off-canvas con overlay.
4. **Mega menu keyboard**: Soporte de navegacion por teclado.
5. **Topbar close**: Boton de cerrar el topbar.
6. **Breadcrumbs**: Sin JS necesario.

**Estructura del JS**:

```javascript
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.jarabaStickyHeader = {
    attach: function (context) {
      once('jaraba-sticky-header', '.jaraba-header--sticky', context)
        .forEach(function (header) {
          // Logica de sticky con IntersectionObserver o scroll listener
        });
    }
  };

  Drupal.behaviors.jarabaAutoHideHeader = {
    attach: function (context) {
      once('jaraba-autohide-header', '.jaraba-header--autohide', context)
        .forEach(function (header) {
          // Logica de autohide: lastScrollY, threshold
        });
    }
  };

  Drupal.behaviors.jarabaMobileMenu = {
    attach: function (context) {
      once('jaraba-mobile-menu', '.jaraba-mobile-menu', context)
        .forEach(function (menu) {
          // Toggle open/close, overlay click, ESC key
          // Accordion submenus: toggle aria-expanded, max-height
        });
    }
  };

  Drupal.behaviors.jarabaTopbar = {
    attach: function (context) {
      once('jaraba-topbar', '.jaraba-topbar', context)
        .forEach(function (topbar) {
          // Close button: classList.add('is-hidden'), localStorage
        });
    }
  };

})(Drupal, once);
```

### 5.6 Verificacion i18n en Templates Twig

**Accion**: Revisar los 6 templates de navegacion y asegurar que TODOS los textos visibles usan `{% trans %}`:

| Template | Textos a verificar |
|----------|-------------------|
| `jaraba-header.html.twig` | "Skip to main content", aria-labels, "Menu" |
| `jaraba-footer.html.twig` | "All rights reserved", "Subscribe", "Privacy", "Terms", "Cookies" |
| `jaraba-mobile-menu.html.twig` | "Close menu", aria-labels |
| `jaraba-mega-menu.html.twig` | Headings de columnas |
| `jaraba-topbar.html.twig` | "Close" aria-label |
| `jaraba-breadcrumbs.html.twig` | "Home", "Breadcrumb" aria-label |

---

## 6. Fase 2: Cierre de Gaps Site Structure Manager (Doc 176)

> **Duracion estimada**: 16-20 horas
> **Prerequisito**: Fase 1A completada

### 6.1 Gaps Identificados

El Doc 176 tiene ~60% implementado. Los gaps restantes son:

| # | Gap | Descripcion | Esfuerzo |
|---|-----|-------------|----------|
| G-01 | Frontend page para el arbol | Crear `page--site-builder.html.twig` limpio con parciales | 3h |
| G-02 | Drag & Drop con persistencia | El arbol visual funciona pero necesita mejoras en la persistencia de reorder | 4h |
| G-03 | URL auto-generation | Generar path_alias automatico basado en jerarquia del arbol | 3h |
| G-04 | Bulk operations en arbol | Publicar/despublicar multiples paginas | 2h |
| G-05 | Integracion redirects en arbol | Cuando se mueve una pagina, crear redirect automatico | 2h |
| G-06 | Dashboard KPIs premium | Completar los KPIs glassmorphism del dashboard | 3h |
| G-07 | Slide-panel para CRUD de paginas en arbol | Crear/editar pagina desde el arbol via slide-panel | 3h |

### 6.2 Implementacion Detallada

#### G-01: Frontend Page para el Arbol

**Archivo**: `web/themes/custom/ecosistema_jaraba_theme/templates/page--site-builder.html.twig`

Ya existe una template suggestion `page__site_builder` en el tema. El archivo debe seguir el patron:

```twig
{# page--site-builder.html.twig - Full-width frontend sin Drupal regions #}
{{ attach_library('ecosistema_jaraba_theme/global-styling') }}
{{ attach_library('jaraba_site_builder/site-tree-manager') }}
{{ attach_library('jaraba_site_builder/navigation') }}

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
    {% trans %}Skip to main content{% endtrans %}
  </a>

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({})
  } %}

  <main id="main-content" class="site-builder-main">
    <div class="site-builder-wrapper">
      {{ page.content }}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    theme_settings: theme_settings|default({})
  } %}

  {% include '@ecosistema_jaraba_theme/partials/_slide-panel.html.twig' %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

#### G-07: Slide-Panel para CRUD

Todos los formularios de creacion/edicion de paginas, redirects, menus y configuracion se abren en slide-panel. El patron:

```html
<button class="btn"
        data-slide-panel="large"
        data-slide-panel-url="/admin/content/site-menus/add"
        data-slide-panel-title="{{ 'Add Menu'|t }}">
  {{ 'Add Menu'|t }}
</button>
```

Requiere que los controladores detecten `$request->isXmlHttpRequest()` y devuelvan solo el HTML del formulario sin wrapper de pagina.

---

## 7. Fase 3: Blog System Nativo (Doc 178)

> **Duracion estimada**: 50-60 horas
> **Prerequisito**: Fase 2 completada

### 7.1 Arquitectura del Modulo jaraba_blog

**Modulo nuevo**: `web/modules/custom/jaraba_blog/`

**Estructura de ficheros**:

```
jaraba_blog/
├── jaraba_blog.info.yml
├── jaraba_blog.module
├── jaraba_blog.install
├── jaraba_blog.routing.yml
├── jaraba_blog.services.yml
├── jaraba_blog.libraries.yml
├── jaraba_blog.links.menu.yml
├── jaraba_blog.links.task.yml
├── jaraba_blog.links.action.yml
├── jaraba_blog.permissions.yml
├── package.json
├── scss/
│   ├── main.scss
│   ├── _blog-listing.scss
│   ├── _blog-detail.scss
│   ├── _blog-sidebar.scss
│   ├── _blog-author.scss
│   ├── _blog-categories.scss
│   └── _blog-rss.scss
├── css/
│   └── blog.css
├── js/
│   └── blog.js
├── src/
│   ├── Entity/
│   │   ├── BlogPost.php
│   │   ├── BlogCategory.php
│   │   └── BlogAuthor.php
│   ├── Form/
│   │   ├── BlogPostForm.php
│   │   ├── BlogCategoryForm.php
│   │   ├── BlogAuthorForm.php
│   │   ├── BlogPostSettingsForm.php
│   │   ├── BlogCategorySettingsForm.php
│   │   └── BlogAuthorSettingsForm.php
│   ├── Service/
│   │   ├── BlogService.php
│   │   ├── BlogRssService.php
│   │   └── BlogSeoService.php
│   ├── Controller/
│   │   ├── BlogFrontendController.php
│   │   ├── BlogApiController.php
│   │   └── BlogRssController.php
│   ├── BlogPostListBuilder.php
│   ├── BlogCategoryListBuilder.php
│   ├── BlogAuthorListBuilder.php
│   ├── BlogPostAccessControlHandler.php
│   ├── BlogCategoryAccessControlHandler.php
│   └── BlogAuthorAccessControlHandler.php
└── templates/
    ├── frontend/
    │   ├── blog-listing.html.twig
    │   └── blog-detail.html.twig
    └── partials/
        ├── blog-post-card.html.twig
        ├── blog-sidebar.html.twig
        ├── blog-author-bio.html.twig
        ├── blog-categories-nav.html.twig
        ├── blog-pagination.html.twig
        ├── blog-share-buttons.html.twig
        └── blog-related-posts.html.twig
```

### 7.2 Entidades

#### BlogPost (Content Entity)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` | integer (auto) | ID unico |
| `uuid` | uuid | UUID |
| `tenant_id` | entity_reference → group | Tenant |
| `title` | string(255) | Titulo del post |
| `slug` | string(255) | URL amigable |
| `excerpt` | string_long | Extracto/resumen |
| `body` | text_long | Contenido completo (HTML) |
| `featured_image` | entity_reference → file | Imagen destacada |
| `featured_image_alt` | string(255) | Alt accesible |
| `category_id` | entity_reference → blog_category | Categoria principal |
| `tags` | string_long | Tags separados por coma |
| `author_id` | entity_reference → blog_author | Autor |
| `status` | list_string | draft/published/scheduled/archived |
| `published_at` | datetime | Fecha de publicacion |
| `scheduled_at` | datetime | Fecha de publicacion programada |
| `is_featured` | boolean | Destacado en portada |
| `reading_time` | integer | Tiempo de lectura (minutos) |
| `meta_title` | string(70) | SEO: meta title |
| `meta_description` | string(160) | SEO: meta description |
| `og_image` | entity_reference → file | Open Graph image |
| `schema_type` | list_string | Schema.org: Article/BlogPosting/NewsArticle |
| `views_count` | integer | Contador de visitas |
| `created` | created | Fecha de creacion |
| `changed` | changed | Fecha de modificacion |

**Handlers**: list_builder, views_data, form (add/edit), delete, access, route_provider.
**Links**: collection en `/admin/content/blog-posts`, settings en `/admin/structure/blog`.
**Field UI**: `field_ui_base_route = "entity.blog_post.settings"`.

#### BlogCategory (Content Entity)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` | integer (auto) | ID unico |
| `uuid` | uuid | UUID |
| `tenant_id` | entity_reference → group | Tenant |
| `name` | string(100) | Nombre |
| `slug` | string(100) | URL amigable |
| `description` | string_long | Descripcion |
| `parent_id` | entity_reference → self | Padre (jerarquia) |
| `icon` | string(50) | Icono |
| `color` | string(7) | Color hex |
| `weight` | integer | Orden |
| `is_active` | boolean | Activa/inactiva |
| `posts_count` | integer | Cache de conteo de posts |
| `meta_title` | string(70) | SEO |
| `meta_description` | string(160) | SEO |
| `created` | created | Creacion |

#### BlogAuthor (Content Entity)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` | integer (auto) | ID unico |
| `uuid` | uuid | UUID |
| `tenant_id` | entity_reference → group | Tenant |
| `user_id` | entity_reference → user | Usuario Drupal vinculado |
| `display_name` | string(100) | Nombre para mostrar |
| `bio` | string_long | Biografia |
| `avatar` | entity_reference → file | Foto de perfil |
| `social_twitter` | string(255) | URL Twitter |
| `social_linkedin` | string(255) | URL LinkedIn |
| `social_website` | string(255) | URL sitio web |
| `is_active` | boolean | Activo/inactivo |
| `posts_count` | integer | Cache de conteo de posts |
| `created` | created | Creacion |

### 7.3 Servicios

| Servicio | Responsabilidad |
|----------|----------------|
| `jaraba_blog.blog` | CRUD de posts, listados paginados, busqueda, posts relacionados, conteo de vistas |
| `jaraba_blog.rss` | Generacion de feed RSS/Atom per-tenant, cache con invalidacion |
| `jaraba_blog.seo` | Meta tags por post, Schema.org JSON-LD, Open Graph, Twitter Cards |

### 7.4 API REST

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | `/api/v1/blog/posts` | Listar posts (paginado, filtros) |
| POST | `/api/v1/blog/posts` | Crear post |
| GET | `/api/v1/blog/posts/{id}` | Detalle de post |
| PATCH | `/api/v1/blog/posts/{id}` | Actualizar post |
| DELETE | `/api/v1/blog/posts/{id}` | Eliminar post |
| POST | `/api/v1/blog/posts/{id}/publish` | Publicar post |
| POST | `/api/v1/blog/posts/{id}/schedule` | Programar publicacion |
| GET | `/api/v1/blog/categories` | Listar categorias |
| POST | `/api/v1/blog/categories` | Crear categoria |
| PATCH | `/api/v1/blog/categories/{id}` | Actualizar categoria |
| DELETE | `/api/v1/blog/categories/{id}` | Eliminar categoria |
| GET | `/api/v1/blog/authors` | Listar autores |
| POST | `/api/v1/blog/authors` | Crear autor |
| PATCH | `/api/v1/blog/authors/{id}` | Actualizar autor |
| GET | `/api/v1/blog/posts/{id}/related` | Posts relacionados |
| POST | `/api/v1/blog/posts/{id}/view` | Incrementar contador de vistas |
| GET | `/api/v1/blog/stats` | Estadisticas del blog |

### 7.5 Templates y Frontend

**Paginas frontend del blog** (rutas publicas per-tenant):

| Ruta | Template | Descripcion |
|------|----------|-------------|
| `/blog` | `blog-listing.html.twig` | Listado con sidebar, paginacion |
| `/blog/{slug}` | `blog-detail.html.twig` | Detalle con author bio, share, related |
| `/blog/categoria/{slug}` | `blog-listing.html.twig` (filtrado) | Listado por categoria |
| `/blog/autor/{slug}` | `blog-listing.html.twig` (filtrado) | Listado por autor |
| `/blog/feed.xml` | RSS XML | Feed RSS |

**Parciales**:
- `blog-post-card.html.twig`: Card de post para listados (imagen, titulo, extracto, autor, fecha, categoria badge)
- `blog-sidebar.html.twig`: Sidebar con categorias, posts populares, newsletter
- `blog-author-bio.html.twig`: Bio del autor con avatar y social links
- `blog-categories-nav.html.twig`: Navegacion de categorias con conteo
- `blog-pagination.html.twig`: Paginacion numerada mobile-friendly
- `blog-share-buttons.html.twig`: Botones de compartir (Twitter, LinkedIn, Facebook, copiar enlace)
- `blog-related-posts.html.twig`: Grid de posts relacionados

### 7.6 SCSS y Design Tokens

**Nuevo package.json** para `jaraba_blog`:

```json
{
    "name": "jaraba-blog",
    "version": "1.0.0",
    "scripts": {
        "build": "sass scss/main.scss css/blog.css --style=compressed",
        "watch": "sass scss/main.scss css/blog.css --watch"
    },
    "devDependencies": {
        "sass": "^1.77.0"
    }
}
```

**Parciales SCSS**: Todos usan `var(--ej-*)` con fallbacks inline. Mobile-first. BEM naming (`.blog-listing__*`, `.blog-detail__*`, `.blog-card__*`).

---

## 8. Fase 4: SEO/GEO + Integracion IA (Doc 179)

> **Duracion estimada**: 40-48 horas
> **Prerequisito**: Fase 3 completada

### 8.1 Servicios de SEO Avanzado

**Ubicacion**: Dentro de `jaraba_site_builder` (extension).

| Servicio | Responsabilidad |
|----------|----------------|
| `jaraba_site_builder.seo_manager` | CRUD de configuracion SEO per-page, meta tags, canonical, robots |
| `jaraba_site_builder.schema_generator` | Generacion de JSON-LD Schema.org per-page (Article, BreadcrumbList, Organization, LocalBusiness, FAQPage) |
| `jaraba_site_builder.hreflang_manager` | Gestion de hreflang tags per-page, deteccion de idiomas disponibles |
| `jaraba_site_builder.geo_targeting` | Geo meta tags, local business schema, localizacion per-tenant |
| `jaraba_site_builder.seo_ai_assistant` | Asistente IA para sugerencias de meta tags, generacion automatica, analisis de keywords (via @ai.provider) |

### 8.2 Schema.org JSON-LD

**Tipos soportados**:

| Tipo Schema | Donde se aplica | Campos |
|------------|----------------|--------|
| `Organization` | Pagina principal del tenant | name, url, logo, contactPoint, sameAs |
| `WebSite` | Todas las paginas | name, url, potentialAction (SearchAction) |
| `BreadcrumbList` | Todas las paginas con breadcrumbs | itemListElement con position, name, item |
| `Article` / `BlogPosting` | Blog posts | headline, author, datePublished, image, publisher |
| `LocalBusiness` | Paginas de negocio local | name, address, geo, openingHours, telephone |
| `FAQPage` | Paginas de FAQ | mainEntity con Question/Answer |
| `Product` | Paginas de producto (ComercioConecta) | name, image, description, offers |

### 8.3 Hreflang y Geo-targeting

**Entidad nueva**: `SeoPageConfig` (Content Entity)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` | integer | ID |
| `tenant_id` | entity_reference → group | Tenant |
| `page_id` | entity_reference → page_content | Pagina asociada |
| `meta_title` | string(70) | Titulo SEO (override) |
| `meta_description` | string(160) | Descripcion SEO |
| `canonical_url` | string(500) | URL canonica |
| `robots` | list_string | index/noindex, follow/nofollow |
| `og_title` | string(100) | Open Graph title |
| `og_description` | string(200) | Open Graph description |
| `og_image` | entity_reference → file | Open Graph image |
| `twitter_card` | list_string | summary/summary_large_image |
| `schema_type` | list_string | Organization/Article/LocalBusiness/FAQPage/Product |
| `schema_custom_json` | string_long | JSON-LD personalizado |
| `hreflang_config` | string_long | JSON: [{lang: 'es', url: '...'}, {lang: 'en', url: '...'}] |
| `geo_region` | string(10) | Codigo de region ISO 3166-2 |
| `geo_position` | string(50) | Latitud;Longitud |
| `keywords` | string_long | Keywords separadas por coma |
| `last_audit_score` | integer | Score del ultimo audit SEO (0-100) |
| `last_audit_date` | datetime | Fecha del ultimo audit |

### 8.4 Asistente IA para SEO

**Integracion con @ai.provider** (DIRECTRIZ DIR-17):

```php
class SeoAiAssistantService {

  public function __construct(
    protected AiProviderInterface $aiProvider,
    // ...
  ) {}

  /**
   * Genera sugerencias de meta tags basadas en el contenido de la pagina.
   */
  public function suggestMetaTags(string $pageContent, string $pageTitle): array {
    $prompt = "Analiza este contenido y sugiere meta title (max 60 chars), " .
              "meta description (max 155 chars) y 5 keywords principales:\n\n" .
              "Titulo: {$pageTitle}\n" .
              "Contenido: " . mb_substr(strip_tags($pageContent), 0, 2000);

    $response = $this->aiProvider->chat([
      ['role' => 'user', 'content' => $prompt],
    ]);

    return $this->parseAiSuggestions($response);
  }
}
```

---

## 9. Arquitectura de Entidades Completa

### Entidades existentes (Doc 176):

| Entidad | Modulo | Tabla | Campos | Estado |
|---------|--------|-------|--------|--------|
| `SiteConfig` | site_builder | `site_config` | 30+ | Implementada |
| `SitePageTree` | site_builder | `site_page_tree` | 18 | Implementada |
| `SiteRedirect` | site_builder | `site_redirect` | 11 | Implementada |
| `SiteUrlHistory` | site_builder | `site_url_history` | 9 | Implementada |

### Entidades Doc 177 (implementadas en Fase 1):

| Entidad | Tabla | Campos | Field UI | Views | Estado |
|---------|-------|--------|----------|-------|--------|
| `SiteMenu` | `site_menu` | 8 | Si | Si | Implementada |
| `SiteMenuItem` | `site_menu_item` | 17 | Si | Si | Implementada |
| `SiteHeaderConfig` | `site_header_config` | 33 | Si | Si | Implementada |
| `SiteFooterConfig` | `site_footer_config` | 25 | Si | Si | Implementada |

### Entidades Doc 178 (a implementar):

| Entidad | Tabla | Campos | Field UI | Views | Estado |
|---------|-------|--------|----------|-------|--------|
| `BlogPost` | `blog_post` | 24 | Si | Si | Pendiente |
| `BlogCategory` | `blog_category` | 15 | Si | Si | Pendiente |
| `BlogAuthor` | `blog_author` | 13 | Si | Si | Pendiente |

### Entidades Doc 179 (a implementar):

| Entidad | Tabla | Campos | Field UI | Views | Estado |
|---------|-------|--------|----------|-------|--------|
| `SeoPageConfig` | `seo_page_config` | 19 | Si | Si | Pendiente |

### Total: 12 entidades (8 implementadas + 4 pendientes)

---

## 10. Arquitectura de Servicios Completa

| Servicio | Modulo | Estado |
|----------|--------|--------|
| `jaraba_site_builder.structure` | site_builder | Implementado |
| `jaraba_site_builder.redirect` | site_builder | Implementado |
| `jaraba_site_builder.sitemap` | site_builder | Implementado |
| `jaraba_site_builder.analytics` | site_builder | Implementado |
| `jaraba_site_builder.seo_auditor` | site_builder | Implementado |
| `jaraba_site_builder.header_variant` | site_builder | Implementado |
| `jaraba_site_builder.footer_variant` | site_builder | Implementado |
| `jaraba_site_builder.menu` | site_builder | Implementado |
| `jaraba_site_builder.navigation_render` | site_builder | Implementado |
| `jaraba_site_builder.seo_manager` | site_builder | Pendiente (Doc 179) |
| `jaraba_site_builder.schema_generator` | site_builder | Pendiente (Doc 179) |
| `jaraba_site_builder.hreflang_manager` | site_builder | Pendiente (Doc 179) |
| `jaraba_site_builder.geo_targeting` | site_builder | Pendiente (Doc 179) |
| `jaraba_site_builder.seo_ai_assistant` | site_builder | Pendiente (Doc 179) |
| `jaraba_blog.blog` | blog | Pendiente (Doc 178) |
| `jaraba_blog.rss` | blog | Pendiente (Doc 178) |
| `jaraba_blog.seo` | blog | Pendiente (Doc 178) |

### Total: 17 servicios (9 implementados + 8 pendientes)

---

## 11. Catalogo Completo de API REST

### Doc 176 - Site Structure (implementados):

| Metodo | Ruta | Controller |
|--------|------|-----------|
| GET | `/api/v1/site/tree` | SiteStructureApiController::getTree |
| POST | `/api/v1/site/tree/reorder` | SiteStructureApiController::reorderTree |
| POST | `/api/v1/site/pages` | SiteStructureApiController::addPage |
| GET | `/api/v1/site/pages/available` | SiteStructureApiController::getAvailablePages |
| PATCH | `/api/v1/site/pages/{id}` | SiteStructureApiController::updatePage |
| DELETE | `/api/v1/site/pages/{id}` | SiteStructureApiController::deletePage |
| POST | `/api/v1/site/pages/{id}/move` | SiteStructureApiController::movePage |
| GET | `/api/v1/site/config` | SiteConfigApiController::getConfig |
| PUT | `/api/v1/site/config` | SiteConfigApiController::updateConfig |
| POST | `/api/v1/site/config/logo` | SiteConfigApiController::uploadLogo |
| GET | `/api/v1/site/stats` | SiteStructureApiController::getStats |
| GET | `/api/v1/site/pages/{id}/seo-audit` | SiteStructureApiController::seoAudit |

### Doc 177 - Navigation (implementados):

| Metodo | Ruta | Controller |
|--------|------|-----------|
| GET | `/api/v1/site/header` | NavigationApiController::getHeader |
| PUT | `/api/v1/site/header` | NavigationApiController::updateHeader |
| POST | `/api/v1/site/header/logo` | NavigationApiController::uploadHeaderLogo |
| DELETE | `/api/v1/site/header/logo` | NavigationApiController::deleteHeaderLogo |
| GET | `/api/v1/site/footer` | NavigationApiController::getFooter |
| PUT | `/api/v1/site/footer` | NavigationApiController::updateFooter |
| POST | `/api/v1/site/footer/logo` | NavigationApiController::uploadFooterLogo |
| GET | `/api/v1/site/menus` | NavigationApiController::listMenus |
| POST | `/api/v1/site/menus` | NavigationApiController::createMenu |
| GET | `/api/v1/site/menus/{id}` | NavigationApiController::getMenu |
| DELETE | `/api/v1/site/menus/{id}` | NavigationApiController::deleteMenu |
| GET | `/api/v1/site/menus/{id}/tree` | NavigationApiController::getMenuTree |
| POST | `/api/v1/site/menus/{id}/items` | NavigationApiController::addMenuItem |
| PATCH | `/api/v1/site/menus/{id}/items/{itemId}` | NavigationApiController::updateMenuItem |
| DELETE | `/api/v1/site/menus/{id}/items/{itemId}` | NavigationApiController::deleteMenuItem |
| POST | `/api/v1/site/menus/{id}/reorder` | NavigationApiController::reorderMenuItems |
| GET | `/api/v1/site/breadcrumbs/{pageTreeId}` | NavigationApiController::getBreadcrumbs |

### Doc 178 - Blog (pendientes): 17 endpoints
### Doc 179 - SEO (pendientes): ~10 endpoints

### Total: ~56 endpoints API (29 implementados + ~27 pendientes)

---

## 12. Templates Twig y Parciales

### Parciales de navegacion (Fase 1 - implementados):

| Template | Ubicacion | hook_theme |
|----------|-----------|-----------|
| `jaraba-header.html.twig` | `templates/partials/` | `jaraba_header` |
| `jaraba-footer.html.twig` | `templates/partials/` | `jaraba_footer` |
| `jaraba-mobile-menu.html.twig` | `templates/partials/` | `jaraba_mobile_menu` |
| `jaraba-mega-menu.html.twig` | `templates/partials/` | `jaraba_mega_menu` |
| `jaraba-topbar.html.twig` | `templates/partials/` | `jaraba_topbar` |
| `jaraba-breadcrumbs.html.twig` | `templates/partials/` | `jaraba_breadcrumbs` |

### Parciales de blog (Fase 3 - pendientes): 7 parciales

### Paginas completas de tema:

| Template | Ruta(s) | Estado |
|----------|---------|--------|
| `page--site-builder.html.twig` | `/site-builder/*` | Existente pero necesita revision |
| `page--blog.html.twig` | `/blog/*` | Pendiente (Doc 178) |

---

## 13. Checklist de Cumplimiento de Directrices

### Pre-commit checklist (aplicar a CADA fichero nuevo):

- [ ] **DIR-01**: Todos los textos de UI usan `$this->t()` / `{% trans %}` / `Drupal.t()`
- [ ] **DIR-02**: SCSS usa SOLO `var(--ej-*, fallback)`. NO define `$variables` propias
- [ ] **DIR-03**: SCSS usa `@use` (no `@import`). Color functions: `color.adjust()` (no `darken()`/`lighten()`)
- [ ] **DIR-04**: Templates Twig sin regiones ni bloques de Drupal
- [ ] **DIR-05**: Verificar si ya existe un parcial antes de crear uno nuevo
- [ ] **DIR-06**: Configuracion del tema se consume via `theme_settings` variable en Twig
- [ ] **DIR-07**: Paginas frontend con layout full-width, sin sidebar admin
- [ ] **DIR-08**: SCSS mobile-first (`@media min-width`), touch targets 44px
- [ ] **DIR-09**: CRUD en slide-panel con `data-slide-panel` attributes
- [ ] **DIR-10**: Body classes en `hook_preprocess_html()`, NUNCA en templates
- [ ] **DIR-11**: Content entities: `fieldable=TRUE`, `views_data`, `field_ui_base_route`, handlers completos
- [ ] **DIR-12**: Navegacion correcta: config en `/admin/structure`, content en `/admin/content`
- [ ] **DIR-13**: `tenant_id` en TODA entidad, queries SIEMPRE filtran por tenant
- [ ] **DIR-14**: Iconos con `{{ jaraba_icon() }}` y SVG duotone
- [ ] **DIR-15**: API: autenticacion obligatoria, errores genericos, logs detallados
- [ ] **DIR-16**: Colores de la paleta Jaraba oficial
- [ ] **DIR-17**: IA via `@ai.provider` con failover y circuit breaker
- [ ] **DIR-18**: `package.json` con script `build` de Dart Sass
- [ ] **DIR-19**: Compilacion y comandos dentro de contenedor Docker

---

## 14. Plan de Testing

### Tests unitarios por fase:

| Fase | Tests | Tipo |
|------|-------|------|
| 1A | Compilacion SCSS sin errores | Build |
| 1A | JS navigation.js sin errores de sintaxis | Lint |
| 2 | SiteStructureService: reorder, move, auto-redirect | Unit |
| 3 | BlogService: CRUD posts, paginacion, filtros | Unit |
| 3 | BlogRssService: generacion feed valido | Unit |
| 3 | BlogSeoService: meta tags, Schema.org | Unit |
| 4 | SeoAiAssistantService: parseo sugerencias | Unit |
| 4 | SchemaGeneratorService: JSON-LD valido | Unit |
| 4 | HreflangManagerService: tags correctos | Unit |

### Tests de integracion:

| Test | Que verifica |
|------|-------------|
| API Header CRUD | GET/PUT header config, upload/delete logo |
| API Menu CRUD | Create/read/update/delete menus e items, reorder |
| API Blog CRUD | Create/publish/schedule posts, categorias, autores |
| API SEO CRUD | Configuracion SEO per-page, Schema.org |
| Multi-tenant isolation | Tenant A no ve datos de Tenant B |
| Slide-panel forms | Formularios se abren y guardan correctamente |

### Tests visuales (BackstopJS):

| Pagina | Viewports |
|--------|-----------|
| Blog listing | 375px, 768px, 1440px |
| Blog detail | 375px, 768px, 1440px |
| Header (5 variantes) | 375px, 1440px |
| Footer (5 variantes) | 375px, 1440px |
| Mobile menu | 375px |
| Site builder dashboard | 768px, 1440px |

---

## 15. Estimacion de Esfuerzo y Cronograma

| Fase | Descripcion | Horas | Prerequisito |
|------|-------------|-------|-------------|
| **1A** | Acciones correctivas Doc 177 | 4-5h | Ninguno |
| **2** | Cierre gaps Doc 176 | 16-20h | Fase 1A |
| **3** | Blog System Nativo Doc 178 | 50-60h | Fase 2 |
| **4** | SEO/GEO + IA Doc 179 | 40-48h | Fase 3 |
| **Total** | | **110-133h** | |

### Cronograma sugerido:

```
Semana 1: Fase 1A (1 dia) + Fase 2 inicio (4 dias)
Semana 2: Fase 2 cierre (1 dia) + Fase 3 inicio (4 dias)
Semana 3: Fase 3 continuacion (5 dias)
Semana 4: Fase 3 cierre (2 dias) + Fase 4 inicio (3 dias)
Semana 5: Fase 4 continuacion (5 dias)
Semana 6: Fase 4 cierre (2 dias) + Testing + Documentacion (3 dias)
```

---

## 16. Dependencias y Riesgos

### Dependencias tecnicas:

| Dependencia | Modulo | Estado |
|-------------|--------|--------|
| `ecosistema_jaraba_core` | Servicio TenantContextService | Implementado |
| `ecosistema_jaraba_theme` | Parciales _header, _footer, slide-panel | Implementado |
| `jaraba_page_builder` | Entidad PageContent | Implementado |
| Group module | Multi-tenant via grupos | Implementado |
| `drupal/ai` | Provider IA para Doc 179 | Configurado |

### Riesgos identificados:

| # | Riesgo | Probabilidad | Impacto | Mitigacion |
|---|--------|-------------|---------|------------|
| R-01 | Conflicto theme_settings vs entidades de navegacion | Media | Alto | Estrategia de prioridad per-tenant documentada en 2.2 |
| R-02 | Performance con menus grandes (50+ items) | Baja | Medio | Cache de menu tree con invalidacion en CRUD |
| R-03 | SEO IA: dependencia de proveedor externo | Media | Medio | Circuit breaker + fallback a sugerencias basicas |
| R-04 | Blog con alto volumen de posts | Baja | Medio | Paginacion con cursor, indices en DB |

---

## 17. Criterios de Aceptacion

### Fase 1A (Acciones correctivas):
- [ ] SCSS compila sin errores
- [ ] Navigation library registrada y se carga en paginas del site-builder
- [ ] JS de sticky header funciona al hacer scroll
- [ ] JS de mobile menu abre/cierra con overlay
- [ ] Entity update (drush entity-updates) instala las 4 tablas de navegacion

### Fase 2 (Doc 176):
- [ ] Arbol de paginas con drag & drop funcional
- [ ] CRUD de paginas via slide-panel
- [ ] Auto-redirect al mover paginas
- [ ] Dashboard con KPIs premium

### Fase 3 (Doc 178):
- [ ] CRUD completo de blog posts con editor
- [ ] Listado paginado con filtros por categoria, tag, autor
- [ ] Detalle de post con author bio, share buttons, related posts
- [ ] Feed RSS valido per-tenant
- [ ] Pagina frontend limpia sin Drupal regions

### Fase 4 (Doc 179):
- [ ] Configuracion SEO per-page editable desde slide-panel
- [ ] Schema.org JSON-LD valido para 7 tipos
- [ ] Hreflang tags correctos para multi-idioma
- [ ] Asistente IA genera sugerencias de meta tags
- [ ] Score de audit SEO per-page (0-100)

---

> **Documento generado**: 2026-02-12
> **Proxima revision**: Tras completar Fase 1A
> **Aprobacion requerida**: SI - Antes de iniciar implementacion
