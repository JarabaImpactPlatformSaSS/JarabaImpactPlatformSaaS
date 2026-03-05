# Auditoria Completa de Gaps — Billing, Commerce y Fiscal
# Jaraba Impact Platform SaaS
# Fecha: 2026-03-05 | Version: 1.0.0
# Autor: Auditoria automatizada Claude Code
# Estado: PENDIENTE IMPLEMENTACION

---

## Metodologia

Auditadas **4 dimensiones** con investigacion de codigo real (no solo config):
- **Complitud**: Existen todos los componentes necesarios?
- **Integridad**: Los datos fluyen correctamente end-to-end?
- **Consistencia**: Los sistemas paralelos son coherentes entre si?
- **Coherencia**: La arquitectura refleja la logica de negocio?

Principio rector: **RUNTIME-VERIFY-001** — verificar la diferencia entre "el codigo existe" y "el usuario lo experimenta".

---

## SEVERIDAD CRITICA — Bloquean produccion

### GAP-C01: Fiscal Delegation No Conectada al Ciclo de Facturacion

**El codigo existe**: `FiscalInvoiceDelegationService` con logica real de delegacion a VeriFactu, Facturae B2G y E-Factura B2B. Los 3 modulos fiscales estan implementados con servicios reales (incluido SOAP FACe).

**El usuario NO lo experimenta**: Nadie llama a `processFinalizedInvoice()`. Cuando llega el webhook `invoice.paid`, `BillingWebhookController` sincroniza la factura localmente pero **nunca invoca la delegacion fiscal**.

```
FLUJO ACTUAL:    webhook invoice.paid -> syncInvoice() -> [STOP]
FLUJO CORRECTO:  webhook invoice.paid -> syncInvoice() -> processFinalizedInvoice()
                                                          |-> VeriFactu (OBLIGATORIO)
                                                          |-> Facturae B2G (si AAPP)
                                                          +-> E-Factura B2B (si empresa)
```

**Impacto**: Incumplimiento RD 1007/2023 — toda factura pagada DEBE registrarse en VeriFactu. Riesgo legal y sancionador.

**Ficheros**: `jaraba_billing/src/Controller/BillingWebhookController.php:134-142`, `jaraba_billing/src/Service/FiscalInvoiceDelegationService.php`

---

### GAP-C02: Dunning Sin Cron — Proceso Nunca Avanza

**El codigo existe**: `DunningService` con 6 pasos completos (dia 0->3->7->10->14->21), emails, restricciones progresivas.

**El usuario NO lo experimenta**: No hay `hook_cron()` en `jaraba_billing.module` que invoque `processDunning()`. El dunning se **inicia** via webhook pero **nunca progresa** por los pasos.

```
ACTUAL:  Dia 0: webhook -> startDunning() -> [STOP, nunca mas progresa]
DEBERIA: Cron diario -> processDunning() -> escala pasos dia 3, 7, 10, 14, 21
```

**Impacto**: Tenants morosos mantienen acceso indefinidamente. No se suspenden ni cancelan.

**Fichero**: `jaraba_billing/jaraba_billing.module` (31 lineas, sin hook_cron)

---

### GAP-C03: Suscripciones Locales Sin Stripe — Perdida de Ingresos

Hay **5 flujos** donde entidades locales se crean/modifican pero Stripe NO se actualiza:

| Flujo | Fichero | Local | Stripe |
|-------|---------|-------|--------|
| Cambio de plan | `TenantSubscriptionService::changePlan()` L237 | Actualiza `subscription_plan` | No llama a `StripeSubscriptionService` |
| Suscripcion addon | `AddonSubscriptionService::subscribe()` L104 | Crea `AddonSubscription` | Nada — `stripe_subscription_item_id` nunca se puebla |
| Activacion vertical | `VerticalAddonBillingService::activateVerticalAddon()` L61 | Crea `AddonSubscription` | Comentado: "(Futuro)" |
| Cancelacion plan | `TenantSubscriptionService::cancelSubscription()` L148 | Marca local `cancelled` | No cancela en Stripe |
| Cancelacion addon | `AddonSubscriptionService::cancel()` L187 | Marca local `cancelled` | No elimina Subscription Item |

**Impacto**: Usuarios cambian plan/addons localmente pero se les cobra segun el estado anterior en Stripe. O peor: cancelan pero siguen siendo cobrados.

---

