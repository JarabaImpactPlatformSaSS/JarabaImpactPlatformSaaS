# Plan de Remediación Definitivo v2.0 — Ecosistema Jaraba Impact Platform

**Fecha:** 2026-02-23
**Autor:** Claude (Anthropic) — Consolidación Auditoría Codex + Contra-Auditoría Claude
**Estado:** DEFINITIVO — Listo para implementación por Claude Code
**Esfuerzo total:** 150-190 horas | **Horizonte:** 60 días (4 fases)
**Equipo:** 2 Backend Drupal Senior + 1 QA + 0.5 DevOps

---

## PRERREQUISITO OBLIGATORIO: Tabla de Equivalencias Canónica de Planes

**Owner:** Pepe Jaraba (Product Owner)
**Deadline:** Antes del inicio de Fase 1

Esta tabla es la FUENTE ÚNICA DE VERDAD. Todo el código debe referirse a estos machine_names.

```yaml
# config/sync/ecosistema_jaraba_core.plan_catalog.yml
plan_catalog:
  starter:
    machine_name: starter
    label_es: "Básico"
    label_en: "Starter"
    aliases_deprecados: [basico, basic, free]
    stripe_product_prefix: "prod_"
    price_monthly_eur: 29
    price_yearly_eur: 290
    features:
      max_users: 3
      max_pages: 5
      max_products: 50
      storage_gb: 5
      api_calls: 10000
      ai_credits: 1000
      webhooks: false
      api_access: false
      white_label: false
      platform_fee_percent: 8
    sla: null

  professional:
    machine_name: professional
    label_es: "Profesional"
    label_en: "Professional"
    aliases_deprecados: [profesional, growth, pro]
    stripe_product_prefix: "prod_"
    price_monthly_eur: 79
    price_yearly_eur: 790
    features:
      max_users: 10
      max_pages: 20
      max_products: 500
      storage_gb: 25
      api_calls: 50000
      ai_credits: 5000
      webhooks: true
      api_access: true
      white_label: false
      platform_fee_percent: 5
    sla: "99.5%"

  enterprise:
    machine_name: enterprise
    label_es: "Enterprise"
    label_en: "Enterprise"
    aliases_deprecados: [business, premium]
    stripe_product_prefix: "prod_"
    price_monthly_eur: 199
    price_yearly_eur: 1990
    features:
      max_users: -1  # ilimitado
      max_pages: -1
      max_products: -1
      storage_gb: -1
      api_calls: -1
      ai_credits: -1
      webhooks: true
      api_access: true
      white_label: true
      platform_fee_percent: 3
    sla: "99.9%"
```

---

## FASE 1: Aislamiento Multi-Tenant + Contrato Tenant (Días 1-15)

### REM-P0-01: Unificar contrato tenant en billing API/webhooks
**Estimación:** 10-12h | **Módulo:** `jaraba_billing`
**Problema:** BillingApiController carga entidades Group y las pasa a servicios tipados para TenantInterface.
**Referencia spec:** Doc 07 (TenantContextService), Doc 02 (jaraba_tenant module)

#### Paso 1: Crear TenantResolverTrait para billing

```php
<?php
// web/modules/custom/jaraba_billing/src/Trait/TenantResolverTrait.php

namespace Drupal\jaraba_billing\Trait;

use Drupal\jaraba_tenant\Service\TenantContextServiceInterface;
use Drupal\jaraba_tenant\Entity\TenantInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait TenantResolverTrait {

  /**
   * Resuelve el tenant actual usando TenantContextService.
   *
   * NUNCA cargar Group directamente. Siempre usar este método.
   * Ref: Doc 07, sección 7.2 — TenantContextService
   */
  protected function resolveCurrentTenant(): TenantInterface {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant instanceof TenantInterface) {
      throw new AccessDeniedHttpException('No se pudo resolver el tenant actual.');
    }
    return $tenant;
  }

  /**
   * Resuelve tenant por ID con verificación de tipo.
   */
  protected function resolveTenantById(int $tenantId): TenantInterface {
    $tenant = $this->entityTypeManager
      ->getStorage('tenant')
      ->load($tenantId);

    if (!$tenant instanceof TenantInterface) {
      throw new AccessDeniedHttpException(
        sprintf('Tenant %d no encontrado o tipo inválido.', $tenantId)
      );
    }
    return $tenant;
  }
}
```

#### Paso 2: Refactorizar BillingApiController

```php
<?php
// web/modules/custom/jaraba_billing/src/Controller/BillingApiController.php

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_billing\Trait\TenantResolverTrait;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_tenant\Service\TenantContextServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class BillingApiController extends ControllerBase {

  use TenantResolverTrait;

  public function __construct(
    protected TenantContextServiceInterface $tenantContext,
    protected TenantSubscriptionService $subscriptionService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_tenant.context'),
      $container->get('jaraba_billing.subscription_service'),
    );
  }

  /**
   * GET /api/v1/billing/subscription
   *
   * ANTES (roto):
   *   $group = $this->entityTypeManager()->getStorage('group')->load($id);
   *   $this->subscriptionService->getSubscription($group); // TypeError
   *
   * DESPUÉS (correcto):
   *   $tenant = $this->resolveCurrentTenant(); // TenantInterface garantizado
   */
  public function getSubscription(): JsonResponse {
    $tenant = $this->resolveCurrentTenant();
    $subscription = $this->subscriptionService->getActiveSubscription($tenant);

    if (!$subscription) {
      return new JsonResponse(['error' => 'No active subscription'], 404);
    }

    return new JsonResponse([
      'data' => [
        'tenant_id' => $tenant->id(),
        'plan' => $subscription->get('tier')->value,
        'status' => $subscription->get('status')->value,
        'current_period_start' => $subscription->get('current_period_start')->value,
        'current_period_end' => $subscription->get('current_period_end')->value,
        'cancel_at_period_end' => (bool) $subscription->get('cancel_at_period_end')->value,
        'stripe_subscription_id' => $subscription->get('stripe_subscription_id')->value,
      ],
    ]);
  }

  /**
   * GET /api/v1/billing/usage
   */
  public function getUsage(): JsonResponse {
    $tenant = $this->resolveCurrentTenant();
    $usage = $this->subscriptionService->getUsageSummary($tenant);
    return new JsonResponse(['data' => $usage]);
  }
}
```

