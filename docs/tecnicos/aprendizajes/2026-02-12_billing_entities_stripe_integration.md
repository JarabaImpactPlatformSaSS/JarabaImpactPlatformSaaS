# Aprendizaje #60: Billing Entities + Stripe Integration

**Fecha:** 2026-02-12
**Contexto:** Implementacion de 3 entidades billing, 3 servicios Stripe, webhook controller dedicado, plantilla Zero Region para eventos, y fix de consent-banner library

---

## Que se hizo

### Fase 1 — Correcciones rapidas
- Creada `page--eventos.html.twig` Zero Region template (patron identico a `page--crm.html.twig`)
- Fix `consent-banner` library: cambiado `ecosistema_jaraba_theme/global` (inexistente) por `ecosistema_jaraba_theme/global-styling` (real)

### Fase 2 — 3 Content Entities para jaraba_billing
- **BillingInvoice**: Facturas sincronizadas desde Stripe. Campos monetarios Decimal(10,4), currency ISO 4217. Helpers `isPaid()`, `isOverdue()`
- **BillingUsageRecord**: Registros de uso medido. Patron **append-only** (sin EntityChangedInterface, sin form edit/delete, access handler FORBID update/delete)
- **BillingPaymentMethod**: Cache local de metodos de pago Stripe. Helpers `isDefault()`, `isExpired()` (chequea exp_month/exp_year)
- 5 forms, 3 access handlers, 3 list builders, permissions.yml, routing.yml, links (menu/task/action), install

### Fase 3 — Integracion Stripe real
- **StripeCustomerService**: CRUD clientes Stripe + `syncPaymentMethods()` que crea/actualiza entidades locales
- **StripeSubscriptionService**: create/cancel/update/pause/resume subscriptions. Orquesta `TenantSubscriptionService` (local) + API Stripe (remota)
- **StripeInvoiceService**: `syncInvoice()` crea/actualiza BillingInvoice local (amounts /100 desde centavos). `reportUsage()`, `listInvoices()`, `voidInvoice()`
- **BillingWebhookController**: Endpoint dedicado `/api/billing/stripe-webhook`. 8 eventos via match(). HMAC-SHA256 via `StripeConnectService::verifyWebhookSignature()`

### Fase 4 — 8 ficheros de tests
- 3 entity tests (isPaid, isOverdue, isDefault, isExpired, append-only verification)
- 3 service tests (mocks de StripeConnectService, EntityTypeManager)
- 1 webhook controller test (signature validation, event dispatch)
- 1 consent controller test (status/grant/revoke endpoints)

---

## Lecciones aprendidas

### 1. Patron append-only para entidades financieras
Las entidades de registro financiero (BillingUsageRecord) deben ser **inmutables** una vez creadas. Se implementa:
- NO implementar `EntityChangedInterface`
- Solo form handler `add` (sin `edit`, sin `delete`)
- Access handler retorna `AccessResult::forbidden()` para operaciones `update` y `delete`
- Patron ya usado en `FinancialTransaction` de jaraba_foc

### 2. StripeConnectService como unico transporte HTTP
Los 3 nuevos servicios Stripe (Customer, Subscription, Invoice) **nunca** llaman a la API Stripe directamente. Todos usan `StripeConnectService::stripeRequest(string $method, string $endpoint, array $data)` de `jaraba_foc` como transporte HTTP unificado. Ventajas:
- Un unico punto de configuracion de API key
- Logging centralizado
- Verificacion de webhook reutilizable (`verifyWebhookSignature()`)

### 3. Webhook controllers separados por dominio
Se creo `BillingWebhookController` separado del `StripeWebhookController` de jaraba_foc. Cada uno maneja eventos distintos:
- **jaraba_foc**: `account.updated`, `payout.paid`, `charge.succeeded`, `charge.refunded` (operaciones financieras FOC)
- **jaraba_billing**: `invoice.*`, `customer.subscription.*`, `payment_method.*` (ciclo de vida billing)
- Ambos comparten el mismo mecanismo HMAC pero con webhook secrets independientes

