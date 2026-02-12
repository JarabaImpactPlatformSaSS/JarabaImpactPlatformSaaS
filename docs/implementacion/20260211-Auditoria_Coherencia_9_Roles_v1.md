# 🔬 Auditoría de Coherencia Multi-Rol — Specs 20260118 vs Codebase Real

> **Fecha:** 2026-02-11  
> **Método:** Cross-referencia código real contra specs y `00_DIRECTRICES_PROYECTO.md`  
> **9 Roles:** Arquitecto SaaS · Ingeniero SW · UX · Drupal · Web · Theming · GrapesJS · SEO/GEO · IA

---

## ⚠️ CORRECCIÓN — Error en Auditoría Inicial

> [!CAUTION]
> **Mi auditoría anterior calificó Doc 134 (Stripe Billing) como 0%.** Esto fue **INCORRECTO**.  
> El codebase tiene ~35-40% de la funcionalidad billing ya implementada, fragmentada entre `ecosistema_jaraba_core` y `jaraba_foc`.

---

## 1. 🏗️ Arquitecto SaaS — Estado Real de Billing

### Funcionalidad Stripe EXISTENTE (no detectada inicialmente)

| Servicio | Módulo | Funciones |
|----------|--------|-----------|
| [JarabaStripeConnect](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Service/JarabaStripeConnect.php) | `ecosistema_jaraba_core` | Connect Express accounts, onboarding KYC, payments, refunds |
| [TenantSubscriptionService](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Service/TenantSubscriptionService.php) | `ecosistema_jaraba_core` | Trial 14d, activate, suspend, past_due + grace 7d, cancel deferred |
| [TenantMeteringService](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Service/TenantMeteringService.php) | `ecosistema_jaraba_core` | 8 métricas, usage tracking, bill calculation, IVA 21% |
| [WebhookService](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Service/WebhookService.php) | `ecosistema_jaraba_core` | HMAC-SHA256, retry con backoff, 11 event types |
| [StripeConnectService](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_foc/src/Service/StripeConnectService.php) | `jaraba_foc` | Destination Charges, Application Fee 5%, payouts |
| [SaasMetricsService](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_foc/src/Service/SaasMetricsService.php) | `jaraba_foc` | MRR, ARR, NRR, GRR, CAC, LTV, Rule of 40 |
| [FinancialTransaction](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_foc/src/Entity/FinancialTransaction.php) | `jaraba_foc` | Entidad inmutable append-only |
| [CostAllocation](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_foc/src/Entity/CostAllocation.php) | `jaraba_foc` | Distribución de costes |
| [FocMetricSnapshot](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_foc/src/Entity/FocMetricSnapshot.php) | `jaraba_foc` | Snapshots periódicos |

### 🔴 Incoherencia #1 — Duplicación Stripe Connect

```diff
- JarabaStripeConnect (en ecosistema_jaraba_core)
- StripeConnectService  (en jaraba_foc)
+ Ambos implementan Connect pero con APIs diferentes.
+ Necesitan fusionarse en un único servicio.
```

**Decisión necesaria:** ¿Consolidar en `jaraba_foc` (recomendado) o crear `jaraba_billing`?

### Estado CORREGIDO de Doc 134

| Requisito | Spec 134 | Implementado | Gap |
|-----------|----------|-------------|-----|
| Stripe Connect | 7 métodos | `JarabaStripeConnect` + `StripeConnectService` | 🟡 Duplicado |
| Subscriptions | CRUD completa | `TenantSubscriptionService` (trial/active/cancel) | 🟡 Falta sync con Stripe API |
| Metered Billing | Usage-based pricing | `TenantMeteringService` (8 métricas) | 🟡 Falta `stripe.usageRecords.create()` |
| Invoicing | Invoice entities | `calculateBill()` en Metering, sin entidad Invoice | 🟡 Falta Invoice entity |
| Webhooks | Stripe webhooks | `WebhookService` (genérico, no Stripe-specific) | 🟡 Falta Stripe endpoint signature verification |
| Customer Portal | Embedded Stripe Portal | No existe | ⬜ Pendiente |
| Tax Compliance | Stripe Tax | No existe | ⬜ Pendiente |
| 7 Content Entities billing_* | Spec define 7 entities | 3 en FOC + fields en Tenant | ⬜ Faltan 4 entities |

**Estimación corregida:** ~~300-360h~~ → **150-200h** (eliminando lo ya implementado)

---

