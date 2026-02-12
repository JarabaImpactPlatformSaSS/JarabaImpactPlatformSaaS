# 📋 Tech Debt — Auditoría Coherencia (2026-02-11)

> **Origen:** [Auditoría Coherencia 9 Roles](../implementacion/20260211-Auditoria_Coherencia_9_Roles_v1.md)
> **Fecha:** 2026-02-11
> **Estado:** 10/10 resueltas ✅ COMPLETO

---

## ✅ Resueltas

| # | Incoherencia | Resolución | Fecha |
|---|-------------|-----------|-------|
| 2 | 0 PHPUnit tests | 227 tests (208 Unit + 13 Kernel + 6 Functional) | 2026-02-11 |
| 5 | Docs: 8 package.json | Corregido a 14 en Índice General v12.2.0 | 2026-02-09 |
| 6 | Consent Manager ausente | Existe en `jaraba_analytics` (ConsentRecord, ConsentService, consent-banner.js) | 2026-02-11 |
| 9 | Doc count SCSS modules | Corregido en Índice General v12.2.0 | 2026-02-09 |

---

## 🟡 Tech Debt Pendiente

### ~~TD-001: Stripe Connect Duplicación (#1 — Alta)~~ ✅ RESUELTO 2026-02-11

**Resolución:** `JarabaStripeConnect.php` tenía **0 usos** en el codebase. Service definition ya había sido removida. Archivo eliminado, `services.yml` limpiado.

- `StripeConnectService` (FOC) es la única implementación activa
- Tests: 332 tests, 0 errores tras eliminación

---

### ~~TD-002: Constructores PHP 8.2 vs Legacy (#3 — Baja)~~ ✅ RESUELTO 2026-02-11

**Resolución:** Convención documentada en [`coding_conventions.md`](coding_conventions.md):
- **Servicios nuevos** → PHP 8.2 Constructor Promotion
- **Servicios existentes** → Legacy aceptado, refactorizar al modificar
- `declare(strict_types=1)` obligatorio en archivos nuevos

---

### ~~TD-003: God Object — 50 Services en Core (#4 — Media)~~ ✅ RESUELTO 2026-02-11

**Resolución:** 8 servicios billing extraídos a nuevo módulo `jaraba_billing`:
- `PlanValidator`, `TenantSubscriptionService`, `TenantMeteringService`
- `PricingRuleEngine`, `ReverseTrialService`, `ExpansionRevenueService`
- `ImpactCreditService`, `SyntheticCfoService`

Aliases backward-compatible en `ecosistema_jaraba_core.services.yml`.
Verificado: 332 tests, 0 errores.

---

### ~~TD-004: E2E Billing Cypress Tests (#7 — Media)~~ ✅ RESUELTO 2026-02-11

**Resolución:** Creado `billing.cy.js` con 12 tests en 4 escenarios:
- Plan display & limits (3 tests)
- Usage dashboard / metering (3 tests)
- Trial expiration warning (2 tests)
- Plan upgrade flow (3 tests)

Env vars añadidos en `cypress.config.js`: `billingUrl`, `plansUrl`.

---

### ~~TD-005: State vs Entity para Billing (#8 — Media)~~ ✅ YA MIGRADO

**Hallazgo:** Los campos billing YA son base fields de Tenant entity:
- `grace_period_ends` (línea 536) — comentario: "Migrado desde State API"
- `cancel_at` (línea 548) — comentario: "Migrado desde State API"
- `current_period_end` (línea 522)
- `stripe_customer_id`, `stripe_subscription_id`, `stripe_connect_id`

**Verificación:** Zero usages de State API para billing data en `/src/Service/`.

**Mejora menor pendiente:** Añadir campo `cancel_reason` (string) para trazabilidad completa.

---

### ~~TD-006: Page Config Button (#10 — Media)~~ ✅ RESUELTO 2026-02-11

**Root cause:** Ruta `/api/v1/pages/{id}/config` requería permiso `'use page builder'` que **no existía** en `permissions.yml` → 403 silencioso para todos los usuarios.

**Fix:** Cambiado a `'edit page builder content'` (consistente con canvas save/update routes).
- `routing.yml` línea 722
- `PageConfigApiController.php` docblock actualizado

---

### ~~TD-007: hero--split Rompe Layout Admin (#11 — Alta)~~ ✅ RESUELTO 2026-02-11

**Root cause:** La clase `hero--split` se aplicaba incondicionalmente al `<body>` desde `jaraba_theming_preprocess_html`, creando un grid 2 columnas que rompía el layout de páginas admin como `/admin/reports/dblog`.

**Fix multi-capa:**
1. `jaraba_theming.module` — Guard admin route: `isAdminRoute()` + prefijos `system.*`, `dblog.*` retorna antes de inyectar clases
2. `CriticalCssService.php` — Retorna NULL para rutas admin, evitando cargar `homepage.css` (que contiene `.hero--split`)
3. `_site-builder.scss` — Override CSS comentado como respaldo

- Verificado: body `display: block`, `grid-template-columns: none` en admin pages
- Tests: 332 tests, 926 assertions, 0 errores

---

### ~~TD-008: Billing Extraction Namespace Type-Hints (#12 — Media)~~ ✅ RESUELTO 2026-02-11

**Root cause:** Tras la extracción de 8 servicios billing a `jaraba_billing`, los consumidores en `ecosistema_jaraba_core` mantenían `use` statements apuntando al namespace antiguo, causando `TypeError` en strict type checking.

**Archivos corregidos:**
- `TenantManager.php` — `PlanValidator`, `TenantSubscriptionService` → `Drupal\jaraba_billing\Service\*`
- `UsageDashboardController.php` — `PricingRuleEngine`, `TenantMeteringService` → `Drupal\jaraba_billing\Service\*`
- `UsageApiController.php` — `PricingRuleEngine`, `TenantMeteringService` → `Drupal\jaraba_billing\Service\*`
- `PlanValidator.php` — Añadido método `getAvailableFeatures()` faltante

- Tests: 332 tests, 926 assertions, 0 errores