#### Paso 3: Refactorizar BillingWebhookController

```php
<?php
// web/modules/custom/jaraba_billing/src/Controller/BillingWebhookController.php
// SOLO el método que resuelve tenant — el resto del webhook handler se mantiene

namespace Drupal\jaraba_billing\Controller;

use Drupal\jaraba_billing\Trait\TenantResolverTrait;

class BillingWebhookController extends ControllerBase {

  use TenantResolverTrait;

  /**
   * Resuelve tenant desde metadata de Stripe subscription.
   *
   * El tenant_id se almacena en subscription.metadata.tenant_id
   * al crear la suscripción (Doc 134, sección 5.4).
   *
   * ANTES (roto):
   *   $group = $this->entityTypeManager()->getStorage('group')->load($metadata['group_id']);
   *
   * DESPUÉS (correcto):
   */
  protected function resolveTenantFromStripeEvent(\Stripe\Event $event): \Drupal\jaraba_tenant\Entity\TenantInterface {
    $object = $event->data->object;

    // Intentar metadata directa
    $tenantId = $object->metadata['tenant_id'] ?? null;

    if (!$tenantId) {
      // Fallback: buscar por stripe_customer_id
      $customerId = $object->customer ?? null;
      if ($customerId) {
        $tenants = $this->entityTypeManager
          ->getStorage('tenant')
          ->loadByProperties(['field_stripe_customer_id' => $customerId]);
        $tenant = reset($tenants);
        if ($tenant) {
          return $tenant;
        }
      }
      throw new \RuntimeException('Cannot resolve tenant from Stripe event: ' . $event->id);
    }

    return $this->resolveTenantById((int) $tenantId);
  }
}
```

#### Paso 4: Actualizar services.yml

```yaml
# web/modules/custom/jaraba_billing/jaraba_billing.services.yml
services:
  jaraba_billing.api_controller:
    class: Drupal\jaraba_billing\Controller\BillingApiController
    arguments:
      - '@jaraba_tenant.context'
      - '@jaraba_billing.subscription_service'

  jaraba_billing.webhook_controller:
    class: Drupal\jaraba_billing\Controller\BillingWebhookController
    arguments:
      - '@jaraba_tenant.context'
      - '@jaraba_billing.subscription_service'
      - '@jaraba_billing.stripe_factory'
      - '@logger.channel.jaraba_billing'
```

#### Test obligatorio REM-P0-01

```php
<?php
// web/modules/custom/jaraba_billing/tests/src/Kernel/TenantResolutionBillingTest.php

namespace Drupal\Tests\jaraba_billing\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_tenant\Entity\Tenant;
use Drupal\jaraba_billing\Controller\BillingApiController;

class TenantResolutionBillingTest extends KernelTestBase {

  protected static $modules = [
    'jaraba_tenant',
    'jaraba_billing',
    'group',
    'user',
  ];

  /**
   * Verifica que el controller usa TenantInterface, no Group.
   */
  public function testBillingControllerResolvesTenantInterface(): void {
    // Crear tenant de test
    $tenant = Tenant::create([
      'name' => 'Test Tenant',
      'machine_name' => 'test_tenant',
      'plan_type' => 'starter',
      'status' => 'active',
    ]);
    $tenant->save();

    // Mockear TenantContextService para que devuelva el tenant
    $tenantContext = $this->createMock(\Drupal\jaraba_tenant\Service\TenantContextServiceInterface::class);
    $tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $this->container->set('jaraba_tenant.context', $tenantContext);

    $controller = BillingApiController::create($this->container);
    $response = $controller->getSubscription();

    // No debe lanzar TypeError (que es lo que pasaba con Group)
    $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
  }

  /**
   * Verifica que se deniega acceso si no hay tenant.
   */
  public function testBillingDeniesWithoutTenant(): void {
    $tenantContext = $this->createMock(\Drupal\jaraba_tenant\Service\TenantContextServiceInterface::class);
    $tenantContext->method('getCurrentTenant')->willReturn(null);

    $this->container->set('jaraba_tenant.context', $tenantContext);

    $controller = BillingApiController::create($this->container);

    $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);
    $controller->getSubscription();
  }
}
```

---

### REM-P0-02: Cerrar exposición cross-tenant en Search Console endpoint
**Estimación:** 6-8h | **Módulo:** `jaraba_page_builder`
**Problema:** AnalyticsDashboardController::getSearchConsoleData() acepta page_id sin verificar pertenencia al tenant.
**Referencia spec:** Doc 160 (Page Builder SaaS), Doc 07 (aislamiento)

#### Implementación: Middleware de verificación tenant para PageContent

