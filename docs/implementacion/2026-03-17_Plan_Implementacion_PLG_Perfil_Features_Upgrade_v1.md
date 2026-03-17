# Plan de Implementación: PLG Upgrade UI — Perfil ↔ Features ↔ Planes

**Fecha de creación:** 2026-03-17
**Última actualización:** 2026-03-17
**Autor:** Claude Opus 4.6 (Anthropic) — Arquitecto SaaS Senior / Ingeniero UX Senior
**Versión:** 1.0.0
**Categoría:** Plan de Implementación Estratégico
**Código:** PLG-UPGRADE-UI-001
**Estado:** PLANIFICADO
**Esfuerzo estimado:** 40-60h (3 fases, 15 acciones)
**Diagnóstico fuente:** `docs/analisis/2026-03-17_Diagnostico_Coherencia_Perfil_Vertical_Planes_Features_v1.md` (DIAG-PLG-001)
**Directrices aplicables:** `00_DIRECTRICES_PROYECTO.md` v137.0.0, `CLAUDE.md` v1.5.3
**Módulos afectados:** `ecosistema_jaraba_core`, `jaraba_billing`, `jaraba_business_tools` (y los 9 verticales restantes), `ecosistema_jaraba_theme`
**Rutas principales:** `/user/{uid}`, `/entrepreneur/dashboard` (y 9 dashboards más), `/planes/{vertical}`, `/planes/checkout/{plan}`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Qué se implementa](#11-qué-se-implementa)
   - 1.2 [Por qué se implementa](#12-por-qué-se-implementa)
   - 1.3 [Principios rectores](#13-principios-rectores)
   - 1.4 [Métricas de impacto](#14-métricas-de-impacto)
   - 1.5 [Alcance y exclusiones](#15-alcance-y-exclusiones)
   - 1.6 [Relación con planes previos](#16-relación-con-planes-previos)
2. [Arquitectura de la Solución](#2-arquitectura-de-la-solución)
   - 2.1 [Principio: el usuario SIEMPRE sabe qué tiene y qué le falta](#21-principio)
   - 2.2 [Patrón de datos: SubscriptionContext](#22-patrón-de-datos)
   - 2.3 [Servicios nuevos y existentes](#23-servicios)
   - 2.4 [Parciales Twig reutilizables (4 nuevos)](#24-parciales-twig)
   - 2.5 [SCSS con tokens inyectables](#25-scss)
   - 2.6 [Integración con UserProfileSectionRegistry](#26-integración-perfil)
   - 2.7 [Integración con dashboards verticales](#27-integración-dashboards)
3. [Fase 1 — Perfil: Tarjeta de Suscripción + Features (Semana 1)](#3-fase-1)
   - 3.1 [SubscriptionProfileSection: nuevo servicio tagged](#31-subscription-section)
   - 3.2 [SubscriptionContextService: resolver plan + features + uso del usuario](#32-context-service)
   - 3.3 [Parcial _subscription-card.html.twig](#33-parcial-subscription-card)
   - 3.4 [Parcial _feature-list.html.twig (checks verdes + locks naranjas)](#34-parcial-feature-list)
   - 3.5 [Parcial _usage-bar.html.twig (barras de progreso)](#35-parcial-usage-bar)
   - 3.6 [SCSS _subscription-card.scss con var(--ej-*)](#36-scss)
   - 3.7 [Preprocess: inyección de datos en page--user.html.twig](#37-preprocess)
   - 3.8 [Feature labels humanos: FeatureLabelService](#38-feature-labels)
4. [Fase 2 — Dashboards Verticales: Badge + Barras + Lock Overlay (Semana 2)](#4-fase-2)
   - 4.1 [Parcial _plan-badge.html.twig (badge de plan en header)](#41-plan-badge)
   - 4.2 [Parcial _feature-gating-inline.html.twig (lock overlay reutilizable)](#42-feature-gating)
   - 4.3 [Integración en entrepreneur-dashboard.html.twig (caso piloto)](#43-emprendimiento)
   - 4.4 [Extensión a los 9 verticales restantes](#44-extensión-verticales)
   - 4.5 [CTA upgrade contextual cuando uso >80%](#45-cta-contextual)
5. [Fase 3 — Upgrade Modal + UpgradeTrigger UI (Semana 3)](#5-fase-3)
   - 5.1 [Parcial _upgrade-modal.html.twig (comparación de planes)](#51-upgrade-modal)
   - 5.2 [JavaScript: comportamiento del modal (slide-panel)](#52-javascript)
   - 5.3 [Integración con UpgradeTriggerService (fire() → UI)](#53-trigger-ui)
   - 5.4 [Notificación de límite alcanzado (toast + CTA)](#54-notificación-límite)
6. [Especificaciones Técnicas Detalladas](#6-especificaciones-técnicas)
   - 6.1 [SubscriptionContextService: firma, métodos y lógica](#61-context-service-detalle)
   - 6.2 [SubscriptionProfileSection: implementación completa](#62-subscription-section-detalle)
   - 6.3 [FeatureLabelService: mapa de 80+ features → labels humanos](#63-feature-labels-detalle)
   - 6.4 [Estructura de datos SubscriptionContext](#64-estructura-datos)
   - 6.5 [Templates Twig: variables, parciales y directivas trans](#65-templates)
   - 6.6 [SCSS: estructura BEM, tokens y responsive](#66-scss-detalle)
   - 6.7 [hook_preprocess: inyección de SubscriptionContext](#67-preprocess-detalle)
   - 6.8 [Iconos usados (ICON-CONVENTION-001)](#68-iconos)
7. [Tabla de Archivos Creados/Modificados](#7-tabla-archivos)
8. [Tabla de Correspondencia con Especificaciones Técnicas](#8-correspondencia)
9. [Tabla de Cumplimiento de Directrices](#9-cumplimiento)
10. [Verificación RUNTIME-VERIFY-001](#10-verificación)
11. [Plan de Testing](#11-testing)
12. [Registro de Cambios](#12-registro)

---

## 1. Resumen Ejecutivo

### 1.1 Qué se implementa

Este plan implementa la **capa visual de PLG (Product-Led Growth)** que conecta la infraestructura de gating existente (FeatureAccessService, PlanValidator, UpgradeTriggerService) con la experiencia del usuario. El resultado es que el usuario **siempre sabe qué plan tiene, qué incluye, qué le falta, cuánto ha consumido, y cómo mejorar**.

Componentes principales:

1. **Tarjeta de suscripción en el perfil** (`/user/{uid}`): Muestra plan actual, estado (active/trial/past_due), días restantes si trial, fecha próximo pago. Usa el patrón extensible `UserProfileSectionRegistry` con un nuevo `SubscriptionProfileSection`.

2. **Lista de features con diferenciación visual**: Features incluidos (check verde) + features premium bloqueados (lock naranja con label "Disponible en Profesional →"). Datos dinámicos desde `SaasPlanFeatures` ConfigEntity, labels humanos desde `FeatureLabelService`.

3. **Barras de uso vs límites**: Indicadores visuales de consumo (ej: "47/50 consultas IA hoy") con color progresivo (verde < 60%, naranja 60-80%, rojo > 80%). Datos desde `PlanValidator::enforceLimit()`.

4. **Badge de plan en dashboards verticales**: Identificador visual del tier actual en el header del dashboard. Permite al usuario saber en todo momento qué plan tiene.

5. **Lock overlay en features premium**: Cuando el usuario navega a una funcionalidad que requiere upgrade, en vez de un 403, ve un overlay con comparación de plan y CTA de checkout. Usa `FeatureAccessService::canAccess()` + modal en slide-panel.

6. **CTA de upgrade contextual**: Botón "Mejorar plan" que aparece cuando el uso supera el 80% del límite. Conecta con la pricing page o directamente con checkout.

### 1.2 Por qué se implementa

El diagnóstico DIAG-PLG-001 reveló que el SaaS tiene **6 gaps críticos** en la experiencia PLG:

- 6 de 7 servicios de gating NO tienen representación visual
- 0 widgets de suscripción en el perfil
- 0 indicadores de features bloqueados
- 0 barras de uso
- El usuario descubre limitaciones solo cuando el sistema lo bloquea (403 genérico)

**Resultado actual:** Conversión free→paid estimada en 1-2% (benchmark sin PLG UI).
**Resultado esperado:** Conversión 5-8% (benchmark con PLG UI visible — OpenView Partners 2024).
**Delta:** +€33.180/año en MRR (conservador, base 1.000 usuarios).

### 1.3 Principios rectores

| Principio | Aplicación |
|-----------|-----------|
| **El usuario SIEMPRE sabe qué tiene** | Tarjeta de suscripción visible en perfil y dashboard |
| **Upgrade como oportunidad, no como bloqueo** | Locks naranjas con CTA persuasivo, no 403 |
| **Datos dinámicos desde admin** | Todo proviene de SaasPlan/SaasPlanFeatures ConfigEntities |
| **Parciales reutilizables** | 4 nuevos parciales Twig (subscription-card, feature-list, usage-bar, plan-badge) |
| **CSS tokens inyectables** | var(--ej-*) para todos los colores, configurable desde UI |
| **Mobile-first** | Diseño responsive, tarjetas apilables en móvil |
| **Textos traducibles** | {% trans %} en todos los textos UI |
| **SCSS Dart Sass moderno** | @use, color-mix(), BEM |
| **ICON-DUOTONE-001** | Locks, checks, barras con iconos duotone de paleta Jaraba |

### 1.4 Métricas de impacto

| Métrica | Antes | Después | Delta |
|---------|-------|---------|-------|
| Widgets de plan en perfil | 0 | 1 (prominente) | +1 |
| Features visibles con upgrade CTA | 0 | 80+ (dinámicos) | +80 |
| Barras de uso visibles | 0 | 4-6 por vertical | +6 |
| Conversión free→paid estimada | 1-2% | 5-8% | +3-6x |
| Servicios de gating con UI | 1/7 | 7/7 | +6 |

### 1.5 Alcance y exclusiones

**Dentro del alcance:**
- SubscriptionProfileSection en perfil (/user/{uid})
- 4 parciales Twig reutilizables
- SubscriptionContextService + FeatureLabelService
- Badge de plan en 10 dashboards verticales
- Barras de uso con datos reales
- Lock overlay para features bloqueados
- CTA de upgrade contextual
- SCSS con tokens inyectables
- Tests unitarios para servicios nuevos

**Fuera del alcance:**
- Cambios en FeatureAccessService (ya funcional)
- Cambios en PlanValidator (ya funcional)
- Email de upsell automatizado (ya existe via DunningService/UpgradeTriggerService)
- Stripe Customer Portal (cubierto por STRIPE-CHECKOUT-001 Fase 3)
- Cambios en la pricing page (ya funcional con CTAs de checkout)

### 1.6 Relación con planes previos

| Plan | Código | Relación |
|------|--------|---------|
| Stripe Checkout E2E | STRIPE-CHECKOUT-001 | Los CTAs de upgrade apuntan a `/planes/checkout/{plan}` ya operativo |
| Precios Configurables v2.1 | VERT-PRICING-001 | SubscriptionContextService usa SaasPlanFeatures cascade |
| Setup Wizard + Daily Actions | SETUP-WIZARD-DAILY-001 | La tarjeta de suscripción se integra en el mismo perfil hub |
| Verticales Componibles | ADDON-VERTICAL-001 | FeatureAccessService ya check addons verticales |

---

## 2. Arquitectura de la Solución

### 2.1 Principio: el usuario SIEMPRE sabe qué tiene y qué le falta

Cada página donde el usuario trabaja (perfil, dashboard vertical) debe contener:

1. **Qué plan tiene** → Badge + nombre del plan + estado
2. **Qué incluye** → Lista de features con checks verdes
3. **Qué le falta** → Features bloqueados con locks naranjas + "Disponible en {tier} →"
4. **Cuánto ha consumido** → Barras de uso vs límites del plan
5. **Cómo mejorar** → CTA "Mejorar plan" que conecta con pricing/checkout

### 2.2 Patrón de datos: SubscriptionContext

```php
// Estructura que SubscriptionContextService devuelve para renderizar en templates.
[
  'plan' => [
    'id' => 9,
    'name' => 'Emprendimiento Profesional',
    'tier' => 'professional',
    'vertical' => 'emprendimiento',
    'price_monthly' => 79.00,
    'is_free' => FALSE,
  ],
  'subscription' => [
    'status' => 'active', // active, trial, past_due, suspended, cancelled
    'trial_ends' => '2026-04-01', // NULL si no trial
    'trial_days_remaining' => 14, // 0 si no trial
    'next_billing_date' => '2026-04-17',
  ],
  'features' => [
    'included' => [
      ['key' => 'bmc_ia', 'label' => 'Business Model Canvas con IA', 'icon' => 'canvas'],
      ['key' => 'validacion_mvp', 'label' => 'Validación MVP', 'icon' => 'experiment'],
      // ... 16 features más para Profesional
    ],
    'locked' => [
      ['key' => 'api_access', 'label' => 'Acceso API completo', 'icon' => 'code', 'available_in' => 'Enterprise'],
      ['key' => 'white_label', 'label' => 'Marca blanca', 'icon' => 'palette', 'available_in' => 'Enterprise'],
      // ... 5 features más de Enterprise
    ],
  ],
  'usage' => [
    ['key' => 'copilot_sessions_daily', 'label' => 'Consultas IA hoy', 'current' => 47, 'limit' => 200, 'percentage' => 23],
    ['key' => 'hypotheses_active', 'label' => 'Hipótesis activas', 'current' => 12, 'limit' => 100, 'percentage' => 12],
    ['key' => 'mentoring_sessions_monthly', 'label' => 'Sesiones de mentoría este mes', 'current' => 3, 'limit' => 10, 'percentage' => 30],
  ],
  'upgrade' => [
    'available' => TRUE, // FALSE si ya es Enterprise
    'next_tier' => 'enterprise',
    'next_tier_label' => 'Enterprise',
    'next_tier_price' => 199.00,
    'features_unlocked_count' => 5,
    'checkout_url' => '/es/planes/checkout/10', // Pre-resolved
    'pricing_url' => '/es/planes/emprendimiento',
  ],
]
```

### 2.3 Servicios nuevos y existentes

**NUEVOS (2):**

| Servicio | Service ID | Archivo | Función |
|----------|-----------|---------|---------|
| SubscriptionContextService | `ecosistema_jaraba_core.subscription_context` | `src/Service/SubscriptionContextService.php` | Resuelve SubscriptionContext completo para un usuario |
| FeatureLabelService | `ecosistema_jaraba_core.feature_labels` | `src/Service/FeatureLabelService.php` | Mapa 80+ feature keys → labels humanos + iconos |

**EXISTENTES (reutilizados, no modificados):**

| Servicio | Uso |
|----------|-----|
| PlanResolverService | Cascade features por vertical+tier |
| FeatureAccessService | Check plan+addons |
| PlanValidator | enforceLimit() para uso actual |
| TenantContextService | Resolver tenant del usuario |
| TenantBridgeService | Tenant↔Group conversion |
| MetaSitePricingService | Precios para CTA de upgrade |

### 2.4 Parciales Twig reutilizables (4 nuevos)

| Parcial | Archivo | Variables | Dónde se incluye |
|---------|---------|-----------|-----------------|
| `_subscription-card.html.twig` | `templates/partials/` | plan, subscription, upgrade | page--user.html.twig |
| `_feature-list.html.twig` | `templates/partials/` | features.included, features.locked | page--user.html.twig, dashboards |
| `_usage-bar.html.twig` | `templates/partials/` | usage[] | page--user.html.twig, dashboards |
| `_plan-badge.html.twig` | `templates/partials/` | plan.name, plan.tier | Dashboard headers (10 verticales) |

### 2.5 SCSS con tokens inyectables

**Nuevo archivo:** `scss/components/_subscription-card.scss`
**Incluido en:** `scss/main.scss` via `@use 'components/subscription-card';`

**Colores (todos var(--ej-*)):**
- Feature incluido: `var(--ej-color-success, #10B981)` (check verde)
- Feature bloqueado: `var(--ej-color-primary, #FF8C42)` (lock naranja impulso)
- Uso normal (<60%): `var(--ej-color-success, #10B981)`
- Uso medio (60-80%): `var(--ej-color-warning, #F59E0B)`
- Uso alto (>80%): `var(--ej-color-danger, #EF4444)`
- Badge plan: `var(--ej-color-corporate, #233D63)`
- CTA upgrade: `var(--ej-color-primary, #FF8C42)` (naranja impulso)

### 2.6 Integración con UserProfileSectionRegistry

```yaml
# ecosistema_jaraba_core.services.yml
ecosistema_jaraba_core.user_profile_section.subscription:
  class: Drupal\ecosistema_jaraba_core\UserProfile\SubscriptionProfileSection
  arguments:
    - '@ecosistema_jaraba_core.subscription_context'
    - '@?ecosistema_jaraba_core.tenant_context'
  tags:
    - { name: ecosistema_jaraba_core.user_profile_section }
```

**Peso:** 5 (aparece ANTES del professional_profile que tiene peso 10)

### 2.7 Integración con dashboards verticales

Cada dashboard vertical incluirá:
```twig
{# En el hero header del dashboard #}
{% if subscription_context.plan %}
  {% include '@ecosistema_jaraba_theme/partials/_plan-badge.html.twig' with {
    plan: subscription_context.plan,
  } only %}
{% endif %}

{# Después del Setup Wizard, antes del contenido principal #}
{% if subscription_context.usage %}
  {% include '@ecosistema_jaraba_theme/partials/_usage-bar.html.twig' with {
    usage: subscription_context.usage,
    upgrade: subscription_context.upgrade,
  } only %}
{% endif %}
```

---

## 6. Especificaciones Técnicas Detalladas

### 6.1 SubscriptionContextService

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/SubscriptionContextService.php`

**Dependencias (DI):**
- `@ecosistema_jaraba_core.tenant_context` (resolver tenant del usuario)
- `@ecosistema_jaraba_core.tenant_bridge` (Tenant↔Group)
- `@ecosistema_jaraba_core.plan_resolver` (features cascade)
- `@?jaraba_billing.feature_access` (check acceso — opcional cross-módulo)
- `@?jaraba_billing.plan_validator` (uso actual — opcional cross-módulo)
- `@ecosistema_jaraba_core.feature_labels` (labels humanos)
- `@entity_type.manager` (cargar SaasPlan, Tenant)

**Firma del método principal:**
```php
public function getContextForUser(int $uid): array
```

**Lógica:**
1. Resolver tenant del usuario via TenantContextService
2. Si no hay tenant → retornar contexto vacío (usuario sin suscripción)
3. Cargar SaasPlan desde tenant.subscription_plan
4. Cargar SaasPlanFeatures via PlanResolverService
5. Clasificar features: included (en el plan actual) vs locked (en tiers superiores)
6. Para cada feature locked, determinar en qué tier está disponible
7. Calcular uso actual vs límites via PlanValidator
8. Determinar upgrade path (next tier, precio, checkout URL)
9. Retornar SubscriptionContext array

### 6.3 FeatureLabelService

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/FeatureLabelService.php`

**Mapa de features → labels (extracto de los 80+):**
```php
protected const FEATURE_LABELS = [
  // Emprendimiento
  'bmc_ia' => ['label' => 'Business Model Canvas con IA', 'icon_cat' => 'business', 'icon_name' => 'canvas'],
  'validacion_mvp' => ['label' => 'Validación MVP (Lean Startup)', 'icon_cat' => 'business', 'icon_name' => 'experiment'],
  'mentoring_1a1' => ['label' => 'Mentoría individual', 'icon_cat' => 'business', 'icon_name' => 'mentoring'],
  'proyecciones_financieras' => ['label' => 'Proyecciones financieras', 'icon_cat' => 'analytics', 'icon_name' => 'chart-line'],
  'copilot_proactivo' => ['label' => 'Copilot proactivo con IA', 'icon_cat' => 'ai', 'icon_name' => 'sparkles'],
  'acceso_financiacion' => ['label' => 'Acceso a financiación', 'icon_cat' => 'business', 'icon_name' => 'funding'],
  // ... 70+ más
];
```

**Método:**
```php
public function getLabel(string $featureKey): array {
  return self::FEATURE_LABELS[$featureKey] ?? [
    'label' => ucfirst(str_replace('_', ' ', $featureKey)),
    'icon_cat' => 'ui',
    'icon_name' => 'check',
  ];
}
```

### 6.4 Estructura de datos SubscriptionContext

Ver sección 2.2 para la estructura completa del array.

**Notas de implementación:**
- `plan.tier` se resuelve via `PlanResolverService::normalize()` (acepta cualquier nombre de plan)
- `features.locked` se calcula comparando los features del tier actual con los de los tiers superiores
- `usage[].current` se obtiene via queries rápidas (COUNT, no entity loads — <50ms por query)
- `upgrade.checkout_url` se pre-resuelve via `Url::fromRoute()` (ROUTE-LANGPREFIX-001)

### 6.8 Iconos usados (ICON-CONVENTION-001)

| Contexto | Icono | Variante | Color |
|----------|-------|----------|-------|
| Feature incluido | check-circle | duotone | verde-innovacion |
| Feature bloqueado | lock | duotone | naranja-impulso |
| Plan badge | shield-check | duotone | azul-corporativo |
| Uso normal | gauge | duotone | verde-innovacion |
| Uso alto | alert-triangle | duotone | naranja-impulso |
| CTA upgrade | arrow-up-circle | duotone | naranja-impulso |
| Trial countdown | clock | duotone | azul-corporativo |

---

## 7. Tabla de Archivos Creados/Modificados

### Archivos nuevos (8)

| Archivo | Tipo | Propósito |
|---------|------|-----------|
| `ecosistema_jaraba_core/src/Service/SubscriptionContextService.php` | Service | Resolver SubscriptionContext |
| `ecosistema_jaraba_core/src/Service/FeatureLabelService.php` | Service | Labels humanos para 80+ features |
| `ecosistema_jaraba_core/src/UserProfile/SubscriptionProfileSection.php` | Section | Widget suscripción en perfil |
| `ecosistema_jaraba_theme/templates/partials/_subscription-card.html.twig` | Parcial | Tarjeta de plan + estado + CTA |
| `ecosistema_jaraba_theme/templates/partials/_feature-list.html.twig` | Parcial | Lista features (checks + locks) |
| `ecosistema_jaraba_theme/templates/partials/_usage-bar.html.twig` | Parcial | Barras de uso vs límites |
| `ecosistema_jaraba_theme/templates/partials/_plan-badge.html.twig` | Parcial | Badge de plan para dashboard headers |
| `ecosistema_jaraba_theme/scss/components/_subscription-card.scss` | SCSS | Estilos con var(--ej-*) |

### Archivos modificados (13)

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | 3 servicios nuevos registrados |
| `ecosistema_jaraba_theme/scss/main.scss` | @use 'components/subscription-card' |
| `ecosistema_jaraba_theme/templates/page--user.html.twig` | Include _subscription-card + _feature-list |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | Inyección de subscription_context en preprocess |
| `jaraba_business_tools/templates/entrepreneur-dashboard.html.twig` | Include _plan-badge + _usage-bar |
| 8 dashboards verticales más | Include _plan-badge + _usage-bar |

---

## 8. Tabla de Correspondencia con Especificaciones Técnicas

| Especificación | Acción | Directriz |
|---------------|--------|-----------|
| UserProfileSectionRegistry | Nuevo SubscriptionProfileSection tagged | SETUP-WIZARD-DAILY-001 |
| SubscriptionContextService | Nuevo servicio con @? cross-módulo | OPTIONAL-CROSSMODULE-001 |
| FeatureLabelService | Mapa estático 80+ features | — |
| 4 parciales Twig | {% include ... only %} | TWIG-INCLUDE-ONLY-001 |
| Todos los textos | {% trans %} | i18n |
| Todos los colores | var(--ej-*) | CSS-VAR-ALL-COLORS-001 |
| Iconos | duotone, paleta Jaraba | ICON-CONVENTION-001 |
| URLs | Url::fromRoute() | ROUTE-LANGPREFIX-001 |
| Precios | MetaSitePricingService | NO-HARDCODE-PRICE-001 |
| SCSS | @use, color-mix(), Dart Sass | SCSS-001 |
| Compilación | npm run build:css | SCSS-COMPILE-VERIFY-001 |
| Body classes | hook_preprocess_html() | Directriz body classes |
| DI services | @? para cross-módulo | OPTIONAL-CROSSMODULE-001 |
| catch blocks | \Throwable | UPDATE-HOOK-CATCH-001 |

---

## 9. Tabla de Cumplimiento de Directrices

| Directriz | Aplica | Cómo se cumple |
|-----------|--------|----------------|
| ZERO-REGION-001 | ✅ | Datos via preprocess, no controller return |
| ZERO-REGION-003 | ✅ | drupalSettings via $variables['#attached'] |
| CSS-VAR-ALL-COLORS-001 | ✅ | Todos los colores via var(--ej-*) |
| SCSS-COMPILE-VERIFY-001 | ✅ | Compilar + verificar timestamp |
| TWIG-INCLUDE-ONLY-001 | ✅ | Keyword only en todos los includes |
| ICON-DUOTONE-001 | ✅ | Variante default duotone |
| NO-HARDCODE-PRICE-001 | ✅ | Precios via SubscriptionContextService |
| ROUTE-LANGPREFIX-001 | ✅ | URLs pre-resueltas en PHP |
| OPTIONAL-CROSSMODULE-001 | ✅ | @? para jaraba_billing services |
| TENANT-BRIDGE-001 | ✅ | TenantBridgeService para Tenant↔Group |
| UPDATE-HOOK-CATCH-001 | ✅ | \Throwable en todos los catch |
| ORTOGRAFIA-TRANS-001 | ✅ | Tildes correctas en {% trans %} |
| PIPELINE-E2E-001 | ✅ | L1→L2→L3→L4 verificadas |
| IMPLEMENTATION-CHECKLIST-001 | ✅ | Servicio + ruta + template + SCSS |

---

## 10. Verificación RUNTIME-VERIFY-001

Tras implementar, verificar:

1. **CSS compilado**: timestamp CSS > SCSS
2. **Parciales renderizados**: curl /user/{uid} → grep "subscription-card"
3. **Datos dinámicos**: verificar que plan_name, features, usage aparecen en HTML
4. **drupalSettings**: verificar que upgrade.checkout_url está pre-resuelto
5. **Responsive**: verificar en viewport 375px (mobile)
6. **i18n**: verificar que textos usan {% trans %} (ORTOGRAFIA-TRANS-001)
7. **Iconos**: verificar que lock/check son duotone de paleta Jaraba
8. **Colores**: verificar que barras usan var(--ej-color-success/warning/danger)

---

## 11. Plan de Testing

### Tests Unitarios

| Test | Clase | Qué verifica |
|------|-------|-------------|
| SubscriptionContextTest | `ecosistema_jaraba_core/tests/src/Unit/` | getContextForUser() con plan activo, trial, sin plan |
| FeatureLabelServiceTest | `ecosistema_jaraba_core/tests/src/Unit/` | Labels para keys conocidos y desconocidos |

### Tests Kernel

| Test | Clase | Qué verifica |
|------|-------|-------------|
| SubscriptionProfileSectionTest | `ecosistema_jaraba_core/tests/src/Kernel/` | isApplicable, getLinks, getExtraData con tenant mock |

---

## 12. Registro de Cambios

| Versión | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-17 | Claude Opus 4.6 | Creación del plan: 3 fases, 15 acciones, basado en diagnóstico DIAG-PLG-001 |
