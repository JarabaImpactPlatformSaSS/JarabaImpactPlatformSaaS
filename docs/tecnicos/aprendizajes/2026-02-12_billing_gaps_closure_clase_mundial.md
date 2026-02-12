# Aprendizaje #62: Billing Clase Mundial — Cierre 15 Gaps

**Fecha:** 2026-02-12
**Contexto:** Auditoria cruzada de 3 especificaciones tecnicas maestras (134_Stripe_Billing_Integration, 111_UsageBased_Pricing, 158_Vertical_Pricing_Matrix) contra la implementacion existente de `jaraba_billing`. Se identificaron 15 gaps (G1-G15) que elevaban la cobertura del ~35-40% al 100% (Clase Mundial).

---

## Que se hizo

### Fase 1 — Campos nuevos en entidades existentes (G1-G2)
- **BillingInvoice +6 campos**: `stripe_customer_id`, `subtotal`, `tax`, `total` (Decimal 10,4), `billing_reason` (list_string: subscription_cycle, subscription_create, subscription_update, manual, upcoming), `lines` (string_long JSON)
- **BillingUsageRecord +5 campos**: `subscription_item_id` (string 64), `reported_at` (timestamp), `idempotency_key` (string 128), `billed` (boolean), `billing_period` (string 7, formato YYYY-MM)
- hook_update_N (10001) en `jaraba_billing.install` para aplicar schema sin perdida de datos

### Fase 2 — 2 entidades nuevas (G3-G4)
- **BillingCustomer** (G3): Mapeo tenant↔Stripe customer. Campos: tenant_id (UNIQUE), stripe_customer_id (UNIQUE), stripe_connect_id, billing_email, billing_name, tax_id, tax_id_type (es_cif, eu_vat), billing_address (JSON), default_payment_method, invoice_settings, metadata. Con Form, SettingsForm, AccessControlHandler, ListBuilder
- **TenantAddon** (G4): Add-ons activos por suscripcion. Campos: tenant_id, addon_code (list_string: 9 opciones), stripe_subscription_item_id, price (Decimal 10,2), status (active, canceled, pending), activated_at, canceled_at. Con Form, SettingsForm, AccessControlHandler, ListBuilder

### Fase 3 — Servicios nuevos y correcciones (G5-G8, G12-G13)
- **DunningService** (G7): Secuencia 6 pasos de cobro (spec 134 §6). Tabla custom `billing_dunning_state` via hook_schema(). Metodos: startDunning(), processDunning(), stopDunning(), isInDunning(), getDunningStatus(), executeStep(). Secuencia: email_soft(0d) → email_reminder(3d)+banner → email_urgent(7d)+premium_disabled → email_final_warning(10d)+readonly → email_suspension(14d)+suspended → cancel(21d)+canceled
- **FeatureAccessService** (G8): Verificacion plan+addons en tiempo real (spec 158 §6.1). Metodos: canAccess(), getActiveAddons(), hasActiveAddon(), getAddonForFeature(), getAvailableAddons(). Constante FEATURE_ADDON_MAP con 18 mappings feature→addon_code
- **BillingWebhookController** (G6): handleSubscriptionUpdated ya implementa sincronizacion de estado (active→activateSubscription, past_due→markPastDue+startDunning, canceled→cancelSubscription). handleTrialWillEnd envia email de aviso y marca banner in-app
- **StripeInvoiceService.syncInvoice** (G5): Ahora puebla campos fiscales (subtotal, tax, total divididos /100, billing_reason, lines JSON)
- **StripeCustomerService** (G5): Ahora sincroniza con BillingCustomer local via `syncBillingCustomer()`
- **PlanValidator** (G12): Soporte add-ons activos. Query directa a TenantAddon (evita dependencia circular con FeatureAccessService)
- **StripeInvoiceService.flushUsageToStripe()** (G13): Carga registros con reported_at NULL, agrupa por subscription_item_id, reporta y marca reported_at

### Fase 4 — 3 API Controllers REST (G9-G11)
- **BillingApiController** (G9): 13 endpoints — subscription CRUD, invoices, payment-methods, portal-session, customer update. Patron ControllerBase + ContainerInjectionInterface + JsonResponse
- **UsageBillingApiController** (G10): 7 endpoints — record, current, history, breakdown, forecast, my-plan, estimate
- **AddonApiController** (G11): 6 endpoints — subscription with addons, available addons, activate, cancel, upgrade, upcoming invoice

