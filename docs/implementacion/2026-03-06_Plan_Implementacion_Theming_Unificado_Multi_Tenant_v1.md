# Plan de Implementacion: Theming Unificado Multi-Tenant — THEMING-UNIFY-001

**Fecha de creacion:** 2026-03-06 10:00
**Ultima actualizacion:** 2026-03-06 10:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion (Theming / Multi-Tenant / Frontend)
**Prioridad:** P0 (Critico — gap entre configuracion y renderizacion)
**Modulos afectados:** jaraba_theming, ecosistema_jaraba_core, jaraba_site_builder, ecosistema_jaraba_theme
**Especificacion referencia:** THEMING-UNIFY-001

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnostico del Estado Actual](#2-diagnostico-del-estado-actual)
   - 2.1 [Cadena de renderizacion actual](#21-cadena-de-renderizacion-actual)
   - 2.2 [Gaps identificados (6 gaps)](#22-gaps-identificados)
   - 2.3 [Dos sistemas paralelos sin integracion](#23-dos-sistemas-paralelos-sin-integracion)
   - 2.4 [Matriz de campos duplicados](#24-matriz-de-campos-duplicados)
   - 2.5 [Servicios muertos y consumidores](#25-servicios-muertos-y-consumidores)
3. [Arquitectura Objetivo](#3-arquitectura-objetivo)
   - 3.1 [Cascada unificada de 5 niveles](#31-cascada-unificada-de-5-niveles)
   - 3.2 [Single Source of Truth por ambito](#32-single-source-of-truth-por-ambito)
   - 3.3 [Pipeline de renderizacion unificado](#33-pipeline-de-renderizacion-unificado)
   - 3.4 [Resolucion de tenant para anonimos](#34-resolucion-de-tenant-para-anonimos)
   - 3.5 [Diagrama de componentes C4](#35-diagrama-de-componentes-c4)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Fases de Implementacion](#5-fases-de-implementacion)
   - 5.1 [Fase 1 — Correccion de gaps criticos (P0)](#51-fase-1--correccion-de-gaps-criticos-p0)
   - 5.2 [Fase 2 — Cascada unificada de layout](#52-fase-2--cascada-unificada-de-layout)
   - 5.3 [Fase 3 — Resolucion por hostname para anonimos](#53-fase-3--resolucion-por-hostname-para-anonimos)
   - 5.4 [Fase 4 — Consolidacion de entidades](#54-fase-4--consolidacion-de-entidades)
   - 5.5 [Fase 5 — Frontend self-service completo](#55-fase-5--frontend-self-service-completo)
   - 5.6 [Fase 6 — Tests y verificacion RUNTIME-VERIFY-001](#56-fase-6--tests-y-verificacion-runtime-verify-001)
6. [Tabla de Correspondencia de Especificaciones Tecnicas](#6-tabla-de-correspondencia-de-especificaciones-tecnicas)
7. [Tabla de Cumplimiento de Directrices](#7-tabla-de-cumplimiento-de-directrices)
8. [Configuracion](#8-configuracion)
   - 8.1 [Parametros configurables desde UI de Drupal](#81-parametros-configurables-desde-ui-de-drupal)
   - 8.2 [Variables CSS inyectables](#82-variables-css-inyectables)
   - 8.3 [SCSS y Dart Sass](#83-scss-y-dart-sass)
9. [Templates Twig y Parciales](#9-templates-twig-y-parciales)
   - 9.1 [Inventario de parciales existentes](#91-inventario-de-parciales-existentes)
   - 9.2 [Variables Twig para parciales](#92-variables-twig-para-parciales)
   - 9.3 [Reglas de Zero Region Pattern](#93-reglas-de-zero-region-pattern)
10. [Verificacion](#10-verificacion)
    - 10.1 [Tests automatizados](#101-tests-automatizados)
    - 10.2 [Verificacion manual](#102-verificacion-manual)
    - 10.3 [Checklist RUNTIME-VERIFY-001](#103-checklist-runtime-verify-001)
    - 10.4 [Checklist IMPLEMENTATION-CHECKLIST-001](#104-checklist-implementation-checklist-001)
11. [Despliegue](#11-despliegue)
12. [Troubleshooting](#12-troubleshooting)
13. [Referencias](#13-referencias)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### Problema

El sistema de tematizacion multi-tenant de Jaraba Impact Platform presenta **6 gaps arquitectonicos criticos** que causan una desconexion total entre "lo que el tenant configura" y "lo que el usuario experimenta":

1. **TenantThemeConfig se guarda pero NUNCA se lee** para CTA, header layout, footer ni navegacion en el pipeline de renderizado.
2. **El campo `header_cta_url` NO EXISTE** en TenantThemeConfig, pero el formulario `/my-settings/design` lo muestra y lo descarta silenciosamente al guardar.
3. **El override de meta-sitio solo funciona en rutas `page_content`**, dejando todas las demas rutas (homepage, login, blog, settings) sin branding del tenant.
4. **Existen dos sistemas paralelos sin integracion**: TenantThemeConfig (legado, 54 campos hardcoded) y DesignTokenConfig + StylePreset (nuevo, JSON flexible, cascada 4 niveles) — pero el sistema nuevo NUNCA se consume en runtime.
5. **TenantContextService resuelve por usuario autenticado, no por hostname**, lo que significa que visitantes anonimos de un meta-sitio no ven los colores/fuentes del tenant.
6. **Los pipelines de CSS tokens y Layout/Content estan completamente separados** y leen de fuentes de datos diferentes.

### Solucion

Este plan implementa una **cascada unificada de 5 niveles** que conecta la configuracion del tenant con el renderizado en TODAS las rutas y para TODOS los usuarios (autenticados y anonimos):

```
Nivel 1: Plataforma (ecosistema_jaraba_theme.settings) — defaults globales
Nivel 2: Vertical (DesignTokenConfig scope=vertical) — paleta por vertical
Nivel 3: Plan (DesignTokenConfig scope=plan) — features por plan de suscripcion
Nivel 4: Tenant (TenantThemeConfig) — personalizacion del tenant
Nivel 5: SiteConfig (meta-sitio) — override por pagina/dominio especifico
```

### Resultado esperado

- Un tenant configura CTA, colores, header y footer en `/my-settings/design` y los cambios se reflejan **inmediatamente** en todas las paginas de su meta-sitio, para todos los visitantes.
- Consistencia visual garantizada entre `plataformadeecosistemas.jaraba-saas.lndo.site`, `pepejaraba.jaraba-saas.lndo.site` y cualquier otro meta-sitio.
- Arquitectura preparada para escalar a N tenants sin cambios de codigo.

---

## 2. Diagnostico del Estado Actual

### 2.1 Cadena de renderizacion actual

```
REQUEST (usuario visita pepejaraba.jaraba-saas.lndo.site/es/blog)
    |
    v
[A] hook_page_attachments() — jaraba_theming.module:29
    |-- ThemeTokenService::generateCss()
    |   |-- resolveTenantId() via TenantContextService (solo usuarios auth)
    |   |-- getActiveConfig(tenant_id) -> TenantThemeConfig entity
    |   `-- generateCssVariables() -> CSS :root { --ej-color-primary: #FF8C42 }
    `-- Inyecta <style id="jaraba-design-tokens"> en <head>
        PROBLEMA: Solo inyecta CSS tokens (colores, fuentes).
                  Solo funciona para usuarios autenticados del tenant.
                  Usuarios anonimos obtienen fallback "platform".

[B] hook_preprocess_html() — jaraba_theming.module:54
    |-- ThemeTokenService::getHeaderVariant() -> body class "header--classic"
    |-- ThemeTokenService::getHeroVariant() -> body class "hero--split"
    `-- ThemeTokenService::getActiveConfig() -> body class "vertical--platform"
        PROBLEMA: Lee de TenantThemeConfig (correcto para body classes).
                  Pero NO inyecta header_cta_*, footer_*, nav_items.

[C] hook_preprocess_html() — ecosistema_jaraba_theme.theme:2225
    |-- ThemeTokenService::generateCss(tenantId)
    `-- Inyecta <style id="jaraba-tenant-tokens"> (DESPUES del anterior)
        PROBLEMA: Duplica inyeccion CSS. Misma fuente que [A].
                  Ambos leen TenantThemeConfig::generateCssVariables().

[D] hook_preprocess_page() — ecosistema_jaraba_theme.theme:2439
    |-- _ecosistema_jaraba_theme_get_base_settings($config)
    |   `-- Lee de ecosistema_jaraba_theme.settings (config global)
    |       CTA: enable_header_cta=1, header_cta_text='Andalucia +ei',
    |            header_cta_url='/andalucia-ei'
    |
    |-- SI $route_match->getParameter('page_content') instanceof PageContent:
    |   |-- MetaSiteResolverService::resolveFromPageContent($page_content)
    |   |-- Override: header_cta_text = SiteConfig::getHeaderCtaText()
    |   |-- Override: header_cta_url = SiteConfig::getHeaderCtaUrl()
    |   |-- Override: header_layout, footer_layout, nav_items, copyright
    |   `-- Override: logo, site_name, site_slogan
    |   CORRECTO: Pero SOLO se ejecuta en rutas con parametro page_content.
    |
    `-- SI NO es page_content:
        `-- Usa valores GLOBALES. CTA = "Andalucia +ei"
            PROBLEMA: Rutas /blog, /login, /my-settings, / NO obtienen
                      branding del tenant.

[E] _header-classic.html.twig:179-184
    |-- {% if ts.enable_header_cta|default(false) %}
    |--   <a href="{{ ts.header_cta_url|default('/user/register') }}"
    |--      class="btn-primary btn-primary--glow">
    |--     {{ ts.header_cta_text|default('Empieza gratis'|t) }} ->
    |--   </a>
    `-- {% endif %}
        RESULTADO: Renderiza el CTA que venga de theme_settings.
                   Si es ruta page_content: CTA del SiteConfig.
                   Si es otra ruta: CTA GLOBAL ("Andalucia +ei").
                   NUNCA: CTA de TenantThemeConfig (/my-settings/design).
```

### 2.2 Gaps identificados

| ID | Gap | Severidad | Impacto |
|----|-----|-----------|---------|
| GAP-1 | TenantThemeConfig se guarda pero NUNCA se lee para layout/CTA/nav en hook_preprocess_page | P0 Critico | Config del tenant es invisible |
| GAP-2 | Campo `header_cta_url` no existe en TenantThemeConfig entity pero si en formulario | P0 Critico | URL del CTA se pierde silenciosamente |
| GAP-3 | Meta-site override solo en rutas page_content | P1 Alto | Branding inconsistente entre paginas |
| GAP-4 | Dos fuentes de CTA/layout sin sincronizacion (TenantThemeConfig vs SiteConfig) | P1 Alto | Confusion de administradores |
| GAP-5 | TenantContextService resuelve por usuario, no por hostname | P1 Alto | Anonimos ven tema global |
| GAP-6 | Dos pipelines separados (CSS tokens vs Layout) leen de fuentes distintas | P2 Medio | Inconsistencia arquitectonica |

### 2.3 Dos sistemas paralelos sin integracion

El proyecto tiene DOS sistemas de theming registrados en services.yml que NO se conectan:

**Sistema A: TenantThemeConfig (jaraba_theming) — ACTIVO pero INCOMPLETO**

| Componente | Archivo | Estado |
|-----------|---------|--------|
| TenantThemeConfig entity | `jaraba_theming/src/Entity/TenantThemeConfig.php` | 54 campos hardcoded, FALTA `header_cta_url` |
| ThemeTokenService | `jaraba_theming/src/Service/ThemeTokenService.php` | Genera CSS tokens. `getHeaderVariant()` y `getHeroVariant()` NO se usan en preprocess_page |
| IndustryPresetService | `jaraba_theming/src/Service/IndustryPresetService.php` | 15 presets hardcoded en PHP (no en DB) |
| TenantThemeCustomizerForm | `jaraba_theming/src/Form/TenantThemeCustomizerForm.php` | Frontend form en `/my-settings/design`. Guarda en TenantThemeConfig pero output NUNCA se lee |
| TenantThemeConfigForm | `jaraba_theming/src/Form/TenantThemeConfigForm.php` | Admin CRUD form. PremiumEntityFormBase. 5 secciones |
| hook_page_attachments | `jaraba_theming/jaraba_theming.module:29` | Inyecta CSS tokens (colores/fuentes). Correcto |
| hook_preprocess_html | `jaraba_theming/jaraba_theming.module:54` | Inyecta body classes (header--, hero--, vertical--). Correcto |

**Sistema B: DesignTokenConfig + StylePreset (ecosistema_jaraba_core) — REGISTRADO pero MUERTO**

| Componente | Archivo | Estado |
|-----------|---------|--------|
| DesignTokenConfig entity | `ecosistema_jaraba_core/src/Entity/DesignTokenConfig.php` | ConfigEntity. Cascada 4 niveles. JSON flexible |
| StylePreset entity | `ecosistema_jaraba_core/src/Entity/StylePreset.php` | ConfigEntity. Templates inmutables por sector |
| StylePresetService | `ecosistema_jaraba_core/src/Service/StylePresetService.php` | Resuelve tokens en cascada. **NUNCA consumido en hooks** |
| PresetApplicatorService | `ecosistema_jaraba_core/src/Service/PresetApplicatorService.php` | Copia preset a DesignTokenConfig. **NUNCA consumido** |
| TenantThemeService | `ecosistema_jaraba_core/src/Service/TenantThemeService.php` | Delegador legacy. Intenta ambos sistemas. **NUNCA consumido en hooks** |

**Consecuencia**: Los servicios del Sistema B estan registrados en `services.yml`, ocupan espacio en el container de DI, pero NO producen ningun efecto visible en el frontend. Son codigo muerto desde el punto de vista del usuario.

### 2.4 Matriz de campos duplicados

Tres entidades almacenan informacion de theming con campos que se superponen:

| Campo/Concepto | TenantThemeConfig | SiteConfig | DesignTokenConfig | Quien lo lee en runtime |
|---------------|-------------------|-----------|-------------------|------------------------|
| `header_cta_enabled` | boolean (L331) | (deducido de getHeaderCtaText) | — | SiteConfig (solo page_content) |
| `header_cta_text` | string max 32 (L336) | string max 50 (L354) | — | SiteConfig (solo page_content) |
| `header_cta_url` | **NO EXISTE** | string max 255 (L367) | — | SiteConfig (solo page_content) |
| `header_type/variant` | list_string (L281) | list_string (L315) | `component_variants` JSON | SiteConfig (solo page_content) |
| `header_sticky` | boolean (L326) | boolean (L334) | — | SiteConfig (solo page_content) |
| `footer_variant/type` | string (L376) | list_string (L381) | `component_variants` JSON | SiteConfig (solo page_content) |
| `footer_copyright` | string (L382) | string (L433) | — | SiteConfig (solo page_content) |
| `color_primary` | string hex (L108) | — | `color_tokens` JSON | TenantThemeConfig via CSS |
| `font_headings` | string (L194) | — | `typography_tokens` JSON | TenantThemeConfig via CSS |
| `social_*` (5 campos) | string cada uno | `social_links` JSON | — | NINGUNO |
| `site_name` | string (en form, no entity) | string (L126) | — | SiteConfig |
| `logo` | image (L296) | image (L155) | — | SiteConfig |

**Conclusion**: Existe una duplicacion masiva de campos entre TenantThemeConfig y SiteConfig, con la agravante de que **solo SiteConfig se lee en runtime** y solo en rutas page_content.

### 2.5 Servicios muertos y consumidores

Analisis de consumo real en runtime (hooks, controllers, templates):

| Servicio | Registrado en | Consumido por hooks/templates | Estado |
|----------|--------------|------------------------------|--------|
| `jaraba_theming.token_service` | jaraba_theming.services.yml | hook_page_attachments, hook_preprocess_html, ecosistema_jaraba_theme.theme:2225 | ACTIVO |
| `jaraba_theming.industry_preset_service` | jaraba_theming.services.yml | TenantThemeCustomizerForm (form select) | ACTIVO (form only) |
| `ecosistema_jaraba_core.style_preset` | ecosistema_jaraba_core.services.yml | **NINGUNO** en hooks/templates | MUERTO |
| `ecosistema_jaraba_core.preset_applicator` | ecosistema_jaraba_core.services.yml | **NINGUNO** | MUERTO |
| `ecosistema_jaraba_core.tenant_theme` | ecosistema_jaraba_core.services.yml | **NINGUNO** en hooks/templates | MUERTO |
| `jaraba_site_builder.meta_site_resolver` | jaraba_site_builder.services.yml | ecosistema_jaraba_theme.theme:2502, PathProcessorPageContent | ACTIVO |

---

## 3. Arquitectura Objetivo

### 3.1 Cascada unificada de 5 niveles

La nueva arquitectura implementa una cascada donde cada nivel puede sobrescribir al anterior. El nivel mas especifico gana:

```
Nivel 1: PLATAFORMA (ecosistema_jaraba_theme.settings)
    |   Defaults globales del SaaS: CTA "Empezar", colores corporativos,
    |   layout classic, footer standard, megamenu SaaS.
    |   Fuente: Drupal config en /admin/appearance/settings/ecosistema_jaraba_theme
    |
    v
Nivel 2: VERTICAL (TenantThemeConfig.vertical o DesignTokenConfig scope=vertical)
    |   Paleta de colores por vertical (empleabilidad=azul, agro=verde, etc.)
    |   Variantes de hero/card preconfiguradas por sector.
    |   Fuente: IndustryPresetService o StylePreset entities.
    |
    v
Nivel 3: PLAN (DesignTokenConfig scope=plan) [FUTURO — no implementar ahora]
    |   Features visuales por plan (dark mode solo Enterprise+, custom CSS).
    |   Fuente: DesignTokenConfig entities.
    |
    v
Nivel 4: TENANT (TenantThemeConfig entity)
    |   Personalizacion completa del tenant: colores, fuentes, CTA, header,
    |   footer, logo, redes sociales.
    |   Fuente: /my-settings/design (TenantThemeCustomizerForm)
    |   >>> ESTE ES EL NIVEL QUE HOY NO SE LEE <<<
    |
    v
Nivel 5: META-SITIO (SiteConfig entity)
    |   Override por dominio/pagina especifica. Principalmente branding
    |   (site_name, tagline) y navegacion (nav_items, footer_items).
    |   Fuente: /admin/content/site-configs/{id}
    |   >>> HOY SOLO FUNCIONA EN RUTAS page_content <<<
    |
    v
RENDERIZADO: _header.html.twig, _footer.html.twig
    Recibe theme_settings con valores ya resueltos por la cascada.
    No necesita conocer el origen de cada valor.
```

### 3.2 Single Source of Truth por ambito

Para eliminar duplicaciones, cada ambito tiene UNA fuente de verdad:

| Ambito | Source of Truth | Entity | Quien la consume |
|--------|----------------|--------|-----------------|
| CSS tokens (colores, fuentes, radii) | TenantThemeConfig | ContentEntity, jaraba_theming | ThemeTokenService via hook_page_attachments |
| Layout (header_variant, footer_type) | TenantThemeConfig | ContentEntity, jaraba_theming | **NUEVO**: UnifiedThemeResolverService |
| CTA (text, url, enabled) | TenantThemeConfig | ContentEntity, jaraba_theming | **NUEVO**: UnifiedThemeResolverService |
| Site identity (name, tagline, logo) | SiteConfig | ContentEntity, jaraba_site_builder | MetaSiteResolverService |
| Navegacion (nav_items, footer_items) | SiteConfig + SitePageTree | ContentEntity, jaraba_site_builder | MetaSiteResolverService |
| Override por dominio/pagina | SiteConfig | ContentEntity, jaraba_site_builder | MetaSiteResolverService |

**Regla SSOT-THEME-001**: TenantThemeConfig es la fuente de verdad para TODO lo visual (colores, fuentes, layout, CTA). SiteConfig es la fuente de verdad para TODO lo estructural (nombre, logo, nav, paginas legales). Si ambos definen el mismo campo (ej: header_cta_text), SiteConfig gana como Nivel 5 (override de meta-sitio).

### 3.3 Pipeline de renderizacion unificado

```
REQUEST entra
    |
    v
[1] UnifiedThemeResolverService::resolveForCurrentRequest()
    |-- Intenta resolveFromHostname() via MetaSiteResolverService
    |   (funciona para anonimos y autenticados)
    |-- Intenta resolveTenantId() via TenantContextService
    |   (funciona solo para autenticados)
    |-- Carga TenantThemeConfig del tenant resuelto
    |-- Carga SiteConfig del tenant resuelto (si existe)
    |-- Aplica cascada: Plataforma -> TenantThemeConfig -> SiteConfig
    `-- Retorna ThemeContext DTO con TODOS los valores resueltos

[2] hook_page_attachments() — CSS tokens
    |-- ThemeTokenService::generateCss(tenantId) [SIN CAMBIOS]
    `-- Inyecta <style id="jaraba-design-tokens">

[3] hook_preprocess_html() — Body classes
    |-- ThemeTokenService::getHeaderVariant() [SIN CAMBIOS]
    `-- Body classes: header--, hero--, vertical--, dark-mode-enabled

[4] hook_preprocess_page() — Layout/Content variables
    |-- UnifiedThemeResolverService::resolveForCurrentRequest()
    |-- Aplica cascada para theme_settings:
    |   Base: _ecosistema_jaraba_theme_get_base_settings() (Nivel 1)
    |   Override: TenantThemeConfig campos (Nivel 4) [NUEVO]
    |   Override: SiteConfig campos (Nivel 5) [MEJORADO: todas las rutas]
    |-- Inyecta variables Twig: theme_settings, meta_site, site_name, logo
    `-- RESULTADO: CTA, header, footer, nav correctos en TODAS las rutas

[5] _header.html.twig + _footer.html.twig
    `-- Renderizan con theme_settings ya resueltos. Sin cambios en templates.
```

### 3.4 Resolucion de tenant para anonimos

**Problema actual**: `TenantContextService::getCurrentTenant()` devuelve NULL para anonimos porque resuelve por `admin_user_id` o group membership del usuario actual.

**Solucion**: `UnifiedThemeResolverService` usa DOS estrategias en paralelo:

```
Estrategia A: Por hostname (funciona para anonimos)
    Request::getHost() -> MetaSiteResolverService::resolveFromDomain()
    -> 3 subestrategias (Domain entity, Tenant.domain, subdomain prefix)
    -> group_id -> tenant_id

Estrategia B: Por usuario autenticado (funciona para usuarios logueados)
    TenantContextService::getCurrentTenantId()
    -> admin_user_id match O group membership
    -> tenant_id

Prioridad: Si ambas resuelven, hostname gana (porque el usuario puede estar
visitando un meta-sitio ajeno estando logueado en otro tenant).
```

### 3.5 Diagrama de componentes C4

```
+------------------------------------------------------------------+
|                    ECOSISTEMA JARABA THEME                        |
|                                                                   |
|  hook_preprocess_page()                                          |
|  +------------------------------------------------------------+  |
|  | 1. Base settings (global config)                            |  |
|  | 2. UnifiedThemeResolverService::resolveForCurrentRequest()  |  |
|  |    +-- Hostname -> MetaSiteResolverService                  |  |
|  |    +-- User -> TenantContextService                         |  |
|  |    +-- TenantThemeConfig (Nivel 4)                          |  |
|  |    `-- SiteConfig (Nivel 5)                                 |  |
|  | 3. theme_settings = cascada resuelta                        |  |
|  +------------------------------------------------------------+  |
|                         |                                         |
|  hook_preprocess_html() |  hook_page_attachments()               |
|  +-------------------+  |  +-----------------------------+       |
|  | Body classes:      |  |  | <style id="jaraba-design-  |       |
|  | header--classic    |  |  |   tokens">                  |       |
|  | hero--split        |  |  | :root {                     |       |
|  | vertical--platform |  |  |   --ej-color-primary: ...   |       |
|  +-------------------+  |  |   --ej-font-headings: ...    |       |
|                         |  | }                            |       |
|                         |  +-----------------------------+       |
|                         v                                         |
|  +------------------------------------------------------------+  |
|  | _header.html.twig (dispatcher)                              |  |
|  |   -> _header-classic.html.twig                              |  |
|  |      {{ ts.enable_header_cta }}  <- cascada resuelta        |  |
|  |      {{ ts.header_cta_text }}    <- cascada resuelta        |  |
|  |      {{ ts.header_cta_url }}     <- cascada resuelta        |  |
|  +------------------------------------------------------------+  |
|  | _footer.html.twig                                           |  |
|  |   {{ ts.footer_layout }}         <- cascada resuelta        |  |
|  |   {{ ts.footer_copyright }}      <- cascada resuelta        |  |
|  +------------------------------------------------------------+  |
+------------------------------------------------------------------+

+---------------------------+   +---------------------------+
| jaraba_theming             |   | jaraba_site_builder       |
|                            |   |                           |
| TenantThemeConfig          |   | SiteConfig                |
| (ContentEntity)            |   | (ContentEntity)           |
| - color_primary            |   | - site_name               |
| - font_headings            |   | - site_tagline            |
| - header_variant           |   | - site_logo               |
| - header_cta_enabled       |   | - header_cta_text (L5)    |
| - header_cta_text          |   | - header_cta_url (L5)     |
| - header_cta_url [NUEVO]   |   | - header_type (L5)        |
| - footer_variant           |   | - footer_type (L5)        |
| - custom_css               |   | - nav_items, footer_items |
|                            |   |                           |
| ThemeTokenService          |   | MetaSiteResolverService   |
| (CSS tokens)               |   | (hostname -> tenant)      |
+---------------------------+   +---------------------------+
             |                              |
             v                              v
+----------------------------------------------------------+
| UnifiedThemeResolverService [NUEVO]                       |
| ecosistema_jaraba_core                                    |
|                                                           |
| resolveForCurrentRequest(): ThemeContext                   |
| - Combina hostname resolution + user resolution           |
| - Carga TenantThemeConfig + SiteConfig                    |
| - Aplica cascada 5 niveles                                |
| - Retorna DTO con todos los valores resueltos             |
|                                                           |
| Cache: request-scoped (static property)                   |
| Fallback: global theme settings si no hay tenant          |
+----------------------------------------------------------+
```

---

## 4. Requisitos Previos

### 4.1 Software Requerido

| Software | Version | Proposito |
|----------|---------|-----------|
| Lando | 3.x | Entorno de desarrollo local |
| PHP | 8.4 | Runtime Drupal 11 |
| Drupal | 11.x | CMS base |
| MariaDB | 10.11 | Base de datos |
| Dart Sass | 1.77+ | Compilacion SCSS (`@use`, NO `@import`) |
| Node.js | 20 LTS | Compilacion assets frontend |
| Drush | 13.x | CLI de Drupal (dentro del container) |

### 4.2 Conocimientos Previos

- Arquitectura multi-tenant del proyecto (TENANT-BRIDGE-001, TenantContextService, MetaSiteResolverService)
- Sistema de 5 capas de Design Tokens (docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md)
- Patron Zero Region (paginas frontend limpias sin regiones Drupal)
- PremiumEntityFormBase y patron de entity forms del proyecto
- SCSS con Dart Sass moderno (@use, color-mix(), variables CSS)
- Hook system de Drupal (preprocess_html, preprocess_page, page_attachments)

### 4.3 Accesos Necesarios

- [x] Acceso al repositorio GitHub (privado)
- [x] Entorno Lando funcional (`lando start` desde raiz)
- [x] URL de desarrollo: `https://jaraba-saas.lndo.site/`
- [x] Acceso admin Drupal (usuario 1)
- [x] Acceso a meta-sitios de test: `pepejaraba.jaraba-saas.lndo.site`, `plataformadeecosistemas.jaraba-saas.lndo.site`

### 4.4 Comandos dentro del container Docker

TODOS los comandos Drupal/Drush se ejecutan dentro del container de Lando:

```bash
# Entrar al container
lando ssh

# O prefijo lando para cada comando
lando drush cr
lando drush updb
lando php scripts/validation/validate-all.sh
```

---

## 5. Fases de Implementacion

### 5.1 Fase 1 — Correccion de gaps criticos (P0)

**Objetivo**: Cerrar GAP-1 y GAP-2. Que TenantThemeConfig se lea correctamente y que `header_cta_url` exista como campo.

#### Paso 1.1: Anadir campo `header_cta_url` a TenantThemeConfig

**Archivo**: `web/modules/custom/jaraba_theming/src/Entity/TenantThemeConfig.php`

Anadir despues de `header_cta_text` (linea 340):

```php
$fields['header_cta_url'] = BaseFieldDefinition::create('string')
    ->setLabel(t('URL CTA'))
    ->setDescription(t('URL del boton CTA en el header (ruta interna o URL absoluta).'))
    ->setSetting('max_length', 255)
    ->setDefaultValue('/registro')
    ->setDisplayConfigurable('form', TRUE);
```

**Directrices aplicables**:
- UPDATE-HOOK-REQUIRED-001: Requiere hook_update_N() para instalar el campo
- UPDATE-FIELD-DEF-001: Usar `setName()` y `setTargetEntityTypeId()` en el update hook
- UPDATE-HOOK-CATCH-001: try-catch con `\Throwable`, NO `\Exception`

#### Paso 1.2: hook_update_N() para el nuevo campo

**Archivo**: `web/modules/custom/jaraba_theming/jaraba_theming.install`

```php
/**
 * Add header_cta_url field to TenantThemeConfig entity.
 */
function jaraba_theming_update_10002(): void {
  try {
    $update_manager = \Drupal::entityDefinitionUpdateManager();
    $field = \Drupal\Core\Field\BaseFieldDefinition::create('string')
      ->setName('header_cta_url')
      ->setTargetEntityTypeId('tenant_theme_config')
      ->setLabel(t('URL CTA'))
      ->setDescription(t('URL del boton CTA en el header.'))
      ->setSetting('max_length', 255)
      ->setDefaultValue('/registro')
      ->setDisplayConfigurable('form', TRUE);

    $update_manager->installFieldStorageDefinition(
      'header_cta_url',
      'tenant_theme_config',
      'jaraba_theming',
      $field
    );
  }
  catch (\Throwable $e) {
    \Drupal::logger('jaraba_theming')->error(
      'Error installing header_cta_url field: @error',
      ['@error' => $e->getMessage()]
    );
  }
}
```

**Verificacion**:
```bash
lando drush updb -y
lando drush entity-updates  # Verificar que no hay pendientes
```

#### Paso 1.3: Verificar que TenantThemeCustomizerForm guarda `header_cta_url` correctamente

**Archivo**: `web/modules/custom/jaraba_theming/src/Form/TenantThemeCustomizerForm.php`

El mapping en linea 880 ya tiene `'header_cta_url' => 'header_cta_url'`, y el check `$config->hasField($entityField)` en linea 910 ahora encontrara el campo. **No requiere cambios en el form** — solo el campo en la entity era lo que faltaba.

**Resultado esperado**: Al guardar en `/my-settings/design`, el campo `header_cta_url` se persiste correctamente en TenantThemeConfig.

---

### 5.2 Fase 2 — Cascada unificada de layout

**Objetivo**: Crear `UnifiedThemeResolverService` que lea TenantThemeConfig (Nivel 4) y SiteConfig (Nivel 5) para inyectar en theme_settings en TODAS las rutas.

#### Paso 2.1: Crear UnifiedThemeResolverService

**Archivo**: `web/modules/custom/ecosistema_jaraba_core/src/Service/UnifiedThemeResolverService.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_site_builder\Service\MetaSiteResolverService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resuelve la configuracion visual completa para el request actual.
 *
 * Implementa una cascada de 5 niveles:
 * 1. Plataforma (defaults globales)
 * 2. Vertical (paleta por sector)
 * 3. Plan (features por plan) [reservado]
 * 4. Tenant (TenantThemeConfig)
 * 5. Meta-sitio (SiteConfig override)
 *
 * Funciona tanto para usuarios anonimos (resolucion por hostname)
 * como para usuarios autenticados (resolucion por TenantContextService).
 *
 * @package Drupal\ecosistema_jaraba_core\Service
 */
class UnifiedThemeResolverService {

  /**
   * Cache estatica por request.
   */
  protected ?array $resolvedContext = NULL;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
    protected ?MetaSiteResolverService $metaSiteResolver = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * Resuelve el contexto de tema completo para el request actual.
   *
   * Combina resolucion por hostname (anonimos) y por usuario (autenticados).
   * Aplica cascada TenantThemeConfig (Nivel 4) -> SiteConfig (Nivel 5).
   *
   * @return array
   *   Array con claves:
   *   - 'tenant_theme_config': TenantThemeConfig entity o NULL
   *   - 'site_config': SiteConfig entity o NULL
   *   - 'meta_site': array de MetaSiteResolverService o NULL
   *   - 'theme_overrides': array key=>value con overrides para theme_settings
   *   - 'tenant_id': int o NULL
   *   - 'resolved_via': string ('hostname'|'user'|'none')
   */
  public function resolveForCurrentRequest(): array {
    if ($this->resolvedContext !== NULL) {
      return $this->resolvedContext;
    }

    $result = [
      'tenant_theme_config' => NULL,
      'site_config' => NULL,
      'meta_site' => NULL,
      'theme_overrides' => [],
      'tenant_id' => NULL,
      'resolved_via' => 'none',
    ];

    // Estrategia A: Resolver por hostname (funciona para anonimos).
    $metaSite = $this->resolveByHostname();
    if ($metaSite) {
      $result['meta_site'] = $metaSite;
      $result['site_config'] = $metaSite['site_config'] ?? NULL;
      $result['tenant_id'] = $metaSite['group_id'] ?? NULL;
      $result['resolved_via'] = 'hostname';
    }

    // Estrategia B: Resolver por usuario autenticado (fallback).
    if ($result['tenant_id'] === NULL) {
      $tenantId = $this->resolveByUser();
      if ($tenantId !== NULL) {
        $result['tenant_id'] = $tenantId;
        $result['resolved_via'] = 'user';
      }
    }

    // Cargar TenantThemeConfig si hay tenant_id.
    if ($result['tenant_id'] !== NULL) {
      $result['tenant_theme_config'] = $this->loadTenantThemeConfig(
        (int) $result['tenant_id']
      );
    }

    // Construir theme_overrides aplicando cascada.
    $result['theme_overrides'] = $this->buildThemeOverrides(
      $result['tenant_theme_config'],
      $result['site_config'],
      $result['meta_site']
    );

    $this->resolvedContext = $result;
    return $result;
  }

  /**
   * Resuelve meta-sitio por hostname del request actual.
   */
  protected function resolveByHostname(): ?array {
    if ($this->metaSiteResolver === NULL) {
      return NULL;
    }

    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return NULL;
    }

    try {
      return $this->metaSiteResolver->resolveFromRequest($request);
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Resuelve tenant ID por usuario autenticado.
   */
  protected function resolveByUser(): ?int {
    if ($this->tenantContext === NULL) {
      return NULL;
    }

    try {
      return $this->tenantContext->getCurrentTenantId();
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Carga TenantThemeConfig activa para un tenant.
   */
  protected function loadTenantThemeConfig(int $tenantId): ?object {
    try {
      if (!$this->entityTypeManager->hasDefinition('tenant_theme_config')) {
        return NULL;
      }

      $configs = $this->entityTypeManager
        ->getStorage('tenant_theme_config')
        ->loadByProperties([
          'tenant_id' => $tenantId,
          'is_active' => TRUE,
        ]);

      return !empty($configs) ? reset($configs) : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Construye el array de overrides aplicando cascada Nivel 4 -> Nivel 5.
   *
   * Nivel 4 (TenantThemeConfig) define la base del tenant.
   * Nivel 5 (SiteConfig) puede sobrescribir campos especificos del meta-sitio.
   *
   * @param object|null $themeConfig
   *   TenantThemeConfig entity o NULL.
   * @param object|null $siteConfig
   *   SiteConfig entity o NULL.
   * @param array|null $metaSite
   *   Contexto de meta-sitio (nav_items, etc.) o NULL.
   *
   * @return array
   *   Array key=>value con overrides para theme_settings.
   */
  protected function buildThemeOverrides(
    ?object $themeConfig,
    ?object $siteConfig,
    ?array $metaSite,
  ): array {
    $overrides = [];

    // --- Nivel 4: TenantThemeConfig ---
    if ($themeConfig !== NULL) {
      // Header CTA.
      $ctaEnabled = (bool) ($themeConfig->get('header_cta_enabled')->value ?? TRUE);
      $ctaText = $themeConfig->get('header_cta_text')->value ?? '';
      $ctaUrl = $themeConfig->get('header_cta_url')->value ?? '/registro';

      if ($ctaEnabled && !empty($ctaText)) {
        $overrides['enable_header_cta'] = TRUE;
        $overrides['header_cta_text'] = $ctaText;
        $overrides['header_cta_url'] = $ctaUrl;
      }

      // Header layout y sticky.
      $headerVariant = $themeConfig->get('header_variant')->value ?? '';
      if (!empty($headerVariant)) {
        $overrides['header_layout'] = $headerVariant;
      }
      $overrides['header_sticky'] = (bool) ($themeConfig->get('header_sticky')->value ?? TRUE);

      // Footer.
      $footerVariant = $themeConfig->get('footer_variant')->value ?? '';
      if (!empty($footerVariant)) {
        $overrides['footer_layout'] = $footerVariant;
      }
      $footerCopyright = $themeConfig->get('footer_copyright')->value ?? '';
      if (!empty($footerCopyright)) {
        $overrides['footer_copyright'] = str_replace(
          ['[year]', '{year}'],
          date('Y'),
          $footerCopyright
        );
      }

      // Redes sociales.
      foreach (['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'] as $network) {
        $field = 'social_' . $network;
        if ($themeConfig->hasField($field)) {
          $value = $themeConfig->get($field)->value ?? '';
          if (!empty($value)) {
            $overrides['footer_social_' . $network] = $value;
          }
        }
      }

      // Desactivar megamenu SaaS en meta-sitios de tenants.
      $overrides['header_megamenu'] = FALSE;
      $overrides['footer_show_powered_by'] = FALSE;
    }

    // --- Nivel 5: SiteConfig (override de meta-sitio) ---
    if ($siteConfig !== NULL) {
      // Resolver traduccion del SiteConfig para idioma actual.
      try {
        $currentLangcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if ($siteConfig->isTranslatable() && $siteConfig->hasTranslation($currentLangcode)) {
          $siteConfig = $siteConfig->getTranslation($currentLangcode);
        }
      }
      catch (\Throwable) {
        // Continuar con el idioma por defecto.
      }

      // CTA override desde SiteConfig (Nivel 5 gana sobre Nivel 4).
      $scCtaText = $siteConfig->getHeaderCtaText() ?? '';
      if (!empty($scCtaText)) {
        $overrides['enable_header_cta'] = TRUE;
        $overrides['header_cta_text'] = $scCtaText;
        $overrides['header_cta_url'] = $siteConfig->getHeaderCtaUrl() ?: '#';
      }

      // Header/Footer override.
      $scHeaderType = $siteConfig->getHeaderType() ?? '';
      if (!empty($scHeaderType)) {
        $overrides['header_layout'] = $scHeaderType;
      }
      $overrides['header_sticky'] = $siteConfig->isHeaderSticky();

      $scFooterType = $siteConfig->getFooterType() ?? '';
      if (!empty($scFooterType)) {
        $overrides['footer_layout'] = $scFooterType;
      }

      $scFooterCopyright = $siteConfig->getFooterCopyright() ?? '';
      if (!empty($scFooterCopyright)) {
        $overrides['footer_copyright'] = str_replace(
          ['[year]', '{year}'],
          date('Y'),
          $scFooterCopyright
        );
      }

      // Auth visibility.
      if (method_exists($siteConfig, 'isHeaderShowAuth')) {
        $overrides['header_show_auth'] = $siteConfig->isHeaderShowAuth();
      }

      // Ecosystem footer band.
      if (method_exists($siteConfig, 'isEcosystemFooterEnabled')) {
        $overrides['ecosystem_footer_enabled'] = $siteConfig->isEcosystemFooterEnabled();
        if ($overrides['ecosystem_footer_enabled'] && method_exists($siteConfig, 'getEcosystemFooterLinks')) {
          $overrides['ecosystem_footer_links'] = $siteConfig->getEcosystemFooterLinks();
        }
      }
    }

    // Navegacion desde meta-sitio context.
    if ($metaSite !== NULL) {
      if (!empty($metaSite['nav_items_formatted'])) {
        $overrides['navigation_items'] = implode("\n", $metaSite['nav_items_formatted']);
      }

      // Footer columns desde nav_items y footer_items.
      $navItems = $metaSite['nav_items'] ?? [];
      $footerLegalItems = $metaSite['footer_items'] ?? [];

      if (!empty($navItems) || !empty($footerLegalItems)) {
        $splitPoint = (int) ceil(count($navItems) / 2);

        $col1Items = array_slice($navItems, 0, $splitPoint);
        if (!empty($col1Items)) {
          $col1Links = array_map(fn($i) => $i['title'] . '|' . $i['url'], $col1Items);
          $overrides['footer_nav_col1_title'] = ($siteConfig ? ($siteConfig->get('footer_col1_title')->value ?? '') : '') ?: ($metaSite['tenant_name'] ?? '');
          $overrides['footer_nav_col1_links'] = implode("\n", $col1Links);
        }

        $col2Items = array_slice($navItems, $splitPoint);
        if (!empty($col2Items)) {
          $col2Links = array_map(fn($i) => $i['title'] . '|' . $i['url'], $col2Items);
          $overrides['footer_nav_col2_title'] = ($siteConfig ? ($siteConfig->get('footer_col2_title')->value ?? '') : '') ?: 'Empresa';
          $overrides['footer_nav_col2_links'] = implode("\n", $col2Links);
        }

        if (!empty($footerLegalItems)) {
          $col3Links = array_map(fn($i) => $i['title'] . '|' . $i['url'], $footerLegalItems);
          $overrides['footer_nav_col3_title'] = ($siteConfig ? ($siteConfig->get('footer_col3_title')->value ?? '') : '') ?: 'Legal';
          $overrides['footer_nav_col3_links'] = implode("\n", $col3Links);
        }
      }
    }

    return $overrides;
  }

}
```

**Directrices aplicables**:
- OPTIONAL-CROSSMODULE-001: `@?jaraba_site_builder.meta_site_resolver` y `@?ecosistema_jaraba_core.tenant_context` en services.yml
- CONTAINER-DEPS-002: Sin dependencias circulares (UnifiedThemeResolverService consume servicios, no es consumido por ellos)
- PRESAVE-RESILIENCE-001: Todos los accesos a servicios opcionales protegidos con try-catch `\Throwable`

#### Paso 2.2: Registrar el servicio

**Archivo**: `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml`

Anadir:

```yaml
ecosistema_jaraba_core.unified_theme_resolver:
  class: Drupal\ecosistema_jaraba_core\Service\UnifiedThemeResolverService
  arguments:
    - '@entity_type.manager'
    - '@request_stack'
    - '@logger.channel.ecosistema_jaraba_core'
    - '@?jaraba_site_builder.meta_site_resolver'
    - '@?ecosistema_jaraba_core.tenant_context'
```

#### Paso 2.3: Modificar hook_preprocess_page() para usar la cascada unificada

**Archivo**: `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme`

Modificar la funcion `ecosistema_jaraba_theme_preprocess_page()` (linea 2439+). La logica actual del bloque META-SITIO OVERRIDE (lineas 2488-2621) se reemplaza por el servicio unificado. La nueva logica:

```php
function ecosistema_jaraba_theme_preprocess_page(&$variables) {
  $config = \Drupal::config('ecosistema_jaraba_theme.settings');
  $variables['theme_settings'] = _ecosistema_jaraba_theme_get_base_settings($config);

  // ... (mantener logo, site_name, site_slogan, vertical_brand existentes) ...

  // =========================================================================
  // THEMING-UNIFY-001: Cascada unificada de tema multi-tenant.
  // Reemplaza el bloque META-SITIO OVERRIDE anterior.
  // Funciona en TODAS las rutas (no solo page_content).
  // Funciona para anonimos (resolucion por hostname) y autenticados.
  // =========================================================================
  $variables['meta_site'] = NULL;
  try {
    if (\Drupal::hasService('ecosistema_jaraba_core.unified_theme_resolver')) {
      $resolver = \Drupal::service('ecosistema_jaraba_core.unified_theme_resolver');
      $context = $resolver->resolveForCurrentRequest();

      // Aplicar overrides de la cascada a theme_settings.
      if (!empty($context['theme_overrides'])) {
        $variables['theme_settings'] = array_merge(
          $variables['theme_settings'],
          $context['theme_overrides']
        );
      }

      // Inyectar meta_site context si existe.
      if ($context['meta_site'] !== NULL) {
        $variables['meta_site'] = $context['meta_site'];

        // Override site identity desde meta-sitio.
        $variables['site_name'] = $context['meta_site']['tenant_name'] ?? $variables['site_name'];

        if ($context['site_config'] !== NULL) {
          $sc = $context['site_config'];
          $variables['site_slogan'] = $sc->getTagline() ?: '';

          // Logo del tenant.
          $logoFile = $sc->get('site_logo')->entity ?? NULL;
          if ($logoFile) {
            $file_url_generator = \Drupal::service('file_url_generator');
            $variables['logo'] = $file_url_generator->generateAbsoluteString(
              $logoFile->getFileUri()
            );
          }

          // Schema.org type.
          $schemaType = ($context['meta_site']['group_id'] ?? 0) == 5 ? 'Person' : 'Organization';
          $variables['meta_site']['schema_type'] = $schemaType;

          // Canonical URL.
          $tenantEntity = $sc->get('tenant_id')->entity ?? NULL;
          if ($tenantEntity) {
            $tenantDomain = $tenantEntity->get('domain')->value ?? NULL;
            if ($tenantDomain) {
              $variables['meta_site']['canonical_url'] = 'https://' . $tenantDomain;
            }
          }
        }
      }

      // Para page_content routes, mantener compatibilidad con H9 title override.
      // (esto se hace en hook_preprocess_html, no aqui)
    }
  }
  catch (\Throwable) {
    // Theme preprocess should never crash.
  }

  // ... (mantener i18n, megamenu condicional, copilot, avatar_nav, zero-region) ...

  // Megamenu: SOLO si NO hay meta-sitio resuelto (SaaS principal).
  if (empty($variables['meta_site'])) {
    $variables['theme_settings']['header_megamenu'] = TRUE;
  }
}
```

**NOTA CRITICA**: El bloque existente de META-SITIO OVERRIDE (lineas 2488-2621) se REEMPLAZA por el codigo anterior. Todo lo que estaba dentro de ese bloque ahora esta en `UnifiedThemeResolverService::buildThemeOverrides()`. Los bloques de i18n (2623+), megamenu (2656+), copilot (2729+), avatar_nav (2748+) y zero-region (2773+) se MANTIENEN sin cambios.

---

### 5.3 Fase 3 — Resolucion por hostname para anonimos

**Objetivo**: Cerrar GAP-5. Que los CSS tokens (colores, fuentes) tambien funcionen para visitantes anonimos de meta-sitios.

#### Paso 3.1: Modificar hook_page_attachments() en jaraba_theming.module

El hook actual usa `ThemeTokenService::generateCss()` que internamente llama a `resolveTenantId()` via `TenantContextService` — esto devuelve NULL para anonimos.

**Archivo**: `web/modules/custom/jaraba_theming/jaraba_theming.module`

Modificar `jaraba_theming_page_attachments()`:

```php
/**
 * Implements hook_page_attachments().
 *
 * Inyecta CSS Custom Properties segun la configuracion del tenant.
 * THEMING-UNIFY-001: Usa resolucion por hostname para anonimos.
 */
function jaraba_theming_page_attachments(array &$attachments): void {
  /** @var \Drupal\jaraba_theming\Service\ThemeTokenService $tokenService */
  $tokenService = \Drupal::service('jaraba_theming.token_service');

  // Primero intentar resolver tenant_id por hostname (funciona para anonimos).
  $tenantId = $tokenService->resolveTenantId();

  if ($tenantId === NULL && \Drupal::hasService('ecosistema_jaraba_core.unified_theme_resolver')) {
    try {
      /** @var \Drupal\ecosistema_jaraba_core\Service\UnifiedThemeResolverService $resolver */
      $resolver = \Drupal::service('ecosistema_jaraba_core.unified_theme_resolver');
      $context = $resolver->resolveForCurrentRequest();
      $tenantId = $context['tenant_id'] !== NULL ? (int) $context['tenant_id'] : NULL;
    }
    catch (\Throwable) {
      // Fallback a defaults.
    }
  }

  $css = $tokenService->generateCss($tenantId);

  $attachments['#attached']['html_head'][] = [
    [
      '#type' => 'html_tag',
      '#tag' => 'style',
      '#value' => $css,
      '#attributes' => [
        'id' => 'jaraba-design-tokens',
      ],
    ],
    'jaraba_design_tokens',
  ];
}
```

#### Paso 3.2: Eliminar inyeccion duplicada en ecosistema_jaraba_theme.theme

El bloque en lineas 2220-2247 de `ecosistema_jaraba_theme.theme` (que inyecta `<style id="jaraba-tenant-tokens">`) es **duplicado** del hook_page_attachments. Con la mejora del Paso 3.1, este bloque ya no es necesario y debe eliminarse para evitar doble inyeccion de CSS tokens.

**Archivo**: `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme`

Eliminar el bloque entre lineas 2220-2247 (el que inyecta `jaraba_tenant_tokens`).

**Razon**: El hook_page_attachments de jaraba_theming.module ya inyecta los tokens del tenant. Tener dos `<style>` con los mismos tokens es redundante y puede causar confusion en el inspector del navegador.

---

### 5.4 Fase 4 — Consolidacion de entidades

**Objetivo**: Cerrar GAP-4. Definir claramente la responsabilidad de cada entity para eliminar duplicacion de campos.

#### Paso 4.1: Documentar la separacion de responsabilidades (SSOT-THEME-001)

**NO se migran campos** entre entidades en esta fase. Se documenta la fuente de verdad:

| Responsabilidad | Entity | Razon |
|----------------|--------|-------|
| Colores, fuentes, border-radius, sombras | TenantThemeConfig | Son CSS tokens que se inyectan como :root variables. Estructura flat (un campo = un token). |
| Header variant, CTA, footer variant | TenantThemeConfig | El tenant controla la apariencia de SU meta-sitio. |
| Redes sociales | TenantThemeConfig | Son datos visuales del footer. |
| Custom CSS | TenantThemeConfig | Es un override visual (solo Enterprise+). |
| Site name, tagline, logo | SiteConfig | Son datos de identidad, no de tema visual. |
| Nav items, footer items | SiteConfig + SitePageTree | Son datos estructurales (arbol de paginas). |
| Homepage, blog index, paginas legales | SiteConfig | Son referencias a PageContent entities. |
| SEO (meta_title_suffix, OG, analytics) | SiteConfig | Son datos de indexacion, no visuales. |
| Contacto (email, phone, address) | SiteConfig | Son datos de negocio, no de tema. |

#### Paso 4.2: SiteConfig campos de header/footer como override (Nivel 5)

Los campos `header_cta_text`, `header_cta_url`, `header_type`, `footer_type` en SiteConfig se MANTIENEN pero se documentan como **Nivel 5 override**. El tenant configura en `/my-settings/design` (TenantThemeConfig, Nivel 4) y el admin puede sobrescribir desde `/admin/content/site-configs/{id}` (SiteConfig, Nivel 5) si necesita un override especifico.

**Regla**: Si SiteConfig tiene un campo de header/footer con valor NO vacio, GANA sobre TenantThemeConfig. Si esta vacio, TenantThemeConfig aplica. Esto ya esta implementado en `UnifiedThemeResolverService::buildThemeOverrides()`.

---

### 5.5 Fase 5 — Frontend self-service completo

**Objetivo**: Verificar que el formulario `/my-settings/design` funciona de extremo a extremo con la cascada unificada.

#### Paso 5.1: Verificar getConfigValue() en TenantThemeCustomizerForm

**Archivo**: `web/modules/custom/jaraba_theming/src/Form/TenantThemeCustomizerForm.php`

El metodo `getConfigValue()` (linea 965+) lee valores de la entity TenantThemeConfig para popular el formulario. Verificar que funciona correctamente para todos los campos incluyendo el nuevo `header_cta_url`:

```php
protected function getConfigValue($config, string $key, $default) {
    if (!$config) {
        return $default;
    }
    // Solo campos que existan en la entity.
    if (!$config->hasField($key)) {
        return $default;
    }
    $value = $config->get($key)->value;
    return $value !== NULL && $value !== '' ? $value : $default;
}
```

#### Paso 5.2: Verificar invalidacion de cache tras guardar

El submit handler (linea 953) llama a `\Drupal::service('cache.render')->invalidateAll()`. Esto es correcto pero agresivo. Verificar que tambien se invalida el cache estatico de `UnifiedThemeResolverService`:

El servicio usa `$this->resolvedContext` como cache request-scoped que se resetea automaticamente en cada request. No necesita invalidacion manual. El `cache.render->invalidateAll()` ya fuerza que el siguiente request recalcule todo.

#### Paso 5.3: Textos traducibles en el formulario ({% trans %} compliance)

Verificar que TODOS los textos en `TenantThemeCustomizerForm` usan `$this->t()`:

Los textos actuales (lineas 540-583) ya usan `$this->t()` correctamente:
- `$this->t('Encabezado')` — titulo del tab
- `$this->t('Mostrar boton CTA en encabezado')` — checkbox label
- `$this->t('Texto del boton CTA')` — textfield label
- `$this->t('Enlace del boton CTA')` — textfield label

**En templates Twig**: Verificar que los textos de fallback en `_header-classic.html.twig` usan `{% trans %}`:
- Linea 182: `{{ ts.header_cta_text|default('Empieza gratis'|t) }}` — usa filtro `|t`, deberia usar `{% trans %}` para consistencia, pero como es un default dentro de expresion Twig, `|t` es aceptable aqui (no es texto visible directamente, es fallback).

---

### 5.6 Fase 6 — Tests y verificacion RUNTIME-VERIFY-001

**Objetivo**: Garantizar que la implementacion funciona de extremo a extremo.

#### Paso 6.1: Test unitario para UnifiedThemeResolverService

**Archivo**: `web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/UnifiedThemeResolverServiceTest.php`

Escenarios a cubrir:
1. Sin tenant (anonimo en SaaS principal) -> retorna overrides vacio
2. Tenant resuelto por hostname -> retorna overrides de TenantThemeConfig
3. Tenant resuelto por usuario -> retorna overrides de TenantThemeConfig
4. Meta-sitio con SiteConfig -> SiteConfig gana sobre TenantThemeConfig para CTA
5. Meta-sitio sin SiteConfig -> TenantThemeConfig aplica
6. Cache request-scoped -> segunda llamada retorna mismo resultado sin queries
7. TenantThemeConfig sin header_cta_url -> usa default '/registro'

#### Paso 6.2: Test kernel para campo header_cta_url

**Archivo**: `web/modules/custom/jaraba_theming/tests/src/Kernel/TenantThemeConfigFieldsTest.php`

Verificar que el campo `header_cta_url` existe, se guarda y se lee correctamente:
```php
$config = TenantThemeConfig::create([
  'name' => 'Test Config',
  'header_cta_url' => '/mi-pagina',
  'header_cta_text' => 'Probar',
  'header_cta_enabled' => TRUE,
]);
$config->save();

$loaded = TenantThemeConfig::load($config->id());
$this->assertEquals('/mi-pagina', $loaded->get('header_cta_url')->value);
```

#### Paso 6.3: Verificacion RUNTIME-VERIFY-001

Checklist de 5 puntos obligatorios:

| # | Verificacion | Comando/Accion | Resultado esperado |
|---|-------------|----------------|-------------------|
| 1 | CSS compilado | `lando ssh -c "ls -la web/themes/custom/ecosistema_jaraba_theme/css/main.css"` | Timestamp > SCSS |
| 2 | Tablas DB | `lando drush sql:query "DESCRIBE tenant_theme_config"` | Campo `header_cta_url` existe |
| 3 | Rutas accesibles | `lando drush router:match /my-settings/design` | TenantThemeCustomizerForm |
| 4 | Servicio registrado | `lando drush debug:container ecosistema_jaraba_core.unified_theme_resolver` | Clase UnifiedThemeResolverService |
| 5 | drupalSettings | Inspeccionar `<style id="jaraba-design-tokens">` en el HTML | Variables --ej-* del tenant |

#### Paso 6.4: Verificacion visual por meta-sitio

Para cada meta-sitio, verificar consistencia:

| Meta-sitio | URL dev | Verificar |
|-----------|---------|-----------|
| SaaS principal | `https://jaraba-saas.lndo.site/es/` | CTA global correcto |
| PED | `https://plataformadeecosistemas.jaraba-saas.lndo.site/es/` | CTA del tenant (no "Andalucia +ei") |
| Pepe Jaraba | `https://pepejaraba.jaraba-saas.lndo.site/es/` | CTA del tenant |
| Jaraba Impact | `https://jarabaimpact.jaraba-saas.lndo.site/es/` | CTA del tenant |

Para CADA meta-sitio, verificar en TODAS estas rutas:
1. Homepage (`/es/`)
2. Una pagina de contenido (`/es/{slug-page-content}`)
3. `/es/blog` (ruta sin page_content)
4. `/es/user/login` (ruta de autenticacion)
5. `/es/my-settings/design` (formulario de personalizacion)

El CTA, header layout, footer y colores deben ser CONSISTENTES en todas las rutas del mismo meta-sitio.

---

## 6. Tabla de Correspondencia de Especificaciones Tecnicas

| ID Especificacion | Descripcion | Fase | Archivos afectados | Directriz vinculada |
|-------------------|-------------|------|-------------------|-------------------|
| THEMING-UNIFY-001 | Cascada unificada de 5 niveles para tema multi-tenant | Fase 2 | UnifiedThemeResolverService.php, ecosistema_jaraba_theme.theme | TENANT-001, TENANT-002 |
| THEMING-UNIFY-002 | Campo header_cta_url en TenantThemeConfig entity | Fase 1 | TenantThemeConfig.php, jaraba_theming.install | UPDATE-HOOK-REQUIRED-001, UPDATE-FIELD-DEF-001 |
| THEMING-UNIFY-003 | Resolucion de tenant por hostname para anonimos | Fase 3 | jaraba_theming.module, UnifiedThemeResolverService.php | DOMAIN-ROUTE-CACHE-001, VARY-HOST-001 |
| THEMING-UNIFY-004 | Eliminacion de inyeccion CSS duplicada | Fase 3 | ecosistema_jaraba_theme.theme (lineas 2220-2247) | SCSS-COMPILE-VERIFY-001 |
| THEMING-UNIFY-005 | SSOT por ambito: TenantThemeConfig (visual) vs SiteConfig (estructural) | Fase 4 | Documentacion (este plan) | TENANT-BRIDGE-001 |
| THEMING-UNIFY-006 | Preprocess_page unificado con servicio | Fase 2 | ecosistema_jaraba_theme.theme (lineas 2488-2621) | ZERO-REGION-001, PRESAVE-RESILIENCE-001 |
| THEMING-UNIFY-007 | Test unitario UnifiedThemeResolverService | Fase 6 | UnifiedThemeResolverServiceTest.php | KERNEL-TEST-001, MOCK-METHOD-001 |
| THEMING-UNIFY-008 | Test kernel campo header_cta_url | Fase 6 | TenantThemeConfigFieldsTest.php | KERNEL-TEST-DEPS-001 |
| THEMING-UNIFY-009 | Verificacion RUNTIME-VERIFY-001 post-implementacion | Fase 6 | (scripts de verificacion) | RUNTIME-VERIFY-001, IMPLEMENTATION-CHECKLIST-001 |
| THEMING-UNIFY-010 | Registro de servicio con deps opcionales | Fase 2 | ecosistema_jaraba_core.services.yml | OPTIONAL-CROSSMODULE-001, PHANTOM-ARG-001 |

---

## 7. Tabla de Cumplimiento de Directrices

### 7.1 Directrices de Arquitectura Multi-Tenant

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| TENANT-001 | Toda query DEBE filtrar por tenant | TenantThemeConfig se carga con `loadByProperties(['tenant_id' => $id])` | Fase 2 |
| TENANT-002 | Usar tenant_context para obtener tenant | UnifiedThemeResolverService usa TenantContextService como fallback | Fase 2 |
| TENANT-BRIDGE-001 | SIEMPRE usar TenantBridgeService para Tenant<->Group | MetaSiteResolverService resuelve group_id, NO tenant_id directamente | Fase 2 |
| TENANT-ISOLATION-ACCESS-001 | AccessControlHandler verifica tenant match | TenantThemeConfig AccessControlHandler existente ya lo verifica | N/A |
| DOMAIN-ROUTE-CACHE-001 | Cada hostname DEBE tener Domain entity | Verificar como prerequisito. No se modifica el sistema de Domain | Fase 3 |
| VARY-HOST-001 | Append Vary: Host para CDN | SecurityHeadersSubscriber ya implementado. Sin cambios | N/A |

### 7.2 Directrices de Codigo PHP

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| CONTROLLER-READONLY-001 | No redeclarar typed properties de padre | UnifiedThemeResolverService no extiende ControllerBase. No aplica | N/A |
| DRUPAL11-001 | Hijos no redeclaran typed properties del padre | Sin herencia problematica. Constructor promotion seguro | Fase 2 |
| UPDATE-HOOK-REQUIRED-001 | Cambios en baseFieldDefinitions requieren hook_update_N | hook_update_10002 para header_cta_url | Fase 1 |
| UPDATE-FIELD-DEF-001 | setName() y setTargetEntityTypeId() en updateFieldStorageDefinition | Incluido en hook_update_10002 | Fase 1 |
| UPDATE-HOOK-CATCH-001 | try-catch con \Throwable, NO \Exception | Todos los try-catch usan \Throwable | Fase 1-2 |
| OPTIONAL-CROSSMODULE-001 | Cross-module deps con @? en services.yml | `@?jaraba_site_builder.meta_site_resolver`, `@?ecosistema_jaraba_core.tenant_context` | Fase 2 |
| CONTAINER-DEPS-002 | No dependencias circulares | UnifiedThemeResolverService consume servicios, no es consumido por ellos en DI | Fase 2 |
| LOGGER-INJECT-001 | @logger.channel.X -> LoggerInterface | Usa `@logger.channel.ecosistema_jaraba_core` con LoggerInterface en constructor | Fase 2 |
| PHANTOM-ARG-001 | Args services.yml coinciden con constructor | 5 args en yml = 5 params en constructor | Fase 2 |
| PRESAVE-RESILIENCE-001 | Servicios opcionales con hasService() + try-catch | Todos los accesos opcionales protegidos | Fase 2 |
| ACCESS-RETURN-TYPE-001 | checkAccess() devuelve AccessResultInterface | No se modifica AccessControlHandler | N/A |

### 7.3 Directrices de Theming y Frontend

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| CSS-VAR-ALL-COLORS-001 | CADA color en SCSS DEBE ser var(--ej-*, fallback) | No se modifican SCSS. Los tokens se inyectan desde TenantThemeConfig::generateCssVariables() | N/A |
| SCSS-001 | @use crea scope aislado | No se crean nuevos parciales SCSS en esta implementacion | N/A |
| SCSS-COMPILE-VERIFY-001 | Recompilar post-edicion y verificar timestamp | Verificar si se modifican SCSS. En esta implementacion no se tocan SCSS | N/A |
| SCSS-COLORMIX-001 | Migrar rgba() a color-mix() | No se modifican SCSS existentes | N/A |
| SCSS-COMPILETIME-001 | Variables SCSS para color.scale DEBEN ser hex estatico | No se modifican variables SCSS | N/A |
| ZERO-REGION-001 | Variables via hook_preprocess_page, controller devuelve solo markup | Sin cambios en controllers. Variables se inyectan en preprocess_page | Fase 2 |
| ZERO-REGION-003 | #attached del controller NO se procesa. Usar preprocess | CSS tokens via hook_page_attachments (modulo hook, no controller) | Fase 3 |
| ICON-CONVENTION-001 | Usar jaraba_icon() con variante duotone | No se anaden nuevos iconos en esta implementacion | N/A |
| ICON-DUOTONE-001 | Variante default duotone | N/A | N/A |
| ICON-COLOR-001 | Solo colores paleta Jaraba | N/A | N/A |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | No se anaden nuevas URLs hardcoded | N/A |

### 7.4 Directrices de Twig y Traduccion

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| {% trans %} obligatorio | Textos SIEMPRE con bloque trans, NO filtro \|t | Los templates existentes ya cumplen. No se modifican templates | N/A |
| TWIG-ENTITY-METHOD-001 | Usar getters, no propiedades directas | UnifiedThemeResolverService usa `->get('field')->value`, no `->field` | Fase 2 |
| INNERHTML-XSS-001 | Drupal.checkPlain() para datos API en innerHTML | No hay JS nuevo en esta implementacion | N/A |

### 7.5 Directrices de Entity y Forms

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| PREMIUM-FORMS-PATTERN-001 | Toda entity form extiende PremiumEntityFormBase | TenantThemeConfigForm ya extiende PremiumEntityFormBase. Sin cambios | N/A |
| ENTITY-FK-001 | FKs cross-modulo como integer, mismo modulo como entity_reference | tenant_id en TenantThemeConfig es entity_reference (correcto, apunta a tenant del mismo ecosistema) | N/A |
| AUDIT-CONS-001 | TODA ContentEntity DEBE tener AccessControlHandler | TenantThemeConfig ya tiene uno en anotacion | N/A |
| ENTITY-PREPROCESS-001 | TODA ContentEntity con view mode DEBE tener preprocess | jaraba_theming_preprocess_tenant_theme_config ya existe en .module | N/A |
| FIELD-UI-SETTINGS-TAB-001 | Entities con field_ui_base_route DEBEN tener default local task | Verificar existencia. Si falta, anadir | Fase 1 |
| LABEL-NULLSAFE-001 | $entity->label() puede devolver NULL | PremiumEntityFormBase tiene null-safe fallback | N/A |

### 7.6 Directrices de JavaScript

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| Vanilla JS + Drupal.behaviors | NO React, NO Vue | No se anade JS nuevo | N/A |
| CSRF-JS-CACHE-001 | Token /session/token cacheado | No hay nuevas llamadas fetch | N/A |
| ROUTE-LANGPREFIX-001 | URLs via drupalSettings | No se anaden nuevas URLs en JS | N/A |

### 7.7 Directrices de Seguridad

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| SECRET-MGMT-001 | No secrets en config/sync | No hay secrets en este plan | N/A |
| AUDIT-SEC-002 | Rutas con datos tenant usan _permission | /my-settings/design usa 'edit own theme config' | N/A |
| ACCESS-STRICT-001 | Comparaciones ownership con (int)..===(int) | TenantThemeConfig access handler usa comparacion estricta | N/A |
| API-WHITELIST-001 | Endpoints con campos dinamicos filtran input | No hay nuevos API endpoints | N/A |

### 7.8 Directrices de Testing

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| KERNEL-TEST-DEPS-001 | $modules NO auto-resuelve dependencias | Test kernel listara TODOS los modulos | Fase 6 |
| KERNEL-TEST-001 | KernelTestBase SOLO si necesita DB/entities | Test de campo header_cta_url usa Kernel (necesita entity storage) | Fase 6 |
| MOCK-DYNPROP-001 | PHP 8.4 prohibe dynamic properties en mocks | Tests unitarios usan clases anonimas con typed properties | Fase 6 |
| MOCK-METHOD-001 | createMock() solo soporta metodos de la interface | Mocks usan ContentEntityInterface para hasField() | Fase 6 |
| TEST-CACHE-001 | Entity mocks implementan getCacheContexts/Tags/MaxAge | Incluido en mocks de test | Fase 6 |

### 7.9 Directrices de Documentacion

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| DOC-GUARD-001 | NUNCA sobreescribir master docs con Write | Este plan es documento NUEVO en docs/implementacion/ | N/A |
| COMMIT-SCOPE-001 | Docs separados de codigo | Master docs se actualizan en commit separado con prefijo `docs:` | Post-impl |

### 7.10 Directrices de GrapesJS / Page Builder

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| CANVAS-ARTICLE-001 | ContentArticle reutiliza engine GrapesJS via library dependency | No se modifica GrapesJS | N/A |
| ICON-EMOJI-001 | NO emojis Unicode en canvas_data | No se modifica canvas_data | N/A |
| ICON-CANVAS-INLINE-001 | SVG con hex explicito en stroke/fill | No se anaden nuevos iconos | N/A |
| Design Tokens GrapesJS | --gjs-primary-color = var(--ej-color-corporate) | Los CSS tokens de TenantThemeConfig se inyectan como :root, GrapesJS los hereda automaticamente | Fase 3 |

### 7.11 Directrices SCSS / Dart Sass

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| Dart Sass moderno | @use (NO @import), @use 'sass:color', color-mix() | No se crean nuevos archivos SCSS | N/A |
| SCSS-ENTRY-CONSOLIDATION-001 | No tener name.scss y _name.scss en mismo directorio | No se crean nuevos SCSS | N/A |
| Variables inyectables | Valores configurados desde UI de Drupal sin codigo | TenantThemeConfig::generateCssVariables() genera :root { --ej-* } desde campos configurados en UI (/my-settings/design). No hay hex hardcoded en SCSS | Continuo |
| npm run build | Compilacion desde web/themes/custom/ecosistema_jaraba_theme/ | Si se modifican SCSS, recompilar con `lando ssh -c "cd web/themes/custom/ecosistema_jaraba_theme && npm run build"` | Post-impl |

### 7.12 Directrices de Slide-Panel y Modales

| Directriz | Descripcion | Como se cumple | Fase |
|-----------|-------------|---------------|------|
| SLIDE-PANEL-RENDER-001 | Usar renderPlain() para forms en slide-panel | TenantThemeCustomizerForm es pagina completa, no slide-panel | N/A |
| FORM-CACHE-001 | NUNCA setCached(TRUE) incondicional | TenantThemeCustomizerForm no usa setCached() | N/A |

---

## 8. Configuracion

### 8.1 Parametros configurables desde UI de Drupal

#### Nivel 1 — Configuracion Global del Tema

**Ruta admin**: `/admin/appearance/settings/ecosistema_jaraba_theme`

| Parametro | Tipo | Default | Descripcion | Seccion UI |
|-----------|------|---------|-------------|-----------|
| `enable_header_cta` | boolean | FALSE | Mostrar boton CTA en header | Header Options > Boton CTA |
| `header_cta_text` | string | 'Empezar' | Texto del boton CTA | Header Options > Boton CTA |
| `header_cta_url` | string | '/user/register' | URL destino del boton CTA | Header Options > Boton CTA |
| `header_layout` | string | 'classic' | Variante de header | Header Options |
| `header_sticky` | boolean | TRUE | Header fijo al scroll | Header Options |
| `footer_layout` | string | 'standard' | Variante de footer | Footer Options |
| `footer_copyright` | string | '(c) [year] Jaraba...' | Texto copyright | Footer Options |
| `navigation_items` | text | 'Empleo\|/empleo...' | Items de navegacion (Texto\|URL) | Navigation |
| `footer_nav_col1_title` | string | 'Plataforma' | Titulo columna 1 footer | Footer Navigation |
| `footer_nav_col1_links` | text | 'Empleabilidad\|/empleo...' | Links columna 1 (Texto\|URL) | Footer Navigation |
| (etc. para col2, col3) | | | | |

#### Nivel 4 — Personalizacion del Tenant

**Ruta frontend**: `/my-settings/design` (requiere permiso 'edit own theme config')

| Parametro | Tipo | Default | Descripcion | Tab UI |
|-----------|------|---------|-------------|--------|
| `color_primary` | color | '#FF8C42' | Color primario de marca | Colores |
| `color_secondary` | color | '#00A9A5' | Color secundario | Colores |
| `color_accent` | color | '#233D63' | Color de acento | Colores |
| `font_headings` | select | 'outfit' | Fuente de titulos | Tipografia |
| `font_body` | select | 'inter' | Fuente de cuerpo | Tipografia |
| `header_variant` | visual_picker | 'classic' | Estilo de header | Encabezado |
| `header_cta_enabled` | checkbox | TRUE | Mostrar boton CTA | Encabezado |
| `header_cta_text` | textfield | 'Empezar' | Texto del CTA (max 32 chars) | Encabezado |
| `header_cta_url` | textfield | '/registro' | URL del CTA **[NUEVO]** | Encabezado |
| `hero_variant` | visual_picker | 'split' | Estilo de hero | Hero Section |
| `footer_variant` | select | 'standard' | Estilo de footer | Pie de Pagina |
| `social_*` | textfield | '' | URLs de redes sociales (5 campos) | Redes Sociales |
| `custom_css` | textarea | '' | CSS personalizado (Enterprise+) | Opciones Avanzadas |

#### Nivel 5 — Override de Meta-sitio

**Ruta admin**: `/admin/content/site-configs/{id}/edit`

| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| `header_cta_text` | string | '' | Override CTA texto (si no vacio, gana sobre Nivel 4) |
| `header_cta_url` | string | '' | Override CTA URL |
| `header_type` | list | 'classic' | Override header variant |
| `footer_type` | list | 'standard' | Override footer variant |
| `footer_copyright` | string | '' | Override copyright |

### 8.2 Variables CSS inyectables

Variables CSS Custom Properties generadas por TenantThemeConfig::generateCssVariables() e inyectadas en `<style id="jaraba-design-tokens">` dentro de `:root {}`:

| Variable CSS | Campo TenantThemeConfig | Fallback SCSS | Descripcion |
|-------------|------------------------|--------------|-------------|
| `--ej-color-primary` | `color_primary` | #FF8C42 | Color primario (naranja impulso) |
| `--ej-color-secondary` | `color_secondary` | #00A9A5 | Color secundario (verde innovacion) |
| `--ej-color-accent` | `color_accent` | #233D63 | Color de acento (azul corporativo) |
| `--ej-color-success` | `color_success` | #10B981 | Color de exito |
| `--ej-color-warning` | `color_warning` | #F59E0B | Color de advertencia |
| `--ej-color-error` | `color_error` | #EF4444 | Color de error |
| `--ej-color-bg-body` | `color_bg_body` | #F8FAFC | Fondo del body |
| `--ej-color-bg-surface` | `color_bg_surface` | #FFFFFF | Fondo de tarjetas/superficies |
| `--ej-color-text` | `color_text` | #334155 | Color de texto principal |
| `--ej-font-family-headings` | `font_headings` | 'Outfit', sans-serif | Fuente de titulos |
| `--ej-font-family-body` | `font_body` | 'Inter', sans-serif | Fuente de cuerpo |
| `--ej-font-size-base` | `font_size_base` | 16px | Tamano base de fuente |
| `--ej-border-radius` | `border_radius` | 8px | Radio de bordes |
| `--ej-card-border-radius` | `card_border_radius` | 12px | Radio de bordes de tarjetas |
| `--ej-button-border-radius` | `button_border_radius` | 8px | Radio de bordes de botones |

**Consumo en SCSS**: TODOS los archivos SCSS del proyecto usan estas variables con fallback:

```scss
// CORRECTO (CSS-VAR-ALL-COLORS-001):
.btn-primary {
  background-color: var(--ej-color-primary, #FF8C42);
  border-radius: var(--ej-button-border-radius, 8px);
}

// INCORRECTO (PROHIBIDO):
.btn-primary {
  background-color: #FF8C42;  // Hex hardcoded
}
```

### 8.3 SCSS y Dart Sass

**Compilacion**: Dart Sass moderno (`@use`, NO `@import`).

```bash
# Dentro del container de Docker/Lando:
lando ssh -c "cd web/themes/custom/ecosistema_jaraba_theme && npm run build"
```

**Verificacion post-compilacion** (SCSS-COMPILE-VERIFY-001):
```bash
lando ssh -c "stat -c '%Y %n' web/themes/custom/ecosistema_jaraba_theme/css/main.css web/themes/custom/ecosistema_jaraba_theme/scss/main.scss"
# El timestamp de main.css DEBE ser mayor que main.scss
```

**Regla SCSS-COMPILETIME-001**: Si algun parcial SCSS necesita usar `color.scale()`, `color.adjust()` o `color.change()`, la variable de entrada DEBE ser un hex estatico, NUNCA un `var()`. Para opacidad en runtime, usar `color-mix(in srgb, var(--ej-color-primary) 50%, transparent)`.

**En esta implementacion NO se crean ni modifican archivos SCSS**. Los CSS tokens se inyectan como `:root` variables inline, que son consumidas por los SCSS existentes via `var(--ej-*, fallback)`.

---

## 9. Templates Twig y Parciales

### 9.1 Inventario de parciales existentes

Antes de crear cualquier parcial nuevo, verificar si existe uno reutilizable:

| Parcial | Archivo | Proposito | Variables clave |
|---------|---------|-----------|----------------|
| `_header.html.twig` | partials/_header.html.twig | Dispatcher de layouts de header | `theme_settings`, `nav_items`, `site_name`, `logo` |
| `_header-classic.html.twig` | partials/_header-classic.html.twig | Layout clasico con megamenu | `ts.enable_header_cta`, `ts.header_cta_text`, `ts.header_cta_url` |
| `_header-centered.html.twig` | partials/_header-centered.html.twig | Layout centrado | Mismas variables CTA |
| `_header-hero.html.twig` | partials/_header-hero.html.twig | Layout transparente sobre hero | Mismas variables CTA |
| `_header-minimal.html.twig` | partials/_header-minimal.html.twig | Solo logo + hamburguesa | Sin CTA visible |
| `_header-split.html.twig` | partials/_header-split.html.twig | Logo centro, menus laterales | Sin CTA primario |
| `_footer.html.twig` | partials/_footer.html.twig | Footer con 3 layouts | `ts.footer_layout`, `ts.footer_copyright`, `ts.footer_nav_col*` |
| `_copilot-fab.html.twig` | partials/_copilot-fab.html.twig | Boton flotante del copiloto IA | `copilot_context` |
| `_command-bar.html.twig` | partials/_command-bar.html.twig | Barra de comandos (Cmd+K) | Datos via JS |
| `_avatar-nav.html.twig` | partials/_avatar-nav.html.twig | Navegacion contextual por rol | `avatar_nav.items` |
| `_language-switcher.html.twig` | partials/_language-switcher.html.twig | Selector de idioma | `available_languages`, `current_langcode` |
| `_bottom-nav.html.twig` | partials/_bottom-nav.html.twig | Navegacion inferior mobile | Items de navegacion |
| `_skeleton.html.twig` | partials/_skeleton.html.twig | Placeholder de carga | — |
| `_empty-state.html.twig` | partials/_empty-state.html.twig | Estado vacio | Titulo, descripcion, accion |

**En esta implementacion NO se crean nuevos parciales**. Los existentes ya cubren todos los layouts de header y footer. El cambio esta en el pipeline de datos (preprocess_page), no en los templates.

### 9.2 Variables Twig para parciales

Las variables Twig que reciben los parciales se inyectan desde `hook_preprocess_page()`. Con la cascada unificada (Fase 2), los valores ya llegan resueltos:

```twig
{# _header.html.twig — Variables disponibles tras cascada unificada #}

{% set ts = theme_settings|default({}) %}

{# CTA: Valor resuelto por cascada (Nivel 1 < Nivel 4 < Nivel 5) #}
{{ ts.enable_header_cta }}   {# true/false #}
{{ ts.header_cta_text }}      {# "Empezar" o texto del tenant #}
{{ ts.header_cta_url }}       {# "/registro" o URL del tenant #}

{# Header layout: Valor resuelto por cascada #}
{{ ts.header_layout }}        {# "classic", "minimal", etc. #}
{{ ts.header_sticky }}        {# true/false #}
{{ ts.header_megamenu }}      {# true solo en SaaS principal #}

{# Footer: Valor resuelto por cascada #}
{{ ts.footer_layout }}        {# "standard", "mega", "split" #}
{{ ts.footer_copyright }}     {# Texto con anyo resuelto #}
{{ ts.footer_nav_col1_title }} {# "Plataforma" o nombre del tenant #}
{{ ts.footer_nav_col1_links }} {# "Link1|/url1\nLink2|/url2" #}

{# Navegacion: Items parseados en _header.html.twig #}
{% for item in nav_items %}
  {{ item.text }}  {# "Inicio", "Servicios", etc. #}
  {{ item.url }}   {# "/", "/servicios", etc. #}
{% endfor %}
```

### 9.3 Reglas de Zero Region Pattern

Todas las paginas frontend del SaaS siguen el patron Zero Region:

1. **Template `page--{ruta}.html.twig`** con layout limpio (sin `page.content`, sin bloques heredados).
2. **`{{ clean_content }}`** en vez de `{{ page.content }}` — extrae solo `system_main_block`.
3. **`{{ clean_messages }}`** para mensajes — extrae `system_messages_block`.
4. **Body classes via `hook_preprocess_html()`** (NUNCA `attributes.addClass()` en template).
5. **Template suggestions via `hook_theme_suggestions_page_alter()`** y `$ecosistema_prefixes`.
6. **Controller devuelve solo** `['#type' => 'markup', '#markup' => '']` — variables via preprocess.
7. **drupalSettings via preprocess** (ZERO-REGION-003): `$variables['#attached']['drupalSettings']`.
8. **Layout full-width**, mobile-first, sin sidebar de admin para no-administradores.
9. **Acciones crear/editar/ver en slide-panel** (modal), usuario no abandona la pagina.
10. **El tenant NO tiene acceso al tema de administracion de Drupal** — solo ve el frontend limpio.

---

## 10. Verificacion

### 10.1 Tests automatizados

```bash
# Dentro del container de Docker:
lando ssh

# Tests unitarios de UnifiedThemeResolverService
cd /app
./vendor/bin/phpunit web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/UnifiedThemeResolverServiceTest.php

# Tests kernel de TenantThemeConfig campo header_cta_url
./vendor/bin/phpunit web/modules/custom/jaraba_theming/tests/src/Kernel/TenantThemeConfigFieldsTest.php

# Tests existentes para verificar no regresion
./vendor/bin/phpunit web/modules/custom/jaraba_theming/tests/
./vendor/bin/phpunit web/modules/custom/ecosistema_jaraba_core/tests/ --filter=Theme
```

### 10.2 Verificacion manual

| Verificacion | Como probar | Resultado esperado |
|-------------|-------------|-------------------|
| Campo header_cta_url se guarda | Ir a `/my-settings/design`, rellenar CTA URL, guardar | Sin error. Al recargar el form, el valor persiste |
| CTA del tenant se renderiza | Visitar homepage del meta-sitio | CTA muestra texto y URL del tenant, no "Andalucia +ei" |
| CTA consistente entre rutas | Visitar /blog, /login, /my-settings en el meta-sitio | Mismo CTA en todas las rutas |
| Anonimos ven colores del tenant | Abrir incognito en URL del meta-sitio | CSS tokens del tenant (no defaults plataforma) |
| SaaS principal no afectado | Visitar `jaraba-saas.lndo.site/es/` | CTA global, megamenu, colores plataforma |
| SiteConfig override funciona | Configurar CTA diferente en SiteConfig vs TenantThemeConfig | SiteConfig (Nivel 5) gana |

### 10.3 Checklist RUNTIME-VERIFY-001

- [ ] CSS compilado: timestamp de main.css > timestamp de main.scss
- [ ] Tablas DB: campo `header_cta_url` existe en tabla `tenant_theme_config`
- [ ] Rutas accesibles: `/my-settings/design` responde 200
- [ ] Servicio registrado: `ecosistema_jaraba_core.unified_theme_resolver` en container
- [ ] drupalSettings: `<style id="jaraba-design-tokens">` contiene variables `--ej-*` del tenant
- [ ] Body classes: `header--{variant}` y `hero--{variant}` correctas en `<body>`
- [ ] CTA renderizado: `<a class="btn-primary btn-primary--glow">` con texto del tenant
- [ ] Footer renderizado: copyright y columnas del tenant
- [ ] Mobile menu: items de navegacion del tenant (no megamenu SaaS)
- [ ] Logo: logo del tenant (si configurado en SiteConfig)

### 10.4 Checklist IMPLEMENTATION-CHECKLIST-001

#### Complitud
- [ ] UnifiedThemeResolverService registrado en services.yml Y consumido en hook_preprocess_page
- [ ] hook_update_10002 para campo header_cta_url
- [ ] Rutas existentes siguen funcionando (no regresion)
- [ ] SCSS no modificados (no recompilacion necesaria)

#### Integridad
- [ ] Tests existen: Unit para UnifiedThemeResolverService, Kernel para campo header_cta_url
- [ ] hook_update_N() con `installFieldStorageDefinition` para nuevo campo
- [ ] Verificar: `lando php scripts/validation/validate-entity-integrity.php`

#### Consistencia
- [ ] PREMIUM-FORMS-PATTERN-001 en TenantThemeConfigForm (ya cumple)
- [ ] CSS-VAR-ALL-COLORS-001 en SCSS (ya cumple, sin cambios)
- [ ] TENANT-001 en queries (loadByProperties filtra por tenant_id)
- [ ] OPTIONAL-CROSSMODULE-001 en services.yml (deps con @?)

#### Coherencia
- [ ] Este plan de implementacion creado en docs/implementacion/
- [ ] MEMORY.md actualizado con nuevas reglas (THEMING-UNIFY-001, SSOT-THEME-001)
- [ ] Master docs actualizados si nueva regla descubierta (en commit separado `docs:`)

#### Automatizacion
- [ ] `lando php scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_theming`
- [ ] `lando php scripts/validation/validate-service-consumers.php` (SERVICE-ORPHAN-001)
- [ ] `lando php scripts/validation/validate-optional-deps.php` (OPTIONAL-CROSSMODULE-001)
- [ ] `lando php scripts/validation/validate-circular-deps.php` (CONTAINER-DEPS-002)
- [ ] `lando php scripts/validation/validate-logger-injection.php` (LOGGER-INJECT-001)

---

## 11. Despliegue

### 11.1 Preparacion

```bash
# Dentro del container Lando:
lando drush updb -y          # Ejecuta hook_update_10002
lando drush cr               # Limpia caches
lando drush entity-updates   # Verifica que no hay cambios pendientes

# Verificar servicio:
lando drush debug:container ecosistema_jaraba_core.unified_theme_resolver
```

### 11.2 Proceso de despliegue

| Ambiente | URL | Proceso |
|----------|-----|---------|
| Desarrollo (Lando) | `https://jaraba-saas.lndo.site/` | `lando drush updb && lando drush cr` |
| Produccion (IONOS) | `https://plataformadeecosistemas.com/` | `drush updb && drush cr` via deploy.yml |

### 11.3 Rollback

En caso de problemas:

```bash
# Revertir campo header_cta_url (si falla hook_update_10002):
lando drush sql:query "ALTER TABLE tenant_theme_config DROP COLUMN header_cta_url"
lando drush cr

# Revertir servicio (si falla UnifiedThemeResolverService):
# El hook_preprocess_page tiene hasService() check, asi que si el servicio
# no esta disponible, fallback a valores globales (comportamiento anterior).
```

---

## 12. Troubleshooting

### Problema 1: CTA sigue mostrando valor global tras guardar en /my-settings/design

**Sintomas**:
- El formulario guarda sin error
- Al recargar la pagina frontend, el CTA muestra "Andalucia +ei" (global)

**Causa posible**:
- UnifiedThemeResolverService no registrado o con error de DI
- Cache no invalidada

**Solucion**:
```bash
lando drush cr
lando drush debug:container ecosistema_jaraba_core.unified_theme_resolver
# Si no aparece: verificar services.yml y que el modulo esta habilitado
```

### Problema 2: Anonimos no ven colores del tenant

**Sintomas**:
- Usuarios logueados ven colores correctos
- En incognito, colores son los de plataforma

**Causa posible**:
- MetaSiteResolverService no resuelve el hostname
- No hay Domain entity para el hostname

**Solucion**:
```bash
# Verificar que existe Domain entity para el hostname:
lando drush sql:query "SELECT id, hostname FROM config WHERE name LIKE 'domain.record.%'"

# Verificar que existe Tenant con ese domain:
lando drush sql:query "SELECT id, domain FROM tenant WHERE domain LIKE '%midominio%'"
```

### Problema 3: Error 500 en hook_preprocess_page tras deploy

**Sintomas**:
- Pagina en blanco o error 500
- Log: TypeError o ServiceNotFoundException

**Causa posible**:
- Servicio `ecosistema_jaraba_core.unified_theme_resolver` no encontrado
- Dependencia circular

**Solucion**:
```bash
lando drush cr
# El hook tiene \Drupal::hasService() check, asi que si el servicio
# falla, deberia degradar a comportamiento anterior.
# Si persiste, verificar logs:
lando drush watchdog:show --severity=error --count=20
```

### Problema 4: Campo header_cta_url no se crea en BD

**Sintomas**:
- hook_update_10002 ejecuta sin error pero campo no existe

**Causa posible**:
- Entity definition ya instalada sin el campo (cache de definicion)

**Solucion**:
```bash
lando drush entity-updates
# Si no detecta el cambio:
lando drush eval "\Drupal::entityDefinitionUpdateManager()->applyUpdates();"
```

---

## 13. Referencias

### Documentos del Proyecto
- [Arquitectura de Theming SaaS Master](../arquitectura/2026-02-05_arquitectura_theming_saas_master.md)
- [Especificacion GrapesJS para SaaS](../arquitectura/2026-02-05_especificacion_grapesjs_saas.md)
- [Zero Region Pattern](../tecnicos/aprendizajes/2026-02-02_page_builder_frontend_limpio_zero_region.md)
- [MetaSitio Remediacion Integral](./20260226-Plan_Implementacion_MetaSitio_Remediacion_Integral_v1.md)
- [Directrices del Proyecto (v115)](../00_DIRECTRICES_PROYECTO.md)
- [Arquitectura Master (v104)](../00_DOCUMENTO_MAESTRO_ARQUITECTURA.md)
- [Flujo de Trabajo (v68)](../00_FLUJO_TRABAJO_CLAUDE.md)

### Archivos Clave del Codigo
- `web/modules/custom/jaraba_theming/src/Entity/TenantThemeConfig.php` — Entity con campos de tema
- `web/modules/custom/jaraba_theming/src/Service/ThemeTokenService.php` — Genera CSS tokens
- `web/modules/custom/jaraba_theming/src/Form/TenantThemeCustomizerForm.php` — Form frontend /my-settings/design
- `web/modules/custom/jaraba_site_builder/src/Entity/SiteConfig.php` — Entity de meta-sitio
- `web/modules/custom/jaraba_site_builder/src/Service/MetaSiteResolverService.php` — Resuelve hostname -> tenant
- `web/modules/custom/ecosistema_jaraba_core/src/Service/TenantContextService.php` — Resuelve usuario -> tenant
- `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` — Hooks de preprocess
- `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header.html.twig` — Dispatcher de header
- `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header-classic.html.twig` — Layout clasico con CTA
- `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_footer.html.twig` — Footer con columnas

### Reglas Nuevas Introducidas
- **THEMING-UNIFY-001**: Cascada unificada de 5 niveles para tema multi-tenant
- **SSOT-THEME-001**: TenantThemeConfig = visual, SiteConfig = estructural
- **THEMING-RESOLVE-001**: Resolucion de tenant por hostname para anonimos (complementa TenantContextService)

---

## 14. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-03-06 | 1.0.0 | IA Asistente (Claude Opus 4.6) | Creacion inicial. Diagnostico de 6 gaps, arquitectura objetivo de cascada 5 niveles, 6 fases de implementacion, tablas de correspondencia y cumplimiento de directrices |

---

> **Nota**: Recuerda actualizar el indice general (`00_INDICE_GENERAL.md`) despues de implementar este plan. Commit de master docs separado con prefijo `docs:` (COMMIT-SCOPE-001).