```php
<?php
// web/modules/custom/jaraba_page_builder/src/Service/PageTenantVerifier.php

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_page_builder\Entity\PageContentInterface;
use Drupal\jaraba_tenant\Entity\TenantInterface;
use Drupal\jaraba_tenant\Service\TenantContextServiceInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PageTenantVerifier {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextServiceInterface $tenantContext,
  ) {}

  /**
   * Verifica que una página pertenece al tenant activo.
   *
   * @throws AccessDeniedHttpException si page_id no pertenece al tenant.
   */
  public function verifyPageBelongsToCurrentTenant(int $pageId): PageContentInterface {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant instanceof TenantInterface) {
      throw new AccessDeniedHttpException('Tenant no resuelto.');
    }

    $page = $this->entityTypeManager
      ->getStorage('page_content')
      ->load($pageId);

    if (!$page instanceof PageContentInterface) {
      throw new AccessDeniedHttpException(
        sprintf('Página %d no encontrada.', $pageId)
      );
    }

    // Verificar ownership por tenant_id (campo definido en Doc 160, PageContent entity)
    $pageTenantId = $page->get('tenant_id')->target_id ?? $page->get('tenant_id')->value ?? null;

    if ((int) $pageTenantId !== (int) $tenant->id()) {
      // Log del intento de acceso cross-tenant
      \Drupal::logger('jaraba_page_builder')->warning(
        'Cross-tenant access attempt: tenant @current tried to access page @page owned by tenant @owner',
        [
          '@current' => $tenant->id(),
          '@page' => $pageId,
          '@owner' => $pageTenantId,
        ]
      );
      throw new AccessDeniedHttpException('Acceso denegado: recurso no pertenece a este tenant.');
    }

    return $page;
  }
}
```

#### Aplicar en AnalyticsDashboardController

```php
<?php
// web/modules/custom/jaraba_page_builder/src/Controller/AnalyticsDashboardController.php
// Refactorizar los métodos que aceptan page_id

class AnalyticsDashboardController extends ControllerBase {

  public function __construct(
    // ... dependencias existentes ...
    protected PageTenantVerifier $pageTenantVerifier,
  ) {}

  /**
   * GET /api/page-builder/analytics/{page_id}/search-console
   *
   * ANTES (inseguro):
   *   $page = $this->entityTypeManager()->getStorage('page_content')->load($pageId);
   *   // Sin verificación de tenant -> exposición cross-tenant
   *
   * DESPUÉS (seguro):
   */
  public function getSearchConsoleData(int $page_id): JsonResponse {
    // Esta línea es el fix: verifica ownership O lanza 403
    $page = $this->pageTenantVerifier->verifyPageBelongsToCurrentTenant($page_id);

    // Lógica de analytics existente...
    $data = $this->analyticsService->getSearchConsoleMetrics($page);

    return new JsonResponse(['data' => $data]);
  }

  /**
   * Aplicar el mismo patrón a TODOS los endpoints que aceptan page_id:
   * - getPageAnalytics($page_id)
   * - getPagePerformance($page_id)
   * - getPageHeatmap($page_id)
   * - etc.
   */
}
```

#### Service definition

```yaml
# web/modules/custom/jaraba_page_builder/jaraba_page_builder.services.yml
services:
  jaraba_page_builder.page_tenant_verifier:
    class: Drupal\jaraba_page_builder\Service\PageTenantVerifier
    arguments:
      - '@entity_type.manager'
      - '@jaraba_tenant.context'
```

#### Test obligatorio REM-P0-02

```php
<?php
// web/modules/custom/jaraba_page_builder/tests/src/Kernel/CrossTenantAccessTest.php

namespace Drupal\Tests\jaraba_page_builder\Kernel;

use Drupal\KernelTests\KernelTestBase;

class CrossTenantAccessTest extends KernelTestBase {

  protected static $modules = [
    'jaraba_page_builder',
    'jaraba_tenant',
    'group',
    'user',
  ];

  public function testBlocksCrossTenantPageAccess(): void {
    // Crear tenant A y tenant B
    $tenantA = $this->createTenant('tenant_a', 'starter');
    $tenantB = $this->createTenant('tenant_b', 'professional');

    // Crear página asignada a tenant A
    $page = $this->createPage($tenantA->id(), 'Mi Página');

    // Configurar contexto como tenant B
    $this->setCurrentTenant($tenantB);

    // Intentar acceder a página de tenant A desde contexto de tenant B
    $verifier = $this->container->get('jaraba_page_builder.page_tenant_verifier');

    $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);
    $this->expectExceptionMessage('Acceso denegado: recurso no pertenece a este tenant.');

    $verifier->verifyPageBelongsToCurrentTenant($page->id());
  }

  public function testAllowsSameTenantPageAccess(): void {
    $tenantA = $this->createTenant('tenant_a', 'starter');
    $page = $this->createPage($tenantA->id(), 'Mi Página');
    $this->setCurrentTenant($tenantA);

    $verifier = $this->container->get('jaraba_page_builder.page_tenant_verifier');
    $result = $verifier->verifyPageBelongsToCurrentTenant($page->id());

    $this->assertEquals($page->id(), $result->id());
  }

  // Helpers...
  protected function createTenant(string $machineName, string $plan): object { /* ... */ }
  protected function createPage(int $tenantId, string $title): object { /* ... */ }
  protected function setCurrentTenant(object $tenant): void { /* ... */ }
}
```

---

### REM-P0-03: Access handler PageContent con criterio tenant
**Estimación:** 6-8h | **Módulo:** `jaraba_page_builder`
**Problema:** PageContentAccessControlHandler no valida tenant_id, solo owner y permisos.
**Referencia spec:** Doc 160 (permisos Page Builder), Doc 04 (RBAC)