### Fase 5 — Tests (G15) y fixes PHP 8.4
- 88 tests, 304 assertions, 0 errors, 0 failures
- 11 test fixes para PHP 8.4 dynamic property deprecation
- Permisos ampliados (G14): manage billing customers, manage tenant addons, view billing dunning, view billing usage

---

## Lecciones aprendidas

### 1. PHP 8.4 rompe propiedades dinamicas en mocks PHPUnit
PHP 8.4 depreca propiedades dinamicas en objetos. Los mocks de `FieldItemListInterface` con `$mock->value = 'paid'` ya no funcionan — `->value` retorna NULL. **Solucion**: usar `(object) ['value' => $val]` (stdClass) en lugar de mocks para acceso a campos de entidades Drupal en tests.

**Antes (PHP 8.3 OK, PHP 8.4 FALLA):**
```php
$field = $this->createMock(FieldItemListInterface::class);
$field->value = 'paid'; // Propiedad dinamica — NULL en PHP 8.4
$entity->method('get')->willReturn($field);
```

**Despues (PHP 8.4 compatible):**
```php
$field = (object) ['value' => 'paid']; // stdClass permite propiedades
$entity->method('get')->willReturn($field);
```

### 2. TenantInterface vs EntityInterface en mocks de tests
Los servicios de billing que aceptan `TenantInterface` en sus firmas (PlanValidator::hasFeature, TenantSubscriptionService::activateSubscription) rechazan mocks de `EntityInterface` con TypeError. **Solucion**: siempre mockear `\Drupal\ecosistema_jaraba_core\Entity\TenantInterface` cuando el servicio bajo test lo requiera.

### 3. Evitar dependencia circular en servicios billing
`FeatureAccessService` depende de `PlanValidator`. Si `PlanValidator` dependiese de `FeatureAccessService` para comprobar add-ons, se crearia un ciclo. **Solucion**: `PlanValidator` hace la consulta de add-ons activos directamente via `EntityTypeManager::getStorage('tenant_addon')` sin inyectar `FeatureAccessService`.

### 4. DunningService usa tabla custom en lugar de entidad
La tabla `billing_dunning_state` se crea via `hook_schema()` en lugar de ser una Content Entity porque:
- Es un registro de estado efimero (se borra al resolver)
- Solo necesita 4 columnas simples (tenant_id, started_at, current_step, last_action_at)
- No necesita Field UI, Views, ni REST
- Operaciones directas con `\Drupal\Core\Database\Connection` son mas eficientes para consultas batch en cron

### 5. StripeCustomerService debe sincronizar entidad local
Despues de crear/encontrar un customer en Stripe API, siempre sincronizar con la entidad BillingCustomer local. Esto permite consultas sin llamada API (getByTenantId) y mantiene datos fiscales (tax_id, billing_address) disponibles localmente para generacion de facturas.

### 6. Webhook handlers deben tener logica real
Los handlers `handleSubscriptionUpdated` y `handleTrialWillEnd` originalmente eran no-ops (solo log). En produccion esto significa:
- Cambios de estado de Stripe (past_due, canceled) no se reflejan localmente
- Fin de trial no genera notificacion al usuario
- El dunning no se inicia automaticamente
Regla: todo handler de webhook registrado DEBE implementar logica funcional o no registrarse.

---

## Reglas derivadas

### BILLING-005: DunningService con tabla custom via hook_schema()
El estado de dunning se almacena en tabla custom `billing_dunning_state` (no Content Entity). hook_schema() define la tabla. Se accede via Database\Connection directamente. Se borra al resolver el pago.

### BILLING-006: Evitar dependencia circular en billing services
PlanValidator NO debe inyectar FeatureAccessService. Para verificar add-ons activos, PlanValidator accede directamente a `EntityTypeManager::getStorage('tenant_addon')`. FeatureAccessService puede inyectar PlanValidator sin problemas.

### BILLING-007: PHP 8.4 — usar stdClass para field mocks
En tests unitarios que necesiten mockear `$entity->get('field')->value`, NUNCA usar `$this->createMock(FieldItemListInterface::class)` con propiedad dinamica `->value`. Siempre usar `(object) ['value' => $val]` que funciona en PHP 8.3 y 8.4.

