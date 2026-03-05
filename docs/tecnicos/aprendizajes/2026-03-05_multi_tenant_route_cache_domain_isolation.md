# Aprendizaje #162 — Multi-Tenant Route Cache Isolation + Visual Customizer + Vertical Tabs

**Fecha:** 2026-03-05
**Contexto:** Debugging de homepage contamination (jaraba-saas.lndo.site renderizaba contenido de PED meta-sitio) + fixes en tenant theme customizer.

## Hallazgos

### 1. Domain Module Route Cache Mechanism (DOMAIN-ROUTE-CACHE-001)

**Problema:** La homepage del SaaS principal mostraba contenido del meta-sitio PED (plataformadeecosistemas) intermitentemente.

**Root Cause:** Drupal's `RouteProvider::getRouteCollectionForRequest()` (core/lib/Drupal/Core/Routing/RouteProvider.php:168-194) cachea route collections en el bin `data` (Redis) con cache key:
```
route:[domain]=X:[language]=Y:[query_parameters]=Z:/path
```

La parte `[domain]` viene de `DomainSubscriber::onRouteMatch()` (domain/src/EventSubscriber/DomainSubscriber.php:106) que llama a `$this->routeProvider->addExtraCacheKeyPart('domain', $domain->id())`.

Sin un Domain entity para `plataformadeecosistemas.jaraba-saas.lndo.site`, el Domain module usaba la entidad default (`jaraba_saas_lndo_site`) para ambos hostnames, colisionando las cache keys.

**Impacto critico:** Cuando la cache HIT en RouteProvider (linea 172-178), los path processors NO se ejecutan (linea 184 solo corre en el ELSE branch). La primera peticion a `/` determina la ruta para TODAS las peticiones subsiguientes con la misma cache key.

**Fix:** Crear Domain entity para cada hostname de meta-sitio en desarrollo local (config/sync/domain.record.plataformadeecosistemas_jaraba_saas_lndo_site.yml).

**Regla:** DOMAIN-ROUTE-CACHE-001 (P0) — Cada hostname que sirva contenido diferente DEBE tener su propia Domain entity en el modulo Domain. Sin ella, las cache keys de ruta colisionan en Redis y los path processors dejan de ejecutarse para el segundo hostname.

### 2. Vary:Host Header para Reverse Proxy (VARY-HOST-001)

`SecurityHeadersSubscriber` ampliado con metodo `onAddVaryHost()` en prioridad -10 (DESPUES de FinishResponseSubscriber que establece Vary:Cookie en prioridad 0). Appends `Vary: Host` para que CDN/Varnish/Traefik no sirvan respuestas cacheadas de un subdominio a otro.

**Patron:** Dual event registration en `getSubscribedEvents()`:
```php
KernelEvents::RESPONSE => [
    ['onKernelResponse', 100],   // Security headers (early)
    ['onAddVaryHost', -10],      // Vary:Host (AFTER FinishResponseSubscriber)
],
```

### 3. Visual Customizer JS Field Name Alignment (PHANTOM-ARG-JS-001)

El JS `visual-customizer.js` tenia 11 campos fantasma y 4 mismatches con los campos PHP reales de `TenantThemeCustomizerForm`. La "Vista Previa" solo aplicaba 5 de 14 campos — practicamente no funcionaba.

**Regla:** Los mapas de nombres de campo en JS (colorMap, typographyMap, sizeMap) DEBEN coincidir EXACTAMENTE con los `$form` element keys del PHP. `#tree=false` implica que los nombres son flat, no nested. Verificar con `grep` los nombres de campo del PHP form antes de editar el JS.

### 4. Vertical Tab Titles (VERTICAL-TAB-TEXT-001)

Drupal's `vertical-tabs.js` (linea 92) usa `$summary[0]?.firstChild?.textContent` para extraer el titulo de la tab. Si el `#title` contiene HTML markup (como `<img>` tags), `textContent` devuelve string vacio.

**Fix:** Usar texto plano `$this->t('Label')` en `#title` de vertical tabs, NUNCA `Markup::create(icon + text)`.

## Reglas de Oro

- **#104**: Cada hostname multi-tenant NECESITA su propia Domain entity. Sin ella, RouteProvider cachea la ruta del primer hostname y la sirve a todos los demas (cache key colision).
- **#105**: Vary:Host DESPUES de FinishResponseSubscriber (prioridad -10). Mapas JS↔PHP de campos DEBEN verificarse con grep contra los form elements del PHP.

## Verificaciones Runtime

1. `curl -sI https://jaraba-saas.lndo.site/ | grep -i vary` → `Vary: Cookie, Host`
2. `curl -sI https://plataformadeecosistemas.jaraba-saas.lndo.site/ | grep -i vary` → `Vary: Cookie, Host`
3. Homepage SaaS NO muestra contenido PED tras limpiar cache
4. Vista Previa en /my-settings/design aplica 13 campos al DOM

## Cross-refs

- Directrices v112.0.0, Arquitectura v101.0.0, Indice v141.0.0, Flujo v65.0.0