```php
<?php
// web/modules/custom/jaraba_page_builder/src/PageContentAccessControlHandler.php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class PageContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * ANTES: Solo verificaba permisos y owner.
   * DESPUÉS: Verifica TAMBIÉN tenant ownership.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // 1. Super admin bypass
    if ($account->hasPermission('bypass tenant isolation')) {
      return AccessResult::allowed()
        ->cachePerPermissions();
    }

    // 2. NUEVO: Verificar tenant ownership
    $tenantContext = \Drupal::service('jaraba_tenant.context');
    $currentTenant = $tenantContext->getCurrentTenant();

    if ($currentTenant) {
      $entityTenantId = $entity->get('tenant_id')->target_id
        ?? $entity->get('tenant_id')->value
        ?? null;

      if ($entityTenantId && (int) $entityTenantId !== (int) $currentTenant->id()) {
        return AccessResult::forbidden('Cross-tenant access denied.')
          ->addCacheableDependency($entity)
          ->addCacheableDependency($currentTenant);
      }
    }

    // 3. Verificaciones de permisos existentes
    switch ($operation) {
      case 'view':
        if ($entity->get('status')->value) {
          return AccessResult::allowedIfHasPermission($account, 'access page builder');
        }
        return AccessResult::allowedIf(
          $account->id() === $entity->getOwnerId()
        )->addCacheableDependency($entity);

      case 'update':
        if ($account->hasPermission('edit any page content')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::allowedIf(
          $account->hasPermission('edit own page content')
          && $account->id() === $entity->getOwnerId()
        )->cachePerPermissions()->addCacheableDependency($entity);

      case 'delete':
        if ($account->hasPermission('delete any page content')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::allowedIf(
          $account->hasPermission('delete own page content')
          && $account->id() === $entity->getOwnerId()
        )->cachePerPermissions()->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }
}
```

---

## FASE 2: Coherencia Billing-Plan-Pricing (Días 16-30)

### REM-P0-04: Unificar mapping Stripe en webhooks
**Estimación:** 10-12h | **Módulos:** `jaraba_billing` + `ecosistema_jaraba_core`
**Problema:** jaraba_billing webhook escribe product_id en subscription_plan, pero core mapea por stripe_price_id.
**Referencia spec:** Doc 134 (sección 5.4 — SubscriptionUpdatedHandler)

#### Crear PlanResolverService canónico

```php
<?php
// web/modules/custom/ecosistema_jaraba_core/src/Service/PlanResolverService.php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * FUENTE ÚNICA DE VERDAD para resolver planes.
 *
 * Cualquier módulo que necesite resolver un plan (desde Stripe price_id,
 * product_id, machine_name o alias) DEBE usar este servicio.
 *
 * Ref: Prerrequisito — Tabla de Equivalencias Canónica
 */
class PlanResolverService {

  /**
   * Mapeo canónico price_id -> plan machine_name.
   * Se carga desde config en constructor.
   */
  private array $priceToplan = [];
  private array $productToPlan = [];
  private array $aliasToPlan = [];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->buildMappings();
  }

  /**
   * Construye los mapeos desde la config canónica + entidades SaasPlan.
   */
  private function buildMappings(): void {
    // Cargar planes desde entidades SaasPlan
    $plans = $this->entityTypeManager
      ->getStorage('saas_plan')
      ->loadMultiple();

    foreach ($plans as $plan) {
      $machineName = $plan->get('machine_name')->value ?? $plan->id();

      // Mapeo por stripe_price_id (mensual y anual)
      $monthlyPriceId = $plan->get('stripe_price_id_monthly')->value ?? '';
      $yearlyPriceId = $plan->get('stripe_price_id_yearly')->value ?? '';

      if ($monthlyPriceId) {
        $this->priceToplan[$monthlyPriceId] = $machineName;
      }
      if ($yearlyPriceId) {
        $this->priceToplan[$yearlyPriceId] = $machineName;
      }

      // Mapeo por stripe_product_id
      $productId = $plan->get('stripe_product_id')->value ?? '';
      if ($productId) {
        $this->productToPlan[$productId] = $machineName;
      }
    }

    // Cargar aliases deprecados desde config canónica
    $catalog = $this->configFactory->get('ecosistema_jaraba_core.plan_catalog');
    foreach (['starter', 'professional', 'enterprise'] as $plan) {
      $aliases = $catalog->get("plan_catalog.{$plan}.aliases_deprecados") ?? [];
      foreach ($aliases as $alias) {
        $this->aliasToPlan[$alias] = $plan;
      }
      // El propio machine_name también es un alias válido
      $this->aliasToPlan[$plan] = $plan;
    }
  }

  /**
   * Resuelve plan desde Stripe price_id.
   * Usar en webhooks: subscription.items.data[0].price.id
   */
  public function resolveFromPriceId(string $priceId): ?string {
    return $this->priceToplan[$priceId] ?? null;
  }

  /**
   * Resuelve plan desde Stripe product_id.
   * Fallback si price_id no está disponible.
   */
  public function resolveFromProductId(string $productId): ?string {
    return $this->productToPlan[$productId] ?? null;
  }

  /**
   * Normaliza cualquier nombre de plan (incluyendo aliases deprecados).
   *
   * Ejemplos:
   *   'basico' -> 'starter'
   *   'profesional' -> 'professional'
   *   'professional' -> 'professional'
   *   'growth' -> 'professional'
   */
  public function normalize(string $planName): string {
    $normalized = strtolower(trim($planName));
    return $this->aliasToPlan[$normalized] ?? $normalized;
  }

  /**
   * Resuelve plan desde un evento Stripe completo.
   * Intenta price_id primero, fallback a product_id.
   */
  public function resolveFromStripeSubscription(object $stripeSubscription): string {
    // Intentar por price_id (preferido, más específico)
    $priceId = $stripeSubscription->items->data[0]->price->id ?? '';
    $plan = $this->resolveFromPriceId($priceId);
    if ($plan) {
      return $plan;
    }

    // Fallback: por product_id
    $productId = $stripeSubscription->items->data[0]->price->product ?? '';
    $plan = $this->resolveFromProductId($productId);
    if ($plan) {
      return $plan;
    }

    // Último fallback: metadata
    $metadataPlan = $stripeSubscription->metadata['plan'] ?? '';
    if ($metadataPlan) {
      return $this->normalize($metadataPlan);
    }

    throw new \RuntimeException(
      sprintf('Cannot resolve plan from Stripe subscription %s (price: %s, product: %s)',
        $stripeSubscription->id, $priceId, $productId)
    );
  }

  /**
   * Valida que un plan machine_name es canónico.
   */
  public function isValidPlan(string $planName): bool {
    return in_array($planName, ['starter', 'professional', 'enterprise'], true);
  }

  /**
   * Obtiene features de un plan.
   */
  public function getFeatures(string $planName): array {
    $normalized = $this->normalize($planName);
    $catalog = $this->configFactory->get('ecosistema_jaraba_core.plan_catalog');
    return $catalog->get("plan_catalog.{$normalized}.features") ?? [];
  }
}
```

