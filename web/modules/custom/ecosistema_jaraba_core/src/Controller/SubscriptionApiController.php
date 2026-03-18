<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST para gestión de suscripciones del tenant.
 *
 * Doc 158 §5.4, CSRF-API-001, ROUTE-LANGPREFIX-001.
 */
class SubscriptionApiController extends ControllerBase {

  /**
   * Servicio de contexto de suscripción.
   *
   * @var object|null
   */
  protected $subscriptionContext;

  /**
   * Servicio de compatibilidad de add-ons.
   *
   * @var object|null
   */
  protected $compatibilityService;

  /**
   * Servicio de contexto de tenant.
   *
   * @var object|null
   */
  protected $tenantContext;

  /**
   * Constructor.
   *
   * @param object|null $subscription_context
   *   Servicio de suscripción (opcional).
   * @param object|null $compatibility_service
   *   Servicio de compatibilidad (opcional).
   * @param object|null $tenant_context
   *   Servicio de contexto de tenant (opcional).
   */
  public function __construct(
    ?object $subscription_context,
    ?object $compatibility_service,
    ?object $tenant_context,
  ) {
    $this->subscriptionContext = $subscription_context;
    $this->compatibilityService = $compatibility_service;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    try {
      $subscriptionContext = $container->get('ecosistema_jaraba_core.subscription_context');
    }
    catch (\Throwable) {
      $subscriptionContext = NULL;
    }

    try {
      $compatibilityService = $container->get('jaraba_addons.addon_compatibility');
    }
    catch (\Throwable) {
      $compatibilityService = NULL;
    }

    try {
      $tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    }
    catch (\Throwable) {
      $tenantContext = NULL;
    }

    return new static($subscriptionContext, $compatibilityService, $tenantContext);
  }

