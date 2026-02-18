<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_billing\Service\FeatureAccessService;
use Drupal\jaraba_billing\Service\StripeSubscriptionService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller para add-ons — spec 158 §5.4.
 *
 * 6 endpoints para gestión de add-ons de suscripción.
 */
class AddonApiController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected FeatureAccessService $featureAccess,
    protected StripeSubscriptionService $stripeSubscription,
    protected TenantSubscriptionService $tenantSubscription,
    protected StripeConnectService $stripeConnect,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_billing.feature_access'),
      $container->get('jaraba_billing.stripe_subscription'),
      $container->get('jaraba_billing.tenant_subscription'),
      $container->get('jaraba_foc.stripe_connect'),
      $container->get('logger.channel.jaraba_billing'),
    );
  }

  /**
   * Obtiene el tenant_id del usuario actual.
   */
  protected function getCurrentTenantId(): ?int {
    $user = $this->currentUser();
    if (!$user || $user->isAnonymous()) {
      return NULL;
    }
    $userEntity = $this->entityTypeManager()->getStorage('user')->load($user->id());
    if ($userEntity && $userEntity->hasField('field_tenant') && !$userEntity->get('field_tenant')->isEmpty()) {
      return (int) $userEntity->get('field_tenant')->target_id;
    }
    return NULL;
  }

  /**
   * GET /api/v1/subscription — Suscripción actual con add-ons.
   */
  public function getSubscription(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $tenant = $this->entityTypeManager()->getStorage('group')->load($tenantId);
      if (!$tenant) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 404);
      }

      $activeAddons = $this->featureAccess->getActiveAddons($tenantId);

      // Load addon details.
      $addonDetails = [];
      $storage = $this->entityTypeManager()->getStorage('tenant_addon');
      $entities = $storage->loadByProperties([
        'tenant_id' => $tenantId,
        'status' => 'active',
      ]);
      foreach ($entities as $entity) {
        $addonDetails[] = [
          'code' => $entity->get('addon_code')->value,
          'price' => (float) $entity->get('price')->value,
          'activated_at' => $entity->get('activated_at')->value,
          'stripe_subscription_item_id' => $entity->get('stripe_subscription_item_id')->value,
        ];
      }

      $data = [
        'tenant_id' => $tenantId,
        'status' => $tenant->get('subscription_status')->value ?? 'none',
        'plan' => $tenant->get('subscription_plan')->value ?? NULL,
        'trial_ends' => $tenant->get('trial_ends')->value ?? NULL,
        'addons' => $addonDetails,
        'addon_codes' => $activeAddons,
      ];

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting subscription with addons: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/subscription/addons/available — Add-ons disponibles.
   */
  public function getAvailableAddons(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $available = $this->featureAccess->getAvailableAddons($tenantId);
      return new JsonResponse(['success' => TRUE, 'data' => $available]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting available addons: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/subscription/addons — Activar un add-on.
   */
  public function activateAddon(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $addonCode = $body['addon_code'] ?? NULL;
    $priceId = $body['price_id'] ?? NULL;

    if (!$addonCode) {
      return new JsonResponse(['success' => FALSE, 'error' => 'addon_code is required'], 400);
    }

    // Check not already active.
    if ($this->featureAccess->hasActiveAddon($tenantId, $addonCode)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Add-on already active'], 409);
    }

    try {
      $stripeItemId = NULL;
      $price = (float) ($body['price'] ?? 0);

      // If price_id provided, add to Stripe subscription.
      if ($priceId && !empty($body['subscription_id'])) {
        $result = $this->stripeConnect->stripeRequest('POST', '/subscription_items', [
          'subscription' => $body['subscription_id'],
          'price' => $priceId,
        ]);
        $stripeItemId = $result['id'] ?? NULL;
      }

      // Create local TenantAddon entity.
      $storage = $this->entityTypeManager()->getStorage('tenant_addon');
      $addon = $storage->create([
        'tenant_id' => $tenantId,
        'addon_code' => $addonCode,
        'stripe_subscription_item_id' => $stripeItemId,
        'price' => $price,
        'status' => 'active',
        'activated_at' => time(),
      ]);
      $addon->save();

      $this->logger->info('Add-on @code activated for tenant @tenant', [
        '@code' => $addonCode,
        '@tenant' => $tenantId,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'addon_id' => (int) $addon->id(),
        'addon_code' => $addonCode,
        'status' => 'active',
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error activating addon: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
    }
  }

  /**
   * DELETE /api/v1/subscription/addons/{code} — Cancelar un add-on.
   */
  public function cancelAddon(string $code): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('tenant_addon');
      $entities = $storage->loadByProperties([
        'tenant_id' => $tenantId,
        'addon_code' => $code,
        'status' => 'active',
      ]);

      if (empty($entities)) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Add-on not found or not active'], 404);
      }

      $addon = reset($entities);

      // Cancel in Stripe if applicable.
      $stripeItemId = $addon->get('stripe_subscription_item_id')->value;
      if ($stripeItemId) {
        try {
          $this->stripeConnect->stripeRequest('DELETE', '/subscription_items/' . $stripeItemId, [
            'proration_behavior' => 'create_prorations',
          ]);
        }
        catch (\Exception $e) {
          $this->logger->warning('Error canceling Stripe subscription item @item: @error', [
            '@item' => $stripeItemId,
            '@error' => $e->getMessage(),
          ]);
        }
      }

      // Update local entity.
      $addon->set('status', 'canceled');
      $addon->set('canceled_at', time());
      $addon->save();

      $this->logger->info('Add-on @code canceled for tenant @tenant', [
        '@code' => $code,
        '@tenant' => $tenantId,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'addon_code' => $code,
        'status' => 'canceled',
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error canceling addon: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
    }
  }

  /**
   * POST /api/v1/subscription/upgrade — Upgrade de plan base.
   */
  public function upgradePlan(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $newPriceId = $body['price_id'] ?? NULL;
    $subscriptionId = $body['subscription_id'] ?? NULL;

    if (!$newPriceId || !$subscriptionId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'price_id and subscription_id are required'], 400);
    }

    try {
      $result = $this->stripeSubscription->updateSubscription($subscriptionId, $newPriceId);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'subscription_id' => $result['id'],
        'status' => $result['status'],
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error upgrading plan: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
    }
  }

  /**
   * GET /api/v1/subscription/invoice/upcoming — Preview de próxima factura.
   */
  public function getUpcomingInvoice(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      // Look up Stripe customer ID.
      $customerStorage = $this->entityTypeManager()->getStorage('billing_customer');
      $customers = $customerStorage->loadByProperties(['tenant_id' => $tenantId]);

      if (empty($customers)) {
        return new JsonResponse(['success' => FALSE, 'error' => 'No billing customer found'], 404);
      }

      $customer = reset($customers);
      $stripeCustomerId = $customer->get('stripe_customer_id')->value;

      $upcoming = $this->stripeConnect->stripeRequest('GET', '/invoices/upcoming', [
        'customer' => $stripeCustomerId,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'amount_due' => ($upcoming['amount_due'] ?? 0) / 100,
        'subtotal' => ($upcoming['subtotal'] ?? 0) / 100,
        'tax' => ($upcoming['tax'] ?? 0) / 100,
        'total' => ($upcoming['total'] ?? 0) / 100,
        'currency' => strtoupper($upcoming['currency'] ?? 'EUR'),
        'period_start' => $upcoming['period_start'] ?? NULL,
        'period_end' => $upcoming['period_end'] ?? NULL,
        'lines' => array_map(function ($line) {
          return [
            'description' => $line['description'] ?? '',
            'amount' => ($line['amount'] ?? 0) / 100,
            'quantity' => $line['quantity'] ?? 1,
          ];
        }, $upcoming['lines']['data'] ?? []),
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting upcoming invoice: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
    }
  }

}