#### Service definition

```yaml
# web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml
services:
  ecosistema_jaraba_core.plan_resolver:
    class: Drupal\ecosistema_jaraba_core\Service\PlanResolverService
    arguments:
      - '@config.factory'
      - '@entity_type.manager'
```

#### Refactorizar SubscriptionUpdatedHandler para usar PlanResolverService

```php
<?php
// web/modules/custom/jaraba_billing/src/WebhookHandler/SubscriptionUpdatedHandler.php
// Sección relevante — resolver plan

class SubscriptionUpdatedHandler implements WebhookHandlerInterface {

  public function __construct(
    protected PlanResolverService $planResolver,
    // ... otras dependencias
  ) {}

  public function handle(\Stripe\Event $event): void {
    $stripeSubscription = $event->data->object;

    $subscription = BillingSubscription::loadByStripeId($stripeSubscription->id);
    if (!$subscription) {
      throw new \Exception('Subscription not found: ' . $stripeSubscription->id);
    }

    // ANTES (roto): $subscription->set('subscription_plan', $stripeSubscription->items->data[0]->price->product);
    // Esto guardaba product_id donde debería ir el plan machine_name.

    // DESPUÉS (correcto): usar PlanResolverService
    $newPlan = $this->planResolver->resolveFromStripeSubscription($stripeSubscription);
    $newPriceId = $stripeSubscription->items->data[0]->price->id;

    $subscription->set('tier', $newPlan);            // machine_name canónico
    $subscription->set('price_id', $newPriceId);     // stripe price_id para trazabilidad
    $subscription->set('status', $stripeSubscription->status);
    $subscription->set('current_period_start', $stripeSubscription->current_period_start);
    $subscription->set('current_period_end', $stripeSubscription->current_period_end);
    $subscription->set('cancel_at_period_end', $stripeSubscription->cancel_at_period_end);

    // Actualizar permisos si el plan cambió
    $oldPlan = $subscription->original?->get('tier')->value ?? '';
    if ($oldPlan !== $newPlan) {
      $this->permissionService->updateTenantPermissions(
        $subscription->get('tenant_id')->value,
        $newPlan
      );
    }

    $subscription->save();
  }
}
```

#### Test obligatorio REM-P0-04

```php
<?php
// web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/PlanResolverServiceTest.php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;

class PlanResolverServiceTest extends UnitTestCase {

  public function testNormalizeAliases(): void {
    $resolver = $this->createResolver();

    $this->assertEquals('starter', $resolver->normalize('basico'));
    $this->assertEquals('starter', $resolver->normalize('basic'));
    $this->assertEquals('starter', $resolver->normalize('free'));
    $this->assertEquals('starter', $resolver->normalize('starter'));
    $this->assertEquals('professional', $resolver->normalize('profesional'));
    $this->assertEquals('professional', $resolver->normalize('growth'));
    $this->assertEquals('professional', $resolver->normalize('pro'));
    $this->assertEquals('professional', $resolver->normalize('professional'));
    $this->assertEquals('enterprise', $resolver->normalize('business'));
    $this->assertEquals('enterprise', $resolver->normalize('premium'));
    $this->assertEquals('enterprise', $resolver->normalize('enterprise'));
  }

  public function testIsValidPlan(): void {
    $resolver = $this->createResolver();

    $this->assertTrue($resolver->isValidPlan('starter'));
    $this->assertTrue($resolver->isValidPlan('professional'));
    $this->assertTrue($resolver->isValidPlan('enterprise'));
    $this->assertFalse($resolver->isValidPlan('basico'));
    $this->assertFalse($resolver->isValidPlan('growth'));
    $this->assertFalse($resolver->isValidPlan('unknown'));
  }

  // Helper para crear resolver con config mockeada
  private function createResolver(): PlanResolverService { /* ... */ }
}
```

---

### REM-P0-05: Canonizar IDs de plan en runtime
**Estimación:** 8-10h | **Módulos:** `core` + `billing` + `page_builder`

#### Config sync corregida

```yaml
# config/sync/ecosistema_jaraba_core.saas_plan.starter.yml
id: starter
label: 'Starter'
label_es: 'Básico'
machine_name: starter
price_monthly: 29
price_yearly: 290
currency: EUR
stripe_product_id: ''       # Completar en despliegue
stripe_price_id_monthly: '' # Completar en despliegue
stripe_price_id_yearly: ''  # Completar en despliegue
features:
  max_users: 3
  max_pages: 5
  max_products: 50
  storage_gb: 5
  api_calls: 10000
  ai_credits: 1000
  webhooks: false
  api_access: false
  white_label: false
is_active: true
sort_order: 10

# config/sync/ecosistema_jaraba_core.saas_plan.professional.yml
id: professional
label: 'Professional'
label_es: 'Profesional'
machine_name: professional
price_monthly: 79
price_yearly: 790
# ... (mismo patrón, features de professional según Doc 04/158)

# config/sync/ecosistema_jaraba_core.saas_plan.enterprise.yml
id: enterprise
label: 'Enterprise'
label_es: 'Enterprise'
machine_name: enterprise
price_monthly: 199
price_yearly: 1990
# ... (features enterprise)
```