  /**
   * GET /api/v1/subscription — Suscripción actual con add-ons.
   */
  public function getCurrentSubscription(): JsonResponse {
    if ($this->subscriptionContext === NULL) {
      return new JsonResponse(['error' => 'Subscription service unavailable'], 503);
    }

    $uid = $this->currentUser()->id();
    if ($uid === 0) {
      return new JsonResponse(['error' => 'Authentication required'], 401);
    }

    try {
      /** @var array<string, mixed> $context */
      $context = $this->subscriptionContext->getContextForUser($uid);
      if ($context === []) {
        return new JsonResponse(['plan' => NULL, 'message' => 'No subscription found'], 200);
      }
      return new JsonResponse($context, 200);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/subscription/addons/available — Add-ons disponibles.
   */
  public function getAvailableAddons(): JsonResponse {
    $uid = $this->currentUser()->id();
    if ($uid === 0) {
      return new JsonResponse(['error' => 'Authentication required'], 401);
    }

    try {
      $tenantVertical = $this->resolveTenantVertical();

      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager()->getStorage('addon');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->condition('addon_type', ['vertical'], 'NOT IN')
        ->sort('weight', 'ASC')
        ->execute();

      /** @var \Drupal\jaraba_addons\Entity\Addon[] $addons */
      $addons = $storage->loadMultiple($ids);

      $result = [];
      foreach ($addons as $addon) {
        $machineName = $addon->get('machine_name')->value ?? '';
        $level = 'available';
        if ($tenantVertical !== '' && $this->compatibilityService !== NULL) {
          $level = $this->compatibilityService->getRecommendationLevel($machineName, $tenantVertical);
        }

        $result[] = [
          'id' => (int) $addon->id(),
          'label' => $addon->label(),
          'machine_name' => $machineName,
          'addon_type' => $addon->get('addon_type')->value ?? 'feature',
          'price_monthly' => (float) ($addon->get('price_monthly')->value ?? 0),
          'price_yearly' => (float) ($addon->get('price_yearly')->value ?? 0),
          'recommendation_level' => $level,
          'features' => $addon->getFeaturesIncluded(),
        ];
      }

      usort($result, static function (array $a, array $b): int {
        $order = ['recommended' => 0, 'available' => 1, 'not_applicable' => 2];
        return ($order[$a['recommendation_level']] ?? 1) <=> ($order[$b['recommendation_level']] ?? 1);
      });

      return new JsonResponse([
        'addons' => $result,
        'tenant_vertical' => $tenantVertical,
        'count' => count($result),
      ], 200);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/subscription/upgrade — Upgrade de plan.
   */
  public function upgradeSubscription(Request $request): JsonResponse {
    $uid = $this->currentUser()->id();
    if ($uid === 0) {
      return new JsonResponse(['error' => 'Authentication required'], 401);
    }

    if ($this->subscriptionContext === NULL) {
      return new JsonResponse(['error' => 'Subscription service unavailable'], 503);
    }

    try {
      /** @var array<string, mixed> $context */
      $context = $this->subscriptionContext->getContextForUser($uid);
      $upgradeAvailable = (bool) ($context['upgrade']['available'] ?? FALSE);
      if ($upgradeAvailable === FALSE) {
        return new JsonResponse([
          'error' => 'No upgrade available',
          'current_tier' => $context['plan']['tier'] ?? 'unknown',
        ], 422);
      }

      $body = json_decode($request->getContent(), TRUE);
      $planId = $body['plan_id'] ?? NULL;

      $checkoutUrl = '';
      if ($planId !== NULL) {
        try {
          $checkoutUrl = Url::fromRoute('jaraba_billing.checkout', ['saas_plan' => $planId])->toString();
        }
        catch (\Throwable) {
          // Ruta no disponible.
        }
      }

      if ($checkoutUrl === '' && isset($context['upgrade']['checkout_url']) && $context['upgrade']['checkout_url'] !== '') {
        $checkoutUrl = $context['upgrade']['checkout_url'];
      }

      return new JsonResponse([
        'checkout_url' => $checkoutUrl,
        'next_tier' => $context['upgrade']['next_tier'] ?? '',
        'next_tier_label' => $context['upgrade']['next_tier_label'] ?? '',
        'next_price' => $context['upgrade']['next_tier_price'] ?? 0,
      ], 200);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/subscription/invoice/upcoming — Preview de próxima factura.
   */
  public function getUpcomingInvoice(): JsonResponse {
    $uid = $this->currentUser()->id();
    if ($uid === 0) {
      return new JsonResponse(['error' => 'Authentication required'], 401);
    }

    if ($this->subscriptionContext === NULL) {
      return new JsonResponse(['error' => 'Subscription service unavailable'], 503);
    }

    try {
      /** @var array<string, mixed> $context */
      $context = $this->subscriptionContext->getContextForUser($uid);
      $plan = $context['plan'] ?? NULL;
      if ($plan === NULL) {
        return new JsonResponse(['invoice' => NULL, 'message' => 'No active subscription'], 200);
      }

      $billing = $context['billing'] ?? [];
      $addons = $context['addons']['active'] ?? [];
      $totalMonthly = (float) ($billing['total_monthly'] ?? 0);

      $lineItems = [];
      $lineItems[] = [
        'description' => $context['plan']['name'] ?? 'Plan',
        'type' => 'plan',
        'amount' => $billing['plan_monthly'] ?? 0,
      ];
      foreach ($addons as $addon) {
        $lineItems[] = [
          'description' => $addon['label'] ?? 'Add-on',
          'type' => 'addon',
          'amount' => $addon['price'] ?? 0,
        ];
      }

      return new JsonResponse([
        'invoice' => [
          'line_items' => $lineItems,
          'subtotal' => $totalMonthly,
          'tax_rate' => 21,
          'tax_amount' => round($totalMonthly * 0.21, 2),
          'total' => round($totalMonthly * 1.21, 2),
          'currency' => 'EUR',
          'next_invoice_date' => $billing['next_invoice_date'] ?? NULL,
          'billing_cycle' => $billing['billing_cycle'] ?? 'monthly',
        ],
      ], 200);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['error' => 'Internal error'], 500);
    }
  }

  /**
   * Resuelve el vertical del tenant actual.
   */
  protected function resolveTenantVertical(): string {
    if ($this->tenantContext === NULL) {
      return '';
    }
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if (!$tenant instanceof ContentEntityInterface || !$tenant->hasField('vertical')) {
        return '';
      }
      $verticalEntity = $tenant->get('vertical')->entity;
      if (!$verticalEntity instanceof ContentEntityInterface) {
        return '';
      }
      return (string) ($verticalEntity->get('machine_name')->value ?? '');
    }
    catch (\Throwable) {
      return '';
    }
  }

}