### BILLING-008: Mockear TenantInterface, no EntityInterface
Cuando un servicio bajo test acepta `TenantInterface` en su firma, el mock DEBE ser `$this->createMock(TenantInterface::class)`. Usar `EntityInterface::class` causa TypeError en PHP 8.4 con strict types.

---

## Ficheros creados/modificados

### Nuevos (~25 ficheros)
- `src/Entity/BillingCustomer.php` + Form, SettingsForm, AccessControlHandler, ListBuilder
- `src/Entity/TenantAddon.php` + Form, SettingsForm, AccessControlHandler, ListBuilder
- `src/Service/DunningService.php`
- `src/Service/FeatureAccessService.php`
- `src/Controller/BillingApiController.php`
- `src/Controller/UsageBillingApiController.php`
- `src/Controller/AddonApiController.php`
- `tests/src/Unit/Entity/BillingCustomerTest.php`
- `tests/src/Unit/Entity/TenantAddonTest.php`
- `tests/src/Unit/Service/DunningServiceTest.php`
- `tests/src/Unit/Service/FeatureAccessServiceTest.php`
- `tests/src/Unit/Controller/BillingApiControllerTest.php`
- `tests/src/Unit/Controller/UsageBillingApiControllerTest.php`
- `tests/src/Unit/Controller/AddonApiControllerTest.php`

### Modificados (~10 ficheros)
- `src/Entity/BillingInvoice.php` — +6 campos fiscales (baseFieldDefinitions)
- `src/Entity/BillingUsageRecord.php` — +5 campos sync Stripe
- `src/Controller/BillingWebhookController.php` — handleSubscriptionUpdated + handleTrialWillEnd implementados
- `src/Service/StripeInvoiceService.php` — syncInvoice +campos fiscales, +flushUsageToStripe()
- `src/Service/StripeCustomerService.php` — +syncBillingCustomer(), +getByTenantId()
- `src/Service/PlanValidator.php` — +soporte add-ons activos
- `jaraba_billing.services.yml` — +2 servicios (dunning, feature_access)
- `jaraba_billing.routing.yml` — +26 rutas API REST
- `jaraba_billing.permissions.yml` — +4 permisos
- `jaraba_billing.install` — +hook_update_10001, +hook_schema (billing_dunning_state)
- `jaraba_billing.links.menu.yml`, `links.task.yml`, `links.action.yml` — +BillingCustomer, +TenantAddon

### Tests corregidos (5 ficheros)
- `tests/src/Unit/Entity/BillingInvoiceTest.php` — stdClass field mocks
- `tests/src/Unit/Entity/BillingPaymentMethodTest.php` — stdClass field mocks
- `tests/src/Unit/Service/FeatureAccessServiceTest.php` — TenantInterface + stdClass
- `tests/src/Unit/Service/DunningServiceTest.php` — TenantInterface + assertion
- `tests/src/Unit/Service/StripeCustomerServiceTest.php` — billing_customer storage mock

---

## Patrones reutilizados

| Patron | Fichero de referencia | Aplicado en |
|--------|-----------------------|-------------|
| ContentEntity con tenant_id | `BillingInvoice.php` | BillingCustomer, TenantAddon |
| hook_schema() tabla custom | `jaraba_customer_success.install` | billing_dunning_state |
| REST API JsonResponse | `UsageApiController.php` (core) | 3 API controllers |
| Webhook handler match() | `BillingWebhookController.php` | handleSubscriptionUpdated, handleTrialWillEnd |
| Feature↔Addon mapping | spec 158 §5.3 | FeatureAccessService::FEATURE_ADDON_MAP |
| Cron batch processing | `TenantMeteringService` | DunningService::processDunning() |

---

## Metricas

| Metrica | Antes | Despues |
|---------|-------|---------|
| Cobertura specs billing | ~35-40% | ~100% |
| Entidades billing | 3 | 5 |
| Servicios billing | 11 | 13 |
| API endpoints REST | 0 | 26 |
| Controllers | 1 (webhook) | 4 (webhook + 3 API) |
| Tests | 77 | 88 |
| Assertions | ~250 | 304 |
| Permisos billing | 5 | 9 |
| Campos BillingInvoice | ~8 | 14 |
| Campos BillingUsageRecord | ~6 | 11 |
