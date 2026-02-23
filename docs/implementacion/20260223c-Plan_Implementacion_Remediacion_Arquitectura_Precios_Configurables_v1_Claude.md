# Plan de Implementación: Remediación Arquitectura de Precios Configurables

> **Especificación fuente:** `20260223b2-Plan_Remediacion_Definitivo_v2_1_Claude.md`

| Campo | Valor |
|-------|-------|
| **Fecha** | 23 de febrero de 2026 |
| **Autor** | Claude (Anthropic) — Arquitecto SaaS / Implementación |
| **Versión** | 1.0.0 |
| **Categoría** | Plan de Implementación |
| **Código** | REM-PRECIOS-001 |
| **Estado** | LISTO PARA IMPLEMENTACIÓN |
| **Esfuerzo estimado** | 140–180 horas · 60 días (4 fases) |
| **Equipo** | 2 Backend Drupal Senior + 1 QA + 0.5 DevOps |
| **Documentos fuente** | Doc 104 (Admin Center), Doc 158 (Pricing), Plan Remediación v2.1 |
| **Directrices aplicables** | `00_DIRECTRICES_PROYECTO.md` v61.0.0, `00_FLUJO_TRABAJO_CLAUDE.md` v15.0.0 |
| **Arquitectura de referencia** | `2026-02-05_arquitectura_theming_saas_master.md` v2.1 |

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Alcance y Objetivos](#2-alcance-y-objetivos)
   - 2.1 [Objetivos de Negocio](#21-objetivos-de-negocio)
   - 2.2 [Objetivos Técnicos](#22-objetivos-técnicos)
   - 2.3 [Fuera de Alcance](#23-fuera-de-alcance)
3. [Arquitectura General de la Solución](#3-arquitectura-general-de-la-solución)
   - 3.1 [Diagrama de Componentes](#31-diagrama-de-componentes)
   - 3.2 [Flujo de Datos: Planes ↔ Stripe ↔ Tenant](#32-flujo-de-datos-planes--stripe--tenant)
   - 3.3 [Modelo de Entidades](#33-modelo-de-entidades)
   - 3.4 [Separación de Responsabilidades](#34-separación-de-responsabilidades)
4. [Tabla de Correspondencia de Especificaciones Técnicas](#4-tabla-de-correspondencia-de-especificaciones-técnicas)
5. [Matriz de Cumplimiento de Directrices del Proyecto](#5-matriz-de-cumplimiento-de-directrices-del-proyecto)
6. [Fase 1: Config Entities + Aislamiento Tenant (Días 1–15)](#6-fase-1-config-entities--aislamiento-tenant-días-115)
   - 6.1 [REM-P0-07: Entidad SaasPlanTier](#61-rem-p0-07-entidad-saasplantier)
   - 6.2 [REM-P0-07b: Entidad SaasPlanFeatures](#62-rem-p0-07b-entidad-saasplanfeatures)
   - 6.3 [REM-P0-07c: Formularios de Administración](#63-rem-p0-07c-formularios-de-administración)
   - 6.4 [REM-P0-07d: Config Install (Seed Data)](#64-rem-p0-07d-config-install-seed-data)
   - 6.5 [REM-P0-07e: ListBuilders para Admin UI](#65-rem-p0-07e-listbuilders-para-admin-ui)
   - 6.6 [REM-P0-07f: Rutas y Permisos](#66-rem-p0-07f-rutas-y-permisos)
   - 6.7 [REM-P0-07g: Integración en Navegación Admin](#67-rem-p0-07g-integración-en-navegación-admin)
   - 6.8 [REM-P0-01 a P0-06: Tareas de Aislamiento Heredadas de v2.0](#68-rem-p0-01-a-p0-06-tareas-de-aislamiento-heredadas-de-v20)
7. [Fase 2: Billing Coherente (Días 16–30)](#7-fase-2-billing-coherente-días-1630)
   - 7.1 [REM-P1-01: PlanResolverService v2](#71-rem-p1-01-planresolverservice-v2)
   - 7.2 [REM-P1-02: Mapping Stripe ↔ Config Entities](#72-rem-p1-02-mapping-stripe--config-entities)
   - 7.3 [REM-P1-03: SubscriptionUpdatedHandler Refactor](#73-rem-p1-03-subscriptionupdatedhandler-refactor)
   - 7.4 [REM-P1-04: Canonización de IDs](#74-rem-p1-04-canonización-de-ids)
8. [Fase 3: Entitlements sin Drift (Días 31–50)](#8-fase-3-entitlements-sin-drift-días-3150)
   - 8.1 [REM-P2-01: QuotaManagerService v2](#81-rem-p2-01-quotamanagerservice-v2)
   - 8.2 [REM-P2-02: Feature Flags Analytics](#82-rem-p2-02-feature-flags-analytics)
   - 8.3 [REM-P2-03: Validación Pre-Deploy](#83-rem-p2-03-validación-pre-deploy)
   - 8.4 [REM-P2-04: Frontend de Gestión de Planes (Admin Center)](#84-rem-p2-04-frontend-de-gestión-de-planes-admin-center)
9. [Fase 4: CI/CD + Tests de Contrato (Días 51–60)](#9-fase-4-cicd--tests-de-contrato-días-5160)
   - 9.1 [Tests de Contrato Obligatorios](#91-tests-de-contrato-obligatorios)
   - 9.2 [Pipeline CI Específico](#92-pipeline-ci-específico)
   - 9.3 [Auditoría Final por Vertical](#93-auditoría-final-por-vertical)
10. [Arquitectura de Theming y Frontend para Plan Admin UI](#10-arquitectura-de-theming-y-frontend-para-plan-admin-ui)
    - 10.1 [Modelo SASS: Archivos SCSS con Variables Inyectables](#101-modelo-sass-archivos-scss-con-variables-inyectables)
    - 10.2 [Templates Twig Limpias y Parciales Reutilizables](#102-templates-twig-limpias-y-parciales-reutilizables)
    - 10.3 [Layout Full-Width y Mobile-First](#103-layout-full-width-y-mobile-first)
    - 10.4 [Patrón Modal para Acciones CRUD](#104-patrón-modal-para-acciones-crud)
    - 10.5 [Body Classes via hook_preprocess_html()](#105-body-classes-via-hook_preprocess_html)
    - 10.6 [Directriz de Iconos: jaraba_icon()](#106-directriz-de-iconos-jaraba_icon)
    - 10.7 [Directriz de Textos Traducibles](#107-directriz-de-textos-traducibles)
    - 10.8 [Dart Sass Moderno](#108-dart-sass-moderno)
    - 10.9 [Paleta de Colores Oficial](#109-paleta-de-colores-oficial)
11. [Integración con GrapesJS (Page Builder)](#11-integración-con-grapesjs-page-builder)
    - 11.1 [Bloques de Pricing en GrapesJS](#111-bloques-de-pricing-en-grapesjs)
    - 11.2 [Datos Dinámicos desde Config Entities](#112-datos-dinámicos-desde-config-entities)
    - 11.3 [Directrices GrapesJS Aplicables](#113-directrices-grapesjs-aplicables)
12. [Integración con Entidades de Contenido y Navegación Drupal](#12-integración-con-entidades-de-contenido-y-navegación-drupal)
    - 12.1 [Navegación en /admin/structure](#121-navegación-en-adminstructure)
    - 12.2 [Navegación en /admin/content](#122-navegación-en-admincontent)
    - 12.3 [Field UI y Views](#123-field-ui-y-views)
13. [Aislamiento del Tenant: Sin Acceso al Tema de Administración](#13-aislamiento-del-tenant-sin-acceso-al-tema-de-administración)
14. [SEO y GEO Consideraciones](#14-seo-y-geo-consideraciones)
15. [Inteligencia Artificial Aplicada](#15-inteligencia-artificial-aplicada)
16. [Riesgos y Mitigaciones](#16-riesgos-y-mitigaciones)
17. [Checklist de Verificación Pre-Deploy](#17-checklist-de-verificación-pre-deploy)
18. [Comandos de Ejecución en Docker](#18-comandos-de-ejecución-en-docker)
19. [Registro de Cambios](#19-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este plan de implementación detalla paso a paso cómo construir la **arquitectura de precios, tiers y features 100% configurables desde la interfaz de administración de Drupal**, eliminando para siempre cualquier valor hardcodeado en el código fuente.

La solución se basa en dos **Config Entities** de Drupal (`SaasPlanTier` y `SaasPlanFeatures`) que actúan como fuente de verdad para la lógica de negocio del SaaS. Los precios de billing residen en Stripe (fuente de verdad financiera) y se vinculan mediante `stripe_price_id` almacenados en las Config Entities.

### Problema que resuelve

En la versión actual del SaaS, los planes, límites y precios están dispersos en:
- Arrays PHP hardcodeados dentro de servicios
- Archivos YAML estáticos que no se actualizan sin deploy
- Configuraciones inconsistentes entre verticales

Esto genera:
1. **Dependencia de desarrollador** para cualquier cambio de precios o features
2. **Drift** entre lo que Stripe cobra y lo que la plataforma permite
3. **Imposibilidad de escalar** a nuevas verticales sin tocar código

### Solución implementada

| Concepto | Fuente de verdad | Interfaz de gestión |
|----------|-------------------|---------------------|
| **Tiers** (starter, professional, enterprise) | Config Entity: `saas_plan_tier` | `/admin/config/jaraba/plans` |
| **Features y límites** por tier+vertical | Config Entity: `saas_plan_features` | `/admin/config/jaraba/plan-features` |
| **Precios** (mensual, anual, add-ons) | Stripe Products + Prices | Stripe Dashboard + sync automático |

### Impacto esperado

- **Cero deploys** para cambios de precios, features o límites
- **Onboarding de nuevas verticales** en minutos desde UI
- **Auditoría completa** de quién cambió qué y cuándo (Config Entity audit trail)
- **Tests de contrato** que verifican la ausencia de hardcoding

---

## 2. Alcance y Objetivos

### 2.1 Objetivos de Negocio

1. **Autonomía operativa:** El administrador (Pepe) puede crear, editar y eliminar planes SaaS, sus features y sus límites desde `/admin/config/jaraba/plans` sin intervención de desarrollo.
2. **Pricing independiente por vertical:** Cada vertical (Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta) tiene precios, comisiones y límites independientes.
3. **Escalabilidad:** Añadir una nueva vertical o un nuevo tier (e.g., "Business") solo requiere crear las Config Entities correspondientes desde la UI.
4. **Consistencia billing ↔ entitlements:** El `PlanResolverService` resuelve unívocamente qué plan tiene un tenant y qué puede hacer, sin posibilidad de drift.
5. **Trazabilidad:** Cada cambio en las Config Entities queda registrado en el sistema de config export de Drupal, permitiendo auditoría y rollback.

### 2.2 Objetivos Técnicos

1. Implementar `SaasPlanTier` como Config Entity con CRUD completo y formulario de administración.
2. Implementar `SaasPlanFeatures` como Config Entity con formulario de edición de límites numéricos y feature flags.
3. Refactorizar `PlanResolverService` para que lea exclusivamente de Config Entities (cero arrays hardcodeados).
4. Refactorizar `QuotaManagerService` para que delegue toda la resolución de límites al `PlanResolverService`.
5. Crear 18 archivos YAML de seed data (5 verticales × 3 tiers + 3 defaults).
6. Implementar 8 tests de contrato que verifiquen:
   - Ausencia de precios hardcodeados en el código
   - Existencia de features configuradas para todas las combinaciones vertical+tier
   - Resolución correcta de aliases
   - Consistencia entre Stripe price IDs y Config Entities
7. Cumplir al 100% con todas las directrices del proyecto (SCSS SSOT, Dart Sass, templates limpias, iconos, textos traducibles, modales, mobile-first, body classes via `preprocess_html`).

### 2.3 Fuera de Alcance

- Implementación del dashboard de Stripe Connect (se usa Stripe Dashboard existente).
- Migración de datos de tenants existentes (se realiza en un plan separado).
- Cambios en el motor de GrapesJS más allá de los bloques de pricing.
- Implementación de nuevas verticales (la arquitectura lo soporta, pero la creación de verticales no es parte de este plan).

---

## 3. Arquitectura General de la Solución

### 3.1 Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────────┐
│                        ADMIN UI (Drupal)                            │
│  /admin/config/jaraba/plans        /admin/config/jaraba/plan-features│
│  ┌──────────────────┐              ┌──────────────────────────┐     │
│  │  SaasPlanTierForm │              │  SaasPlanFeaturesForm    │     │
│  │  (EntityForm)     │              │  (EntityForm)            │     │
│  └────────┬─────────┘              └────────────┬─────────────┘     │
│           │                                     │                   │
│           ▼                                     ▼                   │
│  ┌──────────────────┐              ┌──────────────────────────┐     │
│  │  SaasPlanTier     │◄────────────│  SaasPlanFeatures        │     │
│  │  (ConfigEntity)   │  tier ref   │  (ConfigEntity)          │     │
│  │                   │             │                          │     │
│  │  - id             │             │  - id (vertical_tier)    │     │
│  │  - label          │             │  - limits{}              │     │
│  │  - aliases[]      │             │  - feature_flags{}       │     │
│  │  - stripe_product │             │  - stripe_prices{}       │     │
│  │  - badge_color    │             │  - platform_fee_percent  │     │
│  └──────────────────┘              └────────────┬─────────────┘     │
│                                                 │                   │
└─────────────────────────────────────────────────┼───────────────────┘
                                                  │
                        ┌─────────────────────────▼──────────────┐
                        │        PlanResolverService v2          │
                        │  - normalize(planName) → canonical ID  │
                        │  - getFeatures(vertical, tier)         │
                        │  - checkLimit(vertical, tier, key)     │
                        │  - resolveFromStripeSubscription(sub)  │
                        └─────────────────┬──────────────────────┘
                                          │
              ┌───────────────────────────┼───────────────────────────┐
              │                           │                           │
              ▼                           ▼                           ▼
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐
│  QuotaManagerService │  │ SubscriptionHandler  │  │   Billing Module     │
│  - canCreatePage()   │  │ - onSubscriptionUpd  │  │   (jaraba_billing)   │
│  - canUseFeature()   │  │ - resolveFromStripe  │  │   - checkout flow    │
│  - checkQuota()      │  │ - updateTenantPlan   │  │   - trial logic      │
└──────────────────────┘  └──────────────────────┘  └──────────────────────┘
              │                           │                           │
              ▼                           ▼                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        STRIPE (Fuente de verdad financiera)         │
│  Products → Prices → Subscriptions → Webhooks                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 3.2 Flujo de Datos: Planes ↔ Stripe ↔ Tenant

**Flujo de configuración (Admin → Config Entities):**

1. El administrador accede a `/admin/config/jaraba/plans`.
2. Crea o edita un `SaasPlanTier` (e.g., "professional") con sus aliases y Stripe Product IDs por vertical.
3. Accede a `/admin/config/jaraba/plan-features` y edita `empleabilidad_professional`.
4. Configura límites (max_users: 10, max_courses: -1), feature flags (ai_copilot: true) y Stripe Price IDs.
5. Los cambios quedan inmediatamente activos sin necesidad de deploy.

**Flujo de suscripción (Tenant → Stripe → Drupal):**

1. El tenant selecciona un plan desde la página de pricing.
2. Se redirige a Stripe Checkout con el `stripe_price_id` correspondiente obtenido de `SaasPlanFeatures`.
3. Stripe procesa el pago y envía webhook `customer.subscription.updated`.
4. `SubscriptionUpdatedHandler` recibe el webhook y llama a `PlanResolverService::resolveFromStripeSubscription()`.
5. El servicio busca en las Config Entities `SaasPlanFeatures` cuál tiene el `stripe_price_id` recibido.
6. Actualiza el campo `plan_type` del tenant con el tier canónico resuelto.
7. A partir de ese momento, `QuotaManagerService` consulta `PlanResolverService::checkLimit()` que lee de las Config Entities.

**Flujo de resolución de cuotas (Runtime):**

1. Un tenant intenta crear una página en el Page Builder.
2. `QuotaManagerService::canCreatePage()` obtiene `vertical` y `plan_type` del tenant.
3. Llama a `PlanResolverService::checkLimit(vertical, tier, 'max_pages')`.
4. `PlanResolverService` busca la Config Entity `{vertical}_{tier}` (e.g., `empleabilidad_professional`).
5. Si no existe, busca el fallback `_default_{tier}`.
6. Retorna el límite numérico (-1 = ilimitado, 0 = deshabilitado, N = límite concreto).
7. `QuotaManagerService` compara con el conteo actual y permite o deniega la acción.

### 3.3 Modelo de Entidades

```
┌─────────────────────────────────────────┐
│ saas_plan_tier (Config Entity)          │
├─────────────────────────────────────────┤
│ id: string (machine_name canónico)      │
│ label: string (EN)                      │
│ label_es: string (ES)                   │
│ weight: int (orden visual)              │
│ is_active: bool                         │
│ aliases: string[] (nombres históricos)  │
│ description: string                     │
│ badge_color: string (#hex)              │
│ stripe_product_ids: {vertical: prod_id} │
├─────────────────────────────────────────┤
│ Ejemplo IDs: starter, professional,     │
│              enterprise                 │
└─────────────────────────────────────────┘
        │ 1:N (referencia lógica por tier ID)
        ▼
┌─────────────────────────────────────────┐
│ saas_plan_features (Config Entity)      │
├─────────────────────────────────────────┤
│ id: string ({vertical}_{tier})          │
│ label: string                           │
│ vertical: string                        │
│ tier: string (ref → saas_plan_tier.id)  │
│ limits: {key: int}  (-1=∞, 0=off)      │
│ feature_flags: {key: bool}              │
│ stripe_prices: {monthly: '', yearly: ''}│
│ platform_fee_percent: float             │
│ sla: string|null                        │
├─────────────────────────────────────────┤
│ Ejemplo IDs:                            │
│   empleabilidad_starter                 │
│   agroconecta_professional              │
│   _default_enterprise                   │
└─────────────────────────────────────────┘
```

**Relación:** La relación entre `SaasPlanTier` y `SaasPlanFeatures` es lógica, no referencial. El campo `tier` de `SaasPlanFeatures` contiene el `id` del tier correspondiente. Esto permite:
- Crear features con defaults globales (`_default_starter`) sin tier específico por vertical.
- Cascada de resolución: `{vertical}_{tier}` → `_default_{tier}` → NULL (fail-safe deniega).

### 3.4 Separación de Responsabilidades

| Componente | Responsabilidad | NO hace |
|------------|-----------------|---------|
| `SaasPlanTier` | Define qué tiers existen, sus aliases y Stripe Products | No almacena precios ni límites |
| `SaasPlanFeatures` | Define límites, feature flags y Stripe Prices por vertical+tier | No resuelve aliases ni normalizaciones |
| `PlanResolverService` | Normaliza nombres de planes, resuelve features con cascada, mapea Stripe subscriptions | No almacena estado ni accede a la BD de tenants |
| `QuotaManagerService` | Verifica cuotas del tenant actual contra los límites del plan | No lee Config Entities directamente (delega a PlanResolver) |
| `SubscriptionUpdatedHandler` | Procesa webhooks de Stripe y actualiza el plan del tenant | No define lógica de billing ni precios |
| Stripe | Fuente de verdad para precios, cobros y subscriptions | No define features ni límites |

---

## 4. Tabla de Correspondencia de Especificaciones Técnicas

Esta tabla mapea cada especificación técnica del Plan de Remediación v2.1 con la tarea concreta de implementación, los archivos afectados, las directrices aplicables y el criterio de aceptación.

| # | Especificación (v2.1) | Tarea Impl. | Archivos Principales | Directriz(es) | Criterio de Aceptación |
|---|----------------------|-------------|---------------------|---------------|----------------------|
| 1 | Sección 1.1: Entidad SaasPlanTier | REM-P0-07 | `src/Entity/SaasPlanTier.php`, `src/Entity/SaasPlanTierInterface.php` | CONFIG-SEED-001, ENTITY-APPEND-001 (no aplica: es editable) | La entidad se crea, edita y elimina desde `/admin/config/jaraba/plans` |
| 2 | Sección 1.1: Formulario SaasPlanTierForm | REM-P0-07 | `src/Form/SaasPlanTierForm.php` | TM-CAST-001 (textos traducibles) | Formulario funcional con validación, todos los labels usan `$this->t()` |
| 3 | Sección 1.2: Entidad SaasPlanFeatures | REM-P0-07b | `src/Entity/SaasPlanFeatures.php` | CONFIG-SEED-001 | Cada combinación vertical+tier tiene features editables |
| 4 | Sección 1.2: Formulario SaasPlanFeaturesForm | REM-P0-07b | `src/Form/SaasPlanFeaturesForm.php` | TM-CAST-001 | Límites numéricos (-1/0/N), flags checkbox, Stripe IDs texto |
| 5 | Sección 1.3: PlanResolverService v2 | REM-P1-01 | `src/Service/PlanResolverService.php` | Regla Oro #1 (no hardcodear) | No contiene arrays de límites, precios ni constantes numéricas de negocio |
| 6 | Sección 1.4: QuotaManagerService v2 | REM-P2-01 | `jaraba_page_builder/src/Service/QuotaManagerService.php` | Regla Oro #1 | Toda llamada a límites pasa por `PlanResolverService` |
| 7 | Sección 1.5: Config Install (18 YAMLs) | REM-P0-07d | `config/install/ecosistema_jaraba_core.plan_tier.*.yml`, `config/install/ecosistema_jaraba_core.plan_features.*.yml` | CONFIG-SEED-001 | 3 tiers + 15 features + 3 defaults = 21 archivos YAML |
| 8 | Sección 1.6: Rutas de administración | REM-P0-07f | `ecosistema_jaraba_core.routing.yml`, `ecosistema_jaraba_core.links.menu.yml` | — | Rutas accesibles, permisos correctos, menús enlazados |
| 9 | Sección 2.1: Mapping Stripe | REM-P1-02 | `src/EventSubscriber/SubscriptionUpdatedHandler.php` | MSG-ENC-001 (seguridad), CSRF-API-001 | Resuelve plan desde Stripe Price ID buscando en Config Entities |
| 10 | Sección 2.2: Canonizar IDs | REM-P1-04 | `PlanResolverService::normalize()` | — | Los aliases "basico", "basic", "free" resuelven a "starter" |
| 11 | Sección 2.3: Config Page Builder | REM-P1-01 ajuste | `jaraba_page_builder.settings.yml` (eliminar), `QuotaManagerService` | PB-PREVIEW-001 | Se elimina `plan_limits` de settings.yml; QuotaManager lee de PlanResolver |
| 12 | Sección 2.4: Fallback cuotas | REM-P1-02 ajuste | `PlanResolverService::checkLimit()` | Regla Oro #3 (detección proactiva) | Log indica URL `/admin/config/jaraba/plan-features` cuando falta config |
| 13 | Sección 3: Precios por Vertical | REM-P0-07d seed | 15 archivos YAML de features | — | Precios de Doc 158 cargados como seed data, editables tras instalación |
| 14 | Sección 4: Tests de Contrato | REM-P3-01 | `tests/src/Kernel/PlanConfigContractTest.php` | TEST-MOCK-001, TEST-NS-001 | 8 tests verifican ausencia de hardcoding y completitud de configs |
| 15 | Sección 5: Resumen de esfuerzo | — | (planificación) | — | Cumplimiento del timeline de 60 días en 4 fases |
| 16 | Admin Center Plan UI (Doc 104 §10.2) | REM-P2-04 | Templates Twig, SCSS, JS | SSOT theming, TPL-PAGE-001, TPL-BODY-001, TPL-ICON-EXE-001 | UI premium accesible desde Admin Center con glassmorphism |
| 17 | Pricing Page frontend | REM-P2-04b | Template Twig de pricing, parciales | Mobile-first, traducible, modal | Página de pricing dinámica alimentada por Config Entities |

---

## 5. Matriz de Cumplimiento de Directrices del Proyecto

Esta sección garantiza que **cada directriz** del proyecto se aplica correctamente en la implementación. El equipo técnico debe verificar cada ítem durante el code review.

### 5.1 Directrices de Testing y Calidad

| ID Directriz | Descripción | Cómo se cumple en esta implementación |
|-------------|-------------|---------------------------------------|
| **TEST-MOCK-001** | Clases `final` no se mockean directamente | Los tests de `PlanResolverService` inyectan el `EntityTypeManager` como `object` y usan interfaces temporales con `if (!interface_exists(...))` |
| **TEST-NS-001** | Interfaces de mock en `if (!interface_exists(...))` | Todas las interfaces temporales de test usan este guard |
| **TEST-CACHE-001** | Mocks de entidad implementan métodos de caché | Los mocks de `SaasPlanTier` y `SaasPlanFeatures` en tests implementan `getCacheContexts()`, `getCacheTags()`, `getCacheMaxAge()` |
| **CONFIG-SEED-001** | Config install via update hook | El `update_hook` lee los YAMLs con `Yaml::decode()`, codifica campos JSON, y crea las entities. Verifica existencia previa para idempotencia |
| **ENTITY-APPEND-001** | Entidades inmutables sin edición | **No aplica** a este plan: `SaasPlanTier` y `SaasPlanFeatures` son editables por diseño (son config de negocio, no logs) |

### 5.2 Directrices de Seguridad

| ID Directriz | Descripción | Cómo se cumple |
|-------------|-------------|----------------|
| **CSRF-API-001** | Rutas API con `_csrf_request_header_token` | Toda ruta API del billing usa `_csrf_request_header_token: 'TRUE'`. El JS obtiene token de `/session/token` y lo envía como `X-CSRF-Token` |
| **TWIG-XSS-001** | `\|safe_html` en contenido de usuario, nunca `\|raw` | Los templates del Admin UI renderizan descriptions y labels con autoescaping de Twig. Solo se usa `\|raw` para JSON-LD generado por backend |
| **MSG-ENC-001** | Datos sensibles cifrados con AES-256-GCM | **No aplica directamente** a Config Entities (son configuración, no datos sensibles). Sin embargo, los Stripe Price IDs se tratan como configuración sensible y se excluyen de config exports públicos |

### 5.3 Directrices de Page Builder

| ID Directriz | Descripción | Cómo se cumple |
|-------------|-------------|----------------|
| **PB-PREVIEW-001** | Templates con `preview_image` | Los bloques de pricing del Page Builder incluyen preview PNGs en `images/previews/` |
| **PB-DATA-001** | `preview_data` con datos ricos | Los previews de pricing incluyen 3+ features representativas y precios reales por vertical |
| **PB-DUP-001** | Sin bloques duplicados en GrapesJS | Se verifica `blockManager.get(id)` antes de registrar bloques de pricing dinámicos |

### 5.4 Directrices de Drupal Core

| ID Directriz | Descripción | Cómo se cumple |
|-------------|-------------|----------------|
| **DRUPAL-ENTUP-001** | No usar `applyUpdates()` | Los update hooks usan `installFieldStorageDefinition()` explícitamente |
| **TM-CAST-001** | Cast `(string)` en TranslatableMarkup | Todos los valores de `$this->t()` asignados a render arrays se castean a `(string)` |
| **API-FIELD-001** | Field mapping explícito | Los campos de la Config Entity coinciden exactamente con los definidos en la clase |
| **STATE-001** | Status coherentes con `allowed_values` | Los tiers usan IDs canónicos consistentes en toda la plataforma |

### 5.5 Directrices de Theming y Frontend

| Directriz | Descripción | Cómo se cumple |
|-----------|-------------|----------------|
| **SSOT (Single Source of Truth)** | Variables SCSS solo en `ecosistema_jaraba_core/scss/_variables.scss` | Los SCSS nuevos del Admin UI de planes NO definen variables `$ej-*`. Solo consumen `var(--ej-*, fallback)` |
| **Módulos solo CSS vars** | Módulos satélite no definen `$ej-*` | Los estilos del formulario de planes usan exclusivamente CSS Custom Properties con fallback inline |
| **Package.json obligatorio** | Todo módulo con SCSS tiene `package.json` | `ecosistema_jaraba_core` ya tiene `package.json`. Se verifica que los scripts de build están actualizados |
| **Dart Sass moderno** | Usar `@use 'sass:color'`, no `darken()`/`lighten()` | Se usa `color.adjust()` y `color-mix()` en CSS. Cero funciones deprecadas |
| **TPL-PAGE-001** | Page templates sin `<!DOCTYPE>`, `<html>`, `<body>` | Las page templates del Admin Center no contienen wrappers HTML. Solo contenido dentro de `<body>` |
| **TPL-BODY-001** | Body classes solo en `hook_preprocess_html()` | Las body classes `page--admin-plans`, `page--admin-plan-features` se asignan en `ecosistema_jaraba_theme.theme` dentro de `hook_preprocess_html()` |
| **TPL-ICON-EXE-001** | Iconos con `jaraba_icon()`, excepto SVG decorativos multi-color | Todos los iconos del Admin UI de planes usan `{{ jaraba_icon('ui', 'settings') }}`. No hay SVGs inline excepto los decorativos con comentario de exención |
| **Textos traducibles** | Todos los textos de interfaz usan `$this->t()` en PHP y `{% trans %}` en Twig | Verificado: cada label, description, placeholder y mensaje de estado usa el sistema de traducción de Drupal |
| **Variables inyectables desde UI** | Los valores de diseño se configuran en la UI del tema | Los colores del badge de plan usan `var(--ej-color-primary)` con fallback al valor de `badge_color` de la Config Entity |
| **Frontend limpio** | Sin `page.content` ni bloques heredados | Las páginas de frontend de planes usan layout full-width sin regiones de Drupal. El contenido se inyecta directamente en el controlador |
| **Layout mobile-first** | Diseño pensado para móvil | Grid responsive con `grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))`. Breakpoints progresivos |
| **Acciones en modal** | Crear/editar/ver en modal | Los formularios de crear/editar plan se abren en modal (slide-panel o dialog) para que el usuario no abandone la página de listado |

### 5.6 Directrices de GrapesJS

| Directriz | Cómo se cumple |
|-----------|----------------|
| **GRAPEJS-001** (changeProp con defaults en model) | Los traits del bloque de pricing que usan `changeProp: true` tienen propiedad correspondiente en `model.defaults` |
| **Nomenclatura `jaraba-{cat}-{variante}`** | El bloque se registra como `jaraba-pricing-dynamic` |
| **Dual architecture** (script + behavior) | El bloque interactivo de tabs/toggle annual/monthly usa `script` property para el canvas y `Drupal.behaviors` para la página pública |
| **Design tokens via CSS vars** | Los estilos del bloque usan `var(--ej-*)` exclusivamente |

### 5.7 Reglas de Oro Aplicables

| # | Regla | Aplicación concreta |
|---|-------|---------------------|
| 1 | No hardcodear | Cero arrays de precios/límites en código PHP. Todo en Config Entities |
| 3 | Detección proactiva | Logs con URL de admin cuando falta configuración. Alertas push para features no configurados |
| 4 | Tenant isolation | `tenant_id` verificado en cada acceso a features/cuotas |
| 7 | Documentar siempre | Este documento + aprendizaje generado post-implementación |
| 9 | Verificar CI tras cambios config | Pipeline completo tras cada merge de Config Entity changes |
| 10 | Update hooks para config resync | YAML install files + update hook para resincronización |
| 11 | CSRF header en APIs | Todas las rutas API del billing usan `_csrf_request_header_token` |
| 12 | Sanitizar contenido | Twig autoescaping + `\|safe_html` en descriptions de planes |
| 15 | Config seeding con JSON | Update hook con `Yaml::decode()` → `json_encode()` → `Entity::create()->save()` |

---

## 6. Fase 1: Config Entities + Aislamiento Tenant (Días 1–15)

**Esfuerzo estimado: 50–60 horas**

### 6.1 REM-P0-07: Entidad SaasPlanTier

**Descripción extensa:**

La entidad `SaasPlanTier` es una Config Entity de Drupal que define los niveles de plan disponibles en la plataforma (e.g., Starter, Professional, Enterprise). A diferencia de una Content Entity, una Config Entity se exporta/importa con el sistema de configuración de Drupal (`drush config:export/import`), lo que permite:

1. **Versionado en Git:** Los planes quedan almacenados en `config/sync/` como YAML.
2. **Deploy reproducible:** El mismo conjunto de planes se despliega en todos los entornos.
3. **Edición en runtime:** Se pueden editar desde la UI sin necesidad de deploy.

**Archivos a crear:**

| Archivo | Ubicación | Descripción |
|---------|-----------|-------------|
| `SaasPlanTier.php` | `web/modules/custom/ecosistema_jaraba_core/src/Entity/` | Clase de la Config Entity con propiedades, getters y lógica de aliases |
| `SaasPlanTierInterface.php` | `web/modules/custom/ecosistema_jaraba_core/src/Entity/` | Interfaz que define el contrato público de la entidad |
| `config/schema/ecosistema_jaraba_core.plan_tier.schema.yml` | `web/modules/custom/ecosistema_jaraba_core/` | Schema de configuración para validación |

**Especificación de propiedades:**

```php
// Las propiedades de SaasPlanTier y su propósito:
protected string $id;                    // Machine name canónico (e.g., 'starter')
protected string $label;                 // Nombre en inglés (e.g., 'Starter')
protected string $label_es = '';         // Nombre en español para i18n (e.g., 'Básico')
protected int $weight = 0;              // Orden de visualización en listados
protected bool $is_active = TRUE;       // Si el plan está activo para nuevos clientes
protected array $aliases = [];          // Nombres históricos que mapean a este plan
protected string $description = '';     // Descripción del plan
protected string $badge_color = '#00A9A5'; // Color hex del badge visual
protected array $stripe_product_ids = []; // Mapa vertical → Stripe Product ID
```

**Patrón de resolución de aliases:**

Los aliases resuelven el problema histórico de que los planes se han llamado de distintas formas a lo largo del tiempo. Por ejemplo, el plan "Starter" ha sido referenciado como "basico", "basic", "free" en distintos lugares del código y la base de datos. El `PlanResolverService::normalize()` usa estos aliases para normalizar cualquier nombre al ID canónico.

```php
// Ejemplo de uso de aliases
$tier = SaasPlanTier::load('starter');
$tier->getAliases();  // ['basico', 'basic', 'free']
$tier->isAlias('basico');  // TRUE

// PlanResolverService normaliza:
$resolver->normalize('basico');  // 'starter'
$resolver->normalize('Starter'); // 'starter'
$resolver->normalize('FREE');    // 'starter'
```

**Schema de configuración:**

```yaml
# config/schema/ecosistema_jaraba_core.plan_tier.schema.yml
ecosistema_jaraba_core.plan_tier.*:
  type: config_entity
  label: 'Plan SaaS tier'
  mapping:
    id:
      type: string
      label: 'Machine name'
    label:
      type: label
      label: 'Name (EN)'
    label_es:
      type: string
      label: 'Name (ES)'
    weight:
      type: integer
      label: 'Weight'
    is_active:
      type: boolean
      label: 'Active'
    aliases:
      type: sequence
      label: 'Aliases'
      sequence:
        type: string
        label: 'Alias'
    description:
      type: string
      label: 'Description'
    badge_color:
      type: string
      label: 'Badge color'
    stripe_product_ids:
      type: mapping
      label: 'Stripe Product IDs by vertical'
      mapping:
        empleabilidad:
          type: string
          label: 'Empleabilidad'
        emprendimiento:
          type: string
          label: 'Emprendimiento'
        agroconecta:
          type: string
          label: 'AgroConecta'
        comercioconecta:
          type: string
          label: 'ComercioConecta'
        serviciosconecta:
          type: string
          label: 'ServiciosConecta'
```

**Directrices de implementación:**

- Todos los labels del formulario DEBEN usar `$this->t()` para traducibilidad.
- El `label_es` es un campo adicional explícito para la traducción al español, ya que las Config Entities no tienen soporte nativo de `config_translation` por defecto sin módulo adicional. Esto permite mostrar el nombre del plan en el idioma correcto del frontend.
- El campo `stripe_product_ids` es un mapa asociativo donde la clave es el machine_name de la vertical. Para añadir nuevas verticales en el futuro, se extiende este mapa desde el formulario y el schema.
- El `badge_color` se usa tanto en el Admin UI como en los componentes de frontend (pricing cards, plan badges). Se inyecta como CSS Custom Property para que el tema pueda sobreescribirlo: `style="--plan-badge-color: {{ tier.badge_color }}"`.

### 6.2 REM-P0-07b: Entidad SaasPlanFeatures

**Descripción extensa:**

La entidad `SaasPlanFeatures` almacena los límites numéricos, feature flags y configuración de Stripe para cada combinación de vertical+tier. Su formato de ID es `{vertical}_{tier}` (e.g., `empleabilidad_starter`), lo que permite:

1. **Personalización granular:** Empleabilidad Starter puede tener 5 cursos mientras AgroConecta Starter tiene 50 productos.
2. **Defaults globales:** Un ID `_default_starter` sirve como fallback para verticales que no tengan configuración específica.
3. **Escalabilidad:** Añadir una nueva vertical solo requiere crear las 3 Config Entities (una por tier).

**Archivos a crear:**

| Archivo | Ubicación | Descripción |
|---------|-----------|-------------|
| `SaasPlanFeatures.php` | `web/modules/custom/ecosistema_jaraba_core/src/Entity/` | Config Entity con límites, flags y precios |
| `config/schema/ecosistema_jaraba_core.plan_features.schema.yml` | `web/modules/custom/ecosistema_jaraba_core/` | Schema de validación |

**Especificación de límites numéricos:**

Los límites usan una convención semántica:
- **-1** = ilimitado (sin tope)
- **0** = deshabilitado (feature no disponible)
- **N > 0** = límite concreto

```php
protected array $limits = [
    'max_users'              => 3,    // Usuarios del tenant
    'max_pages'              => 5,    // Páginas del Page Builder
    'max_products'           => 50,   // Productos en marketplace
    'max_courses'            => 5,    // Cursos en LMS
    'max_job_postings'       => 3,    // Ofertas de empleo activas
    'max_services'           => 5,    // Servicios publicados
    'storage_gb'             => 5,    // Almacenamiento en GB
    'api_calls'              => 10000,// Llamadas API por mes
    'ai_credits'             => 1000, // Créditos de IA por mes
    'orders_per_month'       => 100,  // Pedidos por mes
    'candidates_per_month'   => 50,   // Candidaturas por mes
    'bookings_per_month'     => 50,   // Reservas por mes
    'mentoring_hours_month'  => 0,    // Horas de mentoría por mes
];
```

**Especificación de feature flags:**

```php
protected array $feature_flags = [
    'webhooks'               => FALSE, // Webhooks salientes
    'api_access'             => FALSE, // Acceso API lectura
    'api_write_access'       => FALSE, // Acceso API escritura
    'white_label'            => FALSE, // Marca blanca
    'ai_copilot'             => FALSE, // AI Copilot contextual
    'premium_blocks'         => FALSE, // Bloques premium del Page Builder
    'video_conferencing'     => FALSE, // Videoconferencia integrada
    'digital_signature'      => FALSE, // Firma digital PAdES
    'matching_engine'        => FALSE, // Motor de matching empleo
    'learning_paths'         => FALSE, // Rutas de aprendizaje
    'auto_certificates'      => FALSE, // Certificados automáticos
    'financial_projections'  => FALSE, // Proyecciones financieras
    'competitive_analysis'   => FALSE, // Análisis competitivo
    'qr_traceability'        => FALSE, // Trazabilidad QR
    'priority_support'       => FALSE, // Soporte prioritario
    'dedicated_support'      => FALSE, // Soporte dedicado + SLA
];
```

**Schema de configuración:**

```yaml
# config/schema/ecosistema_jaraba_core.plan_features.schema.yml
ecosistema_jaraba_core.plan_features.*:
  type: config_entity
  label: 'Plan features configuration'
  mapping:
    id:
      type: string
      label: 'ID (vertical_tier)'
    label:
      type: label
      label: 'Label'
    vertical:
      type: string
      label: 'Vertical'
    tier:
      type: string
      label: 'Tier'
    limits:
      type: mapping
      label: 'Numeric limits'
      mapping:
        max_users:
          type: integer
          label: 'Max users'
        max_pages:
          type: integer
          label: 'Max pages'
        max_products:
          type: integer
          label: 'Max products'
        max_courses:
          type: integer
          label: 'Max courses'
        max_job_postings:
          type: integer
          label: 'Max job postings'
        max_services:
          type: integer
          label: 'Max services'
        storage_gb:
          type: integer
          label: 'Storage GB'
        api_calls:
          type: integer
          label: 'API calls/month'
        ai_credits:
          type: integer
          label: 'AI credits/month'
        orders_per_month:
          type: integer
          label: 'Orders/month'
        candidates_per_month:
          type: integer
          label: 'Candidates/month'
        bookings_per_month:
          type: integer
          label: 'Bookings/month'
        mentoring_hours_month:
          type: integer
          label: 'Mentoring hours/month'
    feature_flags:
      type: mapping
      label: 'Feature flags'
      mapping:
        webhooks:
          type: boolean
          label: 'Webhooks'
        api_access:
          type: boolean
          label: 'API access (read)'
        api_write_access:
          type: boolean
          label: 'API access (write)'
        white_label:
          type: boolean
          label: 'White label'
        ai_copilot:
          type: boolean
          label: 'AI Copilot'
        premium_blocks:
          type: boolean
          label: 'Premium blocks'
        video_conferencing:
          type: boolean
          label: 'Video conferencing'
        digital_signature:
          type: boolean
          label: 'Digital signature'
        matching_engine:
          type: boolean
          label: 'Matching engine'
        learning_paths:
          type: boolean
          label: 'Learning paths'
        auto_certificates:
          type: boolean
          label: 'Auto certificates'
        financial_projections:
          type: boolean
          label: 'Financial projections'
        competitive_analysis:
          type: boolean
          label: 'Competitive analysis'
        qr_traceability:
          type: boolean
          label: 'QR traceability'
        priority_support:
          type: boolean
          label: 'Priority support'
        dedicated_support:
          type: boolean
          label: 'Dedicated support'
    stripe_prices:
      type: mapping
      label: 'Stripe Price IDs'
      mapping:
        monthly:
          type: string
          label: 'Monthly price ID'
        yearly:
          type: string
          label: 'Yearly price ID'
    platform_fee_percent:
      type: float
      label: 'Platform fee %'
    sla:
      type: string
      label: 'SLA'
      nullable: true
```

### 6.3 REM-P0-07c: Formularios de Administración

**Descripción extensa:**

Los formularios de administración permiten al administrador gestionar los planes y features sin tocar código. Se implementan como `EntityForm` de Drupal, lo que proporciona:

1. **Validación automática** del schema de configuración.
2. **CSRF protection** nativa de Form API.
3. **Integration** con el sistema de traducción de Drupal.

**Directrices de implementación para los formularios:**

1. **Textos traducibles:** Todo label, description, placeholder y option text DEBE usar `$this->t()`:
   ```php
   '#title' => $this->t('Plan name (EN)'),
   '#description' => $this->t('Canonical machine name. DO NOT change after creation.'),
   ```

2. **Accesibilidad:** Los campos de formulario DEBEN tener `#description` para guiar al usuario:
   ```php
   '#description' => $this->t('-1 = unlimited, 0 = disabled'),
   ```

3. **Organización visual:** Los campos se agrupan en `#type => 'details'` colapsables:
   - Stripe Prices (abierto por defecto)
   - Límites numéricos (abierto)
   - Feature flags (abierto)
   - Comisión y SLA (cerrado por defecto)

4. **Mensajes de estado:** Al guardar, se muestra un mensaje con el nombre del plan:
   ```php
   $this->messenger()->addStatus(
       $this->t('Plan @label saved.', ['@label' => $this->entity->label()])
   );
   ```

5. **Redirección post-save:** El formulario redirige a la colección de planes (`toUrl('collection')`).

**SaasPlanTierForm — Detalles de implementación:**

El formulario de tiers incluye:
- Campo `label` (textfield): Nombre en inglés del plan.
- Campo `label_es` (textfield): Nombre en español.
- Campo `id` (machine_name): Generado automáticamente desde `label`, no editable una vez creado.
- Campo `description` (textarea): Descripción del plan (hasta 3 filas).
- Campo `aliases` (textfield): Lista separada por comas de nombres históricos.
- Campo `badge_color` (color): Selector de color HTML5.
- Campo `is_active` (checkbox): Activa/desactiva el plan.
- Campo `weight` (weight): Orden de visualización.
- Fieldset `stripe_product_ids` (details): Un textfield por cada vertical conocida.

**SaasPlanFeaturesForm — Detalles de implementación:**

El formulario de features incluye:
- Info markup: Muestra el nombre del plan, vertical y tier como header no editable.
- Fieldset `stripe_prices`: Textfields para Price IDs mensual y anual.
- Fieldset `limits`: Un campo `number` por cada límite, con `#min => -1`.
- Fieldset `feature_flags`: Un checkbox por cada feature.
- Campo `platform_fee_percent`: Number con `#step => 0.5`, rango 0–100.
- Campo `sla`: Select con opciones vacía / 99.5% / 99.9% / 99.99%.

### 6.4 REM-P0-07d: Config Install (Seed Data)

**Descripción extensa:**

Los archivos YAML de `config/install/` se procesan durante la instalación del módulo y sirven como seed data. Son inmediatamente editables desde la UI tras la instalación.

**Inventario completo de archivos YAML (21 archivos):**

**3 Tiers:**
```
config/install/ecosistema_jaraba_core.plan_tier.starter.yml
config/install/ecosistema_jaraba_core.plan_tier.professional.yml
config/install/ecosistema_jaraba_core.plan_tier.enterprise.yml
```

**15 Features (5 verticales × 3 tiers):**
```
config/install/ecosistema_jaraba_core.plan_features.empleabilidad_starter.yml
config/install/ecosistema_jaraba_core.plan_features.empleabilidad_professional.yml
config/install/ecosistema_jaraba_core.plan_features.empleabilidad_enterprise.yml
config/install/ecosistema_jaraba_core.plan_features.emprendimiento_starter.yml
config/install/ecosistema_jaraba_core.plan_features.emprendimiento_professional.yml
config/install/ecosistema_jaraba_core.plan_features.emprendimiento_enterprise.yml
config/install/ecosistema_jaraba_core.plan_features.agroconecta_starter.yml
config/install/ecosistema_jaraba_core.plan_features.agroconecta_professional.yml
config/install/ecosistema_jaraba_core.plan_features.agroconecta_enterprise.yml
config/install/ecosistema_jaraba_core.plan_features.comercioconecta_starter.yml
config/install/ecosistema_jaraba_core.plan_features.comercioconecta_professional.yml
config/install/ecosistema_jaraba_core.plan_features.comercioconecta_enterprise.yml
config/install/ecosistema_jaraba_core.plan_features.serviciosconecta_starter.yml
config/install/ecosistema_jaraba_core.plan_features.serviciosconecta_professional.yml
config/install/ecosistema_jaraba_core.plan_features.serviciosconecta_enterprise.yml
```

**3 Defaults (fallback cross-vertical):**
```
config/install/ecosistema_jaraba_core.plan_features._default_starter.yml
config/install/ecosistema_jaraba_core.plan_features._default_professional.yml
config/install/ecosistema_jaraba_core.plan_features._default_enterprise.yml
```

**Datos seed del Doc 158 (referencia de precios actuales):**

| Vertical | Starter €/mes | Pro €/mes | Enterprise €/mes | Comisión S/P/E |
|----------|--------------|-----------|-----------------|----------------|
| Empleabilidad | 29 | 79 | 149 | 8% / 5% / 3% |
| Emprendimiento | 39 | 99 | 199 | 8% / 5% / 3% |
| AgroConecta | 29 | 79 | 149 | 8% / 5% / 3% |
| ComercioConecta | 29 | 79 | 149 | 8% / 5% / 3% |
| ServiciosConecta | 29 | 79 | 149 | 10% / 7% / 4% |

> **IMPORTANTE:** Estos precios son la fotografía actual. Los Stripe Price IDs se dejan vacíos en los YAML de install porque se configuran en el Stripe Dashboard y se vinculan posteriormente desde la UI de `/admin/config/jaraba/plan-features`. Los precios reales de billing viven en Stripe.

**Ejemplo de YAML de tier seed:**

```yaml
# config/install/ecosistema_jaraba_core.plan_tier.starter.yml
id: starter
label: Starter
label_es: Básico
weight: 10
is_active: true
aliases:
  - basico
  - basic
  - free
description: 'Plan de entrada para pequeños negocios'
badge_color: '#00A9A5'
stripe_product_ids:
  empleabilidad: ''
  emprendimiento: ''
  agroconecta: ''
  comercioconecta: ''
  serviciosconecta: ''
```

**Ejemplo de YAML de features seed:**

```yaml
# config/install/ecosistema_jaraba_core.plan_features.empleabilidad_starter.yml
id: empleabilidad_starter
label: 'Empleabilidad - Starter'
vertical: empleabilidad
tier: starter
limits:
  max_users: 2
  max_courses: 5
  max_job_postings: 3
  candidates_per_month: 50
  max_pages: 5
  max_products: 0
  max_services: 0
  storage_gb: 1
  api_calls: 10000
  ai_credits: 0
  orders_per_month: 0
  bookings_per_month: 0
  mentoring_hours_month: 0
feature_flags:
  webhooks: false
  api_access: false
  api_write_access: false
  white_label: false
  ai_copilot: false
  matching_engine: false
  learning_paths: false
  auto_certificates: false
  premium_blocks: false
  video_conferencing: false
  digital_signature: false
  financial_projections: false
  competitive_analysis: false
  qr_traceability: false
  priority_support: false
  dedicated_support: false
stripe_prices:
  monthly: ''
  yearly: ''
platform_fee_percent: 8.0
sla: null
```

**Update hook para resincronización (CONFIG-SEED-001):**

Dado que los archivos `config/install/` solo se procesan durante la instalación del módulo, se necesita un update hook para sincronizar los YAML con la BD activa cuando se modifican los archivos:

```php
/**
 * Import/resync SaasPlanTier and SaasPlanFeatures config entities.
 */
function ecosistema_jaraba_core_update_9001(): string {
  $module_path = \Drupal::service('extension.list.module')
    ->getPath('ecosistema_jaraba_core');
  $config_path = $module_path . '/config/install';

  $count = 0;

  // Process tier configs.
  foreach (glob($config_path . '/ecosistema_jaraba_core.plan_tier.*.yml') as $file) {
    $data = \Drupal\Core\Serialization\Yaml::decode(file_get_contents($file));
    $id = $data['id'];
    $storage = \Drupal::entityTypeManager()->getStorage('saas_plan_tier');
    if (!$storage->load($id)) {
      $storage->create($data)->save();
      $count++;
    }
  }

  // Process features configs.
  foreach (glob($config_path . '/ecosistema_jaraba_core.plan_features.*.yml') as $file) {
    $data = \Drupal\Core\Serialization\Yaml::decode(file_get_contents($file));
    $id = $data['id'];
    $storage = \Drupal::entityTypeManager()->getStorage('saas_plan_features');
    if (!$storage->load($id)) {
      $storage->create($data)->save();
      $count++;
    }
  }

  return "Imported $count plan config entities.";
}
```

### 6.5 REM-P0-07e: ListBuilders para Admin UI

**Descripción extensa:**

Los ListBuilders renderizan la tabla de administración de cada entidad. Son la vista principal en `/admin/config/jaraba/plans` y `/admin/config/jaraba/plan-features`.

**SaasPlanTierListBuilder:**

```php
namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class SaasPlanTierListBuilder extends ConfigEntityListBuilder {

  public function buildHeader(): array {
    $header['label'] = $this->t('Plan');
    $header['id'] = $this->t('Machine name');
    $header['is_active'] = $this->t('Active');
    $header['aliases'] = $this->t('Aliases');
    $header['badge_color'] = $this->t('Color');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['is_active'] = $entity->get('is_active') ? $this->t('Yes') : $this->t('No');
    $row['aliases'] = implode(', ', $entity->getAliases());
    $row['badge_color'] = [
      'data' => [
        '#markup' => '<span style="display:inline-block;width:24px;height:24px;border-radius:50%;background:' . $entity->get('badge_color') . '"></span>',
      ],
    ];
    return $row + parent::buildRow($entity);
  }

}
```

**SaasPlanFeaturesListBuilder:**

```php
namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class SaasPlanFeaturesListBuilder extends ConfigEntityListBuilder {

  public function buildHeader(): array {
    $header['label'] = $this->t('Plan + Vertical');
    $header['vertical'] = $this->t('Vertical');
    $header['tier'] = $this->t('Tier');
    $header['platform_fee'] = $this->t('Fee %');
    $header['sla'] = $this->t('SLA');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['vertical'] = ucfirst($entity->get('vertical'));
    $row['tier'] = ucfirst($entity->get('tier'));
    $row['platform_fee'] = $entity->get('platform_fee_percent') . '%';
    $row['sla'] = $entity->get('sla') ?: $this->t('None');
    return $row + parent::buildRow($entity);
  }

}
```

### 6.6 REM-P0-07f: Rutas y Permisos

**Rutas a registrar en `ecosistema_jaraba_core.routing.yml`:**

```yaml
# === SaaS Plans Administration ===

entity.saas_plan_tier.collection:
  path: '/admin/config/jaraba/plans'
  defaults:
    _entity_list: 'saas_plan_tier'
    _title: 'SaaS Plans'
  requirements:
    _permission: 'administer saas plans'

entity.saas_plan_tier.add_form:
  path: '/admin/config/jaraba/plans/add'
  defaults:
    _entity_form: 'saas_plan_tier.add'
    _title: 'Add Plan'
  requirements:
    _permission: 'administer saas plans'

entity.saas_plan_tier.edit_form:
  path: '/admin/config/jaraba/plans/{saas_plan_tier}'
  defaults:
    _entity_form: 'saas_plan_tier.edit'
    _title: 'Edit Plan'
  requirements:
    _permission: 'administer saas plans'

entity.saas_plan_tier.delete_form:
  path: '/admin/config/jaraba/plans/{saas_plan_tier}/delete'
  defaults:
    _entity_form: 'saas_plan_tier.delete'
    _title: 'Delete Plan'
  requirements:
    _permission: 'administer saas plans'

entity.saas_plan_features.collection:
  path: '/admin/config/jaraba/plan-features'
  defaults:
    _entity_list: 'saas_plan_features'
    _title: 'Plan Features by Vertical'
  requirements:
    _permission: 'administer saas plans'

entity.saas_plan_features.edit_form:
  path: '/admin/config/jaraba/plan-features/{saas_plan_features}'
  defaults:
    _entity_form: 'saas_plan_features.edit'
    _title: 'Edit Plan Features'
  requirements:
    _permission: 'administer saas plans'
```

**Permiso a registrar en `ecosistema_jaraba_core.permissions.yml`:**

```yaml
administer saas plans:
  title: 'Administer SaaS plans'
  description: 'Create, edit and delete SaaS plan tiers and features configuration.'
  restrict access: true
```

> **Nota de seguridad:** Este permiso se marca con `restrict access: true`, lo que genera una advertencia en la UI de permisos de Drupal para que solo se asigne a roles de confianza (Platform Admin).

### 6.7 REM-P0-07g: Integración en Navegación Admin

**Descripción extensa:**

Las Config Entities deben ser accesibles desde la navegación de administración de Drupal para que el administrador pueda encontrarlas fácilmente. Se registran en dos ubicaciones:

1. **`/admin/config/jaraba/`** — Agrupación bajo la sección "Jaraba" de configuración.
2. **Menú de administración** — Links en el menú de admin de Drupal.

**Links de menú en `ecosistema_jaraba_core.links.menu.yml`:**

```yaml
entity.saas_plan_tier.collection:
  title: 'SaaS Plans'
  description: 'Manage SaaS plan tiers (Starter, Professional, Enterprise).'
  route_name: entity.saas_plan_tier.collection
  parent: ecosistema_jaraba_core.admin_config
  weight: 10

entity.saas_plan_features.collection:
  title: 'Plan Features'
  description: 'Configure features, limits and Stripe prices per vertical and tier.'
  route_name: entity.saas_plan_features.collection
  parent: ecosistema_jaraba_core.admin_config
  weight: 11
```

**Links de acción en `ecosistema_jaraba_core.links.action.yml`:**

```yaml
entity.saas_plan_tier.add:
  route_name: entity.saas_plan_tier.add_form
  title: 'Add plan tier'
  appears_on:
    - entity.saas_plan_tier.collection
```

### 6.8 REM-P0-01 a P0-06: Tareas de Aislamiento Heredadas de v2.0

Estas tareas se mantienen intactas de la v2.0 y se ejecutan en paralelo con las tareas de Config Entities:

| Tarea | Descripción | Horas | Criterio |
|-------|-------------|-------|----------|
| REM-P0-01 | Auditar `tenant_id` en todas las queries | 6-8h | `grep -r 'tenant_id'` cubre 100% de servicios |
| REM-P0-02 | Verificar aislamiento de Group module | 4-6h | Tests de acceso cross-tenant pasan |
| REM-P0-03 | Asegurar Stripe Connect isolation | 4-6h | Cada tenant tiene `stripe_account_id` propio |
| REM-P0-04 | Mapping Stripe → Plan canónico | 6-8h | `SubscriptionUpdatedHandler` usa `PlanResolverService v2` |
| REM-P0-05 | Canonizar IDs en config/sync | 4-6h | Cero archivos YAML con nombres en español |
| REM-P0-06 | Config split por entorno | 4-6h | config_split activo para dev/staging/prod |

---

## 7. Fase 2: Billing Coherente (Días 16–30)

**Esfuerzo estimado: 30–40 horas**

### 7.1 REM-P1-01: PlanResolverService v2

**Descripción extensa:**

El `PlanResolverService` es el servicio central que traduce cualquier referencia a un plan en el conjunto de features y límites correspondientes. La v2 elimina completamente los arrays hardcodeados y lee todo desde Config Entities.

**Principio de diseño:**

> El `PlanResolverService` no almacena ningún valor de negocio (precios, límites, features). Su única responsabilidad es la resolución: dado un nombre de plan y una vertical, encontrar la Config Entity correcta.

**Métodos principales y su lógica:**

```php
/**
 * normalize(string $planName): string
 *
 * Normaliza cualquier nombre de plan a su machine_name canónico.
 * Carga lazily TODOS los SaasPlanTier y construye un mapa de aliases.
 *
 * Ejemplos:
 *   normalize('basico')       → 'starter'
 *   normalize('Profesional')  → 'professional'
 *   normalize('ENTERPRISE')   → 'enterprise'
 *   normalize('unknown')      → 'unknown' (no falla, retorna lowercase)
 */

/**
 * getFeatures(string $vertical, string $tier): ?SaasPlanFeatures
 *
 * Obtiene las features para una combinación vertical+tier.
 * Cascada de resolución:
 *   1. Buscar: {vertical}_{tier} (e.g., empleabilidad_starter)
 *   2. Fallback: _default_{tier} (e.g., _default_starter)
 *   3. NULL si no existe ninguno (fail-safe: denegar todo)
 */

/**
 * checkLimit(string $vertical, string $tier, string $limitKey): int
 *
 * Verifica un límite específico. Wrapper de getFeatures() + getLimit().
 * Si no hay features configuradas, loguea error con URL de configuración
 * y retorna 0 (fail-safe: denegar).
 */

/**
 * resolveFromStripeSubscription(object $sub): string
 *
 * Dado un evento de Stripe subscription, busca en TODAS las
 * SaasPlanFeatures cuál tiene el stripe_price_id correspondiente.
 * Fallback a metadata['plan'] normalizado.
 * Último fallback: 'starter'.
 */
```

**Registro del servicio en `ecosistema_jaraba_core.services.yml`:**

```yaml
ecosistema_jaraba_core.plan_resolver:
  class: Drupal\ecosistema_jaraba_core\Service\PlanResolverService
  arguments:
    - '@entity_type.manager'
  tags:
    - { name: service_collector }
```

**Test de contrato — Sin hardcoding:**

```php
public function testPlanResolverUsesNoHardcodedValues(): void {
  $ref = new \ReflectionClass(PlanResolverService::class);
  $src = file_get_contents($ref->getFileName());

  // No debe contener precios en euros
  $this->assertDoesNotMatchRegularExpression('/[0-9]+\.?[0-9]*\s*€/', $src);

  // No debe contener arrays de límites
  $this->assertStringNotContainsString("'max_users'", $src);
  $this->assertStringNotContainsString("'storage_gb'", $src);

  // No debe contener arrays de features
  $this->assertStringNotContainsString("'ai_copilot'", $src);
  $this->assertStringNotContainsString("'premium_blocks'", $src);
}
```

### 7.2 REM-P1-02: Mapping Stripe ↔ Config Entities

**Descripción extensa:**

El mapping entre Stripe y las Config Entities se realiza mediante los campos `stripe_product_ids` (en `SaasPlanTier`) y `stripe_prices` (en `SaasPlanFeatures`):

- **Stripe Product** → `SaasPlanTier::stripe_product_ids[vertical]` — Identifica qué producto de Stripe corresponde a qué tier en qué vertical.
- **Stripe Price** → `SaasPlanFeatures::stripe_prices[monthly|yearly]` — Identifica qué precio de Stripe corresponde a qué combinación tier+vertical+ciclo.

**Flujo de resolución desde webhook:**

1. Stripe envía `customer.subscription.updated` con `subscription.items.data[0].price.id`.
2. `resolveFromStripeSubscription()` itera TODAS las `SaasPlanFeatures` buscando ese `price_id`.
3. Si lo encuentra, retorna el `tier` de esa Config Entity.
4. Si no lo encuentra, busca en `subscription.metadata['plan']` y normaliza.
5. Último fallback: retorna `'starter'`.

**Consideraciones de rendimiento:**

La búsqueda en Config Entities es O(n) donde n = número de combinaciones (actualmente 18). Esto es aceptable porque:
- Los webhooks de Stripe no son de alta frecuencia (máximo cientos/día).
- Las Config Entities se cachean agresivamente por Drupal.
- El lazy initialization de `PlanResolverService` solo carga las entities una vez por request.

### 7.3 REM-P1-03: SubscriptionUpdatedHandler Refactor

**Descripción extensa:**

El handler actual del webhook de Stripe debe refactorizarse para usar `PlanResolverService v2` en lugar de la lógica hardcodeada.

**Antes (v2.0):**
```php
// INCORRECTO: Array hardcodeado de mapping
$planMap = [
  'price_starter_monthly' => 'starter',
  'price_pro_monthly' => 'professional',
  // ... más valores hardcodeados
];
$tier = $planMap[$priceId] ?? 'starter';
```

**Después (v2.1):**
```php
// CORRECTO: Delega al PlanResolverService
$tier = $this->planResolver->resolveFromStripeSubscription($subscription);
$tenant->set('plan_type', $tier);
$tenant->save();
```

### 7.4 REM-P1-04: Canonización de IDs

**Descripción extensa:**

La canonización asegura que en toda la plataforma se usa el machine_name canónico del tier, no alias históricos. Esto se verifica con un script que busca en config/sync:

```bash
# Verificar que no hay archivos con nombres en español
docker exec jarabasaas_appserver_1 bash -c \
  "grep -rl 'plan_type.*basico\|plan_type.*profesional' /app/config/sync/"
# Resultado esperado: vacío
```

---

## 8. Fase 3: Entitlements sin Drift (Días 31–50)

**Esfuerzo estimado: 25–35 horas**

### 8.1 REM-P2-01: QuotaManagerService v2

**Descripción extensa:**

El `QuotaManagerService` es el punto de entrada para que cualquier módulo verifique si el tenant actual puede realizar una acción (crear página, usar feature, etc.). La v2 elimina su propia configuración de límites y delega todo a `PlanResolverService`.

**Principio de diseño:**

> El `QuotaManagerService` solo conoce tres cosas:
> 1. Qué vertical y tier tiene el tenant actual (desde `TenantContextService`).
> 2. Qué límite o feature necesita verificar (parámetro de la llamada).
> 3. Cómo contar los recursos actuales del tenant (query a la BD).
>
> TODO lo demás lo resuelve `PlanResolverService`.

**Métodos principales:**

```php
public function canCreatePage(): bool {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) return FALSE;

    $vertical = $tenant->get('vertical')->value ?? '_default';
    $tier = $tenant->get('plan_type')->value ?? 'starter';

    $limit = $this->planResolver->checkLimit($vertical, $tier, 'max_pages');

    if ($limit === -1) return TRUE;  // Ilimitado
    if ($limit === 0) return FALSE;  // Deshabilitado

    return $this->countTenantPages($tenant->id()) < $limit;
}

public function canUseFeature(string $featureKey): bool {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) return FALSE;

    $features = $this->planResolver->getFeatures(
        $tenant->get('vertical')->value ?? '_default',
        $tenant->get('plan_type')->value ?? 'starter'
    );

    return $features ? $features->hasFeature($featureKey) : FALSE;
}

// Método genérico para cualquier límite numérico
public function checkQuota(string $limitKey, int $currentCount): bool {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) return FALSE;

    $limit = $this->planResolver->checkLimit(
        $tenant->get('vertical')->value ?? '_default',
        $tenant->get('plan_type')->value ?? 'starter',
        $limitKey
    );

    if ($limit === -1) return TRUE;
    if ($limit === 0) return FALSE;

    return $currentCount < $limit;
}
```

**Eliminación del config propio:**

El archivo `jaraba_page_builder.settings.yml` contenía una sección `plan_limits` con valores hardcodeados. Esta sección se elimina completamente:

```yaml
# ANTES (jaraba_page_builder.settings.yml):
plan_limits:
  starter:
    max_pages: 5
  professional:
    max_pages: 25
  enterprise:
    max_pages: -1

# DESPUÉS: Se elimina la sección plan_limits entera.
# Los límites se leen desde SaasPlanFeatures via PlanResolverService.
```

### 8.2 REM-P2-02: Feature Flags Analytics

**Descripción extensa:**

Se implementa un mecanismo para trackear qué features se verifican y cuáles se deniegan, permitiendo al equipo de producto entender los patrones de uso y optimizar los planes.

```php
// En QuotaManagerService::canUseFeature()
if (!$result) {
    \Drupal::service('ecosistema_jaraba_core.analytics')->trackEvent(
        'feature_denied',
        [
            'tenant_id' => $tenant->id(),
            'feature' => $featureKey,
            'current_tier' => $tier,
            'vertical' => $vertical,
        ]
    );
}
```

### 8.3 REM-P2-03: Validación Pre-Deploy

**Descripción extensa:**

Se crea un Drush command que valida la completitud de la configuración de planes antes de un deploy a producción:

```php
/**
 * Validates that all vertical+tier combinations have feature configs.
 *
 * @command jaraba:validate-plans
 * @aliases jvp
 */
public function validatePlans(): void {
    $verticals = ['empleabilidad', 'emprendimiento', 'agroconecta',
                  'comercioconecta', 'serviciosconecta'];
    $tiers = ['starter', 'professional', 'enterprise'];
    $storage = $this->entityTypeManager->getStorage('saas_plan_features');
    $missing = [];

    foreach ($verticals as $v) {
        foreach ($tiers as $t) {
            $id = $v . '_' . $t;
            $entity = $storage->load($id);
            $default = $storage->load('_default_' . $t);
            if (!$entity && !$default) {
                $missing[] = $id;
            }
        }
    }

    if (empty($missing)) {
        $this->io()->success('All vertical+tier combinations have feature configs.');
    } else {
        $this->io()->error('Missing configs: ' . implode(', ', $missing));
        $this->io()->note('Configure at: /admin/config/jaraba/plan-features');
        throw new \RuntimeException('Plan validation failed.');
    }
}
```

### 8.4 REM-P2-04: Frontend de Gestión de Planes (Admin Center)

**Descripción extensa:**

El frontend del Admin Center para gestión de planes sigue los estándares de theming del proyecto:

**Template Twig parcial para la tabla de planes (`_admin-plans-table.html.twig`):**

Este parcial se reutiliza tanto en el Admin Center como en el dashboard de billing.

```twig
{# @file partials/_admin-plans-table.html.twig #}
{# Parcial: Tabla de planes SaaS con badges de color y acciones en modal #}

<div class="plans-admin" data-component="plans-admin">
  <div class="plans-admin__header">
    <h2 class="plans-admin__title">{{ 'SaaS Plans'|t }}</h2>
    <button class="plans-admin__add-btn ej-btn ej-btn--primary"
            data-modal-url="{{ path('entity.saas_plan_tier.add_form') }}"
            aria-label="{{ 'Add new plan'|t }}">
      {{ jaraba_icon('ui', 'plus', {size: '16px'}) }}
      <span>{{ 'Add Plan'|t }}</span>
    </button>
  </div>

  <div class="plans-admin__grid">
    {% for tier in tiers %}
      <div class="plans-admin__card ej-card ej-card--hoverable"
           style="--plan-badge-color: {{ tier.badge_color }}">
        <div class="plans-admin__card-badge"
             style="background-color: {{ tier.badge_color }}">
          {{ tier.label }}
        </div>
        <div class="plans-admin__card-body">
          <h3 class="plans-admin__card-title">{{ tier.label }}</h3>
          <p class="plans-admin__card-desc">{{ tier.description }}</p>
          <div class="plans-admin__card-meta">
            <span class="plans-admin__card-status {{ tier.is_active ? 'is-active' : 'is-inactive' }}">
              {{ tier.is_active ? 'Active'|t : 'Inactive'|t }}
            </span>
            <span class="plans-admin__card-aliases">
              {{ tier.aliases|join(', ') }}
            </span>
          </div>
        </div>
        <div class="plans-admin__card-actions">
          <a href="{{ path('entity.saas_plan_tier.edit_form', {saas_plan_tier: tier.id}) }}"
             class="ej-btn ej-btn--outline ej-btn--sm"
             data-modal-trigger
             aria-label="{{ 'Edit plan @name'|t({'@name': tier.label}) }}">
            {{ jaraba_icon('actions', 'edit', {size: '14px'}) }}
            {{ 'Edit'|t }}
          </a>
        </div>
      </div>
    {% endfor %}
  </div>
</div>
```

**Directrices aplicadas en el template:**

1. **`jaraba_icon()`** para todos los iconos (no SVGs inline).
2. **`|t`** para todos los textos de interfaz (traducibles).
3. **`data-modal-trigger`** para abrir formularios de edición en modal.
4. **CSS Custom Properties** para el color del badge (`--plan-badge-color`).
5. **BEM naming** para las clases CSS (`plans-admin__card-badge`).
6. **`aria-label`** para accesibilidad.
7. **No se usan regiones ni bloques de Drupal** — contenido inyectado directamente.

---

## 9. Fase 4: CI/CD + Tests de Contrato (Días 51–60)

**Esfuerzo estimado: 35–45 horas**

### 9.1 Tests de Contrato Obligatorios

**Descripción extensa:**

Los tests de contrato verifican invariantes estructurales que deben cumplirse en todo momento. No testean funcionalidad sino que aseguran que el código no contiene anti-patrones.

**Test 1: Todas las verticales tienen features configuradas**

```php
public function testAllVerticalsHaveFeatureConfigs(): void {
    $verticals = ['empleabilidad', 'emprendimiento', 'agroconecta',
                  'comercioconecta', 'serviciosconecta'];
    $tiers = ['starter', 'professional', 'enterprise'];
    $storage = $this->container->get('entity_type.manager')
        ->getStorage('saas_plan_features');

    foreach ($verticals as $v) {
        foreach ($tiers as $t) {
            $id = $v . '_' . $t;
            $entity = $storage->load($id);
            $default = $storage->load('_default_' . $t);
            $this->assertTrue(
                $entity !== NULL || $default !== NULL,
                "No features for $id nor default for $t. Configure at /admin/config/jaraba/plan-features"
            );
        }
    }
}
```

**Test 2: PlanResolverService sin valores hardcodeados**

```php
public function testPlanResolverUsesNoHardcodedValues(): void {
    $ref = new \ReflectionClass(PlanResolverService::class);
    $src = file_get_contents($ref->getFileName());

    $this->assertDoesNotMatchRegularExpression('/[0-9]+\.?[0-9]*\s*€/', $src,
        'PlanResolverService must not contain euro prices');
    $this->assertStringNotContainsString("'max_users'", $src,
        'PlanResolverService must not contain limit keys');
    $this->assertStringNotContainsString("'storage_gb'", $src,
        'PlanResolverService must not contain limit keys');
}
```

**Test 3: QuotaManagerService sin arrays de límites**

```php
public function testQuotaManagerDelegatesToPlanResolver(): void {
    $ref = new \ReflectionClass(QuotaManagerService::class);
    $src = file_get_contents($ref->getFileName());

    // No debe contener arrays de límites hardcodeados
    $this->assertDoesNotMatchRegularExpression(
        '/\[\s*[\'"]starter[\'"]\s*=>\s*\[/', $src,
        'QuotaManagerService must not contain plan-specific limit arrays'
    );
}
```

**Test 4: Resolución de aliases funcional**

```php
public function testAliasResolution(): void {
    $resolver = $this->container->get('ecosistema_jaraba_core.plan_resolver');

    $this->assertEquals('starter', $resolver->normalize('basico'));
    $this->assertEquals('starter', $resolver->normalize('basic'));
    $this->assertEquals('starter', $resolver->normalize('free'));
    $this->assertEquals('professional', $resolver->normalize('profesional'));
    $this->assertEquals('professional', $resolver->normalize('growth'));
    $this->assertEquals('professional', $resolver->normalize('pro'));
    $this->assertEquals('enterprise', $resolver->normalize('business'));
    $this->assertEquals('enterprise', $resolver->normalize('premium'));
}
```

**Test 5: Cascada de features (específico → default → null)**

```php
public function testFeatureResolutionCascade(): void {
    $resolver = $this->container->get('ecosistema_jaraba_core.plan_resolver');

    // Caso 1: Existe config específica
    $features = $resolver->getFeatures('empleabilidad', 'starter');
    $this->assertNotNull($features);
    $this->assertEquals('empleabilidad', $features->get('vertical'));

    // Caso 2: No existe config específica, usa default
    // (Requiere crear un vertical de prueba sin config)
    $features = $resolver->getFeatures('test_vertical_sin_config', 'starter');
    if ($features) {
        $this->assertEquals('_default', $features->get('vertical'));
    }
}
```

**Test 6: Coherencia de Stripe Price IDs**

```php
public function testStripePriceIdsAreUnique(): void {
    $storage = $this->container->get('entity_type.manager')
        ->getStorage('saas_plan_features');
    $allFeatures = $storage->loadMultiple();
    $priceIds = [];

    foreach ($allFeatures as $features) {
        foreach (['monthly', 'yearly'] as $cycle) {
            $priceId = $features->getStripePriceId($cycle);
            if (!empty($priceId)) {
                $this->assertNotContains($priceId, $priceIds,
                    "Duplicate Stripe Price ID: $priceId in {$features->id()}");
                $priceIds[] = $priceId;
            }
        }
    }
}
```

**Test 7: Tiers activos tienen al menos un features config**

```php
public function testActiveTiersHaveFeatures(): void {
    $tierStorage = $this->container->get('entity_type.manager')
        ->getStorage('saas_plan_tier');
    $featuresStorage = $this->container->get('entity_type.manager')
        ->getStorage('saas_plan_features');

    $activeTiers = $tierStorage->loadByProperties(['is_active' => TRUE]);

    foreach ($activeTiers as $tier) {
        $hasAnyFeatures = FALSE;
        $allFeatures = $featuresStorage->loadMultiple();
        foreach ($allFeatures as $features) {
            if ($features->get('tier') === $tier->id()) {
                $hasAnyFeatures = TRUE;
                break;
            }
        }
        $this->assertTrue($hasAnyFeatures,
            "Active tier '{$tier->id()}' has no features configured");
    }
}
```

**Test 8: Config schemas son válidos**

```php
public function testConfigSchemasAreValid(): void {
    $typed_config = $this->container->get('config.typed');

    // Verificar que los schemas están definidos
    $this->assertTrue(
        $typed_config->hasConfigSchema('ecosistema_jaraba_core.plan_tier.starter'),
        'Schema for plan_tier must exist'
    );
    $this->assertTrue(
        $typed_config->hasConfigSchema('ecosistema_jaraba_core.plan_features.empleabilidad_starter'),
        'Schema for plan_features must exist'
    );
}
```

### 9.2 Pipeline CI Específico

```yaml
# .github/workflows/plan-config-tests.yml
name: Plan Config Contract Tests
on:
  push:
    paths:
      - 'web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlan*'
      - 'web/modules/custom/ecosistema_jaraba_core/src/Service/PlanResolver*'
      - 'web/modules/custom/ecosistema_jaraba_core/config/install/*plan*'
      - 'web/modules/custom/jaraba_page_builder/src/Service/QuotaManager*'

jobs:
  contract-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run contract tests
        run: |
          vendor/bin/phpunit \
            --group plan-config-contract \
            --testdox
```

### 9.3 Auditoría Final por Vertical

**Checklist de auditoría por vertical:**

Para cada vertical (Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta):

- [ ] Existen los 3 archivos YAML de features (starter, professional, enterprise)
- [ ] Los límites numéricos son coherentes con el Doc 158
- [ ] Los feature flags activan features que el módulo del vertical realmente implementa
- [ ] Los Stripe Product IDs están configurados en el SaasPlanTier
- [ ] El Stripe Price ID (monthly + yearly) está configurado en cada SaasPlanFeatures
- [ ] El `platform_fee_percent` coincide con la tabla del Doc 158
- [ ] Un tenant de prueba con ese plan puede acceder a las features habilitadas
- [ ] Un tenant de prueba con ese plan NO puede exceder los límites configurados

---

## 10. Arquitectura de Theming y Frontend para Plan Admin UI

### 10.1 Modelo SASS: Archivos SCSS con Variables Inyectables

**Directriz fundamental (del documento maestro de arquitectura de theming):**

> Los módulos satélite **NO DEBEN** definir variables SCSS `$ej-*`. Solo consumen CSS Custom Properties con fallbacks inline: `var(--ej-*, #fallback)`.

**Para los estilos del Admin UI de planes se crea un parcial SCSS:**

Archivo: `web/modules/custom/ecosistema_jaraba_core/scss/_plan-admin.scss`

```scss
/**
 * @file
 * Styles for SaaS Plan Admin UI.
 *
 * DIRECTRIZ: Usa Design Tokens con CSS Custom Properties (var(--ej-*))
 * NO definir variables $ej-* en este archivo.
 *
 * COMPILACIÓN:
 * docker exec jarabasaas_appserver_1 bash -c \
 *   "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"
 */

@use 'sass:color';

// === PLAN ADMIN TABLE ===
.plans-admin {
  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--ej-spacing-lg, 1.5rem);
    flex-wrap: wrap;
    gap: var(--ej-spacing-md, 1rem);
  }

  &__title {
    font-family: var(--ej-font-family, 'Inter', sans-serif);
    font-size: var(--ej-font-size-2xl, 1.5rem);
    font-weight: 700;
    color: var(--ej-text-primary, #212121);
    margin: 0;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--ej-spacing-lg, 1.5rem);
  }

  &__card {
    background: var(--ej-bg-card, #ffffff);
    border: 1px solid var(--ej-border-color, #e0e0e0);
    border-radius: var(--ej-border-radius, 10px);
    padding: var(--ej-spacing-lg, 1.5rem);
    transition: var(--ej-transition, all 250ms cubic-bezier(0.4, 0, 0.2, 1));
    position: relative;
    overflow: hidden;

    &:hover {
      box-shadow: var(--ej-shadow-md, 0 4px 16px rgba(0, 0, 0, 0.10));
      transform: translateY(-2px);
    }
  }

  &__card-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--ej-border-radius-full, 9999px);
    color: #ffffff;
    font-size: var(--ej-font-size-sm, 0.875rem);
    font-weight: 600;
    background-color: var(--plan-badge-color, var(--ej-color-primary, #00A9A5));
    margin-bottom: var(--ej-spacing-md, 1rem);
  }

  &__card-status {
    font-size: var(--ej-font-size-xs, 0.75rem);
    padding: 2px 8px;
    border-radius: var(--ej-border-radius-sm, 6px);

    &.is-active {
      background: color-mix(in srgb, var(--ej-color-success, #43A047) 15%, white);
      color: var(--ej-color-success, #43A047);
    }

    &.is-inactive {
      background: var(--ej-gray-100, #f5f5f5);
      color: var(--ej-gray-500, #9e9e9e);
    }
  }

  &__card-actions {
    margin-top: var(--ej-spacing-md, 1rem);
    display: flex;
    gap: var(--ej-spacing-sm, 0.5rem);
  }
}

// === RESPONSIVE (Mobile-first) ===
@media (max-width: 576px) {
  .plans-admin {
    &__grid {
      grid-template-columns: 1fr;
    }

    &__header {
      flex-direction: column;
      align-items: stretch;
    }
  }
}
```

**Verificación de cumplimiento SCSS:**
- No hay ningún `$ej-*` definido en este archivo.
- Todos los valores usan `var(--ej-*, fallback)`.
- Se usa `color-mix()` (CSS nativo) en lugar de `darken()`/`lighten()` (Sass deprecated).
- Se incluye el header de documentación con comando de compilación Docker.

### 10.2 Templates Twig Limpias y Parciales Reutilizables

**Directriz TPL-PAGE-001:**
> Las page templates (`page--*.html.twig`) NO DEBEN contener `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`.

**Verificación de parciales existentes antes de crear nuevos:**

| Parcial existente | Reutilizable aquí | Uso |
|-------------------|-------------------|-----|
| `_header.html.twig` | Si | Header del tema en la página de admin |
| `_footer.html.twig` | Si | Footer del tema |
| `_copilot-fab.html.twig` | Si | FAB de copiloto contextual |
| `_avatar-nav.html.twig` | Si | Navegación por avatar |

**Parciales NUEVOS necesarios para esta implementación:**

| Parcial nuevo | Ubicación | Propósito | Incluido en |
|---------------|-----------|-----------|-------------|
| `_admin-plans-table.html.twig` | `templates/partials/` | Grid de cards de planes SaaS | Admin Center plan management page |
| `_admin-plan-features-matrix.html.twig` | `templates/partials/` | Matriz de features por vertical+tier | Admin Center features page |
| `_pricing-card.html.twig` | `templates/partials/` | Card individual de pricing (frontend) | Pricing page, GrapesJS pricing block |

**Estructura de inclusión:**

```twig
{# page--admin-plans.html.twig (Página de administración de planes) #}
{# DIRECTRIZ TPL-PAGE-001: Sin DOCTYPE/html/body #}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main class="admin-plans-page page-wrapper--clean" role="main">
  <div class="admin-plans-page__container">
    {% include '@ecosistema_jaraba_theme/partials/_admin-plans-table.html.twig'
       with {tiers: tiers} only %}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
{% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' %}
```

### 10.3 Layout Full-Width y Mobile-First

**Directriz de layout:**
> El SaaS debe tener control absoluto sobre el frontend limpio, usando layout full-width y pensado para móvil.

**Implementación CSS:**
```scss
.admin-plans-page {
  width: 100%;
  max-width: 100vw;
  padding: 0;
  margin: 0;

  &__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--ej-spacing-md, 1rem);

    @media (min-width: 768px) {
      padding: var(--ej-spacing-xl, 2rem);
    }
  }
}
```

### 10.4 Patrón Modal para Acciones CRUD

**Directriz:**
> Todas las acciones de crear/editar/ver en una página de frontend deben abrirse en un modal.

**Implementación con el slide-panel existente:**

El tema ya incluye una librería `slide-panel` para modales deslizantes. Los formularios de plan se cargan en este slide-panel:

```javascript
// Comportamiento Drupal para cargar formularios en modal
(function (Drupal) {
  'use strict';

  Drupal.behaviors.planAdminModals = {
    attach: function (context) {
      const triggers = context.querySelectorAll('[data-modal-trigger]');
      triggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
          e.preventDefault();
          const url = trigger.getAttribute('href') || trigger.dataset.modalUrl;
          if (url) {
            Drupal.behaviors.slidePanel.open(url);
          }
        });
      });
    }
  };
})(Drupal);
```

### 10.5 Body Classes via hook_preprocess_html()

**Directriz TPL-BODY-001:**
> Body classes DEBEN asignarse SOLO en `hook_preprocess_html()`, NUNCA en page templates.

**Implementación en `ecosistema_jaraba_theme.theme`:**

```php
// En hook_preprocess_html():
$route = (string) \Drupal::routeMatch()->getRouteName();

// Plan admin pages
if (str_starts_with($route, 'entity.saas_plan_tier.')) {
  $variables['attributes']['class'][] = 'page-wrapper--clean';
  $variables['attributes']['class'][] = 'page--admin-plans';
}
if (str_starts_with($route, 'entity.saas_plan_features.')) {
  $variables['attributes']['class'][] = 'page-wrapper--clean';
  $variables['attributes']['class'][] = 'page--admin-plan-features';
}
```

> **IMPORTANTE:** Siempre castear `getRouteName()` a `(string)` para evitar `TypeError` en páginas 404 donde el nombre de ruta puede ser `NULL`.

### 10.6 Directriz de Iconos: jaraba_icon()

**Directriz TPL-ICON-EXE-001:**
> Todos los iconos DEBEN renderizarse con `jaraba_icon(category, name, options)`. SVGs inline solo se aceptan para ilustraciones decorativas multi-color con CSS `var()` y opacidades diferenciadas, con comentario de exención.

**Uso correcto en templates de planes:**

```twig
{# Iconos correctos #}
{{ jaraba_icon('ui', 'settings', {size: '20px'}) }}
{{ jaraba_icon('actions', 'edit', {size: '14px'}) }}
{{ jaraba_icon('ui', 'plus', {size: '16px'}) }}
{{ jaraba_icon('analytics', 'gauge', {size: '18px', color: 'innovation'}) }}

{# INCORRECTO - NO hacer esto: #}
{# <svg xmlns="..."><path d="..."/></svg> #}
```

**Categorías de iconos disponibles:**
- `business`: diagnostic, gauge, check, company, mentor
- `analytics`: gauge, chart, trend, funnel
- `actions`: edit, delete, check, plus, download
- `ai`: brain, copilot, sparkle
- `ui`: settings, menu, search, filter, close, plus
- `verticals`: empleabilidad, emprendimiento, agroconecta

### 10.7 Directriz de Textos Traducibles

**Directriz:**
> Todos los textos de la interfaz DEBEN ser siempre traducibles.

**En PHP (controladores y formularios):**
```php
// CORRECTO:
$form['label'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Plan name (EN)'),
    '#description' => $this->t('Canonical name in English.'),
];

// INCORRECTO:
$form['label'] = [
    '#type' => 'textfield',
    '#title' => 'Plan name (EN)',  // NO traducible
];
```

**En Twig (templates):**
```twig
{# CORRECTO: #}
<h2>{{ 'SaaS Plans'|t }}</h2>
<button>{{ 'Add Plan'|t }}</button>
<span>{{ 'Active'|t }}</span>

{# CORRECTO con variables: #}
{{ 'Edit plan @name'|t({'@name': tier.label}) }}

{# INCORRECTO: #}
<h2>SaaS Plans</h2>  {# NO traducible #}
```

**En JavaScript:**
```javascript
// CORRECTO:
const label = Drupal.t('Save changes');
const message = Drupal.t('Plan @name updated.', {'@name': planName});

// INCORRECTO:
const label = 'Save changes';  // NO traducible
```

### 10.8 Dart Sass Moderno

**Directriz:**
> Usar `@use 'sass:color'` y `color.adjust()`. Cero funciones deprecadas.

**Ejemplo correcto:**
```scss
@use 'sass:color';
@use 'sass:math';

// CORRECTO:
.button-hover {
  background: color.adjust(#FF8C42, $lightness: -10%);
}

// MEJOR AÚN (CSS nativo, sin Sass):
.button-hover {
  background: color-mix(in srgb, var(--ej-color-primary) 85%, black);
}

// INCORRECTO (deprecado):
.button-hover {
  background: darken(#FF8C42, 10%);  // NO usar
}
```

**Compilación desde Docker:**
```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/ecosistema_jaraba_core && \
   npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"
```

### 10.9 Paleta de Colores Oficial

La paleta oficial del proyecto Jaraba, definida en `_variables.scss`:

| Token | Valor Hex | Uso |
|-------|-----------|-----|
| `--ej-color-corporate` | `#233D63` | Azul corporativo profundo |
| `--ej-color-impulse` | `#FF8C42` | Naranja de impulso/acción |
| `--ej-color-innovation` | `#00A9A5` | Verde-azulado innovación |
| `--ej-color-agro` | `#556B2F` | Verde oliva AgroConecta |
| `--ej-color-primary` | Inyectable per-tenant | Color principal de marca |
| `--ej-color-secondary` | Inyectable per-tenant | Color secundario |
| `--ej-color-success` | `#43A047` | Éxito/confirmación |
| `--ej-color-warning` | `#FFA000` | Advertencia |
| `--ej-color-error` | `#E53935` | Error/peligro |
| `--ej-color-info` | `#1976D2` | Información |

**Badge colors por plan (del seed data):**
- Starter: `#00A9A5` (innovation/verde-azulado)
- Professional: `#FF8C42` (impulse/naranja)
- Enterprise: `#6B3FA0` (púrpura premium)

---

## 11. Integración con GrapesJS (Page Builder)

### 11.1 Bloques de Pricing en GrapesJS

**Descripción extensa:**

Se registra un bloque de pricing dinámico en GrapesJS que obtiene los datos de los planes desde las Config Entities via API REST, permitiendo al usuario insertar una tabla de pricing actualizada en cualquier página del Page Builder.

**Componente GrapesJS:**

```javascript
// grapesjs-jaraba-pricing.js
editor.DomComponents.addType('jaraba-pricing-dynamic', {
  isComponent(el) {
    return el.getAttribute && el.getAttribute('data-jaraba') === 'pricing-dynamic';
  },
  model: {
    defaults: {
      tagName: 'section',
      classes: ['jaraba-pricing-section'],
      attributes: { 'data-jaraba': 'pricing-dynamic' },
      draggable: '.page-content, section',
      droppable: false,
      // GRAPEJS-001: Properties en defaults para changeProp
      vertical: 'empleabilidad',
      billingCycle: 'monthly',
      showEnterprise: true,
      traits: [
        {
          name: 'vertical',
          label: 'Vertical',
          type: 'select',
          options: [
            { id: 'empleabilidad', name: 'Empleabilidad' },
            { id: 'emprendimiento', name: 'Emprendimiento' },
            { id: 'agroconecta', name: 'AgroConecta' },
            { id: 'comercioconecta', name: 'ComercioConecta' },
            { id: 'serviciosconecta', name: 'ServiciosConecta' },
          ],
          changeProp: true,
        },
        {
          name: 'billingCycle',
          label: 'Billing Cycle',
          type: 'select',
          options: [
            { id: 'monthly', name: 'Monthly' },
            { id: 'yearly', name: 'Yearly' },
          ],
          changeProp: true,
        },
        {
          name: 'showEnterprise',
          label: 'Show Enterprise',
          type: 'checkbox',
          changeProp: true,
        },
      ],
      styles: `
        .jaraba-pricing-section {
          padding: 4rem 1rem;
          background: var(--ej-bg-page, linear-gradient(135deg, #FAFAFA, #EEEEEE));
        }
        .jaraba-pricing-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
          gap: 1.5rem;
          max-width: 1200px;
          margin: 0 auto;
        }
      `,
      'script-props': ['vertical', 'billingCycle', 'showEnterprise'],
    },
  },
});
```

### 11.2 Datos Dinámicos desde Config Entities

**API REST para obtener datos de planes:**

Se expone un endpoint REST que el bloque de GrapesJS consume para renderizar datos actualizados:

```php
// Route: /api/v1/plans/{vertical}
// Returns: JSON with tier data for the specified vertical
// Security: _csrf_request_header_token: 'TRUE'
```

### 11.3 Directrices GrapesJS Aplicables

| Directriz | Aplicación |
|-----------|-----------|
| **GRAPEJS-001** | Todos los traits con `changeProp: true` tienen propiedad en `model.defaults` |
| **Nomenclatura** | Bloque registrado como `jaraba-pricing-dynamic` |
| **Dual architecture** | Script property para canvas + Drupal.behaviors para público |
| **Design tokens** | Estilos usan `var(--ej-*)` exclusivamente |
| **PB-DUP-001** | Se verifica `blockManager.get('jaraba-pricing-dynamic')` antes de registrar |

---

## 12. Integración con Entidades de Contenido y Navegación Drupal

### 12.1 Navegación en /admin/structure

Las Config Entities `SaasPlanTier` y `SaasPlanFeatures` se integran en la estructura de administración de Drupal de la siguiente manera:

```
/admin/config/jaraba/
├── plans                    → SaasPlanTier collection (listado de tiers)
│   ├── add                  → Formulario de añadir tier
│   ├── {tier_id}            → Formulario de editar tier
│   └── {tier_id}/delete     → Formulario de eliminar tier
└── plan-features            → SaasPlanFeatures collection (listado de features)
    └── {features_id}        → Formulario de editar features
```

**Nota:** `SaasPlanFeatures` no tiene formulario de creación porque las combinaciones vertical+tier se generan desde los YAML de install y el update hook. El administrador solo puede editarlas, no crearlas libremente, para evitar inconsistencias.

### 12.2 Navegación en /admin/content

Las Config Entities de planes no aparecen en `/admin/content` porque no son Content Entities (no tienen contenido editorial). Sin embargo, los **tenants** sí aparecen en `/admin/content` y muestran el plan activo de cada uno:

```
/admin/content/tenants      → Vista con columna "Plan" que muestra el tier activo
```

### 12.3 Field UI y Views

**Field UI:** Las Config Entities no soportan Field UI de forma nativa (es una limitación de Drupal). Los campos adicionales se añaden como propiedades en la clase PHP y en el schema YAML.

**Views:** Se puede crear una vista personalizada que liste los planes usando un Views query plugin custom, o usar la API de Views Data para exponer las Config Entities a Views.

Para esta implementación, los listados se manejan con los `ListBuilder` nativos de Config Entity, que son más eficientes y apropiados para este caso de uso.

---

## 13. Aislamiento del Tenant: Sin Acceso al Tema de Administración

**Directriz:**
> El tenant no debe tener acceso al tema de administración de Drupal.

**Implementación:**

1. **Rutas de admin protegidas:** El permiso `administer saas plans` se asigna SOLO al rol `platform_admin`, nunca a roles de tenant.
2. **Tema de admin deshabilitado para tenants:** El tenant accede al frontend limpio del SaaS en rutas como `/dashboard`, `/content-hub`, etc. Estas rutas usan templates `page--*.html.twig` con layout limpio (sin sidebar de admin).
3. **Verificación de acceso:** En `hook_preprocess_html()` se detecta si el usuario actual es admin de plataforma:
   ```php
   $is_platform_admin = $current_user->hasPermission('administer saas plans');
   $variables['attributes']['class'][] = $is_platform_admin
       ? 'user--platform-admin'
       : 'user--tenant';
   ```
4. **CSS condicional:** Los estilos de la barra de admin se ocultan para tenants:
   ```scss
   body.user--tenant {
     #toolbar-administration { display: none; }
   }
   ```

---

## 14. SEO y GEO Consideraciones

**Para la página de pricing pública:**

1. **Schema.org JSON-LD:** Se genera automáticamente un schema `Product` + `Offer` para cada plan, mejorando la indexación:
   ```json
   {
     "@context": "https://schema.org",
     "@type": "Product",
     "name": "Jaraba Empleabilidad Professional",
     "offers": {
       "@type": "Offer",
       "price": "79.00",
       "priceCurrency": "EUR",
       "availability": "https://schema.org/InStock"
     }
   }
   ```

2. **Meta tags:** La página de pricing incluye `og:title`, `og:description` con los planes y precios actualizados.

3. **Hreflang:** Si la página tiene versiones en ES e EN, se incluyen los hreflang correspondientes.

4. **GEO:** Los precios se adaptan por región usando los datos de vertical (e.g., ServiciosConecta tiene comisiones distintas).

---

## 15. Inteligencia Artificial Aplicada

**Integración del AI Copilot con el sistema de planes:**

1. **Recomendación de plan:** El copilot contextual puede sugerir un upgrade cuando detecta que el tenant está cerca de su límite:
   ```
   "Has usado 4 de 5 páginas en tu plan Starter.
   ¿Te gustaría ver los beneficios del plan Professional?"
   ```

2. **Feature discovery:** El copilot informa sobre features disponibles en planes superiores:
   ```
   "El AI Copilot avanzado está disponible en el plan Professional.
   Incluye análisis competitivo, proyecciones financieras y matching engine."
   ```

3. **Datos para el copilot:** `QuotaManagerService::getUsageSummary()` retorna un resumen de uso que el copilot consume.

---

## 16. Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|--------|-------------|---------|-----------|
| 1 | Config Entity corrupta tras import | Baja | Alto | Schema validation + config split por entorno |
| 2 | Stripe Price ID duplicado | Media | Alto | Test de contrato #6 verifica unicidad |
| 3 | Vertical sin features configuradas | Baja | Alto | Drush command `jaraba:validate-plans` en pipeline CI + fallback `_default_*` |
| 4 | Performance en resolución (muchas entities) | Baja | Medio | Lazy initialization + cache agresivo de Drupal para Config Entities |
| 5 | Migración de tenants existentes con plan legacy | Media | Alto | Script de migración que usa `PlanResolverService::normalize()` para cannonicalizar |
| 6 | Drift entre Stripe y Config Entities | Media | Alto | Webhook sync bidireccional + auditoría mensual con Drush command |

---

## 17. Checklist de Verificación Pre-Deploy

### 17.1 Verificación de Código

- [ ] Todos los archivos PHP pasan `php -l` sin errores de sintaxis
- [ ] `PlanResolverService` no contiene arrays de límites ni precios (test #2)
- [ ] `QuotaManagerService` no contiene arrays de planes (test #3)
- [ ] Todos los formularios usan `$this->t()` para labels
- [ ] Todos los templates usan `|t` para textos
- [ ] Los SCSS no definen `$ej-*` variables (solo `var(--ej-*)`)
- [ ] Los SCSS se compilan sin errores con Dart Sass
- [ ] Los iconos usan `jaraba_icon()` (no SVGs inline)
- [ ] Las body classes se asignan en `hook_preprocess_html()`, no en templates

### 17.2 Verificación de Configuración

- [ ] 3 SaasPlanTier (starter, professional, enterprise) existen
- [ ] 15 SaasPlanFeatures (5 verticales × 3 tiers) existen
- [ ] 3 SaasPlanFeatures default (_default_starter, _default_professional, _default_enterprise) existen
- [ ] Los schemas de configuración son válidos (test #8)
- [ ] Los Stripe Price IDs son únicos (test #6)
- [ ] `drush jaraba:validate-plans` pasa sin errores

### 17.3 Verificación Funcional

- [ ] El administrador puede crear un nuevo tier desde UI
- [ ] El administrador puede editar features de un plan desde UI
- [ ] Un tenant con plan Starter NO puede exceder max_pages = 5
- [ ] Un tenant con plan Enterprise (max_pages = -1) puede crear páginas ilimitadas
- [ ] Un tenant con plan Starter NO puede usar AI Copilot (feature flag = false)
- [ ] Un tenant con plan Professional SÍ puede usar AI Copilot (feature flag = true)
- [ ] El webhook de Stripe resuelve correctamente el plan desde el Price ID
- [ ] Los formularios se abren en modal desde la página de listado

### 17.4 Verificación Visual

- [ ] La página de admin de planes se ve correcta en desktop (1200px+)
- [ ] La página de admin de planes se ve correcta en tablet (768px)
- [ ] La página de admin de planes se ve correcta en móvil (375px)
- [ ] Los badges de color muestran el color correcto
- [ ] Los iconos se renderizan correctamente
- [ ] El dark mode no rompe la UI de planes (si está activo)

---

## 18. Comandos de Ejecución en Docker

**Todos los comandos se ejecutan dentro del contenedor Docker via Lando:**

```bash
# === COMPILACIÓN SCSS ===
# Core module (incluye _plan-admin.scss)
lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && \
  npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"

# Tema principal
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npm run build"

# === DRUPAL CACHE ===
lando drush cr

# === UPDATE HOOKS ===
lando drush updatedb -y

# === VALIDACIÓN DE PLANES ===
lando drush jaraba:validate-plans

# === CONFIG EXPORT ===
lando drush config:export -y

# === TESTS ===
lando ssh -c "cd /app && vendor/bin/phpunit \
  --group plan-config-contract \
  --testdox"

# === VERIFICACIÓN PHP SYNTAX ===
lando ssh -c "find /app/web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlan* \
  -name '*.php' -exec php -l {} \;"

# === VERIFICACIÓN SCSS (no $ej-* en módulos satélite) ===
lando ssh -c "grep -rn '\\\$ej-' /app/web/modules/custom/ecosistema_jaraba_core/scss/_plan-admin.scss || echo 'OK: No SCSS variable definitions found'"

# === VERIFICACIÓN ICONOS (no SVGs inline en templates) ===
lando ssh -c "grep -rn '<svg' /app/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_admin-plans-table.html.twig || echo 'OK: No inline SVGs found'"
```

---

## 19. Registro de Cambios

| Fecha | Versión | Autor | Descripción |
|-------|---------|-------|-------------|
| 2026-02-23 | 1.0.0 | Claude (Anthropic) | Plan de implementación detallado para la arquitectura de precios configurables (Remediación v2.1). Incluye: TOC completo, descripciones extensas para equipos futuros, tabla de correspondencia de 17 especificaciones, matriz de cumplimiento de 30+ directrices, 4 fases con desglose de tareas, arquitectura de theming (SCSS SSOT, Dart Sass, templates limpias, iconos, textos traducibles, modales, mobile-first), integración GrapesJS, integración entidades/navegación Drupal, aislamiento tenant, SEO/GEO, IA, riesgos, checklists y comandos Docker. |

---

> **Nota para el equipo técnico:** Este documento es la guía de implementación principal para la Remediación v2.1. Cada sección contiene la justificación técnica, los archivos afectados y las directrices que se deben cumplir. Ante cualquier duda, consultar los documentos fuente referenciados en la tabla de metadata inicial.
>
> **Referencias cruzadas:**
> - `docs/00_DIRECTRICES_PROYECTO.md` — Directrices maestras (v61.0.0)
> - `docs/00_FLUJO_TRABAJO_CLAUDE.md` — Flujo de trabajo IA (v15.0.0)
> - `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` — Arquitectura de theming
> - `docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md` — Especificación GrapesJS
> - `docs/tecnicos/aprendizajes/2026-02-23_html_nesting_fix_icon_directive.md` — Directriz iconos y templates
> - `docs/implementacion/20260223b2-Plan_Remediacion_Definitivo_v2_1_Claude.md` — Especificación fuente
