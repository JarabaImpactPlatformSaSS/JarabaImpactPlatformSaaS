<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_billing\Service\StripeCustomerService;
use Drupal\jaraba_billing\Service\StripeInvoiceService;
use Drupal\jaraba_billing\Service\StripeSubscriptionService;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller para billing — spec 134 §9.
 *
 * 13 endpoints para gestión de suscripciones, facturas,
 * métodos de pago y datos de facturación.
 */
class BillingApiController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected StripeCustomerService $stripeCustomer,
    protected StripeSubscriptionService $stripeSubscription,
    protected StripeInvoiceService $stripeInvoice,
    protected TenantSubscriptionService $tenantSubscription,
    protected TenantMeteringService $metering,
    protected StripeConnectService $stripeConnect,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_billing.stripe_customer'),
      $container->get('jaraba_billing.stripe_subscription'),
      $container->get('jaraba_billing.stripe_invoice'),
      $container->get('jaraba_billing.tenant_subscription'),
      $container->get('jaraba_billing.tenant_metering'),
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
   * GET /api/v1/billing/subscription — Suscripción actual del tenant.
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

      $data = [
        'tenant_id' => $tenantId,
        'status' => $tenant->get('subscription_status')->value ?? 'none',
        'plan' => $tenant->get('subscription_plan')->value ?? NULL,
        'trial_ends' => $tenant->get('trial_ends')->value ?? NULL,
        'cancel_at' => $tenant->get('cancel_at')->value ?? NULL,
      ];

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting subscription: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/billing/subscription — Crear suscripción.
   */
  public function createSubscription(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $priceId = $body['price_id'] ?? NULL;
    $paymentMethodId = $body['payment_method_id'] ?? NULL;

    if (!$priceId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'price_id is required'], 400);
    }

    try {
      $tenant = $this->entityTypeManager()->getStorage('group')->load($tenantId);
      if (!$tenant) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 404);
      }

      // Get or create Stripe customer.
      $customer = $this->stripeCustomer->getByTenantId($tenantId);
      if (!$customer) {
        $billingEmail = $body['email'] ?? $this->currentUser()->getEmail();
        $billingName = $body['name'] ?? $tenant->label();
        $stripeCustomer = $this->stripeCustomer->createOrGetCustomer($tenantId, $billingEmail, $billingName);
        $customerId = $stripeCustomer['id'];
      }
      else {
        $customerId = $customer['stripe_customer_id'];
      }

      $options = [
        'metadata' => ['tenant_id' => (string) $tenantId],
      ];
      if ($paymentMethodId) {
        $options['default_payment_method'] = $paymentMethodId;
      }

      $subscription = $this->stripeSubscription->createSubscription($customerId, $priceId, $options);

      // Activate locally.
      $this->tenantSubscription->activateSubscription($tenant);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'subscription_id' => $subscription['id'],
        'status' => $subscription['status'],
        'current_period_end' => $subscription['current_period_end'] ?? NULL,
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating subscription: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * PUT /api/v1/billing/subscription/plan — Cambiar plan.
   */
  public function changePlan(Request $request): JsonResponse {
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
      $this->logger->error('Error changing plan: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * DELETE /api/v1/billing/subscription — Cancelar suscripción.
   */
  public function cancelSubscription(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $subscriptionId = $body['subscription_id'] ?? NULL;
    $immediately = (bool) ($body['immediately'] ?? FALSE);

    if (!$subscriptionId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'subscription_id is required'], 400);
    }

    try {
      $result = $this->stripeSubscription->cancelSubscription($subscriptionId, $immediately);

      $tenant = $this->entityTypeManager()->getStorage('group')->load($tenantId);
      if ($tenant) {
        $this->tenantSubscription->cancelSubscription($tenant, $immediately);
      }

      return new JsonResponse(['success' => TRUE, 'data' => [
        'subscription_id' => $result['id'],
        'cancel_at_period_end' => $result['cancel_at_period_end'] ?? FALSE,
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error canceling subscription: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/billing/subscription/reactivate — Reactivar suscripción.
   */
  public function reactivateSubscription(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $subscriptionId = $body['subscription_id'] ?? NULL;

    if (!$subscriptionId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'subscription_id is required'], 400);
    }

    try {
      $result = $this->stripeSubscription->resumeSubscription($subscriptionId);

      $tenant = $this->entityTypeManager()->getStorage('group')->load($tenantId);
      if ($tenant) {
        $this->tenantSubscription->activateSubscription($tenant);
      }

      return new JsonResponse(['success' => TRUE, 'data' => [
        'subscription_id' => $result['id'],
        'status' => $result['status'],
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error reactivating subscription: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/billing/invoices — Listar facturas del tenant.
   */
  public function listInvoices(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('billing_invoice');
      $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);

      $invoices = [];
      foreach ($entities as $entity) {
        $invoices[] = [
          'id' => (int) $entity->id(),
          'invoice_number' => $entity->get('invoice_number')->value,
          'status' => $entity->get('status')->value,
          'amount_due' => (float) $entity->get('amount_due')->value,
          'subtotal' => (float) ($entity->get('subtotal')->value ?? 0),
          'tax' => (float) ($entity->get('tax')->value ?? 0),
          'total' => (float) ($entity->get('total')->value ?? 0),
          'currency' => $entity->get('currency')->value,
          'billing_reason' => $entity->get('billing_reason')->value ?? NULL,
          'due_date' => $entity->get('due_date')->value,
          'paid_at' => $entity->get('paid_at')->value,
          'pdf_url' => $entity->get('pdf_url')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $invoices]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing invoices: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/billing/invoices/{id}/pdf — URL del PDF de factura.
   */
  public function getInvoicePdf(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('billing_invoice')->load($id);
      if (!$entity || (int) $entity->get('tenant_id')->target_id !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Invoice not found'], 404);
      }

      $pdfUrl = $entity->get('pdf_url')->value;
      if (!$pdfUrl) {
        // Try to fetch from Stripe.
        $stripeId = $entity->get('stripe_invoice_id')->value;
        if ($stripeId) {
          $pdfUrl = $this->stripeInvoice->getInvoicePdf($stripeId);
        }
      }

      return new JsonResponse(['success' => TRUE, 'data' => ['pdf_url' => $pdfUrl]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting invoice PDF: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/billing/usage — Uso actual del periodo.
   */
  public function getUsage(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $usage = $this->metering->getUsage((string) $tenantId);
      return new JsonResponse(['success' => TRUE, 'data' => $usage]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting usage: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/billing/portal-session — Crear sesión de Stripe Customer Portal.
   */
  public function createPortalSession(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $customer = $this->stripeCustomer->getByTenantId($tenantId);
      if (!$customer) {
        return new JsonResponse(['success' => FALSE, 'error' => 'No billing customer found'], 404);
      }

      $body = json_decode($request->getContent(), TRUE) ?? [];
      $returnUrl = $body['return_url'] ?? '/billing';

      $session = $this->stripeConnect->stripeRequest('POST', '/billing_portal/sessions', [
        'customer' => $customer['stripe_customer_id'],
        'return_url' => $returnUrl,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'url' => $session['url'] ?? NULL,
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating portal session: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/billing/payment-methods — Listar métodos de pago.
   */
  public function listPaymentMethods(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('billing_payment_method');
      $entities = $storage->loadByProperties([
        'tenant_id' => $tenantId,
        'status' => 'active',
      ]);

      $methods = [];
      foreach ($entities as $entity) {
        $methods[] = [
          'id' => $entity->get('stripe_payment_method_id')->value,
          'type' => $entity->get('type')->value,
          'card_brand' => $entity->get('card_brand')->value,
          'card_last4' => $entity->get('card_last4')->value,
          'card_exp_month' => $entity->get('card_exp_month')->value,
          'card_exp_year' => $entity->get('card_exp_year')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $methods]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing payment methods: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/billing/payment-methods — Añadir método de pago.
   */
  public function addPaymentMethod(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $paymentMethodId = $body['payment_method_id'] ?? NULL;

    if (!$paymentMethodId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'payment_method_id is required'], 400);
    }

    try {
      $customer = $this->stripeCustomer->getByTenantId($tenantId);
      if (!$customer) {
        return new JsonResponse(['success' => FALSE, 'error' => 'No billing customer found'], 404);
      }

      $result = $this->stripeCustomer->attachPaymentMethod($paymentMethodId, $customer['stripe_customer_id']);

      // Set as default if requested.
      if (!empty($body['set_default'])) {
        $this->stripeCustomer->setDefaultPaymentMethod($customer['stripe_customer_id'], $paymentMethodId);
      }

      return new JsonResponse(['success' => TRUE, 'data' => [
        'payment_method_id' => $result['id'],
        'type' => $result['type'] ?? 'card',
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error adding payment method: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * DELETE /api/v1/billing/payment-methods/{id} — Eliminar método de pago.
   */
  public function deletePaymentMethod(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $this->stripeCustomer->detachPaymentMethod($id);

      return new JsonResponse(['success' => TRUE, 'data' => ['detached' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error deleting payment method: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * PUT /api/v1/billing/customer — Actualizar datos de facturación.
   */
  public function updateCustomer(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];

    try {
      $storage = $this->entityTypeManager()->getStorage('billing_customer');
      $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);

      if (empty($entities)) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Billing customer not found'], 404);
      }

      $entity = reset($entities);

      // Update allowed fields.
      $allowedFields = ['billing_email', 'billing_name', 'tax_id', 'tax_id_type', 'billing_address'];
      foreach ($allowedFields as $field) {
        if (isset($body[$field])) {
          $entity->set($field, $body[$field]);
        }
      }
      $entity->save();

      // Sync to Stripe.
      $stripeCustomerId = $entity->get('stripe_customer_id')->value;
      if ($stripeCustomerId) {
        $stripeData = [];
        if (isset($body['billing_email'])) {
          $stripeData['email'] = $body['billing_email'];
        }
        if (isset($body['billing_name'])) {
          $stripeData['name'] = $body['billing_name'];
        }
        if (!empty($stripeData)) {
          $this->stripeCustomer->updateCustomer($stripeCustomerId, $stripeData);
        }
      }

      return new JsonResponse(['success' => TRUE, 'data' => ['updated' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error updating customer: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

}
