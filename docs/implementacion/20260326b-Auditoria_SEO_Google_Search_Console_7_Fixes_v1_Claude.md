# Auditoria SEO Google Search Console — 7 Fixes Clase Mundial

**Version:** 1.0.0
**Fecha:** 2026-03-26
**Autor:** Claude Opus 4.6
**Roles:** Arquitecto SaaS Senior, Ingeniero SEO/GEO Senior, Ingeniero Drupal Senior
**Estado:** COMPLETADO
**Commit:** b7482c792
**Modulo principal:** `jaraba_page_builder`
**Modulos afectados:** `jaraba_page_builder`, `ecosistema_jaraba_theme`, `jaraba_insights_hub`

---

## Indice de Navegacion (TOC)

1. [Objetivos y Alcance](#1-objetivos-y-alcance)
2. [Diagnostico Google Search Console](#2-diagnostico-google-search-console)
3. [Root Causes Identificados](#3-root-causes-identificados)
4. [Fix 1: SeoRedirectSubscriber — Redirect /node a Homepage](#4-fix-1-seoredirectsubscriber--redirect-node-a-homepage)
5. [Fix 2+3: Canonical Homepage Limpia + Cross-Language](#5-fix-23-canonical-homepage-limpia--cross-language)
6. [Fix 4: Sitemap .html Bug en URLs de Verticales](#6-fix-4-sitemap-html-bug-en-urls-de-verticales)
7. [Fix 5: robots.txt con Disallows por Prefijo de Idioma](#7-fix-5-robotstxt-con-disallows-por-prefijo-de-idioma)
8. [Fix 6: HreflangService Filtro por seo_active_languages](#8-fix-6-hreflangservice-filtro-por-seo_active_languages)
9. [Fix 7: Validador SEO Multi-Dominio 17 Checks](#9-fix-7-validador-seo-multi-dominio-17-checks)
10. [Verificacion Produccion (RUNTIME-VERIFY-001)](#10-verificacion-produccion-runtime-verify-001)
11. [Tabla de Cumplimiento de Directrices](#11-tabla-de-cumplimiento-de-directrices)
12. [Glosario](#12-glosario)

---

## 1. Objetivos y Alcance

### Objetivo

Resolver los 7 problemas reportados por Google Search Console para los dominios de produccion del Ecosistema Jaraba, con especial foco en `jarabaimpact.com`, aplicando correcciones arquitectonicas que benefician a los 4 dominios de produccion.

### Alcance

| Dimension | Incluido | Excluido |
|-----------|----------|----------|
| Dominios | 4 produccion (plataformadeecosistemas.com, jarabaimpact.com, pepejaraba.com, plataformadeecosistemas.es) | Dominios dev (*.lndo.site, *.jaraba.io) |
| Problemas GSC | Canonical mismatch, crawled not indexed, robots.txt, noindex, soft 404 | Core Web Vitals, mobile usability |
| Idiomas | es (activo), en/pt-br (inactivos con canonical cross-language) | Nuevos idiomas futuros |
| Automatizacion | Fixes inmediatos | Notificacion automatica a Google (plan separado: 20260326c) |

### Dominios de Produccion

| Dominio | Group ID | Variante | Proposito |
|---------|:--------:|:--------:|-----------|
| plataformadeecosistemas.com | — | generic | SaaS hub (default) |
| plataformadeecosistemas.es | 7 | pde | Corporativo PED S.L. |
| jarabaimpact.com | 6 | jarabaimpact | Franquicia Metodo Jaraba |
| pepejaraba.com | 5 | pepejaraba | Marca personal fundador |

---

## 2. Diagnostico Google Search Console

### Correo 1: Canonical Mismatch + Crawled Not Indexed

Google Search Console reporto el 2026-03-26 los siguientes problemas para `jarabaimpact.com`:

| URL Afectada | Problema GSC | Ultimo Rastreo |
|-------------|--------------|:--------------:|
| `https://jarabaimpact.com/es/node` | Duplicada: Google eligio canonica diferente | 22 mar 2026 |
| `https://jarabaimpact.com/pt-br/serviciosconecta` | Duplicada: Google eligio canonica diferente | 23 mar 2026 |
| `https://jarabaimpact.com/es/serviciosconecta` | Duplicada: Google eligio canonica diferente | 23 mar 2026 |
| `https://jarabaimpact.com/es/comercioconecta/auditoria-seo` | Rastreada: actualmente sin indexar | 23 mar 2026 |

### Correo 2: Problemas Adicionales

| Problema | Severidad | Analisis |
|----------|:---------:|----------|
| Bloqueada por robots.txt | Media | `/node/` bloqueada pero `/es/node/` no (falta prefijo idioma) |
| Excluida por etiqueta noindex | Informativo | Correcto: metatag.metatag_defaults.404.yml tiene `robots: 'sin indice'` |
| Soft 404 | Informativo | Paginas con contenido thin (lead magnets tipo formulario) |

---

## 3. Root Causes Identificados

### RC-1: /es/node Canonical Self-Referencing

**Causa**: `system.site.front = /node`. En `ecosistema_jaraba_theme_page_attachments_alter()`, la canonical se genera con `$request->getRequestUri()` que devuelve `/es/node`. Google detecta que `/es/node` y `/es` son la misma pagina y elige `/es` como canonica, creando un "canonical mismatch".

**Ubicacion del bug**: `ecosistema_jaraba_theme.theme` linea 5532 (pre-fix).

**Agravante**: No existia ningun redirect de `/es/node` a `/es`, asi que Google rastreaba ambas URLs indefinidamente.

### RC-2: pt-br sin Contenido Real

**Causa**: `seo_active_languages = 'es'` en theme settings. Los idiomas `en` y `pt-br` estan configurados en Drupal pero no tienen contenido real. Las URLs `/pt-br/*` son accesibles y sirven contenido en espanol con prefijo pt-br, generando duplicados.

**Doble problema**:
1. La canonical en paginas pt-br se auto-referenciaba (apuntaba a la propia URL pt-br).
2. `HreflangService` emitia `<link rel="alternate" hreflang="pt-br">` para page_content con traducciones, dirigiendo a Google a rastrear URLs pt-br.

**Ubicacion**: `ecosistema_jaraba_theme.theme` linea 5532 + `HreflangService.php` linea 86.

### RC-3: Sitemap con URLs .html que 404

**Causa**: `SitemapController::staticPages()` hardcodeaba extension `.html` en las 8 URLs de landings verticales (`/es/serviciosconecta.html`), pero las rutas en `ecosistema_jaraba_core.routing.yml` estan definidas sin extension (`/serviciosconecta`). Las URLs .html del sitemap devolvian 404.

**Impacto**: Google recibia en el sitemap URLs inexistentes, desperdiciando crawl budget y generando senales negativas. Ademas, Google descubria las URLs reales (sin .html) via enlaces internos, creando duplicidad canonical.

**Ubicacion**: `SitemapController.php` lineas 676-683 (pre-fix).

### RC-4: robots.txt sin Prefijos de Idioma

**Causa**: `Disallow: /node/` bloquea `/node/*` pero NO `/es/node/*`, `/en/node/*` ni `/pt-br/node/*`. Drupal usa prefijos de idioma en las URLs, por lo que Google navegaba las versiones con prefijo sin restriccion.

**Ubicacion**: `SitemapController::robots()` lineas 62-75 (pre-fix).

---

## 4. Fix 1: SeoRedirectSubscriber — Redirect /node a Homepage

### Descripcion

Nuevo `EventSubscriber` en `KernelEvents::REQUEST` con prioridad 290 que intercepta peticiones al path del front page (`/node` por defecto) con cualquier prefijo de idioma y emite un 301 redirect a la homepage limpia `/{langcode}`.

### Archivo Creado

**`web/modules/custom/jaraba_page_builder/src/EventSubscriber/SeoRedirectSubscriber.php`**

| Aspecto | Detalle |
|---------|---------|
| Clase | `SeoRedirectSubscriber implements EventSubscriberInterface` |
| Evento | `KernelEvents::REQUEST` prioridad 290 |
| DI | `ConfigFactoryInterface`, `LanguageManagerInterface` |
| Logica | Lee `system.site.page.front` dinamicamente (no hardcodea `/node`) |
| Match | Exacto (`===`) — `/es/node` redirige, `/es/node/123` NO |
| Redirect | 301 permanente con query string preservado |
| Patron | Sigue `LegacyApiRedirectSubscriber` (mismo modulo, misma estructura) |

### Servicio Registrado

En `jaraba_page_builder.services.yml`:
```yaml
jaraba_page_builder.seo_redirect:
  class: Drupal\jaraba_page_builder\EventSubscriber\SeoRedirectSubscriber
  arguments: ['@config.factory', '@language_manager']
  tags: [{ name: event_subscriber }]
```

### Regla Nueva: SEO-REDIRECT-NODE-001

> El path del front page de Drupal (`system.site.page.front`, normalmente `/node`) DEBE redirigir 301 a la homepage limpia `/{langcode}`. Sin este redirect, Google rastrea ambas URLs y genera canonical mismatch.

---

## 5. Fix 2+3: Canonical Homepage Limpia + Cross-Language

### Descripcion

Modificacion de `ecosistema_jaraba_theme_page_attachments_alter()` para generar canonical URLs correctas en dos escenarios:

1. **Homepage (Fix 3)**: Cuando `$is_front === TRUE`, la canonical es `$host . '/' . $langcode` en lugar de `$host . $request->getRequestUri()` (que devolveria `/es/node`).

2. **Idioma inactivo (Fix 2)**: Cuando el idioma actual NO esta en `seo_active_languages`, la canonical apunta a la version en el idioma activo default (es). Ejemplo: `/pt-br/serviciosconecta` genera canonical `https://jarabaimpact.com/es/serviciosconecta`.

### Archivo Modificado

**`web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme`** (bloque `page_attachments_alter`, ~linea 5531)

### Logica Implementada

```
SI es_front_page:
  canonical = $host / $langcode_activo
SINO:
  canonical = $host + requestUri (sin query string)
  SI langcode NO en seo_active_languages:
    Reemplazar prefijo idioma por idioma activo default (es)
```

La sustitucion de prefijo usa `preg_replace` con anclaje `^` al inicio de la URL para evitar false matches en segmentos internos.

### Regla Nueva: SEO-CANONICAL-CLEAN-001

> La canonical de la homepage SIEMPRE es `$host/$langcode` (sin `/node`). Para idiomas no activos (`seo_active_languages`), la canonical apunta al idioma activo default, consolidando senales SEO en la version con contenido real.

---

## 6. Fix 4: Sitemap .html Bug en URLs de Verticales

### Descripcion

Eliminacion de la extension `.html` de las 8 URLs de landings verticales en `SitemapController::staticPages()`. Las rutas en `ecosistema_jaraba_core.routing.yml` estan definidas sin extension.

### Archivo Modificado

**`web/modules/custom/jaraba_page_builder/src/Controller/SitemapController.php`** metodo `staticPages()`, lineas 675-683.

### URLs Corregidas

| Antes (404) | Despues (200) |
|-------------|---------------|
| `/es/empleabilidad.html` | `/es/empleabilidad` |
| `/es/emprendimiento.html` | `/es/emprendimiento` |
| `/es/comercioconecta.html` | `/es/comercioconecta` |
| `/es/agroconecta.html` | `/es/agroconecta` |
| `/es/serviciosconecta.html` | `/es/serviciosconecta` |
| `/es/jarabalex.html` | `/es/jarabalex` |
| `/es/formacion.html` | `/es/formacion` |
| `/es/andaluciamasei.html` | `/es/andaluciamasei` |

---

## 7. Fix 5: robots.txt con Disallows por Prefijo de Idioma

### Descripcion

Generacion dinamica de disallows con prefijo de idioma en `SitemapController::robots()`. Para cada idioma configurado en Drupal (`es`, `en`, `pt-br`), se generan disallows adicionales para las rutas de sistema bloqueadas.

### Archivo Modificado

**`web/modules/custom/jaraba_page_builder/src/Controller/SitemapController.php`** metodo `robots()`.

### Disallows Generados por Idioma

Para cada langcode (`es`, `en`, `pt-br`):
- `Disallow: /{lang}/admin/`
- `Disallow: /{lang}/node/`
- `Disallow: /{lang}/node`
- `Disallow: /{lang}/user/register`
- `Disallow: /{lang}/user/password`
- `Disallow: /{lang}/user/login`
- `Disallow: /{lang}/user/logout`
- `Disallow: /{lang}/search/`
- `Disallow: /{lang}/batch`
- `Disallow: /{lang}/filter/tips`
- `Disallow: /{lang}/node/add/`

Tambien se anadio `Disallow: /node` (sin trailing slash) para bloquear el path exacto `/node`.

### Regla Nueva: SEO-ROBOTS-LANGPREFIX-001

> El robots.txt DEBE incluir disallows con prefijo de idioma para CADA idioma configurado en Drupal. Sin ellos, Google puede rastrear versiones con prefijo de rutas bloqueadas (`/es/admin/`, `/es/node/`).

---

## 8. Fix 6: HreflangService Filtro por seo_active_languages

### Descripcion

Modificacion de `HreflangService` para filtrar las traducciones emitidas en hreflang tags por `seo_active_languages` (theme setting). Antes del fix, el servicio emitia hreflang para TODOS los idiomas con traduccion, incluyendo pt-br y en que no tienen contenido real.

### Archivo Modificado

**`web/modules/custom/jaraba_page_builder/src/Service/HreflangService.php`**

### Cambios

1. **Nuevo argumento DI**: `ConfigFactoryInterface $config_factory` (4o parametro del constructor).
2. **Nuevo metodo**: `getActiveLanguages(): string[]` — lee `seo_active_languages` de theme settings.
3. **Filtro en `generateHreflangTags()`**: Solo emite hreflang para idiomas en la lista activa.
4. **Filtro en `generateOgLocaleTags()`**: Solo emite `og:locale:alternate` para idiomas activos.
5. **x-default condicional**: Solo se emite si el idioma default esta en la lista activa.
6. **catch(\Throwable)**: Corregido de `catch(\Exception)` per UPDATE-HOOK-CATCH-001.

### Servicio Actualizado

En `jaraba_page_builder.services.yml`:
```yaml
jaraba_page_builder.hreflang:
  arguments: ['@entity_type.manager', '@language_manager', '@current_route_match', '@config.factory']
```

---

## 9. Fix 7: Validador SEO Multi-Dominio 17 Checks

### Descripcion

Extension de `scripts/validation/validate-seo-multi-domain.php` de 10 a 17 checks, cubriendo todos los fixes implementados.

### Checks Nuevos (11-17)

| Check | Descripcion | Metodo Validacion |
|:-----:|-------------|-------------------|
| 11 | SeoRedirectSubscriber existe y registrado | `file_exists()` + grep services.yml |
| 12 | Subscriber redirige front page dinamicamente | grep `system.site` + `page.front` |
| 13 | Canonical cross-language implementado | grep `seo_active_langs` + `defaultActiveLang` |
| 14 | Canonical front page sin /node | grep `SEO-CANONICAL-CLEAN-001` |
| 15 | Sitemap sin .html en URLs verticales | Extrae seccion `$staticPages`, verifica 0 `.html` en lineas `path` |
| 16 | robots.txt genera disallows con prefijo idioma | grep `SEO-ROBOTS-LANGPREFIX-001` |
| 17 | HreflangService filtra por seo_active_languages | grep `getActiveLanguages` |

### Resultado

```
Resultado: 17/17 checks OK
ALL CHECKS PASSED
```

---

## 10. Verificacion Produccion (RUNTIME-VERIFY-001)

Verificaciones ejecutadas contra los servidores de produccion tras deploy:

| # | Verificacion | Comando | Resultado |
|:-:|-------------|---------|:---------:|
| 1 | Redirect /es/node | `curl -sI jarabaimpact.com/es/node` | 301 → `/es` |
| 2 | Canonical cross-language | `curl -s jarabaimpact.com/pt-br/serviciosconecta \| grep canonical` | `href=".../es/serviciosconecta"` |
| 3 | Sitemap sin .html | `curl -s jarabaimpact.com/sitemap-static.xml \| grep -c '.html'` | `0` |
| 4 | robots.txt langprefix | `curl -s jarabaimpact.com/robots.txt \| grep '/es/node/'` | Presente |
| 5 | PHPStan L6 | `phpstan analyse --level 6` (3 archivos) | 0 errores |
| 6 | PHPCS Drupal | `phpcs --standard=Drupal,DrupalPractice` | 0 errores |
| 7 | PHANTOM-ARG-001 | `validate-phantom-args.php` | 1002 servicios, 0 violaciones |
| 8 | validate-all.sh --fast | Suite de validacion rapida | 12/12 passed |
| 9 | validate-seo-multi-domain.php | 17 checks SEO | 17/17 OK |
| 10 | Revision independiente (reviewer agent) | Revision de codigo completa | APROBADO, 0 bloqueantes |

---

## 11. Tabla de Cumplimiento de Directrices

| Directriz | Descripcion | Cumplimiento |
|-----------|-------------|:------------:|
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute(), nunca hardcoded | N/A (redirect usa path directo, no genera URLs) |
| PHANTOM-ARG-001 | Args services.yml coinciden con constructor | SeoRedirectSubscriber: 2 args, HreflangService: 4 args |
| OPTIONAL-PARAM-ORDER-001 | Params opcionales al final | Sin params opcionales en constructores nuevos |
| CONTROLLER-READONLY-001 | No readonly en propiedades heredadas | SeoRedirectSubscriber no extiende ControllerBase |
| UPDATE-HOOK-CATCH-001 | catch(\Throwable), nunca catch(\Exception) | HreflangService corregido a \Throwable |
| SECRET-MGMT-001 | Sin secrets en config/sync | Sin secrets en ningun archivo |
| CSS-VAR-ALL-COLORS-001 | Colores via var(--ej-*) | N/A (sin cambios CSS) |
| TENANT-001 | Queries filtradas por tenant | N/A (sin queries DB directas) |
| SEO-HREFLANG-ACTIVE-001 | Hreflang solo idiomas activos | HreflangService + theme preprocess filtran por seo_active_languages |
| SEO-HREFLANG-FRONT-001 | Hreflang sin /node en URLs | SeoRedirectSubscriber + canonical limpia |
| SEO-METASITE-001 | Meta tags diferenciados por metasitio | Canonical respeta metasitio via getSchemeAndHttpHost() |
| IMPLEMENTATION-CHECKLIST-001 | Verificacion post-implementacion | 10 verificaciones ejecutadas (seccion 10) |
| DOC-GLOSSARY-001 | Glosario en documentos extensos | Seccion 12 |

---

## 12. Glosario

| Termino | Definicion | Contexto |
|---------|-----------|----------|
| **GSC** | Google Search Console | Herramienta de Google para monitorizar indexacion |
| **Canonical** | URL canonica que Google debe indexar | `<link rel="canonical" href="...">` en `<head>` |
| **Hreflang** | Tag HTML que indica versiones en otros idiomas | `<link rel="alternate" hreflang="es" href="...">` |
| **Crawl budget** | Numero de paginas que Google rastrea por sesion | Recurso limitado; URLs 404 lo desperdician |
| **Sitemap** | Archivo XML que lista URLs para rastreo | `/sitemap.xml`, `/sitemap-static.xml` |
| **robots.txt** | Archivo que indica a crawlers que rutas evitar | `Disallow: /admin/` |
| **301 redirect** | Redireccion permanente | Senala a Google que la URL ha cambiado definitivamente |
| **Cross-language canonical** | Canonical que apunta a version en otro idioma | `/pt-br/*` canonical → `/es/*` |
| **seo_active_languages** | Theme setting con idiomas que tienen contenido real | Valor actual: `es` |
| **EventSubscriber** | Patron Symfony para interceptar eventos del kernel | `KernelEvents::REQUEST` |
| **PathProcessor** | Procesador de paths entrantes/salientes de Drupal | Prioridad 200-400, antes del router |
| **MetaSite** | Dominio de produccion con identidad propia | 4 dominios en MetaSiteResolverService::VARIANT_MAP |
| **VARIANT_MAP** | Constante SSOT que mapea group_id a variante | `5→pepejaraba, 6→jarabaimpact, 7→pde` |
| **PHPStan L6** | Analizador estatico PHP nivel 6 (strict) | Detecta errores de tipo, null safety, etc. |
| **PHANTOM-ARG-001** | Regla: args services.yml deben coincidir con constructor | Validador bidireccional (args de mas y de menos) |

---

*Documento generado automaticamente. Commit: b7482c792. Deploy verificado en produccion 2026-03-26.*
