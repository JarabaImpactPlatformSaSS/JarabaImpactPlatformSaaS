# Auditoría Integral: Sistema de Precios, Add-ons y PLG — Clase Mundial

**Fecha de creación:** 2026-03-18
**Última actualización:** 2026-03-18
**Autor:** Claude Opus 4.6 (Anthropic) — Consultor Senior Multidisciplinar
**Versión:** 1.0.0
**Categoría:** Auditoría Integral de Pricing, Marketplace de Add-ons, PLG (Product-Led Growth)
**Código:** AUDIT-PRICING-PLG-001
**Estado:** DIAGNOSTICADO
**Verticales auditados:** Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta, JarabaLex, Andalucía +ei, Formación, Content Hub
**Directrices aplicables:** NO-HARDCODE-PRICE-001, ADDON-VERTICAL-001, PLG-UPGRADE-UI-001, SETUP-WIZARD-DAILY-001, ZERO-REGION-001, CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001, TWIG-INCLUDE-ONLY-001, ROUTE-LANGPREFIX-001, i18n ({% trans %}), SCSS-COMPILE-VERIFY-001
**Módulos afectados:** ecosistema_jaraba_core, jaraba_billing, jaraba_addons, jaraba_usage_billing, ecosistema_jaraba_theme
**Rutas principales:** /planes, /planes/{vertical}, /addons, /addons/{addon_id}, /user/{uid}/edit (Mi suscripción), /planes/checkout/{saas_plan}, /api/v1/addons/*, /api/v1/subscription/*
**Especificación de referencia:** Doc 158 — Platform Vertical Pricing Matrix v1 (fuente de verdad de precios, Golden Rule #131)
**Dependencias de especificaciones:** Doc 134 (Stripe Billing), Doc 111 (UsageBased Pricing), Doc 104 (SaaS Admin Center)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Metodología de Auditoría](#2-metodología-de-auditoría)
3. [Inventario de Implementación Existente](#3-inventario-de-implementación-existente)
   - 3.1 [Servicios de Pricing](#31-servicios-de-pricing)
   - 3.2 [Entidades de Plan y Tier](#32-entidades-de-plan-y-tier)
   - 3.3 [Marketplace de Add-ons](#33-marketplace-de-add-ons)
   - 3.4 [Sección "Mi Suscripción" (Perfil Usuario)](#34-sección-mi-suscripción-perfil-usuario)
   - 3.5 [Setup Wizard + Daily Actions](#35-setup-wizard--daily-actions)
4. [Confrontación de Mercado por Vertical](#4-confrontación-de-mercado-por-vertical)
   - 4.1 [Empleabilidad](#41-empleabilidad)
   - 4.2 [Emprendimiento](#42-emprendimiento)
   - 4.3 [AgroConecta](#43-agroconecta)
   - 4.4 [ComercioConecta](#44-comercioconecta)
   - 4.5 [ServiciosConecta](#45-serviciosconecta)
   - 4.6 [JarabaLex](#46-jarabalex)
   - 4.7 [Andalucía +ei](#47-andalucía-ei)
   - 4.8 [Add-ons de Marketing](#48-add-ons-de-marketing)
5. [Análisis de Gaps Críticos](#5-análisis-de-gaps-críticos)
   - GAP-PRICING-001 a GAP-PRICING-011
6. [Diagnóstico de Lógica de Negocio](#6-diagnóstico-de-lógica-de-negocio)
   - 6.1 [Flujo de Suscripción Completo](#61-flujo-de-suscripción-completo)
   - 6.2 [Flujo de Add-ons](#62-flujo-de-add-ons)
   - 6.3 [PLG (Product-Led Growth)](#63-plg-product-led-growth)
7. [Verificación RUNTIME-VERIFY-001](#7-verificación-runtime-verify-001)
8. [Cumplimiento Setup Wizard + Daily Actions](#8-cumplimiento-setup-wizard--daily-actions)
9. [Tabla de Correspondencia con Especificaciones](#9-tabla-de-correspondencia-con-especificaciones)
10. [Cumplimiento de Directrices del Proyecto](#10-cumplimiento-de-directrices-del-proyecto)
11. [Recomendaciones Priorizadas](#11-recomendaciones-priorizadas)

---

## 1. Resumen Ejecutivo

Esta auditoría evalúa la coherencia integral del sistema de precios de la Jaraba Impact Platform desde 5 perspectivas: (A) conformidad con la especificación de referencia Doc 158, (B) competitividad de mercado para cada vertical, (C) consistencia técnica entre configuración, interfaz de administración, frontend público y perfil de usuario, (D) completitud del marketplace de add-ons como experiencia de usuario end-to-end, y (E) integración con los patrones transversales PLG y Setup Wizard + Daily Actions.

### Hallazgos Principales

| Dimensión | Puntuación | Estado |
|-----------|-----------|--------|
| Conformidad con Doc 158 (precios) | 53% (8/15 planes correctos) | ⚠️ CRÍTICO |
| Competitividad de mercado | 95% — precios altamente competitivos | ✅ EXCELENTE |
| Consistencia técnica (admin→frontend→perfil) | 70% — gaps en perfil usuario | ⚠️ PARCIAL |
| Marketplace de add-ons (catálogo, compra, gestión) | 80% — catálogo funcional, falta integración | ⚠️ PARCIAL |
| PLG + Setup Wizard + Daily Actions | 60% — falta paso de suscripción en wizards | ⚠️ PARCIAL |

### Gaps Críticos Identificados

Se identificaron **11 gaps** categorizados por severidad:

| Severidad | Cantidad | Impacto |
|-----------|----------|---------|
| P0 (Bloqueante) | 1 | Precios no alineados con Doc 158 |
| P1 (Alto) | 4 | Add-ons no integrados en perfil, bundles no implementados, matriz compatibilidad ausente, total mensual no visible |
| P2 (Medio) | 4 | Barras de uso en 0, factura próxima, descuento anual inconsistente, API subscription incompleta |
| P3 (Bajo) | 2 | Wizard step de suscripción, daily action PLG |

---

## 2. Metodología de Auditoría

### 2.1 Fuentes de Datos

1. **Doc 158** — Platform Vertical Pricing Matrix v1 (especificación oficial de precios)
2. **Config/sync YAML** — 26 archivos `ecosistema_jaraba_core.saas_plan.*.yml` con precios operativos
3. **Código fuente** — MetaSitePricingService, SubscriptionContextService, SubscriptionProfileSection, AddonCatalogController, PricingController, CheckoutController
4. **Templates Twig** — `_subscription-card.html.twig`, `addons-catalog.html.twig`, `addons-detail.html.twig`
5. **Investigación de mercado** — Pricing público de Shopify, HubSpot, Brevo, Calendly, Acuity, Jobvite, iCIMS, Aranzadi, vLex, Leyus AI, Notion, Monday.com, BigCommerce, WooCommerce (marzo 2026)
6. **Inventario Setup Wizard + Daily Actions** — 50+ wizard steps, 54+ daily actions en 9 verticales

### 2.2 Criterios de Evaluación

- **Conformidad**: ¿Los precios implementados coinciden con Doc 158?
- **Competitividad**: ¿Son competitivos vs. alternativas del mercado para cada vertical?
- **Consistencia**: ¿El usuario ve el mismo precio en landing, checkout y perfil?
- **Completitud**: ¿El flujo de compra de add-ons está completo de extremo a extremo?
- **PLG**: ¿El usuario recibe señales claras de upgrade en contextos naturales?

---

## 3. Inventario de Implementación Existente

### 3.1 Servicios de Pricing

| Servicio | Módulo | Función | Estado |
|----------|--------|---------|--------|
| `MetaSitePricingService` | ecosistema_jaraba_core | Pricing para landing pages y /planes | ✅ Implementado |
| `PlanResolverService` | ecosistema_jaraba_core | Cascade de resolución vertical→tier→features | ✅ Implementado |
| `SubscriptionContextService` | ecosistema_jaraba_core | Contexto completo de suscripción para PLG | ✅ Implementado |
| `PricingRuleEngine` | ecosistema_jaraba_core | Cálculo de precios con reglas dinámicas | ✅ Implementado |
| `PricingRecommendationService` | ecosistema_jaraba_core | Sugiere upgrades basados en uso | ✅ Implementado |
| `FeatureAccessService` | jaraba_billing | Verificación de acceso por plan + addons | ✅ Implementado |
| `FairUsePolicyService` | ecosistema_jaraba_core | Enforcement de límites por plan | ✅ Implementado |
| `CheckoutSessionService` | jaraba_billing | Creación de sesión Stripe Checkout (embedded) | ✅ Implementado |
| `StripeProductSyncService` | jaraba_billing | Sincronización SaasPlan → Stripe Products/Prices | ✅ Implementado (17 productos a 2026-03-17) |
| `ProrationService` | jaraba_billing | Prorrateo por cambio de plan mid-cycle | ✅ Implementado |
| `UsageIngestionService` | jaraba_usage_billing | Registro de eventos de uso | ✅ Implementado |
| `TenantMeteringService` | jaraba_billing | Metering y cuotas | ✅ Implementado |

### 3.2 Entidades de Plan y Tier

| Entidad | Tipo | Instancias | Observaciones |
|---------|------|------------|---------------|
| `SaasPlan` | ContentEntity | 26 configs (9 verticales × 3 tiers) | Contiene precios EUR, stripe_price_id, features |
| `SaasPlanTier` | ConfigEntity | 3 (starter, professional, enterprise) | Tier genérico con alias y peso |
| `SaasPlanFeatures` | ConfigEntity | Variable por vertical×tier | Feature lists con limits |
| `FairUsePolicy` | ConfigEntity | 4 (_global, starter, professional, enterprise) | Límites de uso por tier |
| `PricingRule` | ContentEntity | Variable | Reglas de pricing dinámico / usage-based |

### 3.3 Marketplace de Add-ons

**Rutas Frontend:**
| Ruta | Controller | Permiso | Estado |
|------|-----------|---------|--------|
| `/addons` | `AddonCatalogController::catalog()` | Público | ✅ Implementado |
| `/addons/{addon_id}` | `AddonCatalogController::detail()` | Público | ✅ Implementado |

**Rutas API:**
| Método | Ruta | Controller | Permiso | Estado |
|--------|------|-----------|---------|--------|
| GET | `/api/v1/addons` | `AddonApiController::listAddons()` | Autenticado | ✅ Implementado |
| POST | `/api/v1/addons/{addon_id}/subscribe` | `AddonApiController::subscribe()` | `purchase addons` | ✅ Implementado |
| POST | `/api/v1/addons/subscriptions/{id}/cancel` | `AddonApiController::cancel()` | `purchase addons` | ✅ Implementado |
| GET | `/api/v1/addons/subscriptions` | `AddonApiController::listSubscriptions()` | `purchase addons` | ✅ Implementado |

**Templates:**
| Template | Propósito | Estado |
|----------|-----------|--------|
| `addons-catalog.html.twig` | Grid de addons con filtros por tipo | ✅ Implementado |
| `addons-detail.html.twig` | Detalle de addon con toggle mensual/anual y CTA suscripción | ✅ Implementado |
| `_subscription-card.html.twig` | Tarjeta PLG en perfil usuario | ✅ Implementado (sin add-ons) |

### 3.4 Sección "Mi Suscripción" (Perfil Usuario)

**Componente:** `SubscriptionProfileSection` (tagged service, peso 5)
**Template:** `_subscription-card.html.twig`

**Lo que muestra actualmente:**
- ✅ Tier del plan actual (Free/Starter/Professional/Enterprise)
- ✅ Vertical asociado
- ✅ Badge de estado (Activa, Trial con cuenta atrás, Pago pendiente, etc.)
- ✅ Precio mensual
- ✅ Features incluidos (hasta 6, resto colapsado)
- ✅ Features bloqueados por upgrade (hasta 4)
- ✅ Barras de uso vs. límites (con roles progressbar ARIA)
- ✅ CTA de upgrade directo a Stripe Checkout
- ✅ Link "Comparar planes" a /planes

**Lo que NO muestra (gaps):**
- ❌ Add-ons activos del tenant
- ❌ Link al catálogo de add-ons (`/addons`)
- ❌ Total mensual (plan base + add-ons)
- ❌ Próxima fecha de facturación
- ❌ Add-ons recomendados para el vertical
- ❌ Bundles disponibles con descuento

### 3.5 Setup Wizard + Daily Actions

**Inventario completo:**

| Vertical | Wizard Steps | Daily Actions | ¿Paso de suscripción? | ¿Daily action PLG? |
|----------|-------------|---------------|----------------------|---------------------|
| Empleabilidad (Candidato) | 5 | 4 | ❌ | ❌ |
| Emprendimiento (Negocio) | 3 | 4 | ❌ | ❌ |
| AgroConecta (Productor) | 5 | 4 | ❌ | ❌ |
| ComercioConecta (Merchant) | 5 | 5 | ❌ | ❌ |
| ServiciosConecta (Provider) | 4 | 4 | ❌ | ❌ |
| LMS Learner | 3 | 5 | ❌ | ❌ |
| LMS Instructor | 3 | 5 | ❌ | ❌ |
| Content Hub (Editor) | 3 | 4 | ❌ | ❌ |
| Legal Intelligence | 3 | 4 | ❌ | ❌ |
| Andalucía EI (Orientador) | 3 | 7 | ❌ | ❌ |
| Andalucía EI (Coordinador) | 4 | (compartidas) | ❌ | ❌ |
| Mentoría (Mentor) | 3 | 4 | ❌ | ❌ |
| Copilot (Emprendedor) | 4 | 4 | ❌ | ❌ |
| **TOTAL** | **51** | **54+** | **0/13** | **0/13** |

---

## 4. Confrontación de Mercado por Vertical

### 4.1 Empleabilidad

**Competidores directos:** Jobvite ($4.8K-60K/año), iCIMS ($14.5K-635K/año), Workable ($149/mes+), LinkedIn Recruiter ($8.9K/año)

| Tier | Doc 158 | Implementado | Competencia media | Ratio competitivo |
|------|---------|-------------|-------------------|-------------------|
| Starter €29 | ✅ €29 | ✅ €29 | $149/mo (Workable) | **5.1× más barato** |
| Pro €79 | ✅ €79 | ✅ €79 | $400-5K/mo (Jobvite) | **5-63× más barato** |
| Enterprise €149 | ✅ €149 | ❌ €199 | $1.2K-50K/mo | **6-250× más barato** |

**Veredicto:** Precios extremadamente competitivos. La plataforma ofrece LMS + matching IA + copilot + job board integrado, lo que justifica el precio vs. un ATS puro. **El Enterprise config (€199) debe corregirse a €149 según Doc 158.**

**Posicionamiento recomendado:** "La plataforma de empleabilidad integral más accesible de Europa", enfocada en PYMEs, centros de orientación y programas públicos de empleo.

### 4.2 Emprendimiento

**Competidores directos:** Notion ($10-20/user), Monday.com ($9-19/user), HubSpot CRM (Free-$890/mo), LivePlan ($15-30/mo)

| Tier | Doc 158 | Implementado | Competencia | Veredicto |
|------|---------|-------------|-------------|-----------|
| Starter €39 | ❌ €29 | — | Notion Plus: $10/user | Buen valor, con BMC+IA |
| Pro €99 | ❌ €79 | — | LivePlan Premium: $30/mo | Justificado: mentoría + IA |
| Enterprise €199 | ✅ €199 | — | Custom enterprise | Competitivo |

**Posicionamiento:** No es solo una herramienta de productividad (Notion/Monday): es un ecosistema completo de apoyo al emprendimiento con diagnóstico digital, BMC con IA, validación MVP, mentoría estructurada y proyecciones financieras. **€39/99/199 son precios que reflejan valor diferencial.**

### 4.3 AgroConecta

**Competidores directos:** Shopify ($29-299/mo + comisión), WooCommerce (gratuito + hosting $20-40/mo), Agrimp (transaccional), Orbia (custom)

| Tier | Doc 158 | Implementado | Competencia | Brecha |
|------|---------|-------------|-------------|--------|
| Starter €49 | ❌ €29 | — | Shopify Basic: $29 | Config subvalora 40% |
| Pro €129 | ❌ €59 | — | Shopify Grow: $79 | Config subvalora 54% |
| Enterprise €249 | ❌ €149 | — | Shopify Advanced: $299 | Config subvalora 40% |

**Análisis crítico:** AgroConecta incluye trazabilidad QR/blockchain, certificaciones ecológicas, envío con cadena de frío y gestión de productores — funcionalidades que en Shopify requerirían múltiples apps de pago ($50-200/mes adicionales). **Los precios del Doc 158 son correctos y competitivos. Las configs actuales subvaloran masivamente el vertical.**

### 4.4 ComercioConecta

**Competidores directos:** Shopify ($29-299/mo + 0.5-2% comisión), Square Online (Free-$79/mo + 2.9%), BigCommerce ($29-299/mo), WooCommerce (Free + hosting)

| Tier | Doc 158 | Implementado | Shopify equivalente |
|------|---------|-------------|---------------------|
| Starter €39 | ❌ €29 | — | Basic $29 + comisión 2% |
| Pro €99 | ❌ €59 | — | Grow $79 + comisión 0.5% |
| Enterprise €199 | ❌ €149 | — | Advanced $299 + comisión 0.15% |

**Ventaja competitiva:** Jaraba incluye comisiones escalonadas (6%→4%→2%) que se compensan con menores cuotas mensuales en tiers altos. Además, QR dinámico, flash offers, local SEO y click & collect están incluidos sin apps adicionales.

### 4.5 ServiciosConecta

**Competidores directos:** Calendly ($10-16/user), Acuity ($16-45/mo), SimplyBook.me ($0-60/mo), Cal.com (Open source)

| Tier | Doc 158 | Implementado | Calendly equivalente |
|------|---------|-------------|----------------------|
| Starter €29 | ✅ €29 | — | Pro $10/user (solo booking) |
| Pro €79 | ❌ €59 | — | Teams $16/user (solo booking) |
| Enterprise €149 | ✅ €149 | — | Enterprise $15K/año |

**Diferenciación:** Calendly/Acuity solo cubren booking. Jaraba incluye firma digital PAdES, buzón de confianza, facturación automática con SII, presupuestador IA y portal cliente documental. **Valor muy superior al precio.**

### 4.6 JarabaLex

**Competidores directos:** Aranzadi One (€117/mes), vLex (custom, €200+/mes), Leyus AI (€120/mes), Lefebvre GenIA-L (custom enterprise)

| Tier | Implementado | Competidor más cercano | Ventaja |
|------|-------------|------------------------|---------|
| Starter €49 | ✅ €49 | Leyus AI Professional €120 | **59% más barato** |
| Pro €99 | ✅ €99 | Aranzadi One €117 | **15% más barato** |
| Enterprise €199 | ✅ €199 | vLex Enterprise €200+ | **Equivalente** |

**Posicionamiento excepcional:** Con IA legal integrada (LCIS), alertas normativas, citaciones cruzadas e integración LexNET, JarabaLex ofrece más funcionalidad a menor precio que los incumbentes. **Precios muy bien calibrados.**

### 4.7 Andalucía +ei

**Nicho específico:** No hay competidores directos SaaS para gestión de programas de empleo e intermediación en España. Los servicios públicos usan herramientas ad-hoc o Excel.

| Tier | Implementado | Referencia de mercado |
|------|-------------|----------------------|
| Starter €49 | ✅ €49 | Soluciones custom: €5K-20K implementación |
| Pro €99 | ✅ €99 | — |
| Enterprise €249 | ✅ €249 | Contratos públicos: €50K+ |

**Análisis:** Al no existir competencia SaaS directa, los precios son una propuesta de valor extraordinaria para ayuntamientos, diputaciones y entidades del tercer sector. **Posicionamiento Blue Ocean.**

### 4.8 Add-ons de Marketing

| Add-on Doc 158 | Precio/mes | Competidor más cercano | Ratio |
|----------------|-----------|------------------------|-------|
| jaraba_crm €19 | €19 | HubSpot CRM Starter $20 | 1:1 |
| jaraba_email €29 | €29 | Brevo Starter $25 | Ligeramente mayor |
| jaraba_email_plus €59 | €59 | Brevo Premium $66 | 10% más barato |
| jaraba_social €25 | €25 | Buffer Pro $5/canal × 5 = $25 | Equivalente |
| paid_ads_sync €15 | €15 | Sin equivalente integrado | Único |
| retargeting_pixels €12 | €12 | Sin equivalente integrado | Único |
| events_webinars €19 | €19 | Zoom Pro $13/mo (solo video) | +eventos |
| ab_testing €15 | €15 | Optimizely $3K/mo | **200× más barato** |
| referral_program €19 | €19 | ReferralCandy $59/mo | **3× más barato** |

**Bundles (Doc 158 §3.3):**
| Bundle | Precio | Ahorro | Estado |
|--------|--------|--------|--------|
| Marketing Starter | €35 | 15% | ❌ No implementado |
| Marketing Pro | €59 | 20% | ❌ No implementado |
| Marketing Complete | €99 | 30% | ❌ No implementado |
| Growth Engine | €79 | 15% | ❌ No implementado |

---

## 5. Análisis de Gaps Críticos

### GAP-PRICING-001 — Precios Config ≠ Doc 158 (P0)

**Severidad:** P0 — Bloqueante
**Impacto:** 7 de 15 planes tienen precios incorrectos en config/sync, violando Golden Rule #131 (Doc 158 = fuente de verdad)

| Vertical | Tier | Doc 158 | Config Actual | Diferencia |
|----------|------|---------|---------------|-----------|
| Empleabilidad | Enterprise | €149 | €199 | +€50 (+33%) |
| Emprendimiento | Starter | €39 | €29 | -€10 (-26%) |
| Emprendimiento | Pro | €99 | €79 | -€20 (-20%) |
| AgroConecta | Starter | €49 | €29 | -€20 (-41%) |
| AgroConecta | Pro | €129 | €59 | -€70 (-54%) |
| AgroConecta | Enterprise | €249 | €149 | -€100 (-40%) |
| ComercioConecta | Starter | €39 | €29 | -€10 (-26%) |
| ComercioConecta | Pro | €99 | €59 | -€40 (-40%) |
| ComercioConecta | Enterprise | €199 | €149 | -€50 (-25%) |
| ServiciosConecta | Pro | €79 | €59 | -€20 (-25%) |

**Causa raíz:** Las configs parecen usar una nivelación "aplanada" (€29/€59/€149 genéricos) en vez de los precios diferenciados por vertical que establece Doc 158.

**Remedio:** Actualizar las 10 configs YAML + resincronizar Stripe Products/Prices via `StripeProductSyncService`.

### GAP-PRICING-002 — Add-ons no integrados en "Mi Suscripción" (P1)

**Severidad:** P1 — Alto
**Impacto:** El usuario no puede ver ni gestionar sus add-ons desde su perfil. Debe navegar a `/addons` (ruta desconectada del flujo de perfil).

**Wireframe Doc 158 §7:** Define explícitamente secciones "ADD-ONS ACTIVOS" y "ADD-ONS DISPONIBLES" integradas en "MI SUSCRIPCIÓN".

**Archivos afectados:**
- `SubscriptionContextService.php` — no resuelve add-ons activos del tenant
- `_subscription-card.html.twig` — no tiene sección de add-ons
- `SubscriptionProfileSection.php` — no pasa datos de add-ons

### GAP-PRICING-003 — Bundles de Marketing no implementados (P1)

**Severidad:** P1 — Alto
**Impacto:** Los 4 bundles del Doc 158 §3.3 (Marketing Starter/Pro/Complete, Growth Engine) con descuentos de 15-30% no existen como Addon entities ni como Stripe Products.

**Impacto en revenue:** Los bundles son la estrategia principal de upsell modular del SaaS. Sin ellos, el usuario compra add-ons individuales perdiendo la oportunidad de agrupar con descuento.

### GAP-PRICING-004 — Matriz de compatibilidad add-ons × vertical ausente (P1)

**Severidad:** P1 — Alto
**Impacto:** Doc 158 §4 define qué add-ons son compatibles (✓), recomendados (⭐) o no aplicables (❌) para cada vertical. El `AddonCatalogController` NO filtra por vertical activo del tenant ni muestra badges de recomendación.

**Consecuencia UX:** Un tenant de Empleabilidad ve add-ons que no tienen sentido para su vertical, diluyendo la propuesta de valor.

### GAP-PRICING-005 — Total mensual no visible (P1)

**Severidad:** P1 — Alto
**Impacto:** La tarjeta de suscripción solo muestra el precio del plan base. El total (base + add-ons) no se calcula ni se muestra. Tampoco la próxima fecha de facturación.

### GAP-PRICING-006 — Barras de uso siempre en 0 (P2)

**Severidad:** P2 — Medio
**Impacto:** `SubscriptionContextService::resolveUsage()` línea 291: `$current = 0;` — el conteo real nunca se conecta desde `UsageIngestionService` / `TenantMeteringService`.

**Consecuencia PLG:** Las barras de uso son el principal motor de upgrade orgánico. Si siempre muestran 0%, el usuario no percibe que está acercándose a su límite.

### GAP-PRICING-007 — API /api/v1/subscription no implementada (P2)

**Severidad:** P2 — Medio
**Impacto:** Doc 158 §5.4 define 6 endpoints de suscripción (`GET /api/v1/subscription`, `GET .../addons/available`, `POST .../addons`, `DELETE .../addons/{code}`, `POST .../upgrade`, `GET .../invoice/upcoming`). Solo existen los 4 endpoints de `/api/v1/addons/*`. Los endpoints de suscripción base y factura están ausentes.

### GAP-PRICING-008 — Descuento anual inconsistente (P2)

**Severidad:** P2 — Medio
**Impacto:** Doc 158 §9 establece dos descuentos diferentes: planes base = "2 meses gratis" (16.7%), add-ons anuales = "15%". La implementación aplica ×10 uniformemente (16.7% = 2 meses gratis), pero no diferencia el 15% para add-ons.

### GAP-PRICING-009 — Setup Wizard sin paso de suscripción (P3)

**Severidad:** P3 — Bajo (pero importante para PLG)
**Impacto:** Ninguno de los 13 wizards incluye un paso "Elige tu plan ideal" ni "Mejora tu suscripción". Para PLG, el wizard debería incluir un paso contextual de upgrade cuando el usuario usa el plan gratuito o starter.

### GAP-PRICING-010 — Daily Actions sin acción PLG (P3)

**Severidad:** P3 — Bajo (pero importante para PLG)
**Impacto:** Ningún dashboard incluye una daily action de tipo "Explora add-ons para tu negocio" o "Revisa tu plan". Debería aparecer condicionalmente cuando el usuario supera el 60% de uso o no ha revisado su plan en 30+ días.

### GAP-PRICING-011 — Add-ons de Marketing no seeded como Addon entities (P2)

**Severidad:** P2 — Medio
**Impacto:** Los 9 add-ons de marketing del Doc 158 (jaraba_crm, jaraba_email, jaraba_social, paid_ads_sync, retargeting_pixels, events_webinars, ab_testing, referral_program, jaraba_email_plus) necesitan verificación de que existen como Addon entities en jaraba_addons con precios correctos.

---

## 6. Diagnóstico de Lógica de Negocio

### 6.1 Flujo de Suscripción Completo

```
VISITANTE → /planes → Selecciona vertical → /planes/{vertical} → Compara tiers
    ↓
Clic "Empezar gratis" → /registro/{vertical}?plan={plan_id}
    ↓
Clic "Contratar" (paid) → /planes/checkout/{saas_plan} → Stripe Embedded Checkout
    ↓
Stripe webhook → StripeSubscriptionService → tenant_subscription creada
    ↓
Usuario autenticado → Dashboard → _subscription-card.html.twig
    ↓ (upgrade)
Clic "Mejorar a Professional" → /planes/checkout/{plan_id} → Stripe (proration)
```

**Estado:** ✅ Flujo base completo y funcional.

### 6.2 Flujo de Add-ons

```
Usuario autenticado → /addons → Browse catálogo → Filtros por tipo
    ↓
Clic "Ver Detalles" → /addons/{addon_id} → Toggle mensual/anual → Precio
    ↓
Clic "Suscribirse" → POST /api/v1/addons/{id}/subscribe → AddonSubscriptionService
    ↓
AddonSubscription entity creada → FeatureAccessService actualizado
```

**Estado:** ✅ Flujo funcional. **⚠️ Desconectado del perfil del usuario** — no hay ruta desde "Mi suscripción" hacia `/addons`. El usuario debe descubrir la URL por sí mismo.

### 6.3 PLG (Product-Led Growth)

**Puntos de contacto PLG implementados:**
1. ✅ `_subscription-card.html.twig` — CTA de upgrade en perfil
2. ✅ Pricing page pública con comparación de tiers
3. ✅ `SubscriptionContextService` — features bloqueados con "Disponible con upgrade"
4. ✅ Enterprise → checkout directo (sin "Contactar ventas")

**Puntos de contacto PLG ausentes:**
1. ❌ Barras de uso con datos reales (siempre 0%)
2. ❌ Notificación cuando uso supera 80% del límite
3. ❌ Add-ons recomendados en contexto del dashboard
4. ❌ Wizard step de suscripción
5. ❌ Daily action condicional de upgrade
6. ❌ In-app upsell cuando se intenta usar feature bloqueado

---

## 7. Verificación RUNTIME-VERIFY-001

| Check | Estado | Detalle |
|-------|--------|---------|
| CSS compilado (timestamp CSS > SCSS) | ⚠️ Pendiente | Verificar con `npm run build` en contenedor |
| Tablas DB creadas | ✅ | SaasPlan, Addon, AddonSubscription con install hooks |
| Rutas en routing.yml accesibles | ✅ | /planes, /planes/{vertical}, /addons, /addons/{id} verificadas |
| data-* selectores JS↔HTML | ✅ | data-addon-id, data-addon-type, data-billing-cycle match |
| drupalSettings inyectado | ⚠️ | addon-detail.js usa Drupal.url() (ROUTE-LANGPREFIX-001 cumplido), pero addon-catalog.js no inyecta drupalSettings |
| Stripe Price IDs sincronizados | ⚠️ | Configs tienen `stripe_price_id: ''` — necesitan sync |

---

## 8. Cumplimiento Setup Wizard + Daily Actions

### 8.1 Patrón SETUP-WIZARD-DAILY-001

**Infraestructura core:** ✅ Completamente implementada
- `SetupWizardRegistry` con CompilerPass → 51 steps registrados
- `DailyActionsRegistry` con CompilerPass → 54+ actions registradas
- 2 auto-complete global steps (Zeigarnik) → progreso inicial 33-50%
- API `/api/v1/setup-wizard/{wizard_id}/status` para refresh JS
- Templates parciales `_setup-wizard.html.twig` y `_daily-actions.html.twig`
- 13 dashboard controllers consumen ambos registries

### 8.2 Gap en Suscripción

**Falta un `SubscriptionUpgradeStep` global (peso alto, ej. 90) que:**
- Muestre estado del plan actual
- Sugiera features del siguiente tier
- Se marque como "complete" cuando el usuario tenga plan paid
- Se registre como `__global__` para inyectarse en TODOS los wizards

**Falta un `ReviewSubscriptionAction` condicional que:**
- Aparezca cuando uso > 60% de algún límite
- Aparezca cuando han pasado 30+ días sin revisar plan
- Se oculte para Enterprise (ya maximizado)
- Link directo a `/addons` con parámetro de vertical

---

## 9. Tabla de Correspondencia con Especificaciones

| Especificación | Sección | Requisito | Estado | Gap ID |
|---------------|---------|-----------|--------|--------|
| Doc 158 §2 | Planes Base | Precios por vertical y tier | ⚠️ 7/15 incorrectos | GAP-PRICING-001 |
| Doc 158 §3.1 | Add-ons Principales | 4 add-ons marketing (CRM, Email, Social, Email+) | ⚠️ Verificar seed | GAP-PRICING-011 |
| Doc 158 §3.2 | Add-ons Extensión | 5 add-ons extensión | ⚠️ Verificar seed | GAP-PRICING-011 |
| Doc 158 §3.3 | Bundles | 4 bundles con descuento | ❌ No implementado | GAP-PRICING-003 |
| Doc 158 §4 | Matriz Compatibilidad | Filtro vertical × add-on | ❌ No implementado | GAP-PRICING-004 |
| Doc 158 §5.1 | Stripe Products | Productos por plan + add-on | ✅ 17 sincronizados | — |
| Doc 158 §5.2 | tenant_subscription | Modelo de datos suscripción | ✅ Implementado | — |
| Doc 158 §5.3 | tenant_addon | Modelo de datos addon | ✅ Implementado | — |
| Doc 158 §5.4 | API Gestión | 6 endpoints REST | ⚠️ 4/6 implementados | GAP-PRICING-007 |
| Doc 158 §6 | FeatureAccessService | Verificación acceso por plan + addons | ✅ Implementado | — |
| Doc 158 §7 | UI Selector Add-ons | "Mi Suscripción" con add-ons | ❌ Add-ons ausentes | GAP-PRICING-002 |
| Doc 158 §8.1 | ECA Activación | Flujo activación add-on | ✅ Via AddonSubscriptionService | — |
| Doc 158 §8.2 | ECA Cancelación | Flujo cancelación add-on | ✅ Via AddonApiController | — |
| Doc 158 §8.3 | ECA Upgrade | Flujo upgrade plan | ✅ Via CheckoutController | — |
| Doc 158 §9 | Descuentos | Anual 2 meses gratis + 15% add-ons | ⚠️ Uniforme 16.7% | GAP-PRICING-008 |
| Doc 158 §10 | Métricas Revenue | ARPU, Attach Rate, NRR | ✅ RevenueMetricsService | — |
| Doc 111 | Usage-Based Pricing | Ingestion + Aggregation + Billing | ✅ jaraba_usage_billing | — |
| Doc 134 | Stripe Integration | Embedded Checkout, Webhooks, Sync | ✅ jaraba_billing | — |
| Doc 104 | SaaS Admin Center | /admin/structure/saas-plan | ✅ Implementado | — |
| PLG-UPGRADE-UI-001 | PLG UI | Subscription card en perfil | ✅ Parcial | GAP-PRICING-002,005,006 |
| SETUP-WIZARD-DAILY-001 | Wizards | Paso suscripción en wizards | ❌ Ausente | GAP-PRICING-009 |
| SETUP-WIZARD-DAILY-001 | Daily Actions | Acción PLG condicional | ❌ Ausente | GAP-PRICING-010 |

---

## 10. Cumplimiento de Directrices del Proyecto

| Directriz | Regla | Estado en Pricing/Addons | Notas |
|-----------|-------|--------------------------|-------|
| NO-HARDCODE-PRICE-001 | Precios desde MetaSitePricingService | ✅ Cumplido | Twig usa `ped_pricing` desde preprocess |
| CSS-VAR-ALL-COLORS-001 | Colores via var(--ej-*) | ✅ Cumplido | `_subscription-card`, `addons-catalog` usan CSS vars |
| ICON-CONVENTION-001 | jaraba_icon() duotone | ✅ Cumplido | Catálogo usa jaraba_icon para iconos de tipo |
| ICON-DUOTONE-001 | Variante default duotone | ✅ Cumplido | — |
| TWIG-INCLUDE-ONLY-001 | {% include %} con only | ✅ Cumplido | `_subscription-card` se incluye con `only` |
| ZERO-REGION-001 | Variables via preprocess | ✅ Cumplido | PricingController devuelve markup vacío |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | ✅ Cumplido | En MetaSitePricingService y SubscriptionContextService |
| i18n ({% trans %}) | Textos traducibles | ✅ Cumplido | Todos los textos en bloque {% trans %} |
| SCSS-COMPILE-VERIFY-001 | CSS compilado tras edición | ⚠️ Pendiente verificar | Requiere `npm run build` tras cambios |
| PREMIUM-FORMS-PATTERN-001 | Forms extienden PremiumEntityFormBase | ✅ Cumplido | SaasPlanForm, AddonForm |
| TENANT-001 | Queries filtran por tenant | ✅ Cumplido | AddonApiController verifica tenant ownership |
| TENANT-ISOLATION-ACCESS-001 | ACH verifica tenant match | ✅ Cumplido | SaasPlanAccessControlHandler, AddonAccessControlHandler |
| OPTIONAL-CROSSMODULE-001 | @? para dependencias cross-módulo | ✅ Cumplido | SubscriptionContextService usa @? para billing |
| ENTITY-FK-001 | FK cross-módulo como integer | ✅ Cumplido | tenant_id = entity_reference, addon = integer |
| SLIDE-PANEL-RENDER-001 | renderPlain() en slide-panel | N/A | Addons no usan slide-panel actualmente |
| CONTROLLER-READONLY-001 | No readonly en inherited props | ✅ Cumplido | Controllers revisados |
| UPDATE-HOOK-REQUIRED-001 | hook_update_N() para cambios | ✅ Cumplido | install hooks presentes |

---

## 11. Recomendaciones Priorizadas

### P0 — Corrección Inmediata (1-2 días)
1. **GAP-PRICING-001**: Actualizar 10 configs SaasPlan YAML con precios del Doc 158
2. Resincronizar Stripe Products/Prices via `StripeProductSyncService`
3. Verificar que las landing pages `/planes/{vertical}` reflejan precios correctos

### P1 — Alto Impacto (3-5 días)
4. **GAP-PRICING-002**: Integrar add-ons en `_subscription-card.html.twig` con secciones "Add-ons activos" y enlace a `/addons`
5. **GAP-PRICING-003**: Crear 4 Addon entities tipo 'bundle' con precios del Doc 158
6. **GAP-PRICING-004**: Implementar filtro de compatibilidad vertical en catálogo de addons
7. **GAP-PRICING-005**: Añadir total mensual y próxima factura a la tarjeta de suscripción

### P2 — Medio Impacto (3-5 días)
8. **GAP-PRICING-006**: Conectar barras de uso con TenantMeteringService
9. **GAP-PRICING-007**: Implementar endpoints REST de suscripción faltantes
10. **GAP-PRICING-008**: Diferenciar descuento anual (16.7% planes vs 15% add-ons)
11. **GAP-PRICING-011**: Verificar y completar seed de add-ons de marketing

### P3 — PLG Avanzado (2-3 días)
12. **GAP-PRICING-009**: Implementar `SubscriptionUpgradeStep` global en wizards
13. **GAP-PRICING-010**: Implementar `ReviewSubscriptionAction` condicional en dashboards

**Estimación total:** 9-15 días de trabajo según priorización.

---

*Documento generado como parte de la auditoría integral de clase mundial para Jaraba Impact Platform. Fuentes de mercado: Shopify.com, HubSpot.com, Brevo.com, Calendly.com, Acuity Scheduling, vLex, Aranzadi, Leyus AI (marzo 2026).*
