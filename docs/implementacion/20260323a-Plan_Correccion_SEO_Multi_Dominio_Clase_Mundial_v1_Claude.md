# Plan de Corrección SEO Multi-Dominio — Clase Mundial 10/10

**Documento:** 20260323a
**Versión:** 1.0
**Fecha:** 2026-03-23
**Autor:** Claude Opus 4.6 (1M context)
**Estado:** Aprobado para implementación
**Prioridad:** P0 — Impacto directo en indexación de Google
**Verticales afectadas:** Todas (transversal)
**Módulos involucrados:** ecosistema_jaraba_core, ecosistema_jaraba_theme, jaraba_page_builder, jaraba_site_builder

---

## Índice de Navegación (TOC)

1. [Contexto y Diagnóstico](#1-contexto-y-diagnóstico)
2. [Auditoría Detallada — Estado Actual vs Estado Objetivo](#2-auditoría-detallada)
3. [Arquitectura SEO Multi-Dominio Objetivo](#3-arquitectura-seo-multi-dominio-objetivo)
4. [Especificaciones de Implementación](#4-especificaciones-de-implementación)
   - 4.1 [FIX-01: hreflang /node → URLs canónicas](#41-fix-01-hreflang-node)
   - 4.2 [FIX-02: Meta tags diferenciados por metasitio](#42-fix-02-meta-tags-diferenciados)
   - 4.3 [FIX-03: Idiomas fantasma (en, pt-br)](#43-fix-03-idiomas-fantasma)
   - 4.4 [FIX-04: robots.txt dinámico por dominio](#44-fix-04-robotstxt-dinámico)
   - 4.5 [FIX-05: Sitemaps vacíos → poblados](#45-fix-05-sitemaps-vacíos)
   - 4.6 [FIX-06: Canonical cross-domain](#46-fix-06-canonical-cross-domain)
   - 4.7 [FIX-07: og:url absoluto + og:locale](#47-fix-07-ogurl-absoluto)
   - 4.8 [FIX-08: Redirección raíz / → /es](#48-fix-08-redirección-raíz)
5. [Tabla de Correspondencia — Directrices de Aplicación](#5-tabla-de-correspondencia)
6. [Orden de Implementación y Dependencias](#6-orden-de-implementación)
7. [Validadores y Safeguards](#7-validadores-y-safeguards)
8. [Checklist de Verificación Post-Implementación](#8-checklist-de-verificación)
9. [Impacto Esperado en Indexación](#9-impacto-esperado)
10. [Glosario](#10-glosario)

---

## 1. Contexto y Diagnóstico

### 1.1 Situación actual

Jaraba Impact Platform opera con **4 dominios de producción** configurados via Domain module:

| Dominio | Group ID | Variante Homepage | Propósito |
|---------|:--------:|:-----------------:|-----------|
| plataformadeecosistemas.com | — | generic | SaaS hub (dominio por defecto) |
| plataformadeecosistemas.es | 7 | pde | Corporativo PED |
| jarabaimpact.com | 6 | jarabaimpact | Franquicia Método Jaraba |
| pepejaraba.com | 5 | pepejaraba | Marca personal fundador |

La arquitectura multi-tenant ya diferencia el **contenido visual** de cada homepage via `homepage_variant` (HOMEPAGE-ELEVATION-001), pero la **capa SEO** (meta tags, hreflang, sitemap, canonical, robots.txt) no se ha adaptado al modelo multi-dominio. Todos los dominios comparten los mismos meta tags genéricos.

### 1.2 Problemas detectados y verificados contra producción

| # | Problema | Severidad | Verificado en prod |
|---|---------|:---------:|:-------------------:|
| 1 | hreflang apunta a `/es/node`, `/en/node`, `/pt-br/node` | P0 | SÍ — confirmado en jarabaimpact.com |
| 2 | Title idéntico en 4 dominios: "Impulsa tu ecosistema digital \| Jaraba" | P0 | SÍ — confirmado |
| 3 | Idiomas en/pt-br sin contenido real generan duplicados | P1 | SÍ — hreflang emitidos, contenido es español |
| 4 | robots.txt estático hardcodea `plataformadeecosistemas.com` en Sitemap | P1 | SÍ — archivo en web/robots.txt |
| 5 | sitemap-pages.xml y sitemap-articles.xml vacíos en todos los dominios | P1 | SÍ — `<urlset>` sin `<url>` |
| 6 | Sin canonical cross-domain (cada dominio se declara canonical a sí mismo) | P1 | SÍ — no hay `<link rel="canonical">` en jarabaimpact.com |
| 7 | og:url relativo (`/`) en homepage | P2 | SÍ — código en ecosistema_jaraba_theme.theme |
| 8 | URL raíz sin /es no redirige 301 a /es | P2 | Plausible — canonical apunta a /es |

### 1.3 Hallazgo DESCARTADO del informe original

> **"robots.txt de jarabaimpact.com TRADUCIDO al español"** — FALSO. Verificado en producción: sirve directivas estándar en inglés. Este hallazgo es incorrecto.

### 1.4 Causa raíz técnica

1. **`system.site.yml` → `front: /node`**: Drupal resuelve la homepage como ruta interna `/node`. `Url::fromRoute('<front>')` genera `/es/node` porque `/node` no tiene alias a `/`.

2. **`metatag.metatag_defaults.front.yml`** tiene title/description genéricos SIN tokens de dominio. Drupal no tiene tokens nativos para Domain module.

3. **`web/robots.txt` estático** se sirve antes que cualquier controller Drupal — el SitemapController dinámico nunca se ejecuta.

4. **SitemapController** busca entidades `page_content` y `content_article` publicadas, pero si no hay entidades para el dominio actual, devuelve XML vacío.

5. **Tres idiomas configurados** (es/en/pt-br) pero solo español tiene contenido. Drupal genera hreflang para los tres automáticamente.

---

## 2. Auditoría Detallada

### 2.1 Flujo actual de generación hreflang

```
html.html.twig
  └─ {% if not is_page_content %}
       └─ {% include '_hreflang-meta.html.twig' %}
            └─ language_links (de preprocess_html)
                 └─ Url::fromRoute($route_name, $params, ['language' => $lang, 'absolute' => TRUE])
                      └─ Para <front>: genera /es/node, /en/node, /pt-br/node
```

**Fuente del bug**: `ecosistema_jaraba_theme.theme` líneas 2660-2683. La función `Url::fromRoute()` con la ruta `<front>` y `system.site.page.front = /node` genera `/es/node` porque Drupal no tiene un alias de `/node` a `/`.

### 2.2 Flujo actual de meta tags homepage

```
metatag.metatag_defaults.front.yml
  └─ title: "Impulsa tu ecosistema digital | [site:name]"
  └─ description: "Plataforma SaaS que conecta talento..."
  └─ canonical_url: "[site:url]"
```

**Sin diferenciación por dominio.** El token `[site:name]` siempre resuelve a "Jaraba" (system.site.name).

### 2.3 Flujo actual de robots.txt

```
Nginx recibe GET /robots.txt
  └─ try_files /robots.txt → MATCH (fichero estático en web/)
       └─ Sirve web/robots.txt (hardcoded plataformadeecosistemas.com)
       └─ Controller dinámico NUNCA se ejecuta
```

### 2.4 Flujo actual de sitemaps

```
GET /sitemap.xml → jaraba_page_builder.SitemapController::index()
  └─ Referencia: sitemap-pages.xml, sitemap-articles.xml, sitemap-static.xml

GET /sitemap-pages.xml → SitemapController::pages()
  └─ EntityQuery page_content WHERE status=1
  └─ Si no hay page_content publicados → XML vacío

GET /sitemap-articles.xml → SitemapController::articles()
  └─ EntityQuery content_article WHERE status=1
  └─ Si no hay content_article publicados → XML vacío
```

---

## 3. Arquitectura SEO Multi-Dominio Objetivo

### 3.1 Principio rector

Cada metasitio (dominio) es un **sitio independiente a ojos de Google**:
- **Title, description y og:* únicos** por dominio
- **Canonical que apunta al propio dominio** (no cross-domain, porque el contenido ES diferente por homepage_variant)
- **hreflang solo para idiomas con contenido real** (solo `es` por ahora)
- **Sitemap propio** con URLs del dominio correspondiente
- **robots.txt dinámico** con referencia al sitemap del dominio actual

### 3.2 Diagrama de flujo objetivo

```
Request a jarabaimpact.com/
  │
  ├─ robots.txt → Controller dinámico → Sitemap: https://jarabaimpact.com/sitemap.xml
  │
  ├─ <head>
  │    ├─ <title>Franquicia Método Jaraba — Ecosistemas de Impacto | Jaraba Impact</title>
  │    ├─ <meta name="description" content="Únete a la red de franquicias...">
  │    ├─ <link rel="canonical" href="https://jarabaimpact.com/es">
  │    ├─ <link rel="alternate" hreflang="es" href="https://jarabaimpact.com/es">
  │    ├─ <link rel="alternate" hreflang="x-default" href="https://jarabaimpact.com/es">
  │    ├─ <meta property="og:url" content="https://jarabaimpact.com/es">
  │    ├─ <meta property="og:title" content="Franquicia Método Jaraba...">
  │    └─ <meta property="og:locale" content="es_ES">
  │
  └─ sitemap.xml → índice con sitemap-pages.xml, sitemap-static.xml
       └─ Todas las URLs con https://jarabaimpact.com/...
```

### 3.3 Modelo de datos para SEO por metasitio

La fuente de verdad para el contenido SEO de cada homepage será un **nuevo servicio** `MetaSiteSeoService` que centraliza:

| Campo | Fuente | Fallback |
|-------|--------|----------|
| title | Theme Settings > SEO tab > campo por dominio | metatag.metatag_defaults.front |
| description | Theme Settings > SEO tab > campo por dominio | metatag.metatag_defaults.front |
| og:title | Mismo que title | title |
| og:description | Mismo que description | description |
| og:image | SEO-OG-IMAGE-FALLBACK-001 cascade | og-image-dynamic.png |
| canonical | `$request->getSchemeAndHttpHost() . '/' . $langcode` | [site:url] |
| og:url | Mismo que canonical | canonical |
| schema_org_type | VerticalBrandConfig → Organization/Person | Organization |

---

## 4. Especificaciones de Implementación

### 4.1 FIX-01: hreflang `/node` → URLs canónicas {#41-fix-01-hreflang-node}

**Problema**: `Url::fromRoute('<front>')` genera `/es/node` porque `system.site.page.front = /node`.

**Solución**: En el bloque de generación hreflang de `preprocess_html` (líneas 2660-2683), detectar la ruta `<front>` y generar la URL como `$host . '/' . $langcode` en vez de usar `Url::fromRoute()`.

**Ficheros a modificar**:
- `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` (líneas 2660-2683)

**Lógica**:

```php
// DENTRO del foreach de language_links (línea 2668):
foreach ($language_manager->getLanguages() as $langcode => $language) {
  // FIX-01: La ruta <front> genera /es/node porque system.site.front = /node.
  // Para la homepage, construir URL limpia: https://dominio.com/es
  if ($route_name_hl === NULL || $route_name_hl === '<front>' || $route_name_hl === 'view.frontpage.page_1') {
    $hl_links[$langcode] = $host_hl . '/' . $langcode;
    continue;
  }
  // Resto del try-catch existente...
}
```

**También corregir** el mismo patrón en `preprocess_page` (líneas 2903-2929) que genera `$variables['language_links']` para el language switcher:

```php
// Línea 2919: misma detección para <front>
if ($route_name === NULL || $route_name === '<front>' || $route_name === 'view.frontpage.page_1') {
  $language_links[$langcode] = $host . '/' . $langcode;
  continue;
}
```

**Resultado esperado**:
- Antes: `<link rel="alternate" hreflang="es" href="https://jarabaimpact.com/es/node">`
- Después: `<link rel="alternate" hreflang="es" href="https://jarabaimpact.com/es">`

**Directrices aplicables**: HREFLANG-CONDITIONAL-001, ROUTE-LANGPREFIX-001

---

### 4.2 FIX-02: Meta tags diferenciados por metasitio {#42-fix-02-meta-tags-diferenciados}

**Problema**: Title y description idénticos en los 4 dominios.

**Solución**: Crear un mapa de SEO metadata por `homepage_variant` en el `hook_preprocess_html()` y sobreescribir los metatags de Drupal via `#attached/html_head`.

**Ficheros a modificar**:
- `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` — `hook_preprocess_html()` o creación de nuevo hook `hook_page_attachments_alter()`

**Mapa de contenido SEO por metasitio**:

| Variante | Title | Description |
|----------|-------|-------------|
| generic | Impulsa tu ecosistema digital \| Jaraba | Plataforma SaaS con 10 verticales, IA y multi-tenancy... |
| pde | Ecosistemas Digitales de Impacto \| PED S.L. | Plataforma de Ecosistemas Digitales — empleabilidad, emprendimiento, comercio y servicios profesionales con IA |
| jarabaimpact | Franquicia Método Jaraba — Ecosistemas de Impacto \| Jaraba Impact | Únete a la red de franquicias de impacto social. Replica ecosistemas digitales de empleo, emprendimiento y comercio local en tu territorio |
| pepejaraba | Jose Jaraba — Emprendedor Social e Innovador \| Pepe Jaraba | Fundador de Jaraba Impact Platform. Emprendimiento social, innovación territorial y ecosistemas digitales de impacto |

**Lógica**: Los textos DEBEN ser configurables desde Theme Settings (tab SEO o nueva tab "Metasitios") para cumplir la directriz de textos configurables desde UI. El código sirve como fallback.

**Implementación en Theme Settings**:
- Nuevos campos en `ecosistema_jaraba_theme.theme` en `theme_settings_form()`:
  - `metasite_seo_pde_title`, `metasite_seo_pde_description`
  - `metasite_seo_jarabaimpact_title`, `metasite_seo_jarabaimpact_description`
  - `metasite_seo_pepejaraba_title`, `metasite_seo_pepejaraba_description`

**Inyección**: En `hook_page_attachments_alter()`, detectar `homepage_variant` y sobreescribir los metatags del módulo metatag:

```php
function ecosistema_jaraba_theme_page_attachments_alter(array &$attachments) {
  $route = \Drupal::routeMatch()->getRouteName();
  if (!in_array($route, ['<front>', 'view.frontpage.page_1'])) {
    return;
  }
  // Resolver variante desde UnifiedThemeResolverService
  // Leer título/descripción desde theme_get_setting('metasite_seo_{variant}_title')
  // Sobreescribir html_head metatags
}
```

**Directrices aplicables**: NO-HARDCODE-PRICE-001 (principio de no hardcodear, configurar desde UI), ZERO-REGION-003

---

### 4.3 FIX-03: Idiomas fantasma (en, pt-br) {#43-fix-03-idiomas-fantasma}

**Problema**: Tres idiomas configurados (es/en/pt-br) pero solo español tiene contenido. Los hreflang emitidos para en/pt-br apuntan a contenido español, creando duplicados.

**Opciones**:

| Opción | Pros | Contras | Recomendación |
|--------|------|---------|:-------------:|
| A. Eliminar idiomas en/pt-br de Drupal | Limpio, sin duplicados | Pierde infraestructura i18n si se necesita en futuro | NO |
| B. Excluir en/pt-br de hreflang pero mantener configurados | Mantiene i18n para cuando haya traducciones | Requiere lógica condicional | **SÍ** |
| C. Crear contenido mínimo en en/pt-br | SEO completo | Mucho trabajo de traducción | Futuro |

**Solución (Opción B)**: Filtrar los idiomas emitidos en hreflang para excluir los que no tienen contenido real.

**Fichero a modificar**: `ecosistema_jaraba_theme.theme` — bloque hreflang en preprocess_html (líneas 2660-2683).

**Lógica**: Añadir un array de idiomas activos para SEO. Configurable desde Theme Settings para que se pueda activar en/pt-br cuando haya traducciones sin tocar código.

```php
// Idiomas activos para hreflang. Configurables desde Theme Settings > SEO.
$seo_active_languages = array_filter(
  explode(',', theme_get_setting('seo_active_languages') ?? 'es'),
  fn($l) => !empty(trim($l))
);

foreach ($language_manager->getLanguages() as $langcode => $language) {
  if (!in_array($langcode, $seo_active_languages)) {
    continue; // Excluir idiomas sin contenido real.
  }
  // ... generación de URL existente
}
```

**Resultado**: Solo se emite `<link rel="alternate" hreflang="es">` + `x-default`, eliminando en/pt-br hasta que haya contenido.

**Theme Settings**: Nuevo campo `seo_active_languages` (textfield, default: "es") en la tab SEO existente.

**Directrices aplicables**: HREFLANG-CONDITIONAL-001

---

### 4.4 FIX-04: robots.txt dinámico por dominio {#44-fix-04-robotstxt-dinámico}

**Problema**: `web/robots.txt` es un archivo estático con `Sitemap: https://plataformadeecosistemas.com/sitemap.xml` hardcodeado. Los otros 3 dominios sirven el mismo archivo, referenciando un sitemap del dominio equivocado.

**Solución**: Renombrar el fichero estático y dejar que el controller dinámico de `jaraba_page_builder` sirva el robots.txt con la URL del dominio actual.

**Pasos**:

1. **Renombrar** `web/robots.txt` → `web/robots.txt.original` (backup, no eliminar)

2. **Verificar/actualizar** el controller dinámico en `jaraba_page_builder/src/Controller/SitemapController.php`:

```php
public function robots(): Response {
  $host = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
  $content = $this->getBaseRobotsContent();
  $content .= "\n# Sitemaps\n";
  $content .= "Sitemap: {$host}/sitemap.xml\n";
  return new Response($content, 200, ['Content-Type' => 'text/plain']);
}
```

3. **Verificar** que la ruta en `jaraba_page_builder.routing.yml` esté configurada correctamente:

```yaml
jaraba_page_builder.robots_txt:
  path: '/robots.txt'
  defaults:
    _controller: '\Drupal\jaraba_page_builder\Controller\SitemapController::robots'
  requirements:
    _access: 'TRUE'
```

4. **Nginx**: Verificar que la config de Nginx NO tenga una regla `location = /robots.txt` que sirva el fichero directamente (bypaseando Drupal). Si existe, eliminarla.

**Directrices aplicables**: DOMAIN-ROUTE-CACHE-001 (cada dominio tiene respuesta diferente), VARY-HOST-001

---

### 4.5 FIX-05: Sitemaps vacíos → poblados {#45-fix-05-sitemaps-vacíos}

**Problema**: `sitemap-pages.xml` y `sitemap-articles.xml` están vacíos en todos los dominios.

**Diagnóstico necesario**: Verificar si existen entidades `page_content` y `content_article` publicadas en base de datos. Si no existen, el problema no es de código sino de contenido.

**Solución en dos capas**:

**Capa 1 — Incluir rutas estáticas del SaaS** (no dependen de entidades):

El `sitemap-static.xml` solo tiene 5 URLs (/, /about, /contact, /privacy, /terms). Debe incluir TODAS las páginas públicas:

```php
// Añadir a SitemapController::staticPages() o crear método expandido:
$static_routes = [
  '/' => 1.0,
  '/planes' => 0.9,          // Pricing hub
  '/planes/starter' => 0.8,
  '/planes/professional' => 0.8,
  '/planes/enterprise' => 0.8,
  '/demo' => 0.8,            // Demo showcase
  '/test-vertical' => 0.7,   // Quiz vertical
  '/ayuda' => 0.6,           // Centro de ayuda
  '/about' => 0.5,
  '/contact' => 0.5,
  '/privacy' => 0.3,
  '/terms' => 0.3,
];
// Verticales con landing pública:
foreach (['empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
          'serviciosconecta', 'jarabalex', 'formacion', 'andaluciamasei'] as $vert) {
  $static_routes['/' . $vert . '.html'] = 0.7;
}
```

**Capa 2 — Diagnóstico de entidades**:

Verificar en la base de datos:
```sql
SELECT COUNT(*) FROM page_content WHERE status = 1;
SELECT COUNT(*) FROM content_article_field_data WHERE status = 1;
```

Si existen entidades publicadas pero el sitemap está vacío, revisar el EntityQuery del controller (filtros de tenant, domain access, etc.).

**Capa 3 — URLs por dominio**:

El sitemap DEBE usar el hostname del request actual para generar URLs, NO hardcodear un dominio:

```php
$host = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
// URL: $host . '/es' . $path
```

**Directrices aplicables**: TENANT-001 (filtrar por tenant), DOMAIN-ROUTE-CACHE-001

---

### 4.6 FIX-06: Canonical cross-domain {#46-fix-06-canonical-cross-domain}

**Problema**: No hay `<link rel="canonical">` en algunas páginas (verificado en jarabaimpact.com homepage).

**Decisión arquitectónica**: Los 4 dominios sirven **contenido diferenciado** (via homepage_variant). NO son aliases — son metasitios independientes. Por tanto:

- **NO se necesita canonical cross-domain** (no apuntar todo a un dominio principal).
- **SÍ se necesita canonical intra-dominio** (cada página se auto-declara canonical con su propio dominio).

**Solución**: El módulo metatag ya genera canonical via `[current-page:url]`, pero puede no funcionar correctamente con Domain module. Verificar y asegurar que se genera en `hook_page_attachments_alter()`:

```php
// En ecosistema_jaraba_core.module o ecosistema_jaraba_theme_page_attachments_alter():
$request = \Drupal::request();
$canonical = $request->getSchemeAndHttpHost() . $request->getRequestUri();
// Limpiar query strings
$canonical = strtok($canonical, '?');
$attachments['#attached']['html_head'][] = [
  ['#tag' => 'link', '#attributes' => ['rel' => 'canonical', 'href' => $canonical]],
  'seo_canonical_url',
];
```

**Directrices aplicables**: ROUTE-LANGPREFIX-001

---

### 4.7 FIX-07: og:url absoluto + og:locale {#47-fix-07-ogurl-absoluto}

**Problema**: `og:url` es `"/"` en homepage (relativo, no válido para OG protocol).

**Solución**: En el mismo bloque de `hook_page_attachments_alter()` que genera los meta tags diferenciados (FIX-02), generar og:url absoluto:

```php
$og_url = $request->getSchemeAndHttpHost() . $request->getRequestUri();
$attachments['#attached']['html_head'][] = [
  ['#tag' => 'meta', '#attributes' => ['property' => 'og:url', 'content' => $og_url]],
  'seo_og_url',
];
$attachments['#attached']['html_head'][] = [
  ['#tag' => 'meta', '#attributes' => ['property' => 'og:locale', 'content' => 'es_ES']],
  'seo_og_locale',
];
```

**Directrices aplicables**: SEO-OG-IMAGE-FALLBACK-001

---

### 4.8 FIX-08: Redirección raíz / → /es {#48-fix-08-redirección-raíz}

**Problema**: Acceder a `https://dominio.com/` no redirige 301 a `https://dominio.com/es`, generando contenido duplicado.

**Solución**: Drupal ya debería hacer esta redirección via language negotiation (path prefix). Si no lo hace, es porque el language negotiation está configurado para aceptar la raíz como idioma por defecto.

**Verificación**: Comprobar `config/sync/language.negotiation.yml`:
- Si `prefixes.es: es`, la raíz `/` debería redirigir a `/es`
- Si la configuración de `redirect` está desactivada en language negotiation, activarla

**Alternativa Nginx** (más eficiente):

```nginx
# En el server block de producción:
location = / {
  return 301 /es;
}
```

**Directrices aplicables**: ROUTE-LANGPREFIX-001

---

## 5. Tabla de Correspondencia — Directrices de Aplicación

| Directriz | FIX | Cómo se cumple |
|-----------|:---:|----------------|
| DOMAIN-ROUTE-CACHE-001 | 04, 05 | robots.txt y sitemap dinámicos respetan el hostname del request |
| VARY-HOST-001 | 04, 05 | SecurityHeadersSubscriber ya emite Vary:Host — CDN sirve respuesta correcta por dominio |
| HREFLANG-CONDITIONAL-001 | 01, 03 | hreflang solo para idiomas con contenido real, skip en page_content routes |
| ROUTE-LANGPREFIX-001 | 01, 06, 08 | URLs con /es prefix, NUNCA hardcoded paths |
| SEO-OG-IMAGE-FALLBACK-001 | 07 | og:url y og:image con URLs absolutas |
| NO-HARDCODE-PRICE-001 | 02 | Textos SEO configurables desde Theme Settings, no hardcoded |
| TENANT-001 | 05 | Sitemaps filtran por dominio/tenant |
| NAP-NORMALIZATION-001 | 06 | Canonical usa el dominio real del request |
| ZERO-REGION-003 | 02, 07 | Meta tags inyectados via #attached/html_head en preprocess/page_attachments |
| TWIG-INCLUDE-ONLY-001 | 01 | _hreflang-meta.html.twig ya usa `only` keyword |
| CSS-VAR-ALL-COLORS-001 | — | Sin cambios CSS |
| ICON-CONVENTION-001 | — | Sin cambios de iconos |
| SCSS-COMPILE-VERIFY-001 | — | Sin cambios SCSS |
| PREMIUM-FORMS-PATTERN-001 | — | Sin formularios nuevos |

---

## 6. Orden de Implementación y Dependencias

```
FIX-01 (hreflang /node)     ← Sin dependencias. PRIMERO.
  │
  ├─ FIX-03 (idiomas fantasma) ← Depende de FIX-01 (misma sección de código)
  │
  ├─ FIX-02 (meta tags)        ← Independiente. Puede ser paralelo.
  │
  ├─ FIX-07 (og:url)           ← Implementar junto con FIX-02
  │
  └─ FIX-06 (canonical)        ← Implementar junto con FIX-02/07

FIX-04 (robots.txt)           ← Independiente
  │
  └─ FIX-05 (sitemaps)         ← Verificar tras FIX-04

FIX-08 (redirect / → /es)     ← Independiente. Config Nginx.
```

**Estimación de ficheros modificados**: 4-6 ficheros
**Commits recomendados**: 2 (SEO meta tags + robots/sitemap)

---

## 7. Validadores y Safeguards

### 7.1 Nuevo validador: validate-seo-multi-domain.php

```
CHECK 1: hreflang NO contiene /node en ninguna URL
CHECK 2: Title homepage diferente por homepage_variant
CHECK 3: Canonical tag presente en homepage de cada dominio
CHECK 4: og:url es absoluto (empieza con https://)
CHECK 5: og:locale es es_ES (no vacío)
CHECK 6: hreflang solo emite idiomas de seo_active_languages
CHECK 7: robots.txt no hardcodea dominio en Sitemap
CHECK 8: sitemap-static.xml tiene >= 10 URLs
CHECK 9: sitemap-pages.xml no está vacío (si hay page_content publicados)
CHECK 10: No existe fichero estático web/robots.txt (debe ser dinámico)
```

### 7.2 Pre-commit hook

Añadir validación a lint-staged para `*.theme` files:

```json
"web/themes/custom/**/*.theme": [
  "php scripts/validation/validate-seo-multi-domain.php --fast"
]
```

### 7.3 hook_requirements (runtime)

Añadir check en `ecosistema_jaraba_core.install`:

```php
function ecosistema_jaraba_core_requirements($phase) {
  if ($phase !== 'runtime') return [];
  // CHECK: system.site.page.front no debe ser /node si hay alias
  // CHECK: robots.txt estático no debe existir
  // CHECK: idiomas configurados vs seo_active_languages
}
```

---

## 8. Checklist de Verificación Post-Implementación

### RUNTIME-VERIFY-001 adaptado para SEO

- [ ] **L1 (PHP)**: preprocess_html genera language_links SIN `/node`
- [ ] **L2 (Twig)**: _hreflang-meta.html.twig emite solo idiomas activos
- [ ] **L3 (HTML)**: `<head>` contiene `<link rel="alternate" hreflang="es" href="https://dominio.com/es">`
- [ ] **L4 (HTML)**: `<title>` es diferente en cada dominio
- [ ] **L5 (HTML)**: `<link rel="canonical">` presente y correcto
- [ ] **L6 (HTML)**: `<meta property="og:url">` es URL absoluta
- [ ] **L7 (HTTP)**: `/robots.txt` contiene `Sitemap: https://{dominio_actual}/sitemap.xml`
- [ ] **L8 (XML)**: `/sitemap-static.xml` tiene >= 10 URLs
- [ ] **L9 (Google)**: Rich Results Test sin errores
- [ ] **L10 (Google)**: Search Console no reporta errores de hreflang

### Herramientas de verificación

```bash
# Verificar hreflang en producción:
curl -s https://jarabaimpact.com/ | grep -i hreflang

# Verificar robots.txt por dominio:
curl -s https://jarabaimpact.com/robots.txt | grep Sitemap
curl -s https://pepejaraba.com/robots.txt | grep Sitemap

# Verificar sitemap no vacío:
curl -s https://jarabaimpact.com/sitemap-static.xml | grep '<url>'

# Verificar canonical:
curl -s https://jarabaimpact.com/ | grep canonical

# Verificar og:url:
curl -s https://jarabaimpact.com/ | grep og:url
```

---

## 9. Impacto Esperado en Indexación

| Métrica | Antes | Después |
|---------|-------|---------|
| URLs con hreflang correcto | 0% | 100% |
| Dominios con title diferenciado | 0/4 | 4/4 |
| Sitemaps con contenido | 1/3 (solo static) | 3/3 |
| robots.txt correcto por dominio | 1/4 | 4/4 |
| Canonical presente en homepage | ~0/4 | 4/4 |
| Idiomas fantasma en hreflang | 2 (en, pt-br) | 0 |
| Contenido duplicado inter-dominio | 100% (mismo title) | 0% |

**Resultado esperado**: Eliminación de los warnings de Search Console sobre hreflang, mejora de la cobertura de indexación, y diferenciación de autoridad entre los 4 dominios.

---

## 10. Glosario

| Sigla | Significado |
|-------|-------------|
| SEO | Search Engine Optimization — optimización para motores de búsqueda |
| hreflang | Atributo HTML que indica idioma y región de una página alternativa |
| OG | Open Graph — protocolo de meta tags para redes sociales (Facebook, LinkedIn) |
| CDN | Content Delivery Network — red de distribución de contenido |
| SERP | Search Engine Results Page — página de resultados de búsqueda |
| NAP | Name, Address, Phone — datos de contacto normalizados para SEO local |
| JSON-LD | JavaScript Object Notation for Linked Data — formato de datos estructurados |
| CSP | Content Security Policy — política de seguridad de contenido |
| HSTS | HTTP Strict Transport Security — fuerza HTTPS en navegadores |
| TOC | Table of Contents — índice de navegación |
| SSOT | Single Source of Truth — fuente única de verdad |
| PED | Plataforma de Ecosistemas Digitales S.L. — razón social de la empresa |