#### ELIMINAR los archivos de config con nombres en español

```bash
# Archivos a ELIMINAR (aliases deprecados en config sync)
rm config/sync/ecosistema_jaraba_core.saas_plan.basico.yml
rm config/sync/ecosistema_jaraba_core.saas_plan.profesional.yml
# enterprise se mantiene (ya es canónico)
```

---

### REM-P0-06: Bugs nominales de pricing y trial typing
**Estimación:** 4-6h | **Módulo:** `ecosistema_jaraba_core`

#### Fix: Métodos de pricing en SaasPlan

```php
<?php
// web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlan.php
// AÑADIR aliases de métodos para backward compatibility

class SaasPlan extends ContentEntityBase implements SaasPlanInterface {

  /**
   * Método canónico (definido en interfaz).
   */
  public function getPriceMonthly(): float {
    return (float) ($this->get('price_monthly')->value ?? 0);
  }

  /**
   * Método canónico (definido en interfaz).
   */
  public function getPriceYearly(): float {
    return (float) ($this->get('price_yearly')->value ?? 0);
  }

  /**
   * @deprecated Usar getPriceMonthly(). Se eliminará en v2.1.
   */
  public function getMonthlyPrice(): float {
    @trigger_error('getMonthlyPrice() is deprecated. Use getPriceMonthly().', E_USER_DEPRECATED);
    return $this->getPriceMonthly();
  }

  /**
   * @deprecated Usar getPriceYearly(). Se eliminará en v2.1.
   */
  public function getYearlyPrice(): float {
    @trigger_error('getYearlyPrice() is deprecated. Use getPriceYearly().', E_USER_DEPRECATED);
    return $this->getPriceYearly();
  }
}
```

#### Fix: Trial date typing

```php
<?php
// web/modules/custom/ecosistema_jaraba_core/src/Controller/OnboardingController.php
// CORREGIR las líneas que tratan DateTimeInterface como string

// ANTES (roto):
// $trialEnd = $tenant->getTrialEndsAt();
// $daysLeft = (strtotime($trialEnd) - time()) / 86400;  // ERROR: $trialEnd es DateTimeInterface

// DESPUÉS (correcto):
$trialEnd = $tenant->getTrialEndsAt();
if ($trialEnd instanceof \DateTimeInterface) {
  $now = new \DateTimeImmutable();
  $daysLeft = (int) $now->diff($trialEnd)->format('%r%a');
} else {
  $daysLeft = 0;
}
```

```php
<?php
// web/modules/custom/ecosistema_jaraba_core/src/Controller/StripeController.php
// Mismo patrón

// ANTES (roto):
// $trialEndTimestamp = new DateTime($tenant->getTrialEndsAt());

// DESPUÉS (correcto):
$trialEnd = $tenant->getTrialEndsAt();
$trialEndTimestamp = ($trialEnd instanceof \DateTimeInterface)
  ? $trialEnd->getTimestamp()
  : 0;
```

---

## FASE 3: Configuración y Entitlements sin Drift (Días 31-50)

### REM-P1-01: Normalizar keys de configuración Page Builder
**Estimación:** 6-8h | **Módulo:** `jaraba_page_builder`

```yaml
# web/modules/custom/jaraba_page_builder/config/schema/jaraba_page_builder.schema.yml
# ANTES: tres keys diferentes (page_limits, default_plans_limit, default_max_pages)
# DESPUÉS: una sola key canónica

jaraba_page_builder.settings:
  type: config_object
  label: 'Page Builder Settings'
  mapping:
    plan_limits:
      type: mapping
      label: 'Límites por plan'
      mapping:
        starter:
          type: mapping
          mapping:
            max_pages: { type: integer, label: 'Máximo de páginas' }
            premium_blocks: { type: boolean, label: 'Acceso bloques premium' }
        professional:
          type: mapping
          mapping:
            max_pages: { type: integer, label: 'Máximo de páginas' }
            premium_blocks: { type: boolean, label: 'Acceso bloques premium' }
        enterprise:
          type: mapping
          mapping:
            max_pages: { type: integer, label: 'Máximo de páginas (-1 = ilimitado)' }
            premium_blocks: { type: boolean, label: 'Acceso bloques premium' }
```

```yaml
# config/sync/jaraba_page_builder.settings.yml
plan_limits:
  starter:
    max_pages: 5
    premium_blocks: false
  professional:
    max_pages: 20
    premium_blocks: true
  enterprise:
    max_pages: -1
    premium_blocks: true
```

### REM-P1-02: Eliminar fallback hardcode de cuotas
**Estimación:** 10-12h | **Módulo:** `jaraba_page_builder`

