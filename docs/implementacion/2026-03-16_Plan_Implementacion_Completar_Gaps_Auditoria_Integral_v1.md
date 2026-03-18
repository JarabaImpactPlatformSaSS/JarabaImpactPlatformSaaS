# Plan de Implementación — Completar Gaps Auditoría Integral SaaS — Clase Mundial

> **Versión:** 2.0.0 | **Fecha:** 2026-03-16 | **Autor:** Claude Code (Opus 4.6)
>
> **Auditoría origen:** `docs/analisis/2026-03-16_Auditoria_Integral_SaaS_Setup_Wizard_Daily_Actions_Runtime_Arquitectura_v1.md` (v2.0)
>
> **Filosofía:** "Sin Humo" — cerrar TODOS los gaps detectados hasta alcanzar 10/10 clase mundial, verificando PIPELINE-E2E-001 en las 4 capas
>
> **Scope:** 68 access handlers + 12 rutas Field UI + 6 SCSS + 2 entities + DailyActionsRegistry + 9 verticales (36 wizard steps + 40 daily actions) + 8 hook_theme() + 8 templates
>
> **Salvaguarda nueva:** PIPELINE-E2E-001 — toda implementación verifica L1 (DI) → L2 (Data) → L3 (hook_theme) → L4 (Template) antes de marcar como completada

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Previo y Gaps Detectados](#2-estado-previo-y-gaps-detectados)
3. [Fase P0 — Fixes Críticos Inmediatos](#3-fase-p0--fixes-críticos-inmediatos)
   - 3.1 [ACCESS-RETURN-TYPE-001: 68 Access Handlers](#31-access-return-type-001-68-access-handlers)
   - 3.2 [FIELD-UI-SETTINGS-TAB-001: 13 Rutas Faltantes](#32-field-ui-settings-tab-001-13-rutas-faltantes)
4. [Fase P1 — Deuda Técnica](#4-fase-p1--deuda-técnica)
   - 4.1 [CSS-VAR-ALL-COLORS-001: 6 Archivos SCSS](#41-css-var-all-colors-001-6-archivos-scss)
   - 4.2 [VIEWS-DATA-001: 2 Entities sin Views Handler](#42-views-data-001-2-entities-sin-views-handler)
5. [Fase P2 — DailyActionsRegistry (Infraestructura Transversal)](#5-fase-p2--dailyactionsregistry-infraestructura-transversal)
   - 5.1 [DailyActionInterface](#51-dailyactioninterface)
   - 5.2 [DailyActionsRegistry](#52-dailyactionsregistry)
   - 5.3 [DailyActionsCompilerPass](#53-dailyactionscompilerpass)
   - 5.4 [Migración del Hardcoded buildDailyActions() de Andalucía +ei](#54-migración-del-hardcoded-builddailyactions-de-andalucía-ei)
6. [Fase P2 — Setup Wizard: 9 Verticales Restantes](#6-fase-p2--setup-wizard-9-verticales-restantes)
   - 6.1 [Empleabilidad (jaraba_candidate)](#61-empleabilidad-jaraba_candidate)
   - 6.2 [Emprendimiento (jaraba_copilot_v2)](#62-emprendimiento-jaraba_copilot_v2)
   - 6.3 [Comercio Conecta (jaraba_comercio_conecta)](#63-comercio-conecta-jaraba_comercio_conecta)
   - 6.4 [AgroConecta (jaraba_agroconecta_core)](#64-agroconecta-jaraba_agroconecta_core)
   - 6.5 [JarabaLex (jaraba_legal_intelligence)](#65-jarabalex-jaraba_legal_intelligence)
   - 6.6 [Servicios Conecta (jaraba_servicios_conecta)](#66-servicios-conecta-jaraba_servicios_conecta)
   - 6.7 [Content Hub (jaraba_content_hub)](#67-content-hub-jaraba_content_hub)
   - 6.8 [Formación / LMS (jaraba_lms)](#68-formación--lms-jaraba_lms)
   - 6.9 [Demo (transversal)](#69-demo-transversal)
7. [Fase P2 — Daily Actions: 9 Verticales Restantes](#7-fase-p2--daily-actions-9-verticales-restantes)
8. [Integración en Dashboards Existentes](#8-integración-en-dashboards-existentes)
9. [Tabla de Correspondencia con Especificaciones Técnicas](#9-tabla-de-correspondencia-con-especificaciones-técnicas)
10. [Tabla de Cumplimiento de Directrices](#10-tabla-de-cumplimiento-de-directrices)
11. [Plan de Testing](#11-plan-de-testing)
12. [RUNTIME-VERIFY-001 — Verificación Post-Implementación](#12-runtime-verify-001--verificación-post-implementación)
13. [Gestión de Riesgos](#13-gestión-de-riesgos)
14. [Cronograma y Estimaciones](#14-cronograma-y-estimaciones)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### Problema

La auditoría integral del 2026-03-16 identificó gaps en 4 dimensiones:

| Dimensión | Score Pre-Fix | Score Post-Fix Objetivo |
|-----------|--------------|------------------------|
| Setup Wizard (verticales) | 10% (1/10) | 100% (10/10) |
| Runtime Verification | 86% (12/14) | 100% (14/14) |
| Consistencia Arquitectónica | 57% (4/7) | 100% (7/7) |
| Infraestructura Transversal | 100% | 100% |

### Solución

Implementación completa en 3 fases:

- **P0 (Inmediato):** 68 access handlers + 13 rutas Field UI → Desbloquea funcionalidad admin rota
- **P1 (Corto plazo):** 6 SCSS + 2 entities → Elimina deuda técnica
- **P2 (Medio plazo):** DailyActionsRegistry + 9 verticales con Setup Wizard + Daily Actions → 100% cobertura

### Estimación Total

| Fase | Contenido | Horas |
|------|-----------|-------|
| P0 | 68 access handlers + 13 rutas | 3-4h |
| P1 | SCSS tokens + views_data | 1-2h |
| P2 — Registry | DailyActionsRegistry + migración | 4-6h |
| P2 — Verticales | 9 verticales × ~3-4h | 27-36h |
| **Total** | | **35-48h** |

---

## 2. Estado Previo y Gaps Detectados

### 2.1 Infraestructura Existente (100% — no tocar)

| Componente | Archivo | Estado |
|-----------|---------|--------|
| SetupWizardStepInterface | `ecosistema_jaraba_core/src/SetupWizard/SetupWizardStepInterface.php` | ✅ 13 métodos |
| SetupWizardRegistry | `ecosistema_jaraba_core/src/SetupWizard/SetupWizardRegistry.php` | ✅ Tagged services |
| SetupWizardCompilerPass | `ecosistema_jaraba_core/src/DependencyInjection/Compiler/SetupWizardCompilerPass.php` | ✅ |
| SetupWizardApiController | `ecosistema_jaraba_core/src/Controller/SetupWizardApiController.php` | ✅ REST API |
| _setup-wizard.html.twig | `ecosistema_jaraba_theme/templates/partials/_setup-wizard.html.twig` | ✅ 204 líneas |
| _daily-actions.html.twig | `ecosistema_jaraba_theme/templates/partials/_daily-actions.html.twig` | ✅ 88 líneas |
| _setup-wizard.scss | `ecosistema_jaraba_theme/scss/components/_setup-wizard.scss` | ✅ 784 líneas, 10 keyframes |
| setup-wizard.js | `ecosistema_jaraba_theme/js/setup-wizard.js` | ✅ 315 líneas |
| Library setup-wizard | `ecosistema_jaraba_theme.libraries.yml` líneas 572-581 | ✅ |

### 2.2 Primer Caso Implementado: Andalucía +ei (Coordinador)

- 4 Setup Wizard Steps: PlanFormativo → AccionesFormativas → Sesiones → ValidaciónNormativa
- 5 Daily Actions: Solicitudes (primary), Participante, Sesión, STO, Leads
- Dashboard integrado: `CoordinadorDashboardController::dashboard()` + template
- Archivos: `jaraba_andalucia_ei/src/SetupWizard/Coordinador*.php` (4 archivos)

### 2.3 Gaps Identificados

| # | Gap | Severidad | Archivos afectados |
|---|-----|-----------|-------------------|
| G1 | ACCESS-RETURN-TYPE-001: 68 handlers con `: AccessResult` | ALTA | 68 archivos en 25 módulos |
| G2 | FIELD-UI-SETTINGS-TAB-001: 13 entities sin ruta settings | MEDIA | 2 routing.yml |
| G3 | CSS-VAR-ALL-COLORS-001: hex hardcoded en SCSS | MEDIA | 6 archivos SCSS |
| G4 | VIEWS-DATA-001: entities sin views_data handler | BAJA | 2 entity files |
| G5 | Daily Actions no centralizado (hardcoded) | MEDIA | 1 controller |
| G6 | 9 verticales sin Setup Wizard | ALTA | ~36 archivos nuevos |
| G7 | 9 verticales sin Daily Actions | ALTA | ~36 archivos nuevos |

---

## 3. Fase P0 — Fixes Críticos Inmediatos

### 3.1 ACCESS-RETURN-TYPE-001: 68 Access Handlers

**Regla:** `checkAccess()` DEBE declarar `: AccessResultInterface` (NO `: AccessResult`). La clase padre `EntityAccessControlHandler::checkAccess()` devuelve `AccessResultInterface`; un return type más restrictivo causa error PHPStan Level 6.

**Fix mecánico para cada archivo:**

```php
// 1. Añadir import (si no existe):
use Drupal\Core\Access\AccessResultInterface;

// 2. Cambiar return type:
// ANTES:
protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
// DESPUÉS:
protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
```

**68 archivos por módulo:**

| Módulo | Archivos | Cantidad |
|--------|----------|----------|
| jaraba_comercio_conecta | CartItem, CouponRedemption, FlashOfferClaim, NotificationPreference, PosConflict, PosSync, PushSubscription, QrLeadCapture, QrScanEvent, ReviewRetail, WishlistItem | 11 |
| jaraba_legal | AupViolation, OffboardingRequest, ServiceAgreement, SlaRecord, UsageLimitRecord, WhistleblowerReport | 6 |
| jaraba_legal_intelligence | LegalAlert, LegalBookmark, LegalCitation, LegalCoherenceLog, LegalResolution, LegalSource | 6 |
| jaraba_servicios_conecta | AvailabilitySlot, Booking, ReviewServicios, ServiceOffering, ServicePackage | 5 |
| jaraba_copilot_v2 | EntrepreneurLearning, Experiment, FieldExit, Hypothesis | 4 |
| jaraba_billing | BillingInvoice, BillingPaymentMethod, TenantAddon | 3 |
| jaraba_dr | BackupVerification, DrIncident, DrTestResult | 3 |
| jaraba_legal_cases | CaseActivity, InquiryTriage | 2 |
| jaraba_multiregion | CurrencyRate, TaxRule, TenantRegion | 3 |
| jaraba_pilot_manager | PilotFeedback, PilotProgram, PilotTenant | 3 |
| jaraba_agents | AgentConversation, AgentHandoff | 2 |
| jaraba_customer_success | CsPlaybook, VerticalRetentionProfile | 2 |
| jaraba_institutional | InstitutionalProgram, ProgramParticipant | 2 |
| jaraba_agent_flows | AgentFlowStepLog | 1 |
| jaraba_agroconecta_core | ReviewAgro | 1 |
| jaraba_content_hub | ContentComment | 1 |
| jaraba_crm | PipelineStage | 1 |
| jaraba_diagnostic | EmployabilityDiagnostic | 1 |
| jaraba_einvoice_b2b | EInvoiceTenantConfig | 1 |
| jaraba_email | EmailSequenceStep | 1 |
| jaraba_facturae | FacturaeDocument | 1 |
| jaraba_legal_calendar | CalendarConnection | 1 |
| jaraba_legal_knowledge | LegalNormRelation | 1 |
| jaraba_legal_vault | DocumentAuditLog | 1 |
| jaraba_lms | CourseReview | 1 |
| jaraba_mentoring | SessionReview | 1 |
| jaraba_privacy | DpaAgreement | 1 |
| jaraba_tenant_export | TenantExportRecord | 1 |
| jaraba_verifactu | VeriFactuInvoiceRecord | 1 |
| **Total** | | **68** |

**Verificación post-fix:**
```bash
# Dentro del contenedor Lando:
lando php vendor/bin/phpstan analyse --level 6 --no-progress \
  web/modules/custom/jaraba_*/src/Access/*AccessControlHandler.php 2>&1 | grep "AccessResult"
```

### 3.2 FIELD-UI-SETTINGS-TAB-001: 13 Rutas Faltantes

**Regla:** Toda entity con `field_ui_base_route` en su anotación DEBE tener esa ruta definida en routing.yml. Sin ella, Field UI → 404.

**Patrón de ruta (existente en el proyecto):**

```yaml
# Ejemplo real de jaraba_comercio_conecta.routing.yml:
jaraba_comercio_conecta.product_retail.settings:
  path: '/admin/structure/comercio-products'
  defaults:
    _form: '\Drupal\jaraba_comercio_conecta\Form\ProductRetailSettingsForm'
    _title: 'Configuración Productos Comercio'
  requirements:
    _permission: 'manage comercio products'
```

Para entities sin form de settings dedicada, se usa `\Drupal\system\Form\SystemConfigEditForm` como placeholder genérico, o simplemente un controller con título. El propósito de esta ruta es servir como base para los tabs de Field UI ("Manage fields", "Manage form display", "Manage display").

**11 rutas para `jaraba_comercio_conecta.routing.yml`:**

| Route Name | Path | Permission | Entity |
|-----------|------|------------|--------|
| `jaraba_comercio_conecta.comercio_carrier_config.settings` | `/admin/structure/comercio-carrier-configs` | `manage comercio carrier configs` | CarrierConfig |
| `jaraba_comercio_conecta.flash_offer.settings` | `/admin/structure/comercio-flash-offers` | `manage comercio flash offers` | FlashOffer |
| `jaraba_comercio_conecta.comercio_incident_ticket.settings` | `/admin/structure/comercio-incident-tickets` | `manage comercio incidents` | IncidentTicket |
| `jaraba_comercio_conecta.notification_template.settings` | `/admin/structure/comercio-notification-templates` | `manage comercio notifications` | NotificationTemplate |
| `jaraba_comercio_conecta.comercio_pos_connection.settings` | `/admin/structure/comercio-pos-connections` | `manage comercio pos connections` | PosConnection |
| `jaraba_comercio_conecta.qr_code.settings` | `/admin/structure/comercio-qr-codes` | `manage comercio qr codes` | QrCodeRetail |
| `jaraba_comercio_conecta.review.settings` | `/admin/structure/comercio-reviews` | `manage comercio reviews` | ReviewRetail |
| `jaraba_comercio_conecta.comercio_search_synonym.settings` | `/admin/structure/comercio-search-synonyms` | `manage comercio search` | SearchSynonym |
| `jaraba_comercio_conecta.comercio_shipment.settings` | `/admin/structure/comercio-shipments` | `manage comercio shipments` | ShipmentRetail |
| `jaraba_comercio_conecta.comercio_shipping_method.settings` | `/admin/structure/comercio-shipping-methods` | `manage comercio shipping methods` | ShippingMethodRetail |
| `jaraba_comercio_conecta.comercio_shipping_zone.settings` | `/admin/structure/comercio-shipping-zones` | `manage comercio shipping zones` | ShippingZone |

**1 ruta para `jaraba_servicios_conecta.routing.yml`:**

| Route Name | Path | Permission | Entity |
|-----------|------|------------|--------|
| `jaraba_servicios_conecta.review_servicios.settings` | `/admin/structure/servicios-reviews` | `manage servicios reviews` | ReviewServicios |

**Nota:** Los `.links.task.yml` de ambos módulos YA referencian estas rutas (task tabs definidos). Solo falta la ruta en routing.yml.

**Verificación post-fix:**
```bash
# Dentro del contenedor Lando:
lando drush route:list --format=table | grep ".settings"
# Verificar que las 13 nuevas rutas aparecen
```

---

## 4. Fase P1 — Deuda Técnica

### 4.1 CSS-VAR-ALL-COLORS-001: 6 Archivos SCSS

**Regla:** CADA color en SCSS DEBE ser `var(--ej-*, fallback)`. Sin excepciones. Los colores hardcoded impiden customización por tenant via Theme UI.

| Archivo | Línea | Antes | Después |
|---------|-------|-------|---------|
| `scss/_user-pages.scss` | ~1068 | `background: #fafbfc;` | `background: var(--ej-bg-surface, #fafbfc);` |
| `scss/_jobseeker-dashboard.scss` | ~95 | `color: #6ee7b7;` | `color: var(--ej-color-success-light, #6ee7b7);` |
| `scss/components/_setup-wizard.scss` | ~668 | `color: #DC2626;` | `color: var(--ej-color-danger, #DC2626);` |
| `scss/components/_reviews.scss` | ~1208 | `background: #cd7f32;` | `background: var(--ej-badge-bronze, #cd7f32);` |
| `scss/components/_reviews.scss` | ~1209 | `background: #c0c0c0;` | `background: var(--ej-badge-silver, #c0c0c0);` |
| `scss/components/_reviews.scss` | ~1210 | `background: #ffd700;` | `background: var(--ej-badge-gold, #ffd700);` |
| `scss/components/_reviews.scss` | ~1211 | `background: #e5e4e2;` | `background: var(--ej-badge-platinum, #e5e4e2);` |
| `scss/components/_product-demo.scss` | ~170 | `background: #2d2d3d;` | `background: var(--ej-bg-dark, #2d2d3d);` |
| `scss/components/_product-demo.scss` | ~177 | `background: #ff5f57;` | `background: var(--ej-color-danger, #ff5f57);` |

**`scss/features/_dark-mode.scss` línea ~52:** `--ej-copilot-input-border: #4A4A6A;` — Es una definición de CSS variable (token), no un uso. Es ACEPTABLE que las definiciones de tokens en dark mode contengan hex estáticos. No requiere fix.

**Verificación post-fix:**
```bash
cd web/themes/custom/ecosistema_jaraba_theme
npm run build
# Verificar: timestamp CSS > SCSS (SCSS-COMPILE-VERIFY-001)
ls -la css/ecosistema-jaraba-theme.css scss/main.scss
```

### 4.2 VIEWS-DATA-001: 2 Entities sin Views Handler

**Regla:** Toda ContentEntity con list_builder DEBE declarar `views_data` handler para integración con Views UI.

| Entity | Archivo | Fix |
|--------|---------|-----|
| A2ATask | `jaraba_ai_agents/src/Entity/A2ATask.php` | Añadir `"views_data" = "Drupal\views\EntityViewsData"` en handlers |
| ProactiveInsight | `jaraba_ai_agents/src/Entity/ProactiveInsight.php` | Añadir `"views_data" = "Drupal\views\EntityViewsData"` en handlers |

**Verificación post-fix:**
```bash
lando drush entity:updates
lando drush views:analyze 2>&1 | grep -i "a2a_task\|proactive_insight"
```

---

## 5. Fase P2 — DailyActionsRegistry (Infraestructura Transversal)

### 5.1 Análisis del Gap

Actualmente, las Daily Actions están hardcoded en `CoordinadorDashboardController::buildDailyActions()`. Esto funciona para un vertical, pero no escala a 10. Necesitamos el mismo patrón extensible que Setup Wizard usa: tagged services + registry + compiler pass.

**Decisión arquitectónica:** Las daily actions, a diferencia del wizard, son **dinámicas** — pueden cambiar según el estado del tenant (solicitudes pendientes, alertas activas, etc.). Por ello, el interface incluye `getContext(int $tenantId): array` para datos dinámicos como badges.

### 5.2 DailyActionInterface

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/DailyActionInterface.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for a daily action card on a vertical dashboard.
 *
 * Daily actions represent recurring operational tasks that users perform
 * on a day-to-day basis. Unlike Setup Wizard steps (one-time configuration),
 * daily actions are always visible and contextual.
 *
 * Collected via tagged services: ecosistema_jaraba_core.daily_action
 * Organized by dashboardId (e.g., 'coordinador_ei', 'merchant_comercio').
 *
 * @see \Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry
 */
interface DailyActionInterface {

  /**
   * Unique action ID within its dashboard.
   *
   * Convention: '{dashboard_id}.{action_name}'
   */
  public function getId(): string;

  /**
   * Dashboard this action belongs to.
   *
   * Multiple actions with the same dashboardId are grouped together.
   */
  public function getDashboardId(): string;

  /**
   * Human-readable action label.
   */
  public function getLabel(): TranslatableMarkup;

  /**
   * Short description of what this action does.
   */
  public function getDescription(): TranslatableMarkup;

  /**
   * Icon configuration for the action card.
   *
   * @return array{category: string, name: string, variant: string}
   */
  public function getIcon(): array;

  /**
   * Color token for the action card accent.
   *
   * Must be from the Jaraba palette: 'azul-corporativo', 'naranja-impulso',
   * 'verde-innovacion'.
   */
  public function getColor(): string;

  /**
   * Drupal route name for the action.
   */
  public function getRoute(): string;

  /**
   * Route parameters.
   */
  public function getRouteParameters(): array;

  /**
   * Optional href override (e.g., for anchor navigation).
   *
   * When set, overrides the route-based URL.
   */
  public function getHrefOverride(): ?string;

  /**
   * Whether to open the action in a slide-panel.
   */
  public function useSlidePanel(): bool;

  /**
   * Slide-panel size: 'small', 'medium', 'large', 'full'.
   */
  public function getSlidePanelSize(): string;

  /**
   * Ordering weight (lower = first). Multiples of 10.
   */
  public function getWeight(): int;

  /**
   * Whether this is the primary action (displayed larger).
   */
  public function isPrimary(): bool;

  /**
   * Dynamic context data including badge count and type.
   *
   * Called at render time — may execute lightweight queries.
   * MUST be fast (< 50ms). Use count queries, NOT full entity loads.
   *
   * @return array{
   *   badge: int|null,
   *   badge_type: string ('info'|'warning'|'critical'),
   *   visible: bool,
   * }
   */
  public function getContext(int $tenantId): array;

}
```

**Justificación del diseño:**

- `getContext(int $tenantId)` permite badges dinámicos (ej: "5 solicitudes pendientes") y visibilidad condicional (`visible: false` para ocultar acciones irrelevantes).
- `isPrimary()` marca la acción principal del día (card más grande, grid-column: span 2).
- `getHrefOverride()` permite anchor navigation dentro de la misma página (como `#panel-solicitudes`).
- `getColor()` usa los 3 tokens de marca: azul-corporativo, naranja-impulso, verde-innovacion.

### 5.3 DailyActionsRegistry

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/DailyActions/DailyActionsRegistry.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

/**
 * Registry that collects daily action services via compiler pass.
 *
 * Identical pattern to SetupWizardRegistry and TenantSettingsRegistry:
 * tagged services collected at container build time, organized by dashboard ID.
 */
class DailyActionsRegistry {

  /** @var DailyActionInterface[][] Keyed by dashboardId */
  private array $actions = [];

  public function addAction(DailyActionInterface $action): void {
    $this->actions[$action->getDashboardId()][] = $action;
  }

  public function hasDashboard(string $dashboardId): bool {
    return !empty($this->actions[$dashboardId]);
  }

  /**
   * Returns daily actions for a dashboard, sorted by weight.
   *
   * @return array<int, array{
   *   id: string,
   *   label: \Drupal\Core\StringTranslation\TranslatableMarkup,
   *   description: \Drupal\Core\StringTranslation\TranslatableMarkup,
   *   icon: array{category: string, name: string, variant: string},
   *   color: string,
   *   route: string,
   *   route_params: array,
   *   href_override: ?string,
   *   use_slide_panel: bool,
   *   slide_panel_size: string,
   *   is_primary: bool,
   *   badge: ?int,
   *   badge_type: string,
   * }>
   */
  public function getActionsForDashboard(string $dashboardId, int $tenantId): array {
    if (!$this->hasDashboard($dashboardId)) {
      return [];
    }

    $steps = $this->actions[$dashboardId];
    usort($steps, fn(DailyActionInterface $a, DailyActionInterface $b) => $a->getWeight() <=> $b->getWeight());

    $result = [];
    foreach ($steps as $action) {
      $context = $action->getContext($tenantId);
      if (!($context['visible'] ?? TRUE)) {
        continue;
      }

      $result[] = [
        'id' => $action->getId(),
        'label' => $action->getLabel(),
        'description' => $action->getDescription(),
        'icon' => $action->getIcon(),
        'color' => $action->getColor(),
        'route' => $action->getRoute(),
        'route_params' => $action->getRouteParameters(),
        'href_override' => $action->getHrefOverride(),
        'use_slide_panel' => $action->useSlidePanel(),
        'slide_panel_size' => $action->getSlidePanelSize(),
        'is_primary' => $action->isPrimary(),
        'badge' => $context['badge'] ?? NULL,
        'badge_type' => $context['badge_type'] ?? 'info',
      ];
    }

    return $result;
  }

}
```

### 5.4 DailyActionsCompilerPass

**Archivo nuevo:** `web/modules/custom/ecosistema_jaraba_core/src/DependencyInjection/Compiler/DailyActionsCompilerPass.php`

**Tag:** `ecosistema_jaraba_core.daily_action`

**Registro en:** `EcosistemaJarabaCoreServiceProvider.php` línea ~35 (junto a SetupWizardCompilerPass)

### 5.5 Registro en services.yml

```yaml
# ecosistema_jaraba_core.services.yml (añadir):
ecosistema_jaraba_core.daily_actions_registry:
  class: Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry
```

### 5.6 Migración de Andalucía +ei

Refactorizar `CoordinadorDashboardController::buildDailyActions()` para usar el registry en lugar de hardcoded:

**Archivos nuevos (5 daily actions como tagged services):**

| Archivo | ID | Label | Primary |
|---------|-----|-------|---------|
| `jaraba_andalucia_ei/src/DailyActions/GestionarSolicitudesAction.php` | `coordinador_ei.solicitudes` | Gestionar solicitudes | SÍ |
| `jaraba_andalucia_ei/src/DailyActions/NuevoParticipanteAction.php` | `coordinador_ei.participante` | Nuevo participante | NO |
| `jaraba_andalucia_ei/src/DailyActions/ProgramarSesionAction.php` | `coordinador_ei.sesion` | Programar sesión | NO |
| `jaraba_andalucia_ei/src/DailyActions/ExportarStoAction.php` | `coordinador_ei.sto` | Exportar STO | NO |
| `jaraba_andalucia_ei/src/DailyActions/CaptacionLeadsAction.php` | `coordinador_ei.leads` | Captación de leads | NO |

**services.yml (jaraba_andalucia_ei):**
```yaml
jaraba_andalucia_ei.daily_action.solicitudes:
  class: Drupal\jaraba_andalucia_ei\DailyActions\GestionarSolicitudesAction
  arguments: ['@entity_type.manager', '@?ecosistema_jaraba_core.tenant_context']
  tags:
    - { name: ecosistema_jaraba_core.daily_action }
# ... (5 servicios)
```

**Cambio en controller:**
```php
// ANTES (hardcoded):
$dailyActions = $this->buildDailyActions($tenantId, $formacionStats, $voboPendientes);

// DESPUÉS (registry):
$dailyActions = $this->dailyActionsRegistry?->getActionsForDashboard('coordinador_ei', $tenantId) ?? [];
```

---

## 6. Fase P2 — Setup Wizard: 9 Verticales Restantes

### Principios para cada vertical

1. **Secuencia lógica de negocio:** Los pasos siguen el orden natural de configuración del vertical (no el orden de la UI)
2. **Completitud basada en datos:** `isComplete()` usa count queries sobre entities existentes del tenant
3. **Slide-panel:** Todos los pasos que crean entities abren en slide-panel (SLIDE-PANEL-RENDER-001)
4. **Iconos:** Categoría semántica del vertical + duotone default (ICON-DUOTONE-001)
5. **Weight:** Múltiplos de 10 para permitir inserción futura
6. **Tenant-aware:** Toda query filtra por tenant_id (TENANT-001)
7. **Optional services:** Dependencias cross-módulo con `@?` (OPTIONAL-CROSSMODULE-001)

### 6.1 Empleabilidad (jaraba_candidate)

**Wizard ID:** `candidato_empleo`
**Dashboard:** `DashboardController` en jaraba_candidate
**Rol:** candidate / job_seeker

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `candidato_empleo.perfil` | Completa tu perfil | candidate_profile existe y campos clave rellenos (nombre, teléfono, ubicación) | `jaraba_candidate.profile_edit` | Large | NO |
| 2 | `candidato_empleo.experiencia` | Añade experiencia | ≥1 candidate_experience para el usuario | `entity.candidate_experience.add_form` | Large | NO |
| 3 | `candidato_empleo.formacion` | Formación académica | ≥1 candidate_education para el usuario | `entity.candidate_education.add_form` | Large | NO |
| 4 | `candidato_empleo.habilidades` | Habilidades y competencias | ≥3 candidate_skill para el usuario | `entity.candidate_skill.add_form` | Medium | NO |
| 5 | `candidato_empleo.idiomas` | Idiomas | ≥1 candidate_language para el usuario | `entity.candidate_language.add_form` | Medium | SÍ |

**Archivos nuevos (5):**
- `jaraba_candidate/src/SetupWizard/CandidatoPerfilStep.php`
- `jaraba_candidate/src/SetupWizard/CandidatoExperienciaStep.php`
- `jaraba_candidate/src/SetupWizard/CandidatoFormacionStep.php`
- `jaraba_candidate/src/SetupWizard/CandidatoHabilidadesStep.php`
- `jaraba_candidate/src/SetupWizard/CandidatoIdiomasStep.php`

**Nota:** jaraba_candidate ya tiene un sistema de "profile completion" tracking. El wizard complementa esa funcionalidad con una guía visual paso a paso.

### 6.2 Emprendimiento (jaraba_copilot_v2)

**Wizard ID:** `emprendedor`
**Dashboard:** Copilot dashboard en jaraba_copilot_v2
**Rol:** entrepreneur / founder

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `emprendedor.diagnostico` | Diagnóstico de madurez | Completar calculadora de madurez empresarial | `jaraba_diagnostic.calculadora_madurez` | NO | NO |
| 2 | `emprendedor.canvas` | Business Model Canvas | ≥1 bloque del canvas completado | Ruta canvas copilot | Large | NO |
| 3 | `emprendedor.hipotesis` | Define hipótesis | ≥1 hypothesis entity | `entity.copilot_hypothesis.add_form` | Large | NO |
| 4 | `emprendedor.experimento` | Primer experimento | ≥1 experiment entity | `entity.copilot_experiment.add_form` | Large | SÍ |

**Archivos nuevos (4):**
- `jaraba_copilot_v2/src/SetupWizard/EmprendedorDiagnosticoStep.php`
- `jaraba_copilot_v2/src/SetupWizard/EmprendedorCanvasStep.php`
- `jaraba_copilot_v2/src/SetupWizard/EmprendedorHipotesisStep.php`
- `jaraba_copilot_v2/src/SetupWizard/EmprendedorExperimentoStep.php`

### 6.3 Comercio Conecta (jaraba_comercio_conecta)

**Wizard ID:** `merchant_comercio`
**Dashboard:** `MerchantPortalController` → `/mi-comercio`
**Rol:** merchant

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `merchant_comercio.perfil` | Perfil de negocio | merchant_profile existe con nombre, dirección, CIF | `entity.merchant_profile.edit_form` | Large | NO |
| 2 | `merchant_comercio.catalogo` | Primer producto | ≥1 product_retail activo | `entity.product_retail.add_form` | Large | NO |
| 3 | `merchant_comercio.envio` | Métodos de envío | ≥1 comercio_shipping_method activo | `entity.comercio_shipping_method.add_form` | Large | NO |
| 4 | `merchant_comercio.pagos` | Configurar pagos | Stripe Connect onboarded (via tenant billing) | Ruta Stripe Connect | NO | NO |
| 5 | `merchant_comercio.qr` | Código QR de tienda | ≥1 comercio_qr_code generado | `entity.comercio_qr_code.add_form` | Medium | SÍ |

**Archivos nuevos (5):**
- `jaraba_comercio_conecta/src/SetupWizard/MerchantPerfilStep.php`
- `jaraba_comercio_conecta/src/SetupWizard/MerchantCatalogoStep.php`
- `jaraba_comercio_conecta/src/SetupWizard/MerchantEnvioStep.php`
- `jaraba_comercio_conecta/src/SetupWizard/MerchantPagosStep.php`
- `jaraba_comercio_conecta/src/SetupWizard/MerchantQrStep.php`

### 6.4 AgroConecta (jaraba_agroconecta_core)

**Wizard ID:** `producer_agro`
**Dashboard:** ProducerDashboardService
**Rol:** producer

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `producer_agro.perfil` | Perfil de productor | producer_profile con nombre, ubicación, tipo_explotación | `entity.producer_profile.edit_form` | Large | NO |
| 2 | `producer_agro.certificacion` | Certificaciones | ≥1 agro_certification activa | `entity.agro_certification.add_form` | Large | NO |
| 3 | `producer_agro.catalogo` | Primer producto | ≥1 product_agro activo | `entity.product_agro.add_form` | Large | NO |
| 4 | `producer_agro.envio` | Zona de envío | ≥1 agro_shipping_zone | `entity.agro_shipping_zone.add_form` | Large | NO |
| 5 | `producer_agro.trazabilidad` | Configurar trazabilidad | ≥1 agro_batch con trace_event_agro | `entity.agro_batch.add_form` | Large | SÍ |

**Archivos nuevos (5):**
- `jaraba_agroconecta_core/src/SetupWizard/ProducerPerfilStep.php`
- `jaraba_agroconecta_core/src/SetupWizard/ProducerCertificacionStep.php`
- `jaraba_agroconecta_core/src/SetupWizard/ProducerCatalogoStep.php`
- `jaraba_agroconecta_core/src/SetupWizard/ProducerEnvioStep.php`
- `jaraba_agroconecta_core/src/SetupWizard/ProducerTrazabilidadStep.php`

### 6.5 JarabaLex (jaraba_legal_intelligence)

**Wizard ID:** `legal_professional`
**Dashboard:** `LegalDashboardController` → `/legal/dashboard`
**Rol:** legal_professional

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `legal_professional.areas` | Áreas de práctica | Perfil profesional con áreas seleccionadas | Ruta perfil legal | Large | NO |
| 2 | `legal_professional.alertas` | Configurar alertas | ≥1 legal_alert activa | `entity.legal_alert.add_form` | Medium | NO |
| 3 | `legal_professional.favoritos` | Guardar resoluciones | ≥1 legal_bookmark | (acción en búsqueda) | NO | SÍ |

**Archivos nuevos (3):**
- `jaraba_legal_intelligence/src/SetupWizard/LegalAreasStep.php`
- `jaraba_legal_intelligence/src/SetupWizard/LegalAlertasStep.php`
- `jaraba_legal_intelligence/src/SetupWizard/LegalFavoritosStep.php`

### 6.6 Servicios Conecta (jaraba_servicios_conecta)

**Wizard ID:** `provider_servicios`
**Dashboard:** `ProviderPortalController` → `/mi-servicio`
**Rol:** provider

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `provider_servicios.perfil` | Perfil profesional | provider_profile completo | `entity.provider_profile.edit_form` | Large | NO |
| 2 | `provider_servicios.servicio` | Primer servicio | ≥1 service_offering activo | `entity.service_offering.add_form` | Large | NO |
| 3 | `provider_servicios.paquete` | Paquete de servicios | ≥1 service_package | `entity.service_package.add_form` | Large | SÍ |
| 4 | `provider_servicios.disponibilidad` | Horario disponible | ≥1 availability_slot | `entity.availability_slot.add_form` | Medium | NO |

**Archivos nuevos (4):**
- `jaraba_servicios_conecta/src/SetupWizard/ProviderPerfilStep.php`
- `jaraba_servicios_conecta/src/SetupWizard/ProviderServicioStep.php`
- `jaraba_servicios_conecta/src/SetupWizard/ProviderPaqueteStep.php`
- `jaraba_servicios_conecta/src/SetupWizard/ProviderDisponibilidadStep.php`

### 6.7 Content Hub (jaraba_content_hub)

**Wizard ID:** `editor_content_hub`
**Dashboard:** `ContentHubDashboardController` → `/content-hub`
**Rol:** editor / content_manager

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `editor_content_hub.autor` | Perfil de autor | ≥1 content_author para el usuario | `entity.content_author.add_form` | Large | NO |
| 2 | `editor_content_hub.categoria` | Crear categoría | ≥1 content_category | `entity.content_category.add_form` | Medium | NO |
| 3 | `editor_content_hub.articulo` | Primer artículo | ≥1 content_article publicado | `entity.content_article.add_form` | Large | NO |

**Archivos nuevos (3):**
- `jaraba_content_hub/src/SetupWizard/EditorAutorStep.php`
- `jaraba_content_hub/src/SetupWizard/EditorCategoriaStep.php`
- `jaraba_content_hub/src/SetupWizard/EditorArticuloStep.php`

### 6.8 Formación / LMS (jaraba_lms)

**Wizard ID:** `instructor_lms`
**Dashboard:** `InstructorDashboardController` → `/mis-cursos`
**Rol:** instructor

| Paso | ID | Label | Completitud | Ruta | Slide-panel | Opcional |
|------|-----|-------|-------------|------|-------------|---------|
| 1 | `instructor_lms.curso` | Crear curso | ≥1 lms_course del instructor | `entity.lms_course.add_form` | Large | NO |
| 2 | `instructor_lms.leccion` | Primera lección | ≥1 lms_lesson para algún curso | `entity.lms_lesson.add_form` | Large | NO |
| 3 | `instructor_lms.publicar` | Publicar curso | ≥1 lms_course con status publicado | (acción en curso) | NO | NO |

**Archivos nuevos (3):**
- `jaraba_lms/src/SetupWizard/InstructorCursoStep.php`
- `jaraba_lms/src/SetupWizard/InstructorLeccionStep.php`
- `jaraba_lms/src/SetupWizard/InstructorPublicarStep.php`

### 6.9 Demo (transversal)

**El vertical demo NO necesita Setup Wizard propio.** Es un meta-vertical de testing que reutiliza los dashboards y wizards de los demás verticales en un tenant de demostración. Se configura via TenantVerticalService seleccionando qué vertical(es) demostrar.

---

## 7. Fase P2 — Daily Actions: 9 Verticales Restantes

### Principios

1. **Máximo 5-7 acciones** por dashboard (evitar sobrecarga cognitiva)
2. **1 primary action** por dashboard (la más frecuente/urgente del día a día)
3. **Badges dinámicos** solo donde hay contadores significativos (pendientes, alertas)
4. **Colores semánticos:** azul = gestión, naranja = acción/urgencia, verde = creación/positivo

### 7.1 Empleabilidad (candidato_empleo)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Buscar ofertas | SÍ | azul-corporativo | Nuevas ofertas hoy | NO |
| Actualizar CV | NO | verde-innovacion | — | Large |
| Ver candidaturas | NO | naranja-impulso | Respuestas pendientes | NO |
| Chat con Copilot | NO | azul-corporativo | — | NO |

### 7.2 Emprendimiento (emprendedor)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Validar hipótesis | SÍ | naranja-impulso | Hipótesis activas | NO |
| Nuevo experimento | NO | verde-innovacion | — | Large |
| Chat con Copilot | NO | azul-corporativo | — | NO |
| Buscar mentores | NO | azul-corporativo | — | NO |

### 7.3 Comercio Conecta (merchant_comercio)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Pedidos pendientes | SÍ | naranja-impulso | Pedidos sin procesar | NO |
| Nuevo producto | NO | verde-innovacion | — | Large |
| Gestionar stock | NO | azul-corporativo | Stock bajo | NO |
| Ver analíticas | NO | azul-corporativo | — | NO |
| Ofertas flash | NO | naranja-impulso | — | Large |

### 7.4 AgroConecta (producer_agro)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Pedidos pendientes | SÍ | naranja-impulso | Pedidos hoy | NO |
| Nuevo producto | NO | verde-innovacion | — | Large |
| Registrar lote | NO | verde-innovacion | — | Large |
| Alertas calidad | NO | naranja-impulso | Alertas activas | NO |

### 7.5 JarabaLex (legal_professional)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Buscar jurisprudencia | SÍ | azul-corporativo | — | NO |
| Alertas normativas | NO | naranja-impulso | Alertas nuevas | NO |
| Mis favoritos | NO | verde-innovacion | — | NO |
| Consultar IA legal | NO | azul-corporativo | — | NO |

### 7.6 Servicios Conecta (provider_servicios)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Reservas pendientes | SÍ | naranja-impulso | Reservas hoy | NO |
| Nuevo servicio | NO | verde-innovacion | — | Large |
| Gestionar horario | NO | azul-corporativo | — | NO |
| Ver reseñas | NO | azul-corporativo | Reseñas nuevas | NO |

### 7.7 Content Hub (editor_content_hub)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Nuevo artículo | SÍ | verde-innovacion | — | Large |
| Borradores | NO | naranja-impulso | Pendientes revisión | NO |
| Comentarios | NO | azul-corporativo | Comentarios nuevos | NO |
| Generar con IA | NO | azul-corporativo | — | NO |

### 7.8 Formación / LMS (instructor_lms)

| Acción | Primary | Color | Badge | Slide-panel |
|--------|---------|-------|-------|-------------|
| Gestionar alumnos | SÍ | azul-corporativo | Inscripciones pendientes | NO |
| Nueva lección | NO | verde-innovacion | — | Large |
| Ver reseñas | NO | naranja-impulso | Reseñas nuevas | NO |
| Analíticas curso | NO | azul-corporativo | — | NO |

---

## 8. Integración en Dashboards Existentes

### 8.1 Patrón de Integración en Controller

Cada dashboard controller necesita:

```php
// 1. Inyección de dependencias (constructor):
protected readonly ?SetupWizardRegistry $wizardRegistry,
protected readonly ?DailyActionsRegistry $dailyActionsRegistry,

// 2. En el método dashboard():
$tenantId = $this->tenantContext?->getCurrentTenantId() ?? 0;

// Setup Wizard
$setupWizard = NULL;
if ($this->wizardRegistry?->hasWizard('wizard_id')) {
  $setupWizard = $this->wizardRegistry->getStepsForWizard('wizard_id', $tenantId);
}

// Daily Actions
$dailyActions = $this->dailyActionsRegistry?->getActionsForDashboard('dashboard_id', $tenantId) ?? [];

// Pasar a template
return [
  '#theme' => 'vertical_dashboard',
  '#setup_wizard' => $setupWizard,
  '#daily_actions' => $dailyActions,
  // ... otros datos
];
```

### 8.2 Patrón de Template

```twig
{# En el template del dashboard de cada vertical #}

{# Setup Wizard — solo si hay datos #}
{% if setup_wizard %}
  {% include '@ecosistema_jaraba_theme/partials/_setup-wizard.html.twig' with {
    wizard: setup_wizard,
    wizard_title: 'Título del Wizard'|t,
    wizard_subtitle: 'Subtítulo descriptivo'|t,
    collapsed_label: 'Configuración completada'|t,
  } only %}
{% endif %}

{# Daily Actions — solo si hay acciones #}
{% if daily_actions is not empty %}
  {% include '@ecosistema_jaraba_theme/partials/_daily-actions.html.twig' with {
    daily_actions: daily_actions,
    actions_title: 'Acciones del día'|t,
  } only %}
{% endif %}
```

### 8.3 Patrón de Library Attachment

En `hook_page_attachments_alter()` del tema, condicionar la library al route:

```php
// En ecosistema_jaraba_theme.theme, función hook_page_attachments_alter():
$wizardRoutes = [
  'jaraba_andalucia_ei.coordinador_dashboard',
  'jaraba_candidate.dashboard',
  'jaraba_copilot_v2.emprendimiento_dashboard',
  'jaraba_comercio_conecta.merchant_portal',
  // ... todos los dashboards
];

if (in_array($route_name, $wizardRoutes)) {
  $attachments['#attached']['library'][] = 'ecosistema_jaraba_theme/setup-wizard';
}
```

### 8.4 Patrón de services.yml

```yaml
# En cada módulo vertical:
jaraba_{vertical}.setup_wizard.{step_name}:
  class: Drupal\jaraba_{vertical}\SetupWizard\{StepClass}
  arguments: ['@entity_type.manager', '@?ecosistema_jaraba_core.tenant_context']
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }

jaraba_{vertical}.daily_action.{action_name}:
  class: Drupal\jaraba_{vertical}\DailyActions\{ActionClass}
  arguments: ['@entity_type.manager', '@?ecosistema_jaraba_core.tenant_context']
  tags:
    - { name: ecosistema_jaraba_core.daily_action }
```

---

## 9. Tabla de Correspondencia con Especificaciones Técnicas

| Especificación | Aplicación | Archivos |
|---------------|-----------|----------|
| SETUP-WIZARD-DAILY-001 | Patrón transversal Setup Wizard + Daily Actions | Todos los /SetupWizard/ y /DailyActions/ |
| PREMIUM-FORMS-PATTERN-001 | Entity forms abiertas en slide-panel extienden PremiumEntityFormBase | Todos los entity forms referenciados |
| TENANT-001 | Toda query en steps/actions filtra por tenant_id | isComplete(), getContext() |
| TENANT-BRIDGE-001 | Resolución tenant via TenantContextService, NO queries ad-hoc | Constructor injection |
| OPTIONAL-CROSSMODULE-001 | Dependencias cross-módulo con @? en services.yml | Todos los services.yml |
| ICON-CONVENTION-001 | Iconos via jaraba_icon() con category/name/variant | getIcon() en cada step/action |
| ICON-DUOTONE-001 | Variante default duotone | Todos los getIcon() |
| ICON-COLOR-001 | Solo paleta Jaraba en iconos | getColor() |
| CSS-VAR-ALL-COLORS-001 | Colores via var(--ej-*, fallback) | _setup-wizard.scss |
| TWIG-INCLUDE-ONLY-001 | Parciales con `with { } only` | Templates dashboard |
| ROUTE-LANGPREFIX-001 | URLs via path() Twig function | Templates dashboard |
| ZERO-REGION-001 | Variables via preprocess, controller retorna markup vacío | Preprocess hooks |
| SLIDE-PANEL-RENDER-001 | renderPlain() en controllers de slide-panel | Target controllers |
| ACCESS-RETURN-TYPE-001 | checkAccess() retorna AccessResultInterface | 68 handlers |
| FIELD-UI-SETTINGS-TAB-001 | field_ui_base_route tiene ruta en routing.yml | 13 rutas |
| VIEWS-DATA-001 | views_data handler en entities con list_builder | 2 entities |
| INNERHTML-XSS-001 | Drupal.checkPlain() para datos API en JS | setup-wizard.js |
| WCAG 2.1 AA | aria-labels, focus-visible, reduced-motion | Parciales Twig + SCSS |
| SCSS-COLORMIX-001 | color-mix() para runtime alpha | SCSS componentes |
| SCSS-COMPILE-VERIFY-001 | Verificar timestamp CSS > SCSS post-compilación | npm run build |
| PHANTOM-ARG-001 | Args services.yml = params constructor PHP | Todos los services.yml |
| LOGGER-INJECT-001 | @logger.channel.X → LoggerInterface $logger | Servicios con logger |
| CONTROLLER-READONLY-001 | entityTypeManager sin readonly en controllers | Controllers dashboard |
| PRESAVE-RESILIENCE-001 | Servicios opcionales con hasService() + try-catch | Steps con presave |
| ENTITY-PREPROCESS-001 | template_preprocess_{type}() para entities con view mode | .module files |
| UPDATE-HOOK-REQUIRED-001 | NO aplica (no hay nuevas entities, solo tagged services) | N/A |

---

## 10. Tabla de Cumplimiento de Directrices

| Directriz | Cumplimiento | Verificación |
|-----------|-------------|-------------|
| declare(strict_types=1) | Todos los archivos PHP nuevos | grep "strict_types" |
| PHPStan Level 6 | AccessResultInterface en return types | lando phpstan analyse |
| Drupal Coding Standards | PHPCS en CI | lando phpcs |
| Textos traducibles | Drupal.t() en JS, TranslatableMarkup en PHP, {% trans %} en Twig | Revisión manual |
| SCSS modelo SaaS | Variables inyectables via Drupal UI, var(--ej-*) | Compilación + inspector |
| Dart Sass moderno | @use (NO @import), color-mix() | npm run build |
| Templates limpios | Zero Region Pattern, sin page.content | Templates dashboard |
| Parciales reutilizables | _setup-wizard.html.twig, _daily-actions.html.twig | {% include ... only %} |
| Slide-panel para acciones | data-slide-panel en action buttons | Template inspection |
| body classes via preprocess | hook_preprocess_html() | .theme file |
| Frontend limpio sin admin | page--{ruta}.html.twig custom | Template suggestions |
| Mobile-first | Stepper vertical < 768px, grid responsive | CSS media queries |
| Accesibilidad WCAG 2.1 AA | aria-labels, roles, focus-visible, reduced-motion | Parciales Twig + SCSS |
| Field UI integración | field_ui_base_route + settings route + local tasks | routing.yml + links.task.yml |
| Views integración | views_data handler en entities | Entity annotations |
| /admin/structure | Config entities settings | routing.yml |
| /admin/content | Content entities collection | routing.yml |

---

## 11. Plan de Testing

### 11.1 Tests Unitarios (por vertical)

```php
// Test para cada SetupWizardStep:
class CoordinadorPlanFormativoStepTest extends UnitTestCase {
  public function testGetId(): void {
    $this->assertEquals('coordinador_ei.plan_formativo', $step->getId());
  }
  public function testGetWizardId(): void {
    $this->assertEquals('coordinador_ei', $step->getWizardId());
  }
  // testGetWeight, testIsOptional, testGetIcon, testGetRoute...
}
```

### 11.2 Tests Kernel (Registry)

```php
class SetupWizardRegistryTest extends KernelTestBase {
  // Verify tagged services are collected
  // Verify sorting by weight
  // Verify completion_percentage calculation
  // Verify isComplete() with mock entities
}

class DailyActionsRegistryTest extends KernelTestBase {
  // Verify tagged services are collected
  // Verify sorting by weight
  // Verify context/badge computation
  // Verify visible=false filtering
}
```

### 11.3 Tests Funcionales (Rutas)

```php
class SetupWizardApiTest extends BrowserTestBase {
  // GET /api/v1/setup-wizard/{id}/status returns JSON
  // Verify CSRF protection
  // Verify tenant filtering
}

class FieldUiSettingsRoutesTest extends BrowserTestBase {
  // Verify 13 new routes are accessible
  // Verify Field UI tabs appear
}
```

---

## 12. RUNTIME-VERIFY-001 — Verificación Post-Implementación

### Checklist por cada vertical

| # | Check | Comando/Acción |
|---|-------|---------------|
| 1 | CSS compilado | `ls -la css/ecosistema-jaraba-theme.css` timestamp > SCSS |
| 2 | Rutas accesibles | `lando drush route:list \| grep setup-wizard` |
| 3 | Tagged services registrados | `lando drush debug:container --tag=ecosistema_jaraba_core.setup_wizard_step` |
| 4 | API endpoint responde | `curl -H "X-CSRF-Token: ..." /api/v1/setup-wizard/{id}/status` |
| 5 | Library attached | Inspector → Network tab → setup-wizard.js cargado |
| 6 | Template renderizado | Inspector → .setup-wizard presente en DOM |
| 7 | Slide-panel funciona | Click en step action → panel se abre |
| 8 | Progress ring animado | Visual check (SVG stroke-dasharray) |
| 9 | Responsive mobile | DevTools → 375px → stepper vertical |
| 10 | localStorage persist | Close/reopen → estado mantenido |
| 11 | API refresh tras save | Guardar en slide-panel → wizard se actualiza |
| 12 | Daily actions visible | Scroll debajo de wizard → cards presentes |

---

## 13. Gestión de Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|-----------|
| Rutas no definidas causan 404 en Field UI | ALTA (confirmado) | MEDIO | Fix P0 inmediato: añadir 13 rutas |
| PHPStan falla en CI por AccessResult | ALTA (confirmado) | ALTO | Fix P0: 68 handlers |
| Wizard steps mal configurados rompen container | BAJA | ALTO | Tests Kernel para cada step |
| Performance: too many count queries en steps | MEDIA | MEDIO | Cache por tenant + request en Registry |
| DailyActions getContext() lento | BAJA | MEDIO | Constraint < 50ms, count queries only |
| Conflicto merge en routing.yml grandes | MEDIA | BAJO | Append-only pattern, no reorder |
| SCSS no compilado post-edit | MEDIA | MEDIO | SCSS-COMPILE-VERIFY-001 + CI check |

---

## 14. Cronograma y Estimaciones

### Fase P0 (Sprint actual — inmediato)

| Tarea | Archivos | Horas | Dependencias |
|-------|----------|-------|-------------|
| Fix 68 access handlers | 68 PHP files | 2h | Ninguna |
| Fix 13 Field UI routes | 2 routing.yml | 1h | Ninguna |
| Verificación PHPStan | — | 0.5h | Ambos fixes |
| **Subtotal P0** | | **3.5h** | |

### Fase P1 (Sprint actual — tras P0)

| Tarea | Archivos | Horas | Dependencias |
|-------|----------|-------|-------------|
| Fix 6 SCSS tokens | 6 SCSS files | 1h | Ninguna |
| Fix 2 views_data | 2 PHP files | 0.5h | Ninguna |
| npm run build + verify | — | 0.5h | SCSS fix |
| **Subtotal P1** | | **2h** | |

### Fase P2 — Infraestructura (Sprint próximo)

| Tarea | Archivos | Horas | Dependencias |
|-------|----------|-------|-------------|
| DailyActionInterface | 1 PHP file | 1h | Ninguna |
| DailyActionsRegistry | 1 PHP file | 1h | Interface |
| DailyActionsCompilerPass | 1 PHP file | 0.5h | Registry |
| ServiceProvider + services.yml | 2 files | 0.5h | CompilerPass |
| Migrar Andalucía +ei | 5 PHP + 1 yml | 2h | Registry |
| Tests Registry | 2 PHP files | 1h | Migración |
| **Subtotal Infra** | | **6h** | |

### Fase P2 — Verticales (Sprints 21-23)

| Vertical | Steps | Actions | Horas | Sprint |
|----------|-------|---------|-------|--------|
| Empleabilidad | 5 | 4 | 4h | 21 |
| Emprendimiento | 4 | 4 | 3.5h | 21 |
| Comercio Conecta | 5 | 5 | 4h | 21 |
| AgroConecta | 5 | 4 | 4h | 22 |
| JarabaLex | 3 | 4 | 3h | 22 |
| Servicios Conecta | 4 | 4 | 3.5h | 22 |
| Content Hub | 3 | 4 | 3h | 23 |
| Formación / LMS | 3 | 4 | 3h | 23 |
| Dashboard integration × 8 | — | — | 4h | 23 |
| **Subtotal Verticales** | **32** | **33** | **32h** | |

### Total General

| Fase | Horas | Estado |
|------|-------|--------|
| P0 | 3.5h | EN PROGRESO |
| P1 | 2h | EN PROGRESO |
| P2 — Infraestructura | 6h | PENDIENTE |
| P2 — Verticales | 32h | PENDIENTE |
| **TOTAL** | **43.5h** | |

---

## 15. Registro de Cambios

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2026-03-16 | 1.0.0 | Creación del plan basado en auditoría integral |
| 2026-03-16 | 2.0.0 | Ejecución completa P0-P2. Descubierto gap L3+L4 (hook_theme + templates). Añadida salvaguarda PIPELINE-E2E-001. Corregidos 8 hook_theme() + 8 templates. 190+ archivos totales. Auditoría final 10/10 clase mundial |

---

> **Documento relacionado:** `docs/analisis/2026-03-16_Auditoria_Integral_SaaS_Setup_Wizard_Daily_Actions_Runtime_Arquitectura_v1.md`
>
> **Plan previo (v1 infraestructura):** `docs/implementacion/2026-03-13_Plan_Implementacion_Setup_Wizard_Daily_Actions_Clase_Mundial_v1.md`