## 2. 💻 Ingeniero SW — Patrones y Calidad

### ✅ Bien Implementado
- **50 services** en `ecosistema_jaraba_core/src/Service/` con DI correcta
- **17 entities** con interfaces, access handlers, list builders
- **Coding standards**: BIZ-*, SEC-*, BE-* reference codes en comments
- **CI Pipeline**: 4 jobs (lint, test, security, build) + `fitness-functions.yml`

### 🔴 Incoherencias

| # | Problema | Impacto |
|---|----------|---------|
| 2 | **0 PHPUnit tests** — `phpunit.xml` configurado pero 0 archivos `*Test.php` en custom modules | CI pasa vacío |
| 3 | **Constructores mezclados** — Algunos usan `__construct(protected ...)` (PHP 8.2+), otros usan el patrón pre-8.0 con `$this->...` | Inconsistencia menor |
| 4 | **Services God Object** — `ecosistema_jaraba_core` tiene 50 servicios, debería extraerse billing/subscription | La spec sugiere `jaraba_billing` pero el código real lo tiene en core |
| 5 | **Directrices dicen 8 módulos** con `package.json`, hay **14 realmente** | Doc desactualizado |

---

## 3. 🎨 Ingeniero UX — Frontend

### ✅ Bien Implementado
- **Patrón "Zero Region"**: Templates limpios `page--*.html.twig` con partials
- **Slide Panel** pattern para CRUD inline
- **Premium Cards** pattern documentado con workflow
- **Copiloto FAB** contextual con 5 modos
- **Desbloqueo Progresivo** UX para Emprendimiento (12 semanas)

### 🟡 Gaps UX

| # | Gap | Impacto |
|---|-----|---------|
| 6 | **Sin Consent Manager** en `web/modules/custom` — conversación history indica implementación pero no hay módulo | GDPR compliance |
| 7 | **10 Cypress E2E tests** existen pero sin cobertura de flujos billing/payment | Happy paths no testados |

---

## 4. 🏛️ Ingeniero Drupal — Entities & Architecture

### ✅ Correcto
- Single-Instance Multi-Tenant con Group module
- Content Entities con annotations PHP 8 correctas
- `hook_cron`, `hook_entity_insert/update` para automatizaciones
- Rutas REST `/api/v1/*` + Swagger
- Entidades inmutables (FOC `FinancialTransaction`) correctamente diseñadas

### 🟡 Gap

| # | Gap | Detalle |
|---|-----|---------|
| 8 | **Tenant entity** almacena `subscription_status` como field pero datos de billing están en `Drupal\State` (grace period, cancel_at) | Mezcla persistence layers — State no es auditable ni exportable |

---

## 5. 🌐 Web & Theming

### ✅ Correcto
- **Federated Design Tokens**: `_variables.scss` + `_injectable.scss` como SSOT
- **14 módulos** con `package.json` para compilación Dart Sass
- **26 SCSS components** en theme
- **Style Presets** entity con `PresetApplicatorService`
- **CSS Custom Properties** `var(--ej-*)` en módulos satélite

### 🟡 Gap menor

| # | Gap | Detalle |
|---|-----|---------|
| 9 | **Directrices** dicen "8 módulos con package.json" — realmente son **14** | Doc desactualizado |

---

## 6. 🧩 Ingeniero GrapesJS — Page Builder

### ✅ Correcto
- **8 entities**: PageContent, PageTemplate, FeatureCard, HomepageContent, IntentionCard, ExperimentVariant, StatItem + más
- **19 controllers** + **12 services** + **16 forms**
- `SchemaOrgService` para JSON-LD automático
- Template Registry SSoT v5.0 con Feature Flags
- A/B Testing con ExperimentVariant

### 🟡 Gap

| # | Gap | Detalle |
|---|-----|---------|
| 10 | **Page Config Button** (última sesión debugeada) — estado pendiente de verificación | Funcionalidad bloqueante para el editor |

---

## 7. 📊 SEO y GEO

### ✅ Bien Implementado (uno de los puntos más fuertes)
- **`jaraba_geo` module**: Schema.org Organization, WebSite, JobPosting, Product, FAQ, Article, LocalBusiness, ProfessionalService
- **`SchemaOrgService`** en Page Builder: FAQPage, BreadcrumbList, JobPosting, Course, Product
- **`llms.txt`** en webroot
- **Answer Capsules** pattern documentado (primeros 150 chars optimizados)
- **`MultilingualGeoService`**: hreflang multi-idioma
- **`VideoGeoService`**: Video Schema.org + YouTube SEO