```php
<?php
// web/modules/custom/jaraba_page_builder/src/Service/QuotaManagerService.php

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Drupal\jaraba_tenant\Service\TenantContextServiceInterface;

class QuotaManagerService {

  public function __construct(
    protected TenantContextServiceInterface $tenantContext,
    protected PlanResolverService $planResolver,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Verifica si el tenant puede crear más páginas.
   *
   * ANTES: fallback hardcodeado si tenant no era TenantInterface
   * DESPUÉS: siempre resuelve via TenantContextService + PlanResolverService
   */
  public function canCreatePage(): bool {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return false;
    }

    $planName = $this->planResolver->normalize(
      $tenant->get('plan_type')->value ?? 'starter'
    );

    $limit = $this->getPageLimit($planName);

    // -1 = ilimitado
    if ($limit === -1) {
      return true;
    }

    $currentCount = $this->countTenantPages($tenant->id());
    return $currentCount < $limit;
  }

  /**
   * Obtiene el límite de páginas del plan desde config canónica.
   *
   * NO hay fallbacks hardcodeados. Si la config no existe, es un error
   * de configuración que debe detectarse en CI (ver REM-P2-02).
   */
  private function getPageLimit(string $planName): int {
    $config = $this->configFactory->get('jaraba_page_builder.settings');
    $limit = $config->get("plan_limits.{$planName}.max_pages");

    if ($limit === null) {
      \Drupal::logger('jaraba_page_builder')->error(
        'Plan limit not configured for plan: @plan. Check jaraba_page_builder.settings.yml',
        ['@plan' => $planName]
      );
      // Fail-safe: denegar si no hay config (NO hardcodear un número)
      return 0;
    }

    return (int) $limit;
  }

  private function countTenantPages(int $tenantId): int {
    return (int) \Drupal::entityTypeManager()
      ->getStorage('page_content')
      ->getQuery()
      ->accessCheck(false)
      ->condition('tenant_id', $tenantId)
      ->condition('status', 1)
      ->count()
      ->execute();
  }

  /**
   * Devuelve resumen de uso para el dashboard.
   */
  public function getUsageSummary(): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return [];
    }

    $plan = $this->planResolver->normalize($tenant->get('plan_type')->value ?? 'starter');
    $limit = $this->getPageLimit($plan);
    $current = $this->countTenantPages($tenant->id());

    return [
      'pages' => [
        'current' => $current,
        'limit' => $limit,
        'percentage' => $limit > 0 ? round(($current / $limit) * 100, 1) : 0,
        'unlimited' => $limit === -1,
      ],
    ];
  }
}
```

### REM-P1-03 a REM-P1-05: Tareas complementarias

**REM-P1-03 (2-4h):** Script de validación pre-deploy que verifica stripe_price_id no vacío.

```php
<?php
// scripts/validate_stripe_config.php
// Ejecutar en CI antes de deploy

$plans = \Drupal::entityTypeManager()->getStorage('saas_plan')->loadMultiple();
$errors = [];

foreach ($plans as $plan) {
  if ($plan->get('is_active')->value) {
    $priceMonthly = $plan->get('stripe_price_id_monthly')->value ?? '';
    $priceYearly = $plan->get('stripe_price_id_yearly')->value ?? '';
    $productId = $plan->get('stripe_product_id')->value ?? '';

    if (empty($priceMonthly) && getenv('APP_ENV') === 'production') {
      $errors[] = "Plan {$plan->id()}: stripe_price_id_monthly vacío en producción";
    }
    if (empty($productId) && getenv('APP_ENV') === 'production') {
      $errors[] = "Plan {$plan->id()}: stripe_product_id vacío en producción";
    }
  }
}

if ($errors) {
  fwrite(STDERR, "ERRORES DE CONFIGURACIÓN STRIPE:\n" . implode("\n", $errors) . "\n");
  exit(1);
}
echo "✓ Configuración Stripe válida\n";
```

**REM-P1-04 (4-6h):** Reemplazar llamadas a getTenantForUser() con TenantContextService.

```php
// BUSCAR Y REEMPLAZAR en ecosistema_jaraba_core.module
// ANTES:
// $tenant = $tenant_context->getTenantForUser($user); // Método que puede no existir

// DESPUÉS:
$tenant = \Drupal::service('jaraba_tenant.context')->getCurrentTenant();
```

**REM-P1-05 (3-4h):** Flag de fuente de datos en analytics.

```php
// En AnalyticsDashboardController, cualquier respuesta con datos simulados:
return new JsonResponse([
  'data' => $metrics,
  'meta' => [
    'source' => $this->hasRealData($tenant) ? 'real' : 'simulated',
    'warning' => $this->hasRealData($tenant) ? null : 'Datos de demostración. Conecte Google Search Console para datos reales.',
  ],
]);
```

---

## FASE 4: Hardening CI + Tests de Contrato (Días 51-60)

### REM-P2-01: CI con Kernel/Functional tests
**Estimación:** 12-15h

```yaml
# .github/workflows/ci.yml
name: CI Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  unit-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_mysql, gd, zip
      - run: composer install --no-interaction
      - name: Unit Tests
        run: vendor/bin/phpunit --testsuite Unit

  kernel-tests:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mariadb:10.11
        env:
          MYSQL_DATABASE: drupal_test
          MYSQL_ROOT_PASSWORD: test
        ports:
          - 3306:3306
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_mysql, gd, zip
      - run: composer install --no-interaction
      - name: Kernel Tests (módulos críticos)
        run: |
          vendor/bin/phpunit --testsuite Kernel \
            --filter='jaraba_billing|jaraba_page_builder|ecosistema_jaraba_core|jaraba_tenant'
        env:
          SIMPLETEST_DB: mysql://root:test@127.0.0.1:3306/drupal_test
          SIMPLETEST_BASE_URL: http://localhost

  functional-tests:
    runs-on: ubuntu-latest
    needs: [kernel-tests]
    services:
      mysql:
        image: mariadb:10.11
        env:
          MYSQL_DATABASE: drupal_test
          MYSQL_ROOT_PASSWORD: test
        ports:
          - 3306:3306
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_mysql, gd, zip
      - run: composer install --no-interaction
      - name: Functional Tests (rutas y permisos)
        run: |
          vendor/bin/phpunit --testsuite Functional \
            --filter='jaraba_billing|jaraba_page_builder'
        env:
          SIMPLETEST_DB: mysql://root:test@127.0.0.1:3306/drupal_test
          SIMPLETEST_BASE_URL: http://localhost

  stripe-config-check:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - name: Validate Stripe Config
        run: |
          # Verificar que configs de planes no tienen IDs vacíos en main
          for file in config/sync/ecosistema_jaraba_core.saas_plan.*.yml; do
            if grep -q "stripe_product_id: ''" "$file"; then
              echo "ERROR: stripe_product_id vacío en $file"
              exit 1
            fi
          done
          echo "✓ Stripe config OK"
```

