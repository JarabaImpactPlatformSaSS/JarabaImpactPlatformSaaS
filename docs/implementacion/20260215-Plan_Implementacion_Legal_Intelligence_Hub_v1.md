# Plan de Implementacion Legal Intelligence Hub v1.0

> **Fecha:** 2026-02-15
> **Ultima actualizacion:** 2026-02-15
> **Autor:** Claude Opus 4.6
> **Version:** 1.0.0
> **Estado:** Planificacion inicial
> **Vertical:** ServiciosConecta (Plataforma de Confianza Digital para Profesionales)
> **Modulo principal:** `jaraba_legal_intelligence`

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Vision y Posicionamiento](#11-vision-y-posicionamiento)
  - [1.2 Relacion con la infraestructura existente](#12-relacion-con-la-infraestructura-existente)
  - [1.3 Patron arquitectonico de referencia](#13-patron-arquitectonico-de-referencia)
  - [1.4 Avatar principal: Elena](#14-avatar-principal-elena)
- [2. Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
  - [3.1 Directriz: i18n — Textos siempre traducibles](#31-directriz-i18n--textos-siempre-traducibles)
  - [3.2 Directriz: Modelo SCSS con Federated Design Tokens](#32-directriz-modelo-scss-con-federated-design-tokens)
  - [3.3 Directriz: Dart Sass moderno](#33-directriz-dart-sass-moderno)
  - [3.4 Directriz: Frontend limpio sin regiones Drupal](#34-directriz-frontend-limpio-sin-regiones-drupal)
  - [3.5 Directriz: Body classes via hook_preprocess_html](#35-directriz-body-classes-via-hook_preprocess_html)
  - [3.6 Directriz: CRUD en modales slide-panel](#36-directriz-crud-en-modales-slide-panel)
  - [3.7 Directriz: Entidades con Field UI y Views](#37-directriz-entidades-con-field-ui-y-views)
  - [3.8 Directriz: No hardcodear configuracion](#38-directriz-no-hardcodear-configuracion)
  - [3.9 Directriz: Parciales Twig reutilizables](#39-directriz-parciales-twig-reutilizables)
  - [3.10 Directriz: Seguridad](#310-directriz-seguridad)
  - [3.11 Directriz: Comentarios de codigo](#311-directriz-comentarios-de-codigo)
  - [3.12 Directriz: Iconos SVG duotone](#312-directriz-iconos-svg-duotone)
  - [3.13 Directriz: AI via abstraccion @ai.provider](#313-directriz-ai-via-abstraccion-aiprovider)
  - [3.14 Directriz: Automaciones via hooks Drupal](#314-directriz-automaciones-via-hooks-drupal)
- [4. Arquitectura del Modulo](#4-arquitectura-del-modulo)
  - [4.1 Nombre y ubicacion](#41-nombre-y-ubicacion)
  - [4.2 Dependencias](#42-dependencias)
  - [4.3 Estructura de directorios](#43-estructura-de-directorios)
  - [4.4 Compilacion SCSS](#44-compilacion-scss)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. FASE 0: Infraestructura y Scaffolding](#6-fase-0-infraestructura-y-scaffolding)
- [7. FASE 1: Entidades Core + Spiders Nacionales](#7-fase-1-entidades-core--spiders-nacionales)
- [8. FASE 2: Pipeline NLP 9 Etapas](#8-fase-2-pipeline-nlp-9-etapas)
- [9. FASE 3: Motor de Busqueda + Frontend](#9-fase-3-motor-de-busqueda--frontend)
- [10. FASE 4: Fuentes Europeas](#10-fase-4-fuentes-europeas)
- [11. FASE 5: Alertas + Digest Semanal](#11-fase-5-alertas--digest-semanal)
- [12. FASE 6: Integracion ServiciosConecta](#12-fase-6-integracion-serviciosconecta)
- [13. FASE 7: Dashboard Admin](#13-fase-7-dashboard-admin)
- [14. FASE 8: Dashboard Profesional + SEO](#14-fase-8-dashboard-profesional--seo)
- [15. FASE 9: QA + Go-Live](#15-fase-9-qa--go-live)
- [16. Paleta de Colores y Design Tokens](#16-paleta-de-colores-y-design-tokens)
- [17. Patron de Iconos SVG](#17-patron-de-iconos-svg)
- [18. Orden de Implementacion Global](#18-orden-de-implementacion-global)
- [19. Relacion con jaraba_legal_knowledge](#19-relacion-con-jaraba_legal_knowledge)
- [20. Registro de Cambios](#20-registro-de-cambios)

---

## 1. Resumen Ejecutivo

El Legal Intelligence Hub es un modulo especializado que dota a abogados, asesorias fiscales y gestorias de acceso inteligente a resoluciones administrativas, jurisprudencia y normativa vigente. A diferencia de bases de datos juridicas tradicionales como Aranzadi, Lefebvre o vLex —que requieren suscripciones de 3.000-8.000 EUR/ano—, este modulo aprovecha fuentes publicas oficiales (CENDOJ, BOE, DGT, TEAC, IGAE, BOICAC, TJUE, TEDH, EUR-Lex) y las enriquece con IA para ofrecer busqueda semantica, resumen automatico e insercion directa de citas en expedientes del profesional.

El pitch diferenciador es: _"Toda la jurisprudencia espanola y europea en tu plataforma: busca en lenguaje natural, resume con IA, e inserta la cita en tu escrito con un clic"_.

### 1.1 Vision y Posicionamiento

El Legal Intelligence Hub se posiciona como el **diferenciador competitivo critico** del vertical ServiciosConecta para profesionales legales, fiscales y administrativos, con cinco pilares:

- **Fuentes publicas oficiales**: CENDOJ, BOE, DGT, TEAC, IGAE, BOICAC, BOJA (nacionales) + TJUE, TEDH, EUR-Lex, EDPB (europeas). Sin coste de licencia, acceso abierto por mandato legal.
- **Pipeline NLP de 9 etapas**: Apache Tika (extraccion) -> spaCy (segmentacion, NER) -> Gemini 2.0 Flash (clasificacion, resumen, strict grounding) -> embeddings (text-embedding-3-large / multilingual-e5-large) -> Qdrant (indexacion vectorial) -> grafo de citas.
- **Busqueda semantica unificada**: El profesional pregunta en lenguaje natural y recibe resoluciones relevantes de fuentes nacionales y europeas simultanmente, con merge & rank, boost por primacia UE y frescura.
- **Insercion en expediente con un clic**: Botones de accion directa para generar citas formateadas (formal, resumida, bibliografica, nota al pie) e insertarlas en escritos del Buzon de Confianza (doc 88).
- **Alertas contextuales inteligentes**: Notificacion cuando una resolucion citada en un expediente activo es anulada, modificada o superada por nueva doctrina, incluyendo sentencias TJUE que contradicen doctrina nacional.

### 1.2 Relacion con la infraestructura existente

El Legal Intelligence Hub se construye sobre la infraestructura consolidada del ecosistema:

- **ecosistema_jaraba_core**: Entidades base (Tenant, Vertical, SaasPlan, Feature), servicios compartidos (TenantManager, PlanValidator, FinOpsTrackingService), sistema de permisos RBAC multi-tenant, design tokens CSS.
- **ecosistema_jaraba_theme**: Tema unificado con Federated Design Tokens, parciales Twig reutilizables (_header, _footer, _copilot-fab), slide-panel singleton.
- **jaraba_rag**: Pipeline RAG con Qdrant, embeddings, grounding validator. Se reutiliza para la busqueda semantica y para la integracion con Copilot de Servicios.
- **jaraba_servicios_conecta**: Vertical padre que provee el contexto de tenant, perfiles profesionales (provider_profile), expedientes del Buzon de Confianza (doc 88) y Copilot de Servicios (doc 93).
- **jaraba_email**: Sistema de email marketing para digest semanal y notificaciones de alertas.
- **jaraba_ai**: Abstraccion `@ai.provider` para llamadas a Gemini 2.0 Flash. NUNCA llamadas HTTP directas.

### 1.3 Patron arquitectonico de referencia

- **Content Entities con Field UI + Views** para todos los datos de negocio (resoluciones, alertas, favoritos, citas).
- **Frontend limpio sin regiones Drupal**: Templates Twig full-width con parciales reutilizables, sin `page.content` ni bloques heredados.
- **CRUD en slide-panel modal**: Acciones de crear/editar alertas, insertar citas, gestionar favoritos se abren en panel lateral.
- **Federated Design Tokens**: SCSS con variables `$legal-*` como fallback, consumo via `var(--ej-*)`.
- **Body classes via `hook_preprocess_html()`**: NUNCA `attributes.addClass()` en templates Twig para el body.
- **API REST versionada** bajo `/api/v1/legal/` con autenticacion, rate limiting y limites por plan.
- **AI via `@ai.provider`**: Gemini 2.0 Flash para clasificacion, resumen y strict grounding. NUNCA llamadas HTTP directas.
- **Automaciones via hooks Drupal**: `hook_entity_insert/update`, `hook_cron`. NO ECA BPMN.
- **Textos siempre traducibles**: `$this->t()` en PHP, `{% trans %}` en Twig, `Drupal.t()` en JS.
- **Dart Sass moderno**: `color.adjust()` en lugar de `darken()`/`lighten()` deprecados.

### 1.4 Avatar principal: Elena

Elena Martinez Garcia representa al profesional liberal que necesita acceso inteligente a informacion juridica actualizada:

| Atributo | Descripcion |
|----------|-------------|
| **Nombre** | Elena Martinez Garcia |
| **Profesion** | Abogada especializada en derecho civil y familia |
| **Ubicacion** | Cabra (Cordoba) - 20.000 habitantes |
| **Edad** | 42 anos |
| **Situacion** | Despacho propio con 1 administrativa a media jornada |
| **Pain Point** | Buscar jurisprudencia le cuesta 2-3 horas por caso. No puede pagar 5.000 EUR/ano de Aranzadi |
| **Meta** | Encontrar resoluciones relevantes en segundos e insertarlas directamente en sus escritos |

**Escenarios de uso de Elena:**

| Escenario | Query al Sistema | Resultado Esperado |
|-----------|-----------------|-------------------|
| Preparar demanda de desahucio | Jurisprudencia TS sobre desahucio por impago post-COVID | Sentencias relevantes con doctrina sobre vulnerabilidad y plazos |
| Recurrir sancion administrativa | Resoluciones TEAC sobre sanciones en IVA por facturas falsas | Criterios del TEAC + sentencias AN que las confirman/anulan |
| Clausulas abusivas en hipotecas | TJUE clausulas abusivas directiva 93/13 | Sentencia Aziz (C-415/11) + doctrina nacional aplicable |
| Impuesto Sucesiones no residente | Libre circulacion capitales ISD no residentes | TJUE C-127/12 + DGT consultas vinculantes |

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

La siguiente tabla mapea cada especificacion tecnica del Legal Intelligence Hub con su fase de implementacion, entidades principales y nivel de reutilizacion.

| Doc # | Titulo Especificacion | Fase | Entidades Principales | Reutilizacion |
|-------|----------------------|------|----------------------|---------------|
| **178** | Legal Intelligence Hub (Especificacion base) | Fases 0-3, 5-9 | `legal_resolution` (35+ campos), `legal_alert`, `legal_bookmark`, `legal_citation`, `legal_source` | 40% (jaraba_rag pipeline, patron de busqueda vectorial) |
| **178A** | EU Sources (Fuentes europeas TJUE/TEDH/EUR-Lex) | Fase 4 | Campos EU en `legal_resolution` (celex_number, ecli, case_number, etc.), taxonomias UE | 30% (pipeline NLP existente, Qdrant) |
| **178B** | Implementation Guide (Codigo PHP/Python/YAML completo) | Todas | Codigo completo de entidades, servicios, spiders, NLP, tests | 50% (patron de servicios Drupal, QueueWorkers) |

**Resumen de reutilizacion por componente:**

| Componente | Fuente de reutilizacion | % Reutilizacion |
|-----------|------------------------|----------------|
| Pipeline RAG (embeddings + Qdrant) | jaraba_rag | 60% |
| Patron de Content Entities | ecosistema_jaraba_core | 70% |
| Plantillas frontend (parciales) | ecosistema_jaraba_theme | 80% |
| Spiders / Web Scraping | Componente exclusivo | 0% |
| Pipeline NLP (spaCy + Tika) | Componente exclusivo | 0% |
| Taxonomias juridicas | Componente exclusivo | 0% |
| Sistema de alertas | jaraba_email (patron) | 30% |
| Integracion con Copilot | jaraba_servicios_conecta | 40% |

---

## 3. Cumplimiento de Directrices del Proyecto

Esta seccion documenta como el Legal Intelligence Hub cumple con cada directriz obligatoria del proyecto, segun `docs/00_DIRECTRICES_PROYECTO.md` y los workflows definidos en `.agent/workflows/`.

### 3.1 Directriz: i18n — Textos siempre traducibles

**Referencia:** `.agent/workflows/i18n-traducciones.md`

Todo texto visible al usuario DEBE ser traducible. No se admite ningun string hardcodeado en la interfaz.

| Contexto | Metodo | Ejemplo Legal Intelligence Hub |
|----------|--------|-------------------------------|
| PHP (Controllers, Services) | `$this->t('Texto')` | `$this->t('Search legal resolutions')` |
| PHP (Entities, fuera de clase con DI) | `new TranslatableMarkup('Texto')` | `new TranslatableMarkup('Legal Resolution')` |
| Twig templates | `{% trans %}Texto{% endtrans %}` | `{% trans %}Insert as argument{% endtrans %}` |
| Twig con variables | `{{ 'Texto @var'|t({'@var': value}) }}` | `{{ 'Resolution @ref from @body'|t({'@ref': ref, '@body': body}) }}` |
| JavaScript | `Drupal.t('Texto')` | `Drupal.t('No results found')` |
| Annotations de entidad | `@Translation('Texto')` | `@Translation('Legal Resolution')` |
| YAML (menus, permisos) | Texto directo (Drupal traduce) | `title: 'Search legal resolutions'` |
| Formularios | `'#title' => $this->t('Texto')` | `'#title' => $this->t('Search query')` |

**Ejemplo CORRECTO:**
```php
// En LegalSearchController.php
$this->messenger()->addStatus($this->t('Found @count resolutions matching your query.', [
  '@count' => $results['total'],
]));
```

**Ejemplo PROHIBIDO:**
```php
// NUNCA hardcodear strings visibles al usuario
$this->messenger()->addStatus("Encontradas $count resoluciones.");
```

### 3.2 Directriz: Modelo SCSS con Federated Design Tokens

**Referencia:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`, `.agent/workflows/scss-estilos.md`

El modulo Legal Intelligence Hub como modulo satelite NUNCA define variables `$ej-*`. Solo consume CSS Custom Properties con fallbacks inline.

| Capa | Nombre | Ubicacion | Que define Legal Intelligence |
|------|--------|-----------|-------------------------------|
| 1 | SCSS Tokens (SSOT) | `ecosistema_jaraba_core/scss/_variables.scss` | Nada (solo lectura) |
| 2 | CSS Custom Properties | `ecosistema_jaraba_core/scss/_injectable.scss` | Nada (solo lectura) |
| 3 | Componente local | `jaraba_legal_intelligence/scss/_variables-legal.scss` | Variables locales `$legal-*` como fallback |
| 4 | Tenant Override | `hook_preprocess_html()` del tema | Nada (gestionado por ecosistema_jaraba_core) |

**Ejemplo CORRECTO:**
```scss
// jaraba_legal_intelligence/scss/_variables-legal.scss
// Variables locales del modulo legal - SOLO como fallback para var(--ej-*)
$legal-primary: #1E3A5F;      // Azul Juridico Profundo
$legal-accent: #C8A96E;       // Oro Justicia
$legal-surface: #F5F3EF;      // Pergamino suave
$legal-success: #2D6A4F;      // Verde Vigente
$legal-danger: #9B2335;       // Rojo Derogada
$legal-warning: #D4A843;      // Ambar Superada
$legal-eu-blue: #003399;      // Azul UE
$legal-eu-gold: #FFCC00;      // Oro UE
$legal-text: #1A1A2E;         // Texto principal
$legal-text-light: #6B7280;   // Texto secundario

// USO en parciales SCSS del modulo:
.legal-resolution-card {
  background: var(--ej-bg-surface, #{$legal-surface});
  color: var(--ej-text-primary, #{$legal-text});
  border-left: 4px solid var(--ej-color-primary, #{$legal-primary});
}
```

**Ejemplo PROHIBIDO:**
```scss
// NUNCA hacer esto en un modulo satelite
$ej-color-primary: #1E3A5F;  // PROHIBIDO: redefine token SSOT
```

### 3.3 Directriz: Dart Sass moderno

**Referencia:** `.agent/workflows/scss-estilos.md`

Toda funcion de manipulacion de color debe usar la sintaxis moderna de Dart Sass.

**CORRECTO:**
```scss
@use 'sass:color';

.legal-btn--hover {
  background: color.adjust($legal-primary, $lightness: -10%);
}

.legal-badge--vigente {
  background: color.change($legal-success, $alpha: 0.15);
  color: $legal-success;
}

.legal-card--elevated {
  box-shadow: 0 4px 12px color.change($legal-primary, $alpha: 0.12);
}
```

**PROHIBIDO (funciones deprecadas):**
```scss
// NUNCA usar:
background: darken($legal-primary, 10%);   // DEPRECADO
background: lighten($legal-accent, 10%);   // DEPRECADO
border-color: saturate($color, 20%);       // DEPRECADO
```

**Compilacion:**
```bash
# Dentro del contenedor Docker:
lando ssh -c "cd /app/web/modules/custom/jaraba_legal_intelligence && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"
lando drush cr
```

### 3.4 Directriz: Frontend limpio sin regiones Drupal

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Todas las paginas frontend del Legal Intelligence Hub usan templates Twig limpias que renderizan el HTML completo sin `{{ page.content }}`, sin bloques heredados, sin sidebars, y con layout full-width pensado para movil.

**Estructura obligatoria de cada pagina frontend:**

```twig
{# page--legal-search.html.twig #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('jaraba_legal_intelligence/legal.search') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--premium page-wrapper--legal">

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    'site_name': site_name,
    'site_slogan': site_slogan,
    'logo': logo,
    'logged_in': logged_in
  } only %}

  <main class="main-content main-content--full main-content--legal" role="main">
    <div class="main-content__inner main-content__inner--full">
      {# Contenido especifico de la pagina - SIN page.content #}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    'site_name': site_name
  } only %}

  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' only %}
</div>
```

**Ejemplo PROHIBIDO:**
```twig
{# NUNCA usar regiones de Drupal para contenido del modulo #}
<main>{{ page.content }}</main>
```

### 3.5 Directriz: Body classes via hook_preprocess_html

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Las clases anadidas con `attributes.addClass()` en templates Twig **NO funcionan para el body**. Se DEBE usar `hook_preprocess_html()`.

**CORRECTO:**
```php
/**
 * Implements hook_preprocess_html().
 *
 * Inyecta clases CSS al <body> segun la ruta activa del Legal Intelligence Hub.
 * DIRECTRIZ: NUNCA usar attributes.addClass() en templates Twig para body.
 */
function jaraba_legal_intelligence_preprocess_html(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName();

  // Mapa de rutas del Legal Intelligence Hub a clases CSS del body.
  $routeClasses = [
    'jaraba_legal.search' => 'page--legal-search',
    'jaraba_legal.resolution' => 'page--legal-resolution',
    'jaraba_legal.public_summary' => 'page--legal-public',
    'jaraba_legal.settings' => 'page--legal-admin',
  ];

  if (isset($routeClasses[$route_name])) {
    $variables['attributes']['class'][] = $routeClasses[$route_name];
    $variables['attributes']['class'][] = 'page--legal';
    $variables['attributes']['class'][] = 'page--clean-layout';
  }
}
```

**PROHIBIDO:**
```twig
{# NUNCA usar esto para body classes #}
{% set attributes = attributes.addClass('page--legal-search') %}
```

### 3.6 Directriz: CRUD en modales slide-panel

**Referencia:** `.agent/workflows/slide-panel-modales.md`

Las acciones de crear alertas, insertar citas, gestionar favoritos y editar configuracion se abren en slide-panel.

**Patron HTML (data attributes, sin JS adicional):**
```html
<!-- Boton para insertar cita en expediente -->
<button class="btn btn--primary"
        data-slide-panel="cite-{{ resolution.id }}"
        data-slide-panel-url="/legal/cite/{{ resolution.id }}/formal?ajax=1"
        data-slide-panel-title="{{ 'Insert citation'|t }}"
        data-slide-panel-size="--large">
  {{ jaraba_icon('legal', 'citation', { size: '18px' }) }}
  {% trans %}Insert as argument{% endtrans %}
</button>

<!-- Boton para crear alerta -->
<button class="btn btn--outline"
        data-slide-panel="alert-create"
        data-slide-panel-url="/legal/alerts/add?ajax=1"
        data-slide-panel-title="{{ 'Create alert'|t }}"
        data-slide-panel-size="--medium">
  {{ jaraba_icon('legal', 'alert-bell', { size: '16px' }) }}
  {% trans %}Create alert{% endtrans %}
</button>
```

**Patron PHP en Controller (deteccion AJAX):**
```php
/**
 * Renderiza el formulario de insercion de cita.
 *
 * Si la peticion es AJAX (slide-panel), devuelve solo el HTML del formulario.
 */
public function cite(Request $request, int $resolution_id, string $format): Response|array {
  $form = $this->formBuilder()->getForm(LegalCitationInsertForm::class, $resolution_id, $format);

  if ($request->isXmlHttpRequest()) {
    $html = (string) $this->renderer->render($form);
    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  return $form;
}
```

**Dependencia de libreria:**
```yaml
dependencies:
  - ecosistema_jaraba_theme/slide-panel
```

### 3.7 Directriz: Entidades con Field UI y Views

**Referencia:** `.agent/workflows/drupal-custom-modules.md`, `docs/00_DIRECTRICES_PROYECTO.md`

Todas las entidades del Legal Intelligence Hub DEBEN ser Content Entities con soporte para Field UI y Views.

**Checklist obligatorio por entidad:**

- `@ContentEntityType` annotation completa con `id`, `label`, `label_collection`, `label_singular`, `label_plural`
- Handlers: `list_builder`, `views_data` (`Drupal\views\EntityViewsData`), `form` (default/add/edit/delete), `access`, `route_provider` (`AdminHtmlRouteProvider`)
- `field_ui_base_route` apuntando a la ruta de settings en `/admin/structure/`
- Links: `canonical`, `add-form`, `edit-form`, `delete-form`, `collection`
- Collection en `/admin/content/legal-{entity}` (pestana en Content)
- Settings en `/admin/structure/legal-{entity}` (enlace en Structure)
- 4 archivos YAML de navegacion admin

**Ejemplo CORRECTO (LegalResolution):**
```php
/**
 * @ContentEntityType(
 *   id = "legal_resolution",
 *   label = @Translation("Legal Resolution"),
 *   label_collection = @Translation("Legal Resolutions"),
 *   label_singular = @Translation("legal resolution"),
 *   label_plural = @Translation("legal resolutions"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\LegalResolutionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_intelligence\Form\LegalResolutionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_intelligence\LegalResolutionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   field_ui_base_route = "jaraba_legal.resolution.settings",
 * )
 */
```

**Ejemplo PROHIBIDO:**
```php
// NUNCA crear entidades sin Field UI ni Views
// NUNCA usar tablas custom en lugar de Content Entities (excepto legal_citation_graph)
```

### 3.8 Directriz: No hardcodear configuracion

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

Toda configuracion de negocio DEBE ser editable desde la UI de Drupal.

| Dato | Mecanismo | Ejemplo Legal Intelligence Hub |
|------|-----------|-------------------------------|
| URL de Qdrant | Config Settings | `jaraba_legal_intelligence.settings.qdrant_url` |
| URL del servicio NLP (FastAPI) | Config Settings | `jaraba_legal_intelligence.settings.nlp_service_url` |
| URL de Apache Tika | Config Settings | `jaraba_legal_intelligence.settings.tika_url` |
| Fuentes activas | Config Entity `legal_source` | Activar/desactivar fuentes individuales |
| Frecuencia de sincronizacion | Campo en `legal_source` | Diaria, semanal, mensual por fuente |
| Limites por plan (busquedas/mes) | Config Settings | Starter: 50, Pro: ilimitado, Enterprise: ilimitado |
| Alertas maximas por plan | Config Settings | Starter: 3, Pro: 20, Enterprise: ilimitado |
| Score threshold Qdrant | Config Settings | Default: 0.65 (editable) |
| Prompt de clasificacion NLP | Config Settings (text_long) | Editable sin tocar codigo |
| Prompt de resumen NLP | Config Settings (text_long) | Editable sin tocar codigo |

**Patron de fallback con `?:` (falsy coalesce):**
```php
// CORRECTO: Usa ?: porque Drupal config puede devolver string vacio ''.
$qdrantUrl = $this->config('jaraba_legal_intelligence.settings')
  ->get('qdrant_url') ?: 'http://localhost:6333';

// INCORRECTO: ?? solo comprueba null, no string vacio.
$qdrantUrl = $this->config('jaraba_legal_intelligence.settings')
  ->get('qdrant_url') ?? 'http://localhost:6333';
```

### 3.9 Directriz: Parciales Twig reutilizables

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

**Parciales existentes que Legal Intelligence Hub DEBE reutilizar (NO duplicar):**

| Parcial | Ubicacion | Uso en Legal Intelligence Hub |
|---------|-----------|-------------------------------|
| `_header.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Header de todas las paginas frontend |
| `_footer.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Footer de todas las paginas frontend |
| `_copilot-fab.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | FAB del copilot IA en todas las paginas |
| `_stats.html.twig` | `ecosistema_jaraba_theme/templates/partials/` | Estadisticas en dashboard admin |

**Parciales NUEVOS del Legal Intelligence Hub (en el modulo, no en el tema):**

| Parcial | Ubicacion | Reutilizado en |
|---------|-----------|----------------|
| `_resolution-card.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Resultados de busqueda, favoritos, alertas |
| `_citation-badge.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Estado legal (vigente/derogada/superada) en todas las vistas |
| `_search-facets.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Filtros facetados en busqueda y dashboard |
| `_citation-graph.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Mini-grafo de citas en detalle y busqueda |
| `_eu-primacy-indicator.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Badges primacia UE, efecto directo, transposicion |
| `_alert-card.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Tarjeta de alerta en dashboard, lista de alertas |
| `_digest-preview.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Preview del digest semanal |
| `_source-status.html.twig` | `jaraba_legal_intelligence/templates/partials/` | Estado de fuentes en dashboard admin |

**Convenio de inclusion:**
```twig
{% include '@jaraba_legal_intelligence/partials/_resolution-card.html.twig' with {
  'resolution': resolution,
  'show_actions': true,
  'show_graph': false,
  'compact': false
} only %}
```

### 3.10 Directriz: Seguridad

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

- Rate limiting obligatorio: 100 busquedas/hora por usuario, 50 busquedas/hora para plan Starter.
- Prompt sanitization contra whitelist para datos interpolados en prompts de Gemini.
- Circuit breaker para Qdrant y servicio NLP: saltar 5 min tras 5 fallos consecutivos.
- API keys en variables de entorno (`.env`), NUNCA en configuracion exportable de Drupal.
- Aislamiento de tenant en Qdrant con filtro `must` (AND), NUNCA `should` (OR) para datos privados (favoritos, alertas cifradas).
- Todos los endpoints `/api/v1/legal/*` requieren autenticacion y permiso `access legal api`.
- Busquedas y favoritos del profesional cifrados con clave derivada del tenant (AES-256-GCM) para secreto profesional (art. 542.3 LOPJ).
- Queries de busqueda registradas anonimizadas para analytics agregados, sin vincular a profesional concreto.
- Deduplicacion por SHA-256 hash del contenido completo (`content_hash`) para evitar resoluciones duplicadas.
- Validacion de certificados y fuentes antes de ingestar documentos.

### 3.11 Directriz: Comentarios de codigo

**Referencia:** `docs/00_DIRECTRICES_PROYECTO.md`

Los comentarios deben cubrir tres dimensiones en espanol:

1. **Estructura**: Organizacion, relaciones entre componentes, patrones usados, jerarquias.
2. **Logica**: Proposito (por que), flujo de ejecucion, reglas de negocio, decisiones de diseno, edge cases.
3. **Sintaxis**: Parametros (tipo + proposito), valores de retorno, excepciones, estructuras de datos complejas.

**Ejemplo CORRECTO:**
```php
/**
 * Fusiona y rankea resultados de busqueda nacionales y europeos.
 *
 * Aplica boost de +0.05 a resoluciones TJUE/TEDH cuando la busqueda es 'all'
 * para reflejar la primacia del derecho de la UE. Tambien aplica boost de
 * +0.02 a resoluciones de menos de 1 ano para favorecer doctrina fresca.
 * Deduplica por resolution_id para evitar que chunks distintos de la misma
 * resolucion aparezcan como resultados separados.
 *
 * @param array $results Resultados crudos de Qdrant (nacional + UE)
 * @param string $scope Ambito de busqueda: 'national', 'eu' o 'all'
 * @return array Resultados rankeados y deduplicados, ordenados por score desc
 */
```

**Ejemplo PROHIBIDO:**
```php
// Fusiona resultados  <-- Demasiado vago, no explica el por que
```

### 3.12 Directriz: Iconos SVG duotone

**Referencia:** `.agent/workflows/scss-estilos.md`

Se crean 12 iconos nuevos en la categoria `legal/` del sistema de iconos del ecosistema.

**Directorio:** `ecosistema_jaraba_core/images/icons/legal/`

Cada icono tiene dos versiones:
1. `{name}.svg` - Version outline (trazo)
2. `{name}-duotone.svg` - Version duotone (2 tonos con opacidad)

**Estructura del SVG duotone:**
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
  <!-- Capa de fondo (opacidad 0.3) -->
  <path d="..." fill="currentColor" opacity="0.3"/>
  <!-- Capa principal (trazo o relleno solido) -->
  <path d="..." stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Uso en Twig:**
```twig
{{ jaraba_icon('legal', 'gavel', { color: 'corporate', size: '24px' }) }}
{{ jaraba_icon('legal', 'scale-balance', { variant: 'duotone', color: 'impulse', size: '32px' }) }}
```

**12 iconos de la categoria `legal/`:**

| Icono | Nombre | Uso principal |
|-------|--------|---------------|
| Mazo de juez | `gavel` | Sentencias judiciales |
| Balanza | `scale-balance` | Justicia, resolucion |
| Libro de leyes | `law-book` | Normativa, legislacion |
| Pergamino | `scroll-decree` | Resoluciones administrativas |
| Lupa juridica | `search-legal` | Busqueda semantica |
| Cita | `citation` | Insercion de citas |
| Campana alerta | `alert-bell` | Alertas inteligentes |
| Bandera UE | `eu-flag` | Fuentes europeas |
| Bandera ES | `es-flag` | Fuentes nacionales |
| Grafo citas | `citation-graph` | Red de citas |
| Digest | `digest-mail` | Digest semanal |
| Escudo RGPD | `shield-privacy` | Secreto profesional |

### 3.13 Directriz: AI via abstraccion @ai.provider

**Referencia:** `.agent/workflows/ai-integration.md`, `docs/00_DIRECTRICES_PROYECTO.md`

NUNCA implementar clientes HTTP directos a APIs de IA. Siempre usar la abstraccion del modulo AI de Drupal.

**CORRECTO:**
```php
// Clasificar resolucion con Gemini via @ai.provider.
$provider = \Drupal::service('ai.provider');
$response = $provider->chat([
  new ChatMessage('system', $this->getClassificationPrompt()),
  new ChatMessage('user', mb_substr($resolutionText, 0, 8000)),
], 'gemini-2.0-flash', ['temperature' => 0.1, 'response_format' => 'json']);
```

**PROHIBIDO:**
```php
// NUNCA llamada HTTP directa a API de IA.
$client = new \GuzzleHttp\Client();
$response = $client->post('https://generativelanguage.googleapis.com/v1beta/...', [...]);
```

**Especializacion de proveedor IA por funcion en Legal Intelligence Hub:**

| Funcion | Proveedor | Razon |
|---------|-----------|-------|
| Clasificacion de resoluciones | Gemini 2.0 Flash | Strict Grounding, JSON schema, temperatura 0.1 |
| Resumen y ratio decidendi | Gemini 2.0 Flash | Strict Grounding, precision en lenguaje juridico |
| Embeddings nacionales | text-embedding-3-large (OpenAI) | 3072 dimensiones, precision en espanol |
| Embeddings UE multilingues | multilingual-e5-large (HuggingFace) | 1024 dim, multilingue EN/FR/ES/DE |
| Deteccion de intent en Copilot | Claude Haiku | Rapido y barato para clasificacion |

### 3.14 Directriz: Automaciones via hooks Drupal

**Referencia:** `.agent/workflows/drupal-eca-hooks.md`

Las automaciones del Legal Intelligence Hub se implementan via hooks nativos de Drupal, NO via ECA BPMN.

**CORRECTO:**
```php
/**
 * Implements hook_entity_insert().
 *
 * Cuando se indexa una nueva resolucion, verifica si contradice doctrina
 * citada en expedientes activos y genera alertas si es necesario.
 */
function jaraba_legal_intelligence_entity_insert(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() !== 'legal_resolution') {
    return;
  }
  // Verificar si la nueva resolucion afecta a resoluciones citadas en expedientes.
  \Drupal::service('jaraba_legal.alerts')->checkNewResolutionImpact($entity);
}
```

**PROHIBIDO:**
```yaml
# NUNCA usar ECA BPMN para automatizaciones del Legal Intelligence Hub
eca:
  id: legal_new_resolution
  events:
    - entity_insert:legal_resolution
```

**Hooks a implementar en Legal Intelligence Hub:**

| Hook | Entidad | Accion |
|------|---------|--------|
| `hook_entity_insert` | `legal_resolution` | Verificar impacto en expedientes activos, generar alertas |
| `hook_entity_update` | `legal_resolution` | Detectar cambio de estado (derogada, anulada), propagar alertas |
| `hook_cron` | Spiders | Ejecutar ingesta programada por fuente (diaria/semanal/mensual) |
| `hook_cron` | Pipeline NLP | Procesar cola de resoluciones pendientes de NLP |
| `hook_cron` | Digest | Enviar digest semanal personalizado (lunes 07:00 UTC) |
| `hook_cron` | Cleanup | Limpiar vectores huerfanos en Qdrant, purgar logs antiguos |

---

## 4. Arquitectura del Modulo

### 4.1 Nombre y ubicacion

```
web/modules/custom/jaraba_legal_intelligence/
```

### 4.2 Dependencias

```yaml
# jaraba_legal_intelligence.info.yml
name: 'Jaraba Legal Intelligence Hub'
type: module
description: 'AI-powered legal research with semantic search across national (ES)
  and European (EU/CEDH) sources. Pipeline NLP, Qdrant vector search,
  citation insertion, intelligent alerts.'
package: 'Jaraba ServiciosConecta'
core_version_requirement: ^11
php: 8.3
dependencies:
  - drupal:user
  - drupal:file
  - drupal:views
  - drupal:field_ui
  - drupal:taxonomy
  - drupal:datetime
  - ecosistema_jaraba_core:ecosistema_jaraba_core
configure: jaraba_legal_intelligence.settings
```

> **Nota:** Las dependencias externas (Apache Tika, spaCy, Qdrant client) se gestionan via Docker y FastAPI Python. El modulo AI de Drupal (`ai`) se inyecta via servicio `@ai.provider`, no como dependencia directa del info.yml. jaraba_servicios_conecta se consume via servicios, no como dependencia dura, para permitir uso independiente.

### 4.3 Estructura de directorios

```
jaraba_legal_intelligence/
├── jaraba_legal_intelligence.info.yml
├── jaraba_legal_intelligence.module
├── jaraba_legal_intelligence.install
├── jaraba_legal_intelligence.routing.yml
├── jaraba_legal_intelligence.services.yml
├── jaraba_legal_intelligence.libraries.yml
├── jaraba_legal_intelligence.permissions.yml
├── jaraba_legal_intelligence.links.menu.yml
├── jaraba_legal_intelligence.links.task.yml
├── jaraba_legal_intelligence.links.action.yml
├── config/
│   ├── install/
│   │   ├── jaraba_legal_intelligence.settings.yml
│   │   ├── jaraba_legal_intelligence.sources.yml
│   │   ├── taxonomy.vocabulary.legal_jurisdiction.yml
│   │   ├── taxonomy.vocabulary.legal_resolution_type.yml
│   │   ├── taxonomy.vocabulary.legal_issuing_body.yml
│   │   ├── taxonomy.vocabulary.legal_topic_fiscal.yml
│   │   ├── taxonomy.vocabulary.legal_topic_laboral.yml
│   │   ├── taxonomy.vocabulary.legal_topic_civil.yml
│   │   ├── taxonomy.vocabulary.legal_topic_mercantil.yml
│   │   ├── taxonomy.vocabulary.legal_topic_subvenciones.yml
│   │   └── taxonomy.vocabulary.eu_procedure_type.yml
│   └── schema/
│       └── jaraba_legal_intelligence.schema.yml
├── css/
│   └── legal-intelligence.css                      # CSS compilado (NO editar)
├── js/
│   ├── legal-search.js                             # Busqueda semantica frontend
│   ├── legal-results.js                            # Renderizado de resultados
│   ├── legal-facets.js                             # Filtros facetados interactivos
│   ├── legal-citation-graph.js                     # Visualizacion grafo de citas (D3.js)
│   ├── legal-citation-insert.js                    # Insercion de citas en expediente
│   └── legal-alerts.js                             # Gestion de alertas frontend
├── package.json
├── scss/
│   ├── main.scss                                   # Punto de entrada SCSS
│   ├── _variables-legal.scss                       # Variables locales del modulo
│   ├── _search.scss                                # Pagina de busqueda
│   ├── _results.scss                               # Tarjetas de resultados
│   ├── _resolution-detail.scss                     # Detalle de resolucion
│   ├── _facets.scss                                # Filtros facetados
│   ├── _citation-graph.scss                        # Grafo de citas
│   ├── _citation-insert.scss                       # Panel de insercion de citas
│   ├── _alerts.scss                                # Alertas y digest
│   ├── _badges.scss                                # Badges de estado legal
│   ├── _admin-dashboard.scss                       # Dashboard admin legal
│   ├── _public-summary.scss                        # Paginas publicas SEO
│   └── _digest-email.scss                          # Estilos email digest
├── src/
│   ├── Access/
│   │   ├── LegalResolutionAccessControlHandler.php
│   │   ├── LegalAlertAccessControlHandler.php
│   │   ├── LegalBookmarkAccessControlHandler.php
│   │   ├── LegalCitationAccessControlHandler.php
│   │   └── LegalSourceAccessControlHandler.php
│   ├── Controller/
│   │   ├── LegalSearchController.php               # Busqueda frontend + API
│   │   ├── LegalResolutionController.php            # Detalle, cita, similar, public
│   │   └── LegalAdminController.php                 # Dashboard admin, sync, stats
│   ├── Entity/
│   │   ├── LegalResolution.php                      # Entidad principal (35+ campos)
│   │   ├── LegalSource.php                          # Fuente de datos configurable
│   │   ├── LegalAlert.php                           # Suscripcion de alerta
│   │   ├── LegalBookmark.php                        # Favorito del profesional
│   │   └── LegalCitation.php                        # Cita insertada en expediente
│   ├── Form/
│   │   ├── LegalSearchForm.php                      # Formulario de busqueda
│   │   ├── LegalAlertForm.php                       # Formulario de alertas
│   │   └── LegalSettingsForm.php                    # Configuracion general
│   ├── ListBuilder/
│   │   ├── LegalResolutionListBuilder.php
│   │   ├── LegalAlertListBuilder.php
│   │   ├── LegalBookmarkListBuilder.php
│   │   ├── LegalCitationListBuilder.php
│   │   └── LegalSourceListBuilder.php
│   ├── Plugin/
│   │   ├── QueueWorker/
│   │   │   ├── LegalIngestionWorker.php             # Cola de ingesta de resoluciones
│   │   │   └── LegalNlpWorker.php                   # Cola de procesamiento NLP
│   │   └── Action/
│   │       └── ReindexResolutionAction.php
│   ├── Service/
│   │   ├── LegalSearchService.php                   # Busqueda semantica + facetas
│   │   ├── LegalIngestionService.php                # Orquestacion de ingesta
│   │   ├── LegalNlpPipelineService.php              # Pipeline NLP 9 etapas
│   │   ├── LegalAlertService.php                    # Gestion de alertas
│   │   ├── LegalCitationService.php                 # Insercion de citas
│   │   ├── LegalDigestService.php                   # Digest semanal personalizado
│   │   ├── LegalMergeRankService.php                # Merge & rank nacional + UE
│   │   └── Spider/
│   │       ├── SpiderInterface.php                  # Contrato para todos los spiders
│   │       ├── CendojSpider.php                     # CENDOJ (jurisprudencia)
│   │       ├── BoeSpider.php                        # BOE (legislacion)
│   │       ├── DgtSpider.php                        # DGT (consultas vinculantes)
│   │       ├── TeacSpider.php                       # TEAC (resoluciones economico-admin)
│   │       ├── EurLexSpider.php                     # EUR-Lex SPARQL (legislacion UE)
│   │       ├── CuriaSpider.php                      # CURIA/TJUE (jurisprudencia UE)
│   │       ├── HudocSpider.php                      # HUDOC/TEDH (derechos humanos)
│   │       └── EdpbSpider.php                       # EDPB (directrices RGPD)
│   └── EventSubscriber/
│       └── LegalEventSubscriber.php
├── templates/
│   ├── legal-search-page.html.twig                  # Pagina principal de busqueda
│   ├── legal-search-results.html.twig               # Resultados de busqueda
│   ├── legal-resolution-detail.html.twig            # Detalle de resolucion
│   ├── legal-citation-insert.html.twig              # Panel de insercion de cita
│   ├── legal-admin-dashboard.html.twig              # Dashboard admin
│   ├── legal-digest-email.html.twig                 # Email digest semanal
│   └── partials/
│       ├── _resolution-card.html.twig               # Tarjeta de resolucion
│       ├── _citation-badge.html.twig                # Badge estado legal
│       ├── _search-facets.html.twig                 # Filtros facetados
│       ├── _citation-graph.html.twig                # Mini-grafo de citas
│       ├── _eu-primacy-indicator.html.twig          # Indicadores UE
│       ├── _alert-card.html.twig                    # Tarjeta de alerta
│       ├── _digest-preview.html.twig                # Preview digest
│       └── _source-status.html.twig                 # Estado fuente admin
├── scripts/
│   └── nlp/
│       ├── requirements.txt                         # spaCy, tika, qdrant-client, fastapi
│       ├── pipeline.py                              # Pipeline NLP FastAPI endpoints
│       ├── legal_ner.py                             # NER juridico custom spaCy
│       ├── embeddings.py                            # Generacion de embeddings
│       └── qdrant_client.py                         # Cliente Qdrant para Python
└── tests/
    └── src/
        ├── Unit/
        │   ├── LegalSearchServiceTest.php
        │   ├── LegalNlpPipelineServiceTest.php
        │   ├── LegalMergeRankServiceTest.php
        │   └── LegalCitationServiceTest.php
        └── Kernel/
            ├── LegalResolutionEntityTest.php
            ├── LegalIngestionTest.php
            └── LegalAlertServiceTest.php
```

**Total de archivos: ~75+**

### 4.4 Compilacion SCSS

**package.json:**
```json
{
  "name": "jaraba-legal-intelligence",
  "version": "1.0.0",
  "description": "SCSS del Legal Intelligence Hub - Busqueda juridica inteligente",
  "scripts": {
    "build": "sass scss/main.scss:css/legal-intelligence.css --style=compressed --no-source-map",
    "build:all": "npm run build",
    "watch": "sass --watch scss/main.scss:css/legal-intelligence.css --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.71.0"
  }
}
```

**Comandos de compilacion:**
```bash
# Instalar dependencias (primera vez):
lando ssh -c "cd /app/web/modules/custom/jaraba_legal_intelligence && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm install"

# Compilar SCSS:
lando ssh -c "cd /app/web/modules/custom/jaraba_legal_intelligence && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"

# Limpiar cache de Drupal:
lando drush cr
```

---

## 5. Estado por Fases

| Fase | Descripcion | Docs Tecnicos | Estado | Horas Est. | Dependencia |
|------|-------------|---------------|--------|------------|-------------|
| Fase 0 | Infraestructura y Scaffolding | 178B | 🔶 **Planificada** | 30-40h | ecosistema_jaraba_core |
| Fase 1 | Entidades Core + Spiders Nacionales | 178, 178B | ⬜ Futura | 80-100h | Fase 0 |
| Fase 2 | Pipeline NLP 9 Etapas | 178, 178B | ⬜ Futura | 70-90h | Fase 1 |
| Fase 3 | Motor de Busqueda + Frontend | 178, 178B | ⬜ Futura | 80-100h | Fase 2 |
| Fase 4 | Fuentes Europeas | 178A, 178B | ⬜ Futura | 60-80h | Fase 2 |
| Fase 5 | Alertas + Digest Semanal | 178, 178B | ⬜ Futura | 50-65h | Fase 3 |
| Fase 6 | Integracion ServiciosConecta | 178, 178B | ⬜ Futura | 40-55h | Fase 3, Fase 5 |
| Fase 7 | Dashboard Admin | 178B | ⬜ Futura | 35-45h | Fases 1-6 |
| Fase 8 | Dashboard Profesional + SEO | 178, 178B | ⬜ Futura | 40-50h | Fase 7 |
| Fase 9 | QA + Go-Live | 178B | ⬜ Futura | 45-60h | Fases 0-8 |

**Total entidades:** 5 Content Entities + 9 Taxonomias + tabla `legal_citation_graph`
**Total servicios:** 7 Services + 8 Spiders + 2 QueueWorkers
**Total horas:** 530-685 horas (26-34 semanas)
**Coste estimado:** 23.850-30.825 EUR (a 45 EUR/hora equipo EDI)

---

## 6. FASE 0: Infraestructura y Scaffolding

### 6.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Prerequisito obligatorio. Sin scaffolding, Qdrant collections, Docker NLP y taxonomias, ninguna otra fase puede comenzar. |
| **Dependencias externas** | Solo `ecosistema_jaraba_core` (existente) |
| **Complejidad** | 🟢 Baja (configuracion, no logica de negocio) |
| **Horas estimadas** | 30-40h |

### 6.2 Entidades

No se crean entidades en esta fase.

### 6.3 Taxonomias (9 vocabularios)

| Vocabulario | Machine Name | Tipo | Terminos iniciales |
|-------------|-------------|------|-------------------|
| Jurisdiccion | `legal_jurisdiction` | Plano | Civil, Penal, Laboral, Contencioso-Administrativo, Fiscal, Mercantil, Social |
| Tipo Resolucion | `legal_resolution_type` | Plano | Sentencia, Auto, Consulta Vinculante, Resolucion TEAC, Informe IGAE, Consulta BOICAC, Circular, Directiva UE, Reglamento UE |
| Organo Emisor | `legal_issuing_body` | Plano | TS, TC, AN, TSJ, AP, DGT, TEAC, IGAE, ICAC, DGRN, AEPD, TJUE, TEDH, EDPB |
| Materia Fiscal | `legal_topic_fiscal` | Jerarquico | IRPF, IS, IVA, ITP, Sucesiones, Aduanas, Procedimiento tributario |
| Materia Laboral | `legal_topic_laboral` | Jerarquico | Despido, Salarios, SS, Prevencion, Negociacion colectiva, ERE/ERTE |
| Materia Civil | `legal_topic_civil` | Jerarquico | Contratos, Familia, Herencias, Propiedad, Responsabilidad, Arrendamientos |
| Materia Mercantil | `legal_topic_mercantil` | Jerarquico | Sociedades, Concursal, Propiedad industrial, Competencia, Bancario |
| Materia Subvenciones | `legal_topic_subvenciones` | Jerarquico | Justificacion, Reintegro, Auditoria, Bases reguladoras, Convocatoria |
| Tipo Procedimiento UE | `eu_procedure_type` | Plano | Cuestion prejudicial, Recurso por incumplimiento, Recurso de anulacion, Accion por omision |

### 6.4 Infraestructura Docker

- Contenedor Apache Tika (puerto 9998) para extraccion de texto de PDFs.
- Contenedor FastAPI Python (puerto 8001) con spaCy `es_core_news_lg` para segmentacion y NER juridico.
- Coleccion Qdrant `legal_intelligence` (3072 dims, Cosine, on_disk, hnsw_m=32, ef_construct=200).
- Coleccion Qdrant `legal_intelligence_eu` (1024 dims para multilingual-e5-large, Cosine, on_disk).

### 6.5 Services

Ninguno en esta fase.

### 6.6 Controllers

Ninguno en esta fase.

### 6.7 Templates y Parciales Twig

Ninguno en esta fase.

### 6.8 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `jaraba_legal_intelligence.info.yml` | Definicion del modulo |
| `jaraba_legal_intelligence.module` | Hook implementations (preprocess_html, entity_insert, cron) |
| `jaraba_legal_intelligence.install` | hook_install() para indices DB + tabla legal_citation_graph |
| `jaraba_legal_intelligence.permissions.yml` | 7 permisos RBAC |
| `jaraba_legal_intelligence.routing.yml` | Rutas frontend, API, admin |
| `jaraba_legal_intelligence.services.yml` | 7 servicios + logger channel |
| `jaraba_legal_intelligence.libraries.yml` | Librerias CSS/JS |
| `jaraba_legal_intelligence.links.menu.yml` | Enlace en Structure |
| `jaraba_legal_intelligence.links.task.yml` | Pestanas en Content |
| `jaraba_legal_intelligence.links.action.yml` | Botones "Agregar" |
| `config/install/*.yml` | 9 vocabularios + settings + sources |
| `config/schema/*.yml` | Schema de configuracion |
| `package.json` | Compilacion SCSS Dart Sass |
| `scss/_variables-legal.scss` | Variables locales del modulo |
| `scss/main.scss` | Punto de entrada SCSS |
| `docker-compose.legal.yml` | Contenedores Tika + FastAPI NLP |
| `scripts/nlp/requirements.txt` | Dependencias Python |

### 6.9 Archivos a Modificar

Ninguno en esta fase.

### 6.10 SCSS: Directrices

- Variables locales `$legal-*` como fallback.
- NUNCA redefinir `$ej-*`.
- Usar `color.adjust()`, NUNCA `darken()`/`lighten()`.
- Compilacion via `npm run build`.

### 6.11 Verificacion

- [ ] Modulo instalable con `drush en jaraba_legal_intelligence` sin errores
- [ ] 9 vocabularios de taxonomia creados con terminos seed
- [ ] Colecciones Qdrant `legal_intelligence` y `legal_intelligence_eu` creadas
- [ ] Contenedor Tika responde en `http://tika:9998/tika`
- [ ] Contenedor FastAPI NLP responde en `http://localhost:8001/health`
- [ ] SCSS compila sin errores con `npm run build`
- [ ] 7 permisos visibles en `/admin/people/permissions`
- [ ] Tabla `legal_citation_graph` creada via `hook_install()`

---

## 7. FASE 1: Entidades Core + Spiders Nacionales

### 7.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Sin entidades ni ingesta de datos, no hay nada que buscar. Esta fase crea la base de datos de resoluciones y los conectores a fuentes nacionales. |
| **Dependencias externas** | Fase 0 (infraestructura Docker, Qdrant, taxonomias) |
| **Entidades** | 5 (`legal_resolution`, `legal_source`, `legal_alert`, `legal_bookmark`, `legal_citation`) |
| **Complejidad** | 🟡 Media-Alta (LegalResolution tiene 35+ campos incluyendo campos EU del doc 178A) |
| **Horas estimadas** | 80-100h |

### 7.2 Entidades

#### 7.2.1 Entidad `legal_resolution` (35+ campos)

**Tipo:** ContentEntity
**ID:** `legal_resolution`
**Base table:** `legal_resolution`
**Descripcion:** Entidad principal del modulo. Almacena resoluciones judiciales, consultas vinculantes, normativa y doctrina administrativa. Incluye campos nacionales (ES) y campos europeos (UE/CEDH, doc 178A). Los campos AI-generated (abstract_ai, key_holdings, topics) se rellenan por el pipeline NLP de la Fase 2.

**Campos principales (35+):**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id`, `uuid` | serial, uuid | Identificadores |
| `source_id` | string(32) | Fuente: cendoj, boe, dgt, teac, tjue, eurlex, tedh, edpb... |
| `external_ref` | string(128) | Referencia oficial (V0123-24, STS 1234/2024). UNIQUE |
| `content_hash` | string(64) | SHA-256 para deduplicacion |
| `title` | string(512) | Titulo de la resolucion |
| `resolution_type` | string(64) | sentencia, auto, consulta_vinculante, resolucion, directiva... |
| `issuing_body` | string(128) | TS, DGT, TEAC, TJUE, TEDH... |
| `jurisdiction` | string(64) | civil, penal, laboral, fiscal, contencioso... |
| `date_issued`, `date_published` | datetime | Fechas de emision y publicacion |
| `status_legal` | string(32) | vigente, derogada, anulada, superada, parcialmente_derogada |
| `full_text` | text_long | Texto integro de la resolucion |
| `original_url` | uri | URL publica original |
| `abstract_ai` | text_long | Resumen IA (3-5 lineas, generado por Gemini) |
| `key_holdings` | text_long | Ratio decidendi extraida por IA |
| `topics` | string(2048) | Temas clasificados (JSON array) |
| `cited_legislation` | string(4096) | Normas citadas (JSON) |
| `celex_number` | string(32) | Identificador CELEX EUR-Lex (campos UE) |
| `ecli` | string(64) | European Case Law Identifier |
| `case_number` | string(64) | Numero de asunto (C-415/11) |
| `procedure_type` | string(64) | prejudicial, infraccion, anulacion... |
| `respondent_state` | string(3) | Estado demandado (ISO 3166-1) |
| `cedh_articles` | string(512) | Articulos CEDH alegados/violados (JSON) |
| `eu_legal_basis` | string(2048) | Base juridica UE (JSON) |
| `advocate_general` | string(128) | Nombre del Abogado General |
| `importance_level` | integer | 1=key case, 2=media, 3=baja |
| `language_original` | string(3) | Idioma original. Default: 'es' |
| `impact_spain` | text_long | Impacto en derecho espanol (IA) |
| `vector_ids` | string(4096) | IDs de chunks en Qdrant (JSON) |
| `qdrant_collection` | string(64) | Coleccion: legal_intelligence o legal_intelligence_eu |
| `seo_slug` | string(255) | Slug URL para paginas publicas |
| `created`, `changed` | timestamps | Fechas de creacion y modificacion |
| `last_nlp_processed` | timestamp | Ultima vez procesado por pipeline NLP |

#### 7.2.2 Entidad `legal_source`

**Tipo:** ContentEntity
**ID:** `legal_source`
**Campos:** id, uuid, name, machine_name, base_url, spider_class, frequency (daily/weekly/monthly), is_active, last_sync_at, total_documents, error_count, last_error, priority, created, changed.

#### 7.2.3 Entidad `legal_alert`

**Tipo:** ContentEntity
**ID:** `legal_alert`
**Campos:** id, uuid, label, provider_id (FK user), group_id (FK group), alert_type (10 tipos), severity, filter_sources (JSON), filter_topics (JSON), filter_jurisdictions (JSON), channels (JSON), is_active, last_triggered, trigger_count, created.

#### 7.2.4 Entidad `legal_bookmark`

**Tipo:** ContentEntity
**ID:** `legal_bookmark`
**Campos:** id, uuid, user_id (FK user), resolution_id (FK legal_resolution), notes, folder, created.

#### 7.2.5 Entidad `legal_citation`

**Tipo:** ContentEntity
**ID:** `legal_citation`
**Campos:** id, uuid, resolution_id (FK legal_resolution), expediente_id (FK expediente), group_id (FK group), citation_format, citation_text, inserted_by (FK user), created.

### 7.3 Services

#### 7.3.1 `LegalIngestionService`

Orquesta la ingesta de resoluciones: selecciona el spider adecuado, deduplicacion por hash, encolado en pipeline NLP.

#### 7.3.2 `SpiderInterface` + 4 Spiders Nacionales

```php
interface SpiderInterface {
  public function getId(): string;
  public function crawl(array $options = []): array;
  public function getFrequency(): string;
}
```

| Spider | Fuente | Frecuencia | Contenido |
|--------|--------|------------|-----------|
| `CendojSpider` | CENDOJ | Diaria | Jurisprudencia TS, AN, TSJ, AP |
| `BoeSpider` | BOE Open Data | Diaria | Legislacion, disposiciones |
| `DgtSpider` | DGT Hacienda | Semanal | Consultas vinculantes |
| `TeacSpider` | TEAC | Semanal | Resoluciones economico-admin |

### 7.4 Controllers

Ninguno en esta fase (se crean en Fase 3).

### 7.5 Templates y Parciales Twig

Ninguno en esta fase.

### 7.6 Frontend Assets

Ninguno en esta fase.

### 7.7 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `src/Entity/LegalResolution.php` | Entidad principal (35+ campos) |
| `src/Entity/LegalSource.php` | Fuente de datos |
| `src/Entity/LegalAlert.php` | Suscripcion de alerta |
| `src/Entity/LegalBookmark.php` | Favorito |
| `src/Entity/LegalCitation.php` | Cita en expediente |
| `src/Access/*AccessControlHandler.php` | 5 handlers de acceso |
| `src/ListBuilder/*ListBuilder.php` | 5 list builders |
| `src/Form/LegalResolutionForm.php` | Formulario de resolucion |
| `src/Service/LegalIngestionService.php` | Servicio de ingesta |
| `src/Service/Spider/SpiderInterface.php` | Contrato de spiders |
| `src/Service/Spider/CendojSpider.php` | Spider CENDOJ |
| `src/Service/Spider/BoeSpider.php` | Spider BOE |
| `src/Service/Spider/DgtSpider.php` | Spider DGT |
| `src/Service/Spider/TeacSpider.php` | Spider TEAC |
| `src/Plugin/QueueWorker/LegalIngestionWorker.php` | QueueWorker ingesta |

### 7.8 Archivos a Modificar

Ninguno (modulo autocontenido).

### 7.9 SCSS: Directrices

No aplica en esta fase (backend only).

### 7.10 Verificacion

- [ ] 5 entidades instalables con `drush entity:updates` sin errores
- [ ] Entidades visibles en `/admin/content/` con pestanas
- [ ] Entidades configurables en `/admin/structure/` con Field UI
- [ ] SpiderInterface implementado por 4 spiders nacionales
- [ ] CendojSpider descarga al menos 10 resoluciones de prueba
- [ ] BoeSpider descarga al menos 5 disposiciones de prueba
- [ ] Deduplicacion por `content_hash` funciona correctamente
- [ ] LegalIngestionWorker encola resoluciones para NLP

---

## 8. FASE 2: Pipeline NLP 9 Etapas

### 8.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Sin NLP, las resoluciones son texto crudo. Esta fase extrae informacion estructurada, genera resumenes IA e indexa en Qdrant para busqueda semantica. |
| **Dependencias** | Fase 1 (entidades + ingesta), Docker NLP (Fase 0) |
| **Complejidad** | 🔴 Alta (pipeline multi-etapa, integracion Tika + spaCy + Gemini + Qdrant) |
| **Horas estimadas** | 70-90h |

### 8.2 Services

#### 8.2.1 `LegalNlpPipelineService`

Pipeline de 9 etapas:

1. **Extraccion** (Apache Tika): PDF/HTML -> texto plano limpio
2. **Normalizacion** (Python/regex): Limpieza encoding, saltos, cabeceras
3. **Segmentacion** (spaCy `es_core_news_lg`): Division en antecedentes, fundamentos, fallo
4. **NER Juridico** (modelo custom spaCy): Extraccion de leyes, articulos, tribunales, fechas
5. **Clasificacion** (Gemini 2.0 Flash): Temas, jurisdiccion, tipo de resolucion
6. **Resumen** (Gemini 2.0 Flash): Abstract de 3-5 lineas + ratio decidendi
7. **Embeddings** (text-embedding-3-large): Vectorizacion de chunks de 512 tokens
8. **Indexacion** (Qdrant): Insercion con payload filtrable
9. **Grafos** (MariaDB): Construccion de red de citas (`legal_citation_graph`)

### 8.3 Python FastAPI

4 scripts Python en `scripts/nlp/`:

| Script | Responsabilidad | Endpoints |
|--------|----------------|-----------|
| `pipeline.py` | FastAPI server, orquestacion | `POST /api/segment`, `POST /api/ner`, `GET /health` |
| `legal_ner.py` | NER juridico con spaCy custom | Usado por `pipeline.py` |
| `embeddings.py` | Generacion de embeddings | Usado por LegalNlpPipelineService (PHP) |
| `qdrant_client.py` | Operaciones Qdrant desde Python | Usado por `pipeline.py` |

### 8.4 QueueWorkers

| Plugin | Cola | Descripcion |
|--------|------|-------------|
| `LegalNlpWorker` | `legal_nlp_pipeline` | Procesa resoluciones pendientes de NLP (1 a 1, timeout 120s) |

### 8.5 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `src/Service/LegalNlpPipelineService.php` | Pipeline PHP de 9 etapas |
| `src/Plugin/QueueWorker/LegalNlpWorker.php` | QueueWorker NLP |
| `scripts/nlp/pipeline.py` | FastAPI server NLP |
| `scripts/nlp/legal_ner.py` | NER juridico spaCy |
| `scripts/nlp/embeddings.py` | Generacion embeddings |
| `scripts/nlp/qdrant_client.py` | Cliente Qdrant Python |

### 8.6 Verificacion

- [ ] Pipeline procesa una resolucion del BOE end-to-end
- [ ] Tika extrae texto de PDF correctamente
- [ ] spaCy segmenta en antecedentes/fundamentos/fallo
- [ ] NER extrae al menos 3 entidades juridicas de una sentencia
- [ ] Gemini clasifica temas y genera resumen con strict grounding
- [ ] Embeddings generados e insertados en Qdrant (verificar con `curl`)
- [ ] Tabla `legal_citation_graph` contiene relaciones entre resoluciones
- [ ] LegalNlpWorker procesa cola sin errores

---

## 9. FASE 3: Motor de Busqueda + Frontend

### 9.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Primera interfaz visible para Elena. Busqueda semantica en lenguaje natural con resultados enriquecidos, filtros facetados y acciones directas. |
| **Dependencias** | Fase 2 (pipeline NLP + Qdrant indexado) |
| **Complejidad** | 🟡 Media-Alta (frontend complejo con facetas, grafos, insercion de citas) |
| **Horas estimadas** | 80-100h |

### 9.2 Services

#### 9.2.1 `LegalSearchService`

Busqueda semantica en Qdrant con:
- Embedding de la query del usuario
- Busqueda por vector similarity (cosine, threshold 0.65)
- Filtros facetados (fuente, jurisdiccion, tipo, fecha, organo, importancia)
- Lookup exacto por referencia (`V0123-24`, `STS 1234/2024`)
- Busqueda de resoluciones similares (findSimilar)

#### 9.2.2 `LegalCitationService`

Generacion de citas formateadas en 4 formatos:
- **Formal** (para escritos judiciales): `Segun establece la Consulta Vinculante V0123-24 de la DGT, de fecha 15/03/2024: «[ratio]».`
- **Resumida** (para informes): `V0123-24 (DGT, 15/03/2024): [ratio]`
- **Bibliografica** (para publicaciones): `DGT. [titulo]. V0123-24, 15/03/2024.`
- **Nota al pie** (para documentos academicos): `Vid. la Consulta Vinculante V0123-24, DGT (15/03/2024).`

### 9.3 Controllers

| Ruta | Controller | Metodo | Descripcion |
|------|-----------|--------|-------------|
| `/legal/search` | `LegalSearchController` | `search` | Pagina principal de busqueda |
| `/legal/{source_id}/{external_ref}` | `LegalResolutionController` | `view` | Detalle de resolucion |
| `/legal/cite/{resolution_id}/{format}` | `LegalResolutionController` | `cite` | Insercion de cita |
| `/legal/{resolution_id}/similar` | `LegalResolutionController` | `similar` | Resoluciones similares |
| `/api/v1/legal/search` | `LegalSearchController` | `apiSearch` | API REST busqueda |
| `/api/v1/legal/resolutions/{id}` | `LegalResolutionController` | `apiGet` | API REST detalle |
| `/api/v1/legal/bookmark` | `LegalResolutionController` | `apiBookmark` | API REST favoritos |

### 9.4 Templates y Parciales Twig

| Template | Descripcion |
|----------|-------------|
| `legal-search-page.html.twig` | Pagina principal con formulario y resultados |
| `legal-search-results.html.twig` | Lista de resultados con tarjetas |
| `legal-resolution-detail.html.twig` | Detalle completo de resolucion |
| `legal-citation-insert.html.twig` | Panel de insercion en slide-panel |
| `partials/_resolution-card.html.twig` | Tarjeta de resolucion |
| `partials/_citation-badge.html.twig` | Badge vigente/derogada/superada |
| `partials/_search-facets.html.twig` | Filtros facetados |
| `partials/_citation-graph.html.twig` | Mini-grafo de citas |

### 9.5 Frontend Assets

| Archivo | Descripcion |
|---------|-------------|
| `js/legal-search.js` | Busqueda con debounce, typeahead |
| `js/legal-results.js` | Renderizado dinamico de resultados |
| `js/legal-facets.js` | Filtros facetados interactivos |
| `scss/_search.scss` | Estilos pagina busqueda |
| `scss/_results.scss` | Estilos tarjetas resultados |
| `scss/_resolution-detail.scss` | Estilos detalle |
| `scss/_facets.scss` | Estilos filtros facetados |

### 9.6 Verificacion

- [ ] Busqueda semantica devuelve resultados relevantes para "tributacion criptomonedas IRPF"
- [ ] Filtros por jurisdiccion, tipo, fecha y organo funcionan correctamente
- [ ] Lookup exacto por referencia (V0123-24) devuelve la resolucion correcta
- [ ] Tarjeta de resolucion muestra: titulo, organo, fecha, badge estado, resumen IA, ratio
- [ ] Detalle muestra texto completo, legislacion citada, resoluciones similares
- [ ] Insercion de cita genera texto formateado correctamente en 4 formatos
- [ ] Mini-grafo de citas navega a resoluciones relacionadas
- [ ] API REST `/api/v1/legal/search` devuelve JSON con resultados y facetas
- [ ] Layout full-width sin regiones de Drupal
- [ ] SCSS compilado sin errores, variables `$legal-*` con fallback `var(--ej-*)`

---

## 10. FASE 4: Fuentes Europeas

### 10.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Completar cobertura normativa con TJUE, TEDH, EUR-Lex. Sin dimension europea, el hub es incompleto para practica profesional real. |
| **Dependencias** | Fase 2 (pipeline NLP reutilizable) |
| **Complejidad** | 🟡 Media-Alta (SPARQL, multilingue, merge & rank) |
| **Horas estimadas** | 60-80h |

### 10.2 Spiders UE (4)

| Spider | Fuente | Acceso Tecnico | Frecuencia |
|--------|--------|---------------|------------|
| `EurLexSpider` | EUR-Lex Cellar | SPARQL endpoint + REST API Open Data | Semanal |
| `CuriaSpider` | CURIA/TJUE | EUR-Lex SPARQL (sector 6) + web scraping CURIA | Semanal |
| `HudocSpider` | HUDOC/TEDH | REST API JSON con filtros pais/articulo/fecha | Semanal |
| `EdpbSpider` | EDPB | Web scraping + RSS feeds | Mensual |

### 10.3 Embeddings Multilingues

- Corpus europeo usa `multilingual-e5-large` (1024 dims) en coleccion `legal_intelligence_eu`
- Abstracts siempre en espanol (traduccion automatica con Gemini si fuente EN/FR)
- Texto original disponible en idioma de la fuente

### 10.4 Services

#### 10.4.1 `LegalMergeRankService`

Fusiona resultados de `legal_intelligence` (nacional) y `legal_intelligence_eu` (europeo):
- Score boost +0.05 para TJUE/TEDH (primacia UE)
- Score boost +0.02 para resoluciones < 1 ano (frescura)
- Deduplicacion por `resolution_id`
- Indicadores visuales: bandera ES/UE/CEDH en cada resultado

### 10.5 Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `src/Service/Spider/EurLexSpider.php` | Spider EUR-Lex SPARQL |
| `src/Service/Spider/CuriaSpider.php` | Spider CURIA/TJUE |
| `src/Service/Spider/HudocSpider.php` | Spider HUDOC/TEDH |
| `src/Service/Spider/EdpbSpider.php` | Spider EDPB |
| `src/Service/LegalMergeRankService.php` | Merge & rank nacional+UE |
| `templates/partials/_eu-primacy-indicator.html.twig` | Badges UE |

### 10.6 Verificacion

- [ ] EUR-Lex Spider descarga directivas y reglamentos vigentes via SPARQL
- [ ] CURIA Spider descarga sentencias TJUE desde 2000
- [ ] HUDOC Spider descarga sentencias TEDH contra Espana
- [ ] Embeddings multilingues indexados en `legal_intelligence_eu`
- [ ] Busqueda unificada "libre circulacion capitales ISD" retorna TJUE C-127/12 + DGT consultas
- [ ] Merge & rank aplica boost UE correctamente
- [ ] Badges de primacia, efecto directo y transposicion visibles
- [ ] Prompt extendido para clasificacion de resoluciones europeas funciona

---

## 11. FASE 5: Alertas + Digest Semanal

### 11.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Proactividad: Elena recibe notificaciones cuando una resolucion citada en su expediente es anulada o cuando nueva doctrina relevante se publica. Digest semanal personalizado por area de practica. |
| **Dependencias** | Fase 3 (busqueda semantica para matching de alertas) |
| **Complejidad** | 🟡 Media (logica de matching + email) |
| **Horas estimadas** | 50-65h |

### 11.2 Services

#### 11.2.1 `LegalAlertService`

- `checkNewResolutionImpact($resolution)`: Al indexar nueva resolucion, verifica si afecta a expedientes activos.
- `processAlerts()`: Ejecutado por cron, evalua suscripciones de alertas contra nuevas resoluciones.
- Tipos de alertas: resolucion_anulada, cambio_criterio, nueva_doctrina, normativa_modificada, plazo_procesal, sentencia_tjue_espana, sentencia_tedh, directriz_edpb, plazo_transposicion, conclusiones_ag.

#### 11.2.2 `LegalDigestService`

- `generateWeeklyDigest($providerId)`: Genera resumen personalizado de resoluciones indexadas en ultimos 7 dias que coinciden con areas de practica del profesional.
- `sendDigests()`: Ejecutado por cron lunes 07:00 UTC.
- Template email `legal-digest-email.html.twig` con max 10 resoluciones.

### 11.3 Controllers

| Ruta | Controller | Descripcion |
|------|-----------|-------------|
| `/api/v1/legal/alerts` | `LegalSearchController` | CRUD alertas (GET/POST/PATCH/DELETE) |
| `/api/v1/legal/digest/preview` | `LegalSearchController` | Preview del digest |

### 11.4 Templates

| Template | Descripcion |
|----------|-------------|
| `legal-digest-email.html.twig` | Email digest semanal |
| `partials/_alert-card.html.twig` | Tarjeta de alerta |
| `partials/_digest-preview.html.twig` | Preview del digest |

### 11.5 SCSS

| Archivo | Descripcion |
|---------|-------------|
| `scss/_alerts.scss` | Estilos alertas y digest |
| `scss/_digest-email.scss` | Estilos email digest (inline) |

### 11.6 Verificacion

- [ ] Crear alerta por topic + jurisdiccion funciona desde slide-panel
- [ ] Al indexar resolucion que coincide con alerta, se dispara notificacion
- [ ] Alerta critica por resolucion anulada genera email + push + in-app
- [ ] Digest semanal genera email con max 10 resoluciones relevantes
- [ ] Preview del digest accesible via API
- [ ] Limites por plan respetados (Starter: 3 alertas, Pro: 20, Enterprise: ilimitado)

---

## 12. FASE 6: Integracion ServiciosConecta

### 12.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Integracion nativa con Copilot de Servicios (doc 93) y Buzon de Confianza (doc 88): buscar jurisprudencia desde el chat y insertar citas en escritos del expediente. |
| **Dependencias** | Fase 3 (busqueda), Fase 5 (alertas) |
| **Complejidad** | 🟡 Media (bridge entre modulos, skill de Copilot) |
| **Horas estimadas** | 40-55h |

### 12.2 Services

#### 12.2.1 `LegalCitationService` (extendido)

- `attachToExpediente($resolutionId, $expedienteId, $format)`: Vincula resolucion a expediente del Buzon de Confianza con cita formateada.
- `getExpedienteReferences($expedienteId)`: Lista resoluciones vinculadas a un expediente.
- `detachFromExpediente($citationId)`: Desvincula resolucion.

#### 12.2.2 Copilot Bridge (skill `legal_search`)

Nuevo intent `legal_search` para el Copilot de Servicios (doc 93):
- Trigger: deteccion de keywords `jurisprudencia|resolucion|sentencia|consulta DGT|doctrina|normativa`
- Flow: Intent detection -> Extraccion entidades -> Query Qdrant -> Top 5 resultados -> Respuesta conversacional con citas -> Botones [Insertar en expediente] [Ver completo] [Buscar similares]

### 12.3 Archivos a Crear/Modificar

| Archivo | Accion | Descripcion |
|---------|--------|-------------|
| `src/Service/LegalCitationService.php` | Extender | Metodos de vinculacion expediente |
| Copilot agent (jaraba_servicios_conecta) | Modificar | Anadir skill `legal_search` |

### 12.4 Verificacion

- [ ] Copilot responde a "busca jurisprudencia sobre clausulas abusivas" con resultados del Legal Intelligence Hub
- [ ] Boton "Insertar en expediente" crea LegalCitation vinculada al expediente activo
- [ ] Lista de referencias en expediente muestra resoluciones vinculadas
- [ ] Al desvincular, se elimina la LegalCitation

---

## 13. FASE 7: Dashboard Admin

### 13.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Visibilidad para el administrador de plataforma: estado de fuentes, metricas de ingesta, volumen Qdrant, errores de pipeline. |
| **Dependencias** | Fases 1-6 (todas las fuentes y servicios) |
| **Complejidad** | 🟢 Baja-Media (dashboard de lectura, sin logica compleja) |
| **Horas estimadas** | 35-45h |

### 13.2 Controllers

| Ruta | Controller | Descripcion |
|------|-----------|-------------|
| `/admin/config/jaraba/legal-intelligence` | `LegalSettingsForm` | Configuracion general |
| `/admin/config/jaraba/legal-intelligence/dashboard` | `LegalAdminController` | Dashboard admin |
| `/admin/config/jaraba/legal-intelligence/sync/{source_id}` | `LegalAdminController` | Forzar sync |
| `/api/v1/admin/legal/stats` | `LegalAdminController` | API estadisticas |
| `/api/v1/admin/legal/sources` | `LegalAdminController` | API estado fuentes |

### 13.3 Templates

| Template | Descripcion |
|----------|-------------|
| `legal-admin-dashboard.html.twig` | Dashboard admin con metricas |
| `partials/_source-status.html.twig` | Estado de cada fuente |

### 13.4 Metricas del Dashboard

| Metrica | Fuente | Descripcion |
|---------|--------|-------------|
| Total resoluciones | MariaDB `COUNT(*)` | Total de resoluciones indexadas |
| Por fuente | MariaDB `GROUP BY source_id` | Desglose por fuente |
| Ultimo sync | Campo `last_sync_at` de `legal_source` | Frescura de cada fuente |
| Errores pipeline | Log + campo `error_count` | Resoluciones con NLP fallido |
| Volumen Qdrant | API Qdrant `/collections/*/` | GB y puntos por coleccion |
| Busquedas/dia | Analytics agregados | Uso del servicio de busqueda |

### 13.5 Verificacion

- [ ] Dashboard muestra total de resoluciones, desglose por fuente y ultimo sync
- [ ] Boton "Forzar sincronizacion" ejecuta spider de la fuente seleccionada
- [ ] Errores de pipeline visibles con detalle
- [ ] Volumen de Qdrant consultable desde el dashboard
- [ ] Settings editables: URLs de servicios, thresholds, prompts

---

## 14. FASE 8: Dashboard Profesional + SEO

### 14.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Paginas publicas SEO de resoluciones (abstract + metadatos) como lead generation. Dashboard del profesional con favoritos y alertas. |
| **Dependencias** | Fase 7 |
| **Complejidad** | 🟡 Media (Schema.org, SEO, paginas publicas) |
| **Horas estimadas** | 40-50h |

### 14.2 Controllers

| Ruta | Controller | Descripcion |
|------|-----------|-------------|
| `/legal/{source_slug}/{seo_slug}` | `LegalResolutionController` | Pagina publica SEO |

### 14.3 SEO Schema.org

```json
{
  "@context": "https://schema.org",
  "@type": "LegalForceDocument",
  "name": "Consulta Vinculante V0123-24",
  "description": "Tributacion de criptomonedas en IRPF...",
  "legislationIdentifier": "V0123-24",
  "legislationDate": "2024-03-15",
  "legislationLegalForce": "InForce",
  "legislationJurisdiction": {
    "@type": "AdministrativeArea",
    "name": "España"
  },
  "author": {
    "@type": "GovernmentOrganization",
    "name": "Direccion General de Tributos"
  }
}
```

### 14.4 Estrategia SEO/GEO

- Paginas de resumen publicas (abstract + metadatos) indexables por Google.
- Texto completo solo accesible para usuarios autenticados (lead generation).
- URLs semanticas: `/legal/dgt/V0123-24-tributacion-criptomonedas-irpf`
- Sitemap XML especifico para resoluciones con `lastmod` actualizado.

### 14.5 SCSS

| Archivo | Descripcion |
|---------|-------------|
| `scss/_public-summary.scss` | Paginas publicas SEO |

### 14.6 Verificacion

- [ ] Paginas publicas accesibles sin autenticacion con abstract y metadatos
- [ ] Texto completo redirige a login para usuarios no autenticados
- [ ] Schema.org LegalForceDocument validado con Google Rich Results Test
- [ ] URLs semanticas generadas correctamente
- [ ] Dashboard profesional muestra favoritos, alertas activas, busquedas recientes
- [ ] Sitemap XML incluye resoluciones con lastmod

---

## 15. FASE 9: QA + Go-Live

### 15.1 Justificacion

| Criterio | Valor |
|----------|-------|
| **Valor negocio** | Validacion integral antes de puesta en produccion. Tests, optimizacion, documentacion. |
| **Dependencias** | Fases 0-8 completas |
| **Complejidad** | 🟡 Media (testing, optimizacion) |
| **Horas estimadas** | 45-60h |

### 15.2 Tests PHPUnit (7)

| Test | Tipo | Descripcion |
|------|------|-------------|
| `LegalSearchServiceTest` | Unit | Busqueda semantica, filtros, merge & rank |
| `LegalNlpPipelineServiceTest` | Unit | Pipeline NLP 9 etapas, mocking Tika/spaCy/Gemini |
| `LegalMergeRankServiceTest` | Unit | Score boosting, deduplicacion, ranking |
| `LegalCitationServiceTest` | Unit | 4 formatos de cita, vinculacion a expediente |
| `LegalResolutionEntityTest` | Kernel | Campos de entidad, isEuSource(), formatCitation() |
| `LegalIngestionTest` | Kernel | Deduplicacion por hash, ingesta end-to-end |
| `LegalAlertServiceTest` | Kernel | Matching de alertas, generacion de notificaciones |

### 15.3 Optimizaciones

- Indices compuestos en MariaDB (`source_id + date_issued DESC`, `source_id + resolution_type + date_issued DESC`)
- Cache de facetas en Redis (TTL 5 min)
- Batch processing para ingesta masiva (100 resoluciones por batch)
- Compresion de respuestas API (gzip)
- Rate limiting por plan efectivo

### 15.4 Verificacion

- [ ] 7 tests PHPUnit pasan sin errores
- [ ] Pipeline NLP procesa 100 resoluciones en batch sin OOM
- [ ] Busqueda semantica < 500ms para queries tipicas
- [ ] Rate limiting efectivo: 403 al superar 50 busquedas/mes en plan Starter
- [ ] Alertas criticas se envian en < 5 minutos desde indexacion
- [ ] Digest semanal se genera correctamente para profesionales con areas configuradas
- [ ] Todas las paginas frontend pasan validacion WCAG 2.1 AA basica
- [ ] Zero errores en consola JS
- [ ] SCSS compilado < 50KB

---

## 16. Paleta de Colores y Design Tokens

### 16.1 Tokens de Color del Legal Intelligence Hub

| Token | Variable SCSS | Valor Hex | Uso |
|-------|--------------|-----------|-----|
| `$legal-primary` | Azul Juridico Profundo | `#1E3A5F` | Cabeceras, bordes principales, enlaces |
| `$legal-accent` | Oro Justicia | `#C8A96E` | Acentos, iconos destacados, CTAs |
| `$legal-surface` | Pergamino Suave | `#F5F3EF` | Fondos de tarjetas, areas de lectura |
| `$legal-success` | Verde Vigente | `#2D6A4F` | Badge "vigente", indicadores positivos |
| `$legal-danger` | Rojo Derogada | `#9B2335` | Badge "derogada"/"anulada", alertas criticas |
| `$legal-warning` | Ambar Superada | `#D4A843` | Badge "superada", alertas medias |
| `$legal-eu-blue` | Azul UE | `#003399` | Indicadores de fuentes europeas |
| `$legal-eu-gold` | Oro UE | `#FFCC00` | Estrella UE, badges de primacia |
| `$legal-text` | Texto Principal | `#1A1A2E` | Texto de resoluciones, titulos |
| `$legal-text-light` | Texto Secundario | `#6B7280` | Metadata, fechas, labels |

### 16.2 Implementacion en SCSS

```scss
// jaraba_legal_intelligence/scss/_variables-legal.scss
// Tokens locales del Legal Intelligence Hub.
// SOLO como fallback para var(--ej-*).
// NUNCA redefinir $ej-* en un modulo satelite.

@use 'sass:color';

$legal-primary: #1E3A5F;
$legal-accent: #C8A96E;
$legal-surface: #F5F3EF;
$legal-success: #2D6A4F;
$legal-danger: #9B2335;
$legal-warning: #D4A843;
$legal-eu-blue: #003399;
$legal-eu-gold: #FFCC00;
$legal-text: #1A1A2E;
$legal-text-light: #6B7280;

// USO correcto con CSS Custom Properties:
.legal-resolution-card {
  background: var(--ej-bg-surface, #{$legal-surface});
  color: var(--ej-text-primary, #{$legal-text});
  border-left: 4px solid var(--ej-color-primary, #{$legal-primary});

  &__badge--vigente {
    background: color.change($legal-success, $alpha: 0.15);
    color: $legal-success;
  }

  &__badge--derogada {
    background: color.change($legal-danger, $alpha: 0.15);
    color: $legal-danger;
  }

  &__badge--superada {
    background: color.change($legal-warning, $alpha: 0.15);
    color: $legal-warning;
  }

  &__badge--eu {
    background: color.change($legal-eu-blue, $alpha: 0.1);
    color: $legal-eu-blue;
    border: 1px solid $legal-eu-gold;
  }
}
```

---

## 17. Patron de Iconos SVG

**Directorio:** `ecosistema_jaraba_core/images/icons/legal/`

| Nombre | Outline | Duotone | Uso en Twig |
|--------|---------|---------|-------------|
| `gavel` | `gavel.svg` | `gavel-duotone.svg` | `{{ jaraba_icon('legal', 'gavel', { size: '24px' }) }}` |
| `scale-balance` | `scale-balance.svg` | `scale-balance-duotone.svg` | `{{ jaraba_icon('legal', 'scale-balance', { size: '24px' }) }}` |
| `law-book` | `law-book.svg` | `law-book-duotone.svg` | `{{ jaraba_icon('legal', 'law-book', { size: '24px' }) }}` |
| `scroll-decree` | `scroll-decree.svg` | `scroll-decree-duotone.svg` | `{{ jaraba_icon('legal', 'scroll-decree', { size: '24px' }) }}` |
| `search-legal` | `search-legal.svg` | `search-legal-duotone.svg` | `{{ jaraba_icon('legal', 'search-legal', { size: '24px' }) }}` |
| `citation` | `citation.svg` | `citation-duotone.svg` | `{{ jaraba_icon('legal', 'citation', { size: '24px' }) }}` |
| `alert-bell` | `alert-bell.svg` | `alert-bell-duotone.svg` | `{{ jaraba_icon('legal', 'alert-bell', { size: '24px' }) }}` |
| `eu-flag` | `eu-flag.svg` | `eu-flag-duotone.svg` | `{{ jaraba_icon('legal', 'eu-flag', { size: '24px' }) }}` |
| `es-flag` | `es-flag.svg` | `es-flag-duotone.svg` | `{{ jaraba_icon('legal', 'es-flag', { size: '24px' }) }}` |
| `citation-graph` | `citation-graph.svg` | `citation-graph-duotone.svg` | `{{ jaraba_icon('legal', 'citation-graph', { size: '24px' }) }}` |
| `digest-mail` | `digest-mail.svg` | `digest-mail-duotone.svg` | `{{ jaraba_icon('legal', 'digest-mail', { size: '24px' }) }}` |
| `shield-privacy` | `shield-privacy.svg` | `shield-privacy-duotone.svg` | `{{ jaraba_icon('legal', 'shield-privacy', { size: '24px' }) }}` |

**Estructura SVG duotone:**
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
  <!-- Capa de fondo (opacidad 0.3) -->
  <path d="..." fill="currentColor" opacity="0.3"/>
  <!-- Capa principal (trazo o relleno solido) -->
  <path d="..." stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

---

## 18. Orden de Implementacion Global

Secuencia estricta de 15 pasos a seguir durante la implementacion:

| # | Paso | Fase | Prerequisitos | Entregable |
|---|------|------|---------------|------------|
| 1 | Crear scaffolding del modulo (info.yml, module, install, permissions, routing, services) | 0 | ecosistema_jaraba_core | Modulo instalable |
| 2 | Crear 9 vocabularios de taxonomia con terminos seed | 0 | Paso 1 | Taxonomias en /admin/structure/taxonomy |
| 3 | Configurar Docker: Tika + FastAPI NLP + Qdrant collections | 0 | Paso 1 | Servicios respondiendo en puertos |
| 4 | Implementar 5 Content Entities con Access Handlers, List Builders, Forms | 1 | Paso 2 | Entidades en /admin/content y /admin/structure |
| 5 | Implementar SpiderInterface + 4 spiders nacionales (CENDOJ, BOE, DGT, TEAC) | 1 | Paso 4 | Ingesta de resoluciones de prueba |
| 6 | Implementar LegalIngestionService + LegalIngestionWorker (QueueWorker) | 1 | Paso 5 | Cola de ingesta funcional |
| 7 | Implementar scripts Python NLP (pipeline.py, legal_ner.py, embeddings.py) | 2 | Paso 3 | FastAPI endpoints /api/segment, /api/ner |
| 8 | Implementar LegalNlpPipelineService + LegalNlpWorker (9 etapas) | 2 | Pasos 6, 7 | Resoluciones procesadas e indexadas en Qdrant |
| 9 | Implementar LegalSearchService + LegalCitationService | 3 | Paso 8 | Busqueda semantica funcional |
| 10 | Implementar frontend: controllers, templates, parciales, JS, SCSS | 3 | Paso 9 | Pagina de busqueda accesible |
| 11 | Implementar 4 spiders UE + LegalMergeRankService + embeddings multilingues | 4 | Paso 8 | Fuentes europeas indexadas |
| 12 | Implementar LegalAlertService + LegalDigestService + templates email | 5 | Paso 10 | Alertas y digest funcionales |
| 13 | Implementar integracion Copilot (skill legal_search) + vinculacion expediente | 6 | Pasos 10, 12 | Copilot responde a queries legales |
| 14 | Implementar dashboards admin y profesional + paginas publicas SEO | 7, 8 | Pasos 1-13 | Dashboards y paginas SEO |
| 15 | Tests PHPUnit + optimizaciones + QA + go-live | 9 | Pasos 1-14 | 7 tests, produccion ready |

---

## 19. Relacion con jaraba_legal_knowledge

### 19.1 Modulo Existente

El ecosistema ya contiene `jaraba_legal_knowledge`, un modulo implementado en la sesion de febrero 2026 (ver indice general, entrada "MODULOS 20260201"). Este modulo proporciona:

- **4 Content Entities**: `LegalNorm`, `LegalChunk`, `LegalQueryLog`, `NormChangeAlert`
- **Pipeline BOE**: Sincronizacion diaria con BOE Open Data, chunking, embeddings en Qdrant
- **LegalRagService**: Busqueda RAG con respuestas conversacionales y citas
- **TaxCalculatorService**: Calculadoras IRPF/IVA
- **Alertas de cambios**: NormChangeAlert por modificacion/derogacion de normas

### 19.2 Estrategia de Convivencia

`jaraba_legal_knowledge` y `jaraba_legal_intelligence` son modulos complementarios, NO competidores:

| Aspecto | jaraba_legal_knowledge | jaraba_legal_intelligence |
|---------|----------------------|--------------------------|
| **Scope** | Normativa vigente (BOE) | Jurisprudencia + doctrina + normativa |
| **Fuentes** | Solo BOE | 10+ fuentes nacionales + 4 europeas |
| **Entidad principal** | `LegalNorm` (norma vigente) | `LegalResolution` (resolucion/sentencia) |
| **Busqueda** | RAG conversacional | Busqueda semantica + facetas + grafos |
| **Insercion** | No | Si (citas en expedientes) |
| **Alertas** | `NormChangeAlert` (cambios normativos) | `LegalAlert` (10 tipos, incluyendo impacto UE) |
| **Target** | Todos los profesionales | Profesionales legales/fiscales especializados |

### 19.3 Plan de Migracion

1. **Fase 0-1**: `jaraba_legal_intelligence` se instala como modulo independiente. No toca `jaraba_legal_knowledge`.
2. **Fase 2**: El pipeline NLP del Legal Intelligence Hub consume el contenido del BOE de forma independiente (su propio BoeSpider). `jaraba_legal_knowledge` sigue funcionando en paralelo.
3. **Fase 6+**: Se evalua la migracion de `LegalNorm` a campos de `LegalResolution` con `source_id = 'boe'`. Las calculadoras IRPF/IVA de `jaraba_legal_knowledge` se mantienen como estan (scope diferente).
4. **Futuro**: Una vez que el Legal Intelligence Hub tenga cobertura completa del BOE, se puede deprecar la funcionalidad de normativa de `jaraba_legal_knowledge` y mantener solo las calculadoras fiscales como modulo independiente.

**Principio rector**: Ningun cambio destructivo en `jaraba_legal_knowledge` hasta que el Legal Intelligence Hub demuestre paridad funcional completa en produccion.

---

## 20. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-02-15 | Claude Opus 4.6 | Documento inicial: 20 secciones, 10 fases, 5 entidades, 9 taxonomias, 7 servicios, 8 spiders, 4 Python scripts, 7 tests. 530-685h / 23.850-30.825 EUR |
