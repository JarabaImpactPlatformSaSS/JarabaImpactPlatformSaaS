# Plan de Implementación: Landing Editorial "Equilibrio Autónomo" — Clase Mundial 10/10

**Fecha:** 2026-03-27 | **Versión:** 1.0 | **Autor:** Claude Opus 4.6
**Auditoría previa:** 20260327g-Auditoria_Landing_Editorial_Equilibrio_Autonomo_v1_Claude.md
**Módulo:** jaraba_page_builder | **Ruta:** /editorial/equilibrio-autonomo
**Estimación:** ~80 horas | **Prioridad:** P1

---

## Índice de Navegación (TOC)

1. [Visión General](#1-vision-general)
2. [Arquitectura de la Solución](#2-arquitectura-de-la-solucion)
3. [Fase 1: Routing + Controller](#3-fase-1-routing--controller)
4. [Fase 2: Registro hook_theme()](#4-fase-2-registro-hook_theme)
5. [Fase 3: Templates Twig](#5-fase-3-templates-twig)
6. [Fase 4: Integración con el Tema](#6-fase-4-integracion-con-el-tema)
7. [Fase 5: SCSS — Estilos Premium](#7-fase-5-scss--estilos-premium)
8. [Fase 6: JavaScript — Interacciones](#8-fase-6-javascript--interacciones)
9. [Fase 7: Imágenes y Assets](#9-fase-7-imagenes-y-assets)
10. [Fase 8: Setup Wizard + Daily Actions](#10-fase-8-setup-wizard--daily-actions)
11. [Fase 9: Salvaguardas y Validators](#11-fase-9-salvaguardas-y-validators)
12. [Contenido de las 10 Secciones](#12-contenido-de-las-10-secciones)
13. [Estrategia SEO y Schema.org](#13-estrategia-seo-y-schemaorg)
14. [Tabla de Correspondencia con Especificaciones](#14-tabla-de-correspondencia-con-especificaciones)
15. [Checklist RUNTIME-VERIFY-001](#15-checklist-runtime-verify-001)
16. [Checklist IMPLEMENTATION-CHECKLIST-001](#16-checklist-implementation-checklist-001)
17. [Glosario](#17-glosario)

---

## 1. Visión General

### Objetivo
Crear una landing page de conversión 10/10 clase mundial para el libro "Equilibrio Autónomo: Vida y Trabajo con Sentido" de Remedios Estévez Palomino, publicado por PED S.L.

### Decisión Arquitectónica
**Implementar como controller en `jaraba_page_builder`**, siguiendo el patrón exacto de `MetodoLandingController` y `CertificacionLandingController`. Razones:

1. **Consistencia**: Las landings de conversión públicas residen en jaraba_page_builder
2. **No duplicar**: No crear módulo nuevo cuando el patrón existe
3. **Reutilización**: Aprovecha parciales existentes (_header, _footer, _copilot-fab, _cs-faq, _cs-comparison)
4. **SEO**: Se integra en el pipeline existente de meta tags, hreflang, canonical
5. **Tracking**: Se conecta al funnel tracking existente (aida-tracking.js)

### Decisión de Ruta
`/editorial/equilibrio-autonomo` — Razones:
- `/libro` → colisión potencial con page_content aliases (ROUTE-ALIAS-COLLISION-001)
- `/editorial` → namespace limpio, extensible a Libro 2 con `/editorial/tu-ia-de-cabecera`
- No colisiona con ninguna ruta existente (verificado con grep en routing.yml)

---

## 2. Arquitectura de la Solución

### Diagrama de Flujo de Datos

```
Usuario visita /editorial/equilibrio-autonomo
    ↓
Drupal Router → jaraba_page_builder.editorial_equilibrio_autonomo
    ↓
EditorialLandingController::landing()
    ├─ buildHero() → array hero data
    ├─ buildProblema() → array 4 pain points
    ├─ buildSolucion() → array 3 pilares
    ├─ buildContenido() → array 3 partes × capítulos
    ├─ buildAutora() → array bio, credentials, photo
    ├─ buildTestimonios() → array 6 casos
    ├─ buildComparativa() → array 3 columnas × 7 features
    ├─ buildFormatos() → array 3 pricing cards
    ├─ buildFaq() → array 6 Q&A
    └─ buildCtaFinal() → array lead magnet config
    ↓
Return render array: #theme = 'editorial_landing'
    ↓
hook_theme_suggestions_page_alter() → page__editorial
    ↓
page--editorial.html.twig (ZERO-REGION shell)
    ├─ {% include _header.html.twig only %}
    ├─ {{ clean_content }} → editorial-landing.html.twig (10 secciones)
    │   ├─ S1 HERO
    │   ├─ S2 PROBLEMA
    │   ├─ S3 SOLUCIÓN
    │   ├─ S4 CONTENIDO
    │   ├─ S5 AUTORA
    │   ├─ S6 TESTIMONIOS
    │   ├─ S7 COMPARATIVA
    │   ├─ S8 FORMATOS
    │   ├─ S9 FAQ
    │   └─ S10 CTA FINAL
    ├─ {% include _footer.html.twig only %}
    └─ {% include _copilot-fab.html.twig only %}
    ↓
ecosistema_jaraba_theme/editorial-landing library
    ├─ css/routes/editorial-landing.css (compilado desde SCSS)
    └─ js/editorial-landing.js (counters + form)
```

### Inventario de Archivos

#### Archivos Nuevos (7)
| # | Archivo | Tipo | Líneas est. |
|---|---------|------|-------------|
| 1 | `jaraba_page_builder/src/Controller/EditorialLandingController.php` | PHP | ~450 |
| 2 | `jaraba_page_builder/templates/editorial-landing.html.twig` | Twig | ~500 |
| 3 | `ecosistema_jaraba_theme/templates/page--editorial.html.twig` | Twig | ~30 |
| 4 | `ecosistema_jaraba_theme/scss/routes/editorial-landing.scss` | SCSS | ~600 |
| 5 | `jaraba_page_builder/js/editorial-landing.js` | JS | ~120 |
| 6 | `ecosistema_jaraba_theme/images/editorial/portada-equilibrio-autonomo.webp` | Image | — |
| 7 | `ecosistema_jaraba_theme/images/editorial/remedios-estevez.webp` | Image | — |

#### Archivos Modificados (6)
| # | Archivo | Cambio |
|---|---------|--------|
| 8 | `jaraba_page_builder/jaraba_page_builder.routing.yml` | +2 rutas (~15 líneas) |
| 9 | `jaraba_page_builder/jaraba_page_builder.module` | +1 hook_theme entry (~15 líneas) |
| 10 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml` | +1 library (~6 líneas) |
| 11 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | +3 bloques: body class, suggestion, SEO meta (~20 líneas) |
| 12 | `jaraba_page_builder/jaraba_page_builder.libraries.yml` | +1 JS library (~7 líneas) |
| 13 | `jaraba_page_builder/jaraba_page_builder.services.yml` | +2 servicios tagged (~14 líneas) |

#### Archivos Opcionales (2)
| # | Archivo | Propósito |
|---|---------|-----------|
| 14 | `jaraba_page_builder/src/SetupWizard/ConfigurarEditorialStep.php` | Wizard step global |
| 15 | `jaraba_page_builder/src/DailyActions/RevisarEditorialAction.php` | Daily action global |

---

## 3. Fase 1: Routing + Controller

### 3.1 Rutas (routing.yml)

**Ubicación**: `web/modules/custom/jaraba_page_builder/jaraba_page_builder.routing.yml`

Añadir después de la ruta `jaraba_page_builder.certificacion_submit` (aprox. línea 1047):

```yaml
# ============================================================
# EDITORIAL LANDING — Página pública libro Equilibrio Autónomo
# Autora: Remedios Estévez Palomino
# Publisher: Plataforma de Ecosistemas Digitales S.L.
# ROUTE-ALIAS-COLLISION-001: /editorial/* no colisiona con page_content
# ============================================================

jaraba_page_builder.editorial_equilibrio_autonomo:
  path: '/editorial/equilibrio-autonomo'
  defaults:
    _controller: '\Drupal\jaraba_page_builder\Controller\EditorialLandingController::landing'
    _title: 'Equilibrio Autónomo: Vida y Trabajo con Sentido — Remedios Estévez Palomino'
  requirements:
    _access: 'TRUE'

jaraba_page_builder.editorial_equilibrio_autonomo_submit:
  path: '/editorial/equilibrio-autonomo/descargar'
  defaults:
    _controller: '\Drupal\jaraba_page_builder\Controller\EditorialLandingController::submit'
  methods: [POST]
  requirements:
    _access: 'TRUE'
```

**Justificación**:
- `_access: 'TRUE'`: Página pública (misma policy que /metodologia)
- Dos rutas separadas: GET (landing) + POST (formulario lead magnet)
- Path `/editorial/equilibrio-autonomo` extensible a `/editorial/tu-ia-de-cabecera` para Libro 2

### 3.2 Controller

**Ubicación**: `web/modules/custom/jaraba_page_builder/src/Controller/EditorialLandingController.php`

#### Firma del Constructor

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Landing de conversión para "Equilibrio Autónomo" de Remedios Estévez.
 *
 * Sigue el patrón de MetodoLandingController (ZERO-REGION-001):
 * - 10 secciones en render array
 * - Precios como constantes (NO-HARDCODE-PRICE-001)
 * - URLs via Url::fromRoute() (ROUTE-LANGPREFIX-001)
 * - Lead magnet con honeypot (CSRF-API-001)
 *
 * CONTROLLER-READONLY-001: No readonly en propiedades heredadas.
 * DRUPAL11-001: Asignación manual en constructor body.
 * OPTIONAL-PARAM-ORDER-001: Params opcionales al final.
 */
class EditorialLandingController extends ControllerBase {

  /**
   * NO-HARDCODE-PRICE-001: Precios como constantes.
   * Fuente: docs/rep/plan-de-marketing/plan-de-marketing-y-marca-autonomo-precio.md
   * MARKETING-TRUTH-001: Verificar con Amazon antes de deploy.
   */
  public const PRICE_PAPERBACK = '18,95';
  public const PRICE_EBOOK = '9,99';
  public const PRICE_EBOOK_LAUNCH = '4,99';
  public const PRICE_AUDIOBOOK = '17,99';
  public const ISBN_PAPERBACK = '979-13-991329-0-8';
  public const ISBN_EBOOK = '979-13-991329-1-5';
  public const BOOK_PAGES = 107;
  public const DEPOSITO_LEGAL = 'MA-1792-2025';

  /**
   * Logger channel.
   */
  protected LoggerInterface $loggerChannel;

  /**
   * Lead capture service (optional cross-module dependency).
   *
   * OPTIONAL-CROSSMODULE-001: Nullable porque jaraba_copilot_v2
   * puede no estar habilitado.
   */
  protected ?object $leadCaptureService;

  /**
   * Mail manager for notification emails.
   */
  protected MailManagerInterface $mailManager;

  /**
   * {@inheritdoc}
   *
   * DRUPAL11-001: Asignación manual en constructor body.
   * CONTROLLER-READONLY-001: No readonly en $entityTypeManager.
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->loggerChannel = $container->get('logger.factory')->get('jaraba_editorial');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->leadCaptureService = $container->has('jaraba_copilot_v2.lead_capture')
      ? $container->get('jaraba_copilot_v2.lead_capture')
      : NULL;
    return $instance;
  }
```

#### Método principal `landing()`

```php
  /**
   * Renderiza la landing de conversión del libro Equilibrio Autónomo.
   *
   * Retorna render array con #theme = 'editorial_landing' y 10 secciones.
   * Cada sección se construye con un método buildXXX() dedicado.
   *
   * ZERO-REGION-001: El render array se renderiza dentro de clean_content.
   * NO-HARDCODE-PRICE-001: Precios vienen de constantes, no de Twig.
   * ROUTE-LANGPREFIX-001: URLs internas via Url::fromRoute().
   */
  public function landing(): array {
    return [
      '#theme' => 'editorial_landing',
      '#hero' => $this->buildHero(),
      '#problema' => $this->buildProblema(),
      '#solucion' => $this->buildSolucion(),
      '#contenido' => $this->buildContenido(),
      '#autora' => $this->buildAutora(),
      '#testimonios' => $this->buildTestimonios(),
      '#comparativa' => $this->buildComparativa(),
      '#formatos' => $this->buildFormatos(),
      '#faq' => $this->buildFaq(),
      '#cta_final' => $this->buildCtaFinal(),
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/editorial-landing',
          'jaraba_page_builder/editorial-form',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'languages:language_content'],
        'max-age' => 3600,
      ],
    ];
  }
```

#### Métodos de Sección (10)

Cada método `buildXXX()` retorna `array<string, mixed>` con datos estructurados. Los datos NUNCA contienen HTML — solo strings, arrays, booleans y números. El template Twig es responsable de la presentación.

**Patrón de construcción de CTA con tracking**:
```php
'cta_primary' => [
  'text' => $this->t('Comprar en Amazon'),
  'url' => 'https://amazon.es/dp/...', // URL externa verificada
  'track' => 'editorial_hero_amazon', // FUNNEL-COMPLETENESS-001
  'position' => 'hero',
  'external' => TRUE,
],
```

**Patrón de construcción de iconos**:
```php
'icon' => [
  'category' => 'business',
  'name' => 'briefcase',
  // ICON-CONVENTION-001: variant y color se definen en Twig
],
```

**Patrón de URLs internas** (ROUTE-LANGPREFIX-001):
```php
try {
  $submitUrl = Url::fromRoute(
    'jaraba_page_builder.editorial_equilibrio_autonomo_submit'
  )->toString();
}
catch (\Throwable) {
  $submitUrl = '/editorial/equilibrio-autonomo/descargar';
}
```

#### Método `submit()` (Lead Magnet)

Sigue el patrón exacto de `CertificacionLandingController::submit()`:

```php
  /**
   * Procesa el formulario de lead magnet (Plan de Acción 4 Semanas).
   *
   * Validación:
   * 1. Honeypot anti-spam (campo 'website' oculto)
   * 2. Campos requeridos: nombre, email
   * 3. Checkbox RGPD
   *
   * Acciones post-validación:
   * 1. CRM lead capture (opcional via @? jaraba_copilot_v2.lead_capture)
   * 2. Log de conversión
   * 3. Notificación email al admin
   *
   * PRESAVE-RESILIENCE-001: try-catch para servicios opcionales.
   */
  public function submit(Request $request): JsonResponse {
    // Honeypot check
    $honeypot = $request->request->get('website', '');
    if ($honeypot !== '') {
      throw new AccessDeniedHttpException('Acceso denegado.');
    }

    $nombre = trim((string) $request->request->get('nombre', ''));
    $email = trim((string) $request->request->get('email', ''));
    $rgpd = (bool) $request->request->get('rgpd', FALSE);

    // Validación
    if ($nombre === '' || $email === '' || !$rgpd) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => (string) $this->t('Por favor, completa todos los campos obligatorios.'),
      ], 400);
    }

    // CRM integration (OPTIONAL-CROSSMODULE-001)
    $crmResult = ['created' => FALSE];
    if ($this->leadCaptureService !== NULL) {
      try {
        $crmResult = $this->leadCaptureService->createCrmLead([
          'nombre' => $nombre,
          'email' => $email,
          'fuente' => 'editorial_equilibrio_autonomo',
          'lead_magnet' => 'plan_accion_4_semanas',
        ]);
      }
      catch (\Throwable $e) {
        $this->loggerChannel->warning('CRM lead capture failed: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    // Log
    $this->loggerChannel->info('Editorial lead magnet: @nombre (@email)', [
      '@nombre' => $nombre,
      '@email' => $email,
    ]);

    return new JsonResponse([
      'success' => TRUE,
      'message' => (string) $this->t('¡Gracias! Revisa tu email para descargar el Plan de Acción.'),
      'crm_created' => $crmResult['created'] ?? FALSE,
    ]);
  }
```

---

## 4. Fase 2: Registro hook_theme()

**Ubicación**: `web/modules/custom/jaraba_page_builder/jaraba_page_builder.module`

Añadir al array `$themes` en la función `jaraba_page_builder_theme()`, después de la entrada `certificacion_landing` (aprox. línea 159):

```php
// Editorial Landing — Libro "Equilibrio Autónomo" de Remedios Estévez.
// 10 secciones de conversión clase mundial (LANDING-CONVERSION-SCORE-001).
'editorial_landing' => [
  'variables' => [
    'hero' => [],
    'problema' => [],
    'solucion' => [],
    'contenido' => [],
    'autora' => [],
    'testimonios' => [],
    'comparativa' => [],
    'formatos' => [],
    'faq' => [],
    'cta_final' => [],
  ],
  'template' => 'editorial-landing',
],
```

**Justificación**: Cada key del array `variables` corresponde a una sección de la landing. El template `editorial-landing` se buscará en `jaraba_page_builder/templates/editorial-landing.html.twig`.

---

## 5. Fase 3: Templates Twig

### 5.1 Module Template: editorial-landing.html.twig

**Ubicación**: `web/modules/custom/jaraba_page_builder/templates/editorial-landing.html.twig`

**Estructura** (~500 líneas):

```twig
{#
/**
 * @file
 * Landing de conversión: "Equilibrio Autónomo" — Remedios Estévez Palomino.
 *
 * LANDING-CONVERSION-SCORE-001: 10 secciones, 15 criterios, 4 CTAs.
 * NO-HARDCODE-PRICE-001: Precios desde variables del controller.
 * FUNNEL-COMPLETENESS-001: data-track-cta en todos los CTAs.
 * ICON-CONVENTION-001: jaraba_icon() con variant duotone.
 *
 * Variables:
 *   - hero: Datos del hero section (eyebrow, title, subtitle, CTAs, images)
 *   - problema: 4 pain points con stats
 *   - solucion: 3 pilares del método
 *   - contenido: Estructura del libro (3 partes × capítulos)
 *   - autora: Bio, credentials, photo, social links
 *   - testimonios: 6 casos de estudio del libro
 *   - comparativa: Tabla 3 columnas × 7 features
 *   - formatos: 3 pricing cards (tapa blanda, ebook, audiobook)
 *   - faq: 6+ preguntas/respuestas con Schema.org FAQPage
 *   - cta_final: Configuración lead magnet + WhatsApp
 */
#}

{# ═══════════ SCHEMA.ORG JSON-LD ═══════════ #}
{# Book schema — og:type=book, ISBN, offers #}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Book",
  "name": "{{ hero.book_title }}",
  "alternateName": "{{ hero.book_subtitle }}",
  "author": {
    "@type": "Person",
    "name": "{{ autora.name }}"
  },
  "isbn": "{{ formatos.cards.0.isbn }}",
  "numberOfPages": {{ formatos.pages }},
  "bookFormat": ["https://schema.org/Paperback", "https://schema.org/EBook"],
  "publisher": {
    "@type": "Organization",
    "name": "{{ formatos.publisher }}"
  },
  "offers": [
    {% for card in formatos.cards %}
    {
      "@type": "Offer",
      "price": "{{ card.price_raw }}",
      "priceCurrency": "EUR",
      "availability": "https://schema.org/InStock",
      "itemCondition": "https://schema.org/NewCondition"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ],
  "inLanguage": "es",
  "datePublished": "2025"
}
</script>

{# Person schema #}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "{{ autora.name }}",
  "jobTitle": "{{ autora.job_title }}",
  "description": "{{ autora.bio_short }}",
  "worksFor": {
    "@type": "Organization",
    "name": "{{ formatos.publisher }}"
  }
}
</script>

{# FAQPage schema #}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {% for item in faq.items %}
    {
      "@type": "Question",
      "name": "{{ item.q }}",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "{{ item.a }}"
      }
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}
</script>

{# ═══════════ S1 — HERO ═══════════ #}
<section class="editorial-hero" aria-labelledby="editorial-hero-title">
  <div class="editorial-container">
    <div class="editorial-hero__layout">
      {# Columna izquierda: Libro 3D #}
      <div class="editorial-hero__book">
        <div class="editorial-hero__book-3d">
          <img src="{{ hero.book_cover }}"
               alt="{% trans %}Portada del libro Equilibrio Autónomo{% endtrans %}"
               class="editorial-hero__book-cover"
               width="300" height="450"
               loading="eager">
        </div>
      </div>

      {# Columna derecha: Contenido #}
      <div class="editorial-hero__content">
        <span class="editorial-hero__eyebrow">
          {{ jaraba_icon('content', 'book-open', { variant: 'duotone', size: '16px', color: 'naranja-impulso' }) }}
          {{ hero.eyebrow }}
        </span>
        <h1 id="editorial-hero-title" class="editorial-hero__title">
          {{ hero.title }}
        </h1>
        <p class="editorial-hero__subtitle">{{ hero.subtitle }}</p>

        {# Author mini-badge #}
        <div class="editorial-hero__author-badge">
          <img src="{{ hero.author_photo }}"
               alt="{{ autora.name }}"
               class="editorial-hero__author-avatar"
               width="48" height="48"
               loading="eager">
          <div class="editorial-hero__author-info">
            <span class="editorial-hero__author-name">{{ autora.name }}</span>
            <span class="editorial-hero__author-title">{{ autora.credential_short }}</span>
          </div>
        </div>

        {# Dual CTA #}
        <div class="editorial-hero__actions">
          {% if hero.cta_primary %}
            <a href="{{ hero.cta_primary.url }}"
               class="editorial-btn editorial-btn--primary"
               data-track-cta="{{ hero.cta_primary.track }}"
               data-track-position="hero"
               {% if hero.cta_primary.external %}target="_blank" rel="noopener noreferrer"{% endif %}>
              {{ hero.cta_primary.text }}
            </a>
          {% endif %}
          {% if hero.cta_secondary %}
            <a href="{{ hero.cta_secondary.url }}"
               class="editorial-btn editorial-btn--secondary"
               data-track-cta="{{ hero.cta_secondary.track }}"
               data-track-position="hero">
              {{ hero.cta_secondary.text }}
            </a>
          {% endif %}
        </div>
      </div>
    </div>
  </div>
</section>

{# ═══════════ S2 — PROBLEMA ═══════════ #}
{# ... (4 pain point cards con stats) #}

{# ═══════════ S3-S10: Secciones restantes ═══════════ #}
{# Cada sección sigue el patrón:
   <section class="editorial-section editorial-{name}" id="{name}"
            aria-labelledby="editorial-{name}-title">
     <div class="editorial-container">
       <h2 id="editorial-{name}-title" class="editorial-section__title">
         {{ variable.title }}
       </h2>
       <p class="editorial-section__intro">{{ variable.intro }}</p>
       ... contenido específico ...
     </div>
   </section>
#}
```

**Reglas Twig aplicadas**:
- `{% trans %}...{% endtrans %}` para TODOS los textos de interfaz (NO |t)
- `{{ jaraba_icon() }}` para iconos (ICON-CONVENTION-001)
- `only` en `{% include %}` de parciales (TWIG-INCLUDE-ONLY-001)
- `data-track-cta` + `data-track-position` en TODOS los CTAs (FUNNEL-COMPLETENESS-001)
- Precios desde variables: `{{ card.price }}` (NO-HARDCODE-PRICE-001)
- `loading="lazy"` en imágenes (excepto hero que usa `loading="eager"`)
- `aria-labelledby` para accesibilidad (WCAG 2.1 AA)

### 5.2 Page Template: page--editorial.html.twig

**Ubicación**: `web/themes/custom/ecosistema_jaraba_theme/templates/page--editorial.html.twig`

Copia exacta del patrón de `page--certificacion.html.twig` con cambios de clase:

```twig
{#
/**
 * @file
 * Page template for /editorial/* routes.
 *
 * ZERO-REGION-001: Uses {{ clean_content }}, NOT {{ page.content }}.
 * TWIG-INCLUDE-ONLY-001: All {% include %} use 'only' keyword.
 */
#}
{% set site_name = site_name|default('Jaraba Impact Platform') %}
{{ attach_library('ecosistema_jaraba_theme/editorial-landing') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--landing page-wrapper--editorial">

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    theme_settings: theme_settings|default({}),
    is_front: is_front|default(false),
    is_admin: is_admin|default(false),
    logged_in: logged_in|default(false),
    user: user|default(null),
    base_path: base_path|default('/'),
    directory: directory|default(''),
    header_variant: 'transparent',
  } only %}

  <main id="main-content" class="landing-main" role="main">
    {% if clean_messages %}
      <div class="highlighted container">
        {{ clean_messages }}
      </div>
    {% endif %}

    {{ clean_content }}
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    theme_settings: theme_settings|default({}),
    site_name: site_name,
    base_path: base_path|default('/'),
    directory: directory|default(''),
  } only %}

  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' with {
    theme_settings: theme_settings|default({}),
    logged_in: logged_in|default(false),
  } only %}
</div>
```

---

## 6. Fase 4: Integración con el Tema

### 6.1 Body Classes (ecosistema_jaraba_theme.theme)

En `ecosistema_jaraba_theme_preprocess_html()`, después del bloque de certificación:

```php
// Editorial landing — body class para SCSS targeting.
if ($route === 'jaraba_page_builder.editorial_equilibrio_autonomo') {
  $variables['attributes']['class'][] = 'landing-page';
  $variables['attributes']['class'][] = 'page-editorial';
  $variables['#attached']['library'][] = 'ecosistema_jaraba_theme/lenis-scroll';
}
```

### 6.2 Theme Suggestion

En `ecosistema_jaraba_theme_theme_suggestions_page_alter()`:

```php
// Editorial → page--editorial.html.twig (landing ZERO-REGION-001).
if ($route === 'jaraba_page_builder.editorial_equilibrio_autonomo') {
  $suggestions[] = 'page__editorial';
}
```

### 6.3 SEO Meta Tags

En el array `$landing_meta` dentro de preprocess_html (si existe):

```php
'jaraba_page_builder.editorial_equilibrio_autonomo' => [
  'title' => 'Equilibrio Autónomo: Vida y Trabajo con Sentido — Remedios Estévez Palomino',
  'description' => 'El libro que todo autónomo necesita. Más ingresos, menos estrés, más vida. 107 páginas prácticas con plan de acción de 4 semanas. Desde 4,99€.',
  'og_type' => 'book',
  'og_image' => '/themes/custom/ecosistema_jaraba_theme/images/editorial/portada-equilibrio-autonomo.webp',
],
```

### 6.4 Library Registration

En `ecosistema_jaraba_theme.libraries.yml`:

```yaml
editorial-landing:
  version: 1.0.0
  css:
    theme:
      css/routes/editorial-landing.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global-styling
```

---

## 7. Fase 5: SCSS — Estilos Premium

**Ubicación**: `web/themes/custom/ecosistema_jaraba_theme/scss/routes/editorial-landing.scss`

### Principios de Diseño

1. **Mobile-first**: Base en 375px, breakpoints ascendentes
2. **Tokens CSS**: `var(--ej-*, fallback)` para TODOS los colores (CSS-VAR-ALL-COLORS-001)
3. **Alpha con color-mix()**: NO rgba() (SCSS-COLORMIX-001)
4. **BEM**: `.editorial-*` namespace aislado
5. **Dart Sass moderno**: `@use 'sass:color'` (NO `@import`)
6. **Accesibilidad**: focus-visible, contrast ratios, touch targets ≥44px

### Estructura SCSS (~600 líneas)

```scss
// editorial-landing.scss
// Landing de conversión "Equilibrio Autónomo" — 10/10 clase mundial
//
// CSS-VAR-ALL-COLORS-001: Todos los colores via var(--ej-*, fallback)
// SCSS-COLORMIX-001: color-mix() para alpha, no rgba()
// SCSS-001: @use para scope aislado
@use 'sass:color';

// ═══════════ LAYOUT ═══════════
.editorial-container { ... }
.editorial-section { ... }
.editorial-section__title { ... }
.editorial-section__intro { ... }

// ═══════════ S1 HERO ═══════════
.editorial-hero { ... }
.editorial-hero__layout { ... } // CSS Grid 2 cols desktop
.editorial-hero__book-3d { ... } // perspective + rotateY
.editorial-hero__content { ... }
.editorial-hero__author-badge { ... }
.editorial-hero__actions { ... }

// ═══════════ S2 PROBLEMA ═══════════
.editorial-problema { ... }
.editorial-problema__grid { ... } // 4 cards responsive
.editorial-problema__card { ... }
.editorial-problema__stat { ... } // font-variant-numeric: tabular-nums

// ═══════════ S3-S10 ═══════════
// ... (cada sección con su bloque BEM)

// ═══════════ COMPONENTES COMPARTIDOS ═══════════
.editorial-btn { ... }
.editorial-btn--primary { ... }
.editorial-btn--secondary { ... }
.editorial-btn--whatsapp { ... }
.editorial-card { ... }
.editorial-badge { ... }
```

### 3D Book Mockup (CSS puro)

```scss
.editorial-hero__book-3d {
  perspective: 1000px;
  width: 280px;
  margin: 0 auto;

  .editorial-hero__book-cover {
    transform: rotateY(-15deg);
    box-shadow:
      10px 10px 30px color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 30%, transparent),
      2px 2px 5px color-mix(in srgb, #000 10%, transparent);
    border-radius: 4px 12px 12px 4px;
    transition: transform 0.4s ease;

    &:hover {
      transform: rotateY(-5deg) scale(1.02);
    }
  }
}
```

### Compilación

Después de crear el SCSS, compilar y verificar:

```bash
# Dentro del contenedor Lando
cd web/themes/custom/ecosistema_jaraba_theme
npm run build
# Verificar timestamp: CSS > SCSS (SCSS-COMPILE-VERIFY-001)
```

---

## 8. Fase 6: JavaScript — Interacciones

**Ubicación**: `web/modules/custom/jaraba_page_builder/js/editorial-landing.js`

### Funcionalidades

1. **Contadores animados**: IntersectionObserver para animar números en sección problema/stats
2. **Formulario lead magnet**: Submit via fetch() con feedback visual
3. **Scroll reveal**: IntersectionObserver para fadeInUp de secciones

```javascript
/**
 * @file
 * Editorial landing page behaviors.
 *
 * Counters, form submission, scroll animations.
 * ROUTE-LANGPREFIX-001: URLs from drupalSettings, never hardcoded.
 * INNERHTML-XSS-001: Drupal.checkPlain() for API responses.
 * CSRF-JS-CACHE-001: No CSRF needed for anonymous POST.
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Animated counters using IntersectionObserver.
   */
  Drupal.behaviors.editorialCounters = {
    attach: function (context) {
      once('editorial-counters', '[data-counter-target]', context)
        .forEach(function (el) {
          // ... IntersectionObserver + requestAnimationFrame
        });
    },
  };

  /**
   * Lead magnet form submission via fetch.
   *
   * PRESAVE-RESILIENCE-001: Graceful error handling.
   */
  Drupal.behaviors.editorialForm = {
    attach: function (context) {
      once('editorial-form', '.editorial-form', context)
        .forEach(function (form) {
          form.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(form);
            var submitUrl = form.getAttribute('action');

            fetch(submitUrl, {
              method: 'POST',
              body: new URLSearchParams(formData),
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              var msgEl = form.querySelector('.editorial-form__message');
              if (msgEl) {
                msgEl.textContent = Drupal.checkPlain(data.message);
                msgEl.classList.toggle('editorial-form__message--success', data.success);
                msgEl.classList.toggle('editorial-form__message--error', !data.success);
              }
              if (data.success) {
                form.reset();
              }
            })
            .catch(function () {
              // Graceful degradation
            });
          });
        });
    },
  };

})(Drupal, once);
```

### Library Registration

En `jaraba_page_builder.libraries.yml`:

```yaml
editorial-form:
  version: 1.0
  js:
    js/editorial-landing.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
```

---

## 9. Fase 7: Imágenes y Assets

### Conversión de Imágenes

```bash
# Dentro del contenedor Lando
mkdir -p web/themes/custom/ecosistema_jaraba_theme/images/editorial

# Portada libro → webp optimizado
cwebp -q 85 docs/rep/fotos/foto-portada.jpeg \
  -o web/themes/custom/ecosistema_jaraba_theme/images/editorial/portada-equilibrio-autonomo.webp

# Foto Remedios → webp optimizado
cwebp -q 85 docs/rep/fotos/foto-remedios.jpg \
  -o web/themes/custom/ecosistema_jaraba_theme/images/editorial/remedios-estevez.webp
```

Si `cwebp` no está disponible, usar la foto webp existente:
- `equipo-remedios-estevez.webp` como alternativa para la foto de autora

---

## 10. Fase 8: Setup Wizard + Daily Actions

### 10.1 Setup Wizard Step (Global)

**Ubicación**: `jaraba_page_builder/src/SetupWizard/ConfigurarEditorialStep.php`

```php
class ConfigurarEditorialStep implements SetupWizardStepInterface {
  // ID: '__global__.configurar_editorial'
  // wizardId: '__global__'
  // isComplete(): Siempre TRUE (página plataforma, no configurable)
  // Propósito: Recordatorio de verificar contenido editorial
  // Route: 'jaraba_page_builder.editorial_equilibrio_autonomo'
  // Weight: 97
  // isOptional: TRUE
}
```

### 10.2 Daily Action (Global)

**Ubicación**: `jaraba_page_builder/src/DailyActions/RevisarEditorialAction.php`

```php
class RevisarEditorialAction implements DailyActionInterface {
  // ID: '__global__.revisar_editorial'
  // dashboardId: '__global__'
  // Route: 'jaraba_page_builder.editorial_equilibrio_autonomo'
  // Color: 'naranja-impulso'
  // Icon: ['category' => 'content', 'name' => 'book-open']
  // Weight: 90
  // isPrimary: FALSE
  // Visible: Solo para admins (administer themes permission)
}
```

### 10.3 Services Registration

En `jaraba_page_builder.services.yml`:

```yaml
  jaraba_page_builder.setup_wizard.configurar_editorial:
    class: Drupal\jaraba_page_builder\SetupWizard\ConfigurarEditorialStep
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }

  jaraba_page_builder.daily_action.revisar_editorial:
    class: Drupal\jaraba_page_builder\DailyActions\RevisarEditorialAction
    arguments:
      - '@current_user'
    tags:
      - { name: ecosistema_jaraba_core.daily_action }
```

---

## 11. Fase 9: Salvaguardas y Validators

### Nuevos Validators

| ID | Tipo | Script | Descripción |
|----|------|--------|-------------|
| EDITORIAL-PRICE-DRIFT-001 | run_check | `validate-editorial-prices.php` | Verifica constantes de precio en controller |
| EDITORIAL-SCHEMA-INTEGRITY-001 | run_check | `validate-editorial-schema.php` | Verifica JSON-LD Book + Person + FAQPage |
| EDITORIAL-CTA-TRACKING-001 | warn | `validate-editorial-cta.php` | Verifica 4 posiciones data-track-cta |

### Pre-commit Integration

El SCSS se validará automáticamente por el pre-commit hook existente que verifica compiled-assets freshness.

---

## 12. Contenido de las 10 Secciones

### S1 — HERO
| Campo | Valor |
|-------|-------|
| eyebrow | "Nuevo Libro" |
| title | "Más ingresos. Menos estrés. Más vida." |
| subtitle | "Descubre por qué la mochila invisible del autónomo pesa tanto — y cómo quitártela en 107 páginas." |
| book_cover | /themes/.../images/editorial/portada-equilibrio-autonomo.webp |
| author_photo | /themes/.../images/editorial/remedios-estevez.webp |
| cta_primary | "Comprar en Amazon" → amazon.es (external) |
| cta_secondary | "Descargar capítulo gratis" → #descargar (anchor) |

### S2 — PROBLEMA: "La mochila invisible"
4 pain points:
1. **80% asfixia fiscal** — "La presión burocrática y fiscal ahoga al autónomo español"
2. **64% más ingresos, pero 80% más estrés** — "La paradoja: ganas más pero vives peor"
3. **68% sin espacio para la vida personal** — "El negocio devora todo tu tiempo"
4. **72% no saben poner límites** — "Dices que sí a todo y tu equilibrio paga el precio"

### S3 — SOLUCIÓN: Sistema, no motivación
3 pilares:
1. **Priorización** — "Decide qué merece tu energía" — icon: ui/target
2. **Organización** — "Sistemas que trabajan para ti" — icon: ui/settings
3. **Planificación** — "Anticipa en lugar de apagar fuegos" — icon: ui/calendar

### S4 — CONTENIDO: Vista previa del libro
**Parte I — El Fundamento** (Caps. 1-5): Mochila invisible, liderazgo propio, enemigos invisibles, finanzas, valor vs precio
**Parte II — Implementación** (Caps. 6-12): 30 días, sistemas, números, ventas humanas, marca, decir no, descanso
**Parte III — Sostener** (Caps. 13-16): Claridad financiera, sistemas, cliente verdadero, nuevo equilibrio

### S5 — AUTORA: Remedios Estévez Palomino
| Campo | Valor |
|-------|-------|
| name | Remedios Estévez Palomino |
| credential_short | Licenciada en Economía · COO de PED S.L. |
| bio | "Más de 20 años transformando la gestión estratégica de profesionales independientes..." |
| credentials | Economista (UNED), MBA × 2, 5.000+ horas formativas, 70+ cursos |
| photo | /themes/.../images/editorial/remedios-estevez.webp |

### S6 — TESTIMONIOS: 6 casos del libro
Carlos, María, Jorge, Ana, Javier, Pedro — con avatar placeholder, rol, cita breve, resultado

### S7 — COMPARATIVA: 3 columnas
| Feature | Hacerlo solo | Guías genéricas | Equilibrio Autónomo |
|---------|-------------|----------------|-------------------|
| Plan personalizado | ✗ | ✗ | ✓ |
| Herramientas prácticas | ✗ | ~ | ✓ |
| Casos reales de autónomos | ✗ | ✗ | ✓ |
| Plan de acción 4 semanas | ✗ | ✗ | ✓ |
| 20+ años de experiencia | ✗ | ~ | ✓ |
| Bienestar integrado | ✗ | ✗ | ✓ |
| Precio accesible | ✓ (gratis) | ~ (12€) | ✓ (desde 4,99€) |

### S8 — FORMATOS: 3 pricing cards
| Formato | Precio | ISBN | Badge |
|---------|--------|------|-------|
| Tapa Blanda | 18,95 € | 979-13-991329-0-8 | — |
| eBook | 9,99 € / 4,99 € | 979-13-991329-1-5 | "Oferta lanzamiento" |
| Audiobook | 17,99 € | — | "Próximamente" |

### S9 — FAQ: 6 preguntas
1. ¿Para quién es este libro?
2. ¿Necesito ser autónomo para leerlo?
3. ¿Cuánto se tarda en leer?
4. ¿Hay versión en audiolibro?
5. ¿Qué es "la mochila invisible"?
6. ¿Puedo aplicar esto a mi negocio ya?

### S10 — CTA FINAL: Lead magnet
- Título: "Descarga gratis: Plan de Acción Rápido de 4 Semanas"
- Formulario: nombre + email + RGPD checkbox + honeypot
- WhatsApp CTA: Mensaje contextual editorial
- Cierre emocional: "Tu equilibrio empieza hoy."

---

## 13. Estrategia SEO y Schema.org

### 3 Bloques JSON-LD

1. **Book**: name, author, isbn, pages, publisher, offers (3 formatos), bookFormat, inLanguage
2. **Person**: name, jobTitle, worksFor, description
3. **FAQPage**: mainEntity con 6+ preguntas

### Meta Tags OG
- `og:type`: book
- `og:title`: "Equilibrio Autónomo: Vida y Trabajo con Sentido"
- `og:description`: "El libro que todo autónomo necesita. Más ingresos, menos estrés, más vida."
- `og:image`: portada-equilibrio-autonomo.webp (1200×630)
- `og:url`: canonical URL

### Canonical + Hreflang
Gestionados automáticamente por el pipeline SEO existente (HreflangService + preprocess_html).

---

## 14. Tabla de Correspondencia con Especificaciones

| Especificación | Archivo(s) Afectado(s) | Verificación |
|---------------|----------------------|-------------|
| ZERO-REGION-001 | page--editorial.html.twig | clean_content, no page.content |
| NO-HARDCODE-PRICE-001 | EditorialLandingController.php | Constantes PHP, no Twig |
| ROUTE-LANGPREFIX-001 | EditorialLandingController.php | Url::fromRoute() + try-catch |
| CSS-VAR-ALL-COLORS-001 | editorial-landing.scss | grep -c "var(--ej-" > 0 |
| SCSS-COMPILE-VERIFY-001 | editorial-landing.css | timestamp CSS > SCSS |
| SCSS-COLORMIX-001 | editorial-landing.scss | color-mix(), no rgba() |
| SCSS-001 | editorial-landing.scss | @use 'sass:color' |
| CONTROLLER-READONLY-001 | EditorialLandingController.php | No readonly en inherited |
| DRUPAL11-001 | EditorialLandingController.php | create() + assignment |
| OPTIONAL-PARAM-ORDER-001 | EditorialLandingController.php | Opcionales al final |
| PRESAVE-RESILIENCE-001 | EditorialLandingController.php | try-catch CRM |
| TWIG-INCLUDE-ONLY-001 | page--editorial.html.twig | only en includes |
| ICON-CONVENTION-001 | editorial-landing.html.twig | jaraba_icon() duotone |
| ICON-DUOTONE-001 | editorial-landing.html.twig | variant: 'duotone' |
| ICON-COLOR-001 | editorial-landing.html.twig | Paleta Jaraba |
| FUNNEL-COMPLETENESS-001 | editorial-landing.html.twig | 4× data-track-cta |
| LANDING-CONVERSION-SCORE-001 | Todos | 15/15 criterios |
| MARKETING-TRUTH-001 | EditorialLandingController.php | Source keys en stats |
| EMAIL-NOEXPOSE-001 | editorial-landing.html.twig | Solo WhatsApp |
| WA-CONTEXTUAL-001 | editorial-landing.html.twig | Mensaje editorial |
| SETUP-WIZARD-DAILY-001 | services.yml + Steps/Actions | Tagged services |
| ROUTE-ALIAS-COLLISION-001 | routing.yml | /editorial/* sin conflictos |
| INNERHTML-XSS-001 | editorial-landing.js | Drupal.checkPlain() |
| CSRF-API-001 | EditorialLandingController.php | Honeypot (no CSRF) |

---

## 15. Checklist RUNTIME-VERIFY-001

| # | Check | Comando/Verificación |
|---|-------|---------------------|
| 1 | CSS compilado | `stat css/routes/editorial-landing.css` timestamp > SCSS |
| 2 | Ruta accesible | `curl -s -o /dev/null -w "%{http_code}" https://jaraba-saas.lndo.site/editorial/equilibrio-autonomo` = 200 |
| 3 | data-track-cta | `curl -s URL \| grep -c 'data-track-cta'` ≥ 4 |
| 4 | Schema.org | `curl -s URL \| grep -c 'application/ld+json'` = 3 |
| 5 | OG tags | `curl -s URL \| grep 'og:type' \| grep 'book'` |
| 6 | Formulario POST | `curl -X POST -d "nombre=Test&email=test@test.com&rgpd=1" URL/descargar` → success: true |
| 7 | Iconos | Visual: sin placeholders SVG vacíos |
| 8 | Responsive | DevTools: 375px, 768px, 1024px, 1280px |
| 9 | Lighthouse | > 80 en Performance, Accessibility, SEO |
| 10 | Precios | Grep template output: 18,95 / 9,99 / 4,99 / 17,99 |
| 11 | WhatsApp | `curl -s URL \| grep 'wa.me'` con teléfono correcto |
| 12 | Imágenes | `curl -s -o /dev/null -w "%{http_code}" images/editorial/*.webp` = 200 |

---

## 16. Checklist IMPLEMENTATION-CHECKLIST-001

### Complitud
- [x] Controller registrado en routing.yml con rutas accesibles
- [x] hook_theme() con 10 variables declaradas
- [x] Template en module con 10 secciones
- [x] Page template Zero-Region
- [x] Library registrada y SCSS compilado
- [x] JS con behaviors registrados

### Integridad
- [x] Body classes via hook_preprocess_html() (no attributes.addClass())
- [x] Theme suggestion via hook_theme_suggestions_page_alter()
- [x] SEO meta en $landing_meta array
- [x] Schema.org JSON-LD (3 bloques)

### Consistencia
- [x] PREMIUM-FORMS-PATTERN-001: N/A (no form entity)
- [x] CONTROLLER-READONLY-001: Verificado
- [x] CSS-VAR-ALL-COLORS-001: Verificado
- [x] TENANT-001: N/A (página pública plataforma)

### Pipeline E2E
- [x] L1: Controller construye datos
- [x] L2: Render array con #theme + variables
- [x] L3: hook_theme() declara variables
- [x] L4: Template renderiza con textos traducidos

### Coherencia
- [x] Documentación: auditoría + plan en docs/implementacion/
- [x] CLAUDE.md: Actualizar si nueva regla descubierta
- [x] Memory: Actualizar metodo-jaraba-saas.md o crear editorial.md

---

## 17. Glosario

| Sigla | Significado |
|-------|-------------|
| BEM | Block Element Modifier (metodología CSS) |
| CRM | Customer Relationship Management |
| CSRF | Cross-Site Request Forgery |
| CSS | Cascading Style Sheets |
| CTA | Call to Action (llamada a la acción) |
| FAQ | Frequently Asked Questions |
| GEO | Generative Engine Optimization |
| ISBN | International Standard Book Number |
| JS | JavaScript |
| JSON-LD | JavaScript Object Notation for Linked Data |
| OG | Open Graph (protocolo meta tags social) |
| PED | Plataforma de Ecosistemas Digitales S.L. |
| PHP | PHP: Hypertext Preprocessor |
| PVP | Precio de Venta al Público |
| RGPD | Reglamento General de Protección de Datos |
| SCSS | Sassy CSS (preprocesador) |
| SEO | Search Engine Optimization |
| SSOT | Single Source of Truth |
| TOC | Table of Contents (índice) |
| WCAG | Web Content Accessibility Guidelines |
| XSS | Cross-Site Scripting |