### Sin gaps significativos ✅

---

## 8. 🤖 Ingeniero IA

### ✅ Bien Implementado
- **AI Trilogy completa**: Content Hub + Skills + Tenant Knowledge
- **AI Orchestration**: `AiProviderPluginManager` (no HTTP directo)
- **RAG + Qdrant**: `KbIndexerService`, chunking, embeddings
- **Copiloto v2**: 5 modos, 8 servicios, FeatureUnlock
- **AI Guardrails**: `AIGuardrailsService` (PII, prompt validation)
- **FinOps AI**: `AICostOptimizationService`, token budgets

### Sin gaps significativos ✅

---

## 📋 Resumen de Incoherencias

| # | Tipo | Descripción | Severidad |
|---|------|-------------|-----------|
| 1 | ~~Duplicación~~ | ~~`JarabaStripeConnect` duplicado~~ → **✅ RESUELTO 2026-02-11**: Eliminado (0 usos). `StripeConnectService` (FOC) es única implementación | ✅ |
| 2 | ~~Tests vacíos~~ | ~~CI pipeline ejecuta PHPUnit sobre 0 tests~~ → **✅ RESUELTO 2026-02-11**: 227 tests (208 Unit + 13 Kernel + 6 Functional) | ✅ |
| 3 | ~~Constructores~~ | ~~PHP 8.2 promoted vs legacy~~ → **✅ RESUELTO 2026-02-11**: Convención documentada en `coding_conventions.md` | ✅ |
| 4 | ~~God Object~~ | ~~50 services en core~~ → **✅ RESUELTO 2026-02-11**: 8 billing services extraídos a `jaraba_billing` module con aliases backward-compatible | ✅ |
| 5 | ~~Docs desactualizados~~ | ~~Directrices dicen 8 `package.json`, hay 14~~ → **✅ RESUELTO 2026-02-09**: corregido en Índice General v12.2.0 | ✅ |
| 6 | ~~Consent Manager~~ | ~~Implementado en conversación pero no en codebase~~ → **✅ RESUELTO 2026-02-11**: Existe en `jaraba_analytics` (ConsentRecord, ConsentService, ConsentController, consent-banner.js/scss) + `gdpr_consent` contrib | ✅ |
| 7 | ~~E2E billing~~ | ~~No hay Cypress tests para billing~~ → **✅ RESUELTO 2026-02-11**: `billing.cy.js` con 12 tests (4 escenarios) | ✅ |
| 8 | ~~State vs Entity~~ | ~~Billing dates en State~~ → **✅ YA MIGRADO**: `grace_period_ends`, `cancel_at`, `current_period_end` son base fields de Tenant | ✅ |
| 9 | ~~Doc count~~ | ~~Número de módulos SCSS desactualizado en directrices~~ → **✅ RESUELTO 2026-02-09**: corregido en Índice General v12.2.0 | ✅ |
| 10 | ~~Page Config~~ | ~~Button debugging pendiente~~ → **✅ RESUELTO 2026-02-11**: Permiso inexistente `use page builder` → `edit page builder content` | ✅ |

---

## 🎯 Plan de Acción Corregido (por prioridad real)

### P0 — Coherencia Inmediata (1-2 días)

1. **Fusionar Stripe Connect** → `jaraba_foc` como autoridad, deprecar `JarabaStripeConnect` en core
2. **Crear primer PHPUnit** → Test para `TenantSubscriptionService` (unit) y `TenantMeteringService` (unit)  
3. **Verificar Consent Manager** → ¿Se implementó en contrib o se perdió?

### P1 — Completar Billing (~150-200h)

4. Crear entities `billing_invoice`, `billing_payment`, `billing_customer`, `billing_usage_record`
5. Conectar `TenantSubscriptionService` con Stripe Subscriptions API real
6. Stripe webhook endpoint (`/stripe/webhook`) con signature verification
7. Customer Portal embedding

### P2 — Infrastructure para producción (~40-50h)

8. `docker-compose.prod.yml` + `Dockerfile`
9. Prometheus/Grafana stack básico
10. GoLive Runbook (Doc 139)

### P3 — Actualizar Documentación

11. Corregir `00_DIRECTRICES_PROYECTO.md` — 14 modules con package.json, no 8
12. Actualizar mapeo de specs (Stripe es 35-40%, no 0%)
