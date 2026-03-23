# Plan de Elevación: Casos de Éxito a Clase Mundial 10/10

> **Versión:** 1.0.0
> **Fecha:** 2026-03-23
> **Autor:** Claude Opus 4.6 (1M context)
> **Estado:** Propuesta — pendiente de aprobación
> **Score actual:** 3/15 (LANDING-CONVERSION-SCORE-001)
> **Score objetivo:** 15/15 (10/10 clase mundial)
> **Verticales afectados:** 9 comerciales (Demo excluido)
> **Módulos principales:** `jaraba_success_cases`, `ecosistema_jaraba_core`, 8 módulos verticales, `ecosistema_jaraba_theme`
> **Directriz raíz:** CASE-STUDY-PATTERN-001, SUCCESS-CASES-001, LANDING-CONVERSION-SCORE-001, PRICING-4TIER-001

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Auditoría del Estado Actual (3/15)](#2-auditoría-del-estado-actual-315)
   - 2.1 [Evaluación criterio por criterio](#21-evaluación-criterio-por-criterio)
   - 2.2 [Violaciones de directrices detectadas](#22-violaciones-de-directrices-detectadas)
   - 2.3 [Brecha arquitectónica: SuccessCase entity desconectada](#23-brecha-arquitectónica-successcase-entity-desconectada)
3. [Arquitectura Objetivo](#3-arquitectura-objetivo)
   - 3.1 [Principio: Single Source of Truth (SuccessCase entity)](#31-principio-single-source-of-truth-successcase-entity)
   - 3.2 [Principio: Template único parametrizado](#32-principio-template-único-parametrizado)
   - 3.3 [Principio: Precios desde MetaSitePricingService](#33-principio-precios-desde-metasitepricingservice)
   - 3.4 [Principio: URLs via Url::fromRoute()](#34-principio-urls-via-urlfromroute)
   - 3.5 [Diagrama de flujo de datos](#35-diagrama-de-flujo-de-datos)
4. [Fases de Implementación](#4-fases-de-implementación)
   - 4.1 [Fase 0 — Fundamentos: SuccessCase entity + tenant isolation](#41-fase-0--fundamentos-successcase-entity--tenant-isolation)
   - 4.2 [Fase 1 — Controller unificado + template parametrizado](#42-fase-1--controller-unificado--template-parametrizado)
   - 4.3 [Fase 2 — Hero impactante + Trust badges + Urgency](#43-fase-2--hero-impactante--trust-badges--urgency)
   - 4.4 [Fase 3 — Secciones de conversión faltantes](#44-fase-3--secciones-de-conversión-faltantes)
   - 4.5 [Fase 4 — Social proof denso + Lead magnet](#45-fase-4--social-proof-denso--lead-magnet)
   - 4.6 [Fase 5 — Sticky CTA + Animaciones + Mobile-first](#46-fase-5--sticky-cta--animaciones--mobile-first)
   - 4.7 [Fase 6 — SEO avanzado + Schema.org completo](#47-fase-6--seo-avanzado--schemaorg-completo)
   - 4.8 [Fase 7 — Tracking completo + A/B Testing](#48-fase-7--tracking-completo--ab-testing)
   - 4.9 [Fase 8 — Accesibilidad WCAG 2.1 AA](#49-fase-8--accesibilidad-wcag-21-aa)
   - 4.10 [Fase 9 — Salvaguardas y validadores](#410-fase-9--salvaguardas-y-validadores)
5. [Tabla de Correspondencia: Especificaciones Técnicas](#5-tabla-de-correspondencia-especificaciones-técnicas)
6. [Tabla de Cumplimiento de Directrices](#6-tabla-de-cumplimiento-de-directrices)
7. [Impacto en Setup Wizard y Daily Actions](#7-impacto-en-setup-wizard-y-daily-actions)
8. [Salvaguardas Propuestas](#8-salvaguardas-propuestas)
9. [Riesgos y Mitigaciones](#9-riesgos-y-mitigaciones)
10. [Criterios de Aceptación 10/10](#10-criterios-de-aceptación-1010)
11. [Glosario de Siglas](#11-glosario-de-siglas)

---

## 1. Resumen Ejecutivo

Las 9 páginas de caso de éxito del SaaS (una por vertical comercial) son una pieza clave del embudo de conversión: transforman visitantes anónimos en registros cualificados mediante storytelling de transformación real. Actualmente **puntúan 3/15** en los criterios LANDING-CONVERSION-SCORE-001, con 10 fallos completos y 4 parciales.

### Hallazgos críticos

| # | Hallazgo | Impacto | Directriz violada |
|---|----------|---------|-------------------|
| 1 | **Entidad SuccessCase existe pero está completamente desconectada** de los 8 controllers hardcodeados | Testimonios, métricas y narrativas son ficción estática en PHP — no editables desde UI | SUCCESS-CASES-001 (P1) |
| 2 | **Precios hardcodeados en Twig y PHP** (149€, 299€, etc.) | Desincronización con MetaSitePricingService y SaasPlan entities | NO-HARDCODE-PRICE-001 (P0) |
| 3 | **URLs hardcodeadas como strings** (`'/planes/agroconecta'`) | Con prefijo `/es/` activo, generan 404 | ROUTE-LANGPREFIX-001 (P0) |
| 4 | **Sin Sticky CTA** — parcial no incluido + JS desconectado | Pérdida de conversiones en scroll largo (9-10 secciones) | LANDING-CONVERSION-SCORE-001 §12 |
| 5 | **Animaciones reveal rotas** — clases CSS presentes pero JS no las procesa | UX plana, sin engagement visual | LANDING-CONVERSION-SCORE-001 §13 |
| 6 | **Sin FAQ, sin lead magnet, sin features grid, sin comparativa, sin how-it-works** | 5 secciones completas ausentes | LANDING-CONVERSION-SCORE-001 §§4-8,10 |
| 7 | **Solo 1 testimonial por página** (criterio exige 4+) | Social proof insuficiente | LANDING-CONVERSION-SCORE-001 §7 |
| 8 | **9 templates casi idénticos (~95%) duplicados** manualmente | Mantenimiento de 2.250 líneas vs. ~350 con template parametrizado | DRY, mantenibilidad |
| 9 | **SuccessCase entity sin tenant_id** | Sin aislamiento multi-tenant | TENANT-001 (P0) |
| 10 | **SUCCESS-CASES-001 no está en CLAUDE.md** ni tiene validator | Regla P1 sin enforcement | SAFEGUARD gap |

### Objetivo

Elevar las 9 páginas de caso de éxito a **15/15** (10/10 clase mundial) mediante:

- **Consolidación arquitectónica**: Un controller unificado (`CaseStudyLandingController`) alimentado por la entidad `SuccessCase` ya existente
- **Template único parametrizado**: Una sola plantilla Twig con 12 secciones, reutilizando parciales existentes del tema
- **Todas las secciones de conversión**: Hero urgente, trust badges, pain points, how-it-works, features, comparativa, social proof denso, lead magnet, 4 tiers pricing, FAQ con Schema.org, CTA final, sticky CTA
- **Cumplimiento completo** de las 140+ directrices del proyecto

---

## 2. Auditoría del Estado Actual (3/15)

### 2.1 Evaluación criterio por criterio

| # | Criterio LANDING-CONVERSION-SCORE-001 | Estado actual | Detalle |
|---|---------------------------------------|---------------|---------|
| 1 | Hero impactante (headline + subtitle + 2 CTAs + urgency badge + split image/video) | **PARCIAL** | Tiene H1, subtitle y 2 CTAs. Falta urgency badge. Background-image oscuro sin split — no hay imagen visible del protagonista junto al headline. No hay video. |
| 2 | Trust badges visibles inmediatamente bajo el hero (14d trial, RGPD, sin tarjeta) | **FALLO** | Los trust badges solo aparecen al final (sección CTA final), no bajo el hero. El visitante debe hacer scroll completo para verlos. |
| 3 | Pain points con título + icono + descripción | **PARCIAL** | Existe sección `cs-pain` con 4 tarjetas, pero son métricas cuantitativas (horas/semana, €/mes), no pain points narrativos con icono duotone propio por punto. Solo 1 icono genérico `alert-circle`. |
| 4 | 3 pasos claros de cómo funciona | **FALLO** | No existe sección how-it-works. El timeline (sección 5) es la historia cronológica del protagonista (Día 1, 7, 14), no los pasos orientados al prospecto ("Regístrate → Configura → Crece"). |
| 5 | 12+ features con iconos duotone de marca | **FALLO** | No hay sección de features. La sección "descubrimiento" lista 4-6 bullets de producto sin `jaraba_icon()` duotone. |
| 6 | Comparativa 3 columnas vs. competidores con checkmarks | **FALLO** | Solo JarabaLex tiene comparativa (2 columnas Aranzadi vs JarabaLex, no 3). Las otras 8 verticales carecen de comparativa. |
| 7 | Social proof denso (4+ testimonials, 3+ métricas, logos partner, case study card) | **FALLO** | 1 testimonial por página. Métricas de resultados presentes (3-5 datos). Sin logos de partners. Sin tarjeta de caso cruzado. Total: 1 testimonio = insuficiente. |
| 8 | Lead magnet con captura de email | **FALLO** | Ausente en las 9 plantillas. No hay formulario de email, ni referencia a `PublicSubscribeController` ni parcial `_lead-magnet`. |
| 9 | 3-4 tiers pricing inline con CTA por plan y badge "Más popular" | **PARCIAL** | 3 tiers por plantilla (omiten Starter o Enterprise según vertical). PRICING-4TIER-001 define 4 tiers canónicos. Badge personalizado al personaje ("El plan de Elena") en lugar de "Más popular". Precios hardcodeados en Twig/PHP, no desde MetaSitePricingService. |
| 10 | FAQ 10+ preguntas con Schema.org FAQPage | **FALLO** | No hay sección FAQ en ninguna plantilla. El JSON-LD es `@type: Article`, no `FAQPage`. |
| 11 | CTA final con urgencia | **PASS** | Sección `cs-final-cta` presente en las 9 plantillas con título urgente, subtítulo, CTA btn--xl con tracking, y 3 trust badges con `jaraba_icon()`. |
| 12 | Sticky CTA visible al hacer scroll | **FALLO** | El parcial `_landing-sticky-cta.html.twig` no está incluido en ningún template. El JS `landing-sticky-cta.js` busca `.landing-hero` / `.landing-final-cta`, clases que los case studies no usan (usan `.cs-hero` / `.cs-final-cta`). La library `landing-sticky-cta` tampoco es dependencia. |
| 13 | Animaciones reveal fade-up en cada sección below-the-fold | **FALLO** | Todas las secciones tienen `class="reveal-element reveal-fade-up"` en el HTML. Sin embargo, `scroll-animations.js` solo observa `.animate-on-scroll:not(.is-observed)` — la clase `.reveal-element` no tiene ningún CSS de estado inicial ni transición, y el behavior JS no la busca. Las animaciones están escritas pero **no funcionan en runtime**. |
| 14 | Tracking completo (data-track-cta en CADA CTA + data-track-position en CADA sección) | **PARCIAL** | 5 CTAs tienen `data-track-cta` + `data-track-position`. Pero: (a) las secciones `<section>` no tienen `data-track-position`, (b) el 2º CTA del hero (scroll anchor) carece de tracking. |
| 15 | Mobile-first (sticky CTA bottom, touch targets 44px, responsive layout) | **PARCIAL** | CSS mobile-first con breakpoints `min-width` correcto. Grid responsivo funcional. Pero: sin sticky CTA mobile, sin `min-height: 44px` explícito en touch targets, y un breakpoint `max-width: 600px` que viola el patrón mobile-first del resto del SCSS. |

**Score efectivo: 1 PASS + 4 PARCIALES (×0.5) + 10 FALLOS = 3/15**

### 2.2 Violaciones de directrices detectadas

| Directriz | Violación | Severidad | Archivos afectados |
|-----------|-----------|-----------|-------------------|
| **SUCCESS-CASES-001** (P1) | Datos hardcodeados en 8 controllers, entidad SuccessCase ignorada | Alta | 8 `*CaseStudyController.php` |
| **NO-HARDCODE-PRICE-001** (P0) | Precios EUR en Twig (149€, 299€, 129€, 249€...) y en PHP (`'19'`, `'79'`) | Alta | 9 templates + 8 controllers |
| **ROUTE-LANGPREFIX-001** (P0) | `#pricing_url => '/planes/agroconecta'` como string, no `Url::fromRoute()` | Alta | 8 controllers |
| **PRICING-4TIER-001** | Solo 3 de 4 tiers mostrados (omiten Starter o Enterprise) | Media | 9 templates |
| **TENANT-001** (P0) | Entidad `SuccessCase` sin campo `tenant_id` | Alta | `SuccessCase.php` |
| **TWIG-INCLUDE-ONLY-001** | Templates de caso de éxito no usan `{% include %}` con `only` — son monolíticos | Media | 9 templates |
| **CASE-STUDY-PATTERN-001** | `hook_preprocess_html` inyecta `compliance-page` body class a JarabaLex case study | Baja | `ecosistema_jaraba_theme.theme:2090` |
| **CSS-VAR-ALL-COLORS-001** | Cumplido ✓ | — | — |
| **SCSS-COLORMIX-001** | Cumplido ✓ | — | — |
| **ICON-CONVENTION-001** | Cumplido ✓ (3 iconos usados con formato correcto) | — | — |
| **FUNNEL-COMPLETENESS-001** | Parcial — CTAs tracked pero secciones sin `data-track-position` | Media | 9 templates |
| **ZERO-REGION-001** | Cumplido ✓ (via `page--dashboard.html.twig` y `page--jarabalex.html.twig`) | — | — |
| **ENTITY-PREPROCESS-001** | Cumplido ✓ en `jaraba_success_cases.module` | — | — |
| **ANNUAL-DISCOUNT-001** | Sin verificar — precios no vienen de SaasPlan entities | Media | 9 templates |

### 2.3 Brecha arquitectónica: SuccessCase entity desconectada

El aprendizaje #143 (2026-02-27) documenta la creación del módulo `jaraba_success_cases` con la entidad `SuccessCase` (20+ campos: narrativa, métricas JSON, rating, vertical, media). Establece la regla **SUCCESS-CASES-001 (P1)**: "Todo caso de éxito publicado DEBE provenir de la entidad centralizada SuccessCase."

**Realidad actual:**

```
jaraba_success_cases (módulo habilitado, entidad SuccessCase con 20+ campos)
          ↕ DESCONECTADO ↕
8 CaseStudyControllers (datos hardcodeados en PHP)
9 templates Twig (textos hardcodeados, precios estáticos)
```

La entidad tiene campos perfectamente alineados con lo que necesitan los case study controllers:
- `challenge_before` → sección "El Dolor"
- `solution_during` → sección "El Descubrimiento"
- `result_after` → sección "Los Resultados"
- `quote_short` / `quote_long` → sección "Testimonial"
- `metrics_json` → métricas antes/después
- `hero_image` → imagen hero
- `vertical` → enrutamiento por vertical
- `rating` → estrellas del testimonial

Pero **ningún controller la consume**. La API `/api/success-cases` existe pero no tiene consumidores en el frontend de case studies.

---

## 3. Arquitectura Objetivo

### 3.1 Principio: Single Source of Truth (SuccessCase entity)

Toda la información de un caso de éxito (narrativa, métricas, testimonios, imágenes) se almacena en la entidad `SuccessCase` y se edita desde la UI de administración de Drupal. Los controllers se limitan a cargar la entidad y pasarla al template.

**Campos adicionales necesarios en SuccessCase:**

| Campo | Tipo | Propósito |
|-------|------|-----------|
| `tenant_id` | `entity_reference` (Group) | Aislamiento multi-tenant (TENANT-001) |
| `protagonist_name` | `string` | Nombre del protagonista para personalizar secciones |
| `protagonist_role` | `string` | Cargo/rol del protagonista |
| `protagonist_company` | `string` | Empresa/organización |
| `protagonist_image` | `image` | Foto del protagonista (separada del hero) |
| `timeline_json` | `string_long` | JSON con hitos del timeline (día, título, texto) |
| `pain_points_json` | `string_long` | JSON con pain points (label, before, after, change) |
| `discovery_features` | `string_long` | JSON con features descubiertas |
| `comparison_json` | `string_long` | JSON con columnas comparativas (opcional por vertical) |
| `additional_testimonials_json` | `string_long` | JSON con testimonios adicionales para social proof denso |
| `faq_json` | `string_long` | JSON con preguntas frecuentes (min 10) |
| `video_url` | `string` | URL de video testimonial (opcional) |
| `partner_logos_json` | `string_long` | JSON con logos de partners/asociaciones |
| `schema_date_published` | `datetime` | Fecha de publicación para Schema.org |
| `cta_urgency_text` | `string` | Texto del badge de urgencia del hero |
| `before_after_image` | `image` | Imagen antes/después |
| `dashboard_image` | `image` | Captura del dashboard |
| `discovery_image` | `image` | Imagen de descubrimiento del producto |
| `climax_image` | `image` | Imagen del momento cumbre |

> **Nota sobre JSON fields:** Se mantiene el patrón `metrics_json` ya existente en la entidad. Los campos JSON permiten estructuras flexibles por vertical sin requerir sub-entidades. El `template_preprocess_success_case()` ya decodifica JSON — se extiende el mismo patrón.

### 3.2 Principio: Template único parametrizado

Los 9 templates actuales (~248 líneas cada uno, ~2.250 líneas total) se reemplazan por **un solo template parametrizado** compuesto de parciales Twig reutilizables:

```
case-study-landing.html.twig (~350 líneas)
  ├── {% include 'partials/_cs-hero.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-trust-badges.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-context.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-pain-points.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-how-it-works.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-discovery.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-timeline.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-results.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-features.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-comparison.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-social-proof.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-lead-magnet.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-pricing.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-faq.html.twig' with {...} only %}
  ├── {% include 'partials/_cs-final-cta.html.twig' with {...} only %}
  └── {% include 'partials/_landing-sticky-cta.html.twig' with {...} only %}
```

**Ventajas:**
- De 2.250 líneas a ~350 + parciales reutilizables
- Cada parcial testable y reutilizable en otras landing pages
- Cumple TWIG-INCLUDE-ONLY-001 (keyword `only`)
- Textos siempre con `{% trans %}` para traducibilidad
- Secciones opcionales controladas por `{% if comparison is not empty %}` para verticales sin comparativa

### 3.3 Principio: Precios desde MetaSitePricingService

```php
// ANTES (viola NO-HARDCODE-PRICE-001):
'#pricing' => ['starter_price' => '19', 'professional_price' => '79']

// DESPUÉS (correcto):
$pricing = $this->metaSitePricingService->getPricingPreview($vertical);
// $pricing contiene los 4 tiers con precios reales desde SaasPlan entities
```

El preprocess hook inyecta `$variables['ped_pricing']` y el template usa `{{ ped_pricing.{vertical}.professional_price|default('79') }}`.

### 3.4 Principio: URLs via Url::fromRoute()

```php
// ANTES (viola ROUTE-LANGPREFIX-001):
'#pricing_url' => '/planes/agroconecta'

// DESPUÉS (correcto, con try-catch por seguridad):
try {
  $pricingUrl = Url::fromRoute('jaraba_billing.plans.vertical', ['vertical' => $vertical])->toString();
} catch (\Exception $e) {
  $pricingUrl = '/planes/' . $vertical;
}
```

### 3.5 Diagrama de flujo de datos

```
┌─────────────────────┐     ┌─────────────────────────────┐
│  Admin UI            │     │  SuccessCase entity          │
│  /admin/content/     │────>│  (20+ campos + 18 nuevos)    │
│  success-case        │     │  tenant_id, vertical,        │
└─────────────────────┘     │  métricas JSON, FAQ JSON...  │
                             └──────────────┬──────────────┘
                                            │ EntityQuery
                                            ▼
┌─────────────────────────────────────────────────────────┐
│  CaseStudyLandingController::caseStudy($vertical, $slug)│
│  - Carga SuccessCase por slug + vertical                 │
│  - Inyecta MetaSitePricingService para pricing           │
│  - Inyecta Url::fromRoute() para URLs                    │
│  - Pasa render array con #theme = 'case_study_landing'   │
└──────────────────────────┬──────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────┐
│  hook_preprocess_page() en ecosistema_jaraba_theme.theme │
│  - Inyecta ped_pricing desde MetaSitePricingService       │
│  - Inyecta drupalSettings para JS                         │
│  - Inyecta body classes correctas                         │
└──────────────────────────┬───────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────┐
│  case-study-landing.html.twig (template único)            │
│  16 secciones via {% include %} parciales con only        │
│  Schema.org Article + FAQPage + Review JSON-LD            │
│  data-track-cta + data-track-position en todos los CTAs   │
│  reveal-element animations + sticky CTA                   │
└──────────────────────────────────────────────────────────┘
```

---

## 4. Fases de Implementación

### 4.1 Fase 0 — Fundamentos: SuccessCase entity + tenant isolation

**Objetivo:** Preparar la entidad SuccessCase para ser el SSOT de todos los datos de caso de éxito, incluyendo aislamiento multi-tenant.

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_success_cases/src/Entity/SuccessCase.php` | Añadir 18 campos nuevos (ver §3.1) + `tenant_id` entity_reference |
| `jaraba_success_cases/jaraba_success_cases.install` | `hook_update_10002()` con `updateFieldableEntityType()` + `installFieldStorageDefinition()` para cada campo nuevo |
| `jaraba_success_cases/src/SuccessCaseAccessControlHandler.php` | Añadir verificación de tenant match en update/delete (TENANT-ISOLATION-ACCESS-001) |
| `jaraba_success_cases/src/Form/SuccessCaseForm.php` | Extender de `PremiumEntityFormBase` (PREMIUM-FORMS-PATTERN-001) con `getSectionDefinitions()` y `getFormIcon()` |

**Reglas a cumplir:**
- TENANT-001: campo `tenant_id` obligatorio con entity_reference a Group
- TENANT-ISOLATION-ACCESS-001: checkAccess() verifica tenant match
- UPDATE-HOOK-FIELDABLE-001: usar `getFieldStorageDefinitions()` (NO `getBaseFieldDefinitions()`)
- UPDATE-HOOK-CATCH-001: `catch(\Throwable)` (NO `\Exception`)
- PREMIUM-FORMS-PATTERN-001: form extiende PremiumEntityFormBase
- ENTITY-001: ya implementa EntityOwnerInterface + EntityChangedInterface ✓
- ACCESS-RETURN-TYPE-001: checkAccess() declara `: AccessResultInterface`

**hook_update_10002() — pseudocódigo:**

```php
function jaraba_success_cases_update_10002(): string {
  try {
    $manager = \Drupal::entityDefinitionUpdateManager();
    $entity_type = \Drupal::entityTypeManager()->getDefinition('success_case');
    $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('success_case');
    $manager->updateFieldableEntityType($entity_type, $fields);

    // Instalar cada campo nuevo individualmente
    $new_fields = ['tenant_id', 'protagonist_name', 'protagonist_role', ...];
    foreach ($new_fields as $field_name) {
      if (isset($fields[$field_name])) {
        $field = $fields[$field_name];
        $field->setName($field_name);
        $field->setTargetEntityTypeId('success_case');
        $manager->installFieldStorageDefinition($field_name, 'success_case', 'jaraba_success_cases', $field);
      }
    }
    return 'Added 18 new fields to SuccessCase entity including tenant_id.';
  }
  catch (\Throwable $e) {
    return 'Failed: ' . $e->getMessage();
  }
}
```

**Datos iniciales:** Crear un script `scripts/migration/seed-success-cases.php` que migre los datos hardcodeados de los 8 controllers a entidades SuccessCase. Esto preserva la narrativa actual mientras permite edición futura desde UI.

---

### 4.2 Fase 1 — Controller unificado + template parametrizado

**Objetivo:** Reemplazar los 8 controllers + 9 templates por un controller y un template únicos.

**Archivos nuevos:**

| Archivo | Propósito |
|---------|-----------|
| `ecosistema_jaraba_core/src/Controller/CaseStudyLandingController.php` | Controller unificado con DI de SuccessCase storage, MetaSitePricingService, TenantContextService |
| `ecosistema_jaraba_theme/templates/case-study-landing.html.twig` | Template maestro (reemplaza 9 templates) |
| `ecosistema_jaraba_theme/templates/partials/_cs-hero.html.twig` | Parcial hero con urgency badge + trust badges |
| `ecosistema_jaraba_theme/templates/partials/_cs-context.html.twig` | Parcial contexto del protagonista |
| `ecosistema_jaraba_theme/templates/partials/_cs-pain-points.html.twig` | Parcial pain points con iconos duotone |
| `ecosistema_jaraba_theme/templates/partials/_cs-how-it-works.html.twig` | Parcial 3 pasos (NUEVO) |
| `ecosistema_jaraba_theme/templates/partials/_cs-discovery.html.twig` | Parcial descubrimiento |
| `ecosistema_jaraba_theme/templates/partials/_cs-timeline.html.twig` | Parcial timeline 14 días |
| `ecosistema_jaraba_theme/templates/partials/_cs-results.html.twig` | Parcial resultados con métricas animadas |
| `ecosistema_jaraba_theme/templates/partials/_cs-features.html.twig` | Parcial 12+ features con iconos (NUEVO) |
| `ecosistema_jaraba_theme/templates/partials/_cs-comparison.html.twig` | Parcial comparativa 3 columnas (NUEVO) |
| `ecosistema_jaraba_theme/templates/partials/_cs-social-proof.html.twig` | Parcial social proof denso (NUEVO) |
| `ecosistema_jaraba_theme/templates/partials/_cs-pricing.html.twig` | Parcial mini pricing 4 tiers |
| `ecosistema_jaraba_theme/templates/partials/_cs-faq.html.twig` | Parcial FAQ 10+ preguntas con Schema.org (NUEVO) |
| `ecosistema_jaraba_theme/templates/partials/_cs-final-cta.html.twig` | Parcial CTA final (refactor del existente) |

**Archivos a eliminar (tras migración):**

Los 9 templates `*-case-study.html.twig` y los 8 `*CaseStudyController.php` se mantienen temporalmente como fallback y se eliminan tras verificar la migración completa.

**CaseStudyLandingController — estructura:**

```php
namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

final class CaseStudyLandingController extends ControllerBase {

  // DI: entity_type.manager, ecosistema_jaraba_core.metasite_pricing,
  //     ecosistema_jaraba_core.tenant_context, extension.list.theme

  public function caseStudy(string $vertical, string $slug): array {
    $cases = $this->entityTypeManager
      ->getStorage('success_case')
      ->loadByProperties([
        'slug' => $slug,
        'vertical' => $vertical,
        'status' => TRUE,
      ]);

    if (empty($cases)) {
      throw new NotFoundHttpException();
    }

    $case = reset($cases);
    $themePath = $this->themeExtensionList->getPath('ecosistema_jaraba_theme');

    // Precios desde MetaSitePricingService (NO-HARDCODE-PRICE-001)
    $pricing = $this->pricingService->getPricingPreview($vertical);

    // URLs via Url::fromRoute() (ROUTE-LANGPREFIX-001)
    try {
      $pricingUrl = Url::fromRoute('jaraba_billing.plans.vertical', [
        'vertical' => $vertical,
      ])->toString();
    }
    catch (\Exception $e) {
      $pricingUrl = '/' . $vertical . '/precios';
    }

    try {
      $registerUrl = Url::fromRoute('jaraba_registration.register_vertical', [
        'vertical' => $vertical,
      ])->toString();
    }
    catch (\Exception $e) {
      $registerUrl = '/registro/' . $vertical;
    }

    return [
      '#theme' => 'case_study_landing',
      '#case' => $this->prepareCaseData($case, $themePath),
      '#pricing' => $pricing,
      '#pricing_url' => $pricingUrl,
      '#register_url' => $registerUrl,
      '#vertical' => $vertical,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/case-study-landing',
          'ecosistema_jaraba_theme/scroll-animations',
          'ecosistema_jaraba_theme/landing-sticky-cta',
        ],
      ],
      '#cache' => [
        'max-age' => 86400,
        'tags' => ['success_case_list', 'success_case:' . $case->id()],
        'contexts' => ['url.path'],
      ],
    ];
  }

  private function prepareCaseData(SuccessCase $case, string $themePath): array {
    // Extrae todos los campos de la entidad a un array primitivo
    // Decodifica JSON fields (metrics, timeline, pain_points, faq, etc.)
    // Resuelve URLs de imágenes
    // Retorna array plano apto para Twig
  }
}
```

**Routing unificado en `ecosistema_jaraba_core.routing.yml`:**

```yaml
ecosistema_jaraba_core.case_study:
  path: '/{vertical}/caso-de-exito/{slug}'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\CaseStudyLandingController::caseStudy'
    _title: 'Caso de éxito'
  requirements:
    vertical: 'jarabalex|agroconecta|comercioconecta|empleabilidad|emprendimiento|formacion|serviciosconecta|andalucia-ei|content-hub'
    slug: '[a-z0-9-]+'
    _access: 'TRUE'
```

> **Decisión de diseño:** Ruta centralizada con constraint regex en `vertical` para evitar 9 entradas de routing duplicadas. El path mantiene compatibilidad con las URLs actuales.

**hook_theme() en `ecosistema_jaraba_core.module`:**

```php
'case_study_landing' => [
  'variables' => [
    'case' => [],
    'pricing' => [],
    'pricing_url' => '',
    'register_url' => '',
    'vertical' => '',
  ],
  'template' => 'case-study-landing',
  'path' => $theme_path . '/templates',
],
```

---

### 4.3 Fase 2 — Hero impactante + Trust badges + Urgency

**Objetivo:** Criterios 1 y 2 de LANDING-CONVERSION-SCORE-001.

**Parcial `_cs-hero.html.twig`:**

```twig
{# Parcial: Hero de caso de éxito — criterios 1+2 LANDING-CONVERSION-SCORE-001 #}
<section class="cs-hero" aria-labelledby="cs-title" style="background-image: url('{{ hero_image }}');">
  <div class="cs-hero__overlay"></div>
  <div class="cs-hero__container">

    {# Urgency badge (criterio 1) #}
    {% if urgency_text %}
      <div class="cs-hero__urgency-badge" aria-label="{{ urgency_text }}">
        {{ jaraba_icon('status', 'clock', { variant: 'duotone', size: '16px', color: 'white' }) }}
        <span>{{ urgency_text }}</span>
      </div>
    {% endif %}

    <p class="cs-hero__eyebrow">{% trans %}Caso de éxito{% endtrans %} · {{ vertical_label }}</p>
    <h1 id="cs-title" class="cs-hero__title">{{ headline }}</h1>
    <p class="cs-hero__subtitle">{{ subtitle }}</p>

    <div class="cs-hero__ctas">
      <a href="{{ register_url }}" class="btn btn--primary btn--lg"
         data-track-cta="cs_{{ vertical }}_hero_trial"
         data-track-position="case_study_hero">
        {% trans %}Empieza gratis 14 días{% endtrans %}
      </a>
      <a href="#cs-results" class="btn btn--outline btn--lg"
         data-track-cta="cs_{{ vertical }}_hero_results"
         data-track-position="case_study_hero">
        {% trans %}Ver resultados{% endtrans %}
      </a>
    </div>

    {# Trust badges inmediatamente bajo CTAs (criterio 2) #}
    <div class="cs-hero__trust" role="list" aria-label="{% trans %}Garantías{% endtrans %}">
      <span class="cs-hero__trust-badge" role="listitem">
        {{ jaraba_icon('ui', 'shield', { variant: 'duotone', size: '16px', color: 'white' }) }}
        {% trans %}14 días gratis{% endtrans %}
      </span>
      <span class="cs-hero__trust-badge" role="listitem">
        {{ jaraba_icon('legal', 'shield-privacy', { variant: 'duotone', size: '16px', color: 'white' }) }}
        {% trans %}RGPD compliant{% endtrans %}
      </span>
      <span class="cs-hero__trust-badge" role="listitem">
        {{ jaraba_icon('ui', 'lock', { variant: 'duotone', size: '16px', color: 'white' }) }}
        {% trans %}Sin tarjeta de crédito{% endtrans %}
      </span>
    </div>
  </div>
</section>
```

**SCSS nuevo en `_cs-hero` (dentro del SCSS de case study):**

- `.cs-hero__urgency-badge`: `position: absolute; top: 1.5rem; right: 1.5rem; background: var(--ej-color-warning, #F59E0B); color: white; padding: 0.5rem 1rem; border-radius: 2rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; animation: pulse 2s infinite;`
- `.cs-hero__trust`: `display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; justify-content: center;`
- `.cs-hero__trust-badge`: `display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: color-mix(in srgb, white 90%, transparent); font-weight: 500;`

---

### 4.4 Fase 3 — Secciones de conversión faltantes

**Objetivo:** Criterios 3 (pain points mejorados), 4 (how-it-works), 5 (features), 6 (comparativa).

#### 4.4.1 Pain points mejorados (criterio 3)

Parcial `_cs-pain-points.html.twig` — 4 tarjetas con icono duotone propio, título narrativo y descripción del dolor, además de las métricas cuantitativas actuales.

Los pain points se almacenan en `pain_points_json` de SuccessCase:

```json
[
  {
    "icon_category": "analytics",
    "icon_name": "chart-line",
    "title": "Facturación estancada",
    "description": "Vendía a granel a Italia sin margen de beneficio",
    "metric_before": "8.000 €/mes",
    "metric_after": "30.000 €/mes",
    "metric_change": "+275%"
  }
]
```

#### 4.4.2 How-it-works (criterio 4) — NUEVO

Parcial `_cs-how-it-works.html.twig` — 3 pasos orientados al prospecto.

Los 3 pasos son configurables en Theme Settings (ya que son comunes a todas las verticales con variantes menores). Cada vertical puede tener steps personalizados en SuccessCase, o heredar los genéricos del tema.

```twig
<section class="cs-how-it-works reveal-element reveal-fade-up" aria-labelledby="cs-how-title">
  <div class="cs-section__container">
    <h2 id="cs-how-title" class="cs-section__title">{% trans %}Así de fácil{% endtrans %}</h2>
    <div class="cs-how__steps">
      {% for step in steps %}
        <div class="cs-how__step">
          <div class="cs-how__number">{{ loop.index }}</div>
          {{ jaraba_icon(step.icon_category, step.icon_name, { variant: 'duotone', size: '48px', color: 'naranja-impulso' }) }}
          <h3 class="cs-how__step-title">{{ step.title }}</h3>
          <p class="cs-how__step-text">{{ step.description }}</p>
        </div>
      {% endfor %}
    </div>
  </div>
</section>
```

**Pasos genéricos por defecto (configurables desde Theme Settings):**
1. "Regístrate gratis" — icono `actions/add-circle`
2. "Configura tu espacio" — icono `tools/download` (sustituir por `ui/settings`)
3. "Empieza a crecer" — icono `analytics/chart-line`

#### 4.4.3 Features grid (criterio 5) — NUEVO

Parcial `_cs-features.html.twig` — Grid de 12+ features con iconos duotone de marca, alimentado por `discovery_features` JSON de SuccessCase.

```json
[
  {
    "icon_category": "ai",
    "icon_name": "brain",
    "title": "IA Jurídica",
    "description": "Búsqueda semántica en 2M+ sentencias"
  }
]
```

El grid usa `display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;` para responsive sin breakpoints.

#### 4.4.4 Comparativa (criterio 6) — NUEVO

Parcial `_cs-comparison.html.twig` — Tabla de 3 columnas (Método tradicional | Competidor | Nuestra plataforma) con checkmarks/crosses.

Datos desde `comparison_json` de SuccessCase. Secciones opcionales: `{% if comparison is not empty %}`.

**Vertical-specific:** Solo JarabaLex (Aranzadi), AgroConecta (distribuidores), ComercioConecta (marketplaces) tienen competidores nombrados. Los demás usan "Método tradicional" vs "Otros servicios" vs "[Vertical]".

---

### 4.5 Fase 4 — Social proof denso + Lead magnet

**Objetivo:** Criterios 7 y 8 de LANDING-CONVERSION-SCORE-001.

#### 4.5.1 Social proof denso (criterio 7) — NUEVO

Parcial `_cs-social-proof.html.twig` — Sección con:
- **4+ testimonios**: 1 testimonial principal (el existente) + 3 testimonios cortos desde `additional_testimonials_json`
- **3+ métricas globales** de la plataforma (usuarios, verticales, satisfacción) — desde Theme Settings
- **Logos de partners/asociaciones** — desde `partner_logos_json` de SuccessCase
- **Tarjeta de caso cruzado** — enlace a otro caso de éxito de un vertical diferente, generado dinámicamente desde SuccessCase entities publicadas

```twig
<section class="cs-social-proof reveal-element reveal-fade-up" aria-labelledby="cs-proof-title">
  <div class="cs-section__container">
    <h2 id="cs-proof-title">{% trans %}No solo lo decimos nosotros{% endtrans %}</h2>

    {# Métricas globales #}
    <div class="cs-proof__metrics">
      {% for metric in global_metrics %}
        <div class="cs-proof__metric">
          <span class="cs-proof__metric-value" data-count="{{ metric.value }}">0</span>
          <span class="cs-proof__metric-label">{{ metric.label }}</span>
        </div>
      {% endfor %}
    </div>

    {# Testimoniales múltiples #}
    <div class="cs-proof__testimonials">
      {# Testimonial principal (existente) con Schema.org Review #}
      <blockquote class="cs-proof__main-quote"
        itemscope itemtype="https://schema.org/Review">
        <p itemprop="reviewBody">{{ testimonial.quote }}</p>
        <footer>
          <cite itemprop="author" itemscope itemtype="https://schema.org/Person">
            <span itemprop="name">{{ testimonial.name }}</span>
          </cite>
          <span>{{ testimonial.role }}, {{ testimonial.company }}</span>
          <div itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
            <meta itemprop="ratingValue" content="{{ testimonial.rating|default(5) }}">
            <meta itemprop="bestRating" content="5">
            <span aria-label="{% trans %}{{ testimonial.rating|default(5) }} de 5 estrellas{% endtrans %}">
              {% for i in 1..5 %}★{% endfor %}
            </span>
          </div>
        </footer>
      </blockquote>

      {# Testimonios adicionales cortos #}
      {% for extra in additional_testimonials %}
        <blockquote class="cs-proof__short-quote">
          <p>"{{ extra.quote }}"</p>
          <footer><cite>{{ extra.name }}</cite> — {{ extra.role }}</footer>
        </blockquote>
      {% endfor %}
    </div>

    {# Logos de partners #}
    {% if partner_logos is not empty %}
      <div class="cs-proof__logos" aria-label="{% trans %}Partners y asociaciones{% endtrans %}">
        {% for logo in partner_logos %}
          <img src="{{ logo.url }}" alt="{{ logo.name }}" loading="lazy" width="120" height="40">
        {% endfor %}
      </div>
    {% endif %}

    {# Tarjeta de caso cruzado #}
    {% if cross_case %}
      <div class="cs-proof__cross-case">
        <h3>{% trans %}Más historias de éxito{% endtrans %}</h3>
        <a href="{{ cross_case.url }}" class="cs-proof__case-card"
           data-track-cta="cs_{{ vertical }}_cross_case"
           data-track-position="case_study_social_proof">
          <img src="{{ cross_case.image }}" alt="{{ cross_case.title }}" loading="lazy">
          <div>
            <strong>{{ cross_case.title }}</strong>
            <p>{{ cross_case.subtitle }}</p>
          </div>
        </a>
      </div>
    {% endif %}
  </div>
</section>
```

#### 4.5.2 Lead magnet (criterio 8) — NUEVO

Reutilizar el parcial existente `_lead-magnet.html.twig` (ya usado en las landings verticales y homepage). Conectado a `PublicSubscribeController` con `source=lead_magnet_case_study_{vertical}`.

```twig
{% include 'partials/_lead-magnet.html.twig' with {
  title: 'Descarga la guía gratuita'|t,
  subtitle: ('Aprende cómo ' ~ protagonist_name ~ ' transformó su ' ~ vertical_label)|t,
  source: 'lead_magnet_case_study_' ~ vertical,
  cta_text: 'Descargar gratis'|t,
} only %}
```

---

### 4.6 Fase 5 — Sticky CTA + Animaciones + Mobile-first

**Objetivo:** Criterios 12, 13 y 15 de LANDING-CONVERSION-SCORE-001.

#### 4.6.1 Sticky CTA (criterio 12)

**Tres cambios necesarios:**

1. **Incluir parcial en el template:**
   ```twig
   {% include 'partials/_landing-sticky-cta.html.twig' with {
     cta_url: register_url,
     cta_text: 'Empieza gratis 14 días'|t,
   } only %}
   ```

2. **Actualizar `landing-sticky-cta.js`** para reconocer selectores de case study:
   ```javascript
   // Añadir .cs-hero y .cs-final-cta a los selectores existentes
   const heroSelectors = '.landing-hero, .hero-landing, .ped-hero, .cs-hero';
   const finalCtaSelectors = '.landing-final-cta, .ped-cta-saas, .cs-final-cta';
   ```

3. **Añadir library dependency** en la nueva library `case-study-landing`:
   ```yaml
   case-study-landing:
     css:
       theme:
         css/routes/case-study-landing.css: {}
     dependencies:
       - ecosistema_jaraba_theme/global-styling
       - ecosistema_jaraba_theme/scroll-animations
       - ecosistema_jaraba_theme/landing-sticky-cta
   ```

#### 4.6.2 Animaciones reveal (criterio 13)

**El problema actual:** Los templates usan `reveal-element reveal-fade-up` pero el JS busca `animate-on-scroll`.

**Solución — dos opciones (elegir una):**

**Opción A (recomendada):** Añadir `.reveal-element` al `querySelectorAll` del behavior `scrollAnimations`:

```javascript
// En scroll-animations.js, behavior scrollAnimations:
const elements = context.querySelectorAll(
  '.animate-on-scroll:not(.is-observed), .reveal-element:not(.is-observed)'
);
```

Y añadir CSS para `.reveal-element`:

```scss
.reveal-element {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease-out, transform 0.6s ease-out;

  &.is-visible {
    opacity: 1;
    transform: none;
  }
}

.reveal-fade-up {
  // ya cubierto por .reveal-element defaults
}
```

**Opción B:** Reemplazar `reveal-element reveal-fade-up` por `animate-on-scroll` en todos los templates. Más sencillo pero menos semántico.

> **Decisión recomendada:** Opción A — mantiene la semántica y es retrocompatible con los 9 templates existentes durante la transición.

#### 4.6.3 Mobile-first completo (criterio 15)

- **Touch targets 44px:** Añadir `min-height: 44px; min-width: 44px;` a todos los botones y links interactivos en el SCSS de case study
- **Corregir breakpoint `max-width: 600px`** en `.cs-discovery__compare` → reescribir como `min-width: 601px` (mobile-first)
- **Sticky CTA mobile bottom:** Ya cubierto por el parcial `_landing-sticky-cta.html.twig` que tiene `position: fixed; bottom: 0;` en mobile
- **Eliminar dead code:** `.cs-pain__label { margin-top: 0.35rem; }` duplicado

---

### 4.7 Fase 6 — SEO avanzado + Schema.org completo

**Objetivo:** Criterio 10 (FAQ) + mejora de Schema.org existente.

#### 4.7.1 FAQ 10+ preguntas (criterio 10) — NUEVO

Parcial `_cs-faq.html.twig` con preguntas almacenadas en `faq_json` de SuccessCase.

Cada vertical tiene 10 FAQ contextualizadas al caso (ej: "¿Cuánto tardó Elena en configurar JarabaLex?", "¿Necesito conocimientos técnicos?", "¿Qué pasa si no me convence?").

**Schema.org FAQPage** como segundo JSON-LD en el template:

```twig
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {% for faq in faqs %}
    {
      "@type": "Question",
      "name": "{{ faq.question|e('js') }}",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "{{ faq.answer|e('js') }}"
      }
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}
</script>
```

#### 4.7.2 Schema.org Article mejorado

Añadir campos faltantes al JSON-LD de Article:

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "@id": "{{ canonical_url }}#article",
  "headline": "{{ headline }}",
  "description": "{{ meta_description }}",
  "image": "{{ hero_image_absolute }}",
  "url": "{{ canonical_url }}",
  "mainEntityOfPage": { "@type": "WebPage", "@id": "{{ canonical_url }}" },
  "author": { "@type": "Organization", "name": "Plataforma de Ecosistemas" },
  "publisher": {
    "@type": "Organization",
    "name": "Plataforma de Ecosistemas",
    "logo": { "@type": "ImageObject", "url": "{{ logo_url }}" }
  },
  "datePublished": "{{ schema_date_published }}",
  "dateModified": "{{ case.changed }}",
  "review": {
    "@type": "Review",
    "reviewRating": { "@type": "Rating", "ratingValue": "{{ rating }}", "bestRating": "5" },
    "author": { "@type": "Person", "name": "{{ testimonial.name }}" },
    "reviewBody": "{{ testimonial.quote }}"
  }
}
```

---

### 4.8 Fase 7 — Tracking completo + A/B Testing

**Objetivo:** Criterio 14 de LANDING-CONVERSION-SCORE-001.

#### 4.8.1 Tracking de secciones

Añadir `data-track-position` a cada `<section>`:

```twig
<section class="cs-hero" data-track-position="case_study_hero">
<section class="cs-trust" data-track-position="case_study_trust">
<section class="cs-context" data-track-position="case_study_context">
<section class="cs-pain" data-track-position="case_study_pain_points">
<section class="cs-how-it-works" data-track-position="case_study_how_it_works">
<section class="cs-discovery" data-track-position="case_study_discovery">
<section class="cs-timeline" data-track-position="case_study_timeline">
<section class="cs-results" data-track-position="case_study_results">
<section class="cs-features" data-track-position="case_study_features">
<section class="cs-comparison" data-track-position="case_study_comparison">
<section class="cs-social-proof" data-track-position="case_study_social_proof">
<section class="cs-lead-magnet" data-track-position="case_study_lead_magnet">
<section class="cs-pricing" data-track-position="case_study_pricing">
<section class="cs-faq" data-track-position="case_study_faq">
<section class="cs-final-cta" data-track-position="case_study_bottom">
```

#### 4.8.2 Tracking de TODOS los CTAs

Asegurar que **cada** enlace de conversión tenga `data-track-cta`:
- Hero CTA primario: `cs_{vertical}_hero_trial`
- Hero CTA secundario: `cs_{vertical}_hero_results`
- Lead magnet: `cs_{vertical}_lead_magnet`
- Pricing (×4 tiers): `cs_{vertical}_free`, `cs_{vertical}_starter`, `cs_{vertical}_professional`, `cs_{vertical}_enterprise`
- CTA final: `cs_{vertical}_final_cta`
- Caso cruzado: `cs_{vertical}_cross_case`

Total: 10 CTAs trackeados por página.

#### 4.8.3 A/B Testing

Conectar con la infraestructura `PageExperiment` + `metasite-experiments.js` existente. Experimentos sugeridos:
- Hero: con vs. sin video testimonial
- Pricing: 3 tiers vs. 4 tiers
- Lead magnet: posición media vs. posición final
- Urgency badge: countdown vs. texto estático

---

### 4.9 Fase 8 — Accesibilidad WCAG 2.1 AA

**Objetivo:** Cumplimiento WCAG completo.

| Problema actual | Solución |
|-----------------|----------|
| Estrellas `★★★★★` sin `aria-label` | Añadir `aria-label="{% trans %}{{ rating }} de 5 estrellas{% endtrans %}"` |
| Icono `alert-circle` sin `aria-hidden` | Añadir `aria-hidden="true"` a todos los iconos decorativos |
| Secciones sin `aria-labelledby` | Cada `<section>` con `aria-labelledby` apuntando a su `<h2>` id |
| Flecha scroll `↓` sin aria | `aria-label="{% trans %}Desplazar hacia abajo{% endtrans %}"` |
| Touch targets < 44px | `min-height: 44px; min-width: 44px;` en botones y links |
| Contraste insuficiente en overlay hero | Verificar ratio 4.5:1 con overlay actual; ajustar opacidad si necesario |
| Focus visible en CTAs | `:focus-visible { outline: 3px solid var(--ej-color-primary); outline-offset: 2px; }` |
| Skip to main content | Link invisible `#main-content` al inicio del template |

---

### 4.10 Fase 9 — Salvaguardas y validadores

**Objetivo:** Crear mecanismos de defensa automatizados para prevenir regresiones.

#### 4.10.1 Nuevo validator: `validate-case-study-conversion-score.php`

Script que evalúa los 15 criterios de LANDING-CONVERSION-SCORE-001 en el template de caso de éxito:

```php
// CHECK 1: Hero con urgency badge (.cs-hero__urgency-badge)
// CHECK 2: Trust badges bajo hero (.cs-hero__trust)
// CHECK 3: Pain points con iconos (jaraba_icon en _cs-pain-points)
// CHECK 4: How-it-works section (.cs-how-it-works)
// CHECK 5: Features grid (12+ items en _cs-features)
// CHECK 6: Comparison table (.cs-comparison)
// CHECK 7: Social proof (4+ blockquotes en _cs-social-proof)
// CHECK 8: Lead magnet (_lead-magnet include)
// CHECK 9: 4 pricing tiers (.cs-pricing__card × 4)
// CHECK 10: FAQ 10+ (.cs-faq__item count >= 10 + application/ld+json FAQPage)
// CHECK 11: Final CTA (.cs-final-cta)
// CHECK 12: Sticky CTA (_landing-sticky-cta include)
// CHECK 13: Reveal animations (.reveal-element en cada section below hero)
// CHECK 14: data-track-cta count >= 8 + data-track-position en cada section
// CHECK 15: Mobile-first (min-height: 44px en SCSS + no max-width breakpoints)
```

Score < 12 = FAIL, 12-14 = WARN, 15 = PASS.

#### 4.10.2 Nuevo validator: `validate-success-cases-ssot.php`

Verifica que SUCCESS-CASES-001 se cumple:

```php
// CHECK 1: Ningún *CaseStudyController.php contiene arrays hardcodeados de métricas/testimonios
// CHECK 2: CaseStudyLandingController carga datos de SuccessCase entity
// CHECK 3: SuccessCase entity tiene tenant_id field
// CHECK 4: Todos los SuccessCase tienen vertical asignado
// CHECK 5: No hay precios EUR hardcodeados en templates case-study (NO-HARDCODE-PRICE-001)
// CHECK 6: Todas las URLs en controllers usan Url::fromRoute() (ROUTE-LANGPREFIX-001)
```

#### 4.10.3 Actualizar `validate-case-study-completeness.php`

Extender los 7 checks existentes con:
- CHECK 8: Template único `case-study-landing.html.twig` existe con 15+ secciones
- CHECK 9: Todos los parciales `_cs-*.html.twig` existen
- CHECK 10: Library `case-study-landing` incluye dependencies de sticky-cta y scroll-animations
- CHECK 11: `scroll-animations.js` incluye `.reveal-element` en sus selectores
- CHECK 12: SuccessCase entities existen para los 9 verticales (al menos 1 publicada por vertical)

#### 4.10.4 Añadir SUCCESS-CASES-001 a CLAUDE.md

La regla P1 debe estar en las directrices principales, no solo en aprendizajes:

```
- SUCCESS-CASES-001: Todo caso de éxito publicado DEBE provenir de la entidad centralizada SuccessCase.
  No se permiten testimonios hardcodeados en controllers ni templates.
  API: /api/success-cases. Validación: validate-success-cases-ssot.php
```

#### 4.10.5 Pre-commit hook para case studies

Añadir a lint-staged en `package.json` del tema:

```json
"**/templates/partials/_cs-*.html.twig": "php scripts/validation/validate-twig-syntax.php"
```

---

## 5. Tabla de Correspondencia: Especificaciones Técnicas

| ID | Especificación | Fase | Archivos | Directriz |
|----|---------------|------|----------|-----------|
| CS-SPEC-001 | SuccessCase entity con tenant_id + 18 campos nuevos | F0 | `SuccessCase.php`, `.install` | TENANT-001, UPDATE-HOOK-FIELDABLE-001 |
| CS-SPEC-002 | AccessControlHandler con tenant match | F0 | `SuccessCaseAccessControlHandler.php` | TENANT-ISOLATION-ACCESS-001, ACCESS-RETURN-TYPE-001 |
| CS-SPEC-003 | Form extends PremiumEntityFormBase | F0 | `SuccessCaseForm.php` | PREMIUM-FORMS-PATTERN-001 |
| CS-SPEC-004 | Script migración datos hardcodeados → entidades | F0 | `scripts/migration/seed-success-cases.php` | SUCCESS-CASES-001 |
| CS-SPEC-005 | CaseStudyLandingController unificado con DI | F1 | `CaseStudyLandingController.php` | ZERO-REGION-001, ROUTE-LANGPREFIX-001 |
| CS-SPEC-006 | Ruta unificada con constraint regex | F1 | `ecosistema_jaraba_core.routing.yml` | CHECKOUT-ROUTE-COLLISION-001 |
| CS-SPEC-007 | Template maestro case-study-landing.html.twig | F1 | Template + 15 parciales | TWIG-INCLUDE-ONLY-001, TWIG-URL-RENDER-ARRAY-001 |
| CS-SPEC-008 | hook_theme con variables tipadas | F1 | `ecosistema_jaraba_core.module` | HOOK-THEME-COMPLETENESS-001 |
| CS-SPEC-009 | Precios desde MetaSitePricingService | F1 | Controller + preprocess | NO-HARDCODE-PRICE-001 |
| CS-SPEC-010 | URLs via Url::fromRoute() con try-catch | F1 | Controller | ROUTE-LANGPREFIX-001 |
| CS-SPEC-011 | Hero con urgency badge + trust badges | F2 | `_cs-hero.html.twig` | LANDING-CONVERSION-SCORE-001 §1-2 |
| CS-SPEC-012 | Pain points con iconos duotone | F3 | `_cs-pain-points.html.twig` | ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001 |
| CS-SPEC-013 | How-it-works 3 pasos | F3 | `_cs-how-it-works.html.twig` | LANDING-CONVERSION-SCORE-001 §4 |
| CS-SPEC-014 | Features grid 12+ con iconos | F3 | `_cs-features.html.twig` | LANDING-CONVERSION-SCORE-001 §5 |
| CS-SPEC-015 | Comparativa 3 columnas | F3 | `_cs-comparison.html.twig` | LANDING-CONVERSION-SCORE-001 §6 |
| CS-SPEC-016 | Social proof denso (4+ testimonials + métricas + logos) | F4 | `_cs-social-proof.html.twig` | LANDING-CONVERSION-SCORE-001 §7 |
| CS-SPEC-017 | Lead magnet con email capture | F4 | Reutiliza `_lead-magnet.html.twig` | LEAD-MAGNET-CRM-001 |
| CS-SPEC-018 | 4 tiers pricing inline | F4 | `_cs-pricing.html.twig` | PRICING-4TIER-001, ANNUAL-DISCOUNT-001 |
| CS-SPEC-019 | Sticky CTA con selectores actualizados | F5 | `landing-sticky-cta.js`, library deps | LANDING-CONVERSION-SCORE-001 §12 |
| CS-SPEC-020 | Animaciones reveal funcionales | F5 | `scroll-animations.js`, SCSS nuevo | LANDING-CONVERSION-SCORE-001 §13 |
| CS-SPEC-021 | Touch targets 44px + mobile-first corregido | F5 | SCSS case study | LANDING-CONVERSION-SCORE-001 §15 |
| CS-SPEC-022 | FAQ 10+ con Schema.org FAQPage | F6 | `_cs-faq.html.twig` | LANDING-CONVERSION-SCORE-001 §10 |
| CS-SPEC-023 | Schema.org Article completo | F6 | Template maestro | SEO-OG-IMAGE-FALLBACK-001 |
| CS-SPEC-024 | Tracking completo CTAs + secciones | F7 | Template maestro | FUNNEL-COMPLETENESS-001 |
| CS-SPEC-025 | A/B testing integrado | F7 | `metasite-experiments.js` | LANDING-CONVERSION-SCORE-001 |
| CS-SPEC-026 | WCAG 2.1 AA completo | F8 | Todos los parciales | Accesibilidad |
| CS-SPEC-027 | Validator conversion score | F9 | `validate-case-study-conversion-score.php` | SAFEGUARD |
| CS-SPEC-028 | Validator SSOT success cases | F9 | `validate-success-cases-ssot.php` | SUCCESS-CASES-001 |
| CS-SPEC-029 | SCSS compilado con `npm run build` | Todas | SCSS routes + `package.json` | SCSS-COMPILE-VERIFY-001, SCSS-COMPONENT-BUILD-001 |
| CS-SPEC-030 | Body classes correctas (excluir compliance-page) | F1 | `.theme` preprocess_html | CASE-STUDY-PATTERN-001 |
| CS-SPEC-031 | page--case-study.html.twig (Zero Region dedicado) | F1 | Template page--case-study.html.twig | ZERO-REGION-001 |
| CS-SPEC-032 | Textos siempre traducibles con {% trans %} | Todas | Todos los parciales | i18n |
| CS-SPEC-033 | Colores exclusivamente via var(--ej-*) | Todas | SCSS | CSS-VAR-ALL-COLORS-001 |
| CS-SPEC-034 | SCSS con @use (NO @import), Dart Sass moderno | Todas | SCSS | SCSS-001 |
| CS-SPEC-035 | Field UI habilitado para SuccessCase | F0 | `SuccessCase.php` annotation | FIELD-UI-SETTINGS-TAB-001 |
| CS-SPEC-036 | Views integration para SuccessCase | F0 | `SuccessCase.php` annotation `views_data` | Views integration |
| CS-SPEC-037 | Admin nav: /admin/content/success-case + /admin/structure para settings | F0 | Routing | Entity navigation pattern |

---

## 6. Tabla de Cumplimiento de Directrices

| Directriz | Estado actual | Estado tras plan | Fase |
|-----------|---------------|-----------------|------|
| SUCCESS-CASES-001 (P1) | VIOLADA — datos hardcodeados | Cumplida — entidad SSOT | F0-F1 |
| NO-HARDCODE-PRICE-001 (P0) | VIOLADA — precios en Twig/PHP | Cumplida — MetaSitePricingService | F1 |
| ROUTE-LANGPREFIX-001 (P0) | VIOLADA — paths hardcodeados | Cumplida — Url::fromRoute() | F1 |
| TENANT-001 (P0) | VIOLADA — sin tenant_id | Cumplida — entity_reference | F0 |
| TENANT-ISOLATION-ACCESS-001 | VIOLADA — sin tenant check | Cumplida — checkAccess() | F0 |
| PRICING-4TIER-001 | VIOLADA — 3 de 4 tiers | Cumplida — 4 tiers inline | F4 |
| ANNUAL-DISCOUNT-001 | NO VERIFICADA | Cumplida — badge dinámico | F4 |
| PREMIUM-FORMS-PATTERN-001 | VIOLADA — ContentEntityForm | Cumplida — PremiumEntityFormBase | F0 |
| CASE-STUDY-PATTERN-001 | PARCIAL — body classes contaminadas | Cumplida — exclusión correcta | F1 |
| LANDING-CONVERSION-SCORE-001 | 3/15 | 15/15 | F2-F8 |
| FUNNEL-COMPLETENESS-001 | PARCIAL — secciones sin tracking | Cumplida — tracking completo | F7 |
| TWIG-INCLUDE-ONLY-001 | VIOLADA — monolítico | Cumplida — parciales con only | F1 |
| ZERO-REGION-001 | Cumplida ✓ | Cumplida + template dedicado | F1 |
| CSS-VAR-ALL-COLORS-001 | Cumplida ✓ | Cumplida ✓ | — |
| SCSS-COLORMIX-001 | Cumplida ✓ | Cumplida ✓ | — |
| SCSS-001 (@use) | Cumplida ✓ | Cumplida ✓ | — |
| SCSS-COMPILE-VERIFY-001 | Cumplida ✓ | Cumplida ✓ | — |
| ICON-CONVENTION-001 | Cumplida ✓ | Cumplida + más iconos | F3 |
| ICON-DUOTONE-001 | Cumplida ✓ | Cumplida ✓ | — |
| ICON-COLOR-001 | Cumplida ✓ | Cumplida ✓ | — |
| ENTITY-001 | Cumplida ✓ | Cumplida ✓ | — |
| ENTITY-PREPROCESS-001 | Cumplida ✓ | Cumplida ✓ | — |
| UPDATE-HOOK-FIELDABLE-001 | — | Cumplida — getFieldStorageDefinitions | F0 |
| UPDATE-HOOK-CATCH-001 | — | Cumplida — catch(\Throwable) | F0 |
| HOOK-THEME-COMPLETENESS-001 | Cumplida ✓ | Cumplida ✓ | F1 |
| FIELD-UI-SETTINGS-TAB-001 | Pendiente verificar | Cumplida | F0 |
| LEAD-MAGNET-CRM-001 | — | Cumplida — PublicSubscribeController | F4 |
| MARKETING-TRUTH-001 | Cumplida ✓ | Cumplida ✓ | — |
| SEO-OG-IMAGE-FALLBACK-001 | Parcial — sin og:image | Cumplida — Schema.org + OG | F6 |
| OBSERVER-SCROLL-ROOT-001 | N/A (no en slide-panel) | N/A | — |
| ACCESS-RETURN-TYPE-001 | Pendiente verificar | Cumplida — AccessResultInterface | F0 |
| SLIDE-PANEL-RENDER-002 | N/A (page landing, no slide-panel) | N/A | — |

---

## 7. Impacto en Setup Wizard y Daily Actions

Los casos de éxito son páginas de conversión **pre-login** para visitantes anónimos. Por lo tanto, **no requieren wizard steps ni daily actions** para usuarios logueados. Sin embargo, hay dos conexiones indirectas:

### 7.1 Daily Action existente: ExplorarQuizAction

El quiz de recomendación de vertical (`/test-vertical`) enlaza a las landing verticales, que a su vez enlazan a los casos de éxito. La cadena es: QuizResult → Landing vertical → Case study card → Case study landing.

### 7.2 Nuevo: DailyAction para admin (propuesta)

Para administradores con rol de marketing, considerar una daily action `RevisarCasosExitoAction` que:
- Verifica que los 9 verticales tienen al menos 1 SuccessCase publicada
- Alerta si algún caso tiene métricas vacías o FAQ < 10
- Enlaza a `/admin/content/success-case` para gestión

### 7.3 Wizard step de Content Hub

El wizard step `EditorArticuloStep` del Content Hub podría sugerir vincular artículos con casos de éxito relacionados (cross-content), pero esto es una mejora futura, no un requisito del plan actual.

---

## 8. Salvaguardas Propuestas

### 8.1 Nuevos validadores (3)

| Validator | Check ID | Tipo | Checks |
|-----------|----------|------|--------|
| `validate-case-study-conversion-score.php` | CASE-STUDY-CONVERSION-001 | `run_check` | 15 (uno por criterio) |
| `validate-success-cases-ssot.php` | SUCCESS-CASES-SSOT-001 | `run_check` | 6 |
| Extensión de `validate-case-study-completeness.php` | CASE-STUDY-COMPLETENESS-001 | `run_check` (existente) | 12 (de 7 a 12) |

### 8.2 Pre-commit hooks (extensión)

```json
"**/templates/partials/_cs-*.html.twig": [
  "php scripts/validation/validate-twig-syntax.php",
  "php scripts/validation/validate-twig-include-only.php"
]
```

### 8.3 hook_requirements en jaraba_success_cases.module

Extender el `hook_requirements()` existente:

```php
// CHECK 1 (existente): Entity type instalado
// CHECK 2 (nuevo): 9 verticales tienen al menos 1 SuccessCase publicada
// CHECK 3 (nuevo): Todas las SuccessCase publicadas tienen tenant_id
// CHECK 4 (nuevo): Todas las SuccessCase publicadas tienen faq_json con >= 10 items
```

Visible en `/admin/reports/status`.

### 8.4 Cache tags granulares

Cada SuccessCase tiene tag `success_case:{id}`. El controller usa `success_case_list` + tag individual. Invalidación automática al editar desde admin UI.

### 8.5 Regla SUCCESS-CASES-001 en CLAUDE.md

Añadir al bloque de Content Entities:

```
- SUCCESS-CASES-001: Todo caso de éxito publicado DEBE provenir de SuccessCase entity.
  NUNCA hardcodear métricas, testimonios o narrativas en controllers/templates.
  Validación: validate-success-cases-ssot.php
```

### 8.6 Fitness function en CI

Añadir a `fitness-functions.yml`:

```yaml
- name: case-study-conversion-score
  command: php scripts/validation/validate-case-study-conversion-score.php
  threshold: 12  # WARN, 15 = PASS
```

---

## 9. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| URLs actuales (`/jarabalex/caso-de-exito/despacho-martinez`) rompen con ruta unificada | Media | Alto (SEO) | Mantener rutas antiguas como redirects 301 en routing.yml o PathAlias |
| Entidades SuccessCase vacías en producción tras deploy | Alta | Medio | Script `seed-success-cases.php` como parte del deploy. Post-deploy check en hook_requirements |
| Precios desincronizados entre MetaSitePricingService y mini pricing | Baja | Medio | Cache tag compartido con SaasPlan. Validator PRICING-COHERENCE-001 existente |
| 18 campos nuevos en SuccessCase → update hook largo | Baja | Bajo | Batch de 5 campos por hook_update si necesario. catch(\Throwable) |
| Templates legacy (9) coexistiendo con template nuevo → confusión | Media | Medio | Fase de deprecación: mantener legacy 2 sprints, luego eliminar con commit separado |
| `landing-sticky-cta.js` selectores ampliados afectan otras páginas | Baja | Bajo | Selectores `.cs-hero` son únicos de case studies. Sin colisión |

---

## 10. Criterios de Aceptación 10/10

El plan se considera completo cuando:

- [ ] `php scripts/validation/validate-case-study-conversion-score.php` → PASS (15/15)
- [ ] `php scripts/validation/validate-success-cases-ssot.php` → PASS (6/6)
- [ ] `php scripts/validation/validate-case-study-completeness.php` → PASS (12/12)
- [ ] `php scripts/validation/validate-pricing-tiers.php` → PASS (incluye case study pricing)
- [ ] `php scripts/validation/validate-no-hardcoded-prices.php` → PASS (sin EUR en templates case study)
- [ ] 9 SuccessCase entities publicadas (1 por vertical comercial) con todos los campos completos
- [ ] Template `case-study-landing.html.twig` con 15+ secciones via parciales
- [ ] Sticky CTA funcional en scroll (verificado visualmente en `https://jaraba-saas.lndo.site/`)
- [ ] Animaciones reveal funcionales en runtime (no solo en HTML)
- [ ] Schema.org Article + FAQPage + Review validados en Rich Results Test
- [ ] WCAG 2.1 AA: contraste 4.5:1, touch targets 44px, aria completo
- [ ] Mobile responsive completo (verificado en 375px, 768px, 1024px, 1440px)
- [ ] A/B testing configurado para al menos 1 experimento por vertical
- [ ] `npm run build` del tema compila sin errores e incluye CSS de case study
- [ ] Pre-commit hooks configurados para parciales `_cs-*.html.twig`
- [ ] SUCCESS-CASES-001 documentada en CLAUDE.md
- [ ] 0 violaciones de NO-HARDCODE-PRICE-001 y ROUTE-LANGPREFIX-001

---

## 11. Glosario de Siglas

| Sigla | Significado |
|-------|------------|
| CTA | Call To Action — botón o enlace que invita al usuario a realizar una acción de conversión |
| SSOT | Single Source Of Truth — fuente única de verdad para datos, evitando duplicación |
| DI | Dependency Injection — patrón de inyección de dependencias del contenedor de servicios de Drupal |
| SCSS | Sassy CSS — preprocesador CSS que se compila a CSS estándar |
| BEM | Block Element Modifier — convención de nomenclatura CSS (ej: `.cs-hero__title--large`) |
| FAQ | Frequently Asked Questions — preguntas frecuentes |
| JSON-LD | JavaScript Object Notation for Linked Data — formato de datos estructurados para SEO |
| WCAG | Web Content Accessibility Guidelines — directrices de accesibilidad web |
| PLG | Product-Led Growth — estrategia de crecimiento dirigida por el producto |
| RGPD | Reglamento General de Protección de Datos — normativa europea de privacidad |
| SEO | Search Engine Optimization — optimización para motores de búsqueda |
| UX | User Experience — experiencia de usuario |
| OG | Open Graph — protocolo de metadatos para redes sociales |
| CSRF | Cross-Site Request Forgery — ataque de falsificación de solicitud entre sitios |
| XSS | Cross-Site Scripting — ataque de inyección de scripts maliciosos |
| SSE | Server-Sent Events — tecnología de streaming servidor→cliente |
| MCP | Model Context Protocol — protocolo de contexto para modelos de IA |
| BANT | Budget, Authority, Need, Timeline — framework de cualificación de leads |
| MQL | Marketing Qualified Lead — lead cualificado por marketing |
| CRM | Customer Relationship Management — gestión de relaciones con clientes |
| CI | Continuous Integration — integración continua |
| HMAC | Hash-based Message Authentication Code — código de autenticación basado en hash |
| CSP | Content Security Policy — política de seguridad de contenido del navegador |
| CDN | Content Delivery Network — red de distribución de contenido |
| RTL | Right-To-Left — soporte de idiomas que se escriben de derecha a izquierda |
| WOFF2 | Web Open Font Format 2 — formato de fuentes web comprimido |

---

*Documento generado como parte del sistema de documentación de Jaraba Impact Platform.*
*Cumple DOC-GUARD-001: documento nuevo, no sobreescribe master docs.*
*Cumple DOC-GLOSSARY-001: glosario de siglas incluido al final.*