### GAP-C04: Usage Metering Stubbed — Facturacion por Uso Inoperante

**El codigo existe**: `UsageStripeSyncService::syncUsageToStripe()` con 93 lineas de codigo.

**El usuario NO lo experimenta**: El metodo solo hace logging e incrementa un contador. **No hay ninguna llamada real a la API de Stripe**:

```php
// Lineas 59-77 — logging sin API call
$this->logger->info('Sincronizando uso con Stripe: @metric = @qty...');
$syncedCount++; // Incrementa pero no envia nada
```

**Impacto**: Todo el uso (API calls, storage, AI tokens, emails) es gratuito. Los tenants consumen sin limite real de cobro.

---

## SEVERIDAD ALTA — Afectan integridad y coherencia

### GAP-H01: Sistema Dual de Addons — TenantAddon vs AddonSubscription

Existen **dos entidades paralelas** que representan el mismo concepto ("tenant tiene addon activo"):

| Aspecto | TenantAddon (jaraba_billing) | AddonSubscription (jaraba_addons) |
|---------|------------------------------|-----------------------------------|
| Referencia addon | String hardcoded (`addon_code`) | Entity reference a `Addon` |
| Tipos soportados | Solo features (9 codigos fijos) | Features, storage, API, support, custom, **vertical** |
| Ref. tenant | **Group** entity | **Tenant** entity |
| Stripe | `stripe_subscription_item_id` | No integracion |
| Creado por | `BillingApiController` | `AddonApiController` |

**Feature access check inconsistente**:
```
FeatureAccessService::canAccess()
  PASO 2: Consulta TenantAddon (addon_code) -> features regulares
  PASO 3: Consulta AddonSubscription -> SOLO verticales

  Si un feature addon se crea via AddonSubscription -> FeatureAccessService NO lo detecta
  Si un vertical se crea via TenantAddon -> TenantVerticalService NO lo detecta
```

**Referencia a entidad diferente**: TenantAddon referencia `Group`, AddonSubscription referencia `Tenant`. Viola TENANT-BRIDGE-001.

**Impacto**: Addon activado en un sistema es invisible para el otro. Feature gates pueden bloquear/permitir incorrectamente.

---

### GAP-H02: PlanValidator Duplicado

Existen **dos clases PlanValidator** en modulos diferentes:
- `ecosistema_jaraba_core/src/Service/PlanValidator.php`
- `jaraba_billing/src/Service/PlanValidator.php`

Ambas inyectadas como servicios distintos. Consumidores pueden usar la incorrecta.

**Impacto**: Comportamiento inconsistente entre modulos que usan una u otra version.

---

### GAP-H03: TenantMeteringService Duplicado

Dos implementaciones:
- `ecosistema_jaraba_core/src/Service/TenantMeteringService.php`
- `jaraba_billing/src/Service/TenantMeteringService.php`

**Impacto**: Metering registrado en un servicio puede no ser visible para el otro.

---

### GAP-H04: FOC Desconectado del Comercio

**FinancialTransaction** (ledger inmutable) existe como arquitectura de compliance. Pero:
- Cuando un `order_retail` se completa en ComercioConecta -> **NO se crea FinancialTransaction**
- `CheckoutService::processCheckout()` no invoca FOC
- `RevenueMetricsService` calcula MRR desde `billing_invoice`, no desde `financial_transaction`
- `PayoutRecord` (comercio) no se vincula a FOC

**Impacto**: El ledger financiero esta vacio para transacciones de marketplace. Las metricas SaaS no reflejan ingresos de comisiones.

---

### GAP-H05: Endpoints IA Sin Gate de Features ni Tokens

| Endpoint | Feature Gate | Token Limit |
|----------|-------------|-------------|
| Copilot Stream | SI `AIUsageLimitService` | SI |
| SEO Suggestions (`/api/v1/page-builder/seo-ai-suggest`) | NO Solo permiso | NO |
| AI Template Generator | NO Solo permiso | NO |
| AI Image Suggestions | NO Solo permiso | NO |

**Impacto**: Usuarios en plan Starter acceden a funciones IA premium sin restriccion. Consume tokens LLM sin control de coste.

---

### GAP-H06: Credenciales Sin Gate de Plan

`CredentialStackAccessControlHandler` usa solo permisos Drupal. No consulta `FeatureAccessService` ni `PlanValidator::enforceCredentialStackLimit()`.

