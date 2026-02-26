# Plan de Implementación: Remediación Integral del Meta-Sitio SaaS

> **Código:** 178_MetaSite_SaaS_Remediation_v1  
> **Versión:** 1.0  
> **Fecha:** 2026-02-26  
> **Estado:** Especificación Técnica para Implementación  
> **Entorno destino:** Drupal 11 SaaS (`jaraba-saas.lndo.site`)  
> **Dependencias:** `05_Core_Theming`, `178_MetaSite_Remediation_ClaudeCode_v1`, Arquitectura Theming SaaS Master, PlanResolverService, MetaSiteResolverService  
> **Prioridad:** BLOCKER — Prerrequisito para go-to-market  
> **Origen de la auditoría:** Auditoría integral multidisciplinar del meta-sitio (26/02/2026)

---

## 📑 Tabla de Contenidos (TOC)

1. [Contexto, Alcance y Objetivos](#1-contexto-alcance-y-objetivos)
2. [Inventario de Hallazgos y Correspondencia con Remediación WP](#2-inventario-de-hallazgos-y-correspondencia-con-remediación-wp)
3. [Componente 1: Homepage — Propuesta de Valor y Navegación](#3-componente-1-homepage--propuesta-de-valor-y-navegación)
4. [Componente 2: Pricing Dinámico desde PlanResolverService](#4-componente-2-pricing-dinámico-desde-planresolverservice)
5. [Componente 3: Lead Magnets con Captura de Email](#5-componente-3-lead-magnets-con-captura-de-email)
6. [Componente 4: Consolidación de Rutas Legacy → F4](#6-componente-4-consolidación-de-rutas-legacy--f4)
7. [Componente 5: Social Proof y Métricas Coherentes](#7-componente-5-social-proof-y-métricas-coherentes)
8. [Componente 6: Página /planes con Value Ladder Dinámico](#8-componente-6-página-planes-con-value-ladder-dinámico)
9. [Componente 7: SEO, Schema.org y Meta Tags](#9-componente-7-seo-schemaorg-y-meta-tags)
10. [Componente 8: Páginas Institucionales (Contacto, Sobre Nosotros)](#10-componente-8-páginas-institucionales-contacto-sobre-nosotros)
11. [Tabla de Correspondencia: Directrices de Aplicación](#11-tabla-de-correspondencia-directrices-de-aplicación)
12. [Plan de Verificación](#12-plan-de-verificación)
13. [Roadmap de Ejecución por Sprints](#13-roadmap-de-ejecución-por-sprints)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Contexto, Alcance y Objetivos

### 1.1 Contexto

La auditoría integral del meta-sitio SaaS (26/02/2026) identificó **20 hallazgos** organizados en 4 niveles de severidad. El meta-sitio tiene una **arquitectura técnica de clase mundial** (9 secciones por vertical, i18n, Schema.org FAQPage, tracking integrado, Federated Design Tokens) pero presenta deficiencias significativas en:

- **Propuesta de valor** del homepage (genérica, sin diferenciación)
- **Embudo de conversión** (página `/planes` inexistente, lead magnets sin captura de email)
- **Coherencia de datos** (métricas contradictorias entre homepage y landings)
- **Duplicación de rutas** (legacy vs F4 canibalizando SEO)

Paralelamente, el documento `20260226a-178_MetaSite_Remediation_ClaudeCode_v1_Claude.md` identifica **14 bugs** en los sitios WordPress de producción (`pepejaraba.com`, `jarabaimpact.com`) que deben resolverse en **ambos entornos**: WordPress (inmediato) y Drupal 11 SaaS (definitivo).

### 1.2 Alcance

Este plan cubre **exclusivamente la implementación en el SaaS Drupal 11** (`jaraba-saas.lndo.site`). Los fixes WordPress están documentados en el doc 178 y son responsabilidad de su propio sprint.

**Archivos afectados (resumen):**

| Tipo | Cantidad estimada | Ubicación |
|------|-------------------|-----------|
| Templates Twig (modificar) | ~12 | `ecosistema_jaraba_theme/templates/partials/` |
| Templates Twig (nuevos) | ~4 | `ecosistema_jaraba_theme/templates/` |
| Controller PHP (modificar) | 2 | `ecosistema_jaraba_core/src/Controller/` |
| Service PHP (nuevo) | 1 | `ecosistema_jaraba_core/src/Service/` |
| SCSS parciales (modificar) | ~3 | `ecosistema_jaraba_theme/scss/components/` |
| SCSS parciales (nuevos) | ~2 | `ecosistema_jaraba_core/scss/` |
| Routing YAML (modificar) | 1 | `ecosistema_jaraba_core.routing.yml` |
| Theme functions (.theme) | 1 | `ecosistema_jaraba_theme.theme` |
| Config YAML (nuevos) | ~3 | `ecosistema_jaraba_core/config/install/` |
| Theme settings | 1 | `ecosistema_jaraba_theme.theme` (settings form) |

### 1.3 Objetivos medibles

| Objetivo | Métrica | Umbral |
|----------|---------|--------|
| Headline homepage específico | A/B test CTR | > 3% (actual estimado < 1%) |
| Página `/planes` funcional | 0 errores 404 | Los CTAs de pricing no rompen |
| Lead magnets con captura | Email capturado + recurso entregado | 100% de submits |
| Métricas coherentes | 0 contradicciones entre páginas | Verificación manual |
| Rutas legacy redirigidas | 301 redirects configurados | 5/5 rutas |
| Pricing dinámico desde ConfigEntity | Datos de `PlanResolverService` | 0 precios hardcodeados |

---

## 2. Inventario de Hallazgos y Correspondencia con Remediación WP

### 2.1 Mapa de hallazgos auditoría SaaS → Doc 178 WP

| ID Auditoría SaaS | Hallazgo | Severidad | Corresponde a BUG WP | Sprint |
|---|---|---|---|---|
| **F-01** | Página `/planes` inexistente — CTAs llevan a 404 | 🔴 Crítico | BUG-005 (Value Ladder) | S1 |
| **F-02** | Lead magnets sin captura de email | 🔴 Crítico | BUG-001 (CTA Kit), BUG-006 (Email) | S1 |
| **HOME-01** | Headline genérico "Impulsa tu ecosistema digital" | 🔴 Crítico | BUG-008 (Hero copy) | S1 |
| **HOME-02** | 16 enlaces de nav antes del fold (parálisis) | 🔴 Crítico | — | S1 |
| **HOME-03** | Métricas contradictorias entre páginas | 🟠 Alto | — | S1 |
| **NAV-01** | Header nav apunta a rutas legacy | 🟠 Alto | BUG-009 (Botón Ecosistema) | S2 |
| **NAV-02** | Rutas legacy `/empleo`, `/emprender`, `/comercio` duplican F4 | 🟠 Alto | — | S2 |
| **SP-01** | Testimonios ficticios sin foto ni empresa real | 🟠 Alto | BUG-007 (Testimonios) | S2 |
| **SP-02** | Métricas de social proof irreales y no verificables | 🟠 Alto | — | S2 |
| **SEO-01** | Meta title/description no visible en templates | 🟡 Medio | BUG-010/011 (Schema/Meta) | S3 |
| **SEO-02** | URLs canónicas legacy→F4 sin redirects | 🟡 Medio | — | S2 |
| **UX-01** | Sin demo/video/screenshot del producto | 🟡 Medio | — | S3 |
| **UX-02** | Footer links a páginas inexistentes | 🟡 Medio | BUG-012 (Cross-pollination) | S2 |
| **INST-01** | No hay página `/contacto` funcional | 🟡 Medio | BUG-004 (Formulario contacto) | S3 |
| **INST-02** | No hay página `/sobre-nosotros` | 🟡 Medio | — | S3 |
| **VERT-01** | Falta landing F4 para Instituciones (B2G) | 🟡 Medio | — | S3 |
| **VERT-02** | Falta landing F4 para Talento (reclutadores) | 🟡 Medio | — | S3 |

---

## 3. Componente 1: Homepage — Propuesta de Valor y Navegación

### 3.1 Objetivo

Reescribir el hero de la homepage para comunicar valor concreto, y eliminar la parálisis de decisión reduciendo de 16 a 6 opciones de navegación.

### 3.2 Archivos a modificar

#### [MODIFY] [_hero.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hero.html.twig)

**Lógica de cambio:** El contenido del hero (eyebrow, headline, subheadline, CTAs) **ya es configurable** vía `theme_settings` pasado desde `preprocess_page()`. Los textos hardcodeados del template son **fallbacks** cuando no hay configuración. El cambio principal es actualizar los fallbacks y añadir campo en theme settings.

**Cambios específicos:**

1. **Eyebrow fallback**: Cambiar de `"Plataforma SaaS para Ecosistemas de Impacto"` a `"Ya confían +1.500 profesionales en España"` (verificar que la métrica es real o usar versión genérica `"Plataforma de transformación digital"`)
2. **Headline fallback**: Cambiar de `"Impulsa tu ecosistema digital"` a `"Empleo, emprendimiento y negocio digital — todo en un solo lugar"`
3. **Subheadline fallback**: Cambiar de la lista de verticales a `"Encuentra trabajo, lanza tu negocio o digitaliza tu tienda. Con IA que te guía paso a paso. Gratis."`
4. **CTA secundario**: Cambiar de `"Ya tengo cuenta"` a `"Descubre el Método →"` con `href="/metodo"` (configurable desde theme settings)

**Directrices que aplican:**
- `i18n`: Todos los textos con `{% trans %}` / `|t`
- `LEGAL-CONFIG-001`: Contenido editable desde Theme Settings sin tocar código
- `TWIG-XSS-001`: Usar `|safe_html`, nunca `|raw` para datos de usuario

```twig
{# Fallbacks actualizados según auditoría #}
{% set hero_eyebrow = theme_settings.hero_eyebrow|default('Plataforma de transformación digital'|t) %}
{% set hero_headline = theme_settings.hero_headline|default('Empleo, emprendimiento y negocio digital — todo en un solo lugar'|t) %}
{% set hero_subtitle = theme_settings.hero_subtitle|default('Encuentra trabajo, lanza tu negocio o digitaliza tu tienda. Con IA que te guía paso a paso. Gratis.'|t) %}
{% set hero_cta_secondary_text = theme_settings.hero_cta_secondary_text|default('Descubre el Método →'|t) %}
{% set hero_cta_secondary_url = theme_settings.hero_cta_secondary_url|default('/metodo') %}
```

#### [MODIFY] [_intentions-grid.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_intentions-grid.html.twig)

**Decisión de diseño:** Eliminar esta sección de la homepage. La intentions-grid duplica la función del vertical-selector y sobrecarga la navegación. Mantener el archivo para otros usos pero no incluirlo en `page--front.html.twig`.

#### [MODIFY] [page--front.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig)

**Cambios:**
1. Eliminar `{% include '_intentions-grid.html.twig' %}` del flujo de la homepage
2. Mantener: Header → Hero → Vertical Selector (6 cards) → Features (3 cards) → Stats → Footer
3. Resultado: de 16 opciones de navegación a ~11 (header 5 + selector 6), eliminando ~5 redundantes

#### [MODIFY] [_vertical-selector.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_vertical-selector.html.twig)

**Cambios:**
1. Actualizar URLs de los fallback para apuntar a rutas F4 (ya lo hace: `/empleabilidad`, `/emprendimiento`, etc. ✅)
2. Verificar que los 6 verticales usan los colores de paleta Jaraba correctos per `ICON-COLOR-001`
3. Los iconos ya usan `jaraba_icon()` con `variant: 'duotone'` ✅

#### [MODIFY] [_header.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header.html.twig)

**Cambios:**
1. Actualizar los fallback nav links para usar rutas F4:
   - `Empleo` → `/empleabilidad` (en lugar de `/empleo`)
   - `Emprender` → `/emprendimiento` (en lugar de `/emprender`)
   - `Comercio` → `/comercioconecta` (en lugar de `/comercio`)
   - Mantener `Talento` → `/talento` (sin F4 aún) e `Instituciones` → `/instituciones`
2. Estos links son configurables desde theme settings (`nav_items`) — actualizar solo los fallbacks

### 3.3 Theme Settings (Configuración desde UI)

**Los textos del hero ya son configurables** vía `theme_get_setting()` en el preprocess. Solo hay que verificar que las entradas de formulario existen en el `ThemeSettingsForm`. Si no existen (posible), crear un nuevo TAB "Meta-Sitio" en las theme settings con campos para:

```php
// En ecosistema_jaraba_theme.theme - form_system_theme_settings_alter()
// TAB: Meta-sitio Homepage
$form['metasite_homepage'] = [
  '#type' => 'details',
  '#title' => t('Meta-sitio: Homepage'),
  '#open' => FALSE,
];
$form['metasite_homepage']['hero_eyebrow'] = [
  '#type' => 'textfield',
  '#title' => t('Eyebrow del Hero'),
  '#default_value' => theme_get_setting('hero_eyebrow') ?? '',
  '#description' => t('Texto encima del headline. Dejar vacío para usar el valor por defecto.'),
];
// ... hero_headline, hero_subtitle, hero_cta_primary_text, hero_cta_secondary_text, hero_cta_secondary_url
```

**Directriz cumplida**: `LEGAL-CONFIG-001` — contenido editable desde UI sin código.

---

## 4. Componente 2: Pricing Dinámico desde PlanResolverService

### 4.1 Objetivo

Reemplazar los datos de pricing hardcodeados en las landings verticales ("Desde 0€/mes") por datos dinámicos leídos de las entidades `SaasPlanTier` y `SaasPlanFeatures` via `PlanResolverService`.

### 4.2 Contexto técnico

**Infraestructura existente (ya implementada, v62.0.0):**
- `SaasPlanTier` ConfigEntity: tiers con aliases, Stripe Price IDs, jerarquía
- `SaasPlanFeatures` ConfigEntity: features + límites por combinación `{vertical}_{tier}`
- `PlanResolverService`: cascade resolution `specific → _default → NULL`
- Admin UI: `/admin/config/jaraba/plan-tiers` y `/admin/config/jaraba/plan-features`

**¿Es posible inyectar datos dinámicos en el meta-sitio?** → **SÍ**. La infraestructura existe. Falta:
1. Un servicio que agrupe los datos de pricing para la capa de presentación
2. Inyección en `VerticalLandingController.buildLanding()` y en el template `_landing-pricing-preview.html.twig`
3. Una página `/planes` con la vista completa

### 4.3 Archivos a crear/modificar

#### [NEW] `MetaSitePricingService.php`

**Ubicación:** `web/modules/custom/ecosistema_jaraba_core/src/Service/MetaSitePricingService.php`

**Responsabilidad:** Obtiene y formatea datos de pricing para presentación en el meta-sitio. Consume `PlanResolverService` y presenta datos listos para Twig.

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides pricing data for meta-site templates.
 *
 * Reads from SaasPlanTier and SaasPlanFeatures ConfigEntities
 * to build presentation-ready pricing data for landing pages
 * and the /planes page.
 *
 * Resolution cascade (via PlanResolverService):
 *   1. Specific: {vertical}_{tier}
 *   2. Default: _default_{tier}
 *   3. NULL (no config → fallback hardcoded)
 */
class MetaSitePricingService {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PlanResolverService $planResolver,
  ) {}

  /**
   * Gets pricing preview data for a specific vertical.
   *
   * Returns a structured array ready for _landing-pricing-preview.html.twig.
   *
   * @param string $vertical
   *   Machine name of the vertical (e.g. 'agroconecta').
   *
   * @return array
   *   Pricing preview data keyed by tier (starter, professional, enterprise).
   */
  public function getPricingPreview(string $vertical): array {
    // Load all tiers ordered by weight
    $tiers = $this->entityTypeManager
      ->getStorage('saas_plan_tier')
      ->loadMultiple();

    // Sort by weight
    uasort($tiers, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    $pricing = [];
    foreach ($tiers as $tier) {
      $tierKey = $tier->getTierKey();
      $features = $this->planResolver->getFeatures($vertical, $tierKey);

      $pricing[] = [
        'tier_key' => $tierKey,
        'label' => (string) $tier->label(),
        'description' => (string) $tier->getDescription(),
        'features' => $features ? $features->getFeatures() : [],
        'limits' => $features ? $features->getLimits() : [],
        'is_recommended' => ($tierKey === 'professional'),
        'stripe_price_monthly' => $tier->getStripePriceMonthly(),
        'stripe_price_yearly' => $tier->getStripePriceYearly(),
      ];
    }

    return $pricing;
  }

  /**
   * Gets the "from price" display text for a vertical.
   *
   * Used in the pricing preview section of landing pages.
   *
   * @param string $vertical
   *   Machine name of the vertical.
   *
   * @return array
   *   Array with 'from_price', 'from_label', 'features_highlights'.
   */
  public function getFromPrice(string $vertical): array {
    $starterFeatures = $this->planResolver->getFeatures($vertical, 'starter');

    // Get highlight features for the free/starter tier
    $highlights = $starterFeatures ? array_slice($starterFeatures->getFeatures(), 0, 4) : [];

    return [
      'from_price' => (string) $this->t('0€/mes'),
      'from_label' => (string) $this->t('Empieza gratis'),
      'features_highlights' => $highlights,
    ];
  }
}
```

**Registro del servicio** en `ecosistema_jaraba_core.services.yml`:
```yaml
ecosistema_jaraba_core.metasite_pricing:
  class: Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService
  arguments:
    - '@entity_type.manager'
    - '@ecosistema_jaraba_core.plan_resolver'
```

#### [MODIFY] [VerticalLandingController.php](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php)

**Cambios:**
1. Inyectar `MetaSitePricingService` en el constructor
2. En `buildLanding()`, enriquecer `$data['pricing']` con datos dinámicos del servicio
3. Mantener fallbacks hardcodeados para cuando no hay ConfigEntity configurada

```php
// En buildLanding():
protected function buildLanding(array $data): array {
  // Enrich pricing data from ConfigEntities if available
  $verticalKey = $data['vertical_key'] ?? '';
  if ($verticalKey && $this->pricingService) {
    $dynamicPricing = $this->pricingService->getFromPrice($verticalKey);
    if (!empty($dynamicPricing['features_highlights'])) {
      $data['pricing'] = array_merge($data['pricing'] ?? [], $dynamicPricing);
    }
  }

  return [
    '#theme' => 'vertical_landing_content',
    '#vertical_data' => $data,
    '#attached' => [
      'library' => [
        'ecosistema_jaraba_core/global',
        'ecosistema_jaraba_theme/progressive-profiling',
        'ecosistema_jaraba_theme/landing-sections',
      ],
    ],
  ];
}
```

**Inyección de dependencia:**
```php
public static function create(ContainerInterface $container): static {
  $instance = parent::create($container);
  $instance->pricingService = $container->get('ecosistema_jaraba_core.metasite_pricing');
  return $instance;
}
```

> [!IMPORTANT]
> Se usa inyección `@?` (optional) para no romper si el servicio aún no existe. Los fallbacks hardcodeados del template se mantienen como safety net.

#### [MODIFY] [_landing-pricing-preview.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_landing-pricing-preview.html.twig)

**Cambios:**
1. Leer `pricing.from_price` y `pricing.from_label` del data dinámico si existe
2. Leer `pricing.features_highlights` para liste de features del tier gratuito
3. Mantener fallbacks actuales exactamente como están

```twig
{% set from_price = pricing.from_price|default('0€/mes'|t) %}
{% set from_label = pricing.from_label|default('Empieza gratis'|t) %}
{% set features = pricing.features_highlights|default(pricing.features|default([])) %}
```

---

## 5. Componente 3: Lead Magnets con Captura de Email

### 5.1 Objetivo

Implementar un mecanismo de captura de email **antes** de entregar los recursos gratuitos de los lead magnets. Sin captura de email, los lead magnets no generan leads.

### 5.2 Patrón de implementación: Slide-Panel con Formulario

Siguiendo la directriz del proyecto de usar **slide-panel para todas las acciones CRUD** (ver `/slide-panel-modales` workflow), el lead magnet abrirá un slide-panel con formulario de email en vez de navegar a una página nueva.

#### [MODIFY] [_landing-lead-magnet.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_landing-lead-magnet.html.twig)

**Cambios:**
1. El CTA del lead magnet abre un slide-panel (`data-slide-panel`) con formulario de captura
2. Formulario: Nombre + Email + Checkbox RGPD + Submit
3. Post-submit: entrega del recurso + creación de `jaraba_lead` entity

```twig
{# Antes: link directo al recurso (sin captura) #}
{# <a href="{{ lead_magnet.cta.url }}"> #}

{# Después: slide-panel con formulario de captura #}
<a href="/api/v1/lead-magnet/{{ vertical_key }}/form"
   data-slide-panel="medium"
   data-slide-panel-title="{{ lead_magnet.title }}"
   class="btn btn--primary btn--lg"
   data-track-cta="lead_magnet_{{ vertical_key }}"
   data-track-position="landing_lead_magnet">
  {{ lead_magnet.cta.text }}
</a>
```

#### [NEW] API endpoint para formulario de lead magnet

**Ruta:** `POST /api/v1/lead-magnet/{vertical}/capture`  
**Controlador:** Añadir método en `VerticalLandingController` o crear `LeadMagnetController`

```php
/**
 * Captures email and delivers lead magnet resource.
 *
 * @param string $vertical
 *   Machine name of the vertical.
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The request with JSON body: {email, name, gdpr_consent}.
 *
 * @return \Symfony\Component\HttpFoundation\JsonResponse
 *   Success response with download URL.
 */
public function captureLeadMagnet(string $vertical, Request $request): JsonResponse {
  // 1. Validate email, name, gdpr_consent
  // 2. Create jaraba_lead entity with vertical tag
  // 3. Return download URL for the resource
  // 4. Trigger ECA: email sequence enrollment
}
```

**Directrices que aplican:**
- `CSRF-API-001`: Usar `_csrf_request_header_token: 'TRUE'` en routing
- `API-WHITELIST-001`: Filtrar campos contra constante `ALLOWED_FIELDS`
- `TWIG-XSS-001`: Todo dato de usuario sanitizado con `|safe_html`
- `LEGAL-ROUTE-001`: URLs en español SEO-friendly

> [!NOTE]
> La implementación completa de email nurturing (secuencia de 7 emails post-descarga descrita en Doc 178 §7) se implementará en un sprint separado usando el módulo `jaraba_email` y ECA triggers. Este componente solo cubre la **captura del email**.

---

## 6. Componente 4: Consolidación de Rutas Legacy → F4

### 6.1 Objetivo

Redirigir las 5 rutas legacy a sus equivalentes F4 para eliminar canibalización SEO y confusión de usuario.

### 6.2 Archivos a modificar

#### [MODIFY] `ecosistema_jaraba_core.routing.yml`

Añadir redirects 301 para las rutas legacy:

```yaml
# Legacy redirects → F4 equivalents
ecosistema_jaraba_core.legacy.empleo:
  path: '/empleo'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\VerticalLandingController::redirectLegacy'
    target: '/empleabilidad'
  requirements:
    _access: 'TRUE'

ecosistema_jaraba_core.legacy.emprender:
  path: '/emprender'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\VerticalLandingController::redirectLegacy'
    target: '/emprendimiento'
  requirements:
    _access: 'TRUE'

ecosistema_jaraba_core.legacy.comercio:
  path: '/comercio'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\VerticalLandingController::redirectLegacy'
    target: '/comercioconecta'
  requirements:
    _access: 'TRUE'
```

#### [MODIFY] [VerticalLandingController.php](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php)

**Nuevo método:**

```php
/**
 * Redirects legacy vertical routes to their F4 equivalents.
 *
 * Returns 301 Moved Permanently to ensure SEO juice transfers.
 *
 * @param string $target
 *   Target path from route defaults.
 *
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 *   301 redirect.
 */
public function redirectLegacy(string $target): RedirectResponse {
  return new RedirectResponse($target, 301);
}
```

> [!WARNING]
> **No eliminar las rutas legacy** del controller que contienen los métodos `legacyEmpleo()`, `legacyEmprender()`, etc. — solo reemplazar su routing para hacer redirect en vez de renderizar contenido.

### 6.3 Rutas que mantienen su contenido (sin F4 equivalente)

| Ruta | Estado | Acción futura |
|------|--------|---------------|
| `/talento` | Solo legacy | Crear landing F4 en Sprint 3 |
| `/instituciones` | Solo legacy | Crear landing F4 en Sprint 3 |

---

## 7. Componente 5: Social Proof y Métricas Coherentes

### 7.1 Objetivo

Unificar todas las métricas de social proof para que sean coherentes entre homepage y landings, y reemplazar testimonios no verificables por datos reales o placeholders honestos.

### 7.2 Archivos a modificar

#### [MODIFY] [_stats.html.twig](file:///Z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_stats.html.twig)

**Cambios:**
1. Los stats deben ser **configurables desde theme settings** (ya lo son parcialmente — verificar)
2. Unificar las métricas: usar datos reales o quitar hasta tener datos verificables
3. Si no hay datos reales configurados, NO mostrar la sección (conditional render)

```twig
{% set stats_items = theme_settings.stats_items|default([]) %}
{% if stats_items|length > 0 %}
  <section class="stats-section reveal-element reveal-fade-up">
    {# ... render stats ... #}
  </section>
{% endif %}
```

**Nuevo TAB en theme settings** "Meta-sitio: Métricas":
```php
$form['metasite_stats'] = [
  '#type' => 'details',
  '#title' => t('Meta-sitio: Métricas de impacto'),
  '#description' => t('Configurar métricas de social proof. Dejar vacío para no mostrar la sección.'),
];
// Campos para 4 métricas: valor, label, icono
```

#### [MODIFY] Datos de social proof en `VerticalLandingController`

**Cambios en cada método vertical (agroconecta, comercioconecta, etc.):**
1. Las métricas de `social_proof.metrics` deben ser coherentes con las de la homepage
2. Los testimonios deben tener `photo_url`, `full_name`, `company`, `linkedin_url` o no mostrarse
3. Si un campo no tiene dato real, usar placeholder configurable

```php
'social_proof' => [
  'metrics' => [
    // Solo métricas verificables — si no hay dato real, quitar
    ['value' => '500+', 'label' => (string) $this->t('Productores activos')],
    ['value' => '98%', 'label' => (string) $this->t('Satisfacción')],
  ],
  'testimonials' => [
    // Solo si tiene nombre completo + empresa verificable
    [
      'quote' => (string) $this->t('Cita real del cliente'),
      'author' => 'Nombre Completo',
      'role' => 'Cargo, Empresa',
      'verified' => TRUE, // Flag para mostrar badge verificado
    ],
  ],
],
```

> [!IMPORTANT]
> **Decisión a revisar por el usuario:** ¿Hay datos reales de métricas disponibles? Si no, la recomendación es mostrar métricas genéricas honestas ("Más de X profesionales usan el ecosistema") o no mostrar la sección hasta tener datos reales. Las métricas actuales (1.500+ candidatos, 2.500+ comercios, 340+ negocios) parecen inconsistentes entre sí.

---

## 8. Componente 6: Página `/planes` con Value Ladder Dinámico

### 8.1 Objetivo

Crear la página `/planes` que muestre la escalera de valor completa con datos dinámicos de `SaasPlanTier` y `SaasPlanFeatures`.

### 8.2 Archivos a crear

#### [NEW] Ruta en `ecosistema_jaraba_core.routing.yml`

```yaml
ecosistema_jaraba_core.pricing:
  path: '/planes'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\PricingController::pricingPage'
    _title: 'Planes y Precios'
  requirements:
    _access: 'TRUE'
  options:
    _admin_route: FALSE
```

#### [NEW] `PricingController.php`

**Ubicación:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/PricingController.php`

**Responsabilidad:** Renderiza la página `/planes` con datos de `MetaSitePricingService`.

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the /planes pricing page.
 *
 * Reads pricing data from SaasPlanTier and SaasPlanFeatures
 * ConfigEntities to render a dynamic pricing page.
 */
class PricingController extends ControllerBase {

  public function __construct(
    protected MetaSitePricingService $pricingService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.metasite_pricing'),
    );
  }

  /**
   * Renders the /planes pricing page.
   *
   * @return array
   *   Render array with pricing_page theme.
   */
  public function pricingPage(): array {
    // Get all tiers with their features (using default vertical)
    $tiers = $this->pricingService->getPricingPreview('_default');

    return [
      '#theme' => 'pricing_page',
      '#tiers' => $tiers,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/global',
          'ecosistema_jaraba_theme/pricing-page',
        ],
      ],
    ];
  }
}
```

#### [NEW] Template `pricing-page.html.twig`

**Ubicación:** `web/modules/custom/ecosistema_jaraba_core/templates/pricing-page.html.twig`

**Estructura:**
1. Hero de pricing con headline y subheadline
2. Grid de 3 cards (Free / Pro / Enterprise)
3. Tabla comparativa de features
4. FAQ de pricing
5. Final CTA

```twig
{#
/**
 * @file pricing-page.html.twig
 * Página /planes — Pricing dinámico desde SaasPlanTier/Features.
 *
 * VARIABLES:
 * - tiers: Array de tiers con label, description, features, limits, is_recommended.
 *
 * DIRECTRICES:
 * - i18n: {% trans %} en todos los textos
 * - ICON-CONVENTION-001: jaraba_icon('category', 'name', { variant: 'duotone' })
 * - ICON-COLOR-001: Colores de paleta Jaraba
 * - CSS: var(--ej-*) con fallbacks inline
 */
#}

<section class="pricing-hero" aria-labelledby="pricing-title">
  <h1 id="pricing-title" class="pricing-hero__title">
    {% trans %}Elige el plan que se adapta a ti{% endtrans %}
  </h1>
  <p class="pricing-hero__subtitle">
    {% trans %}Empieza gratis. Actualiza cuando lo necesites. Sin permanencia.{% endtrans %}
  </p>
</section>

<section class="pricing-grid" aria-label="{% trans %}Comparar planes{% endtrans %}">
  <div class="pricing-grid__container">
    {% for tier in tiers %}
      <div class="pricing-card{% if tier.is_recommended %} pricing-card--recommended{% endif %}">
        {% if tier.is_recommended %}
          <div class="pricing-card__badge">{% trans %}Más popular{% endtrans %}</div>
        {% endif %}
        <h2 class="pricing-card__name">{{ tier.label }}</h2>
        <p class="pricing-card__description">{{ tier.description }}</p>

        <div class="pricing-card__features">
          {% for feature in tier.features %}
            <div class="pricing-card__feature">
              {{ jaraba_icon('ui', 'check', { variant: 'outline', color: 'verde-innovacion', size: '16px' }) }}
              <span>{{ feature|t }}</span>
            </div>
          {% endfor %}
        </div>

        <a href="/user/register?plan={{ tier.tier_key }}"
           class="btn btn--{{ tier.is_recommended ? 'primary' : 'secondary' }} btn--lg"
           data-track-cta="pricing_{{ tier.tier_key }}"
           data-track-position="pricing_page">
          {% if tier.tier_key == 'starter' %}
            {% trans %}Empezar gratis{% endtrans %}
          {% else %}
            {% trans %}Solicitar información{% endtrans %}
          {% endif %}
        </a>
      </div>
    {% endfor %}
  </div>
</section>

<section class="pricing-guarantee">
  <p>{% trans %}Sin tarjeta de crédito. Sin permanencia. Cancela cuando quieras.{% endtrans %}</p>
</section>
```

#### [NEW] Page template `page--pricing.html.twig`

Siguiendo el patrón `/frontend-page-pattern`:

```twig
{# page--pricing.html.twig - Zero Region Policy #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}

<!DOCTYPE html>
<html{{ html_attributes }}>
<head>
  <head-placeholder token="{{ placeholder_token }}">
  <title>{{ head_title|safe_join(' | ') }}</title>
  <css-placeholder token="{{ placeholder_token }}">
  <js-placeholder token="{{ placeholder_token }}">
</head>

<body{{ attributes.addClass('pricing-page', 'page-pricing', 'full-width-layout') }}>
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Skip to main content{% endtrans %}
  </a>

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({}),
    avatar_nav: avatar_nav|default(null)
  } %}

  <main id="main-content" class="pricing-main">
    {% if clean_messages %}
      <div class="highlighted container">{{ clean_messages }}</div>
    {% endif %}
    <div class="pricing-wrapper">{{ clean_content }}</div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    theme_settings: theme_settings|default({})
  } %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

**Registrar en `.theme`:**
1. Template suggestion en `theme_suggestions_page_alter()`
2. Body classes en `hook_preprocess_html()`

> [!WARNING]
> **Las clases `attributes.addClass()` NO funcionan para el body** (directriz del proyecto). Se DEBE usar `hook_preprocess_html()`.

#### [NEW] SCSS partial `_pricing-page.scss`

**Ubicación:** `web/themes/custom/ecosistema_jaraba_theme/scss/components/_pricing-page.scss`

**Directrices SCSS:**
- Usar `var(--ej-*, fallback)` para todos los colores
- `font-family: var(--ej-font-family, 'Outfit', sans-serif)` per `BRAND-FONT-001`
- Dart Sass: `@use 'sass:color'`, nunca `darken()`/`lighten()`
- Glassmorphism premium para las cards recomendadas
- Mobile-first, responsive

```scss
@use 'sass:color';
@use 'variables' as *;

/**
 * @file _pricing-page.scss
 * Estilos para la página /planes con pricing dinámico.
 *
 * DIRECTRIZ: Usa Design Tokens con CSS Custom Properties (var(--ej-*))
 *
 * COMPILACIÓN:
 * npx sass scss/main.scss:css/main.css --style=compressed
 */

.pricing-hero {
  text-align: center;
  padding: var(--ej-spacing-2xl, 4rem) var(--ej-spacing-lg, 2rem);
  background: var(--ej-bg-gradient, linear-gradient(135deg, #f8fafc, #eef2f7));

  &__title {
    font-family: var(--ej-font-family, 'Outfit', sans-serif);
    font-size: clamp(2rem, 5vw, 3rem);
    color: var(--ej-color-corporate, #233D63);
    font-weight: 800;
  }

  &__subtitle {
    color: var(--ej-text-muted, #64748b);
    max-width: 600px;
    margin-inline: auto;
  }
}

.pricing-grid {
  &__container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--ej-spacing-lg, 2rem);
    max-width: 1200px;
    margin-inline: auto;
    padding: var(--ej-spacing-xl, 3rem) var(--ej-spacing-lg, 2rem);
  }
}

.pricing-card {
  background: var(--ej-bg-surface, #fff);
  border-radius: 16px;
  padding: var(--ej-spacing-xl, 2.5rem);
  border: 1px solid var(--ej-border-color, #e2e8f0);
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);

  &:hover {
    transform: translateY(-6px) scale(1.02);
  }

  &--recommended {
    background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 2px solid var(--ej-color-impulse, #FF8C42);
    box-shadow:
      0 4px 24px rgba(0,0,0,0.04),
      0 1px 2px rgba(0,0,0,0.02),
      inset 0 1px 0 rgba(255,255,255,0.9);
    position: relative;
  }

  &__badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--ej-color-impulse, #FF8C42);
    color: #fff;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
  }
}
```

**Importar en `main.scss`:**
```scss
@use 'components/pricing-page';
```

---

## 9. Componente 7: SEO, Schema.org y Meta Tags

### 9.1 Archivos a verificar

- **Schema.org FAQPage** en `_landing-faq.html.twig` → ✅ Ya implementado
- **Schema.org Organization** → Verificar que existe en homepage
- **Schema.org Product** → Añadir en página `/planes` (nuevo)
- **Meta title/description** → Verificar que módulo Metatag los inyecta
- **Open Graph** → Verificar en metatag config

### 9.2 Nuevo Schema.org para `/planes`

```twig
{# Schema.org Product para pricing page #}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Jaraba Impact Platform",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web",
  "offers": [
    {% for tier in tiers %}
    {
      "@type": "Offer",
      "name": {{ tier.label|json_encode|raw }},
      "priceCurrency": "EUR",
      "price": "0",
      "description": {{ tier.description|json_encode|raw }}
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}
</script>
```

---

## 10. Componente 8: Páginas Institucionales (Contacto, Sobre Nosotros)

### 10.1 Decisión de implementación

Las páginas `/contacto` y `/sobre-nosotros` se implementan preferiblemente como **PageContent entidades del Page Builder** (GrapesJS), ya que su contenido es mayoritariamente estático y editable.

**Si se necesitan formularios** (contacto), usar Webform o formulario custom siguiendo `FORM-MSG-001` (mensajes en templates custom).

### 10.2 Verificación de URLs del footer

Verificar que todas las URLs del footer apuntan a páginas existentes:

| URL | Estado actual | Acción |
|-----|---------------|--------|
| `/politica-privacidad` | ✅ `LEGAL-ROUTE-001` | Verificar |
| `/terminos-uso` | ✅ `LEGAL-ROUTE-001` | Verificar |
| `/politica-cookies` | ✅ `LEGAL-ROUTE-001` | Verificar |
| `/sobre-nosotros` | ❓ Sin verificar | Crear o verificar |
| `/contacto` | ❓ Sin verificar | Crear o verificar |
| `/blog` | ❓ Sin verificar | Verificar |

---

## 11. Tabla de Correspondencia: Directrices de Aplicación

### 11.1 Mapa completo de directrices → Componentes

| Directriz | ID | Componentes que la aplican | Verificación |
|---|---|---|---|
| **i18n obligatorio** | Workflow i18n | TODOS — `{% trans %}`, `$this->t()`, `Drupal.t()` | Grep `|t` / `{% trans %}` |
| **SCSS variables inyectables** | Arquitectura Theming | C1, C6 — `var(--ej-*, fallback)`, NO hex hardcodeados | Grep `#` en SCSS |
| **Dart Sass moderno** | SCSS Workflow #17-19 | C6 — `@use 'sass:color'`, `color.scale()`, NO `darken()` | Build sin warnings |
| **Zero Region Policy** | Frontend Page Pattern | C6 — `{{ clean_content }}`, NO `{{ page.content }}` | Template audit |
| **Body classes via hook** | Frontend Page Pattern | C6 — `hook_preprocess_html()`, NO `attributes.addClass()` | Code review |
| **Icon Convention** | ICON-CONVENTION-001 | C6 — `jaraba_icon('category', 'name', {variant: 'duotone'})` | Grep `jaraba_icon` |
| **Duotone-first** | ICON-DUOTONE-001 | C6 — Default `variant: 'duotone'` en templates premium | Visual check |
| **Colores Jaraba** | ICON-COLOR-001 | TODOS — `azul-corporativo`, `naranja-impulso`, `verde-innovacion` | Grep icon calls |
| **Font Outfit** | BRAND-FONT-001 | C6 — `font-family: var(--ej-font-family, 'Outfit', sans-serif)` | Grep `font-family` |
| **XSS prevention** | TWIG-XSS-001 | C3, C5, C6 — `|safe_html`, NUNCA `|raw` para datos usuario | Template audit |
| **CSRF en APIs** | CSRF-API-001 | C3 — `_csrf_request_header_token: 'TRUE'` | Routing check |
| **API field whitelist** | API-WHITELIST-001 | C3 — `ALLOWED_FIELDS` constante | Controller check |
| **Config desde UI** | LEGAL-CONFIG-001 | C1, C5, C8 — Theme Settings editables | Admin UI test |
| **Sticky header** | CSS-STICKY-001 | C1, C6 — `position: sticky` default | Visual check |
| **Cascade pricing** | PLAN-CASCADE-001 | C2 — `PlanResolverService` cascade | Unit test |
| **Plan normalization** | PLAN-RESOLVER-001 | C2 — `normalize()` via aliases | Unit test |
| **Premium forms** | PREMIUM-FORMS-PATTERN-001 | C3 — Si se crea form entity para leads | Pattern match |
| **No emojis** | ICON-EMOJI-001 | TODOS — SVGs del icon system, no Unicode | Grep emojis |

### 11.2 Convenciones de nomenclatura

| Elemento | Convención | Ejemplo |
|---|---|---|
| SCSS parciales | `_kebab-case.scss` | `_pricing-page.scss` |
| Templates Twig | `kebab-case.html.twig` | `pricing-page.html.twig` |
| Parciales Twig | `_kebab-case.html.twig` | `_landing-pricing-preview.html.twig` |
| Controladores | `PascalCase + Controller.php` | `PricingController.php` |
| Servicios | `PascalCase + Service.php` | `MetaSitePricingService.php` |
| Routes | `module.feature.action` | `ecosistema_jaraba_core.pricing` |
| CSS classes | BEM `block__element--modifier` | `pricing-card__badge--highlighted` |
| CSS variables | `--ej-semantic-name` | `--ej-color-corporate` |

---

## 12. Plan de Verificación

### 12.1 Tests automatizados

#### Tests unitarios existentes (verificar que no se rompen)

```bash
# Ejecutar dentro del contenedor Docker
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app && php vendor/bin/phpunit web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Entity/SaasPlanTest.php"
```

#### Nuevo test: MetaSitePricingService

```bash
# Crear y ejecutar test para el nuevo servicio
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app && php vendor/bin/phpunit web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/MetaSitePricingServiceTest.php"
```

**Casos de test:**
1. `testGetPricingPreviewReturnsAllTiers` — Verifica que devuelve 3 tiers ordenados por weight
2. `testGetFromPriceReturnsFallbackWhenNoConfig` — Verifica fallback cuando no hay ConfigEntity
3. `testGetPricingPreviewCascadeResolution` — Verifica cascade specific → _default

### 12.2 Compilación SCSS

```bash
# Compilar tema principal (SIEMPRE verificar después de cambios SCSS)
# Desde PowerShell en Windows:
cd z:\home\PED\JarabaImpactPlatformSaaS\web\themes\custom\ecosistema_jaraba_theme
npx sass scss/main.scss:css/main.css --style=compressed

# Limpiar caché
docker exec jarabasaas_appserver_1 drush cr
```

**Verificar que:**
- ✅ No hay warnings de funciones deprecadas (`darken`, `lighten`)
- ✅ El output CSS no contiene `Inter` (solo `Outfit`)
- ✅ El output CSS no contiene hex hardcodeados sin variable CSS

### 12.3 Verificación en navegador

Usar el browser tool para verificar cada página después de implementar:

1. **Homepage** (`https://jaraba-saas.lndo.site/`):
   - [ ] Header con nav links actualizados a rutas F4
   - [ ] Hero con headline nuevo (o configurable desde admin)
   - [ ] SIN intentions-grid (solo vertical-selector)
   - [ ] Stats coherentes con landings verticales
   - [ ] Footer con links funcionales

2. **Página /planes** (`https://jaraba-saas.lndo.site/planes`):
   - [ ] NO devuelve 404
   - [ ] Muestra 3 cards de pricing (Free / Pro / Enterprise)
   - [ ] Card Pro tiene badge "Más popular"
   - [ ] CTAs funcionales (apuntan a registro con `?plan=`)
   - [ ] Schema.org SoftwareApplication en `<head>`

3. **Landing vertical cualquiera** (ej: `https://jaraba-saas.lndo.site/agroconecta`):
   - [ ] Pricing preview muestra datos dinámicos si hay ConfigEntity
   - [ ] Lead magnet CTA abre slide-panel con formulario
   - [ ] FAQ tiene Schema.org JSON-LD
   - [ ] Final CTA funciona

4. **Rutas legacy** (ej: `https://jaraba-saas.lndo.site/empleo`):
   - [ ] Redirige 301 a `/empleabilidad`
   - [ ] Verificar en `curl -I` que devuelve `301 Moved Permanently`

5. **Admin: Theme Settings** (`https://jaraba-saas.lndo.site/admin/appearance/settings/ecosistema_jaraba_theme`):
   - [ ] Existe TAB "Meta-sitio: Homepage" con campos configurables
   - [ ] Cambiar headline desde UI → verificar que cambia en frontend

### 12.4 Verificación manual por el usuario

1. Configurar precios reales en `/admin/config/jaraba/plan-tiers`
2. Configurar features por vertical en `/admin/config/jaraba/plan-features`
3. Verificar que `/planes` refleja los datos configurados
4. Verificar que cada landing vertical muestra el pricing dinámico

---

## 13. Roadmap de Ejecución por Sprints

### Sprint 1: Emergency Fixes (Prioridad Crítica) — 2-3 días

| ID | Tarea | Esfuerzo |
|---|---|---|
| C6 | Crear página `/planes` con pricing dinámico | 4h |
| C1 | Reescribir fallbacks hero homepage | 2h |
| C1 | Eliminar intentions-grid de homepage | 30min |
| C4 | Configurar redirects 301 legacy → F4 | 2h |
| C5 | Unificar/eliminar métricas inconsistentes | 2h |
| | SCSS compilation + cache clear + verificación | 1h |
| | **Total Sprint 1** | **~12h** |

### Sprint 2: Core Funnel — 1 semana

| ID | Tarea | Esfuerzo |
|---|---|---|
| C2 | Crear `MetaSitePricingService` + inyección en controller | 4h |
| C2 | Actualizar `_landing-pricing-preview.html.twig` | 2h |
| C3 | Implementar lead magnet con slide-panel | 6h |
| C1 | Actualizar nav links header a rutas F4 | 1h |
| C5 | Reemplazar testimonios ficticios o quitar sección | 2h |
| C1 | Theme settings TAB Meta-sitio | 3h |
| | Tests unitarios para MetaSitePricingService | 2h |
| | **Total Sprint 2** | **~20h** |

### Sprint 3: Growth Infrastructure — 2 semanas

| ID | Tarea | Esfuerzo |
|---|---|---|
| C7 | SEO: Schema.org Product, meta tags, OG | 4h |
| C8 | Verificar/crear páginas `/contacto`, `/sobre-nosotros` | 4h |
| C8 | Verificar links footer funcionales | 2h |
| VERT-01 | Crear landing F4 para Instituciones (B2G) | 6h |
| VERT-02 | Crear landing F4 para Talento (reclutadores) | 6h |
| UX-01 | Añadir demo/video/screenshots del producto | 4h |
| | Verificación final en navegador | 2h |
| | **Total Sprint 3** | **~28h** |

---

## 14. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-26 | 1.0 | Creación inicial basada en auditoría integral + Doc 178 WP |
