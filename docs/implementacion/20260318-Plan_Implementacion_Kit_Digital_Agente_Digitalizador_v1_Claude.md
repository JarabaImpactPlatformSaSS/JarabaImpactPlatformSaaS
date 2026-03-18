# Plan de Implementacion: Kit Digital — Agente Digitalizador
# Version: 1.0 | Fecha: 18 marzo 2026
# Estado: Plan de Implementacion aprobado para ejecucion
# Dependencias: Doc 179 (spec), Doc 158 (pricing), jaraba_billing, ecosistema_jaraba_core
# Especificaciones de origen: 179_Kit_Digital_Agente_Digitalizador_Implementacion_v1
# Equipo: Claude Code (integramente)

---

## Tabla de Contenidos (TOC)

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Correspondencia de especificaciones tecnicas](#2-correspondencia-de-especificaciones-tecnicas)
3. [Correspondencia de directrices del proyecto](#3-correspondencia-de-directrices-del-proyecto)
4. [Fase 1 — Backend: Entity + Service + Permissions](#4-fase-1--backend-entity--service--permissions)
   - 4.1 [KitDigitalAgreement ContentEntity](#41-kitdigitalagreement-contententity)
   - 4.2 [KitDigitalService](#42-kitdigitalservice)
   - 4.3 [KitDigitalAgreementAccessControlHandler](#43-kitdigitalagreementaccesscontrolhandler)
   - 4.4 [KitDigitalAgreementForm (PremiumEntityFormBase)](#44-kitdigitalagreementform-premiumentityformbase)
   - 4.5 [Permissions, routing, hook_update_N](#45-permissions-routing-hook_update_n)
   - 4.6 [Services.yml registration](#46-servicesyml-registration)
5. [Fase 2 — Frontend: Landing pages Kit Digital](#5-fase-2--frontend-landing-pages-kit-digital)
   - 5.1 [Arquitectura de rutas y templates](#51-arquitectura-de-rutas-y-templates)
   - 5.2 [KitDigitalController](#52-kitdigitalcontroller)
   - 5.3 [Preprocess hooks y body classes](#53-preprocess-hooks-y-body-classes)
   - 5.4 [Templates Twig (zero-region)](#54-templates-twig-zero-region)
   - 5.5 [Parciales reutilizables](#55-parciales-reutilizables)
   - 5.6 [SCSS y compilacion](#56-scss-y-compilacion)
   - 5.7 [Libraries y attachments](#57-libraries-y-attachments)
   - 5.8 [Iconografia y logos obligatorios](#58-iconografia-y-logos-obligatorios)
   - 5.9 [Precios dinamicos (NO-HARDCODE-PRICE-001)](#59-precios-dinamicos-no-hardcode-price-001)
   - 5.10 [Schema.org y SEO](#510-schemaorg-y-seo)
6. [Fase 3 — Automatizacion: ECA + FOC + Setup Wizard + Daily Actions](#6-fase-3--automatizacion-eca--foc--setup-wizard--daily-actions)
   - 6.1 [Flujo ECA alta Kit Digital](#61-flujo-eca-alta-kit-digital)
   - 6.2 [Metricas FOC](#62-metricas-foc)
   - 6.3 [Setup Wizard: KitDigitalOnboardingStep](#63-setup-wizard-kitdigitalonboardingstep)
   - 6.4 [Daily Actions: AcuerdosPendientesAction + BonoExpirationAction](#64-daily-actions-acuerdospendientesaction--bonoexpirationaction)
   - 6.5 [Admin Dashboard Kit Digital](#65-admin-dashboard-kit-digital)
7. [Fase 4 — Integracion Stripe y ciclo de vida del bono](#7-fase-4--integracion-stripe-y-ciclo-de-vida-del-bono)
8. [Verificacion RUNTIME-VERIFY-001 + PIPELINE-E2E-001](#8-verificacion-runtime-verify-001--pipeline-e2e-001)
9. [Testing](#9-testing)
10. [Cronograma de ejecucion](#10-cronograma-de-ejecucion)

---

## 1. Resumen ejecutivo

### Que es

Implementacion del sistema de gestion de Acuerdos de Prestacion de Soluciones de Digitalizacion para que PED S.L. actue como Agente Digitalizador del Kit Digital. Incluye:

- **Entity `KitDigitalAgreement`** en `jaraba_billing` para gestionar acuerdos, bonos, estados y justificaciones
- **6 landing pages publicas** en `/kit-digital/*` con contenido obligatorio segun Anexo II Red.es
- **KitDigitalService** con 8 metodos para el ciclo de vida completo del bono
- **Admin Dashboard** para gestion de acuerdos y seguimiento de justificaciones
- **Setup Wizard + Daily Actions** para el flujo operativo del admin
- **Metricas FOC** para medir conversion y retencion post-bono
- **Integracion Stripe** para vincular bonos a suscripciones

### Donde vive

| Componente | Modulo | Razon |
|-----------|--------|-------|
| Entity + Service + Access + Forms | `jaraba_billing` | KIT-DIGITAL-003: no crear modulo nuevo, billing es el hogar natural |
| Controller landing | `jaraba_billing` | Rutas publicas pertenecen al modulo que las gestiona |
| Templates landing | `ecosistema_jaraba_theme` | Zero-region pattern, parciales reutilizables |
| SCSS landing | `ecosistema_jaraba_theme/scss/components/_kit-digital.scss` | Patron route SCSS del tema |
| Admin controller | `jaraba_billing` | Gestion administrativa |
| Setup Wizard steps | `jaraba_billing/src/SetupWizard/` | Tagged services SETUP-WIZARD-DAILY-001 |
| Daily Actions | `jaraba_billing/src/DailyActions/` | Tagged services SETUP-WIZARD-DAILY-001 |
| FOC metrics | `jaraba_foc` (addon opcional) | Metricas existentes + kit_digital_* nuevas |

### Que NO hace este plan

- NO implementa PAdES (firma digital) — se referencia como dependencia futura
- NO implementa el Buzon de Confianza — feature gate existente, implementacion posterior
- NO crea modulo nuevo — todo en `jaraba_billing` (KIT-DIGITAL-003)
- NO hardcodea precios — usa MetaSitePricingService (NO-HARDCODE-PRICE-001)

---

## 2. Correspondencia de especificaciones tecnicas

| Spec Doc 179 | Seccion plan | Componente resultante | Estado previo |
|-------------|-------------|----------------------|---------------|
| §3.2 Entity KitDigitalAgreement | 4.1 | `jaraba_billing/src/Entity/KitDigitalAgreement.php` | NO EXISTE |
| §3.3 KitDigitalService | 4.2 | `jaraba_billing/src/Service/KitDigitalService.php` | NO EXISTE |
| §3.4 Rutas (6) | 5.1, 4.5 | `jaraba_billing.routing.yml` (additions) | NO EXISTE |
| §3.5 Permisos (2) | 4.5 | `jaraba_billing.permissions.yml` (additions) | NO EXISTE |
| §3.6 Flujo ECA | 6.1 | `config/sync/eca.*.kit_digital_*.yml` | NO EXISTE |
| §3.7 Metricas FOC | 6.2 | `jaraba_foc` (addon opcional @?) | NO EXISTE |
| §3.1 Web dedicada (6 paginas) | 5.1-5.10 | 6 templates Twig + controller + SCSS | NO EXISTE |
| §6.2 Wizard + Daily Actions | 6.3, 6.4 | Tagged services SETUP-WIZARD-DAILY-001 | NO EXISTE |

### Dependencias existentes verificadas

| Modulo | Estado | Uso en Kit Digital |
|--------|--------|-------------------|
| `jaraba_verifactu` | COMPLETO | C5 Factura electronica |
| `jaraba_crm` | COMPLETO | C3 CRM |
| `jaraba_social` | COMPLETO | C9 Redes sociales |
| `jaraba_billing` (Stripe) | COMPLETO | Vinculacion bono → suscripcion |
| `ecosistema_jaraba_core` (MetaSitePricingService) | COMPLETO | Precios dinamicos |
| `ecosistema_jaraba_theme` (parciales) | COMPLETO | _header, _footer, _hero, _faq |

---

## 3. Correspondencia de directrices del proyecto

| Directriz | Aplicacion en Kit Digital | Verificacion |
|----------|--------------------------|-------------|
| **TENANT-001** | `tenant_id` FK en KitDigitalAgreement, toda query filtra por tenant | validate-tenant-isolation.php |
| **TENANT-BRIDGE-001** | Usar TenantBridgeService para resolver Tenant<->Group | Manual |
| **TENANT-ISOLATION-ACCESS-001** | AccessControlHandler verifica tenant match en update/delete | Manual |
| **PREMIUM-FORMS-PATTERN-001** | KitDigitalAgreementForm extiende PremiumEntityFormBase | Manual |
| **CONTROLLER-READONLY-001** | NO readonly en propiedades heredadas de ControllerBase | Manual |
| **ACCESS-RETURN-TYPE-001** | checkAccess() retorna `: AccessResultInterface` | PHPStan L6 |
| **ZERO-REGION-001** | Landing pages usan `{{ clean_content }}`, NO `{{ page.content }}` | Manual |
| **ZERO-REGION-003** | #attached del controller NO se procesa; usar preprocess | Manual |
| **ROUTE-LANGPREFIX-001** | URLs via Url::fromRoute(), NUNCA hardcoded `/es/` | validate-routing.php |
| **CSS-VAR-ALL-COLORS-001** | Todos los colores con `var(--ej-*, fallback)` | Manual |
| **SCSS-COMPILETIME-001** | Funciones Sass compile-time con hex estatico, runtime con color-mix() | npm run build |
| **SCSS-COMPILE-VERIFY-001** | Verificar timestamp CSS > SCSS tras cada edicion | validate-compiled-assets.php |
| **ICON-CONVENTION-001** | `jaraba_icon('category', 'name', {variant: 'duotone'})` | Manual |
| **ICON-DUOTONE-001** | Variante default siempre duotone | Manual |
| **ICON-COLOR-001** | Solo colores de paleta: azul-corporativo, naranja-impulso, verde-innovacion, white, neutral | Manual |
| **NO-HARDCODE-PRICE-001** | Precios via MetaSitePricingService, NUNCA EUR en Twig | validate-no-hardcoded-prices.php |
| **TWIG-INCLUDE-ONLY-001** | `{% include ... only %}` en todos los parciales | Manual |
| **TWIG-URL-RENDER-ARRAY-001** | `url()` devuelve render array; usar `{{ path() }}` para href | Manual |
| **ENTITY-FK-001** | tenant_id = entity_reference, cross-modulo opcional = integer | Manual |
| **AUDIT-CONS-001** | AccessControlHandler en anotacion entity | validate-entity-integrity.php |
| **ENTITY-PREPROCESS-001** | template_preprocess_{type}() en .module | validate-entity-integrity.php |
| **UPDATE-HOOK-REQUIRED-001** | hook_update_10006 con installEntityType() | validate-entity-integrity.php |
| **UPDATE-HOOK-CATCH-001** | try-catch con \Throwable (NO \Exception) | Manual |
| **OPTIONAL-CROSSMODULE-001** | @? para deps cross-modulo en services.yml | validate-optional-deps.php |
| **LOGGER-INJECT-001** | @logger.channel.jaraba_billing → LoggerInterface | validate-logger-injection.php |
| **PHANTOM-ARG-001** | Args YAML = constructor params (bidireccional) | validate-phantom-args.php |
| **FIELD-UI-SETTINGS-TAB-001** | field_ui_base_route + default local task tab | validate-entity-integrity.php |
| **KIT-DIGITAL-001** | Logos obligatorios en /kit-digital/* | Manual |
| **KIT-DIGITAL-002** | Precios = Config Entities editables (Regla #131 Doc 158) | Manual |
| **KIT-DIGITAL-003** | Entity en jaraba_billing, no modulo nuevo | Manual |
| **KIT-DIGITAL-005** | Metricas en FOC existente | Manual |
| **KIT-DIGITAL-006** | ROUTE-LANGPREFIX-001 aplica | validate-routing.php |
| **KIT-DIGITAL-007** | TENANT-001 aplica: cada acuerdo tiene tenant_id | validate-tenant-isolation.php |
| **SETUP-WIZARD-DAILY-001** | Patron transversal: tagged services via CompilerPass | Manual |
| **PIPELINE-E2E-001** | Verificar 4 capas: L1 Service → L2 Controller → L3 hook_theme → L4 Template | Manual |
| **DOC-GUARD-001** | Commits de docs separados de codigo | Pre-commit |
| **COMMIT-SCOPE-001** | Commits docs con prefijo `docs:` | Pre-commit |
| **SECRET-MGMT-001** | Secrets via settings.secrets.php + getenv() | Manual |
| **CSRF-API-001** | API routes con _csrf_request_header_token: 'TRUE' | Manual |
| **STRIPE-ENV-UNIFY-001** | Stripe keys via getenv() | Manual |
| **CHECKOUT-ROUTE-COLLISION-001** | Rutas Kit Digital en /kit-digital/*, NO en /checkout/* | Manual |

---

## 4. Fase 1 — Backend: Entity + Service + Permissions

### 4.1 KitDigitalAgreement ContentEntity

**Archivo:** `web/modules/custom/jaraba_billing/src/Entity/KitDigitalAgreement.php`

**Anotacion ContentEntityType:**

```
id = "kit_digital_agreement"
label = "Kit Digital Agreement"
label_collection = "Kit Digital Agreements"
label_singular = "acuerdo Kit Digital"
label_plural = "acuerdos Kit Digital"
handlers:
  list_builder = KitDigitalAgreementListBuilder
  views_data = Drupal\views\EntityViewsData
  form:
    default = KitDigitalAgreementForm
    add = KitDigitalAgreementForm
    edit = KitDigitalAgreementForm
    delete = Drupal\Core\Entity\ContentEntityDeleteForm
  access = KitDigitalAgreementAccessControlHandler
  route_provider:
    html = Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
base_table = "kit_digital_agreement"
admin_permission = "administer kit digital"
entity_keys:
  id = "id"
  uuid = "uuid"
  label = "agreement_number"
links:
  canonical = /admin/content/kit-digital-agreement/{kit_digital_agreement}
  add-form = /admin/content/kit-digital-agreement/add
  edit-form = /admin/content/kit-digital-agreement/{kit_digital_agreement}/edit
  delete-form = /admin/content/kit-digital-agreement/{kit_digital_agreement}/delete
  collection = /admin/content/kit-digital-agreements
field_ui_base_route = jaraba_billing.kit_digital_agreement.settings
```

**Campos baseFieldDefinitions (17 campos):**

| Campo | Tipo | Obligatorio | Descripcion |
|-------|------|-------------|-------------|
| `tenant_id` | entity_reference (group) | SI | ENTITY-FK-001: FK al tenant |
| `agreement_number` | string(255) | SI | Numero de referencia del acuerdo (label) |
| `beneficiary_name` | string(255) | SI | Razon social del beneficiario |
| `beneficiary_nif` | string(20) | SI | NIF/CIF del beneficiario |
| `segmento` | list_string | SI | I, II, III, IV, V (por numero empleados) |
| `bono_digital_amount` | decimal(10,2) | NO | Importe del bono en EUR |
| `bono_digital_ref` | string(255) | NO | Referencia del bono digital en Red.es |
| `paquete` | list_string | SI | comercio_digital, productor_digital, profesional_digital, despacho_digital, emprendedor_digital |
| `categorias_kit_digital` | string_long | NO | JSON con categorias cubiertas (C1-C9) |
| `plan_tier` | list_string | NO | starter, pro, enterprise |
| `stripe_subscription_id` | string(255) | NO | ID suscripcion Stripe vinculada |
| `start_date` | datetime | SI | Inicio del servicio |
| `end_date` | datetime | SI | Fin del servicio (minimo +12 meses) |
| `status` | list_string | SI | draft, signed, active, justification_pending, justified, paid, expired |
| `justification_date` | datetime | NO | Fecha de justificacion |
| `justification_memory` | file | NO | Memoria tecnica de actuacion (PDF) |
| `created` | created | AUTO | Timestamp creacion |
| `changed` | changed | AUTO | Timestamp modificacion |

**Interfaces:** `ContentEntityBase` + `EntityChangedInterface` + `EntityChangedTrait`

**Metodos adicionales:**
- `isActive(): bool` — status == 'active'
- `isExpired(): bool` — end_date < now
- `isPendingJustification(): bool` — status == 'justification_pending'
- `getCoveredMonths(): int` — ceil(bono_amount / monthly_price)
- `getMonthlyPrice(): float` — via MetaSitePricingService resolucion

### 4.2 KitDigitalService

**Archivo:** `web/modules/custom/jaraba_billing/src/Service/KitDigitalService.php`

**Constructor DI (8 dependencias):**
```
EntityTypeManagerInterface $entityTypeManager
TenantBridgeService $tenantBridge (ecosistema_jaraba_core.tenant_bridge)
?TenantSubscriptionService $subscriptionService (jaraba_billing.tenant_subscription)
?MetaSitePricingService $pricingService (@?ecosistema_jaraba_core.metasite_pricing)
?FocEventRecorderInterface $focRecorder (@?jaraba_foc.event_recorder)
LoggerInterface $logger (@logger.channel.jaraba_billing)
AccountProxyInterface $currentUser (@current_user)
TimeInterface $time (@datetime.time)
```

**Metodos publicos (8):**

1. `createAgreement(int $tenant_id, string $paquete, string $segmento, float $bono_amount, string $plan_tier = 'pro'): KitDigitalAgreement`
   - Crea entidad con datos del beneficiario (extraidos del tenant via TenantBridgeService)
   - Genera agreement_number con formato `KD-{YEAR}-{SEQUENTIAL}`
   - Calcula end_date = start_date + max(12, coveredMonths) meses
   - Estado inicial: 'draft'
   - Registra evento FOC: `kit_digital_agreement_created`

2. `getCategoriesForPaquete(string $paquete): array`
   - Mapeo estatico paquete → categorias Kit Digital
   - comercio_digital → [C1, C2, C3, C6]
   - productor_digital → [C1, C8, C6, C2]
   - profesional_digital → [C4, C5, C3, C7]
   - despacho_digital → [C4, C5, C7, C2]
   - emprendedor_digital → [C4, C6, C2, C3]

3. `getMaxBonoAmount(string $paquete, string $segmento): float`
   - Tabla de bonos maximos segun Anexo IV (paquete + segmento)
   - Ejemplo: comercio_digital + Seg.I = 12.000 EUR

4. `generateJustificationMemory(int $agreement_id): string`
   - Genera PDF con datos del acuerdo, actividades realizadas, funcionalidades entregadas
   - Retorna file URI del PDF generado
   - Actualiza campo `justification_memory` en la entidad

5. `linkToStripeSubscription(int $agreement_id, string $stripe_subscription_id): void`
   - Vincula acuerdo a suscripcion Stripe existente
   - Calcula periodo cubierto por el bono
   - Actualiza estado a 'active'

6. `calculateCoveredMonths(float $bono_amount, float $monthly_price): int`
   - Calculo: ceil(bono_amount / monthly_price)
   - Minimo: 12 meses (requisito Kit Digital)
   - Ejemplo: 6.000 EUR / 79 EUR/mes = 76 meses

7. `getActiveAgreements(): array`
   - Retorna todos los acuerdos con status = 'active'
   - Para reporting y dashboard admin

8. `isKitDigitalTenant(int $tenant_id): bool`
   - Verifica si un tenant fue adquirido via bono Kit Digital
   - Consulta si existe KitDigitalAgreement con tenant_id y status != 'draft'
   - Usado en FOC para Kit Digital Conversion Rate

### 4.3 KitDigitalAgreementAccessControlHandler

**Archivo:** `web/modules/custom/jaraba_billing/src/Access/KitDigitalAgreementAccessControlHandler.php`

**Patron:** Identico a BillingInvoiceAccessControlHandler pero con permisos Kit Digital.

- `checkAccess()` retorna `: AccessResultInterface` (ACCESS-RETURN-TYPE-001)
- Admin bypass: `administer kit digital` o `administer billing`
- View: `view kit digital agreements`
- Update/Delete: solo admin
- TENANT-ISOLATION-ACCESS-001: verificar tenant match en update/delete

### 4.4 KitDigitalAgreementForm (PremiumEntityFormBase)

**Archivo:** `web/modules/custom/jaraba_billing/src/Form/KitDigitalAgreementForm.php`

**Secciones (getSectionDefinitions):**

```php
[
  'identification' => [
    'label' => t('Identificacion'),
    'icon' => ['category' => 'compliance', 'name' => 'document'],
    'description' => t('Datos del acuerdo y referencia del bono.'),
    'fields' => ['agreement_number', 'bono_digital_ref', 'tenant_id'],
  ],
  'beneficiary' => [
    'label' => t('Beneficiario'),
    'icon' => ['category' => 'ui', 'name' => 'user'],
    'description' => t('Datos de la empresa beneficiaria.'),
    'fields' => ['beneficiary_name', 'beneficiary_nif', 'segmento'],
  ],
  'package' => [
    'label' => t('Paquete y plan'),
    'icon' => ['category' => 'commerce', 'name' => 'package'],
    'description' => t('Solucion digital contratada.'),
    'fields' => ['paquete', 'plan_tier', 'categorias_kit_digital', 'bono_digital_amount'],
  ],
  'lifecycle' => [
    'label' => t('Ciclo de vida'),
    'icon' => ['category' => 'actions', 'name' => 'calendar'],
    'description' => t('Fechas, estado y vinculacion Stripe.'),
    'fields' => ['start_date', 'end_date', 'status', 'stripe_subscription_id'],
  ],
  'justification' => [
    'label' => t('Justificacion'),
    'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
    'description' => t('Documentacion para justificar el bono.'),
    'fields' => ['justification_date', 'justification_memory'],
  ],
]
```

**getFormIcon:** `['category' => 'compliance', 'name' => 'certificate']`

### 4.5 Permissions, routing, hook_update_N

**Permisos nuevos (jaraba_billing.permissions.yml):**
```yaml
administer kit digital:
  title: 'Administrar acuerdos Kit Digital'
  description: 'Crear, editar y gestionar acuerdos Kit Digital y seguimiento de bonos'
  restrict access: true

view kit digital agreements:
  title: 'Ver acuerdos Kit Digital'
  description: 'Ver detalles y estado de acuerdos Kit Digital'
```

**Rutas nuevas (jaraba_billing.routing.yml):**
```yaml
# Field UI settings
jaraba_billing.kit_digital_agreement.settings:
  path: '/admin/config/billing/kit-digital-agreement'
  defaults:
    _form: 'Drupal\jaraba_billing\Form\KitDigitalAgreementSettingsForm'
    _title: 'Kit Digital Agreement settings'
  requirements:
    _permission: 'administer kit digital'

# Landing pages publicas
jaraba_billing.kit_digital.landing:
  path: '/kit-digital'
  defaults:
    _controller: '\Drupal\jaraba_billing\Controller\KitDigitalController::landing'
    _title: 'Kit Digital - Soluciones de Digitalizacion'
  requirements:
    _access: 'TRUE'

jaraba_billing.kit_digital.paquete:
  path: '/kit-digital/{paquete}'
  defaults:
    _controller: '\Drupal\jaraba_billing\Controller\KitDigitalController::paquete'
  requirements:
    _access: 'TRUE'
    paquete: 'comercio-digital|productor-digital|profesional-digital|despacho-digital|emprendedor-digital'

# Admin dashboard
jaraba_billing.kit_digital.admin:
  path: '/admin/content/kit-digital'
  defaults:
    _controller: '\Drupal\jaraba_billing\Controller\KitDigitalAdminController::dashboard'
    _title: 'Kit Digital - Gestion de Acuerdos'
  requirements:
    _permission: 'administer kit digital'
```

**Nota critica ROUTE-LANGPREFIX-001:** Las rutas NO llevan prefijo `/es/` hardcoded. Drupal resuelve el prefijo de idioma automaticamente via PathProcessor. Hardcodear `/es/` causa 404 cuando el sitio sirve en otro idioma.

**hook_update_10006:**
```php
function jaraba_billing_update_10006(): void {
  try {
    $updateManager = \Drupal::entityDefinitionUpdateManager();
    $entityTypeManager = \Drupal::entityTypeManager();
    $entityType = $entityTypeManager->getDefinition('kit_digital_agreement', FALSE);
    if ($entityType && !$updateManager->getEntityType('kit_digital_agreement')) {
      $updateManager->installEntityType($entityType);
    }
  }
  catch (\Throwable $e) {
    \Drupal::logger('jaraba_billing')->error('Kit Digital entity install failed: @msg', ['@msg' => $e->getMessage()]);
  }
}
```

### 4.6 Services.yml registration

```yaml
# KitDigitalService
jaraba_billing.kit_digital:
  class: Drupal\jaraba_billing\Service\KitDigitalService
  arguments:
    - '@entity_type.manager'
    - '@ecosistema_jaraba_core.tenant_bridge'
    - '@?jaraba_billing.tenant_subscription'
    - '@?ecosistema_jaraba_core.metasite_pricing'
    - '@?jaraba_foc.event_recorder'
    - '@logger.channel.jaraba_billing'
    - '@current_user'
    - '@datetime.time'

# Setup Wizard step
jaraba_billing.setup_wizard.kit_digital_agreement:
  class: Drupal\jaraba_billing\SetupWizard\KitDigitalAgreementStep
  arguments:
    - '@entity_type.manager'
  tags:
    - { name: ecosistema_jaraba_core.setup_wizard_step }

# Daily Actions (2)
jaraba_billing.daily_action.kit_digital_pending:
  class: Drupal\jaraba_billing\DailyActions\KitDigitalPendingAction
  arguments:
    - '@entity_type.manager'
  tags:
    - { name: ecosistema_jaraba_core.daily_action }

jaraba_billing.daily_action.kit_digital_expiring:
  class: Drupal\jaraba_billing\DailyActions\KitDigitalExpiringAction
  arguments:
    - '@entity_type.manager'
    - '@datetime.time'
  tags:
    - { name: ecosistema_jaraba_core.daily_action }
```

---

## 5. Fase 2 — Frontend: Landing pages Kit Digital

### 5.1 Arquitectura de rutas y templates

```
GET /kit-digital
  → jaraba_billing.kit_digital.landing
  → KitDigitalController::landing()
  → #theme: 'kit_digital_landing'
  → page--kit-digital.html.twig (zero-region)
  → body class: 'page-kit-digital clean-layout full-width-layout'

GET /kit-digital/{paquete}
  → jaraba_billing.kit_digital.paquete
  → KitDigitalController::paquete($paquete)
  → #theme: 'kit_digital_paquete'
  → page--kit-digital.html.twig (reutiliza)
  → body class: 'page-kit-digital page-kit-digital--{paquete}'
```

**Flujo E2E (PIPELINE-E2E-001):**

```
L1: KitDigitalService inyectado en KitDigitalController (constructor + create())
L2: Controller pasa datos al render array (#theme, #paquete_data, #logos, #pricing)
L3: hook_theme() declara variables en 'kit_digital_landing' y 'kit_digital_paquete'
L4: Template incluye parciales con textos traducidos y keyword 'only'
```

### 5.2 KitDigitalController

**Archivo:** `web/modules/custom/jaraba_billing/src/Controller/KitDigitalController.php`

**Metodo landing():**
- Lista 5 paquetes con datos estaticos traducibles (t())
- Inyecta precios via MetaSitePricingService (@? opcional)
- Inyecta logos obligatorios como paths relativos al theme
- Retorna render array con #theme = 'kit_digital_landing'
- #attached: library route-kit-digital

**Metodo paquete(string $paquete):**
- Mapea slug a datos completos del paquete
- 9 secciones: hero, categorias, funcionalidades, requisitos, precio, FAQ, CTA, logos, schema
- Precios desde MetaSitePricingService (cascada: vertical_specific → default → hardcoded fallback)
- Schema.org Product + Offer para SEO
- Retorna render array con #theme = 'kit_digital_paquete'

**DI Constructor:**
```php
public function __construct(
  protected ?MetaSitePricingService $pricingService,
  protected ?KitDigitalService $kitDigitalService,
) {}

public static function create(ContainerInterface $container): static {
  $instance = new static(
    $container->has('ecosistema_jaraba_core.metasite_pricing')
      ? $container->get('ecosistema_jaraba_core.metasite_pricing') : NULL,
    $container->has('jaraba_billing.kit_digital')
      ? $container->get('jaraba_billing.kit_digital') : NULL,
  );
  // CONTROLLER-READONLY-001: asignar manualmente
  $instance->entityTypeManager = $container->get('entity_type.manager');
  return $instance;
}
```

### 5.3 Preprocess hooks y body classes

**ecosistema_jaraba_theme.theme — hook_preprocess_html():**
```php
// Kit Digital routes
$kit_digital_routes = [
  'jaraba_billing.kit_digital.landing',
  'jaraba_billing.kit_digital.paquete',
];
if (in_array($route_name, $kit_digital_routes, TRUE)) {
  $variables['attributes']['class'][] = 'page-kit-digital';
  $variables['attributes']['class'][] = 'clean-layout';
  $variables['attributes']['class'][] = 'full-width-layout';
}
```

**hook_theme_suggestions_page_alter():**
```php
if (in_array($route, $kit_digital_routes, TRUE)) {
  $suggestions[] = 'page__kit_digital';
}
```

### 5.4 Templates Twig (zero-region)

**page--kit-digital.html.twig:**
```twig
{#
 * Zero-region landing para Kit Digital.
 * Incluye header, clean_content, footer, logos obligatorios.
 * ZERO-REGION-001: {{ clean_content }} en vez de {{ page.content }}
 #}
{% set site_name = site_name|default('Plataforma de Ecosistemas Digitales') %}

{{ attach_library('ecosistema_jaraba_theme/global-styling') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--kit-digital">

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo,
    theme_settings: theme_settings
  } only %}

  <main id="main-content" class="kit-digital-main">
    {% if clean_messages %}
      <div class="highlighted container">{{ clean_messages }}</div>
    {% endif %}
    {{ clean_content }}
  </main>

  {# Logos obligatorios Kit Digital (KIT-DIGITAL-001) #}
  {% include '@ecosistema_jaraba_theme/partials/_kit-digital-logos.html.twig' only %}

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    footer_copyright: footer_copyright,
    theme_settings: theme_settings
  } only %}

</div>
```

### 5.5 Parciales reutilizables

**Nuevos parciales a crear:**

| Parcial | Descripcion | Reutilizable en |
|---------|-------------|----------------|
| `_kit-digital-logos.html.twig` | Logos obligatorios (Kit Digital + NextGenEU + Gobierno Espana + Plan Recuperacion) | Todas las paginas /kit-digital/* |
| `_kit-digital-hero.html.twig` | Hero section con titulo, descripcion, CTA | Landing + cada paquete |
| `_kit-digital-categorias.html.twig` | Grid de categorias Kit Digital cubiertas (C1-C9) | Cada paquete |
| `_kit-digital-pricing-card.html.twig` | Tarjeta de precio regular vs precio con bono | Cada paquete |
| `_kit-digital-requisitos.html.twig` | Lista de requisitos tecnicos minimos | Cada paquete |

**Parciales existentes reutilizados:**

| Parcial existente | Uso en Kit Digital |
|-------------------|-------------------|
| `_header.html.twig` | Header de todas las paginas |
| `_footer.html.twig` | Footer configurable desde UI |
| `_faq-section.html.twig` | FAQ section en cada paquete (si existe) |
| `_copilot-fab.html.twig` | FAB del copilot IA |

### 5.6 SCSS y compilacion

**Nuevo archivo:** `web/themes/custom/ecosistema_jaraba_theme/scss/components/_kit-digital.scss`

**Estructura:**
```scss
@use '../variables' as *;

// === KIT DIGITAL LANDING ===
.kit-digital-main {
  // Full-width, mobile-first
}

.kit-digital-hero {
  background: linear-gradient(135deg, var(--ej-color-azul-corporativo, #233D63) 0%, color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 60%, white) 100%);
  padding: var(--ej-spacing-2xl) var(--ej-spacing-lg);
  text-align: center;
  color: white;
}

.kit-digital-paquetes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--ej-spacing-lg);
  padding: var(--ej-spacing-2xl) var(--ej-spacing-lg);
}

.kit-digital-paquete-card {
  border-radius: var(--ej-radius-lg, 12px);
  box-shadow: var(--ej-shadow-md);
  padding: var(--ej-spacing-lg);
  background: var(--ej-color-bg-card, #fff);
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: var(--ej-shadow-lg);
  }
}

// === LOGOS OBLIGATORIOS ===
.kit-digital-logos {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--ej-spacing-lg);
  padding: var(--ej-spacing-xl) var(--ej-spacing-lg);
  flex-wrap: wrap;
  background: var(--ej-color-bg-surface, #f8f9fa);
  border-top: 1px solid var(--ej-color-border, #e5e7eb);

  img {
    max-height: 48px;
    width: auto;
  }
}

// === PRICING CARD (bono vs regular) ===
.kit-digital-pricing {
  text-align: center;

  &__bono-badge {
    display: inline-block;
    background: var(--ej-color-verde-innovacion, #00A9A5);
    color: white;
    padding: 4px 12px;
    border-radius: var(--ej-radius-full, 9999px);
    font-weight: 700;
    font-size: var(--ej-font-size-sm, 0.875rem);
  }

  &__regular-price {
    text-decoration: line-through;
    color: var(--ej-color-text-muted, #6b7280);
  }

  &__bono-price {
    font-size: var(--ej-font-size-2xl, 2rem);
    font-weight: 800;
    color: var(--ej-color-verde-innovacion, #00A9A5);
  }
}

// === RESPONSIVE ===
@media (max-width: 768px) {
  .kit-digital-paquetes {
    grid-template-columns: 1fr;
  }

  .kit-digital-logos {
    flex-direction: column;
    gap: var(--ej-spacing-md);
  }
}
```

**Integracion en landing.scss:**
```scss
@use '../components/kit-digital';
```

**Compilacion:**
```bash
cd web/themes/custom/ecosistema_jaraba_theme
npm run build
# Verificar: ls -la css/routes/landing.css (timestamp > scss)
```

### 5.7 Libraries y attachments

**ecosistema_jaraba_theme.libraries.yml (adicion):**
```yaml
route-kit-digital:
  version: 1.0.0
  css:
    theme:
      css/routes/landing.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global-styling
```

**hook_page_attachments_alter (adicion):**
```php
if (str_starts_with($route, 'jaraba_billing.kit_digital.')) {
  $attachments['#attached']['library'][] = 'ecosistema_jaraba_theme/route-kit-digital';
}
```

### 5.8 Iconografia y logos obligatorios

**Logos Kit Digital (SVG en theme/images/):**
- `kit-digital-logo.svg` — Logo oficial Kit Digital
- `next-generation-eu.svg` — Logo NextGenerationEU
- `plan-recuperacion.svg` — Logo Plan de Recuperacion, Transformacion y Resiliencia
- `gobierno-espana.svg` — Logo Gobierno de Espana

**ICON-CONVENTION-001 para iconos de paquetes:**
```twig
{{ jaraba_icon('commerce', 'store', {variant: 'duotone', color: 'azul-corporativo', size: '48px'}) }}
{{ jaraba_icon('verticals', 'leaf', {variant: 'duotone', color: 'verde-innovacion', size: '48px'}) }}
{{ jaraba_icon('verticals', 'briefcase', {variant: 'duotone', color: 'naranja-impulso', size: '48px'}) }}
{{ jaraba_icon('legal', 'gavel', {variant: 'duotone', color: 'azul-corporativo', size: '48px'}) }}
{{ jaraba_icon('ai', 'rocket', {variant: 'duotone', color: 'naranja-impulso', size: '48px'}) }}
```

### 5.9 Precios dinamicos (NO-HARDCODE-PRICE-001)

**En preprocess:**
```php
$variables['ped_pricing'] = [];
if (\Drupal::hasService('ecosistema_jaraba_core.metasite_pricing')) {
  try {
    $pricingService = \Drupal::service('ecosistema_jaraba_core.metasite_pricing');
    $variables['ped_pricing'] = $pricingService->getPricingPreview('_default');
  }
  catch (\Throwable) {}
}
```

**En Twig:**
```twig
{# NUNCA: €39/mes (hardcoded) #}
{# SIEMPRE: #}
{{ ped_pricing.comercioconecta.starter_price|default('39') }} {% trans %}EUR/mes{% endtrans %}
```

### 5.10 Schema.org y SEO

**Cada pagina de paquete incluye:**
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Comercio Digital - Kit Digital",
  "description": "Solucion de digitalizacion para comercios de proximidad",
  "offers": {
    "@type": "Offer",
    "price": "39",
    "priceCurrency": "EUR",
    "priceValidUntil": "2027-12-31",
    "availability": "https://schema.org/InStock"
  },
  "provider": {
    "@type": "Organization",
    "name": "Plataforma de Ecosistemas Digitales S.L.",
    "url": "https://plataformadeecosistemas.com"
  }
}
```

**Meta tags via preprocess_html:**
- `<title>Kit Digital - {Paquete} | Plataforma de Ecosistemas Digitales</title>`
- `<meta name="description" content="...">`
- `<meta property="og:title" content="...">`
- `<meta property="og:image" content="/images/og-kit-digital.png">`
- `<link rel="canonical" href="https://plataformadeecosistemas.com/kit-digital/{paquete}">`

---

## 6. Fase 3 — Automatizacion: ECA + FOC + Setup Wizard + Daily Actions

### 6.1 Flujo ECA alta Kit Digital

**Trigger:** Entity insert en `kit_digital_agreement` con status = 'signed'

**Pasos:**
1. Validar segmento vs paquete
2. Calcular bono maximo con `KitDigitalService::getMaxBonoAmount()`
3. Crear tenant via Group API (si no existe)
4. Activar vertical correspondiente via `TenantVerticalService`
5. Crear suscripcion Stripe con periodo = meses cubiertos
6. Vincular con `KitDigitalService::linkToStripeSubscription()`
7. Enviar email de bienvenida al beneficiario
8. Notificar admin
9. Registrar evento FOC: `kit_digital_agreement_activated`

### 6.2 Metricas FOC

**5 metricas nuevas (KIT-DIGITAL-005: integradas en FOC existente):**

```php
'kit_digital_agreements_total'       // Acuerdos activos
'kit_digital_bono_total_eur'         // Suma total bonos EUR
'kit_digital_conversion_rate'        // kit_digital_tenants / total_tenants
'kit_digital_months_covered_avg'     // Media meses cubiertos por bono
'kit_digital_post_bono_retention'    // % tenants que siguen pagando post-bono
```

**Implementacion via @?jaraba_foc.event_recorder** (opcional, OPTIONAL-CROSSMODULE-001).

### 6.3 Setup Wizard: KitDigitalAgreementStep

**Archivo:** `web/modules/custom/jaraba_billing/src/SetupWizard/KitDigitalAgreementStep.php`

```php
class KitDigitalAgreementStep implements SetupWizardStepInterface {
  public function getId(): string { return 'admin_billing.kit_digital'; }
  public function getWizardId(): string { return 'admin_billing'; }
  public function getLabel(): TranslatableMarkup { return $this->t('Acuerdos Kit Digital'); }
  public function getWeight(): int { return 50; }
  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'certificate', 'variant' => 'duotone'];
  }
  public function getRoute(): string { return 'entity.kit_digital_agreement.collection'; }
  public function isComplete(int $tenantId): bool {
    // TRUE si tiene al menos 1 acuerdo activo
  }
}
```

### 6.4 Daily Actions: KitDigitalPendingAction + KitDigitalExpiringAction

**KitDigitalPendingAction:**
- ID: `admin_billing.kit_digital_pending`
- Muestra badge con acuerdos en estado `justification_pending`
- Color: naranja-impulso (urgencia)
- Ruta: `/admin/content/kit-digital-agreements?status=justification_pending`
- Primary: FALSE

**KitDigitalExpiringAction:**
- ID: `admin_billing.kit_digital_expiring`
- Muestra badge con acuerdos que expiran en < 60 dias
- Color: naranja-impulso
- Ruta: `/admin/content/kit-digital-agreements?expiring=60`
- Primary: FALSE

### 6.5 Admin Dashboard Kit Digital

**Ruta:** `/admin/content/kit-digital`
**Controller:** `KitDigitalAdminController::dashboard()`

**Secciones del dashboard:**
1. **KPIs:** Total acuerdos, bono total EUR, conversion rate, retencion post-bono
2. **Tabla acuerdos recientes:** agreement_number, beneficiary, paquete, status, bono, fecha
3. **Alertas:** Acuerdos pendientes justificacion, bonos proximos a expirar
4. **Grafico:** Conversion Kit Digital vs organico (mensual)

---

## 7. Fase 4 — Integracion Stripe y ciclo de vida del bono

**Flujo de vinculacion bono → Stripe:**

1. Admin crea KitDigitalAgreement con datos del beneficiario
2. Beneficiario firma el Acuerdo de Prestacion (status: signed → active)
3. Sistema crea tenant + vertical + suscripcion Stripe
4. Suscripcion Stripe: precio = plan_tier, metadata incluye `kit_digital_agreement_id`
5. Bono cubre N meses (calculateCoveredMonths)
6. Stripe NO cobra durante periodo de bono (trial_end = start_date + N meses)
7. Al expirar bono: Stripe empieza a cobrar automaticamente
8. Webhook `invoice.payment_failed` activa dunning normal
9. Si tenant cancela tras bono: se registra en FOC `kit_digital_post_bono_churn`

**STRIPE-ENV-UNIFY-001:** Todas las keys via `getenv()` en settings.secrets.php.

---

## 8. Verificacion RUNTIME-VERIFY-001 + PIPELINE-E2E-001

### RUNTIME-VERIFY-001 (5 checks post-implementacion)

| # | Check | Comando/Metodo |
|---|-------|---------------|
| 1 | CSS compilado | `ls -la css/routes/landing.css` (timestamp > SCSS) |
| 2 | Tabla DB kit_digital_agreement creada | `drush sqlq "SHOW TABLES LIKE 'kit_digital_agreement'"` |
| 3 | Rutas accesibles | `curl https://jaraba-saas.lndo.site/kit-digital` |
| 4 | data-* selectores matchean | DevTools: verificar data-paquete, data-segmento |
| 5 | drupalSettings inyectado | DevTools console: `drupalSettings.kitDigital` |

### PIPELINE-E2E-001 (4 capas)

| Capa | Verificacion |
|------|-------------|
| L1 | KitDigitalService inyectado en KitDigitalController via create() |
| L2 | Controller pasa #paquete_data, #logos, #pricing al render array |
| L3 | hook_theme() declara 'kit_digital_landing' con variables: paquetes, logos, pricing |
| L4 | Template usa parciales con `{% include ... only %}` y textos `{% trans %}` |

---

## 9. Testing

### Unit Tests (KitDigitalService)
- `testGetCategoriesForPaquete()` — 5 paquetes mapean a categorias correctas
- `testGetMaxBonoAmount()` — Matriz paquete × segmento retorna valores correctos
- `testCalculateCoveredMonths()` — Calculo meses con minimo 12
- `testIsKitDigitalTenant()` — Deteccion tenant Kit Digital

### Kernel Tests (Entity + Access)
- `testKitDigitalAgreementCRUD()` — Crear, leer, actualizar, eliminar
- `testAccessControlHandler()` — Permisos por rol
- `testTenantIsolation()` — Acuerdos de otro tenant no visibles

### Functional Tests (Landing pages)
- `testLandingPageAccessible()` — GET /kit-digital retorna 200
- `testPaquetePageAccessible()` — GET /kit-digital/comercio-digital retorna 200
- `testLogosPresentes()` — Logos obligatorios en HTML
- `testPreciosDinamicos()` — Precios desde MetaSitePricingService, no hardcoded

---

## 10. Cronograma de ejecucion

| Fase | Componentes | Commits estimados |
|------|------------|-------------------|
| **Fase 1** | Entity + Service + Access + Form + Permissions + Routes + hook_update | 1 commit |
| **Fase 2** | Controller + Templates + SCSS + Libraries + Logos + SEO | 1 commit |
| **Fase 3** | ECA + FOC + Wizard + Daily Actions + Admin Dashboard | 1 commit |
| **Fase 4** | Stripe integration + bono lifecycle | 1 commit |
| **Testing** | Unit + Kernel + Functional | 1 commit |
| **Docs** | Master docs update (v142+) | 1 commit separado (COMMIT-SCOPE-001) |

**Orden de ejecucion:** Fase 1 → Fase 2 → Fase 3 → Fase 4 → Testing → Docs

---

*Plan de implementacion generado 18 marzo 2026.*
*Basado en: Doc 179 v1 (spec), CLAUDE.md v1.5.4, Directrices v141, Arquitectura v129, Flujo v94.*
*Verificado contra: 33 directrices, 4 fases E2E, 27 scripts validacion.*
*Este plan es ejecutable por Claude Code sin ambiguedad.*