**Impacto**: Usuarios con permiso `manage credential stacks` crean stacks ilimitados sin verificar plan/addon.

---

### GAP-H07: Webhook FOC Sin Eventos de Marketplace

StripeWebhookController (FOC) no maneja:
- `account.application.authorized` — vendor conecta cuenta Stripe
- `account.application.deauthorized` — vendor revoca acceso
- `customer.subscription.paused` — suscripcion pausada

**Impacto**: El FOC no tiene visibilidad sobre el ciclo de vida de vendors en marketplace.

---

### GAP-H08: Upgrade Triggers Sub-utilizados

`UpgradeTriggerService` tiene 39 tipos definidos pero solo se invoca desde:
- 10 vertical FeatureGateServices
- PlanValidator (2 puntos)
- NO desde AI controllers, Page Builder premium, Credentials

**Impacto**: Usuarios que alcanzan limites en AI/PageBuilder/Credentials no reciben prompt de upgrade.

---

## SEVERIDAD MEDIA — Degradan calidad

### GAP-M01: IVA/Tax Ausente en Pricing Pages

Los precios se muestran sin:
- Indicacion de IVA incluido/excluido
- Calculo de IVA (21% Espana)
- Validacion de NIF/CIF del comprador
- Texto legal LSSI-CE

**Impacto**: No conformidad con normativa espanola de comercio electronico.

---

### GAP-M02: Comision Marketplace Hardcoded al 10%

`CheckoutService::processCheckout()` linea 132:
```php
$commission_rate = 10.0;  // HARDCODED
```

No configurable por tenant, vertical ni plan. Sin ConfigEntity ni UI admin.

**Impacto**: Inflexibilidad comercial. Imposible ofrecer tarifas diferenciadas.

---

### GAP-M03: Wallet Schema On-Demand

Las tablas `billing_tenant_wallet` y `billing_wallet_ledger` se crean via `ensureTablesExist()` en runtime, no en `hook_schema()` ni `hook_update_N()`.

**Impacto**: Si la DB se restaura desde backup o se pre-crea, las tablas podrian no existir.

---

### GAP-M04: Webhook Sin Deduplicacion de Eventos

No existe tabla `stripe_webhook_event_log` para prevenir procesamiento duplicado. Stripe puede reintentar webhooks.

**Impacto**: Emails duplicados de dunning, notificaciones dobles.

---

### GAP-M05: Customer Stripe No Auto-Creado en Onboarding

`StripeCustomerService::createOrGetCustomer()` existe pero solo se invoca desde `BillingApiController::createSubscription()`, no durante la creacion del tenant.

**Impacto**: Si el tenant navega al dashboard antes de suscribirse, no tiene Stripe customer. Race condition si intenta pagar despues.

---

### GAP-M06: 3 Verticales Sin Pricing Page Publica

`andalucia_ei`, `jaraba_content_hub` y `demo` no estan en la regex de la ruta `/planes/{vertical_key}`.

**Impacto**: Tenants de estos verticales no pueden ver/comparar planes publicamente.

---

### GAP-M07: Webhooks FOC Incompletos para Marketplace

(Detallado en GAP-H07 — webhooks Stripe de marketplace no manejados por FOC.)

---

### GAP-M08: Upgrade Triggers Sub-utilizados

(Detallado en GAP-H08 — 39 tipos definidos pero solo ~12 consumidores activos.)

---

## MATRIZ CONSOLIDADA — "El Codigo Existe" vs "El Usuario Lo Experimenta"

| Componente | Codigo | Runtime | Gap |
|------------|--------|---------|-----|
| VeriFactu records | Implementado | Nunca invocado | C01 CRITICO |
| Facturae B2G (SOAP FACe real) | Implementado | Nunca invocado | C01 CRITICO |
| E-Factura B2B | Implementado (SPFE stub) | Nunca invocado | C01 CRITICO |
| Dunning 6 pasos | Implementado | Solo paso 0 se ejecuta | C02 CRITICO |
| Addon billing Stripe | Campo existe | Siempre NULL | C03 CRITICO |
| Usage metering -> Stripe | Metodo existe | Solo logging | C04 CRITICO |
| FOC ledger financiero | Entity inmutable | Sin datos de commerce | H04 ALTO |
| Wallet criptografico | Hash SHA-256 chain | Sin consumidores reales | H04 ALTO |
| AI token gating | En Copilot | No en SEO/Template/Image | H05 ALTO |
| Revenue dashboard | MRR/ARR calculados | Desde billing_invoice, no FOC | H04 ALTO |
| Synthetic CFO | Clase existe | Stubbed, sin AI real | MEDIO |
| Proration preview | Llama Stripe API | Preview-only, no se aplica | MEDIO |
| Commission configurable | Hardcoded 10% | No configurable | M02 MEDIO |
| IVA/Tax en pricing | No existe | No existe | M01 MEDIO |

