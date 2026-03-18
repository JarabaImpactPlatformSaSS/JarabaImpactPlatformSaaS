# Plan de Implementación: Pricing, Add-ons & PLG — Clase Mundial

**Fecha:** 2026-03-18
**Versión:** 1.0.0
**Estado:** ESPECIFICACIÓN APROBADA
**Autor:** Claude Opus 4.6 (Anthropic) — Arquitecto SaaS Senior + Consultor de Negocio Senior
**Auditoría de referencia:** `docs/analisis/2026-03-18_Auditoria_Integral_Pricing_Addons_PLG_Clase_Mundial_v1.md` (AUDIT-PRICING-PLG-001)
**Especificación de referencia:** Doc 158 — Platform Vertical Pricing Matrix v1
**Módulos afectados:** ecosistema_jaraba_core, jaraba_billing, jaraba_addons, jaraba_usage_billing, ecosistema_jaraba_theme
**Estimación total:** 9-15 días de trabajo
**Prerequisitos:** Acceso Stripe API (settings.secrets.php), Lando activo, MariaDB 10.11

---

## Tabla de Contenidos (TOC)

1. [Contexto y Motivación](#1-contexto-y-motivación)
2. [Estado Actual — Scorecard](#2-estado-actual--scorecard)
3. [Gaps Identificados (11 items)](#3-gaps-identificados)
4. [Arquitectura de la Solución](#4-arquitectura-de-la-solución)
   - 4.1 [Diagrama de Flujo Completo](#41-diagrama-de-flujo-completo)
   - 4.2 [Modelo de Datos Ampliado](#42-modelo-de-datos-ampliado)
   - 4.3 [Servicios Nuevos y Modificados](#43-servicios-nuevos-y-modificados)
   - 4.4 [Templates y Parciales Twig](#44-templates-y-parciales-twig)
   - 4.5 [SCSS y Compilación](#45-scss-y-compilación)
5. [Fases de Implementación](#5-fases-de-implementación)
   - Fase A: P0 — Corrección de Precios (1-2 días)
   - Fase B: P1 — Add-ons en Perfil + Bundles + Compatibilidad (3-5 días)
   - Fase C: P2 — Uso Real + API + Descuentos (3-5 días)
   - Fase D: P3 — PLG Avanzado (Wizard + Daily Actions) (2-3 días)
6. [Tabla de Correspondencia con Especificaciones](#6-tabla-de-correspondencia-con-especificaciones)
7. [Cumplimiento de Directrices](#7-cumplimiento-de-directrices)
8. [Testing y Verificación](#8-testing-y-verificación)
9. [Checklist IMPLEMENTATION-CHECKLIST-001](#9-checklist-implementation-checklist-001)
10. [Impacto de Negocio](#10-impacto-de-negocio)

---

## 1. Contexto y Motivación

### 1.1 Problema

La auditoría AUDIT-PRICING-PLG-001 identificó 11 gaps entre la especificación Doc 158 y la implementación actual del sistema de precios. Los más críticos:

1. **7 de 15 planes tienen precios incorrectos** (no alineados con Doc 158, que es la fuente de verdad tras estudio de mercado — Golden Rule #131).
2. **El usuario no puede ver ni gestionar add-ons desde su perfil** ("Mi suscripción" solo muestra plan base).
3. **Los bundles de marketing no existen**, eliminando la principal estrategia de upsell modular.
4. **Las barras de uso siempre muestran 0%**, neutralizando el motor PLG de upgrade orgánico.

### 1.2 Objetivo

Llevar el sistema de pricing, add-ons y PLG al nivel de clase mundial definido por Doc 158, asegurando:

- **Conformidad 100%** con precios del estudio de mercado
- **Experiencia completa end-to-end** para el usuario: ver plan → ver add-ons → comprar → gestionar → upgrade
- **PLG nativo** con señales de upgrade en cada punto de contacto natural
- **Cumplimiento total** de directrices del proyecto (50+ reglas aplicables)

### 1.3 Principios de Implementación

| Principio | Directriz | Aplicación |
|-----------|-----------|------------|
| Sin precios hardcodeados | NO-HARDCODE-PRICE-001 | Todo precio desde MetaSitePricingService o SaasPlan entity |
| Colores como tokens | CSS-VAR-ALL-COLORS-001 | Todo SCSS usa `var(--ej-*, fallback)` |
| Iconos duotone de marca | ICON-CONVENTION-001 + ICON-DUOTONE-001 | `jaraba_icon('category', 'name', { variant: 'duotone' })` |
| Textos siempre traducibles | i18n {% trans %} | Bloques `{% trans %}...{% endtrans %}`, NUNCA filtro `|t` |
| SCSS con Dart Sass moderno | SCSS-001, SCSS-COMPILE-VERIFY-001 | `@use '../variables' as *;`, compilar tras cada edición |
| Variables desde UI de Drupal | SSOT-THEME-001 | Valores configurables en Appearance > Ecosistema Jaraba Theme |
| Páginas frontend limpias | ZERO-REGION-001 | Sin `page.content` ni bloques heredados |
| Parciales reutilizables | TWIG-INCLUDE-ONLY-001 | `{% include %}` con `only` keyword |
| Acciones en slide-panel | SLIDE-PANEL-RENDER-001 | Modales para crear/editar, sin abandonar página |
| Mobile-first | Responsive | Layout pensado para móvil primero |
| Body classes via PHP | Hook preprocess | `hook_preprocess_html()`, NUNCA `attributes.addClass()` en template |
| URLs via Url::fromRoute() | ROUTE-LANGPREFIX-001 | NUNCA paths hardcodeados (el sitio usa /es/ prefix) |
| Forms premium | PREMIUM-FORMS-PATTERN-001 | Extienden PremiumEntityFormBase |
| Entidades con Views + Field UI | ENTITY-FK-001 | views_data en anotación, field_ui_base_route |
| Navigation admin | Estructura + Contenido | ConfigEntities en /admin/structure, ContentEntities en /admin/content |
| Ejecución en contenedor | Lando | `lando drush`, `lando npm`, `lando php` |
| Color-mix para runtime | SCSS-COMPILETIME-001 | `color-mix(in srgb, var(--ej-*) N%, transparent)` |
| Ortografía española | ORTOGRAFIA-TRANS-001 | Tildes y eñes correctas en textos traducibles |

---

## 2. Estado Actual — Scorecard

| Dimensión | Puntuación | Objetivo | Descripción |
|-----------|-----------|----------|-------------|
| Conformidad Doc 158 | 2/5 | 5/5 | 7 precios incorrectos, bundles ausentes |
| Pricing Pages (público) | 4/5 | 5/5 | /planes y /planes/{vertical} funcionan bien |
| Admin SaaS Plans | 5/5 | 5/5 | /admin/structure/saas-plan completo |
| Checkout Stripe | 4/5 | 5/5 | Embedded checkout funcional, stripe_price_id vacíos |
| Mi Suscripción (perfil) | 3/5 | 5/5 | Plan mostrado, add-ons ausentes |
| Catálogo Add-ons | 4/5 | 5/5 | /addons funcional, sin filtro vertical |
| Bundles Marketing | 0/5 | 5/5 | No implementados |
| Barras de Uso | 1/5 | 5/5 | Template OK, datos siempre 0 |
| PLG Signals | 2/5 | 5/5 | Upgrade CTA existe, faltan 5 touchpoints |
| Setup Wizard PLG | 0/5 | 4/5 | Sin paso de suscripción |
| Daily Actions PLG | 0/5 | 4/5 | Sin acción de upgrade condicional |
| API REST Suscripción | 2/5 | 5/5 | 4/6 endpoints implementados |
| **TOTAL** | **27/60 (45%)** | **57/60 (95%)** | — |

---

## 3. Gaps Identificados

| ID | Título | Severidad | Fase | Archivos Afectados |
|----|--------|-----------|------|---------------------|
| GAP-PRICING-001 | Precios config ≠ Doc 158 | P0 | A | 10 archivos YAML en config/sync |
| GAP-PRICING-002 | Add-ons no integrados en Mi Suscripción | P1 | B | SubscriptionContextService, _subscription-card.html.twig, SubscriptionProfileSection |
| GAP-PRICING-003 | Bundles de marketing no implementados | P1 | B | jaraba_addons.install, Addon entities |
| GAP-PRICING-004 | Matriz compatibilidad add-ons × vertical | P1 | B | AddonCatalogController, addons-catalog.html.twig |
| GAP-PRICING-005 | Total mensual no visible | P1 | B | _subscription-card.html.twig, SubscriptionContextService |
| GAP-PRICING-006 | Barras de uso siempre en 0 | P2 | C | SubscriptionContextService::resolveUsage() |
| GAP-PRICING-007 | API /subscription incompleta | P2 | C | ecosistema_jaraba_core.routing.yml, nuevo controller |
| GAP-PRICING-008 | Descuento anual inconsistente | P2 | C | Addon entity, addons-detail.html.twig |
| GAP-PRICING-009 | Setup Wizard sin paso suscripción | P3 | D | Nuevo step global |
| GAP-PRICING-010 | Daily Actions sin acción PLG | P3 | D | Nueva action condicional |
| GAP-PRICING-011 | Add-ons marketing sin verificar seed | P2 | C | jaraba_addons.install |

---

## 4. Arquitectura de la Solución

### 4.1 Diagrama de Flujo Completo

```
┌─────────────────────────────────────────────────────────────────────┐
│                    EXPERIENCIA COMPLETA DEL USUARIO                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  VISITANTE                                                           │
│  ─────────                                                           │
│  /planes ──→ Hub con 7 verticales (MetaSitePricingService)          │
│       ↓                                                              │
│  /planes/{vertical} ──→ 3 tiers + toggle mensual/anual              │
│       ↓                                                              │
│  "Empezar gratis" ──→ /registro/{vertical}?plan={id}                │
│  "Contratar"      ──→ /planes/checkout/{saas_plan} (Stripe)         │
│                                                                      │
│  USUARIO AUTENTICADO                                                 │
│  ────────────────────                                                │
│  Dashboard ──→ _subscription-card.html.twig                         │
│       │         ├── Plan actual + estado + precio                    │
│       │         ├── Features incluidos/bloqueados                    │
│       │         ├── Barras de uso REALES [GAP-006]                   │
│       │         ├── ADD-ONS ACTIVOS [GAP-002] ←── NUEVO             │
│       │         ├── ADD-ONS RECOMENDADOS [GAP-004] ←── NUEVO        │
│       │         ├── Total mensual (base+addons) [GAP-005] ←── NUEVO │
│       │         ├── CTA "Mejorar plan" ──→ /planes/checkout/{plan}  │
│       │         └── Link "Explorar Add-ons" ──→ /addons             │
│       │                                                              │
│       ├──→ Setup Wizard ──→ Paso "Elige tu plan" [GAP-009] ←── NUEVO│
│       │                                                              │
│       └──→ Daily Actions ──→ "Revisa tu plan" [GAP-010] ←── NUEVO  │
│                                 (condicional: uso>60% o 30d sin rev) │
│                                                                      │
│  /addons ──→ Catálogo con filtro por vertical [GAP-004]             │
│       ↓       + badges ⭐ recomendado                                │
│  /addons/{id} ──→ Detalle + toggle + suscribirse                    │
│       ↓                                                              │
│  POST /api/v1/addons/{id}/subscribe ──→ AddonSubscription            │
│                                                                      │
│  BUNDLES [GAP-003] ←── NUEVO                                        │
│  /addons?type=bundle ──→ 4 bundles con descuento                    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.2 Modelo de Datos Ampliado

#### 4.2.1 Addon entity — campos nuevos para bundles y compatibilidad

```
Addon (ContentEntity existente)
├── id: SERIAL
├── label: VARCHAR — nombre del addon/bundle
├── addon_type: VARCHAR — vertical|feature|storage|api_calls|support|custom|bundle ← NUEVO: 'bundle'
├── description: TEXT — descripción rica
├── price_monthly: DECIMAL(10,2)
├── price_yearly: DECIMAL(10,2)
├── features: TEXT (JSON) — lista de features incluidos
├── limits: TEXT (JSON) — límites por feature
├── vertical_ref: VARCHAR — referencia a vertical (si addon_type='vertical')
├── compatible_verticals: TEXT (JSON) ← NUEVO: array de vertical machine_names compatibles
├── recommendation_level: VARCHAR ← NUEVO: 'recommended'|'available'|'not_applicable'
├── bundle_items: TEXT (JSON) ← NUEVO: para type='bundle', array de addon IDs incluidos
├── bundle_discount_pct: INT ← NUEVO: porcentaje de descuento del bundle
├── status: BOOLEAN
├── weight: INT
├── created/changed: timestamps
└── stripe_product_id: VARCHAR — Stripe product reference
```

**Nota:** Los campos `compatible_verticals`, `recommendation_level`, `bundle_items` y `bundle_discount_pct` se añaden como base fields con `hook_update_N()` (UPDATE-HOOK-REQUIRED-001). No requieren Field UI ya que son datos programáticos, no editables por el admin genérico.

#### 4.2.2 SubscriptionContext — estructura ampliada

```php
// Estructura actual de SubscriptionContextService::getContextForUser()
[
  'plan' => [...],              // ✅ Existe
  'subscription' => [...],      // ✅ Existe
  'features' => [...],          // ✅ Existe
  'usage' => [...],             // ⚠️ Existe pero siempre 0
  'upgrade' => [...],           // ✅ Existe
  // ──── NUEVAS SECCIONES ────
  'addons' => [                 // ← NUEVO (GAP-002)
    'active' => [
      ['addon_id' => 5, 'label' => 'jaraba_email', 'price' => 29.0, 'icon_cat' => 'ui', 'icon_name' => 'mail'],
      ...
    ],
    'recommended' => [           // ← NUEVO (GAP-004)
      ['addon_id' => 3, 'label' => 'jaraba_crm', 'price' => 19.0, 'recommendation' => 'high_impact', ...],
      ...
    ],
  ],
  'billing' => [                 // ← NUEVO (GAP-005)
    'total_monthly' => 127.0,    // plan_base + Σ(addon_prices)
    'next_invoice_date' => '15/04/2026',
    'billing_cycle' => 'monthly',
  ],
]
```

### 4.3 Servicios Nuevos y Modificados

#### 4.3.1 Servicios a MODIFICAR

| Servicio | Cambio | Directriz |
|----------|--------|-----------|
| `SubscriptionContextService` | Añadir resolución de add-ons activos, add-ons recomendados, total mensual, next invoice | OPTIONAL-CROSSMODULE-001 (AddonSubscriptionService via @?) |
| `AddonCatalogController` | Filtrar por compatibilidad vertical del tenant, badges de recomendación | TENANT-001 |
| `MetaSitePricingService` | Sin cambios (ya lee de SaasPlan entities) | — |

#### 4.3.2 Servicios a CREAR

| Servicio | Módulo | Función | Tag |
|----------|--------|---------|-----|
| `AddonCompatibilityService` | jaraba_addons | Resuelve compatibilidad add-on × vertical, devuelve 'recommended'/'available'/'not_applicable' | — |
| `SubscriptionUpgradeStep` | ecosistema_jaraba_core | Setup Wizard step global: "Elige tu plan ideal" | `ecosistema_jaraba_core.setup_wizard_step` |
| `ReviewSubscriptionAction` | ecosistema_jaraba_core | Daily Action condicional: "Revisa tu suscripción" | `ecosistema_jaraba_core.daily_action` |

### 4.4 Templates y Parciales Twig

#### 4.4.1 Parciales EXISTENTES a modificar

| Parcial | Cambio |
|---------|--------|
| `_subscription-card.html.twig` | Añadir 3 secciones: add-ons activos, add-ons recomendados, total+factura |

#### 4.4.2 Parciales NUEVOS a crear

| Parcial | Propósito | Incluido por |
|---------|-----------|-------------|
| `_subscription-addons.html.twig` | Lista de add-ons activos del tenant (cards compactas con precio, botón configurar) | `_subscription-card.html.twig` |
| `_subscription-recommended.html.twig` | Add-ons recomendados según vertical (max 3, badges ⭐, botón "+ Añadir") | `_subscription-card.html.twig` |
| `_subscription-billing-summary.html.twig` | Total mensual, próxima factura, link a facturación | `_subscription-card.html.twig` |

**Convenciones para todos los parciales nuevos:**

```twig
{# TWIG-INCLUDE-ONLY-001: Se incluye con keyword only #}
{# CSS-VAR-ALL-COLORS-001: Todos los colores via var(--ej-*, fallback) #}
{# i18n: Todos los textos con {% trans %}...{% endtrans %} #}
{# ICON-CONVENTION-001: jaraba_icon() con variant: 'duotone' #}
```

Ejemplo de inclusión en `_subscription-card.html.twig`:
```twig
{% if ctx.addons.active is not empty %}
  {% include '@ecosistema_jaraba_theme/partials/_subscription-addons.html.twig' with {
    active_addons: ctx.addons.active,
    addons_catalog_url: path('jaraba_addons.catalog'),
  } only %}
{% endif %}
```

#### 4.4.3 Template del catálogo — modificaciones

| Archivo | Cambio |
|---------|--------|
| `addons-catalog.html.twig` | Añadir badge ⭐ "Recomendado para tu vertical" cuando `addon.recommendation_level == 'recommended'` |
| `addons-detail.html.twig` | Mostrar banner de compatibilidad si `addon.compatible_verticals` no incluye vertical del tenant |

### 4.5 SCSS y Compilación

#### 4.5.1 Archivos SCSS nuevos

Todos los estilos de los parciales nuevos se integran en el archivo SCSS existente de la tarjeta de suscripción, para no crear archivos huérfanos (SCSS-ENTRY-CONSOLIDATION-001).

| Archivo | Contiene | Entry point |
|---------|----------|-------------|
| `scss/components/_subscription-card.scss` | Estilos para `_subscription-card`, `_subscription-addons`, `_subscription-recommended`, `_subscription-billing-summary` | Incluido en `main.scss` |
| `scss/components/_addons-catalog.scss` | Estilos para badge de recomendación y compatibilidad | Incluido en `main.scss` |

**Reglas SCSS obligatorias:**

```scss
// SCSS-001: @use crea scope aislado, cada parcial debe incluir:
@use '../variables' as *;

// CSS-VAR-ALL-COLORS-001: NUNCA hex hardcoded
.subscription-card__addon-price {
  color: var(--ej-color-impulse, #FF8C42);
}

// SCSS-COMPILETIME-001: Para alpha runtime, usar color-mix
.subscription-card__addon-card {
  background: color-mix(in srgb, var(--ej-bg-surface, #ffffff) 95%, transparent);
  border: 1px solid color-mix(in srgb, var(--ej-color-corporate, #233D63) 15%, transparent);
}

// SCSS-COLORMIX-001: NUNCA rgba()
// ❌ background: rgba(35, 61, 99, 0.1);
// ✅ background: color-mix(in srgb, var(--ej-color-corporate, #233D63) 10%, transparent);
```

#### 4.5.2 Compilación

```bash
# Ejecutar DENTRO del contenedor Lando
lando npm run build --prefix web/themes/custom/ecosistema_jaraba_theme/

# Verificar timestamp (SCSS-COMPILE-VERIFY-001)
lando bash -c "stat -c '%Y %n' web/themes/custom/ecosistema_jaraba_theme/css/ecosistema-jaraba-theme.css web/themes/custom/ecosistema_jaraba_theme/scss/main.scss"

# Limpiar cache
lando drush cr
```

---

## 5. Fases de Implementación

### Fase A: P0 — Corrección de Precios (1-2 días)

#### A.1 Actualizar configs SaasPlan YAML

**Archivos a modificar** (10 archivos en `config/sync/`):

| Archivo | Campo `price_monthly` | Valor actual | Valor correcto (Doc 158) |
|---------|----------------------|-------------|--------------------------|
| `ecosistema_jaraba_core.saas_plan.empleabilidad_enterprise.yml` | price_monthly | 199 | **149** |
| `ecosistema_jaraba_core.saas_plan.emprendimiento_basico.yml` | price_monthly | 29 | **39** |
| `ecosistema_jaraba_core.saas_plan.emprendimiento_profesional.yml` | price_monthly | 79 | **99** |
| `ecosistema_jaraba_core.saas_plan.agroconecta_basico.yml` | price_monthly | 29 | **49** |
| `ecosistema_jaraba_core.saas_plan.agroconecta_profesional.yml` | price_monthly | 59 | **129** |
| `ecosistema_jaraba_core.saas_plan.agroconecta_enterprise.yml` | price_monthly | 149 | **249** |
| `ecosistema_jaraba_core.saas_plan.comercioconecta_basico.yml` | price_monthly | 29 | **39** |
| `ecosistema_jaraba_core.saas_plan.comercioconecta_profesional.yml` | price_monthly | 59 | **99** |
| `ecosistema_jaraba_core.saas_plan.comercioconecta_enterprise.yml` | price_monthly | 149 | **199** |
| `ecosistema_jaraba_core.saas_plan.serviciosconecta_profesional.yml` | price_monthly | 59 | **79** |

**También actualizar `price_yearly`** aplicando "2 meses gratis" (×10):

| Vertical | Tier | `price_yearly` correcto |
|----------|------|-------------------------|
| Empleabilidad Enterprise | €149 × 10 | **1490** |
| Emprendimiento Starter | €39 × 10 | **390** |
| Emprendimiento Pro | €99 × 10 | **990** |
| AgroConecta Starter | €49 × 10 | **490** |
| AgroConecta Pro | €129 × 10 | **1290** |
| AgroConecta Enterprise | €249 × 10 | **2490** |
| ComercioConecta Starter | €39 × 10 | **390** |
| ComercioConecta Pro | €99 × 10 | **990** |
| ComercioConecta Enterprise | €199 × 10 | **1990** |
| ServiciosConecta Pro | €79 × 10 | **790** |

#### A.2 Importar configuración y sincronizar Stripe

```bash
# 1. Importar configs actualizadas
lando drush cim --partial --source=modules/custom/ecosistema_jaraba_core/config/install -y

# 2. Sincronizar con Stripe
lando drush jaraba:stripe:sync-products

# 3. Verificar sincronización
lando drush jaraba:stripe:verify-prices

# 4. Limpiar cache
lando drush cr
```

#### A.3 Verificación visual

Acceder a `https://jaraba-saas.lndo.site/planes` y verificar cada vertical con sus precios correctos.

**Criterios de aceptación Fase A:**
- [ ] 15/15 planes con precios alineados a Doc 158
- [ ] Stripe Products/Prices sincronizados
- [ ] /planes muestra precios correctos
- [ ] /planes/{vertical} muestra precios correctos para cada vertical

---

### Fase B: P1 — Add-ons en Perfil + Bundles + Compatibilidad (3-5 días)

#### B.1 Ampliar SubscriptionContextService (GAP-002, GAP-005)

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/SubscriptionContextService.php`

**Cambios:**
1. Inyectar `?AddonSubscriptionService` (OPTIONAL-CROSSMODULE-001)
2. Añadir método `resolveActiveAddons($tenant)` — consulta `AddonSubscription` entities activas del tenant
3. Añadir método `resolveRecommendedAddons($verticalKey)` — usa `AddonCompatibilityService` para obtener add-ons recomendados no suscritos
4. Añadir método `resolveBillingSummary($tenant)` — calcula total mensual (plan + add-ons) y next invoice date desde Stripe metadata
5. Integrar en `doResolve()` para devolver secciones `addons` y `billing`

**Patrón DI:**
```yaml
# ecosistema_jaraba_core.services.yml
ecosistema_jaraba_core.subscription_context:
  class: Drupal\ecosistema_jaraba_core\Service\SubscriptionContextService
  arguments:
    - '@entity_type.manager'
    - '@?ecosistema_jaraba_core.tenant_context'
    - '@?ecosistema_jaraba_core.tenant_bridge'
    - '@?ecosistema_jaraba_core.plan_resolver'
    - '@?jaraba_addons.addon_subscription'       # ← NUEVO
    - '@?jaraba_addons.addon_compatibility'       # ← NUEVO
```

#### B.2 Crear AddonCompatibilityService (GAP-004)

**Archivo nuevo:** `web/modules/custom/jaraba_addons/src/Service/AddonCompatibilityService.php`

**Responsabilidad:** Implementar la matriz de compatibilidad del Doc 158 §4.

**Estructura de datos interna (constante de clase):**
```php
protected const COMPATIBILITY_MATRIX = [
  'jaraba_crm' => [
    'empleabilidad' => 'available',
    'emprendimiento' => 'recommended',
    'agroconecta' => 'available',
    'comercioconecta' => 'available',
    'serviciosconecta' => 'recommended',
  ],
  'jaraba_email' => [
    'empleabilidad' => 'recommended',
    'emprendimiento' => 'recommended',
    'agroconecta' => 'recommended',
    'comercioconecta' => 'recommended',
    'serviciosconecta' => 'recommended',
  ],
  // ... resto del Doc 158 §4
];
```

**Métodos:**
- `getCompatibleAddons(string $vertical): array` — devuelve add-ons disponibles con nivel
- `getRecommendedAddons(string $vertical): array` — solo los 'recommended'
- `isCompatible(string $addonCode, string $vertical): bool`
- `getRecommendationLevel(string $addonCode, string $vertical): string`

#### B.3 Crear parciales Twig (GAP-002, GAP-005)

##### `_subscription-addons.html.twig`

**Variables:**
- `active_addons`: array de add-ons activos `[{addon_id, label, price, icon_cat, icon_name}]`
- `addons_catalog_url`: URL al catálogo

**Estructura:**
```twig
{# Sección de add-ons activos #}
<div class="subscription-card__addons">
  <h3>{% trans %}Add-ons activos{% endtrans %}</h3>
  <div class="subscription-card__addons-grid">
    {% for addon in active_addons %}
      <div class="subscription-card__addon-item">
        {{ jaraba_icon(addon.icon_cat, addon.icon_name, { variant: 'duotone', ... }) }}
        <span>{{ addon.label }}</span>
        <span>{{ addon.price|number_format(0, ',', '.') }}&euro;/{% trans %}mes{% endtrans %}</span>
      </div>
    {% endfor %}
  </div>
  <a href="{{ addons_catalog_url }}">{% trans %}Explorar más add-ons{% endtrans %}</a>
</div>
```

##### `_subscription-recommended.html.twig`

**Variables:**
- `recommended_addons`: array de add-ons recomendados `[{addon_id, label, price, icon_cat, icon_name, recommendation}]`
- `vertical_label`: nombre del vertical del tenant

**Estructura:** Cards compactas con badge ⭐ "Recomendado para {vertical}", precio y botón "+ Añadir" que enlaza a `/addons/{addon_id}`.

##### `_subscription-billing-summary.html.twig`

**Variables:**
- `total_monthly`: float — total mensual (plan + add-ons)
- `next_invoice_date`: string — fecha de próxima factura
- `billing_cycle`: string — 'monthly'|'yearly'
- `financial_dashboard_url`: URL al dashboard financiero

**Estructura:** Línea horizontal con total en negrita + fecha de próxima factura + link a "Mi facturación".

#### B.4 Crear Addon entities de tipo 'bundle' (GAP-003)

**Archivo:** `web/modules/custom/jaraba_addons/jaraba_addons.install`

**Añadir en `hook_update_N()`** (siguiente número disponible):

4 bundles del Doc 158 §3.3:

| Bundle | Items | Precio/mes | Ahorro |
|--------|-------|-----------|--------|
| Marketing Starter | jaraba_email + retargeting_pixels | €35 | 15% |
| Marketing Pro | jaraba_crm + jaraba_email + jaraba_social | €59 | 20% |
| Marketing Complete | Todos principales + extensiones | €99 | 30% |
| Growth Engine | jaraba_email_plus + ab_testing + referral_program | €79 | 15% |

**Campos base nuevos en Addon entity:** `bundle_items` (JSON array de addon IDs), `bundle_discount_pct` (INT). Requieren `hook_update_N()` con `updateFieldableEntityType()` usando `getFieldStorageDefinitions()` (UPDATE-HOOK-FIELDABLE-001).

#### B.5 Modificar catálogo de add-ons (GAP-004)

**Archivo:** `web/modules/custom/jaraba_addons/src/Controller/AddonCatalogController.php`

**Cambios:**
1. Inyectar `?AddonCompatibilityService` y `?TenantContextService`
2. En `catalog()`, resolver vertical del tenant actual
3. Filtrar add-ons no compatibles (o marcarlos como "No disponible para tu vertical")
4. Añadir `recommendation_level` a cada addon en el render array
5. Ordenar: recommended primero, luego available, luego not_applicable

**Template:** Añadir badge `⭐ {% trans %}Recomendado{% endtrans %}` cuando `addon.recommendation_level == 'recommended'`.

**Criterios de aceptación Fase B:**
- [ ] "Mi suscripción" muestra add-ons activos con precio
- [ ] "Mi suscripción" muestra add-ons recomendados para el vertical
- [ ] "Mi suscripción" muestra total mensual y próxima factura
- [ ] "Mi suscripción" tiene enlace al catálogo de add-ons
- [ ] Catálogo filtra por compatibilidad vertical
- [ ] Catálogo muestra badges de recomendación
- [ ] 4 bundles creados con precios y descuentos correctos
- [ ] SCSS compilado (SCSS-COMPILE-VERIFY-001)
- [ ] Textos traducibles ({% trans %})
- [ ] Colores via var(--ej-*)

---

### Fase C: P2 — Uso Real + API + Descuentos (3-5 días)

#### C.1 Conectar barras de uso (GAP-006)

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Service/SubscriptionContextService.php`

**Cambio en `resolveUsage()`:**

```php
// ANTES: $current = 0; // Default
// DESPUÉS: Resolver uso real via TenantMeteringService
if (\Drupal::hasService('jaraba_billing.tenant_metering')) {
  $metering = \Drupal::service('jaraba_billing.tenant_metering');
  $current = $metering->getCurrentUsage($tenant->id(), $key);
}
```

**Servicios opcionales (OPTIONAL-CROSSMODULE-001):**
- `TenantMeteringService` para métricas generales
- `AIUsageLimitService` para consultas IA
- `QuotaManagerService` para páginas publicadas

#### C.2 API endpoints de suscripción (GAP-007)

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/SubscriptionApiController.php`

**Endpoints a implementar:**

| Método | Ruta | Función | Permiso |
|--------|------|---------|---------|
| GET | `/api/v1/subscription` | Suscripción actual con add-ons | `view own subscription` |
| GET | `/api/v1/subscription/addons/available` | Add-ons disponibles filtrados por vertical | `view addon catalog` |
| POST | `/api/v1/subscription/upgrade` | Upgrade de plan (redirect a checkout) | `purchase plans` |
| GET | `/api/v1/subscription/invoice/upcoming` | Preview de próxima factura | `view own subscription` |

**Routing (ecosistema_jaraba_core.routing.yml):**
```yaml
ecosistema_jaraba_core.api.subscription:
  path: '/api/v1/subscription'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\SubscriptionApiController::getCurrentSubscription'
  requirements:
    _permission: 'view own subscription'
  options:
    _csrf_request_header_token: 'TRUE'
```

#### C.3 Descuento anual diferenciado (GAP-008)

**Regla:** Planes base = 16.7% (2 meses gratis, ×10). Add-ons = 15% (×10.2).

**Archivos a verificar:**
- `addons-detail.html.twig` — toggle mensual/anual debe mostrar ahorro correcto
- `addon-detail.js` — cálculo de precio anual debe usar ×10.2 para add-ons

#### C.4 Verificar seed de add-ons de marketing (GAP-011)

**Archivo:** `web/modules/custom/jaraba_addons/jaraba_addons.install`

Verificar que los 9 add-ons del Doc 158 existen con precios correctos:

| Add-on | Precio/mes |
|--------|-----------|
| jaraba_crm | €19 |
| jaraba_email | €29 |
| jaraba_email_plus | €59 |
| jaraba_social | €25 |
| paid_ads_sync | €15 |
| retargeting_pixels | €12 |
| events_webinars | €19 |
| ab_testing | €15 |
| referral_program | €19 |

Si alguno falta, crearlo en el siguiente `hook_update_N()`.

**Criterios de aceptación Fase C:**
- [ ] Barras de uso muestran datos reales (no 0)
- [ ] 4 endpoints API funcionales con respuestas JSON
- [ ] Descuento anual correcto (16.7% planes, 15% add-ons)
- [ ] 9 add-ons de marketing con precios del Doc 158
- [ ] Tests unitarios para SubscriptionApiController
- [ ] Tests unitarios para AddonCompatibilityService

---

### Fase D: P3 — PLG Avanzado (Wizard + Daily Actions) (2-3 días)

#### D.1 SubscriptionUpgradeStep (GAP-009)

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/SubscriptionUpgradeStep.php`

**Comportamiento:**
- `getWizardId()`: `'__global__'` — se inyecta en TODOS los wizards (ZEIGARNIK-PRELOAD-001)
- `getWeight()`: 90 — aparece al final del wizard, después de los pasos funcionales
- `isComplete()`: TRUE si el usuario tiene un plan paid (starter/professional/enterprise con precio > 0)
- `getLabel()`: "Elige tu plan ideal"
- `getDescription()`: "Desbloquea todas las funcionalidades de tu vertical con un plan profesional"
- `getIcon()`: `['category' => 'ui', 'name' => 'credit-card']`
- `getRoute()`: `'ecosistema_jaraba_core.pricing.page'`
- `useSlidePanel()`: FALSE — la página de pricing es compleja y necesita full page
- `isOptional()`: TRUE — el usuario puede usar la plataforma sin pagar

**Registro en services.yml:**
```yaml
ecosistema_jaraba_core.setup_wizard.subscription_upgrade:
  class: Drupal\ecosistema_jaraba_core\SetupWizard\SubscriptionUpgradeStep
  arguments:
    - '@?ecosistema_jaraba_core.subscription_context'
    - '@current_user'
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }
```

**Efecto Zeigarnik:** Con este paso, un usuario con plan paid tendrá 3 auto-complete steps (Account, Vertical, Subscription) = ~50% de progreso inicial, amplificando el efecto motivacional de completar el wizard.

#### D.2 ReviewSubscriptionAction (GAP-010)

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/ReviewSubscriptionAction.php`

**Comportamiento:**
- `getDashboardId()`: `'__global__'` — se inyecta en TODOS los dashboards
- `getLabel()`: "Revisa tu suscripción"
- `getDescription()`: "Explora add-ons y funcionalidades premium para tu negocio"
- `getIcon()`: `['category' => 'ui', 'name' => 'tag']`
- `getColor()`: `'impulse'`
- `getRoute()`: `'jaraba_addons.catalog'`
- `useSlidePanel()`: FALSE
- `isPrimary()`: FALSE — nunca domina el dashboard
- `getContext()`: Array de condiciones para visibilidad

**Condiciones de visibilidad (`getContext()`):**
```php
public function getContext(): array {
  // Solo visible si:
  // 1. Usuario no es Enterprise (ya tiene todo)
  // 2. Uso > 60% de algún límite O
  // 3. Han pasado 30+ días desde última revisión de plan
  return [
    'exclude_tiers' => ['enterprise'],
    'usage_threshold' => 60,
    'days_since_review' => 30,
  ];
}
```

**Registro en services.yml:**
```yaml
ecosistema_jaraba_core.daily_action.review_subscription:
  class: Drupal\ecosistema_jaraba_core\DailyActions\ReviewSubscriptionAction
  arguments:
    - '@?ecosistema_jaraba_core.subscription_context'
    - '@current_user'
  tags:
    - { name: ecosistema_jaraba_core.daily_action }
```

**Criterios de aceptación Fase D:**
- [ ] Wizard step global aparece en TODOS los wizards
- [ ] Step se marca "complete" cuando usuario tiene plan paid
- [ ] Daily action aparece condicionalmente (uso>60% o 30d sin revisión)
- [ ] Daily action NO aparece para Enterprise
- [ ] Daily action enlaza a /addons

---

## 6. Tabla de Correspondencia con Especificaciones

| Especificación | Sección | Requisito | Fase | Archivos de Implementación |
|---------------|---------|-----------|------|---------------------------|
| Doc 158 §2 | Planes Base | Precios correctos por vertical | A | 10 configs YAML |
| Doc 158 §3.1-3.2 | Add-ons | 9 add-ons marketing con precios | C | jaraba_addons.install |
| Doc 158 §3.3 | Bundles | 4 bundles con descuento | B | jaraba_addons.install, Addon entity |
| Doc 158 §4 | Compatibilidad | Matriz vertical × add-on | B | AddonCompatibilityService, AddonCatalogController |
| Doc 158 §5.4 | API | 6 endpoints REST | C | SubscriptionApiController |
| Doc 158 §7 | UI Mi Suscripción | Add-ons en perfil | B | SubscriptionContextService, 3 parciales twig |
| Doc 158 §9 | Descuentos | Anual diferenciado | C | addon-detail.js, config YAML |
| Doc 111 | Usage-Based | Barras de uso reales | C | SubscriptionContextService::resolveUsage() |
| PLG-UPGRADE-UI-001 | PLG | Señales de upgrade contextuales | B+D | Subscription card + wizard step + daily action |
| SETUP-WIZARD-DAILY-001 | Wizard | Paso suscripción global | D | SubscriptionUpgradeStep |
| SETUP-WIZARD-DAILY-001 | Daily Actions | Acción PLG condicional | D | ReviewSubscriptionAction |

---

## 7. Cumplimiento de Directrices

| Directriz | Cómo se cumple en cada fase |
|-----------|----------------------------|
| **NO-HARDCODE-PRICE-001** | Precios SIEMPRE desde SaasPlan entity o MetaSitePricingService. NUNCA en templates. |
| **CSS-VAR-ALL-COLORS-001** | Todos los SCSS nuevos usan `var(--ej-*, fallback)`. Sin hex hardcoded. |
| **SCSS-001** | Cada parcial SCSS comienza con `@use '../variables' as *;`. Dart Sass moderno. |
| **SCSS-COMPILE-VERIFY-001** | Tras cada edición SCSS: `lando npm run build`, verificar timestamp, `lando drush cr`. |
| **SCSS-COMPILETIME-001** | Variables en funciones Sass = hex estático. Runtime alpha = `color-mix()`. |
| **SCSS-COLORMIX-001** | CERO `rgba()`. Solo `color-mix(in srgb, var(--ej-*) N%, transparent)`. |
| **ICON-CONVENTION-001** | `jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo' })`. |
| **ICON-DUOTONE-001** | Variante duotone por defecto en todos los parciales premium. |
| **TWIG-INCLUDE-ONLY-001** | `{% include '...' with { var1, var2 } only %}` en todos los includes. |
| **TWIG-ENTITY-METHOD-001** | Getters (`entity.label()`, `entity.id()`), NUNCA property access. |
| **ZERO-REGION-001** | Controllers devuelven `['#type' => 'markup', '#markup' => '']`. Variables en preprocess. |
| **ROUTE-LANGPREFIX-001** | `Url::fromRoute()` en PHP. `{{ path() }}` o `Drupal.url()` en Twig/JS. |
| **i18n {% trans %}** | Bloques `{% trans %}...{% endtrans %}` con ortografía española correcta (ORTOGRAFIA-TRANS-001). |
| **SSOT-THEME-001** | Valores configurables desde Appearance > Ecosistema Jaraba Theme. Sin código para cambiar contenido. |
| **SLIDE-PANEL-RENDER-001** | Acciones crear/editar abren en slide-panel. `renderPlain()`, `#action` explícito. |
| **PREMIUM-FORMS-PATTERN-001** | Formularios de Addon extienden PremiumEntityFormBase. |
| **TENANT-001** | Queries filtran por tenant. AddonSubscription verifica tenant ownership. |
| **OPTIONAL-CROSSMODULE-001** | Dependencias cross-módulo con `@?`. Constructors aceptan `?Service = NULL`. |
| **UPDATE-HOOK-REQUIRED-001** | Nuevos campos (bundle_items, compatible_verticals, etc.) con `hook_update_N()`. |
| **UPDATE-HOOK-FIELDABLE-001** | `updateFieldableEntityType()` usa `getFieldStorageDefinitions()`, NO `getBaseFieldDefinitions()`. |
| **UPDATE-HOOK-CATCH-001** | `catch(\Throwable)` en hooks, NUNCA `catch(\Exception)`. |
| **CONTROLLER-READONLY-001** | No `protected readonly` en propiedades heredadas de ControllerBase. |
| **ENTITY-FK-001** | FK cross-módulo como integer. tenant_id como entity_reference. |
| **Mobile-first** | Layout responsive, mobile primero. |
| **Body classes** | `hook_preprocess_html()`, NUNCA `attributes.addClass()` en template. |

---

## 8. Testing y Verificación

### 8.1 Tests Unitarios

| Test | Módulo | Verifica |
|------|--------|---------|
| `AddonCompatibilityServiceTest` | jaraba_addons | Matriz compatibilidad correcta por vertical |
| `SubscriptionContextServiceTest` (ampliar) | ecosistema_jaraba_core | Resolución de add-ons activos + billing |
| `PricingCascadeContractTest` (ampliar) | ecosistema_jaraba_core | Precios Doc 158 en configs |
| `SubscriptionApiControllerTest` | ecosistema_jaraba_core | 4 endpoints REST correctos |

### 8.2 Tests Kernel

| Test | Módulo | Verifica |
|------|--------|---------|
| `PlanConfigContractTest` (ampliar) | ecosistema_jaraba_core | 15 planes con precios Doc 158 |
| `AddonBundleIntegrationTest` | jaraba_addons | 4 bundles con items y descuentos correctos |

### 8.3 Verificación Visual (RUNTIME-VERIFY-001)

```bash
# 1. CSS compilado
lando npm run build --prefix web/themes/custom/ecosistema_jaraba_theme/

# 2. Rutas accesibles
lando drush router:list | grep -E "planes|addons|subscription"

# 3. Cache limpio
lando drush cr

# 4. Verificar en navegador:
# - https://jaraba-saas.lndo.site/planes
# - https://jaraba-saas.lndo.site/planes/empleabilidad
# - https://jaraba-saas.lndo.site/addons
# - https://jaraba-saas.lndo.site/user/{uid}/edit (Mi suscripción)
```

---

## 9. Checklist IMPLEMENTATION-CHECKLIST-001

### Completitud
- [ ] Servicios registrados en services.yml Y consumidos por controllers/templates
- [ ] Rutas en routing.yml apuntan a clases/métodos existentes
- [ ] Nuevos campos de entidad con hook_update_N() + EntityDefinitionUpdateManager
- [ ] Templates incluidos con `{% include ... only %}`
- [ ] SCSS compilado, library registrada
- [ ] drupalSettings inyectado donde se necesite JS interactivo

### Integridad
- [ ] Tests unitarios para AddonCompatibilityService
- [ ] Tests unitarios para SubscriptionApiController
- [ ] Tests ampliar para SubscriptionContextService
- [ ] hook_update_N() para nuevos campos de Addon entity
- [ ] Config export si nuevas config entities

### Consistencia
- [ ] PREMIUM-FORMS-PATTERN-001 en formularios
- [ ] CSS-VAR-ALL-COLORS-001 en SCSS
- [ ] TENANT-001 en queries
- [ ] NO-HARDCODE-PRICE-001 en templates

### Pipeline E2E (PIPELINE-E2E-001)
- [ ] L1: SubscriptionContextService inyectado en controllers
- [ ] L2: Controllers pasan datos al render array (#subscription_context)
- [ ] L3: hook_theme() declara variables en 'variables' array
- [ ] L4: Templates incluyen parciales con textos traducidos y `only` keyword

---

## 10. Impacto de Negocio

### 10.1 Revenue Impact

| Corrección | Impacto Estimado en MRR |
|------------|------------------------|
| Alinear precios AgroConecta (€29→€49/€59→€129/€149→€249) | +120% ARPU en vertical |
| Alinear precios ComercioConecta | +60% ARPU en vertical |
| Alinear precios Emprendimiento | +35% ARPU en vertical |
| Bundles de marketing | +15-30% addon attach rate |
| Barras de uso reales | +20-40% upgrade rate orgánico (estimado) |
| PLG signals (wizard + daily action) | +10-15% conversion free→paid |

### 10.2 User Experience Impact

| Mejora | Beneficio |
|--------|-----------|
| Add-ons en perfil | Descubrimiento orgánico sin salir del flujo |
| Badges de recomendación | Orientación contextual reduce decision fatigue |
| Total mensual visible | Transparencia → confianza → retención |
| Wizard step suscripción | Reduce time-to-value para usuarios free |
| Daily action PLG | Recordatorio gentil en contexto de trabajo |

### 10.3 Métricas a Monitorizar (Doc 158 §10)

| Métrica | Baseline actual | Objetivo post-implementación |
|---------|----------------|------------------------------|
| ARPU | ¿? | +40% (corrección precios) |
| Addon Attach Rate | 0% (addons desconectados) | >15% en 90 días |
| Avg Addons per Tenant | 0 | >0.5 |
| Upgrade Rate (90d) | ¿? | >12% |
| Net Revenue Retention | ¿? | >105% |

---

*Plan de implementación generado como parte de la auditoría integral AUDIT-PRICING-PLG-001 para Jaraba Impact Platform. Alineado con Doc 158, directrices del proyecto v140.0.0, y estudio de mercado competitivo (marzo 2026).*