### 4. Libreria CSS inexistente en dependencia
El `consent-banner` de jaraba_analytics referenciaba `ecosistema_jaraba_theme/global` como dependencia, pero esa libreria no existe en el tema. Solo existe `global-styling` (linea 1 de `ecosistema_jaraba_theme.libraries.yml`). El CSS se cargaba igualmente porque el tema global siempre se adjunta, pero Drupal emitiria warnings en contextos donde la libreria se use explicitamente. **Regla**: siempre verificar que las dependencias de librerias apuntan a nombres reales.

---

## Reglas derivadas

### BILLING-001: Entidades financieras append-only
Las entidades que registran transacciones, uso o eventos financieros DEBEN ser inmutables. Sin `EntityChangedInterface`, sin form edit/delete, access handler FORBID update/delete.

### BILLING-002: Stripe API siempre via StripeConnectService
Nunca instanciar clientes HTTP propios para Stripe. Siempre usar `jaraba_foc.stripe_connect` como transporte (`stripeRequest()`). Esto centraliza API key, logging, y error handling.

### BILLING-003: Webhook controllers separados por dominio funcional
Cada modulo que reciba webhooks de Stripe debe tener su propio controller con su propio webhook secret. Esto permite configuracion independiente y separacion de responsabilidades.

### BILLING-004: Verificar dependencias de librerias contra nombres reales
Antes de declarar `dependencies` en `*.libraries.yml`, verificar que el nombre de la libreria objetivo existe literalmente en el `.libraries.yml` del tema o modulo referenciado.

---

## Ficheros creados/modificados

### Nuevos (34 ficheros)
- `web/themes/custom/ecosistema_jaraba_theme/templates/page--eventos.html.twig`
- `web/modules/custom/jaraba_billing/src/Entity/BillingInvoice.php`
- `web/modules/custom/jaraba_billing/src/Entity/BillingUsageRecord.php`
- `web/modules/custom/jaraba_billing/src/Entity/BillingPaymentMethod.php`
- `web/modules/custom/jaraba_billing/src/Form/BillingInvoice{Form,SettingsForm}.php`
- `web/modules/custom/jaraba_billing/src/Form/BillingUsageRecordSettingsForm.php`
- `web/modules/custom/jaraba_billing/src/Form/BillingPaymentMethod{Form,SettingsForm}.php`
- `web/modules/custom/jaraba_billing/src/Access/Billing{Invoice,UsageRecord,PaymentMethod}AccessControlHandler.php`
- `web/modules/custom/jaraba_billing/src/ListBuilder/Billing{Invoice,UsageRecord,PaymentMethod}ListBuilder.php`
- `web/modules/custom/jaraba_billing/src/Service/Stripe{Customer,Subscription,Invoice}Service.php`
- `web/modules/custom/jaraba_billing/src/Controller/BillingWebhookController.php`
- `web/modules/custom/jaraba_billing/jaraba_billing.{permissions,routing,links.menu,links.task,links.action,install}.yml`
- 8 ficheros de tests en `tests/src/Unit/`

### Modificados (3 ficheros)
- `web/modules/custom/jaraba_analytics/jaraba_analytics.libraries.yml` — Fix consent-banner dependency
- `web/modules/custom/jaraba_billing/jaraba_billing.services.yml` — +3 servicios Stripe
- `web/modules/custom/jaraba_billing/jaraba_billing.info.yml` — +drupal:views dependency

---

## Patrones reutilizados

| Patron | Fichero de referencia | Aplicado en |
|--------|-----------------------|-------------|
| ContentEntity con tenant_id | `jaraba_events/src/Entity/MarketingEvent.php` | BillingInvoice, BillingPaymentMethod |
| Append-only entity | `jaraba_foc/src/Entity/FinancialTransaction.php` | BillingUsageRecord |
| Webhook HMAC | `jaraba_foc/src/Controller/StripeWebhookController.php` | BillingWebhookController |
| Zero Region template | `page--crm.html.twig` | page--eventos.html.twig |
| Access handler tenant check | `jaraba_events/src/Access/MarketingEventAccessControlHandler.php` | 3 access handlers |
