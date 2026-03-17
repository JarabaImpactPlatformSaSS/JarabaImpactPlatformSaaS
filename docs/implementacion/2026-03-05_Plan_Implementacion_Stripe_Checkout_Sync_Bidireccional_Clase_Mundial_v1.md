# Plan de Implementacion: Stripe Checkout Embebido y Sincronizacion Bidireccional Drupal-Stripe — Clase Mundial

**Fecha de creacion:** 2026-03-05
**Ultima actualizacion:** 2026-03-17
**Autor:** Claude Opus 4.6 (Anthropic) — Arquitecto Senior SaaS / Especialista Stripe Connect
**Version:** 2.0.0
**Categoria:** Plan de Implementacion Estrategico
**Codigo:** STRIPE-CHECKOUT-001
**Estado:** EN PROGRESO (Fases 1-2 completadas, Fases 3-4 pendientes)
**Esfuerzo estimado:** 80-100h (4 fases, 28 acciones)
**Documentos fuente:** Auditoria Gaps Billing v1 (2026-03-05), REM-BILLING-001, Doc 158 (Pricing Matrix), VERT-PRICING-001, Plan Verticalizacion Precios v1
**Directrices aplicables:** `00_DIRECTRICES_PROYECTO.md` v114.0.0, `00_FLUJO_TRABAJO_CLAUDE.md` v63.0.0, `00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` v99.0.0, `CLAUDE.md` v1.2.0
**Modulos afectados:** `jaraba_billing`, `jaraba_foc`, `ecosistema_jaraba_core`, `jaraba_addons`, `ecosistema_jaraba_theme`
**Rutas principales:** `/planes/{vertical_key}` (pricing), `/registro` (onboarding), `/planes/checkout/{plan}` (checkout), `/my-dashboard` (tenant dashboard), `/api/v1/billing/*` (API billing)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Principios rectores](#13-principios-rectores)
   - 1.4 [Metricas de impacto](#14-metricas-de-impacto)
   - 1.5 [Alcance y exclusiones](#15-alcance-y-exclusiones)
   - 1.6 [Filosofia "Sin Humo"](#16-filosofia-sin-humo)
   - 1.7 [Relacion con planes previos](#17-relacion-con-planes-previos)
2. [Diagnostico: Estado Actual vs Estado Objetivo](#2-diagnostico-estado-actual-vs-estado-objetivo)
   - 2.1 [Inventario de lo que ya existe (no duplicar)](#21-inventario-de-lo-que-ya-existe)
   - 2.2 [Gaps criticos identificados](#22-gaps-criticos-identificados)
   - 2.3 [Flujo actual (roto)](#23-flujo-actual-roto)
   - 2.4 [Flujo objetivo (completo)](#24-flujo-objetivo-completo)
   - 2.5 [Riesgos de no implementar](#25-riesgos-de-no-implementar)
3. [Arquitectura de la Solucion](#3-arquitectura-de-la-solucion)
   - 3.1 [Principio fundamental: Drupal como Source of Truth](#31-principio-fundamental-drupal-como-source-of-truth)
   - 3.2 [StripeProductSyncService: Sincronizacion automatica Plan-to-Stripe](#32-stripeproductsyncservice)
   - 3.3 [Checkout Session embebido: Flujo completo](#33-checkout-session-embebido)
   - 3.4 [Auto-provisionamiento de tenant post-checkout](#34-auto-provisionamiento-post-checkout)
   - 3.5 [Ciclo de vida completo de suscripcion](#35-ciclo-de-vida-completo)
   - 3.6 [Modelo de datos y relaciones entre entidades](#36-modelo-de-datos)
   - 3.7 [Patron de inyeccion de dependencias](#37-patron-di)
   - 3.8 [Cascada de tokens CSS para checkout](#38-cascada-tokens-css)
4. [Fase 1 — Sincronizacion Bidireccional Drupal-Stripe (Semana 1)](#4-fase-1-sincronizacion-bidireccional)
   - 4.1 [StripeProductSyncService: Crear/actualizar Products y Prices en Stripe](#41-stripeproductsyncservice)
   - 4.2 [Campos nuevos en SaasPlan: stripe_product_id, stripe_price_yearly_id](#42-campos-nuevos-saasplan)
   - 4.3 [hook_entity_presave() para SaasPlan](#43-hook-entity-presave)
   - 4.4 [hook_update_N() para nuevos campos](#44-hook-update)
   - 4.5 [Drush command: stripe:sync-plans (migracion inicial)](#45-drush-command)
   - 4.6 [SaasPlanTier: Poblado automatico de stripe_price_monthly/yearly](#46-saasplantier-sync)
5. [Fase 2 — Checkout Session Embebido (Semana 2)](#5-fase-2-checkout-session-embebido)
   - 5.1 [CheckoutSessionService: Crear sesiones de Stripe Checkout](#51-checkoutsessionservice)
   - 5.2 [CheckoutController: Ruta /checkout/{plan}](#52-checkoutcontroller)
   - 5.3 [Template Twig: page--checkout.html.twig (Zero Region)](#53-template-twig-checkout)
   - 5.4 [JavaScript: stripe-checkout.js (Embedded Checkout)](#54-javascript-checkout)
   - 5.5 [SCSS: _checkout.scss con tokens inyectables](#55-scss-checkout)
   - 5.6 [Parciales reutilizables: _checkout-header.html.twig, _checkout-summary.html.twig](#56-parciales)
   - 5.7 [Pagina de exito post-checkout: /checkout/success](#57-pagina-exito)
   - 5.8 [Pagina de cancelacion: /checkout/cancel](#58-pagina-cancelacion)
6. [Fase 3 — Auto-Provisionamiento y Lifecycle (Semana 3)](#6-fase-3-auto-provisionamiento)
   - 6.1 [Webhook checkout.session.completed: Provisionamiento completo](#61-webhook-checkout-completed)
   - 6.2 [TenantOnboardingService: Integracion con checkout flow](#62-tenant-onboarding)
   - 6.3 [StripeCustomerService: Auto-creacion en onboarding](#63-auto-creacion-customer)
   - 6.4 [Cambio de plan mid-cycle: Flujo upgrade/downgrade con proration](#64-cambio-plan)
   - 6.5 [Cancelacion: Flujo inmediato y diferido](#65-cancelacion)
   - 6.6 [Reactivacion de suscripcion cancelada](#66-reactivacion)
   - 6.7 [Portal de facturacion del tenant (Stripe Customer Portal)](#67-portal-facturacion)
7. [Fase 4 — Frontend Premium y Experiencia de Usuario (Semana 4)](#7-fase-4-frontend-premium)
   - 7.1 [Pricing page: Integracion con Stripe price_ids reales](#71-pricing-page-integracion)
   - 7.2 [Tenant Dashboard: Widget de suscripcion con estado real](#72-tenant-dashboard-widget)
   - 7.3 [Slide-panel: Gestion de metodos de pago](#73-slide-panel-payment-methods)
   - 7.4 [Slide-panel: Historial de facturas con descarga PDF](#74-slide-panel-facturas)
   - 7.5 [Notificaciones: Trial expiring, payment failed, plan changed](#75-notificaciones)
   - 7.6 [Responsive mobile-first para todo el flujo de checkout](#76-responsive)
   - 7.7 [Accesibilidad WCAG 2.1 AA en checkout y billing](#77-accesibilidad)
8. [Especificaciones Tecnicas Detalladas](#8-especificaciones-tecnicas)
   - 8.1 [StripeProductSyncService: Firma, metodos y logica completa](#81-stripeproductsyncservice-detalle)
   - 8.2 [CheckoutSessionService: Firma, metodos y parametros Stripe](#82-checkoutsessionservice-detalle)
   - 8.3 [CheckoutController: Rutas, permisos y render arrays](#83-checkoutcontroller-detalle)
   - 8.4 [stripe-checkout.js: Stripe.js Embedded Checkout integration](#84-javascript-detalle)
   - 8.5 [SCSS: Estructura BEM, tokens y responsive](#85-scss-detalle)
   - 8.6 [Templates Twig: Variables, parciales y directivas trans](#86-templates-detalle)
   - 8.7 [hook_preprocess_html(): Body classes para checkout](#87-preprocess-html)
   - 8.8 [hook_preprocess_page(): drupalSettings para checkout](#88-preprocess-page)
   - 8.9 [Webhook handlers: Firma y logica de cada evento](#89-webhook-handlers)
   - 8.10 [Drush command: stripe:sync-plans](#810-drush-command)
   - 8.11 [hook_update_N(): Migracion de campos](#811-hook-update)
   - 8.12 [Iconos: Categorias y nombres usados en billing/checkout](#812-iconos)
   - 8.13 [Permisos y roles: RBAC para billing](#813-permisos)
9. [Tabla de Archivos Creados/Modificados](#9-tabla-de-archivos)
10. [Tabla de Correspondencia con Especificaciones Tecnicas](#10-correspondencia-especificaciones)
11. [Tabla de Cumplimiento de Directrices del Proyecto](#11-cumplimiento-directrices)
12. [Verificacion RUNTIME-VERIFY-001](#12-verificacion-runtime)
13. [Plan de Testing](#13-plan-de-testing)
14. [Coherencia con Documentacion Existente](#14-coherencia-documentacion)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Este plan implementa el **flujo completo de pagos SaaS** end-to-end: desde que un admin crea un plan en Drupal hasta que un usuario paga, se provisiona su tenant, y gestiona su suscripcion — todo sin salir de la plataforma y sin intervención manual en el dashboard de Stripe.

Componentes principales:

1. **Sincronización bidireccional Drupal→Stripe**: Cuando un admin crea o edita un SaasPlan en `/admin/structure/saas-plan`, el sistema crea/actualiza automaticamente el Product y Price correspondiente en Stripe. El campo `stripe_price_id` se rellena automaticamente — nunca mas hay que copiar IDs del dashboard de Stripe.

2. **Stripe Checkout embebido**: El formulario de pago de Stripe se renderiza directamente dentro de las paginas del SaaS (no redirige fuera). El usuario selecciona un plan en `/planes/{vertical}`, llega a `/checkout/{plan}`, ve el formulario Stripe embebido con branding del tenant, paga, y su tenant se provisiona automaticamente.

3. **Auto-provisionamiento post-checkout**: Cuando Stripe confirma el pago via webhook `checkout.session.completed`, el sistema crea automaticamente: Tenant entity, Group, Domain Access, Stripe Customer, y activa la suscripcion — sin intervencion humana.

4. **Lifecycle completo de suscripcion**: Cambio de plan (upgrade/downgrade con proration preview), cancelacion (inmediata o diferida), reactivacion, portal de facturacion Stripe, historial de facturas con PDF, gestion de metodos de pago — todo desde slide-panels en el dashboard del tenant.

### 1.2 Por que se implementa

El SaaS tiene la infraestructura de billing **90% construida** (18 servicios, 5 entidades, 13 endpoints REST, webhook handlers, dunning, fiscal delegation) pero el **flujo critico de conversion** esta desconectado:

- Los 40+ planes SaaS configurados en Drupal tienen `stripe_price_id: ''` (vacio)
- No existe endpoint para crear Checkout Sessions
- El JS del frontend referencia `redirectToCheckout(sessionId)` pero el sessionId no tiene origen
- El webhook `checkout.session.completed` tiene handler pero no hay flujo que lo active
- El admin debe crear Products/Prices manualmente en Stripe Dashboard — imposible para un SaaS que escala

**Resultado actual**: Un prospecto puede ver precios en `/planes/agroconecta` pero no puede contratar. El flujo se rompe entre "ver precio" y "pagar".

### 1.3 Principios rectores

| Principio | Aplicacion |
|-----------|-----------|
| **Drupal es el Source of Truth** | Los planes se crean/editan en Drupal. Stripe refleja lo que Drupal define, no al reves. |
| **Sin Humo** | No reinventamos el checkout: usamos Stripe Checkout embebido que maneja 3DS, Apple Pay, Google Pay, validaciones, traducciones y PCI compliance. |
| **Zero Region Policy** | Las paginas de checkout usan templates limpias sin `{{ page.content }}`, con `{{ clean_content }}` y parciales reutilizables. |
| **Mobile-first** | Todo el flujo de checkout, pricing y billing se diseña para movil primero con Dart Sass moderno y breakpoints progresivos. |
| **Textos traducibles** | Cada texto visible usa `{% trans %}`, `$this->t()` o `Drupal.t()`. Sin excepciones. |
| **Tokens inyectables** | Colores y tipografia via `var(--ej-*, fallback)`. El checkout hereda el branding del tenant automaticamente via ThemeTokenService cascade. |
| **Acciones en modal/slide-panel** | Gestion de pago, facturas y metodos de pago se abren en slide-panel desde el dashboard del tenant. El usuario nunca abandona su pagina actual. |
| **TENANT-ISOLATION-ACCESS-001** | Cada operacion de billing verifica que el tenant del usuario coincide con el tenant de la entidad. |

### 1.4 Metricas de impacto

| Metrica | Antes | Despues | Delta |
|---------|-------|---------|-------|
| Tiempo setup plan en Stripe | 15-30 min manual | 0s (automatico) | -100% |
| Conversion pricing→pago | 0% (flujo roto) | Medible via Stripe | ∞ |
| Provisionamiento post-pago | Manual por admin | <5s automatico | -100% esfuerzo |
| Flujos desconectados (GAP-C03) | 5 | 0 | -100% |
| stripe_price_id vacios | 40+ (100%) | 0 (0%) | -100% |

### 1.5 Alcance y exclusiones

**Dentro del alcance:**
- Sincronización Drupal→Stripe de Products/Prices
- Stripe Checkout embebido (no hosted page redirect)
- Auto-provisionamiento de tenant post-checkout
- Cambio de plan, cancelacion, reactivacion
- Portal de facturacion (Stripe Customer Portal)
- Templates, SCSS, JS para checkout y billing
- Tests unitarios, kernel y funcionales

**Fuera del alcance (cubierto por REM-BILLING-001):**
- Dunning automation (GAP-C02) → ya planificado
- Fiscal delegation (GAP-C01) → ya planificado
- Usage metering real (GAP-C04) → ya planificado
- Consolidacion TenantAddon→AddonSubscription (GAP-H01)
- Feature gating en AI/Credentials (GAP-H05/H06)
- IVA/Tax compliance LSSI-CE (GAP-M01)

### 1.6 Filosofia "Sin Humo"

> Cada linea de codigo que escribimos debe tener un efecto visible para el usuario. Si el admin crea un plan y no aparece en Stripe, eso es humo. Si el usuario hace click en "Contratar" y no pasa nada, eso es humo. Este plan elimina todo el humo entre "plan definido" y "tenant pagando".

### 1.7 Relacion con planes previos

| Plan | Codigo | Relacion |
|------|--------|----------|
| Remediacion Billing Commerce Fiscal | REM-BILLING-001 | **Complementario**: Este plan resuelve el flujo de checkout; REM-BILLING resuelve gaps internos (dunning, fiscal, metering) |
| Verticalizacion Planes Precios SaaS | VERT-PRICING-001 | **Dependencia upstream**: Los 40+ planes SaaS configurados son el input de este plan |
| Verticales Componibles Addon Marketplace | VERT-ADDON-001 | **Sinergia**: El checkout puede incluir addons como subscription items |
| Remediacion Onboarding Checkout | Aprendizaje #163 | **Refinamiento**: Este plan implementa lo aprendido en la auditoria de onboarding |
| Spec 158: Pricing Matrix | Doc 158 | **Especificacion fuente**: Precios por vertical y tier documentados |

---

## 2. Diagnostico: Estado Actual vs Estado Objetivo

### 2.1 Inventario de lo que ya existe (no duplicar)

> REGLA CRITICA: Antes de crear codigo nuevo, verificar que no existe ya un servicio, entidad o patron que haga lo mismo. Este inventario es la referencia canonica.

#### Entidades existentes (NO crear nuevas)

| Entidad | Modulo | Tabla | Uso | Estado |
|---------|--------|-------|-----|--------|
| `SaasPlan` | ecosistema_jaraba_core | saas_plan + _field_data | Definicion de planes con precios, features, limits | 100% — 40+ configs |
| `SaasPlanTier` | ecosistema_jaraba_core | config | Normalizacion de tiers con stripe_price_monthly/yearly | 100% — 0 configs seed |
| `SaasPlanFeatures` | ecosistema_jaraba_core | config | Features/limits por vertical+tier | 100% — 30+ configs |
| `BillingCustomer` | jaraba_billing | billing_customer | Mapa Tenant↔Stripe Customer | 100% |
| `BillingInvoice` | jaraba_billing | billing_invoice | Cache facturas Stripe | 100% |
| `BillingPaymentMethod` | jaraba_billing | billing_payment_method | Cache metodos de pago | 100% |
| `BillingUsageRecord` | jaraba_billing | billing_usage_record | Metricas de uso | 100% |
| `Addon` | jaraba_addons | addon | Catalogo de addons | 100% |
| `AddonSubscription` | jaraba_addons | addon_subscription | Suscripciones addon | 100% |

#### Servicios existentes (NO duplicar)

| Servicio | DI Key | Modulo | Metodos clave | Estado |
|----------|--------|--------|---------------|--------|
| `StripeConnectService` | jaraba_foc.stripe_connect | jaraba_foc | `stripeRequest()`, `verifyWebhookSignature()`, `getSecretKey()` | 100% — transporte HTTP |
| `StripeSubscriptionService` | jaraba_billing.stripe_subscription | jaraba_billing | `createSubscription()`, `updateSubscription()`, `cancelSubscription()`, `pauseSubscription()`, `resumeSubscription()`, `syncSubscriptionStatus()` | 100% — con locks |
| `StripeCustomerService` | jaraba_billing.stripe_customer | jaraba_billing | `createOrGetCustomer()`, `attachPaymentMethod()`, `setDefaultPaymentMethod()`, `syncPaymentMethods()` | 100% |
| `StripeInvoiceService` | jaraba_billing.stripe_invoice | jaraba_billing | `syncInvoice()`, `listInvoices()`, `getInvoicePdf()`, `reportUsage()`, `flushUsageToStripe()` | 100% |
| `TenantSubscriptionService` | jaraba_billing.tenant_subscription | jaraba_billing | `startTrial()`, `activateSubscription()`, `changePlan()`, `cancelSubscription()`, `markPastDue()`, `processExpiredSubscriptions()` | 100% — BIZ-002 |
| `DunningService` | jaraba_billing.dunning | jaraba_billing | `startDunning()`, `processDunning()`, `stopDunning()` — 6 pasos | 100% |
| `FiscalInvoiceDelegationService` | jaraba_billing.fiscal_delegation | jaraba_billing | `processFinalizedInvoice()` — VeriFactu/Facturae/E-Factura | 100% |
| `ProrationService` | jaraba_billing.proration | jaraba_billing | `previewProration()` — /invoices/upcoming | 100% |
| `PlanResolverService` | ecosistema_jaraba_core.plan_resolver | ecosistema_jaraba_core | `normalize()`, `getFeatures()`, `getLimit()` | 100% |
| `FeatureAccessService` | jaraba_billing.feature_access | jaraba_billing | `canAccess()` — plan + addons | 100% |
| `PlanValidator` | jaraba_billing.plan_validator | jaraba_billing | `enforceLimit()` | 100% |
| `ThemeTokenService` | jaraba_theming.theme_token | jaraba_theming | `getActiveConfig()`, `generateCss()` | 100% — cascada tenant |

#### Controllers existentes (EXTENDER, no duplicar)

| Controller | Modulo | Rutas | Estado |
|------------|--------|-------|--------|
| `BillingWebhookController` | jaraba_billing | `POST /api/v1/billing/stripe-webhook` — 11 eventos | 100% — incluye checkout.session.completed + auto-provisioning S2-03 |
| `BillingApiController` | jaraba_billing | 13 endpoints REST (subscription CRUD, invoices, payment methods, portal, usage) | 100% |
| `PricingController` | ecosistema_jaraba_core | `GET /planes`, `GET /planes/{vertical_key}`, `GET /api/v1/pricing/{vertical}` | 100% — renderiza pricing pages |
| `StripeController` | ecosistema_jaraba_core | `createSubscription()`, `confirmSubscription()`, `createPortalSession()`, Stripe Connect onboarding | 100% — legacy SDK (a migrar) |
| `TenantSelfServiceController` | ecosistema_jaraba_core | `GET /my-dashboard`, `GET /my-settings` | Parcial — dashboard sin widget billing |

#### Frontend existente

| Recurso | Ubicacion | Estado |
|---------|-----------|--------|
| `ecosistema-jaraba-stripe.js` | ecosistema_jaraba_core/js/ | 100% — Stripe Elements Card, 3DS, payment methods |
| `pricing-page.html.twig` | ecosistema_jaraba_theme/templates/ | 100% — tabla comparativa de planes |
| `pricing-hub-page.html.twig` | ecosistema_jaraba_theme/templates/ | 100% — hub de todos los verticales |
| `_pricing-page.scss` | ecosistema_jaraba_theme/scss/components/ | 100% — estilos pricing |
| `_pricing-hub.scss` | ecosistema_jaraba_theme/scss/components/ | 100% — estilos hub |

### 2.2 Gaps criticos identificados

| Gap | Descripcion | Impacto | Esfuerzo |
|-----|------------|---------|----------|
| **GAP-SYNC-001** | No hay sincronizacion Drupal→Stripe de Products/Prices. Los 40+ planes tienen `stripe_price_id: ''` | Imposible cobrar | 12h |
| **GAP-CHECKOUT-001** | No existe endpoint para crear Checkout Sessions. El JS tiene `redirectToCheckout()` sin origen de sessionId | Flujo de pago roto | 16h |
| **GAP-PROVISION-001** | BillingWebhookController tiene handler para `checkout.session.completed` con auto-provisioning (S2-03), pero no hay checkout que lo active | Auto-provisionamiento inutil | 8h |
| **GAP-FRONTEND-001** | No existe pagina `/checkout/{plan}` con template Zero Region, SCSS mobile-first, ni JS de Stripe.js embebido | Sin experiencia de checkout | 20h |
| **GAP-LIFECYCLE-001** | TenantSubscriptionService tiene metodos completos pero no estan expuestos en UI del tenant dashboard | Tenant no puede gestionar su plan | 12h |
| **GAP-FIELDS-001** | SaasPlan solo tiene `stripe_price_id` (un campo). Faltan `stripe_product_id` y `stripe_price_yearly_id` para sincronizacion completa | Sync incompleta | 4h |

### 2.3 Flujo actual (roto)

```
Admin crea plan en Drupal          Stripe Dashboard (vacio)
        |                                   |
        v                                   |
SaasPlan entity                    No Products, No Prices
(stripe_price_id = '')                      |
        |                                   |
        v                                   |
PricingController → /planes               Usuario ve precios
        |                                   |
        v                                   |
Boton "Contratar"                  ??????
        |                                   |
        X  <-- FLUJO ROTO -->      No hay Checkout Session
                                   No hay Payment
                                   No hay Tenant
```

### 2.4 Flujo objetivo (completo)

```
Admin crea/edita plan en Drupal ──── hook_entity_presave() ────┐
        |                                                       |
        v                                                       v
SaasPlan entity                              StripeProductSyncService
(stripe_product_id = prod_xxx)               ├─ POST /v1/products
(stripe_price_id = price_xxx_monthly)        ├─ POST /v1/prices (monthly)
(stripe_price_yearly_id = price_xxx_yearly)  └─ POST /v1/prices (yearly)
        |
        v
PricingController → /planes/{vertical}      Usuario ve precios reales
        |
        v
Boton "Contratar" → /checkout/{plan_id}     CheckoutController
        |                                    ├─ Crea Checkout Session
        v                                    ├─ Pasa stripe_price_id
Stripe Checkout embebido                     └─ Retorna client_secret
(dentro de la pagina del SaaS)
        |
        v                                    stripe-checkout.js
Usuario paga (3DS, Apple Pay, etc.)          ├─ initEmbeddedCheckout()
        |                                    └─ mount('#checkout-container')
        v
Stripe webhook: checkout.session.completed
        |
        v
BillingWebhookController (S2-03)
├─ Crea BillingCustomer
├─ Crea Tenant + Group + Domain
├─ Activa suscripcion
├─ Envia email bienvenida
└─ Redirect → /my-dashboard

        Tenant operativo en <5 segundos
```

### 2.5 Riesgos de no implementar

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Plataforma sin monetizacion | CERTEZA | CRITICO — 0 ingresos | Este plan |
| Competidores captan usuarios | ALTA | ALTO — market share | Lanzar en <4 semanas |
| Admin fatigue por setup manual Stripe | ALTA | MEDIO — errores en price_ids | Sync automatica |
| Abandono en checkout por redireccion externa | MEDIA | MEDIO — -40% conversion | Checkout embebido |
| SCA/3DS failures por implementacion custom | MEDIA | ALTO — pagos rechazados | Stripe Checkout maneja todo |

---

## 3. Arquitectura de la Solucion

### 3.1 Principio fundamental: Drupal como Source of Truth

El paradigma es unidireccional para definicion de productos/precios:

```
Drupal (Source of Truth) ──────→ Stripe (Payment Processor)
       ←── webhooks ──────────
```

- **Drupal→Stripe**: Cuando un SaasPlan se crea/edita, se sincroniza automaticamente a Stripe via `StripeProductSyncService`. El admin NUNCA toca el dashboard de Stripe para crear precios.
- **Stripe→Drupal**: Los webhooks informan cambios de estado (pago exitoso, fallo, cancelacion). Drupal actualiza el estado local via `TenantSubscriptionService`.
- **Stripe Dashboard**: Solo para monitoreo, reportes y configuracion avanzada (Radar, Tax, etc.). NO para gestion de productos/precios.

### 3.2 StripeProductSyncService: Sincronizacion automatica Plan-to-Stripe

**Ubicacion**: `web/modules/custom/jaraba_billing/src/Service/StripeProductSyncService.php`
**DI Key**: `jaraba_billing.stripe_product_sync`

El servicio se invoca automaticamente desde `hook_entity_presave()` de SaasPlan. Logica:

1. **Si el plan NO tiene `stripe_product_id`** (nuevo):
   - Crea Product en Stripe: `POST /v1/products` con name, description, metadata (vertical, tier)
   - Crea Price monthly: `POST /v1/prices` con product, unit_amount, currency=eur, recurring.interval=month
   - Crea Price yearly: `POST /v1/prices` con product, unit_amount, currency=eur, recurring.interval=year
   - Guarda los 3 IDs en el SaasPlan entity

2. **Si el plan YA tiene `stripe_product_id`** (edicion):
   - Actualiza Product: `POST /v1/products/{id}` con name, metadata
   - Si precio monthly cambio: Archiva Price viejo, crea nuevo, actualiza `stripe_price_id`
   - Si precio yearly cambio: Archiva Price viejo, crea nuevo, actualiza `stripe_price_yearly_id`
   - Los precios archivados siguen siendo validos para suscripciones existentes

3. **Si el plan se desactiva** (status=FALSE):
   - Archiva Product en Stripe: `POST /v1/products/{id}` con active=false
   - NO elimina — las suscripciones existentes continuan

**Patron de idempotencia**: Cada llamada incluye `Idempotency-Key` basado en plan_id + campo + valor. Si la llamada falla y se reintenta, Stripe devuelve el mismo resultado.

### 3.3 Checkout Session embebido: Flujo completo

Se usa **Stripe Embedded Checkout** (no el Stripe.js hosted redirect). El formulario de pago se renderiza directamente dentro de un `<div>` en la pagina del SaaS, manteniendo la experiencia visual integrada.

**Secuencia**:

1. Usuario selecciona plan en `/planes/{vertical}` → click "Contratar"
2. Redirige a `/checkout/{plan_id}?cycle=monthly|yearly`
3. `CheckoutController::checkoutPage()` renderiza template Zero Region con `#checkout-container`
4. `hook_preprocess_page__checkout()` inyecta `drupalSettings.stripeCheckout.publicKey` y `sessionUrl`
5. JS `stripe-checkout.js` carga `@stripe/stripe-js`, llama `stripe.initEmbeddedCheckout()`, monta en `#checkout-container`
6. Backend: `CheckoutSessionService::createSession()` crea Session via Stripe API con:
   - `mode: 'subscription'`
   - `line_items: [{price: stripe_price_id, quantity: 1}]`
   - `ui_mode: 'embedded'`
   - `return_url: /checkout/success?session_id={CHECKOUT_SESSION_ID}`
   - `metadata: {vertical, plan_id, tenant_name, email}`
   - `subscription_data.trial_settings` (si trial activo)
7. Stripe renderiza formulario de pago con 3DS, Apple Pay, Google Pay, SEPA, etc.
8. Usuario paga → Stripe envia `checkout.session.completed` via webhook
9. `BillingWebhookController` provisiona tenant automaticamente (S2-03)
10. Redirect al `return_url` → `/checkout/success` muestra confirmacion

### 3.4 Auto-provisionamiento de tenant post-checkout

El handler `handleCheckoutSessionCompleted()` en `BillingWebhookController` ya implementa el flujo S2-03:

```php
// YA EXISTE — lineas ~340-380 de BillingWebhookController.php
// 1. Extrae metadata de la session (email, business_name, vertical, plan_id)
// 2. Verifica si ya existe un tenant para ese customerId
// 3. Si no: crea tenant via TenantOnboardingService::processRegistration()
// 4. Llama completeOnboarding(tenant, customerId, subscriptionId)
```

**Lo que falta conectar**: El flujo que CREA la Checkout Session para que este webhook se active.

### 3.5 Ciclo de vida completo de suscripcion

```
                    ┌──────────────────────────────┐
                    │      CHECKOUT SESSION         │
                    │  (Stripe Embedded Checkout)   │
                    └──────────┬───────────────────┘
                               │ checkout.session.completed
                               v
                    ┌──────────────────────────────┐
                    │         TRIAL (14 dias)       │
                    │  TenantSubscriptionService    │
                    │  .startTrial()                │
                    └──────────┬───────────────────┘
                               │ trial_will_end (3 dias antes)
                               │ → email reminder
                               v
                    ┌──────────────────────────────┐
            ┌───── │         ACTIVE                │ ─────┐
            │      │  invoice.paid (recurrente)    │      │
            │      └──────────┬───────────────────┘      │
            │                 │                           │
    upgrade/│                 │ invoice.payment_failed    │ cancelar
   downgrade│                 v                           │
            │      ┌──────────────────────────────┐      │
            │      │         PAST_DUE              │      │
            │      │  DunningService (6 pasos)     │      │
            │      │  0→3→7→10→14→21 dias          │      │
            │      └──────────┬───────────────────┘      │
            │                 │                           │
            │         pago recuperado  │  paso 5 (21d)    │
            │              │           │                  │
            v              v           v                  v
    ┌───────────────┐  ┌──────┐  ┌──────────┐  ┌──────────────┐
    │ PLAN CHANGED  │  │ACTIVE│  │ SUSPENDED│  │  CANCELLED   │
    │ proration     │  │(loop)│  │ readonly │  │ cancel_at o  │
    │ preview first │  └──────┘  └──────────┘  │ inmediato    │
    └───────────────┘                          └──────────────┘
                                                       │
                                                       v
                                               ┌──────────────┐
                                               │ REACTIVATE   │
                                               │ (si < 30d)   │
                                               └──────────────┘
```

### 3.6 Modelo de datos y relaciones entre entidades

```
SaasPlan (source of truth)
├── stripe_product_id ──────→ Stripe Product
├── stripe_price_id ────────→ Stripe Price (monthly)
├── stripe_price_yearly_id ─→ Stripe Price (yearly)
├── vertical ───────────────→ Vertical entity
├── features ───────────────→ SaasPlanFeatures (cascade)
└── limits ─────────────────→ JSON

Tenant (subscriber)
├── subscription_plan ──────→ SaasPlan
├── subscription_status ────→ trial|active|past_due|suspended|cancelled
├── stripe_subscription_id ─→ Stripe Subscription
├── trial_ends ─────────────→ datetime
├── grace_period_ends ──────→ datetime
└── cancel_at ──────────────→ datetime

BillingCustomer (billing identity)
├── tenant_id ──────────────→ Tenant (Group)
├── stripe_customer_id ─────→ Stripe Customer
├── billing_email ──────────→ string
└── tax_id ─────────────────→ string (NIF/CIF)

BillingInvoice (invoice cache)
├── tenant_id ──────────────→ Tenant (Group)
├── stripe_invoice_id ──────→ Stripe Invoice
├── status ─────────────────→ draft|open|paid|void
└── pdf_url ────────────────→ Stripe hosted PDF

BillingPaymentMethod (payment method cache)
├── tenant_id ──────────────→ Tenant (Group)
├── stripe_pm_id ───────────→ Stripe PaymentMethod
├── card_brand ─────────────→ visa|mastercard|amex
└── card_last4 ─────────────→ string(4)
```

### 3.7 Patron de inyeccion de dependencias

Todos los servicios nuevos siguen el patron del proyecto:

```yaml
# jaraba_billing.services.yml
jaraba_billing.stripe_product_sync:
  class: Drupal\jaraba_billing\Service\StripeProductSyncService
  arguments:
    - '@?jaraba_foc.stripe_connect'      # Optional cross-module (OPTIONAL-CROSSMODULE-001)
    - '@entity_type.manager'
    - '@logger.channel.jaraba_billing'    # LoggerInterface directamente (LOGGER-INJECT-001)
    - '@lock'                             # Race condition protection (AUDIT-PERF-002)

jaraba_billing.checkout_session:
  class: Drupal\jaraba_billing\Service\CheckoutSessionService
  arguments:
    - '@?jaraba_foc.stripe_connect'      # Optional (OPTIONAL-CROSSMODULE-001)
    - '@?jaraba_billing.stripe_customer'  # Optional (para createOrGet)
    - '@entity_type.manager'
    - '@logger.channel.jaraba_billing'
    - '@config.factory'                   # Para leer trial_days, currency
```

**Reglas DI aplicadas:**
- `@?` para todo servicio cross-modulo (OPTIONAL-CROSSMODULE-001)
- `LoggerInterface` directo con `@logger.channel.*` (LOGGER-INJECT-001)
- Sin dependencias circulares (CONTAINER-DEPS-002)
- Constructor args == services.yml args exactamente (PHANTOM-ARG-001)

### 3.8 Cascada de tokens CSS para checkout

El checkout hereda automaticamente el branding del tenant gracias a la cascada de 5 capas del ThemeTokenService:

```
1. SCSS Variables (_variables.scss)     → $ej-color-corporate: #233D63
2. CSS Custom Properties (:root)        → --ej-color-corporate: #233D63
3. Component Tokens (_checkout.scss)    → background: var(--ej-color-corporate)
4. Tenant Override (ThemeTokenService)  → --ej-color-corporate: #CUSTOM_TENANT_COLOR
5. Vertical Preset (VerticalBrandConfig)→ --ej-vertical-primary: #VERTICAL_COLOR
```

El template de checkout NO hardcodea colores. Todo via `var(--ej-*, fallback)`:

```scss
// _checkout.scss
.checkout__header {
  background: var(--ej-color-corporate, #233D63);
  color: var(--ej-bg-surface, #fff);
}
.checkout__cta {
  background: var(--ej-color-impulse, #FF8C42);
}
```

---

## 4. Fase 1 — Sincronizacion Bidireccional Drupal-Stripe (Semana 1)

### 4.1 StripeProductSyncService: Crear/actualizar Products y Prices en Stripe

**Archivo**: `web/modules/custom/jaraba_billing/src/Service/StripeProductSyncService.php`

**Responsabilidad**: Mantener los Products y Prices de Stripe como reflejo exacto de los SaasPlan entities de Drupal.

**Metodos publicos**:

```php
/**
 * Sincroniza un SaasPlan con Stripe (crea o actualiza Product + Prices).
 *
 * Se invoca automaticamente desde hook_entity_presave() de SaasPlan.
 * Usa locks para prevenir race conditions y idempotency keys para reintentos seguros.
 *
 * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
 *   El plan SaaS a sincronizar.
 *
 * @return array{product_id: string, price_monthly_id: string, price_yearly_id: string}
 *   Los IDs de Stripe generados/actualizados.
 *
 * @throws \RuntimeException
 *   Si la comunicacion con Stripe falla despues de reintentos.
 */
public function syncPlan(SaasPlanInterface $plan): array;

/**
 * Sincroniza TODOS los planes activos a Stripe (bulk).
 *
 * Usado por el Drush command `stripe:sync-plans` para migracion inicial.
 * Procesa planes secuencialmente con delay de 100ms para respetar rate limits.
 *
 * @param bool $force
 *   Si TRUE, re-sincroniza incluso planes que ya tienen stripe_product_id.
 *
 * @return array{synced: int, skipped: int, errors: array}
 *   Resumen de la operacion.
 */
public function syncAllPlans(bool $force = FALSE): array;

/**
 * Archiva un Product en Stripe cuando el plan se desactiva.
 *
 * No elimina el producto — las suscripciones existentes continuan.
 * Solo evita que nuevas suscripciones usen ese precio.
 *
 * @param string $stripeProductId
 *   ID del producto Stripe (prod_xxx).
 */
public function archiveProduct(string $stripeProductId): void;
```

**Logica interna de `syncPlan()`**:

```
1. Adquirir lock "stripe_sync_plan_{plan_id}" (30s timeout)
2. Si plan.stripe_product_id esta vacio:
   a. POST /v1/products {name, metadata: {drupal_plan_id, vertical, tier}}
   b. Guardar product_id en plan entity
3. Si plan.stripe_product_id existe:
   a. POST /v1/products/{id} {name, metadata} (actualizar nombre)
4. Comparar precio monthly actual vs stripe_price_id actual:
   a. Si cambio o no existe:
      - POST /v1/prices {product, unit_amount: price_monthly*100, currency: eur, recurring: {interval: month}}
      - Si habia precio anterior: POST /v1/prices/{old_id} {active: false} (archivar)
      - Guardar nuevo price_id en plan entity
5. Repetir paso 4 para precio yearly con stripe_price_yearly_id
6. Liberar lock
7. Retornar {product_id, price_monthly_id, price_yearly_id}
```

**Importante**: Los precios en Stripe son **inmutables**. No se puede editar un Price existente — hay que archivar el viejo y crear uno nuevo. Las suscripciones existentes con el precio viejo continuan sin cambios (Stripe lo maneja automaticamente).

### 4.2 Campos nuevos en SaasPlan: stripe_product_id, stripe_price_yearly_id

**Archivo a modificar**: `web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlan.php`

Campos nuevos en `baseFieldDefinitions()`:

| Campo | Tipo | Max Length | Descripcion |
|-------|------|-----------|-------------|
| `stripe_product_id` | string | 100 | ID del Product en Stripe (prod_xxx). Se rellena automaticamente por StripeProductSyncService. |
| `stripe_price_yearly_id` | string | 100 | ID del Price yearly en Stripe (price_xxx). El `stripe_price_id` existente pasa a representar monthly. |

**Getters nuevos**:
```php
public function getStripeProductId(): ?string;
public function getStripePriceYearlyId(): ?string;
```

**Nota**: El campo `stripe_price_id` existente se renombraria conceptualmente a "monthly" pero no se modifica fisicamente — solo se anade documentacion.

### 4.3 hook_entity_presave() para SaasPlan

**Archivo a modificar**: `web/modules/custom/jaraba_billing/jaraba_billing.module`

```php
/**
 * Implements hook_entity_presave() for saas_plan.
 *
 * Sincroniza automaticamente el plan con Stripe Products/Prices.
 * Aplica PRESAVE-RESILIENCE-001: servicio opcional + try-catch.
 */
function jaraba_billing_saas_plan_presave(EntityInterface $entity): void {
  // Solo si el servicio existe (modulo habilitado)
  if (!\Drupal::hasService('jaraba_billing.stripe_product_sync')) {
    return;
  }

  // No sincronizar durante imports o syncs
  if ($entity->isSyncing()) {
    return;
  }

  try {
    $syncService = \Drupal::service('jaraba_billing.stripe_product_sync');
    $result = $syncService->syncPlan($entity);

    // Actualizar campos del entity con los IDs de Stripe
    if (!empty($result['product_id'])) {
      $entity->set('stripe_product_id', $result['product_id']);
    }
    if (!empty($result['price_monthly_id'])) {
      $entity->set('stripe_price_id', $result['price_monthly_id']);
    }
    if (!empty($result['price_yearly_id'])) {
      $entity->set('stripe_price_yearly_id', $result['price_yearly_id']);
    }
  }
  catch (\Throwable $e) {
    // PRESAVE-RESILIENCE-001: el save del plan NO debe fallar por Stripe
    \Drupal::logger('jaraba_billing')->error(
      'Stripe sync failed for plan @id: @error',
      ['@id' => $entity->id() ?? 'new', '@error' => $e->getMessage()]
    );
  }
}
```

### 4.4 hook_update_N() para nuevos campos

**Archivo a modificar**: `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.install`

```php
/**
 * Install stripe_product_id and stripe_price_yearly_id fields on saas_plan.
 *
 * UPDATE-HOOK-REQUIRED-001: Campos nuevos en baseFieldDefinitions() requieren
 * hook_update_N() con installFieldStorageDefinition().
 * UPDATE-FIELD-DEF-001: setName() y setTargetEntityTypeId() obligatorios.
 * UPDATE-HOOK-CATCH-001: catch(\Throwable) — no catch(\Exception).
 */
function ecosistema_jaraba_core_update_XXXXX(): string {
  $updateManager = \Drupal::entityDefinitionUpdateManager();
  $entityType = $updateManager->getEntityType('saas_plan');
  if (!$entityType) {
    return 'saas_plan entity type not installed yet.';
  }

  $fields = \Drupal\ecosistema_jaraba_core\Entity\SaasPlan::baseFieldDefinitions($entityType);
  $installed = [];
  $targetFields = ['stripe_product_id', 'stripe_price_yearly_id'];

  foreach ($targetFields as $fieldName) {
    if (!isset($fields[$fieldName])) {
      continue;
    }
    try {
      $existing = $updateManager->getFieldStorageDefinition($fieldName, 'saas_plan');
      if (!$existing) {
        $fields[$fieldName]->setName($fieldName);
        $fields[$fieldName]->setTargetEntityTypeId('saas_plan');
        $updateManager->installFieldStorageDefinition(
          $fieldName, 'saas_plan', 'ecosistema_jaraba_core', $fields[$fieldName]
        );
        $installed[] = $fieldName;
      }
    }
    catch (\Throwable $e) {
      return "Error installing $fieldName: " . $e->getMessage();
    }
  }

  return 'Installed fields on saas_plan: ' . (empty($installed) ? 'none needed' : implode(', ', $installed));
}
```

### 4.5 Drush command: stripe:sync-plans (migracion inicial)

**Archivo nuevo**: `web/modules/custom/jaraba_billing/src/Commands/StripeSyncCommands.php`

Comando Drush para la sincronizacion inicial de los 40+ planes existentes:

```bash
lando drush stripe:sync-plans                    # Sincroniza solo planes sin stripe_product_id
lando drush stripe:sync-plans --force             # Re-sincroniza todos los planes
lando drush stripe:sync-plans --dry-run           # Muestra que haria sin ejecutar
lando drush stripe:sync-plans --vertical=agroconecta  # Solo planes de un vertical
```

**Logica**:
1. Carga todos los SaasPlan con status=TRUE
2. Filtra por vertical si se especifica
3. Para cada plan: llama `StripeProductSyncService::syncPlan()`
4. Muestra progreso: `[12/40] Synced: AgroConecta Profesional → prod_xxx, price_xxx`
5. Resumen final: `Synced: 38, Skipped: 2, Errors: 0`

### 4.6 SaasPlanTier: Poblado automatico de stripe_price_monthly/yearly

Tras sincronizar planes, los SaasPlanTier ConfigEntities pueden auto-poblarse:

El Drush command `stripe:sync-plans` incluye flag `--update-tiers` que:
1. Para cada tier (starter, professional, enterprise)
2. Busca el SaasPlan correspondiente del tier default (`_default` vertical)
3. Copia `stripe_price_id` → `stripe_price_monthly` del tier
4. Copia `stripe_price_yearly_id` → `stripe_price_yearly` del tier
5. Guarda el ConfigEntity

---

## 5. Fase 2 — Checkout Session Embebido (Semana 2)

### 5.1 CheckoutSessionService: Crear sesiones de Stripe Checkout

**Archivo nuevo**: `web/modules/custom/jaraba_billing/src/Service/CheckoutSessionService.php`
**DI Key**: `jaraba_billing.checkout_session`

**Responsabilidad**: Crear Stripe Checkout Sessions con la configuracion correcta para suscripciones SaaS.

**Metodo principal**:

```php
/**
 * Crea una Stripe Checkout Session para suscripcion a un plan SaaS.
 *
 * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
 *   El plan a suscribir.
 * @param string $billingCycle
 *   'monthly' o 'yearly'.
 * @param string $customerEmail
 *   Email del prospecto (se usara para crear/recuperar Stripe Customer).
 * @param string $businessName
 *   Nombre de la empresa (metadata para auto-provisioning).
 * @param string $vertical
 *   Machine name del vertical (metadata para auto-provisioning).
 * @param string|null $existingCustomerId
 *   Si el prospecto ya tiene un Stripe Customer ID (upgrade flow).
 *
 * @return array{client_secret: string, session_id: string}
 *   client_secret para Stripe.js initEmbeddedCheckout().
 */
public function createSession(
  SaasPlanInterface $plan,
  string $billingCycle,
  string $customerEmail,
  string $businessName,
  string $vertical,
  ?string $existingCustomerId = NULL,
): array;
```

**Parametros Stripe API**:

```php
$params = [
  'mode' => 'subscription',
  'ui_mode' => 'embedded',
  'line_items' => [[
    'price' => $billingCycle === 'yearly'
      ? $plan->getStripePriceYearlyId()
      : $plan->getStripePriceId(),
    'quantity' => 1,
  ]],
  'return_url' => $returnUrl . '?session_id={CHECKOUT_SESSION_ID}',
  'subscription_data' => [
    'metadata' => [
      'drupal_plan_id' => $plan->id(),
      'vertical' => $vertical,
      'business_name' => $businessName,
      'email' => $customerEmail,
    ],
    'trial_period_days' => $this->getTrialDays(),
  ],
  'customer_email' => $customerEmail,
  'metadata' => [
    'drupal_plan_id' => $plan->id(),
    'vertical' => $vertical,
    'business_name' => $businessName,
  ],
];
```

### 5.2 CheckoutController: Ruta /checkout/{plan}

**Archivo nuevo**: `web/modules/custom/jaraba_billing/src/Controller/CheckoutController.php`

**Rutas**:

| Metodo | Ruta | Handler | Permiso |
|--------|------|---------|---------|
| GET | `/checkout/{saas_plan}` | `checkoutPage()` | `_access: 'TRUE'` (publico) |
| POST | `/api/v1/billing/checkout-session` | `createCheckoutSession()` | `_access: 'TRUE'` + CSRF |
| GET | `/checkout/success` | `checkoutSuccess()` | `_access: 'TRUE'` |
| GET | `/checkout/cancel` | `checkoutCancel()` | `_access: 'TRUE'` |

**`checkoutPage()`**: Renderiza la pagina de checkout con Zero Region Policy.

```php
public function checkoutPage(SaasPlanInterface $saas_plan, Request $request): array {
  $cycle = $request->query->get('cycle', 'monthly');
  $priceId = $cycle === 'yearly'
    ? $saas_plan->getStripePriceYearlyId()
    : $saas_plan->getStripePriceId();

  if (empty($priceId)) {
    throw new NotFoundHttpException();
  }

  return [
    '#theme' => 'checkout_page',
    '#plan' => [
      'id' => $saas_plan->id(),
      'name' => $saas_plan->getName(),
      'price' => $cycle === 'yearly'
        ? $saas_plan->getPriceYearly()
        : $saas_plan->getPriceMonthly(),
      'cycle' => $cycle,
      'features' => $saas_plan->getFeatures(),
      'vertical' => $saas_plan->getVertical()?->label(),
    ],
    '#attached' => [
      'library' => [
        'jaraba_billing/stripe-checkout',
      ],
      'drupalSettings' => [
        'stripeCheckout' => [
          'publicKey' => $this->getStripePublicKey(),
          'sessionUrl' => Url::fromRoute('jaraba_billing.checkout_session.create')->toString(),
          'planId' => $saas_plan->id(),
          'cycle' => $cycle,
        ],
      ],
    ],
    '#cache' => [
      'max-age' => 0,
    ],
  ];
}
```

**CONTROLLER-READONLY-001**: El controller NO usa `readonly` en constructor promotion para `$entityTypeManager`. Se asigna manualmente en el body del constructor.

### 5.3 Template Twig: page--checkout.html.twig (Zero Region)

**Archivo nuevo**: `web/themes/custom/ecosistema_jaraba_theme/templates/page--checkout.html.twig`

Sigue Zero Region Policy (ZERO-REGION-001/002/003):

```twig
{#
/**
 * @file
 * page--checkout.html.twig — Pagina de Checkout (Zero Region Policy).
 *
 * Layout: full-width, mobile-first, sin sidebar.
 * CSS: var(--ej-*) tokens inyectables via ThemeTokenService cascade.
 * i18n: {% trans %} para todos los textos.
 */
#}

{{ attach_library('ecosistema_jaraba_theme/global') }}

<div class="checkout-page">
  {% include '@ecosistema_jaraba_theme/partials/_checkout-header.html.twig' %}

  <main class="checkout-page__main" role="main">
    {% if clean_messages %}
      <div class="checkout-page__messages">
        {{ clean_messages }}
      </div>
    {% endif %}

    <div class="checkout-page__content">
      {{ clean_content }}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_checkout-footer.html.twig' %}
</div>
```

**Body classes via hook_preprocess_html()** (NO attributes.addClass()):

```php
// En ecosistema_jaraba_theme.theme -> hook_preprocess_html()
if ($route_name === 'jaraba_billing.checkout') {
  $variables['attributes']['class'][] = 'page-checkout';
  $variables['attributes']['class'][] = 'full-width-layout';
  $variables['attributes']['class'][] = 'no-admin-toolbar';
}
```

### 5.4 JavaScript: stripe-checkout.js (Embedded Checkout)

**Archivo nuevo**: `web/modules/custom/jaraba_billing/js/stripe-checkout.js`

```javascript
/**
 * @file
 * Stripe Embedded Checkout integration.
 *
 * Carga Stripe.js, crea Checkout Session via API, y monta
 * el formulario embebido en #checkout-container.
 *
 * drupalSettings requeridos:
 *   - stripeCheckout.publicKey: Stripe publishable key
 *   - stripeCheckout.sessionUrl: URL para crear session (POST)
 *   - stripeCheckout.planId: ID del SaasPlan
 *   - stripeCheckout.cycle: 'monthly' | 'yearly'
 *
 * ROUTE-LANGPREFIX-001: sessionUrl viene de Url::fromRoute() via drupalSettings.
 * INNERHTML-XSS-001: No se usa innerHTML con datos de API.
 * CSRF-JS-CACHE-001: Token de /session/token cacheado.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.stripeCheckout = {
    attach: function (context) {
      once('stripe-checkout', '#checkout-container', context).forEach(async function (container) {
        // ... implementacion completa con error handling
      });
    },
  };
})(Drupal, drupalSettings, once);
```

**Flujo JS**:
1. Carga `@stripe/stripe-js` desde Stripe CDN (unica excepcion al "no CDN" — requerido por Stripe para PCI compliance)
2. Obtiene CSRF token de `/session/token` (CSRF-JS-CACHE-001)
3. POST a `sessionUrl` con `{planId, cycle, email, businessName}` + CSRF header
4. Recibe `{clientSecret}` del backend
5. `stripe.initEmbeddedCheckout({clientSecret})` → monta formulario
6. Maneja errores con mensajes traducibles via `Drupal.t()`

### 5.5 SCSS: _checkout.scss con tokens inyectables

**Archivo nuevo**: `web/themes/custom/ecosistema_jaraba_theme/scss/components/_checkout.scss`

**Reglas aplicadas**:
- CSS-VAR-ALL-COLORS-001: Todos los colores via `var(--ej-*, fallback)`
- SCSS-001: `@use '../variables' as *;` al inicio
- SCSS-COLORMIX-001: `color-mix()` en vez de `rgba()`
- Mobile-first con breakpoints progresivos
- BEM naming convention

```scss
@use '../variables' as *;

// ============================================================================
// CHECKOUT PAGE — Layout y componentes
// ============================================================================

.checkout-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--ej-bg-body, #F8FAFC);
  font-family: var(--ej-font-family, Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif);
}

.checkout-page__main {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem 1rem;

  @media (min-width: $ej-breakpoint-md) {
    padding: 2.5rem 2rem;
  }
}

.checkout-page__content {
  width: 100%;
  max-width: 960px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;

  @media (min-width: $ej-breakpoint-md) {
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
  }
}

// Resumen del plan (columna izquierda)
.checkout-summary {
  background: var(--ej-bg-surface, #fff);
  border: 1px solid var(--ej-border-color, #E0E0E0);
  border-radius: var(--ej-border-radius, 10px);
  padding: 1.5rem;
  order: 2; // Mobile: despues del form

  @media (min-width: $ej-breakpoint-md) {
    order: 1; // Desktop: antes del form
  }
}

.checkout-summary__plan-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--ej-text-primary, #212121);
  margin: 0 0 0.5rem;
}

.checkout-summary__price {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ej-color-corporate, #233D63);

  &-cycle {
    font-size: 0.875rem;
    font-weight: 400;
    color: var(--ej-text-secondary, #757575);
  }
}

.checkout-summary__features {
  list-style: none;
  padding: 0;
  margin: 1.5rem 0 0;

  li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0;
    font-size: 0.875rem;
    color: var(--ej-text-primary, #212121);
    border-bottom: 1px solid var(--ej-border-color-light, #EEEEEE);

    &:last-child {
      border-bottom: none;
    }
  }
}

// Contenedor Stripe Checkout (columna derecha)
.checkout-stripe {
  order: 1; // Mobile: primero

  @media (min-width: $ej-breakpoint-md) {
    order: 2; // Desktop: segundo
  }
}

#checkout-container {
  min-height: 400px;
  border-radius: var(--ej-border-radius, 10px);
  overflow: hidden;
}

// Estado de carga
.checkout-stripe__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  background: var(--ej-bg-surface, #fff);
  border: 1px solid var(--ej-border-color, #E0E0E0);
  border-radius: var(--ej-border-radius, 10px);
}

// Error state
.checkout-stripe__error {
  padding: 2rem;
  text-align: center;
  background: color-mix(in srgb, var(--ej-color-danger, #EF4444) 8%, transparent);
  border: 1px solid var(--ej-color-danger, #EF4444);
  border-radius: var(--ej-border-radius, 10px);
  color: var(--ej-color-danger, #EF4444);
}

// Responsive
@media (max-width: #{$ej-breakpoint-sm - 1}) {
  .checkout-page__content {
    padding: 0;
  }

  .checkout-summary {
    border-radius: 0;
    border-left: none;
    border-right: none;
  }
}

// Reduced motion
@media (prefers-reduced-motion: reduce) {
  .checkout-stripe__loading {
    animation: none;
  }
}
```

### 5.6 Parciales reutilizables

**`_checkout-header.html.twig`** — Header minimalista para checkout (sin menu completo):

```twig
{# Parcial: Header para paginas de checkout #}
<header class="checkout-header" role="banner">
  <div class="checkout-header__inner">
    <a href="/" class="checkout-header__brand" aria-label="{% trans %}Volver al inicio{% endtrans %}">
      {{ jaraba_icon('ui', 'arrow-left', { size: '20px', color: 'neutral' }) }}
      <span class="checkout-header__brand-name">{{ site_name|default('Jaraba Impact Platform') }}</span>
    </a>
    <div class="checkout-header__secure">
      {{ jaraba_icon('compliance', 'shield', { size: '16px', color: 'success' }) }}
      <span>{% trans %}Pago seguro{% endtrans %}</span>
    </div>
  </div>
</header>
```

**`_checkout-footer.html.twig`** — Footer minimalista con legal:

```twig
{# Parcial: Footer para paginas de checkout #}
<footer class="checkout-footer" role="contentinfo">
  <div class="checkout-footer__inner">
    <p class="checkout-footer__legal">
      {% trans %}Pago procesado de forma segura por Stripe. Tus datos de pago nunca se almacenan en nuestros servidores.{% endtrans %}
    </p>
    <div class="checkout-footer__links">
      <a href="/terminos">{% trans %}Terminos de servicio{% endtrans %}</a>
      <a href="/privacidad">{% trans %}Politica de privacidad{% endtrans %}</a>
    </div>
  </div>
</footer>
```

### 5.7 Pagina de exito post-checkout: /checkout/success

Template Zero Region con confirmacion, proximos pasos y redirect automatico al dashboard.

### 5.8 Pagina de cancelacion: /checkout/cancel

Template Zero Region con CTA para volver a intentar o contactar soporte.

---

## 6. Fase 3 — Auto-Provisionamiento y Lifecycle (Semana 3)

### 6.1 Webhook checkout.session.completed: Provisionamiento completo

El handler ya existe en `BillingWebhookController::handleCheckoutSessionCompleted()` (S2-03). Lo que falta es:

1. **Verificar metadata completa**: El webhook recibe `metadata.drupal_plan_id`, `metadata.vertical`, `metadata.business_name`, `metadata.email`
2. **Crear BillingCustomer**: Llamar `StripeCustomerService::createOrGetCustomer()` con el customer_id de la session
3. **Resolver SaasPlan**: Cargar el plan por ID, verificar que existe y esta activo
4. **Invocar TenantOnboardingService**: Con todos los datos necesarios para crear Tenant + Group + Domain
5. **Activar suscripcion**: `TenantSubscriptionService::activateSubscription()` o `startTrial()` segun configuracion
6. **Enviar email de bienvenida**: Via MailManager con template personalizado

### 6.2 TenantOnboardingService: Integracion con checkout flow

El servicio ya existe. La integracion requiere:
- Verificar que acepta `stripe_customer_id` y `stripe_subscription_id` como parametros
- Verificar que crea el Group y Domain Access correctamente
- Verificar que asigna el plan (subscription_plan field) al tenant

### 6.3 StripeCustomerService: Auto-creacion en onboarding

Actualmente: el BillingCustomer solo se crea cuando el admin lo hace manualmente o via BillingApiController.

**Cambio**: Invocar `StripeCustomerService::createOrGetCustomer()` durante el checkout flow para asegurar que el customer existe en local antes de completar el onboarding.

### 6.4 Cambio de plan mid-cycle: Flujo upgrade/downgrade con proration

**Flujo existente** (ya implementado en BillingApiController):
1. GET `/api/v1/billing/proration-preview?new_plan_id=X` → ProrationService::previewProration()
2. PATCH `/api/v1/billing/subscription/plan` con `{plan_id}` → StripeSubscriptionService::updateSubscription()
3. Stripe maneja proration automaticamente → genera factura → webhook

**Lo que falta**: UI en el tenant dashboard (slide-panel) que muestre preview de proration y confirme el cambio.

### 6.5 Cancelacion: Flujo inmediato y diferido

**Ya implementado** en BillingApiController:
- DELETE `/api/v1/billing/subscription?immediately=true|false`
- TenantSubscriptionService::cancelSubscription() con `cancel_at_period_end` o inmediato

**Lo que falta**: UI en tenant dashboard con dialogo de confirmacion y opcion "al final del periodo" vs "inmediata".

### 6.6 Reactivacion de suscripcion cancelada

**Ya implementado**: POST `/api/v1/billing/subscription/reactivate`

**Lo que falta**: Boton en tenant dashboard cuando status=cancelled y periodo no ha expirado.

### 6.7 Portal de facturacion del tenant (Stripe Customer Portal)

**Ya implementado**: POST `/api/v1/billing/portal-session` → crea session del Billing Portal de Stripe

**Lo que falta**: Enlace en tenant dashboard "Gestionar facturacion" que abra el portal en nueva pestaña.

---

## 7. Fase 4 — Frontend Premium y Experiencia de Usuario (Semana 4)

### 7.1 Pricing page: Integracion con Stripe price_ids reales

La pagina de pricing (`/planes/{vertical}`) ya renderiza planes con precios. Lo que falta:

1. **Boton "Contratar"**: Actualmente no tiene destino. Debe enlazar a `/checkout/{plan_id}?cycle=monthly|yearly`
2. **Toggle monthly/yearly**: El JS del pricing toggle debe actualizar los enlaces de checkout
3. **Verificar que `stripe_price_id` no este vacio**: Si un plan no tiene price sincronizado, mostrar "Proximamente" en vez de boton

### 7.2 Tenant Dashboard: Widget de suscripcion con estado real

**Ubicacion**: Template `tenant-self-service-dashboard.html.twig` (ya existe)

Widget nuevo que muestra:
- Plan actual (nombre, precio, ciclo)
- Estado (active, trial, past_due, cancelled) con badge de color
- Fecha de renovacion o expiracion
- Botones: "Cambiar plan" (slide-panel), "Cancelar" (dialogo), "Ver facturas" (slide-panel)

**Datos inyectados** via `hook_preprocess_page()` → `drupalSettings.tenantBilling`:
```javascript
{
  planName: "AgroConecta Profesional",
  planPrice: "59.00",
  billingCycle: "monthly",
  status: "active",
  renewalDate: "2026-04-05",
  trialEnds: null,
  invoicesUrl: "/api/v1/billing/invoices",
  portalUrl: "/api/v1/billing/portal-session"
}
```

### 7.3 Slide-panel: Gestion de metodos de pago

Abre slide-panel con lista de tarjetas guardadas + formulario para añadir nueva via Stripe Elements.

**SLIDE-PANEL-RENDER-001**: Usa `renderPlain()`, no `render()`.
**FORM-CACHE-001**: No `setCached(TRUE)` incondicional.

### 7.4 Slide-panel: Historial de facturas con descarga PDF

Lista de BillingInvoice entities con:
- Numero, fecha, importe, estado (badge)
- Boton "Descargar PDF" que abre `pdf_url` de Stripe

### 7.5 Notificaciones

Emails automaticos (ya parcialmente implementados en BillingWebhookController):
- Trial expiring (3 dias antes)
- Payment failed (inicio dunning)
- Plan changed (confirmacion upgrade/downgrade)
- Subscription cancelled (confirmacion)
- Welcome email (post-checkout)

### 7.6 Responsive mobile-first para todo el flujo de checkout

Todos los componentes diseñados mobile-first:
- Checkout: 1 columna en movil, 2 columnas en desktop
- Pricing: Cards apiladas en movil, grid en desktop
- Dashboard widgets: Stack vertical en movil, grid en desktop

### 7.7 Accesibilidad WCAG 2.1 AA en checkout y billing

- Contraste minimo 4.5:1 para texto normal, 3:1 para texto grande
- Focus visible en todos los interactivos
- aria-labels en botones de accion (cambiar plan, cancelar, etc.)
- Headings jerarquicos (h1→h2→h3)
- Skip navigation link en checkout

---

## 8. Especificaciones Tecnicas Detalladas

### 8.1 StripeProductSyncService: Firma, metodos y logica completa

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Sincroniza SaasPlan entities con Stripe Products y Prices.
 *
 * Principio: Drupal es Source of Truth. Stripe refleja lo que Drupal define.
 *
 * Patron de sincronizacion:
 * - Plan nuevo (sin stripe_product_id) → crea Product + 2 Prices
 * - Plan editado (con stripe_product_id) → actualiza Product, archiva/crea Prices si precio cambio
 * - Plan desactivado → archiva Product (suscripciones existentes continuan)
 *
 * Directrices aplicadas:
 * - OPTIONAL-CROSSMODULE-001: StripeConnectService inyectado como @?
 * - LOGGER-INJECT-001: LoggerInterface directo
 * - AUDIT-PERF-002: LockBackendInterface para race conditions
 * - STRIPE-ENV-UNIFY-001: Keys via StripeConnectService (settings.secrets.php)
 */
class StripeProductSyncService {

  public function __construct(
    protected ?StripeConnectService $stripeConnect,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected LockBackendInterface $lock,
  ) {}

  /**
   * Sincroniza un SaasPlan con Stripe.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
   *   El plan a sincronizar.
   *
   * @return array{product_id: string, price_monthly_id: string, price_yearly_id: string}
   */
  public function syncPlan(SaasPlanInterface $plan): array {
    if (!$this->stripeConnect) {
      $this->logger->warning('StripeConnectService not available — skipping plan sync.');
      return ['product_id' => '', 'price_monthly_id' => '', 'price_yearly_id' => ''];
    }

    $lockId = 'stripe_sync_plan_' . ($plan->id() ?? 'new_' . md5($plan->getName()));
    if (!$this->lock->acquire($lockId, 30.0)) {
      throw new \RuntimeException("Could not acquire lock for plan sync: $lockId");
    }

    try {
      $productId = $this->syncProduct($plan);
      $priceMonthlyId = $this->syncPrice($plan, $productId, 'month');
      $priceYearlyId = $this->syncPrice($plan, $productId, 'year');

      return [
        'product_id' => $productId,
        'price_monthly_id' => $priceMonthlyId,
        'price_yearly_id' => $priceYearlyId,
      ];
    }
    finally {
      $this->lock->release($lockId);
    }
  }

  // ... metodos privados syncProduct(), syncPrice(), archiveProduct()
  // ... documentados en detalle con logica de idempotencia y manejo de errores
}
```

### 8.2 CheckoutSessionService: Firma, metodos y parametros Stripe

Similar estructura al anterior, con metodo `createSession()` que:
- Valida que el plan tiene `stripe_price_id` no vacio
- Resuelve el precio segun ciclo (monthly/yearly)
- Construye parametros de la Checkout Session
- Llama `stripeConnect->stripeRequest('POST', '/v1/checkout/sessions', $params)`
- Retorna `{client_secret, session_id}`

### 8.3 CheckoutController: Rutas, permisos y render arrays

- Extiende `ControllerBase`
- CONTROLLER-READONLY-001: No `readonly` en promotion para $entityTypeManager
- Rutas registradas en `jaraba_billing.routing.yml`
- Permisos: checkout page publica, API con CSRF

### 8.4 stripe-checkout.js: Detalle de implementacion

- `Drupal.behaviors.stripeCheckout` con `once()`
- Carga Stripe.js via `loadStripe()` desde CDN oficial
- CSRF token cacheado (CSRF-JS-CACHE-001)
- Textos via `Drupal.t()` (traducibles)
- URLs via `drupalSettings` (ROUTE-LANGPREFIX-001)
- Error handling con mensajes visibles al usuario

### 8.5 SCSS: Estructura BEM, tokens y responsive

Estructura BEM completa para:
- `.checkout-page` (container)
- `.checkout-header` (parcial)
- `.checkout-summary` (resumen plan)
- `.checkout-stripe` (container Stripe)
- `.checkout-success` (pagina exito)
- `.checkout-footer` (parcial)

### 8.6 Templates Twig: Variables, parciales y directivas trans

Todos los textos con `{% trans %}...{% endtrans %}` (bloque, NO filtro `|t`).
Variables inyectadas via preprocess, no en controller (ZERO-REGION-003).

### 8.7 hook_preprocess_html(): Body classes para checkout

```php
// En ecosistema_jaraba_theme_preprocess_html()
$checkout_routes = [
  'jaraba_billing.checkout',
  'jaraba_billing.checkout.success',
  'jaraba_billing.checkout.cancel',
];
if (in_array($route_name, $checkout_routes)) {
  $variables['attributes']['class'][] = 'page-checkout';
  $variables['attributes']['class'][] = 'full-width-layout';
  $variables['attributes']['class'][] = 'no-admin-toolbar';
}
```

### 8.8 hook_preprocess_page(): drupalSettings para checkout

```php
// ZERO-REGION-003: drupalSettings via preprocess, no via controller
$variables['#attached']['drupalSettings']['stripeCheckout'] = [
  'publicKey' => getenv('STRIPE_PUBLIC_KEY') ?: '',
];
```

### 8.9 Webhook handlers: Firma y logica de cada evento

Reutiliza los handlers existentes en BillingWebhookController. No se crean nuevos — solo se verifica que `checkout.session.completed` tenga acceso a los datos de metadata.

### 8.10 Drush command: stripe:sync-plans

Implementacion con `DrushCommands` base class, opciones `--force`, `--dry-run`, `--vertical`, `--update-tiers`.

### 8.11 hook_update_N(): Migracion de campos

Sigue UPDATE-HOOK-REQUIRED-001, UPDATE-FIELD-DEF-001, UPDATE-HOOK-CATCH-001. Numero de hook se determina al implementar (siguiente disponible en ecosistema_jaraba_core).

### 8.12 Iconos: Categorias y nombres usados en billing/checkout

| Contexto | Categoria | Nombre | Variante | Color |
|----------|-----------|--------|----------|-------|
| Header checkout "volver" | ui | arrow-left | duotone | neutral |
| Header "pago seguro" | compliance | shield | duotone | success |
| Feature check en summary | status | check | duotone | success |
| Error en checkout | status | warning | duotone | danger |
| Plan icon en pricing | commerce | tag | duotone | impulse |
| Invoice icon | fiscal | document | duotone | corporate |
| Payment method | commerce | credit-card | duotone | corporate |
| Dashboard billing widget | finance | wallet | duotone | impulse |

Todos cumplen ICON-CONVENTION-001 (funcion `jaraba_icon()`), ICON-DUOTONE-001 (default duotone), ICON-COLOR-001 (solo paleta Jaraba).

### 8.13 Permisos y roles: RBAC para billing

| Permiso | Descripcion | Roles |
|---------|------------|-------|
| `manage subscriptions` | Cambiar plan, cancelar, reactivar | tenant_admin |
| `view own invoices` | Ver facturas propias | tenant_admin, tenant_member |
| `manage payment methods` | Añadir/eliminar tarjetas | tenant_admin |
| `view billing usage` | Ver metricas de uso | tenant_admin |
| `administer billing` | Gestion global (admin center) | platform_admin |

---

## 9. Tabla de Archivos Creados/Modificados

| Accion | Archivo | Fase | Modulo |
|--------|---------|------|--------|
| **CREAR** | `jaraba_billing/src/Service/StripeProductSyncService.php` | F1 | jaraba_billing |
| **CREAR** | `jaraba_billing/src/Service/CheckoutSessionService.php` | F2 | jaraba_billing |
| **CREAR** | `jaraba_billing/src/Controller/CheckoutController.php` | F2 | jaraba_billing |
| **CREAR** | `jaraba_billing/src/Commands/StripeSyncCommands.php` | F1 | jaraba_billing |
| **CREAR** | `jaraba_billing/js/stripe-checkout.js` | F2 | jaraba_billing |
| **CREAR** | `ecosistema_jaraba_theme/templates/page--checkout.html.twig` | F2 | tema |
| **CREAR** | `ecosistema_jaraba_theme/templates/partials/_checkout-header.html.twig` | F2 | tema |
| **CREAR** | `ecosistema_jaraba_theme/templates/partials/_checkout-footer.html.twig` | F2 | tema |
| **CREAR** | `ecosistema_jaraba_theme/templates/checkout-page.html.twig` | F2 | tema |
| **CREAR** | `ecosistema_jaraba_theme/templates/checkout-success.html.twig` | F2 | tema |
| **CREAR** | `ecosistema_jaraba_theme/templates/checkout-cancel.html.twig` | F2 | tema |
| **CREAR** | `ecosistema_jaraba_theme/scss/components/_checkout.scss` | F2 | tema |
| MODIFICAR | `ecosistema_jaraba_core/src/Entity/SaasPlan.php` (2 campos nuevos) | F1 | core |
| MODIFICAR | `ecosistema_jaraba_core/ecosistema_jaraba_core.install` (hook_update) | F1 | core |
| MODIFICAR | `jaraba_billing/jaraba_billing.module` (hook_entity_presave) | F1 | billing |
| MODIFICAR | `jaraba_billing/jaraba_billing.services.yml` (2 servicios nuevos) | F1-F2 | billing |
| MODIFICAR | `jaraba_billing/jaraba_billing.routing.yml` (4 rutas nuevas) | F2 | billing |
| MODIFICAR | `jaraba_billing/jaraba_billing.libraries.yml` (1 library nueva) | F2 | billing |
| MODIFICAR | `ecosistema_jaraba_core/ecosistema_jaraba_core.module` (hook_theme) | F2 | core |
| MODIFICAR | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` (preprocess_html, suggestions) | F2 | tema |
| MODIFICAR | `ecosistema_jaraba_theme/scss/main.scss` (@use checkout) | F2 | tema |
| MODIFICAR | `ecosistema_jaraba_theme/templates/pricing-page.html.twig` (enlace checkout) | F4 | tema |
| MODIFICAR | `ecosistema_jaraba_theme/templates/tenant-self-service-dashboard.html.twig` (widget billing) | F4 | tema |
| MODIFICAR | `ecosistema_jaraba_core/src/Form/SaasPlanForm.php` (2 campos nuevos en form) | F1 | core |

---

## 10. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Codigo | Seccion de este plan | Estado |
|----------------|--------|---------------------|--------|
| Core Entidades Esquema BD | f-01 | 4.2, 3.6 | SaasPlan extendido con 2 campos |
| Core APIs Contratos | f-03 | 5.2, 8.3 | 4 rutas nuevas en billing API |
| Core Permisos RBAC | f-04 | 8.13 | 5 permisos billing |
| Core Flujos ECA | f-06 | 6.1 | checkout.session.completed → auto-provisioning |
| SaaS Admin Center Premium | f-104 | 7.2 | Widget billing en tenant dashboard |
| Platform Vertical Pricing Matrix | Doc 158 | 4.1, 5.1 | Sync automatico de todos los precios |
| Usage Based Pricing | Doc 111 | 3.2 | Precios recurrentes via Stripe Prices |
| Pagos y Monetizacion SaaS | Logica 1934 | 3.3, 6.1 | Checkout → subscription → auto-provisioning |
| Definicion Planes SaaS | Logica 1908 | 4.1-4.6 | Sync bidireccional de planes |
| Flujo Onboarding Tenant | Logica 1959 | 6.1-6.2 | Checkout integrado en onboarding |
| Remediacion Billing Commerce Fiscal | REM-BILLING-001 | 1.7, 2.2 | Complementario — cierra GAP-CHECKOUT-001 |
| Verticalizacion Planes Precios SaaS | VERT-PRICING-001 | 4.5, 4.6 | Sync de 40+ planes seeded |
| Verticales Componibles Addon Marketplace | VERT-ADDON-001 | 3.5 | Checkout puede incluir addon items |

---

## 11. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz | Codigo | Como se cumple | Verificacion |
|-----------|--------|----------------|--------------|
| Drupal es Source of Truth para planes | STRIPE-CHECKOUT-001 §3.1 | Admin edita en Drupal → hook_presave sync a Stripe | `drush stripe:sync-plans --dry-run` |
| Multi-tenant isolation | TENANT-ISOLATION-ACCESS-001 | CheckoutController verifica tenant match; billing entities filtran por tenant_id | Test funcional |
| Tenant bridge | TENANT-BRIDGE-001 | CheckoutSessionService usa TenantBridgeService para resolver Group↔Tenant | Unit test |
| Query filter por tenant | TENANT-001 | Todas las queries de billing filtran por tenant_id | Grep `->condition('tenant_id'` |
| Tenant context | TENANT-002 | Usa `ecosistema_jaraba_core.tenant_context` para resolver tenant actual | Service injection |
| Premium entity forms | PREMIUM-FORMS-PATTERN-001 | SaasPlanForm ya extiende PremiumEntityFormBase; campos nuevos en sections existentes | Visual verification |
| Zero Region Policy | ZERO-REGION-001/002/003 | page--checkout.html.twig usa `{{ clean_content }}`, preprocess para drupalSettings | Template inspection |
| Body classes via preprocess_html | Hook pattern | `page-checkout`, `full-width-layout` via hook, NO attributes.addClass() | Grep template |
| Controller readonly | CONTROLLER-READONLY-001 | CheckoutController asigna $entityTypeManager manualmente, no readonly | PHPStan level 6 |
| Optional cross-module DI | OPTIONAL-CROSSMODULE-001 | StripeConnectService via `@?`, StripeCustomerService via `@?` | validate-optional-deps.php |
| Logger injection | LOGGER-INJECT-001 | `@logger.channel.jaraba_billing` → `LoggerInterface $logger` directo | validate-logger-injection.php |
| No circular deps | CONTAINER-DEPS-002 | Nuevos servicios no crean ciclos (verificado vs grafo existente) | validate-circular-deps.php |
| Phantom args | PHANTOM-ARG-001 | Args en services.yml == constructor params exactamente | validate-service-consumers.php |
| Update hook para campos nuevos | UPDATE-HOOK-REQUIRED-001 | hook_update_XXXXX() con installFieldStorageDefinition | drush updatedb |
| Field def setName/setTarget | UPDATE-FIELD-DEF-001 | setName() + setTargetEntityTypeId() en hook_update | Inspeccion manual |
| Catch Throwable en hooks | UPDATE-HOOK-CATCH-001 | `catch (\Throwable $e)` en hook_update, no `\Exception` | Inspeccion manual |
| Presave resilience | PRESAVE-RESILIENCE-001 | hasService() + try-catch en hook_entity_presave; save no falla por Stripe | Unit test mock |
| Stripe keys via env | STRIPE-ENV-UNIFY-001 | Usa StripeConnectService que lee de settings.secrets.php | Inspeccion manual |
| URLs via fromRoute | ROUTE-LANGPREFIX-001 | Todas las URLs en JS via drupalSettings (inyectadas desde Url::fromRoute) | Grep hardcoded URLs |
| Textos traducibles Twig | i18n-001 | `{% trans %}` bloque para TODOS los textos en templates | Grep `|t` (no debe existir) |
| Textos traducibles PHP | i18n-001 | `$this->t()` en forms/controllers | Inspeccion manual |
| Textos traducibles JS | i18n-001 | `Drupal.t()` en stripe-checkout.js | Grep JS strings |
| CSS variables | CSS-VAR-ALL-COLORS-001 | TODOS los colores via `var(--ej-*, fallback)`. Cero hex hardcoded en SCSS | Grep `#[0-9a-f]` en SCSS |
| Dart Sass moderno | SCSS-001 | `@use '../variables' as *;` al inicio de cada parcial | Build script |
| SCSS color-mix | SCSS-COLORMIX-001 | `color-mix(in srgb, ...)` en vez de `rgba()` | Grep `rgba` en SCSS |
| SCSS entry consolidation | SCSS-ENTRY-CONSOLIDATION-001 | `_checkout.scss` como parcial, importado desde main.scss | Build verification |
| SCSS compile verify | SCSS-COMPILE-VERIFY-001 | Verificar timestamp CSS > SCSS tras cada edicion | `npm run build` + stat |
| Iconos duotone default | ICON-DUOTONE-001 | Todos los iconos sin variant explicito usan duotone | Template inspection |
| Iconos colores paleta | ICON-COLOR-001 | Solo colores de paleta Jaraba en iconos | Template inspection |
| Iconos via funcion | ICON-CONVENTION-001 | `{{ jaraba_icon('category', 'name', options) }}` siempre | Template inspection |
| No emojis en canvas | ICON-EMOJI-001 | No emojis Unicode en templates | Grep Unicode ranges |
| Slide panel render | SLIDE-PANEL-RENDER-001 | renderPlain() para slide-panel content, #action explicito | Controller inspection |
| Form cache | FORM-CACHE-001 | No setCached(TRUE) incondicional | Form inspection |
| CSRF en API | CSRF-API-001 | `_csrf_request_header_token: 'TRUE'` en rutas POST | routing.yml inspection |
| CSRF JS cache | CSRF-JS-CACHE-001 | Token cacheado en variable modulo | JS inspection |
| XSS innerHTML | INNERHTML-XSS-001 | No innerHTML con datos API; Drupal.checkPlain() si necesario | JS inspection |
| Mobile-first responsive | SCSS-RESPONSIVE-001 | Base styles para movil, media queries para desktop (`min-width`) | SCSS inspection |
| Accesibilidad | WCAG 2.1 AA | aria-labels, focus visible, contraste, headings jerarquicos | /audit-wcag |
| Secret management | SECRET-MGMT-001 | Stripe keys via getenv() en settings.secrets.php | No secrets in config/sync |
| Webhook HMAC | AUDIT-SEC-001 | verifyWebhookSignature() con hash_equals() | Existing implementation |
| Access strict comparison | ACCESS-STRICT-001 | (int) tenant_id === (int) entity tenant | Access handler inspection |
| Views integration | Views-001 | SaasPlan ya tiene views_data en annotation | Existing |
| Field UI | FIELD-UI-SETTINGS-TAB-001 | SaasPlan ya tiene field_ui_base_route | Existing |
| Admin navigation | NAV-001 | SaasPlan en /admin/structure/saas-plan | Existing |
| No `{{ page.content }}` | Zero Region | `{{ clean_content }}` en checkout templates | Template inspection |
| Tokens inyectables | TOKEN-CASCADE-001 | ThemeTokenService cascade aplica automaticamente | Visual verification |

---

## 12. Verificacion RUNTIME-VERIFY-001

Tras completar la implementacion, verificar las 5 capas:

### Capa 1: CSS compilado
```bash
# Verificar que SCSS compila sin errores
cd web/themes/custom/ecosistema_jaraba_theme && npm run build
# Verificar timestamp CSS > SCSS
stat css/ecosistema-jaraba-theme.css | grep Modify
stat scss/components/_checkout.scss | grep Modify
```

### Capa 2: Tablas DB / Campos
```bash
lando drush updatedb -y
lando drush sql:query "SELECT stripe_product_id, stripe_price_yearly_id FROM saas_plan_field_data LIMIT 5"
# Verificar que los campos existen y tienen valores tras sync
```

### Capa 3: Rutas accesibles
```bash
# Verificar que las rutas responden
curl -s -o /dev/null -w "%{http_code}" https://jaraba-saas.lndo.site/es/checkout/basico
# Esperado: 200 (publico)
curl -s -o /dev/null -w "%{http_code}" https://jaraba-saas.lndo.site/es/api/v1/billing/checkout-session
# Esperado: 405 (requiere POST) o 403 (sin CSRF)
```

### Capa 4: data-* selectores matchean
```bash
# Verificar que #checkout-container existe en el HTML
curl -s -b $COOKIES https://jaraba-saas.lndo.site/es/checkout/basico | grep -c 'checkout-container'
# Esperado: 1
# Verificar que JS espera el mismo selector
grep -c 'checkout-container' web/modules/custom/jaraba_billing/js/stripe-checkout.js
# Esperado: >= 1
```

### Capa 5: drupalSettings inyectado
```bash
# Verificar que stripeCheckout aparece en la pagina
curl -s -b $COOKIES https://jaraba-saas.lndo.site/es/checkout/basico | grep -c 'stripeCheckout'
# Esperado: >= 1 (en <script> de drupalSettings)
```

### Capa 6: Stripe sync verificacion
```bash
# Verificar que planes tienen stripe_product_id despues del sync
lando drush stripe:sync-plans --dry-run
# Esperado: lista de planes con "Would sync: ..."
lando drush stripe:sync-plans
# Esperado: "Synced: 40, Skipped: 0, Errors: 0"
lando drush sql:query "SELECT name, stripe_product_id, stripe_price_id FROM saas_plan_field_data WHERE stripe_product_id != '' LIMIT 5"
# Esperado: filas con prod_xxx y price_xxx
```

### Capa 7: Flujo end-to-end (manual)
1. Navegar a `https://jaraba-saas.lndo.site/es/planes/agroconecta`
2. Click "Contratar" en plan Profesional
3. Verificar que `/checkout/agroconecta_pro` carga el formulario Stripe embebido
4. Usar tarjeta test `4242 4242 4242 4242` — pagar
5. Verificar redirect a `/checkout/success`
6. Verificar que se creo un tenant automaticamente
7. Navegar a `/my-dashboard` — verificar widget de suscripcion con estado "active"

---

## 13. Plan de Testing

### 13.1 Tests Unitarios

| Test | Clase | Que verifica |
|------|-------|-------------|
| StripeProductSyncServiceTest | `jaraba_billing/tests/src/Unit/Service/` | syncPlan() crea Product + Prices, maneja plan existente, archiva desactivado |
| CheckoutSessionServiceTest | `jaraba_billing/tests/src/Unit/Service/` | createSession() construye params correctos, valida precio no vacio |
| StripeSyncCommandsTest | `jaraba_billing/tests/src/Unit/Commands/` | Drush command procesa planes, respeta --force y --dry-run |

### 13.2 Tests Kernel

| Test | Clase | Que verifica |
|------|-------|-------------|
| SaasPlanFieldsTest | `ecosistema_jaraba_core/tests/src/Kernel/Entity/` | Campos stripe_product_id y stripe_price_yearly_id existen y persisten |
| PresaveHookTest | `jaraba_billing/tests/src/Kernel/` | hook_entity_presave invoca sync cuando servicio disponible, no falla cuando no |

### 13.3 Tests Funcionales

| Test | Clase | Que verifica |
|------|-------|-------------|
| CheckoutRouteAccessTest | `jaraba_billing/tests/src/Functional/` | /checkout/{plan} responde 200 para plan valido, 404 para plan sin stripe_price_id |
| CheckoutSessionApiTest | `jaraba_billing/tests/src/Functional/` | POST /api/v1/billing/checkout-session con CSRF retorna client_secret |
| PricingPageCheckoutLinksTest | `ecosistema_jaraba_core/tests/src/Functional/` | /planes/{vertical} incluye enlaces a /checkout/{plan_id} |

---

## 14. Coherencia con Documentacion Existente

| Documento | Relacion | Accion |
|-----------|----------|--------|
| `00_DIRECTRICES_PROYECTO.md` v114 | Directrices aplicadas al 100% (ver seccion 11) | Ninguna — todo conforme |
| `00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` v99 | Arquitectura de billing extendida con checkout | Actualizar seccion de billing |
| `00_INDICE_GENERAL.md` v139 | Nuevo plan de implementacion | Añadir entrada |
| `00_FLUJO_TRABAJO_CLAUDE.md` v63 | Flujo de trabajo aplicado | Ninguna |
| `07_VERTICAL_CUSTOMIZATION_PATTERNS.md` v3.1 | Checkout vertical-aware | Ninguna — ya contemplado |
| Aprendizaje #60 (billing entities) | Entidades reutilizadas, no duplicadas | Referencia cruzada |
| Aprendizaje #159 (Stripe DI audit) | LOGGER-DI-001 y PHANTOM-ARG-001 aplicados | Referencia cruzada |
| Aprendizaje #163 (onboarding remediation) | Checkout embebido implementa recomendaciones | Marcar como IMPLEMENTADO |
| Doc 158 (Pricing Matrix) | 40+ planes sincronizados a Stripe | Marcar como IMPLEMENTADO |
| REM-BILLING-001 | GAP-CHECKOUT-001 y GAP-SYNC-001 cerrados | Actualizar estado gaps |
| VERT-PRICING-001 | Precios reales sincronizados a Stripe | Marcar como IMPLEMENTADO |
| Auditoria Gaps Billing v1 | Gaps criticos cerrados | Actualizar severidades |

---

## 15. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-05 | Claude Opus 4.6 | Creacion del plan completo: 4 fases, 28 acciones, 80-100h estimadas |
| 2.0.0 | 2026-03-17 | Claude Opus 4.6 | **EJECUCION Fases 1+2**: Sync bidireccional + checkout operativo. Ver §15.1 |

### 15.1 Detalle de Ejecucion v2.0.0 (2026-03-17)

**Estado global:** PLANIFICADO → EN PROGRESO (Fases 1-2 completadas, Fases 3-4 pendientes)

#### Bugs Criticos Resueltos

| Bug | Severidad | Root Cause | Fix |
|-----|-----------|-----------|-----|
| **CHECKOUT-ROUTE-COLLISION-001** | P0 | Ruta `/checkout/{saas_plan}` colisionaba con `commerce_checkout.form` (`/checkout/{commerce_order}/{step}` con step=null default). Commerce capturaba la ruta y lanzaba `ParamNotConvertedException` → 404 para TODOS los checkout. | Rutas movidas a `/planes/checkout/{saas_plan}`, `/planes/checkout/success`, `/planes/checkout/cancel` (ROUTE-VAR-FIRST-SEGMENT-001). |
| **STRIPE-URL-PREFIX-001** | P0 | `StripeProductSyncService` y `CheckoutSessionService` usaban endpoints con prefijo `/v1/` (`/v1/products`, `/v1/checkout/sessions`) que se concatenaba con base `https://api.stripe.com/v1` generando URLs invalidas (`v1/v1/products`). Otros servicios (FOC, AgroConecta, InvoiceService) NO tenian el bug. | Eliminado `/v1/` de TODOS los endpoints en StripeProductSyncService y CheckoutSessionService. |
| **CHECKOUT-404-EMPTY-STRIPE** | P1 | `CheckoutController::checkoutPage()` lanzaba `NotFoundHttpException` cuando `stripe_price_id` estaba vacio. Con las keys no configuradas + el bug de URLs, TODOS los planes devolvian 404. | Degradacion elegante: modo preview con CTA de contacto (visitantes) + aviso administrativo con enlace al formulario de edicion (admins). |
| **CHECKOUT-NO-STATUS-CHECK** | P2 | Planes desactivados (status=0) eran accesibles en checkout. | Check de `status` antes de cualquier otra logica; 404 para planes inactivos. |

#### Fase 1 — Sincronizacion Bidireccional (COMPLETADA 2026-03-17)

- [x] §4.1 StripeProductSyncService: URLs corregidas, endpoints alineados con convencion FOC
- [x] §4.2 Campos stripe_product_id, stripe_price_id, stripe_price_yearly_id: ya existian (update_9002, update_10004)
- [x] §4.3 hook_entity_presave para SaasPlan: ya implementado con PRESAVE-RESILIENCE-001
- [x] §4.5 Sincronizacion masiva: 25 planes sincronizados (17 Products + 17 monthly Prices + yearly Prices)
- [x] Verificacion: API Stripe responde correctamente, todos los planes con stripe_price_id != ''

**Resultado en Stripe:**
- 17 Products activos (planes con precio > 0)
- 17+ Prices activos (monthly para todos, yearly para los que tienen precio anual)
- Metadata correcta: drupal_plan_id, vertical, source=jaraba_saas

#### Fase 2 — Checkout Session Embebido (COMPLETADA 2026-03-17)

- [x] §5.1 CheckoutSessionService: URLs corregidas, funcional
- [x] §5.2 CheckoutController: Rutas movidas a /planes/checkout/*, degradacion elegante, status check
- [x] §5.3 Template checkout-page.html.twig: Bifurcacion stripe_ready/preview, admin notice
- [x] §5.4 stripe-checkout.js: Ya funcional (CSRF, Embedded Checkout, error handling)
- [x] §5.5 SCSS: Nuevos estilos para preview mode y admin notice, compilado OK
- [x] §5.7 Pagina exito: /planes/checkout/success responde 200
- [x] §5.8 Pagina cancelacion: /planes/checkout/cancel responde 200

**RUNTIME-VERIFY-001 (5/5 PASS):**
- CSS compilado (timestamp OK)
- Rutas accesibles (200)
- Planes inactivos (404)
- Preview mode classes en DOM (7 matches)
- drupalSettings inyectado correctamente cuando Stripe ready

#### Fase 3 — Auto-Provisionamiento (PENDIENTE)

Requiere:
- [ ] Configurar Stripe Webhook endpoint en Stripe Dashboard
- [ ] TenantOnboardingService integracion con checkout.session.completed
- [ ] Flujo upgrade/downgrade con proration preview
- [ ] Portal de facturacion Stripe Customer Portal

#### Fase 4 — Frontend Premium (PENDIENTE)

Requiere:
- [ ] Pricing page: enlaces a /planes/checkout/{plan} (actualizar ruta)
- [ ] Tenant Dashboard: widget de suscripcion
- [ ] Slide-panel: metodos de pago, facturas
- [ ] Notificaciones: trial expiring, payment failed

#### Archivos Modificados en v2.0.0

| Archivo | Cambio | Directriz |
|---------|--------|-----------|
| `jaraba_billing/jaraba_billing.routing.yml` | Rutas /checkout/* → /planes/checkout/* | ROUTE-VAR-FIRST-SEGMENT-001 |
| `jaraba_billing/src/Controller/CheckoutController.php` | Degradacion elegante + status check + admin notice | STRIPE-CHECKOUT-001 §5.2 |
| `jaraba_billing/jaraba_billing.module` | Variables stripe_ready, is_admin, admin_edit_url, contact_email | ZERO-REGION-003 |
| `jaraba_billing/src/Service/StripeProductSyncService.php` | URLs /v1/* → /* (fix doble prefijo) | STRIPE-URL-PREFIX-001 |
| `jaraba_billing/src/Service/CheckoutSessionService.php` | URLs /v1/* → /* (fix doble prefijo) | STRIPE-URL-PREFIX-001 |
| `ecosistema_jaraba_theme/templates/checkout-page.html.twig` | Bifurcacion stripe_ready/preview | STRIPE-CHECKOUT-001 §5.3 |
| `ecosistema_jaraba_theme/scss/components/_checkout.scss` | Estilos preview mode + admin notice | CSS-VAR-ALL-COLORS-001 |
| `ecosistema_jaraba_theme/css/ecosistema-jaraba-theme.css` | Recompilado | SCSS-COMPILE-VERIFY-001 |