---

## PLAN DE ACCION PRIORIZADO

### Sprint P0 — Bloquean produccion (1-2 semanas)

| # | Accion | Fichero(s) | Esfuerzo |
|---|--------|------------|----------|
| 1 | Conectar `invoice.paid` -> `FiscalInvoiceDelegationService` via `hook_entity_update` | `jaraba_billing.module` | 2h |
| 2 | Anadir `hook_cron()` -> `DunningService::processDunning()` | `jaraba_billing.module` | 1h |
| 3 | Conectar `TenantSubscriptionService::changePlan()` -> `StripeSubscriptionService` | `TenantSubscriptionService.php` | 4h |
| 4 | Conectar `cancelSubscription()` -> `StripeSubscriptionService::cancelSubscription()` | `TenantSubscriptionService.php` | 2h |
| 5 | Implementar sync real en `UsageStripeSyncService::syncUsageToStripe()` | `UsageStripeSyncService.php` | 4h |

### Sprint P1 — Integridad (2-3 semanas)

| # | Accion | Fichero(s) | Esfuerzo |
|---|--------|------------|----------|
| 6 | Consolidar TenantAddon -> AddonSubscription (migration + retire) | Multiples | 16h |
| 7 | Unificar PlanValidator (eliminar duplicado) | 2 modulos | 4h |
| 8 | Unificar TenantMeteringService (eliminar duplicado) | 2 modulos | 4h |
| 9 | Conectar commerce -> FOC (crear FinancialTransaction en checkout webhook) | `jaraba_comercio_conecta` | 8h |
| 10 | Gate AI endpoints (SEO, Template, Image) con FeatureAccessService + AIUsageLimitService | 3 controllers | 6h |
| 11 | Gate Credentials con PlanValidator::enforceCredentialStackLimit() | AccessControlHandler | 2h |
| 12 | Crear Stripe Subscription Items para addons en `subscribe()` | `AddonSubscriptionService.php` | 8h |

### Sprint P2 — Coherencia (3-4 semanas)

| # | Accion | Fichero(s) | Esfuerzo |
|---|--------|------------|----------|
| 13 | IVA/Tax en pricing pages + texto legal LSSI-CE | Templates + Controller | 8h |
| 14 | Comision marketplace configurable (ConfigEntity + UI) | `CheckoutService`, nueva entity | 8h |
| 15 | Webhook deduplication table | `jaraba_billing` | 4h |
| 16 | Auto-crear Stripe Customer en tenant onboarding | `jaraba_commerce.module` hook | 2h |
| 17 | Wallet schema en hook_schema() | `jaraba_billing.install` | 2h |
| 18 | Pricing pages para andalucia_ei y jaraba_content_hub | Routing + config | 4h |
| 19 | Upgrade triggers en AI controllers y Credentials | 5 controllers | 4h |
| 20 | Revenue metrics desde FOC (no billing_invoice) | `RevenueMetricsService` | 8h |

---

## Esfuerzo Total Estimado

| Sprint | Esfuerzo | Items |
|--------|----------|-------|
| P0 Critico | ~13h | 5 |
| P1 Alto | ~48h | 7 |
| P2 Medio | ~40h | 8 |
| **Total** | **~101h** | **20** |

---

## Referencias

- CLAUDE.md: RUNTIME-VERIFY-001, TENANT-BRIDGE-001, STRIPE-ENV-UNIFY-001
- Verticales Componibles: `docs/implementacion/2026-03-04_tenant_settings_hub_branding_system_v1.md`
- Gap Analysis previo: `docs/analisis/2026-03-03_Gaps_Clase_Mundial_Analisis_Completo_v1.md`
- Modulos auditados: jaraba_billing, jaraba_addons, jaraba_foc, jaraba_commerce, jaraba_comercio_conecta, jaraba_social_commerce, ecosistema_jaraba_core
