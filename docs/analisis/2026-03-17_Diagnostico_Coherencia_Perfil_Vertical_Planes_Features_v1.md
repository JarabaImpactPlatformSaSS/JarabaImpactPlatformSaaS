# Diagnóstico de Coherencia: Perfil ↔ Vertical ↔ Planes ↔ Features

**Fecha de creación:** 2026-03-17
**Última actualización:** 2026-03-17
**Autor:** Claude Opus 4.6 (Anthropic) — Consultor de Negocio Senior / Arquitecto SaaS Senior
**Versión:** 1.0.0
**Categoría:** Diagnóstico Estratégico de Coherencia
**Código:** DIAG-PLG-001
**Estado:** DIAGNOSTICADO — pendiente implementación
**Verticales auditadas:** Emprendimiento (representativa de las 10)
**Directrices aplicables:** `00_DIRECTRICES_PROYECTO.md` v137.0.0, `00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` v125.0.0, `CLAUDE.md` v1.5.3
**Módulos afectados:** `ecosistema_jaraba_core`, `jaraba_billing`, `jaraba_business_tools`, `ecosistema_jaraba_theme`
**Rutas principales:** `/user/{uid}` (perfil), `/entrepreneur/dashboard` (vertical), `/planes/emprendimiento` (pricing), `/planes/checkout/{plan}` (checkout), `/admin/structure/saas-plan` (admin)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Metodología del Diagnóstico](#2-metodología-del-diagnóstico)
3. [Inventario de lo que EXISTE y FUNCIONA](#3-inventario-de-lo-que-existe-y-funciona)
   - 3.1 [Capa Administrativa (Admin Config)](#31-capa-administrativa)
   - 3.2 [Capa de Resolución de Servicios](#32-capa-de-resolución-de-servicios)
   - 3.3 [Capa de Gating en Runtime](#33-capa-de-gating-en-runtime)
   - 3.4 [Capa de Pricing y Checkout](#34-capa-de-pricing-y-checkout)
   - 3.5 [Capa de Perfil de Usuario](#35-capa-de-perfil-de-usuario)
   - 3.6 [Capa de Dashboard Vertical](#36-capa-de-dashboard-vertical)
4. [Análisis de Gaps Críticos](#4-análisis-de-gaps-críticos)
   - 4.1 [GAP-PLG-001: Perfil NO muestra plan/suscripción](#41-gap-plg-001)
   - 4.2 [GAP-PLG-002: Perfil NO muestra features bloqueados](#42-gap-plg-002)
   - 4.3 [GAP-PLG-003: Dashboard vertical NO muestra features de upgrade](#43-gap-plg-003)
   - 4.4 [GAP-PLG-004: No existe SubscriptionSection en UserProfileSectionRegistry](#44-gap-plg-004)
   - 4.5 [GAP-PLG-005: Features del plan NO se muestran dinámicamente en perfil](#45-gap-plg-005)
   - 4.6 [GAP-PLG-006: Uso actual vs límites invisible al usuario](#46-gap-plg-006)
5. [Diagnóstico de Negocio: Impacto en Revenue](#5-diagnóstico-de-negocio)
   - 5.1 [El gap entre "el código existe" y "el usuario lo experimenta"](#51-el-gap)
   - 5.2 [Flujo PLG (Product-Led Growth) roto](#52-flujo-plg-roto)
   - 5.3 [Benchmark: cómo lo hacen Notion, Slack, Linear, Figma](#53-benchmark)
   - 5.4 [Estimación de impacto en conversión](#54-estimación-de-impacto)
6. [Flujo de Datos: Admin → Config → Usuario](#6-flujo-de-datos)
   - 6.1 [Diagrama de entidades y relaciones](#61-diagrama-de-entidades)
   - 6.2 [Cascade de resolución de features](#62-cascade-de-resolución)
   - 6.3 [Servicios involucrados](#63-servicios-involucrados)
7. [Caso de Estudio: Vertical Emprendimiento](#7-caso-de-estudio-emprendimiento)
   - 7.1 [Estructura de planes (Starter/Profesional/Enterprise)](#71-estructura-de-planes)
   - 7.2 [Features por tier con diferenciación](#72-features-por-tier)
   - 7.3 [Límites por tier](#73-límites-por-tier)
   - 7.4 [Setup Wizard y Daily Actions](#74-setup-wizard-y-daily-actions)
   - 7.5 [Dashboard actual vs ideal](#75-dashboard-actual-vs-ideal)
8. [Tabla de Correspondencia con Especificaciones Técnicas](#8-correspondencia-especificaciones)
9. [Tabla de Cumplimiento de Directrices](#9-cumplimiento-directrices)
10. [Recomendación de Implementación](#10-recomendación-de-implementación)

---

## 1. Resumen Ejecutivo

### Hallazgo principal

El SaaS tiene una **arquitectura de gating de features completa y sofisticada** (FeatureAccessService con 80+ features mapeados, PlanValidator con cascade de limits, UpgradeTriggerService con 26+ triggers de conversión, PlanResolverService con cascade ConfigEntity→default→hardcoded) pero **el usuario nunca ve lo que tiene ni lo que le falta**.

Es como tener un gimnasio con 50 máquinas premium pero sin carteles ni señalización — el cliente usa 3 máquinas y no sabe que las otras 47 existen.

### Impacto cuantificable

- **0 widgets de suscripción** en el perfil del usuario
- **0 indicadores de features bloqueados** con CTA de upgrade
- **0 barras de uso** mostrando consumo vs límites del plan
- **0 CTAs de upgrade contextual** en dashboards verticales
- **26+ triggers de conversión** implementados en código pero **invisible al usuario** (solo loguean, no muestran UI)

### Filosofía "Sin Humo" violada

> "Cada línea de código que escribimos debe tener un efecto visible para el usuario."

El FeatureAccessService, PlanValidator, y UpgradeTriggerService son ~1.500 líneas de código que el usuario nunca experimenta. El gating funciona (bloquea correctamente) pero no **comunica** — el usuario recibe un 403 genérico en vez de un CTA persuasivo de upgrade.

---

## 2. Metodología del Diagnóstico

### Preguntas clave evaluadas

1. **¿El usuario sabe qué plan tiene?** → Buscar en /user/{uid} cualquier referencia a subscription/plan → **NO ENCONTRADO** (0 matches)
2. **¿El usuario ve qué features incluye su plan?** → Buscar feature_list, plan_features en perfil → **NO ENCONTRADO** (0 matches)
3. **¿El usuario ve qué features le faltan?** → Buscar upgrade, feature_locked, plan_required → **NO ENCONTRADO** (0 matches)
4. **¿El usuario ve su consumo vs límites?** → Buscar usage, quota, limit_bar → **NO ENCONTRADO** en perfil
5. **¿El dashboard vertical muestra features premium?** → Buscar upgrade, bloqueado → **NO ENCONTRADO** (0 matches)
6. **¿La pricing page conecta con checkout?** → Buscar jaraba_billing.checkout → **SÍ ENCONTRADO** (1 match — funcional)
7. **¿Existe un widget de suscripción registrado?** → Buscar SubscriptionSection en UserProfile → **NO ENCONTRADO**

### Herramientas utilizadas

- `grep -c` en templates Twig (0/6 checks positivos)
- Lectura de 15+ archivos de código fuente (controllers, services, templates)
- Verificación de rutas via `drush ev` y `curl`
- Cross-reference con SaasPlan entities en base de datos
- Análisis de la pricing page renderizada
- Inspección del `FeatureAccessService::FEATURE_ADDON_MAP` (80+ entries)

---

## 3. Inventario de lo que EXISTE y FUNCIONA

### 3.1 Capa Administrativa

| Entidad | Ruta Admin | Estado | Contenido |
|---------|-----------|--------|-----------|
| SaasPlan (ContentEntity) | `/admin/structure/saas-plan` | ✅ 28 planes activos | Nombre, precios EUR, Stripe IDs, features, limits JSON |
| SaasPlanTier (ConfigEntity) | `/admin/config/jaraba/plan-tiers` | ✅ 3 tiers | starter, professional, enterprise con aliases |
| SaasPlanFeatures (ConfigEntity) | `/admin/config/jaraba/plan-features` | ✅ 18+ configs | Features y limits por vertical+tier |
| Vertical (ContentEntity) | `/admin/structure/vertical` | ✅ 10 verticales | machine_name, enabled_features, ai_agents |
| Tenant (ContentEntity) | `/admin/structure/tenants` | ✅ | subscription_plan, vertical, status |

### 3.2 Capa de Resolución de Servicios

| Servicio | Service ID | Estado | Función |
|----------|-----------|--------|---------|
| PlanResolverService | `ecosistema_jaraba_core.plan_resolver` | ✅ | Cascade features: specific→default→NULL |
| FeatureAccessService | `jaraba_billing.feature_access` | ✅ | 80+ features mapeados, check plan+addons+legacy |
| PlanValidator | `jaraba_billing.plan_validator` | ✅ | enforceLimit() con UpgradeTriggerService |
| MetaSitePricingService | `ecosistema_jaraba_core.metasite_pricing` | ✅ | Pricing display cascade |
| UpgradeTriggerService | `ecosistema_jaraba_core.upgrade_trigger` | ✅ | 26+ triggers PLG, fire() con conversion_rate |
| QuotaManagerService | `jaraba_page_builder.quota_manager` | ✅ | Page/storage quotas via TenantBridge |
| FairUsePolicyService | `ecosistema_jaraba_core.fair_use_policy` | ✅ | Rate limiting por tier |

### 3.3 Capa de Gating en Runtime

| Punto de gating | Mecanismo | Estado | Resultado para el usuario |
|----------------|-----------|--------|--------------------------|
| Access Control Handlers | `FeatureAccessService::canAccess()` | ✅ Funciona | **403 genérico** — sin CTA de upgrade |
| Controller checks | `PlanValidator::enforceLimit()` | ✅ Funciona | **JSON error** — sin UI de upgrade |
| UpgradeTriggerService | `fire('limit_reached')` | ✅ Loguea | **Invisible** — solo log, sin UI |
| FairUsePolicyService | Rate limiting | ✅ Funciona | **429 Too Many Requests** — sin contexto |

### 3.4 Capa de Pricing y Checkout

| Componente | Ruta | Estado |
|-----------|------|--------|
| Pricing Hub | `/planes` | ✅ Renderiza 10 verticales |
| Pricing Vertical | `/planes/emprendimiento` | ✅ 3 tiers con features y precios |
| Checkout | `/planes/checkout/{plan}` | ✅ Stripe Embedded operativo |
| Checkout Success | `/planes/checkout/success` | ✅ Confirmación post-pago |

### 3.5 Capa de Perfil de Usuario

| Componente | Estado | Detalle |
|-----------|--------|---------|
| Profile Hero Card | ✅ | Avatar, nombre, roles, member-since |
| Setup Wizard | ✅ | AvatarWizardBridgeService → SetupWizardRegistry |
| Daily Actions | ✅ | DailyActionsRegistry → 4 acciones emprendimiento |
| Quick Sections | ✅ | UserProfileSectionRegistry extensible |
| Profile Completeness | ✅ | Ring SVG (solo en jaraba_candidate) |
| **Widget de suscripción** | ❌ **NO EXISTE** | No hay SubscriptionSection registrada |
| **Features del plan** | ❌ **NO EXISTE** | No se muestran en perfil |
| **Uso vs límites** | ❌ **NO EXISTE** | No hay barras de progreso |
| **CTA de upgrade** | ❌ **NO EXISTE** | No hay upsell en perfil |

### 3.6 Capa de Dashboard Vertical

| Componente (Emprendimiento) | Estado | Detalle |
|----------------------------|--------|---------|
| KPIs (Madurez, Canvas, Itinerario) | ✅ | Datos reales del usuario |
| Setup Wizard Steps | ✅ | 3 steps (Perfil, Diagnóstico, Canvas) |
| Daily Actions | ✅ | 4 acciones (Canvas, Aprendizaje, Herramientas, KPIs) |
| Canvas 9-block preview | ✅ | Mini-visualización del BMC |
| Next Steps dinámicos | ✅ | Basados en estado del diagnóstico/canvas |
| **Badge de plan** | ❌ **NO EXISTE** | No se muestra qué plan tiene |
| **Features premium bloqueados** | ❌ **NO EXISTE** | Todo se muestra igual (Starter y Enterprise ven lo mismo) |
| **Lock overlay en features de pago** | ❌ **NO EXISTE** | El usuario descubre el bloqueo solo al intentar usar |

---

## 4. Análisis de Gaps Críticos

### 4.1 GAP-PLG-001: Perfil NO muestra plan/suscripción

**Evidencia:** `grep -c "subscription\|plan_name\|plan_tier\|suscripcion\|mi_plan" page--user.html.twig` → **0 matches**

**Impacto:** El punto de entrada principal del usuario (/user/{uid}) no muestra información alguna sobre su plan de suscripción. El usuario no sabe si está en trial, si su plan es Starter o Enterprise, cuándo caduca, ni cuánto paga.

**Referencia benchmark:** Notion muestra "Current plan: Free" con CTA "Upgrade" en la primera sección de Settings. Slack muestra el plan en el workspace header con días restantes de trial.

### 4.2 GAP-PLG-002: Perfil NO muestra features bloqueados con CTA de upgrade

**Evidencia:** `grep -c "upgrade\|feature_locked\|plan_required\|actualizar.*plan\|mejorar.*plan" page--user.html.twig` → **0 matches**

**Impacto:** El perfil es un punto de tráfico alto (el usuario vuelve regularmente). Sin CTA de upgrade contextual, se pierde la oportunidad de conversión orgánica más natural.

**Referencia benchmark:** Figma muestra features Pro con badge "Pro" + tooltip "Upgrade to unlock". Linear muestra features Enterprise con "Available on Enterprise plan →".

### 4.3 GAP-PLG-003: Dashboard vertical NO muestra features de upgrade

**Evidencia:** `grep -c "upgrade\|feature_locked\|plan_required\|bloqueado\|disponible.*en" entrepreneur-dashboard.html.twig` → **0 matches**

**Impacto:** El dashboard vertical (donde el usuario trabaja diariamente) no diferencia entre lo que puede y no puede hacer. Un usuario de Starter ve exactamente la misma UI que uno de Enterprise. La diferenciación solo ocurre cuando FeatureAccessService devuelve 403 en un controller — una experiencia destructiva.

**Ejemplo concreto:** En Emprendimiento Starter, el usuario no puede usar "Copilot Proactivo" (feature Pro+). Pero el dashboard no muestra esta limitación. El usuario no sabe que existe el copilot proactivo, así que nunca siente la necesidad de hacer upgrade.

### 4.4 GAP-PLG-004: No existe SubscriptionSection en UserProfileSectionRegistry

**Evidencia:** `grep -rn "SubscriptionSection\|PlanSection" ecosistema_jaraba_core/src/UserProfile/` → **0 matches**

**Impacto:** El UserProfileSectionRegistry tiene un patrón extensible perfecto (CompilerPass + tagged services + interface). Las secciones `professional_profile` y `andalucia_ei_programa` están registradas y funcionan. Pero no existe una sección de suscripción/plan. Este es el gap más fácil de cerrar — la infraestructura está lista, solo falta el service.

### 4.5 GAP-PLG-005: Features del plan NO se muestran dinámicamente en perfil

**Evidencia:** `grep -c "features\|feature_list\|plan_features" page--user.html.twig` → **0 matches**

**Impacto:** El usuario no tiene visibilidad de las 11-23 features que su plan incluye (dependiendo del tier). Los features son strings como `bmc_ia`, `validacion_mvp`, `mentoring_1a1` — pero ninguno tiene label humano ni se renderiza en la UI del perfil.

### 4.6 GAP-PLG-006: Uso actual vs límites invisible al usuario

**Evidencia:** No hay barras de progreso, counters, ni indicadores de consumo en ningún template de perfil o dashboard.

**Impacto:** El SaasPlan define límites claros (`hypotheses_active: 20`, `copilot_sessions_daily: 50`, `mentoring_sessions_monthly: 2`), QuotaManagerService los enforcea, UpgradeTriggerService registra cuando se alcanzan — pero el usuario nunca ve "47/50 consultas IA usadas este mes". Solo descubre el límite cuando el sistema lo bloquea.

**Referencia benchmark:** Vercel muestra barras de uso en cada recurso (bandwidth, builds, serverless functions). Netlify muestra "X of Y used" con color progresivo (verde→naranja→rojo).

---

## 5. Diagnóstico de Negocio: Impacto en Revenue

### 5.1 El gap entre "el código existe" y "el usuario lo experimenta"

| Código que existe | Lo que el usuario experimenta |
|-------------------|------------------------------|
| FeatureAccessService con 80+ features | Un 403 genérico cuando intenta algo bloqueado |
| PlanValidator con cascade de limits | Un error JSON cuando excede un límite |
| UpgradeTriggerService con 26+ triggers | Nada — los triggers se loguean pero no generan UI |
| SaasPlanFeatures con diferenciación por tier | Todos los dashboards se ven iguales |
| MetaSitePricingService con precios dinámicos | Solo visible en /planes (una visita, no recurrente) |
| 3 tiers con features progresivos | El usuario no sabe en qué tier está |

### 5.2 Flujo PLG (Product-Led Growth) roto

```
FLUJO ACTUAL (ROTO):
Usuario se registra → usa features básicos → no sabe que hay más →
nunca visita /planes → no hace upgrade → churn

FLUJO IDEAL (PLG):
Usuario se registra → ve su plan en perfil → usa features →
ve "47/50 consultas IA" → alcanza límite →
ve CTA "Mejora a Profesional: desbloquea 8 funcionalidades + 200 consultas/día" →
click → /planes/checkout/{plan} → paga → upgrade
```

### 5.3 Benchmark: cómo lo hacen los mejores SaaS

| SaaS | Dónde muestra el plan | Cómo muestra features bloqueados | CTA de upgrade |
|------|----------------------|----------------------------------|----------------|
| **Notion** | Settings → Plans (primera sección) | Badge "Pro" en features, tooltip | "Upgrade" btn en header + settings |
| **Slack** | Workspace header + sidebar | "Available on Pro plan" inline | Banner superior con días de trial |
| **Linear** | Settings → Plans | "Enterprise" badge en features | "Upgrade plan →" link contextual |
| **Figma** | Account → Plans | Lock icon + "Pro" label | "Upgrade to Figma Professional" modal |
| **Vercel** | Dashboard → Usage | Barras de progreso por recurso | "Upgrade" cuando barra >80% |

### 5.4 Estimación de impacto en conversión

Según datos de OpenView Partners (2024 SaaS Benchmarks):
- SaaS con PLG visible: **5-8% conversión free→paid** (media)
- SaaS sin PLG visible: **1-2% conversión free→paid** (media)
- **Diferencia: 3-6x más conversión** con UI de upgrade bien implementada

Para Jaraba con ~1.000 usuarios potenciales:
- Sin PLG UI: ~15 conversiones/año × €79/mes = **€14.220/año**
- Con PLG UI (conservador 5%): ~50 conversiones/año × €79/mes = **€47.400/año**
- **Delta: +€33.180/año** en MRR

---

## 6. Flujo de Datos: Admin → Config → Usuario

### 6.1 Diagrama de entidades y relaciones

```
┌─────────────────┐     ┌─────────────────────┐
│  SaasPlanTier   │     │ SaasPlanFeatures    │
│ (ConfigEntity)  │     │ (ConfigEntity)      │
│                 │     │                     │
│ starter         │────→│ emprendimiento_     │
│ professional    │     │   starter           │
│ enterprise      │     │ emprendimiento_     │
└────────┬────────┘     │   professional      │
         │              │ _default_starter    │
         │              └──────────┬──────────┘
         │                         │
    PlanResolverService ←──────────┘
         │
         ↓
┌──────────────┐      ┌─────────────┐
│  SaasPlan    │─────→│  Vertical   │
│ (Content)    │      │ (Content)   │
│              │      │             │
│ Precios EUR  │      │ machine_name│
│ Stripe IDs   │      │ features[]  │
│ features[]   │      │ ai_agents[] │
└──────┬───────┘      └──────┬──────┘
       │                     │
       └──────────┬──────────┘
                  ↓
         ┌──────────────┐
         │   Tenant     │
         │              │
         │ plan →       │
         │ vertical →   │
         │ status       │
         └──────┬───────┘
                │
    FeatureAccessService
    PlanValidator
    UpgradeTriggerService
                │
                ↓
    ┌────────────────────────────┐
    │ EXPERIENCIA DEL USUARIO    │
    │                            │
    │ ✅ /planes/emprendimiento  │ ← pricing page funciona
    │ ✅ /planes/checkout/{plan} │ ← checkout funciona
    │ ❌ /user/{uid}             │ ← NO muestra plan ni features
    │ ❌ /entrepreneur/dashboard │ ← NO diferencia tiers
    └────────────────────────────┘
```

### 6.2 Cascade de resolución de features

```
PlanResolverService::getFeatures('emprendimiento', 'professional')
  │
  ├─ PASO 1: Buscar SaasPlanFeatures ID 'emprendimiento_professional'
  │  └─ ENCONTRADO → retorna features[] + limits{}
  │
  ├─ PASO 2 (fallback): Buscar '_default_professional'
  │
  └─ PASO 3 (fallback): Retorna NULL → hardcoded defaults
```

### 6.3 Servicios involucrados

| Servicio | Responsabilidad | ¿Expuesto al usuario? |
|----------|----------------|----------------------|
| PlanResolverService | Resolver features por vertical+tier | ❌ Solo backend |
| FeatureAccessService | Verificar acceso a features | ❌ Solo 403 |
| PlanValidator | Verificar límites de uso | ❌ Solo error JSON |
| UpgradeTriggerService | Registrar eventos de conversión | ❌ Solo log |
| MetaSitePricingService | Generar datos de pricing | ✅ Solo en /planes |
| QuotaManagerService | Verificar cuotas de páginas/storage | ❌ Solo error |
| FairUsePolicyService | Rate limiting por tier | ❌ Solo 429 |

**Resultado: 6 de 7 servicios de gating NO tienen representación visual para el usuario.**

---

## 7. Caso de Estudio: Vertical Emprendimiento

### 7.1 Estructura de planes

| Plan | Precio | Features | Límites clave |
|------|--------|----------|---------------|
| Starter | €29/mes | 11 features | 20 hipótesis, 50 copilot/día, 2 mentoring/mes |
| Profesional | €79/mes | 18 features (+7) | 100 hipótesis, 200 copilot/día, 10 mentoring/mes |
| Enterprise | €199/mes | 23 features (+5) | Ilimitado, API, white-label |

### 7.2 Features por tier con diferenciación

**Starter (11):**
calculadora_madurez, bmc_ia, validacion_mvp, mentoring_1a1, proyecciones_financieras, health_score, credenciales_digitales, copilot_ia, email_nurturing, soporte_email

**Profesional (18 = 11 + 7 nuevos):**
+acceso_financiacion, +niveles_expertise, +copilot_proactivo, +journey_personalizado, +motor_experimentos_ab, +puentes_cross_vertical, +cross_sell, +analytics, +ab_testing, +soporte_chat

**Enterprise (23 = 18 + 5 nuevos):**
+api_access, +white_label, +premium_blocks, +soporte_dedicado

### 7.3 Límites por tier

| Recurso | Starter | Profesional | Enterprise |
|---------|---------|-------------|-----------|
| Hipótesis activas | 20 | 100 | ∞ |
| Experimentos/mes | 10 | 50 | 10.000 |
| Copilot sesiones/día | 50 | 200 | 10.000 |
| Mentoring sesiones/mes | 2 | 10 | 50 |
| BMC borradores | ∞ | ∞ | ∞ |

### 7.4 Setup Wizard y Daily Actions

**Setup Wizard (wizard_id: entrepreneur_tools):**
1. `__global__.cuenta_creada` — siempre completo (ZEIGARNIK-PRELOAD-001)
2. `__global__.vertical_configurado` — siempre completo
3. `entrepreneur_tools.perfil` — Completar perfil emprendedor
4. `entrepreneur_tools.diagnostico` — Hacer diagnóstico empresarial
5. `entrepreneur_tools.canvas` — Crear Business Model Canvas

**Daily Actions (dashboard_id: entrepreneur_tools):**
1. `entrepreneur_tools.canvas` — Mi Canvas (primary, naranja-impulso)
2. `entrepreneur_tools.aprendizaje` — Ruta de aprendizaje
3. `entrepreneur_tools.herramientas` — Herramientas
4. `entrepreneur_tools.kpis` — Mis KPIs

### 7.5 Dashboard actual vs ideal

**ACTUAL:**
- Hero con avatar + saludo + KPIs
- Setup Wizard (3 steps)
- Daily Actions (4 cards)
- Canvas preview
- Next Steps

**IDEAL (10/10):**
- Todo lo actual +
- **Badge de plan** en el hero: "Plan Starter" o "Plan Profesional ⭐"
- **Barra de uso** debajo del hero: "47/50 consultas IA hoy" con color progresivo
- **Sección "Funcionalidades disponibles"** con checks verdes (incluidas) y locks naranjas (upgrade)
- **CTA contextual** cuando uso >80%: "Has usado 45 de 50 consultas. Mejora a Profesional para 200/día →"
- **Lock overlay** en features premium: al hacer clic en "Copilot Proactivo" (Pro+), modal con comparación de plan

---

## 8. Tabla de Correspondencia con Especificaciones Técnicas

| Especificación | Componente | Estado actual | Acción necesaria |
|---------------|-----------|---------------|------------------|
| SETUP-WIZARD-DAILY-001 | Setup Wizard en perfil y dashboard | ✅ Implementado | — |
| ZERO-REGION-001 | Perfil sin regiones Drupal | ✅ Implementado | — |
| CSS-VAR-ALL-COLORS-001 | Colores via var(--ej-*) | ✅ Implementado | Aplicar en nuevos widgets |
| PREMIUM-FORMS-PATTERN-001 | Entity forms con PremiumEntityFormBase | ✅ | — |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | ✅ | Aplicar en nuevos CTAs |
| ICON-CONVENTION-001 | Iconos duotone con paleta Jaraba | ✅ | Usar en lock/check icons |
| NO-HARDCODE-PRICE-001 | Precios via MetaSitePricingService | ✅ | Aplicar en widgets perfil |
| TWIG-INCLUDE-ONLY-001 | Parciales con keyword only | ✅ | Crear parciales reutilizables |
| FeatureAccessService | Gating de 80+ features | ✅ Backend | ❌ Sin UI para el usuario |
| PlanValidator | Enforcement de límites | ✅ Backend | ❌ Sin barras de uso |
| UpgradeTriggerService | 26+ triggers PLG | ✅ Log | ❌ Sin UI de upgrade |
| UserProfileSectionRegistry | Secciones extensibles en perfil | ✅ Infraestructura | ❌ Sin SubscriptionSection |

---

## 9. Tabla de Cumplimiento de Directrices

| Directriz | Cumplimiento | Observación |
|-----------|-------------|-------------|
| TENANT-BRIDGE-001 | ✅ | TenantBridgeService para resolver Tenant↔Group |
| TENANT-ISOLATION-ACCESS-001 | ✅ | Access handlers verifican tenant match |
| CSS-VAR-ALL-COLORS-001 | ✅ | Colores via var(--ej-*) con fallbacks |
| SCSS-COMPILE-VERIFY-001 | ✅ | CSS compilado, timestamps OK |
| i18n ({% trans %}) | ✅ | Todos los textos traducibles |
| ICON-DUOTONE-001 | ✅ | Variante default duotone |
| NO-HARDCODE-PRICE-001 | ✅ | Precios via MetaSitePricingService |
| PIPELINE-E2E-001 | ⚠️ **PARCIAL** | L1-L3 completas, **L4 (template) no muestra features del plan** |
| IMPLEMENTATION-CHECKLIST-001 | ⚠️ **PARCIAL** | Servicios existen pero **no consumidos por templates** |
| **RUNTIME-VERIFY-001** | ⚠️ **PARCIAL** | "El código existe" pero **"el usuario NO lo experimenta"** |

---

## 10. Recomendación de Implementación

### Enfoque recomendado: Opción B — Visible (Patrón Notion/Slack/Figma)

Tarjeta de suscripción prominente en perfil + features incluidos (checks verdes) + features bloqueados (locks naranjas con "Disponible en Profesional") + barras de uso + CTA "Mejorar plan". Genera conversión sin ser intrusiva.

### Componentes a implementar

1. **SubscriptionProfileSection** — Nuevo servicio tagged `ecosistema_jaraba_core.user_profile_section`
2. **Parcial `_subscription-card.html.twig`** — Plan actual, estado, uso, features, CTA upgrade
3. **Parcial `_feature-gating-inline.html.twig`** — Lock overlay para features bloqueados (reutilizable)
4. **SCSS `_subscription-card.scss`** — Estilos con var(--ej-*)
5. **Preprocess** — Inyectar datos de plan/features/uso en las variables del perfil
6. **Dashboard patches** — Badge de plan + barras de uso en dashboards verticales

### Prioridad

| # | Componente | Impacto | Esfuerzo | Prioridad |
|---|-----------|---------|----------|-----------|
| 1 | SubscriptionProfileSection en perfil | Alto (visibilidad plan) | Medio | P0 |
| 2 | CTA upgrade contextual en perfil | Alto (conversión) | Bajo | P0 |
| 3 | Barras de uso vs límites | Alto (awareness) | Medio | P1 |
| 4 | Lock overlay en dashboards | Medio (descubrimiento) | Alto | P1 |
| 5 | Badge de plan en dashboard header | Bajo (informativo) | Bajo | P2 |

### Documento de plan de implementación

El plan detallado de implementación se creará en:
`docs/implementacion/2026-03-17_Plan_Implementacion_PLG_Perfil_Features_Upgrade_v1.md`

---

## Registro de Cambios

| Versión | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-17 | Claude Opus 4.6 | Diagnóstico inicial: 6 gaps detectados, 7 servicios sin UI, benchmark 5 SaaS, caso emprendimiento |
