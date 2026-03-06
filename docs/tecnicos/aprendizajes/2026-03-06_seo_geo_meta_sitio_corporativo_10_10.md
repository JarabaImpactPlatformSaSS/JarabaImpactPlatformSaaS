# Aprendizaje #170 — SEO/GEO Meta-Sitio Corporativo 10/10 + Reclutamiento Landing Elevation

**Fecha:** 2026-03-06
**Contexto:** Auditoria SEO/GEO profunda del meta-sitio corporativo (plataformadeecosistemas.es) + elevacion de la landing de reclutamiento Andalucia +ei. 4 bloqueantes resueltos, NAP normalizado, Schema.org enriquecido.

## Hallazgos

### 1. Orphan Template Detection Pattern
- `_ped-schema.html.twig` y `_seo-schema.html.twig` existian como parciales pero NUNCA se incluian en `html.html.twig`
- Schema.org Corporation y Organization/SoftwareApplication eran invisibles en el HTML servido
- Fix: añadir `{% include %}` en `html.html.twig` con `ignore missing` — global para _seo-schema, condicional `is_meta_site` para _ped-schema
- Leccion: SIEMPRE verificar que los parciales Twig estan referenciados desde algun parent template

### 2. ZERO-REGION-003 en Controllers de Landing
- `AndaluciaEiLandingController::reclutamiento()` usaba `#attached` para library injection
- En Zero Region pages, el render pipeline NO procesa `#attached` del controller
- Fix: mover library attachment a `hook_page_attachments()` en el .module
- Patron: TODA inyeccion de libraries en Zero Region DEBE ir via hook_page_attachments()

### 3. Cookie Banner localStorage-First (Root Cause)
- `submitConsent()` hacia fetch() al servidor y si fallaba, el banner no se cerraba
- Root cause: la UX dependia del exito del POST al servidor
- Fix: guardar en localStorage y ocultar banner ANTES del fetch. Server POST es best-effort
- Patron: COOKIE-CONSENT-LOCAL-FIRST — localStorage siempre primero, server async

### 4. og:image Fallback Chain
- `_jaraba_page_builder_extract_og_image()` tenia fallback a `logo.svg` que no existia
- El tema no tiene logo.svg pero si `images/og-image-dynamic.png`
- Fix: fallback chain con 3 candidatos: og-image-dynamic.png > og-image-dynamic.webp > logo.svg
- Patron: SEO-OG-IMAGE-FALLBACK-001 — cascade de candidatos para og:image

### 5. Hreflang Deduplication
- HreflangService (jaraba_page_builder) genera hreflang para page_content routes
- `_hreflang-meta.html.twig` (parcial Twig) genera hreflang para TODAS las rutas
- Resultado: duplicacion en paginas page_content
- Fix: variable `is_page_content` en preprocess_html, condicional en html.html.twig
- URLs absolutas: `'absolute' => TRUE` en Url::fromRoute() del preprocess_html

### 6. NAP Normalization (Name/Address/Phone)
- Telefonos falsos `+34 955 000 123` y `+34 600 123 456` en map templates
- Email placeholder `info@andalucia-ei.es` en config y templates
- Fix: normalizar a telefono real `+34 623 174 304` y email `contacto@plataformadeecosistemas.es`
- Corporation schema: 2 direcciones postales (Malaga + Sevilla), GeoCoordinates, hasMap

### 7. Meta-Site Homepage Detection
- `isFrontPage()` devuelve false para PageBuilder pages (front = /node, home real = page/78)
- Fix: comprobar `$requestPath` contra `['/', '/es', '/es/']` en vez de `isFrontPage()`
- Aplicado al popup de reclutamiento que solo debe aparecer en homepage

### 8. Publicidad Oficial Obligatoria (Legal)
- Logos institucionales del programa PIIL CV son legalmente obligatorios en la landing
- Imagen copiada a `jaraba_andalucia_ei/images/publicidad-oficial-piil.png`
- Template con `role="banner"`, alt descriptivo de los 5 logos, `loading="eager"`
- h1 dedicado: "T-Acompanamos: Programa de Proyectos Integrales..."

## Reglas Nuevas

| Regla | Severidad | Descripcion |
|-------|-----------|-------------|
| SEO-OG-IMAGE-FALLBACK-001 | P1 | og:image cascade: content_data > og-image-dynamic.png > logo.svg |
| HREFLANG-CONDITIONAL-001 | P1 | Skip parcial Twig hreflang en page_content routes (HreflangService lo cubre) |
| NAP-NORMALIZATION-001 | P1 | NAP consistente en todos los Schema.org: telefono, email, direccion |
| ORPHAN-TEMPLATE-001 | P1 | Todo parcial Twig DEBE tener al menos un {% include %} en un parent |
| COOKIE-CONSENT-LOCAL-FIRST | P1 | localStorage primero, server POST best-effort asincono |

## Reglas de Oro
- **#106**: Orphan templates — verificar SIEMPRE que los parciales Twig estan incluidos desde algun template padre. Un parcial sin include es codigo muerto invisible.
- **#107**: NAP consistency — Name/Address/Phone DEBEN ser identicos en TODOS los Schema.org del sitio. Un telefono falso destruye la confianza SEO.
- **#108**: og:image fallback — SIEMPRE verificar que el fichero de fallback existe fisicamente. Un fallback roto es peor que no tener fallback.

## Validaciones
- curl meta-sitio homepage: meta description OK, og:image OK, Corporation schema OK, hreflang OK (sin duplicados)
- curl reclutamiento landing: geo OK (ES-AN, ICBM, GeoCoordinates), OG OK, EducationalOccupationalProgram OK, FAQPage OK
- PHP lint: 0 errores en 3 ficheros
- SCSS compilado: timestamp CSS > SCSS
- Popup: carga en homepage meta-sitio, sessionStorage dismiss funcional

## Cross-refs
- Directrices v120.0.0, Arquitectura v108.0.0, Indice v149.0.0, Flujo v73.0.0
