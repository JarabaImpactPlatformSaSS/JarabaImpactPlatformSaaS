# Plan de Implementacion: Pricing 4 Tiers + Modelo de Negocio Clase Mundial

> **Tipo:** Plan de Implementacion Detallado
> **Version:** 1.0.0
> **Fecha original:** 2026-03-20
> **Ultima actualizacion:** 2026-03-20
> **Estado:** Aprobado para implementacion
> **Alcance:** Correccion tier Free, ajuste precios, configurabilidad admin, features clase mundial, salvaguardas
> **Prerequisito:** Auditoria `docs/analisis/2026-03-20_Auditoria_Definitiva_Pricing_Suscripciones_Modelo_Negocio_SaaS_v1.md`
> **Autor:** Claude Opus 4.6 (1M context)
> **Cross-refs:** Directrices v152.0.0, Arquitectura v139.0.0, Doc 158 (Golden Rule #131)

---

## INDICE DE NAVEGACION (TOC)

1. [Contexto y Objetivo](#1-contexto-y-objetivo)
2. [Prerequisitos y Verificaciones Previas](#2-prerequisitos-y-verificaciones-previas)
3. [FASE 1 — P0 Fixes: Correccion Arquitectural](#3-fase-1-p0-fixes)
   - 3.1 [Crear SaasPlanTier Free](#31-crear-saasplantier-free)
   - 3.2 [Fix getTierConfig()](#32-fix-gettierconfig)
   - 3.3 [Crear Plans Formacion](#33-crear-plans-formacion)
   - 3.4 [Fix catch Throwable](#34-fix-catch-throwable)
   - 3.5 [Actualizar texto hub](#35-actualizar-texto-hub)
4. [FASE 2 — P1: Precios y Datos Dinamicos](#4-fase-2-p1-precios-y-datos-dinamicos)
   - 4.1 [Actualizar precios Emprendimiento](#41-actualizar-precios-emprendimiento)
   - 4.2 [Actualizar precios JarabaLex](#42-actualizar-precios-jarabalex)
   - 4.3 [Descuento anual 17% a 20%](#43-descuento-anual)
   - 4.4 [Normalizar weights](#44-normalizar-weights)
   - 4.5 [Schema.org dinamico hub](#45-schemaorg-dinamico-hub)
   - 4.6 [Badge descuento dinamico](#46-badge-descuento-dinamico)
5. [FASE 3 — Configurabilidad Admin](#5-fase-3-configurabilidad-admin)
   - 5.1 [FAQs configurables](#51-faqs-configurables)
   - 5.2 [Taglines en entidad Vertical](#52-taglines-en-entidad-vertical)
   - 5.3 [Feature labels externalizados](#53-feature-labels-externalizados)
   - 5.4 [Descuento anual configurable](#54-descuento-anual-configurable)
6. [FASE 4 — Features Clase Mundial](#6-fase-4-features-clase-mundial)
   - 6.1 [Social proof section](#61-social-proof-section)
   - 6.2 [Comparison matrix](#62-comparison-matrix)
   - 6.3 [Sticky CTA mobile](#63-sticky-cta-mobile)
   - 6.4 [Pause subscription](#64-pause-subscription)
   - 6.5 [Win-back emails](#65-win-back-emails)
   - 6.6 [Cancellation flow mejorado](#66-cancellation-flow-mejorado)
7. [FASE 5 — Salvaguardas y Validacion](#7-fase-5-salvaguardas-y-validacion)
8. [Directrices de Aplicacion (Checklist)](#8-directrices-de-aplicacion)
9. [Tabla de Correspondencia Tecnica](#9-tabla-de-correspondencia-tecnica)
10. [Verificacion RUNTIME-VERIFY-001](#10-verificacion-runtime)

---

## 1. CONTEXTO Y OBJETIVO

### Problema identificado

La auditoria definitiva del 2026-03-20 revelo que el modelo de pricing tiene un **bug arquitectonico critico**: la BD contiene 4 planes por vertical (Free, Starter, Pro, Enterprise) pero solo 3 SaasPlanTier ConfigEntities. El plan Free a 0 EUR es completamente invisible en todas las paginas de precios.

Adicionalmente, ~10 elementos de marketing/UX estan hardcoded y no son configurables desde la interfaz de administracion, violando NO-HARDCODE-PRICE-001 y la filosofia "Sin Humo" del proyecto.

### Objetivo

Llevar el sistema de pricing de 6.7/10 a 9.3/10 mediante:
1. Fix del bug de 4 tiers (Free visible en /planes)
2. Ajuste de precios (Emprendimiento ↓, JarabaLex ↑, Formacion NEW)
3. Configurabilidad admin total (FAQs, labels, taglines desde UI)
4. Features de clase mundial (social proof, pause, win-back)
5. Salvaguardas automatizadas (5 scripts nuevos)

---

## 2. PREREQUISITOS Y VERIFICACIONES PREVIAS

Antes de comenzar cualquier fase, ejecutar:

```bash
# Dentro del contenedor Lando
lando ssh

# 1. Verificar estado limpio
git status

# 2. Verificar que tests pasan
cd /app && php vendor/bin/phpunit --testsuite Unit --no-coverage 2>&1 | tail -5

# 3. Verificar estado actual de SaasPlanTier
drush ev "print_r(array_keys(\Drupal::entityTypeManager()->getStorage('saas_plan_tier')->loadMultiple()));"

# 4. Verificar SaasPlan count por vertical
drush sql:query "SELECT COUNT(*) as cnt, SUBSTRING_INDEX(name, ' ', 1) as vertical FROM saas_plan_field_data GROUP BY vertical ORDER BY vertical"

# 5. Verificar que validate-all.sh pasa
cd /app && bash scripts/validation/validate-all.sh --fast 2>&1 | tail -10
```

### Directrices aplicables (verificar antes de cada cambio)

| Directriz | Donde aplica | Recordatorio |
|-----------|-------------|-------------|
| NO-HARDCODE-PRICE-001 | TODO precio y texto marketing | Desde MetaSitePricingService o Theme Settings |
| CSS-VAR-ALL-COLORS-001 | SCSS nuevo | Prefijo `--ej-*`, NUNCA hex directo |
| ICON-CONVENTION-001 | Iconos en templates | `jaraba_icon('cat', 'name', {variant:'duotone'})` |
| i18n | Textos en templates | `{% trans %}texto{% endtrans %}` (bloque, NO filtro) |
| SCSS-COMPILE-VERIFY-001 | Tras edicion SCSS | `npm run build` + verificar timestamp CSS > SCSS |
| TWIG-INCLUDE-ONLY-001 | Includes de parciales | `{% include '...' with {...} only %}` |
| ZERO-REGION-001 | Templates de pagina | `{{ clean_content }}`, NO `{{ page.content }}` |
| UPDATE-HOOK-REQUIRED-001 | Nueva ConfigEntity | hook_update_N + installEntityType() |
| PREMIUM-FORMS-PATTERN-001 | Forms de entidad | Extender PremiumEntityFormBase |
| SLIDE-PANEL-RENDER-001 | Acciones crear/editar | renderPlain() en slide-panel |
| ROUTE-LANGPREFIX-001 | URLs en JS y templates | Url::fromRoute(), NUNCA paths hardcoded |
| DOC-GUARD-001 | Master docs | Edit incremental, commit separado `docs:` |

---

## 3. FASE 1 — P0 FIXES: CORRECCION ARQUITECTURAL

**Estimacion:** 3-4 horas | **Prioridad:** BLOQUEA DESPLIEGUE

### 3.1 Crear SaasPlanTier Free

**Descripcion detallada:**

El sistema actualmente tiene 3 SaasPlanTier ConfigEntities (starter weight=0, professional weight=10, enterprise weight=20). Necesitamos un 4to tier "free" con weight=-10 para que aparezca primero en la iteracion de `getPricingPreview()`.

**Archivo a crear:** `config/sync/ecosistema_jaraba_core.plan_tier.free.yml`

```yaml
uuid: [generar UUID]
langcode: es
status: true
dependencies: {  }
id: free
label: Free
tier_key: free
aliases:
  - free
  - gratis
  - gratuito
stripe_price_monthly: ''
stripe_price_yearly: ''
description: 'Plan gratuito para explorar la plataforma sin compromiso.'
weight: -10
```

**Cambios adicionales necesarios:**

1. **PlanResolverService::normalize()** — Verificar que el alias 'free' ya NO esta bajo starter. Actualizar el SaasPlanTier starter para remover 'free' de sus aliases:

```yaml
# config/sync/ecosistema_jaraba_core.plan_tier.starter.yml
aliases:
  - starter
  - basico
  - basic
  # REMOVER: - free  ← Ya no es alias de starter
```

2. **MetaSitePricingService::loadVerticalPrices()** — Actualmente asigna keys `['free', 'starter', 'professional', 'enterprise']` secuencialmente. Con el 4to tier, `getPricingPreview()` iterara 4 tiers y consumira correctamente `$priceMap['free']`.

3. **MetaSitePricingService::getFallbackTiers()** — Anadir tier Free al fallback (L182-239):

```php
[
    'tier_key' => 'free',
    'label' => (string) $this->t('Free'),
    'description' => (string) $this->t('Para explorar la plataforma sin compromiso'),
    'features' => [...],
    'limits' => ['max_pages' => 1, 'storage_gb' => 0.5],
    'is_recommended' => FALSE,
    'price_monthly' => 0.0,
    'price_yearly' => 0.0,
],
```

4. **hook_update_N** — Obligatorio por UPDATE-HOOK-REQUIRED-001:

```php
function ecosistema_jaraba_core_update_NNNN(): string {
    try {
        $edm = \Drupal::entityDefinitionUpdateManager();
        $entity_type = \Drupal::entityTypeManager()
            ->getDefinition('saas_plan_tier');
        $edm->installEntityType($entity_type);
    }
    catch (\Throwable $e) {
        return 'SaasPlanTier ya instalado: ' . $e->getMessage();
    }
    return 'SaasPlanTier entity type re-installed with Free tier support.';
}
```

**Verificacion post-implementacion:**

```bash
drush updatedb -y
drush ev "print_r(array_keys(\Drupal::entityTypeManager()->getStorage('saas_plan_tier')->loadMultiple()));"
# Debe mostrar: ['free', 'starter', 'professional', 'enterprise']

drush ev "print_r(\Drupal::service('ecosistema_jaraba_core.metasite_pricing')->getPricingPreview('empleabilidad'));"
# Debe mostrar 4 tiers con Free a 0€
```

### 3.2 Fix getTierConfig()

**Descripcion detallada:**

`TenantSubscriptionService.php:354` llama `$this->planResolver->getTierConfig($vertical, $tier)` pero el metodo no existe en PlanResolverService. Esto causa TypeError cuando un tenant intenta cambiar de plan (upgrade/downgrade).

**Opcion A (RECOMENDADA):** Crear el metodo en PlanResolverService:

```php
/**
 * Gets the SaasPlanTier ConfigEntity for a given tier key.
 *
 * @param string $vertical
 *   Machine name of the vertical (used for logging context).
 * @param string $tierKey
 *   Canonical tier key (e.g. 'starter', 'professional').
 *
 * @return \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface|null
 *   The tier config entity, or NULL if not found.
 */
public function getTierConfig(string $vertical, string $tierKey): ?SaasPlanTierInterface
{
    $normalized = $this->normalize($tierKey);
    try {
        $tier = $this->entityTypeManager->getStorage('saas_plan_tier')->load($normalized);
        return $tier instanceof SaasPlanTierInterface ? $tier : NULL;
    }
    catch (\Throwable $e) {
        $this->logger->warning('Cannot load SaasPlanTier @tier for @vertical: @error', [
            '@tier' => $tierKey,
            '@vertical' => $vertical,
            '@error' => $e->getMessage(),
        ]);
        return NULL;
    }
}
```

**Opcion B:** Cambiar la llamada en TenantSubscriptionService para usar `getFeatures()` en su lugar. Depende de que datos necesita el caller.

**Directriz:** SERVICE-CALL-CONTRACT-001 — Las firmas de metodo DEBEN coincidir exactamente.

### 3.3 Crear Plans Formacion

**Descripcion detallada:**

La vertical Formacion solo tiene `formacion_free.yml` en BD. Faltan Starter, Professional y Enterprise. Los plan_features YA existen (`formacion_starter`, `formacion_professional`, `formacion_enterprise`), por lo que solo faltan los SaasPlan ContentEntities con precios.

**Archivos a crear:**

`config/sync/ecosistema_jaraba_core.saas_plan.formacion_starter.yml`:
```yaml
langcode: es
status: true
name: 'Formacion Starter'
price_monthly: '39.00'
price_yearly: '374.00'
features:
  - course_builder
  - student_portal
  - basic_certificates
  - copilot_ia
  - soporte_email
limits: '{"courses":5,"students_per_course":25,"copilot_uses_per_month":30,"storage_gb":5,"certificates":50}'
stripe_price_id: ''
weight: 10
```

`config/sync/ecosistema_jaraba_core.saas_plan.formacion_pro.yml`:
```yaml
langcode: es
status: true
name: 'Formacion Profesional'
price_monthly: '79.00'
price_yearly: '758.00'
features:
  - course_builder
  - student_portal
  - basic_certificates
  - learning_paths
  - gamification
  - copilot_ia
  - analytics
  - soporte_email
  - soporte_chat
limits: '{"courses":25,"students_per_course":100,"copilot_uses_per_month":200,"storage_gb":25,"certificates":-1}'
stripe_price_id: ''
weight: 20
```

`config/sync/ecosistema_jaraba_core.saas_plan.formacion_enterprise.yml`:
```yaml
langcode: es
status: true
name: 'Formacion Enterprise'
price_monthly: '149.00'
price_yearly: '1430.00'
features:
  - course_builder
  - student_portal
  - basic_certificates
  - learning_paths
  - gamification
  - xapi_tracking
  - copilot_ia
  - analytics
  - api_access
  - personalizacion_marca
  - premium_blocks
  - soporte_email
  - soporte_chat
  - soporte_dedicado
limits: '{"courses":-1,"students_per_course":-1,"copilot_uses_per_month":10000,"storage_gb":100,"certificates":-1}'
stripe_price_id: ''
weight: 30
```

**Nota:** Se necesita tambien un `drush config:import` o `drush cr` para que las ConfigEntities de SaasPlan se carguen. Si SaasPlan es ContentEntity (no ConfigEntity), habra que crear via hook_update_N con entity storage.

### 3.4 Fix catch Throwable

**Descripcion detallada:**

ReverseTrialService.php tiene 6 instancias de `catch (\Exception $e)`. En PHP 8.4, TypeError extiende `\Error`, no `\Exception`. Usar `catch (\Throwable $e)` captura ambos.

**Directriz:** UPDATE-HOOK-CATCH-001

**Archivo:** `web/modules/custom/jaraba_billing/src/Service/ReverseTrialService.php`

**Cambios (6 lineas):**
- L139: `catch (\Exception $e)` → `catch (\Throwable $e)`
- L180: idem
- L245: idem
- L328: idem
- L448: idem
- L501: idem

**Verificacion:** `grep -n 'catch.*\\\\Exception' web/modules/custom/jaraba_billing/src/Service/ReverseTrialService.php` debe retornar 0 resultados.

### 3.5 Actualizar texto hub

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/pricing-hub-page.html.twig`

**Cambio L83:** `{% trans %}3 niveles para cada vertical{% endtrans %}` → `{% trans %}4 niveles para cada vertical{% endtrans %}`

**Cambio L86:** `{% trans %}Todos los verticales ofrecen 3 planes adaptados a tu nivel de necesidad.{% endtrans %}` → `{% trans %}Todos los verticales ofrecen 4 planes: desde gratuito hasta enterprise, adaptados a tu nivel de necesidad.{% endtrans %}`

---

## 4. FASE 2 — P1: PRECIOS Y DATOS DINAMICOS

**Estimacion:** 4-6 horas | **Prioridad:** COMPLETAR ANTES DE MERGE

### 4.1 Actualizar precios Emprendimiento

**Archivos a modificar:**

| Archivo | Campo | Valor actual | Valor nuevo |
|---------|-------|-------------|-------------|
| `saas_plan.emprendimiento_starter.yml` | price_monthly | 39.00 | 19.00 |
| `saas_plan.emprendimiento_starter.yml` | price_yearly | 390.00 | 182.00 |
| `saas_plan.emprendimiento_pro.yml` | price_monthly | 99.00 | 49.00 |
| `saas_plan.emprendimiento_pro.yml` | price_yearly | 990.00 | 470.00 |
| `saas_plan.emprendimiento_enterprise.yml` | price_monthly | 199.00 | 99.00 |
| `saas_plan.emprendimiento_enterprise.yml` | price_yearly | 1990.00 | 950.00 |

**Justificacion:** Mercado andaluz sensible al precio, WTP 10-25 EUR/mes, competencia gratuita (ChatGPT), valor estrategico como puerta del ecosistema.

### 4.2 Actualizar precios JarabaLex

| Archivo | Campo | Valor actual | Valor nuevo |
|---------|-------|-------------|-------------|
| `saas_plan.jarabalex_starter.yml` | price_monthly | 49.00 | 59.00 |
| `saas_plan.jarabalex_starter.yml` | price_yearly | 490.00 | 566.00 |
| `saas_plan.jarabalex_pro.yml` | price_monthly | 99.00 | 149.00 |
| `saas_plan.jarabalex_pro.yml` | price_yearly | 990.00 | 1430.00 |
| `saas_plan.jarabalex_enterprise.yml` | price_monthly | 199.00 | 299.00 |
| `saas_plan.jarabalex_enterprise.yml` | price_yearly | 1990.00 | 2870.00 |

**Justificacion:** vLex 75-300 EUR/mes, Aranzadi 100-250 EUR/mes. IA legal integrada es un diferencial premium que justifica precio superior.

### 4.3 Descuento anual 17% → 20%

Recalcular TODOS los `price_yearly` con formula: `price_monthly × 12 × 0.80` (redondeado a EUR entero).

Ejemplo: Empleabilidad Starter 29€ × 12 × 0.80 = 278.40 → 278€

Aplicar a los 32 archivos `saas_plan.*.yml` que tienen precio >0.

### 4.4 Normalizar weights

**AgroConecta:** Cambiar weights de 10/11/12 a 10/20/30 en:
- `saas_plan.agroconecta_starter.yml`: weight 10 (OK)
- `saas_plan.agroconecta_pro.yml`: weight 11 → 20
- `saas_plan.agroconecta_enterprise.yml`: weight 12 → 30

**Patron estandar:** Free=0, Starter=10, Professional=20, Enterprise=30

### 4.5 Schema.org dinamico hub

**Archivo:** `pricing-hub-page.html.twig` L205-242

**Reemplazar** el JSON-LD estatico por uno dinamico:

```twig
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Jaraba Impact Platform",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web",
  "url": "{{ url('ecosistema_jaraba_core.pricing.page', {}, {absolute: true}) }}",
  "offers": [
    {% for tier in overview_tiers %}
    {
      {% if tier.price_monthly > 0 %}
      "@type": "AggregateOffer",
      "name": {{ tier.label|json_encode(constant('JSON_UNESCAPED_UNICODE')) }},
      "priceCurrency": "EUR",
      "lowPrice": "{{ tier.price_monthly }}",
      "highPrice": "{{ tier.price_monthly * 3 }}",
      {% else %}
      "@type": "Offer",
      "name": {{ tier.label|json_encode(constant('JSON_UNESCAPED_UNICODE')) }},
      "priceCurrency": "EUR",
      "price": "0",
      {% endif %}
      "availability": "https://schema.org/InStock",
      "description": {{ tier.description|default('')|json_encode(constant('JSON_UNESCAPED_UNICODE')) }}
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}
</script>
```

### 4.6 Badge descuento dinamico

**Archivo:** `pricing-page.html.twig` L75

**Reemplazar:**
```twig
<span class="pricing-toggle__badge">-17%</span>
```

**Por:**
```twig
{% set max_savings = 0 %}
{% for tier in tiers %}
  {% if tier.price_monthly > 0 and tier.price_yearly > 0 %}
    {% set tier_savings = (((tier.price_monthly * 12 - tier.price_yearly) / (tier.price_monthly * 12)) * 100)|round(0) %}
    {% if tier_savings > max_savings %}
      {% set max_savings = tier_savings %}
    {% endif %}
  {% endif %}
{% endfor %}
{% if max_savings > 0 %}
  <span class="pricing-toggle__badge">-{{ max_savings }}%</span>
{% endif %}
```

**Directriz:** MARKETING-TRUTH-001 — Claims marketing DEBEN coincidir con billing real.

---

## 5. FASE 3 — CONFIGURABILIDAD ADMIN

**Estimacion:** 8-12 horas | **Prioridad:** Sprint actual

### 5.1 FAQs configurables

**Enfoque:** Usar Theme Settings (ecosistema_jaraba_theme.settings) — patron ya existente para `plg_guarantee_text`.

Anadir nuevos campos en el formulario de tema (EcosistemaJarabaThemeSettings o similar) bajo un vertical tab "Pricing":
- `pricing_faq_items`: JSON textarea con FAQ items editables desde UI
- Formato: `[{"question":"...", "answer":"..."}, ...]`

**Cambio en PricingController:** Leer de theme_get_setting('pricing_faq_items') con fallback al array actual.

### 5.2 Taglines en entidad Vertical

**Enfoque:** Anadir campo `tagline` a la entidad Vertical (si no existe).

```php
$fields['tagline'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Tagline'))
    ->setDescription(t('Breve descripcion para la pagina de precios'))
    ->setSettings(['max_length' => 255])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
```

**Directriz:** UPDATE-HOOK-REQUIRED-001 — hook_update_N obligatorio para nuevo campo.

**Cambio en PricingController:** Reemplazar `getVerticalTaglines()` hardcoded por carga desde entidad Vertical.

### 5.3 Feature labels externalizados

**Enfoque:** Mover los 80+ labels de `MetaSitePricingService::formatFeatureLabels()` a un archivo YAML configurable:

`config/sync/ecosistema_jaraba_core.feature_labels.yml` o similar.

Alternativa: Usar `SaasPlanFeatures` ConfigEntity — anadir campo `feature_labels` como mapa key→label.

### 5.4 Descuento anual configurable

**Enfoque:** Anadir campo `annual_discount_percent` a SaasPlanTier ConfigEntity.

```yaml
# plan_tier.starter.yml
annual_discount_percent: 20
```

**Cambio en pricing-page.html.twig:** Leer `tiers[0].annual_discount_percent` para el badge.

---

## 6. FASE 4 — FEATURES CLASE MUNDIAL

**Estimacion:** 2-3 dias | **Prioridad:** Backlog (post-merge de Fases 1-3)

### 6.1 Social proof section

**Parcial nuevo:** `_pricing-social-proof.html.twig`

Contenido configurable desde Theme Settings:
- `pricing_social_logos`: JSON con logos de clientes (URL imagen + alt text)
- `pricing_testimonials`: JSON con testimonios (nombre, cargo, empresa, foto, cita)

**SCSS:** `scss/components/_pricing-social-proof.scss` → `css/components/pricing-social-proof.css`

**Library:** Registrar en `ecosistema_jaraba_theme.libraries.yml` como `pricing-social-proof`.

**Directrices:**
- SCSS-001: `@use '../variables' as *;` en cada parcial SCSS
- CSS-VAR-ALL-COLORS-001: Colores via `var(--ej-*, fallback)`
- TWIG-INCLUDE-ONLY-001: `{% include '..._pricing-social-proof.html.twig' with {...} only %}`

### 6.2 Comparison matrix expandible

**Parcial:** `_pricing-comparison-matrix.html.twig`

Tabla responsive que se muestra como accordion en mobile.

**Datos:** Desde `MetaSitePricingService::getPricingPreview()` — ya tiene features por tier.

**UX:** `<details>` nativo con animacion CSS (no JS pesado).

### 6.3 Sticky CTA bar en mobile

**SCSS nuevo en pricing page:**

```scss
.pricing-sticky-cta {
    display: none; // Hidden on desktop

    @media (max-width: 768px) {
        display: flex;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: var(--ej-z-sticky, 100);
        background: var(--ej-color-surface, #fff);
        padding: 0.75rem 1rem;
        box-shadow: 0 -2px 8px color-mix(in srgb, var(--ej-color-text) 10%, transparent);
        transform: translateY(100%);
        transition: transform 0.3s ease;

        &.is-visible {
            transform: translateY(0);
        }
    }
}
```

**JS:** IntersectionObserver en el primer pricing-card. Cuando sale del viewport, mostrar sticky bar. Patron OBSERVER-SCROLL-ROOT-001 adaptado a viewport (no slide-panel).

### 6.4 Pause subscription

**Nuevo servicio:** `jaraba_billing/src/Service/PauseSubscriptionService.php`

**Metodos:**
- `pauseSubscription(TenantInterface $tenant, int $months = 1): bool` — Pausa 1-3 meses
- `resumeSubscription(TenantInterface $tenant): bool`
- `isPaused(TenantInterface $tenant): bool`
- `getResumeDate(TenantInterface $tenant): ?\DateTimeInterface`

**Estado tenant:** Nuevo valor `'paused'` en `subscription_status` list_string.

**Stripe:** `Subscription::update(['pause_collection' => ['behavior' => 'void']])` — Stripe soporta pause nativo.

**UI:** Paso en cancellation flow antes de confirmar cancelacion.

**Directrices:**
- TENANT-001: filtrar por tenant
- SERVICE-CALL-CONTRACT-001: firmas exactas
- DI en services.yml con @? para dependencias opcionales

### 6.5 Win-back emails

**Nuevo servicio:** `jaraba_billing/src/Service/WinBackCampaignService.php`

4 touches post-cancelacion:
1. Dia 7: "Te echamos de menos — 1 mes gratis"
2. Dia 30: Feature announcement (que se estan perdiendo)
3. Dia 60: "50% descuento 3 meses"
4. Dia 90: Survey final "Has encontrado lo que buscabas?"

**Cron:** En `jaraba_billing_cron()` — batch processing como QuizFollowUpCron.

**Dedup:** Via campo `_winback_sent` en tenant data o State API.

### 6.6 Cancellation flow mejorado

Agregar 4 pasos al flujo de cancelacion:
1. Survey de razon (multiple choice)
2. Save offer basada en razon (descuento o pause)
3. Opcion pause como alternativa
4. Confirmacion con data export

---

## 7. FASE 5 — SALVAGUARDAS Y VALIDACION

### Scripts nuevos a crear

| Script | Checks | Modo |
|--------|--------|------|
| `validate-pricing-tiers.php` | Cada SaasPlanTier tiene SaasPlan por vertical | fast |
| `validate-pricing-display.php` | MetaSitePricingService retorna datos para todos tiers × verticales | full |
| `validate-stripe-sync.php` | SaasPlan con precio >0 tienen stripe_price_id | full |
| `validate-schema-org-pricing.php` | Schema.org en templates usa datos dinamicos | fast |
| `validate-annual-discount.php` | Yearly = Monthly × 12 × (1 - descuento) ± 1 EUR | fast |

### Integracion en validate-all.sh

Anadir las 5 checks como `run_check` en `scripts/validation/validate-all.sh`.

### Reglas para CLAUDE.md

```
### PRICING-TIER-PARITY-001 (established 2026-03-20)
TODA SaasPlanTier DEBE tener al menos 1 SaasPlan ContentEntity con precio por cada
vertical comercial. Sin plan → pricing page muestra tier a 0€ (falso).
Validacion: `php scripts/validation/validate-pricing-tiers.php`

### PRICING-4TIER-001 (established 2026-03-20)
El modelo de pricing tiene 4 tiers: free (weight=-10), starter (weight=0),
professional (weight=10), enterprise (weight=20). Los 4 SaasPlanTier ConfigEntities
DEBEN existir. El alias 'free' NO es parte de starter — es un tier independiente.

### ANNUAL-DISCOUNT-001 (established 2026-03-20)
price_yearly DEBE ser price_monthly × 12 × (1 - annual_discount). El descuento
estandar es 20% ("2 meses gratis"). Tolerancia: ± 1 EUR por redondeo.
Validacion: `php scripts/validation/validate-annual-discount.php`
```

---

## 8. DIRECTRICES DE APLICACION (CHECKLIST)

### Para CADA cambio de codigo:

- [ ] ¿Textos traducibles? (`{% trans %}`, `$this->t()`)
- [ ] ¿SCSS con `@use '../variables' as *`?
- [ ] ¿Colores con `var(--ej-*, fallback)`?
- [ ] ¿Iconos via `jaraba_icon()`?
- [ ] ¿URLs via `path()` / `url()` / `Url::fromRoute()`?
- [ ] ¿Parciales con `only` keyword?
- [ ] ¿catch(\Throwable) en vez de catch(\Exception)?
- [ ] ¿hook_update_N si hay cambio de entidad o campo?
- [ ] ¿services.yml con @? para cross-module?
- [ ] ¿Compilar SCSS y verificar timestamp?

### Para templates de pricing:

- [ ] ¿Precios desde tiers data (NO hardcoded)?
- [ ] ¿Schema.org dinamico desde datos?
- [ ] ¿Badge descuento calculado (NO fijo)?
- [ ] ¿Textos marketing desde Theme Settings o BD?
- [ ] ¿data-track-cta + data-track-position en CTAs?

---

## 9. TABLA DE CORRESPONDENCIA TECNICA

| Especificacion | Fase | Archivos | Directrices |
|---------------|------|----------|-------------|
| Crear SaasPlanTier Free | 1.1 | plan_tier.free.yml, plan_tier.starter.yml, MetaSitePricingService.php | UPDATE-HOOK-REQUIRED-001, PRICING-4TIER-001 |
| Fix getTierConfig() | 1.2 | PlanResolverService.php, TenantSubscriptionService.php | SERVICE-CALL-CONTRACT-001 |
| Plans Formacion | 1.3 | saas_plan.formacion_*.yml (3 archivos) | PRICING-TIER-PARITY-001 |
| Fix catch Throwable | 1.4 | ReverseTrialService.php (6 lineas) | UPDATE-HOOK-CATCH-001 |
| Texto hub 4 niveles | 1.5 | pricing-hub-page.html.twig | i18n, MARKETING-TRUTH-001 |
| Precios Emprendimiento | 2.1 | saas_plan.emprendimiento_*.yml (3 archivos) | NO-HARDCODE-PRICE-001 |
| Precios JarabaLex | 2.2 | saas_plan.jarabalex_*.yml (3 archivos) | NO-HARDCODE-PRICE-001 |
| Descuento 20% | 2.3 | Todos saas_plan.*.yml con precio >0 | ANNUAL-DISCOUNT-001 |
| Weights normalizados | 2.4 | saas_plan.agroconecta_*.yml | Data quality |
| Schema.org dinamico | 2.5 | pricing-hub-page.html.twig | MARKETING-TRUTH-001, NO-HARDCODE-PRICE-001 |
| Badge dinamico | 2.6 | pricing-page.html.twig | MARKETING-TRUTH-001 |
| FAQs configurables | 3.1 | EcosistemaJarabaThemeSettings, PricingController | NO-HARDCODE-PRICE-001 |
| Taglines en Vertical | 3.2 | Vertical entity, PricingController | UPDATE-HOOK-REQUIRED-001 |
| Feature labels YAML | 3.3 | MetaSitePricingService, config YAML | NO-HARDCODE-PRICE-001 |
| Descuento configurable | 3.4 | SaasPlanTier entity, pricing-page.twig | NO-HARDCODE-PRICE-001 |
| Social proof | 4.1 | _pricing-social-proof.html.twig, SCSS | TWIG-INCLUDE-ONLY-001, CSS-VAR-ALL-COLORS-001 |
| Comparison matrix | 4.2 | _pricing-comparison-matrix.html.twig, SCSS | Responsive, a11y |
| Sticky CTA mobile | 4.3 | SCSS, JS (IntersectionObserver) | OBSERVER-SCROLL-ROOT-001 |
| Pause subscription | 4.4 | PauseSubscriptionService.php, services.yml | TENANT-001, DI |
| Win-back emails | 4.5 | WinBackCampaignService.php, hook_cron | QUIZ-FOLLOWUP-DRIP-001 pattern |
| Cancellation flow | 4.6 | Cancel template, JS, service | SLIDE-PANEL-RENDER-001 |
| 5 scripts validacion | 5 | scripts/validation/ (5 archivos) | Safeguard system Layer 1 |

---

## 10. VERIFICACION RUNTIME-VERIFY-001

Tras completar CADA fase, verificar las 5 capas + 7 extras:

### Capas estandar (RUNTIME-VERIFY-001)

1. **CSS compilado:** `npm run build` + verificar timestamp CSS > SCSS
2. **Tablas DB:** `drush updatedb --no-interaction` sin errores
3. **Rutas accesibles:** Visitar /planes, /planes/empleabilidad, /planes/checkout/1
4. **data-* selectores:** Verificar data-billing, data-tier, data-checkout-plan en DOM
5. **drupalSettings:** Verificar stripeCheckout en page source

### Extras para pricing

6. **4 tiers visibles:** /planes/empleabilidad muestra Free(0€), Starter, Pro, Enterprise
7. **Schema.org correcto:** View source > buscar "application/ld+json" > verificar precios
8. **Badge dinamico:** Toggle anual muestra descuento real (no -17% fijo)
9. **Formacion pricing:** /planes/formacion muestra 4 tiers con precios correctos
10. **Upgrade funciona:** Desde /tenant/change-plan, clic en "Mejorar" no da TypeError
11. **Slide-panel plan:** /my-settings/plan/slide-panel renderiza sin BigPipe chrome
12. **Mobile CTA:** En viewport <768px, sticky CTA aparece al scroll (post-Fase 4)

---

**FIN DEL PLAN DE IMPLEMENTACION**

*Generado por Claude Opus 4.6 (1M context) el 2026-03-20.*
*Basado en auditoria verificada contra codigo real del codebase.*
*Cada especificacion trazada a archivo, linea y directriz del proyecto.*
