<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Drupal\jaraba_billing\Service\DunningService;
use Drupal\jaraba_billing\Service\FiscalInvoiceDelegationService;
use Drupal\jaraba_billing\Service\StripeInvoiceService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoint dedicado para webhooks de billing desde Stripe.
 *
 * Separado del StripeWebhookController de FOC para manejar
 * eventos específicos de billing y suscripciones.
 *
 * EVENTOS MANEJADOS:
 * - invoice.paid: Factura pagada → sincronizar BillingInvoice
 * - invoice.payment_failed: Fallo de pago → marcar tenant past_due
 * - invoice.finalized: Factura finalizada → sincronizar
 * - customer.subscription.updated: Cambio de suscripción
 * - customer.subscription.deleted: Cancelación de suscripción
 * - customer.subscription.trial_will_end: Aviso fin de trial
 * - payment_method.attached: Nuevo método de pago
 * - payment_method.detached: Método de pago eliminado
 *
 * SEGURIDAD: HMAC-SHA256 via StripeConnectService::verifyWebhookSignature().
 *
 * AUDIT-PERF-002: Usa LockBackendInterface para prevenir lost-updates
 * cuando webhooks concurrentes modifican el estado del mismo tenant.
 */
class BillingWebhookController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected StripeConnectService $stripeConnect,
    protected StripeInvoiceService $invoiceService,
    protected TenantSubscriptionService $tenantSubscription,
    protected LoggerInterface $billingLogger,
    protected LockBackendInterface $lock,
    protected ?DunningService $dunningService = NULL,
    protected ?MailManagerInterface $mailManager = NULL,
    protected ?PlanResolverService $planResolver = NULL,
    protected ?FiscalInvoiceDelegationService $fiscalDelegation = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_foc.stripe_connect'),
      $container->get('jaraba_billing.stripe_invoice'),
      $container->get('jaraba_billing.tenant_subscription'),
      $container->get('logger.channel.jaraba_billing'),
      $container->get('lock'),
      $container->get('jaraba_billing.dunning'),
      $container->get('plugin.manager.mail'),
      $container->has('ecosistema_jaraba_core.plan_resolver')
        ? $container->get('ecosistema_jaraba_core.plan_resolver')
        : NULL,
      $container->has('jaraba_billing.fiscal_delegation')
        ? $container->get('jaraba_billing.fiscal_delegation')
        : NULL,
    );
  }

  /**
   * Procesa webhooks entrantes de Stripe para billing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP entrante.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta HTTP.
   */
  public function handle(Request $request): Response {
    $payload = $request->getContent();
    $sigHeader = $request->headers->get('Stripe-Signature', '');

    // Verificación de firma HMAC-SHA256.
    if (!$this->stripeConnect->verifyWebhookSignature($payload, $sigHeader)) {
      $this->billingLogger->warning('Billing webhook rechazado: firma inválida.');
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid signature']], 400);
    }

    $event = json_decode($payload, TRUE);
    if (!$event || !isset($event['type'])) {
      $this->billingLogger->warning('Billing webhook rechazado: payload inválido.');
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid payload']], 400);
    }

    $eventType = $event['type'];
    $eventId = $event['id'] ?? '';
    $eventData = $event['data']['object'] ?? [];

    // GAP-M04: Deduplication — skip already-processed events.
    if ($eventId && $this->isDuplicateEvent($eventId)) {
      $this->billingLogger->debug('Billing webhook duplicate skipped: @id (@type)', [
        '@id' => $eventId,
        '@type' => $eventType,
      ]);
      return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'already_processed'], 'meta' => ['timestamp' => time()]]);
    }

    $this->billingLogger->info('Billing webhook recibido: @type', ['@type' => $eventType]);

    try {
      $result = match ($eventType) {
        'invoice.paid' => $this->handleInvoicePaid($eventData),
        'invoice.payment_failed' => $this->handleInvoicePaymentFailed($eventData),
        'invoice.finalized' => $this->handleInvoiceFinalized($eventData),
        'invoice.updated' => $this->handleInvoiceUpdated($eventData),
        'customer.subscription.created' => $this->handleSubscriptionCreated($eventData),
        'customer.subscription.updated' => $this->handleSubscriptionUpdated($eventData),
        'customer.subscription.deleted' => $this->handleSubscriptionDeleted($eventData),
        'customer.subscription.trial_will_end' => $this->handleTrialWillEnd($eventData),
        'checkout.session.completed' => $this->handleCheckoutSessionCompleted($eventData),
        'payment_method.attached' => $this->handlePaymentMethodAttached($eventData),
        'payment_method.detached' => $this->handlePaymentMethodDetached($eventData),
        default => $this->handleUnknownEvent($eventType),
      };

      // GAP-M04: Log processed event for deduplication.
      if ($eventId) {
        $this->logProcessedEvent($eventId, $eventType);
      }

      return $result;
    }
    catch (\Throwable $e) {
      $this->billingLogger->error('Error procesando billing webhook @type: @error', [
        '@type' => $eventType,
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Processing failed']], 500);
    }
  }

  /**
   * Factura pagada: sincronizar BillingInvoice local + delegacion fiscal.
   *
   * GAP-C01: Tras sincronizar la factura localmente, invoca
   * FiscalInvoiceDelegationService para registrar en VeriFactu
   * (obligatorio RD 1007/2023) y delegar a Facturae B2G o E-Factura B2B
   * segun el tipo de destinatario.
   */
  protected function handleInvoicePaid(array $data): JsonResponse {
    $invoiceEntity = $this->invoiceService->syncInvoice($data);

    // GAP-C01: Delegacion fiscal — VeriFactu + Facturae/E-Factura.
    if ($invoiceEntity && $this->fiscalDelegation) {
      try {
        $this->fiscalDelegation->processFinalizedInvoice($invoiceEntity);
        $this->billingLogger->info('Fiscal delegation completed for invoice @id', [
          '@id' => $invoiceEntity->id(),
        ]);
      }
      catch (\Throwable $e) {
        // Log but do NOT block the webhook — invoice is already paid.
        $this->billingLogger->error('Fiscal delegation failed for invoice @id: @msg', [
          '@id' => $invoiceEntity->id(),
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->info('Invoice pagada sincronizada: @id', [
      '@id' => $data['id'] ?? 'unknown',
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Fallo de pago: marcar tenant como past_due.
   *
   * AUDIT-PERF-002: Lock por tenant para prevenir lost-updates.
   */
  protected function handleInvoicePaymentFailed(array $data): JsonResponse {
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      $lockId = 'jaraba_billing:webhook_tenant:' . $tenantId;
      if (!$this->lock->acquire($lockId, 30)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'tenant locked'], 503);
      }

      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in payment_failed webhook', ['@id' => $tenantId]);
        }
        elseif ($tenant) {
          $this->tenantSubscription->markPastDue($tenant);
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error marcando tenant @id past_due: @error', [
          '@id' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    // Sincronizar la factura con estado fallido.
    $this->invoiceService->syncInvoice($data, $tenantId ?: NULL);

    // Enviar email de notificación de fallo de pago.
    if ($tenantId && $this->mailManager) {
      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $loadedTenant = $tenantStorage->load($tenantId);
        $customerStorage = $this->entityTypeManager()->getStorage('billing_customer');
        $customers = $customerStorage->loadByProperties(['tenant_id' => $tenantId]);
        $customer = !empty($customers) ? reset($customers) : NULL;
        $billingEmail = $customer ? $customer->get('billing_email')->value : NULL;

        if ($billingEmail && $loadedTenant) {
          $this->mailManager->mail('jaraba_billing', 'payment_failed', $billingEmail, 'es', [
            'tenant_label' => $loadedTenant->label(),
            'invoice_id' => $data['id'] ?? '',
          ]);
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error sending payment_failed email for tenant @tenant: @error', [
          '@tenant' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->warning('Pago fallido en invoice @id, tenant @tenant', [
      '@id' => $data['id'] ?? 'unknown',
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Factura finalizada: sincronizar.
   */
  protected function handleInvoiceFinalized(array $data): JsonResponse {
    $this->invoiceService->syncInvoice($data);

    $this->billingLogger->info('Invoice finalizada sincronizada: @id', [
      '@id' => $data['id'] ?? 'unknown',
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GAP-PRORATION: Invoice updated — sync proration line items.
   *
   * Triggered when Stripe updates an invoice (e.g., adding proration
   * line items after a mid-cycle plan change).
   */
  protected function handleInvoiceUpdated(array $data): JsonResponse {
    $this->invoiceService->syncInvoice($data);

    // Log proration-specific info.
    $hasProration = FALSE;
    foreach (($data['lines']['data'] ?? []) as $line) {
      if (!empty($line['proration'])) {
        $hasProration = TRUE;
        break;
      }
    }

    $this->billingLogger->info('Invoice updated sincronizada: @id (proration: @proration)', [
      '@id' => $data['id'] ?? 'unknown',
      '@proration' => $hasProration ? 'yes' : 'no',
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Suscripción actualizada: sincroniza estado del tenant local.
   *
   * AUDIT-PERF-002: Lock por tenant para prevenir lost-updates cuando
   * múltiples webhooks de Stripe modifican el estado del mismo tenant.
   */
  protected function handleSubscriptionUpdated(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';
    $status = $data['status'] ?? 'unknown';
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      $lockId = 'jaraba_billing:webhook_tenant:' . $tenantId;
      if (!$this->lock->acquire($lockId, 30)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'tenant locked'], 503);
      }

      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in subscription_updated webhook', ['@id' => $tenantId]);
          $tenant = NULL;
        }
        if ($tenant) {
          switch ($status) {
            case 'active':
              $this->tenantSubscription->activateSubscription($tenant);
              // Stop dunning if payment recovered.
              if ($this->dunningService && $this->dunningService->isInDunning($tenantId)) {
                $this->dunningService->stopDunning($tenantId);
              }
              break;

            case 'past_due':
              $this->tenantSubscription->markPastDue($tenant);
              // Start dunning process.
              if ($this->dunningService) {
                $this->dunningService->startDunning($tenantId);
              }
              break;

            case 'canceled':
              $this->tenantSubscription->cancelSubscription($tenant, TRUE);
              break;

            case 'trialing':
              // No change for trialing status.
              break;
          }

          // If plan changed (items differ), update subscription_plan.
          // Use PlanResolver to normalize tier from Stripe Price ID when available.
          $priceId = $data['items']['data'][0]['price']['id'] ?? NULL;
          $planId = $data['items']['data'][0]['price']['product'] ?? NULL;
          if ($planId) {
            $resolvedTier = NULL;
            if ($this->planResolver && $priceId) {
              $resolvedTier = $this->planResolver->resolveFromStripePriceId($priceId);
            }
            $tenant->set('subscription_plan', $resolvedTier ?? $planId);
            $tenant->save();
          }
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error sincronizando suscripción @id para tenant @tenant: @error', [
          '@id' => $subscriptionId,
          '@tenant' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    $this->billingLogger->info('Suscripción actualizada: @id, estado: @status, tenant: @tenant', [
      '@id' => $subscriptionId,
      '@status' => $status,
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Suscripción eliminada/cancelada.
   *
   * AUDIT-PERF-002: Lock por tenant para prevenir lost-updates.
   */
  protected function handleSubscriptionDeleted(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      $lockId = 'jaraba_billing:webhook_tenant:' . $tenantId;
      if (!$this->lock->acquire($lockId, 30)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'tenant locked'], 503);
      }

      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in subscription_deleted webhook', ['@id' => $tenantId]);
        }
        elseif ($tenant) {
          $this->tenantSubscription->cancelSubscription($tenant, TRUE);
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error cancelando tenant @id: @error', [
          '@id' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    $this->billingLogger->info('Suscripción eliminada: @id, tenant: @tenant', [
      '@id' => $subscriptionId,
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Trial a punto de terminar: notifica al tenant.
   */
  protected function handleTrialWillEnd(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';
    $trialEnd = $data['trial_end'] ?? NULL;
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);

    if ($tenantId) {
      try {
        $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->load($tenantId);
        if (!$tenant instanceof TenantInterface) {
          $this->billingLogger->error('Tenant @id not found or invalid type in trial_will_end webhook', ['@id' => $tenantId]);
          $tenant = NULL;
        }

        if ($tenant && $this->mailManager) {
          // Look up billing email for the tenant.
          $customerStorage = $this->entityTypeManager()->getStorage('billing_customer');
          $customers = $customerStorage->loadByProperties(['tenant_id' => $tenantId]);
          $customer = !empty($customers) ? reset($customers) : NULL;

          $to = $customer ? $customer->get('billing_email')->value : NULL;
          if ($to) {
            $params = [
              'tenant_label' => $tenant->label(),
              'trial_end' => $trialEnd ? date('d/m/Y', (int) $trialEnd) : 'pronto',
              'subscription_id' => $subscriptionId,
            ];
            $this->mailManager->mail('jaraba_billing', 'trial_will_end', $to, 'es', $params);
          }
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error notificando fin de trial para tenant @tenant: @error', [
          '@tenant' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->info('Trial terminará pronto: suscripción @id, fin: @end, tenant: @tenant', [
      '@id' => $subscriptionId,
      '@end' => $trialEnd ? date('Y-m-d', (int) $trialEnd) : 'unknown',
      '@tenant' => $tenantId,
    ]);

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Método de pago vinculado.
   */
  protected function handlePaymentMethodAttached(array $data): JsonResponse {
    $pmId = $data['id'] ?? '';
    $customerId = $data['customer'] ?? '';

    if ($pmId && $customerId) {
      try {
        $storage = $this->entityTypeManager()->getStorage('billing_payment_method');

        // Buscar tenant_id a partir del customer.
        $tenantId = NULL;
        $existingByCustomer = $storage->loadByProperties([
          'stripe_customer_id' => $customerId,
        ]);
        if (!empty($existingByCustomer)) {
          $sample = reset($existingByCustomer);
          $tenantId = $sample->get('tenant_id')->target_id;
        }

        $values = [
          'tenant_id' => $tenantId,
          'stripe_payment_method_id' => $pmId,
          'stripe_customer_id' => $customerId,
          'type' => $data['type'] ?? 'card',
          'card_brand' => $data['card']['brand'] ?? NULL,
          'card_last4' => $data['card']['last4'] ?? NULL,
          'card_exp_month' => $data['card']['exp_month'] ?? NULL,
          'card_exp_year' => $data['card']['exp_year'] ?? NULL,
          'status' => 'active',
        ];

        $entity = $storage->create($values);
        $entity->save();
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error creando payment method @pm: @error', [
          '@pm' => $pmId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->info('Payment method vinculado: @pm', ['@pm' => $pmId]);
    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Método de pago desvinculado.
   */
  protected function handlePaymentMethodDetached(array $data): JsonResponse {
    $pmId = $data['id'] ?? '';

    if ($pmId) {
      try {
        $storage = $this->entityTypeManager()->getStorage('billing_payment_method');
        $existing = $storage->loadByProperties([
          'stripe_payment_method_id' => $pmId,
        ]);
        if (!empty($existing)) {
          $entity = reset($existing);
          $entity->set('status', 'detached');
          $entity->save();
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error marcando payment method @pm como detached: @error', [
          '@pm' => $pmId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->billingLogger->info('Payment method desvinculado: @pm', ['@pm' => $pmId]);
    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Subscription created: auto-provision tenant if not yet provisioned (S2-03).
   *
   * Triggered when Stripe creates a subscription (e.g., via Checkout Session
   * or API). If tenant_id is in metadata and already exists, activates it.
   * If no tenant exists yet, delegates to TenantOnboardingService for
   * automated provisioning.
   *
   * IDEMPOTENCY: Checks if tenant already exists before creating.
   * AUDIT-PERF-002: Lock per customer to prevent duplicate provisioning.
   */
  protected function handleSubscriptionCreated(array $data): JsonResponse {
    $subscriptionId = $data['id'] ?? '';
    $status = $data['status'] ?? 'unknown';
    $tenantId = (int) ($data['metadata']['tenant_id'] ?? 0);
    $customerId = $data['customer'] ?? '';

    $this->billingLogger->info('Subscription created: @id, status: @status, customer: @customer, tenant: @tenant', [
      '@id' => $subscriptionId,
      '@status' => $status,
      '@customer' => $customerId,
      '@tenant' => $tenantId,
    ]);

    // If tenant already exists, just activate/update.
    if ($tenantId > 0) {
      $lockId = 'jaraba_billing:provision_tenant:' . $tenantId;
      if (!$this->lock->acquire($lockId, 30)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'tenant locked'], 503);
      }

      try {
        $tenant = $this->entityTypeManager()->getStorage('tenant')->load($tenantId);
        if ($tenant instanceof TenantInterface) {
          if ($status === 'active') {
            $this->tenantSubscription->activateSubscription($tenant);
          }
          // Store Stripe IDs.
          if ($tenant->hasField('stripe_subscription_id')) {
            $tenant->set('stripe_subscription_id', $subscriptionId);
          }
          if ($customerId && $tenant->hasField('stripe_customer_id')) {
            $tenant->set('stripe_customer_id', $customerId);
          }
          $tenant->save();
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Error activating tenant @id on subscription.created: @error', [
          '@id' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Checkout session completed: auto-provision new tenant (S2-03).
   *
   * This is the primary auto-provisioning entry point. When a new customer
   * completes Stripe Checkout, this handler:
   * 1. Extracts customer email and subscription from the session.
   * 2. Checks if a tenant already exists for this customer (idempotency).
   * 3. If not, delegates to TenantOnboardingService for full provisioning.
   *
   * Requires metadata in the Checkout Session:
   * - plan_tier: The plan to assign (starter, professional, business, enterprise).
   * - vertical: The vertical for the tenant.
   *
   * AUDIT-PERF-002: Lock per customer_email to prevent duplicate provisioning.
   */
  protected function handleCheckoutSessionCompleted(array $data): JsonResponse {
    $sessionId = $data['id'] ?? '';
    $customerEmail = $data['customer_details']['email'] ?? $data['customer_email'] ?? '';
    $customerId = $data['customer'] ?? '';
    $subscriptionId = $data['subscription'] ?? '';
    $mode = $data['mode'] ?? '';
    $metadata = $data['metadata'] ?? [];

    // Only process subscription checkouts.
    if ($mode !== 'subscription' || empty($subscriptionId)) {
      $this->billingLogger->info('Checkout session @id ignored (mode: @mode).', [
        '@id' => $sessionId,
        '@mode' => $mode,
      ]);
      return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'ignored_mode'], 'meta' => ['timestamp' => time()]]);
    }

    $this->billingLogger->info('Auto-provision: checkout.session.completed @id for @email', [
      '@id' => $sessionId,
      '@email' => $customerEmail,
    ]);

    // Idempotency: check if tenant already exists for this Stripe customer.
    if ($customerId) {
      $lockId = 'jaraba_billing:provision_customer:' . md5($customerId);
      if (!$this->lock->acquire($lockId, 60)) {
        return new JsonResponse(['status' => 'retry', 'reason' => 'provisioning locked'], 503);
      }

      try {
        $existingTenants = $this->entityTypeManager()
          ->getStorage('tenant')
          ->loadByProperties(['stripe_customer_id' => $customerId]);

        if (!empty($existingTenants)) {
          $tenant = reset($existingTenants);
          $this->billingLogger->info('Tenant @id already exists for customer @customer, skipping provisioning.', [
            '@id' => $tenant->id(),
            '@customer' => $customerId,
          ]);

          // Still update subscription ID if needed.
          if ($tenant->hasField('stripe_subscription_id') && empty($tenant->get('stripe_subscription_id')->value)) {
            $tenant->set('stripe_subscription_id', $subscriptionId);
            $tenant->save();
          }

          return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'already_provisioned', 'tenant_id' => $tenant->id()], 'meta' => ['timestamp' => time()]]);
        }

        // Auto-provision: dos flujos posibles.
        // A) Usuario ya registrado → vincular Stripe IDs a su tenant.
        // B) Usuario nuevo → crear via TenantOnboardingService.
        $drupalPlanId = $metadata['drupal_plan_id'] ?? '';
        $vertical = $metadata['vertical'] ?? 'demo';
        $businessName = $metadata['business_name'] ?? $customerEmail;

        // Resolver plan tier desde el SaasPlan entity (drupal_plan_id en metadata).
        $planTier = 'starter';
        if ($drupalPlanId) {
          $planEntity = $this->entityTypeManager()->getStorage('saas_plan')->load($drupalPlanId);
          if ($planEntity) {
            $planName = strtolower($planEntity->getName());
            if (str_contains($planName, 'enterprise')) {
              $planTier = 'enterprise';
            }
            elseif (str_contains($planName, 'profesional') || str_contains($planName, 'professional') || str_contains($planName, 'premium')) {
              $planTier = 'professional';
            }
          }
        }

        // Flujo A: Buscar usuario existente por email.
        $existingUsers = $this->entityTypeManager()->getStorage('user')
          ->loadByProperties(['mail' => $customerEmail]);

        if (!empty($existingUsers)) {
          $user = reset($existingUsers);
          $this->billingLogger->info('Checkout completed for existing user @uid (@email).', [
            '@uid' => $user->id(),
            '@email' => $customerEmail,
          ]);

          // Buscar tenant del usuario via TenantContextService.
          if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
            $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            $tenant = $tenantContext->getTenantForUser($user);

            if ($tenant) {
              // Vincular Stripe IDs al tenant existente.
              if ($tenant->hasField('stripe_customer_id')) {
                $tenant->set('stripe_customer_id', $customerId);
              }
              if ($tenant->hasField('stripe_subscription_id')) {
                $tenant->set('stripe_subscription_id', $subscriptionId);
              }
              if ($tenant->hasField('subscription_plan') && $drupalPlanId) {
                $tenant->set('subscription_plan', $drupalPlanId);
              }
              if ($tenant->hasField('subscription_status')) {
                $tenant->set('subscription_status', 'active');
              }
              $tenant->save();

              $this->billingLogger->info('Linked Stripe IDs to existing tenant @id (plan: @plan).', [
                '@id' => $tenant->id(),
                '@plan' => $planTier,
              ]);
            }
          }
        }
        // Flujo B: Usuario nuevo → auto-provisioning completo.
        elseif (\Drupal::hasService('ecosistema_jaraba_core.tenant_onboarding')) {
          $onboarding = \Drupal::service('ecosistema_jaraba_core.tenant_onboarding');

          // Generar domain desde business_name (slug).
          $domain = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $businessName)));
          $domain = substr($domain, 0, 30) ?: 'tenant-' . time();

          // Password random (se enviara email de reset).
          $randomPassword = bin2hex(random_bytes(16));

          $registrationData = [
            'organization_name' => $businessName,
            'domain' => $domain,
            'admin_email' => $customerEmail,
            'admin_name' => $businessName,
            'password' => $randomPassword,
            'vertical_id' => $vertical,
            'plan' => $planTier,
            'drupal_plan_id' => $drupalPlanId,
            'auto_provisioned' => TRUE,
          ];

          $result = $onboarding->processRegistration($registrationData);

          if (!empty($result['tenant'])) {
            $onboarding->completeOnboarding($result['tenant'], $customerId, $subscriptionId);

            // Enviar email de password reset al usuario nuevo.
            if (!empty($result['user'])) {
              _user_mail_notify('password_reset', $result['user']);
            }

            $this->billingLogger->info('Auto-provisioned tenant @id for new user @email (plan: @plan, vertical: @vertical).', [
              '@id' => $result['tenant']->id(),
              '@email' => $customerEmail,
              '@plan' => $planTier,
              '@vertical' => $vertical,
            ]);
          }
        }
        else {
          $this->billingLogger->warning('No provisioning path available for @email.', [
            '@email' => $customerEmail,
          ]);
        }
      }
      catch (\Throwable $e) {
        $this->billingLogger->error('Auto-provisioning failed for @email: @error', [
          '@email' => $customerEmail,
          '@error' => $e->getMessage(),
        ]);
      }
      finally {
        $this->lock->release($lockId);
      }
    }

    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'processed'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Evento no reconocido.
   */
  protected function handleUnknownEvent(string $eventType): JsonResponse {
    $this->billingLogger->debug('Billing webhook ignorado: @type', ['@type' => $eventType]);
    return new JsonResponse(['success' => TRUE, 'data' => ['status' => 'ignored'], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * GAP-M04: Checks if an event has already been processed.
   */
  protected function isDuplicateEvent(string $eventId): bool {
    try {
      $count = \Drupal::database()->select('stripe_webhook_event_log', 'l')
        ->condition('event_id', $eventId)
        ->countQuery()
        ->execute()
        ->fetchField();
      return (int) $count > 0;
    }
    catch (\Throwable) {
      // Table may not exist yet — not a duplicate.
      return FALSE;
    }
  }

  /**
   * GAP-M04: Logs a processed event for deduplication.
   */
  protected function logProcessedEvent(string $eventId, string $eventType): void {
    try {
      \Drupal::database()->insert('stripe_webhook_event_log')
        ->fields([
          'event_id' => $eventId,
          'event_type' => $eventType,
          'processed_at' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();
    }
    catch (\Throwable $e) {
      // Non-critical — log but don't fail.
      $this->billingLogger->warning('Failed to log webhook event @id: @msg', [
        '@id' => $eventId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