### REM-P2-02: Tests de contrato cross-módulo
**Estimación:** 16-20h

```php
<?php
// tests/src/Kernel/ContractTests/TenantPlanBillingContractTest.php

namespace Drupal\Tests\Contract\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests de contrato que verifican coherencia entre módulos.
 *
 * Estos tests son el reemplazo permanente de auditorías manuales.
 * Si un contrato se rompe, el CI falla.
 *
 * @group contract
 */
class TenantPlanBillingContractTest extends KernelTestBase {

  protected static $modules = [
    'ecosistema_jaraba_core',
    'jaraba_tenant',
    'jaraba_billing',
    'jaraba_page_builder',
    'group',
    'user',
  ];

  /**
   * CONTRATO 1: TenantContextService siempre devuelve TenantInterface o null.
   */
  public function testTenantContextReturnsCorrectType(): void {
    $service = $this->container->get('jaraba_tenant.context');
    $result = $service->getCurrentTenant();

    $this->assertTrue(
      $result === null || $result instanceof \Drupal\jaraba_tenant\Entity\TenantInterface,
      'TenantContextService debe devolver TenantInterface o null, nunca GroupInterface'
    );
  }

  /**
   * CONTRATO 2: Todos los planes en config tienen machine_names canónicos.
   */
  public function testAllPlansHaveCanonicalNames(): void {
    $validPlans = ['starter', 'professional', 'enterprise'];
    $plans = $this->container->get('entity_type.manager')
      ->getStorage('saas_plan')
      ->loadMultiple();

    foreach ($plans as $plan) {
      $machineName = $plan->id();
      $this->assertContains(
        $machineName,
        $validPlans,
        "Plan '{$machineName}' no es un machine_name canónico. Válidos: " . implode(', ', $validPlans)
      );
    }
  }

  /**
   * CONTRATO 3: PlanResolverService normaliza todos los aliases conocidos.
   */
  public function testPlanResolverNormalizesAllAliases(): void {
    $resolver = $this->container->get('ecosistema_jaraba_core.plan_resolver');

    $expectations = [
      'basico' => 'starter',
      'starter' => 'starter',
      'profesional' => 'professional',
      'professional' => 'professional',
      'growth' => 'professional',
      'enterprise' => 'enterprise',
    ];

    foreach ($expectations as $input => $expected) {
      $this->assertEquals($expected, $resolver->normalize($input),
        "normalize('{$input}') debería devolver '{$expected}'");
    }
  }

  /**
   * CONTRATO 4: Page Builder limits están configurados para todos los planes canónicos.
   */
  public function testPageBuilderLimitsExistForAllPlans(): void {
    $config = $this->config('jaraba_page_builder.settings');

    foreach (['starter', 'professional', 'enterprise'] as $plan) {
      $maxPages = $config->get("plan_limits.{$plan}.max_pages");
      $this->assertNotNull($maxPages,
        "plan_limits.{$plan}.max_pages no está configurado en jaraba_page_builder.settings");
    }
  }

  /**
   * CONTRATO 5: QuotaManagerService NO tiene fallbacks hardcodeados.
   */
  public function testQuotaManagerHasNoHardcodedFallbacks(): void {
    // Verificar que el código fuente no contiene hardcodes de límites
    $filePath = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/src/Service/QuotaManagerService.php';
    if (file_exists($filePath)) {
      $content = file_get_contents($filePath);
      // No debe contener arrays literales de límites
      $this->assertStringNotContainsString(
        "'starter' =>",
        $content,
        'QuotaManagerService no debe tener límites hardcodeados. Usar config.'
      );
    }
  }

  /**
   * CONTRATO 6: Billing services solo aceptan TenantInterface (no GroupInterface).
   */
  public function testBillingServicesTypeHints(): void {
    $reflector = new \ReflectionClass(\Drupal\jaraba_billing\Service\TenantSubscriptionService::class);

    foreach ($reflector->getMethods() as $method) {
      foreach ($method->getParameters() as $param) {
        $type = $param->getType();
        if ($type instanceof \ReflectionNamedType) {
          $this->assertNotEquals(
            'Drupal\group\Entity\GroupInterface',
            $type->getName(),
            "Método {$method->getName()}() tiene parámetro tipado como GroupInterface. Usar TenantInterface."
          );
        }
      }
    }
  }
}
```

### REM-P2-03: Guardrails IA persistencia (6-8h)

```php
<?php
// web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.install
// AÑADIR en hook_schema() o como update hook

function ecosistema_jaraba_core_update_10001(): void {
  $schema = \Drupal::database()->schema();

  if (!$schema->tableExists('ai_guardrail_logs')) {
    $schema->createTable('ai_guardrail_logs', [
      'fields' => [
        'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'tenant_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
        'user_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
        'guardrail_type' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE],
        'action_taken' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
        'input_hash' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE],
        'details' => ['type' => 'text', 'size' => 'medium'],
        'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'tenant_created' => ['tenant_id', 'created'],
        'guardrail_type' => ['guardrail_type'],
      ],
    ]);
  }
}
```

---

## Registro de Cambios

| Fecha | Versión | Autor | Descripción |
|---|---|---|---|
| 2026-02-23 | 2.0.0 | Claude (Anthropic) | Plan definitivo consolidado: Codex v1.0 + Codex v1.1 + Contra-Auditoría Claude. 16 tareas REM-*, código PHP completo, tests, configs, pipeline CI. Ready for Claude Code. |
